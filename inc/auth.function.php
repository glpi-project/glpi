<?php


/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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

if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

/**
 * Have I the right $right to module $module (conpare to session variable)
 *
 * @param $module Module to check
 * @param $right Right to check
 *
 * @return Boolean : session variable have more than the right specified for the module
**/
function haveRight($module, $right) {
	global $DB;
	
	//If GLPI is using the slave DB -> read only mode
	if ($DB->isSlave() && $right == "w")
		return false;
		
	$matches = array (
		"" => array (
			"",
			"r",
			"w"
		), // ne doit pas arriver normalement
		"r" => array (
			"r",
			"w"
		),
		"w" => array (
			"w"
		),
		"1" => array (
			"1"
		),
		"0" => array (
			"0",
			"1"
		), // ne doit pas arriver non plus
	);

	if (isset ($_SESSION["glpiactiveprofile"][$module]) && in_array($_SESSION["glpiactiveprofile"][$module], $matches[$right]))
		return true;
	else
		return false;
}

/**
 * Have I the right $right to module type $type (conpare to session variable)
 *
 * @param $right Right to check
 * @param $type Type to check
 *
 * @return Boolean : session variable have more than the right specified for the module type
**/
function haveTypeRight($type, $right) {
	global $LANG,$PLUGIN_HOOKS;

	switch ($type) {
		case GENERAL_TYPE :
			return true;
			break;
		case COMPUTER_TYPE :
			return haveRight("computer", $right);
			break;
		case NETWORKING_TYPE :
			return haveRight("networking", $right);
			break;
		case PRINTER_TYPE :
			return haveRight("printer", $right);
			break;
		case MONITOR_TYPE :
			return haveRight("monitor", $right);
			break;
		case PERIPHERAL_TYPE :
			return haveRight("peripheral", $right);
			break;
		case SOFTWARE_TYPE :
			return haveRight("software", $right);
			break;
		case CONTACT_TYPE :
			return haveRight("contact_enterprise", $right);
			break;
		case ENTERPRISE_TYPE :
			return haveRight("contact_enterprise", $right);
			break;
		case INFOCOM_TYPE :
			return haveRight("contract_infocom", $right);
			break;
		case CONTRACT_TYPE :
			return haveRight("contract_infocom", $right);
			break;
		case CARTRIDGE_TYPE :
			return haveRight("cartridge", $right);
			break;
		case TYPEDOC_TYPE :
			return haveRight("typedoc", $right);
			break;
		case DOCUMENT_TYPE :
			return haveRight("document", $right);
			break;
		case KNOWBASE_TYPE :
			return (haveRight("knowbase", $right)||haveRight("faq", $right));
			break;
		case USER_TYPE :
			return haveRight("user", $right);
			break;
		case TRACKING_TYPE :
			if ($right=='r'){
				return haveRight("show_all_ticket", 1);
			} else  if ($right=='w'){
				return haveRight("update_ticket", 1);
			} else {
				return haveRight("show_all_ticket", $right);
			}
			break;
		case CONSUMABLE_TYPE :
			return haveRight("consumable", $right);
			break;
		case CARTRIDGE_ITEM_TYPE :
			return haveRight("cartridge", $right);
			break;
		case CONSUMABLE_ITEM_TYPE :
			return haveRight("consumable", $right);
			break;
		case LICENSE_TYPE :
			return haveRight("software", $right);
			break;
		case LINK_TYPE :
			return haveRight("link", $right);
			break;
		case PHONE_TYPE :
			return haveRight("phone", $right);
			break;
		case REMINDER_TYPE :
			return haveRight("reminder_public", $right);
			break;
		case GROUP_TYPE :
			return haveRight("group", $right);
			break;
		case ENTITY_TYPE :
			return haveRight("entity", $right);
			break;
		case AUTH_MAIL_TYPE :
			return haveRight("config",$right);
			break;	
		case AUTH_LDAP_TYPE :
			return haveRight("config",$right);
			break;	
		case OCSNG_TYPE :
			return haveRight("ocsng",$right);
			break;	
		case REGISTRY_TYPE :
			return haveRight("ocsng",$right);
			break;	
		case PROFILE_TYPE :
			return haveRight("profile",$right);
			break;	
		case MAILGATE_TYPE :
			return haveRight("config",$right);
			break;	
		case RULE_TYPE :
			return haveRight("rule_tracking",$right)||haveRight("rule_ocs",$right)||haveRight("rule_ldap",$right)||haveRight("rule_softwarecategories",$right);
			break;	
		default :
			// Plugin case
			if ($type>1000){
				if (isset($PLUGIN_HOOKS['plugin_types'][$type])){
					$function='plugin_'.$PLUGIN_HOOKS['plugin_types'][$type].'_haveTypeRight';
					if (function_exists($function)){
						return $function($type,$right);
					} 
				} 
			}
			
			break;

	}
	return false;
}

