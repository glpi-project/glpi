<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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


	if (isset($_POST["prise"])&&$_POST["prise"]){
		commonHeader($LANG['Menu'][6],$_SERVER['PHP_SELF'],"utils","report");

		$name=getDropdownName("glpi_netpoints",$_POST["prise"]);

		// Titre
		echo "<div align='center'><h2>".$LANG['reports'][51]." $name</h2></div><br><br>";
		$query="SELECT a.name as bureau, a.id, glpi_netpoints.name as prise, c.name as port,
				c.ip,c.mac,c.id AS IDport
			FROM glpi_netpoints
			LEFT JOIN glpi_locations a ON a.id=glpi_netpoints.locations_id
			LEFT JOIN glpi_networkports c ON c.netpoints_id=glpi_netpoints.id
			WHERE glpi_netpoints.id='".$_POST["prise"]."' AND c.itemtype=".NETWORKING_TYPE.";";

		/*!
		  on envoie la requ�e de selection qui varie selon le choix fait dans la dropdown �la fonction report perso qui
		  affiche un rapport en fonction de la prise choisie  
		 */

		$result = $DB->query($query);
		if ($result&&$DB->numrows($result)){

			echo "<div align='center'><table class='tab_cadre_report'>";
			echo "<tr> ";
			echo "<th>".$LANG['common'][15]."</th>";
			echo "<th>".$LANG['reports'][52]."</th>";
			echo "<th>".$LANG['reports'][38]."</th>";
			echo "<th>".$LANG['reports'][46]."</th>";
			echo "<th>".$LANG['device_iface'][2]."</th>";
			echo "<th>".$LANG['reports'][47]."</th>";
			echo "<th>".$LANG['reports'][38]."</th>";
			echo "<th>".$LANG['device_iface'][2]."</th>";
			echo "<th>".$LANG['reports'][36]."</th>";
			echo "</tr>";

			while( $ligne = $DB->fetch_array($result))
			{
				$prise=$ligne['prise'];
				$ID=$ligne['id'];
				$lieu=getDropdownName("glpi_locations",$ID);
				//$etage=$ligne['etage'];
				$nw=new NetworkPort_NetworkPort();
				$networkports_id_1=$nw->getOppositeContact($ligne['IDport']);
				$np=new NetworkPort();

				$ordi="";
				$ip2="";
				$mac2="";
				$portordi="";

				if ($networkports_id_1){
					$np->getFromDB($networkports_id_1);
					$np->getDeviceData($np->fields["items_id"],$np->fields["itemtype"]);
					$ordi=$np->device_name;
					$ip2=$np->fields['ip'];
					$mac2=$np->fields['mac'];
					$portordi=$np->fields['name'];
				}

				$ip=$ligne['ip'];
				$mac=$ligne['mac'];
				$port=$ligne['port'];
				$np=new NetworkPort();
				$np->getFromDB($ligne['IDport']);

				$nd=new NetworkEquipment();
				$nd->getFromDB($np->fields["items_id"]);
				$switch=$nd->fields["name"];


				//inserer ces valeures dans un tableau

				echo "<tr class='tab_bg_1'>";
				if($lieu) echo "<td>$lieu</td>"; else echo "<td> N/A </td>";	
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

		commonFooter();

	} else  {
		glpi_header($CFG_GLPI['root_doc']."/front/report.networking.php"); 
	}
?>
