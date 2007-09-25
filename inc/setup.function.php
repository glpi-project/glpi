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

// FUNCTIONS Setup


function showDropdownList($target, $tablename,$FK_entities=''){
	global $DB,$CFG_GLPI,$LANG;
	
	if (!haveRight("dropdown", "w")&&!haveRight("entity_dropdown", "w"))
		return false;	
	
	$field="name";
	if (in_array($tablename, $CFG_GLPI["specif_entities_tables"])) {
		$field="completename";
	}
	
	$entity_restrict = -1;
	if (in_array($tablename, $CFG_GLPI["specif_entities_tables"])) {
		$field="completename";
		if (!empty($FK_entities)&&$FK_entities>=0){
			$entity_restrict = $FK_entities;
		} else {	
			$entity_restrict = $_SESSION["glpiactive_entity"];
		}
	} 
	
	echo "<div class='center'>";
	$query="SELECT * FROM $tablename ".($entity_restrict>=0?" WHERE FK_entities='$entity_restrict' ":"")." ORDER BY $field";
	if ($result=$DB->query($query)){
		if ($DB->numrows($result)>0){
			echo "<form method='post' name='massiveaction_form' id='massiveaction_form' action=\"$target\"><table class='tab_cadre_fixe'>";
			
			$sel="";
			if (isset($_GET["select"])&&$_GET["select"]=="all") {
				$sel="checked";
			}			
			$i=0;
			while ($data=$DB->fetch_assoc($result)){
				$class=" class='tab_bg_2' ";
				if ($i%2){
					$class=" class='tab_bg_1' ";
				} 
				echo "<tr $class><td width='10'><input type='checkbox' name='item[".$data["ID"]."]' value='1' $sel></td><td>".$data[$field]."</td></tr>";
				$i++;
			}
			echo "</table>";
			echo "<input type='hidden' name='which' value='$tablename'>";
			echo "<input type='hidden' name='FK_entities' value='$entity_restrict'>";
			
			echo "<div>";
			echo "<table width='80%'>";
			$parameters="which=$tablename&amp;mass_deletion=1&amp;FK_entities=$FK_entities";
			echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td><a onclick= \"if ( markAllRows('massiveaction_form') ) return false;\" href='".$_SERVER['PHP_SELF']."?$parameters&amp;select=all'>".$LANG["buttons"][18]."</a></td>";

			echo "<td>/</td><td ><a onclick=\"if ( unMarkAllRows('massiveaction_form') ) return false;\" href='".$_SERVER['PHP_SELF']."?$parameters&amp;select=none'>".$LANG["buttons"][19]."</a>";
			echo "</td><td class='left' width='80%'>";
			echo "<input type='submit' class='submit' name='mass_delete' value='".$LANG["buttons"][6]."'>";
			echo "&nbsp;<strong>".$LANG["setup"][1]."</strong>";
			echo "</td></table></div>";
			echo "</form>";
		} else {
			echo "<strong>".$LANG["search"][15]."</strong>";
		}
	
	}
	echo "</div>";
	
}

