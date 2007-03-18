<?php
/*
 * @version $Id: rule.ocs.class.php 4582 2007-03-13 23:37:19Z moyo $
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
*   - actions
* 
**/
class LdapAffectEntityRule extends Rule {

	//Store the id of the ocs server
	var $ldap_server_id;

	function LdapAffectEntityRule($ldap_server_id=-1) {
		$this->table = "glpi_rules_descriptions";
		$this->type = -1;
		$this->ldap_server_id = $ldap_server_id;
		$this->rule_type = RULE_LDAP_AFFECT_ENTITY;
		
		//Dynamically add all the ldap criterias to the current list of rule's criterias
		$this->addLdapCriteriasToArray();
	}


	/**
	 * Get the attributes needed for processing the rules
	 * @param type type of the rule
	 * @param extra_params extra parameters given
	 * @return an array of attributes
	 */
	function getRulesMatchingAttributes($type, $ldap_server,$user_fields) {
		
		$rule_parameters = array();
		
		//Get all the ldap fields
		$fields = getFieldsForQuery(RULE_LDAP_AFFECT_ENTITY);
		
		foreach ($fields as $field)
			if (isset($user_fields[$field["field"]]))
			{
				switch($field["field"])
				{
					case "LDAP_SERVER":
						$rule_parameters["LDAP_SERVER"] = $user_fields[$field["field"]];
						break;
					default :
						$rule_parameters[$field["field"]] = $user_fields[$field["field"]];
						break;	
				}
						
			}
		return $rule_parameters;	
	}

	/**
	 * Try to find which rule is matched
	 * @return an LdapAffectEntityRule object 
	 */
	function processAllRules($computer_id) {
		global $DB;
		// MOYO : A faire dans la rule Collection (du style option dans rulecollection : stop on first execution)
		//Get all rules to affect computers to an entity
		$sql = "SELECT ID from glpi_rules_descriptions WHERE rule_type=" . RULE_LDAP_AFFECT_ENTITY . " ORDER by ranking ASC";
		$result = $DB->query($sql);
		while ($rule = $DB->fetch_array($result)) {
			$ocsrule = new LdapAffectEntityRule($this->ocs_server_id);
			$ocsrule->getRuleWithCriteriasAndActions($rule["ID"], 1, 1);

			//We need to provide the current computer id
			$rule_infos = $ocsrule->getRulesMatchingAttributes(RULE_LDAP_AFFECT_ENTITY, $computer_id);
			if ($ocsrule->processRule($rule_infos,RULE_LD))
			{
				$this->matched_rule = $ocsrule;
				return true;
			} 
		}

		return false;
	}
	function maxActionsCount(){
		// Unlimited
		return 2;
	}
	/**
	 * Display form to add rules
	 * @param rule_type Type of rule (ocs_affectation, ldap_rights)
	 */
	function showAndAddRuleForm($target, $ID) {
		global $LANG, $CFG_GLPI;

		$canedit = haveRight("config", "w");

		echo "<form name='entityaffectation_form' id='entityaffectation_form' method='post' action=\"$target\">";

		if ($canedit) {

			echo "<div align='center'>";
			echo "<table  class='tab_cadre_fixe'>";
			echo "<tr class='tab_bg_1'><th colspan='5'>" . $LANG["rulesengine"][21] . $LANG["rulesengine"][18] . "</tr><tr><td class='tab_bg_2' align='center'>";
			echo $LANG["common"][16] . ":";
			echo "</td><td align='center' class='tab_bg_2'>";
			autocompletionTextField("name", "glpi_rules_descriptions", "name", "", 30);
			echo $LANG["joblist"][6] . ":";
			autocompletionTextField("description", "glpi_rules_descriptions", "description", "", 30);
			echo "</td><td align='center' class='tab_bg_2'>";
			echo $LANG["rulesengine"][9] . ":";
			$this->dropdownRulesMatch("match", "AND");
			echo "</td><td align='center' class='tab_bg_2'>";
			echo "<input type=hidden name='rule_type' value=\"" . $this->rule_type . "\">";
			echo "<input type=hidden name='FK_entities' value=\"-1\">";
			echo "<input type=hidden name='affectentity' value=\"" . $ID . "\">";
			echo "<input type='submit' name='add_rule' value=\"" . $LANG["buttons"][8] . "\" class='submit'>";
			echo "</td></tr>";

			echo "</table></div><br>";

		}

		echo "<div align='center'><table class='tab_cadrehov'><tr><th colspan='3'>" . $LANG["entity"][5] . "</th></tr>";

		//Get all rules and actions
		$rules = $this->getRulesByID( $ID, 0, 1);

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
					echo "<td><a href=\"" . $CFG_GLPI["root_doc"] . "/front/rule.ocs.form.php?ID=" . $rule->fields["ID"] . "&amp;onglet=1\">" . $rule->fields["name"] . "</a></td>";
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
	 * Get all ldap rules criterias from the DB and add them into the RULES_CRITERIAS
	 */
	function addLdapCriteriasToArray()
	{
		global $DB,$RULES_CRITERIAS;

			$sql = "SELECT name,value,rule_type FROM glpi_rules_ldap_parameters WHERE rule_type=".RULE_LDAP_AFFECT_ENTITY;
			$result = $DB->query($sql);
			while ($datas = $DB->fetch_array($result))
			{
					$RULES_CRITERIAS[RULE_LDAP_AFFECT_ENTITY][$datas["value"]]['name']=$datas["name"];
					$RULES_CRITERIAS[RULE_LDAP_AFFECT_ENTITY][$datas["value"]]['field']=$datas["value"];
					$RULES_CRITERIAS[RULE_LDAP_AFFECT_ENTITY][$datas["value"]]['linkfield']='';
					$RULES_CRITERIAS[RULE_LDAP_AFFECT_ENTITY][$datas["value"]]['table']='';
				}
	}
	
/**
 * Return all rules from database
 * @param type of rules
 * @param withcriterias import rules criterias too
 * @param withactions import rules actions too
 */
function getRulesByID($ID, $withcriterias, $withactions) {
	global $DB;
	$ocs_affect_computer_rules = array ();
	// MOYO : quoi donc que ca fout la ca ?
	// MOYO : ca correspond pas deja à un cas particulier de ca : getRuleWithCriteriasAndActions ?


	//Get all the rules whose rule_type is $rule_type and entity is $ID
	$sql="SELECT * FROM `glpi_rules_actions` as gra, glpi_rules_descriptions as grd  WHERE gra.FK_rules=grd.ID AND gra.field='FK_entities'  and grd.rule_type=".$this->rule_type." and gra.value='".$ID."'";
	
	$result = $DB->query($sql);
	while ($rule = $DB->fetch_array($result)) {
		$affect_rule = new Rule;
		$affect_rule->getRuleWithCriteriasAndActions($rule["ID"], 0, 1);
		$ocs_affect_computer_rules[] = $affect_rule;
	}

	return $ocs_affect_computer_rules;
}



}


class LdapRuleCollection extends RuleCollection {

	function LdapRuleCollection() {
		global $DB;
		$this->rule_type = RULE_LDAP_AFFECT_ENTITY;
		$this->rule_class_name = 'LdapAffectEntityRule';
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][31];
	}

}
?>
