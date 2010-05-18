<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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

$NEEDED_ITEMS=array("reservation","search","user","computer","printer","monitor","peripheral","networking","software","phone");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");


// Redirect management
if (isset($_GET["redirect"])){
	manageRedirect($_GET["redirect"]);
}

//*******************
	// Affichage Module reservation 
	//******************
	checkRight("reservation_helpdesk","1");
	$rr=new ReservationResa();
	if (isset($_POST["edit_resa"])){
		list($begin_year,$begin_month,$begin_day)=explode("-",$_POST["begin"]);
		$id_item=key($_POST["items"]);
		if ($_SESSION["glpiID"]==$_POST["id_user"]){
			$_POST['_target']=$_SERVER['PHP_SELF'];
			$_POST['_item']=key($_POST["items"]);

			if ($rr->update($_POST)){
					glpi_header($CFG_GLPI["root_doc"]."/front/helpdesk.resa.php?show=resa&ID=".$_POST['_item']."&mois_courant=$begin_month&annee_courante=$begin_year");
			} else {
				exit();
			}
		}
	}

	helpHeader($LANG['title'][1],$_SERVER['PHP_SELF'],$_SESSION["glpiname"]);

	if (isset($_POST["clear_resa"])){
		$id_item=key($_POST["items"]);
		if ($rr->delete($_POST)){ // delete() need an array !
			logEvent($_POST["ID"], "reservation", 4, "inventory", $_SESSION["glpiname"]." delete a reservation.");
		}
		list($begin_year,$begin_month,$begin_day)=explode("-",$_POST["begin_date"]);
		$_GET["mois_courant"]=$begin_month;
		$_GET["annee_courant"]=$begin_year;
		printCalendrier($_SERVER['PHP_SELF'],$id_item);

	}

	if (isset($_GET["ID"])){
		printCalendrier($_SERVER['PHP_SELF'],$_GET["ID"]);
	}
	else if (isset($_GET["add_item"])){
		if (!isset($_GET["date"])) $_GET["date"]=date("Y-m-d");
		showAddReservationForm($_SERVER['PHP_SELF'],$_GET["add_item"],$_GET["date"]);
	}
	else if (isset($_GET["edit"])){
		showAddReservationForm($_SERVER['PHP_SELF'],$_GET["edit_item"],"",$_GET["edit"]);
	}
	else if (isset($_POST["add_resa"])){
		$all_ok=true;
		$id_item=0;
		foreach ($_POST['items'] as $id_item){
			$_POST['id_item']=$id_item;
			$ok=true;
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
			for ($i=0;$i<$times&&$_POST['_ok'];$i++){
				$_POST["begin"]=date('Y-m-d H:i:s', strtotime($begin)+$i*$to_add*DAY_TIMESTAMP);
				$_POST["end"]=date('Y-m-d H:i:s', strtotime($end)+$i*$to_add*DAY_TIMESTAMP);

				if ($_SESSION["glpiID"]==$_POST["id_user"]) {
					unset($rr->fields["ID"]);
					$_POST['_ok']=$rr->add($_POST);
				}
	
			}
			// Positionnement du calendrier au mois de debut
			$_GET["mois_courant"]=$begin_month;
			$_GET["annee_courant"]=$begin_year;
	
			if ($_POST['_ok']){
				logEvent($_POST["id_item"], "reservation", 4, "inventory", $_SESSION["glpiname"]." add a reservation.");
			} else $all_ok=false;
		}

		if ($all_ok){
			// Several reservations
			if (count($_POST['items'])>1){
				glpi_header($CFG_GLPI["root_doc"] . "/front/helpdesk.resa.php?ID=");
			} else { // Only one reservation
				glpi_header($CFG_GLPI["root_doc"] . "/front/helpdesk.resa.php?ID=".$_POST['id_item']);
			}
		}
	}
	else {

		printReservationItems($_SERVER['PHP_SELF']);
	}

//*******************
// fin  Affichage Module reservation 
//*******************
helpFooter();


?>