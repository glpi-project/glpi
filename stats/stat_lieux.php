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

echo "<div align='center'><p><b>".$lang["stats"][19]."</p></b>";

if(empty($_POST["date1"])) $_POST["date1"] = "";
if(empty($_POST["date2"])) $_POST["date2"] = "";
if ($_POST["date1"]!=""&&$_POST["date2"]!=""&&strcmp($_POST["date2"],$_POST["date1"])<0){
$tmp=$_POST["date1"];
$_POST["date1"]=$_POST["date2"];
$_POST["date2"]=$tmp;
}

if(empty($_POST["dropdown"])) $_POST["dropdown"] = "glpi_type_computers";

echo "<form method=\"post\" name=\"form\" action=\"stat_lieux.php\">";

echo "<table class='tab_cadre'><tr class='tab_bg_2'><td rowspan='2'>";
echo "<select name=\"dropdown\">";
echo "<option value=\"glpi_type_computers\" ".($_POST["dropdown"]=="glpi_type_computers"?"selected":"").">".$lang["computers"][8]."</option>";
echo "<option value=\"glpi_dropdown_os\" ".($_POST["dropdown"]=="glpi_dropdown_os"?"selected":"").">".$lang["computers"][9]."</option>";
echo "<option value=\"glpi_dropdown_moboard\" ".($_POST["dropdown"]=="glpi_dropdown_moboard"?"selected":"").">".$lang["computers"][35]."</option>";
echo "<option value=\"glpi_dropdown_processor\" ".($_POST["dropdown"]=="glpi_dropdown_processor"?"selected":"").">".$lang["setup"][7]."</option>";
echo "<option value=\"glpi_dropdown_locations\" ".($_POST["dropdown"]=="glpi_dropdown_locations"?"selected":"").">".$lang["stats"][21]."</option>";
echo "<option value=\"glpi_dropdown_gfxcard\" ".($_POST["dropdown"]=="glpi_dropdown_gfxcard"?"selected":"").">".$lang["computers"][34]."</option>";
echo "<option value=\"glpi_dropdown_hdtype\" ".($_POST["dropdown"]=="glpi_dropdown_hdtype"?"selected":"").">".$lang["computers"][36]."</option>";
echo "</select></td>";

echo "<td align='right'>";
echo "Date de debut :</td><td> <input type=\"texte\" readonly name=\"date1\"  size ='10' value=\"". $_POST["date1"] ."\" /></td>";
echo "<td><input name='button' type='button' class='button'  onClick=\"window.open('$HTMLRel/mycalendar.php?form=form&amp;elem=date1&amp;value=".$_POST["date1"]."','".$lang["buttons"][15]."','width=200,height=220')\" value='".$lang["buttons"][15]."'>";
echo "</td><td><input name='button_reset' type='button' class='button' onClick=\"document.forms['form'].date1.value=''\" value='".$lang["buttons"][16]."'>";
echo "</td><td rowspan='2' align='center'><input type=\"submit\" class='button' name\"submit\" Value=\"". $lang["buttons"][7] ."\" /></td></tr>";
echo "<tr class='tab_bg_2'><td align='right'>Date de fin :</td><td><input type=\"texte\" readonly name=\"date2\"  size ='10' value=\"". $_POST["date2"] ."\" /></td>";
echo "<td><input name='button' type='button' class='button'  onClick=\"window.open('$HTMLRel/mycalendar.php?form=form&amp;elem=date2&amp;value=".$_POST["date2"]."','".$lang["buttons"][15]."','width=200,height=220')\" value='".$lang["buttons"][15]."'>";
echo "</td><td><input name='button_reset' type='button' class='button' onClick=\"document.forms['form'].date2.value=''\" value='".$lang["buttons"][16]."'>";
echo "</td></tr>";
echo "</table></form></div>";

//recuperation des differents lieux d'interventions
//Get the distincts intervention location
$type = getNbIntervDropdown($_POST["dropdown"]);

echo "<div align ='center'>";

if (is_array($type))
   {
 //affichage du tableau
 echo "<table class='tab_cadre2' cellpadding='5' >";
 $champ=str_replace("locations","location",str_replace("glpi_","",str_replace("dropdown_","",str_replace("_computers","",$_POST["dropdown"]))));
 echo "<tr><th>&nbsp;</th><th>".$lang["stats"][22]."</th><th>".$lang["stats"][14]."</th><th>".$lang["stats"][15]."</th><th>".$lang["stats"][25]."</th><th>".$lang["stats"][27]."</th><th>".$lang["stats"][30]."</th></tr>";

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
	echo "<td>".getDropdownName($_POST["dropdown"],$key["ID"]) ."&nbsp;($count)</td>";
	//le nombre d'intervention
	//the number of intervention
		echo "<td>".getNbinter(4,"glpi_computers.".getDropdownNameFromTable($_POST["dropdown"]),$key["ID"],$_POST["date1"],$_POST["date2"] )."</td>";
	//le nombre d'intervention resolues
	//the number of resolved intervention
		echo "<td>".getNbresol(4,"glpi_computers.".getDropdownNameFromTable($_POST["dropdown"]),$key["ID"],$_POST["date1"],$_POST["date2"])."</td>";
	//Le temps moyen de resolution
	//The average time to resolv
		echo "<td>".getResolAvg(4,"glpi_computers.".getDropdownNameFromTable($_POST["dropdown"]),$key["ID"],$_POST["date1"],$_POST["date2"])."</td>";
	//Le temps moyen de l'intervention réelle
	//The average realtime to resolv
		echo "<td>".getRealAvg(1,"glpi_computers.".getDropdownNameFromTable($_POST["dropdown"]),$key["ID"])."</td>";
	//Le temps total de l'intervention réelle
	//The total realtime to resolv
		echo "<td>".getRealTotal(1,"glpi_computers.".getDropdownNameFromTable($_POST["dropdown"]),$key["ID"])."</td>";
	//Le temps total de l'intervention réelle
	//The total realtime to resolv
		echo "<td>".getFirstActionAvg(1,"glpi_computers.".getDropdownNameFromTable($_POST["dropdown"]),$key["ID"])."</td>";

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
