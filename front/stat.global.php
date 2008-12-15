<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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


$NEEDED_ITEMS=array("tracking","stat");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");


commonHeader($LANG["Menu"][13],$_SERVER['PHP_SELF'],"maintain","stat");

checkRight("statistic","1");

if(empty($_POST["date1"])&&empty($_POST["date2"])) {
	$year=date("Y")-1;
	$_POST["date1"]=date("Y-m-d",mktime(1,0,0,date("m"),date("d"),$year));

	$_POST["date2"]=date("Y-m-d");
}


if (!empty($_POST["date1"])&&!empty($_POST["date2"])&&strcmp($_POST["date2"],$_POST["date1"])<0){
	$tmp=$_POST["date1"];
	$_POST["date1"]=$_POST["date2"];
	$_POST["date2"]=$tmp;
}


echo "<div align='center'><form method=\"post\" name=\"form\" action=\"stat.global.php\">";
echo "<table class='tab_cadre'><tr class='tab_bg_2'><td align='right'>";
echo $LANG["search"][8]." :</td><td>";
showDateFormItem("date1",$_POST["date1"]);
echo "</td><td rowspan='2' align='center'><input type=\"submit\" class='button' name=\"submit\" Value=\"". $LANG["buttons"][7] ."\"></td></tr>";
echo "<tr class='tab_bg_2'><td align='right'>".$LANG["search"][9]." :</td><td>";
showDateFormItem("date2",$_POST["date2"]);
echo "</td></tr>";
echo "</table></form></div>";


///////// Stats nombre intervention
// Total des interventions
$entrees_total=constructEntryValues("inter_total",$_POST["date1"],$_POST["date2"]);
if (count($entrees_total)>0)
	graphBy($entrees_total,$LANG["stats"][5],$LANG["stats"][35],1,"month");

	// Total des interventions r�olues
	$entrees_solved=constructEntryValues("inter_solved",$_POST["date1"],$_POST["date2"]);
if (count($entrees_solved)>0)
	graphBy($entrees_solved,$LANG["stats"][11],$LANG["stats"][35],1,"month");

	//Temps moyen de resolution d'intervention
	$entrees_avgtime=constructEntryValues("inter_avgsolvedtime",$_POST["date1"],$_POST["date2"]);
if (count($entrees_avgtime)>0)
	graphBy($entrees_avgtime,$LANG["stats"][6],$LANG["job"][21],0,"month");

	//Temps moyen d'intervention r�l
	$entrees_avgtime=constructEntryValues("inter_avgrealtime",$_POST["date1"],$_POST["date2"]);
if (count($entrees_avgtime)>0)
	graphBy($entrees_avgtime,$LANG["stats"][25],$LANG["stats"][33],0,"month");

	//Temps moyen de prise en compte de l'intervention
	$entrees_avgtime=constructEntryValues("inter_avgtakeaccount",$_POST["date1"],$_POST["date2"]);
if (count($entrees_avgtime)>0)
	graphBy($entrees_avgtime,$LANG["stats"][30],$LANG["job"][21],0,"month");

	commonFooter();
	?>
