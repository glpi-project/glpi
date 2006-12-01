<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

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

include ("_relpos.php");

$NEEDED_ITEMS=array("state","user","computer","printer","monitor","peripheral","networking","phone");
include ($phproot . "/inc/includes.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";

if(!isset($_GET["start"])) $_GET["start"] = 0;
if (!isset($_GET["order"])) $_GET["order"] = "ASC";
if (!isset($_GET["field"])) $_GET["field"] = "glpi_state_item.ID";
if (!isset($_GET["phrasetype"])) $_GET["phrasetype"] = "contains";
if (!isset($_GET["contains"])) $_GET["contains"] = "";
if (!isset($_GET["sort"])) $_GET["sort"] = "glpi_dropdown_state.name";
if (!isset($_GET["state"])) $_GET["state"] = "";
if (!isset($_GET["synthese"])) $_GET["synthese"] = "no";

if (isset($tab["deletestate"])) {
	checkTypeRight($tab["device_type"],"w");
	updateState($tab["device_type"],$tab["device_id"],0);

	logEvent(0, "state", 4, "state", $_SESSION["glpiname"]." delete state.");
}

checkCentralAccess();

commonHeader($lang["title"][9],$_SERVER['PHP_SELF']);

titleState();

if ($_GET["synthese"]=="yes")
showStateSummary($_SERVER['PHP_SELF']);
else {
	searchFormStateItem($_GET["field"],$_GET["phrasetype"],$_GET["contains"],$_GET["sort"],$_GET["state"]);
	showStateItemList($_SERVER['PHP_SELF'],$_SESSION["glpiname"],$_GET["field"],$_GET["phrasetype"],$_GET["contains"],$_GET["sort"],$_GET["order"],$_GET["start"],$_GET["state"]);
}

commonFooter();
?>
