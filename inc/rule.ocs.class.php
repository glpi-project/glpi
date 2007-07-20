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
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------
if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}


class OcsRuleCollection extends RuleCollection {

	//Store the id of the ocs server
	var $ocs_server_id;

	function OcsRuleCollection($ocs_server_id) {
		$this->rule_type = RULE_OCS_AFFECT_COMPUTER;
		$this->rule_class_name = 'OcsAffectEntityRule';
		$this->ocs_server_id = $ocs_server_id;
		$this->stop_on_first_match=true;
		$this->right="rule_ocs";
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][18];
	}

	/**
	 * Get the attributes needed for processing the rules
	 * @return an array of attributes
	 */
	function prepareInputDataForProcess($input,$computer_id){
		global $DBocs;
		$tables = $this->getTablesForQuery();
		$fields = $this->getFieldsForQuery();
		$linked_fields = $this->getFKFieldsForQuery();


		$rule_parameters = array ();

		$sql = "";
		$begin_sql = "SELECT ";
		$select_sql = "";
		$from_sql = "";
		$where_sql = "";

		//Build the select request
		foreach ($fields as $field) {
			switch (strtoupper($field)) {
				
				//OCS server ID is provided by extra_params -> get the configuration associated with the ocs server
				case "OCS_SERVER" :
					$rule_parameters["OCS_SERVER"] = $this->ocs_server_id;
					break;
					//TAG and DOMAIN should come from the OCS DB 
				default :
					$select_sql .= ($select_sql != "" ? " , " : "") . $field;
					break;
			}

		}

		//Build the FROM part of the request
		//Remove all the non duplicated table names
		$tables = array_unique($tables);
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

			$fields = $this->getFieldsForQuery(1);

			//May have more than one line : for example in case of multiple network cards
			if ($DBocs->numrows($result) > 0)
			{
				while ($datas = $DBocs->fetch_array($result))
				{
					foreach ($fields as $field) {
						if ($field != "OCS_SERVER" && isset($datas[$field]))
							$ocs_datas[$field][] = $datas[$field];
					}
				}
			}

			return array_merge($rule_parameters, $ocs_datas);
		} else
			return $rule_parameters;
	}



	/**
	* Get the list of all tables to include in the query
	* @return an array of table names
	*/
	function getTablesForQuery()
	{
		global $RULES_CRITERIAS;

		$tables = array();
		foreach ($RULES_CRITERIAS[$this->rule_type] as $criteria){
			if ((!isset($criteria['virtual']) || !$criteria['virtual']) && $criteria['table'] != '' && !array_key_exists($criteria["table"],$tables)) {
				$tables[]=$criteria['table'];
			}
		}
		return $tables;		  
	}
	
	function getFieldsForQuery($withouttable=0)
	{
		global $RULES_CRITERIAS;

		$fields = array();
		foreach ($RULES_CRITERIAS[$this->rule_type] as $key => $criteria){

			if ($withouttable)
			{
				if (strcasecmp($key,$criteria['field']) != 0)
					$fields[]=$key;
				else	
					$fields[]=$criteria['field'];
			}
			else
			{	
				//If the field is different from the key
				if (strcasecmp($key,$criteria['field']) != 0)
					$as = " AS ".$key;
				else
					$as ="";
					
				//If the field name is not null AND a table name is provided
				if (($criteria['field'] != '' && (!isset($criteria['virtual']) || !$criteria['virtual']))){
					if ( $criteria['table'] != '') {
						$fields[]=$criteria['table'].".".$criteria['field'].$as;
					} else{
						$fields[]=$criteria['field'].$as;	
					}
				}
				else
				$fields[]=$criteria['id'];
			}
		}
		return $fields;		  
	}
	
	
	function getFKFieldsForQuery()
	{
		global $RULES_CRITERIAS;

		$fields = array();
		foreach ($RULES_CRITERIAS[$this->rule_type] as $criteria){
			//If the field name is not null AND a table name is provided
			if ( (!isset($criteria['virtual']) || !$criteria['virtual']) && $criteria['linkfield'] != ''){
				$fields[]=$criteria['table'].".".$criteria['linkfield'];
			}
		}	
		return $fields;		  
	}


}

/**
* Rule class store all informations about a GLPI rule :
*   - description
*   - criterias
*   - actions
* 
**/
class OcsAffectEntityRule extends Rule {


	function OcsAffectEntityRule($ocs_server_id=-1) {
		$this->table = "glpi_rules_descriptions";
		$this->type = -1;
		$this->rule_type = RULE_OCS_AFFECT_COMPUTER;
		$this->right="rule_ocs";
		$this->can_sort=true;
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][18];
	}

	function maxActionsCount(){
		// Unlimited
		return 1;
	}
	/**
	 * Display form to add rules
	 * @param $target 
	 * @param $ID
	 */
	function showAndAddRuleForm($target, $ID) {
		global $LANG, $CFG_GLPI;

		$canedit = haveRight($this->right, "w");

		echo "<form name='entityaffectation_form' id='entityaffectation_form' method='post' action=\"$target\">";

		if ($canedit) {

			echo "<div align='center'>";
			echo "<table  class='tab_cadre_fixe'>";
			echo "<tr class='tab_bg_1'><th colspan='4'>" .  $LANG["rulesengine"][18] . "</tr><tr><td class='tab_bg_2' align='center'>";
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
		echo "</table></div>";

		if ($canedit) {
			echo "<div align='center'>";
			echo "<table width='80%'>";
			echo "<tr><td><img src=\"" . $CFG_GLPI["root_doc"] . "/pics/arrow-left.png\" alt=''></td><td class='center'><a onclick= \"if ( markAllRows('entityaffectation_form') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?ID=$ID&amp;select=all'>" . $LANG["buttons"][18] . "</a></td>";

			echo "<td>/</td><td class='center'><a onclick= \"if ( unMarkAllRows('entityaffectation_form') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?ID=$ID&amp;select=none'>" . $LANG["buttons"][19] . "</a>";
			echo "</td><td align='left' width='80%'>";
			echo "<input type='submit' name='delete_computer_rule' value=\"" . $LANG["buttons"][6] . "\" class='submit'>";
			echo "</td>";
			echo "</table>";

			echo "</div>";

		}
		echo "</form>";
	}


/**
 * Return all rules from database
 * @param $ID ID of the rules
 * @param withcriterias import rules criterias too
 * @param withactions import rules actions too
 */
function getRulesByID($ID, $withcriterias, $withactions) {
	global $DB;
	$ocs_affect_computer_rules = array ();


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


?>
