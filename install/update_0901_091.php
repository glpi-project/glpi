<?php
/*
 * @version $Id: $
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
   $newtables     = array('glpi_objectlocks');

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

   $migration->displayMessage(sprintf(__('Add of - %s to database'), 'Object Locks'));

   // create table
   if (!TableExists('glpi_objectlocks')) {
      $query = "CREATE TABLE `glpi_objectlocks` (
                 `id` INT(11) NOT NULL AUTO_INCREMENT,
                 `itemtype` VARCHAR(100) NOT NULL COMMENT 'Type of locked object',
                 `items_id` INT(11) NOT NULL COMMENT 'RELATION to various tables, according to itemtype (ID)',
                 `users_id` INT(11) NOT NULL COMMENT 'id of the locker',
                 `date_mod` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Timestamp of the lock',
                 PRIMARY KEY (`id`),
                 UNIQUE INDEX `item` (`itemtype`, `items_id`)
               ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

      $DB->queryOrDie($query, "0.91 add table glpi_objectlocks");
   }

   // insert new profile
   $query = "INSERT INTO `glpi_profiles` 
         (`name`, `interface`, `is_default`, `helpdesk_hardware`, `helpdesk_item_type`, `ticket_status`, `date_mod`, `comment`, `problem_status`, `create_ticket_on_login`, `tickettemplates_id`, `change_status`) 
         VALUES 
         ('Read-Only','central','0','0','[]','{\"1\":{\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"6\":0},\"2\":{\"1\":0,\"3\":0,\"4\":0,\"5\":0,\"6\":0},\"3\":{\"1\":0,\"2\":0,\"4\":0,\"5\":0,\"6\":0},\"4\":{\"1\":0,\"2\":0,\"3\":0,\"5\":0,\"6\":0},\"5\":{\"1\":0,\"2\":0,\"3\":0,\"4\":0,\"6\":0},\"6\":{\"1\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0}}', NULL, 'This profile defines read-only access. It is used when objects are locked. It can also be used to give to users rights to unlock objects.', '{\"1\":{\"7\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"8\":0,\"6\":0},\"7\":{\"1\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"8\":0,\"6\":0},\"2\":{\"1\":0,\"7\":0,\"3\":0,\"4\":0,\"5\":0,\"8\":0,\"6\":0},\"3\":{\"1\":0,\"7\":0,\"2\":0,\"4\":0,\"5\":0,\"8\":0,\"6\":0},\"4\":{\"1\":0,\"7\":0,\"2\":0,\"3\":0,\"5\":0,\"8\":0,\"6\":0},\"5\":{\"1\":0,\"7\":0,\"2\":0,\"3\":0,\"4\":0,\"8\":0,\"6\":0},\"8\":{\"1\":0,\"7\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"6\":0},\"6\":{\"1\":0,\"7\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"8\":0}}', 0, 0, '{\"1\":{\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},\"9\":{\"1\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},\"10\":{\"1\":0,\"9\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},\"7\":{\"1\":0,\"9\":0,\"10\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},\"4\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},\"11\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},\"12\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"5\":0,\"8\":0,\"6\":0},\"5\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"8\":0,\"6\":0},\"8\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"6\":0},\"6\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0}}'); 
         ";
   $DB->query( $query ) or die( "0.91 Can't add default Unlock profile" ) ;
   $ret = $DB->insert_id();

   // insert default rights for this profile
   ProfileRight::updateProfileRights($ret,
                  array( 'backup' => '1',
                           'bookmark_public' => '1',
                           'budget' => '161',
                           'calendar' => '1',
                           'cartridge' => '161',
                           'change' => '1185',
                           'changevalidation' => '0',
                           'computer' => '161',
                           'config' => '1',
                           'consumable' => '161',
                           'contact_enterprise' => '161',
                           'contract' => '161',
                           'device' => '0',
                           'document' => '161',
                           'domain' => '1',
                           'dropdown' => '1',
                           'entity' => '1185',
                           'followup' => '8193',
                           'global_validation' => '0',
                           'group' => '129',
                           'infocom' => '1',
                           'internet' => '129',
                           'itilcategory' => '1',
                           'knowbase' => '2177',
                           'knowbasecategory' => '1',
                           'link' => '129',
                           'location' => '1',
                           'logs' => '1',
                           'monitor' => '161',
                           'netpoint' => '1',
                           'networking' => '161',
                           'notification' => '1',
                           'password_update' => '0',
                           'peripheral' => '161',
                           'phone' => '161',
                           'planning' => '3073',
                           'printer' => '161',
                           'problem' => '1185',
                           'profile' => '129',
                           'project' => '1185',
                           'projecttask' => '1',
                           'queuedmail' => '1',
                           'reminder_public' => '129',
                           'reports' => '1',
                           'reservation' => '1',
                           'rssfeed_public' => '129',
                           'rule_dictionnary_dropdown' => '1',
                           'rule_dictionnary_printer' => '1',
                           'rule_dictionnary_software' => '1',
                           'rule_import' => '1',
                           'rule_ldap' => '1',
                           'rule_mailcollector' => '1',
                           'rule_softwarecategories' => '1',
                           'rule_ticket' => '1',
                           'search_config' => '0',
                           'show_group_hardware' => '1',
                           'sla' => '1',
                           'software' => '161',
                           'solutiontemplate' => '1',
                           'state' => '1',
                           'statistic' => '1',
                           'task' => '8193',
                           'taskcategory' => '1',
                           'ticket' => '7297',
                           'ticketcost' => '1',
                           'ticketrecurrent' => '1',
                           'tickettemplate' => '1',
                           'ticketvalidation' => '0',
                           'transfer' => '1',
                           'typedoc' => '1',
                           'user' => '2177') ) ;


   Config::setConfigurationValues('core', array( 'lock_use_lock_item' => 0,
                                             'lock_autolock_mode'   => 1,
                                             'lock_directunlock_notification' => 0,
                                             'lock_item_list' => '[]',
                                             'lock_lockprofile_id' => $ret));

   // cron task
   if (!countElementsInTable('glpi_crontasks',
                             "`itemtype`='ObjectLock' AND `name`='unlockobject'")) {
      $query = "INSERT INTO `glpi_crontasks` ( `itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`, `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`) 
                  VALUES ('ObjectLock', 'unlockobject', 86400, 4, 0, 1, 3, 0, 24, 30, NULL, NULL, NULL); " ;
      $DB->query( $query ) or die( "0.91 Can't add UnlockObject cron task" ) ;
   }
   // notification template
   $query = "SELECT *
             FROM `glpi_notificationtemplates`
             WHERE `itemtype` = 'ObjectLock'";

   if ($result=$DB->query($query)) {
      if ($DB->numrows($result)==0) {
         $query = "INSERT INTO `glpi_notificationtemplates` ( `name`, `itemtype`, `date_mod`) 
               VALUES ('Unlock Item request', 'ObjectLock', NOW() ); " ;
         $DB->queryOrDie($query, "0.84 add planning recall notification");
         $notid = $DB->insert_id();

         $query = "INSERT INTO `glpi_notificationtemplatetranslations`
                                (`notificationtemplates_id`, `language`, `subject`,
                                 `content_text`,
                                 `content_html`)
                         VALUES ($notid, '', '##objectlock.action##', 
      '##objectlock.type## ###objectlock.id## - ##objectlock.name##

      ##lang.objectlock.url##
      ##objectlock.url##

      ##lang.objectlock.date_mod##
      ##objectlock.date_mod##

      Hello ##objectlock.lockedby.firstname##,
      Could go to this item and unlock it for me?
      Thank you,
      Regards,
      ##objectlock.requester.firstname##',
      '&lt;table&gt;
      &lt;tbody&gt;
      &lt;tr&gt;&lt;th colspan=\"2\"&gt;&lt;a href=\"##objectlock.url##\"&gt;##objectlock.type## ###objectlock.id## - ##objectlock.name##&lt;/a&gt;&lt;/th&gt;&lt;/tr&gt;
      &lt;tr&gt;
      &lt;td&gt;##lang.objectlock.url##&lt;/td&gt;
      &lt;td&gt;##objectlock.url##&lt;/td&gt;
      &lt;/tr&gt;
      &lt;tr&gt;
      &lt;td&gt;##lang.objectlock.date_mod##&lt;/td&gt;
      &lt;td&gt;##objectlock.date_mod##&lt;/td&gt;
      &lt;/tr&gt;
      &lt;/tbody&gt;
      &lt;/table&gt;
      &lt;p&gt;&lt;span style=\"font-size: small;\"&gt;Hello ##objectlock.lockedby.firstname##,&lt;br /&gt;Could go to this item and unlock it for me?&lt;br /&gt;Thank you,&lt;br /&gt;Regards,&lt;br /&gt;##objectlock.requester.firstname## ##objectlock.requester.lastname##&lt;/span&gt;&lt;/p&gt;')";
         
         $DB->queryOrDie($query, "0.91 add Unlock Request notification translation");

         $query = "INSERT INTO `glpi_notifications`
                                (`name`, `entities_id`, `itemtype`, `event`, `mode`,
                                 `notificationtemplates_id`, `comment`, `is_recursive`, `is_active`,
                                 `date_mod`)
                         VALUES ('Request Unlock Items', 0, 'ObjectLock', 'unlock', 'mail',
                                   $notid, '', 1, 1, NOW())";
         $DB->queryOrDie($query, "0.91 add Unlock Request notification");
         $notifid = $DB->insert_id();

         $query = "INSERT INTO `glpi_notificationtargets`
                                (`id`, `notifications_id`, `type`, `items_id`)
                         VALUES (NULL, $notifid, ".Notification::USER_TYPE.", ".Notification::USER.");";
         $DB->queryOrDie($query, "0.91 add Unlock Request notification target");
      }
   }
   Config::setConfigurationValues('core', array('set_default_requester' => 1));
   $migration->addField("glpi_users", "set_default_requester", "tinyint(1) NULL DEFAULT NULL");

   // Add task template
   if (!TableExists('glpi_tasktemplates')) {
      $query = "CREATE TABLE `glpi_tasktemplates` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `content` text COLLATE utf8_unicode_ci,
                  `taskcategories_id` int(11) NOT NULL DEFAULT '0',
                  `actiontime` int(11) NOT NULL DEFAULT '0',
                  `comment` text COLLATE utf8_unicode_ci,
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `taskcategories_id` (`taskcategories_id`),
                  KEY `entities_id` (`entities_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "0.84 add table glpi_tasktemplates");
   }

   $migration->addField("glpi_users", "lock_autolock_mode", "tinyint(1) NULL DEFAULT NULL");
   $migration->addField("glpi_users", "lock_directunlock_notification", "tinyint(1) NULL DEFAULT NULL");


   // ************ Keep it at the end **************
   $migration->executeMigration();

   return $updateresult;
}
?>
