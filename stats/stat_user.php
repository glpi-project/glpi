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
 Original Author of file: Mustapha Saddalah et Bazile Lebeau
 Purpose of file:
 ----------------------------------------------------------------------
*/
 
include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_tracking.php");
require ("functions.php");


checkAuthentication("normal");

commonHeader("Stats",$_SERVER["PHP_SELF"]);

echo "<div align ='center'><b>".$lang["stats"][18]."</b></div>";
if(empty($_POST["date1"])) $_POST["date1"] = "";
if(empty($_POST["date2"])) $_POST["date2"] = "";
if ($_POST["date1"]!=""&&$_POST["date2"]!=""&&strcmp($_POST["date2"],$_POST["date1"])<0){
$tmp=$_POST["date1"];
$_POST["date1"]=$_POST["date2"];
$_POST["date2"]=$tmp;
}

echo "<div align='center'><form method=\"post\" name=\"form\" action=\"stat_user.php\">";
echo "<table><tr><td align='right'>";
echo "Date de debut :</td><td><input type=\"texte\" name=\"date1\" readonly value=\"". $_POST["date1"] ."\" /></td>";
echo "<td><input name='button' type='button' class='button'  onClick=\"window.open('$HTMLRel/mycalendar.php?form=form&amp;elem=date1','Calendrier','width=200,height=220')\" value='".$lang["buttons"][15]."...'></td></tr>";
echo "<tr><td align='right'>Date de fin :</td><td><input type=\"texte\" name=\"date2\" readonly  value=\"". $_POST["date2"] ."\" /></td>";
echo "<td><input name='button' type='button' class='button'  onClick=\"window.open('$HTMLRel/mycalendar.php?form=form&amp;elem=date2','Calendrier','width=200,height=220')\" value='".$lang["buttons"][15]."...'></td></tr>";
echo "<tr><td></td><td align='center'><input type=\"submit\" class='button' name\"submit\" Value=\"". $lang["buttons"][7] ."\" /></td><td></td>";
echo "</tr></table>";
echo "</form></div>";
echo "<hr noshade>";

//On recupere les differents auteurs d'interventions
//Get the distinct intervention authors
$nomUsr = getNbIntervAuthor();

echo "<div align ='center'>";

if (is_array($nomUsr))
{
//affichage du tableau
//table display
echo "<table class='tab_cadre2' cellpadding='5' >";
echo "<tr><th>".$lang["stats"][20]."</th><th>".$lang["stats"][22]."</th><th>".$lang["stats"][14]."</th><th>".$lang["stats"][15]."</th><th>".$lang["stats"][25]."</th></tr>";
//Pour chacun de ces auteurs on affiche
//foreach these authors display
   foreach($nomUsr as $key)
   {
	echo "<tr class='tab_bg_1'>";
	echo "<td>".$key["author"]."</td>";

		echo "<td>".getNbinter(4,'author',$key["author"], $_POST["date1"], $_POST["date2"])."</td>";
		echo "<td>".getNbresol(4,'author',$key["author"], $_POST["date1"], $_POST["date2"])."</td>";
		echo "<td>".getResolAvg(4, 'author',$key["author"], $_POST["date1"], $_POST["date2"])."</td>";
		echo "<td>".getRealAvg(4, 'author',$key["author"], $_POST["date1"], $_POST["date2"])."</td>";
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
