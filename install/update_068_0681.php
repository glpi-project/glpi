<?php
/*
* @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi-project.org
 ----------------------------------------------------------------------

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
 ------------------------------------------------------------------------
*/

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

// Update from 0.68 to 0.68.1
function update068to0681(){
	global $db,$lang,$phproot,$cfg_glpi;

if(TableExists("glpi_repair_item")) {
	$query = "DROP TABLE `glpi_repair_item`;";
	$db->query($query) or die("0.68.1 drop glpi_repair_item ".$lang["update"][90].$db->error());
}

$tables=array("computers","monitors","networking","peripherals","phones","printers");
foreach ($tables as $tbl){
	if (isIndex("glpi_".$tbl,"type")){
		$query = "ALTER TABLE `glpi_$tbl` DROP INDEX `type`;";
		$db->query($query) or die("0.68.1 drop index type glpi_$tbl ".$lang["update"][90].$db->error());
	}
	if (isIndex("glpi_".$tbl,"type_2")){
		$query = "ALTER TABLE `glpi_$tbl` DROP INDEX `type_2`;";
		$db->query($query) or die("0.68.1 drop index type_2 glpi_$tbl ".$lang["update"][90].$db->error());
	}
	if (isIndex("glpi_".$tbl,"model")){
		$query = "ALTER TABLE `glpi_$tbl` DROP INDEX `model`;";
		$db->query($query) or die("0.68.1 drop index model glpi_$tbl ".$lang["update"][90].$db->error());
	}

	if (!isIndex("glpi_".$tbl,"type")){
		$query = "ALTER TABLE `glpi_$tbl` ADD INDEX ( `type` )";
		$db->query($query) or die("0.68.1 add index type glpi_$tbl ".$lang["update"][90].$db->error());
	}
	if (!isIndex("glpi_".$tbl,"model")){
		$query = "ALTER TABLE `glpi_$tbl` ADD INDEX ( `model` )";
		$db->query($query) or die("0.68.1 add index model glpi_$tbl ".$lang["update"][90].$db->error());
	}

	if(!isIndex("glpi_".$tbl, "FK_groups")) {
		$query = "ALTER TABLE `glpi_$tbl` ADD INDEX ( `FK_groups` )";
		$db->query($query) or die("0.68.1 add index on glpi_$tbl.FK_groups ".$lang["update"][90].$db->error());
	}
	
	if(!isIndex("glpi_".$tbl, "FK_users")) {
		$query = "ALTER TABLE `glpi_$tbl` ADD INDEX ( `FK_users` )";
		$db->query($query) or die("0.68.1 add index on glpi_$tbl.FK_users ".$lang["update"][90].$db->error());
	}

}


if(!isIndex("glpi_software", "FK_groups")) {
	$query = "ALTER TABLE `glpi_software` ADD INDEX ( `FK_groups` )";
	$db->query($query) or die("0.68.1 add index on glpi_software.FK_groups ".$lang["update"][90].$db->error());
}
	
if(!isIndex("glpi_software", "FK_users")) {
	$query = "ALTER TABLE `glpi_software` ADD INDEX ( `FK_users` )";
	$db->query($query) or die("0.68.1 add index on glpi_software.FK_users ".$lang["update"][90].$db->error());
}


if(!isIndex("glpi_cartridges_type", "location")) {
	$query = "ALTER TABLE `glpi_cartridges_type` ADD INDEX ( `location` )";
	$db->query($query) or die("0.68.1 add index on glpi_cartridges_type.location ".$lang["update"][90].$db->error());
}

if(FieldExists("glpi_cartridges_type", "type")) {
	$query = "ALTER TABLE `glpi_cartridges_type` CHANGE `type` `type` INT NOT NULL DEFAULT '0'";
	$db->query($query) or die("0.68.1 alter glpi_cartridges_type.type ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_cartridges_type", "type")) {
	$query = "ALTER TABLE `glpi_cartridges_type` ADD INDEX ( type )";
	$db->query($query) or die("0.68.1 add index on glpi_cartridges_type.type ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_cartridges_type", "alarm")) {
	$query = "ALTER TABLE `glpi_cartridges_type` ADD INDEX ( alarm )";
	$db->query($query) or die("0.68.1 add index on glpi_cartridges_type.alarm ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_computers", "os_sp")) {
	$query = "ALTER TABLE `glpi_computers` ADD INDEX ( `os_sp` )";
	$db->query($query) or die("0.68.1 add index on glpi_computers.os_sp ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_computers", "os_version")) {
	$query = "ALTER TABLE `glpi_computers` ADD INDEX ( `os_version` )";
	$db->query($query) or die("0.68.1 add index on glpi_computers.os_version ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_computers", "network")) {
	$query = "ALTER TABLE `glpi_computers` ADD INDEX ( `network` )";
	$db->query($query) or die("0.68.1 add index on glpi_computers.network ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_computers", "domain")) {
	$query = "ALTER TABLE `glpi_computers` ADD INDEX ( `domain` )";
	$db->query($query) or die("0.68.1 add index on glpi_computers.domain ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_computers", "auto_update")) {
	$query = "ALTER TABLE `glpi_computers` ADD INDEX ( `auto_update` )";
	$db->query($query) or die("0.68.1 add index on glpi_computers.auto_update ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_computers", "ocs_import")) {
	$query = "ALTER TABLE `glpi_computers` ADD INDEX ( `ocs_import` )";
	$db->query($query) or die("0.68.1 add index on glpi_computers.ocs_import ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_consumables", "id_user")) {
	$query = "ALTER TABLE `glpi_consumables` ADD INDEX ( `id_user` )";
	$db->query($query) or die("0.68.1 add index on glpi_consumables.id_user ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_consumables_type", "location")) {
	$query = "ALTER TABLE `glpi_consumables_type` ADD INDEX ( `location` )";
	$db->query($query) or die("0.68.1 add index on glpi_consumables_type.location ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_consumables_type", "type")) {
	$query = "ALTER TABLE `glpi_consumables_type` ADD INDEX ( `type` )";
	$db->query($query) or die("0.68.1 add index on glpi_consumables_type.type ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_consumables_type", "alarm")) {
	$query = "ALTER TABLE `glpi_consumables_type` ADD INDEX ( `alarm` )";
	$db->query($query) or die("0.68.1 add index on glpi_consumables_type.alarm ".$lang["update"][90].$db->error());
}

if(FieldExists("glpi_contacts", "type")) {
	$query = "ALTER TABLE `glpi_contacts` CHANGE `type` `type` INT( 11 ) NULL ";
	$db->query($query) or die("0.68.1 alter glpi_contacts.type ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_contract_device", "device_type")) {
	$query = "ALTER TABLE `glpi_contract_device` ADD INDEX ( `device_type` )";
	$db->query($query) or die("0.68.1 add index on glpi_contract_device.device_type ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_contract_device", "is_template")) {
	$query = "ALTER TABLE `glpi_contract_device` ADD INDEX ( `is_template` )";
	$db->query($query) or die("0.68.1 add index on glpi_contract_device.is_template ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_device_hdd", "interface")) {
	$query = "ALTER TABLE `glpi_device_hdd` ADD INDEX ( `interface` )";
	$db->query($query) or die("0.68.1 add index on glpi_device_hdd.interface ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_device_ram", "type")) {
	$query = "ALTER TABLE `glpi_device_ram` ADD INDEX ( `type` )";
	$db->query($query) or die("0.68.1 add index on glpi_device_ram.type ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_display", "FK_users")) {
	$query = "ALTER TABLE `glpi_display` ADD INDEX ( `FK_users` )";
	$db->query($query) or die("0.68.1 add index on glpi_display.FK_users ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_docs", "FK_users")) {
	$query = "ALTER TABLE `glpi_docs` ADD INDEX ( `FK_users` )";
	$db->query($query) or die("0.68.1 add index on glpi_docs.FK_users ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_docs", "FK_tracking")) {
	$query = "ALTER TABLE `glpi_docs` ADD INDEX ( `FK_tracking` )";
	$db->query($query) or die("0.68.1 add index on glpi_docs.FK_tracking ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_doc_device", "device_type")) {
	$query = "ALTER TABLE `glpi_doc_device` ADD INDEX ( `device_type` )";
	$db->query($query) or die("0.68.1 add index on glpi_doc_device.device_type ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_dropdown_tracking_category", "parentID")) {
	$query = "ALTER TABLE `glpi_dropdown_tracking_category` ADD INDEX ( `parentID` )";
	$db->query($query) or die("0.68.1 add index on glpi_dropdown_tracking_category.parentID ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_history", "device_type")) {
	$query = "ALTER TABLE `glpi_history` ADD INDEX ( `device_type` )";
	$db->query($query) or die("0.68.1 add index on glpi_history.device_type ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_history", "device_internal_type")) {
	$query = "ALTER TABLE `glpi_history` ADD INDEX ( `device_internal_type` )";
	$db->query($query) or die("0.68.1 add index on glpi_history.device_internal_type ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_infocoms", "budget")) {
	$query = "ALTER TABLE `glpi_infocoms` ADD INDEX ( `budget` )";
	$db->query($query) or die("0.68.1 add index on glpi_infocoms.budget ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_infocoms", "alert")) {
	$query = "ALTER TABLE `glpi_infocoms` ADD INDEX ( `alert` )";
	$db->query($query) or die("0.68.1 add index on glpi_infocoms.alert ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_kbitems", "author")) {
	$query = "ALTER TABLE `glpi_kbitems` ADD INDEX ( `author` )";
	$db->query($query) or die("0.68.1 add index on glpi_kbitems.author ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_kbitems", "faq")) {
	$query = "ALTER TABLE `glpi_kbitems` ADD INDEX ( `faq` )";
	$db->query($query) or die("0.68.1 add index on glpi_kbitems.faq ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_licenses", "oem_computer")) {
	$query = "ALTER TABLE `glpi_licenses` ADD INDEX ( `oem_computer` )";
	$db->query($query) or die("0.68.1 add index on glpi_licenses.oem_computer ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_licenses", "oem")) {
	$query = "ALTER TABLE `glpi_licenses` ADD INDEX ( `oem` )";
	$db->query($query) or die("0.68.1 add index on glpi_licenses.oem ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_licenses", "buy")) {
	$query = "ALTER TABLE `glpi_licenses` ADD INDEX ( `buy` )";
	$db->query($query) or die("0.68.1 add index on glpi_licenses.buy ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_licenses", "serial")) {
	$query = "ALTER TABLE `glpi_licenses` ADD INDEX ( `serial` )";
	$db->query($query) or die("0.68.1 add index on glpi_licenses.serial ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_licenses", "expire")) {
	$query = "ALTER TABLE `glpi_licenses` ADD INDEX ( `expire` )";
	$db->query($query) or die("0.68.1 add index on glpi_licenses.expire ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_networking", "network")) {
	$query = "ALTER TABLE `glpi_networking` ADD INDEX ( `network` )";
	$db->query($query) or die("0.68.1 add index on glpi_networking.network ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_networking", "domain")) {
	$query = "ALTER TABLE `glpi_networking` ADD INDEX ( `domain` )";
	$db->query($query) or die("0.68.1 add index on glpi_networking.domain ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_networking_ports", "iface")) {
	$query = "ALTER TABLE `glpi_networking_ports` ADD INDEX ( `iface` )";
	$db->query($query) or die("0.68.1 add index on glpi_networking_ports.iface ".$lang["update"][90].$db->error());
}

if(FieldExists("glpi_phones", "power")) {
	$query = "ALTER TABLE `glpi_phones` CHANGE `power` `power` INT NOT NULL DEFAULT '0'";
	$db->query($query) or die("0.68.1 alter glpi_phones.power ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_phones", "power")) {
	$query = "ALTER TABLE `glpi_phones` ADD INDEX ( `power` )";
	$db->query($query) or die("0.68.1 add index on glpi_phones.power ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_reminder", "begin")) {
	$query = "ALTER TABLE `glpi_reminder` ADD INDEX ( `begin` )";
	$db->query($query) or die("0.68.1 add index on glpi_reminder.begin ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_reminder", "end")) {
	$query = "ALTER TABLE `glpi_reminder` ADD INDEX ( `end` )";
	$db->query($query) or die("0.68.1 add index on glpi_reminder.end ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_software", "update_software")) {
	$query = "ALTER TABLE `glpi_software` ADD INDEX ( `update_software` )";
	$db->query($query) or die("0.68.1 add index on glpi_software.update_software ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_state_item", "state")) {
	$query = "ALTER TABLE `glpi_state_item` ADD INDEX ( `state` )";
	$db->query($query) or die("0.68.1 add index on glpi_state_item.state ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_tracking", "FK_group")) {
	$query = "ALTER TABLE `glpi_tracking` ADD INDEX ( `FK_group` )";
	$db->query($query) or die("0.68.1 add index on glpi_tracking.FK_group ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_tracking", "assign_ent")) {
	$query = "ALTER TABLE `glpi_tracking` ADD INDEX ( `assign_ent` )";
	$db->query($query) or die("0.68.1 add index on glpi_tracking.assign_ent ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_tracking", "device_type")) {
	$query = "ALTER TABLE `glpi_tracking` ADD INDEX ( `device_type` )";
	$db->query($query) or die("0.68.1 add index on glpi_tracking.device_type ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_tracking", "priority")) {
	$query = "ALTER TABLE `glpi_tracking` ADD INDEX ( `priority` )";
	$db->query($query) or die("0.68.1 add index on glpi_tracking.priority ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_tracking", "request_type")) {
	$query = "ALTER TABLE `glpi_tracking` ADD INDEX ( `request_type` )";
	$db->query($query) or die("0.68.1 add index on glpi_tracking.request_type ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_users", "location")) {
	$query = "ALTER TABLE `glpi_users` ADD INDEX ( `location` )";
	$db->query($query) or die("0.68.1 add index on glpi_users.location ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_printers", "network")) {
	$query = "ALTER TABLE `glpi_printers` ADD INDEX ( `network` )";
	$db->query($query) or die("0.68.1 add index on glpi_printers.network ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_printers", "domain")) {
	$query = "ALTER TABLE `glpi_printers` ADD INDEX ( `domain` )";
	$db->query($query) or die("0.68.1 add index on glpi_printers.domain ".$lang["update"][90].$db->error());
}

if(FieldExists("glpi_device_case", "format")) {
	$query = "ALTER TABLE `glpi_device_case` CHANGE `format` `format` ENUM( 'Grand', 'Moyen', 'Micro', 'Slim', '' ) NULL DEFAULT 'Moyen'";
	$db->query($query) or die("0.68.1 alter glpi_device_case.format ".$lang["update"][90].$db->error());
}

if(FieldExists("glpi_device_gfxcard", "interface")) {
	$query = "ALTER TABLE `glpi_device_gfxcard` CHANGE `interface` `interface` ENUM( 'AGP', 'PCI', 'PCI-X', 'Other', '' ) NULL DEFAULT 'AGP'";
	$db->query($query) or die("0.68.1 alter glpi_device_gfxcard.interface ".$lang["update"][90].$db->error());
}

// Add default values in GLPI_DROPDOWN_HDD_TYPE
// Rename glpi_dropdown HDD_TYPE -> INTERFACE
if(!TableExists("glpi_dropdown_interface")) {
	$query = "ALTER TABLE `glpi_dropdown_hdd_type` RENAME `glpi_dropdown_interface` ";
	$db->query($query) or die("0.68.1 alter dropdown_hdd_type -> dropdown_interface ".$lang["update"][90].$db->error());
	
	$values=array("SATA","IDE","SCSI","USB");
	$interfaces=array();
	foreach ($values as $val){
		$query="SELECT * FROM glpi_dropdown_interface WHERE name LIKE '$val';";
		$result=$db->query($query);
		if ($db->numrows($result)==1){
			$row=$db->fetch_array($result);
			$interfaces[$val]=$row["ID"];
		} else {
			$query="INSERT INTO glpi_dropdown_interface (`name`) VALUES ('$val');";
			$db->query($query);
			$interfaces[$val]=$db->insert_id();
		}
	}
	// ALTER TABLES
	$query = "ALTER TABLE `glpi_device_control` CHANGE `interface` `interface2` ENUM( 'IDE', 'SATA', 'SCSI', 'USB' ) NOT NULL DEFAULT 'IDE'";
	$db->query($query) or die("0.68.1 alter device_control ".$lang["update"][90].$db->error());
	$query = "ALTER TABLE `glpi_device_drive` CHANGE `interface` `interface2` ENUM( 'IDE', 'SATA', 'SCSI' )  NOT NULL DEFAULT 'IDE'";
	$db->query($query) or die("0.68.1 alter device_drive ".$lang["update"][90].$db->error());

	$query = "ALTER TABLE `glpi_device_control` ADD `interface` INT NULL ";
	$db->query($query) or die("0.68.1 alter device_control ".$lang["update"][90].$db->error());
	$query = "ALTER TABLE `glpi_device_drive` ADD `interface` INT NULL ";
	$db->query($query) or die("0.68.1 alter device_drive ".$lang["update"][90].$db->error());
	

	foreach ($interfaces as $name => $ID){
		$query="UPDATE glpi_device_drive SET interface='$ID' WHERE interface2='$name';";
		$db->query($query) or die("0.68.1 update data device_drive ".$lang["update"][90].$db->error());
		$query="UPDATE glpi_device_control SET interface='$ID' WHERE interface2='$name';";
		$db->query($query) or die("0.68.1 update data device_control ".$lang["update"][90].$db->error());
	}

	// DROP TABLES
	$query = "ALTER TABLE `glpi_device_control` DROP `interface2`;";
	$db->query($query) or die("0.68.1 drop interface2 device_drive ".$lang["update"][90].$db->error());
	$query = "ALTER TABLE `glpi_device_drive` DROP `interface2`;";
	$db->query($query) or die("0.68.1 drop interface2 device_drive ".$lang["update"][90].$db->error());
	
	// ADD INDEX
	$query = "ALTER TABLE `glpi_device_drive` ADD INDEX ( `interface` )";
	$db->query($query) or die("0.68.1 add index on glpi_device_drive.interface ".$lang["update"][90].$db->error());
	$query = "ALTER TABLE `glpi_device_control` ADD INDEX ( `interface` )";
	$db->query($query) or die("0.68.1 add index on glpi_device_drive.interface ".$lang["update"][90].$db->error());

}

if(FieldExists("glpi_profiles", "update")) {
	$query = "ALTER TABLE `glpi_profiles` CHANGE `update` `check_update` CHAR( 1 ) NULL DEFAULT NULL";
	$db->query($query) or die("0.68.1 alter glpi_profiles.update ".$lang["update"][90].$db->error());
}

if(FieldExists("glpi_config", "last_update_check")) {
	$query = "ALTER TABLE `glpi_config` DROP `last_update_check`;";
	$db->query($query) or die("0.68.1 drop glpi_config.last_update_check ".$lang["update"][90].$db->error());
}


if(!FieldExists("glpi_config", "keep_tracking_on_delete")) {
	$query = "ALTER TABLE `glpi_config` ADD `keep_tracking_on_delete` INT DEFAULT '1'";
	$db->query($query) or die("0.68.1 drop glpi_config.keep_tracking_on_delete ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_config", "show_admin_doc")) {
	$query = "ALTER TABLE `glpi_config` ADD `show_admin_doc` INT DEFAULT '0' ";
	$db->query($query) or die("0.68.1 drop glpi_config.show_admin_doc ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_config", "time_step")) {
	$query = "ALTER TABLE `glpi_config` ADD `time_step` INT DEFAULT '5' ";
	$db->query($query) or die("0.68.1 drop glpi_config.time_step ".$lang["update"][90].$db->error());
}

$query="UPDATE glpi_config SET time_step='5', show_admin_doc='0', keep_tracking_on_delete='0';";
$db->query($query) or die("0.68.1 update glpi_config data ".$lang["update"][90].$db->error());

if(!FieldExists("glpi_ocs_config", "cron_sync_number")) {
	$query = "ALTER TABLE `glpi_ocs_config` ADD `cron_sync_number` INT DEFAULT '1' ";
	$db->query($query) or die("0.68.1 drop glpi_ocs_config.cron_sync_number ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_profiles", "show_group_ticket")) {
	$query = "ALTER TABLE `glpi_profiles` ADD `show_group_ticket` char(1) DEFAULT '0' ";
	$db->query($query) or die("0.68.1 drop glpi_profiles.show_group_ticket ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_config", "ldap_group_condition")) {
	$query = "ALTER TABLE `glpi_config` ADD `ldap_group_condition` VARCHAR( 255 ) NULL ,
ADD `ldap_search_for_groups` TINYINT NOT NULL DEFAULT '0',
ADD `ldap_field_group_member` VARCHAR( 255 ) NULL ";
	$db->query($query) or die("0.68.1 add glpi_config.ldap_*_groups ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_groups", "ldap_group_dn")) {
	$query = "ALTER TABLE `glpi_groups` ADD `ldap_group_dn` VARCHAR( 255 ) NULL ";
	$db->query($query) or die("0.68.1 add glpi_groups.ldap_group_dn ".$lang["update"][90].$db->error());
}


if(!FieldExists("glpi_ocs_link", "ocs_deviceid")) {
	$query = "ALTER TABLE `glpi_ocs_link` CHANGE `ocs_id` `ocs_deviceid` VARCHAR( 255 ) NOT NULL ;";
	$db->query($query) or die("0.68.1 add glpi_ocs_link.ocs_deviceid ".$lang["update"][90].$db->error());
}


if(!FieldExists("glpi_ocs_link", "ocs_id")) {
	$query = "ALTER TABLE `glpi_ocs_link` ADD `ocs_id` INT NOT NULL DEFAULT '0' AFTER `glpi_id` ;";
	$db->query($query) or die("0.68.1 add glpi_ocs_link.ocs_id ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_ocs_link", "last_ocs_update")) {
	$query = "ALTER TABLE `glpi_ocs_link` ADD `last_ocs_update` DATETIME NULL AFTER `last_update` ;";
	$db->query($query) or die("0.68.1 add glpi_ocs_link.last_ocs_update ".$lang["update"][90].$db->error());
}






if (countElementsInTable("glpi_ocs_link")){
	include ($phproot . "/inc/ocsng.class.php");
	$cfg_glpi["ocs_mode"]=1;
	$dbocs=new DBocs;
	// Get datas to update
	$query="SELECT * 
			FROM glpi_ocs_link";
	$result_glpi=$db->query($query);

	while ($data_glpi=$db->fetch_array($result_glpi)){

		// Get ocs informations
		$query_ocs="SELECT * 
					FROM hardware WHERE DEVICEID='".$data_glpi["ocs_deviceid"]."' 
					LIMIT 1;";

		$result_ocs=$dbocs->query($query_ocs) or die("0.68.1 get ocs infos ".$lang["update"][90].$db->error());
		if ($result_ocs&&$dbocs->numrows($result_ocs)){
			$data_ocs=$dbocs->fetch_array($result_ocs);
			
			$query_update="UPDATE glpi_ocs_link
						SET ocs_id='".$data_ocs["ID"]."', 
							last_ocs_update='".$data_ocs["LASTDATE"]."'
						WHERE ID='".$data_glpi["ID"]."';";
			$db->query($query_update) or die("0.68.1 update ocs infos ".$lang["update"][90].$db->error());
		}
	}
}


} // fin 0.68 #####################################################################################

?>