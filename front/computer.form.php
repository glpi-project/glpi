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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
$NEEDED_ITEMS=array("computer","device","networking","monitor","printer","tracking","software","peripheral","reservation","state","infocom","contract","document","user","link","ocsng","phone","enterprise","search");
include ($phproot . "/inc/includes.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";
if(!isset($tab["withtemplate"])) $tab["withtemplate"] = "";

$computer=new Computer();
//Add a new computer
if (isset($tab["add"])) {
	checkRight("computer","w");
	$newID=$computer->add($tab);
	logEvent($newID, "computers", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][20]." ".$tab["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
}
// delete a computer
else if (isset($tab["delete"])) {
	checkRight("computer","w");

	if (!empty($tab["withtemplate"]))
		$computer->delete($tab,1);
	else $computer->delete($tab);
	logEvent($tab["ID"], "computers", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][22]);
	if(!empty($tab["withtemplate"])) 
		glpi_header($cfg_glpi["root_doc"]."/front/setup.templates.php");
	else 
		glpi_header($cfg_glpi["root_doc"]."/front/computer.php");
}
else if (isset($_POST["restore"]))
{
	checkRight("computer","w");
	$computer->restore($_POST);
	logEvent($tab["ID"],"computers", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][23]);
	glpi_header($cfg_glpi["root_doc"]."/front/computer.php");
}
else if (isset($tab["purge"]))
{
	checkRight("computer","w");
	$computer->delete($tab,1);
	logEvent($tab["ID"], "computers", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][24]);
	glpi_header($cfg_glpi["root_doc"]."/front/computer.php");
}
//update a computer
else if (isset($tab["update"])) {
	if(empty($tab["show"])) $tab["show"] = "";
	if(empty($tab["contains"])) $tab["contains"] = "";
	checkRight("computer","w");
	$computer->update($tab);
	logEvent($tab["ID"], "computers", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
//Disconnect a device 
else if (isset($tab["disconnect"])) {
	checkRight("computer","w");
	Disconnect($tab["ID"]);
	logEvent($tab["cID"], "computers", 5, "inventory", $_SESSION["glpiname"]." ".$lang["log"][26]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($tab["connect"])&&isset($tab["item"])&&$tab["item"]>0){
	checkRight("computer","w");
	Connect($tab["item"],$tab["cID"],$tab["device_type"],$tab["withtemplate"]);
	logEvent($tab["cID"], "computers", 5, "inventory", $_SESSION["glpiname"] ." ".$lang["log"][27]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
//Update a device specification
elseif(isset($_POST["update_device"])) {
	checkRight("computer","w");

	// Update quantity
	foreach ($_POST as $key => $val){
		$tab=split("_",$key);
		if (count($tab)==2)
			if ($tab[0]=="quantity"){
				update_device_quantity($val,$tab[1]);
			}
	}

	// Update specificity
	foreach ($_POST as $key => $val){
		$tab=split("_",$key);
		if (count($tab)==2)
			if ($tab[0]=="devicevalue"){
				update_device_specif($val,$tab[1]);
			} 
	}

	logEvent($_POST["ID"],"computers",4,"inventory",$_SESSION["glpiname"] ." ".$lang["log"][28]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
//add a new device
elseif (isset($_POST["connect_device"])) {
	checkRight("computer","w");
	if (isset($_POST["new_device_id"])&&$_POST["new_device_id"]>0)
		compdevice_add($_POST["cID"],$_POST["new_device_type"],$_POST["new_device_id"]);
	glpi_header($_SERVER['PHP_SELF']."?ID=".$_POST["cID"]."&withtemplate=".$tab["withtemplate"]);
}
elseif(isset($tab["unlock_field"])){
	checkRight("ocsng","w");
	if (isset($tab["lockfield"])&&count($tab["lockfield"])){
		foreach ($tab["lockfield"] as $key => $val)
			deleteInOcsArray($tab["ID"],$key,"computer_update");
	}
	glpi_header($_SERVER['HTTP_REFERER']);
} elseif (isset($tab["force_ocs_resynch"])){
	$dbocs=new DBocs();
	checkRight("ocsng","w");
	ocsUpdateComputer($tab["resynch_id"],1,1);
	glpi_header($_SERVER['HTTP_REFERER']);
} else {//print computer informations

	checkRight("computer","r");


	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
	}


	commonHeader($lang["title"][3],$_SERVER['PHP_SELF']);

	if ($computer->getFromDB($tab["ID"]))
		$computer->showOnglets($_SERVER['PHP_SELF']."?ID=".$tab["ID"], $tab["withtemplate"],$_SESSION['glpi_onglet'] );
	//show computer form to add
	if (!empty($tab["withtemplate"])) {

		if ($computer->showForm($_SERVER['PHP_SELF'],$tab["ID"], $tab["withtemplate"])){
			if (!empty($tab["ID"])){
				switch($_SESSION['glpi_onglet']){
					case 2 :			
						showSoftwareInstalled($tab["ID"],$tab["withtemplate"]);
						break;
					case 3 :
						showConnections($_SERVER['PHP_SELF'],$tab["ID"],$tab["withtemplate"]);
						if ($tab["withtemplate"]!=2)
							showPortsAdd($tab["ID"],COMPUTER_TYPE);
						showPorts($tab["ID"], COMPUTER_TYPE,$tab["withtemplate"]);
						break;					
					case 4 :
						showInfocomForm($cfg_glpi["root_doc"]."/front/infocom.form.php",COMPUTER_TYPE,$tab["ID"],1,$tab["withtemplate"]);
						showContractAssociated(COMPUTER_TYPE,$tab["ID"],$tab["withtemplate"]);
						break;
					case 5 :
						showDocumentAssociated(COMPUTER_TYPE,$tab["ID"],$tab["withtemplate"]);
						break;
					default :
						if (!display_plugin_action(COMPUTER_TYPE,$tab["ID"],$_SESSION['glpi_onglet'], $tab["withtemplate"]))
							showDeviceComputerForm($_SERVER['PHP_SELF'],$tab["ID"], $tab["withtemplate"]);	
						break;
				}
			}
		}
	} else {

		if (haveRight("delete_ticket","1")&&isset($_POST["delete_inter"])&&!empty($_POST["todel"])){
			$job=new Job();
			foreach ($_POST["todel"] as $key => $val){
				if ($val==1) {
					$job->delete(array("ID"=>$key));
				}
			}
		}

		if ($computer->showForm($_SERVER['PHP_SELF'],$tab["ID"], $tab["withtemplate"])) {
			switch($_SESSION['glpi_onglet']){
				case -1 :
					showDeviceComputerForm($_SERVER['PHP_SELF'],$tab["ID"], $tab["withtemplate"]);			
					showSoftwareInstalled($tab["ID"]);
					showConnections($_SERVER['PHP_SELF'],$tab["ID"]);
					showPortsAdd($tab["ID"],COMPUTER_TYPE);
					showPorts($tab["ID"], COMPUTER_TYPE);
					showInfocomForm($cfg_glpi["root_doc"]."/front/infocom.form.php",COMPUTER_TYPE,$tab["ID"]);
					showContractAssociated(COMPUTER_TYPE,$tab["ID"]);
					showDocumentAssociated(COMPUTER_TYPE,$tab["ID"]);
					showJobListForItem($_SESSION["glpiname"],COMPUTER_TYPE,$tab["ID"]);
					showOldJobListForItem($_SESSION["glpiname"],COMPUTER_TYPE,$tab["ID"]);
					showLinkOnDevice(COMPUTER_TYPE,$tab["ID"]);
					display_plugin_action(COMPUTER_TYPE,$tab["ID"],$_SESSION['glpi_onglet'],$tab["withtemplate"]);
					break;
				case 2 :
					showSoftwareInstalled($tab["ID"]);
					break;
				case 3 :
					showConnections($_SERVER['PHP_SELF'],$tab["ID"]);
					showPortsAdd($tab["ID"],COMPUTER_TYPE);
					showPorts($tab["ID"], COMPUTER_TYPE);
					break;
				case 4 :
					showInfocomForm($cfg_glpi["root_doc"]."/front/infocom.form.php",COMPUTER_TYPE,$tab["ID"]);
					showContractAssociated(COMPUTER_TYPE,$tab["ID"]);
					break;
				case 5 :
					showDocumentAssociated(COMPUTER_TYPE,$tab["ID"]);
					break;
				case 6 :
					showJobListForItem($_SESSION["glpiname"],COMPUTER_TYPE,$tab["ID"]);
					showOldJobListForItem($_SESSION["glpiname"],COMPUTER_TYPE,$tab["ID"]);
					break;
				case 7 :
					showLinkOnDevice(COMPUTER_TYPE,$tab["ID"]);
					break;
				case 10 :
					showNotesForm($_SERVER['PHP_SELF'],COMPUTER_TYPE,$tab["ID"]);
					break;
				case 11 :
					showDeviceReservations($_SERVER['PHP_SELF'],COMPUTER_TYPE,$tab["ID"]);
					break;
				case 12 :
					showHistory(COMPUTER_TYPE,$tab["ID"]);
					break;
				case 13 :
					ocsEditLock($_SERVER['PHP_SELF'],$tab["ID"]);
					break;
				default :
					if (!display_plugin_action(COMPUTER_TYPE,$tab["ID"],$_SESSION['glpi_onglet'],$tab["withtemplate"]))
						showDeviceComputerForm($_SERVER['PHP_SELF'],$tab["ID"], $tab["withtemplate"]);			
					break;
			}

		}
	}
	commonFooter();
}
?>
