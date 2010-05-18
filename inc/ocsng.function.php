<?php


/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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

function ocsGetTagLimit($cfg_ocs){

	$WHERE="";
	if (!empty ($cfg_ocs["tag_limit"])){
		$splitter = explode("$", trim($cfg_ocs["tag_limit"]));
		if (count($splitter)) {
			$WHERE = "( accountinfo.TAG='" . $splitter[0] . "' ";
			for ($i = 1; $i < count($splitter); $i++){
				$WHERE .= " OR accountinfo.TAG='" .$splitter[$i] . "' ";
			}
			$WHERE .= ")";
		}
	}
	if (!empty ($cfg_ocs["tag_exclude"])){
		$splitter = explode("$", $cfg_ocs["tag_exclude"]);

		if (count($splitter)) {
			if (!empty($WHERE)){
				$WHERE.=" AND ";
			}
			$WHERE .= "accountinfo.TAG <> '" . $splitter[0] . "' ";
			for ($i = 1; $i < count($splitter); $i++){
				$WHERE .= " AND accountinfo.TAG <> '" .$splitter[$i] . "' ";
			}
		}
	}
	return $WHERE;
}

function ocsShowNewComputer($ocs_server_id, $advanced, $check, $start, $entity=0, $tolinked = false) {
	global $DB, $DBocs, $LANG, $CFG_GLPI;

	if (!haveRight("ocsng", "w"))
		return false;

	$cfg_ocs = getOcsConf($ocs_server_id);

	$WHERE = ocsGetTagLimit($cfg_ocs);

	$query_ocs = "SELECT hardware.*, accountinfo.TAG AS TAG, bios.SSN as SERIAL
			FROM hardware
			INNER JOIN accountinfo ON (hardware.ID = accountinfo.HARDWARE_ID)
			INNER JOIN bios ON (hardware.ID = bios.HARDWARE_ID)".
			(!empty($WHERE)?"WHERE $WHERE":"")." ORDER BY hardware.NAME";
	$result_ocs = $DBocs->query($query_ocs);

	// Existing OCS - GLPI link
	$query_glpi = "SELECT *
			FROM glpi_ocs_link
			WHERE ocs_server_id='".$ocs_server_id."'";
	$result_glpi = $DB->query($query_glpi);

	if ($tolinked){
		// Computers existing in GLPI
		$query_glpi_comp = "SELECT ID,name
				FROM glpi_computers
				WHERE is_template='0' AND FK_entities IN (".$_SESSION["glpiactiveentities_string"].")";
		$result_glpi_comp = $DB->query($query_glpi_comp);
	}

	if ($DBocs->numrows($result_ocs) > 0) {

		// Get all hardware from OCS DB
		$hardware = array ();
		while ($data = $DBocs->fetch_array($result_ocs)) {
			$data = clean_cross_side_scripting_deep(addslashes_deep($data));
			$hardware[$data["ID"]]["date"] = $data["LASTDATE"];
			$hardware[$data["ID"]]["name"] = $data["NAME"];
			$hardware[$data["ID"]]["TAG"] = $data["TAG"];
			$hardware[$data["ID"]]["ID"] = $data["ID"];
			$hardware[$data["ID"]]["serial"] = $data["SERIAL"];
		}

		// Get all links between glpi and OCS
		$already_linked = array ();
		if ($DB->numrows($result_glpi) > 0) {
			while ($data = $DBocs->fetch_array($result_glpi)) {
				$already_linked[$data["ocs_id"]] = $data["last_update"];
			}
		}

		// Get all existing computers name in GLPI
		if ($tolinked){
			$computer_names = array ();
			if ($DB->numrows($result_glpi_comp) > 0) {
				while ($data = $DBocs->fetch_array($result_glpi_comp)) {
					// TODO store multiple values for each name
					// really usefull for big database ?
					$computer_names[strtolower($data["name"])] = $data["ID"];
				}
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
			echo "<div class='center'><strong>" . $LANG['ocsng'][22] . "</strong></div>";
		}

		echo "<div class='center'>";
		if (($numrows = count($hardware)) > 0) {

			$parameters = "check=$check";
			printPager($start, $numrows, $_SERVER['PHP_SELF'], $parameters);

			// delete end
			array_splice($hardware, $start + $_SESSION['glpilist_limit']);
			// delete begin
			if ($start > 0)
				array_splice($hardware, 0, $start);

			if (!$tolinked) {
				echo "<form method='post' name='ocsng_import_mode' id='ocsng_import_mode' action='" . $_SERVER['PHP_SELF'] . "'>";

				echo "<table class='tab_cadre'>";
				echo "<tr><th>" . $LANG['ocsng'][41] . "</th></tr>";
				echo "<tr class='tab_bg_1'>";
				echo "<td class='center'>";

				if ($advanced)
					$status = "false";
				else
					$status = "true";

				echo "<a href='" . $_SERVER['PHP_SELF'] . "?change_import_mode=" . $status . "'>";
				if ($advanced)
					echo $LANG['ocsng'][38];
				else
					echo $LANG['ocsng'][37];
				echo "</a></td>";
				echo "</tr></table></form><br>";
			}

			echo "<strong>" . $LANG['ocsconfig'][18] . "</strong><br>";
			echo "<form method='post' name='ocsng_form' id='ocsng_form' action='" . $_SERVER['PHP_SELF'] . "'>";
			if (!$tolinked){
				echo "<a href='" . $_SERVER['PHP_SELF'] . "?check=all&amp;start=$start' onclick= \"if ( markCheckboxes('ocsng_form') ) return false;\">" . $LANG['buttons'][18] . "</a>&nbsp;/&nbsp;<a href='" . $_SERVER['PHP_SELF'] . "?check=none&amp;start=$start' onclick= \"if ( unMarkCheckboxes('ocsng_form') ) return false;\">" . $LANG['buttons'][19] . "</a>";
			}

			echo "<table class='tab_cadre'>";

			echo "<tr><th>" . $LANG['ocsng'][5] . "</th><th>".$LANG['common'][19]."</th><th>" . $LANG['common'][27] . "</th><th>TAG</th>";
			if ($advanced && !$tolinked) {
				echo "<th>" . $LANG['ocsng'][40] . "</th>";
				echo "<th>" . $LANG['ocsng'][36] . "</th>";
			}
			echo "<th>&nbsp;</th></tr>";

			echo "<tr class='tab_bg_1'><td colspan='" . ($advanced ? 7 : 5) . "' align='center'>";
			echo "<input class='submit' type='submit' name='import_ok' value='" . $LANG['buttons'][37] . "'>";
			echo "</td></tr>";

			$rule = new OcsRuleCollection($ocs_server_id);

			foreach ($hardware as $ID => $tab) {
				$comp = new Computer;
				$comp->fields["ID"] = $tab["ID"];

				$data = array ();
				if ($advanced && !$tolinked)
					$data = $rule->processAllRules(array (), array (), $tab["ID"]);

				echo "<tr class='tab_bg_2'><td>" . $tab["name"] . "</td><td>".$tab["serial"]."</td><td>" . convDateTime($tab["date"]) . "</td><td>" . $tab["TAG"] . "</td>";

				if ($advanced && !$tolinked) {
					if (!isset ($data['FK_entities'])) {
						echo "<td class='center'><img src=\"" . GLPI_ROOT . "/pics/redbutton.png\"></td>";
						$data['FK_entities'] = -1;
					} else
						echo "<td class='center'><img src=\"" . GLPI_ROOT . "/pics/greenbutton.png\"></td>";

					echo "<td>";
					dropdownValue("glpi_entities", "toimport_entities[" . $tab["ID"] . "]=" . $data['FK_entities'], $data['FK_entities'], 0);
					echo "</td>";
				}

				echo "<td>";
				if (!$tolinked){
					echo "<input type='checkbox' name='toimport[" . $tab["ID"] . "]' " . ($check == "all" ? "checked" : "") . ">";
				} else {
					// TODO : display in entity mode
					if (isset ($computer_names[strtolower($tab["name"])])){
						dropdownValue("glpi_computers", "tolink[" .
						$tab["ID"] . "]", $computer_names[strtolower($tab["name"])]);
					} else {
						dropdown("glpi_computers", "tolink[" .
						$tab["ID"] . "]");
					}
				}
				echo "</td>";

				echo "</tr>";
			}
			echo "<tr class='tab_bg_1'><td colspan='" . ($advanced ? 7 : 5) . "' align='center'>";
			echo "<input class='submit' type='submit' name='import_ok' value='" . $LANG['buttons'][37] . "'>";
			echo "<input type=hidden name='ocs_server_id' value='" . $ocs_server_id . "'>";
			echo "</td></tr>";
			echo "</table>";
			echo "</form>";

			printPager($start, $numrows, $_SERVER['PHP_SELF'], $parameters);

		} else
			echo "<strong>" . $LANG['ocsng'][9] . "</strong>";

		echo "</div>";

	} else
		echo "<div class='center'><strong>" . $LANG['ocsng'][9] . "</strong></div>";
}

/**
 * Make the item link between glpi and ocs.
 *
 * This make the database link between ocs and glpi databases
 *
 *@param $ocs_id integer : ocs item unique id.
 *@param $glpi_computer_id integer : glpi computer id
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
		// TODO : Check if this code part is ok ? why to send a link if insert do not works ? May have problem for example on ocsLinkComputer
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

	$query = "SELECT * FROM deleted_equiv ORDER BY DATE";
	$result = $DBocs->query($query);
	if ($DBocs->numrows($result)) {
		$deleted = array ();
		while ($data = $DBocs->fetch_array($result)) {
			$deleted[$data["DELETED"]] = $data["EQUIVALENT"];
		}

		if (count($deleted)){
			foreach ($deleted as $del => $equiv) {
				if (!empty ($equiv)&&!is_null($equiv)) { // New name


					// Get hardware due to bug of duplicates management of OCS
					if (strstr($equiv,"-")) {
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

					if ($data) {
						$sql_id = "SELECT glpi_id
							FROM glpi_ocs_link
							WHERE ocs_id='".$data["ID"]."' AND ocs_server_id='$ocs_server_id'";
						if ($res_id = $DB->query($sql_id)){
							if ($DB->numrows($res_id)>0){
								//Add history to indicates that the ocs_id changed
								$changes[0]='0';
								//Old ocs_id
								$changes[1]=$del;
								//New ocs_id
								$changes[2]=$data["ID"];
								historyLog ($DB->result($res_id,0,"glpi_id"),COMPUTER_TYPE,$changes,0,HISTORY_OCS_IDCHANGED);
							}
						}
					}
				} else { // Deleted
					if (strstr($del,"-"))
						$query = "SELECT *
							FROM glpi_ocs_link
							WHERE ocs_deviceid='$del' AND ocs_server_id='$ocs_server_id'";
					else
						$query = "SELECT *
							FROM glpi_ocs_link
							WHERE ocs_id='$del' AND ocs_server_id='$ocs_server_id'";
					if ($result = $DB->query($query)){
						if ($DB->numrows($result)>0) {
							$data = $DB->fetch_array($result);
							$comp = new Computer();
							$comp->delete(array (
								"ID" => $data["glpi_id"],
							), 0);

							//Add history to indicates that the machine was deleted from OCS
							$changes[0]='0';
							$changes[1]=$data["ocs_id"];
							$changes[2]="";
							historyLog ($data["glpi_id"],COMPUTER_TYPE,$changes,0,HISTORY_OCS_DELETE);

							$query = "DELETE FROM glpi_ocs_link WHERE ID ='" . $data["ID"] . "'";
							$DB->query($query);
						}
					}
				}
				// Delete item in DB
				$equiv_clean="EQUIVALENT = '$equiv'";
				if (empty($equiv)){
					$equiv_clean=" ( EQUIVALENT = '$equiv' OR EQUIVALENT IS NULL ) ";
				}
				$query="DELETE FROM deleted_equiv WHERE DELETED = '$del' AND $equiv_clean";
				$DBocs->query($query);
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
				while (!$fp = setEntityLock($data['FK_entities'])) {
					sleep(1);
				}
			}

			//Check if machine could be linked with another one already in DB
			if ($canlink){
				$found_computers = getMachinesAlreadyInGLPI($ocs_id,$ocs_server_id,$data['FK_entities']);
				// machines founded -> try to link
				if (is_array($found_computers) && count($found_computers)>0){
					foreach ($found_computers as $glpi_id){
						if (ocsLinkComputer($ocs_id,$ocs_server_id,$glpi_id,$canlink)){
							return OCS_COMPUTER_LINKED;
						}
					}
				}
				// Else simple Import
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
				if ($cfg_ocs["default_state"]>0){
					$input["state"] = $cfg_ocs["default_state"];
				}
				$glpi_id = $comp->add($input);

				$ocs_id = $line['ID'];

				$changes[0]='0';
				$changes[1]="";
				$changes[2]=$ocs_id;
				historyLog ($glpi_id,COMPUTER_TYPE,$changes,0,HISTORY_OCS_IMPORT);

				if ($idlink = ocsLink($line['ID'], $ocs_server_id, $glpi_id)) {
					ocsUpdateComputer($idlink, $ocs_server_id, 0);
				}

			}

			if ($lock) {
				removeEntityLock($data['FK_entities'], $fp);
			}
			//Return code to indicates that the machine was imported
			return OCS_COMPUTER_IMPORTED;
		}
		else
			//Return code to indicates that the machine was not imported because it doesn't matched rules
			return OCS_COMPUTER_FAILED_IMPORT;
}

function ocsLinkComputer($ocs_id, $ocs_server_id, $glpi_id,$link_auto=0) {
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

	// TODO : if OCS ID change : ocs_link exists but not hardware in OCS so update only ocs_link and do not reset items before updateComputer

	// No ocs_link or ocs computer does not exists so can link
	if (!$ocs_exists || $numrows == 0) {

		$ocsConfig = getOcsConf($ocs_server_id);

		// Set OCS checksum to max value
		$query = "UPDATE hardware
			SET CHECKSUM='" . MAX_OCS_CHECKSUM . "'
			WHERE ID='$ocs_id'";
		$DBocs->query($query);

		if ($idlink = ocsLink($ocs_id, $ocs_server_id, $glpi_id)) {

			$comp = new Computer;
			$comp->getFromDB($glpi_id);
			$input["ID"] = $glpi_id;
			$input["ocs_import"] = 1;

			// Not already import from OCS / mark default state
			if ($link_auto || (!$comp->fields['ocs_import'] && $ocsConfig["default_state"]>0)) {
				$input["state"] = $ocsConfig["default_state"];
			}

			$comp->update($input);

			// Auto restore if deleted
			if ($comp->fields['deleted']){
				$comp->restore(array('ID'=>$glpi_id));
			}

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
				ocsResetSoftwares($glpi_id);
			if ($cfg_ocs["import_disk"])
				ocsResetDisks($glpi_id);
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
			historyLog ($glpi_id,COMPUTER_TYPE,$changes,0,HISTORY_OCS_LINK);

			ocsUpdateComputer($idlink, $ocs_server_id, 0);
			return true;
		}
	} else {
		addMessageAfterRedirect($ocs_id . " - " . $LANG['ocsng'][23],false,ERROR);
	}

	return false;
}


function ocsProcessComputer($ocs_id, $ocs_server_id, $lock = 0, $defaultentity = -1,$canlink=0) {
	global $DBocs, $DB;

	checkOCSconnection($ocs_server_id);

	$comp = new Computer;

	//Check it machine is already present AND was imported by OCS
	$query = "SELECT ID, glpi_id, ocs_id
		FROM glpi_ocs_link
		WHERE ocs_id = '$ocs_id' AND ocs_server_id='" . $ocs_server_id . "';";
	$result_glpi_ocs_link = $DB->query($query);
	if ($DB->numrows($result_glpi_ocs_link)) {
		$datas = $DB->fetch_array($result_glpi_ocs_link);

		//Return code to indicates that the machine was synchronized or only last inventory date changed
		return ocsUpdateComputer($datas["ID"], $ocs_server_id, 1, 0);
	} else
		return ocsImportComputer($ocs_id, $ocs_server_id, $lock, $defaultentity,$canlink);
}

/** Return array of GLPI computers matching the OCS one using the OCS config
* @param $ocs_id integer : ocs ID of the computer
* @param $ocs_server_id integer : ocs server ID
* @param $entity integer : entity ID
* @return array containing the glpi computer ID
*/
function getMachinesAlreadyInGLPI($ocs_id,$ocs_server_id,$entity){
	global $DB,$DBocs;
	$conf = getOcsConf($ocs_server_id);

	$found_computers=array();

	$sql_fields = "hardware.ID";
	$sql_from ="hardware";
	$first = true;
	$ocsParams = array();

	if ($conf["glpi_link_enabled"])	{
		$ok=false;
		//Build the request against OCS database to get the machine's informations
		if ( $conf["link_ip"] || $conf["link_mac_address"]){
			$sql_from.=" LEFT JOIN networks ON (hardware.ID=networks.HARDWARE_ID) ";
		}

		if ($conf["link_ip"]){
			$sql_fields.=", networks.IPADDRESS";
			$ocsParams["IPADDRESS"] = array();
			$ok=true;
		}
		if ($conf["link_mac_address"]){
			$sql_fields.=", networks.MACADDR";
			$ocsParams["MACADDR"] = array();
			$ok=true;
		}
		if ($conf["link_serial"]){
			$sql_from.=" LEFT JOIN bios ON (bios.HARDWARE_ID=hardware.ID) ";

			$sql_fields.=", bios.SSN";
			$ocsParams["SSN"] = array();
			$ok=true;
		}
		if ($conf["link_name"] > 0){
			$sql_fields.=", hardware.NAME";
			$ocsParams["NAME"] = array();
			$ok=true;
		}

		// No criteria to link
		if (!$ok){
			return -1;
		}

		//Execute request
		$sql = "SELECT ".$sql_fields."
			FROM $sql_from
			WHERE hardware.ID='$ocs_id';";
		$result = $DBocs->query($sql);

		//Get the list of parameters

		while ($dataOcs = $DB->fetch_array($result)){
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
		$sql_where = " FK_entities='$entity' AND is_template=0 ";
		$sql_from = "glpi_computers";
		if ( $conf["link_ip"] || $conf["link_mac_address"]){
			$sql_from.=" LEFT JOIN glpi_networking_ports ON (glpi_computers.ID=glpi_networking_ports.on_device
								AND glpi_networking_ports.device_type=".COMPUTER_TYPE.") ";
		}
		if ($conf["link_ip"]){
			if (empty($ocsParams["IPADDRESS"])){
				return -1;
			} else {
				$sql_where.=" AND glpi_networking_ports.ifaddr IN ";
				for ($i=0; $i < count($ocsParams["IPADDRESS"]);$i++)
					$sql_where .= ($i>0 ? ',"' : '("').$ocsParams["IPADDRESS"][$i].'"';
				$sql_where.=")";
			}
		}
		if ($conf["link_mac_address"]){
			if (empty($ocsParams["MACADDR"])){
				return -1;
			} else {
				$sql_where.=" AND glpi_networking_ports.ifmac IN ";
				for ($i=0; $i < count($ocsParams["MACADDR"]);$i++)
					$sql_where .= ($i>0 ? ',"' : '("').$ocsParams["MACADDR"][$i].'"';
				$sql_where.=")";
			}
		}
		if ($conf["link_name"] > 0){
			//Search only computers with blank name
			if ($conf["link_name"] == 2){
				$sql_where .= " AND (glpi_computers.name='' OR glpi_computers.name IS NULL) ";
			} else {
				if (empty($ocsParams["NAME"]))
					return -1;
				else
					$sql_where .= " AND glpi_computers.name=\"".$ocsParams["NAME"][0]."\"";
			}
		}
		if ($conf["link_serial"]){
			if (empty($ocsParams["SSN"]))
				return -1;
			else
				$sql_where .= " AND glpi_computers.serial=\"".$ocsParams["SSN"][0]."\"";
		}
		if ($conf["link_if_status"] > 0)
			$sql_where .= " AND glpi_computers.state='".$conf["link_if_status"]."'";

		$sql_glpi = "SELECT glpi_computers.ID FROM $sql_from " .
			"WHERE $sql_where ORDER BY `glpi_computers`.`deleted` ASC";
		$result_glpi = $DB->query($sql_glpi);

		if ($DB->numrows($result_glpi) > 0){
			while ($data = $DB->fetch_array($result_glpi)) {
				$found_computers[]=$data['ID'];
			}
		}
	}

	return $found_computers;

}

function ocsUpdateComputer($ID, $ocs_server_id, $dohistory, $force = 0) {
	global $DB, $DBocs, $CFG_GLPI;

	checkOCSconnection($ocs_server_id);

	$cfg_ocs = getOcsConf($ocs_server_id);

	$query = "SELECT *
		FROM glpi_ocs_link
		WHERE ID='$ID' and ocs_server_id='".$ocs_server_id."'";
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
			$data_ocs = addslashes_deep($DBocs->fetch_array($result_ocs));

			// update last_update and and last_ocs_update
			$query = "UPDATE glpi_ocs_link
					SET last_update='" . $_SESSION["glpi_currenttime"] . "',
						last_ocs_update='" . $data_ocs["LASTDATE"] . "',
						ocs_agent_version='".$data_ocs["USERAGENT"]." '
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

			//By default log history
			$loghistory["history"] = 1;

			// Is an update to do ?
			if ($mixed_checksum) {
				// Get updates on computers :
				$computer_updates = importArrayFromDB($line["computer_update"]);

				// Update Administrative informations
				ocsUpdateAdministrativeInfo($line['glpi_id'], $line['ocs_id'], $ocs_server_id, $cfg_ocs, $computer_updates, $comp->fields['FK_entities'], $dohistory);

				if ($mixed_checksum & pow(2, HARDWARE_FL))
					$loghistory = ocsUpdateHardware($line['glpi_id'], $line['ocs_id'], $ocs_server_id, $cfg_ocs, $computer_updates, $dohistory);

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
					// Get import peripheral
					$import_peripheral = importArrayFromDB($line["import_peripheral"]);
					ocsUpdatePeripherals(PERIPHERAL_TYPE, $comp->fields["FK_entities"], $line['glpi_id'], $line['ocs_id'], $ocs_server_id, $cfg_ocs, $import_peripheral, $dohistory);
				}
				if ($mixed_checksum & pow(2, SOFTWARES_FL)) {
					// Get import software
					$import_software = importArrayFromDB($line["import_software"]);
					ocsUpdateSoftware($line['glpi_id'], $comp->fields["FK_entities"], $line['ocs_id'], $ocs_server_id, $cfg_ocs, $import_software, (!$loghistory["history"]?0:$dohistory));
				}
				if ($mixed_checksum & pow(2, DRIVES_FL)) {
					// Get import drives
					$import_disk = importArrayFromDB($line["import_disk"]);
					ocsUpdateDisk($line['glpi_id'], $line['ocs_id'], $ocs_server_id, $cfg_ocs, $import_disk);
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

				//Return code to indicate that computer was synchronized
				return OCS_COMPUTER_SYNCHRONIZED;
			}
			else
				//Return code to indicate only last inventory date changed
				return OCS_COMPUTER_NOTUPDATED;

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
/* // NOT_USED
function getNumberOfOcsConfigs() {
	global $DB, $CACHE_CFG;

	return countElementsInTable("glpi_ocs_config");
}
*/

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

	$logHistory = 1;

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

		$sql_computer = "SELECT glpi_dropdown_os.name as os_name, glpi_dropdown_os_sp.name as os_sp" .
			" FROM glpi_computers, glpi_ocs_link, glpi_dropdown_os, glpi_dropdown_os_sp" .
			" WHERE glpi_ocs_link.glpi_id = glpi_computers.ID AND glpi_dropdown_os.ID = glpi_computers.os
				AND glpi_dropdown_os_sp.ID = glpi_computers.os_sp
				AND glpi_ocs_link.ocs_id='".$ocs_id."' AND glpi_ocs_link.ocs_server_id='".$ocs_server_id."'";

		$res_computer = $DB->query($sql_computer);
		if ($DB->numrows($res_computer) ==  1)
		{
			$data_computer = $DB->fetch_array($res_computer);
			$computerOS = $data_computer["os_name"];
			$computerOSSP = $data_computer["os_sp"];

			//Do not log software history in case of OS or Service Pack change
			if (!$dohistory || $computerOS != $line["OSNAME"] || $computerOSSP != $line["OSCOMMENTS"])
				$logHistory = 0;
		}

		if ($cfg_ocs["import_general_os"]) {
			if (!in_array("os", $computer_updates)) {
				$osname=$line["OSNAME"];
				// Hack for OCS encoding problems
				if (!seems_utf8($osname)){
					$osname=utf8_encode($osname);
				}

				$compupdate["os"] = externalImportDropdown('glpi_dropdown_os', $osname);
			}
			if (!in_array("os_version", $computer_updates)) {
				$compupdate["os_version"] = externalImportDropdown('glpi_dropdown_os_version', $line["OSVERSION"]);
			}
			if (!strpos($line["OSCOMMENTS"],"CEST") && !in_array("os_sp", $computer_updates)) // Not linux comment
				$compupdate["os_sp"] = externalImportDropdown('glpi_dropdown_os_sp', $line["OSCOMMENTS"]);
		}

		if ($cfg_ocs["import_general_domain"] && !in_array("domain", $computer_updates)) {
			$compupdate["domain"] = externalImportDropdown('glpi_dropdown_domain', $line["WORKGROUP"]);
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
			$comp->update($compupdate, $dohistory);
		}

	}
	return array("history"=>$logHistory);
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
			$compupdate["model"] = externalImportDropdown('glpi_dropdown_model', $line["SMODEL"],-1,(isset($line["SMANUFACTURER"])?array("manufacturer"=>$line["SMANUFACTURER"]):array()));
		}

		if ($cfg_ocs["import_general_enterprise"] && !in_array("FK_glpi_enterprise", $computer_updates)) {
			$compupdate["FK_glpi_enterprise"] = externalImportDropdown("glpi_dropdown_manufacturer", $line["SMANUFACTURER"]);
		}

		if ($cfg_ocs["import_general_type"] && !empty ($line["TYPE"]) && !in_array("type", $computer_updates)) {
			$compupdate["type"] = externalImportDropdown('glpi_type_computers', $line["TYPE"]);
		}

		if (count($compupdate)) {
			$compupdate["ID"] = $glpi_id;
			$comp = new Computer();
			$comp->update($compupdate, $dohistory);
		}

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
		return $group->add($input);
	} else {
		$line2 = $DB->fetch_array($result2);
		return $line2["ID"];
	}

}

