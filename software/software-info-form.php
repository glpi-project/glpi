<?php
/*
 
 ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer, turin@incubus.de 

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
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------


*/
 

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_software.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;

if (isset($_POST["add"]))
{
	checkAuthentication("admin");
	addSoftware($_POST);
	logEvent(0, "software", 4, "inventory", $_SESSION["glpiname"]." added item ".$_POST["name"].".");
	header("Location: ".$cfg_install["root"]."/software/");
} 
else if (isset($_POST["delete"]))
{
	checkAuthentication("admin");
	deleteSoftware($_POST);
	logEvent($tab["ID"], "software", 4, "inventory", $_SESSION["glpiname"]." deleted item.");
	header("Location: ".$cfg_install["root"]."/software/");
}
else if (isset($_POST["update"]))
{
	checkAuthentication("admin");
	updateSoftware($_POST);
	logEvent($_POST["ID"], "software", 4, "inventory", $_SESSION["glpiname"]." updated item.");
	commonHeader("Software",$_SERVER["PHP_SELF"]);
	showSoftwareForm($_SERVER["PHP_SELF"],$_POST["ID"]);
	commonFooter();

} 
else
{
	checkAuthentication("normal");
	commonHeader("Software",$_SERVER["PHP_SELF"]);
	showSoftwareForm($_SERVER["PHP_SELF"],$tab["ID"]);
	commonFooter();
}

?>
