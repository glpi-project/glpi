<?php
/*
 
 ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
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
require ("functions.php");


checkAuthentication("normal");

commonHeader("Stats",$_SERVER["PHP_SELF"]);

echo "<center><b>".$lang["stats"][18]."</b></center><hr noshade>";
//affichage du tableau
//table display
echo "<center><table border='0' cellpadding=5>";
echo "<tr><th>".$lang["stats"][20]."</th><th>".$lang["stats"][22]."</th><th>".$lang["stats"][14]."</th><th>".$lang["stats"][15]."</th></tr>";

//On recupere les differents auteurs d'interventions
//Get the distinct intervention authors
$nomUsr = getNbIntervAuthor();

//Pour chacun de ces auteurs on affiche
//foreach these authors display
foreach($nomUsr as $key)
{
	echo "<tr class='tab_bg_1'>";
	echo "<td>".$key["author"]."</td>";
	//le nombre d'intervention
	//the number of intervention
	echo "<td>".getNbinter(1,'author',$key["author"])."</td>";
	//le nombre d'intervention resolues
	//the number of resolved intervention
	echo "<td>".getNbresol(1,'author',$key["author"])."</td>";
	//Le temps moyen de resolution
	//The average time to resolv
	echo "<td>".getResolAvg(1, 'author',$key["author"])."</td>";
	
	echo "</tr>";
}
echo "</table>";
commonFooter();
?>
