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
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_reservation.php");
include ($phproot . "/glpi/includes_tracking.php");
include ($phproot . "/glpi/includes_financial.php");
include ($phproot . "/glpi/includes_documents.php");
include ($phproot . "/glpi/includes_state.php");
include ($phproot . "/glpi/includes_users.php");
include ($phproot . "/glpi/includes_links.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";
if(!isset($tab["withtemplate"])) $tab["withtemplate"] = "";

if (isset($_POST["add"]))
{
	checkAuthentication("admin");
	$newID=addNetdevice($_POST);
	logEvent($newID, "networking", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][20]." :  ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($tab["delete"]))
{
	checkAuthentication("admin");
	if (!empty($tab["withtemplate"]))
		deleteNetdevice($tab,1);
	else deleteNetdevice($tab);

	logEvent($tab["ID"], "networking", 4, "inventory", $_SESSION["glpiname"] ." ".$lang["log"][22]);
	if(!empty($tab["withtemplate"])) 
		glpi_header($cfg_install["root"]."/setup/setup-templates.php");
	 else 
	glpi_header($cfg_install["root"]."/networking/");
}
else if (isset($_POST["restore"]))
{
	checkAuthentication("admin");
	restoreNetdevice($_POST);
	logEvent($tab["ID"], "networking", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][23]);
	glpi_header($cfg_install["root"]."/networking/");
}
else if (isset($tab["purge"]))
{
	checkAuthentication("admin");
	deleteNetdevice($tab,1);
	updateState(NETWORKING_TYPE,$tab["ID"],0);
	logEvent($tab["ID"], "networking", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][24]);
	glpi_header($cfg_install["root"]."/networking/");
}
else if (isset($_POST["update"]))
{
	checkAuthentication("admin");
	updateNetdevice($_POST);
	logEvent($_POST["ID"], "networking", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else
{
	checkAuthentication("normal");

	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
//		glpi_header($_SERVER['HTTP_REFERER']);
	}

	commonHeader($lang["title"][6],$_SERVER["PHP_SELF"]);
	
	$ci=new CommonItem();
	if ($ci->getFromDB(NETWORKING_TYPE,$tab["ID"]))
		showNetworkingOnglets($_SERVER["PHP_SELF"]."?ID=".$tab["ID"], $tab["withtemplate"],$_SESSION['glpi_onglet'] );
	
	if (!empty($tab["withtemplate"])) {

		if (showNetworkingForm($_SERVER["PHP_SELF"],$tab["ID"], $tab["withtemplate"])){
		if (!empty($tab["ID"])){
			switch($_SESSION['glpi_onglet']){
			case 4 :
				showInfocomForm($cfg_install["root"]."/infocoms/infocoms-info-form.php",NETWORKING_TYPE,$tab["ID"],1,$tab["withtemplate"]);
				showContractAssociated(NETWORKING_TYPE,$tab["ID"],$tab["withtemplate"]);
				break;
			case 5 :
				showDocumentAssociated(NETWORKING_TYPE,$tab["ID"],$tab["withtemplate"]);		
				break;
			default :
				showPorts($tab["ID"], NETWORKING_TYPE,$tab["withtemplate"]);
				if ($tab["withtemplate"]!=2) showPortsAdd($tab["ID"],NETWORKING_TYPE);
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

	
		if (showNetworkingForm ($_SERVER["PHP_SELF"],$tab["ID"])){
		switch($_SESSION['glpi_onglet']){
			case -1:
				showPorts($tab["ID"],NETWORKING_TYPE);
				showPortsAdd($tab["ID"],NETWORKING_TYPE);
				showInfocomForm($cfg_install["root"]."/infocoms/infocoms-info-form.php",NETWORKING_TYPE,$tab["ID"]);
				showContractAssociated(NETWORKING_TYPE,$tab["ID"]);
				showDocumentAssociated(NETWORKING_TYPE,$tab["ID"],$tab["withtemplate"]);
				showJobListForItem($_SESSION["glpiname"],NETWORKING_TYPE,$tab["ID"]);
				showOldJobListForItem($_SESSION["glpiname"],NETWORKING_TYPE,$tab["ID"]);
				showLinkOnDevice(NETWORKING_TYPE,$tab["ID"]);
				break;
			case 4 :
				showInfocomForm($cfg_install["root"]."/infocoms/infocoms-info-form.php",NETWORKING_TYPE,$tab["ID"]);
				showContractAssociated(NETWORKING_TYPE,$tab["ID"]);
				break;
			case 5 :
				showDocumentAssociated(NETWORKING_TYPE,$tab["ID"],$tab["withtemplate"]);
				break;
			case 6 :
				showJobListForItem($_SESSION["glpiname"],NETWORKING_TYPE,$tab["ID"]);
				showOldJobListForItem($_SESSION["glpiname"],NETWORKING_TYPE,$tab["ID"]);
				break;
			case 7 :
				showLinkOnDevice(NETWORKING_TYPE,$tab["ID"]);
				break;				
			default :
				showPorts($tab["ID"],NETWORKING_TYPE);
				showPortsAdd($tab["ID"],NETWORKING_TYPE);
				break;
			}

		
		}
	}
	commonFooter();
}



?>
