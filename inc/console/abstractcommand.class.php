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

namespace Glpi\Console;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use DB;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command {

   /**
    * @var DB
    */
   protected $db;

   /**
    * @var InputInterface
    */
   protected $input;

   /**
    * @var OutputInterface
    */
   protected $output;

   protected function initialize(InputInterface $input, OutputInterface $output) {

      $this->input = $input;
      $this->output = $output;

      $this->initDbConnection();
   }

   /**
    * Check database connection.
    *
    * @throws RuntimeException
    *
    * @return void
    */
   protected function initDbConnection() {

      global $DB;

      if (!($DB instanceof DB) || !$DB->connected) {
         throw new RuntimeException(__('Unable to connect to database.'));
      }

      $this->db = $DB;
   }

   /**
    * Correctly write output messages when a progress bar is displayed.
    *
    * @param string|array $messages
    * @param ProgressBar  $progress_bar
    * @param integer      $verbosity
    *
    * @return void
    */
   protected function writelnOutputWithProgressBar($messages,
                                                   ProgressBar $progress_bar,
                                                   $verbosity = OutputInterface::VERBOSITY_NORMAL) {

      if ($verbosity > $this->output->getVerbosity()) {
         return; // Do nothing if message will not be output due to its too high verbosity
      }

      $progress_bar->clear();
      $this->output->writeln(
         $messages,
         $verbosity
      );
      $progress_bar->display();
   }

   /**
    * Output session buffered messages.
    *
    * @param array $levels_to_output
    *
    * @return void
    */
   protected function outputSessionBufferedMessages($levels_to_output = [INFO, WARNING, ERROR]) {

      if (empty($_SESSION['MESSAGE_AFTER_REDIRECT'])) {
         return;
      }

      $msg_levels = [
         INFO    => [
            'tag'       => 'info',
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
         ],
         WARNING => [
            'tag'       => 'comment',
            'verbosity' => OutputInterface::VERBOSITY_NORMAL,
         ],
         ERROR   => [
            'tag'       => 'error',
            'verbosity' => OutputInterface::VERBOSITY_QUIET,
         ],
      ];

      foreach ($msg_levels as $key => $options) {
         if (!in_array($key, $levels_to_output)) {
            continue;
         }

         if (!array_key_exists($key, $_SESSION['MESSAGE_AFTER_REDIRECT'])) {
            continue;
         }

         foreach ($_SESSION['MESSAGE_AFTER_REDIRECT'][$key] as $message) {
            $message = strip_tags(preg_replace('/<br\s*\/?>/', ' ', $message)); // Output raw text
            $this->output->writeln(
               "<{$options['tag']}>{$message}</{$options['tag']}>",
               $options['verbosity']
            );
         }
      }
   }
}
