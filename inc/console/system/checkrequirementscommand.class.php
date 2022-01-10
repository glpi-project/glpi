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
use Glpi\System\RequirementsManager;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckRequirementsCommand extends AbstractCommand {

   protected $requires_db = false;

   protected function configure() {
      parent::configure();

      $this->setName('glpi:system:check_requirements');
      $this->setAliases(['system:check_requirements']);
      $this->setDescription(__('Check system requirements'));
   }

   protected function execute(InputInterface $input, OutputInterface $output) {

      $requirements_manager = new RequirementsManager();
      $core_requirements = $requirements_manager->getCoreRequirementList(
         $this->db instanceof \DBmysql && $this->db->connected ? $this->db : null
      );

      $informations = new Table($output);
      $informations->setHeaders(
         [
            __('Requirement'),
            __('Status'),
            __('Messages'),
         ]
      );

      /* @var \Glpi\System\Requirement\RequirementInterface $requirement */
      foreach ($core_requirements as $requirement) {
         if ($requirement->isOutOfContext()) {
            continue; // skip requirement if not relevant
         }

         if ($requirement->isValidated()) {
            $status = sprintf('<%s>[%s]</>', 'fg=black;bg=green', __('OK'));
         } else {
            $status = $requirement->isOptional()
               ? sprintf('<%s>[%s]</> ', 'fg=white;bg=yellow', __('WARNING'))
               : sprintf('<%s>[%s]</> ', 'fg=white;bg=red', __('ERROR'));
         }

         $informations->addRow(
            [
               $requirement->getTitle(),
               $status,
               $requirement->isValidated() ? '' : implode("\n", $requirement->getValidationMessages())
            ]
         );
      }

      $informations->render();

      return 0; // Success
   }

   public function mustCheckMandatoryRequirements(): bool {

      return false;
   }
}
