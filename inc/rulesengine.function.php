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
		case REGEX_MATCH:
			if (preg_match($pattern, $field) > 0)
				return true;
			else
				return false;	
		case REGEX_NOT_MATCH:
			if (preg_match($pattern, $field) == 0)
				return true;
			else
				return false;	
	}
	return false;
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
	// MOYO : quoi donc que ca fout la ca ?
	// MOYO : ca correspond pas deja à un cas particulier de ca : getRuleWithCriteriasAndActions ?


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
			case REGEX_MATCH:
				return $LANG["rulesengine"][26];
			case REGEX_NOT_MATCH:
				return $LANG["rulesengine"][27];
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
	// MOYO : pourquoi ce n'est pas dans la classe ? 
	global $RULES_CRITERIAS;
	foreach ($RULES_CRITERIAS[$type] as $rule)
	{
		if ($rule["ID"] == $ID)
			return $rule;
	}
	return false;
}

/**
 * Return all the search constants for a type of rules
 * @param $type the type of rule
 * @return an array of search constants
 */
function getCriteriasByType($type)
{
	// MOYO : pourquoi ce n'est pas dans la classe ? 

	global $RULES_CRITERIAS;
	return $RULES_CRITERIAS[$type];
}

/**
 * Display a dropdown with all the rule matching
 */
function dropdownRulesMatch($name,$value=''){
	global $LANG;

	$elements[AND_MATCHING] = AND_MATCHING;
	$elements[OR_MATCHING] = OR_MATCHING;
	dropdownArrayValues($name,$elements,$value);
}

/**
 * Display a dropdown with all the criterias
 */
function dropdownRulesConditions($name,$value=''){
	global $LANG;

	$elements[PATTERN_IS] = $LANG["rulesengine"][0];
	$elements[PATTERN_IS_NOT] = $LANG["rulesengine"][1];
	$elements[PATTERN_CONTAIN] = $LANG["rulesengine"][2];
	$elements[PATTERN_NOT_CONTAIN] = $LANG["rulesengine"][3];
	$elements[PATTERN_BEGIN] = $LANG["rulesengine"][4];
	$elements[PATTERN_END] = $LANG["rulesengine"][5];
	$elements[REGEX_MATCH] = $LANG["rulesengine"][26];
	$elements[REGEX_NOT_MATCH] = $LANG["rulesengine"][27];
	
	dropdownArrayValues($name,$elements,$value);
}

/**
 * Display a dropdown with all the possible actions
 */
function dropdownRulesActions($name,$value=''){
	global $LANG;

	$elements["assign"] = $LANG["rulesengine"][22];
/*	$elements["set"] = $LANG["rulesengine"][23];
	$elements["get"] = $LANG["rulesengine"][23];
*/
	
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
 * @param type the rule's type
 */
function getCriteriasByRuleType($type)
{
	global $RULES_CRITERIAS;
	$criterias = getCriteriasByType($type);
	$field = array();
	foreach ($criterias as $ID => $criteria)
		$fields[]=array("name"=>$criteria['name'],"value"=>$ID);	
			
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
