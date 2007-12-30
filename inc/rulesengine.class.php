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


class SingletonRuleList {
	var $list;
	var $load;	
	
	function SingletonRuleList () {
		$this->list = array();
		$this->load = 0;
	}
	 
}

/**
 * get a unique instance of a SingletonRuleList for a type of RuleCollection
 * 
 * Not member of SingletonRuleList because PHP 5 need 'static function'
 * 
 * @param $type of the Rule listed
 * @return unique instance of an object
 */
function &getInstanceOfSingletonRuleList($type) {
	static $instances = array();
	
	if (!isset($instances[$type])) {
		$instances[$type] = new SingletonRuleList();
		// echo("++ getInstance($type) : created\n");
	}
	// else	echo("++ getInstance($type) : reused\n");
	
	return $instances[$type];
}

class RuleCollection {
	var $rule_type;
	var $rule_class_name="Rule";
	var $stop_on_first_match=false;
	var $right="config";
	var $orderby="ranking";
	var $use_output_rule_process_as_next_input=false;
	
	var $can_replay_rules=false;
	var $RuleList=NULL;
	
	/**
	* Constructor
	* @param rule_type the rule type used for the collection
	**/
	function RuleCollection($rule_type){
		$this->rule_type = $rule_type;
		$this->cache_table="";	
		$this->cache_params = array();
		$this->can_replay_rules = false;
	}


	/**
	* Get Collection Datas : retrieve descriptions and rules
	* @param $retrieve_criteria Retrieve the criterias of the rules ?
	* @param $retrieve_action Retrieve the action of the rules ?
	**/
	function getCollectionDatas($retrieve_criteria=0,$retrieve_action=0){
		global $DB;
		
		if ($this->RuleList === NULL)
			$this->RuleList = getInstanceOfSingletonRuleList($this->rule_type);
			
		$need = 1+($retrieve_criteria?2:0)+($retrieve_action?4:0);

		// check if load required
		if (($need & $this->RuleList->load) != $need) {

			//Select all the rules of a different type
			$sql = "SELECT ID FROM glpi_rules_descriptions WHERE rule_type=".$this->rule_type." ORDER by ".$this->orderby." ASC";
			 $result = $DB->query($sql);
			if ($result){
				$this->RuleList->list = array();
			 	while ($rule=$DB->fetch_array($result)) {
				 	//For each rule, get a Rule object with all the criterias and actions
					$tempRule= new $this->rule_class_name();
					if ($tempRule->getRuleWithCriteriasAndActions($rule["ID"],$retrieve_criteria,$retrieve_action)){
						//Add the object to the list of rules
						$this->RuleList->list[] = $tempRule;
					}
				}
				$this->RuleList->load = $need;
			}
			// echo "+ getCollectionDatas(".$this->rule_class_name.",$retrieve_criteria,$retrieve_action)\t=> " . count($this->RuleList->list) . "\n";
		}
		// else echo "- getCollectionDatas(".$this->rule_class_name.",$retrieve_criteria,$retrieve_action)\t=> " . count($this->RuleList->list) . "\n";
	}

	/**
	 * Is a confirmation needed before replay on DB ?
	 * If needed need to send 'replay_confirm' in POST 
	 * @param $target filename : where to go when done
	 * @return  true if confirmtion is needed, else false
	**/
	function warningBeforeReplayRulesOnExistingDB($target){
		return false;
	}

	/**
	 * Replay Collection on DB
	 * @param $items array containg items to replay. If empty -> all
	 * @param $params additional parameters if needed
	**/
	function replayRulesOnExistingDB($items=array(),$params=array()){

	}

	/**
	* Get title used in list of rules
	* @return Title of the rule collection
	**/	
	function getTitle(){
		global $LANG;
		return $LANG["rulesengine"][29];
	}

	/**
	* Show the list of rules
	* @param $target  
	* @return nothing
	**/
	function showForm($target){
		global $CFG_GLPI, $LANG;
			
		$canedit = haveRight($this->right, "w");

		//Display informations about the how the rules engine process the rules
		if ($this->stop_on_first_match){
			//The engine stop on the first matched rule
			echo "<span class='center'><strong>".$LANG["rulesengine"][120]."</strong></span><br>";
		} else {
			//The engine process all the rules
			echo "<span class='center'><strong>".$LANG["rulesengine"][121]."</strong></span><br>";
		}
		if ($this->use_output_rule_process_as_next_input){
			//The engine keep the result of a rule to be processed further
			echo "<span class='center'><strong>".$LANG["rulesengine"][122]."</strong></span><br>";
		}
		$this->getCollectionDatas(0,0);
		echo "<br><form name='ruleactions_form' id='ruleactions_form' method='post' action=\"$target\">\n";
		echo "<div class='center'>"; 
		echo "<table class='tab_cadrehov'>";

		echo "<tr><th colspan='6'><div class='relative'><span><strong>" . $this->getTitle() . "</strong></span>";
		if ($canedit){
			echo "<span style='  position:absolute; right:0; margin-right:5px; font-size:10px;'><a href=\"".ereg_replace(".php",".form.php",$target)."\"><img src=\"".$CFG_GLPI["root_doc"]."/pics/plus.png\" alt='+' title='".$LANG["buttons"][8]."'></a></span>";
		}

		echo "</div></th></tr>";
		echo "<tr>";
		echo "<td class='tab_bg_2'></td>";
		echo "<td class='tab_bg_2'>".$LANG["common"][16]."</td>";
		echo "<td class='tab_bg_2'>".$LANG["joblist"][6]."</td>";
		echo "<td class='tab_bg_2'>".$LANG["common"][60]."</td>";
		echo "<td class='tab_bg_2' colspan='2'></td>";
		echo "</tr>";
		
		$i=0;
		$nb=count($this->RuleList->list);
		foreach ($this->RuleList->list as $rule){
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
			if ($this->orderby=="ranking"){
				echo "<option value=\"move_rule\">".$LANG["buttons"][20]."</option>";
			}
			echo "<option value=\"activate_rule\">".$LANG["rulesengine"][46]."</option>";
			echo "</select>";

			$params=array('action'=>'__VALUE__',
					'type'=>RULE_TYPE,
					'rule_type'=>$this->rule_type,
					);
			
			ajaxUpdateItemOnSelectEvent("massiveaction","show_massiveaction",$CFG_GLPI["root_doc"]."/ajax/dropdownMassiveAction.php",$params);
		
			echo "<span id='show_massiveaction'>&nbsp;</span>\n";			

			echo "</td>";
			if ($this->can_replay_rules){
				echo "<td><input type='submit' name='replay_rule' value=\"" . $LANG["rulesengine"][76] . "\" class='submit'></td>";
			}
			echo "</tr>";
			
			echo "</table>";
			echo "</div>";
		} 
		
		echo "<span class='center'><a href='#' onClick=\"window.open('".$CFG_GLPI["root_doc"]."/front/popup.php?popup=test_all_rules&amp;rule_type=".$this->rule_type."&amp' ,'glpipopup', 'height=400, width=1000, top=100, left=100, scrollbars=yes' )\">".$LANG["rulesengine"][84]."</a></span>"; 
		echo "</form>";


		$this->showAdditionalInformationsInForm($target);

	}

