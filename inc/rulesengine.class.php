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

// ----------------------------------------------------------------------
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------
if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

class RuleCollection {
	var $rule_list = array();
	var $rule_type;
	var $rule_class_name="Rule";
	
	function RuleCollection($rule_type){
		global $DB;
		$this->rule_type = $rule_type;
	}


	function getCollectionDatas($retrieve_criteria=0,$retrieve_action=0){
		global $DB;
		//Select all the rules of a different type
		$sql = "SELECT ID FROM glpi_rules_descriptions WHERE rule_type=".$this->rule_type." ORDER by ranking ASC";
		 $result = $DB->query($sql);
		if ($result){
		 	while ($rule=$DB->fetch_array($result)) {
			 	//For each rule, get a Rule object with all the criterias and actions
				$tempRule= new $this->rule_class_name();
				$tempRule->getRuleWithCriteriasAndActions($rule["ID"],$retrieve_criteria,$retrieve_action);
			
				//Add the object to the list of rules
				$this->rule_list[] = $tempRule; 	
			}
		}
	}

	function title($addbutton=false) {
		global $LANG, $CFG_GLPI;
	
		$buttons = array ();
		displayTitle($CFG_GLPI["root_doc"] . "/pics/computer.png", $LANG["Menu"][0], $LANG["rulesengine"][8], $buttons);
	}

	function getTitle(){
		global $LANG;
		return $LANG["rulesengine"][29];
	}

	function showForm($target){
		global $CFG_GLPI, $LANG;
			
		$canedit = haveRight("config", "w");
		$this->getCollectionDatas(0,0);
		echo "<form name='ruleactions_form' id='ruleactions_form' method='post' action=\"$target\">";
		echo "<div align='center'>"; 
		echo "<table class='tab_cadrehov'>";

		echo "<tr><th colspan='6'><div style='position: relative'><span><strong>" . $this->getTitle() . "</strong></span>";
		echo "<span style='  position:absolute; right:0; margin-right:5px; font-size:10px;'><a href=\"".ereg_replace(".php",".form.php",$target)."\"><img src=\"".$CFG_GLPI["root_doc"]."/pics/plus.png\" alt='+' title='".$LANG["buttons"][8]."'></a></span>";

		echo "</div></th></tr>";
		echo "<tr>";
		echo "<td class='tab_bg_2'></td>";
		echo "<td class='tab_bg_2'>".$LANG["common"][16]."</td>";
		echo "<td class='tab_bg_2'>".$LANG["joblist"][6]."</td>";
		echo "<td class='tab_bg_2' colspan='2'></td>";
		echo "</tr>";
		
		$i=0;
		$nb=count($this->rule_list);
		foreach ($this->rule_list as $rule){
			$rule->showMinimalForm($target,$i==0,$i==$nb-1);
			$i++;
		}
		echo "</table>";
		echo "</div>";
		if ($canedit&&$nb>0) {
			echo "<div align='center'>";
			echo "<table cellpadding='5' width='80%'>";
			echo "<tr><td><img src=\"" . $CFG_GLPI["root_doc"] . "/pics/arrow-left.png\" alt=''></td><td><a onclick= \"if ( markAllRows('entityaffectation_form') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?select=all'>" . $LANG["buttons"][18] . "</a></td>";

			echo "<td>/</td><td><a onclick= \"if ( unMarkAllRows('entityaffectation_form') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?select=none'>" . $LANG["buttons"][19] . "</a>";
			echo "</td><td align='left' width='80%'>";
			echo "<input type='submit' name='deleterule' value=\"" . $LANG["buttons"][6] . "\" class='submit'>";
			echo "</td>";
			echo "</table>";
			echo "</div>";
		} 
		echo "</form>";

	}

