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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EnableMaintenanceModeCommand extends AbstractCommand {

   protected function configure() {
      parent::configure();

      $this->setName('glpi:maintenance:enable_maintenance_mode');
      $this->setAliases(
         [
            'glpi:maintenance:on',
            'maintenance:on',
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

   protected function execute(InputInterface $input, OutputInterface $output) {

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
