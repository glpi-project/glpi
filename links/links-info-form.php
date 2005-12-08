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
include ($phproot . "/glpi/includes_links.php");


if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(empty($tab["ID"])) $tab["ID"] = "";


if (isset($_POST["add"]))
{
	checkAuthentication("admin");
	$newID=addLink($_POST);
	logEvent($newID, "links", 4, "setup", $_SESSION["glpiname"]." ".$lang["log"][20]." ".$_POST["name"].".");
	glpi_header($cfg_install["root"]."/links/");
}
else if (isset($_POST["delete"]))
{
	checkAuthentication("admin");
	deleteLink($_POST);
	logEvent($_POST["ID"], "links", 4, "setup", $_SESSION["glpiname"]." ".$lang["log"][22]);
	glpi_header($cfg_install["root"]."/links/");
}
else if (isset($_POST["update"]))
{
	checkAuthentication("admin");
	updateLink($_POST);
	logEvent($_POST["ID"], "links", 4, "setup", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["adddevice"])){
	checkAuthentication("admin");
	addLinkDevice($_POST["device_type"],$_POST["lID"]);
	logEvent($tab["lID"], "links", 4, "setup", $_SESSION["glpiname"]." ".$lang["log"][32]);
	glpi_header($cfg_install["root"]."/links/links-info-form.php?ID=".$_POST["lID"]);
}
else if (isset($_GET["deletedevice"])){
	checkAuthentication("admin");
	deleteLinkDevice($_GET["ID"]);
	logEvent(0, "links", 4, "setup", $_SESSION["glpiname"]." ".$lang["log"][33]);
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

	commonHeader($lang["title"][33],$_SERVER["PHP_SELF"]);

//	showLinkOnglets($_SERVER["PHP_SELF"]."?ID=".$tab["ID"], "",$_SESSION['glpi_onglet'] );
	if (showLinkForm($_SERVER["PHP_SELF"],$tab["ID"])&&!empty($tab["ID"]))
		showLinkDevice($tab["ID"]);
	commonFooter();
}


?>
