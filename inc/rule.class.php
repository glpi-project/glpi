<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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

   public $dohistory=true;

   // Specific ones
   ///Actions affected to this rule
   var $actions = array();
   ///Criterias affected to this rule
   var $criterias = array();
   /// Right needed to use this rule
   var $right='config';
   /// Rules can be sorted ?
   var $can_sort=false;
   /// field used to order rules
   var $orderby='ranking';

   var $specific_parameters = false;

   var $regex_results = array();
   var $criterias_results = array();

   const RULE_NOT_IN_CACHE = -1;
   const RULE_WILDCARD = '*';

//Generic rules engine
   const PATTERN_IS = 0;
   const PATTERN_IS_NOT = 1;
   const PATTERN_CONTAIN = 2;
   const PATTERN_NOT_CONTAIN = 3;
   const PATTERN_BEGIN = 4;
   const PATTERN_END = 5;
   const REGEX_MATCH = 6;
   const REGEX_NOT_MATCH = 7;

   const AND_MATCHING = "AND";
   const OR_MATCHING = "OR";
   /**
   * Constructor
   **/
   function __construct() {
      // Temproray hack for this class
      $this->forceTable('glpi_rules');
   }

   function isEntityAssign() {
      return false;
   }

   function canCreate() {
      return haveRight('config', 'w');
   }

   function canView() {
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

   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['linkfield']     = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();

      $tab[3]['table']     = $this->getTable();
      $tab[3]['field']     = 'ranking';
      $tab[3]['linkfield'] = '';
      $tab[3]['name']      = $LANG['rulesengine'][10];
      $tab[3]['datatype']  = 'bool';

      $tab[4]['table']     = $this->getTable();
      $tab[4]['field']     = 'description';
      $tab[4]['linkfield'] = '';
      $tab[4]['name']      = $LANG['joblist'][6];
      $tab[4]['datatype']  = 'text';

      $tab[5]['table']     = $this->getTable();
      $tab[5]['field']     = 'match';
      $tab[5]['linkfield'] = '';
      $tab[5]['name']      = $LANG['rulesengine'][9];
      $tab[5]['datatype']  = 'text';

      $tab[8]['table']     = $this->getTable();
      $tab[8]['field']     = 'is_active';
      $tab[8]['linkfield'] = 'is_active';
      $tab[8]['name']      = $LANG['common'][60];
      $tab[8]['datatype']  = 'bool';

      $tab[16]['table']     = $this->getTable();
      $tab[16]['field']     = 'comment';
      $tab[16]['linkfield'] = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      $tab[80]['table']     = 'glpi_entities';
      $tab[80]['field']     = 'completename';
      $tab[80]['linkfield'] = 'entities_id';
      $tab[80]['name']      = $LANG['entity'][0];

      $tab[86]['table']     = $this->getTable();
      $tab[86]['field']     = 'is_recursive';
      $tab[86]['linkfield'] = 'is_recursive';
      $tab[86]['name']      = $LANG['entity'][9];
      $tab[86]['datatype']  = 'bool';

      return $tab;
   }

   /**
   * Show the rule
   *
   * @param $ID ID of the rule
   * @param $options array
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
   *
   * @return nothing
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI, $LANG;


      if (!$this->isNewID($ID)) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
      }

      $canedit=$this->can($this->right,"w");

      $this->showTabs($options);
      $this->showFormHeader($options);
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this,"name");
      echo "</td>";
      echo "<td>".$LANG['joblist'][6]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this,"description");
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
      echo "<textarea cols='110' rows='3' name='comment' >".$this->fields["comment"]."</textarea>";
      if (!$this->isNewID($ID)) {
         echo "<br>".$LANG['common'][26]."&nbsp;: ";
         echo ($this->fields["date_mod"] ? convDateTime($this->fields["date_mod"]) : $LANG['setup'][307]);
      }
      echo"</td></tr>\n";

      if ($canedit) {
         echo "<input type='hidden' name='ranking' value='".$this->fields["ranking"]."'>";
         echo "<input type='hidden' name='sub_type' value='".get_class($this)."'>";

         if ($ID > 0) {
            echo "<tr><td class='tab_bg_2 center' colspan='4'>";
            echo "<a href='#' onClick=\"var w=window.open('".$CFG_GLPI["root_doc"].
                "/front/popup.php?popup=test_rule&amp;sub_type=".$this->getType()."&amp;rules_id=".
                $this->fields["id"]."' ,'glpipopup', 'height=400, width=1000, top=100, left=100,".
                " scrollbars=yes' );w.focus();\">".$LANG['buttons'][50]."</a>";
            echo "</td></tr>\n";
         }
      }

      $this->showFormButtons($options);
      $this->addDivForTabs();
      return true;
   }

   /**
   * Display a dropdown with all the rule matching
   * @param $name dropdown name
   * @param $value default value
   **/
   function dropdownRulesMatch($name,$value='') {
      global $LANG;

      $elements[Rule::AND_MATCHING] = $LANG['rulesengine'][42];
      $elements[Rule::OR_MATCHING] = $LANG['rulesengine'][43];
      return Dropdown::showFromArray($name,$elements,array('value' => $value));
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

      foreach($this->getActions() as $key => $val) {
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
    * @param $rules_id  rule ID
   **/
   function showActionsList($rules_id) {
      global $CFG_GLPI, $LANG;

      $canedit = $this->can($rules_id, "w");
      $this->getTitleAction(getItemTypeFormURL(get_class($this)));

      if (($this->maxActionsCount()==0 || sizeof($this->actions) < $this->maxActionsCount())
          && $canedit) {

         echo "<form name='actionsaddform' method='post'
                action=\"".getItemTypeFormURL(get_class($this))."\">\n";
         $this->addActionForm($rules_id);
         echo "</form>";
      }

      echo "<form name='actionsform' id='actionsform'
             method='post' action=\"".getItemTypeFormURL(get_class($this))."\">\n";
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
      $val=$this->dropdownActions(RuleAction::getAlreadyUsedForRuleID($rules_id,$this->getType()));
      echo "</td><td class='left'>";
      echo "<span id='action_span'>\n";
      $_POST["sub_type"]=$this->getType();
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
      global $LANG,$CFG_GLPI;

      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>" . $LANG['rulesengine'][16] . "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td class='center'>".$LANG['rulesengine'][16] . "&nbsp;:</td><td>";
      $val=$this->dropdownCriterias();
      echo "</td><td class='left'>";
      echo "<span id='criteria_span'>\n";
      $_POST["sub_type"]=$this->getType();
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

   function maybeRecursive() {
      return false;
   }

   /**
    * Display all rules criterias
    * @param $rules_id
    */
   function showCriteriasList($rules_id) {
      global $CFG_GLPI, $LANG;

      $canedit = $this->can($rules_id, "w");
      $this->getTitleCriteria(getItemTypeFormURL(get_class($this)));

      if (($this->maxCriteriasCount()==0 || sizeof($this->criterias) < $this->maxCriteriasCount())
          && $canedit) {

         echo "<form name='criteriasaddform'method='post'
                action=\"".getItemTypeFormURL(get_class($this))."\">\n";
         $this->addCriteriaForm($rules_id);
         echo "</form>";
      }
      echo "<form name='criteriasform' id='criteriasform'
             method='post' action=\"".getItemTypeFormURL(get_class($this))."\">\n";
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
      global $CFG_GLPI,$LANG;

      $items=array();
      foreach ($this->getCriterias() as $ID => $crit) {
         $items[$ID]=$crit['name'];
      }
      asort($items);
      $rand=Dropdown::showFromArray("criteria", $items);
      $params = array('criteria' => '__VALUE__',
                      'rand'=>$rand,
                      'sub_type' => $this->getType());
      ajaxUpdateItemOnSelectEvent("dropdown_criteria$rand","criteria_span",
                                  $CFG_GLPI["root_doc"]."/ajax/rulecriteria.php",$params,false);
      if ($this->specific_parameters) {
               $itemtype = get_class($this).'Parameter';
               echo "<img alt='' title=\"".$LANG['rulesengine'][140]."\" src='".$CFG_GLPI["root_doc"].
                     "/pics/add_dropdown.png' style='cursor:pointer; margin-left:2px;'
                     onClick=\"var w = window.open('".getItemTypeFormURL($itemtype).
                     "?popup=1&amp;rand=".$params['rand']."' ,'glpipopup', 'height=400, ".
                     "width=1000, top=100, left=100, scrollbars=yes' );w.focus();\">";
      }

      return key($items);
   }

   /**
    * Get all ldap rules criterias from the DB and add them into the RULES_CRITERIAS
    */
   function addSpecificCriteriasToArray(&$criterias) {
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

      $actions=$this->getActions();
      // Complete used array with duplicate items
      // add duplicates of used items
      foreach ($used as $ID) {
         if (isset($actions[$ID]['duplicatewith'])) {
            $used[$actions[$ID]['duplicatewith']] = $actions[$ID]['duplicatewith'];
         }
      }
      // Parse for duplicates of already used items
      foreach ($actions as $ID => $act) {
         if (isset($actions[$ID]['duplicatewith'])
               && in_array($actions[$ID]['duplicatewith'],$used)) {
            $used[$ID]=$ID;
         }
      }

      $items=array();
      $value='';

      foreach ($actions as $ID => $act) {
         $items[$ID]=$act['name'];
         if (empty($value) && !isset($used[$ID])) {
            $value=$ID;
         }
      }
      asort($items);

      $rand=Dropdown::showFromArray("field", $items, array('value' => $value, 'used' => $used));
      $params = array('field'    => '__VALUE__',
                      'sub_type' => $this->getType());
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
         $this->regex_results = array();
         $this->criterias_results = array();
         $input=$this->prepareInputDataForProcess($input,$params);

         if ($this->checkCriterias($input)) {
            $output=$this->executeActions($output,$params);

            //Hook
            $hook_params["sub_type"]=$this->getType();
            $hook_params["ruleid"]=$this->fields["id"];
            $hook_params["input"]=$input;
            $hook_params["output"]=$output;
            doHook("rule_matched",$hook_params);
            $output["_rule_process"]=true;
            unset($output["_no_rule_matches"]);
         }
      }
   }

   /**
    * Check criterias
    * @param $input the input data used to check criterias
    * @return boolean if criterias match
   **/
   function checkCriterias($input) {

      $doactions=false;
      reset($this->criterias);
      if ($this->fields["match"]==Rule::AND_MATCHING) {
         $doactions=true;
         foreach ($this->criterias as $criteria) {
            $doactions &= $this->checkCriteria($criteria,$input);
            if (!$doactions) {
               break;
            }
         }
      } else { // OR MATCHING
         $doactions=false;
         foreach ($this->criterias as $criteria) {
            $doactions |= $this->checkCriteria($criteria,$input);
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
   * @param $check_results
   * @return boolean if criterias match
   */
   function testCriterias($input,&$check_results) {

      reset($this->criterias);

      foreach ($this->criterias as $criteria) {
         $result = $this->checkCriteria($criteria,$input);
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
   **/
   function checkCriteria(&$criteria,&$input) {

      $partial_regex_result=array();

      // Undefine criteria field : set to blank
      if (!isset($input[$criteria->fields["criteria"]])) {
         $input[$criteria->fields["criteria"]]='';
      }
      //If the value is not an array
      if (!is_array($input[$criteria->fields["criteria"]])) {
         $value=$this->getCriteriaValue($criteria->fields["criteria"],
                                        $criteria->fields["condition"],
                                        $input[$criteria->fields["criteria"]]);

         $res = RuleCriteria::match($criteria,
                                    $value,
                                    $this->criterias_results,
                                    $partial_regex_result);
      } else {
         //If the value if, in fact, an array of values
         // Negative condition : Need to match all condition (never be)
         if (in_array($criteria->fields["condition"],array(Rule::PATTERN_IS_NOT,
                                                           Rule::PATTERN_NOT_CONTAIN,
                                                           Rule::REGEX_NOT_MATCH))) {
            $res = true;
            foreach($input[$criteria->fields["criteria"]] as $tmp) {
               $value=$this->getCriteriaValue($criteria->fields["criteria"],
                                              $criteria->fields["condition"],$tmp);
               $res &= RuleCriteria::match($criteria,
                                           $value,
                                           $this->criterias_results,
                                           $partial_regex_result);
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
               $res |= RuleCriteria::match($criteria,
                                           $value,
                                           $this->criterias_results,
                                           $partial_regex_result);
            }
         }
      }
      // Found regex on this criteria
      if (count($partial_regex_result)) {
         // No regex existing : put found
         if (!count($this->regex_results)) {
            $this->regex_results=$partial_regex_result;
         } else { // Already existing regex : append found values
            $temp_result=array();
            foreach ($partial_regex_result as $new) {
               foreach ($this->regex_results as $old) {
                  $temp_result[]=array_merge($old,$new);
               }
            }
            $this->regex_results=$temp_result;
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
   * @return the $output array modified
   */
   function executeActions($output,$params) {

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
                  $res .= RuleAction::getRegexResultById($action->fields["value"],
                                                         $this->regex_results[0]);
                  $output[$action->fields["field"]]=$res;
                  break;
            }
         }
      }
      return $output;
   }

   function cleanDBonPurge() {
      global $DB;

      // Delete a rule and all associated criterias and actions
      $sql = "DELETE
              FROM `glpi_ruleactions`
              WHERE `rules_id` = '".$this->fields['id']."'";
      $DB->query($sql);

      $sql = "DELETE
              FROM `glpi_rulecriterias`
              WHERE `rules_id` = '".$this->fields['id']."'";
      $DB->query($sql);
   }

   /**
   * Show the minimal form for the rule
   * @param $target link to the form page
   * @param $first is it the first rule ?
   * @param $last is it the last rule ?
   * @param $display_entities display entities / make it read only display
   */
   function showMinimalForm($target,$first=false,$last=false,$display_entities=false) {
      global $LANG,$CFG_GLPI;

      $canedit = haveRight($this->right,"w") && !$display_entities;
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
         echo "<td>&nbsp;</td>";
      }
      echo "<td><a id='rules".$this->fields["id"]."' href=\"".str_replace(".php",".form.php",$target)."?id=".$this->fields["id"].
                 "&amp;onglet=1\">" . $this->fields["name"] . "</a> ";
      if (!empty($this->fields["comment"])) {
         showToolTip($this->fields["comment"],array('applyto'=>"rules".$this->fields["id"]));
      }
      echo "</td>";
      echo "<td>".$this->fields["description"]."</td>";
      echo "<td>".Dropdown::getYesNo($this->fields["is_active"])."</td>";

      if ($display_entities) {
         echo "<td>".Dropdown::getDropdownName('glpi_entities',$this->fields['entities_id'])."</td>";
      }

      if (!$display_entities) {
         if ($this->can_sort && !$first && $canedit) {
            echo "<td><a href=\"".$target."?type=".$this->fields["sub_type"]."&amp;action=up&amp;id=".
                     $this->fields["id"]."\">";
            echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/deplier_up.png\" alt=''></a></td>";
         } else {
            echo "<td>&nbsp;</td>";
         }
      }
      if (!$display_entities) {
         if ($this->can_sort && !$last && $canedit) {
            echo "<td><a href=\"".$target."?type=".$this->fields["sub_type"]."&amp;action=down&amp;id=".
                     $this->fields["id"]."\">";
            echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/deplier_down.png\" alt=''></a></td>";
         } else {
            echo "<td>&nbsp;</td>";
         }
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
              WHERE `sub_type` = '".$this->getType()."'";
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
      global $LANG;

      $actions = $this->getActions();
      $check_results = array();
      $output = array();

      //Test all criterias, without stopping at the first good one
      $this->testCriterias($input,$check_results);

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
         echo "<td class='b'>".Dropdown::getYesNo($criteria_result["result"])."</td></tr>\n";
      }
      echo "</table>";

      $global_result =(isset($output["_rule_process"])?1:0);

      echo "<br><table class='tab_cadrehov'>";
      echo "<tr><th colspan='2'>" . $LANG['rulesengine'][81] . "</th></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td class='center' colspan='2'>".$LANG['rulesengine'][41]." : ";
      echo "<strong> ".Dropdown::getYesNo($global_result)."</strong></td>";

      $output = $this->preProcessPreviewResults($output);

      foreach ($output as $criteria => $value) {
         if (isset($actions[$criteria])) {
            echo "<tr class='tab_bg_2'>";
            echo "<td>".$actions[$criteria]["name"]."</td>";
            echo "<td>";
            echo $this->getActionValue($criteria,$value);
            echo "</td></tr>\n";
         }
      }

      //If a regular expression was used, and matched, display the results
      if (count($this->regex_results)) {
         echo "<tr class='tab_bg_2'>";
         echo "<td>".$LANG['rulesengine'][85]."</td>";
         echo "<td>";
         printCleanArray($this->regex_results[0]);
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
      echo $this->getMinimalCriteriaText($fields);
   }

   function getMinimalCriteriaText($fields) {
      $text="<td>" . $this->getCriteriaName($fields["criteria"]) . "</td>";
      $text.="<td>" . RuleCriteria::getConditionByID($fields["condition"]) . "</td>";
      $text.="<td>" . $this->getCriteriaDisplayPattern($fields["criteria"],$fields["condition"],
                                                     $fields["pattern"]) . "</td>";
      return $text;
   }
   /**
   * Show the minimal infos for the action rule
   * @param $fields datas used to display the action
   * @param $canedit right to edit ?
   */
   function showMinimalAction($fields,$canedit) {
      echo $this->getMinimalActionText($fields);
   }

   function getMinimalActionText($fields) {
      $text="<td>" . $this->getActionName($fields["field"]) . "</td>";
      $text.="<td>" . RuleAction::getActionByID($fields["action_type"]) . "</td>";
      $text.="<td>" . stripslashes($this->getActionValue($fields["field"],$fields["value"])) . "</td>";
      return $text;
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
          && ($condition==Rule::PATTERN_IS || $condition==Rule::PATTERN_IS_NOT)) {
         switch ($crit['type']) {
            case "yesonly":
            case "yesno":
               return Dropdown::getYesNo($pattern);
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
          && ($test||$condition==Rule::PATTERN_IS || $condition==Rule::PATTERN_IS_NOT)) {
         switch ($crit['type']) {
            case "yesonly":
               Dropdown::showYesNo($name,$crit['table'],0);
               $display=true;
               break;
            case "yesno":
               Dropdown::showYesNo($name,$crit['table']);
               $display=true;
               break;
            case "dropdown" :
               Dropdown::show(getItemTypeForTable($crit['table']),
                        array('name' => $name,'value' => $value));
               $display=true;
               break;

            case "dropdown_users" :
               User::dropdown(array('value'  => $value,
                                    'name' => $name,
                                    'right'  => 'all'));
               $display=true;
               break;

            case "dropdown_tracking_itemtype" :
               Dropdown::dropdownTypes($name,0,array_keys(Ticket::getAllTypesForHelpdesk()));
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
         $rc = new RuleCriteria();
         autocompletionTextField($rc,"pattern",array('name' =>$name,
                                                     'value'=>$value));
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
               return Ticket::getStatus($value);

            case "dropdown_assign" :
            case "dropdown_users" :
               return getUserName($value);

            case "yesonly":
            case "yesno" :
               if ($value) {
                  return $LANG['choice'][1];
               } else {
                  return $LANG['choice'][0];
               }

            case "dropdown_urgency" :
               return Ticket::getUrgencyName($value);

            case "dropdown_impact" :
               return Ticket::getImpactName($value);

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

      if ($condition!=Rule::PATTERN_IS && $condition!=Rule::PATTERN_IS_NOT) {
         $crit=$this->getCriteria($ID);
         if (isset($crit['type'])) {
            switch ($crit['type']) {
               case "dropdown" :
                  return Dropdown::getDropdownName($crit["table"],$value);

               case "dropdown_assign" :
               case "dropdown_users" :
                  return getUserName($value);

               case "yesonly":
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
      global $DB, $LANG;

      $criterias = $this->getCriterias();
      if ($this->getRuleWithCriteriasAndActions($rules_id,1,0)) {
         echo "<form name='testrule_form' id='testrule_form' method='post' action=\"$target\">\n";
         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='3'>" . $LANG['rulesengine'][6] . "</th></tr>";

         $type_match=($this->fields["match"]==Rule::AND_MATCHING
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
               $criteria_constants = $criterias[$criteria->fields["criteria"]];
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
         echo "<input type='hidden' name='sub_type' value='" . $this->getType() . "'>";
         echo "</td></tr>\n";
         echo "</table></div></form>\n";
      }
   }

   function preProcessPreviewResults($output) {
      return $output;
   }


   /**
   * Dropdown rules for a defined sub_type of rule
   *
   * Parameters which could be used in options array :
   *    - name : string / name of the select (default is depending itemtype)
   *    - sub_type : integer / sub_type of rule
   *
   * @param $options possible options
   */
   static function dropdown($options=array()) {
      global $DB, $CFG_GLPI, $LANG;

      $p['sub_type'] = '';
      $p['name']   = 'rules_id';
      $p['entity_restrict'] = '';

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key]=$val;
         }
      }

      if ($p['sub_type'] == '') {
         return false;
      }

      $rand=mt_rand();
      $limit_length=$_SESSION["glpidropdown_chars_limit"];

      $use_ajax=false;
      if ($CFG_GLPI["use_ajax"]) {
         $nb=countElementsInTable("glpi_rules", "`sub_type`='".$p['sub_type']."'");

         if ($nb>$CFG_GLPI["ajax_limit_count"]) {
            $use_ajax=true;
         }
      }
      $params=array('searchText' => '__VALUE__',
                  'myname'     => $p['name'],
                  'limit'      => $limit_length,
                  'rand'       => $rand,
                  'type'       => $p['sub_type'],
                  'entity_restrict'  => $p['entity_restrict']);
      $default ="<select name='".$p['name']."' id='dropdown_".$p['name'].$rand."'>";
      $default.="<option value='0'>".DROPDOWN_EMPTY_VALUE."</option></select>";
      ajaxDropdown($use_ajax,"/ajax/dropdownRules.php",$params,$default,$rand);

      return $rand;
   }

   function getCriterias() {
      return array();
   }

   function getActions() {
      return array();
   }

   static function getActionsByType($sub_type) {
      if (class_exists($sub_type)) {
         $rule = new $sub_type();
         return $rule->getActions();
      }
      else {
         return array();
      }
   }

   /**
    * Return all rules from database
    * @param $ID of entity
    * @param $withcriterias import rules criterias too
    * @param $withactions import rules actions too
    */
   function getRulesForEntity($ID, $withcriterias, $withactions) {
      global $DB;

      $rules = array ();

      //Get all the rules whose sub_type is $sub_type and entity is $ID
      $query = "SELECT `glpi_rules`.`id`
              FROM `glpi_ruleactions`, `glpi_rules`
              WHERE `glpi_ruleactions`.`rules_id` = `glpi_rules`.`id`
                    AND `glpi_ruleactions`.`field` = 'entities_id'
                    AND `glpi_rules`.`sub_type` = '".get_class($this)."'
                    AND `glpi_ruleactions`.`value` = '$ID'";
      foreach ($DB->request($query) as $rule) {
         $affect_rule = new Rule;
         $affect_rule->getRuleWithCriteriasAndActions($rule["id"], 0, 1);
         $rules[] = $affect_rule;
      }
      return $rules;
   }

   function showNewRuleForm($ID) {
      global $LANG;

      echo "<form method='post' action='".getItemTypeFormURL('Entity')."'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>" . $this->getTitle() . "</th></tr>\n";
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16] . "&nbsp;:&nbsp;";
      autocompletionTextField($this, "name", array('value'=>'', 'size'=>33));
      echo "&nbsp;&nbsp;&nbsp;".$LANG['joblist'][6] . "&nbsp;:&nbsp;";
      autocompletionTextField($this, "description", array('value'=>'', 'size'=>33));
      echo "&nbsp;&nbsp;&nbsp;".$LANG['rulesengine'][9] . "&nbsp;:&nbsp;";
      $this->dropdownRulesMatch("match", "AND");
      echo "</td><td class='tab_bg_2 center'>";
      echo "<input type=hidden name='sub_type' value='".get_class($this)."'>";
      echo "<input type=hidden name='entities_id' value='-1'>";
      echo "<input type=hidden name='affectentity' value='$ID'>";
      echo "<input type=hidden name='_method' value='addRule'>";
      echo "<input type='submit' name='execute' value=\"" . $LANG['buttons'][8] .
             "\" class='submit'>";
      echo "</td></tr>\n";
      echo "</table></form>";
   }

   function showAndAddRuleForm($ID) {
      global $LANG, $CFG_GLPI;

      $canedit = haveRight($this->right, "w");

      if ($canedit) {
         $this->showNewRuleForm($ID);
      }

         //Get all rules and actions
      $rules = $this->getRulesForEntity( $ID, 0, 1);
      if (empty ($rules)) {
         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr><th>" . $LANG['search'][15] . "</th>";
         echo "</tr>\n";
         echo "</table><br>\n";
      } else {
         if ($canedit) {
            echo "<form name='entityaffectation_form' id='entityaffectation_form' method='post' ".
                  "action='".getItemTypeSearchURL(get_class($this))."'>";
         }
         echo "<table class='tab_cadre_fixehov'><tr>";
         if ($canedit) {
            echo "<th></th>";
         }
         echo "<th>" . $this->getTitle() . "</th>";
         echo "<th>" . $LANG['joblist'][6] . "</th>";
         echo "<th>" . $LANG['common'][60] . "</th>";
         echo "</tr>\n";
         initNavigateListItems(get_class($this), $LANG['entity'][0]."=".Dropdown::getDropdownName("glpi_entities",$ID));

         foreach ($rules as $rule) {
            addToNavigateListItems(get_class($this),$rule->fields["id"]);
            echo "<tr class='tab_bg_1'>";
            if ($canedit) {
               echo "<td width='10'>";
               echo "<input type='checkbox' name='item[" . $rule->fields["id"] . "]' value='1'>";
               echo "</td>";
            }
            if ($canedit) {
               echo "<td><a href=\"".getItemTypeFormURL(get_class($this))."?id=" .
                      $rule->fields["id"] . "&amp;onglet=1\">" . $rule->fields["name"] . "</a></td>";
            } else {
               echo "<td>" . $rule->fields["name"] . "</td>";
            }
            echo "<td>" . $rule->fields["description"] . "</td>";
            echo "<td>" . Dropdown::getYesNo($rule->fields["is_active"]) . "</td>";
            echo "</tr>\n";
         }
         echo "</table>\n";
         if ($canedit) {
            openArrowMassive("entityaffectation_form", true);
            echo "<input type='hidden' name='action' value='delete'>";
            closeArrowMassive('massiveaction', $LANG['buttons'][6]);
            echo "</form>";
         }
         echo "<br>";
      }
   }

   function defineTabs($options=array()) {
      global $LANG,$CFG_GLPI;

      $ong[1]=$LANG['title'][26];
      if ($this->fields['id'] > 0) {
         $ong[12]=$LANG['title'][38];
      }
      return $ong;
   }
}

?>
