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
$NEEDED_ITEMS=array("link");
include ($phproot . "/inc/includes.php");


if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(empty($tab["ID"])) $tab["ID"] = "";

$link=new Link();

if (isset($_POST["add"]))
{
	checkRight("link","w");

	$newID=$link->add($_POST);
	logEvent($newID, "links", 4, "setup", $_SESSION["glpiname"]." ".$lang["log"][20]." ".$_POST["name"].".");
	glpi_header($cfg_glpi["root_doc"]."/front/link.php");
}
else if (isset($_POST["delete"]))
{
	checkRight("link","w");

	$link->delete($_POST);
	logEvent($_POST["ID"], "links", 4, "setup", $_SESSION["glpiname"]." ".$lang["log"][22]);
	glpi_header($cfg_glpi["root_doc"]."/front/link.php");
}
else if (isset($_POST["update"]))
{
	checkRight("link","w");

	$link->update($_POST);
	logEvent($_POST["ID"], "links", 4, "setup", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["adddevice"])){
	checkRight("link","w");

	addLinkDevice($_POST["device_type"],$_POST["lID"]);
	logEvent($tab["lID"], "links", 4, "setup", $_SESSION["glpiname"]." ".$lang["log"][32]);
	glpi_header($cfg_glpi["root_doc"]."/front/link.form.php?ID=".$_POST["lID"]);
}
else if (isset($_GET["deletedevice"])){
	checkRight("link","w");

	deleteLinkDevice($_GET["ID"]);
	logEvent(0, "links", 4, "setup", $_SESSION["glpiname"]." ".$lang["log"][33]);
	glpi_header($_SERVER['HTTP_REFERER']);
}

else
{
	checkRight("link","r");

	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
		//		glpi_header($_SERVER['HTTP_REFERER']);
	}

	commonHeader($lang["title"][33],$_SERVER['PHP_SELF']);

	if ($link->showForm($_SERVER['PHP_SELF'],$tab["ID"])&&!empty($tab["ID"]))
		showLinkDevice($tab["ID"]);
	commonFooter();
}


?>