	/**
	* Modify rule's ranking and automatically reorder all rules
	* @param ID the rule ID whose ranking must be modified
	* @param action up and down
	*/
	function changeRuleOrder($ID,$action)
	{
		global $DB;
		$rules = array();
		$sql ="SELECT ID FROM glpi_rules_descriptions WHERE rule_type =".$this->rule_type." ORDER BY ranking ASC";
		$result = $DB->query($sql);
		$i=0;
		//Reorder rules : we reaffect ranking for each rule of type $type
		for ($i=0;$rule = $DB->fetch_array($result);$i++){
			if ($rule["ID"] == $ID){
				//If action is up and if the rule is not the fist of the list
				if ($action == "up" && $i > 0){
					$rules[$i] = $rules[$i-1];
					$rules[$i-1] = $ID;
				} elseif ($action == "down" && $i < $DB->numrows($result)){
				//If action is down and if not the last 
					$rules[$i] = $rules[$i+1];
					$rules[$i+1] = $ID;
				} else {
					$rules[$i]=$rule["ID"];
				}
			} else {
				$rules[$i]=$rule["ID"];	
			}
		}
		
		for ($i=0; $i < sizeof($rules);$i++){
			$sql = "UPDATE glpi_rules_descriptions SET ranking='".$i."' WHERE ID='".$rules[$i]."'";
			$DB->query($sql);				
		}
	}
}

/**
 * Rule class store all informations about a GLPI rule :
 *   - description
 *   - criterias
 *   - actions
*/
class Rule extends CommonDBTM{

	//Actions affected to this rule
	var $actions = array();

	//Criterias affected to this rule
	var $criterias = array();

	//Store the rule that matched the criterias
	var $matched_rule;
	
	function Rule() {
		$this->table = "glpi_rules_descriptions";
		$this->type = -1;
	}

	function showForm($target,$ID,$withtemplate=''){
			global $CFG_GLPI, $LANG;

			$canedit=haveRight("config","w");

			$new=false;
			if (!empty($ID)&&$ID>0){
				$this->getRuleWithCriteriasAndActions($ID,1,1);
			} else {
				$this->getEmpty();
				$new=true;
			}

			$this->showOnglets($ID, $new,$_SESSION['glpi_onglet'],"rule_type='".$this->rule_type."'");
			echo "<form name='rule_form'  method='post' action=\"$target\">";

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
			

			echo "<td class='tab_bg_2'>" . $LANG["rulesengine"][10] . "</td>";
			echo "<td class='tab_bg_2'>" . (isset($this->fields["ranking"])?$this->fields["ranking"]:"") . "</td>";

			echo "</tr>";
			
			if ($canedit)
			{
				if ($new){
					echo "<tr><td class='tab_bg_2' align='center' colspan='4'>";
					echo "<input type='hidden' name='rule_type' value='".$this->rule_type."''";
					echo "<input type='submit' name='add_rule' value=\"" . $LANG["buttons"][8] . "\" class='submit'>";
					echo "</tr>";

				} else {
					echo "<tr><td class='tab_bg_2' align='center' colspan='2'>";
					echo "<input type='hidden' name='ID' value='".$ID."''";
					echo "<input type='submit' name='update_rule' value=\"" . $LANG["buttons"][7] . "\" class='submit'></td>";
					echo "<td class='tab_bg_2' align='center' colspan='2'>";
					echo "<input type='submit' name='delete_rule' value=\"" . $LANG["buttons"][6] . "\" class='submit'></td>";
					echo "</tr>";
				}
			}
			
			echo "</table>";

			echo "</div></form>";
	}

	/**
	 * Get all criterias for a given rule
	 * @param $ID the rule_description ID
	 * @param withcriterias 1 to retrieve all the criterias for a given rule
	 * @param withaction  1 to retrive all the actions for a given rule
	 */
	function getRuleWithCriteriasAndActions($ID, $withcriterias = 0, $withactions = 0) {
		if ($ID == ""){
			$this->getEmpty();
		} else {
			$this->getFromDB($ID);
		
			if ($withactions){
				$RuleAction = new RuleAction;
				$this->actions = $RuleAction->getRuleActions($ID);
			}	
			if ($withcriterias){
				$RuleCriterias = new RuleCriteria;
				$this->criterias = $RuleCriterias->getRuleCriterias($ID);
			}
		}
	}
	
	/**
	 * Print a good title for computer pages
	 *@return nothing (diplays)
	 *
	 **/
	function title($addbutton=false) {
		global $LANG, $CFG_GLPI;

		$buttons = array ();
		displayTitle($CFG_GLPI["root_doc"] . "/pics/computer.png", $LANG["Menu"][0], $LANG["rulesengine"][8], $buttons);
	}


