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

commonHeader("Stats",$_SERVER["PHP_SELF"]);

if(empty($_POST["date1"])) $_POST["date1"] = "";
if(empty($_POST["date2"])) $_POST["date2"] = "";
if ($_POST["date1"]!=""&&$_POST["date2"]!=""&&strcmp($_POST["date2"],$_POST["date1"])<0){
$tmp=$_POST["date1"];
$_POST["date1"]=$_POST["date2"];
$_POST["date2"]=$tmp;
}


echo "<div align='center'><form method=\"post\" name=\"form\" action=\"stat_global.php\">";
echo "<table class='tab_cadre'><tr class='tab_bg_2'><td align='right'>";
echo "Date de debut :</td><td> <input type=\"texte\" readonly name=\"date1\"  size ='10' value=\"". $_POST["date1"] ."\" /></td>";
echo "<td><input name='button' type='button' class='button'  onClick=\"window.open('$HTMLRel/mycalendar.php?form=form&amp;elem=date1&amp;value=".$_POST["date1"]."','".$lang["buttons"][15]."','width=200,height=220')\" value='".$lang["buttons"][15]."'>";
echo "</td><td><input name='button_reset' type='button' class='button' onClick=\"document.forms['form'].date1.value=''\" value='".$lang["buttons"][16]."'>";
echo "</td><td rowspan='2' align='center'><input type=\"submit\" class='button' name\"submit\" Value=\"". $lang["buttons"][7] ."\" /></td></tr>";
echo "<tr class='tab_bg_2'><td align='right'>Date de fin :</td><td><input type=\"texte\" readonly name=\"date2\"  size ='10' value=\"". $_POST["date2"] ."\" /></td>";
echo "<td><input name='button' type='button' class='button'  onClick=\"window.open('$HTMLRel/mycalendar.php?form=form&amp;elem=date2&amp;value=".$_POST["date2"]."','".$lang["buttons"][15]."','width=200,height=220')\" value='".$lang["buttons"][15]."'>";
echo "</td><td><input name='button_reset' type='button' class='button' onClick=\"document.forms['form'].date2.value=''\" value='".$lang["buttons"][16]."'>";
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
