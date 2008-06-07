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



$NEEDED_ITEMS=array("software","infocom");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");


if(!isset($_GET["ID"])) $_GET["ID"] = "";
if(!isset($_GET["sID"])) $_GET["sID"] = "";

$version=new SoftwareLicense();
if (isset($_POST["add"]))
{
	checkRight("software","w");

	$newID=$version->add($_POST);
	logEvent($_POST['sID'], "software", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][85]." $newID.");
	glpi_header($CFG_GLPI["root_doc"]."/front/software.form.php?ID=".$version->fields['sID']);
}
else if (isset($_POST["delete"]))
{
	checkRight("software","w");

	$version->delete($_POST);

	logEvent($version->fields['sID'], "software", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][87]." ".$_POST["ID"]);
	glpi_header($CFG_GLPI["root_doc"]."/front/software.form.php?ID=".$version->fields['sID']);
}
else if (isset($_POST["update"]))
{
	checkRight("software","w");

	$version->update($_POST);
	logEvent($version->fields['sID'], "software", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][86]." ".$_POST["ID"]);
	glpi_header($CFG_GLPI["root_doc"]."/front/software.form.php?ID=".$version->fields['sID']);
} 
else
{
	checkRight("software","r");

	commonHeader($LANG["Menu"][4],$_SERVER['PHP_SELF'],"inventory","software");
	$version->showForm($_SERVER['PHP_SELF'],$_GET["ID"],$_GET["sID"]);

	commonFooter();
}

?>
