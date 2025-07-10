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

namespace Glpi\Console\Plugin;

use Glpi\Console\AbstractCommand;
use Plugin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SuspendExecutionCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('plugin:suspend_execution');
        $this->setDescription(__('Suspend execution of all active plugins'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!(new Plugin())->suspendAllPluginsExecution()) {
            $this->output->writeln(
                '<error>' . __('An unexpected error occurred') . '</error>',
                OutputInterface::VERBOSITY_QUIET
            );
            return self::FAILURE;
        }

        $output->writeln('<info>' . __('Execution of all active plugins has been suspended.') . '</info>');

        return self::SUCCESS;
    }
}
