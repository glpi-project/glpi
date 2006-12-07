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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

function ocsShowNewComputer($check,$start,$tolinked=0){
	global $db,$dbocs,$lang,$HTMLRel,$cfg_glpi;

	if (!haveRight("ocsng","w")) return false;

	$cfg_ocs=getOcsConf(1);

	$WHERE="";
	if (!empty($cfg_ocs["tag_limit"])){
		$splitter=explode("$",$cfg_ocs["tag_limit"]);
		if (count($splitter)){
			$WHERE="WHERE TAG='".$splitter[0]."' ";
			for ($i=1;$i<count($splitter);$i++)
				$WHERE.=" OR TAG='".$splitter[$i]."' ";
		}
	}

	$query_ocs = "SELECT hardware.*, accountinfo.TAG AS TAG 
		FROM hardware 
		INNER JOIN accountinfo ON (hardware.ID = accountinfo.HARDWARE_ID) 
		$WHERE 
		ORDER BY hardware.NAME";
	$result_ocs = $dbocs->query($query_ocs);

	// Existing OCS - GLPI link
	$query_glpi = "SELECT * 
		FROM glpi_ocs_link";
	$result_glpi = $db->query($query_glpi);

	// Computers existing in GLPI
	$query_glpi_comp = "SELECT ID,name 
		FROM glpi_computers 
		WHERE deleted = 'N' AND is_template='0'";
	$result_glpi_comp = $db->query($query_glpi_comp);

	if ($dbocs->numrows($result_ocs)>0){

		// Get all hardware from OCS DB
		$hardware=array();
		while($data=$dbocs->fetch_array($result_ocs)){
			$data=clean_cross_side_scripting_deep(addslashes_deep($data));
			$hardware[$data["ID"]]["date"]=$data["LASTDATE"];
			$hardware[$data["ID"]]["name"]=$data["NAME"];
			$hardware[$data["ID"]]["TAG"]=$data["TAG"];
			$hardware[$data["ID"]]["ID"]=$data["ID"];
		}

		// Get all links between glpi and OCS
		$already_linked=array();
		if ($db->numrows($result_glpi)>0){
			while($data=$dbocs->fetch_array($result_glpi)){
				$already_linked[$data["ocs_id"]]=$data["last_update"];
			}
		}

		// Get all existing computers name in GLPI
		$computer_names=array();
		if ($db->numrows($result_glpi_comp)>0){
			while($data=$dbocs->fetch_array($result_glpi_comp)){
				$computer_names[$data["name"]]=$data["ID"];
			}
		}

		// Clean $hardware from already linked element
		if (count($already_linked)>0){
			foreach ($already_linked as $ID => $date){
				if (isset($hardware[$ID])&&isset($already_linked[$ID]))
					unset($hardware[$ID]);
			}
		}




		if ($tolinked&&count($hardware)){
			echo "<div align='center'><strong>".$lang["ocsng"][22]."</strong></div>";
		}

		echo "<div align='center'>";
		if (($numrows=count($hardware))>0){

			$parameters="check=$check";
			printPager($start,$numrows,$_SERVER['PHP_SELF'],$parameters);

			// delete end 
			array_splice($hardware,$start+$cfg_glpi["list_limit"]);
			// delete begin
			if ($start>0)
				array_splice($hardware,0,$start);
			echo "<strong>".$lang["ocsconfig"][18]."</strong><br>";
			echo "<form method='post' name='ocsng_form' id='ocsng_form' action='".$_SERVER['PHP_SELF']."'>";
			if ($tolinked==0)
				echo "<a href='".$_SERVER['PHP_SELF']."?check=all&amp;start=$start' onclick= \"if ( markAllRows('ocsng_form') ) return false;\">".$lang["buttons"][18]."</a>&nbsp;/&nbsp;<a href='".$_SERVER['PHP_SELF']."?check=none&amp;start=$start' onclick= \"if ( unMarkAllRows('ocsng_form') ) return false;\">".$lang["buttons"][19]."</a>";


			echo "<table class='tab_cadre'>";
			echo "<tr><th>".$lang["ocsng"][5]."</th><th>".$lang["common"][27]."</th><th>TAG</th><th>&nbsp;</th></tr>";

			echo "<tr class='tab_bg_1'><td colspan='4' align='center'>";
			echo "<input class='submit' type='submit' name='import_ok' value='".$lang["buttons"][37]."'>";
			echo "</td></tr>";


			foreach ($hardware as $ID => $tab){
				echo "<tr class='tab_bg_2'><td>".$tab["name"]."</td><td>".convDateTime($tab["date"])."</td><td>".$tab["TAG"]."</td><td>";

				if ($tolinked==0)
					echo "<input type='checkbox' name='toimport[".$tab["ID"]."]' ".($check=="all"?"checked":"").">";
				else {
					if (isset($computer_names[$tab["name"]]))
						dropdownValue("glpi_computers","tolink[".$tab["ID"]."]",$computer_names[$tab["name"]]);
					else
						dropdown("glpi_computers","tolink[".$tab["ID"]."]");
				}
				echo "</td></tr>";

			}
			echo "<tr class='tab_bg_1'><td colspan='4' align='center'>";
			echo "<input class='submit' type='submit' name='import_ok' value='".$lang["buttons"][37]."'>";
			echo "</td></tr>";
			echo "</table>";
			echo "</form>";

			printPager($start,$numrows,$_SERVER['PHP_SELF'],$parameters);

		} else echo "<strong>".$lang["ocsng"][9]."</strong>";

		echo "</div>";

	} else echo "<div align='center'><strong>".$lang["ocsng"][9]."</strong></div>";
}

/**
 * Make the item link between glpi and ocs.
 *
 * This make the database link between ocs and glpi databases
 *
 *@param $ocs_item_id integer : ocs item unique id.
 *@param $glpi_computer_id integer : glpi computer id
 *
 *@return integer : link id.
 *
 **/
function ocsLink($ocs_id,$glpi_computer_id) {
	global $db,$dbocs;

	// Need to get device id due to ocs bug on duplicates
	$query_ocs="SELECT * 
		FROM hardware
		WHERE ID = '$ocs_id';";
	$result_ocs=$dbocs->query($query_ocs);
	$data=$dbocs->fetch_array($result_ocs);

	$query = "INSERT INTO glpi_ocs_link 
		(glpi_id,ocs_id,ocs_deviceid,last_update) 
		VALUES ('".$glpi_computer_id."','".$ocs_id."','".$data["DEVICEID"]."',NOW())";
	$result=$db->query($query);

	if ($result)
		return ($db->insert_id());
	else return false;
}


function ocsManageDeleted(){
	global $db,$dbocs;

	// Activate TRACE_DELETED : ALSO DONE IN THE CONFIG
	$query = "UPDATE config SET IVALUE='1' WHERE NAME='TRACE_DELETED'";
	$dbocs->query($query);


	$query="SELECT * FROM deleted_equiv";
	$result = $dbocs->query($query);
	if ($dbocs->numrows($result)){
		$deleted=array();
		while ($data=$dbocs->fetch_array($result)){
			$deleted[$data["DELETED"]]=$data["EQUIVALENT"];
		}

		$query="TRUNCATE TABLE deleted_equiv";
		$result = $dbocs->query($query);

		if (count($deleted))
			foreach ($deleted as $del => $equiv){
				if (!empty($equiv)){ // New name
					// Get hardware due to bug of duplicates management of OCS
					if (ereg("-",$equiv)){
						$query_ocs="SELECT * 
							FROM hardware 
							WHERE DEVICEID='$equiv'";
						$result_ocs=$dbocs->query($query_ocs);
						if ($data=$dbocs->fetch_array($result_ocs));

						$query="UPDATE glpi_ocs_link 
							SET ocs_id='".$data["ID"]."', ocs_deviceid='".$data["DEVICEID"]."' 
							WHERE ocs_deviceid='$del'";
						$db->query($query);

					} else {
						$query_ocs="SELECT * 
							FROM hardware 
							WHERE ID='$equiv'";

						$result_ocs=$dbocs->query($query_ocs);
						if ($data=$dbocs->fetch_array($result_ocs));

						$query="UPDATE glpi_ocs_link 
							SET ocs_id='".$data["ID"]."', ocs_deviceid='".$data["DEVICEID"]."' 
							WHERE ocs_id='$del'";
						$db->query($query);

					}
				} else { // Deleted
					if (ereg("-",$del))
						$query="SELECT * 
							FROM glpi_ocs_link 
							WHERE ocs_deviceid='$del'";
					else $query="SELECT * 
						FROM glpi_ocs_link 
							WHERE ocs_id='$del'";
					$result=$db->query($query);
					if ($db->numrows($result)){
						$del=$db->fetch_array($result);
						$comp=new Computer();
						$comp->delete(array("ID"=>$del["glpi_id"]),0);
					}
				}
			}
	}
}


function ocsImportComputer($ocs_id){
	global $dbocs;

	// Set OCS checksum to max value
	$query = "UPDATE hardware SET CHECKSUM='".MAX_OCS_CHECKSUM."' WHERE ID='$ocs_id'";
	$dbocs->query($query);

	$query = "SELECT * FROM hardware WHERE ID='$ocs_id'";
	$result = $dbocs->query($query);
	$comp = new Computer;
	if ($result&&$dbocs->numrows($result)==1){
		$line=$dbocs->fetch_array($result);
		$line=clean_cross_side_scripting_deep(addslashes_deep($line));
		$dbocs->close();

		$comp->fields["name"] = $line["NAME"];
		$comp->fields["ocs_import"] = 1;
		$glpi_id=$comp->addToDB();
		if ($glpi_id){
			$cfg_ocs=getOcsConf(1);
			if ($cfg_ocs["default_state"]){
				updateState(COMPUTER_TYPE,$glpi_id,$cfg_ocs["default_state"],0,0);
			}
			ocsImportTag($line['ID'],$glpi_id,$cfg_ocs);
		}

		if ($idlink = ocsLink($line['ID'], $glpi_id)){
			ocsUpdateComputer($idlink,0);
		}
	}
}