/**
 * Have I the right to "write" an Object
 *
 * @param $type Type of the object
 * @param $ID ID of the object
 *
 * @return Boolean
**/
/*
function canEditItem ($type,$ID=0) {
	global $CFG_GLPI;

	$can_edit = haveTypeRight($type,"w");
	
	if (isset($CFG_GLPI["recursive_type"][$type]) && $ID) {
		$obj = new CommonItem;
		if ($obj->getFromDB($type,$ID)) {
			if ($obj->obj->fields["recursive"]) {
				$can_edit = $can_edit && haveRecursiveAccessToEntity($obj->obj->fields["FK_entities"]);
			}	
		} else {
			$can_edit = false;
		}		
	}

	return $can_edit;
}
*/

/**
 * Check if I the right to "write" an Object
 *
 * @param $type Type of the object
 * @param $ID ID of the object
 *
 * @return Nothing : display error if not permit
**/
/*
function checkEditItem ($type,$ID=0) {
	global $CFG_GLPI;

	if (!canEditItem ($type,$ID)) {
		// Gestion timeout session
		if (!isset ($_SESSION["glpiID"])) {
			glpi_header($CFG_GLPI["root_doc"] . "/index.php");
			exit ();
		}

		displayRightError();
	}
}
*/
/**
 * Display common message for privileges errors
 *
 * @return Nothing
**/
function displayRightError() {
	global $LANG, $CFG_GLPI, $HEADER_LOADED;
	if (!$HEADER_LOADED) {
		if (!isset ($_SESSION["glpiactiveprofile"]["interface"])){
			nullHeader($LANG["login"][5], $_SERVER['PHP_SELF']);
		} else {
			if ($_SESSION["glpiactiveprofile"]["interface"] == "central"){
				commonHeader($LANG["login"][5], $_SERVER['PHP_SELF']);
			} else {
				if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk"){
					helpHeader($LANG["login"][5], $_SERVER['PHP_SELF']);
				}
			}
		}
	}
	echo "<div class='center'><br><br><img src=\"" . $CFG_GLPI["root_doc"] . "/pics/warning.png\" alt=\"warning\"><br><br>";
	echo "<strong>" . $LANG["login"][5] . "</strong></div>";
	nullFooter();
	exit ();
}

/**
 * Check if I have the right $right to module $module (conpare to session variable)
 *
 * @param $module Module to check
 * @param $right Right to check
 *
 * @return Nothing : display error if not permit
**/
function checkRight($module, $right) {
	global $CFG_GLPI;

	if (!haveRight($module, $right)) {
		// Gestion timeout session
		if (!isset ($_SESSION["glpiID"])) {
			glpi_header($CFG_GLPI["root_doc"] . "/index.php");
			exit ();
		}

		displayRightError();
	}
}

/**
 * Check if I have one of the right specified
 *
 * @param $modules array of modules where keys are modules and value are right
 *
 * @return Nothing : display error if not permit
**/
function checkSeveralRightsOr($modules) {
	global $CFG_GLPI;

	$valid = false;
	if (count($modules)){
		foreach ($modules as $mod => $right){
			if (is_numeric($mod)){
				if (haveTypeRight($mod, $right)){
					$valid = true;
				}
			} else if (haveRight($mod, $right)){
				$valid = true;
			}
		}
	}

	if (!$valid) {
		// Gestion timeout session
		if (!isset ($_SESSION["glpiID"])) {
			glpi_header($CFG_GLPI["root_doc"] . "/index.php");
			exit ();
		}

		displayRightError();
	}
}

/**
 * Check if I have all the rights specified
 *
 * @param $modules array of modules where keys are modules and value are right
 *
 * @return Nothing : display error if not permit
**/
function checkSeveralRightsAnd($modules) {
	global $CFG_GLPI;

	$valid = true;
	if (count($modules)){
		foreach ($modules as $mod => $right){
			if (is_numeric($mod)){
				if (!haveTypeRight($mod, $right)){
					$valid = false;
				}
			} else if (!haveRight($mod, $right)){
				$valid = false;
			}
		}
	}

	if (!$valid) {
		// Gestion timeout session
		if (!isset ($_SESSION["glpiID"])) {
			glpi_header($CFG_GLPI["root_doc"] . "/index.php");
			exit ();
		}
		displayRightError();
	}
}
/**
 * Check if I have the right $right to module type $type (conpare to session variable)
 *
 * @param $type Module type to check
 * @param $right Right to check
 *
 * @return Nothing : display error if not permit
**/
function checkTypeRight($type, $right) {
	global $CFG_GLPI;
	if (!haveTypeRight($type, $right)) {
		// Gestion timeout session
		if (!isset ($_SESSION["glpiID"])) {
			glpi_header($CFG_GLPI["root_doc"] . "/index.php");
			exit ();
		}
		displayRightError();
	}
}
/**
 * Check if I have access to the central interface
 *
 * @return Nothing : display error if not permit
**/
function checkCentralAccess() {

	global $CFG_GLPI;

	if (!isset ($_SESSION["glpiactiveprofile"]) || $_SESSION["glpiactiveprofile"]["interface"] != "central") {
		// Gestion timeout session
		if (!isset ($_SESSION["glpiID"])) {
			glpi_header($CFG_GLPI["root_doc"] . "/index.php");
			exit ();
		}
		displayRightError();
	}
}
/**
 * Check if I have access to the helpdesk interface
 *
 * @return Nothing : display error if not permit
**/
function checkHelpdeskAccess() {

	global $CFG_GLPI;

	if (!isset ($_SESSION["glpiactiveprofile"]) || $_SESSION["glpiactiveprofile"]["interface"] != "helpdesk") {
		// Gestion timeout session
		if (!isset ($_SESSION["glpiID"])) {
			glpi_header($CFG_GLPI["root_doc"] . "/index.php");
			exit ();
		}
		displayRightError();
	}
}

