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
include ($phproot . "/glpi/includes_documents.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_monitors.php");
include ($phproot . "/glpi/includes_peripherals.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_software.php");
include ($phproot . "/glpi/includes_financial.php");
include ($phproot . "/glpi/includes_knowbase.php");
include ($phproot . "/glpi/includes_cartridges.php");
include ($phproot . "/glpi/includes_consumables.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";

if (isset($_POST["add"]))
{
	checkAuthentication("admin");

	$newID=addDocument($_POST);
	logEvent($newID, "documents", 4, "document", $_SESSION["glpiname"]." ".$lang["log"][20]." ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
} 
else if (isset($_POST["delete"]))
{
	checkAuthentication("admin");
	deleteDocument($_POST);
	logEvent($tab["ID"], "documents", 4, "document", $_SESSION["glpiname"]." ".$lang["log"][22]);
	glpi_header($cfg_install["root"]."/documents/");
}
else if (isset($_POST["restore"]))
{
	checkAuthentication("admin");
	restoreDocument($_POST);
	logEvent($tab["ID"], "documents", 4, "document", $_SESSION["glpiname"]." ".$lang["log"][23]);
	glpi_header($cfg_install["root"]."/documents/");
}
else if (isset($_POST["purge"]))
{
	checkAuthentication("admin");
	deleteDocument($_POST,1);
	logEvent($tab["ID"], "documents", 4, "document", $_SESSION["glpiname"]." ".$lang["log"][24]);
	glpi_header($cfg_install["root"]."/documents/");
}
else if (isset($_POST["additem"])){
	checkAuthentication("admin");

	$template=0;
	if (isset($_POST["is_template"])) $template=1;
	
	if ($_POST['type']>0&&$_POST['item']>0){
		addDeviceDocument($_POST["conID"],$_POST['type'],$_POST['item'],$template);
		logEvent($tab["conID"], "documents", 4, "document", $_SESSION["glpiname"]." ".$lang["log"][32]);
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_GET["deleteitem"])){
	checkAuthentication("admin");
	deleteDeviceDocument($_GET["ID"]);
	logEvent($tab["ID"], "documents", 4, "document", $_SESSION["glpiname"]." ".$lang["log"][33]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["addenterprise"])){
	checkAuthentication("admin");

	addEnterpriseDocument($_POST["conID"],$_POST["entID"]);
	logEvent($tab["ID"], "documents", 4, "document", $_SESSION["glpiname"]."  ".$lang["log"][32]);
	glpi_header($cfg_install["root"]."/documents/documents-info-form.php?ID=".$_POST["conID"]);
}
else if (isset($_GET["deleteenterprise"])){
	checkAuthentication("admin");
	deleteEnterpriseDocument($_GET["ID"]);
	logEvent($tab["ID"], "documents", 4, "document", $_SESSION["glpiname"]."  ".$lang["log"][33]);
	glpi_header($cfg_install["root"]."/documents/documents-info-form.php?ID=".$_POST["conID"]);
}
else if (isset($_POST["update"]))
{
	checkAuthentication("admin");
	updateDocument($_POST);
	logEvent($_POST["ID"], "documents", 4, "document", $_SESSION["glpiname"]." ".$lang["log"][21]);
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

	commonHeader($lang["title"][25],$_SERVER["PHP_SELF"]);

	$ci=new CommonItem();
	if ($ci->getFromDB(DOCUMENT_TYPE,$tab["ID"]))
	showDocumentOnglets($_SERVER["PHP_SELF"]."?ID=".$tab["ID"], "",$_SESSION['glpi_onglet'] );

	showDocumentForm($_SERVER["PHP_SELF"],$tab["ID"]);

	commonFooter();
}

?>
