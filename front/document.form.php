<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------


$NEEDED_ITEMS=array("document","computer","printer","monitor","peripheral","networking","software","contract","knowbase","cartridge","consumable","phone","enterprise","contact");
define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(!isset($_GET["ID"])) $_GET["ID"] = "";

$doc= new Document();

if (isset($_POST["add"]))
{
	checkRight("document","w");

	$newID=$doc->add($_POST);
	$name="";
	if (isset($_POST["name"])){
		$name=$_POST["name"];
	} else if (isset($_FILES['filename'])){
		if (isset($_FILES['filename']['name'])){
			$name=$_FILES['filename']['name'];
		}
	}
	logEvent($newID, "documents", 4, "document", $_SESSION["glpiname"]." ".$LANG["log"][20]." ".$name.".");
	glpi_header($_SERVER['HTTP_REFERER']);
} 
else if (isset($_POST["delete"]))
{
	checkRight("document","w");

	$doc->delete($_POST);
	logEvent($_POST["ID"], "documents", 4, "document", $_SESSION["glpiname"]." ".$LANG["log"][22]);
	glpi_header($CFG_GLPI["root_doc"]."/front/document.php");
}
else if (isset($_POST["restore"]))
{
	checkRight("document","w");

	$doc->restore($_POST);
	logEvent($_POST["ID"], "documents", 4, "document", $_SESSION["glpiname"]." ".$LANG["log"][23]);
	glpi_header($CFG_GLPI["root_doc"]."/front/document.php");
}
else if (isset($_POST["purge"]))
{
	checkRight("document","w");

	$doc->delete($_POST,1);
	logEvent($_POST["ID"], "documents", 4, "document", $_SESSION["glpiname"]." ".$LANG["log"][24]);
	glpi_header($CFG_GLPI["root_doc"]."/front/document.php");
}

else if (isset($_POST["update"]))
{
	checkRight("document","w");

	$doc->update($_POST);
	logEvent($_POST["ID"], "documents", 4, "document", $_SESSION["glpiname"]." ".$LANG["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
} 
else if (isset($_POST["additem"])){

	checkRight("document","w");

	if ($_POST['type']>0&&$_POST['item']>0){
		addDeviceDocument($_POST["conID"],$_POST['type'],$_POST['item']);
		logEvent($_POST["conID"], "documents", 4, "document", $_SESSION["glpiname"]." ".$LANG["log"][32]);
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["deleteitem"])){

	checkRight("document","w");
	if (count($_POST["item"])){
		foreach ($_POST["item"] as $key => $val){
			deleteDeviceDocument($key);
		}
	}
	logEvent($_POST["conID"], "documents", 4, "document", $_SESSION["glpiname"]." ".$LANG["log"][33]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_GET["deleteitem"])){

	checkRight("document","w");
	
	deleteDeviceDocument($_GET["ID"]);

	logEvent($_GET["ID"], "documents", 4, "document", $_SESSION["glpiname"]." ".$LANG["log"][33]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["addenterprise"])){

	checkRight("document","w");

	addEnterpriseDocument($_POST["conID"],$_POST["entID"]);
	logEvent($_POST["ID"], "documents", 4, "document", $_SESSION["glpiname"]."  ".$LANG["log"][32]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_GET["deleteenterprise"])){

	checkRight("document","w");

	deleteEnterpriseDocument($_GET["ID"]);
	logEvent($_GET["ID"], "documents", 4, "document", $_SESSION["glpiname"]."  ".$LANG["log"][33]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else
{
	checkRight("document","r");

	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
		//		glpi_header($_SERVER['HTTP_REFERER']);
	}

	commonHeader($LANG["title"][25],$_SERVER['PHP_SELF'],"financial","document");


	if ($doc->showForm($_SERVER['PHP_SELF'],$_GET["ID"])){
		switch ($_SESSION['glpi_onglet']){
			case -1 :
				showDeviceDocument($_GET["ID"]);
				displayPluginAction(DOCUMENT_TYPE,$_GET["ID"],$_SESSION['glpi_onglet']);
				break;
			case 10 :
				showNotesForm($_SERVER['PHP_SELF'],DOCUMENT_TYPE,$_GET["ID"]);
				break;
			default :
				if ($_GET["ID"]){
					if (!displayPluginAction(DOCUMENT_TYPE,$_GET["ID"],$_SESSION['glpi_onglet'])){
						showDeviceDocument($_GET["ID"]);
					}
				}
				break;
		}
	}
	commonFooter();
}

?>
