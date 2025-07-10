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

namespace Glpi\Console\System;

use Glpi\Console\AbstractCommand;
use Glpi\System\Status\StatusChecker;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Safe\json_encode;

class CheckStatusCommand extends AbstractCommand
{
    protected $requires_db = false;

    protected function configure()
    {
        parent::configure();

        $this->setName('system:status');
        $this->setDescription(__('Check system status'));
        $this->addOption(
            'private',
            'p',
            InputOption::VALUE_NONE,
            'Status information publicity. Private status information may contain potentially sensitive information such as version information.'
        );
        $this->addOption(
            'service',
            's',
            InputOption::VALUE_OPTIONAL,
            'The service to check or all',
            'all'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $status = StatusChecker::getServiceStatus($input->getOption('service'), !$input->getOption('private'));
        $output->writeln(json_encode($status, JSON_PRETTY_PRINT));

        return 0; // Success
    }
}
