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
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_reservation.php");
include ($phproot . "/glpi/includes_tracking.php");
include ($phproot . "/glpi/includes_cartridges.php");
include ($phproot . "/glpi/includes_financial.php");
include ($phproot . "/glpi/includes_documents.php");
include ($phproot . "/glpi/includes_state.php");
include ($phproot . "/glpi/includes_users.php");
include ($phproot . "/glpi/includes_links.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";
if(!isset($tab["withtemplate"])) $tab["withtemplate"] = "";

$print=new Printer();
if (isset($_POST["add"]))
{
	checkRight("printer","w");
	
	$newID=$print->add($_POST);
	logEvent($newID, "printers", 4, "inventory", $_SESSION["glpiname"]."  ".$lang["log"][20]."  ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($tab["delete"]))
{
	checkRight("printer","w");

	if (!empty($tab["withtemplate"]))
		$print->delete($tab,1);
	else $print->delete($tab);
	logEvent($tab["ID"], "printers", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][22]);
	if(!empty($tab["withtemplate"])) 
		glpi_header($cfg_glpi["root_doc"]."/setup/setup-templates.php");
	 else 
		glpi_header($cfg_glpi["root_doc"]."/printers/");
}
else if (isset($_POST["restore"]))
{
	checkRight("printer","w");
	$print->restore($_POST);
	logEvent($tab["ID"], "printers", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][23]);
	glpi_header($cfg_glpi["root_doc"]."/printers/");
}
else if (isset($tab["purge"]))
{
	checkRight("printer","w");
	$print->delete($tab,1);
	logEvent($tab["ID"], "printers", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][24]);
	glpi_header($cfg_glpi["root_doc"]."/printers/");
}
else if (isset($_POST["update"]))
{
	checkRight("printer","w");
	$print->update($_POST);
	logEvent($_POST["ID"], "printers", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($tab["disconnect"]))
{
	checkRight("printer","w");
	Disconnect($tab["ID"]);
	logEvent(0, "printers", 5, "inventory", $_SESSION["glpiname"]."  ".$lang["log"][26]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if(isset($tab["connect"])&&isset($tab["item"])&&$tab["item"]>0)
{

	checkRight("printer","w");
	Connect($_SERVER["PHP_SELF"],$tab["sID"],$tab["item"],PRINTER_TYPE);
	logEvent($tab["sID"], "printers", 4, "inventory", $_SESSION["glpiname"]."  ".$lang["log"][27]);
	glpi_header($cfg_glpi["root_doc"]."/printers/printers-info-form.php?ID=".$tab["sID"]);
}
else
{
	checkRight("printer","r");
	
	commonHeader($lang["title"][8],$_SERVER["PHP_SELF"]);

if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
if (isset($_GET['onglet'])) {
	$_SESSION['glpi_onglet']=$_GET['onglet'];
//		glpi_header($_SERVER['HTTP_REFERER']);
}	
	
	if ($print->getFromDB($tab["ID"]))
		$print->showOnglets($_SERVER["PHP_SELF"]."?ID=".$tab["ID"], $tab["withtemplate"],$_SESSION['glpi_onglet'] );

	if (!empty($tab["withtemplate"])) {
		
		if (showPrintersForm($_SERVER["PHP_SELF"],$tab["ID"], $tab["withtemplate"])){
		
			if (!empty($tab["ID"])){
			switch($_SESSION['glpi_onglet']){
				case 3 :
					showPorts($tab["ID"], PRINTER_TYPE,$tab["withtemplate"]);
					if ($tab["withtemplate"]!=2)	showPortsAdd($tab["ID"],PRINTER_TYPE);
					break;

				case 4 :			
					showInfocomForm($cfg_glpi["root_doc"]."/infocoms/infocoms-info-form.php",PRINTER_TYPE,$tab["ID"],1,$tab["withtemplate"]);	
					showContractAssociated(PRINTER_TYPE,$tab["ID"],$tab["withtemplate"]);
					break;
				case 5 :
					showDocumentAssociated(PRINTER_TYPE,$tab["ID"],$tab["withtemplate"]);	
					break;
				default :
					display_plugin_action(PRINTER_TYPE,$tab["ID"],$_SESSION['glpi_onglet'],$tab["withtemplate"]);
					break;
			}	
			
			
			
		
			
			}
		}
		
	} else {
		if (haveRight("delete_ticket","1"))
		if (isAdmin($_SESSION["glpitype"])&&isset($_POST["delete_inter"])&&!empty($_POST["todel"])){
			foreach ($_POST["todel"] as $key => $val){
				if ($val==1) {
					deleteTracking($key);
				}
			}
		}


		if (showPrintersForm($_SERVER["PHP_SELF"],$tab["ID"], $tab["withtemplate"])){
		
			switch($_SESSION['glpi_onglet']){
				case -1:
						showCartridgeInstalled($tab["ID"]);
						showCartridgeInstalled($tab["ID"],1);		
						showConnect($_SERVER["PHP_SELF"],$tab["ID"],PRINTER_TYPE);
						showPorts($tab["ID"], PRINTER_TYPE,$tab["withtemplate"]);
						showPortsAdd($tab["ID"],PRINTER_TYPE);	
						showInfocomForm($cfg_glpi["root_doc"]."/infocoms/infocoms-info-form.php",PRINTER_TYPE,$tab["ID"]);
						showContractAssociated(PRINTER_TYPE,$tab["ID"]);
						showDocumentAssociated(PRINTER_TYPE,$tab["ID"]);
						showJobListForItem($_SESSION["glpiname"],PRINTER_TYPE,$tab["ID"]);
						showOldJobListForItem($_SESSION["glpiname"],PRINTER_TYPE,$tab["ID"]);		
						showLinkOnDevice(PRINTER_TYPE,$tab["ID"]);
					break;
				case 3 :			
					showConnect($_SERVER["PHP_SELF"],$tab["ID"],PRINTER_TYPE);
					showPorts($tab["ID"], PRINTER_TYPE,$tab["withtemplate"]);
					showPortsAdd($tab["ID"],PRINTER_TYPE);	
					break;
				case 4 :	
					showInfocomForm($cfg_glpi["root_doc"]."/infocoms/infocoms-info-form.php",PRINTER_TYPE,$tab["ID"]);
					showContractAssociated(PRINTER_TYPE,$tab["ID"]);
					break;
				case 5 :
					showDocumentAssociated(PRINTER_TYPE,$tab["ID"]);
					break;
				case 6 :	
					showJobListForItem($_SESSION["glpiname"],PRINTER_TYPE,$tab["ID"]);
					showOldJobListForItem($_SESSION["glpiname"],PRINTER_TYPE,$tab["ID"]);		
					break;
				case 7 :
					showLinkOnDevice(PRINTER_TYPE,$tab["ID"]);
					break;	

				case 10 :
					showNotesForm($_SERVER["PHP_SELF"],PRINTER_TYPE,$tab["ID"]);
					break;
				case 11 :
					showDeviceReservations($_SERVER["PHP_SELF"],PRINTER_TYPE,$tab["ID"]);
					break;
				case 12 :
					showHistory(PRINTER_TYPE,$tab["ID"]);
				break;
				default :
					if (!display_plugin_action(PRINTER_TYPE,$tab["ID"],$_SESSION['glpi_onglet'],$tab["withtemplate"])){
						if (haveRight("cartridge","r")){
							showCartridgeInstalled($tab["ID"]);		
							showCartridgeInstalled($tab["ID"],1);
						}
					}
					break;
			}		
			
			
		}
	}
	commonFooter();
}


?>
