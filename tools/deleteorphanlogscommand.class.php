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

use Glpi\Console\AbstractCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteOrphanLogsCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('tools:delete_orphan_logs');
        $this->setDescription(__('Delete orphan logs'));

        $this->addOption(
            'dry-run',
            '',
            InputOption::VALUE_NONE,
            __('Simulate the command without actually delete anything')
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if (!$input->getOption('no-interaction') && !$input->getOption('dry-run')) {
           // Ask for confirmation (unless --no-interaction)
            $output->writeln(__('You are about to delete orphan logs of GLPI log table (glpi_logs).'));

            $this->askForConfirmation(false);
        }

        $globalCount = 0;
        $listTable = $this->db->listTables();
        $dry_run = $input->getOption('dry-run');

        foreach ($listTable as $table) {
            $tablename = $table['TABLE_NAME'];

            $itemtype = getItemTypeForTable($tablename);
            $output->writeln(
                '<comment>' . sprintf(__('Searching for orphaned "%s"...'), $itemtype) . '</comment>',
                OutputInterface::VERBOSITY_VERBOSE
            );

            $result = $this->db->request(
                [
                    'SELECT' => ['id', 'items_id'],
                    'FROM' => 'glpi_logs',
                    'WHERE' => [
                        ['NOT' => ['items_id' => new QuerySubQuery(['SELECT' => 'id', 'FROM' => $tablename])]],
                        'itemtype' => $itemtype,
                    ],
                ]
            );

            $globalCount += $result->numrows();

            if ($result->numrows() > 0) {
                $msg = $dry_run ? __('Found %s orphaned "%s" record(s).') : __('Deleting %s orphaned "%s" record(s)...');
                $output->writeln('<info>' . sprintf($msg, $result->numrows(), $itemtype) . '</info>');

                if (!$dry_run) {
                    $progress_bar = new ProgressBar($output);

                    $i = $total = 0;
                    foreach ($result as $row) {
                        $ids[] = $row['id'];
                        $i++;
                        $total++;
                        if ($i % 1000 == 0 || count($result) === $total) {
                            $this->db->delete('glpi_logs', ['id' => $ids]);
                            $progress_bar->advance($i);
                         //reset
                            $ids = [];
                            $i = 0;
                        }
                    }

                    $progress_bar->finish();
                    $this->output->write(PHP_EOL);
                }
            }
        }

        if (!$globalCount) {
            $output->writeln(
                '<info>' . __('No orphans found in the glpi_logs table.') . '</info>',
                OutputInterface::VERBOSITY_QUIET
            );
        } else if (!$dry_run) {
            $output->writeln(
                '<info>' . __('Deletion done.') . '</info>',
                OutputInterface::VERBOSITY_QUIET
            );
        } else {
            $msg = sprintf(
                __('Found %s orphan(s) in the glpi_logs table. Launch the command without the --dry-run option to delete them.'),
                $globalCount
            );
            $output->writeln(
                '<info>' . $msg . '</info>',
                OutputInterface::VERBOSITY_QUIET
            );
        }

        return 0; // Success
    }
}
