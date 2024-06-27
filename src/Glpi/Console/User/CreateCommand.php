<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Console\User;

use Glpi\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class CreateCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->setName('user:create');
        $this->setDescription(__('Create a new local GLPI user'));

        $this->addArgument('username', InputArgument::REQUIRED, __('Login'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $user_input = ['name' => $input->getArgument('username')];

        $user = new \User();
        if ($user->getFromDBbyName($user_input['name'])) {
            $output->writeln('<error>' . __('User already exists') . '</error>');
            return 1;
        }

        // Ask for password and then confirm it
        $helper = $this->getHelper('question');
        $question = new Question(__('Enter password'));
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $password = $helper->ask($input, $output, $question);
        $question = new Question(__('Confirm password'));
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $password2 = $helper->ask($input, $output, $question);
        if ($password !== $password2) {
            $output->writeln('<error>' . __('Passwords do not match') . '</error>');
            return 1;
        }
        $user_input['password'] = $password;
        $user_input['password2'] = $password;

        if ($user->add($user_input)) {
            $output->writeln('<info>' . __('User created') . '</info>');
            return 0;
        } else {
            $output->writeln('<error>' . __('Failed to create user') . '</error>');
            return 1;
        }
    }
}
