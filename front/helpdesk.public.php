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

$NEEDED_ITEMS=array("user","tracking","reservation","document","knowbase","computer","printer","networking","peripheral","monitor","software","infocom","phone","enterprise");
include ($phproot . "/inc/includes.php");


// Redirect management
if (isset($_GET['redirect'])){
	checkHelpdeskAccess();
	list($type,$ID)=split("_",$_GET["redirect"]);
	glpi_header($cfg_glpi["root_doc"]."/front/helpdesk.public.php?show=user&ID=$ID");
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

		logEvent($_POST["tracking"], "tracking", 4, "tracking", $_SESSION["glpiname"]." ".$lang["log"][20]." $newID.");
		glpi_header($_SERVER['HTTP_REFERER']);

	}	
	if (!isset($_GET["start"])) $_GET["start"]=0;

	helpHeader($lang["title"][1],$_SERVER["PHP_SELF"],$_SESSION["glpiname"]);

	if (!isset($_GET["ID"])) {
		if (!isset($_GET["start"])) $_GET["start"]=0;
		if (!isset($_GET["status"])) $_GET["status"]="all";
		if (!isset($_GET["sort"])) $_GET["sort"]="";
		if (!isset($_GET["order"])) $_GET["order"]="DESC";
		searchSimpleFormTracking($_SERVER["PHP_SELF"],$_GET["status"]);
		showTrackingList($_SERVER["PHP_SELF"],$_GET["start"],$_GET["sort"],$_GET["order"],$_GET["status"],$_SESSION["glpiID"],-1);
	}
	else {
		if (isset($_POST["update"])){
			$track=new Job();
			$track->update($_POST);
		}

		if (showJobDetails($_SERVER["PHP_SELF"]."?show=user&ID=".$_GET["ID"],$_GET["ID"]))
			showFollowupsSummary($_GET["ID"]);
	}
}
elseif (isset($_POST["clear_resa"])||isset($_POST["edit_resa"])||isset($_POST["add_resa"])||(isset($_GET["show"]) && strcmp($_GET["show"],"resa") == 0)){

	//*******************
	// Affichage Module réservation 
	//******************
	checkRight("reservation_helpdesk","1");
	$rr=new ReservationResa();
	if (isset($_POST["edit_resa"])){
		list($begin_year,$begin_month,$begin_day)=split("-",$_POST["begin_date"]);

		if ($_SESSION["glpiID"]==$_POST["id_user"]) 
			if ($rr->update($_POST,$_SERVER["PHP_SELF"],$_POST["id_item"]))
				glpi_header($cfg_glpi["root_doc"]."/front/helpdesk.public.php?show=resa&ID=".$_POST["id_item"]."&mois_courant=$begin_month&annee_courante=$begin_year");
			else exit();
	}

	helpHeader($lang["title"][1],$_SERVER["PHP_SELF"],$_SESSION["glpiname"]);

	if (isset($_POST["clear_resa"])){
		if ($rr->delete($_POST)){ // delete() need an array !
			logEvent($_POST["ID"], "reservation", 4, "inventory", $_SESSION["glpiname"]."delete a reservation.");
		}
		list($begin_year,$begin_month,$begin_day)=split("-",$_POST["begin_date"]);
		$_GET["mois_courant"]=$begin_month;
		$_GET["annee_courant"]=$begin_year;
		printCalendrier($_SERVER["PHP_SELF"],$_POST["id_item"]);

	}

	if (isset($_GET["ID"])){
		printCalendrier($_SERVER["PHP_SELF"],$_GET["ID"]);
	}
	else if (isset($_GET["add"])){
		showAddReservationForm($_SERVER["PHP_SELF"],$_GET["add"],$_GET["date"]);
	}
	else if (isset($_GET["edit"])){
		showAddReservationForm($_SERVER["PHP_SELF"],$_GET["item"],"",$_GET["edit"]);
	}
	else if (isset($_POST["add_resa"])){
		$ok=true;
		$times=$_POST["periodicity_times"];
		list($begin_year,$begin_month,$begin_day)=split("-",$_POST["begin_date"]);
		list($end_year,$end_month,$end_day)=split("-",$_POST["end_date"]);
		$to_add=1;
		if ($_POST["periodicity"]=="week") $to_add=7;
		for ($i=1;$i<=$times&&$ok;$i++){
			$_POST["begin_date"]=date("Y-m-d",mktime(0,0,0,$begin_month,$begin_day+($i-1)*$to_add,$begin_year));
			$_POST["end_date"]=date("Y-m-d",mktime(0,0,0,$end_month,$end_day+($i-1)*$to_add,$end_year));
			if ($_SESSION["glpiID"]==$_POST["id_user"]) {
				unset($rr->fields["ID"]);
				$ok=$rr->add($_POST,$_SERVER["PHP_SELF"],$ok);
			}

		}
		// Positionnement du calendrier au mois de debut
		$_GET["mois_courant"]=$begin_month;
		$_GET["annee_courant"]=$begin_year;

		if ($ok){
			logEvent($_POST["id_item"], "reservation", 4, "inventory", $_SESSION["glpiname"]." add a reservation.");
			printCalendrier($_SERVER["PHP_SELF"],$_POST["id_item"]);
		}
	}
	else {
		printReservationItems($_SERVER["PHP_SELF"]);
	}
}
//*******************
// fin  Affichage Module réservation 
//*******************


//*******************
// Affichage Module FAQ
//******************



else if (isset($_GET["show"]) && strcmp($_GET["show"],"faq") == 0){
	$name="";
	checkRight("faq","r");
	helpHeader($lang["title"][1],$_SERVER["PHP_SELF"],$_SESSION["glpiname"]);


	if (isset($_GET["ID"])){

		if (ShowKbItemFull($_GET["ID"],"no"))
			showDocumentAssociated(KNOWBASE_TYPE,$_GET["ID"],3);

	} else {
		initExpandSessionVar();

		if (isset($_GET["toshow"])) {
			if ($_GET["toshow"]=="all")
				ExpandSessionVarShowAll();
			else ExpandSessionVarShow($_GET["toshow"]);
		}
		if (isset($_GET["tohide"])) {
			if ($_GET["tohide"]=="all")
				ExpandSessionVarHideAll();
			else ExpandSessionVarHide($_GET["tohide"]);
		}
		if (isset($_POST["contains"])) $contains=$_POST["contains"];
		else $contains="";

		if (!empty($contains)) searchLimitSessionVarKnowbase($contains);

		faqShowCategoriesall($_SERVER["PHP_SELF"]."?show=faq",$contains);
	}
}
//*******************
//  fin Affichage Module FAQ
//******************


else {
	checkHelpdeskAccess();
	helpHeader($lang["title"][1],$_SERVER["PHP_SELF"],$_SESSION["glpiname"]);

	printHelpDesk($_SESSION["glpiID"],1);
}

helpFooter();

?>
