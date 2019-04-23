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
use Glpi\Event;
use ITILEvent;
use Toolbox;
use Glpi\Console\AbstractCommand;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Question\ChoiceQuestion;

class EventsToITILEventsCommand extends AbstractCommand {

   /**
    * Error code returned if migration failed.
    *
    * @var integer
    */
   const ERROR_MIGRATION_FAILED = 1;


   protected function configure() {
      parent::configure();

      $this->setName('glpi:migration:events_to_itilevents');
      $this->setDescription(__('Migrate old events to the new ITIL Events format and table'));

      $this->addOption(
         'skip-errors',
         's',
         InputOption::VALUE_NONE,
         __('Do not exit on import errors')
      );

      $this->addOption(
         'drop',
         'd',
         InputOption::VALUE_NONE,
         __('Remove existing event table')
      );
   }

   protected function execute(InputInterface $input, OutputInterface $output) {

      global $DB;

      $no_interaction = $input->getOption('no-interaction');

      if (!$no_interaction) {
         // Ask for confirmation (unless --no-interaction)
         $output->writeln(
            [
               __('You are about to launch migration of old events to the new ITIL Events format and table.'),
               __('It is better to make a backup of your existing data before continuing.')
            ]
         );

         /** @var \Symfony\Component\Console\Helper\QuestionHelper $question_helper */
         $question_helper = $this->getHelper('question');
         $run = $question_helper->ask(
            $input,
            $output,
            new ConfirmationQuestion(
               '<comment>' . __('Do you want to launch migration ?') . ' [yes/No]</comment>',
               false
            )
         );
         if (!$run) {
            $output->writeln(
               '<comment>' . __('Migration aborted.') . '</comment>',
               OutputInterface::VERBOSITY_VERBOSE
            );
            return 0;
         }
      }

      if (!$this->migrateEvents()) {
         return self::ERROR_MIGRATION_FAILED;
      }

      if ($input->getOption('drop')) {
         $DB->rawQuery("DROP TABLE glpi_events;");
      }

      $output->writeln('<info>' . __('Migration done.') . '</info>');

      return 0; // Success
   }

   private  function migrateEvents() {

      $no_interaction = $this->input->getOption('no-interaction');
      $skip_errors = $this->input->getOption('skip-errors');

      $has_errors = false;

      $this->output->writeln(
         '<comment>' . __('Migrating events...') . '</comment>',
         OutputInterface::VERBOSITY_NORMAL
      );

      $items_iterator = $this->db->request(['FROM'  => 'glpi_events']);

      if ($items_iterator->count()) {
         $progress_bar = new ProgressBar($this->output, $items_iterator->count());
         $progress_bar->start();

         $this->db->beginTransaction();
         $itilevent = new ITILEvent();

         foreach ($items_iterator as $event) {
            $progress_bar->advance(1);

            $message = sprintf(__('Importing event %s...'), $event->getID());

            $this->writelnOutputWithProgressBar(
               $message,
               $progress_bar,
               OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $event_data = [
               'name'      => $event->fields['name'],
               'content'   => json_encode([
                  'type'      => $event->fields['type'],
                  'items_id'  => $event->fields['items_id'],
                  'service'   => $event->fields['service'],
                  'level'     => $event->fields['level']
               ] + $extrainfo),
               'significance' => ITILEvent::INFORMATION,
               'date'      => $event->fields['date']
            ];

            if (!$itilevent->add($event_data)) {
               $has_errors = true;

               $message = sprintf(__('Unable to import event %s.'), $event->getID());
               $this->outputImportError($message, $progress_bar);
               if ($this->input->getOption('skip-errors')) {
                  continue;
               } else {
                  $this->output->writeln(
                     '<comment>' . __('Rolling back...') . '</comment>',
                     OutputInterface::VERBOSITY_NORMAL
                  );
                  $this->db->rollBack();
                  return false;
               }
            }
         }

         $progress_bar->finish();
         $this->db->commit();
         $this->output->write(PHP_EOL);
      } else {
         $this->output->writeln(
            '<comment>' . __('No events found.') . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
         );
      }

      return !$has_errors;
   }

   /**
    * Returns verbosity level for import errors.
    *
    * @return number
    */
   private function getImportErrorsVerbosity() {

      return $this->input->getOption('skip-errors')
         ? OutputInterface::VERBOSITY_NORMAL
         : OutputInterface::VERBOSITY_QUIET;
   }

   /**
    * Output import error message.
    *
    * @param string           $message
    * @param ProgressBar|null $progress_bar
    *
    * @return void
    */
   private function outputImportError($message, ProgressBar $progress_bar = null) {

      $skip_errors = $this->input->getOption('skip-errors');

      $verbosity = $skip_errors
         ? OutputInterface::VERBOSITY_NORMAL
         : OutputInterface::VERBOSITY_QUIET;

      $message = '<error>' . $message . '</error>';

      if ($skip_errors && $progress_bar instanceof ProgressBar) {
         $this->writelnOutputWithProgressBar(
            $message,
            $progress_bar,
            $verbosity
         );
      } else {
         if (!$skip_errors && $progress_bar instanceof ProgressBar) {
            $this->output->write(PHP_EOL); // Keep progress bar last state and go to next line
         }
         $this->output->writeln(
            $message,
            $verbosity
         );
      }
   }
}
