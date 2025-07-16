<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\Console\Migration;

use DateTimeZone;
use DBConnection;
use Glpi\Console\AbstractCommand;
use Glpi\Console\Command\ConfigurationCommandInterface;
use Glpi\Console\Exception\EarlyExitException;
use Glpi\System\Requirement\DbTimezones;
use Safe\DateTime;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Safe\preg_match;

class TimestampsCommand extends AbstractCommand implements ConfigurationCommandInterface
{
    /**
     * Error code returned when failed to migrate one table.
     *
     * @var integer
     */
    public const ERROR_TABLE_MIGRATION_FAILED = 1;

    /**
     * Error code returned if DB configuration file cannot be updated.
     *
     * @var integer
     */
    public const ERROR_UNABLE_TO_UPDATE_CONFIG = 2;

    protected function configure()
    {
        parent::configure();

        $this->setName('migration:timestamps');
        $this->setDescription(__('Convert "datetime" fields to "timestamp" to use timezones.'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //convert db

        // we are going to update datetime types to timestamp type
        $tbl_iterator = $this->db->getTzIncompatibleTables();

        $output->writeln(
            sprintf(
                '<info>' . __('Found %s table(s) requiring migration.') . '</info>',
                $tbl_iterator->count()
            )
        );

        $errors = false;

        if ($tbl_iterator->count() === 0) {
            $output->writeln('<info>' . __('No migration needed.') . '</info>');
        } else {
            $this->warnAboutExecutionTime();
            $this->askForConfirmation();

            $tables = [];
            foreach ($tbl_iterator as $table_data) {
                $tables[] = $table_data['TABLE_NAME'];
            }
            sort($tables);

            $progress_message = (fn(string $table) => sprintf(__('Migrating table "%s"...'), $table));

            foreach ($this->iterate($tables, $progress_message) as $table) {
                $tablealter = ''; // init by default

                // get accurate info from information_schema to perform correct alter
                $col_iterator = $this->db->request([
                    'SELECT' => [
                        'table_name AS TABLE_NAME',
                        'column_name AS COLUMN_NAME',
                        'column_default AS COLUMN_DEFAULT',
                        'column_comment AS COLUMN_COMMENT',
                        'is_nullable AS IS_NULLABLE',
                    ],
                    'FROM'   => 'information_schema.columns',
                    'WHERE'  => [
                        'table_schema' => $this->db->dbdefault,
                        'table_name'   => $table,
                        'data_type'    => 'datetime',
                    ],
                ]);

                foreach ($col_iterator as $column) {
                    $nullable = false;
                    $default = null;
                    //check if nullable
                    if ('YES' === $column['IS_NULLABLE']) {
                        $nullable = true;
                    }

                    // Fix invalid zero dates
                    $this->db->update(
                        $table,
                        [
                            $column['COLUMN_NAME'] => $nullable ? null : '1970-01-01 00:00:01',
                        ],
                        [
                            ['NOT' => [$column['COLUMN_NAME'] => null]],
                            [$column['COLUMN_NAME'] => ['<', '1970-01-01 00:00:01']],
                        ]
                    );

                    //guess default value
                    if (is_null($column['COLUMN_DEFAULT']) && !$nullable) { // no default
                        // Prevent MySQL/MariaDB to force "default current_timestamp on update current_timestamp"
                        // as "on update current_timestamp" could be a real problem on fields like "date_creation".
                        $default = "CURRENT_TIMESTAMP";
                    } elseif ((is_null($column['COLUMN_DEFAULT']) || strtoupper($column['COLUMN_DEFAULT']) == 'NULL') && $nullable) {
                        $default = "NULL";
                    } elseif (!is_null($column['COLUMN_DEFAULT']) && strtoupper($column['COLUMN_DEFAULT']) != 'NULL') {
                        if (preg_match('/^current_timestamp(\(\))?$/i', $column['COLUMN_DEFAULT']) === 1) {
                            $default = $column['COLUMN_DEFAULT'];
                        } elseif ($column['COLUMN_DEFAULT'] < '1970-01-01 00:00:01') {
                            // Prevent default value to be out of range (lower to min possible value)
                            $defaultDate = new DateTime('1970-01-01 00:00:01', new DateTimeZone('UTC'));
                            $defaultDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
                            $default = $this->db->quoteValue($defaultDate->format("Y-m-d H:i:s"));
                        } elseif ($column['COLUMN_DEFAULT'] > '2038-01-19 03:14:07') {
                            // Prevent default value to be out of range (greater to max possible value)
                            $defaultDate = new DateTime('2038-01-19 03:14:07', new DateTimeZone('UTC'));
                            $defaultDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
                            $default = $this->db->quoteValue($defaultDate->format("Y-m-d H:i:s"));
                        } else {
                            $default = $this->db->quoteValue($column['COLUMN_DEFAULT']);
                        }
                    }

                    //build alter
                    $tablealter .= "\n\t MODIFY COLUMN " . $this->db->quoteName($column['COLUMN_NAME']) . " TIMESTAMP";
                    if ($nullable) {
                        $tablealter .= " NULL";
                    } else {
                        $tablealter .= " NOT NULL";
                    }
                    if ($default !== null) {
                        $tablealter .= " DEFAULT $default";
                    }
                    if ($column['COLUMN_COMMENT'] != '') {
                        $tablealter .= " COMMENT '" . $this->db->escape($column['COLUMN_COMMENT']) . "'";
                    }
                    $tablealter .= ",";
                }
                $tablealter =  rtrim($tablealter, ",");

                // apply alter to table
                $query = "ALTER TABLE " . $this->db->quoteName($table) . " " . $tablealter . ";\n";

                $result = $this->db->doQuery($query);
                if (false === $result) {
                    $message = sprintf(
                        __('Migration of table "%s" failed with message "(%s) %s".'),
                        $table,
                        $this->db->errno(),
                        $this->db->error()
                    );
                    $this->outputMessage(
                        '<error>' . $message . '</error>',
                        OutputInterface::VERBOSITY_QUIET
                    );
                    $errors = true;
                }
            }
        }

        $properties_to_update = [
            DBConnection::PROPERTY_ALLOW_DATETIME => false,
        ];

        if ($this->db->use_timezones !== true) {
            $timezones_requirement = new DbTimezones($this->db);
            if ($timezones_requirement->isValidated()) {
                $properties_to_update[DBConnection::PROPERTY_USE_TIMEZONES] = true;
            } else {
                $output->writeln(
                    [
                        '<comment>' . __('Timezones usage cannot be activated due to missing requirements.') . '</comment>',
                        '<comment>' . sprintf(__('Run the "%1$s" command for more details.'), 'php bin/console database:enable_timezones') . '</comment>',
                    ],
                    OutputInterface::VERBOSITY_QUIET
                );
            }
        }

        if (!DBConnection::updateConfigProperties($properties_to_update)) {
            throw new EarlyExitException(
                '<error>' . __('Unable to update DB configuration file.') . '</error>',
                self::ERROR_UNABLE_TO_UPDATE_CONFIG
            );
        }

        if ($errors) {
            throw new EarlyExitException(
                '<error>' . __('Errors occurred during migration.') . '</error>',
                self::ERROR_TABLE_MIGRATION_FAILED
            );
        }

        if ($tbl_iterator->count() > 0) {
            $output->writeln('<info>' . __('Migration done.') . '</info>');
        }

        return 0; // Success
    }

    public function getConfigurationFilesToUpdate(InputInterface $input): array
    {
        $config_files_to_update = ['config_db.php'];
        if (file_exists(GLPI_CONFIG_DIR . '/config_db_slave.php')) {
            $config_files_to_update[] = 'config_db_slave.php';
        }
        return $config_files_to_update;
    }
}
