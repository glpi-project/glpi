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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_financial.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_monitors.php");
include ($phproot . "/glpi/includes_peripherals.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_software.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";
if(!isset($tab["search"])) $tab["search"] = "";

if (isset($_GET["add"]))
{
	checkAuthentication("admin");

	addInfocom($_GET);
	logEvent(0, "infocom", 4, "financial", $_SESSION["glpiname"]." added infocoms.");
	header("Location: ".$_SERVER['HTTP_REFERER']);
} 
else if (isset($_POST["delete"]))
{
	checkAuthentication("admin");
	deleteInfocom($_POST);
	logEvent($tab["ID"], "infocom", 4, "financial", $_SESSION["glpiname"]." deleted item.");
	header("Location: ".$_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["update"]))
{
	checkAuthentication("admin");
	updateInfocom($_POST);
	logEvent($_POST["ID"], "infocom", 4, "financial", $_SESSION["glpiname"]." updated item.");
	header("Location: ".$_SERVER['HTTP_REFERER']);

} 
else
{
	if (empty($tab["ID"]))
	checkAuthentication("admin");
	else checkAuthentication("normal");

	commonHeader($lang["title"][21],$_SERVER["PHP_SELF"]);
	showInfocomForm($_SERVER["PHP_SELF"],$tab["ID"],$tab["search"]);

	commonFooter();
}

?>