function showFormTreeDown($target, $tablename, $human, $ID, $value2 = '', $where = '', $tomove = '', $type = '',$FK_entities='') {

	global $CFG_GLPI, $LANG;

	if (!haveRight("dropdown", "w")&&!haveRight("entity_dropdown", "w"))
		return false;


	$entity_restrict = -1;
	$numberof = 0;
	if (in_array($tablename, $CFG_GLPI["specif_entities_tables"])) {
		if (!empty($FK_entities)&&$FK_entities>=0){
			$entity_restrict = $FK_entities;
		} else {	
			$entity_restrict = $_SESSION["glpiactive_entity"];
		}

		$numberof = countElementsInTableForEntity($tablename, $entity_restrict);
	} else {
		$numberof = countElementsInTable($tablename);
	}


	echo "<div class='center'>&nbsp;\n";
	
	if (ereg('setup.dropdowns.php',$target)){
		echo "<a href='$target?which=$tablename&amp;mass_deletion=1&amp;FK_entities=$FK_entities'>".$LANG["title"][42]."</a>";
	}
	
	echo "<form method='post' action=\"$target\">";



	echo "<table class='tab_cadre_fixe'  cellpadding='1'>\n";
	echo "<tr><th colspan='3'>$human:</th></tr>";
	if ($numberof > 0) {
		echo "<tr><td  align='center' valign='middle' class='tab_bg_1'>";
		echo "<input type='hidden' name='which' value='$tablename'>";
		echo "<input type='hidden' name='FK_entities' value='$entity_restrict'>";

		$value = getTreeLeafValueName($tablename, $ID, 1);

		dropdownValue($tablename, "ID", $ID, 0, $entity_restrict);
		// on ajoute un input text pour entrer la valeur modifier
		echo "&nbsp;&nbsp<input type='image' class='calendrier' src=\"" . $CFG_GLPI["root_doc"] . "/pics/puce.gif\" alt='' title='' name='fillright' value='fillright'>&nbsp";

		echo "<input type='text' maxlength='100' size='20' name='value' value=\"" . $value["name"] . "\"><br>";
		echo "<textarea rows='2' cols='50' name='comments' title='" . $LANG["common"][25] . "' >" . $value["comments"] . "</textarea>";

		echo "</td><td align='center' class='tab_bg_2' width='99'>";
		echo "<input type='hidden' name='tablename' value='$tablename'>";
		//  on ajoute un bouton modifier
		echo "<input type='submit' name='update' value='" . $LANG["buttons"][14] . "' class='submit'>";
		echo "</td><td align='center' class='tab_bg_2' width='99'>";
		//
		echo "<input type='submit' name='delete' value=\"" . $LANG["buttons"][6] . "\" class='submit'>";
		echo "</td></tr></table></form>";

		echo "<form method='post' action=\"$target\">";

		echo "<input type='hidden' name='which' value='$tablename'>";
		echo "<table class='tab_cadre_fixe' cellpadding='1'>\n";

		echo "<tr><td align='center' class='tab_bg_1'>";

		dropdownValue($tablename, "value_to_move", $tomove, 0, $entity_restrict);
		echo "&nbsp;&nbsp;&nbsp;" . $LANG["setup"][75] . " :&nbsp;&nbsp;&nbsp;";

		dropdownValue($tablename, "value_where", $where, 0, $entity_restrict);
		echo "</td><td align='center' colspan='2' class='tab_bg_2' width='202'>";
		echo "<input type='hidden' name='tablename' value='$tablename' >";
		echo "<input type='submit' name='move' value=\"" . $LANG["buttons"][20] . "\" class='submit'>";
		echo "<input type='hidden' name='FK_entities' value='$entity_restrict'>";

		echo "</td></tr>";

	}
	echo "</table></form>";

	echo "<form action=\"$target\" method='post'>";
	echo "<input type='hidden' name='FK_entities' value='$entity_restrict'>";
	echo "<input type='hidden' name='which' value='$tablename'>";

	echo "<table class='tab_cadre_fixe' cellpadding='1'>\n";
	echo "<tr><td  align='center'  class='tab_bg_1'>";
	echo "<input type='text' maxlength='100' size='15' name='value'>&nbsp;&nbsp;&nbsp;";

	if ($numberof > 0) {
		echo "<select name='type'>";
		echo "<option value='under' " . ($type == 'under' ? " selected " : "") . ">" . $LANG["setup"][75] . "</option>";
		echo "<option value='same' " . ($type == 'same' ? " selected " : "") . ">" . $LANG["setup"][76] . "</option>";
		echo "</select>&nbsp;&nbsp;&nbsp;";
		;
		dropdownValue($tablename, "value2", $value2, 0, $entity_restrict);
	} else
		echo "<input type='hidden' name='type' value='first'>";

	echo "<br><textarea rows='2' cols='50' name='comments' title='" . $LANG["common"][25] . "' ></textarea>";

	echo "</td><td align='center' colspan='2' class='tab_bg_2'  width='202'>";
	echo "<input type='hidden' name='tablename' value='$tablename' >";

	echo "<input type='submit' name='add' value=\"" . $LANG["buttons"][8] . "\" class='submit'>";
	echo "</td></tr>";

	echo "</table></form></div>";
}

