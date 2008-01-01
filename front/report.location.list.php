<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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


$NEEDED_ITEMS=array("networking");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("reports","r");

if (isset($_POST["location"])&&$_POST["location"]){
	commonHeader($LANG["Menu"][6],$_SERVER['PHP_SELF'],"utils","report");

	// Titre
	$name=getDropdownName("glpi_dropdown_locations",$_POST["location"]);
	echo "<div align='center'><h2>".$LANG["reports"][54]." $name </h2></div>";

	$query="SELECT glpi_dropdown_netpoint.name AS prise, glpi_networking_ports.name AS port, glpi_networking_ports.ifaddr  
		AS ip, glpi_networking_ports.ifmac AS mac,glpi_networking_ports.ID AS IDport, glpi_dropdown_locations.ID as location,glpi_dropdown_locations.completename
		FROM glpi_dropdown_locations
		LEFT JOIN glpi_dropdown_netpoint ON glpi_dropdown_netpoint.location = 
		glpi_dropdown_locations.ID
		LEFT JOIN glpi_networking_ports ON glpi_networking_ports.netpoint=glpi_dropdown_netpoint.id 
		WHERE ".getRealQueryForTreeItem("glpi_dropdown_locations",$_POST["location"])." AND glpi_networking_ports.device_type=".NETWORKING_TYPE."
		ORDER BY glpi_dropdown_locations.completename, glpi_networking_ports.name;";

	$result = $DB->query($query);
	if ($result&&$DB->numrows($result)){

		echo "<div align='center'><table class='tab_cadre_report'>";
		echo "<tr> ";
		echo "<th>".$LANG["common"][15]."</th>";
		echo "<th>".$LANG["reports"][37]."</th>";
		echo "<th>".$LANG["reports"][52]."</th>";
		echo "<th>".$LANG["reports"][38]."</th>";
		echo "<th>".$LANG["reports"][46]."</th>";
		echo "<th>".$LANG["reports"][53]."</th>";
		echo "<th>".$LANG["reports"][47]."</th>";
		echo "<th>".$LANG["reports"][38]."</th>";
		echo "<th>".$LANG["reports"][53]."</th>";
		echo "<th>".$LANG["reports"][36]."</th>";
		echo "</tr>";
		while( $ligne = $DB->fetch_array($result)){
			$lieu=$ligne["completename"];
			$prise=$ligne['prise'];
			$port=$ligne['port'];
			$nw=new NetWire();
			$end1=$nw->getOppositeContact($ligne['IDport']);
			$np=new Netport();

			$ordi="";
			$ip2="";
			$mac2="";
			$portordi="";

			if ($end1){
				$np->getFromDB($end1);
				$np->getDeviceData($np->fields["on_device"],$np->fields["device_type"]);
				$ordi=$np->device_name;
				$ip2=$np->fields['ifaddr'];
				$mac2=$np->fields['ifmac'];
				$portordi=$np->fields['name'];
			}

			$ip=$ligne['ip'];
			$mac=$ligne['mac'];

			$np=new Netport();
			$np->getFromDB($ligne['IDport']);

			$nd=new Netdevice();
			$nd->getFromDB($np->fields["on_device"]);
			$switch=$nd->fields["name"];


			//inserer ces valeures dans un tableau

			echo "<tr class='tab_bg_1'>";	
			if($lieu) echo "<td>$lieu</td>"; else echo "<td> N/A </td>";
			if($prise) echo "<td>$prise</td>"; else echo "<td> N/A </td>";
			if($switch) echo "<td>$switch</td>"; else echo "<td> N/A </td>";
			if($ip) echo "<td>$ip</td>"; else echo "<td> N/A </td>";
			if($port) echo "<td>$port</td>"; else echo "<td> N/A </td>";
			if($mac) echo "<td>$mac</td>"; else echo "<td> N/A </td>";
			if($portordi) echo "<td>$portordi</td>"; else echo "<td> N/A </td>";
			if($ip2) echo "<td>$ip2</td>"; else echo "<td> N/A </td>";
			if($mac2) echo "<td>$mac2</td>"; else echo "<td> N/A </td>";
			if($ordi) echo "<td>$ordi</td>"; else echo "<td> N/A </td>";
			echo "</tr>\n";
		}	
		echo "</table></div><br><hr><br>";

	}

	echo "</div>";
	commonFooter();

} else  glpi_header($_SERVER['HTTP_REFERER']); 

?>
