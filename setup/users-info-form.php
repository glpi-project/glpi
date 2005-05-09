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

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_setup.php");


if(empty($_GET["name"])) $_GET["name"] = "";
if (isset($_POST["add"])) {
	checkAuthentication("admin");
	// Pas de nom pas d'ajout	
	if (!empty($_POST["name"])){
		addUser($_POST);
		logEvent(0, "users", 4, "setup", $_SESSION["glpiname"]." added user ".$_POST["name"].".");
	}
	header("Location: ".$_SERVER['HTTP_REFERER']);
	exit();
} else if (isset($_POST["delete"])) {
	checkAuthentication("admin");
	deleteUser($_POST);
	logEvent(0,"users", 4, "setup", $_SESSION["glpiname"]." deleted user ".$_POST["name"].".");
	header("Location: ".$cfg_install["root"]."/setup/setup-users.php");
	exit();
} else if (isset($_POST["update"])) {
	checkAuthentication("admin");
	commonHeader($lang["title"][13],$_SERVER["PHP_SELF"]);
	updateUser($_POST);
	logEvent(0,"users", 5, "setup", $_SESSION["glpiname"]." updated user ".$_POST["name"].".");
	showUserform($_SERVER["PHP_SELF"],$_POST["name"]);
} else {

checkAuthentication("admin");
commonHeader($lang["title"][13],$_SERVER["PHP_SELF"]);
showUserform($_SERVER["PHP_SELF"],$_GET["name"]);
}
	


commonFooter();

?>
