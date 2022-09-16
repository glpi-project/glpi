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

namespace Glpi\Console\Database;

use Glpi\Cache\CacheManager;
use Glpi\Console\AbstractCommand;
use Glpi\Console\Command\ForceNoPluginsOptionCommandInterface;
use Glpi\Console\Traits\TelemetryActivationTrait;
use Glpi\System\Diagnostic\DatabaseSchemaIntegrityChecker;
use Glpi\Toolbox\VersionParser;
use Migration;
use Session;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Update;

class UpdateCommand extends AbstractCommand implements ForceNoPluginsOptionCommandInterface
{
    use TelemetryActivationTrait;

    /**
     * Error code returned when trying to update from an unstable version.
     *
     * @var integer
     */
    const ERROR_NO_UNSTABLE_UPDATE = 1;

    /**
     * Error code returned when security key file is missing.
     *
     * @var integer
     */
    const ERROR_MISSING_SECURITY_KEY_FILE = 2;

    /**
     * Error code returned when database is not a valid GLPI database.
     *
     * @var integer
     */
    const ERROR_INVALID_DATABASE = 3;

    /**
     * Error code returned when database integrity check failed.
     *
     * @var integer
     */
    const ERROR_DATABASE_INTEGRITY_CHECK_FAILED = 4;

    protected $requires_db_up_to_date = false;

    protected function configure()
    {
        parent::configure();

        $this->setName('glpi:database:update');
        $this->setAliases(['db:update']);
        $this->setDescription(__('Update database schema to new version'));

        $this->addOption(
            'allow-unstable',
            'u',
            InputOption::VALUE_NONE,
            __('Allow update to an unstable version')
        );

        $this->addOption(
            '--skip-db-checks',
            's',
            InputOption::VALUE_NONE,
            __('Do not check database schema integrity before performing the update')
        );

        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            __('Force execution of update from v-1 version of GLPI even if schema did not changed')
        );

        $this->registerTelemetryActivationOptions($this->getDefinition());
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {

        global $GLPI_CACHE;
        $GLPI_CACHE = (new CacheManager())->getInstallerCacheInstance(); // Use dedicated "installer" cache

        parent::initialize($input, $output);

        $this->outputWarningOnMissingOptionnalRequirements();

        $this->db->disableTableCaching();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $allow_unstable = $input->getOption('allow-unstable');
        $force          = $input->getOption('force');
        $no_interaction = $input->getOption('no-interaction'); // Base symfony/console option

        $update = new Update($this->db);

       // Initialize entities
        $_SESSION['glpidefault_entity'] = 0;
        Session::initEntityProfiles(2);
        Session::changeProfile(4);

       // Display current/future state information
        $currents            = $update->getCurrents();
        $current_version     = $currents['version'];
        $current_db_version  = $currents['dbversion'];

        if ($current_version === null) {
            $msg = sprintf(
                __('Current GLPI version not found for database named "%s". Update cannot be done.'),
                $this->db->dbdefault
            );
            $output->writeln('<error>' . $msg . '</error>');
            return self::ERROR_INVALID_DATABASE;
        }

        $informations = new Table($output);
        $informations->setHeaders(['', __('Current'), _n('Target', 'Targets', 1)]);
        $informations->addRow([__('Database host'), $this->db->dbhost, '']);
        $informations->addRow([__('Database name'), $this->db->dbdefault, '']);
        $informations->addRow([__('Database user'), $this->db->dbuser, '']);
        $informations->addRow([__('GLPI version'), $current_version, GLPI_VERSION]);
        $informations->addRow([__('GLPI database version'), $current_db_version, GLPI_SCHEMA_VERSION]);
        $informations->render();

        if (Update::isDbUpToDate() && !$force) {
            $output->writeln('<info>' . __('No migration needed.') . '</info>');
            return 0;
        }

        if (VersionParser::isStableRelease($current_version) && !VersionParser::isStableRelease(GLPI_VERSION) && !$allow_unstable) {
           // Prevent unstable update unless explicitly asked
            $output->writeln(
                sprintf(
                    '<error>' . __('%s is not a stable release. Please upgrade manually or add --allow-unstable option.') . '</error>',
                    GLPI_VERSION
                ),
                OutputInterface::VERBOSITY_QUIET
            );
            return self::ERROR_NO_UNSTABLE_UPDATE;
        }

        if ($update->isExpectedSecurityKeyFileMissing()) {
            $output->writeln(
                sprintf(
                    '<error>' . __('The key file "%s" used to encrypt/decrypt sensitive data is missing. You should retrieve it from your previous installation or encrypted data will be unreadable.') . '</error>',
                    $update->getExpectedSecurityKeyFilePath()
                ),
                OutputInterface::VERBOSITY_QUIET
            );

            if ($no_interaction) {
                return self::ERROR_MISSING_SECURITY_KEY_FILE;
            }
        }

        $this->checkSchemaIntegrity($current_db_version);

        $this->askForConfirmation();

        global $migration; // Migration scripts are using global `$migration`
        $migration = new Migration(GLPI_VERSION);
        $migration->setOutputHandler($output);
        $update->setMigration($migration);
        $update->doUpdates($current_version, $force);
        $output->writeln('<info>' . __('Migration done.') . '</info>');

        (new CacheManager())->resetAllCaches(); // Ensure cache will not use obsolete data

        $this->handTelemetryActivation($input, $output);

        return 0; // Success
    }

