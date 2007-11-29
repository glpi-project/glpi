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

// CLASSES Software

class Software extends CommonDBTM {

	function Software() {
		$this->table = "glpi_software";
		$this->type = SOFTWARE_TYPE;
		$this->dohistory = true;
	}

	function defineOnglets($withtemplate) {
		global $LANG, $CFG_GLPI;
		$ong[1] = $LANG["title"][26];
		if (empty ($withtemplate)) {
			$ong[2] = $LANG["software"][19];
		}
		$ong[4] = $LANG["Menu"][26];
		$ong[5] = $LANG["title"][25];

		if (empty ($withtemplate)) {
			$ong[6] = $LANG["title"][28];
			$ong[7] = $LANG["title"][34];
			$ong[10] = $LANG["title"][37];
			if (haveRight("reservation_central", "r"))
				$ong[11] = $LANG["title"][35];

			$ong[12] = $LANG["title"][38];

		}
		return $ong;
	}

	function pre_updateInDB($input, $updates) {
		$this->fields["date_mod"] = $_SESSION["glpi_currenttime"];
		$updates[] = "date_mod";
			
		return array ($input,$updates);
	}

	function prepareInputForUpdate($input) {

		if (isset ($input['is_update']) && ! $input['is_update'])
			$input['update_software'] = -1;

		return $input;
	}

	function prepareInputForAdd($input) {
		// set new date.
		$input["date_mod"] = $_SESSION["glpi_currenttime"];

		if (isset ($input['is_update']) && !$input['is_update'])
			$input['update_software'] = -1;

		if (isset($input["ID"])&&$input["ID"]>0){
			$input["_oldID"]=$input["ID"];
		}
		unset($input['ID']);
		unset($input['withtemplate']);

		//If category was not set by user (when manually adding a user)
		if (!isset($input["category"])||!$input["category"])
		{
			$softcatrule = new SoftwareCategoriesRuleCollection;
			$result = $softcatrule->processAllRules(null,null,$input);
			if (!empty($result) && isset($result["category"])){
				$input["category"]=$result["category"];
			} else {
				$input["category"]=0;
			}
		}
				
		return $input;
	}
	function post_addItem($newID, $input) {
		global $DB;


		// Manage add from template
		if (isset($input["_oldID"])){
			// ADD Infocoms
			$ic = new Infocom();
			if ($ic->getFromDBforDevice(SOFTWARE_TYPE, $input["_oldID"])) {
				$ic->fields["FK_device"] = $newID;
				unset ($ic->fields["ID"]);
				$ic->addToDB();
			}
	
			// ADD Contract				
			$query = "SELECT FK_contract from glpi_contract_device WHERE FK_device='" . $input["_oldID"] . "' AND device_type='" . SOFTWARE_TYPE . "';";
			$result = $DB->query($query);
			if ($DB->numrows($result) > 0) {
	
				while ($data = $DB->fetch_array($result))
					addDeviceContract($data["FK_contract"], SOFTWARE_TYPE, $newID);
			}
	
			// ADD Documents			
			$query = "SELECT FK_doc from glpi_doc_device WHERE FK_device='" . $input["_oldID"] . "' AND device_type='" . SOFTWARE_TYPE . "';";
			$result = $DB->query($query);
			if ($DB->numrows($result) > 0) {
	
				while ($data = $DB->fetch_array($result))
					addDeviceDocument($data["FK_doc"], SOFTWARE_TYPE, $newID);
			}
		}

	}

	function cleanDBonPurge($ID) {

		global $DB, $CFG_GLPI;

		$job = new Job();
		$query = "SELECT * FROM glpi_tracking WHERE (computer = '$ID'  AND device_type='" . SOFTWARE_TYPE . "')";
		$result = $DB->query($query);

		if ($DB->numrows($result))
			while ($data = $DB->fetch_array($result)) {
				if ($CFG_GLPI["keep_tracking_on_delete"] == 1) {
					$query = "UPDATE glpi_tracking SET computer = '0', device_type='0' WHERE ID='" . $data["ID"] . "';";
					$DB->query($query);
				} else
					$job->delete(array (
						"ID" => $data["ID"]
					));
			}

		$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='" . SOFTWARE_TYPE . "')";
		$result = $DB->query($query);

