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



$db = new DB;
$query2="SELECT glpi_dropdown_locations.name as stage
FROM glpi_dropdown_locations
WHERE glpi_dropdown_locations.id=".$_POST["location"]."";
$result = $db->query($query2);
if ($db->numrows($result)==1){
	commonHeader("Reports",$_SERVER["PHP_SELF"]);
	
	$ligne = $db->fetch_array($result);
	$stage=$ligne['stage'];

	// Titre
        $name=getDropdownName("glpi_dropdown_locations",$_POST["location"]);
	echo "<div align='center'><h2>".$lang["reports"][54]." $name </h2></div><br><br>";
        	
        $query="SELECT glpi_dropdown_netpoint.name AS prise, a.name AS port, a.ifaddr            
AS ip, a.ifmac AS mac, glpi_networking.name AS switch, b.name AS portordi, 
b.ifaddr AS ip2, b.ifmac AS mac2, glpi_computers.name AS ordi
 FROM glpi_dropdown_locations
 LEFT JOIN glpi_dropdown_netpoint ON glpi_dropdown_netpoint.location = 
glpi_dropdown_locations.ID
 LEFT JOIN glpi_networking_ports a ON a.netpoint = glpi_dropdown_netpoint.ID
 AND a.device_type =2
 LEFT JOIN glpi_networking_wire ON glpi_networking_wire.END2 = a.ID
 LEFT JOIN glpi_networking ON glpi_networking.ID = a.on_device
 LEFT JOIN glpi_networking_ports b ON b.ID = glpi_networking_wire.END1
 AND b.device_type =1
 LEFT JOIN glpi_computers ON glpi_computers.ID = b.on_device
 WHERE glpi_dropdown_locations.ID =".$_POST["location"]."";
        	
	/*!
 	on envoie la requête de selection qui varie selon le choix fait dans la dropdown à la fonction report perso qui
 	affiche un rapport en fonction de l'étage choisi  
	*/
	report_perso("glpi_networking_lieu",$query);
	commonFooter();
	
} else  header("Location: ".$_SERVER['HTTP_REFERER']); 
	
?>
