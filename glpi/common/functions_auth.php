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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

/**
* Test if an user have the right to assign a job to another user 
*
* Return true if the user with name $name is allowed to assign a job.
* Else return false.
*
*@param $name (username).
*@return boolean
*/
function can_assign_job($name)
{
  $db = new DB;
  $query = "SELECT * FROM glpi_users WHERE (name = '".$name."')";
	$result = $db->query($query);
	if (!$result&&$db->numrows()==0) return false;
	$type = $db->result($result, 0, "can_assign_job");
	if ($type == 'yes')
	{
	 return true;
	 }
	 else
	 {
	 return false;
	 }
}
/**
* Test if an user has the postonly rights or higher.
*
* Return true if the user with authentication type $authtype has
* the postonly rights.
*
*
*@param $authtype authentication type
*
*@return boolean
*
**/
function isPostOnly($authtype) {
	switch ($authtype){
		case "post-only" :
		case "normal" :
		case "admin":
		case "super-admin":
			return true;
			break;
		default :
			return false;
		}
}
/**
* Test if an user has the normal rights or higher.
*
* Return true if the user with authentication type $authtype has
* the normal rights.
*
*
*@param $authtype authentication type
*
*@return boolean
*
**/
function isNormal($authtype) {
	switch ($authtype){
		case "normal" :
		case "admin":
		case "super-admin":
			return true;
			break;
		default :
			return false;
		}
}

/**
* Test if an user has the admin rights or higher.
*
* Return true if the user with authentication type $authtype has
* the admin rights.
*
*
*@param $authtype authentication type
*
*@return boolean
*
**/
function isAdmin($authtype) {
	switch ($authtype){
		case "admin":
		case "super-admin":
			return true;
			break;
		default :
			return false;
		}
}
/**
* Test if an user has the super-admin rights or higher.
*
* Return true if the user with authentication type $authtype has
* the super-admin rights.
*
*
*@param $authtype authentication type
*
*@return boolean
*
**/
function isSuperAdmin($authtype) {
	switch ($authtype){
			case "super-admin":
			return true;
			break;
		default :
			return false;
		}
}

