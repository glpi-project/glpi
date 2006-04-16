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


function checkRight($module,$right) {
	global $lang,$HTMLRel,$HEADER_LOADED;

	if (!haveRight($module,$right)){
		if (!$HEADER_LOADED){
			if (!isset($_SESSION["glpiprofile"]["interface"]))
				nullHeader($lang["login"][5],$_SERVER["PHP_SELF"]);
			else if ($_SESSION["glpiprofile"]["interface"]=="central")
				commonHeader($lang["login"][5],$_SERVER["PHP_SELF"]);
			else if ($_SESSION["glpiprofile"]["interface"]=="helpdesk")
				helpHeader($lang["login"][5],$_SERVER["PHP_SELF"]);
		}
		echo "<div align='center'><br><br><img src=\"".$HTMLRel."pics/warning.png\" alt=\"warning\"><br><br>";
		echo "<b>".$lang["login"][5]."</b></div>";
		nullFooter();
		exit();
	}
}

function checkCommonItemRight($type,$right) {
	global $lang,$HTMLRel,$HEADER_LOADED;

	$ci=new CommonItem();
	$ci->setType($type);
	if (!$ci->haveRight($right)){
		if (!$HEADER_LOADED){
			if (!isset($_SESSION["glpiprofile"]["interface"]))
				nullHeader($lang["login"][5],$_SERVER["PHP_SELF"]);
			else if ($_SESSION["glpiprofile"]["interface"]=="central")
				commonHeader($lang["login"][5],$_SERVER["PHP_SELF"]);
			else if ($_SESSION["glpiprofile"]["interface"]=="helpdesk")
				helpHeader($lang["login"][5],$_SERVER["PHP_SELF"]);
		}
		echo "<div align='center'><br><br><img src=\"".$HTMLRel."pics/warning.png\" alt=\"warning\"><br><br>";
		echo "<b>".$lang["login"][5]."</b></div>";
		nullFooter();
		exit();
	}
}

function checkCentralAccess(){

	global $lang,$HTMLRel,$HEADER_LOADED;

	if ($_SESSION["glpiprofile"]["interface"]!="central"){
		if (!$HEADER_LOADED){
			if (!isset($_SESSION["glpiprofile"]["interface"]))
				nullHeader($lang["login"][5],$_SERVER["PHP_SELF"]);
			else if ($_SESSION["glpiprofile"]["interface"]=="central")
				commonHeader($lang["login"][5],$_SERVER["PHP_SELF"]);
			else if ($_SESSION["glpiprofile"]["interface"]=="helpdesk")
				helpHeader($lang["login"][5],$_SERVER["PHP_SELF"]);
		}
		echo "<div align='center'><br><br><img src=\"".$HTMLRel."pics/warning.png\" alt=\"warning\"><br><br>";
		echo "<b>".$lang["login"][5]."</b></div>";
		nullFooter();
		exit();
	}
}

function checkHelpdeskAccess(){

	global $lang,$HTMLRel,$HEADER_LOADED;

	if ($_SESSION["glpiprofile"]["interface"]!="helpdesk"){
		if (!$HEADER_LOADED){
			if (!isset($_SESSION["glpiprofile"]["interface"]))
				nullHeader($lang["login"][5],$_SERVER["PHP_SELF"]);
			else if ($_SESSION["glpiprofile"]["interface"]=="central")
				commonHeader($lang["login"][5],$_SERVER["PHP_SELF"]);
			else if ($_SESSION["glpiprofile"]["interface"]=="helpdesk")
				helpHeader($lang["login"][5],$_SERVER["PHP_SELF"]);
		}
		echo "<div align='center'><br><br><img src=\"".$HTMLRel."pics/warning.png\" alt=\"warning\"><br><br>";
		echo "<b>".$lang["login"][5]."</b></div>";
		nullFooter();
		exit();
	}
}

function checkLoginUser(){

	global $lang,$HTMLRel;

	if (!isset($_SESSION["glpiuser"])){
		if (!$HEADER_LOADED){
			if (!isset($_SESSION["glpiprofile"]["interface"]))
				nullHeader($lang["login"][5],$_SERVER["PHP_SELF"]);
			else if ($_SESSION["glpiprofile"]["interface"]=="central")
				commonHeader($lang["login"][5],$_SERVER["PHP_SELF"]);
			else if ($_SESSION["glpiprofile"]["interface"]=="helpdesk")
				helpHeader($lang["login"][5],$_SERVER["PHP_SELF"]);
		}
		echo "<div align='center'><br><br><img src=\"".$HTMLRel."pics/warning.png\" alt=\"warning\"><br><br>";
		echo "<b>".$lang["login"][5]."</b></div>";
		nullFooter();
		exit();
	}
}