function ocsImportTag($ocs_id,$glpi_id,$cfg_ocs){
	global $dbocs;
	// Import TAG
	if (!empty($cfg_ocs["import_tag_field"])){
		$query = "SELECT TAG 
			FROM accountinfo 
			WHERE HARDWARE_ID='$ocs_id'";

		$resultocs=$dbocs->query($query);
		if ($resultocs&&$dbocs->numrows($resultocs)>0){
			$tag=addslashes($dbocs->result($resultocs,0,0));
			if (!empty($tag)){
				$comp=new Computer();
				$input["ID"] = $glpi_id;
				switch ($cfg_ocs["import_tag_field"]){
					case "otherserial":
						case "contact_num":
						$input[$cfg_ocs["import_tag_field"]]=$tag;
					break;
					case "location":
						$input[$cfg_ocs["import_tag_field"]]=ocsImportDropdown('glpi_dropdown_locations','name',$tag);;
					break;
					case "network":
						$input[$cfg_ocs["import_tag_field"]]=ocsImportDropdown('glpi_dropdown_network','name',$tag);;
					break;
				}
				$comp->update($input,0);
			}
		}
	}
}

function ocsLinkComputer($ocs_id,$glpi_id){
	global $db,$dbocs,$lang;

	$query="SELECT * 
		FROM glpi_ocs_link 
		WHERE glpi_id='$glpi_id'";

	$result=$db->query($query);
	$ocs_exists=true;
	$numrows=$db->numrows($result);
	// Already link - check if the OCS computer already exists
	if ($numrows>0){
		$data=$db->fetch_assoc($result);
		$query = "SELECT * 
			FROM hardware 
			WHERE ID='".$data["ocs_id"]."'";
		$result_ocs=$dbocs->query($query);
		// Not found
		if ($dbocs->numrows($result_ocs)==0){
			$ocs_exists=false;
			$query="DELETE FROM glpi_ocs_link 
				WHERE ID='".$data["ID"]."'";
			$db->query($query);
		}
	} 

	if (!$ocs_exists||$numrows==0){

		// Set OCS checksum to max value
		$query = "UPDATE hardware 
			SET CHECKSUM='".MAX_OCS_CHECKSUM."' 
			WHERE ID='$ocs_id'";
		$dbocs->query($query);

		if ($idlink = ocsLink($ocs_id, $glpi_id)){

			$comp=new Computer();
			$input["ID"] = $glpi_id;
			$input["ocs_import"] = 1;
			$comp->update($input);


			// Reset using GLPI Config
			$cfg_ocs=getOcsConf(1);
			$query = "SELECT * 
				FROM hardware 
				WHERE ID='$ocs_id'";
			$result = $dbocs->query($query);
			$line=$dbocs->fetch_array($result);

			ocsImportTag($line["ID"],$glpi_id,$cfg_ocs);

			if($cfg_ocs["import_general_os"]) 
				ocsResetDropdown($glpi_id,"os","glpi_dropdown_os");
			if($cfg_ocs["import_device_processor"]) 
				ocsResetDevices($glpi_id,PROCESSOR_DEVICE);
			if($cfg_ocs["import_device_iface"]) 
				ocsResetDevices($glpi_id,NETWORK_DEVICE);
			if($cfg_ocs["import_device_memory"]) 
				ocsResetDevices($glpi_id,RAM_DEVICE);
			if($cfg_ocs["import_device_hdd"]) 
				ocsResetDevices($glpi_id,HDD_DEVICE);
			if($cfg_ocs["import_device_sound"]) 
				ocsResetDevices($glpi_id,SND_DEVICE);
			if($cfg_ocs["import_device_gfxcard"]) 
				ocsResetDevices($glpi_id,GFX_DEVICE);
			if($cfg_ocs["import_device_drives"]) 
				ocsResetDevices($glpi_id,DRIVE_DEVICE);
			if($cfg_ocs["import_device_modems"] || $cfg_ocs["import_device_ports"]) 
				ocsResetDevices($glpi_id,PCI_DEVICE);
			if($cfg_ocs["import_software"]) 
				ocsResetLicenses($glpi_id);
			if($cfg_ocs["import_periph"]) 
				ocsResetPeriphs($glpi_id);
			if($cfg_ocs["import_monitor"]==1) // Only reset monitor as global in unit management try to link monitor with existing
				ocsResetMonitors($glpi_id);
			if($cfg_ocs["import_printer"]) 
				ocsResetPrinters($glpi_id);

			ocsUpdateComputer($idlink,0);
		}
	} else {
		$_SESSION["MESSAGE_AFTER_REDIRECT"]= $ocs_id." - ".$lang["ocsng"][23];
	}

}


function ocsUpdateComputer($ID,$dohistory,$force=0){

	global $db,$dbocs;

	$cfg_ocs=getOcsConf(1);

	$query="SELECT * 
		FROM glpi_ocs_link 
		WHERE ID='$ID'";
	$result=$db->query($query);

	if ($db->numrows($result)==1){
		$line=$db->fetch_assoc($result);
		// Get OCS ID 
		$query_ocs = "SELECT * 
			FROM hardware 
			WHERE ID='".$line['ocs_id']."'";
		$result_ocs = $dbocs->query($query_ocs);
		
		// Need do history to be 2 not to lock fields 
		if (dohistory){
			$dohistory=2;
		}
		if ($dbocs->numrows($result_ocs)==1){
			$data_ocs=$dbocs->fetch_array($result_ocs);
			if ($force){
				$ocs_checksum=MAX_OCS_CHECKSUM;
				$query_ocs="UPDATE hardware 
					SET CHECKSUM= (".MAX_OCS_CHECKSUM.") 
					WHERE ID='".$line['ocs_id']."'";
					$dbocs->query($query_ocs);
			}else 
				$ocs_checksum=$data_ocs["CHECKSUM"];



			$mixed_checksum=intval($ocs_checksum) &  intval($cfg_ocs["checksum"]);
			
/*			echo "OCS CS=".decbin($ocs_checksum)." - $ocs_checksum<br>";
			  echo "GLPI CS=".decbin($cfg_ocs["checksum"])." - ".$cfg_ocs["checksum"]."<br>";
			  echo "MIXED CS=".decbin($mixed_checksum)." - $mixed_checksum <br>";
 */			 	
			// Is an update to do ?
			if ($mixed_checksum){
				// Get updates on computers :
				$computer_updates=importArrayFromDB($line["computer_update"]);

				if ($mixed_checksum&pow(2,HARDWARE_FL))
					ocsUpdateHardware($line['glpi_id'],$line['ocs_id'],$cfg_ocs,$computer_updates,$dohistory);

				if ($mixed_checksum&pow(2,BIOS_FL))
					ocsUpdateBios($line['glpi_id'],$line['ocs_id'],$cfg_ocs,$computer_updates,$dohistory);

				// Get import devices
				$import_device=importArrayFromDB($line["import_device"]);
				if ($mixed_checksum&pow(2,MEMORIES_FL))
					ocsUpdateDevices(RAM_DEVICE,$line['glpi_id'],$line['ocs_id'],$cfg_ocs,$import_device,$dohistory);

				if ($mixed_checksum&pow(2,STORAGES_FL)){
					ocsUpdateDevices(HDD_DEVICE,$line['glpi_id'],$line['ocs_id'],$cfg_ocs,$import_device,$dohistory);
					ocsUpdateDevices(DRIVE_DEVICE,$line['glpi_id'],$line['ocs_id'],$cfg_ocs,$import_device,$dohistory);
				}

				if ($mixed_checksum&pow(2,HARDWARE_FL))
					ocsUpdateDevices(PROCESSOR_DEVICE,$line['glpi_id'],$line['ocs_id'],$cfg_ocs,$import_device,$dohistory);

				if ($mixed_checksum&pow(2,VIDEOS_FL))
					ocsUpdateDevices(GFX_DEVICE,$line['glpi_id'],$line['ocs_id'],$cfg_ocs,$import_device,$dohistory);

				if ($mixed_checksum&pow(2,SOUNDS_FL))
					ocsUpdateDevices(SND_DEVICE,$line['glpi_id'],$line['ocs_id'],$cfg_ocs,$import_device,$dohistory);

				if ($mixed_checksum&pow(2,NETWORKS_FL))
					ocsUpdateDevices(NETWORK_DEVICE,$line['glpi_id'],$line['ocs_id'],$cfg_ocs,$import_device,$dohistory);

				if ($mixed_checksum&pow(2,MODEMS_FL)||$mixed_checksum&pow(2,PORTS_FL))
					ocsUpdateDevices(PCI_DEVICE,$line['glpi_id'],$line['ocs_id'],$cfg_ocs,$import_device,$dohistory);

				if ($mixed_checksum&pow(2,MONITORS_FL)){
					// Get import monitors
					$import_monitor=importArrayFromDB($line["import_monitor"]);
					ocsUpdatePeripherals(MONITOR_TYPE,$line['glpi_id'],$line['ocs_id'],$cfg_ocs,$import_monitor,$dohistory);
				}

				if ($mixed_checksum&pow(2,PRINTERS_FL)){
					// Get import printers
					$import_printer=importArrayFromDB($line["import_printers"]);
					ocsUpdatePeripherals(PRINTER_TYPE,$line['glpi_id'],$line['ocs_id'],$cfg_ocs,$import_printer,$dohistory);
				}

				if ($mixed_checksum&pow(2,INPUTS_FL)){
					// Get import monitors
					$import_peripheral=importArrayFromDB($line["import_peripheral"]);
					ocsUpdatePeripherals(PERIPHERAL_TYPE,$line['glpi_id'],$line['ocs_id'],$cfg_ocs,$import_peripheral,$dohistory);
				}

				if ($mixed_checksum&pow(2,SOFTWARES_FL)){
					// Get import monitors
					$import_software=importArrayFromDB($line["import_software"]);
					ocsUpdateSoftware($line['glpi_id'],$line['ocs_id'],$cfg_ocs,$import_software,$dohistory);
				} 



				// Update OCS Cheksum 
				$query_ocs="UPDATE hardware 
					SET CHECKSUM= (CHECKSUM - $mixed_checksum) 
					WHERE ID='".$line['ocs_id']."'";
				$dbocs->query($query_ocs);
				// update last_update and and last_ocs_update
				$query = "UPDATE glpi_ocs_link 
					SET last_update=NOW(), last_ocs_update='".$data_ocs["LASTCOME"]."' 
					WHERE ID='$ID'";
				$db->query($query);
			}
		}
	}
}

