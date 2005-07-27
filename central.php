<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2005 by the INDEPNET Development Team.
 
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
 
// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
include ("glpi/includes.php");
include ($phproot . "/glpi/includes_tracking.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_monitors.php");
include ($phproot . "/glpi/includes_peripherals.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_software.php");
include ($phproot . "/glpi/includes_enterprises.php");
include ($phproot . "/glpi/includes_setup.php");


checkAuthentication("normal");

commonHeader($lang["title"][0],$_SERVER["PHP_SELF"]);

// Greet the user

echo "<center><b>".$lang["central"][0]." ".(empty($_SESSION["glpirealname"])?$_SESSION["glpiname"]:$_SESSION["glpirealname"]).", ".$lang["central"][1]."</b></center>";
//echo "<hr noshade>";

if (!isset($_GET['start'])) $_GET['start']=0;

echo "<div align='center'>";
echo "<table><tr><td align='center' width='45%'>";
echo "<b>".$lang["central"][9]."</b><br>";
showCentralJobList($_SERVER["PHP_SELF"],$_GET['start']);
echo "</td><td  align='center'  width='45%'>";
// Show last add events
showAddEvents($_SERVER["PHP_SELF"],"","",$_SESSION["glpiname"]);
echo "</td></tr></table>";
echo "</div>";


if(empty($_GET["start"])) $_GET["start"] = 0;
	showJobList($_SERVER["PHP_SELF"],"","unassigned","","","",$_GET["start"]);

commonFooter();

?>
