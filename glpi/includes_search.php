<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2005 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
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
 
$LINK_ID_TABLE=array( COMPUTER_TYPE=> "glpi_computers",
						NETWORKING_TYPE => "glpi_networking",
						PRINTER_TYPE => "glpi_printers",
						MONITOR_TYPE => "glpi_monitors",
						PERIPHERAL_TYPE => "glpi_peripherals",
						SOFTWARE_TYPE => "glpi_software",
						CONTACT_TYPE => "glpi_contract",
						ENTERPRISE_TYPE => "glpi_enterprises",
						INFOCOM_TYPE => "glpi_infocoms",
						CONTRACT_TYPE => "glpi_contract",
						CARTRIDGE_TYPE => "glpi_cartriges_type",
						TYPEDOC_TYPE => "glpi_type_docs",
						DOCUMENT_TYPE => "glpi_docs",
						KNOWBASE_TYPE => "glpi_kbitems",
						USER_TYPE => "glpi_users",
						TRACKING_TYPE => "glpi_tracking",
						CONSUMABLE_TYPE => "glpi_consumables_type",
						CONSUMABLE_ITEM_TYPE => "glpi_consumables",
						CARTRIDGE_ITEM_TYPE => "glpi_cartridges",
						LICENSE_TYPE => "glpi_licenses",
);

$INFOFORM_PAGES=array( COMPUTER_TYPE=> "computers/computers-info-form.php",
						NETWORKING_TYPE => "glpi_networking",
						PRINTER_TYPE => "glpi_printers",
						MONITOR_TYPE => "glpi_monitors",
						PERIPHERAL_TYPE => "glpi_peripherals",
						SOFTWARE_TYPE => "glpi_software",
						CONTACT_TYPE => "glpi_contract",
						ENTERPRISE_TYPE => "glpi_enterprises",
						INFOCOM_TYPE => "glpi_infocoms",
						CONTRACT_TYPE => "glpi_contract",
						CARTRIDGE_TYPE => "glpi_cartriges_type",
						TYPEDOC_TYPE => "glpi_type_docs",
						DOCUMENT_TYPE => "glpi_docs",
						KNOWBASE_TYPE => "glpi_kbitems",
						USER_TYPE => "glpi_users",
						TRACKING_TYPE => "glpi_tracking",
						CONSUMABLE_TYPE => "glpi_consumables_type",
						CONSUMABLE_ITEM_TYPE => "glpi_consumables",
						CARTRIDGE_ITEM_TYPE => "glpi_cartridges",
						LICENSE_TYPE => "glpi_licenses",
);

$SEARCH_OPTION=array(
COMPUTER_TYPE => array(1 => array("table" => "glpi_computers", 
									 "field" => "name",
									 "name" => $lang["computers"][7],
									),
						  2 => array("table" => "glpi_computers", 
									 "field" => "ID",
									 "name" => $lang["computers"][31],
									),
						  3 => array("table" => "glpi_dropdown_locations", 
									 "field" => "name",
									 "name" => $lang["computers"][10],
									),
						  4 => array("table" => "glpi_type_computers", 
									 "field" => "name",
									 "name" => $lang["computers"][8],
									),
						  5 => array("table" => "glpi_dropdown_model", 
									 "field" => "name",
									 "name" => $lang["computers"][50],
									),
						  6 => array("table" => "glpi_dropdown_os", 
									 "field" => "name",
									 "name" => $lang["computers"][9],
									),
						  7 => array("table" => "glpi_device_processor", 
									 "field" => "designation",
									 "name" => $lang["computers"][21],
									),
						  8 => array("table" => "glpi_computers", 
									 "field" => "serial",
									 "name" => $lang["computers"][17],
									),
						  9 => array("table" => "glpi_computers", 
									 "field" => "otherserial",
									 "name" => $lang["computers"][18],
									),
						  10 => array("table" => "glpi_device_processor", 
									 "field" => "designation",
									 "name" => $lang["computers"][23],
									),
						  11 => array("table" => "glpi_device_iface", 
									 "field" => "designation",
									 "name" => $lang["computers"][26],
									),
						  12 => array("table" => "glpi_device_sndcard", 
									 "field" => "designation",
									 "name" => $lang["computers"][33],
									),
						  13 => array("table" => "glpi_device_gfxcard", 
									 "field" => "designation",
									 "name" => $lang["computers"][34],
									),
						  14 => array("table" => "glpi_device_moboard", 
									 "field" => "designation",
									 "name" => $lang["computers"][35],
									),
						  15 => array("table" => "glpi_device_hdd", 
									 "field" => "designation",
									 "name" => $lang["computers"][36],
									),
						  16 => array("table" => "glpi_computers", 
									 "field" => "comments",
									 "name" => $lang["computers"][19],
									),
						  17 => array("table" => "glpi_computers", 
									 "field" => "contact",
									 "name" => $lang["computers"][16],
									),
						  18 => array("table" => "glpi_computers", 
									 "field" => "contact_num",
									 "name" => $lang["computers"][15],
									),
						  19 => array("table" => "glpi_computers", 
									 "field" => "date_mod",
									 "name" => $lang["computers"][11],
									),
						  20 => array("table" => "glpi_networking_ports", 
									 "field" => "ifaddr",
									 "name" => $lang["networking"][14],
									),
						  21 => array("table" => "glpi_networking_ports", 
									 "field" => "ifmac",
									 "name" => $lang["networking"][15],
									),
						  22 => array("table" => "glpi_dropdown_netpoint", 
									 "field" => "name",
									 "name" => $lang["networking"][51],
									),
						  23 => array("table" => "glpi_enterprises", 
									 "field" => "name",
									 "name" => $lang["common"][5],
									),
						  24 => array("table" => "glpi_users", 
									 "field" => "name",
									 "name" => $lang["common"][10],
									),
						  25 => array("table" => "glpi_infocoms", 
									 "field" => "num_immo",
									 "name" => $lang["financial"][20],
									),
						  26 => array("table" => "glpi_infocoms", 
									 "field" => "num_commande",
									 "name" => $lang["financial"][18],
									),
						  27 => array("table" => "glpi_infocoms", 
									 "field" => "bon_livraison",
									 "name" => $lang["financial"][19],
									),
						  28 => array("table" => "glpi_infocoms", 
									 "field" => "facture",
									 "name" => $lang["financial"][82],
									),
						  29 => array("table" => "glpi_contracts", 
									 "field" => "name",
									 "name" => $lang["financial"][27]." ".$lang["financial"][1],
									),
						  30 => array("table" => "glpi_contracts", 
									 "field" => "num",
									 "name" => $lang["financial"][4]." ".$lang["financial"][1],
									),
						 ),
"glpi_printers" => array(),
"glpi_networking" => array(),

);


?>
