<?php


/*
 * @version $Id: search.function.php 4409 2007-02-14 19:32:10Z moyo $
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
 
 // Get rules_option array
$RULES_CRITERIAS=getRulesOptions();
 
define ("RULE_WILDCARD","*");
 
/**
 * Try to match a definied rule
 * 
 * @param $field the field to match
 * @param $condition the condition (is, is_not, contain, not_contain,begin,end)
 * @param $pattern the pattern to match
 * @return true if the field match the rule, false if it doesn't match
 */
function matchRules($field, $condition, $pattern) {

	//If pattern is wildcard, don't check the rule and return true
	if ($pattern == RULE_WILDCARD)
		return true;
		
	switch ($condition) {
		case PATTERN_IS :
			if ($field == $pattern)
				return true;
			else
				return false;
		case PATTERN_IS_NOT :
			if ($field == $pattern)
				return false;
			else
				return true;
		case PATTERN_END:
			$value = "/".$pattern."$/";
			if (preg_match($value, $field) > 0)
				return true;
			else
				return false;	
		case PATTERN_BEGIN:
			$value = stripos($field,$pattern);
			if (($value !== false) && $value == 0)
				return true;
			else
				return false;	
			
		case PATTERN_CONTAIN:
			$value = stripos($field,$pattern);
			if (($value !== false) && $value >= 0)
				return true;
			else
				return false;	
			
		case PATTERN_NOT_CONTAIN:
			$value = stripos($field,$pattern);
			if ($value === false)
				return true;
			else
				return false;			
	}
	return false;
}

/**
 * Display form to add rules
 * @param rule_type Type of rule (ocs_affectation, ldap_rights)
 */
function showOcsAffectationRules($target, $ID, $rule_type) {
	global $LANG, $CFG_GLPI;

	$canedit = haveRight("config", "w");

	echo "<form name='entityaffectation_form' id='entityaffectation_form' method='post' action=\"$target\">";

		if ($canedit){
	
			echo "<div align='center'>";
			echo "<table  class='tab_cadre_fixe'>";
			echo "<tr class='tab_bg_1'><th colspan='5'>".$LANG["rulesengine"][21].$LANG["rulesengine"][18]."</tr><tr><td class='tab_bg_2' align='center'>";
			echo $LANG["common"][16].":";
			echo "</td><td align='center' class='tab_bg_2'>";
			autocompletionTextField("name","glpi_rules_descriptions","name","",30);
			echo $LANG["joblist"][6].":";
			autocompletionTextField("description","glpi_rules_descriptions","description","",30);
			echo "</td><td align='center' class='tab_bg_2'>";
			echo $LANG["rulesengine"][9].":";
			dropdownRulesMatch("match","AND");
			echo "</td><td align='center' class='tab_bg_2'>";
			echo "<input type=hidden name='rule_type' value=\"".RULE_OCS_AFFECT_COMPUTER."\">";
			echo "<input type=hidden name='FK_entities' value=\"-1\">";
			echo "<input type=hidden name='affectentity' value=\"".$ID."\">";
			echo "<input type='submit' name='addrule' value=\"".$LANG["buttons"][8]."\" class='submit'>";
			echo "</td></tr>";
	
			echo "</table></div><br>";
	
		}


	echo "<div align='center'><table class='tab_cadrehov'><tr><th colspan='3'>" . $LANG["entity"][5] . "</th></tr>";

	//Get all rules and actions
	$rules = getRulesByID($rule_type, $ID, 0, 1);

	if (!empty ($rules)) {

		foreach ($rules as $rule) {
			echo "<tr class='tab_bg_1'>";

			if ($canedit) {
				echo "<td width='10'>";
				$sel = "";
				if (isset ($_GET["select"]) && $_GET["select"] == "all")
					$sel = "checked";
				echo "<input type='checkbox' name='item[" . $rule->fields["ID"] . "]' value='1' $sel>";
				echo "</td>";
			}

			if ($canedit)
				echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/rule.form.php?ID=".$rule->fields["ID"]."\">" . $rule->fields["name"] . "</a></td>";
			else
				echo "<td>" . $rule->fields["name"] . "</td>";
					
			echo "<td>" . $rule->fields["description"] . "</td>";
			echo "</tr>";
		}
	}
	echo "<table>";

	if ($canedit) {
		echo "<div align='center'>";
		echo "<table cellpadding='5' width='80%'>";
		echo "<tr><td><img src=\"" . $CFG_GLPI["root_doc"] . "/pics/arrow-left.png\" alt=''></td><td><a onclick= \"if ( markAllRows('entityaffectation_form') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?ID=$ID&amp;select=all'>" . $LANG["buttons"][18] . "</a></td>";

		echo "<td>/</td><td><a onclick= \"if ( unMarkAllRows('entityaffectation_form') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?ID=$ID&amp;select=none'>" . $LANG["buttons"][19] . "</a>";
		echo "</td><td align='left' width='80%'>";
		echo "<input type='submit' name='delete_rule' value=\"" . $LANG["buttons"][6] . "\" class='submit'>";
		echo "</td>";
		echo "</table>";

		echo "</div>";

	}
	echo "</form>";
}

