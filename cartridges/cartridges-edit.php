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
include ($phproot . "/glpi/includes_cartridges.php");
include ($phproot . "/glpi/includes_financial.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["tID"])) $tab["tID"] = "";
if(!isset($tab["cID"])) $tab["cID"] = "";

if (isset($_POST["update_pages"]))
{
	checkAuthentication("admin");
	updateCartridgePages($_POST["cID"],$_POST['pages']);
	
	logEvent(0, "cartridges", 4, "inventory", $_SESSION["glpiname"]." update a cartridge.");
	
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_GET["add"]))
{
	
	checkAuthentication("admin");
	addCartridge($_GET["tID"]);
	logEvent($tab["tID"], "cartridges", 4, "inventory", $_SESSION["glpiname"]." added a cartridge.");
	
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["add_several"]))
{
	
	checkAuthentication("admin");
	for ($i=0;$i<$_POST["to_add"];$i++)
		addCartridge($_POST["tID"]);
	logEvent($tab["tID"], "cartridges", 4, "inventory", $_SESSION["glpiname"]." added ".$_POST["to_add"]." cartridge.");
	
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($tab["delete"]))
{
	checkAuthentication("admin");
	deleteCartridge($tab["ID"]);
	logEvent(0, "cartridges", 4, "inventory", $_SESSION["glpiname"]." deleted a cartridge.");
	glpi_header($_SERVER['HTTP_REFERER']." ");
}
else if (isset($tab["install"]))
{
	checkAuthentication("admin");
	installCartridge($tab["pID"],$tab["tID"]);
	logEvent($tab["tID"], "cartridges", 5, "inventory", $_SESSION["glpiname"]." installed cartridge.");
	//echo $tab["back"];
	glpi_header($cfg_install["root"]."/printers/printers-info-form.php?ID=".$tab["pID"]);
}
else if (isset($tab["uninstall"]))
{
	checkAuthentication("admin");
	uninstallCartridge($tab["ID"]);
	logEvent(0, "cartridges", 5, "inventory", $_SESSION["glpiname"]." uninstalled cartridge.");
	glpi_header($_SERVER['HTTP_REFERER']." ");
}
else if (isset($tab["back"]))
{
	
	glpi_header($tab["back"]." ");
}


?>
