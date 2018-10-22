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

namespace Glpi\Console\Task;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use CronTask;
use Glpi\Console\AbstractCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class CronJobCommand extends AbstractCommand {

   /**
    * Error code returned if a global lock exists.
    *
    * @var integer
    */
   const ERROR_GLOBAL_LOCK_EXISTS = 1;

   protected function configure() {
      parent::configure();

      $this->setName('glpi:task:cronjob');
      $this->setAliases(['task:cronjob']);
      $this->setDescription(__('Execute plannified automatic tasks'));
   }

   protected function execute(InputInterface $input, OutputInterface $output) {

      if (is_file(GLPI_CRON_DIR . DIRECTORY_SEPARATOR . 'all.lock')) {
         return self::ERROR_GLOBAL_LOCK_EXISTS; // No cron execution if global lock exists
      }

      $already_executed = [];

      $php_finder = new PhpExecutableFinder();
      $php_path = $php_finder->find();

      while (null !== ($crontask = CronTask::getNextTaskToExecute($already_executed))) {
         $already_executed[] = $crontask->fields['id'];

         if (false !== $php_path) {
            // Run in isolation if PHP executable found
            $process = new Process(
               [
                  $php_path,
                  GLPI_ROOT . DIRECTORY_SEPARATOR . 'console.php',
                  'glpi:task:execute',
                  '--task=' . $crontask->fields['itemtype'] . '::' . $crontask->fields['name'],
                  '-vvv', // TODO Get verbosity from self ($output->getVerbosity() transformed to -q or -v*)
               ]
            );
            $process->setTimeout(null); // No timeout
            $process->run(); // ->start() instead of ->run() to parallelize

            // TODO Output something ? $process->getOutput();
         } else {
            try {
               $crontask->run();
            } catch (\Exception $exception) {
               // Catch exceptions to be able to execute next tasks
               // TODO Output an error
            }
         }
      }

      return 0;
   }
}
