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

/// Update from 0.51x to 0.6
function update051to06() {
   global $DB;

   echo "<p class='center'>Version 0.6 </p>";

   /*******************************GLPI 0.6***********************************************/
   $query = "UPDATE `glpi_tracking`
             SET `category` = '0'
             WHERE `category` IS NULL";
   $DB->queryOrDie($query, "0.6 prepare for alter category tracking");

   $query = "ALTER TABLE `glpi_tracking`
             CHANGE `category` `category` INT(11) DEFAULT '0' NOT NULL";
   $DB->queryOrDie($query, "0.6 alter category tracking");

   // state pour les template
   if (!$DB->fieldExists("glpi_state_item", "is_template", false)) {
      $query = "ALTER TABLE `glpi_state_item`
                ADD `is_template` ENUM('0', '1') DEFAULT '0' NOT NULL ";
      $DB->queryOrDie($query, "0.6 add is_template in state_item");
   }

   if (!$DB->tableExists("glpi_dropdown_cartridge_type")) {
      $query = "CREATE TABLE `glpi_dropdown_cartridge_type` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL default '',
                  PRIMARY KEY  (`ID`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.6 add table dropdown_cartridge_type");

      $query = "INSERT INTO `glpi_dropdown_cartridge_type`
                       (`name`)
                VALUES ('Ink-Jet')";
      $DB->queryOrDie($query, "0.6 add entries to dropdown_cartridge_type");

      $query = "INSERT INTO `glpi_dropdown_cartridge_type`
                       (`name`)
                VALUES ('Toner')";
      $DB->queryOrDie($query, "0.6 add entries to dropdown_cartridge_type");

      $query = "INSERT INTO `glpi_dropdown_cartridge_type`
                       (`name`)
                VALUES ('Ribbon')";
      $DB->queryOrDie($query, "0.6 add entries to dropdown_cartridge_type");
   }

   // specific alarm pour les cartouches
   if (!$DB->fieldExists("glpi_cartridges_type", "alarm", false)) {
      $query = "ALTER TABLE `glpi_cartridges_type`
                ADD `alarm` TINYINT DEFAULT '10' NOT NULL ";
      $DB->queryOrDie($query, "0.6 add alarm in cartridges_type");
   }

   // email for enterprises
   if (!$DB->fieldExists("glpi_enterprises", "email", false)) {
      $query = "ALTER TABLE `glpi_enterprises`
                ADD `email` VARCHAR(255) NOT NULL";
      $DB->queryOrDie($query, "0.6 add email in enterprises");
   }

   // ldap_port for config
   if (!$DB->fieldExists("glpi_config", "ldap_port", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `ldap_port` VARCHAR(10) DEFAULT '389' NOT NULL AFTER `ID` ";
      $DB->queryOrDie($query, "0.6 add ldap_port in config");
   }

   // CAS configuration
   if (!$DB->fieldExists("glpi_config", "cas_host", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `cas_host` VARCHAR(255) NOT NULL ,
                ADD `cas_port` VARCHAR(255) NOT NULL ,
                ADD `cas_uri` VARCHAR(255) NOT NULL ";
      $DB->queryOrDie($query, "0.6 add cas config in config");
   }

   // Limit Item for contracts and correct template bug
   if (!$DB->fieldExists("glpi_contracts", "device_countmax", false)) {
      $query = "ALTER TABLE `glpi_contracts`
                ADD `device_countmax` INT DEFAULT '0' NOT NULL ";
      $DB->queryOrDie($query, "0.6 add device_countmax in contracts");
   }

   if (!$DB->fieldExists("glpi_contract_device", "is_template", false)) {
      $query = "ALTER TABLE `glpi_contract_device`
                ADD `is_template` ENUM('0', '1') DEFAULT '0' NOT NULL ";
      $DB->queryOrDie($query, "0.6 add is_template in contract_device");
   }

   if (!$DB->fieldExists("glpi_doc_device", "is_template", false)) {
      $query = "ALTER TABLE `glpi_doc_device`
                ADD `is_template` ENUM('0', '1') DEFAULT '0' NOT NULL ";
      $DB->queryOrDie($query, "0.6 add is_template in doc_device");

      $query = "ALTER TABLE `glpi_doc_device`
                ADD INDEX (`is_template`) ";
      $DB->queryOrDie($query, "0.6 alter is_template in doc_device");
   }

   // Contract Type to dropdown
   if (!$DB->tableExists("glpi_dropdown_contract_type")) {
      $query = "CREATE TABLE `glpi_dropdown_contract_type` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL default '',
                  PRIMARY KEY  (`ID`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.6 add table dropdown_contract_type");

      $query = "INSERT INTO `glpi_dropdown_contract_type`
                       (`name`)
                VALUES ('".__('Loan')."')";
      $DB->queryOrDie($query, "0.6 add entries to dropdown_contract_type");

      $query = "INSERT INTO `glpi_dropdown_contract_type`
                       (`name`)
                VALUES ('".__('Renting')."')";
      $DB->queryOrDie($query, "0.6 add entries to dropdown_contract_type");

      $query = "INSERT INTO `glpi_dropdown_contract_type`
                       (`name`)
                VALUES ('".__('Leasing')."')";
      $DB->queryOrDie($query, "0.6 add entries to dropdown_contract_type");

      $query = "INSERT INTO `glpi_dropdown_contract_type`
                       (`name`)
                VALUES ('".__('Insurance')."')";
      $DB->queryOrDie($query, "0.6 add entries to dropdown_contract_type");

      $query = "INSERT INTO `glpi_dropdown_contract_type`
                       (`name`)
                VALUES ('".__('Hardware support')."')";
      $DB->queryOrDie($query, "0.6 add entries to dropdown_contract_type");

      $query = "INSERT INTO `glpi_dropdown_contract_type`
                       (`name`)
                VALUES ('".__('Software support')."')";
      $DB->queryOrDie($query, "0.6 add entries to dropdown_contract_type");

      $query = "INSERT INTO `glpi_dropdown_contract_type`
                       (`name`)
                VALUES ('".__('Service provided')."')";
      $DB->queryOrDie($query, "0.6 add entries to dropdown_contract_type");
   }

   //// Update author and assign from tracking / followups
   if (!$DB->fieldExists("glpi_tracking", "assign_type", false)) {

      // Create assin_type field
      $query = "ALTER TABLE `glpi_tracking`
                ADD `assign_type` TINYINT DEFAULT '0' NOT NULL AFTER `assign` ";
      $DB->queryOrDie($query, "0.6 add assign_type in tracking");

      $users = [];
      // Load All users
      $query = "SELECT `ID`, `name`
                FROM `glpi_users`";
      $result = $DB->query($query);
      while ($line = $DB->fetchArray($result)) {
         $users[$line["name"]] = $line["ID"];
      }
      $DB->freeResult($result);

      // Update authors tracking
      $query = "UPDATE `glpi_tracking`
                SET `author` = '0'
                WHERE `author` IS NULL";
      $DB->queryOrDie($query, "0.6 prepare for alter category tracking");

      // Load tracking authors tables
      $authors = [];
      $query   = "SELECT `ID`, `author`
                  FROM `glpi_tracking`";
      $result  = $DB->query($query);
      while ($line = $DB->fetchArray($result)) {
         $authors[$line["ID"]] = $line["author"];
      }
      $DB->freeResult($result);

      if (count($authors)>0) {
         foreach ($authors as $ID => $val) {
            if (isset($users[$val])) {
               $query = "UPDATE `glpi_tracking`
                         SET `author` = '".$users[$val]."'
                         WHERE `ID` = '$ID'";
               $DB->query($query);
            }
         }
      }
      unset($authors);

      $query = "ALTER TABLE `glpi_tracking`
                CHANGE `author` `author` INT(11) DEFAULT '0' NOT NULL";
      $DB->queryOrDie($query, "0.6 alter author in tracking");

      $assign = [];

      // Load tracking assign tables
      $query  = "SELECT `ID`, `assign`
                 FROM `glpi_tracking`";
      $result = $DB->query($query);
      while ($line = $DB->fetchArray($result)) {
         $assign[$line["ID"]] = $line["assign"];
      }
      $DB->freeResult($result);

      if (count($assign)>0) {
         foreach ($assign as $ID => $val) {
            if (isset($users[$val])) {
               $query = "UPDATE `glpi_tracking`
                         SET `assign` = '".$users[$val]."',
                             `assign_type` = '".USER_TYPE."'
                         WHERE `ID` = '$ID'";
               $DB->query($query);
            }
         }
      }
      unset($assign);

      // Update assign tracking
      $query = "ALTER TABLE `glpi_tracking`
                CHANGE `assign` `assign` INT(11) DEFAULT '0' NOT NULL";
      $DB->queryOrDie($query, "0.6 alter assign in tracking");

      $authors = [];
      // Load followup authors tables
      $query  = "SELECT `ID`, `author`
                 FROM `glpi_followups`";
      $result = $DB->query($query);
      while ($line = $DB->fetchArray($result)) {
         $authors[$line["ID"]] = $line["author"];
      }
      $DB->freeResult($result);

      if (count($authors)>0) {
         foreach ($authors as $ID => $val) {
            if (isset($users[$val])) {
               $query = "UPDATE `glpi_followups`
                         SET `author` = '".$users[$val]."'
                         WHERE `ID` = '$ID'";
               $DB->query($query);
            }
         }
      }
      unset($authors);

      // Update authors tracking
      $query = "ALTER TABLE `glpi_followups`
                CHANGE `author` `author` INT(11) DEFAULT '0' NOT NULL";
      $DB->queryOrDie($query, "0.6 alter author in followups");

      // Update Enterprise Tracking
      $query  = "SELECT `computer`, `ID`
                 FROM `glpi_tracking`
                 WHERE `device_type` = '".ENTERPRISE_TYPE."'";
      $result = $DB->query($query);

      if ($DB->numrows($result)>0) {
         while ($line = $DB->fetchArray($result)) {
            $query = "UPDATE `glpi_tracking`
                      SET `assign` = '".$line["computer"]."',
                          `assign_type` = '".ENTERPRISE_TYPE."',
                          `device_type` = '0',
                          `computer` = '0'
                      WHERE `ID` = '".$line["ID"]."'";
            $DB->query($query);
         }
      }
      $DB->freeResult($result);
   }

   // Add planning feature
   if (!$DB->tableExists("glpi_tracking_planning")) {
      $query = "CREATE TABLE `glpi_tracking_planning` (
                  `ID` bigint(20) NOT NULL auto_increment,
                  `id_tracking` int(11) NOT NULL default '0',
                  `id_assign` int(11) NOT NULL default '0',
                  `begin` datetime NOT NULL default '0000-00-00 00:00:00',
                  `end` datetime NOT NULL default '0000-00-00 00:00:00',
                  PRIMARY KEY  (`ID`),
                  KEY `id_tracking` (`id_tracking`),
                  KEY `begin` (`begin`),
                  KEY `end` (`end`)
                ) TYPE=MyISAM ";
      $DB->queryOrDie($query, "0.6 add table glpi_tracking_planning");
   }

   if (!$DB->fieldExists("glpi_config", "planning_begin", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `planning_begin` TIME DEFAULT '08:00:00' NOT NULL";
      $DB->queryOrDie($query, "0.6 add planning begin in config");
   }

   if (!$DB->fieldExists("glpi_config", "planning_end", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `planning_end` TIME DEFAULT '20:00:00' NOT NULL";
      $DB->queryOrDie($query, "0.6 add planning end in config");
   }

   // Merge glpi_users and glpi_prefs
   if (!$DB->fieldExists("glpi_users", "language", false)) {

      // Create fields
      $query = "ALTER TABLE `glpi_users`
                ADD `tracking_order` ENUM('yes', 'no') DEFAULT 'no' NOT NULL ";
      $DB->queryOrDie($query, "0.6 add tracking_order in users");

      $query = "ALTER TABLE `glpi_users`
                ADD `language` VARCHAR(255) NOT NULL DEFAULT 'english'";
      $DB->queryOrDie($query, "0.6 add language in users");

      // Move data
      $query  = "SELECT *
                 FROM `glpi_prefs`";
      $result = $DB->query($query);

      if ($DB->numrows($result)>0) {
         while ($data=$DB->fetchArray($result)) {
            $query2 = "UPDATE `glpi_users`
                       SET `language` = '".$data['language']."',
                            `tracking_order` = '".$data['tracking_order']."'
                       WHERE `name` = '".$data['username']."'";
            $DB->queryOrDie($query2, "0.6 move pref to users");
         }
      }
      $DB->freeResult($result);

      // Drop glpi_prefs
      $query = "DROP TABLE `glpi_prefs`";
      $DB->queryOrDie($query, "0.6 drop glpi_prefs");
   }

   // Create glpi_dropdown_ram_type
   if (!$DB->tableExists("glpi_dropdown_ram_type")) {
      $query = "CREATE TABLE `glpi_dropdown_ram_type` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL default '',
                  PRIMARY KEY  (`ID`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.6 add table glpi_dropdown_ram_type");

      $query = "ALTER TABLE `glpi_device_ram`
                ADD `new_type` INT(11) DEFAULT '0' NOT NULL ";
      $DB->queryOrDie($query, "0.6 create new type field for glpi_device_ram");

      $query = "INSERT INTO `glpi_dropdown_ram_type`
                       (`name`)
                VALUES ('EDO')";
      $DB->queryOrDie($query, "0.6 insert value in glpi_dropdown_ram");

      $query = "INSERT INTO `glpi_dropdown_ram_type`
                       (`name`)
                VALUES ('DDR')";
      $DB->queryOrDie($query, "0.6 insert value in glpi_dropdown_ram");

      $query = "INSERT INTO `glpi_dropdown_ram_type`
                       (`name`)
                VALUES ('SDRAM')";
      $DB->queryOrDie($query, "0.6 insert value in glpi_dropdown_ram");

      $query = "INSERT INTO `glpi_dropdown_ram_type`
                       (`name`)
                VALUES ('SDRAM-2')";
      $DB->queryOrDie($query, "0.6 insert value in glpi_dropdown_ram");

      // Get values
      $query  = "SELECT *
                 FROM `glpi_dropdown_ram_type`";
      $result = $DB->query($query);
      $val    = [];
      while ($data=$DB->fetchArray($result)) {
         $val[$data['name']] = $data['ID'];
      }
      $DB->freeResult($result);

      // Update glpi_device_ram
      $query  = "SELECT *
                 FROM `glpi_device_ram`";
      $result = $DB->query($query);
      if ($DB->numrows($result)>0) {
         while ($data=$DB->fetchArray($result)) {
            $query2 = "UPDATE `glpi_device_ram`
                       SET `new_type` = '".$val[$data['type']]."'
                       WHERE `ID` = '".$data['ID']."'";
            $DB->query($query2);
         }
      }
      $DB->freeResult($result);

      // ALTER glpi_device_ram
      $query = "ALTER TABLE `glpi_device_ram`
                DROP `type`";
      $DB->queryOrDie($query, "0.6 drop type in glpi_dropdown_ram");

      $query = "ALTER TABLE `glpi_device_ram`
                CHANGE `new_type` `type` INT(11) DEFAULT '0' NOT NULL ";
      $DB->queryOrDie($query, "0.6 rename new_type in glpi_dropdown_ram");
   }

   // Create external links
   if (!$DB->tableExists("glpi_links")) {
      $query = "CREATE TABLE `glpi_links` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL default '',
                  PRIMARY KEY  (`ID`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.6 add table glpi_links");
   }

   if (!$DB->tableExists("glpi_links_device")) {
      $query = "CREATE TABLE `glpi_links_device` (
                  `ID` int(11) NOT NULL auto_increment,
                  `FK_links` int(11) NOT NULL default '0',
                  `device_type` int(11) NOT NULL default '0',
                  PRIMARY KEY  (`ID`),
                  KEY `device_type` (`device_type`),
                  KEY `FK_links` (`FK_links`),
                  UNIQUE `device_type_2` (`device_type`,`FK_links`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.6 add table glpi_links_device");
   }

   // Initial count page for printer
   if (!$DB->fieldExists("glpi_printers", "initial_pages", false)) {
      $query = "ALTER TABLE `glpi_printers`
                ADD `initial_pages` VARCHAR(30) DEFAULT '0' NOT NULL ";
      $DB->queryOrDie($query, "0.6 add initial_pages in printers");
   }

   // Auto assign intervention
   if (!$DB->fieldExists("glpi_config", "auto_assign", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `auto_assign` ENUM('0', '1') DEFAULT '0' NOT NULL ";
      $DB->queryOrDie($query, "0.6 add auto_assign in config");
   }

   // Create glpi_dropdown_network
   if (!$DB->tableExists("glpi_dropdown_network")) {
      $query = "CREATE TABLE `glpi_dropdown_network` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL default '',
                  PRIMARY KEY  (`ID`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.6 add table glpi_dropdown_network");
   }

   if (!$DB->fieldExists("glpi_computers", "network", false)) {
      $query = "ALTER TABLE `glpi_computers`
                ADD `network` INT(11) DEFAULT '0' NOT NULL AFTER `location` ";
      $DB->queryOrDie($query, "0.6 a network in computers");
   }

   if (!$DB->fieldExists("glpi_printers", "network", false)) {
      $query = "ALTER TABLE `glpi_printers`
                ADD `network` INT(11) DEFAULT '0' NOT NULL AFTER `location` ";
      $DB->queryOrDie($query, "0.6 add network in printers");
   }

   if (!$DB->fieldExists("glpi_networking", "network", false)) {
      $query = "ALTER TABLE `glpi_networking`
                ADD `network` INT(11) DEFAULT '0' NOT NULL AFTER `location` ";
      $DB->queryOrDie($query, "0.6 a network in networking");
   }

   // Create glpi_dropdown_domain
   if (!$DB->tableExists("glpi_dropdown_domain")) {
      $query = "CREATE TABLE `glpi_dropdown_domain` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL default '',
                  PRIMARY KEY  (`ID`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.6 add table glpi_dropdown_domain");
   }

   if (!$DB->fieldExists("glpi_computers", "domain", false)) {
      $query = "ALTER TABLE `glpi_computers`
                ADD `domain` INT(11) DEFAULT '0' NOT NULL AFTER `location` ";
      $DB->queryOrDie($query, "0.6 a domain in computers");
   }

   if (!$DB->fieldExists("glpi_printers", "domain", false)) {
      $query = "ALTER TABLE `glpi_printers`
                ADD `domain` INT(11) DEFAULT '0' NOT NULL AFTER `location` ";
      $DB->queryOrDie($query, "0.6 a domain in printers");
   }

   if (!$DB->fieldExists("glpi_networking", "domain", false)) {
      $query = "ALTER TABLE `glpi_networking`
                ADD `domain` INT(11) DEFAULT '0' NOT NULL AFTER `location` ";
      $DB->queryOrDie($query, "0.6 a domain in networking");
   }

   // Create glpi_dropdown_vlan
   if (!$DB->tableExists("glpi_dropdown_vlan")) {
      $query = "CREATE TABLE `glpi_dropdown_vlan` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL default '',
                  PRIMARY KEY  (`ID`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.6 add table glpi_dropdown_vlan");
   }

   if (!$DB->tableExists("glpi_networking_vlan")) {
      $query = "CREATE TABLE `glpi_networking_vlan` (
                  `ID` int(11) NOT NULL auto_increment,
                  `FK_port` int(11) NOT NULL default '0',
                  `FK_vlan` int(11) NOT NULL default '0',
                  PRIMARY KEY  (`ID`),
                  KEY `FK_port` (`FK_port`),
                  KEY `FK_vlan` (`FK_vlan`),
                  UNIQUE `FK_port_2` (`FK_port`,`FK_vlan`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.6 add table glpi_networking_vlan");
   }

   // Global Peripherals
   if (!$DB->fieldExists("glpi_peripherals", "is_global", false)) {
      $query = "ALTER TABLE `glpi_peripherals`
                ADD `is_global` ENUM('0', '1') DEFAULT '0' NOT NULL AFTER `FK_glpi_enterprise` ";
      $DB->queryOrDie($query, "0.6 add is_global in peripherals");
   }

   // Global Monitors
   if (!$DB->fieldExists("glpi_monitors", "is_global", false)) {
      $query = "ALTER TABLE `glpi_monitors`
                ADD `is_global` ENUM('0', '1') DEFAULT '0' NOT NULL AFTER `FK_glpi_enterprise` ";
      $DB->queryOrDie($query, "0.6 add is_global in peripherals");
   }

   // Mailing Resa
   if (!$DB->fieldExists("glpi_config", "mailing_resa_admin", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `mailing_resa_admin` VARCHAR(200) NOT NULL DEFAULT '1' AFTER `admin_email` ";
      $DB->queryOrDie($query, "0.6 add mailing_resa_admin in config");
   }

   if (!$DB->fieldExists("glpi_config", "mailing_resa_user", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `mailing_resa_user` VARCHAR(200) NOT NULL DEFAULT '1' AFTER `admin_email` ";
      $DB->queryOrDie($query, "0.6 add mailing_resa_user in config");
   }

   if (!$DB->fieldExists("glpi_config", "mailing_resa_all_admin", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `mailing_resa_all_admin` VARCHAR(200) NOT NULL DEFAULT '0' AFTER `admin_email`";
      $DB->queryOrDie($query, "0.6 add mailing_resa_all_admin in config");
   }

   // Modele ordinateurs
   if (!$DB->tableExists("glpi_dropdown_model")) {
      // model=type pour faciliter la gestion en post mise a jour :
      //   y a plus qu'a deleter les elements non voulu
      // cela conviendra a tout le monde en fonction de l'utilisation du champ type

      $query = "ALTER TABLE `glpi_type_computers`
                RENAME `glpi_dropdown_model` ;";
      $DB->queryOrDie($query, "0.6 rename table glpi_type_computers");

      $query = "CREATE TABLE `glpi_type_computers` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL default '',
                  PRIMARY KEY  (`ID`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.6 add table glpi_type_computers");

      // copie model dans type
      $query  = "SELECT *
                 FROM `glpi_dropdown_model`";
      $result = $DB->query($query);
      if ($DB->numrows($result)>0) {
         while ($data=$DB->fetchArray($result)) {
            $query = "INSERT INTO `glpi_type_computers`
                             (`ID`, `name`)
                      VALUES ('".$data['ID']."', '".addslashes($data['name'])."')";
            $DB->queryOrDie($query, "0.6 insert value in glpi_type_computers");
         }
      }
      $DB->freeResult($result);

      $query = "INSERT INTO `glpi_type_computers`
                       (`name`)
                VALUES ('Server')";
      $DB->queryOrDie($query, "0.6 insert value in glpi_type_computers");

      $serverid = $DB->insertId();

      // Type -> modele
      $query = "ALTER TABLE `glpi_computers`
                CHANGE `type` `model` INT(11) DEFAULT NULL ";
      $DB->queryOrDie($query, "0.6 add model in computers");

      $query = "ALTER TABLE `glpi_computers`
                ADD `type` INT(11) DEFAULT NULL AFTER `model` ";
      $DB->queryOrDie($query, "0.6 add model in computers");

      // Update server values and drop flags_server
      $query = "UPDATE `glpi_computers`
                SET `type` = '$serverid'
                WHERE `flags_server` = '1'";
      $DB->queryOrDie($query, "0.6 update type of computers");

      $query = "ALTER TABLE `glpi_computers`
                DROP `flags_server`;";
      $DB->queryOrDie($query, "0.6 drop type in glpi_dropdown_ram");
   }

   if (!$DB->tableExists("glpi_consumables_type")) {
      $query = "CREATE TABLE `glpi_consumables_type` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL default '',
                  `ref` varchar(255) NOT NULL default '',
                  `location` int(11) NOT NULL default '0',
                  `type` tinyint(4) NOT NULL default '0',
                  `FK_glpi_enterprise` int(11) NOT NULL default '0',
                  `tech_num` int(11) default '0',
                  `deleted` enum('Y','N') NOT NULL default 'N',
                  `comments` text NOT NULL,
                  `alarm` tinyint(4) NOT NULL default '10',
                  PRIMARY KEY  (`ID`),
                  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
                  KEY `tech_num` (`tech_num`),
                  KEY `deleted` (`deleted`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.6 add table glpi_consumables_type");

      $query = "CREATE TABLE `glpi_consumables` (
                  `ID` int(11) NOT NULL auto_increment,
                  `FK_glpi_consumables_type` int(11) default NULL,
                  `date_in` date default NULL,
                  `date_out` date default NULL,
                  PRIMARY KEY  (`ID`),
                  KEY `FK_glpi_cartridges_type` (`FK_glpi_consumables_type`),
                  KEY `date_in` (`date_in`),
                  KEY `date_out` (`date_out`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.6 add table glpi_consumables");

      $query = "CREATE TABLE `glpi_dropdown_consumable_type` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL default '',
                  PRIMARY KEY  (`ID`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.6 add table glpi_dropdown_consumable_type");
   }

   // HDD connect type
   if (!$DB->tableExists("glpi_dropdown_hdd_type")) {
      $query = "CREATE TABLE `glpi_dropdown_hdd_type` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL default '',
                  PRIMARY KEY  (`ID`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.6 add table glpi_dropdown_hdd_type");

      $query = "INSERT INTO `glpi_dropdown_hdd_type`
                       (`name`)
                VALUES ('IDE')";
      $DB->queryOrDie($query, "0.6 insert value in glpi_dropdown_hdd_type");

      $query = "INSERT INTO `glpi_dropdown_hdd_type`
                       (`name`)
                VALUES ('SATA')";
      $DB->queryOrDie($query, "0.6 insert value in glpi_dropdown_hdd_type");

      $query = "INSERT INTO `glpi_dropdown_hdd_type`
                       (`name`)
                VALUES ('SCSI')";
      $DB->queryOrDie($query, "0.6 insert value in glpi_dropdown_hdd_type");

      // Insertion des enum dans l'ordre - le alter garde donc les bonne valeurs
      $query = "ALTER TABLE `glpi_device_hdd`
                CHANGE `interface` `interface` INT(11) DEFAULT '0' NOT NULL";
      $DB->queryOrDie($query, "0.6 alter interface of  glpi_device_hdd");
   }

}
