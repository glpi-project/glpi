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

namespace Glpi\Console\System;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Glpi\Console\AbstractCommand;
use Glpi\System\Status\StatusChecker;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckStatusCommand extends AbstractCommand {

   protected function configure() {
      parent::configure();

      $this->setName('glpi:system:status');
      $this->setAliases(['system:status']);
      $this->setDescription(__('Check system status'));
      $this->addOption('format', 'f', InputOption::VALUE_OPTIONAL,
         'Output format [plain or json]', 'plain');
      $this->addOption('private', 'p', InputOption::VALUE_NONE,
         'Status information publicity. Private status information may contain potentially sensitive information such as version information.');
   }

   protected function execute(InputInterface $input, OutputInterface $output) {

      $format = strtolower($input->getOption('format'));
      $status = StatusChecker::getFullStatus(!$input->getOption('private'), $format === 'json');

      if ($format === 'json') {
         $output->writeln(json_encode($status, JSON_PRETTY_PRINT));
      } else {
         $output->writeln($status);
      }

      return 0; // Success
   }
}
