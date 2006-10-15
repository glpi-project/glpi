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
	
	"glpi_dropdown_case_type" => array("glpi_cartridges_type"=>"type"),
	
	"glpi_dropdown_consumable_type" => array(CONSUMABLE_TYPE),
	
	
	"glpi_dropdown_contact_type" => array(CONTACT_TYPE),
	
	"glpi_dropdown_contract_type" => array(CONTRACT_TYPE),
	
	"glpi_dropdown_domain" => array("glpi_computers"=>"domain","glpi_printers"=>"domain","glpi_networking"=>"domain"),
	
	"glpi_dropdown_enttype" => array(ENTERPRISE_TYPE),
	
	"glpi_dropdown_firmware
	
	"glpi_dropdown_iface
	
	"glpi_dropdown_interface
	
	"glpi_dropdown_kbcategories
	
	"glpi_dropdown_locations
	
	
	"glpi_dropdown_model" => array(COMPUTER_TYPE);
	
	"glpi_dropdown_model_monitors" => array(MONITOR_TYPE);
	
	
	"glpi_dropdown_model_networkings" => array(NETWORKING_TYPE);
	
	"glpi_dropdown_model_peripherals" => array(PERIPHERAL_TYPE);
	
	"glpi_dropdown_model_phones" => array(PHONE_TYPE);
	
	"glpi_dropdown_model_printers" => array(PRINTER_TYPE);
	
	"glpi_dropdown_netpoint
	
	"glpi_dropdown_network
	
	"glpi_dropdown_os" => array(COMPUTER_TYPE);
	
	"glpi_dropdown_os_sp" => array(COMPUTER_TYPE);
	
	"glpi_dropdown_os_version" => array(COMPUTER_TYPE);
	
	"glpi_dropdown_phone_power" => array(PHONE_TYPE);
	
	"glpi_dropdown_ram_type
	
	"glpi_dropdown_rubdocs
	
	"glpi_dropdown_state
	
	"glpi_dropdown_tracking_category
	
	"glpi_dropdown_vlan" => array(NETWORKING_TYPE);
	
);