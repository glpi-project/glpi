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

namespace Glpi\Console\Security;

use Glpi\Console\AbstractCommand;
use Glpi\Security\TOTPManager;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use User;

class DisableTFACommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('security:disable_2fa');
        $this->setDescription(__('Disable 2FA for a user'));
        $this->addArgument('login', InputArgument::REQUIRED, __('Username'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('login');
        $user = new User();
        if (!$user->getFromDBbyName($username)) {
            $output->writeln("<error>" . sprintf(__("User %s not found"), $username) . "</error>");
            return 1;
        }
        $totp_manager = new TOTPManager();
        if (!$totp_manager->is2FAEnabled($user->getID())) {
            $output->writeln("<error>" . __("2FA is not enabled for this user") . "</error>");
            return 0;
        }
        if ($totp_manager->get2FAEnforcement($user->getID())) {
            $output->writeln("<info>" . __("2FA is enforced for this user. They will be required to set it up again the next time they log in.") . "</info>");
        }
        $helper = new QuestionHelper();
        $question = new ConfirmationQuestion(__('Are you sure you want to disable 2FA for this user?'), false);
        if (!$helper->ask($input, $output, $question)) {
            return 0;
        }
        $totp_manager->disable2FAForUser($user->getID());
        $output->writeln("<info>" . sprintf(__("2FA disabled for user %s"), $username) . "</info>");
        return 0;
    }
}