	/**
	* Show the list of rules
	* @param $target  
	* @return nothing
	**/
	function showAdditionalInformationsInForm($target){
	}	

	/**
	* Complete reorder the rules
	**/
	function completeReorder(){
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
	**/
	function changeRuleOrder($ID,$action){
		global $DB;
		//$sql ="SELECT ID FROM glpi_rules_descriptions WHERE rule_type =".$this->rule_type." ORDER BY ranking ASC";
		$sql ="SELECT ranking FROM glpi_rules_descriptions WHERE ID ='$ID'";
		if ($result = $DB->query($sql)){
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
				if ($result2 = $DB->query($sql2)){
					if ($DB->numrows($result2)==1){
						list($other_ID,$new_rank)=$DB->fetch_array($result2);
						$query="UPDATE glpi_rules_descriptions SET ranking='$new_rank' WHERE ID ='$ID'";
						$query2="UPDATE glpi_rules_descriptions SET ranking='$current_rank' WHERE ID ='$other_ID'";
						return ($DB->query($query)&&$DB->query($query2));
					}
				}
			}
			return false;
		}
	}
	
	function deleteRuleOrder($ranking){
		global $DB;
		$rules = array();
		$sql ="UPDATE glpi_rules_descriptions SET ranking=ranking-1 WHERE rule_type =".$this->rule_type." AND ranking > '$ranking' ";
		return $DB->query($sql);
	}
	