/**
 * Check if I am logged in
 *
 * @return Nothing : display error if not permit
**/
function checkLoginUser() {

	global $CFG_GLPI;

	if (!isset ($_SESSION["glpiname"])) {
		// Gestion timeout session
		if (!isset ($_SESSION["glpiID"])) {
			glpi_header($CFG_GLPI["root_doc"] . "/index.php");
			exit ();
		}
		displayRightError();
	}
}

/**
 * Check if I have the right to access to the FAQ (profile or anonymous FAQ)
 *
 * @return Nothing : display error if not permit
**/
function checkFaqAccess() {
	global $CFG_GLPI;

	if ($CFG_GLPI["public_faq"] == 0 && !haveRight("faq", "r")) {
		displayRightError();
	}

}

/**
 * Include the good language dict.
 *
 * Get the default language from current user in $_SESSION["glpilanguage"].
 * And load the dict that correspond.
 * @param $forcelang Force to load a specific lang 
 *
 * @return nothing (make an include)
 *
**/
function loadLanguage($forcelang='') {

	global $LANG, $CFG_GLPI;
	$file = "";

	if (!isset($_SESSION["glpilanguage"])){
		$_SESSION["glpilanguage"]=$CFG_GLPI["default_language"];
	}

	$trytoload=$_SESSION["glpilanguage"];
	// Force to load a specific lang
	if (!empty($forcelang)){
		$trytoload=$forcelang;
	}
	// If not set try default lang file
	if (empty($trytoload)){
		$trytoload=$CFG_GLPI["default_language"];
	}

	if (isset ($CFG_GLPI["languages"][$trytoload][1])) {
		$file = "/locales/" . $CFG_GLPI["languages"][$trytoload][1];
	}

	if (empty ($file) || !is_file(GLPI_ROOT . $file)) {
		$trytoload='en_GB';
		$file = "/locales/en_GB.php";
	}
	$options = array (
		'cacheDir' => GLPI_CACHE_DIR,
		'lifeTime' => DEFAULT_CACHE_LIFETIME,
		'automaticSerialization' => true,
		'caching' => $CFG_GLPI["use_cache"],
		'hashedDirectoryLevel' => 2,
		'masterFile' => GLPI_ROOT . $file,
		'fileLocking' => CACHE_FILELOCKINGCONTROL,
		'writeControl' => CACHE_WRITECONTROL,
		'readControl' => CACHE_READCONTROL,
		);
	$cache = new Cache_Lite_File($options);

	// Set a id for this cache : $file
	if (!($LANG = $cache->get($file, "GLPI_LANG"))) {
		// Cache miss !
		// Put in $LANG datas to put in cache
		include (GLPI_ROOT . $file);
		$cache->save($LANG, $file, "GLPI_LANG");
	}

	// Debug display lang element with item
	if ($CFG_GLPI["debug"] && $CFG_GLPI["debug_lang"]) {
		foreach ($LANG as $module => $tab) {
			foreach ($tab as $num => $val) {
				$LANG[$module][$num] .= "<span style='font-size:12px; color:red;'>$module/$num</span>";
			}
		}
	}
	return $trytoload;
}

