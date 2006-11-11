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

function getDeviceTypeLabel($dev_type){
	global $lang;
	switch ($dev_type){
		case MOBOARD_DEVICE :
			return $lang["devices"][5];
			break;
		case PROCESSOR_DEVICE :
			return $lang["devices"][4];
			break;
		case RAM_DEVICE :
			return  $lang["devices"][6];
			break;
		case HDD_DEVICE :
			return $lang["devices"][1];
			break;
		case NETWORK_DEVICE :
			return $lang["devices"][3];
			break;
		case DRIVE_DEVICE :
			return $lang["devices"][19];
			break;
		case CONTROL_DEVICE :
			return $lang["devices"][20];
			break;
		case GFX_DEVICE :
			return $lang["devices"][2];
			break;
		case SND_DEVICE :
			return $lang["devices"][7];
			break;
		case PCI_DEVICE :
			return $lang["devices"][21];
			break;
		case CASE_DEVICE :
			return $lang["devices"][22];
			break;
		case POWER_DEVICE :
			return $lang["devices"][23];
			break;

	}


}


//print form/tab for a device linked to a computer
function printDeviceComputer($device,$quantity,$specif,$compID,$compDevID,$withtemplate='') {
	global $lang,$HTMLRel;

	if (!haveRight("computer","r")) return false;
	$canedit=haveRight("computer","w");

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
			if (!empty($device->fields["interface"]))	$entry[$lang["device_hdd"][2]]=getDropdownName("glpi_dropdown_interface",$device->fields["interface"]);
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
			if (!empty($device->fields["type"])) $entry[$lang["common"][17]]=getDropdownName("glpi_dropdown_ram_type",$device->fields["type"]);
			if (!empty($device->fields["frequence"])) $entry[$lang["device_ram"][1]]=$device->fields["frequence"];

			$specificity_size = 10;
			break;
		case SND_DEVICE :

			$type=$lang["devices"][7];
			$name=$device->fields["designation"];
			if (!empty($device->fields["type"])) $entry[$lang["common"][17]]=$device->fields["type"];

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
			if (!empty($device->fields["type"])) {
				$entry[$lang["device_case"][0]]=getDropdownName("glpi_dropdown_case_type",$device->fields["type"]);
			}

			break;
	}

	echo "<tr class='tab_bg_2'>";
	echo "<td align='center'>";
	echo "<select name='quantity_$compDevID'>";
	for ($i=0;$i<100;$i++)
		echo "<option value='$i' ".($quantity==$i?"selected":"").">".$i."x</option>";
	echo "</select>";
	echo "</td>";
	echo "<td align='center'><a href='".$HTMLRel."front/device.php?device_type=".$device->type."'>$type</a></td>";
	echo "<td align='center'><a href='".$HTMLRel."front/device.form.php?ID=".$device->fields['ID']."&amp;device_type=".$device->type."'>&nbsp;$name&nbsp;</a></td>";

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

		//Mise a jour des spécificitées
		if(!empty($withtemplate) && $withtemplate == 2) {
			if(empty($specif)) $specif = "&nbsp;";
			echo "<td colspan='$colspan'>".$specificity_label.":&nbsp;$specif</td><td>&nbsp;</td>";
		}
		else {

			echo "<td align='right' colspan='$colspan'>".$specificity_label.":&nbsp;<input type='text' name='devicevalue_$compDevID' value=\"".$specif."\" size='$specificity_size' ></td>";

		}

	} 
	echo "</tr>";
}







//Update an internal device specificity
// $strict : update based on ID
function update_device_specif($newValue,$compDevID,$strict=false) {

	// Check old value for history 
	global $db;
	$query ="SELECT * FROM glpi_computer_device WHERE ID = '".$compDevID."'";
	if ($result = $db->query($query)) 
		if ($db->numrows($result)){
			$data = addslashes_deep($db->fetch_array($result));
			// Is it a real change ?
			if($data["specificity"]!=$newValue){
				// Update specificity 
				$WHERE=" WHERE FK_device = '".$data["FK_device"]."' AND FK_computers = '".$data["FK_computers"]."' AND device_type = '".$data["device_type"]."'  AND specificity='".$data["specificity"]."'";
				if ($strict) $WHERE=" WHERE ID='$compDevID'";
				
				$query2 = "UPDATE glpi_computer_device SET specificity = '".$newValue."' $WHERE";
				if($db->query($query2)){

					$changes[0]='0';
					$changes[1]=addslashes($data["specificity"]);
					$changes[2]=$newValue;
					// history log
					historyLog ($data["FK_computers"],COMPUTER_TYPE,$changes,$data["device_type"],HISTORY_UPDATE_DEVICE);
					return true;
				}else{ 
					return false;
				}
			}
		}

}


