<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2004 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
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

function getDeviceTable($dev_type){
		switch ($dev_type){
			case MOBOARD_DEVICE :
				return "glpi_device_moboard";
				break;
			case PROCESSOR_DEVICE :
				return "glpi_device_processor";
				break;
			case RAM_DEVICE :
				return "glpi_device_ram";
				break;
			case HDD_DEVICE :
				return "glpi_device_hdd";
				break;
			case NETWORK_DEVICE :
				return "glpi_device_iface";
				break;
			case DRIVE_DEVICE :
				return "glpi_device_drive";
				break;
			case CONTROL_DEVICE :
				return "glpi_device_control";
				break;
			case GFX_DEVICE :
				return "glpi_device_gfxcard";
				break;
			case SND_DEVICE :
				return "glpi_device_sndcard";
				break;
			case PCI_DEVICE :
				return "glpi_device_pci";
				break;
			case CASE_DEVICE :
				return "glpi_device_case";
				break;
			case POWER_DEVICE :
				return "glpi_device_power";
				break;
				
		}
	
}


//print form/tab for a device linked to a computer
function printDeviceComputer($device,$specif,$compID,$compDevID) {
	global $lang;
	
	//print the good form switch the wanted device type.
	switch($device->type) {
		case HDD_DEVICE :
			
			echo "<tr><th width='300px' >".$lang["devices"][1]."</th>";
			
			echo "<th >".$lang["devices"][8]." : ".$device->fields["designation"]."</th>";
			echo "</tr>";
			
			echo "<tr class='tab_bg_2'><td>".$lang["device_hdd"][0]." : ".$device->fields["rpm"]."</td>";
			//echo "<td></td>";
			//echo "<td>&nbsp;</td>";
			
			echo "<td>".$lang["device_hdd"][2]." : ".$device->fields["interface"]."</td>";
			//echo "<td></td>";
			//echo "<td>&nbsp;</td>";
			echo "</tr><tr class='tab_bg_2'>";
			
			echo "<td>".$lang["device_hdd"][1]." : ".$device->fields["cache"]."</td>";
			//echo "<td></td>";
			echo "<td>&nbsp;</td>";
			echo "</tr>";
			$specificity_label = $lang["device_hdd"][4];
		
		break;
		case GFX_DEVICE :
			
			echo "<tr><th width='300px' >".$lang["devices"][2]."</th>";
			
			echo "<th >".$lang["devices"][8]." : ".$device->fields["designation"]."</th>";
			echo "</tr>";
			
			echo "<tr class='tab_bg_2'><td>".$lang["device_gfxcard"][0]." : ".$device->fields["ram"]."</td>";
			
			
			echo "<td>".$lang["device_gfxcard"][2]." : ".$device->fields["interface"]."</td>";
			
			echo "</tr>";
			$specificity_label = "";
		break;
		case NETWORK_DEVICE :
			
			echo "<tr><th width='300px' >".$lang["devices"][3]."</th>";
			
			echo "<th >".$lang["devices"][8]." : ".$device->fields["designation"]."</th>";
			echo "</tr>";
			
			echo "<tr class='tab_bg_2'><td>".$lang["device_iface"][0]." : ".$device->fields["bandwidth"]."</td>";
			
			echo "<td>&nbsp;</td>";
			echo "</tr>";
			$specificity_label = $lang["device_iface"][2];
		break;
		case MOBOARD_DEVICE :
			
			echo "<tr><th width='300px' >".$lang["devices"][5]."</th>";
			
			echo "<th >".$lang["devices"][8]." : ".$device->fields["designation"]."</th>";
			echo "</tr>";
			
			echo "<tr class='tab_bg_2'><td>".$lang["device_moboard"][0].":".$device->fields["chipset"]."</td>";
			
			echo "<td>&nbsp;</td>";
			echo "</tr>";
			$specificity_label = "";
		break;
		case PROCESSOR_DEVICE :
			
			echo "<tr><th width='300px' >".$lang["devices"][4]."</th>";
			
			echo "<th colspan='2'>".$lang["devices"][8]." : ".$device->fields["designation"]."</th>";
			echo "</tr>";
			
			
			echo "<tr class='tab_bg_2'><td>".$lang["device_processor"][0]." : ".$device->fields["frequence"]."</td>";
			
			echo "<td>&nbsp;</td>";
			echo "</tr>";
			$specificity_label = $lang["device_processor"][0];
		break;
		case RAM_DEVICE :
			
			echo "<tr><th width='300px' >".$lang["devices"][6]."</th>";
			
			echo "<th >".$lang["devices"][8]." : ".$device->fields["designation"]."</th>";
			echo "</tr>";
			
			echo "<tr class='tab_bg_2'><td>".$lang["device_ram"][0]." : ".$device->fields["type"]."</td>";
			
			echo "<td>".$lang["device_ram"][1]." : ".$device->fields["frequence"]."</td>";
			
			echo "</tr>";
			$specificity_label = $lang["device_ram"][0];
		break;
		case SND_DEVICE :
			
			echo "<tr><th width='300px' >".$lang["devices"][7]."</th>";
			
			echo "<th >".$lang["devices"][8]." : ".$device->fields["designation"]."</th>";
			echo "</tr>";
			
			echo "<tr class='tab_bg_2'><td>".$lang["device_sndcard"][0]." : ".$device->fields["type"]."</td>";
			
			echo "<td>&nbsp;</td>";
			echo "</tr>";
			$specificity_label = "";
		break;
	}
	if(!empty($specificity_label)) {
		echo "<tr class='tab_bg_2'>";
		//Mise a jour des spécificitées
		echo "<form action=\"\" method=\"post\" >";
		echo "<td>".$specificity_label." : <input type='text' name='device_value' value=\"".$specif."\" size='20' /></td>";
		echo "<td align='center'><input type='submit' class='submit' name='update_device' value=\"".$lang["buttons"][7]."\" size='20' /></td>";
		echo "<input type=\"hidden\" name=\"compDevID\" value=\"".$compDevID."\" />";
		echo "</form>";
		echo "</tr>";
	}
}

