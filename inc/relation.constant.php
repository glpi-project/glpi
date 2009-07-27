<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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

	"glpi_authldaps" => array("glpi_configs"=>"extra_ldap_server",
				"glpi_authldapsreplicates"=>"server_id"),
	"glpi_bookmarks" => array("glpi_bookmarks_users"=>"FK_bookmark"),

	"glpi_cartridgesitems" => array("glpi_cartridges"=>"FK_glpi_cartridges_type",
				"glpi_cartridges_printersmodels"=>"FK_glpi_cartridges_type"),

	"glpi_computers" => array("glpi_computers_devices"=>"FK_computers",
						"glpi_computersdisks"=>"FK_computers",
						"glpi_computers_items"=>"end2",
						"glpi_computers_softwareversions"=>"cID",
						"glpi_softwarelicenses"=>"FK_computers",
						"glpi_ocslinks"=>"glpi_id",
						"glpi_registrykeys"=>"computer_id",
						),

	"glpi_consumablesitems" => array("glpi_consumables"=>"FK_glpi_consumables_type"),

	"glpi_contacts" => array("glpi_contacts_suppliers"=>"FK_contact"),
	
	"glpi_contracts" => array("glpi_contracts_items"=>"FK_contract",
						"glpi_contracts_suppliers"=>"FK_contract"),
	
	"glpi_documents" => array("glpi_documents_items"=>"FK_doc"),

	"glpi_autoupdatesystems" => array("glpi_computers"=>"auto_update"),

	"glpi_budgets" => array("glpi_infocoms"=>"budget"),
	
	"glpi_cartridgesitemstypes" => array("glpi_cartridgesitems"=>"type"),
	
	"glpi_devicescasestypes" => array("glpi_devicescases"=>"type"),
	
	"glpi_consumablesitemstypes" =>array("glpi_consumablesitems"=>"type"),
	
	"glpi_contactstypes"=>array("glpi_contacts"=>"type"),
	
	"glpi_contractstypes" =>array("glpi_contracts"=>"contract_type"),
	
	"glpi_domains" => array("glpi_computers"=>"domain",
						"glpi_printers"=>"domain",
						"glpi_networkequipments"=>"domain"),
	
	"glpi_supplierstypes" =>array("glpi_suppliers"=>"type"),

	"glpi_filesystems" =>array("glpi_computersdisks"=>"FK_filesystems"),
	
	"glpi_networkequipmentsfirmwares" =>array("glpi_networkequipments"=>"firmware"),
	
	"glpi_networkinterfaces" =>array("glpi_networkports"=>"iface"),
	
	"glpi_interfaces" =>array("glpi_devicesharddrives"=>"FK_interface",
					"glpi_devicesdrives"=>"FK_interface",
					"glpi_devicesgraphiccards"=>"FK_interface",
					"glpi_devicescontrols"=>"FK_interface"),
	
	"glpi_knowbaseitemscategories" =>array("glpi_knowbaseitemscategories"=>"parentID",
					"glpi_knowbaseitems" =>"categoryID"),

	"glpi_softwarelicensestypes" =>array("glpi_softwarelicenses"=>"type"),
	
	"glpi_locations" =>array(
					"glpi_cartridgesitems"=>"location",
					"glpi_consumablesitems"=>"location",
					"glpi_computers"=>"location",
					"glpi_netpoints"=>"location",
					"glpi_locations"=>"parentID",
					"glpi_monitors"=>"location",
					"glpi_printers"=>"location",
					"glpi_networkequipments"=>"location",
					"glpi_peripherals"=>"location",
					"glpi_phones"=>"location",
					"glpi_software"=>"location",
					"glpi_users"=>"location",
				),
	
	"glpi_manufacturers" =>array("glpi_cartridgesitems"=>"FK_glpi_enterprise",
						"glpi_computers"=>"FK_glpi_enterprise",
						"glpi_consumablesitems"=>"FK_glpi_enterprise",
						"glpi_devicescases"=>"FK_glpi_enterprise",
						"glpi_devicescontrols"=>"FK_glpi_enterprise",
						"glpi_devicesdrives"=>"FK_glpi_enterprise",
						"glpi_devicesgraphiccards"=>"FK_glpi_enterprise",
						"glpi_devicesharddrives"=>"FK_glpi_enterprise",
						"glpi_devicesnetworkcards"=>"FK_glpi_enterprise",
						"glpi_devicesmotherboards"=>"FK_glpi_enterprise",
						"glpi_devicespcis"=>"FK_glpi_enterprise",
						"glpi_devicespowersupplies"=>"FK_glpi_enterprise",
						"glpi_devicesprocessors"=>"FK_glpi_enterprise",
						"glpi_devicesmemories"=>"FK_glpi_enterprise",
						"glpi_devicessoundcards"=>"FK_glpi_enterprise",
						"glpi_monitors"=>"FK_glpi_enterprise",
						"glpi_networkequipments"=>"FK_glpi_enterprise",
						"glpi_peripherals"=>"FK_glpi_enterprise",
						"glpi_phones"=>"FK_glpi_enterprise",
						"glpi_printers"=>"FK_glpi_enterprise",
						"glpi_software"=>"FK_glpi_enterprise",
	),
	
	"glpi_computersmodels" =>array("glpi_computers"=>"model"),
	
	"glpi_monitorsmodels" =>array("glpi_monitors"=>"model"),
	
	"glpi_networkequipmentsmodels" =>array("glpi_networkequipments"=>"model"),
	
	"glpi_peripheralsmodels" =>array("glpi_peripherals"=>"model"),
	
	"glpi_phonesmodels" =>array("glpi_phones"=>"model"),
	
	"glpi_printersmodels" =>array("glpi_printers"=>"model",
									"glpi_cartridges_printersmodels" =>"FK_glpi_dropdown_model_printers"),
	
	"glpi_netpoints" =>array("glpi_networkports"=>"netpoint"),
	
	"glpi_networks" =>array("glpi_computers"=>"network",
					"glpi_printers"=>"network",
					"glpi_networkequipments"=>"network",),
	
	"glpi_operatingsystems" =>array("glpi_computers"=>"os",
				"glpi_software"=>"platform"),
	
	"glpi_operatingsystemsservicepacks" =>array("glpi_computers"=>"os_sp"),
	
	"glpi_operatingsystemsversions" =>array("glpi_computers"=>"os_version"),
	
	"glpi_phonespowersupplies" =>array("glpi_phones"=>"power"),
	
	"glpi_devicesmemoriestypes" =>array("glpi_devicesmemories"=>"type"),
	
	"glpi_documentscategories" =>array("glpi_configs"=>"default_rubdoc_tracking",
					"glpi_documents"=>"rubrique"),

	"glpi_softwarescategories" =>array("glpi_software"=>"category",
					"glpi_configs"=>"category_on_software_delete",),
	
	"glpi_states" =>array("glpi_computers"=>"state",
					"glpi_monitors"=>"state",
					"glpi_networkequipments"=>"state",
					"glpi_peripherals"=>"state",
					"glpi_phones"=>"state",
					"glpi_printers"=>"state",
					"glpi_software"=>"oldstate", /// TODO DEL
					"glpi_softwareversions"=>"state",
				),
	
	"glpi_ticketscategories" =>array("glpi_tracking"=>"category"),

	"glpi_userstitles" =>array("glpi_users"=>"title"),
	
	"glpi_userstypes" =>array("glpi_users"=>"type"),
	
	"glpi_vlans" =>array("glpi_networkports_vlans"=>"FK_vlan"),
	
	"glpi_suppliers" =>array("glpi_contacts_suppliers"=>"FK_enterprise",
				"glpi_contracts_suppliers"=>"FK_enterprise",
				"glpi_infocoms" =>"FK_enterprise",
				"glpi_tracking"=>"assign_ent",
	),
	"glpi_entities" => array("glpi_bookmarks"=>"FK_entities", 
				"glpi_cartridgesitems"=>"FK_entities", 
				"glpi_computers"=>"FK_entities",
				"glpi_consumablesitems"=>"FK_entities",
				"glpi_contacts"=>"FK_entities",
				"glpi_contracts"=>"FK_entities",
				"glpi_documents"=>"FK_entities",
				"glpi_locations"=>"FK_entities",
				"glpi_netpoints"=>"FK_entities",
				"glpi_suppliers"=>"FK_entities",
				"glpi_entities"=>"parentID",
				"_glpi_entitiesdatas"=>"FK_entities",
				"glpi_groups"=>"FK_entities",
				"glpi_knowbaseitems"=>"FK_entities",
				"glpi_links"=>"FK_entities",
				"glpi_mailcollectors"=>"FK_entities",
				"glpi_monitors"=>"FK_entities",
				"glpi_networkequipments"=>"FK_entities",
				"glpi_peripherals"=>"FK_entities",
				"glpi_phones"=>"FK_entities",
				"glpi_printers"=>"FK_entities",
				"glpi_reminders"=>"FK_entities",
				"glpi_rules_descriptions"=>"FK_entities",
				"glpi_software"=>"FK_entities",
				"glpi_softwarelicenses"=>"FK_entities",
				"glpi_tracking"=>"FK_entities",
				"glpi_users"=>"FK_entities",
				"glpi_users_profiles"=>"FK_entities"),
	"glpi_followups" =>array("glpi_tracking_planning"=>"id_followup"),

	"glpi_groups" =>array("glpi_computers"=>"FK_groups",
						"glpi_monitors"=>"FK_groups",
						"glpi_networkequipments"=>"FK_groups",
						"glpi_peripherals"=>"FK_groups",
						"glpi_phones"=>"FK_groups",
						"glpi_printers"=>"FK_groups",
						"glpi_software"=>"FK_groups",
						"glpi_tracking"=>array("FK_group","assign_group"),
						"glpi_users_groups"=>"FK_groups",
	),
	

	"glpi_links" =>array("glpi_links_itemtypes"=>"FK_links"),
	
	"glpi_networkports"=>array("glpi_networkports_vlans"=>"FK_port",
							"glpi_networkports_networkports"=>array("end1","end2")
	),

	"glpi_ocsservers" => array("glpi_ocslinks"=>"ocs_server_id"),

	"glpi_printers" =>array("glpi_cartridges"=>"FK_glpi_printers"),
	
	"glpi_profiles" =>array("glpi_users"=>"FK_profiles",
				"glpi_users_profiles"=>"FK_profiles",
				),

	"glpi_reservationsitems" => array("glpi_reservations"=>"id_item"),

	"glpi_rules_descriptions" => array("glpi_rules_actions"=>"FK_rules",
						"glpi_rules_criterias"=>"FK_rules",
						"glpi_rule_cache_manufacturer"=>"rule_id",
						"glpi_rule_cache_model_computer"=>"rule_id",
						"glpi_rule_cache_model_monitor"=>"rule_id",
						"glpi_rule_cache_model_printer"=>"rule_id",
						"glpi_rule_cache_model_peripheral"=>"rule_id",
						"glpi_rule_cache_model_phone"=>"rule_id",
						"glpi_rule_cache_model_networking"=>"rule_id",
						"glpi_rule_cache_type_computer"=>"rule_id",
						"glpi_rule_cache_type_monitor"=>"rule_id",
						"glpi_rule_cache_type_printer"=>"rule_id",
						"glpi_rule_cache_type_peripheral"=>"rule_id",
						"glpi_rule_cache_type_phone"=>"rule_id",
						"glpi_rule_cache_type_networking"=>"rule_id",
						"glpi_rule_cache_software"=>"rule_id",
						"glpi_rule_cache_os"=>"rule_id",
						"glpi_rule_cache_os_sp"=>"rule_id",
						"glpi_rule_cache_os_version"=>"rule_id"),
	"glpi_software" =>array("glpi_softwarelicenses"=>"sID",
				"glpi_softwareversions"=>"sID",
				"glpi_software"=>"update_software"),

	"glpi_softwareversions" =>array("glpi_computers_softwareversions"=>"vID",
					"glpi_softwarelicenses"=>array("buy_version","use_version")),

	"glpi_tracking" => array("_glpi_documents"=>"FK_tracking",
				"glpi_followups"=>"tracking"),

	"glpi_type_computers"=>array("glpi_computers"=>"type"),
	
	"glpi_type_monitors"=>array("glpi_monitors"=>"type"),
	
	"glpi_type_docs"=>array("glpi_monitors"=>"type"),
	
	"glpi_type_networking"=>array("glpi_networkequipments"=>"type"),
	
	"glpi_type_peripherals"=>array("glpi_peripherals"=>"type"),
	
	"glpi_type_phones"=>array("glpi_phones"=>"type"),
	
	"glpi_type_printers"=>array("glpi_printers"=>"type"),

	"glpi_users"=> array("glpi_bookmarks"=>"FK_users",
				"glpi_cartridgesitems"=>"tech_num",
				"glpi_computers"=>array("tech_num","FK_users"),
				"glpi_consumables"=>"id_user",
				"glpi_consumablesitems"=>"tech_num",
				"glpi_displayprefs"=>"FK_users",
				"glpi_bookmarks_users"=>"FK_users",
				"glpi_documents"=>"FK_users",
				"glpi_followups"=>"author",
				"glpi_groups"=>"FK_users",
				"glpi_knowbaseitems"=>"author",
				"glpi_monitors"=>array("tech_num","FK_users"),
				"glpi_networkequipments"=>array("tech_num","FK_users"),
				"glpi_peripherals"=>array("tech_num","FK_users"),
				"glpi_phones"=>array("tech_num","FK_users"),
				"glpi_printers"=>array("tech_num","FK_users"),
				"glpi_reminders"=>"FK_users",
				"glpi_reservations"=>"id_user",
				"glpi_software"=>array("tech_num","FK_users"),
				"glpi_tracking"=>array("author","assign","recipient"),
				"glpi_tracking_planning"=>"id_assign",
				"glpi_users_groups"=>"FK_users",
				"glpi_users_profiles"=>"FK_users",

	),

	// link from devices tables (computers, software, ...)
	"_virtual_device" => array (
		"glpi_contracts_items" 	=> array("FK_device","device_type"),
		"glpi_documents_items"		=> array("FK_device","device_type"),
		"glpi_infocoms"			=> array("FK_device","device_type"),
		),
	
);
?>
