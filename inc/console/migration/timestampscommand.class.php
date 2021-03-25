<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

use Glpi\Console\AbstractCommand;
use QueryExpression;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class TimestampsCommand extends AbstractCommand {

   protected function configure() {
      parent::configure();

      $this->setName('glpi:migration:timestamps');
      $this->setDescription(__('Convert "datetime" fields to "timestamp" to use timezones.'));
   }

   protected function execute(InputInterface $input, OutputInterface $output) {
      //convert db

      // we are going to update datetime types to timestamp type
      $tbl_iterator = $this->db->request([
         'SELECT'       => ['information_schema.columns.table_name as TABLE_NAME'],
         'DISTINCT'     => true,
         'FROM'         => 'information_schema.columns',
         'INNER JOIN'   => [
            'information_schema.tables' => [
               'FKEY' => [
                  'information_schema.tables'  => 'table_name',
                  'information_schema.columns' => 'table_name',
                  [
                     'AND' => [
                        'information_schema.tables.table_schema' => new QueryExpression(
                           $this->db->quoteName('information_schema.columns.table_schema')
                        ),
                     ]
                  ],
               ]
            ]
         ],
         'WHERE'       => [
            'information_schema.columns.table_schema' => $this->db->dbdefault,
            'information_schema.columns.table_name'   => ['LIKE', 'glpi\_%'],
            'information_schema.columns.data_type'    => 'datetime',
            'information_schema.tables.table_type'    => 'BASE TABLE',

         ],
         'ORDER'       => [
            'information_schema.columns.table_name'
         ]
      ]);

      $output->writeln(
         sprintf(
            '<info>' . __('Found %s table(s) requiring migration.') . '</info>',
            $tbl_iterator->count()
         )
      );

      if ($tbl_iterator->count() === 0) {
         $output->writeln('<info>' . __('No migration needed.') . '</info>');
         return 0; // Success
      }

      if (!$input->getOption('no-interaction')) {
         // Ask for confirmation (unless --no-interaction)
         /** @var \Symfony\Component\Console\Helper\QuestionHelper $question_helper */
         $question_helper = $this->getHelper('question');
         $run = $question_helper->ask(
            $input,
            $output,
            new ConfirmationQuestion(__('Do you want to continue ?') . ' [Yes/no]', true)
         );
         if (!$run) {
            $output->writeln(
               '<comment>' . __('Migration aborted.') . '</comment>',
               OutputInterface::VERBOSITY_VERBOSE
            );
            return 0;
         }
      }

      $progress_bar = new ProgressBar($output, $tbl_iterator->count());
      $progress_bar->start();

      while ($table = $tbl_iterator->next()) {
         $progress_bar->advance(1);

         $tablealter = ''; // init by default

         // get accurate info from information_schema to perform correct alter
         $col_iterator = $this->db->request([
            'SELECT' => [
               'table_name AS TABLE_NAME',
               'column_name AS COLUMN_NAME',
               'column_default AS COLUMN_DEFAULT',
               'column_comment AS COLUMN_COMMENT',
               'is_nullable AS IS_NULLABLE',
            ],
            'FROM'   => 'information_schema.columns',
            'WHERE'  => [
               'table_schema' => $this->db->dbdefault,
               'table_name'   => $table['TABLE_NAME'],
               'data_type'    => 'datetime'
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
               // Prevent MySQL/MariaDB to force "default current_timestamp on update current_timestamp"
               // as "on update current_timestamp" could be a real problem on fields like "date_creation".
               $default = "CURRENT_TIMESTAMP";
            } else if ((is_null($column['COLUMN_DEFAULT']) || strtoupper($column['COLUMN_DEFAULT']) == 'NULL') && $nullable) {
               $default = "NULL";
            } else if (!is_null($column['COLUMN_DEFAULT']) && strtoupper($column['COLUMN_DEFAULT']) != 'NULL') {
               if (preg_match('/^current_timestamp(\(\))?$/i', $column['COLUMN_DEFAULT']) === 1) {
                  $default = $column['COLUMN_DEFAULT'];
               } else if ($column['COLUMN_DEFAULT'] < '1970-01-01 00:00:01') {
                  // Prevent default value to be out of range (lower to min possible value)
                  $defaultDate = new \DateTime('1970-01-01 00:00:01', new \DateTimeZone('UTC'));
                  $defaultDate->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                  $default = $this->db->quoteValue($defaultDate->format("Y-m-d H:i:s"));
               } else if ($column['COLUMN_DEFAULT'] > '2038-01-19 03:14:07') {
                  // Prevent default value to be out of range (greater to max possible value)
                  $defaultDate = new \DateTime('2038-01-19 03:14:07', new \DateTimeZone('UTC'));
                  $defaultDate->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                  $default = $this->db->quoteValue($defaultDate->format("Y-m-d H:i:s"));
               } else {
                  $default = $this->db->quoteValue($column['COLUMN_DEFAULT']);
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
         $this->writelnOutputWithProgressBar(
            '<comment>' . sprintf(__('Running %s'), $query) . '</comment>',
            $progress_bar,
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
            $this->writelnOutputWithProgressBar(
               '<error>' . $message . '</error>',
               $progress_bar,
               OutputInterface::VERBOSITY_QUIET
            );
         }
      }

      $progress_bar->finish();
      $this->output->write(PHP_EOL);

      $output->writeln('<info>' . __('Migration done.') . '</info>');

      return 0; // Success
   }
}
