<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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
 * Update from 0.84 to 0.85
 *
 * @return bool for success (will die for most error)
**/
function update084to085() {
   global $DB, $migration;

   $updateresult       = true;
   $ADDTODISPLAYPREF   = array();
   $DELFROMDISPLAYPREF = array();

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '0.85'));
   $migration->setVersion('0.85');

   $backup_tables = false;
   $newtables     = array('glpi_changes', 'glpi_changes_groups', 'glpi_changes_items',
                          'glpi_changes_problems', 'glpi_changes_suppliers',
                          'glpi_changes_tickets', 'glpi_changes_users',
                          'glpi_changetasks'
                          // Only do profilerights once : so not delete it
                          /*, 'glpi_profilerights'*/);

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


   $migration->displayMessage(sprintf(__('Data migration - %s'), 'config table'));

   if (FieldExists('glpi_configs', 'version')) {
      if (!TableExists('origin_glpi_configs')) {
         $migration->copyTable('glpi_configs', 'origin_glpi_configs');
      }

      $query  = "SELECT *
                 FROM `glpi_configs`
                 WHERE `id` = '1'";
      $result_of_configs = $DB->query($query);

      // Update glpi_configs
      $migration->addField('glpi_configs', 'context', 'VARCHAR(150) COLLATE utf8_unicode_ci',
                           array('update' => "'core'"));
      $migration->addField('glpi_configs', 'name', 'VARCHAR(150) COLLATE utf8_unicode_ci',
                           array('update' => "'version'"));
      $migration->addField('glpi_configs', 'value', 'text', array('update' => "'0.85'"));
      $migration->addKey('glpi_configs', array('context', 'name'), 'unicity', 'UNIQUE');

      $migration->migrationOneTable('glpi_configs');

      $fields = array();
      if ($DB->numrows($result_of_configs) == 1) {
         $configs = $DB->fetch_assoc($result_of_configs);
         unset($configs['id']);
         unset($configs['version']);
         // First drop fields not to have constraint on insert
         foreach ($configs as $name => $value) {
            $migration->dropField('glpi_configs', $name);
         }
         $migration->migrationOneTable('glpi_configs');
         // Then insert new values
         foreach ($configs as $name => $value) {
            $query = "INSERT INTO `glpi_configs`
                             (`context`, `name`, `value`)
                      VALUES ('core', '$name', '".addslashes($value)."');";
            $DB->query($query);
         }
      }
      $migration->dropField('glpi_configs', 'version');
      $migration->migrationOneTable('glpi_configs');
      $migration->dropTable('origin_glpi_configs');

   }

   $migration->displayMessage(sprintf(__('Data migration - %s'), 'profile table'));

   if (!TableExists('glpi_profilerights')) {
      if (!TableExists('origin_glpi_profiles')) {
         $migration->copyTable('glpi_profiles', 'origin_glpi_profiles');
      }

      $query = "CREATE TABLE `glpi_profilerights` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `profiles_id` int(11) NOT NULL DEFAULT '0',
                  `name` varchar(255) DEFAULT NULL,
                  `rights` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unicity` (`profiles_id`, `name`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.85 add table glpi_profilerights");

      $query = "DESCRIBE `origin_glpi_profiles`";

      $rights = array();
      foreach ($DB->request($query) as $field) {
         if ($field['Type'] == 'char(1)') {
            $rights[$field['Field']] = $field['Field'];
            $migration->dropField('glpi_profiles', $field['Field']);
         }
      }
      $query = "SELECT *
                FROM `origin_glpi_profiles`";

      foreach ($DB->request($query) as $profile) {
         $profiles_id = $profile['id'];
         foreach ($rights as $right) {
            if ($profile[$right] == NULL) {
               $new_right = 0;
            } else {
               if (($profile[$right] == 'r')
                   || ($profile[$right] == '1')) {
                  $new_right = READ;
               } else if ($profile[$right] == 'w') {
                  $new_right = ALLSTANDARDRIGHT;
               }
            }

            $query = "INSERT INTO `glpi_profilerights`
                             (`profiles_id`, `name`, `rights`)
                      VALUES ('$profiles_id', '$right', '".$new_right."')";
            $DB->query($query);
         }
      }
      $migration->migrationOneTable('glpi_profiles');
      $migration->dropTable('origin_glpi_profiles');

   }

   // New system of profiles

// delete import_externalauth_users
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'import_externalauth_users' AND `rights` = '".ALLSTANDARDRIGHT."'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . User::IMPORTEXTAUTHUSERS ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                       AND `name` = 'User'";
      $DB->queryOrDie($query, "0.85 update user with import_externalauth_users right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'import_externalauth_users'";
   $DB->queryOrDie($query, "0.85 delete import_externalauth_users right");


   // save value of rule_ticket to root_rule_ticket
   $query  = "UPDATE `glpi_profilerights`
              SET `name` = 'root_rule_ticket'
              WHERE `name` = 'rule_ticket'";
   $DB->queryOrDie($query, "0.85 rename rule_ticket to root_rule_ticket");

   // rename entity_rule_ticket to rule_ticket
   $query  = "UPDATE `glpi_profilerights`
              SET `name` = 'rule_ticket'
              WHERE `name` = 'entity_rule_ticket'";
   $DB->queryOrDie($query, "0.85 rename entity_rule_ticket to rule_ticket");

   // delete root_rule_ticket
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'root_rule_ticket' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` =  `rights` | " . RuleTicket::PARENT ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                       AND `name` = 'rule_ticket'";
      $DB->queryOrDie($query, "0.85 update new rule_ticket with old rule_ticket right");
   }

   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'root_rule_ticket'";
   $DB->queryOrDie($query, "0.85 delete old rule_ticket right");


   // delete knowbase_admin
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'knowbase_admin' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . KnowbaseItem::KNOWBASEADMIN ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'knowbase'";
      $DB->queryOrDie($query, "0.85 update knowbase with knowbase_admin right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'knowbase_admin'";
   $DB->queryOrDie($query, "0.85 delete knowbase_admin right");

   // delete faq
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'faq' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . KnowbaseItem::READFAQ ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                       AND `name` = 'knowbase'";
      $DB->queryOrDie($query, "0.85 update knowbase with read faq right");
   }
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'faq' AND `rights` = '".ALLSTANDARDRIGHT."'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . KnowbaseItem::READFAQ ." | ".KnowbaseItem::PUBLISHFAQ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                       AND `name` = 'knowbase'";
      $DB->queryOrDie($query, "0.85 update knowbase with write faq right");
   }

   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'faq'";
   $DB->queryOrDie($query, "0.85 delete faq right");


   // delete user_authtype
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'user_authtype' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . User::READAUTHENT ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                       AND `name` = 'user'";
      $DB->queryOrDie($query, "0.85 update user with read user_authtype right");
   }
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'user_authtype' AND `rights` = '".ALLSTANDARDRIGHT."'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . User::READAUTHENT ." | ".User::UPDATEAUTHENT."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                       AND `name` = 'user'";
         $DB->queryOrDie($query, "0.85 update user with write user_authtype right");
   }

   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'user_authtype'";
   $DB->queryOrDie($query, "0.85 delete user_authtype right");


   // delete entity_helpdesk
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'entity_helpdesk' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . Entity::READHELPDESK ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                       AND `name` = 'entity'";
         $DB->queryOrDie($query, "0.85 update entity with read entity_helpdesk right");
   }
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'entity_helpdesk' AND `rights` = '".ALLSTANDARDRIGHT."'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . Entity::READHELPDESK ." | ".Entity::UPDATEHELPDESK."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                       AND `name` = 'entity'";
         $DB->queryOrDie($query, "0.85 update user with write entity_helpdesk right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'entity_helpdesk'";
   $DB->queryOrDie($query, "0.85 delete entity_helpdesk right");


   // delete reservation_helpdesk
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'reservation_helpdesk' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . ReservationItem::RESERVEANITEM ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'reservation_central'";
         $DB->queryOrDie($query, "0.85 update reservation_central with reservation_helpdesk right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'reservation_helpdesk'";
   $DB->queryOrDie($query, "0.85 delete reservation_helpdesk right");


   // rename reservation_central
   $query  = "UPDATE `glpi_profilerights`
              SET `name` = 'reservation'
              WHERE `name` = 'reservation_central'";
   $DB->queryOrDie($query, "0.85 delete reservation_central");


   // pour que la procédure soit ré-entrante et ne pas perdre les sélections dans le profile
   if (countElementsInTable("glpi_profilerights", "`name` = 'ticket'") == 0) {
      // rename create_ticket
      $query  = "UPDATE `glpi_profilerights`
                 SET `name` = 'ticket'
                 WHERE `name` = 'create_ticket'";
      $DB->queryOrDie($query, "0.85 rename create_ticket to ticket");

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = ". (CREATE | Ticket::READMY)."
                 WHERE `name` = 'ticket'
                       AND `rights` = '1'";
      $DB->queryOrDie($query, "0.85 update ticket with create_ticket right");
   }


   // delete update_ticket
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'update_ticket' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . UPDATE  ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'ticket'";
      $DB->queryOrDie($query, "0.85 update ticket with update_ticket right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'update_ticket'";
   $DB->queryOrDie($query, "0.85 delete update_ticket right");


   // delete delete_ticket
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'delete_ticket' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . DELETE ." | " . PURGE ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'ticket'";
      $DB->queryOrDie($query, "0.85 update ticket with delete_ticket right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'delete_ticket'";
   $DB->queryOrDie($query, "0.85 delete delete_ticket right");


   // delete show_all_ticket
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'show_all_ticket' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . Ticket::READALL ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'ticket'";
      $DB->queryOrDie($query, "0.85 update ticket with show_all_ticket right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'show_all_ticket'";
   $DB->queryOrDie($query, "0.85 delete show_all_ticket right");


   // delete show_group_ticket
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'show_group_ticket' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . Ticket::READGROUP ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'ticket'";
      $DB->queryOrDie($query, "0.85 update ticket with show_group_ticket right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'show_group_ticket'";
   $DB->queryOrDie($query, "0.85 delete show_group_ticket right");


   // delete show_assign_ticket
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'show_assign_ticket' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . Ticket::READASSIGN ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'ticket'";
         $DB->queryOrDie($query, "0.85 update ticket with show_assign_ticket right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'show_assign_ticket'";
   $DB->queryOrDie($query, "0.85 delete show_assign_ticket right");


   // delete assign_ticket
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'assign_ticket' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . Ticket::ASSIGN ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'ticket'";
      $DB->queryOrDie($query, "0.85 update ticket with assign_ticket right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'assign_ticket'";
   $DB->queryOrDie($query, "0.85 delete assign_ticket right");


   // delete steal_ticket
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'steal_ticket' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . Ticket::STEAL ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'ticket'";
      $DB->queryOrDie($query, "0.85 update ticket with steal_ticket right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'steal_ticket'";
   $DB->queryOrDie($query, "0.85 delete steal_ticket right");


   // delete own_ticket
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'own_ticket' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . Ticket::OWN ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'ticket'";
      $DB->queryOrDie($query, "0.85 update ticket with own_ticket right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'own_ticket'";
   $DB->queryOrDie($query, "0.85 delete own_ticket right");


   // must be done after ticket right
   // pour que la procédure soit ré-entrante
   if (countElementsInTable("glpi_profilerights", "`name` = 'change'") == 0) {
      ProfileRight::addProfileRights(array('change'));

      ProfileRight::updateProfileRightAsOtherRight('change', Change::READMY,
                                                   "`name` = 'ticket'
                                                     AND `rights` & ". Ticket::OWN);
      ProfileRight::updateProfileRightAsOtherRight('change', Change::READALL,
                                                   "`name` = 'ticket'
                                                     AND `rights` & ".Ticket::READALL);
      ProfileRight::updateProfileRightAsOtherRight('change',
                                                    CREATE ." | ". UPDATE ." | ". DELETE ." | ". PURGE,
                                                    "`name` = 'ticket' AND `rights` & ".UPDATE);
   }


   // delete update_priority
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'update_priority' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . Ticket::CHANGEPRIORITY ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'ticket'";
         $DB->queryOrDie($query, "0.85 update ticket with update_priority right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'update_priority'";
   $DB->queryOrDie($query, "0.85 delete update_priority right");


   // pour que la procédure soit ré-entrante et ne pas perdre les sélections dans le profile
   if (countElementsInTable("glpi_profilerights", "`name` = 'followup'") == 0) {
      // rename create_ticket
      $query  = "UPDATE `glpi_profilerights`
                 SET `name` = 'followup'
                 WHERE `name` = 'global_add_followups'";
      $DB->queryOrDie($query, "0.85 rename global_add_followups to followup");

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = ". TicketFollowup::ADDALLTICKET ."
                 WHERE `name` = 'followup'
                       AND `rights` = '1'";
      $DB->queryOrDie($query, "0.85 update followup with global_add_followups right");
   }


   // delete add_followups
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'add_followups' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . TicketFollowup::ADDMYTICKET  ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'followup'";
         $DB->queryOrDie($query, "0.85 update followup with add_followups right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'add_followups'";
   $DB->queryOrDie($query, "0.85 delete add_followups right");


   // delete group_add_followups
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'group_add_followups' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . TicketFollowup::ADDGROUPTICKET  ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'followup'";
      $DB->queryOrDie($query, "0.85 update followup with group_add_followups right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'group_add_followups'";
   $DB->queryOrDie($query, "0.85 delete group_add_followups right");


   // delete observe_ticket for followup
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'observe_ticket' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . TicketFollowup::SEEPUBLIC  ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'followup'";
      $DB->queryOrDie($query, "0.85 update followup with observe_ticket right");
   }
    // don't delete observe_ticket because already use for task


   // delete show_full_ticket for followup
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'show_full_ticket' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " .TicketFollowup::SEEPUBLIC ." | ".
                                              TicketFollowup::SEEPRIVATE ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'followup'";
         $DB->queryOrDie($query, "0.85 update followup with show_full_ticket right");
   }
   // don't delete show_full_ticket because already use for task


   // delete update_followups
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'update_followups' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . READ  ." | ". TicketFollowup::UPDATEALL  ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'followup'";
      $DB->queryOrDie($query, "0.85 update followup with update_followups right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'update_followups'";
   $DB->queryOrDie($query, "0.85 delete update_followups right");


   // delete update_own_followups
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'update_own_followups' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . READ  ." | ". TicketFollowup::UPDATEMY  ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'followup'";
      $DB->queryOrDie($query, "0.85 update followup with update_own_followups right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'update_own_followups'";
   $DB->queryOrDie($query, "0.85 delete update_own_followups right");


   // delete delete_followups
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'delete_followups' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . PURGE  ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'followup'";
      $DB->queryOrDie($query, "0.85 update followup with delete_followups right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'delete_followups'";
   $DB->queryOrDie($query, "0.85 delete delete_followups right");


   // pour que la procédure soit ré-entrante et ne pas perdre les sélections dans le profile
   if (countElementsInTable("glpi_profilerights", "`name` = 'task'") == 0) {
      // rename create_ticket
      $query  = "UPDATE `glpi_profilerights`
                 SET `name` = 'task'
                 WHERE `name` = 'global_add_tasks'";
      $DB->queryOrDie($query, "0.85 rename global_add_tasks to task");

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = ". TicketTask::ADDALLTICKET ."
                 WHERE `name` = 'task'
                       AND `rights` = '1'";
      $DB->queryOrDie($query, "0.85 update followup with global_add_tasks right");
   }


   // delete update_tasks
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'update_tasks' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . READ  ." | ". TicketTask::UPDATEALL  ." | " . PURGE ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'task'";
      $DB->queryOrDie($query, "0.85 update task with update_tasks right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'update_tasks'";
   $DB->queryOrDie($query, "0.85 delete update_tasks right");


   // delete observe_ticket for task
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'observe_ticket' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . TicketTask::SEEPUBLIC  ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'task'";
         $DB->queryOrDie($query, "0.85 update task with observe_ticket right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'observe_ticket'";
   $DB->queryOrDie($query, "0.85 delete observe_ticket right");


   // delete show_full_ticket for task
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'show_full_ticket' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " .TicketTask::SEEPUBLIC ." | ".TicketTask::SEEPRIVATE ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'task'";
         $DB->queryOrDie($query, "0.85 update task with show_full_ticket right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'show_full_ticket'";
   $DB->queryOrDie($query, "0.85 delete show_full_ticket right");


   // pour que la procédure soit ré-entrante et ne pas perdre les sélections dans le profile
   if (countElementsInTable("glpi_profilerights", "`name` = 'validation'") == 0) {
      // rename delete_validations
      $query  = "UPDATE `glpi_profilerights`
                 SET `name` = 'validation'
                 WHERE `name` = 'delete_validations'";
      $DB->queryOrDie($query, "0.85 rename delete_validations to validation");

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = ". DELETE ."
                 WHERE `name` = 'validation'
                       AND `rights` = '1'";
      $DB->queryOrDie($query, "0.85 update validation with delete_validations right");
   }


   // delete create_request_validation
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'create_request_validation' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . TicketValidation::CREATEREQUEST ." | ".PURGE."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                       AND `name` = 'validation'";
      $DB->queryOrDie($query, "0.85 update validation with create_request_validation right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'create_request_validation'";
   $DB->queryOrDie($query, "0.85 delete create_request_validation right");

   // delete create_incident_validation
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'create_incident_validation' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . TicketValidation::CREATEINCIDENT ." | ".PURGE."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                       AND `name` = 'validation'";
      $DB->queryOrDie($query, "0.85 update validation with create_incident_validation right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'create_incident_validation'";
   $DB->queryOrDie($query, "0.85 delete create_incident_validation right");

   // delete validate_request
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'validate_request' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . TicketValidation::VALIDATEREQUEST ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                       AND `name` = 'validation'";
      $DB->queryOrDie($query, "0.85 update validation with validate_request right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'validate_request'";
   $DB->queryOrDie($query, "0.85 delete validate_request right");

   // delete validate_incident
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'validate_incident' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . TicketValidation::VALIDATEINCIDENT ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                       AND `name` = 'validation'";
      $DB->queryOrDie($query, "0.85 update validation with validate_incident right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'validate_incident'";
   $DB->queryOrDie($query, "0.85 delete validate_incident right");


   // pour que la procédure soit ré-entrante et ne pas perdre les sélections dans le profile
   if (countElementsInTable("glpi_profilerights", "`name` = 'planning'") == 0) {
      // rename show_planning
      $query  = "UPDATE `glpi_profilerights`
                 SET `name` = 'planning'
                 WHERE `name` = 'show_planning'";
      $DB->queryOrDie($query, "0.85 rename show_planning to planning");

      // READMY = 1 => do update needed
   }

   // delete show_group_planning
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'show_group_planning' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . Planning::READGROUP  ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'planning'";
      $DB->queryOrDie($query, "0.85 update planning with show_group_planning right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'show_group_planning'";
   $DB->queryOrDie($query, "0.85 delete show_group_planning right");


   // delete show_all_planning
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'show_all_planning' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . Planning::READALL  ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'planning'";
      $DB->queryOrDie($query, "0.85 update planning with show_all_planning right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'show_all_planning'";
   $DB->queryOrDie($query, "0.85 delete show_all_planning right");


   // pour que la procédure soit ré-entrante et ne pas perdre les sélections dans le profile
   if (countElementsInTable("glpi_profilerights", "`name` = 'problem'") == 0) {
      // rename show_my_problem
      $query  = "UPDATE `glpi_profilerights`
                 SET `name` = 'problem'
                 WHERE `name` = 'show_my_problem'";
      $DB->queryOrDie($query, "0.85 rename show_my_problem to problem");

      // READMY = 1 => do update needed
   }

   // delete show_all_problem
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'show_all_problem' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . Problem::READALL  ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'problem'";
      $DB->queryOrDie($query, "0.85 update problem with show_all_problem right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'show_all_problem'";
   $DB->queryOrDie($query, "0.85 delete show_all_problem right");


   // delete edit_all_problem
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'edit_all_problem' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . CREATE ." | ". UPDATE . PURGE ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'problem'";
         $DB->queryOrDie($query, "0.85 update problem with edit_all_problem right");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'edit_all_problem'";
   $DB->queryOrDie($query, "0.85 delete edit_all_problem right");


   // delete delete_problem
   foreach ($DB->request("glpi_profilerights",
         "`name` = 'delete_problem' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . DELETE ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'problem'";
      $DB->queryOrDie($query, "0.85 update problem with delete_problem");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'delete_problem'";
   $DB->queryOrDie($query, "0.85 delete problem right");


   // update search_config
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'search_config' AND `rights` = '".ALLSTANDARDRIGHT."'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . DisplayPreference::PERSONAL ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'search_config'";
      $DB->queryOrDie($query, "0.85 update search_config with search_config");
   }


   // delete search_config_global
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'search_config_global' AND `rights` = '".ALLSTANDARDRIGHT."'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . DisplayPreference::GENERAL ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'search_config'";
      $DB->queryOrDie($query, "0.85 update search_config with search_config_global");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'search_config_global'";
   $DB->queryOrDie($query, "0.85 delete search_config_global right");


   // delete check_update
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'check_update' AND `rights` = '1'") as $profrights) {

      $query  = "UPDATE `glpi_profilerights`
                 SET `rights` = `rights` | " . Backup::CHECKUPDATE ."
                 WHERE `profiles_id` = '".$profrights['profiles_id']."'
                      AND `name` = 'backup'";
         $DB->queryOrDie($query, "0.85 update backup with check_update");
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'check_update'";
   $DB->queryOrDie($query, "0.85 delete check_update right");

   // entity_dropdown => right by object

   // pour que la procédure soit ré-entrante et ne pas perdre les sélections dans le profile
   if (countElementsInTable("glpi_profilerights", "`name` = 'domain'") == 0) {
      ProfileRight::addProfileRights(array('domain'));
      ProfileRight::updateProfileRightsAsOtherRights('domain', 'entity_dropdown');
   }

   if (countElementsInTable("glpi_profilerights", "`name` = 'location'") == 0) {
      ProfileRight::addProfileRights(array('location'));
      ProfileRight::updateProfileRightsAsOtherRights('location', 'entity_dropdown');
   }

   if (countElementsInTable("glpi_profilerights", "`name` = 'itilcategory'") == 0) {
      ProfileRight::addProfileRights(array('itilcategory'));
      ProfileRight::updateProfileRightsAsOtherRights('itilcategory', 'entity_dropdown');
   }

   if (countElementsInTable("glpi_profilerights", "`name` = 'knowbasecategory'") == 0) {
      ProfileRight::addProfileRights(array('knowbasecategory'));
      ProfileRight::updateProfileRightsAsOtherRights('knowbasecategory', 'entity_dropdown');
   }

   if (countElementsInTable("glpi_profilerights", "`name` = 'netpoint'") == 0) {
      ProfileRight::addProfileRights(array('netpoint'));
      ProfileRight::updateProfileRightsAsOtherRights('netpoint', 'entity_dropdown');
   }

   if (countElementsInTable("glpi_profilerights", "`name` = 'taskcategory'") == 0) {
      ProfileRight::addProfileRights(array('taskcategory'));
      ProfileRight::updateProfileRightsAsOtherRights('taskcategory', 'entity_dropdown');
   }

   if (countElementsInTable("glpi_profilerights", "`name` = 'state'") == 0) {
      ProfileRight::addProfileRights(array('state'));
      ProfileRight::updateProfileRightsAsOtherRights('state', 'entity_dropdown');
   }

   if (countElementsInTable("glpi_profilerights", "`name` = 'solutiontemplate'") == 0) {
      ProfileRight::addProfileRights(array('solutiontemplate'));
      ProfileRight::updateProfileRightsAsOtherRights('solutiontemplate', 'entity_dropdown');
   }

   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'entity_dropdown'";
   $DB->queryOrDie($query, "0.85 delete entity_dropdown right");


   // delete notes
   $tables = array('budget', 'cartridge', 'change','computer', 'consumable', 'contact_enterprise',
                   'contract', 'document', 'entity', 'monitor', 'networking', 'peripheral',
                   'phone', 'printer', 'problem', 'software');

   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'notes' AND `rights` = '1'") as $profrights) {

      foreach ($tables as $table) {
         $query  = "UPDATE `glpi_profilerights`
                    SET `rights` = `rights` | " . READNOTE ."
                    WHERE `profiles_id` = '".$profrights['profiles_id']."'
                          AND `name` = '$table'";
         $DB->queryOrDie($query, "0.85 update $table with read notes right");
      }
   }
   foreach ($DB->request("glpi_profilerights",
                         "`name` = 'notes' AND `rights` = '".ALLSTANDARDRIGHT."'") as $profrights) {

      foreach ($tables as $table) {
         $query  = "UPDATE `glpi_profilerights`
                    SET `rights` = `rights` | " . READNOTE ." | ".UPDATENOTE ."
                    WHERE `profiles_id` = '".$profrights['profiles_id']."'
                          AND `name` = '$table'";
         $DB->queryOrDie($query, "0.85 update $table with update notes right");
      }
   }
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'notes'";
   $DB->queryOrDie($query, "0.85 delete notes right");

   $DELFROMDISPLAYPREF['Profile'] = array(29, 35, 37, 43, 53, 54, 57, 65, 66, 67, 68, 69, 70, 71,
                                          72, 73, 74, 75, 76, 77, 78, 80, 81, 88, 93, 94, 95, 96,
                                          97, 98, 99, 104, 113, 114, 116, 117, 121, 122, 123);


   $migration->displayTitle('Update for mailqueue');

   if (!TableExists('glpi_queuedmails')) {
      $query = "CREATE TABLE `glpi_queuedmails` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `itemtype` varchar(100) default NULL,
                  `items_id` int(11) NOT NULL DEFAULT '0',
                  `notificationtemplates_id` int(11) NOT NULL DEFAULT '0',
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
                  `sent_try` int(11) NOT NULL DEFAULT '0',
                  `create_time` datetime DEFAULT NULL,
                  `send_time` datetime DEFAULT NULL,
                  `sent_time` datetime DEFAULT NULL,
                  `name` TEXT DEFAULT NULL,
                  `sender` TEXT DEFAULT NULL,
                  `sendername` TEXT DEFAULT NULL,
                  `recipient` TEXT DEFAULT NULL,
                  `recipientname` TEXT DEFAULT NULL,
                  `replyto` TEXT DEFAULT NULL,
                  `replytoname` TEXT DEFAULT NULL,
                  `headers` TEXT DEFAULT NULL,
                  `body_html` LONGTEXT DEFAULT NULL,
                  `body_text` LONGTEXT DEFAULT NULL,
                  `messageid` TEXT DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `item` (`itemtype`,`items_id`, `notificationtemplates_id`),
                  KEY `is_deleted` (`is_deleted`),
                  KEY `entities_id` (`entities_id`),
                  KEY `sent_try` (`sent_try`),
                  KEY `create_time` (`create_time`),
                  KEY `send_time` (`send_time`),
                  KEY `sent_time` (`sent_time`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.85 add glpi_queuedmails");
      $ADDTODISPLAYPREF['QueueMail'] = array(16, 7, 20, 21, 22, 15);
   }

   if (!countElementsInTable('glpi_crontasks',
                             "`itemtype`='QueuedMail' AND `name`='queuedmail'")) {
      $query = "INSERT INTO `glpi_crontasks`
                       (`itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`,
                        `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`)
                VALUES ('QueuedMail', 'queuedmail', 60, 50, 1, 1, 3,
                        0, 24, 30, NULL, NULL, NULL)";
      $DB->queryOrDie($query, "0.85 populate glpi_crontasks for queuemail");
   }

   if (!countElementsInTable('glpi_crontasks',
                             "`itemtype`='QueuedMail' AND `name`='queuedmailclean'")) {
      $query = "INSERT INTO `glpi_crontasks`
                       (`itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`,
                        `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`)
                VALUES ('QueuedMail', 'queuedmailclean', 86400, 30, 1, 1, 3,
                        0, 24, 30, NULL, NULL, NULL)";
      $DB->queryOrDie($query, "0.85 populate glpi_crontasks for queuemail");
   }

   if (!countElementsInTable('glpi_crontasks',
                             "`itemtype`='Crontask' AND `name`='temp'")) {
      $query = "INSERT INTO `glpi_crontasks`
                       (`itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`,
                        `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`)
                VALUES ('Crontask', 'temp', 3600, NULL, 1, 1, 3,
                        0, 24, 30, NULL, NULL, NULL)";
      $DB->queryOrDie($query, "0.85 populate glpi_crontasks for clean temporary files");
   }

   if ($migration->addField("glpi_entities", "delay_send_emails", "integer",
                            array('value' => -2))) {
      $migration->migrationOneTable('glpi_entities');
      // Set directly to root entity
      $query = 'UPDATE `glpi_entities`
                SET `delay_send_emails` = 0
                WHERE `id` = 0';
      $DB->queryOrDie($query, "0.85 default value for delay_send_emails for root entity");
   }

   // pour que la procédure soit ré-entrante
   if (countElementsInTable("glpi_profilerights", "`name` = 'queuedmail'") == 0) {
      ProfileRight::addProfileRights(array('queuedmail'));

      ProfileRight::updateProfileRightsAsOtherRights('queuedmail', 'notification');
   }


   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), 'Change'));

   // changes management
   if (!TableExists('glpi_changes')) {
      $query = "CREATE TABLE `glpi_changes` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) DEFAULT NULL,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
                  `status` int(11) NOT NULL DEFAULT '1',
                  `content` longtext DEFAULT NULL,
                  `date_mod` DATETIME DEFAULT NULL,
                  `date` DATETIME DEFAULT NULL,
                  `solvedate` DATETIME DEFAULT NULL,
                  `closedate` DATETIME DEFAULT NULL,
                  `due_date` DATETIME DEFAULT NULL,
                  `users_id_recipient` int(11) NOT NULL DEFAULT '0',
                  `users_id_lastupdater` int(11) NOT NULL DEFAULT '0',
                  `urgency` int(11) NOT NULL DEFAULT '1',
                  `impact` int(11) NOT NULL DEFAULT '1',
                  `priority` int(11) NOT NULL DEFAULT '1',
                  `itilcategories_id` int(11) NOT NULL DEFAULT '0',
                  `impactcontent` longtext DEFAULT NULL,
                  `controlistcontent` longtext DEFAULT NULL,
                  `rolloutplancontent` longtext DEFAULT NULL,
                  `backoutplancontent` longtext DEFAULT NULL,
                  `checklistcontent` longtext DEFAULT NULL,
                  `solutiontypes_id` int(11) NOT NULL DEFAULT '0',
                  `solution` text COLLATE utf8_unicode_ci,
                  `actiontime` int(11) NOT NULL DEFAULT '0',
                  `notepad` LONGTEXT NULL,
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `is_deleted` (`is_deleted`),
                  KEY `date` (`date`),
                  KEY `closedate` (`closedate`),
                  KEY `status` (`status`),
                  KEY `priority` (`priority`),
                  KEY `date_mod` (`date_mod`),
                  KEY `itilcategories_id` (`itilcategories_id`),
                  KEY `users_id_recipient` (`users_id_recipient`),
                  KEY `solvedate` (`solvedate`),
                  KEY `solutiontypes_id` (`solutiontypes_id`),
                  KEY `urgency` (`urgency`),
                  KEY `impact` (`impact`),
                  KEY `due_date` (`due_date`),
                  KEY `users_id_lastupdater` (`users_id_lastupdater`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.85 create glpi_changes");
   }

   if (!TableExists('glpi_changes_users')) {
      $query = "CREATE TABLE `glpi_changes_users` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `changes_id` int(11) NOT NULL DEFAULT '0',
                  `users_id` int(11) NOT NULL DEFAULT '0',
                  `type` int(11) NOT NULL DEFAULT '1',
                  `use_notification` tinyint(1) NOT NULL DEFAULT '0',
                  `alternative_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unicity` (`changes_id`,`type`,`users_id`,`alternative_email`),
                  KEY `user` (`users_id`,`type`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.85 add table glpi_changes_users");
   }

   if (!TableExists('glpi_changes_groups')) {
      $query = "CREATE TABLE `glpi_changes_groups` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `changes_id` int(11) NOT NULL DEFAULT '0',
                  `groups_id` int(11) NOT NULL DEFAULT '0',
                  `type` int(11) NOT NULL DEFAULT '1',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unicity` (`changes_id`,`type`,`groups_id`),
                  KEY `group` (`groups_id`,`type`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.85 add table glpi_changes_groups");
   }

   if (!TableExists('glpi_changes_suppliers')) {
      $query = "CREATE TABLE `glpi_changes_suppliers` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `changes_id` int(11) NOT NULL DEFAULT '0',
                  `suppliers_id` int(11) NOT NULL DEFAULT '0',
                  `type` int(11) NOT NULL DEFAULT '1',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unicity` (`changes_id`,`type`,`suppliers_id`),
                  KEY `group` (`suppliers_id`,`type`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.85 add table glpi_changes_suppliers");
   }

   if (!TableExists('glpi_changes_items')) {
      $query = "CREATE TABLE `glpi_changes_items` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `changes_id` int(11) NOT NULL DEFAULT '0',
                  `itemtype` varchar(100) default NULL,
                  `items_id` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unicity` (`changes_id`,`itemtype`,`items_id`),
                  KEY `item` (`itemtype`,`items_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.85 add table glpi_changes_items");
   }

   if (!TableExists('glpi_changes_tickets')) {
      $query = "CREATE TABLE `glpi_changes_tickets` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `changes_id` int(11) NOT NULL DEFAULT '0',
                  `tickets_id` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unicity` (`changes_id`,`tickets_id`),
                  KEY `tickets_id` (`tickets_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.85 add table glpi_changes_tickets");
   }

   if (!TableExists('glpi_changes_problems')) {
      $query = "CREATE TABLE `glpi_changes_problems` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `changes_id` int(11) NOT NULL DEFAULT '0',
                  `problems_id` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unicity` (`changes_id`,`problems_id`),
                  KEY `problems_id` (`problems_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.85 add table glpi_changes_problems");
   }

   if (!TableExists('glpi_changetasks')) {
      $query = "CREATE TABLE `glpi_changetasks` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) DEFAULT NULL,
                  `changes_id` int(11) NOT NULL DEFAULT '0',
                  `changetasks_id` int(11) NOT NULL DEFAULT '0',
                  `is_blocked` tinyint(1) NOT NULL DEFAULT '0',
                  `taskcategories_id` int(11) NOT NULL DEFAULT '0',
                  `status` varchar(255) DEFAULT NULL,
                  `priority` int(11) NOT NULL DEFAULT '1',
                  `percentdone` int(11) NOT NULL DEFAULT '0',
                  `date` datetime DEFAULT NULL,
                  `begin` datetime DEFAULT NULL,
                  `end` datetime DEFAULT NULL,
                  `users_id` int(11) NOT NULL DEFAULT '0',
                  `users_id_tech` int(11) NOT NULL DEFAULT '0',
                  `content` longtext COLLATE utf8_unicode_ci,
                  `actiontime` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `changes_id` (`changes_id`),
                  KEY `changetasks_id` (`changetasks_id`),
                  KEY `is_blocked` (`is_blocked`),
                  KEY `priority` (`priority`),
                  KEY `status` (`status`),
                  KEY `percentdone` (`percentdone`),
                  KEY `users_id` (`users_id`),
                  KEY `users_id_tech` (`users_id_tech`),
                  KEY `date` (`date`),
                  KEY `begin` (`begin`),
                  KEY `end` (`end`),
                  KEY `taskcategories_id` (taskcategories_id)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.85 add table glpi_changetasks");
   }

   $migration->addField('glpi_tickets', 'validation_percent', 'integer', array('value' => 0));

   /// TODO add changetasktypes table as dropdown
   /// TODO review users linked to changetask
   /// TODO add display prefs

   $migration->addField('glpi_profiles', 'change_status', "text",
                        array('comment' => "json encoded array of from/dest allowed status change"));


   $migration->displayMessage(sprintf(__('Data migration - %s'), 'drop rules cache'));
   $migration->dropTable('glpi_rulecachecomputermodels');
   $migration->dropTable('glpi_rulecachecomputertypes');
   $migration->dropTable('glpi_rulecachemanufacturers');
   $migration->dropTable('glpi_rulecachemonitormodels');
   $migration->dropTable('glpi_rulecachemonitortypes');
   $migration->dropTable('glpi_rulecachenetworkequipmentmodels');
   $migration->dropTable('glpi_rulecachenetworkequipmenttypes');
   $migration->dropTable('glpi_rulecacheoperatingsystems');
   $migration->dropTable('glpi_rulecacheoperatingsystemservicepacks');
   $migration->dropTable('glpi_rulecacheoperatingsystemversions');
   $migration->dropTable('glpi_rulecacheperipheralmodels');
   $migration->dropTable('glpi_rulecacheperipheraltypes');
   $migration->dropTable('glpi_rulecachephonemodels');
   $migration->dropTable('glpi_rulecachephonetypes');
   $migration->dropTable('glpi_rulecacheprintermodels');
   $migration->dropTable('glpi_rulecacheprinters');
   $migration->dropTable('glpi_rulecacheprintertypes');
   $migration->dropTable('glpi_rulecachesoftwares');

   $migration->displayMessage(sprintf(__('Data migration - %s'), 'glpi_rules'));

   $migration->addField("glpi_rules", 'uuid', "string");
   $migration->migrationOneTable('glpi_rules');

   // Dropdown translations
   $migration->displayMessage(sprintf(__('Data migration - %s'), 'glpi_knowbaseitemtranslations'));
   Config::setConfigurationValues('core', array('translate_kb' => 0));
   if (!TableExists("glpi_knowbaseitemtranslations")) {
      $query = "CREATE TABLE IF NOT EXISTS `glpi_knowbaseitemtranslations` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `knowbaseitems_id` int(11) NOT NULL DEFAULT '0',
                  `language` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `name` text COLLATE utf8_unicode_ci,
                  `answer` longtext COLLATE utf8_unicode_ci,
                  PRIMARY KEY (`id`),
                  KEY `item` (`knowbaseitems_id`, `language`),
                  FULLTEXT KEY `fulltext` (`name`,`answer`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "0.85 add table glpi_knowbaseitemtranslations");
   }

   // kb translations
   $migration->displayMessage(sprintf(__('Data migration - %s'), 'glpi_dropdowntranslations'));
   Config::setConfigurationValues('core', array('translate_dropdowns' => 0));
   if (!TableExists("glpi_dropdowntranslations")) {
      $query = "CREATE TABLE IF NOT EXISTS `glpi_dropdowntranslations` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `items_id` int(11) NOT NULL DEFAULT '0',
                  `itemtype` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `language` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `field` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `value` text COLLATE utf8_unicode_ci,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unicity` (`itemtype`,`items_id`,`language`,`field`),
                  KEY `typeid` (`itemtype`,`items_id`),
                  KEY `language` (`language`),
                  KEY `field` (`field`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

      $DB->queryOrDie($query, "0.85 add table glpi_dropdowntranslations");
   }


   //generate uuid for the basic rules of glpi
   // we use a complete sql where for cover all migration case (0.78 -> 0.85)
   $rules = array(array('sub_type'    => 'RuleImportEntity',
                        'name'        => 'Root',
                        'match'       => 'AND',
                        'description' => ''),

                  array('sub_type'    => 'RuleRight',
                        'name'        => 'Root',
                        'match'       => 'AND',
                        'description' => ''),

                  array('sub_type'    => 'RuleMailCollector',
                        'name'        => 'Root',
                        'match'       => 'AND',
                        'description' => ''),

                  array('sub_type'    => 'RuleMailCollector',
                        'name'        => 'Auto-Reply X-Auto-Response-Suppress',
                        'match'       => 'AND',
                        'description' => 'Exclude Auto-Reply emails using X-Auto-Response-Suppress header'),

                  array('sub_type'    => 'RuleMailCollector',
                        'name'        => 'Auto-Reply Auto-Submitted',
                        'match'       => 'AND',
                        'description' => 'Exclude Auto-Reply emails using Auto-Submitted header'),

                  array('sub_type'    => 'RuleTicket',
                        'name'        => 'Ticket location from item',
                        'match'       => 'AND',
                        'description' => ''),

                  array('sub_type'    => 'RuleTicket',
                        'name'        => 'Ticket location from user',
                        'match'       => 'AND',
                        'description' => ''));

   $i = 0;
   foreach ($rules as $rule) {
      $query  = "UPDATE `glpi_rules`
                 SET `uuid` = 'STATIC-UUID-$i'
                 WHERE `entities_id` = 0
                       AND `is_recursive` = 0
                       AND `sub_type` = '".$rule['sub_type']."'
                       AND `name` = '".$rule['name']."'
                       AND `description` = '".$rule['description']."'
                       AND `match` = '".$rule['match']."'
                 ORDER BY id ASC
                 LIMIT 1";
      $DB->queryOrDie($query, "0.85 add uuid to basic rules (STATIC-UUID-$i)");
      $i++;
   }

   //generate uuid for the rules of user
   foreach ($DB->request('glpi_rules', array('uuid' => NULL)) as $data) {
      $uuid  = Rule::getUuid();
      $query = "UPDATE `glpi_rules`
                SET `uuid` = '$uuid'
                WHERE `id` = '".$data['id']."'";
      $DB->queryOrDie($query, "0.85 add uuid to existing rules");
   }

   $migration->addField('glpi_users', 'is_deleted_ldap', 'bool');
   $migration->addKey('glpi_users', 'is_deleted_ldap');

   Config::deleteConfigurationValues('core', array('use_ajax'));
   Config::deleteConfigurationValues('core', array('ajax_min_textsearch_load'));
   Config::deleteConfigurationValues('core', array('ajax_buffertime_load'));

   Config::deleteConfigurationValues('core', array('is_categorized_soft_expanded'));
   Config::deleteConfigurationValues('core', array('is_not_categorized_soft_expanded'));
   $migration->dropField("glpi_users", 'is_categorized_soft_expanded');
   $migration->dropField("glpi_users", 'is_not_categorized_soft_expanded');

// Config::setConfigurationValues('core', array('use_unicodefont' => 0));
// $migration->addField("glpi_users", 'use_unicodefont', "int(11) DEFAULT NULL");
   Config::deleteConfigurationValues('core', array('use_unicodefont'));
   $migration->dropField("glpi_users", 'use_unicodefont');
   Config::setConfigurationValues('core', array('pdffont' => 'helvetica'));
   $migration->addField("glpi_users", 'pdffont', "string");


   $migration->addField("glpi_users", 'picture', "string");

   $migration->addField("glpi_authldaps", 'picture_field','string');

   $migration->addField('glpi_links', 'open_window', 'bool', array('value' => 1));

   $migration->displayMessage(sprintf(__('Data migration - %s'), 'glpi_states'));
   foreach (array('is_visible_computer', 'is_visible_monitor', 'is_visible_networkequipment',
                  'is_visible_peripheral', 'is_visible_phone', 'is_visible_printer',
                  'is_visible_softwareversion') as $field)  {
      $migration->addField('glpi_states', $field, 'bool',
                           array('value' => '1'));
      $migration->addKey('glpi_states', $field);
   }


   // glpi_domains by entity
   $migration->addField('glpi_domains', 'entities_id', 'integer', array('after' => 'name'));
   $migration->addField('glpi_domains', 'is_recursive', 'bool', array('update' => '1',
                                                                      'after'  => 'entities_id'));

   // glpi_states by entity
   $migration->addField('glpi_states', 'entities_id', 'integer', array('after' => 'name'));
   $migration->addField('glpi_states', 'is_recursive', 'bool', array('update' => '1',
                                                                     'after'  => 'entities_id'));


   // add validity date for a user
   $migration->addField('glpi_users', 'begin_date', 'datetime');
   $migration->addField('glpi_users', 'end_date', 'datetime');

   // Create notification for reply to satisfaction survey based on satisfaction notif
   // Check if notifications already exists
   if (countElementsInTable('glpi_notifications',
                            "`itemtype` = 'Ticket'
                              AND `event` = 'replysatisfaction'")==0) {
   // No notifications duplicate all

      $query = "SELECT *
                FROM `glpi_notifications`
                WHERE `itemtype` = 'Ticket'
                      AND `event` = 'satisfaction'";
      foreach ($DB->request($query) as $notif) {
         $query = "INSERT INTO `glpi_notifications`
                          (`name`, `entities_id`, `itemtype`, `event`, `mode`,
                          `notificationtemplates_id`, `comment`, `is_recursive`, `is_active`,
                          `date_mod`)
                   VALUES ('".addslashes($notif['name'])." Answer',
                           '".$notif['entities_id']."', 'Ticket',
                           'replysatisfaction', '".$notif['mode']."',
                           '".$notif['notificationtemplates_id']."',
                           '".addslashes($notif['comment'])."', '".$notif['is_recursive']."',
                           '".$notif['is_active']."', NOW());";
         $DB->queryOrDie($query, "0.85 insert replysatisfaction notification");
         $newID  = $DB->insert_id();
         $query2 = "SELECT *
                    FROM `glpi_notificationtargets`
                    WHERE `notifications_id` = '".$notif['id']."'";
         // Add same recipent of satisfaction
         foreach ($DB->request($query2) as $target) {
            $query = "INSERT INTO `glpi_notificationtargets`
                             (`notifications_id`, `type`, `items_id`)
                      VALUES ($newID, '".$target['type']."', '".$target['items_id']."')";
            $DB->queryOrDie($query, "0.85 insert targets for replysatisfaction notification");
         }
         // Add Tech in charge
            $query = "INSERT INTO `glpi_notificationtargets`
                             (`notifications_id`, `type`, `items_id`)
                      VALUES ($newID, '".Notification::USER_TYPE."', '".Notification::ASSIGN_TECH."')";
            $DB->queryOrDie($query, "0.85 insert tech in charge target for replysatisfaction notification");
      }
   }

   // * Convert SLA resolution time to new system (ticket #4346)
   if (!FieldExists("glpi_slas", "definition_time")) {
      $migration->addField("glpi_slas", 'definition_time', "string");
      $migration->addField("glpi_slas", 'end_of_working_day', "bool");
      $migration->migrationOneTable('glpi_slas');

      // Minutes
      $query = "SELECT *
                FROM `glpi_slas`
                WHERE `resolution_time` <= '3000'";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $a_ids = array();
            while ($data = $DB->fetch_assoc($result)) {
               $a_ids[] = $data['id'];
            }
            $DB->query("UPDATE `glpi_slas`
                        SET `definition_time` = 'minute',
                            `resolution_time` = `resolution_time`/60
                        WHERE `id` IN (".implode(",", $a_ids).")");
         }
      }
      // Hours
      $query = "SELECT *
                FROM `glpi_slas`
                WHERE `resolution_time` > '3000'
                      AND `resolution_time` <= '82800'";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $a_ids = array();
            while ($data = $DB->fetch_assoc($result)) {
               $a_ids[] = $data['id'];
            }
            $DB->query("UPDATE `glpi_slas`
                        SET `definition_time` = 'hour',
                            `resolution_time` = `resolution_time`/3600
                        WHERE `id` IN (".implode(",", $a_ids).")");
         }
      }
      // Days
      $query = "SELECT *
                FROM `glpi_slas`
                WHERE `resolution_time` > '82800'";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $a_ids = array();
            while ($data = $DB->fetch_assoc($result)) {
               $a_ids[] = $data['id'];
            }
            $DB->query("UPDATE `glpi_slas`
                        SET `definition_time` = 'day',
                            `resolution_time` = `resolution_time`/86400
                        WHERE `id` IN (".implode(",", $a_ids).")");
         }
      }
   }

   Config::setConfigurationValues('core', array('keep_devices_when_purging_item' => 0));
   $migration->addField("glpi_users", "keep_devices_when_purging_item", "tinyint(1) DEFAULT NULL");

   Config::setConfigurationValues('core', array('maintenance_mode' => 0));
   Config::setConfigurationValues('core', array('maintenance_text' => ''));

   $query = "SELECT *
             FROM `glpi_notificationtemplates`
             WHERE `itemtype` = 'MailCollector'";

   if ($result=$DB->query($query)) {
      if ($DB->numrows($result)==0) {
         $query = "INSERT INTO `glpi_notificationtemplates`
                          (`name`, `itemtype`, `date_mod`)
                   VALUES ('Receiver errors', 'MailCollector', NOW())";
         $DB->queryOrDie($query, "0.85 add mail collector notification");
         $notid = $DB->insert_id();

         $query = "INSERT INTO `glpi_notificationtemplatetranslations`
                          (`notificationtemplates_id`, `language`, `subject`,
                           `content_text`,
                           `content_html`)
                   VALUES ($notid, '', '##mailcollector.action##',
                           '##FOREACHmailcollectors##
##lang.mailcollector.name## : ##mailcollector.name##
##lang.mailcollector.errors## : ##mailcollector.errors##
##mailcollector.url##
##ENDFOREACHmailcollectors##',
'&lt;p&gt;##FOREACHmailcollectors##&lt;br /&gt;##lang.mailcollector.name## : ##mailcollector.name##&lt;br /&gt; ##lang.mailcollector.errors## : ##mailcollector.errors##&lt;br /&gt;&lt;a href=\"##mailcollector.url##\"&gt;##mailcollector.url##&lt;/a&gt;&lt;br /&gt; ##ENDFOREACHmailcollectors##&lt;/p&gt;
&lt;p&gt;&lt;/p&gt;')";
         $DB->queryOrDie($query, "0.85 add mail collector notification translation");


         $query = "INSERT INTO `glpi_notifications`
                          (`name`, `entities_id`, `itemtype`, `event`, `mode`,
                           `notificationtemplates_id`, `comment`, `is_recursive`, `is_active`,
                           `date_mod`)
                   VALUES ('Receiver errors', 0, 'MailCollector', 'error', 'mail',
                             $notid, '', 1, 1, NOW())";
         $DB->queryOrDie($query, "0.85 add mail collector notification");
         $notifid = $DB->insert_id();

         $query = "INSERT INTO `glpi_notificationtargets`
                          (`id`, `notifications_id`, `type`, `items_id`)
                   VALUES (NULL, $notifid, ".Notification::USER_TYPE.", ".Notification::GLOBAL_ADMINISTRATOR.");";
         $DB->queryOrDie($query, "0.85 add mail collector notification target");
      }
   }

   if (!countElementsInTable('glpi_crontasks',
                             "`itemtype`='MailCollector' AND `name`='mailgateerror'")) {
      $query = "INSERT INTO `glpi_crontasks`
                       (`itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`,
                        `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`)
                VALUES ('MailCollector', 'mailgateerror', ".DAY_TIMESTAMP.", NULL, 1, 1, 3,
                        0, 24, 30, NULL, NULL, NULL)";
      $DB->queryOrDie($query, "0.85 populate glpi_crontasks for mailgateerror");
   }
   if (!countElementsInTable('glpi_crontasks',
                             "`itemtype`='Crontask' AND `name`='circularlogs'")) {
      $query = "INSERT INTO `glpi_crontasks`
                       (`itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`,
                        `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`)
                VALUES ('Crontask', 'circularlogs', ".DAY_TIMESTAMP.", 4, ".CronTask::STATE_DISABLE.", 1, 3,
                        0, 24, 30, NULL, NULL, NULL)";
      $DB->queryOrDie($query, "0.85 populate glpi_crontasks for circularlogs");
   }

   $migration->addField('glpi_documents', 'is_blacklisted', 'bool');

   if (!TableExists("glpi_blacklistedmailcontents")) {
      $query = "CREATE TABLE IF NOT EXISTS `glpi_blacklistedmailcontents` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) DEFAULT NULL,
                  `content` text COLLATE utf8_unicode_ci,
                  `comment` text COLLATE utf8_unicode_ci,
                  PRIMARY KEY (`id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "0.85 add table glpi_blacklistedmailcontents");
   }

   $migration->addField('glpi_documents', 'tag', 'string');
   $migration->addField('glpi_queuedmails', 'documents', 'text');
   $migration->addKey('glpi_documents', 'tag');
   Config::setConfigurationValues('core', array('use_rich_text' => 0));
   Config::setConfigurationValues('core', array('attach_ticket_documents_to_mail' => 0));

   // increase password length
   $migration->changeField('glpi_users', 'password', 'password', 'string');

   // Hierarchical software category
   $migration->addField('glpi_softwarecategories', 'softwarecategories_id', 'integer');
   $migration->addField("glpi_softwarecategories", 'completename', "text");
   $migration->addField("glpi_softwarecategories", 'level', "integer");
   $migration->addField("glpi_softwarecategories", 'ancestors_cache', "longtext");
   $migration->addField("glpi_softwarecategories", 'sons_cache', "longtext");
   $migration->migrationOneTable('glpi_softwarecategories');
   $migration->addKey('glpi_softwarecategories', 'softwarecategories_id');
   regenerateTreeCompleteName("glpi_softwarecategories");

   // glpi_cartridgeitems  glpi_consumableitems by entity
   $migration->addField('glpi_consumableitems', 'is_recursive', 'bool',
                         array('update' => '1',
                               'after'  => 'entities_id'));
   $migration->addField('glpi_cartridgeitems', 'is_recursive', 'bool',
                        array('update' => '1',
                              'after'  => 'entities_id'));
   // Fix events
   $query = "UPDATE `glpi_events`
             SET `type` = 'consumableitems'
             WHERE `type` = 'consumables'";
   $DB->queryOrDie($query, "0.85 fix events for consumables");

   $query = "UPDATE `glpi_events`
             SET `type` = 'cartridgeitems'
             WHERE `type` = 'cartridges';";
   $DB->queryOrDie($query, "0.85 fix events for cartridges");


   // Bookmark order :
   $migration->addField('glpi_users', 'privatebookmarkorder', 'longtext');

   // Pref to comme back ticket created
   if ($migration->addField('glpi_users', 'backcreated', 'TINYINT(1) DEFAULT NULL')) {
      $query = "INSERT INTO `glpi_configs`
                       (`context`, `name`, `value`)
                VALUES ('core', 'backcreated', 0)";
      $DB->queryOrDie($query, "update glpi_configs with backcreated");
   }


   // ************ Keep it at the end **************
   //TRANS: %s is the table or item to migrate
   $migration->displayMessage(sprintf(__('Data migration - %s'), 'glpi_displaypreferences'));

   $migration->updateDisplayPrefs($ADDTODISPLAYPREF, $DELFROMDISPLAYPREF);

   // must always be at the end
   $migration->executeMigration();

   return $updateresult;
}

?>