/**
 * Get OCSNG mode configuration
 *
 * Get all config of the OCSNG mode
 *
 * @param $id int : ID of the OCS config (default value 1)
 *@return Value of $confVar fields or false if unfound.
 *
 **/
function getOcsConf($id) {
	global $db;
	$query = "SELECT * 
		FROM glpi_ocs_config 
		WHERE ID='$id'";
	$result = $db->query($query);
	if($result) return $db->fetch_assoc($result);
	else return 0;
}


/**
 * Update the computer hardware configuration
 *
 * Update the computer hardware configuration
 *
 *@param $ocs_id integer : glpi computer id
 *@param $glpi_id integer : ocs computer id.
 *@param $cfg_ocs array : ocs config
 *@param $computer_updates array : already updated fields of the computer
 *@param $dohistory log updates on history ?
 *
 *@return nothing.
 *
 **/
function ocsUpdateHardware($glpi_id,$ocs_id,$cfg_ocs,$computer_updates,$dohistory=1) {
	global $dbocs,$lang,$db;
	$query = "SELECT * 
		FROM hardware 
		WHERE ID='".$ocs_id."'";
	$result = $dbocs->query($query);
	if ($dbocs->numrows($result)==1) {

		$line=$dbocs->fetch_assoc($result);
		$line=clean_cross_side_scripting_deep(addslashes_deep($line));
		$compudate=array();

		if($cfg_ocs["import_general_os"]&&!in_array("os",$computer_updates)) {
			$compupdate["os"] = ocsImportDropdown('glpi_dropdown_os','name',$line["OSNAME"]);
			$compupdate["os_version"] = ocsImportDropdown('glpi_dropdown_os_version','name',$line["OSVERSION"]);
			if (!ereg("CEST",$line["OSCOMMENTS"])) // Not linux comment
				$compupdate["os_sp"] = ocsImportDropdown('glpi_dropdown_os_sp','name',$line["OSCOMMENTS"]);
		}

		if($cfg_ocs["import_general_domain"]&&!in_array("domain",$computer_updates)) {
			$compupdate["domain"] = ocsImportDropdown('glpi_dropdown_domain','name',$line["WORKGROUP"]);
		}

		if($cfg_ocs["import_general_contact"]&&!in_array("contact",$computer_updates)) {
			$compupdate["contact"] = $line["USERID"];
			$query="SELECT ID 
				FROM glpi_users
				WHERE name='".$line["USERID"]."';";
			$result=$db->query($query);
			if ($db->numrows($result)==1&&!in_array("FK_users",$computer_updates)){
				$compupdate["FK_users"] = $db->result($result,0,0);
			}
		}

		if($cfg_ocs["import_general_name"]&&!in_array("name",$computer_updates)) {
			$compupdate["name"] = $line["NAME"];
		}

		if($cfg_ocs["import_general_comments"]&&!in_array("comments",$computer_updates)) {
			$compupdate["comments"]="";;
			if (!empty($line["DESCRIPTION"])&&$line["DESCRIPTION"]!="N/A") $compupdate["comments"] .= $line["DESCRIPTION"]."\r\n";
			$compupdate["comments"] .= "Swap: ".$line["SWAP"];
		}
		if (count($compupdate)){
			$compupdate["ID"] = $glpi_id;
			$comp=new Computer();
			$comp->update($compupdate,$dohistory);
		}

	}
}


/**
 * Update the computer bios configuration
 *
 * Update the computer bios configuration
 *
 *@param $ocs_id integer : glpi computer id
 *@param $glpi_id integer : ocs computer id.
 *@param $cfg_ocs array : ocs config
 *@param $computer_updates array : already updated fields of the computer
 *@param $dohistory boolean : log changes ?
 *
 *@return nothing.
 *
 **/
function ocsUpdateBios($glpi_id,$ocs_id,$cfg_ocs,$computer_updates,$dohistory=1) {
	global $dbocs;
	$query = "SELECT * 
		FROM bios 
		WHERE HARDWARE_ID='".$ocs_id."'";
	$result = $dbocs->query($query);
	$compupdate=array();
	if ($dbocs->numrows($result)==1) {
		$line=$dbocs->fetch_assoc($result);
		$line=clean_cross_side_scripting_deep(addslashes_deep($line));
		$compudate=array();

		if($cfg_ocs["import_general_serial"]&&!in_array("serial",$computer_updates)) {
			$compupdate["serial"] = $line["SSN"];
		}

		if($cfg_ocs["import_general_model"]&&!in_array("model",$computer_updates)) {
			$compupdate["model"] = ocsImportDropdown('glpi_dropdown_model','name',$line["SMODEL"]);
		}	

		if($cfg_ocs["import_general_enterprise"]&&!in_array("FK_glpi_enterprise",$computer_updates)) {
			$compupdate["FK_glpi_enterprise"] = ocsImportEnterprise($line["SMANUFACTURER"]);
		}

		if($cfg_ocs["import_general_type"]&&!empty($line["TYPE"])&&!in_array("type",$computer_updates)) {
			$compupdate["type"] = ocsImportDropdown('glpi_type_computers','name',$line["TYPE"]);
		}

		if (count($compupdate)){
			$compupdate["ID"] = $glpi_id;
			$comp=new Computer();
			$comp->update($compupdate,$dohistory);
		}

	}
}


/**
 * Import a dropdown from OCS table.
 *
 * This import a new dropdown if it doesn't exist.
 *
 *@param $dpdTable string : Name of the glpi dropdown table.
 *@param $dpdRow string : Name of the glinclude ($phproot . "/glpi/includes_devices.php");pi dropdown row.
 *@param $value string : Value of the new dropdown.
 *
 *@return integer : dropdown id.
 *
 **/

function ocsImportDropdown($dpdTable,$dpdRow,$value) {
	global $db,$cfg_glpi;

	if (empty($value)) return 0;

	$query2 = "SELECT * 
		FROM ".$dpdTable." 
		WHERE $dpdRow='".$value."'";
	$result2 = $db->query($query2);
	if($db->numrows($result2) == 0) {
		if (in_array($dpdTable,$cfg_glpi["dropdowntree_tables"])&&$dpdRow=="name"){
			$query3 = "INSERT INTO ".$dpdTable." (".$dpdRow.",completename) 
				VALUES ('".$value."','".$value."')";
		} else {
			$query3 = "INSERT INTO ".$dpdTable." (".$dpdRow.") 
				VALUES ('".$value."')";
		}
		$db->query($query3);
		return $db->insert_id();
	} else {
		$line2 = $db->fetch_array($result2);
		return $line2["ID"];
	}

}


/**
 * Import general config of a new enterprise
 *
 * This function create a new enterprise in GLPI with some general datas.
 *
 *@param $name : name of the enterprise.
 *
 *@return integer : inserted enterprise id.
 *
 **/
function ocsImportEnterprise($name) {
	global $db;
	if (empty($name)) return 0;
	$query = "SELECT ID 
		FROM glpi_enterprises 
		WHERE name = '".$name."'";
	$result = $db->query($query);
	if ($db->numrows($result)>0){
		$enterprise_id  = $db->result($result,0,"ID");
	} else {
		$entpr = new Enterprise;
		$entpr->fields["name"] = $name;
		$enterprise_id = $entpr->addToDB();
	}
	return($enterprise_id);
}

function ocsCleanLinks(){
	global $db,$dbocs;


	// Delete unexisting GLPI computers
	$query="SELECT glpi_ocs_link.ID AS ID 
		FROM glpi_ocs_link 
		LEFT JOIN glpi_computers ON glpi_computers.ID=glpi_ocs_link.glpi_id 
		WHERE glpi_computers.ID IS NULL";

	$result=$db->query($query);
	if ($db->numrows($result)>0){
		while ($data=$db->fetch_array($result)){
			$query2="DELETE FROM glpi_ocs_link 
				WHERE ID='".$data['ID']."'";
			$db->query($query2);
		}
	}

	// Delete unexisting OCS hardware
	$query_ocs = "SELECT * 
		FROM hardware";
	$result_ocs = $dbocs->query($query_ocs);

	$hardware=array();
	if ($dbocs->numrows($result_ocs)>0){
		while($data=$dbocs->fetch_array($result_ocs)){
			$data=clean_cross_side_scripting_deep(addslashes_deep($data));
			$hardware[$data["ID"]]=$data["DEVICEID"];
		}
	}

	$query="SELECT *
		FROM glpi_ocs_link";
	$result = $db->query($query);

	if ($db->numrows($result)>0){
		while($data=$db->fetch_array($result)){
			$data=clean_cross_side_scripting_deep(addslashes_deep($data));
			if (!isset($hardware[$data["ocs_id"]])){
				$query_del="DELETE FROM glpi_ocs_link 
					WHERE ID='".$data["ID"]."'";
				$db->query($query_del);
			}
		}
	}


}


function cron_ocsng(){

	global $db,$dbocs;

	$cfg_ocs=getOcsConf(1);
	ocsManageDeleted();
	
	if (!$cfg_ocs["cron_sync_number"]) return 0;
	
	$query_ocs = "SELECT * 
		FROM hardware 
		WHERE (CHECKSUM & ".$cfg_ocs["checksum"].") > 0";
	$result_ocs = $dbocs->query($query_ocs);
	if ($dbocs->numrows($result_ocs)>0){

		$hardware=array();
		while($data=$dbocs->fetch_array($result_ocs)){
			$hardware[$data["ID"]]["date"]=$data["LASTDATE"];
			$hardware[$data["ID"]]["name"]=addslashes($data["NAME"]);
		}

		$query_glpi = "SELECT * 
			FROM glpi_ocs_link 
			WHERE auto_update= '1'
			ORDER BY last_update";
		$result_glpi = $db->query($query_glpi);
		$done=0;
		while($done<$cfg_ocs["cron_sync_number"]&&$data=$db->fetch_assoc($result_glpi)){
			$data=clean_cross_side_scripting_deep(addslashes_deep($data));

			if (isset($hardware[$data["ocs_id"]])){ 
				ocsUpdateComputer($data["ID"],1);
				$done++;
			}
		}
		if ($done>0) return 1;

	} 
	return 0;

}


