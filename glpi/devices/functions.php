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

//print form/tab for a device linked to a computer
function printDeviceComputer($device,$specif,$compID,$compDevID) {
	global $lang;
	#print_r($device);
	echo "<tr><th>".$lang["devices"][8].":</th>";
	echo "<th>".$device->fields["designation"]."</th>";
	echo "</tr>";
	//print the good form switch the wanted device type.
	switch($device->table) {
		case "glpi_device_hdd" :
			echo "<tr><td>".$lang["device_hdd"][0].":</td>";
			echo "<td>".$device->fields["rpm"]."</td>";
			echo "</tr>";
			echo "<tr><td>".$lang["device_hdd"][2].":</td>";
			echo "<td>".$device->fields["interface"]."</td>";
			echo "</tr>";
			echo "<tr><td>".$lang["device_hdd"][1].":</td>";
			echo "<td>".$device->fields["cache"]."</td>";
			echo "</tr>";
			$specificity_label = $lang["device_hdd"][4];
		break;
		case "glpi_device_gfxcard" :
			echo "<tr><td>".$lang["device_gfxcard"][0].":</td>";
			echo "<td>".$device->fields["ram"]."</td>";
			echo "</tr>";
			echo "<tr><td>".$lang["device_gfxcard"][2].":</td>";
			echo "<td>".$device->fields["interface"]."</td>";
			echo "</tr>";
			$specificity_label = "";
		break;
		case "glpi_device_iface" :
			echo "<tr><td>".$lang["device_iface"][0].":</td>";
			echo "<td>".$device->fields["bandwidth"]."</td>";
			echo "</tr>";
			$specificity_label = $lang["device_iface"][2];
		break;
		case "glpi_device_moboard" :
			echo "<tr><td>".$lang["device_moboard"][0].":</td>";
			echo "<td>".$device->fields["chipset"]."</td>";
			echo "</tr>";
			$specificity_label = "";
		break;
		case "glpi_device_processor" :
			echo "<tr><td>".$lang["device_processor"][0].":</td>";
			echo "<td>".$device->fields["frequence"]."</td>";
			echo "</tr>";
			$specificity_label = $lang["device_processor"][0];
		break;
		case "glpi_device_ram" :
			echo "<tr><td>".$lang["device_ram"][0].":</td>";
			echo "<td>".$device->fields["type"]."</td>";
			echo "</tr>";
			echo "<tr><td>".$lang["device_ram"][1].":</td>";
			echo "<td>".$device->fields["frequence"]."</td>";
			echo "</tr>";
			$specificity_label = $lang["device_ram"][0];
		break;
		case "glpi_device_sndcard" :
			echo "<tr><td>".$lang["device_sndcard"][0].":</td>";
			echo "<td>".$device->fields["type"]."</td>";
			echo "</tr>";
			$specificity_label = "";
		break;
	}
	if(!empty($specificity_label)) {
		echo "<tr><td>".$specificity_label.":</td>";
		//Mise a jour des spécificitées
		echo "<form action=\"\" method=\"post\" >";
		echo "<td><input type='text' name='device_value' value=\"".$specif."\" size='20' /></td>";
		echo "<td><input type='submit' name='update_device' value=\"".$lang["buttons"][7]."\" size='20' /></td>";
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
function device_selecter($target,$cID) {
	global $lang;
	echo "<form action=\"$target\" method=\"post\">";
	echo "<select name=\"new_device_type\">";
	echo "<option value=\"glpi_device_hdd\">".$lang["devices"][1]."</option>";
	echo "<option value=\"glpi_device_gfxcard\">".$lang["devices"][2]."</option>";
	echo "<option value=\"glpi_device_iface\">".$lang["devices"][3]."</option>";
	echo "<option value=\"glpi_device_processor\">".$lang["devices"][4]."</option>";
	echo "<option value=\"glpi_device_moboard\">".$lang["devices"][5]."</option>";
	echo "<option value=\"glpi_device_ram\">".$lang["devices"][6]."</option>";
	echo "<option value=\"glpi_device_sndcard\">".$lang["devices"][7]."</option>";
	echo "</select>";
	echo "<input type=\"hidden\" name=\"cID\" value=\"".$cID."\" >";
	echo "<input type=\"submit\" value=\"".$lang["buttons"][2]."\" />";
	echo "</form>";
}

//Print the form/tab to add a new device on a computer
function compdevice_form_add($target,$device_type,$cID) {
	global $lang;
	$db = new DB;
	$query = "SELECT `ID`, `designation` FROM `".$device_type."`";
	if($result = $db->query($query)) {
		echo "<div align=\"center\">";
		echo "<table>";
		echo "<tr>";
		echo "<th>";
		echo $lang["devices"][9].$cID;
		echo "</th>";
		echo "</tr>";
		echo "<tr>";
		echo "<form action=\"$target\" method=\"post\">";
		echo "<select name=\"new_device_id\">";
		$device = new Device($device_type);
		while($line = $db->fetch_array($result)){
			echo "<option value=\"";
			echo $line["ID"]."\" >".$line["designation"]."</option>"; 
		}
		echo "</select>";
		echo "<input type=\"hidden\" name=\"device_type\" value=\"".$device_type."\" >";
		echo "<input type=\"hidden\" name=\"cID\" value=\"".$cID."\" >";
		echo "<input type=\"submit\" value=\"".$lang["buttons"][2]."\" />";
		echo "</form>";
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