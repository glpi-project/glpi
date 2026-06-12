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
use Glpi\Security\SessionTracker;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use User;

class SessionListCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('security:session:list');
        $this->setDescription(__('List active sessions'));
        $this->addOption('login', 'u', InputOption::VALUE_OPTIONAL, __('Username'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getOption('login');
        $user = new User();

        if ($username && !$user->getFromDBbyName($username)) {
            $output->writeln("<error>" . sprintf(__("User %s not found"), $username) . "</error>");
            return 1;
        }

        $session_tracker = new SessionTracker();

        $sessions = $session_tracker->getSessions($user->getID(), [
            'status' => 'active',
        ]);

        $table = new Table($output);
        $table->setHeaders([
            __('ID'),
            _n('Type', 'Types', 1),
            _n('User', 'Users', 1),
            __('Details'),
            _n('IP address', 'IP addresses', 1),
            __('Login'),
            __('Last activity'),
        ]);
        foreach ($sessions as $session) {
            $table->addRow([
                $session['internal_identifier'],
                $session['type_raw'],
                $session['user'],
                html_entity_decode(strip_tags($session['details'])),
                $session['ip_address'],
                $session['login'],
                $session['last_activity'],
            ]);
        }

        $table->render();
        return 0;
    }
}
