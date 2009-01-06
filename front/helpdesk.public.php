<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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



$NEEDED_ITEMS=array("user","tracking","reservation","document","knowbase","computer","printer","networking","peripheral","monitor","software","infocom","phone","enterprise","group");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

	// Change profile system
	if (isset ($_POST['newprofile'])) {
		if (isset ($_SESSION["glpiprofiles"][$_POST['newprofile']])) {
			changeProfile($_POST['newprofile']);
			if ($_SESSION["glpiactiveprofile"]["interface"]=="central"){
				glpi_header($CFG_GLPI['root_doc']."/front/central.php");
			} else {
				glpi_header($_SERVER['PHP_SELF']);
			}
		} else {
			glpi_header(preg_replace("/FK_entities=.*/","",$_SERVER['HTTP_REFERER']));
		}
	}

	// Manage entity change
	if (isset($_GET["active_entity"])){
		if (!isset($_GET["recursive"])) {
			$_GET["recursive"]=0;
		}
		changeActiveEntities($_GET["active_entity"],$_GET["recursive"]);
		if ($_GET["active_entity"]==$_SESSION["glpiactive_entity"]){
			glpi_header(preg_replace("/FK_entities.*/","",$_SERVER['HTTP_REFERER']));
		}
	}

// Redirect management
if (isset($_GET["redirect"])){
	manageRedirect($_GET["redirect"]);
}

if (isset($_GET["show"]) && strcmp($_GET["show"],"user") == 0)
{

	checkHelpdeskAccess();
	//*******************
	// Affichage interventions en cours
	//******************
	if (isset($_POST['add'])&&haveRight("comment_ticket","1")) {
		$fup=new Followup();
		$newID=$fup->add($_POST);

		logEvent($_POST["tracking"], "tracking", 4, "tracking", $_SESSION["glpiname"]." ".$LANG["log"][20]." $newID.");
		glpi_header($_SERVER['HTTP_REFERER']);

	}	
	if (!isset($_GET["start"])) $_GET["start"]=0;

	helpHeader($LANG["title"][1],$_SERVER['PHP_SELF'],$_SESSION["glpiname"]);

	if (!isset($_GET["ID"])) {
		if (!isset($_GET["start"])) $_GET["start"]=0;
		if (!isset($_GET["status"])) $_GET["status"]="all";
		if (!isset($_GET["sort"])) $_GET["sort"]="";
		if (!isset($_GET["search"])) $_GET["search"]="";
		if (!isset($_GET["tosearch"])) $_GET["tosearch"]="name_contents";
		if (!isset($_GET["order"])) $_GET["order"]="DESC";
		if (!isset($_GET["group"])) $_GET["group"]=0;
		if (!isset($_GET["category"])) $_GET["category"]=0;
		if (!isset($_GET["showfollowups"])) $_GET["showfollowups"]=0;
		if (!isset($_GET["extended"])) $_GET["extended"]=0;

		searchSimpleFormTracking($_GET["extended"],$_SERVER['PHP_SELF'],$_GET["status"],$_GET["tosearch"],$_GET["search"],$_GET["group"],$_GET["showfollowups"],$_GET["category"]);
		showTrackingList($_SERVER['PHP_SELF'],$_GET["start"],$_GET["sort"],$_GET["order"],$_GET["status"],$_GET["tosearch"],$_GET["search"],$_SESSION["glpiID"],$_GET["group"],$_GET["showfollowups"],$_GET["category"]);
	}
	else {
		if (isset($_POST["update"])){
			$track=new Job();
			$track->update($_POST);
			glpi_header($_SERVER['PHP_SELF']."?show=user&ID=".$_POST["ID"]);
		}

		if (showJobDetails($_SERVER['PHP_SELF']."?show=user&ID=".$_GET["ID"],$_GET["ID"]))
			showFollowupsSummary($_GET["ID"]);
	}
}

//*******************
// fin  Affichage Module r�ervation 
//*******************


else {
	checkHelpdeskAccess();
	helpHeader($LANG["title"][1],$_SERVER['PHP_SELF'],$_SESSION["glpiname"]);

	printHelpDesk($_SESSION["glpiID"],1);
}

helpFooter();

?>
