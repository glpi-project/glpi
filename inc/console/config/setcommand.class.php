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

namespace Glpi\Console\Config;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Config;
use Glpi\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Toolbox;

class SetCommand extends AbstractCommand {

   /**
    * Error thrown when context is invalid.
    *
    * @var integer
    */
   const ERROR_INVALID_CONTEXT = 1;

   protected function configure() {
      parent::configure();

      $this->setName('glpi:config:set');
      $this->setAliases(['config:set']);
      $this->setDescription(__('Set configuration value'));
      $this->addArgument('key', InputArgument::REQUIRED, 'Configuration key');
      $this->addArgument('value', InputArgument::REQUIRED, 'Configuration value (ommit argument to be prompted for value)');
      $this->addOption('context', 'c', InputOption::VALUE_REQUIRED, 'Configuration context', 'core');
   }

   protected function interact(InputInterface $input, OutputInterface $output) {
      if (null === $input->getArgument('value')) {
         /** @var \Symfony\Component\Console\Helper\QuestionHelper $question_helper */
         $question_helper = $this->getHelper('question');
         $question = new Question(__('Configuration value:'), '');
         $question->setHidden(true); // Hide prompt as configuration value may be sensitive
         $value = $question_helper->ask($input, $output, $question);
         $input->setArgument('value', $value);
      }
   }

   protected function execute(InputInterface $input, OutputInterface $output) {

      $context = $input->getOption('context');
      $key     = Toolbox::addslashes_deep($input->getArgument('key'));
      $value   = Toolbox::addslashes_deep($input->getArgument('value'));

      if (!preg_match('/^core|plugin:[a-z]+$/', $context)) {
         $output->writeln(
            sprintf(
               '<error>' . __('Invalid context "%s".') . '</error>',
               $context
            ),
            OutputInterface::VERBOSITY_QUIET
         );
         return self::ERROR_INVALID_CONTEXT;
      }

      Config::setConfigurationValues($context, [$key => $value]);

      $output->writeln('<info>' . __(sprintf('Configuration "%s" updated.', $key)) . '</info>');

      return 0; // Success
   }
}
