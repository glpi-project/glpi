<?php
/*
 
 ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer, turin@incubus.de 

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
include ($phproot . "/glpi/includes_software.php");
if($_POST){ $tab = $_POST;}
else if($_GET){ $tab = $_GET;}
if (isset($tab["addform"])) {
	checkAuthentication("admin");
	commonHeader("Software",$_SERVER["PHP_SELF"]);
	showLicenseForm($_SERVER["PHP_SELF"],$tab["ID"]);
	commonFooter();
} else if (isset($_POST["add"])) {
	checkAuthentication("admin");
	addLicense($tab);
	logEvent($tab["sID"], "software", 4, "inventory", $_SESSION["glpiname"]." added a license.");
	header("Location: $_SERVER[HTTP_REFERER]");
} else if (isset($_POST["delete"])) {
	checkAuthentication("admin");
	deleteLicense($tab["ID"]);
	logEvent(0, "software", 4, "inventory", $_SESSION["glpiname"]." deleted a license.");
	header("Location: $_SERVER[HTTP_REFERER]");
} else if (isset($tab["select"])) {
	checkAuthentication("admin");
	commonHeader("Software",$_SERVER["PHP_SELF"]);
	showLicenseSelect($_SERVER[HTTP_REFERER],$_SERVER["PHP_SELF"],$tab["cID"],$tab["sID"]);
	commonFooter();
} else if (isset($tab["install"])) {
	checkAuthentication("admin");
	installSoftware($tab["cID"],$tab["lID"]);
	logEvent($tab["cID"], "computers", 5, "inventory", $_SESSION["glpiname"]." installed software.");
	header("Location: $_SERVER[HTTP_REFERER]");
} else if (isset($tab["uninstall"])) {
	checkAuthentication("admin");
	uninstallSoftware($tab["lID"]);
	logEvent($tab["cID"], "computers", 5, "inventory", $_SESSION["glpiname"]." uninstalled software.");
	header("Location: $_SERVER[HTTP_REFERER]");
}


?>
