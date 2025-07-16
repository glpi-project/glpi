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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use User;

class DisableCommand extends AbstractUserCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->setName('user:disable');
        $this->setDescription(__('Disable a GLPI user'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $username = $input->getArgument('username');
        $user = new User();
        if ($user->getFromDBbyName($username)) {
            $user->update([
                'id' => $user->getID(),
                'is_active' => 0,
            ]);
            $output->writeln('<info>' . __('User disabled') . '</info>');
            return 0;
        } else {
            $output->writeln('<error>' . __('User not found') . '</error>');
            return 1;
        }
    }
}
