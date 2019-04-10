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
 * Update from 0.85 to 0.85.3
 *
 * @return bool for success (will die for most error)
**/
function update085to0853() {
   global $DB, $migration;

   $updateresult     = true;
   $ADDTODISPLAYPREF = [];

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '0.85.3'));
   $migration->setVersion('0.85.3');

   $backup_tables = false;
   $newtables     = [];

   foreach ($newtables as $new_table) {
      // rename new tables if exists ?
      if ($DB->tableExists($new_table)) {
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

   // Increase cron_limit
   $current_config = Config::getConfigurationValues('core');
   if ($current_config['cron_limit'] == 1) {
      Config::setConfigurationValues('core', ['cron_limit' => 5]);
   }
   Config::setConfigurationValues('core', ['task_state' => Planning::TODO]);
   $migration->addField("glpi_users", "task_state", "int(11) DEFAULT NULL");

   $migration->addField('glpi_projecttasks', 'is_milestone', 'bool');
   $migration->addKey('glpi_projecttasks', 'is_milestone');

   // Change Ticket items
   // Add glpi_items_tickets table for associated elements
   if (!$DB->tableExists('glpi_items_tickets')) {
      $query = "CREATE TABLE `glpi_items_tickets` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `itemtype` varchar(255) DEFAULT NULL,
                  `items_id` int(11) NOT NULL DEFAULT '0',
                  `tickets_id` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unicity` (`itemtype`, `items_id`, `tickets_id`),
                  KEY `tickets_id` (`tickets_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.85 add table glpi_items_tickets");

      $query = "SELECT `itemtype`, `items_id`, `id`
                FROM `glpi_tickets`
                WHERE `itemtype` IS NOT NULL
                    AND `itemtype` <> ''
                AND `items_id` != 0";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            while ($data = $DB->fetchAssoc($result)) {
                $query = "INSERT INTO `glpi_items_tickets`
                             (`id`, `items_id`, `itemtype`, `tickets_id`)
                          VALUES (NULL, '".$data['items_id']."', '".$data['itemtype']."', '".$data['id']."')";
                $DB->queryOrDie($query, "0.85 associated ticket sitems migration");
            }

         }
      }
      // Delete old columns and keys
      $migration->dropField("glpi_tickets", "itemtype");
      $migration->dropField("glpi_tickets", "items_id");
      $migration->dropKey("glpi_tickets", "item");

   }

   // correct value of status for changes
   $query = "UPDATE `glpi_changes`
             SET `status` = 1
             WHERE `status` = 2";
   $DB->queryOrDie($query, "0.85.3 correct status for change");

   if ($migration->addField("glpi_entities", "is_notif_enable_default", "integer",
                            ['value' => -2])) {
      $migration->migrationOneTable('glpi_entities');
      // Set directly to root entity
      $query = 'UPDATE `glpi_entities`
                SET `is_notif_enable_default` = 1
                WHERE `id` = 0';
      $DB->queryOrDie($query, "0.85.3 default value for is_notif_enable_default for root entity");
   }

   // ************ Keep it at the end **************
   //TRANS: %s is the table or item to migrate
   $migration->displayMessage(sprintf(__('Data migration - %s'), 'glpi_displaypreferences'));

   foreach ($ADDTODISPLAYPREF as $type => $tab) {
      $query = "SELECT DISTINCT `users_id`
                FROM `glpi_displaypreferences`
                WHERE `itemtype` = '$type'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            while ($data = $DB->fetchAssoc($result)) {
               $query = "SELECT MAX(`rank`)
                         FROM `glpi_displaypreferences`
                         WHERE `users_id` = '".$data['users_id']."'
                               AND `itemtype` = '$type'";
               $result = $DB->query($query);
               $rank   = $DB->result($result, 0, 0);
               $rank++;

               foreach ($tab as $newval) {
                  $query = "SELECT *
                            FROM `glpi_displaypreferences`
                            WHERE `users_id` = '".$data['users_id']."'
                                  AND `num` = '$newval'
                                  AND `itemtype` = '$type'";
                  if ($result2=$DB->query($query)) {
                     if ($DB->numrows($result2)==0) {
                        $query = "INSERT INTO `glpi_displaypreferences`
                                         (`itemtype` ,`num` ,`rank` ,`users_id`)
                                  VALUES ('$type', '$newval', '".$rank++."',
                                          '".$data['users_id']."')";
                        $DB->query($query);
                     }
                  }
               }
            }

         } else { // Add for default user
            $rank = 1;
            foreach ($tab as $newval) {
               $query = "INSERT INTO `glpi_displaypreferences`
                                (`itemtype` ,`num` ,`rank` ,`users_id`)
                         VALUES ('$type', '$newval', '".$rank++."', '0')";
               $DB->query($query);
            }
         }
      }
   }
   // change type of field solution in ticket.change and problem
   $migration->changeField('glpi_tickets', 'solution', 'solution', 'longtext');
   $migration->changeField('glpi_changes', 'solution', 'solution', 'longtext');
   $migration->changeField('glpi_problems', 'solution', 'solution', 'longtext');

   // must always be at the end
   $migration->executeMigration();

   return $updateresult;
}

