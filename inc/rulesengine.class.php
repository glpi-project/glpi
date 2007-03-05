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
if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

class RuleDescription extends CommonDBTM {

	function RuleDescription() {
		$this->table = "glpi_rules_descriptions";
		$this->type = -1;
	}

	function getRuleDescription($ID) {
		global $DB;
		$sql = "SELECT * FROM glpi_rules_descriptions as gud, glpi_rules_actions as gra WHERE gra.into=" . $ID . " AND gra.FK_rules=grd.ID";

		$rules_description = array ();
		$result = $DB->query($sql);

		//It should have only one description per rule. But in case there's more than one, only return the first
		if ($DB->numrows($result) > 0)
			$this->fields = $DB->fetch_array($result);
	}

	function showForm($target,$ID,$withtemplate='')
	{
			global $CFG_GLPI, $LANG;
			
			$canedit = haveRight("config", "w");
			
			$this->showOnglets($ID, $withtemplate,$_SESSION['glpi_onglet']);
			echo "<form name='entityaffectation_form' id='entityaffectation_form' method='post' action=\"$target\">";
			
			if ($ID != -1)
				echo "<input type=hidden name=ID value='".$ID."''";
				
			echo "<div align='center'>"; 
			echo "<table class='tab_cadre_fixe'>";
			echo "<tr><th colspan='4'>" . $LANG["entity"][5] . "</th></tr>";
			echo "<tr>";
			echo "<td class='tab_bg_2'>".$LANG["common"][16]."</td>";
			echo "<td class='tab_bg_2'>";
			autocompletionTextField("name",$this->table,"name",$this->fields["name"] ,25);
			echo "</td>";
			echo "<td class='tab_bg_2'>".$LANG["joblist"][6]."</td>";
			echo "<td class='tab_bg_2'>";
			autocompletionTextField("description",$this->table,"description",$this->fields["description"] ,25);
			echo "</td>";
			echo "</tr>";

			echo "<tr>";
			echo "<td class='tab_bg_2'>".$LANG["rulesengine"][9]."</td>";
			echo "<td class='tab_bg_2'>";
			dropdownRulesMatch("match",$this->fields["match"]);
			echo "</td>";
			
			switch ($this->fields["rule_type"])
			{
				case RULE_LDAP_AFFECT_RIGHT :
					echo "<td class='tab_bg_2'>".$LANG["rulesengine"][10]."</td>";
					echo "<td class='tab_bg_2'>".$this->fields["ranking"]."</td>";
					break;

				break;
				default:
					echo "<td class='tab_bg_2' colspan='2'></td>";
				break;
			}
			echo "</tr>";
			
			if ($canedit)
			{
				echo "<tr><td class='tab_bg_2' align='center' colspan='2'>";
				echo "<input type='submit' name='update_description' value=\"" . $LANG["buttons"][7] . "\" class='submit'></td>";
				echo "<td class='tab_bg_2' align='center' colspan='2'>";
				echo "<input type='submit' name='delete_rule' value=\"" . $LANG["buttons"][6] . "\" class='submit'></td>";
				echo "</tr>";
			}
			
			echo "</table>";

			echo "</div></form>";
	}

	function defineOnglets($withtemplate=''){
		global $LANG;

		$ong[1]=$LANG["title"][26];
		$ong[2]=$LANG["rulesengine"][6];
		$ong[3]=$LANG["rulesengine"][7];
		return $ong;
	}

}

class RuleAction extends CommonDBTM {
	function RuleAction() {
		$this->table = "glpi_rules_actions";
		$this->type = -1;
	}
	/**
	 * Get all actions for a given rule
	 * @param $ID the rule_description ID
	 * @return an array of RuleAction objects
	 */
	function getRuleActions($ID) {
		$sql = "SELECT * FROM glpi_rules_actions WHERE FK_rules=" . $ID;
		global $DB;

		$rules_actions = array ();
		$result = $DB->query($sql);
		while ($rule = $DB->fetch_array($result))
		{
			$tmp = new RuleAction;
			$tmp->fields = $rule;
			$rules_actions[] = $tmp;
		}
		return $rules_actions;
	}

	function showForm($target,$ID,$ruleid=-1)
	{
		
	}

