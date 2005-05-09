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
 
// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_devices.php");
include ($phproot . "/glpi/includes_enterprises.php");



if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";
if(empty($tab["device_type"])) {
	 header("Location : ".$phproot . "/devices/");
	 exit();
}

if (isset($_SERVER["HTTP_REFERER"])) $REFERER=$_SERVER["HTTP_REFERER"];
if (isset($tab["referer"])) $REFERER=$tab["referer"];
if (isset($_POST["referer"])) $REFERER=$_POST["referer"];

unset($_POST["referer"]);
unset($tab["referer"]);

if (isset($_POST["add"])) {
	checkAuthentication("admin");
	addDevice($_POST);
	logEvent(0, "Devices", 4, "inventory", $_SESSION["glpiname"]." added ".$_POST["designation"].".");
	header("Location: ".$cfg_install["root"]."/devices/index.php?device_type=".$_POST["device_type"]);
	exit();
}
else if (isset($tab["delete"])) {
	checkAuthentication("admin");
	deleteDevice($tab);
	logEvent($tab["ID"], "Devices", 4, "inventory", $_SESSION["glpiname"]." deleted item.");
	header("Location: ".$cfg_install["root"]."/devices/index.php?device_type=".$tab["device_type"]);
	exit();
}
else if (isset($_POST["update"])) {
	checkAuthentication("admin");
	updateDevice($_POST);
	logEvent($_POST["ID"], "Devices", 4, "inventory", $_SESSION["glpiname"]." updated item.");
	header("Location: ".$_SERVER['HTTP_REFERER']."&referer=$REFERER");
	exit();
}
else {
	checkAuthentication("normal");
	commonHeader($lang["title"][30],$_SERVER["PHP_SELF"]);
	showDevicesForm($_SERVER["PHP_SELF"],$tab["ID"],$tab["device_type"]);
	commonFooter();
}


?>
