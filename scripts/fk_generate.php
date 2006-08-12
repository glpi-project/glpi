<?
/*
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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
include ($phproot."/inc/includes.php");


$result=$db->list_tables();
$numtab=0;
while ($t=$db->fetch_array($result)){
	
	// on se  limite aux tables préfixées _glpi
	if (ereg("glpi_",$t[0])){
		$query="ALTER TABLE `$t[0]` TYPE = innodb";
		$db->query($query);
	}
}

// glpi_alerts -> based on device_type

// glpi_cartridges
 $query="ALTER TABLE `glpi_cartridges` CHANGE `FK_glpi_cartridges_type` `FK_glpi_cartridges_type` INT( 11 ) NULL ,
CHANGE `FK_glpi_printers` `FK_glpi_printers` INT( 11 ) NULL ";
$db->query($query) or die($query." ".$db->error());

$query = "ALTER TABLE `glpi_cartridges`
  ADD CONSTRAINT `glpi_cartridges_ibfk_2` FOREIGN KEY (`FK_glpi_printers`) REFERENCES `glpi_printers` (`ID`),
  ADD CONSTRAINT `glpi_cartridges_ibfk_1` FOREIGN KEY (`FK_glpi_cartridges_type`) REFERENCES `glpi_cartridges_type` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_cartridges_assoc
$query="ALTER TABLE `glpi_cartridges_assoc`
  ADD CONSTRAINT `glpi_cartridges_assoc_ibfk_2` FOREIGN KEY (`FK_glpi_dropdown_model_printers`) REFERENCES `glpi_dropdown_model_printers` (`ID`),
  ADD CONSTRAINT `glpi_cartridges_assoc_ibfk_1` FOREIGN KEY (`FK_glpi_cartridges_type`) REFERENCES `glpi_cartridges_type` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_cartridges_type
$query="ALTER TABLE `glpi_cartridges_type`
  ADD CONSTRAINT `glpi_cartridges_type_ibfk_16` FOREIGN KEY (`tech_num`) REFERENCES `glpi_users` (`ID`),
  ADD CONSTRAINT `glpi_cartridges_type_ibfk_13` FOREIGN KEY (`location`) REFERENCES `glpi_dropdown_locations` (`ID`),
  ADD CONSTRAINT `glpi_cartridges_type_ibfk_14` FOREIGN KEY (`type`) REFERENCES `glpi_dropdown_cartridge_type` (`ID`),
  ADD CONSTRAINT `glpi_cartridges_type_ibfk_15` FOREIGN KEY (`FK_glpi_enterprise`) REFERENCES `glpi_enterprises` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_computers
// Prepare DB
$query="
ALTER TABLE `glpi_computers` CHANGE `tech_num` `tech_num` INT( 11 ) NULL ,
CHANGE `os` `os` INT( 11 ) NULL ,
CHANGE `os_version` `os_version` INT( 11 ) NULL ,
CHANGE `os_sp` `os_sp` INT( 11 ) NULL ,
CHANGE `auto_update` `auto_update` INT( 11 ) NULL ,
CHANGE `location` `location` INT( 11 ) NULL ,
CHANGE `domain` `domain` INT( 11 ) NULL ,
CHANGE `network` `network` INT( 11 ) NULL ,
CHANGE `model` `model` INT( 11 ) NULL ,
CHANGE `type` `type` INT( 11 ) NULL ,
CHANGE `FK_glpi_enterprise` `FK_glpi_enterprise` INT( 11 ) NULL ";
$db->query($query) or die($query." ".$db->error());
$query="UPDATE `glpi_computers` SET 
`tech_num` = NULL ,
`os` = NULL ,
`os_version` = NULL ,
`os_sp` = NULL ,
`auto_update` = NULL ,
`location` = NULL ,
`domain` = NULL ,
`network` = NULL ,
`model` = NULL ,
`type` = NULL ,
`FK_glpi_enterprise` = NULL
 WHERE `ID` =1 LIMIT 1 ";
$db->query($query) or die($query." ".$db->error());

$query="ALTER TABLE `glpi_computers`
  ADD CONSTRAINT `glpi_computers_ibfk_21` FOREIGN KEY (`tech_num`) REFERENCES `glpi_users` (`ID`),
  ADD CONSTRAINT `glpi_computers_ibfk_22` FOREIGN KEY (`os`) REFERENCES `glpi_dropdown_os` (`ID`),
  ADD CONSTRAINT `glpi_computers_ibfk_23` FOREIGN KEY (`os_version`) REFERENCES `glpi_dropdown_os_version` (`ID`),
  ADD CONSTRAINT `glpi_computers_ibfk_24` FOREIGN KEY (`os_sp`) REFERENCES `glpi_dropdown_os_sp` (`ID`),
  ADD CONSTRAINT `glpi_computers_ibfk_25` FOREIGN KEY (`auto_update`) REFERENCES `glpi_dropdown_auto_update` (`ID`),
  ADD CONSTRAINT `glpi_computers_ibfk_26` FOREIGN KEY (`location`) REFERENCES `glpi_dropdown_locations` (`ID`),
  ADD CONSTRAINT `glpi_computers_ibfk_27` FOREIGN KEY (`domain`) REFERENCES `glpi_dropdown_domain` (`ID`),
  ADD CONSTRAINT `glpi_computers_ibfk_28` FOREIGN KEY (`network`) REFERENCES `glpi_dropdown_network` (`ID`),
  ADD CONSTRAINT `glpi_computers_ibfk_29` FOREIGN KEY (`model`) REFERENCES `glpi_dropdown_model` (`ID`),
  ADD CONSTRAINT `glpi_computers_ibfk_30` FOREIGN KEY (`type`) REFERENCES `glpi_type_computers` (`ID`),
  ADD CONSTRAINT `glpi_computers_ibfk_31` FOREIGN KEY (`FK_glpi_enterprise`) REFERENCES `glpi_enterprises` (`ID`),
  ADD CONSTRAINT `glpi_computers_ibfk_32` FOREIGN KEY (`FK_users`) REFERENCES `glpi_users` (`ID`),
  ADD CONSTRAINT `glpi_computers_ibfk_33` FOREIGN KEY (`FK_groups`) REFERENCES `glpi_groups` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_computer_device -> based on DEVICE_TYPE
$query = "ALTER TABLE `glpi_computer_device`
  ADD CONSTRAINT `glpi_computer_device_ibfk_1` FOREIGN KEY (`FK_computers`) REFERENCES `glpi_computers` (`ID`);";
$db->query($query) or die($query." ".$db->error());


// glpi_connect_wire -> based on device_type : end2 / type
$query = "ALTER TABLE `glpi_connect_wire`
  ADD CONSTRAINT `glpi_connect_wire_ibfk_1` FOREIGN KEY (`end2`) REFERENCES `glpi_computers` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_consumables
$query="ALTER TABLE `glpi_consumables` CHANGE `id_user` `id_user` INT( 11 ) NULL";
$db->query($query) or die($query." ".$db->error());
$query="UPDATE `glpi_consumables` set id_user= NULL WHERE id_user='0'";
$db->query($query) or die($query." ".$db->error());
$query = "ALTER TABLE `glpi_consumables`
  ADD CONSTRAINT `glpi_consumables_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `glpi_users` (`ID`),
  ADD CONSTRAINT `glpi_consumables_ibfk_1` FOREIGN KEY (`FK_glpi_consumables_type`) REFERENCES `glpi_consumables_type` (`ID`);";
$db->query($query) or die($query." ".$db->error());


// glpi_consumables_type
$query="ALTER TABLE `glpi_consumables_type`
  ADD CONSTRAINT `glpi_consumables_type_ibfk_4` FOREIGN KEY (`tech_num`) REFERENCES `glpi_users` (`ID`),
  ADD CONSTRAINT `glpi_consumables_type_ibfk_1` FOREIGN KEY (`location`) REFERENCES `glpi_dropdown_locations` (`ID`),
  ADD CONSTRAINT `glpi_consumables_type_ibfk_2` FOREIGN KEY (`type`) REFERENCES `glpi_dropdown_consumable_type` (`ID`),
  ADD CONSTRAINT `glpi_consumables_type_ibfk_3` FOREIGN KEY (`FK_glpi_enterprise`) REFERENCES `glpi_enterprises` (`ID`);";
$db->query($query) or die($query." ".$db->error());

//glpi_contacts
$query="ALTER TABLE `glpi_contacts`
  ADD CONSTRAINT `glpi_contacts_ibfk_1` FOREIGN KEY (`type`) REFERENCES `glpi_dropdown_contact_type` (`ID`);";
$db->query($query) or die($query." ".$db->error());

//glpi_contact_enterprise
$query="ALTER TABLE `glpi_contact_enterprise`
  ADD CONSTRAINT `glpi_contact_enterprise_ibfk_2` FOREIGN KEY (`FK_contact`) REFERENCES `glpi_contacts` (`ID`),
  ADD CONSTRAINT `glpi_contact_enterprise_ibfk_1` FOREIGN KEY (`FK_enterprise`) REFERENCES `glpi_enterprises` (`ID`);";
$db->query($query) or die($query." ".$db->error());

//glpi_contracts
$query="ALTER TABLE `glpi_contracts`
  ADD CONSTRAINT `glpi_contracts_ibfk_1` FOREIGN KEY (`contract_type`) REFERENCES `glpi_dropdown_contract_type` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_contract_device
$query="ALTER TABLE `glpi_contract_device`
  ADD CONSTRAINT `glpi_contract_device_ibfk_1` FOREIGN KEY (`FK_contract`) REFERENCES `glpi_contracts` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_contract_enterprise
$query="ALTER TABLE `glpi_contract_enterprise`
  ADD CONSTRAINT `glpi_contract_enterprise_ibfk_2` FOREIGN KEY (`FK_contract`) REFERENCES `glpi_contracts` (`ID`),
  ADD CONSTRAINT `glpi_contract_enterprise_ibfk_1` FOREIGN KEY (`FK_enterprise`) REFERENCES `glpi_enterprises` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_device_case
$query="ALTER TABLE `glpi_device_case`
  ADD CONSTRAINT `glpi_device_case_ibfk_1` FOREIGN KEY (`FK_glpi_enterprise`) REFERENCES `glpi_enterprises` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_device_control
$query="ALTER TABLE `glpi_device_control`
  ADD CONSTRAINT `glpi_device_control_ibfk_1` FOREIGN KEY (`FK_glpi_enterprise`) REFERENCES `glpi_enterprises` (`ID`),
  ADD CONSTRAINT `glpi_device_control_ibfk_2` FOREIGN KEY (`interface`) REFERENCES `glpi_dropdown_interface` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_device_drive
$query="ALTER TABLE `glpi_device_drive`
  ADD CONSTRAINT `glpi_device_drive_ibfk_1` FOREIGN KEY (`FK_glpi_enterprise`) REFERENCES `glpi_enterprises` (`ID`),
  ADD CONSTRAINT `glpi_device_drive_ibfk_2` FOREIGN KEY (`interface`) REFERENCES `glpi_dropdown_interface` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_device_gfxcard
$query="ALTER TABLE `glpi_device_gfxcard`
  ADD CONSTRAINT `glpi_device_gfxcard_ibfk_1` FOREIGN KEY (`FK_glpi_enterprise`) REFERENCES `glpi_enterprises` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_device_hdd
$query="ALTER TABLE `glpi_device_hdd`
  ADD CONSTRAINT `glpi_device_hdd_ibfk_2` FOREIGN KEY (`FK_glpi_enterprise`) REFERENCES `glpi_enterprises` (`ID`),
  ADD CONSTRAINT `glpi_device_hdd_ibfk_1` FOREIGN KEY (`interface`) REFERENCES `glpi_dropdown_interface` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_device_iface
$query="ALTER TABLE `glpi_device_iface`
  ADD CONSTRAINT `glpi_device_iface_ibfk_1` FOREIGN KEY (`FK_glpi_enterprise`) REFERENCES `glpi_enterprises` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_device_moboard
$query="ALTER TABLE `glpi_device_moboard`
  ADD CONSTRAINT `glpi_device_moboard_ibfk_1` FOREIGN KEY (`FK_glpi_enterprise`) REFERENCES `glpi_enterprises` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_device_pci
$query="ALTER TABLE `glpi_device_pci`
  ADD CONSTRAINT `glpi_device_pci_ibfk_1` FOREIGN KEY (`FK_glpi_enterprise`) REFERENCES `glpi_enterprises` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_device_power
$query="ALTER TABLE `glpi_device_power`
  ADD CONSTRAINT `glpi_device_power_ibfk_1` FOREIGN KEY (`FK_glpi_enterprise`) REFERENCES `glpi_enterprises` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_device_processor
$query="ALTER TABLE `glpi_device_processor`
  ADD CONSTRAINT `glpi_device_processor_ibfk_1` FOREIGN KEY (`FK_glpi_enterprise`) REFERENCES `glpi_enterprises` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_device_ram
$query="ALTER TABLE `glpi_device_ram`
  ADD CONSTRAINT `glpi_device_ram_ibfk_2` FOREIGN KEY (`type`) REFERENCES `glpi_dropdown_ram_type` (`ID`),
  ADD CONSTRAINT `glpi_device_ram_ibfk_1` FOREIGN KEY (`FK_glpi_enterprise`) REFERENCES `glpi_enterprises` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_device_sndcard
$query="ALTER TABLE `glpi_device_sndcard`
  ADD CONSTRAINT `glpi_device_sndcard_ibfk_1` FOREIGN KEY (`FK_glpi_enterprise`) REFERENCES `glpi_enterprises` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_display -> based on type
$query="ALTER TABLE `glpi_display` CHANGE `FK_users` `FK_users` INT( 11 ) NULL ";
$db->query($query) or die($query." ".$db->error());
$query="UPDATE glpi_display SET FK_users=NULL WHERE FK_users=0;";
$db->query($query) or die($query." ".$db->error());

$query="ALTER TABLE `glpi_display`
  ADD CONSTRAINT `glpi_display_ibfk_1` FOREIGN KEY (`FK_users`) REFERENCES `glpi_users_groups` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_docs
$query="ALTER TABLE `glpi_docs`
  ADD CONSTRAINT `glpi_docs_ibfk_3` FOREIGN KEY (`FK_tracking`) REFERENCES `glpi_tracking` (`ID`),
  ADD CONSTRAINT `glpi_docs_ibfk_1` FOREIGN KEY (`rubrique`) REFERENCES `glpi_dropdown_rubdocs` (`ID`),
  ADD CONSTRAINT `glpi_docs_ibfk_2` FOREIGN KEY (`FK_users`) REFERENCES `glpi_users` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_doc_device -> based on device_type
$query="ALTER TABLE `glpi_doc_device`
  ADD CONSTRAINT `glpi_doc_device_ibfk_1` FOREIGN KEY (`FK_doc`) REFERENCES `glpi_docs` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_dropdown_kbcategories
$query="ALTER TABLE `glpi_dropdown_kbcategories`
  ADD CONSTRAINT `glpi_dropdown_kbcategories_ibfk_1` FOREIGN KEY (`parentID`) REFERENCES `glpi_dropdown_kbcategories` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_dropdown_locations
$query="ALTER TABLE `glpi_dropdown_locations`
  ADD CONSTRAINT `glpi_dropdown_locations_ibfk_1` FOREIGN KEY (`parentID`) REFERENCES `glpi_dropdown_kbcategories` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_dropdown_netpoint
$query="ALTER TABLE `glpi_dropdown_netpoint`
  ADD CONSTRAINT `glpi_dropdown_netpoint_ibfk_1` FOREIGN KEY (`location`) REFERENCES `glpi_dropdown_locations` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_dropdown_enterprises
$query="ALTER TABLE `glpi_enterprises`
  ADD CONSTRAINT `glpi_enterprises_ibfk_1` FOREIGN KEY (`type`) REFERENCES `glpi_dropdown_enttype` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_event_log -> based on device_type

// glpi_followups
$query="ALTER TABLE `glpi_followups`
  ADD CONSTRAINT `glpi_followups_ibfk_2` FOREIGN KEY (`author`) REFERENCES `glpi_users` (`ID`),
  ADD CONSTRAINT `glpi_followups_ibfk_1` FOREIGN KEY (`tracking`) REFERENCES `glpi_tracking` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_history -> based on device_type

// glpi_infocoms -> based on device_type
$query="ALTER TABLE `glpi_infocoms`
  ADD CONSTRAINT `glpi_infocoms_ibfk_1` FOREIGN KEY (`FK_enterprise`) REFERENCES `glpi_enterprises` (`ID`),
  ADD CONSTRAINT `glpi_infocoms_ibfk_2` FOREIGN KEY (`budget`) REFERENCES `glpi_dropdown_budget` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_inst_software
$query="ALTER TABLE `glpi_inst_software`
  ADD CONSTRAINT `glpi_inst_software_ibfk_2` FOREIGN KEY (`license`) REFERENCES `glpi_licenses` (`ID`),
  ADD CONSTRAINT `glpi_inst_software_ibfk_1` FOREIGN KEY (`cID`) REFERENCES `glpi_computers` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_kbitems
$query="ALTER TABLE `glpi_kbitems`
  ADD CONSTRAINT `glpi_kbitems_ibfk_2` FOREIGN KEY (`author`) REFERENCES `glpi_users` (`ID`),
  ADD CONSTRAINT `glpi_kbitems_ibfk_1` FOREIGN KEY (`categoryID`) REFERENCES `glpi_dropdown_kbcategories` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_licenses
$query="ALTER TABLE `glpi_licenses`
  ADD CONSTRAINT `glpi_licenses_ibfk_2` FOREIGN KEY (`oem_computer`) REFERENCES `glpi_computers` (`ID`),
  ADD CONSTRAINT `glpi_licenses_ibfk_1` FOREIGN KEY (`sID`) REFERENCES `glpi_software` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_links_device -> based on device_type
$query="ALTER TABLE `glpi_links_device`
  ADD CONSTRAINT `glpi_links_device_ibfk_1` FOREIGN KEY (`FK_links`) REFERENCES `glpi_links` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_mailing -> based on device_type

// glpi_monitors
// Prepare DB
$query="ALTER TABLE `glpi_monitors` CHANGE `tech_num` `tech_num` INT( 11 ) NULL ,
CHANGE `FK_glpi_enterprise` `FK_glpi_enterprise` INT( 11 ) NULL ,
CHANGE `location` `location` INT( 11 ) NULL  ";
$db->query($query) or die($query." ".$db->error());
$query="UPDATE `glpi_monitors` SET 
`tech_num` = NULL ,
`FK_glpi_enterprise` = NULL,
`location` = NULL
 WHERE `ID` =1 LIMIT 1 ";
$db->query($query) or die($query." ".$db->error());

$query="ALTER TABLE `glpi_monitors`
  ADD CONSTRAINT `glpi_monitors_ibfk_13` FOREIGN KEY (`FK_groups`) REFERENCES `glpi_groups` (`ID`),
  ADD CONSTRAINT `glpi_monitors_ibfk_10` FOREIGN KEY (`model`) REFERENCES `glpi_dropdown_model_monitors` (`ID`),
  ADD CONSTRAINT `glpi_monitors_ibfk_11` FOREIGN KEY (`FK_glpi_enterprise`) REFERENCES `glpi_enterprises` (`ID`),
  ADD CONSTRAINT `glpi_monitors_ibfk_12` FOREIGN KEY (`FK_users`) REFERENCES `glpi_users` (`ID`),
  ADD CONSTRAINT `glpi_monitors_ibfk_7` FOREIGN KEY (`tech_num`) REFERENCES `glpi_users` (`ID`),
  ADD CONSTRAINT `glpi_monitors_ibfk_8` FOREIGN KEY (`location`) REFERENCES `glpi_dropdown_locations` (`ID`),
  ADD CONSTRAINT `glpi_monitors_ibfk_9` FOREIGN KEY (`type`) REFERENCES `glpi_type_monitors` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_networking
$query="ALTER TABLE `glpi_networking` CHANGE `tech_num` `tech_num` INT( 11 ) NULL ,
CHANGE `location` `location` INT( 11 ) NULL ,
CHANGE `domain` `domain` INT( 11 ) NULL ,
CHANGE `network` `network` INT( 11 ) NULL ,
CHANGE `type` `type` INT( 11 ) NULL ,
CHANGE `FK_glpi_enterprise` `FK_glpi_enterprise` INT( 11 ) NULL ";
$db->query($query) or die($query." ".$db->error());
$query="UPDATE `glpi_networking` SET 
`tech_num` = NULL ,
`FK_glpi_enterprise` = NULL,
`domain` = NULL,
`network` = NULL,
`location` = NULL
 WHERE `ID` =1 LIMIT 1 ";
$db->query($query) or die($query." ".$db->error());

$query="ALTER TABLE `glpi_networking`
  ADD CONSTRAINT `glpi_networking_ibfk_26` FOREIGN KEY (`FK_groups`) REFERENCES `glpi_groups` (`ID`),
  ADD CONSTRAINT `glpi_networking_ibfk_17` FOREIGN KEY (`tech_num`) REFERENCES `glpi_users` (`ID`),
  ADD CONSTRAINT `glpi_networking_ibfk_18` FOREIGN KEY (`location`) REFERENCES `glpi_dropdown_locations` (`ID`),
  ADD CONSTRAINT `glpi_networking_ibfk_19` FOREIGN KEY (`domain`) REFERENCES `glpi_dropdown_domain` (`ID`),
  ADD CONSTRAINT `glpi_networking_ibfk_20` FOREIGN KEY (`network`) REFERENCES `glpi_dropdown_network` (`ID`),
  ADD CONSTRAINT `glpi_networking_ibfk_21` FOREIGN KEY (`type`) REFERENCES `glpi_type_networking` (`ID`),
  ADD CONSTRAINT `glpi_networking_ibfk_22` FOREIGN KEY (`model`) REFERENCES `glpi_dropdown_model_networking` (`ID`),
  ADD CONSTRAINT `glpi_networking_ibfk_23` FOREIGN KEY (`firmware`) REFERENCES `glpi_dropdown_firmware` (`ID`),
  ADD CONSTRAINT `glpi_networking_ibfk_24` FOREIGN KEY (`FK_glpi_enterprise`) REFERENCES `glpi_enterprises` (`ID`),
  ADD CONSTRAINT `glpi_networking_ibfk_25` FOREIGN KEY (`FK_users`) REFERENCES `glpi_users` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_networking_ports -> based on device_type
$query="ALTER TABLE `glpi_networking_ports`
  ADD CONSTRAINT `glpi_networking_ports_ibfk_2` FOREIGN KEY (`netpoint`) REFERENCES `glpi_dropdown_netpoint` (`ID`),
  ADD CONSTRAINT `glpi_networking_ports_ibfk_1` FOREIGN KEY (`iface`) REFERENCES `glpi_dropdown_iface` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_networking_vlan
$query="ALTER TABLE `glpi_networking_vlan`
  ADD CONSTRAINT `glpi_networking_vlan_ibfk_2` FOREIGN KEY (`FK_vlan`) REFERENCES `glpi_dropdown_vlan` (`ID`),
  ADD CONSTRAINT `glpi_networking_vlan_ibfk_1` FOREIGN KEY (`FK_port`) REFERENCES `glpi_networking_ports` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_networking_wire
$query="ALTER TABLE `glpi_networking_wire`
  ADD CONSTRAINT `glpi_networking_wire_ibfk_2` FOREIGN KEY (`end2`) REFERENCES `glpi_networking_ports` (`ID`),
  ADD CONSTRAINT `glpi_networking_wire_ibfk_1` FOREIGN KEY (`end1`) REFERENCES `glpi_networking_ports` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_ocs_link
$query="ALTER TABLE `glpi_ocs_link`
  ADD CONSTRAINT `glpi_ocs_link_ibfk_1` FOREIGN KEY (`glpi_id`) REFERENCES `glpi_computers` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_peripherals
$query="ALTER TABLE `glpi_peripherals` CHANGE `tech_num` `tech_num` INT( 11 ) NULL ,
CHANGE `location` `location` INT( 11 ) NULL ,
CHANGE `type` `type` INT( 11 ) NULL ,
CHANGE `FK_glpi_enterprise` `FK_glpi_enterprise` INT( 11 ) NULL ";
$db->query($query) or die($query." ".$db->error());
$query="UPDATE `glpi_peripherals` SET 
`tech_num` = NULL ,
`FK_glpi_enterprise` = NULL,
`type` = NULL,
`model` = NULL,
`location` = NULL
 WHERE `ID` =1 LIMIT 1 ";
$db->query($query) or die($query." ".$db->error());

$query="ALTER TABLE `glpi_peripherals`
  ADD CONSTRAINT `glpi_peripherals_ibfk_21` FOREIGN KEY (`FK_groups`) REFERENCES `glpi_groups` (`ID`),
  ADD CONSTRAINT `glpi_peripherals_ibfk_15` FOREIGN KEY (`tech_num`) REFERENCES `glpi_users` (`ID`),
  ADD CONSTRAINT `glpi_peripherals_ibfk_16` FOREIGN KEY (`location`) REFERENCES `glpi_dropdown_locations` (`ID`),
  ADD CONSTRAINT `glpi_peripherals_ibfk_17` FOREIGN KEY (`type`) REFERENCES `glpi_type_peripherals` (`ID`),
  ADD CONSTRAINT `glpi_peripherals_ibfk_18` FOREIGN KEY (`model`) REFERENCES `glpi_dropdown_model_peripherals` (`ID`),
  ADD CONSTRAINT `glpi_peripherals_ibfk_19` FOREIGN KEY (`FK_glpi_enterprise`) REFERENCES `glpi_enterprises` (`ID`),
  ADD CONSTRAINT `glpi_peripherals_ibfk_20` FOREIGN KEY (`FK_users`) REFERENCES `glpi_users` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_phones
$query="ALTER TABLE `glpi_phones` CHANGE `tech_num` `tech_num` INT( 11 ) NULL ,
CHANGE `location` `location` INT( 11 ) NULL ,
CHANGE `type` `type` INT( 11 ) NULL ,
CHANGE `power` `power` INT( 11 ) NULL ,
CHANGE `FK_glpi_enterprise` `FK_glpi_enterprise` INT( 11 ) NULL ";
$db->query($query) or die($query." ".$db->error());
$query="UPDATE `glpi_phones` SET 
`tech_num` = NULL ,
`FK_glpi_enterprise` = NULL,
`type` = NULL,
`model` = NULL,
`power` = NULL,
`location` = NULL
 WHERE `ID` =1 LIMIT 1 ";
$db->query($query) or die($query." ".$db->error());

$query="ALTER TABLE `glpi_phones`
  ADD CONSTRAINT `glpi_phones_ibfk_15` FOREIGN KEY (`FK_groups`) REFERENCES `glpi_groups` (`ID`),
  ADD CONSTRAINT `glpi_phones_ibfk_10` FOREIGN KEY (`type`) REFERENCES `glpi_type_phones` (`ID`),
  ADD CONSTRAINT `glpi_phones_ibfk_11` FOREIGN KEY (`model`) REFERENCES `glpi_dropdown_model_phones` (`ID`),
  ADD CONSTRAINT `glpi_phones_ibfk_12` FOREIGN KEY (`power`) REFERENCES `glpi_dropdown_phone_power` (`ID`),
  ADD CONSTRAINT `glpi_phones_ibfk_13` FOREIGN KEY (`FK_glpi_enterprise`) REFERENCES `glpi_enterprises` (`ID`),
  ADD CONSTRAINT `glpi_phones_ibfk_14` FOREIGN KEY (`FK_users`) REFERENCES `glpi_users` (`ID`),
  ADD CONSTRAINT `glpi_phones_ibfk_8` FOREIGN KEY (`tech_num`) REFERENCES `glpi_users` (`ID`),
  ADD CONSTRAINT `glpi_phones_ibfk_9` FOREIGN KEY (`location`) REFERENCES `glpi_dropdown_locations` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_printers
$query="ALTER TABLE `glpi_printers` CHANGE `tech_num` `tech_num` INT( 11 ) NULL ,
CHANGE `location` `location` INT( 11 ) NULL ,
CHANGE `domain` `domain` INT( 11 ) NULL ,
CHANGE `network` `network` INT( 11 ) NULL ,
CHANGE `FK_glpi_enterprise` `FK_glpi_enterprise` INT( 11 ) NULL  ";
$db->query($query) or die($query." ".$db->error());
$query="UPDATE `glpi_printers` SET 
`tech_num` = NULL ,
`FK_glpi_enterprise` = NULL,
`type` = NULL,
`model` = NULL,
`domain` = NULL,
`network` = NULL,
`location` = NULL
 WHERE `ID` =1 LIMIT 1 ";
$db->query($query) or die($query." ".$db->error());

$query="ALTER TABLE `glpi_printers`
  ADD CONSTRAINT `glpi_printers_ibfk_16` FOREIGN KEY (`FK_groups`) REFERENCES `glpi_groups` (`ID`),
  ADD CONSTRAINT `glpi_printers_ibfk_10` FOREIGN KEY (`domain`) REFERENCES `glpi_dropdown_domain` (`ID`),
  ADD CONSTRAINT `glpi_printers_ibfk_11` FOREIGN KEY (`network`) REFERENCES `glpi_dropdown_network` (`ID`),
  ADD CONSTRAINT `glpi_printers_ibfk_12` FOREIGN KEY (`type`) REFERENCES `glpi_type_printers` (`ID`),
  ADD CONSTRAINT `glpi_printers_ibfk_13` FOREIGN KEY (`model`) REFERENCES `glpi_dropdown_model_printers` (`ID`),
  ADD CONSTRAINT `glpi_printers_ibfk_14` FOREIGN KEY (`FK_glpi_enterprise`) REFERENCES `glpi_enterprises` (`ID`),
  ADD CONSTRAINT `glpi_printers_ibfk_15` FOREIGN KEY (`FK_users`) REFERENCES `glpi_users` (`ID`),
  ADD CONSTRAINT `glpi_printers_ibfk_8` FOREIGN KEY (`tech_num`) REFERENCES `glpi_users` (`ID`),
  ADD CONSTRAINT `glpi_printers_ibfk_9` FOREIGN KEY (`location`) REFERENCES `glpi_dropdown_locations` (`ID`);";
$db->query($query) or die($query." ".$db->error());


// glpi_reminder
$query="ALTER TABLE `glpi_reminder`
  ADD CONSTRAINT `glpi_reminder_ibfk_1` FOREIGN KEY (`author`) REFERENCES `glpi_users` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_reservation_item -> based on device_type

// glpi_reservation_resa
$query="ALTER TABLE `glpi_reservation_resa`
  ADD CONSTRAINT `glpi_reservation_resa_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `glpi_users` (`ID`),
  ADD CONSTRAINT `glpi_reservation_resa_ibfk_1` FOREIGN KEY (`id_item`) REFERENCES `glpi_reservation_item` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_software
$query="ALTER TABLE `glpi_software` CHANGE `tech_num` `tech_num` INT( 11 ) NULL ,
CHANGE `FK_glpi_enterprise` `FK_glpi_enterprise` INT( 11 ) NULL,
CHANGE `update_software` `update_software` INT( 11 ) NULL ";
$db->query($query) or die($query." ".$db->error());
$query="UPDATE `glpi_software` SET 
`tech_num` = NULL ,
`update_software` = NULL ,
`FK_glpi_enterprise` = NULL
 WHERE `ID` =1 LIMIT 1 ";
$db->query($query) or die($query." ".$db->error());

$query="ALTER TABLE `glpi_software`
  ADD CONSTRAINT `glpi_software_ibfk_27` FOREIGN KEY (`FK_groups`) REFERENCES `glpi_groups` (`ID`),
  ADD CONSTRAINT `glpi_software_ibfk_21` FOREIGN KEY (`location`) REFERENCES `glpi_dropdown_locations` (`ID`),
  ADD CONSTRAINT `glpi_software_ibfk_22` FOREIGN KEY (`tech_num`) REFERENCES `glpi_users` (`ID`),
  ADD CONSTRAINT `glpi_software_ibfk_23` FOREIGN KEY (`platform`) REFERENCES `glpi_dropdown_os` (`ID`),
  ADD CONSTRAINT `glpi_software_ibfk_24` FOREIGN KEY (`update_software`) REFERENCES `glpi_software` (`ID`),
  ADD CONSTRAINT `glpi_software_ibfk_25` FOREIGN KEY (`FK_glpi_enterprise`) REFERENCES `glpi_enterprises` (`ID`),
  ADD CONSTRAINT `glpi_software_ibfk_26` FOREIGN KEY (`FK_users`) REFERENCES `glpi_users` (`ID`);";
$db->query($query) or die($query." ".$db->error());
 
// glpi_state_item -> based on device_type
$query="ALTER TABLE `glpi_state_item`
  ADD CONSTRAINT `glpi_state_item_ibfk_1` FOREIGN KEY (`state`) REFERENCES `glpi_dropdown_state` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_tracking -> based on device_type
$query="ALTER TABLE `glpi_tracking`
  ADD CONSTRAINT `glpi_tracking_ibfk_1` FOREIGN KEY (`author`) REFERENCES `glpi_users` (`ID`),
  ADD CONSTRAINT `glpi_tracking_ibfk_2` FOREIGN KEY (`FK_group`) REFERENCES `glpi_groups` (`ID`),
  ADD CONSTRAINT `glpi_tracking_ibfk_3` FOREIGN KEY (`assign`) REFERENCES `glpi_users` (`ID`),
  ADD CONSTRAINT `glpi_tracking_ibfk_4` FOREIGN KEY (`assign_ent`) REFERENCES `glpi_enterprises` (`ID`),
  ADD CONSTRAINT `glpi_tracking_ibfk_5` FOREIGN KEY (`category`) REFERENCES `glpi_dropdown_tracking_category` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_tracking_planning
$query="ALTER TABLE `glpi_tracking_planning`
  ADD CONSTRAINT `glpi_tracking_planning_ibfk_2` FOREIGN KEY (`id_assign`) REFERENCES `glpi_users` (`ID`),
  ADD CONSTRAINT `glpi_tracking_planning_ibfk_1` FOREIGN KEY (`id_followup`) REFERENCES `glpi_followups` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_users
$query="UPDATE `glpi_users` SET 
`location` = NULL";
$db->query($query) or die($query." ".$db->error());

$query="ALTER TABLE `glpi_users`
  ADD CONSTRAINT `glpi_users_ibfk_1` FOREIGN KEY (`location`) REFERENCES `glpi_dropdown_locations` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_users_groups
$query="ALTER TABLE `glpi_users_groups`
  ADD CONSTRAINT `glpi_users_groups_ibfk_2` FOREIGN KEY (`FK_groups`) REFERENCES `glpi_groups` (`ID`),
  ADD CONSTRAINT `glpi_users_groups_ibfk_1` FOREIGN KEY (`FK_users`) REFERENCES `glpi_users` (`ID`);";
$db->query($query) or die($query." ".$db->error());

// glpi_users_profiles
$query="ALTER TABLE `glpi_users_profiles`
  ADD CONSTRAINT `glpi_users_profiles_ibfk_2` FOREIGN KEY (`FK_profiles`) REFERENCES `glpi_profiles` (`ID`),
  ADD CONSTRAINT `glpi_users_profiles_ibfk_1` FOREIGN KEY (`FK_users`) REFERENCES `glpi_users` (`ID`);";
$db->query($query) or die($query." ".$db->error());


?>