	function showMinimalForm($type,$editable)
	{
			global $LANG,$CFG_GLPI;
			
			$canedit = haveRight("config","w");
			
			if (!$editable)
				echo "<tr>";  
			else
			{
				echo "<tr class='tab_bg_1'>";
				
				if ($canedit) {
					echo "<td width='10'>";
					$sel = "";
					if (isset ($_GET["select"]) && $_GET["select"] == "all")
						$sel = "checked";
					echo "<input type='checkbox' name='item[" . $this->fields["ID"] . "]' value='1' $sel>";
					echo "</td>";
				}

			}
			
			switch ($type)
			{
				case RULE_LDAP_AFFECT_RIGHT:
				break;
				case RULE_OCS_AFFECT_COMPUTER:
					if ($editable && $canedit)
						echo "<td class='tab_bg_2'><a href=\"".$CFG_GLPI["root_doc"]."/front/ruleaction.form.php?action=edit_action&ID=".$this->fields["ID"]."\">" . $this->fields["action_type"] . "</a></td>";
					else
					
					echo "<td class='tab_bg_2'>".$this->fields["action_type"]."</td>";
					echo "<td class='tab_bg_2'>".$LANG["log"][63]."</td>";
					echo "<td class='tab_bg_2'>".$this->fields["value"]."</td>";
				break;
				default:
				break;
			}
			echo "</tr>";
	}

}

class RuleCriteria extends CommonDBTM {
	function RuleCriteria() {
		$this->table = "glpi_rules_criterias";
		$this->type = -1;
	}

	/**
	* Get all criterias for a given rule
	* @param $ID the rule_description ID
	* @return an array of RuleCriteria objects
	*/
	function getRuleCriterias($ID) {
		global $DB;
		$sql = "SELECT * FROM glpi_rules_criterias WHERE FK_rules=" . $ID;

		$rules_list = array ();
		$result = $DB->query($sql);
		while ($rule = $DB->fetch_array($result))
		{
			$tmp = new RuleCriteria;
			$tmp->fields = $rule;
			$rules_list[] = $tmp;
		}
		return $rules_list;
	}

	function showForm($target,$ID,$ruleid=-1)
	{
			global $LANG,$CFG_GLPI;
			$canedit = haveRight("config", "w");
			echo "<form name='entityaffectation_form' id='entityaffectation_form' method='post' action=\"$target\">";
			echo "<div align='center'>"; 
			echo "<table class='tab_cadre_fixe'>";
			echo "<tr><th colspan='4'>" . $LANG["rulesengine"][6] . "</th></tr>";
			echo "<tr>";
			echo "<td class='tab_bg_2'></td>";
			echo "<td class='tab_bg_2'>".$LANG["rulesengine"][16]."</td>";
			echo "<td class='tab_bg_2'>".$LANG["rulesengine"][14]."</td>";
			echo "<td class='tab_bg_2'>".$LANG["rulesengine"][15]."</td>";
			echo "</tr>";
		
	}

	function showMinimalForm($type,$editable)
	{
			global $LANG,$CFG_GLPI;
			$canedit = haveRight("config","w");
			
			if (!$editable)
				echo "<tr>";  
			else
			{
				echo "<tr class='tab_bg_1'>";
				
				if ($canedit) {
					echo "<td width='10'>";
					$sel = "";
					if (isset ($_GET["select"]) && $_GET["select"] == "all")
						$sel = "checked";
					echo "<input type='checkbox' name='item[" . $this->fields["ID"] . "]' value='1' $sel>";
					echo "</td>";
				}

			}
				
			switch ($type)
			{
				case RULE_LDAP_AFFECT_RIGHT:
				break;
				case RULE_OCS_AFFECT_COMPUTER:
					if ($editable && $canedit)
						echo "<td class='tab_bg_2'><a href=\"".$CFG_GLPI["root_doc"]."/front/rule.form.php?action=edit_criteria&ID=".$this->fields["ID"]."\">" . getCriteriaDescriptionByID($this->fields["criteria"],RULE_OCS_AFFECT_COMPUTER) . "</a></td>";
					else
						echo "<td class='tab_bg_2'>".getCriteriaDescriptionByID($this->fields["criteria"],RULE_OCS_AFFECT_COMPUTER)."</td>";
					
					echo "<td class='tab_bg_2'>".getConditionByID($this->fields["condition"])."</td>";
					echo "<td class='tab_bg_2'>".getCriteriaPatternValue($this->fields["pattern"],RULE_OCS_AFFECT_COMPUTER,$this->fields["criteria"])."</td>";
				break;
				default:
				break;
			}
			echo "</tr>";
	}
	
