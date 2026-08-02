<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
use Glpi\DBAL\QueryExpression;
use Glpi\System\Requirement\DbTimezones;
use LogicException;
use Safe\DateTime;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Throwable;

use function Safe\preg_match;

class TimestampsCommand extends AbstractCommand implements ConfigurationCommandInterface
{
    /**
     * Minimum value supported by MySQL/MariaDB TIMESTAMP type, in UTC.
     */
    private const TIMESTAMP_MIN_VALUE = '1970-01-01 00:00:01';

    /**
     * Maximum value supported by the standard MariaDB (32-bit)/MySQL TIMESTAMP type, in UTC.
     */
    private const TIMESTAMP_MAX_VALUE = '2038-01-19 03:14:07';

    /**
     * Maximum value supported by the extended (64-bit) MariaDB 11.5+ TIMESTAMP type, in UTC.
     */
    private const TIMESTAMP_MAX_VALUE_EXTENDED = '2106-02-07 06:28:15';

    /**
     * Error code returned when failed to migrate one table.
     *
     * @var int
     */
    public const ERROR_TABLE_MIGRATION_FAILED = 1;

    /**
     * Error code returned if DB configuration file cannot be updated.
     *
     * @var int
     */
    public const ERROR_UNABLE_TO_UPDATE_CONFIG = 2;

    /**
     * Error code returned when future dates beyond the server TIMESTAMP range are detected
     * and explicit consent to clamp them was not given.
     *
     * @var int
     */
    public const ERROR_FUTURE_DATES_REQUIRE_CONSENT = 3;

    /**
     * Cached server TIMESTAMP upper bound (resolved once per execution).
     * Null until resolved by {@link getTimestampMaxValue()}.
     */
    private ?string $timestamp_max_value = null;

