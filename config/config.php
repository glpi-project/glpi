<?php
/*
 * @version $Id$
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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

	if (!defined('GLPI_ROOT')){
		die("Sorry. You can't access directly to this file");
		}


	// ITEMS TYPE
	define("GENERAL_TYPE",0);//
	define("COMPUTER_TYPE",1);//
	define("NETWORKING_TYPE",2);//
	define("PRINTER_TYPE",3);//
	define("MONITOR_TYPE",4);//
	define("PERIPHERAL_TYPE",5);//
	define("SOFTWARE_TYPE",6);//
	define("CONTACT_TYPE",7);//
	define("ENTERPRISE_TYPE",8);//
	define("INFOCOM_TYPE",9);//
	define("CONTRACT_TYPE",10);//
	define("CARTRIDGE_TYPE",11);//
	define("TYPEDOC_TYPE",12);
	define("DOCUMENT_TYPE",13);//
	define("KNOWBASE_TYPE",14);//
	define("USER_TYPE",15);//
	define("TRACKING_TYPE",16);//
	define("CONSUMABLE_TYPE",17);//
	define("CONSUMABLE_ITEM_TYPE",18);
	define("CARTRIDGE_ITEM_TYPE",19);
	define("LICENSE_TYPE",20);
	define("LINK_TYPE",21);
	define("STATE_TYPE",22);
	define("PHONE_TYPE",23);//
	define("DEVICE_TYPE",24);
	define("REMINDER_TYPE",25);
	define("STAT_TYPE",26);
	define("GROUP_TYPE",27);


	// DEVICE TYPE
	define("MOBOARD_DEVICE",1);
	define("PROCESSOR_DEVICE",2);
	define("RAM_DEVICE",3);
	define("HDD_DEVICE",4);
	define("NETWORK_DEVICE",5);
	define("DRIVE_DEVICE",6); 
	define("CONTROL_DEVICE",7);
	define("GFX_DEVICE",8);
	define("SND_DEVICE",9);
	define("PCI_DEVICE",10);
	define("CASE_DEVICE",11);
	define("POWER_DEVICE",12);

	// DEVICE INTERNAL ACTION
	define("HISTORY_ADD_DEVICE",1);
	define("HISTORY_UPDATE_DEVICE",2);
	define("HISTORY_DELETE_DEVICE",3);
	define("HISTORY_INSTALL_SOFTWARE",4);
	define("HISTORY_UNINSTALL_SOFTWARE",5);

	// OCSNG TYPES
	define("HARDWARE_FL",0);
	define("BIOS_FL",1);
	define("MEMORIES_FL",2);
	define("SLOTS_FL",3);
	define("REGISTRY_FL",4);
	define("CONTROLLERS_FL",5);
	define("MONITORS_FL",6);
	define("PORTS_FL",7);
	define("STORAGES_FL",8);
	define("DRIVES_FL",9);
	define("INPUTS_FL",10);
	define("MODEMS_FL",11);
	define("NETWORKS_FL",12);
	define("PRINTERS_FL",13);
	define("SOUNDS_FL",14);
	define("VIDEOS_FL",15);
	define("SOFTWARES_FL",16);

	define("MAX_OCS_CHECKSUM",131071);


	// GLPI MODE
	define("NORMAL_MODE",0);
	define("TRANSLATION_MODE",1);
	define("DEBUG_MODE",2);
	define("DEMO_MODE",3);

	// MAILING TYPE
	define("USER_MAILING_TYPE",1);
	define("PROFILE_MAILING_TYPE",2);
	define("GROUP_MAILING_TYPE",3);

	// MAILING USERS TYPE
	define("ADMIN_MAILING",1);
	define("ASSIGN_MAILING",2);
	define("AUTHOR_MAILING",3);
	define("OLD_ASSIGN_MAILING",4);
	define("TECH_MAILING",5);
	define("USER_MAILING",6);

	// EXPORT TYPE
	define("HTML_OUTPUT",0);
	define("SYLK_OUTPUT",1);
	define("PDF_OUTPUT",2);

	// HELPDESK LINK HARDWARE DEFINITION : CHECKSUM SYSTEM : BOTH=1*2^0+1*2^1=3
	define("HELPDESK_MY_HARDWARE",0);
	define("HELPDESK_ALL_HARDWARE",1);

	// ALERTS TYPE
	define("ALERT_THRESHOLD",1);
	define("ALERT_END",2);
	define("ALERT_NOTICE",3);

	// TIMES
	define("MINUTE_TIMESTAMP",60);
	define("HOUR_TIMESTAMP",3600);
	define("DAY_TIMESTAMP",86400);
	define("WEEK_TIMESTAMP",604800);
	define("MONTH_TIMESTAMP",2592000);

	// Current version of GLPI
	define("GLPI_VERSION","0.68.2");

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
			DEVICE_TYPE => "???",
			REMINDER_TYPE => "glpi_reminder",
			STAT_TYPE => "???",
			GROUP_TYPE => "glpi_groups",
			);

	$INFOFORM_PAGES=array( 
			COMPUTER_TYPE=> "front/computer.form.php",
			NETWORKING_TYPE => "front/networking.form.php",
			PRINTER_TYPE => "front/printer.form.php",
			MONITOR_TYPE => "front/monitor.form.php",
			PERIPHERAL_TYPE => "front/peripheral.form.php",
			SOFTWARE_TYPE => "front/software.form.php",
			CONTACT_TYPE => "front/contact.form.php",
			ENTERPRISE_TYPE => "front/enterprise.form.php",
			INFOCOM_TYPE => "front/infocom.form.php",
			CONTRACT_TYPE => "front/contract.form.php",
			CARTRIDGE_TYPE => "front/cartridge.form.php",
			TYPEDOC_TYPE => "front/typedoc.form.php",
			DOCUMENT_TYPE => "front/document.form.php",
			KNOWBASE_TYPE => "front/knowbase.form.php",
			USER_TYPE => "front/user.form.php",
			TRACKING_TYPE => "front/tracking.form.php",
			CONSUMABLE_TYPE => "front/consumable.form.php",
			CONSUMABLE_ITEM_TYPE => "??",
			CARTRIDGE_ITEM_TYPE => "??",
			LICENSE_TYPE => "??",
			LINK_TYPE => "front/link.form.php",
			STATE_TYPE => "??",
			PHONE_TYPE => "front/phone.form.php",
			DEVICE_TYPE => "???",
			REMINDER_TYPE => "front/reminder.form.php",
			STAT_TYPE => "???",
			GROUP_TYPE => "front/group.form.php",
			);


	define("ENABLE_CACHE",true);
	define("DEFAULT_CACHE_LIFETIME",DAY_TIMESTAMP);

	include (GLPI_ROOT."/config/based_config.php");
	


	if(!file_exists(GLPI_CONFIG_DIR . "/config_db.php")) {
		nullHeader("Mysql Error",$_SERVER['PHP_SELF']);
		echo "<div align='center'>";
		echo "<p>Error : GLPI seems to not be installed properly.</p><p> config_db.php file is missing.</p><p>Please restart the install process.</p>";
		echo "</div>";
		nullFooter("Mysql Error",$_SERVER['PHP_SELF']);
	
		die();
	}
	else
	{
	
		require_once (GLPI_CONFIG_DIR . "/config_db.php");
		include_once (GLPI_ROOT."/lib/cache_lite/Lite/Output.php");
		include_once (GLPI_ROOT."/lib/cache_lite/Lite/File.php");

		$DB = new DB;

		$cache_options = array(
			'cacheDir' => GLPI_DOC_DIR."/_cache/",
			'lifeTime' => DEFAULT_CACHE_LIFETIME,
			'pearErrorMode' => CACHE_LITE_ERROR_DIE,
			'automaticSerialization' => true,
			'caching' => ENABLE_CACHE,
			'hashedDirectoryLevel' => 2,
			//  'fileLocking' => false,
			//  'writeControl' => false,
			//'readControl' => false,
		);
		$CACHE_CFG = new Cache_Lite($cache_options);


		if ($data = $CACHE_CFG->get("CFG_GLPI_1","GLPI_CFG")) {
			$CFG_GLPI=$data;
		} else {


			// *************************** Statics config options **********************
			// ********************options d'installation statiques*********************
			// ***********************************************************************
		
			// dicts
			// dictionnaires
			// Name - lang file - central help file - helpdesk help file - calendar dico - toolbar dico

			$GLPI_CACHE = new Cache_Lite_Output($cache_options);
			$CFG_GLPI["cache"]=$GLPI_CACHE;
			$CFG_GLPI["languages"]=array(   
					"pt_BR"=>array("Brazilian","pt_BR.php","pt_PT.html","hd-en_GB.html","br","en"),
					"bg_BG"=>array("Bulgarian","bg_BG.php","en_GB.html","hd-en_GB.html","en","en"),
					"de_DE"=>array("Deutch","de_DE.php","en_GB.html","hd-en_GB.html","de","en"),
					"nl_NL"=>array("Dutch","nl_NL.php","en_GB.html","hd-en_GB.html","nl","en"),
					"en_GB"=>array("English","en_GB.php","en_GB.html","en_GB.html","en","en"),
					"es_AR"=>array("Español (Argentina)","es_AR.php","en_GB.html","hd-en_GB.html","es","en"),
					"es_ES"=>array("Español (España)","es_ES.php","en_GB.html","hd-en_GB.html","es","en"),
					"fr_FR"=>array("Français","fr_FR.php","fr_FR.html","hd-fr_FR.html","fr","fr"),
					"hu_HU"=>array("Hungarian","hu_HU.php","en_GB.html","hd-en_GB.html","hu","en"),
					"it_IT"=>array("Italiano","it_IT.php","en_GB.html","hd-it_IT.html","it","en"),
					"po_PO"=>array("Polish","po_PO.php","en_GB.html","hd-en_GB.html","pl","en"),
					"pt_PT"=>array("Português","pt_PT.php","pt_PT.html","hd-en_GB.html","br","en"),
					"ro_RO"=>array("Romanian","ro_RO.php","en_GB.html","hd-en_GB.html","ro","en"),
					"ru_RU"=>array("Russian","ru_RU.php","en_GB.html","hd-en_GB.html","ru","en"),
					"zh_CN"=>array("Simplified Chinese","zh_CN.php","en_GB.html","hd-en_GB.html","en","en"),
					"sv_SE"=>array("Swedish","sv_SE.php","en_GB.html","hd-en_GB.html","sv","en"),
					);




			//DEVICE ARRAY.
			$CFG_GLPI["devices_tables"] =array("moboard","processor","ram","hdd","iface","drive","control","gfxcard","sndcard","pci","case","power");
			$CFG_GLPI["deleted_tables"]=array("glpi_computers","glpi_networking","glpi_printers","glpi_monitors","glpi_peripherals","glpi_software","glpi_cartridges_type","glpi_contracts","glpi_contacts","glpi_enterprises","glpi_docs","glpi_phones","glpi_consumables_type");
		
			$CFG_GLPI["template_tables"]=array("glpi_computers","glpi_networking","glpi_printers","glpi_monitors","glpi_peripherals","glpi_software","glpi_phones");
		
			$CFG_GLPI["dropdowntree_tables"]=array("glpi_dropdown_locations","glpi_dropdown_kbcategories","glpi_dropdown_tracking_category");
			$CFG_GLPI["state_type"]=array(COMPUTER_TYPE,PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,NETWORKING_TYPE,PHONE_TYPE);
			$CFG_GLPI["linkuser_type"]=array(COMPUTER_TYPE,PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,NETWORKING_TYPE,PHONE_TYPE,SOFTWARE_TYPE);



			//Options g�� dynamiquement, ne pas toucher cette partie.
			//Options from DB, do not touch this part.
			$CFG_GLPI["debug"]=$CFG_GLPI["debug_sql"]=$CFG_GLPI["debug_vars"]=$CFG_GLPI["debug_profile"]=$CFG_GLPI["debug_lang"]=0;
			$config_object=new Config();
		
			if($config_object->getFromDB(1))
			{
				$CFG_GLPI=array_merge($CFG_GLPI,$config_object->fields);

			if ( !isset($_SERVER['REQUEST_URI']) ) {
				$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
			}
			$currentdir=getcwd();
			chdir(GLPI_ROOT);
			$glpidir=str_replace(str_replace('\\', '/',getcwd()),"",str_replace('\\', '/',$currentdir));
			chdir($currentdir);
			
			$globaldir=preg_replace("/\/[0-9a-zA-Z\.\-\_]+\.php/","",$_SERVER['REQUEST_URI']);
			$globaldir=preg_replace("/\?.*/","",$globaldir);
			$CFG_GLPI["root_doc"]=str_replace($glpidir,"",$globaldir);
			$CFG_GLPI["root_doc"]=preg_replace("/\/$/","",$CFG_GLPI["root_doc"]);
	
	
			// Path for icon of document type
			$CFG_GLPI["typedoc_icon_dir"] = $CFG_GLPI["root_doc"]."/pics/icones";


			// *************************** Mode NORMAL / TRALATION /DEBUG  **********************
			// *********************************************************************************
	
			// Mode debug ou traduction
			//$CFG_GLPI["debug"]=DEBUG_MODE;
			$CFG_GLPI["debug_sql"]=($CFG_GLPI["debug"]==DEBUG_MODE?1:0); // affiche les requetes
			$CFG_GLPI["debug_vars"]=($CFG_GLPI["debug"]==DEBUG_MODE?1:0); // affiche les variables
			$CFG_GLPI["debug_profile"]=($CFG_GLPI["debug"]==DEBUG_MODE?1:0); // Profile les requetes
			$CFG_GLPI["debug_lang"]=($CFG_GLPI["debug"]==TRANSLATION_MODE?1:0); // affiche les variables de trads
	


			if(!empty($CFG_GLPI["ldap_host"])){
				$CFG_GLPI["ldap_basedn"] = utf8_decode($CFG_GLPI["ldap_basedn"]);
				$CFG_GLPI["ldap_rootdn"] = utf8_decode($CFG_GLPI["ldap_rootdn"]);
				$CFG_GLPI["ldap_pass"] = utf8_decode($CFG_GLPI["ldap_pass"]);
	
				//// AJOUTER CA DANS LA CONFIG POST INSTALL
				$CFG_GLPI['ldap_fields'] = array( "name" => $CFG_GLPI['ldap_login'], 
						"email" => $CFG_GLPI['ldap_field_email'], 
						"location" => $CFG_GLPI['ldap_field_location'], 
						"phone" => $CFG_GLPI['ldap_field_phone'], 
						"phone2" => $CFG_GLPI['ldap_field_phone2'], 
						"mobile" => $CFG_GLPI['ldap_field_mobile'], 
						"realname" => $CFG_GLPI['ldap_field_realname'],
						"firstname" => $CFG_GLPI['ldap_field_firstname']
						);
			}
		 $CACHE_CFG->save($CFG_GLPI,"CFG_GLPI_1","GLPI_CFG");
		}
		
	}

	// Mode debug activé on affiche un certains nombres d'informations
	if ($CFG_GLPI["debug"]==DEBUG_MODE){
		ini_set('display_errors','On');
		error_reporting(E_ALL);
		ini_set('error_prepend_string','<div style="position:fload-left; background-color:red; z-index:10000">PHP ERROR : ');
		ini_set('error_append_string','</div>');
	}else{
		//Pas besoin des warnings de PHP en mode normal : on va eviter de faire peur ;)
		error_reporting(0); 
	}

	if (isset($_SESSION["glpiroot"])&&$CFG_GLPI["root_doc"]!=$_SESSION["glpiroot"]) {
		glpi_header($_SESSION["glpiroot"]);
	}



	// Override cfg_features by session value
	if (isset($_SESSION['glpilist_limit'])) $CFG_GLPI["list_limit"]=$_SESSION['glpilist_limit'];

	if ((!isset($CFG_GLPI["version"])||trim($CFG_GLPI["version"])!=GLPI_VERSION)&&!isset($_GET["donotcheckversion"])){
		loadLanguage();
		nullHeader("UPDATE NEEDED",$_SERVER["PHP_SELF"]);
		echo "<div align='center'>";
		if (!isset($CFG_GLPI["version"])||trim($CFG_GLPI["version"])<GLPI_VERSION){
			echo "<form method='post' action='".$CFG_GLPI["root_doc"]."/install/update.php'>";
			echo "<table class='tab_cadre_fixe'><tr><th>";
			echo $LANG["update"][88];
			echo "</th></tr>";
			echo "<tr class='tab_bg_1'><td align='center'>";
			echo "<input type='submit' name='from_update' value='".$LANG["install"][4]."' class='submit'>";
			echo "</td></tr>";
			echo "</table></form>";
		} else if (trim($CFG_GLPI["version"])>GLPI_VERSION){
			echo "<table class='tab_cadre_fixe'><tr><th>";
			echo $LANG["update"][89];
			echo "</th></tr>";
			echo "</table>";
		}
		echo "</div>";
		nullFooter();
		exit();
	} 

}
?>