	function processRule($informations,$type)
	{
		//Get all the informations about the condition
		$criteria_informations = getCriteriaByID($this->fields["criteria"],$type);
		$field = $criteria_informations["field"];
		if ($field)
			return (matchRules($informations[$field],$this->fields["condition"],$this->fields["pattern"]));
		else
			return false;	
	}
}

class RuleCollection {
	var $rule_list = array();
	
function RuleCollection($rule_type)
{
		global $DB;
		//Select all the rules of a different type
		$sql = "SELECT ID FROM glpi_rules_descriptions WHERe rule_type=".$rule_type." ORDER by rank ASC";
		 $result = $DB->query($sql);
		 while ($rule=$DB->fetch_array($result))
		 {
		 	
		 	//For each rule, get a Rule object with all the criterias and actions
			$tempRule = new Rule;
			$tempRule->getRuleWithCriteriasAndActions($rule["ID"],1,1);
			
			//Add the object to the list of rules
			$rule_list[] = $tempRule; 	
		 }	
}
}

/**
 * Rule class store all informations about a GLPI rule :
 *   - description
 *   - criterias
 *   - actions */

class Rule extends CommonDBTM{

	//Rule description
	var $description;
	//Actions affected to this rule
	var $actions = array();

	//Criterias affected to this rule
	var $criterias = array();

	function Rule() {
		$this->table = "glpi_rules_descriptions";
		$this->type = -1;
	}

	/**
	 * Get all criterias for a given rule
	 * @param $ID the rule_description ID
	 * @param withcriterias 1 to retrieve all the criterias for a given rule
	 * @param withaction  1 to retrive all the actions for a given rule
	 */
	function getRuleWithCriteriasAndActions($ID, $withcriterias = 0, $withactions = 0) {
		$this->description = new RuleDescription;
		$this->description->getFromDB($ID);
		
		if ($withactions)
		{
			$RuleAction = new RuleAction;
			$this->actions = $RuleAction->getRuleActions($ID);
		}	
		if ($withcriterias)
		{
			$RuleCriterias = new RuleCriteria;
			$this->criterias = $RuleCriterias->getRuleCriterias($ID);
		}
	}
	
	/**
	 * Print a good title for computer pages
	 *
	 *@return nothing (diplays)
	 *
	 **/
	function title(){
		global  $LANG,$CFG_GLPI;

		$buttons=array();
		displayTitle($CFG_GLPI["root_doc"]."/pics/computer.png",$LANG["Menu"][0],$LANG["rulesengine"][8],$buttons);
	}

	/**
	 * Display all rules actions
	 */
	function showActionsList($target,$editable)
	{
			global $CFG_GLPI, $LANG;
			
			$canedit = haveRight("config", "w");
			echo "<form name='ruleactions_form' id='ruleactions_form' method='post' action=\"$target\">";
			echo "<div align='center'>"; 
			echo "<table class='tab_cadre_fixe'>";
			echo "<tr><th colspan='".($editable=="true"?" 4 ":"3")."'>" . $LANG["rulesengine"][7] . "</th></tr>";
			echo "<tr>";
			if ($editable)
				echo "<td class='tab_bg_2'></td>";
			echo "<td class='tab_bg_2'>".$LANG["rulesengine"][11]."</td>";
			echo "<td class='tab_bg_2'>".$LANG["rulesengine"][12]."</td>";
			echo "<td class='tab_bg_2'>".$LANG["rulesengine"][13]."</td>";
			echo "</tr>";
						
			foreach ($this->actions as $action)
				$action->showMinimalForm($this->description->fields["rule_type"],$editable);
				
		if ($editable && $canedit) {
		echo "<div align='center'>";
		echo "<table cellpadding='5' width='80%'>";
		echo "<tr><td><img src=\"" . $CFG_GLPI["root_doc"] . "/pics/arrow-left.png\" alt=''></td><td><a onclick= \"if ( markAllRows('entityaffectation_form') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?ID='".$this->description->fields["ID"]."'&amp;select=all'>" . $LANG["buttons"][18] . "</a></td>";

		echo "<td>/</td><td><a onclick= \"if ( unMarkAllRows('entityaffectation_form') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?ID='".$this->description->fields["ID"]."'&amp;select=none'>" . $LANG["buttons"][19] . "</a>";
		echo "</td><td align='left' width='80%'>";
		echo "<input type='submit' name='deleteuser' value=\"" . $LANG["buttons"][6] . "\" class='submit'>";
		echo "</td>";
		echo "</table>";

		echo "</div>";

	}
		else
		{	
			echo "</table>";
			echo "</div>";
			echo "</form>";
		}			}
	
