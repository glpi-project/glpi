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
$NEEDED_ITEMS=array("networking","reservation","tracking","document","state","user","link","phone","enterprise","infocom","contract");
include ($phproot . "/inc/includes.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";
if(!isset($tab["withtemplate"])) $tab["withtemplate"] = "";

$netdevice=new Netdevice();
if (isset($_POST["add"]))
{
	checkRight("networking","w");
	$newID=$netdevice->add($_POST);
	logEvent($newID, "networking", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][20]." :  ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($tab["delete"]))
{
	checkRight("networking","w");
	if (!empty($tab["withtemplate"]))
		$netdevice->delete($tab,1);
	else $netdevice->delete($tab);

	logEvent($tab["ID"], "networking", 4, "inventory", $_SESSION["glpiname"] ." ".$lang["log"][22]);
	if(!empty($tab["withtemplate"])) 
		glpi_header($cfg_glpi["root_doc"]."/front/setup.templates.php");
	else 
		glpi_header($cfg_glpi["root_doc"]."/front/networking.php");
}
else if (isset($_POST["restore"]))
{
	checkRight("networking","w");
	$netdevice->restore($_POST);
	logEvent($tab["ID"], "networking", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][23]);
	glpi_header($cfg_glpi["root_doc"]."/front/networking.php");
}
else if (isset($tab["purge"]))
{
	checkRight("networking","w");
	$netdevice->delete($tab,1);
	logEvent($tab["ID"], "networking", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][24]);
	glpi_header($cfg_glpi["root_doc"]."/front/networking.php");
}
else if (isset($_POST["update"]))
{
	checkRight("networking","w");
	$netdevice->update($_POST);
	logEvent($_POST["ID"], "networking", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else
{
	checkRight("networking","r");

	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
		//		glpi_header($_SERVER['HTTP_REFERER']);
	}

	commonHeader($lang["title"][6],$_SERVER['PHP_SELF']);

	if ($netdevice->getFromDB($tab["ID"]))
		$netdevice->showOnglets($_SERVER['PHP_SELF']."?ID=".$tab["ID"], $tab["withtemplate"],$_SESSION['glpi_onglet'] );

	if (!empty($tab["withtemplate"])) {

		if ($netdevice->showForm($_SERVER['PHP_SELF'],$tab["ID"], $tab["withtemplate"])){
			if (!empty($tab["ID"])){
				switch($_SESSION['glpi_onglet']){
					case 4 :
						showInfocomForm($cfg_glpi["root_doc"]."/front/infocom.form.php",NETWORKING_TYPE,$tab["ID"],1,$tab["withtemplate"]);
						showContractAssociated(NETWORKING_TYPE,$tab["ID"],$tab["withtemplate"]);
						break;
					case 5 :
						showDocumentAssociated(NETWORKING_TYPE,$tab["ID"],$tab["withtemplate"]);		
						break;

					default :
						if (!display_plugin_action(NETWORKING_TYPE,$tab["ID"],$_SESSION['glpi_onglet'],$tab["withtemplate"])){
							showPorts($tab["ID"], NETWORKING_TYPE,$tab["withtemplate"]);
							if ($tab["withtemplate"]!=2) showPortsAdd($tab["ID"],NETWORKING_TYPE);
						}
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


		if ($netdevice->showForm ($_SERVER['PHP_SELF'],$tab["ID"])){
			switch($_SESSION['glpi_onglet']){
				case -1:
					showPortsAdd($tab["ID"],NETWORKING_TYPE);
					showPorts($tab["ID"],NETWORKING_TYPE);
					showInfocomForm($cfg_glpi["root_doc"]."/front/infocom.form.php",NETWORKING_TYPE,$tab["ID"]);
					showContractAssociated(NETWORKING_TYPE,$tab["ID"]);
					showDocumentAssociated(NETWORKING_TYPE,$tab["ID"],$tab["withtemplate"]);
					showJobListForItem($_SESSION["glpiname"],NETWORKING_TYPE,$tab["ID"]);
					showOldJobListForItem($_SESSION["glpiname"],NETWORKING_TYPE,$tab["ID"]);
					showLinkOnDevice(NETWORKING_TYPE,$tab["ID"]);
					display_plugin_action(NETWORKING_TYPE,$tab["ID"],$_SESSION['glpi_onglet'],$tab["withtemplate"]);
					break;
				case 4 :
					showInfocomForm($cfg_glpi["root_doc"]."/front/infocom.form.php",NETWORKING_TYPE,$tab["ID"]);
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
				case 10 :
					showNotesForm($_SERVER['PHP_SELF'],NETWORKING_TYPE,$tab["ID"]);
					break;			
				case 11 :
					showDeviceReservations($_SERVER['PHP_SELF'],NETWORKING_TYPE,$tab["ID"]);
					break;
				case 12 :
					showHistory(NETWORKING_TYPE,$tab["ID"]);
					break;
				default :
					if (!display_plugin_action(NETWORKING_TYPE,$tab["ID"],$_SESSION['glpi_onglet'],$tab["withtemplate"])){
						showPortsAdd($tab["ID"],NETWORKING_TYPE);
						showPorts($tab["ID"],NETWORKING_TYPE);
					}
					break;
			}


		}
	}
	commonFooter();
}



?>