function checkAccessToPublicFaq(){
	global $lang,$HTMLRel,$cfg_glpi;

	if ($cfg_glpi["public_faq"] == 0 || !haveRight("faq","r")){
		if (!$HEADER_LOADED){
			if (!isset($_SESSION["glpiprofile"]["interface"]))
				nullHeader($lang["login"][5],$_SERVER["PHP_SELF"]);
			else if ($_SESSION["glpiprofile"]["interface"]=="central")
				commonHeader($lang["login"][5],$_SERVER["PHP_SELF"]);
			else if ($_SESSION["glpiprofile"]["interface"]=="helpdesk")
				helpHeader($lang["login"][5],$_SERVER["PHP_SELF"]);
		}
		echo "<div align='center'><br><br><img src=\"".$HTMLRel."pics/warning.png\" alt=\"warning\"><br><br>";
		echo "<b>".$lang["login"][5]."</b></div>";
		nullFooter();
		exit();
	}

}


/**
* Test if an user have the right to assign a job to another user 
*
* Return true if the user with name $name is allowed to assign a job.
* Else return false.
*
*@param $name (username).
*@return boolean
*/
// TO BE DELETED
function can_assign_job($name)
{
  global $db;
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
// TO BE DELETED
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
// TO BE DELETED
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
// TO BE DELETED
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
// TO BE DELETED
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
// TO BE DELETED
function checkAuthentication($authtype) {

	// Nouvelle gestion des droits :
	// ne faire dans le checkAuth que la verif des droits -> checkRight
	// Tout le reste : secu + loadlang -> includes.php ou config.php

	// Universal method to have a magic-quote-gpc system
	global $_POST, $_GET,$_COOKIE,$tab,$cfg_glpi,$lang, $HTMLRel;
	// Clean array and addslashes
	
	// Checks a GLOBAL user and password against the database
	// If $authtype is "normal" or "admin", it checks if the user
	// has the privileges to do something. Should be used in every 
	// control-page to set a minium security level.
	
	
	
	if(!session_id()){@session_start();}
	
	if(empty($_SESSION["glpiauthorisation"])&& $authtype != "anonymous")
	{
		nullHeader("Login",$_SERVER["PHP_SELF"]);
		echo "<div align='center'><b><a href=\"".$cfg_glpi["root_doc"]."/logout.php\">Relogin</a></b></div>";
		nullFooter();
		die();	
	}

	$type="anonymous";
	if (isset($_SESSION["glpitype"]))
		$type = $_SESSION["glpitype"];	
		
	// Check username and password
	if (!isset($_SESSION["glpiname"])&& $authtype != "anonymous") {
		header("Vary: User-Agent");
		nullHeader($lang["login"][3], $_SERVER["PHP_SELF"]);
		echo "<div align='center'><b>".$lang["login"][0]."</b><br><br>";
		echo "<b><a href=\"".$cfg_glpi["root_doc"]."/logout.php\">".$lang["login"][1]."</a></b></div>";
		nullFooter();
		exit();
	} else {
		header("Vary: User-Agent");

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
    				if ($cfg_glpi["public_faq"] == 0&&!isset($_SESSION["glpiname"])){
      					nullHeader("Login",$_SERVER["PHP_SELF"]);
      					echo "<div align='center'><b><a href=\"".$cfg_glpi["root_doc"]."/logout.php\">No anonymous authorisation</a></b></div>";
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

	GLOBAL $lang,$cfg_glpi;

	if(empty($_SESSION["glpilanguage"])) {
		$file= "/glpi/dicts/".$cfg_glpi["languages"][$cfg_glpi["default_language"]][1];
	} else {
		$file = "/glpi/dicts/".$cfg_glpi["languages"][$_SESSION["glpilanguage"]][1];
	}
		include ("_relpos.php");
		include ($phproot . $file);
		
	// Debug display lang element with item
	if ($cfg_glpi["debug"]&&$cfg_glpi["debug_lang"]){
		foreach ($lang as $module => $tab)
		foreach ($tab as $num => $val){
			$lang[$module][$num].="<span style='font-size:12px; color:red;'>$module/$num</span>";
		
		}
	}

}

?>
