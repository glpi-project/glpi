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
 
// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
$NEEDED_ITEMS=array("computer","device","networking","monitor","printer","tracking","software","peripheral","reservation","state","infocom","contract","document","user","link","ocsng","phone","enterprise");
include ($phproot . "/inc/includes.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";
if(!isset($tab["withtemplate"])) $tab["withtemplate"] = "";

$comp=new Computer();
//Add a new computer
if (isset($tab["add"])) {
	checkRight("computer","w");
	$newID=$comp->add($tab);
	logEvent($newID, "computers", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][20]." ".$tab["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
}
// delete a computer
else if (isset($tab["delete"])) {
	checkRight("computer","w");

	if (!empty($tab["withtemplate"]))
	$comp->delete($tab,1);
	else $comp->delete($tab);
	logEvent($tab["ID"], "computers", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][22]);
	if(!empty($tab["withtemplate"])) 
		glpi_header($cfg_glpi["root_doc"]."/setup/setup-templates.php");
	 else 
		glpi_header($cfg_glpi["root_doc"]."/computers/");
}
else if (isset($_POST["restore"]))
{
	checkRight("computer","w");
	$comp->restore($_POST);
	logEvent($tab["ID"],"computers", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][23]);
	glpi_header($cfg_glpi["root_doc"]."/computers/");
}
else if (isset($tab["purge"]))
{
	checkRight("computer","w");
	$comp->delete($tab,1);
	logEvent($tab["ID"], "computers", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][24]);
	glpi_header($cfg_glpi["root_doc"]."/computers/");
}
//update a computer
else if (isset($tab["update"])) {
	if(empty($tab["show"])) $tab["show"] = "";
	if(empty($tab["contains"])) $tab["contains"] = "";
	checkRight("computer","w");
	$comp->update($tab);
	logEvent($tab["ID"], "computers", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
//Disconnect a device 
else if (isset($tab["disconnect"])) {
	checkRight("computer","w");
	Disconnect($tab["ID"]);
	logEvent($tab["cID"], "computers", 5, "inventory", $_SESSION["glpiname"]." ".$lang["log"][26]);
	glpi_header($_SERVER["PHP_SELF"]."?ID=".$tab["cID"]."&withtemplate=".$tab["withtemplate"]);
}
else if (isset($tab["connect"])&&isset($tab["item"])&&$tab["item"]>0){
	checkRight("computer","w");
	Connect($_SERVER["PHP_SELF"],$tab["item"],$tab["cID"],$tab["device_type"],$tab["withtemplate"]);
	logEvent($tab["cID"], "computers", 5, "inventory", $_SESSION["glpiname"] ." ".$lang["log"][27]);
	glpi_header($_SERVER["PHP_SELF"]."?ID=".$tab["cID"]."&withtemplate=".$tab["withtemplate"]);
}
//Update a device specification
elseif(isset($_POST["update_device_x"])||isset($_POST["update_device"])) {
	checkRight("computer","w");
	foreach ($_POST as $key => $val){
		$tab=split("_",$key);
		if (count($tab)==2&&$tab[0]=="devicevalue"){
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
		glpi_header($_SERVER["PHP_SELF"]."?ID=".$_POST["cID"]."&withtemplate=".$tab["withtemplate"]);
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
		checkRight("computer","w");
		unlink_device_computer($devtodel);
		logEvent($_POST["ID"],"computers",4,"inventory",$_SESSION["glpiname"] ." ".$lang["log"][29]." ".$tab["ID"].".");
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
elseif(isset($tab["unlock_field"])){
	checkRight("ocsng","w");
	if (isset($tab["lockfield"])&&count($tab["lockfield"])){
		foreach ($tab["lockfield"] as $key => $val)
			deleteInOcsArray($tab["ID"],$key,"computer_update");
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
//print computer informations
else {

	checkRight("computer","r");


if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
if (isset($_GET['onglet'])) {
	$_SESSION['glpi_onglet']=$_GET['onglet'];
//	glpi_header($_SERVER['HTTP_REFERER']);
}


	commonHeader($lang["title"][3],$_SERVER["PHP_SELF"]);
	
	if ($comp->getFromDB($tab["ID"]))
		$comp->showOnglets($_SERVER["PHP_SELF"]."?ID=".$tab["ID"], $tab["withtemplate"],$_SESSION['glpi_onglet'] );
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
				showInfocomForm($cfg_glpi["root_doc"]."/infocoms/infocoms-info-form.php",COMPUTER_TYPE,$tab["ID"],1,$tab["withtemplate"]);
				showContractAssociated(COMPUTER_TYPE,$tab["ID"],$tab["withtemplate"]);
				break;
			case 5 :
				showDocumentAssociated(COMPUTER_TYPE,$tab["ID"],$tab["withtemplate"]);
				break;
			default :
				if (!display_plugin_action(COMPUTER_TYPE,$tab["ID"],$_SESSION['glpi_onglet'], $tab["withtemplate"]))
					showDeviceComputerForm($_SERVER["PHP_SELF"],$tab["ID"], $tab["withtemplate"]);	
				break;
			}
		}
	}
	} else {

	if (haveRight("delete_ticket","1")&&isset($_POST["delete_inter"])&&!empty($_POST["todel"])){
		$job=new Job();
		foreach ($_POST["todel"] as $key => $val){
			if ($val==1) {
				$jopb->delete($key);
			}
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
				showInfocomForm($cfg_glpi["root_doc"]."/infocoms/infocoms-info-form.php",COMPUTER_TYPE,$tab["ID"]);
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
				showConnections($_SERVER["PHP_SELF"],$tab["ID"]);
				showPorts($tab["ID"], COMPUTER_TYPE);
				showPortsAdd($tab["ID"],COMPUTER_TYPE);
				break;
			case 4 :
				showInfocomForm($cfg_glpi["root_doc"]."/infocoms/infocoms-info-form.php",COMPUTER_TYPE,$tab["ID"]);
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
				showNotesForm($_SERVER["PHP_SELF"],COMPUTER_TYPE,$tab["ID"]);
				break;
			case 11 :
				showDeviceReservations($_SERVER["PHP_SELF"],COMPUTER_TYPE,$tab["ID"]);
				break;
			case 12 :
				showHistory(COMPUTER_TYPE,$tab["ID"]);
				break;
			case 13 :
				include ($phproot."/glpi/includes_search.php");
				ocsEditLock($_SERVER["PHP_SELF"],$tab["ID"]);
				break;
			default :
				if (!display_plugin_action(COMPUTER_TYPE,$tab["ID"],$_SESSION['glpi_onglet'],$tab["withtemplate"]))
					showDeviceComputerForm($_SERVER["PHP_SELF"],$tab["ID"], $tab["withtemplate"]);			
				break;
			}
			
		}
	}
	commonFooter();
}
?>
