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

/**
 * Rule class store all informations about a GLPI rule :
 *   - description
 *   - criterias
 *   - actions
**/
class Rule extends CommonDBTM {
   // From CommonDBTM
   public $table = 'glpi_rules';
   public $type = 'Rule';

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
      if ($sub_type > 0) {
         $this->sub_type=$sub_type;
      }
   }

   static function canCreate() {
      return haveRight('config', 'w');
   }

   static function canView() {
      return haveRight('config', 'r');
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
      Dropdown::showYesNo("is_active",$this->fields["is_active"]);
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
         openArrowMassive("actionsform", true);
         echo "<input type='hidden' name='rules_id' value='$rules_id'>";
         closeArrowMassive('delete_action', $LANG['buttons'][6]);
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
         openArrowMassive("criteriasform", true);
         echo "<input type='hidden' name='rules_id' value='$rules_id'>";
         closeArrowMassive('delete_criteria', $LANG['buttons'][6]);
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
              FROM `glpi_ruleactions`
              WHERE `rules_id` = '$ID'";
      $DB->query($sql);

      $sql = "DELETE
              FROM `glpi_rulecriterias`
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
         if (isset($RULES_ACTIONS[$this->sub_type][$criteria])) {
            echo "<tr class='tab_bg_2'>";
            echo "<td>".$RULES_ACTIONS[$this->sub_type][$criteria]["name"]."</td>";
            echo "<td>";
            echo $this->getActionValue($criteria,$value);
            echo "</td></tr>\n";
         }
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
      global $LANG;

      $crit=$this->getCriteria($ID);
      if (isset($crit['type'])
          && ($condition==PATTERN_IS || $condition==PATTERN_IS_NOT)) {
         switch ($crit['type']) {
            case "dropdown" :
               return Dropdown::getDropdownName($crit["table"],$pattern);

            case "dropdown_users" :
               return getUserName($pattern);

            case "dropdown_tracking_itemtype" :
               if (class_exists($pattern)) {
                  $item= new $pattern();
                  return $item->getTypeName();
               } else {
                  if (empty($pattern)) {
                     return $LANG['help'][30];
                  }
               }
               break;
            case "dropdown_priority" :
               return Ticket::getPriorityName($pattern);

            case "dropdown_urgency" :
               return Ticket::getUrgencyName($pattern);

            case "dropdown_impact" :
               return Ticket::getImpactName($pattern);
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
               Dropdown::dropdownValue($crit['table'],$name,$value);
               $display=true;
               break;

            case "dropdown_users" :
               User::dropdownAllUsers($name,$value);
               $display=true;
               break;

            case "dropdown_tracking_itemtype" :
               Dropdown::dropdownTypes($name,0,array_keys(getAllTypesForHelpdesk()));
               $display=true;
               break;

            case "dropdown_urgency" :
               Ticket::dropdownUrgency($name,$value);
               $display=true;
               break;

            case "dropdown_impact" :
               Ticket::dropdownImpact($name,$value);
               $display=true;
               break;

            case "dropdown_priority" :
               Ticket::dropdownPriority($name,$value);
               $display=true;
               break;
         }
      }
      if (!$display) {
         autocompletionTextField($name, "glpi_rulecriterias", "pattern", $value, 40);
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
               return Dropdown::getDropdownName($action["table"],$value);

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
               return Ticket::getPriorityName($value);
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
               return Dropdown::getDropdownName($crit["table"],$value);

            case "dropdown_assign" :
            case "dropdown_users" :
               return getUserName($value);

            case "yesno" :
               if ($value) {
                  return $LANG['choice'][1];
               } else {
                  return $LANG['choice'][0];
               }

            case "dropdown_impact" :
               return Ticket::getImpactName($value);

            case "dropdown_urgency" :
               return Ticket::getUrgencyName($value);

            case "dropdown_priority" :
               return Ticket::getPriorityName($value);
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


?>
