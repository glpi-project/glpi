<?php
/*
 
  ----------------------------------------------------------------------
 GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 Bazile Lebeau, baaz@indepnet.net - Jean-Mathieu Doléans, jmd@indepnet.net
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
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
 

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_peripherals.php");
if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(empty($tab["ID"])) $tab["ID"] = "";


if (isset($_POST["add"]))
{
	checkAuthentication("admin");
	addPeripheral($_POST);
	logEvent(0, "Peripheral", 4, "inventory", $_SESSION["glpiname"]." added ".$_POST["name"].".");
	header("Location: $_SERVER[HTTP_REFERER]");
}
else if (isset($_POST["delete"]))
{
	checkAuthentication("admin");
	deletePeripheral($_POST);
	Disconnect($tab["ID"],5);
	logEvent($_POST["ID"], "Peripherals", 4, "inventory", $_SESSION["glpiname"]." deleted item.");
	header("Location: ".$cfg_install["root"]."/peripherals/");
}
else if (isset($_POST["update"]))
{
	checkAuthentication("admin");
	updatePeripheral($_POST);
	logEvent($_POST["ID"], "Peripherals", 4, "inventory", $_SESSION["glpiname"]." updated item.");
	commonHeader("Peripherals",$_SERVER["PHP_SELF"]);
	showPeripheralForm($_SERVER["PHP_SELF"],$_POST["ID"]);
	commonFooter();

}
else if (isset($tab["disconnect"]))
{
	checkAuthentication("admin");
	Disconnect($tab["ID"],5);
	logEvent($tab["ID"], "Peripherals", 5, "inventory", $_SESSION["glpiname"]." disconnected item.");
	commonHeader("Peripherals",$_SERVER["PHP_SELF"]);
	showPeripheralForm($_SERVER["PHP_SELF"],$tab["ID"]);
	commonFooter();
}
else if(isset($tab["connect"]))
{
 	if($tab["connect"]==1)
	{
		checkAuthentication("admin");
		commonHeader("Peripherals",$_SERVER["PHP_SELF"]);
		showConnectSearch($_SERVER["PHP_SELF"],$tab["ID"]);
		commonFooter();
	}
	else if ($tab["connect"]==2)
	{
		checkAuthentication("admin");
		commonHeader("Peripherals",$_SERVER["PHP_SELF"]);
		listConnectComputers($_SERVER["PHP_SELF"],$tab);
		commonFooter();
	}
	else if ($tab["connect"]==3)
	{
		checkAuthentication("admin");
		commonHeader("Peripherals",$_SERVER["PHP_SELF"]);
		Connect($_SERVER["PHP_SELF"],$tab["sID"],$tab["cID"],5);
		logEvent($tab["sID"], "Peripherals", 4, "inventory", $_SESSION["glpiname"]." connected item.");
		showPeripheralForm($_SERVER["PHP_SELF"],$tab["sID"]);
		commonFooter();
	}
}
else
{
	if (empty($tab["ID"]))
	checkAuthentication("admin");
	else checkAuthentication("normal");

	commonHeader("Peripherals",$_SERVER["PHP_SELF"]);
	showPeripheralForm($_SERVER["PHP_SELF"],$tab["ID"]);
	commonFooter();
}


?>
