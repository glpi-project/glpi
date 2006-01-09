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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_financial.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_monitors.php");
include ($phproot . "/glpi/includes_peripherals.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_software.php");
include ($phproot . "/glpi/includes_documents.php");
include ($phproot . "/glpi/includes_links.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";

if (isset($_POST["add"]))
{
	checkAuthentication("admin");

	$newID=addContract($_POST);
	logEvent($newID, "contracts", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][20]." ".$_POST["num"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
} 
else if (isset($_POST["delete"]))
{
	checkAuthentication("admin");
	deleteContract($_POST);
	logEvent($tab["ID"], "contracts", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][22]);
	glpi_header($cfg_install["root"]."/contracts/");
}
else if (isset($_POST["restore"]))
{
	checkAuthentication("admin");
	restoreContract($_POST);
	logEvent($tab["ID"], "contracts", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][23]);
	glpi_header($cfg_install["root"]."/contracts/");
}
else if (isset($_POST["purge"]))
{
	checkAuthentication("admin");
	deleteContract($_POST,1);
	logEvent($tab["ID"], "contracts", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][24]);
	glpi_header($cfg_install["root"]."/contracts/");
}
else if (isset($_POST["additem"])){
	checkAuthentication("admin");
	
	
	
	$template=0;
	if (isset($_POST["is_template"])) $template=1;
	
	if ($_POST['type']>0&&$_POST['item']>0){
		addDeviceContract($_POST["conID"],$_POST['type'],$_POST['item'],$template);
		logEvent($tab["ID"], "contracts", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][32]);
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_GET["deleteitem"])){
	checkAuthentication("admin");
	deleteDeviceContract($_GET["ID"]);
	logEvent($tab["ID"], "contracts", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][33]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["addenterprise"])){
	checkAuthentication("admin");

	addEnterpriseContract($_POST["conID"],$_POST["entID"]);
	logEvent($tab["ID"], "contracts", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][34]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_GET["deleteenterprise"])){
	checkAuthentication("admin");
	deleteEnterpriseContract($_GET["ID"]);
	logEvent($tab["ID"], "contracts", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][35]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["update"]))
{
	checkAuthentication("admin");
	updateContract($_POST);
	logEvent($_POST["ID"], "contracts", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
} 
else
{
	if (empty($tab["ID"]))
	checkAuthentication("admin");
	else checkAuthentication("normal");

	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
//		glpi_header($_SERVER['HTTP_REFERER']);
	}

	commonHeader($lang["title"][20],$_SERVER["PHP_SELF"]);

	
	$ci=new CommonItem();
	if ($ci->getFromDB(CONTRACT_TYPE,$tab["ID"]))
	showContractOnglets($_SERVER["PHP_SELF"]."?ID=".$tab["ID"], "",$_SESSION['glpi_onglet'] );

	if (showContractForm($_SERVER["PHP_SELF"],$tab["ID"])) {
		if (!empty($tab['ID']))
		switch($_SESSION['glpi_onglet']){
		case -1 :	
			showEnterpriseContract($tab["ID"]);
			showDeviceContract($tab["ID"]);
			showDocumentAssociated(CONTRACT_TYPE,$tab["ID"]);
			showLinkOnDevice(CONTACT_TYPE,$tab["ID"]);
			break;
		case 5 : 
			showDocumentAssociated(CONTRACT_TYPE,$tab["ID"]);
			break;
		case 7 : 
			showLinkOnDevice(CONTRACT_TYPE,$tab["ID"]);
			break;
		case 10 :
				showNotesForm($_SERVER["PHP_SELF"],CONTRACT_TYPE,$tab["ID"]);
				break;
		default :
			showEnterpriseContract($tab["ID"]);
			showDeviceContract($tab["ID"]);
		break;
		}
	}	
	
	
	commonFooter();
}

?>
