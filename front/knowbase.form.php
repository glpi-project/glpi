<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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



$NEEDED_ITEMS=array("knowbase","document");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(!isset($_GET["ID"])) $_GET["ID"] = "";
if(!isset($_GET["modify"])) $_GET["modify"] = "";
if(!isset($_GET["delete"])) $_GET["delete"] = "";
if(!isset($_GET["addtofaq"])) $_GET["addtofaq"] = "";
if(!isset($_GET["removefromfaq"])) $_GET["removefromfaq"] = "";


$kb=new kbItem;


if ($_GET["ID"]=="new"){
	// on affiche le formulaire de saisie de l'item
	$kb->can(-1,'w');

	commonHeader($LANG["title"][5],$_SERVER['PHP_SELF'],"utils","knowbase");
	$kb->showForm($_SERVER['PHP_SELF'],"");
	commonFooter();

} else if (isset($_POST["add"])){
	// ajoute un item dans la base de connaisssances 	
	$kb->can(-1,'w');

	$newID=$kb->add($_POST);
	logEvent($newID, "knowbase", 5, "tools", $_SESSION["glpiname"]." ".$LANG["log"][20]);
	glpi_header($CFG_GLPI["root_doc"]."/front/knowbase.php");

}  else if (isset($_POST["update"])){
	// actualiser  un item dans la base de connaissances
	$kb->can($_POST["ID"],'w');

	$kb->update($_POST);
	logEvent($_POST["ID"], "knowbase", 5, "tools", $_SESSION["glpiname"]." ".$LANG["log"][21]);	
	glpi_header($CFG_GLPI["root_doc"]."/front/knowbase.form.php?ID=".$_POST['ID']);

} else if (isset($_GET["ID"])  && strcmp($_GET["modify"],"yes") == 0){
	// modifier un item dans la base de connaissance
	$kb->can($_GET["ID"],'r');

	commonHeader($LANG["title"][5],$_SERVER['PHP_SELF'],"utils","knowbase");
	$kb->showForm($_SERVER['PHP_SELF'],$_GET["ID"]);
	commonFooter();

} else if (isset($_GET["ID"])  && strcmp($_GET["delete"],"yes") == 0){
	// effacer un item dans la base de connaissances
	$kb->can($_GET["ID"],'w');

	$kb->delete($_GET);
	logEvent($_GET["ID"], "knowbase", 5, "tools", $_SESSION["glpiname"]." ".$LANG["log"][22]);	
	glpi_header($CFG_GLPI["root_doc"]."/front/knowbase.php");

} else if (isset($_GET["ID"])  && strcmp($_GET["addtofaq"],"yes") == 0){
	// ajouter  un item dans la faq
	$kb->can($_GET["ID"],'w');

	KbItemaddtofaq($_GET["ID"]);
	glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_GET["ID"])  && strcmp($_GET["removefromfaq"],"yes") == 0){
	// retirer  un item de la faq
	$kb->can($_GET["ID"],'w');

	KbItemremovefromfaq($_GET["ID"]);
	glpi_header($_SERVER['HTTP_REFERER']);

} else if (empty($_GET["ID"])) {
	glpi_header($CFG_GLPI["root_doc"]."/front/knowbase.php");

} else  {
	// Affiche un item de la base de connaissances
	$kb->can($_GET["ID"],'r');

	commonHeader($LANG["title"][5],$_SERVER['PHP_SELF'],"utils","knowbase");

	if (ShowKbItemFull($_GET["ID"])){
		kbItemMenu($_GET["ID"]);
		showDocumentAssociated(KNOWBASE_TYPE,$_GET["ID"]);
	}
	commonFooter();
}


?>
