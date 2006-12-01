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

$NEEDED_ITEMS=array("printer","computer","networking","reservation","tracking","cartridge","contract","infocom","document","state","user","link","enterprise");
include ($phproot . "/inc/includes.php");


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
		glpi_header($cfg_glpi["root_doc"]."/front/setup.templates.php");
	else 
		glpi_header($cfg_glpi["root_doc"]."/front/printer.php");
}
else if (isset($_POST["restore"]))
{
	checkRight("printer","w");
	$print->restore($_POST);
	logEvent($tab["ID"], "printers", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][23]);
	glpi_header($cfg_glpi["root_doc"]."/front/printer.php");
}
else if (isset($tab["purge"]))
{
	checkRight("printer","w");
	$print->delete($tab,1);
	logEvent($tab["ID"], "printers", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][24]);
	glpi_header($cfg_glpi["root_doc"]."/front/printer.php");
}
else if (isset($_POST["update"]))
{
	checkRight("printer","w");
	$print->update($_POST);
	logEvent($_POST["ID"], "printers", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($tab["unglobalize"]))
{
	checkRight("printer","w");

	unglobalizeDevice(PRINTER_TYPE,$tab["ID"]);
	logEvent($tab["ID"], "printers", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][60]);
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
	Connect($tab["sID"],$tab["item"],PRINTER_TYPE);
	logEvent($tab["sID"], "printers", 4, "inventory", $_SESSION["glpiname"]."  ".$lang["log"][27]);
	glpi_header($cfg_glpi["root_doc"]."/front/printer.form.php?ID=".$tab["sID"]);
}
else
{
	checkRight("printer","r");

	commonHeader($lang["title"][8],$_SERVER['PHP_SELF']);

	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
	}	

	if ($print->getFromDB($tab["ID"]))
		$print->showOnglets($_SERVER['PHP_SELF']."?ID=".$tab["ID"], $tab["withtemplate"],$_SESSION['glpi_onglet'] );

	if (!empty($tab["withtemplate"])) {

		if ($print->showForm($_SERVER['PHP_SELF'],$tab["ID"], $tab["withtemplate"])){

			if (!empty($tab["ID"])){
				switch($_SESSION['glpi_onglet']){
					case 3 :
						if ($tab["withtemplate"]!=2)	showPortsAdd($tab["ID"],PRINTER_TYPE);
						showPorts($tab["ID"], PRINTER_TYPE,$tab["withtemplate"]);
						break;

					case 4 :			
						showInfocomForm($cfg_glpi["root_doc"]."/front/infocom.form.php",PRINTER_TYPE,$tab["ID"],1,$tab["withtemplate"]);	
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
		if (haveRight("delete_ticket","1")&&isset($_POST["delete_inter"])&&!empty($_POST["todel"])){
			$job=new Job();
			foreach ($_POST["todel"] as $key => $val){
				if ($val==1) {
					$job->delete(array("ID"=>$key));
				}
			}
		}


		if ($print->showForm($_SERVER['PHP_SELF'],$tab["ID"], $tab["withtemplate"])){

			switch($_SESSION['glpi_onglet']){
				case -1:
					showCartridgeInstalled($tab["ID"]);
					showCartridgeInstalled($tab["ID"],1);		
					showConnect($_SERVER['PHP_SELF'],$tab["ID"],PRINTER_TYPE);
					showPortsAdd($tab["ID"],PRINTER_TYPE);	
					showPorts($tab["ID"], PRINTER_TYPE,$tab["withtemplate"]);
					showInfocomForm($cfg_glpi["root_doc"]."/front/infocom.form.php",PRINTER_TYPE,$tab["ID"]);
					showContractAssociated(PRINTER_TYPE,$tab["ID"]);
					showDocumentAssociated(PRINTER_TYPE,$tab["ID"]);
					showJobListForItem($_SESSION["glpiname"],PRINTER_TYPE,$tab["ID"]);
					showOldJobListForItem($_SESSION["glpiname"],PRINTER_TYPE,$tab["ID"]);		
					showLinkOnDevice(PRINTER_TYPE,$tab["ID"]);
					display_plugin_action(PRINTER_TYPE,$tab["ID"],$_SESSION['glpi_onglet'],$tab["withtemplate"]);
					break;
				case 3 :			
					showConnect($_SERVER['PHP_SELF'],$tab["ID"],PRINTER_TYPE);
					showPortsAdd($tab["ID"],PRINTER_TYPE);	
					showPorts($tab["ID"], PRINTER_TYPE,$tab["withtemplate"]);
					break;
				case 4 :	
					showInfocomForm($cfg_glpi["root_doc"]."/front/infocom.form.php",PRINTER_TYPE,$tab["ID"]);
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
					showNotesForm($_SERVER['PHP_SELF'],PRINTER_TYPE,$tab["ID"]);
					break;
				case 11 :
					showDeviceReservations($_SERVER['PHP_SELF'],PRINTER_TYPE,$tab["ID"]);
					break;
				case 12 :
					showHistory(PRINTER_TYPE,$tab["ID"]);
					break;
				default :
					if (!display_plugin_action(PRINTER_TYPE,$tab["ID"],$_SESSION['glpi_onglet'],$tab["withtemplate"])){
						showCartridgeInstalled($tab["ID"]);		
						showCartridgeInstalled($tab["ID"],1);
					}
					break;
			}		


		}
	}
	commonFooter();
}


?>
