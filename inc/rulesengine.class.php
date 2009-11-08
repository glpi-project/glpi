<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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

class SingletonRuleList {
   /// Items list
   var $list = array();
   /// Items loaded ?
   var $load = 0;

   /**
   * get a unique instance of a SingletonRuleList for a type of RuleCollection
   *
   * @param $type of the Rule listed
   * @return unique instance of an object
   */
   public static  function &getInstance($type) {
      static $instances = array();

      if (!isset($instances[$type])) {
         $instances[$type] = new SingletonRuleList();
      }
      return $instances[$type];
   }


}

class RuleCollection {
   /// Rule type
   var $sub_type;
   /// Name of the class used to rule
   var $rule_class_name="Rule";
   /// process collection stop on first matched rule
   var $stop_on_first_match=false;
   /// Right needed to use this rule collection
   var $right="config";
   /// field used to order rules
   var $orderby="ranking";
   /// Processing several rules : use result of the previous one to computer the current one
   var $use_output_rule_process_as_next_input=false;
   /// Rule collection can be replay (for dictionnary)
   var $can_replay_rules=false;
   /// List of rules of the rule collection
   var $RuleList=NULL;
   /// Menu type
   var $menu_type="rule";
   /// Menu option
   var $menu_option="";

   /**
   * Constructor
   * @param sub_type the rule type used for the collection
   **/
   function __construct($sub_type) {
      $this->sub_type = $sub_type;
   }

   /**
   * Get Collection Size : retrieve the number of rules
   *
   * @return : number of rules
   **/
   function getCollectionSize() {
      return countElementsInTable("glpi_rules", "sub_type=".$this->sub_type);
   }

   /**
   * Get Collection Part : retrieve descriptions of a range of rules
   *
   * @param $start : first rule (in the result set)
   * @param $limit : max number of rules ti retrieve
   **/
   function getCollectionPart($start=0,$limit=0) {
      global $DB;

      $this->RuleList = new SingletonRuleList($this->sub_type);
      $this->RuleList->list = array();

      //Select all the rules of a different type
      $sql = "SELECT *
              FROM `glpi_rules`
              WHERE `sub_type` = '".$this->sub_type."'
              ORDER BY ".$this->orderby." ASC";
      if ($limit) {
         $sql .= " LIMIT ".intval($start).",".intval($limit);
      }
      $result = $DB->query($sql);

      if ($result) {
         while ($data=$DB->fetch_assoc($result)) {
            //For each rule, get a Rule object with all the criterias and actions
            $tempRule= $this->getRuleClass();
            $tempRule->fields = $data;
            $this->RuleList->list[] = $tempRule;
         }
      }
   }

   /**
   * Get Collection Datas : retrieve descriptions and rules
   * @param $retrieve_criteria Retrieve the criterias of the rules ?
   * @param $retrieve_action Retrieve the action of the rules ?
   **/
   function getCollectionDatas($retrieve_criteria=0,$retrieve_action=0) {
      global $DB;

      if ($this->RuleList === NULL) {
         $this->RuleList = SingletonRuleList::getInstance($this->sub_type);
      }
      $need = 1+($retrieve_criteria?2:0)+($retrieve_action?4:0);

      // check if load required
      if (($need & $this->RuleList->load) != $need) {
         //Select all the rules of a different type
         $sql = "SELECT `id`
                 FROM `glpi_rules`
                 WHERE `is_active` = '1'
                       AND `sub_type` = '".$this->sub_type."'
                 ORDER BY ".$this->orderby." ASC";
         $result = $DB->query($sql);

         if ($result) {
            $this->RuleList->list = array();
            while ($rule=$DB->fetch_array($result)) {
               //For each rule, get a Rule object with all the criterias and actions
               $tempRule= $this->getRuleClass();
               if ($tempRule->getRuleWithCriteriasAndActions($rule["id"],$retrieve_criteria,
                                                             $retrieve_action)) {
                  //Add the object to the list of rules
                  $this->RuleList->list[] = $tempRule;
               }
            }
            $this->RuleList->load = $need;
         }
      }
   }

   /**
    * Get a instance of the class to manipulate rule of this collection
    *
   **/
   function getRuleClass() {
      return new $this->rule_class_name();
   }

   /**
    * Is a confirmation needed before replay on DB ?
    * If needed need to send 'replay_confirm' in POST
    * @param $target filename : where to go when done
    * @return  true if confirmtion is needed, else false
   **/
   function warningBeforeReplayRulesOnExistingDB($target) {
      return false;
   }

   /**
    * Replay Collection on DB
    * @param $offset  first row to work on
    * @param $maxtime float : max system time to stop working
    * @param $items   array containg items to replay. If empty -> all
    * @param $params  additional parameters if needed
    *
    * @return -1 if all rows done, else offset for next run
   **/
   function replayRulesOnExistingDB($offset=0,$maxtime=0, $items=array(),$params=array()) {
   }

   /**
   * Get title used in list of rules
   * @return Title of the rule collection
   **/
   function getTitle() {
      global $LANG;

      return $LANG['rulesengine'][29];
   }

   /**
   * Show the list of rules
   * @param $target
   * @return nothing
   **/
   function showForm($target) {
      global $CFG_GLPI, $LANG;

      $canedit = haveRight($this->right, "w");
      echo "<table class='tab_cadre_fixe'><tr><th>";
      //Display informations about the how the rules engine process the rules
      if ($this->stop_on_first_match) {
         //The engine stop on the first matched rule
         echo "<span class='center b'>".$LANG['rulesengine'][120]."</span><br>";
      } else {
         //The engine process all the rules
         echo "<span class='center b'>".$LANG['rulesengine'][121]."</span><br>";
      }
      if ($this->use_output_rule_process_as_next_input) {
         //The engine keep the result of a rule to be processed further
         echo "<span class='center b'>".$LANG['rulesengine'][122]."</span><br>";
      }
      echo "</th></tr></table>\n";

      $nb = $this->getCollectionSize();
      $start = (isset($_GET["start"]) ? $_GET["start"] : 0);
      if ($start >= $nb) {
         $start = 0;
      }
      $limit = $_SESSION['glpilist_limit'];
      $this->getCollectionPart($start,$limit);

      printPager($start,$nb,$_SERVER['PHP_SELF'],"");

      echo "<br><form name='ruleactions_form' id='ruleactions_form' method='post' action=\"$target\">";
      echo "\n<div class='center'>";
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr><th colspan='6'>" . $this->getTitle() ."</th></tr>\n";
      echo "<tr><td class='tab_bg_2 center' colspan='2'>".$LANG['common'][16]."</td>";
      echo "<td class='tab_bg_2 center'>".$LANG['joblist'][6]."</td>";
      echo "<td class='tab_bg_2 center'>".$LANG['common'][60]."</td>";
      echo "<td class='tab_bg_2' colspan='2'></td></tr>\n";

      initNavigateListItems(RULE_TYPE,"",$this->sub_type);
      for ($i=$start,$j=0 ; isset($this->RuleList->list[$j]) ; $i++,$j++) {
         $this->RuleList->list[$j]->showMinimalForm($target,$i==0,$i==$nb-1);
         addToNavigateListItems(RULE_TYPE,$this->RuleList->list[$j]->fields['id'],$this->sub_type);
      }
      echo "</table>\n";
      if ($canedit&&$nb>0) {
         echo "<table width='950px' class='tab_glpi'>";
         echo "<tr><td><img src=\"" . $CFG_GLPI["root_doc"] . "/pics/arrow-left.png\" alt=''></td>";
         echo "<td class='center'>";
         echo "<a onclick= \"if (markCheckboxes('entityaffectation_form')) return false;\" href='" .
                $_SERVER['PHP_SELF'] . "?select=all'>" . $LANG['buttons'][18] . "</a></td>";
         echo "<td>/</td><td class='center'>";
         echo "<a onclick= \"if (unMarkCheckboxes('entityaffectation_form')) return false;\" href='" .
                $_SERVER['PHP_SELF'] . "?select=none'>" . $LANG['buttons'][19] . "</a></td>";
         echo "<td class='left' width='80%'>";
         echo "<select name='massiveaction' id='massiveaction'>";
         echo "<option value='-1' selected>------</option>";
         echo "<option value='delete'>".$LANG['buttons'][6]."</option>";
         if ($this->orderby=="ranking") {
            echo "<option value='move_rule'>".$LANG['buttons'][20]."</option>";
         }
         echo "<option value='activate_rule'>".$LANG['buttons'][41]."</option>";
         echo "</select>\n";

         $params=array('action'=>'__VALUE__',
                       'itemtype'=>RULE_TYPE,
                       'sub_type'=>$this->sub_type);

         ajaxUpdateItemOnSelectEvent("massiveaction","show_massiveaction",
                                     $CFG_GLPI["root_doc"]."/ajax/dropdownMassiveAction.php",$params);

         echo "<span id='show_massiveaction'>&nbsp;</span>\n";
         echo "</td>";
         if ($this->can_replay_rules) {
            echo "<td><input type='submit' name='replay_rule' value=\"" . $LANG['rulesengine'][76] .
                       "\" class='submit'></td>";
         }
         echo "</tr></table>\n";
         echo "</div>";
      }
      echo "</form>";
      echo "<br><span class='icon_consol'>";
      echo "<a href='#' onClick=\"var w=window.open('".$CFG_GLPI["root_doc"].
             "/front/popup.php?popup=test_all_rules&amp;sub_type=".$this->sub_type.
             "&amp' ,'glpipopup', 'height=400, width=1000, top=100, left=100, scrollbars=yes' );".
             "w.focus();\">".$LANG['rulesengine'][84]."</a></span>";

      $this->showAdditionalInformationsInForm($target);
   }

