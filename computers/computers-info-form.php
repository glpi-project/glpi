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
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_monitors.php");
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_tracking.php");
include ($phproot . "/glpi/includes_software.php");

if ($add) {
	checkAuthentication("admin");
	addComputer($HTTP_POST_VARS);
	logEvent(0, "computers", 4, "inventory", "$IRMName added ".$HTTP_POST_VARS["name"].".");
	header("Location: $HTTP_REFERER");
} else if ($delete) {
	checkAuthentication("admin");
	deleteComputer($HTTP_POST_VARS);
	logEvent($HTTP_POST_VARS["ID"], "computers", 4, "inventory", "$IRMName deleted item.");
	header("Location: ".$cfg_install["root"]."/computers/");
} else if ($update) {
	checkAuthentication("admin");
	updateComputer($HTTP_POST_VARS);
	logEvent($HTTP_POST_VARS["ID"], "computers", 4, "inventory", "$IRMName updated item.");
	commonHeader("Computers",$PHP_SELF);
	showComputerForm(0,$PHP_SELF,$ID);
	showPorts($ID, 1);
	showPortsAdd($ID,1);
	showJobList($IRMName,$show,$contains,$ID);
	showSoftwareInstalled($ID);
	commonFooter();
} else {

	checkAuthentication("normal");
	commonHeader("Computers",$PHP_SELF);
	if ($withtemplate == 1) {
		showComputerForm($withtemplate,$PHP_SELF,$ID);
	} else {
		if (showComputerForm(0,$PHP_SELF,$ID)) {
	
			showPorts($ID, 1);
			
			showPortsAdd($ID,1);
		
			showConnections($ID);
		
			showJobList($IRMName,$show,$contains,$ID);
	
			showOldJobListForItem($IRMName,$contains,$ID);
	
			showSoftwareInstalled($ID);
		}
	}
	commonFooter();
}


?>
