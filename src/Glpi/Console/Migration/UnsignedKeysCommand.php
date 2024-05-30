<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use DBConnection;
use Glpi\Console\AbstractCommand;
use Glpi\Console\Command\ConfigurationCommandInterface;
use Plugin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UnsignedKeysCommand extends AbstractCommand implements ConfigurationCommandInterface
{
    /**
     * Error code returned when failed to migrate one column.
     *
     * @var int
     */
    const ERROR_COLUMN_MIGRATION_FAILED = 1;

    /**
     * Error code returned if DB configuration file cannot be updated.
     *
     * @var integer
     */
    const ERROR_UNABLE_TO_UPDATE_CONFIG = 2;

    protected function configure()
    {
        parent::configure();

        $this->setName('migration:unsigned_keys');
        $this->setDescription(__('Migrate primary/foreign keys to unsigned integers'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $columns = $this->db->getSignedKeysColumns();

        $output->writeln(
            sprintf(
                '<info>' . __('Found %s primary/foreign key columns(s) using signed integers.') . '</info>',
                $columns->count()
            )
        );

        $errors = false;
        $errored_plugins = [];

        if ($columns->count() === 0) {
            $output->writeln('<info>' . __('No migration needed.') . '</info>');
        } else {
            $this->warnAboutExecutionTime();
            $this->askForConfirmation();

            $foreign_keys = $this->db->getForeignKeysContraints();

            $progress_message = function (array $column) {
                return sprintf(__('Migrating column "%s.%s"...'), $column['TABLE_NAME'], $column['COLUMN_NAME']);
            };

            foreach ($this->iterate($columns, $progress_message) as $column) {
                $table_name  = $column['TABLE_NAME'];
                $column_name = $column['COLUMN_NAME'];
                $data_type   = $column['DATA_TYPE'];
                $nullable    = $column['IS_NULLABLE'] === 'YES';
                $default     = $column['COLUMN_DEFAULT'];
                $extra       = $column['EXTRA'];

                $plugin_matches  = [];
                $is_plugin_table = preg_match('/^glpi_plugin_(?<plugin_key>[^_]+)_/', $table_name, $plugin_matches) === 1;
                $plugin_key      = $is_plugin_table ? $plugin_matches['plugin_key'] : null;

                // Ensure that column is not referenced in a CONSTRAINT key.
                foreach ($foreign_keys as $foreign_key) {
                    if (
                        ($foreign_key['TABLE_NAME'] === $table_name && $foreign_key['COLUMN_NAME'] === $column_name)
                        || ($foreign_key['REFERENCED_TABLE_NAME'] === $table_name && $foreign_key['REFERENCED_COLUMN_NAME'] === $column_name)
                    ) {
                        $message = sprintf(
                            __('Migration of column "%s.%s" cannot be done as it is referenced in CONSTRAINT "%s" of table "%s.%s".'),
                            $table_name,
                            $column_name,
                            $foreign_key['CONSTRAINT_NAME'],
                            $foreign_key['TABLE_NAME'],
                            $foreign_key['COLUMN_NAME']
                        );
                        $this->outputMessage(
                            '<error>' . $message . '</error>',
                            OutputInterface::VERBOSITY_QUIET
                        );
                        $errors = true;
                        if ($is_plugin_table) {
                            $errored_plugins[] = $plugin_key;
                        }
                        continue 2; // Non blocking error, it should not prevent migration of other fields
                    }
                }

                // Ensure that column has not a negative default value
                if ($default !== null && $default < 0) {
                    $message = sprintf(
                        __('Migration of column "%s.%s" cannot be done as its default value is negative.'),
                        $table_name,
                        $column_name
                    );
                    $this->outputMessage(
                        '<error>' . $message . '</error>',
                        OutputInterface::VERBOSITY_QUIET
                    );
                    $errors = true;
                    if ($is_plugin_table) {
                        $errored_plugins[] = $plugin_key;
                    }
                    continue; // Do not migrate this column
                }

                // Check for negative values in table data
                $min = $this->db
                    ->request(['SELECT' => ['MIN' => sprintf('%s AS min', $column_name)], 'FROM' => $table_name])
                    ->current()['min'];
                if ($min !== null && $min < 0) {
                    if (!$is_plugin_table) {
                        // Force migration of unconsistent -1 values in core tables
                        $forced_value = $default !== null || $nullable ? $default : 0;
                        $message = sprintf(
                            __('Column "%s.%s" contains negative values. Updating them to "%s"...'),
                            $table_name,
                            $column_name,
                            $forced_value === null ? 'NULL' : $forced_value
                        );
                        $this->outputMessage('<comment>' . $message . '</comment>');

                        $result = $this->db->update(
                            $table_name,
                            [$column_name => $forced_value],
                            [$column_name => ['<', 0]]
                        );
                        if ($result === false) {
                            $message = sprintf(
                                __('Updating column "%s.%s" values failed with message "(%s) %s".'),
                                $table_name,
                                $column_name,
                                $this->db->errno(),
                                $this->db->error()
                            );
                            $this->outputMessage(
                                '<error>' . $message . '</error>',
                                OutputInterface::VERBOSITY_QUIET
                            );
                            $errors = true;
                            continue; // Go to next column
                        }
                    } else {
                        // Cannot determine whether -1 values in plugin tables are legitimate (bad foreign key design)
                        // or inconsistent (wrong value inserted in DB)
                        $message = sprintf(
                            __('Migration of column "%s.%s" cannot be done as it contains negative values.'),
                            $table_name,
                            $column_name
                        );
                        $this->outputMessage(
                            '<error>' . $message . '</error>',
                            OutputInterface::VERBOSITY_QUIET
                        );
                        $errors = true;
                        $errored_plugins[] = $plugin_key;
                        continue; // Do not migrate this column
                    }
                }

                $query = sprintf(
                    'ALTER TABLE %s MODIFY COLUMN %s %s unsigned %s %s %s',
                    $this->db->quoteName($table_name),
                    $this->db->quoteName($column_name),
                    $data_type,
                    $nullable ? 'NULL' : 'NOT NULL',
                    $default !== null || $nullable ? sprintf('DEFAULT %s', $this->db->quoteValue($default)) : '',
                    $extra
                );

                $result = $this->db->doQuery($query);

                if ($result === false) {
                    $message = sprintf(
                        __('Migration of column "%s.%s" failed with message "(%s) %s".'),
                        $table_name,
                        $column_name,
                        $this->db->errno(),
                        $this->db->error()
                    );
                    $this->outputMessage(
                        '<error>' . $message . '</error>',
                        OutputInterface::VERBOSITY_QUIET
                    );
                    $errors = true;
                    if ($is_plugin_table) {
                        $errored_plugins[] = $plugin_key;
                    }
                    continue; // Go to next column
                }
            }
        }

        if (!DBConnection::updateConfigProperty(DBConnection::PROPERTY_ALLOW_SIGNED_KEYS, false)) {
            throw new \Glpi\Console\Exception\EarlyExitException(
                '<error>' . __('Unable to update DB configuration file.') . '</error>',
                self::ERROR_UNABLE_TO_UPDATE_CONFIG
            );
        }

        if ($errors) {
            $message = '<error>' . __('Errors occurred during migration.') . '</error>';
            if (count($errored_plugins) > 0) {
                $errored_plugins = array_unique($errored_plugins);
                $plugin = new Plugin();
                $plugins_names = [];
                foreach ($errored_plugins as $errored_plugin) {
                    $plugins_names[] = $plugin->getInformationsFromDirectory($errored_plugin)['name'] ?? $errored_plugin;
                }
                $message .= "\n";
                $message .= sprintf(
                    '<comment>' . __('Some errors are related to following plugins: %s.') . '</comment>',
                    implode(', ', $plugins_names)
                );
                $message .= "\n";
                $message .= '<comment>' . __('You should try to update these plugins to their latest version and run the command again.') . '</comment>';
            }
            throw new \Glpi\Console\Exception\EarlyExitException(
                $message,
                self::ERROR_COLUMN_MIGRATION_FAILED
            );
        }

        if ($columns->count() > 0) {
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
