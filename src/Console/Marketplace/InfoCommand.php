<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\Console\Marketplace;

use GLPINetwork;
use Glpi\Marketplace\Controller;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InfoCommand extends AbstractMarketplaceCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('glpi:marketplace:info');
        $this->setAliases(['marketplace:info']);
        $this->setDescription(__('Get information about a plugin'));

        $this->addArgument('plugin', InputArgument::REQUIRED, __('The plugin key'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!GLPINetwork::isRegistered()) {
            $output->writeln("<error>" . __("The GLPI Network registration key is missing or invalid") . "</error>");
        }

        $plugin = $input->getArgument('plugin');

        $controller = new Controller();
        $plugins = $controller::getAPI()->getAllPlugins();

        $result = array_filter($plugins, static function ($p) use ($plugin) {
            return strtolower($p['key']) === strtolower($plugin);
        });

        if (count($result) === 0) {
            $output->writeln('<error>' . __('Plugin not found') . '</error>');
            return 1;
        }

        $result = reset($result);
        $output->write(var_export($result, true));

        return 0; // Success
    }

    protected function getPluginChoiceQuestion(): string
    {
        return __('Which plugin do you want information on?');
    }
}