function showFormDropDown($target, $tablename, $human, $ID, $value2 = '',$FK_entities='') {

	global $DB, $CFG_GLPI, $LANG;

	if (!haveRight("dropdown", "w")&&!haveRight("entity_dropdown", "w"))
		return false;

	$entity_restrict = -1;
	$numberof=0;
	if (in_array($tablename, $CFG_GLPI["specif_entities_tables"])) {
		if (!empty($FK_entities)&&$FK_entities>=0){
			$entity_restrict = $FK_entities;
		} else {	
			$entity_restrict = $_SESSION["glpiactive_entity"];
		}
		$numberof = countElementsInTableForEntity($tablename, $entity_restrict);
	} else {
		$numberof = countElementsInTable($tablename);
	}

	

	echo "<div class='center'>&nbsp;";
	echo "<form method='post' action=\"$target\">";
	echo "<table class='tab_cadre_fixe' cellpadding='1'>";
	echo "<tr><th colspan='3'>$human:</th></tr>";
	if ($numberof > 0) {
		echo "<tr><td class='tab_bg_1' align='center' valign='top'>";
		echo "<input type='hidden' name='which' value='$tablename'>";
		echo "<input type='hidden' name='FK_entities' value='$entity_restrict'>";

		if ($tablename != "glpi_dropdown_netpoint") {
			if (!empty ($ID)) {
				$value = getDropdownName($tablename, $ID, 1);
			} else {
				$value = array (
					"name" => "",
					"comments" => ""
				);
			}
			dropdownValue($tablename, "ID", $ID, 0, $entity_restrict);

		} else {
			$value = "";
			$loc = "";
			dropdownNetpoint("ID", $ID, 0, $entity_restrict);
		}

		// on ajoute un input text pour entrer la valeur modifier
		echo "&nbsp;&nbsp;<input type='image' class='calendrier'  src=\"" . $CFG_GLPI["root_doc"] . "/pics/puce.gif\" alt='' title='' name='fillright' value='fillright'>&nbsp;";


		if ($tablename == "glpi_dropdown_netpoint") {
			$query = "select * from glpi_dropdown_netpoint where ID = '" . $ID . "'";
			$result = $DB->query($query);
			$value = $loc = $comments = "";
			$entity = 0;
			if ($DB->numrows($result) == 1) {
				$value = $DB->result($result, 0, "name");
				$loc = $DB->result($result, 0, "location");
				$comments = $DB->result($result, 0, "comments");
			}
			echo "<br>";
			echo $LANG["common"][15] . ": ";

			dropdownValue("glpi_dropdown_locations", "value2", $loc, 0, $entity_restrict);
			echo $LANG["networking"][52] . ": ";
			echo "<input type='text' maxlength='100' size='10' name='value' value=\"" . $value . "\"><br>";
			echo "<textarea rows='2' cols='50' name='comments' title='" . $LANG["common"][25] . "' >" . $comments . "</textarea>";

		} else {

			echo "<input type='text' maxlength='100' size='20' name='value' value=\"" . $value["name"] . "\"><br>";
			echo "<textarea rows='2' cols='50' name='comments' title='" . $LANG["common"][25] . "' >" . $value["comments"] . "</textarea>";
		}
		//
		echo "</td><td align='center' class='tab_bg_2' width='99'>";
		echo "<input type='hidden' name='tablename' value='$tablename'>";

		//  on ajoute un bouton modifier
		echo "<input type='submit' name='update' value='" . $LANG["buttons"][14] . "' class='submit'>";
		echo "</td><td align='center' class='tab_bg_2' width='99'>";
		//
		echo "<input type='submit' name='delete' value=\"" . $LANG["buttons"][6] . "\" class='submit'>";
		echo "</td></tr>";

	}
	echo "</table></form>";
	echo "<form action=\"$target\" method='post'>";
	echo "<input type='hidden' name='which' value='$tablename'>";
	echo "<input type='hidden' name='FK_entities' value='$entity_restrict'>";

	echo "<table class='tab_cadre_fixe' cellpadding='1'>";
	echo "<tr><td align='center'  class='tab_bg_1'>";
	if ($tablename == "glpi_dropdown_netpoint") {
		echo $LANG["common"][15] . ": ";
		dropdownValue("glpi_dropdown_locations", "value2", $value2, 0, $entity_restrict);
		echo $LANG["networking"][52] . ": ";
		echo "<input type='text' maxlength='100' size='10' name='value'><br>";
		echo "<textarea rows='2' cols='50' name='comments' title='" . $LANG["common"][25] . "'></textarea>";
	} else {
		echo "<input type='text' maxlength='100' size='20' name='value'><br>";
		echo "<textarea rows='2' cols='50' name='comments' title='" . $LANG["common"][25] . "'></textarea>";
	}
	echo "</td><td align='center' colspan='2' class='tab_bg_2' width='202'>";
	echo "<input type='hidden' name='tablename' value='$tablename' >";
	echo "<input type='hidden' name='FK_entities' value='$entity_restrict'>";

	echo "<input type='submit' name='add' value=\"" . $LANG["buttons"][8] . "\" class='submit'>";
	echo "</td></tr>";

	// Multiple Add for Netpoint
	if ($tablename == "glpi_dropdown_netpoint") {
		echo "</table></form>";

		echo "<form action=\"$target\" method='post'>";
		echo "<input type='hidden' name='which' value='$tablename'>";
		echo "<table class='tab_cadre_fixe' cellpadding='1'>";
		echo "<tr><td align='center'  class='tab_bg_1'>";

		echo $LANG["common"][15] . ": ";
		dropdownValue("glpi_dropdown_locations", "value2", $value2, 0, $entity_restrict);
		echo $LANG["networking"][52] . ": ";
		echo "<input type='text' maxlength='100' size='5' name='before'>";
		dropdownInteger('from', 0, 0, 400);
		echo "-->";
		dropdownInteger('to', 0, 0, 400);

		echo "<input type='text' maxlength='100' size='5' name='after'><br>";
		echo "<textarea rows='2' cols='50' name='comments' title='" . $LANG["common"][25] . "'></textarea>";
		echo "</td><td align='center' colspan='2' class='tab_bg_2' width='202'>";
		echo "<input type='hidden' name='tablename' value='$tablename' >";
		echo "<input type='hidden' name='FK_entities' value='$entity_restrict'>";

		echo "<input type='submit' name='several_add' value=\"" . $LANG["buttons"][8] . "\" class='submit'>";
		echo "</td></tr>";
	}

	echo "</table></form></div>";
}

function moveTreeUnder($table, $to_move, $where) {
	global $DB;
	if ($where != $to_move) {
		// Is the $where location under the to move ???
		$impossible_move = false;

		$current_ID = $where;
		while ($current_ID != 0 && $impossible_move == false) {

			$query = "SELECT * FROM $table WHERE ID='$current_ID'";
			$result = $DB->query($query);
			$current_ID = $DB->result($result, 0, "parentID");
			if ($current_ID == $to_move){
				$impossible_move = true;
			}
		}
		if (!$impossible_move) {
			// Move Location
			$query = "UPDATE $table SET parentID='$where' where ID='$to_move'";
			$result = $DB->query($query);
			regenerateTreeCompleteNameUnderID($table, $to_move);
		}
	}
}

