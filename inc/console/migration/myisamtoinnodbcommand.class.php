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

use DB;
use Glpi\Console\AbstractCommand;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class MyIsamToInnoDbCommand extends AbstractCommand {

   /**
    * Error code returned when failed to migrate one table.
    *
    * @var integer
    */
   const ERROR_TABLE_MIGRATION_FAILED = 1;

   protected function configure() {
      parent::configure();

      $this->setName('glpi:migration:myisam_to_innodb');
      $this->setDescription(__('Migrate MyISAM tables to InnoDB'));
   }

   protected function execute(InputInterface $input, OutputInterface $output) {

      $no_interaction = $input->getOption('no-interaction'); // Base symfony/console option

      $myisam_tables = $this->db->getMyIsamTables();

      $output->writeln(
         sprintf(
            '<info>' . __('Found %s table found using MyISAM engine.') . '</info>',
            $myisam_tables->count()
         )
      );

      if (0 === $myisam_tables->count()) {
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

      while ($table = $myisam_tables->next()) {
         $table_name = DB::quoteName($table['TABLE_NAME']);
         $output->writeln(
            '<comment>' . sprintf(__('Migrating table "%s"...'), $table_name) . '</comment>',
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
            $output->writeln(
               '<error>' . $message . '</error>',
               OutputInterface::VERBOSITY_QUIET
            );
            return self::ERROR_TABLE_MIGRATION_FAILED;
         }
      }

      $output->writeln('<info>' . __('Migration done.') . '</info>');

      return 0; // Success
   }
}
