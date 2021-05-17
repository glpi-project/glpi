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

/**
 * Update from 9.5.5 to 9.5.6
 *
 * @return bool for success (will die for most error)
 **/
function update955to956() {
   /** @global Migration $migration */
   global $DB, $migration, $CFG_GLPI;

   $current_config   = Config::getConfigurationValues('core');
   $updateresult     = true;
   $ADDTODISPLAYPREF = [];

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '9.5.6'));
   $migration->setVersion('9.5.6');

   //put you migration script here

   // Change DC itemtype template_name search option ID from 50 to 61 to prevent duplicate IDs now that those itemtypes have Infocom search options.
   $migration->changeSearchOption(Enclosure::class, 50, 61);
   $migration->changeSearchOption(PassiveDCEquipment::class, 50, 61);
   $migration->changeSearchOption(PDU::class, 50, 61);
   $migration->changeSearchOption(Rack::class, 50, 61);

   // ************ Keep it at the end **************
   $migration->executeMigration();

   return $updateresult;
}
