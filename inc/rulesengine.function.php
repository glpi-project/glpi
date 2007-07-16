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
 
define ("RULE_WILDCARD","*");
 // Get rules_option array

include_once (GLPI_ROOT."/inc/rules.constant.php");

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
	
	//Perform comparison with fields in lower case
	$field = strtolower($field);
	$pattern = strtolower($pattern);
	
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
			$value = strpos($field,$pattern);
			if (($value !== false) && $value == 0)
				return true;
			else
				return false;	
			
		case PATTERN_CONTAIN:
			$value = strpos($field,$pattern);
			if (($value !== false) && $value >= 0)
				return true;
			else
				return false;	
			
		case PATTERN_NOT_CONTAIN:
			$value = strpos($field,$pattern);
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
 * Return the condition label by giving his ID
 * @param $ID condition's ID
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
 * Display a dropdown with all the criterias
 */
function dropdownRulesConditions($type,$name,$value=''){
	global $LANG;

	$elements[PATTERN_IS] = $LANG["rulesengine"][0];
	$elements[PATTERN_IS_NOT] = $LANG["rulesengine"][1];
	if ($type!="dropdown"){
		$elements[PATTERN_CONTAIN] = $LANG["rulesengine"][2];
		$elements[PATTERN_NOT_CONTAIN] = $LANG["rulesengine"][3];
		$elements[PATTERN_BEGIN] = $LANG["rulesengine"][4];
		$elements[PATTERN_END] = $LANG["rulesengine"][5];
		$elements[REGEX_MATCH] = $LANG["rulesengine"][26];
		$elements[REGEX_NOT_MATCH] = $LANG["rulesengine"][27];
	}
	
	dropdownArrayValues($name,$elements,$value);
}

/**
 * Display a dropdown with all the possible actions
 */
function dropdownRulesActions($name,$value=''){
	global $LANG;

	$elements["assign"] = $LANG["rulesengine"][22];
/*	$elements["set"] = $LANG["rulesengine"][23];
	$elements["get"] = $LANG["rulesengine"][31];
*/
	
	dropdownArrayValues($name,$elements,$value);
}

function getActionByID($ID)
{
	global $LANG;
	switch ($ID)
	{
		case "assign" : 
			return $LANG["rulesengine"][22];
	}
}

?>
