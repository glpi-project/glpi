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
function printDeviceComputer($device,$specif,$compID,$compDevID,$withtemplate='') {
	global $lang,$HTMLRel;
	
	//print the good form switch the wanted device type.
	$entry=array();
	$type="";
	$name="";
	switch($device->type) {
		case HDD_DEVICE :
			$type=$lang["devices"][1];
			$name=$device->fields["designation"];
			if (!empty($device->fields["rpm"]))	$entry[$lang["device_hdd"][0]]=$device->fields["rpm"];
			if (!empty($device->fields["interface"]))	$entry[$lang["device_hdd"][2]]=$device->fields["interface"];
			if (!empty($device->fields["cache"])) $entry[$lang["device_hdd"][1]]=$device->fields["cache"];
			
			$specificity_label = $lang["device_hdd"][4];
		
		break;
		case GFX_DEVICE :
			$type=$lang["devices"][2];
			$name=$device->fields["designation"];
			if (!empty($device->fields["ram"])) $entry[$lang["device_gfxcard"][0]]=$device->fields["ram"];
			if (!empty($device->fields["interface"])) $entry[$lang["device_gfxcard"][2]]=$device->fields["interface"];
			
			$specificity_label = "";
		break;
		case NETWORK_DEVICE :
			$type=$lang["devices"][3];
			$name=$device->fields["designation"];
			if (!empty($device->fields["bandwidth"])) $entry[$lang["device_iface"][0]]=$device->fields["bandwidth"];
			
			$specificity_label = $lang["device_iface"][2];
		break;
		case MOBOARD_DEVICE :
			$type=$lang["devices"][5];
			$name=$device->fields["designation"];
			if (!empty($device->fields["chipset"])) $entry[$lang["device_moboard"][0]]=$device->fields["chipset"];
			
			$specificity_label = "";
		break;
		case PROCESSOR_DEVICE :
			$type=$lang["devices"][4];
			$name=$device->fields["designation"];
			if (!empty($device->fields["frequence"])) $entry[$lang["device_processor"][0]]=$device->fields["frequence"];
			
			$specificity_label = $lang["device_processor"][0];
		break;
		case RAM_DEVICE :
			$type=$lang["devices"][6];
			$name=$device->fields["designation"];
			if (!empty($device->fields["type"])) $entry[$lang["device_ram"][0]]=$device->fields["type"];
			if (!empty($device->fields["frequence"])) $entry[$lang["device_ram"][1]]=$device->fields["frequence"];
			
			$specificity_label = $lang["device_ram"][0];
		break;
		case SND_DEVICE :
			
			$type=$lang["devices"][7];
			$name=$device->fields["designation"];
			if (!empty($device->fields["type"])) $entry[$lang["device_sndcard"][0]]=$device->fields["type"];
			
			$specificity_label = "";
		break;
	}
	
	echo "<tr class='tab_bg_2'>";
	echo "<td align='center'>$type</td><td align='center'>$name</td>";
	
	if (count($entry)>0){
		$colspan=60/count($entry);
		foreach ($entry as $key => $val){
		echo "<td colspan='$colspan'>$key:&nbsp;$val</td>";
	
		}
	
	} else echo "<td colspan='60'>&nbsp;</td>";
	
	
	if(!empty($specificity_label)) {
		
		//Mise a jour des spécificitées
		if(!empty($withtemplate) && $withtemplate == 2) {
			if(empty($specif)) $specif = "&nbsp;";
			echo "<td>".$specificity_label." : </td>";
			echo "<td align='center'>".$specif."</td>";
		}
		else {
			echo "<form name='form_update_device_$compDevID' action=\"\" method=\"post\" >";
			echo "<td align='right'>".$specificity_label." : <input type='text' name='device_value' value=\"".$specif."\" size='10' /></td>";
			echo "<td align='center'>";
			echo "<img src='".$HTMLRel."pics/actualiser.png' class='calendrier' alt='".$lang["buttons"][7]."' title='".$lang["buttons"][7]."'
			onclick='form_update_device_$compDevID.submit()'>";
			echo "</td>";
			echo "<input type=\"hidden\" name=\"update_device\" value=\"".$compDevID."\" />";
			echo "</form>";
			echo "<form name='form_unlink_device_$compDevID' action=\"\" method=\"post\" >";
			echo "<td><img class='calendrier' src='".$HTMLRel."pics/clear-old.png'  onclick='form_unlink_device_$compDevID.submit()' title ='".$lang["devices"][11]."' alt='".$lang["devices"][11]."'</img></td>";
			echo "<input type=\"hidden\" name=\"unlink_device\" value=\"".$compDevID."\" />";
			echo "</form>";
		}
		
	} else {
   		echo "<td>&nbsp;</td><td>&nbsp;</td>";
		if(!empty($withtemplate) && $withtemplate == 2) {
  		  echo "<td>&nbsp;</td>";
  		 } else {
  		 echo "<form name='form_unlink_device_$compDevID' action=\"\" method=\"post\" >";
  		echo "<td><img class='calendrier' src='".$HTMLRel."pics/clear-old.png'  onclick='form_unlink_device_$compDevID.submit()' title ='".$lang["devices"][11]."' alt='".$lang["devices"][11]."'</img></td>";
  		 echo "<input type=\"hidden\" name=\"unlink_device\" value=\"".$compDevID."\" />";
  		 echo "</form>";
  		 }
  	}
	echo "</tr>";
}







//Update an internal device specificity
function update_device_specif($newValue,$compDevID) {

	$db = new DB;
	$query = "UPDATE glpi_computer_device SET specificity = '".$newValue."' WHERE ID = '".$compDevID."'";
	if($db->query($query)) return true;
	else return false;
}


/**
* Unlink a device, linked to a computer.
* 
* Unlink a device and a computer witch link ID is $compDevID (on table glpi_computer_device)
*
* @param $compDevID ID of the computer-device link (table glpi_computer_device)
* @returns boolean
**/
function unlink_device_computer($compDevID) {
	$db = new DB;
	$query = "DELETE FROM glpi_computer_device where ID = '".$compDevID."'";
	if($db->query($query)) return true;
	else return false;
}
//print select form for device type
function device_selecter($target,$cID,$withtemplate='') {
	global $lang;
	if(!empty($withtemplate) && $withtemplate == 2) {
	//do nothing
	} else {
		
		echo "<tr  class='tab_bg_1'><td colspan='2'>";
		echo $lang["devices"][0].":";
		echo "</td>";
		echo "<td align ='center' colspan='63'>"; 
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
		//echo "<td colspan='2' align='center'>";
		echo "<input type=\"hidden\" name=\"withtemplate\" value=\"".$withtemplate."\" >";
		echo "<input type=\"hidden\" name=\"connect_device\" value=\"".true."\" >";
		echo "<input type=\"hidden\" name=\"cID\" value=\"".$cID."\" >";
		echo "<input type=\"submit\" class ='submit' value=\"".$lang["buttons"][2]."\" />";
		echo "</form>";
		echo "</td>";
		echo "</tr>";
		}
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
function compdevice_add($cID,$device_type,$dID,$specificity='') {
	$device = new Device($device_type);
	$device->getfromDB($dID);
	$device->computer_link($cID,$device_type,$specificity);
}

?>