	/**
	 * Display all rules actions
	 */
	function showCriteriasList($target,$editable)
	{
			global $CFG_GLPI, $LANG;
			
			$canedit = haveRight("config", "w");
			echo "<form name='entityaffectation_form' id='entityaffectation_form' method='post' action=\"$target\">";
			echo "<div align='center'>"; 
			echo "<table class='tab_cadre_fixe'>";
			echo "<tr><th colspan='".($editable=="true"?" 4 ":"3")."'>" . $LANG["rulesengine"][6] . "</th></tr>";
			echo "<tr>";
			if ($editable)
				echo "<td class='tab_bg_2'></td>";
			echo "<td class='tab_bg_2'>".$LANG["rulesengine"][16]."</td>";
			echo "<td class='tab_bg_2'>".$LANG["rulesengine"][14]."</td>";
			echo "<td class='tab_bg_2'>".$LANG["rulesengine"][15]."</td>";
			echo "</tr>";
			
			$maxsize = sizeof($this->criterias);
			$i=0;
			foreach ($this->criterias as $criteria)
			{
				if ($i != 0 && $i < $maxsize)
					echo "<tr><td class='tab_bg_2' colspan='".($editable=="true"?" 4 ":"3")."' align='center'>".$this->description->fields["match"]."</td></tr>";
				$criteria->showMinimalForm($this->description->fields["rule_type"],$editable);
				$i++;
			}
			
		if ($editable && $canedit) {
		echo "<div align='center'>";
		echo "<table cellpadding='5' width='80%'>";
		echo "<tr><td><img src=\"" . $CFG_GLPI["root_doc"] . "/pics/arrow-left.png\" alt=''></td><td><a onclick= \"if ( markAllRows('entityaffectation_form') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?ID='".$this->description->fields["ID"]."'&amp;select=all'>" . $LANG["buttons"][18] . "</a></td>";

		echo "<td>/</td><td><a onclick= \"if ( unMarkAllRows('entityaffectation_form') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?ID='".$this->description->fields["ID"]."'&amp;select=none'>" . $LANG["buttons"][19] . "</a>";
		echo "</td><td align='left' width='80%'>";
		echo "<input type='submit' name='deleteuser' value=\"" . $LANG["buttons"][6] . "\" class='submit'>";
		echo "</td>";
		echo "</table>";

		echo "</div>";

	}
		else
		{	
			echo "</table>";
			echo "</div>";
			echo "</form>";
		}		
	}	

	/**
	 * Try to match all criterias of a rule using the rules engine
	 */
	function processRule($informations)
	{
		$result=false;
		if (sizeof($this->criterias) > 0)
		{
		foreach ($this->criterias as $criteria)
		{
			$res = $criteria->processRule($informations,$this->description->fields["rule_type"]);
			
			//If AND -> one false and the rule is not matched
			if ($this->description->fields["match"] == AND_MATCHING)
				$result=$res;
			//If OR -> if this criteria return false but another criteria already return true -> let true
			elseif (($res == false && $result==true) || ($res == true)) 
				$result=true;
			//Put false
			else
				$result = false;		
		}
		return $result;
		}
		else
			return false; 
	}

	/**
	 * Function to be implemented to get the attribtes needed to process rule's matching
	 */
	function getRequestForAttributes($type)
	{	
		return array();
	}

	function processAllRules($rule_parameters)
	{
		return null;
	}

}
	
?>
