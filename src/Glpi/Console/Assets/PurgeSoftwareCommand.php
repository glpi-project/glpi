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

namespace Glpi\Console\Assets;

use Glpi\Console\AbstractCommand;
use PurgeSoftwareTask;
use Software;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PurgeSoftwareCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('assets:purgesoftware');
        $this->setDescription(Software::getPurgeTaskDescription());

        $this->addOption(
            'max',
            'm',
            InputOption::VALUE_REQUIRED,
            Software::getPurgeTaskParameterDescription(),
            500
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->validateInput($input);
        $max = $input->getOption('max');

        $task = new PurgeSoftwareTask();
        $total = $task->run($max);
        $output->writeln('<info>' . sprintf(__('%s item(s) removed from the database.'), $total) . '</info>');

        return 0;
    }

    /**
     * Validate command input.
     *
     * @param InputInterface $input
     *
     * @throws InvalidArgumentException
     */
    private function validateInput(InputInterface $input)
    {
        $max = $input->getOption('max');
        if (!is_numeric($max)) {
            throw new InvalidArgumentException(
                __('Option --max must be an integer.')
            );
        }
    }
}
