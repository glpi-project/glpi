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
include ("_relpos.php");
$NEEDED_ITEMS=array("contact","enterprise","link");
include ($phproot . "/inc/includes.php");


if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(empty($tab["ID"])) $tab["ID"] = "";

$contact=new Contact;
if (isset($_POST["add"]))
{
	checkRight("contact_enterprise","w");

	$newID=$contact->add($_POST);
	logEvent($newID, "contacts", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][20]." ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["delete"]))
{
	checkRight("contact_enterprise","w");

	$contact->delete($_POST);
	logEvent($_POST["ID"], "contacts", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][22]);
	glpi_header($cfg_glpi["root_doc"]."/front/contact.php");
}
else if (isset($_POST["restore"]))
{
	checkRight("contact_enterprise","w");

	$contact->restore($_POST);
	logEvent($tab["ID"], "contacts", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][23]);
	glpi_header($cfg_glpi["root_doc"]."/front/contact.php");
}
else if (isset($_POST["purge"]))
{
	checkRight("contact_enterprise","w");

	$contact->delete($_POST,1);
	logEvent($tab["ID"], "contacts", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][24]);
	glpi_header($cfg_glpi["root_doc"]."/front/contact.php");
}
else if (isset($_POST["update"]))
{
	checkRight("contact_enterprise","w");

	$contact->update($_POST);
	logEvent($_POST["ID"], "contacts", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["addenterprise"])){
	checkRight("contact_enterprise","w");

	addContactEnterprise($_POST["entID"],$_POST["conID"]);
	logEvent($tab["conID"], "contacts", 4, "financial", $_SESSION["glpiname"]."  ".$lang["log"][34]);
	glpi_header($cfg_glpi["root_doc"]."/front/contact.form.php?ID=".$_POST["conID"]);
}
else if (isset($_GET["deleteenterprise"])){
	checkRight("contact_enterprise","w");

	deleteContactEnterprise($_GET["ID"]);
	logEvent($_GET["cID"], "contacts", 4, "financial", $_SESSION["glpiname"]."  ".$lang["log"][35]);
	glpi_header($_SERVER['HTTP_REFERER']);
}

else
{
	checkRight("contact_enterprise","r");

	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
	}

	commonHeader($lang["title"][22],$_SERVER['PHP_SELF']);

	if ($contact->getFromDB($tab["ID"]))
		$contact->showOnglets($_SERVER['PHP_SELF']."?ID=".$tab["ID"], "",$_SESSION['glpi_onglet'] );

	if ($contact->showForm($_SERVER['PHP_SELF'],$tab["ID"])) {
		if (!empty($tab['ID']))
			switch($_SESSION['glpi_onglet']){
				case -1 :	
					showEnterpriseContact($tab["ID"]);
					showLinkOnDevice(CONTACT_TYPE,$tab["ID"]);
					display_plugin_action(CONTACT_TYPE,$tab["ID"],$_SESSION['glpi_onglet']);
					break;
				case 7 : 
					showLinkOnDevice(CONTACT_TYPE,$tab["ID"]);
					break;

				case 10 :
					showNotesForm($_SERVER['PHP_SELF'],CONTACT_TYPE,$tab["ID"]);
					break;
				default :
					if (!display_plugin_action(CONTACT_TYPE,$tab["ID"],$_SESSION['glpi_onglet']))
						showEnterpriseContact($tab["ID"]);
					break;
			}
	}	

	commonFooter();
}


?>
