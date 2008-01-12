<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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


$NEEDED_ITEMS=array("enterprise","contact","document","contract","tracking","user","group","computer","printer","monitor","peripheral","networking","software","link","phone","infocom","device");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(!isset($_GET["ID"])) $_GET["ID"] = "";

if (!isset($_GET["start"])) {
	$_GET["start"]=0;
}

if (!isset($_GET["sort"])) $_GET["sort"]="";
if (!isset($_GET["order"])) $_GET["order"]="";

$ent=new Enterprise();
if (isset($_POST["add"]))
{
	checkEditItem(ENTERPRISE_TYPE);

	$newID=$ent->add($_POST);
	logEvent($newID, "enterprises", 4, "financial", $_SESSION["glpiname"]." ".$LANG["log"][20]." ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
} 
else if (isset($_POST["delete"]))
{
	checkEditItem(ENTERPRISE_TYPE, $_POST["ID"]);

	$ent->delete($_POST);
	logEvent($_POST["ID"], "enterprises", 4, "financial", $_SESSION["glpiname"]." ".$LANG["log"][22]);
	glpi_header($CFG_GLPI["root_doc"]."/front/enterprise.php");
}
else if (isset($_POST["restore"]))
{
	checkEditItem(ENTERPRISE_TYPE, $_POST["ID"]);

	$ent->restore($_POST);
	logEvent($_POST["ID"], "enterprises", 4, "financial", $_SESSION["glpiname"]." ".$LANG["log"][23]);
	glpi_header($CFG_GLPI["root_doc"]."/front/enterprise.php");
}
else if (isset($_POST["purge"]))
{
	checkEditItem(ENTERPRISE_TYPE, $_POST["ID"]);

	$ent->delete($_POST,1);
	logEvent($_POST["ID"], "enterprises", 4, "financial", $_SESSION["glpiname"]." ".$LANG["log"][24]);
	glpi_header($CFG_GLPI["root_doc"]."/front/enterprise.php");
}
else if (isset($_POST["update"]))
{
	checkEditItem(ENTERPRISE_TYPE, $_POST["ID"]);

	$ent->update($_POST);
	logEvent($_POST["ID"], "enterprises", 4, "financial", $_SESSION["glpiname"]." ".$LANG["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
} 
else if (isset($_POST["addcontact"]))
{
	checkEditItem(ENTERPRISE_TYPE, $_POST["eID"]);

	addContactEnterprise($_POST["eID"],$_POST["cID"]);
	logEvent($_POST["eID"], "enterprises", 4, "financial", $_SESSION["glpiname"]." ".$LANG["log"][36]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_GET["deletecontact"]))
{
	checkEditItem(ENTERPRISE_TYPE, $_GET["eID"]);

	deleteContactEnterprise($_GET["ID"]);
	logEvent($_GET["eID"], "enterprises", 4, "financial", $_SESSION["glpiname"]." ".$LANG["log"][37]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["addcontract"]))
{
	checkEditItem(ENTERPRISE_TYPE, $_POST["conID"]);

	addEnterpriseContract($_POST["conID"],$_POST["entID"]);
	logEvent($_POST["conID"], "contracts", 4, "financial", $_SESSION["glpiname"]." ".$LANG["log"][34]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_GET["deletecontract"]))
{
	checkEditItem(ENTERPRISE_TYPE, $_GET["ID"]);

	deleteEnterpriseContract($_GET["ID"]);
	logEvent($_GET["ID"], "contracts", 4, "financial", $_SESSION["glpiname"]." ".$LANG["log"][35]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else
{
	checkRight("contact_enterprise","r");

	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
	}


	commonHeader($LANG["Menu"][23],$_SERVER['PHP_SELF'],"financial","enterprise");


	if ($ent->showForm($_SERVER['PHP_SELF'],$_GET["ID"])){
		if (!empty($_GET["ID"])){
			switch($_SESSION['glpi_onglet']){
				case -1:
					showAssociatedContact($_GET["ID"]);
					showContractAssociatedEnterprise($_GET["ID"]);
					showDocumentAssociated(ENTERPRISE_TYPE,$_GET["ID"]);
					showTrackingList($_SERVER['PHP_SELF'],$_GET["start"],$_GET["sort"],$_GET["order"],"all",'','',0,0,0,0,0,$_GET["ID"]);
					showLinkOnDevice(ENTERPRISE_TYPE,$_GET["ID"]);
					displayPluginAction(ENTERPRISE_TYPE,$_GET["ID"],$_SESSION['glpi_onglet']);
					break;
				case 1 :
					showAssociatedContact($_GET["ID"]);
					break;
				case 4 :
					showContractAssociatedEnterprise($_GET["ID"]);
					break;
				case 5 :
					showDocumentAssociated(ENTERPRISE_TYPE,$_GET["ID"],0);
					break;
				case 6 :
					showTrackingList($_SERVER['PHP_SELF']."?ID=".$_GET["ID"],$_GET["start"],$_GET["sort"],$_GET["order"],"all",'','',0,0,0,0,0,$_GET["ID"]);
					break;
				case 7 : 
					showLinkOnDevice(ENTERPRISE_TYPE,$_GET["ID"]);
					break;
				case 10 :
					showNotesForm($_SERVER['PHP_SELF'],ENTERPRISE_TYPE,$_GET["ID"]);
					break;	
				case 15 :
					showInfocomEnterprise($_GET["ID"]);
					break;	
				default : 
					if (!displayPluginAction(ENTERPRISE_TYPE,$_GET["ID"],$_SESSION['glpi_onglet'])){
						showAssociatedContact($_GET["ID"]);
					}
					break;
			}
		}
	}
	commonFooter();
}

?>
