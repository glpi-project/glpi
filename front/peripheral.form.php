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


$NEEDED_ITEMS=array("peripheral","infocom","contract","user","group","link","networking","document","tracking","reservation","computer","enterprise","ocsng");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(empty($_GET["ID"])) $_GET["ID"] = "";
if(!isset($_GET["sort"])) $_GET["sort"] = "";
if(!isset($_GET["order"])) $_GET["order"] = "";

if(!isset($_GET["withtemplate"])) $_GET["withtemplate"] = "";

$peripheral=new Peripheral();

if (isset($_POST["add"]))
{
	checkRight("peripheral","w");

	$newID=$peripheral->add($_POST);
	logEvent($newID, "peripherals", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][20]." ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["delete"]))
{
	checkRight("peripheral","w");

	if (!empty($_POST["withtemplate"]))
		$peripheral->delete($_POST,1);
	else $peripheral->delete($_POST);

	logEvent($_POST["ID"], "peripherals", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][22]);
	if(!empty($_POST["withtemplate"])) 
		glpi_header($CFG_GLPI["root_doc"]."/front/setup.templates.php");
	else 
		glpi_header($CFG_GLPI["root_doc"]."/front/peripheral.php");
}
else if (isset($_POST["restore"]))
{
	checkRight("peripheral","w");

	$peripheral->restore($_POST);
	logEvent($_POST["ID"], "peripherals", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][23]);
	glpi_header($CFG_GLPI["root_doc"]."/front/peripheral.php");
}
else if (isset($_POST["purge"]) || isset($_GET["purge"]))
{
	checkRight("peripheral","w");

	if (isset($_POST["purge"]))
		$input["ID"]=$_POST["ID"];
	else
		$input["ID"] = $_GET["ID"];	

	$peripheral->delete($input,1);
	logEvent($input["ID"], "peripherals", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][24]);
	glpi_header($CFG_GLPI["root_doc"]."/front/peripheral.php");
}
else if (isset($_POST["update"]))
{
	checkRight("peripheral","w");

	$peripheral->update($_POST);
	logEvent($_POST["ID"], "peripherals", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_GET["unglobalize"]))
{
	checkRight("peripheral","w");

	unglobalizeDevice(PERIPHERAL_TYPE,$_GET["ID"]);
	logEvent($_GET["ID"], "peripherals", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][60]);
	glpi_header($CFG_GLPI["root_doc"]."/front/peripheral.form.php?ID=".$_GET["ID"]);
}
else if (isset($_GET["disconnect"]))
{
	checkRight("peripheral","w");
	Disconnect($_GET["ID"]);
	logEvent(0, "peripherals", 5, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][27]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if(isset($_POST["connect"])&&isset($_POST["item"])&&$_POST["item"]>0)
{

	checkRight("peripheral","w");

	Connect($_POST["sID"],$_POST["item"],PERIPHERAL_TYPE);
	logEvent($_POST["sID"], "peripherals", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][26]);
	glpi_header($CFG_GLPI["root_doc"]."/front/peripheral.form.php?ID=".$_POST["sID"]);
}
else
{
	checkRight("peripheral","r");

	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
		//		glpi_header($_SERVER['HTTP_REFERER']);
	}


	commonHeader($LANG["Menu"][16],$_SERVER['PHP_SELF'],"inventory","peripheral");

	$peripheral->showForm($_SERVER['PHP_SELF'],$_GET["ID"], $_GET["withtemplate"]);
	commonFooter();
}


?>
