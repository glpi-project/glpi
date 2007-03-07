<?php


/*
 * @version $Id: profile.class.php 4258 2007-01-04 16:16:18Z moyo $
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
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------
if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

/**
 * Rule class store all informations about a GLPI rule :
 *   - description
 *   - criterias
 *   - actions */

class OcsAffectEntityRule extends Rule {

	//Store the id of the ocs server
	var $ocs_server_id;

	function OcsAffectEntityRule($ocs_server_id) {
		$this->table = "glpi_rules_descriptions";
		$this->type = -1;
		$this->ocs_server_id = $ocs_server_id;
	}

	/**
	 * Print a good title for computer pages
	 *
	 *@return nothing (diplays)
	 *
	 **/
	function title() {
		global $LANG, $CFG_GLPI;

		$buttons = array ();
		displayTitle($CFG_GLPI["root_doc"] . "/pics/computer.png", $LANG["Menu"][0], $LANG["rulesengine"][8], $buttons);
	}

	/**
	 * Get the attributes needed for processing the rules
	 * @param type type of the rule
	 * @param extra_params extra parameters given
	 * @return an array of attributes
	 */
	function getRulesMatchingAttributes($type, $extra_params) {
		global $DBocs;

		$computer_id = $extra_params["computer_id"];
		$tables = getTablesForQuery($type);
		$fields = getFieldsForQuery($type);
		$linked_fields = getFKFieldsForQuery($type);

		$rule_parameters = array ();

		$sql = "";
		$begin_sql = "SELECT ";
		$select_sql = "";
		$from_sql = "";
		$where_sql = "";

		//Build the select request
		foreach ($fields as $field) {
			switch ($field) {
				//OCS server ID is provided by extra_params -> get the configuration associated with the ocs server
				case "ocs_server" :
					$rule_parameters["ocs_server"] = $this->ocs_server_id;
					break;
					//TAG dans DOMAIN should come from the OCS DB 
				default :
					$select_sql .= ($select_sql != "" ? " , " : "") . $field;
					break;
			}

		}

		//Build the FROM part of the request
		foreach ($tables as $table) {
			$from_sql .= ($from_sql != "" ? " , " : "") . $table;
		}

		//Build the WHERE part of the request
		foreach ($linked_fields as $linked_field) {
			$where_sql .= ($where_sql != "" ? " AND " : "") . $linked_field . "=hardware.ID ";
		}

		if ($select_sql != "" && $from_sql != "" && $where_sql != "") {
			//Build the all request
			$sql = $begin_sql . $select_sql . " FROM " . $from_sql . " WHERE " . $where_sql . " AND hardware.ID=" . $computer_id;

			checkOCSconnection($this->ocs_server_id);
			$result = $DBocs->query($sql);
			$ocs_datas = array ();

			if ($DBocs->numrows($result) > 0)
				$ocs_datas = $DBocs->fetch_array($result);

			return array_merge($rule_parameters, $ocs_datas);
		} else
			return $rule_parameters;
	}

	/**
	 * Try to find which rule is matched
	 * @return an OcsAffectEntityRule object 
	 */
	function processAllRules($computer_id) {
		global $DB;

		//Get all rules to affect computers to an entity
		$sql = "SELECT ID from glpi_rules_descriptions WHERE rule_type=" . RULE_OCS_AFFECT_COMPUTER . " ORDER by ranking ASC";
		$result = $DB->query($sql);
		while ($rule = $DB->fetch_array($result)) {
			$ocsrule = new OcsAffectEntityRule($this->ocs_server_id);
			$ocsrule->getRuleWithCriteriasAndActions($rule["ID"], 1, 1);

			//We need to provide the current computer id
			$extra_params["computer_id"] = $computer_id;
			$rule_infos = $ocsrule->getRulesMatchingAttributes(RULE_OCS_AFFECT_COMPUTER, $extra_params);

			if ($ocsrule->processRule($rule_infos))
				return $ocsrule;
		}

		return null;
	}

