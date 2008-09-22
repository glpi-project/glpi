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

$NEEDED_ITEMS=array("transfer");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("transfer","r");

if(empty($_GET["ID"])) $_GET["ID"] = "";

$transfer=new Transfer();

if (isset($_POST["add"]))
{
	$transfer->check(-1,'w');

	$newID=$transfer->add($_POST);
	logEvent($newID, "transfers", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][20]." ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["delete"]))
{
	$transfer->check($_POST["ID"],'w');

	$transfer->delete($_POST);
	logEvent($_POST["ID"], "transfers", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][22]);
	glpi_header($CFG_GLPI["root_doc"]."/front/transfer.php");
}else if (isset($_POST["update"]))
{
	$transfer->check($_POST["ID"],'w');

	$transfer->update($_POST);
	logEvent($_POST["ID"], "transfers", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
}

commonHeader($LANG["transfer"][1],$_SERVER['PHP_SELF'],"admin","transfer");

if (!isset($_SESSION['glpi_tab'])||$_SESSION['glpi_tab']==-1) $_SESSION['glpi_tab']=1;
if (isset($_GET['onglet'])) {
	$_SESSION['glpi_tab']=$_GET['onglet'];
}

$transfer->showTabs($_GET["ID"], '',$_SESSION['glpi_tab']);
$transfer->showForm($_SERVER['PHP_SELF'],$_GET["ID"]);

commonFooter();
?>