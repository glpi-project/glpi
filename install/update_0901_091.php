<?php
/*
 * @version $Id: $
 -------------------------------------------------------------------------
 GLPI Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI Gestionnaire Libre de Parc Informatique
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
 * Update from 0.90.1 to 0.91
 *
 * @return bool for success (will die for most error)
**/
function update0901to091() {
   global $DB, $migration;

   $updateresult     = true;
   $ADDTODISPLAYPREF = array();

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '0.91'));
   $migration->setVersion('0.91');


   $backup_tables = false;
   $newtables     = array();

   foreach ($newtables as $new_table) {
      // rename new tables if exists ?
      if (TableExists($new_table)) {
         $migration->dropTable("backup_$new_table");
         $migration->displayWarning("$new_table table already exists. ".
                                    "A backup have been done to backup_$new_table.");
         $backup_tables = true;
         $query         = $migration->renameTable("$new_table", "backup_$new_table");
      }
   }
   if ($backup_tables) {
      $migration->displayWarning("You can delete backup tables if you have no need of them.",
                                 true);
   }

   Config::setConfigurationValues('core', array('set_default_requester' => 1));
   $migration->addField("glpi_users", "set_default_requester", "tinyint(1) NULL DEFAULT NULL");

   // TEMPLATE UPDATE		
   if (isIndex('glpi_tickettemplatepredefinedfields', 'unicity')) {
      $DB->queryOrDie("ALTER TABLE `glpi_tickettemplatepredefinedfields`		
                   DROP KEY `unicity`;", "Associated items migration : alter template predefinedfields unicity");
   }

   // Get associated item searchoption num		
   $searchOption = Search::getOptions('Ticket');
   $item_num     = 0;
   $itemtype_num = 0;
   foreach ($searchOption as $num => $option) {
      if (is_array($option)) {
         if ($option['field'] == 'items_id') {
            $item_num = $num;
         } else if ($option['field'] == 'itemtype') {
            $itemtype_num = $num;
         }
      }
   }

   foreach (array('glpi_tickettemplatepredefinedfields', 'glpi_tickettemplatehiddenfields', 'glpi_tickettemplatemandatoryfields') as $table) {
      $columns = array();
      switch ($table) {
         case 'glpi_tickettemplatepredefinedfields' :
            $columns = array('num', 'value', 'tickettemplates_id');
            break;
         default :
            $columns = array('num', 'tickettemplates_id');
            break;
      }
      $query = "SELECT `".implode('`,`', $columns)."`		
               FROM `$table`		
               WHERE `num` = '$item_num'		
               OR `num` = '$itemtype_num';";

      $items_to_update = array();
      if ($result          = $DB->query($query)) {
         if ($DB->numrows($result) > 0) {
            while ($data = $DB->fetch_assoc($result)) {
               if ($data['num'] == $itemtype_num) {
                  $items_to_update[$data['tickettemplates_id']]['itemtype'] = isset($data['value']) ? $data['value'] : 0;
               } elseif ($data['num'] == $item_num) {
                  $items_to_update[$data['tickettemplates_id']]['items_id'] = isset($data['value']) ? $data['value'] : 0;
               }
            }
         }
      }

      switch ($table) {
         case 'glpi_tickettemplatepredefinedfields' : // Update predefined items		
            foreach ($items_to_update as $templates_id => $type) {
               if (isset($type['itemtype'])) {
                  if (isset($type['items_id'])) {
                     $DB->queryOrDie("UPDATE `$table`		
                                     SET `value` = '".$type['itemtype']."_".$type['items_id']."'		
                                     WHERE `num` = '".$item_num."' 		
                                     AND `tickettemplates_id` = '".$templates_id."';", "Associated items migration : update predefined items");

                     $DB->queryOrDie("DELETE FROM `$table`		
                                     WHERE `num` = '".$itemtype_num."'		
                                     AND `tickettemplates_id` = '".$templates_id."';", "Associated items migration : delete $table itemtypes");
                  }
               }
            }
            break;
         default: // Update mandatory and hidden items		
            foreach ($items_to_update as $templates_id => $type) {
               if (isset($type['itemtype'])) {
                  if (isset($type['items_id'])) {
                     $DB->queryOrDie("DELETE FROM `$table`		
                                        WHERE `num` = '".$item_num."'		
                                        AND `tickettemplates_id` = '".$templates_id."';", "Associated items migration : delete $table itemtypes");
                     $DB->queryOrDie("UPDATE `$table`		
                                        SET `num` = '".$item_num."' 		
                                        WHERE `num` = '".$itemtype_num."'		
                                        AND `tickettemplates_id` = '".$templates_id."';", "Associated items migration : delete $table itemtypes");
                  } else {
                     $DB->queryOrDie("UPDATE `$table`		
                                        SET `num` = '".$item_num."'		
                                        WHERE `num` = '".$itemtype_num."'		
                                        AND `tickettemplates_id` = '".$templates_id."';", "Associated items migration : delete $table itemtypes");
                  }
               }
            }
            break;
      }
   }

   // ************ Keep it at the end **************
   $migration->executeMigration();

   return $updateresult;
}
