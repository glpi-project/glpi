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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------
 
include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_tracking.php");
require ("functions.php");

checkAuthentication("normal");

commonHeader($lang["title"][11],$_SERVER["PHP_SELF"]);

if(empty($_POST["date1"])&&empty($_POST["date2"])) {
$_POST["date1"]="0000-00-00";
$_POST["date2"]=date("Y-m-d");
}

if(empty($_POST["date1"])) $_POST["date1"] = "";
if(empty($_POST["date2"])) $_POST["date2"] = "";
if ($_POST["date1"]!=""&&$_POST["date2"]!=""&&strcmp($_POST["date2"],$_POST["date1"])<0){
$tmp=$_POST["date1"];
$_POST["date1"]=$_POST["date2"];
$_POST["date2"]=$tmp;
}


echo "<div align='center'><form method=\"post\" name=\"form\" action=\"stat_global.php\">";
echo "<table class='tab_cadre'><tr class='tab_bg_2'><td align='right'>";
echo $lang["search"][8]." :</td><td>";
showCalendarForm("form","date1",$_POST["date1"]);
echo "</td><td rowspan='2' align='center'><input type=\"submit\" class='button' name\"submit\" Value=\"". $lang["buttons"][7] ."\" /></td></tr>";
echo "<tr class='tab_bg_2'><td align='right'>".$lang["search"][9]." :</td><td>";
showCalendarForm("form","date2",$_POST["date2"]);
echo "</td></tr>";
echo "</table></form></div>";


///////// Stats nombre intervention
// Total des interventions
$entrees_total=constructEntryValues("inter_total",$_POST["date1"],$_POST["date2"]);
	if (count($entrees_total)>0)
graphByMonth($entrees_total,$lang["stats"][5],$lang["stats"][35]);

// Total des interventions résolues
$entrees_solved=constructEntryValues("inter_solved",$_POST["date1"],$_POST["date2"]);
	if (count($entrees_solved)>0)
graphByMonth($entrees_solved,$lang["stats"][11],$lang["stats"][35]);

//Temps moyen de resolution d'intervention
$entrees_avgtime=constructEntryValues("inter_avgsolvedtime",$_POST["date1"],$_POST["date2"]);
	if (count($entrees_avgtime)>0)
graphByMonth($entrees_avgtime,$lang["stats"][6],$lang["stats"][32],0);

//Temps moyen d'intervention réel
$entrees_avgtime=constructEntryValues("inter_avgrealtime",$_POST["date1"],$_POST["date2"]);
	if (count($entrees_avgtime)>0)
graphByMonth($entrees_avgtime,$lang["stats"][25],$lang["stats"][33],0);

//Temps moyen de prise en compte de l'intervention
$entrees_avgtime=constructEntryValues("inter_avgtakeaccount",$_POST["date1"],$_POST["date2"]);
	if (count($entrees_avgtime)>0)
graphByMonth($entrees_avgtime,$lang["stats"][30],$lang["stats"][32],0);

commonFooter();
?>