    protected function configure()
    {
        parent::configure();

        $this->setName('migration:timestamps');
        $this->setDescription(__('Convert "datetime" fields to "timestamp" to use timezones.'));
        $this->addOption(
            'allow-future-date-clamping',
            null,
            InputOption::VALUE_NONE,
            __('Authorize clamping of future dates beyond the server TIMESTAMP range without prompting.')
        );
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

            $this->confirmFutureDateClamping($tables);

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

                    // Fix invalid zero dates and dates out of TIMESTAMP range
                    $this->normalizeOutOfRangeValues($table, $column['COLUMN_NAME'], $nullable);

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
                        } elseif ($column['COLUMN_DEFAULT'] < self::TIMESTAMP_MIN_VALUE) {
                            // Prevent default value to be out of range (lower to min possible value)
                            $default = $this->db->quoteValue($this->getTimestampBoundValue(self::TIMESTAMP_MIN_VALUE));
                        } elseif ($column['COLUMN_DEFAULT'] > $this->getTimestampMaxValue()) {
                            // Prevent default value to be out of range (greater to max possible value)
                            $default = $this->db->quoteValue($this->getTimestampBoundValue($this->getTimestampMaxValue()));
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

    private function normalizeOutOfRangeValues(string $table, string $column, bool $nullable): void
    {
        $min_value = $this->getTimestampBoundValue(self::TIMESTAMP_MIN_VALUE);
        $max_value = $this->getTimestampBoundValue($this->getTimestampMaxValue());

        $this->updateOutOfRangeValues(
            $table,
            $column,
            ['<', $min_value],
            $nullable ? null : $min_value,
            sprintf(__('less than "%s"'), $min_value)
        );

        $this->updateOutOfRangeValues(
            $table,
            $column,
            ['>', $max_value],
            $max_value,
            sprintf(__('greater than "%s"'), $max_value)
        );
    }

    /**
     * @param array<int, string> $condition
     */
    private function updateOutOfRangeValues(
        string $table,
        string $column,
        array $condition,
        ?string $replacement,
        string $condition_description
    ): void {
        if ($this->db === null) {
            throw new LogicException(); // To make PHPStan happy
        }
        $this->db->update(
            $table,
            [
                $column => $replacement,
            ],
            [
                ['NOT' => [$column => null]],
                [$column => $condition],
            ]
        );

        $affected_rows = $this->db->affectedRows();
        if ($affected_rows <= 0) {
            return;
        }

        $this->outputMessage(
            '<comment>' . sprintf(
                __('%1$s row(s) from "%2$s"."%3$s" containing values %4$s were updated to "%5$s" to fit TIMESTAMP bounds.'),
                $affected_rows,
                $table,
                $column,
                $condition_description,
                $replacement ?? 'NULL'
            ) . '</comment>'
        );
    }

    private function getTimestampBoundValue(string $value): string
    {
        $date = new DateTime($value, new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone(date_default_timezone_get()));

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Resolve the server's TIMESTAMP upper bound, in UTC.
     *
     * Uses a behavioral probe: attempts to CAST the extended (64-bit MariaDB 11.5+) max
     * value to TIMESTAMP. If the server accepts it, the extended range is supported;
     * otherwise falls back to the standard 32-bit limit. The result is cached for the
     * command lifetime.
     */
    private function getTimestampMaxValue(): string
    {
        if ($this->timestamp_max_value !== null) {
            return $this->timestamp_max_value;
        }

        $extended = false;
        try {
            $iterator = $this->db->request([
                'SELECT' => new QueryExpression(
                    sprintf("CAST('%s' AS TIMESTAMP) AS probe", self::TIMESTAMP_MAX_VALUE_EXTENDED)
                ),
            ]);
            foreach ($iterator as $row) {
                $extended = isset($row['probe']) && $row['probe'] === self::TIMESTAMP_MAX_VALUE_EXTENDED;
                break;
            }
        } catch (Throwable $e) {
            $extended = false;
        }

        $this->timestamp_max_value = $extended
            ? self::TIMESTAMP_MAX_VALUE_EXTENDED
            : self::TIMESTAMP_MAX_VALUE;

        return $this->timestamp_max_value;
    }

    /**
     * Pre-scan the tables to migrate for datetime values beyond the server's TIMESTAMP
     * upper bound and ask for explicit consent before clamping them.
     *
     * Pre-1970 dates are considered genuinely invalid and are clamped silently during
     * migration; only future dates beyond the server max require consent.
     *
     * @param list<string> $tables
     */
    private function confirmFutureDateClamping(array $tables): void
    {
        $max_value = $this->getTimestampBoundValue($this->getTimestampMaxValue());

        $affected = [];
        $total_rows = 0;

        foreach ($tables as $table) {
            $col_iterator = $this->db->request([
                'SELECT' => ['column_name AS COLUMN_NAME'],
                'FROM'   => 'information_schema.columns',
                'WHERE'  => [
                    'table_schema' => $this->db->dbdefault,
                    'table_name'   => $table,
                    'data_type'    => 'datetime',
                ],
            ]);

            foreach ($col_iterator as $column) {
                $column_name = $column['COLUMN_NAME'];
                $count_iterator = $this->db->request([
                    'COUNT'  => 'cpt',
                    'FROM'   => $table,
                    'WHERE'  => [
                        ['NOT' => [$column_name => null]],
                        [$column_name => ['>', $max_value]],
                    ],
                ]);
                $count = (int) ($count_iterator->current()['cpt'] ?? 0);
                if ($count > 0) {
                    $affected[] = ['table' => $table, 'column' => $column_name, 'rows' => $count];
                    $total_rows += $count;
                }
            }
        }

        if ($total_rows === 0) {
            return;
        }

        $this->output->writeln(
            '<comment>' . sprintf(
                __('%1$s row(s) with future dates beyond the server TIMESTAMP limit (%2$s) were detected and will be clamped to that limit, destroying the original values:'),
                $total_rows,
                $max_value
            ) . '</comment>',
            OutputInterface::VERBOSITY_QUIET
        );
        foreach ($affected as $item) {
            $this->output->writeln(
                '<comment>' . sprintf(
                    __('- "%1$s"."%2$s": %3$s row(s)'),
                    $item['table'],
                    $item['column'],
                    $item['rows']
                ) . '</comment>',
                OutputInterface::VERBOSITY_QUIET
            );
        }

        if ($this->input->getOption('allow-future-date-clamping')) {
            return;
        }

        if (!$this->input->getOption('no-interaction')) {
            $question_helper = new QuestionHelper();
            $confirmed = $question_helper->ask(
                $this->input,
                $this->output,
                new ConfirmationQuestion(
                    sprintf(
                        __('Continue and clamp these future dates to "%s"? [yes/No]'),
                        $max_value
                    ),
                    false
                )
            );
            if (!$confirmed) {
                throw new EarlyExitException(
                    '<comment>' . __('Aborted.') . '</comment>',
                    0
                );
            }
            return;
        }

        throw new EarlyExitException(
            '<error>' . sprintf(
                __('Future dates beyond the server TIMESTAMP range were detected. Either fix them, run the command interactively, or pass the "%1$s" option to authorize clamping.'),
                '--allow-future-date-clamping'
            ) . '</error>',
            self::ERROR_FUTURE_DATES_REQUIRE_CONSENT
        );
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