/**
 * Set the entities session variable. Load all entities from DB
 *
 * @param $userID : ID of the user
 * @return Nothing 
**/
function initEntityProfiles($userID) {
	global $DB;

	$profile = new Profile;

	$query = "SELECT DISTINCT glpi_profiles.* FROM glpi_users_profiles INNER JOIN glpi_profiles ON (glpi_users_profiles.FK_profiles = glpi_profiles.ID)
					WHERE glpi_users_profiles.FK_users='$userID'";
	$result = $DB->query($query);
	$_SESSION['glpiprofiles'] = array ();
	if ($DB->numrows($result)) {
		while ($data = $DB->fetch_assoc($result)) {
			$profile->fields = array ();
			$profile->getFromDB($data['ID']);
			$profile->cleanProfile();
			$_SESSION['glpiprofiles'][$data['ID']] = $profile->fields;
		}

		foreach ($_SESSION['glpiprofiles'] as $key => $tab) {
			$query2 = "SELECT glpi_users_profiles.FK_entities as eID, glpi_users_profiles.ID as kID, glpi_users_profiles.recursive as recursive, glpi_entities.* FROM glpi_users_profiles LEFT JOIN glpi_entities ON (glpi_users_profiles.FK_entities = glpi_entities.ID)
													WHERE glpi_users_profiles.FK_profiles='$key' AND glpi_users_profiles.FK_users='$userID'";
			$result2 = $DB->query($query2);
			if ($DB->numrows($result2)) {
				while ($data = $DB->fetch_array($result2)) {
					$_SESSION['glpiprofiles'][$key]['entities'][$data['kID']]['ID'] = $data['eID'];
					$_SESSION['glpiprofiles'][$key]['entities'][$data['kID']]['name'] = $data['name'];
					$_SESSION['glpiprofiles'][$key]['entities'][$data['kID']]['completename'] = $data['completename'];
					$_SESSION['glpiprofiles'][$key]['entities'][$data['kID']]['recursive'] = $data['recursive'];
				}
			}
		}
	}
}

/**
 * Change active profile to the $ID one. Update glpiactiveprofile session variable.
 *
 * @param $ID : ID of the new profile
 * @return Nothing 
**/
function changeProfile($ID) {
	global $CFG_GLPI,$LANG;
	if (isset ($_SESSION['glpiprofiles'][$ID]) && count($_SESSION['glpiprofiles'][$ID]['entities'])) {
		// glpiactiveprofile -> active profile
		$_SESSION['glpiactiveprofile'] = $_SESSION['glpiprofiles'][$ID];
		$_SESSION['glpi_entities_tree']=array();
		$_SESSION['glpiactiveentities'] = array ();
		// Init ancestors if needed
		if (!isset($_SESSION['glpi_entities_ancestors'])){
			$_SESSION['glpi_entities_ancestors']=array();
		}
		// glpiactiveentities -> active entities
		foreach ($_SESSION['glpiactiveprofile']['entities'] as $key => $val) {
			if (!$val['recursive']) {
				if (!array_search($val["ID"], $_SESSION['glpiactiveentities'])) {
					$_SESSION['glpiactiveentities'][$val['ID']] = $val['ID'];
					$entity=array();
					$entity[$val['ID']]['tree']=array();
					
					if ($val['ID']==0){
						$entity[$val['ID']]['name'] = $LANG["entity"][2];
					} else {
						$entity[$val['ID']]['name'] = $val['name'];
					}
					$_SESSION['glpi_entities_tree'][] = $entity;
				}
			} else {
				$entitytree = getTreeForItem("glpi_entities", $val['ID']);
				$_SESSION['glpi_entities_tree'][]=$entitytree;
				$entities = contructListFromTree($entitytree);

				if (count($entities)) {
					foreach ($entities as $key2 => $val2) {
						$_SESSION['glpiactiveentities'][$key2] = $key2;
					}
				}
			}
		}
		if (isset($_SESSION['glpiactiveentities'][$_SESSION["glpidefault_entity"]])){
			changeActiveEntities($_SESSION["glpidefault_entity"],true);
		} else {
			changeActiveEntities("all");
		}

		
		doHook("change_profile");
	}
	cleanCache("GLPI_HEADER_".$_SESSION["glpiID"]);
}


/**
 * Change active entity to the $ID one. Update glpiactiveentities session variable.
 * Reload groups related to this entity.
 *
 * @param $ID : ID of the new active entity ("all"=>load all possible entities)
 * @param $recursive : also display sub entities of the active entity ?
 * @return Nothing 
**/
function changeActiveEntities($ID="all",$recursive=false) {
	global $LANG;
	$newentities=array();
	if ($ID=="all"){
		foreach ($_SESSION['glpi_entities_tree'] as $key => $tree){
			$entities = contructListFromTree($tree);
			if (count($entities)) {
				foreach ($entities as $key2 => $val2) {
					$newentities[$key2] = $key2;
				}
			}		
		}
	} else {
		$newentities[$ID] = $ID;
		if ($recursive){
			foreach ($_SESSION['glpi_entities_tree'] as $key => $tree){
				$entities = contructListFromTree($tree);
				$tofind=array($ID);
				while (count($tofind)){
					$founded=array();
					foreach ($entities as $key2 => $val2){
						if (in_array($val2,$tofind)){
							$newentities[$key2] = $key2;
							$founded[]=$key2;
							unset($entities[$key2]);
						}
					}
					$tofind=$founded;
				}
			}
		}
	}
	if (count($newentities)>0){
		$_SESSION['glpiactiveentities']=$newentities;
		// Active entity loading
		$active=key($_SESSION['glpiactiveentities']);
		$_SESSION["glpiactive_entity"] = $active;
		$_SESSION["glpiactive_entity_name"] = getDropdownName("glpi_entities",$active);
		if ($recursive){
			$_SESSION["glpiactive_entity_name"] .= " (".$LANG["entity"][7].")";
		}
		if ($ID=="all"){
			$_SESSION["glpiactive_entity_name"] .= " (".$LANG["buttons"][40].")";
		}
		if (countElementsInTable('glpi_entities')<count($_SESSION['glpiactiveentities'])){
			$_SESSION['glpishowallentities']=1;
		} else {
			$_SESSION['glpishowallentities']=0;
		}
		
		// Clean session variable to search system
		if (isset($_SESSION['glpisearch'])&&count($_SESSION['glpisearch'])){
			foreach ($_SESSION['glpisearch'] as $type => $tab){
				if (isset($tab['start'])&&$tab['start']>0){
					$_SESSION['glpisearch'][$type]['start']=0;
				}
			}
		}

		loadGroups();
		doHook("change_entity");
		cleanCache("GLPI_HEADER_".$_SESSION["glpiID"]);
	}
}

