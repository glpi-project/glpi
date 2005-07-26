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
// Original Author of file: Mustapha Saddalah et Bazile Lebeau
// Purpose of file:
// ----------------------------------------------------------------------
 
include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_tracking.php");
include ($phproot . "/glpi/includes_enterprises.php");
include ($phproot . "/glpi/includes_setup.php");
require ("functions.php");


checkAuthentication("normal");

commonHeader($lang["title"][11],$_SERVER["PHP_SELF"]);

echo "<div align ='center'><p><b>".$lang["stats"][17]."</b></p></div>";
if(empty($_POST["date1"])) $_POST["date1"] = "";
if(empty($_POST["date2"])) $_POST["date2"] = "";
if ($_POST["date1"]!=""&&$_POST["date2"]!=""&&strcmp($_POST["date2"],$_POST["date1"])<0){
$tmp=$_POST["date1"];
$_POST["date1"]=$_POST["date2"];
$_POST["date2"]=$tmp;
}

echo "<div align='center'><form method=\"post\" name=\"form\" action=\"stat_technicien.php\">";
echo "<table class='tab_cadre'><tr class='tab_bg_2'><td align='right'>";
echo $lang["search"][8]." :</td><td>";
showCalendarForm("form","date1",$_POST["date1"]);
echo "</td><td rowspan='2' align='center'><input type=\"submit\" class='button' name=\"submit\" Value=\"". $lang["buttons"][7] ."\" /></td></tr>";
echo "<tr class='tab_bg_2'><td align='right'>".$lang["search"][9]." :</td><td>";
showCalendarForm("form","date2",$_POST["date2"]);
echo "</td></tr>";
echo "</table></form></div>";


//recuperation des different utilisateurs ayant eu des interventions attribuées
//get distinct user who has intervention assigned to
$nomTech = getNbIntervTech();

echo "<div align ='center'>";
if (is_array($nomTech))
 {
//affichage du tableu
//table display
echo "<table class='tab_cadre2' cellpadding='5' >";
echo "<tr><th>".$lang["stats"][16]."</th><th>&nbsp;</th><th>".$lang["stats"][13]."</th><th>".$lang["stats"][14]."</th><th>".$lang["stats"][15]."</th><th>".$lang["stats"][25]."</th><th>".$lang["stats"][27]."</th><th>".$lang["stats"][30]."</th></tr>";
//Pour chacun de ces utilisateurs on affiche
//foreach these users display

foreach($nomTech as $key){
$name=getAssignName($key["assign"],$key["assign_type"]);
$val[$name]["assign"]=$key["assign"];
$val[$name]["assign_type"]=$key["assign_type"];
}
ksort($val);
  
  foreach($val as $k=>$key)
  {
	echo "<tr class='tab_bg_2'>";
	echo "<td>".getAssignName($key["assign"],$key["assign_type"],1)."</td><td><a href='graph_item.php?ID=".$key["assign"]."&assign_type=".$key["assign_type"]."&type=technicien'><img src=\"".$HTMLRel."pics/stats_item.png\" alt='' title=''></a></td>";
	//le nombre d'intervention
	//the number of intervention
		echo "<td>".getNbinter(4,'assign',unhtmlentities($key["assign"]),$_POST["date1"],$_POST["date2"])."</td>";
	//le nombre d'intervention resolues
	//the number of resolved intervention
		echo "<td>".getNbresol(4,'assign',unhtmlentities($key["assign"]),$_POST["date1"],$_POST["date2"])."</td>";
	//Le temps moyen de resolution
	//The average time to resolv
		echo "<td>".getResolAvg(4, 'assign',unhtmlentities($key["assign"]),$_POST["date1"],$_POST["date2"])."</td>";
	//Le temps moyen de l'intervention réelle
	//The average realtime to resolv
		echo "<td>".getRealAvg(4, 'assign',unhtmlentities($key["assign"]),$_POST["date1"],$_POST["date2"])."</td>";
	//Le temps total de l'intervention réelle
	//The total realtime to resolv
		echo "<td>".getRealTotal(4, 'assign',unhtmlentities($key["assign"]),$_POST["date1"],$_POST["date2"])."</td>";
	//Le temps total de l'intervention réelle
	//The total realtime to resolv
		echo "<td>".getFirstActionAvg(4, 'assign',unhtmlentities($key["assign"]),$_POST["date1"],$_POST["date2"])."</td>";

	echo "</tr>";
  }
echo "</table>";
}
else {

echo $lang["stats"][23];
}

echo "</div>";


commonFooter();
?>
