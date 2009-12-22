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


define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");


checkCentralAccess();

/// TODO management of reservation to reservation.form.php : Same to helpdesk : clean helpdesk.resa.php too

$rr=new Reservation();

if (isset($_POST["clear_resa"])||isset($_POST["add_resa"])||isset($_POST["edit_resa"])||(isset($_GET["show"]) && strcmp($_GET["show"],"resa") == 0)){

	checkRight("reservation_helpdesk","1");

   commonHeader($LANG['Menu'][17],$_SERVER['PHP_SELF'],"utils","reservation");

	if (isset($_POST["edit_resa"])){
		list($begin_year,$begin_month,$begin_day)=explode("-",$_POST["begin"]);
		if (haveRight("reservation_central","w")||$_SESSION["glpiID"]==$_POST["users_id"]){
			$_POST['_target']=$_SERVER['PHP_SELF'];
			$_POST['_item']=key($_POST["items"]);
			if ($rr->update($_POST)){
				glpi_header($CFG_GLPI["root_doc"]."/front/reservationitem.php?show=resa&id=".$_POST['_item']."&mois_courant=$begin_month&annee_courante=$begin_year");
			}
		}
	} else if (isset($_POST["clear_resa"])){
		$reservationitems_id=key($_POST["items"]);
		if ($rr->delete($_POST)){
			Event::log($_POST["id"], "reservation", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][22]);
		}

		list($begin_year,$begin_month,$begin_day)=explode("-",$_POST["begin"]);
		$_GET["mois_courant"]=$begin_month;
		$_GET["annee_courant"]=$begin_year;
		printCalendrier($_SERVER['PHP_SELF'],$reservationitems_id);
	} else if (isset($_GET["add_resa_on_item"])){
		if (!isset($_GET["date"])) $_GET["date"]=date("Y-m-d");
		if (count($_GET["add_item"])){
			showAddReservationForm($_SERVER['PHP_SELF'],$_GET["add_item"],$_GET["date"]);
		}else {
			glpi_header($_SERVER['HTTP_REFERER']);
		}
	}
	else if (isset($_GET["edit"])){
		showAddReservationForm($_SERVER['PHP_SELF'],$_GET["edit_item"],"",$_GET["edit"]);
	}
	else if (isset($_POST["add_resa"])){
		$all_ok=true;
		$reservationitems_id=0;
		foreach ($_POST['items'] as $reservationitems_id){
			$_POST['reservationitems_id']=$reservationitems_id;

			$times=$_POST["periodicity_times"];
			$begin=$_POST["begin"];
			list($begin_year,$begin_month,$begin_day)=explode("-",$_POST["begin"]);
			$end=$_POST["end"];
			$to_add=1;

			if ($_POST["periodicity"]=="week") {
				$to_add=7;
			}
			$_POST['_target']=$_SERVER['PHP_SELF'];

			$_POST['_ok']=true;
			for ($i=0;$i<$times&&($_POST['_ok']);$i++){
				$_POST["begin"]=date('Y-m-d H:i:s', strtotime($begin)+$i*$to_add*DAY_TIMESTAMP);
				$_POST["end"]=date('Y-m-d H:i:s', strtotime($end)+$i*$to_add*DAY_TIMESTAMP);

				if (haveRight("reservation_central","w")||$_SESSION["glpiID"]==$_POST["users_id"]) {
					unset($rr->fields["id"]);
					$_POST['_ok']=$rr->add($_POST);
				}

			}
			// Positionnement du calendrier au mois de debut
			$_GET["mois_courant"]=$begin_month;
			$_GET["annee_courant"]=$begin_year;

			if ($_POST['_ok']){
				Event::log($_POST["reservationitems_id"], "reservation", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][20]);
			} else $all_ok=false;

			if ($all_ok){
				// Several reservations
				if (count($_POST['items'])>1){
					glpi_header($CFG_GLPI["root_doc"] . "/front/reservationitem.php?show=resa&id=");
				} else { // Only one reservation
					glpi_header($CFG_GLPI["root_doc"] . "/front/reservationitem.php?show=resa&id=$reservationitems_id");
				}
			}
		}

	} else if (isset($_GET["id"])){
		printCalendrier($_SERVER['PHP_SELF'],$_GET["id"]);
	}else {

      Search::show('ReservationItem');

//		printReservationItems($_SERVER['PHP_SELF']);
	}
}
else {
	checkSeveralRightsOr(array("reservation_central"=>"r","reservation_helpdesk"=>"1"));
	if (!haveRight("reservation_central","r")){
		commonHeader($LANG['Menu'][17],$_SERVER['PHP_SELF'],"utils","reservation");
		printReservationItems($_SERVER['PHP_SELF']);
	}
	else {

		checkRight("reservation_central","r");

		commonHeader($LANG['Menu'][17],$_SERVER['PHP_SELF'],"utils","reservation");

      Search::show('ReservationItem');
	}
}
commonFooter();


?>
