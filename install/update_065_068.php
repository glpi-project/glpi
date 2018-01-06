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

/// Update from 0.65 to 0.68
function update065to068() {
   global $DB;

   if (!$DB->tableExists("glpi_profiles")) {
      $query = "CREATE TABLE `glpi_profiles` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) default NULL,
                  `interface` varchar(50) NOT NULL default 'helpdesk',
                  `is_default` enum('0','1') NOT NULL default '0',
                  `computer` char(1) default NULL,
                  `monitor` char(1) default NULL,
                  `software` char(1) default NULL,
                  `networking` char(1) default NULL,
                  `printer` char(1) default NULL,
                  `peripheral` char(1) default NULL,
                  `cartridge` char(1) default NULL,
                  `consumable` char(1) default NULL,
                  `phone` char(1) default NULL,
                  `notes` char(1) default NULL,
                  `contact_enterprise` char(1) default NULL,
                  `document` char(1) default NULL,
                  `contract_infocom` char(1) default NULL,
                  `knowbase` char(1) default NULL,
                  `faq` char(1) default NULL,
                  `reservation_helpdesk` char(1) default NULL,
                  `reservation_central` char(1) default NULL,
                  `reports` char(1) default NULL,
                  `ocsng` char(1) default NULL,
                  `dropdown` char(1) default NULL,
                  `device` char(1) default NULL,
                  `typedoc` char(1) default NULL,
                  `link` char(1) default NULL,
                  `config` char(1) default NULL,
                  `search_config` char(1) default NULL,
                  `update` char(1) default NULL,
                  `profile` char(1) default NULL,
                  `user` char(1) default NULL,
                  `group` char(1) default NULL,
                  `logs` char(1) default NULL,
                  `reminder_public` char(1) default NULL,
                  `backup` char(1) default NULL,
                  `create_ticket` char(1) default NULL,
                  `delete_ticket` char(1) default NULL,
                  `comment_ticket` char(1) default NULL,
                  `comment_all_ticket` char(1) default NULL,
                  `update_ticket` char(1) default NULL,
                  `own_ticket` char(1) default NULL,
                  `steal_ticket` char(1) default NULL,
                  `assign_ticket` char(1) default NULL,
                  `show_ticket` char(1) default NULL,
                  `show_full_ticket` char(1) default NULL,
                  `observe_ticket` char(1) default NULL,
                  `show_planning` char(1) default NULL,
                  `show_all_planning` char(1) default NULL,
                  `statistic` char(1) default NULL,
                  `password_update` char(1) default NULL,
                  `helpdesk_hardware` tinyint(2) NOT NULL  DEFAULT '0',
                  `helpdesk_hardware_type` int(11) NOT NULL  DEFAULT '0',
                  PRIMARY KEY  (`ID`),
                  KEY `interface` (`interface`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.68 add profiles");

      $helpdesk_link_type = [COMPUTER_TYPE, MONITOR_TYPE, NETWORKING_TYPE, PERIPHERAL_TYPE,
                                  PHONE_TYPE,PRINTER_TYPE];
      $checksum = 0;
      foreach ($helpdesk_link_type as $val) {
         $checksum += pow(2, $val);
      }

      $query = "INSERT INTO `glpi_profiles`
                VALUES (1, 'post-only', 'helpdesk', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL,
                        NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'r', '1', NULL, NULL, NULL, NULL,
                        NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1',
                        NULL, '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1', NULL, NULL, NULL,
                        '1', '1', '$checksum')";
      $DB->queryOrDie($query, "0.68 add post-only profile");

      $query = "INSERT INTO `glpi_profiles`
                VALUES (2, 'normal', 'central', '0', 'r', 'r', 'r', 'r', 'r', 'r', 'r', 'r', 'r',
                        'r', 'r', 'r', 'r', 'r', 'r', '1', 'r', 'r', NULL, NULL, NULL, 'r', 'r',
                        NULL, NULL, 'r', NULL, 'r', 'r', NULL, NULL, NULL, '1', '1', '1', '0', '0',
                        '1', '0', '0', '1', '0', '1', '1', '0', '1', '1', '1', '$checksum')";
      $DB->queryOrDie($query, "0.68 add normal profile");

      $query = "INSERT INTO `glpi_profiles`
                VALUES (3, 'admin', 'central', '0', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w',
                        'w', 'w', 'w', 'w', 'w', 'w', '1', 'w', 'r', 'w', 'w', 'w', 'w', 'w', NULL,
                        'w', 'r', 'r', 'w', 'w', NULL, NULL, NULL, '1', '1', '1', '1', '1', '1',
                        '1', '1', '1', '1', '1', '1', '1', '1', '1', '3', '$checksum')";
      $DB->queryOrDie($query, "0.68 add admin profile");

      $query = "INSERT INTO `glpi_profiles`
                VALUES (4, 'super-admin', 'central', '0', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w',
                        'w', 'w', 'w', 'w', 'w', 'w', 'w', '1', 'w', 'r', 'w', 'w', 'w', 'w', 'w',
                        'w', 'w', 'r', 'w', 'w', 'w', 'r', 'w', 'w', '1', '1', '1', '1', '1', '1',
                        '1', '1', '1', '1', '1', '1', '1', '1', '1', '3', '$checksum')";
      $DB->queryOrDie($query, "0.68 add super-admin profile");
   }

   if ($DB->fieldExists("glpi_config", "post_only_followup", false)) {
      $query = "SELECT `post_only_followup`
                FROM `glpi_config`
                WHERE `ID` = '1' ";
      $result = $DB->query($query);

      if ($DB->result($result, 0, 0)) {
         $query = "UPDATE `glpi_profiles`
                   SET `comment_ticket` = '1'";
         $DB->queryOrDie($query, "0.68 update default glpi_profiles");
      }

      $query = "ALTER TABLE `glpi_config`
                DROP `post_only_followup`";
      $DB->queryOrDie($query, "0.68 drop post_only_followup in glpi_config");
   }

   $profiles = ["post-only"    => 1,
                     "normal"       => 2,
                     "admin"        => 3,
                     "super-admin"  => 4];

   if (!$DB->tableExists("glpi_users_profiles")) {
      $query = "CREATE TABLE `glpi_users_profiles` (
                  `ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                  `FK_users` INT NOT NULL DEFAULT '0',
                  `FK_profiles` INT NOT NULL DEFAULT '0',
                  KEY `FK_users` (`FK_users`),
                  KEY `FK_profiles` (`FK_profiles`),
                  UNIQUE `FK_users_profiles` (`FK_users`,`FK_profiles`)
                ) TYPE = MYISAM";
      $DB->queryOrDie($query, "0.68 create users_profiles table");

      $query = "SELECT `ID`, `type`
                FROM `glpi_users`";
      $result = $DB->query($query);

      if ($DB->numrows($result)) {
         while ($data=$DB->fetch_array($result)) {
            $query2 = "INSERT INTO `glpi_users_profiles`
                              (`FK_users`, `FK_profiles`)
                       VALUES ('".$data['ID']."', '".$profiles[$data['type']]."')";
            $DB->queryOrDie($query2, "0.68 insert new users_profiles");
         }
      }

      $query = "ALTER TABLE `glpi_users`
                DROP `type`,
                DROP `can_assign_job`";
      $DB->queryOrDie($query, "0.68 drop type and can_assign_job from users");
   }

   if (!$DB->tableExists("glpi_mailing")) {
      $query = "CREATE TABLE `glpi_mailing` (
                  `ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                  `type`  varchar(255) default NULL,
                  `FK_item` INT NOT NULL DEFAULT '0',
                  `item_type` INT NOT NULL DEFAULT '0',
                  KEY `type` (`type`),
                  KEY `FK_item` (`FK_item`),
                  KEY `item_type` (`item_type`),
                  KEY `items` (`item_type`,`FK_item`),
                  UNIQUE `mailings` (`type`,`FK_item`,`item_type`)
                ) TYPE = MYISAM";
      $DB->queryOrDie($query, "0.68 create mailing table");

      $query = "SELECT *
                FROM `glpi_config`
                WHERE `ID` = '1'";
      $result = $DB->query($query);

      if ($result) {
         $data = $DB->fetch_assoc($result);
         if ($data["mailing_resa_all_admin"]) {
            $query2 = "INSERT INTO `glpi_mailing`
                              (`type`, `FK_item`, `item_type`)
                       VALUES ('resa', '".$profiles["admin"]."', '".Notification::PROFILE_TYPE."')";
            $DB->queryOrDie($query2, "0.68 populate mailing resa all admin");
         }

         if ($data["mailing_resa_user"]) {
            $query2 = "INSERT INTO `glpi_mailing`
                              (`type`, `FK_item`, `item_type`)
                       VALUES ('resa', '".Notification::AUTHOR."', '".Notification::USER_TYPE."')";
            $DB->queryOrDie($query2, "0.68 populate mailing resa all admin");
         }

         if ($data["mailing_resa_admin"]) {
            $query2 = "INSERT INTO `glpi_mailing`
                              (`type`, `FK_item`, `item_type`)
                       VALUES ('resa', '".Notification::GLOBAL_ADMINISTRATOR."',
                               '".Notification::USER_TYPE."')";
            $DB->queryOrDie($query2, "0.68 populate mailing resa all admin");
         }

         if ($data["mailing_new_all_admin"]) {
            $query2 = "INSERT INTO `glpi_mailing`
                              (`type`, `FK_item`, `item_type`)
                       VALUES ('new', '".$profiles["admin"]."', '".Notification::PROFILE_TYPE."')";
            $DB->queryOrDie($query2, "0.68 populate mailing new all admin");
         }

         if ($data["mailing_update_all_admin"]) {
            $query2 = "INSERT INTO `glpi_mailing`
                              (`type`, `FK_item`, `item_type`)
                       VALUES ('update', '".$profiles["admin"]."', '".Notification::PROFILE_TYPE."')";
            $DB->queryOrDie($query2, "0.68 populate mailing update all admin");
         }

         if ($data["mailing_followup_all_admin"]) {
            $query2 = "INSERT INTO `glpi_mailing`
                              (`type`, `FK_item`, `item_type`)
                       VALUES ('followup', '".$profiles["admin"]."',
                               '".Notification::PROFILE_TYPE."')";
            $DB->queryOrDie($query2, "0.68 populate mailing followup all admin");
         }

         if ($data["mailing_finish_all_admin"]) {
            $query2 = "INSERT INTO `glpi_mailing`
                              (`type`, `FK_item`, `item_type`)
                       VALUES ('finish', '".$profiles["admin"]."', '".Notification::PROFILE_TYPE."')";
            $DB->queryOrDie($query2, "0.68 populate mailing finish all admin");
         }

         if ($data["mailing_new_all_normal"]) {
            $query2 = "INSERT INTO `glpi_mailing`
                              (`type`, `FK_item`, `item_type`)
                       VALUES ('new', '".$profiles["normal"]."', '".Notification::PROFILE_TYPE."')";
            $DB->queryOrDie($query2, "0.68 populate mailing new all normal");
         }

         if ($data["mailing_update_all_normal"]) {
            $query2 = "INSERT INTO `glpi_mailing`
                              (`type`, `FK_item`, `item_type`)
                       VALUES ('update', '".$profiles["normal"]."',
                               '".Notification::PROFILE_TYPE."')";
            $DB->queryOrDie($query2, "0.68 populate mailing update all normal");
         }

         if ($data["mailing_followup_all_normal"]) {
            $query2 = "INSERT INTO `glpi_mailing`
                              (`type`, `FK_item`, `item_type`)
                       VALUES ('followup', '".$profiles["normal"]."',
                               '".Notification::PROFILE_TYPE."')";
            $DB->queryOrDie($query2, "0.68 populate mailing followup all normal");
         }

         if ($data["mailing_finish_all_normal"]) {
            $query2 = "INSERT INTO `glpi_mailing`
                              (`type`, `FK_item`, `item_type`)
                       VALUES ('finish', '".$profiles["normal"]."',
                               '".Notification::PROFILE_TYPE."')";
            $DB->queryOrDie($query2, "0.68 populate mailing finish all normal");
         }

         if ($data["mailing_new_admin"]) {
            $query2 = "INSERT INTO `glpi_mailing`
                              (`type`, `FK_item`, `item_type`)
                       VALUES ('new', '".Notification::GLOBAL_ADMINISTRATOR."',
                               '".Notification::USER_TYPE."')";
            $DB->queryOrDie($query2, "0.68 populate mailing new admin");
         }

         if ($data["mailing_update_admin"]) {
            $query2 = "INSERT INTO `glpi_mailing`
                              (`type`, `FK_item`, `item_type`)
                       VALUES ('update', '".Notification::GLOBAL_ADMINISTRATOR."',
                               '".Notification::USER_TYPE."')";
            $DB->queryOrDie($query2, "0.68 populate mailing update admin");
         }

         if ($data["mailing_followup_admin"]) {
            $query2 = "INSERT INTO `glpi_mailing`
                              (`type`, `FK_item`, `item_type`)
                       VALUES ('followup', '".Notification::GLOBAL_ADMINISTRATOR."',
                               '".Notification::USER_TYPE."')";
            $DB->queryOrDie($query2, "0.68 populate mailing followup admin");
         }

         if ($data["mailing_finish_admin"]) {
            $query2 = "INSERT INTO `glpi_mailing`
                              (`type`, `FK_item`, `item_type`)
                       VALUES ('finish', '".Notification::GLOBAL_ADMINISTRATOR."',
                               '".Notification::USER_TYPE."')";
            $DB->queryOrDie($query2, "0.68 populate mailing finish admin");
         }

         if ($data["mailing_new_attrib"]) {
            $query2 = "INSERT INTO `glpi_mailing`
                              (`type`, `FK_item`, `item_type`)
                       VALUES ('new', '".Notification::ASSIGN_TECH."',
                               '".Notification::USER_TYPE."')";
            $DB->queryOrDie($query2, "0.68 populate mailing new attrib");
         }

         if ($data["mailing_update_attrib"]) {
            $query2 = "INSERT INTO `glpi_mailing`
                              (`type`, `FK_item`, `item_type`)
                       VALUES ('update', '".Notification::ASSIGN_TECH."',
                               '".Notification::USER_TYPE."')";
            $DB->queryOrDie($query2, "0.68 populate mailing update attrib");
         }

         if ($data["mailing_followup_attrib"]) {
            $query2 = "INSERT INTO `glpi_mailing`
                              (`type`, `FK_item`, `item_type`)
                       VALUES ('followup', '".Notification::ASSIGN_TECH."',
                               '".Notification::USER_TYPE."')";
            $DB->queryOrDie($query2, "0.68 populate mailing followup attrib");
         }

         if ($data["mailing_finish_attrib"]) {
            $query2 = "INSERT INTO `glpi_mailing`
                              (`type`, `FK_item`, `item_type`)
                       VALUES ('finish', '".Notification::ASSIGN_TECH."',
                               '".Notification::USER_TYPE."')";
            $DB->queryOrDie($query2, "0.68 populate mailing finish attrib");
         }

         if ($data["mailing_attrib_attrib"]) {
            $query2 = "INSERT INTO `glpi_mailing`
                              (`type`, `FK_item`, `item_type`)
                       VALUES ('update', '".Notification::OLD_TECH_IN_CHARGE."',
                               '".Notification::USER_TYPE."')";
            $DB->queryOrDie($query2, "0.68 populate mailing finish attrib");
         }

         if ($data["mailing_new_user"]) {
            $query2 = "INSERT INTO `glpi_mailing`
                              (`type`, `FK_item`, `item_type`)
                       VALUES ('new', '".Notification::AUTHOR."', '".Notification::USER_TYPE."')";
            $DB->queryOrDie($query2, "0.68 populate mailing new user");
         }

         if ($data["mailing_update_user"]) {
            $query2 = "INSERT INTO `glpi_mailing`
                              (`type`, `FK_item`, `item_type`)
                       VALUES ('update', '".Notification::AUTHOR."', '".Notification::USER_TYPE."')";
            $DB->queryOrDie($query2, "0.68 populate mailing update user");
         }

         if ($data["mailing_followup_user"]) {
            $query2 = "INSERT INTO `glpi_mailing`
                              (`type`, `FK_item`, `item_type`)
                       VALUES ('followup', '".Notification::AUTHOR."',
                               '".Notification::USER_TYPE."')";
            $DB->queryOrDie($query2, "0.68 populate mailing followup user");
         }

         if ($data["mailing_finish_user"]) {
            $query2 = "INSERT INTO `glpi_mailing`
                              (`type`, `FK_item`, `item_type`)
                       VALUES ('finish', '".Notification::AUTHOR."', '".Notification::USER_TYPE."')";
            $DB->queryOrDie($query2, "0.68 populate mailing finish user");
         }
      }

      $query = " ALTER TABLE `glpi_config`
                 DROP `mailing_resa_all_admin`,
                 DROP `mailing_resa_user`,
                 DROP `mailing_resa_admin`,
                 DROP `mailing_new_admin`,
                 DROP `mailing_update_admin`,
                 DROP `mailing_followup_admin`,
                 DROP `mailing_finish_admin`,
                 DROP `mailing_new_all_admin`,
                 DROP `mailing_update_all_admin`,
                 DROP `mailing_followup_all_admin`,
                 DROP `mailing_finish_all_admin`,
                 DROP `mailing_new_all_normal`,
                 DROP `mailing_update_all_normal`,
                 DROP `mailing_followup_all_normal`,
                 DROP `mailing_finish_all_normal`,
                 DROP `mailing_new_attrib`,
                 DROP `mailing_update_attrib`,
                 DROP `mailing_followup_attrib`,
                 DROP `mailing_finish_attrib`,
                 DROP `mailing_new_user`,
                 DROP `mailing_update_user`,
                 DROP `mailing_followup_user`,
                 DROP `mailing_finish_user`,
                 DROP `mailing_attrib_attrib`";
      $DB->queryOrDie($query, "0.68 delete mailing config from config");
   }

   // Convert old content of knowbase in HTML And add new fields
   if ($DB->tableExists("glpi_kbitems")) {
      if (!$DB->fieldExists("glpi_kbitems", "author", false)) {
         // convert
         $query = "SELECT *
                   FROM `glpi_kbitems` ";
         $result = $DB->query($query);

         if ($DB->numrows($result)>0) {
            while ($line = $DB->fetch_array($result)) {
               $query = "UPDATE `glpi_kbitems`
                         SET `answer` = '".addslashes(rembo($line["answer"]))."'
                         WHERE `ID` = '".$line["ID"]."'";
               $DB->queryOrDie($query, "0.68 convert knowbase to xhtml");
            }
            $DB->free_result($result);
         }

         // add new fields
         $query = "ALTER TABLE `glpi_kbitems`
                   ADD `author` INT( 11 ) NOT NULL DEFAULT '0' AFTER `faq`,
                   ADD `view` INT( 11 ) NOT NULL DEFAULT '0' AFTER `author`,
                   ADD `date` DATETIME NULL DEFAULT NULL AFTER `view`,
                   ADD `date_mod` DATETIME NULL DEFAULT NULL AFTER `date`";
         $DB->queryOrDie($query, "0.68 add  fields in knowbase");
      }
   } // fin convert

   // Add Level To Dropdown
   $dropdowntree_tables = ["glpi_dropdown_kbcategories", "glpi_dropdown_locations"];
   foreach ($dropdowntree_tables as $t) {
      if (!$DB->fieldExists($t, "level", false)) {
         $query = "ALTER TABLE `$t`
                   ADD `level` INT(11)";
         $DB->queryOrDie($query, "0.68 add level to $t");
      }
   }

   if ($DB->fieldExists("glpi_config", "root_doc", false)) {
      $query = "ALTER TABLE `glpi_config`
                DROP `root_doc`";
      $DB->queryOrDie($query, "0.68 drop root_doc");
   }

   // add smtp config
   if (!$DB->fieldExists("glpi_config", "smtp_mode", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `smtp_mode` tinyint(4) DEFAULT '0' NOT NULL,
                ADD `smtp_host` varchar(255),
                ADD `smtp_port` int(11) DEFAULT '25' NOT NULL,
                ADD `smtp_username` varchar(255),
                ADD `smtp_password` varchar(255)";
      $DB->queryOrDie($query, "0.68 add smtp config");
   }

   $map_lang = ["french"       => "fr_FR",
                     "english"      => "en_GB",
                     "deutsch"      => "de_DE",
                     "italian"      => "it_IT",
                     "castillano"   => "es_ES",
                     "portugese"    => "pt_PT",
                     "dutch"        => "nl_NL",
                     "hungarian"    => "hu_HU",
                     "polish"       => "po_PO",
                     "rumanian"     => "ro_RO",
                     "russian"      => "ru_RU"];

   foreach ($map_lang as $old => $new) {
      $query = "UPDATE `glpi_users`
                SET `language` = '$new'
                WHERE `language` = '$old'";
      $DB->queryOrDie($query, "0.68 update $new lang user setting");
   }

   $query = "SELECT `default_language`
             FROM `glpi_config`
             WHERE `ID` = '1'";
   $result   = $DB->query($query);
   $def_lang = $DB->result($result, 0, 0);

   if (isset($map_lang[$def_lang])) {
      $query = "UPDATE `glpi_config`
                SET `default_language` = '".$map_lang[$def_lang]."'
                WHERE `ID` = '1'";
      $DB->queryOrDie($query, "0.68 update default_language in config");
   }

   // Improve link management
   if (!$DB->fieldExists("glpi_links", "link", false)) {
      $query = "ALTER TABLE `glpi_links`
                CHANGE `name` `link` VARCHAR( 255 ) NULL DEFAULT NULL ";
      $DB->queryOrDie($query, "0.68 rename name in link");

      $query = "ALTER TABLE `glpi_links`
                ADD `name` VARCHAR( 255 ) NULL AFTER `ID`";
      $DB->queryOrDie($query, "0.68 add name in link");

      $query = "UPDATE `glpi_links`
                SET `name` = `link`";
      $DB->queryOrDie($query, "0.68 init name field in link");
   }

   if ($DB->fieldExists("glpi_config", "ldap_field_name", false)) {
      $query = "UPDATE `glpi_config`
                SET `ldap_login` = `ldap_field_name` ";
      $DB->query($query);

      $query = "ALTER TABLE `glpi_config`
                DROP `ldap_field_name` ";
      $DB->queryOrDie($query, "0.68 drop ldap_field_name in config");
   }

   // Security user Helpdesk
   $query = "UPDATE `glpi_users`
             SET `password` = '',
                 `active` = '0'
             WHERE `name` = 'Helpdesk'";
   $DB->queryOrDie($query, "0.68 security update for user Helpdesk");

   if (!$DB->fieldExists("glpi_ocs_config", "import_general_name", false)) {
      $query = "ALTER TABLE `glpi_ocs_config`
                ADD `import_general_name` INT( 2 ) NOT NULL DEFAULT '0' AFTER `import_printer`";
      $DB->queryOrDie($query, "0.68 add import_name in ocs_config");
   }

   // Clean default values for devices
   if ($DB->fieldExists("glpi_device_drive", "speed", false)) {
      $query = "ALTER TABLE `glpi_device_drive`
                CHANGE `speed` `speed` VARCHAR( 255 ) NULL ";
      $DB->queryOrDie($query, "0.68 alter speed in device_drive");
   }

   if ($DB->fieldExists("glpi_device_gfxcard", "ram", false)) {
      $query = "ALTER TABLE `glpi_device_gfxcard`
                CHANGE `ram` `ram` VARCHAR( 255 ) NULL ";
      $DB->queryOrDie($query, "0.68 alter ram in device_gfxcard");
   }

   if ($DB->fieldExists("glpi_device_hdd", "rpm", false)) {
      $query = "ALTER TABLE `glpi_device_hdd`
                CHANGE `rpm` `rpm` VARCHAR( 255 ) NULL,
                CHANGE `cache` `cache` VARCHAR( 255 ) NULL ";
      $DB->queryOrDie($query, "0.68 alter rpm and cache in device_hdd");
   }

   if ($DB->fieldExists("glpi_device_iface", "bandwidth", false)) {
      $query = "ALTER TABLE `glpi_device_iface`
                CHANGE `bandwidth` `bandwidth` VARCHAR( 255 ) NULL ";
      $DB->queryOrDie($query, "0.68 alter bandwidth in device_iface");
   }

   if ($DB->fieldExists("glpi_device_moboard", "chipset", false)) {
      $query = "ALTER TABLE `glpi_device_moboard`
                CHANGE `chipset` `chipset` VARCHAR( 255 ) NULL ";
      $DB->queryOrDie($query, "0.68 alter chipset in device_moboard");
   }

   if ($DB->fieldExists("glpi_device_drive", "speed", false)) {
      $query = "ALTER TABLE `glpi_device_drive`
                CHANGE `speed` `speed` VARCHAR( 255 ) NULL ";
      $DB->queryOrDie($query, "0.68 alter speed in device_drive");
   }

   if ($DB->fieldExists("glpi_device_power", "power", false)) {
      $query = "ALTER TABLE `glpi_device_power`
                CHANGE `power` `power` VARCHAR( 255 ) NULL ";
      $DB->queryOrDie($query, "0.68 alter power in device_power");
   }

   if ($DB->fieldExists("glpi_device_ram", "frequence", false)) {
      $query = "ALTER TABLE `glpi_device_ram`
                CHANGE `frequence` `frequence` VARCHAR( 255 ) NULL ";
      $DB->queryOrDie($query, "0.68 alter frequence in device_ram");
   }

   if ($DB->fieldExists("glpi_device_sndcard", "type", false)) {
      $query = "ALTER TABLE `glpi_device_sndcard`
                CHANGE `type` `type` VARCHAR( 255 ) NULL ";
      $DB->queryOrDie($query, "0.68 alter type in device_sndcard");
   }

   if (!$DB->fieldExists("glpi_display", "FK_users", false)) {
      $query = "ALTER TABLE `glpi_display`
                ADD `FK_users` INT NOT NULL DEFAULT '0'";
      $DB->queryOrDie($query, "0.68 alter display add FK_users");

      $query = "ALTER TABLE `glpi_display`
                DROP INDEX `type_2`,
                ADD UNIQUE `type_2` (`type`, `num`, `FK_users`)";
      $DB->queryOrDie($query, "0.68 alter display update unique key");
   }

   // Proxy configuration
   if (!$DB->fieldExists("glpi_config", "proxy_name", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `proxy_name` VARCHAR( 255 ) NULL,
                ADD `proxy_port` VARCHAR( 255 ) DEFAULT '8080' NOT NULL,
                ADD `proxy_user` VARCHAR( 255 ) NULL,
                ADD `proxy_password` VARCHAR( 255 ) NULL";
      $DB->queryOrDie($query, "0.68 add proxy fields to glpi_config");
   }

   // Log update with followups
   if (!$DB->fieldExists("glpi_config", "followup_on_update_ticket", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `followup_on_update_ticket` tinyint(4) DEFAULT '1' NOT NULL";
      $DB->queryOrDie($query, "0.68 add followup_on_update_ticket to glpi_config");
   }

   // Ticket Category -> Tree mode
   if (!$DB->fieldExists("glpi_dropdown_tracking_category", "completename", false)) {
      $query = "ALTER TABLE glpi_dropdown_tracking_category
                ADD `parentID` INT NOT NULL DEFAULT '0' AFTER `ID`,
                ADD `completename` TEXT NULL AFTER `name`,
                ADD `level` INT NULL AFTER `comments` ";
      $DB->queryOrDie($query, "0.68 glpi_dropdown_tracking_category to dropdown tree");
   }

   // User to Document
   if (!$DB->fieldExists("glpi_docs", "FK_users", false)) {
      $query = "ALTER TABLE `glpi_docs`
                ADD `FK_users` int DEFAULT '0' NOT NULL,
                ADD `FK_tracking` int DEFAULT '0' NOT NULL";
      $DB->queryOrDie($query, "0.68 add FK_users to docs");
   }

   // Import Ocs TAG
   if (!$DB->fieldExists("glpi_ocs_config", "import_tag_field", false)) {
      $query = "ALTER TABLE `glpi_ocs_config`
                ADD `import_tag_field` varchar( 255 ) NULL";
      $DB->queryOrDie($query, "0.68 add import_tag_field to ocs_config");
   }

   // Use ocs soft dict
   if (!$DB->fieldExists("glpi_ocs_config", "use_soft_dict", false)) {
      $query = "ALTER TABLE `glpi_ocs_config`
                ADD `use_soft_dict` char( 1 ) DEFAULT '1'";
      $DB->queryOrDie($query, "0.68 add use_soft_dict to ocs_config");
   }

   // Link user and group to hardware
   $new_link = ["computers", "monitors", "networking", "peripherals", "phones", "printers",
                     "software"];

   foreach ($new_link as $table) {
      if (!$DB->fieldExists("glpi_$table", "FK_users", false)) {
         $query = "ALTER TABLE `glpi_$table`
                   ADD `FK_users` INT(11) DEFAULT '0',
                   ADD `FK_groups` INT(11) DEFAULT '0'";
         $DB->queryOrDie($query, "0.65 add link user group field in $table");

         if ($table != "software") {
            // Update using name field of users
            $query2 = "SELECT `glpi_users`.`ID` AS USER,
                              `glpi_$table`.`ID` AS ID
                       FROM `glpi_$table`
                       LEFT JOIN `glpi_users` ON (`glpi_$table`.`contact` = `glpi_users`.`name`
                                                  AND `glpi_$table`.`contact` <> '')
                       WHERE `glpi_users`.`ID` IS NOT NULL";
            $result2 = $DB->query($query2);

            if ($DB->numrows($result2)>0) {
               while ($data=$DB->fetch_assoc($result2)) {
                  $query3 = "UPDATE `glpi_$table`
                             SET `FK_users` = '".$data["USER"]."'
                             WHERE `ID` = '".$data["ID"]."'";
                  $DB->query($query3);
               }
            }

            // Update using realname field of users
            $query2 = "SELECT `glpi_users`.`ID` AS USER,
                              `glpi_$table`.`ID` AS ID
                       FROM `glpi_$table`
                       LEFT JOIN `glpi_users` ON (`glpi_$table`.`contact` = `glpi_users`.`realname`
                                                  AND `glpi_$table`.`contact` <> '')
                       WHERE `glpi_users`.`ID` IS NOT NULL
                             AND `glpi_$table`.`FK_users` ='0' ";
            $result2 = $DB->query($query2);

            if ($DB->numrows($result2)>0) {
               while ($data=$DB->fetch_assoc($result2)) {
                  $query3 = "UPDATE `glpi_$table`
                             SET `FK_users` = '".$data["USER"]."'
                             WHERE `ID` = '".$data["ID"]."'";
                  $DB->query($query3);
               }
            }
         }
      }
   }

   //// Group management
   // Manage old plugin table
   if ($DB->tableExists("glpi_groups") && $DB->fieldExists("glpi_groups", "extend", false)) {
      $query = "ALTER TABLE `glpi_groups`
                RENAME `glpi_plugin_droits_groups`";
      $DB->queryOrDie($query, "0.68 rename plugin groups table");
   }

   if (!$DB->tableExists("glpi_groups")) {
      $query = "CREATE TABLE `glpi_groups` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) default NULL,
                  `comments` text,
                  `ldap_field` varchar(255) default NULL,
                  `ldap_value` varchar(255) default NULL,
                  PRIMARY KEY  (`ID`),
                  KEY `name` (`name`),
                  KEY `ldap_field` (`ldap_field`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.68 add groups");

      $query = "INSERT INTO `glpi_display`
                       (`type`, `num`, `rank`, `FK_users`)
                VALUES ('".GROUP_TYPE."', '16', '1', '0')";
      $DB->queryOrDie($query, "0.68 add groups search config");
   }

   if (!$DB->tableExists("glpi_users_groups")) {
      $query = "CREATE TABLE `glpi_users_groups` (
                  `ID` int(11) NOT NULL auto_increment,
                  `FK_users` int(11) default '0',
                  `FK_groups` int(11) default '0',
                  PRIMARY KEY  (`ID`),
                  UNIQUE KEY `FK_users` (`FK_users`,`FK_groups`),
                  KEY `FK_users_2` (`FK_users`),
                  KEY `FK_groups` (`FK_groups`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.68 add users_groups");
   }

   if (!$DB->fieldExists("glpi_config", "ldap_field_group", false)) {
      $query = "ALTER TABLE  `glpi_config`
                ADD `ldap_field_group` varchar(255) NULL";
      $DB->queryOrDie($query, "0.68 add ldap_field_group in config");
   }

   if (!$DB->fieldExists("glpi_tracking", "request_type", false)) {
      $query = "ALTER TABLE  `glpi_tracking`
                ADD `request_type` tinyint(2) DEFAULT '0' AFTER `author`";
      $DB->queryOrDie($query, "0.68 add request_type in tracking");
   }

   // History update for software
   if ($DB->fieldExists("glpi_history", "device_internal_action", false)) {
      $query = "ALTER TABLE `glpi_history`
                CHANGE `device_internal_action` `linked_action` TINYINT( 4 ) NULL DEFAULT '0'";
      $DB->queryOrDie($query, "0.68 alater glpi_history");
   }

   if (!$DB->tableExists("glpi_alerts")) {
      $query = "CREATE TABLE `glpi_alerts` (
                  `ID` int(11) NOT NULL auto_increment,
                  `device_type` int(11) default '0',
                  `FK_device` int(11) default '0',
                  `type` int(11) default '0',
                  `date` timestamp NULL default CURRENT_TIMESTAMP,
                  PRIMARY KEY  (`ID`),
                  UNIQUE KEY `alert` (`device_type`,`FK_device`,`type`),
                  KEY `item` (`device_type`,`FK_device`),
                  KEY `device_type` (`device_type`),
                  KEY `FK_device` (`FK_device`),
                  KEY `type` (`type`),
                  KEY `date` (`date`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.68 add alerts");
   }

   if (!$DB->fieldExists("glpi_contracts", "alert", false)) {
      $query = "ALTER TABLE `glpi_contracts`
                ADD `alert` tinyint(2) NOT NULL DEFAULT '0'";
      $DB->queryOrDie($query, "0.68 add alert in contracts");
   }

   if (!$DB->fieldExists("glpi_infocoms", "alert", false)) {
      $query = "ALTER TABLE `glpi_infocoms`
                ADD `alert` tinyint(2) NOT NULL DEFAULT '0'";
      $DB->queryOrDie($query, "0.68 add alert in infocoms");
   }

   if (!$DB->fieldExists("glpi_config", "contract_alerts", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `contract_alerts` tinyint(2) NOT NULL  DEFAULT '0'";
      $DB->queryOrDie($query, "0.68 add contract_alerts in config");
   }

   if (!$DB->fieldExists("glpi_config", "infocom_alerts", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `infocom_alerts` tinyint(2) NOT NULL DEFAULT '0'";
      $DB->queryOrDie($query, "0.68 add infocom_alerts in config");
   }

   if (!$DB->fieldExists("glpi_tracking", "FK_group", false)) {
      $query = "ALTER TABLE `glpi_tracking`
                ADD `FK_group` int(11) NOT NULL  DEFAULT '0' AFTER `author`";
      $DB->queryOrDie($query, "0.68 add FK_group in tracking");
   }

   if (!$DB->fieldExists("glpi_config", "cartridges_alert", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `cartridges_alert` int(11) NOT NULL  DEFAULT '0'";
      $DB->queryOrDie($query, "0.68 add cartridges_alert in config");
   }

   if (!$DB->fieldExists("glpi_config", "consumables_alert", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `consumables_alert` int(11) NOT NULL  DEFAULT '0'";
      $DB->queryOrDie($query, "0.68 add consumables_alert in config");
   }

   if (!$DB->fieldExists("glpi_contacts", "firstname", false)) {
      $que = "ALTER TABLE `glpi_contacts`
              ADD `firstname` varchar(255)  DEFAULT '' AFTER `name`";
      $DB->queryOrDie($query, "0.68 add firstname in contacts");
   }

   if (!$DB->fieldExists("glpi_contacts", "mobile", false)) {
      $query = "ALTER TABLE `glpi_contacts`
                ADD `mobile` varchar(255)  DEFAULT '' AFTER `phone2`";
      $DB->queryOrDie($query, "0.68 add mobile in contacts");
   }

   if (!$DB->fieldExists("glpi_enterprises", "country", false)) {
      $query = "ALTER TABLE `glpi_enterprises`
                ADD `country` varchar(255)  DEFAULT '' AFTER `address`";
      $DB->queryOrDie($query, "0.68 add country in enterprises");
   }

   if (!$DB->fieldExists("glpi_enterprises", "state", false)) {
      $query = "ALTER TABLE `glpi_enterprises`
                ADD `state` varchar(255)  DEFAULT '' AFTER `address`";
      $DB->queryOrDie($query, "0.68 add state in enterprises");
   }

   if (!$DB->fieldExists("glpi_enterprises", "town", false)) {
      $query = "ALTER TABLE `glpi_enterprises`
                ADD `town` varchar(255)  DEFAULT '' AFTER `address`";
      $DB->queryOrDie($query, "0.68 add town in enterprises");
   }

   if (!$DB->fieldExists("glpi_enterprises", "postcode", false)) {
      $query = "ALTER TABLE `glpi_enterprises`
                ADD `postcode` varchar(255)  DEFAULT '' AFTER `address`";
      $DB->queryOrDie($query, "0.68 add postcode in enterprises");
   }

   if (!$DB->fieldExists("glpi_contracts", "renewal", false)) {
      $query = "ALTER TABLE `glpi_contracts`
                ADD `renewal` tinyint(2) NOT NULL DEFAULT '0'";
      $DB->queryOrDie($query, "0.68 add renewal in contracts");
   }

   // Update contract periodicity and facturation
   $values = ["4" => "6",
                   "5" => "12",
                   "6" => "24"];

   foreach ($values as $key => $val) {
      $query = "UPDATE `glpi_contracts`
                SET `periodicity` = '$val'
                WHERE `periodicity` = '$key'";
      $DB->queryOrDie($query, "0.68 update contract periodicity value");

      $query = "UPDATE `glpi_contracts`
                SET `facturation` = '$val'
                WHERE `facturation` = '$key'";
      $DB->queryOrDie($query, "0.68 update contract facturation value");
   }

   // Add user fields
   if (!$DB->fieldExists("glpi_users", "mobile", false)) {
      $query = "ALTER TABLE `glpi_users`
                ADD `mobile` varchar(255)  DEFAULT '' AFTER `phone`";
      $DB->queryOrDie($query, "0.68 add mobile in users");
   }

   if (!$DB->fieldExists("glpi_users", "phone2", false)) {
      $query = "ALTER TABLE `glpi_users`
                ADD `phone2` varchar(255)  DEFAULT '' AFTER `phone`";
      $DB->queryOrDie($query, "0.68 add phone2 in users");
   }

   if (!$DB->fieldExists("glpi_users", "firstname", false)) {
      $query = "ALTER TABLE `glpi_users`
                ADD `firstname` varchar(255)  DEFAULT '' AFTER `realname`";
      $DB->queryOrDie($query, "0.68 add firstname in users");
   }

   if (!$DB->fieldExists("glpi_users", "comments", false)) {
      $query = "ALTER TABLE `glpi_users`
                ADD `comments` TEXT";
      $DB->queryOrDie($query, "0.68 add comments in users");
   }

   if (!$DB->fieldExists("glpi_config", "ldap_field_firstname", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `ldap_field_firstname` varchar(200)  DEFAULT 'givenname'
                     AFTER `ldap_field_realname`";
      $DB->queryOrDie($query, "0.68 add ldap_field_firstname in config");
   }

   if (!$DB->fieldExists("glpi_config", "ldap_field_mobile", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `ldap_field_mobile` varchar(200)  DEFAULT 'mobile' AFTER `ldap_field_phone`";
      $DB->queryOrDie($query, "0.68 add ldap_mobile in config");
   }

   if (!$DB->fieldExists("glpi_config", "ldap_field_phone2", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `ldap_field_phone2` varchar(200)  DEFAULT 'homephone' AFTER `ldap_field_phone`";
      $DB->queryOrDie($query, "0.68 add ldap_field_phone2 in config");
   }

} // fin 0.68 #####################################################################################



/**
 * NOT USED IN CORE - Used for update process - Replace bbcode in text by html tag
 * used in update_065_068.php
 *
 * @param $string string: initial string
 *
 * @return formatted string
**/
function rembo($string) {

   // Adapte de PunBB
   //Copyright (C)  Rickard Andersson (rickard@punbb.org)

   // If the message contains a code tag we have to split it up
   // (text within [code][/code] shouldn't be touched)
   if (strpos($string, '[code]') !== false
       && strpos($string, '[/code]') !== false) {

      list($inside, $outside) = split_text($string, '[code]', '[/code]');
      $outside                = array_map('trim', $outside);
      $string                 = implode('<">', $outside);
   }

   $pattern = ['#\[b\](.*?)\[/b\]#s',
                    '#\[i\](.*?)\[/i\]#s',
                    '#\[u\](.*?)\[/u\]#s',
                    '#\[s\](.*?)\[/s\]#s',
                    '#\[c\](.*?)\[/c\]#s',
                    '#\[g\](.*?)\[/g\]#s',
                    '#\[email\](.*?)\[/email\]#',
                    '#\[email=(.*?)\](.*?)\[/email\]#',
                    '#\[color=([a-zA-Z]*|\#?[0-9a-fA-F]{6})](.*?)\[/color\]#s'];

   $replace = ['<span class="b">$1</span>',
                    '<em>$1</em>',
                    '<span class="souligne">$1</span>',
                    '<span class="barre">$1</span>',
                    '<div class="center">$1</div>',
                    '<big>$1</big>',
                    '<a href="mailto:$1">$1</a>',
                    '<a href="mailto:$1">$2</a>',
                    '<span style="color: $1">$2</span>'];

   // This thing takes a while! :)
   $string = preg_replace($pattern, $replace, $string);
   $string = clicurl($string);
   $string = autop($string);

   // If we split up the message before we have to concatenate it together again (code tags)
   if (isset($inside)) {
      $outside    = explode('<">', $string);
      $string     = '';
      $num_tokens = count($outside);

      for ($i = 0; $i < $num_tokens; ++$i) {
         $string .= $outside[$i];
         if (isset($inside[$i])) {
            $string .= '<br><br><div class="spaced"><table class="code center"><tr>' .
                       '<td class="punquote"><span class="b">Code:</span><br><br><pre>'.
                       trim($inside[$i]).'</pre></td></tr></table></div>';
         }
      }
   }
   return $string;
}


/**
 * Rend une url cliquable htp/https/ftp meme avec une variable Get
 *
 * @param $chaine
 *
 * @return $string
**/
function clicurl($chaine) {

   $text = preg_replace("`((?:https?|ftp)://\S+)(\s|\z)`", '<a href="$1">$1</a>$2', $chaine);
   return $text;
}


/**
 * Met en "ordre" une chaine avant affichage
 * Remplace tr??s AVANTAGEUSEMENT nl2br
 *
 * @param $pee
 * @param $br
 *
 * @return $string
**/
function autop($pee, $br = 1) {

   // Thanks  to Matthew Mullenweg

   $pee = preg_replace("/(\r\n|\n|\r)/", "\n", $pee); // cross-platform newlines
   $pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates
   $pee = preg_replace('/\n?(.+?)(\n\n|\z)/s', "<p>$1</p>\n", $pee); // make paragraphs, including one at the end

   if ($br) {
      $pee = preg_replace('|(?<!</p>)\s*\n|', "<br>\n", $pee); // optionally make line breaks
   }
   return $pee;
}


/**
 * Split the message into tokens ($inside contains all text inside $start and $end, and $outside contains all text outside)
 *
 * @param $text
 * @param $start
 * @param $end
 *
 * @return array
**/
function split_text($text, $start, $end) {

   // Adapt?? de PunBB
   //Copyright (C)  Rickard Andersson (rickard@punbb.org)

   $tokens     = explode($start, $text);
   $outside[]  = $tokens[0];
   $num_tokens = count($tokens);

   for ($i=1; $i<$num_tokens; ++$i) {
      $temp      = explode($end, $tokens[$i]);
      $inside[]  = $temp[0];
      $outside[] = $temp[1];
   }

   return [$inside, $outside];
}

