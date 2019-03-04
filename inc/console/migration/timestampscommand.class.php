<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\Console\Migration;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use CommonDBTM;
use Glpi\Console\AbstractCommand;
use Log;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TimestampsCommand extends AbstractCommand {

   protected function configure() {
      parent::configure();

      $this->setName('glpi:migration:timestamps');
      $this->setDescription(__('Convert DATETIME to timestamps to use timezones.'));
   }

   protected function execute(InputInterface $input, OutputInterface $output) {
      //convert db
      $now = date('Y-m-d H:i:s');

      // we are going to update datetime, date and time (?) types to timestamp type
      $tbl_iterator = $this->db->request([
         'SELECT'       => ['INFORMATION_SCHEMA.COLUMNS.TABLE_NAME'],
         'DISTINCT'     => true,
         'FROM'         => 'INFORMATION_SCHEMA.COLUMNS',
         'INNER JOIN'   => [
            'INFORMATION_SCHEMA.TABLES' => [
               'ON' => [
                  'INFORMATION_SCHEMA.TABLES.TABLE_NAME',
                  'INFORMATION_SCHEMA.COLUMNS.TABLE_NAME', [
                     'AND' => ['INFORMATION_SCHEMA.TABLES.TABLE_TYPE' => 'BASE TABLE']
                  ]
               ]
            ]
         ],
         'WHERE'       => [
            'INFORMATION_SCHEMA.COLUMNS.TABLE_SCHEMA' => $this->db->dbdefault,
            'INFORMATION_SCHEMA.COLUMNS.COLUMN_TYPE'  => 'DATETIME'
         ]
      ]);

      while ($table = $tbl_iterator->next()) {
         $tablealter = ''; // init by default

         // get accurate info from information_schema to perform correct alter
         $col_iterator = $this->db->request([
            'FROM'   => 'INFORMATION_SCHEMA.COLUMNS',
            'WHERE'  => [
               'TABLE_SCHEMA' => $this->db->dbdefault,
               'TABLE_NAME'   => $table['TABLE_NAME'],
               'COLUMN_TYPE'  => 'DATETIME'
            ]
         ]);

         while ($column = $col_iterator->next()) {
            $nullable = false;
            $default = null;
            //check if nullable
            if ('YES' === $column['IS_NULLABLE']) {
               $nullable = true;
            }

            //guess default value
            if (is_null($column['COLUMN_DEFAULT']) && !$nullable) { // no default
               $default = null;
            } else if ((is_null($column['COLUMN_DEFAULT']) || strtoupper($column['COLUMN_DEFAULT']) == 'NULL') && $nullable) {
               $default = "NULL";
            } else if (!is_null($column['COLUMN_DEFAULT']) && strtoupper($column['COLUMN_DEFAULT']) != 'NULL') {
               if ($column['COLUMN_DEFAULT'] < '1970-01-01 00:00:01') {
                  // Prevent default value to be out of range (lower to min possible value)
                  $defaultDate = new \DateTime('1970-01-01 00:00:01', new \DateTimeZone('UTC'));
                  $defaultDate->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                  $default = $defaultDate->format("Y-m-d H:i:s");
               } else if ($column['COLUMN_DEFAULT'] > '2038-01-19 03:14:07') {
                  // Prevent default value to be out of range (greater to max possible value)
                  $defaultDate = new \DateTime('2038-01-19 03:14:07', new \DateTimeZone('UTC'));
                  $defaultDate->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                  $default = $defaultDate->format("Y-m-d H:i:s");
               } else {
                  $default = $column['COLUMN_DEFAULT'];
               }
            }

            //build alter
            $tablealter .= "\n\t MODIFY COLUMN ".$this->db->quoteName($column['COLUMN_NAME'])." TIMESTAMP";
            if ($nullable) {
               $tablealter .= " NULL";
            } else {
               $tablealter .= " NOT NULL";
            }
            if ($default !== null) {
               if ($default !== 'NULL') {
                  $default = "'" . $this->db->escape($default) . "'";
               }
               $tablealter .= " DEFAULT $default";
            }
            if ($column['COLUMN_COMMENT'] != '') {
               $tablealter .= " COMMENT '".$this->db->escape($column['COLUMN_COMMENT'])."'";
            }
            $tablealter .= ",";
         }
         $tablealter =  rtrim($tablealter, ",");

         // apply alter to table
         $query = "ALTER TABLE " . $this->db->quoteName($table['TABLE_NAME']) . " " . $tablealter.";\n";
         $output->writeln(
            '<comment>' . sprintf(__('Running %s'), $query) . '</comment>',
            OutputInterface::VERBOSITY_VERBOSE
         );

         $result = $this->db->query($query);
         if (false === $result) {
            $message = sprintf(
               __('Update of `%s` failed with message "(%s) %s".'),
               $table['TABLE_NAME'],
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
