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

if ($addform) {
	checkAuthentication("admin");
	commonHeader("Software",$PHP_SELF);
	showLicenseForm($PHP_SELF,$ID);
	commonFooter();
} else if ($add) {
	checkAuthentication("admin");
	addLicense($HTTP_POST_VARS);
	logEvent($HTTP_POST_VARS["sID"], "software", 4, "inventory", "$IRMName added a license.");
	header("Location: $HTTP_REFERER");
} else if ($delete) {
	checkAuthentication("admin");
	deleteLicense($ID);
	logEvent(0, "software", 4, "inventory", "$IRMName deleted a license.");
	header("Location: $HTTP_REFERER");
} else if ($select) {
	checkAuthentication("admin");
	commonHeader("Software",$PHP_SELF);
	showLicenseSelect($HTTP_REFERER,$PHP_SELF,$cID,$sID);
	commonFooter();
} else if ($install) {
	checkAuthentication("admin");
	installSoftware($cID,$lID);
	logEvent($cID, "computers", 5, "inventory", "$IRMName installed software.");
	header("Location: $back");
} else if ($uninstall) {
	checkAuthentication("admin");
	uninstallSoftware($lID);
	logEvent($cID, "computers", 5, "inventory", "$IRMName uninstalled software.");
	header("Location: $HTTP_REFERER");
}


?>
