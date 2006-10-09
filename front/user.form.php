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

include ("_relpos.php");

$NEEDED_ITEMS=array("user","profile","group","setup");
include ($phproot . "/inc/includes.php");

if(empty($_GET["ID"])) $_GET["ID"] = "";


$user=new User();
if (empty($_GET["ID"])&&isset($_GET["name"])){

	$user->getFromDBbyName($_GET["name"]);
	glpi_header($cfg_glpi["root_doc"]."/front/user.form.php?ID=".$user->fields['ID']);
}

if(empty($_GET["name"])) $_GET["name"] = "";

if (isset($_POST["add"])) {
	checkRight("user","w");

	// Pas de nom pas d'ajout	
	if (!empty($_POST["name"])){
		$newID=$user->add($_POST);
		logEvent($newID, "users", 4, "setup", $_SESSION["glpiname"]." ".$lang["log"][20]." ".$_POST["name"].".");
	}
	glpi_header($_SERVER['HTTP_REFERER']);
} else if (isset($_POST["delete"])) {
	checkRight("user","w");

	$user->delete($_POST);
	logEvent(0,"users", 4, "setup", $_SESSION["glpiname"]."  ".$lang["log"][22]." ".$_POST["ID"].".");
	glpi_header($cfg_glpi["root_doc"]."/front/user.php");
} else if (isset($_POST["update"])) {
	checkRight("user","w");

	$user->update($_POST);
	logEvent(0,"users", 5, "setup", $_SESSION["glpiname"]."  ".$lang["log"][21]."  ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
} else if (isset($_POST["addgroup"]))
{
	checkRight("user","w");

	addUserGroup($_POST["FK_users"],$_POST["FK_groups"]);

	logEvent($_POST["FK_users"], "users", 4, "setup", $_SESSION["glpiname"]." ".$lang["log"][48]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["deletegroup"]))
{
	checkRight("user","w");
	if (count($_POST["item"]))
		foreach ($_POST["item"] as $key => $val)
			deleteUserGroup($key);

	logEvent($_POST["FK_users"], "users", 4, "setup", $_SESSION["glpiname"]." ".$lang["log"][49]);
	glpi_header($_SERVER['HTTP_REFERER']);
} else {


	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
	}


	if (!isset($_GET["ext_auth"])){
		checkRight("user","r");

		commonHeader($lang["title"][13],$_SERVER["PHP_SELF"]);

		if ($user->getFromDB($_GET["ID"]))
			$user->showOnglets($_SERVER["PHP_SELF"]."?ID=".$_GET["ID"], "",$_SESSION['glpi_onglet'] );


		if ($user->showForm($_SERVER["PHP_SELF"],$_GET["ID"])){
			if (!empty($_GET["ID"]))
			switch($_SESSION['glpi_onglet']){
				case -1:
					showGroupAssociated($_SERVER["PHP_SELF"],$_GET["ID"]);
					showDeviceUser($_GET["ID"]);
					display_plugin_action(USER_TYPE,$_GET["ID"],$_SESSION['glpi_onglet']);
					break;
				case 1 :
					showGroupAssociated($_SERVER["PHP_SELF"],$_GET["ID"]);
					break;
				case 2 :
					showDeviceUser($_GET["ID"]);
					break;
				default : 
					if (!display_plugin_action(USER_TYPE,$_GET["ID"],$_SESSION['glpi_onglet']))
						showGroupAssociated($_SERVER["PHP_SELF"],$_GET["ID"]);
					break;
			}
			
		}
		commonFooter();
	} else {
		if (isset($_GET['add_ext_auth'])){
			if (isset($_GET['login'])&&!empty($_GET['login'])){

				// LDAP case : get all informations
				if (!empty($cfg_glpi["ldap_host"])&&!empty($cfg_glpi["ldap_rootdn"])){
					$succeded=false;
					$identificat = new Identification();
					$found_dn=$identificat->ldap_get_dn($cfg_glpi["ldap_host"],$cfg_glpi["ldap_basedn"],utf8_decode($_GET['login']),$cfg_glpi["ldap_rootdn"],$cfg_glpi["ldap_pass"],$cfg_glpi["ldap_port"]);
					if ($found_dn&&!$identificat->user->getFromDBbyName($_GET['login'])){
						$identificat->user->getFromLDAP($cfg_glpi["ldap_host"],$cfg_glpi["ldap_port"],$found_dn,$cfg_glpi["ldap_rootdn"],$cfg_glpi["ldap_pass"],$cfg_glpi['ldap_fields'],utf8_decode($_GET['login']));
						$identificat->user->fields["_extauth"]=1;
						$input=$identificat->user->fields;
						unset($identificat->user->fields);
						$identificat->user->add($input);
						$succeded=true;
					}
					// AD case
					if (!$succeded) {
						$found_dn=false;
						$found_dn=$identificat->ldap_get_dn_active_directory($cfg_glpi["ldap_host"],$cfg_glpi["ldap_basedn"],$_POST['login_name'],$cfg_glpi["ldap_rootdn"],$cfg_glpi["ldap_pass"],$cfg_glpi["ldap_port"]);
						if ($found_dn!=false&&!$identificat->user->getFromDBbyName($_GET['login'])){ 
							$identificat->user->getFromLDAP_active_directory($cfg_glpi["ldap_host"],$cfg_glpi["ldap_port"],$found_dn,$cfg_glpi["ldap_rootdn"],$cfg_glpi["ldap_pass"],$cfg_glpi['ldap_fields'],utf8_decode($_GET['login']));
							$identificat->user->fields["_extauth"]=1;
							$input=$identificat->user->fields;
							unset($identificat->user->fields);
							$identificat->user->add($input);
							$succeded=true;
						}
					}
				} else {
					$user=new User();
					$input["name"]=$_GET['login'];
					$input["_extauth"]=1;
					$user->add($input);
				}
			}
			glpi_header($_SERVER['HTTP_REFERER']);
		}
		checkRight("user","w");
		commonHeader($lang["title"][13],$_SERVER["PHP_SELF"]);
		showAddExtAuthUserForm($_SERVER["PHP_SELF"]);
		commonFooter();
	}
}





?>
