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
include ($phproot . "/glpi/includes_documents.php");
include ($phproot . "/glpi/includes_tracking.php");
include ($phproot . "/glpi/includes_users.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_monitors.php");
include ($phproot . "/glpi/includes_peripherals.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_software.php");
include ($phproot . "/glpi/includes_links.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";

if (isset($_GET["start"])) $start=$_GET["start"];
else $start=0;


if (isset($_POST["add"]))
{
	checkAuthentication("admin");

	$newID=addEnterprise($_POST);
	logEvent($newID, "enterprises", 4, "financial", $_SESSION["glpiname"]." added item ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
} 
else if (isset($_POST["delete"]))
{
	checkAuthentication("admin");
	deleteEnterprise($_POST);
	logEvent($tab["ID"], "enterprises", 4, "financial", $_SESSION["glpiname"]." deleted item.");
	glpi_header($cfg_install["root"]."/enterprises/");
}
else if (isset($_POST["restore"]))
{
	checkAuthentication("admin");
	restoreEnterprise($_POST);
	logEvent($tab["ID"], "enterprises", 4, "financial", $_SESSION["glpiname"]." restored item.");
	glpi_header($cfg_install["root"]."/enterprises/");
}
else if (isset($_POST["purge"]))
{
	checkAuthentication("admin");
	deleteEnterprise($_POST,1);
	logEvent($tab["ID"], "enterprises", 4, "financial", $_SESSION["glpiname"]." purge item.");
	glpi_header($cfg_install["root"]."/enterprises/");
}
else if (isset($_POST["addcontact"])){
	checkAuthentication("admin");
	addContactEnterprise($_POST["eID"],$_POST["cID"]);
	logEvent($tab["ID"], "enterprises", 4, "financial", $_SESSION["glpiname"]." associate contact.");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_GET["deletecontact"])){
	checkAuthentication("admin");
	deleteContactEnterprise($_GET["ID"]);
	logEvent(0, "enterprises", 4, "financial", $_SESSION["glpiname"]." delete associate contact.");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["update"]))
{
	checkAuthentication("admin");
	updateEnterprise($_POST);
	logEvent($_POST["ID"], "enterprises", 4, "financial", $_SESSION["glpiname"]." updated item.");
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


	commonHeader($lang["title"][23],$_SERVER["PHP_SELF"]);
	$ci=new CommonItem();
	if ($ci->getFromDB(ENTERPRISE_TYPE,$tab["ID"]))
		showEnterpriseOnglets($_SERVER["PHP_SELF"]."?ID=".$tab["ID"], "",$_SESSION['glpi_onglet'] );

	if (showEnterpriseForm($_SERVER["PHP_SELF"],$tab["ID"])){
		if (!empty($tab["ID"]))
		switch($_SESSION['glpi_onglet']){
			case -1:
				showAssociatedContact($tab["ID"]);
				showContractAssociatedEnterprise($tab["ID"]);
				showDocumentAssociated(ENTERPRISE_TYPE,$tab["ID"]);
				showJobList($_SERVER["PHP_SELF"],$_GET["ID"],"enterprise","","","",$start);
				showLinkOnDevice(ENTERPRISE_TYPE,$tab["ID"]);
				break;
			case 1 :
				showAssociatedContact($tab["ID"]);
				break;
			case 4 :
				showContractAssociatedEnterprise($tab["ID"]);
				break;
			case 5 :
				showDocumentAssociated(ENTERPRISE_TYPE,$tab["ID"],0);
				break;
			case 6 :
				showJobList($_SERVER["PHP_SELF"],$_GET["ID"],"enterprise","","","",$start);	
				break;
			case 7 : 
				showLinkOnDevice(ENTERPRISE_TYPE,$tab["ID"]);
				break;			
			default : 
				showAssociatedContact($tab["ID"]);

				break;
		}
	}

	commonFooter();
}

?>
