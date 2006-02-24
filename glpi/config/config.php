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

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------
// And Julien Dombre for externals identifications
// And Marco Gaiarin for ldap features

 
include ("_relpos.php");
include ($phproot . '/glpi/config/based_config.php');

if(!file_exists($cfg_install['config_dir'] . "/config_db.php")) {
	nullHeader("Mysql Error",$_SERVER['PHP_SELF']);
	echo "<div align='center'>";
	echo "<p>Error : GLPI seems to not be installed properly.</p><p> config_db.php file is missing.</p><p>Please restart the install process.</p>";
	echo "</div>";
	nullFooter("Mysql Error",$_SERVER['PHP_SELF']);

	die();
}
else
{

require_once ($cfg_install["config_dir"] . "/config_db.php");



// *************************** Statics config options **********************
// ********************options d'installation statiques*********************
// ***********************************************************************

// dicts
// dictionnaires


$cfg_install["languages"]=array("deutsch"=>array("Deutsch","deutsch.php","english.html","hd-english.html"),
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
$cfg_devices_tables =array("moboard","processor","ram","hdd","iface","drive","control","gfxcard","sndcard","pci","case","power");
//$cfg_devices_tables = array("moboard","processor","ram","hdd","iface","gfxcard","sndcard");

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

$deleted_tables=array("glpi_computers","glpi_networking","glpi_printers","glpi_monitors","glpi_peripherals","glpi_software","glpi_cartridges_type","glpi_contracts","glpi_contacts","glpi_enterprises","glpi_docs","glpi_phones");

$template_tables=array("glpi_computers","glpi_networking","glpi_printers","glpi_monitors","glpi_peripherals","glpi_software","glpi_phones");

$dropdowntree_tables=array("glpi_dropdown_locations","glpi_dropdown_kbcategories");


//Options g�� dynamiquement, ne pas toucher cette partie.
//Options from DB, do not touch this part.

$db = new DB;
$query = "select * from glpi_config";
$result = $db->query($query);
if($result)
{
$data=$db->fetch_assoc($result);

//root document
//document root
$cfg_install["root"] = $data["root_doc"];

// Path for icon of document type
$cfg_install["typedoc_icon_dir"] = "pics/icones";

// Default language
$cfg_install["default_language"] = $data["default_language"];

// *************************** Mode NORMAL / TRALATION /DEBUG  **********************
// *********************************************************************************


// Mode debug ou traduction
$cfg_debug["active"]=$data["debug"]; // 0 inactif , 1 traduction , 2 debug complet
$cfg_debug["sql"]=($cfg_debug["active"]==2); // affiche les requetes
$cfg_debug["vars"]=($cfg_debug["active"]==2); // affiche les variables
$cfg_debug["profile"]=($cfg_debug["active"]==2); // Profile les requetes
$cfg_debug["lang"]=($cfg_debug["active"]==1); // affiche les variables de trads

// Mode debug activé on affiche un certains nombres d'informations
	if ($cfg_debug["active"]==2){
	ini_set('display_errors','On');
	error_reporting(E_ALL);
	ini_set('error_prepend_string','<div style="position:absolute; top:5px; left:5px; background-color:red; z-index:10000">PHP ERROR : ');
	ini_set('error_append_string','</div>');
}else{
//Pas besoin des warnings de PHP en mode normal : on va eviter de faire peur ;)
error_reporting(0); 
}



// Gestion de source d'information alternatives pour le login
// telles que des serveurs de mail en imap pop...
// ports standards : pop 110 , imap 993
// Dans tous les cas le dernier type de login test�est la base de donn�s
// Dans le cas o le login est incorrect dans la base mais est correct
// sur la source alternative, l'utilisateur est ajout�ou son mot de passe
// est modifi�// Si plusieurs sources alternatives sont d�inies, seule la premi�e
// fournissant un login correct est utilis�
$cfg_login['imap']['auth_server'] = $data["imap_auth_server"];
$cfg_login['imap']['host'] = $data["imap_host"];

// LDAP setup.
// We can use LDAP both for authentication and for user information

$cfg_login['ldap']['host'] = $data["ldap_host"];
$cfg_login['ldap']['basedn'] = utf8_decode($data["ldap_basedn"]);

$cfg_login['ldap']['rootdn'] = utf8_decode($data["ldap_rootdn"]);
$cfg_login['ldap']['pass'] = utf8_decode($data["ldap_pass"]);
$cfg_login['ldap']['login'] = $data["ldap_login"];
$cfg_login['ldap']['port'] = $data["ldap_port"];

// Log in filter A AJOUTER DANS LA DB
$cfg_login['ldap']['condition'] = utf8_decode($data["ldap_condition"]);

// Use LDAP TLS
$cfg_login['ldap']['use_tls'] = utf8_decode($data["ldap_use_tls"]);

// some lDAP server (eg, M$ Active Directory) does not like anonymous
// bind
//$cfg_login['ldap']['rootdn'] = "cn=admin,ou=People,dc=sv,dc=lnf,dc=it";
//$cfg_login['ldap']['pass'] = "secret";
// relation between the GLPI users table field and the LDAP field

//// AJOUTER CA DANS LA CONFIG POST INSTALL
$cfg_login['ldap']['fields'] = array( "name" => $data["ldap_field_name"], 
					"email" => $data["ldap_field_email"], 
					"location" => $data["ldap_field_location"], 
					"phone" => $data["ldap_field_phone"], 
					"realname" => $data["ldap_field_realname"]);
// CAS authentification method
$cfg_login["cas"]["host"]=$data["cas_host"];
$cfg_login["cas"]["port"]=$data["cas_port"];
$cfg_login["cas"]["uri"]=$data["cas_uri"];

//other sources
//$cfg_login['other_source']...


// Utilisation des fonctions mailing ou non, mettez 1 si vous voulez utiliser les 
//notifications par mail.
//Necessite que votre fonction mail() fonctionne.
$cfg_features["mailing"]	= $data["mailing"];	
// Addresse de l'administrateur (obligatoire si mailing activ�

$cfg_mailing["admin_email"]	= $data["admin_email"];

// Signature for automatic generated E-Mails
$cfg_mailing["signature"]	= $data["mailing_signature"];

// A utiliser  uniquement si $cfg_features["mailing"] = 1;
// D�inition des envois des mails d'informations
// admin : vers le mail $cfg_features["admin_email"]
// all_admin : tous les utilisateurs en mode admin
// all_normal : toutes les utilisateurs en mode normal
// attrib : personne responsable de la tache
// user : utilisateur demandeur
// 1 pour l'envoi et 0 dans le cas contraire 

$cfg_mailing["new"]["admin"]= $data["mailing_new_admin"];
$cfg_mailing["update"]["admin"]= $data["mailing_update_admin"];
$cfg_mailing["followup"]["admin"]=$data["mailing_followup_admin"];
$cfg_mailing["finish"]["admin"]=$data["mailing_finish_admin"];

$cfg_mailing["new"]["all_admin"]=$data["mailing_new_all_admin"];
$cfg_mailing["update"]["all_admin"]=$data["mailing_update_all_admin"];
$cfg_mailing["followup"]["all_admin"]=$data["mailing_followup_all_admin"];
$cfg_mailing["finish"]["all_admin"]=$data["mailing_finish_all_admin"];


$cfg_mailing["new"]["all_normal"]=$data["mailing_new_all_normal"];
$cfg_mailing["update"]["all_normal"]=$data["mailing_update_all_normal"];
$cfg_mailing["followup"]["all_normal"]=$data["mailing_followup_all_normal"];
$cfg_mailing["finish"]["all_normal"]=$data["mailing_finish_all_normal"];

$cfg_mailing["new"]["attrib"] = $data["mailing_new_attrib"];
$cfg_mailing["update"]["attrib"] = $data["mailing_update_attrib"];
$cfg_mailing["followup"]["attrib"]=$data["mailing_followup_attrib"];
$cfg_mailing["finish"]["attrib"]=$data["mailing_finish_attrib"];
$cfg_mailing["attrib"]["attrib"] = $data["mailing_attrib_attrib"];

$cfg_mailing["new"]["user"]=$data["mailing_new_user"];
$cfg_mailing["update"]["user"]=$data["mailing_update_user"];
$cfg_mailing["followup"]["user"]=$data["mailing_followup_user"];
$cfg_mailing["finish"]["user"]=$data["mailing_finish_user"];

$cfg_mailing["resa"]["admin"]=$data["mailing_resa_admin"];
$cfg_mailing["resa"]["all_admin"]=$data["mailing_resa_all_admin"];
$cfg_mailing["resa"]["user"]=$data["mailing_resa_user"];


// Features configuration

// Log level :
// Niveau de log :

// 1 - Critical (login failures) |  (erreur de loging seulement)
// 2 - Severe - not used  | (non utilis�
// 3 - Important - (sucessfull logins)  |  importants (loging r�ssis)
// 4 - Notice (updates, adds, deletes, tracking) | classique
// 5 - Junk (i.e., setup dropdown fields, update users or templates) | log tout (ou presque)
$cfg_features["event_loglevel"]	= $data["event_loglevel"];

// Show jobs at login.
// Montrer les interventions au loging (1 = oui | 0 = non)
$cfg_features["jobs_at_login"]	= $data["jobs_at_login"];

// Show last num_of_events on login.
// Nombre des derniers evenements presents dans le tableau au loging
$cfg_features["num_of_events"]	= $data["num_of_events"];

//++ not on the config
// Send Expire Headers and set Meta-Tags for proper content expiration.
$cfg_features["sendexpire"]		= $data["sendexpire"];

// In listings, cut long text fields after cut characters.
$cfg_features["cut"]			= $data["cut"];	

// Expire events older than this days at every login
// (only admin-level login, set to 0 to disable expiration).
// Temps en jours durant lequel on log les actions ayant eu lieu
// mettez cette variable a 0 pour conserver tous les logs (prend beaucoup de place dans la bdd)
$cfg_features["expire_events"]	= $data["expire_events"];

// Threshold for long listings, activates pager.
//Nombre d'occurence (ordinateurs, imprimantes etc etc...) qui apparaitrons dans
//la liste de recherche par page.

$cfg_features["list_limit"]		= $data["list_limit"];	

//use helpdesk.html or not
//utilisation du helpdesk.html ou pas
$cfg_features["permit_helpdesk"] = $data["permit_helpdesk"];


//show alarm when number of unused cartridges if under the threshold 
$cfg_features["cartridges_alarm"] = $data["cartridges_alarm"];


// Auto Assign tracking
$cfg_features["auto_assign"] = $data["auto_assign"];

// OCS MODE
$cfg_features["ocs_mode"] = $data["ocs_mode"];

// Authorized anonymous knowledgebase consultation
$cfg_features["public_faq"] = $data["public_faq"];

// Base URL for the URL view in mail
$cfg_features["url_base"] = $data["url_base"];
// Enable the URL view in mail
$cfg_features["url_in_mail"] = $data["url_in_mail"];

// version number
// numero de version

$cfg_install["version"]		= $data["version"];

//Date fiscale
$cfg_install["date_fiscale"]		= $data["date_fiscale"];

$cfg_layout["logotxt"]		= $data["logotxt"];

// Priority colors
$cfg_layout["priority_1"] = $data["priority_1"];
$cfg_layout["priority_2"] = $data["priority_2"];
$cfg_layout["priority_3"] = $data["priority_3"];
$cfg_layout["priority_4"] = $data["priority_4"];
$cfg_layout["priority_5"] = $data["priority_5"];


// Planning being and end
$cfg_features["planning_begin"] = $data["planning_begin"];
$cfg_features["planning_end"] = $data["planning_end"];

// Wildcard for AJAX
// TODO : Add in glpi_config
$cfg_features["use_ajax"] = $data["use_ajax"];
$cfg_features["ajax_wildcard"] = $data["ajax_wildcard"];
$cfg_features["ajax_limit_count"] = $data["ajax_limit_count"];
$cfg_features["ajax_autocompletion"] = $data["ajax_autocompletion"];

// Droprown string limit size
$cfg_layout["dropdown_limit"]		= $data["dropdown_limit"];	

// Sizes
$cfg_layout["dropdown_max"] = $data["dropdown_max"];

//Login text
$cfg_layout["text_login"] = $data["text_login"];

// Auto update
$cfg_features["auto_update_check"] = $data["auto_update_check"];
$cfg_features["last_update_check"] = $data["last_update_check"];
$cfg_features["founded_new_version"] = $data["founded_new_version"];

// Auto add users from auth ext
$cfg_features["auto_add_users"] = $data["auto_add_users"];

// Post-only users can add followups ?
$cfg_features["post_only_followup"] = $data["post_only_followup"];

// Date Format
$cfg_layout["dateformat"] = $data["dateformat"];

// Affichage ID
$cfg_layout["view_ID"] = $data["view_ID"];

// Next Prev 
$cfg_layout["nextprev_item"] = $data["nextprev_item"];

}



}
?>