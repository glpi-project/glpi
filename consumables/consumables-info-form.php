<?php
/*
 * @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
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
include ($phproot . "/glpi/includes_consumables.php");
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_financial.php");
include ($phproot . "/glpi/includes_links.php");
include ($phproot . "/glpi/includes_documents.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";

if (isset($_POST["add"]))
{
	checkAuthentication("admin");

	$newID=addConsumableType($_POST);
	logEvent($newID, "consumables", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][20]." ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
} 
else if (isset($_POST["delete"]))
{
	checkAuthentication("admin");
	deleteConsumableType($_POST);
	logEvent($tab["ID"], "consumables", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][22]);
	glpi_header($cfg_install["root"]."/consumables/");
}
else if (isset($_POST["restore"]))
{
	checkAuthentication("admin");
	restoreConsumableType($_POST);
	logEvent($tab["ID"], "consumables", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][23]);
	glpi_header($cfg_install["root"]."/consumables/");
}
else if (isset($_POST["purge"]))
{
	checkAuthentication("admin");
	deleteConsumableType($_POST,1);
	logEvent($tab["ID"], "consumables", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][24]);
	glpi_header($cfg_install["root"]."/consumables/");
}
else if (isset($_POST["update"]))
{
	checkAuthentication("admin");
	updateConsumableType($_POST);
	logEvent($_POST["ID"], "consumables", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
} 
else
{
	if (empty($tab["ID"]))
	checkAuthentication("admin");
	else checkAuthentication("normal");

	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
	//	glpi_header($_SERVER['HTTP_REFERER']);
	}

	commonHeader($lang["title"][36],$_SERVER["PHP_SELF"]);
	
	$ci=new CommonItem();
	if ($ci->getFromDB(CONSUMABLE_TYPE,$tab["ID"]))
	showConsumableOnglets($_SERVER["PHP_SELF"]."?ID=".$tab["ID"], "",$_SESSION['glpi_onglet'] );

	if (showConsumableTypeForm($_SERVER["PHP_SELF"],$tab["ID"])) {
		if (!empty($tab['ID']))
		switch($_SESSION['glpi_onglet']){
		case -1 :	
			showConsumableAdd($tab["ID"]);
			showConsumables($tab["ID"]);
			showConsumables($tab["ID"],1);
			showInfocomForm($cfg_install["root"]."/infocoms/infocoms-info-form.php",CONSUMABLE_TYPE,$tab["ID"],1);
			showDocumentAssociated(CONSUMABLE_TYPE,$tab["ID"]);
			showLinkOnDevice(CONSUMABLE_TYPE,$tab["ID"]);
			break;
		case 4 :
			showInfocomForm($cfg_install["root"]."/infocoms/infocoms-info-form.php",CONSUMABLE_TYPE,$tab["ID"],1);
			break;
			
		case 5 :
			showDocumentAssociated(CONSUMABLE_TYPE,$tab["ID"]);
			break;
			
		case 7 : 
			showLinkOnDevice(CONSUMABLE_TYPE,$tab["ID"]);
			break;

		case 10 :
				showNotesForm($_SERVER["PHP_SELF"],CONSUMABLE_TYPE,$tab["ID"]);
				break;
		default :
			showConsumableAdd($tab["ID"]);
			showConsumables($tab["ID"]);
			showConsumables($tab["ID"],1);
		break;
		}
	}
	
	commonFooter();
}

?>