function ocsShowUpdateComputer($check,$start){
	global $db,$dbocs,$lang,$HTMLRel,$cfg_glpi;

	if (!haveRight("ocsng","w")) return false;

	$cfg_ocs=getOcsConf(1);

	$query_ocs = "SELECT * 
		FROM hardware 
		WHERE (CHECKSUM & ".$cfg_ocs["checksum"].") > 0 
		ORDER BY LASTDATE";
	$result_ocs = $dbocs->query($query_ocs);

	$query_glpi = "SELECT glpi_ocs_link.last_update as last_update,  glpi_ocs_link.glpi_id as glpi_id, 
		glpi_ocs_link.ocs_id as ocs_id, glpi_computers.name as name, 
		glpi_ocs_link.auto_update as auto_update, glpi_ocs_link.ID as ID 
			FROM glpi_ocs_link 
			LEFT JOIN glpi_computers ON (glpi_computers.ID = glpi_ocs_link.glpi_id) 
			ORDER BY glpi_ocs_link.auto_update DESC, glpi_ocs_link.last_update, glpi_computers.name";

	$result_glpi = $db->query($query_glpi);
	if ($dbocs->numrows($result_ocs)>0){

		// Get all hardware from OCS DB
		$hardware=array();
		while($data=$dbocs->fetch_array($result_ocs)){
			$hardware[$data["ID"]]["date"]=$data["LASTDATE"];
			$hardware[$data["ID"]]["name"]=addslashes($data["NAME"]);
		}

		// Get all links between glpi and OCS
		$already_linked=array();
		if ($db->numrows($result_glpi)>0){
			while($data=$db->fetch_assoc($result_glpi)){
				$data=clean_cross_side_scripting_deep(addslashes_deep($data));
				if (isset($hardware[$data["ocs_id"]])){ 
					$already_linked[$data["ocs_id"]]["date"]=$data["last_update"];
					$already_linked[$data["ocs_id"]]["name"]=$data["name"];
					$already_linked[$data["ocs_id"]]["ID"]=$data["ID"];
					$already_linked[$data["ocs_id"]]["glpi_id"]=$data["glpi_id"];
					$already_linked[$data["ocs_id"]]["ocs_id"]=$data["ocs_id"];
					$already_linked[$data["ocs_id"]]["auto_update"]=$data["auto_update"];
				}
			}
		}
		echo "<div align='center'>";
		echo "<h2>".$lang["ocsng"][10]."</h2>";

		if (($numrows=count($already_linked))>0){

			$parameters="check=$check";
			printPager($start,$numrows,$_SERVER['PHP_SELF'],$parameters);

			// delete end 
			array_splice($already_linked,$start+$cfg_glpi["list_limit"]);
			// delete begin
			if ($start>0)
				array_splice($already_linked,0,$start);

			echo "<form method='post' id='ocsng_form' name='ocsng_form' action='".$_SERVER['PHP_SELF']."'>";

			echo "<a href='".$_SERVER['PHP_SELF']."?check=all' onclick= \"if ( markAllRows('ocsng_form') ) return false;\">".$lang["buttons"][18]."</a>&nbsp;/&nbsp;<a href='".$_SERVER['PHP_SELF']."?check=none' onclick= \"if ( unMarkAllRows('ocsng_form') ) return false;\">".$lang["buttons"][19]."</a>";
			echo "<table class='tab_cadre'>";
			echo "<tr><th>".$lang["ocsng"][11]."</th><th>".$lang["ocsng"][13]."</th><th>".$lang["ocsng"][14]."</th><th>".$lang["ocsng"][6]."</th><th>&nbsp;</th></tr>";

			echo "<tr class='tab_bg_1'><td colspan='5' align='center'>";
			echo "<input class='submit' type='submit' name='update_ok' value='".$lang["buttons"][7]."'>";
			echo "</td></tr>";

			foreach ($already_linked as $ID => $tab){

				echo "<tr align='center' class='tab_bg_2'>";
				echo "<td><a href='".$HTMLRel."front/computer.form.php?ID=".$tab["glpi_id"]."'>".$tab["name"]."</a></td>";
				echo "<td>".convDateTime($tab["date"])."</td><td>".convDateTime($hardware[$tab["ocs_id"]]["date"])."</td>";
				echo "<td>".$lang["choice"][$tab["auto_update"]]."</td>";
				echo "<td><input type='checkbox' name='toupdate[".$tab["ID"]."]' ".($check=="all"?"checked":"").">";
				echo "</td></tr>";
			}
			echo "<tr class='tab_bg_1'><td colspan='5' align='center'>";
			echo "<input class='submit' type='submit' name='update_ok' value='".$lang["buttons"][7]."'>";
			echo "</td></tr>";
			echo "</table>";
			echo "</form>";
			printPager($start,$numrows,$_SERVER['PHP_SELF'],$parameters);

		} else echo "<br><strong>".$lang["ocsng"][11]."</strong>";

		echo "</div>";

	} else echo "<div align='center'><strong>".$lang["ocsng"][12]."</strong></div>";
}


function mergeOcsArray($glpi_id,$tomerge,$field){
	global $db;
	$query="SELECT $field 
		FROM glpi_ocs_link 
		WHERE glpi_id='$glpi_id'";
	$result=$db->query($query);
	if ($db->numrows($result))
	if ($result=$db->query($query)){
		$tab=importArrayFromDB($db->result($result,0,0));
		$newtab=array_merge($tomerge,$tab);
		$newtab=array_unique($newtab);
		$query="UPDATE glpi_ocs_link 
			SET $field='".exportArrayToDB($newtab)."' 
			WHERE glpi_id='$glpi_id'";
		$db->query($query);
	}

}

function deleteInOcsArray($glpi_id,$todel,$field){
	global $db;
	$query="SELECT $field FROM glpi_ocs_link WHERE glpi_id='$glpi_id'";
	if ($result=$db->query($query)){
		$tab=importArrayFromDB($db->result($result,0,0));
		unset($tab[$todel]);
		$query="UPDATE glpi_ocs_link 
			SET $field='".exportArrayToDB($tab)."' 
			WHERE glpi_id='$glpi_id'";
		$db->query($query);
	}

}

function addToOcsArray($glpi_id,$toadd,$field){
	global $db;
	$query="SELECT $field 
		FROM glpi_ocs_link 
		WHERE glpi_id='$glpi_id'";
	if ($result=$db->query($query)){
		$tab=importArrayFromDB($db->result($result,0,0));
		foreach ($toadd as $key => $val)
			$tab[$key]=$val;
		$query="UPDATE glpi_ocs_link 
			SET $field='".exportArrayToDB($tab)."' 
			WHERE glpi_id='$glpi_id'";
		$db->query($query);
	}

}


function ocsEditLock($target,$ID){
	global $db,$lang,$SEARCH_OPTION;


	$query="SELECT * 
		FROM glpi_ocs_link 
		WHERE glpi_id='$ID'";

	$result=$db->query($query);
	if ($db->numrows($result)==1){
		$data=$db->fetch_assoc($result);

		echo "<div align='center'>";
		echo "<form method='post' action=\"$target\">";
		echo "<input type='hidden' name='ID' value='$ID'>";
		echo "<table class='tab_cadre'><tr class='tab_bg_2'><td>";
		echo "<input type='hidden' name='resynch_id' value='".$data["ID"]."'>";
		echo "<input class=submit type='submit' name='force_ocs_resynch' value='".$lang["ocsng"][24]."'>";
		echo "</td><tr></table>";
		echo "</form>";

		echo "</div>";

		echo "<div align='center'>";
		// Print lock fields for OCSNG

		$lockable_fields=array("name","type","FK_glpi_enterprise","model","serial","comments","contact","domain","os","os_sp","os_version");
		$locked=array_intersect(importArrayFromDB($data["computer_update"]),$lockable_fields);

		if (count($locked)){
			echo "<form method='post' action=\"$target\">";
			echo "<input type='hidden' name='ID' value='$ID'>";
			echo "<table class='tab_cadre'>";
			echo "<tr><th colspan='2'>".$lang["ocsng"][16]."</th></tr>";
			foreach ($locked as $key => $val){
				foreach ($SEARCH_OPTION[COMPUTER_TYPE] as $key2 => $val2)
					if ($val2["linkfield"]==$val||($val2["table"]=="glpi_computers"&&$val2["field"]==$val))
						echo "<tr class='tab_bg_1'><td>".$val2["name"]."</td><td><input type='checkbox' name='lockfield[".$key."]'></td></tr>";
			}
			echo "<tr class='tab_bg_2'><td align='center' colspan='2'><input class='submit' type='submit' name='unlock_field' value='".$lang["buttons"][38]."'></td></tr>";
			echo "</table>";
			echo "</form>";
		} else echo "<strong>".$lang["ocsng"][15]."</strong>";
		echo "</div>";
	}

}

/**
 * Import the devices for a computer
 *
 * 
 *
 *@param $device_type integer : device type
 *@param $glpi_id integer : glpi computer id.
 *@param $ocs_id integer : ocs computer id (ID).
 *@param $cfg_ocs array : ocs config
 *@param $dohistory boolean : log changes ?
 *@param $import_device array : already imported devices
 *
 *@return Nothing (void).
 *
 **/
