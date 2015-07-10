<?php


/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief 
*/

/// Update from 0.72 to 0.72.1

function update072to0721() {
   global $DB, $CFG_GLPI;

   //TRANS: %s is the number of new version
   echo "<h3>".sprintf(__('Update to %s'), '0.72.1')."</h3>";
   displayMigrationMessage("0721"); // Start

   if (!isIndex("glpi_groups", "ldap_group_dn")) {
      $query = "ALTER TABLE `glpi_groups` ADD INDEX `ldap_group_dn` ( `ldap_group_dn` );";
      $DB->query($query, "0.72.1 add index on ldap_group_dn in glpi_groups");
   }

   if (!isIndex("glpi_groups", "ldap_value")) {
      $query = "ALTER TABLE `glpi_groups` ADD INDEX `ldap_value`  ( `ldap_value` );";
      $DB->query($query, "0.72.1 add index on ldap_value in glpi_groups");
   }

   if (!isIndex('glpi_tracking', 'date_mod')) {
      $query=" ALTER TABLE `glpi_tracking` ADD INDEX `date_mod` (`date_mod`)  ";
      $DB->query($query, "0.72.1 add date_mod index in glpi_tracking");
   }

   // Display "Work ended." message - Keep this as the last action.
   displayMigrationMessage("0721"); // End
} // fin 0.72.1 #####################################################################################
?>
