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
use Glpi\Event;
use Glpi\Helpdesk\DefaultDataManager;
use Glpi\Message\MessageType;
use Glpi\OAuth\Server;
use Glpi\Progress\AbstractProgressIndicator;
use Glpi\Rules\RulesManager;
use Glpi\System\Diagnostic\DatabaseSchemaIntegrityChecker;
use Glpi\Toolbox\VersionParser;
use Psr\Log\LoggerAwareTrait;

use function Safe\preg_match;
use function Safe\preg_replace;

/**
 *  Update class
 **/
class Update
{
    use LoggerAwareTrait;

    private $DB;
    private $version;
    private $language;

    /**
     * Directory containing migrations.
     *
     * @var string
     */
    private $migrations_directory;

    /**
     * Constructor
     *
     * @param object $DB   Database instance
     * @param string $migrations_directory
     *
     * @since 11.0.0 The `$args` parameter has been removed.
     */
    public function __construct($DB, string $migrations_directory = GLPI_ROOT . '/install/migrations/')
    {
        $this->DB = $DB;
        $this->migrations_directory = $migrations_directory;
    }

    /**
     * Get current values (versions, lang, ...)
     *
     * @return array
     */
    public function getCurrents()
    {
        $currents = [];
        $DB = $this->DB;

        if (!$DB->tableExists('glpi_config') && !$DB->tableExists('glpi_configs')) {
            if ($DB->listTables()->count() > 0) {
                // < 0.31
                // Version was not yet stored in DB
                $currents = [
                    'version'   => '0.1',
                    'dbversion' => '0.1',
                    'language'  => 'en_GB',
                ];
            } else {
                // Not a GLPI database
                $currents = [
                    'version'   => null,
                    'dbversion' => null,
                    'language'  => 'en_GB',
                ];
            }
        } elseif (!$DB->tableExists("glpi_configs")) {
            // >= 0.31 and < 0.78
            // Get current version
            $result = $DB->request([
                'SELECT' => ['version', 'language'],
                'FROM'   => 'glpi_config',
            ])->current();

            $currents['version']    = trim($result['version']);
            $currents['dbversion']  = $currents['version'];
            $currents['language']   = trim($result['language']);
        } elseif ($DB->fieldExists('glpi_configs', 'version')) {
            // < 0.85
            // Get current version and language
            $result = $DB->request([
                'SELECT' => ['version', 'language'],
                'FROM'   => 'glpi_configs',
            ])->current();

            $currents['version']    = trim($result['version']);
            $currents['dbversion']  = $currents['version'];
            $currents['language']   = trim($result['language']);
        } else {
            // >= 0.85
            $values = Config::getConfigurationValues(
                'core',
                ['version', 'dbversion', 'language']
            );

            $currents['version']   = $values['version'] ?? null;
            $currents['dbversion'] = $values['dbversion'] ?? $currents['version']; // `dbversion` was not existing prior to 9.2.0
            $currents['language']  = $values['language'] ?? 'en_GB';
        }

        $this->version    = $currents['version'];
        $this->language   = $currents['language'];

        return $currents;
    }

    /**
     * Verify the database schema integrity.
     *
     * @return bool
     */
    final public function isUpdatedSchemaConsistent(): bool
    {
        global $DB;

        $checker = new DatabaseSchemaIntegrityChecker($DB, false, true, true, true, true, true);
        $differences = $checker->checkCompleteSchema(
            sprintf('%s/install/mysql/glpi-empty.sql', GLPI_ROOT),
            true
        );

        return count($differences) === 0;
    }