function ocsCleanLinks($ocs_server_id) {
	global $DB, $DBocs;

	checkOCSconnection($ocs_server_id);
	ocsManageDeleted($ocs_server_id);

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
	$query_ocs = "SELECT * FROM hardware";
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
			logInFile("cron", "Check updates from server " . $cfg_ocs['name'] . "\n");

		if (!$cfg_ocs["cron_sync_number"]){
			return 0;
		}
		ocsManageDeleted($ocs_server_id);


		$query = "SELECT MAX(last_ocs_update)
			FROM glpi_ocs_link
			WHERE ocs_server_id='$ocs_server_id'";
		$max_date="0000-00-00 00:00:00";
		if ($result=$DB->query($query)){
			if ($DB->numrows($result)>0){
				$max_date=$DB->result($result,0,0);
			}
		}

		$query_ocs = "SELECT * FROM hardware INNER JOIN accountinfo ON (hardware.ID = accountinfo.HARDWARE_ID)
			WHERE ((hardware.CHECKSUM & " . $cfg_ocs["checksum"] . ") > 0 OR hardware.LASTDATE > '$max_date') ";

		// workaround to avoid duplicate when synchro occurs during an inventory
		// "after" insert in ocsweb.hardware  and "before" insert in ocsweb.deleted_equiv
		$query_ocs .= " AND TIMESTAMP(LASTDATE) < (NOW()-180) ";

		$tag_limit=ocsGetTagLimit($cfg_ocs);
		if (!empty($tag_limit)){
			$query_ocs.= "AND ".$tag_limit;
		}

		$query_ocs.=" ORDER BY hardware.LASTDATE ASC LIMIT ".intval($cfg_ocs["cron_sync_number"]);

		$result_ocs = $DBocs->query($query_ocs);
		if ($DBocs->numrows($result_ocs) > 0) {
			while ($data = $DBocs->fetch_array($result_ocs)) {
				ocsProcessComputer($data["ID"],$ocs_server_id,0,-1,1);
				logInFile("cron", "Update computer " . $data["ID"] . "\n");
			}
		} else {
			return 0;
		}
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
			WHERE glpi_ocs_link.ocs_server_id='" . $ocs_server_id . "'
			ORDER BY glpi_ocs_link.auto_update DESC, glpi_ocs_link.last_update, glpi_computers.name";

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

		echo "<div class='center'>";
		echo "<h2>" . $LANG['ocsng'][10] . "</h2>";

		if (($numrows = count($already_linked)) > 0) {

			$parameters = "check=$check";
			printPager($start, $numrows, $_SERVER['PHP_SELF'], $parameters);

			// delete end
			array_splice($already_linked, $start + $_SESSION['glpilist_limit']);
			// delete begin
			if ($start > 0)
				array_splice($already_linked, 0, $start);

			echo "<form method='post' id='ocsng_form' name='ocsng_form' action='" . $_SERVER['PHP_SELF'] . "'>";

			echo "<a href='" . $_SERVER['PHP_SELF'] . "?check=all' onclick= \"if ( markCheckboxes('ocsng_form') ) return false;\">" . $LANG['buttons'][18] . "</a>&nbsp;/&nbsp;<a href='" . $_SERVER['PHP_SELF'] . "?check=none' onclick= \"if ( unMarkCheckboxes('ocsng_form') ) return false;\">" . $LANG['buttons'][19] . "</a>";
			echo "<table class='tab_cadre'>";
			echo "<tr><th>" . $LANG['ocsng'][11] . "</th><th>" . $LANG['ocsng'][13] . "</th><th>" . $LANG['ocsng'][14] . "</th><th>" . $LANG['ocsng'][6] . "</th><th>&nbsp;</th></tr>";

			echo "<tr class='tab_bg_1'><td colspan='5' align='center'>";
			echo "<input class='submit' type='submit' name='update_ok' value='" . $LANG['ldap'][15] . "'>";
			echo "</td></tr>";

			foreach ($already_linked as $ID => $tab) {

				echo "<tr align='center' class='tab_bg_2'>";
				echo "<td><a href='" . $CFG_GLPI["root_doc"] . "/front/computer.form.php?ID=" . $tab["glpi_id"] . "'>" . $tab["name"] . "</a></td>";
				echo "<td>" . convDateTime($tab["date"]) . "</td><td>" . convDateTime($hardware[$tab["ocs_id"]]["date"]) . "</td>";
				echo "<td>" . $LANG['choice'][$tab["auto_update"]] . "</td>";
				echo "<td><input type='checkbox' name='toupdate[" . $tab["ID"] . "]' " . ($check == "all" ? "checked" : "") . ">";
				echo "</td></tr>";
			}
			echo "<tr class='tab_bg_1'><td colspan='5' align='center'>";
			echo "<input class='submit' type='submit' name='update_ok' value='" . $LANG['ldap'][15] . "'>";
			echo "<input type=hidden name='ocs_server_id' value='" . $ocs_server_id . "'>";
			echo "</td></tr>";
			echo "</table>";
			echo "</form>";
			printPager($start, $numrows, $_SERVER['PHP_SELF'], $parameters);

		} else
			echo "<br><strong>" . $LANG['ocsng'][11] . "</strong>";

		echo "</div>";

	} else
		echo "<div class='center'><strong>" . $LANG['ocsng'][12] . "</strong></div>";
}

