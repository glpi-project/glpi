<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2004 by the INDEPNET Development Team.
 
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
*/

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_software.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_tracking.php");


if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";

if (isset($_POST["add"]))
{
	checkAuthentication("admin");
	unset($_POST["search_software"]);

	addSoftware($_POST);
	logEvent(0, "software", 4, "inventory", $_SESSION["glpiname"]." added item ".$_POST["name"].".");
	header("Location: ".$_SERVER['HTTP_REFERER']);
} 
else if (isset($_POST["delete"]))
{
	checkAuthentication("admin");
	deleteSoftware($_POST);
	logEvent($tab["ID"], "software", 4, "inventory", $_SESSION["glpiname"]." deleted item.");
	header("Location: ".$cfg_install["root"]."/software/");
}
else if (isset($_POST["update"]))
{
	checkAuthentication("admin");
	unset($_POST["search_software"]);
	updateSoftware($_POST);
	logEvent($_POST["ID"], "software", 4, "inventory", $_SESSION["glpiname"]." updated item.");
	commonHeader($lang["title"][12],$_SERVER["PHP_SELF"]);
	showSoftwareForm($_SERVER["PHP_SELF"],$_POST["ID"]);
	showJobListForItem($_SESSION["glpiname"],SOFTWARE_TYPE,$_POST["ID"]);
	showOldJobListForItem($_SESSION["glpiname"],SOFTWARE_TYPE,$_POST["ID"]);

	commonFooter();

} 
else if (isset($tab["Modif_Interne"])){
	checkAuthentication("admin");
	commonHeader($lang["title"][12],$_SERVER["PHP_SELF"]);
	showSoftwareForm($_SERVER["PHP_SELF"],$tab["ID"],$tab['search_software']);
	showJobListForItem($_SESSION["glpiname"],SOFTWARE_TYPE,$tab["ID"]);
	showOldJobListForItem($_SESSION["glpiname"],SOFTWARE_TYPE,$tab["ID"]);

	commonFooter();

}
else
{
	if (empty($tab["ID"]))
	checkAuthentication("admin");
	else checkAuthentication("normal");

	commonHeader($lang["title"][12],$_SERVER["PHP_SELF"]);
	showSoftwareForm($_SERVER["PHP_SELF"],$tab["ID"]);
	if (!empty($_GET["ID"])){
	showJobListForItem($_SESSION["glpiname"],SOFTWARE_TYPE,$tab["ID"]);
	showOldJobListForItem($_SESSION["glpiname"],SOFTWARE_TYPE,$tab["ID"]);
	}

	commonFooter();
}

?>