    /**
     * Run updates
     *
     * @param string $current_version  Current version
     * @param bool   $force_latest     Force replay of latest migration
     *
     * @return bool
     */
    public function doUpdates(
        $current_version = null,
        bool $force_latest = false,
        ?AbstractProgressIndicator $progress_indicator = null
    ): bool {
        if ($current_version === null) {
            if ($this->version === null) {
                throw new RuntimeException('Cannot process updates without any version specified!');
            }
            $current_version = $this->version;
        }

        if (version_compare($current_version, '0.85.5', 'lt')) {
            $progress_indicator?->addMessage(
                MessageType::Error,
                sprintf(__('Upgrade from version lower than %s is not supported.'), '0.85.5')
            );
            $progress_indicator?->fail();
            return false;
        }
        if (version_compare($current_version, GLPI_VERSION, '>')) {
            $progress_indicator?->addMessage(
                MessageType::Error,
                sprintf(__('Downgrading to version %s is not supported.'), GLPI_VERSION)
            );
            $progress_indicator?->fail();
            return false;
        }

        $DB = $this->DB;

        $support_legacy_data = version_compare(VersionParser::getNormalizedVersion($current_version), '10.0.0', '>=')
            ? (Config::getConfigurationValue('core', 'support_legacy_data') ?? true)
            : true;
        if ($support_legacy_data) {
            // Remove strict flags to prevent failure on invalid legacy data.
            // e.g. with `NO_ZERO_DATE` flag `ALTER TABLE` operations fails when a row contains a `0000-00-00 00:00:00` datetime value.
            // Unitary removal of these flags is not pôssible as MySQL 8.0 triggers warning if
            // `STRICT_{ALL|TRANS}_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO` are not used all together.
            $sql_mode = $DB->doQuery(sprintf('SELECT @@sql_mode as %s', $DB->quoteName('sql_mode')))->fetch_assoc()['sql_mode'] ?? '';
            $sql_mode_flags = array_filter(
                explode(',', $sql_mode),
                fn(string $flag) => !in_array(
                    trim($flag),
                    [
                        'STRICT_ALL_TABLES',
                        'STRICT_TRANS_TABLES',
                        'NO_ZERO_IN_DATE',
                        'NO_ZERO_DATE',
                        'ERROR_FOR_DIVISION_BY_ZERO',
                    ]
                )
            );
            $DB->doQuery(sprintf('SET SESSION sql_mode = %s', $DB->quote(implode(',', $sql_mode_flags))));
        }

        $migrations = $this->getMigrationsToDo($current_version, $force_latest);

        $number_of_steps = count($migrations);
        $init_form_weight = (int) round($number_of_steps * 0.1); // 10 % of the update process
        $init_rules_weight = (int) round($number_of_steps * 0.1); // 10 % of the update process
        $structure_check_weight = (int) round($number_of_steps * 0.02); // 2 % of the update process
        $post_update_weight = 1;
        $cron_config_weight = 1;
        $generate_keys_weight = (int) round($number_of_steps * 0.02); // 2 % of the update process
        $number_of_steps = count($migrations)
            + $init_form_weight
            + $init_rules_weight
            + $structure_check_weight
            + $post_update_weight
            + $generate_keys_weight;
        if (GLPI_SYSTEM_CRON) {
            $number_of_steps += $cron_config_weight;
        }

        $progress_indicator?->setMaxSteps($number_of_steps);

        foreach ($migrations as $key => $migration_specs) {
            $progress_indicator?->setProgressBarMessage(sprintf(__('Upgrading to %s…'), $migration_specs['target_version']));

            include_once($migration_specs['file']);

            try {
                $migration_specs['function']();
            } catch (Throwable $e) {
                $progress_indicator?->addMessage(
                    MessageType::Error,
                    sprintf(
                        __('An error occurred during the update. The error was: %s'),
                        $e->getMessage()
                    )
                );
                $progress_indicator?->fail();
                $this->logger?->error($e->getMessage(), context: ['exception' => $e]);
                return false;
            }

            if ($key !== array_key_last($migrations)) {
                // Set current version to target version to ensure complete migrations to not be replayed if one
                // of remaining migrations fails.
                //
                // /!\ Do not dot this for last migration:
                // 1. This should be done at the end of the whole update process.
                // 2. Last migration target version value may be higher than GLPI_VERSION, when GLPI_VERSION uses a pre-release suffix.
                $DB->updateOrInsert(
                    'glpi_configs',
                    [
                        'value' => $migration_specs['target_version'],
                    ],
                    [
                        'context' => 'core',
                        'name'    => 'version',
                    ]
                );
            }

            $progress_indicator?->advance();
        }

        // Create default forms
        $progress_indicator?->setProgressBarMessage(__('Creating default forms…'));
        Session::loadAllCoreLocales();
        $helpdesk_data_manager = new DefaultDataManager();
        $helpdesk_data_manager->initializeDataIfNeeded();
        $progress_indicator?->advance($init_form_weight);
        $progress_indicator?->addMessage(MessageType::Success, __('Default forms created.'));

        // Initalize rules
        $progress_indicator?->setProgressBarMessage(__('Initializing default rules…'));
        RulesManager::initializeRules();
        $progress_indicator?->advance($init_rules_weight);
        $progress_indicator?->addMessage(MessageType::Success, __('Default rules initialized.'));

        $progress_indicator?->setProgressBarMessage(__('Checking the database structure…'));
        if (($myisam_count = $DB->getMyIsamTables()->count()) > 0) {
            $progress_indicator?->addMessage(
                MessageType::Warning,
                sprintf(__('%d tables are using the deprecated MyISAM storage engine.'), $myisam_count)
                    . ' '
                    . sprintf(__('Run the "%1$s" command to migrate them.'), 'php bin/console migration:myisam_to_innodb')
            );
        }
        if (($datetime_count = $DB->getTzIncompatibleTables()->count()) > 0) {
            $progress_indicator?->addMessage(
                MessageType::Warning,
                sprintf(__('%1$s columns are using the deprecated datetime storage field type.'), $datetime_count)
                    . ' '
                    . sprintf(__('Run the "%1$s" command to migrate them.'), 'php bin/console migration:timestamps')
            );
        }
        if (($non_utf8mb4_count = $DB->getNonUtf8mb4Tables()->count()) > 0) {
            $progress_indicator?->addMessage(
                MessageType::Warning,
                sprintf(__('%1$s tables are using the deprecated utf8mb3 storage charset.'), $non_utf8mb4_count)
                    . ' '
                    . sprintf(__('Run the "%1$s" command to migrate them.'), 'php bin/console migration:utf8mb4')
            );
        }
        if (($signed_keys_col_count = $DB->getSignedKeysColumns()->count()) > 0) {
            $progress_indicator?->addMessage(
                MessageType::Warning,
                sprintf(__('%d primary or foreign keys columns are using signed integers.'), $signed_keys_col_count)
                    . ' '
                    . sprintf(__('Run the "%1$s" command to migrate them.'), 'php bin/console migration:unsigned_keys')
            );
        }
        $progress_indicator?->advance($structure_check_weight);

        $progress_indicator?->setProgressBarMessage(__('Finalizing the update…'));

        if (GLPI_SYSTEM_CRON) {
            // Downstream packages may provide a good system cron
            $DB->update(
                'glpi_crontasks',
                [
                    'mode'   => 2,
                ],
                [
                    'name'      => ['!=', 'watcher'],
                    'allowmode' => ['&', 2],
                ]
            );
            $progress_indicator?->advance($cron_config_weight);
        }

        // Update version number and default langage and new version_founded ---- LEAVE AT THE END
        $configs = [
            'version'             => GLPI_VERSION,
            'dbversion'           => GLPI_SCHEMA_VERSION,
            'language'            => $this->language,
            'founded_new_version' => '',
        ];
        foreach ($configs as $name => $value) {
            $DB->updateOrInsert(
                'glpi_configs',
                [
                    'value' => $value,
                ],
                [
                    'context' => 'core',
                    'name'    => $name,
                ]
            );
        }

        // Reset telemetry if its state is running, assuming it remained stuck due to telemetry service issue (see #7492).
        $crontask_telemetry = new CronTask();
        $crontask_telemetry->getFromDBbyName("Telemetry", "telemetry");
        if ($crontask_telemetry->fields['state'] === CronTask::STATE_RUNNING) {
            $crontask_telemetry->resetDate();
            $crontask_telemetry->resetState();
        }

        $progress_indicator?->advance($post_update_weight);

        $progress_indicator?->setProgressBarMessage(__('Generating security keys…'));

        //generate security key if missing, and update db
        $glpikey = new GLPIKey();
        if (!$glpikey->keyExists() && !$glpikey->generate()) {
            $progress_indicator?->addMessage(
                MessageType::Error,
                sprintf(
                    __('Unable to create security key file! You have to run the "%s" command to manually create this file.'),
                    'php bin/console security:change_key'
                )
            );
        }

        //generate keys, if needed
        Server::generateKeys();

        $progress_indicator?->advance($generate_keys_weight);
        $progress_indicator?->addMessage(MessageType::Success, __('Security keys generated.'));

        if (
            (Config::getConfigurationValue('core', 'plugins_execution_mode') ?? null) === Plugin::EXECUTION_MODE_SUSPENDED_BY_UPDATE
            && VersionParser::getIntermediateVersion($current_version) === VersionParser::getIntermediateVersion(GLPI_VERSION)
        ) {
            // The target version is the same intermediate/major version.
            // Resume plugins execution if it was previously suspended by a GLPI codebase update.
            $progress_indicator?->setProgressBarMessage(__('Resuming plugins execution…'));

            (new Plugin())->resumeAllPluginsExecution();

            Event::log(
                '',
                Plugin::class,
                3,
                "setup",
                __('Execution of all the plugins has been resumed after the database update.')
            );

            $progress_indicator?->addMessage(MessageType::Success, __('Execution of all active plugins has been resumed.'));
        }

        $progress_indicator?->setProgressBarMessage('');
        $progress_indicator?->addMessage(MessageType::Success, __('Update done.'));
        $progress_indicator?->finish();

        return true;
    }

