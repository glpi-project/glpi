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

/// Update from 0.72.2 to 0.72.3

function update0722to0723() {
   global $DB, $CFG_GLPI;

   //TRANS: %s is the number of new version
   echo "<h3>".sprintf(__('Update to %s'), '0.72.3')."</h3>";
   displayMigrationMessage("0723"); // Start

   //// Correct search.constant numbers : problem in previous update
   $updates = [];
   // serial / otherserial
   $updates[] = ['type'  => STATE_TYPE,
                      'from'  => 9,
                      'to'    => 6];

   $updates[] = ['type'  => STATE_TYPE,
                      'from'  => 8,
                      'to'    => 5];

   foreach ($updates as $data) {
      $query = "UPDATE `glpi_display`
                SET `num` = ".$data['to']."
                WHERE `num` = ".$data['from']."
                     AND `type` = '".$data['type']."'";
      $DB->queryOrDie($query, "0.72.3 reorder search.constant");
   }

   $LINK_ID_TABLE = [1  => "glpi_computers",
                          2  => "glpi_networking",
                          3  => "glpi_printers",
                          4  => "glpi_monitors",
                          5  => "glpi_peripherals",
                          6  => "glpi_software",
                          7  => "glpi_contacts",
                          8  => "glpi_enterprises",
                          9  => "glpi_infocoms",
                          10 => "glpi_contracts",
                          11 => "glpi_cartridges_type",
                          12 => "glpi_type_docs",
                          13 => "glpi_docs",
                          14 => "glpi_kbitems",
                          15 => "glpi_users",
                          16 => "glpi_tracking",
                          17 => "glpi_consumables_type",
                          18 => "glpi_consumables",
                          19 => "glpi_cartridges",
                          20 => "glpi_softwarelicenses",
                          21 => "glpi_links",
                          23 => "glpi_phones",
                          25 => "glpi_reminder",
                          27 => "glpi_groups",
                          28 => "glpi_entities",
                          29 => "glpi_reservation_item",
                          32 => "glpi_ocs_config",
                          33 => "glpi_registry",
                          34 => "glpi_profiles",
                          35 => "glpi_mailgate",
                          36 => "glpi_rules_descriptions",
                          37 => "glpi_transfers",
                          39 => "glpi_softwareversions",
                          41 => "glpi_computerdisks",
                          42 => "glpi_networking_ports",
                          43 => "glpi_followups"];

   $query = "SELECT DISTINCT `device_type`
             FROM `glpi_doc_device`";
   if ($result = $DB->query($query)) {
      if ($DB->numrows($result)>0) {
         while ($data = $DB->fetchAssoc($result)) {
            if (isset($LINK_ID_TABLE[$data['device_type']])) {
               $table = $LINK_ID_TABLE[$data['device_type']];

               $query2 = "DELETE
                          FROM `glpi_doc_device`
                          WHERE `device_type` = ".$data['device_type']."
                                AND `FK_device` NOT IN (SELECT `ID`
                                                        FROM `$table`)";
               $DB->queryOrDie($query2, "0.72.3 clean doc_device table");
            }
         }
      }
   }

   if ($DB->fieldExists("glpi_auth_ldap", "ldap_group_condition", false)) {
      $query = "ALTER TABLE `glpi_auth_ldap`
                CHANGE `ldap_group_condition` `ldap_group_condition` TEXT NULL DEFAULT NULL";
      $DB->queryOrDie($query, "0.72.3 alter ldap_group_condition in glpi_auth_ldap");
   }

   // Display "Work ended." message - Keep this as the last action.
   displayMigrationMessage("0723"); // End
} // fin 0.72.3
