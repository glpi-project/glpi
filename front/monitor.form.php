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
$NEEDED_ITEMS=array("monitor","computer","reservation","tracking","infocom","contract","document","state","user","link","enterprise");
include ($phproot . "/inc/includes.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(empty($tab["ID"])) $tab["ID"] = "";
if(!isset($tab["withtemplate"])) $tab["withtemplate"] = "";

$monitor=new Monitor();
if (isset($_POST["add"]))
{
	checkRight("monitor","w");

	$newID=$monitor->add($_POST);
	logEvent($newID, "monitors", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][20]." ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($tab["delete"]))
{
	checkRight("monitor","w");

	if (!empty($tab["withtemplate"]))
		$monitor->delete($tab,1);
	else $monitor->delete($tab);

	logEvent($tab["ID"], "monitors", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][22]);
	if(!empty($tab["withtemplate"])) 
		glpi_header($cfg_glpi["root_doc"]."/front/setup.templates.php");
	else 
		glpi_header($cfg_glpi["root_doc"]."/front/monitor.php");
}
else if (isset($_POST["restore"]))
{
	checkRight("monitor","w");

	$monitor->restore($_POST);
	logEvent($tab["ID"], "monitors", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][23]);
	glpi_header($cfg_glpi["root_doc"]."/front/monitor.php");
}
else if (isset($tab["purge"]))
{
	checkRight("monitor","w");

	$monitor->delete($tab,1);
	logEvent($tab["ID"], "monitors", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][24]);
	glpi_header($cfg_glpi["root_doc"]."/front/monitor.php");
}
else if (isset($_POST["update"]))
{
	checkRight("monitor","w");

	$monitor->update($_POST);
	logEvent($_POST["ID"], "monitors", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($tab["unglobalize"]))
{
	checkRight("monitor","w");

	unglobalizeDevice(MONITOR_TYPE,$tab["ID"]);
	logEvent($tab["ID"], "monitors", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][60]);
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

	Connect($tab["sID"],$tab["item"],MONITOR_TYPE);
	logEvent($tab["sID"], "monitors", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][27]);
	glpi_header($cfg_glpi["root_doc"]."/front/monitor.form.php?ID=".$tab["sID"]);

}
else
{
	checkRight("monitor","r");

	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
		//		glpi_header($_SERVER['HTTP_REFERER']);
	}

	commonHeader($lang["title"][18],$_SERVER['PHP_SELF']);
	if ($monitor->getFromDB($tab["ID"]))
		$monitor->showOnglets($_SERVER['PHP_SELF']."?ID=".$tab["ID"], $tab["withtemplate"],$_SESSION['glpi_onglet'] );
	if (!empty($tab["withtemplate"])) {

		if ($monitor->showForm($_SERVER['PHP_SELF'],$tab["ID"], $tab["withtemplate"])){
			if (!empty($tab["ID"])){
				switch($_SESSION['glpi_onglet']){
					case 4 :
						showInfocomForm($cfg_glpi["root_doc"]."/front/infocom.form.php",MONITOR_TYPE,$tab["ID"],1,$tab["withtemplate"]);
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
			$job=new Job();
			foreach ($_POST["todel"] as $key => $val){
				if ($val==1) {
					$job->delete(array("ID"=>$key));
				}
			}
		}

		if ($monitor->showForm($_SERVER['PHP_SELF'],$tab["ID"])){
			switch($_SESSION['glpi_onglet']){
				case -1:
					showConnect($_SERVER['PHP_SELF'],$tab['ID'],MONITOR_TYPE);
					showInfocomForm($cfg_glpi["root_doc"]."/front/infocom.form.php",MONITOR_TYPE,$tab["ID"]);
					showContractAssociated(MONITOR_TYPE,$tab["ID"]);			
					showDocumentAssociated(COMPUTER_TYPE,$tab["ID"]);	
					showJobListForItem($_SESSION["glpiname"],MONITOR_TYPE,$tab["ID"]);
					showOldJobListForItem($_SESSION["glpiname"],MONITOR_TYPE,$tab["ID"]);	
					showLinkOnDevice(MONITOR_TYPE,$tab["ID"]);
					display_plugin_action(MONITOR_TYPE,$tab["ID"],$_SESSION['glpi_onglet'],$tab["withtemplate"]);
					break;
				case 4 :			
					showInfocomForm($cfg_glpi["root_doc"]."/front/infocom.form.php",MONITOR_TYPE,$tab["ID"]);
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
					showNotesForm($_SERVER['PHP_SELF'],MONITOR_TYPE,$tab["ID"]);
					break;	
				case 11 :
					showDeviceReservations($_SERVER['PHP_SELF'],MONITOR_TYPE,$tab["ID"]);
					break;
				case 12 :
					showHistory(MONITOR_TYPE,$tab["ID"]);
					break;	
				default :
					if (!display_plugin_action(MONITOR_TYPE,$tab["ID"],$_SESSION['glpi_onglet'],$tab["withtemplate"]))
						showConnect($_SERVER['PHP_SELF'],$tab['ID'],MONITOR_TYPE);
					break;	
			}




		}
	}
	commonFooter();
}


?>
