<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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


if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

// _ before the link table name => no clean cache on this relation

$RELATION=array(

	"glpi_cartridges_type" => array("glpi_cartridges"=>"FK_glpi_cartridges_type",
				"glpi_cartridges_assoc"=>"FK_glpi_cartridges_type"),

	"glpi_computers" => array("glpi_computer_device"=>"FK_computers",
						"glpi_connect_wire"=>"end2",
						"glpi_inst_software"=>"cID",
						"glpi_licenses"=>"oem_computer",
						"glpi_ocs_link"=>"glpi_id",
						"glpi_registry"=>"computer_id"),

	"glpi_consumables_type" => array("glpi_consumables"=>"FK_glpi_consumables_type"),

	"glpi_contacts" => array("glpi_contact_enterprise"=>"FK_contact"),
	
	"glpi_contracts" => array("glpi_contract_device"=>"FK_contract",
						"glpi_contract_enterprise"=>"FK_contract"),
	
	"glpi_docs" => array("glpi_doc_device"=>"FK_doc"),

	"glpi_dropdown_auto_update" => array("glpi_computers"=>"auto_update"),

	"glpi_dropdown_budget" => array("glpi_infocoms"=>"budget"),
	
	"glpi_dropdown_cartridge_type" => array("glpi_cartridges_type"=>"type"),
	
	"glpi_dropdown_case_type" => array("glpi_device_case"=>"type"),
	
	"glpi_dropdown_consumable_type" =>array("glpi_consumables_type"=>"type"),
	
	"glpi_dropdown_contact_type"=>array("glpi_contacts"=>"type"),
	
	"glpi_dropdown_contract_type" =>array("glpi_contracts"=>"contract_type"),
	
	"glpi_dropdown_domain" => array("glpi_computers"=>"domain",
						"glpi_printers"=>"domain",
						"glpi_networking"=>"domain"),
	"glpi_dropdown_manufacturer" =>array("glpi_cartridges_type"=>"FK_glpi_enterprise",
							"glpi_computers"=>"FK_glpi_enterprise",
							"glpi_consumables_type"=>"FK_glpi_enterprise",
							"glpi_device_case"=>"FK_glpi_enterprise",
							"glpi_device_control"=>"FK_glpi_enterprise",
							"glpi_device_drive"=>"FK_glpi_enterprise",
							"glpi_device_gfxcard"=>"FK_glpi_enterprise",
							"glpi_device_hdd"=>"FK_glpi_enterprise",
							"glpi_device_iface"=>"FK_glpi_enterprise",
							"glpi_device_moboard"=>"FK_glpi_enterprise",
							"glpi_device_pci"=>"FK_glpi_enterprise",
							"glpi_device_power"=>"FK_glpi_enterprise",
							"glpi_device_processor"=>"FK_glpi_enterprise",
							"glpi_device_ram"=>"FK_glpi_enterprise",
							"glpi_device_sndcard"=>"FK_glpi_enterprise",
							"glpi_monitors"=>"FK_glpi_enterprise",
							"glpi_networking"=>"FK_glpi_enterprise",
							"glpi_peripherals"=>"FK_glpi_enterprise",
							"glpi_phones"=>"FK_glpi_enterprise",
							"glpi_printers"=>"FK_glpi_enterprise",
							"glpi_software"=>"FK_glpi_enterprise",
	),
	
	"glpi_dropdown_enttype" =>array("glpi_enterprises"=>"type"),
	
	"glpi_dropdown_firmware" =>array("glpi_networking"=>"firmware"),
	
	"glpi_dropdown_iface" =>array("glpi_networking_ports"=>"iface"),
	
	"glpi_dropdown_interface" =>array("glpi_device_hdd"=>"interface",
						"glpi_device_drive"=>"interface",
						"glpi_device_control"=>"interface"),
	
	"glpi_dropdown_kbcategories" =>array("glpi_dropdown_kbcategories"=>"parentID",
					"glpi_kbitems" =>"categoryID"),
	
	"glpi_dropdown_locations" =>array("glpi_computers"=>"location",
					"glpi_monitors"=>"location",	
					"glpi_printers"=>"location",
					"glpi_software"=>"location",
					"glpi_networking"=>"location",
					"glpi_peripherals"=>"location",
					"glpi_phones"=>"location",
					"glpi_dropdown_netpoint"=>"location",
					"glpi_cartridges_type"=>"location",
					"glpi_consumables_type"=>"location",
					"glpi_dropdown_locations"=>"parentID",
					"glpi_users"=>"location",	
				),
	
	
	"glpi_dropdown_model" =>array("glpi_computers"=>"model"),
	
	"glpi_dropdown_model_monitors" =>array("glpi_monitors"=>"model"),
	
	"glpi_dropdown_model_networking" =>array("glpi_networking"=>"model"),
	
	"glpi_dropdown_model_peripherals" =>array("glpi_peripherals"=>"model"),
	
	"glpi_dropdown_model_phones" =>array("glpi_phones"=>"model"),
	
	"glpi_dropdown_model_printers" =>array("glpi_printers"=>"model",
									"glpi_cartridges_assoc" =>"FK_glpi_dropdown_model_printers"),
	
	"glpi_dropdown_netpoint" =>array("glpi_networking_ports"=>"netpoint"),
	
	"glpi_dropdown_network" =>array("glpi_computers"=>"network",
								"glpi_printers"=>"network",
								"glpi_networking"=>"network",),
	
	"glpi_dropdown_os" =>array("glpi_computers"=>"os",
												"glpi_software"=>"platform"),
	
	"glpi_dropdown_os_sp" =>array("glpi_computers"=>"os_sp"),
	
	"glpi_dropdown_os_version" =>array("glpi_computers"=>"os_version"),
	
	"glpi_dropdown_phone_power" =>array("glpi_phones"=>"power"),
	
	"glpi_dropdown_ram_type" =>array("glpi_device_ram"=>"type"),
	
	"glpi_dropdown_rubdocs" =>array("glpi_config"=>"default_rubdoc_tracking",
					"glpi_docs"=>"rubrique"),

	"glpi_dropdown_software_category" =>array("glpi_software"=>"category"),
	
	"glpi_dropdown_state" =>array("glpi_computers"=>"state",
					"glpi_monitors"=>"state",
					"glpi_networking"=>"state",
					"glpi_peripherals"=>"state",
					"glpi_phones"=>"state",
					"glpi_printers"=>"state",
					"glpi_software"=>"state",
				),
	
	"glpi_dropdown_tracking_category" =>array("glpi_tracking"=>"category"),
	
	"glpi_dropdown_vlan" =>array("glpi_networking_vlan"=>"FK_vlan"),
	
	"glpi_enterprises" =>array("glpi_contact_enterprise"=>"FK_enterprise",
				"glpi_contract_enterprise"=>"FK_enterprise",
				"glpi_infocoms" =>"FK_enterprise",
				"glpi_tracking"=>"assign_ent",
	),
	"glpi_entities" => array("glpi_cartridges_type"=>"FK_entities", 
				"glpi_computers"=>"FK_entities",
				"glpi_consumables_type"=>"FK_entities",
				"glpi_contacts"=>"FK_entities",
				"glpi_contracts"=>"FK_entities",
				"glpi_docs"=>"FK_entities",
				"glpi_dropdown_locations"=>"FK_entities",
				"glpi_dropdown_netpoint"=>"FK_entities",
				"glpi_enterprises"=>"FK_entities",
				"glpi_entities"=>"parentID",
				"_glpi_entities_data"=>"FK_entities",
				"glpi_groups"=>"FK_entities",
				"glpi_mailgate"=>"FK_entities",
				"glpi_monitors"=>"FK_entities",
				"glpi_networking"=>"FK_entities",
				"glpi_peripherals"=>"FK_entities",
				"glpi_phones"=>"FK_entities",
				"glpi_printers"=>"FK_entities",
				"glpi_reminder"=>"FK_entities",
				"glpi_rules_descriptions"=>"FK_entities",
				"glpi_software"=>"FK_entities",
				"glpi_tracking"=>"FK_entities",
				"glpi_users_profiles"=>"FK_entities"),
	"glpi_followups" =>array("glpi_tracking_planning"=>"id_followup"),

	"glpi_groups" =>array("glpi_computers"=>"FK_groups",
						"glpi_monitors"=>"FK_groups",
						"glpi_networking"=>"FK_groups",
						"glpi_peripherals"=>"FK_groups",
						"glpi_phones"=>"FK_groups",
						"glpi_printers"=>"FK_groups",
						"glpi_software"=>"FK_groups",
						"glpi_tracking"=>array("FK_group","assign_group"),
						"glpi_users_groups"=>"FK_groups",
	),
	
	"glpi_licenses" =>array("glpi_inst_software"=>"license"),

	"glpi_links" =>array("glpi_links_device"=>"FK_links"),
	
	"glpi_networking_ports"=>array("glpi_networking_vlan"=>"FK_port",
							"glpi_networking_wire"=>array("end1","end2")
	),

	"glpi_ocs_config" => array("glpi_ocs_link"=>"ocs_server_id"),

	"glpi_printers" =>array("glpi_cartridges"=>"FK_glpi_printers"),
	
	"glpi_profiles" =>array("glpi_users_profiles"=>"FK_profiles"),

	"glpi_reservation_item" => array("glpi_reservation_resa"=>"id_item"),

	"glpi_rules_descriptions" => array("glpi_rules_actions"=>"FK_rules",
						"glpi_rules_criterias"=>"FK_rules"),

	"glpi_software" =>array("glpi_licenses"=>"sID",
						"glpi_software"=>"update_software"),

	"glpi_tracking" => array("_glpi_docs"=>"FK_tracking",
				"glpi_followups"=>"tracking"),

	"glpi_type_computers"=>array("glpi_computers"=>"type"),
	
	"glpi_type_monitors"=>array("glpi_monitors"=>"type"),
	
	"glpi_type_docs"=>array("glpi_monitors"=>"type"),
	
	"glpi_type_networking"=>array("glpi_networking"=>"type"),
	
	"glpi_type_peripherals"=>array("glpi_peripherals"=>"type"),
	
	"glpi_type_phones"=>array("glpi_phones"=>"type"),
	
	"glpi_type_printers"=>array("glpi_printers"=>"type"),

	"glpi_users"=> array("glpi_cartridges_type"=>"tech_num",
				"glpi_computers"=>array("tech_num","FK_users"),
				"glpi_consumables"=>"id_user",
				"glpi_consumables_type"=>"tech_num",
				"glpi_display"=>"FK_users",
				"glpi_docs"=>"FK_users",
				"glpi_followups"=>"author",
				"glpi_kbitems"=>"author",
				"glpi_monitors"=>array("tech_num","FK_users"),
				"glpi_networking"=>array("tech_num","FK_users"),
				"glpi_peripherals"=>array("tech_num","FK_users"),
				"glpi_phones"=>array("tech_num","FK_users"),
				"glpi_printers"=>array("tech_num","FK_users"),
				"glpi_reminder"=>"author",
				"glpi_reservation_resa"=>"id_user",
				"glpi_software"=>array("tech_num","FK_users"),
				"glpi_tracking"=>array("author","assign","recipient"),
				"glpi_tracking_planning"=>"id_assign",
				"glpi_users_groups"=>"FK_users",
				"glpi_users_profiles"=>"FK_users",

	),
	
);
