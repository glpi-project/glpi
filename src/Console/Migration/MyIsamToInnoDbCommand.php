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

namespace Glpi\Console\Migration;

use DBConnection;
use DBmysql;
use Glpi\Console\AbstractCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MyIsamToInnoDbCommand extends AbstractCommand
{
    /**
     * Error code returned when failed to migrate one table.
     *
     * @var integer
     */
    const ERROR_TABLE_MIGRATION_FAILED = 1;

    /**
     * Error code returned if DB configuration file cannot be updated.
     *
     * @var integer
     */
    const ERROR_UNABLE_TO_UPDATE_CONFIG = 2;

    protected function configure()
    {
        parent::configure();

        $this->setName('glpi:migration:myisam_to_innodb');
        $this->setDescription(__('Migrate MyISAM tables to InnoDB'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $myisam_tables = $this->db->getMyIsamTables();

        $output->writeln(
            sprintf(
                '<info>' . __('Found %s table(s) using MyISAM engine.') . '</info>',
                $myisam_tables->count()
            )
        );

        $errors = false;

        if (0 === $myisam_tables->count()) {
            $output->writeln('<info>' . __('No migration needed.') . '</info>');
        } else {
            $this->askForConfirmation();

            $progress_bar = new ProgressBar($output);

            foreach ($progress_bar->iterate($myisam_tables) as $table) {
                $table_name = DBmysql::quoteName($table['TABLE_NAME']);
                $this->writelnOutputWithProgressBar(
                    '<comment>' . sprintf(__('Migrating table "%s"...'), $table_name) . '</comment>',
                    $progress_bar,
                    OutputInterface::VERBOSITY_VERBOSE
                );
                $result = $this->db->query(sprintf('ALTER TABLE %s ENGINE = InnoDB', $table_name));

                if (false === $result) {
                    $message = sprintf(
                        __('Migration of table "%s"  failed with message "(%s) %s".'),
                        $table_name,
                        $this->db->errno(),
                        $this->db->error()
                    );
                    $this->writelnOutputWithProgressBar(
                        '<error>' . $message . '</error>',
                        $progress_bar,
                        OutputInterface::VERBOSITY_QUIET
                    );
                    $errors = true;
                }
            }

            $this->output->write(PHP_EOL);
        }

        if (!DBConnection::updateConfigProperty(DBConnection::PROPERTY_ALLOW_MYISAM, false)) {
            throw new \Glpi\Console\Exception\EarlyExitException(
                '<error>' . __('Unable to update DB configuration file.') . '</error>',
                self::ERROR_UNABLE_TO_UPDATE_CONFIG
            );
        }

        if ($errors) {
            throw new \Glpi\Console\Exception\EarlyExitException(
                '<error>' . __('Errors occurred during migration.') . '</error>',
                self::ERROR_TABLE_MIGRATION_FAILED
            );
        }

        if ($myisam_tables->count() > 0) {
            $output->writeln('<info>' . __('Migration done.') . '</info>');
        }

        return 0; // Success
    }
}
