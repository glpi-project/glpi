<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

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
/*!
  \brief affiche le rapport r�eau par switch 

 */



include ("_relpos.php");

$NEEDED_ITEMS=array("networking");
include ($phproot . "/inc/includes.php");

checkRight("reports","r");

//$item_db_name[0] = "glpi_computers";
//$item_db_name[1] = "glpi_networking";
//$item_db_name[2] = "glpi_peripherals";
$item_db_name[0]="glpi_dropdow_location";

$query2="SELECT glpi_networking.name as switch
FROM glpi_networking
WHERE glpi_networking.id=".$_POST["switch"]."";
$result = $db->query($query2);

// Titre
if ($db->numrows($result)==1){
	commonHeader($lang["Menu"][6],$_SERVER['PHP_SELF']);

	$ligne = $db->fetch_array($result);
	$switch=$ligne['switch'];

	echo "<div align='center'><h2>".$lang["reports"][49]." $switch </h2></div><br><br>";

	$query="SELECT c.name as port,c.ifaddr as ip,c.ifmac as mac, c.ID AS IDport, glpi_networking.name as switch
		FROM glpi_networking
		LEFT JOIN glpi_networking_ports c ON c.device_type=".NETWORKING_TYPE." AND c.on_device=glpi_networking.ID
		WHERE glpi_networking.id=".$_POST["switch"]."";

	/*!
	  on envoie la requ�e de selection qui varie selon le choix fait dans la dropdown �la fonction report perso qui
	  affiche un rapport en fonction du switch choisi  
	 */

	report_perso("glpi_networking_switch",$query);
	commonFooter();

} else  glpi_header($_SERVER['HTTP_REFERER']); 

?>
