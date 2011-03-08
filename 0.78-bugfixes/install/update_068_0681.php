<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

/// Update from 0.68 to 0.68.1
function update068to0681(){
	global $DB,$LANG,$CFG_GLPI;

	if(TableExists("glpi_repair_item")) {
		$query = "DROP TABLE `glpi_repair_item`;";
		$DB->query($query) or die("0.68.1 drop glpi_repair_item ".$LANG['update'][90].$DB->error());
	}

	$tables=array("computers","monitors","networking","peripherals","phones","printers");
	foreach ($tables as $tbl){
		if (isIndex("glpi_".$tbl,"type")){
			$query = "ALTER TABLE `glpi_$tbl` DROP INDEX `type`;";
			$DB->query($query) or die("0.68.1 drop index type glpi_$tbl ".$LANG['update'][90].$DB->error());
		}
		if (isIndex("glpi_".$tbl,"type_2")){
			$query = "ALTER TABLE `glpi_$tbl` DROP INDEX `type_2`;";
			$DB->query($query) or die("0.68.1 drop index type_2 glpi_$tbl ".$LANG['update'][90].$DB->error());
		}
		if (isIndex("glpi_".$tbl,"model")){
			$query = "ALTER TABLE `glpi_$tbl` DROP INDEX `model`;";
			$DB->query($query) or die("0.68.1 drop index model glpi_$tbl ".$LANG['update'][90].$DB->error());
		}

		if (!isIndex("glpi_".$tbl,"type")){
			$query = "ALTER TABLE `glpi_$tbl` ADD INDEX ( `type` )";
			$DB->query($query) or die("0.68.1 add index type glpi_$tbl ".$LANG['update'][90].$DB->error());
		}
		if (!isIndex("glpi_".$tbl,"model")){
			$query = "ALTER TABLE `glpi_$tbl` ADD INDEX ( `model` )";
			$DB->query($query) or die("0.68.1 add index model glpi_$tbl ".$LANG['update'][90].$DB->error());
		}

		if(!isIndex("glpi_".$tbl, "FK_groups")) {
			$query = "ALTER TABLE `glpi_$tbl` ADD INDEX ( `FK_groups` )";
			$DB->query($query) or die("0.68.1 add index on glpi_$tbl.FK_groups ".$LANG['update'][90].$DB->error());
		}

		if(!isIndex("glpi_".$tbl, "FK_users")) {
			$query = "ALTER TABLE `glpi_$tbl` ADD INDEX ( `FK_users` )";
			$DB->query($query) or die("0.68.1 add index on glpi_$tbl.FK_users ".$LANG['update'][90].$DB->error());
		}

	}


	if(!isIndex("glpi_software", "FK_groups")) {
		$query = "ALTER TABLE `glpi_software` ADD INDEX ( `FK_groups` )";
		$DB->query($query) or die("0.68.1 add index on glpi_software.FK_groups ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_software", "FK_users")) {
		$query = "ALTER TABLE `glpi_software` ADD INDEX ( `FK_users` )";
		$DB->query($query) or die("0.68.1 add index on glpi_software.FK_users ".$LANG['update'][90].$DB->error());
	}


	if(!isIndex("glpi_cartridges_type", "location")) {
		$query = "ALTER TABLE `glpi_cartridges_type` ADD INDEX ( `location` )";
		$DB->query($query) or die("0.68.1 add index on glpi_cartridges_type.location ".$LANG['update'][90].$DB->error());
	}

	if(FieldExists("glpi_cartridges_type", "type")) {
		$query = "ALTER TABLE `glpi_cartridges_type` CHANGE `type` `type` INT NOT NULL DEFAULT '0'";
		$DB->query($query) or die("0.68.1 alter glpi_cartridges_type.type ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_cartridges_type", "type")) {
		$query = "ALTER TABLE `glpi_cartridges_type` ADD INDEX ( type )";
		$DB->query($query) or die("0.68.1 add index on glpi_cartridges_type.type ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_cartridges_type", "alarm")) {
		$query = "ALTER TABLE `glpi_cartridges_type` ADD INDEX ( alarm )";
		$DB->query($query) or die("0.68.1 add index on glpi_cartridges_type.alarm ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_computers", "os_sp")) {
		$query = "ALTER TABLE `glpi_computers` ADD INDEX ( `os_sp` )";
		$DB->query($query) or die("0.68.1 add index on glpi_computers.os_sp ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_computers", "os_version")) {
		$query = "ALTER TABLE `glpi_computers` ADD INDEX ( `os_version` )";
		$DB->query($query) or die("0.68.1 add index on glpi_computers.os_version ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_computers", "network")) {
		$query = "ALTER TABLE `glpi_computers` ADD INDEX ( `network` )";
		$DB->query($query) or die("0.68.1 add index on glpi_computers.network ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_computers", "domain")) {
		$query = "ALTER TABLE `glpi_computers` ADD INDEX ( `domain` )";
		$DB->query($query) or die("0.68.1 add index on glpi_computers.domain ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_computers", "auto_update")) {
		$query = "ALTER TABLE `glpi_computers` ADD INDEX ( `auto_update` )";
		$DB->query($query) or die("0.68.1 add index on glpi_computers.auto_update ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_computers", "ocs_import")) {
		$query = "ALTER TABLE `glpi_computers` ADD INDEX ( `ocs_import` )";
		$DB->query($query) or die("0.68.1 add index on glpi_computers.ocs_import ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_consumables", "id_user")) {
		$query = "ALTER TABLE `glpi_consumables` ADD INDEX ( `id_user` )";
		$DB->query($query) or die("0.68.1 add index on glpi_consumables.id_user ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_consumables_type", "location")) {
		$query = "ALTER TABLE `glpi_consumables_type` ADD INDEX ( `location` )";
		$DB->query($query) or die("0.68.1 add index on glpi_consumables_type.location ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_consumables_type", "type")) {
		$query = "ALTER TABLE `glpi_consumables_type` ADD INDEX ( `type` )";
		$DB->query($query) or die("0.68.1 add index on glpi_consumables_type.type ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_consumables_type", "alarm")) {
		$query = "ALTER TABLE `glpi_consumables_type` ADD INDEX ( `alarm` )";
		$DB->query($query) or die("0.68.1 add index on glpi_consumables_type.alarm ".$LANG['update'][90].$DB->error());
	}

	if(FieldExists("glpi_contacts", "type")) {
		$query = "ALTER TABLE `glpi_contacts` CHANGE `type` `type` INT( 11 ) NULL ";
		$DB->query($query) or die("0.68.1 alter glpi_contacts.type ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_contract_device", "device_type")) {
		$query = "ALTER TABLE `glpi_contract_device` ADD INDEX ( `device_type` )";
		$DB->query($query) or die("0.68.1 add index on glpi_contract_device.device_type ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_contract_device", "is_template")) {
		$query = "ALTER TABLE `glpi_contract_device` ADD INDEX ( `is_template` )";
		$DB->query($query) or die("0.68.1 add index on glpi_contract_device.is_template ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_device_hdd", "interface")) {
		$query = "ALTER TABLE `glpi_device_hdd` ADD INDEX ( `interface` )";
		$DB->query($query) or die("0.68.1 add index on glpi_device_hdd.interface ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_device_ram", "type")) {
		$query = "ALTER TABLE `glpi_device_ram` ADD INDEX ( `type` )";
		$DB->query($query) or die("0.68.1 add index on glpi_device_ram.type ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_display", "FK_users")) {
		$query = "ALTER TABLE `glpi_display` ADD INDEX ( `FK_users` )";
		$DB->query($query) or die("0.68.1 add index on glpi_display.FK_users ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_docs", "FK_users")) {
		$query = "ALTER TABLE `glpi_docs` ADD INDEX ( `FK_users` )";
		$DB->query($query) or die("0.68.1 add index on glpi_docs.FK_users ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_docs", "FK_tracking")) {
		$query = "ALTER TABLE `glpi_docs` ADD INDEX ( `FK_tracking` )";
		$DB->query($query) or die("0.68.1 add index on glpi_docs.FK_tracking ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_doc_device", "device_type")) {
		$query = "ALTER TABLE `glpi_doc_device` ADD INDEX ( `device_type` )";
		$DB->query($query) or die("0.68.1 add index on glpi_doc_device.device_type ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_dropdown_tracking_category", "parentID")) {
		$query = "ALTER TABLE `glpi_dropdown_tracking_category` ADD INDEX ( `parentID` )";
		$DB->query($query) or die("0.68.1 add index on glpi_dropdown_tracking_category.parentID ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_history", "device_type")) {
		$query = "ALTER TABLE `glpi_history` ADD INDEX ( `device_type` )";
		$DB->query($query) or die("0.68.1 add index on glpi_history.device_type ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_history", "device_internal_type")) {
		$query = "ALTER TABLE `glpi_history` ADD INDEX ( `device_internal_type` )";
		$DB->query($query) or die("0.68.1 add index on glpi_history.device_internal_type ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_infocoms", "budget")) {
		$query = "ALTER TABLE `glpi_infocoms` ADD INDEX ( `budget` )";
		$DB->query($query) or die("0.68.1 add index on glpi_infocoms.budget ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_infocoms", "alert")) {
		$query = "ALTER TABLE `glpi_infocoms` ADD INDEX ( `alert` )";
		$DB->query($query) or die("0.68.1 add index on glpi_infocoms.alert ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_kbitems", "author")) {
		$query = "ALTER TABLE `glpi_kbitems` ADD INDEX ( `author` )";
		$DB->query($query) or die("0.68.1 add index on glpi_kbitems.author ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_kbitems", "faq")) {
		$query = "ALTER TABLE `glpi_kbitems` ADD INDEX ( `faq` )";
		$DB->query($query) or die("0.68.1 add index on glpi_kbitems.faq ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_licenses", "oem_computer")) {
		$query = "ALTER TABLE `glpi_licenses` ADD INDEX ( `oem_computer` )";
		$DB->query($query) or die("0.68.1 add index on glpi_licenses.oem_computer ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_licenses", "oem")) {
		$query = "ALTER TABLE `glpi_licenses` ADD INDEX ( `oem` )";
		$DB->query($query) or die("0.68.1 add index on glpi_licenses.oem ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_licenses", "buy")) {
		$query = "ALTER TABLE `glpi_licenses` ADD INDEX ( `buy` )";
		$DB->query($query) or die("0.68.1 add index on glpi_licenses.buy ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_licenses", "serial")) {
		$query = "ALTER TABLE `glpi_licenses` ADD INDEX ( `serial` )";
		$DB->query($query) or die("0.68.1 add index on glpi_licenses.serial ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_licenses", "expire")) {
		$query = "ALTER TABLE `glpi_licenses` ADD INDEX ( `expire` )";
		$DB->query($query) or die("0.68.1 add index on glpi_licenses.expire ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_networking", "network")) {
		$query = "ALTER TABLE `glpi_networking` ADD INDEX ( `network` )";
		$DB->query($query) or die("0.68.1 add index on glpi_networking.network ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_networking", "domain")) {
		$query = "ALTER TABLE `glpi_networking` ADD INDEX ( `domain` )";
		$DB->query($query) or die("0.68.1 add index on glpi_networking.domain ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_networking_ports", "iface")) {
		$query = "ALTER TABLE `glpi_networking_ports` ADD INDEX ( `iface` )";
		$DB->query($query) or die("0.68.1 add index on glpi_networking_ports.iface ".$LANG['update'][90].$DB->error());
	}

	if(FieldExists("glpi_phones", "power")) {
		$query = "ALTER TABLE `glpi_phones` CHANGE `power` `power` INT NOT NULL DEFAULT '0'";
		$DB->query($query) or die("0.68.1 alter glpi_phones.power ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_phones", "power")) {
		$query = "ALTER TABLE `glpi_phones` ADD INDEX ( `power` )";
		$DB->query($query) or die("0.68.1 add index on glpi_phones.power ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_reminder", "begin")) {
		$query = "ALTER TABLE `glpi_reminder` ADD INDEX ( `begin` )";
		$DB->query($query) or die("0.68.1 add index on glpi_reminder.begin ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_reminder", "end")) {
		$query = "ALTER TABLE `glpi_reminder` ADD INDEX ( `end` )";
		$DB->query($query) or die("0.68.1 add index on glpi_reminder.end ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_software", "update_software")) {
		$query = "ALTER TABLE `glpi_software` ADD INDEX ( `update_software` )";
		$DB->query($query) or die("0.68.1 add index on glpi_software.update_software ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_state_item", "state")) {
		$query = "ALTER TABLE `glpi_state_item` ADD INDEX ( `state` )";
		$DB->query($query) or die("0.68.1 add index on glpi_state_item.state ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_tracking", "FK_group")) {
		$query = "ALTER TABLE `glpi_tracking` ADD INDEX ( `FK_group` )";
		$DB->query($query) or die("0.68.1 add index on glpi_tracking.FK_group ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_tracking", "assign_ent")) {
		$query = "ALTER TABLE `glpi_tracking` ADD INDEX ( `assign_ent` )";
		$DB->query($query) or die("0.68.1 add index on glpi_tracking.assign_ent ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_tracking", "device_type")) {
		$query = "ALTER TABLE `glpi_tracking` ADD INDEX ( `device_type` )";
		$DB->query($query) or die("0.68.1 add index on glpi_tracking.device_type ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_tracking", "priority")) {
		$query = "ALTER TABLE `glpi_tracking` ADD INDEX ( `priority` )";
		$DB->query($query) or die("0.68.1 add index on glpi_tracking.priority ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_tracking", "request_type")) {
		$query = "ALTER TABLE `glpi_tracking` ADD INDEX ( `request_type` )";
		$DB->query($query) or die("0.68.1 add index on glpi_tracking.request_type ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_users", "location")) {
		$query = "ALTER TABLE `glpi_users` ADD INDEX ( `location` )";
		$DB->query($query) or die("0.68.1 add index on glpi_users.location ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_printers", "network")) {
		$query = "ALTER TABLE `glpi_printers` ADD INDEX ( `network` )";
		$DB->query($query) or die("0.68.1 add index on glpi_printers.network ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_printers", "domain")) {
		$query = "ALTER TABLE `glpi_printers` ADD INDEX ( `domain` )";
		$DB->query($query) or die("0.68.1 add index on glpi_printers.domain ".$LANG['update'][90].$DB->error());
	}

	if(FieldExists("glpi_device_case", "format")) {
		$query = "ALTER TABLE `glpi_device_case` CHANGE `format` `format` ENUM( 'Grand', 'Moyen', 'Micro', 'Slim', '' ) NULL DEFAULT 'Moyen'";
		$DB->query($query) or die("0.68.1 alter glpi_device_case.format ".$LANG['update'][90].$DB->error());
	}

	if(FieldExists("glpi_device_gfxcard", "interface")) {
		$query = "ALTER TABLE `glpi_device_gfxcard` CHANGE `interface` `interface` ENUM( 'AGP', 'PCI', 'PCI-X', 'Other', '' ) NULL DEFAULT 'AGP'";
		$DB->query($query) or die("0.68.1 alter glpi_device_gfxcard.interface ".$LANG['update'][90].$DB->error());
	}

	// Add default values in GLPI_DROPDOWN_HDD_TYPE
	// Rename glpi_dropdown HDD_TYPE -> INTERFACE
	if(!TableExists("glpi_dropdown_interface")) {
		$query = "ALTER TABLE `glpi_dropdown_hdd_type` RENAME `glpi_dropdown_interface` ";
		$DB->query($query) or die("0.68.1 alter dropdown_hdd_type -> dropdown_interface ".$LANG['update'][90].$DB->error());

		$values=array("SATA","IDE","SCSI","USB");
		$interfaces=array();
		foreach ($values as $val){
			$query="SELECT * FROM glpi_dropdown_interface WHERE name LIKE '$val';";
			$result=$DB->query($query);
			if ($DB->numrows($result)==1){
				$row=$DB->fetch_array($result);
				$interfaces[$val]=$row["ID"];
			} else {
				$query="INSERT INTO glpi_dropdown_interface (`name`) VALUES ('$val');";
				$DB->query($query);
				$interfaces[$val]=$DB->insert_id();
			}
		}
		// ALTER TABLES
		$query = "ALTER TABLE `glpi_device_control` CHANGE `interface` `interface2` ENUM( 'IDE', 'SATA', 'SCSI', 'USB' ) NOT NULL DEFAULT 'IDE'";
		$DB->query($query) or die("0.68.1 alter device_control ".$LANG['update'][90].$DB->error());
		$query = "ALTER TABLE `glpi_device_drive` CHANGE `interface` `interface2` ENUM( 'IDE', 'SATA', 'SCSI' )  NOT NULL DEFAULT 'IDE'";
		$DB->query($query) or die("0.68.1 alter device_drive ".$LANG['update'][90].$DB->error());

		$query = "ALTER TABLE `glpi_device_control` ADD `interface` INT NULL ";
		$DB->query($query) or die("0.68.1 alter device_control ".$LANG['update'][90].$DB->error());
		$query = "ALTER TABLE `glpi_device_drive` ADD `interface` INT NULL ";
		$DB->query($query) or die("0.68.1 alter device_drive ".$LANG['update'][90].$DB->error());


		foreach ($interfaces as $name => $ID){
			$query="UPDATE glpi_device_drive SET interface='$ID' WHERE interface2='$name';";
			$DB->query($query) or die("0.68.1 update data device_drive ".$LANG['update'][90].$DB->error());
			$query="UPDATE glpi_device_control SET interface='$ID' WHERE interface2='$name';";
			$DB->query($query) or die("0.68.1 update data device_control ".$LANG['update'][90].$DB->error());
		}

		// DROP TABLES
		$query = "ALTER TABLE `glpi_device_control` DROP `interface2`;";
		$DB->query($query) or die("0.68.1 drop interface2 device_drive ".$LANG['update'][90].$DB->error());
		$query = "ALTER TABLE `glpi_device_drive` DROP `interface2`;";
		$DB->query($query) or die("0.68.1 drop interface2 device_drive ".$LANG['update'][90].$DB->error());

		// ADD INDEX
		$query = "ALTER TABLE `glpi_device_drive` ADD INDEX ( `interface` )";
		$DB->query($query) or die("0.68.1 add index on glpi_device_drive.interface ".$LANG['update'][90].$DB->error());
		$query = "ALTER TABLE `glpi_device_control` ADD INDEX ( `interface` )";
		$DB->query($query) or die("0.68.1 add index on glpi_device_drive.interface ".$LANG['update'][90].$DB->error());

	}

	if(FieldExists("glpi_profiles", "update")) {
		$query = "ALTER TABLE `glpi_profiles` CHANGE `update` `check_update` CHAR( 1 ) NULL DEFAULT NULL";
		$DB->query($query) or die("0.68.1 alter glpi_profiles.update ".$LANG['update'][90].$DB->error());
	}

	if(FieldExists("glpi_config", "last_update_check")) {
		$query = "ALTER TABLE `glpi_config` DROP `last_update_check`;";
		$DB->query($query) or die("0.68.1 drop glpi_config.last_update_check ".$LANG['update'][90].$DB->error());
	}


	if(!FieldExists("glpi_config", "keep_tracking_on_delete")) {
		$query = "ALTER TABLE `glpi_config` ADD `keep_tracking_on_delete` INT DEFAULT '1'";
		$DB->query($query) or die("0.68.1 drop glpi_config.keep_tracking_on_delete ".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_config", "show_admin_doc")) {
		$query = "ALTER TABLE `glpi_config` ADD `show_admin_doc` INT DEFAULT '0' ";
		$DB->query($query) or die("0.68.1 drop glpi_config.show_admin_doc ".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_config", "time_step")) {
		$query = "ALTER TABLE `glpi_config` ADD `time_step` INT DEFAULT '5' ";
		$DB->query($query) or die("0.68.1 drop glpi_config.time_step ".$LANG['update'][90].$DB->error());
	}

	$query="UPDATE glpi_config SET time_step='5', show_admin_doc='0', keep_tracking_on_delete='0';";
	$DB->query($query) or die("0.68.1 update glpi_config data ".$LANG['update'][90].$DB->error());

	if(!FieldExists("glpi_ocs_config", "cron_sync_number")) {
		$query = "ALTER TABLE `glpi_ocs_config` ADD `cron_sync_number` INT DEFAULT '1' ";
		$DB->query($query) or die("0.68.1 drop glpi_ocs_config.cron_sync_number ".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_profiles", "show_group_ticket")) {
		$query = "ALTER TABLE `glpi_profiles` ADD `show_group_ticket` char(1) DEFAULT '0' ";
		$DB->query($query) or die("0.68.1 drop glpi_profiles.show_group_ticket ".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_config", "ldap_group_condition")) {
		$query = "ALTER TABLE `glpi_config` ADD `ldap_group_condition` VARCHAR( 255 ) NULL ,
			ADD `ldap_search_for_groups` TINYINT NOT NULL DEFAULT '0',
			ADD `ldap_field_group_member` VARCHAR( 255 ) NULL ";
		$DB->query($query) or die("0.68.1 add glpi_config.ldap_*_groups ".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_groups", "ldap_group_dn")) {
		$query = "ALTER TABLE `glpi_groups` ADD `ldap_group_dn` VARCHAR( 255 ) NULL ";
		$DB->query($query) or die("0.68.1 add glpi_groups.ldap_group_dn ".$LANG['update'][90].$DB->error());
	}


	if(!FieldExists("glpi_ocs_link", "ocs_deviceid")) {
		$query = "ALTER TABLE `glpi_ocs_link` CHANGE `ocs_id` `ocs_deviceid` VARCHAR( 255 ) NOT NULL ;";
		$DB->query($query) or die("0.68.1 add glpi_ocs_link.ocs_deviceid ".$LANG['update'][90].$DB->error());
	}


	if(!FieldExists("glpi_ocs_link", "ocs_id")) {
		$query = "ALTER TABLE `glpi_ocs_link` ADD `ocs_id` INT NOT NULL DEFAULT '0' AFTER `glpi_id` ;";
		$DB->query($query) or die("0.68.1 add glpi_ocs_link.ocs_id ".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_ocs_link", "last_ocs_update")) {
		$query = "ALTER TABLE `glpi_ocs_link` ADD `last_ocs_update` DATETIME NULL AFTER `last_update` ;";
		$DB->query($query) or die("0.68.1 add glpi_ocs_link.last_ocs_update ".$LANG['update'][90].$DB->error());
	}






	if (countElementsInTable("glpi_ocs_link")){
		include_once (GLPI_ROOT . "/inc/commondbtm.class.php");
		include_once (GLPI_ROOT . "/inc/ocsng.function.php");
		include_once (GLPI_ROOT . "/inc/ocsng.class.php");
		$CFG_GLPI["ocs_mode"]=1;
		$DBocs=new DBocs(1);
		// Get datas to update
		$query="SELECT * 
			FROM glpi_ocs_link";
		$result_glpi=$DB->query($query);

		while ($data_glpi=$DB->fetch_array($result_glpi)){

			// Get ocs informations
			$query_ocs="SELECT * 
				FROM hardware WHERE DEVICEID='".$data_glpi["ocs_deviceid"]."' 
				LIMIT 1;";

			$result_ocs=$DBocs->query($query_ocs) or die("0.68.1 get ocs infos ".$LANG['update'][90].$DB->error());
			if ($result_ocs&&$DBocs->numrows($result_ocs)){
				$data_ocs=$DBocs->fetch_array($result_ocs);

				$query_update="UPDATE glpi_ocs_link
					SET ocs_id='".$data_ocs["ID"]."', 
					    last_ocs_update='".$data_ocs["LASTDATE"]."'
						    WHERE ID='".$data_glpi["ID"]."';";
				$DB->query($query_update) or die("0.68.1 update ocs infos ".$LANG['update'][90].$DB->error());
			}
		}
	}

	if(!TableExists("glpi_dropdown_case_type")) {

		$query ="CREATE TABLE `glpi_dropdown_case_type` (
			`ID` int(11) NOT NULL auto_increment,
			`name` varchar(255) NOT NULL,
			`comments` text,
			PRIMARY KEY  (`ID`),
			KEY `name` (`name`)
				) ENGINE=MyISAM ;";

		$DB->query($query) or die("0.68.1 add table dropdown_case_type ".$LANG['update'][90].$DB->error());
		// ajout du champs type
		$query = "ALTER TABLE `glpi_device_case` ADD  `type` INT( 11 ) default NULL AFTER `designation` ;";

		$DB->query($query) or die("0.68.1 add glpi_device_case.type ".$LANG['update'][90].$DB->error());

		// Ajout des entrees dans la table dropdown_case_type
		$query = "INSERT INTO `glpi_dropdown_case_type` ( `ID` , `name` , `comments` ) VALUES ('1' , 'Grand', NULL);";
		$DB->query($query) or die("0.68.1 glpi_device_case ".$LANG['update'][90].$DB->error());
		$query = "INSERT INTO `glpi_dropdown_case_type` ( `ID` , `name` , `comments` ) VALUES ('2' , 'Moyen', NULL);";
		$DB->query($query) or die("0.68.1 glpi_device_case ".$LANG['update'][90].$DB->error());
		$query = "INSERT INTO `glpi_dropdown_case_type` ( `ID` , `name` , `comments` ) VALUES ('3' , 'Micro', NULL);";
		$DB->query($query) or die("0.68.1 glpi_device_case ".$LANG['update'][90].$DB->error());

		// Mapping format enum / type

		$query = "UPDATE `glpi_device_case` SET  `type`='1' WHERE `format`='Grand';";
		$DB->query($query) or die("0.68.1 glpi_device_case ".$LANG['update'][90].$DB->error());
		$query = "UPDATE `glpi_device_case` SET  `type`='2' WHERE `format`='Moyen';";
		$DB->query($query) or die("0.68.1 glpi_device_case ".$LANG['update'][90].$DB->error());
		$query = "UPDATE `glpi_device_case` SET  `type`='3' WHERE `format`='Micro';";
		$DB->query($query) or die("0.68.1 glpi_device_case ".$LANG['update'][90].$DB->error());

		// Supression du champts format
		$query = "ALTER TABLE `glpi_device_case` DROP `format`;";
		$DB->query($query) or die("0.68.1 drop format from glpi_device_case ".$LANG['update'][90].$DB->error());


	}

	// Clean state datas
	if(TableExists("glpi_state_item")) {
		$query="SELECT COUNT(*) AS CPT, device_type, id_device 
			FROM glpi_state_item
			GROUP BY device_type, id_device
			HAVING CPT > 1";
		$result=$DB->query($query);
		if ($DB->numrows($result)){
			while ($data=$DB->fetch_array($result)){
				$query2="DELETE FROM glpi_state_item
								WHERE device_type='".$data["device_type"]."'
								AND id_device='".$data["id_device"]."'
								LIMIT ".($data["CPT"]-1).";";
				$DB->query($query2) or die("0.68.1 clean glpi_state_item ".$LANG['update'][90].$DB->error());
			}
		}
	
		if (isIndex("glpi_state_item","device_type")){
			$query=" ALTER TABLE `glpi_state_item` DROP INDEX `device_type` ;";
			$DB->query($query) or die("0.68.1 drop index glpi_state_item ".$LANG['update'][90].$DB->error());
		}
		if (isIndex("glpi_state_item","device_type2")){
			$query=" ALTER TABLE `glpi_state_item` DROP INDEX `device_type2` ;";
			$DB->query($query) or die("0.68.1 drop index glpi_state_item ".$LANG['update'][90].$DB->error());
		}
	
		$query=" ALTER TABLE `glpi_state_item` ADD INDEX ( `device_type` ) ";
		$DB->query($query) or die("0.68.1 add index glpi_state_item ".$LANG['update'][90].$DB->error());
		$query=" ALTER TABLE `glpi_state_item` ADD UNIQUE ( `device_type`,`id_device` ) ";
		$DB->query($query) or die("0.68.1 add unique glpi_state_item ".$LANG['update'][90].$DB->error());
	}


} // fin 0.68 #####################################################################################

?>
