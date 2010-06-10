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
include ($phproot . "/glpi/includes_monitors.php");

if ($add) {
	checkAuthentication("admin");
	addMonitor($HTTP_POST_VARS);
	logEvent(0, "monitors", 4, "inventory", "$IRMName added ".$HTTP_POST_VARS["name"].".");
	header("Location: $HTTP_REFERER");
} else if ($delete) {
	checkAuthentication("admin");
	deleteMonitor($HTTP_POST_VARS);
	logEvent($HTTP_POST_VARS["ID"], "monitors", 4, "inventory", "$IRMName deleted item.");
	header("Location: ".$cfg_install["root"]."/monitors/");
} else if ($update) {
	checkAuthentication("admin");
	updateMonitor($HTTP_POST_VARS);
	logEvent($HTTP_POST_VARS["ID"], "monitors", 4, "inventory", "$IRMName updated item.");
	commonHeader("Monitors",$PHP_SELF);
	showMonitorsForm($PHP_SELF,$ID);
	commonFooter();

} else if ($disconnect) {
	checkAuthentication("admin");
	Disconnect($ID,4);
	logEvent($ID, "monitors", 5, "inventory", "$IRMName disconnected item.");
	commonHeader("Monitors",$PHP_SELF);
	showMonitorsForm($PHP_SELF,$ID);
	commonFooter();
} else if ($connect==1) {
	checkAuthentication("admin");
	commonHeader("Monitors",$PHP_SELF);
	showConnectSearch($PHP_SELF,$ID);
	commonFooter();
} else if ($connect==2) {
	checkAuthentication("admin");
	commonHeader("Monitors",$PHP_SELF);
	listConnectComputers($PHP_SELF,$HTTP_POST_VARS);
	commonFooter();
} else if ($connect==3) {
	checkAuthentication("admin");
	commonHeader("Monitors",$PHP_SELF);
	Connect($PHP_SELF,$sID,$cID,4);
	logEvent($sID, "monitors", 5, "inventory", "$IRMName connected item.");
	showMonitorsForm($PHP_SELF,$sID);
	commonFooter();

} else {
	checkAuthentication("normal");
	commonHeader("Monitors",$PHP_SELF);
	showMonitorsForm($PHP_SELF,$ID);
	commonFooter();
}


?>
