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

/// Update from 0.6 to 0.65
function update06to065() {
   global $DB;

       echo "<p class='center'>Version 0.65 </p>";

   if (!isIndex("glpi_networking_ports", "on_device_2")) {
      $query = "ALTER TABLE `glpi_networking_ports`
                ADD INDEX (`on_device`) ";
      $DB->queryOrDie($query, "0.65");
   }

   if (!isIndex("glpi_networking_ports", "device_type")) {
      $query = "ALTER TABLE `glpi_networking_ports`
                ADD INDEX (`device_type`) ";
      $DB->queryOrDie($query, "0.65");
   }

   if (!isIndex("glpi_computer_device", "FK_device")) {
      $query = "ALTER TABLE `glpi_computer_device`
                ADD INDEX (`FK_device`) ";
      $DB->queryOrDie($query, "0.65");
   }

   // Field for public FAQ
   if (!$DB->fieldExists("glpi_config", "public_faq", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `public_faq` ENUM( '0', '1' ) NOT NULL AFTER `auto_assign` ";
      $DB->queryOrDie($query, "0.65 add public_faq in config");
   }

   // Optimize amort_type field
   if ($DB->fieldExists("glpi_infocoms", "amort_type", false)) {
      $query2 = "UPDATE `glpi_infocoms`
                 SET `amort_type` = '0'
                 WHERE `amort_type` = ''";
      $DB->queryOrDie($query2, "0.65 update amort_type='' in tracking");

      $query = "ALTER TABLE `glpi_infocoms`
                CHANGE `amort_type` `amort_type` tinyint(4) NOT NULL DEFAULT '0'";
      $DB->queryOrDie($query, "0.65 alter amort_type in infocoms");
   }

   if (!$DB->tableExists("glpi_display")) {
      $query = "CREATE TABLE `glpi_display` (
                  `ID` int(11) NOT NULL auto_increment,
                  `type` smallint(6) NOT NULL default '0',
                  `num` smallint(6) NOT NULL default '0',
                  `rank` smallint(6) NOT NULL default '0',
                  PRIMARY KEY (`ID`),
                  UNIQUE KEY `type_2` (`type`, `num`),
                  KEY `type` (`type`),
                  KEY `rank` (`rank`),
                  KEY `num` (`num`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.65 add glpi_display table");

      // TEMPORARY : ADD ITEMS TO DISPLAY TABLE : TO DEL OR TO
      $query = "INSERT INTO `glpi_display`
                VALUES (32, 1, 4, 4),
                       (34, 1, 6, 6),
                       (33, 1, 5, 5),
                       (31, 1, 8, 3),
                       (30, 1, 23, 2),
                       (86, 12, 3, 1),
                       (49, 4, 31, 1),
                       (50, 4, 23, 2),
                       (51, 4, 3, 3),
                       (52, 4, 4, 4),
                       (44, 3, 31, 1),
                       (38, 2, 31, 1),
                       (39, 2, 23, 2),
                       (45, 3, 23, 2),
                       (46, 3, 3, 3),
                       (63, 6, 4, 3),
                       (62, 6, 5, 2),
                       (61, 6, 23, 1),
                       (83, 11, 4, 2),
                       (82, 11, 3, 1),
                       (57, 5, 3, 3),
                       (56, 5, 23, 2),
                       (55, 5, 31, 1),
                       (29, 1, 31, 1),
                       (35, 1, 3, 7),
                       (36, 1, 19, 8),
                       (37, 1, 17, 9),
                       (40, 2, 3, 3),
                       (41, 2, 4, 4),
                       (42, 2, 11, 6),
                       (43, 2, 9, 7),
                       (47, 3, 4, 4),
                       (48, 3, 9, 6),
                       (53, 4, 9, 6),
                       (54, 4, 7, 7),
                       (58, 5, 4, 4),
                       (59, 5, 9, 6),
                       (60, 5, 7, 7),
                       (64, 7, 3, 1),
                       (65, 7, 4, 2),
                       (66, 7, 5, 3),
                       (67, 7, 6, 4),
                       (68, 7, 9, 5),
                       (69, 8, 9, 1),
                       (70, 8, 3, 2),
                       (71, 8, 4, 3),
                       (72, 8, 5, 4),
                       (73, 8, 10, 5),
                       (74, 8, 6, 6),
                       (75, 10, 4, 1),
                       (76, 10, 3, 2),
                       (77, 10, 5, 3),
                       (78, 10, 6, 4),
                       (79, 10, 7, 5),
                       (80, 10, 11, 6),
                       (84, 11, 5, 3),
                       (85, 11, 6, 4),
                       (88, 12, 6, 2),
                       (89, 12, 4, 3),
                       (90, 12, 5, 4),
                       (91, 13, 3, 1),
                       (92, 13, 4, 2),
                       (93, 13, 7, 3),
                       (94, 13, 5, 4),
                       (95, 13, 6, 5),
                       (96, 15, 3, 1),
                       (97, 15, 4, 2),
                       (98, 15, 5, 3),
                       (99, 15, 6, 4),
                       (100, 15, 7, 5),
                       (101, 17, 3, 1),
                       (102, 17, 4, 2),
                       (103, 17, 5, 3),
                       (104, 17, 6, 4),
                       (105, 2, 40, 5),
                       (106, 3, 40, 5),
                       (107, 4, 40, 5),
                       (108, 5, 40, 5),
                       (109, 15, 8, 6),
                       (110, 23, 31, 1),
                       (111, 23, 23, 2),
                       (112, 23, 3, 3),
                       (113, 23, 4, 4),
                       (114, 23, 40, 5),
                       (115, 23, 9, 6),
                       (116, 23, 7, 7)";
      $DB->query($query);
   }

   if (!$DB->fieldExists("glpi_config", "ldap_login", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `ldap_login` VARCHAR( 200 ) NOT NULL DEFAULT 'uid' AFTER `ldap_condition`";
      $DB->queryOrDie($query, "0.65 add url in config");
   }

   if (!$DB->fieldExists("glpi_config", "url_base", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `url_base` VARCHAR( 255 ) NOT NULL ";
      $DB->queryOrDie($query, "0.65 add url in config");

      $query = "ALTER TABLE `glpi_config`
                ADD `url_in_mail` ENUM( '0', '1' ) NOT NULL ";
      $DB->queryOrDie($query, "0.65 add url_in_mail in config");

      $query = "UPDATE `glpi_config`
                SET `url_base` = '".str_replace("/install.php", "", $_SERVER['HTTP_REFERER'])."'
                WHERE `ID` = '1'";
      $DB->queryOrDie($query, " url");
   }

   if (!$DB->fieldExists("glpi_config", "text_login", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `text_login` TEXT NOT NULL ";
      $DB->queryOrDie($query, "0.65 add text_login in config");
   }

   if (!$DB->fieldExists("glpi_config", "auto_update_check", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `auto_update_check` SMALLINT DEFAULT '0' NOT NULL ,
                ADD `last_update_check` DATE DEFAULT '".date("Y-m-d")."' NOT NULL,
                ADD `founded_new_version` VARCHAR( 10 ) NOT NULL ";
      $DB->queryOrDie($query, "0.65 add auto_login_check in config");
   }

   //// Tracking
   if ($DB->fieldExists("glpi_tracking", "status", false)) {
      $already_done = false;
      if ($result = $DB->query("show fields from glpi_tracking")) {
         while ($data=$DB->fetch_array($result)) {
            if ($data["Field"]=="status" && strstr($data["Type"], "done")) {
               $already_done = true;
            }
         }
      }

      if (!$already_done) {
         $query = "ALTER TABLE `glpi_tracking`
                   CHANGE `status` `status` ENUM('new', 'old', 'old_done', 'assign', 'plan',
                                                 'old_notdone', 'waiting') DEFAULT 'new' NOT NULL";
         $DB->queryOrDie($query, "0.65 alter status in tracking");

         $query2 = "UPDATE `glpi_tracking`
                    SET `status` = 'old_done'
                    WHERE `status` <> 'new'";
         $DB->queryOrDie($query2, "0.65 update status=old in tracking");

         $query3 = "UPDATE `glpi_tracking`
                    SET `status` = 'assign'
                    WHERE `status` = 'new'
                          AND `assign` <> '0'";
         $DB->queryOrDie($query3, "0.65 update status=assign in tracking");

         $query4 = "ALTER TABLE `glpi_tracking`
                    CHANGE `status` `status` ENUM('new', 'old_done', 'assign', 'plan', 'old_notdone',
                                                  'waiting') DEFAULT 'new' NOT NULL";
         $DB->queryOrDie($query4, "0.65 alter status in tracking");
      }
   }

   if (!isIndex("glpi_tracking_planning", "id_assign")) {
      $query = "ALTER TABLE `glpi_tracking_planning`
                ADD INDEX ( `id_assign` ) ";
      $DB->queryOrDie($query, "0.65 add index for id_assign in tracking_planning");
   }

   if ($DB->fieldExists("glpi_tracking", "emailupdates", false)) {
      $query2 = " UPDATE `glpi_tracking`
                  SET `emailupdates` = 'no'
                  WHERE `emailupdates` = ''";
      $DB->queryOrDie($query2, "0.65 update emailupdate='' in tracking");

      $query = "ALTER TABLE `glpi_tracking`
                CHANGE `emailupdates` `emailupdates` ENUM('yes', 'no') DEFAULT 'no' NOT NULL";
      $DB->queryOrDie($query, "0.65 alter emailupdates in tracking");
   }

   if (!$DB->fieldExists("glpi_followups", "private", false)) {
      $query = "ALTER TABLE `glpi_followups`
                ADD `private` INT( 1 ) DEFAULT '0' NOT NULL";
      $DB->queryOrDie($query, "0.65 add private in followups");
   }

   if (!$DB->fieldExists("glpi_followups", "realtime", false)) {
      $query = "ALTER TABLE `glpi_followups`
                ADD `realtime` FLOAT DEFAULT '0' NOT NULL ";
      $DB->queryOrDie($query, "0.65 add realtime in followups");
   }

   if (!$DB->fieldExists("glpi_config", "mailing_attrib_attrib", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `mailing_attrib_attrib` tinyint(4) NOT NULL DEFAULT '1' AFTER `mailing_finish_user`";
      $DB->queryOrDie($query, "0.65 add mailing_attrib_attrib in config");
   }

   if (!$DB->fieldExists("glpi_tracking_planning", "id_followup", false)) {
      $query = "ALTER TABLE `glpi_tracking_planning`
                ADD `id_followup` INT DEFAULT '0' NOT NULL AFTER `id_tracking` ";
      $DB->queryOrDie($query, "0.65 add id_followup in tracking_planning");

      $query = "ALTER TABLE `glpi_tracking_planning`
                ADD INDEX (`id_followup`)";
      $DB->queryOrDie($query, "0.65 add index for id_followup in tracking_planning");

      //// Move Planned item to followup
      // Get super-admin ID
      $suid   = 0;
      $query0 = "SELECT `ID`
                 FROM `glpi_users`
                 WHERE `type` = 'super-admin'";
      $result0 = $DB->query($query0);
      if ($DB->numrows($result0)>0) {
         $suid = $DB->result($result0, 0, 0);
      }
      $DB->free_result($result0);

      $query = "SELECT *
                FROM `glpi_tracking_planning`
                ORDER BY `id_tracking`";
      $result = $DB->query($query);

      $used_followups = [];
      if ($DB->numrows($result)>0) {
         while ($data=$DB->fetch_array($result)) {
            $found = -1;
            // Is a followup existing ?
            $query2 = "SELECT *
                       FROM `glpi_followups`
                       WHERE `tracking` = '".$data["id_tracking"]."'";
            $result2 = $DB->query($query2);
            if ($DB->numrows($result2)>0) {
               while ($found<0 && $data2=$DB->fetch_array($result2)) {
                  if (!in_array($data2['ID'], $used_followups)) {
                     $found = $data2['ID'];
                  }
               }
            }
            $DB->free_result($result2);

            // Followup not founded
            if ($found<0) {
               $query3 = "INSERT INTO `glpi_followups`
                                 (`tracking`, `date`, `author`, `contents`)
                          VALUES ('".$data["id_tracking"]."', '".date("Y-m-d")."', '$suid',
                                  'Automatic Added followup for compatibility problem in update')";
               $DB->query($query3);
               $found = $DB->insert_id();
            }
            array_push($used_followups, $found);

            $query4 = "UPDATE `glpi_tracking_planning`
                       SET `id_followup` = '$found'
                       WHERE `ID` ='".$data['ID']."'";
            $DB->query($query4);
         }
      }
      unset($used_followups);
      $DB->free_result($result);

      $query = "ALTER TABLE `glpi_tracking_planning`
                DROP `id_tracking` ";
      $DB->queryOrDie($query, "0.65 add index for id_followup in tracking_planning");
   }

   if (!$DB->fieldExists("glpi_config", "use_ajax", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `dropdown_max` INT DEFAULT '100' NOT NULL ,
                ADD `ajax_wildcard` CHAR( 1 ) DEFAULT '*' NOT NULL ,
                ADD `use_ajax` SMALLINT DEFAULT '0' NOT NULL ,
                ADD `ajax_limit_count` INT DEFAULT '50' NOT NULL ";
      $DB->queryOrDie($query, "0.65 add ajax fields in config");
   }

   if (!$DB->fieldExists("glpi_config", "ajax_autocompletion", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `ajax_autocompletion` SMALLINT DEFAULT '1' NOT NULL ";
      $DB->queryOrDie($query, "0.65 add ajax_autocompletion field in config");
   }

   if (!$DB->fieldExists("glpi_config", "auto_add_users", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `auto_add_users` SMALLINT DEFAULT '1' NOT NULL ";
      $DB->queryOrDie($query, "0.65 add auto_add_users field in config");
   }

   if (!$DB->fieldExists("glpi_config", "dateformat", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `dateformat` SMALLINT DEFAULT '0' NOT NULL ";
      $DB->queryOrDie($query, "0.65 add dateformat field in config");
   }

   if ($DB->fieldExists("glpi_software", "version", false)) {
      $query = "ALTER TABLE `glpi_software`
                CHANGE `version` `version` VARCHAR( 200 ) NOT NULL";
      $DB->queryOrDie($query, "0.65 alter version field in software");
   }

   if (!$DB->fieldExists("glpi_config", "nextprev_item", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `nextprev_item` VARCHAR( 200 ) DEFAULT 'name' NOT NULL ";
      $DB->queryOrDie($query, "0.65 add nextprev_item field in config");
   }

   if (!$DB->fieldExists("glpi_config", "view_ID", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `view_ID` SMALLINT DEFAULT '0' NOT NULL ";
      $DB->queryOrDie($query, "0.65 add nextprev_item field in config");
   }

   if ($DB->fieldExists("glpi_infocoms", "comments", false)) {
      $query = "ALTER TABLE `glpi_infocoms`
                CHANGE `comments` `comments` TEXT";
      $DB->queryOrDie($query, "0.65 alter comments in glpi_infocoms");
   }

   $new_model = ["monitors", "networking", "peripherals", "printers"];
   foreach ($new_model as $model) {
      if (!$DB->tableExists("glpi_dropdown_model_$model")) {
         // model=type pour faciliter la gestion en post mise ???jour : ya plus qu'a deleter les elements non voulu
         // cela conviendra a tout le monde en fonction de l'utilisation du champ type
         $query = "CREATE TABLE `glpi_dropdown_model_$model` (
                     `ID` int(11) NOT NULL auto_increment,
                     `name` varchar(255) NOT NULL default '',
                     PRIMARY KEY  (`ID`)
                   ) TYPE=MyISAM";
         $DB->queryOrDie($query, "0.65 add table glpi_dropdown_model_$model");

         // copie type dans model
         $query = "SELECT *
                   FROM `glpi_type_$model`";
         $result = $DB->query($query);

         if ($DB->numrows($result)>0) {
            while ($data=$DB->fetch_array($result)) {
               $query = "INSERT INTO `glpi_dropdown_model_$model`
                                (`ID`, `name`)
                         VALUES ('".$data['ID']."', '".addslashes($data['name'])."')";
               $DB->queryOrDie($query, "0.65 insert value in glpi_dropdown_model_$model");
            }
         }
         $DB->free_result($result);
      }

      if (!$DB->fieldExists("glpi_$model", "model", false)) {
         $query = "ALTER TABLE `glpi_$model`
                   ADD `model` INT(11) DEFAULT NULL AFTER `type` ";
         $DB->queryOrDie($query, "0.6 add model in $model");

         $query = "UPDATE `glpi_$model`
                   SET `model` = `type` ";
         $DB->queryOrDie($query, "0.6 add model in $model");
      }
   }

   // Update pour les cartouches compatibles : type -> model
   if ($DB->fieldExists("glpi_cartridges_assoc", "FK_glpi_type_printer", false)) {
      $query = "ALTER TABLE `glpi_cartridges_assoc`
                CHANGE `FK_glpi_type_printer` `FK_glpi_dropdown_model_printers` INT( 11 )
                           DEFAULT '0' NOT NULL ";
      $DB->queryOrDie($query, "0.65 alter FK_glpi_type_printer field in cartridges_assoc");
   }

   if (!$DB->fieldExists("glpi_links", "data", false)) {
      $query = "ALTER TABLE `glpi_links`
                ADD `data` TEXT NOT NULL ";
      $DB->queryOrDie($query, "0.65 create data in links");
   }

   if (!$DB->tableExists("glpi_dropdown_auto_update")) {
      $query = "CREATE TABLE `glpi_dropdown_auto_update` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL default '',
                  PRIMARY KEY  (`ID`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.65 add table glpi_dropdown_auto_update");
   }

   if (!$DB->fieldExists("glpi_computers", "auto_update", false)) {
      $query = "ALTER TABLE `glpi_computers`
                ADD `auto_update` INT DEFAULT '0' NOT NULL AFTER `os` ";
      $DB->queryOrDie($query, "0.65 alter computers add auto_update");
   }

   // Update specificity of computer_device
   $query = "SELECT `glpi_computer_device`.`ID` AS ID,
                    `glpi_device_processor`.`specif_default` AS SPECIF
             FROM `glpi_computer_device`
             LEFT JOIN `glpi_device_processor`
                ON (`glpi_computer_device`.`FK_device` = `glpi_device_processor`.`ID`
                    AND `glpi_computer_device`.`device_type` = '".PROCESSOR_DEVICE."')
             WHERE `glpi_computer_device`.`specificity` =''";
   $result = $DB->query($query);

   if ($DB->numrows($result)>0) {
      while ($data=$DB->fetch_assoc($result)) {
         $query2 = "UPDATE `glpi_computer_device`
                    SET `specificity` = '".$data["SPECIF"]."'
                    WHERE `ID` = '".$data["ID"]."'";
         $DB->query($query2);
      }
   }

   $query = "SELECT `glpi_computer_device`.`ID` AS ID,
                    `glpi_device_ram`.`specif_default` AS SPECIF
             FROM `glpi_computer_device`
             LEFT JOIN `glpi_device_ram`
                ON (`glpi_computer_device`.`FK_device` = `glpi_device_ram`.`ID`
                    AND `glpi_computer_device`.`device_type` = '".RAM_DEVICE."')
             WHERE `glpi_computer_device`.`specificity` =''";
   $result = $DB->query($query);

   if ($DB->numrows($result)>0) {
      while ($data=$DB->fetch_assoc($result)) {
         $query2 = "UPDATE `glpi_computer_device`
                    SET `specificity` = '".$data["SPECIF"]."'
                    WHERE `ID` = '".$data["ID"]."'";
         $DB->query($query2);
      }
   }

   $query = "SELECT `glpi_computer_device`.`ID` AS ID,
                    `glpi_device_hdd`.`specif_default` AS SPECIF
             FROM `glpi_computer_device`
             LEFT JOIN `glpi_device_hdd`
                ON (`glpi_computer_device`.`FK_device` = `glpi_device_hdd`.`ID`
                    AND `glpi_computer_device`.`device_type` = '".HDD_DEVICE."')
             WHERE `glpi_computer_device`.`specificity` =''";
   $result = $DB->query($query);

   if ($DB->numrows($result)>0) {
      while ($data=$DB->fetch_assoc($result)) {
         $query2 = "UPDATE `glpi_computer_device`
                    SET `specificity` = '".$data["SPECIF"]."'
                    WHERE `ID` = '".$data["ID"]."'";
         $DB->query($query2);
      }
   }

   $query = "SELECT `glpi_computer_device`.`ID` as ID,
                    `glpi_device_iface`.`specif_default` AS SPECIF
             FROM `glpi_computer_device`
             LEFT JOIN `glpi_device_iface`
                ON (`glpi_computer_device`.`FK_device` = `glpi_device_iface`.`ID`
                    AND `glpi_computer_device`.`device_type` = '".NETWORK_DEVICE."')
             WHERE `glpi_computer_device`.`specificity` =''";
   $result = $DB->query($query);

   if ($DB->numrows($result)>0) {
      while ($data=$DB->fetch_assoc($result)) {
         $query2 = "UPDATE `glpi_computer_device`
                    SET `specificity` = '".$data["SPECIF"]."'
                    WHERE `ID` = '".$data["ID"]."'";
         $DB->query($query2);
      }
   }

   // add field notes in tables
   $new_notes = ["cartridges_type", "computers", "consumables_type", "contacts", "contracts",
                      "docs", "enterprises", "monitors", "networking", "peripherals", "printers",
                      "software"];

   foreach ($new_notes as $notes) {
      if (!$DB->fieldExists("glpi_$notes", "notes", false)) {
         $query = "ALTER TABLE `glpi_$notes`
                   ADD `notes` LONGTEXT NULL ";
         $DB->queryOrDie($query, "0.65 add notes field in table");
      }
   }

   if (!$DB->fieldExists("glpi_users", "active", false)) {
      $query = "ALTER TABLE `glpi_users`
                ADD `active` INT( 2 ) DEFAULT '1' NOT NULL ";
      $DB->queryOrDie($query, "0.65 add active in users");
   }

   if ($DB->tableExists("glpi_type_docs")) {
      $query = "SELECT *
                FROM `glpi_type_docs`
                WHERE `ext` IN ('odt', 'ods', 'odp', 'otp', 'ott', 'ots', 'odf', 'odg', 'otg', 'odb',
                                'oth', 'odm', 'odc', 'odi')";
      $result = $DB->query($query);

      if ($DB->numrows($result)==0) {
         $query2 = "INSERT INTO `glpi_type_docs`
                           (`name`, `ext`, `icon`, `mime`, `upload`, `date_mod`)
                    VALUES ('Oasis Open Office Writer', 'odt', 'odt-dist.png', '', 'Y',
                            '2006-01-21 17:41:13'),
                           ('Oasis Open Office Calc', 'ods', 'ods-dist.png', '', 'Y',
                            '2006-01-21 17:41:31'),
                           ('Oasis Open Office Impress', 'odp', 'odp-dist.png', '', 'Y',
                            '2006-01-21 17:42:54'),
                           ('Oasis Open Office Impress Template', 'otp', 'odp-dist.png', '', 'Y',
                            '2006-01-21 17:43:58'),
                           ('Oasis Open Office Writer Template', 'ott', 'odt-dist.png', '', 'Y',
                            '2006-01-21 17:44:41'),
                           ('Oasis Open Office Calc Template', 'ots', 'ods-dist.png', '', 'Y',
                            '2006-01-21 17:45:30'),
                           ('Oasis Open Office Math', 'odf', 'odf-dist.png', '', 'Y',
                            '2006-01-21 17:48:05'),
                           ('Oasis Open Office Draw', 'odg', 'odg-dist.png', '', 'Y',
                            '2006-01-21 17:48:31'),
                           ('Oasis Open Office Draw Template', 'otg', 'odg-dist.png', '', 'Y',
                            '2006-01-21 17:49:46'),
                           ('Oasis Open Office Base', 'odb', 'odb-dist.png', '', 'Y',
                            '2006-01-21 18:03:34'),
                           ('Oasis Open Office HTML', 'oth', 'oth-dist.png', '', 'Y',
                            '2006-01-21 18:05:27'),
                           ('Oasis Open Office Writer Master', 'odm', 'odm-dist.png', '', 'Y',
                            '2006-01-21 18:06:34'),
                           ('Oasis Open Office Chart', 'odc', '', '', 'Y', '2006-01-21 18:07:48'),
                           ('Oasis Open Office Image', 'odi', '', '', 'Y', '2006-01-21 18:08:18')";
         $DB->queryOrDie($query2, "0.65 add new type docs");
      }
   }

   ///// BEGIN  MySQL Compatibility
   if ($DB->fieldExists("glpi_infocoms", "warranty_value", false)) {
      $query2 = "UPDATE `glpi_infocoms`
                 SET `warranty_value` = '0'
                 WHERE `warranty_value` IS NULL";
      $DB->queryOrDie($query2, "0.65 update warranty_value='' in tracking");

      $query = "ALTER TABLE `glpi_infocoms`
                CHANGE `warranty_info` `warranty_info` VARCHAR( 255 ) NULL DEFAULT NULL,
                CHANGE `warranty_value` `warranty_value` FLOAT NOT NULL DEFAULT '0',
                CHANGE `num_commande` `num_commande` VARCHAR( 200 ) NULL DEFAULT NULL,
                CHANGE `bon_livraison` `bon_livraison` VARCHAR( 200 ) NULL DEFAULT NULL,
                CHANGE `facture` `facture` VARCHAR( 200 ) NULL DEFAULT NULL,
                CHANGE `num_immo` `num_immo` VARCHAR( 200 ) NULL DEFAULT NULL";
      $DB->queryOrDie($query, "0.65 alter various fields in infocoms");
   }

   if ($DB->fieldExists("glpi_reservation_item", "comments", false)) {
      $query = "ALTER TABLE `glpi_reservation_item`
                CHANGE `comments` `comments` TEXT NULL ";
      $DB->queryOrDie($query, "0.65 alter comments in glpi_reservation_item");
   }

   if ($DB->fieldExists("glpi_cartridges_type", "comments", false)) {
      $query = "ALTER TABLE `glpi_cartridges_type`
                CHANGE `name` `name` VARCHAR( 255 ) NULL DEFAULT NULL,
                CHANGE `ref` `ref` VARCHAR( 255 ) NULL DEFAULT NULL ,
                CHANGE `comments` `comments` TEXT NULL DEFAULT NULL ";
      $DB->queryOrDie($query, "0.65 alter various fields in cartridges_type");
   }

   if ($DB->fieldExists("glpi_computer_device", "specificity", false)) {
      $query = "ALTER TABLE `glpi_computer_device`
                CHANGE `specificity` `specificity` VARCHAR( 250 ) NULL ";
      $DB->queryOrDie($query, "0.65 alter specificity in glpi_computer_device");
   }

   $inv_table = ["computers", "monitors", "networking", "peripherals", "printers"];
   foreach ($inv_table as $table) {
      if ($DB->fieldExists("glpi_$table", "comments", false)) {
         $query = "UPDATE `glpi_$table`
                   SET `location` = '0'
                   WHERE `location` IS NULL";
         $DB->queryOrDie($query, "0.65 prepare data fro alter various fields in $table");

         $query = "ALTER TABLE `glpi_$table`
                   CHANGE `name` `name` VARCHAR( 200 ) NULL ,
                   CHANGE `serial` `serial` VARCHAR( 200 ) NULL ,
                   CHANGE `otherserial` `otherserial` VARCHAR( 200 ) NULL ,
                   CHANGE `contact` `contact` VARCHAR( 200 ) NULL ,
                   CHANGE `contact_num` `contact_num` VARCHAR( 200 ) NULL ,
                   CHANGE `location` `location` INT( 11 ) NOT NULL DEFAULT '0',
                   CHANGE `comments` `comments` TEXT NULL ";
         $DB->queryOrDie($query, "0.65 alter various fields in $table");
      }
   }

   if ($DB->fieldExists("glpi_computers", "os", false)) {
      $query = "UPDATE `glpi_computers`
                SET `model` = '0'
                WHERE `model` IS NULL";
      $DB->queryOrDie($query, "0.65 prepare model for alter computers");

      $query = "UPDATE `glpi_computers`
                SET `type` = '0'
                WHERE `type` IS NULL";
      $DB->queryOrDie($query, "0.65 prepare type for alter computers");

      $query = "ALTER TABLE `glpi_computers`
                CHANGE `os` `os` INT( 11 ) NOT NULL DEFAULT '0',
                CHANGE `model` `model` INT( 11 ) NOT NULL DEFAULT '0',
                CHANGE `type` `type` INT( 11 ) NOT NULL DEFAULT '0'";
      $DB->queryOrDie($query, "0.65 alter various fields in computers");
   }

   if ($DB->fieldExists("glpi_networking", "ram", false)) {
      $query = "ALTER TABLE `glpi_networking`
                CHANGE `ram` `ram` VARCHAR( 200 ) NULL,
                CHANGE `ifmac` `ifmac` VARCHAR( 200 ) NULL ,
                CHANGE `ifaddr` `ifaddr` VARCHAR( 200 ) NULL";
      $DB->queryOrDie($query, "0.65 alter 2 various fields in networking");
   }

   if ($DB->fieldExists("glpi_peripherals", "brand", false)) {
      $query = "ALTER TABLE `glpi_peripherals`
                CHANGE `brand` `brand` VARCHAR( 200 ) NULL ";
      $DB->queryOrDie($query, "0.65 alter 2 various fields in peripherals");
   }

   if ($DB->fieldExists("glpi_printers", "ramSize", false)) {
      $query = "ALTER TABLE `glpi_printers`
                CHANGE `ramSize` `ramSize` VARCHAR( 200 ) NULL ";
      $DB->queryOrDie($query, "0.65 alter 2 various fields in printers");
   }

   if ($DB->fieldExists("glpi_consumables_type", "comments", false)) {
      $query = "ALTER TABLE `glpi_consumables_type`
                CHANGE `name` `name` VARCHAR( 255 ) NULL,
                CHANGE `ref` `ref` VARCHAR( 255 ) NULL,
                CHANGE `comments` `comments` TEXT NULL ";
      $DB->queryOrDie($query, "0.65 alter various fields in consumables_type");
   }

   if ($DB->fieldExists("glpi_contacts", "comments", false)) {
      $query = "ALTER TABLE `glpi_contacts`
                CHANGE `name` `name` VARCHAR( 255 ) NULL,
                CHANGE `phone` `phone` VARCHAR( 200 ) NULL,
                CHANGE `phone2` `phone2` VARCHAR( 200 ) NULL,
                CHANGE `fax` `fax` VARCHAR( 200 ) NULL,
                CHANGE `email` `email` VARCHAR( 255 ) NULL,
                CHANGE `comments` `comments` TEXT NULL ";
      $DB->queryOrDie($query, "0.65 alter various fields in contacts");
   }

   if ($DB->fieldExists("glpi_contracts", "comments", false)) {
      $query = "ALTER TABLE `glpi_contracts`
                CHANGE `name` `name` VARCHAR( 255 ) NULL,
                CHANGE `num` `num` VARCHAR( 255 ) NULL,
                CHANGE `comments` `comments` TEXT NULL,
                CHANGE `compta_num` `compta_num` VARCHAR( 255 ) NULL ";
      $DB->queryOrDie($query, "0.65 alter various fields in contracts");
   }

   $device = ["case", "control", "drive", "gfxcard", "hdd", "iface", "moboard", "power", "pci",
                   "processor", "ram", "sndcard"];

   foreach ($device as $dev) {
      if ($DB->fieldExists("glpi_device_$dev", "comment", false)) {
         $query = "ALTER TABLE `glpi_device_$dev`
                   CHANGE `designation` `designation` VARCHAR( 255 ) NULL,
                   CHANGE `comment` `comment` TEXT NULL,
                   CHANGE `specif_default` `specif_default` VARCHAR( 250 ) NULL ";
         $DB->queryOrDie($query, "0.65 alter various fields in device_$dev");
      }

      if (!isIndex("glpi_device_$dev", "designation")) {
         $query = "ALTER TABLE `glpi_device_$dev`
                   ADD INDEX (`designation`)";
         $DB->queryOrDie($query, "0.65 alter various fields in device_$dev");
      }
   }

   if ($DB->fieldExists("glpi_docs", "comment", false)) {
      $query = "ALTER TABLE `glpi_docs`
                CHANGE `name` `name` VARCHAR( 255 ) NULL,
                CHANGE `filename` `filename` VARCHAR( 255 ) NULL,
                CHANGE `mime` `mime` VARCHAR( 30 ) NULL,
                CHANGE `comment` `comment` TEXT NULL,
                CHANGE `link` `link` VARCHAR( 255 ) NULL";
      $DB->queryOrDie($query, "0.65 alter various fields in docs");
   }

   if ($DB->fieldExists("glpi_enterprises", "comments", false)) {
      $query = "ALTER TABLE `glpi_enterprises`
                CHANGE `name` `name` VARCHAR( 200 ) NULL,
                CHANGE `address` `address` TEXT NULL,
                CHANGE `website` `website` VARCHAR( 200 ) NULL,
                CHANGE `phonenumber` `phonenumber` VARCHAR( 200 ) NULL,
                CHANGE `comments` `comments` TEXT NULL,
                CHANGE `fax` `fax` VARCHAR( 255 ) NULL,
                CHANGE `email` `email` VARCHAR( 255 ) NULL";
      $DB->queryOrDie($query, "0.65 alter various fields in enterprises");
   }

   if ($DB->fieldExists("glpi_event_log", "message", false)) {
      $query = "ALTER TABLE `glpi_event_log`
                CHANGE `itemtype` `itemtype` VARCHAR( 200 ) NULL,
                CHANGE `service` `service` VARCHAR( 200 ) NULL,
                CHANGE `message` `message` TEXT NULL";
      $DB->queryOrDie($query, "0.65 alter various fields in event_log");
   }

   if ($DB->fieldExists("glpi_kbitems", "question", false)) {
      $query = "ALTER TABLE `glpi_kbitems`
                CHANGE `question` `question` TEXT NULL,
                CHANGE `answer` `answer` TEXT NULL ";
      $DB->queryOrDie($query, "0.65 alter various fields in kbitems");
   }

   if ($DB->fieldExists("glpi_licenses", "serial", false)) {
      $query = "ALTER TABLE `glpi_licenses`
                CHANGE `serial` `serial` VARCHAR( 255 ) NULL";
      $DB->queryOrDie($query, "0.65 alter serial in licenses");
   }

   if ($DB->fieldExists("glpi_links", "data", false)) {
      $query = "ALTER TABLE `glpi_links`
                CHANGE `name` `name` VARCHAR( 255 ) NULL,
                CHANGE `data` `data` TEXT NULL";
      $DB->queryOrDie($query, "0.65 alter various fields in links");
   }

   if ($DB->fieldExists("glpi_networking_ports", "ifmac", false)) {
      $query = "ALTER TABLE `glpi_networking_ports`
                CHANGE `name` `name` CHAR( 200 ) NULL,
                CHANGE `ifaddr` `ifaddr` CHAR( 200 ) NULL,
                CHANGE `ifmac` `ifmac` CHAR( 200 ) NULL";
      $DB->queryOrDie($query, "0.65 alter various fields in networking_ports");
   }

   if ($DB->fieldExists("glpi_reservation_resa", "comment", false)) {
      $query = "ALTER TABLE `glpi_reservation_resa`
                CHANGE `comment` `comment` TEXT NULL";
      $DB->queryOrDie($query, "0.65 alter comment in reservation_resa");
   }

   if ($DB->fieldExists("glpi_software", "version", false)) {
      $query = "ALTER TABLE `glpi_software`
                CHANGE `name` `name` VARCHAR( 200 ) NULL,
                CHANGE `version` `version` VARCHAR( 200 ) NULL ";
      $DB->queryOrDie($query, "0.65 alter various fields in software");
   }

   if ($DB->fieldExists("glpi_type_docs", "name", false)) {
      $query = "ALTER TABLE `glpi_type_docs`
                CHANGE `name` `name` VARCHAR( 255 ) NULL,
                CHANGE `ext` `ext` VARCHAR( 10 ) NULL,
                CHANGE `icon` `icon` VARCHAR( 255 ) NULL,
                CHANGE `mime` `mime` VARCHAR( 100 ) NULL ";
      $DB->queryOrDie($query, "0.65 alter various fields in type_docs");
   }

   if ($DB->fieldExists("glpi_users", "language", false)) {
      $query = "ALTER TABLE `glpi_users`
                CHANGE `name` `name` VARCHAR( 80 ) NULL,
                CHANGE `password` `password` VARCHAR( 80 ) NULL,
                CHANGE `password_md5` `password_md5` VARCHAR( 80 ) NULL,
                CHANGE `email` `email` VARCHAR( 200 ) NULL,
                CHANGE `realname` `realname` VARCHAR( 255 ) NULL,
                CHANGE `language` `language` VARCHAR( 255 ) NULL";
      $DB->queryOrDie($query, "0.65 alter various fields in users");
   }

   if ($DB->fieldExists("glpi_config", "cut", false)) {
      $query = "ALTER TABLE `glpi_config`
                CHANGE `num_of_events` `num_of_events` VARCHAR( 200 ) NULL,
                CHANGE `jobs_at_login` `jobs_at_login` VARCHAR( 200 ) NULL,
                CHANGE `sendexpire` `sendexpire` VARCHAR( 200 ) NULL,
                CHANGE `cut` `cut` VARCHAR( 200 ) NULL,
                CHANGE `expire_events` `expire_events` VARCHAR( 200 ) NULL,
                CHANGE `list_limit` `list_limit` VARCHAR( 200 ) NULL,
                CHANGE `version` `version` VARCHAR( 200 ) NULL,
                CHANGE `logotxt` `logotxt` VARCHAR( 200 ) NULL,
                CHANGE `root_doc` `root_doc` VARCHAR( 200 ) NULL,
                CHANGE `event_loglevel` `event_loglevel` VARCHAR( 200 ) NULL,
                CHANGE `mailing` `mailing` VARCHAR( 200 ) NULL,
                CHANGE `imap_auth_server` `imap_auth_server` VARCHAR( 200 ) NULL,
                CHANGE `imap_host` `imap_host` VARCHAR( 200 ) NULL,
                CHANGE `ldap_host` `ldap_host` VARCHAR( 200 ) NULL,
                CHANGE `ldap_basedn` `ldap_basedn` VARCHAR( 200 ) NULL,
                CHANGE `ldap_rootdn` `ldap_rootdn` VARCHAR( 200 ) NULL,
                CHANGE `ldap_pass` `ldap_pass` VARCHAR( 200 ) NULL,
                CHANGE `admin_email` `admin_email` VARCHAR( 200 ) NULL,
                CHANGE `mailing_signature` `mailing_signature` VARCHAR( 200 ) NOT NULL DEFAULT '--',
                CHANGE `mailing_new_admin` `mailing_new_admin` tinyint(4) NOT NULL DEFAULT '1',
                CHANGE `mailing_followup_admin` `mailing_followup_admin` tinyint(4) NOT NULL
                        DEFAULT '1',
                CHANGE `mailing_finish_admin` `mailing_finish_admin` tinyint(4) NOT NULL DEFAULT '1',
                CHANGE `mailing_new_all_admin` `mailing_new_all_admin` tinyint(4) NOT NULL
                        DEFAULT '0',
                CHANGE `mailing_followup_all_admin` `mailing_followup_all_admin` tinyint(4) NOT NULL
                        DEFAULT '0',
                CHANGE `mailing_finish_all_admin` `mailing_finish_all_admin` tinyint(4) NOT NULL
                        DEFAULT '0',
                CHANGE `mailing_new_all_normal` `mailing_new_all_normal` tinyint(4) NOT NULL
                        DEFAULT '0',
                CHANGE `mailing_followup_all_normal` `mailing_followup_all_normal` tinyint(4)
                        NOT NULL DEFAULT '0',
                CHANGE `mailing_finish_all_normal` `mailing_finish_all_normal` tinyint(4) NOT NULL
                        DEFAULT '0',
                CHANGE `mailing_new_attrib` `mailing_new_attrib` tinyint(4) NOT NULL DEFAULT '1',
                CHANGE `mailing_followup_attrib` `mailing_followup_attrib` tinyint(4) NOT NULL
                        DEFAULT '1',
                CHANGE `mailing_finish_attrib` `mailing_finish_attrib` tinyint(4) NOT NULL
                        DEFAULT '1',
                CHANGE `mailing_new_user` `mailing_new_user` tinyint(4) NOT NULL DEFAULT '1',
                CHANGE `mailing_followup_user` `mailing_followup_user` tinyint(4) NOT NULL
                        DEFAULT '1',
                CHANGE `mailing_finish_user` `mailing_finish_user` tinyint(4) NOT NULL DEFAULT '1',
                CHANGE `mailing_resa_all_admin` `mailing_resa_all_admin` tinyint(4) NOT NULL
                        DEFAULT '0',
                CHANGE `mailing_resa_user` `mailing_resa_user` tinyint(4) NOT NULL DEFAULT '1',
                CHANGE `mailing_resa_admin` `mailing_resa_admin` tinyint(4) NOT NULL DEFAULT '1',
                CHANGE `ldap_field_name` `ldap_field_name` VARCHAR( 200 ) NULL,
                CHANGE `ldap_field_email` `ldap_field_email` VARCHAR( 200 ) NULL,
                CHANGE `ldap_field_location` `ldap_field_location` VARCHAR( 200 ) NULL,
                CHANGE `ldap_field_realname` `ldap_field_realname` VARCHAR( 200 ) NULL,
                CHANGE `ldap_field_phone` `ldap_field_phone` VARCHAR( 200 ) NULL,
                CHANGE `ldap_condition` `ldap_condition` VARCHAR( 255 ) NULL,
                CHANGE `permit_helpdesk` `permit_helpdesk` VARCHAR( 200 ) NULL,
                CHANGE `cas_host` `cas_host` VARCHAR( 255 ) NULL,
                CHANGE `cas_port` `cas_port` VARCHAR( 255 ) NULL,
                CHANGE `cas_uri` `cas_uri` VARCHAR( 255 ) NULL,
                CHANGE `url_base` `url_base` VARCHAR( 255 ) NULL,
                CHANGE `text_login` `text_login` TEXT NULL,
                CHANGE `founded_new_version` `founded_new_version` VARCHAR( 10 ) NULL ";
      $DB->queryOrDie($query, "0.65 alter various fields in config");
   }
   ///// END  MySQL Compatibility

   if (!$DB->fieldExists("glpi_config", "dropdown_limit", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `dropdown_limit` INT( 11 ) DEFAULT '50' NOT NULL ";
      $DB->queryOrDie($query, "0.65 add dropdown_limit in config");
   }

   if ($DB->fieldExists("glpi_consumables_type", "type", false)) {
      $query = "ALTER TABLE `glpi_consumables_type`
                CHANGE `type` `type` INT( 11 ) NOT NULL DEFAULT '0',
                CHANGE `alarm` `alarm` INT( 11 ) NOT NULL DEFAULT '10'";
      $DB->queryOrDie($query, "0.65 alter type and alarm in consumables_type");
   }

   if (!$DB->fieldExists("glpi_config", "post_only_followup", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `post_only_followup` tinyint( 4 ) DEFAULT '1' NOT NULL ";
      $DB->queryOrDie($query, "0.65 add dropdown_limit in config");
   }

   if (!$DB->fieldExists("glpi_monitors", "flags_dvi", false)) {
      $query = "ALTER TABLE `glpi_monitors`
                ADD `flags_dvi` tinyint( 4 ) DEFAULT '0' NOT NULL AFTER `flags_bnc`";
      $DB->queryOrDie($query, "0.65 add dropdown_limit in config");
   }

   if (!$DB->tableExists("glpi_history")) {
      $query = "CREATE TABLE `glpi_history` (
                  `ID` int(11) NOT NULL auto_increment,
                  `FK_glpi_device` int(11) NOT NULL default '0',
                  `device_type` tinyint(4) NOT NULL default '0',
                  `device_internal_type` int(11) default '0',
                  `device_internal_action` tinyint(4) default '0',
                  `user_name` varchar(200) default NULL,
                  `date_mod` datetime default NULL,
                  `id_search_option` int(11) NOT NULL default '0',
                  `old_value` varchar(255) default NULL,
                  `new_value` varchar(255) default NULL,
                  PRIMARY KEY  (`ID`),
                  KEY `FK_glpi_device` (`FK_glpi_device`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.65 add glpi_history table");
   }

   if ($DB->fieldExists("glpi_tracking", "assign_type", false)) {
      $query = "ALTER TABLE `glpi_tracking`
                ADD `assign_ent` INT NOT NULL DEFAULT '0' AFTER `assign` ";
      $DB->queryOrDie($query, "0.65 add assign_ent in tracking");

      $query = "UPDATE `glpi_tracking`
                SET `assign_ent` = `assign`
                WHERE `assign_type` = '".ENTERPRISE_TYPE."'";
      $DB->queryOrDie($query, "0.65 update assign_ent in tracking");

      $query = "UPDATE `glpi_tracking`
                SET `assign` = 0
                WHERE `assign_type` = '".ENTERPRISE_TYPE."'";
      $DB->queryOrDie($query, "0.65 update assign_ent in tracking");

      $query = "ALTER TABLE `glpi_tracking`
                DROP `assign_type`";
      $DB->queryOrDie($query, "0.65 drop assign_type in tracking");
   }

   if (!$DB->fieldExists("glpi_config", "mailing_update_admin", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `mailing_update_admin` tinyint(4) NOT NULL DEFAULT '1' AFTER `mailing_new_admin`";
      $DB->queryOrDie($query, "0.65 add mailing_update_admin in config");
   }

   if (!$DB->fieldExists("glpi_config", "mailing_update_all_admin", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `mailing_update_all_admin` tinyint(4) NOT NULL DEFAULT '0'
                     AFTER `mailing_new_all_admin`";
      $DB->queryOrDie($query, "0.65 add mailing_update_all_admin in config");
   }

   if (!$DB->fieldExists("glpi_config", "mailing_update_all_normal", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `mailing_update_all_normal` tinyint(4) NOT NULL DEFAULT '0'
                     AFTER `mailing_new_all_normal`";
      $DB->queryOrDie($query, "0.65 add mailing_update_all_normal in config");
   }

   if (!$DB->fieldExists("glpi_config", "mailing_update_attrib", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `mailing_update_attrib` tinyint(4) NOT NULL DEFAULT '1'
                     AFTER `mailing_new_attrib`";
      $DB->queryOrDie($query, "0.65 add mailing_update_attrib in config");
   }

   if (!$DB->fieldExists("glpi_config", "mailing_update_user", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `mailing_update_user` tinyint(4) NOT NULL DEFAULT '1' AFTER `mailing_new_user`";
      $DB->queryOrDie($query, "0.65 add mailing_update_user in config");
   }

   if (!$DB->fieldExists("glpi_config", "ldap_use_tls", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `ldap_use_tls` VARCHAR( 200 ) NOT NULL DEFAULT '0' AFTER `ldap_login` ";
      $DB->queryOrDie($query, "0.65 add ldap_use_tls in config");
   }

   if ($DB->fieldExists("glpi_config", "cut", false)) { // juste pour affichage identique sur toutes les versions.
      $query = "UPDATE `glpi_config`
                SET `cut` = '255'
                WHERE `ID` = 1";
      $DB->queryOrDie($query, "0.65 update Cut in config");
   }

   if (!$DB->fieldExists("glpi_licenses", "comments", false)) {
      $query = "ALTER TABLE `glpi_licenses`
                ADD `comments` TEXT NULL ";
      $DB->queryOrDie($query, "0.65 add comments in licenses");
   }

   ///////////// MODE OCS

   // Delete plugin table
   if ($DB->tableExists("glpi_ocs_link") && !$DB->fieldExists("glpi_ocs_link", "import_device", false)) {
      $query = "DROP TABLE `glpi_ocs_link`";
      $DB->queryOrDie($query, "0.65 MODE OCS drop plugin ocs_link");
   }

   if ($DB->tableExists("glpi_ocs_config") && !$DB->fieldExists("glpi_ocs_config", "checksum", false)) {
      $query = "DROP TABLE `glpi_ocs_config`";
      $DB->queryOrDie($query, "0.65 MODE OCS drop plugin ocs_config");
   }

   if (!$DB->tableExists("glpi_ocs_link")) {
      $query = "CREATE TABLE `glpi_ocs_link` (
                  `ID` int(11) NOT NULL auto_increment,
                  `glpi_id` int(11) NOT NULL default '0',
                  `ocs_id` varchar(255) NOT NULL default '',
                  `auto_update` int(2) NOT NULL default '1',
                  `last_update` datetime NOT NULL default '0000-00-00 00:00:00',
                  `computer_update` LONGTEXT NULL,
                  `import_device` LONGTEXT NULL,
                  `import_software` LONGTEXT NULL,
                  `import_monitor` LONGTEXT NULL,
                  `import_peripheral` LONGTEXT NULL,
                  `import_printers` LONGTEXT NULL,
                  PRIMARY KEY  (`ID`),
                  UNIQUE KEY `ocs_id_2` (`ocs_id`),
                  KEY `ocs_id` (`ocs_id`),
                  KEY `glpi_id` (`glpi_id`),
                  KEY `auto_update` (`auto_update`),
                  KEY `last_update` (`last_update`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.65 MODE OCS creation ocs_link");
   }

   if (!$DB->tableExists("glpi_ocs_config")) {
      $query = "CREATE TABLE `glpi_ocs_config` (
                  `ID` int(11) NOT NULL auto_increment,
                  `ocs_db_user` varchar(255) NOT NULL default '',
                  `ocs_db_passwd` varchar(255) NOT NULL default '',
                  `ocs_db_host` varchar(255) NOT NULL default '',
                  `ocs_db_name` varchar(255) NOT NULL default '',
                  `checksum` int(11) NOT NULL default '0',
                  `import_periph` int(2) NOT NULL default '0',
                  `import_monitor` int(2) NOT NULL default '0',
                  `import_software` int(2) NOT NULL default '0',
                  `import_printer` int(2) NOT NULL default '0',
                  `import_general_os` int(2) NOT NULL default '0',
                  `import_general_serial` int(2) NOT NULL default '0',
                  `import_general_model` int(2) NOT NULL default '0',
                  `import_general_enterprise` int(2) NOT NULL default '0',
                  `import_general_type` int(2) NOT NULL default '0',
                  `import_general_domain` int(2) NOT NULL default '0',
                  `import_general_contact` int(2) NOT NULL default '0',
                  `import_general_comments` int(2) NOT NULL default '0',
                  `import_device_processor` int(2) NOT NULL default '0',
                  `import_device_memory` int(2) NOT NULL default '0',
                  `import_device_hdd` int(2) NOT NULL default '0',
                  `import_device_iface` int(2) NOT NULL default '0',
                  `import_device_gfxcard` int(2) NOT NULL default '0',
                  `import_device_sound` int(2) NOT NULL default '0',
                  `import_device_drives` int(2) NOT NULL default '0',
                  `import_device_ports` int(2) NOT NULL default '0',
                  `import_device_modems` int(2) NOT NULL default '0',
                  `import_ip` int(2) NOT NULL default '0',
                  `default_state` int(11) NOT NULL default '0',
                  `tag_limit` varchar(255) NOT NULL default '',
                  PRIMARY KEY  (`ID`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.65 MODE OCS creation ocs_config");

      $query = "INSERT INTO `glpi_ocs_config`
                VALUES (1, 'ocs', 'ocs', 'localhost', 'ocsweb', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '')";
      $DB->queryOrDie($query, "0.65 MODE OCS add default config");
   }

   if (!$DB->fieldExists("glpi_computers", "ocs_import", false)) {
      $query = "ALTER TABLE `glpi_computers`
                ADD `ocs_import` TINYINT NOT NULL DEFAULT '0'";
      $DB->queryOrDie($query, "0.65 MODE OCS add default config");
   }

   if (!$DB->fieldExists("glpi_config", "ocs_mode", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `ocs_mode` TINYINT NOT NULL DEFAULT '0' ";
      $DB->queryOrDie($query, "0.65 MODE OCS add ocs_mode in config");
   }
   ///////////// FIN MODE OCS

   if (!$DB->tableExists("glpi_dropdown_budget")) {
      $query = "CREATE TABLE `glpi_dropdown_budget` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL DEFAULT '',
                  PRIMARY KEY  (`ID`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.65 add dropdown_budget");
   }

   if (!$DB->fieldExists("glpi_infocoms", "budget", false)) {
      $query = "ALTER TABLE `glpi_infocoms`
                ADD `budget` INT NULL DEFAULT '0'";
      $DB->queryOrDie($query, "0.65 add budget in infocoms");
   }

   if (!$DB->fieldExists("glpi_tracking", "cost_time", false)) {
      $query = "ALTER TABLE `glpi_tracking`
                ADD `cost_time` FLOAT NOT NULL DEFAULT '0',
                ADD `cost_fixed` FLOAT NOT NULL DEFAULT '0',
                ADD `cost_material` FLOAT NOT NULL DEFAULT '0'";
      $DB->queryOrDie($query, "0.65 add cost fields in tracking");
   }

   // Global Printers
   if (!$DB->fieldExists("glpi_printers", "is_global", false)) {
      $query = "ALTER TABLE `glpi_printers`
                ADD `is_global` ENUM('0', '1') DEFAULT '0' NOT NULL AFTER `FK_glpi_enterprise`";
      $DB->queryOrDie($query, "0.6 add is_global in printers");
   }

   if (!$DB->fieldExists("glpi_config", "debug", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `debug` int(2) NOT NULL default '0' ";
      $DB->queryOrDie($query, "0.65 add debug in config");
   }

   if (!$DB->tableExists("glpi_dropdown_os_version")) {
      $query = "CREATE TABLE `glpi_dropdown_os_version` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL default '',
                  PRIMARY KEY  (`ID`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.65 add dropdown_os_version");
   }

   if (!$DB->tableExists("glpi_dropdown_os_sp")) {
      $query = "CREATE TABLE `glpi_dropdown_os_sp` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL default '',
                  PRIMARY KEY  (`ID`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.65 add dropdown_os_sp");
   }

   if (!$DB->fieldExists("glpi_computers", "os_version", false)) {
      $query = "ALTER TABLE `glpi_computers`
                ADD `os_version` INT NOT NULL DEFAULT '0' AFTER `os`,
                ADD `os_sp` INT NOT NULL DEFAULT '0' AFTER `os_version` ";
      $DB->queryOrDie($query, "0.65 add os_version os_sp in computers");
   }

   // ADD INDEX
   $tbl = ["cartridges_type", "computers", "consumables_type", "contacts", "contracts",
                "docs", "enterprises", "monitors", "networking", "peripherals", "printers",
                "software", "users"];

   foreach ($tbl as $t) {
      if (!isIndex("glpi_$t", "name")) {
         $query = "ALTER TABLE `glpi_$t`
                   ADD INDEX (`name`) ";
         $DB->queryOrDie($query, "0.65 add index in name field $t");
      }
   }

   $result = $DB->list_tables();
   while ($line = $DB->fetch_array($result)) {
      if (strstr($line[0], "glpi_dropdown") || strstr($line[0], "glpi_type")) {
         if (!isIndex($line[0], "name")) {
            $query = "ALTER TABLE `".$line[0]."`
                      ADD INDEX (`name`) ";
            $DB->queryOrDie($query, "0.65 add index in name field ".$line[0]."");
         }
      }
   }

   if (!isIndex("glpi_reservation_item", "device_type_2")) {
      $query = "ALTER TABLE `glpi_reservation_item`
                ADD INDEX  `device_type_2` (`device_type`, `id_device`) ";
      $DB->queryOrDie($query, "0.65 add index in reservation_item ".$line[0]."");
   }

   if (!$DB->tableExists("glpi_dropdown_model_phones")) {
      $query = "CREATE TABLE `glpi_dropdown_model_phones` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL default '',
                  PRIMARY KEY  (`ID`),
                  KEY `name` (`name`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.65 add dropdown_model_phones");
   }

   if (!$DB->tableExists("glpi_type_phones")) {
      $query = "CREATE TABLE `glpi_type_phones` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL default '',
                  PRIMARY KEY  (`ID`),
                  KEY `name` (`name`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.65 add type_phones");
   }

   if (!$DB->tableExists("glpi_dropdown_phone_power")) {
      $query = "CREATE TABLE `glpi_dropdown_phone_power` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL default '',
                  PRIMARY KEY  (`ID`),
                  KEY `name` (`name`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.65 add dropdown_phone_power");
   }

   if (!$DB->tableExists("glpi_phones")) {
      $query = "CREATE TABLE `glpi_phones` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) default NULL,
                  `date_mod` datetime NOT NULL default '0000-00-00 00:00:00',
                  `contact` varchar(255) default NULL,
                  `contact_num` varchar(255) default NULL,
                  `tech_num` int(11) NOT NULL default '0',
                  `comments` text,
                  `serial` varchar(255) default NULL,
                  `otherserial` varchar(255) default NULL,
                  `firmware` varchar(255) default NULL,
                  `location` int(11) NOT NULL default '0',
                  `type` int(11) NOT NULL default '0',
                  `model` int(11) default NULL,
                  `brand` varchar(255) default NULL,
                  `power` tinyint(4) NOT NULL default '0',
                  `number_line` varchar(255) NOT NULL default '',
                  `flags_casque` tinyint(4) NOT NULL default '0',
                  `flags_hp` tinyint(4) NOT NULL default '0',
                  `FK_glpi_enterprise` int(11) NOT NULL default '0',
                  `is_global` enum('0','1') NOT NULL default '0',
                  `deleted` enum('Y','N') NOT NULL default 'N',
                  `is_template` enum('0','1') NOT NULL default '0',
                  `tplname` varchar(255) default NULL,
                  `notes` longtext,
                  PRIMARY KEY  (`ID`),
                  KEY `type` (`type`),
                  KEY `name` (`name`),
                  KEY `location` (`location`),
                  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
                  KEY `deleted` (`deleted`),
                  KEY `is_template` (`is_template`),
                  KEY `tech_num` (`tech_num`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.65 add phones");

      $query = "INSERT INTO `glpi_phones`
                VALUES (1, NULL, '0000-00-00 00:00:00', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0,
                        0, NULL, NULL, 0, '', 0, 0, 0, '0', 'N', '1', 'Blank Template', NULL)";
      $DB->queryOrDie($query, "0.65 blank template in phones");
   }

   if (!$DB->tableExists("glpi_reminder")) {
      $query = "CREATE TABLE `glpi_reminder` (
                  `ID` int(11) NOT NULL auto_increment,
                  `date` datetime default NULL,
                  `author` int(11) NOT NULL default '0',
                  `title` text,
                  `text` text,
                  `type` varchar(50) NOT NULL default 'private',
                  `begin` datetime default NULL,
                  `end` datetime default NULL,
                  `rv` enum('0','1') NOT NULL default '0',
                  `date_mod` datetime default NULL,
                  PRIMARY KEY  (`ID`),
                  KEY `date` (`date`),
                  KEY `author` (`author`),
                  KEY `rv` (`rv`),
                  KEY `type` (`type`)
                ) TYPE=MyISAM ";
      $DB->queryOrDie($query, "0.65 add reminder");
   }

   $result = $DB->list_tables();
   while ($line = $DB->fetch_array($result)) {
      if (strstr($line[0], "glpi_dropdown") || strstr($line[0], "glpi_type")) {
         if ($line[0] != "glpi_type_docs") {
            if (!$DB->fieldExists($line[0], "comments", false)) {
               $query = "ALTER TABLE `".$line[0]."`
                         ADD `comments` TEXT NULL ";
               $DB->queryOrDie($query, "0.65 add comments field in ".$line[0]."");
            }
         }
      }
   }

   if (!$DB->fieldExists("glpi_consumables", "id_user", false)) {
      $query = "ALTER TABLE `glpi_consumables`
                ADD `id_user` INT NOT NULL DEFAULT '0'";
      $DB->queryOrDie($query, "0.65 add id_user field in consumables");
   }

} // fin 0.65

