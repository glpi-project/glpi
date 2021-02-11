<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

namespace Glpi\Console\System;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Glpi\Console\AbstractCommand;
use Glpi\System\Status\StatusChecker;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListServiceCommand extends AbstractCommand {

   protected function configure() {
      parent::configure();

      $this->setName('glpi:system:service:list');
      $this->setAliases(['system:service:list']);
      $this->setDescription(__('List system services'));
      $this->addOption('format', 'f', InputOption::VALUE_OPTIONAL,
         'Output format [plain or json]', 'plain');
   }

   protected function execute(InputInterface $input, OutputInterface $output) {

      $format = strtolower($input->getOption('format'));

      $services = array_keys(StatusChecker::getServices());


      if ($format === 'json') {
         $output->writeln(json_encode($services, JSON_PRETTY_PRINT));
      } else {
         foreach ($services as $service) {
            $output->writeln($service);
         }
      }

      return 0; // Success
   }
}
