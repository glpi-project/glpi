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

namespace Glpi\Console\Database;

use DBmysql;
use Glpi\Cache\CacheManager;
use Glpi\Console\AbstractCommand;
use Glpi\Console\Command\ConfigurationCommandInterface;
use Glpi\Console\Exception\EarlyExitException;
use Glpi\Console\Traits\TelemetryActivationTrait;
use Glpi\Progress\ConsoleProgressIndicator;
use Glpi\System\Diagnostic\DatabaseSchemaIntegrityChecker;
use Glpi\System\Requirement\DatabaseTablesEngine;
use Glpi\Toolbox\DatabaseSchema;
use Glpi\Toolbox\VersionParser;
use GLPIKey;
use LogicException;
use Migration;
use Override;
use Session;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Update;

use function Safe\preg_match;
use function Safe\preg_replace;
use function Safe\sha1_file;

class UpdateCommand extends AbstractCommand implements ConfigurationCommandInterface
{
    use TelemetryActivationTrait;

    /**
     * Error code returned when trying to update from an unstable version.
     *
     * @var integer
     */
    public const ERROR_NO_UNSTABLE_UPDATE = 1;

    /**
     * Error code returned when security key file is missing.
     *
     * @var integer
     */
    public const ERROR_MISSING_SECURITY_KEY_FILE = 2;

    /**
     * Error code returned when database is not a valid GLPI database.
     *
     * @var integer
     */
    public const ERROR_INVALID_DATABASE = 3;

    /**
     * Error code returned when database integrity check failed.
     *
     * @var integer
     */
    public const ERROR_DATABASE_INTEGRITY_CHECK_FAILED = 4;

    /**
     * Error code returned when an error occurred during the update.
     *
     * @var integer
     */
    public const ERROR_UPDATE_FAILED = 5;

    protected $requires_db_up_to_date = false;

    #[Override]
    public function getSpecificMandatoryRequirements(): array
    {
        $valid_db = $this->db instanceof DBmysql && $this->db->connected;
        return $valid_db ? [new DatabaseTablesEngine($this->db)] : [];
    }

    protected function configure()
    {
        parent::configure();

        $this->setName('database:update');
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
            __('Do not check database schema integrity before and after performing the update')
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
        parent::initialize($input, $output);

        $this->outputWarningOnMissingOptionnalRequirements();

        $this->db->disableTableCaching();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$output instanceof ConsoleOutputInterface) {
            throw new LogicException('This command accepts only an instance of "ConsoleOutputInterface".');
        }

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
        $informations->addRow([
            __('GLPI database version'),
            $this->getPrettyDbVersion($current_db_version),
            $this->getPrettyDbVersion(GLPI_SCHEMA_VERSION),
        ]);
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

        $progress_indicator = new ConsoleProgressIndicator($output);

        $update->setMigration(new Migration(GLPI_VERSION, $progress_indicator));
        try {
            $success = $update->doUpdates(
                current_version: $current_version,
                force_latest: $force,
                progress_indicator: $progress_indicator
            );
            if ($success === false) {
                $output->writeln('<error>' . __('Update failed.') . '</error>', OutputInterface::VERBOSITY_QUIET);
                return self::ERROR_UPDATE_FAILED;
            }
        } catch (Throwable $e) {
            $progress_indicator->fail();

            $message = sprintf(
                __('An error occurred during the database update. The error was: %s'),
                $e->getMessage()
            );
            $output->writeln('<error>' . $message . '</error>', OutputInterface::VERBOSITY_QUIET);
            return self::ERROR_UPDATE_FAILED;
        }

        (new CacheManager())->resetAllCaches(); // Ensure cache will not use obsolete data

        $this->handTelemetryActivation($input, $output);

