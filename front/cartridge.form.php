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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------


$NEEDED_ITEMS=array("cartridge","printer","link","document","infocom","contract","enterprise");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(!isset($_GET["ID"])) $_GET["ID"] = "";

$cartype=new CartridgeType();

if (isset($_POST["add"]))
{
	checkRight("cartridge","w");

	$newID=$cartype->add($_POST);
	logEvent($newID, "cartridges", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][20]." ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
} 
else if (isset($_POST["delete"]))
{
	checkRight("cartridge","w");

	$cartype->delete($_POST);
	logEvent($_POST["ID"], "cartridges", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][22]);
	glpi_header($CFG_GLPI["root_doc"]."/front/cartridge.php");
}
else if (isset($_POST["restore"]))
{
	checkRight("cartridge","w");

	$cartype->restore($_POST);
	logEvent($_POST["ID"], "cartridges", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][23]);
	glpi_header($CFG_GLPI["root_doc"]."/front/cartridge.php");
}
else if (isset($_POST["purge"]))
{
	checkRight("cartridge","w");

	$cartype->delete($_POST,1);
	logEvent($_POST["ID"], "cartridges", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][24]);
	glpi_header($CFG_GLPI["root_doc"]."/front/cartridge.php");
}
else if (isset($_POST["update"]))
{
	checkRight("cartridge","w");

	$cartype->update($_POST);
	logEvent($_POST["ID"], "cartridges", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
} else if (isset($_POST["addtype"])){

	checkRight("cartridge","w");

	$cartype->addCompatibleType($_POST["tID"],$_POST["model"]);
	logEvent($_POST["tID"], "cartridges", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][30]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_GET["deletetype"])){

	checkRight("cartridge","w");

	$cartype->deleteCompatibleType($_GET["ID"]);
	logEvent($_POST["ID"], "cartridges", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][31]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else
{
	checkRight("cartridge","r");

	commonHeader($LANG["Menu"][21],$_SERVER['PHP_SELF'],"inventory","cartridge");
	$cartype->showForm($_SERVER['PHP_SELF'],$_GET["ID"]);
	commonFooter();
}

?>