/**
 * Return all rules from database
 * @param type of rules
 * @param withcriterias import rules criterias too
 * @param withactions import rules actions too
 */
function getRulesByID($rule_type, $ID, $withcriterias, $withactions) {
	global $DB;
	$ocs_affect_computer_rules = array ();

	//Get all the rules whose rule_type is $rule_type and entity is $ID
	$sql="SELECT * FROM `glpi_rules_actions` as gra, glpi_rules_descriptions as grd  WHERE gra.FK_rules=grd.ID AND gra.field='FK_entities'  and grd.rule_type=".$rule_type." and gra.value='".$ID."'";
	
	$result = $DB->query($sql);
	while ($rule = $DB->fetch_array($result)) {
		$affect_rule = new Rule;
		$affect_rule->getRuleWithCriteriasAndActions($rule["ID"], 0, 1);
		$ocs_affect_computer_rules[] = $affect_rule;
	}

	return $ocs_affect_computer_rules;
}

/**
 * Return the condition label by giving his ID
 * @param condition's ID
 * @return condition's label
 */
function getConditionByID($ID)
{
		global $LANG;
		switch ($ID)
		{
			case PATTERN_IS : 
				return $LANG["rulesengine"][0];
			case PATTERN_IS_NOT:
				return $LANG["rulesengine"][1];
			case PATTERN_CONTAIN:
				return $LANG["rulesengine"][2];
			case PATTERN_NOT_CONTAIN:
				return $LANG["rulesengine"][3];
			case PATTERN_BEGIN:
				return $LANG["rulesengine"][4];
			case PATTERN_END:
				return $LANG["rulesengine"][5];
	}
}

/**
 * Get a criteria description by his ID and type
 * @param the criteria's ID
 * @param the criteria's type
 * @return the criteria's description
 */
function getCriteriaDescriptionByID($ID,$type)
{
		global $LANG,$RULES_CRITERIAS;
		switch ($type)
		{
			case RULE_OCS_AFFECT_COMPUTER : 
				$rule = getCriteriaByID($ID,$type);
				return $rule["name"];
			case RULE_LDAP_AFFECT_RIGHT:
				break;
			default:
				break;	
	}
}

/**
 * Get a criteria by his ID and type
 * @param the criteria's ID
 * @param the criteria's type
 * @return the criteria's informations
  */
function getCriteriaByID($ID,$type)
{
	global $RULES_CRITERIAS;
	foreach ($RULES_CRITERIAS[$type] as $rule)
	{
		if ($rule["ID"] == $ID)
			return $rule;
	}
}

/**
 * Return all the search constants for a type of rules
 * @param $type the type of rule
 * @return an array of search constants
 */
function getCriteriasByType($type)
{
	global $RULES_CRITERIAS;
	return $RULES_CRITERIAS[$type];
}
/**
 * Return a value associated with a pattern
 * @param the pattern's value
 * @param the rule's type
 * @param the pattern
 */
function getCriteriaPatternValue($value,$type,$pattern)
{
	switch ($type)
	{
		case RULE_OCS_AFFECT_COMPUTER :
			switch ($pattern)
			{
				case "TAG" :
					return $value;
				case "DOMAIN" :
					return $value;
				case "OCS_SERVER":
					$ocs_conf = getOcsConf($value);
					if ($ocs_conf)
						return $ocs_conf["name"];
					else
						return $value;
			}
		break;
		case RULE_LDAP_AFFECT_RIGHT:
			return $value;
		default:
			return $value;
	}
}

