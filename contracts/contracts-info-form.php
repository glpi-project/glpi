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
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_monitors.php");
include ($phproot . "/glpi/includes_peripherals.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_software.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";
if(!isset($tab["search"])) $tab["search"] = "";

if (isset($_POST["add"]))
{
	checkAuthentication("admin");

	addContract($_POST);
	logEvent(0, "contract", 4, "financial", $_SESSION["glpiname"]." added item ".$_POST["num"].".");
	header("Location: ".$_SERVER['HTTP_REFERER']);
} 
else if (isset($_POST["delete"]))
{
	checkAuthentication("admin");
	deleteContract($_POST);
	logEvent($tab["ID"], "contract", 4, "financial", $_SESSION["glpiname"]." deleted item.");
	header("Location: ".$cfg_install["root"]."/contracts/");
}
else if (isset($_POST["restore"]))
{
	checkAuthentication("admin");
	restoreContract($_POST);
	logEvent($tab["ID"], "contract", 4, "financial", $_SESSION["glpiname"]." restored item.");
	header("Location: ".$cfg_install["root"]."/contracts/");
}
else if (isset($_POST["purge"]))
{
	checkAuthentication("admin");
	deleteContract($_POST,1);
	logEvent($tab["ID"], "contract", 4, "financial", $_SESSION["glpiname"]." purge item.");
	header("Location: ".$cfg_install["root"]."/contracts/");
}
else if (isset($_POST["additem"])){
	checkAuthentication("admin");
	if(isset($_POST["item"]))
	list($type,$ID)=explodeAllItemsSelectResult($_POST["item"]);
	else {$type=$_POST["type"];$ID=$_POST["ID"];}
	
	addDeviceContract($_POST["conID"],$type,$ID);
	logEvent($tab["ID"], "contract", 4, "financial", $_SESSION["glpiname"]." associate device.");
	header("Location: ".$_SERVER['HTTP_REFERER']);
}
else if (isset($_GET["deleteitem"])){
	checkAuthentication("admin");
	deleteDeviceContract($_GET["ID"]);
	logEvent($tab["ID"], "contract", 4, "financial", $_SESSION["glpiname"]." delete device.");
	header("Location: ".$_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["addenterprise"])){
	checkAuthentication("admin");

	addEnterpriseContract($_POST["conID"],$_POST["entID"]);
	logEvent($tab["ID"], "contract", 4, "financial", $_SESSION["glpiname"]." associate device.");
	header("Location: ".$cfg_install["root"]."/contracts/contracts-info-form.php?ID=".$_POST["conID"]);
}
else if (isset($_GET["deleteenterprise"])){
	checkAuthentication("admin");
	deleteEnterpriseContract($_GET["ID"]);
	logEvent($tab["ID"], "contract", 4, "financial", $_SESSION["glpiname"]." delete device.");
	header("Location: ".$cfg_install["root"]."/contracts/contracts-info-form.php?ID=".$_POST["conID"]);
}
else if (isset($_POST["update"]))
{
	checkAuthentication("admin");
	updateContract($_POST);
	logEvent($_POST["ID"], "contract", 4, "financial", $_SESSION["glpiname"]." updated item.");
	header("Location: ".$_SERVER['HTTP_REFERER']);

} 
else
{
	if (empty($tab["ID"]))
	checkAuthentication("admin");
	else checkAuthentication("normal");

	commonHeader($lang["title"][20],$_SERVER["PHP_SELF"]);
	showContractForm($_SERVER["PHP_SELF"],$tab["ID"],$tab["search"]);

	commonFooter();
}

?>
