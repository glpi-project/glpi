<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2004 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer 

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
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
 

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_reservation.php");
include ($phproot . "/glpi/includes_tracking.php");

if(!isset($_GET["ID"])) $_GET["ID"] = "";

if (isset($_POST["add"]))
{
	checkAuthentication("admin");
	addNetdevice($_POST);
	logEvent(0, "networking", 4, "inventory", $_SESSION["glpiname"]." added item name ".$_POST["name"].".");
	header("Location: $_SERVER[HTTP_REFERER]");
}
else if (isset($_POST["delete"]))
{
	checkAuthentication("admin");
	deleteNetdevice($_POST);
	logEvent($_POST["ID"], "networking", 4, "inventory", $_SESSION["glpiname"] ."deleted item.");
	header("Location: ".$cfg_install["root"]."/networking/");
}
else if (isset($_POST["update"]))
{
	checkAuthentication("admin");
	updateNetdevice($_POST);
	logEvent($_POST["ID"], "networking", 4, "inventory", $_SESSION["glpiname"]." updated item.");
	commonHeader("Networking",$_SERVER["PHP_SELF"]);
	showNetworkingForm ($_SERVER["PHP_SELF"],$_POST["ID"]);
	showJobListForItem($_SESSION["glpiname"],2,$_POST["ID"]);
	showOldJobListForItem($_SESSION["glpiname"],2,$_POST["ID"]);

	commonFooter();

}
else
{
	if (empty($_GET["ID"]))
	checkAuthentication("admin");
	else checkAuthentication("normal");

	commonHeader("Networking",$_SERVER["PHP_SELF"]);
	showNetworkingForm ($_SERVER["PHP_SELF"],$_GET["ID"]);
	showJobListForItem($_SESSION["glpiname"],2,$_GET["ID"]);
	showOldJobListForItem($_SESSION["glpiname"],2,$_GET["ID"]);

	commonFooter();
}



?>