		$query = "DELETE FROM glpi_contract_device WHERE (FK_device = '$ID' AND device_type='" . SOFTWARE_TYPE . "')";
		$result = $DB->query($query);

		$query = "select * from glpi_reservation_item where (device_type='" . SOFTWARE_TYPE . "' and id_device='$ID')";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result) > 0) {
				$rr = new ReservationItem();
				$rr->delete(array (
					"ID" => $DB->result($result,
					0,
					"ID"
				)));
			}
		}

		// Delete all Licenses
		$query2 = "SELECT ID FROM glpi_licenses WHERE (sID = '$ID')";

		if ($result2 = $DB->query($query2)) {
			if ($DB->numrows($result2)) {
				$lic = new License;

				while ($data = $DB->fetch_array($result2)) {
					$lic->delete(array (
						"ID" => $data["ID"]
					));
				}
			}
		}
	}

	function showForm($target, $ID, $search_software = "", $withtemplate = '') {
		// Show Software or blank form

		global $CFG_GLPI, $LANG;

		if (!haveRight("software", "r"))
			return false;

		$sw_spotted = false;
		$use_cache=true;
		if ((empty ($ID) && $withtemplate == 1) || $ID == -1) {
			$use_cache=false;
			if ($this->getEmpty()){
				$sw_spotted = true;
			}
		} else {
			if ($this->getfromDB($ID) && haveAccessToEntity($this->fields["FK_entities"])){
				$sw_spotted = true;
			}
		}

		if ($sw_spotted) {
			$this->showOnglets($ID, $withtemplate, $_SESSION['glpi_onglet']);
			if (!empty ($withtemplate) && $withtemplate == 2) {
				$template = "newcomp";
				$datestring = $LANG["computers"][14] . ": ";
				$date = convDateTime($_SESSION["glpi_currenttime"]);
			}
			elseif (!empty ($withtemplate) && $withtemplate == 1) {
				$template = "newtemplate";
				$datestring = $LANG["computers"][14] . ": ";
				$date = convDateTime($_SESSION["glpi_currenttime"]);
			} else {
				$datestring = $LANG["common"][26] . " : ";
				$date = convDateTime($this->fields["date_mod"]);
				$template = false;
			}

			echo "<div class='center'><form method='post' action=\"$target\">";
			if (strcmp($template, "newtemplate") === 0) {
				echo "<input type=\"hidden\" name=\"is_template\" value=\"1\" />";
			}

			echo "<input type='hidden' name='FK_entities' value='".$this->fields["FK_entities"]."'>";

			echo "<table class='tab_cadre_fixe'>";

			echo "<tr><th align='center' colspan='2' >";
			if (!$template) {
				echo $LANG["common"][2]." ".$this->fields["ID"];
			} elseif (strcmp($template, "newcomp") === 0) {
				echo $LANG["software"][42] . ": " . $this->fields["tplname"];
				echo "<input type='hidden' name='tplname' value='" . $this->fields["tplname"] . "'>";
			} elseif (strcmp($template, "newtemplate") === 0) {
				echo $LANG["common"][6] . "&nbsp;: ";
				autocompletionTextField("tplname", "glpi_software", "tplname", $this->fields["tplname"], 20,$this->fields["FK_entities"]);
			}
			if (isMultiEntitiesMode()){
				echo "&nbsp;(".getDropdownName("glpi_entities",$this->fields["FK_entities"]).")";
			}

			echo "</th><th colspan='2' align='center'>" . $datestring . $date;
			if (!$template && !empty ($this->fields['tplname']))
				echo "&nbsp;&nbsp;&nbsp;(" . $LANG["common"][13] . ": " . $this->fields['tplname'] . ")";
			echo "</th></tr>";

			if (!$use_cache||!($CFG_GLPI["cache"]->start($ID . "_" . $_SESSION["glpilanguage"], "GLPI_" . $this->type))) {
				echo "<tr class='tab_bg_1'><td>" . $LANG["common"][16] . ":		</td>";
				echo "<td>";
				autocompletionTextField("name", "glpi_software", "name", $this->fields["name"], 25,$this->fields["FK_entities"]);
				echo "</td>";
				
				
				echo "<td>" . $LANG["common"][36] . ":		</td><td>";
				dropdownValue("glpi_dropdown_software_category", "category", $this->fields["category"]);
				echo "</td>";

				echo "<tr class='tab_bg_1'><td>" . $LANG["software"][3] . ": 	</td><td>";
				dropdownValue("glpi_dropdown_os", "platform", $this->fields["platform"]);
				echo "</td>";

				echo "<td>" . $LANG["common"][5] . ": 	</td><td>";
				dropdownValue("glpi_dropdown_manufacturer", "FK_glpi_enterprise", $this->fields["FK_glpi_enterprise"]);
				echo "</td></tr>";

				echo "<tr class='tab_bg_1'>";
				echo "<td >" . $LANG["common"][34] . ": 	</td>";
				echo "<td >";
				dropdownAllUsers("FK_users", $this->fields["FK_users"], 1, $this->fields["FK_entities"]);
				echo "</td>";

				echo "<td>" . $LANG["common"][35] . ":</td><td>";
				dropdownValue("glpi_groups", "FK_groups", $this->fields["FK_groups"], 1, $this->fields["FK_entities"]);
				echo "</td></tr>";

				echo "<tr class='tab_bg_1'><td>" . $LANG["common"][10] . ": 	</td><td>";
				dropdownUsersID("tech_num", $this->fields["tech_num"], "interface", 1, $this->fields["FK_entities"]);
				echo "</td>";

				echo "<td>" . $LANG["common"][15] . ": 	</td><td>";
				dropdownValue("glpi_dropdown_locations", "location", $this->fields["location"], 1, $this->fields["FK_entities"]);
				echo "</td></tr>";

				// UPDATE
				echo "<tr class='tab_bg_1'><td>" . $LANG["software"][29] . ":</td><td>";
				dropdownYesNo("is_update",$this->fields['is_update']);
				echo "&nbsp;" . $LANG["pager"][2] . "&nbsp;";
				dropdownValue("glpi_software", "update_software", $this->fields["update_software"]);
				echo "</td>";

				echo "<td>" . $LANG["state"][0] . ":</td><td>";
				dropdownValue("glpi_dropdown_state", "state", $this->fields["state"]);
				echo "</td></tr>";

				echo "<tr class='tab_bg_1'><td>" . $LANG["software"][46] . ":</td><td>";
				dropdownYesNo('helpdesk_visible',$this->fields['helpdesk_visible']);
				echo "</td>";
				echo "<td colspan='2'></td></tr>";

				echo "<tr class='tab_bg_1'><td valign='top'>";
				echo $LANG["common"][25] . ":	</td>";
				echo "<td align='center' colspan='3'><textarea cols='50' rows='4' name='comments' >" . $this->fields["comments"] . "</textarea>";
				echo "</td></tr>";
				if ($use_cache){
					$CFG_GLPI["cache"]->end();
				}
			}

			if (haveRight("software", "w")) {
				echo "<tr>";

				if ($template) {

					if (empty ($ID) || $withtemplate == 2) {
						echo "<td class='tab_bg_2' align='center' colspan='4'>\n";
						echo "<input type='hidden' name='ID' value=$ID>";
						echo "<input type='submit' name='add' value=\"" . $LANG["buttons"][8] . "\" class='submit'>";
						echo "</td>\n";
					} else {
						echo "<td class='tab_bg_2' align='center' colspan='4'>\n";
						echo "<input type='hidden' name='ID' value=$ID>";
						echo "<input type='submit' name='update' value=\"" . $LANG["buttons"][7] . "\" class='submit'>";
						echo "</td>\n";
					}
				} else {

					echo "<td class='tab_bg_2'>&nbsp;</td>";
					echo "<td class='tab_bg_2' valign='top'>";
					echo "<input type='hidden' name='ID' value=\"$ID\">\n";
					echo "<div class='center'><input type='submit' name='update' value=\"" . $LANG["buttons"][7] . "\" class='submit'></div>";
					echo "</td>";
					echo "<td class='tab_bg_2' valign='top' colspan='2'>\n";
					echo "<div class='center'>";
					if (!$this->fields["deleted"])
						echo "<input type='submit' name='delete' value=\"" . $LANG["buttons"][6] . "\" class='submit'>";
					else {
						echo "<input type='submit' name='restore' value=\"" . $LANG["buttons"][21] . "\" class='submit'>";
						echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"" . $LANG["buttons"][22] . "\" class='submit'>";
					}
					echo "</div>";
					echo "</td>";

				}
				echo "</tr>";
			}
			echo "</table></form></div>";

			return true;
		} else {
			echo "<div class='center'><strong>".$LANG["common"][54]."</strong></div>";
			return false;
		}

	}

	// SPECIFIC FUNCTIONS
	function countInstallations() {
		global $DB;
		$query = "SELECT * FROM glpi_inst_software WHERE (sID = " . $this->fields["ID"] . ")";
		if ($result = $DB->query($query)) {
			$number = $DB->numrows($result);
			return $number;
		} else {
			return false;
		}
	}
}

