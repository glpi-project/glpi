<?php
/*
 * @version $Id$
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


$NEEDED_ITEMS=array("device","enterprise");
define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");



if(!isset($_GET["ID"])) $_GET["ID"] = "";

if (isset($_SERVER['HTTP_REFERER'])) $REFERER=$_SERVER['HTTP_REFERER'];
if (isset($_GET["referer"])) $REFERER=$_GET["referer"];
else if (isset($_POST["referer"])) {
	$REFERER=$_POST["referer"];
	unset($_POST["referer"]);
}

$REFERER=preg_replace("/&/","&amp;",$REFERER);

if (!isset($_SESSION['glpi_tab'])) $_SESSION['glpi_tab']=1;
if (isset($_GET['onglet'])) {
	$_SESSION['glpi_tab']=$_GET['onglet'];
}


checkRight("device","w");

if (isset($_POST["add"])) {
	$device=new Device($_POST["device_type"]);	
	$newID=$device->add($_POST);

	logEvent(0, "devices", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][20]." ".$_POST["designation"].".");
	glpi_header($CFG_GLPI["root_doc"]."/front/device.php?device_type=".$_POST["device_type"]);
}
else if (isset($_POST["delete"])) {
	$device=new Device($_POST["device_type"]);	
	$device->delete($_POST);
	logEvent($_POST["ID"], "devices", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][22]);
	glpi_header($CFG_GLPI["root_doc"]."/front/device.php?device_type=".$_POST["device_type"]);
}
else if (isset($_POST["update"])) {
	$device=new Device($_POST["device_type"]);	
	$device->update($_POST);
	logEvent($_POST["ID"], "devices", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']."&referer=$REFERER");
}
else {

	commonHeader($LANG["title"][30],$_SERVER['PHP_SELF'],"config","device");
	showDevicesForm($_SERVER['PHP_SELF'],$_GET["ID"],$_GET["device_type"]);
	commonFooter();
}


?>
