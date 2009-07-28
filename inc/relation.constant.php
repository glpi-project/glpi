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
                              "glpi_authldapsreplicates"=>"authldaps_id"),
	"glpi_bookmarks" => array("glpi_bookmarks_users"=>"FK_bookmark"),

	"glpi_cartridgesitems" => array("glpi_cartridges"=>"cartridgesitems_id",
				"glpi_cartridges_printersmodels"=>"cartridgesitems_id"),

	"glpi_computers" => array("glpi_computers_devices"=>"FK_computers",
						"glpi_computersdisks"=>"FK_computers",
						"glpi_computers_items"=>"end2",
						"glpi_computers_softwaresversions"=>"cID",
						"glpi_softwareslicenses"=>"FK_computers",
						"glpi_ocslinks"=>"glpi_id",
						"glpi_registrykeys"=>"computer_id",
						),

	"glpi_consumablesitems" => array("glpi_consumables"=>"consumablesitems_id"),

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
	
	"glpi_knowbaseitemscategories" =>array("glpi_knowbaseitemscategories"=>"knowbaseitemscategories_id",
					"glpi_knowbaseitems" =>"categoryID"),

	"glpi_softwareslicensestypes" =>array("glpi_softwareslicenses"=>"type"),
	
	"glpi_locations" =>array(
					"glpi_cartridgesitems"=>"locations_id",
					"glpi_consumablesitems"=>"locations_id",
					"glpi_computers"=>"locations_id",
					"glpi_netpoints"=>"locations_id",
					"glpi_locations"=>"locations_id",
					"glpi_monitors"=>"locations_id",
					"glpi_printers"=>"locations_id",
					"glpi_networkequipments"=>"locations_id",
					"glpi_peripherals"=>"locations_id",
					"glpi_phones"=>"locations_id",
					"glpi_softwares"=>"locations_id",
					"glpi_users"=>"locations_id",
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
						"glpi_softwares"=>"FK_glpi_enterprise",
	),
	
	"glpi_computersmodels" =>array("glpi_computers"=>"model"),
	
	"glpi_monitorsmodels" =>array("glpi_monitors"=>"model"),
	
	"glpi_networkequipmentsmodels" =>array("glpi_networkequipments"=>"model"),
	
	"glpi_peripheralsmodels" =>array("glpi_peripherals"=>"model"),
	
	"glpi_phonesmodels" =>array("glpi_phones"=>"model"),
	
	"glpi_printersmodels" =>array("glpi_printers"=>"model",
									"glpi_cartridges_printersmodels" =>"printersmodels_id"),
	
	"glpi_netpoints" =>array("glpi_networkports"=>"netpoint"),
	
	"glpi_networks" =>array("glpi_computers"=>"network",
					"glpi_printers"=>"network",
					"glpi_networkequipments"=>"network",),
	
	"glpi_operatingsystems" =>array("glpi_computers"=>"os",
				"glpi_softwares"=>"platform"),
	
	"glpi_operatingsystemsservicepacks" =>array("glpi_computers"=>"os_sp"),
	
	"glpi_operatingsystemsversions" =>array("glpi_computers"=>"os_version"),
	
	"glpi_phonespowersupplies" =>array("glpi_phones"=>"power"),
	
	"glpi_devicesmemoriestypes" =>array("glpi_devicesmemories"=>"type"),
	
	"glpi_documentscategories" =>array("glpi_configs"=>"default_rubdoc_tracking",
					"glpi_documents"=>"rubrique"),

	"glpi_softwarescategories" =>array("glpi_softwares"=>"category",
					"glpi_configs"=>"category_on_software_delete",),
	
	"glpi_states" =>array("glpi_computers"=>"state",
					"glpi_monitors"=>"state",
					"glpi_networkequipments"=>"state",
					"glpi_peripherals"=>"state",
					"glpi_phones"=>"state",
					"glpi_printers"=>"state",
					"glpi_softwares"=>"oldstate", /// TODO DEL
					"glpi_softwaresversions"=>"state",
				),
	
	"glpi_ticketscategories" =>array("glpi_tickets"=>"category"),

	"glpi_userstitles" =>array("glpi_users"=>"title"),
	
	"glpi_userstypes" =>array("glpi_users"=>"type"),
	
	"glpi_vlans" =>array("glpi_networkports_vlans"=>"FK_vlan"),
	
	"glpi_suppliers" =>array("glpi_contacts_suppliers"=>"FK_enterprise",
				"glpi_contracts_suppliers"=>"FK_enterprise",
				"glpi_infocoms" =>"FK_enterprise",
				"glpi_tickets"=>"assign_ent",
	),
	"glpi_entities" => array("glpi_bookmarks"=>"entities_id", 
				"glpi_cartridgesitems"=>"entities_id", 
				"glpi_computers"=>"entities_id",
				"glpi_consumablesitems"=>"entities_id",
				"glpi_contacts"=>"entities_id",
				"glpi_contracts"=>"entities_id",
				"glpi_documents"=>"entities_id",
				"glpi_locations"=>"entities_id",
				"glpi_netpoints"=>"entities_id",
				"glpi_suppliers"=>"entities_id",
				"glpi_entities"=>"entities_id",
				"_glpi_entitiesdatas"=>"entities_id",
				"glpi_groups"=>"entities_id",
				"glpi_knowbaseitems"=>"entities_id",
				"glpi_links"=>"entities_id",
				"glpi_mailcollectors"=>"entities_id",
				"glpi_monitors"=>"entities_id",
				"glpi_networkequipments"=>"entities_id",
				"glpi_peripherals"=>"entities_id",
				"glpi_phones"=>"entities_id",
				"glpi_printers"=>"entities_id",
				"glpi_reminders"=>"entities_id",
				"glpi_rules"=>"entities_id",
				"glpi_softwares"=>"entities_id",
				"glpi_softwareslicenses"=>"entities_id",
				"glpi_tickets"=>"entities_id",
				"glpi_users"=>"entities_id",
				"glpi_profiles_users"=>"entities_id"),
	"glpi_ticketsfollowups" =>array("glpi_ticketsplannings"=>"id_followup"),

	"glpi_groups" =>array("glpi_computers"=>"FK_groups",
						"glpi_monitors"=>"FK_groups",
						"glpi_networkequipments"=>"FK_groups",
						"glpi_peripherals"=>"FK_groups",
						"glpi_phones"=>"FK_groups",
						"glpi_printers"=>"FK_groups",
						"glpi_softwares"=>"FK_groups",
						"glpi_tickets"=>array("FK_group","assign_group"),
						"glpi_groups_users"=>"FK_groups",
	),
	

	"glpi_links" =>array("glpi_links_itemtypes"=>"FK_links"),
	
	"glpi_networkports"=>array("glpi_networkports_vlans"=>"FK_port",
							"glpi_networkports_networkports"=>array("end1","end2")
	),

	"glpi_ocsservers" => array("glpi_ocslinks"=>"ocs_server_id"),

	"glpi_printers" =>array("glpi_cartridges"=>"printers_id"),
	
	"glpi_profiles" =>array("glpi_users"=>"FK_profiles",
				"glpi_profiles_users"=>"FK_profiles",
				),

	"glpi_reservationsitems" => array("glpi_reservations"=>"id_item"),

	"glpi_rules" => array("glpi_rulesactions"=>"FK_rules",
						"glpi_rulescriterias"=>"FK_rules",
						"glpi_rulescachemanufacturers"=>"rule_id",
						"glpi_rulescachecomputersmodels"=>"rule_id",
						"glpi_rulescachemonitorsmodels"=>"rule_id",
						"glpi_rulescacheprintersmodels"=>"rule_id",
						"glpi_rulescacheperipheralsmodels"=>"rule_id",
						"glpi_rulescachephonesmodels"=>"rule_id",
						"glpi_rulescachenetworkequipmentsmodels"=>"rule_id",
						"glpi_rulescachecomputerstypes"=>"rule_id",
						"glpi_rulescachemonitorstypes"=>"rule_id",
						"glpi_rulescacheprinterstypes"=>"rule_id",
						"glpi_rulescacheperipheralstypes"=>"rule_id",
						"glpi_rulescachephonestypes"=>"rule_id",
						"glpi_rulescachenetworkequipmentstypes"=>"rule_id",
						"glpi_rulescachesoftwares"=>"rule_id",
						"glpi_rulescacheoperatingsystems"=>"rule_id",
						"glpi_rulescacheoperatingsystemsservicepacks"=>"rule_id",
						"glpi_rulescacheoperatingsystemsversions"=>"rule_id"),
	"glpi_softwares" =>array("glpi_softwareslicenses"=>"sID",
				"glpi_softwaresversions"=>"sID",
				"glpi_softwares"=>"update_software"),

	"glpi_softwaresversions" =>array("glpi_computers_softwaresversions"=>"vID",
					"glpi_softwareslicenses"=>array("buy_version","use_version")),

	"glpi_tickets" => array("_glpi_documents"=>"FK_tracking",
				"glpi_ticketsfollowups"=>"tracking"),

	"glpi_computerstypes"=>array("glpi_computers"=>"type"),
	
	"glpi_monitorstypes"=>array("glpi_monitors"=>"type"),
	
	"glpi_documentstypes"=>array("glpi_monitors"=>"type"),
	
	"glpi_networkequipmentstypes"=>array("glpi_networkequipments"=>"type"),
	
	"glpi_peripheralstypes"=>array("glpi_peripherals"=>"type"),
	
	"glpi_phonestypes"=>array("glpi_phones"=>"type"),
	
	"glpi_printerstypes"=>array("glpi_printers"=>"type"),

	"glpi_users"=> array("glpi_bookmarks"=>"users_id",
				"glpi_cartridgesitems"=>"users_id_tech",
				"glpi_computers"=>array("users_id_tech","users_id"),
				"glpi_consumables"=>"users_id",
				"glpi_consumablesitems"=>"users_id_tech",
				"glpi_displayprefs"=>"users_id",
				"glpi_bookmarks_users"=>"users_id",
				"glpi_documents"=>"users_id",
				"glpi_ticketsfollowups"=>"users_id",
				"glpi_groups"=>"users_id",
				"glpi_knowbaseitems"=>"users_id",
				"glpi_monitors"=>array("users_id_tech","users_id"),
				"glpi_networkequipments"=>array("users_id_tech","users_id"),
				"glpi_peripherals"=>array("users_id_tech","users_id"),
				"glpi_phones"=>array("users_id_tech","users_id"),
				"glpi_printers"=>array("users_id_tech","users_id"),
				"glpi_reminders"=>"users_id",
				"glpi_reservations"=>"users_id",
				"glpi_softwares"=>array("users_id_tech","users_id"),
				"glpi_tickets"=>array("users_id","users_id_assign","users_id_recipient"),
				"glpi_ticketsplannings"=>"users_id",
				"glpi_groups_users"=>"users_id",
				"glpi_profiles_users"=>"users_id",

	),

	// link from devices tables (computers, software, ...)
	"_virtual_device" => array (
		"glpi_contracts_items" 	=> array("items_id","itemtype"),
		"glpi_documents_items"		=> array("items_id","itemtype"),
		"glpi_infocoms"			=> array("items_id","itemtype"),
		),
	
);
?>
