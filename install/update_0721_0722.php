<?php


/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

/** @file
* @brief
*/

/// Update from 0.72.1 to 0.72.2

function update0721to0722() {
   global $DB, $CFG_GLPI;

   //TRANS: %s is the number of new version
   echo "<h3>".sprintf(__('Update to %s'), '0.72.2')."</h3>";
   displayMigrationMessage("0722"); // Start

   // Delete state from reservation search
   $query = "DELETE
             FROM `glpi_display`
             WHERE `type` = ".RESERVATION_TYPE."
                   AND `num` = 31";
   $DB->queryOrDie($query, "0.72.2 delete search of state from reservations");

   // Clean licences alerts
   $query = "DELETE
             FROM `glpi_alerts`
             WHERE `device_type` = '".SOFTWARELICENSE_TYPE."'";
   $DB->queryOrDie($query, "0.72.2 delete search of state from reservations");

   //// Correct search.constant numbers
   $updates = [];
   // location :
   $updates[] = ['type'  => CARTRIDGEITEM_TYPE,
                      'from'  => 3,
                      'to'    => 34];

   $updates[] = ['type'  => CARTRIDGEITEM_TYPE,
                      'from'  => 6,
                      'to'    => 3];

   $updates[] = ['type'  => CONSUMABLEITEM_TYPE,
                      'from'  => 3,
                      'to'    => 34];

   $updates[] = ['type'  => CONSUMABLEITEM_TYPE,
                      'from'  => 6,
                      'to'    => 3];

   $updates[] = ['type'  => USER_TYPE,
                      'from'  => 3,
                      'to'    => 34];

   $updates[] = ['type'  => USER_TYPE,
                      'from'  => 7,
                      'to'    => 3];

   // serial / otherserial
   $updates[] = ['type'  => COMPUTER_TYPE,
                      'from'  => 40,
                      'to'    => 46];

   $updates[] = ['type'  => COMPUTER_TYPE,
                      'from'  => 5,
                      'to'    => 40];

   $updates[] = ['type'  => COMPUTER_TYPE,
                      'from'  => 8,
                      'to'    => 5];

   $updates[] = ['type'  => COMPUTER_TYPE,
                      'from'  => 6,
                      'to'    => 45];

   $updates[] = ['type'  => COMPUTER_TYPE,
                      'from'  => 9,
                      'to'    => 6];

   $updates[] = ['type'  => STATE_TYPE,
                      'from'  => 9,
                      'to'    => 6];

   $updates[] = ['type'  => STATE_TYPE,
                      'from'  => 8,
                      'to'    => 5];

   // Manufacturer
   $updates[] = ['type'  => CONSUMABLEITEM_TYPE,
                      'from'  => 5,
                      'to'    => 23];

   $updates[] = ['type'  => CARTRIDGEITEM_TYPE,
                      'from'  => 5,
                      'to'    => 23];

   // tech_num
   $updates[] = ['type'  => CONSUMABLEITEM_TYPE,
                      'from'  => 7,
                      'to'    => 24];

   $updates[] = ['type'  => CARTRIDGEITEM_TYPE,
                      'from'  => 7,
                      'to'    => 24];

   // date_mod
   $updates[] = ['type'  => NETWORKING_TYPE,
                      'from'  => 9,
                      'to'    => 19];

   $updates[] = ['type'  => PRINTER_TYPE,
                      'from'  => 9,
                      'to'    => 19];

   $updates[] = ['type'  => MONITOR_TYPE,
                      'from'  => 9,
                      'to'    => 19];

   $updates[] = ['type'  => PERIPHERAL_TYPE,
                      'from'  => 9,
                      'to'    => 19];

   $updates[] = ['type'  => SOFTWARE_TYPE,
                      'from'  => 9,
                      'to'    => 19];

   $updates[] = ['type'  => PHONE_TYPE,
                      'from'  => 9,
                      'to'    => 19];

   // comments
   $updates[] = ['type'  => NETWORKING_TYPE,
                      'from'  => 10,
                      'to'    => 16];

   $updates[] = ['type'  => PRINTER_TYPE,
                      'from'  => 10,
                      'to'    => 16];

   $updates[] = ['type'  => MONITOR_TYPE,
                      'from'  => 10,
                      'to'    => 16];

   $updates[] = ['type'  => PERIPHERAL_TYPE,
                      'from'  => 10,
                      'to'    => 16];

   $updates[] = ['type'  => SOFTWARE_TYPE,
                      'from'  => 6,
                      'to'    => 16];

   $updates[] = ['type'  => CONTACT_TYPE,
                      'from'  => 7,
                      'to'    => 16];

   $updates[] = ['type'  => ENTERPRISE_TYPE,
                      'from'  => 7,
                      'to'    => 16];

   $updates[] = ['type'  => CARTRIDGEITEM_TYPE,
                      'from'  => 10,
                      'to'    => 16];

   $updates[] = ['type'  => DOCUMENT_TYPE,
                      'from'  => 6,
                      'to'    => 16];

   $updates[] = ['type'  => USER_TYPE,
                      'from'  => 12,
                      'to'    => 16];

   $updates[] = ['type'  => PHONE_TYPE,
                      'from'  => 10,
                      'to'    => 16];

   foreach ($updates as $data) {
      $query = "UPDATE `glpi_display`
                SET `num` = ".$data['to']."
                WHERE `num` = ".$data['from']."
                      AND `type` = '".$data['type']."'";
      $DB->queryOrDie($query, "0.72.2 reorder search.constant");
   }

   // Display "Work ended." message - Keep this as the last action.
   displayMigrationMessage("0722"); // End
} // fin 0.72.2
