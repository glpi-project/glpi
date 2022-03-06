<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class UnsignedKeysCommand extends AbstractCommand {

   /**
    * Error code returned when failed to migrate one column.
    *
    * @var int
    */
   const ERROR_COLUMN_MIGRATION_FAILED = 1;

   protected function configure() {
      parent::configure();

      $this->setName('glpi:migration:unsigned_keys');
      $this->setDescription(__('Migrate primary/foreign keys to unsigned integers'));
   }

   protected function execute(InputInterface $input, OutputInterface $output) {

      $no_interaction = $input->getOption('no-interaction');

      $columns = $this->db->getSignedKeysColumns();

      $output->writeln(
         sprintf(
            '<info>' . __('Found %s primary/foreign key columns(s) using signed integers.') . '</info>',
            $columns->count()
         )
      );

      if ($columns->count() === 0) {
         $output->writeln('<info>' . __('No migration needed.') . '</info>');
         return 0;
      }

      if (!$no_interaction) {
         // Ask for confirmation (unless --no-interaction)
         /** @var QuestionHelper $question_helper */
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

      $foreign_keys = $this->db->getForeignKeysContraints();

      $progress_bar = new ProgressBar($output);
      $errors = 0;

      foreach ($progress_bar->iterate($columns) as $column) {
         $table_name  = $column['TABLE_NAME'];
         $column_name = $column['COLUMN_NAME'];
         $data_type   = $column['DATA_TYPE'];
         $nullable    = $column['IS_NULLABLE'] === 'YES';
         $default     = $column['COLUMN_DEFAULT'];
         $extra       = $column['EXTRA'];

         $min = $this->db
            ->request(['SELECT' => ['MIN' => sprintf('%s AS min', $column_name)], 'FROM' => $table_name])
            ->next()['min'];

         if (($min !== null && $min < 0) || ($default !== null && $default < 0)) {
            $message = sprintf(
               __('Migration of column "%s.%s" cannot be done as it contains negative values.'),
               $table_name,
               $column_name
            );
            $this->writelnOutputWithProgressBar(
                '<error>' . $message . '</error>',
                $progress_bar,
                OutputInterface::VERBOSITY_QUIET
            );
            $errors++;
            continue; // Do not migrate this column
         }

         // Ensure that column is not referenced in a CONSTRAINT key.
         foreach ($foreign_keys as $foreign_key) {
            if (($foreign_key['TABLE_NAME'] === $table_name && $foreign_key['COLUMN_NAME'] === $column_name)
                || ($foreign_key['REFERENCED_TABLE_NAME'] === $table_name && $foreign_key['REFERENCED_COLUMN_NAME'] === $column_name)) {
               $message = sprintf(
                  __('Migration of column "%s.%s" cannot be done as it is referenced in CONSTRAINT "%s" of table "%s.%s".'),
                  $table_name,
                  $column_name,
                  $foreign_key['CONSTRAINT_NAME'],
                  $foreign_key['TABLE_NAME'],
                  $foreign_key['COLUMN_NAME']
               );
               $this->writelnOutputWithProgressBar(
                   '<error>' . $message . '</error>',
                   $progress_bar,
                   OutputInterface::VERBOSITY_QUIET
               );
               $errors++;
               continue 2; // Non blocking error, it should not prevent migration of other fields
            }
         }

         $this->writelnOutputWithProgressBar(
            '<comment>' . sprintf(__('Migrating column "%s.%s"...'), $table_name, $column_name) . '</comment>',
             $progress_bar,
             OutputInterface::VERBOSITY_VERBOSE
         );

         $query = sprintf(
            'ALTER TABLE %s MODIFY COLUMN %s %s unsigned %s %s %s',
            $this->db->quoteName($table_name),
            $this->db->quoteName($column_name),
            $data_type,
            $nullable ? 'NULL' : 'NOT NULL',
            $default !== null || $nullable ? sprintf('DEFAULT %s', $this->db->quoteValue($default)) : '',
            $extra
         );

         $result = $this->db->query($query);

         if ($result === false) {
            $message = sprintf(
               __('Migration of column "%s.%s" failed with message "(%s) %s".'),
               $table_name,
               $column_name,
               $this->db->errno(),
               $this->db->error()
            );
            $this->writelnOutputWithProgressBar(
               '<error>' . $message . '</error>',
                $progress_bar,
                OutputInterface::VERBOSITY_QUIET
            );
            $errors++;
            continue; // Go to next column
         }
      }

      $this->output->write(PHP_EOL);

      if ($errors) {
         $output->writeln(
            '<error>' . __('Errors occured during migration.') . '</error>',
            OutputInterface::VERBOSITY_QUIET
         );
         return self::ERROR_COLUMN_MIGRATION_FAILED;
      }

      $output->writeln('<info>' . __('Migration done.') . '</info>');

      return 0; // Success
   }
}
