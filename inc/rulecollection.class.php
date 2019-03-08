<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

use Glpi\Event;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class RuleCollection extends CommonDBTM {
   /// Rule type
   public $sub_type;
   /// process collection stop on first matched rule
   public $stop_on_first_match                   = false;
   /// field used to order rules
   public $orderby                               = "ranking";
   /// Processing several rules : use result of the previous one to computer the current one
   public $use_output_rule_process_as_next_input = false;
   /// Rule collection can be replay (for dictionnary)
   public $can_replay_rules                      = false;
   /// List of rules of the rule collection
   public $RuleList                              = null;
   /// Menu type
   public $menu_type                             = "rule";
   /// Menu option
   public $menu_option                           = "";

   public $entity                                = 0;

   static $rightname                             = 'config';



   /// Tab orientation : horizontal or vertical
   public $taborientation = 'horizontal';

   static function getTable($classname = null) {
      return parent::getTable('Rule');
   }


   /**
    * @param $entity (default 0)
   **/
   function setEntity($entity = 0) {
      $this->entity = $entity;
   }


   function canList() {
      return static::canView();
   }


   function isEntityAssign() {
      return false;
   }


   /**
    * Get Collection Size : retrieve the number of rules
    *
    * @param $recursive (true by default)
    * @param $condition (0 by default)
    *
    * @return : number of rules
   **/
   function getCollectionSize($recursive = true, $condition = 0) {

      $restrict = [
         'sub_type'  => $this->getRuleClassName()
      ] + getEntitiesRestrictCriteria('glpi_rules', 'entities_id', $this->entity, $recursive);

      if ($condition > 0) {
         $restrict['condition'] = ['&', (int)$condition];
      }
      return countElementsInTable("glpi_rules", $restrict);
   }


   /**
    * @deprecated 9.4
    * @param $options   array
   **/
   function getRuleListQuery($options = []) {
      Toolbox::deprecated('Use getRuleListCriteria');

      $p['active']    = true;
      $p['start']     = 0;
      $p['limit']     = 0;
      $p['inherited'] = 1;
      $p['childrens'] = 0;
      $p['condition'] = 0;

      foreach ($options as $key => $value) {
         $p[$key] = $value;
      }

      if ($p['active']) {
         $sql_active = " `is_active` = 1";
      } else {
         $sql_active = "1";
      }

      if ($p['condition'] > 0) {
         $sql_active .= " AND `condition` & ". (int) $p['condition'];
      }

      $sql = "SELECT `glpi_rules`.*
              FROM `glpi_rules`";

      //Select all the rules of a different type
      if ($this->isRuleRecursive()) {
         $sql .= " LEFT JOIN `glpi_entities` ON (`glpi_entities`.`id`=`glpi_rules`.`entities_id`)
                   WHERE $sql_active
                         AND `sub_type` = '".$this->getRuleClassName()."' ";

         if (!$p['childrens']) {
            $sql .= getEntitiesRestrictRequest(" AND", "glpi_rules", "entities_id", $this->entity,
                                               $p['inherited']);
         } else {
            $sons = getSonsOf('glpi_entities', $this->entity);
            $sql .= " AND `glpi_rules`.`entities_id` IN (".implode(',', $sons).")";
         }
         $sql .= " ORDER BY `glpi_entities`.`level` ASC,
                            `".$this->orderby."` ASC";

      } else {
         $sql .= "WHERE $sql_active
                        AND `sub_type` = '".$this->getRuleClassName()."'
                  ORDER BY `".$this->orderby."` ASC";
      }

      if ($p['limit']) {
         $sql .= " LIMIT ".intval($p['start']).",".intval($p['limit']);
      }
      return $sql;
   }

   /**
    * Get rules list criteria
    *
    * @param array $options Options
    *
    * @return array
   **/
   function getRuleListCriteria($options = []) {

      $p['active']    = true;
      $p['start']     = 0;
      $p['limit']     = 0;
      $p['inherited'] = 1;
      $p['childrens'] = 0;
      $p['condition'] = 0;

      foreach ($options as $key => $value) {
         $p[$key] = $value;
      }

      $criteria = [
         'SELECT' => Rule::getTable() . '.*',
         'FROM'   => Rule::getTable(),
         'ORDER'  => [
            $this->orderby . ' ASC'
         ]
      ];

      $where = [];
      if ($p['active']) {
         $where['is_active'] = 1;
      }

      if ($p['condition'] > 0) {
         $where['condition'] = ['&', (int)$p['condition']];
      }

      //Select all the rules of a different type
      $where['sub_type'] = $this->getRuleClassName();
      if ($this->isRuleRecursive()) {
         $criteria['LEFT JOIN'] = [
            Entity::getTable() => [
               'ON' => [
                  Entity::getTable()   => 'id',
                  Rule::getTable()     => 'entities_id'
               ]
            ]
         ];

         if (!$p['childrens']) {
            $where += getEntitiesRestrictCriteria(
               Rule::getTable(),
               'entities_id',
               $this->entity,
               $p['inherited']
            );
         } else {
            $sons = getSonsOf('glpi_entities', $this->entity);
            $where[Rule::getTable() . '.entities_id'] = $sons;
         }

         $criteria['ORDER'] = [
            Entity::getTable() . '.level ASC',
            $this->orderby . ' ASC'
         ];
      }

      if ($p['limit']) {
         $criteria['LIMIT'] = (int)$p['limit'];
         $criteria['START'] = (int)$p['start'];
      }
      $criteria['WHERE'] = $where;

      return $criteria;
   }

   /**
    * Get Collection Part : retrieve descriptions of a range of rules
    *
    * @param $options array of options may be :
    *         - start : first rule (in the result set - default 0)
    *         - limit : max number of rules to retrieve (default 0)
    *         - recursive : boolean get recursive rules
    *         - childirens : boolean get childrens rules
   **/
   function getCollectionPart($options = []) {
      global $DB;

      $p['start']     = 0;
      $p['limit']     = 0;
      $p['recursive'] = true;
      $p['childrens'] = 0;
      $p['condition'] = 0;

      foreach ($options as $key => $value) {
         $p[$key] = $value;
      }

      // no need to use SingletonRuleList::getInstance because we read only 1 page
      $this->RuleList       = new SingletonRuleList();
      $this->RuleList->list = [];

      //Select all the rules of a different type
      $criteria   = $this->getRuleListCriteria($p);
      $iterator   = $DB->request($criteria);

      while ($data = $iterator->next()) {
         //For each rule, get a Rule object with all the criterias and actions
         $tempRule               = $this->getRuleClass();
         $tempRule->fields       = $data;
         $this->RuleList->list[] = $tempRule;
      }
   }


   /**
    * Get Collection Datas : retrieve descriptions and rules
    *
    * @param $retrieve_criteria  Retrieve the criterias of the rules ? (default 0)
    * @param $retrieve_action    Retrieve the action of the rules ? (default 0)
    * @param $condition          Retrieve with a specific condition
   **/
   function getCollectionDatas($retrieve_criteria = 0, $retrieve_action = 0, $condition = 0) {
      global $DB;

      if ($this->RuleList === null) {
         $this->RuleList = SingletonRuleList::getInstance($this->getRuleClassName(),
                                                          $this->entity);
      }
      $need = 1+($retrieve_criteria?2:0)+($retrieve_action?4:0)+(8*$condition);

      // check if load required
      if (($need & $this->RuleList->load) != $need) {
         //Select all the rules of a different type
         $criteria = $this->getRuleListCriteria(['condition' => $condition]);
         $iterator = $DB->request($criteria);

         if (count($iterator)) {
            $this->RuleList->list = [];

            while ($rule = $iterator->next()) {
               //For each rule, get a Rule object with all the criterias and actions
               $tempRule = $this->getRuleClass();

               if ($tempRule->getRuleWithCriteriasAndActions($rule["id"], $retrieve_criteria,
                                                             $retrieve_action)) {
                  //Add the object to the list of rules
                  $this->RuleList->list[] = $tempRule;
               }
            }

            $this->RuleList->load = $need;
         }
      }
   }


   function getRuleClassName() {

      if (preg_match('/(.*)Collection/', get_class($this), $rule_class)) {
         return $rule_class[1];
      }
      return "";
   }


   /**
    * Get a instance of the class to manipulate rule of this collection
   **/
   function getRuleClass() {

      $name = $this->getRuleClassName();
      if ($name !=  '') {
         return new $name();
      }
      return null;
   }


   /**
    * Is a confirmation needed before replay on DB ?
    * If needed need to send 'replay_confirm' in POST
    *
    * @param $target filename : where to go when done
    *
    * @return  true if confirmtion is needed, else false
   **/
   function warningBeforeReplayRulesOnExistingDB($target) {
      return false;
   }


   /**
    * Replay Collection on DB
    *
    * @param $offset             first row to work on (default 0)
    * @param $maxtime   float    max system time to stop working (default 0)
    * @param $items     array    containg items to replay. If empty -> all
    * @param $params    array    additional parameters if needed
    *
    * @return -1 if all rows done, else offset for next run
   **/
   function replayRulesOnExistingDB($offset = 0, $maxtime = 0, $items = [], $params = []) {
   }


   /**
    * Get title used in list of rules
    *
    * @return Title of the rule collection
   **/
   function getTitle() {
      return __('Rules list');
   }


   /**
    * Indicates if the rule can be affected to an entity or if it's global
   **/
   function isRuleEntityAssigned() {

      $rule = $this->getRuleClass();
      return $rule->isEntityAssign();
   }


   /**
    * Indicates if the rule can be affected to an entity or if it's global
   **/
   function isRuleRecursive() {

      $rule = $this->getRuleClass();
      return $rule->maybeRecursive();
   }

   /**
    * Indicates if the rule use conditions
   **/
   function isRuleUseConditions() {

      $rule = $this->getRuleClass();
      return $rule->useConditions();
   }

   /**
    * Indicates if the rule use conditions
   **/
   function getDefaultRuleConditionForList() {

      $rule = $this->getRuleClass();
      $cond = $rule->getConditionsArray();
      // Get max value
      if (count($cond)) {
         return max(array_keys($cond));
      }
      return 0;
   }

   function showEngineSummary() {

      echo "<table class='tab_cadre_fixe'><tr><th>";

      //Display information about the how the rules engine process the rules
      if ($this->stop_on_first_match) {
         //The engine stop on the first matched rule
         echo "<span class='center b'>".__('The engine stops on the first checked rule.').
              "</span><br>";

      } else {
         //The engine process all the rules
         echo "<span class='center b'>".__('The engine treats all the rules.')."</span><br>";
      }

      if ($this->use_output_rule_process_as_next_input) {
         //The engine keep the result of a rule to be processed further
         echo "<span class='center b'>".
                __('The engine passes the result of a rule to the following one.')."</span><br>";
      }

      if ($this->isRuleUseConditions()) {
         //The engine keep the result of a rule to be processed further
         echo "<span class='center b'>".
                __('Rules are conditionals. Each one can be used on multiple actions.');
         echo "</span><br>";
      }

      echo "</th></tr>";
      echo "</table>\n";
   }


   /**
    * Show the list of rules
    *
    * @param $target
    * @param $options   array
    *
    * @return nothing
   **/
   function showListRules($target, $options = []) {
      global $CFG_GLPI;

      $p['inherited'] = 1;
      $p['childrens'] = 0;
      $p['active']    = false;
      $p['condition'] = 0;
      $rand           = mt_rand();

      foreach (['inherited','childrens', 'condition'] as $param) {
         if (isset($options[$param])
             && $this->isRuleRecursive()) {
            $p[$param] = $options[$param];
         }
      }

      $rule             = $this->getRuleClass();
      $display_entities = ($this->isRuleRecursive()
                           && ($p['inherited'] || $p['childrens']));

      // Do not know what it is ?
      $canedit    = (self::canUpdate()
                     && !$display_entities);

      $use_conditions = false;
      if ($rule->useConditions()) {
         // First get saved option
         $p['condition'] = Session::getSavedOption($this->getType(), 'condition', 0);
         if ($p['condition'] == 0) {
            $p['condition'] = $this->getDefaultRuleConditionForList();
         }
         $use_conditions = true;
         // Mini Search engine
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><td class='center' width='50%'>";
         echo __('Rules used for')."</td><td>";
         $rule->dropdownConditions(['value' => $p['condition'],
                                         'on_change'  => 'reloadTab("start=0&inherited='.$p['inherited']
                                                         .'&childrens='.$p['childrens'].'&condition="+this.value)']);
         echo "</td></tr></table>";
      }

      $nb         = $this->getCollectionSize($p['inherited'], $p['condition']);
      $p['start'] = (isset($options["start"]) ? $options["start"] : 0);

      if ($p['start'] >= $nb) {
         $p['start'] = 0;
      }

      $p['limit'] = $_SESSION['glpilist_limit'];
      $this->getCollectionPart($p);

      Html::printAjaxPager('', $p['start'], $nb);

      Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
      echo "\n<div class='spaced'>";

      if ($canedit && $nb) {
         $massiveactionparams = ['num_displayed' => min($p['limit'], $nb),
                                      'container'     => 'mass'.__CLASS__.$rand,
                                      'extraparams'   => ['entity' => $this->entity,
                                                               'condition' => $p['condition'],
                                                               'rule_class_name'
                                                                 => $this->getRuleClassName()]];
         Html::showMassiveActions($massiveactionparams);
      }

      echo "<table class='tab_cadre_fixehov'>";
      $colspan = 6;

      if ($display_entities) {
         $colspan++;
      }
      if ($use_conditions) {
         $colspan++;
      }
      echo "<tr><th colspan='$colspan'>" . $this->getTitle() ."</th></tr>\n";

      echo "<tr>";
      echo "<th>";
      if ($canedit) {
         echo Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
      }
      echo "</th>";
      echo "<th>".__('Name')."</th>";
      echo "<th>".__('Description')."</th>";
      if ($use_conditions) {
         echo "<th>".__('Use rule for')."</th>";
      }
      echo "<th>".__('Active')."</th>";

      if ($display_entities) {
         echo "<th>".__('Entity')."</th>\n";
      }
      if (!$display_entities) {
         echo "<th colspan='2'>&nbsp;</th>";
      }
      echo "</tr>\n";

      if (count($this->RuleList->list)) {
         $ruletype = $this->RuleList->list[0]->getType();
         Session::initNavigateListItems($ruletype);
      }

      for ($i=$p['start'],$j=0; isset($this->RuleList->list[$j]); $i++,$j++) {
         $this->RuleList->list[$j]->showMinimalForm($target, $i==0, $i==$nb-1, $display_entities, $p['condition']);
         Session::addToNavigateListItems($ruletype, $this->RuleList->list[$j]->fields['id']);
      }
      if ($nb) {
         echo "<tr>";
         echo "<th>";
         if ($canedit) {
            echo Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         }
         echo "</th>";
         echo "<th>".__('Name')."</th>";
         echo "<th>".__('Description')."</th>";
         if ($use_conditions) {
            echo "<th>".__('Use rule for')."</th>";
         }
         echo "<th>".__('Active')."</th>";

         if ($display_entities) {
            echo "<th>".__('Entity')."</th>\n";
         }
         if (!$display_entities) {
            echo "<th colspan='2'>&nbsp;</th>";
         }
         echo "</tr>\n";
      }
      echo "</table>\n";

      if ($canedit && $nb) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
      }

      echo "</div>";
      Html::closeForm();

      Html::printAjaxPager('', $p['start'], $nb);

      echo "<div class='spaced center'>";

      if ($plugin = isPluginItemType($this->getType())) {
         $url = $CFG_GLPI["root_doc"]."/plugins/".strtolower($plugin['plugin']);
      } else {
         $url = $CFG_GLPI["root_doc"];
      }

      echo "<a class='vsubmit' href='#' onClick=\"".
                  Html::jsGetElementbyID('allruletest'.$rand).".dialog('open'); return false;\">".
                  __('Test rules engine')."</a>";
      Ajax::createIframeModalWindow('allruletest'.$rand,
                                    $url."/front/rulesengine.test.php?".
                                          "sub_type=".$this->getRuleClassName().
                                          "&condition=".$p['condition'],
                                    ['title' => __('Test rules engine')]);
      echo "</div>";

      if ($this->can_replay_rules) {
         echo "<div class='spaced center'>";
         echo "<a class='vsubmit' href='".$rule->getSearchURL()."?replay_rule=replay_rule'>".
               __s('Replay the dictionary rules')."</a>";
         echo "</div>";
      }

      echo "<div class='spaced'>";
      $this->showAdditionalInformationsInForm($target);
      echo "</div>";
   }


   /**
    * Show the list of rules
    *
    * @param $target
    *
    * @return nothing
   **/
   function showAdditionalInformationsInForm($target) {
   }


   /**
    * Modify rule's ranking and automatically reorder all rules
    *
    * @param $ID     the rule ID whose ranking must be modified
    * @param $action up or down
    * @param $condition action on a specific condition
   **/
   function changeRuleOrder($ID, $action, $condition = 0) {
      global $DB;

      $sql = "SELECT `ranking`
              FROM `glpi_rules`
              WHERE `id` ='$ID'";

      $add_condition = '';

      if ($condition > 0) {
         $add_condition = ' AND `condition` & '. (int) $condition;

      }

      if ($result = $DB->query($sql)) {
         if ($DB->numrows($result) == 1) {
            $current_rank = $DB->result($result, 0, 0);
            // Search rules to switch
            $sql2 = "SELECT `id`, `ranking`
                     FROM `glpi_rules`
                     WHERE `sub_type` = '".$this->getRuleClassName()."'";

            switch ($action) {
               case "up" :
                  $sql2 .= " AND `ranking` < '$current_rank'
                            $add_condition
                            ORDER BY `ranking` DESC
                            LIMIT 1";
                  break;

               case "down" :
                  $sql2 .= " AND `ranking` > '$current_rank'
                             $add_condition
                            ORDER BY `ranking` ASC
                            LIMIT 1";
                  break;

               default :
                  return false;
            }

            if ($result2 = $DB->query($sql2)) {
               if ($DB->numrows($result2) == 1) {
                  list($other_ID,$new_rank) = $DB->fetch_row($result2);
                  echo $current_rank.' '.$ID.'<br>';
                  echo $new_rank.' '.$other_ID.'<br>';

                  $rule = $this->getRuleClass();
                  $result = false;
                  $sql3 = "SELECT `id`, `ranking`
                           FROM `glpi_rules`
                           WHERE `sub_type` = '".$this->getRuleClassName()."'";
                  $diff = $new_rank - $current_rank;
                  switch ($action) {
                     case "up" :
                        $sql3 .= " AND `ranking` > '$new_rank'
                                 AND `ranking` <= '$current_rank'";
                        $diff += 1;
                        break;

                     case "down" :
                        $sql3 .= " AND `ranking` >= '$current_rank'
                                 AND `ranking` < '$new_rank'";
                        $diff -= 1;
                        break;

                     default :
                        return false;
                  }

                  if ($diff != 0) {
                     // Move several rules
                     foreach ($DB->request($sql3) as $data) {
                        $data['ranking'] += $diff;
                        $result = $rule->update($data);
                     }
                  } else {
                     // Only move one
                     $result = $rule->update(['id'      => $ID,
                                                   'ranking' => $new_rank]);
                  }

                  // Update reference
                  if ($result) {
                     $result = $rule->update(['id'      => $other_ID,
                                                   'ranking' => $current_rank]);
                  }
                  return $result;
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

      $DB->update(
         'glpi_rules', [
            'ranking' => new \QueryExpression($DB->quoteName('ranking') . ' - 1')
         ], [
            'sub_type'  => $this->getRuleClassName(),
            'ranking'   => ['>', $ranking]
         ]
      );
      return $DB->query($sql);
   }


   /**
    * Move a rule in an ordered collection
    *
    * @param $ID        of the rule to move
    * @param $ref_ID    of the rule position  (0 means all, so before all or after all)
    * @param $type      of move : after or before ( default 'after')
    *
    * @return true if all ok
   **/
   function moveRule($ID, $ref_ID, $type = 'after') {
      global $DB;

      $ruleDescription = new Rule();

      // Get actual ranking of Rule to move
      $ruleDescription->getFromDB($ID);
      $old_rank = $ruleDescription->fields["ranking"];

      // Compute new ranking
      if ($ref_ID) { // Move after/before an existing rule
         $ruleDescription->getFromDB($ref_ID);
         $rank = $ruleDescription->fields["ranking"];

      } else if ($type == "after") {
         // Move after all
         $query = "SELECT MAX(`ranking`) AS maxi
                   FROM `glpi_rules`
                   WHERE `sub_type` ='".$this->getRuleClassName()."' ";
         $result = $DB->query($query);
         $ligne  = $DB->fetch_assoc($result);
         $rank   = $ligne['maxi'];

      } else {
         // Move before all
         $rank = 1;
      }

      $rule   = $this->getRuleClass();

      $result = false;

      // Move others rules in the collection
      if ($old_rank < $rank) {
         if ($type == "before") {
            $rank--;
         }

         // Move back all rules between old and new rank
         $query = "SELECT `id`, `ranking`
                   FROM `glpi_rules`
                   WHERE `sub_type` ='".$this->getRuleClassName()."'
                         AND `ranking` > '$old_rank'
                         AND `ranking` <= '$rank'";

         foreach ($DB->request($query) as $data) {
            $data['ranking']--;
            $result = $rule->update($data);
         }

      } else if ($old_rank > $rank) {
         if ($type == "after") {
            $rank++;
         }

         // Move forward all rule  between old and new rank
         $query = "SELECT `id`, `ranking`
                   FROM `glpi_rules`
                   WHERE `sub_type` ='".$this->getRuleClassName()."'
                         AND `ranking` >= '$rank'
                         AND `ranking` < '$old_rank'";

         foreach ($DB->request($query) as $data) {
            $data['ranking']++;
            $result = $rule->update($data);
         }

      } else { // $old_rank == $rank : nothing to do
         $result = false;
      }

      // Move the rule
      if ($result
          && ($old_rank != $rank)) {
         $result = $rule->update(['id'      => $ID,
                                       'ranking' => $rank]);
      }
      return ($result ? true : false);
   }


   /**
    * Print a title for backup rules
    *
    * @since 0.85
    *
    * @return nothing (display)
   **/
   static function titleBackup() {
      global $CFG_GLPI;

      $buttons = [];
      $title   = "";

      $buttons["{$CFG_GLPI["root_doc"]}/front/rule.backup.php?action=import"] = _x('button', 'Import');
      $buttons["{$CFG_GLPI["root_doc"]}/front/rule.backup.php?action=export"] = _x('button', 'Export');

      echo "<div class='center'><table class='tab_glpi'><tr>";
      echo "<td><i class='far fa-save fa-3x'></i></td>";
      foreach ($buttons as $key => $val) {
         echo "<td><a class='vsubmit' href='".$key."'>".$val."</a></td>";
      }
      echo "</tr></table></div>";
   }


   /**
    * Duplicate a rule
    *
    * @param $ID        of the rule to duplicate
    *
    * @since 0.85
    *
    * @return true if all ok
   **/
   function duplicateRule($ID) {

      //duplicate rule
      $rulecollection = new self();
      $rulecollection->getFromDB($ID);

      //get ranking
      $ruletype    = $rulecollection->fields['sub_type'];
      $rule        = new $ruletype;
      $nextRanking = $rule->getNextRanking();

      //Update fields of the new duplicate
      $rulecollection->fields['name']        = sprintf(__('Copy of %s'),
                                                       $rulecollection->fields['name']);
      $rulecollection->fields['is_active']   = 0;
      $rulecollection->fields['ranking']     = $nextRanking;
      $rulecollection->fields['uuid']        = Rule::getUuid();
      unset($rulecollection->fields['id']);

      //add new duplicate
      $input = toolbox::addslashes_deep($rulecollection->fields);
      $newID = $rulecollection->add($input);
      $rule  = $rulecollection->getRuleClass();
      if (!$newID) {
         return false;
      }
      //find and duplicate actions
      $ruleaction = new RuleAction(get_class($rule));
      $actions    = $ruleaction->find(['rules_id' => $ID]);
      $actions    = toolbox::addslashes_deep($actions);
      foreach ($actions as $action) {
         $action['rules_id'] = $newID;
         unset($action['id']);
         if (!$ruleaction->add($action)) {
            return false;
         }
      }

      //find and duplicate criterias
      $rulecritera = new RuleCriteria(get_class($rule));
      $criteria   = $rulecritera->find(['rules_id' => $ID]);
      $criteria = toolbox::addslashes_deep($criteria);
      foreach ($criteria as $criterion) {
         $criterion['rules_id'] = $newID;
         unset($criterion['id']);
         if (!$rulecritera->add($criterion)) {
            return false;
         }
      }

      return true;
   }


   /**
    * Export rules in a xml format
    *
    * @param items array the input data to transform to xml
    *
    * @since 0.85
    *
    * @return nothing, send attachment to browser
   **/
   static function exportRulesToXML($items = []) {

      if (!count($items)) {
         return false;
      }

      $rulecollection = new self();
      $rulecritera    = new RuleCriteria();
      $ruleaction     = new RuleAction();

      //create xml
      $xmlE           = new SimpleXMLElement('<rules/>');

      //parse all rules
      foreach ($items as $key => $ID) {
         $rulecollection->getFromDB($ID);
         if (!class_exists($rulecollection->fields['sub_type'])) {
            continue;
         }
         $rule = new $rulecollection->fields['sub_type'];
         unset($rulecollection->fields['id']);
         unset($rulecollection->fields['date_mod']);

         $name = Dropdown::getDropdownName("glpi_entities",
                                           $rulecollection->fields['entities_id']);
         $rulecollection->fields['entities_id'] = $name;

         //add root node
         $xmlERule = $xmlE->addChild('rule');

         //convert rule direct indexes in XML
         foreach ($rulecollection->fields as $key => $val) {
            $xmlERule->$key = $val;
         }

         //find criterias
         $criterias = $rulecritera->find(['rules_id' => $ID]);
         foreach ($criterias as &$criteria) {
            unset($criteria['id']);
            unset($criteria['rules_id']);

            $available_criteria = $rule->getCriterias();
            $crit               = $criteria['criteria'];
            if (self::isCriteraADropdown($available_criteria, $criteria['condition'], $crit)) {
               $criteria['pattern']
                  = Html::clean(Dropdown::getDropdownName($available_criteria[$crit]['table'],
                                                          $criteria['pattern']));
            }

            //convert criterias in XML
            $xmlECritiera = $xmlERule->addChild('rulecriteria');
            foreach ($criteria as $key => $val) {
               $xmlECritiera->$key = $val;
            }
         }

         //find actions
         $actions = $ruleaction->find(['rules_id' => $ID]);
         foreach ($actions as &$action) {
            unset($action['id']);
            unset($action['rules_id']);

            //process FK (just in case of "assign" action)
            if (($action['action_type'] == "assign")
                && (strpos($action['field'], '_id') !== false)
                && !(($action['field'] == "entities_id")
                     && ($action['value'] == 0))) {
               $field = $action['field'];
               if ($action['field'][0] == "_") {
                  $field = substr($action['field'], 1);
               }
               $table = getTableNameForForeignKeyField($field);

               $action['value'] = Html::clean(Dropdown::getDropdownName($table, $action['value']));
            }

            //convert actions in XML
            $xmlEAction = $xmlERule->addChild('ruleaction');
            foreach ($action as $key => $val) {
               $xmlEAction->$key = $val;
            }
         }
      }

      //convert SimpleXMLElement to xml string
      $xml = $xmlE->asXML();

      //send attachment to browser
      header('Content-type: application/xml');
      header('Content-Disposition: attachment; filename="rules.xml"');
      echo $xml;

      //exit;
   }


   /**
    * Print a form to select a xml file for import rules
    *
    * @since 0.85
    *
    * @return nothing (display)
   **/
   static function displayImportRulesForm() {

      echo "<form name='form' method='post' action='rule.backup.php' ".
             "enctype='multipart/form-data' >";
      echo "<div class='center'>";

      echo "<h2>".__("Import rules from a XML file")."</h2>";
      echo "<input type='file' name='xml_file'>&nbsp;";
      echo "<input type='hidden' name='action' value='preview_import'>";
      echo "<input type='submit' name='import' value=\""._sx('button', 'Import').
             "\" class='submit'>";

      // Close for Form
      echo "</div>";
      Html::closeForm();
   }


   /**
    *
    * Check if a criterion is a dropdown or not
    *
    * @since 0.85
    *
    * @param $available_criteria    available criterai for this rule
    * @param $condition             the rulecriteria condition
    * @param $criterion             the criterion
    *
    * @return true if a criterion is a dropdown, false otherwise
   **/
   static function isCriteraADropdown($available_criteria, $condition, $criterion) {

      if (isset($available_criteria[$criterion]['type'])) {
         $type = $available_criteria[$criterion]['type'];
      } else {
         $type = false;
      }
      return (in_array($condition,
                       [Rule::PATTERN_IS, Rule::PATTERN_IS_NOT, Rule::PATTERN_UNDER])
              && ($type == 'dropdown'));
   }


   /**
    * Print a form to inform user when conflicts appear during the import of rules from a xml file
    *
    * @since 0.85
    *
    * @return true if all ok
   **/
   static function previewImportRules() {
      global $DB;

      if (!isset($_FILES["xml_file"]) || ($_FILES["xml_file"]["size"] == 0)) {
         return false;
      }

      if ($_FILES["xml_file"]["error"] != UPLOAD_ERR_OK) {
         Session::addMessageAfterRedirect(__("No file was uploaded"));
         return false;
      }
      //get xml file content
      $xml           = file_get_contents($_FILES["xml_file"]["tmp_name"]);
      //convert a xml string into a SimpleXml object
      if (!$xmlE= simplexml_load_string($xml)) {
         Session::addMessageAfterRedirect(__('Unauthorized file type'), false, ERROR);
      }
      //convert SimpleXml object into an array and store it in session
      $rules         = json_decode(json_encode((array) $xmlE), true);
      //check rules (check if entities, criterias and actions is always good in this glpi)
      $entity        = new Entity();
      $rules_refused = [];

      //In case there's only one rule to import, recreate an array with key => value
      if (isset($rules['rule']['entities_id'])) {
         $rules['rule'] = [0 => $rules['rule']];
      }

      foreach ($rules['rule'] as $k_rule => &$rule) {
         $tmprule = new $rule['sub_type'];
         //check entities
         if ($tmprule->isEntityAssign()) {
            $entities_found = $entity->find(['completename' => $rule['entities_id']]);
            if (empty($entities_found)) {
               $rules_refused[$k_rule]['entity'] = true;
            }
         }

         //process direct attributes
         foreach ($rule as &$val) {
            if (is_array($val)
                    && empty($val)) {
               $val = "";
            }
         }

         //check criterias
         if (isset($rule['rulecriteria'])) {
            //check and correct criterias array format
            if (isset($rule['rulecriteria']['criteria'])) {
               $rule['rulecriteria'] = [$rule['rulecriteria']];
            }

            foreach ($rule['rulecriteria'] as $k_crit => $criteria) {
               $available_criteria = $tmprule->getCriterias();
               $crit               = $criteria['criteria'];
               //check FK (just in case of "is", "is_not" and "under" criteria)
               if (self::isCriteraADropdown($available_criteria,
                                            $criteria['condition'], $crit)) {
                  //escape pattern
                  $criteria['pattern'] = $DB->escape(Html::entity_decode_deep($criteria['pattern']));
                  $itemtype = getItemTypeForTable($available_criteria[$crit]['table']);
                  $item     = new $itemtype();
                  if ($item instanceof CommonTreeDropdown) {
                     $found = $item->find(['completename' => $criteria['pattern']]);
                  } else {
                     $found = $item->find(['name' => $criteria['pattern']]);
                  }
                  if (empty($found)) {
                     $rules_refused[$k_rule]['criterias'][] = $k_crit;
                  } else {
                     $tmp = array_pop($found);
                     $rules['rule'][$k_rule]['rulecriteria'][$k_crit]['pattern'] = $tmp['id'];
                  }
               }
            }
         }

         //check actions
         if (isset($rule['ruleaction'])) {
            //check and correct actions array format
            if (isset($rule['ruleaction']['field'])) {
               $rule['ruleaction'] = [$rule['ruleaction']];
            }

            foreach ($rule['ruleaction'] as $k_action => $action) {
               $available_actions = $tmprule->getActions();
               $act               = $action['field'];

               if (($action['action_type'] == "assign")
                   && (isset($available_actions[$act]['type'])
                       && ($available_actions[$act]['type'] == 'dropdown'))) {

                  //pass root entity and empty array (N/A value)
                  if (($action['field'] == "entities_id")
                      && (($action['value'] == 0)
                          || ($action['value'] == []))) {
                     continue;
                  }

                  //escape value
                  $action['value'] = $DB->escape(Html::entity_decode_deep($action['value']));
                  $itemtype = getItemTypeForTable($available_actions[$act]['table']);
                  $item     = new $itemtype();
                  if ($item instanceof CommonTreeDropdown) {
                     $found = $item->find(['completename' => $action['value']]);
                  } else {
                     $found = $item->find(['name' => $action['value']]);
                  }
                  if (empty($found)) {
                     $rules_refused[$k_rule]['actions'][] = $k_action;
                  } else {
                     $tmp = array_pop($found);
                     $rules['rule'][$k_rule]['ruleaction'][$k_action]['value'] = $tmp['id'];
                  }
               }
            }
         }
      }

      //save rules for ongoing processing
      $_SESSION['glpi_import_rules']         = $rules;
      $_SESSION['glpi_import_rules_refused'] = $rules_refused;

      //if no conflict detected, we can directly process the import
      if (!count($rules_refused)) {
         Html::redirect("rule.backup.php?action=process_import");
      }

      //print report
      echo "<form name='form' method='post' action='rule.backup.php' >";
      echo "<div class='spaced' id='tabsbody'>";
      echo "<table class='tab_cadre'>";
      echo "<input type='hidden' name='action' value='process_import'>";
      echo "<tr><th colspan='3'>".__('Rules refused')."</th></tr>";
      echo "<tr>";
      echo "<th>"._n('Type', 'Type', 1)."</th>";
      echo "<th>".__('Name')."</th>";
      echo "<th>".__('Reason of rejection')."</th>";
      echo "</tr>";

      $odd = true;
      foreach ($rules_refused as $k_rule => $refused) {
         $odd = !$odd;
         if ($odd) {
            $class = " class='tab_bg_1' ";
         } else {
            $class = " class='tab_bg_2' ";
         }

         $sub_type = $rules['rule'][$k_rule]['sub_type'];
         $item     = new $sub_type();

         echo "<tr $class>";
         echo "<td>".$item->getTitle()."</td>";
         echo "<td>".$rules['rule'][$k_rule]['name']."</td>";
         echo "<td>";

         echo "<table class='tab_cadre' style='width:100%'>";
         //show entity select
         if (!isset($refused['criterias']) && !isset($refused['actions'])) {
            if (isset($refused['entity'])) {
               echo "<tr class='tab_bg_1_2'>";
               echo "<td>";
               printf(__('%1$s (%2$s)'), __('Entity not found'),
                       $rules['rule'][$k_rule]['entities_id']);
               echo "</td>";
               echo "<td>";
               echo __('Select the desired entity')."&nbsp;";
               Dropdown::show('Entity',
                              ['comments' => false,
                                    'name'     => "new_entities[".
                                                   $rules['rule'][$k_rule]['uuid']."]"]);
               echo "</td>";
               echo "</tr>";
            }
         }

         //show criterias refused for this rule
         if (isset($refused['criterias'])) {
            echo "<tr class='tab_bg_1_2'>";
            echo "<td>".__('Criteria refused')."</td>";
            echo "<td>";

            echo "<table class='tab_cadre' style='width:100%'>";
            echo "<tr class='tab_bg_2'>";
            echo "<th class='center b'>"._n('Criterion', 'Criteria', 1)."</th>\n";
            echo "<th class='center b'>".__('Condition')."</th>\n";
            echo "<th class='center b'>".__('Reason')."</th>\n";
            echo "</tr>\n";
            foreach ($refused['criterias'] as $k_criteria) {
               $criteria = $rules['rule'][$k_rule]['rulecriteria'][$k_criteria];

               //fix empty empty array values
               if (empty($criteria['value'])) {
                  $criteria['value'] = null;
               }
               echo "<tr class='tab_bg_1'>";
               echo "<td>" . $item->getCriteriaName($criteria["criteria"]) . "</td>";
               echo "<td>" . RuleCriteria::getConditionByID($criteria["condition"],
                                                            get_class($item),
                                                            $criteria["criteria"]) . "</td>";
               echo "<td>" . $criteria["pattern"]."</td>";
               echo "</tr>";
            }
            echo "</table>\n";
            echo "</td>";
            echo "</tr>";
         }

         //show actions refused for this rule
         if (isset($refused['actions'])) {
            echo "<tr class='tab_bg_1_2'>";
            echo "<td>".__('Actions refused')."</td>";
            echo "<td>";

            echo "<table class='tab_cadre' style='width:100%'>";
            echo "<tr class='tab_bg_2'>";
            echo "<th class='center b'>"._n('Field', 'Fields', Session::getPluralNumber())."</th>";
            echo "<th class='center b'>".__('Action type')."</th>";
            echo "<th class='center b'>".__('Value')."</th>";
            echo "</tr>\n";
            foreach ($refused['actions'] as $k_action) {
               $action = $rules['rule'][$k_rule]['ruleaction'][$k_action];
               //fix empty empty array values
               if (empty($action['value'])) {
                  $action['value'] = null;
               }
               echo "<tr class='tab_bg_1'>";
               echo "<td>" . $item->getActionName($action["field"]) . "</td>";
               echo "<td>" . RuleAction::getActionByID($action["action_type"]) . "</td>";
               echo "<td>" . $action["value"]."</td>";
               echo "</tr>";
            }
            echo "</table>\n";
            echo "</td>";
            echo "</tr>";
         }
         echo "</table>\n";
         echo "</td></tr>";
      }

      //display buttons
      $class = ($odd?" class='tab_bg_1' ":" class='tab_bg_2' ");
      echo "<tr $class><td colspan='3' class='center'>";
      echo "<input type='submit' name='import' value=\""._sx('button', 'Post').
             "\" class='submit'>";
      echo "</td></tr>";

      // Close for Form
      echo "</table></div>";
      Html::closeForm();

      return true;
   }


   /**
    * import rules in glpi after user validation
    *
    * @since 0.85
    *
    * @return true if all ok
   **/
   static function processImportRules() {
      global $DB;

      $ruleCriteria = new RuleCriteria();
      $ruleAction   = new RuleAction();
      $entity       = new Entity();

      //get session vars
      $rules         = $_SESSION['glpi_import_rules'];
      $rules_refused = $_SESSION['glpi_import_rules_refused'];
      $rr_keys       = array_keys($rules_refused);
      unset($_SESSION['glpi_import_rules']);
      unset($_SESSION['glpi_import_rules_refused']);

      // unset all refused rules
      foreach ($rules['rule'] as $k_rule => &$rule) {
         if (in_array($k_rule, $rr_keys)) {
            //Do not process rule with actions or criterias refused
            if (isset($rules_refused[$k_rule]['criterias'])
                || isset($rules_refused[$k_rule]['actions'])) {
               unset($rules['rule'][$k_rule]);
            } else {// accept rule with only entity not found (change entity)
               $rule['entities_id'] = $_REQUEST['new_entities'][$rule['uuid']];
            }
         }
      }

      //import all right rules
      while (!empty($rules['rule'])) {
         $current_rule             = array_shift($rules['rule']);
         $add_criteria_and_actions = false;
         $params                   = [];
         $itemtype                 = $current_rule['sub_type'];
         $item                     = new $itemtype();

         //Find a rule by it's uuid
         $found    = $item->find(['uuid' => $current_rule['uuid']]);
         $params   = Toolbox::addslashes_deep($current_rule);
         unset($params['rulecriteria']);
         unset($params['ruleaction']);

         if (!$item->isEntityAssign()) {
            $params['entities_id'] = 0;
         } else {
            $entities_found = $entity->find(['completename' => $rule['entities_id']]);
            if (!empty($entities_found)) {
               $entity_found          = array_shift($entities_found);
               $params['entities_id'] = $entity_found['id'];
            } else {
               $params['entities_id'] = 0;
            }
         }
         foreach (['is_recursive', 'is_active'] as $field) {
            //Should not be necessary but without it there's an sql error...
            if (!isset($params[$field]) || ($params[$field] == '')) {
               $params[$field] = 0;
            }
         }

         //if uuid not exist, create rule
         if (empty($found)) {
            //Manage entity
            $params['_add'] = true;
            $rules_id       = $item->add($params);
            if ($rules_id) {
               Event::log($rules_id, "rules", 4, "setup",
                          sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $rules_id));
               $add_criteria_and_actions = true;
            }
         } else { //if uuid exists, then update the rule
            $tmp               = array_shift($found);
            $params['id']      = $tmp['id'];
            $params['_update'] = true;
            $rules_id          = $tmp['id'];
            if ($item->update($params)) {
               Event::log($rules_id, "rules", 4, "setup",
                          sprintf(__('%s updates an item'), $_SESSION["glpiname"]));

               //remove all dependent criterias and action
               $ruleCriteria->deleteByCriteria(["rules_id" => $rules_id]);
               $ruleAction->deleteByCriteria(["rules_id" => $rules_id]);
               $add_criteria_and_actions = true;
            }
         }

         if ($add_criteria_and_actions) {
            //Add criteria
            if (isset($current_rule['rulecriteria'])) {
               foreach ($current_rule['rulecriteria'] as $criteria) {
                  $criteria['rules_id'] = $rules_id;
                  //fix array in value key
                  //(simplexml bug, empty xml node are converted in empty array instead of null)
                  if (is_array($criteria['pattern'])) {
                     $criteria['pattern'] = null;
                  }
                  $criteria = Toolbox::addslashes_deep($criteria);
                  $ruleCriteria->add($criteria);
               }
            }

            //Add actions
            if (isset($current_rule['ruleaction'])) {
               foreach ($current_rule['ruleaction'] as $action) {
                  $action['rules_id'] = $rules_id;
                  //fix array in value key
                  //(simplexml bug, empty xml node are converted in empty array instead of null)
                  if (is_array($action['value'])) {
                     $action['value'] = null;
                  }
                  $action = Toolbox::addslashes_deep($action);
                  $ruleAction->add($action);
               }
            }
         }
      }

      Session::addMessageAfterRedirect(__('Successful importation'));

      return true;
   }


   /**
    * Process all the rules collection
    *
    * @param input            array the input data used to check criterias (need to be clean slashes)
    * @param output           array the initial ouput array used to be manipulate by actions (need to be clean slashes)
    * @param params           array parameters for all internal functions (need to be clean slashes)
    * @param options          array options :
    *                            - condition : specific condition to limit rule list
    *                            - only_criteria : only react on specific criteria
    *
    * @return the output array updated by actions (addslashes datas)
   **/
   function processAllRules($input = [], $output = [], $params = [], $options = []) {

      $p['condition']     = 0;
      $p['only_criteria'] = null;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      // Get Collection datas
      $this->getCollectionDatas(1, 1, $p['condition']);
      $input                      = $this->prepareInputDataForProcessWithPlugins($input, $params);
      $output["_no_rule_matches"] = true;
      //Store rule type being processed (for plugins)
      $params['rule_itemtype']    = $this->getRuleClassName();

      if (count($this->RuleList->list)) {
         foreach ($this->RuleList->list as $rule) {
            //If the rule is active, process it

            if ($rule->fields["is_active"]) {
               $output["_rule_process"] = false;
               $rule->process($input, $output, $params, $p);

               if ($output["_rule_process"] && $this->stop_on_first_match) {
                  unset($output["_rule_process"]);
                  $output["_ruleid"] = $rule->fields["id"];
                  return Toolbox::addslashes_deep($output);
               }
            }

            if ($this->use_output_rule_process_as_next_input) {
               $output = $this->prepareInputDataForProcessWithPlugins($output, $params);
               $input  = $output;
            }
         }
      }

      return Toolbox::addslashes_deep($output);
   }


   /**
    * Show form displaying results for rule collection preview
    *
    * @param $target          where to go
    * @param $values    array of data
    * @param $condition       condition to limit rules (default 0)
    **/
   function showRulesEnginePreviewCriteriasForm($target, array $values, $condition = 0) {
      global $DB;

      $input = $this->prepareInputDataForTestProcess($condition);

      if (count($input)) {
         $rule      = $this->getRuleClass();
         $criterias = $rule->getAllCriteria();
         echo "<form name='testrule_form' id='testrulesengine_form' method='post' action='$target'>";
         echo "\n<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='2'>" . _n('Criterion', 'Criteria', Session::getPluralNumber()) . "</th></tr>\n";

         //Brower all criterias
         foreach ($input as $criteria) {
            echo "<tr class='tab_bg_1'>";

            if (isset($criterias[$criteria])) {
               $criteria_constants = $criterias[$criteria];
               echo "<td>".$criteria_constants["name"]."</td>";
            } else {
               echo "<td>".$criteria."</td>";
            }

            echo "<td>";
            $rule->displayCriteriaSelectPattern($criteria, $criteria, Rule::PATTERN_IS,
                                                isset($values[$criteria])?$values[$criteria]:'');
            echo "</td></tr>\n";
         }

         $rule->showSpecificCriteriasForPreview($_POST);

         echo "<tr><td class='tab_bg_2 center' colspan='2'>";
         echo "<input type='submit' name='test_all_rules' value='". _sx('button', 'Test')."'
                class='submit'>";
         echo "<input type='hidden' name='sub_type' value='" . $this->getRuleClassName() . "'>";
         echo "<input type='hidden' name='condition' value='$condition'>";
         echo "</td></tr>\n";
         echo "</table></div>";
         Html::closeForm();

      } else {
         echo '<br><div class="center b">'.__('No element to be tested').'</div>';
      }

      return $input;
   }


   /**
    * Test all the rules collection
    *
    * @param input      array the input data used to check criterias
    * @param output     array the initial ouput array used to be manipulate by actions
    * @param params     array parameters for all internal functions
    * @param $condition       condition to limit rules (DEFAULT 0)
    *
    * @return the output array updated by actions
   **/
   function testAllRules($input = [], $output = [], $params = [], $condition = 0) {

      // Get Collection datas
      $this->getCollectionDatas(1, 1, $condition);

      $output["_no_rule_matches"] = true;

      if (count($this->RuleList->list)) {
         foreach ($this->RuleList->list as $rule) {
            //If the rule is active, process it
            if ($rule->fields["is_active"]) {
               $output["_rule_process"]                     = false;
               $output["result"][$rule->fields["id"]]["id"] = $rule->fields["id"];
               $rule->process($input, $output, $params);

               if ($output["_rule_process"]
                   && $this->stop_on_first_match) {
                  unset($output["_rule_process"]);
                  $output["result"][$rule->fields["id"]]["result"] = 1;
                  $output["_ruleid"]                               = $rule->fields["id"];
                  return $output;

               } else if ($output["_rule_process"]) {
                  $output["result"][$rule->fields["id"]]["result"] = 1;

               } else {
                  $output["result"][$rule->fields["id"]]["result"] = 0;
               }

            } else {
               //Rule is inactive
               $output["result"][$rule->fields["id"]]["result"] = 2;
            }

            if ($this->use_output_rule_process_as_next_input) {
               $input = $output;
            }
         }
      }

      return $output;
   }


   /**
    * Prepare input datas for the rules collection
    *
    * @param $input  the input data used to check criterias
    * @param $params parameters
    *
    * @return the updated input datas
   **/
   function prepareInputDataForProcess($input, $params) {
      return $input;
   }


   /**
    * Prepare input datas for the rules collection, also using plugins values
    *
    * @since 0.84
    *
    * @param $input  the input data used to check criterias
    * @param $params parameters
    *
    * @return the updated input datas
   **/
   function prepareInputDataForProcessWithPlugins($input, $params) {
      global $PLUGIN_HOOKS;

      $input = $this->prepareInputDataForProcess($input, $params);
      if (isset($PLUGIN_HOOKS['use_rules'])) {
         foreach ($PLUGIN_HOOKS['use_rules'] as $plugin => $val) {
            if (!Plugin::isPluginLoaded($plugin)) {
               continue;
            }
            if (is_array($val) && in_array($this->getRuleClassName(), $val)) {
               $results = Plugin::doOneHook($plugin, 'ruleCollectionPrepareInputDataForProcess',
                                             ['rule_itemtype' => $this->getRuleClassName(),
                                                   'values'        => ['input' => $input,
                                                                            'params' => $params]]);
               if (is_array($results)) {
                  foreach ($results as $id => $result) {
                     $input[$id] = $result;
                  }
               }
            }
         }
      }
      return $input;
   }


   /**
    * Prepare input datas for the rules collection
    *
    * @param $condition condition to limit rules (DEFAULT 0)
    *
    * @return the updated input datas
   **/
   function prepareInputDataForTestProcess($condition = 0) {
      global $DB;

      $limit = '';
      if ($condition > 0) {
         $limit = " AND `glpi_rules`.`condition` & ". (int) $condition;
      }
      $input = [];
      $res   = $DB->query("SELECT DISTINCT `glpi_rulecriterias`.`criteria`
                           FROM `glpi_rulecriterias`, `glpi_rules`
                           WHERE `glpi_rules`.`is_active` = 1
                                 AND `glpi_rulecriterias`.`rules_id` = `glpi_rules`.`id`
                                 $limit
                                 AND `glpi_rules`.`sub_type` = '".$this->getRuleClassName()."'");

      while ($data = $DB->fetch_assoc($res)) {
         $input[] = $data["criteria"];
      }
      return $input;
   }


   /**
    * Show form displaying results for rule engine preview
    *
    * @param $target          where to go
    * @param $input     array of data
    * @param $condition       condition to limit rules (DEFAULT 0)
   **/
   function showRulesEnginePreviewResultsForm($target, array $input, $condition = 0) {

      $output = [];

      if ($this->use_output_rule_process_as_next_input) {
         $output = $input;
      }

      $output = $this->testAllRules($input, $output, $input, $condition);

      $rule   = $this->getRuleClass();

      echo "<div class='center'>";

      if (isset($output["result"])) {
         echo "<table class='tab_cadrehov'>";
         echo "<tr><th colspan='2'>" . __('Result details') . "</th></tr>\n";

         foreach ($output["result"] as $ID=>$rule_result) {
            echo "<tr class='tab_bg_1'>";
            $rule->getFromDB($ID);
            echo "<td>".$rule->fields["name"]."</td>";
            echo "<td class='b'>";

            switch ($rule_result["result"]) {
               case 0 :
               case 1 :
                  echo Dropdown::getYesNo($rule_result["result"]);
                  break;

               case 2 :
                  echo __('Inactive');
                  break;
            }

            echo "</td></tr>\n";
         }

         echo "</table>";
      }

      $output        = $this->cleanTestOutputCriterias($output);
      unset($output["result"]);
      $global_result = (count($output)?1:0);

      echo "<br><table class='tab_cadrehov'>";
      $this->showTestResults($rule, $output, $global_result);
      echo "</table></div>";
   }


   /**
    * Unset criterias from the rule's ouput results (begins by _)
    *
    * @param $output    array clean output array to clean
    *
    * @return cleaned array
   **/
   function cleanTestOutputCriterias(array $output) {

      $rule   = $this->getRuleClass();
      $actions = $rule->getAllActions();

      //If output array contains keys begining with _ : drop it
      foreach ($output as $criteria => $value) {
         if ($criteria[0] == '_'&& !isset($actions[$criteria])) {
            unset($output[$criteria]);
         }
      }
      return $output;
   }


   /**
    * Show test results for a rule
    *
    * @param $rule                     rule object
    * @param $output          array    output data array
    * @param $global_result   boolean  global result
    *
    * @return cleaned array
    **/
   function showTestResults($rule, array $output, $global_result) {

      $actions = $rule->getAllActions();
      echo "<table class='tab_cadrehov'>";
      echo "<tr><th colspan='2'>" . __('Rule results') . "</th></tr>\n";
      echo "<tr class='tab_bg_1'>";
      echo "<td class='center'>".__('Validation')."</td>";
      echo "<td><span class='b'>".Dropdown::getYesNo($global_result)."</span></td>";

      $output = $this->preProcessPreviewResults($output);

      foreach ($output as $criteria => $value) {
         if (isset($actions[$criteria])) {
            echo "<tr class='tab_bg_2'>";
            echo "<td>".$actions[$criteria]["name"]."</td>";
            $action_type = (isset($actions[$criteria]['action_type'])?$actions[$criteria]['action_type']:'');
            echo "<td>".$rule->getActionValue($criteria, $action_type, $value);
            echo "</td></tr>\n";
         }
      }
      echo "</tr></table>\n";
   }


   /**
    * @param $output
   **/
   function preProcessPreviewResults($output) {
      global $PLUGIN_HOOKS;

      if (isset($PLUGIN_HOOKS['use_rules'])) {
         $params['rule_itemtype'] = $this->getType();
         foreach ($PLUGIN_HOOKS['use_rules'] as $plugin => $val) {
            if (!Plugin::isPluginLoaded($plugin)) {
               continue;
            }
            if (is_array($val) && in_array($this->getType(), $val)) {
               $results = Plugin::doOneHook($plugin, "preProcessRuleCollectionPreviewResults",
                                            ['output' => $output,
                                                  'params' => $params]);
               if (is_array($results)) {
                  foreach ($results as $id => $result) {
                     $output[$id] = $result;
                  }
               }
            }
         }
      }
      return $this->cleanTestOutputCriterias($output);
   }


   /**
    * Print a title if needed which will be displayed above list of rules
    *
    * @return nothing (display)
   **/
   function title() {
   }


   /**
    * Get rulecollection classname by giving his itemtype
    *
    * @param $itemtype                 itemtype
    * @param $check_dictionnary_type   check if the itemtype is a dictionnary or not
    *                                  (false by default)
    *
    * @return the rulecollection class or null
    */
   static function getClassByType($itemtype, $check_dictionnary_type = false) {
      global $CFG_GLPI;

      if ($plug = isPluginItemType($itemtype)) {
         $typeclass = 'Plugin'.$plug['plugin'].$plug['class'].'Collection';

      } else {
         if (in_array($itemtype, $CFG_GLPI["dictionnary_types"])) {
            $typeclass = 'RuleDictionnary'.$itemtype."Collection";
         } else {
            $typeclass = $itemtype."Collection";
         }
      }

      if (($check_dictionnary_type && in_array($itemtype, $CFG_GLPI["dictionnary_types"]))
          || !$check_dictionnary_type) {

         if ($item = getItemForItemtype($typeclass)) {
            return $item;
         }
         return null;
      }
   }


   function showInheritedTab() {
      return false;
   }


   function showChildrensTab() {
      return false;
   }


   /**
    * Get all the fields needed to perform the rule
   **/
   function getFieldsToLookFor() {
      global $DB;

      $params = [];
      $query = "SELECT DISTINCT `glpi_rulecriterias`.`criteria` AS `criteria`
                FROM `glpi_rules`,
                     `glpi_rulecriterias`
                WHERE `glpi_rules`.`sub_type` = '".$this->getRuleClassName()."'
                      AND `glpi_rulecriterias`.`rules_id` = `glpi_rules`.`id`
                      AND `glpi_rules`.`is_active` = 1";

      foreach ($DB->request($query) as $param) {
             $params[] = Toolbox::strtolower($param["criteria"]);
      }
      return $params;
   }


   /**
    * For tabs management : force isNewItem
    *
    * @since 0.83
   **/
   function isNewItem() {
      return false;
   }


   /**
    * @see CommonGLPI::defineTabs()
   **/
   function defineTabs($options = []) {

      $ong               = [];
      $this->addStandardTab(__CLASS__, $ong, $options);
      $ong['no_all_tab'] = true;
      return $ong;
   }


   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item instanceof RuleCollection) {
         $ong = [];
         if ($item->showInheritedTab()) {
            //TRANS: %s is the entity name
            $ong[1] = sprintf(__('Rules applied: %s'),
                              Dropdown::getDropdownName('glpi_entities',
                                                        $_SESSION['glpiactive_entity']));
         }
         $title = _n('Rule', 'Rules', Session::getPluralNumber());
         if ($item->isRuleRecursive()) {
            //TRANS: %s is the entity name
            $title = sprintf(__('Local rules: %s'),
                             Dropdown::getDropdownName('glpi_entities',
                                                       $_SESSION['glpiactive_entity']));
         }
         $ong[2] = $title;
         if ($item->showChildrensTab()) {
            $ong[3] = __('Rules applicable in the sub-entities');
         }
         return $ong;
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item instanceof RuleCollection) {
         $options = $_GET;
         switch ($tabnum) {
            case 1:
               $options['inherited'] = 1;
               break;

            case 2:
               $options['inherited'] = 0;
               break;

            case 3 :
               $options['inherited'] = 0;
               $options['childrens'] = 1;
               break;
         }
         if ($item->isRuleEntityAssigned()) {
            $item->setEntity($_SESSION['glpiactive_entity']);
         }
         $item->title();
         $item->showEngineSummary();
         $item->showListRules($_GET['_target'], $options);
         return true;
      }
      return false;
   }

   /**
    * Get list of dictionnaries
    *
    * @return array
    */
   public static function getDictionnaries() {
      $dictionnaries =[];

      $entries = [];

      if (Session::haveRight("rule_dictionnary_software", READ)) {
         $entries[] = [
            'label'  => _n('Software', 'Software', 2),
            'link'   => 'ruledictionnarysoftware.php'
         ];
      }

      if (Session::haveRight("rule_dictionnary_dropdown", READ)) {
         $entries[] = [
            'label'  => _n('Manufacturer', 'Manufacturers', 2),
            'link'   => 'ruledictionnarymanufacturer.php'
         ];
      }

      if (Session::haveRight("rule_dictionnary_printer", READ)) {
         $entries[] = [
            'label'  => _n('Printer', 'Printers', 2),
            'link'   => 'ruledictionnaryprinter.php'
         ];
      }

      if (count($entries)) {
         $dictionnaries[] = [
            'type'      => __('Global dictionary'),
            'entries'   => $entries
         ];
      }

      if (Session::haveRight("rule_dictionnary_dropdown", READ)) {
         $dictionnaries[] = [
            'type'      => _n('Model', 'Models', 2),
            'entries'   => [
               [
                  'label'  => _n('Computer model', 'Computer models', 2),
                  'link'   => 'ruledictionnarycomputermodel.php'
               ], [
                  'label'  => _n('Monitor model', 'Monitor models', 2),
                  'link'   => 'ruledictionnarymonitormodel.php'
               ], [
                  'label'  => _n('Printer model', 'Printer models', 2),
                  'link'   => 'ruledictionnaryprintermodel.php'
               ], [
                  'label'  => _n('Device model', 'Device models', 2),
                  'link'   => 'ruledictionnaryperipheralmodel.php'
               ], [
                  'label'  => _n('Network equipment model', 'Network equipment models', 2),
                  'link'   => 'ruledictionnarynetworkequipmentmodel.php'
               ], [
                  'label'  => _n('Phone model', 'Phone models', 2),
                  'link'   => 'ruledictionnaryphonemodel.php'
               ]
            ]
         ];
      }

      if (Session::haveRight("rule_dictionnary_dropdown", READ)) {
         $dictionnaries[] = [
            'type'      => _n('Type', 'Types', 2),
            'entries'   => [
               [
                  'label'  => _n('Computer type', 'Computer types', 2),
                  'link'   => 'ruledictionnarycomputertype.php'
               ], [
                  'label'  => _n('Monitor type', 'Monitor types', 2),
                  'link'   => 'ruledictionnarymonitortype.php'
               ], [
                  'label'  => _n('Printer type', 'Printer types', 2),
                  'link'   => 'ruledictionnaryprintertype.php'
               ], [
                  'label'  => _n('Device type', 'Device types', 2),
                  'link'   => 'ruledictionnaryperipheraltype.php'
               ], [
                  'label'  => _n('Network equipment type', 'Network equipment types', 2),
                  'link'   => 'ruledictionnarynetworkequipmenttype.php'
               ], [
                  'label'  => _n('Phone type', 'Phone types', 2),
                  'link'   => 'ruledictionnaryphonetype.php'
               ]
            ]
         ];
      }

      if (Session::haveRight("rule_dictionnary_dropdown", READ)) {
         $dictionnaries[] = [
            'type'      => _n('Operating system', 'Operating systems', 2),
            'entries'   => [
               [
                  'label'  => _n('Operating system', 'Operating systems', 2),
                  'link'   => 'ruledictionnaryoperatingsystem.php'
               ], [
                  'label'  => _n('Service pack', 'Service packs', 2),
                  'link'   => 'ruledictionnaryoperatingsystemservicepack.php'
               ], [
                  'label'  => _n('Version', 'Versions', 2),
                  'link'   => 'ruledictionnaryoperatingsystemversion.php'
               ], [
                  'label'  => _n('Architecture', 'Architectures', 2),
                  'link'   => 'ruledictionnaryoperatingsystemarchitecture.php'
               ]
            ]
         ];
      }

      return $dictionnaries;
   }
}
