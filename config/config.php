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
	include_once (GLPI_ROOT."/config/based_config.php");
	include (GLPI_ROOT."/config/define.php");



	if(!file_exists(GLPI_CONFIG_DIR . "/config_db.php")) {
		nullHeader("Mysql Error",$_SERVER['PHP_SELF']);
		echo "<div align='center'>";
		echo "<p>Error : GLPI seems to not be installed properly.</p><p> config_db.php file is missing.</p><p>Please restart the install process.</p>";
		echo "</div>";
		nullFooter("Mysql Error",$_SERVER['PHP_SELF']);
	
		die();
	} else {
	
		require_once (GLPI_CONFIG_DIR . "/config_db.php");
		include_once (GLPI_ROOT."/lib/cache_lite/Lite/Output.php");
		include_once (GLPI_ROOT."/lib/cache_lite/Lite/File.php");

		$DB = new DB;

		$cache_options = array(
			'cacheDir' => GLPI_DOC_DIR."/_cache/",
			'lifeTime' => DEFAULT_CACHE_LIFETIME,
			'automaticSerialization' => true,
			'caching' => ENABLE_CACHE,
			'hashedDirectoryLevel' => 2,
			'fileLocking' => CACHE_FILELOCKINGCONTROL,
			'writeControl' => CACHE_WRITECONTROL,
			'readControl' => CACHE_READCONTROL,
		);
		$CACHE_CFG = new Cache_Lite($cache_options);


		if (!($CFG_GLPI= $CACHE_CFG->get("CFG_GLPI_1","GLPI_CFG"))) {
			// *************************** Statics config options **********************
			// ********************options d'installation statiques*********************
			// ***********************************************************************
		
			// dicts
			// dictionnaires
			// Name - lang file - central help file - helpdesk help file - calendar dico - toolbar dico

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
					"pl_PL"=>array("Polish","pl_PL.php","en_GB.html","hd-en_GB.html","pl","en"),
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
		
			$CFG_GLPI["dropdowntree_tables"]=array("glpi_entities","glpi_dropdown_locations","glpi_dropdown_kbcategories","glpi_dropdown_tracking_category");
			$CFG_GLPI["state_type"]=array(COMPUTER_TYPE,PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,NETWORKING_TYPE,PHONE_TYPE);
			$CFG_GLPI["linkuser_type"]=array(COMPUTER_TYPE,PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,NETWORKING_TYPE,PHONE_TYPE,SOFTWARE_TYPE);
			$CFG_GLPI["dropdownentity_tables"]=array("glpi_dropdown_locations","glpi_dropdown_netpoint");
			$CFG_GLPI["entity_restrict_type"]=array(KNOWBASE_TYPE);

			$CFG_GLPI["specif_entities_tables"]=array("glpi_cartridges_type","glpi_computers","glpi_consumables_type","glpi_contacts","glpi_contracts","glpi_docs",
				"glpi_dropdown_locations","glpi_dropdown_netpoint","glpi_enterprises","glpi_groups",
				"glpi_kbitems","glpi_monitors","glpi_networking","glpi_peripherals","glpi_phones","glpi_printers","glpi_software",
				"glpi_tracking");

			//Options g�� dynamiquement, ne pas toucher cette partie.
			//Options from DB, do not touch this part.
			$CFG_GLPI["debug"]=$CFG_GLPI["debug_sql"]=$CFG_GLPI["debug_vars"]=$CFG_GLPI["debug_profile"]=$CFG_GLPI["debug_lang"]=0;
			$config_object=new Config();
		
			if($config_object->getFromDB(1)){
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
					$CFG_GLPI["ldap_basedn"] = utf8_decode(strtolower($CFG_GLPI["ldap_basedn"]));
					$CFG_GLPI["ldap_rootdn"] = utf8_decode(strtolower($CFG_GLPI["ldap_rootdn"]));
					$CFG_GLPI["ldap_pass"] = utf8_decode(strtolower($CFG_GLPI["ldap_pass"]));
		
					//// AJOUTER CA DANS LA CONFIG POST INSTALL
					$CFG_GLPI['ldap_fields'] = array( "name" => strtolower($CFG_GLPI['ldap_login']), 
							"email" => strtolower($CFG_GLPI['ldap_field_email']), 
							"location" => strtolower($CFG_GLPI['ldap_field_location']), 
							"phone" => strtolower($CFG_GLPI['ldap_field_phone']), 
							"phone2" => strtolower($CFG_GLPI['ldap_field_phone2']), 
							"mobile" => strtolower($CFG_GLPI['ldap_field_mobile']), 
							"realname" => strtolower($CFG_GLPI['ldap_field_realname']),
							"firstname" => strtolower($CFG_GLPI['ldap_field_firstname'])
							);
					$CFG_GLPI["ldap_field_group_member"]=strtolower($CFG_GLPI["ldap_field_group_member"]);
				}
			$CACHE_CFG->save($CFG_GLPI,"CFG_GLPI_1","GLPI_CFG");
			}
			
		}

		$GLPI_CACHE = new Cache_Lite_Output($cache_options);
		$CFG_GLPI["cache"]=$GLPI_CACHE;
	
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
			nullHeader("UPDATE NEEDED",$_SERVER['PHP_SELF']);
			echo "<div align='center'>";
	
	
			echo "<table class='tab_cadre_fixe'>";
			echo "<tr><th>".$LANG["install"][6]."</th><th >".$LANG["install"][7]."</th></tr>";
	
			$error=checkWriteAccessToDirs();
	
			echo "</table><br>";
	
			if (!$error){
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
			} else {
				echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">";
				echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$LANG["install"][27]."\" />";
				echo "</form>";
			}
			echo "</div>";
			nullFooter();
			exit();
		} 
	}


?>
