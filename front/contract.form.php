<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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


$NEEDED_ITEMS=array("contract","enterprise","computer","printer","monitor","peripheral","networking","software","document","link","phone","infocom");
define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(!isset($_GET["ID"])) $_GET["ID"] = "";

$contract=new Contract();

if (isset($_POST["add"]))
{
	checkEditItem(CONTRACT_TYPE);

	$newID=$contract->add($_POST);
	logEvent($newID, "contracts", 4, "financial", $_SESSION["glpiname"]." ".$LANG["log"][20]." ".$_POST["num"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
} 
else if (isset($_POST["delete"]))
{
	checkEditItem(CONTRACT_TYPE, $_POST["ID"]);

	$contract->delete($_POST);
	logEvent($_POST["ID"], "contracts", 4, "financial", $_SESSION["glpiname"]." ".$LANG["log"][22]);
	glpi_header($CFG_GLPI["root_doc"]."/front/contract.php");
}
else if (isset($_POST["restore"]))
{
	checkEditItem(CONTRACT_TYPE, $_POST["ID"]);

	$contract->restore($_POST);
	logEvent($_POST["ID"], "contracts", 4, "financial", $_SESSION["glpiname"]." ".$LANG["log"][23]);
	glpi_header($CFG_GLPI["root_doc"]."/front/contract.php");
}
else if (isset($_POST["purge"]))
{
	checkEditItem(CONTRACT_TYPE, $_POST["ID"]);

	$contract->delete($_POST,1);
	logEvent($_POST["ID"], "contracts", 4, "financial", $_SESSION["glpiname"]." ".$LANG["log"][24]);
	glpi_header($CFG_GLPI["root_doc"]."/front/contract.php");
}
else if (isset($_POST["update"]))
{
	checkEditItem(CONTRACT_TYPE, $_POST["ID"]);

	$contract->update($_POST);
	logEvent($_POST["ID"], "contracts", 4, "financial", $_SESSION["glpiname"]." ".$LANG["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
} 
else if (isset($_POST["additem"]))
{
	if (strstr($_SERVER['HTTP_REFERER'], $_SERVER['SCRIPT_NAME'])) {
		// error_log("update from contract form");
		checkEditItem(CONTRACT_TYPE, $_POST["conID"]);
	} else {
		// error_log("update from infocom form of an equipement");
		checkRight("contract_infocom","w");
	}

	if ($_POST['type']>0&&$_POST['item']>0){
		addDeviceContract($_POST["conID"],$_POST['type'],$_POST['item']);
		logEvent($_POST["conID"], "contracts", 4, "financial", $_SESSION["glpiname"]." ".$LANG["log"][32]);
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["deleteitem"]))
{
	// delete item from massive action menu
	
	if (strstr($_SERVER['HTTP_REFERER'], $_SERVER['SCRIPT_NAME'])) {
		// error_log("update from contract form");
		checkEditItem(CONTRACT_TYPE, $_POST["conID"]);
	} else {
		// error_log("update from infocom form of an equipement");
		checkRight("contract_infocom","w");
	}

	if (count($_POST["item"]))
		foreach ($_POST["item"] as $key => $val)
			deleteDeviceContract($key);

	logEvent($_POST["conID"], "contracts", 4, "financial", $_SESSION["glpiname"]." ".$LANG["log"][33]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_GET["deleteitem"]))
{
	// delete single item from url on list
	
	if (strstr($_SERVER['HTTP_REFERER'], $_SERVER['SCRIPT_NAME'])) {
		// error_log("update from contract form");
		checkEditItem(CONTRACT_TYPE, $_GET["conID"]);
	} else {
		// error_log("update from infocom form of an equipement");
		checkRight("contract_infocom","w");
	}

	deleteDeviceContract($_GET["ID"]);

	logEvent($_GET["conID"], "contracts", 4, "financial", $_SESSION["glpiname"]." ".$LANG["log"][33]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["addenterprise"]))
{
	checkEditItem(CONTRACT_TYPE, $_POST["conID"]);

	addEnterpriseContract($_POST["conID"],$_POST["entID"]);
	logEvent($_POST["conID"], "contracts", 4, "financial", $_SESSION["glpiname"]." ".$LANG["log"][34]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_GET["deleteenterprise"]))
{
	checkEditItem(CONTRACT_TYPE, $_GET["ID"]);

	deleteEnterpriseContract($_GET["ID"]);
	logEvent($_GET["ID"], "contracts", 4, "financial", $_SESSION["glpiname"]." ".$LANG["log"][35]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else
{
	checkRight("contract_infocom","r");

	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
	}

	commonHeader($LANG["title"][20],$_SERVER['PHP_SELF'],"financial","contract");

	if ($contract->showForm($_SERVER['PHP_SELF'],$_GET["ID"])) {
		if (!empty($_GET['ID'])){
			switch($_SESSION['glpi_onglet']){
				case -1 :	
					showEnterpriseContract($_GET["ID"]);
					showDeviceContract($_GET["ID"]);
					showDocumentAssociated(CONTRACT_TYPE,$_GET["ID"]);
					showLinkOnDevice(CONTACT_TYPE,$_GET["ID"]);
					displayPluginAction(CONTRACT_TYPE,$_GET["ID"],$_SESSION['glpi_onglet']);
					break;
				case 5 : 
					showDocumentAssociated(CONTRACT_TYPE,$_GET["ID"]);
					break;
				case 7 : 
					showLinkOnDevice(CONTRACT_TYPE,$_GET["ID"]);
					break;
				case 10 :
					showNotesForm($_SERVER['PHP_SELF'],CONTRACT_TYPE,$_GET["ID"]);
					break;
				default :
					if (!displayPluginAction(CONTRACT_TYPE,$_GET["ID"],$_SESSION['glpi_onglet'])){
						showEnterpriseContract($_GET["ID"]);
						showDeviceContract($_GET["ID"]);
					}
					break;
			}
		}
	}	
	commonFooter();
}

?>