	function maxActionsCount(){
		// Unlimited
		return 0;
	}
	/**
	 * Display all rules actions
	 */
	function showActionsList($target)
	{
		global $CFG_GLPI, $LANG;
			
		$canedit = haveRight("config", "w");
		echo "<form name='actionsform' id='actionsform' method='post' action=\"$target\">";
			
		if (($this->maxActionsCount()==0 || sizeof($this->actions) < $this->maxActionsCount()) && $canedit){
			$this->addActionForm();
		}
				
		echo "<div align='center'>"; 
		echo "<table class='tab_cadrehov'>";
		echo "<tr><th colspan='".($canedit?" 4 ":"3")."'>" . $LANG["rulesengine"][7] . "</th></tr>";
		echo "<tr  class='tab_bg_2'>";
		if ($canedit){
			echo "<td class='tab_bg_2'>&nbsp;</td>";
		}
		echo "<td class='tab_bg_2'>".$LANG["rulesengine"][12]."</td>";
		echo "<td class='tab_bg_2'>".$LANG["rulesengine"][11]."</td>";
		echo "<td class='tab_bg_2'>".$LANG["rulesengine"][13]."</td>";
		echo "</tr>";

		$nb=count($this->actions);
		foreach ($this->actions as $action){
			$this->showMinimalActionForm($action->fields,$canedit);
		}
				
		if ($canedit&&$nb>0) {
			echo "<div align='center'>";
			echo "<table cellpadding='5' width='80%'>";
			echo "<tr><td><img src=\"" . $CFG_GLPI["root_doc"] . "/pics/arrow-left.png\" alt=''></td><td><a onclick= \"if ( markAllRows('actionsform') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?ID='".$this->fields["ID"]."'&amp;select=all'>" . $LANG["buttons"][18] . "</a></td>";

			echo "<td>/</td><td><a onclick= \"if ( unMarkAllRows('actionsform') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?ID='".$this->fields["ID"]."'&amp;select=none'>" . $LANG["buttons"][19] . "</a>";
			echo "</td><td align='left' width='80%'>";
			echo "<input type='submit' name='delete_action' value=\"" . $LANG["buttons"][6] . "\" class='submit'>";
			echo "</td>";
			echo "</table>";

			echo "</div>";

		} else 	{	
			echo "</table>";
			echo "</div>";
			echo "</form>";
		}			
	}

	function addActionForm() {
		global $LANG,$CFG_GLPI;
		echo "<div align='center'>";
		echo "<table  class='tab_cadre_fixe'>";
		echo "<tr class='tab_bg_1'><th colspan='5'>" . $LANG["rulesengine"][7] . ":</tr>";
		echo "<tr  class='tab_bg_2' align='center'><td>";
		echo $LANG["rulesengine"][30] . ":";
		echo "</td><td>";
		$this->dropdownActionFields();
		echo "</td><td>";

		echo "<script type='text/javascript' >\n";
		echo "   new Form.Element.Observer('criteria', 1, \n";
		echo "      function(element, value) {\n";
		echo "      	new Ajax.Updater('criteria_span','".$CFG_GLPI["root_doc"]."/ajax/ruleaction.php',{asynchronous:true, evalScripts:true, \n";
		echo "           method:'post', parameters:'field='+value+'&rule_type=".$this->rule_type."'\n";
		echo "})})\n";
		echo "</script>\n";
		
		echo "<span id='criteria_span'>\n";
		$_POST["rule_type"]=$this->rule_type;
		include (GLPI_ROOT."/ajax/ruleaction.php");
		echo "</span>\n";	

/*
		dropdownRulesActions("action_type");
		echo $LANG["rulesengine"][13] . ":";
		dropdownValue("glpi_entities", "value");
*/
		echo "</td><td>";
		echo "<input type=hidden name='FK_rules' value=\"" . $this->fields["ID"] . "\">";
		echo "<input type='submit' name='add_action' value=\"" . $LANG["buttons"][8] . "\" class='submit'>";
		echo "</td></tr>";

		echo "</table></div><br>";
	}


