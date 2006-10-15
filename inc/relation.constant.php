<?php
/*
 * @version $Id: search.class.php 3959 2006-10-14 15:29:32Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

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

$RELATION=array(

	"glpi_dropdown_auto_update" => array("glpi_computers"=>"auto_update"),

	"glpi_dropdown_budget" => array("glpi_infocoms"=>"budget"),
	
	"glpi_dropdown_cartridge_type" => array("glpi_cartridges_type"=>"type"),
	
	"glpi_dropdown_case_type" => array("glpi_device_case"=>"type"),
	
	"glpi_dropdown_consumable_type" =>array("glpi_consumables_type"=>"type"),
	
	
	"glpi_dropdown_contact_type"=>array("glpi_contacts"=>"type"),
	
	"glpi_dropdown_contract_type" =>array("glpi_contracts"=>"type"),
	
	"glpi_dropdown_domain" => array("glpi_computers"=>"domain",
															"glpi_printers"=>"domain",
															"glpi_networking"=>"domain"),
	
	"glpi_dropdown_enttype" =>array("glpi_enterprises"=>"type"),
	
	"glpi_dropdown_firmware" =>array("glpi_networking"=>"firmware"),
	
	"glpi_dropdown_iface" =>array("glpi_networking_ports"=>"iface"),
	
	"glpi_dropdown_interface" =>array("glpi_device_hdd "=>"interface",
															"glpi_device_drive"=>"interface",
															"glpi_device_control"=>"interface"),
	
	"glpi_dropdown_kbcategories" =>array("glpi_dropdown_kbcategories"=>"parentID"),
	
	"glpi_dropdown_locations" =>array("glpi_computers"=>"location",
															"glpi_monitors"=>"location",	
															"glpi_printers"=>"location",
															"glpi_software"=>"location",
															"glpi_networking"=>"location",
															"glpi_peripherals"=>"location",
															"glpi_dropdown_netpoint"=>"location",
															"glpi_cartridges_type"=>"location",
															"glpi_users"=>"location",
													),
	
	
	"glpi_dropdown_model" =>array("glpi_computers"=>"model"),
	
	"glpi_dropdown_model_monitors" =>array("glpi_monitors"=>"model"),
	
	
	"glpi_dropdown_model_networkings" =>array("glpi_networking"=>"model"),
	
	"glpi_dropdown_model_peripherals" =>array("glpi_peripherals"=>"model"),
	
	"glpi_dropdown_model_phones" =>array("glpi_phones"=>"model"),
	
	"glpi_dropdown_model_printers" =>array("glpi_printers"=>"model"),
	
	"glpi_dropdown_netpoint" =>array("glpi_networking_ports"=>"type"),
	
	"glpi_dropdown_network" =>array("glpi_networking"=>"type"),
	
	"glpi_dropdown_os" =>array("glpi_computers"=>"os",
												"glpi_software"=>"platform"),
	
	"glpi_dropdown_os_sp" =>array("glpi_computers"=>"os_sp"),
	
	"glpi_dropdown_os_version" =>array("glpi_computers"=>"os_version"),
	
	"glpi_dropdown_phone_power" =>array("glpi_phones"=>"power"),
	
	"glpi_dropdown_ram_type" =>array("glpi_device_ram"=>"type"),
	
	"glpi_dropdown_rubdocs" =>array("glpi_docs"=>"rubrique"),
	
	"glpi_dropdown_state" =>array("glpi_state_item"=>"state"),
	
	"glpi_dropdown_tracking_category" =>array("glpi_tracking"=>"category"),
	
	"glpi_dropdown_vlan" =>array("glpi_networking_vlan"=>"FK_vlan"),
	
	"glpi_type_computers"=>array("glpi_computers "=>"type"),
	
	"glpi_type_monitors "=>array("glpi_monitors "=>"type"),
	
	"glpi_type_docs "=>array("glpi_monitors "=>"type"),
	
	"glpi_type_networking "=>array("glpi_networking "=>"type"),
	
	"glpi_type_peripherals "=>array("glpi_peripherals"=>"type"),
	
	"glpi_type_phones "=>array("glpi_phones"=>"type"),
	
	"glpi_type_printers"=>array("glpi_printers"=>"type"),


	
);