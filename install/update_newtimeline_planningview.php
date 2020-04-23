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
 * Update from 9.4.3 to 9.4.5
 *
 * @return bool for success (will die for most error)
 **/
function updateConfigForNewTimeLineView() {
   global $DB, $migration;

    $updateresult = true;

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '9.4.5'));
   $migration->setVersion('9.4.5');
   //TRANS: %s is 'Clean DB : rename tables'
   $migration->displayMessage(sprintf(__('Change of the database config - %s'),
      'Add an option to display the days in new timeline view'));

   $query = "INSERT INTO `glpi_configs` (`glpi_configs`.`context` ,`glpi_configs`.`name`, `glpi_configs`.`value`) VALUES ('core','planning_days','[1,2,3,4,5,6,7]');";

   $DB->queryOrDie($query, "0.78 populate glpi_config");

   // ************ Keep it at the end **************
   $migration->executeMigration();

   return $updateresult;
}
