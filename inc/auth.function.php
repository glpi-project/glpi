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

function haveRight($module,$right){

	$matches=array(
			""  => array("","r","w"), // ne doit pas arriver normalement
			"r" => array("r","w"),
			"w" => array("w"),
			"1" => array("1"),
			"0" => array("0","1"), // ne doit pas arriver non plus
		      );

	if (isset($_SESSION["glpiprofile"][$module])&&in_array($_SESSION["glpiprofile"][$module],$matches[$right]))
		return true;
	else return false;
}


/**
 * Get The Type Name of the Object
 *
 * @return String: name of the object type in the current language
 */
function haveTypeRight ($type,$right){
	global $LANG;

	switch ($type){
		case GENERAL_TYPE :
			return true;;
			break;
		case COMPUTER_TYPE :
			return haveRight("computer",$right);
			break;
		case NETWORKING_TYPE :
			return haveRight("networking",$right);
			break;
		case PRINTER_TYPE :
			return haveRight("printer",$right);
			break;
		case MONITOR_TYPE : 
			return haveRight("monitor",$right);
			break;
		case PERIPHERAL_TYPE : 
			return haveRight("peripheral",$right);
			break;				
		case PHONE_TYPE : 
			return haveRight("phone",$right);
			break;				
		case SOFTWARE_TYPE : 
		case LICENSE_TYPE : 
			return haveRight("software",$right);
			break;				
		case CONTRACT_TYPE : 
			return haveRight("contract_infocom",$right);
			break;				
		case ENTERPRISE_TYPE : 
			return haveRight("contact_enterprise",$right);
			break;
		case CONTACT_TYPE : 
			return haveRight("contact_enterprise",$right);
			break;
		case KNOWBASE_TYPE : 
			return haveRight("knowbase",$right);
			break;	
		case USER_TYPE : 
			return haveRight("user",$right);
			break;	
		case TRACKING_TYPE : 
			return haveRight("show_ticket",$right);
			break;	
		case CARTRIDGE_TYPE : 
			return haveRight("cartridge",$right);
			break;
		case CONSUMABLE_TYPE : 
			return haveRight("consumable",$right);
			break;					
		case LICENSE_TYPE : 
			return haveRight("software",$right);
			break;					
		case CARTRIDGE_ITEM_TYPE : 
			return haveRight("cartridge",$right);
			break;
		case CONSUMABLE_ITEM_TYPE : 
			return haveRight("consumable",$right);
			break;					
		case DOCUMENT_TYPE : 
			return haveRight("document",$right);
			break;					
		case GROUP_TYPE : 
			return haveRight("group",$right);
			break;					
	}
	return false;
}


function displayRightError(){
	global $LANG,$CFG_GLPI,$HEADER_LOADED;
	if (!$HEADER_LOADED){
			if (!isset($_SESSION["glpiprofile"]["interface"]))
				nullHeader($LANG["login"][5],$_SERVER["PHP_SELF"]);
			else if ($_SESSION["glpiprofile"]["interface"]=="central")
				commonHeader($LANG["login"][5],$_SERVER["PHP_SELF"]);
			else if ($_SESSION["glpiprofile"]["interface"]=="helpdesk")
				helpHeader($LANG["login"][5],$_SERVER["PHP_SELF"]);
		}
		echo "<div align='center'><br><br><img src=\"".$CFG_GLPI["root_doc"]."/pics/warning.png\" alt=\"warning\"><br><br>";
		echo "<b>".$LANG["login"][5]."</b></div>";
		nullFooter();
		exit();
}



function checkRight($module,$right) {
	global $CFG_GLPI;

	if (!haveRight($module,$right)){
		// Gestion timeout session
		if (!isset($_SESSION["glpiID"])){
			glpi_header($CFG_GLPI["root_doc"]."/index.php");
			exit();
		}

		displayRightError();
	}
}

function checkSeveralRightsOr($modules) {
	global $CFG_GLPI;

	$valid=false;
	if (count($modules))
		foreach ($modules as $mod => $right)
			if (haveRight($mod,$right)) $valid=true;

	if (!$valid){
		// Gestion timeout session
		if (!isset($_SESSION["glpiID"])){
			glpi_header($CFG_GLPI["root_doc"]."/index.php");
			exit();
		}

		displayRightError();
	}
}

