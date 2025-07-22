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

namespace Glpi\Console\Config;

use Config;
use Glpi\Console\AbstractCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

use function Safe\preg_match;

class SetCommand extends AbstractCommand
{
    /**
     * Error thrown when context is invalid.
     *
     * @var integer
     */
    public const ERROR_INVALID_CONTEXT = 1;

    protected function configure()
    {
        parent::configure();

        $this->setName('config:set');
        $this->setDescription(__('Set configuration value'));
        $this->addArgument('key', InputArgument::REQUIRED, 'Configuration key');
        $this->addArgument('value', InputArgument::REQUIRED, 'Configuration value (ommit argument to be prompted for value)');
        $this->addOption('context', 'c', InputOption::VALUE_REQUIRED, 'Configuration context', 'core');
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (null === $input->getArgument('value')) {
            $question_helper = new QuestionHelper();
            $question = new Question(__('Configuration value:'), '');
            $question->setHidden(true); // Hide prompt as configuration value may be sensitive
            $value = $question_helper->ask($input, $output, $question);
            $input->setArgument('value', $value);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $context = $input->getOption('context');
        $key     = $input->getArgument('key');
        $value   = $input->getArgument('value');

        if (!preg_match('/^core|inventory|plugin:[a-z]+$/', $context)) {
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
