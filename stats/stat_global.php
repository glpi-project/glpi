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
 Original Author of file: Mustapha Saddallah et Bazile Lebeau
 Purpose of file:
 ----------------------------------------------------------------------
*/
 
include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
require ("functions.php");

checkAuthentication("normal");

commonHeader("Stats",$_SERVER["PHP_SELF"]);


echo "<div align ='center'><b>".$lang["stats"][12]."</b></div><hr noshade>";
//affichage du tableau
//table displaying
echo "<div align ='center'><table border='0' cellpadding='5' class='center'>";
echo "<tr><th colspan=\"1\"></th><th>".$lang["stats"][8]."</th><th>".$lang["stats"][9]."</th><th>".$lang["stats"][10]."</th></tr>";
echo "<tr class='tab_bg_1'>";
//Nombre d'interventions
//number of interventions
echo "<td>".$lang["stats"][5]."</td>";
echo "<td>".getNbInter(3,"","")."</td>";
echo "<td>".getNbInter(2,"","")."</td>";
echo "<td>".getNbInter(1,"","")."</td>";
echo "</tr>";
//Nombre d'intervention résolues
//Number of resolved/old intervention 
echo "<tr class='tab_bg_1'>";
echo "<td>".$lang["stats"][11]."</td>";
echo "<td>".getNbResol(3,"","")."</td>";
echo "<td>".getNbResol(2,"","")."</td>";
echo "<td>".getNbResol(1,"","")."</td>";
echo "</tr>";
//Temps moyen de resolution d'intervention
//Average time to resolve intervention
echo "<tr class='tab_bg_1'>";
echo "<td>".$lang["stats"][6]."</td>";
echo "<td>".getResolAvg(3,"","")."</td>";
echo "<td>".getResolAvg(2,"","")."</td>";
echo "<td>".getResolAvg(1,"","")."</td>";
echo "</tr>";
//Temps maximal de resolution d'intervention
//Max time to resolv intervention
echo "<tr class='tab_bg_1'>";
echo "<td>".$lang["stats"][7]."</td>";
echo "<td>".getResolMax(3)."</td>";
echo "<td>".getResolMax(2)."</td>";
echo "<td>".getResolMax(1)."</td>";
echo "</tr>";
echo "</table>";
echo "</div>";

commonFooter();
?>
