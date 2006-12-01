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
$NEEDED_ITEMS=array("cartridge","printer","link","document","infocom","contract","enterprise");
include ($phproot . "/inc/includes.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";

$cartype=new CartridgeType();

if (isset($_POST["add"]))
{
	checkRight("cartridge","w");

	$newID=$cartype->add($_POST);
	logEvent($newID, "cartridges", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][20]." ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
} 
else if (isset($_POST["delete"]))
{
	checkRight("cartridge","w");

	$cartype->delete($_POST);
	logEvent($tab["ID"], "cartridges", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][22]);
	glpi_header($cfg_glpi["root_doc"]."/front/cartridge.php");
}
else if (isset($_POST["restore"]))
{
	checkRight("cartridge","w");

	$cartype->restore($_POST);
	logEvent($tab["ID"], "cartridges", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][23]);
	glpi_header($cfg_glpi["root_doc"]."/front/cartridge.php");
}
else if (isset($_POST["purge"]))
{
	checkRight("cartridge","w");

	$cartype->delete($_POST,1);
	logEvent($tab["ID"], "cartridges", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][24]);
	glpi_header($cfg_glpi["root_doc"]."/front/cartridge.php");
}
else if (isset($_POST["update"]))
{
	checkRight("cartridge","w");

	$cartype->update($_POST);
	logEvent($_POST["ID"], "cartridges", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
} else if (isset($_POST["addtype"])){

	checkRight("cartridge","w");

	$cartype->addCompatibleType($_POST["tID"],$_POST["model"]);
	logEvent($tab["ID"], "cartridges", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][30]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_GET["deletetype"])){

	checkRight("cartridge","w");

	$cartype->deleteCompatibleType($_GET["ID"]);
	logEvent($tab["ID"], "cartridges", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][31]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else
{
	checkRight("cartridge","r");

	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
	}

	commonHeader($lang["title"][19],$_SERVER['PHP_SELF']);

	if ($cartype->getFromDB($tab["ID"]))
		$cartype->showOnglets($_SERVER['PHP_SELF']."?ID=".$tab["ID"], "",$_SESSION['glpi_onglet'] );

	if ($cartype->showForm($_SERVER['PHP_SELF'],$tab["ID"])) {
		if (!empty($tab['ID']))
			switch($_SESSION['glpi_onglet']){
				case -1 :	
					showCompatiblePrinters($tab["ID"]);
					showCartridgesAdd($tab["ID"]);
					showCartridges($tab["ID"]);
					showCartridges($tab["ID"],1);
					showInfocomForm($cfg_glpi["root_doc"]."/front/infocom.form.php",CARTRIDGE_TYPE,$tab["ID"],1);
					showDocumentAssociated(CARTRIDGE_TYPE,$tab["ID"]);
					showLinkOnDevice(CARTRIDGE_TYPE,$tab["ID"]);
					display_plugin_action(CARTRIDGE_TYPE,$tab["ID"],$_SESSION['glpi_onglet']);
					break;
				case 4 :
					showInfocomForm($cfg_glpi["root_doc"]."/front/infocom.form.php",CARTRIDGE_TYPE,$tab["ID"],1);
					break;

				case 5 :
					showDocumentAssociated(CARTRIDGE_TYPE,$tab["ID"]);
					break;			
				case 7 : 
					showLinkOnDevice(CARTRIDGE_TYPE,$tab["ID"]);
					break;
				case 10 :
					showNotesForm($_SERVER['PHP_SELF'],CARTRIDGE_TYPE,$tab["ID"]);
					break;
				default :
					if (!display_plugin_action(CARTRIDGE_TYPE,$tab["ID"],$_SESSION['glpi_onglet'])){
						showCompatiblePrinters($tab["ID"]);
						showCartridgesAdd($tab["ID"]);
						showCartridges($tab["ID"]);
						showCartridges($tab["ID"],1);
					}
					break;
			}
	}

	commonFooter();
}

?>
