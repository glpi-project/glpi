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

namespace Glpi\Console\Marketplace;

use Glpi\Console\AbstractCommand;
use Glpi\Marketplace\Controller;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

abstract class AbstractMarketplaceCommand extends AbstractCommand
{
    /**
     * Get the available choices for the plugin selection
     *
     * @return array Array of choices where the key is the plugin key and the value is the plugin name
     */
    protected function getPluginChoiceChoices(): array
    {
        $controller = new Controller();
        $plugins = $controller::getAPI()->getAllPlugins();
        $result = [];

        foreach ($plugins as $plugin) {
            $result[$plugin['key']] = $plugin['name'];
        }
        return $result;
    }

    /**
     * Get the plugin choice question prompt
     * @return string
     */
    abstract protected function getPluginChoiceQuestion(): string;

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $plugin_arg_name = null;

        if ($this->getDefinition()->hasArgument('plugin')) {
            $plugin_arg_name = 'plugin';
        } elseif ($this->getDefinition()->hasArgument('plugins')) {
            $plugin_arg_name = 'plugins';
        }
        if ($plugin_arg_name === null) {
            return;
        }

        $directories = $input->getArgument($plugin_arg_name);

        if (empty($directories)) {
            // Ask for plugin list if directory argument is empty
            $choices = $this->getPluginChoiceChoices();

            if (!empty($choices)) {
                /** @var QuestionHelper $question_helper */
                $question_helper = $this->getHelper('question');
                $question = new ChoiceQuestion(
                    $this->getPluginChoiceQuestion(),
                    $choices
                );
                $question->setAutocompleterValues(array_keys($choices));
                $question->setMultiselect($plugin_arg_name === 'plugins');
                $answer = $question_helper->ask(
                    $input,
                    $output,
                    $question
                );
                $input->setArgument($plugin_arg_name, $answer);
            }
        }
    }
}
