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
 
// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
$NEEDED_ITEMS=array("device","enterprise");
include ($phproot . "/inc/includes.php");



if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";
if(empty($tab["device_type"])) {
	 glpi_header($cfg_glpi["root_doc"] . "/devices/");
}

if (isset($_SERVER["HTTP_REFERER"])) $REFERER=$_SERVER["HTTP_REFERER"];
if (isset($tab["referer"])) $REFERER=$tab["referer"];
if (isset($_POST["referer"])) $REFERER=$_POST["referer"];

$REFERER=preg_replace("/&/","&amp;",$REFERER);

unset($_POST["referer"]);
unset($tab["referer"]);

checkRight("device","w");

if (isset($_POST["add"])) {
	
	addDevice($_POST);
	logEvent(0, "devices", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][21]." ".$_POST["designation"].".");
	glpi_header($cfg_glpi["root_doc"]."/devices/index.php?device_type=".$_POST["device_type"]);
}
else if (isset($tab["delete"])) {

	deleteDevice($tab);
	logEvent($tab["ID"], "devices", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][22]);
	glpi_header($cfg_glpi["root_doc"]."/devices/index.php?device_type=".$tab["device_type"]);
}
else if (isset($_POST["update"])) {

	updateDevice($_POST);
	logEvent($_POST["ID"], "devices", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']."&referer=$REFERER");
}
else {

	commonHeader($lang["title"][30],$_SERVER["PHP_SELF"]);
	showDevicesForm($_SERVER["PHP_SELF"],$tab["ID"],$tab["device_type"]);
	commonFooter();
}


?>
