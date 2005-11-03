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
include ($phproot . "/glpi/includes_tracking.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_monitors.php");
include ($phproot . "/glpi/includes_peripherals.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_software.php");
include ($phproot . "/glpi/includes_enterprises.php");
include ($phproot . "/glpi/includes_users.php");

checkAuthentication("post-only");
if (isset($_GET["update_item"])) {
	commonHeader($lang["title"][10],$_SERVER["PHP_SELF"]);
	echo "<center>";
	itemFormTracking($_GET["ID"],$_SERVER["PHP_SELF"]);
	echo "</center>";
	commonFooter();

} else if (isset($_GET["update_item_ok"])) {
	
itemJob ($_GET["ID"],$_GET["device_type"],$_GET["computer"]);
	
glpi_header($cfg_install["root"]."/tracking/tracking-followups.php?ID=".$_GET["ID"]);
	
}else if (isset($_GET["update_author"])) {
	commonHeader($lang["title"][10],$_SERVER["PHP_SELF"]);
	echo "<center>";
	authorFormTracking($_GET["ID"],$_SERVER["PHP_SELF"]);
	echo "</center>";
	commonFooter();

} else if (isset($_GET["update_author_ok"])) {
	
authorJob ($_GET["ID"],$_GET["author"]);
	
glpi_header($cfg_install["root"]."/tracking/tracking-followups.php?ID=".$_GET["ID"]);
	
}else if (isset($_GET["update"])) {

	if (can_assign_job($_SESSION["glpiname"])&&isset($_GET['assign_id'])){
		assignJob ($_GET["ID"],$_GET['assign_type'],$_GET['assign_id'],$_SESSION["glpiname"]);	
	}
	
	categoryJob ($_GET["ID"],$_GET["category"],$_SESSION["glpiname"]);	
	priorityJob ($_GET["ID"],$_GET["priority"],$_SESSION["glpiname"]);
	
	glpi_header($_SERVER['HTTP_REFERER']);

}



?>
