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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

function ocsShowNewComputer($ocs_server_id, $advanced, $check, $start, $tolinked = 0) {
	global $DB, $DBocs, $LANG, $CFG_GLPI;

	if (!haveRight("ocsng", "w"))
		return false;

	$cfg_ocs = getOcsConf($ocs_server_id);

	$WHERE = "";
	if (!empty ($cfg_ocs["tag_limit"])) {
		$splitter = explode("$", $cfg_ocs["tag_limit"]);
		if (count($splitter)) {
			$WHERE = "WHERE TAG='" . $splitter[0] . "' ";
			for ($i = 1; $i < count($splitter); $i++)
				$WHERE .= " OR TAG='" .
				$splitter[$i] . "' ";
		}
	}

	$query_ocs = "SELECT hardware.*, accountinfo.TAG AS TAG 
				FROM hardware 
				INNER JOIN accountinfo ON (hardware.ID = accountinfo.HARDWARE_ID) 
				$WHERE 
				ORDER BY hardware.NAME";
	$result_ocs = $DBocs->query($query_ocs);

	// Existing OCS - GLPI link
	$query_glpi = "SELECT * 
				FROM glpi_ocs_link WHERE ocs_server_id=" . $ocs_server_id;
	$result_glpi = $DB->query($query_glpi);

	// Computers existing in GLPI
	$query_glpi_comp = "SELECT ID,name 
				FROM glpi_computers 
				WHERE deleted = '0' AND is_template='0'";
	$result_glpi_comp = $DB->query($query_glpi_comp);

	if ($DBocs->numrows($result_ocs) > 0) {

		// Get all hardware from OCS DB
		$hardware = array ();
		while ($data = $DBocs->fetch_array($result_ocs)) {
			$data = clean_cross_side_scripting_deep(addslashes_deep($data));
			$hardware[$data["ID"]]["date"] = $data["LASTDATE"];
			$hardware[$data["ID"]]["name"] = $data["NAME"];
			$hardware[$data["ID"]]["TAG"] = $data["TAG"];
			$hardware[$data["ID"]]["ID"] = $data["ID"];
		}

		// Get all links between glpi and OCS
		$already_linked = array ();
		if ($DB->numrows($result_glpi) > 0) {
			while ($data = $DBocs->fetch_array($result_glpi)) {
				$already_linked[$data["ocs_id"]] = $data["last_update"];
			}
		}

		// Get all existing computers name in GLPI
		$computer_names = array ();
		if ($DB->numrows($result_glpi_comp) > 0) {
			while ($data = $DBocs->fetch_array($result_glpi_comp)) {
				$computer_names[$data["name"]] = $data["ID"];
			}
		}

		// Clean $hardware from already linked element
		if (count($already_linked) > 0) {
			foreach ($already_linked as $ID => $date) {
				if (isset ($hardware[$ID]) && isset ($already_linked[$ID]))
					unset ($hardware[$ID]);
			}
		}

		if ($tolinked && count($hardware)) {
			echo "<div align='center'><strong>" . $LANG["ocsng"][22] . "</strong></div>";
		}

		echo "<div align='center'>";
		if (($numrows = count($hardware)) > 0) {

			$parameters = "check=$check";
			printPager($start, $numrows, $_SERVER['PHP_SELF'], $parameters);

			// delete end 
			array_splice($hardware, $start + $CFG_GLPI["list_limit"]);
			// delete begin
			if ($start > 0)
				array_splice($hardware, 0, $start);

			if (!$tolinked) {
				echo "<form method='post' name='ocsng_import_mode' id='ocsng_import_mode' action='" . $_SERVER['PHP_SELF'] . "'>";

				echo "<table class='tab_cadre'>";
				echo "<tr><th>" . $LANG["ocsng"][41] . "</th></tr>";
				echo "<tr class='tab_bg_1'>";
				echo "<td align='center'>";

				if ($advanced)
					$status = "false";
				else
					$status = "true";

				echo "<a href='" . $_SERVER['PHP_SELF'] . "?change_import_mode=" . $status . "'>";
				if ($advanced)
					echo $LANG["ocsng"][38];
				else
					echo $LANG["ocsng"][37];
				echo "</a></td>";
				echo "</tr></table></form><br>";
			}

			echo "<strong>" . $LANG["ocsconfig"][18] . "</strong><br>";
			echo "<form method='post' name='ocsng_form' id='ocsng_form' action='" . $_SERVER['PHP_SELF'] . "'>";
			if ($tolinked == 0)
				echo "<a href='" . $_SERVER['PHP_SELF'] . "?check=all&amp;start=$start' onclick= \"if ( markAllRows('ocsng_form') ) return false;\">" . $LANG["buttons"][18] . "</a>&nbsp;/&nbsp;<a href='" . $_SERVER['PHP_SELF'] . "?check=none&amp;start=$start' onclick= \"if ( unMarkAllRows('ocsng_form') ) return false;\">" . $LANG["buttons"][19] . "</a>";

			echo "<table class='tab_cadre'>";

			echo "<tr><th>" . $LANG["ocsng"][5] . "</th><th>" . $LANG["common"][27] . "</th><th>TAG</th>";
			if ($advanced && !$tolinked) {
				echo "<th>" . $LANG["ocsng"][40] . "</th>";
				echo "<th>" . $LANG["ocsng"][36] . "</th>";
			}
			echo "<th>&nbsp;</th></tr>";

			echo "<tr class='tab_bg_1'><td colspan='" . ($advanced ? 6 : 4) . "' align='center'>";
			echo "<input class='submit' type='submit' name='import_ok' value='" . $LANG["buttons"][37] . "'>";
			echo "</td></tr>";

			$rule = new OcsRuleCollection($ocs_server_id);

			foreach ($hardware as $ID => $tab) {
				$comp = new Computer;
				$comp->fields["ID"] = $tab["ID"];

				$data = array ();
				if ($advanced && !$tolinked)
					$data = $rule->processAllRules(array (), array (), $tab["ID"]);

				echo "<tr class='tab_bg_2'><td>" . $tab["name"] . "</td><td>" . convDateTime($tab["date"]) . "</td><td>" . $tab["TAG"] . "</td>";

				if ($advanced && !$tolinked) {
					if (!isset ($data['FK_entities'])) {
						echo "<td align='center'><img src=\"" . GLPI_ROOT . "/pics/redbutton.png\"></td>";
						$data['FK_entities'] = -1;
					} else
						echo "<td align='center'><img src=\"" . GLPI_ROOT . "/pics/export.png\"></td>";

					echo "<td>";
					dropdownValue("glpi_entities", "toimport_entities[" . $tab["ID"] . "]=" . $data['FK_entities'], $data['FK_entities'], 0);
					echo "</td>";
				}

				echo "<td>";
				if ($tolinked == 0)
					echo "<input type='checkbox' name='toimport[" . $tab["ID"] . "]' " . ($check == "all" ? "checked" : "") . ">";
				else {
					if (isset ($computer_names[$tab["name"]]))
						dropdownValue("glpi_computers", "tolink[" .
						$tab["ID"] . "]", $computer_names[$tab["name"]]);
					else
						dropdown("glpi_computers", "tolink[" .
						$tab["ID"] . "]");
				}
				echo "</td>";

				echo "</tr>";
			}
			echo "<tr class='tab_bg_1'><td colspan='" . ($advanced ? 6 : 4) . "' align='center'>";
			echo "<input class='submit' type='submit' name='import_ok' value='" . $LANG["buttons"][37] . "'>";
			echo "<input type=hidden name='ocs_server_id' value='" . $ocs_server_id . "'>";
			echo "</td></tr>";
			echo "</table>";
			echo "</form>";

			printPager($start, $numrows, $_SERVER['PHP_SELF'], $parameters);

		} else
			echo "<strong>" . $LANG["ocsng"][9] . "</strong>";

		echo "</div>";

	} else
		echo "<div align='center'><strong>" . $LANG["ocsng"][9] . "</strong></div>";
}

/**
 * Make the item link between glpi and ocs.
 *
 * This make the database link between ocs and glpi databases
 *
 *@param $ocs_id integer : ocs item unique id.
 *param $glpi_computer_id integer : glpi computer id
 *@param $ocs_server_id integer : ocs server id
 *
 *@return integer : link id.
 *
 **/
function ocsLink($ocs_id, $ocs_server_id, $glpi_computer_id) {
	global $DB, $DBocs;

	checkOCSconnection($ocs_server_id);

	// Need to get device id due to ocs bug on duplicates
	$query_ocs = "SELECT * 
				FROM hardware
				WHERE ID = '$ocs_id';";
	$result_ocs = $DBocs->query($query_ocs);
	$data = $DBocs->fetch_array($result_ocs);

	$query = "INSERT INTO glpi_ocs_link 
				(glpi_id,ocs_id,ocs_deviceid,last_update,ocs_server_id) 
				VALUES ('" . $glpi_computer_id . "','" . $ocs_id . "','" . $data["DEVICEID"] . "','" . $_SESSION["glpi_currenttime"] . "','" . $ocs_server_id . "')";
	$result = $DB->query($query);

	if ($result) {
		return ($DB->insert_id());
	} else {
		$query = "SELECT ID
		                        FROM glpi_ocs_link
		                        WHERE ocs_id = '$ocs_id' AND ocs_server_id='" . $ocs_server_id . "';";
		$result = $DB->query($query);
		$data = $DB->fetch_array($result);
		if ($data['ID']) {
			return $data['ID'];
		} else {
			return false;
		}
	}
}

function ocsCheckConfig($what=1) {
	global $DBocs;

	# Check OCS version
	if ($what & 1) {
		$result = $DBocs->query("SELECT TVALUE FROM config WHERE NAME='GUI_VERSION'");
		if ($DBocs->numrows($result) != 1 || $DBocs->result($result, 0, 0) < 4020) {
			return false;
		}
	}
	# Check TRACE_DELETED in CONFIG
	if ($what & 2) {
		$result = $DBocs->query("SELECT IVALUE FROM config WHERE NAME='TRACE_DELETED'");
		if ($DBocs->numrows($result) != 1 || $DBocs->result($result, 0, 0) != 1) {
			$query = "UPDATE config SET IVALUE='1' WHERE NAME='TRACE_DELETED'";

			if (!$DBocs->query($query)) return false;			
		}
	}
	# Check write access on hardware.CHECKSUM
	if ($what & 4) {
		if (!$DBocs->query("UPDATE hardware SET CHECKSUM = CHECKSUM LIMIT 1")) {
			return false;			
		}
	}
	# Check delete access on deleted_equiv
	if ($what & 8) {
		if (!$DBocs->query("DELETE FROM deleted_equiv LIMIT 0")) {
			return false;			
		}
	}

	return true;
}
function ocsManageDeleted($ocs_server_id) {
	global $DB, $DBocs;

	if (!(checkOCSconnection($ocs_server_id) && ocsCheckConfig(1))) {
		return false;
	}

	$query = "SELECT * FROM deleted_equiv";
	$result = $DBocs->query($query);
	if ($DBocs->numrows($result)) {
		$deleted = array ();
		while ($data = $DBocs->fetch_array($result)) {
			$deleted[$data["DELETED"]] = $data["EQUIVALENT"];
		}

		$query = "TRUNCATE TABLE deleted_equiv";
		$result = $DBocs->query($query);

		if (count($deleted))
			foreach ($deleted as $del => $equiv) {


				if (!empty ($equiv)) { // New name


					// Get hardware due to bug of duplicates management of OCS
					if (ereg("-", $equiv)) {
						$query_ocs = "SELECT * 
								FROM hardware 
								WHERE DEVICEID='$equiv'";
						$result_ocs = $DBocs->query($query_ocs);
						
						if ($data = $DBocs->fetch_array($result_ocs)) {

							$query = "UPDATE glpi_ocs_link 
									SET ocs_id='" . $data["ID"] . "', ocs_deviceid='" . $data["DEVICEID"] . "' 
									WHERE ocs_deviceid='$del' AND ocs_server_id='$ocs_server_id'";
							$DB->query($query);

							//Update hardware checksum due to a bug in OCS 
 							//(when changing netbios name, software checksum is set instead of hardware checksum...)
							$querychecksum = "UPDATE hardware 
									SET CHECKSUM = (CHECKSUM | ".pow(2, HARDWARE_FL).") 
									WHERE ID='".$data["ID"]."'";
							$DBocs->query($querychecksum);

						}

					} else {
						$query_ocs = "SELECT * 
								FROM hardware 
								WHERE ID='$equiv'";

						$result_ocs = $DBocs->query($query_ocs);
						if ($data = $DBocs->fetch_array($result_ocs)){
							
							$query = "UPDATE glpi_ocs_link 
								SET ocs_id='" . $data["ID"] . "', ocs_deviceid='" . $data["DEVICEID"] . "' 
								WHERE ocs_id='$del' AND ocs_server_id='$ocs_server_id'";
							$DB->query($query);

							//Update hardware checksum due to a bug in OCS 
 							//(when changing netbios name, software checksum is set instead of hardware checksum...)
							$querychecksum = "UPDATE hardware 
									SET CHECKSUM = (CHECKSUM | ".pow(2, HARDWARE_FL).") 
									WHERE ID='".$data["ID"]."'";
							$DBocs->query($querychecksum);
						}

					}

					$sql_id = "SELECT glpi_id FROM glpi_ocs_link WHERE ocs_id=".$data["ID"]." AND ocs_server_id=$ocs_server_id";
					$res_id = $DB->query($sql_id);

					//Add history to indicates that the ocs_id changed
					$changes[0]='0';
					//Old ocs_id
					$changes[1]=$del;
					//New ocs_id
					$changes[2]=$data["ID"];
					historyLog ($DB->result($res_id,0,"glpi_id"),COMPUTER_TYPE,$changes,null,HISTORY_OCS_IDCHANGED);
					
				} else { // Deleted
					if (ereg("-", $del))
						$query = "SELECT * 
														FROM glpi_ocs_link 
														WHERE ocs_deviceid='$del' AND ocs_server_id='$ocs_server_id'";
					else
						$query = "SELECT * 
														FROM glpi_ocs_link 
														WHERE ocs_id='$del' AND ocs_server_id='$ocs_server_id'";
					$result = $DB->query($query);
					if ($DB->numrows($result)) {
						$data = $DB->fetch_array($result);
						$comp = new Computer();
						$comp->delete(array (
							"ID" => $data["glpi_id"],
							"_from_ocs" => 1
						), 0);

						//Add history to indicates that the machine was deleted from OCS
						$changes[0]='0';
						$changes[1]=$data["ocs_id"];
						$changes[2]="";
						historyLog ($data["glpi_id"],COMPUTER_TYPE,$changes,null,HISTORY_OCS_DELETE);

						$query = "DELETE FROM glpi_ocs_link WHERE ID ='" . $data["ID"] . "'";
						$DB->query($query);
					}
				}
			}
	}
}