//Update an internal device specificity
function update_device_specif($newValue,$compDevID) {

	$db = new DB;
	$query = "UPDATE glpi_computer_device SET specificity = '".$newValue."' WHERE ID = '".$compDevID."'";
	if($db->query($query)) return true;
	else return false;
}

//print select form for device type
function device_selecter($target,$cID,$withtemplate='') {
	global $lang;
	echo "<form action=\"$target\" method=\"post\">";
	echo "<select name=\"new_device_type\">";
	echo "<option value=\"".HDD_DEVICE."\">".$lang["devices"][1]."</option>";
	echo "<option value=\"".GFX_DEVICE."\">".$lang["devices"][2]."</option>";
	echo "<option value=\"".NETWORK_DEVICE."\">".$lang["devices"][3]."</option>";
	echo "<option value=\"".PROCESSOR_DEVICE."\">".$lang["devices"][4]."</option>";
	echo "<option value=\"".MOBOARD_DEVICE."\">".$lang["devices"][5]."</option>";
	echo "<option value=\"".RAM_DEVICE."\">".$lang["devices"][6]."</option>";
	echo "<option value=\"".SND_DEVICE."\">".$lang["devices"][7]."</option>";
	echo "</select>";
	echo "<input type=\"hidden\" name=\"withtemplate\" value=\"".$withtemplate."\" >";
	echo "<input type=\"hidden\" name=\"connect_device\" value=\"".true."\" >";
	echo "<input type=\"hidden\" name=\"cID\" value=\"".$cID."\" >";
	echo "<input type=\"submit\" class ='submit' value=\"".$lang["buttons"][2]."\" />";
	echo "</form>";
}

//Print the form/tab to add a new device on a computer
function compdevice_form_add($target,$device_type,$cID,$withtemplate='') {
	global $lang;
	$db = new DB;

	$query = "SELECT `ID`, `designation` FROM `".getDeviceTable($device_type)."`";
	if($result = $db->query($query)) {
		echo "<div align=\"center\">";
		echo "<table class='tab_cadre'>";
		echo "<tr>";
		echo "<th>";
		echo $lang["devices"][9].$cID;
		echo "</th>";
		echo "</tr>";
		echo "<tr><td align='center'>";
		echo "<form action=\"$target\" method=\"post\">";
		echo "<select name=\"new_device_id\">";
		$device = new Device($device_type);
		while($line = $db->fetch_array($result)){
			echo "<option value=\"";
			echo $line["ID"]."\" >".$line["designation"]."</option>"; 
		}
		echo "</select>";
		echo "<input type=\"hidden\" name=\"withtemplate\" value=\"".$withtemplate."\" >";
		echo "<input type=\"hidden\" name=\"connect_device\" value=\"".true."\" >";
		echo "<input type=\"hidden\" name=\"device_type\" value=\"".$device_type."\" >";
		echo "<input type=\"hidden\" name=\"cID\" value=\"".$cID."\" >";
		echo "<input type=\"submit\" value=\"".$lang["buttons"][2]."\" />";
		echo "</form>";
		echo "</td></tr>";
		echo "</table>";
		echo "</div>";
	} else {
		
		//or display an error message.
		return false;
	}
}
//Link the device to the computer
function compdevice_add($cID,$device_type,$dID) {
	$device = new Device($device_type);
	$device->getfromDB($dID);
	$device->computer_link($cID,$device_type);
}

?>