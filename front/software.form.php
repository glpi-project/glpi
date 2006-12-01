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

$NEEDED_ITEMS=array("computer","software","tracking","document","user","link","reservation","infocom","contract","enterprise");
include ($phproot . "/inc/includes.php");


if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";
if(!isset($tab["withtemplate"])) $tab["withtemplate"] = "";
if(!isset($tab["search_software"])) $tab["search_software"] = "";

$soft=new Software();
if (isset($_POST["add"]))
{
	checkRight("software","w");

	unset($_POST["search_software"]);

	$newID=$soft->add($_POST);
	logEvent($newID, "software", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][20]." ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($tab["delete"]))
{
	checkRight("software","w");

	if (!empty($tab["withtemplate"]))
		$soft->delete($tab,1);
	else $soft->delete($tab);

	logEvent($tab["ID"], "software", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][22]);
	if(!empty($tab["withtemplate"])) 
		glpi_header($cfg_glpi["root_doc"]."/front/setup.templates.php");
	else 
		glpi_header($cfg_glpi["root_doc"]."/front/software.php");
}
else if (isset($_POST["restore"]))
{
	checkRight("software","w");

	$soft->restore($_POST);
	logEvent($tab["ID"], "software", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][23]);
	glpi_header($cfg_glpi["root_doc"]."/front/software.php");
}
else if (isset($tab["purge"]))
{
	checkRight("software","w");

	$soft->delete($tab,1);
	logEvent($tab["ID"], "software", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][24]);
	glpi_header($cfg_glpi["root_doc"]."/front/software.php");
}
else if (isset($_POST["update"]))
{
	checkRight("software","w");

	unset($_POST["search_software"]);
	$soft->update($_POST);
	logEvent($_POST["ID"], "software", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
} 
else
{
	checkRight("software","r");

	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
		//glpi_header($_SERVER['HTTP_REFERER']);
	}

	commonHeader($lang["title"][12],$_SERVER['PHP_SELF']);

	if ($soft->getFromDB($tab["ID"]))
		$soft->showOnglets($_SERVER['PHP_SELF']."?ID=".$tab["ID"], $tab["withtemplate"],$_SESSION['glpi_onglet'] );

	if (!empty($tab["withtemplate"])) {

		if ($soft->showForm($_SERVER['PHP_SELF'],$tab["ID"],$tab['search_software'], $tab["withtemplate"])){

			if (!empty($tab["ID"])){
				switch($_SESSION['glpi_onglet']){
					case 4 :
						showInfocomForm($cfg_glpi["root_doc"]."/front/infocom.form.php",SOFTWARE_TYPE,$tab["ID"],1,$tab["withtemplate"]);
						showContractAssociated(SOFTWARE_TYPE,$tab["ID"],$tab["withtemplate"]);
						break;
					case 5 :
						showDocumentAssociated(SOFTWARE_TYPE,$tab["ID"],$tab["withtemplate"]);
						break;
					default :
						display_plugin_action(SOFTWARE_TYPE,$tab["ID"],$_SESSION['glpi_onglet'], $tab["withtemplate"]);
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

		if ($soft->showForm($_SERVER['PHP_SELF'],$tab["ID"],$tab['search_software'])){
			switch($_SESSION['glpi_onglet']){
				case -1:
					showLicensesAdd($tab["ID"]);
					showLicenses($tab["ID"]);
					showInfocomForm($cfg_glpi["root_doc"]."/front/infocom.form.php",SOFTWARE_TYPE,$tab["ID"]);
					showContractAssociated(SOFTWARE_TYPE,$tab["ID"]);
					showDocumentAssociated(SOFTWARE_TYPE,$tab["ID"]);
					showJobListForItem($_SESSION["glpiname"],SOFTWARE_TYPE,$tab["ID"]);
					showOldJobListForItem($_SESSION["glpiname"],SOFTWARE_TYPE,$tab["ID"]);
					showLinkOnDevice(SOFTWARE_TYPE,$tab["ID"]);
					display_plugin_action(SOFTWARE_TYPE,$tab["ID"],$_SESSION['glpi_onglet'],$tab["withtemplate"]);
					break;
				case 2 :
					showLicensesAdd($tab["ID"]);
					showLicenses($tab["ID"],1);
					break;
				case 4 :
					showInfocomForm($cfg_glpi["root_doc"]."/front/infocom.form.php",SOFTWARE_TYPE,$tab["ID"]);
					showContractAssociated(SOFTWARE_TYPE,$tab["ID"]);
					break;
				case 5 :
					showDocumentAssociated(SOFTWARE_TYPE,$tab["ID"]);
					break;
				case 6 :
					showJobListForItem($_SESSION["glpiname"],SOFTWARE_TYPE,$tab["ID"]);
					showOldJobListForItem($_SESSION["glpiname"],SOFTWARE_TYPE,$tab["ID"]);
					break;
				case 7 :
					showLinkOnDevice(SOFTWARE_TYPE,$tab["ID"]);
					break;	
				case 10 :
					showNotesForm($_SERVER['PHP_SELF'],SOFTWARE_TYPE,$tab["ID"]);
					break;				
				case 11 :
					showDeviceReservations($_SERVER['PHP_SELF'],SOFTWARE_TYPE,$tab["ID"]);
					break;
				case 12 :
					showHistory(SOFTWARE_TYPE,$tab["ID"]);
					break;
				default :
					if (!display_plugin_action(SOFTWARE_TYPE,$tab["ID"],$_SESSION['glpi_onglet'],$tab["withtemplate"])){
						showLicensesAdd($tab["ID"]);
						showLicenses($tab["ID"]);
					}
					break;
			}
		}
	}

	commonFooter();
}

?>