function ocsImportComputer($ocs_id, $ocs_server_id, $lock = 0, $defaultentity = -1,$canlink=0) {
	global $DBocs, $DB;

	checkOCSconnection($ocs_server_id);

		$comp = new Computer;

		// Set OCS checksum to max value
		$query = "UPDATE hardware SET CHECKSUM='" . MAX_OCS_CHECKSUM . "' WHERE ID='$ocs_id'";
		$DBocs->query($query);

		//No entity predefined, check rules
		if ($defaultentity == -1) {
			//Try to affect computer to an entity
			$rule = new OcsRuleCollection($ocs_server_id);
			$data = array ();
			$data = $rule->processAllRules(array (), array (), $ocs_id);
		} else
			//An entity has already been defined via the web interface
			$data['FK_entities'] = $defaultentity;

		//Try to match all the rules, return the first good one, or null if not rules matched
		if (isset ($data['FK_entities']) && $data['FK_entities'] >= 0) {

			if ($lock) {
				while (!$fp = setEntityLock($data['FK_entities']))
					sleep(2);
			}

			//Check if machine could be linked with another one already in DB
			if ($canlink)
			{
				$glpi_id = getMachinesAlreadyInGLPI($ocs_id,$ocs_server_id,$data['FK_entities']);
				//One machine was found -> link
				if ($glpi_id != -1)
				{
					ocsLinkComputer($ocs_id,$ocs_server_id,$glpi_id,1);
					return 3;
				}
			}
			
			//New machine to import
			$query = "SELECT * FROM hardware WHERE ID='$ocs_id'";
			$result = $DBocs->query($query);
			if ($result && $DBocs->numrows($result) == 1) {
	
				$line = $DBocs->fetch_array($result);
				$line = clean_cross_side_scripting_deep(addslashes_deep($line));
	
				$cfg_ocs = getOcsConf($ocs_server_id);
				$input = array ();
				$input["FK_entities"] = $data['FK_entities'];
				$input["name"] = $line["NAME"];
				$input["ocs_import"] = 1;
				$input["state"] = $cfg_ocs["default_state"];
				$input["_from_ocs"] = 1;
				$glpi_id = $comp->add($input);
	
				$ocs_id = $line['ID'];
	
				$changes[0]='0';
				$changes[1]="";
				$changes[2]=$ocs_id;
				historyLog ($glpi_id,COMPUTER_TYPE,$changes,null,HISTORY_OCS_IMPORT);
				
				if ($idlink = ocsLink($line['ID'], $ocs_server_id, $glpi_id)) {
					ocsUpdateComputer($idlink, $ocs_server_id, 0);
				}
	
			}
	
			if ($lock)
				removeEntityLock($data['FK_entities'], $fp);
	
			//Return code to indicates that the machine was imported
			return 1;	
		}
		else
			//Return code to indicates that the machine was not imported because it doesn't matched rules
			return 2;
}

function ocsLinkComputer($ocs_id, $ocs_server_id, $glpi_id,$glpi_link=0) {
	global $DB, $DBocs, $LANG;
	checkOCSconnection($ocs_server_id);

	$query = "SELECT *  
				FROM glpi_ocs_link
				WHERE glpi_id='$glpi_id'";

	$result = $DB->query($query);
	$ocs_exists = true;
	$numrows = $DB->numrows($result);
	// Already link - check if the OCS computer already exists
	if ($numrows > 0) {
		$data = $DB->fetch_assoc($result);
		$query = "SELECT * 
							FROM hardware 
							WHERE ID='" . $data["ocs_id"] . "'";
		$result_ocs = $DBocs->query($query);
		// Not found
		if ($DBocs->numrows($result_ocs) == 0) {
			$ocs_exists = false;
			$query = "DELETE FROM glpi_ocs_link 
										WHERE ID='" . $data["ID"] . "'";
			$DB->query($query);
		}
	}

	if ($glpi_link || (!$ocs_exists || $numrows == 0)) {

		$ocsConfig = getOcsConf($ocs_server_id);
		
		// Set OCS checksum to max value
		$query = "UPDATE hardware 
							SET CHECKSUM='" . MAX_OCS_CHECKSUM . "' 
							WHERE ID='$ocs_id'";
		$DBocs->query($query);

		if ($idlink = ocsLink($ocs_id, $ocs_server_id, $glpi_id)) {
		
			$comp = new Computer;
			$input["ID"] = $glpi_id;
			$input["ocs_import"] = 1;
			$input["_from_ocs"] = 1;
			
			if ($glpi_link)
				//Change the state
				$input["state"] = $ocsConfig["default_state"];
			
			$comp->update($input);
		
			// Reset using GLPI Config
			$cfg_ocs = getOcsConf($ocs_server_id);
			$query = "SELECT * 
										FROM hardware 
										WHERE ID='$ocs_id'";
			$result = $DBocs->query($query);
			$line = $DBocs->fetch_array($result);

			if ($cfg_ocs["import_general_os"])
				ocsResetDropdown($glpi_id, "os", "glpi_dropdown_os");
			if ($cfg_ocs["import_device_processor"])
				ocsResetDevices($glpi_id, PROCESSOR_DEVICE);
			if ($cfg_ocs["import_device_iface"])
				ocsResetDevices($glpi_id, NETWORK_DEVICE);
			if ($cfg_ocs["import_device_memory"])
				ocsResetDevices($glpi_id, RAM_DEVICE);
			if ($cfg_ocs["import_device_hdd"])
				ocsResetDevices($glpi_id, HDD_DEVICE);
			if ($cfg_ocs["import_device_sound"])
				ocsResetDevices($glpi_id, SND_DEVICE);
			if ($cfg_ocs["import_device_gfxcard"])
				ocsResetDevices($glpi_id, GFX_DEVICE);
			if ($cfg_ocs["import_device_drives"])
				ocsResetDevices($glpi_id, DRIVE_DEVICE);
			if ($cfg_ocs["import_device_modems"] || $cfg_ocs["import_device_ports"])
				ocsResetDevices($glpi_id, PCI_DEVICE);
			if ($cfg_ocs["import_software"])
				ocsResetLicenses($glpi_id);
			if ($cfg_ocs["import_periph"])
				ocsResetPeriphs($glpi_id);
			if ($cfg_ocs["import_monitor"] == 1) // Only reset monitor as global in unit management try to link monitor with existing
				ocsResetMonitors($glpi_id);
			if ($cfg_ocs["import_printer"])
				ocsResetPrinters($glpi_id);
			if ($cfg_ocs["import_registry"])
				ocsResetRegistry($glpi_id);

			$changes[0]='0';
			$changes[1]="";
			$changes[2]=$ocs_id;
			historyLog ($glpi_id,COMPUTER_TYPE,$changes,null,HISTORY_OCS_LINK);
			
			ocsUpdateComputer($idlink, $ocs_server_id, 0);
		}
	} else {
		$_SESSION["MESSAGE_AFTER_REDIRECT"] = $ocs_id . " - " . $LANG["ocsng"][23];
	}

}


function ocsProcessComputer($ocs_id, $ocs_server_id, $lock = 0, $defaultentity = -1,$canlink=0) {
	global $DBocs, $DB;

	checkOCSconnection($ocs_server_id);

	$comp = new Computer;
	
	//Check it machine is already present AND was imported by OCS
	$query = "SELECT ID,glpi_id,ocs_id FROM glpi_ocs_link WHERE ocs_id = '$ocs_id' AND ocs_server_id='" . $ocs_server_id . "';";
	$result_glpi_ocs_link = $DB->query($query);
	if ($DB->numrows($result_glpi_ocs_link)) {
		$datas = $DB->fetch_array($result_glpi_ocs_link);
		ocsUpdateComputer($datas["ID"], $ocs_server_id, 1, 0);

		//Return code to indicates that the machine was synchronized
		return 0;
	} else
		return ocsImportComputer($ocs_id, $ocs_server_id, $lock, $defaultentity,$canlink);
}