function mergeOcsArray($glpi_id, $tomerge, $field) {
	global $DB;
	$query = "SELECT `$field`
		FROM glpi_ocs_link
		WHERE glpi_id='$glpi_id'";
	if ($result = $DB->query($query)){
		if ($DB->numrows($result)){
			$tab = importArrayFromDB($DB->result($result, 0, 0));
			$newtab = array_merge($tomerge, $tab);
			$newtab = array_unique($newtab);
			$query = "UPDATE glpi_ocs_link
				SET `$field`='" . exportArrayToDB($newtab) . "'
				WHERE glpi_id='$glpi_id'";
			$DB->query($query);
		}
	}

}

function deleteInOcsArray($glpi_id, $todel, $field,$is_value_to_del=false) {
	global $DB;
	$query = "SELECT `$field` FROM glpi_ocs_link WHERE glpi_id='$glpi_id'";
	if ($result = $DB->query($query)) {
		if ($DB->numrows($result)){
			$tab = importArrayFromDB($DB->result($result, 0, 0));
			if ($is_value_to_del){
				$todel=array_search($todel,$tab);
			}
			if (isset($tab[$todel])){
				unset ($tab[$todel]);
				$query = "UPDATE glpi_ocs_link
					SET `$field`='" . exportArrayToDB($tab) . "'
					WHERE glpi_id='$glpi_id'";
				$DB->query($query);
			}
		}
	}
}

function replaceOcsArray($glpi_id, $newArray, $field) {
	global $DB;

	$newArray = exportArrayToDB($newArray);
	$query = "SELECT `$field` FROM glpi_ocs_link WHERE glpi_id='".$glpi_id."'";
	if ($result = $DB->query($query)) {
		if ($DB->numrows($result)){
			$query = "UPDATE glpi_ocs_link
				SET `$field`='" . $newArray . "'
				WHERE glpi_id=".$glpi_id;
			$DB->query($query);
		}
	}
}

function addToOcsArray($glpi_id, $toadd, $field) {
	global $DB;
	$query = "SELECT `$field`
		FROM glpi_ocs_link
		WHERE glpi_id='$glpi_id'";
	if ($result = $DB->query($query)) {
		if ($DB->numrows($result)){
			$tab = importArrayFromDB($DB->result($result, 0, 0));
			foreach ($toadd as $key => $val) {
				$tab[$key] = $val;
			}
			$query = "UPDATE glpi_ocs_link
				SET `$field`='" . exportArrayToDB($tab) . "'
				WHERE glpi_id='$glpi_id'";
			$DB->query($query);
		}
	}

}