/**
* Verify if the current user has some rights
*
* Do nothing if the current user (wich session call this func) has 
* rights egal or higher as $authtype.
* 
* @param $authtype min level right we wish to allow
* @Return Nothing (display function)
*
**/      
function checkAuthentication($authtype) {
	// Universal method to have a magic-quote-gpc system
	global $_POST, $_GET,$_COOKIE,$tab,$cfg_features,$cfg_install;
	// Clean array and addslashes
	
	if (get_magic_quotes_gpc()) {
		if (isset($_POST)){
			$_POST = array_map('stripslashes_deep', $_POST);
		}
		if (isset($_GET)){
			$_GET = array_map('stripslashes_deep', $_GET);
		}
		if (isset($tab)){
			$tab = array_map('stripslashes_deep', $tab);    
		}
	}    
	if (isset($_POST)){
		$_POST = array_map('addslashes_deep', $_POST);
		$_POST = array_map('clean_cross_side_scripting_deep', $_POST);
	}
	if (isset($_GET)){
		$_GET = array_map('addslashes_deep', $_GET);
		$_GET = array_map('clean_cross_side_scripting_deep', $_GET);
	}
	if (isset($tab)){
		$tab = array_map('addslashes_deep', $tab);
		$tab = array_map('clean_cross_side_scripting_deep', $tab);
	}

	// Checks a GLOBAL user and password against the database
	// If $authtype is "normal" or "admin", it checks if the user
	// has the privileges to do something. Should be used in every 
	// control-page to set a minium security level.
	
	
	
	if(!session_id()){@session_start();}

	if (isset($_SESSION["root"])&&$cfg_install["root"]!=$_SESSION["root"]) {
		glpi_header($_SESSION["root"]);
	}
	
	// Override cfg_features by session value
	if (isset($_SESSION['list_limit'])) $cfg_features["list_limit"]=$_SESSION['list_limit'];

	GLOBAL $cfg_install, $lang, $HTMLRel;

	if(empty($_SESSION["authorisation"])&& $authtype != "anonymous")
	{
		nullHeader("Login",$_SERVER["PHP_SELF"]);
		echo "<div align='center'><b><a href=\"".$cfg_install["root"]."/logout.php\">Relogin</a></b></div>";
		nullFooter();
		die();	
	}

	
	// New database object
	loadLanguage();
	$type="anonymous";
	if (isset($_SESSION["glpitype"]))
		$type = $_SESSION["glpitype"];	
		
	// Check username and password
	if (!isset($_SESSION["glpiname"])&& $authtype != "anonymous") {
		header("Vary: User-Agent");
		nullHeader($lang["login"][3], $_SERVER["PHP_SELF"]);
		echo "<div align='center'><b>".$lang["login"][0]."</b><br><br>";
		echo "<b><a href=\"".$cfg_install["root"]."/logout.php\">".$lang["login"][1]."</a></b></div>";
		nullFooter();
		exit();
	} else {
		header("Vary: User-Agent");

		loadLanguage();

		switch ($authtype) {
			case "super-admin";
				if (!isSuperAdmin($type)) 
				{
					commonHeader($lang["login"][5],$_SERVER["PHP_SELF"]);
					echo "<div align='center'><br><br><img src=\"".$HTMLRel."pics/warning.png\" alt=\"warning\"><br><br>";
					echo "<b>".$lang["login"][5]."</b></div>";
					commonFooter();
					exit();
				}
			break;
				
			case "admin";
				if (!isAdmin($type)) 
				{
					commonHeader($lang["login"][5],$_SERVER["PHP_SELF"]);
					echo "<div align='center'><br><br><img src=\"".$HTMLRel."pics/warning.png\" alt=\"warning\"><br><br>";
					echo "<b>".$lang["login"][5]."</b></div>";
					commonFooter();
					exit();
				}
			break;
				
			case "normal";
				if (!isNormal($type))
				{
					commonHeader($lang["login"][5],$_SERVER["PHP_SELF"]);
					echo "<div align='center'><br><br><img src=\"".$HTMLRel."pics/warning.png\" alt=\"warning\"><br><br>";
					echo "<b>".$lang["login"][5]."</b></div>";
					commonFooter();
					exit();
				}
			break;
		
			case "post-only";
				if (!isPostOnly($type)) {
					commonHeader($lang["login"][5],$_SERVER["PHP_SELF"]);
					echo "<div align='center'><br><br><img src=\"".$HTMLRel."pics/warning.png\" alt=\"warning\"><br><br>";
					echo "<b>".$lang["login"][5]."</b></div>";
					commonFooter();
					exit();
				}
			break;
			case "anonymous";
    				if ($cfg_features['public_faq'] == 0){
      					nullHeader("Login",$_SERVER["PHP_SELF"]);
      					echo "<div align='center'><b><a href=\"".$cfg_install["root"]."/logout.php\">No anonymous authorisation</a></b></div>";
      					nullFooter();
      					exit();
    				}
			break;
				
			break;
		}
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

	GLOBAL $lang,$cfg_install,$cfg_debug;

	if(empty($_SESSION["glpilanguage"])) {
		$file= "/glpi/dicts/".$cfg_install["languages"][$cfg_install["default_language"]][1];
	} else {
		$file = "/glpi/dicts/".$cfg_install["languages"][$_SESSION["glpilanguage"]][1];
	}
		include ("_relpos.php");
		include ($phproot . $file);
		
	// Debug display lang element with item
	if ($cfg_debug["active"]&&$cfg_debug["lang"]){
		foreach ($lang as $module => $tab)
		foreach ($tab as $num => $val){
			$lang[$module][$num].="<span style='font-size:12px; color:red;'>$module/$num</span>";
		
		}
	}

}

?>
