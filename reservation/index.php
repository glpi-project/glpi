<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2004 by the INDEPNET Development Team.
 
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
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------


*/
 

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_setup.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_monitors.php");
include ($phproot . "/glpi/includes_peripherals.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_reservation.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";


if (isset($_POST["add_resa"])||(isset($_GET["show"]) && strcmp($_GET["show"],"resa") == 0)){
	checkAuthentication("normal");

	commonHeader("Reservation",$_SERVER["PHP_SELF"]);

	if (isset($_GET["clear"])){
		if (deleteReservation($_GET["clear"])){
			logEvent($_GET["clear"], "reservation", 4, "inventory", $_SESSION["glpiname"]." delete a reservation.");
		}
	}

	if (isset($_GET["ID"])){
		printCalendrier($_SERVER["PHP_SELF"],$_GET["ID"]);
	}
	else if (isset($_GET["add"])){
		showAddReservationForm($_SERVER["PHP_SELF"],$_GET["add"],$_GET["date"]);
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
			$ok=addReservation($_POST,$_SERVER["PHP_SELF"],$ok);

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
else {
	checkAuthentication("normal");
	if ($_SESSION["glpitype"]=="normal"){
		commonHeader("Reservation",$_SERVER["PHP_SELF"]);
		printReservationItems($_SERVER["PHP_SELF"]);
	}
	// On est pas normal -> admin ou super-admin
	else {
	if (isset($_GET["add"]))
	{
		addReservationItem($_GET);
		logEvent(0, "reservation", 4, "inventory", $_SESSION["glpiname"]." added reservation item ".$_GET["device_type"]."-".$_GET["id_device"].".");
		header("Location: $_SERVER[HTTP_REFERER]");
	} 
	else if (isset($_GET["delete"]))
	{
		deleteReservationItem($_GET);
		logEvent(0, "reservation", 4, "inventory", $_SESSION["glpiname"]." deleted reservation item.");
		header("Location: $_SERVER[HTTP_REFERER]");
	}


	if (isset($_POST["updatecomment"]))
	{
		updateReservationComment($_POST);
		logEvent(0, "reservation", 4, "inventory", $_SESSION["glpiname"]." update reservation comment.");
	} 

	if(!isset($_GET["start"])) $_GET["start"] = 0;
	if (!isset($_GET["order"])) $_GET["order"] = "ASC";
	if (!isset($_GET["field"])) $_GET["field"] = "glpi_reservation_item.ID";
	if (!isset($_GET["phrasetype"])) $_GET["phrasetype"] = "contains";
	if (!isset($_GET["contains"])) $_GET["contains"] = "";
	if (!isset($_GET["sort"])) $_GET["sort"] = "glpi_reservation_item.ID";


	checkAuthentication("admin");

	commonHeader("Reservation",$_SERVER["PHP_SELF"]);
	if (isset($_GET["comment"])){
		if (showReservationCommentForm($_SERVER["PHP_SELF"],$_GET["comment"])){
			}
		else {
			titleReservation();
			searchFormReservationItem($_GET["field"],$_GET["phrasetype"],$_GET["contains"],$_GET["sort"]);
			showReservationItemList($_SERVER["PHP_SELF"],$_SESSION["glpiname"],$_GET["field"],$_GET["phrasetype"],$_GET["contains"],$_GET["sort"],$_GET["order"],$_GET["start"]);
		}
		
	}else {
	
	titleReservation();
	searchFormReservationItem($_GET["field"],$_GET["phrasetype"],$_GET["contains"],$_GET["sort"]);
	showReservationItemList($_SERVER["PHP_SELF"],$_SESSION["glpiname"],$_GET["field"],$_GET["phrasetype"],$_GET["contains"],$_GET["sort"],$_GET["order"],$_GET["start"]);
	}
}
}
commonFooter();


?>