	function addCriteriaForm() {
		global $LANG,$CFG_GLPI,$RULES_CRITERIAS;
		echo "<div align='center'>";
		echo "<table  class='tab_cadre_fixe'>";
		echo "<tr class='tab_bg_1'><th colspan='5'>" . $LANG["rulesengine"][16] . ":</tr>";
		echo "<tr class='tab_bg_2' align='center'><td>";
		echo $LANG["rulesengine"][16] . ":";
		echo "</td><td>";
		$this->dropdownCriterias();
		echo "</td><td>";

		echo "<script type='text/javascript' >\n";
		echo "   new Form.Element.Observer('criteria', 1, \n";
		echo "      function(element, value) {\n";
		echo "      	new Ajax.Updater('criteria_span','".$CFG_GLPI["root_doc"]."/ajax/rulecriteria.php',{asynchronous:true, evalScripts:true, \n";
		echo "           method:'post', parameters:'criteria='+value+'&rule_type=".$this->rule_type."'\n";
		echo "})})\n";
		echo "</script>\n";
		
		echo "<span id='criteria_span'>\n";
		$_POST["rule_type"]=$this->rule_type;
		include (GLPI_ROOT."/ajax/rulecriteria.php");
		echo "</span>\n";	

		echo "</td><td>";


			
		echo "<input type=hidden name='FK_rules' value=\"" . $this->fields["ID"] . "\">";
		echo "<input type='submit' name='add_criteria' value=\"" . $LANG["buttons"][8] . "\" class='submit'>";
		echo "</td></tr>";

		echo "</table></div><br>";
	}

	function maxCriteriasCount(){
		// Unlimited
		return 0;
	}
	
	/**
	 * Display all rules actions
	 */
	function showCriteriasList($target)
	{
		global $CFG_GLPI, $LANG;
			
		$canedit = haveRight("config", "w");

		echo "<form name='criteriasform' id='criteriasform' method='post' action=\"$target\">";
		if (($this->maxCriteriasCount()==0 || sizeof($this->criterias) < $this->maxCriteriasCount()) && $canedit){
			$this->addCriteriaForm();
		}
			
		echo "<div align='center'>"; 
		echo "<table class='tab_cadrehov'>";
		echo "<tr><th colspan='".($canedit?" 4 ":"3")."'>" . $LANG["rulesengine"][6] . "</th></tr>";
		echo "<tr>";
		if ($canedit){
			echo "<td class='tab_bg_2'>&nbsp;</td>";
		}
		echo "<td class='tab_bg_2'>".$LANG["rulesengine"][16]."</td>";
		echo "<td class='tab_bg_2'>".$LANG["rulesengine"][14]."</td>";
		echo "<td class='tab_bg_2'>".$LANG["rulesengine"][15]."</td>";
		echo "</tr>";
			
		$maxsize = sizeof($this->criterias);
		foreach ($this->criterias as $criteria){
			$this->showMinimalCriteriaForm($criteria->fields,$canedit);
		}
			
		if ($canedit&&$maxsize>0) {
			echo "<div align='center'>";
			echo "<table cellpadding='5' width='80%'>";
			echo "<tr><td><img src=\"" . $CFG_GLPI["root_doc"] . "/pics/arrow-left.png\" alt=''></td><td><a onclick= \"if ( markAllRows('criteriasform') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?ID='".$this->fields["ID"]."'&amp;select=all'>" . $LANG["buttons"][18] . "</a></td>";

			echo "<td>/</td><td><a onclick= \"if ( unMarkAllRows('criteriasform') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?ID='".$this->fields["ID"]."'&amp;select=none'>" . $LANG["buttons"][19] . "</a>";
			echo "</td><td align='left' width='80%'>";
			echo "<input type='submit' name='delete_criteria' value=\"" . $LANG["buttons"][6] . "\" class='submit'>";
			echo "</td>";
			echo "</table>";

			echo "</div>";
		} else {	
			echo "</table>";
			echo "</div>";
			echo "</form>";
		}		
	}	


	function dropdownCriterias(){
		$items=array();
		foreach ($this->getCriterias() as $ID => $crit){
			$items[$ID]=$crit['name'];
		}
		dropdownArrayValues("criteria", $items);
		// Force set item to default value on reload
		echo "<script type='text/javascript' >\n";
		echo "document.getElementById('criteria').value='".key($items)."';";
		echo "</script>\n";

	}

	function dropdownActionFields(){
		$items=array();
		foreach ($this->getActions() as $ID => $act){
			$items[$ID]=$act['name'];
		}

		dropdownArrayValues("field", $items);
		// Force set item to default value on reload
		echo "<script type='text/javascript' >\n";
		echo "document.getElementById('action').value='".key($items)."';";
		echo "</script>\n";

	}

	function getCriterias(){
		global $RULES_CRITERIAS;
		if (isset($RULES_CRITERIAS[$this->rule_type])){
			return $RULES_CRITERIAS[$this->rule_type];
		} else {
			return array();
		}
	}


	function getActions(){
		global $RULES_ACTIONS;
		if (isset($RULES_ACTIONS[$this->rule_type])){
			return $RULES_ACTIONS[$this->rule_type];
		} else {
			return array();
		}
	}

