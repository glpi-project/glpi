<?php
/*
 * @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_users.php");
include ($phproot . "/glpi/includes_planning.php");
include ($phproot . "/glpi/includes_tracking.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_monitors.php");
include ($phproot . "/glpi/includes_peripherals.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_software.php");
include ($phproot . "/glpi/includes_enterprises.php");

checkAuthentication("admin");
	
if (isset($_POST["add_planning"])){

checkAuthentication("normal");
if (addPlanningTracking($_POST,$_SERVER["REQUEST_URI"])){
	logEvent(0, "planning", 4, "planning", $_SESSION["glpiname"]." ".$lang["log"][20]);
	glpi_header($cfg_install["root"]."/tracking/tracking-info-form.php?ID=".$_POST["id_tracking"]);
} 
} else if (isset($_POST["edit_planning"])){
	
	list($begin_year,$begin_month,$begin_day)=split("-",$_POST["begin_date"]);
	list($end_year,$end_month,$end_day)=split("-",$_POST["end_date"]);

	$_POST["begin"]=date("Y-m-d H:i:00",mktime($_POST["begin_hour"],$_POST["begin_min"],0,$begin_month,$begin_day,$begin_year));
	$_POST["end"]=date("Y-m-d H:i:00",mktime($_POST["end_hour"],$_POST["end_min"],0,$end_month,$end_day,$end_year));

	if (updatePlanningTracking($_POST,$_SERVER["PHP_SELF"],$_POST["ID"])){
		logEvent(0, "planning", 4, "planning", $_SESSION["glpiname"]." ".$lang["log"][21]);
		glpi_header($_POST["referer"]);
	}
	logEvent(0, "planning", 4, "planning", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_POST["referer"]);
	
} else if (isset($_POST["delete"])){
	
	deletePlanningTracking($_POST["ID"]);
	logEvent(0, "planning", 4, "planning", $_SESSION["glpiname"]." ".$lang["log"][22]);
	glpi_header($cfg_install["root"]."/tracking/tracking-info-form.php?ID=".$_POST["id_tracking"]);
		
} else if (isset($_GET["edit"])){
	commonHeader($lang["title"][31],$_SERVER["PHP_SELF"]);

	showAddPlanningTrackingForm($_SERVER["PHP_SELF"],$_GET["fup"],$_GET["ID"]);
	
	commonFooter();
} else {
	commonHeader($lang["title"][31],$_SERVER["PHP_SELF"]);

	showAddPlanningTrackingForm($_SERVER["PHP_SELF"],$_GET["fup"]);
	
	commonFooter();
}
?>