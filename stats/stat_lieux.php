<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 Bazile Lebeau, baaz@indepnet.net - Jean-Mathieu Doléans, jmd@indepnet.net
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer, turin@incubus.de 

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
 Original Author of file: Mustapha Saddalah et Bazile Lebea
 Purpose of file:
 ----------------------------------------------------------------------
*/
 
include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_tracking.php");
include ($phproot . "/glpi/includes_setup.php");
require ("functions.php");


checkAuthentication("normal");

commonHeader("Stats",$_SERVER["PHP_SELF"]);

echo "<div align='center'><b>".$lang["stats"][19]."</b>";
if(empty($_POST["date1"])) $_POST["date1"] = "";
if(empty($_POST["date2"])) $_POST["date2"] = "";
if(empty($_POST["dropdown"])) $_POST["dropdown"] = "glpi_type_computers";
echo "<form method=\"post\" name=\"form\" action=\"stat_lieux.php\">";
echo "<select name=\"dropdown\">";
echo "<option value=\"glpi_type_computers\" ".($_POST["dropdown"]=="glpi_type_computers"?"selected":"").">".$lang["computers"][8]."</option>";
echo "<option value=\"glpi_dropdown_os\" ".($_POST["dropdown"]=="glpi_dropdown_os"?"selected":"").">".$lang["computers"][9]."</option>";
echo "<option value=\"glpi_dropdown_moboard\" ".($_POST["dropdown"]=="glpi_dropdown_moboard"?"selected":"").">".$lang["computers"][35]."</option>";
echo "<option value=\"glpi_dropdown_processor\" ".($_POST["dropdown"]=="glpi_dropdown_processor"?"selected":"").">".$lang["setup"][7]."</option>";
echo "<option value=\"glpi_dropdown_locations\" ".($_POST["dropdown"]=="glpi_dropdown_locations"?"selected":"").">".$lang["stats"][21]."</option>";
echo "<option value=\"glpi_dropdown_gfxcard\" ".($_POST["dropdown"]=="glpi_dropdown_gfxcard"?"selected":"").">".$lang["computers"][34]."</option>";
echo "<option value=\"glpi_dropdown_hdtype\" ".($_POST["dropdown"]=="glpi_dropdown_hdtype"?"selected":"").">".$lang["computers"][36]."</option>";
echo "</select>";
echo "<table><tr><td align='right'>";
echo $lang["search"][8]." :</td><td><input type=\"texte\" readonly name=\"date1\" value=\"". $_POST["date1"] ."\" /></td>";
echo "<td><input name='button' type='button' class='button'  onClick=\"window.open('mycalendar.php?form=form&amp;elem=date1','Calendrier','width=200,height=220')\" value='".$lang["buttons"][15]."...'></td></tr>";
echo "<tr><td align='right'>".  $lang["search"][9] ." :</td><td><input type=\"texte\" readonly name=\"date2\" value=\"". $_POST["date2"] ."\" /></td>";
echo "<td><input name='button' type='button' class='button'  onClick=\"window.open('mycalendar.php?form=form&amp;elem=date2','Calendrier','width=200,height=220')\" value='".$lang["buttons"][15]."...'></td></tr>";
echo "<tr><td></td><td align='center'><input type=\"submit\" class='button' name\"submit\" Value=\"". $lang["buttons"][7] ."\" /></td><td></td>";
echo "</tr></table>";
echo "</form></div>";
echo "<hr noshade>";

//recuperation des differents lieux d'interventions
//Get the distincts intervention location
$type = getNbIntervDropdown($_POST["dropdown"]);

echo "<div align ='center'>";

if (is_array($type))
   {
 //affichage du tableau
 echo "<table class='tab_cadre2' cellpadding='5' >";
 $champ=str_replace("locations","location",str_replace("glpi_","",str_replace("dropdown_","",str_replace("_computers","",$_POST["dropdown"]))));
 echo "<tr><th>&nbsp;</th><th>".$lang["stats"][22]."</th><th>".$lang["stats"][14]."</th><th>".$lang["stats"][15]."</th><th>".$lang["stats"][25]."</th></tr>";

 //Pour chaque lieu on affiche
 //for each location displays
      foreach($type as $key)
      {
      	$query="SELECT count(*) FROM glpi_computers WHERE $champ='".$key["ID"]."'";
      	$db=new DB;
      	if ($result=$db->query($query))
      	 	$count=$db->result($result,0,0);
      	 else $count=0; 
	echo "<tr class='tab_bg_1'>";
	echo "<td>".getDropdownName($_POST["dropdown"],$key["ID"]) ."($count)</td>";
	//le nombre d'intervention
	//the number of intervention
	if(!empty($_POST["date1"]) && !empty($_POST["date2"])) {
	
		echo "<td>".getNbinter(4,"glpi_computers.".getDropdownNameFromTable($_POST["dropdown"]),$key["ID"],$_POST["date1"],$_POST["date2"] )."</td>";
	}
	else {
		echo "<td>".getNbinter(1,"glpi_computers.".getDropdownNameFromTable($_POST["dropdown"]),$key["ID"])."</td>";
	}
	//le nombre d'intervention resolues
	//the number of resolved intervention
	if(!empty($_POST["date1"]) && !empty($_POST["date2"])) {
		echo "<td>".getNbresol(4,"glpi_computers.".getDropdownNameFromTable($_POST["dropdown"]),$key["ID"],$_POST["date1"],$_POST["date2"])."</td>";
	}
	else {
		echo "<td>".getNbresol(1,"glpi_computers.".getDropdownNameFromTable($_POST["dropdown"]),$key["ID"])."</td>";
	}
	//Le temps moyen de resolution
	//The average time to resolv
	if(!empty($_POST["date1"]) && !empty($_POST["date2"])) {
		echo "<td>".getResolAvg(4,"glpi_computers.".getDropdownNameFromTable($_POST["dropdown"]),$key["ID"],$_POST["date1"],$_POST["date2"])."</td>";
	}
	else {
		echo "<td>".getResolAvg(1,"glpi_computers.".getDropdownNameFromTable($_POST["dropdown"]),$key["ID"])."</td>";
	}
	//Le temps moyen de l'intervention réelle
	//The average realtime to resolv
	if(!empty($_POST["date1"]) && !empty($_POST["date2"])) {
		echo "<td>".getRealAvg(4,"glpi_computers.".getDropdownNameFromTable($_POST["dropdown"]),$key["ID"],$_POST["date1"],$_POST["date2"])."</td>";
	}
	else {
		echo "<td>".getRealAvg(1,"glpi_computers.".getDropdownNameFromTable($_POST["dropdown"]),$key["ID"])."</td>";
	}

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
