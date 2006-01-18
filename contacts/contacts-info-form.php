<?php
/*
 * @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
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
include ($phproot . "/glpi/includes_links.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(empty($tab["ID"])) $tab["ID"] = "";


if (isset($_POST["add"]))
{
	checkAuthentication("admin");
	$newID=addContact($_POST);
	logEvent($newID, "contacts", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][20]." ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["delete"]))
{
	checkAuthentication("admin");
	deleteContact($_POST);
	logEvent($_POST["ID"], "contacts", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][22]);
	glpi_header($cfg_install["root"]."/contacts/");
}
else if (isset($_POST["restore"]))
{
	checkAuthentication("admin");
	restoreContact($_POST);
	logEvent($tab["ID"], "contacts", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][23]);
	glpi_header($cfg_install["root"]."/contacts/");
}
else if (isset($_POST["purge"]))
{
	checkAuthentication("admin");
	deleteContact($_POST,1);
	logEvent($tab["ID"], "contacts", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][24]);
	glpi_header($cfg_install["root"]."/contacts/");
}
else if (isset($_POST["update"]))
{
	checkAuthentication("admin");
	updateContact($_POST);
	logEvent($_POST["ID"], "contacts", 4, "financial", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["addenterprise"])){
	checkAuthentication("admin");
	addContactEnterprise($_POST["entID"],$_POST["conID"]);
	logEvent($tab["conID"], "contacts", 4, "financial", $_SESSION["glpiname"]."  ".$lang["log"][34]);
	glpi_header($cfg_install["root"]."/contacts/contacts-info-form.php?ID=".$_POST["conID"]);
}
else if (isset($_GET["deleteenterprise"])){
	checkAuthentication("admin");
	deleteContactEnterprise($_GET["ID"]);
	logEvent(0, "contacts", 4, "financial", $_SESSION["glpiname"]."  ".$lang["log"][35]);
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

	commonHeader($lang["title"][22],$_SERVER["PHP_SELF"]);
	
	$ci=new CommonItem();
	if ($ci->getFromDB(CONTACT_TYPE,$tab["ID"]))
	showContactOnglets($_SERVER["PHP_SELF"]."?ID=".$tab["ID"], "",$_SESSION['glpi_onglet'] );

	if (showContactForm($_SERVER["PHP_SELF"],$tab["ID"])) {
		if (!empty($tab['ID']))
		switch($_SESSION['glpi_onglet']){
		case -1 :	
			showEnterpriseContact($tab["ID"]);
		
			showLinkOnDevice(CONTACT_TYPE,$tab["ID"]);
			break;
		case 7 : 
			showLinkOnDevice(CONTACT_TYPE,$tab["ID"]);
			break;

		case 10 :
				showNotesForm($_SERVER["PHP_SELF"],CONTACT_TYPE,$tab["ID"]);
				break;
		default :
			showEnterpriseContact($tab["ID"]);
		break;
		}
	}	
	
	commonFooter();
}


?>
