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
use Log;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildMissingTimestampsCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('migration:build_missing_timestamps');
        $this->setDescription(__('Set missing `date_creation` and `date_mod` values using log entries.'));
        $this->setHidden(true); // Hide this command as it is when migrating from really old GLPI version
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $tables_iterator = $this->db->request(
            [
                'SELECT' => [
                    'table_name AS TABLE_NAME',
                    'column_name AS COLUMN_NAME',
                ],
                'FROM'   => 'information_schema.columns',
                'WHERE'  => [
                    'table_schema' => $this->db->dbdefault,
                    'table_name'   => ['LIKE', 'glpi\_%'],
                    'column_name'  => ['date_creation', 'date_mod'],
                ],
                'ORDER'  => ['table_name', 'column_name'],
            ]
        );

        $log_table = Log::getTable();

        foreach ($tables_iterator as $table_info) {
            $table    = $table_info['TABLE_NAME'];
            $item     = getItemForTable($table);
            $itemtype = $item::class;
            $column   = $table_info['COLUMN_NAME'];

            if ($item === null) {
                continue;
            }

            if (!$item->dohistory) {
                continue; // Skip items that does not have an history
            }

            $output->writeln(
                '<comment>' . sprintf(__('Filling `%s`.`%s`...'), $table, $column) . '</comment>',
                OutputInterface::VERBOSITY_VERBOSE
            );

            $target_date = $column === 'date_creation' ? 'MIN(`date_mod`)' : 'MAX(`date_mod`)';

            $result = $this->db->doQuery(
                "
            UPDATE `$table`
            LEFT JOIN (
               SELECT $target_date AS `date_mod`, `itemtype`, `items_id`
               FROM  `$log_table`
               GROUP BY `itemtype`, `items_id`
            ) as `logs`
            ON `logs`.`itemtype` = '$itemtype' AND `logs`.`items_id` = `$table`.`id`
            SET  `$table`.`$column` = `logs`.`date_mod` WHERE `$table`.`$column` IS NULL
            "
            );
            if (false === $result) {
                $message = sprintf(
                    __('Update of `%s`.`%s` failed with message "(%s) %s".'),
                    $table,
                    $column,
                    $this->db->errno(),
                    $this->db->error()
                );
                $output->writeln(
                    '<error>' . $message . '</error>',
                    OutputInterface::VERBOSITY_QUIET
                );
            }
        }

        $output->writeln('<info>' . __('Migration done.') . '</info>');

        return 0; // Success
    }
}
