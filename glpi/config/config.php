<?php
/*
 * @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

 
include ("_relpos.php");
include ($phproot . '/glpi/config/based_config.php');

// Current version of GLPI
define("GLPI_VERSION","0.65");


if(!file_exists($cfg_glpi["config_dir"] . "/config_db.php")) {
	nullHeader("Mysql Error",$_SERVER['PHP_SELF']);
	echo "<div align='center'>";
	echo "<p>Error : GLPI seems to not be installed properly.</p><p> config_db.php file is missing.</p><p>Please restart the install process.</p>";
	echo "</div>";
	nullFooter("Mysql Error",$_SERVER['PHP_SELF']);

	die();
}
else
{

require_once ($cfg_glpi["config_dir"] . "/config_db.php");


// *************************** Statics config options **********************
// ********************options d'installation statiques*********************
// ***********************************************************************

// dicts
// dictionnaires


$cfg_glpi["languages"]=array("deutsch"=>array("Deutsch","deutsch.php","english.html","hd-english.html"),
				"dutch"=>array("Dutch","dutch.php","english.html","hd-english.html"),
				"english"=>array("English","english.php","english.html","hd-english.html"),
				"castellano"=>array("Español (castellano)","castellano.php","castellano.html","hd-castellano.html"),
				"french"=>array("Français","french.php","french.html","hd-french.html"),
				"italian"=>array("Italiano","italian.php","english.html","hd-italian.html"),
				"polish"=>array("Polish","polish.php","english.html","hd-english.html"),
				"portugese"=>array("Português (brasileiro)","portugese.php","english.html","hd-english.html"),
				"rumaninian"=>array("Rumanian","rumanian.php","english.html","hd-english.html"),
				"hungarian"=>array("Hungarian","hungarian.php","english.html","hd-english.html"),
				);

// ITEMS TYPE
define("GENERAL_TYPE","0");
define("COMPUTER_TYPE","1");
define("NETWORKING_TYPE","2");
define("PRINTER_TYPE","3");
define("MONITOR_TYPE","4");
define("PERIPHERAL_TYPE","5");
define("SOFTWARE_TYPE","6");
define("CONTACT_TYPE","7");
define("ENTERPRISE_TYPE","8");
define("INFOCOM_TYPE","9");
define("CONTRACT_TYPE","10");
define("CARTRIDGE_TYPE","11");
define("TYPEDOC_TYPE","12");
define("DOCUMENT_TYPE","13");
define("KNOWBASE_TYPE","14");
define("USER_TYPE","15");
define("TRACKING_TYPE","16");
define("CONSUMABLE_TYPE","17");
define("CONSUMABLE_ITEM_TYPE","18");
define("CARTRIDGE_ITEM_TYPE","19");
define("LICENSE_TYPE","20");
define("LINK_TYPE","21");
define("STATE_TYPE","22");
define("PHONE_TYPE","23");

// DEVICE TYPE
define("MOBOARD_DEVICE","1");
define("PROCESSOR_DEVICE","2");
define("RAM_DEVICE","3");
define("HDD_DEVICE","4");
define("NETWORK_DEVICE","5");
define("DRIVE_DEVICE","6"); 
define("CONTROL_DEVICE","7");
define("GFX_DEVICE","8");
define("SND_DEVICE","9");
define("PCI_DEVICE","10");
define("CASE_DEVICE","11");
define("POWER_DEVICE","12");

// DEVICE INTERNAL ACTION
define("ADD_DEVICE","1");
define("UPDATE_DEVICE","2");
define("DELETE_DEVICE","3");

// OCSNG TYPES
define("HARDWARE_FL","0");
define("BIOS_FL","1");
define("MEMORIES_FL","2");
define("SLOTS_FL","3");
define("REGISTRY_FL","4");
define("CONTROLLERS_FL","5");
define("MONITORS_FL","6");
define("PORTS_FL","7");
define("STORAGES_FL","8");
define("DRIVES_FL","9");
define("INPUTS_FL","10");
define("MODEMS_FL","11");
define("NETWORKS_FL","12");
define("PRINTERS_FL","13");
define("SOUNDS_FL","14");
define("VIDEOS_FL","15");
define("SOFTWARES_FL","16");

define("MAX_OCS_CHECKSUM","131071");


//DEVICE ARRAY.
$cfg_glpi["devices_tables"] =array("moboard","processor","ram","hdd","iface","drive","control","gfxcard","sndcard","pci","case","power");
$cfg_glpi["deleted_tables"]=array("glpi_computers","glpi_networking","glpi_printers","glpi_monitors","glpi_peripherals","glpi_software","glpi_cartridges_type","glpi_contracts","glpi_contacts","glpi_enterprises","glpi_docs","glpi_phones","glpi_consumables_type");

$cfg_glpi["template_tables"]=array("glpi_computers","glpi_networking","glpi_printers","glpi_monitors","glpi_peripherals","glpi_software","glpi_phones");

$cfg_glpi["dropdowntree_tables"]=array("glpi_dropdown_locations","glpi_dropdown_kbcategories");

$LINK_ID_TABLE=array(
		COMPUTER_TYPE=> "glpi_computers",
		NETWORKING_TYPE => "glpi_networking",
		PRINTER_TYPE => "glpi_printers",
		MONITOR_TYPE => "glpi_monitors",
		PERIPHERAL_TYPE => "glpi_peripherals",
		SOFTWARE_TYPE => "glpi_software",
		CONTACT_TYPE => "glpi_contacts",
		ENTERPRISE_TYPE => "glpi_enterprises",
		INFOCOM_TYPE => "glpi_infocoms",
		CONTRACT_TYPE => "glpi_contracts",
		CARTRIDGE_TYPE => "glpi_cartridges_type",
		TYPEDOC_TYPE => "glpi_type_docs",
		DOCUMENT_TYPE => "glpi_docs",
		KNOWBASE_TYPE => "glpi_kbitems",
		USER_TYPE => "glpi_users",
		TRACKING_TYPE => "glpi_tracking",
		CONSUMABLE_TYPE => "glpi_consumables_type",
		CONSUMABLE_ITEM_TYPE => "glpi_consumables",
		CARTRIDGE_ITEM_TYPE => "glpi_cartridges",
		LICENSE_TYPE => "glpi_licenses",
		LINK_TYPE => "glpi_links",
		STATE_TYPE => "glpi_state_item",
		PHONE_TYPE => "glpi_phones",
);

$INFOFORM_PAGES=array( 
		COMPUTER_TYPE=> "computers/computers-info-form.php",
		NETWORKING_TYPE => "networking/networking-info-form.php",
		PRINTER_TYPE => "printers/printers-info-form.php",
		MONITOR_TYPE => "monitors/monitors-info-form.php",
		PERIPHERAL_TYPE => "peripherals/peripherals-info-form.php",
		SOFTWARE_TYPE => "software/software-info-form.php",
		CONTACT_TYPE => "contacts/contacts-info-form.php",
		ENTERPRISE_TYPE => "enterprises/enterprises-info-form.php",
		INFOCOM_TYPE => "infocoms/infocoms-info-form.php",
		CONTRACT_TYPE => "contracts/contracts-info-form.php",
		CARTRIDGE_TYPE => "cartridges/cartridges-info-form.php",
		TYPEDOC_TYPE => "typedocs/typedocs-info-form.php",
		DOCUMENT_TYPE => "documents/documents-info-form.php",
		KNOWBASE_TYPE => "knowbase/knowbase-info-form.php",
		USER_TYPE => "users/users-info-form.php",
		TRACKING_TYPE => "????",
		CONSUMABLE_TYPE => "consumables/consumables-info-form.php",
		CONSUMABLE_ITEM_TYPE => "??",
		CARTRIDGE_ITEM_TYPE => "??",
		LICENSE_TYPE => "??",
		LINK_TYPE => "links/links-info-form.php",
		STATE_TYPE => "??",
		PHONE_TYPE => "phones/phones-info-form.php",
);



// *************************** El�ents optionnels  **********************
// ***********************************************************************
// ***********************************************************************

// Navigation Functions
// Fonctions du menu
class baseFunctions {
	// Could have inventory, maintain, admin and settings, 
	//changes these values on the dicts on header menu.

	var $inventory	= true;

	var $maintain	= true;

	var $settings	= true;

	var $utils	= true;

	var $financial	= true;

}

//Options g�� dynamiquement, ne pas toucher cette partie.
//Options from DB, do not touch this part.
$cfg_glpi["debug"]=$cfg_glpi["debug_sql"]=$cfg_glpi["debug_vars"]=$cfg_glpi["debug_profile"]=$cfg_glpi["debug_lang"]=0;

$db = new DB;
$query = "select * from glpi_config";
$result = $db->query($query);
if($result)
{
$cfg_glpi=array_merge($cfg_glpi,$data=$db->fetch_assoc($result));

// Path for icon of document type
$cfg_glpi["typedoc_icon_dir"] = "pics/icones";

// *************************** Mode NORMAL / TRALATION /DEBUG  **********************
// *********************************************************************************

// Mode debug ou traduction
//$cfg_glpi["debug"]=2;
$cfg_glpi["debug_sql"]=($cfg_glpi["debug"]==2?1:0); // affiche les requetes
$cfg_glpi["debug_vars"]=($cfg_glpi["debug"]==2?1:0); // affiche les variables
$cfg_glpi["debug_profile"]=($cfg_glpi["debug"]==2?1:0); // Profile les requetes
$cfg_glpi["debug_lang"]=($cfg_glpi["debug"]==1?1:0); // affiche les variables de trads

// Mode debug activé on affiche un certains nombres d'informations
	if ($cfg_glpi["debug"]==2){
	ini_set('display_errors','On');
	error_reporting(E_ALL);
	ini_set('error_prepend_string','<div style="position:absolute; top:5px; left:5px; background-color:red; z-index:10000">PHP ERROR : ');
	ini_set('error_append_string','</div>');
}else{
//Pas besoin des warnings de PHP en mode normal : on va eviter de faire peur ;)
error_reporting(0); 
}


if(!empty($cfg_glpi["ldap_host"])){
	$cfg_glpi["ldap_basedn"] = utf8_decode($cfg_glpi["ldap_basedn"]);
	$cfg_glpi["ldap_rootdn"] = utf8_decode($cfg_glpi["ldap_rootdn"]);
	$cfg_glpi["ldap_pass"] = utf8_decode($cfg_glpi["ldap_pass"]);

	//// AJOUTER CA DANS LA CONFIG POST INSTALL
	$cfg_glpi['ldap_fields'] = array( "name" => $cfg_glpi['ldap_field_name'], 
					"email" => $cfg_glpi['ldap_field_email'], 
					"location" => $cfg_glpi['ldap_field_location'], 
					"phone" => $cfg_glpi['ldap_field_phone'], 
					"realname" => $cfg_glpi['ldap_field_realname']
					);
}

}

if ((!isset($cfg_glpi["version"])||trim($cfg_glpi["version"])!=GLPI_VERSION)&&!isset($_GET["donotcheckversion"])){
		loadLanguage();
		nullHeader("UPDATE NEEDED",$_SERVER["PHP_SELF"]);
		echo "<div align='center'>";
	if (!isset($cfg_glpi["version"])||trim($cfg_glpi["version"])<GLPI_VERSION){
		echo "<form method='post' action='".$cfg_glpi["root_doc"]."/update.php'>";
		echo "<table class='tab_cadre_fixe'><tr><th>";
		echo $lang["update"][88];
		echo "</th></tr>";
		echo "<tr class='tab_bg_1'><td align='center'>";
		echo "<input type='submit' name='from_update' value='".$lang["install"][4]."' class='submit'>";
		echo "</td></tr>";
		echo "</table></form>";
	} else if (trim($cfg_glpi["version"])>GLPI_VERSION){
		echo "<table class='tab_cadre_fixe'><tr><th>";
		echo $lang["update"][89];
		echo "</th></tr>";
		echo "</table>";
	}
		echo "</div>";
		nullFooter();
		exit();
} 

}
?>
