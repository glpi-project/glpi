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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
$NEEDED_ITEMS=array("infocom","computer","printer","monitor","peripheral","networking","software","cartridge","consumable","phone");
include ($phproot . "/inc/includes.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";
if(!isset($tab["search"])) $tab["search"] = "";

$ic=new Infocom();

if (isset($_GET["add"]))
{
	checkRight("contract_infocom","w");

	$newID=$ic->add($_GET);
	logEvent($newID, "infocom", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][20]);
	glpi_header($_SERVER['HTTP_REFERER']);
} 
else if (isset($_POST["delete"]))
{
	checkRight("contract_infocom","w");

	$ic->delete($_POST);
	logEvent($tab["ID"], "infocom", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][22]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["update"]))
{
	checkRight("contract_infocom","w");

	$ic->update($_POST);
	logEvent($_POST["ID"], "infocom", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
} 
else
{
	checkRight("contract_infocom","r");

	commonHeader($lang["title"][21],$_SERVER['PHP_SELF']);
	showInfocomForm($_SERVER['PHP_SELF'],$tab["ID"],$tab["search"]);

	commonFooter();
}

?>
