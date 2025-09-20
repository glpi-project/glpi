<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Tests\Command;

use DBmysql;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class TestUpdatedDataCommand extends Command
{
    protected function configure()
    {
        parent::configure();

        $this->setName(self::class);

        $this->addOption(
            'host',
            null,
            InputOption::VALUE_REQUIRED,
            'Database host'
        );

        $this->addOption(
            'port',
            null,
            InputOption::VALUE_REQUIRED,
            'Database port'
        );

        $this->addOption(
            'user',
            null,
            InputOption::VALUE_REQUIRED,
            'Database user'
        );

        $this->addOption(
            'pass',
            null,
            InputOption::VALUE_OPTIONAL,
            'Database password (will be prompted for value if option passed without value)',
            '' // Empty string by default (enable detection of null if passed without value)
        );

        $this->addOption(
            'fresh-db',
            null,
            InputOption::VALUE_REQUIRED,
            'Fresh database name (database which has been installed from scratch)'
        );

        $this->addOption(
            'updated-db',
            null,
            InputOption::VALUE_REQUIRED,
            'Updated database name (database which has been updated from a previous version)'
        );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {

        if (null === $input->getOption('pass')) {
            $question_helper = new QuestionHelper();
            $value = $question_helper->ask($input, $output, new Question('Database password:', ''));
            $input->setOption('pass', $value);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $host = $input->getOption('host');
        $port = $input->getOption('port');
        $user = $input->getOption('user');
        $pass = $input->getOption('pass');
        $hostport = $host . (!empty($port) ? ':' . $port : '');

        $fresh_db = new class ($hostport, $user, $pass, $input->getOption('fresh-db')) extends DBmysql {
            public function __construct($dbhost, $dbuser, $dbpassword, $dbdefault)
            {
                $this->dbhost     = $dbhost;
                $this->dbuser     = $dbuser;
                $this->dbpassword = $dbpassword;
                $this->dbdefault  = $dbdefault;
                parent::__construct();
            }
        };

        $updated_db = new class ($hostport, $user, $pass, $input->getOption('updated-db')) extends DBmysql {
            public function __construct($dbhost, $dbuser, $dbpassword, $dbdefault)
            {
                $this->dbhost     = $dbhost;
                $this->dbuser     = $dbuser;
                $this->dbpassword = $dbpassword;
                $this->dbdefault  = $dbdefault;
                parent::__construct();
            }
        };

        $error = false;

        if (!$this->hasSameConfigurationEntries($fresh_db, $updated_db, $output)) {
            $error = true;
        }

        if ($this->hasMissingRowsInUpdatedDb($fresh_db, $updated_db, $output)) {
            $error = true;
        }

        return $error ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Check if there are a the same configuration entries in both fresh and update table.
     */
    private function hasSameConfigurationEntries(DBmysql $fresh_db, DBmysql $updated_db, OutputInterface $output): bool
    {
        $fresh_config_entries = \array_map(
            fn(array $row) => $row['context'] . ':' . $row['name'],
            \iterator_to_array($fresh_db->request(['FROM' => 'glpi_configs']))
        );
        \sort($fresh_config_entries);

        $updated_config_entries = \array_map(
            fn(array $row) => $row['context'] . ':' . $row['name'],
            \iterator_to_array($updated_db->request(['FROM' => 'glpi_configs']))
        );
        \sort($updated_config_entries);

        if ($fresh_config_entries !== $updated_config_entries) {
            foreach (\array_diff($fresh_config_entries, $updated_config_entries) as $missing_config) {
                $msg = sprintf('Unable to find the following configuration entry in the updated database: %s', $missing_config);
                $output->writeln('<error>‣</error> ' . $msg, OutputInterface::VERBOSITY_QUIET);
            }
            foreach (\array_diff($updated_config_entries, $fresh_config_entries) as $unexpected_config) {
                $msg = sprintf('Unexpected configuration entry found in the updated database: %s', $unexpected_config);
                $output->writeln('<error>‣</error> ' . $msg, OutputInterface::VERBOSITY_QUIET);
            }

            return false;
        }

        return true;
    }

    /**
     * Check if there are missing table rows in the updated database compared to the fresh database.
     */
    private function hasMissingRowsInUpdatedDb(DBmysql $fresh_db, DBmysql $updated_db, OutputInterface $output): bool
    {
        $missing = false;

        $table_iterator = $fresh_db->listTables(
            'glpi\_%',
            [
                ['NOT' => ['table_name' => ['LIKE', 'glpi\_plugin\_%']]],
                ['NOT' => ['table_name' => $this->getExcludedTables()]],
            ]
        );

        foreach ($table_iterator as $table_data) {
            $table_name = $table_data['TABLE_NAME'];

            $itemtype = getItemTypeForTable($table_name);

            $excluded_fields = $this->getExcludedFields($table_name);
            $excluded_fields[] = $itemtype != null ? $itemtype::getIndexName() : 'id';

            $row_iterator = $fresh_db->request(['FROM' => $table_name]);
            foreach ($row_iterator as $row_data) {
                $criteria = [];

                // Ignore e2e_tests user
                if ($table_name === 'glpi_users' && $row_data['name'] === 'e2e_tests') {
                    continue;
                }

                // Ignore e2e oauth client
                if ($table_name === 'glpi_oauthclients' && $row_data['name'] === 'Test E2E OAuth Client') {
                    continue;
                }

                // Ignore e2e LDAP
                if ($table_name === 'glpi_authldaps' && $row_data['name'] === '_e2e_ldap') {
                    continue;
                }

                foreach ($row_data as $key => $value) {
                    if (in_array($key, $excluded_fields)) {
                        continue; // Ignore fields that would be subject to legitimate changes
                    }
                    $field_type = $this->getFieldType($fresh_db, $table_name, $key);
                    if ($value === null && !in_array($field_type, ['datetime', 'timestamp'], true)) {
                        $empty_value = '';
                        if (in_array($field_type, ['int', 'tinyint'])) {
                            $empty_value = 0;
                        }

                        // some fields were not nullable in previous GLPI versions
                        $criteria[] = [
                            'OR' => [
                                [$key => $empty_value],
                                [$key => null],
                            ],
                        ];
                    } elseif ($field_type === 'json') {
                        $criteria[$key] = new QueryExpression(
                            QueryFunction::cast(
                                new QueryExpression($fresh_db->quoteValue($value)),
                                'JSON'
                            ),
                        );
                    } else {
                        $criteria[$key] = $value;
                    }
                }

                $found_in_updated = $updated_db->request(
                    [
                        'FROM'  => $table_name,
                        'WHERE' => $criteria,
                    ]
                );
                if ($found_in_updated->count() !== 1) {
                    $missing = true;
                    $msg = sprintf('Unable to find the following object in table "%s": %s', $table_name, json_encode($row_data));
                    $output->writeln('<error>‣</error> ' . $msg, OutputInterface::VERBOSITY_QUIET);
                }
            }
        }

        return $missing;
    }

    /**
     * Return list of tables to exclude from comparison.
     *
     * @return array
     */
    private function getExcludedTables(): array
    {
        return [
            // Config entries are tested separately (see `self::hasSameConfigurationEntries()`)
            'glpi_configs',

            // Root entity configuration is never updated during migration
            'glpi_entities',

            // Migration may produce logs
            'glpi_logs',

            // Notifications update is complex and following cases can result in differences between updated data and fresh install:
            // - existing templates are never updated;
            // - existing templates are rarely reused, as they can have been modified/deleted;
            // - new notifications events/targets defaults are not always applied during update, to let administrator decide how to configure them;
            // - ...
            'glpi_notifications',
            'glpi_notifications_notificationtemplates',
            'glpi_notificationtargets',
            'glpi_notificationtemplates',
            'glpi_notificationtemplatetranslations',

            // Profiles are not automatically updated
            'glpi_profilerights',
            'glpi_profiles',
            'glpi_profiles_users',

            // Rules are not automatically updated
            'glpi_rules',
            'glpi_rulecriterias',
            'glpi_ruleactions',

            // Dashbords may have placeholders which are only present on new installs
            'glpi_dashboards_dashboards',
            'glpi_dashboards_items',
        ];
    }

    /**
     * Return list of fields to exclude from comparison.
     * Keys are table name (or * for fields that should be excluded for all tables).
     * Values are an array of fields identifiers.
     *
     * @return array
     */
    private function getExcludedFields(string $table_name): array
    {
        $excluded_fields = [
            '*' => [
                'comment', // Some items contains comments like 'Automatically generated by GLPI X.X.X'
                'date_creation',
                'date_mod',
                // By definition, any uuid fields should always be unique
                'uuid',
                'forms_sections_uuid',
            ],
            'glpi_crontasks' => [
                'frequency', // Field default value may have changed
                'hourmin', // Field default value may have changed
                'hourmax', // Field default value may have changed
                'lastrun',
            ],
            'glpi_displaypreferences' => [
                'rank', // New display preferences are added with next available rank by migrations
            ],
            'glpi_requesttypes' => [
                'is_followup_default', // Field value was not forced by migration (0.90.x to 9.1.0)
                'is_mailfollowup_default', // Field value was not forced by migration (0.90.x to 9.1.0)
            ],
            'glpi_softwarecategories' => [
                'name', // 'FUSION' has not been automatically renamed to 'Inventoried' by migration (9.5.x to 10.0.0)
                'completename', // 'FUSION' has not been automatically renamed to 'Inventoried' by migration (9.5.x to 10.0.0)
            ],
            'glpi_users' => [
                'password',
            ],
        ];
        return array_merge(
            $excluded_fields['*'],
            $excluded_fields[$table_name] ?? []
        );
    }

    /**
     * Return field type.
     *
     * @param DBmysql $db
     * @param string $table
     * @param string $field
     *
     * @return string|null
     */
    private function getFieldType(DBmysql $db, string $table, string $field): ?string
    {
        static $types;

        if ($types === null) {
            $types = [];

            $fields_iterator = $db->request(
                [
                    'SELECT'   => [
                        'table_name as TABLE_NAME',
                        'column_name AS COLUMN_NAME',
                        'data_type  as DATA_TYPE',
                    ],
                    'FROM'     => 'information_schema.columns',
                    'WHERE'    => [
                        'table_schema' => $db->dbdefault,
                        'table_name'   => ['LIKE', 'glpi\_%'],
                    ],
                ]
            );
            foreach ($fields_iterator as $field_data) {
                $table_name = $field_data['TABLE_NAME'];
                $field_name = $field_data['COLUMN_NAME'];
                $field_type = $field_data['DATA_TYPE'];
                if (!array_key_exists($table_name, $types)) {
                    $types[$table_name] = [];
                }
                $types[$table_name][$field_name] = $field_type;
            }
        }

        return $types[$table][$field] ?? null;
    }
}