class License extends CommonDBTM {

	function License() {
		$this->table = "glpi_licenses";
	}

	function prepareInputForUpdate($input) {
		if (empty ($input['expire']))
			unset ($input['expire']);
		if (!isset ($input['expire']) || $input['expire'] == "0000-00-00"){
			$input['expire'] = "NULL";
		}
		if (isset ($input['oem']) && !$input['oem'])
			$input['oem_computer'] = -1;
		
		return $input;
	}

	function prepareInputForAdd($input) {
		if (empty ($input['expire']) || $input['expire'] == "0000-00-00" || $input['expire'] == "NULL")
			unset ($input['expire']);
		if (isset($input['oem']) && !$input['oem']){
			$input['oem_computer'] = -1;
		}
		if (!isset($input['oem_computer']) || $input['oem_computer'] == 0){
			$input['oem_computer'] = -1;
		}
		if (!isset($input['version'])){
			$input['version'] = '';
		}
		unset ($input["form"]);
		unset ($input["withtemplate"]);
		unset ($input["lID"]);
		return $input;
	}

	function post_addItem($newID, $input) {
		// Add license but not for unglobalize system
		if (!isset ($input["_duplicate_license"]) && isset($input['oem']) && isset($input['oem_computer']) && $input['oem'] && $input['oem_computer'] > 0){
			installSoftware($input['oem_computer'], $newID);
		}

		$type = SOFTWARE_TYPE;
		$dupid = $this->fields["sID"];
		if (isset ($input["_duplicate_license"])) {
			$type = LICENSE_TYPE;
			$dupid = $input["_duplicate_license"];
		}

		// Add infocoms if exists for the licence
		$ic = new Infocom();
		if ($ic->getFromDBforDevice($type, $dupid)) {
			unset ($ic->fields["ID"]);
			$ic->fields["FK_device"] = $newID;
			$ic->fields["device_type"] = LICENSE_TYPE;
			$ic->addToDB();
		}
	}

	function cleanDBonPurge($ID) {

		global $DB;

		$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='" . LICENSE_TYPE . "')";
		$result = $DB->query($query);

		// Delete Installations
		$query2 = "DELETE FROM glpi_inst_software WHERE (license = '$ID')";
		$DB->query($query2);
	}

}
?>
