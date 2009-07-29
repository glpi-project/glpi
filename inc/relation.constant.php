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

   "glpi_authldaps" => array("glpi_configs"=>"authldaps_id_extra",
                              "glpi_authldapsreplicates"=>"authldaps_id"),
	"glpi_bookmarks" => array("glpi_bookmarks_users"=>"bookmarks_id"),

	"glpi_cartridgesitems" => array("glpi_cartridges"=>"cartridgesitems_id",
				"glpi_cartridges_printersmodels"=>"cartridgesitems_id"),

	"glpi_computers" => array("glpi_computers_devices"=>"computers_id",
						"glpi_computersdisks"=>"computers_id",
						"glpi_computers_items"=>"computers_id",
						"glpi_computers_softwaresversions"=>"cID",
						"glpi_softwareslicenses"=>"computers_id",
						"glpi_ocslinks"=>"glpi_id",
						"glpi_registrykeys"=>"computer_id",
						),

	"glpi_consumablesitems" => array("glpi_consumables"=>"consumablesitems_id"),

	"glpi_contacts" => array("glpi_contacts_suppliers"=>"contacts_id"),
	
	"glpi_contracts" => array("glpi_contracts_items"=>"contracts_id",
						"glpi_contracts_suppliers"=>"contracts_id"),
	
	"glpi_documents" => array("glpi_documents_items"=>"documents_id"),

	"glpi_autoupdatesystems" => array("glpi_computers"=>"autoupdatesystems_id"),

	"glpi_budgets" => array("glpi_infocoms"=>"budgets_id"),
	
	"glpi_cartridgesitemstypes" => array("glpi_cartridgesitems"=>"cartridgesitemstypes_id"),
	
	"glpi_devicescasestypes" => array("glpi_devicescases"=>"devicescasestypes_id"),
	
	"glpi_consumablesitemstypes" =>array("glpi_consumablesitems"=>"consumablesitemstypes_id"),
	
	"glpi_contactstypes"=>array("glpi_contacts"=>"contactstypes_id"),
	
	"glpi_contractstypes" =>array("glpi_contracts"=>"contractstypes_id"),
	
	"glpi_domains" => array("glpi_computers"=>"domains_id",
						"glpi_printers"=>"domains_id",
						"glpi_networkequipments"=>"domains_id"),
	
	"glpi_supplierstypes" =>array("glpi_suppliers"=>"supplierstypes_id"),

	"glpi_filesystems" =>array("glpi_computersdisks"=>"filesystems_id"),
	
	"glpi_networkequipmentsfirmwares" =>array("glpi_networkequipments"=>"firmware"),
	
	"glpi_networkinterfaces" =>array("glpi_networkports"=>"iface"),
	
	"glpi_interfaces" =>array("glpi_devicesharddrives"=>"interfaces_id",
					"glpi_devicesdrives"=>"interfaces_id",
					"glpi_devicesgraphiccards"=>"interfaces_id",
					"glpi_devicescontrols"=>"interfaces_id"),
	
	"glpi_knowbaseitemscategories" =>array("glpi_knowbaseitemscategories"=>"knowbaseitemscategories_id",
					"glpi_knowbaseitems" =>"knowbaseitemscategories_id"),

	"glpi_softwareslicensestypes" =>array("glpi_softwareslicenses"=>"softwareslicensestypes_id"),
	
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
	
	"glpi_manufacturers" =>array("glpi_cartridgesitems"=>"manufacturers_id",
						"glpi_computers"=>"manufacturers_id",
						"glpi_consumablesitems"=>"manufacturers_id",
						"glpi_devicescases"=>"manufacturers_id",
						"glpi_devicescontrols"=>"manufacturers_id",
						"glpi_devicesdrives"=>"manufacturers_id",
						"glpi_devicesgraphiccards"=>"manufacturers_id",
						"glpi_devicesharddrives"=>"manufacturers_id",
						"glpi_devicesnetworkcards"=>"manufacturers_id",
						"glpi_devicesmotherboards"=>"manufacturers_id",
						"glpi_devicespcis"=>"manufacturers_id",
						"glpi_devicespowersupplies"=>"manufacturers_id",
						"glpi_devicesprocessors"=>"manufacturers_id",
						"glpi_devicesmemories"=>"manufacturers_id",
						"glpi_devicessoundcards"=>"manufacturers_id",
						"glpi_monitors"=>"manufacturers_id",
						"glpi_networkequipments"=>"manufacturers_id",
						"glpi_peripherals"=>"manufacturers_id",
						"glpi_phones"=>"manufacturers_id",
						"glpi_printers"=>"manufacturers_id",
						"glpi_softwares"=>"manufacturers_id",
	),
	
	"glpi_computersmodels" =>array("glpi_computers"=>"computersmodels_id"),
	
	"glpi_monitorsmodels" =>array("glpi_monitors"=>"monitorsmodels_id"),
	
	"glpi_networkequipmentsmodels" =>array("glpi_networkequipments"=>"networkequipmentsmodels_id"),
	
	"glpi_peripheralsmodels" =>array("glpi_peripherals"=>"peripheralsmodels_id"),
	
	"glpi_phonesmodels" =>array("glpi_phones"=>"phonesmodels_id"),
	
	"glpi_printersmodels" =>array("glpi_printers"=>"printersmodels",
									"glpi_cartridges_printersmodels" =>"printersmodels_id"),
	
	"glpi_netpoints" =>array("glpi_networkports"=>"netpoint"),
	
	"glpi_networks" =>array("glpi_computers"=>"networks_id",
					"glpi_printers"=>"networks_id",
					"glpi_networkequipments"=>"networks_id",),
	
	"glpi_operatingsystems" =>array("glpi_computers"=>"operatingsystems_id",
                              "glpi_softwares"=>"operatingsystems_id"),
	
	"glpi_operatingsystemsservicepacks" =>array("glpi_computers"=>"operatingsystemsservicepacks_id"),
	
	"glpi_operatingsystemsversions" =>array("glpi_computers"=>"operatingsystemsversions_id"),
	
	"glpi_phonespowersupplies" =>array("glpi_phones"=>"power"),
	
	"glpi_devicesmemoriestypes" =>array("glpi_devicesmemories"=>"devicesmemoriestypes_id"),
	
	"glpi_documentscategories" =>array("glpi_configs"=>"documentscategories_id_forticket",
					"glpi_documents"=>"documentscategories_id"),

	"glpi_softwarescategories" =>array("glpi_softwares"=>"softwarescategories_id",
					"glpi_configs"=>"softwarescategories_id_ondelete",),
	
	"glpi_states" =>array("glpi_computers"=>"states_id",
					"glpi_monitors"=>"states_id",
					"glpi_networkequipments"=>"states_id",
					"glpi_peripherals"=>"states_id",
					"glpi_phones"=>"states_id",
					"glpi_printers"=>"states_id",
					"glpi_softwares"=>"oldstate", /// TODO DEL
					"glpi_softwaresversions"=>"states_id",
				),
	
	"glpi_ticketscategories" =>array("glpi_tickets"=>"ticketscategories_id"),

	"glpi_userstitles" =>array("glpi_users"=>"userstitles_id"),
	
	"glpi_userscategories" =>array("glpi_users"=>"userscategories_id"),
	
	"glpi_vlans" =>array("glpi_networkports_vlans"=>"FK_vlan"),
	
	"glpi_suppliers" =>array("glpi_contacts_suppliers"=>"suppliers_id",
				"glpi_contracts_suppliers"=>"suppliers_id",
				"glpi_infocoms" =>"suppliers_id",
				"glpi_tickets"=>"suppliers_id_assign",
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
	"glpi_ticketsfollowups" =>array("glpi_ticketsplannings"=>"ticketsfollowups_id"),

	"glpi_groups" =>array("glpi_computers"=>"groups_id",
						"glpi_monitors"=>"groups_id",
						"glpi_networkequipments"=>"groups_id",
						"glpi_peripherals"=>"groups_id",
						"glpi_phones"=>"groups_id",
						"glpi_printers"=>"groups_id",
						"glpi_softwares"=>"groups_id",
						"glpi_tickets"=>array("groups_id","groups_id_assign"),
						"glpi_groups_users"=>"groups_id",
	),
	

	"glpi_links" =>array("glpi_links_itemtypes"=>"FK_links"),
	
	"glpi_networkports"=>array("glpi_networkports_vlans"=>"FK_port",
							"glpi_networkports_networkports"=>array("networkports_id_1","networkports_id_2")
	),

	"glpi_ocsservers" => array("glpi_ocslinks"=>"ocs_server_id"),

	"glpi_printers" =>array("glpi_cartridges"=>"printers_id"),
	
	"glpi_profiles" =>array("glpi_users"=>"profiles_id",
				"glpi_profiles_users"=>"profiles_id",
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

	"glpi_tickets" => array("_glpi_documents"=>"tickets_id",
				"glpi_ticketsfollowups"=>"tickets_id"),

	"glpi_computerstypes"=>array("glpi_computers"=>"computerstypes_id"),
	
	"glpi_monitorstypes"=>array("glpi_monitors"=>"monitorstypes_id"),
	
//	"glpi_documentstypes"=>array("glpi_documents"=>"type"),
	
	"glpi_networkequipmentstypes"=>array("glpi_networkequipments"=>"networkequipmentstypes_id"),
	
	"glpi_peripheralstypes"=>array("glpi_peripherals"=>"peripheralstypes_id"),
	
	"glpi_phonestypes"=>array("glpi_phones"=>"phonestypes_id"),
	
	"glpi_printerstypes"=>array("glpi_printers"=>"printerstypes_id"),

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