function updateDropdown($input) {
	global $DB, $CFG_GLPI;

	if ($input["tablename"] == "glpi_dropdown_netpoint") {
		$query = "update " . $input["tablename"] . " SET name = '" . $input["value"] . "', location = '" . $input["value2"] . "', comments='" . $input["comments"] . "' where ID = '" . $input["ID"] . "'";

	} else {
		$query = "update " . $input["tablename"] . " SET name = '" . $input["value"] . "', comments='" . $input["comments"] . "' where ID = '" . $input["ID"] . "'";
	}

	if ($result = $DB->query($query)) {
		if (in_array($input["tablename"], $CFG_GLPI["dropdowntree_tables"])) {
			regenerateTreeCompleteNameUnderID($input["tablename"], $input["ID"]);
			if ($input["tablename"]=="glpi_entities"&&isset($_SESSION["glpiID"])){
				$activeprof=$_SESSION['glpiactiveprofile']['ID'];
				initEntityProfiles($_SESSION["glpiID"]);
				changeProfile($activeprof);
			}
		}
		cleanRelationCache($input["tablename"]);
		return true;
	} else {
		return false;
	}
}

function getDropdownID($input)
{
	global $DB, $CFG_GLPI;
	// Clean datas
	$input["value"]=trim($input["value"]);
	if (!empty ($input["value"])) {
		$add_entity_field_twin = "";
		if (in_array($input["tablename"], $CFG_GLPI["specif_entities_tables"])) {
			$add_entity_field_twin = " FK_entities = '" . $input["FK_entities"] . "' AND ";
		}
		$query="";
		$query_twin="";
		if ($input["tablename"] == "glpi_dropdown_netpoint") {
			$query_twin="SELECT ID FROM " . $input["tablename"] . " WHERE $add_entity_field_twin name= '".$input["value"]."' AND location = '".$input["value2"]."'";
		} else {
			if (in_array($input["tablename"], $CFG_GLPI["dropdowntree_tables"])) {

				$query_twin="SELECT ID FROM " . $input["tablename"] . " WHERE $add_entity_field_twin name= '".$input["value"]."' AND parentID='0'";

				if ($input['type'] != "first" && $input["value2"] != 0) {
					$level_up=-1;
					$query = "SELECT * FROM " . $input["tablename"] . " where ID='" . $input["value2"] . "'";
					
					$result = $DB->query($query);
					
					if ($DB->numrows($result) > 0) {
						
						$data = $DB->fetch_array($result);
						$level_up = $data["parentID"];
						if ($input["type"] == "under") {
							$level_up = $data["ID"];
						}
					} 
					$query_twin="SELECT ID FROM " . $input["tablename"] . " WHERE $add_entity_field_twin name= '".$input["value"]."' AND parentID='$level_up'";
				}
			} else {
				$query_twin="SELECT ID FROM " . $input["tablename"] . " WHERE $add_entity_field_twin name= '".$input["value"]."' ";
			}
		}
		
		// Check twin :
		if ($result_twin = $DB->query($query_twin) ) {
			if ($DB->numrows($result_twin) > 0){
				return $DB->result($result_twin,0,"ID");
			}
		}
		return -1;
	}
}

function addDropdown($input) {
	global $DB, $CFG_GLPI;
	// Clean datas
	$input["value"]=trim($input["value"]);

	// Check twin :
	if ($ID = getDropdownID($input) ) {
		if ($ID>0){
			return $ID;
		}
	}

	if (!empty ($input["value"])) {
		$add_entity_field = "";
		$add_entity_value = "";
		if (in_array($input["tablename"], $CFG_GLPI["specif_entities_tables"])) {
			$add_entity_field = "FK_entities,";
			$add_entity_value = "'" . $input["FK_entities"] . "',";
		}
		$query="";
		if ($input["tablename"] == "glpi_dropdown_netpoint") {
			$query = "INSERT INTO " . $input["tablename"] . " (" . $add_entity_field . "name,location,comments) VALUES (" . $add_entity_value . "'" . $input["value"] . "', '" . $input["value2"] . "', '" . $input["comments"] . "')";
		} else {
			if (in_array($input["tablename"], $CFG_GLPI["dropdowntree_tables"])) {

				$query = "INSERT INTO " . $input["tablename"] . " (" . $add_entity_field . "name,parentID,completename,comments) VALUES (" . $add_entity_value . "'" . $input["value"] . "', '0','','" . $input["comments"] . "')";

				if ($input['type'] != "first" && $input["value2"] != 0) {
					$level_up=-1;
					$query = "SELECT * FROM " . $input["tablename"] . " where ID='" . $input["value2"] . "'";
					
					$result = $DB->query($query);
					
					if ($DB->numrows($result) > 0) {
						
						$data = $DB->fetch_array($result);
						$level_up = $data["parentID"];
						if ($input["type"] == "under") {
							$level_up = $data["ID"];
						}
					} 
					$query = "INSERT INTO " . $input["tablename"] . " (" . $add_entity_field . "name,parentID,completename,comments) VALUES (" . $add_entity_value . "'" . $input["value"] . "', '$level_up','','" . $input["comments"] . "')";
				}
			} else {
				$query = "INSERT INTO " . $input["tablename"] . " (" . $add_entity_field . "name,comments) VALUES (" . $add_entity_value . "'" . $input["value"] . "','" . $input["comments"] . "')";
			}
		}

		if ($result = $DB->query($query)) {
			$ID = $DB->insert_id();
			if (in_array($input["tablename"], $CFG_GLPI["dropdowntree_tables"])) {
				regenerateTreeCompleteNameUnderID($input["tablename"], $ID);
			}
			if ($input["tablename"]=="glpi_entities"&&isset($_SESSION["glpiID"])){
				$activeprof=$_SESSION['glpiactiveprofile']['ID'];
				initEntityProfiles($_SESSION["glpiID"]);
				changeProfile($activeprof);
			}

			cleanRelationCache($input["tablename"]);
			return $ID;
		} else {
			return false;
		}
	}
}

