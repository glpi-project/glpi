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

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

commonHeader($LANG['Menu'][13],$_SERVER['PHP_SELF'],"maintain","stat");

checkRight("statistic","1");

if (empty($_POST["date1"]) && empty($_POST["date2"])) {
   $year = date("Y")-1;
   $_POST["date1"] = date("Y-m-d",mktime(1,0,0,date("m"),date("d"),$year));
   $_POST["date2"] = date("Y-m-d");
}

if (!empty($_POST["date1"])
    && !empty($_POST["date2"])
    && strcmp($_POST["date2"],$_POST["date1"]) < 0) {

   $tmp = $_POST["date1"];
   $_POST["date1"] = $_POST["date2"];
   $_POST["date2"] = $tmp;
}

echo "<div class='center'><form method='post' name='form' action='stat.global.php'>";
echo "<table class='tab_cadre'><tr class='tab_bg_2'>";
echo "<td class='right'>".$LANG['search'][8]."&nbsp;:</td><td>";
showDateFormItem("date1",$_POST["date1"]);
echo "</td><td rowspan='2' class='center'>";
echo "<input type='submit' class='button' name='submit' value='". $LANG['buttons'][7] ."'></td></tr>";
echo "<tr class='tab_bg_2'><td class='right'>".$LANG['search'][9]."&nbsp;:</td><td>";
showDateFormItem("date2",$_POST["date2"]);
echo "</td></tr>";
echo "</table></form></div>";

///////// Stats nombre intervention
// Total des interventions
$entrees_total = Stat::constructEntryValues("inter_total",$_POST["date1"],$_POST["date2"]);
// Total des interventions résolues
$entrees_solved = Stat::constructEntryValues("inter_solved",$_POST["date1"],$_POST["date2"]);
//Temps moyen de resolution d'intervention
$entrees_avgsolvedtime = Stat::constructEntryValues("inter_avgsolvedtime",$_POST["date1"],$_POST["date2"]);
foreach ($entrees_avgsolvedtime as $key => $val) {
   $entrees_avgsolvedtime[$key] = $entrees_avgsolvedtime[$key] / HOUR_TIMESTAMP;
}

//Temps moyen d'intervention reel
$entrees_avgrealtime = Stat::constructEntryValues("inter_avgrealtime",$_POST["date1"],$_POST["date2"]);
foreach ($entrees_avgrealtime as $key => $val) {
   $entrees_avgrealtime[$key] = $entrees_avgrealtime[$key] / HOUR_TIMESTAMP;
}

//Temps moyen de prise en compte de l'intervention
$entrees_avgtaketime = Stat::constructEntryValues("inter_avgtakeaccount",$_POST["date1"],$_POST["date2"]);
foreach ($entrees_avgtaketime as $key => $val) {
   $entrees_avgtaketime[$key] = $entrees_avgtaketime[$key] / HOUR_TIMESTAMP;
}

Stat::showGraph(array($LANG['stats'][5]=>$entrees_total)
               ,array('title'=>$LANG['stats'][5],
                     'showtotal' => 1,
                     'unit'      => $LANG['stats'][35]));

Stat::showGraph(array($LANG['stats'][11]=>$entrees_solved)
               ,array('title'    => $LANG['stats'][11],
                     'showtotal' => 1,
                     'unit'      => $LANG['stats'][35]));

Stat::showGraph(array($LANG['stats'][6]=>$entrees_avgsolvedtime)
               ,array('title' => $LANG['stats'][6],
                     'unit'   => $LANG['job'][21]));

Stat::showGraph(array($LANG['stats'][25]=>$entrees_avgrealtime)
               ,array('title' => $LANG['stats'][25],
                     'unit'   => $LANG['job'][21]));


Stat::showGraph(array($LANG['stats'][30]=>$entrees_avgtaketime)
               ,array('title' => $LANG['stats'][30],
                     'unit'   => $LANG['job'][21]));

commonFooter();

?>