        if ($this->input->getOption('skip-db-checks')) {
            $this->output->writeln(
                [
                    '<comment>' . __('The database schema integrity check has been skipped.') . '</comment>',
                    '<comment>' . sprintf(
                        __('It is recommended to run the "%s" command to validate that the database schema is consistent with the current GLPI version.'),
                        'php bin/console database:check_schema_integrity'
                    ) . '</comment>',
                ],
                OutputInterface::VERBOSITY_QUIET
            );
        } elseif (!$update->isUpdatedSchemaConsistent()) {
            // Exit with an error if database schema is not consistent.
            // Keep this code at end of command to ensure that the whole migration is still executed.
            // Many old GLPI instances will likely have differences, and people will have to fix them manually.
            //
            // Exiting with an exit code will permit to warn non-interactive automated scripts about the failure.
            // Administrators will likely receive a failure notification and will be able to do manual actions in order to
            // fix schema integrity.
            $this->output->writeln(
                [
                    '<error>' . __('The database schema is not consistent with the current GLPI version.') . '</error>',
                    '<error>' . sprintf(
                        __('It is recommended to run the "%s" command to see the differences.'),
                        'php bin/console database:check_schema_integrity'
                    ) . '</error>',
                ],
                OutputInterface::VERBOSITY_QUIET
            );
            return self::ERROR_DATABASE_INTEGRITY_CHECK_FAILED;
        }

        return 0; // Success
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

        $checker = new DatabaseSchemaIntegrityChecker($this->db, false, true, true, true, true, true);

        if (!$checker->canCheckIntegrity($installed_version)) {
            $msg = sprintf(
                __('Database schema integrity check skipped as version "%s" is not supported by checking process.'),
                $installed_version
            );
            $this->output->writeln('<comment>' . $msg . '</comment>', OutputInterface::VERBOSITY_QUIET);
            return;
        }

        $error = null;
        try {
            $differences = $checker->checkCompleteSchemaForVersion($installed_version, true);
            if (count($differences) > 0) {
                $install_version_nohash = preg_replace('/@.+$/', '', $installed_version);
                $error = sprintf(__('The database schema is not consistent with the installed GLPI version (%s).'), $install_version_nohash)
                    . ' '
                    . sprintf(__('Run the "%1$s" command to view found differences.'), 'php bin/console database:check_schema_integrity');
            }
        } catch (Throwable $e) {
            $error = sprintf(__('Database integrity check failed with error (%s).'), $e->getMessage());
        }

        if ($error !== null) {
            if (!$this->input->getOption('no-interaction')) {
                // On interactive mode, display error only.
                // User will be asked for confirmation before update execution.
                $this->output->writeln('<error>' . $error . '</error>', OutputInterface::VERBOSITY_QUIET);
            } else {
                // On non-interactive mode, exit with error.
                throw new EarlyExitException(
                    '<error>' . $error . '</error>',
                    self::ERROR_DATABASE_INTEGRITY_CHECK_FAILED
                );
            }
        } else {
            $this->output->writeln('<info>' . __('Database schema is OK.') . '</info>');
        }
    }

    /**
     * Get DB version to display.
     *
     * @param string $raw_version
     *
     * @return string
     */
    private function getPrettyDbVersion(string $raw_version): string
    {
        $version_matches = [];
        if (preg_match('/^(?<version>.+)@(?<hash>.+)$/', $raw_version, $version_matches) !== 1) {
            // Version does not match expected pattern. It either contains no hash, either has an unexpected format.
            // Preserve raw version string for debug purpose.
            return $raw_version;
        }

        $version_cleaned = $version_matches['version'];
        $version_hash    = $version_matches['hash'];

        if (!VersionParser::isStableRelease($version_cleaned)) {
            // Not a stable version. Keep hash for debug purpose.
            return $raw_version;
        }

        $schema_path = DatabaseSchema::getEmptySchemaPath($version_cleaned);
        if ($schema_path === null || $version_hash !== sha1_file($schema_path)) {
            // Version hash does not match schema file sha1. Installation was probably made from a specific commit
            // or a nightly build.
            // Keep hash for debug purpose.
            return $raw_version;
        }

        return $version_cleaned;
    }

    public function getConfigurationFilesToUpdate(InputInterface $input): array
    {
        if (!(new GLPIKey())->keyExists()) {
            return ['glpicrypt.key'];
        }

        return [];
    }
}
