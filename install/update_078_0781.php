<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

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

/**
 * Update from 0.78 to 0.78.1
 *
 * @return bool for success (will die for most error)
 */
function update078to0781() {
   global $DB, $migration;

   $updateresult = true;

   $migration->displayTitle(sprintf(__('Update to %s'), '0.78.1'));
   $migration->setVersion('0.78.1');

   //TRANS: %s is 'Clean reservation entity link'
   $migration->displayMessage(sprintf(__('Data migration - %s'),
                                      'Clean reservation entity link')); // Updating schema

   $entities=getAllDatasFromTable('glpi_entities');
   $entities[0]="Root";

   $query = "SELECT DISTINCT `itemtype` FROM `glpi_reservationitems`";
   if ($result=$DB->query($query)) {
      if ($DB->numrows($result)>0) {
         while ($data = $DB->fetch_assoc($result)) {
            $itemtable=getTableForItemType($data['itemtype']);
            // ajout d'un contrôle pour voir si la table existe ( cas migration plugin non fait)
            if (!TableExists($itemtable)) {
               $migration->displayWarning("*** Skip : no table $itemtable ***", true);
               continue;
            }
            $do_recursive = false;
            if (FieldExists($itemtable,'is_recursive', false)) {
               $do_recursive=true;
            }
            foreach ($entities as $entID => $val) {
               if ($do_recursive) {
                  // Non recursive ones
                  $query3="UPDATE `glpi_reservationitems`
                           SET `entities_id`=$entID, `is_recursive`=0
                           WHERE `itemtype`='".$data['itemtype']."'
                              AND `items_id` IN (SELECT `id` FROM `$itemtable`
                              WHERE `entities_id`=$entID AND `is_recursive`=0)";
                  $DB->queryOrDie($query3, "0.78.1 update entities_id and is_recursive=0 in glpi_reservationitems for ".$data['itemtype']);

                  // Recursive ones
                  $query3="UPDATE `glpi_reservationitems`
                           SET `entities_id`=$entID, `is_recursive`=1
                           WHERE `itemtype`='".$data['itemtype']."'
                              AND `items_id` IN (SELECT `id` FROM `$itemtable`
                              WHERE `entities_id`=$entID AND `is_recursive`=1)";
                  $DB->queryOrDie($query3, "0.78.1 update entities_id and is_recursive=1 in glpi_reservationitems for ".$data['itemtype']);
               } else {
                  $query3="UPDATE `glpi_reservationitems`
                           SET `entities_id`=$entID
                           WHERE `itemtype`='".$data['itemtype']."'
                              AND `items_id` IN (SELECT `id` FROM `$itemtable`
                              WHERE `entities_id`=$entID)";
                  $DB->queryOrDie($query3, "0.78.1 update entities_id in glpi_reservationitems for ".$data['itemtype']);
               }
            }
         }
      }
   }

   $query = "ALTER TABLE `glpi_tickets`
             CHANGE `global_validation` `global_validation` VARCHAR(255) DEFAULT 'none'";
   $DB->query($query) or die("0.78.1 change ticket global_validation default state");

   $query = "UPDATE `glpi_tickets`
             SET `global_validation`='none'
             WHERE `id` NOT IN (SELECT DISTINCT `tickets_id`
                                FROM `glpi_ticketvalidations`)";
   $DB->query($query) or die("0.78.1 update ticket global_validation state");


   if (!FieldExists('glpi_knowbaseitemcategories','entities_id', false)) {
      $query = "ALTER TABLE `glpi_knowbaseitemcategories`
                    ADD `entities_id` INT NOT NULL DEFAULT '0' AFTER `id`,
                    ADD `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `entities_id`,
                    ADD INDEX `entities_id` (`entities_id`),ADD INDEX `is_recursive` (`is_recursive`)";
      $DB->queryOrDie($query, "0.78.1 add entities_id,is_recursive in glpi_knowbaseitemcategories");

      // Set existing categories recursive global
      $query = "UPDATE `glpi_knowbaseitemcategories` SET `is_recursive` = '1'";
      $DB->queryOrDie($query, "0.78.1 set value of is_recursive in glpi_knowbaseitemcategories");

      $query = "ALTER TABLE `glpi_knowbaseitemcategories` DROP INDEX `unicity` ,
               ADD UNIQUE `unicity` ( `entities_id`, `knowbaseitemcategories_id` , `name` ) ";
      $DB->queryOrDie($query, "0.78.1 update unicity index on glpi_knowbaseitemcategories");
   }

   // must always be at the end (only for end message)
   $migration->executeMigration();

   return $updateresult;
}
?>