function checkSeveralRightsAnd($modules) {
	global $CFG_GLPI;

	$valid=true;
	if (count($modules))
		foreach ($modules as $mod => $right)
			if (!haveRight($mod,$right)) $valid=false;

	if (!$valid){
		// Gestion timeout session
		if (!isset($_SESSION["glpiID"])){
			glpi_header($CFG_GLPI["root_doc"]."/index.php");
			exit();
		}
		displayRightError();
	}
}

function checkTypeRight($type,$right) {
	global $CFG_GLPI;

	if (!haveTypeRight($type,$right)){
		// Gestion timeout session
		if (!isset($_SESSION["glpiID"])){
			glpi_header($CFG_GLPI["root_doc"]."/index.php");
			exit();
		}
		displayRightError();
	}
}

function checkCentralAccess(){

	global $CFG_GLPI;

	if (!isset($_SESSION["glpiprofile"])||$_SESSION["glpiprofile"]["interface"]!="central"){
		// Gestion timeout session
		if (!isset($_SESSION["glpiID"])){
			glpi_header($CFG_GLPI["root_doc"]."/index.php");
			exit();
		}
		displayRightError();
	}
}

function checkHelpdeskAccess(){

	global $CFG_GLPI;

	if (!isset($_SESSION["glpiprofile"])||$_SESSION["glpiprofile"]["interface"]!="helpdesk"){
		// Gestion timeout session
		if (!isset($_SESSION["glpiID"])){
			glpi_header($CFG_GLPI["root_doc"]."/index.php");
			exit();
		}
		displayRightError();
	}
}

function checkLoginUser(){

	global $CFG_GLPI;

	if (!isset($_SESSION["glpiname"])){
		// Gestion timeout session
		if (!isset($_SESSION["glpiID"])){
			glpi_header($CFG_GLPI["root_doc"]."/index.php");
			exit();
		}
		displayRightError();
	}
}

function checkAccessToPublicFaq(){
	global $CFG_GLPI;

	if ($CFG_GLPI["public_faq"] == 0 && !haveRight("faq","r")){
		displayRightError();
	}

}


/**
 * Include the good language dict.
 *
 * Get the default language from current user in $_SESSION["glpilanguage"].
 * And load the dict that correspond.
 *
 * @return nothing (make an include)
 *
 */
function loadLanguage() {

	global $LANG,$CFG_GLPI;
	$file="";

	if(empty($_SESSION["glpilanguage"])) {
		if (isset($CFG_GLPI["languages"][$CFG_GLPI["default_language"]][1])){
			$file= "/locales/".$CFG_GLPI["languages"][$CFG_GLPI["default_language"]][1];
		}
	} else {
		if (isset($CFG_GLPI["languages"][$_SESSION["glpilanguage"]][1])){
			$file = "/locales/".$CFG_GLPI["languages"][$_SESSION["glpilanguage"]][1];
		}
	}
	if (empty($file)||!is_file(GLPI_ROOT . $file)){
		$file="/locales/en_GB.php";
	}
	$options = array(
   	 'cacheDir' => GLPI_DOC_DIR."/_cache/",
	 'lifeTime' => DEFAULT_CACHE_LIFETIME,
	 'automaticSerialization' => true,
	 'caching' => ENABLE_CACHE,
	 'hashedDirectoryLevel' => 1,
   	 'masterFile' => GLPI_ROOT . $file
	);
	$cache = new Cache_Lite_File($options);

	// Set a id for this cache : $file
	if (!($LANG = $cache->get($file,"GLPI_LANG"))) {
		// Cache miss !
		// Put in $LANG datas to put in cache
		include (GLPI_ROOT . $file);
		$cache->save($LANG,$file,"GLPI_LANG");
	}
	

	// Debug display lang element with item
	if ($CFG_GLPI["debug"]&&$CFG_GLPI["debug_lang"]){
		foreach ($LANG as $module => $tab){
			foreach ($tab as $num => $val){
				$LANG[$module][$num].="<span style='font-size:12px; color:red;'>$module/$num</span>";
			}
		}
	}

}

?>
