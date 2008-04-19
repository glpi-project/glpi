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

// ----------------------------------------------------------------------
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------
if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

class DictionnarySoftwareCollection extends RuleCachedCollection {

	/**
	 * Constructor
	**/
	function DictionnarySoftwareCollection() {
		
		$this->rule_type = RULE_DICTIONNARY_SOFTWARE;
		$this->rule_class_name = 'DictionnarySoftwareRule';
		$this->stop_on_first_match = true;
		$this->right = "rule_dictionnary_software";

		//Init cache system values
		$this->initCache("glpi_rule_cache_software",  
		array ("name" => "old_value","manufacturer" => "manufacturer"),
		array ("name" => "new_value","version" => "version","manufacturer" => "new_manufacturer"));
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][35];
	}

	function warningBeforeReplayRulesOnExistingDB($target){
		global $LANG,$CFG_GLPI;
		echo "<form name='testrule_form' id='softdictionnary_confirmation' method='post' action=\"".$target."\">\n";
		echo "<div class='center'>"; 
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th colspan='2'><strong>" .$LANG["rulesengine"][92]. "</strong></th</tr>";
		echo "<tr><td align='center' class='tab_bg_2'>"; 
		echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/warning.png\"></td>";
		echo "<td align='center' class='tab_bg_2'>".$LANG["rulesengine"][93]. "</td></tr>\n";
		echo "<tr><th colspan='2'><strong>" .$LANG["rulesengine"][95]. "</strong></th</tr>";
		echo "<tr><td align='center' class='tab_bg_2'>".$LANG["rulesengine"][96]."</td>"; 
		echo "<td align='center' class='tab_bg_2'>"; 
		dropdownValue("glpi_dropdown_manufacturer","manufacturer");
		echo"</td></tr>\n";
	
		echo "<tr><td align='center' class='tab_bg_2' colspan='2'><input type='submit' name='replay_rule' value=\"" . $LANG["buttons"][2] . "\" class='submit'><input type='hidden' name='replay_confirm' value='replay_confirm'</td></tr>";
		echo "</table>";
		echo "</div></form>";
		return true;
	}

	function replayRulesOnExistingDB($offset=0,$maxtime=0, $items = array (),$params=0) {
		global $DB;
		if (isCommandLine()){
			echo "replayRulesOnExistingDB started : " . date("r") . "\n";
		}

		
		if (count($items) == 0) {
			//Select all the differents software
			$sql = "SELECT DISTINCT glpi_software.name, glpi_dropdown_manufacturer.name AS manufacturer," .
			" glpi_software.FK_glpi_enterprise as FK_glpi_enterprise " .
			"FROM glpi_software LEFT JOIN glpi_dropdown_manufacturer " .
			"ON glpi_dropdown_manufacturer.ID=glpi_software.FK_glpi_enterprise ";
			
			if (isset($params['manufacturer'])&&$params['manufacturer'] > 0) {
				$sql.=" WHERE FK_glpi_enterprise=".$params['manufacturer'];
			}
			if ($offset) {
				$sql .= " LIMIT $offset,999999999";
			} 
				
			$res = $DB->query($sql);
			$nb = $DB->numrows($res)+$offset;
			$i  = $offset;
			$step=($nb>1000 ? 50 : ($nb>20 ? floor($DB->numrows($res)/20) : 1));

			while ($input = $DB->fetch_array($res)) {
				if (!($i % $step)) {
					if (isCommandLine()) {
						echo date("H:i:s") . " replayRulesOnExistingDB : $i/$nb (".round(memory_get_usage()/(1024*1024),2)." Mo)\n";
					} else {
						changeProgressBarPosition($i,$nb,"$i / $nb");
					}
				}
				
				//If manufacturer is set, then first run the manufacturer's dictionnary
				if (isset($input["manufacturer"]))
					$input["manufacturer"] = processManufacturerName($input["manufacturer"]);
				
				//Replay software dictionnary rules
				$input=addslashes_deep($input);
				$res_rule = $this->processAllRules($input, array (), array ());
				$res_rule = addslashes_deep($res_rule);
				
				//If the software's name or version has changed
				if ((isset ($res_rule["name"]) && $res_rule["name"] != $input["name"]) || (isset ($res_rule["version"])) && $res_rule["version"] != '')
				{
					$IDs = array();
					//Find all the softwares in the database with the same name and manufacturer
					$sql = "SELECT ID FROM `glpi_software` WHERE name='" . $input["name"] . "' AND FK_glpi_enterprise=" . $input["FK_glpi_enterprise"];
					$res_soft = $DB->query($sql);
					if ($DB->numrows($res_soft) > 0)
					{
						//Store all the software's IDs in an array
						while ($result = $DB->fetch_array($res_soft))
							$IDs[] = $result["ID"];
							
						//Replay dictionnary on all the softwares
						$this->replayDictionnaryOnSoftwaresByID($IDs, $res_rule);
					}
				}
				
				$i++;
				if ($maxtime) {
					$crt=explode(" ",microtime());
					if ($crt[0]+$crt[1] > $maxtime) {
						break;
					}
				}
			} // each distinct software

			if (isCommandLine()) {
				echo "replayRulesOnExistingDB : $i/$nb               \n";
			} else {
				changeProgressBarPosition($i,$nb,"$i / $nb");
			}
						
		} else {
			$this->replayDictionnaryOnSoftwaresByID($items);
		}
		if (isCommandLine())
			echo "replayRulesOnExistingDB ended : " . date("r") . "\n";

		return ($i==$nb ? -1 : $i);
	}

	/**
	 * Create a new software
	 */
	function createSoftsInEnty(&$new_softs,$new_name,$manufacturer,$entity)
	{
		$new_softs[$entity][$new_name] = addSoftwareOrRestoreFromTrash($new_name,$manufacturer,$entity,'',IMPORT_TYPE_DICTIONNARY);
		return $new_softs[$entity][$new_name];
	}


	function replayDictionnaryOnSoftwaresByID($IDs, $res_rule=array()) {
		global $DB;
		
		$new_softs = array();
		$delete_ids = array ();

		foreach ($IDs as $ID) {
			$res_soft = $DB->query("SELECT gs.ID AS ID, gs.name AS name, gs.FK_entities AS FK_entities, gm.name AS manufacturer
						FROM glpi_software AS gs LEFT JOIN glpi_dropdown_manufacturer AS gm ON gs.FK_glpi_enterprise = gm.ID 
						WHERE gs.is_template=0 AND gs.ID =" . $ID);
			
			if ($DB->numrows($res_soft))
			{
				$soft = $DB->fetch_array($res_soft);
				
				//For each software
				$this->replayDictionnaryOnOneSoftware($new_softs,$res_rule, $ID,$soft["FK_entities"], 
					(isset($soft["name"])?$soft["name"]:''), 
					(isset($soft["manufacturer"])?$soft["manufacturer"]:''), $delete_ids);
			}
		}

		//Delete software if needed
		$this->putOldSoftsInTrash($delete_ids);
	}

	function replayDictionnaryOnOneSoftware(&$new_softs,$res_rule, $ID,$entity, $name, $manufacturer, & $soft_ids) {
		global $DB;

		$input["name"] = $name;
		$input["manufacturer"] = $manufacturer;
		$input=addslashes_deep($input);

		if (empty($res_rule))
		{
			$res_rule = $this->processAllRules($input, array (), array ());
			$res_rule=addslashes_deep($res_rule);
		}
			
		//Get all the different versions for a software
		$result = $DB->query("SELECT ID, version FROM glpi_licenses WHERE sID=" . $ID);
		while ($license = $DB->fetch_array($result)) {
			$input["version"]=addslashes($license["version"]);
			//Replay software dictionnary rules
			
			//Software's name has changed
			if (isset($res_rule["name"]) && $res_rule["name"] != $name)
			{	
				if (isset($res_rule["FK_glpi_enterprise"]))
					$manufacturer = getDropdownName("glpi_dropdown_manufacturer",$res_rule["FK_glpi_enterprise"]);
				//New software not already present in this entity
				if (!isset($new_softs[$entity][$res_rule["name"]]))
					$new_software_id = $this->createSoftsInEnty($new_softs,$res_rule["name"],$manufacturer,$entity);
				else
					$new_software_id = $new_softs[$entity][$res_rule["name"]];
			}			 
			else
				$new_software_id = $ID;
				
			//if (isCommandLine())
			//	echo "replayDictionnaryOnOneSoftware".$ID."/".$entity."/".$name."/".(isset($res_rule["version"]) && $res_rule["version"] != '')."/".$manufacturer."\n";
			
			$this->moveLicenses($ID, $new_software_id, $license["ID"], $input["version"], ((isset($res_rule["version"]) && $res_rule["version"] != '') ? $res_rule["version"] : $license["version"]), $entity);
		}
		$soft_ids[] = $ID;
	}
	
	/**
	 * Delete a list of softwares
	 */
	function putOldSoftsInTrash($soft_ids) {
		global $DB,$CFG_GLPI,$LANG;

		if (isCommandLine()) {
			//echo "checkUnusedSoftwaresAndDelete ()\n";
		}
		if (count($soft_ids) > 0) {
			
			$first = true;
			$ids = "";
			foreach ($soft_ids as $soft_id) {
				$ids .= (!$first ? "," : "") . $soft_id;
				$first = false;
			}

			//Try to delete all the software that are not used anymore (which means that don't have license associated anymore)
			$res_countsoftinstall = $DB->query("SELECT glpi_software.ID as ID, count( glpi_licenses.sID ) AS cpt " .
						"FROM `glpi_software` LEFT JOIN glpi_licenses ON glpi_licenses.sID = glpi_software.ID " .
						"WHERE glpi_software.ID IN (" . $ids . ") AND deleted=0 GROUP BY glpi_software.ID HAVING cpt=0 ORDER BY cpt");

			$software = new Software;
			while ($soft = $DB->fetch_array($res_countsoftinstall)) {
				putSoftwareInTrash($soft["ID"], $LANG["rulesengine"][87], IMPORT_TYPE_DICTIONNARY);
			}
		}
	}

	/**
	 * Change software's name, and move licenses if needed
	 */
	function moveLicenses($ID,$new_software_id, $license_id, $old_version, $new_version, $entity) {
		global $DB;
		
		$new_licenseID = $this->licenseExists($new_software_id, $license_id,$new_version);
		
		//A license does not exist
		if ($new_licenseID == -1)
		{
			//Transfer licenses from old software to new software for a specific version
			$DB->query("UPDATE glpi_licenses SET version='" . $new_version . "', sID=" . $new_software_id . " WHERE sID=" . $ID." AND version='".$old_version."'");
		}
		else {
			//Change ID of the license in glpi_inst_software
			$DB->query("UPDATE glpi_inst_software SET license=" . $new_licenseID . " WHERE license=" . $ID);
	
			//Delete old license
			$old_license = new License;
			$old_license->delete(array("ID"=>$license_id));
		}
	}

	/**
	 * Check if a license exists
	 */
	function licenseExists($software_id, $license_id, $version) {
		global $DB;

		$license = new License;
		$license->getFromDB($license_id);
		
		//Check if the version exists
		$sql = "SELECT * FROM glpi_licenses WHERE sID=" . $software_id . " AND version='" . $version . "' AND (serial='free' OR serial='global')";

		//Unset unnecessary fields
		unset ($license->fields["ID"]);
		unset ($license->fields["version"]);
		unset ($license->fields["sID"]);

		//Add all license's fields to the request
		foreach ($license->fields as $field => $value)
			$sql .= " AND " . $field . "='" . $value . "'";

		$res_version = $DB->query($sql);
		return (!$DB->numrows($res_version)?-1:$DB->result($res_version, 0, "ID"));
	}

	function insertDataInCache($old_values, $output) {
		global $DB;

		$sql = "INSERT INTO " . $this->cache_table . " (`old_value`,`manufacturer`,`rule_id`,`new_value`,`version`,`new_manufacturer`) " .
		"VALUES (\"" . $old_values["name"] . "\",\"" . $old_values["manufacturer"] . "\"," . $output["_ruleid"] . ", \""
		 . (isset($output["name"])?$output["name"]:$old_values["name"]) . "\", \"" .
		  (isset($output["version"])?$output["version"]:'') . "\", \"" .
		  (isset($output["manufacturer"])?$output["manufacturer"]:'') . "\")";
		$DB->query($sql);
	}

}

