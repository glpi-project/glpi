<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2004 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer 

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
include ($phproot . "/glpi/includes_tracking.php");
include ($phproot . "/glpi/includes_reservation.php");

checkAuthentication("post-only");

helpHeader("Helpdesk Access Only",$_SERVER["PHP_SELF"],$_SESSION["glpiname"]);

if (isset($_GET["show"]) && strcmp($_GET["show"],"user") == 0)
{
	include ($phproot . "/glpi/includes_computers.php");
	include ($phproot . "/glpi/includes_printers.php");
	include ($phproot . "/glpi/includes_peripherals.php");
	include ($phproot . "/glpi/includes_monitors.php");
	include ($phproot . "/glpi/includes_networking.php");

	if (!isset($_GET["ID"])) {
		showJobList($_SERVER["PHP_SELF"],$_SESSION["glpiname"],$_GET["show"],"","","",0);
	}
	else {
		 showJobDetails($_GET["ID"]);
	}
}
elseif (isset($_POST["add_resa"])||(isset($_GET["show"]) && strcmp($_GET["show"],"resa") == 0)){
	include ($phproot . "/glpi/includes_computers.php");
	include ($phproot . "/glpi/includes_printers.php");
	include ($phproot . "/glpi/includes_peripherals.php");
	include ($phproot . "/glpi/includes_monitors.php");
	include ($phproot . "/glpi/includes_networking.php");

	if (isset($_GET["clear"])){
		if (deleteReservation($_GET["clear"])){
			logEvent($_GET["clear"], "reservation", 4, "inventory", $_SESSION["glpiname"]."delete a reservation.");
		}
	}

	if (isset($_GET["ID"])){
		printCalendrier($_SERVER["PHP_SELF"],$_GET["ID"]);
	}
	else if (isset($_GET["add"])){
		showAddReservationForm($_SERVER["PHP_SELF"],$_GET["add"],$_GET["date"]);
	}
	else if (isset($_POST["add_resa"])){
		if (addReservation($_POST)){
			logEvent($_POST["id_item"], "reservation", 4, "inventory", $_SESSION["glpiname"]."add a reservation.");
			printCalendrier($_SERVER["PHP_SELF"],$_POST["id_item"]);
		}
	}
	else {
		printReservationItems();
	}
}
else {
printHelpDesk($_SESSION["glpiname"]);
}

helpFooter();

?>