   /**
   * Show the list of rules
   * @param $target
   * @return nothing
   **/
   function showAdditionalInformationsInForm($target) {
   }

   /**
   * Modify rule's ranking and automatically reorder all rules
   * @param $ID the rule ID whose ranking must be modified
   * @param $action up or down
   **/
   function changeRuleOrder($ID,$action) {
      global $DB;

      $sql = "SELECT `ranking`
              FROM `glpi_rules`
              WHERE `id` ='$ID'";

      if ($result = $DB->query($sql)) {
         if ($DB->numrows($result)==1) {
            $current_rank=$DB->result($result,0,0);
            // Search rules to switch
            $sql2 = "SELECT `id`, `ranking`
                     FROM `glpi_rules`
                     WHERE `sub_type` ='".$this->sub_type."'";
            switch ($action) {
               case "up" :
                  $sql2 .= " AND `ranking` < '$current_rank'
                           ORDER BY `ranking` DESC
                           LIMIT 1";
                  break;

               case "down" :
                  $sql2 .= " AND `ranking` > '$current_rank'
                           ORDER BY `ranking` ASC
                           LIMIT 1";
                  break;

               default :
                  return false;
            }
            if ($result2 = $DB->query($sql2)) {
               if ($DB->numrows($result2)==1) {
                  list($other_ID,$new_rank)=$DB->fetch_array($result2);
                  $query = "UPDATE
                            `glpi_rules`
                            SET `ranking` = '$new_rank'
                            WHERE `id` ='$ID'";
                  $query2 = "UPDATE
                             `glpi_rules`
                             SET `ranking` = '$current_rank'
                             WHERE `id` ='$other_ID'";
                  return ($DB->query($query)&&$DB->query($query2));
               }
            }
         }
         return false;
      }
   }

   /**
    * Update Rule Order when deleting a rule
    *
    * @param $ranking rank of the deleted rule
    *
    * @return true if all ok
   **/
   function deleteRuleOrder($ranking) {
      global $DB;

      $rules = array();
      $sql = "UPDATE
              `glpi_rules`
              SET `ranking` = `ranking`-1
              WHERE `sub_type` ='".$this->sub_type."'
                    AND `ranking` > '$ranking' ";
      return $DB->query($sql);
   }

   /**
    * Move a rule in an ordered collection
    *
    * @param $ID of the rule to move
    * @param $ref_ID of the rule position  (0 means all, so before all or after all)
    * @param $type of move : after or before
    *
    * @return true if all ok
    *
   **/
   function moveRule($ID,$ref_ID,$type='after') {
      global $DB;

      $ruleDescription = new Rule;

      // Get actual ranking of Rule to move
      $ruleDescription->getFromDB($ID);
      $old_rank=$ruleDescription->fields["ranking"];

      // Compute new ranking
      if ($ref_ID) { // Move after/before an existing rule
         $ruleDescription->getFromDB($ref_ID);
         $rank=$ruleDescription->fields["ranking"];
      } else if ($type == "after") {
         // Move after all
         $query = "SELECT MAX(`ranking`) AS maxi
                   FROM `glpi_rules`
                   WHERE `sub_type` ='".$this->sub_type."' ";
         $result = $DB->query($query);
         $ligne = $DB->fetch_array($result);
         $rank = $ligne['maxi'];
      } else {
         // Move before all
         $rank=0;
      }

      // Move others rules in the collection
      if ($old_rank < $rank) {
         if ($type=="before"){
            $rank--;
         }
         // Move back all rules between old and new rank
         $query = "UPDATE
                   `glpi_rules`
                   SET `ranking` = `ranking`-1
                   WHERE `sub_type` ='".$this->sub_type."'
                         AND `ranking` > '$old_rank'
                         AND `ranking` <= '$rank'";
         $result = $DB->query($query);
      } else if ($old_rank > $rank) {
         if ($type=="after") {
            $rank++;
         }
         // Move forward all rule  between old and new rank
         $query = "UPDATE
                   `glpi_rules`
                   SET `ranking` = `ranking`+1
                   WHERE `sub_type` ='".$this->sub_type."'
                         AND `ranking` >= '$rank'
                         AND `ranking` < '$old_rank'";
         $result = $DB->query($query);
      } else { // $old_rank == $rank : nothing to do
         $result = false;
      }

      // Move the rule
      if ($result && $old_rank != $rank) {
         $query = "UPDATE
                   `glpi_rules`
                   SET `ranking` = '$rank'
                   WHERE `id` = '$ID' ";
         $result = $DB->query($query);
      }
      return ($result ? true : false);
   }

   /**
   * Process all the rules collection
   * @param input the input data used to check criterias
   * @param output the initial ouput array used to be manipulate by actions
   * @param params parameters for all internal functions
   * @return the output array updated by actions
   **/
   function processAllRules($input=array(),$output=array(),$params=array()) {

      // Get Collection datas
      $this->getCollectionDatas(1,1);
      $input=$this->prepareInputDataForProcess($input,$params);

      if (count($this->RuleList->list)) {
         foreach ($this->RuleList->list as $rule) {
            //If the rule is active, process it
            if ($rule->fields["is_active"]) {
               $output["_rule_process"]=false;
               $rule->process($input,$output,$params);
               if ($output["_rule_process"] && $this->stop_on_first_match) {
                  unset($output["_rule_process"]);
                  $output["_ruleid"]=$rule->fields["id"];
                  return $output;
               }
            }
            if ($this->use_output_rule_process_as_next_input) {
               $input=$output;
            }
         }
      }
      return $output;
   }

