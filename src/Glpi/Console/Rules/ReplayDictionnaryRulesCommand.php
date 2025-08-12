<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\Console\Rules;

use Glpi\Console\AbstractCommand;
use RuleCollection;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class ReplayDictionnaryRulesCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('rules:replay_dictionnary_rules');
        $this->setDescription(__('Replay dictionary rules on existing items'));

        $this->addOption(
            'dictionnary',
            'd',
            InputOption::VALUE_REQUIRED,
            sprintf(
                __('Dictionary to use. Possible values are: %s'),
                implode(', ', $this->getDictionnaryTypes())
            )
        );

        $this->addOption(
            'manufacturer-id',
            'm',
            InputOption::VALUE_REQUIRED,
            __('If option is set, only items having given manufacturer ID will be processed.')
            . "\n" . __('Currently only available for Software dictionary.')
        );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {

        if (empty($input->getOption('dictionnary'))) {
            // Ask for dictionary argument is empty
            $question_helper = new QuestionHelper();
            $question = new ChoiceQuestion(
                __('Which dictionary do you want to replay?'),
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $dictionnary = $input->getOption('dictionnary');
        $rulecollection = RuleCollection::getClassByType($dictionnary);

        if (
            !in_array($dictionnary, $this->getDictionnaryTypes())
            || !($rulecollection instanceof RuleCollection)
        ) {
            throw new InvalidArgumentException(
                __('Invalid "dictionary" value.')
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
    private function getDictionnaryTypes(): array
    {
        global $CFG_GLPI;
        $types = $CFG_GLPI['dictionnary_types'];
        sort($types);
        return $types;
    }
}