function ocsUpdateDevices($device_type,$glpi_id,$ocs_id,$cfg_ocs,$import_device,$dohistory){
	global $dbocs,$db;

	$do_clean=false;
	switch ($device_type){
		case RAM_DEVICE:
			//Memoire
			if ($cfg_ocs["import_device_memory"]){
				$do_clean=true;

				$query2 = "SELECT * 
					FROM memories 
					WHERE HARDWARE_ID = '".$ocs_id."' 
					ORDER BY ID";
				$result2 = $dbocs->query($query2);
				if($dbocs->numrows($result2) > 0) {
					while($line2 = $dbocs->fetch_array($result2)) {
						$line2=clean_cross_side_scripting_deep(addslashes_deep($line2));			
						if(!empty($line2["CAPACITY"])&&$line2["CAPACITY"]!="No") {
							if($line2["DESCRIPTION"]) $ram["designation"] = $line2["DESCRIPTION"];
							else $ram["designation"] = "Unknown";
							$ram["specif_default"] =  $line2["CAPACITY"];
							if (!in_array(RAM_DEVICE."$$$$$".$ram["designation"],$import_device)){
								$ram["frequence"] =  $line2["SPEED"];
								$ram["type"] = ocsImportDropdown("glpi_dropdown_ram_type","name",$line2["TYPE"]);
								$ram_id = ocsAddDevice(RAM_DEVICE,$ram);
								if ($ram_id){
									$devID=compdevice_add($glpi_id,RAM_DEVICE,$ram_id,$line2["CAPACITY"],$dohistory);
									addToOcsArray($glpi_id,array($devID=>RAM_DEVICE."$$$$$".$ram["designation"]),"import_device");
								}
							} else {
								$id=array_search(RAM_DEVICE."$$$$$".$ram["designation"],$import_device);
								update_device_specif($line2["CAPACITY"],$id,1);
								unset($import_device[$id]);
							}
						}
					}
				}
			}
			break;
		case HDD_DEVICE:
			//Disque Dur
			if ($cfg_ocs["import_device_hdd"]){
				$do_clean=true;

				$query2 = "SELECT * 
					FROM storages 
					WHERE HARDWARE_ID = '".$ocs_id."' 
					ORDER BY ID";
				$result2 = $dbocs->query($query2);

				if($dbocs->numrows($result2) > 0) {
					while($line2 = $dbocs->fetch_array($result2)) {
						$line2=clean_cross_side_scripting_deep(addslashes_deep($line2));
						if(!empty($line2["DISKSIZE"])&&eregi("disk",$line2["TYPE"])) {
							if($line2["NAME"]) $dd["designation"] = $line2["NAME"];
							else if($line2["MODEL"]) $dd["designation"] = $line2["MODEL"];
							else $dd["designation"] = "Unknown";
							
							if (!in_array(HDD_DEVICE."$$$$$".$dd["designation"],$import_device)){
								$dd["specif_default"] =  $line2["DISKSIZE"];
								$dd_id = ocsAddDevice(HDD_DEVICE,$dd);
								if ($dd_id){
									$devID=compdevice_add($glpi_id,HDD_DEVICE,$dd_id,$line2["DISKSIZE"],$dohistory);
									addToOcsArray($glpi_id,array($devID=>HDD_DEVICE."$$$$$".$dd["designation"]),"import_device");
								}
							} else {
								$id=array_search(HDD_DEVICE."$$$$$".$dd["designation"],$import_device);
								update_device_specif($line2["DISKSIZE"],$id,1);
								unset($import_device[$id]);
							}

						}
					}
				}
			}

			break;
		case DRIVE_DEVICE:
			//lecteurs
			if ($cfg_ocs["import_device_drives"]){
				$do_clean=true;

				$query2 = "SELECT * 
					FROM storages 
					WHERE HARDWARE_ID = '".$ocs_id."' ORDER BY ID";
				$result2 = $dbocs->query($query2);
				if($dbocs->numrows($result2) > 0) {
					while($line2 = $dbocs->fetch_array($result2)) {
						$line2=clean_cross_side_scripting_deep(addslashes_deep($line2));
						if(empty($line2["DISKSIZE"])||!eregi("disk",$line2["TYPE"])) {
							if($line2["NAME"]) $stor["designation"] = $line2["NAME"];
							else if($line2["MODEL"]) $stor["designation"] = $line2["MODEL"];
							else $stor["designation"] = "Unknown";
							if (!in_array(DRIVE_DEVICE."$$$$$".$stor["designation"],$import_device)){
								$stor["specif_default"] =  $line2["DISKSIZE"];
								$stor_id = ocsAddDevice(DRIVE_DEVICE,$stor);
								if ($stor_id){
									$devID=compdevice_add($glpi_id,DRIVE_DEVICE,$stor_id,"",$dohistory);
									addToOcsArray($glpi_id,array($devID=>DRIVE_DEVICE."$$$$$".$stor["designation"]),"import_device");
								}
							} else {
								$id=array_search(DRIVE_DEVICE."$$$$$".$stor["designation"],$import_device);
								unset($import_device[$id]);
							}

						}
					}
				}
			}
			break;
		case PCI_DEVICE:
			//Modems
			if ($cfg_ocs["import_device_modems"]){	
				$do_clean=true;

				$query2 = "SELECT * 
					FROM modems 
					WHERE HARDWARE_ID = '".$ocs_id."' 
					ORDER BY ID";
				$result2 = $dbocs->query($query2);
				if($dbocs->numrows($result2) > 0) {
					while($line2 = $dbocs->fetch_array($result2)) {
						$line2=clean_cross_side_scripting_deep(addslashes_deep($line2));
						$mdm["designation"] = $line2["NAME"];
						if (!in_array(PCI_DEVICE."$$$$$".$mdm["designation"],$import_device)){
							if(!empty($line2["DESCRIPTION"])) $mdm["comment"] = $line2["TYPE"]."\r\n".$line2["DESCRIPTION"];
							$mdm_id = ocsAddDevice(PCI_DEVICE,$mdm);
							if ($mdm_id){
								$devID=compdevice_add($glpi_id,PCI_DEVICE,$mdm_id,"",$dohistory);
								addToOcsArray($glpi_id,array($devID=>PCI_DEVICE."$$$$$".$mdm["designation"]),"import_device");
							}
						} else {
							$id=array_search(PCI_DEVICE."$$$$$".$mdm["designation"],$import_device);
							unset($import_device[$id]);
						}

					}
				}
			}
			//Ports
			if ($cfg_ocs["import_device_ports"]){

				$query2 = "SELECT * 
					FROM ports 
					WHERE HARDWARE_ID = '".$ocs_id."' 
					ORDER BY ID";
				$result2 = $dbocs->query($query2);
				if($dbocs->numrows($result2) > 0) {
					while($line2 = $dbocs->fetch_array($result2)) {
						$line2=clean_cross_side_scripting_deep(addslashes_deep($line2));
						$port["designation"]="";	
						if ($line2["TYPE"]!="Other") $port["designation"] .= $line2["TYPE"];
						if ($line2["NAME"]!="Not Specified") $port["designation"] .= " ".$line2["NAME"];
						else if ($line2["CAPTION"]!="None") $port["designation"] .= " ".$line2["CAPTION"];
						if (!empty($port["designation"]))
							if (!in_array(PCI_DEVICE."$$$$$".$port["designation"],$import_device)){
								if(!empty($line2["DESCRIPTION"])&&$line2["DESCRIPTION"]!="None") $port["comment"] = $line2["DESCRIPTION"];
								$port_id = ocsAddDevice(PCI_DEVICE,$port);
								if ($port_id){
									$devID=compdevice_add($glpi_id,PCI_DEVICE,$port_id,"",$dohistory);
									addToOcsArray($glpi_id,array($devID=>PCI_DEVICE."$$$$$".$port["designation"]),"import_device");
								}
							} else {
								$id=array_search(PCI_DEVICE."$$$$$".$port["designation"],$import_device);
								unset($import_device[$id]);
							}						
					}
				}
			}
			break;
		case PROCESSOR_DEVICE:
			//Processeurs : 
			if ($cfg_ocs["import_device_processor"]){
				$do_clean=true;

				$query = "SELECT * 
					FROM hardware 
					WHERE ID='$ocs_id' ORDER BY ID";
				$result = $dbocs->query($query);
				if ($dbocs->numrows($result)==1){
					$line=$dbocs->fetch_array($result);
					$line=clean_cross_side_scripting_deep(addslashes_deep($line));
					for($i = 0;$i < $line["PROCESSORN"]; $i++) {
						$processor = array();
						$processor["designation"] = $line["PROCESSORT"];
						$processor["specif_default"] =  $line["PROCESSORS"];
						if (!in_array(PROCESSOR_DEVICE."$$$$$".$processor["designation"],$import_device)){
							$proc_id = ocsAddDevice(PROCESSOR_DEVICE,$processor);
							if ($proc_id){
								$devID=compdevice_add($glpi_id,PROCESSOR_DEVICE,$proc_id,$line["PROCESSORS"],$dohistory);
								addToOcsArray($glpi_id,array($devID=>PROCESSOR_DEVICE."$$$$$".$processor["designation"]),"import_device");
							}
						} else {
							$id=array_search(PROCESSOR_DEVICE."$$$$$".$processor["designation"],$import_device);
							update_device_specif($line["PROCESSORS"],$id,1);
							unset($import_device[$id]);
						}						
					}
				}
			}
			break;
		case NETWORK_DEVICE:

			//Carte reseau
			if ($cfg_ocs["import_device_iface"]||$cfg_ocs["import_ip"]){

				$query2 = "SELECT * 
					FROM networks 
					WHERE HARDWARE_ID = '".$ocs_id."' 
					ORDER BY ID";

				$result2 = $dbocs->query($query2);
				$i=0;
				// Add network device
				if($dbocs->numrows($result2) > 0) {
					$already_used_ip=array();
					while($line2 = $dbocs->fetch_array($result2)) {
						$line2=clean_cross_side_scripting_deep(addslashes_deep($line2));
						if ($cfg_ocs["import_device_iface"]){
							$do_clean=true;
							$network["designation"] = $line2["DESCRIPTION"];
							if (!in_array(NETWORK_DEVICE."$$$$$".$network["designation"],$import_device)){
								if(!empty($line2["SPEED"])) $network["bandwidth"] =  $line2["SPEED"];
								$net_id = ocsAddDevice(NETWORK_DEVICE,$network);
								if ($net_id){
									$devID=compdevice_add($glpi_id,NETWORK_DEVICE,$net_id,$line2["MACADDR"],$dohistory);
									addToOcsArray($glpi_id,array($devID=>NETWORK_DEVICE."$$$$$".$network["designation"]),"import_device");
								}
							} else {
								$id=array_search(NETWORK_DEVICE."$$$$$".$network["designation"],$import_device);
								update_device_specif($line2["MACADDR"],$id,1);
								unset($import_device[$id]);
							}						
						}

						if (!empty($line2["IPADDRESS"])&&$cfg_ocs["import_ip"]){
							$ocs_ips=split(",",$line2["IPADDRESS"]);
							$ocs_ips=array_unique($ocs_ips);
							sort($ocs_ips);

							// Is there an existing networking port ?
							$query="SELECT * 
								FROM glpi_networking_ports 
								WHERE device_type='".COMPUTER_TYPE."' 
								AND on_device='$glpi_id' 
								AND ifmac='".$line2["MACADDR"]."'
								AND name='".$line2["DESCRIPTION"]."' 
								ORDER BY ID";
							$glpi_ips=array();
							$result=$db->query($query);
							if ($db->numrows($result)>0){
								while ($data=$db->fetch_array($result))
									if (!in_array($data["ID"],$already_used_ip)){
										$glpi_ips[]=$data["ID"];
									}
							}
							
							unset($netport);
							$netport["ifmac"]=$line2["MACADDR"];
							$netport["iface"]=ocsImportDropdown("glpi_dropdown_iface","name",$line2["TYPE"]);
							$netport["name"]=$line2["DESCRIPTION"];
							$netport["on_device"]=$glpi_id;
							$netport["device_type"]=COMPUTER_TYPE;

							$np=new Netport();
							// Update already in DB
							for ($j=0;$j<min(count($glpi_ips),count($ocs_ips));$j++){
									$netport["ifaddr"]=$ocs_ips[$j];
									$netport["logical_number"]=$i;
									$netport["ID"]=$glpi_ips[$j];
									$already_used_ip[]=$glpi_ips[$j];
									$np->update($netport);
									$i++;
							}

							// If other IP founded
							if (count($glpi_ips)<count($ocs_ips))
								for ($j=count($glpi_ips);$j<count($ocs_ips);$j++){
									unset($netport["ID"]);
									unset($np->fields["ID"]);
									$netport["ifaddr"]=$ocs_ips[$j];
									$netport["logical_number"]=$i;
									$np->add($netport);
									$i++;
								}
						}
					}
				}

			}
			break;
		case GFX_DEVICE:
			//carte graphique
			if ($cfg_ocs["import_device_gfxcard"]){
				$do_clean=true;

				$query2 = "SELECT DISTINCT(NAME) as NAME, MEMORY 
					FROM videos 
					WHERE HARDWARE_ID = '".$ocs_id."'and NAME != '' 
					ORDER BY ID";
				$result2 = $dbocs->query($query2);
				if($dbocs->numrows($result2) > 0) {
					while($line2 = $dbocs->fetch_array($result2)) {
						$line2=clean_cross_side_scripting_deep(addslashes_deep($line2));
						$video["designation"] = $line2["NAME"];
						if (!in_array(GFX_DEVICE."$$$$$".$video["designation"],$import_device)){
							$video["ram"]="";
							if(!empty($line2["MEMORY"])) $video["ram"] =  $line2["MEMORY"];
							$video_id = ocsAddDevice(GFX_DEVICE,$video);
							if ($video_id){
								$devID=compdevice_add($glpi_id,GFX_DEVICE,$video_id,$video["ram"],$dohistory);
								addToOcsArray($glpi_id,array($devID=>GFX_DEVICE."$$$$$".$video["designation"]),"import_device");
							}
						} else {
							$id=array_search(GFX_DEVICE."$$$$$".$video["designation"],$import_device);
							update_device_specif($line2["MEMORY"],$id,1);
							unset($import_device[$id]);
						}						
					}
				}
			}
			break;
		case SND_DEVICE:
			//carte son
			if ($cfg_ocs["import_device_sound"]){
				$do_clean=true;

				$query2 = "SELECT DISTINCT(NAME) as NAME, DESCRIPTION 
					FROM sounds 
					WHERE HARDWARE_ID = '".$ocs_id."' 
					AND NAME != '' ORDER BY ID";
				$result2 = $dbocs->query($query2);
				if($dbocs->numrows($result2) > 0) {
					while($line2 = $dbocs->fetch_array($result2)) {
						$line2=clean_cross_side_scripting_deep(addslashes_deep($line2));
						$snd["designation"] = $line2["NAME"];
						if (!in_array(SND_DEVICE."$$$$$".$snd["designation"],$import_device)){
							if(!empty($line2["DESCRIPTION"])) $snd["comment"] =  $line2["DESCRIPTION"];
							$snd_id = ocsAddDevice(SND_DEVICE,$snd);
							if ($snd_id){
								$devID=compdevice_add($glpi_id,SND_DEVICE,$snd_id,"",$dohistory);
								addToOcsArray($glpi_id,array($devID=>SND_DEVICE."$$$$$".$snd["designation"]),"import_device");
							}
						} else {
							$id=array_search(SND_DEVICE."$$$$$".$snd["designation"],$import_device);
							unset($import_device[$id]);
						}						
					}
				}
			}
			break;
	}

	// Delete Unexisting Items not found in OCS
	if ($do_clean&&count($import_device)){
		foreach ($import_device as $key => $val){
			if (!(strpos($val,$device_type."$$")===false)){

                                // Networking case : Delete ports corresponding to device : 
 		                                if ($device_type==NETWORK_DEVICE){ 
 		                                        $np=new Netport(); 
 		                                        $np->getFromDB($key); 
 		                                        $query="SELECT specificity FROM glpi_computer_device WHERE ID='$key' "; 
 		                                        $result=$db->query($query); 
 		                                        if ($db->numrows($result)){ 
 		                                                $macaddr=$db->result($result,0,0); 
 		                                                $query2="DELETE FROM glpi_networking_ports WHERE name='".str_replace($device_type.'$$$$$',"",$val)."' AND ifmac='$macaddr'"; 
 		                                                $db->query($query2); 
 		                                        } 
 		                                } 

				unlink_device_computer($key,$dohistory);
				deleteInOcsArray($glpi_id,$key,"import_device");
			} 
		}
	}
	//Alimentation
	//Carte mere
}

