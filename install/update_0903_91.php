<?php
/*
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
 * Update from 0.90.3 to 9.1
 *
 * @return bool for success (will die for most error)
**/
function update0903to91() {
   global $DB, $migration, $CFG_GLPI;

   $current_config   = Config::getConfigurationValues('core');
   $updateresult     = true;
   $ADDTODISPLAYPREF = array();

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '9.1'));
   $migration->setVersion('9.1');


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


   /************** Lock Objects *************/
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
      $DB->queryOrDie($query, "9.1 add table glpi_objectlocks");

      // insert new profile (read only access for locks)
      $query = "INSERT INTO `glpi_profiles`
                       (`name`, `interface`, `is_default`, `helpdesk_hardware`, `helpdesk_item_type`,
                        `ticket_status`, `date_mod`, `comment`, `problem_status`,
                        `create_ticket_on_login`, `tickettemplates_id`, `change_status`)
                VALUES ('Read-Only','central','0','0','[]',
                        '{\"1\":{\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"6\":0},\"2\":{\"1\":0,\"3\":0,\"4\":0,\"5\":0,\"6\":0},\"3\":{\"1\":0,\"2\":0,\"4\":0,\"5\":0,\"6\":0},\"4\":{\"1\":0,\"2\":0,\"3\":0,\"5\":0,\"6\":0},\"5\":{\"1\":0,\"2\":0,\"3\":0,\"4\":0,\"6\":0},\"6\":{\"1\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0}}',
                        NULL,
                        'This profile defines read-only access. It is used when objects are locked. It can also be used to give to users rights to unlock objects.',
                        '{\"1\":{\"7\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"8\":0,\"6\":0},\"7\":{\"1\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"8\":0,\"6\":0},\"2\":{\"1\":0,\"7\":0,\"3\":0,\"4\":0,\"5\":0,\"8\":0,\"6\":0},\"3\":{\"1\":0,\"7\":0,\"2\":0,\"4\":0,\"5\":0,\"8\":0,\"6\":0},\"4\":{\"1\":0,\"7\":0,\"2\":0,\"3\":0,\"5\":0,\"8\":0,\"6\":0},\"5\":{\"1\":0,\"7\":0,\"2\":0,\"3\":0,\"4\":0,\"8\":0,\"6\":0},\"8\":{\"1\":0,\"7\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"6\":0},\"6\":{\"1\":0,\"7\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"8\":0}}',
                        0, 0,
                        '{\"1\":{\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},\"9\":{\"1\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},\"10\":{\"1\":0,\"9\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},\"7\":{\"1\":0,\"9\":0,\"10\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},\"4\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},\"11\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},\"12\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"5\":0,\"8\":0,\"6\":0},\"5\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"8\":0,\"6\":0},\"8\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"6\":0},\"6\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0}}')";

      $DB->queryOrDie($query, "9.1 update profile with Unlock profile") ;
      $ro_p_id = $DB->insert_id();
      $DB->queryOrDie("INSERT INTO `glpi_profilerights`
                              (`profiles_id`, `name`, `rights`)
                       VALUES ($ro_p_id, 'backup',                    '1'),
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
   }

   // cron task
   if (!countElementsInTable('glpi_crontasks',
                             "`itemtype`='ObjectLock' AND `name`='unlockobject'")) {
      $query = "INSERT INTO `glpi_crontasks`
                       (`itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`,
                        `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`)
                VALUES ('ObjectLock', 'unlockobject', 86400, 4, 0, 1, 3,
                        0, 24, 30, NULL, NULL, NULL); " ;
      $DB->queryOrDie($query, "9.1 Add UnlockObject cron task");
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
         $DB->queryOrDie($query, "9.1 Add unlock request notification template");
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

         $DB->queryOrDie($query, "9.1 add Unlock Request notification translation");

         $query = "INSERT INTO `glpi_notifications`
                                (`name`, `entities_id`, `itemtype`, `event`, `mode`,
                                 `notificationtemplates_id`, `comment`, `is_recursive`, `is_active`,
                                 `date_mod`)
                         VALUES ('Request Unlock Items', 0, 'ObjectLock', 'unlock', 'mail',
                                   $notid, '', 1, 1, NOW())";
         $DB->queryOrDie($query, "9.1 add Unlock Request notification");
         $notifid = $DB->insert_id();

         $query = "INSERT INTO `glpi_notificationtargets`
                                (`id`, `notifications_id`, `type`, `items_id`)
                         VALUES (NULL, $notifid, ".Notification::USER_TYPE.", ".Notification::USER.");";
         $DB->queryOrDie($query, "9.1 add Unlock Request notification target");
      }
   }
   $migration->addField("glpi_users", "lock_autolock_mode", "tinyint(1) NULL DEFAULT NULL");
   $migration->addField("glpi_users", "lock_directunlock_notification", "tinyint(1) NULL DEFAULT NULL");



   /************** Default Requester *************/
   Config::setConfigurationValues('core', array('set_default_requester' => 1));
   $migration->addField("glpi_users", "set_default_requester", "tinyint(1) NULL DEFAULT NULL");


   /************** Kernel version for os *************/
   $migration->addField("glpi_computers", "os_kernel_version", "string");


   /************** Task's templates *************/
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
      $DB->queryOrDie($query, "9.1 add table glpi_tasktemplates");
   }

   /************** Installation date for softwares *************/
   $migration->addField("glpi_computers_softwareversions", "date_install", "DATE");
   $migration->addKey("glpi_computers_softwareversions", "date_install");

   /************** Location for budgets *************/
   $migration->addField("glpi_budgets", "locations_id", "integer");
   $migration->addKey("glpi_budgets", "locations_id");

   if (!TableExists('glpi_budgettypes')) {
      $query = "CREATE TABLE `glpi_budgettypes` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
        `comment` text COLLATE utf8_unicode_ci,
        `date_mod` datetime DEFAULT NULL,
        `date_creation` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `name` (`name`),
        KEY `date_mod` (`date_mod`),
        KEY `date_creation` (`date_creation`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "add table glpi_budgettypes");
   }

   $new = $migration->addField("glpi_budgets", "budgettypes_id", "integer");
   $migration->addKey("glpi_budgets", "budgettypes_id");

   if ($new) {
      $query = "UPDATE `glpi_displaypreferences`
                SET `num`='6' WHERE `itemtype`='Budget' AND `num`='4'";
      $DB->queryOrDie($query, "change budget display preference");
   }

   /************** New Planning with fullcalendar.io *************/
   $migration->addField("glpi_users", "plannings", "text");



   /************** API Rest *************/
   Config::setConfigurationValues('core', array('enable_api'                      => 0));
   Config::setConfigurationValues('core', array('enable_api_login_credentials'    => 0));
   Config::setConfigurationValues('core', array('enable_api_login_external_token' => 1));
   Config::setConfigurationValues('core', array('url_base_api' => trim($current_config['url_base'], "/")."/api"));
   if (!TableExists('glpi_apiclients')) {
      $query = "CREATE TABLE `glpi_apiclients` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `entities_id` INT NOT NULL DEFAULT '0',
                  `is_recursive` TINYINT(1) NOT NULL DEFAULT '0',
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `date_mod` DATETIME DEFAULT NULL,
                  `is_active` TINYINT(1) NOT NULL DEFAULT '0',
                  `ipv4_range_start` BIGINT NULL ,
                  `ipv4_range_end` BIGINT NULL ,
                  `ipv6` VARCHAR( 255 ) NULL,
                  `app_token` VARCHAR( 255 ) NULL,
                  `app_token_date` DATETIME DEFAULT NULL,
                  `dolog_method` TINYINT NOT NULL DEFAULT '0',
                  `comment` TEXT NULL ,
                  PRIMARY KEY (`id`),
                  KEY `date_mod` (`date_mod`),
                  KEY `is_active` (`is_active`)
                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "9.1 add table glpi_apiclients");
      $query = "INSERT INTO `glpi_apiclients`
                  VALUES (1, 1, 1, 'full access', NOW(), 1, NULL, NULL, NULL, '', '', 0, NULL);";
      $DB->queryOrDie($query, "9.1 insert first line into table glpi_apiclients");
   }

   /************** Date mod/creation for itemtypes *************/
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


   /************** Enhance Associated items for ticket ***************/
   // TEMPLATE UPDATE
   $migration->dropKey('glpi_tickettemplatepredefinedfields', 'unicity');

   // Get associated item searchoption num
   if (!isset($CFG_GLPI["use_rich_text"])) {
      $CFG_GLPI["use_rich_text"] = false;
   }
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

   foreach (array('glpi_tickettemplatepredefinedfields', 'glpi_tickettemplatehiddenfields',
                  'glpi_tickettemplatemandatoryfields') as $table) {
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
                  $items_to_update[$data['tickettemplates_id']]['itemtype']
                     = isset($data['value']) ? $data['value'] : 0;
               } else if ($data['num'] == $item_num) {
                  $items_to_update[$data['tickettemplates_id']]['items_id']
                     = isset($data['value']) ? $data['value'] : 0;
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
                                      AND `tickettemplates_id` = '".$templates_id."'",
                                     "Associated items migration : update predefined items");

                     $DB->queryOrDie("DELETE FROM `$table`
                                      WHERE `num` = '".$itemtype_num."'
                                            AND `tickettemplates_id` = '".$templates_id."'",
                                     "Associated items migration : delete $table itemtypes");
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
                                            AND `tickettemplates_id` = '".$templates_id."'",
                                     "Associated items migration : delete $table itemtypes");
                  }
                  $DB->queryOrDie("UPDATE `$table`
                                   SET `num` = '".$item_num."'
                                   WHERE `num` = '".$itemtype_num."'
                                         AND `tickettemplates_id` = '".$templates_id."'",
                                 "Associated items migration : delete $table itemtypes");
               }
            }
            break;
      }
   }

   /************** Add more fields to software licenses */
   $new = $migration->addField("glpi_softwarelicenses", "is_deleted", "bool");
   $migration->addField("glpi_softwarelicenses", "locations_id", "integer");
   $migration->addField("glpi_softwarelicenses", "users_id_tech", "integer");
   $migration->addField("glpi_softwarelicenses", "users_id", "integer");
   $migration->addField("glpi_softwarelicenses", "groups_id_tech", "integer");
   $migration->addField("glpi_softwarelicenses", "groups_id", "integer");
   $migration->addField("glpi_softwarelicenses", "is_helpdesk_visible", "bool");
   $migration->addField("glpi_softwarelicenses", "is_template", "bool");
   $migration->addField("glpi_softwarelicenses", "template_name", "string");
   $migration->addField("glpi_softwarelicenses", "states_id", "string");
   $migration->addKey("glpi_softwarelicenses", "locations_id");
   $migration->addKey("glpi_softwarelicenses", "users_id_tech");
   $migration->addKey("glpi_softwarelicenses", "users_id");
   $migration->addKey("glpi_softwarelicenses", "groups_id_tech");
   $migration->addKey("glpi_softwarelicenses", "groups_id");
   $migration->addKey("glpi_softwarelicenses", "is_helpdesk_visible");
   $migration->addKey("glpi_softwarelicenses", "is_deleted");
   $migration->addKey("glpi_softwarelicenses", "is_template");
   $migration->addKey("glpi_softwarelicenses", "states_id");

   $migration->addField("glpi_infocoms", "destruction_date", "datetime");
   $migration->addField("glpi_entities", "autofill_destruction_date",
                        "string", array('value' => '-2'));

   $migration->addField("glpi_states", "is_visible_softwarelicense", "bool");
   $migration->addKey("glpi_states", "is_visible_softwarelicense");

   /************* Add is_recursive on assets ***/

   foreach (array('glpi_computers', 'glpi_monitors', 'glpi_phones', 'glpi_peripherals') as $table) {
      $migration->addField($table, "is_recursive", "bool");
      $migration->addKey($table, "is_recursive");
   }

   /************* Add antivirus table */
   if (!TableExists('glpi_computerantiviruses')) {
      $query = "CREATE TABLE `glpi_computerantiviruses` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `computers_id` int(11) NOT NULL DEFAULT '0',
        `name` varchar(255) DEFAULT NULL,
        `manufacturers_id` int(11) NOT NULL DEFAULT '0',
        `antivirus_version` varchar(255) DEFAULT NULL,
        `signature_version` varchar(255) DEFAULT NULL,
        `is_active` tinyint(1) NOT NULL DEFAULT '0',
        `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
        `is_uptodate` tinyint(1) NOT NULL DEFAULT '0',
        `is_dynamic` tinyint(1) NOT NULL DEFAULT '0',
        `date_expiration` datetime DEFAULT NULL,
        `date_mod` datetime DEFAULT NULL,
        `date_creation` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `name` (`name`),
        KEY `antivirus_version` (`antivirus_version`),
        KEY `signature_version` (`signature_version`),
        KEY `is_active` (`is_active`),
        KEY `is_uptodate` (`is_uptodate`),
        KEY `is_dynamic` (`is_dynamic`),
        KEY `is_deleted` (`is_deleted`),
        KEY `computers_id` (`computers_id`),
        KEY `date_expiration` (`date_expiration`),
        KEY `date_mod` (`date_mod`),
        KEY `date_creation` (`date_creation`)
      ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
      $DB->queryOrDie($query, "Add antivirus table");
   }

   if ($new) {
      //new right for software license
      //copy the software right value to the new license right
      foreach ($DB->request("glpi_profilerights", "`name` = 'software'") as $profrights) {
         $query = "INSERT INTO `glpi_profilerights`
                          (`id`, `profiles_id`, `name`, `rights`)
                   VALUES (NULL, '".$profrights['profiles_id']."', 'license',
                           '".$profrights['rights']."')";
         $DB->queryOrDie($query, "9.1 add right for softwarelicense");
      }
   }

   //new right for survey
   foreach ($DB->request("glpi_profilerights", "`name` = 'ticket'") as $profrights) {
      $query = "UPDATE `glpi_profilerights`
                SET `rights` = `rights` | " . Ticket::SURVEY ."
                WHERE `profiles_id` = '".$profrights['profiles_id']."'
                       AND `name` = 'ticket'";
      $DB->queryOrDie($query, "9.1 update ticket with survey right");
   }

   //new field
   $migration->addField('glpi_authldaps', 'location_field', 'string', ['after' => 'email4_field']);


   //TRANS: %s is the table or item to migrate
   $migration->displayMessage(sprintf(__('Data migration - %s'), 'glpi_displaypreferences'));

   $ADDTODISPLAYPREF['SoftwareLicense'] = array(3, 10, 162, 5);
   foreach ($ADDTODISPLAYPREF as $type => $tab) {
      $query = "SELECT DISTINCT `users_id`
                FROM `glpi_displaypreferences`
                WHERE `itemtype` = '$type'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            while ($data = $DB->fetch_assoc($result)) {
               $query = "SELECT MAX(`rank`)
                         FROM `glpi_displaypreferences`
                         WHERE `users_id` = '".$data['users_id']."'
                               AND `itemtype` = '$type'";
               $result = $DB->query($query);
               $rank   = $DB->result($result,0,0);
               $rank++;

               foreach ($tab as $newval) {
                  $query = "SELECT *
                            FROM `glpi_displaypreferences`
                            WHERE `users_id` = '".$data['users_id']."'
                                  AND `num` = '$newval'
                                  AND `itemtype` = '$type'";
                  if ($result2 = $DB->query($query)) {
                     if ($DB->numrows($result2) == 0) {
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

   /** ************ New SLA structure ************ */
   if (!TableExists('glpi_slts')) {
      $query = "CREATE TABLE `glpi_slts` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                  `type` int(11) NOT NULL DEFAULT '0',
                  `comment` text COLLATE utf8_unicode_ci,
                  `number_time` int(11) NOT NULL,
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
                                (`id`, `name`,`entities_id`, `is_recursive`, `type`, `comment`,
                                 `number_time`, `date_mod`, `definition_time`,
                                 `end_of_working_day`, `date_creation`, `slas_id`)
                         VALUES ('".$data['id']."', '".$data['name']."', '".$data['entities_id']."',
                                 '".$data['is_recursive']."', '".SLT::TTR."',
                                 '".addslashes($data['comment'])."', '".$data['resolution_time']."',
                                 '".$data['date_mod']."',
                                 '".$data['definition_time']."', '".$data['end_of_working_day']."',
                                 '".date('Y-m-d H:i:s')."', '".$data['id']."');";
               $DB->queryOrDie($query, "SLA migration to SLT");
            }
         }
      }

      // Delete deprecated fields of SLA
      foreach (array('number_time', 'definition_time',
                     'end_of_working_day') as $field) {
         $migration->dropField('glpi_slas', $field);
      }

      // Slalevels changes
      $migration->changeField('glpi_slalevels', 'slas_id', 'slts_id', 'integer');
      $migration->dropKey('glpi_slalevels', 'slas_id');
      $migration->addKey('glpi_slalevels', 'slts_id');

      // Ticket changes
      $migration->changeField("glpi_tickets", "slas_id", "slts_ttr_id", "integer");
      $migration->dropKey('glpi_tickets', 'slas_id');
      $migration->dropKey('glpi_tickets', 'slts_ttr_id');

      $migration->addField("glpi_tickets", "slts_tto_id", "integer", array('after' => 'slts_ttr_id'));
      $migration->addField("glpi_tickets", "time_to_own", "datetime", array('after' => 'due_date'));
      $migration->addKey('glpi_tickets', 'slts_tto_id');
      $migration->addKey('glpi_tickets', 'time_to_own');

      // Unique key for slalevel_ticket
      $migration->addKey('glpi_slalevels_tickets', array('tickets_id', 'slalevels_id'),
                         'unicity', 'UNIQUE');

      // Sla rules criterias migration
      $DB->queryOrDie("UPDATE `glpi_rulecriterias`
                       SET `criteria` = 'slts_ttr_id'
                       WHERE `criteria` = 'slas_id'",
                      "SLA rulecriterias migration");

      // Sla rules actions migration
      $DB->queryOrDie("UPDATE `glpi_ruleactions`
                       SET `field` = 'slts_ttr_id'
                       WHERE `field` = 'slas_id'",
                      "SLA ruleactions migration");
   }

   // to delete in next version - fix change in update
   if (!FieldExists('glpi_slas', 'calendars_id')) {
      $migration->addField("glpi_slas", "calendars_id", "integer", array('after' => 'is_recursive'));
      $migration->addKey('glpi_slas', 'calendars_id');
   }
   if (FieldExists('glpi_slts', 'resolution_time')
       && !FieldExists('glpi_slts', 'number_time')) {
      $migration->changeField('glpi_slts', 'resolution_time', 'number_time', 'integer');
   }

   /************** High contrast CSS **************/
   Config::setConfigurationValues('core', array('highcontrast_css' => 0));
   $migration->addField("glpi_users", "highcontrast_css", "tinyint(1) DEFAULT 0");


   /************** SMTP option for self-signed certificates **************/
   Config::setConfigurationValues('core', array('smtp_check_certificate' => 1));

   // for group task
   $migration->addField("glpi_tickettasks", "groups_id_tech", "integer");
   $migration->addKey("glpi_tickettasks", "groups_id_tech");
   $migration->addField("glpi_changetasks", "groups_id_tech", "integer");
   $migration->addKey("glpi_changetasks", "groups_id_tech");
   $migration->addField("glpi_problemtasks", "groups_id_tech", "integer");
   $migration->addKey("glpi_problemtasks", "groups_id_tech");
   $migration->addField("glpi_groups", "is_task", "bool", array('value' => 1,
                                                                'after' => 'is_assign'));

   // for date_mod adding to tasks and to followups
   if (!FieldExists('glpi_tickettasks', 'date_mod')) {
      $migration->addField("glpi_tickettasks", "date_mod", "datetime");
      $migration->addKey("glpi_tickettasks", "date_mod");
   }
   if (!FieldExists('glpi_problemtasks', 'date_mod')) {
      $migration->addField("glpi_problemtasks", "date_mod", "datetime");
      $migration->addKey("glpi_problemtasks", "date_mod");
   }
   if (!FieldExists('glpi_changetasks', 'date_mod')) {
      $migration->addField("glpi_changetasks", "date_mod", "datetime");
      $migration->addKey("glpi_changetasks", "date_mod");
   }
   if (!FieldExists('glpi_ticketfollowups', 'date_mod')) {
      $migration->addField("glpi_ticketfollowups", "date_mod", "datetime");
      $migration->addKey("glpi_ticketfollowups", "date_mod");
   }


   // ************ Keep it at the end **************
   $migration->executeMigration();

   return $updateresult;
}