	function moveRule($ID,$ref_ID,$type='after'){
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
	* @param input the input data used to check criterias
	* @param output the initial ouput array used to be manipulate by actions
	* @param params parameters for all internal functions
	* @return the output array updated by actions

	**/
	function processAllRules($input=array(),$output=array(),$params=array()){	
		
		// Get Collection datas
		$this->getCollectionDatas(1,1);
		$input=$this->prepareInputDataForProcess($input,$params);
		
		if (count($this->RuleList->list)){

			foreach ($this->RuleList->list as $rule){
				//If the rule is active, process it
				if ($rule->fields["active"]){
					$output["_rule_process"]=false;
					$rule->process($input,$output,$params);
					if ($output["_rule_process"]&&$this->stop_on_first_match){
						unset($output["_rule_process"]);
						$output["_ruleid"]=$rule->fields["ID"];
						return $output;
					}
				}
				if ($this->use_output_rule_process_as_next_input){
					$input=$output;
				}
			}
		}
		return $output;
	}

	function showRulesEngineCriteriasForm($target,$rule_type,$values){

		global $DB, $LANG,$RULES_CRITERIAS,$RULES_ACTIONS; 

		$input = $this->prepareInputDataForTestProcess();

		if (count($input)){
			echo "<form name='testrule_form' id='testrulesengine_form' method='post' action=\"$target\">\n";
			echo "<div class='center'>";
			echo "<table class='tab_cadre_fixe'>"; 
			echo "<tr><th colspan='2'>" . $LANG["rulesengine"][6] . "</th></tr>"; 
			
			 //Brower all criterias 
			foreach ($input as $criteria){
				echo "<tr class='tab_bg_1'>"; 

				if (isset($RULES_CRITERIAS[$rule_type][$criteria])){
					$criteria_constants = $RULES_CRITERIAS[$rule_type][$criteria];
					echo "<td>".$criteria_constants["name"].":</td>";
				}else{
					echo "<td>".$criteria.":</td>";
				}
				echo "<td>";

				$rule = getRuleClass($this->rule_type);
				$rule->displayCriteriaSelectPattern($criteria,$criteria,PATTERN_IS,isset($values[$criteria])?$values[$criteria]:'');
				echo "</td>";
				echo "</tr>"; 
			}
			$rule->showSpecificCriteriasForPreview($_POST);

			echo "<tr><td class='tab_bg_2' colspan='2' align='center'>"; 
			echo "<input type='submit' name='test_all_rules' value=\"" . $LANG["buttons"][50] . "\" class='submit'>";
			echo "<input type='hidden' name='rule_type' value=\"" . $this->rule_type . "\">"; 
			echo "</td></tr>"; 
			echo "</table>";
			echo "</div>";
			echo "</form>";
		}		
		return $input;
	}


	/**
	* Test all the rules collection
	* @param input the input data used to check criterias
	* @param output the initial ouput array used to be manipulate by actions
	* @param params parameters for all internal functions
	* @return the output array updated by actions
	**/
	function testAllRules($input=array(),$output=array(),$params=array()){	
		// Get Collection datas
		$this->getCollectionDatas(1,1);
		
		if (count($this->RuleList->list)){

			foreach ($this->RuleList->list as $rule){
				//If the rule is active, process it
				if ($rule->fields["active"]){
					$output["_rule_process"]=false;
					$output["result"][$rule->fields["ID"]]["ID"]=$rule->fields["ID"];
					
					$rule->process($input,$output,$params);
					if ($output["_rule_process"]&&$this->stop_on_first_match){
						unset($output["_rule_process"]);
						$output["result"][$rule->fields["ID"]]["result"]=1;
						$output["_ruleid"]=$rule->fields["ID"];
						return $output;
					}elseif ($output["_rule_process"]){
						$output["result"][$rule->fields["ID"]]["result"]=1;
					}else{
						$output["result"][$rule->fields["ID"]]["result"]=0;
					}
				}else{
					//Rule is inactive
					$output["result"][$rule->fields["ID"]]["result"]=2;
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
	**/
	function prepareInputDataForProcess($input,$params){
		return $input;
	}

	/**
	* Prepare input datas for the rules collection
	* @return the updated input datas
	**/
	function prepareInputDataForTestProcess(){
		global $DB;
		$input = array();
		
		$res = $DB->query("SELECT DISTINCT grc.criteria as criteria FROM glpi_rules_criterias as grc, glpi_rules_descriptions grd WHERE grc.FK_rules=grd.ID AND grd.rule_type=".$this->rule_type);
		while ($data = $DB->fetch_array($res))
			$input[]=$data["criteria"];
		return $input;
	}	

	function showRulesEnginePreviewResultsForm($target,$input){
		global $LANG,$RULES_ACTIONS;
		$output = array();

		$output = $this->testAllRules($input,array(),array());
		$rule = getRuleClass($this->rule_type);

		echo "<div class='center'>"; 
		
		if (isset($output["result"])){
			echo "<table class='tab_cadrehov'>";
			echo "<tr><th colspan='4'>" . $LANG["rulesengine"][82] . "</th></tr>";

			foreach ($output["result"] as $ID=>$rule_result){
				echo "<tr  class='tab_bg_2'>";
				$rule->getFromDB($ID);
				echo "<td class='tab_bg_2'>";
				echo $rule->fields["name"];
				echo "</td>";
				
				echo "<td class='tab_bg_2'>";
				switch ($rule_result["result"]){
					case 0 : 
						echo "<strong>".$LANG["choice"][0]."</strong>";
					break;
					case 1 : 
						echo "<strong>".$LANG["choice"][1]."</strong>";
					break;
					case 2 : 
						echo "<strong>".$LANG["rulesengine"][107]."</strong>";
					break;
					
				}
				echo "</td>";
				echo "</tr>";
			}
			echo "</table>";
		}
		
		$output = $this->cleanTestOutputCriterias($output);
		unset($output["result"]);			

		$global_result =(count($output)?1:0);
		
		echo "<br><table class='tab_cadrehov'>";
		$this->showTestResults($rule,$output,$global_result);
		echo "</table></div>";
		
	}
	
	/**
	 * Unset criterias from the rule's ouput results
	 **/
	function cleanTestOutputCriterias($output){
		//If output array contains keys begining with _ : drop it
		foreach($output as $criteria => $value){
			if ($criteria[0]=='_'){
				unset($output[$criteria]);
			}
		}
		return $output;			
	}
	
	function showTestResults($rule,$output,$global_result){
		global $LANG,$RULES_ACTIONS;
		echo "<tr><th colspan='4'>" . $LANG["rulesengine"][81] . "</th></tr>";
		echo "<tr  class='tab_bg_2'>";
		echo "<td class='tab_bg_2' colspan='4' align='center'>".$LANG["rulesengine"][41]." : <strong> ".getYesNo($global_result)."</strong></td>";

		foreach ($output as $criteria => $value){
			echo "<tr  class='tab_bg_2'>";
			echo "<td class='tab_bg_2'>";
			echo $RULES_ACTIONS[$this->rule_type][$criteria]["name"];
			echo "</td>";
			echo "<td class='tab_bg_2'>";
			echo $rule->getActionValue($criteria,$value);
			echo "</td>";
			echo "</tr>";
		}
		echo "</tr>";
	}
}

/**
 * Rule class store all informations about a GLPI rule :
 *   - description
 *   - criterias
 *   - actions
**/
class Rule extends CommonDBTM{

	//Actions affected to this rule
	var $actions = array();

	//Criterias affected to this rule
	var $criterias = array();
	// Rule type
	var $rule_type;
	var $right="config";
	var $can_sort;
	var $orderby="ranking";

	/**
	* Constructor
	* @param rule_type the rule type used for the collection
	**/
	function Rule($rule_type=0) {
		$this->table = "glpi_rules_descriptions";
		$this->type = -1;
		$this->rule_type=$rule_type;
		$this->can_sort=false;
	}

	function post_getEmpty () {
		$this->fields['active']=1;
	}

	function getTitleRule($target){
	}
	
	function getTitle(){
		global $LANG;
		return $LANG["rulesengine"][8];	
	}
	
	/**
	* Show the rule
	* @param $target  
	* @param $ID ID of the rule  
	* @param $withtemplate 
	* @return nothing
	**/
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

		$this->showOnglets($ID, $new,$_SESSION['glpi_onglet'],"rule_type='".$this->rule_type."'",$this->orderby);
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
		echo "</td></tr>";

		echo "<tr>";
		echo "<td class='tab_bg_2'>".$LANG["rulesengine"][9]."</td>";
		echo "<td class='tab_bg_2'>";
		$this->dropdownRulesMatch("match",$this->fields["match"]);
		echo "</td>";
			
		echo "<td class='tab_bg_2'>".$LANG["common"][60]."</td>";
		echo "<td class='tab_bg_2'>";
		dropdownYesNo("active",$this->fields["active"]);
		echo"</td></tr>";
			
		if ($canedit){
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

				echo "<tr><td class='tab_bg_2' align='center' colspan='4'>";
				echo "<a href='#' onClick=\"window.open('".$CFG_GLPI["root_doc"]."/front/popup.php?popup=test_rule&amp;rule_type=".$this->rule_type."&amp;rule_id=".$this->fields["ID"]."' ,'glpipopup', 'height=400, width=1000, top=100, left=100, scrollbars=yes' )\">".$LANG["buttons"][50]."</a>"; 
				echo "</td></tr>";
			}
		}			
		echo "</table></div></form>";
	}

	/**
	* Display a dropdown with all the rule matching
	**/
	function dropdownRulesMatch($name,$value=''){
		global $LANG;

		$elements[AND_MATCHING] = $LANG["rulesengine"][42];
		$elements[OR_MATCHING] = $LANG["rulesengine"][43];
		return dropdownArrayValues($name,$elements,$value);
	}

	/**
	 * Get all criterias for a given rule
	 * @param $ID the rule_description ID
	 * @param $withcriterias 1 to retrieve all the criterias for a given rule
	 * @param $withactions  1 to retrive all the actions for a given rule
	 **/
	function getRuleWithCriteriasAndActions($ID, $withcriterias = 0, $withactions = 0) {
		if ($ID == ""){
			return $this->getEmpty();
		} else {
			if ($ret=$this->getFromDB($ID)){
				if ($withactions){
					$RuleAction = new RuleAction;
					$this->actions = $RuleAction->getRuleActions($ID);
				}	
				if ($withcriterias){
					$RuleCriterias = new RuleCriteria;
					$this->criterias = $RuleCriterias->getRuleCriterias($ID);
				}
				return true;
			}
		}
		return false;
	}
	
	function getTitleAction($target){
	}

	function getTitleCriteria($target){
	}

	/**
	 * Get maximum number of Actions of the Rule (0 = unlimited)
	 * @return the maximum number of actions
	**/
	function maxActionsCount(){
		// Unlimited
		return 0;
	}

	/**
	 * Display all rules actions
	 * @param $target  
	 * @param $rule_id  
	**/
	function showActionsList($target,$rule_id){
		global $CFG_GLPI, $LANG;
			
		$canedit = haveRight($this->right, "w");
		
		$this->getTitleAction($target);	

		if (($this->maxActionsCount()==0 || sizeof($this->actions) < $this->maxActionsCount()) && $canedit){
			echo "<form name='actionsaddform' method='post' action=\"$target\">\n";
			$this->addActionForm($rule_id);
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
			echo "<input type='hidden' name='rule_id' value='" . $rule_id . "'>";
			echo "</td></tr></table>";
			echo "</div>";
		} 			
		echo "</form>";

	}

	/**
	 * Display the add action form
	**/
	function addActionForm($rule_id) {
		global $LANG,$CFG_GLPI;
		echo "<div class='center'>";
		echo "<table  class='tab_cadre_fixe'>";
		echo "<tr class='tab_bg_1'><th colspan='4'>" . $LANG["rulesengine"][7] . ":</tr>";
		echo "<tr  class='tab_bg_2' align='center'><td>";
		echo $LANG["rulesengine"][30] . ":";
		echo "</td><td>";
		$val=$this->dropdownActions(getAlreadyUsedActionsByRuleID($rule_id,$this->rule_type));
		echo "</td><td align='left' width='500px'>";
		echo "<span id='action_span'>\n";
		$_POST["rule_type"]=$this->rule_type;
		$_POST["field"]=$val;
		include (GLPI_ROOT."/ajax/ruleaction.php");
		echo "</span>\n";	

		echo "</td><td>";
		echo "<input type=hidden name='FK_rules' value=\"" . $this->fields["ID"] . "\">";
		echo "<input type='submit' name='add_action' value=\"" . $LANG["buttons"][8] . "\" class='submit'>";
		echo "<input type='hidden' name='rule_id' value='" . $rule_id . "'>";
		
		echo "</td></tr>";

		echo "</table></div><br>";
	}

	/**
	 * Display the add criteria form
	 */
	function addCriteriaForm($rule_id) {
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
		echo "<input type='hidden' name='rule_id' value='" . $rule_id . "'>";
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
	function showCriteriasList($target,$rule_id)
	{
		global $CFG_GLPI, $LANG;
			
		$canedit = haveRight($this->right, "w");

		
		
		$this->getTitleCriteria($target);
		if (($this->maxCriteriasCount()==0 || sizeof($this->criterias) < $this->maxCriteriasCount()) && $canedit){
			echo "<form name='criteriasaddform'method='post' action=\"$target\">\n";
			$this->addCriteriaForm($rule_id);
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
			echo "<input type='hidden' name='rule_id' value='" . $rule_id . "'>";
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
	function dropdownActions($used=array()){
		global $CFG_GLPI;

		$items=array();
		foreach ($this->getActions() as $ID => $act){
				$items[$ID]=$act['name'];
		}

		$rand=dropdownArrayValues("field", $items,'',$used);
			$params=array('field'=>'__VALUE__',
					'rule_type'=>$this->rule_type
			);
			ajaxUpdateItemOnSelectEvent("dropdown_field$rand","action_span",$CFG_GLPI["root_doc"]."/ajax/ruleaction.php",$params,false);
			ajaxUpdateItem("action_span",$CFG_GLPI["root_doc"]."/ajax/ruleaction.php",$params,false,"dropdown_field$rand");
	
	}

	/**
	 * Get the criterias array definition
	 * @return the criterias array
	**/
	
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
	**/
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
	**/
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
	**/
	
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
	* @param $getvalue
	* @return the output array updated by actions. If rule matched add field _rule_process to return value
	*/
	function process(&$input,&$output,&$params,$getvalue=true)
	{
		if (count($this->criterias))	
		{
			$regex_result=array();
			
			$input=$this->prepareInputDataForProcess($input,$params);

 			if ($this->checkCriterias($input,$regex_result,$getvalue)){
				$output=$this->executeActions($output,$params,$regex_result);
	
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

	/**
	 * Check criterias
	 * @param $input the input data used to check criterias
	 * @param $regex_result
	 * @param $getvalue
	 * @return boolean if criterias match
	**/
	function checkCriterias($input,&$regex_result,$getvalue=true){
		$doactions=false;
		reset($this->criterias);
		if ($this->fields["match"]==AND_MATCHING){
			$doactions=true;			
			foreach ($this->criterias as $criteria){
				$doactions &= $this->checkCriteria($criteria,$input,$regex_result,$getvalue);
				if (!$doactions) break;
			}
		} else { // OR MATCHING
			$doactions=false;
			foreach ($this->criterias as $criteria){
				$doactions |= $this->checkCriteria($criteria,$input,$regex_result,$getvalue);
				if ($doactions) break;
			}
		}
		return $doactions;
	}

	/**
	* Check criterias
	* @param $input the input data used to check criterias
	* @param $regex_result
	* @param $check_results
	* @return boolean if criterias match
	*/
	function testCriterias($input,&$regex_result,&$check_results){
		reset($this->criterias);
		
		foreach ($this->criterias as $criteria){
			$result = $this->checkCriteria($criteria,$input,$regex_result,false);
			$check_results[$criteria->fields["ID"]]["name"]=$criteria->fields["criteria"];
			$check_results[$criteria->fields["ID"]]["value"]=$criteria->fields["pattern"];
			$check_results[$criteria->fields["ID"]]["result"]=((!$result)?0:1);
			$check_results[$criteria->fields["ID"]]["ID"]=$criteria->fields["ID"];
		}
	}

	/**
	 * Process a criteria of a rule
	 * @param $criteria criteria to check
	 * @param $input the input data used to check criterias
	 * @param $regex_result
	 * @param $get_value
	**/
	function checkCriteria(&$criteria,&$input,&$regex_result,$get_value=true)
	{
		// Undefine criteria field : set to blank
		if (!isset($input[$criteria->fields["criteria"]])){
			$input[$criteria->fields["criteria"]]='';
		}
		//If the value is not an array
		if (!is_array($input[$criteria->fields["criteria"]])){
//			if ($get_value)
				$value=$this->getCriteriaValue($criteria->fields["criteria"],$criteria->fields["condition"],$input[$criteria->fields["criteria"]]);
//			else
//			$value = $input[$criteria->fields["criteria"]];

			// TODO Store value in temp array : $criteria->fields["criteria"] / $criteria->fields["condition"] -> value
			// TODO : Clean on update action

			$res = matchRules($value,$criteria->fields["condition"],$criteria->fields["pattern"],$regex_result);
		} else	{
			//If the value if, in fact, an array of values
			// Negative condition : Need to match all condition (never be)
			if (in_array($criteria->fields["condition"],array(PATTERN_IS_NOT,PATTERN_NOT_CONTAIN,REGEX_NOT_MATCH))){
				$res = true;
				foreach($input[$criteria->fields["criteria"]] as $tmp){
//					if ($get_value)
						$value=$this->getCriteriaValue($criteria->fields["criteria"],$criteria->fields["condition"],$tmp);
//					else
//						$value = $tmp;	
					$res &= matchRules($value,$criteria->fields["condition"],$criteria->fields["pattern"],$regex_result);
					if (!$res) break;
				}
		
			// Positive condition : Need to match one
			 } else {
				$res = false;
				foreach($input[$criteria->fields["criteria"]] as $tmp){
//					if ($get_value)
						$value=$this->getCriteriaValue($criteria->fields["criteria"],$criteria->fields["condition"],$tmp);
//					else
//						$value = $tmp;	
					$res |= matchRules($value,$criteria->fields["condition"],$criteria->fields["pattern"],$regex_result);
					if ($res) break;
				}
	
			}
		}
		return $res;	
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
	* @param $regex_results 
	* @return the $output array modified
	*/
	function executeActions($output,$params,$regex_results)
	{
		if (count($this->actions)){
			foreach ($this->actions as $action){
				switch ($action->fields["action_type"]){
					case "assign" :
						$output[$action->fields["field"]] = $action->fields["value"];
					break;
					case "ignore":
						$output["ignore"] = $action->fields["value"];
					case "regex_result":
					case "append_regex_result":
						//Regex result : assign value from the regex
						//Append regex result : append result from a regex
						if ($action->fields["action_type"] == "append_regex_result")
							$res=(isset($params[$action->fields["field"]])?$params[$action->fields["field"]]:"");
						else
							$res="";	
							
						$res .= getRegexResultById($action->fields["value"],$regex_results);
						if ($res != null) 
							$output[$action->fields["field"]]=$res;
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
		if ($this->fields["active"])
			echo "<td>".$LANG["choice"][1]."</td>";
		else
			echo "<td>".$LANG["choice"][0]."</td>";
				
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

	function showRulePreviewResultsForm($target,$input,$params)
	{
		global $LANG,$RULES_ACTIONS;
		$regex_results = array();
		$check_results = array();
		$output = array();
		
		//Test all criterias, without stopping at the first good one
		$this->testCriterias($input,$regex_results,$check_results);

		//Process the rule
		$this->process($input,$output,$params,false);

		$criteria = new RuleCriteria;

		echo "<div class='center'>"; 
		echo "<table class='tab_cadrehov'>";
		echo "<tr><th colspan='4'>" . $LANG["rulesengine"][82] . "</th></tr>";
		
		echo "<tr class='tab_bg_2'>";
		$criteria->showForm('','',-1,true);			
		echo "<td class='tab_bg_2'>".$LANG["rulesengine"][41]."</td>";
		echo "</tr>";

		foreach ($check_results as $ID=>$criteria_result)
		{
			echo "<tr  class='tab_bg_2'>";
			$criteria->getFromDB($criteria_result["ID"]);
			$this->showMinimalCriteria($criteria->fields);
			echo "<td class='tab_bg_2'>";
			echo "<strong>".getYesNo($criteria_result["result"])."</strong>";
			echo "</td>";
		}
		echo "</table>";

		$global_result =(isset($output["_rule_process"])?1:0);
		
		echo "<br><table class='tab_cadrehov'>";
		echo "<tr><th colspan='4'>" . $LANG["rulesengine"][81] . "</th></tr>";
		echo "<tr  class='tab_bg_2'>";
		echo "<td class='tab_bg_2' colspan='4' align='center'>".$LANG["rulesengine"][41]." : <strong> ".getYesNo($global_result)."</strong></td>";

		//If output array contains keys begining with _ : drop it
		foreach($output as $criteria => $value)
			if ($criteria[0]=='_')
			unset($output[$criteria]);
			
		
		foreach ($output as $criteria => $value)
		{
			echo "<tr  class='tab_bg_2'>";
			echo "<td class='tab_bg_2'>";
			echo $RULES_ACTIONS[$this->rule_type][$criteria]["name"];
			echo "</td>";
			echo "<td class='tab_bg_2'>";
			echo $this->getActionValue($criteria,$value);
			echo "</td>";
			echo "</tr>";
		}

		//If a regular expression was used, and matched, display the results
		if (count($regex_results))
		{
				echo "<tr  class='tab_bg_2'>";
				echo "<td class='tab_bg_2'>".$LANG["rulesengine"][85]."</td>";
				echo "<td class='tab_bg_2'>";
				print_r($regex_results);
				echo "</td>";
				echo "</tr>";
		}

		echo "</tr>";
		
		echo "</table></div>";
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
		echo "<td>" . $this->getCriteriaDisplayPattern($fields["criteria"],$fields["condition"],$fields["pattern"]) . "</td>";
	}	

	/**
	 * Show the minimal infos for the action rule
	* @param $fields datas used to display the action
	* @param $canedit right to edit ?
	 */
	function showMinimalAction($fields,$canedit){
		echo "<td>" . $this->getActionName($fields["field"]) . "</td>";
		echo "<td>" . getActionByID($fields["action_type"]) . "</td>";
		echo "<td>" . stripslashes($this->getActionValue($fields["field"],$fields["value"],$fields["action_type"])) . "</td>";
		
	}	
	
	/**
 	* Return a value associated with a pattern associated to a criteria to display it
 	* @param $ID the given criteria
        * @param $condition condition used
 	* @param $pattern the pattern
 	*/
 	function getCriteriaDisplayPattern($ID,$condition,$pattern)
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
					if ($condition==PATTERN_IS||$condition==PATTERN_IS_NOT){
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
 	* Display item used to select a pattern for a criteria
	* @param $name criteria name
 	* @param $ID the given criteria
	* @param $condition condition used
 	* @param $value the pattern
 	*/
 	function displayCriteriaSelectPattern($name,$ID,$condition,$value=""){
		$crit=$this->getCriteria($ID);

		$display=false;
		if (isset($crit['type'])){
			switch ($crit['type']){
				case "dropdown":
					if ($condition==PATTERN_IS||$condition==PATTERN_IS_NOT){
						dropdownValue($crit['table'],$name,$value);
						$display=true;
					}
					break;
				case "dropdown_users":
					if ($condition==PATTERN_IS||$condition==PATTERN_IS_NOT){
						dropdownAllUsers($name,$value);
						$display=true;
					}
					break;
				case "dropdown_request_type":
					if ($condition==PATTERN_IS||$condition==PATTERN_IS_NOT){
						dropdownRequestType($name,$value);
						$display=true;
					}
					break;
				case "dropdown_priority":
					if ($condition==PATTERN_IS||$condition==PATTERN_IS_NOT){
						dropdownPriority($name,$value);
						$display=true;
					} 
					break;
			}
		} 
		if (!$display){
			autocompletionTextField($name, "glpi_rules_criterias", "pattern", $value, 30);
		}
	}

	/**
 	* Display item to select a value for criteria
 	* @param $type criteria type
	* @param $condition condition used
 	*/
 	function displayCriteriaSelectValue($type,$condition){
		$display=false;
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
			switch ($action['type'])
			{
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

	/**
 	 * Return a value associated with a pattern associated to a criteria to display it
 	 * @param $ID the given criteria
	 * @param $condition condition used
 	 * @param $value the pattern
 	 */
	
 	function getCriteriaValue($ID,$condition,$value)
	{
		global $LANG;
		$crit=$this->getCriteria($ID);
		if (!isset($crit['type'])){
			return $value;
		} else {
			switch ($crit['type']){
				case "dropdown":
					if ($condition!=PATTERN_IS && $condition!=PATTERN_IS_NOT){
						return getDropdownName($crit["table"],$value);
					}
				break;
				case "dropdown_assign":
				case "dropdown_users":
 					if ($condition!=PATTERN_IS&&$condition!=PATTERN_IS_NOT){
 						return getUserName($value);
 					}
				break;
				case "yesno":
 					if ($condition!=PATTERN_IS&&$condition!=PATTERN_IS_NOT){
 						if ($value) 
 							return $LANG["choice"][1];
 						else
 							return $LANG["choice"][0];	
 					}
				break;
				case "dropdown_priority":
 					if ($condition!=PATTERN_IS&&$condition!=PATTERN_IS_NOT){
 						return getPriorityName($value);
 					}
				break;
			}
			return $value;
		}
	}

	/**
	 * Function used to display type specific criterias during rule's preview
	 */
	function showSpecificCriteriasForPreview($fields)
	{
		
	}
	
	/**
	 ** Function used to add specific params before rule processing
	 */
	function addSpecificParamsForPreview($fields,$params){
		return $params;
	}
	
	function showRulePreviewCriteriasForm($target,$rule_id){

		global $DB, $LANG,$RULES_CRITERIAS,$RULES_ACTIONS; 
		
		if ($this->getRuleWithCriteriasAndActions($rule_id,1,0)){
			echo "<form name='testrule_form' id='testrule_form' method='post' action=\"$target\">\n";
			echo "<div class='center'>";
			echo "<table class='tab_cadre_fixe'>"; 
			echo "<tr><th colspan='3'>" . $LANG["rulesengine"][6] . "</th></tr>"; 

			$type_match=($this->fields["match"]==AND_MATCHING?$LANG["rulesengine"][42]:$LANG["rulesengine"][43]);
			$already_displayed=array(); 
			$first=true;
			 //Brower all criterias 
			foreach ($this->criterias as $criteria){
				
				//Look for the criteria in the field of already displayed criteria : if present, don't display it again 
				if (!in_array($criteria->fields["criteria"],$already_displayed)){
					$already_displayed[]=$criteria->fields["criteria"];

					echo "<tr class='tab_bg_1'>"; 
					echo "<td>";
					if ($first){
						echo "&nbsp;";
						$first=false;
					} else {
						echo $type_match;
					}
					echo "</td>";
					
					$criteria_constants = $RULES_CRITERIAS[$this->fields["rule_type"]][$criteria->fields["criteria"]];
					echo "<td>".$criteria_constants["name"].":</td>";
					echo "<td>";
					$value="";

					if (isset($_POST[$criteria->fields["criteria"]])){
						$value=$_POST[$criteria->fields["criteria"]];
					}	
					$this->displayCriteriaSelectPattern($criteria->fields["criteria"],$criteria->fields['criteria'],$criteria->fields['condition'],$value);
					echo "</td>";
					echo "</tr>"; 
				}
		
			}
			$this->showSpecificCriteriasForPreview($_POST);
		

			echo "<tr><td class='tab_bg_2' colspan='3' align='center'>"; 
			echo "<input type='submit' name='test_rule' value=\"" . $LANG["buttons"][50] . "\" class='submit'>";
			echo "<input type='hidden' name='rule_id' value=\"" . $rule_id . "\">"; 
			echo "<input type='hidden' name='rule_type' value=\"" . $this->rule_type . "\">"; 
			echo "</td></tr>"; 
			echo "</table>";
			echo "</div>";
			echo "</form>";
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
	**/
	function getRuleActions($ID) {
		$sql = "SELECT * FROM glpi_rules_actions WHERE FK_rules=" . $ID;
		global $DB;

		$rules_actions = array ();
		$result = $DB->query($sql);
		while ($rule = $DB->fetch_array($result)){
			$tmp = new RuleAction;
			$tmp->fields = $rule;
			$rules_actions[] = $tmp;
		}
		return $rules_actions;
	}

	function showForm($target,$ID,$ruleid=-1){	
	}

	
	/**
	 * Add an action
	**/
	function addActionByAttributes($action,$ruleid,$field,$value){
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
	**/
	function getRuleCriterias($ID) {
		global $DB;
		$sql = "SELECT * FROM glpi_rules_criterias WHERE FK_rules=" . $ID;

		$rules_list = array ();
		$result = $DB->query($sql);
		while ($rule = $DB->fetch_assoc($result)){
			$tmp = new RuleCriteria;
			$tmp->fields = $rule;
			$rules_list[] = $tmp;
		}
		return $rules_list;
	}

	function showForm($target,$ID,$ruleid=-1,$only_td=false){
		global $LANG,$CFG_GLPI;
		if (!$only_td){
			echo "<form name='entityaffectation_form' id='entityaffectation_form' method='post' action=\"$target\">\n";
			echo "<div class='center'>"; 
			echo "<table class='tab_cadre_fixe'>";
			echo "<tr><th colspan='4'>" . $LANG["rulesengine"][6] . "</th></tr>";
			echo "<tr>";
			echo "<td class='tab_bg_2'></td>";
		}

		echo "<td class='tab_bg_2'>".$LANG["rulesengine"][16]."</td>";
		echo "<td class='tab_bg_2'>".$LANG["rulesengine"][14]."</td>";
		echo "<td class='tab_bg_2'>".$LANG["rulesengine"][15]."</td>";

		if (!$only_td){
			echo "</tr>";
		}
		
	}
	
	/**
	 * Process a criteria of a rule
	 * @param $input the input data used to check criterias
	 * @param $regex_result
	**/
	function process(&$input,&$regex_result){

		// Undefine criteria field : set to blank
		if (!isset($input[$this->fields["criteria"]])){
			$input[$this->fields["criteria"]]='';
		}
		
		//If the value is not an array
		if (!is_array($input[$this->fields["criteria"]])){
			$value=$this->getValueToMatch($this->fields["condition"],$input[$this->fields["criteria"]]);
			$res = matchRules($value,$this->fields["condition"],$this->fields["pattern"],$regex_result);
		} else	{
			//If the value if, in fact, an array of values
			// Negative condition : Need to match all condition (never be)
			if (in_array($this->fields["condition"],array(PATTERN_IS_NOT,PATTERN_NOT_CONTAIN,REGEX_NOT_MATCH))){
				$res = true;
				foreach($input[$this->fields["criteria"]] as $tmp){
					$value=$this->getValueToMatch($this->fields["condition"],$tmp);
					$res &= matchRules($value,$this->fields["condition"],$this->fields["pattern"],$regex_result);
					if (!$res){
						break;
					}
				}
		
			// Positive condition : Need to match one
			 } else {
				$res = false;
				foreach($input[$this->fields["criteria"]] as $tmp){
					$value=$this->getValueToMatch($this->fields["condition"],$tmp);
					$res |= matchRules($value,$this->fields["condition"],$this->fields["pattern"],$regex_result);
					if ($res){
						break;
					}
				}	
			}
			return $value;
		}
		return $res;	
	}

	/**
	 * Return a value associated with a pattern associated to a criteria to compare it
	 * @param $condition condition used
	 * @param $initValue the pattern
	**/
	function getValueToMatch($condition,&$initValue){
		if (empty($this->type)){
			return $initValue;
		} else {			
			switch ($this->type){
				case "dropdown":
					if ($condition!=PATTERN_IS&&$condition!=PATTERN_IS_NOT){
						return getDropdownName($this->table,$initValue);
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

}

class RuleCached extends Rule{

	function getTitleAction($target){
		global $LANG,$CFG_GLPI;
		echo "<div class='center'>"; 
		echo "<table class='tab_cadrehov'>";
		echo "<tr  class='tab_bg_2'>";
		echo "<td width='100%'>";
		echo $LANG["rulesengine"][83];
		echo "</td></tr>";
		echo "</table></div><br>";
	}

	function deleteCacheByRuleId($ID){
		global $DB;
		$DB->query("DELETE FROM ".getCacheTableByRuleType($this->rule_type)." WHERE rule_id=".$ID);
	}

	function post_updateItem($input,$updates) {
		if(isset($updates['match']))
			$this->deleteCacheByRuleId($input["ID"]);
	}
	
	function showCacheStatusByRule($target){
		global $DB,$LANG;
		echo "<div class='center'>"; 
		echo "<table  class='tab_cadre_fixe'>";

		$rulecollection = getRuleCollectionClass($this->rule_type);
		
		$res_count=$DB->query("SELECT *
		FROM ".$rulecollection->cache_table.", glpi_rules_descriptions
		WHERE ".$rulecollection->cache_table.".rule_id=glpi_rules_descriptions.ID 
		AND ".$rulecollection->cache_table.".rule_id=".$this->fields["ID"].
		" ORDER BY name");

		$this->showCacheRuleHeader();
		
		$total = 0;		
		while ($datas = $DB->fetch_array($res_count)){
			echo "<tr>";
			$this->showCacheRuleDetail($datas);
			echo "</tr>";
			$total++;
		}
		
		echo "</table></div><br>";
		echo "<center><a href=\"$target\">".$LANG["buttons"][13]."</center>";
		
	}

	
	function showCacheRuleHeader(){
		global $LANG;
		echo "<th colspan='2'>".$LANG["rulesengine"][100]." : ".$this->fields["name"]."</th></tr>";
		echo "<tr>";
		echo "<td class='tab_bg_1'>".$LANG["rulesengine"][104]."</td>";
		echo "<td class='tab_bg_1'>".$LANG["rulesengine"][105]."</td>";
		echo "</tr>";
	}

	function showCacheRuleDetail($fields){
		global $LANG;
		echo "<td class='tab_bg_2'>".$fields["old_value"]."</td>";
		echo "<td class='tab_bg_2'>".($fields["new_value"]!=''?$fields["new_value"]:$LANG["rulesengine"][106])."</td>";
	}
			
}

class RuleDictionnaryModel extends RuleCached{
	function maxActionsCount(){
		return 1;
	}

	function showCacheRuleHeader(){
		global $LANG;
		echo "<th colspan='3'>".$LANG["rulesengine"][100]." : ".$this->fields["name"]."</th></tr>";
		echo "<tr>";
		echo "<td class='tab_bg_1'>".$LANG["rulesengine"][104]."</td>";
		echo "<td class='tab_bg_1'>".$LANG["common"][5]."</td>";
		echo "<td class='tab_bg_1'>".$LANG["rulesengine"][105]."</td>";
		echo "</tr>";
	}

	function showCacheRuleDetail($fields){
		global $LANG;
		echo "<td class='tab_bg_2'>".$fields["old_value"]."</td>";
		echo "<td class='tab_bg_2'>".($fields["manufacturer"]!=''?$fields["manufacturer"]:'')."</td>";		
		echo "<td class='tab_bg_2'>".($fields["new_value"]!=''?$fields["new_value"]:$LANG["rulesengine"][106])."</td>";
	}
}

class RuleDictionnaryType extends RuleCached{
	function maxActionsCount(){
		return 1;
	}
}

/**
 * Specific rule collection for dictionnary : got a function initialize rule's caching system
 * 
**/
class RuleCachedCollection extends RuleCollection{
	
	var $cache_table;
	var $cache_params;

	function initCache($cache_table,$input_params=array("name"=>"old_value"),$output_params=array("name"=>"new_value")){
		$this->can_replay_rules=true;
		$this->cache_table=$cache_table;
		$this->cache_params["input_value"]=$input_params;
		$this->cache_params["output_value"]=$output_params;
	}

	/**
	* Show the list of rules
	* @param $target  
	* @return nothing
	**/
	function showAdditionalInformationsInForm($target){
		global $CFG_GLPI,$LANG;
		echo "<span class='center'><a href='#' onClick=\"window.open('".$CFG_GLPI["root_doc"]."/front/popup.php?popup=show_cache&amp;rule_type=".$this->rule_type."' ,'glpipopup', 'height=400, width=1000, top=100, left=100, scrollbars=yes' )\">".$LANG["rulesengine"][100]."</a></span>"; 

	}	


	/**
	* Process all the rules collection
	* @param input the input data used to check criterias
	* @param output the initial ouput array used to be manipulate by actions
	* @param params parameters for all internal functions
	* @param force_no_cache don't write rule's result into cache (for preview mode mainly)
	* @return the output array updated by actions
	**/
	function processAllRules($input=array(),$output=array(),$params=array(),$force_no_cache=false){	

		//If cache enabled : try to get value from the cache
		$new_values = $this->checkDataInCache($input);

		if ($new_values != RULE_NOT_IN_CACHE){
			$output["_rule_process"]=true;
			return array_merge($output,$new_values);
		}

		$output=parent::processAllRules($input,$output,$params);

		if (!$force_no_cache&&isset($output["_ruleid"])){
			$this->insertDataInCache($input,$output);
			unset($output["_ruleid"]);
		}

	}

	function showCacheStatusByRuleType(){
		global $DB,$LANG,$CFG_GLPI;
		echo "<div class='center'>"; 
		echo "<table  class='tab_cadre_fixe'>";

		$res_count=$DB->query("SELECT name, rule_id, count(rule_id) as cpt
		FROM ".$this->cache_table.", glpi_rules_descriptions
		WHERE ".$this->cache_table.".rule_id=glpi_rules_descriptions.ID GROUP BY rule_id
		ORDER BY name");

		echo "<th colspan='2'>".$LANG["rulesengine"][100]." : ".$this->getTitle()."</th></tr>";
		echo "<tr>";
		echo "<td class='tab_bg_1'>".$LANG["rulesengine"][102]."</td>";
		echo "<td class='tab_bg_1'>".$LANG["rulesengine"][103]."</td>";
		echo "</tr>";
		
		$total = 0;		
		while ($datas = $DB->fetch_array($res_count)){
			echo "<tr>";			
			echo "<td class='tab_bg_2'>";
			echo "<a href='#' onClick=\"window.open('".$CFG_GLPI["root_doc"]."/front/popup.php?popup=show_cache&amp;rule_type=".$this->rule_type."&rule_id=".$datas["rule_id"]."' ,'glpipopup', 'height=400, width=1000, top=100, left=100, scrollbars=yes' )\">";
			echo $datas["name"];
			echo "</a></td>";
			echo "<td class='tab_bg_2'>".$datas["cpt"]."</td>";
			echo "</tr>";
			$total+=$datas["cpt"];
		}
		
		echo "<tr>";
		echo "<td class='tab_bg_2'><strong>".$LANG["common"][33]." (".$DB->numrows($res_count).")</strong></td>";
		echo "<td class='tab_bg_2'><strong>".$total."</strong></td>";
		echo "</tr></table></div>";		
	}


	function checkDataInCache($input){
		global $DB;
		
		$where="";
		$first=true;
		foreach($this->cache_params["input_value"] as $param => $value){
			if (isset($input[$param])){
				$where.=(!$first?" AND ":"")." ".$value."='".$input[$param]."'";
				$first=false;
			}
		}
			
		$sql = "SELECT * FROM ".$this->cache_table." WHERE ".$where;

		if ($res_check = $DB->query($sql)){
			$output_values=array();
			if ($DB->numrows($res_check) == 1){
				$data=$DB->fetch_assoc($res_check);
				foreach ($this->cache_params["output_value"] as $param => $param_value){
					if (isset($data[$param_value])){
						$output_values[$param]=$data[$param_value];
					}
				}
				return $output_values;
		
			}
		}
		return RULE_NOT_IN_CACHE;
	}

	function insertDataInCache($input,$output){
		global $DB;

		$old_values="";
		$into_old="";
		foreach($this->cache_params["input_value"] as $param => $value){
			$into_old.="`".$value."`, ";
			$old_values.="\"".$input[$param]."\", ";
		}
		
		$into_new="";
		$new_values="";
		foreach($this->cache_params["output_value"] as $param => $value){
			$into_new.=", `".$value."`";
			$new_values.=" ,\"".$output[$param]."\"";
		}
		$sql="INSERT INTO ".$this->cache_table." (".$into_old."`rule_id`".$into_new.") VALUES (".$old_values.$output["_ruleid"].$new_values.")";
		$DB->query($sql);
	}

	function deleteCache(){
		global $DB;
		$DB->query("TRUNCATE TABLE ".$this->cache_table);
	}	
	
}
class RuleTypeCollection extends RuleCachedCollection{

	var $item_table="";
	
	function getRelatedObject(){		
	}

	function replayRulesOnExistingDB(){
		global $DB,$LANG;

		$obj = $this->getRelatedObject();
		$this->deleteCache();

		$Sql="SELECT * FROM " . $this->item_table;
		$result = $DB->query($Sql);

		$nb = $DB->numrows($result);
		
		if ($result && $nb>0) {
			// Step to refresh progressbar
			$step=($nb>20 ? floor($nb/20) : 1);
			$i=0;
			while ($data = $DB->fetch_array($result)){
				if (!($i % $step) && !isCommandLine()){
					changeProgressBarPosition($i,$nb,"$i / $nb");
				}
				//Replay Type dictionnary
				$ID=externalImportDropdown($this->item_table,addslashes($data["name"]),-1,array(),addslashes($data["comments"]));

				if ($data['ID'] != $ID) {
					$nbupd=0;
	
					$Sql = "UPDATE ".$obj->table." SET type=".$ID." WHERE type=".$data['ID'];
					$resupd = $DB->query($Sql);
					$nbupd = ($resupd ? $DB->affected_rows() : -1);					
	
					$Sql = "DELETE FROM ".$this->item_table." WHERE ID=".$data['ID'];
					$resdel = $DB->query($Sql);
					$nbdel = ($resdel ? $DB->affected_rows() : -1);
				}		

				$i++;
			} 
		}
		
		if (!isCommandLine()) {
			changeProgressBarPosition($nb,$nb,"$i / $nb");
		}
	} // function
}	

class RuleModelCollection extends RuleCachedCollection{
	var $item_table;
	
	function getRelatedObject(){		
	}
	
	function RuleModelCollection(){
		$this->item_table = "";
	}

	function replayRulesOnExistingDB(){
		global $DB;
		
		//error_log("RuleModelCollection::replayRulesOnExistingDB");
		$this->deleteCache();
		$item = $this->getRelatedObject();
		
		$tocheck=array();
		$sql="SELECT DISTINCT glpi_dropdown_manufacturer.ID AS idmanu, glpi_dropdown_manufacturer.name AS manufacturer, ".
			$this->item_table.".ID AS idmodel, ".$this->item_table.".name AS name, ".$this->item_table.".comments AS comments ".
			"FROM ".$item->table.", ".$this->item_table.", .glpi_dropdown_manufacturer ".
			"WHERE ".$item->table.".model=".$this->item_table.".ID ".
			"AND ".$item->table.".FK_glpi_enterprise=glpi_dropdown_manufacturer.ID";
		$result = $DB->query($sql);

		$nb = $DB->numrows($result);
		$step=($nb>20 ? floor($DB->numrows($result)/20) : 1);

		if ($DB->numrows($result)>0) {	
			for ($i=0;$data = $DB->fetch_array($result); $i++) {
				
				if (!($i % $step) && !isCommandLine()){
					changeProgressBarPosition($i,$nb,"$i / $nb");
				}
				if (isset($data["manufacturer"])){
					$data["manufacturer"] = processManufacturerName($data["manufacturer"]);
				}
				$ID=externalImportDropdown($this->item_table,addslashes($data["name"]),-1,$data,addslashes($data["comments"]));
				if ($ID != $data["idmodel"]) {
					$tocheck[$data["idmodel"]]=1;

					$sql = "UPDATE ".$item->table." SET model=".$ID." WHERE FK_glpi_enterprise=".$data['idmanu']." AND model=".$data['idmodel'];
					$resupd = $DB->query($sql);
				}
			} // while fetch
		}
		foreach ($tocheck AS $ID => $rien) 	{
			$sql="SELECT COUNT(*) FROM ".$item->table." WHERE model=$ID";
			$result = $DB->query($sql);
			if ($result && $DB->result($result,0,0)==0) {
				$Sql = "DELETE FROM ".$this->item_table." WHERE ID=".$ID;
				$resdel = $DB->query($Sql);
				
				//if ($resdel) error_log("$ID not used, deleted");								
			}		
		} // each tocheck
		if (!isCommandLine()) {
			changeProgressBarPosition($nb,$nb,"$i / $nb");
		}
	}
	
	/**
	 * Override rulesengine::insertDataInCache. The insert request is specific because of the insertion of the field manufacturer
	 * It is, moreover, quicker because the request is hardcoded
	**/

	function insertDataInCache($old_values,$output){
		global $DB;
		$sql="INSERT INTO ".$this->cache_table." (`old_value`,`manufacturer`,`rule_id`,`new_value`) VALUES (\"".$old_values["name"]."\",\"".$old_values["manufacturer"]."\", ".$output["_ruleid"].", \"".$output["name"]."\")";
		$DB->query($sql);
	}
		
}	

?>
