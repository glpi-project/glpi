<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Auth;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use User;

class ResetPasswordCommand extends AbstractUserCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->setName('user:reset_password');
        $this->setDescription(__('Reset the password of a local GLPI user'));

        $this->addOption('password', 'p', InputOption::VALUE_OPTIONAL, __('Password'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $user_input = ['name' => $input->getArgument('username')];

        $user = new User();
        if (!$user->getFromDBbyName($user_input['name'])) {
            $output->writeln('<error>' . __('User not found') . '</error>');
            return 1;
        }

        if ($user->fields['authtype'] !== Auth::DB_GLPI) {
            $output->writeln('<error>' . __("The authentication method configuration doesn't allow you to change your password.") . '</error>');
            return 1;
        }

        $user_input['id'] = $user->getID();

        $password = $this->askForPassword($input, $output);
        if ($password === false) {
            return 1;
        }
        $user_input['password'] = $password;
        $user_input['password2'] = $password;


        if ($user->update($user_input)) {
            $output->writeln('<info>' . __('Reset password successful.') . '</info>');
            return 0;
        } else {
            $output->writeln('<error>' . __('Unable to reset password, please contact your administrator') . '</error>');
            return 1;
        }
    }
}