/**
* Rule class store all informations about a GLPI rule :
*   - description
*   - criterias
*   - actions
* 
**/
class DictionnarySoftwareRule extends RuleCached {

	/**
	 * Constructor
	**/
	function DictionnarySoftwareRule() {
		$this->table = "glpi_rules_descriptions";
		$this->type = -1;
		$this->rule_type = RULE_DICTIONNARY_SOFTWARE;
		$this->right = "rule_dictionnary_software";
		$this->can_sort = true;
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][35];
	}

	function maxActionsCount() {
		return 3;
	}
	
	function showCacheRuleHeader()
	{
		global $LANG;
		echo "<th colspan='5'>".$LANG["rulesengine"][100]." : ".$this->fields["name"]."</th></tr>";
		echo "<tr>";
		echo "<td class='tab_bg_1'>".$LANG["rulesengine"][104]."</td>";
		echo "<td class='tab_bg_1'>".$LANG["common"][5]." ".$LANG["rulesengine"][108]."</td>";
		echo "<td class='tab_bg_1'>".$LANG["rulesengine"][105]."</td>";
		echo "<td class='tab_bg_1'>".$LANG["rulesengine"][78]."</td>";		
		echo "<td class='tab_bg_1'>".$LANG["common"][5]."</td>";
		echo "</tr>";
	}

	function showCacheRuleDetail($fields)
	{
		global $LANG;
		echo "<td class='tab_bg_2'>".$fields["old_value"]."</td>";
		echo "<td class='tab_bg_2'>".$fields["manufacturer"]."</td>";
		echo "<td class='tab_bg_2'>".($fields["new_value"]!=''?$fields["new_value"]:$LANG["rulesengine"][106])."</td>";
		echo "<td class='tab_bg_2'>".($fields["version"]!=''?$fields["version"]:$LANG["rulesengine"][106])."</td>";		
		echo "<td class='tab_bg_2'>".((isset($fields["new_manufacturer"]) && $fields["new_manufacturer"]!='')?getDropdownName("glpi_dropdown_manufacturer",$fields["new_manufacturer"]):$LANG["rulesengine"][106])."</td>";
	}	
}

?>