function getMachinesAlreadyInGLPI($ocs_id,$ocs_server_id,$entity)
{
	global $DB,$DBocs;
	$conf = getOcsConf($ocs_server_id);
	$sql_and = "";
	$sql_fields = "";
	$sql_from ="hardware";
	$first = true;
	$ocsParams = array();
	
	if ($conf["glpi_link_enabled"])
	{
		//Build the request against OCS database to get the machine's informations
		if ( $conf["link_ip"] || $conf["link_mac_address"])
		{
			$sql_from.=" ,networks";
			$sql_and.=" AND hardware.ID=networks.HARDWARE_ID";
		}	
		if ($conf["link_ip"])
		{
			$sql_fields.=(!$first?",":'')."networks.IPADDRESS";
			$first=false;
			$ocsParams["IPADDRESS"] = array();
		}
		if ($conf["link_mac_address"])
		{
			$sql_fields.=(!$first?",":'')."networks.MACADDR";
			$first=false;
			$ocsParams["MACADDR"] = array();
		}
		if ($conf["link_serial"])
		{
			$sql_from.=",bios";
			$sql_and .= " AND bios.HARDWARE_ID=hardware.ID";
			$sql_fields.=(!$first?",":'')."bios.SSN";
			$ocsParams["SSN"] = array();
			$first=false;
		}
		if ($conf["link_name"] > 0)
		{
			$sql_fields.=(!$first?",":'')."hardware.NAME";
			$first=false;
			$ocsParams["NAME"] = array();
		}
		
		//Execute request
		$sql = "SELECT ".$sql_fields." from ".$sql_from." WHERE hardware.ID=".$ocs_id." ".$sql_and;
		$result = $DBocs->query($sql);
		
		//Get the list of parameters
		
		while ($dataOcs = $DB->fetch_array($result))
		{
			if ($conf["link_ip"])
				if (!empty($dataOcs["IPADDRESS"]) && !in_array($dataOcs["IPADDRESS"],$ocsParams["IPADDRESS"]))
					$ocsParams["IPADDRESS"][]= $dataOcs["IPADDRESS"];
			if ($conf["link_mac_address"])
				if (!empty($dataOcs["MACADDR"]) && !in_array($dataOcs["MACADDR"],$ocsParams["MACADDR"]))
					$ocsParams["MACADDR"][]= $dataOcs["MACADDR"];
			if ($conf["link_name"] > 0)
				if (!empty($dataOcs["NAME"]) && !in_array($dataOcs["NAME"],$ocsParams["NAME"]))
					$ocsParams["NAME"][]= $dataOcs["NAME"];
			if ($conf["link_serial"])
				if (!empty($dataOcs["SSN"]) && !in_array($dataOcs["SSN"],$ocsParams["SSN"]))
					$ocsParams["SSN"][]= $dataOcs["SSN"];
		}

		//Build the request to check if the machine exists in GLPI
		$sql_and = "";
		$sql_from = "";
		if ( $conf["link_ip"] || $conf["link_mac_address"])
		{
			$sql_from.=" ,glpi_networking_ports";
			$sql_and.=" AND glpi_computers.ID=glpi_networking_ports.on_device AND glpi_networking_ports.device_type=".COMPUTER_TYPE;
		}	
		if ($conf["link_ip"])
		{
			if (empty($ocsParams["IPADDRESS"]))
				return -1;
			else
			{	
				$sql_and.=" AND glpi_networking_ports.ifaddr IN ";
				for ($i=0; $i < count($ocsParams["IPADDRESS"]);$i++)
					$sql_and .= ($i>0 ? ',"' : '("').$ocsParams["IPADDRESS"][$i].'"';
				$sql_and.=")";
			}			
		}
		if ($conf["link_mac_address"])
		{
			if (empty($ocsParams["MACADDR"]))
				return -1;
			else
			{	
				$sql_and.=" AND glpi_networking_ports.ifmac IN ";
				for ($i=0; $i < count($ocsParams["MACADDR"]);$i++)
					$sql_and .= ($i>0 ? ',"' : '("').$ocsParams["MACADDR"][$i].'"';
				$sql_and.=")";
			}
		}
		if ($conf["link_name"] > 0)
		{
			//Search only computers with blank name
			if ($conf["link_name"] == 2)
				$sql_and .= " AND glpi_computers.name=\"\"";
			else
			{	
				if (empty($ocsParams["NAME"]))
					return -1;
				else
					$sql_and .= " AND glpi_computers.name=\"".$ocsParams["NAME"][0]."\"";
			}
		}
		if ($conf["link_serial"])
		{
			if (empty($ocsParams["SSN"]))
				return -1;
			else
				$sql_and .= (!$first?" AND ":' ')."glpi_computers.serial=\"".$ocsParams["SSN"][0]."\"";
		}
		if ($conf["link_if_status"] > 0)
			$sql_and .= " AND glpi_computers.state=".$conf["link_if_status"];
		
		$sql_glpi = "SELECT glpi_computers.ID FROM glpi_computers ".$sql_from." WHERE FK_entities=".$entity.$sql_and;
		$result_glpi = $DB->query($sql_glpi);
		if ($DB->numrows($result_glpi) > 0)
			return $DB->result($result_glpi,0,"ID");
		else
			return -1;	
	}
	else
		return -1;
	
		
}
function ocsUpdateComputer($ID, $ocs_server_id, $dohistory, $force = 0) {
	global $DB, $DBocs, $CFG_GLPI;

	checkOCSconnection($ocs_server_id);

	$cfg_ocs = getOcsConf($ocs_server_id);

	$query = "SELECT * 
				FROM glpi_ocs_link 
				WHERE ID='$ID' and ocs_server_id=" . $ocs_server_id;
	$result = $DB->query($query);
	if ($DB->numrows($result) == 1) {

		$line = $DB->fetch_assoc($result);

		$comp = new Computer;
		$comp->getFromDB($line["glpi_id"]);

		// Get OCS ID 
		$query_ocs = "SELECT * 
							FROM hardware 
							WHERE ID='" . $line['ocs_id'] . "'";
		$result_ocs = $DBocs->query($query_ocs);
		// Need do history to be 2 not to lock fields
		if ($dohistory) {
			$dohistory = 2;
		}
		if ($DBocs->numrows($result_ocs) == 1) {
			$data_ocs = $DBocs->fetch_array($result_ocs);

			// update last_update and and last_ocs_update
			$query = "UPDATE glpi_ocs_link 
					SET last_update='" . $_SESSION["glpi_currenttime"] . "', last_ocs_update='" . $data_ocs["LASTCOME"] . "' 
					WHERE ID='$ID'";
			$DB->query($query);

			if ($force) {

				$ocs_checksum = MAX_OCS_CHECKSUM;
				$query_ocs = "UPDATE hardware 
													SET CHECKSUM= (" . MAX_OCS_CHECKSUM . ") 
													WHERE ID='" . $line['ocs_id'] . "'";
				$DBocs->query($query_ocs);
			} else {
				$ocs_checksum = $data_ocs["CHECKSUM"];
			}

			$mixed_checksum = intval($ocs_checksum) & intval($cfg_ocs["checksum"]);

			/*			echo "OCS CS=".decbin($ocs_checksum)." - $ocs_checksum<br>";
						  echo "GLPI CS=".decbin($cfg_ocs["checksum"])." - ".$cfg_ocs["checksum"]."<br>";
						  echo "MIXED CS=".decbin($mixed_checksum)." - $mixed_checksum <br>";
			 */
			// Is an update to do ?
			if ($mixed_checksum) {
				// Get updates on computers :
				$computer_updates = importArrayFromDB($line["computer_update"]);

				// Update Administrative informations
				ocsUpdateAdministrativeInfo($line['glpi_id'], $line['ocs_id'], $ocs_server_id, $cfg_ocs, $computer_updates, $comp->fields['FK_entities'], $dohistory);

				if ($mixed_checksum & pow(2, HARDWARE_FL))
					ocsUpdateHardware($line['glpi_id'], $line['ocs_id'], $ocs_server_id, $cfg_ocs, $computer_updates, $dohistory);
				if ($mixed_checksum & pow(2, BIOS_FL))
					ocsUpdateBios($line['glpi_id'], $line['ocs_id'], $ocs_server_id, $cfg_ocs, $computer_updates, $dohistory);
				// Get import devices
				$import_device = importArrayFromDB($line["import_device"]);
				if ($mixed_checksum & pow(2, MEMORIES_FL))
					ocsUpdateDevices(RAM_DEVICE, $line['glpi_id'], $line['ocs_id'], $ocs_server_id, $cfg_ocs, $import_device, '', $dohistory);
				if ($mixed_checksum & pow(2, STORAGES_FL)) {
					ocsUpdateDevices(HDD_DEVICE, $line['glpi_id'], $line['ocs_id'], $ocs_server_id, $cfg_ocs, $import_device, '', $dohistory);
					ocsUpdateDevices(DRIVE_DEVICE, $line['glpi_id'], $line['ocs_id'], $ocs_server_id, $cfg_ocs, $import_device, '', $dohistory);
				}

				if ($mixed_checksum & pow(2, HARDWARE_FL))
					ocsUpdateDevices(PROCESSOR_DEVICE, $line['glpi_id'], $line['ocs_id'], $ocs_server_id, $cfg_ocs, $import_device, '', $dohistory);
				if ($mixed_checksum & pow(2, VIDEOS_FL))
					ocsUpdateDevices(GFX_DEVICE, $line['glpi_id'], $line['ocs_id'], $ocs_server_id, $cfg_ocs, $import_device, '', $dohistory);
				if ($mixed_checksum & pow(2, SOUNDS_FL))
					ocsUpdateDevices(SND_DEVICE, $line['glpi_id'], $line['ocs_id'], $ocs_server_id, $cfg_ocs, $import_device, '', $dohistory);

				if ($mixed_checksum & pow(2, NETWORKS_FL)) {
					$import_ip = importArrayFromDB($line["import_ip"]);
					ocsUpdateDevices(NETWORK_DEVICE, $line['glpi_id'], $line['ocs_id'], $ocs_server_id, $cfg_ocs, $import_device, $import_ip, $dohistory);
				}
				if ($mixed_checksum & pow(2, MODEMS_FL) || $mixed_checksum & pow(2, PORTS_FL))
					ocsUpdateDevices(PCI_DEVICE, $line['glpi_id'], $line['ocs_id'], $ocs_server_id, $cfg_ocs, $import_device, '', $dohistory);

				if ($mixed_checksum & pow(2, MONITORS_FL)) {
					// Get import monitors
					$import_monitor = importArrayFromDB($line["import_monitor"]);
					ocsUpdatePeripherals(MONITOR_TYPE, $comp->fields["FK_entities"], $line['glpi_id'], $line['ocs_id'], $ocs_server_id, $cfg_ocs, $import_monitor, $dohistory);
				}

				if ($mixed_checksum & pow(2, PRINTERS_FL)) {
					// Get import printers
					$import_printer = importArrayFromDB($line["import_printers"]);
					ocsUpdatePeripherals(PRINTER_TYPE, $comp->fields["FK_entities"], $line['glpi_id'], $line['ocs_id'], $ocs_server_id, $cfg_ocs, $import_printer, $dohistory);
				}

				if ($mixed_checksum & pow(2, INPUTS_FL)) {
					// Get import monitors
					$import_peripheral = importArrayFromDB($line["import_peripheral"]);
					ocsUpdatePeripherals(PERIPHERAL_TYPE, $comp->fields["FK_entities"], $line['glpi_id'], $line['ocs_id'], $ocs_server_id, $cfg_ocs, $import_peripheral, $dohistory);
				}
				if ($mixed_checksum & pow(2, SOFTWARES_FL)) {
					// Get import monitors
					$import_software = importArrayFromDB($line["import_software"]);
					ocsUpdateSoftware($line['glpi_id'], $comp->fields["FK_entities"], $line['ocs_id'], $ocs_server_id, $cfg_ocs, $import_software, $dohistory);
				}
				if ($mixed_checksum & pow(2, REGISTRY_FL)) {
					//import registry entries not needed
					ocsUpdateRegistry($line['glpi_id'], $line['ocs_id'], $ocs_server_id, $cfg_ocs);
				}

				// Update OCS Cheksum 
				$query_ocs = "UPDATE hardware 
													SET CHECKSUM= (CHECKSUM - $mixed_checksum) 
													WHERE ID='" . $line['ocs_id'] . "'";
				$DBocs->query($query_ocs);
				$comp = new Computer();
				$comp->cleanCache($line['glpi_id']);
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

	global $DB, $CACHE_CFG;

	//if ($data = $CACHE_CFG->get("CFG_OCSGLPI_$id", "GLPI_CFG")) {
	//	return $data;
	//} else {
	$query = "SELECT * 
						FROM glpi_ocs_config 
						WHERE ID='$id'";
	$result = $DB->query($query);
	if ($result)
		$data = $DB->fetch_assoc($result);
	else
		$data = 0;
	//	$CACHE_CFG->save($data, "CFG_OCSGLPI_$id", "GLPI_CFG");
	return $data;
	//}
}

/**
 * Get numbero of OCSNG mode configurations
 *
 * Get number of OCS configurations
 *
 **/

function getNumberOfOcsConfigs() {
	global $DB, $CACHE_CFG;

	$sql = "SELECT ID FROM glpi_ocs_config";
	$result = $DB->query($sql);
	if ($DB->numrows($result) > 0)
		return size($DB->fetch_array($result));
	else
		return 0;
}

/**
 * Update the computer hardware configuration
 *
 * Update the computer hardware configuration
 *
 *@param $ocs_id integer : glpi computer id
 *@param $glpi_id integer : ocs computer id.
 *@param $ocs_server_id integer : ocs server id
 *@param $cfg_ocs array : ocs config
 *@param $computer_updates array : already updated fields of the computer
 *@param $dohistory log updates on history ? 
 *
 *@return nothing.
 *
 **/
function ocsUpdateHardware($glpi_id, $ocs_id, $ocs_server_id, $cfg_ocs, $computer_updates, $dohistory = 2) {
	global $LANG, $DB, $DBocs;

	checkOCSconnection($ocs_server_id);

	$query = "SELECT * 
				FROM hardware 
				WHERE ID='" . $ocs_id . "'";
	$result = $DBocs->query($query);
	if ($DBocs->numrows($result) == 1) {

		$line = $DBocs->fetch_assoc($result);
		$line = clean_cross_side_scripting_deep(addslashes_deep($line));
		$compudate = array ();

		if ($cfg_ocs["import_os_serial"] && !in_array("os_license_number", $computer_updates)) {
			if (!empty ($line["WINPRODKEY"]))
				$compupdate["os_license_number"] = $line["WINPRODKEY"];
			if (!empty ($line["WINPRODID"]))
				$compupdate["os_license_id"] = $line["WINPRODID"];
		}

		if ($cfg_ocs["import_general_os"]) {
			if (!in_array("os", $computer_updates)) {
				$compupdate["os"] = ocsImportDropdown('glpi_dropdown_os', $line["OSNAME"]);
			}
			if (!in_array("os_version", $computer_updates)) {
				$compupdate["os_version"] = ocsImportDropdown('glpi_dropdown_os_version', $line["OSVERSION"]);
			}
			if (!ereg("CEST", $line["OSCOMMENTS"]) && !in_array("os_sp", $computer_updates)) // Not linux comment
				$compupdate["os_sp"] = ocsImportDropdown('glpi_dropdown_os_sp', $line["OSCOMMENTS"]);
		}

		if ($cfg_ocs["import_general_domain"] && !in_array("domain", $computer_updates)) {
			$compupdate["domain"] = ocsImportDropdown('glpi_dropdown_domain', $line["WORKGROUP"]);
		}

		if ($cfg_ocs["import_general_contact"] && !in_array("contact", $computer_updates)) {
			$compupdate["contact"] = $line["USERID"];
			$query = "SELECT ID 
										FROM glpi_users
										WHERE name='" . $line["USERID"] . "';";
			$result = $DB->query($query);
			if ($DB->numrows($result) == 1 && !in_array("FK_users", $computer_updates)) {
				$compupdate["FK_users"] = $DB->result($result, 0, 0);
			}
		}

		if ($cfg_ocs["import_general_name"] && !in_array("name", $computer_updates)) {
			$compupdate["name"] = $line["NAME"];
		}

		if ($cfg_ocs["import_general_comments"] && !in_array("comments", $computer_updates)) {
			$compupdate["comments"] = "";
			;
			if (!empty ($line["DESCRIPTION"]) && $line["DESCRIPTION"] != "N/A")
				$compupdate["comments"] .= $line["DESCRIPTION"] . "\r\n";
			$compupdate["comments"] .= "Swap: " . $line["SWAP"];
		}
		if (count($compupdate)) {
			$compupdate["ID"] = $glpi_id;
			$comp = new Computer();
			$compupdate["_from_ocs"] = 1;
			$comp->update($compupdate, $dohistory);
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
 *@param $ocs_server_id integer : ocs server id
 *@param $cfg_ocs array : ocs config
 *@param $computer_updates array : already updated fields of the computer
 *@param $dohistory boolean : log changes ?
 *
 *@return nothing.
 *
 **/
function ocsUpdateBios($glpi_id, $ocs_id, $ocs_server_id, $cfg_ocs, $computer_updates, $dohistory = 2) {
	global $DBocs;

	checkOCSconnection($ocs_server_id);

	$query = "SELECT * 
				FROM bios 
				WHERE HARDWARE_ID='" . $ocs_id . "'";
	$result = $DBocs->query($query);
	$compupdate = array ();
	if ($DBocs->numrows($result) == 1) {
		$line = $DBocs->fetch_assoc($result);
		$line = clean_cross_side_scripting_deep(addslashes_deep($line));
		$compudate = array ();

		if ($cfg_ocs["import_general_serial"] && !in_array("serial", $computer_updates)) {
			$compupdate["serial"] = $line["SSN"];
		}

		if ($cfg_ocs["import_general_model"] && !in_array("model", $computer_updates)) {
			$compupdate["model"] = ocsImportDropdown('glpi_dropdown_model', $line["SMODEL"]);
		}

		if ($cfg_ocs["import_general_enterprise"] && !in_array("FK_glpi_enterprise", $computer_updates)) {
			$compupdate["FK_glpi_enterprise"] = ocsImportDropdown("glpi_dropdown_manufacturer", $line["SMANUFACTURER"]);
		}

		if ($cfg_ocs["import_general_type"] && !empty ($line["TYPE"]) && !in_array("type", $computer_updates)) {
			$compupdate["type"] = ocsImportDropdown('glpi_type_computers', $line["TYPE"]);
		}

		if (count($compupdate)) {
			$compupdate["ID"] = $glpi_id;
			$comp = new Computer();
			$compupdate["_from_ocs"] = 1;
			$comp->update($compupdate, $dohistory);
		}

	}
}

/**
 * Import a dropdown from OCS table.
 *
 * This import a new dropdown if it doesn't exist.
 *
 *@param $dpdTable string : Name of the glpi dropdown table.
 *@param $value string : Value of the new dropdown.
 *@param $FK_entities int : entity in case of specific dropdown
 *
 *@return integer : dropdown id.
 *
 **/

function ocsImportDropdown($dpdTable, $value, $FK_entities = -1) {
	global $DB, $CFG_GLPI;

	if (empty ($value))
		return 0;

	$entity_restrict = "";
	$addfield = "";
	$addvalue = "";
	if (in_array($dpdTable, $CFG_GLPI["specif_entities_tables"])) {
		$entity_restrict = " AND FK_entities='$FK_entities'";
		$addfield = ",FK_entities";
		$addvalue = ",'$FK_entities'";
	}
	$query2 = "SELECT * 
				FROM " . $dpdTable . " 
				WHERE name='" . $value . "' $entity_restrict";
	$result2 = $DB->query($query2);
	if ($DB->numrows($result2) == 0) {
		$input["tablename"] = $dpdTable;
		$input["value"] = $value;
		$input['type'] = "first";
		$input["comments"] = "";
		$input["FK_entities"] = $FK_entities;
		return addDropdown($input);
	} else {
		$line2 = $DB->fetch_array($result2);
		return $line2["ID"];
	}

}

/**
 * Import a group from OCS table.
 *
 *@param $value string : Value of the new dropdown.
 *@param $FK_entities int : entity in case of specific dropdown
 *
 *@return integer : dropdown id.
 *
 **/

function ocsImportGroup($value, $FK_entities) {
	global $DB, $CFG_GLPI;

	if (empty ($value))
		return 0;

	$query2 = "SELECT ID
				FROM glpi_groups
				WHERE name='" . $value . "' AND FK_entities='$FK_entities'";
	$result2 = $DB->query($query2);
	if ($DB->numrows($result2) == 0) {
		$group = new Group;
		$input["name"] = $value;
		$input["FK_entities"] = $FK_entities;
		$input["_from_ocs"] = 1;
		return $group->add($input);
	} else {
		$line2 = $DB->fetch_array($result2);
		return $line2["ID"];
	}

}

function ocsCleanLinks($ocs_server_id) {
	global $DB, $DBocs;

	checkOCSconnection($ocs_server_id);

	// Delete unexisting GLPI computers
	$query = "SELECT glpi_ocs_link.ID AS ID 
				FROM glpi_ocs_link 
				LEFT JOIN glpi_computers ON glpi_computers.ID=glpi_ocs_link.glpi_id 
				WHERE glpi_computers.ID IS NULL AND ocs_server_id='$ocs_server_id'";

	$result = $DB->query($query);
	if ($DB->numrows($result) > 0) {
		while ($data = $DB->fetch_array($result)) {
			$query2 = "DELETE FROM glpi_ocs_link 
								WHERE ID='" . $data['ID'] . "'";
			$DB->query($query2);
		}
	}

	// Delete unexisting OCS hardware
	$query_ocs = "SELECT * 
				FROM hardware";
	$result_ocs = $DBocs->query($query_ocs);

	$hardware = array ();
	if ($DBocs->numrows($result_ocs) > 0) {
		while ($data = $DBocs->fetch_array($result_ocs)) {
			$data = clean_cross_side_scripting_deep(addslashes_deep($data));
			$hardware[$data["ID"]] = $data["DEVICEID"];
		}
	}

	$query = "SELECT *
				FROM glpi_ocs_link
				WHERE ocs_server_id='$ocs_server_id'";
	$result = $DB->query($query);

	if ($DB->numrows($result) > 0) {
		while ($data = $DB->fetch_array($result)) {
			$data = clean_cross_side_scripting_deep(addslashes_deep($data));
			if (!isset ($hardware[$data["ocs_id"]])) {
				$query_del = "DELETE FROM glpi_ocs_link 
										WHERE ID='" . $data["ID"] . "'";
				$DB->query($query_del);
				$comp = new Computer();
				$comp->delete(array (
					"ID" => $data["glpi_id"],
					"_from_ocs" => 1
				), 0);

			}
		}
	}

}

function cron_ocsng() {

	global $DB, $CFG_GLPI;

	//Get a randon server id
	$ocs_server_id = getRandomOCSServerID();
	if ($ocs_server_id > 0) {
		//Initialize the server connection
		$DBocs = getDBocs($ocs_server_id);

		$cfg_ocs = getOcsConf($ocs_server_id);
		if ($CFG_GLPI["use_errorlog"]) {
			logInFile("cron", "Check updates from server " . $cfg_ocs['name'] . "\n");
		}

		if (!$cfg_ocs["cron_sync_number"])
			return 0;

		ocsManageDeleted($ocs_server_id);

		$query_ocs = "SELECT * 
						FROM hardware 
						WHERE (CHECKSUM & " . $cfg_ocs["checksum"] . ") > 0";
		$result_ocs = $DBocs->query($query_ocs);
		if ($DBocs->numrows($result_ocs) > 0) {

			$hardware = array ();
			while ($data = $DBocs->fetch_array($result_ocs)) {
				$hardware[$data["ID"]]["date"] = $data["LASTDATE"];
				$hardware[$data["ID"]]["name"] = addslashes($data["NAME"]);
			}

			$query_glpi = "SELECT * 
									FROM glpi_ocs_link 
									WHERE auto_update= '1'
									ORDER BY last_update";
			$result_glpi = $DB->query($query_glpi);
			$done = 0;
			while ($done < $cfg_ocs["cron_sync_number"] && $data = $DB->fetch_assoc($result_glpi)) {
				$data = clean_cross_side_scripting_deep(addslashes_deep($data));

				if (isset ($hardware[$data["ocs_id"]])) {
					ocsUpdateComputer($data["ID"], $ocs_server_id, 2);
					if ($CFG_GLPI["use_errorlog"]) {
						logInFile("cron", "Update computer " . $data["ID"] . "\n");
					}
					$done++;
				}
			}
			if ($done > 0) {
				return 1;
			}
		}
		return 0;
	}
	return 1;
}

function ocsShowUpdateComputer($ocs_server_id, $check, $start) {
	global $DB, $DBocs, $LANG, $CFG_GLPI;

	checkOCSconnection($ocs_server_id);

	if (!haveRight("ocsng", "w"))
		return false;

	$cfg_ocs = getOcsConf($ocs_server_id);
	$query_ocs = "SELECT * 
				FROM hardware 
				WHERE (CHECKSUM & " . $cfg_ocs["checksum"] . ") > 0 
				ORDER BY LASTDATE";
	$result_ocs = $DBocs->query($query_ocs);

	$query_glpi = "SELECT glpi_ocs_link.last_update as last_update,  glpi_ocs_link.glpi_id as glpi_id, 
				glpi_ocs_link.ocs_id as ocs_id, glpi_computers.name as name, 
				glpi_ocs_link.auto_update as auto_update, glpi_ocs_link.ID as ID 
					FROM glpi_ocs_link  
					LEFT JOIN glpi_computers ON (glpi_computers.ID = glpi_ocs_link.glpi_id) 
					WHERE glpi_ocs_link.ocs_server_id=" . $ocs_server_id . " ORDER BY glpi_ocs_link.auto_update DESC, glpi_ocs_link.last_update, glpi_computers.name";

	$result_glpi = $DB->query($query_glpi);
	if ($DBocs->numrows($result_ocs) > 0) {
		// Get all hardware from OCS DB
		$hardware = array ();
		while ($data = $DBocs->fetch_array($result_ocs)) {
			$hardware[$data["ID"]]["date"] = $data["LASTDATE"];
			$hardware[$data["ID"]]["name"] = addslashes($data["NAME"]);
		}

		// Get all links between glpi and OCS
		$already_linked = array ();
		if ($DB->numrows($result_glpi) > 0) {
			while ($data = $DB->fetch_assoc($result_glpi)) {
				$data = clean_cross_side_scripting_deep(addslashes_deep($data));
				if (isset ($hardware[$data["ocs_id"]])) {
					$already_linked[$data["ocs_id"]]["date"] = $data["last_update"];
					$already_linked[$data["ocs_id"]]["name"] = $data["name"];
					$already_linked[$data["ocs_id"]]["ID"] = $data["ID"];
					$already_linked[$data["ocs_id"]]["glpi_id"] = $data["glpi_id"];
					$already_linked[$data["ocs_id"]]["ocs_id"] = $data["ocs_id"];
					$already_linked[$data["ocs_id"]]["auto_update"] = $data["auto_update"];
				}
			}
		}

		echo "<div align='center'>";
		echo "<h2>" . $LANG["ocsng"][10] . "</h2>";

		if (($numrows = count($already_linked)) > 0) {

			$parameters = "check=$check";
			printPager($start, $numrows, $_SERVER['PHP_SELF'], $parameters);

			// delete end 
			array_splice($already_linked, $start + $CFG_GLPI["list_limit"]);
			// delete begin
			if ($start > 0)
				array_splice($already_linked, 0, $start);

			echo "<form method='post' id='ocsng_form' name='ocsng_form' action='" . $_SERVER['PHP_SELF'] . "'>";

			echo "<a href='" . $_SERVER['PHP_SELF'] . "?check=all' onclick= \"if ( markAllRows('ocsng_form') ) return false;\">" . $LANG["buttons"][18] . "</a>&nbsp;/&nbsp;<a href='" . $_SERVER['PHP_SELF'] . "?check=none' onclick= \"if ( unMarkAllRows('ocsng_form') ) return false;\">" . $LANG["buttons"][19] . "</a>";
			echo "<table class='tab_cadre'>";
			echo "<tr><th>" . $LANG["ocsng"][11] . "</th><th>" . $LANG["ocsng"][13] . "</th><th>" . $LANG["ocsng"][14] . "</th><th>" . $LANG["ocsng"][6] . "</th><th>&nbsp;</th></tr>";

			echo "<tr class='tab_bg_1'><td colspan='5' align='center'>";
			echo "<input class='submit' type='submit' name='update_ok' value='" . $LANG["buttons"][7] . "'>";
			echo "</td></tr>";

			foreach ($already_linked as $ID => $tab) {

				echo "<tr align='center' class='tab_bg_2'>";
				echo "<td><a href='" . $CFG_GLPI["root_doc"] . "/front/computer.form.php?ID=" . $tab["glpi_id"] . "'>" . $tab["name"] . "</a></td>";
				echo "<td>" . convDateTime($tab["date"]) . "</td><td>" . convDateTime($hardware[$tab["ocs_id"]]["date"]) . "</td>";
				echo "<td>" . $LANG["choice"][$tab["auto_update"]] . "</td>";
				echo "<td><input type='checkbox' name='toupdate[" . $tab["ID"] . "]' " . ($check == "all" ? "checked" : "") . ">";
				echo "</td></tr>";
			}
			echo "<tr class='tab_bg_1'><td colspan='5' align='center'>";
			echo "<input class='submit' type='submit' name='update_ok' value='" . $LANG["buttons"][7] . "'>";
			echo "<input type=hidden name='ocs_server_id' value='" . $ocs_server_id . "'>";
			echo "</td></tr>";
			echo "</table>";
			echo "</form>";
			printPager($start, $numrows, $_SERVER['PHP_SELF'], $parameters);

		} else
			echo "<br><strong>" . $LANG["ocsng"][11] . "</strong>";

		echo "</div>";

	} else
		echo "<div align='center'><strong>" . $LANG["ocsng"][12] . "</strong></div>";
}

function mergeOcsArray($glpi_id, $tomerge, $field) {
	global $DB;
	$query = "SELECT $field 
				FROM glpi_ocs_link 
				WHERE glpi_id='$glpi_id'";
	$result = $DB->query($query);
	if ($DB->numrows($result))
		if ($result = $DB->query($query)) {
			$tab = importArrayFromDB($DB->result($result, 0, 0));
			$newtab = array_merge($tomerge, $tab);
			$newtab = array_unique($newtab);
			$query = "UPDATE glpi_ocs_link 
									SET $field='" . exportArrayToDB($newtab) . "' 
									WHERE glpi_id='$glpi_id'";
			$DB->query($query);
		}

}

function deleteInOcsArray($glpi_id, $todel, $field) {
	global $DB;
	$query = "SELECT $field FROM glpi_ocs_link WHERE glpi_id='$glpi_id'";
	if ($result = $DB->query($query)) {
		$tab = importArrayFromDB($DB->result($result, 0, 0));
		unset ($tab[$todel]);
		$query = "UPDATE glpi_ocs_link 
							SET $field='" . exportArrayToDB($tab) . "' 
							WHERE glpi_id='$glpi_id'";
		$DB->query($query);
	}

}

function replaceOcsArray($glpi_id, $newArray, $field) {
	global $DB;
	
	$newArray = exportArrayToDB($newArray);
	//print_r($newArray);
	$query = "SELECT $field FROM glpi_ocs_link WHERE glpi_id=".$glpi_id;
	if ($result = $DB->query($query)) {
		$query = "UPDATE glpi_ocs_link 
							SET $field='" . $newArray . "' 
							WHERE glpi_id=".$glpi_id;
		$DB->query($query);
	}
}

function addToOcsArray($glpi_id, $toadd, $field) {
	global $DB;
	$query = "SELECT $field 
				FROM glpi_ocs_link 
				WHERE glpi_id='$glpi_id'";
	if ($result = $DB->query($query)) {
		$tab = importArrayFromDB($DB->result($result, 0, 0));
		foreach ($toadd as $key => $val) {
			$tab[$key] = $val;
		}
		$query = "UPDATE glpi_ocs_link 
							SET $field='" . exportArrayToDB($tab) . "' 
							WHERE glpi_id='$glpi_id'";
		$DB->query($query);
	}

}

function ocsEditLock($target, $ID) {
	global $DB, $LANG, $SEARCH_OPTION;

	if (!haveRight("computer","w")) {
		return false;
	}
	$query = "SELECT * 
				FROM glpi_ocs_link 
				WHERE glpi_id='$ID'";

	$result = $DB->query($query);
	if ($DB->numrows($result) == 1) {
		$data = $DB->fetch_assoc($result);
		if (haveRight("sync_ocsng","w")){
			echo "<div align='center'>";
			echo "<form method='post' action=\"$target\">";
			echo "<input type='hidden' name='ID' value='$ID'>";
			echo "<table class='tab_cadre'><tr class='tab_bg_2'><td>";
			echo "<input type='hidden' name='resynch_id' value='" . $data["ID"] . "'>";
			echo "<input class=submit type='submit' name='force_ocs_resynch' value='" . $LANG["ocsng"][24] . "'>";
			echo "</td><tr></table>";
			echo "</form>";
			echo "</div>";
		}

		echo "<div align='center'>";
		// Print lock fields for OCSNG

		$lockable_fields = array (
			"name",
			"type",
			"FK_glpi_enterprise",
			"model",
			"serial",
			"comments",
			"contact",
			"domain",
			"os",
			"os_sp",
			"os_version",
			"os_license_number",
			"FK_users"
		);
		$locked = array_intersect(importArrayFromDB($data["computer_update"]), $lockable_fields);

		if (count($locked)) {
			echo "<form method='post' action=\"$target\">";
			echo "<input type='hidden' name='ID' value='$ID'>";
			echo "<table class='tab_cadre'>";
			echo "<tr><th colspan='2'>" . $LANG["ocsng"][16] . "</th></tr>";
			foreach ($locked as $key => $val) {
				foreach ($SEARCH_OPTION[COMPUTER_TYPE] as $key2 => $val2)
					if ($val2["linkfield"] == $val || ($val2["table"] == "glpi_computers" && $val2["field"] == $val))
						echo "<tr class='tab_bg_1'><td>" . $val2["name"] . "</td><td><input type='checkbox' name='lockfield[" . $key . "]'></td></tr>";
			}
			echo "<tr class='tab_bg_2'><td align='center' colspan='2'><input class='submit' type='submit' name='unlock_field' value='" . $LANG["buttons"][38] . "'></td></tr>";
			echo "</table>";
			echo "</form>";
		} else
			echo "<strong>" . $LANG["ocsng"][15] . "</strong>";
		echo "</div>";

		//Search locked monitors
		$header = false;
		echo "<br>";
		echo "<div align='center'>";
		$locked_monitor = importArrayFromDB($data["import_monitor"]);
		foreach ($locked_monitor as $key => $val) {
			if ($val != "_version_070_") {
				$querySearchLockedMonitor = "SELECT end1 FROM glpi_connect_wire WHERE ID='$key'";
				$resultSearch = $DB->query($querySearchLockedMonitor);
				if ($DB->numrows($resultSearch) == 0) {
					//$header = true;
					if (!$header) {
						$header = true;
						echo "<form method='post' action=\"$target\">";
						echo "<input type='hidden' name='ID' value='$ID'>";
						echo "<table class='tab_cadre'>";
						echo "<tr><th colspan='2'>" . $LANG["ocsng"][30] . "</th></tr>";
					}
					echo "<tr class='tab_bg_1'><td>" . $val . "</td><td><input type='checkbox' name='lockmonitor[" . $key . "]'></td></tr>";
				}
			}
		}
		if ($header) {
			echo "<tr class='tab_bg_2'><td align='center' colspan='2'><input class='submit' type='submit' name='unlock_monitor' value='" . $LANG["buttons"][38] . "'></td></tr>";
			echo "</table>";
			echo "</form>";
		} else
			echo "<strong>" . $LANG["ocsng"][31] . "</strong>";
		echo "</div>";

		//Search locked printers
		$header = false;
		echo "<br>";
		echo "<div align='center'>";
		$locked_printer = importArrayFromDB($data["import_printers"]);
		foreach ($locked_printer as $key => $val) {
			$querySearchLockedPrinter = "SELECT end1 FROM glpi_connect_wire WHERE ID='$key'";
			$resultSearchPrinter = $DB->query($querySearchLockedPrinter);
			if ($DB->numrows($resultSearchPrinter) == 0) {
				//$header = true;
				if (!($header)) {
					$header = true;
					echo "<form method='post' action=\"$target\">";
					echo "<input type='hidden' name='ID' value='$ID'>";
					echo "<table class='tab_cadre'>";
					echo "<tr><th colspan='2'>" . $LANG["ocsng"][34] . "</th></tr>";
				}
				echo "<tr class='tab_bg_1'><td>" . $val . "</td><td><input type='checkbox' name='lockprinter[" . $key . "]'></td></tr>";
			}
		}
		if ($header) {
			echo "<tr class='tab_bg_2'><td align='center' colspan='2'><input class='submit' type='submit' name='unlock_printer' value='" . $LANG["buttons"][38] . "'></td></tr>";
			echo "</table>";
			echo "</form>";
		} else
			echo "<strong>" . $LANG["ocsng"][35] . "</strong>";
		echo "</div>";

		//Search locked peripherals
		$header = false;
		echo "<br>";
		echo "<div align='center'>";
		$locked_printer = importArrayFromDB($data["import_peripheral"]);
		foreach ($locked_printer as $key => $val) {
			$querySearchLockedPeriph = "SELECT end1 FROM glpi_connect_wire WHERE ID='$key'";
			$resultSearchPrinter = $DB->query($querySearchLockedPeriph);
			if ($DB->numrows($resultSearchPrinter) == 0) {
				//$header = true;
				if (!($header)) {
					$header = true;
					echo "<form method='post' action=\"$target\">";
					echo "<input type='hidden' name='ID' value='$ID'>";
					echo "<table class='tab_cadre'>";
					echo "<tr><th colspan='2'>" . $LANG["ocsng"][32] . "</th></tr>";
				}
				echo "<tr class='tab_bg_1'><td>" . $val . "</td><td><input type='checkbox' name='lockperiph[" . $key . "]'></td></tr>";
			}
		}
		if ($header) {
			echo "<tr class='tab_bg_2'><td align='center' colspan='2'><input class='submit' type='submit' name='unlock_periph' value='" . $LANG["buttons"][38] . "'></td></tr>";
			echo "</table>";
			echo "</form>";
		} else
			echo "<strong>" . $LANG["ocsng"][33] . "</strong>";
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
 *@param $ocs_server_id integer : ocs server id
 *@param $cfg_ocs array : ocs config
 *@param $dohistory boolean : log changes ?
 *@param $import_device array : already imported devices
 *@param $import_ip array : already imported ip
 *
 *@return Nothing (void).
 *
 **/
function ocsUpdateDevices($device_type, $glpi_id, $ocs_id, $ocs_server_id, $cfg_ocs, $import_device, $import_ip, $dohistory) {
	global $DB, $DBocs;

	checkOCSconnection($ocs_server_id);

	$do_clean = false;
	switch ($device_type) {
		case RAM_DEVICE :
			//Memoire
			if ($cfg_ocs["import_device_memory"]) {
				$do_clean = true;

				$query2 = "SELECT * 
													FROM memories 
													WHERE HARDWARE_ID = '" . $ocs_id . "' 
													ORDER BY ID";
				$result2 = $DBocs->query($query2);
				if ($DBocs->numrows($result2) > 0) {
					while ($line2 = $DBocs->fetch_array($result2)) {
						$line2 = clean_cross_side_scripting_deep(addslashes_deep($line2));
						if (!empty ($line2["CAPACITY"]) && $line2["CAPACITY"] != "No") {
							if ($line2["DESCRIPTION"])
								$ram["designation"] = $line2["DESCRIPTION"];
							else
								$ram["designation"] = "Unknown";
							$ram["specif_default"] = $line2["CAPACITY"];
							if (!in_array(RAM_DEVICE . '$$$$$' . $ram["designation"], $import_device)) {
								$ram["frequence"] = $line2["SPEED"];
								$ram["type"] = ocsImportDropdown("glpi_dropdown_ram_type", $line2["TYPE"]);
								$ram_id = ocsAddDevice(RAM_DEVICE, $ram);
								if ($ram_id) {
									$devID = compdevice_add($glpi_id, RAM_DEVICE, $ram_id, $line2["CAPACITY"], $dohistory);
									addToOcsArray($glpi_id, array (
										$devID => RAM_DEVICE . '$$$$$' . $ram["designation"]
									), "import_device");
								}
							} else {
								$id = array_search(RAM_DEVICE . '$$$$$' . $ram["designation"], $import_device);
								update_device_specif($line2["CAPACITY"], $id, 1);
								unset ($import_device[$id]);
							}
						}
					}
				}
			}
			break;
		case HDD_DEVICE :
			//Disque Dur
			if ($cfg_ocs["import_device_hdd"]) {
				$do_clean = true;

				$query2 = "SELECT * 
													FROM storages 
													WHERE HARDWARE_ID = '" . $ocs_id . "' 
													ORDER BY ID";
				$result2 = $DBocs->query($query2);

				if ($DBocs->numrows($result2) > 0) {
					while ($line2 = $DBocs->fetch_array($result2)) {
						$line2 = clean_cross_side_scripting_deep(addslashes_deep($line2));
						if (!empty ($line2["DISKSIZE"]) && eregi("disk", $line2["TYPE"])) {
							if ($line2["NAME"])
								$dd["designation"] = $line2["NAME"];
							else
								if ($line2["MODEL"])
									$dd["designation"] = $line2["MODEL"];
								else
									$dd["designation"] = "Unknown";

							if (!in_array(HDD_DEVICE . '$$$$$' . $dd["designation"], $import_device)) {
								$dd["specif_default"] = $line2["DISKSIZE"];
								$dd_id = ocsAddDevice(HDD_DEVICE, $dd);
								if ($dd_id) {
									$devID = compdevice_add($glpi_id, HDD_DEVICE, $dd_id, $line2["DISKSIZE"], $dohistory);
									addToOcsArray($glpi_id, array (
										$devID => HDD_DEVICE . '$$$$$' . $dd["designation"]
									), "import_device");
								}
							} else {
								$id = array_search(HDD_DEVICE . '$$$$$' . $dd["designation"], $import_device);
								update_device_specif($line2["DISKSIZE"], $id, 1);
								unset ($import_device[$id]);
							}

						}
					}
				}
			}

			break;
		case DRIVE_DEVICE :
			//lecteurs
			if ($cfg_ocs["import_device_drives"]) {
				$do_clean = true;

				$query2 = "SELECT * 
													FROM storages 
													WHERE HARDWARE_ID = '" . $ocs_id . "' ORDER BY ID";
				$result2 = $DBocs->query($query2);
				if ($DBocs->numrows($result2) > 0) {
					while ($line2 = $DBocs->fetch_array($result2)) {
						$line2 = clean_cross_side_scripting_deep(addslashes_deep($line2));
						if (empty ($line2["DISKSIZE"]) || !eregi("disk", $line2["TYPE"])) {
							if ($line2["NAME"])
								$stor["designation"] = $line2["NAME"];
							else
								if ($line2["MODEL"])
									$stor["designation"] = $line2["MODEL"];
								else
									$stor["designation"] = "Unknown";
							if (!in_array(DRIVE_DEVICE . '$$$$$' . $stor["designation"], $import_device)) {
								$stor["specif_default"] = $line2["DISKSIZE"];
								$stor_id = ocsAddDevice(DRIVE_DEVICE, $stor);
								if ($stor_id) {
									$devID = compdevice_add($glpi_id, DRIVE_DEVICE, $stor_id, "", $dohistory);
									addToOcsArray($glpi_id, array (
										$devID => DRIVE_DEVICE . '$$$$$' . $stor["designation"]
									), "import_device");
								}
							} else {
								$id = array_search(DRIVE_DEVICE . '$$$$$' . $stor["designation"], $import_device);
								unset ($import_device[$id]);
							}

						}
					}
				}
			}
			break;
		case PCI_DEVICE :
			//Modems
			if ($cfg_ocs["import_device_modems"]) {
				$do_clean = true;

				$query2 = "SELECT * 
													FROM modems 
													WHERE HARDWARE_ID = '" . $ocs_id . "' 
													ORDER BY ID";
				$result2 = $DBocs->query($query2);
				if ($DBocs->numrows($result2) > 0) {
					while ($line2 = $DBocs->fetch_array($result2)) {
						$line2 = clean_cross_side_scripting_deep(addslashes_deep($line2));
						$mdm["designation"] = $line2["NAME"];
						if (!in_array(PCI_DEVICE . '$$$$$' . $mdm["designation"], $import_device)) {
							if (!empty ($line2["DESCRIPTION"]))
								$mdm["comment"] = $line2["TYPE"] . "\r\n" . $line2["DESCRIPTION"];
							$mdm_id = ocsAddDevice(PCI_DEVICE, $mdm);
							if ($mdm_id) {
								$devID = compdevice_add($glpi_id, PCI_DEVICE, $mdm_id, "", $dohistory);
								addToOcsArray($glpi_id, array (
									$devID => PCI_DEVICE . '$$$$$' . $mdm["designation"]
								), "import_device");
							}
						} else {
							$id = array_search(PCI_DEVICE . '$$$$$' . $mdm["designation"], $import_device);
							unset ($import_device[$id]);
						}

					}
				}
			}
			//Ports
			if ($cfg_ocs["import_device_ports"]) {

				$query2 = "SELECT * 
													FROM ports 
													WHERE HARDWARE_ID = '" . $ocs_id . "' 
													ORDER BY ID";
				$result2 = $DBocs->query($query2);
				if ($DBocs->numrows($result2) > 0) {
					while ($line2 = $DBocs->fetch_array($result2)) {
						$line2 = clean_cross_side_scripting_deep(addslashes_deep($line2));
						$port["designation"] = "";
						if ($line2["TYPE"] != "Other")
							$port["designation"] .= $line2["TYPE"];
						if ($line2["NAME"] != "Not Specified")
							$port["designation"] .= " " . $line2["NAME"];
						else
							if ($line2["CAPTION"] != "None")
								$port["designation"] .= " " . $line2["CAPTION"];
						if (!empty ($port["designation"]))
							if (!in_array(PCI_DEVICE . '$$$$$' . $port["designation"], $import_device)) {
								if (!empty ($line2["DESCRIPTION"]) && $line2["DESCRIPTION"] != "None")
									$port["comment"] = $line2["DESCRIPTION"];
								$port_id = ocsAddDevice(PCI_DEVICE, $port);
								if ($port_id) {
									$devID = compdevice_add($glpi_id, PCI_DEVICE, $port_id, "", $dohistory);
									addToOcsArray($glpi_id, array (
										$devID => PCI_DEVICE . '$$$$$' . $port["designation"]
									), "import_device");
								}
							} else {
								$id = array_search(PCI_DEVICE . '$$$$$' . $port["designation"], $import_device);
								unset ($import_device[$id]);
							}
					}
				}
			}
			break;
		case PROCESSOR_DEVICE :
			//Processeurs : 
			if ($cfg_ocs["import_device_processor"]) {
				$do_clean = true;

				$query = "SELECT * 
													FROM hardware 
													WHERE ID='$ocs_id' ORDER BY ID";
				$result = $DBocs->query($query);
				if ($DBocs->numrows($result) == 1) {
					$line = $DBocs->fetch_array($result);
					$line = clean_cross_side_scripting_deep(addslashes_deep($line));
					for ($i = 0; $i < $line["PROCESSORN"]; $i++) {
						$processor = array ();
						$processor["designation"] = $line["PROCESSORT"];
						$processor["specif_default"] = $line["PROCESSORS"];
						if (!in_array(PROCESSOR_DEVICE . '$$$$$' . $processor["designation"], $import_device)) {
							$proc_id = ocsAddDevice(PROCESSOR_DEVICE, $processor);
							if ($proc_id) {
								$devID = compdevice_add($glpi_id, PROCESSOR_DEVICE, $proc_id, $line["PROCESSORS"], $dohistory);
								addToOcsArray($glpi_id, array (
									$devID => PROCESSOR_DEVICE . '$$$$$' . $processor["designation"]
								), "import_device");
							}
						} else {
							$id = array_search(PROCESSOR_DEVICE . '$$$$$' . $processor["designation"], $import_device);
							update_device_specif($line["PROCESSORS"], $id, 1);
							unset ($import_device[$id]);
						}
					}
				}
			}
			break;
		case NETWORK_DEVICE :

			//Carte reseau
			if ($cfg_ocs["import_device_iface"] || $cfg_ocs["import_ip"]) {

				$query2 = "SELECT * 
													FROM networks 
													WHERE HARDWARE_ID = '" . $ocs_id . "' 
													ORDER BY ID";

				$result2 = $DBocs->query($query2);
				$i = 0;
				$manually_link = false;
				
				//Count old ip in GLPI
				$count_ip = count($import_ip);
				// Add network device
				if ($DBocs->numrows($result2) > 0) {
					while ($line2 = $DBocs->fetch_array($result2)) {
						$line2 = clean_cross_side_scripting_deep(addslashes_deep($line2));
						if ($cfg_ocs["import_device_iface"]) {
							$do_clean = true;
							$network["designation"] = $line2["DESCRIPTION"];
							if (!in_array(NETWORK_DEVICE . '$$$$$' . $network["designation"], $import_device)) {
								
								if (!empty ($line2["SPEED"]))
									$network["bandwidth"] = $line2["SPEED"];
								$net_id = ocsAddDevice(NETWORK_DEVICE, $network);
								if ($net_id) {
									$devID = compdevice_add($glpi_id, NETWORK_DEVICE, $net_id, $line2["MACADDR"], $dohistory);
									addToOcsArray($glpi_id, array (
										$devID => NETWORK_DEVICE . '$$$$$' . $network["designation"]
									), "import_device");
								}
							} else {
								$id = array_search(NETWORK_DEVICE . '$$$$$' . $network["designation"], $import_device);
								update_device_specif($line2["MACADDR"], $id, 1);
								unset ($import_device[$id]);
							}
						}

						if (!empty ($line2["IPADDRESS"]) && $cfg_ocs["import_ip"]) {
							$do_clean = true;
							$ocs_ips = split(",", $line2["IPADDRESS"]);
							$ocs_ips = array_unique($ocs_ips);
							sort($ocs_ips);

							//if never imported in 0.70, insert id in the array
							if ($count_ip == 0) {
								//get old IP in DB							
								$querySelectIDandIP = "SELECT ID,ifaddr FROM glpi_networking_ports 
																			WHERE device_type='" . COMPUTER_TYPE . "' 
																			AND on_device='$glpi_id' 
																			AND ifmac='" . $line2["MACADDR"] . "'" . "
																			AND name='" . $line2["DESCRIPTION"] . "'";
								$result = $DB->query($querySelectIDandIP);
								if ($DB->numrows($result) > 0) {
									while ($data = $DB->fetch_array($result)) {
										//Upate import_ip column and import_ip array										
										addToOcsArray($glpi_id, array (
											$data["ID"] => $data["ifaddr"]
										), "import_ip");
										$import_ip[$data["ID"]] = $data["ifaddr"];
									}
								}
							}
							unset ($netport);
							$netport["ifmac"] = $line2["MACADDR"];
							$netport["iface"] = ocsImportDropdown("glpi_dropdown_iface", $line2["TYPE"]);
							$netport["name"] = $line2["DESCRIPTION"];
							$netport["on_device"] = $glpi_id;
							$netport["device_type"] = COMPUTER_TYPE;
							$netport["netmask"] = $line2["IPMASK"];
							$netport["gateway"] = $line2["IPGATEWAY"];
							$netport["subnet"] = $line2["IPSUBNET"];

							$np = new Netport();

							for ($j = 0; $j < count($ocs_ips); $j++) {
								$id_ip = array_search($ocs_ips[$j], $import_ip);
								//Update already in DB
								if ($id_ip) {
									$netport["ifaddr"] = $ocs_ips[$j];
									$netport["logical_number"] = $j;
									$netport["ID"] = $id_ip;
									$netport["_from_ocs"] = 1;
									$np->update($netport);
									unset ($import_ip[$id_ip]);
									$count_ip++;
								}
								//If new IP found
								else {
									unset ($np->fields["netpoint"]);
									unset ($netport["ID"]);
									unset ($np->fields["ID"]);
									$netport["ifaddr"] = $ocs_ips[$j];
									$netport["logical_number"] = $j;
									$netport["_from_ocs"] = 1;
									$newID = $np->add($netport);
									//ADD to array
									addToOcsArray($glpi_id, array (
										$newID => $ocs_ips[$j]
									), "import_ip");
									$count_ip++;
								}
							}
						}
					}
				}
			}
			break;
		case GFX_DEVICE :
			//carte graphique
			if ($cfg_ocs["import_device_gfxcard"]) {
				$do_clean = true;

				$query2 = "SELECT DISTINCT(NAME) as NAME, MEMORY 
													FROM videos 
													WHERE HARDWARE_ID = '" . $ocs_id . "'and NAME != '' 
													ORDER BY ID";
				$result2 = $DBocs->query($query2);
				if ($DBocs->numrows($result2) > 0) {
					while ($line2 = $DBocs->fetch_array($result2)) {
						$line2 = clean_cross_side_scripting_deep(addslashes_deep($line2));
						$video["designation"] = $line2["NAME"];
						if (!in_array(GFX_DEVICE . '$$$$$' . $video["designation"], $import_device)) {
							$video["ram"] = "";
							if (!empty ($line2["MEMORY"]))
								$video["ram"] = $line2["MEMORY"];
							$video_id = ocsAddDevice(GFX_DEVICE, $video);
							if ($video_id) {
								$devID = compdevice_add($glpi_id, GFX_DEVICE, $video_id, $video["ram"], $dohistory);
								addToOcsArray($glpi_id, array (
									$devID => GFX_DEVICE . '$$$$$' . $video["designation"]
								), "import_device");
							}
						} else {
							$id = array_search(GFX_DEVICE . '$$$$$' . $video["designation"], $import_device);
							update_device_specif($line2["MEMORY"], $id, 1);
							unset ($import_device[$id]);
						}
					}
				}
			}
			break;
		case SND_DEVICE :
			//carte son
			if ($cfg_ocs["import_device_sound"]) {
				$do_clean = true;

				$query2 = "SELECT DISTINCT(NAME) as NAME, DESCRIPTION 
													FROM sounds 
													WHERE HARDWARE_ID = '" . $ocs_id . "' 
													AND NAME != '' ORDER BY ID";
				$result2 = $DBocs->query($query2);
				if ($DBocs->numrows($result2) > 0) {
					while ($line2 = $DBocs->fetch_array($result2)) {
						$line2 = clean_cross_side_scripting_deep(addslashes_deep($line2));
						$snd["designation"] = $line2["NAME"];
						if (!in_array(SND_DEVICE . '$$$$$' . $snd["designation"], $import_device)) {
							if (!empty ($line2["DESCRIPTION"]))
								$snd["comment"] = $line2["DESCRIPTION"];
							$snd_id = ocsAddDevice(SND_DEVICE, $snd);
							if ($snd_id) {
								$devID = compdevice_add($glpi_id, SND_DEVICE, $snd_id, "", $dohistory);
								addToOcsArray($glpi_id, array (
									$devID => SND_DEVICE . '$$$$$' . $snd["designation"]
								), "import_device");
							}
						} else {
							$id = array_search(SND_DEVICE . '$$$$$' . $snd["designation"], $import_device);
							unset ($import_device[$id]);
						}
					}
				}
			}
			break;
	}

	// Delete Unexisting Items not found in OCS
	if ($do_clean && count($import_device)) {
		foreach ($import_device as $key => $val) {
			if (!(strpos($val, $device_type . '$$') === false)) {
				unlink_device_computer($key, $dohistory);
				deleteInOcsArray($glpi_id, $key, "import_device");
			}
		}

	}
	if ($do_clean && count($import_ip) && $device_type == NETWORK_DEVICE) {
		foreach ($import_ip as $key => $val) {
			$query2 = "DELETE FROM glpi_networking_ports WHERE on_device='$glpi_id' AND ifaddr='$val'";
			$DB->query($query2);
			deleteInOcsArray($glpi_id, $key, "import_ip");
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
function ocsAddDevice($device_type, $dev_array) {

	global $DB;
	$query = "SELECT * 
				FROM " . getDeviceTable($device_type) . " 
				WHERE designation='" . $dev_array["designation"] . "'";
	$result = $DB->query($query);
	if ($DB->numrows($result) == 0) {
		$dev = new Device($device_type);
		$input = array ();
		foreach ($dev_array as $key => $val) {
			$input[$key] = $val;
		}
		$input["_from_ocs"] = 1;
		return $dev->add($input);
	} else {
		$line = $DB->fetch_array($result);
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
 *@param $ocs_server_id integer : ocs server id
 *@param $cfg_ocs array : ocs config
 *@param $entity integer : entity of the computer
 *@param $dohistory boolean : log changes ?
 *@param $import_periph array : already imported periph
 *
 *@return Nothing (void).
 *
 **/
function ocsUpdatePeripherals($device_type, $entity, $glpi_id, $ocs_id, $ocs_server_id, $cfg_ocs, $import_periph, $dohistory) {
	global $DB, $DBocs, $LINK_ID_TABLE;

	checkOCSconnection($ocs_server_id);

	$do_clean = false;
	$connID = 0;
	//Tag for data since 0.70 for the import_monitor array.
	$tagVersionInArray = "_version_070_";

	$count_monitor = count($import_periph);
	switch ($device_type) {
		case MONITOR_TYPE :
			if ($cfg_ocs["import_monitor"]) {

				//Update data in import_monitor array for 0.70
				if (!in_array($tagVersionInArray, $import_periph)) {
					foreach ($import_periph as $key => $val) {
						$monitor_tag = $val;
						//delete old value									
						deleteInOcsArray($glpi_id, $key, "import_monitor");
						//search serial when it exists	
						$monitor_serial = "";
						$query_monitor_id = "SELECT end1 FROM glpi_connect_wire WHERE ID=$key";
						$result_monitor_id = $DB->query($query_monitor_id);
						if ($DB->numrows($result_monitor_id) == 1) {
							//get monitor Id
							$id_monitor = $DB->result($result_monitor_id, 0, "end1");
							$query_monitor_serial = "SELECT serial FROM glpi_monitors WHERE ID = $id_monitor";
							$result_monitor_serial = $DB->query($query_monitor_serial);
							//get serial
							if ($DB->numrows($result_monitor_serial) == 1)
								$monitor_serial = $DB->result($result_monitor_serial, 0, "serial");
						}
						//concat name + serial
						$monitor_tag .= $monitor_serial;
						//add new value (serial + name when its possible)							
						addToOcsArray($glpi_id, array (
							$key => $monitor_tag
						), "import_monitor");

						//Update the array with the new value of the monitor
						$import_periph[$key] = $monitor_tag;
					}
					//add the tag for the array version's
					addToOcsArray($glpi_id, array (
						0 => $tagVersionInArray
					), "import_monitor");

				}

				$do_clean = true;
				$m = new Monitor;

				$query = "SELECT DISTINCT CAPTION, MANUFACTURER, DESCRIPTION, SERIAL, TYPE 
													FROM monitors 
													WHERE HARDWARE_ID = '" . $ocs_id . "'";
				$result = $DBocs->query($query);
				if ($DBocs->numrows($result) > 0)
					while ($line = $DBocs->fetch_array($result)) {
						$line = clean_cross_side_scripting_deep(addslashes_deep($line));
						$mon = array ();
						$mon["name"] = $line["CAPTION"];

						if (empty ($line["CAPTION"]) && !empty ($line["MANUFACTURER"])) {
							$mon["name"] = $line["MANUFACTURER"];
						}
						if (empty ($line["CAPTION"]) && !empty ($line["TYPE"])) {
							if (!empty ($line["MANUFACTURER"])) {
								$mon["name"] .= " ";
							}
							$mon["name"] .= $line["TYPE"];
						}

						$mon["serial"] = $line["SERIAL"];
						$checkMonitor = "";
						if (!empty ($mon["serial"])) {
							$checkMonitor = $mon["name"];
							$checkMonitor .= $mon["serial"];
						} else {
							$checkMonitor = $mon["name"];
						}

						if (!empty ($mon["name"])) {
							if (!in_array($checkMonitor, $import_periph)) {
								// Clean monitor object
								$m->reset();

								$mon["FK_glpi_enterprise"] = ocsImportDropdown("glpi_dropdown_manufacturer", $line["MANUFACTURER"]);
								$mon["comments"] = $line["DESCRIPTION"];
								$id_monitor = 0;
								$found_already_monitor = false;
								if ($cfg_ocs["import_monitor"] == 1) {
									//Config says : manage monitors as global
									//check if monitors already exists in GLPI
									$mon["is_global"] = 1;
									$query = "SELECT ID FROM glpi_monitors WHERE name = '" . $mon["name"] . "'
																				AND is_global = '1' AND FK_entities=" . $entity;
									$result_search = $DB->query($query);
									if ($DB->numrows($result_search) > 0) {
										//Periph is already in GLPI
										//Do not import anything just get periph ID for link
										$id_monitor = $DB->result($result_search, 0, "ID");
									} else {
										$input = $mon;
										$input["state"] = $cfg_ocs["default_state"];
										$input["FK_entities"] = $entity;
										$input["_from_ocs"] = 1;
										$id_monitor = $m->add($input);
									}
								} else {
									if ($cfg_ocs["import_monitor"] == 2) {
										//COnfig says : manage monitors as single units
										//Import all monitors as non global.
										$mon["is_global"] = 0;
										// First import - Is there already a monitor ?
										if ($count_monitor == 0) {
											$query_search = "SELECT ID FROM glpi_monitors WHERE name = '" . $mon["name"] . "'
																										 AND is_global = '1' AND FK_entities=" . $entity;
											$result_search = $DB->query($query_search);
											if ($DB->numrows($result_search) == 1) {
												$id_monitor = $DB->result($result_search, 0, 0);
												$found_already_monitor = true;
											}
										}

										if ($found_already_monitor && $id_monitor) {

											$m->getFromDB($id_monitor);
											// Found a non global monitor : good
											if (!$m->fields["is_global"]) {
												$mon["ID"] = $id_monitor;
												unset ($mon["comments"]);
												$mon["_from_ocs"] = 1;
												$m->update($mon);
											} else { // Found a global monitor : bad idea. need to add another one
												$found_already_monitor = false;
												$id_monitor = 0;
												// Try to find a monitor with the same serial.
												if (!empty ($mon["serial"])) {
													$query = "SELECT ID FROM glpi_monitors WHERE serial LIKE '%" . $mon["serial"] . "%' AND FK_entities=" . $entity;
													$result_search = $DB->query($query);
													if ($DB->numrows($result_search) == 1) {
														//Monitor founded
														$id_monitor = $DB->result($result_search, 0, "ID");
													}
												}
												//Search by serial failed, search by name
												if (!$id_monitor) {
													//Try to find a monitor with the same name.
													if (!empty ($mon["name"])) {
														$query = "SELECT ID,serial FROM glpi_monitors WHERE name = '" . $mon["name"] . "' AND FK_entities=" . $entity;
														$result_search = $DB->query($query);
														if ($DB->numrows($result_search) == 1) {
															//Monitor founded
															$serial_monitor = "";
															$serial_monitor = $DB->result($result_search, 0, "serial");
															//Verify if serial are equals (for monitor with the same name and different serial)
															if ($serial_monitor == $mon['serial'])
																$id_monitor = $DB->result($result_search, 0, "ID");
														}
													}
												}
												// Nothing found : add it
												if (!$id_monitor) {
													$input = $mon;
													$input["state"] = $cfg_ocs["default_state"];
													$input["FK_entities"] = $entity;
													$input["_from_ocs"] = 1;
													$id_monitor = $m->add($input);
												}
											}
										} else {
											// Try to find a monitor with the same serial.
											if (!empty ($mon["serial"])) {
												$query = "SELECT ID FROM glpi_monitors WHERE serial LIKE '%" . $mon["serial"] . "%' AND FK_entities=" . $entity;
												$result_search = $DB->query($query);
												if ($DB->numrows($result_search) == 1) {
													//Monitor founded												
													$id_monitor = $DB->result($result_search, 0, "ID");
												}
											}
											//Search by serial failed, search by name
											if (!$id_monitor) {
												//Try to find a monitor with the same name.
												if (!empty ($mon["name"])) {
													$query = "SELECT ID,serial FROM glpi_monitors WHERE name = '" . $mon["name"] . "' AND FK_entities=" . $entity;
													$result_search = $DB->query($query);
													if ($DB->numrows($result_search) == 1) {
														//Monitor founded
														$serial_monitor = "";
														$serial_monitor = $DB->result($result_search, 0, "serial");
														//Verify if serial are equals (for dual monitor with the same name)
														if ($serial_monitor != "" && !empty ($mon['serial']))
															if ($serial_monitor == $mon['serial'])
																$id_monitor = $DB->result($result_search, 0, "ID");
													}
												}
											}
											if (!$id_monitor) {
												$input = $mon;
												$input["state"] = $cfg_ocs["default_state"];
												$input["FK_entities"] = $entity;
												$input["_from_ocs"] = 1;
												$id_monitor = $m->add($input);
											}
										}
									}
								}
								if ($id_monitor) {
									if (!$found_already_monitor) {
										//Import unique : Disconnect monitor on other computer
										if ($cfg_ocs["import_monitor"] == 2) {
											$queryGetConnectionID = "SELECT * FROM glpi_connect_wire WHERE end1=$id_monitor";
											$result_search_id = $DB->query($queryGetConnectionID);
											if ($DB->numrows($result_search_id) == 1) {
												$id_conn = $DB->result($result_search_id, 0, "ID");
												Disconnect($id_conn, $dohistory, $ocs_server_id);
											}
										}
										$connID = Connect($id_monitor, $glpi_id, MONITOR_TYPE, $dohistory);
									}
									if (!empty ($mon["serial"])) {
										$addValuetoDB = $mon["name"];
										$addValuetoDB .= $mon["serial"];
									} else {
										$addValuetoDB = $mon["name"];
									}
									if (!in_array($tagVersionInArray, $import_periph)) {
										addToOcsArray($glpi_id, array (
											0 => $tagVersionInArray
										), "import_monitor");
									}
									addToOcsArray($glpi_id, array (
										$connID => $addValuetoDB
									), "import_monitor");
									$count_monitor++;
									//Update column "deleted" set value to 0 and set status to default
									$input = array ();
									$input["ID"] = $id_monitor;
									$input["deleted"] = 0;
									$input["state"] = $cfg_ocs["default_state"];
									$input["_from_ocs"] = 1;
									$m->update($input);
								}
							} else {
								$searchDBValue = "";
								if (!empty ($mon["serial"])) {
									$searchDBValue = $mon["name"];
									$searchDBValue .= $mon["serial"];
								} else
									$searchDBValue = $mon["name"];
								$id = array_search($searchDBValue, $import_periph);
								unset ($import_periph[$id]);
							}
						}
					}
				if (in_array($tagVersionInArray, $import_periph)) {
					//unset the version Tag
					unset ($import_periph[0]);
				}
			}
			break;
		case PRINTER_TYPE :
			if ($cfg_ocs["import_printer"]) {
				$do_clean = true;

				$query = "SELECT * 
													FROM printers 
													WHERE HARDWARE_ID = '" . $ocs_id . "'";
				$result = $DBocs->query($query);
				$p = new Printer;

				if ($DBocs->numrows($result) > 0)
					while ($line = $DBocs->fetch_array($result)) {
						$line = clean_cross_side_scripting_deep(addslashes_deep($line));
						// TO TEST : PARSE NAME to have real name.
						$print["name"] = $line["NAME"];
						if (empty ($print["name"]))
							$print["name"] = $line["DRIVER"];

						if (!empty ($print["name"]))
							if (!in_array($print["name"], $import_periph)) {
								// Clean printer object
								$p->reset();

								//$print["comments"] = $line["PORT"]."\r\n".$line["NAME"];
								$print["comments"] = $line["PORT"] . "\r\n" . $line["DRIVER"];
								$id_printer = 0;

								if ($cfg_ocs["import_printer"] == 1) {
									//Config says : manage printers as global
									//check if printers already exists in GLPI
									$print["is_global"] = 1;
									$query = "SELECT ID 
																												FROM glpi_printers 
																												WHERE name = '" . $print["name"] . "' 
																												AND is_global = '1' AND FK_entities=" . $entity;
									$result_search = $DB->query($query);
									if ($DB->numrows($result_search) > 0) {
										//Periph is already in GLPI
										//Do not import anything just get periph ID for link
										$id_printer = $DB->result($result_search, 0, "ID");
									} else {
										$input = $print;
										$input["state"] = $cfg_ocs["default_state"];
										$input["FK_entities"] = $entity;
										$input["_from_ocs"] = 1;
										$id_printer = $p->add($input);
									}
								} else
									if ($cfg_ocs["import_printer"] == 2) {
										//COnfig says : manage printers as single units
										//Import all printers as non global.
										$input = $print;
										$input["is_global"] = 0;
										$input["state"] = $cfg_ocs["default_state"];
										$input["FK_entities"] = $entity;
										$input["_from_ocs"] = 1;
										$id_printer = $p->add($input);
									}
								if ($id_printer) {
									$connID = Connect($id_printer, $glpi_id, PRINTER_TYPE, $dohistory);
									addToOcsArray($glpi_id, array (
										$connID => $print["name"]
									), "import_printers");
									//Update column "deleted" set value to 0 and set status to default
									$input = array ();
									$input["ID"] = $id_printer;
									$input["deleted"] = 0;
									$input["state"] = $cfg_ocs["default_state"];
									$input["_from_ocs"] = 1;
									$p->update($input);
								}
							} else {
								$id = array_search($print["name"], $import_periph);
								unset ($import_periph[$id]);
							}
					}
			}
			break;
		case PERIPHERAL_TYPE :
			if ($cfg_ocs["import_periph"]) {
				$do_clean = true;
				$p = new Peripheral;

				$query = "SELECT DISTINCT CAPTION, MANUFACTURER, INTERFACE, TYPE 
													FROM inputs 
													WHERE HARDWARE_ID = '" . $ocs_id . "' 
													AND CAPTION <> ''";
				$result = $DBocs->query($query);
				if ($DBocs->numrows($result) > 0)
					while ($line = $DBocs->fetch_array($result)) {
						$line = clean_cross_side_scripting_deep(addslashes_deep($line));

						$periph["name"] = $line["CAPTION"];
						if (!in_array($periph["name"], $import_periph)) {
							// Clean peripheral object
							$p->reset();

							if ($line["MANUFACTURER"] != "NULL")
								$periph["brand"] = $line["MANUFACTURER"];
							if ($line["INTERFACE"] != "NULL")
								$periph["comments"] = $line["INTERFACE"];
							$periph["type"] = ocsImportDropdown("glpi_type_peripherals", $line["TYPE"]);

							$id_periph = 0;

							if ($cfg_ocs["import_periph"] == 1) {
								//Config says : manage peripherals as global
								//check if peripherals already exists in GLPI
								$periph["is_global"] = 1;
								$query = "SELECT ID 
																									FROM glpi_peripherals 
																									WHERE name = '" . $periph["name"] . "' 
																									AND is_global = '1' AND FK_entities=" . $entity;
								$result_search = $DB->query($query);
								if ($DB->numrows($result_search) > 0) {
									//Periph is already in GLPI
									//Do not import anything just get periph ID for link
									$id_periph = $DB->result($result_search, 0, "ID");
								} else {
									$input = $periph;
									$input["state"] = $cfg_ocs["default_state"];
									$input["FK_entities"] = $entity;
									$input["_from_ocs"] = 1;
									$id_periph = $p->add($input);
								}
							} else
								if ($cfg_ocs["import_periph"] == 2) {
									//COnfig says : manage peripherals as single units
									//Import all peripherals as non global.
									$input = $periph;
									$input["is_global"] = 0;
									$input["state"] = $cfg_ocs["default_state"];
									$input["FK_entities"] = $entity;
									$input["_from_ocs"] = 1;
									$id_periph = $p->add($input);
								}
							if ($id_periph) {
								$connID = Connect($id_periph, $glpi_id, PERIPHERAL_TYPE, $dohistory);
								addToOcsArray($glpi_id, array (
									$connID => $periph["name"]
								), "import_peripheral");
								//Update column "deleted" set value to 0 and set status to default
								$default_state = $cfg_ocs["default_state"];
								$input = array ();
								$input["ID"] = $id_periph;
								$input["deleted"] = 0;
								$input["state"] = $default_state;
								$input["_from_ocs"] = 1;
								$p->update($input);
								//$queryUpdate = "UPDATE glpi_peripherals SET deleted='0', state='$default_state' WHERE ID='$id_periph'";
								//$DB->query($queryUpdate);
							}
						} else {
							$id = array_search($periph["name"], $import_periph);
							unset ($import_periph[$id]);
						}
					}
			}
			break;
	}

	// Disconnect Unexisting Items not found in OCS
	if ($do_clean && count($import_periph)) {
		foreach ($import_periph as $key => $val) {

			Disconnect($key, $dohistory, $ocs_server_id);

			switch ($device_type) {
				case MONITOR_TYPE :
					deleteInOcsArray($glpi_id, $key, "import_monitor");
					break;
				case PRINTER_TYPE :
					deleteInOcsArray($glpi_id, $key, "import_printer");
					break;
				case PERIPHERAL_TYPE :
					deleteInOcsArray($glpi_id, $key, "import_peripheral");
					break;
			}
		}
	}

}
/**
 * Update the administrative informations
 *
 * This function erase old data and import the new ones about administrative informations
 *
 *
 *@param $glpi_id integer : glpi computer id.
 *@param $ocs_id integer : ocs computer id (ID).
 *@param $ocs_server_id integer : ocs server id
 *@param $computer_updates array : already updated fields of the computer
 *@param $entity integer : entity of the computer
 *@param $dohistory boolean : log changes ?
 *@param $cfg_ocs array : configuration ocs of the server
 
 *@return Nothing (void).
 *
 **/
function ocsUpdateAdministrativeInfo($glpi_id, $ocs_id, $ocs_server_id, $cfg_ocs, $computer_updates, $entity, $dohistory) {
	global $DB, $DBocs;
	checkOCSconnection($ocs_server_id);

	//check link between ocs and glpi column
	$queryListUpdate = "SELECT * from glpi_ocs_admin_link where ocs_server_id='$ocs_server_id' ";
	$result = $DB->query($queryListUpdate);
	if ($DB->numrows($result) > 0) {
		$queryOCS = "SELECT * from accountinfo where HARDWARE_ID='$ocs_id'";
		$resultOCS = $DBocs->query($queryOCS);
		if ($DBocs->numrows($resultOCS) > 0) {
			$data_ocs = $DBocs->fetch_array($resultOCS);
			$comp = new Computer();

			//update data 
			while ($links_glpi_ocs = $DB->fetch_array($result)) {
				//get info from ocs
				$ocs_column = $links_glpi_ocs['ocs_column'];
				$glpi_column = $links_glpi_ocs['glpi_column'];
				if (isset ($data_ocs[$ocs_column]) && !in_array($glpi_column, $computer_updates)) {
					$var = $data_ocs[$ocs_column];
					switch ($glpi_column) {
						case "FK_groups" :
							$var = ocsImportGroup($var, $entity);
							break;
						case "location" :
							$var = ocsImportDropdown("glpi_dropdown_locations", $var, $entity);
							break;
						case "network" :
							$var = ocsImportDropdown("glpi_dropdown_network", $var);
							break;
					}
					$input = array ();
					$input[$glpi_column] = $var;
					$input["ID"] = $glpi_id;
					$input["_from_ocs"] = 1;
					$comp->update($input, $dohistory);
				}
			}
		}
	}
	//if column in OCS has been deleted, we delete the rules in GLPI
	/*			else{
					$queryDelete ="DELETE from glpi_ocs_admin_link where ocs_server_id='$ocs_server_id' and ocs_column='$ocs_column'";
					$DB->query($queryDelete);  
				}*/

}

/**
 * Update config of the registry
 *
 * This function erase old data and import the new ones about registry (Microsoft OS after Windows 95)
 *
 *
 *@param $glpi_id integer : glpi computer id.
 *@param $ocs_id integer : ocs computer id (ID).
 *@param $ocs_server_id integer : ocs server id
 *@param $cfg_ocs array : ocs config
 *@return Nothing (void).
 *
 **/
function ocsUpdateRegistry($glpi_id, $ocs_id, $ocs_server_id, $cfg_ocs) {
	global $DB, $DBocs;

	checkOCSconnection($ocs_server_id);

	if ($cfg_ocs["import_registry"]) {
		//before update, delete all entries about $glpi_id
		$query_delete = "DELETE from glpi_registry WHERE computer_id='" . $glpi_id . "'";
		$DB->query($query_delete);

		//Get data from OCS database
		$query = "SELECT registry.NAME as NAME, registry.REGVALUE as regvalue, registry.HARDWARE_ID as computer_id, regconfig.REGTREE as regtree, regconfig.REGKEY as regkey
							FROM registry LEFT JOIN regconfig ON (registry.NAME = regconfig.NAME)
		   					WHERE HARDWARE_ID = '" . $ocs_id . "'";
		$result = $DBocs->query($query);
		if ($DBocs->numrows($result) > 0) {
			$reg = new Registry();

			//update data	
			while ($data = $DBocs->fetch_array($result)) {
				$data = clean_cross_side_scripting_deep(addslashes_deep($data));
				$input = array ();
				$input["computer_id"] = $glpi_id;
				$input["registry_hive"] = $data["regtree"];
				$input["registry_value"] = $data["regvalue"];
				$input["registry_path"] = $data["regkey"];
				$input["registry_ocs_name"] = $data["NAME"];
				$input["_from_ocs"] = 1;
				$isNewReg = $reg->add($input);
			}
		}
	}
	return;
}
/**
 * Update config of a new software
 *
 * This function create a new software in GLPI with some general datas.
 *
 *
 *@param $glpi_id integer : glpi computer id.
 *@param $ocs_id integer : ocs computer id (ID).
 *@param $ocs_server_id integer : ocs server id
 *@param $cfg_ocs array : ocs config
 *@param $entity integer : entity of the computer
 *@param $dohistory boolean : log changes ?
 *@param $import_software array : already imported softwares
 *
 *@return Nothing (void).
 *
 **/
function ocsUpdateSoftware($glpi_id, $entity, $ocs_id, $ocs_server_id, $cfg_ocs, $import_software, $dohistory) {
	global $DB, $DBocs;

	checkOCSconnection($ocs_server_id);

	//Tag for data since 0.70 for the import_software array.
	$tagVersionInArray = "_version_070_";

	if ($cfg_ocs["import_software"]) {

		//------------------------------------------------------------------------------------------------------------------//
		//---- Import_software array is not in the new form ( ID => name+version) -----//
		//----------------------------------------------------------------------------------------------------------------//
		if (!in_array($tagVersionInArray, $import_software)) {
			
			//Add the tag of the version at the beginning of the array
			$softs_array[0] = $tagVersionInArray;
			
			//For each element of the table, add instID=>name.version
			foreach ($import_software as $key => $value)
			{
				$query_softs = "SELECT glpi_licenses.version as VERSION 
				FROM glpi_inst_software, glpi_licenses 
				WHERE glpi_inst_software.license=glpi_licenses.ID AND glpi_inst_software.cID=".$glpi_id." AND glpi_inst_software.ID=".$key;
				$result_softs = $DB->query($query_softs);
				$softs = $DB->fetch_array($result_softs);
				$softs_array[$key] =  $value . '$$$$$'. $softs["VERSION"];
			}
			
			//Replace in GLPI database the import_software by the new one
			replaceOcsArray($glpi_id, $softs_array, "import_software");

			//Get import_software from the GLPI db
			//TODO: don't get import_software in DB, but use the newly contructed one
			$query = "SELECT import_software 
						FROM glpi_ocs_link 
						WHERE glpi_id=".$glpi_id;
			$result = $DB->query($query);

			//Reload import_software from DB
			if ($DB->numrows($result))
			{
				$tmp = $DB->fetch_array($result);
				$import_software = importArrayFromDB($tmp["import_software"]);
			}
					
		}
		
		$import_software_licensetype = $cfg_ocs["import_software_licensetype"];
		$import_software_buy = $cfg_ocs["import_software_buy"];

		//---- Get all the softwares for this machine from OCS -----//
		if ($cfg_ocs["use_soft_dict"])
			$query2 = "SELECT softwares.NAME AS INITNAME, dico_soft.FORMATTED AS NAME, 
							softwares.VERSION AS VERSION, softwares.PUBLISHER AS PUBLISHER, softwares.COMMENTS AS COMMENTS 
							FROM softwares 
							INNER JOIN dico_soft ON (softwares.NAME = dico_soft.EXTRACTED) 
							WHERE softwares.HARDWARE_ID='$ocs_id'";
		else
			$query2 = "SELECT softwares.NAME AS INITNAME, softwares.NAME AS NAME, 
								softwares.VERSION AS VERSION, softwares.PUBLISHER AS PUBLISHER, softwares.COMMENTS AS COMMENTS
								FROM softwares 
								WHERE softwares.HARDWARE_ID='$ocs_id'";

		$result2 = $DBocs->query($query2);
		$to_add_to_ocs_array = array ();
		$soft = new Software;
		
		if ($DBocs->numrows($result2) > 0)
			while ($data2 = $DBocs->fetch_array($result2)) {
				$data2 = clean_cross_side_scripting_deep(addslashes_deep($data2));
				$initname = $data2["INITNAME"];
				$name = $data2["NAME"];
				$version = $data2["VERSION"];

				// Clean software object
				$soft->reset();

				//If name+version not in present for this computer in glpi, add it 
				if (!in_array($initname . '$$$$$'. $version, $import_software)) 
				{
						//------------------------------------------------------------------------------------------------------------------//
						//---- The software doesn't exists in this version for this computer -----//
						//----------------------------------------------------------------------------------------------------------------//

						//Look for the software by his name in GLPI for a specific entity
						$query_search = "SELECT glpi_software.ID as ID  
												FROM glpi_software 
												WHERE name = '" . $name . "' AND FK_entities=" . $entity;
						$result_search = $DB->query($query_search);
						if ($DB->numrows($result_search) > 0) {
							//Software already exists for this entity, get his ID
							$data = $DB->fetch_array($result_search);
							$isNewSoft = $data["ID"];
						} else
						$isNewSoft = 0;
						
						if (!$isNewSoft) {
							$input = array ();
							$input["name"] = $name;
							$input["comments"] = $data2["COMMENTS"];
							$input["FK_entities"] = $entity;
	
							if (!empty ($data2["PUBLISHER"])) {
								$input["FK_glpi_enterprise"] = ocsImportDropdown("glpi_dropdown_manufacturer", $data2["PUBLISHER"]);
							}
							$input["_from_ocs"] = 1;
							$isNewSoft = $soft->add($input);
	
						}
						
						//Import license for this software
						$licenseID = ocsImportLicense($isNewSoft, $version);
	
						//Install license for this machine
						$instID = installSoftware($glpi_id, $licenseID, '', $dohistory);
						
						//Add the software to the table of softwares for this computer to add in database
						$to_add_to_ocs_array[$instID] = $initname . '$$$$$'. $version;

				} else {
					$instID = -1;

					//------------------------------------------------------------------------------------------------------------------//
					//--------------------- The software exists in this version for this computer --------------//
					//----------------------------------------------------------------------------------------------------------------//

					//Get the name of the software in GLPI to know if the software's name have already been changed by the OCS dictionnary
					$instID = array_search($initname . '$$$$$'. $version, $import_software);
					$query_soft = "SELECT glpi_software.ID, glpi_software.name FROM glpi_software, glpi_inst_software, glpi_licenses".
					" WHERE glpi_inst_software.ID=".$instID." AND glpi_inst_software.license=glpi_licenses.ID AND glpi_licenses.sID=glpi_software.ID";

					$result_soft = $DB->query($query_soft);
					$tmpsoft = $DB->fetch_array($result_soft);
					$softName = $tmpsoft["name"];
					$softID = $tmpsoft["ID"];
					$s = new Software;
					$input["ID"]=$softID;
					$input["_from_ocs"] = 1;
	
					//First, get the name of the software into GLPI db IF dictionnary is used
					if ($cfg_ocs["use_soft_dict"])
					{
							//First use of the dictionnary OR name changed in the dictionnary
							if ($softName != $name)
							{
								$input["name"]=$name;
								$s->update($input);
							}
					}
					//Dictionnary not use anymore : revert to original name
					elseif ($softName != $initname)
					{	
						$input["name"]=$initname;
						$s->update($input);
					}
					
					unset ($import_software[$instID]);
				}
			}
		
		//Remove the tag from the import_software array
		unset ($import_software[0]);

		//Add all the new softwares
		if (count($to_add_to_ocs_array)) {
			addToOcsArray($glpi_id, $to_add_to_ocs_array, "import_software");
		}

		// Remove softwares not present in OCS
		if (count($import_software)) {

			foreach ($import_software as $key => $val) {

				$query = "SELECT * 
									FROM glpi_inst_software 
									WHERE ID = '" . $key . "'";
				$result = $DB->query($query);
				if ($DB->numrows($result) > 0) {
					if ($data = $DB->fetch_assoc($result)) {
						uninstallSoftware($key, $dohistory);
						$query2 = "SELECT COUNT(*) 
													FROM glpi_inst_software 
													WHERE license = '" . $data['license'] . "'";
						$result2 = $DB->query($query2);
						if ($DB->result($result2, 0, 0) == 1) {
							$lic = new License;
							$lic->getfromDB($data['license']);
							$query3 = "SELECT COUNT(*) 
																FROM glpi_licenses 
																WHERE sID='" . $lic->fields['sID'] . "'";
							$result3 = $DB->query($query3);
							if ($DB->result($result3, 0, 0) == 1) {
								$soft = new Software();
								$soft->delete(array (
									'ID' => $lic->fields['sID'],
									"_from_ocs" => 1
								), 0);
							}
							$lic->delete(array (
								"ID" => $data['license'],
								"_from_ocs" => 1
							));
						}
					}
				}

				deleteInOcsArray($glpi_id, $key, "import_software");
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
 *@param $version : version of the software
 *@param $serial : default serial (used to identify global and freelicenses).
 *@param $buy : is the license buyed ?
 *
 *@return integer : inserted license id.
 *
 **/
function ocsImportLicense($software, $version, $serial = "global", $buy = "0", $oem = "0") {
	global $DB, $LANGOcs;

	$query = "SELECT ID 
				FROM glpi_licenses 
				WHERE sID = '" . $software . "' 
				AND version='" . $version . "'
				AND serial='" . $serial . "' 
				AND buy='" . $buy . "'"; #TODO serial => type
	$result = $DB->query($query);
	if ($DB->numrows($result) > 0) {
		$data = $DB->fetch_array($result);
		$isNewLicc = $data["ID"];
	} else {
		$isNewLicc = 0;
	}
	if (!$isNewLicc) {
		$licc = new License;
		$input["sID"] = $software;
		$input["serial"] = $serial;
		$input["buy"] = $buy;
		$input["version"] = $version;
		$input["oem"] = $oem;
		$input["_from_ocs"] = 1;
		$isNewLicc = $licc->add($input);
	}
	return ($isNewLicc);
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
	global $DB;

	$query = "SELECT * 
				FROM glpi_inst_software 
				WHERE cid = '" . $glpi_computer_id . "'";
	$result = $DB->query($query);
	if ($DB->numrows($result) > 0) {
		while ($data = $DB->fetch_assoc($result)) {
			$query2 = "SELECT COUNT(*) 
										FROM glpi_inst_software 
										WHERE license = '" . $data['license'] . "'";
			$result2 = $DB->query($query2);
			if ($DB->result($result2, 0, 0) == 1) {
				$lic = new License;
				$lic->getfromDB($data['license']);
				$query3 = "SELECT COUNT(*) 
													FROM glpi_licenses 
													WHERE sID='" . $lic->fields['sID'] . "'";
				$result3 = $DB->query($query3);
				if ($DB->result($result3, 0, 0) == 1) {
					$soft = new Software();
					$soft->delete(array (
						'ID' => $lic->fields['sID'],
						"_from_ocs" => 1
					), 1);
				}
				$lic->delete(array (
					"ID" => $data['license'],
					"_from_ocs" => 1
				));

			}
		}

		$query = "DELETE FROM glpi_inst_software 
							WHERE cid = '" . $glpi_computer_id . "'";
		$DB->query($query);
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
	global $DB;
	$query = "DELETE FROM glpi_computer_device 
				WHERE device_type = '" . $device_type . "' 
				AND FK_computers = '" . $glpi_computer_id . "'";
	$DB->query($query);
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

	global $DB;

	$query = "SELECT * 
				FROM glpi_connect_wire 
				WHERE end2 = '" . $glpi_computer_id . "' 
				AND type = '" . PERIPHERAL_TYPE . "'";
	$result = $DB->query($query);
	$per = new Peripheral();
	if ($DB->numrows($result) > 0) {
		while ($data = $DB->fetch_assoc($result)) {
			$query2 = "SELECT COUNT(*) 
										FROM glpi_connect_wire 
										WHERE end1 = '" . $data['end1'] . "' 
										AND type = '" . PERIPHERAL_TYPE . "'";
			$result2 = $DB->query($query2);
			if ($DB->result($result2, 0, 0) == 1) {
				$per->delete(array (
					'ID' => $data['end1'],
					"_from_ocs" => 1
				), 1);
			}
		}

		$query2 = "DELETE FROM glpi_connect_wire 
							WHERE end2 = '" . $glpi_computer_id . "' 
							AND type = '" . PERIPHERAL_TYPE . "'";
		$DB->query($query2);
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

	global $DB;
	$query = "SELECT * 
				FROM glpi_connect_wire 
				WHERE end2 = '" . $glpi_computer_id . "' 
				AND type = '" . MONITOR_TYPE . "'";
	$result = $DB->query($query);
	$mon = new Monitor();
	if ($DB->numrows($result) > 0) {
		while ($data = $DB->fetch_assoc($result)) {
			$query2 = "SELECT COUNT(*) 
										FROM glpi_connect_wire 
										WHERE end1 = '" . $data['end1'] . "' 
										AND type = '" . MONITOR_TYPE . "'";
			$result2 = $DB->query($query2);
			if ($DB->result($result2, 0, 0) == 1) {
				$mon->delete(array (
					'ID' => $data['end1'],
					"_from_ocs" => 1
				), 1);
			}
		}

		$query2 = "DELETE FROM glpi_connect_wire 
							WHERE end2 = '" . $glpi_computer_id . "' 
							AND type = '" . MONITOR_TYPE . "'";
		$DB->query($query2);
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

	global $DB;

	$query = "SELECT * 
				FROM glpi_connect_wire 
				WHERE end2 = '" . $glpi_computer_id . "' 
				AND type = '" . PRINTER_TYPE . "'";
	$result = $DB->query($query);
	if ($DB->numrows($result) > 0) {
		while ($data = $DB->fetch_assoc($result)) {
			$query2 = "SELECT COUNT(*) 
										FROM glpi_connect_wire 
										WHERE end1 = '" . $data['end1'] . "' 
										AND type = '" . PRINTER_TYPE . "'";
			$result2 = $DB->query($query2);
			$printer = new Printer();
			if ($DB->result($result2, 0, 0) == 1) {
				$printer->delete(array (
					'ID' => $data['end1'],
					"_from_ocs" => 1
				), 1);
			}
		}

		$query2 = "DELETE FROM glpi_connect_wire 
							WHERE end2 = '" . $glpi_computer_id . "' 
							AND type = '" . PRINTER_TYPE . "'";
		$DB->query($query2);
	}
}
/**
 * Delete old registry entries

 *
 *@param $glpi_computer_id integer : glpi computer id.
 *
 *@return nothing.
 *
 **/
function ocsResetRegistry($glpi_computer_id) {

	global $DB;

	$query = "SELECT * 
				FROM glpi_registry 
				WHERE computer_id = '" . $glpi_computer_id . "'";
	$result = $DB->query($query);
	if ($DB->numrows($result) > 0) {
		while ($data = $DB->fetch_assoc($result)) {
			$query2 = "SELECT COUNT(*) 
										FROM glpi_registry 
										WHERE computer_id = '" . $data['computer_id'] . "'";
			$result2 = $DB->query($query2);
			$registry = new Registry();
			if ($DB->result($result2, 0, 0) == 1) {
				$registry->delete(array (
					'ID' => $data['computer_id'],
					"_from_ocs" => 1
				), 1);
			}
		}
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
function ocsResetDropdown($glpi_computer_id, $field, $table) {

	global $DB;
	$query = "SELECT $field AS VAL 
				FROM glpi_computers 
				WHERE ID = '" . $glpi_computer_id . "'";
	$result = $DB->query($query);
	if ($DB->numrows($result) == 1) {
		$value = $DB->result($result, 0, "VAL");
		$query = "SELECT COUNT(*) AS CPT 
							FROM glpi_computers 
							WHERE $field = '$value'";
		$result = $DB->query($query);
		if ($DB->result($result, 0, "CPT") == 1) {
			$query2 = "DELETE FROM $table 
										WHERE ID = '$value'";
			$DB->query($query2);
		}
	}
}

/**
 * Choose an ocs server
 *
 * @return nothing. 
 */
function ocsChooseServer($target) {
	global $DB, $LANG;

	$query = "SELECT * FROM glpi_ocs_config WHERE is_template='0' ORDER BY name ASC";
	$result = $DB->query($query);
	if ($DB->numrows($result) > 1) {
		echo "<form action=\"$target\" method=\"get\">";
		echo "<div align='center'>";
		echo "<table class='tab_cadre'>";
		echo "<tr class='tab_bg_2'><th colspan='2'>" . $LANG["ocsng"][26] . "</th></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>" . $LANG["common"][16] . "</td><td align='center'>";
		echo "<select name='ocs_server_id'>";
		while ($ocs = $DB->fetch_array($result))
			echo "<option value=" . $ocs["ID"] . ">" . $ocs["name"] . "</option>";

		echo "</select></td></tr>";
		echo "<tr class='tab_bg_2'><td align='center' colspan=2><input class='submit' type='submit' name='ocs_showservers' value='" . $LANG["buttons"][2] . "'></td></tr>";
		echo "</table></div></form>";

	}
	elseif ($DB->numrows($result) == 1) {
		$ocs = $DB->fetch_array($result);
		glpi_header($_SERVER['PHP_SELF'] . "?ocs_server_id=" . $ocs["ID"]);
	} else{
		echo "<form action=\"$target\" method=\"get\">";
		echo "<div align='center'>";
		echo "<table class='tab_cadre'>";
		echo "<tr class='tab_bg_2'><th colspan='2'>" . $LANG["ocsng"][26] . "</th></tr>";
		echo "<tr class='tab_bg_2'><td align='center' colspan=2>" . $LANG["ocsng"][27] . "</td></tr>";
		echo "</table></div></form>";
	}
}

/**
 * Get a connection to the OCS server
 * @param $ocs_server_id the ocs server id
 * @return the connexion to the ocs database
 */
function getDBocs($ocs_server_id) {
	return new DBocs($ocs_server_id);
}

/**
 * Check if OCS connection is always valid
 * If not, then establish a new connection on the good server
 * 
 * @return nothing.
 */
function checkOCSconnection($ocs_server_id) {
	global $DBocs;

	//If $DBocs is not initialized, or if the connection should be on a different ocs server
	// --> reinitialize connection to OCS server 
	if (!$DBocs || $ocs_server_id != $DBocs->getServerID()) {
		$DBocs = getDBocs($ocs_server_id);
	}

	if ($DBocs->error)
		return false;
	else
		return true;
}
/**
 * Get the ocs server id of a machine, by giving the machine id
 * @param $ID the machine ID
 * @return the ocs server id of the machine 
 */
function getOCSServerByMachineID($ID) {
	global $DB;
	$sql = "SELECT ocs_server_id FROM glpi_ocs_link WHERE glpi_ocs_link.glpi_id='" . $ID . "'";
	$result = $DB->query($sql);
	if ($DB->numrows($result) > 0) {
		$datas = $DB->fetch_array($result);
		return $datas["ocs_server_id"];
	}
	return -1;
}

/**
 * Get an Ocs Server name, by giving his ID
 * @return the ocs server name
 */
function getOCSServerNameByID($ID) {
	$ocs_server_id = getOCSServerByMachineID($ID);
	$conf = getOcsConf($ocs_server_id);
	return $conf["name"];
}

/**
 * Get a random ocs_server_id 
 * @return an ocs server id
 */
function getRandomOCSServerID() {
	global $DB;
	$sql = "SELECT ID FROM glpi_ocs_config ORDER BY RAND() LIMIT 1";
	$result = $DB->query($sql);
	if ($DB->numrows($result) > 0) {
		$datas = $DB->fetch_array($result);
		return $datas["ID"];
	}
	return -1;
}

function getColumnListFromAccountInfoTable($ID, $glpi_column) {
	global $DBocs, $DB;
	$listColumn = "";
	if ($ID != -1) {
		checkOCSconnection($ID);
		if (!$DBocs->error) {
			$result = $DBocs->query("SHOW COLUMNS FROM accountinfo");
			if ($DBocs->numrows($result) > 0) {
				while ($data = $DBocs->fetch_array($result)) {
					//get the selected value in glpi if specified
					$query = "SELECT ocs_column from glpi_ocs_admin_link where ocs_server_id='" . $ID . "' and glpi_column='" . $glpi_column . "'";
					$result_DB = $DB->query($query);
					$selected = "";
					if ($DB->numrows($result_DB) > 0) {
						$data_DB = $DB->fetch_array($result_DB);
						$selected = $data_DB["ocs_column"];
					}
					$ocs_column = $data['Field'];
					if (!strcmp($ocs_column, $selected))
						$listColumn .= "<option value='$ocs_column' selected>" . $ocs_column . "</option>";
					else
						$listColumn .= "<option value='$ocs_column'>" . $ocs_column . "</option>";
				}
			}
		}
	}
	return $listColumn;
}

function getListState($ocs_server_id) {
	global $DB, $LANG;
	$queryStateSelected = "SELECT deconnection_behavior from glpi_ocs_config WHERE ID='$ocs_server_id'";
	$resultSelected = $DB->query($queryStateSelected);
	$selected = 0;
	if ($DB->numrows($resultSelected) > 0) {
		$res = $DB->fetch_array($resultSelected);
		$selected = $res["deconnection_behavior"];
	}

	$values[''] = "-----";
	$values["trash"] = $LANG["ocsconfig"][49];
	$values["delete"] = $LANG["ocsconfig"][50];

	$queryStateList = "SELECT name from glpi_dropdown_state";
	$result = $DB->query($queryStateList);
	if ($DB->numrows($result) > 0) {
		while (($data = $DB->fetch_array($result)))
			$values[$data["name"]] = $LANG["ocsconfig"][51] .
			" " . $data["name"];

	}
	dropdownArrayValues("deconnection_behavior", $values, $selected);
}

function setEntityLock($entity) {
	global $CFG_GLPI;
	$fp = fopen(GLPI_LOCK_DIR . "/lock_entity_" . $entity, "w+");

	if (flock($fp, LOCK_EX)) {
		return $fp;
	} else {
		return false;
	}
}

function removeEntityLock($entity, $fp) {
	flock($fp, LOCK_UN);
	fclose($fp);

	//Test if the lock file still exists before removing it
	// (sometimes another thread already already removed the file)
	if (file_exists(GLPI_LOCK_DIR . "/lock_entity_" . $entity)) {
		unlink(GLPI_LOCK_DIR . "/lock_entity_" . $entity);
	}
}

function getMaterialManagementMode($ocs_config, $device_type) {
	global $LANG;
	switch ($device_type) {
		case MONITOR_TYPE :
			return $ocs_config["import_monitor"];
			break;
		case PRINTER_TYPE :
			return $ocs_config["import_printer"];
			break;
		case PERIPHERAL_TYPE :
			return $ocs_config["import_periph"];
			break;
	}
}
?>
