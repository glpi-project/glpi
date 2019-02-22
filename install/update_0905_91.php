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
 * Update from 0.90.5 to 9.1
 *
 * @return bool for success (will die for most error)
**/
function update0905to91() {
   global $DB, $migration, $CFG_GLPI;

   $current_config   = Config::getConfigurationValues('core');
   $updateresult     = true;
   $ADDTODISPLAYPREF = [];

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '9.1'));
   $migration->setVersion('9.1');

   $backup_tables = false;
   // table already exist but deleted during the migration
   // not table created during the migration
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

   $migration->displayMessage(sprintf(__('Add of - %s to database'), 'Object Locks'));

   /************** Lock Objects *************/
   if (!$DB->tableExists('glpi_objectlocks')) {
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

      $DB->queryOrDie($query, "9.1 update profile with Unlock profile");
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
      foreach ($CFG_GLPI['lock_lockable_objects'] as $itemtype) {
         $rightnames[] = $itemtype::$rightname;
      }

      $DB->updateOrDie("glpi_profilerights", [
            'rights' => new \QueryExpression(
               DBmysql::quoteName("rights") . " | " . DBmysql::quoteValue(UNLOCK)
            )
         ], [
            'profiles_id'  => 4,
            'name'         => $rightnames
         ],
         "update super-admin profile with UNLOCK right"
      );

      Config::setConfigurationValues('core', ['lock_use_lock_item'             => 0,
                                                   'lock_autolock_mode'             => 1,
                                                   'lock_directunlock_notification' => 0,
                                                   'lock_item_list'                 => '[]',
                                                   'lock_lockprofile_id'            => $ro_p_id]);
   }

   // cron task
   if (!countElementsInTable('glpi_crontasks',
                             ['itemtype' => 'ObjectLock', 'name' => 'unlockobject'])) {
      $DB->insertOrDie("glpi_crontasks", [
            'itemtype'        => "ObjectLock",
            'name'            => "unlockobject",
            'frequency'       => 86400,
            'param'           => 4,
            'state'           => 0,
            'mode'            => 1,
            'allowmode'       => 3,
            'hourmin'         => 0,
            'hourmax'         => 24,
            'logs_lifetime'   => 30,
            'lastrun'         => null,
            'lastcode'        => null,
            'comment'         => null
         ],
         "9.1 Add UnlockObject cron task"
      );
   }
   // notification template
   $notificationtemplatesIterator = $DB->request([
      'FROM'   => "glpi_notificationtemplates",
      'WHERE'  => ['itemtype' => "ObjectLock"]
   ]);

   if (count($notificationtemplatesIterator) == 0) {
      $DB->insertOrDie("glpi_notificationtemplates", [
            'name'      => "Unlock Item request",
            'itemtype'  => "ObjectLock",
            'date_mod'  => new \QueryExpression("NOW()")
         ],
         "9.1 Add unlock request notification template"
      );
      $notid = $DB->insert_id();

      $contentText =
         '##objectlock.type## ###objectlock.id## - ##objectlock.name##

         ##lang.objectlock.url##
         ##objectlock.url##

         ##lang.objectlock.date_mod##
         ##objectlock.date_mod##

         Hello ##objectlock.lockedby.firstname##,
         Could go to this item and unlock it for me?
         Thank you,
         Regards,
         ##objectlock.requester.firstname##';

      $contentHtml =
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
         &lt;p&gt;&lt;span style=\"font-size: small;\"&gt;Hello ##objectlock.lockedby.firstname##,&lt;br /&gt;Could go to this item and unlock it for me?&lt;br /&gt;Thank you,&lt;br /&gt;Regards,&lt;br /&gt;##objectlock.requester.firstname## ##objectlock.requester.lastname##&lt;/span&gt;&lt;/p&gt;';

      $DB->insertOrDie("glpi_notificationtemplatetranslations", [
            'notificationtemplates_id' => $notid,
            'language'                 => "",
            'subject'                  => "##objectlock.action##",
            'content_text'             => $contentText,
            'content_html'             => $contentHtml
         ],
         "9.1 add Unlock Request notification translation"
      );

      $DB->insertOrDie("glpi_notifications", [
            'name'                     => "Request Unlock Item",
            'entities_id'              => 0,
            'itemtype'                 => "ObjectLock",
            'event'                    => "unlock",
            'mode'                     => "mail",
            'notificationtemplates_id' => $notid,
            'comment'                  => "",
            'is_recursive'             => 1,
            'is_active'                => 1,
            'date_mod'                 => new \QueryExpression("NOW()")
         ],
         "9.1 add Unlock Request notification"
      );
      $notifid = $DB->insert_id();

      $DB->insertOrDie("glpi_notificationtargets", [
            'id'                 => null,
            'notifications_id'   => $notifid,
            'type'               => Notification::USER_TYPE,
            'items_id'           => Notification::USER_TYPE
         ],
         "9.1 add Unlock Request notification target"
      );
   }

   $migration->addField("glpi_users", "lock_autolock_mode", "tinyint(1) NULL DEFAULT NULL");
   $migration->addField("glpi_users", "lock_directunlock_notification", "tinyint(1) NULL DEFAULT NULL");

   /************** Default Requester *************/
   Config::setConfigurationValues('core', ['set_default_requester' => 1]);
   $migration->addField("glpi_users", "set_default_requester", "tinyint(1) NULL DEFAULT NULL");

   // ************ Networkport ethernets **************
   if (!$DB->tableExists("glpi_networkportfiberchannels")) {
      $query = "CREATE TABLE `glpi_networkportfiberchannels` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `networkports_id` int(11) NOT NULL DEFAULT '0',
                  `items_devicenetworkcards_id` int(11) NOT NULL DEFAULT '0',
                  `netpoints_id` int(11) NOT NULL DEFAULT '0',
                  `wwn` varchar(16) COLLATE utf8_unicode_ci DEFAULT '',
                  `speed` int(11) NOT NULL DEFAULT '10' COMMENT 'Mbit/s: 10, 100, 1000, 10000',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `networkports_id` (`networkports_id`),
                  KEY `card` (`items_devicenetworkcards_id`),
                  KEY `netpoint` (`netpoints_id`),
                  KEY `wwn` (`wwn`),
                  KEY `speed` (`speed`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->query($query);
   }

   /************** Kernel version for os *************/
   $migration->addField("glpi_computers", "os_kernel_version", "string");

   /************** os architecture *************/
   $migration->addField("glpi_computers", "operatingsystemarchitectures_id", "integer");
   $migration->addKey("glpi_computers", "operatingsystemarchitectures_id");

   if (!$DB->tableExists('glpi_operatingsystemarchitectures')) {
      $query = "CREATE TABLE `glpi_operatingsystemarchitectures` (
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
      $DB->queryOrDie($query, "9.1 add table glpi_operatingsystemarchitectures");
   }

   /************** Task's templates *************/
   if (!$DB->tableExists('glpi_tasktemplates')) {
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

   if (!$DB->tableExists('glpi_budgettypes')) {
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
      $DB->updateOrDie("glpi_displaypreferences", [
            'num' => 6
         ], [
            'itemtype'  => "Budget",
            'num'       => 4,
         ],
         "change budget display preference"
      );
   }

   /************** New Planning with fullcalendar.io *************/
   $migration->addField("glpi_users", "plannings", "text");

   /************** API Rest *************/
   Config::setConfigurationValues('core', ['enable_api'                      => 0]);
   Config::setConfigurationValues('core', ['enable_api_login_credentials'    => 0]);
   Config::setConfigurationValues('core', ['enable_api_login_external_token' => 1]);
   Config::setConfigurationValues('core', ['url_base_api' => trim($current_config['url_base'], "/")."/apirest.php/"]);
   if (!$DB->tableExists('glpi_apiclients')) {
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

      $DB->insertOrDie("glpi_apiclients", [
            'id'                 => 1,
            'entities_id'        => 0,
            'is_recursive'       => 1,
            'name'               => "full access from localhost",
            'date_mod'           => new \QueryExpression("NOW()"),
            'is_active'          => 1,
            'ipv4_range_start'   => new \QueryExpression("INET_ATON('127.0.0.1')"),
            'ipv4_range_end'     => new \QueryExpression("INET_ATON('127.0.0.1')"),
            'ipv6'               => "::1",
            'app_token'          => "",
            'app_token_date'     => null,
            'dolog_method'       => 0,
            'comment'            => null
         ],
         "9.1 insert first line into table glpi_apiclients"
      );
   }

   /************** Date mod/creation for itemtypes *************/
   $migration->displayMessage(sprintf(__('date_mod and date_creation')));
   $types = ['AuthLDAP', 'Blacklist', 'BlacklistedMailContent', 'Budget',  'Calendar',
                  'CartridgeItemType', 'Change', 'ChangeTask', 'ComputerDisk',
                  'ComputerVirtualMachine', 'ConsumableItemType', 'Contact', 'ContactType',
                  'Contract', 'ContractType', 'Crontask', 'DeviceCaseType', 'DeviceMemoryType',
                  'Document', 'DocumentCategory', 'DocumentType', 'Domain',  'Entity', 'FQDN',
                  'Fieldblacklist', 'FieldUnicity', 'Filesystem', 'Group', 'Holiday', 'Infocom',
                  'InterfaceType', 'IPNetwork', 'ITILCategory', 'KnowbaseItemCategory', 'Location',
                  'Link', 'MailCollector', 'Manufacturer', 'Netpoint', 'Network',
                  'NetworkEquipmentFirmware', 'NetworkName', 'NetworkPort', 'Notification',
                  'NotificationTemplate', 'PhonePowerSupply', 'Problem', 'ProblemTask', 'Profile',
                  'Project', 'ProjectState', 'ProjectTaskType', 'ProjectType',  'Reminder',
                  'RequestType', 'RSSFeed', 'Rule', 'RuleRightParameter', 'SLA',
                  'SoftwareLicenseType', 'SoftwareVersion', 'SolutionTemplate', 'SolutionType',
                  'SsoVariable', 'State', 'Supplier', 'SupplierType',
                  'TaskCategory', 'TaskTemplate',  'Ticket', 'TicketFollowup', 'TicketTask',
                  'User', 'UserCategory', 'UserTitle', 'VirtualMachineState', 'VirtualMachineSystem',
                  'VirtualMachineType', 'Vlan', 'WifiNetwork'];
   $types = array_merge($types, $CFG_GLPI["infocom_types"]);
   $types = array_merge($types, $CFG_GLPI["dictionnary_types"]);
   $types = array_merge($types, $CFG_GLPI["device_types"]);
   $types = array_merge($types, $CFG_GLPI['networkport_instantiations']);

   foreach ($types as $type) {
      $table = getTableForItemType($type);

      if ($DB->tableExists($table)
          && !$DB->fieldExists($table, 'date_mod')) {
         $migration->displayMessage(sprintf(__('Add date_mod to %s'), $table));

         //Add date_mod field if it doesn't exists
         $migration->addField($table, 'date_mod', 'datetime');
         $migration->addKey($table, 'date_mod');
         $migration->migrationOneTable($table);
      }

      if ($DB->tableExists($table)
          && !$DB->fieldExists($table, 'date_creation')) {
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
      if (is_array($option)
          && isset($option['field'])) {
         if ($option['field'] == 'items_id') {
            $item_num = $num;
         } else if ($option['field'] == 'itemtype') {
            $itemtype_num = $num;
         }
      }
   }

   foreach (['glpi_tickettemplatepredefinedfields', 'glpi_tickettemplatehiddenfields',
                  'glpi_tickettemplatemandatoryfields'] as $table) {
      $columns = [];
      switch ($table) {
         case 'glpi_tickettemplatepredefinedfields' :
            $columns = ['num', 'value', 'tickettemplates_id'];
            break;

         default :
            $columns = ['num', 'tickettemplates_id'];
            break;
      }

      $iterator = $DB->request([
         'SELECT' => $columns,
         'FROM'   => $table,
         'WHERE'  => [
            'OR' => [
               ['num' => $item_num],
               ['num' => $itemtype_num]
            ]
         ]
      ]);

      $items_to_update = [];
      if (count($iterator)) {
         while ($data = $iterator->next()) {
            if ($data['num'] == $itemtype_num) {
               $items_to_update[$data['tickettemplates_id']]['itemtype']
                  = isset($data['value']) ? $data['value'] : 0;
            } else if ($data['num'] == $item_num) {
               $items_to_update[$data['tickettemplates_id']]['items_id']
                  = isset($data['value']) ? $data['value'] : 0;
            }
         }
      }

      switch ($table) {
         case 'glpi_tickettemplatepredefinedfields' : // Update predefined items
            foreach ($items_to_update as $templates_id => $type) {
               if (isset($type['itemtype'])) {
                  if (isset($type['items_id'])) {
                     $DB->updateOrDie($table, [
                           'value' => $type['itemtype'] . "_" . $type['items_id']
                        ], [
                           'num'                => $item_num,
                           'tickettemplates_id' => $templates_id,
                        ],
                        "Associated items migration : update predefined items"
                     );

                     $DB->deleteOrDie($table, [
                           'num'                => $itemtype_num,
                           'tickettemplates_id' => $templates_id,
                        ],
                        "Associated items migration : delete $table itemtypes"
                     );
                  }
               }
            }
            break;

         default: // Update mandatory and hidden items
            foreach ($items_to_update as $templates_id => $type) {
               if (isset($type['itemtype'])) {
                  if (isset($type['items_id'])) {
                     $DB->deleteOrDie($table, [
                           'num'                => $item_num,
                           'tickettemplates_id' => $templates_id,
                        ],
                        "Associated items migration : delete $table itemtypes"
                     );
                  }
                  $DB->updateOrDie($table, [
                        'num' => $item_num
                     ], [
                        'num'                => $itemtype_num,
                        'tickettemplates_id' => $templates_id,
                     ],
                     "Associated items migration : update $table itemtypes"
                  );
               }
            }
            break;
      }
   }

   /************** Add more fields to software licenses */
   $migration->addField("glpi_softwarelicenses", "is_deleted", "bool");
   $migration->addField("glpi_softwarelicenses", "locations_id", "integer");
   $migration->addField("glpi_softwarelicenses", "users_id_tech", "integer");
   $migration->addField("glpi_softwarelicenses", "users_id", "integer");
   $migration->addField("glpi_softwarelicenses", "groups_id_tech", "integer");
   $migration->addField("glpi_softwarelicenses", "groups_id", "integer");
   $migration->addField("glpi_softwarelicenses", "is_helpdesk_visible", "bool");
   $migration->addField("glpi_softwarelicenses", "is_template", "bool");
   $migration->addField("glpi_softwarelicenses", "template_name", "string");
   $migration->addField("glpi_softwarelicenses", "states_id", "integer");
   $migration->addField("glpi_softwarelicenses", "manufacturers_id", "integer");

   $migration->addKey("glpi_softwarelicenses", "locations_id");
   $migration->addKey("glpi_softwarelicenses", "users_id_tech");
   $migration->addKey("glpi_softwarelicenses", "users_id");
   $migration->addKey("glpi_softwarelicenses", "groups_id_tech");
   $migration->addKey("glpi_softwarelicenses", "groups_id");
   $migration->addKey("glpi_softwarelicenses", "is_helpdesk_visible");
   $migration->addKey("glpi_softwarelicenses", "is_deleted");
   $migration->addKey("glpi_softwarelicenses", "is_template");
   $migration->addKey("glpi_softwarelicenses", "states_id");
   $migration->addKey("glpi_softwarelicenses", "manufacturers_id");

   $migration->addField("glpi_infocoms", "decommission_date", "datetime");
   $migration->addField("glpi_entities", "autofill_decommission_date",
                        "string", ['value' => '-2']);

   $migration->addField("glpi_states", "is_visible_softwarelicense", "bool");
   $migration->addKey("glpi_states", "is_visible_softwarelicense");

   /************* Add is_recursive on assets ***/
   foreach (['glpi_computers', 'glpi_monitors', 'glpi_phones', 'glpi_peripherals'] as $table) {
      $migration->addField($table, "is_recursive", "bool");
      $migration->addKey($table, "is_recursive");
   }

   /************* Add antivirus table */
   if (!$DB->tableExists('glpi_computerantiviruses')) {
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

   if (countElementsInTable("glpi_profilerights", ['name' => 'license']) == 0) {
      //new right for software license
      //copy the software right value to the new license right
      foreach ($DB->request("glpi_profilerights", "`name` = 'software'") as $profrights) {
         $DB->insertOrDie("glpi_profilerights", [
               'id'           => null,
               'profiles_id'  => $profrights['profiles_id'],
               'name'         => "license",
               'rights'       => $profrights['rights'],
            ],
            "9.1 add right for softwarelicense"
         );
      }
   }

   //new right for survey
   foreach ($DB->request("glpi_profilerights", "`name` = 'ticket'") as $profrights) {
      $DB->updateOrDie("glpi_profilerights", [
            'rights' => new \QueryExpression(
               DBmysql::quoteName("rights") . " | " . DBmysql::quoteValue(Ticket::SURVEY)
            )
         ], [
            'profiles_id'  => $profrights['profiles_id'],
            'name'         => "ticket"
         ],
         "9.1 update ticket with survey right"
      );
   }

   //new field
   $migration->addField('glpi_authldaps', 'location_field', 'string', ['after' => 'email4_field']);

   //TRANS: %s is the table or item to migrate
   $migration->displayMessage(sprintf(__('Data migration - %s'), 'glpi_displaypreferences'));

   $ADDTODISPLAYPREF['SoftwareLicense'] = [3, 10, 162, 5];
   foreach ($ADDTODISPLAYPREF as $type => $tab) {
      $displaypreferencesIterator = $DB->request([
         'SELECT DISTINCT' => "users_id",
         'FROM'            => "glpi_displaypreferences",
         'WHERE'           => ['itemtype' => $type]
      ]);

      if (count($displaypreferencesIterator)) {
         while ($data = $displaypreferencesIterator->next()) {
            $rank = $DB->request([
               'SELECT DISTINCT' => ['MAX' => "rank AS max_rank"],
               'FROM'            => "glpi_displaypreferences",
               'WHERE'           => [
                  'users_id' => $data['users_id'],
                  'itemtype' => $type
               ]
            ])->next();
            $rank = $rank ? $rank['max_rank']++ : 1;

            foreach ($tab as $newval) {
               $iterator = $DB->request([
                  'FROM' => "glpi_displaypreferences",
                  'WHERE' => [
                     'users_id'  => $data['users_id'],
                     'num'       => $newval,
                     'itemtype'  => $type
                  ],
               ]);
               if (count($iterator) == 0) {
                  $DB->insert("glpi_displaypreferences", [
                     'itemtype'  => $type,
                     'num'       => $newval,
                     'rank'      => $rank++,
                     'users_id'  => $data['users_id'],
                  ]);
               }
            }
         }

      } else { // Add for default user
         $rank = 1;
         foreach ($tab as $newval) {
            $DB->insert("glpi_displaypreferences", [
               'itemtype'  => $type,
               'num'       => $newval,
               'rank'      => $rank++,
               'users_id'  => 0,
            ]);
         }
      }
   }

   /** ************ New SLA structure ************ */
   if (!$DB->tableExists('glpi_slts')) {
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
      $DB->queryOrDie($query, "9.1 add table glpi_slts");

      // Sla migration
      $slasIterator = $DB->request("glpi_slas");
      if (count($slasIterator)) {
         while ($data = $slasIterator->next()) {
            $DB->insertOrDie("glpi_slts", [
                  'id'                 => $data['id'],
                  'name'               => Toolbox::addslashes_deep($data['name']),
                  'entities_id'        => $data['entities_id'],
                  'is_recursive'       => $data['is_recursive'],
                  'type'               => SLM::TTR,
                  'comment'            => addslashes($data['comment']),
                  'number_time'        => $data['resolution_time'],
                  'date_mod'           => $data['date_mod'],
                  'definition_time'    => $data['definition_time'],
                  'end_of_working_day' => $data['end_of_working_day'],
                  'date_creation'      => date('Y-m-d H:i:s'),
                  'slas_id'            => $data['id']
               ],
               "SLA migration to SLT"
            );
         }
      }

      // Delete deprecated fields of SLA
      // save table before delete fields
      $migration->copyTable('glpi_slas', 'backup_glpi_slas');

      foreach (['number_time', 'definition_time',
                     'end_of_working_day'] as $field) {
         $migration->dropField('glpi_slas', $field);
      }
   }

   // Slalevels changes
   if ($DB->fieldExists('glpi_slalevels', 'slas_id')) {
      $migration->changeField('glpi_slalevels', 'slas_id', 'slts_id', 'integer');
      $migration->migrationOneTable('glpi_slalevels');
      $migration->dropKey('glpi_slalevels', 'slas_id');
      $migration->addKey('glpi_slalevels', 'slts_id');
   }

   // Ticket changes
   if ($DB->fieldExists('glpi_tickets', 'slas_id')) {
      $migration->changeField("glpi_tickets", "slas_id", "slts_ttr_id", "integer");
      $migration->migrationOneTable('glpi_tickets');
      $migration->dropKey('glpi_tickets', 'slas_id');
      $migration->addKey('glpi_tickets', 'slts_ttr_id');
   }

   if (!$DB->fieldExists('glpi_tickets', 'slts_tto_id')) {
      $migration->addField("glpi_tickets", "slts_tto_id", "integer", ['after' => 'slts_ttr_id']);
      $migration->addKey('glpi_tickets', 'slts_tto_id');
   }

   if (!$DB->fieldExists('glpi_tickets', 'time_to_own')) {
      $migration->addField("glpi_tickets", "time_to_own", "datetime", ['after' => 'due_date']);
      $migration->addKey('glpi_tickets', 'time_to_own');
   }

   if ($DB->fieldExists('glpi_tickets', 'slalevels_id')) {
      $migration->changeField('glpi_tickets', 'slalevels_id', 'ttr_slalevels_id', 'integer');
      $migration->migrationOneTable('glpi_tickets');
      $migration->dropKey('glpi_tickets', 'slalevels_id');
      $migration->addKey('glpi_tickets', 'ttr_slalevels_id');
   }

   // Unique key for slalevel_ticket
   $migration->addKey('glpi_slalevels_tickets', ['tickets_id', 'slalevels_id'],
                        'unicity', 'UNIQUE');

   // Sla rules criterias migration
   $DB->updateOrDie("glpi_rulecriterias",
      ['criteria' => "slts_ttr_id" ],
      ['criteria' => "slas_id"],
      "SLA rulecriterias migration"
   );

   // Sla rules actions migration
   $DB->updateOrDie("glpi_ruleactions",
      ['field' => "slts_ttr_id" ],
      ['field' => "slas_id"],
      "SLA ruleactions migration"
   );

   // to delete in next version - fix change in update
   if (!$DB->fieldExists('glpi_slas', 'calendars_id')) {
      $migration->addField("glpi_slas", "calendars_id", "integer", ['after' => 'is_recursive']);
      $migration->addKey('glpi_slas', 'calendars_id');
   }
   if ($DB->fieldExists('glpi_slts', 'resolution_time')
       && !$DB->fieldExists('glpi_slts', 'number_time')) {
      $migration->changeField('glpi_slts', 'resolution_time', 'number_time', 'integer');
   }

   /************** High contrast CSS **************/
   Config::setConfigurationValues('core', ['highcontrast_css' => 0]);
   $migration->addField("glpi_users", "highcontrast_css", "tinyint(1) DEFAULT 0");

   /************** SMTP option for self-signed certificates **************/
   Config::setConfigurationValues('core', ['smtp_check_certificate' => 1]);

   // for group task
   $migration->addField("glpi_tickettasks", "groups_id_tech", "integer");
   $migration->addKey("glpi_tickettasks", "groups_id_tech");
   $migration->addField("glpi_changetasks", "groups_id_tech", "integer");
   $migration->addKey("glpi_changetasks", "groups_id_tech");
   $migration->addField("glpi_problemtasks", "groups_id_tech", "integer");
   $migration->addKey("glpi_problemtasks", "groups_id_tech");
   $migration->addField("glpi_groups", "is_task", "bool", ['value' => 1,
                                                                'after' => 'is_assign']);

   // for date_mod adding to tasks and to followups
   $migration->addField("glpi_tickettasks", "date_mod", "datetime");
   $migration->addKey("glpi_tickettasks", "date_mod");
   $migration->addField("glpi_problemtasks", "date_mod", "datetime");
   $migration->addKey("glpi_problemtasks", "date_mod");
   $migration->addField("glpi_changetasks", "date_mod", "datetime");
   $migration->addKey("glpi_changetasks", "date_mod");
   $migration->addField("glpi_ticketfollowups", "date_mod", "datetime");
   $migration->addKey("glpi_ticketfollowups", "date_mod");

   // for is_active adding to glpi_taskcategories
   $migration->addField("glpi_taskcategories", "is_active", "bool", ['value' => 1]);
   $migration->addKey("glpi_taskcategories", "is_active");

   // for is_active, is_followup_default, is_ticketheader and is_ticketfollowup in glpi_requesttypes
   $migration->addField("glpi_requesttypes", "is_active", "bool", ['value' => 1]);
   $migration->addKey("glpi_requesttypes", "is_active");
   $migration->addField("glpi_requesttypes", "is_ticketheader", "bool", ['value' => 1]);
   $migration->addKey("glpi_requesttypes", "is_ticketheader");
   $migration->addField("glpi_requesttypes", "is_ticketfollowup", "bool", ['value' => 1]);
   $migration->addKey("glpi_requesttypes", "is_ticketfollowup");
   $migration->addField("glpi_requesttypes", "is_followup_default", "bool", ['value' => 0]);
   $migration->addKey("glpi_requesttypes", "is_followup_default");
   $migration->addField("glpi_requesttypes", "is_mailfollowup_default", "bool", ['value' => 0]);
   $migration->addKey("glpi_requesttypes", "is_mailfollowup_default");

   /************** Fix autoclose_delay for root_entity in glpi_entities (from -1 to 0) **************/
   $DB->updateOrDie("glpi_entities", [
         'autoclose_delay' => 0
      ], [
         'autoclose_delay' => -1,
         'id'              => 0
      ],
      "glpi_entities root_entity change autoclose_delay value from -1 to 0"
   );

   // ************ Keep it at the end **************
   $migration->executeMigration();

   return $updateresult;
}
