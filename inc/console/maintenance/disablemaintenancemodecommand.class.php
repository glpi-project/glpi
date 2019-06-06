<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

namespace Glpi\Console\Maintenance;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Config;
use Glpi\Console\AbstractCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DisableMaintenanceModeCommand extends AbstractCommand {

   protected function configure() {
      parent::configure();

      $this->setName('glpi:maintenance:disable_maintenance_mode');
      $this->setAliases(
         [
            'glpi:maintenance:off',
            'maintenance:off',
         ]
      );
      $this->setDescription(__('Disable maintenance mode'));
   }

   protected function execute(InputInterface $input, OutputInterface $output) {

      $config = new Config();
      $config->setConfigurationValues('core', ['maintenance_mode' => '0']);

      $output->writeln('<info>' . __('Maintenance mode disabled.') . '</info>');

      return 0; // Success
   }
}
