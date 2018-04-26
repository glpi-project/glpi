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
 * Update from 9.3 to 9.4
 *
 * @return bool for success (will die for most error)
**/
function update93to94() {
   global $DB, $migration, $CFG_GLPI;
   $dbutils = new DbUtils();

   $current_config   = Config::getConfigurationValues('core');
   $updateresult     = true;
   $ADDTODISPLAYPREF = [];

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '9.4'));
   $migration->setVersion('9.4');

   /** Add otherserial field on ConsumableItem */
   if (!$DB->fieldExists('glpi_consumableitems', 'otherserial')) {
      $migration->addField("glpi_consumableitems", "otherserial", "varchar(255) NULL DEFAULT NULL");
      $migration->addKey("glpi_consumableitems", 'otherserial');
   }
   /** /Add otherserial field on ConsumableItem */

   // ************ Keep it at the end **************
   $migration->executeMigration();

   return $updateresult;
}
