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
 * DB class to connect to a OCS server
 *
**/
class DBocs extends DBmysql {

   function DBocs() {
      global $db,$cfg_glpi;

      if ($cfg_glpi["ocs_mode"]) {
         $query            = "SELECT * FROM `glpi_ocs_config`";
         $result           = $db->query($query);
         $this->dbhost     = $db->result($result, 0, "ocs_db_host");
         $this->dbuser     = $db->result($result, 0, "ocs_db_user");
         $this->dbpassword = $db->result($result, 0, "ocs_db_passwd");
         $this->dbdefault  = $db->result($result, 0, "ocs_db_name");
         if (!($this->dbh = new mysqli($this->dbhost, $this->dbuser, $this->dbpassword))) {
            $this->error = 1;
         }
         if (!$this->dbh->select_db($this->dbdefault)) {
             $this->error = 1;
         }
      }
   }

}


/// Update from 0.68 to 0.68.1
function update068to0681() {
   global $DB, $CFG_GLPI;

   if ($DB->tableExists("glpi_repair_item")) {
      $query = "DROP TABLE `glpi_repair_item`";
      $DB->queryOrDie($query, "0.68.1 drop glpi_repair_item");
   }

   $tables = ["computers", "monitors", "networking", "peripherals", "phones", "printers"];
   foreach ($tables as $tbl) {
      if (isIndex("glpi_".$tbl, "type")) {
         $query = "ALTER TABLE `glpi_$tbl`
                   DROP INDEX `type`";
         $DB->queryOrDie($query, "0.68.1 drop index type glpi_$tbl");
      }

      if (isIndex("glpi_".$tbl, "type_2")) {
         $query = "ALTER TABLE `glpi_$tbl`
                   DROP INDEX `type_2`";
         $DB->queryOrDie($query, "0.68.1 drop index type_2 glpi_$tbl");
      }

      if (isIndex("glpi_".$tbl, "model")) {
         $query = "ALTER TABLE `glpi_$tbl`
                   DROP INDEX `model`";
         $DB->queryOrDie($query, "0.68.1 drop index model glpi_$tbl");
      }

      if (!isIndex("glpi_".$tbl, "type")) {
         $query = "ALTER TABLE `glpi_$tbl`
                   ADD INDEX (`type`)";
         $DB->queryOrDie($query, "0.68.1 add index type glpi_$tbl");
      }

      if (!isIndex("glpi_".$tbl, "model")) {
         $query = "ALTER TABLE `glpi_$tbl`
                   ADD INDEX (`model`)";
         $DB->queryOrDie($query, "0.68.1 add index model glpi_$tbl");
      }

      if (!isIndex("glpi_".$tbl, "FK_groups")) {
         $query = "ALTER TABLE `glpi_$tbl`
                   ADD INDEX (`FK_groups`)";
         $DB->queryOrDie($query, "0.68.1 add index on glpi_$tbl.FK_groups");
      }

      if (!isIndex("glpi_".$tbl, "FK_users")) {
         $query = "ALTER TABLE `glpi_$tbl`
                   ADD INDEX ( `FK_users` )";
         $DB->queryOrDie($query, "0.68.1 add index on glpi_$tbl.FK_users");
      }
   }

   if (!isIndex("glpi_software", "FK_groups")) {
      $query = "ALTER TABLE `glpi_software`
                ADD INDEX (`FK_groups`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_software.FK_groups");
   }

   if (!isIndex("glpi_software", "FK_users")) {
      $query = "ALTER TABLE `glpi_software`
                ADD INDEX ( `FK_users` )";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_software.FK_users");
   }

   if (!isIndex("glpi_cartridges_type", "location")) {
      $query = "ALTER TABLE `glpi_cartridges_type`
                ADD INDEX (`location`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_cartridges_type.location");
   }

   if ($DB->fieldExists("glpi_cartridges_type", "type", false)) {
      $query = "ALTER TABLE `glpi_cartridges_type`
                CHANGE `type` `type` INT NOT NULL DEFAULT '0'";
      $DB->queryOrDie($query, "0.68.1 alter glpi_cartridges_type.type");
   }

   if (!isIndex("glpi_cartridges_type", "type")) {
      $query = "ALTER TABLE `glpi_cartridges_type`
               ADD INDEX (`type`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_cartridges_type.type");
   }

   if (!isIndex("glpi_cartridges_type", "alarm")) {
      $query = "ALTER TABLE `glpi_cartridges_type`
                ADD INDEX (`alarm`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_cartridges_type.alarm");
   }

   if (!isIndex("glpi_computers", "os_sp")) {
      $query = "ALTER TABLE `glpi_computers`
                ADD INDEX (`os_sp`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_computers.os_sp");
   }

   if (!isIndex("glpi_computers", "os_version")) {
      $query = "ALTER TABLE `glpi_computers`
                ADD INDEX (`os_version`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_computers.os_version");
   }

   if (!isIndex("glpi_computers", "network")) {
      $query = "ALTER TABLE `glpi_computers`
                ADD INDEX (`network`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_computers.network");
   }

   if (!isIndex("glpi_computers", "domain")) {
      $query = "ALTER TABLE `glpi_computers`
                ADD INDEX (`domain`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_computers.domain");
   }

   if (!isIndex("glpi_computers", "auto_update")) {
      $query = "ALTER TABLE `glpi_computers`
                ADD INDEX (`auto_update`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_computers.auto_update");
   }

   if (!isIndex("glpi_computers", "ocs_import")) {
      $query = "ALTER TABLE `glpi_computers`
                ADD INDEX (`ocs_import`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_computers.ocs_import");
   }

   if (!isIndex("glpi_consumables", "id_user")) {
      $query = "ALTER TABLE `glpi_consumables`
                ADD INDEX (`id_user`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_consumables.id_user");
   }

   if (!isIndex("glpi_consumables_type", "location")) {
      $query = "ALTER TABLE `glpi_consumables_type`
                ADD INDEX (`location`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_consumables_type.location");
   }

   if (!isIndex("glpi_consumables_type", "type")) {
      $query = "ALTER TABLE `glpi_consumables_type`
                ADD INDEX (`type`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_consumables_type.type");
   }

   if (!isIndex("glpi_consumables_type", "alarm")) {
      $query = "ALTER TABLE `glpi_consumables_type`
                ADD INDEX (`alarm`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_consumables_type.alarm");
   }

   if ($DB->fieldExists("glpi_contacts", "type", false)) {
      $query = "ALTER TABLE `glpi_contacts`
                CHANGE `type` `type` INT( 11 ) NULL ";
      $DB->queryOrDie($query, "0.68.1 alter glpi_contacts.type");
   }

   if (!isIndex("glpi_contract_device", "device_type")) {
      $query = "ALTER TABLE `glpi_contract_device`
                ADD INDEX (`device_type`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_contract_device.device_type");
   }

   if (!isIndex("glpi_contract_device", "is_template")) {
      $query = "ALTER TABLE `glpi_contract_device`
                ADD INDEX (`is_template`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_contract_device.is_template");
   }

   if (!isIndex("glpi_device_hdd", "interface")) {
      $query = "ALTER TABLE `glpi_device_hdd`
                ADD INDEX (`interface`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_device_hdd.interface");
   }

   if (!isIndex("glpi_device_ram", "type")) {
      $query = "ALTER TABLE `glpi_device_ram`
                ADD INDEX (`type`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_device_ram.type");
   }

   if (!isIndex("glpi_display", "FK_users")) {
      $query = "ALTER TABLE `glpi_display`
                ADD INDEX (`FK_users`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_display.FK_users");
   }

   if (!isIndex("glpi_docs", "FK_users")) {
      $query = "ALTER TABLE `glpi_docs`
                ADD INDEX (`FK_users`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_docs.FK_users");
   }

   if (!isIndex("glpi_docs", "FK_tracking")) {
      $query = "ALTER TABLE `glpi_docs`
                ADD INDEX (`FK_tracking`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_docs.FK_tracking");
   }

   if (!isIndex("glpi_doc_device", "device_type")) {
      $query = "ALTER TABLE `glpi_doc_device`
                ADD INDEX (`device_type`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_doc_device.device_type");
   }

   if (!isIndex("glpi_dropdown_tracking_category", "parentID")) {
      $query = "ALTER TABLE `glpi_dropdown_tracking_category`
                ADD INDEX (`parentID`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_dropdown_tracking_category.parentID");
   }

   if (!isIndex("glpi_history", "device_type")) {
      $query = "ALTER TABLE `glpi_history`
                ADD INDEX (`device_type`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_history.device_type");
   }

   if (!isIndex("glpi_history", "device_internal_type")) {
      $query = "ALTER TABLE `glpi_history`
                ADD INDEX (`device_internal_type`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_history.device_internal_type");
   }

   if (!isIndex("glpi_infocoms", "budget")) {
      $query = "ALTER TABLE `glpi_infocoms`
                ADD INDEX (`budget`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_infocoms.budget");
   }

   if (!isIndex("glpi_infocoms", "alert")) {
      $query = "ALTER TABLE `glpi_infocoms`
                ADD INDEX (`alert`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_infocoms.alert");
   }

   if (!isIndex("glpi_kbitems", "author")) {
      $query = "ALTER TABLE `glpi_kbitems`
                ADD INDEX (`author`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_kbitems.author");
   }

   if (!isIndex("glpi_kbitems", "faq")) {
      $query = "ALTER TABLE `glpi_kbitems`
                ADD INDEX (`faq`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_kbitems.faq");
   }

   if (!isIndex("glpi_licenses", "oem_computer")) {
      $query = "ALTER TABLE `glpi_licenses`
                ADD INDEX (`oem_computer`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_licenses.oem_computer");
   }

   if (!isIndex("glpi_licenses", "oem")) {
      $query = "ALTER TABLE `glpi_licenses`
                ADD INDEX (`oem`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_licenses.oem");
   }

   if (!isIndex("glpi_licenses", "buy")) {
      $query = "ALTER TABLE `glpi_licenses`
                ADD INDEX (`buy`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_licenses.buy");
   }

   if (!isIndex("glpi_licenses", "serial")) {
      $query = "ALTER TABLE `glpi_licenses`
                ADD INDEX (`serial`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_licenses.serial");
   }

   if (!isIndex("glpi_licenses", "expire")) {
      $query = "ALTER TABLE `glpi_licenses`
                ADD INDEX (`expire`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_licenses.expire");
   }

   if (!isIndex("glpi_networking", "network")) {
      $query = "ALTER TABLE `glpi_networking`
                ADD INDEX (`network`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_networking.network");
   }

   if (!isIndex("glpi_networking", "domain")) {
      $query = "ALTER TABLE `glpi_networking`
                ADD INDEX (`domain`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_networking.domain");
   }

   if (!isIndex("glpi_networking_ports", "iface")) {
      $query = "ALTER TABLE `glpi_networking_ports`
                ADD INDEX (`iface`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_networking_ports.iface");
   }

   if ($DB->fieldExists("glpi_phones", "power", false)) {
      $query = "ALTER TABLE `glpi_phones`
                CHANGE `power` `power` INT NOT NULL DEFAULT '0'";
      $DB->queryOrDie($query, "0.68.1 alter glpi_phones.power");
   }

   if (!isIndex("glpi_phones", "power")) {
      $query = "ALTER TABLE `glpi_phones`
                ADD INDEX (`power`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_phones.power");
   }

   if (!isIndex("glpi_reminder", "begin")) {
      $query = "ALTER TABLE `glpi_reminder`
                ADD INDEX (`begin`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_reminder.begin");
   }

   if (!isIndex("glpi_reminder", "end")) {
      $query = "ALTER TABLE `glpi_reminder`
                ADD INDEX (`end`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_reminder.end");
   }

   if (!isIndex("glpi_software", "update_software")) {
      $query = "ALTER TABLE `glpi_software`
                ADD INDEX (`update_software`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_software.update_software");
   }

   if (!isIndex("glpi_state_item", "state")) {
      $query = "ALTER TABLE `glpi_state_item`
                ADD INDEX (`state`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_state_item.state");
   }

   if (!isIndex("glpi_tracking", "FK_group")) {
      $query = "ALTER TABLE `glpi_tracking`
                ADD INDEX (`FK_group`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_tracking.FK_group");
   }

   if (!isIndex("glpi_tracking", "assign_ent")) {
      $query = "ALTER TABLE `glpi_tracking`
                ADD INDEX (`assign_ent`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_tracking.assign_ent");
   }

   if (!isIndex("glpi_tracking", "device_type")) {
      $query = "ALTER TABLE `glpi_tracking`
                ADD INDEX (`device_type`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_tracking.device_type");
   }

   if (!isIndex("glpi_tracking", "priority")) {
      $query = "ALTER TABLE `glpi_tracking`
                ADD INDEX (`priority`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_tracking.priority");
   }

   if (!isIndex("glpi_tracking", "request_type")) {
      $query = "ALTER TABLE `glpi_tracking`
                ADD INDEX (`request_type`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_tracking.request_type");
   }

   if (!isIndex("glpi_users", "location")) {
      $query = "ALTER TABLE `glpi_users`
                ADD INDEX (`location`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_users.location");
   }

   if (!isIndex("glpi_printers", "network")) {
      $query = "ALTER TABLE `glpi_printers`
                ADD INDEX (`network`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_printers.network");
   }

   if (!isIndex("glpi_printers", "domain")) {
      $query = "ALTER TABLE `glpi_printers`
                ADD INDEX (`domain`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_printers.domain");
   }

   if ($DB->fieldExists("glpi_device_case", "format", false)) {
      $query = "ALTER TABLE `glpi_device_case`
                CHANGE `format` `format` ENUM('Grand', 'Moyen', 'Micro', 'Slim', '')
                                         NULL DEFAULT 'Moyen'";
      $DB->queryOrDie($query, "0.68.1 alter glpi_device_case.format");
   }

   if ($DB->fieldExists("glpi_device_gfxcard", "interface", false)) {
      $query = "ALTER TABLE `glpi_device_gfxcard`
                CHANGE `interface` `interface` ENUM('AGP', 'PCI', 'PCI-X', 'Other', '')
                                               NULL DEFAULT 'AGP'";
      $DB->queryOrDie($query, "0.68.1 alter glpi_device_gfxcard.interface");
   }

   // Add default values in GLPI_DROPDOWN_HDD_TYPE
   // Rename glpi_dropdown HDD_TYPE -> INTERFACE
   if (!$DB->tableExists("glpi_dropdown_interface")) {
      $query = "ALTER TABLE `glpi_dropdown_hdd_type`
                RENAME `glpi_dropdown_interface` ";
      $DB->queryOrDie($query, "0.68.1 alter dropdown_hdd_type -> dropdown_interface");

      $values     = ["SATA", "IDE", "SCSI", "USB"];
      $interfaces = [];
      foreach ($values as $val) {
         $query = "SELECT *
                   FROM `glpi_dropdown_interface`
                   WHERE `name` LIKE '$val'";
         $result = $DB->query($query);

         if ($DB->numrows($result)==1) {
            $row              = $DB->fetch_array($result);
            $interfaces[$val] = $row["ID"];
         } else {
            $query = "INSERT INTO `glpi_dropdown_interface`
                             (`name`)
                      VALUES ('$val');";
            $DB->query($query);
            $interfaces[$val] = $DB->insert_id();
         }
      }

      // ALTER TABLES
      $query = "ALTER TABLE `glpi_device_control`
                CHANGE `interface` `interface2` ENUM('IDE', 'SATA', 'SCSI', 'USB') NOT NULL
                                                DEFAULT 'IDE'";
      $DB->queryOrDie($query, "0.68.1 alter device_control");

      $query = "ALTER TABLE `glpi_device_drive`
                CHANGE `interface` `interface2` ENUM('IDE', 'SATA', 'SCSI') NOT NULL DEFAULT 'IDE'";
      $DB->queryOrDie($query, "0.68.1 alter device_drive");

      $query = "ALTER TABLE `glpi_device_control`
                ADD `interface` INT NULL ";
      $DB->queryOrDie($query, "0.68.1 alter device_control");

      $query = "ALTER TABLE `glpi_device_drive`
                ADD `interface` INT NULL ";
      $DB->queryOrDie($query, "0.68.1 alter device_drive");

      foreach ($interfaces as $name => $ID) {
         $query = "UPDATE `glpi_device_drive`
                   SET `interface` = '$ID'
                   WHERE `interface2` = '$name'";
         $DB->queryOrDie($query, "0.68.1 update data device_drive");

         $query = "UPDATE `glpi_device_control`
                   SET `interface` = '$ID'
                   WHERE `interface2` = '$name'";
         $DB->queryOrDie($query, "0.68.1 update data device_control");
      }

      // DROP TABLES
      $query = "ALTER TABLE `glpi_device_control`
                DROP `interface2`";
      $DB->queryOrDie($query, "0.68.1 drop interface2 device_drive");

      $query = "ALTER TABLE `glpi_device_drive`
                DROP `interface2`";
      $DB->queryOrDie($query, "0.68.1 drop interface2 device_drive");

      // ADD INDEX
      $query = "ALTER TABLE `glpi_device_drive`
                ADD INDEX (`interface`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_device_drive.interface");

      $query = "ALTER TABLE `glpi_device_control`
                ADD INDEX (`interface`)";
      $DB->queryOrDie($query, "0.68.1 add index on glpi_device_drive.interface");
   }

   if ($DB->fieldExists("glpi_profiles", "update", false)) {
      $query = "ALTER TABLE `glpi_profiles`
                CHANGE `update` `check_update` CHAR( 1 ) NULL DEFAULT NULL";
      $DB->queryOrDie($query, "0.68.1 alter glpi_profiles.update");
   }

   if ($DB->fieldExists("glpi_config", "last_update_check", false)) {
      $query = "ALTER TABLE `glpi_config`
                DROP `last_update_check`";
      $DB->queryOrDie($query, "0.68.1 drop glpi_config.last_update_check");
   }

   if (!$DB->fieldExists("glpi_config", "keep_tracking_on_delete", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `keep_tracking_on_delete` INT DEFAULT '1'";
      $DB->queryOrDie($query, "0.68.1 drop glpi_config.keep_tracking_on_delete");
   }

   if (!$DB->fieldExists("glpi_config", "show_admin_doc", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `show_admin_doc` INT DEFAULT '0' ";
      $DB->queryOrDie($query, "0.68.1 drop glpi_config.show_admin_doc");
   }

   if (!$DB->fieldExists("glpi_config", "time_step", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `time_step` INT DEFAULT '5' ";
      $DB->queryOrDie($query, "0.68.1 drop glpi_config.time_step");
   }

   $query = "UPDATE `glpi_config`
             SET `time_step` = '5',
                 `show_admin_doc` = '0',
                 `keep_tracking_on_delete` = '0'";
   $DB->queryOrDie($query, "0.68.1 update glpi_config data");

   if (!$DB->fieldExists("glpi_ocs_config", "cron_sync_number", false)) {
      $query = "ALTER TABLE `glpi_ocs_config`
                ADD `cron_sync_number` INT DEFAULT '1' ";
      $DB->queryOrDie($query, "0.68.1 drop glpi_ocs_config.cron_sync_number");
   }

   if (!$DB->fieldExists("glpi_profiles", "show_group_ticket", false)) {
      $query = "ALTER TABLE `glpi_profiles`
                ADD `show_group_ticket` char(1) DEFAULT '0' ";
      $DB->queryOrDie($query, "0.68.1 drop glpi_profiles.show_group_ticket");
   }

   if (!$DB->fieldExists("glpi_config", "ldap_group_condition", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `ldap_group_condition` VARCHAR( 255 ) NULL ,
                ADD `ldap_search_for_groups` TINYINT NOT NULL DEFAULT '0',
                ADD `ldap_field_group_member` VARCHAR( 255 ) NULL ";
      $DB->queryOrDie($query, "0.68.1 add glpi_config.ldap_*_groups");
   }

   if (!$DB->fieldExists("glpi_groups", "ldap_group_dn", false)) {
      $query = "ALTER TABLE `glpi_groups`
                ADD `ldap_group_dn` VARCHAR( 255 ) NULL ";
      $DB->queryOrDie($query, "0.68.1 add glpi_groups.ldap_group_dn");
   }

   if (!$DB->fieldExists("glpi_ocs_link", "ocs_deviceid", false)) {
      $query = "ALTER TABLE `glpi_ocs_link`
                CHANGE `ocs_id` `ocs_deviceid` VARCHAR( 255 ) NOT NULL ";
      $DB->queryOrDie($query, "0.68.1 add glpi_ocs_link.ocs_deviceid");
   }

   if (!$DB->fieldExists("glpi_ocs_link", "ocs_id", false)) {
      $query = "ALTER TABLE `glpi_ocs_link`
                ADD `ocs_id` INT NOT NULL DEFAULT '0' AFTER `glpi_id` ";
      $DB->queryOrDie($query, "0.68.1 add glpi_ocs_link.ocs_id");
   }

   if (!$DB->fieldExists("glpi_ocs_link", "last_ocs_update", false)) {
      $query = "ALTER TABLE `glpi_ocs_link`
                ADD `last_ocs_update` DATETIME NULL AFTER `last_update` ";
      $DB->queryOrDie($query, "0.68.1 add glpi_ocs_link.last_ocs_update");
   }

   if (countElementsInTable("glpi_ocs_link")) {
      $CFG_GLPI["ocs_mode"] = 1;
      $DBocs                = new DBocs(1);
      // Get datas to update
      $query = "SELECT *
                FROM `glpi_ocs_link`";
      $result_glpi = $DB->query($query);

      while ($data_glpi = $DB->fetch_array($result_glpi)) {
         // Get ocs information
         $query_ocs = "SELECT *
                       FROM `hardware`
                       WHERE `DEVICEID` = '".$data_glpi["ocs_deviceid"]."'
                       LIMIT 1";
         $result_ocs = $DBocs->queryOrDie($query_ocs, "0.68.1 get ocs infos");

         if ($result_ocs && $DBocs->numrows($result_ocs)) {
            $data_ocs = $DBocs->fetch_array($result_ocs);

            $query_update = "UPDATE `glpi_ocs_link`
                             SET `ocs_id` = '".$data_ocs["ID"]."',
                                 `last_ocs_update` = '".$data_ocs["LASTDATE"]."'
                             WHERE `ID` = '".$data_glpi["ID"]."'";
            $DB->queryOrDie($query_update, "0.68.1 update ocs infos");
         }
      }
   }

   if (!$DB->tableExists("glpi_dropdown_case_type")) {
      $query = "CREATE TABLE `glpi_dropdown_case_type` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL,
                  `comments` text,
                  PRIMARY KEY (`ID`),
                  KEY `name` (`name`)
                ) ENGINE=MyISAM;";
      $DB->queryOrDie($query, "0.68.1 add table dropdown_case_type");

      // ajout du champs type
      $query = "ALTER TABLE `glpi_device_case`
                ADD   `type` INT(11) default NULL AFTER `designation` ";
      $DB->queryOrDie($query, "0.68.1 add glpi_device_case.type");

      // Ajout des entrees dans la table dropdown_case_type
      $query = "INSERT INTO `glpi_dropdown_case_type`
                       (`ID` , `name` , `comments`)
                VALUES ('1' , 'Grand', NULL)";
      $DB->queryOrDie($query, "0.68.1 glpi_device_case");

      $query = "INSERT INTO `glpi_dropdown_case_type`
                       (`ID` , `name` , `comments`)
                VALUES ('2' , 'Moyen', NULL)";
      $DB->queryOrDie($query, "0.68.1 glpi_device_case");

      $query = "INSERT INTO `glpi_dropdown_case_type`
                       (`ID` , `name` , `comments`)
                VALUES ('3' , 'Micro', NULL)";
      $DB->queryOrDie($query, "0.68.1 glpi_device_case");

      // Mapping format enum / type
      $query = "UPDATE `glpi_device_case`
                SET `type` = '1'
                WHERE `format` = 'Grand'";
      $DB->queryOrDie($query, "0.68.1 glpi_device_case");

      $query = "UPDATE `glpi_device_case`
                SET `type` = '2'
                WHERE `format` = 'Moyen'";
      $DB->queryOrDie($query, "0.68.1 glpi_device_case");

      $query = "UPDATE `glpi_device_case`
                SET `type` = '3'
                 WHERE `format` = 'Micro'";
      $DB->queryOrDie($query, "0.68.1 glpi_device_case");

      // Supression du champ format
      $query = "ALTER TABLE `glpi_device_case`
                DROP `format`";
      $DB->queryOrDie($query, "0.68.1 drop format from glpi_device_case");
   }

   // Clean state datas
   if ($DB->tableExists("glpi_state_item")) {
      $query = "SELECT COUNT(*) AS CPT, `device_type`, `id_device`
                FROM `glpi_state_item`
                GROUP BY `device_type`, `id_device`
                HAVING CPT > 1";
      $result = $DB->query($query);

      if ($DB->numrows($result)) {
         while ($data = $DB->fetch_array($result)) {
            $query2 = "DELETE
                       FROM `glpi_state_item`
                       WHERE `device_type` = '".$data["device_type"]."'
                             AND `id_device` = '".$data["id_device"]."'
                       LIMIT ".($data["CPT"]-1)."";
            $DB->queryOrDie($query2, "0.68.1 clean glpi_state_item");
         }
      }

      if (isIndex("glpi_state_item", "device_type")) {
         $query = "ALTER TABLE `glpi_state_item`
                   DROP INDEX `device_type` ";
         $DB->queryOrDie($query, "0.68.1 drop index glpi_state_item");
      }

      if (isIndex("glpi_state_item", "device_type2")) {
         $query = "ALTER TABLE `glpi_state_item`
                   DROP INDEX `device_type2` ";
         $DB->queryOrDie($query, "0.68.1 drop index glpi_state_item");
      }

      $query = "ALTER TABLE `glpi_state_item`
                ADD INDEX (`device_type`) ";
      $DB->queryOrDie($query, "0.68.1 add index glpi_state_item");

      $query = "ALTER TABLE `glpi_state_item`
                ADD UNIQUE (`device_type`, `id_device`) ";
      $DB->queryOrDie($query, "0.68.1 add unique glpi_state_item");
   }

} // fin 0.68 #####################################################################################
