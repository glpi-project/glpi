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

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

/** Get device table based on device type
*@param $dev_type device type
*@return table name string
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

/** Get device specifity label based on device type
*@param $dev_type device type
*@return specifity label string
*/
function getDeviceSpecifityLabel($dev_type){
	global $LANG;
	switch ($dev_type){
		case MOBOARD_DEVICE :
			return "";
			break;
		case PROCESSOR_DEVICE :
			return $LANG["device_ram"][1];
			break;
		case RAM_DEVICE :
			return  $LANG["device_ram"][2];
			break;
		case HDD_DEVICE :
			return $LANG["device_hdd"][4];
			break;
		case NETWORK_DEVICE :
			return $LANG["device_iface"][2];
			break;
		case DRIVE_DEVICE :
			return "";
			break;
		case CONTROL_DEVICE :
			return "";
			break;
		case GFX_DEVICE :
			//return "";
			return  $LANG["device_gfxcard"][0];
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


/**
 * Get device type name based on device type
 * 
 * @param $device_num device type
 * @return if $device_num == -1 return array of names else return device name
 **/
function getDictDeviceLabel($device_num=-1) {

	global $LANG;
	$dp=array();
	$dp[MOBOARD_DEVICE]=$LANG["devices"][5];	
	$dp[PROCESSOR_DEVICE]=$LANG["devices"][4];
	$dp[NETWORK_DEVICE]=$LANG["devices"][3];
	$dp[RAM_DEVICE]=$LANG["devices"][6];	
	$dp[HDD_DEVICE]=$LANG["devices"][1];	
	$dp[DRIVE_DEVICE]=$LANG["devices"][19];		
	$dp[CONTROL_DEVICE]=$LANG["devices"][20];		
	$dp[GFX_DEVICE]=$LANG["devices"][2];		
	$dp[SND_DEVICE]=$LANG["devices"][7];		
	$dp[PCI_DEVICE]=$LANG["devices"][21];		
	$dp[CASE_DEVICE]=$LANG["devices"][22];		
	$dp[POWER_DEVICE]=$LANG["devices"][23];
	if ($device_num==-1)
		return $dp;
	else return $dp[$device_num];
}

/** print form/tab for a device linked to a computer
*@param $device device object
*@param $quantity quantity of device
*@param $specif specificity value
*@param $compID computer ID
*@param $compDevID computer device ID
*@param $withtemplate template or basic computer
*/
function printDeviceComputer($device,$quantity,$specif,$compID,$compDevID,$withtemplate='') {
	global $LANG,$CFG_GLPI;

	if (!haveRight("computer","r")) return false;
	$canedit=haveRight("computer","w");

	//print the good form switch the wanted device type.
	$entry=array();
	$type="";
	$name="";
	$specificity_label = getDeviceSpecifityLabel($device->devtype);
	switch($device->devtype) {
		case HDD_DEVICE :
			$type=$LANG["devices"][1];
			$name=$device->fields["designation"];
			if (!empty($device->fields["rpm"]))	$entry[$LANG["device_hdd"][0]]=$device->fields["rpm"];
			if ($device->fields["interface"])	$entry[$LANG["common"][65]]=getDropdownName("glpi_dropdown_interface",$device->fields["interface"]);
			if (!empty($device->fields["cache"])) $entry[$LANG["device_hdd"][1]]=$device->fields["cache"];

			$specificity_size = 10;
			break;
		case GFX_DEVICE :
			$type=$LANG["devices"][2];
			$name=$device->fields["designation"];
//			if (!empty($device->fields["ram"])) $entry[$LANG["device_gfxcard"][0]]=$device->fields["ram"];
//			if (!empty($device->fields["interface"])) 		$entry[$LANG["common"][65]]=getDropdownName("glpi_dropdown_interface",$device->fields["interface"]);

			$entry[$LANG["common"][65]]=$device->fields["interface"];
			$specificity_size = 10;
			break;
		case NETWORK_DEVICE :
			$type=$LANG["devices"][3];
			$name=$device->fields["designation"];
			if (!empty($device->fields["bandwidth"])) $entry[$LANG["device_iface"][0]]=$device->fields["bandwidth"];

			$specificity_size = 18;
			break;
		case MOBOARD_DEVICE :
			$type=$LANG["devices"][5];
			$name=$device->fields["designation"];
			if (!empty($device->fields["chipset"])) $entry[$LANG["device_moboard"][0]]=$device->fields["chipset"];

			$specificity_size = 10;
			break;
		case PROCESSOR_DEVICE :
			$type=$LANG["devices"][4];
			$name=$device->fields["designation"];
			if (!empty($device->fields["frequence"])) $entry[$LANG["device_ram"][1]]=$device->fields["frequence"];

			$specificity_size = 10;
			break;
		case RAM_DEVICE :
			$type=$LANG["devices"][6];
			$name=$device->fields["designation"];
			if (!empty($device->fields["type"])) $entry[$LANG["common"][17]]=getDropdownName("glpi_dropdown_ram_type",$device->fields["type"]);
			if (!empty($device->fields["frequence"])) $entry[$LANG["device_ram"][1]]=$device->fields["frequence"];

			$specificity_size = 10;
			break;
		case SND_DEVICE :

			$type=$LANG["devices"][7];
			$name=$device->fields["designation"];
			if (!empty($device->fields["type"])) $entry[$LANG["common"][17]]=$device->fields["type"];

			$specificity_size = 10;
			break;
		case DRIVE_DEVICE : 
			$type=$LANG["devices"][19];
			$name=$device->fields["designation"];
			if ($device->fields["is_writer"]) $entry[$LANG["device_drive"][0]]=getYesNo($device->fields["is_writer"]);
			if (!empty($device->fields["speed"])) $entry[$LANG["device_drive"][1]]=$device->fields["speed"];
			if (!empty($device->fields["frequence"])) $entry[$LANG["common"][65]]=$device->fields["frequence"];
			break;
		case CONTROL_DEVICE :
			$type=$LANG["devices"][20];
			$name=$device->fields["designation"];
			if ($device->fields["raid"]) $entry[$LANG["device_control"][0]]=getYesNo($device->fields["raid"]);
			if ($device->fields["interface"]) $entry[$LANG["common"][65]]=getDropdownName("glpi_dropdown_interface",$device->fields["interface"]);

			break;
		case PCI_DEVICE :
			$type=$LANG["devices"][21];
			$name=$device->fields["designation"];

			break;
		case POWER_DEVICE :
			$type=$LANG["devices"][23];
			$name=$device->fields["designation"];
			if (!empty($device->fields["power"])) $entry[$LANG["device_power"][0]]=$device->fields["power"];
			if ($device->fields["atx"]) $entry[$LANG["device_power"][1]]=getYesNo($device->fields["atx"]);

			break;
		case CASE_DEVICE :
			$type=$LANG["devices"][22];
			$name=$device->fields["designation"];
			if (!empty($device->fields["type"])) {
				$entry[$LANG["device_case"][0]]=getDropdownName("glpi_dropdown_case_type",$device->fields["type"]);
			}

			break;
	}

	echo "<tr class='tab_bg_2'>";
	echo "<td class='center'>";
	echo "<select name='quantity_$compDevID'>";
	for ($i=0;$i<100;$i++)
		echo "<option value='$i' ".($quantity==$i?"selected":"").">".$i."x</option>";
	echo "</select>";
	echo "</td>";

	if (haveRight("device","w")) {
		echo "<td class='center'><a href='".$CFG_GLPI["root_doc"]."/front/device.php?device_type=".$device->devtype."'>$type</a></td>";
		echo "<td class='center'><a href='".$CFG_GLPI["root_doc"]."/front/device.form.php?ID=".$device->fields['ID']."&amp;device_type=".$device->devtype."'>&nbsp;$name&nbsp;".($CFG_GLPI["view_ID"]?" (".$device->fields['ID'].")":"")."</a></td>";
	}  else {
		echo "<td class='center'>$type</td>";
		echo "<td class='center'>&nbsp;$name&nbsp;".($CFG_GLPI["view_ID"]?" (".$device->fields['ID'].")":"")."</td>";
	}

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

		//Mise a jour des sp�ificit�s
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



/**  Update an internal device specificity
* @param $newValue new specifity value
* @param $compDevID computer device ID
* @param $strict update based on ID
* @param $checkcoherence check coherence of new value before updating : do not update if it is not coherent
*/
function update_device_specif($newValue,$compDevID,$strict=false,$checkcoherence=false) {

	// Check old value for history 
	global $DB;
	
		
	$query ="SELECT * FROM glpi_computer_device WHERE ID = '".$compDevID."'";

	if ($result = $DB->query($query)) {
		if ($DB->numrows($result)){
			$data = addslashes_deep($DB->fetch_array($result));
			cleanAllItemCache("device_".$data["FK_computers"],"GLPI_".COMPUTER_TYPE);

			if ($checkcoherence){
				switch ($data["device_type"]){
					case PROCESSOR_DEVICE :
						//Prevent division by O error if newValue is null or doesn't contains any value
						if ($newValue == null || $newValue=='')
							return false;

						//Calculate pourcent change of frequency
						$pourcent =  ( $newValue / ($data["specificity"] / 100) ) - 100;
						
						//If new processor speed value is superior to the old one, and if the change is at least 5% change 
						if ($data["specificity"] < $newValue && $pourcent > 4)
							$condition = true;
						else
							$condition = false;
						break;	
					case GFX_DEVICE :
						//If memory has changed and his new value is not 0
						if ($data["specificity"] != $newValue && $newValue > 0)
							$condition = true;
						else
							$condition = false;		
						break;		 			
					default :
						if ($data["specificity"] != $newValue)
							$condition = true;
						else
							$condition = false;		
						break;		 			
				}
			}
			else
			{
				if ($data["specificity"] != $newValue)
					$condition = true;
				else
					$condition = false;		
			}
			
			// Is it a real change ? 
			if( $condition){
				
				// Update specificity 
				$WHERE=" WHERE FK_device = '".$data["FK_device"]."' AND FK_computers = '".$data["FK_computers"]."' AND device_type = '".$data["device_type"]."'  AND specificity='".$data["specificity"]."'";
				if ($strict) $WHERE=" WHERE ID='$compDevID'";
				
				$query2 = "UPDATE glpi_computer_device SET specificity = '".$newValue."' $WHERE";
				if($DB->query($query2)){

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

}

/**  Update an internal device quantity
* @param $newNumber new quantity value
* @param $compDevID computer device ID
*/
function update_device_quantity($newNumber,$compDevID){
	// Check old value for history 
	global $DB;
	$query ="SELECT * FROM glpi_computer_device WHERE ID = '".$compDevID."'";
	if ($result = $DB->query($query)) {
		$data = addslashes_deep($DB->fetch_array($result));

		$query2 = "SELECT ID FROM glpi_computer_device WHERE FK_device = '".$data["FK_device"]."' AND FK_computers = '".$data["FK_computers"]."' AND device_type = '".$data["device_type"]."' AND specificity='".$data["specificity"]."'";
		if ($result2 = $DB->query($query2)) {

			// Delete devices
			$number=$DB->numrows($result2);
			if ($number>$newNumber){
				for ($i=$newNumber;$i<$number;$i++){
					$data2 = $DB->fetch_array($result2);
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
	global $DB;
	$query ="SELECT * FROM glpi_computer_device WHERE ID = '".$compDevID."'";
	if ($result = $DB->query($query)) {
		$data = $DB->fetch_array($result);
	} 

	cleanAllItemCache("device_".$data["FK_computers"],"GLPI_".COMPUTER_TYPE);

	$query2 = "DELETE FROM glpi_computer_device where ID = '".$compDevID."'";
	if($DB->query($query2)){
		
		if ($dohistory){
			$device = new Device($data["device_type"]);
			if ($device->getFromDB($data["FK_device"])){
				$changes[0]='0';
				$changes[1]=addslashes($device->fields["designation"]);
				$changes[2]="";
				// history log
				historyLog ($data["FK_computers"],COMPUTER_TYPE,$changes,$data["device_type"],HISTORY_DELETE_DEVICE);
			}
		}

		return true;
	}else{ return false;}

}

/**
 * Link the device to the computer
 * 
 * @param $cID Computer ID
 * @param $device_type device type
 * @param $dID device ID
 * @param $specificity specificity value
 * @param $dohistory do history log
 * @returns new computer device ID
 **/
function compdevice_add($cID,$device_type,$dID,$specificity='',$dohistory=1) {
	$device = new Device($device_type);
	$device->getFromDB($dID);
	if (empty($specificity)) $specificity=$device->fields['specif_default'];
	$newID=$device->computer_link($cID,$device_type,$specificity);
	cleanAllItemCache("device_".$cID,"GLPI_".COMPUTER_TYPE);
	if ($dohistory){
		$changes[0]='0';
		$changes[1]="";
		$changes[2]=addslashes($device->fields["designation"]);
		// history log
		historyLog ($cID,COMPUTER_TYPE,$changes,$device_type,HISTORY_ADD_DEVICE);
	}
	return $newID;
}

/**
 * Show Device list of a defined type
 * 
 * @param $device_type device type
 * @param $target wher to go on action
 **/
function showDevicesList($device_type,$target) {

	// Lists Device from a device_type

	global $DB,$CFG_GLPI, $LANG;


	$query = "select device.ID, device.designation, glpi_dropdown_manufacturer.name as manufacturer FROM ".getDeviceTable($device_type)." as device ";
	$query.= " LEFT JOIN glpi_dropdown_manufacturer ON (glpi_dropdown_manufacturer.ID = device.FK_glpi_enterprise ) ";
	$query .= " ORDER by device.designation ASC";
	
	// Get it from database	
	if ($result = $DB->query($query)) {
		$numrows = $DB->numrows($result);
		$numrows_limit = $numrows;
		$result_limit = $result;
		if ($numrows_limit>0) {
			// Produce headline
			echo "<div class='center'><table class='tab_cadre'><tr>";

			// designation
			echo "<th>";
			echo $LANG["common"][16]."</th>";

			// Manufacturer		
			echo "<th>";
			echo $LANG["common"][5]."</th>";

			echo "</tr>";

			while ($data=$DB->fetch_array($result)) {
				$ID = $data["ID"];
				echo "<tr class='tab_bg_2'>";
				echo "<td><strong>";
				echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/device.form.php?ID=$ID&amp;device_type=$device_type\">";
				echo $data["designation"];
				if ($CFG_GLPI["view_ID"]) echo " (".$data["ID"].")";
				echo "</a></strong></td>";
				echo "<td>". $data["manufacturer"]."</td>";
				echo "</tr>";
			}

			// Close Table
			echo "</table></div>";
		} else {
			echo "<div class='center'><strong>".$LANG["devices"][18]."</strong></div>";
		}
	}
}

/**
 * title for Devices
 * 
 * @param $device_type device type
 **/
function titleDevices($device_type){
	global  $LANG,$CFG_GLPI;

	displayTitle($CFG_GLPI["root_doc"]."/pics/periph.png",$LANG["devices"][12],"",array("device.form.php?device_type=$device_type"=>$LANG["devices"][12]));

}


/**
 * Show Device Form
 * 
 * @param $device_type device type
 * @param $ID device ID
 * @param $target where to go on action
 **/
function showDevicesForm ($target,$ID,$device_type) {

	global $CFG_GLPI,$LANG,$REFERER;

	$device = new Device($device_type);

	$device_spotted = false;

	if(empty($ID)) {
		if($device->getEmpty()) $device_spotted = true;
	} else {
		if($device->getFromDB($ID)) $device_spotted = true;
	}
	
	if ($device_spotted){

		$table=getDeviceTable($device_type);
	
		echo "<div class='center'>";
		echo "<a href='$REFERER'>".$LANG["buttons"][13]."</a>";

		$device->showOnglets($ID, "",$_SESSION['glpi_tab'],"","designation","&amp;device_type=$device_type&amp;referer=$REFERER");
		echo "<form method='post' name='form' action=\"$target\">";
		echo "<input type='hidden' name='referer' value='$REFERER'>";
		echo "<table class='tab_cadre_fixe' cellpadding='2'>";
		echo "<tr><th align='center' colspan='1'>";
		echo getDictDeviceLabel($device_type)."</th><th align='center' colspan='1'> ID : ".$ID;
		echo "<tr><td class='tab_bg_1' colspan='1'>";
		// table commune
		echo "<table cellpadding='1' cellspacing='0' border='0'>\n";
		echo "<tr><td>".$LANG["common"][16].":	</td>";
		echo "<td>";
		autocompletionTextField("designation",$table,"designation",$device->fields["designation"],50);
	
		echo "</td></tr>";
		echo "<tr class='tab_bg_1'><td>".$LANG["common"][5].": 	</td><td colspan='2'>";
		dropdownValue("glpi_dropdown_manufacturer","FK_glpi_enterprise",$device->fields["FK_glpi_enterprise"]);
		echo "</td></tr>";
		if (getDeviceSpecifityLabel($device_type)!=""){
			echo "<tr><td>".getDeviceSpecifityLabel($device_type)." ".$LANG["devices"][24]."</td>";
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
				echo "<tr><td>".$LANG["device_moboard"][0].":</td>";
			echo "<td>";
			autocompletionTextField("chipset",$table,"chipset",$device->fields["chipset"],40);
	
			echo "</td></tr>";
			break;
			case "glpi_device_processor" :
				echo "<tr><td>".$LANG["device_ram"][1].":</td><td>";
			autocompletionTextField("frequence",$table,"frequence",$device->fields["frequence"],40);
			echo "</td></tr>";
			break;
			case "glpi_device_ram" :
				echo "<tr><td>".$LANG["common"][17].":</td>";
			echo "<td>";
			dropdownValue("glpi_dropdown_ram_type","type",$device->fields["type"]);
			echo "</td>";
			echo "</tr>";
			echo "<tr><td>".$LANG["device_ram"][1].":</td><td>";
			autocompletionTextField("frequence",$table,"frequence",$device->fields["frequence"],40);
			echo "</td></tr>";
			break;
			case "glpi_device_hdd" :
				echo "<tr><td>".$LANG["device_hdd"][0].":</td><td>";
			autocompletionTextField("rpm",$table,"rpm",$device->fields["rpm"],40);
	
			echo "</td></tr>";
			echo "<tr><td>".$LANG["device_hdd"][1].":</td><td>";
			autocompletionTextField("cache",$table,"cache",$device->fields["cache"],40);
			echo "</td></tr>";
	
	
			echo "<tr><td>".$LANG["common"][65].":</td>";
			echo "<td>";
			dropdownValue("glpi_dropdown_interface","interface",$device->fields["interface"]);
			echo "</td>";
	
			echo "</tr>";
			break;
			case "glpi_device_iface" :
				echo "<tr><td>".$LANG["device_iface"][0].":</td><td>";
			autocompletionTextField("bandwidth",$table,"bandwidth",$device->fields["bandwidth"],40);
			echo "</td></tr>";
			break;
			case "glpi_device_drive" :
				echo "</tr>";
			echo "<tr><td>".$LANG["device_drive"][0].":</td>";
			echo "<td>";
			dropdownYesNo("is_writer",$device->fields["is_writer"]);
			echo "</td>";
			echo "</tr>";
			echo "<tr><td>".$LANG["common"][65].":</td>";
			echo "<td>";
	
			dropdownValue("glpi_dropdown_interface","interface",$device->fields["interface"]);
	
			echo "</td>";
			echo "</tr>";
			echo "<tr><td>".$LANG["device_drive"][1].":</td><td>";
			autocompletionTextField("speed",$table,"speed",$device->fields["speed"],40);
			echo "</td></tr>";
	
	
			break;
			case  "glpi_device_control" :
				echo "</tr>";
			echo "<tr><td>".$LANG["device_control"][0].":</td>";
			echo "<td>";
			dropdownYesNo("raid",$device->fields["raid"]);
			echo "</td>";
			echo "</tr>";
			echo "<tr><td>".$LANG["common"][65].":</td>";
			echo "<td>";
			dropdownValue("glpi_dropdown_interface","interface",$device->fields["interface"]);
			echo "</td>";
			echo "</tr>";
	
			break;
			case "glpi_device_gfxcard" :
				echo "<tr><td>".$LANG["device_gfxcard"][0].":</td><td>";
			autocompletionTextField("specif_default",$table,"specif_default",$device->fields["specif_default"],40);
			echo "</td></tr>";
			echo "<tr><td>".$LANG["common"][65].":</td>";
			echo "<td><select name='interface'>";
			echo "<option value='AGP' ".($device->fields["interface"]=="AGP"?"selected":"").">AGP</option>";
			echo "<option value='PCI' ".($device->fields["interface"]=="PCI"?"selected":"").">PCI</option>";
			echo "<option value='PCIe' ".($device->fields["interface"]=="PCIe"?"selected":"").">PCIe</option>"; 
			echo "<option value='PCI-X' ".($device->fields["interface"]=="PCI-X"?"selected":"").">PCI-X</option>";
			echo "<option value='Other' ".($device->fields["interface"]=="Other"?"selected":"").">Other</option>";
			echo "</select>";
			echo "</td>";
			echo "</tr>";
			break;
			case "glpi_device_sndcard" :
				echo "<tr><td>".$LANG["common"][17].":</td><td>";
			autocompletionTextField("type",$table,"type",$device->fields["type"],40);
			echo "</td></tr>";
			break;
			case "glpi_device_pci" :
				break;
			case "glpi_device_case" :
				echo "<tr><td>".$LANG["device_case"][0].":</td>";
			echo "<td>";
			dropdownValue("glpi_dropdown_case_type","type",$device->fields["type"]);
			echo "</td>";
			echo "</tr>";
	
			break;
			case "glpi_device_power" :
				echo "<tr><td>".$LANG["device_power"][0].":</td><td>";
			autocompletionTextField("power",$table,"power",$device->fields["power"],40);
			echo "</td></tr>";
			echo "<tr><td>".$LANG["device_power"][1].":</td>";
			echo "<td>";
			dropdownYesNo("atx",$device->fields["atx"]);
			echo "</td>";
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
		echo $LANG["common"][25].":	</td>";
		echo "<td class='center'><textarea cols='80' rows='4' name='comment' >".$device->fields["comment"]."</textarea>";
		echo "</td></tr></table>";
	
		echo "</td>";
		echo "</tr>";
		echo "<tr>";
		if(!empty($ID)) {
			echo "<td class='tab_bg_2' valign='top' align='center'>";
			echo "<input type='hidden' name='ID' value=\"$ID\">\n";
			echo "<input type='hidden' name='device_type' value=\"$device_type\">\n";
			echo "<input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit'>";
			echo "</td>";
			echo "<td class='tab_bg_2' valign='top' align='center'>\n";
			echo "<div class='center'>";
			echo "<input type='submit' name='delete' value=\"".$LANG["buttons"][6]."\" class='submit'>";
			echo "</div>";
			echo "</td>";
			echo "</tr>";
		}
		else {
			echo "<td class='tab_bg_2' valign='top' align='center' colspan='2'>";
			echo "<input type='hidden' name='device_type' value=\"$device_type\">\n";
			echo "<input type='submit' name='add' value=\"".$LANG["buttons"][8]."\" class='submit'>";
			echo "</td>";
		}
		echo "</table></form></div>";
	} else {
		echo "<div class='center'><strong>".$LANG["common"][54]."</strong></div>";
		return false;
	}	
}

?>
