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

/// Update from 0.7 to 0.71
function update07to071() {
   global $DB, $CFG_GLPI;

   if (!$DB->fieldExists("glpi_profiles", "rule_dictionnary_software", false)) {
      $query = "ALTER TABLE `glpi_profiles`
                ADD `rule_dictionnary_software` VARCHAR( 1 ) NULL DEFAULT NULL";
      $DB->queryOrDie($query, "0.71 add rule_dictionnary_software in glpi_profiles if not present for compatibility");

      $query = "UPDATE `glpi_profiles`
                SET `rule_dictionnary_software` = `rule_softwarecategories`";
      $DB->queryOrDie($query, "0.71 update value of rule_dictionnary_software right");
   }

   if (!$DB->fieldExists("glpi_profiles", "rule_dictionnary_dropdown", false)) {
      $query = "ALTER TABLE `glpi_profiles`
                ADD `rule_dictionnary_dropdown` VARCHAR( 1 ) NULL DEFAULT NULL";
      $DB->queryOrDie($query, "0.71 add rule_dictionnary_dropdown in glpi_profiles");

      $query = "UPDATE `glpi_profiles`
                SET `rule_dictionnary_dropdown` = `rule_dictionnary_software`";
      $DB->queryOrDie($query, "0.71 update value of rule_dictionnary_dropdown");
   }

   $cache_tables = ["glpi_rule_cache_manufacturer",
                         "glpi_rule_cache_model_computer",
                         "glpi_rule_cache_model_monitor",
                         "glpi_rule_cache_model_printer",
                         "glpi_rule_cache_model_peripheral",
                         "glpi_rule_cache_model_phone",
                         "glpi_rule_cache_model_networking",
                         "glpi_rule_cache_type_computer",
                         "glpi_rule_cache_type_monitor",
                         "glpi_rule_cache_type_printer",
                         "glpi_rule_cache_type_peripheral",
                         "glpi_rule_cache_type_phone",
                         "glpi_rule_cache_type_networking",
                         "glpi_rule_cache_software",
                         "glpi_rule_cache_os",
                         "glpi_rule_cache_os_sp",
                         "glpi_rule_cache_os_version"];

   foreach ($cache_tables as $cache_table) {
      if (!$DB->tableExists($cache_table)) {
         $query = "CREATE TABLE `".$cache_table."` (
                     `ID` INT( 11 ) NOT NULL auto_increment ,
                     `old_value` VARCHAR( 255 ) NULL default NULL ,
                     `rule_id` INT( 11 ) NOT NULL DEFAULT '0',
                     `new_value` VARCHAR( 255 ) NULL default NULL ,
                     PRIMARY KEY ( `ID` ),
                     KEY `rule_id` (`rule_id`),
                     KEY `old_value` (`old_value`)
                   ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $DB->queryOrDie($query, "0.71 add table ".$cache_table."");
      }
   }

   //Add the field version espacially for the software's cache
   if (!$DB->fieldExists("glpi_rule_cache_software", "version", false)) {
      $query = "ALTER TABLE `glpi_rule_cache_software`
                ADD `version` VARCHAR( 255 ) DEFAULT NULL ";
      $DB->queryOrDie($query, "0.71 add version in glpi_rule_cache_software if not present");
   }

   if (!$DB->fieldExists("glpi_rule_cache_software", "manufacturer", false)) {
      $query = "ALTER TABLE `glpi_rule_cache_software`
                ADD `manufacturer` VARCHAR( 255 ) NOT NULL AFTER `old_value` ";
      $DB->queryOrDie($query, "0.71 add manufacturer in glpi_rule_cache_software if not present");
   }

   if (!$DB->fieldExists("glpi_rule_cache_software", "new_manufacturer", false)) {
      $query = "ALTER TABLE `glpi_rule_cache_software`
                ADD `new_manufacturer` VARCHAR( 255 ) NOT NULL AFTER `version` ";
      $DB->queryOrDie($query, "0.71 add new_manufacturer in glpi_rule_cache_software if not present");
   }

   $model_cache_tables = ["glpi_rule_cache_model_computer",
                               "glpi_rule_cache_model_monitor",
                               "glpi_rule_cache_model_printer",
                               "glpi_rule_cache_model_peripheral",
                               "glpi_rule_cache_model_phone",
                               "glpi_rule_cache_model_networking"];

   foreach ($model_cache_tables as $model_cache_table) {
      if (!$DB->fieldExists($model_cache_table, "manufacturer", false)) {
         $query = "ALTER TABLE `".$model_cache_table."`
                   ADD `manufacturer` VARCHAR( 255 ) DEFAULT NULL ";
         $DB->queryOrDie($query, "0.71 add manufacturer in ".$model_cache_table." if not present");
      }
   }

   if (!$DB->fieldExists("glpi_rules_descriptions", "active", false)) {
      $query = "ALTER TABLE `glpi_rules_descriptions`
                ADD `active` INT( 1 ) NOT NULL DEFAULT '1'";
      $DB->queryOrDie($query, "0.71 add active in glpi_rules_descriptions if not present");
   }

   if (!$DB->tableExists("glpi_auth_ldap_replicate")) {
      $query = "CREATE TABLE IF NOT EXISTS `glpi_auth_ldap_replicate` (
                  `ID` int(11) NOT NULL auto_increment,
                  `server_id` int(11) NOT NULL default '0',
                  `ldap_host` varchar(255) NULL default NULL,
                  `ldap_port` int(11) NOT NULL default '389',
                  `name` varchar(255) NULL default NULL,
                  PRIMARY KEY (`ID`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.71 add table glpi_auth_ldap_replicate");
   }

   if (!$DB->fieldExists("glpi_config", "dbreplicate_notify_desynchronization", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `dbreplicate_notify_desynchronization` SMALLINT NOT NULL DEFAULT '0',
                ADD `dbreplicate_email` VARCHAR( 255 ) NULL ,
                ADD `dbreplicate_maxdelay` INT NOT NULL DEFAULT '3600'";
      $DB->queryOrDie($query, "0.71 alter config add config for dbreplicate notif");
   }

   if ($DB->fieldExists("glpi_reminder", "author", false)) {
      $query = "ALTER TABLE `glpi_reminder`
                CHANGE `author` `FK_users` INT( 11 ) NOT NULL DEFAULT '0'";
      $DB->queryOrDie($query, "0.71 rename author in glpi_reminder");

      if (isIndex("glpi_reminder", "author")) {
         $query = "ALTER TABLE `glpi_reminder`
                   DROP INDEX `author`";
         $DB->queryOrDie($query, "0.7 drop index author on glpi_reminder");
      }

      $query = " ALTER TABLE `glpi_reminder`
                 ADD INDEX `FK_users` (`FK_users`) ";
      $DB->queryOrDie($query, "0.71 ad index FK_users in glpi_reminder");
   }

   if (!$DB->fieldExists("glpi_reminder", "recursive", false)) {
      $query = "ALTER TABLE `glpi_reminder`
                ADD `recursive` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `type`";
      $DB->queryOrDie($query, "0.71 add recursive in glpi_reminder");

      $query = "ALTER TABLE `glpi_reminder`
                ADD INDEX `recursive` (`recursive`) ";
      $DB->queryOrDie($query, "0.71 add recursive index in glpi_reminder");
   }

   if (!$DB->fieldExists("glpi_reminder", "private", false)) {
      $query = "ALTER TABLE `glpi_reminder`
                ADD `private` TINYINT( 1 ) NOT NULL DEFAULT '1' AFTER `type`";
      $DB->queryOrDie($query, "0.71 add private in glpi_reminder");

      $query = "UPDATE `glpi_reminder`
                SET `private` = '0'
                WHERE `type` = 'public' ";
      $DB->queryOrDie($query, "0.71 update private field in glpi_reminder");

      $query = "ALTER TABLE `glpi_reminder`
                ADD INDEX `private` (`private`) ";
      $DB->queryOrDie($query, "0.71 add private index in glpi_reminder");

      // Drop type
      $query = "ALTER TABLE `glpi_reminder`
                DROP `type`";
      $DB->queryOrDie($query, "0.71 drop type in glpi_reminder");
   }

   if ($DB->fieldExists("glpi_reminder", "title", false)) {
      $query = "ALTER TABLE `glpi_reminder`
                CHANGE `title` `name` VARCHAR( 255 ) NULL DEFAULT NULL ";
      $DB->queryOrDie($query, "0.71 alter title to namein glpi_reminder");
   }

   if (!isIndex("glpi_ocs_link", "last_ocs_update")) {
      $query = "ALTER TABLE `glpi_ocs_link`
                ADD INDEX `last_ocs_update` (`ocs_server_id`, `last_ocs_update`)";
      $DB->queryOrDie($query, "0.7 alter ocs_link add index on last_ocs_update");
   }

   if (!$DB->fieldExists("glpi_contacts", "recursive", false)) {
      $query = "ALTER TABLE `glpi_contacts`
                ADD `recursive` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `FK_entities`";
      $DB->queryOrDie($query, "0.71 add recursive in glpi_contacts");
   }

   if (!$DB->fieldExists("glpi_contracts", "recursive", false)) {
      $query = "ALTER TABLE `glpi_contracts`
                ADD `recursive` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `FK_entities`";
      $DB->queryOrDie($query, "0.71 add recursive in glpi_contracts");
   }

   if (!$DB->fieldExists("glpi_enterprises", "recursive", false)) {
      $query = "ALTER TABLE `glpi_enterprises`
                ADD `recursive` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `FK_entities`";
      $DB->queryOrDie($query, "0.71 add recursive in glpi_enterprises");
   }

   if (!$DB->fieldExists("glpi_docs", "recursive", false)) {
      $query = "ALTER TABLE `glpi_docs`
                ADD `recursive` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `FK_entities`";
      $DB->queryOrDie($query, "0.71 add recursive in glpi_docs");
   }

   if (!$DB->fieldExists("glpi_monitors", "flags_pivot", false)) {
      $query = "ALTER TABLE `glpi_monitors`
                ADD `flags_pivot` SMALLINT( 6 ) NOT NULL DEFAULT 0 AFTER `flags_dvi`";
      $DB->queryOrDie($query, "0.71 add flags_pivot in glpi_monitors");
   }

   if (!$DB->fieldExists("glpi_kbitems", "FK_entities", false)) {
      $query = "ALTER TABLE `glpi_kbitems`
                ADD `FK_entities` INT(11) NOT NULL DEFAULT 0 AFTER `ID`";
      $DB->queryOrDie($query, "0.71 add FK_entities in glpi_kbitems");
   }

   if (!$DB->fieldExists("glpi_kbitems", "recursive", false)) {
      // Default 1 for migration. All articles become "global" (root + recursive)
      $query = "ALTER TABLE `glpi_kbitems`
                ADD `recursive` TINYINT(1) NOT NULL DEFAULT 1 AFTER `FK_entities`";
      $DB->queryOrDie($query, "0.71 add recursive in glpi_kbitems");
   }

   if (!isIndex("glpi_kbitems", "FK_entities")) {
      $query = "ALTER TABLE `glpi_kbitems`
                ADD INDEX `FK_entities` (`FK_entities`)";
      $DB->queryOrDie($query, "0.7 alter ocs_link add index on last_ocs_update");
   }

   if (!$DB->fieldExists("glpi_config", "category_on_software_delete", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `category_on_software_delete` INT( 11 ) NOT NULL DEFAULT '0'";
      $DB->queryOrDie($query, "0.71 add category_on_software_delete in glpi_config");

      //Create a software category for softwares to be deleted by the dictionary
      $result = $DB->query("SELECT `ID`
                            FROM `glpi_dropdown_software_category`
                            WHERE `name` = 'FUSION'");

      if (!$DB->numrows($result)) {
         $DB->query("INSERT INTO `glpi_dropdown_software_category`
                     SET `name` = 'FUSION'");

         $result = $DB->query("SELECT `ID`
                               FROM `glpi_dropdown_software_category`
                               WHERE `name` = 'FUSION'");
      }
      $cat_id = $DB->result($result, 0, "ID");

      $DB->query("UPDATE `glpi_config`
                  SET `category_on_software_delete` = '".$cat_id."'");
   }

   $query = "DELETE
             FROM `glpi_display`
             WHERE `num` = '121'";
   $DB->queryOrDie($query, "0.71 clean glpi_display for end_warranty infocoms");

   // Delete helpdesk injector user
   $query = "DELETE
             FROM `glpi_users`
             WHERE `ID` = '1'";
   $DB->queryOrDie($query, "0.71 delete helpdesk injector user");

   // Delete helpdesk injector user
   $query = "DELETE
             FROM `glpi_users_profiles`
             WHERE `FK_users` = '1'";
   $DB->queryOrDie($query, "0.71 delete helpdesk injector user profile");

   // change default device type for tracking
   if ($DB->fieldExists("glpi_tracking", "device_type", false)) {
      $query = "ALTER TABLE `glpi_tracking`
                CHANGE `device_type` `device_type` INT( 11 ) NOT NULL DEFAULT '0' ";
      $DB->queryOrDie($query, "0.71 alter device_type from glpi_tracking");
   }

   // Change ldap condition field bigger
   if ($DB->fieldExists("glpi_auth_ldap", "ldap_condition", false)) {
      $query = "ALTER TABLE `glpi_auth_ldap`
                CHANGE `ldap_condition` `ldap_condition` TEXT NULL DEFAULT NULL";
      $DB->queryOrDie($query, "0.71 alter change ldap_condition field to be bigger");
   }

   // Add date_mod to glpi_tracking
   if (!$DB->fieldExists("glpi_tracking", "date_mod", false)) {
      $query = "ALTER TABLE `glpi_tracking`
                ADD `date_mod` DATETIME NULL DEFAULT NULL AFTER `closedate` ";
      $DB->queryOrDie($query, "0.71 alter glpi_tracking add date_mod");

      $query = "UPDATE `glpi_tracking`
                SET `date_mod` = `date`";
      $DB->queryOrDie($query, "0.71 alter glpi_tracking update date_mod value to creation date");
   }

   // Add number format
   if (!$DB->fieldExists("glpi_config", "numberformat", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `numberformat` SMALLINT NOT NULL DEFAULT '0' AFTER `dateformat` ";
      $DB->queryOrDie($query, "0.71 alter config add numberformat");
   }

   // Add group supervisor
   if (!$DB->fieldExists("glpi_groups", "FK_users", false)) {
      $query = "ALTER TABLE `glpi_groups`
                ADD `FK_users` INT NOT NULL DEFAULT '0' AFTER `comments` ";
      $DB->queryOrDie($query, "0.71 alter groups add FK_users supervisor");
   }

   // Add group supervisor
   if (!$DB->fieldExists("glpi_entities_data", "admin_email", false)) {
      $query = "ALTER TABLE `glpi_entities_data`
                ADD `admin_email` VARCHAR( 255 ) NULL AFTER `email`";
      $DB->queryOrDie($query, "0.71 alter entities_data add admin_email");
   }

   // Add cas ldap server link
   if (!$DB->fieldExists("glpi_config", "extra_ldap_server", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `extra_ldap_server` INT NOT NULL DEFAULT '1' AFTER `cas_logout`";
      $DB->queryOrDie($query, "0.71 alter config add extra_ldap_server");
   }

   // Add x509 email field definition
   if (!$DB->fieldExists("glpi_config", "x509_email_field", false)) {
      $query = "ALTER TABLE `glpi_config` ADD `x509_email_field` VARCHAR( 255 ) NULL";
      $DB->queryOrDie($query, "0.71 alter config add x509_email_field");
   }

   // Add x509 email field definition
   if (!$DB->fieldExists("glpi_config", "existing_auth_server_field", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `existing_auth_server_field` VARCHAR( 255 ) NULL  AFTER `extra_ldap_server`";
      $DB->queryOrDie($query, "0.71 alter config add existing_auth_server_field");
   }

   // update cas auth field from 0 -> 5
   $query = "UPDATE `glpi_users`
             SET `auth_method` = '5'
             WHERE `auth_method` = '0'";
   $DB->queryOrDie($query, "0.71 update auth method for CAS");

   if (!$DB->tableExists("glpi_bookmark")) {
      $query = "CREATE TABLE IF NOT EXISTS `glpi_bookmark` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) default NULL,
                  `type` int(11) NOT NULL default '0',
                  `device_type` int(11) NOT NULL default '0',
                  `FK_users` int(11) NOT NULL default '0',
                  `private` smallint(6) NOT NULL default '1',
                  `FK_entities` int(11) NOT NULL default '-1',
                  `recursive` smallint(6) NOT NULL default '0',
                  `path` varchar(255) default NULL,
                  `query` text,
                  PRIMARY KEY (`ID`),
                  KEY `FK_users` (`FK_users`),
                  KEY `private` (`private`),
                  KEY `device_type` (`device_type`),
                  KEY `recursive` (`recursive`),
                  KEY `FK_entities` (`FK_entities`),
                  KEY `type` (`type`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.71 add table glpi_bookmark");
   }

   if (!$DB->fieldExists("glpi_profiles", "show_group_planning", false)) {
      $query = "ALTER TABLE `glpi_profiles`
                ADD `show_group_planning` CHAR( 1 ) NULL AFTER `show_planning` ";
      $DB->queryOrDie($query, "0.71 add show_group_planning in glpi_profiles");

      $query = "UPDATE `glpi_profiles`
                SET `show_group_planning` = `show_all_planning`";
      $DB->queryOrDie($query, "0.71 update value of show_group_planning right");
   }

   if (!$DB->fieldExists("glpi_users", "FK_profiles", false)) {
      $query = "ALTER TABLE `glpi_users`
                ADD `FK_profiles` INT NOT NULL DEFAULT '0'";
      $DB->queryOrDie($query, "0.71 add default profile to user");
   }

   if (!$DB->fieldExists("glpi_users", "FK_entities", false)) {
      $query = "ALTER TABLE `glpi_users`
                ADD `FK_entities` INT NOT NULL DEFAULT '0'";
      $DB->queryOrDie($query, "0.71 add default entity to user");
   }

   if (!$DB->fieldExists("glpi_auth_ldap", "ldap_opt_deref", false)) {
      $query = "ALTER TABLE `glpi_auth_ldap`
                ADD `ldap_opt_deref` INT (1) NOT NULL DEFAULT '0'";
      $DB->queryOrDie($query, "0.71 add ldap_opt_deref to glpi_auth_ldap");
   }

   //ticket opening restrictions
   if (!$DB->fieldExists("glpi_config", "ticket_title_mandatory", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `ticket_title_mandatory` INT (1) NOT NULL DEFAULT '0'";
      $DB->queryOrDie($query, "0.71 add ticket_title_mandatory to glpi_config");
   }

   if (!$DB->fieldExists("glpi_config", "ticket_content_mandatory", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `ticket_content_mandatory` INT (1) NOT NULL DEFAULT '1'";
      $DB->queryOrDie($query, "0.71 add ticket_content_mandatory to glpi_config");
   }

   if (!$DB->fieldExists("glpi_config", "ticket_category_mandatory", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `ticket_category_mandatory` INT (1) NOT NULL DEFAULT '0'";
      $DB->queryOrDie($query, "0.71 add ticket_category_mandatory to glpi_config");
   }

   // Add alerts on licenses
   if (!$DB->fieldExists("glpi_config", "licenses_alert", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `licenses_alert` SMALLINT NOT NULL DEFAULT '0' AFTER `infocom_alerts`";
      $DB->queryOrDie($query, "0.71 add licenses_alert to glpi_config");
   }

   if (!$DB->fieldExists("glpi_config", "autoclean_link_contact", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `autoclean_link_contact` smallint(6) NOT NULL DEFAULT '0'
                                             AFTER `autoupdate_link_location`,
                ADD `autoclean_link_user` smallint(6) NOT NULL DEFAULT '0'
                                          AFTER `autoclean_link_contact`,
                ADD `autoclean_link_group` smallint(6) NOT NULL DEFAULT '0'
                                           AFTER `autoclean_link_user`,
                ADD `autoclean_link_location` smallint(6) NOT NULL DEFAULT '0'
                                              AFTER `autoclean_link_group`";
      $DB->queryOrDie($query, "0.71 add autoclean_link_* to glpi_config");
   }

   if (!$DB->fieldExists("glpi_config", "autoupdate_link_state", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `autoupdate_link_state` smallint(6) NOT NULL DEFAULT '0'
                                            AFTER `autoupdate_link_location`,
                ADD `autoclean_link_state` smallint(6) NOT NULL DEFAULT '0'
                                           AFTER `autoclean_link_location`";
      $DB->queryOrDie($query, "0.71 add autoclean_link_state to glpi_config");

      $query = "UPDATE `glpi_ocs_config`
                SET `deconnection_behavior` = ''
                WHERE `deconnection_behavior` != 'trash'
                      AND `deconnection_behavior` != 'delete'";
      $DB->query($query);
   }

   if (!$DB->fieldExists("glpi_profiles", "bookmark_public", false)) {
      $query = "ALTER TABLE `glpi_profiles`
                ADD `bookmark_public` CHAR( 1 ) AFTER `reminder_public`";
      $DB->queryOrDie($query, "0.71 add bookmark_public to glpi_profiles");

      $query = "UPDATE `glpi_profiles`
                SET `bookmark_public` = `reminder_public`";
      $DB->queryOrDie($query, "0.71 init bookmark_public value in glpi_profiles");
   }

   if (!$DB->fieldExists("glpi_config", "admin_reply", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `admin_reply` VARCHAR( 255 ) NULL AFTER `admin_email`";
      $DB->queryOrDie($query, "0.71 add admin_reply to glpi_config");
   }

   if (!$DB->fieldExists("glpi_config", "mailgate_filesize_max", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `mailgate_filesize_max` int(11) NOT NULL DEFAULT ".(2*1024*1024)."
                                            AFTER `ticket_category_mandatory`";
      $DB->queryOrDie($query, "0.71 add mailgate_filesize_max to glpi_config");
   }

   if (!$DB->fieldExists("glpi_entities_data", "admin_reply", false)) {
      $query = "ALTER TABLE `glpi_entities_data`
                ADD `admin_reply` VARCHAR( 255 ) NULL AFTER `admin_email` ";
      $DB->queryOrDie($query, "0.71 add admin_reply to glpi_entities_data");
   }

   if (!isIndex("glpi_kbitems", "fulltext")) {
      $query = "ALTER TABLE `glpi_kbitems`
                ADD FULLTEXT `fulltext` (`question`, `answer`)";
      $DB->queryOrDie($query, "0.71 add fulltext index  glpi_kbitems");
   }

   if (!$DB->fieldExists("glpi_profiles", "user_auth_method", false)) {
      $query = "ALTER TABLE `glpi_profiles`
                ADD `user_auth_method` CHAR( 1 ) NULL DEFAULT NULL AFTER `user`";
      $DB->queryOrDie($query, "0.71 add user_auth_method to glpi_profiles");

      $query = "UPDATE `glpi_profiles`
                SET `user_auth_method` = `user`";
      $DB->queryOrDie($query, "0.71 init user_auth_method value in glpi_profiles");
   }

   if (isIndex("glpi_printers", "id")) {
      $query = "ALTER TABLE `glpi_printers`
                DROP INDEX `id`";
      $DB->queryOrDie($query, "0.71 drop id index in glpi_printers");
   }

   if (isIndex("glpi_users", "name_2")) {
      $query = "ALTER TABLE `glpi_users`
                DROP INDEX `name_2`";
      $DB->queryOrDie($query, "0.71 drop name_2 index in glpi_users");
   }

   if (!$DB->fieldExists("glpi_rules_descriptions", "comments", false)) {
      $query = "ALTER TABLE `glpi_rules_descriptions`
                ADD `comments` TEXT NULL DEFAULT NULL";
      $DB->queryOrDie($query, "0.71 add comments to glpi_rules_descriptions");
   }

} // fin 0.71 #####################################################################################
