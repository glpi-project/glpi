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
    \brief affiche le rapport réseau par switch 

*/

 

include ("_relpos.php");
include($phproot . "/glpi/networking/_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_networking.php");
checkAuthentication("normal");


//$item_db_name[0] = "glpi_computers";
//$item_db_name[1] = "glpi_networking";
//$item_db_name[2] = "glpi_peripherals";
$item_db_name[0]="glpi_dropdow_location";


$db = new DB;
$query2="SELECT glpi_networking.name as switch
FROM glpi_networking
WHERE glpi_networking.id=".$_POST["switch"]."";
$result = $db->query($query2);

// Titre
if ($db->numrows($result)==1){
	commonHeader("Reports",$_SERVER["PHP_SELF"]);

	$ligne = $db->fetch_array($result);
	$switch=$ligne['switch'];

	echo "<div align='center'><h2>".$lang["reports"][49]." $switch </h2></div><br><br>";
	
	$query="SELECT c.name as port,c.ifaddr as ip,c.ifmac as mac,glpi_networking_wire.id as lien,glpi_networking.name as switch,d.name as portordi,d.ifaddr as ip2,d.ifmac as mac2,glpi_computers.name as ordi,e.ID as ID
        FROM glpi_networking
        LEFT JOIN glpi_dropdown_locations e ON e.ID=glpi_networking.location
        LEFT JOIN glpi_networking_ports c ON c.device_type=2 AND c.on_device=glpi_networking.ID
	LEFT JOIN glpi_networking_wire ON glpi_networking_wire.end2=c.ID
	LEFT JOIN glpi_networking_ports d ON d.ID=glpi_networking_wire.end1 AND d.device_type=1
	LEFT JOIN glpi_computers ON glpi_computers.ID= d.on_device
	WHERE glpi_networking.id=".$_POST["switch"]."";
	/*!
 	on envoie la requête de selection qui varie selon le choix fait dans la dropdown à la fonction report perso qui
 	affiche un rapport en fonction du switch choisi  
	*/

	report_perso("glpi_networking_switch",$query);
	commonFooter();

} else  header("Location: ".$_SERVER['HTTP_REFERER']); 
       	
?>
