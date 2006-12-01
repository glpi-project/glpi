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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
$NEEDED_ITEMS=array("planning","tracking","user","computer","printer","monitor","peripheral","networking","software","enterprise","reminder","phone");
include ($phproot . "/inc/includes.php");

checkRight("comment_all_ticket","1");

$pt=new PlanningTracking();

if (isset($_POST["add_planning"])){

	if ($pt->add($_POST,"")){
		logEvent(0, "planning", 4, "planning", $_SESSION["glpiname"]." ".$lang["log"][20]);
		glpi_header($cfg_glpi["root_doc"]."/front/tracking.form.php?ID=".$_POST["id_tracking"]);
	} 
	logEvent(0, "planning", 4, "planning", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_POST["referer"]);

} else if (isset($_POST["edit_planning"])){

	if ($pt->update($_POST,$_SERVER['PHP_SELF'],$_POST["ID"])){
		logEvent(0, "planning", 4, "planning", $_SESSION["glpiname"]." ".$lang["log"][21]);
		glpi_header($_POST["referer"]);
	}
	logEvent(0, "planning", 4, "planning", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_POST["referer"]);

} else if (isset($_POST["delete"])){

	$pt->delete($_POST["ID"]);
	logEvent(0, "planning", 4, "planning", $_SESSION["glpiname"]." ".$lang["log"][22]);
	glpi_header($cfg_glpi["root_doc"]."/front/tracking.form.php?ID=".$_POST["id_tracking"]);

} else if (isset($_GET["edit"])){
	commonHeader($lang["title"][31],$_SERVER['PHP_SELF']);

	showAddPlanningTrackingForm($_SERVER['PHP_SELF'],$_GET["fup"],$_GET["ID"]);

	commonFooter();
} else {
	commonHeader($lang["title"][31],$_SERVER['PHP_SELF']);

	showAddPlanningTrackingForm($_SERVER['PHP_SELF'],$_GET["fup"]);

	commonFooter();
}
?>
