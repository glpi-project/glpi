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

if (isset($_POST["prise"])){

	$query2="SELECT a.name as office,b.name as stage,glpi_dropdown_netpoint.name as prise
		FROM glpi_dropdown_netpoint
		LEFT JOIN glpi_dropdown_locations a ON a.id=glpi_dropdown_netpoint.location
		LEFT JOIN glpi_dropdown_locations b ON b.id=a.parentid
		WHERE glpi_dropdown_netpoint.id=".$_POST["prise"]."";
	$result = $db->query($query2);
	if ($db->numrows($result)==1){
		commonHeader($lang["Menu"][6],$_SERVER['PHP_SELF']);

		$ligne = $db->fetch_array($result);
		$prise=$ligne['prise'];
		$stage=$ligne['stage'];
		$office=$ligne['office'];

		// Titre
		echo "<div align='center'><h2>".$lang["reports"][51]." $prise  ($office / $stage)</h2></div><br><br>";
		$query="SELECT a.name as bureau,a.ID as ID,glpi_dropdown_netpoint.name as prise,c.name as port,c.ifaddr as ip,c.ifmac as mac,c.ID AS IDport
			FROM glpi_dropdown_netpoint
			LEFT JOIN glpi_dropdown_locations a ON a.id=glpi_dropdown_netpoint.location
			LEFT JOIN glpi_networking_ports c ON c.netpoint=glpi_dropdown_netpoint.id 
			WHERE glpi_dropdown_netpoint.id=".$_POST["prise"]." AND c.device_type=".NETWORKING_TYPE.";";

		/*!
		  on envoie la requ�e de selection qui varie selon le choix fait dans la dropdown �la fonction report perso qui
		  affiche un rapport en fonction de la prise choisie  
		 */

		report_perso("glpi_networking_prise",$query);

		commonFooter();

	} else  glpi_header($_SERVER['HTTP_REFERER']); 



} 
?>