function update_device_quantity($newNumber,$compDevID){
	// Check old value for history 
	global $db;
	$query ="SELECT * FROM glpi_computer_device WHERE ID = '".$compDevID."'";
	if ($result = $db->query($query)) {
		$data = addslashes_deep($db->fetch_array($result));

		$query2 = "SELECT ID FROM glpi_computer_device WHERE FK_device = '".$data["FK_device"]."' AND FK_computers = '".$data["FK_computers"]."' AND device_type = '".$data["device_type"]."' AND specificity='".$data["specificity"]."'";
		if ($result2 = $db->query($query2)) {

			// Delete devices
			$number=$db->numrows($result2);
			if ($number>$newNumber){
				for ($i=$newNumber;$i<$number;$i++){
					$data2 = $db->fetch_array($result2);
					unlink_device_computer($data2["ID"],1);
				}
				// Add devices
			} else if ($number<$newNumber){
				for ($i=$number;$i<$newNumber;$i++){
					compdevice_add($data["FK_computers"],$data["device_type"],$data["FK_device"],$data["specificity"],1);
				}
			}
		}
	}
}

/**
 * Unlink a device, linked to a computer.
 * 
 * Unlink a device and a computer witch link ID is $compDevID (on table glpi_computer_device)
 *
 * @param $compDevID ID of the computer-device link (table glpi_computer_device)
 * @param $dohistory log history updates ?
 * @returns boolean
 **/
function unlink_device_computer($compDevID,$dohistory=1){

	// get old value  and id for history 
	global $db;
	$query ="SELECT * FROM glpi_computer_device WHERE ID = '".$compDevID."'";
	if ($result = $db->query($query)) {
		$data = $db->fetch_array($result);
	} 

	$query2 = "DELETE FROM glpi_computer_device where ID = '".$compDevID."'";
	if($db->query($query2)){
		if ($dohistory){
			$device = new Device($data["device_type"]);
			$device->getFromDB($data["FK_device"]);

			$changes[0]='0';
			$changes[1]=addslashes($device->fields["designation"]);
			$changes[2]="";
			// history log
			historyLog ($data["FK_computers"],COMPUTER_TYPE,$changes,$data["device_type"],HISTORY_DELETE_DEVICE);
		}

		return true;
	}else{ return false;}

}

//Link the device to the computer
function compdevice_add($cID,$device_type,$dID,$specificity='',$dohistory=1) {
	$device = new Device($device_type);
	$device->getfromDB($dID);
	if (empty($specificity)) $specificity=$device->fields['specif_default'];
	$newID=$device->computer_link($cID,$device_type,$specificity);
	if ($dohistory){
		$changes[0]='0';
		$changes[1]="";
		$changes[2]=addslashes($device->fields["designation"]);
		// history log
		historyLog ($cID,COMPUTER_TYPE,$changes,$device_type,HISTORY_ADD_DEVICE);
	}
	return $newID;
}


function showDevicesList($device_type,$target) {

	// Lists Device from a device_type

	global $db,$cfg_glpi, $lang, $HTMLRel;


	$query = "select DISTINCT device.ID from ".getDeviceTable($device_type)." as device ";
	$query.= " LEFT JOIN glpi_enterprises ON (glpi_enterprises.ID = device.FK_glpi_enterprise ) ";
	$query .= " ORDER by device.designation ASC";

	// Get it from database	
	if ($result = $db->query($query)) {
		$numrows = $db->numrows($result);
		$numrows_limit = $numrows;
		$result_limit = $result;
		if ($numrows_limit>0) {
			// Produce headline
			echo "<div align='center'><table class='tab_cadre'><tr>";

			// designation
			echo "<th>";
			echo "<a href=\"$target?device_type=$device_type\">";
			echo $lang["common"][16]."</a></th>";

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
				echo "<a href=\"".$cfg_glpi["root_doc"]."/front/device.form.php?ID=$ID&amp;device_type=$device_type\">";
				echo $device->fields["designation"];
				if ($cfg_glpi["view_ID"]) echo " (".$device->fields["ID"].")";
				echo "</a></b></td>";
				echo "<td>". getDropdownName("glpi_enterprises",$device->fields["FK_glpi_enterprise"]) ."</td>";
				echo "</tr>";
			}

			// Close Table
			echo "</table></div>";
		} else {
			echo "<div align='center'><b>".$lang["devices"][18]."</b></div>";
			echo "<hr noshade>";
		}
	}
}