/**
 * Display a dropdown with all the rule matching
 */
function dropdownRulesMatch($name,$value=''){
	global $LANG;

	$elements[0]["name"] = AND_MATCHING;
	$elements[0]["value"] = AND_MATCHING;
	$elements[1]["name"] = OR_MATCHING;
	$elements[1]["value"] = OR_MATCHING;
	dropdownArrayValues($name,$elements,$value);
}

/**
 * Display a dropdown with all the criterias
 */
function dropdownRulesConditions($name,$value=''){
	global $LANG;

	$elements[0]["name"] = $LANG["rulesengine"][0];
	$elements[0]["value"] = PATTERN_IS;
	$elements[1]["name"] = $LANG["rulesengine"][1];
	$elements[1]["value"] = PATTERN_IS_NOT;
	$elements[2]["name"] = $LANG["rulesengine"][2];
	$elements[2]["value"] = PATTERN_CONTAIN;
	$elements[3]["name"] = $LANG["rulesengine"][3];
	$elements[3]["value"] = PATTERN_NOT_CONTAIN;
	$elements[4]["name"] = $LANG["rulesengine"][4];
	$elements[4]["value"] = PATTERN_BEGIN;
	$elements[5]["name"] = $LANG["rulesengine"][5];
	$elements[5]["value"] = PATTERN_END;
	
	dropdownArrayValues($name,$elements,$value);
}

/**
 * Display a dropdown with all the possible actions
 */
function dropdownRulesActions($name,$value=''){
	global $LANG;

	$elements[0]["name"] = $LANG["rulesengine"][22];
	$elements[0]["value"] = "assign";
	$elements[1]["name"] = $LANG["rulesengine"][23];
	$elements[1]["value"] = "set";
	$elements[2]["name"] = $LANG["rulesengine"][23];
	$elements[2]["value"] = "get";
	
	dropdownArrayValues($name,$elements,$value);
}

/**
 * Get the list of all tables to include in the query
 * @type : the rule's type
 * @return an array of table names
 */
function getTablesForQuery($type)
{
	global $RULES_CRITERIAS;
	$criterias = getCriteriasByType($type);
	$tables = array();
	foreach ($criterias as $criteria)
		if ($criteria['table'] != '' && !array_key_exists($criteria["table"],$tables)) 
			$tables[]=$criteria['table'];
			
	return $tables;		  
}

function getFieldsForQuery($type)
{
	global $RULES_CRITERIAS;
	$criterias = getCriteriasByType($type);
	$field = array();
	foreach ($criterias as $criteria)
		//If the field name is not null AND a table name is provided
		if ($criteria['field'] != '')
			if ( $criteria['table'] != '') 
				$fields[]=$criteria['table'].".".$criteria['field'];
			else
				$fields[]=$criteria['field'];	
			
	return $fields;		  
}

/**
 * Return all possible criterias for a type of rule
 */
function getCriteriasByRuleType($type)
{
	global $RULES_CRITERIAS;
	$criterias = getCriteriasByType($type);
	$field = array();
	foreach ($criterias as $criteria)
		$fields[]=array("name"=>$criteria['name'],"value"=>$criteria['ID']);	
			
	return $fields;		  
}

function getFKFieldsForQuery($type)
{
	global $RULES_CRITERIAS;
	$criterias = getCriteriasByType($type);
	$field = array();
	foreach ($criterias as $criteria)
		//If the field name is not null AND a table name is provided
		if ($criteria['linkfield'] != '')
				$fields[]=$criteria['table'].".".$criteria['linkfield'];
			
	return $fields;		  
}

function getRuleByType($type)
{
	switch ($type)
	{
		case RULE_OCS_AFFECT_COMPUTER :
			return new OcsAffectEntityRule(-1);
		
		default:
			break;
	}
}

function getRuleType($ID)
{
	$rule = new Rule;
	$rule->getRuleWithCriteriasAndActions($ID,0,0);
	return $rule->fields["rule_type"];
	
}
?>