    /**
     * Set migration
     *
     * @param Migration $migration_instance Migration instance
     *
     * @return Update
     */
    public function setMigration(Migration $migration_instance)
    {
        /** @var Migration $migration */
        global $migration; // Migration scripts are using global `$migration`
        $migration = $migration_instance;

        return $this;
    }

    /**
     * Check if expected security key file is missing.
     *
     * @return bool
     */
    public function isExpectedSecurityKeyFileMissing(): bool
    {
        $expected_key_path = $this->getExpectedSecurityKeyFilePath();

        if ($expected_key_path === null) {
            return false;
        }

        return !file_exists($expected_key_path);
    }

    /**
     * Returns expected security key file path.
     * Will return null for GLPI versions that was not yet handling a custom security key.
     *
     * @return string|null
     */
    public function getExpectedSecurityKeyFilePath(): ?string
    {
        $glpikey = new GLPIKey();
        return $glpikey->getExpectedKeyPath($this->getCurrents()['version']);
    }

    /**
     * Get migrations that have to be ran.
     *
     * @param string $current_version
     * @param bool $force_latest
     *
     * @return array
     */
    private function getMigrationsToDo(string $current_version, bool $force_latest = false): array
    {
        $migrations = [];

        $current_version = VersionParser::getNormalizedVersion($current_version);

        $pattern = '/^update_(?<source_version>\d+\.\d+\.(?:\d+|x))_to_(?<target_version>\d+\.\d+\.(?:\d+|x))\.php$/';
        $migration_iterator = new DirectoryIterator($this->migrations_directory);
        foreach ($migration_iterator as $file) {
            $versions_matches = [];
            if ($file->isDir() || $file->isDot() || preg_match($pattern, $file->getFilename(), $versions_matches) !== 1) {
                continue;
            }

            $force_migration = false;
            if ($current_version === '9.2.2' && $versions_matches['target_version'] === '9.2.2') {
                //9.2.2 upgrade script was not run from the release, see https://github.com/glpi-project/glpi/issues/3659
                $force_migration = true;
            } elseif ($force_latest && version_compare($versions_matches['target_version'], $current_version, '=')) {
                $force_migration = true;
            }
            if (version_compare($versions_matches['target_version'], $current_version, '>') || $force_migration) {
                $migrations[$file->getPathname()] = [
                    'file'           => $file->getPathname(),
                    'function'       => preg_replace(
                        '/^update_(\d+)\.(\d+)\.(\d+|x)_to_(\d+)\.(\d+)\.(\d+|x)\.php$/',
                        'update$1$2$3to$4$5$6',
                        $file->getBasename()
                    ),
                    'target_version' => $versions_matches['target_version'],
                ];
            }
        }

        ksort($migrations, SORT_NATURAL);

        return array_values($migrations);
    }

