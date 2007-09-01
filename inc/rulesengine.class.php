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
if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

class RuleCollection {
	var $rule_list = array();
	var $rule_type;
	var $rule_class_name="Rule";
	var $stop_on_first_match=false;
	var $right="config";

	
	/**
	* Constructor
	* @param rule_type the rule type used for the collection
	*/
	function RuleCollection($rule_type){
		$this->rule_type = $rule_type;
	}


	/**
	* Get Collection Datas : retrieve descriptions and rules
	* @param $retrieve_criteria Retrieve the criterias of the rules ?
	* @param $retrieve_action Retrieve the action of the rules ?
	*/
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
	/**
	* Get title used in list of rules
	* @return Title of the rule collection
	*/	
	function getTitle(){
		global $LANG;
		return $LANG["rulesengine"][29];
	}

	/**
	* Show the list of rules
	* @param $target  
	* @return nothing
	*/
	function showForm($target){
		global $CFG_GLPI, $LANG;
			
		$canedit = haveRight($this->right, "w");
		$this->getCollectionDatas(0,0);
		echo "<form name='ruleactions_form' id='ruleactions_form' method='post' action=\"$target\">\n";
		echo "<div class='center'>"; 
		echo "<table class='tab_cadrehov'>";

		echo "<tr><th colspan='5'><div class='relative'><span><strong>" . $this->getTitle() . "</strong></span>";
		if ($canedit){
			echo "<span style='  position:absolute; right:0; margin-right:5px; font-size:10px;'><a href=\"".ereg_replace(".php",".form.php",$target)."\"><img src=\"".$CFG_GLPI["root_doc"]."/pics/plus.png\" alt='+' title='".$LANG["buttons"][8]."'></a></span>";
		}

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
			echo "<div class='center'>";
			echo "<table width='80%'>";
			
			echo "<tr><td><img src=\"" . $CFG_GLPI["root_doc"] . "/pics/arrow-left.png\" alt=''></td><td class='center'><a onclick= \"if ( markAllRows('entityaffectation_form') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?select=all'>" . $LANG["buttons"][18] . "</a></td>";

			echo "<td>/</td><td class='center'><a onclick= \"if ( unMarkAllRows('entityaffectation_form') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?select=none'>" . $LANG["buttons"][19] . "</a>";
			echo "</td><td align='left' width='80%'>";
			echo "<select name=\"massiveaction\" id='massiveaction'>";
			echo "<option value=\"-1\" selected>-----</option>";
			echo "<option value=\"delete\">".$LANG["buttons"][6]."</option>";
			echo "<option value=\"move_rule\">".$LANG["buttons"][20]."</option>";
			echo "</select>";

			$params=array('action'=>'__VALUE__',
					'type'=>RULE_TYPE,
					'rule_type'=>$this->rule_type,
					);
			
			ajaxUpdateItemOnSelectEvent("massiveaction","show_massiveaction",$CFG_GLPI["root_doc"]."/ajax/dropdownMassiveAction.php",$params);
		
			echo "<span id='show_massiveaction'>&nbsp;</span>\n";			

			echo "</td></tr>";
			
			echo "</table>";
			echo "</div>";
		} 
		echo "</form>";

	}

	/**
	* Complete reorder the rules
	*/
	function completeReorder()
	{
		global $DB;
		$rules = array();
		$i=0;
		$sql ="SELECT ID FROM glpi_rules_descriptions WHERE rule_type = '".$this->rule_type."' ORDER BY ranking ASC";
		
		if ($result = $DB->query($sql)){
			//Reorder rules : we reaffect ranking for each rule of type $type
			while ($data=$DB->fetch_array($result)){
				$sql = "UPDATE glpi_rules_descriptions SET ranking='".$i."' WHERE ID='".$data["ID"]."'";
				$DB->query($sql);				
				$i++;
			}
		}
	}

	/**
	* Modify rule's ranking and automatically reorder all rules
	* @param $ID the rule ID whose ranking must be modified
	* @param $action up or down
	*/
	function changeRuleOrder($ID,$action)
	{
		global $DB;
		//$sql ="SELECT ID FROM glpi_rules_descriptions WHERE rule_type =".$this->rule_type." ORDER BY ranking ASC";
		$sql ="SELECT ranking FROM glpi_rules_descriptions WHERE ID ='$ID'";
		if ($result = $DB->query($sql))
		if ($DB->numrows($result)==1){
			
			$current_rank=$DB->result($result,0,0);
			// Search rules to switch
			$sql2="";
			switch ($action){
				case "up":
					$sql2 ="SELECT ID,ranking FROM glpi_rules_descriptions WHERE rule_type ='".$this->rule_type."' AND ranking < '$current_rank' ORDER BY ranking DESC LIMIT 1";
				break;
				case "down":
					$sql2="SELECT ID,ranking FROM glpi_rules_descriptions WHERE rule_type ='".$this->rule_type."' AND ranking > '$current_rank' ORDER BY ranking ASC LIMIT 1";
				break;
				default :
					return false;
				break;
			}
			if ($result2 = $DB->query($sql2))
			if ($DB->numrows($result2)==1){
				list($other_ID,$new_rank)=$DB->fetch_array($result2);
				$query="UPDATE glpi_rules_descriptions SET ranking='$new_rank' WHERE ID ='$ID'";
				$query2="UPDATE glpi_rules_descriptions SET ranking='$current_rank' WHERE ID ='$other_ID'";
				return ($DB->query($query)&&$DB->query($query2));
			}
		}
		return false;
	}
	
	function deleteRuleOrder($ranking)
	{
		global $DB;
		$rules = array();
		$sql ="UPDATE glpi_rules_descriptions SET ranking=ranking-1 WHERE rule_type =".$this->rule_type." AND ranking > '$ranking' ";
		return $DB->query($sql);
	}
	
	function moveRule($ID,$ref_ID,$type='after')
	{
		global $DB;

		$ruleDescription = new Rule;
		$ruleDescription->getFromDB($ID);
		$old_rank=$ruleDescription->fields["ranking"];	
		$ruleDescription->getFromDB($ref_ID);
		$rank=$ruleDescription->fields["ranking"];	

		// Move items to replace new hole
		$query="UPDATE glpi_rules_descriptions SET ranking=ranking-1 
			WHERE rule_type ='".$this->rule_type."' 
				AND ranking > '$old_rank' ";
		$result = $DB->query($query);

		// Move if rank is more than $rank / UPDATE rule
		$query="UPDATE glpi_rules_descriptions SET ranking=ranking+1 
			WHERE rule_type ='".$this->rule_type."' 
				AND ranking ".($type=="after"?'>':'>=')." '$rank' ";
		$result = $DB->query($query);
		// Move rule
		if ($type=='after'){
			$rank++;
		}
		$query="UPDATE glpi_rules_descriptions SET ranking='$rank' 
			WHERE ID='$ID' ";
		$result = $DB->query($query);

		
	}
	/**
	* Process all the rules collection
	* @param $input the input data used to check criterias
	* @param $output the initial ouput array used to be manipulate by actions
	* @param $params parameters for all internal functions
	* @return the output array updated by actions
	*/
	function processAllRules($input=array(),$output=array(),$params=array())
	{	
		// Get Collection datas
		$this->getCollectionDatas(1,1);
		$input=$this->prepareInputDataForProcess($input,$params);
		
		if (count($this->rule_list)){

			foreach ($this->rule_list as $rule){
				$output["_rule_process"]=false;
				$output=$rule->process($input,$output,$params);
				if ($output["_rule_process"]&&$this->stop_on_first_match){
					unset($output["_rule_process"]);
					return $output;
				}
			}
		}
		return $output;
	}

	/**
	* Prepare input datas for the rules collection
	* @param $input the input data used to check criterias
	* @param $params parameters
	* @return the updated input datas
	*/
	function prepareInputDataForProcess($input,$params){
		return $input;
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
	// Rule type
	var $rule_type;
	var $right="config";
	var $can_sort;

	/**
	* Constructor
	* @param rule_type the rule type used for the collection
	*/
	function Rule($rule_type=0) {
		$this->table = "glpi_rules_descriptions";
		$this->type = -1;
		$this->rule_type=$rule_type;
		$this->can_sort=false;
	}

	function getTitleRule($target)
	{
	}
	
	function getTitle()
	{
		global $LANG;
		return $LANG["rulesengine"][8];	
	}
	
	/**
	* Show the rule
	* @param $target  
	* @param $ID ID of the rule  
	* @param $withtemplate 
	* @return nothing
	*/
	function showForm($target,$ID,$withtemplate=''){
			global $CFG_GLPI, $LANG;

			$canedit=haveRight($this->right,"w");

			$new=false;
			if (!empty($ID)&&$ID>0){
				$this->getRuleWithCriteriasAndActions($ID,1,1);
			} else {
				$this->getEmpty();
				$new=true;
			}
			
			$this->getTitleRule($target);

			$this->showOnglets($ID, $new,$_SESSION['glpi_onglet'],"rule_type='".$this->rule_type."'");
			echo "<form name='rule_form'  method='post' action=\"$target\">\n";

			echo "<div class='center'>"; 
			echo "<table class='tab_cadre_fixe'>";
			echo "<tr><th colspan='4'>" . $this->getTitle() . "</th></tr>";
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
			$this->dropdownRulesMatch("match",$this->fields["match"]);
			echo "</td>";
			
			echo "<td class='tab_bg_2' colspan='2'></td>";

			echo "</tr>";
			
			if ($canedit)
			{
				if ($new){
					echo "<tr><td class='tab_bg_2' align='center' colspan='4'>";
					echo "<input type='hidden' name='rule_type' value='".$this->rule_type."'>";
					echo "<input type='submit' name='add_rule' value=\"" . $LANG["buttons"][8] . "\" class='submit'>";
					echo "</td></tr>";

				} else {
					echo "<tr><td class='tab_bg_2' align='center' colspan='2'>";
					echo "<input type='hidden' name='ID' value='".$ID."'>";
					echo "<input type='hidden' name='ranking' value='".$this->fields["ranking"]."'>";
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
	* Display a dropdown with all the rule matching
	*/
	function dropdownRulesMatch($name,$value=''){
	
		$elements[AND_MATCHING] = AND_MATCHING;
		$elements[OR_MATCHING] = OR_MATCHING;
		return dropdownArrayValues($name,$elements,$value);
	}

	/**
	 * Get all criterias for a given rule
	 * @param $ID the rule_description ID
	 * @param $withcriterias 1 to retrieve all the criterias for a given rule
	 * @param $withactions  1 to retrive all the actions for a given rule
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
	
	function getTitleAction($target)
	{
	}

	function getTitleCriteria($target)
	{
	}

	/**
	 * Get maximum number of Actions of the Rule (0 = unlimited)
	* @return the maximum number of actions
	 */
	function maxActionsCount(){
		// Unlimited
		return 0;
	}
	/**
	 * Display all rules actions
	* @param $target  
	 */
	function showActionsList($target)
	{
		global $CFG_GLPI, $LANG;
			
		$canedit = haveRight($this->right, "w");
		
		$this->getTitleAction($target);	
		if (($this->maxActionsCount()==0 || sizeof($this->actions) < $this->maxActionsCount()) && $canedit){
			echo "<form name='actionsaddform' method='post' action=\"$target\">\n";
			$this->addActionForm();
			echo "</form>";
		}
		
		echo "<form name='actionsform' id='actionsform' method='post' action=\"$target\">\n";
				
		echo "<div class='center'>"; 
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
		echo "</table></div>";
				
		if ($canedit&&$nb>0) {
			echo "<div class='center'>";
			echo "<table  width='80%'>";
			echo "<tr><td><img src=\"" . $CFG_GLPI["root_doc"] . "/pics/arrow-left.png\" alt=''></td><td class='center'><a onclick= \"if ( markAllRows('actionsform') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?select=all'>" . $LANG["buttons"][18] . "</a></td>";

			echo "<td>/</td><td class='center'><a onclick= \"if ( unMarkAllRows('actionsform') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?select=none'>" . $LANG["buttons"][19] . "</a>";
			echo "</td><td align='left' width='80%'>";
			echo "<input type='submit' name='delete_action' value=\"" . $LANG["buttons"][6] . "\" class='submit'>";
			echo "</td></tr></table>";
			echo "</div>";
		} 			
		echo "</form>";

	}
	/**
	 * Display the add action form
	 */
	function addActionForm() {
		global $LANG,$CFG_GLPI;
		echo "<div class='center'>";
		echo "<table  class='tab_cadre_fixe'>";
		echo "<tr class='tab_bg_1'><th colspan='4'>" . $LANG["rulesengine"][7] . ":</tr>";
		echo "<tr  class='tab_bg_2' align='center'><td>";
		echo $LANG["rulesengine"][30] . ":";
		echo "</td><td>";
		$val=$this->dropdownActions();
		echo "</td><td align='left' width='500px'>";
		
		echo "<span id='action_span'>\n";
		$_POST["rule_type"]=$this->rule_type;
		$_POST["field"]=$val;
		include (GLPI_ROOT."/ajax/ruleaction.php");
		echo "</span>\n";	

		echo "</td><td>";
		echo "<input type=hidden name='FK_rules' value=\"" . $this->fields["ID"] . "\">";
		echo "<input type='submit' name='add_action' value=\"" . $LANG["buttons"][8] . "\" class='submit'>";
		echo "</td></tr>";

		echo "</table></div><br>";
	}

	/**
	 * Display the add criteria form
	 */
	function addCriteriaForm() {
		global $LANG,$CFG_GLPI,$RULES_CRITERIAS;
		echo "<div class='center'>";
		echo "<table  class='tab_cadre_fixe'>";
		echo "<tr class='tab_bg_1'><th colspan='4'>" . $LANG["rulesengine"][16] . ":</tr>";
		echo "<tr class='tab_bg_2' align='center'><td>";
		echo $LANG["rulesengine"][16] . ":";
		echo "</td><td>";
		$val=$this->dropdownCriterias();
		echo "</td><td align='left' width='500px'>";
		
		echo "<span id='criteria_span'>\n";
		$_POST["rule_type"]=$this->rule_type;
		$_POST["criteria"]=$val;
		include (GLPI_ROOT."/ajax/rulecriteria.php");
		echo "</span>\n";	

		echo "</td><td>";


			
		echo "<input type=hidden name='FK_rules' value=\"" . $this->fields["ID"] . "\">";
		echo "<input type='submit' name='add_criteria' value=\"" . $LANG["buttons"][8] . "\" class='submit'>";
		echo "</td></tr>";

		echo "</table></div><br>";
	}
	/**
	 * Get maximum number of criterias of the Rule (0 = unlimited)
	* @return the maximum number of criterias
	 */
	function maxCriteriasCount(){
		// Unlimited
		return 0;
	}
	
	/**
	 * Display all rules criterias
	 */
	function showCriteriasList($target)
	{
		global $CFG_GLPI, $LANG;
			
		$canedit = haveRight($this->right, "w");

		
		
		$this->getTitleCriteria($target);
		if (($this->maxCriteriasCount()==0 || sizeof($this->criterias) < $this->maxCriteriasCount()) && $canedit){
			echo "<form name='criteriasaddform'method='post' action=\"$target\">\n";
			$this->addCriteriaForm();
			echo "</form>";	
		}
		
		echo "<form name='criteriasform' id='criteriasform' method='post' action=\"$target\">\n";
		echo "<div class='center'>"; 
		echo "<table class='tab_cadrehov'>";
		echo "<tr><th colspan='".($canedit?" 4 ":"3")."'>" . $LANG["rulesengine"][6] . "</th></tr>\n";
		echo "<tr>";
		if ($canedit){
			echo "<td class='tab_bg_2'>&nbsp;</td>";
		}
		echo "<td class='tab_bg_2'>".$LANG["rulesengine"][16]."</td>\n";
		echo "<td class='tab_bg_2'>".$LANG["rulesengine"][14]."</td>\n";
		echo "<td class='tab_bg_2'>".$LANG["rulesengine"][15]."</td>\n";
		echo "</tr>";
			
		$maxsize = sizeof($this->criterias);
		foreach ($this->criterias as $criteria){
			$this->showMinimalCriteriaForm($criteria->fields,$canedit);
		}
		echo "</table></div>";
		if ($canedit&&$maxsize>0) {
			echo "<div class='center'>\n";
			echo "<table width='80%'>\n";
			echo "<tr><td><img src=\"" . $CFG_GLPI["root_doc"] . "/pics/arrow-left.png\" alt=''></td><td class='center'><a onclick= \"if ( markAllRows('criteriasform') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?ID=".$this->fields["ID"]."&amp;select=all'>" . $LANG["buttons"][18] . "</a></td>";

			echo "<td>/</td><td class='center'><a onclick= \"if ( unMarkAllRows('criteriasform') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?ID=".$this->fields["ID"]."&amp;select=none'>" . $LANG["buttons"][19] . "</a>";
			echo "</td><td align='left' width='80%'>";
			echo "<input type='submit' name='delete_criteria' value=\"" . $LANG["buttons"][6] . "\" class='submit'>";
			echo "</td></tr>";
			echo "</table>";
			echo "</div>";
		} 		
		echo "</form>";

	}	

	/**
	 * Display the dropdown of the criterias for the rule
	 */
	function dropdownCriterias(){
		global $CFG_GLPI;
		$items=array();
		foreach ($this->getCriterias() as $ID => $crit){
			$items[$ID]=$crit['name'];
		}
		$rand=dropdownArrayValues("criteria", $items);

		$params=array('criteria'=>'__VALUE__',
				'rule_type'=>$this->rule_type,
		);
		ajaxUpdateItemOnSelectEvent("dropdown_criteria$rand","criteria_span",$CFG_GLPI["root_doc"]."/ajax/rulecriteria.php",$params,false);
		ajaxUpdateItem("criteria_span",$CFG_GLPI["root_doc"]."/ajax/rulecriteria.php",$params,false,"dropdown_criteria$rand");

		return key($items);
	}
	/**
	 * Display the dropdown of the actions for the rule
	 */
	function dropdownActions(){
		global $CFG_GLPI;
		$items=array();
		foreach ($this->getActions() as $ID => $act){
			$items[$ID]=$act['name'];
		}

		$rand=dropdownArrayValues("field", $items);
		$params=array('field'=>'__VALUE__',
				'rule_type'=>$this->rule_type,
		);
		ajaxUpdateItemOnSelectEvent("dropdown_field$rand","action_span",$CFG_GLPI["root_doc"]."/ajax/ruleaction.php",$params,false);
		ajaxUpdateItem("action_span",$CFG_GLPI["root_doc"]."/ajax/ruleaction.php",$params,false,"dropdown_field$rand");
	}

	/**
	 * Get the criterias array definition
	 * @return the criterias array
	 */
	function getCriterias(){
		global $RULES_CRITERIAS;
		if (isset($RULES_CRITERIAS[$this->rule_type])){
			return $RULES_CRITERIAS[$this->rule_type];
		} else {
			return array();
		}
	}

	/**
	 * Get the actions array definition
	 * @return the actions array
	 */
	function getActions(){
		global $RULES_ACTIONS;
		if (isset($RULES_ACTIONS[$this->rule_type])){
			return $this->filterActions($RULES_ACTIONS[$this->rule_type]);
		} else {
			return array();
		}
	}

	/**
	 * Filter actions if needed
	*  @param $actions the actions array
	 * @return the filtered actions array
	 */
	function filterActions($actions){
		return $actions;
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
	 * @param $ID the criteria's ID
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
	 * @param $ID the action's ID
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
	* Process the rule
	* @param $input the input data used to check criterias
	* @param $output the initial ouput array used to be manipulate by actions
	* @param $params parameters for all internal functions
	* @return the output array updated by actions. If rule matched add field _rule_process to return value
	*/
	function process($input,$output,$params)
	{
		if (count($this->criterias))	{
			$input=$this->prepareInputDataForProcess($input,$params);
			$results=array();
			foreach ($this->criterias as $criteria){

				// Undefine criteria field : set to blank
				if (!isset($input[$criteria->fields["criteria"]])){
					$input[$criteria->fields["criteria"]]='';
				}
				
				//If the value is not an array
				if (!is_array($input[$criteria->fields["criteria"]])){
					$value=$this->getCriteriaValueToMatch($criteria->fields["criteria"],$criteria->fields["condition"],$input[$criteria->fields["criteria"]]);
					$results[] = matchRules($value,$criteria->fields["condition"],$criteria->fields["pattern"]);
				} else	{
					//If the value if, in fact, an array of values
					// Negative condition : Need to match all condition (never be)
					if (in_array($criteria->fields["condition"],array(PATTERN_IS_NOT,PATTERN_NOT_CONTAIN,REGEX_NOT_MATCH))){
						$res = true;
						foreach($input[$criteria->fields["criteria"]] as $tmp){
							$value=$this->getCriteriaValueToMatch($criteria->fields["criteria"],$criteria->fields["condition"],$tmp);
							$res &= matchRules($value,$criteria->fields["condition"],$criteria->fields["pattern"]);
						}
	
						$results[] = $res;	

					// Positive condition : Need to match one
					 } else {
						$res = false;
						foreach($input[$criteria->fields["criteria"]] as $tmp){
							$value=$this->getCriteriaValueToMatch($criteria->fields["criteria"],$criteria->fields["condition"],$tmp);

							$res |= matchRules($value,$criteria->fields["condition"],$criteria->fields["pattern"]);
						}
	
						$results[] = $res;	
					}
				}	
			}
			
			if (count($results)){
				$doactions=false;
				if ($this->fields["match"]==AND_MATCHING){
					$doactions=true;
					foreach ($results as $res){
						$doactions&=$res;
					}
				} else { // OR MATCHING
					$doactions=false;
					foreach ($results as $res){
						$doactions|=$res;
					}

				}

				if ($doactions){
					$output=$this->executeActions($output,$params);
					
					//Hook
					$hook_params["rule_type"]=$this->rule_type;
					$hook_params["ruleid"]=$this->fields["ID"];
					$hook_params["input"]=$input;
					$hook_params["output"]=$output;
					
					doHook("rule_matched",$hook_params);
					$output["_rule_process"]=true;
				}
			}
			
		}
		return $output; 
	}

	/**
	* Specific prepare input datas for the rule
	* @param $input the input data used to check criterias
	* @param $params parameters
	* @return the updated input datas
	*/
	function prepareInputDataForProcess($input,$params){
		return $input;
	}

	/**
	* Execute the actions as defined in the rule
	* @param $output the fields to manipulate
	* @param $params parameters
	* @return the $output array modified
	*/
	function executeActions($output,$params)
	{
		if (count($this->actions)){
			foreach ($this->actions as $action){
				switch ($action->fields["action_type"]){
					case "assign" :
						$output[$action->fields["field"]] = $action->fields["value"];
					break;
				}
			}
		}

		return $output;
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

	/**
	 * Show the minimal form for the rule
	* @param $target link to the form page
	* @param $first is it the first rule ?
	* @param $last is it the last rule ?
	 */
	function showMinimalForm($target,$first=false,$last=false){
		global $LANG,$CFG_GLPI;
			
		$canedit = haveRight($this->right,"w");
			
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
		else
			echo "<td></td>";
				
		echo "<td><a href=\"".ereg_replace(".php",".form.php",$target)."?ID=".$this->fields["ID"]."&amp;onglet=1\">" . $this->fields["name"] . "</a></td>";
					
		echo "<td>".$this->fields["description"]."</td>";
		if ($this->can_sort && !$first && $canedit){
			echo "<td><a href=\"".$target."?type=".$this->fields["rule_type"]."&amp;action=up&amp;ID=".$this->fields["ID"]."\"><img src=\"".$CFG_GLPI["root_doc"]."/pics/deplier_up.png\" alt=''></a></td>";
		} else {
			echo "<td>&nbsp;</td>";
		}
		if ($this->can_sort && !$last && $canedit){
			echo "<td><a href=\"".$target."?type=".$this->fields["rule_type"]."&amp;action=down&amp;ID=".$this->fields["ID"]."\"><img src=\"".$CFG_GLPI["root_doc"]."/pics/deplier_down.png\" alt=''></a></td>";
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
		$input["ranking"] = $this->getNextRanking();
		return $input;
	}

	/**
	 * Get the next ranking for a specified rule
	 */
	function getNextRanking()
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

	/**
	 * Show the minimal form for the action rule
	* @param $fields datas used to display the action
	* @param $canedit can edit the actions rule ?
	 */	
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

	/**
	 * Show the minimal form for the criteria rule
	* @param $fields datas used to display the criteria
	* @param $canedit can edit the criterias rule ?
	 */	
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
	/**
	 * Show the minimal infos for the criteria rule
	* @param $fields datas used to display the criteria
	 */	
	function showMinimalCriteria($fields){
		echo "<td>" . $this->getCriteriaName($fields["criteria"]) . "</td>";
		echo "<td>" . getConditionByID($fields["condition"]) . "</td>";
		echo "<td>" . $this->getCriteriaPatternDisplay($fields["criteria"],$fields["condition"],$fields["pattern"]) . "</td>";
	}	
	/**
	 * Show the minimal infos for the action rule
	* @param $fields datas used to display the action
	* @param $canedit right to edit ?
	 */
	function showMinimalAction($fields,$canedit)
	{
		echo "<td>" . $this->getActionName($fields["field"]) . "</td>";
		echo "<td>" . getActionByID($fields["action_type"]) . "</td>";
		echo "<td>" . $this->getActionValue($fields["field"],$fields["value"]) . "</td>";
		
	}	
	
	/**
 	* Return a value associated with a pattern associated to a criteria to display it
 	* @param $ID the given criteria
        * @param $condition condition used
 	* @param $pattern the pattern
 	*/
 	function getCriteriaPatternDisplay($ID,$condition,$pattern)
	{
		$crit=$this->getCriteria($ID);
		
		if (!isset($crit['type'])){
			return $pattern;
		} else {
			switch ($crit['type']){
				case "dropdown":
					if ($condition==PATTERN_IS||$condition==PATTERN_IS_NOT){
						return getDropdownName($crit["table"],$pattern);
					}
					break;
				case "dropdown_users":
					if ($condition==PATTERN_IS||$condition==PATTERN_IS_NOT){
						return getUserName($pattern);
					}
					break;
				case "dropdown_request_type":
					if ($condition==PATTERN_IS||v==PATTERN_IS_NOT){
						return getRequestTypeName($pattern);
					}
					break;
				case "dropdown_priority":
					if ($condition==PATTERN_IS||$condition==PATTERN_IS_NOT){
						return getPriorityName($pattern);
					} 
					break;
			}
			return $pattern;
		}
	}

	/**
 	* Return a value associated with a pattern associated to a criteria to compare it
 	* @param $ID the given criteria
        * @param $condition condition used
 	* @param $initValue the pattern
 	*/
 	function getCriteriaValueToMatch($ID,$condition,$initValue)
	{
		$crit=$this->getCriteria($ID);
		
		if (empty($crit['type'])){
			return $initValue;
		} else {
			
			switch ($crit['type']){
				case "dropdown":
					if ($condition!=PATTERN_IS&&$condition!=PATTERN_IS_NOT){
						return getDropdownName($crit["table"],$initValue);
					}
					break;
				case "dropdown_users":
					if ($condition!=PATTERN_IS&&$condition!=PATTERN_IS_NOT){
						return getUserName($initValue);
					}
					break;
				case "dropdown_request_type":
					if ($condition!=PATTERN_IS&&$condition!=PATTERN_IS_NOT){
						return getRequestTypeName($initValue);
					}
					break;
				case "dropdown_priority":
					if ($condition!=PATTERN_IS&&$condition!=PATTERN_IS_NOT){
						return getPriorityName($initValue);
					} 
					break;
			}
		}
		return $initValue;
	}

	/**
 	* Return a value associated with a pattern associated to a criteria
 	* @param $ID the given action
 	* @param $value the value
 	*/
 	function getActionValue($ID,$value)
	{
		global $LANG;
		$action=$this->getAction($ID);
		
		if (!isset($action['type'])){
			return $value;
		} else {
			
			switch ($action['type']){
				case "dropdown":
					return getDropdownName($action["table"],$value);
					break;
				case "dropdown_assign":
				case "dropdown_users":
					return getUserName($value);
					break;
				case "yesno":
					if ($value) 
						return $LANG["choice"][1];
					else
						return $LANG["choice"][0];	
					break;
				case "dropdown_priority":
					return getPriorityName($value);
					break;
				default :
					return $value;
					break;
			}
		}
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
		while ($rule = $DB->fetch_assoc($result))
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
			echo "<form name='entityaffectation_form' id='entityaffectation_form' method='post' action=\"$target\">\n";
			echo "<div class='center'>"; 
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
