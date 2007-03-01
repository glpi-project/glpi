<?php


/*
 * @version $Id: setup.config.php 4050 2006-10-27 15:32:57Z moyo $
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
 
if (!empty ($_GET["next"])) {
	commonHeader($LANG["title"][14], $_SERVER['PHP_SELF'],"config","extauth");
	titleExtAuth();
	if ($_GET["next"] == "extauth") {
		showFormExtAuthList($_SERVER['PHP_SELF']);
	}
	if ($_GET["next"] == "extauth_mail") {
		$config_mail->showForm($_SERVER['PHP_SELF'], $_GET["ID"]);
	}
	if ($_GET["next"] == "extauth_ldap") {
		$config_ldap->showForm($_SERVER['PHP_SELF'], $_GET["ID"]);
	}

}

//Update CAS configuration
elseif (isset ($_POST["update_conf_cas"])) {
	$config->update($_POST);
	glpi_header($CFG_GLPI["root_doc"] . "/front/setup.auth.php?next=extauth");
}
//IMAP/POP Server add/update/delete
elseif (isset ($_POST["update_mail"])) {
	$config_mail->update($_POST);
	glpi_header($_SERVER['HTTP_REFERER']);
}
elseif (isset ($_POST["add_mail"])) {
	//If no name has been given to this configuration, then go back to the page without adding
	if ($_POST["name"] != "")
		$config_mail->add($_POST);
	glpi_header($_SERVER['HTTP_REFERER']);
}
elseif (isset ($_POST["delete_mail"])) {
	$config_mail->delete($_POST);
	glpi_header($CFG_GLPI["root_doc"] . "/front/setup.auth.php?next=extauth");
}

//LDAP Server add/update/delete
elseif (isset ($_POST["update_ldap"])) {
	$config_ldap->update($_POST);
	glpi_header($_SERVER['HTTP_REFERER']);
}
elseif (isset ($_POST["add_ldap"])) {
	//If no name has been given to this configuration, then go back to the page without adding
	if ($_POST["name"] != "")
		$config_ldap->add($_POST);
	glpi_header($_SERVER['HTTP_REFERER']);
}
elseif (isset ($_POST["delete_ldap"])) {
	$config_ldap->delete($_POST);
	glpi_header($CFG_GLPI["root_doc"] . "/front/setup.auth.php?next=extauth");
}
elseif (isset ($_POST["test_ldap"])) {
	
	//Testing ldap connection
	commonHeader($LANG["title"][14], $_SERVER['PHP_SELF'],"config");
	if (testLDAPConnection($_POST["ID"]))
		$msg =$LANG["login"][22];
	else
		$msg =$LANG["login"][23];	
	
	//Display a message and a back link
	echo "<div align='center'><strong>".$msg."<br>";
	echo "<a href='".$_SERVER['HTTP_REFERER']."'>".$LANG["buttons"][13]."</a>";
	echo "</strong></div>";	
}
elseif (isset ($_POST["test_mail"])) {
	
	//Testing ldap connection
	commonHeader($LANG["title"][14], $_SERVER['PHP_SELF'],"admin");
	if (test_auth_mail($_POST["imap_string"],$_POST["imap_login"],$_POST["imap_password"]))
		$msg =$LANG["login"][22];
	else
		$msg =$LANG["login"][23];	
	
	//Display a message and a back link
	echo "<div align='center'><strong>".$msg."<br>";
	echo "<a href='".$_SERVER['HTTP_REFERER']."'>".$LANG["buttons"][13]."</a>";
	echo "</strong></div>";	
}

commonFooter();
?>
