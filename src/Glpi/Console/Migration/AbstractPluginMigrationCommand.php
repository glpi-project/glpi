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

namespace Glpi\Console\Migration;

use Glpi\Console\AbstractCommand;
use Glpi\Console\Traits\PluginMigrationTrait;
use Glpi\Migration\AbstractPluginMigration;
use Glpi\Progress\ConsoleProgressIndicator;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract command for plugin migrations.
 * Concrete classes just need to define their name, description and migration class.
 */
abstract class AbstractPluginMigrationCommand extends AbstractCommand
{
    use PluginMigrationTrait;

    /**
     * Returns an instance of the migration to use.
     *
     * @return AbstractPluginMigration
     */
    abstract public function getMigration(): AbstractPluginMigration;

    protected function configure()
    {
        $this->setName($this->getName());
        $this->setDescription($this->getDescription());

        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            __('Simulate the migration')
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        global $PHPLOGGER;

        if (!$output instanceof ConsoleOutputInterface) {
            throw new LogicException('This command accepts only an instance of "ConsoleOutputInterface".');
        }

        $migration = $this->getMigration();
        $migration->setLogger($PHPLOGGER);
        $migration->setProgressIndicator(new ConsoleProgressIndicator($output));
        $result = $migration->execute((bool) $input->getOption('dry-run'));

        $this->outputPluginMigrationResult($output, $result);

        return $result->isFullyProcessed() && !$result->hasErrors() ? Command::SUCCESS : Command::FAILURE;
    }
}