    public function getNoPluginsOptionValue()
    {

        return true;
    }

    /**
     * Check schema integrity of installed database.
     *
     * @param string $installed_version
     *
     * @return void
     */
    private function checkSchemaIntegrity(string $installed_version): void
    {
        if ($this->input->getOption('skip-db-checks')) {
            return;
        }

        $this->output->writeln('<comment>' . __('Checking database schema integrity...') . '</comment>');

        $current_version   = GLPI_SCHEMA_VERSION;
        $install_version_nohash = preg_replace('/@.+$/', '', $installed_version);
        $current_version_nohash = preg_replace('/@.+$/', '', $current_version);

        if (
            // source version is unstable version (e.g. upgrade from 10.0.3-dev)
            !VersionParser::isStableRelease($install_version_nohash)
            // or source and target versions are the same, but with a different schema hash
            // (e.g. upgrade from 10.0.2@xxx to 10.0.2@yyy)
            || (
                $install_version_nohash === $current_version_nohash
                && $installed_version !== $current_version
            )
        ) {
            $msg = sprintf(
                __('Database schema integrity check skipped as database was installed using an intermediate unstable version (%s).'),
                $installed_version
            );
            $this->output->writeln('<comment>' . $msg . '</comment>', OutputInterface::VERBOSITY_QUIET);
            return;
        }

        $schema_file = sprintf(
            '%s/install/mysql/glpi-%s-empty.sql',
            GLPI_ROOT,
            VersionParser::getNormalizedVersion($install_version_nohash, false)
        );
        if (!file_exists($schema_file)) {
            $msg = sprintf(
                __('Database schema integrity check skipped as version "%s" is not supported by checking process.'),
                $installed_version
            );
            $this->output->writeln('<comment>' . $msg . '</comment>', OutputInterface::VERBOSITY_QUIET);
            return;
        }

        $checker = new DatabaseSchemaIntegrityChecker($this->db, false, true, true, true, true, true);
        $error = null;
        try {
            $differences = $checker->checkCompleteSchema($schema_file, true);
        } catch (\Throwable $e) {
            $error = sprintf(__('Database integrity check failed with error (%s).'), $e->getMessage());
        }
        if (count($differences) > 0) {
            $error = sprintf(__('The database schema is not consistent with the installed GLPI version (%s).'), $install_version_nohash)
                . ' '
                . sprintf(__('Run the "php bin/console %1$s" command to view found differences.'), 'glpi:database:check_schema_integrity');
        }

        if ($error !== null) {
            if (!$this->input->getOption('no-interaction')) {
                // On interactive mode, display error only.
                // User will be asked for confirmation before update execution.
                $this->output->writeln('<error>' . $error . '</error>', OutputInterface::VERBOSITY_QUIET);
            } else {
                // On non-interactive mode, exit with error.
                throw new \Glpi\Console\Exception\EarlyExitException(
                    '<error>' . $error . '</error>',
                    self::ERROR_DATABASE_INTEGRITY_CHECK_FAILED
                );
            }
        } else {
            $this->output->writeln('<info>' . __('Database schema is OK.') . '</info>');
        }
    }
}
