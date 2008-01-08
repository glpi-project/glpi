<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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





$NEEDED_ITEMS=array("networking");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("reports","r");

// Titre
if (isset($_POST["switch"])&&$_POST["switch"]){
	commonHeader($LANG["Menu"][6],$_SERVER['PHP_SELF'],"utils","report");

	$name=getDropdownName("glpi_networking",$_POST["switch"]);
	echo "<div align='center'><h2>".$LANG["reports"][49]." $name </h2></div><br><br>";

	$query="SELECT c.name as port,c.ifaddr as ip,c.ifmac as mac, c.ID AS IDport, glpi_networking.name as switch
		FROM glpi_networking
		LEFT JOIN glpi_networking_ports c ON c.device_type=".NETWORKING_TYPE." AND c.on_device=glpi_networking.ID
		WHERE glpi_networking.id=".$_POST["switch"]."";


	$result = $DB->query($query);
	if ($result&&$DB->numrows($result)){
		echo "<div align='center'><table class='tab_cadre_report'>";
		echo "<tr> ";
		echo "<th>&nbsp;</th>";
		echo "<th>".$LANG["reports"][46]."</th>";
		echo "<th>".$LANG["reports"][38]."</th>";
		echo "<th>".$LANG["reports"][53]."</th>";
		echo "<th>".$LANG["reports"][47]."</th>";
		echo "<th>".$LANG["reports"][38]."</th>";
		echo "<th>".$LANG["reports"][53]."</th>";
		echo "<th>".$LANG["reports"][36]."</th>";
		echo "</tr>\n";

		while( $ligne = $DB->fetch_array($result)){
			$switch = $ligne['switch'];
			//echo $ligne['location'];
			//$prise=$ligne['prise'];
			$port = $ligne['port'];
			$nw=new NetWire();
			$end1=$nw->getOppositeContact($ligne['IDport']);
			$np=new Netport();
			$ip2="";
			$mac2="";
			$portordi="";
			$ordi="";

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
			//inserer ces valeures dans un tableau
			echo "<tr class='tab_bg_1'>";	
			if($switch) echo "<td>$switch</td>"; else echo "<td> N/A </td>";
			if($port) echo "<td>$port</td>"; else echo "<td> N/A </td>";
			if($ip) echo "<td>$ip</td>"; else echo "<td> N/A </td>";
			if($mac) echo "<td>$mac</td>"; else echo "<td> N/A </td>";
			if($portordi) echo "<td>$portordi</td>"; else echo "<td> N/A </td>";
			if($ip2) echo "<td>$ip2</td>"; else echo "<td> N/A </td>";
			if($mac2) echo "<td>$mac2</td>"; else echo "<td> N/A </td>";
			if($ordi) echo "<td>$ordi</td>"; else echo "<td> N/A </td>";
			echo "</tr>\n";
		}	
		echo "</table></div><br><hr><br>";
	}

	commonFooter();

} else  {
	glpi_header($CFG_GLPI['root_doc']."/front/report.networking.php"); 
}

?>
