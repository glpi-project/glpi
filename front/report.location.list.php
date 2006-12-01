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
  \brief affiche le rapport r�eau par etage 

 */


include ("_relpos.php");
$NEEDED_ITEMS=array("networking");
include ($phproot . "/inc/includes.php");

checkRight("reports","r");

$query2="SELECT glpi_dropdown_locations.name as stage
FROM glpi_dropdown_locations
WHERE glpi_dropdown_locations.id=".$_POST["location"]."";
$result = $db->query($query2);
if ($db->numrows($result)==1){
	commonHeader($lang["Menu"][6],$_SERVER['PHP_SELF']);

	$ligne = $db->fetch_array($result);
	$stage=$ligne['stage'];

	// Titre
	$name=getDropdownName("glpi_dropdown_locations",$_POST["location"]);
	echo "<div align='center'><h2>".$lang["reports"][54]." $name </h2><br><br>";

	$query="SELECT glpi_dropdown_netpoint.name AS prise, c.name AS port, c.ifaddr            
		AS ip, c.ifmac AS mac,c.ID AS IDport, glpi_dropdown_locations.ID as location
		FROM glpi_dropdown_locations
		LEFT JOIN glpi_dropdown_netpoint ON glpi_dropdown_netpoint.location = 
		glpi_dropdown_locations.ID
		LEFT JOIN glpi_networking_ports c ON c.netpoint=glpi_dropdown_netpoint.id 
		WHERE ".getRealQueryForTreeItem("glpi_dropdown_locations",$_POST["location"])." AND c.device_type=".NETWORKING_TYPE.";";

	/*!
	  on envoie la requ�e de selection qui varie selon le choix fait dans la dropdown �la fonction report perso qui
	  affiche un rapport en fonction de l'�age choisi  
	 */
	report_perso("glpi_networking_lieu",$query);
	echo "</div>";
	commonFooter();

} else  glpi_header($_SERVER['HTTP_REFERER']); 

?>
