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

$NEEDED_ITEMS=array("reservation","user","computer","printer","monitor","peripheral","networking","software","phone");
include ($phproot . "/inc/includes.php");


if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";


$ri=new ReservationItem();
$rr=new ReservationResa();
if (isset($_POST["clear_resa"])||isset($_POST["add_resa"])||isset($_POST["edit_resa"])||(isset($_GET["show"]) && strcmp($_GET["show"],"resa") == 0)){

	checkRight("reservation_helpdesk","1");

	if (isset($_POST["edit_resa"])){
		list($begin_year,$begin_month,$begin_day)=split("-",$_POST["begin_date"]);

		if (haveRight("reservation_central","w")||$_SESSION["glpiID"]==$_POST["id_user"]) 
			if ($rr->update($_POST,$_SERVER['PHP_SELF'],$_POST["id_item"]))
				glpi_header($cfg_glpi["root_doc"]."/front/reservation.php?show=resa&ID=".$_POST["id_item"]."&mois_courant=$begin_month&annee_courante=$begin_year");
	}


	commonHeader($lang["title"][35],$_SERVER['PHP_SELF']);

	if (isset($_POST["clear_resa"])){

		if ($rr->delete($_POST)){
			logEvent($_POST["ID"], "reservation", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][22]);
		}

		list($begin_year,$begin_month,$begin_day)=split("-",$_POST["begin_date"]);
		$_GET["mois_courant"]=$begin_month;
		$_GET["annee_courant"]=$begin_year;
		printCalendrier($_SERVER['PHP_SELF'],$_POST["id_item"]);
	} else if (isset($_GET["ID"])){
		printCalendrier($_SERVER['PHP_SELF'],$_GET["ID"]);
	}
	else if (isset($_GET["add"])){
		showAddReservationForm($_SERVER['PHP_SELF'],$_GET["add"],$_GET["date"]);
	}
	else if (isset($_GET["edit"])){
		showAddReservationForm($_SERVER['PHP_SELF'],$_GET["item"],"",$_GET["edit"]);
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

			if (haveRight("reservation_central","w")||$_SESSION["glpiID"]==$_POST["id_user"]) {
				unset($rr->fields["ID"]);
				$ok=$rr->add($_POST,$_SERVER['PHP_SELF'],$ok);
			}

		}
		// Positionnement du calendrier au mois de debut
		$_GET["mois_courant"]=$begin_month;
		$_GET["annee_courant"]=$begin_year;

		if ($ok){
			logEvent($_POST["id_item"], "reservation", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][20]);
			printCalendrier($_SERVER['PHP_SELF'],$_POST["id_item"]);
		}
	}
	else {
		printReservationItems($_SERVER['PHP_SELF']);
	}
}
else {
	checkSeveralRightsOr(array("reservation_central"=>"r","reservation_helpdesk"=>"1"));
	if (!haveRight("reservation_central","r")){
		commonHeader($lang["title"][9],$_SERVER['PHP_SELF']);
		printReservationItems($_SERVER['PHP_SELF']);
	}
	else {
		if (isset($_GET["add"]))
		{
			checkRight("reservation_central","w");
			$ri->add($_GET);
			logEvent(0, "reservation", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][20]." ".$_GET["device_type"]."-".$_GET["id_device"].".");
			glpi_header($_SERVER['HTTP_REFERER']);
		} 
		else if (isset($_GET["delete"]))
		{
			checkRight("reservation_central","w");
			$ri->delete($_GET);
			logEvent(0, "reservation", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][22]);
			glpi_header($_SERVER['HTTP_REFERER']);
		}


		if (isset($_POST["updatecomment"]))
		{
			checkRight("reservation_central","w");
			$ri->update($_POST);
			logEvent(0, "reservation", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][21]);
		} 

		if(!isset($_GET["start"])) $_GET["start"] = 0;
		if (!isset($_GET["order"])) $_GET["order"] = "ASC";
		if (!isset($_GET["field"])) $_GET["field"] = "glpi_reservation_item.ID";
		if (!isset($_GET["phrasetype"])) $_GET["phrasetype"] = "contains";
		if (!isset($_GET["contains"])) $_GET["contains"] = "";
		if (!isset($_GET["sort"])) $_GET["sort"] = "glpi_reservation_item.ID";


		checkRight("reservation_central","r");

		commonHeader($lang["title"][35],$_SERVER['PHP_SELF']);
		if (isset($_GET["comment"])){
			if (showReservationCommentForm($_SERVER['PHP_SELF'],$_GET["comment"])){
			}
			else {

				titleReservation();
				searchFormReservationItem($_GET["field"],$_GET["phrasetype"],$_GET["contains"],$_GET["sort"]);
				showReservationItemList($_SERVER['PHP_SELF'],$_SESSION["glpiname"],$_GET["field"],$_GET["phrasetype"],$_GET["contains"],$_GET["sort"],$_GET["order"],$_GET["start"]);
			}

		}else {

			titleReservation();
			searchFormReservationItem($_GET["field"],$_GET["phrasetype"],$_GET["contains"],$_GET["sort"]);
			showReservationItemList($_SERVER['PHP_SELF'],$_SESSION["glpiname"],$_GET["field"],$_GET["phrasetype"],$_GET["contains"],$_GET["sort"],$_GET["order"],$_GET["start"]);
		}
	}
}
commonFooter();


?>
