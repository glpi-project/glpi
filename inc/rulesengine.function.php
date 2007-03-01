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
/**
 * Try to match a definied rule
 * 
 * @param $field the field to match
 * @param $condition the condition (is, is_not, contain, not_contain,begin,end)
 * @param $pattern the pattern to match
 * @return true if the field match the rule, false if it doesn't match
 */
function matchRules($field, $condition, $pattern) {
	switch ($condition) {
		case PATTERN_IS :
			if ($field == $pattern)
				return true;
			else
				return false;
			break;
		case PATTERN_IS_NOT :
			if ($field == $pattern)
				return false;
			else
				return true;
			break;
		default :
			return false;
	}
}

/**
 * Display form to add rules
 * @param rule_type Type of rule (ocs_affectation, ldap_rights)
 */
function showRules($target, $ID, $rule_type) {
	global $LANG, $CFG_GLPI;

	$canedit = haveRight("config", "w");

	echo "<form name='entityaffectation_form' id='entityaffectation_form' method='post' action=\"$target\">";
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
				echo "<input type='checkbox' name='item[" . $rule->description->fields["ID"] . "]' value='1' $sel>";
				echo "</td>";
			}

			if ($canedit)
				echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/affectcomputerrule.form.php?ID=".$ID."\">" . $rule->description->fields["title"] . "</a></td>";
			else
				echo "<td>" . $rule->description->fields["title"] . "</td>";
					
			echo "<td>" . $rule->description->fields["description"] . "</td>";
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
		echo "<input type='submit' name='deleteuser' value=\"" . $LANG["buttons"][6] . "\" class='submit'>";
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
?>