	function showMinimalCriteria($fields,$editable, $canedit) {
		global $LANG,$CFG_GLPI;

		echo "<td class='tab_bg_2'>" . getCriteriaDescriptionByID($fields["criteria"], RULE_OCS_AFFECT_COMPUTER) . "</td>";
		echo "<td class='tab_bg_2'>" . getConditionByID($fields["condition"]) . "</td>";
		echo "<td class='tab_bg_2'>" . getCriteriaPatternValue($fields["pattern"], RULE_OCS_AFFECT_COMPUTER, $fields["criteria"]) . "</td>";

	}
	
function showFormDescriptionHeader()
{
	global $LANG;
	echo "<td class='tab_bg_2'>".$LANG["rulesengine"][10]."</td>";
	echo "<td class='tab_bg_2'>".$this->fields["ranking"]."</td>";
}

function getRuleTypeTitle()
{
	global $LANG;	
	return $LANG["rulesengine"][17]." ".$LANG["rulesengine"][18];
}

function showMinimalAction($fields,$editable,$canedit)
{
	global $LANG,$CFG_GLPI;
	
	if ($fields["value"] != 0)
	{
	$entity = new Entity;
	$entity->getFromDB($fields["value"]);
	}
	
	if ($editable && $canedit)
		echo "<td class='tab_bg_2'><a href=\"".$CFG_GLPI["root_doc"]."/front/ruleaction.form.php?action=edit_action&ID=".$fields["ID"]."\">" . $fields["action_type"] . "</a></td>";
	else
		echo "<td class='tab_bg_2'>".$fields["action_type"]."</td>";
		echo "<td class='tab_bg_2'>".$LANG["log"][63]."</td>";

		if ($fields["value"] != 0)
			echo "<td class='tab_bg_2'>".$entity->fields["name"]."</td>";
		else
			echo "<td class='tab_bg_2'>".$LANG["entity"][2]."</td>";
}

function addCriteriaForm($fields,$editable, $canedit)
{
		global $LANG;
		if ($editable && $canedit){
	
			echo "<div align='center'>";
			echo "<table  class='tab_cadre_fixe'>";
			echo "<tr class='tab_bg_1'><th colspan='5'>".$LANG["rulesengine"][16].$LANG["rulesengine"][18]."</tr><tr><td class='tab_bg_2' align='center'>";
			echo $LANG["rulesengine"][16].":";
			echo "</td><td align='center' class='tab_bg_2'>";
			$this->getComboTag("criteria");
			echo $LANG["rulesengine"][14].":";
			dropdownRulesConditions("condition");
			echo "</td><td align='center' class='tab_bg_2'>";
			echo $LANG["rulesengine"][15].":";
			autocompletionTextField("pattern","glpi_rules_criterias","pattern","",30);
			echo "</td><td align='center' class='tab_bg_2'>";
			echo "<input type=hidden name='FK_rules' value=\"".$fields["ID"]."\">";
			echo "<input type='submit' name='add_criteria' value=\"".$LANG["buttons"][8]."\" class='submit'>";
			echo "</td></tr>";
	
			echo "</table></div><br>";
	
		}
}

function addActionForm($fields,$editable, $canedit)
{
		global $LANG;
		if ($editable && $canedit){
	
			echo "<div align='center'>";
			echo "<table  class='tab_cadre_fixe'>";
			echo "<tr class='tab_bg_1'><th colspan='5'>".$LANG["rulesengine"][16].$LANG["rulesengine"][18]."</tr><tr><td class='tab_bg_2' align='center'>";
			echo $LANG["rulesengine"][11].":";
			echo "</td><td align='center' class='tab_bg_2'>";
			dropdownRulesActions("action_type");
			echo $LANG["rulesengine"][12].":".$LANG["log"][63];
			echo "</td><td align='center' class='tab_bg_2'>";
			echo $LANG["rulesengine"][13].":";
			dropdownValue("glpi_entities","value");
			echo "</td><td align='center' class='tab_bg_2'>";
			echo "<input type=hidden name='field' value=\"FK_entities\">";
			echo "<input type=hidden name='FK_rules' value=\"".$fields["ID"]."\">";
			echo "<input type='submit' name='add_action' value=\"".$LANG["buttons"][8]."\" class='submit'>";
			echo "</td></tr>";
	
			echo "</table></div><br>";
	
		}
}

function getComboTag($name,$value='')
{
	$criterias = getCriteriasByRuleType(RULE_OCS_AFFECT_COMPUTER);
	$elements = array();
	$i=0;
	foreach ($criterias as $criteria)
	{
		$elements[$i]["name"]=$criteria["name"];
		$elements[$i]["value"]=$criteria["value"];
		$i++;
	}
	dropdownArrayValues($name,$elements,$value);
}
}
?>