function titleDevices($device_type){
	global  $lang,$HTMLRel;           
	echo "<div align='center'><table border='0'><tr><td>";
	//TODO : CHANGER LE PICS et le alt.!!!!!!!!!!!
	echo "<img src=\"".$HTMLRel."pics/periph.png\" alt='".$lang["devices"][12]."' title='".$lang["devices"][12]."'></td><td><a  class='icon_consol' href=\"device.form.php?device_type=$device_type\"><b>".$lang["devices"][12]."</b></a>";
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

	global $cfg_glpi,$lang,$HTMLRel,$REFERER;

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
	echo "<table class='tab_cadre_fixe' cellpadding='2'>";
	echo "<tr><th align='center' colspan='1'>";
	echo getDictDeviceLabel($device_type)."</th><th align='center' colspan='1'> ID : ".$ID;
	echo "<tr><td class='tab_bg_1' colspan='1'>";
	// table commune
	echo "<table cellpadding='1' cellspacing='0' border='0'>\n";
	echo "<tr><td>".$lang["common"][16].":	</td>";
	echo "<td>";
	autocompletionTextField("designation",$table,"designation",$device->fields["designation"],50);

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
			echo "<tr><td>".$lang["common"][17].":</td>";
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
		dropdownValue("glpi_dropdown_interface","interface",$device->fields["interface"]);
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
		echo "<td>".$lang["choice"][1]."<input type='radio' name='is_writer' value=\"Y\" ";
		if(strcmp($device->fields["is_writer"],"Y") == 0) echo "checked='checked'";
		echo "></td>";
		echo "<td>".$lang["choice"][0]."<input type='radio' name='is_writer' value=\"N\" ";
		if(strcmp($device->fields["is_writer"],"N") == 0) echo "checked='checked'";
		echo "></td>";
		echo "</tr>";
		echo "<tr><td>".$lang["device_drive"][2].":</td>";
		echo "<td>";

		dropdownValue("glpi_dropdown_interface","interface",$device->fields["interface"]);

		echo "</td>";
		echo "</tr>";
		echo "<tr><td>".$lang["device_drive"][1].":</td><td>";
		autocompletionTextField("speed",$table,"speed",$device->fields["speed"],20);
		echo "</td></tr>";


		break;
		case  "glpi_device_control" :
			echo "</tr>";
		echo "<tr><td>".$lang["device_control"][0].":</td>";
		echo "<td>".$lang["choice"][1]."<input type='radio' name='raid' value=\"Y\" ";
		if(strcmp($device->fields["raid"],"Y") == 0) echo "checked='checked'";
		echo "></td>";
		echo "<td>".$lang["choice"][0]."<input type='radio' name='raid' value=\"N\" ";
		if(strcmp($device->fields["raid"],"N") == 0) echo "checked='checked'";
		echo "></td>";
		echo "</tr>";
		echo "<tr><td>".$lang["device_control"][1].":</td>";
		echo "<td>";
		dropdownValue("glpi_dropdown_interface","interface",$device->fields["interface"]);
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
			echo "<tr><td>".$lang["common"][17].":</td><td>";
		autocompletionTextField("type",$table,"type",$device->fields["type"],20);
		echo "</td></tr>";
		break;
		case "glpi_device_pci" :
			break;
		case "glpi_device_case" :
			echo "<tr><td>".$lang["device_case"][0].":</td>";
		echo "<td>";
		dropdownValue("glpi_dropdown_case_type","type",$device->fields["type"]);
		echo "</td>";
		echo "</tr>";

		break;
		case "glpi_device_power" :
			echo "<tr><td>".$lang["device_power"][0].":</td><td>";
		autocompletionTextField("power",$table,"power",$device->fields["power"],20);
		echo "</td></tr>";
		echo "<tr><td>".$lang["device_power"][1].":</td>";
		echo "<td>".$lang["choice"][1]."<input type='radio' name='atx' value=\"Y\" ";
		if(strcmp($device->fields["atx"],"Y") == 0) echo "checked='checked'";
		echo "></td>";
		echo "<td>".$lang["choice"][0]."<input type='radio' name='atx' value=\"N\" ";
		if(strcmp($device->fields["atx"],"N") == 0) echo "checked='checked'";
		echo "></td>";
		echo "</tr>";

		break;	

	}
	echo "</table>";
	echo "</td>\n";	
	echo "</tr>";

	echo "<tr>";
	echo "<td class='tab_bg_1' valign='top' colspan='2'>";

	// table commentaires
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'><tr><td valign='top'>";
	echo $lang["common"][25].":	</td>";
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
		echo "<td class='tab_bg_2' valign='top' align='center'>\n";
		echo "<div align='center'>";
		echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
		echo "</div>";
		echo "</td>";
		echo "</tr>";
	}
	else {
		echo "<td class='tab_bg_2' valign='top' align='center' colspan='2'>";
		echo "<input type='hidden' name='device_type' value=\"$device_type\">\n";
		echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
		echo "</td>";
	}
	echo "</table></form></div>";
}

?>
