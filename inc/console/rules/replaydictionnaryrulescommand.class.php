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

namespace Glpi\Console\Rules;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Glpi\Console\AbstractCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class ReplayDictionnaryRulesCommand extends AbstractCommand {

   protected function configure() {
      parent::configure();

      $this->setName('glpi:rules:replay_dictionnary_rules');
      $this->setAliases(['rules:replay_dictionnary_rules']);
      $this->setDescription(__('Replay dictionnary rules on existing items'));

      $this->addOption(
         'dictionnary',
         'd',
         InputOption::VALUE_REQUIRED,
         sprintf(
            __('Dictionnary to use. Possible values are: %s'),
            implode(', ', $this->getDictionnaryTypes())
         )
      );

      $this->addOption(
         'manufacturer-id',
         'm',
         InputOption::VALUE_REQUIRED,
         __('If option is set, only items having given manufacturer ID will be processed.')
            . "\n" . __('Currently only available for Software dictionnary.')
      );
   }

   protected function interact(InputInterface $input, OutputInterface $output) {

      if (empty($input->getOption('dictionnary'))) {
         // Ask for dictionnary argument is empty
         /** @var \Symfony\Component\Console\Helper\QuestionHelper $question_helper */
         $question_helper = $this->getHelper('question');
         $question = new ChoiceQuestion(
            __('Which dictionnary do you want to replay ?'),
            $this->getDictionnaryTypes()
         );
         $answer = $question_helper->ask(
            $input,
            $output,
            $question
         );
         $input->setOption('dictionnary', $answer);
      }
   }

   protected function execute(InputInterface $input, OutputInterface $output) {

      $dictionnary = $input->getOption('dictionnary');
      $rulecollection = \RuleCollection::getClassByType($dictionnary);

      if (!in_array($dictionnary, $this->getDictionnaryTypes())
          || !($rulecollection instanceof \RuleCollection)) {
         throw new InvalidArgumentException(
            sprintf(__('Invalid "dictionnary" value.'))
         );
      }

      $params = [];
      if (null !== ($manufacturer_id = $input->getOption('manufacturer-id'))) {
         $params['manufacturer'] = $manufacturer_id;
      }

      // Nota: implementations of RuleCollection::replayRulesOnExistingDB() are printing
      // messages during execution on CLI mode.
      // This could be improved by using the $output object to handle choosed verbosity level.
      $rulecollection->replayRulesOnExistingDB(0, 0, [], $params);

      return 0; // Success
   }

   /**
    * Return list of available disctionnary types.
    *
    * @return string[]
    */
   private function getDictionnaryTypes(): array {
      global $CFG_GLPI;
      $types = $CFG_GLPI['dictionnary_types'];
      sort($types);
      return $types;
   }
}