	/**
	 * Get a criteria description by his ID
	 * @param $ID the criteria's ID
	 * @return the criteria array
	 */
	function getCriteria($ID)
	{
		$criterias=$this->getCriterias();
		if (isset($criterias[$ID])){
			return $criterias[$ID];
		} else {
			return array();
		}
	}
	/**
	 * Get a action description by his ID
	 * @param $ID the action's ID
	 * @return the action array
	 */
	function getAction($ID)
	{
		$actions=$this->getActions();
		if (isset($actions[$ID])){
			return $actions[$ID];
		} else {
			return array();
		}
	}
	/**
	 * Get a criteria description by his ID
	 * @param the criteria's ID
	 * @return the criteria's description
	 */
	function getCriteriaName($ID)
	{
		$criteria=$this->getCriteria($ID);
		if (isset($criteria['name'])){
			return $criteria['name'];
		} else {
			return "&nbsp;";
		}
	}

	/**
	 * Get a action description by his ID
	 * @param the action's ID
	 * @return the action's description
	 */
	function getActionName($ID)
	{
		$action=$this->getAction($ID);
		if (isset($action['name'])){
			return $action['name'];
		} else {
			return "&nbsp;";
		}
	}
	
	/**
	 * Try to match all criterias of a rule using the rules engine
	 */
	function processRule($informations)
	{
		// MOYO : pourquoi ne pas faire directement : CHECK Critères ET ACTIONS si règle valide ?
		$result=false;
		if (sizeof($this->criterias) > 0)
		{
			foreach ($this->criterias as $criteria)
			{

				// process Criteria
				//Get all the informations about the condition
				$criteria_informations = $this->getCriteria($criteria->fields["criteria"]);

				$res=false;
				if (isset($informations[$criteria_informations["field"]])){
					$res = matchRules($informations[$criteria_informations["field"]],$criteria->fields["condition"],$criteria->fields["pattern"]);
				}

				//If AND -> one false and the rule is not matched
				if ($this->fields["match"] == AND_MATCHING)
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
	 * Function to be implemented to get the attributes needed to process rule's matching
	 */
	function getRequestForAttributes($type)
	{	
		// MOYO jamais appelé dans ce fichier -> surement pas à etre ici : utile que dans des cas précis
		return array();
	}

	function processAllRules($rule_parameters)
	{		
		// MOYO : plutot dans RuleCollection : appels successif a tous les processRule
		return null;
	}

	/**
	 * Delete a rule and all associated criterias and actions
	 */
	function cleanDBonPurge($ID)
	{
		global $DB;
		$sql = "DELETE FROM glpi_rules_actions WHERE FK_Rules=".$ID;
		$DB->query($sql);
		
		$sql = "DELETE FROM glpi_rules_criterias WHERE FK_Rules=".$ID;
		$DB->query($sql);
	}
	
	function defineOnglets($withtemplate=''){
		global $LANG;

		$ong[1]=$LANG["title"][26];
		
		return $ong;
	}

	function showMinimalForm($target,$first=false,$last=false){
		global $LANG,$CFG_GLPI;
			
		$canedit = haveRight("config","w");
			
		echo "<tr class='tab_bg_1'>";
				
		if ($canedit) {
			echo "<td width='10'>";
			$sel = "";
			if (isset ($_GET["select"]) && $_GET["select"] == "all"){
				$sel = "checked";
			}
			echo "<input type='checkbox' name='item[" . $this->fields["ID"] . "]' value='1' $sel>";
			echo "</td>";
		}
			
		if ($canedit){
			echo "<td><a href=\"".ereg_replace(".php",".form.php",$target)."?ID=".$this->fields["ID"]."\">" . $this->fields["name"] . "</a></td>";
		} else{
			echo "<td>".$this->fields["name"] . "</td>";
		}
					
		echo "<td>".$this->fields["description"]."</td>";
		if (!$first){
			echo "<td><a href=\"".$target."?type=".$this->fields["rule_type"]."&action=up&ID=".$this->fields["ID"]."\"><img src=\"".$CFG_GLPI["root_doc"]."/pics/deplier_up.png\"></a></td>";
		} else {
			echo "<td>&nbsp;</td>";
		}
		if (!$last){
			echo "<td><a href=\"".$target."?type=".$this->fields["rule_type"]."&action=down&ID=".$this->fields["ID"]."\"><img src=\"".$CFG_GLPI["root_doc"]."/pics/deplier_down.png\"></a></td>";
		} else {
			echo "<td>&nbsp;</td>";
		}

		echo "</tr>";
	}

	/**
	 * Before adding, add the ranking of the new rule
	 */
	function prepareInputForAdd($input)
	{
		$input["ranking"] = $this->getNextRanking($input["rule_type"]);
		return $input;
	}

	/**
	 * Get the next ranking for a specified rule
	 */
	function getNextRanking($type)
	{
		global $DB;
		$sql = "SELECT max(ranking) as rank FROM glpi_rules_descriptions WHERE rule_type=".$this->rule_type;
		$result = $DB->query($sql);
		if ($DB->numrows($result) > 0)
		{
			$datas = $DB->fetch_assoc($result);
			return $datas["rank"] + 1;
		} else {
			return 0;
		}
	}

	
	function showMinimalActionForm($fields,$canedit)
	{
		echo "<tr class='tab_bg_1'>";
				
		if ($canedit) {
			echo "<td width='10'>";
			$sel = "";
			if (isset ($_GET["select"]) && $_GET["select"] == "all"){
				$sel = "checked";
			}
			echo "<input type='checkbox' name='item[" . $fields["ID"] . "]' value='1' $sel>";
			echo "</td>";
		}
			
		$this->showMinimalAction($fields,$canedit);
		echo "</tr>";
	}


	function showMinimalCriteriaForm($fields,$canedit)
	{
		echo "<tr class='tab_bg_1'>";
				
		if ($canedit) {
			echo "<td width='10'>";
			$sel = "";
			if (isset ($_GET["select"]) && $_GET["select"] == "all"){
				$sel = "checked";
			}
			echo "<input type='checkbox' name='item[" . $fields["ID"] . "]' value='1' $sel>";
			echo "</td>";
		}
		$this->showMinimalCriteria($fields);	
		echo "</tr>";
	}

	function showMinimalCriteria($fields){
		echo "<td>" . $this->getCriteriaName($fields["criteria"]) . "</td>";
		echo "<td>" . getConditionByID($fields["condition"]) . "</td>";
		echo "<td>" . $this->getCriteriaPatternValue($fields["criteria"],$fields["pattern"]) . "</td>";
	}	

	function showMinimalAction($fields,$canedit)
	{
		echo "<td>" . $this->getActionName($fields["field"]) . "</td>";
		echo "<td>" . getActionByID($fields["action_type"]) . "</td>";
		echo "<td>" . $this->getActionValue($fields["field"],$fields["value"]) . "</td>";
		
	}	
	
	function addValueForm($fields,$canedit)
	{
		// MOYO ca sert a quoi ?
		// Ya pas moyen de le rendre générique ?
	}

	/**
 	* Return a value associated with a pattern associated to a criteria
 	* @param $ID the given criteria
 	* @param $pattern the pattern
 	*/
 	function getCriteriaPatternValue($ID,$pattern)
	{
		$crit=$this->getCriteria($ID);
		
		if (!isset($crit['type'])){
			return $pattern;
		} else {
			
			switch ($crit['type']){
				case "dropdown":
					return getDropdownName($crit["table"],$pattern);
					break;
				default :
					return $pattern;
					break;
			}
		}
	}

	/**
 	* Return a value associated with a pattern associated to a criteria
 	* @param $ID the given criteria
 	* @param $pattern the pattern
 	*/
 	function getActionValue($ID,$value)
	{
		$action=$this->getAction($ID);
		
		if (!isset($action['type'])){
			return $value;
		} else {
			
			switch ($action['type']){
				case "dropdown":
					return getDropdownName($action["table"],$value);
					break;
				default :
					return $value;
					break;
			}
		}
	}

/**
 * Execute the actions as defined in the rule
 * @param fields the fields to manipulate
 * @return the fields modified
 */
	function executeActions($fields)
	{
		// MOYO Ya pas moyen de rendre ca générique comme le check des critères ?
		return $fields;
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


	/**
	 * Add an action
	 */
	function addActionByAttributes($action,$ruleid,$field,$value)
	{
		$ruleAction = new RuleAction;
		$input["action_type"]=$action;
		$input["field"]=$field;
		$input["value"]=$value;
		$input["FK_rules"]=$ruleid;
		$ruleAction->add($input);
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
	

}
	
?>
