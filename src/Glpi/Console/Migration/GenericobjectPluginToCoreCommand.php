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
use Glpi\Migration\GenericobjectPluginMigration;
use Glpi\Progress\ConsoleProgressIndicator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenericobjectPluginToCoreCommand extends AbstractCommand
{
    use PluginMigrationTrait;

    protected function configure()
    {
        parent::configure();

        $this->setName('migration:genericobject_plugin_to_core');
        $this->setDescription(__('Migrate GenericObject plugin data into GLPI core tables'));

        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            __('Simulate the migration')
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var \Psr\Log\LoggerInterface $PHPLOGGER
         */
        global $PHPLOGGER;

        $migration = new GenericobjectPluginMigration($this->db, $PHPLOGGER);
        $migration->setProgressIndicator(new ConsoleProgressIndicator($output));

        $result = $migration->execute(simulate: $input->getOption('dry-run'));

        $this->outputPluginMigrationResult($output, $result);

        return $result->isFullyProcessed() && $result->hasErrors() ? 0 : 1;
    }
}