function deleteDropdown($input) {

	global $DB;
	$send = array ();
	$send["tablename"] = $input["tablename"];
	$send["oldID"] = $input["ID"];
	$send["newID"] = 0;
	replaceDropDropDown($send);
	cleanRelationCache($input["tablename"]);
}

//replace all entries for a dropdown in each items
function replaceDropDropDown($input) {
	global $DB,$CFG_GLPI;
	$name = getDropdownNameFromTable($input["tablename"]);
	$RELATION = getDbRelations();
	// Man

	if (isset ($RELATION[$input["tablename"]]))
		foreach ($RELATION[$input["tablename"]] as $table => $field) {

			if (!is_array($field)){

				// Manage OCS lock for items - no need for array case
				if ($table=="glpi_computers"&&$CFG_GLPI['ocs_mode']){
					$query="SELECT ID FROM `glpi_computers` WHERE ocs_import='1' AND `$field` = '" . $input["oldID"] . "'";
					$result=$DB->query($query);
					if ($DB->numrows($result)){
						while ($data=$DB->fetch_array($result)){
							mergeOcsArray($data['ID'],array($field),"computer_update");
						}
					}
				}


				$query = "UPDATE `$table` SET `$field` = '" . $input["newID"] . "'  WHERE `$field` = '" . $input["oldID"] . "'";
				$DB->query($query);
			} else {
				foreach ($field as $f){
					$query = "UPDATE `$table` SET `$f` = '" . $input["newID"] . "'  WHERE `$f` = '" . $input["oldID"] . "'";
					$DB->query($query);
				}
			}
		}

	$query = "DELETE  FROM `".$input["tablename"]."` WHERE `ID` = '" . $input["oldID"] . "'";
	$DB->query($query);
	// Need to be done on entity class
	if ($input["tablename"]=="glpi_entities"){
		$query = "DELETE FROM `glpi_entities_data` WHERE `FK_entities` = '" . $input["oldID"] . "'";
		$DB->query($query);
		if (isset($_SESSION["glpiID"])){
			$activeprof=$_SESSION['glpiactiveprofile']['ID'];
			initEntityProfiles($_SESSION["glpiID"]);
			changeProfile($activeprof);
		}
	}
	cleanRelationCache($input["tablename"]);
}

function showDeleteConfirmForm($target, $table, $ID,$FK_entities) {
	global $DB, $LANG,$CFG_GLPI;

	if (!haveRight("dropdown", "w"))
		return false;

	if (in_array($table,$CFG_GLPI["dropdowntree_tables"])) {

		$query = "SELECT COUNT(*) AS cpt FROM `$table` WHERE `parentID` = '" . $ID . "'";
		$result = $DB->query($query);
		if ($DB->result($result, 0, "cpt") > 0) {
			echo "<div class='center'><p class='red'>" . $LANG["setup"][74] . "</p></div>";
			return;
		}

		if ($table == "glpi_dropdown_kbcategories") {
			$query = "SELECT COUNT(*) AS cpt FROM `glpi_kbitems` WHERE `categoryID` = '" . $ID . "'";
			$result = $DB->query($query);
			if ($DB->result($result, 0, "cpt") > 0) {
				echo "<div class='center'><p class='red'>" . $LANG["setup"][74] . "</p></div>";
				return;
			}
		}
	}



	echo "<div class='center'>";
	echo "<p class='red'>" . $LANG["setup"][63] . "</p>";

	if ($table!="glpi_entities"){
		echo "<p>" . $LANG["setup"][64] . "</p>";
		echo "<form action=\"" . $target . "\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"tablename\" value=\"" . $table . "\"  />";
		echo "<input type=\"hidden\" name=\"ID\" value=\"" . $ID . "\"  />";
		echo "<input type=\"hidden\" name=\"which\" value=\"" . $table . "\"  />";
		echo "<input type=\"hidden\" name=\"forcedelete\" value=\"1\" />";
		echo "<input type=\"hidden\" name=\"FK_entities\" value=\"$FK_entities\" />";
	
		echo "<table class='tab_cadre'><tr><td>";
		echo "<input class='button' type=\"submit\" name=\"delete\" value=\"" . $LANG["buttons"][2] . "\" /></td>";
	
		echo "<td><input class='button' type=\"submit\" name=\"annuler\" value=\"" . $LANG["buttons"][34] . "\" /></td></tr></table>";
		echo "</form>";
	}
	echo "<p>" . $LANG["setup"][65] . "</p>";
	echo "<form action=\" " . $target . "\" method=\"post\">";
	echo "<input type=\"hidden\" name=\"which\" value=\"" . $table . "\"  />";
	echo "<table class='tab_cadre'><tr><td>";
	dropdownNoValue($table, "newID", $ID,$FK_entities);
	echo "<input type=\"hidden\" name=\"tablename\" value=\"" . $table . "\"  />";
	echo "<input type=\"hidden\" name=\"oldID\" value=\"" . $ID . "\"  />";
	echo "<input type=\"hidden\" name=\"FK_entities\" value=\"$FK_entities\" />";
	echo "</td><td><input class='button' type=\"submit\" name=\"replace\" value=\"" . $LANG["buttons"][39] . "\" /></td><td>";
	echo "<input class='button' type=\"submit\" name=\"annuler\" value=\"" . $LANG["buttons"][34] . "\" /></td></tr></table>";
	echo "</form>";

	echo "</div>";
}

