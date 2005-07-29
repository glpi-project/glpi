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
 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_planning.php");
include ($phproot . "/glpi/includes_tracking.php");
include ($phproot . "/glpi/includes_users.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_monitors.php");
include ($phproot . "/glpi/includes_peripherals.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_software.php");
include ($phproot . "/glpi/includes_enterprises.php");


checkAuthentication("normal");

commonHeader($lang["title"][31],$_SERVER["PHP_SELF"]);

if (!isset($_GET["date"])) $_GET["date"]=strftime("%Y-%m-%d");
if (!isset($_GET["type"])) $_GET["type"]="week";
if (!isset($_GET["uID"])) $_GET["uID"]=$_SESSION["glpiID"];

titleTrackingPlanning();

	echo "<div align='center'><form method=\"get\" name=\"form\" action=\"index.php\">";
	echo "<table class='tab_cadre'><tr class='tab_bg_2'>";
	echo "<td>";
	dropdownUsers($_GET['uID'], "uID",1);
	echo "</td>";
	echo "<td align='right'>";
	echo $lang["planning"][4].":</td><td>";
	echo showCalendarForm("form","date",$_GET["date"]);
	echo "</td>";
	echo "<td><select name='type'>";
	echo "<option value='day' ".($_GET["type"]=="day"?" selected ":"").">".$lang["planning"][5]."</option>";
	echo "<option value='week' ".($_GET["type"]=="week"?" selected ":"").">".$lang["planning"][6]."</option>";
	echo "</select></td>";
	echo "<td rowspan='2' align='center'><input type=\"submit\" class='button' name=\"submit\" Value=\"". $lang["buttons"][7] ."\" /></td>";
	echo "</tr>";
	echo "</table></form></div>";

showPlanning($_GET['uID'],$_GET["date"],$_GET["type"]);

commonFooter();

?>
