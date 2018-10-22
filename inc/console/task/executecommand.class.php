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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;

class ExecuteCommand extends AbstractCommand {

   protected function configure() {
      parent::configure();

      $this->setName('glpi:task:execute');
      $this->setAliases(['task:execute']);
      $this->setDescription(__('Execute automatic task'));

      $this->addOption(
         'force',
         'f',
         InputOption::VALUE_NONE,
         'Force execution of task, even if disabled or already running'
      );

      $this->addOption(
         'task',
         't',
         InputOption::VALUE_REQUIRED,
         __('Itemtype::name of task to execute (e.g: "MailCollector::mailgate")')
      );
   }

   protected function execute(InputInterface $input, OutputInterface $output) {

      $force = $input->getOption('force');
      $task  = $input->getOption('task');

      $matches = [];
      if (1 !== preg_match('/^(?P<itemtype>\w+)::(?P<name>\w+)$/', $task, $matches)) {
         throw new InvalidArgumentException(
            sprintf(__('Invalid value "%s" for option --task.'), $task)
         );
      }

      $task_crit = [
         'itemtype' => $matches['itemtype'],
         'name'     => $matches['name'],
      ];
      $task_obj = new CronTask();
      if (false === $task_obj->getFromDBByCrit($task_crit)) {
         throw new InvalidArgumentException(
            sprintf(__('Unknown task "%s".'), $task)
         );
      }

      if (CronTask::STATE_DISABLE === (int)$task_obj->fields['state'] && !$force) {
         $output->writeln(
            '<info>'
               . sprintf(__('Task "%s" is disabled. Use --force option to force execution.'), $task)
               . '</info>',
            OutputInterface::VERBOSITY_QUIET
         );
         return 0;
      }

      if (CronTask::STATE_RUNNING === (int)$task_obj->fields['state'] && !$force) {
         $output->writeln(
            '<info>'
               . sprintf(__('Task "%s" is already running. Use --force option to force execution.'), $task)
               . '</info>',
            OutputInterface::VERBOSITY_QUIET
         );
         return 0;
      }

      $result = $task_obj->run($force);

      // TODO Output human readable result

      return $result;
   }
}