function getDropdownNameFromTable($table) {

	if (ereg("glpi_type_", $table)) {
		$name = ereg_replace("glpi_type_", "", $table);
	} else {
		if ($table == "glpi_dropdown_locations")
			$name = "location";
		else {
			$name = ereg_replace("glpi_dropdown_", "", $table);
		}
	}
	return $name;
}

function getDropdownNameFromTableForStats($table) {

	if (ereg("glpi_type_", $table)) {
		$name = "type";
	} else {
		if ($table == "glpi_dropdown_locations")
			$name = "location";
		else {
			$name = ereg_replace("glpi_dropdown_", "", $table);
		}
	}
	return $name;
}

//check if the dropdown $ID is used into item tables
function dropdownUsed($table, $ID) {

	global $DB;
	$name = getDropdownNameFromTable($table);

	$var1 = true;

	$RELATION = getDbRelations();
	if (isset ($RELATION[$table])){

		foreach ($RELATION[$table] as $tablename => $field) {
			$query = "SELECT COUNT(*) AS cpt FROM `$tablename` WHERE `$field` = '" . $ID . "'";
			$result = $DB->query($query);
			if ($DB->result($result, 0, "cpt") > 0)
				$var1 = false;
		}
	}

	return $var1;

}

function listTemplates($type, $target, $add = 0) {

	global $DB, $CFG_GLPI, $LANG;

	//Check is user have minimum right r
	if (!haveTypeRight($type, "r") && !haveTypeRight($type, "w"))
		return false;

	switch ($type) {
		case COMPUTER_TYPE :
			$title = $LANG["Menu"][0];
			$query = "SELECT * FROM glpi_computers WHERE is_template = '1' AND FK_entities='" . $_SESSION["glpiactive_entity"] . "' ORDER by tplname";
			break;
		case NETWORKING_TYPE :
			$title = $LANG["Menu"][1];
			$query = "SELECT * FROM glpi_networking WHERE is_template = '1' AND FK_entities='" . $_SESSION["glpiactive_entity"] . "' ORDER by tplname";
			break;
		case MONITOR_TYPE :
			$title = $LANG["Menu"][3];
			$query = "SELECT * FROM glpi_monitors WHERE is_template = '1' AND FK_entities='" . $_SESSION["glpiactive_entity"] . "' ORDER by tplname";
			break;
		case PRINTER_TYPE :
			$title = $LANG["Menu"][2];
			$query = "SELECT * FROM glpi_printers WHERE is_template = '1' AND FK_entities='" . $_SESSION["glpiactive_entity"] . "' ORDER by tplname";
			break;
		case PERIPHERAL_TYPE :
			$title = $LANG["Menu"][16];
			$query = "SELECT * FROM glpi_peripherals WHERE is_template = '1' AND FK_entities='" . $_SESSION["glpiactive_entity"] . "' ORDER by tplname";
			break;
		case SOFTWARE_TYPE :
			$title = $LANG["Menu"][4];
			$query = "SELECT * FROM glpi_software WHERE is_template = '1' AND FK_entities='" . $_SESSION["glpiactive_entity"] . "' ORDER by tplname";
			break;
		case PHONE_TYPE :
			$title = $LANG["Menu"][34];
			$query = "SELECT * FROM glpi_phones WHERE is_template = '1' AND FK_entities='" . $_SESSION["glpiactive_entity"] . "' ORDER by tplname";
			break;
		case OCSNG_TYPE :
			$title = $LANG["Menu"][33];
			$query = "SELECT * FROM glpi_ocs_config WHERE is_template = '1' ORDER by tplname";
			break;

	}
	if ($result = $DB->query($query)) {

		echo "<div class='center'><table class='tab_cadre' width='50%'>";
		if ($add) {
			echo "<tr><th>" . $LANG["common"][7] . " - $title:</th></tr>";
		} else {
			echo "<tr><th colspan='2'>" . $LANG["common"][14] . " - $title:</th></tr>";
		}

		if ($add) {

			echo "<tr>";
			echo "<td align='center' class='tab_bg_1'>";
			echo "<a href=\"$target?ID=-1&amp;withtemplate=2\">&nbsp;&nbsp;&nbsp;" . $LANG["common"][31] . "&nbsp;&nbsp;&nbsp;</a></td>";
			echo "</tr>";
		}
	
		while ($data = $DB->fetch_array($result)) {

			$templname = $data["tplname"];
			if ($CFG_GLPI["view_ID"]||empty($data["tplname"])){
            			$templname.= "(".$data["ID"].")";
			}
			echo "<tr>";
			echo "<td align='center' class='tab_bg_1'>";
			
			if (haveTypeRight($type, "w") && !$add) {
				echo "<a href=\"$target?ID=" . $data["ID"] . "&amp;withtemplate=1\">&nbsp;&nbsp;&nbsp;$templname&nbsp;&nbsp;&nbsp;</a></td>";

				echo "<td align='center' class='tab_bg_2'>";
				echo "<strong><a href=\"$target?ID=" . $data["ID"] . "&amp;purge=purge&amp;withtemplate=1\">" . $LANG["buttons"][6] . "</a></strong>";
				echo "</td>";
			} else {
				echo "<a href=\"$target?ID=" . $data["ID"] . "&amp;withtemplate=2\">&nbsp;&nbsp;&nbsp;$templname&nbsp;&nbsp;&nbsp;</a></td>";
			}

			echo "</tr>";

		}

		if (haveTypeRight($type, "w") &&!$add) {
			echo "<tr>";
			echo "<td colspan='2' align='center' class='tab_bg_2'>";
			echo "<strong><a href=\"$target?withtemplate=1\">" . $LANG["common"][9] . "</a></strong>";
			echo "</td>";
			echo "</tr>";
		}

		echo "</table></div>";
	}

}