/**
 * Load groups where I am in the active entity.
 * @return Nothing 
**/
function loadGroups() {
	global $DB;

	$_SESSION["glpigroups"] = array ();


	$first=true;
	$entities="";
	foreach ($_SESSION['glpiactiveentities'] as $val){
		if (!$first) $entities.=",";
		else $first=false;
		$entities.=$val;
	}			


	$query_gp = "SELECT FK_groups FROM glpi_users_groups LEFT JOIN glpi_groups ON (glpi_users_groups.FK_groups = glpi_groups.ID) WHERE glpi_users_groups.FK_users='" . $_SESSION['glpiID'] . "' AND glpi_groups.FK_entities IN ($entities)";

	$result_gp = $DB->query($query_gp);
	if ($DB->numrows($result_gp)) {
		while ($data = $DB->fetch_array($result_gp)) {
			$_SESSION["glpigroups"][] = $data["FK_groups"];
		}
	}
}

/**
 * Check if you could create recursive object in the entity of id = $ID
 *
 * @param $ID : ID of the entity
 * @return Boolean : 
**/
function haveRecursiveAccessToEntity($ID) {

	// Right by profile
	foreach ($_SESSION['glpiactiveprofile']['entities'] as $key => $val) {
		if ($val['ID']==$ID) {
			return $val['recursive']; 		
		}
	}

	// Right is from a recursive profile
	if (isset ($_SESSION['glpiactiveentities'])) {
		return in_array($ID, $_SESSION['glpiactiveentities']);
	} 

	return false;
}

/**
 * Check if you could access (read) to the entity of id = $ID
 *
 * @param $ID : ID of the entity
 * @param $recursive : boolean if resursive item
 * 
 * @return Boolean : read access to entity
**/
function haveAccessToEntity($ID, $recursive=0) {
	if (!isset ($_SESSION['glpiactiveentities'])) {
		return false;
	}
	
	if (!$recursive) {		
		return in_array($ID, $_SESSION['glpiactiveentities']);
	}

	if (in_array($ID, $_SESSION['glpiactiveentities'])) {
		return true;
	}
		
	// Recursive object
	foreach ($_SESSION['glpiactiveentities'] as $ent) {
		if (in_array($ID, getEntityAncestors($ent))) {
			return true;		
		}
	}

	return false;
}

/**
 * Check if you could access to one entity of an list
 *
 * @param $tab : list ID of entities
 * @return Boolean : 
**/
function haveAccessToOneOfEntities($tab) {
	$access=false;
	if (is_array($tab)&&count($tab)){
		foreach ($tab as $val){
			if (haveAccessToEntity($val)){
				return true;
			}
		}
	}
	return $access;
}

