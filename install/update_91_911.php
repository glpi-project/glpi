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

/**
 * Update from 9.1 to 9.1.1
 *
 * @return bool for success (will die for most error)
**/
function update91to911() {
   global $DB, $migration;

   $updateresult = true;

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '9.1.1'));
   $migration->setVersion('9.1.1');

   // rectify missing right in 9.1 update
   if (countElementsInTable("glpi_profilerights", ['name' => 'license']) == 0) {
      foreach ($DB->request("glpi_profilerights", ["name" => 'software']) as $profrights) {
         $DB->insertOrDie("glpi_profilerights", [
               'id'           => null,
               'profiles_id'  => $profrights['profiles_id'],
               'name'         => "license",
               'rights'       => $profrights['rights']
            ],
            "9.1 add right for softwarelicense"
         );
      }
   }

   // ************ Keep it at the end **************
   $migration->executeMigration();

   return $updateresult;
}