    /**
     * Check if database is up-to-date.
     *
     * @return bool
     */
    public static function isDbUpToDate(): bool
    {
        global $CFG_GLPI;

        if (!array_key_exists('dbversion', $CFG_GLPI)) {
            return false; // Considered as outdated if installed version is unknown.
        }

        $installed_db_version = trim($CFG_GLPI['dbversion']);
        $defined_db_version   = GLPI_SCHEMA_VERSION;

        if (!str_contains($installed_db_version, '@') || !str_contains($defined_db_version, '@')) {
            // Either installed or defined version is not containing schema hash.
            // Hash is removed from both to do a simple version comparison.
            $installed_db_version = preg_replace('/@.+$/', '', $installed_db_version);
            $defined_db_version   = preg_replace('/@.+$/', '', $defined_db_version);
        }

        return $installed_db_version === $defined_db_version;
    }

    /**
     * Check if database update is mandatory.
     *
     * @return bool
     */
    public static function isUpdateMandatory(): bool
    {
        global $CFG_GLPI;

        if (GLPI_SKIP_UPDATES) {
            // If `GLPI_SKIP_UPDATES` is set to `true`, bugfixes update are not mandatory.
            $installed_intermediate_version = VersionParser::getIntermediateVersion($CFG_GLPI['version'] ?? '0.0.0-dev');
            $defined_intermediate_version   = VersionParser::getIntermediateVersion(GLPI_VERSION);
            return $installed_intermediate_version !== $defined_intermediate_version;
        }

        return self::isDbUpToDate() === false;
    }
}