/**
 * Add a new device.
 *
 * Add a new device if doesn't exist.
 *
 *@param $device_type integer : device type identifier.
 *@param $dev_array array : device fields.
 *
 *@return integer : device id.
 *
 **/
function ocsAddDevice($device_type,$dev_array) {

	global $db;
	$query = "SELECT * 
		FROM ".getDeviceTable($device_type)." 
		WHERE designation='".$dev_array["designation"]."'";
	$result = $db->query($query);
	if($db->numrows($result) == 0) {
		$dev = new Device($device_type);
		foreach($dev_array as $key => $val) {
			$dev->fields[$key] = $val;
		}
		return($dev->addToDB());
	} else {
		$line = $db->fetch_array($result);
		return $line["ID"];
	}

}

/**
 * Import the devices for a computer
 *
 * 
 *
 *@param $device_type integer : device type 
 *@param $glpi_id integer : glpi computer id.
 *@param $ocs_id integer : ocs computer id (ID).
 *@param $cfg_ocs array : ocs config
 *@param $dohistory boolean : log changes ?
 *@param $import_periph array : already imported periph
 *
 *@return Nothing (void).
 *
 **/
function ocsUpdatePeripherals($device_type,$glpi_id,$ocs_id,$cfg_ocs,$import_periph,$dohistory){
	global $db,$dbocs;
	$do_clean=false;
	$connID=0;
	switch ($device_type){
		case MONITOR_TYPE:
			if ($cfg_ocs["import_monitor"]){
				$do_clean=true;

				$query = "SELECT DISTINCT CAPTION, MANUFACTURER, DESCRIPTION, SERIAL, TYPE 
					FROM monitors 
					WHERE HARDWARE_ID = '".$ocs_id."'";
				$result = $dbocs->query($query);
				if($dbocs->numrows($result) > 0) 
					while($line = $dbocs->fetch_array($result)) {
						$line=clean_cross_side_scripting_deep(addslashes_deep($line));
						$mon["name"] = $line["CAPTION"];
						if (empty($mon["name"])) $mon["name"] = $line["TYPE"];
						if (empty($mon["name"])) $mon["name"] = $line["MANUFACTURER"];
						if (!empty($mon["name"]))
							if (!in_array($mon["name"],$import_periph)){
								$mon["FK_glpi_enterprise"] = ocsImportEnterprise($line["MANUFACTURER"]);
								$mon["comments"] = $line["DESCRIPTION"];
								$mon["serial"] = $line["SERIAL"];
								$mon["date_mod"] = date("Y-m-d H:i:s");
								$id_monitor=0;
								$found_already_monitor=false;
								if($cfg_ocs["import_monitor"] == 1) {
									//Config says : manage monitors as global
									//check if monitors already exists in GLPI
									$mon["is_global"]=1;
									$query = "SELECT ID 
										FROM glpi_monitors 
										WHERE name = '".$mon["name"]."' 
										AND is_global = '1'";
									$result_search = $db->query($query);
									if($db->numrows($result_search) > 0) {
										//Periph is already in GLPI
										//Do not import anything just get periph ID for link
										$id_monitor = $db->result($result_search,0,"ID");
									} else {
										$m=new Monitor;
										$m->fields=$mon;
										$id_monitor=$m->addToDB();

										if ($id_monitor){
											if ($cfg_ocs["default_state"]){
												updateState(MONITOR_TYPE,$id_monitor,$cfg_ocs["default_state"],0,0);
											}
										}
									}
								} else if($cfg_ocs["import_monitor"] == 2) {
									//COnfig says : manage monitors as single units
									//Import all monitors as non global.
									$mon["is_global"]=0;
									$m=new Monitor;

									// First import - Is there already a monitor ?
									if (count($import_periph)==0){
										$query_search="SELECT end1 
											FROM glpi_connect_wire 
											WHERE end2='$glpi_id' 
											AND type='".MONITOR_TYPE."'";
										$result_search=$db->query($query_search);
										if ($db->numrows($result_search)==1){
											$id_monitor=$db->result($result_search,0,0);
											$found_already_monitor=true;
										}
									}

									if ($found_already_monitor&&$id_monitor){

										$m->getFromDB($id_monitor);
										if (!$m->fields["is_global"]){
											$mon["ID"]=$id_monitor;
											unset($mon["comments"]);

											$m->update($mon);
										} else {
											$m->fields=$mon;
											$id_monitor=$m->addToDB();
											$found_already_monitor=false;
											if ($id_monitor){
												if ($cfg_ocs["default_state"]){
													updateState(MONITOR_TYPE,$id_monitor,$cfg_ocs["default_state"],0,0);
												}
											}
										}
									} else {

										$m->fields=$mon;

										$id_monitor=$m->addToDB();
										if ($id_monitor){
											if ($cfg_ocs["default_state"]){
												updateState(MONITOR_TYPE,$id_monitor,$cfg_ocs["default_state"],0,0);
											}
										}

									}
								}	
								if ($id_monitor){
									if (!$found_already_monitor)
										$connID=Connect($id_monitor,$glpi_id,MONITOR_TYPE);
									addToOcsArray($glpi_id,array($connID=>$mon["name"]),"import_monitor");
								}
							} else {
								$id=array_search($mon["name"],$import_periph);
								unset($import_periph[$id]);
							}
					}
			}
			break;
		case PRINTER_TYPE:
			if ($cfg_ocs["import_printer"]){
				$do_clean=true;

				$query = "SELECT * 
					FROM printers 
					WHERE HARDWARE_ID = '".$ocs_id."'";
				$result = $dbocs->query($query);

				if($dbocs->numrows($result) > 0) 
					while($line = $dbocs->fetch_array($result)) {
						$line=clean_cross_side_scripting_deep(addslashes_deep($line));

						// TO TEST : PARSE NAME to have real name.
						$print["name"] = $line["NAME"];
						if (empty($print["name"]))	$print["name"] = $line["DRIVER"];

						if (!empty($print["name"]))
							if (!in_array($print["name"],$import_periph)){
								//$print["comments"] = $line["PORT"]."\r\n".$line["NAME"];
								$print["comments"] = $line["PORT"]."\r\n".$line["DRIVER"];
								$print["date_mod"] = date("Y-m-d H:i:s");
								$id_printer=0;

								if($cfg_ocs["import_printer"] == 1) {
									//Config says : manage printers as global
									//check if printers already exists in GLPI
									$print["is_global"]=1;
									$query = "SELECT ID 
										FROM glpi_printers 
										WHERE name = '".$print["name"]."' 
										AND is_global = '1'";
									$result_search = $db->query($query);
									if($db->numrows($result_search) > 0) {
										//Periph is already in GLPI
										//Do not import anything just get periph ID for link
										$id_printer = $db->result($result_search,0,"ID");
									} else {
										$p=new Printer;
										$p->fields=$print;
										$id_printer=$p->addToDB();
										if ($id_printer){
											if ($cfg_ocs["default_state"]){
												updateState(PRINTER_TYPE,$id_printer,$cfg_ocs["default_state"],0,0);
											}
										}
									}
								} else if($cfg_ocs["import_printer"] == 2) {
									//COnfig says : manage printers as single units
									//Import all printers as non global.
									$print["is_global"]=0;
									$p=new Printer;
									$p->fields=$print;
									$id_printer=$p->addToDB();
									if ($id_printer){
										if ($cfg_ocs["default_state"]){
											updateState(PRINTER_TYPE,$id_printer,$cfg_ocs["default_state"],0,0);
										}
									}
								}	
								if ($id_printer){
									$connID=Connect($id_printer,$glpi_id,PRINTER_TYPE);
									addToOcsArray($glpi_id,array($connID=>$print["name"]),"import_printers");
								}
							} else {
								$id=array_search($print["name"],$import_periph);
								unset($import_periph[$id]);
							}
					}
			}
			break;
		case PERIPHERAL_TYPE:
			if ($cfg_ocs["import_periph"]){
				$do_clean=true;

				$query = "SELECT DISTINCT CAPTION, MANUFACTURER, INTERFACE, TYPE 
					FROM inputs 
					WHERE HARDWARE_ID = '".$ocs_id."' 
					AND CAPTION <> ''";
				$result = $dbocs->query($query);
				if($dbocs->numrows($result) > 0) 
					while($line = $dbocs->fetch_array($result)) {
						$line=clean_cross_side_scripting_deep(addslashes_deep($line));

						$periph["name"] = $line["CAPTION"];
						if (!in_array($periph["name"],$import_periph)){
							if ($line["MANUFACTURER"]!="NULL") $periph["brand"] = $line["MANUFACTURER"];
							if ($line["INTERFACE"]!="NULL") $periph["comments"] = $line["INTERFACE"];
							$periph["type"] = ocsImportDropdown("glpi_type_peripherals","name",$line["TYPE"]);
							$periph["date_mod"] = date("Y-m-d H:i:s");

							$id_periph=0;

							if($cfg_ocs["import_periph"] == 1) {
								//Config says : manage peripherals as global
								//check if peripherals already exists in GLPI
								$periph["is_global"]=1;
								$query = "SELECT ID 
									FROM glpi_peripherals 
									WHERE name = '".$periph["name"]."' 
									AND is_global = '1'";
								$result_search = $db->query($query);
								if($db->numrows($result_search) > 0) {
									//Periph is already in GLPI
									//Do not import anything just get periph ID for link
									$id_periph = $db->result($result_search,0,"ID");
								} else {
									$p=new Peripheral;
									$p->fields=$periph;
									$id_periph=$p->addToDB();
									if ($id_periph){
										if ($cfg_ocs["default_state"]){
											updateState(PERIPHERAL_TYPE,$id_periph,$cfg_ocs["default_state"],0,0);
										}
									}
								}
							} else if($cfg_ocs["import_periph"] == 2) {
								//COnfig says : manage peripherals as single units
								//Import all peripherals as non global.
								$periph["is_global"]=0;
								$p=new Peripheral;
								$p->fields=$periph;
								$id_periph=$p->addToDB();
								if ($id_periph){
									if ($cfg_ocs["default_state"]){
										updateState(PERIPHERAL_TYPE,$id_periph,$cfg_ocs["default_state"],0,0);
									}
								}
							}	
							if ($id_periph){
								$connID=Connect($id_periph,$glpi_id,PERIPHERAL_TYPE);
								addToOcsArray($glpi_id,array($connID=>$periph["name"]),"import_peripheral");
							}
						} else {
							$id=array_search($periph["name"],$import_periph);
							unset($import_periph[$id]);
						}
					}
			}
			break;
	}

	// Disconnect Unexisting Items not found in OCS
	if ($do_clean&&count($import_periph)){
		foreach ($import_periph as $key => $val){


			$query = "SELECT * 
				FROM glpi_connect_wire 
				WHERE ID = '".$key."'";
			$result=$db->query($query);
			if ($db->numrows($result)>0){
				while ($data=$db->fetch_assoc($result)){
					$query2="SELECT COUNT(*) 
						FROM glpi_connect_wire 
						WHERE end1 = '".$data['end1']."' 
						AND type = '".$device_type."'";
					$result2=$db->query($query2);
					if ($db->result($result2,0,0)==1){
						switch ($device_type){
							case MONITOR_TYPE:
								$mon=new Monitor();
								$mon->delete(array('ID'=>$data['end1']),0);
								break;
							case PRINTER_TYPE:
								$print=new Printer();
								$print->delete(array('ID'=>$data['end1']),0);

								break;
							case PERIPHERAL_TYPE:
								$per=new Peripheral();
								$per->delete(array('ID'=>$data['end1']),0);
								break;
						}
					}
				}
			}

			Disconnect($key);

			switch ($device_type){
				case MONITOR_TYPE:
					deleteInOcsArray($glpi_id,$key,"import_monitor");
					break;
				case PRINTER_TYPE:
					deleteInOcsArray($glpi_id,$key,"import_printer");
					break;
				case PERIPHERAL_TYPE:
					deleteInOcsArray($glpi_id,$key,"import_peripheral");
					break;
			}
		}
	}

}