   /**
    * Show form displaying results for rule collection preview
    * @param $target where to go
    * @param $values data array
    **/
   function showRulesEnginePreviewCriteriasForm($target,$values) {
      global $DB, $LANG,$RULES_CRITERIAS,$RULES_ACTIONS;

      $input = $this->prepareInputDataForTestProcess();
      if (count($input)) {
         echo "<form name='testrule_form' id='testrulesengine_form' method='post' action=\"$target\">";
         echo "\n<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='2'>" . $LANG['rulesengine'][6] . "</th></tr>\n";

         //Brower all criterias
         foreach ($input as $criteria) {
            echo "<tr class='tab_bg_1'>";
            if (isset($RULES_CRITERIAS[$this->sub_type][$criteria])) {
               $criteria_constants = $RULES_CRITERIAS[$this->sub_type][$criteria];
               echo "<td>".$criteria_constants["name"]."&nbsp;:</td>";
            } else {
               echo "<td>".$criteria."&nbsp;:</td>";
            }
            echo "<td>";
            $rule = getRuleClass($this->sub_type);
            $rule->displayCriteriaSelectPattern($criteria,$criteria,PATTERN_IS,
                                                isset($values[$criteria])?$values[$criteria]:'');
            echo "</td></tr>\n";
         }
         $rule->showSpecificCriteriasForPreview($_POST);

         echo "<tr><td class='tab_bg_2 center' colspan='2'>";
         echo "<input type='submit' name='test_all_rules' value=\"" . $LANG['buttons'][50] .
                "\" class='submit'>";
         echo "<input type='hidden' name='sub_type' value=\"" . $this->sub_type . "\">";
         echo "</td></tr>\n";
         echo "</table></div>";
         echo "</form>\n";
      } else {
         echo '<br><div class="center b">'.$LANG['rulesengine'][97].'</div>';
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
   function testAllRules($input=array(),$output=array(),$params=array()) {

      // Get Collection datas
      $this->getCollectionDatas(1,1);

      if (count($this->RuleList->list)) {
         foreach ($this->RuleList->list as $rule) {
            //If the rule is active, process it
            if ($rule->fields["is_active"]) {
               $output["_rule_process"]=false;
               $output["result"][$rule->fields["id"]]["id"]=$rule->fields["id"];
               $rule->process($input,$output,$params);
               if ($output["_rule_process"]&&$this->stop_on_first_match) {
                  unset($output["_rule_process"]);
                  $output["result"][$rule->fields["id"]]["result"]=1;
                  $output["_ruleid"]=$rule->fields["id"];
                  return $output;
               } else if ($output["_rule_process"]) {
                  $output["result"][$rule->fields["id"]]["result"]=1;
               } else {
                  $output["result"][$rule->fields["id"]]["result"]=0;
               }
            } else {
               //Rule is inactive
               $output["result"][$rule->fields["id"]]["result"]=2;
            }
            if ($this->use_output_rule_process_as_next_input) {
               $input=$output;
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
   function prepareInputDataForProcess($input,$params) {
      return $input;
   }

   /**
   * Prepare input datas for the rules collection
   * @return the updated input datas
   **/
   function prepareInputDataForTestProcess() {
      global $DB;

      $input = array();
      $res = $DB->query("SELECT DISTINCT `glpi_rulescriterias`.`criteria`
                         FROM `glpi_rulescriterias`, `glpi_rules`
                         WHERE `glpi_rules`.`is_active` = '1'
                               AND `glpi_rulescriterias`.`rules_id`=`glpi_rules`.`id`
                               AND `glpi_rules`.`sub_type`='".$this->sub_type."'");
      while ($data = $DB->fetch_array($res)) {
         $input[]=$data["criteria"];
      }
      return $input;
   }

   /**
    * Show form displaying results for rule engine preview
    * @param $target where to go
    * @param $input data array
    **/
   function showRulesEnginePreviewResultsForm($target,$input) {
      global $LANG,$RULES_ACTIONS;

      $output = $this->testAllRules($input,array(),$input);
      $rule = getRuleClass($this->sub_type);

      echo "<div class='center'>";

      if (isset($output["result"])) {
         echo "<table class='tab_cadrehov'>";
         echo "<tr><th colspan='2'>" . $LANG['rulesengine'][82] . "</th></tr>\n";
         foreach ($output["result"] as $ID=>$rule_result) {
            echo "<tr class='tab_bg_1'>";
            $rule->getFromDB($ID);
            echo "<td>".$rule->fields["name"]."</td>";
            echo "<td class='b'>";
            switch ($rule_result["result"]) {
               case 0 :
                  echo $LANG['choice'][0];
                  break;

               case 1 :
                  echo $LANG['choice'][1];
                  break;

               case 2 :
                  echo $LANG['rulesengine'][107];
                  break;
            }
            echo "</td></tr>\n";
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
    * Unset criterias from the rule's ouput results (begins by _)
    * @param $output clean output array to clean
    * @return cleaned array
    **/
   function cleanTestOutputCriterias($output) {

      //If output array contains keys begining with _ : drop it
      foreach($output as $criteria => $value) {
         if ($criteria[0]=='_') {
            unset($output[$criteria]);
         }
      }
      return $output;
   }

   /**
    * Show test results for a rule
    * @param $rule rule object
    * @param $output Output data array
    * @param $global_result boolean : global result
    * @return cleaned array
    **/
   function showTestResults($rule,$output,$global_result) {
      global $LANG,$RULES_ACTIONS;

      echo "<table class='tab_cadrehov'>";
      echo "<tr><th colspan='2'>" . $LANG['rulesengine'][81] . "</th></tr>\n";
      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2' class='center'>".$LANG['rulesengine'][41]."&nbsp;:&nbsp;<strong> ".
             getYesNo($global_result)."</strong></td>";

      $output = $this->preProcessPreviewResults($output);

      foreach ($output as $criteria => $value) {
         echo "<tr class='tab_bg_2'>";
         echo "<td>".$RULES_ACTIONS[$this->sub_type][$criteria]["name"]."</td>";
         echo "<td>".$rule->getActionValue($criteria,$value)."</td>";
         echo "</tr>\n";
      }
      echo "</tr></table>\n";
   }

   function preProcessPreviewResults($output) {
      return $this->cleanTestOutputCriterias($output);
   }

   /**
    * Print a title if needed which will be displayed above list of rules
    *
    *@return nothing (display)
    **/
   function title() {
   }

}


/**
 * Rule class store all informations about a GLPI rule :
 *   - description
 *   - criterias
 *   - actions
**/
class Rule extends CommonDBTM {
   // From CommonDBTM
   public $table = 'glpi_rules';
   public $type = RULE_TYPE;

   // Specific ones
   ///Actions affected to this rule
   var $actions = array();
   ///Criterias affected to this rule
   var $criterias = array();
   /// Rule type
   var $sub_type;
   /// Right needed to use this rule
   var $right='config';
   /// Rules can be sorted ?
   var $can_sort=false;
   /// field used to order rules
   var $orderby='ranking';

   /**
   * Constructor
   * @param sub_type the rule type used for the collection
   **/
   function __construct($sub_type=0) {
      $this->sub_type=$sub_type;
   }

   function post_getEmpty () {
      $this->fields['is_active']=0;
   }

   /**
   * Get additional header for rule
   * @param $target where to go if link needed
   * @return nothing display
   **/
   function getTitleRule($target) {
   }

   /**
   * Get title used in rule
   * @return Title of the rule
   **/
   function getTitle() {
      global $LANG;

      return $LANG['rulesengine'][8];
   }

   /**
   * Show the rule
   * @param $target
   * @param $ID ID of the rule
   * @param $withtemplate
   * @return nothing
   **/
   function showForm($target,$ID,$withtemplate='') {
      global $CFG_GLPI, $LANG;

      $canedit=haveRight($this->right,"w");
      $new=false;
      if (!empty($ID) && $ID>0) {
         $this->getRuleWithCriteriasAndActions($ID,1,1);
      } else {
         $this->getEmpty();
         $new=true;
      }
      $this->getTitleRule($target);
      $this->showTabs($ID, $new,getActiveTab($this->type),array(),"sub_type='".$this->sub_type."'",
                      $this->orderby);
      echo "<form name='rule_form'  method='post' action=\"$target\">\n";
      echo "<div class='center' id='tabsbody' >";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>" . $this->getTitle() . "</th></tr>\n";
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("name",$this->table,"name",$this->fields["name"] ,40);
      echo "</td>";
      echo "<td>".$LANG['joblist'][6]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("description",$this->table,"description",$this->fields["description"] ,
                              40);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['rulesengine'][9]."&nbsp;:</td>";
      echo "<td>";
      $this->dropdownRulesMatch("match",$this->fields["match"]);
      echo "</td>";
      echo "<td>".$LANG['common'][60]."&nbsp;:</td>";
      echo "<td>";
      dropdownYesNo("is_active",$this->fields["is_active"]);
      echo"</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][25]."&nbsp;:</td>";
      echo "<td class='middle' colspan='3'>";
      echo "<textarea cols='110' rows='2' name='comment' >".$this->fields["comment"]."</textarea>";
      echo"</td></tr>\n";

      if ($canedit) {
         if ($new) {
            echo "<tr class='tab_bg_2'><td class='center' colspan='4'>";
            echo "<input type='hidden' name='sub_type' value='".$this->sub_type."'>";
            echo "<input type='submit' name='add_rule' value=\"" . $LANG['buttons'][8] .
                   "\" class='submit'>";
            echo "</td></tr>\n";
         } else {
            echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
            echo "<input type='hidden' name='id' value='".$ID."'>";
            echo "<input type='hidden' name='ranking' value='".$this->fields["ranking"]."'>";
            echo "<input type='submit' name='update_rule' value=\"" . $LANG['buttons'][7] .
                   "\" class='submit'></td>";
            echo "<td class='center' colspan='2'>";
            echo "<input type='submit' name='delete_rule' value=\"" . $LANG['buttons'][6] .
                   "\" class='submit'></td>";
            echo "</tr>\n";

            echo "<tr><td class='tab_bg_2 center' colspan='4'>";
            echo "<a href='#' onClick=\"var w=window.open('".$CFG_GLPI["root_doc"].
                   "/front/popup.php?popup=test_rule&amp;sub_type=".$this->sub_type."&amp;rules_id=".
                   $this->fields["id"]."' ,'glpipopup', 'height=400, width=1000, top=100, left=100,".
                   " scrollbars=yes' );w.focus();\">".$LANG['buttons'][50]."</a>";
            echo "</td></tr>\n";
         }
      }
      echo "</table></div></form>";

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";
   }

   /**
   * Display a dropdown with all the rule matching
   * @param $name dropdown name
   * @param $value default value
   **/
   function dropdownRulesMatch($name,$value='') {
      global $LANG;

      $elements[AND_MATCHING] = $LANG['rulesengine'][42];
      $elements[OR_MATCHING] = $LANG['rulesengine'][43];
      return dropdownArrayValues($name,$elements,$value);
   }

   /**
    * Get all criterias for a given rule
    * @param $ID the rule_description ID
    * @param $withcriterias 1 to retrieve all the criterias for a given rule
    * @param $withactions  1 to retrive all the actions for a given rule
    **/
   function getRuleWithCriteriasAndActions($ID, $withcriterias = 0, $withactions = 0) {

      if ($ID == "") {
         return $this->getEmpty();
      } else if ($ret=$this->getFromDB($ID)) {
         if ($withactions) {
            $RuleAction = new RuleAction;
            $this->actions = $RuleAction->getRuleActions($ID);
         }
         if ($withcriterias) {
            $RuleCriterias = new RuleCriteria;
            $this->criterias = $RuleCriterias->getRuleCriterias($ID);
         }
         return true;
      }
      return false;
   }

   /**
    * display title for action form
    * @param $target where to go if action
   **/
   function getTitleAction($target) {
      global $LANG,$CFG_GLPI;

      foreach($this->getFilteredActions() as $key => $val) {
         if (isset($val['force_actions'])
             && (in_array('regex_result',$val['force_actions'])
                 || in_array('append_regex_result',$val['force_actions']))) {

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'>";
            echo "<td>".$LANG['rulesengine'][83]."</td></tr>\n";
            echo "</table><br>";
            return;
         }
      }
   }

   /**
    * display title for criteria form
    * @param $target where to go if action
   **/
   function getTitleCriteria($target) {
   }

   /**
    * Get maximum number of Actions of the Rule (0 = unlimited)
    * @return the maximum number of actions
   **/
   function maxActionsCount() {
      // Unlimited
      return 0;
   }

   /**
    * Display all rules actions
    * @param $target  where to go for action
    * @param $rules_id  rule ID
   **/
   function showActionsList($target,$rules_id) {
      global $CFG_GLPI, $LANG;

      $canedit = haveRight($this->right, "w");
      $this->getTitleAction($target);

      if (($this->maxActionsCount()==0 || sizeof($this->actions) < $this->maxActionsCount())
          && $canedit) {

         echo "<form name='actionsaddform' method='post' action=\"$target\">\n";
         $this->addActionForm($rules_id);
         echo "</form>";
      }

      echo "<form name='actionsform' id='actionsform' method='post' action=\"$target\">\n";
      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='".($canedit?" 4 ":"3")."'>" . $LANG['rulesengine'][7] . "</th></tr>";
      echo "<tr class='tab_bg_2'>";
      if ($canedit){
         echo "<td>&nbsp;</td>";
      }
      echo "<td class='center b'>".$LANG['rulesengine'][12]."</td>";
      echo "<td class='center b'>".$LANG['rulesengine'][11]."</td>";
      echo "<td class='center b'>".$LANG['rulesengine'][13]."</td>";
      echo "</tr>\n";

      $nb=count($this->actions);
      foreach ($this->actions as $action){
         $this->showMinimalActionForm($action->fields,$canedit);
      }
      echo "</table></div>\n";

      if ($canedit && $nb>0) {
         echo "<table width='950px' class='tab_glpi'>";
         echo "<tr><td><img src=\"" . $CFG_GLPI["root_doc"] . "/pics/arrow-left.png\" alt=''></td>";
         echo "<td class='center'>";
         echo "<a onclick= \"if ( markCheckboxes('actionsform') ) return false;\" href='" .
                $_SERVER['PHP_SELF'] . "?select=all'>" . $LANG['buttons'][18] . "</a></td>";
         echo "<td>/</td><td class='center'>";
         echo "<a onclick= \"if ( unMarkCheckboxes('actionsform') ) return false;\" href='" .
                $_SERVER['PHP_SELF'] . "?select=none'>" . $LANG['buttons'][19] . "</a></td>";
         echo "<td class='left' width='80%'>";
         echo "<input type='submit' name='delete_action' value=\"" . $LANG['buttons'][6] .
                "\" class='submit'>";
         echo "<input type='hidden' name='rules_id' value='$rules_id'>";
         echo "</td></tr></table>\n";
      }
      echo "</form>";
   }

   /**
    * Display the add action form
    * @param $rules_id rule ID
   **/
   function addActionForm($rules_id) {
      global $LANG,$CFG_GLPI;

      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>" . $LANG['rulesengine'][7] . "</tr>";
      echo "<tr class='tab_bg_1 center'>";
      echo "<td>".$LANG['rulesengine'][30] . "&nbsp;:</td><td>";
      $val=$this->dropdownActions(getAlreadyUsedActionsByRuleID($rules_id,$this->sub_type));
      echo "</td><td class='left'>";
      echo "<span id='action_span'>\n";
      $_POST["sub_type"]=$this->sub_type;
      $_POST["field"]=$val;
      include (GLPI_ROOT."/ajax/ruleaction.php");
      echo "</span>\n";
      echo "</td>";
      echo "<td class='tab_bg_2 left' width='80px'>";
      echo "<input type='hidden' name='rules_id' value=\"" . $this->fields["id"] . "\">";
      echo "<input type='submit' name='add_action' value=\"" . $LANG['buttons'][8] .
             "\" class='submit'>";
      echo "</td></tr>\n";
      echo "</table></div><br>";
   }

   /**
    * Display the add criteria form
    * @param $rules_id rule ID
    */
   function addCriteriaForm($rules_id) {
      global $LANG,$CFG_GLPI,$RULES_CRITERIAS;

      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>" . $LANG['rulesengine'][16] . "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td class='center'>".$LANG['rulesengine'][16] . "&nbsp;:</td><td>";
      $val=$this->dropdownCriterias();
      echo "</td><td class='left'>";
      echo "<span id='criteria_span'>\n";
      $_POST["sub_type"]=$this->sub_type;
      $_POST["criteria"]=$val;
      include (GLPI_ROOT."/ajax/rulecriteria.php");
      echo "</span>\n";
      echo "</td>";
      echo "<td class='tab_bg_2' width='80px'>";
      echo "<input type='hidden' name='rules_id' value=\"" . $this->fields["id"] . "\">";
      echo "<input type='submit' name='add_criteria' value=\"" . $LANG['buttons'][8] .
             "\" class='submit'>";
      echo "</td></tr>\n";
      echo "</table></div><br>";
   }

   /**
    * Get maximum number of criterias of the Rule (0 = unlimited)
    * @return the maximum number of criterias
    */
   function maxCriteriasCount() {
      // Unlimited
      return 0;
   }

   /**
    * Display all rules criterias
    * @param $target
    * @param $rules_id
    */
   function showCriteriasList($target,$rules_id) {
      global $CFG_GLPI, $LANG;

      $canedit = haveRight($this->right, "w");
      $this->getTitleCriteria($target);
      if (($this->maxCriteriasCount()==0 || sizeof($this->criterias) < $this->maxCriteriasCount())
          && $canedit) {

         echo "<form name='criteriasaddform'method='post' action=\"$target\">\n";
         $this->addCriteriaForm($rules_id);
         echo "</form>";
      }
      echo "<form name='criteriasform' id='criteriasform' method='post' action=\"$target\">\n";
      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='".($canedit?" 4 ":"3")."'>" . $LANG['rulesengine'][6] . "</th></tr>\n";
      echo "<tr class='tab_bg_2'>";
      if ($canedit) {
         echo "<td>&nbsp;</td>";
      }
      echo "<td class='center b'>".$LANG['rulesengine'][16]."</td>\n";
      echo "<td class='center b'>".$LANG['rulesengine'][14]."</td>\n";
      echo "<td class='center b'>".$LANG['rulesengine'][15]."</td>\n";
      echo "</tr>\n";

      $maxsize = sizeof($this->criterias);
      foreach ($this->criterias as $criteria) {
         $this->showMinimalCriteriaForm($criteria->fields,$canedit);
      }
      echo "</table></div>\n";

      if ($canedit && $maxsize>0) {
         echo "<div class='center'>\n";
         echo "<table width='950px' class='tab_glpi'>\n";
         echo "<tr><td><img src=\"" . $CFG_GLPI["root_doc"] . "/pics/arrow-left.png\" alt=''></td>";
         echo "<td class='center'>";
         echo "<a onclick= \"if ( markCheckboxes('criteriasform') ) return false;\" href='" .
                $_SERVER['PHP_SELF'] . "?id=".$this->fields["id"]."&amp;select=all'>" .
                $LANG['buttons'][18] . "</a></td>";
         echo "<td>/</td><td class='center'>";
         echo "<a onclick= \"if ( unMarkCheckboxes('criteriasform') ) return false;\" href='" .
                $_SERVER['PHP_SELF'] . "?id=".$this->fields["id"]."&amp;select=none'>" .
                $LANG['buttons'][19] . "</a></td>";
         echo "<td class='left' width='80%'>";
         echo "<input type='submit' name='delete_criteria' value=\"" . $LANG['buttons'][6] .
                "\" class='submit'>";
         echo "<input type='hidden' name='rules_id' value='$rules_id'>";
         echo "</td></tr>\n";
         echo "</table></div>";
      }
      echo "</form>\n";
   }

   /**
    * Display the dropdown of the criterias for the rule
    *
    * @return the initial value (first)
    */
   function dropdownCriterias() {
      global $CFG_GLPI;

      $items=array();
      foreach ($this->getCriterias() as $ID => $crit) {
         $items[$ID]=$crit['name'];
      }
      $rand=dropdownArrayValues("criteria", $items);
      $params = array('criteria'=>'__VALUE__',
                      'sub_type'=>$this->sub_type);
      ajaxUpdateItemOnSelectEvent("dropdown_criteria$rand","criteria_span",
                                  $CFG_GLPI["root_doc"]."/ajax/rulecriteria.php",$params,false);

      return key($items);
   }

   /**
    * Display the dropdown of the actions for the rule
    *
    * @param $used already used actions
    *
    * @return the initial value (first non used)
    */
   function dropdownActions($used=array()) {
      global $CFG_GLPI;

      $items=array();
      $value='';
      foreach ($this->getFilteredActions() as $ID => $act) {
         $items[$ID]=$act['name'];
         if (empty($value) && !isset($used[$ID])) {
            $value=$ID;
         }
      }

      $rand=dropdownArrayValues("field", $items, $value, $used);
      $params = array('field'=>'__VALUE__',
                      'sub_type'=>$this->sub_type);
      ajaxUpdateItemOnSelectEvent("dropdown_field$rand","action_span",
                                  $CFG_GLPI["root_doc"]."/ajax/ruleaction.php",$params,false);

      return $value;
   }

   /**
    * Filter actions if needed
    *  @param $actions the actions array
    *  @param $new_action indicates if the function is called when adding a new action
    *  or when displaying an already added action
    * @return the filtered actions array
    */
   function filterActions($actions) {
      return $actions;
   }

   /**
    * Get the criterias array definition
    * @return the criterias array
   **/
   function getCriterias() {
      global $RULES_CRITERIAS;

      if (isset($RULES_CRITERIAS[$this->sub_type])) {
         return $RULES_CRITERIAS[$this->sub_type];
      } else {
         return array();
      }
   }

   function getFilteredActions() {
      global $RULES_ACTIONS;

      if (isset($RULES_ACTIONS[$this->sub_type])) {
         return $this->filterActions($RULES_ACTIONS[$this->sub_type]);
      } else {
         return array();
      }
   }

   /**
    * Get the actions array definition
    * filter_action true : list all the available actions, false : list all the actions for this type of rule
    * @return the actions array
    */
   function getActions() {
      global $RULES_ACTIONS;

      if (isset($RULES_ACTIONS[$this->sub_type])) {
         return $RULES_ACTIONS[$this->sub_type];
      } else {
         return array();
      }
   }

   /**
    * Get a criteria description by his ID
    * @param $ID the criteria's ID
    * @return the criteria array
   **/
   function getCriteria($ID) {

      $criterias=$this->getCriterias();
      if (isset($criterias[$ID])) {
         return $criterias[$ID];
      }
      return array();
   }

   /**
    * Get a action description by his ID
    * @param $ID the action's ID
    * @return the action array
   **/
   function getAction($ID) {

      $actions=$this->getActions();
      if (isset($actions[$ID])) {
         return $actions[$ID];
      }
      return array();
   }

   /**
    * Get a criteria description by his ID
    * @param $ID the criteria's ID
    * @return the criteria's description
   **/

   function getCriteriaName($ID) {

      $criteria=$this->getCriteria($ID);
      if (isset($criteria['name'])) {
         return $criteria['name'];
      }
      return "&nbsp;";
   }

   /**
    * Get a action description by his ID
    * @param $ID the action's ID
    * @return the action's description
    */
   function getActionName($ID) {

      $action=$this->getAction($ID);
      if (isset($action['name'])) {
         return $action['name'];
      }
      return "&nbsp;";
   }

   /**
   * Process the rule
   * @param $input the input data used to check criterias
   * @param $output the initial ouput array used to be manipulate by actions
   * @param $params parameters for all internal functions
   * @return the output array updated by actions. If rule matched add field _rule_process to return value
   */
   function process(&$input,&$output,&$params) {

      if (count($this->criterias)) {
         $regex_result=array();
         $input=$this->prepareInputDataForProcess($input,$params);

         if ($this->checkCriterias($input,$regex_result)) {
            $output=$this->executeActions($output,$params,$regex_result);

            //Hook
            $hook_params["sub_type"]=$this->sub_type;
            $hook_params["ruleid"]=$this->fields["id"];
            $hook_params["input"]=$input;
            $hook_params["output"]=$output;
            doHook("rule_matched",$hook_params);
            $output["_rule_process"]=true;
         } else {
            $output["_no_rule_matches"] = true;
         }
      }
   }

   /**
    * Check criterias
    * @param $input the input data used to check criterias
    * @param $regex_result
    * @return boolean if criterias match
   **/
   function checkCriterias($input,&$regex_result) {

      $doactions=false;
      reset($this->criterias);
      if ($this->fields["match"]==AND_MATCHING) {
         $doactions=true;
         foreach ($this->criterias as $criteria) {
            $doactions &= $this->checkCriteria($criteria,$input,$regex_result);
            if (!$doactions) {
               break;
            }
         }
      } else { // OR MATCHING
         $doactions=false;
         foreach ($this->criterias as $criteria) {
            $doactions |= $this->checkCriteria($criteria,$input,$regex_result);
            if ($doactions) {
               break;
            }
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
   function testCriterias($input,&$regex_result,&$check_results) {

      reset($this->criterias);

      foreach ($this->criterias as $criteria) {
         $result = $this->checkCriteria($criteria,$input,$regex_result);
         $check_results[$criteria->fields["id"]]["name"]=$criteria->fields["criteria"];
         $check_results[$criteria->fields["id"]]["value"]=$criteria->fields["pattern"];
         $check_results[$criteria->fields["id"]]["result"]=((!$result)?0:1);
         $check_results[$criteria->fields["id"]]["id"]=$criteria->fields["id"];
      }
   }

   /**
    * Process a criteria of a rule
    * @param $criteria criteria to check
    * @param $input the input data used to check criterias
    * @param $regex_result
   **/
   function checkCriteria(&$criteria,&$input,&$regex_result) {

      // Undefine criteria field : set to blank
      if (!isset($input[$criteria->fields["criteria"]])) {
         $input[$criteria->fields["criteria"]]='';
      }
      //If the value is not an array
      if (!is_array($input[$criteria->fields["criteria"]])) {
         $value=$this->getCriteriaValue($criteria->fields["criteria"],$criteria->fields["condition"],
                                        $input[$criteria->fields["criteria"]]);

         // TODO Store value in temp array : $criteria->fields["criteria"] / $criteria->fields["condition"] -> value
         // TODO : Clean on update action
         $res = matchRules($value,$criteria->fields["condition"],$criteria->fields["pattern"],
                           $regex_result);
      } else {
         //If the value if, in fact, an array of values
         // Negative condition : Need to match all condition (never be)
         if (in_array($criteria->fields["condition"],array(PATTERN_IS_NOT,
                                                           PATTERN_NOT_CONTAIN,
                                                           REGEX_NOT_MATCH))) {
            $res = true;
            foreach($input[$criteria->fields["criteria"]] as $tmp) {
               $value=$this->getCriteriaValue($criteria->fields["criteria"],
                                              $criteria->fields["condition"],$tmp);
               $res &= matchRules($value,$criteria->fields["condition"],$criteria->fields["pattern"],
                                  $regex_result);
               if (!$res) {
                  break;
               }
            }
         // Positive condition : Need to match one
         } else {
            $res = false;
            foreach($input[$criteria->fields["criteria"]] as $crit) {
               $value=$this->getCriteriaValue($criteria->fields["criteria"],
                                              $criteria->fields["condition"],$crit);
               $res |= matchRules($value,$criteria->fields["condition"],$criteria->fields["pattern"],
                                  $regex_result);
               //if ($res) {
               //   break;
               //}
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
   function prepareInputDataForProcess($input,$params) {
      return $input;
   }

   /**
   * Execute the actions as defined in the rule
   * @param $output the fields to manipulate
   * @param $params parameters
   * @param $regex_results
   * @return the $output array modified
   */
   function executeActions($output,$params,$regex_results) {

      if (count($this->actions)) {
         foreach ($this->actions as $action) {
            switch ($action->fields["action_type"]) {
               case "assign" :
                  $output[$action->fields["field"]] = $action->fields["value"];
                  break;

               case "regex_result" :
               case "append_regex_result" :
                  //Regex result : assign value from the regex
                  //Append regex result : append result from a regex
                  if ($action->fields["action_type"] == "append_regex_result") {
                     $res=(isset($params[$action->fields["field"]])?$params[$action->fields["field"]]:"");
                  } else {
                     $res="";
                  }
                  $res .= getRegexResultById($action->fields["value"],$regex_results);
                  $output[$action->fields["field"]]=$res;
                  break;
            }
         }
      }
      return $output;
   }

   function cleanDBonPurge($ID) {
      global $DB;

      // Delete a rule and all associated criterias and actions
      $sql = "DELETE
              FROM `glpi_rulesactions`
              WHERE `rules_id` = '$ID'";
      $DB->query($sql);

      $sql = "DELETE
              FROM `glpi_rulescriterias`
              WHERE `rules_id` = '$ID'";
      $DB->query($sql);
   }

   /**
   * Show the minimal form for the rule
   * @param $target link to the form page
   * @param $first is it the first rule ?
   * @param $last is it the last rule ?
   */
   function showMinimalForm($target,$first=false,$last=false) {
      global $LANG,$CFG_GLPI;

      $canedit = haveRight($this->right,"w");
      echo "<tr class='tab_bg_1'>";
      if ($canedit) {
         echo "<td width='10'>";
         $sel = "";
         if (isset ($_GET["select"]) && $_GET["select"] == "all") {
            $sel = "checked";
         }
         echo "<input type='checkbox' name='item[" . $this->fields["id"] . "]' value='1' $sel>";
         echo "</td>";
      } else {
         echo "<td></td>";
      }
      echo "<td><a href=\"".str_replace(".php",".form.php",$target)."?id=".$this->fields["id"].
                 "&amp;onglet=1\">" . $this->fields["name"] . "</a> ";
      if (!empty($this->fields["comment"])) {
         echo "<img alt='' src='".$CFG_GLPI["root_doc"]."/pics/aide.png' ".
               "onmouseout=\"cleanhide('comment_rules".$this->fields["id"]."')\" ".
               "onmouseover=\"cleandisplay('comment_rules".$this->fields["id"]."')\" >";
         echo "<span class='over_link' id='comment_rules".$this->fields["id"]."'>".
                nl2br($this->fields["comment"])."</span>";
      }
      echo "</td>";
      echo "<td>".$this->fields["description"]."</td>";
      echo "<td>".getYesNo($this->fields["is_active"])."</td>";

      if ($this->can_sort && !$first && $canedit) {
         echo "<td><a href=\"".$target."?type=".$this->fields["sub_type"]."&amp;action=up&amp;id=".
                    $this->fields["id"]."\">";
         echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/deplier_up.png\" alt=''></a></td>";
      } else {
      echo "<td>&nbsp;</td>";
      }
      if ($this->can_sort && !$last && $canedit) {
         echo "<td><a href=\"".$target."?type=".$this->fields["sub_type"]."&amp;action=down&amp;id=".
                    $this->fields["id"]."\">";
         echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/deplier_down.png\" alt=''></a></td>";
      } else {
         echo "<td>&nbsp;</td>";
      }
      echo "</tr>\n";
   }

   function prepareInputForAdd($input) {

      // Before adding, add the ranking of the new rule
      $input["ranking"] = $this->getNextRanking();
      return $input;
   }

   /**
    * Get the next ranking for a specified rule
    */
   function getNextRanking() {
      global $DB;

      $sql = "SELECT max(`ranking`) AS rank
              FROM `glpi_rules`
              WHERE `sub_type` = '".$this->sub_type."'";
      $result = $DB->query($sql);

      if ($DB->numrows($result) > 0) {
         $datas = $DB->fetch_assoc($result);
         return $datas["rank"] + 1;
      }
      return 0;
   }

   /**
    * Show the minimal form for the action rule
   * @param $fields datas used to display the action
   * @param $canedit can edit the actions rule ?
    */
   function showMinimalActionForm($fields,$canedit) {

      echo "<tr class='tab_bg_1'>";
      if ($canedit) {
         echo "<td width='10'>";
         $sel = "";
         if (isset ($_GET["select"]) && $_GET["select"] == "all"){
            $sel = "checked";
         }
         echo "<input type='checkbox' name='item[" . $fields["id"] . "]' value='1' $sel>";
         echo "</td>";
      }
      $this->showMinimalAction($fields,$canedit);
      echo "</tr>\n";
   }

   function preProcessResults($results) {
      return $results;
   }

   /**
   * Show preview result of a rule
   * @param $target where to go if action
   * @param $input input data array
   * @param $params params used (see addSpecificParamsForPreview)
   */
   function showRulePreviewResultsForm($target,$input,$params) {
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
      echo "<tr><th colspan='4'>" . $LANG['rulesengine'][82] . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center b'>".$LANG['rulesengine'][16]."</td>";
      echo "<td class='center b'>".$LANG['rulesengine'][14]."</td>";
      echo "<td class='center b'>".$LANG['rulesengine'][15]."</td>";
      echo "<td class='center b'>".$LANG['rulesengine'][41]."</td>";
      echo "</tr>\n";

      foreach ($check_results as $ID=>$criteria_result) {
         echo "<tr class='tab_bg_1'>";
         $criteria->getFromDB($criteria_result["id"]);
         $this->showMinimalCriteria($criteria->fields);
         echo "<td class='b'>".getYesNo($criteria_result["result"])."</td></tr>\n";
      }
      echo "</table>";

      $global_result =(isset($output["_rule_process"])?1:0);

      echo "<br><table class='tab_cadrehov'>";
      echo "<tr><th colspan='2'>" . $LANG['rulesengine'][81] . "</th></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td class='center' colspan='2'>".$LANG['rulesengine'][41]." : ";
      echo "<strong> ".getYesNo($global_result)."</strong></td>";

      $output = $this->preProcessPreviewResults($output);

      foreach ($output as $criteria => $value) {
         echo "<tr class='tab_bg_2'>";
         echo "<td>".$RULES_ACTIONS[$this->sub_type][$criteria]["name"]."</td>";
         echo "<td>";
         echo $this->getActionValue($criteria,$value);
         echo "</td></tr>\n";
      }

      //If a regular expression was used, and matched, display the results
      if (count($regex_results)) {
         echo "<tr class='tab_bg_2'>";
         echo "<td>".$LANG['rulesengine'][85]."</td>";
         echo "<td>";
         printCleanArray($regex_results);
         echo "</td></tr>\n";
      }
      echo "</tr>\n";
      echo "</table></div>";
   }

   /**
    * Show the minimal form for the criteria rule
   * @param $fields datas used to display the criteria
   * @param $canedit can edit the criterias rule ?
    */
   function showMinimalCriteriaForm($fields,$canedit) {

      echo "<tr class='tab_bg_1'>";
      if ($canedit) {
         echo "<td width='10'>";
         $sel = "";
         if (isset ($_GET["select"]) && $_GET["select"] == "all") {
            $sel = "checked";
         }
         echo "<input type='checkbox' name='item[" . $fields["id"] . "]' value='1' $sel>";
         echo "</td>";
      }
      $this->showMinimalCriteria($fields);
      echo "</tr>\n";
   }

   /**
   * Show the minimal infos for the criteria rule
   * @param $fields datas used to display the criteria
   */
   function showMinimalCriteria($fields) {

      echo "<td>" . $this->getCriteriaName($fields["criteria"]) . "</td>";
      echo "<td>" . getConditionByID($fields["condition"]) . "</td>";
      echo "<td>" . $this->getCriteriaDisplayPattern($fields["criteria"],$fields["condition"],
                                                     $fields["pattern"]) . "</td>";
   }

   /**
   * Show the minimal infos for the action rule
   * @param $fields datas used to display the action
   * @param $canedit right to edit ?
   */
   function showMinimalAction($fields,$canedit) {

      echo "<td>" . $this->getActionName($fields["field"]) . "</td>";
      echo "<td>" . getActionByID($fields["action_type"]) . "</td>";
      echo "<td>" . stripslashes($this->getActionValue($fields["field"],$fields["value"])) . "</td>";
   }

   /**
   * Return a value associated with a pattern associated to a criteria to display it
   * @param $ID the given criteria
   * @param $condition condition used
   * @param $pattern the pattern
   */
   function getCriteriaDisplayPattern($ID,$condition,$pattern) {

      $crit=$this->getCriteria($ID);
      if (isset($crit['type'])
          && ($condition==PATTERN_IS || $condition==PATTERN_IS_NOT)) {
         switch ($crit['type']) {
            case "dropdown" :
               return getDropdownName($crit["table"],$pattern);

            case "dropdown_users" :
               return getUserName($pattern);

            case "dropdown_tracking_itemtype" :
               $ci =new CommonItem();
               $ci->setType($pattern);
               return $ci->getType($pattern);

            case "dropdown_priority" :
               return getPriorityName($pattern);
         }
      }
      return $pattern;
   }

   /**
   * Display item used to select a pattern for a criteria
   * @param $name criteria name
   * @param $ID the given criteria
   * @param $condition condition used
   * @param $value the pattern
   * @param $test Is to test rule ?
   */
   function displayCriteriaSelectPattern($name,$ID,$condition,$value="",$test=false) {
      global $CFG_GLPI;

      $crit=$this->getCriteria($ID);
      $display=false;
      if (isset($crit['type'])
          && ($test||$condition==PATTERN_IS || $condition==PATTERN_IS_NOT)) {
         switch ($crit['type']) {
            case "dropdown" :
               dropdownValue($crit['table'],$name,$value);
               $display=true;
               break;

            case "dropdown_users" :
               dropdownAllUsers($name,$value);
               $display=true;
               break;

            case "dropdown_tracking_itemtype" :
               dropdownDeviceTypes($name,0,array_keys(getAllTypesForHelpdesk()));
               $display=true;
               break;

            case "dropdown_priority" :
               dropdownPriority($name,$value);
               $display=true;
               break;
         }
      }
      if (!$display) {
         autocompletionTextField($name, "glpi_rulescriterias", "pattern", $value, 40);
      }
   }

   /**
   * Return a value associated with a pattern associated to a criteria
   * @param $ID the given action
   * @param $value the value
   */
   function getActionValue($ID,$value) {
      global $LANG;

      $action=$this->getAction($ID);
      if (isset($action['type'])) {
         switch ($action['type']) {
            case "dropdown" :
               return getDropdownName($action["table"],$value);

            case "dropdown_status" :
               return getStatusName($value);

            case "dropdown_assign" :
            case "dropdown_users" :
               return getUserName($value);

            case "yesno" :
               if ($value) {
                  return $LANG['choice'][1];
               } else {
                  return $LANG['choice'][0];
               }

            case "dropdown_priority" :
               return getPriorityName($value);
         }
      }
      return $value;
   }

   /**
    * Return a value associated with a pattern associated to a criteria to display it
    * @param $ID the given criteria
    * @param $condition condition used
    * @param $value the pattern
    */
   function getCriteriaValue($ID,$condition,$value) {
      global $LANG;

      $crit=$this->getCriteria($ID);
      if (isset($crit['type'])
          && ($condition!=PATTERN_IS && $condition!=PATTERN_IS_NOT)) {
         switch ($crit['type']) {
            case "dropdown" :
               return getDropdownName($crit["table"],$value);

            case "dropdown_assign" :
            case "dropdown_users" :
               return getUserName($value);

            case "yesno" :
               if ($value) {
                  return $LANG['choice'][1];
               } else {
                  return $LANG['choice'][0];
               }

            case "dropdown_priority" :
               return getPriorityName($value);
         }
      }
      return $value;
   }

   /**
    * Function used to display type specific criterias during rule's preview
    * @param $fields fields values
    */
   function showSpecificCriteriasForPreview($fields) {
   }

   /**
    ** Function used to add specific params before rule processing
    * @param $fields fields values
    * @param $params parameters
    */
   function addSpecificParamsForPreview($fields,$params) {
      return $params;
   }

   /**
    * Criteria form used to preview rule
    * @param $target target of the form
    * @param $rules_id ID of the rule
    */
   function showRulePreviewCriteriasForm($target,$rules_id) {
      global $DB, $LANG,$RULES_CRITERIAS,$RULES_ACTIONS;

      if ($this->getRuleWithCriteriasAndActions($rules_id,1,0)) {
         echo "<form name='testrule_form' id='testrule_form' method='post' action=\"$target\">\n";
         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='3'>" . $LANG['rulesengine'][6] . "</th></tr>";

         $type_match=($this->fields["match"]==AND_MATCHING
                      ?$LANG['rulesengine'][42]:$LANG['rulesengine'][43]);
         $already_displayed=array();
         $first=true;
         //Brower all criterias
         foreach ($this->criterias as $criteria) {
            //Look for the criteria in the field of already displayed criteria :
            //if present, don't display it again
            if (!in_array($criteria->fields["criteria"],$already_displayed)) {
               $already_displayed[]=$criteria->fields["criteria"];
               echo "<tr class='tab_bg_1'>";
               echo "<td>";
               if ($first) {
                  echo "&nbsp;";
                  $first=false;
               } else {
                  echo $type_match;
               }
               echo "</td>";
               $criteria_constants = $RULES_CRITERIAS[$this->fields["sub_type"]]
                                                     [$criteria->fields["criteria"]];
               echo "<td>".$criteria_constants["name"].":</td>";
               echo "<td>";
               $value="";

               if (isset($_POST[$criteria->fields["criteria"]])) {
                  $value=$_POST[$criteria->fields["criteria"]];
               }
               $this->displayCriteriaSelectPattern($criteria->fields['criteria'],
                                                   $criteria->fields['criteria'],
                                                   $criteria->fields['condition'],$value,true);
               echo "</td></tr>\n";
            }
         }
         $this->showSpecificCriteriasForPreview($_POST);

         echo "<tr><td class='tab_bg_2 center' colspan='3'>";
         echo "<input type='submit' name='test_rule' value=\"" . $LANG['buttons'][50] .
               "\" class='submit'>";
         echo "<input type='hidden' name='rules_id' value='$rules_id'>";
         echo "<input type='hidden' name='sub_type' value='" . $this->sub_type . "'>";
         echo "</td></tr>\n";
         echo "</table></div></form>\n";
      }
   }

   function preProcessPreviewResults($output) {
      return $output;
   }

}


class RuleAction extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_rulesactions';

   /**
    * Get all actions for a given rule
    * @param $ID the rule_description ID
    * @return an array of RuleAction objects
   **/
   function getRuleActions($ID) {
      global $DB;

      $sql = "SELECT *
              FROM `glpi_rulesactions`
              WHERE `rules_id` = '$ID'";
      $result = $DB->query($sql);

      $rules_actions = array ();
      while ($rule = $DB->fetch_array($result)) {
         $tmp = new RuleAction;
         $tmp->fields = $rule;
         $rules_actions[] = $tmp;
      }
      return $rules_actions;
   }

   /**
    * Add an action
    * @param $action action type
    * @param $ruleid rule ID
    * @param $field field name
    * @param $value value
   **/
   function addActionByAttributes($action,$ruleid,$field,$value) {

      $ruleAction = new RuleAction;
      $input["action_type"]=$action;
      $input["field"]=$field;
      $input["value"]=$value;
      $input["rules_id"]=$ruleid;
      $ruleAction->add($input);
   }

}


/// Criteria Rule class
class RuleCriteria extends CommonDBTM {
   // From CommonDBTM
   public $table = 'glpi_rulescriterias';

   /**
    * Get all criterias for a given rule
    * @param $ID the rule_description ID
    * @return an array of RuleCriteria objects
   **/
   function getRuleCriterias($ID) {
      global $DB;

      $sql = "SELECT *
              FROM `glpi_rulescriterias`
              WHERE `rules_id` = '$ID'";
      $result = $DB->query($sql);

      $rules_list = array ();
      while ($rule = $DB->fetch_assoc($result)) {
         $tmp = new RuleCriteria;
         $tmp->fields = $rule;
         $rules_list[] = $tmp;
      }
      return $rules_list;
   }

   /**
    * Process a criteria of a rule
    * @param $input the input data used to check criterias
    * @param $regex_result
   **/
   function process(&$input,&$regex_result) {

      // Undefine criteria field : set to blank
      if (!isset($input[$this->fields["criteria"]])) {
         $input[$this->fields["criteria"]]='';
      }

      //If the value is not an array
      if (!is_array($input[$this->fields["criteria"]])) {
         $value=$this->getValueToMatch($this->fields["condition"],$input[$this->fields["criteria"]]);
         $res = matchRules($value,$this->fields["condition"],$this->fields["pattern"],$regex_result);
      } else {
         //If the value if, in fact, an array of values
         // Negative condition : Need to match all condition (never be)
         if (in_array($this->fields["condition"],array(PATTERN_IS_NOT,
                                                       PATTERN_NOT_CONTAIN,
                                                       REGEX_NOT_MATCH))) {
            $res = true;
            foreach($input[$this->fields["criteria"]] as $tmp) {
               $value=$this->getValueToMatch($this->fields["condition"],$tmp);
               $res &= matchRules($value,$this->fields["condition"],$this->fields["pattern"],
                                  $regex_result);
            }

         // Positive condition : Need to match one
         } else {
            $res = false;
            foreach($input[$this->fields["criteria"]] as $tmp) {
               $value=$this->getValueToMatch($this->fields["condition"],$tmp);
               $res |= matchRules($value,$this->fields["condition"],$this->fields["pattern"],
                                  $regex_result);
               if ($res) {
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
   function getValueToMatch($condition,&$initValue) {

      if (!empty($this->type)
          && ($condition!=PATTERN_IS && $condition!=PATTERN_IS_NOT)) {
         switch ($this->type) {
            case "dropdown" :
               return getDropdownName($this->table,$initValue);

            case "dropdown_users" :
               return getUserName($initValue);

            case "dropdown_tracking_itemtype" :
               $ci =new CommonItem();
               $ci->setType($initValue);
               return $ci->getType($initValue);

            case "dropdown_priority" :
               return getPriorityName($initValue);
         }
      }
      return $initValue;
   }

}


/// Rule cached class
class RuleCached extends Rule {

   /**
   * Constructor
   * @param sub_type the rule type used for the collection
   **/
   // Dummy constructor required for php 5.3.0 (regression ?)
   // TODO : switch to __construct ?
   function __construct($sub_type=0) {
      parent::__construct($sub_type);
   }

   /**
   * Delete cache for a rule
   * @param $ID rule ID
   **/
   function deleteCacheByRuleId($ID) {
      global $DB;

      $DB->query("DELETE
                  FROM ".getCacheTableByRuleType($this->sub_type)."
                  WHERE `rules_id` = '$ID'");
   }

   function cleanDBonPurge($ID) {
      parent::cleanDBonPurge($ID);

      $this->deleteCacheByRuleId($ID);
   }

   function post_updateItem($input,$updates,$history=1) {

      if (isset($updates['match'])) {
         $this->deleteCacheByRuleId($input["id"]);
      }
   }

   /**
   * Show cache statis for a current rule
   * @param $target where to go
   **/
   function showCacheStatusByRule($target) {
      global $DB,$LANG;

      echo "<div class='center'>";
      echo "<table  class='tab_cadre_fixe'>";
      $rulecollection = getRuleCollectionClass($this->sub_type);

      $query = "SELECT *
                FROM `".$rulecollection->cache_table."`, `glpi_rules`
                WHERE `".$rulecollection->cache_table."`.`rules_id` = `glpi_rules`.`id`
                      AND `".$rulecollection->cache_table."`.`rules_id` = '".$this->fields["id"]."'
                ORDER BY `name`";

      $res_count=$DB->query($query);
      $this->showCacheRuleHeader();

      while ($datas = $DB->fetch_array($res_count)) {
         echo "<tr>";
         $this->showCacheRuleDetail($datas);
         echo "</tr>\n";
      }

      echo "</table><br><br>\n";
      echo "<a href=\"$target\">".$LANG['buttons'][13]."</a></div>";
   }

   /// Display Header for cache display
   function showCacheRuleHeader() {
      global $LANG;

      echo "<tr><th colspan='2'>".$LANG['rulesengine'][100]."&nbsp;: ".$this->fields["name"];
      echo "</th></tr>\n";
      echo "<tr><td class='tab_bg_1'>".$LANG['rulesengine'][104]."</td>";
      echo "<td class='tab_bg_1'>".$LANG['rulesengine'][105]."</td></tr>";
   }

   /**
    * Display a cache item
    * @param $fields data array
   **/
   function showCacheRuleDetail($fields) {
      global $LANG;

      echo "<td class='tab_bg_2'>".$fields["old_value"]."</td>";
      echo "<td class='tab_bg_2'>".($fields["new_value"]!=''
             ?$fields["new_value"]:$LANG['rulesengine'][106])."</td>";
   }

}


/**
 * Specific rule collection for dictionnary : got a function initialize rule's caching system
 *
**/
class RuleCachedCollection extends RuleCollection {
   /// Cache table used
   var $cache_table;
   /// Cache parameters
   var $cache_params;

   /**
   * Init a cache rule collection
   * @param $cache_table cache table used
   * @param $input_params Input parameters to store
   * @param $output_params Output parameters to store
   * @return nothing
   **/
   function initCache($cache_table,$input_params=array("name"=>"old_value"),
                      $output_params=array("name"=>"new_value")) {

      $this->can_replay_rules=true;
      $this->stop_on_first_match=true;
      $this->cache_table=$cache_table;
      $this->cache_params["input_value"]=$input_params;
      $this->cache_params["output_value"]=$output_params;
   }

   /**
   * Show the list of rules
   * @param $target  where to go
   * @return nothing
   **/
   function showAdditionalInformationsInForm($target) {
      global $CFG_GLPI,$LANG;

      echo "<span class='icon_consol'>";
      echo "<a href='#' onClick=\"var w = window.open('".$CFG_GLPI["root_doc"].
            "/front/popup.php?popup=show_cache&amp;sub_type=".$this->sub_type."' ,'glpipopup', ".
            "'height=400, width=1000, top=100, left=100, scrollbars=yes' );w.focus();\">".
            $LANG['rulesengine'][100]."</a></span>";
   }

   /**
   * Process all the rules collection
   * @param input the input data used to check criterias
   * @param output the initial ouput array used to be manipulate by actions
   * @param params parameters for all internal functions
   * @param force_no_cache don't write rule's result into cache (for preview mode mainly)
   * @return the output array updated by actions
   **/
   function processAllRules($input=array(),$output=array(),$params=array(),$force_no_cache=false) {

      //If cache enabled : try to get value from the cache
      $new_values = $this->checkDataInCache($input);

      if ($new_values != RULE_NOT_IN_CACHE) {
         $output["_rule_process"]=true;
         return array_merge($output,$new_values);
      }
      $output=parent::processAllRules($input,$output,$params);

      if (!$force_no_cache&&isset($output["_ruleid"])) {
         $this->insertDataInCache($input,$output);
         unset($output["_ruleid"]);
      }

      return $output;
   }

   /**
   * Show cache status by rules
   **/
   function showCacheStatusForRuleType() {
      global $DB,$LANG,$CFG_GLPI;

      echo "<div class='center'>";
      echo "<table  class='tab_cadre_fixe'>";

      $query = "SELECT `name`, `rules_id`, count(`rules_id`) AS cpt
                FROM `".$this->cache_table."`, `glpi_rules`
                WHERE `".$this->cache_table."`.`rules_id`=`glpi_rules`.`id`
                GROUP BY `rules_id`
                ORDER BY `name`";
      $res_count=$DB->query($query);

      echo "<tr><th colspan='2'>".$LANG['rulesengine'][100]."&nbsp;: ".$this->getTitle()."</th></tr>\n";
      echo "<tr><td class='tab_bg_1'>".$LANG['rulesengine'][102]."</td>";
      echo "<td class='tab_bg_1'>".$LANG['rulesengine'][103]."</td></tr>\n";

      $total = 0;
      while ($datas = $DB->fetch_array($res_count)) {
         echo "<tr><td class='tab_bg_2'>";
         echo "<a href='/front/popup.php?popup=show_cache&amp;sub_type=".$this->sub_type.
              "&amp;rules_id=".$datas["rules_id"]."' >";
         echo $datas["name"];
         echo "</a></td>";
         echo "<td class='tab_bg_2'>".$datas["cpt"]."</td></tr>\n";
         $total+=$datas["cpt"];
      }
      echo "<tr>\n";
      echo "<td class='tab_bg_2 b'>".$LANG['common'][33]." (".$DB->numrows($res_count).")</td>";
      echo "<td class='tab_bg_2 b'>".$total."</td>";
      echo "</tr></table></div>\n";
   }

   /**
   * Check if a data is in cache
   * @param input data array to search
   * @return boolean : is in cache ?
   **/
   function checkDataInCache($input) {
      global $DB;

      $where="";
      $first=true;
      foreach($this->cache_params["input_value"] as $param => $value) {
         if (isset($input[$param])) {
            $where .= (!$first?" AND ":"")." `".$value."`='".$input[$param]."'";
            $first=false;
         }
      }
      $sql = "SELECT *
              FROM `".$this->cache_table."`
              WHERE ".$where;

      if ($res_check = $DB->query($sql)) {
         $output_values=array();
         if ($DB->numrows($res_check) == 1) {
            $data=$DB->fetch_assoc($res_check);
            foreach ($this->cache_params["output_value"] as $param => $param_value) {
               if (isset($data[$param_value])) {
                  $output_values[$param]=$data[$param_value];
               }
            }
            return $output_values;
         }
      }
      return RULE_NOT_IN_CACHE;
   }

   /**
   * Insert data in cache
   * @param input input data array
   * @param $output output data array
   **/
   function insertDataInCache($input,$output) {
      global $DB;

      $old_values="";
      $into_old="";
      foreach($this->cache_params["input_value"] as $param => $value) {
         $into_old .= "`".$value."`, ";
         // Input are slashes protected...
         $old_values .= "'".$input[$param]."', ";
      }

      $into_new="";
      $new_values="";
      foreach($this->cache_params["output_value"] as $param => $value) {
         if (!isset($output[$param])) {
            $output[$param]="";
         }
         $into_new .= ", `".$value."`";
         // Output are not slashes protected...
         $new_values .= " ,'".addslashes($output[$param])."'";
      }

      $sql = "INSERT INTO
              `".$this->cache_table."` (".$into_old."`rules_id`".$into_new.")
              VALUES (".$old_values.$output["_ruleid"].$new_values.")";
      $DB->query($sql);
   }


}

?>