/**
 * Get SQL request to restrict to current entities of the user
 *
 * @param $separator : separator in the begin of the request
 * @param $table : table where apply the limit (if needed, multiple tables queries)
 * @param $field : field where apply the limit (id != FK_entities)
 * @param $value : entity to restrict (if not set use $_SESSION['glpiactiveentities']). single item or array
 * @param $recursive : need to use recursive process to find item (field need to be named recursive)
 * @return String : the WHERE clause to restrict 
**/
function getEntitiesRestrictRequest($separator = "AND", $table = "", $field = "",$value='',$recursive=false) {

	$query = $separator ." ( ";

	// !='0' needed because consider as empty 
	if ($value!='0'&&empty($value)&&isset($_SESSION['glpishowallentities'])&&$_SESSION['glpishowallentities']){
		return $query." 1 ) ";
	}

	if (!is_array($value)&&strlen($value)==0){
		$value=$_SESSION['glpiactiveentities'];
	}


	if (!empty ($table)) {
		$query .= $table . ".";
	}
	if (empty($field)){
		if ($table=='glpi_entities') {
			$field="ID";
		} else {
			$field="FK_entities";
		}
	}

	$query.=$field;

	if (is_array($value)){
		$query.=" IN (";
		$first=true;
		
		foreach ($value as $val){
			if (!$first){
				$query.=",";
			} else {
				$first=false;
			}
			$query.=$val;
		}
		$query.=") ";
	} else {
		$query.= " = '$value' ";
	}

	if ($recursive){
		$ancestors=array();
		if (is_array($value)){
			foreach ($value as $val){
				$ancestors+=getEntityAncestors($val);
			}
		} else {
			$ancestors=getEntityAncestors($value);
		}
		
		if (count($ancestors)){
			$query.=" OR ( $table.recursive='1' AND $table.$field IN (";
			$first=true;
			foreach ($ancestors as $val){
				if (!$first){
					$query.=",";
				} else {
					$first=false;
				}
				$query.=$val;
			}
			$query.="))";
		}
	}

	$query.=" ) ";

	return $query;
}

