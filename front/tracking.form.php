<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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

define('GLPI_ROOT', '..');
$NEEDED_ITEMS=array("group","user","planning","tracking","computer","printer","monitor","peripheral","networking","software","enterprise","phone","document","mailing");
include (GLPI_ROOT . "/inc/includes.php");

checkCentralAccess();


$fup=new Followup();
$track=new Job();

commonHeader($LANG["title"][10],$_SERVER['PHP_SELF'],"maintain","tracking");
if (isset($_POST['update'])){
	checkSeveralRightsOr(array("update_ticket"=>"1","assign_ticket"=>"1","steal_ticket"=>"1","comment_ticket"=>"1","comment_all_ticket"=>"1"));

	$track->update($_POST);
	logEvent($_POST["ID"], "tracking", 4, "tracking", $_SESSION["glpiname"]." ".$LANG["log"][21]);

	glpi_header($CFG_GLPI["root_doc"]."/front/tracking.form.php?ID=".$_POST["ID"]);


}else if (isset($_POST['add'])||isset($_POST['add_close'])) {
	checkSeveralRightsOr(array("comment_ticket"=>"1","comment_all_ticket"=>"1","show_assign_ticket"=>"1"));
	$newID=$fup->add($_POST);

	logEvent($_POST["tracking"], "tracking", 4, "tracking", $_SESSION["glpiname"]." ".$LANG["log"][20]." $newID.");
	glpi_header($CFG_GLPI["root_doc"]."/front/tracking.form.php?ID=".$_POST["tracking"]);

} else if (isset($_POST["update_followup"])){
	checkRight("comment_all_ticket","1");
	$fup->update($_POST);

	logEvent($_POST["tracking"], "tracking", 4, "tracking", $_SESSION["glpiname"]."  ".$LANG["log"][21]." ".$_POST["ID"].".");
	glpi_header($CFG_GLPI["root_doc"]."/front/tracking.form.php?ID=".$_POST["tracking"]);
} else if (isset($_POST["delete_followup"])){
	checkRight("comment_all_ticket","1");
	$fup->delete($_POST);
	logEvent($_POST["tracking"], "tracking", 4, "tracking", $_SESSION["glpiname"]." ".$LANG["log"][22]." ".$_POST["ID"].".");
	glpi_header($CFG_GLPI["root_doc"]."/front/tracking.form.php?ID=".$_POST["tracking"]);
}

// Manage All case which does not exist
if (!isset($_SESSION['glpi_onglet'])||$_SESSION['glpi_onglet']==-1) $_SESSION['glpi_onglet']=1;
if (isset($_GET['onglet'])) {
	$_SESSION['glpi_onglet']=$_GET['onglet'];
}
if (isset($_GET["ID"]))
if (showJobDetails($_SERVER['PHP_SELF'],$_GET["ID"])){
	switch($_SESSION['glpi_onglet']){
		default :
			if (!displayPluginAction(TRACKING_TYPE,$_GET["ID"],$_SESSION['glpi_onglet'])){
				showFollowupsSummary($_GET["ID"]);
			}
	}
}
commonFooter();
?>
