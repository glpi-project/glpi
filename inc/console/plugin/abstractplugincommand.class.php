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

namespace Glpi\Console\Plugin;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Glpi\Console\AbstractCommand;
use Glpi\Console\Command\ForceNoPluginsOptionCommandInterface;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

abstract class AbstractPluginCommand extends AbstractCommand implements ForceNoPluginsOptionCommandInterface {

   /**
    * Wildcard value to target all directories.
    *
    * @var string
    */
   const DIRECTORY_ALL = '*';

   protected function configure() {
      parent::configure();

      $this->addOption(
         'all',
         'a',
         InputOption::VALUE_NONE,
         __('Run command on all plugins')
      );

      $this->addArgument(
         'directory',
         InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
         __('Plugin directory')
      );
   }

   protected function interact(InputInterface $input, OutputInterface $output) {

      $all         = $input->getOption('all');
      $directories = $input->getArgument('directory');

      if ($all && !empty($directories)) {
         throw new InvalidArgumentException(
            __('Option --all is not compatible with usage of directory argument.')
         );
      }

      if ($all) {
         // Set wildcard value in directory argument
         $input->setArgument('directory', [self::DIRECTORY_ALL]);
      } else if (empty($directories)) {
         // Ask for plugin list if directory argument is empty
         $choices = $this->getDirectoryChoiceChoices();
         $choices = array_merge(
            [self::DIRECTORY_ALL => __('All plugins')],
            $choices
         );

         if (!empty($choices)) {
            /** @var \Symfony\Component\Console\Helper\QuestionHelper $question_helper */
            $question_helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
               $this->getDirectoryChoiceQuestion(),
               $choices
            );
            $question->setAutocompleterValues(array_keys($choices));
            $question->setMultiselect(true);
            $answer = $question_helper->ask(
               $input,
               $output,
               $question
            );
            $input->setArgument('directory', $answer);
         }
      }
   }

   public function getNoPluginsOptionValue() {

      // Force no loading on plugins in plugin install process
      return true;
   }

   /**
    * Normalize input to symplify handling of specific arguments/options values.
    *
    * @param InputInterface $input
    *
    * @return void
    */
   protected function normalizeInput(InputInterface $input) {

      if ($input->getArgument('directory') === [self::DIRECTORY_ALL]) {
         $input->setArgument('directory', array_keys($this->getDirectoryChoiceChoices()));
      }
   }

   /**
    * Returns question to ask if no directory argument has been passed.
    *
    * @return string
    */
   abstract protected function getDirectoryChoiceQuestion();


   /**
    * Returns possible directory choices to suggest if no directory argument has been passed.
    * Returns an array usable in a ChoiceQuestion object.
    *
    * @return string[]
    */
   abstract protected function getDirectoryChoiceChoices();
}
