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

use Glpi\Console\AbstractCommand;
use Glpi\System\Diagnostic\DatabaseSchemaIntegrityChecker;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Glpi\Toolbox\VersionParser;

class CheckSchemaIntegrityCommand extends AbstractCommand
{
    /**
     * Error code returned when empty SQL file is not available / readable.
     *
     * @var integer
     */
    const ERROR_UNABLE_TO_READ_EMPTYSQL = 1;

    /**
     * Error code returned when differences are found.
     *
     * @var integer
     */
    const ERROR_FOUND_DIFFERENCES = 2;

    /**
     * Error code returned when a DB update is necessary to be able to perform the check.
     *
     * @var integer
     */
    const ERROR_REQUIRE_DB_UPDATE = 3;

    protected $requires_db_up_to_date = false;

    protected function configure()
    {
        parent::configure();

        $this->setName('glpi:database:check_schema_integrity');
        $this->setAliases(
            [
                'db:check_schema_integrity',
                'glpi:database:check', // old name
                'db:check', // old alias
            ]
        );
        $this->setDescription(__('Check for schema differences between current database and installation file.'));

        $this->addOption(
            'strict',
            null,
            InputOption::VALUE_NONE,
            __('Strict comparison of definitions')
        );

        $this->addOption(
            'check-all-migrations',
            null,
            InputOption::VALUE_NONE,
            __('Check tokens related to all databases migrations.')
        );

        $this->addOption(
            'check-innodb-migration',
            null,
            InputOption::VALUE_NONE,
            __('Check tokens related to migration from "MyISAM" to "InnoDB".')
        );

        $this->addOption(
            'check-timestamps-migration',
            null,
            InputOption::VALUE_NONE,
            __('Check tokens related to migration from "datetime" to "timestamp".')
        );

        $this->addOption(
            'check-utf8mb4-migration',
            null,
            InputOption::VALUE_NONE,
            __('Check tokens related to migration from "utf8" to "utf8mb4".')
        );

        $this->addOption(
            'check-dynamic-row-format-migration',
            null,
            InputOption::VALUE_NONE,
            __('Check tokens related to "DYNAMIC" row format migration.')
        );

        $this->addOption(
            'check-unsigned-keys-migration',
            null,
            InputOption::VALUE_NONE,
            __('Check tokens related to migration from signed to unsigned integers in primary/foreign keys.')
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        global $CFG_GLPI;

        $installed_version = $CFG_GLPI['dbversion'];
        $current_version   = GLPI_SCHEMA_VERSION;
        // Normalize versions: remove @sha suffix and stability flags
        $install_version_normalized = VersionParser::getNormalizedVersion(preg_replace('/@.+$/', '', $installed_version), false);
        $current_version_normalized = VersionParser::getNormalizedVersion(preg_replace('/@.+$/', '', $current_version), false);

        if (
            $install_version_normalized === $current_version_normalized
            && $installed_version !== $current_version
        ) {
            // Installed version is same as current version, but with a different hash/stability flag.
            // It was probably done from a branch in development or a pre-release.
            // Check will likely find differences, as schema file changed, but these differences would
            // probably be fixed by running update again.
            throw new \Glpi\Console\Exception\EarlyExitException(
                '<error>'
                    . sprintf(
                        __('Cannot check database integrity of intermediate unstable version "%s". Please process to database update and run the command again.'),
                        $installed_version
                    )
                    . '</error>',
                self::ERROR_REQUIRE_DB_UPDATE
            );
        }

        $schema_file = sprintf('%s/install/mysql/glpi-%s-empty.sql', GLPI_ROOT, $install_version_normalized);
        if (!file_exists($schema_file)) {
            throw new \Glpi\Console\Exception\EarlyExitException(
                '<error>' . sprintf(__('Checking database integrity of version "%s" is not supported.'), $installed_version) . '</error>',
                self::ERROR_UNABLE_TO_READ_EMPTYSQL
            );
        }

        $checker = new DatabaseSchemaIntegrityChecker(
            $this->db,
            $input->getOption('strict'),
            !$input->getOption('check-all-migrations') && !$input->getOption('check-innodb-migration'),
            !$input->getOption('check-all-migrations') && !$input->getOption('check-timestamps-migration'),
            !$input->getOption('check-all-migrations') && !$input->getOption('check-utf8mb4-migration'),
            !$input->getOption('check-all-migrations') && !$input->getOption('check-dynamic-row-format-migration'),
            !$input->getOption('check-all-migrations') && !$input->getOption('check-unsigned-keys-migration')
        );

        try {
            $differences = $checker->checkCompleteSchema($schema_file, true);
        } catch (\Throwable $e) {
            $output->writeln(
                '<error>' . $e->getMessage() . '</error>',
                OutputInterface::VERBOSITY_QUIET
            );
            return self::ERROR_UNABLE_TO_READ_EMPTYSQL;
        }

        foreach ($differences as $table_name => $difference) {
            $message = null;
            switch ($difference['type']) {
                case DatabaseSchemaIntegrityChecker::RESULT_TYPE_ALTERED_TABLE:
                    $message = sprintf(__('Table schema differs for table "%s".'), $table_name);
                    break;
                case DatabaseSchemaIntegrityChecker::RESULT_TYPE_MISSING_TABLE:
                    $message = sprintf(__('Table "%s" is missing.'), $table_name);
                    break;
                case DatabaseSchemaIntegrityChecker::RESULT_TYPE_UNKNOWN_TABLE:
                    $message = sprintf(__('Unknown table "%s" has been found in database.'), $table_name);
                    break;
            }
            $output->writeln(
                '<info>' . $message . '</info>',
                OutputInterface::VERBOSITY_QUIET
            );
            $output->write($difference['diff']);
        }

        if (count($differences) > 0) {
            return self::ERROR_FOUND_DIFFERENCES;
        }

        $output->writeln('<info>' . __('Database schema is OK.') . '</info>');

        return 0; // Success
    }
}
