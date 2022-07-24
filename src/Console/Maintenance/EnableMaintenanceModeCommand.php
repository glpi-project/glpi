<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

namespace Glpi\Console\Maintenance;

use Config;
use Glpi\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EnableMaintenanceModeCommand extends AbstractCommand
{
    protected $requires_db_up_to_date = false;

    protected function configure()
    {
        parent::configure();

        $this->setName('glpi:maintenance:enable');
        $this->setAliases(
            [
                'maintenance:enable',
            ]
        );
        $this->setDescription(__('Enable maintenance mode'));

        $this->addOption(
            'text',
            't',
            InputOption::VALUE_OPTIONAL,
            __('Text to display during maintenance')
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        global $CFG_GLPI;

        $values = [
            'maintenance_mode' => '1'
        ];
        if ($input->hasOption('text')) {
            $values['maintenance_text'] = $input->getOption('text');
        }
        $config = new Config();
        $config->setConfigurationValues('core', $values);

        $message = sprintf(
            __('Maintenance mode activated. Backdoor using: %s'),
            $CFG_GLPI['url_base'] . '/index.php?skipMaintenance=1'
        );
        $output->writeln('<info>' . $message . '</info>');

        return 0; // Success
    }
}
