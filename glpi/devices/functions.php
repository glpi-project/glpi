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

function getDeviceSpecifityLabel($dev_type){
global $lang;
		switch ($dev_type){
			case MOBOARD_DEVICE :
				return "";
				break;
			case PROCESSOR_DEVICE :
				return $lang["device_processor"][0];
				break;
			case RAM_DEVICE :
				return  $lang["device_ram"][2];
				break;
			case HDD_DEVICE :
				return $lang["device_hdd"][4];
				break;
			case NETWORK_DEVICE :
				return $lang["device_iface"][2];
				break;
			case DRIVE_DEVICE :
				return "";
				break;
			case CONTROL_DEVICE :
				return "";
				break;
			case GFX_DEVICE :
				return "";
				break;
			case SND_DEVICE :
				return "";
				break;
			case PCI_DEVICE :
				return "";
				break;
			case CASE_DEVICE :
				return "";
				break;
			case POWER_DEVICE :
				return "";
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
	$specificity_label = getDeviceSpecifityLabel($device->type);
	switch($device->type) {
		case HDD_DEVICE :
			$type=$lang["devices"][1];
			$name=$device->fields["designation"];
			if (!empty($device->fields["rpm"]))	$entry[$lang["device_hdd"][0]]=$device->fields["rpm"];
			if (!empty($device->fields["interface"]))	$entry[$lang["device_hdd"][2]]=getDropdownName("glpi_dropdown_hdd_type",$device->fields["interface"]);
			if (!empty($device->fields["cache"])) $entry[$lang["device_hdd"][1]]=$device->fields["cache"];
			
			$specificity_size = 10;
		break;
		case GFX_DEVICE :
			$type=$lang["devices"][2];
			$name=$device->fields["designation"];
			if (!empty($device->fields["ram"])) $entry[$lang["device_gfxcard"][0]]=$device->fields["ram"];
			if (!empty($device->fields["interface"])) $entry[$lang["device_gfxcard"][2]]=$device->fields["interface"];
			
			$specificity_size = 10;
		break;
		case NETWORK_DEVICE :
			$type=$lang["devices"][3];
			$name=$device->fields["designation"];
			if (!empty($device->fields["bandwidth"])) $entry[$lang["device_iface"][0]]=$device->fields["bandwidth"];
			
			$specificity_size = 18;
		break;
		case MOBOARD_DEVICE :
			$type=$lang["devices"][5];
			$name=$device->fields["designation"];
			if (!empty($device->fields["chipset"])) $entry[$lang["device_moboard"][0]]=$device->fields["chipset"];
			
			$specificity_size = 10;
		break;
		case PROCESSOR_DEVICE :
			$type=$lang["devices"][4];
			$name=$device->fields["designation"];
			if (!empty($device->fields["frequence"])) $entry[$lang["device_processor"][0]]=$device->fields["frequence"];
			
			$specificity_size = 10;
		break;
		case RAM_DEVICE :
			$type=$lang["devices"][6];
			$name=$device->fields["designation"];
			if (!empty($device->fields["type"])) $entry[$lang["device_ram"][0]]=getDropdownName("glpi_dropdown_ram_type",$device->fields["type"]);
			if (!empty($device->fields["frequence"])) $entry[$lang["device_ram"][1]]=$device->fields["frequence"];
			
			$specificity_size = 10;
		break;
		case SND_DEVICE :
			
			$type=$lang["devices"][7];
			$name=$device->fields["designation"];
			if (!empty($device->fields["type"])) $entry[$lang["device_sndcard"][0]]=$device->fields["type"];
			
			$specificity_size = 10;
		break;
		case DRIVE_DEVICE : 
			$type=$lang["devices"][19];
			$name=$device->fields["designation"];
			if (!empty($device->fields["is_writer"])) $entry[$lang["device_drive"][0]]=$device->fields["is_writer"];
			if (!empty($device->fields["speed"])) $entry[$lang["device_drive"][1]]=$device->fields["speed"];
			if (!empty($device->fields["frequence"])) $entry[$lang["device_drive"][2]]=$device->fields["frequence"];
		break;
		case CONTROL_DEVICE :
			$type=$lang["devices"][20];
			$name=$device->fields["designation"];
			if (!empty($device->fields["raid"])) $entry[$lang["device_control"][0]]=$device->fields["raid"];
			if (!empty($device->fields["interface"])) $entry[$lang["device_control"][1]]=$device->fields["interface"];
		
		break;
		case PCI_DEVICE :
			$type=$lang["devices"][21];
			$name=$device->fields["designation"];
		
		break;
		case POWER_DEVICE :
			$type=$lang["devices"][23];
			$name=$device->fields["designation"];
			if (!empty($device->fields["power"])) $entry[$lang["device_power"][0]]=$device->fields["power"];
			if (!empty($device->fields["atx"])) $entry[$lang["device_power"][1]]=$device->fields["atx"];
		
		break;
		case CASE_DEVICE :
			$type=$lang["devices"][22];
			$name=$device->fields["designation"];
			if (!empty($device->fields["format"])) $entry[$lang["device_sndcard"][0]]=$device->fields["format"];
		
		break;
	}
	
	echo "<tr class='tab_bg_2'>";
	echo "<td align='center'><a href='".$HTMLRel."devices/index.php?device_type=".$device->type."'>$type</a></td>";
	echo "<td align='center'><a href='".$HTMLRel."devices/devices-info-form.php?ID=".$device->fields['ID']."&amp;device_type=".$device->type."'>&nbsp;$name&nbsp;</a></td>";
	
	if (count($entry)>0){
		$more=0;
		if(!empty($specificity_label)) $more=1;
		$colspan=60/(count($entry)+$more);
		foreach ($entry as $key => $val){
		echo "<td colspan='$colspan'>$key:&nbsp;$val</td>";
	
		}
	
	} else if(empty($specificity_label)) echo "<td colspan='60'>&nbsp;</td>";
	else $colspan=60;
	
	
	if(!empty($specificity_label)) {
		
	
		if(empty($specif) && !empty($device->fields["specif_default"])) {
			$specif = $device->fields["specif_default"];
		}
		//Mise a jour des spécificitées
		if(!empty($withtemplate) && $withtemplate == 2) {
			if(empty($specif)) $specif = "&nbsp;";
			echo "<td colspan='$colspan'>".$specificity_label.":&nbsp;$specif</td><td>&nbsp;</td><td>&nbsp;</td>";
		}
		else {

//			echo "<form name='form_update_device_$compDevID' action=\"\" method=\"post\" >";
			echo "<td align='right' colspan='$colspan'>".$specificity_label.":&nbsp;<input type='text' name='devicevalue_$compDevID' value=\"".$specif."\" size='$specificity_size' ></td>";
			echo "<td align='center'>";
//			echo "<img src='".$HTMLRel."pics/actualiser.png' class='calendrier' alt='".$lang["buttons"][7]."' title='".$lang["buttons"][7]."'
//			onclick='form_update_device_$compDevID.submit()'>";
//			echo "<input type=\"hidden\" name=\"update_device\" value=\"".$compDevID."\" >";
//			echo "</form>";

			echo "<input type='image' name='update_device' value='$compDevID' src='".$HTMLRel."pics/actualiser.png' class='calendrier'>";
			echo "</td>";

			echo "<td>";
//			echo "<form name='form_unlink_device_$compDevID' action=\"\" method=\"post\" >";
//			echo "<img class='calendrier' src='".$HTMLRel."pics/delete2.png'  onclick='form_unlink_device_$compDevID.submit()' title ='".$lang["devices"][11]."' alt='".$lang["devices"][11]."'";
//			echo "<input type=\"hidden\" name=\"unlink_device\" value=\"".$compDevID."\" >";

			echo "<input type='image' name='unlink_device_$compDevID' value='$compDevID' src='".$HTMLRel."pics/delete2.png' class='calendrier'>";
			echo "</td>";

//			echo "</form>";
		}
		
	} else {
   		echo "<td>&nbsp;</td>";
		if(!empty($withtemplate) && $withtemplate == 2) {
  		  echo "<td>&nbsp;</td>";
  		 } else {
//  		 echo "<form name='form_unlink_device_$compDevID' action=\"\" method=\"post\" >";
  		echo "<td>";
//  		echo "<img class='calendrier' src='".$HTMLRel."pics/delete2.png'  onclick='form_unlink_device_$compDevID.submit()' title ='".$lang["devices"][11]."' alt='".$lang["devices"][11]."'";
//  		 echo "<input type=\"hidden\" name=\"unlink_device\" value=\"".$compDevID."\" >";

			echo "<input type='image' name='unlink_device_$compDevID' value='$compDevID' src='".$HTMLRel."pics/delete2.png' class='calendrier'>";

  		echo "</td>";
//  		 echo "</form>";
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
		echo "<table width='800' class='tab_cadre'>";
		echo "<tr  class='tab_bg_1'><td colspan='2' align='right'>";
		echo $lang["devices"][0].":";
		echo "</td>";
		echo "<td colspan='63'>"; 
		echo "<form action=\"$target\" method=\"post\">";
		echo "<select name=\"new_device_type\">";
		
		
		echo "<option value=\"-1\">-----</option>";
		echo "<option value=\"".MOBOARD_DEVICE."\">".getDictDeviceLabel(MOBOARD_DEVICE)."</option>";
		echo "<option value=\"".HDD_DEVICE."\">".getDictDeviceLabel(HDD_DEVICE)."</option>";
		echo "<option value=\"".GFX_DEVICE."\">".getDictDeviceLabel(GFX_DEVICE)."</option>";
		echo "<option value=\"".NETWORK_DEVICE."\">".getDictDeviceLabel(NETWORK_DEVICE)."</option>";
		echo "<option value=\"".PROCESSOR_DEVICE."\">".getDictDeviceLabel(PROCESSOR_DEVICE)."</option>";
		echo "<option value=\"".SND_DEVICE."\">".getDictDeviceLabel(SND_DEVICE)."</option>";
		echo "<option value=\"".RAM_DEVICE."\">".getDictDeviceLabel(RAM_DEVICE)."</option>";
		echo "<option value=\"".DRIVE_DEVICE."\">".getDictDeviceLabel(DRIVE_DEVICE)."</option>";
		echo "<option value=\"".CONTROL_DEVICE."\">".getDictDeviceLabel(CONTROL_DEVICE)."</option>";
		echo "<option value=\"".PCI_DEVICE."\">".getDictDeviceLabel(PCI_DEVICE)."</option>";
		echo "<option value=\"".CASE_DEVICE."\">".getDictDeviceLabel(CASE_DEVICE)."</option>";
		echo "<option value=\"".POWER_DEVICE."\">".getDictDeviceLabel(POWER_DEVICE)."</option>";
		echo "</select>";
		//echo "<td colspan='2' align='center'>";
		echo "<input type=\"hidden\" name=\"withtemplate\" value=\"".$withtemplate."\" >";
		echo "<input type=\"hidden\" name=\"connect_device\" value=\"".true."\" >";
		echo "<input type=\"hidden\" name=\"cID\" value=\"".$cID."\" >";
		echo "<input type=\"submit\" class ='submit' value=\"".$lang["buttons"][2]."\" >";
		echo "</form>";
		echo "</td>";
		echo "</tr></table>";
		}
}

//Print the form/tab to add a new device on a computer
function compdevice_form_add($target,$device_type,$cID,$withtemplate='') {
	global $lang;
	$db = new DB;

	$query = "SELECT `ID`, `designation` FROM `".getDeviceTable($device_type)."` ORDER BY designation";
	if($result = $db->query($query)) {
		if ($db->numrows($result)==0){
			echo "<div align=\"center\"><strong>";
			echo $lang["devices"][18]."<br>";
			echo "<a href=\"javascript:history.back()\">".$lang["buttons"][13]."</a>";
			echo "</strong></div>";
		} else {
			echo "<form action=\"$target\" method=\"post\">";
			echo "<div align=\"center\">";
			echo "<table class='tab_cadre'>";
			echo "<tr>";
			echo "<th>";
			echo $lang["devices"][9].$cID;
			echo "</th>";
			echo "</tr>";
			echo "<tr><td align='center'>";
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
			echo "<input type=\"submit\" value=\"".$lang["buttons"][2]."\" >";
		
			echo "</td></tr>";
			echo "</table>";
			echo "</div>";
			echo "</form>";
		}
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

/* --------------- not in use -------------------- but get it for later if needed.
// Print Search Form
function searchFormDevices($device_type,$field="",$phrasetype= "",$contains="",$sort= "") {

	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang,$HTMLRel;

	$option[$device_type.".designation"]			= $lang["devices"][14];
	$option[$device_type.".ID"]				= $lang["devices"][13];
	$option[$device_type.".comment"]			= $lang["devices"][15];
	$option["glpi_enterprises.name"]			= $lang["common"][5];


	echo "<form method='get' action=\"".$cfg_install["root"]."/devices/index.php\">";
	echo "<div align='center'><table  width='750' class='tab_cadre'>";
	echo "<tr><th colspan='3'><b>".$lang["search"][0].":</b></th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";
	echo "<input type='text' size='15' name=\"contains\" value=\"". $contains ."\" >";
	echo "&nbsp;";
	echo $lang["search"][10]."&nbsp;";
	
	echo "<select name=\"field\" size='1'>";
        echo "<option value='all' ";
	if($field == "all") echo "selected";
	echo ">".$lang["search"][7]."</option>";
        reset($option);
	foreach ($option as $key => $val) {
		echo "<option value=\"".$key."\""; 
		if($key == $field) echo "selected";
		echo ">". $val ."</option>\n";
	}
	echo "</select>&nbsp;";
	echo $lang["search"][4];
	echo "&nbsp;<select name='sort' size='1'>";
	reset($option);
	foreach ($option as $key => $val) {
		echo "<option value=\"".$key."\"";
		if($key == $sort) echo "selected";
		echo ">".$val."</option>\n";
	}
	echo "</select> ";
	echo "<input type=\"hidden\" name=\"device_type\" value=\"".$device_type."\" />";
	echo "</td><td width='80' align='center' class='tab_bg_2'>";
	echo "<input type='submit' value=\"".$lang["buttons"][0]."\" class='submit'>";
	echo "</td></tr></table></div></form>";
}
*/

function showDevicesList($device_type,$target) {

	// Lists Device from a device_type

	GLOBAL $cfg_install, $cfg_layout, $cfg_features, $lang, $HTMLRel;

	$db = new DB;
	// Build query
		$fields = $db->list_fields(getDeviceTable($device_type));
		$columns = $db->num_fields($fields);
		
	$query = "select DISTINCT device.ID from ".getDeviceTable($device_type)." as device ";
	$query.= " LEFT JOIN glpi_enterprises ON (glpi_enterprises.ID = device.FK_glpi_enterprise ) ";
	$query .= " ORDER by device.designation ASC";
	//echo $query;
// Get it from database	
	if ($result = $db->query($query)) {
		$numrows = $db->numrows($result);
		$numrows_limit = $numrows;
		$result_limit = $result;
		if ($numrows_limit>0) {
			// Produce headline
			echo "<center><table class='tab_cadre'><tr>";

			// designation
			echo "<th>";
			echo "<a href=\"$target?device_type=$device_type\">";
			echo $lang["printers"][5]."</a></th>";

			// Manufacturer		
			echo "<th>";
			echo "<a href=\"$target?device_type=$device_type\">";
			echo $lang["common"][5]."</a></th>";
			
			echo "</tr>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$ID = $db->result($result_limit, $i, "ID");
				$device = new Device(str_replace("glpi_device_", "", $device_type));
				$device->getFromDB($ID);
				echo "<tr class='tab_bg_2'>";
				echo "<td><b>";
				echo "<a href=\"".$cfg_install["root"]."/devices/devices-info-form.php?ID=$ID&amp;device_type=$device_type\">";
				echo $device->fields["designation"]." (".$device->fields["ID"].")";
				echo "</a></b></td>";
				echo "<td>". getDropdownName("glpi_enterprises",$device->fields["FK_glpi_enterprise"]) ."</td>";
				echo "</tr>";
			}

			// Close Table
			echo "</table></center>";
		} else {
			echo "<center><b>".$lang["devices"][18]."</b></center>";
			echo "<hr noshade>";
		}
	}
}


function titleDevices($device_type){
	GLOBAL  $lang,$HTMLRel;           
	echo "<div align='center'><table border='0'><tr><td>";
	//TODO : CHANGER LE PICS et le alt.!!!!!!!!!!!
	echo "<img src=\"".$HTMLRel."pics/periph.png\" alt='".$lang["devices"][12]."' title='".$lang["devices"][12]."'></td><td><a  class='icon_consol' href=\"devices-info-form.php?device_type=$device_type\"><b>".$lang["devices"][12]."</b></a>";
	echo "</td>";
	echo "</tr></table></div>";
}

function getDictDeviceLabel($device_num=-1) {
	
	global $lang;
	$dp=array();
	$dp[MOBOARD_DEVICE]=$lang["devices"][5];	
	$dp[PROCESSOR_DEVICE]=$lang["devices"][4];
	$dp[NETWORK_DEVICE]=$lang["devices"][3];
	$dp[RAM_DEVICE]=$lang["devices"][6];	
	$dp[HDD_DEVICE]=$lang["devices"][1];	
	$dp[DRIVE_DEVICE]=$lang["devices"][19];		
	$dp[CONTROL_DEVICE]=$lang["devices"][20];		
	$dp[GFX_DEVICE]=$lang["devices"][2];		
	$dp[SND_DEVICE]=$lang["devices"][7];		
	$dp[PCI_DEVICE]=$lang["devices"][21];		
	$dp[CASE_DEVICE]=$lang["devices"][22];		
	$dp[POWER_DEVICE]=$lang["devices"][23];
	if ($device_num==-1)
	return $dp;
	else return $dp[$device_num];
}

function showDevicesForm ($target,$ID,$device_type) {

	GLOBAL $cfg_install,$cfg_layout,$lang,$HTMLRel,$REFERER;

	$device = new Device($device_type);

	$device_spotted = false;

	if(empty($ID)) {
		if($device->getEmpty()) $device_spotted = false;
	} else {
		if($device->getfromDB($ID)) $device_spotted = true;
	}

		$table=getDeviceTable($device_type);

	echo "<div align='center'>";
	echo "<a href='$REFERER'>".$lang["buttons"][13]."</a>";
	echo "<form method='post' name='form' action=\"$target\">";
	echo "<input type='hidden' name='referer' value='$REFERER'>";
	echo "<table class='tab_cadre' width='800' cellpadding='2'>";
	echo "<tr><th align='center' colspan='1'>";
	echo getDictDeviceLabel($device_type)."</th><th align='center' colspan='1'> ID : ".$ID;
	echo "<tr><td class='tab_bg_1' colspan='1'>";
	// table commune
	echo "<table cellpadding='1' cellspacing='0' border='0'>\n";
	echo "<tr><td>".$lang["printers"][5].":	</td>";
	echo "<td>";
	autocompletionTextField("designation",$table,"designation",$device->fields["designation"],20);

	echo "</td></tr>";
	echo "<tr class='tab_bg_1'><td>".$lang["common"][5].": 	</td><td colspan='2'>";
	dropdownValue("glpi_enterprises","FK_glpi_enterprise",$device->fields["FK_glpi_enterprise"]);
	echo "</td></tr>";
	if (getDeviceSpecifityLabel($device_type)!=""){
		echo "<tr><td>".getDeviceSpecifityLabel($device_type)." ".$lang["devices"][24]."</td>";
		echo "<td><input type='text' name='specif_default' value=\"".$device->fields["specif_default"]."\" size='20'></td>";
		echo "</tr>";
	}
	echo "</table>";
	// fin table Commune
	echo "</td>\n";	
	echo "<td class='tab_bg_1' valign='top'>";

	// table particuliere
	echo "<table cellpadding='1' cellspacing='0' border='0'>";
	switch($table) {
		case "glpi_device_moboard" : 
			echo "<tr><td>".$lang["device_moboard"][0].":</td>";
			echo "<td>";
			autocompletionTextField("chipset",$table,"chipset",$device->fields["chipset"],20);

			echo "</td></tr>";
		break;
		case "glpi_device_processor" :
			echo "<tr><td>".$lang["device_processor"][0].":</td><td>";
				autocompletionTextField("frequence",$table,"frequence",$device->fields["frequence"],20);
			echo "</td></tr>";
		break;
		case "glpi_device_ram" :
			echo "<tr><td>".$lang["device_ram"][0].":</td>";
			echo "<td>";
			dropdownValue("glpi_dropdown_ram_type","type",$device->fields["type"]);
			echo "</td>";
			echo "</tr>";
			echo "<tr><td>".$lang["device_ram"][1].":</td><td>";
				autocompletionTextField("frequence",$table,"frequence",$device->fields["frequence"],20);
			echo "</td></tr>";
		break;
		case "glpi_device_hdd" :
			echo "<tr><td>".$lang["device_hdd"][0].":</td><td>";
			autocompletionTextField("rpm",$table,"rpm",$device->fields["rpm"],20);

			echo "</td></tr>";
			echo "<tr><td>".$lang["device_hdd"][1].":</td><td>";
				autocompletionTextField("cache",$table,"cache",$device->fields["cache"],20);
			echo "</td></tr>";


			echo "<tr><td>".$lang["device_hdd"][2].":</td>";
			echo "<td>";
			dropdownValue("glpi_dropdown_hdd_type","interface",$device->fields["interface"]);
			echo "</td>";

			echo "</tr>";
		break;
		case "glpi_device_iface" :
			echo "<tr><td>".$lang["device_iface"][0].":</td><td>";
			autocompletionTextField("bandwidth",$table,"bandwidth",$device->fields["bandwidth"],20);
			echo "</td></tr>";
		break;
		case "glpi_device_drive" :
			echo "</tr>";
			echo "<tr><td>".$lang["device_drive"][0].":</td>";
			echo "<td>".$lang["choice"][0]."<input type='radio' name='is_writer' value=\"Y\" ";
			if(strcmp($device->fields["is_writer"],"Y") == 0) echo "checked='checked'";
			echo "></td>";
			echo "<td>".$lang["choice"][1]."<input type='radio' name='is_writer' value=\"N\" ";
			if(strcmp($device->fields["is_writer"],"N") == 0) echo "checked='checked'";
			echo "></td>";
			echo "</tr>";
			echo "<tr><td>".$lang["device_drive"][2].":</td>";
			echo "<td><select name='interface'>";
			echo "<option value=\"IDE\"";
			if(strcmp($device->fields["interface"],"IDE") == 0) echo "selected='selected'";
			echo ">".$lang["device_control"][2]."</option>";
			echo "<option value=\"SATA\" ";
			if(strcmp($device->fields["interface"],"SATA") == 0) echo "selected='selected'";
			echo ">".$lang["device_control"][3]."</option>";
			echo "<option value=\"SCSI\"";
			if(strcmp($device->fields["interface"],"SCSI") == 0) echo "selected='selected'";
			echo ">".$lang["device_control"][4]."</option>";
			echo "</select>";
			echo "</td>";
			echo "</tr>";
			echo "<tr><td>".$lang["device_drive"][1].":</td><td>";
			autocompletionTextField("speed",$table,"speed",$device->fields["speed"],20);
			echo "</td></tr>";
			
			
		break;
		case  "glpi_device_control" :
			echo "</tr>";
			echo "<tr><td>".$lang["device_control"][0].":</td>";
			echo "<td>".$lang["choice"][0]."<input type='radio' name='raid' value=\"Y\" ";
			if(strcmp($device->fields["raid"],"Y") == 0) echo "checked='checked'";
			echo "></td>";
			echo "<td>".$lang["choice"][1]."<input type='radio' name='raid' value=\"N\" ";
			if(strcmp($device->fields["raid"],"N") == 0) echo "checked='checked'";
			echo "></td>";
			echo "</tr>";
			echo "<tr><td>".$lang["device_control"][1].":</td>";
			echo "<td><select name='interface'>";
			echo "<option value=\"IDE\"";
			if(strcmp($device->fields["interface"],"IDE") == 0) echo "selected='selected'";
			echo ">".$lang["device_control"][2]."</option>";
			echo "<option value=\"SATA\" ";
			if(strcmp($device->fields["interface"],"SATA") == 0) echo "selected='selected'";
			echo ">".$lang["device_control"][3]."</option>";
			echo "<option value=\"SCSI\"";
			if(strcmp($device->fields["interface"],"SCSI") == 0) echo "selected='selected'";
			echo ">".$lang["device_control"][4]."</option>";
			echo "<option value=\"USB\"";
			if(strcmp($device->fields["interface"],"USB") == 0) echo "selected='selected'";
			echo ">".$lang["device_control"][5]."</option>";
			echo "</select>";
			echo "</td>";
			echo "</tr>";
		
		break;
		case "glpi_device_gfxcard" :
			echo "<tr><td>".$lang["device_gfxcard"][0].":</td><td>";
			autocompletionTextField("ram",$table,"ram",$device->fields["ram"],20);
			echo "</td></tr>";
			echo "<tr><td>".$lang["device_gfxcard"][2].":</td>";
			echo "<td><select name='interface'>";
			echo "<option value='AGP' ".($device->fields["interface"]=="AGP"?"selected":"").">AGP</option>";
			echo "<option value='PCI' ".($device->fields["interface"]=="PCI"?"selected":"").">PCI</option>";
			echo "<option value='PCI-X' ".($device->fields["interface"]=="PCI-X"?"selected":"").">PCI-X</option>";
			echo "<option value='Other' ".($device->fields["interface"]=="Other"?"selected":"").">Other</option>";
			echo "</select>";
			echo "</td>";
			echo "</tr>";
		break;
		case "glpi_device_sndcard" :
			echo "<tr><td>".$lang["device_sndcard"][0].":</td><td>";
			autocompletionTextField("type",$table,"type",$device->fields["type"],20);
			echo "</td></tr>";
		break;
		case "glpi_device_pci" :
		break;
		case "glpi_device_case" :
			echo "<tr><td>".$lang["device_case"][0].":</td>";
			echo "<td><select name='format'>";
			echo "<option value=\"grand\"";
			if(strcmp($device->fields["format"],"Grand") == 0) echo "selected='selected'";
			echo ">".$lang["device_case"][1]."</option>";
			echo "<option value=\"moyen\" ";
			if(strcmp($device->fields["format"],"Moyen") == 0) echo "selected='selected'";
			echo ">".$lang["device_case"][2]."</option>";
			echo "<option value=\"micro\"";
			if(strcmp($device->fields["format"],"Micro") == 0) echo "selected='selected'";
			echo ">".$lang["device_case"][3]."</option>";
			echo "</select>";
			echo "</td>";
			echo "</tr>";

		break;
		case "glpi_device_power" :
			echo "<tr><td>".$lang["device_power"][0].":</td><td>";
			autocompletionTextField("power",$table,"power",$device->fields["power"],20);
			echo "</td></tr>";
			echo "<tr><td>".$lang["device_power"][1].":</td>";
			echo "<td>".$lang["choice"][0]."<input type='radio' name='atx' value=\"Y\" ";
			if(strcmp($device->fields["atx"],"Y") == 0) echo "checked='checked'";
			echo "></td>";
			echo "<td>".$lang["choice"][1]."<input type='radio' name='atx' value=\"N\" ";
			if(strcmp($device->fields["atx"],"N") == 0) echo "checked='checked'";
			echo "></td>";
			echo "</tr>";

		break;	

	}
	//echo "</td></tr>";
	echo "</table>";
	echo "</td>\n";	
	echo "</tr>";
	
	echo "<tr>";
	echo "<td class='tab_bg_1' valign='top' colspan='2'>";

	// table commentaires
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'><tr><td valign='top'>";
	echo $lang["devices"][15].":	</td>";
	echo "<td align='center'><textarea cols='35' rows='4' name='comment' >".$device->fields["comment"]."</textarea>";
	echo "</td></tr></table>";

	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	if($device_spotted) {
		echo "<td class='tab_bg_2' valign='top' align='center'>";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<input type='hidden' name='device_type' value=\"$device_type\">\n";
		echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
		echo "</td>";
		//echo "</form>\n\n";
		//echo "<form action=\"$target\" method='post'>\n";
		echo "<td class='tab_bg_2' valign='top' align='center'>\n";
//		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
//		echo "<input type='hidden' name='device_type' value=\"$device_type\">\n";
		echo "<div align='center'>";
		echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
		echo "</div>";
		echo "</td>";
		//echo "</form>";
		echo "</tr>";
	}
	else {
		echo "<td class='tab_bg_2' valign='top' align='center' colspan='2'>";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<input type='hidden' name='device_type' value=\"$device_type\">\n";
		echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
		echo "</td>";
		//echo "</form>\n\n";	
	}
	echo "</table></form></div>";
}

function updateDevice($input) {
	// Update a device in the database

	$device = new Device($input["device_type"]);
	$device->getFromDB($input["ID"]);
	
	// Fill the update-array with changes
	$x=0;
	$updates = array();
	foreach ($input as $key => $val) {
		if (array_key_exists($key,$device->fields)&&$device->fields[$key] != $input[$key]) {
			$device->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	$device->updateInDB($updates);

}

function addDevice($input) {
	// Add device
	$db=new DB;
	$device = new Device($input["device_type"]);

	
	// dump status
	$oldID=$input["ID"];
	// Pop off the last Three attributes, no longer needed
	unset($input['add']);
	unset($input['ID']);
	unset($input['device_type']);

 	
	// fill array for update
	foreach ($input as $key => $val) {
		if ($key[0]!='_'&&(empty($device->fields[$key]) || $device->fields[$key] != $input[$key])) {
			$device->fields[$key] = $input[$key];
		}
	}
	$new_id = $device->addToDB();




}

function deleteDevice($input) {
	// Delete Device
	$device = new Device($input["device_type"]);
	$device->deleteFromDB($input["ID"]);
	
} 	

?>
