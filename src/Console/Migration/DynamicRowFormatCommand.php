<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

use Glpi\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DynamicRowFormatCommand extends AbstractCommand
{
    /**
     * Error code returned if migration failed on, at least, one table.
     *
     * @var integer
     */
    const ERROR_MIGRATION_FAILED_FOR_SOME_TABLES = 1;

    /**
     * Error code returned if some tables are still using MyISAM engine.
     *
     * @var integer
     */
    const ERROR_INNODB_REQUIRED = 2;

    protected $requires_db_up_to_date = false;

    protected function configure()
    {
        parent::configure();

        $this->setName('migration:dynamic_row_format');
        $this->setDescription(__('Convert database tables to "Dynamic" row format (required for "utf8mb4" character support).'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkForPrerequisites();
        $this->upgradeRowFormat();

        return 0; // Success
    }

    /**
     * Check for migration prerequisites.
     *
     * @return void
     */
    private function checkForPrerequisites(): void
    {

       // Check that all tables are using InnoDB engine
        if (($myisam_count = $this->db->getMyIsamTables()->count()) > 0) {
            $msg = sprintf(__('%d tables are using the deprecated MyISAM storage engine.'), $myisam_count)
            . ' '
            . sprintf(__('Run the "%1$s" command to migrate them.'), 'php bin/console migration:myisam_to_innodb');
            throw new \Glpi\Console\Exception\EarlyExitException('<error>' . $msg . '</error>', self::ERROR_INNODB_REQUIRED);
        }
    }

    /**
     * Upgrade row format from 'Compact'/'Redundant' to 'Dynamic'.
     * This is mandatory to support large indexes.
     *
     * @return void
     */
    private function upgradeRowFormat(): void
    {

        $table_iterator = $this->db->listTables(
            'glpi\_%',
            [
                'row_format'   => ['COMPACT', 'REDUNDANT'],
            ]
        );

        if (0 === $table_iterator->count()) {
            $this->output->writeln('<info>' . __('No migration needed.') . '</info>');
            return;
        }

        $this->output->writeln(
            sprintf(
                '<info>' . __('Found %s table(s) requiring a migration to "ROW_FORMAT=DYNAMIC".') . '</info>',
                $table_iterator->count()
            )
        );

        $this->warnAboutExecutionTime();
        $this->askForConfirmation();

        $tables = [];
        foreach ($table_iterator as $table_data) {
            $tables[] = $table_data['TABLE_NAME'];
        }
        sort($tables);

        $errors = false;

        $progress_message = function (string $table) {
            return sprintf(__('Migrating table "%s"...'), $table);
        };

        foreach ($this->iterate($tables, $progress_message) as $table) {
            $result = $this->db->query(sprintf('ALTER TABLE %s ROW_FORMAT = DYNAMIC', $this->db->quoteName($table)));

            if (!$result) {
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

        if ($errors) {
            throw new \Glpi\Console\Exception\EarlyExitException(
                '<error>' . __('Errors occurred during migration.') . '</error>',
                self::ERROR_MIGRATION_FAILED_FOR_SOME_TABLES
            );
        }

        $this->output->writeln('<info>' . __('Migration done.') . '</info>');
    }
}