/**
 * Update config of a new software
 *
 * This function create a new software in GLPI with some general datas.
 *
 *
 *@param $glpi_id integer : glpi computer id.
 *@param $ocs_id integer : ocs computer id (ID).
 *@param $cfg_ocs array : ocs config
 *@param $dohistory boolean : log changes ?
 *@param $import_software array : already imported softwares
 *
 *@return Nothing (void).
 *
 **/
function ocsUpdateSoftware($glpi_id,$ocs_id,$cfg_ocs,$import_software,$dohistory) {
	global $dbocs,$db;
	if($cfg_ocs["import_software"]){

		if ($cfg_ocs["use_soft_dict"])
			$query2 = "SELECT softwares.NAME AS INITNAME, dico_soft.FORMATTED AS NAME, 
				softwares.VERSION AS VERSION, softwares.PUBLISHER AS PUBLISHER 
				FROM softwares 
				INNER JOIN dico_soft ON (softwares.NAME = dico_soft.EXTRACTED) 
				WHERE softwares.HARDWARE_ID='$ocs_id'";
		else $query2 = "SELECT softwares.NAME AS INITNAME, softwares.NAME AS NAME, 
			softwares.VERSION AS VERSION, softwares.PUBLISHER AS PUBLISHER 
				FROM softwares 
				WHERE softwares.HARDWARE_ID='$ocs_id'";
		$already_imported=array();
		$result2 = $dbocs->query($query2);
		if ($dbocs->numrows($result2)>0)
			while ($data2 = $dbocs->fetch_array($result2)){
				$data2=clean_cross_side_scripting_deep(addslashes_deep($data2));
				$initname =  $data2["INITNAME"];
				$name= $data2["NAME"];
				$version = $data2["VERSION"];
				$publisher = $data2["PUBLISHER"];
				// Import Software
				if (!in_array($name,$already_imported)){ // Manage multiple software with the same name = only one install
					$already_imported[]=$name;
					if (!in_array($initname,$import_software)){

						$query_search = "SELECT ID 
							FROM glpi_software 
							WHERE name = '".$name."' ";
						$result_search = $db->query($query_search);
						if ($db->numrows($result_search)>0){
							$data = $db->fetch_array($result_search);
							$isNewSoft = $data["ID"];
						} else {
							$isNewSoft = 0;
						}

						if (!$isNewSoft) {
							$soft = new Software;
							$soft->fields["name"] = $name;
							$soft->fields["version"] = $version;
							if (!empty($publisher))
								$soft->fields["FK_glpi_enterprise"] = ocsImportEnterprise($publisher);
							$isNewSoft = $soft->addToDB();
						}
						if ($isNewSoft){
							$instID=installSoftware($glpi_id,ocsImportLicense($isNewSoft),'',$dohistory);
							addToOcsArray($glpi_id,array($instID=>$initname),"import_software");
						}

					} else { // Check if software always exists with is real name

						$id=array_search($initname,$import_software);
						unset($import_software[$id]);

						$query_name="SELECT glpi_software.ID as ID , glpi_software.name AS NAME 
							FROM glpi_inst_software 
							LEFT JOIN glpi_licenses ON (glpi_inst_software.license=glpi_licenses.ID) 
							LEFT JOIN glpi_software ON (glpi_licenses.sID = glpi_software.ID) 
							WHERE glpi_inst_software.ID='$id'";
						$result_name=$db->query($query_name);
						if ($db->numrows($result_name)==1){
							if ($db->result($result_name,0,"NAME")!=$name){
								$updates["name"]=$name;
								// No update version
								//$updates["version"]=$version;
								// No update publisher
								//if (!empty($publisher))
								//	$updates["FK_glpi_enterprise"] = ocsImportEnterprise($publisher);
								$updates["ID"]=$db->result($result_name,0,"ID");
								$soft=new Software();
								$soft->update($updates);
							}
						}
					}
				}
			} 

		// Disconnect Unexisting Items not found in OCS
		if (count($import_software)){

			foreach ($import_software as $key => $val){

				$query = "SELECT * 
					FROM glpi_inst_software 
					WHERE ID = '".$key."'";
				$result=$db->query($query);
				if ($db->numrows($result)>0)
					while ($data=$db->fetch_assoc($result)){
						$query2="SELECT COUNT(*) 
							FROM glpi_inst_software 
							WHERE license = '".$data['license']."'";
						$result2=$db->query($query2);
						if ($db->result($result2,0,0)==1){
							$lic=new License;
							$lic->getfromDB($data['license']);
							$query3="SELECT COUNT(*) 
								FROM glpi_licenses 
								WHERE sID='".$lic->fields['sID']."'";
							$result3=$db->query($query3);
							if ($db->result($result3,0,0)==1){
								$soft=new Software ();
								$soft->delete(array('ID'=>$lic->fields['sID']),0);
							}
							$lic->delete(array("ID"=>$data['license']));
						}
					}

				uninstallSoftware($key,$dohistory);
				deleteInOcsArray($glpi_id,$key,"import_software");
			}
		}
	}
}