function showFormExtAuthList($target) {

	global $DB, $LANG, $CFG_GLPI;

	if (!haveRight("config", "w"))
		return false;
	echo "<div class='center'>";
	echo "<form name=cas action=\"$target\" method=\"post\">";
	echo "<input type='hidden' name='ID' value='" . $CFG_GLPI["ID"] . "'>";

	echo "<div id='barre_onglets'><ul id='onglet'>";
	echo "<li ";
	if ($_SESSION['glpi_authconfig'] == 1) {
		echo "class='actif'";
	}
	echo "><a href='$target?onglet=1'>" . $LANG["login"][2] . "</a></li>";
	echo "<li ";
	if ($_SESSION['glpi_authconfig'] == 2) {
		echo "class='actif'";
	}
	echo "><a href='$target?onglet=2'>" . $LANG["login"][3] . "</a></li>";

	echo "<li ";
	if ($_SESSION['glpi_authconfig'] == 3) {
		echo "class='actif'";
	}
	echo "><a href='$target?onglet=3'>" . $LANG["login"][4] . "</a></li>";
	echo "</ul></div>";


	switch ($_SESSION['glpi_authconfig']){
		case 2 :
			if (function_exists('imap_open')) {
				echo "<table class='tab_cadre_fixe' cellpadding='5'>";
				echo "<tr><th colspan='2'>";
				echo "<div class='relative'><span><strong>" . $LANG["login"][3] . "</strong></span>";
				echo "<span style='  position:absolute; right:0; margin-right:5px; font-size:10px;'><a href=\"".$target."?next=extauth_mail\"><img src=\"".$CFG_GLPI["root_doc"]."/pics/plus.png\" alt='+' title='".$LANG["buttons"][8]."'></a></span>";

				echo "</div></th></tr>";
				echo "<tr class='tab_bg_1'><td class='center'>" . $LANG["common"][16] . "</td><td class='center'>" . $LANG["common"][52] . "</td></tr>";
				$sql = "SELECT * from glpi_auth_mail";
				$result = $DB->query($sql);
				if ($DB->numrows($result)) {
					
		
					while ($mail_method = $DB->fetch_array($result)){
						echo "<tr class='tab_bg_2'><td class='center'><a href='$target?next=extauth_mail&amp;ID=" . $mail_method["ID"] . "' >" . $mail_method["name"] . "</a>" .
						"</td><td class='center'>" . $mail_method["imap_host"] . "</td></tr>";
					}
				}
				echo "</table>";
			} else {
				echo "<input type=\"hidden\" name=\"IMAP_Test\" value=\"1\" >";
		
				echo "<table class='tab_cadre_fixe'>";
				echo "<tr><th colspan='2'>" . $LANG["setup"][162] . "</th></tr>";
				echo "<tr class='tab_bg_2'><td class='center'><p class='red'>" . $LANG["setup"][165] . "</p><p>" . $LANG["setup"][166] . "</p></td></tr></table>";
			}
		break;
		case 1 :
			if (extension_loaded('ldap')) {
				
				echo "<table class='tab_cadre_fixe' cellpadding='5'>";
				echo "<tr><th colspan='2'>";
				echo "<div class='relative'><span><strong>" . $LANG["login"][2] . "</strong></span>";
				echo "<span style='  position:absolute; right:0; margin-right:5px; font-size:10px;'><a href=\"".$target."?next=extauth_ldap\"><img src=\"".$CFG_GLPI["root_doc"]."/pics/plus.png\" alt='+' title='".$LANG["buttons"][8]."'></a></span>";

				echo "</div></th></tr>";
				echo "<tr class='tab_bg_1'><td class='center'>" . $LANG["common"][16] . "</td><td class='center'>" . $LANG["common"][52] . "</td></tr>";
		
				$sql = "SELECT * from glpi_auth_ldap";
				$result = $DB->query($sql);
				if ($DB->numrows($result)) {
					while ($ldap_method = $DB->fetch_array($result)){
						echo "<tr class='tab_bg_2'><td class='center'><a href='$target?next=extauth_ldap&amp;ID=" . $ldap_method["ID"] . "' >" . $ldap_method["name"] . "</a>" .
						"</td><td class='center'>" . $ldap_method["ldap_host"] . "</td></tr>";
					}
				}
				echo "</table>";
			} else {
				echo "<input type=\"hidden\" name=\"LDAP_Test\" value=\"1\" >";
				echo "<table class='tab_cadre_fixe'>";
				echo "<tr><th colspan='2'>" . $LANG["setup"][152] . "</th></tr>";
				echo "<tr class='tab_bg_2'><td class='center'><p class='red'>" . $LANG["setup"][157] . "</p><p>" . $LANG["setup"][158] . "</p></td></tr></table>";
			}
		break;

		case 3 :
			if (function_exists('curl_init') && (version_compare(PHP_VERSION, '5', '>=') || (function_exists("domxml_open_mem") && function_exists("utf8_decode")))) {		
				echo "<table class='tab_cadre_fixe' cellpadding='5'>";
				echo "<tr><th colspan='2'>" . $LANG["setup"][177] . "</th></tr>";
				echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][174] . "</td><td><input type=\"text\" name=\"cas_host\" value=\"" . $CFG_GLPI["cas_host"] . "\"></td></tr>";
				echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][175] . "</td><td><input type=\"text\" name=\"cas_port\" value=\"" . $CFG_GLPI["cas_port"] . "\"></td></tr>";
				echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][176] . "</td><td><input type=\"text\" name=\"cas_uri\" value=\"" . $CFG_GLPI["cas_uri"] . "\" ></td></tr>";
				echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][182] . "</td><td><input type=\"text\" name=\"cas_logout\" value=\"" . $CFG_GLPI["cas_logout"] . "\" ></td></tr>";
				echo "<tr class='tab_bg_1'><td align='center' colspan='2'><input type=\"submit\" name=\"update_conf_cas\" class=\"submit\" value=\"" . $LANG["buttons"][7] . "\" ></td></tr>";
		
				echo "</table>";
				echo "<p> " . $LANG["setup"][173] . "</p>";
			} else {
				echo "<input type=\"hidden\" name=\"CAS_Test\" value=\"1\" >";
				echo "<div class='center'><table class='tab_cadre_fixe'>";
				echo "<tr><th colspan='2'>" . $LANG["setup"][177] . "</th></tr>";
				echo "<tr class='tab_bg_2'><td class='center'><p class='red'>" . $LANG["setup"][178] . "</p><p>" . $LANG["setup"][179] . "</p></td></tr></table></div>";
			}
		}


	echo "</form>";
	echo "</div>";
}


	function showMailServerConfig($value) {
		global $LANG;

		if (!haveRight("config", "w"))
			return false;

		if (ereg(":", $value)) {
			$addr = ereg_replace("{", "", preg_replace("/:.*/", "", $value));
			$port = preg_replace("/.*:/", "", preg_replace("/\/.*/", "", $value));
		} else {
			if (ereg("/", $value))
				$addr = ereg_replace("{", "", preg_replace("/\/.*/", "", $value));
			else
				$addr = ereg_replace("{", "", preg_replace("/}.*/", "", $value));
			$port = "";
		}
		$mailbox = preg_replace("/.*}/", "", $value);

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["common"][52] . "</td><td><input size='30' type=\"text\" name=\"mail_server\" value=\"" . $addr . "\" ></td></tr>";
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][168] . "</td><td>";
		echo "<select name='server_type'>";
		echo "<option value=''>&nbsp;</option>";
		echo "<option value='/imap' " . (ereg("/imap", $value) ? " selected " : "") . ">IMAP</option>";
		echo "<option value='/pop' " . (ereg("/pop", $value) ? " selected " : "") . ">POP</option>";
		echo "</select>";
		echo "<select name='server_ssl'>";
		echo "<option value=''>&nbsp;</option>";
		echo "<option value='/ssl' " . (ereg("/ssl", $value) ? " selected " : "") . ">SSL</option>";
		echo "</select>";
		echo "<select name='server_cert'>";
		echo "<option value=''>&nbsp;</option>";
		echo "<option value='/novalidate-cert' " . (ereg("/novalidate-cert", $value) ? " selected " : "") . ">NO-VALIDATE-CERT</option>";
		echo "<option value='/validate-cert' " . (ereg("/validate-cert", $value) ? " selected " : "") . ">VALIDATE-CERT</option>";
		echo "</select>";
		echo "<select name='server_tls'>";
		echo "<option value=''>&nbsp;</option>";
		echo "<option value='/tls' " . (ereg("/tls", $value) ? " selected " : "") . ">TLS</option>";
		echo "<option value='/notls' " . (ereg("/notls", $value) ? " selected " : "") . ">NO-TLS</option>";
		echo "</select>";
		echo "<input type=hidden name=imap_string value='".$value."'>";
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][169] . "</td><td><input size='30' type=\"text\" name=\"server_mailbox\" value=\"" . $mailbox . "\" ></td></tr>";
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][171] . "</td><td><input size='10' type=\"text\" name=\"server_port\" value=\"" . $port . "\" ></td></tr>";
		if (empty ($value))
			$value = "&nbsp;";
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["setup"][170] . "</td><td><strong>$value</strong></td></tr>";

	}
	function constructMailServerConfig($input) {

		$out = "";
		if (isset ($input['mail_server']) && !empty ($input['mail_server']))
			$out .= "{" . $input['mail_server'];
		else
			return $out;
		if (isset ($input['server_port']) && !empty ($input['server_port']))
			$out .= ":" . $input['server_port'];
		if (isset ($input['server_type']))
			$out .= $input['server_type'];
		if (isset ($input['server_ssl']))
			$out .= $input['server_ssl'];
		if (isset ($input['server_cert']))
			$out .= $input['server_cert'];
		if (isset ($input['server_tls']))
			$out .= $input['server_tls'];

		$out .= "}";
		if (isset ($input['server_mailbox']))
			$out .= $input['server_mailbox'];

		return $out;

	}

?>
