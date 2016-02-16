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
   global $DB, $migration, $CFG_GLPI;

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


   /************** Lock Objects ************ */
   if (!TableExists('glpi_objectlocks')) {
      $query = "CREATE TABLE `glpi_objectlocks` (
                 `id` INT(11) NOT NULL AUTO_INCREMENT,
                 `itemtype` VARCHAR(100) NOT NULL COMMENT 'Type of locked object',
                 `items_id` INT(11) NOT NULL COMMENT 'RELATION to various tables, according to itemtype (ID)',
                 `users_id` INT(11) NOT NULL COMMENT 'id of the locker',
                 `date_mod` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Timestamp of the lock',
                 PRIMARY KEY (`id`),
                 UNIQUE INDEX `item` (`itemtype`, `items_id`)
               ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.91 add table glpi_objectlocks");
   }

   // insert new profile
   $query = "INSERT INTO `glpi_profiles`
                    (`name`, `interface`, `is_default`, `helpdesk_hardware`, `helpdesk_item_type`,
                     `ticket_status`, `date_mod`, `comment`, `problem_status`,
                     `create_ticket_on_login`, `tickettemplates_id`, `change_status`)
             VALUES
                    ('Read-Only','central','0','0','[]',
                     '{\"1\":{\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"6\":0},\"2\":{\"1\":0,\"3\":0,\"4\":0,\"5\":0,\"6\":0},\"3\":{\"1\":0,\"2\":0,\"4\":0,\"5\":0,\"6\":0},\"4\":{\"1\":0,\"2\":0,\"3\":0,\"5\":0,\"6\":0},\"5\":{\"1\":0,\"2\":0,\"3\":0,\"4\":0,\"6\":0},\"6\":{\"1\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0}}',
                     NULL,
                     'This profile defines read-only access. It is used when objects are locked. It can also be used to give to users rights to unlock objects.',
                     '{\"1\":{\"7\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"8\":0,\"6\":0},\"7\":{\"1\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"8\":0,\"6\":0},\"2\":{\"1\":0,\"7\":0,\"3\":0,\"4\":0,\"5\":0,\"8\":0,\"6\":0},\"3\":{\"1\":0,\"7\":0,\"2\":0,\"4\":0,\"5\":0,\"8\":0,\"6\":0},\"4\":{\"1\":0,\"7\":0,\"2\":0,\"3\":0,\"5\":0,\"8\":0,\"6\":0},\"5\":{\"1\":0,\"7\":0,\"2\":0,\"3\":0,\"4\":0,\"8\":0,\"6\":0},\"8\":{\"1\":0,\"7\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"6\":0},\"6\":{\"1\":0,\"7\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"8\":0}}',
                     0, 0,
                     '{\"1\":{\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},\"9\":{\"1\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},\"10\":{\"1\":0,\"9\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},\"7\":{\"1\":0,\"9\":0,\"10\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},\"4\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},\"11\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},\"12\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"5\":0,\"8\":0,\"6\":0},\"5\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"8\":0,\"6\":0},\"8\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"6\":0},\"6\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0}}')";

   $DB->queryOrDie($query, "0.91 update profile with Unlock profile") ;
   $ro_p_id = $DB->insert_id();
   $DB->queryOrDie("INSERT INTO `glpi_profilerights`
                     (`profiles_id`, `name`, `rights`) VALUES
                     ($ro_p_id, 'backup',                    '1'),
                     ($ro_p_id, 'bookmark_public',           '1'),
                     ($ro_p_id, 'budget',                    '161'),
                     ($ro_p_id, 'calendar',                  '1'),
                     ($ro_p_id, 'cartridge',                 '161'),
                     ($ro_p_id, 'change',                    '1185'),
                     ($ro_p_id, 'changevalidation',          '0'),
                     ($ro_p_id, 'computer',                  '161'),
                     ($ro_p_id, 'config',                    '1'),
                     ($ro_p_id, 'consumable',                '161'),
                     ($ro_p_id, 'contact_enterprise',        '161'),
                     ($ro_p_id, 'contract',                  '161'),
                     ($ro_p_id, 'device',                    '0'),
                     ($ro_p_id, 'document',                  '161'),
                     ($ro_p_id, 'domain',                    '1'),
                     ($ro_p_id, 'dropdown',                  '1'),
                     ($ro_p_id, 'entity',                    '1185'),
                     ($ro_p_id, 'followup',                  '8193'),
                     ($ro_p_id, 'global_validation',         '0'),
                     ($ro_p_id, 'group',                     '129'),
                     ($ro_p_id, 'infocom',                   '1'),
                     ($ro_p_id, 'internet',                  '129'),
                     ($ro_p_id, 'itilcategory',              '1'),
                     ($ro_p_id, 'knowbase',                  '2177'),
                     ($ro_p_id, 'knowbasecategory',          '1'),
                     ($ro_p_id, 'link',                      '129'),
                     ($ro_p_id, 'location',                  '1'),
                     ($ro_p_id, 'logs',                      '1'),
                     ($ro_p_id, 'monitor',                   '161'),
                     ($ro_p_id, 'netpoint',                  '1'),
                     ($ro_p_id, 'networking',                '161'),
                     ($ro_p_id, 'notification',              '1'),
                     ($ro_p_id, 'password_update',           '0'),
                     ($ro_p_id, 'peripheral',                '161'),
                     ($ro_p_id, 'phone',                     '161'),
                     ($ro_p_id, 'planning',                  '3073'),
                     ($ro_p_id, 'printer',                   '161'),
                     ($ro_p_id, 'problem',                   '1185'),
                     ($ro_p_id, 'profile',                   '129'),
                     ($ro_p_id, 'project',                   '1185'),
                     ($ro_p_id, 'projecttask',               '1'),
                     ($ro_p_id, 'queuedmail',                '1'),
                     ($ro_p_id, 'reminder_public',           '129'),
                     ($ro_p_id, 'reports',                   '1'),
                     ($ro_p_id, 'reservation',               '1'),
                     ($ro_p_id, 'rssfeed_public',            '129'),
                     ($ro_p_id, 'rule_dictionnary_dropdown', '1'),
                     ($ro_p_id, 'rule_dictionnary_printer',  '1'),
                     ($ro_p_id, 'rule_dictionnary_software', '1'),
                     ($ro_p_id, 'rule_import',               '1'),
                     ($ro_p_id, 'rule_ldap',                 '1'),
                     ($ro_p_id, 'rule_mailcollector',        '1'),
                     ($ro_p_id, 'rule_softwarecategories',   '1'),
                     ($ro_p_id, 'rule_ticket',               '1'),
                     ($ro_p_id, 'search_config',             '0'),
                     ($ro_p_id, 'show_group_hardware',       '1'),
                     ($ro_p_id, 'sla',                       '1'),
                     ($ro_p_id, 'software',                  '161'),
                     ($ro_p_id, 'solutiontemplate',          '1'),
                     ($ro_p_id, 'state',                     '1'),
                     ($ro_p_id, 'statistic',                 '1'),
                     ($ro_p_id, 'task',                      '8193'),
                     ($ro_p_id, 'taskcategory',              '1'),
                     ($ro_p_id, 'ticket',                    '7297'),
                     ($ro_p_id, 'ticketcost',                '1'),
                     ($ro_p_id, 'ticketrecurrent',           '1'),
                     ($ro_p_id, 'tickettemplate',            '1'),
                     ($ro_p_id, 'ticketvalidation',          '0'),
                     ($ro_p_id, 'transfer',                  '1'),
                     ($ro_p_id, 'typedoc',                   '1'),
                     ($ro_p_id, 'user',                      '2177')");

   // updates rights for Super-Admin profile
   foreach( $CFG_GLPI['lock_lockable_objects'] as $itemtype ) {
      $rightnames[] = "'".$itemtype::$rightname."'" ;
   }
   $query = "UPDATE `glpi_profilerights`
             SET `rights` = `rights` | ".UNLOCK."
             WHERE `profiles_id` = '4'
                   AND `name` IN (".implode( ",", $rightnames ).")" ;
   $DB->queryOrDie($query, "update super-admin profile with UNLOCK right");

   Config::setConfigurationValues('core', array('lock_use_lock_item'             => 0,
                                                'lock_autolock_mode'             => 1,
                                                'lock_directunlock_notification' => 0,
                                                'lock_item_list'                 => '[]',
                                                'lock_lockprofile_id'            => $ro_p_id));

   // cron task
   if (!countElementsInTable('glpi_crontasks',
                             "`itemtype`='ObjectLock' AND `name`='unlockobject'")) {
      $query = "INSERT INTO `glpi_crontasks`
                       (`itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`,
                        `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`)
                VALUES ('ObjectLock', 'unlockobject', 86400, 4, 0, 1, 3,
                        0, 24, 30, NULL, NULL, NULL); " ;
      $DB->queryOrDie($query, "Update UnlockObject cron task");
   }
   // notification template
   $query = "SELECT *
             FROM `glpi_notificationtemplates`
             WHERE `itemtype` = 'ObjectLock'";

   if ($result = $DB->query($query)) {
      if ($DB->numrows($result) == 0) {
         $query = "INSERT INTO `glpi_notificationtemplates`
                          (`name`, `itemtype`, `date_mod`)
                   VALUES ('Unlock Item request', 'ObjectLock', NOW())";
         $DB->queryOrDie($query, "0.84 add planning recall notification");
         $notid = $DB->insert_id();

         $query = "INSERT INTO `glpi_notificationtemplatetranslations`
                                (`notificationtemplates_id`, `language`,
                                 `subject`,
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
   $migration->addField("glpi_users", "lock_autolock_mode", "bool", true);
   $migration->addField("glpi_users", "lock_directunlock_notification", "bool", true);



   /************** Default Requester ************ */
   Config::setConfigurationValues('core', array('set_default_requester' => 1));
   $migration->addField("glpi_users", "set_default_requester", "tinyint(1) NULL DEFAULT NULL");


   /************** Task's templates ************ */
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
      $DB->queryOrDie($query, "0.91 add table glpi_tasktemplates");
   }


   /************** Installation date for softwares ************ */
   $migration->addField("glpi_computers_softwareversions", "date_install", "DATE");
   $migration->addKey("glpi_computers_softwareversions", "date_install");


   /************** Date mod/creation for itemtypes */
   $migration->displayMessage(sprintf(__('date_mod and date_creation')));
   $types = array('Computer', 'Monitor', 'Printer', 'Phone', 'Software', 'SoftwareVersion',
                  'SoftwareLicense', 'Peripheral', 'NetworkEquipment', 'User', 'Group', 'Entity',
                  'Profile', 'Budget', 'Contact', 'Contract', 'Netpoint', 'NetworkPort', 'Rule',
                  'Cartridge', 'CartridgeItem', 'Consumable', 'ConsumableItem', 'Ticket', 'Problem',
                  'Change', 'Supplier', 'Document', 'AuthLDAP', 'MailCollector', 'Location',
                  'State', 'Manufacturer', 'Blacklist', 'BlacklistedMailContent', 'ITILCategory',
                  'TaskCategory', 'TaskTemplate', 'Project', 'Reminder', 'RSSFeed',
                  'SolutionType', 'RequestType', 'SolutionTemplate', 'ProjectState', 'ProjectType',
                  'ProjectTaskType', 'SoftwareLicenseType', 'CartridgeItemType', 'ConsumableItemType',
                  'ContractType', 'ContactType', 'DeviceMemoryType', 'SupplierType', 'InterfaceType',
                  'DeviceCaseType', 'PhonePowerSupply', 'Filesystem', 'VirtualMachineType',
                  'VirtualMachineSystem', 'VirtualMachineState', 'DocumentCategory', 'DocumentType',
                  'KnowbaseItemCategory', 'Calendar', 'Holiday', 'NetworkEquipmentFirmware',
                  'Network', 'Domain', 'Vlan', 'IPNetwork', 'FQDN', 'WifiNetwork', 'NetworkName',
                  'UserTitle', 'UserCategory', 'RuleRightParameter', 'Fieldblacklist', 'SsoVariable',
                  'NotificationTemplate', 'Notification', 'SLA', 'FieldUnicity', 'Crontask', 'Link',
                  'ComputerDisk', 'ComputerVirtualMachine', 'Infocom');
   $types = array_merge($types, $CFG_GLPI["dictionnary_types"]);
   $types = array_merge($types, $CFG_GLPI["device_types"]);
   $types = array_merge($types, $CFG_GLPI['networkport_instantiations']);

   foreach ($types as $type) {
      $table = getTableForItemType($type);

      if (!FieldExists($table, 'date_mod')) {
         $migration->displayMessage(sprintf(__('Add date_mod to %s'), $table));

         //Add date_mod field if it doesn't exists
         $migration->addField($table, 'date_mod', 'datetime');
         $migration->addKey($table, 'date_mod');
         $migration->migrationOneTable($table);
      }

      if (!FieldExists($table, 'date_creation')) {
         $migration->displayMessage(sprintf(__('Add date_creation to %s'), $table));

         //Add date_creation field
         $migration->addField($table, 'date_creation', 'datetime');
         $migration->addKey($table, 'date_creation');
         $migration->migrationOneTable($table);
      }
   }



   /** ************ New SLA structure ************ */
   if (!TableExists('glpi_slts')) {
      $query = "CREATE TABLE `glpi_slts` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                  `type` int(11) NOT NULL DEFAULT '0',
                  `comment` text COLLATE utf8_unicode_ci,
                  `resolution_time` int(11) NOT NULL,
                  `calendars_id` int(11) NOT NULL DEFAULT '0',
                  `date_mod` datetime DEFAULT NULL,
                  `definition_time` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `end_of_working_day` tinyint(1) NOT NULL DEFAULT '0',
                  `date_creation` datetime DEFAULT NULL,
                  `slas_id` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `calendars_id` (`calendars_id`),
                  KEY `date_mod` (`date_mod`),
                  KEY `date_creation` (`date_creation`),
                  KEY `slas_id` (`slas_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "0.91 add table glpi_slts");

      // Sla migration
      $query = "SELECT *
                FROM `glpi_slas`";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) > 0) {
            while ($data = $DB->fetch_assoc($result)) {
               $query = "INSERT INTO `glpi_slts`
                          (`id`, `name`,`entities_id`, `is_recursive`, `type`, `comment`, `resolution_time`, `calendars_id`, `date_mod`, `definition_time`, `end_of_working_day`, `date_creation`, `slas_id`)
                         VALUES ('".$data['id']."', '".$data['name']."', '".$data['entities_id']."', '".$data['is_recursive']."', '".SLT::RESOLUTION_TYPE."', '".addslashes($data['comment'])."', '".$data['resolution_time']."', '".$data['calendars_id']."', '".$data['date_mod']."', '".$data['definition_time']."', '".$data['end_of_working_day']."', '".date('Y-m-d H:i:s')."', '".$data['id']."');";
               $DB->queryOrDie($query, "SLA migration to SLT");
            }
         }
      }

      // Delete deprecated fields of SLA
      foreach (array('resolution_time', 'calendars_id', 'definition_time', 'end_of_working_day') as $field) {
         $migration->dropField('glpi_slas', $field);
      }

      // Slalevels changes
      $migration->changeField('glpi_slalevels', 'slas_id', 'slts_id', 'integer');

      // Ticket changes
      $migration->changeField("glpi_tickets", "slas_id", "slt_resolution", "integer");
      $migration->addField("glpi_tickets", "slt_takeintoaccount", "integer", array('after' => 'slt_resolution'));
      $migration->addField("glpi_tickets", "limit_takeintoaccount_date", "datetime", array('after' => 'due_date'));
      $migration->addKey('glpi_tickets', 'slt_takeintoaccount');
      $migration->addKey('glpi_tickets', 'limit_takeintoaccount_date');

      // Unique key for slalevel_ticket
      $migration->addKey('glpi_slalevels_tickets', array('tickets_id', 'slalevels_id'), 'unicity', 'UNIQUE');

      // Sla rules criterias migration
      $DB->queryOrDie("UPDATE `glpi_rulecriterias` SET `criteria` = 'slt_resolution' WHERE `criteria` = 'slas_id'", "SLA rulecriterias migration");

      // Sla rules actions migration
      $DB->queryOrDie("UPDATE `glpi_ruleactions` SET `field` = 'slt_resolution' WHERE `field` = 'slas_id'", "SLA ruleactions migration");
   }
   // ************ Keep it at the end **************
   $migration->executeMigration();

   return $updateresult;
}

function setCreationDate() {
   global $DB;


}
?>
