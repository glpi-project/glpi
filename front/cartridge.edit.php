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


$NEEDED_ITEMS=array("cartridge","infocom");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["tID"])) $tab["tID"] = "";
if(!isset($tab["cID"])) $tab["cID"] = "";

//print_r($_POST);

$cart= new Cartridge();
if (isset($_POST["update_pages"])||isset($_POST["update_pages_x"]))
{
	checkRight("cartridge","w");
	$cart->updatePages($_POST["cID"],$_POST['pages']);

	logEvent(0, "cartridges", 4, "inventory", $_SESSION["glpiname"]." update a cartridge.");

	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_GET["add"]))
{

	checkRight("cartridge","w");
	$cart->add($_GET);
	logEvent($tab["tID"], "cartridges", 4, "inventory", $_SESSION["glpiname"]." added a cartridge.");

	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["add_several"]))
{
	checkRight("cartridge","w");
	for ($i=0;$i<$_POST["to_add"];$i++){
		unset($cart->fields["ID"]);
		$cart->add($_POST);
	}
	logEvent($tab["tID"], "cartridges", 4, "inventory", $_SESSION["glpiname"]." added ".$_POST["to_add"]." cartridge.");

	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($tab["delete"]))
{
	checkRight("cartridge","w");
	$cart->delete($tab);
	logEvent(0, "cartridges", 4, "inventory", $_SESSION["glpiname"]." deleted a cartridge.");
	glpi_header($_SERVER['HTTP_REFERER']." ");
}
else if (isset($tab["restore"]))
{
	checkRight("cartridge","w");
	$cart->restore($tab);
	logEvent($tab["tID"], "cartridges", 5, "inventory", $_SESSION["glpiname"]." restore cartridge.");
	glpi_header($_SERVER['HTTP_REFERER']." ");
}
else if (isset($tab["install"]))
{
	checkRight("cartridge","w");
	$cart->install($tab["pID"],$tab["tID"]);
	logEvent($tab["tID"], "cartridges", 5, "inventory", $_SESSION["glpiname"]." installed cartridge.");

	glpi_header($cfg_glpi["root_doc"]."/front/printer.form.php?ID=".$tab["pID"]);
}
else if (isset($tab["uninstall"]))
{
	checkRight("cartridge","w");
	$cart->uninstall($tab["ID"]);
	logEvent(0, "cartridges", 5, "inventory", $_SESSION["glpiname"]." uninstalled cartridge.");
	glpi_header($_SERVER['HTTP_REFERER']." ");
}
else if (isset($tab["back"]))
{
	glpi_header($tab["back"]." ");
}


?>
