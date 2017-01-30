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

/**
 * Update from 9.1 to 9.2
 *
 * @return bool for success (will die for most error)
**/
function update91to92() {
   global $DB, $migration, $CFG_GLPI;

   $current_config   = Config::getConfigurationValues('core');
   $updateresult     = true;
   $ADDTODISPLAYPREF = array();

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '9.2'));
   $migration->setVersion('9.2');

   $backup_tables = false;
   // table already exist but deleted during the migration
   // not table created during the migration
   $newtables     = array(
      'glpi_businesscriticities',
      'glpi_knowbaseitems_items',
      'glpi_knowbaseitems_revisions',
      'glpi_knowbaseitems_comments',
      'glpi_devicecasemodels',
      'glpi_devicecontrolmodels',
      'glpi_devicedrivemodels',
      'glpi_devicegraphiccardmodels',
      'glpi_deviceharddrivemodels',
      'glpi_devicememorymodels',
      'glpi_devicemotherboardmodels',
      'glpi_devicenetworkcardmodels',
      'glpi_devicepcimodels',
      'glpi_devicepowersupplymodels',
      'glpi_deviceprocessormodels',
      'glpi_devicesoundcardmodels',
      'glpi_devicegenericmodels',
      'glpi_devicegenerics',
      'glpi_items_devicegenerics',
      'glpi_devicegenerictypes',
      'glpi_devicebatteries',
      'glpi_items_devicebatteries',
      'glpi_devicebatterytypes',
      'glpi_devicefirmwares',
      'glpi_items_devicefirmwares',
      'glpi_devicefirmwaretypes'
   );

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

   //put you migration script here

   // add business criticity
   $migration->addField("glpi_infocoms", "businesscriticities_id", "integer");
   $migration->addKey("glpi_infocoms", "businesscriticities_id");
   if (!TableExists("glpi_businesscriticities")) {
      $query = "CREATE TABLE `glpi_businesscriticities` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
        `entities_id` int(11) NOT NULL DEFAULT '0',
        `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
        `comment` text COLLATE utf8_unicode_ci,
        `date_mod` datetime DEFAULT NULL,
        `date_creation` datetime DEFAULT NULL,
        `businesscriticities_id` int(11) NOT NULL DEFAULT '0',
        `completename` text COLLATE utf8_unicode_ci,
        `level` int(11) NOT NULL DEFAULT '0',
        `ancestors_cache` longtext COLLATE utf8_unicode_ci,
        `sons_cache` longtext COLLATE utf8_unicode_ci,
        PRIMARY KEY (`id`),
        KEY `name` (`name`),
        KEY `unicity` (`businesscriticities_id`,`name`),
        KEY `date_mod` (`date_mod`),
        KEY `date_creation` (`date_creation`)
      ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "Add business criticity table");
   }

   // Issue #1250 - Add decimal to monitor size
   $migration->changeField('glpi_monitors', 'size', 'size', 'DECIMAL(5,2) NOT NULL DEFAULT "0"');

   //Make software license type a tree dropdown
   $migration->addField("glpi_softwarelicensetypes", "softwarelicensetypes_id", "integer");
   $migration->addKey("glpi_softwarelicensetypes", "softwarelicensetypes_id");
   $migration->addField("glpi_softwarelicensetypes", "level", "integer");
   $migration->addField("glpi_softwarelicensetypes", "ancestors_cache", "longtext");
   $migration->addField("glpi_softwarelicensetypes", "sons_cache", "longtext");
   $migration->addField("glpi_softwarelicensetypes", "entities_id", "integer");
   $migration->addField("glpi_softwarelicensetypes", "is_recursive", "bool");
   $tree = $migration->addField("glpi_softwarelicensetypes", "completename", "text");
   $migration->migrationOneTable('glpi_softwarelicensetypes');

   //First time the dropdown is changed from CommonDropdown to CommonTreeDropdown
   if ($tree) {
      $query = "UPDATE `glpi_softwarelicensetypes`
                SET `completename`=`name`, `is_recursive`='1'";
      $DB->queryOrDie($query, "9.2 make glpi_softwarelicensetypes a tree dropdown");
   }

   // give READ right on components to profiles having UPDATE right
   $query = "UPDATE `glpi_profilerights`
             SET `rights` = `rights` | " . READ . "
             WHERE (`rights` & " . UPDATE .") = '" . UPDATE ."'
                   AND `name` = 'device'";
   $DB->queryOrDie($query, "grant READ right on components to profiles having UPDATE right");

   $migration->displayMessage(sprintf(__('Add of - %s to database'), 'Knowbase item link to tickets'));
   if (!TableExists('glpi_knowbaseitems_items')) {
      $query = "CREATE TABLE `glpi_knowbaseitems_items` (
                 `id` int(11) NOT NULL AUTO_INCREMENT,
                 `knowbaseitems_id` int(11) NOT NULL,
                 `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
                 `items_id` int(11) NOT NULL DEFAULT '0',
                 `date_creation` datetime DEFAULT NULL,
                 `date_mod` datetime DEFAULT NULL,
                 PRIMARY KEY (`id`),
                 UNIQUE KEY `unicity` (`itemtype`,`items_id`,`knowbaseitems_id`),
                 KEY `itemtype` (`itemtype`),
                 KEY `item_id` (`items_id`),
                 KEY `item` (`itemtype`,`items_id`)
               ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "9.2 add table glpi_knowbaseitems_items");
   }

   $migration->displayMessage(sprintf(__('Add of - %s to database'), 'Knowbase item revisions'));
   if (!TableExists('glpi_knowbaseitems_revisions')) {
      $query = "CREATE TABLE `glpi_knowbaseitems_revisions` (
                 `id` int(11) NOT NULL AUTO_INCREMENT,
                 `knowbaseitems_id` int(11) NOT NULL,
                 `revision` int(11) NOT NULL,
                 `name` text COLLATE utf8_unicode_ci,
                 `answer` longtext COLLATE utf8_unicode_ci,
                 `language` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
                 `users_id` int(11) NOT NULL DEFAULT '0',
                 `date_creation` datetime DEFAULT NULL,
                 PRIMARY KEY (`id`),
                 UNIQUE KEY `unicity` (`knowbaseitems_id`, `revision`, `language`),
                 KEY `revision` (`revision`)
               ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "9.2 add table glpi_knowbaseitems_revisions");
   }

   $migration->addField("glpi_knowbaseitemtranslations", "users_id", "integer");
   $migration->addKey("glpi_knowbaseitemtranslations", "users_id");

   //set kb translations users...
   $query = "SELECT `glpi_knowbaseitems`.`id`, `glpi_knowbaseitems`.`users_id`
             FROM `glpi_knowbaseitems`
             INNER JOIN `glpi_knowbaseitemtranslations`
                ON `glpi_knowbaseitemtranslations`.`knowbaseitems_id` = `glpi_knowbaseitems`.`id`";

   if ($result = $DB->query($query)) {
      if ($DB->numrows($result)>0) {
         while ($data = $DB->fetch_assoc($result)) {
            $query = "UPDATE `glpi_knowbaseitemtranslations`
                          SET `users_id` = '{$data['users_id']}'
                          WHERE `knowbaseitems_id` = '{$data['id']}'";
            $DB->queryOrDie($query, 'Set knowledge base translations users');
         }
      }
   }

   $migration->addField("glpi_knowbaseitemtranslations", "date_mod", "DATETIME");
   $migration->addField("glpi_knowbaseitemtranslations", "date_creation", "DATETIME");

   $migration->displayMessage(sprintf(__('Add of - %s to database'), 'Knowbase item comments'));
   if (!TableExists('glpi_knowbaseitems_comments')) {
      $query = "CREATE TABLE `glpi_knowbaseitems_comments` (
                 `id` int(11) NOT NULL AUTO_INCREMENT,
                 `knowbaseitems_id` int(11) NOT NULL,
                 `users_id` int(11) NOT NULL DEFAULT '0',
                 `language` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
                 `comment` text COLLATE utf8_unicode_ci NOT NULL,
                 `parent_comment_id` int(11) DEFAULT NULL,
                 `date_creation` datetime DEFAULT NULL,
                 `date_mod` datetime DEFAULT NULL,
                 PRIMARY KEY (`id`)
               ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "9.2 add table glpi_knowbaseitems_comments");
   }

   $query = "UPDATE `glpi_profilerights`
             SET `rights` = `rights` | " . KnowbaseItem::COMMENTS ."
             WHERE `name` = 'knowbase'";
   $DB->queryOrDie($query, "9.2 update knowledge base with comment right");

   // add kb category to task categories
   $migration->addField("glpi_taskcategories", "knowbaseitemcategories_id", "integer");
   $migration->addKey("glpi_taskcategories", "knowbaseitemcategories_id");

   // #1476 - Add users_id on glpi_documents_items
   $migration->addField("glpi_documents_items", "users_id", "integer", ['null' => TRUE]);
   $migration->addKey("glpi_documents_items", "users_id");
   $migration->addPostQuery(
      "UPDATE glpi_documents_items GDI, glpi_documents GD SET GDI.users_id = GD.users_id WHERE GDI.documents_id = GD.id",
      "9.2 update set users_id on glpi_documents_items"
   );

   //add product number
   $product_types = [
      'Computer',
      'Printer',
      'NetworkEquipment',
      'Phone',
      'Peripheral',
      'Monitor'
   ];

   foreach ($product_types as $type) {
      if (class_exists($type.'Model')) {
         $table = getTableForItemType($type.'Model');
         $migration->addField($table, 'product_number', 'string');
         $migration->addKey($table, 'product_number');
      }
   }

   // add fields on every item_device tables
   $tables = [
      'glpi_items_devicecases',
      'glpi_items_devicecontrols',
      'glpi_items_devicedrives',
      'glpi_items_devicegraphiccards',
      'glpi_items_deviceharddrives',
      'glpi_items_devicememories',
      'glpi_items_devicemotherboards',
      'glpi_items_devicenetworkcards',
      'glpi_items_devicepcis',
      'glpi_items_devicepowersupplies',
      'glpi_items_deviceprocessors',
      'glpi_items_devicesoundcards'
   ];

   //add serial, location and state on each devices items
   foreach ($tables as $table) {
      $migration->addField($table, "otherserial", "varchar(255) NULL DEFAULT NULL");
      $migration->addKey($table, 'otherserial');

      $migration->addField($table, "locations_id", "int(11) NOT NULL DEFAULT '0'");
      $migration->addKey($table, 'locations_id');

      $migration->addField($table, "states_id", "int(11) NOT NULL DEFAULT '0'");
      $migration->addKey($table, 'states_id');
   }

   // Create tables :
   $tables = [
      'glpi_devicecasemodels',
      'glpi_devicecontrolmodels',
      'glpi_devicedrivemodels',
      'glpi_devicegraphiccardmodels',
      'glpi_deviceharddrivemodels',
      'glpi_devicememorymodels',
      'glpi_devicemotherboardmodels',
      'glpi_devicenetworkcardmodels',
      'glpi_devicepcimodels',
      'glpi_devicepowersupplymodels',
      'glpi_deviceprocessormodels',
      'glpi_devicesoundcardmodels',
      'glpi_devicegenericmodels',
      'glpi_devicebatterymodels',
      'glpi_devicefirmwaremodels'
   ];

   foreach ($tables as $table) {
      if (!TableExists($table)) {
         $query = "CREATE TABLE `$table` (
                      `id` INT(11) NOT NULL AUTO_INCREMENT,
                      `name` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
                      `comment` TEXT NULL COLLATE 'utf8_unicode_ci',
                      `product_number` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
                      PRIMARY KEY (`id`),
                      INDEX `name` (`name`),
                      INDEX `product_number` (`product_number`)
                   ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE='utf8_unicode_ci'";
         $DB->queryOrDie($query, "9.2 add model tables for devices");
      }
   }

   // Add a field in glpi_device* tables :
   $tables = [
      'glpi_devicecases'         => 'devicecasemodels_id',
      'glpi_devicecontrols'      => 'devicecontrolmodels_id',
      'glpi_devicedrives'        => 'devicedrivemodels_id',
      'glpi_devicegraphiccards'  => 'devicegraphiccardmodels_id',
      'glpi_deviceharddrives'    => 'deviceharddrivemodels_id',
      'glpi_devicememories'      => 'devicememorymodels_id',
      'glpi_devicemotherboards'  => 'devicemotherboardmodels_id',
      'glpi_devicenetworkcards'  => 'devicenetworkcardmodels_id',
      'glpi_devicepcis'          => 'devicepcimodels_id',
      'glpi_devicepowersupplies' => 'devicepowersupplymodels_id',
      'glpi_deviceprocessors'    => 'deviceprocessormodels_id',
      'glpi_devicesoundcards'    => 'devicesoundcardmodels_id'
   ];

   foreach ($tables as $table => $field) {
      $migration->addField($table, $field, 'int');
      $migration->addKey($table, $field);
   }

   if (!TableExists('glpi_devicegenerics')) {
      $query = "CREATE TABLE `glpi_devicegenerics` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `designation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `devicegenerictypes_id` int(11) NOT NULL DEFAULT '0',
                  `comment` text COLLATE utf8_unicode_ci,
                  `manufacturers_id` int(11) NOT NULL DEFAULT '0',
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                  `locations_id` int(11) NOT NULL DEFAULT '0',
                  `states_id` int(11) NOT NULL DEFAULT '0',
                  `devicegenericmodels_id` int(11) DEFAULT NULL,
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `designation` (`designation`),
                  KEY `manufacturers_id` (`manufacturers_id`),
                  KEY `devicegenerictypes_id` (`devicegenerictypes_id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `locations_id` (`locations_id`),
                  KEY `states_id` (`states_id`),
                  KEY `date_mod` (`date_mod`),
                  KEY `date_creation` (`date_creation`),
                  KEY `devicegenericmodels_id` (`devicegenericmodels_id`)
               ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $DB->queryOrDie($query, "9.2 add table glpi_devicegenerics");
   }

   if (!TableExists('glpi_items_devicegenerics')) {
      $query = "CREATE TABLE `glpi_items_devicegenerics` (
                   `id` INT(11) NOT NULL AUTO_INCREMENT,
                   `items_id` INT(11) NOT NULL DEFAULT '0',
                   `itemtype` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
                   `devicegenerics_id` INT(11) NOT NULL DEFAULT '0',
                   `is_deleted` TINYINT(1) NOT NULL DEFAULT '0',
                   `is_dynamic` TINYINT(1) NOT NULL DEFAULT '0',
                   `entities_id` INT(11) NOT NULL DEFAULT '0',
                   `is_recursive` TINYINT(1) NOT NULL DEFAULT '0',
                   `serial` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
                   `otherserial` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
                   `locations_id` INT(11) NOT NULL DEFAULT '0',
                   `states_id` INT(11) NOT NULL DEFAULT '0',
                   PRIMARY KEY (`id`),
                   INDEX `computers_id` (`items_id`),
                   INDEX `devicegenerics_id` (`devicegenerics_id`),
                   INDEX `is_deleted` (`is_deleted`),
                   INDEX `is_dynamic` (`is_dynamic`),
                   INDEX `entities_id` (`entities_id`),
                   INDEX `is_recursive` (`is_recursive`),
                   INDEX `serial` (`serial`),
                   INDEX `item` (`itemtype`, `items_id`),
                   INDEX `otherserial` (`otherserial`)
                )
                COLLATE='utf8_unicode_ci'
                ENGINE=MyISAM;";
      $DB->queryOrDie($query, "9.2 add table glpi_items_devicegenerics");
   }

   if (!TableExists('glpi_devicegenerictypes')) {
      $query = "CREATE TABLE `glpi_devicegenerictypes` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `name` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
                  `comment` TEXT NULL COLLATE 'utf8_unicode_ci',
                   PRIMARY KEY (`id`),
                   INDEX `name` (`name`)
                )
                COLLATE='utf8_unicode_ci' ENGINE=MyISAM;";
      $DB->queryOrDie($query, "9.2 add table glpi_devicegenerictypes");
   }

   if (!TableExists('glpi_devicebatteries')) {
      $query = "CREATE TABLE `glpi_devicebatteries` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `designation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `manufacturers_id` int(11) NOT NULL DEFAULT '0',
                  `voltage` varchar(3) DEFAULT NULL,
                  `capacity` varchar(3) DEFAULT NULL,
                  `devicebatterytypes_id` int(11) NOT NULL DEFAULT '0',
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                  `devicebatterymodels_id` int(11) DEFAULT NULL,
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `designation` (`designation`),
                  KEY `manufacturers_id` (`manufacturers_id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `date_mod` (`date_mod`),
                  KEY `date_creation` (`date_creation`),
                  KEY `devicebatterymodels_id` (`devicebatterymodels_id`),
                  KEY `devicebatterytypes_id` (`devicebatterytypes_id`)
               ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "9.2 add table glpi_devicebatteries");
   }

   if (!TableExists('glpi_items_devicebatteries')) {
      $query = "CREATE TABLE `glpi_items_devicebatteries` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `items_id` int(11) NOT NULL DEFAULT '0',
                  `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `devicebatteries_id` int(11) NOT NULL DEFAULT '0',
                  `manufacturing_date` date DEFAULT NULL,
                  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
                  `is_dynamic` tinyint(1) NOT NULL DEFAULT '0',
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `locations_id` int(11) NOT NULL DEFAULT '0',
                  `states_id` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  KEY `computers_id` (`items_id`),
                  KEY `devicebatteries_id` (`devicebatteries_id`),
                  KEY `is_deleted` (`is_deleted`),
                  KEY `is_dynamic` (`is_dynamic`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `serial` (`serial`),
                  KEY `item` (`itemtype`,`items_id`),
                  KEY `otherserial` (`otherserial`)
               ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "9.2 add table glpi_items_devicebatteries");
   }

   if (!TableExists('glpi_devicebatterytypes')) {
      $query = "CREATE TABLE `glpi_devicebatterytypes` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `date_mod` (`date_mod`),
                  KEY `date_creation` (`date_creation`)
               ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "9.2 add table glpi_devicebatterytypes");
   }

   if (!TableExists('glpi_devicefirmwares')) {
      $query = "CREATE TABLE `glpi_devicefirmwares` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `designation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `manufacturers_id` int(11) NOT NULL DEFAULT '0',
                  `date` date DEFAULT NULL,
                  `version` varchar(255) DEFAULT NULL,
                  `devicefirmwaretypes_id` int(11) NOT NULL DEFAULT '0',
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                  `devicefirmwaremodels_id` int(11) DEFAULT NULL,
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `designation` (`designation`),
                  KEY `manufacturers_id` (`manufacturers_id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `date_mod` (`date_mod`),
                  KEY `date_creation` (`date_creation`),
                  KEY `devicefirmwaremodels_id` (`devicefirmwaremodels_id`),
                  KEY `devicefirmwaretypes_id` (`devicefirmwaretypes_id`)
               ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "9.2 add table glpi_devicefirmwares");
   }
   if (!TableExists('glpi_items_devicefirmwares')) {
      $query = "CREATE TABLE `glpi_items_devicefirmwares` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `items_id` int(11) NOT NULL DEFAULT '0',
                  `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `devicefirmwares_id` int(11) NOT NULL DEFAULT '0',
                  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
                  `is_dynamic` tinyint(1) NOT NULL DEFAULT '0',
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `locations_id` int(11) NOT NULL DEFAULT '0',
                  `states_id` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  KEY `computers_id` (`items_id`),
                  KEY `devicefirmwares_id` (`devicefirmwares_id`),
                  KEY `is_deleted` (`is_deleted`),
                  KEY `is_dynamic` (`is_dynamic`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `serial` (`serial`),
                  KEY `item` (`itemtype`,`items_id`),
                  KEY `otherserial` (`otherserial`)
               ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "9.2 add table glpi_items_devicefirmwares");
   }
   if (!TableExists('glpi_devicefirmwaretypes')) {
      $query = "CREATE TABLE `glpi_devicefirmwaretypes` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `date_mod` (`date_mod`),
                  KEY `date_creation` (`date_creation`)
               ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "9.2 add table glpi_devicefirmwaretypes");

      $DB->queryOrDie("INSERT INTO `glpi_devicefirmwaretypes` VALUES ('1','BIOS',NULL,NULL,NULL);");
      $DB->queryOrDie("INSERT INTO `glpi_devicefirmwaretypes` VALUES ('2','UEFI',NULL,NULL,NULL);");
      $DB->queryOrDie("INSERT INTO `glpi_devicefirmwaretypes` VALUES ('3','Firmware',NULL,NULL,NULL);");
   }

   //Father/son for Software licenses
   $migration->addField("glpi_softwarelicenses", "softwarelicenses_id", "integer");
   $new = $migration->addField("glpi_softwarelicenses", "completename", "text");
   $migration->addField("glpi_softwarelicenses", "level", "integer");
   $migration->executeMigration();
   if ($new) {
      $query = "UPDATE `glpi_softwarelicenses` SET `completename`=`name`";
      $DB->queryOrDie($query, "9.2 copy name to completename for software licenses");
   }

   // add template key to itiltasks
   $migration->addField("glpi_tickettasks", "tasktemplates_id", "integer");
   $migration->addKey("glpi_tickettasks", "tasktemplates_id");
   $migration->migrationOneTable('glpi_tickettasks');
   $migration->addField("glpi_problemtasks", "tasktemplates_id", "integer");
   $migration->addKey("glpi_problemtasks", "tasktemplates_id");
   $migration->migrationOneTable('glpi_problemtasks');
   $migration->addField("glpi_changetasks", "tasktemplates_id", "integer");
   $migration->addKey("glpi_changetasks", "tasktemplates_id");
   $migration->migrationOneTable('glpi_changetasks');

   // add missing fields to tasktemplate
   $migration->addField("glpi_tasktemplates", "state", "integer");
   $migration->addField("glpi_tasktemplates", "is_private", "bool");
   $migration->addField("glpi_tasktemplates", "users_id_tech", "integer");
   $migration->addField("glpi_tasktemplates", "groups_id_tech", "integer");
   $migration->addKey("glpi_tickettasks", "is_private");
   $migration->addKey("glpi_tickettasks", "users_id_tech");
   $migration->addKey("glpi_tickettasks", "groups_id_tech");
   $migration->migrationOneTable('glpi_tasktemplates');

   // #1735 - Add new notifications
   $notification       = new Notification;
   $notificationtarget = new NotificationTarget;
   $new_notifications  = [
      'requester_user'  => ['label'      => 'New user in requesters',
                            'targets_id' => Notification::AUTHOR],
      'requester_group' => ['label'      => 'New group in requesters',
                            'targets_id' => Notification::REQUESTER_GROUP],
      'observer_user'   => ['label'      => 'New user in observers',
                            'targets_id' => Notification::OBSERVER],
      'observer_group'  => ['label'      => 'New group in observers',
                            'targets_id' => Notification::OBSERVER_GROUP],
      'assign_user'     => ['label'      => 'New user in assignees',
                            'targets_id' => Notification::ASSIGN_TECH],
      'assign_group'    => ['label'      => 'New group in assignees',
                            'targets_id' => Notification::ITEM_TECH_GROUP_IN_CHARGE],
      'assign_supplier' => ['label'      => 'New supplier in assignees',
                            'targets_id' => Notification::SUPPLIER],
   ];

   foreach ($new_notifications as $event => $notif_options) {
      $notifications_id = $notification->add([
         'name'                     => $notif_options['label'],
         'itemtype'                 => 'Ticket',
         'event'                    => $event,
         'mode'                     => 'mail',
         'notificationtemplates_id' => 0,
         'is_recursive'             => 1,
         'is_active'                => 0,
      ]);

      $notificationtarget->add([
         'items_id'         => $notif_options['targets_id'],
         'type'             => 1,
         'notifications_id' => $notifications_id,
      ]);
   }

   // ************ Keep it at the end **************
   $migration->executeMigration();

   return $updateresult;
}
