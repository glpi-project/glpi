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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------


$NEEDED_ITEMS=array("document","computer","printer","monitor","peripheral","networking","software","contract","knowbase","cartridge","consumable","phone","enterprise");
define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";

$doc= new Document();

if (isset($_POST["add"]))
{
	checkRight("document","w");

	$newID=$doc->add($_POST);
	logEvent($newID, "documents", 4, "document", $_SESSION["glpiname"]." ".$LANG["log"][20]." ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
} 
else if (isset($_POST["delete"]))
{
	checkRight("document","w");

	$doc->delete($_POST);
	logEvent($tab["ID"], "documents", 4, "document", $_SESSION["glpiname"]." ".$LANG["log"][22]);
	glpi_header($CFG_GLPI["root_doc"]."/front/document.php");
}
else if (isset($_POST["restore"]))
{
	checkRight("document","w");

	$doc->restore($_POST);
	logEvent($tab["ID"], "documents", 4, "document", $_SESSION["glpiname"]." ".$LANG["log"][23]);
	glpi_header($CFG_GLPI["root_doc"]."/front/document.php");
}
else if (isset($_POST["purge"]))
{
	checkRight("document","w");

	$doc->delete($_POST,1);
	logEvent($tab["ID"], "documents", 4, "document", $_SESSION["glpiname"]." ".$LANG["log"][24]);
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

	$template=0;
	if (isset($_POST["is_template"])) $template=1;

	if ($_POST['type']>0&&$_POST['item']>0){
		addDeviceDocument($_POST["conID"],$_POST['type'],$_POST['item'],$template);
		logEvent($tab["conID"], "documents", 4, "document", $_SESSION["glpiname"]." ".$LANG["log"][32]);
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_GET["deleteitem"])){

	checkRight("document","w");

	deleteDeviceDocument($_GET["ID"]);
	logEvent($tab["ID"], "documents", 4, "document", $_SESSION["glpiname"]." ".$LANG["log"][33]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["addenterprise"])){

	checkRight("document","w");

	addEnterpriseDocument($_POST["conID"],$_POST["entID"]);
	logEvent($tab["ID"], "documents", 4, "document", $_SESSION["glpiname"]."  ".$LANG["log"][32]);
	glpi_header($CFG_GLPI["root_doc"]."/front/document.form.php?ID=".$_POST["conID"]);
}
else if (isset($_GET["deleteenterprise"])){

	checkRight("document","w");

	deleteEnterpriseDocument($_GET["ID"]);
	logEvent($tab["ID"], "documents", 4, "document", $_SESSION["glpiname"]."  ".$LANG["log"][33]);
	glpi_header($CFG_GLPI["root_doc"]."/front/document.form.php?ID=".$_POST["conID"]);
}
else
{
	checkRight("document","r");

	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
		//		glpi_header($_SERVER['HTTP_REFERER']);
	}

	commonHeader($LANG["title"][25],$_SERVER['PHP_SELF']);


	if ($doc->showForm($_SERVER['PHP_SELF'],$tab["ID"])){
		switch ($_SESSION['glpi_onglet']){
			case 10 :
				showNotesForm($_SERVER['PHP_SELF'],DOCUMENT_TYPE,$tab["ID"]);
				break;
			default :
				if ($tab["ID"]){
					if (!display_plugin_action(DOCUMENT_TYPE,$tab["ID"],$_SESSION['glpi_onglet'])){
						showDeviceDocument($tab["ID"]);
					}
				}
				break;
		}
	}
	commonFooter();
}

?>
