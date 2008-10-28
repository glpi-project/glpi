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
 
define ("RULE_WILDCARD","*");
 // Get rules_option array

include_once (GLPI_ROOT."/inc/rules.constant.php");

/**
 * Try to match a definied rule
 * 
 * @param $field the field to match
 * @param $condition the condition (is, is_not, contain, not_contain,begin,end)
 * @param $pattern the pattern to match
 * @param $regex_result
 * @return true if the field match the rule, false if it doesn't match
**/
function matchRules($field, $condition, $pattern,&$regex_result) {

	//If pattern is wildcard, don't check the rule and return true
	if ($pattern == RULE_WILDCARD){
		return true;
	}

	// Trim for remove keyboard errors
	$field=trim($field);
	$pattern=trim($pattern);
	if ($condition != REGEX_MATCH && $condition != REGEX_NOT_MATCH){
		//Perform comparison with fields in lower case
		$field = strtolower($field);
		$pattern = strtolower($pattern);
	}
	
	switch ($condition) {
		case PATTERN_IS :
			if ($field == $pattern){
				return true;
			}else {
				return false;
			}
		case PATTERN_IS_NOT :
			if ($field == $pattern){
				return false;
			}else {
				return true;
			}
		case PATTERN_END:
			$value = "/".$pattern."$/";
			if (preg_match($value, $field) > 0){
				return true;
			}else {
				return false;
			}	
		case PATTERN_BEGIN:
			if (empty($pattern)){
				return false;
			}
			$value = strpos($field,$pattern);
			if (($value !== false) && $value == 0){
				return true;
			}else{
				return false;	
			}
		case PATTERN_CONTAIN:
			if (empty($pattern)){
				return false;
			}
			$value = strpos($field,$pattern);
			if (($value !== false) && $value >= 0){
				return true;
			}else{
				return false;	
			}
		case PATTERN_NOT_CONTAIN:
			if (empty($pattern)){
				return false;
			}
			$value = strpos($field,$pattern);
			if ($value === false){
				return true;
			}else {
				return false;
			}
		case REGEX_MATCH:
			$results = array();
			if (preg_match($pattern."i",$field,$results)>0){
				for ($i=1;$i<count($results);$i++){
					$regex_result[]=$results[$i];
				}
				return true;
			}else {
				return false;
			}
		case REGEX_NOT_MATCH:
			if (preg_match($pattern."i", $field) == 0){
				return true;
			}else {
				return false;
			}	
	}
	return false;
}


