<?php


/*
 * @version $Id: setup.auth.php 7326 2008-09-23 10:18:17Z tsmr $
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

//IMAP/POP Server add/update/delete
if (isset ($_POST["update_mail"])) {
	$config_mail->update($_POST);
	glpi_header($_SERVER['HTTP_REFERER']);
}
elseif (isset ($_POST["add_mail"])) {
	//If no name has been given to this configuration, then go back to the page without adding
	if ($_POST["name"] != ""){
		if ($newID=$config_mail->add($_POST)){
			glpi_header($CFG_GLPI["root_doc"] . "/front/auth.imap.php?next=extauth_mail&ID=".$newID);
		}
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
elseif (isset ($_POST["delete_mail"])) {
	$config_mail->delete($_POST);
	$_SESSION['glpi_authconfig']=2;
	glpi_header($CFG_GLPI["root_doc"] . "/front/auth.imap.php");

}elseif (isset ($_POST["test_mail"])) {
	
	if (test_auth_mail($_POST["imap_string"],$_POST["imap_login"],$_POST["imap_password"])){
		addMessageAfterRedirect($LANG["login"][22]);
	} else {
		addMessageAfterRedirect($LANG["login"][23]);	
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}

if (!isset ($_SESSION['glpi_authconfig'])){
	$_SESSION['glpi_authconfig'] = 2;
}
if (isset ($_GET['onglet'])){
	$_SESSION['glpi_authconfig'] = $_GET['onglet'];
}

if (!isset($_GET["ID"])){
	$_GET["ID"]="";	
}

if (!isset($_GET["next"])){
	$_GET["next"]="";	
}

commonHeader($LANG["title"][14], $_SERVER['PHP_SELF'],"config","extauth","imap");
	
switch($_GET['next']){
	case "extauth_mail" :
		$_SESSION['glpi_authconfig']=2;
		$config_mail->showForm($_SERVER['PHP_SELF'], $_GET["ID"]);
		break;
	default :
		$_SESSION['glpi_authconfig']=2;
		showFormExtAuthList($_SERVER['PHP_SELF']);
		break;
}

commonFooter();
?>
