<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
use Glpi\OAuth\AccessTokenRepository;
use Glpi\OAuth\RefreshTokenRepository;
use Glpi\Security\SessionTracker;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use User;

class SessionRevokeCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('security:session:revoke');
        $this->setDescription(__('Revoke login sessions'));
        // For revoking all sessions of a user
        $this->addOption('login', 'u', InputOption::VALUE_OPTIONAL, __('Username'));
        // For revoking all sessions
        $this->addOption('all', 'a', InputOption::VALUE_NONE, __('Revoke all sessions'));
        // For revoking a specific session
        $this->addOption('session-id', 's', InputOption::VALUE_OPTIONAL, __('Session ID to revoke'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sessionTracker = new SessionTracker();

        $access_repo = new AccessTokenRepository();
        $refresh_repo = new RefreshTokenRepository();

        if ($input->getOption('all')) {
            SessionTracker::revokeAllSessionsExceptCurrent(0);
            $access_repo->revokeAll();
            $refresh_repo->revokeAll();
            $output->writeln('<info>All sessions have been revoked.</info>');
            return 0;
        }

        if ($session_id = $input->getOption('session-id')) {
            if (UuidV4::isValid($session_id)) {
                $access_repo->revokeAccessTokenByUUID($session_id);
            } else {
                $sessionTracker::revokeSession($session_id, 'admin');
            }
            $output->writeln("<info>" . sprintf(__("Session %s has been revoked."), $session_id) . "</info>");
            return 0;
        }

        if ($username = $input->getOption('login')) {
            $user = new User();
            if (!$user->getFromDBbyName($username)) {
                $output->writeln("<error>" . sprintf(__("User %s not found"), $username) . "</error>");
                return 1;
            }
            SessionTracker::revokeAllSessionsExceptCurrent($user->getID());
            $access_repo->revokeAllForUser($user->getID());
            $output->writeln("<info>" . sprintf(__("All sessions for user %s have been revoked."), $username) . "</info>");
            return 0;
        }

        $output->writeln('<error>' . __('You must provide either a username, a session ID, or the --all option to revoke sessions.') . '</error>');

        return 1;
    }
}