/**
 * Return the condition label by giving his ID
 * @param $ID condition's ID
 * @return condition's label
**/
function getConditionByID($ID){
	global $LANG;
	
	switch ($ID){
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
 * Display a dropdown with all the criterias
**/
function dropdownRulesConditions($type,$name,$value=''){
	global $LANG;

	$elements[PATTERN_IS] = $LANG["rulesengine"][0];
	$elements[PATTERN_IS_NOT] = $LANG["rulesengine"][1];
	$elements[PATTERN_CONTAIN] = $LANG["rulesengine"][2];
	$elements[PATTERN_NOT_CONTAIN] = $LANG["rulesengine"][3];
	$elements[PATTERN_BEGIN] = $LANG["rulesengine"][4];
	$elements[PATTERN_END] = $LANG["rulesengine"][5];
	$elements[REGEX_MATCH] = $LANG["rulesengine"][26];
	$elements[REGEX_NOT_MATCH] = $LANG["rulesengine"][27];
	
	return dropdownArrayValues($name,$elements,$value);
}


/**
 * Display a dropdown with all the possible actions
**/
function dropdownRulesActions($rule_type,$name,$value=''){
	global $LANG,$CFG_GLPI,$RULES_ACTIONS;

	$actions=array("assign");
	if (isset($RULES_ACTIONS[$rule_type][$value]['force_actions'])){
		$actions=$RULES_ACTIONS[$rule_type][$value]['force_actions'];
	}

	$elements=array();
	foreach ($actions as $action){
		switch ($action){
			case "assign":
				$elements["assign"] = $LANG["rulesengine"][22];
				break;
			case "regex_result":
				$elements["regex_result"] = $LANG["rulesengine"][45];
				break;
			case "append_regex_result":
				$elements["append_regex_result"] = $LANG["rulesengine"][79];
				break;
			case "affectbyip":
				$elements["affectbyip"] = $LANG["rulesengine"][46];
				break;
			case "affectbyfqdn":
				$elements["affectbyfqdn"] = $LANG["rulesengine"][47];
				break;
		}
	}
	
	return dropdownArrayValues($name,$elements,$value);
}

function getActionByID($ID){
	global $LANG;
	
	switch ($ID){
		case "assign" : 
			return $LANG["rulesengine"][22];
		case "regex_result":
			return $LANG["rulesengine"][45];
		case "append_regex_result":
			return $LANG["rulesengine"][79];
		case "affectbyip":
			return $LANG["rulesengine"][46];
		case "affectbyfqdn":
			return $LANG["rulesengine"][47];
						
	}
}

function getRuleClass($type){
	switch ($type){
		case RULE_OCS_AFFECT_COMPUTER :
			if (!class_exists('OcsAffectEntityRule')){
				include_once(GLPI_ROOT."/inc/rulesengine.class.php");
				include_once(GLPI_ROOT."/inc/rule.ocs.class.php");
			}
			return new OcsAffectEntityRule();
			break;		
		case RULE_AFFECT_RIGHTS :
			if (!class_exists('RightAffectRule')){
				include_once(GLPI_ROOT."/inc/rulesengine.class.php");
				include_once(GLPI_ROOT."/inc/rule.right.class.php");
			}
			return new RightAffectRule();
			break;
		case RULE_TRACKING_AUTO_ACTION:
			if (!class_exists('TrackingBusinessRule')){
				include_once(GLPI_ROOT."/inc/rulesengine.class.php");
				include_once(GLPI_ROOT."/inc/rule.tracking.class.php");
			}
			return new TrackingBusinessRule();
			break;
		case RULE_SOFTWARE_CATEGORY:
			if (!class_exists('SoftwareCategoriesRule')){
				include_once(GLPI_ROOT."/inc/rulesengine.class.php");
				include_once(GLPI_ROOT."/inc/rule.softwarecategories.class.php");
			}
			return new SoftwareCategoriesRule();
			break;
		case RULE_DICTIONNARY_SOFTWARE:
			if (!class_exists('DictionnarySoftwareRule')){
				include_once(GLPI_ROOT."/inc/rulesengine.class.php");
				include_once(GLPI_ROOT."/inc/rule.dictionnary.software.class.php");
			}
			return new DictionnarySoftwareRule;
			break;
		case RULE_DICTIONNARY_MANUFACTURER:
		case RULE_DICTIONNARY_MODEL_NETWORKING :
		case RULE_DICTIONNARY_MODEL_COMPUTER:
		case RULE_DICTIONNARY_MODEL_MONITOR:
		case RULE_DICTIONNARY_MODEL_PRINTER:
		case RULE_DICTIONNARY_MODEL_PERIPHERAL:
		case RULE_DICTIONNARY_MODEL_PHONE:
		case RULE_DICTIONNARY_TYPE_NETWORKING :
		case RULE_DICTIONNARY_TYPE_COMPUTER:
		case RULE_DICTIONNARY_TYPE_PRINTER:
		case RULE_DICTIONNARY_TYPE_MONITOR:
		case RULE_DICTIONNARY_TYPE_PERIPHERAL:
		case RULE_DICTIONNARY_TYPE_PHONE:
		case RULE_DICTIONNARY_OS:
		case RULE_DICTIONNARY_OS_SP:
		case RULE_DICTIONNARY_OS_VERSION:
			if (!class_exists('DictionnaryDropdownCollection')){
				include_once(GLPI_ROOT."/inc/rulesengine.class.php");
				include_once(GLPI_ROOT."/inc/rule.dictionnary.dropdown.class.php");
			}
			return new RuleDictionnaryDropdown($type);
			break;
	}
}

function getRuleCollectionClass($type){
	switch ($type){
		case RULE_OCS_AFFECT_COMPUTER :
			if (!class_exists('OcsRuleCollection')){
				include_once(GLPI_ROOT."/inc/rulesengine.class.php");
				include_once(GLPI_ROOT."/inc/rule.ocs.class.php");
			}
			return new OcsRuleCollection();
			break;		
		case RULE_AFFECT_RIGHTS :
			if (!class_exists('RightRuleCollection')){
				include_once(GLPI_ROOT."/inc/rulesengine.class.php");
				include_once(GLPI_ROOT."/inc/rule.right.class.php");
			}
			return new RightRuleCollection();
			break;
		case RULE_TRACKING_AUTO_ACTION:
			if (!class_exists('TrackingBusinessRuleCollection')){
				include_once(GLPI_ROOT."/inc/rulesengine.class.php");
				include_once(GLPI_ROOT."/inc/rule.tracking.class.php");
			}
			return new TrackingBusinessRuleCollection();
			break;
		case RULE_SOFTWARE_CATEGORY:
			if (!class_exists('SoftwareCategoriesRuleCollection')){
				include_once(GLPI_ROOT."/inc/rulesengine.class.php");
				include_once(GLPI_ROOT."/inc/rule.softwarecategories.class.php");
			}
			return new SoftwareCategoriesRuleCollection();
			break;
		case RULE_DICTIONNARY_SOFTWARE:
			if (!class_exists('DictionnarySoftwareCollection')){
				include_once(GLPI_ROOT."/inc/rulesengine.class.php");
				include_once(GLPI_ROOT."/inc/rule.dictionnary.software.class.php");
			}
			return new DictionnarySoftwareCollection;
			break;
		case RULE_DICTIONNARY_MANUFACTURER:
		case RULE_DICTIONNARY_MODEL_NETWORKING :
		case RULE_DICTIONNARY_MODEL_COMPUTER:
		case RULE_DICTIONNARY_MODEL_MONITOR:
		case RULE_DICTIONNARY_MODEL_PRINTER:
		case RULE_DICTIONNARY_MODEL_PERIPHERAL:
		case RULE_DICTIONNARY_MODEL_PHONE:
		case RULE_DICTIONNARY_TYPE_NETWORKING :
		case RULE_DICTIONNARY_TYPE_COMPUTER:
		case RULE_DICTIONNARY_TYPE_PRINTER:
		case RULE_DICTIONNARY_TYPE_MONITOR:
		case RULE_DICTIONNARY_TYPE_PERIPHERAL:
		case RULE_DICTIONNARY_TYPE_PHONE:
		case RULE_DICTIONNARY_OS:
		case RULE_DICTIONNARY_OS_SP:
		case RULE_DICTIONNARY_OS_VERSION:
			if (!class_exists('DictionnaryDropdownCollection')){
				include_once(GLPI_ROOT."/inc/rulesengine.class.php");
				include_once(GLPI_ROOT."/inc/rule.dictionnary.dropdown.class.php");
			}
			return new DictionnaryDropdownCollection($type);
			break;
	}
}

function getRuleCollectionClassByTableName($tablename){
	
	switch ($tablename){
		case "glpi_software":
			return getRuleCollectionClass(RULE_DICTIONNARY_SOFTWARE);
		case "glpi_dropdown_manufacturer":
			return getRuleCollectionClass(RULE_DICTIONNARY_MANUFACTURER);
		case "glpi_dropdown_model":
			return getRuleCollectionClass(RULE_DICTIONNARY_MODEL_COMPUTER);
		case "glpi_dropdown_model_monitors":
			return getRuleCollectionClass(RULE_DICTIONNARY_MODEL_MONITOR);
		case "glpi_dropdown_model_printers":
			return getRuleCollectionClass(RULE_DICTIONNARY_MODEL_PRINTER);
		case "glpi_dropdown_model_peripherals":
			return getRuleCollectionClass(RULE_DICTIONNARY_MODEL_PERIPHERAL);
		case "glpi_dropdown_model_networking":
			return getRuleCollectionClass(RULE_DICTIONNARY_MODEL_NETWORKING);
		case "glpi_dropdown_model_phone":
			return getRuleCollectionClass(RULE_DICTIONNARY_MODEL_PHONE);
		case "glpi_type_computers":
			return getRuleCollectionClass(RULE_DICTIONNARY_TYPE_COMPUTER);
		case "glpi_type_monitors":
			return getRuleCollectionClass(RULE_DICTIONNARY_TYPE_MONITOR);
		case "glpi_type_printers":
			return getRuleCollectionClass(RULE_DICTIONNARY_TYPE_PRINTER);
		case "glpi_type_peripherals":
			return getRuleCollectionClass(RULE_DICTIONNARY_TYPE_PERIPHERAL);
		case "glpi_type_networking":
			return getRuleCollectionClass(RULE_DICTIONNARY_TYPE_NETWORKING);
		case "glpi_dropdown_type_phone":
			return getRuleCollectionClass(RULE_DICTIONNARY_TYPE_PHONE);
		case "glpi_dropdown_os":
			return getRuleCollectionClass(RULE_DICTIONNARY_OS);
		case "glpi_dropdown_os_sp":
			return getRuleCollectionClass(RULE_DICTIONNARY_OS_SP);
		case "glpi_dropdown_os_version":
			return getRuleCollectionClass(RULE_DICTIONNARY_OS_VERSION);
		default:
			break;		
	}
}

function getCacheTableByRuleType($type){
	
	$rulecollection = getRuleCollectionClass($type);
	return $rulecollection->cache_table;
}

function getRegexResultById($action,$regex_results){
	
	if (count($regex_results)>0){
		if (preg_match_all("/#([0-9])/",$action,$results)>0){
			foreach($results[1] as $result){
				$action=str_replace("#$result",(isset($regex_results[$result])?$regex_results[$result]:''),$action);
			}
		}	
	}
	return $action;
}

/**
 * Get category name to display in commonheader by rule_type
**/
function getCategoryNameToDisplay($rule_type){
	
	switch ($rule_type){
		case RULE_OCS_AFFECT_COMPUTER:
		case RULE_AFFECT_RIGHTS:
		case RULE_SOFTWARE_CATEGORY:
		case RULE_TRACKING_AUTO_ACTION:
			return "rule";
		default:
			return "dictionnary";
	}
}

function getAlreadyUsedActionsByRuleID($rule_id,$rule_type){
	global $DB,$RULES_ACTIONS;
	$actions = array();

	$res = $DB->query("SELECT field FROM glpi_rules_actions WHERE FK_rules=".$rule_id);
	while ($action = $DB->fetch_array($res)){
		if (isset($RULES_ACTIONS[$rule_type][$action["field"]])) {
			$actions[$action["field"]] = $action["field"];
		}
	}
	return $actions;	
}

function processManufacturerName($old_name)
{
	if ($old_name == null)
		return $old_name;
		
	$rulecollection = new DictionnaryDropdownCollection(RULE_DICTIONNARY_MANUFACTURER);
	$output=array();
	$rulecollection->processAllRules(array("name"=>addslashes($old_name)),$output,array());
	if (isset($output["name"]))
		return $output["name"];
	else
		return $old_name;	
}
?>
