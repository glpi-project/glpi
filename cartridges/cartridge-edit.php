<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2004 by the INDEPNET Development Team.
 
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
*/

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_cartridges.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["tID"])) $tab["tID"] = "";
if(!isset($tab["cID"])) $tab["cID"] = "";

//print_r($_POST);
if (isset($tab["Modif_Interne"])){
	checkAuthentication("admin");
	commonHeader($lang["title"][12],$_SERVER["PHP_SELF"]);
	showLicenseForm($_SERVER["PHP_SELF"],$tab['form'],$tab["sID"],$tab["lID"],$tab['search_computer']);
	commonFooter();

}
else if (isset($_GET["add"]))
{
	
	checkAuthentication("admin");
	addCartridge($_GET["tID"]);
	logEvent($tab["tID"], "cartridge", 4, "inventory", $_SESSION["glpiname"]." added a license.");
	
	header("Location: ".$_SERVER['HTTP_REFERER']);
}
else if (isset($tab["delete"]))
{
	checkAuthentication("admin");
	deleteCartridge($tab["ID"]);
	logEvent(0, "cartridge", 4, "inventory", $_SESSION["glpiname"]." deleted a license.");
	header("Location: ".$_SERVER['HTTP_REFERER']." ");
}
else if (isset($tab["install"]))
{
	checkAuthentication("admin");
	installCartridge($tab["pID"],$tab["tID"]);
	logEvent($tab["cID"], "computers", 5, "inventory", $_SESSION["glpiname"]." installed cartridge.");
	//echo $tab["back"];
	header("Location: ".$_SERVER['HTTP_REFERER']);
}
else if (isset($tab["uninstall"]))
{
	checkAuthentication("admin");
	uninstallCartridge($tab["ID"]);
	logEvent($tab["cID"], "computers", 5, "inventory", $_SESSION["glpiname"]." uninstalled cartridge.");
	header("Location: ".$_SERVER['HTTP_REFERER']." ");
}
else if (isset($tab["back"]))
{
	
	header("Location: ".$tab["back"]." ");
}


?>
