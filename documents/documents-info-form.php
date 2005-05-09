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

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";
if(!isset($tab["search"])) $tab["search"] = "";

if (isset($_POST["add"]))
{
	checkAuthentication("admin");

	addDocument($_POST);
	logEvent(0, "contract", 4, "document", $_SESSION["glpiname"]." added item ".$_POST["name"].".");
	header("Location: ".$_SERVER['HTTP_REFERER']);
	exit();
} 
else if (isset($_POST["delete"]))
{
	checkAuthentication("admin");
	deleteDocument($_POST);
	logEvent($tab["ID"], "contract", 4, "document", $_SESSION["glpiname"]." deleted item.");
	header("Location: ".$cfg_install["root"]."/documents/");
	exit();
}
else if (isset($_POST["restore"]))
{
	checkAuthentication("admin");
	restoreDocument($_POST);
	logEvent($tab["ID"], "contract", 4, "document", $_SESSION["glpiname"]." restored item.");
	header("Location: ".$cfg_install["root"]."/documents/");
	exit();
}
else if (isset($_POST["purge"]))
{
	checkAuthentication("admin");
	deleteDocument($_POST,1);
	logEvent($tab["ID"], "contract", 4, "document", $_SESSION["glpiname"]." purge item.");
	header("Location: ".$cfg_install["root"]."/documents/");
	exit();
}
else if (isset($_POST["additem"])){
	checkAuthentication("admin");
	if(isset($_POST["item"]))
	list($type,$ID)=explodeAllItemsSelectResult($_POST["item"]);
	else {$type=$_POST["type"];$ID=$_POST["ID"];}
	
	addDeviceDocument($_POST["conID"],$type,$ID);
	logEvent($tab["ID"], "contract", 4, "document", $_SESSION["glpiname"]." associate device.");
	header("Location: ".$_SERVER['HTTP_REFERER']);
	exit();
}
else if (isset($_GET["deleteitem"])){
	checkAuthentication("admin");
	deleteDeviceDocument($_GET["ID"]);
	logEvent($tab["ID"], "contract", 4, "document", $_SESSION["glpiname"]." delete device.");
	header("Location: ".$_SERVER['HTTP_REFERER']);
	exit();
}
else if (isset($_POST["addenterprise"])){
	checkAuthentication("admin");

	addEnterpriseDocument($_POST["conID"],$_POST["entID"]);
	logEvent($tab["ID"], "contract", 4, "document", $_SESSION["glpiname"]." associate device.");
	header("Location: ".$cfg_install["root"]."/documents/documents-info-form.php?ID=".$_POST["conID"]);
	exit();
}
else if (isset($_GET["deleteenterprise"])){
	checkAuthentication("admin");
	deleteEnterpriseDocument($_GET["ID"]);
	logEvent($tab["ID"], "contract", 4, "document", $_SESSION["glpiname"]." delete device.");
	header("Location: ".$cfg_install["root"]."/documents/documents-info-form.php?ID=".$_POST["conID"]);
	exit();
}
else if (isset($_POST["update"]))
{
	checkAuthentication("admin");
	updateDocument($_POST);
	logEvent($_POST["ID"], "contract", 4, "document", $_SESSION["glpiname"]." updated item.");
	header("Location: ".$_SERVER['HTTP_REFERER']);
	exit();

} 
else
{
	if (empty($tab["ID"]))
	checkAuthentication("admin");
	else checkAuthentication("normal");

	commonHeader($lang["title"][25],$_SERVER["PHP_SELF"]);
	showDocumentForm($_SERVER["PHP_SELF"],$tab["ID"],$tab["search"]);

	commonFooter();
}

?>
