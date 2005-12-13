<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2005 by the INDEPNET Development Team.
 
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
 ------------------------------------------------------------------------
*/

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------


// Functions Dropdown





/**
* affiche un rapport personalisé a partir d'une requete $query
* pour un type de materiel ($item_type)
* 
* Print out a report from a query ($query) for an item type ($item_type).
*
*
* @param $query query for make the report
* @param $item_type item type.
* @return nothing (print out a report).
*/
function report_perso($item_type,$query)
//affiche un rapport personalisé a partir d'une requete $query
//pour un type de materiel ($item_type) 
{

GLOBAL $cfg_layout, $cfg_features, $lang;

$db = new DB;
$result = $db->query($query);
include ("_relpos.php");


switch($item_type)
	{   
		case 'glpi_computers' :
		
		
		echo " <strong>".$lang["reports"][6]."</strong>";
		echo "<table width='100%' class='tab_cadre'>";
		echo "<tr>";
		echo "<th><div align='center'><b>".$lang["computers"][7]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["common"][3]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][10]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][41]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][42]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["financial"][6]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["financial"][7]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["search"][9]."</b></div></th>";
		echo "</tr>";
	 	while( $ligne = $db->fetch_array($result))
					{
						
						$name = $ligne['itemname'];
						$deleted = $ligne['itemdeleted'];
						$lieu = $ligne['location'];
						$achat_date = convDate($ligne['buy_date']);
						$fin_garantie = getWarrantyExpir($ligne["buy_date"],$ligne["warranty_duration"]);
						$contract_type = getDropdownName("glpi_dropdown_contract_type",$ligne["contract_type"]);
						$contract_begin = convDate($ligne['begin_date']);
						$contract_end = getWarrantyExpir($ligne["begin_date"],$ligne["duration"]);
		
						//inserer ces valeures dans un tableau

						echo "<tr>";
						if($name) echo "<td><div align='center'> $name </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($deleted) echo "<td><div align='center'> $deleted </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($lieu) echo "<td><div align='center'> ".getDropdownName("glpi_dropdown_locations",$lieu)." </div></td>"; else echo "<td><div align='center'> N/A </div></td>";	
						if($achat_date) echo "<td><div align='center'> $achat_date </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($fin_garantie) echo "<td><div align='center'> $fin_garantie </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_type) echo "<td><div align='center'> $contract_type </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_begin) echo "<td><div align='center'> $contract_begin </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_end) echo "<td><div align='center'> $contract_end </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						
						echo "</tr>";
					}
		echo "</table><br><hr><br> ";
		break;
		
		case 'glpi_printers' :
		
		echo "<b><strong>".$lang["reports"][7]."</strong></b>";
		echo "<table width='100%' class='tab_cadre'>";
		echo "<tr> ";
		echo "<th><div align='center'><b>".$lang["computers"][7]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["common"][3]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][10]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][41]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][42]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["financial"][6]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["financial"][7]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["search"][9]."</b></div></th>";
		echo "</tr>";
		
		while( $ligne = $db->fetch_array($result))
					{
						$name = $ligne['itemname'];
						$deleted = $ligne['itemdeleted'];
						$lieu = $ligne['location'];
						$achat_date = convDate($ligne['buy_date']);
						$fin_garantie = getWarrantyExpir($ligne["buy_date"],$ligne["warranty_duration"]);
						$contract_type = getDropdownName("glpi_dropdown_contract_type",$ligne["contract_type"]);
						$contract_begin = convDate($ligne['begin_date']);
						$contract_end = getWarrantyExpir($ligne["begin_date"],$ligne["duration"]);
					
					//inserer ces valeures dans un tableau
					echo "<tr>";	
						if($name) echo "<td><div align='center'> $name </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($deleted) echo "<td><div align='center'> $deleted </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($lieu) echo "<td><div align='center'> ".getDropdownName("glpi_dropdown_locations",$lieu)." </div></td>"; else echo "<td><div align='center'> N/A </div></td>";	
						if($achat_date) echo "<td><div align='center'> $achat_date </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($fin_garantie) echo "<td><div align='center'> $fin_garantie </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_type) echo "<td><div align='center'> $contract_type </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_begin) echo "<td><div align='center'> $contract_begin </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_end) echo "<td><div align='center'> $contract_end </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					echo "</tr>";
					}	
		echo "</table><br><hr><br>";
		break;
		
		case 'glpi_monitors' :
		
		echo " <b><strong>".$lang["reports"][9]."</strong></b>";
		echo "<table width='100%' class='tab_cadre'>";
		echo "<tr> ";
		echo "<th><div align='center'><b>".$lang["computers"][7]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["common"][3]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][10]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][41]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][42]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["financial"][6]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["financial"][7]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["search"][9]."</b></div></th>";
		echo "</tr>";
		
		while( $ligne = $db->fetch_array($result))
					{
						$name = $ligne['itemname'];
						$deleted = $ligne['itemdeleted'];
						$lieu = $ligne['location'];
						$achat_date = convDate($ligne['buy_date']);
						$fin_garantie = getWarrantyExpir($ligne["buy_date"],$ligne["warranty_duration"]);
						$contract_type = getDropdownName("glpi_dropdown_contract_type",$ligne["contract_type"]);
						$contract_begin = convDate($ligne['begin_date']);
						$contract_end = getWarrantyExpir($ligne["begin_date"],$ligne["duration"]);
					//inserer ces valeures dans un tableau
					
					echo "<tr>";	
						if($name) echo "<td><div align='center'> $name </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($deleted) echo "<td><div align='center'> $deleted </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($lieu) echo "<td><div align='center'> ".getDropdownName("glpi_dropdown_locations",$lieu)." </div></td>"; else echo "<td><div align='center'> N/A </div></td>";	
						if($achat_date) echo "<td><div align='center'> $achat_date </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($fin_garantie) echo "<td><div align='center'> $fin_garantie </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_type) echo "<td><div align='center'> $contract_type </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_begin) echo "<td><div align='center'> $contract_begin </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_end) echo "<td><div align='center'> $contract_end </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					echo "</tr>";
					}	
		echo "</table><br><hr><br>";
		break;
		
		case 'glpi_networking' :
		
		echo " <b><strong>".$lang["reports"][8]."</strong></b>";
		echo "<table width='100%' class='tab_cadre'>";
		echo "<tr> ";
		echo "<th><div align='center'><b>".$lang["computers"][7]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["common"][3]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][10]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][41]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][42]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["financial"][6]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["financial"][7]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["search"][9]."</b></div></th>";
		echo "</tr>";
		
		while( $ligne = $db->fetch_array($result))
					{
						$name = $ligne['itemname'];
						$deleted = $ligne['itemdeleted'];
						$lieu = $ligne['location'];
						$achat_date = convDate($ligne['buy_date']);
						$fin_garantie = getWarrantyExpir($ligne["buy_date"],$ligne["warranty_duration"]);
						$contract_type = getDropdownName("glpi_dropdown_contract_type",$ligne["contract_type"]);
						$contract_begin = convDate($ligne['begin_date']);
						$contract_end = getWarrantyExpir($ligne["begin_date"],$ligne["duration"]);
					//inserer ces valeures dans un tableau
				
					echo "<tr> ";	
						if($name) echo "<td><div align='center'> $name </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($deleted) echo "<td><div align='center'> $deleted </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($lieu) echo "<td><div align='center'> ".getDropdownName("glpi_dropdown_locations",$lieu)." </div></td>"; else echo "<td><div align='center'> N/A </div></td>";	
						if($achat_date) echo "<td><div align='center'> $achat_date </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($fin_garantie) echo "<td><div align='center'> $fin_garantie </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_type) echo "<td><div align='center'> $contract_type </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_begin) echo "<td><div align='center'> $contract_begin </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_end) echo "<td><div align='center'> $contract_end </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					echo "</tr>";
					}	
		echo "</table><br><hr><br>";
		break;
		case 'glpi_peripherals' :
		
		echo " <b><strong>".$lang["reports"][29]."</strong></b>";
		echo "<table width='100%' class='tab_cadre'>";
		echo "<tr> ";
		echo "<th><div align='center'><b>".$lang["computers"][7]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["common"][3]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][10]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][41]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][42]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["financial"][6]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["financial"][7]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["search"][9]."</b></div></th>";
		echo "</tr>";
		
		while( $ligne = $db->fetch_array($result))
					{
						$name = $ligne['itemname'];
						$deleted = $ligne['itemdeleted'];
						$lieu = $ligne['location'];
						$achat_date = convDate($ligne['buy_date']);
						$fin_garantie = getWarrantyExpir($ligne["buy_date"],$ligne["warranty_duration"]);
						$contract_type = getDropdownName("glpi_dropdown_contract_type",$ligne["contract_type"]);
						$contract_begin = convDate($ligne['begin_date']);
						$contract_end = getWarrantyExpir($ligne["begin_date"],$ligne["duration"]);
					//inserer ces valeures dans un tableau
					
					echo "<tr>";	
						if($name) echo "<td><div align='center'> $name </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($deleted) echo "<td><div align='center'> $deleted </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($lieu) echo "<td><div align='center'> ".getDropdownName("glpi_dropdown_locations",$lieu)." </div></td>"; else echo "<td><div align='center'> N/A </div></td>";	
						if($achat_date) echo "<td><div align='center'> $achat_date </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($fin_garantie) echo "<td><div align='center'> $fin_garantie </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_type) echo "<td><div align='center'> $contract_type </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_begin) echo "<td><div align='center'> $contract_begin </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_end) echo "<td><div align='center'> $contract_end </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					echo "</tr>";
					}	
		echo "</table><br><hr><br>";
		break;
		case 'glpi_software' :
		
		echo " <b><strong>".$lang["reports"][55]."</strong></b>";
		echo "<table width='100%' class='tab_cadre'>";
		echo "<tr> ";
		echo "<th><div align='center'><b>".$lang["computers"][7]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["common"][3]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][10]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][41]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][42]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["financial"][6]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["financial"][7]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["search"][9]."</b></div></th>";
		echo "</tr>";
		
		while( $ligne = $db->fetch_array($result))
					{
						$name = $ligne['itemname'];
						$deleted = $ligne['itemdeleted'];
						$lieu = $ligne['location'];
						$achat_date = convDate($ligne['buy_date']);
						$fin_garantie = getWarrantyExpir($ligne["buy_date"],$ligne["warranty_duration"]);
						$contract_type = getDropdownName("glpi_dropdown_contract_type",$ligne["contract_type"]);
						$contract_begin = convDate($ligne['begin_date']);
						$contract_end = getWarrantyExpir($ligne["begin_date"],$ligne["duration"]);
					//inserer ces valeures dans un tableau
					
					echo "<tr>";	
						if($name) echo "<td><div align='center'> $name </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($deleted) echo "<td><div align='center'> $deleted </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($lieu) echo "<td><div align='center'> ".getDropdownName("glpi_dropdown_locations",$lieu)." </div></td>"; else echo "<td><div align='center'> N/A </div></td>";	
						if($achat_date) echo "<td><div align='center'> $achat_date </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($fin_garantie) echo "<td><div align='center'> $fin_garantie </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_type) echo "<td><div align='center'> $contract_type </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_begin) echo "<td><div align='center'> $contract_begin </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_end) echo "<td><div align='center'> $contract_end </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					echo "</tr>";
					}	
		echo "</table><br><hr><br>";
		break;
		// Rapport réseau par lieu
		case 'glpi_networking_lieu' :
		echo "<table width='100%' class='tab_cadre'>";
		echo "<tr> ";
		echo "<th><div align='center'><b>".$lang["reports"][20]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][37]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][52]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][38]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][46]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][53]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][47]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][38]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][53]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][36]."</b></div></th>";
		echo "</tr>";
		
		while( $ligne = $db->fetch_array($result))
					{
					$lieu=getTreeValueCompleteName("glpi_dropdown_locations",$ligne["location"]);
					//echo $ligne['location'];
					//print_r($ligne);
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
					
					echo "<tr>";	
					if($lieu) echo "<td><div align='center'>$lieu</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($prise) echo "<td><div align='center'>$prise</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($switch) echo "<td><div align='center'>$switch</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($ip) echo "<td><div align='center'>$ip</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($port) echo "<td><div align='center'>$port</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($mac) echo "<td><div align='center'>$mac</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($portordi) echo "<td><div align='center'>$portordi</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($ip2) echo "<td><div align='center'>$ip2</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($mac2) echo "<td><div align='center'>$mac2</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($ordi) echo "<td><div align='center'>$ordi</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					}	
		echo "</table><br><hr><br>";
		break;
	//rapport reseau par switch	
	case 'glpi_networking_switch' :
		echo "<table width='100%' class='tab_cadre'>";
		echo "<tr> ";
		echo "<th><div align='center'>&nbsp;</div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][46]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][38]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][53]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][47]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][38]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][53]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][36]."</b></div></th>";
		echo "</tr>";
		
		while( $ligne = $db->fetch_array($result))
					{
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
					
					echo "<tr>";	
					if($switch) echo "<td><div align='center'>$switch</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($port) echo "<td><div align='center'>$port</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($ip) echo "<td><div align='center'>$ip</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($mac) echo "<td><div align='center'>$mac</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($portordi) echo "<td><div align='center'>$portordi</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($ip2) echo "<td><div align='center'>$ip2</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($mac2) echo "<td><div align='center'>$mac2</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($ordi) echo "<td><div align='center'>$ordi</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					}	
		echo "</table><br><hr><br>";
		break;
		
		//rapport reseau par prise
		case 'glpi_networking_prise' :
		echo "<table width='100%' class='tab_cadre'>";
		echo "<tr> ";
		echo "<th><div align='center'><b>".$lang["reports"][20]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][52]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][38]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][46]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][53]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][47]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][38]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][53]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][36]."</b></div></th>";
		echo "</tr>";
		
		while( $ligne = $db->fetch_array($result))
					{
					$prise=$ligne['prise'];
					$ID=$ligne['ID'];
					$lieu=getDropdownName("glpi_dropdown_locations",$ID);
					//$etage=$ligne['etage'];
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
					$port=$ligne['port'];
					$np=new Netport();
					$np->getFromDB($ligne['IDport']);

					$nd=new Netdevice();
					$nd->getFromDB($np->fields["on_device"]);
					$switch=$nd->fields["name"];
					
					
					//inserer ces valeures dans un tableau
					
					echo "<tr>";
					if($lieu) echo "<td><div align='center'>$lieu</div></td>"; else echo "<td><div align='center'> N/A </div></td>";	
					if($switch) echo "<td><div align='center'>$switch</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($ip) echo "<td><div align='center'>$ip</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($port) echo "<td><div align='center'>$port</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($mac) echo "<td><div align='center'>$mac</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($portordi) echo "<td><div align='center'>$portordi</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($ip2) echo "<td><div align='center'>$ip2</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($mac2) echo "<td><div align='center'>$mac2</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($ordi) echo "<td><div align='center'>$ordi</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					}	
		echo "</table><br><hr><br>";
		break;	
		
	}	
}



?>