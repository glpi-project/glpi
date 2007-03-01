
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

	function showForm($ID)
	{
		
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
	 * @return an array of rules_actions
	 */
	function getRuleActions($ID) {
		$sql = "SELECT * FROM glpi_rules_actions WHERE FK_rules=" . $ID;
		global $DB;

		$rules_actions = array ();
		$result = $DB->query($sql);
		while ($rule = $DB->fetch_array($result))
			$rules_actions[] = $rule;

		return $rules_actions;
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
	* @return an array of rules_criterias
	*/
	function getRuleCriterias($ID) {
		global $DB;
		$sql = "SELECT * FROM glpi_rules_criterias WHERE FK_rules=" . $ID;

		$rules_list = array ();
		$result = $DB->query($sql);
		while ($rule = $DB->fetch_array($result))
			$rules_list[] = $rule;

		return $rules_list;
	}

}

/**
 * Rule class store all informations about a GLPI rule :
 *   - description
 *   - criterias
 *   - actions
 */
class Rule {

	//Rule description
	var $description;
	//Actions affected to this rule
	var $actions;

	//Criterias affected to this rule
	var $criterias;

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
			$this->criterias = $RuleCriterias->getRuleCriteria($ID);
		}
	}

}

class RuleCollection {
	var $rule_list = array();
	
	function RuleCollection($rule_type)
	{
		global $DB;
		//Select all the rules of a different type
		$sql = "SELECT ID FROM glpi_rules_descriptions WHERe rule_type=".$rule_type;
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
	
	function defineOnglets($withtemplate){
		global $LANG;

		$ong[1]=$LANG["title"][26];
		$ong[2]=$LANG["rulesengine"][6];
		$ong[3]=$LANG["rulesengine"][7];
		return $ong;
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

	function showForm($ID,$withtemplate)
	{
		$this->showOnglets($ID, $withtemplate,$_SESSION['glpi_onglet']);
		
	}
}
?>