/**
 * Connect to a LDAP serveur
 *
 * @param $host : LDAP host to connect
 * @param $port : port to use
 * @param $login : login to use
 * @param $password : password to use
 * @param $use_tls : use a tls connection ?
 * @return link to the LDAP server : false if connection failed
**/
function connect_ldap($host, $port, $login = "", $password = "", $use_tls = false) {
	global $CFG_GLPI;

	$ds = @ldap_connect($host, $port);
	if ($ds) {
		@ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
		@ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
		if ($use_tls) {
			if (!@ldap_start_tls($ds)) {
				return false;
			}
		}
		// Auth bind
		if ($login != '') {
			$b = @ldap_bind($ds, $login, $password);
		} else { // Anonymous bind
			$b = @ldap_bind($ds);
		}
		if ($b) {
			return $ds;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function try_connect_ldap($host, $port, $rdn, $rpass, $use_tls,$login, $password){
	$ds = connect_ldap($host, $port, $rdn, $rpass, $use_tls);
	// Test with login and password of the user
	if (!$ds) {
		$ds = connect_ldap($host, $port, $login, $password, $use_tls);
	}
	return $ds;		
}

function ldap_search_user_dn($ds, $basedn, $login_attr, $login, $condition) {

	// Tenter une recherche pour essayer de retrouver le DN
	$filter = "($login_attr=$login)";
	
	if (!empty ($condition)){
		$filter = "(& $filter $condition)";
	}

	if ($result = ldap_search($ds, $basedn, $filter, 
		array ("dn", $login_attr),0,0)
	){
	
		$info = ldap_get_entries($ds, $result);
		if (is_array($info) AND $info['count'] == 1) {
			return $info[0]['dn'];
		} else { // Si echec, essayer de deviner le DN / Flat LDAP
			$dn = "$login_attr=$login, " . $basedn;
			return $dn;
		}
	} else {
		return false;
	}
}

/*
 * Try to authentify a user by checking all the directories
**/
function try_ldap_auth($identificat,$login,$password, $id_auth = -1) {

	//If no specific source is given, test all ldap directories
	if ($id_auth == -1) {
		foreach  ($identificat->auth_methods["ldap"] as $ldap_method) {
			if (!$identificat->auth_succeded) {
				$identificat = ldap_auth($identificat, $login,$password,$ldap_method);
			} else {
				break;
			}
		}
	} else if(array_key_exists($id_auth,$identificat->auth_methods["ldap"])){ //Check if the ldap server indicated as the last good one still exists !
		//A specific ldap directory is given, test it and only this one !
		$identificat = ldap_auth($identificat, $login,$password,$identificat->auth_methods["ldap"][$id_auth]);
	}
	return $identificat;
}

/*
 * Authentify a user by checking a specific directory
**/
function ldap_auth($identificat,$login,$password, $ldap_method) {

	$user_dn = $identificat->connection_ldap($ldap_method["ID"],$ldap_method["ldap_host"], $ldap_method["ldap_port"], $ldap_method["ldap_basedn"], $ldap_method["ldap_rootdn"], $ldap_method["ldap_pass"], $ldap_method["ldap_login"],$login, $password, $ldap_method["ldap_condition"], $ldap_method["ldap_use_tls"]);
	if ($user_dn) {
		$identificat->auth_succeded = true;
		$identificat->extauth = 1;
		$identificat->user_present = $identificat->user->getFromDBbyName($login);
		//$identificat->user->getFromLDAP($ldap_method, $user_dn, utf8_decode($login), utf8_decode($password));
		$identificat->user->getFromLDAP($identificat->ldap_connection,$ldap_method, $user_dn, $login, $password);
		$identificat->auth_parameters = $ldap_method;
		$identificat->user->fields["auth_method"] = AUTH_LDAP;
		$identificat->user->fields["id_auth"] = $ldap_method["ID"];
	}
	return $identificat;
}

function try_mail_auth($identificat, $login,$password,$id_auth = -1) {
	if ($id_auth == -1) {
		foreach ($identificat->auth_methods["mail"] as $mail_method) {
			if (!$identificat->auth_succeded) {
				$identificat = mail_auth($identificat, $login,$password,$mail_method);
			}
			else {
				break;
			}
		}
	} else if(array_key_exists($id_auth,$identificat->auth_methods["mail"])){ //Check if the mail server indicated as the last good one still exists !
		$identificat = mail_auth($identificat, $login,$password,$identificat->auth_methods["mail"][$id_auth]);
	}
	return $identificat;
}

function mail_auth($identificat, $login,$password,$mail_method) {

	if (isset($mail_method["imap_auth_server"])&&!empty ($mail_method["imap_auth_server"])) {
		$identificat->auth_succeded = $identificat->connection_imap($mail_method["imap_auth_server"], utf8_decode($login), utf8_decode($password));
		if ($identificat->auth_succeded) {
			$identificat->extauth = 1;
			$identificat->user_present = $identificat->user->getFromDBbyName($login);
			$identificat->auth_parameters = $mail_method;
		
			$identificat->user->getFromIMAP($mail_method, utf8_decode($login));

			//Update the authentication method for the current user
			$identificat->user->fields["auth_method"] = AUTH_MAIL;
			$identificat->user->fields["id_auth"] = $mail_method["ID"];
		}
	}
	return $identificat;
}

//Test a connexion to the IMAP/POP server
function test_auth_mail($imap_auth_server,$login,$password){
	
	$identificat = new Identification();
	return $identificat->connection_imap($imap_auth_server, utf8_decode($login), utf8_decode($password));
}

/**
 * Import a user from ldap
 * Check all the directories. When the user is found, then import it
**/
function import_user_from_ldap_servers($login){
	global $LANG;

	$identificat = new Identification;
	$identificat->user_present = $identificat->userExists($login);

	//If the user does not exists
	if ($identificat->user_present == 0){
		$identificat->getAuthMethods();
		$ldap_methods = $identificat->auth_methods["ldap"];
		$userid = -1;
		
		foreach ($ldap_methods as $ldap_method){
			$result=ldapImportUserByServerId($login, 0,$ldap_method["ID"]);
			if ($result != false){
				return $result;
			}  
		}
		addMessageAfterRedirect($LANG["login"][15]);
	} else {
		addMessageAfterRedirect($LANG["setup"][606]);
	}
	return false;
	
}


function useAuthMail(){
	global $DB;	

	//Get all the pop/imap servers
	$sql = "SELECT count(*) FROM glpi_auth_mail";
	$result = $DB->query($sql);
	if ($DB->result($result,0,0) > 0) {
		return true;
	}
	return false;
}

function useAuthLdap(){
	global $DB;	

	//Get all the ldap directories
	$sql = "SELECT count(*) FROM glpi_auth_ldap";
	$result = $DB->query($sql);
	if ($DB->result($result,0,0) > 0) {
		return true;
	}
	return false;
}


function useAuthExt(){
	global $DB;	

	//Get all the ldap directories
	if (useAuthLdap()){
		return true;
	}

	if (useAuthMail()){
		return true;
	}
	return false;
}

function showReplicatesList($target,$master_id)
{
	global $DB,$LANG,$CFG_GLPI;

	addNewReplicateForm($target, $master_id);
	
	$sql = "SELECT * FROM glpi_auth_ldap_replicate WHERE server_id=".$master_id." ORDER BY name";
	$result = $DB->query($sql);
	if ($DB->numrows($result)>0){
		echo "<br>";
		$canedit = haveRight("config", "w");

		echo "<form action=\"$target\" method=\"post\" name=\"ldap_replicates_form\" id=\"ldap_replicates_form\">";
		echo"<input type=\"hidden\" name=\"ID\" value=\"" . $master_id . "\" ></td>";
		echo "<div class='center'>";
		echo "<table class='tab_cadre_fixe'>";
	
		echo "<tr><th colspan='4'><div class='relative'><span><strong>" . $LANG["ldap"][18] . "</strong></span></th></tr>";
		echo "<tr class='tab_bg_1'><td class='center'></td><td class='center'>".$LANG["common"][16]."</td><td class='center'>".$LANG["ldap"][18]."</td><td class='center'></td>";
		while ($ldap_replicate = $DB->fetch_array($result)){
			echo "<tr class='tab_bg_2'><td class='center'>";
				
			if (isset ($_GET["select"]) && $_GET["select"] == "all"){
				$sel = "checked";
			} else {
				$sel="";
			}	
			echo "<input type='checkbox' name='item[" . $ldap_replicate["ID"] . "]' value='1' $sel>";
			echo "</td>";
			echo "<td class='center'>" . $ldap_replicate["name"] . "</td>";
			echo "<td class='center'>".$ldap_replicate["ldap_host"]." : ".$ldap_replicate["ldap_port"] . "</td>"; 
			echo "<td align='center' colspan=4>"; 
			echo"<input type=\"submit\" name=\"test_ldap_replicate[".$ldap_replicate["ID"]."]\" class=\"submit\" value=\"" . $LANG["buttons"][50] . "\" ></td>";
			echo"</tr>";
				
		}
				
		echo "<div class='center'>";
		echo "<table width='950px'>";
				
		echo "<tr><td><img src=\"" . $CFG_GLPI["root_doc"] . "/pics/arrow-left.png\" alt=''></td><td class='center'><a onclick= \"if ( markAllRows('ldap_replicates_form') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?next=extauth_ldap&ID=$master_id&select=all'>" . $LANG["buttons"][18] . "</a></td>";
		echo "<td>/</td><td class='center'><a onclick= \"if ( unMarkAllRows('ldap_replicates_form') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?next=extauth_ldap&ID=$master_id&select=none'>" . $LANG["buttons"][19] . "</a>";
		echo "</td><td align='left' width='80%'>";
		echo "<input type='submit' name='delete_replicate' value=\"" . $LANG["buttons"][6] . "\" class='submit'></td>";
		echo "</tr>";
				
		echo "</table>";
		echo "</div>";
		echo "</form>";
	}
}

function addNewReplicateForm($target, $master_id){
	global $LANG;
	
	echo "<form action=\"$target\" method=\"post\" name=\"add_replicate_form\" id=\"add_replicate_form\">";
	echo "<div class='center'>";
	echo "<table class='tab_cadre_fixe'>";
	
	echo "<tr><th colspan='4'><div class='relative'><span><strong>" .$LANG["ldap"][20] . "</strong></span></th></tr>";
	echo "<tr class='tab_bg_1'><td class='center'>".$LANG["common"][16]."</td><td class='center'>".$LANG["common"][52]."</td><td class='center'>".$LANG["setup"][175]."</td><td></td></tr>";
	echo "<tr class='tab_bg_1'>"; 
	echo "<td class='center'><input type='text' name='name'></td>";
	echo "<td class='center'><input type='text' name='ldap_host'></td>"; 
	echo "<td class='center'><input type='text' name='ldap_port'></td>";
	echo "<input type='hidden' name='next' value=\"extauth_ldap\"></td>";
	echo "<input type='hidden' name='server_id' value=\"".$master_id."\">";
	echo "<td class='center'><input type='submit' name='add_replicate' value=\"" . $LANG["buttons"][2] . "\" class='submit'></td></tr>";
	echo "</table>";
	echo "</div>";
	echo "</form>";
	
}


function getAllReplicateForAMaster($master_id){
	global $DB;
	
	$replicates = array();
	$result = $DB->query("SELECT ID, ldap_host,ldap_port FROM glpi_auth_ldap_replicate WHERE server_id=".$master_id);
	if ($DB->numrows($result)>0){
		while ($replicate = $DB->fetch_array($result)){
			$replicates[] = array("ID"=>$replicate["ID"], "ldap_host"=>$replicate["ldap_host"], "ldap_port"=>$replicate["ldap_port"]);
		}
	}
	return $replicates;
}

function getAllReplicatesNamesForAMaster($master_id){
	$replicates = getAllReplicateForAMaster($master_id);
	$str = "";
	foreach ($replicates as $replicate){
		$str.= ($str!=''?',':'')."&nbsp;".$replicate["ldap_host"].":".$replicate["ldap_port"];
	}
	return $str;	
}

// Check if auto login or CAS is activate and redirect to login.php if ok
function checkAlternateAuthSystems($redirect=false){
	global $CFG_GLPI;
	if (isset($_GET["noAUTO"])||isset($_POST["noAUTO"])){
		return false;
	}

	// Using x509 server
	if (!empty($CFG_GLPI["x509_email_field"])
		&&isset($_SERVER['SSL_CLIENT_S_DN'])&&ereg($CFG_GLPI["x509_email_field"],$_SERVER['SSL_CLIENT_S_DN'])) {
		if ($redirect){
			glpi_header("login.php");
		} else {
			return AUTH_X509;
		}
	}

	// Existing auth method
	if (!empty($CFG_GLPI["existing_auth_server_field"])
		&&isset($_SERVER[$CFG_GLPI["existing_auth_server_field"]])&&!empty($_SERVER[$CFG_GLPI["existing_auth_server_field"]])) {
		if ($redirect){
			glpi_header("login.php");
		} else {
			return AUTH_EXTERNAL;
		}
	}

	// Using CAS server
	if (!empty($CFG_GLPI["cas_host"])) {
		if ($redirect){
			glpi_header("login.php");
		} else {
			return AUTH_CAS;
		}
	}

	return false;
}

?>
