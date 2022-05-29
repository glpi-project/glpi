<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

use Glpi\System\Diagnostic\DatabaseSchemaIntegrityChecker;
use Glpi\Toolbox\VersionParser;

/**
 *  Update class
 **/
class Update
{
    private $args = [];
    private $DB;
    /**
     * @var Migration
     */
    private $migration;
    private $version;
    private $dbversion;
    private $language;

    /**
     * Constructor
     *
     * @param object $DB   Database instance
     * @param array  $args Command line arguments; default to empty array
     */
    public function __construct($DB, $args = [])
    {
        $this->DB = $DB;
        $this->args = $args;
    }

    /**
     * Initialize session for update
     *
     * @return void
     */
    public function initSession()
    {
        if (is_writable(GLPI_SESSION_DIR)) {
            Session::setPath();
        } else {
            if (isCommandLine()) {
                die("Can't write in " . GLPI_SESSION_DIR . "\n");
            }
        }
        Session::start();

        if (isCommandLine()) {
           // Init debug variable
            $_SESSION = ['glpilanguage' => (isset($this->args['lang']) ? $this->args['lang'] : 'en_GB')];
            $_SESSION["glpi_currenttime"] = date("Y-m-d H:i:s");
        }

       // Init debug variable
       // Only show errors
        Toolbox::setDebugMode(Session::DEBUG_MODE, 0, 0, 1);
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
        } else if (!$DB->tableExists("glpi_configs")) {
            // >= 0.31 and < 0.78
            // Get current version
            $result = $DB->request([
                'SELECT' => ['version', 'language'],
                'FROM'   => 'glpi_config'
            ])->current();

            $currents['version']    = trim($result['version']);
            $currents['dbversion']  = $currents['version'];
            $currents['language']   = trim($result['language']);
        } else if ($DB->fieldExists('glpi_configs', 'version')) {
            // < 0.85
            // Get current version and language
            $result = $DB->request([
                'SELECT' => ['version', 'language'],
                'FROM'   => 'glpi_configs'
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
        $this->dbversion  = $currents['dbversion'];
        $this->language   = $currents['language'];

        return $currents;
    }

    /**
     * Verify the database schema integrity.
     *
     * @return void
     */
    private function checkSchemaIntegrity(): void
    {
        global $DB;

        $normalized_nersion = VersionParser::getNormalizedVersion(GLPI_VERSION, false);

        $checker = new DatabaseSchemaIntegrityChecker($DB, false, true, true, true, true, true);
        $differences = $checker->checkCompleteSchema(
            sprintf('%s/install/mysql/glpi-%s-empty.sql', GLPI_ROOT, $normalized_nersion),
            true
        );

        if (count($differences) > 0) {
            $this->migration->displayError(
                __('The database schema is not consistent with the current GLPI version.')
                . "\n"
                . __('It is recommended to run the "php bin/console glpi:database:check_schema_integrity" command to see the differences.')
            );
        }
    }

    /**
     * Run updates
     *
     * @param string $current_version  Current version
     * @param bool   $force_latest     Force replay of latest migration
     *
     * @return void
     */
    public function doUpdates($current_version = null, bool $force_latest = false)
    {
        if ($current_version === null) {
            if ($this->version === null) {
                throw new \RuntimeException('Cannot process updates without any version specified!');
            }
            $current_version = $this->version;
        }

        if (version_compare($current_version, '0.80', 'lt')) {
            die('Upgrade is not supported before 0.80!');
            die(1);
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
            $sql_mode = $DB->query(sprintf('SELECT @@sql_mode as %s', $DB->quoteName('sql_mode')))->fetch_assoc()['sql_mode'] ?? '';
            $sql_mode_flags = array_filter(
                explode(',', $sql_mode),
                function (string $flag) {
                    return !in_array(
                        trim($flag),
                        [
                            'STRICT_ALL_TABLES',
                            'STRICT_TRANS_TABLES',
                            'NO_ZERO_IN_DATE',
                            'NO_ZERO_DATE',
                            'ERROR_FOR_DIVISION_BY_ZERO',
                        ]
                    );
                }
            );
            $DB->query(sprintf('SET SESSION sql_mode = %s', $DB->quote(implode(',', $sql_mode_flags))));
        }

       // To prevent problem of execution time
        ini_set("max_execution_time", "0");

       // Update process desactivate all plugins
        $plugin = new Plugin();
        $plugin->unactivateAll();

        if (version_compare($current_version, '0.80', '<') || version_compare($current_version, GLPI_VERSION, '>')) {
            $message = sprintf(
                __('Unsupported version (%1$s)'),
                $current_version
            );
            if (isCommandLine()) {
                echo "$message\n";
                die(1);
            } else {
                $this->migration->displayWarning($message, true);
                die(1);
            }
        }

        $migrations = $this->getMigrationsToDo($current_version, $force_latest);
        foreach ($migrations as $file => $function) {
            include_once($file);
            $function();
        }

        if (($myisam_count = $DB->getMyIsamTables()->count()) > 0) {
            $message = sprintf(__('%d tables are using the deprecated MyISAM storage engine.'), $myisam_count)
                . ' '
                . sprintf(__('Run the "php bin/console %1$s" command to migrate them.'), 'glpi:migration:myisam_to_innodb');
            $this->migration->displayError($message);
        }
        if (($datetime_count = $DB->getTzIncompatibleTables()->count()) > 0) {
            $message = sprintf(__('%1$s columns are using the deprecated datetime storage field type.'), $datetime_count)
                . ' '
                . sprintf(__('Run the "php bin/console %1$s" command to migrate them.'), 'glpi:migration:timestamps');
            $this->migration->displayError($message);
        }
        /*
         * FIXME: Remove `$DB->use_utf8mb4` and `$exclude_plugins = true` conditions in GLPI 10.1.
         * These conditions are here only to prevent having this message on every migration to GLPI 10.0.x.
         * Indeed, as migration command was not available in previous versions, users may not understand
         * why this is considered as an error.
         * Also, some plugins may not have yet handle the switch to utf8mb4.
         */
        if ($DB->use_utf8mb4 && ($non_utf8mb4_count = $DB->getNonUtf8mb4Tables(true)->count()) > 0) {
            $message = sprintf(__('%1$s tables are using the deprecated utf8mb3 storage charset.'), $non_utf8mb4_count)
                . ' '
                . sprintf(__('Run the "php bin/console %1$s" command to migrate them.'), 'glpi:migration:utf8mb4');
            $this->migration->displayError($message);
        }
        /*
         * FIXME: Remove `!$DB->allow_signed_keys` and `$exclude_plugins = true` conditions in GLPI 10.1.
         * These conditions are here only to prevent having this message on every migration to GLPI 10.0.x.
         * Indeed, as migration command was not available in previous versions, users may not understand
         * why this is considered as an error.
         * Also, some plugins may not have yet handle the switch to unsigned keys.
         */
        if (!$DB->allow_signed_keys && ($signed_keys_col_count = $DB->getSignedKeysColumns(true)->count()) > 0) {
            $message = sprintf(__('%d primary or foreign keys columns are using signed integers.'), $signed_keys_col_count)
                . ' '
                . sprintf(__('Run the "php bin/console %1$s" command to migrate them.'), 'glpi:migration:unsigned_keys');
            $this->migration->displayError($message);
        }

        // Update version number and default langage and new version_founded ---- LEAVE AT THE END
        Config::setConfigurationValues('core', ['version'             => GLPI_VERSION,
            'dbversion'           => GLPI_SCHEMA_VERSION,
            'language'            => $this->language,
            'founded_new_version' => ''
        ]);

        if (defined('GLPI_SYSTEM_CRON')) {
           // Downstream packages may provide a good system cron
            $DB->updateOrDie(
                'glpi_crontasks',
                [
                    'mode'   => 2
                ],
                [
                    'name'      => ['!=', 'watcher'],
                    'allowmode' => ['&', 2]
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

       //generate security key if missing, and update db
        $glpikey = new GLPIKey();
        if (!$glpikey->keyExists() && !$glpikey->generate()) {
            $this->migration->displayWarning(__('Unable to create security key file! You have to run "php bin/console glpi:security:change_key" command to manually create this file.'), true);
        }

        // Check if schema has differences from the expected one but do not block the upgrade
        $this->checkSchemaIntegrity();
    }

    /**
     * Set migration
     *
     * @param Migration $migration Migration instance
     *
     * @return Update
     */
    public function setMigration(Migration $migration)
    {
        $this->migration = $migration;
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
        $migration_iterator = new DirectoryIterator(GLPI_ROOT . '/install/migrations/');
        foreach ($migration_iterator as $file) {
            $versions_matches = [];
            if ($file->isDir() || $file->isDot() || preg_match($pattern, $file->getFilename(), $versions_matches) !== 1) {
                continue;
            }

            $force_migration = false;
            if ($current_version === '9.2.2' && $versions_matches['target_version'] === '9.2.2') {
               //9.2.2 upgrade script was not run from the release, see https://github.com/glpi-project/glpi/issues/3659
                $force_migration = true;
            } else if ($force_latest && version_compare($versions_matches['target_version'], $current_version, '=')) {
                $force_migration = true;
            }
            if (version_compare($versions_matches['target_version'], $current_version, '>') || $force_migration) {
                $migrations[$file->getRealPath()] = preg_replace(
                    '/^update_(\d+)\.(\d+)\.(\d+|x)_to_(\d+)\.(\d+)\.(\d+|x)\.php$/',
                    'update$1$2$3to$4$5$6',
                    $file->getBasename()
                );
            }
        }

        ksort($migrations, SORT_NATURAL);

        return $migrations;
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

        $installed_version = trim($CFG_GLPI['dbversion']);
        $defined_version   = GLPI_SCHEMA_VERSION;

        if (!str_contains($installed_version, '@') || !str_contains($defined_version, '@')) {
            // Either installed or defined version is not containing schema hash.
            // Hash is removed from both to do a simple version comparison.
            $installed_version = preg_replace('/@.+$/', '', $installed_version);
            $defined_version   = preg_replace('/@.+$/', '', $defined_version);
        }

        return $installed_version === $defined_version;
    }
}