/**
 * Import config of a new license
 *
 * This function create a new license in GLPI with some general datas.
 *
 *@param $software : id of a software.
 *
 *@return integer : inserted license id.
 *
 **/
function ocsImportLicense($software) {
	global $db,$langOcs;

	$query = "SELECT ID 
		FROM glpi_licenses 
		WHERE sid = '".$software."' 
		AND serial='global' ";
	$result = $db->query($query);
	if ($db->numrows($result)>0){
		$data = $db->fetch_array($result);
		$isNewLicc = $data["ID"];
	} else {
		$isNewLicc = 0;
	}
	if (!$isNewLicc) {
		$licc = new License;
		$licc->fields["sid"] = $software;
		$licc->fields["serial"] = "global";
		$isNewLicc = $licc->addToDB();
	}
	return($isNewLicc);
}


/**
 * Delete old licenses
 *
 * Delete all old licenses of a computer.
 *
 *@param $glpi_computer_id integer : glpi computer id.
 *
 *@return nothing.
 *
 **/
function ocsResetLicenses($glpi_computer_id) {
	global $db;

	$query = "SELECT * 
		FROM glpi_inst_software 
		WHERE cid = '".$glpi_computer_id."'";
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		while ($data=$db->fetch_assoc($result)){
			$query2="SELECT COUNT(*) 
				FROM glpi_inst_software 
				WHERE license = '".$data['license']."'";
			$result2=$db->query($query2);
			if ($db->result($result2,0,0)==1){
				$lic=new License;
				$lic->getfromDB($data['license']);
				$query3="SELECT COUNT(*) 
					FROM glpi_licenses 
					WHERE sID='".$lic->fields['sID']."'";
				$result3=$db->query($query3);
				if ($db->result($result3,0,0)==1){
					$soft=new Software();
					$soft->delete(array('ID'=>$lic->fields['sID']),1);
				}
				$lic->delete(array("ID"=>$data['license']));

			}
		}

		$query = "DELETE FROM glpi_inst_software 
			WHERE cid = '".$glpi_computer_id."'";
		$db->query($query);
	}

}

/**
 * Delete old devices settings
 *
 * Delete Old device settings.
 *
 *@param $device_type integer : device type identifier.
 *@param $glpi_computer_id integer : glpi computer id.
 *
 *@return nothing.
 *
 **/
function ocsResetDevices($glpi_computer_id, $device_type) {
	global $db;
	$query = "DELETE FROM glpi_computer_device 
		WHERE device_type = '".$device_type."' 
		AND FK_computers = '".$glpi_computer_id."'";
	$db->query($query);
}

/**
 * Delete old periphs
 *
 * Delete all old periphs for a computer.
 *
 *@param $glpi_computer_id integer : glpi computer id.
 *
 *@return nothing.
 *
 **/
function ocsResetPeriphs($glpi_computer_id) {

	global $db;

	$query = "SELECT * 
		FROM glpi_connect_wire 
		WHERE end2 = '".$glpi_computer_id."' 
		AND type = '".PERIPHERAL_TYPE."'";
	$result=$db->query($query);
	$per=new Peripheral();
	if ($db->numrows($result)>0){
		while ($data=$db->fetch_assoc($result)){
			$query2="SELECT COUNT(*) 
				FROM glpi_connect_wire 
				WHERE end1 = '".$data['end1']."' 
				AND type = '".PERIPHERAL_TYPE."'";
			$result2=$db->query($query2);
			if ($db->result($result2,0,0)==1){
				$per->delete(array('ID'=>$data['end1']),1);
			}
		}

		$query2 = "DELETE FROM glpi_connect_wire 
			WHERE end2 = '".$glpi_computer_id."' 
			AND type = '".PERIPHERAL_TYPE."'";
		$db->query($query2);
	}

}
/**
 * Delete old monitors
 *
 * Delete all old licenses of a computer.
 *
 *@param $glpi_computer_id integer : glpi computer id.
 *
 *@return nothing.
 *
 **/
function ocsResetMonitors($glpi_computer_id) {

	global $db;
	$query = "SELECT * 
		FROM glpi_connect_wire 
		WHERE end2 = '".$glpi_computer_id."' 
		AND type = '".MONITOR_TYPE."'";
	$result=$db->query($query);
	$mon=new Monitor();
	if ($db->numrows($result)>0){
		while ($data=$db->fetch_assoc($result)){
			$query2="SELECT COUNT(*) 
				FROM glpi_connect_wire 
				WHERE end1 = '".$data['end1']."' 
				AND type = '".MONITOR_TYPE."'";
			$result2=$db->query($query2);
			if ($db->result($result2,0,0)==1){
				$mon->delete(array('ID'=>$data['end1']),1);
			}
		}

		$query2 = "DELETE FROM glpi_connect_wire 
			WHERE end2 = '".$glpi_computer_id."' 
			AND type = '".MONITOR_TYPE."'";
		$db->query($query2);
	}

}
/**
 * Delete old printers
 *
 * Delete all old printers of a computer.
 *
 *@param $glpi_computer_id integer : glpi computer id.
 *
 *@return nothing.
 *
 **/
function ocsResetPrinters($glpi_computer_id) {

	global $db;

	$query = "SELECT * 
		FROM glpi_connect_wire 
		WHERE end2 = '".$glpi_computer_id."' 
		AND type = '".PRINTER_TYPE."'";
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		while ($data=$db->fetch_assoc($result)){
			$query2="SELECT COUNT(*) 
				FROM glpi_connect_wire 
				WHERE end1 = '".$data['end1']."' 
				AND type = '".PRINTER_TYPE."'";
			$result2=$db->query($query2);
			$printer=new Printer();
			if ($db->result($result2,0,0)==1){
				$printer->delete(array('ID'=>$data['end1']),1);
			}
		}

		$query2 = "DELETE FROM glpi_connect_wire 
			WHERE end2 = '".$glpi_computer_id."' 
			AND type = '".PRINTER_TYPE."'";
		$db->query($query2);
	}
}

/**
 * Delete old dropdown value
 *
 * Delete all old dropdown value of a computer.
 *
 *@param $glpi_computer_id integer : glpi computer id.
 *@param $field string : string of the computer table
 *@param $table string : dropdown table name
 *
 *@return nothing.
 *
 **/
function ocsResetDropdown($glpi_computer_id,$field,$table) {

	global $db;
	$query = "SELECT $field AS VAL 
		FROM glpi_computers 
		WHERE ID = '".$glpi_computer_id."'";
	$result=$db->query($query);
	if ($db->numrows($result)==1){
		$value=$db->result($result,0,"VAL");
		$query = "SELECT COUNT(*) AS CPT 
			FROM glpi_computers 
			WHERE $field = '$value'";
		$result=$db->query($query);
		if ($db->result($result,0,"CPT")==1){
			$query2 = "DELETE FROM $table 
				WHERE ID = '$value'";
			$db->query($query2);
		}
	}
}


?>
