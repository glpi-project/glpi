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
include ($phproot . "/glpi/includes_monitors.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_reservation.php");
include ($phproot . "/glpi/includes_tracking.php");
include ($phproot . "/glpi/includes_financial.php");
include ($phproot . "/glpi/includes_documents.php");
include ($phproot . "/glpi/includes_state.php");
include ($phproot . "/glpi/includes_users.php");
include ($phproot . "/glpi/includes_links.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(empty($tab["ID"])) $tab["ID"] = "";
if(!isset($tab["withtemplate"])) $tab["withtemplate"] = "";


if (isset($_POST["add"]))
{
	checkAuthentication("admin");
	$newID=addMonitor($_POST);
	logEvent($newID, "monitors", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][20]." ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($tab["delete"]))
{
	checkAuthentication("admin");
	if (!empty($tab["withtemplate"]))
		deleteMonitor($tab,1);
	else deleteMonitor($tab);
	
	logEvent($tab["ID"], "monitors", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][22]);
	if(!empty($tab["withtemplate"])) 
		glpi_header($cfg_install["root"]."/setup/setup-templates.php");
	 else 
	glpi_header($cfg_install["root"]."/monitors/");
}
else if (isset($_POST["restore"]))
{
	checkAuthentication("admin");
	restoreMonitor($_POST);
	logEvent($tab["ID"], "monitors", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][23]);
	glpi_header($cfg_install["root"]."/monitors/");
}
else if (isset($tab["purge"]))
{
	checkAuthentication("admin");
	deleteMonitor($tab,1);
	updateState(MONITOR_TYPE,$tab["ID"],0);
	logEvent($tab["ID"], "monitors", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][24]);
	glpi_header($cfg_install["root"]."/monitors/");
}
else if (isset($_POST["update"]))
{
	checkAuthentication("admin");
	updateMonitor($_POST);
	logEvent($_POST["ID"], "monitors", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($tab["disconnect"]))
{
	checkAuthentication("admin");
	Disconnect($tab["ID"]);
	logEvent(0, "monitors", 5, "inventory", $_SESSION["glpiname"]." ".$lang["log"][26]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if(isset($tab["connect"])&&isset($tab["item"])&&$tab["item"]>0)
{
	checkAuthentication("admin");
	Connect($_SERVER["PHP_SELF"],$tab["sID"],$tab["item"],MONITOR_TYPE);
	logEvent($tab["sID"], "monitors", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][27]);
	glpi_header($cfg_install["root"]."/monitors/monitors-info-form.php?ID=".$tab["sID"]);

/*
 	if($tab["connect"]==1)
	{
		checkAuthentication("admin");
		commonHeader($lang["title"][18],$_SERVER["PHP_SELF"]);
		showConnectSearch($_SERVER["PHP_SELF"],$tab["ID"]);
		commonFooter();
	}
	else if ($tab["connect"]==2)
	{
		checkAuthentication("admin");
		commonHeader($lang["title"][18],$_SERVER["PHP_SELF"]);
		listConnectComputers($_SERVER["PHP_SELF"],$tab);
		commonFooter();
	}
	else if ($tab["connect"]==3)
	{
		checkAuthentication("admin");
		Connect($_SERVER["PHP_SELF"],$tab["sID"],$tab["cID"],MONITOR_TYPE);
		logEvent($tab["sID"], "monitors", 5, "inventory", $_SESSION["glpiname"]." connected item.");
		glpi_header($cfg_install["root"]."/monitors/monitors-info-form.php?ID=".$tab["sID"]);
	}
*/
}
else
{
	checkAuthentication("normal");
	
	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
//		glpi_header($_SERVER['HTTP_REFERER']);
	}

	commonHeader($lang["title"][18],$_SERVER["PHP_SELF"]);
	$ci=new CommonItem();
	if ($ci->getFromDB(MONITOR_TYPE,$tab["ID"]))
	showMonitorOnglets($_SERVER["PHP_SELF"]."?ID=".$tab["ID"], $tab["withtemplate"],$_SESSION['glpi_onglet'] );
	if (!empty($tab["withtemplate"])) {

		if (showMonitorsForm($_SERVER["PHP_SELF"],$tab["ID"], $tab["withtemplate"])){
		if (!empty($tab["ID"])){
		switch($_SESSION['glpi_onglet']){
			case 4 :
				showInfocomForm($cfg_install["root"]."/infocoms/infocoms-info-form.php",MONITOR_TYPE,$tab["ID"],1,$tab["withtemplate"]);
				showContractAssociated(MONITOR_TYPE,$tab["ID"],$tab["withtemplate"]);
				break;
			case 5 :			
				showDocumentAssociated(MONITOR_TYPE,$tab["ID"],$tab["withtemplate"]);
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

		if (showMonitorsForm($_SERVER["PHP_SELF"],$tab["ID"])){
		switch($_SESSION['glpi_onglet']){
			case -1:
				showConnect($_SERVER["PHP_SELF"],$tab['ID'],MONITOR_TYPE);
				showInfocomForm($cfg_install["root"]."/infocoms/infocoms-info-form.php",MONITOR_TYPE,$tab["ID"]);
				showContractAssociated(MONITOR_TYPE,$tab["ID"]);			
				showDocumentAssociated(COMPUTER_TYPE,$tab["ID"]);	
				showJobListForItem($_SESSION["glpiname"],MONITOR_TYPE,$tab["ID"]);
				showOldJobListForItem($_SESSION["glpiname"],MONITOR_TYPE,$tab["ID"]);	
				showLinkOnDevice(MONITOR_TYPE,$tab["ID"]);
				break;
			case 4 :			
				showInfocomForm($cfg_install["root"]."/infocoms/infocoms-info-form.php",MONITOR_TYPE,$tab["ID"]);
				showContractAssociated(MONITOR_TYPE,$tab["ID"]);			
				break;
			case 5 :			
				showDocumentAssociated(MONITOR_TYPE,$tab["ID"]);	
				break;
			case 6 :			
				showJobListForItem($_SESSION["glpiname"],MONITOR_TYPE,$tab["ID"]);
				showOldJobListForItem($_SESSION["glpiname"],MONITOR_TYPE,$tab["ID"]);	
				break;
			case 7 :
				showLinkOnDevice(MONITOR_TYPE,$tab["ID"]);
				break;				
			default :
				showConnect($_SERVER["PHP_SELF"],$tab['ID'],MONITOR_TYPE);
				break;	
		}
			
			
			
			
		}
	}
	commonFooter();
}


?>
