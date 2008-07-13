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



$NEEDED_ITEMS=array("computer");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");


if(!isset($_GET["ID"])) $_GET["ID"] = "";
if(!isset($_GET["cID"])) $_GET["cID"] = "";

$disk=new ComputerDisk();
if (isset($_POST["add"]))
{
	checkRight("computer","w");

	if ($newID=$disk->add($_POST)){
		logEvent($_POST['FK_computers'], "computer", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][82]." $newID.");
		glpi_header($CFG_GLPI["root_doc"]."/front/computer.form.php?ID=".$disk->fields['FK_computers']);
	} else {
		glpi_header($_SERVER['HTTP_REFERER']);
	}	
}
else if (isset($_POST["delete"]))
{
	checkRight("computer","w");

	$disk->delete($_POST);

	logEvent($disk->fields['FK_computers'], "computer", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][84]." ".$_POST["ID"]);
	glpi_header($CFG_GLPI["root_doc"]."/front/computer.form.php?ID=".$disk->fields['FK_computers']);
}
else if (isset($_POST["update"]))
{
	checkRight("computer","w");

	$disk->update($_POST);
	logEvent($disk->fields['FK_computers'], "computer", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][83]." ".$_POST["ID"]);
	glpi_header($CFG_GLPI["root_doc"]."/front/computer.form.php?ID=".$disk->fields['FK_computers']);
} 
else
{
	checkRight("computer","r");

	commonHeader($LANG["Menu"][0],$_SERVER['PHP_SELF'],"inventory","computer");
	$disk->showForm($_SERVER['PHP_SELF'],$_GET["ID"],$_GET["cID"]);

	commonFooter();
}

?>
