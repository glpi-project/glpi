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
$NEEDED_ITEMS=array("enterprise","contact","document","contract","tracking","user","computer","printer","monitor","peripheral","networking","software","link","phone","infocom","device");
include ($phproot . "/inc/includes.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";

if (isset($_GET["start"])) $start=$_GET["start"];
else $start=0;

$ent=new Enterprise();
if (isset($_POST["add"]))
{
	checkRight("contact_enterprise","w");

	$newID=$ent->add($_POST);
	logEvent($newID, "enterprises", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][20]." ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
} 
else if (isset($_POST["delete"]))
{
	checkRight("contact_enterprise","w");

	$ent->delete($_POST);
	logEvent($tab["ID"], "enterprises", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][22]);
	glpi_header($cfg_glpi["root_doc"]."/front/enterprise.php");
}
else if (isset($_POST["restore"]))
{
	checkRight("contact_enterprise","w");

	$ent->restore($_POST);
	logEvent($tab["ID"], "enterprises", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][23]);
	glpi_header($cfg_glpi["root_doc"]."/front/enterprise.php");
}
else if (isset($_POST["purge"]))
{
	checkRight("contact_enterprise","w");

	$ent->delete($_POST,1);
	logEvent($tab["ID"], "enterprises", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][24]);
	glpi_header($cfg_glpi["root_doc"]."/front/enterprise.php");
}
else if (isset($_POST["update"]))
{
	checkRight("contact_enterprise","w");

	$ent->update($_POST);
	logEvent($_POST["ID"], "enterprises", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
} 
else if (isset($_POST["addcontact"])){
	checkRight("contact_enterprise","w");

	addContactEnterprise($_POST["eID"],$_POST["cID"]);
	logEvent($tab["eID"], "enterprises", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][36]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_GET["deletecontact"])){
	checkRight("contact_enterprise","w");

	deleteContactEnterprise($_GET["ID"]);
	logEvent($_GET["eID"], "enterprises", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][37]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else
{
	checkRight("contact_enterprise","r");

	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
	}


	commonHeader($lang["title"][23],$_SERVER['PHP_SELF']);


	if (haveRight("delete_ticket","1")&&isset($_POST["delete_inter"])&&!empty($_POST["todel"])){
		$job=new Job();
		foreach ($_POST["todel"] as $key => $val){
			if ($val==1) {
				$job->delete(array("ID"=>$key));
			}
		}
	}

	if ($ent->getFromDB($tab["ID"]))
		$ent->showOnglets($_SERVER['PHP_SELF']."?ID=".$tab["ID"], "",$_SESSION['glpi_onglet'] );

	if ($ent->showForm($_SERVER['PHP_SELF'],$tab["ID"])){
		if (!empty($tab["ID"]))
			switch($_SESSION['glpi_onglet']){
				case -1:
					showAssociatedContact($tab["ID"]);
					showContractAssociatedEnterprise($tab["ID"]);
					showDocumentAssociated(ENTERPRISE_TYPE,$tab["ID"]);
					showTrackingList($_SERVER['PHP_SELF'],$start,"","","all",0,0,0,$_GET["ID"]);
					showLinkOnDevice(ENTERPRISE_TYPE,$tab["ID"]);
					display_plugin_action(ENTERPRISE_TYPE,$tab["ID"],$_SESSION['glpi_onglet']);
					break;
				case 1 :
					showAssociatedContact($tab["ID"]);
					break;
				case 4 :
					showContractAssociatedEnterprise($tab["ID"]);
					break;
				case 5 :
					showDocumentAssociated(ENTERPRISE_TYPE,$tab["ID"],0);
					break;
				case 6 :
					showTrackingList($_SERVER['PHP_SELF']."?ID=".$tab["ID"],$start,"","","all",0,0,0,$_GET["ID"]);
					break;
				case 7 : 
					showLinkOnDevice(ENTERPRISE_TYPE,$tab["ID"]);
					break;
				case 10 :
					showNotesForm($_SERVER['PHP_SELF'],ENTERPRISE_TYPE,$tab["ID"]);
					break;	
				case 15 :
					echo "<div align='center'><table border='0'><tr><td valign='top'>";
					showDeviceManufacturer($tab["ID"]);
					echo "</td><td valign='top'>";
					showInternalDeviceManufacturer($tab["ID"]);
					echo "</td><td valign='top'>";
					showInfocomEnterprise($tab["ID"]);
					echo "</td></tr></table></div>";
					break;	
				default : 
					if (!display_plugin_action(ENTERPRISE_TYPE,$tab["ID"],$_SESSION['glpi_onglet']))
						showAssociatedContact($tab["ID"]);

					break;
			}
	}

	commonFooter();
}

?>
