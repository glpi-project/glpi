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
use Glpi\Event;
use QueryExpression;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;

class UnlockCommand extends AbstractCommand {

   protected function configure() {
      parent::configure();

      $this->setName('glpi:task:unlock');
      $this->setAliases(['task:unlock']);
      $this->setDescription(__('Unlock automatic tasks'));

      $this->addOption(
         'all',
         'a',
         InputOption::VALUE_NONE,
         __('Unlock all tasks')
      );

      $this->addOption(
         'cycle',
         'c',
         InputOption::VALUE_OPTIONAL,
         __('Execution time (in cycles) from which the task is considered as stuck (delay = task frequency * cycle)'),
         null // Has to be null to detect lack of definition of both 'cycle' and 'delay' to set default delay (see self::validateInput())
      );

      $this->addOption(
         'delay',
         'd',
         InputOption::VALUE_OPTIONAL,
         __('Execution time (in seconds) from which the task is considered as stuck (default: 1800)'),
         null // Has to be null to detect lack of definition of both 'cycle' and 'delay' to set default delay (see self::validateInput())
      );

      $this->addOption(
         'task',
         't',
         InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
         __('Itemtype::name of task to unlock (e.g: "MailCollector::mailgate")')
      );
   }

   protected function execute(InputInterface $input, OutputInterface $output) {

      $this->validateInput($input);

      $all   = $input->getOption('all');
      $cycle = $input->getOption('cycle');
      $delay = $input->getOption('delay');
      $tasks = $input->getOption('task');

      if (null !== $cycle) {
         $delay = $cycle . ' * ' . $this->db->quoteName('frequency');
      }

      $task_iterator = $this->db->request(
         [
            'SELECT' => [
               'id',
               new QueryExpression(
                  'CONCAT('
                  . $this->db->quoteName('itemtype')
                  . ', ' . $this->db->quoteValue('::')
                  . ', ' . $this->db->quoteName('name')
                  . ') AS ' . $this->db->quoteName('task')
               )
            ],
            'FROM'   => Crontask::getTable(),
            'WHERE'  => [
               'state' => Crontask::STATE_RUNNING,
               new QueryExpression(
                  'UNIX_TIMESTAMP(' .  $this->db->quoteName('lastrun') . ') + ' . $delay
                  . ' <  UNIX_TIMESTAMP(NOW())'
               )
            ]
         ]
      );

      $crontask = new CronTask();
      $unlocked_count = 0;

      foreach ($task_iterator as $task) {
         if (!$all && !in_array($task['task'], $tasks)) {
            $output->writeln(
               '<comment>' . sprintf(__('Task "%s" is still running but not in the whitelist.'), $task['task']) . '</comment>',
               OutputInterface::VERBOSITY_VERBOSE
            );
            continue;
         }

         $input = [
            'id'    => $task['id'],
            'state' => CronTask::STATE_WAITING,
         ];
         if ($crontask->update($input)) {
            $unlocked_count++;
            $message = sprintf(__('Task "%s" unlocked.'), $task['task']);
            $output->writeln('<info>' . $message . '</info>');
            Event::log($task['id'], 'CronTask', 5, 'Configuration', $message);
         } else {
            $output->writeln(
               sprintf(
                  '<error>' . __('An error occurs while trying to unlock "%s" task.') . '</error>',
                  $task['task']
               ),
               OutputInterface::VERBOSITY_QUIET
            );
         }
      }
      $output->writeln(
         '<info>' .sprintf(__('Number of tasks unlocked: %d.'), $unlocked_count) . '</info>'
      );

      return 0; // Success
   }

   /**
    * Validate command input.
    *
    * @param InputInterface $input
    *
    * @return void
    *
    * @throws InvalidArgumentException
    */
   private function validateInput(InputInterface $input) {

      $all   = $input->getOption('all');
      $cycle = $input->getOption('cycle');
      $delay = $input->getOption('delay');
      $tasks = $input->getOption('task');

      if (null !== $cycle && null !== $delay) {
         throw new InvalidArgumentException(
            __('Option --cycle is not compatible with option --delay.')
         );
      }

      if (null !== $cycle && !preg_match('/^\d+$/', $cycle)) {
         throw new InvalidArgumentException(
            __('Option --cycle has to be an integer.')
         );
      }

      if (null !== $delay && !preg_match('/^\d+$/', $delay)) {
         throw new InvalidArgumentException(
            __('Option --delay has to be an integer.')
         );
      }

      if (null === $cycle && null === $delay) {
         $input->setOption('delay', 1800); // Default delay
      }

      if ($all && !empty($tasks)) {
         throw new InvalidArgumentException(
            __('Option --all is not compatible with option --task.')
         );
      }

      if (!$all && empty($tasks)) {
         throw new InvalidArgumentException(
            __('You have to specify which tasks to unlock using --all or --task options.')
         );
      }
   }
}
