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

$NEEDED_ITEMS = array (
	"setup",
	"auth",
	"ldap",
	"user"
);

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("config", "w");
$config = new Config();
$config_mail = new AuthMail();
$config_ldap = new AuthLDAP();

if (!isset($_GET["ID"])) $_GET["ID"]="";
if (!isset($_GET["next"])) $_GET["next"]="";

//Update CAS configuration
if (isset ($_POST["update_conf_cas"])) {
	$config->update($_POST);
	glpi_header($CFG_GLPI["root_doc"] . "/front/setup.auth.php");
}
//IMAP/POP Server add/update/delete
elseif (isset ($_POST["update_mail"])) {
	$config_mail->update($_POST);
	glpi_header($_SERVER['HTTP_REFERER']);
}
elseif (isset ($_POST["add_mail"])) {
	//If no name has been given to this configuration, then go back to the page without adding
	if ($_POST["name"] != ""){
		if ($newID=$config_mail->add($_POST)){
			glpi_header($CFG_GLPI["root_doc"] . "/front/setup.auth.php?next=extauth_mail&ID=".$newID);
		}
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
elseif (isset ($_POST["delete_mail"])) {
	$config_mail->delete($_POST);
	glpi_header($CFG_GLPI["root_doc"] . "/front/setup.auth.php?onglet=2");
}

//LDAP Server add/update/delete
elseif (isset ($_POST["update_ldap"])) {
	$config_ldap->update($_POST);
	glpi_header($_SERVER['HTTP_REFERER']);
}
elseif (isset ($_POST["add_ldap"])) {
	//If no name has been given to this configuration, then go back to the page without adding
	if ($_POST["name"] != ""){
		if ($newID=$config_ldap->add($_POST)){
			if (testLDAPConnection($newID)){
				$_SESSION["MESSAGE_AFTER_REDIRECT"] =$LANG["login"][22];
			} else{
				$_SESSION["MESSAGE_AFTER_REDIRECT"] =$LANG["login"][23];	
			}
			glpi_header($CFG_GLPI["root_doc"] . "/front/setup.auth.php?next=extauth_ldap&ID=".$newID);
		}
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
elseif (isset ($_POST["delete_ldap"])) {
	$config_ldap->delete($_POST);
	glpi_header($CFG_GLPI["root_doc"] . "/front/setup.auth.php?onglet=1");
}
elseif (isset ($_POST["test_ldap"])) {
	
	if (testLDAPConnection($_POST["ID"])){
		$_SESSION["MESSAGE_AFTER_REDIRECT"]=$LANG["login"][22];
	} else{
		$_SESSION["MESSAGE_AFTER_REDIRECT"]=$LANG["login"][23];	
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
elseif (isset ($_POST["test_mail"])) {
	
	if (test_auth_mail($_POST["imap_string"],$_POST["imap_login"],$_POST["imap_password"])){
		$_SESSION["MESSAGE_AFTER_REDIRECT"] =$LANG["login"][22];
	} else {
		$_SESSION["MESSAGE_AFTER_REDIRECT"] =$LANG["login"][23];	
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}


if (!isset ($_SESSION['glpi_authconfig'])){
	$_SESSION['glpi_authconfig'] = 1;
}
if (isset ($_GET['onglet'])){
	$_SESSION['glpi_authconfig'] = $_GET['onglet'];
}

commonHeader($LANG["title"][14], $_SERVER['PHP_SELF'],"config","extauth",$_SESSION['glpi_authconfig']);
switch ($_GET["next"]) {
	case "extauth_mail" :
		$config_mail->showForm($_SERVER['PHP_SELF'], $_GET["ID"]);
		break;
	case "extauth_ldap" :
		$config_ldap->showForm($_SERVER['PHP_SELF'], $_GET["ID"]);
		break;
	default :
		showFormExtAuthList($_SERVER['PHP_SELF']);
		break;
}

commonFooter();
?>
