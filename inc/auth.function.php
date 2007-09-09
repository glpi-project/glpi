<?php


/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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
 */
function haveRight($module, $right) {

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
 */
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
			return haveRight("knowbase", $right);
			break;
		case USER_TYPE :
			return haveRight("user", $right);
			break;
		case TRACKING_TYPE :
			return haveRight("show_all_ticket", $right);
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
 * Display common message for privileges errors
 *
 * @return Nothing
 */
function displayRightError() {
	global $LANG, $CFG_GLPI, $HEADER_LOADED;
	if (!$HEADER_LOADED) {
		if (!isset ($_SESSION["glpiactiveprofile"]["interface"]))
			nullHeader($LANG["login"][5], $_SERVER['PHP_SELF']);
		else
			if ($_SESSION["glpiactiveprofile"]["interface"] == "central")
				commonHeader($LANG["login"][5], $_SERVER['PHP_SELF']);
			else
				if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk")
					helpHeader($LANG["login"][5], $_SERVER['PHP_SELF']);
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
 */
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
 */
function checkSeveralRightsOr($modules) {
	global $CFG_GLPI;

	$valid = false;
	if (count($modules))
		foreach ($modules as $mod => $right)
			if (haveRight($mod, $right))
				$valid = true;

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
 */
function checkSeveralRightsAnd($modules) {
	global $CFG_GLPI;

	$valid = true;
	if (count($modules))
		foreach ($modules as $mod => $right)
			if (!haveRight($mod, $right))
				$valid = false;

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
 */
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
 */
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
 */
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
 */
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
 */
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
 *
 * @return nothing (make an include)
 *
 */
function loadLanguage() {

	global $LANG, $CFG_GLPI;
	$file = "";
	if (!isset($_SESSION["glpilanguage"])){
		$_SESSION["glpilanguage"]=$CFG_GLPI["default_language"];
	}

	if (empty ($_SESSION["glpilanguage"])) {
		if (isset ($CFG_GLPI["languages"][$CFG_GLPI["default_language"]][1])) {
			$file = "/locales/" . $CFG_GLPI["languages"][$CFG_GLPI["default_language"]][1];
		}
	} else {
		if (isset ($CFG_GLPI["languages"][$_SESSION["glpilanguage"]][1])) {
			$file = "/locales/" . $CFG_GLPI["languages"][$_SESSION["glpilanguage"]][1];
		}
	}
	if (empty ($file) || !is_file(GLPI_ROOT . $file)) {
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

}

/**
 * Set the entities session variable. Load all entities from DB
 *
 * @param $userID : ID of the user
 * @return Nothing 
 */
function initEntityProfiles($userID) {
	global $DB;

	$profile = new Profile;

	$query = "SELECT DISTINCT glpi_profiles.* FROM glpi_users_profiles INNER JOIN glpi_profiles ON (glpi_users_profiles.FK_profiles = glpi_profiles.ID)
					WHERE glpi_users_profiles.active='1' AND glpi_users_profiles.FK_users='$userID'";
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
													WHERE glpi_users_profiles.FK_profiles='$key' AND glpi_users_profiles.active='1' AND glpi_users_profiles.FK_users='$userID'";
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
 */
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
		changeActiveEntities("all");
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
 */
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
		loadGroups();
		doHook("change_entity");
		cleanCache("GLPI_HEADER_".$_SESSION["glpiID"]);
	}
}

/**
 * Load groups where I am in the active entity.
 * @return Nothing 
 */
function loadGroups() {
	global $DB;

	$_SESSION["glpigroups"] = array ();
	$query_gp = "SELECT * FROM glpi_users_groups LEFT JOIN glpi_groups ON (glpi_users_groups.FK_groups = glpi_groups.ID) WHERE FK_users='" . $_SESSION['glpiID'] . "' AND glpi_groups.FK_entities='" . $_SESSION["glpiactive_entity"] . "'";

	$result_gp = $DB->query($query_gp);
	if ($DB->numrows($result_gp)) {
		while ($data = $DB->fetch_array($result_gp)) {
			$_SESSION["glpigroups"][] = $data["FK_groups"];
		}
	}
}

/**
 * Check if you could access to the entity of id = $ID
 *
 * @param $ID : ID of the entity
 * @return Boolean : 
 */
function haveAccessToEntity($ID) {
	if (isset ($_SESSION['glpiactiveentities'])) {
		return in_array($ID, $_SESSION['glpiactiveentities']);
	} else {
		return false;
	}
}

/**
 * Check if you could access to one entity of an list
 *
 * @param $tab : list ID of entities
 * @return Boolean : 
 */
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
 * @return String : the WHERE clause to restrict 
 */
function getEntitiesRestrictRequest($separator = "AND", $table = "", $field = "",$value='') {

	$query = $separator ." ";


	if (!empty ($table)) {
		$query .= $table . ".";
	}

	if (!empty ($field)) {
		$query .= $field;
	} else {
		$query .= "FK_entities";
	}
	
	if (!is_array($value)&&strlen($value)==0){
		$value=$_SESSION['glpiactiveentities'];
	}

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
 */
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
 */
function try_ldap_auth($identificat,$login,$password, $id_auth = -1,$isCAS=0) {

	//If no specific source is given, test all ldap directories
	if ($id_auth == -1) {
		foreach  ($identificat->auth_methods["ldap"] as $ldap_method) {
			if (!$identificat->auth_succeded) {
				$identificat = ldap_auth($identificat, $login,$password,$ldap_method,$isCAS);
			}
			else break;
		}
	} else if(array_key_exists($id_auth,$identificat->auth_methods["ldap"])) //Check if the ldap server indicated as the last good one still exists !
		//A specific ldap directory is given, test it and only this one !
		$identificat = ldap_auth($identificat, $login,$password,$identificat->auth_methods["ldap"][$id_auth],$isCAS);
		
		return $identificat;
}

/*
 * Authentify a user by checking a specific directory
 */
function ldap_auth($identificat,$login,$password, $ldap_method,$isCAS) {
	//$user_dn = $identificat->connection_ldap($ldap_method["ldap_host"], $ldap_method["ldap_port"], $ldap_method["ldap_basedn"], $ldap_method["ldap_rootdn"], $ldap_method["ldap_pass"], $ldap_method["ldap_login"], utf8_decode($login), utf8_decode($password), $ldap_method["ldap_condition"], $ldap_method["ldap_use_tls"]);
	$user_dn = $identificat->connection_ldap($ldap_method["ldap_host"], $ldap_method["ldap_port"], $ldap_method["ldap_basedn"], $ldap_method["ldap_rootdn"], $ldap_method["ldap_pass"], $ldap_method["ldap_login"],$login, $password, $ldap_method["ldap_condition"], $ldap_method["ldap_use_tls"]);
	if ($user_dn) {
		$identificat->auth_succeded = true;
		$identificat->extauth = 1;
		$identificat->user_present = $identificat->user->getFromDBbyName($login);
		//$identificat->user->getFromLDAP($ldap_method, $user_dn, utf8_decode($login), utf8_decode($password));
		$identificat->user->getFromLDAP($ldap_method, $user_dn, $login, $password);
		$identificat->auth_parameters = $ldap_method;
		if (!$isCAS) $identificat->user->fields["auth_method"] = AUTH_LDAP;
		else $identificat->user->fields["auth_method"] = AUTH_CAS;
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
			else break;
		}
	} else if(array_key_exists($id_auth,$identificat->auth_methods["mail"])) //Check if the mail server indicated as the last good one still exists !
		$identificat = mail_auth($identificat, $login,$password,$identificat->auth_methods["mail"][$id_auth]);

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
function test_auth_mail($imap_auth_server,$login,$password)
{
	$identificat = new Identification();
	return $identificat->connection_imap($imap_auth_server, utf8_decode($login), utf8_decode($password));
}

/**
 * Import a user from ldap
 * Check all the directories. When the user is found, then import it
 */
function import_user_from_ldap_servers($login)
{
	$identificat = new Identification;
	$identificat->user_present = $identificat->userExists($login);

	//If the user does not exists
	if ($identificat->user_present == 0)
	{
		$identificat->getAuthMethods();
		$ldap_methods = $identificat->auth_methods["ldap"];
		$userid = -1;
		
		foreach ($ldap_methods as $ldap_method)
		{
			$result=ldapImportUserByServerId($login, 0,$ldap_method["ID"]);
			if ($result != false)
			{
				return $result;
			}  
		}
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

?>