function getOcsLockableFields(){
	global $LANG;

	return array (
			"name"=>$LANG['common'][16],
			"type"=>$LANG['common'][17],
			"FK_glpi_enterprise"=>$LANG['common'][5],
			"model"=>$LANG['common'][22],
			"serial"=>$LANG['common'][19],
			"otherserial"=>$LANG['common'][20],
			"comments"=>$LANG['common'][25],
			"contact"=>$LANG['common'][18],
			"contact_num"=>$LANG['common'][21],
			"domain"=>$LANG['setup'][89],
			"network"=>$LANG['setup'][88],
			"os"=>$LANG['computers'][9],
			"os_sp"=>$LANG['computers'][53],
			"os_version"=>$LANG['computers'][52],
			"os_license_number"=>$LANG['computers'][10],
			"os_license_id"=>$LANG['computers'][11],
			"FK_users"=>$LANG['common'][34],
			"location"=>$LANG['common'][15],
			"FK_groups"=>$LANG['common'][35],
		);
}

function ocsUnlockItems($glpi_id,$field){
	global $DB;
	if (!in_array($field,array("import_monitor","import_printers","import_peripheral","import_ip","import_software","import_disk"))){
		return false;
	}
	$query = "SELECT `$field`
		FROM glpi_ocs_link
		WHERE glpi_id='$glpi_id'";
	if ($result = $DB->query($query)) {
		if ($DB->numrows($result)){
			$tab = importArrayFromDB($DB->result($result, 0, 0));
			$update_done=false;

			foreach ($tab as $key => $val) {
				if ($val != "_version_070_") {
					switch ($field){
						case "import_monitor":
						case "import_printers":
						case "import_peripheral":
							$querySearchLocked = "SELECT end1 FROM glpi_connect_wire WHERE ID='$key'";
							break;
						case "import_software":
							$querySearchLocked = "SELECT ID FROM glpi_inst_software WHERE ID='$key'";
							break;
						case "import_ip":
							$querySearchLocked = "SELECT * FROM glpi_networking_ports
								WHERE on_device='$glpi_id' AND device_type='".COMPUTER_TYPE."' AND ifaddr='$val'";
							break;
						case "import_disk":
							$querySearchLocked = "SELECT ID FROM glpi_computerdisks WHERE ID='$key'";
							break;
						default :
							return;
					}
					$resultSearch = $DB->query($querySearchLocked);
					if ($DB->numrows($resultSearch) == 0) {
						unset($tab[$key]);
						$update_done=true;
					}
				}
			}

			if ($update_done){
				$query = "UPDATE glpi_ocs_link
						SET `$field`='" . exportArrayToDB($tab) . "'
						WHERE glpi_id='$glpi_id'";
				$DB->query($query);
			}
		}
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
			echo "<div class='center'>";
			echo "<form method='post' action=\"$target\">";
			echo "<input type='hidden' name='ID' value='$ID'>";
			echo "<table class='tab_cadre'><tr class='tab_bg_2'><td>";
			echo "<input type='hidden' name='resynch_id' value='" . $data["ID"] . "'>";
			echo "<input class=submit type='submit' name='force_ocs_resynch' value='" . $LANG['ocsng'][24] . "'>";
			echo "</td><tr></table>";
			echo "</form>";
			echo "</div>";
		}

		echo "<div width='50%'>";
		// Print lock fields for OCSNG

		$lockable_fields = getOcsLockableFields();
		$locked = importArrayFromDB($data["computer_update"]);
		if (count($locked)>0){
			foreach ($locked as $key => $val){
				if (!isset($lockable_fields[$val])){
					unset($locked[$key]);
				}
			}
		}


		if (count($locked)) {
			echo "<form method='post' action=\"$target\">";
			echo "<input type='hidden' name='ID' value='$ID'>";
			echo "<table class='tab_cadre'>";
			echo "<tr><th colspan='2'>" . $LANG['ocsng'][16] . "</th></tr>";
			foreach ($locked as $key => $val) {
				echo "<tr class='tab_bg_1'><td>" . $lockable_fields[$val] . "</td><td><input type='checkbox' name='lockfield[" . $key . "]'></td></tr>";
			}
			echo "<tr class='tab_bg_2'><td align='center' colspan='2'><input class='submit' type='submit' name='unlock_field' value='" . $LANG['buttons'][38] . "'></td></tr>";
			echo "</table>";
			echo "</form>";
		} else {
			echo "<strong>" . $LANG['ocsng'][15] . "</strong>";
		}
		echo "</div>";

		//Search locked monitors
		$header = false;
		echo "<br>";
		echo "<div width='50%'>";
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
						echo "<tr><th colspan='2'>" . $LANG['ocsng'][30] . "</th></tr>";
					}
					echo "<tr class='tab_bg_1'><td>" . $val . "</td><td><input type='checkbox' name='lockmonitor[" . $key . "]'></td></tr>";
				}
			}
		}
		if ($header) {
			echo "<tr class='tab_bg_2'><td align='center' colspan='2'><input class='submit' type='submit' name='unlock_monitor' value='" . $LANG['buttons'][38] . "'></td></tr>";
			echo "</table>";
			echo "</form>";
		} else
			echo "<strong>" . $LANG['ocsng'][31] . "</strong>";
		echo "</div>";

		//Search locked printers
		$header = false;
		echo "<br>";
		echo "<div class='center'>";
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
					echo "<tr><th colspan='2'>" . $LANG['ocsng'][34] . "</th></tr>";
				}
				echo "<tr class='tab_bg_1'><td>" . $val . "</td><td><input type='checkbox' name='lockprinter[" . $key . "]'></td></tr>";
			}
		}
		if ($header) {
			echo "<tr class='tab_bg_2'><td align='center' colspan='2'><input class='submit' type='submit' name='unlock_printer' value='" . $LANG['buttons'][38] . "'></td></tr>";
			echo "</table>";
			echo "</form>";
		} else
			echo "<strong>" . $LANG['ocsng'][35] . "</strong>";
		echo "</div>";

		// Search locked peripherals
		$header = false;
		echo "<br>";
		echo "<div class='center'>";
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
					echo "<tr><th colspan='2'>" . $LANG['ocsng'][32] . "</th></tr>";
				}
				echo "<tr class='tab_bg_1'><td>" . $val . "</td><td><input type='checkbox' name='lockperiph[" . $key . "]'></td></tr>";
			}
		}
		if ($header) {
			echo "<tr class='tab_bg_2'><td align='center' colspan='2'><input class='submit' type='submit' name='unlock_periph' value='" . $LANG['buttons'][38] . "'></td></tr>";
			echo "</table>";
			echo "</form>";
		} else
			echo "<strong>" . $LANG['ocsng'][33] . "</strong>";
		echo "</div>";

		// Search locked IP
		$header = false;
		echo "<br>";
		echo "<div class='center'>";
		$locked_ip = importArrayFromDB($data["import_ip"]);

		if (!in_array(OCS_IMPORT_TAG_072,$locked_ip))
				$locked_ip=migrateImportIP($ID,$locked_ip);

		foreach ($locked_ip as $key => $val) {
			if ($key>0)
			{
				$tmp = explode(OCS_FIELD_SEPARATOR,$val);
				$querySearchLockedIP = "SELECT *
					FROM glpi_networking_ports
					WHERE on_device='$ID' AND device_type='".COMPUTER_TYPE."'
						AND ifaddr='".$tmp[0]."' AND ifmac='".$tmp[1]."'";
				$resultSearchIP = $DB->query($querySearchLockedIP);
				if ($DB->numrows($resultSearchIP) == 0) {

					if (!($header)) {
						$header = true;
						echo "<form method='post' action=\"$target\">";
						echo "<input type='hidden' name='ID' value='$ID'>";
						echo "<table class='tab_cadre'>";
						echo "<tr><th colspan='2'>" . $LANG['ocsng'][50] . "</th></tr>";
					}
					echo "<tr class='tab_bg_1'><td>" . $val . "</td><td><input type='checkbox' name='lockip[" . $key . "]'></td></tr>";
				}
			}
		}
		if ($header) {
			echo "<tr class='tab_bg_2'><td align='center' colspan='2'><input class='submit' type='submit' name='unlock_ip' value='" . $LANG['buttons'][38] . "'></td></tr>";
			echo "</table>";
			echo "</form>";
		} else
			echo "<strong>" . $LANG['ocsng'][51] . "</strong>";
		echo "</div>";


		// Search locked softwares
		$header = false;
		echo "<br>";
		echo "<div class='center'>";
		$locked_software = importArrayFromDB($data["import_software"]);
		foreach ($locked_software as $key => $val) {
			if ($val != "_version_070_") {
				$querySearchLockedSoft = "SELECT ID FROM glpi_inst_software WHERE ID='$key'";
				$resultSearchSoft = $DB->query($querySearchLockedSoft);
				if ($DB->numrows($resultSearchSoft) == 0) {
					//$header = true;
					if (!($header)) {
						$header = true;
						echo "<form method='post' action=\"$target\">";
						echo "<input type='hidden' name='ID' value='$ID'>";
						echo "<table class='tab_cadre'>";
						echo "<tr><th colspan='2'>" . $LANG['ocsng'][52] . "</th></tr>";
					}

					echo "<tr class='tab_bg_1'><td>" . str_replace('$$$$$',' v. ',$val) . "</td><td><input type='checkbox' name='locksoft[" . $key . "]'></td></tr>";
				}
			}
		}
		if ($header) {
			echo "<tr class='tab_bg_2'><td align='center' colspan='2'><input class='submit' type='submit' name='unlock_soft' value='" . $LANG['buttons'][38] . "'></td></tr>";
			echo "</table>";
			echo "</form>";
		} else
			echo "<strong>" . $LANG['ocsng'][53] . "</strong>";
		echo "</div>";

		// Search locked computerdisks
		$header = false;
		echo "<br>";
		echo "<div class='center'>";
		$locked = importArrayFromDB($data["import_disk"]);
		foreach ($locked as $key => $val) {
			$querySearchLocked = "SELECT ID FROM glpi_computerdisks WHERE ID='$key'";
			$resultSearch = $DB->query($querySearchLocked);
			if ($DB->numrows($resultSearch) == 0) {
				//$header = true;
				if (!($header)) {
					$header = true;
					echo "<form method='post' action=\"$target\">";
					echo "<input type='hidden' name='ID' value='$ID'>";
					echo "<table class='tab_cadre'>";
					echo "<tr><th colspan='2'>" . $LANG['ocsng'][55] . "</th></tr>";
				}

				echo "<tr class='tab_bg_1'><td>$val</td><td><input type='checkbox' name='lockdisk[" . $key . "]'></td></tr>";
			}
		}
		if ($header) {
			echo "<tr class='tab_bg_2'><td align='center' colspan='2'><input class='submit' type='submit' name='unlock_disk' value='" . $LANG['buttons'][38] . "'></td></tr>";
			echo "</table>";
			echo "</form>";
		} else
			echo "<strong>" . $LANG['ocsng'][56] . "</strong>";
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
					// Drop all memories and force no history
					if (!in_array(OCS_IMPORT_TAG_072,$import_device)){
						addToOcsArray($glpi_id,array(0=>OCS_IMPORT_TAG_072),"import_device");
						// Clean memories for this computer
						if (count($import_device)){
							$dohistory=false;
							foreach ($import_device as $import_ID => $val){
								$tmp=explode(OCS_FIELD_SEPARATOR,$val);
								if (isset($tmp[1]) && $tmp[0] == RAM_DEVICE){
									unlink_device_computer($import_ID, false);
									deleteInOcsArray($glpi_id, $import_ID, "import_device");
									unset($import_device[$import_ID]);
								}
							}
						}

					}



					while ($line2 = $DBocs->fetch_array($result2)) {
						$line2 = clean_cross_side_scripting_deep(addslashes_deep($line2));
						if (!empty ($line2["CAPACITY"]) && $line2["CAPACITY"] != "No") {
							$ram["designation"]="";
							if ($line2["TYPE"]!="Empty Slot" && $line2["TYPE"] != "Unknown"){
								$ram["designation"]=$line2["TYPE"];
							}
							if ($line2["DESCRIPTION"]){
								if (!empty($ram["designation"])){
									$ram["designation"].=" - ";
								}
								$ram["designation"] .= $line2["DESCRIPTION"];
							}
							$ram["specif_default"] = $line2["CAPACITY"];
							if (!in_array(RAM_DEVICE . OCS_FIELD_SEPARATOR . $ram["designation"], $import_device)) {
								$ram["frequence"] = $line2["SPEED"];
								$ram["type"] = externalImportDropdown("glpi_dropdown_ram_type", $line2["TYPE"]);
								$ram_id = ocsAddDevice(RAM_DEVICE, $ram);
								if ($ram_id) {
									$devID = compdevice_add($glpi_id, RAM_DEVICE, $ram_id, $line2["CAPACITY"], $dohistory);
									addToOcsArray($glpi_id, array (
										$devID => RAM_DEVICE . OCS_FIELD_SEPARATOR . $ram["designation"]
									), "import_device");
								}
							} else {
								$id = array_search(RAM_DEVICE . OCS_FIELD_SEPARATOR . $ram["designation"], $import_device);
								update_device_specif($line2["CAPACITY"], $id, 1,true);
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
						if (!empty ($line2["DISKSIZE"]) && preg_match("/disk/i", $line2["TYPE"])) {
							if ($line2["NAME"])
								$dd["designation"] = $line2["NAME"];
							else
								if ($line2["MODEL"])
									$dd["designation"] = $line2["MODEL"];
								else
									$dd["designation"] = "Unknown";

							if (!in_array(HDD_DEVICE . OCS_FIELD_SEPARATOR . $dd["designation"], $import_device)) {
								$dd["specif_default"] = $line2["DISKSIZE"];
								$dd_id = ocsAddDevice(HDD_DEVICE, $dd);
								if ($dd_id) {
									$devID = compdevice_add($glpi_id, HDD_DEVICE, $dd_id, $line2["DISKSIZE"], $dohistory);
									addToOcsArray($glpi_id, array (
										$devID => HDD_DEVICE . OCS_FIELD_SEPARATOR . $dd["designation"]
									), "import_device");
								}
							} else {
								$id = array_search(HDD_DEVICE . OCS_FIELD_SEPARATOR . $dd["designation"], $import_device);
								update_device_specif($line2["DISKSIZE"], $id, 1,true);
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
					WHERE HARDWARE_ID = '" . $ocs_id . "'
					ORDER BY ID";
				$result2 = $DBocs->query($query2);
				if ($DBocs->numrows($result2) > 0) {
					while ($line2 = $DBocs->fetch_array($result2)) {
						$line2 = clean_cross_side_scripting_deep(addslashes_deep($line2));
						if (empty ($line2["DISKSIZE"]) || !preg_match("/disk/i", $line2["TYPE"])) {
							if ($line2["NAME"])
								$stor["designation"] = $line2["NAME"];
							else
								if ($line2["MODEL"])
									$stor["designation"] = $line2["MODEL"];
								else
									$stor["designation"] = "Unknown";
							if (!in_array(DRIVE_DEVICE . OCS_FIELD_SEPARATOR . $stor["designation"], $import_device)) {
								$stor["specif_default"] = $line2["DISKSIZE"];
								$stor_id = ocsAddDevice(DRIVE_DEVICE, $stor);
								if ($stor_id) {
									$devID = compdevice_add($glpi_id, DRIVE_DEVICE, $stor_id, "", $dohistory);
									addToOcsArray($glpi_id, array (
										$devID => DRIVE_DEVICE . OCS_FIELD_SEPARATOR . $stor["designation"]
									), "import_device");
								}
							} else {
								$id = array_search(DRIVE_DEVICE . OCS_FIELD_SEPARATOR . $stor["designation"], $import_device);
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
						if (!in_array(PCI_DEVICE . OCS_FIELD_SEPARATOR . $mdm["designation"], $import_device)) {
							if (!empty ($line2["DESCRIPTION"]))
								$mdm["comment"] = $line2["TYPE"] . "\r\n" . $line2["DESCRIPTION"];
							$mdm_id = ocsAddDevice(PCI_DEVICE, $mdm);
							if ($mdm_id) {
								$devID = compdevice_add($glpi_id, PCI_DEVICE, $mdm_id, "", $dohistory);
								addToOcsArray($glpi_id, array (
									$devID => PCI_DEVICE . OCS_FIELD_SEPARATOR . $mdm["designation"]
								), "import_device");
							}
						} else {
							$id = array_search(PCI_DEVICE . OCS_FIELD_SEPARATOR . $mdm["designation"], $import_device);
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
							if (!in_array(PCI_DEVICE . OCS_FIELD_SEPARATOR . $port["designation"], $import_device)) {
								if (!empty ($line2["DESCRIPTION"]) && $line2["DESCRIPTION"] != "None")
									$port["comment"] = $line2["DESCRIPTION"];
								$port_id = ocsAddDevice(PCI_DEVICE, $port);
								if ($port_id) {
									$devID = compdevice_add($glpi_id, PCI_DEVICE, $port_id, "", $dohistory);
									addToOcsArray($glpi_id, array (
										$devID => PCI_DEVICE . OCS_FIELD_SEPARATOR . $port["designation"]
									), "import_device");
								}
							} else {
								$id = array_search(PCI_DEVICE . OCS_FIELD_SEPARATOR . $port["designation"], $import_device);
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
						if (!in_array(PROCESSOR_DEVICE . OCS_FIELD_SEPARATOR . $processor["designation"], $import_device)) {
							$proc_id = ocsAddDevice(PROCESSOR_DEVICE, $processor);
							if ($proc_id) {
								$devID = compdevice_add($glpi_id, PROCESSOR_DEVICE, $proc_id, $line["PROCESSORS"], $dohistory);
								addToOcsArray($glpi_id, array (
									$devID => PROCESSOR_DEVICE . OCS_FIELD_SEPARATOR . $processor["designation"]
								), "import_device");
							}
						} else {
							$id = array_search(PROCESSOR_DEVICE . OCS_FIELD_SEPARATOR . $processor["designation"], $import_device);
							update_device_specif($line["PROCESSORS"], $id, 1,true);
							unset ($import_device[$id]);
						}
					}
				}
			}
			break;
		case NETWORK_DEVICE :
			//Carte reseau
			if ($cfg_ocs["import_device_iface"] || $cfg_ocs["import_ip"]) {
				$do_clean=true;
				//If import_ip doesn't contain _VERSION_072_, then migrate it to the new architecture
				if (!in_array(OCS_IMPORT_TAG_072,$import_ip))
					$import_ip=migrateImportIP($glpi_id,$import_ip);

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
							$network["designation"] = $line2["DESCRIPTION"];
							if (!in_array(NETWORK_DEVICE . OCS_FIELD_SEPARATOR . $network["designation"], $import_device)) {

								if (!empty ($line2["SPEED"]))
									$network["bandwidth"] = $line2["SPEED"];
								$net_id = ocsAddDevice(NETWORK_DEVICE, $network);
								if ($net_id) {
									$devID = compdevice_add($glpi_id, NETWORK_DEVICE, $net_id, $line2["MACADDR"], $dohistory);
									addToOcsArray($glpi_id, array (
										$devID => NETWORK_DEVICE . OCS_FIELD_SEPARATOR . $network["designation"]
									), "import_device");
								}
							} else {
								$id = array_search(NETWORK_DEVICE . OCS_FIELD_SEPARATOR . $network["designation"], $import_device);
								update_device_specif($line2["MACADDR"], $id, 1,true);
								unset ($import_device[$id]);
							}
						}

						if (!empty ($line2["IPADDRESS"]) && $cfg_ocs["import_ip"]) {
							$ocs_ips = explode(",", $line2["IPADDRESS"]);
							$ocs_ips = array_unique($ocs_ips);
							sort($ocs_ips);

							//if never imported in 0.70, insert id in the array
							if ($count_ip == 1) {
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
											$data["ID"] => $data["ifaddr"].OCS_FIELD_SEPARATOR.$line2["MACADDR"]
										), "import_ip");
										$import_ip[$data["ID"]] = $data["ifaddr"].OCS_FIELD_SEPARATOR.$line2["MACADDR"];
									}
								}
							}
							$netport=array();
							$netport["ifmac"] = $line2["MACADDR"];
							$netport["iface"] = externalImportDropdown("glpi_dropdown_iface", $line2["TYPE"]);
							$netport["name"] = $line2["DESCRIPTION"];
							$netport["on_device"] = $glpi_id;
							$netport["device_type"] = COMPUTER_TYPE;
							$netport["netmask"] = $line2["IPMASK"];
							$netport["gateway"] = $line2["IPGATEWAY"];
							$netport["subnet"] = $line2["IPSUBNET"];

							$np = new Netport();

							for ($j = 0; $j < count($ocs_ips); $j++) {

								//First search : look for the same port (same IP and same MAC)
								$id_ip = array_search($ocs_ips[$j].OCS_FIELD_SEPARATOR.$line2["MACADDR"], $import_ip);

								//Second search : IP may have change, so look only for mac address
								if(!$id_ip)
								{
									//Browse the whole import_ip array
									foreach($import_ip as $ID => $ip)
									{
										if ($ID > 0)
										{
											$tmp = explode(OCS_FIELD_SEPARATOR,$ip);

											//Port was found by looking at the mac address
											if (isset($tmp[1]) && $tmp[1] == $line2["MACADDR"])
											{
												//Remove port in import_ip
												deleteInOcsArray($glpi_id,$ID,"import_ip");
												addToOcsArray($glpi_id,array($ID=>$ocs_ips[$j].OCS_FIELD_SEPARATOR.$line2["MACADDR"]),"import_ip");
												$import_ip[$ID]= $ocs_ips[$j].OCS_FIELD_SEPARATOR.$line2["MACADDR"];
												$id_ip = $ID;
												break;
											}
										}
									}
								}

								//Update already in DB
								if ($id_ip>0) {
									$netport["ifaddr"] = $ocs_ips[$j];
									$netport["logical_number"] = $j;
									$netport["ID"] = $id_ip;
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
									$newID = $np->add($netport);
									//ADD to array
									addToOcsArray($glpi_id, array (
										$newID => $ocs_ips[$j].OCS_FIELD_SEPARATOR.$line2["MACADDR"]
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
						if (!in_array(GFX_DEVICE . OCS_FIELD_SEPARATOR . $video["designation"], $import_device)) {
							$video["specif_default"] = "";
							if (!empty ($line2["MEMORY"]))
								$video["specif_default"] = $line2["MEMORY"];
							$video_id = ocsAddDevice(GFX_DEVICE, $video);
							if ($video_id) {
								$devID = compdevice_add($glpi_id, GFX_DEVICE, $video_id, $video["specif_default"], $dohistory);
								addToOcsArray($glpi_id, array (
									$devID => GFX_DEVICE . OCS_FIELD_SEPARATOR . $video["designation"]
								), "import_device");
							}
						} else {
							$id = array_search(GFX_DEVICE . OCS_FIELD_SEPARATOR . $video["designation"], $import_device);
							update_device_specif($line2["MEMORY"], $id, 1,true);
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
						if (!in_array(SND_DEVICE . OCS_FIELD_SEPARATOR . $snd["designation"], $import_device)) {
							if (!empty ($line2["DESCRIPTION"]))
								$snd["comment"] = $line2["DESCRIPTION"];
							$snd_id = ocsAddDevice(SND_DEVICE, $snd);
							if ($snd_id) {
								$devID = compdevice_add($glpi_id, SND_DEVICE, $snd_id, "", $dohistory);
								addToOcsArray($glpi_id, array (
									$devID => SND_DEVICE . OCS_FIELD_SEPARATOR . $snd["designation"]
								), "import_device");
							}
						} else {
							$id = array_search(SND_DEVICE . OCS_FIELD_SEPARATOR . $snd["designation"], $import_device);
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
			if ($key>0)
			{
				// Disconnect wire
				removeConnector($key);

				$query2 = "DELETE FROM glpi_networking_ports WHERE ID = '$key'";
				$DB->query($query2);
				deleteInOcsArray($glpi_id, $key, "import_ip");
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
function ocsAddDevice($device_type, $dev_array) {

	global $DB;
	$table = getDeviceTable($device_type);

	$query = "SELECT *
				FROM `" . $table . "`
				WHERE designation='" . $dev_array["designation"] . "'";

	switch ($table)
	{
		//For network interfaces, check designation AND speed
		case "glpi_device_iface":
			if (isset($dev_array["bandwidth"]))
				$query.=" AND bandwidth='".$dev_array["bandwidth"]."'";
		break;
		default:
		break;
	}

	$result = $DB->query($query);

	if ($DB->numrows($result) == 0) {
		$dev = new Device($device_type);
		$input = array ();
		foreach ($dev_array as $key => $val) {
			$input[$key] = $val;
		}
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

	$count_monitor = count($import_periph);
	switch ($device_type) {
		case MONITOR_TYPE :
			if ($cfg_ocs["import_monitor"]) {

				//Update data in import_monitor array for 0.70
				if (!in_array(OCS_IMPORT_TAG_070, $import_periph)) {
					foreach ($import_periph as $key => $val) {
						$monitor_tag = $val;
						//delete old value
						deleteInOcsArray($glpi_id, $key, "import_monitor");
						//search serial when it exists
						$monitor_serial = "";
						$query_monitor_id = "SELECT end1 FROM glpi_connect_wire WHERE ID='$key'";
						$result_monitor_id = $DB->query($query_monitor_id);
						if ($DB->numrows($result_monitor_id) == 1) {
							//get monitor Id
							$id_monitor = $DB->result($result_monitor_id, 0, "end1");
							$query_monitor_serial = "SELECT serial FROM glpi_monitors WHERE ID = '$id_monitor'";
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
						0 => OCS_IMPORT_TAG_070
					), "import_monitor");

				}

				$do_clean = true;
				$m = new Monitor;

				$query = "SELECT DISTINCT CAPTION, MANUFACTURER, DESCRIPTION, SERIAL, TYPE
					FROM monitors
					WHERE HARDWARE_ID = '" . $ocs_id . "'";
				$result = $DBocs->query($query);
				$lines=array();
				$checkserial=true;

				// First pass - check if all serial present
				if ($DBocs->numrows($result) > 0) {
					while ($line = $DBocs->fetch_array($result)) {
						if (empty($line["SERIAL"])) {
							$checkserial=false;
						}
						$lines[]=clean_cross_side_scripting_deep(addslashes_deep($line));
					}
				}
				/* Second pass - import
					1:Global,
					2:Unique,
					3:Unique on serial : Don't synchronize if serial missing
				*/
				if (count($lines)>0 && ($cfg_ocs["import_monitor"]<=2 || $checkserial)) foreach ($lines as $line) {
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
						$id = array_search($checkMonitor, $import_periph);
						if ($id === false) {
							// Clean monitor object
							$m->reset();

							$mon["FK_glpi_enterprise"] = externalImportDropdown("glpi_dropdown_manufacturer", $line["MANUFACTURER"]);

							if ($cfg_ocs["import_monitor_comments"])
								$mon["comments"] = $line["DESCRIPTION"];
							$id_monitor = 0;

							if ($cfg_ocs["import_monitor"] == 1) {
								//Config says : manage monitors as global
								//check if monitors already exists in GLPI
								$mon["is_global"] = 1;
								$query = "SELECT ID
									FROM glpi_monitors
									WHERE name = '" . $mon["name"] . "'
										AND is_global = '1' AND FK_entities='".$entity."'";
								$result_search = $DB->query($query);
								if ($DB->numrows($result_search) > 0) {
									//Periph is already in GLPI
									//Do not import anything just get periph ID for link
									$id_monitor = $DB->result($result_search, 0, "ID");
								} else {
									$input = $mon;
									if ($cfg_ocs["default_state"]>0){
										$input["state"] = $cfg_ocs["default_state"];
									}
									$input["FK_entities"] = $entity;
									$id_monitor = $m->add($input);
								}
							} else if ($cfg_ocs["import_monitor"] >= 2) {
								//COnfig says : manage monitors as single units
								//Import all monitors as non global.
								$mon["is_global"] = 0;

								// Try to find a monitor with the same serial.
								if (!empty ($mon["serial"])) {
									$query = "SELECT ID
										FROM glpi_monitors
										WHERE serial LIKE '%" . $mon["serial"] . "%' AND is_global=0
											AND FK_entities='".$entity."'";
									$result_search = $DB->query($query);
									if ($DB->numrows($result_search) == 1) {
										//Monitor founded
										$id_monitor = $DB->result($result_search, 0, "ID");
									}
								}
								//Search by serial failed, search by name
								if ($cfg_ocs["import_monitor"]==2 && !$id_monitor) {
									//Try to find a monitor with no serial, the same name and not already connected.
									if (!empty ($mon["name"])) {
										$query = "SELECT glpi_monitors.ID FROM glpi_monitors " .
											"LEFT JOIN glpi_connect_wire ON (glpi_connect_wire.type=".MONITOR_TYPE." AND glpi_connect_wire.end1=glpi_monitors.ID) " .
											"WHERE serial='' AND name = '" . $mon["name"] . "' AND is_global=0 AND FK_entities='$entity' AND glpi_connect_wire.end2 IS NULL";
										$result_search = $DB->query($query);
										if ($DB->numrows($result_search) == 1) {
											$id_monitor = $DB->result($result_search, 0, "ID");
										}
									}
								}
								if (!$id_monitor) {
									$input = $mon;
									if ($cfg_ocs["default_state"]>0){
										$input["state"] = $cfg_ocs["default_state"];
									}
									$input["FK_entities"] = $entity;
									$id_monitor = $m->add($input);
								}
							} // ($cfg_ocs["import_monitor"] >= 2)

							if ($id_monitor) {
								//Import unique : Disconnect monitor on other computer done in Connect function
								$connID = Connect($id_monitor, $glpi_id, MONITOR_TYPE, $dohistory);

								if (!in_array(OCS_IMPORT_TAG_070, $import_periph)) {
									addToOcsArray($glpi_id, array (0 => OCS_IMPORT_TAG_070), "import_monitor");
								}
								addToOcsArray($glpi_id, array ($connID => $checkMonitor), "import_monitor");
								$count_monitor++;

 								//Update column "deleted" set value to 0 and set status to default
								$input = array ();

								$old = new Monitor;
								if ($old->getFromDB($id_monitor)) {
									if ($old->fields["deleted"]) {
										$input["deleted"] = 0;
									}
									if ($cfg_ocs["default_state"]>0 && $old->fields["state"]!=$cfg_ocs["default_state"]) {
										$input["state"] = $cfg_ocs["default_state"];
									}
									if (empty($old->fields["name"]) && !empty($mon["name"])) {
										$input["name"] = $mon["name"];
									}
									if (empty($old->fields["serial"]) && !empty($mon["serial"])) {
										$input["serial"] = $mon["serial"];
									}
									if (count($input)) {
										$input["ID"] = $id_monitor;
										$m->update($input);
									}
								}
							}
						} else { // found in array
							unset ($import_periph[$id]);
						}
					} // empty name
				} // while fetch
				if (in_array(OCS_IMPORT_TAG_070, $import_periph)) {
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
											AND is_global = '1' AND FK_entities='".$entity."'";
									$result_search = $DB->query($query);
									if ($DB->numrows($result_search) > 0) {
										//Periph is already in GLPI
										//Do not import anything just get periph ID for link
										$id_printer = $DB->result($result_search, 0, "ID");
									} else {
										$input = $print;
										if ($cfg_ocs["default_state"]>0){
											$input["state"] = $cfg_ocs["default_state"];
										}
										$input["FK_entities"] = $entity;
										$id_printer = $p->add($input);
									}
								} else
									if ($cfg_ocs["import_printer"] == 2) {
										//COnfig says : manage printers as single units
										//Import all printers as non global.
										$input = $print;
										$input["is_global"] = 0;
										if ($cfg_ocs["default_state"]>0){
											$input["state"] = $cfg_ocs["default_state"];
										}
										$input["FK_entities"] = $entity;
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
									if ($cfg_ocs["default_state"]>0){
										$input["state"] = $cfg_ocs["default_state"];
									}
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
							$periph["type"] = externalImportDropdown("glpi_type_peripherals", $line["TYPE"]);

							$id_periph = 0;

							if ($cfg_ocs["import_periph"] == 1) {
								//Config says : manage peripherals as global
								//check if peripherals already exists in GLPI
								$periph["is_global"] = 1;
								$query = "SELECT ID
									FROM glpi_peripherals
									WHERE name = '" . $periph["name"] . "'
									AND is_global = '1' AND FK_entities='".$entity."'";
								$result_search = $DB->query($query);
								if ($DB->numrows($result_search) > 0) {
									//Periph is already in GLPI
									//Do not import anything just get periph ID for link
									$id_periph = $DB->result($result_search, 0, "ID");
								} else {
									$input = $periph;
									if ($cfg_ocs["default_state"]>0){
										$input["state"] = $cfg_ocs["default_state"];
									}
									$input["FK_entities"] = $entity;
									$id_periph = $p->add($input);
								}
							} else
								if ($cfg_ocs["import_periph"] == 2) {
									//COnfig says : manage peripherals as single units
									//Import all peripherals as non global.
									$input = $periph;
									$input["is_global"] = 0;
									if ($cfg_ocs["default_state"]>0){
										$input["state"] = $cfg_ocs["default_state"];
									}
									$input["FK_entities"] = $entity;
									$id_periph = $p->add($input);
								}
							if ($id_periph) {
								$connID = Connect($id_periph, $glpi_id, PERIPHERAL_TYPE, $dohistory);
								addToOcsArray($glpi_id, array (
									$connID => $periph["name"]
								), "import_peripheral");
								//Update column "deleted" set value to 0 and set status to default
								$input = array ();
								$input["ID"] = $id_periph;
								$input["deleted"] = 0;
								if ($cfg_ocs["default_state"]>0){
									$input["state"] = $cfg_ocs["default_state"];
								}
								$p->update($input);
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

			switch ($device_type) {
				case MONITOR_TYPE :
					// Only if sync done
					if ($cfg_ocs["import_monitor"]<=2 || $checkserial) {
						Disconnect($key, $dohistory, true, $ocs_server_id);
						deleteInOcsArray($glpi_id, $key, "import_monitor");
					}
					break;
				case PRINTER_TYPE :
					Disconnect($key, $dohistory, true, $ocs_server_id);
					deleteInOcsArray($glpi_id, $key, "import_printers");
					break;
				case PERIPHERAL_TYPE :
					Disconnect($key, $dohistory, true, $ocs_server_id);
					deleteInOcsArray($glpi_id, $key, "import_peripheral");
					break;
				default:
					Disconnect($key, $dohistory, true, $ocs_server_id);
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
	$queryListUpdate = "SELECT * FROM glpi_ocs_admin_link WHERE ocs_server_id='$ocs_server_id' ";
	$result = $DB->query($queryListUpdate);
	if ($DB->numrows($result) > 0) {
		$queryOCS = "SELECT * FROM accountinfo WHERE HARDWARE_ID='$ocs_id'";
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
							$var = externalImportDropdown("glpi_dropdown_locations", $var, $entity);
							break;
						case "network" :
							$var = externalImportDropdown("glpi_dropdown_network", $var);
							break;
					}
					$input = array ();
					$input[$glpi_column] = $var;
					$input["ID"] = $glpi_id;
					$comp->update($input, $dohistory);
				}
			}
		}
	}

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
		$query = "SELECT registry.NAME as NAME, registry.REGVALUE as regvalue, registry.HARDWARE_ID as computer_id,
				regconfig.REGTREE as regtree, regconfig.REGKEY as regkey
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
				$isNewReg = $reg->add($input);
				unset($reg->fields);
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
	global $DB, $DBocs, $LANG;

	checkOCSconnection($ocs_server_id);

	if ($cfg_ocs["import_software"]) {

		//------------------------------------------------------------------------------------------------------------------//
		//---- Import_software array is not in the new form ( ID => name+version) -----//
		//----------------------------------------------------------------------------------------------------------------//
		if (!in_array(OCS_IMPORT_TAG_070, $import_software)) {

			//Add the tag of the version at the beginning of the array
			$softs_array[0] = OCS_IMPORT_TAG_070;

			//For each element of the table, add instID=>name.version
			foreach ($import_software as $key => $value){
				$query_softs = "SELECT glpi_softwareversions.name as VERSION
					FROM glpi_inst_software, glpi_softwareversions
					WHERE glpi_inst_software.vID=glpi_softwareversions.ID AND glpi_inst_software.cID='".$glpi_id."'
						AND glpi_inst_software.ID='".$key."'";
				$result_softs = $DB->query($query_softs);
				$softs = $DB->fetch_array($result_softs);
				$softs_array[$key] =  $value . OCS_FIELD_SEPARATOR. $softs["VERSION"];
			}

			//Replace in GLPI database the import_software by the new one
			replaceOcsArray($glpi_id, $softs_array, "import_software");

			//Get import_software from the GLPI db
			//TODO: don't get import_software in DB, but use the newly contructed one
			$query = "SELECT import_software
				FROM glpi_ocs_link
				WHERE glpi_id='".$glpi_id."'";
			$result = $DB->query($query);

			//Reload import_software from DB
			if ($DB->numrows($result))
			{
				$tmp = $DB->fetch_array($result);
				$import_software = importArrayFromDB($tmp["import_software"]);
			}

		}



		//---- Get all the softwares for this machine from OCS -----//
		if ($cfg_ocs["use_soft_dict"]){
			$query2 = "SELECT softwares.NAME AS INITNAME, dico_soft.FORMATTED AS NAME,
				softwares.VERSION AS VERSION, softwares.PUBLISHER AS PUBLISHER, softwares.COMMENTS AS COMMENTS
				FROM softwares
				INNER JOIN dico_soft ON (softwares.NAME = dico_soft.EXTRACTED)
				WHERE softwares.HARDWARE_ID='$ocs_id'";
		} else {
			$query2 = "SELECT softwares.NAME AS INITNAME, softwares.NAME AS NAME,
				softwares.VERSION AS VERSION, softwares.PUBLISHER AS PUBLISHER, softwares.COMMENTS AS COMMENTS
				FROM softwares
				WHERE softwares.HARDWARE_ID='$ocs_id'";
		}

		$result2 = $DBocs->query($query2);
		$to_add_to_ocs_array = array ();
		$soft = new Software;

		if ($DBocs->numrows($result2) > 0)
			while ($data2 = $DBocs->fetch_array($result2)) {
				$data2 = clean_cross_side_scripting_deep(addslashes_deep($data2));
				$initname = $data2["INITNAME"];
				// Hack for OCS encoding problems
				if (!seems_utf8($initname)){
					$initname=utf8_encode($initname);
				}
				$name = $data2["NAME"];
				// Hack for OCS encoding problems
				if (!seems_utf8($name)){
					$name=utf8_encode($name);
				}
				$version = $data2["VERSION"];
				$manufacturer = processManufacturerName($data2["PUBLISHER"]);
				$use_glpi_dictionnary = false;
				if (!$cfg_ocs["use_soft_dict"])
				{
					//Software dictionnary
					$rulecollection = new DictionnarySoftwareCollection;
					$res_rule = $rulecollection->processAllRules(array("name"=>$name,"manufacturer"=>$manufacturer,"old_version"=>$version), array (), array());
               $res_rule = addslashes_deep($res_rule);

					if (isset($res_rule["name"]))
						$modified_name = $res_rule["name"];
					else
						$modified_name = $name;

					if (isset($res_rule["version"]) && $res_rule["version"]!= '')
						$modified_version = $res_rule["version"];
					else
						$modified_version = $version;
				}
				else
				{
					$modified_name = $name;
					$modified_version = $version;
				}

				//Ignore this software
				if (!isset($res_rule["_ignore_ocs_import"]) || !$res_rule["_ignore_ocs_import"])
				{

					// Clean software object
					$soft->reset();

					//If name+version not in present for this computer in glpi, add it
					if (!in_array($initname . OCS_FIELD_SEPARATOR. $version, $import_software))
					{
							//------------------------------------------------------------------------------------------------------------------//
							//---- The software doesn't exists in this version for this computer -----//
							//----------------------------------------------------------------------------------------------------------------//

							$isNewSoft= addSoftwareOrRestoreFromTrash($modified_name,$manufacturer,$entity);
							//Import version for this software
							$versionID = ocsImportVersion($isNewSoft,$modified_version);

							//Install license for this machine
							$instID = installSoftwareVersion($glpi_id, $versionID, $dohistory);

							//Add the software to the table of softwares for this computer to add in database
							$to_add_to_ocs_array[$instID] = $initname . OCS_FIELD_SEPARATOR. $version;

					} else {
						$instID = -1;

						//------------------------------------------------------------------------------------------------------------------//
						//--------------------- The software exists in this version for this computer --------------//
						//----------------------------------------------------------------------------------------------------------------//

						//Get the name of the software in GLPI to know if the software's name have already been changed by the OCS dictionnary
						$instID = array_search($initname . OCS_FIELD_SEPARATOR. $version, $import_software);
						$query_soft = "SELECT glpi_software.ID, glpi_software.name
								FROM glpi_software, glpi_inst_software, glpi_softwareversions
								WHERE glpi_inst_software.ID='".$instID."'
									AND glpi_inst_software.vID=glpi_softwareversions.ID
									AND glpi_softwareversions.sID=glpi_software.ID";

						$result_soft = $DB->query($query_soft);
						$tmpsoft = $DB->fetch_array($result_soft);
						$softName = $tmpsoft["name"];
						$softID = $tmpsoft["ID"];
						$s = new Software;
						$input["ID"]=$softID;

						//First, get the name of the software into GLPI db IF dictionnary is used
						if ($cfg_ocs["use_soft_dict"])
						{
								//First use of the OCS dictionnary OR name changed in the dictionnary
								if ($softName != $name)
								{
									$input["name"]=$name;
									$s->update($input);
								}
						}
						// OCS Dictionnary not use anymore : revert to original name
						else if ($softName != $modified_name)
						{
							$input["name"] = $modified_name;
							$s->update($input);
						}

						unset ($import_software[$instID]);
					}
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
						uninstallSoftwareVersion($key, $dohistory);

						if (countElementsInTable('glpi_inst_software', "vID = '" . $data['vID'] . "'") == 0
							&& countElementsInTable('glpi_softwarelicenses', "buy_version  = '" . $data['vID'] . "'") == 0) {

							$vers = new SoftwareVersion;

							if ($vers->getFromDB($data['vID'])
								&& countElementsInTable('glpi_softwarelicenses', "sID  = '" . $vers->fields['sID'] . "'") == 0
								&& countElementsInTable('glpi_softwareversions', "sID  = '" . $vers->fields['sID'] . "'") == 1) { // 1 is the current to be removed
								putSoftwareInTrash($vers->fields['sID'],$LANG['ocsng'][54]);
							}

							$vers->delete(array (
								"ID" => $data['vID'],
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
 *@param $import_disk array : already imported softwares
 *
 *@return Nothing (void).
 *
 **/
function ocsUpdateDisk($glpi_id, $ocs_id, $ocs_server_id, $cfg_ocs, $import_disk) {
	global $DB, $DBocs, $LANG;
	checkOCSconnection($ocs_server_id);

	$query="SELECT * FROM drives WHERE HARDWARE_ID='$ocs_id'";
	$result=$DBocs->query($query);
	$d=new ComputerDisk();
	if ($DBocs->numrows($result) > 0){
		while ($line = $DBocs->fetch_array($result)) {
			$line = clean_cross_side_scripting_deep(addslashes_deep($line));
			// Only not empty disk
			if ($line['TOTAL']>0){
				$disk=array();
				$disk['FK_computers']=$glpi_id;

				// TYPE : vxfs / ufs  : VOLUMN = mount / FILESYSTEM = device
				if (in_array($line['TYPE'],array("vxfs","ufs")) ){
					$disk['name']=$line['VOLUMN'];
					$disk['mountpoint']=$line['VOLUMN'];
					$disk['device']=$line['FILESYSTEM'];
					$disk['FK_filesystems']=externalImportDropdown('glpi_dropdown_filesystems', $line["TYPE"]);
				} else if (in_array($line['FILESYSTEM'],array('ext4','ext3','ext2','jfs','jfs2','xfs','smbfs','nfs','hfs','ufs','Journaled HFS+','fusefs','fuseblk')) ){
					$disk['name']=$line['VOLUMN'];
					$disk['mountpoint']=$line['VOLUMN'];
					$disk['device']=$line['TYPE'];
					$disk['FK_filesystems']=externalImportDropdown('glpi_dropdown_filesystems', $line["FILESYSTEM"]);
				} else if (in_array($line['FILESYSTEM'],array('FAT32','NTFS','FAT')) ){
					if (!empty($line['VOLUMN'])){
						$disk['name']=$line['VOLUMN'];
					} else {
						$disk['name']=$line['LETTER'];
					}
					$disk['mountpoint']=$line['LETTER'];
					$disk['FK_filesystems']=externalImportDropdown('glpi_dropdown_filesystems', $line["FILESYSTEM"]);
				}

				// Ok import disk
				if (isset($disk['name'])&&!empty ($disk["name"])){
					$disk['totalsize']=$line['TOTAL'];
					$disk['freesize']=$line['FREE'];

					if (!in_array($disk["name"], $import_disk)) {
						$d->reset();
						$id_disk = $d->add($disk);
						if ($id_disk){
							addToOcsArray($glpi_id, array (
										$id_disk => $disk["name"]
									), "import_disk");
						}
					} else {
						// Only update sizes if needed
						$id = array_search($disk["name"], $import_disk);
						if ($d->getFromDB($id)){
							// Update on total size change or variation of 5%
							if ($d->fields['totalsize']!=$disk['totalsize']
							|| (abs($disk['freesize']-$d->fields['freesize'])/$disk['totalsize']) > 0.05){
								$toupdate['ID']=$id;
								$toupdate['totalsize']=$disk['totalsize'];
								$toupdate['freesize']=$disk['freesize'];
								$d->update($toupdate);
							}
							unset ($import_disk[$id]);
						}
					}
				}

			}
		}
	}

	// Delete Unexisting Items not found in OCS
	if (count($import_disk)) {
		foreach ($import_disk as $key => $val) {
			$d->delete(array("ID"=>$key));
			deleteInOcsArray($glpi_id, $key, "import_device");
		}

	}


}

/**
 * Import config of a new version
 *
 * This function create a new software in GLPI with some general datas.
 *
 *@param $software : id of a software.
 *@param $version : version of the software
 *
 *@return integer : inserted version id.
 *
 **/
function ocsImportVersion($software, $version) {
	global $DB, $LANGOcs;

	$isNewVers = 0;

	$query = "SELECT ID
		FROM glpi_softwareversions
		WHERE sID = '" . $software . "'
			AND name='" . $version . "'";

	$result = $DB->query($query);
	if ($DB->numrows($result) > 0) {
		$data = $DB->fetch_array($result);
		$isNewVers = $data["ID"];
	}

	if (!$isNewVers) {
		$vers = new SoftwareVersion;
		// TODO : define a default state ? Need a new option in config
		$input["sID"] = $software;
		$input["name"] = $version;
		$isNewVers = $vers->add($input);
	}
	return ($isNewVers);
}

/**
 * Delete old disks
 *
 * Delete all old disks of a computer.
 *
 *@param $glpi_computer_id integer : glpi computer id.
 *
 *@return nothing.
 *
 **/
function ocsResetDisks($glpi_computer_id) {
	global $DB;

	$query = "DELETE FROM glpi_computerdisks
			WHERE FK_computers = '" . $glpi_computer_id . "'";
	$DB->query($query);
}
/**
 * Delete old softwares
 *
 * Delete all old softwares of a computer.
 *
 *@param $glpi_computer_id integer : glpi computer id.
 *
 *@return nothing.
 *
 **/
function ocsResetSoftwares($glpi_computer_id) {
	global $DB;

	$query = "SELECT *
		FROM glpi_inst_software
		WHERE cID = '" . $glpi_computer_id . "'";
	$result = $DB->query($query);
	if ($DB->numrows($result) > 0) {
		while ($data = $DB->fetch_assoc($result)) {
			$query2 = "SELECT COUNT(*)
				FROM glpi_inst_software
				WHERE vID = '" . $data['vID'] . "'";
			$result2 = $DB->query($query2);
			if ($DB->result($result2, 0, 0) == 1) {
				$vers = new SoftwareVersion;
				$vers->getFromDB($data['vID']);
				$query3 = "SELECT COUNT(*)
					FROM glpi_softwareversions
					WHERE sID='" . $vers->fields['sID'] . "'";
				$result3 = $DB->query($query3);
				if ($DB->result($result3, 0, 0) == 1) {
					$soft = new Software();
					$soft->delete(array (
						'ID' => $vers->fields['sID'],
					), 1);
				}
				$vers->delete(array (
					"ID" => $data['vID'],
				));

			}
		}

		$query = "DELETE FROM glpi_inst_software
				WHERE cID = '" . $glpi_computer_id . "'";
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

			Disconnect($data['ID'],1,false);

			$query2 = "SELECT COUNT(*)
				FROM glpi_connect_wire
				WHERE end1 = '" . $data['end1'] . "'
				AND type = '" . PERIPHERAL_TYPE . "'";
			$result2 = $DB->query($query2);
			if ($DB->result($result2, 0, 0) == 1) {
				$per->delete(array (
					'ID' => $data['end1'],
				), 1);
			}

		}
	}

}
/**
 * Delete old monitors
 *
 * Delete all old monitors of a computer.
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

			Disconnect($data['ID'],1,false);

			$query2 = "SELECT COUNT(*)
				FROM glpi_connect_wire
				WHERE end1 = '" . $data['end1'] . "'
				AND type = '" . MONITOR_TYPE . "'";
			$result2 = $DB->query($query2);
			if ($DB->result($result2, 0, 0) == 1) {
				$mon->delete(array (
					'ID' => $data['end1'],
				), 1);
			}
		}
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

			Disconnect($data['ID'],1,false);

			$query2 = "SELECT COUNT(*)
				FROM glpi_connect_wire
				WHERE end1 = '" . $data['end1'] . "'
				AND type = '" . PRINTER_TYPE . "'";
			$result2 = $DB->query($query2);
			$printer = new Printer();
			if ($DB->result($result2, 0, 0) == 1) {
				$printer->delete(array (
					'ID' => $data['end1'],
				), 1);
			}
		}

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
	$query = "SELECT `$field` AS VAL
		FROM glpi_computers
		WHERE ID = '" . $glpi_computer_id . "'";
	$result = $DB->query($query);
	if ($DB->numrows($result) == 1) {
		$value = $DB->result($result, 0, "VAL");
		$query = "SELECT COUNT(*) AS CPT
			FROM glpi_computers
			WHERE `$field` = '$value'";
		$result = $DB->query($query);
		if ($DB->result($result, 0, "CPT") == 1) {
			$query2 = "DELETE FROM `$table`
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

	$query = "SELECT * FROM glpi_ocs_config ORDER BY name ASC";
	$result = $DB->query($query);
	if ($DB->numrows($result) > 1) {
		echo "<form action=\"$target\" method=\"get\">";
		echo "<div class='center'>";
		echo "<table class='tab_cadre'>";
		echo "<tr class='tab_bg_2'><th colspan='2'>" . $LANG['ocsng'][26] . "</th></tr>";
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][16] . "</td><td class='center'>";
		echo "<select name='ocs_server_id'>";
		while ($ocs = $DB->fetch_array($result))
			echo "<option value=" . $ocs["ID"] . ">" . $ocs["name"] . "</option>";

		echo "</select></td></tr>";
		echo "<tr class='tab_bg_2'><td align='center' colspan=2><input class='submit' type='submit' name='ocs_showservers' value='" . $LANG['buttons'][2] . "'></td></tr>";
		echo "</table></div></form>";

	}
	elseif ($DB->numrows($result) == 1) {
		$ocs = $DB->fetch_array($result);
		glpi_header($_SERVER['PHP_SELF'] . "?ocs_server_id=" . $ocs["ID"]);
	} else{
		echo "<form action=\"$target\" method=\"get\">";
		echo "<div class='center'>";
		echo "<table class='tab_cadre'>";
		echo "<tr class='tab_bg_2'><th colspan='2'>" . $LANG['ocsng'][26] . "</th></tr>";
		echo "<tr class='tab_bg_2'><td align='center' colspan=2>" . $LANG['ocsng'][27] . "</td></tr>";
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

	return $DBocs->connected;
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
					$query = "SELECT ocs_column
						FROM glpi_ocs_admin_link
						WHERE ocs_server_id='" . $ID . "'
							AND glpi_column='" . $glpi_column . "'";
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
/*
function getListState($ocs_server_id) {
	global $DB, $LANG;
	$queryStateSelected = "SELECT deconnection_behavior FROM glpi_ocs_config WHERE ID='$ocs_server_id'";
	$resultSelected = $DB->query($queryStateSelected);
	$selected = 0;
	if ($DB->numrows($resultSelected) > 0) {
		$res = $DB->fetch_array($resultSelected);
		$selected = $res["deconnection_behavior"];
	}

	$values[''] = "-----";
	$values["trash"] = $LANG['ocsconfig'][49];
	$values["delete"] = $LANG['ocsconfig'][50];

	$queryStateList = "SELECT name FROM glpi_dropdown_state";
	$result = $DB->query($queryStateList);
	if ($DB->numrows($result) > 0) {
		while (($data = $DB->fetch_array($result)))
			$values[$data["name"]] = $LANG['ocsconfig'][51] .
			" " . $data["name"];

	}
	dropdownArrayValues("deconnection_behavior", $values, $selected);
}
*/

function getFormServerAction($ID,$templateid)
{
	$action ="";
	if (!isset($withtemplate) || $withtemplate == "")
		$action = "edit_server";
	elseif (isset($withtemplate) && $withtemplate ==1)
	{
		if ($ID == -1 && $templateid == '')
			$action = "add_template";
		else
			$action = "update_template";
	}
	elseif (isset($withtemplate) && $withtemplate ==2)
	{
		if ($templateid== '')
			$action = "edit_server";
		elseif ($ID == -1)
			$action = "add_server_with_template";
		else
			$action = "update_server_with_template";
	}
	return $action;

}
function setEntityLock($entity) {
	global $CFG_GLPI;
	$fp = fopen(GLPI_LOCK_DIR . "/lock_entity_" . $entity, "w+");

	if (flock($fp, LOCK_EX)) {
		return $fp;
	} else {
		fclose($fp);
		return false;
	}
}

function removeEntityLock($entity, $fp) {
	flock($fp, LOCK_UN);
	fclose($fp);

	//Test if the lock file still exists before removing it
	// (sometimes another thread already already removed the file)
	clearstatcache();
	if (file_exists(GLPI_LOCK_DIR . "/lock_entity_" . $entity)) {
		@unlink(GLPI_LOCK_DIR . "/lock_entity_" . $entity);
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

/**
 * Get IP address from OCS hardware table
 * @param ocs_server_id the ID of the OCS server
 * @param computer_id ID of the computer in OCS hardware table
 * @return the ip address or ''
 */
function getOcsGeneralIpAddress($ocs_server_id,$computer_id)
{
	global $DBocs;
	$res = $DBocs->query("SELECT IPADDR FROM hardware WHERE ID='".$computer_id."'");
	if ($DBocs->numrows($res) == 1)
		return $DBocs->result($res,0,"IPADDR");
	else
		return '';
}

function migrateImportIP($glpi_id,$import_ip)
{
	global $DB;

	//Add the new tag as the first occurence in the array
	addToOcsArray($glpi_id,array(0=>OCS_IMPORT_TAG_072),"import_ip");
	$import_ip[0]=OCS_IMPORT_TAG_072;
	//If import_ip is empty : machine comes from pre 0.70 version or new machine to be imported in glpi
	if (count($import_ip) > 1)
	{
		foreach($import_ip as $importip_ID => $value)
		{
			if ($importip_ID > 0)
			{
				//Delete old value in the array (ID => IP)
				deleteInOcsArray($glpi_id,$importip_ID,"import_ip");
				unset($import_ip[$importip_ID]);
				$query="SELECT ifmac, ifaddr FROM glpi_networking_ports WHERE ID='$importip_ID'";
				$result = $DB->query($query);
				$datas = $DB->fetch_array($result);
				$new_ip = (isset($datas["ifaddr"])?$datas["ifaddr"]:"");
				$new_mac = (isset($datas["ifmac"])?$datas["ifmac"]:"");

				//Add new value (ID => IP.$$$$$.MAC)
				addToOcsArray($glpi_id,array($importip_ID=>$new_ip.OCS_FIELD_SEPARATOR.$new_mac),"import_ip");
				$import_ip[$importip_ID]=$new_ip.OCS_FIELD_SEPARATOR.$new_mac;
				}
		}
	}
	return $import_ip;
}

/**
 * Get a direct link to the computer in ocs console
 * @param ocs_server_id the ID of the OCS server
 * @param computer_id ID of the computer in OCS hardware table
 * @param todisplay the link's label to display
 * @return the html link to the computer in ocs console
 */
function getComputerLinkToOcsConsole ($ocs_server_id,$ocs_id,$todisplay)
{
	global $LANG;
	$ocs_config = getOcsConf($ocs_server_id);
	$url = '';
	if ($ocs_config["ocs_url"] != '')
	{
		//Display direct link to the computer in ocsreports
		$url = $ocs_config["ocs_url"];
		if (!preg_match("/\/$/i",$ocs_config["ocs_url"]))
			$url.= '/';
		return "<a href='".$url."machine.php?systemid=".$ocs_id."'>".$todisplay."</a>";
	}
	else
		return $url;
}
?>
