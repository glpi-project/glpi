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

namespace Glpi\Console\Migration;

use Glpi\Console\AbstractCommand;
use Glpi\Console\Command\ConfigurationCommandInterface;
use Glpi\Console\Exception\EarlyExitException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MyIsamToInnoDbCommand extends AbstractCommand implements ConfigurationCommandInterface
{
    protected $requires_db_up_to_date = false;

    /**
     * Error code returned when failed to migrate one table.
     *
     * @var integer
     */
    public const ERROR_TABLE_MIGRATION_FAILED = 1;

    /**
     * Error code returned if DB configuration file cannot be updated.
     *
     * @var integer
     */
    public const ERROR_UNABLE_TO_UPDATE_CONFIG = 2;

    protected function configure()
    {
        parent::configure();

        $this->setName('migration:myisam_to_innodb');
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
            $this->warnAboutExecutionTime();
            $this->askForConfirmation();

            $tables = [];
            foreach ($myisam_tables as $table_data) {
                $tables[] = $table_data['TABLE_NAME'];
            }
            sort($tables);

            $progress_message = (fn(string $table) => sprintf(__('Migrating table "%s"...'), $table));

            foreach ($this->iterate($tables, $progress_message) as $table) {
                $result = $this->db->doQuery(sprintf('ALTER TABLE %s ENGINE = InnoDB', $this->db->quoteName($table)));

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

        if ($errors) {
            throw new EarlyExitException(
                '<error>' . __('Errors occurred during migration.') . '</error>',
                self::ERROR_TABLE_MIGRATION_FAILED
            );
        }

        if ($myisam_tables->count() > 0) {
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
