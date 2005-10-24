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
 
// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_devices.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_monitors.php");
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_tracking.php");
include ($phproot . "/glpi/includes_software.php");
include ($phproot . "/glpi/includes_peripherals.php");
include ($phproot . "/glpi/includes_reservation.php");
include ($phproot . "/glpi/includes_state.php");
include ($phproot . "/glpi/includes_financial.php");
include ($phproot . "/glpi/includes_documents.php");
include ($phproot . "/glpi/includes_users.php");
include ($phproot . "/glpi/includes_links.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";
if(!isset($tab["withtemplate"])) $tab["withtemplate"] = "";


//Add a new computer
if (isset($tab["add"])) {
	checkAuthentication("admin");
	$newID=addComputer($tab);
	logEvent($newID, "computers", 4, "inventory", $_SESSION["glpiname"]." added ".$tab["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
}
// delete a computer
else if (isset($tab["delete"])) {
	checkAuthentication("admin");

	if (!empty($tab["withtemplate"]))
	deleteComputer($tab,1);
	else deleteComputer($tab);
	logEvent($tab["ID"], "computers", 4, "inventory", $_SESSION["glpiname"]." deleted item.");
	if(!empty($tab["withtemplate"])) 
		glpi_header($cfg_install["root"]."/setup/setup-templates.php");
	 else 
		glpi_header($cfg_install["root"]."/computers/");
}
else if (isset($_POST["restore"]))
{
	checkAuthentication("admin");
	restoreComputer($_POST);
	logEvent($tab["ID"], "computers", 4, "inventory", $_SESSION["glpiname"]." restored item.");
	glpi_header($cfg_install["root"]."/computers/");
}
else if (isset($tab["purge"]))
{
	checkAuthentication("admin");
	deleteComputer($tab,1);
	updateState(COMPUTER_TYPE,$tab["ID"],0);
	logEvent($tab["ID"], "computers", 4, "inventory", $_SESSION["glpiname"]." purge item.");
	glpi_header($cfg_install["root"]."/computers/");
}
//update a computer
else if (isset($tab["update"])) {
	if(empty($tab["show"])) $tab["show"] = "";
	if(empty($tab["contains"])) $tab["contains"] = "";
	checkAuthentication("admin");
	updateComputer($tab);
	logEvent($tab["ID"], "computers", 4, "inventory", $_SESSION["glpiname"]." updated item.");
	glpi_header($_SERVER['HTTP_REFERER']);
}
//Disconnect a device 
else if (isset($tab["disconnect"])) {
	checkAuthentication("admin");
	Disconnect($tab["ID"]);
	logEvent($tab["cID"], "computers", 5, "inventory", $_SESSION["glpiname"]." disconnected item.");
	glpi_header($_SERVER["PHP_SELF"]."?ID=".$tab["cID"]."&withtemplate=".$tab["withtemplate"]);
}
//Connect a peripheral
/*else if(isset($tab["connect"])&&isset($tab["device_type"])) {
	if($tab["connect"]==1) {
		checkAuthentication("admin");
		commonHeader($lang["title"][3],$_SERVER["PHP_SELF"]);
		showConnectSearch($_SERVER["PHP_SELF"],$tab["ID"],$tab["device_type"],$tab["withtemplate"]);
		commonFooter();
	}	 
	else if($tab["connect"]==2) {
		checkAuthentication("admin");
		commonHeader($lang["title"][3],$_SERVER["PHP_SELF"]);
		listConnectElement($_SERVER["PHP_SELF"],$tab);
		commonFooter();
	} 
	else if($tab["connect"]==3) {
		checkAuthentication("admin");
		Connect($_SERVER["PHP_SELF"],$tab["ID"],$tab["cID"],$tab["device_type"],$tab["withtemplate"]);
		logEvent($tab["cID"], "computers", 5, "inventory", $_SESSION["glpiname"] ." connected item.");
		glpi_header($_SERVER["PHP_SELF"]."?ID=".$tab["cID"]."&withtemplate=".$tab["withtemplate"]);
	}
}
*/
else if (isset($tab["connect"])&&isset($tab["item"])){
	Connect($_SERVER["PHP_SELF"],$tab["item"],$tab["cID"],$tab["device_type"],$tab["withtemplate"]);
	logEvent($tab["cID"], "computers", 5, "inventory", $_SESSION["glpiname"] ." connected item.");
	glpi_header($_SERVER["PHP_SELF"]."?ID=".$tab["cID"]."&withtemplate=".$tab["withtemplate"]);
}
//Update a device specification
elseif(isset($_POST["update_device_x"])||isset($_POST["update_device"])) {
	checkAuthentication("admin");
	foreach ($_POST as $key => $val){
		$tab=split("_",$key);
		if (count($tab)==2&&$tab[0]=="devicevalue"){
		update_device_specif($val,$tab[1]);
		}
	}
	logEvent($_POST["ID"],"computers",4,"inventory",$_SESSION["glpiname"] ." modified a computer device specificity.");
	glpi_header($_SERVER['HTTP_REFERER']);
}
//add a new device
elseif (isset($_POST["connect_device"])) {
	if(isset($_POST["new_device_type"])){
		if ($_POST["new_device_type"]==-1) glpi_header($_SERVER['HTTP_REFERER']." ");
		checkAuthentication("admin");
		commonHeader($lang["title"][3],$_SERVER["PHP_SELF"]);
		compdevice_form_add($_SERVER["HTTP_REFERER"],$_POST["new_device_type"],$_POST["cID"],$tab["withtemplate"]);
		commonFooter();
	}elseif(isset($_POST["new_device_id"])){
		checkAuthentication("admin");
		compdevice_add($_POST["cID"],$_POST["device_type"],$_POST["new_device_id"]);
		glpi_header($_SERVER["PHP_SELF"]."?ID=".$_POST["cID"]."&withtemplate=".$tab["withtemplate"]);
	}
}
// Unlink a device 
// Problem avec IE donc test sur le hidden action_device - MERCI IE :(
//elseif(isset($_POST["unlink_device"])||isset($_POST["unlink_device_x"])) {
elseif(isset($_POST["device_action"])) {
	
	$devtodel=-1;
	foreach ($_POST as $key => $val)
	if (preg_match("/unlink_device_([0-9]+)_x/",$key,$match))
	{
	$devtodel=$match[1];	
	}

	if ($devtodel>0){
		checkAuthentication("admin");
		unlink_device_computer($devtodel);
		logEvent($_POST["ID"],"computers",4,"inventory",$_SESSION["glpiname"] ." Unlinked a device from computer ".$tab["ID"].".");
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
//print computer informations
else {

	checkAuthentication("normal");


if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
if (isset($_GET['onglet'])) {
	$_SESSION['glpi_onglet']=$_GET['onglet'];
//	glpi_header($_SERVER['HTTP_REFERER']);
}

	commonHeader($lang["title"][3],$_SERVER["PHP_SELF"]);
	
	$ci=new CommonItem();
	if ($ci->getFromDB(COMPUTER_TYPE,$tab["ID"]))
		showComputerOnglets($_SERVER["PHP_SELF"]."?ID=".$tab["ID"], $tab["withtemplate"],$_SESSION['glpi_onglet'] );
	//show computer form to add
	if (!empty($tab["withtemplate"])) {
		
		if (showComputerForm($_SERVER["PHP_SELF"],$tab["ID"], $tab["withtemplate"])){
			if (!empty($tab["ID"])){
			switch($_SESSION['glpi_onglet']){
			case 2 :			
				showSoftwareInstalled($tab["ID"],$tab["withtemplate"]);
				break;
			case 3 :
				showPorts($tab["ID"], COMPUTER_TYPE,$tab["withtemplate"]);
				if ($tab["withtemplate"]!=2)
					showPortsAdd($tab["ID"],COMPUTER_TYPE);
				break;					
			case 4 :
				showInfocomForm($cfg_install["root"]."/infocoms/infocoms-info-form.php",COMPUTER_TYPE,$tab["ID"],1,$tab["withtemplate"]);
				showContractAssociated(COMPUTER_TYPE,$tab["ID"],$tab["withtemplate"]);
				break;
			case 5 :
				showDocumentAssociated(COMPUTER_TYPE,$tab["ID"],$tab["withtemplate"]);
				break;
			default :
				showDeviceComputerForm($_SERVER["PHP_SELF"],$tab["ID"], $tab["withtemplate"]);			
				break;
			}
		}
	}
	} else {
	if (isAdmin($_SESSION["glpitype"])&&isset($_POST["delete_inter"])&&!empty($_POST["todel"])){
		$j=new Job;
		foreach ($_POST["todel"] as $key => $val){
			if ($val==1) $j->deleteInDB($key);
			}
		}
		
		if (showComputerForm($_SERVER["PHP_SELF"],$tab["ID"], $tab["withtemplate"])) {
			switch($_SESSION['glpi_onglet']){
			case -1 :
				showDeviceComputerForm($_SERVER["PHP_SELF"],$tab["ID"], $tab["withtemplate"]);			
				showSoftwareInstalled($tab["ID"]);
				showConnections($_SERVER["PHP_SELF"],$tab["ID"]);
				showPorts($tab["ID"], COMPUTER_TYPE);
				showPortsAdd($tab["ID"],COMPUTER_TYPE);
				showInfocomForm($cfg_install["root"]."/infocoms/infocoms-info-form.php",COMPUTER_TYPE,$tab["ID"]);
				showContractAssociated(COMPUTER_TYPE,$tab["ID"]);
				showDocumentAssociated(COMPUTER_TYPE,$tab["ID"]);
				showJobListForItem($_SESSION["glpiname"],COMPUTER_TYPE,$tab["ID"]);
				showOldJobListForItem($_SESSION["glpiname"],COMPUTER_TYPE,$tab["ID"]);
				showLinkOnDevice(COMPUTER_TYPE,$tab["ID"]);
				break;
			case 2 :
				showSoftwareInstalled($tab["ID"]);
				break;
			case 3 :
				showConnections($_SERVER["PHP_SELF"],$tab["ID"]);
				showPorts($tab["ID"], COMPUTER_TYPE);
				showPortsAdd($tab["ID"],COMPUTER_TYPE);
				break;
			case 4 :
				showInfocomForm($cfg_install["root"]."/infocoms/infocoms-info-form.php",COMPUTER_TYPE,$tab["ID"]);
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
			default :
				showDeviceComputerForm($_SERVER["PHP_SELF"],$tab["ID"], $tab["withtemplate"]);			
				break;
			}
			
		}
	}
	commonFooter();
}
?>
