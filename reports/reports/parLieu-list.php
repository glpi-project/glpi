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
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
/*!
    \brief affiche le rapport réseau par etage 

*/
 

include ("_relpos.php");
include($phproot . "/glpi/networking/_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_networking.php");
checkAuthentication("normal");
commonHeader("Reports",$_SERVER["PHP_SELF"]);


$db = new DB;
$query2="SELECT glpi_dropdown_locations.name as stage
FROM glpi_dropdown_locations
WHERE glpi_dropdown_locations.id=".$_POST["location"]."";
$result = $db->query($query2);

if ($db->numrows($result)==1){

	$ligne = $db->fetch_array($result);
	$stage=$ligne['stage'];

	// Titre

	echo "<div align='center'><h2>".$lang["reports"][34].": $stage </h2></div><br><br>";

	$query="SELECT a.name as bureau,b.name as etage,glpi_dropdown_netpoint.name as prise,c.name as port,glpi_networking_wire.id as lien,glpi_networking.name as switch,d.name as portordi, glpi_computers.name as ordi 
	FROM glpi_dropdown_netpoint
	LEFT JOIN glpi_dropdown_locations a ON a.id=glpi_dropdown_netpoint.location
	LEFT JOIN glpi_dropdown_locations b ON b.id=a.parentid
	LEFT JOIN glpi_networking_ports c ON c.netpoint=glpi_dropdown_netpoint.id AND c.device_type=2
	LEFT JOIN glpi_networking_wire ON glpi_networking_wire.end2=c.id
	LEFT JOIN glpi_networking ON glpi_networking.id=c.on_device
	LEFT JOIN glpi_networking_ports d ON d.id=glpi_networking_wire.end1 AND d.device_type=1
	LEFT JOIN glpi_computers ON glpi_computers.id=d.on_device
	WHERE a.parentid=".$_POST["location"]."
	ORDER BY glpi_dropdown_netpoint.name";


	echo "A REVOIR POUR OBTENIR quelquechose de compatible arborescence de lieu quitte a ne pas utiliser la fonction report_perso";
	/*!
 	on envoie la requête de selection qui varie selon le choix fait dans la dropdown à la fonction report perso qui
 	affiche un rapport en fonction de l'étage choisi  
	*/
	report_perso("glpi_networking_lieu",$query);
} else echo $lang["reports"][48];
	commonFooter();
?>
