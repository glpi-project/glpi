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

$mon=new Monitor();
if (isset($_POST["add"]))
{
	checkRight("monitor","w");

	$newID=$mon->add($_POST);
	logEvent($newID, "monitors", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][20]." ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($tab["delete"]))
{
	checkRight("monitor","w");

	if (!empty($tab["withtemplate"]))
		$mon->delete($tab,1);
	else $mon->delete($tab);
	
	logEvent($tab["ID"], "monitors", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][22]);
	if(!empty($tab["withtemplate"])) 
		glpi_header($cfg_glpi["root_doc"]."/setup/setup-templates.php");
	 else 
	glpi_header($cfg_glpi["root_doc"]."/monitors/");
}
else if (isset($_POST["restore"]))
{
	checkRight("monitor","w");

	$mon->restore($_POST);
	logEvent($tab["ID"], "monitors", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][23]);
	glpi_header($cfg_glpi["root_doc"]."/monitors/");
}
else if (isset($tab["purge"]))
{
	checkRight("monitor","w");

	$mon->delete($tab,1);
	logEvent($tab["ID"], "monitors", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][24]);
	glpi_header($cfg_glpi["root_doc"]."/monitors/");
}
else if (isset($_POST["update"]))
{
	checkRight("monitor","w");

	$mon->update($_POST);
	logEvent($_POST["ID"], "monitors", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($tab["disconnect"]))
{
	checkRight("monitor","w");

	Disconnect($tab["ID"]);
	logEvent(0, "monitors", 5, "inventory", $_SESSION["glpiname"]." ".$lang["log"][26]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if(isset($tab["connect"])&&isset($tab["item"])&&$tab["item"]>0)
{
	checkRight("monitor","w");

	Connect($_SERVER["PHP_SELF"],$tab["sID"],$tab["item"],MONITOR_TYPE);
	logEvent($tab["sID"], "monitors", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][27]);
	glpi_header($cfg_glpi["root_doc"]."/monitors/monitors-info-form.php?ID=".$tab["sID"]);

}
else
{
	checkRight("monitor","r");
	
	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
//		glpi_header($_SERVER['HTTP_REFERER']);
	}

	commonHeader($lang["title"][18],$_SERVER["PHP_SELF"]);
	if ($mon->getFromDB($tab["ID"]))
		$mon->showOnglets($_SERVER["PHP_SELF"]."?ID=".$tab["ID"], $tab["withtemplate"],$_SESSION['glpi_onglet'] );
	if (!empty($tab["withtemplate"])) {

		if (showMonitorsForm($_SERVER["PHP_SELF"],$tab["ID"], $tab["withtemplate"])){
		if (!empty($tab["ID"])){
		switch($_SESSION['glpi_onglet']){
			case 4 :
				showInfocomForm($cfg_glpi["root_doc"]."/infocoms/infocoms-info-form.php",MONITOR_TYPE,$tab["ID"],1,$tab["withtemplate"]);
				showContractAssociated(MONITOR_TYPE,$tab["ID"],$tab["withtemplate"]);
				break;
			case 5 :			
				showDocumentAssociated(MONITOR_TYPE,$tab["ID"],$tab["withtemplate"]);
				break;
			default :
				display_plugin_action(MONITOR_TYPE,$tab["ID"],$_SESSION['glpi_onglet'],$tab["withtemplate"]);
				break;
		}
		
		
		}
		}
		
	} else {
		if (haveRight("delete_ticket","1")&&isset($_POST["delete_inter"])&&!empty($_POST["todel"])){
			foreach ($_POST["todel"] as $key => $val){
				if ($val==1) {
					deleteTracking($key);
				}
			}
		}

		if (showMonitorsForm($_SERVER["PHP_SELF"],$tab["ID"])){
		switch($_SESSION['glpi_onglet']){
			case -1:
				showConnect($_SERVER["PHP_SELF"],$tab['ID'],MONITOR_TYPE);
				showInfocomForm($cfg_glpi["root_doc"]."/infocoms/infocoms-info-form.php",MONITOR_TYPE,$tab["ID"]);
				showContractAssociated(MONITOR_TYPE,$tab["ID"]);			
				showDocumentAssociated(COMPUTER_TYPE,$tab["ID"]);	
				showJobListForItem($_SESSION["glpiname"],MONITOR_TYPE,$tab["ID"]);
				showOldJobListForItem($_SESSION["glpiname"],MONITOR_TYPE,$tab["ID"]);	
				showLinkOnDevice(MONITOR_TYPE,$tab["ID"]);
				break;
			case 4 :			
				showInfocomForm($cfg_glpi["root_doc"]."/infocoms/infocoms-info-form.php",MONITOR_TYPE,$tab["ID"]);
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
			case 10 :
				showNotesForm($_SERVER["PHP_SELF"],MONITOR_TYPE,$tab["ID"]);
				break;	
			case 11 :
				showDeviceReservations($_SERVER["PHP_SELF"],MONITOR_TYPE,$tab["ID"]);
				break;
			case 12 :
				showHistory(MONITOR_TYPE,$tab["ID"]);
				break;	
			default :
				if (!display_plugin_action(MONITOR_TYPE,$tab["ID"],$_SESSION['glpi_onglet'],$tab["withtemplate"]))
					showConnect($_SERVER["PHP_SELF"],$tab['ID'],MONITOR_TYPE);
				break;	
		}
			
			
			
			
		}
	}
	commonFooter();
}


?>
