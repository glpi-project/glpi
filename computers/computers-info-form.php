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
include ($phproot . "/glpi/includes_financial.php");
include ($phproot . "/glpi/includes_documents.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";
if(!isset($tab["withtemplate"])) $tab["withtemplate"] = "";

//Add a new computer
if (isset($tab["add"])) {
	checkAuthentication("admin");
	addComputer($tab);
	logEvent(0, "computers", 4, "inventory", $_SESSION["glpiname"]." added ".$tab["name"].".");
	header("Location: ".$_SERVER['HTTP_REFERER']);
}
// delete a computer
else if (isset($tab["delete"])) {
	checkAuthentication("admin");

	if (!empty($tab["withtemplate"]))
	deleteComputer($tab,1);
	else deleteComputer($tab);
	logEvent($tab["ID"], "computers", 4, "inventory", $_SESSION["glpiname"]." deleted item.");
	if(!empty($tab["withtemplate"])) 
		header("Location: ".$cfg_install["root"]."/setup/setup-templates.php");
	 else 
		header("Location: ".$cfg_install["root"]."/computers/");
	
}
else if (isset($_POST["restore"]))
{
	checkAuthentication("admin");
	restoreComputer($_POST);
	logEvent($tab["ID"], "computers", 4, "inventory", $_SESSION["glpiname"]." restored item.");
	header("Location: ".$cfg_install["root"]."/computers/");
}
else if (isset($_POST["purge"]))
{
	checkAuthentication("admin");
	deleteComputer($_POST,1);
	logEvent($tab["ID"], "computers", 4, "inventory", $_SESSION["glpiname"]." purge item.");
	header("Location: ".$cfg_install["root"]."/computers/");
}
//update a computer
else if (isset($tab["update"])) {
	if(empty($tab["show"])) $tab["show"] = "";
	if(empty($tab["contains"])) $tab["contains"] = "";
	checkAuthentication("admin");
	updateComputer($tab);
	logEvent($tab["ID"], "computers", 4, "inventory", $_SESSION["glpiname"]."updated item.");
	header("Location: ".$_SERVER['HTTP_REFERER']);
}
//Disconnect a device 
else if (isset($tab["disconnect"])) {
	checkAuthentication("admin");
	Disconnect($tab["eID"],$tab["device_type"]);
	logEvent($tab["cID"], "computers", 5, "inventory", $_SESSION["glpiname"]." disconnected item.");
	header("Location: ".$_SERVER["PHP_SELF"]."?ID=".$tab["cID"]."&withtemplate=".$tab["withtemplate"]);
}
//Connect a peripheral
else if(isset($tab["connect"])&&isset($tab["device_type"])) {
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
		header("Location: ".$_SERVER["PHP_SELF"]."?ID=".$tab["cID"]."&withtemplate=".$tab["withtemplate"]);
	}
}
//Update a device specification
elseif(isset($_POST["update_device"])) {
	checkAuthentication("admin");
	update_device_specif($_POST["device_value"],$_POST["update_device"]);
	logEvent($_POST["update_device"],"computers",4,"inventory",$_SESSION["glpiname"] ." modified a computer device spécificity.");
	header("Location: ".$_SERVER['HTTP_REFERER']);
}
//add a new device
elseif (isset($_POST["connect_device"])) {
	if(isset($_POST["new_device_type"])){
		checkAuthentication("admin");
		commonHeader($lang["title"][3],$_SERVER["PHP_SELF"]);
		compdevice_form_add($_SERVER["HTTP_REFERER"],$_POST["new_device_type"],$_POST["cID"],$tab["withtemplate"]);
		commonFooter();
	}elseif(isset($_POST["new_device_id"])){
		checkAuthentication("admin");
		compdevice_add($_POST["cID"],$_POST["device_type"],$_POST["new_device_id"]);
		header("Location: ".$_SERVER["PHP_SELF"]."?ID=".$_POST["cID"]."&withtemplate=".$tab["withtemplate"]);
	}
}
//print computer informations
else {

	checkAuthentication("normal");
	commonHeader($lang["title"][3],$_SERVER["PHP_SELF"]);
	//show computer form to add
	if (!empty($tab["withtemplate"])) {
		if (showComputerForm($_SERVER["PHP_SELF"],$tab["ID"], $tab["withtemplate"])){
		
			if (!empty($tab["ID"])){
			showDocumentAssociated(COMPUTER_TYPE,$tab["ID"],$tab["withtemplate"]);
			showInfocomForm($cfg_install["root"]."/infocoms/infocoms-info-form.php",COMPUTER_TYPE,$tab["ID"],1,$tab["withtemplate"]);
			showPorts($tab["ID"], COMPUTER_TYPE,$tab["withtemplate"]);
			if ($tab["withtemplate"]!=2)
				showPortsAdd($tab["ID"],COMPUTER_TYPE);
		
			showSoftwareInstalled($tab["ID"],$tab["withtemplate"]);
			showContractAssociated(COMPUTER_TYPE,$tab["ID"],$tab["withtemplate"]);
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
			showDocumentAssociated(COMPUTER_TYPE,$tab["ID"]);
			showInfocomForm($cfg_install["root"]."/infocoms/infocoms-info-form.php",COMPUTER_TYPE,$tab["ID"]);
			showConnections($tab["ID"]);
			showPorts($tab["ID"], COMPUTER_TYPE);
			showPortsAdd($tab["ID"],COMPUTER_TYPE);
			showSoftwareInstalled($tab["ID"]);
			showContractAssociated(COMPUTER_TYPE,$tab["ID"]);
			showJobListForItem($_SESSION["glpiname"],COMPUTER_TYPE,$tab["ID"]);
			showOldJobListForItem($_SESSION["glpiname"],COMPUTER_TYPE,$tab["ID"]);
			
		}
	}
	commonFooter();
}


?>
