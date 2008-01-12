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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------


$NEEDED_ITEMS=array("computer","rulesengine","device","networking","monitor","printer","tracking","software","peripheral","reservation","infocom","contract","document","user","group","link","ocsng","phone","enterprise","search","registry","group","setup","ocsng","rule.softwarecategories","rule.dictionnary.software","rule.dictionnary.dropdown");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(!isset($_GET["ID"])) $_GET["ID"] = "";
if(!isset($_GET["sort"])) $_GET["sort"] = "";
if(!isset($_GET["order"])) $_GET["order"] = "";
if(!isset($_GET["withtemplate"])) $_GET["withtemplate"] = "";

$computer=new Computer();
//Add a new computer
if (isset($_POST["add"])) {
	checkRight("computer","w");
	$newID=$computer->add($_POST);
	logEvent($newID, "computers", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][20]." ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
}
// delete a computer
else if (isset($_POST["delete"])) {
	checkRight("computer","w");

	if (!empty($_POST["withtemplate"]))
		$computer->delete($_POST,1);
	else $computer->delete($_POST);
	logEvent($_POST["ID"], "computers", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][22]);
	if(!empty($_POST["withtemplate"])) 
		glpi_header($CFG_GLPI["root_doc"]."/front/setup.templates.php");
	else 
		glpi_header($CFG_GLPI["root_doc"]."/front/computer.php");
}
else if (isset($_POST["restore"]))
{
	checkRight("computer","w");
	$computer->restore($_POST);
	logEvent($_POST["ID"],"computers", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][23]);
	glpi_header($CFG_GLPI["root_doc"]."/front/computer.php");
}
else if (isset($_POST["purge"]) || isset($_GET["purge"]))
{
	checkRight("computer","w");

	if (isset($_POST["purge"]))
		$input["ID"]=$_POST["ID"];
	else
		$input["ID"] = $_GET["ID"];	

	$computer->delete($input,1);
	logEvent($input["ID"], "computers", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][24]);
	glpi_header($CFG_GLPI["root_doc"]."/front/computer.php");
}
//update a computer
else if (isset($_POST["update"])) {
	checkRight("computer","w");
	$computer->update($_POST);
	logEvent($_POST["ID"], "computers", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
//Disconnect a device 
else if (isset($_GET["disconnect"])) {
	checkRight("computer","w");
	Disconnect($_GET["ID"]);
	logEvent($_GET["cID"], "computers", 5, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][26]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["connect"])&&isset($_POST["item"])&&$_POST["item"]>0){
	checkRight("computer","w");
	Connect($_POST["item"],$_POST["cID"],$_POST["device_type"],$_POST["dohistory"]);
	logEvent($_POST["cID"], "computers", 5, "inventory", $_SESSION["glpiname"] ." ".$LANG["log"][27]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
//Update a device specification
elseif(isset($_POST["update_device"])) {
	checkRight("computer","w");

	// Update quantity
	foreach ($_POST as $key => $val){
		$data=split("_",$key);
		if (count($data)==2)
			if ($data[0]=="quantity"){
				update_device_quantity($val,$data[1]);
			}
	}

	// Update specificity
	foreach ($_POST as $key => $val){
		$data=split("_",$key);
		if (count($data)==2)
			if ($data[0]=="devicevalue"){
				update_device_specif($val,$data[1]);
			} 
	}

	logEvent($_POST["ID"],"computers",4,"inventory",$_SESSION["glpiname"] ." ".$LANG["log"][28]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
//add a new device
elseif (isset($_POST["connect_device"])) {
	checkRight("computer","w");
	if (isset($_POST["new_device_id"])&&$_POST["new_device_id"]>0)
		compdevice_add($_POST["cID"],$_POST["new_device_type"],$_POST["new_device_id"]);
	glpi_header($_SERVER['PHP_SELF']."?ID=".$_POST["cID"]."&withtemplate=".$_POST["withtemplate"]);
}
elseif(isset($_POST["unlock_monitor"])){
	checkRight("computer","w");
	if (isset($_POST["lockmonitor"])&&count($_POST["lockmonitor"])){
		foreach ($_POST["lockmonitor"] as $key => $val)
			deleteInOcsArray($_POST["ID"],$key,"import_monitor");
	}
	glpi_header($_SERVER['HTTP_REFERER']);	
}
elseif(isset($_POST["unlock_printer"])){
	checkRight("computer","w");
	if (isset($_POST["lockprinter"])&&count($_POST["lockprinter"])){
		foreach ($_POST["lockprinter"] as $key => $val)
			deleteInOcsArray($_POST["ID"],$key,"import_printers");
	}
	glpi_header($_SERVER['HTTP_REFERER']);	
}
elseif(isset($_POST["unlock_soft"])){
	checkRight("computer","w");
	if (isset($_POST["locksoft"])&&count($_POST["locksoft"])){
		foreach ($_POST["locksoft"] as $key => $val)
			deleteInOcsArray($_POST["ID"],$key,"import_software");
	}
	glpi_header($_SERVER['HTTP_REFERER']);	
}
elseif(isset($_POST["unlock_periph"])){
	checkRight("computer","w");
	if (isset($_POST["lockperiph"])&&count($_POST["lockperiph"])){
		foreach ($_POST["lockperiph"] as $key => $val)
			deleteInOcsArray($_POST["ID"],$key,"import_peripheral");
	}
	glpi_header($_SERVER['HTTP_REFERER']);	
}
elseif(isset($_POST["unlock_ip"])){
	checkRight("computer","w");
	if (isset($_POST["lockip"])&&count($_POST["lockip"])){
		foreach ($_POST["lockip"] as $key => $val)
			deleteInOcsArray($_POST["ID"],$key,"import_ip");
	}
	glpi_header($_SERVER['HTTP_REFERER']);	
}
elseif(isset($_POST["unlock_field"])){
	checkRight("computer","w");
	if (isset($_POST["lockfield"])&&count($_POST["lockfield"])){
		foreach ($_POST["lockfield"] as $key => $val)
			deleteInOcsArray($_POST["ID"],$key,"computer_update");
	}
	glpi_header($_SERVER['HTTP_REFERER']);
} elseif (isset($_POST["force_ocs_resynch"])){
	//checkRight("ocsng","w");
	checkRight("sync_ocsng","w");
	
	//Get the ocs server id associated with the machine
	$ocs_server_id = getOCSServerByMachineID($_POST["ID"]);

	//Update the computer
	ocsUpdateComputer($_POST["resynch_id"],$ocs_server_id,1,1);
	glpi_header($_SERVER['HTTP_REFERER']);
} else {//print computer informations

	checkRight("computer","r");


	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
	}


	commonHeader($LANG["Menu"][0],$_SERVER['PHP_SELF'],"inventory","computer");

	//show computer form to add
	if (!empty($_GET["withtemplate"])) {
	
		if ($computer->showForm($_SERVER['PHP_SELF'],$_GET["ID"], $_GET["withtemplate"])){
			if ($_GET["ID"]>0){
				switch($_SESSION['glpi_onglet']){
					case 2 :			
						showSoftwareInstalled($_GET["ID"],$_GET["withtemplate"]);
						break;
					case 3 :
						showConnections($_SERVER['PHP_SELF'],$_GET["ID"],$_GET["withtemplate"]);
						if ($_GET["withtemplate"]!=2)
							showPortsAdd($_GET["ID"],COMPUTER_TYPE);
						showPorts($_GET["ID"], COMPUTER_TYPE,$_GET["withtemplate"]);
						break;					
					case 4 :
						showInfocomForm($CFG_GLPI["root_doc"]."/front/infocom.form.php",COMPUTER_TYPE,$_GET["ID"],1,$_GET["withtemplate"]);
						showContractAssociated(COMPUTER_TYPE,$_GET["ID"],$_GET["withtemplate"]);
						break;
					case 5 :
						showDocumentAssociated(COMPUTER_TYPE,$_GET["ID"],$_GET["withtemplate"]);
						break;
					default :
						if (!displayPluginAction(COMPUTER_TYPE,$_GET["ID"],$_SESSION['glpi_onglet'], $_GET["withtemplate"]))
							showDeviceComputerForm($_SERVER['PHP_SELF'],$_GET["ID"], $_GET["withtemplate"]);	
						break;
				}
			}
		}
	} else {

		if ($computer->showForm($_SERVER['PHP_SELF'],$_GET["ID"], $_GET["withtemplate"])) {
			switch($_SESSION['glpi_onglet']){
				case -1 :
					showDeviceComputerForm($_SERVER['PHP_SELF'],$_GET["ID"], $_GET["withtemplate"]);			
					showSoftwareInstalled($_GET["ID"]);
					showConnections($_SERVER['PHP_SELF'],$_GET["ID"]);
					showPortsAdd($_GET["ID"],COMPUTER_TYPE);
					showPorts($_GET["ID"], COMPUTER_TYPE);
					showInfocomForm($CFG_GLPI["root_doc"]."/front/infocom.form.php",COMPUTER_TYPE,$_GET["ID"]);
					showContractAssociated(COMPUTER_TYPE,$_GET["ID"]);
					showDocumentAssociated(COMPUTER_TYPE,$_GET["ID"]);
					showJobListForItem($_SESSION["glpiname"],COMPUTER_TYPE,$_GET["ID"],$_GET["sort"],$_GET["order"]);
					showOldJobListForItem($_SESSION["glpiname"],COMPUTER_TYPE,$_GET["ID"],$_GET["sort"],$_GET["order"]);
					showLinkOnDevice(COMPUTER_TYPE,$_GET["ID"]);
					showRegistry(REGISTRY_TYPE,$_GET["ID"]);
					displayPluginAction(COMPUTER_TYPE,$_GET["ID"],$_SESSION['glpi_onglet'],$_GET["withtemplate"]);
					break;
				case 2 :
					showSoftwareInstalled($_GET["ID"]);
					break;
				case 3 :
					showConnections($_SERVER['PHP_SELF'],$_GET["ID"]);
					showPortsAdd($_GET["ID"],COMPUTER_TYPE);
					showPorts($_GET["ID"], COMPUTER_TYPE);
					break;
				case 4 :
					showInfocomForm($CFG_GLPI["root_doc"]."/front/infocom.form.php",COMPUTER_TYPE,$_GET["ID"]);
					showContractAssociated(COMPUTER_TYPE,$_GET["ID"]);
					break;
				case 5 :
					showDocumentAssociated(COMPUTER_TYPE,$_GET["ID"]);
					break;
				case 6 :
					showJobListForItem($_SESSION["glpiname"],COMPUTER_TYPE,$_GET["ID"],$_GET["sort"],$_GET["order"]);
					showOldJobListForItem($_SESSION["glpiname"],COMPUTER_TYPE,$_GET["ID"],$_GET["sort"],$_GET["order"]);
					break;
				case 7 :
					showLinkOnDevice(COMPUTER_TYPE,$_GET["ID"]);
					break;
				case 10 :
					showNotesForm($_SERVER['PHP_SELF'],COMPUTER_TYPE,$_GET["ID"]);
					break;
				case 11 :
					showDeviceReservations($_SERVER['PHP_SELF'],COMPUTER_TYPE,$_GET["ID"]);
					break;
				case 12 :
					showHistory(COMPUTER_TYPE,$_GET["ID"]);
					break;
				case 13 :
					ocsEditLock($_SERVER['PHP_SELF'],$_GET["ID"]);
					break;
				case 14:					
					showRegistry(REGISTRY_TYPE,$_GET["ID"]);
					break;
				default :
					if (!displayPluginAction(COMPUTER_TYPE,$_GET["ID"],$_SESSION['glpi_onglet'],$_GET["withtemplate"]))
						showDeviceComputerForm($_SERVER['PHP_SELF'],$_GET["ID"], $_GET["withtemplate"]);			
					break;
			}

		}
	}
	commonFooter();
}
?>
