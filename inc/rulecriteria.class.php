<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/// Criteria Rule class
class RuleCriteria extends CommonDBChild {

   // From CommonDBChild
   static public $items_id        = 'rules_id';
   public $dohistory              = true;
   public $auto_message_on_action = false;



   /**
    * @since version 0.84
   **/
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   /**
    * @param $rule_type (default 'Rule)
   **/
   function __construct($rule_type='Rule') {
      static::$itemtype = $rule_type;
   }


   /**
    * @since version 0.84.3
    *
    * @see CommonDBTM::post_getFromDB()
    */
   function post_getFromDB() {

      // Get correct itemtype if defult one is used
      if (static::$itemtype == 'Rule') {
         $rule = new Rule();
         if ($rule->getFromDB($this->fields['rules_id'])) {
            static::$itemtype = $rule->fields['sub_type'];
         }
      }
   }


   /**
    * Get title used in rule
    *
    * @param $nb  integer  for singular or plural (default 0)
    *
    * @return Title of the rule
   **/
   static function getTypeName($nb=0) {
      return _n('Criterion', 'Criteria', $nb);
   }


   /**
    * @see CommonDBTM::getRawName()
   **/
   function getRawName() {

      if ($rule = getItemForItemtype(static::$itemtype)) {
         return Html::clean($rule->getMinimalCriteriaText($this->fields));
      }
      return '';
   }


   /**
    * @since version 0.84
    *
    * @see CommonDBChild::post_addItem()
   **/
   function post_addItem() {

      parent::post_addItem();
      if (isset($this->input['rules_id'])
          && ($realrule = Rule::getRuleObjectByID($this->input['rules_id']))) {
         $realrule->update(array('id'       => $this->input['rules_id'],
                                 'date_mod' => $_SESSION['glpi_currenttime']));
      }
   }


   /**
    * @since version 0.84
    *
    * @see CommonDBTM::post_purgeItem()
   **/
   function post_purgeItem() {

      parent::post_purgeItem();
      if (isset($this->fields['rules_id'])
          && ($realrule = Rule::getRuleObjectByID($this->fields['rules_id']))) {
         $realrule->update(array('id'       => $this->fields['rules_id'],
                                 'date_mod' => $_SESSION['glpi_currenttime']));
      }
   }


   /**
    * @since version 0.84
   **/
   function prepareInputForAdd($input) {

      if (!isset($input['criteria']) || empty($input['criteria'])) {
         return false;
      }
      return parent::prepareInputForAdd($input);
   }


   function getSearchOptions() {

      $tab                     = array();

      $tab[1]['table']            = $this->getTable();
      $tab[1]['field']            = 'criteria';
      $tab[1]['name']             = __('Name');
      $tab[1]['massiveaction']    = false;
      $tab[1]['datatype']         = 'specific';
      $tab[1]['additionalfields'] = array('rules_id');

      $tab[2]['table']            = $this->getTable();
      $tab[2]['field']            = 'condition';
      $tab[2]['name']             = __('Condition');
      $tab[2]['massiveaction']    = false;
      $tab[2]['datatype']         = 'specific';
      $tab[2]['additionalfields'] = array('rules_id', 'criteria');

      $tab[3]['table']            = $this->getTable();
      $tab[3]['field']            = 'pattern';
      $tab[3]['name']             = __('Reason');
      $tab[3]['massiveaction']    = false;
      $tab[3]['datatype']         = 'specific';
      $tab[3]['additionalfields'] = array('rules_id', 'criteria', 'condition');

      return $tab;
   }


   /**
    * @since version 0.84
    *
    * @param $field
    * @param $values
    * @param $options   array
   **/
   static function getSpecificValueToDisplay($field, $values, array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      switch ($field) {
         case 'criteria' :
            $generic_rule = new Rule;
            if (isset($values['rules_id'])
                && !empty($values['rules_id'])
                && $generic_rule->getFromDB($values['rules_id'])) {
               if ($rule = getItemForItemtype($generic_rule->fields["sub_type"])) {
                  return $rule->getCriteria($values[$field]);
               }
            }
            break;

         case 'condition' :
            $generic_rule = new Rule;
            if (isset($values['rules_id'])
                && !empty($values['rules_id'])
                && $generic_rule->getFromDB($values['rules_id'])) {
               if (isset($values['criteria']) && !empty($values['criteria'])) {
                  $criterion = $values['criteria'];
               }
               return $rule->getConditionByID($values[$field], $generic_rule->fields["sub_type"], $criterion);
            }
            break;

         case 'pattern' :
            if (!isset($values["criteria"]) || !isset($values["condition"])) {
               return NOT_AVAILABLE;
            }
            $generic_rule = new Rule;
            if (isset($values['rules_id'])
                && !empty($values['rules_id'])
                && $generic_rule->getFromDB($values['rules_id'])) {
               if ($rule = getItemForItemtype($generic_rule->fields["sub_type"])) {
                  return $rule->getCriteriaDisplayPattern($values["criteria"], $values["condition"],
                                                          $values[$field]);
               }
            }
            break;
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @since version 0.84
    *
    * @param $field
    * @param $name               (default '')
    * @param $values             (default '')
    * @param $options      array
   **/
   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {
      global $DB;

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      $options['display'] = false;
      switch ($field) {
         case 'criteria' :
            $generic_rule = new Rule;
            if (isset($values['rules_id'])
                && !empty($values['rules_id'])
                && $generic_rule->getFromDB($values['rules_id'])) {
               if ($rule = getItemForItemtype($generic_rule->fields["sub_type"])) {
                  $options['value'] = $values[$field];
                  $options['name']  = $name;
                  return $rule->dropdownCriteria($options);
               }
            }
            break;

         case 'condition' :
            $generic_rule = new Rule;
            if (isset($values['rules_id'])
                && !empty($values['rules_id'])
                && $generic_rule->getFromDB($values['rules_id'])) {
               if (isset($values['criteria']) && !empty($values['criteria'])) {
                  $options['criterion'] = $values['criteria'];
               }
               $options['value'] = $values[$field];
               $options['name']  = $name;
               return $rule->dropdownConditions($generic_rule->fields["sub_type"], $options);
            }
            break;

         case 'pattern' :
            if (!isset($values["criteria"]) || !isset($values["condition"])) {
               return NOT_AVAILABLE;
            }
            $generic_rule = new Rule;
            if (isset($values['rules_id'])
                && !empty($values['rules_id'])
                && $generic_rule->getFromDB($values['rules_id'])) {
               if ($rule = getItemForItemtype($generic_rule->fields["sub_type"])) {
                  /// TODO : manage display param to this function : need to send ot to all under functions
                  $rule->displayCriteriaSelectPattern($name, $values["criteria"],
                                                      $values["condition"], $values[$field]);
               }
            }
            break;
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   /**
    * Get all criterias for a given rule
    *
    * @param $ID the rule_description ID
    *
    * @return an array of RuleCriteria objects
   **/
   function getRuleCriterias($ID) {
      global $DB;

      $sql = "SELECT *
              FROM `".$this->getTable()."`
              WHERE `".static::$items_id."` = '$ID'
              ORDER BY `id`";

      $result     = $DB->query($sql);
      $rules_list = array();
      while ($rule = $DB->fetch_assoc($result)) {
         $tmp          = new self();
         $tmp->fields  = $rule;
         $rules_list[] = $tmp;
      }

      return $rules_list;
   }


   /**
    * Try to match a defined rule
    *
    * @param &$criterion         RuleCriteria object
    * @param $field              the field to match
    * @param &$criterias_results
    * @param &$regex_result
    *
    * @return true if the field match the rule, false if it doesn't match
   **/
   static function match(RuleCriteria &$criterion, $field, &$criterias_results, &$regex_result) {

      $condition = $criterion->fields['condition'];
      $pattern   = $criterion->fields['pattern'];
      $criteria  = $criterion->fields['criteria'];

      //If pattern is wildcard, don't check the rule and return true
      //or if the condition is "already present in GLPI" : will be processed later
      if (($pattern == Rule::RULE_WILDCARD)
          || ($condition == Rule::PATTERN_FIND)) {
         return true;
      }

      $pattern = trim($pattern);

      switch ($condition) {
         case Rule::PATTERN_EXISTS :
            return (!empty($field));

         case Rule::PATTERN_DOES_NOT_EXISTS :
            return (empty($field));

         case Rule::PATTERN_IS :
            if (is_array($field)) {
               // Special case (used only by UNIQUE_PROFILE, for now)
               // $pattern is an ID
               if (in_array($pattern, $field)) {
                  $criterias_results[$criteria] = $pattern;
                  return true;
               }
            } else {
               //Perform comparison with fields in lower case
               $field                        = Toolbox::strtolower($field);
               $pattern                      = Toolbox::strtolower($pattern);
               if ($field == $pattern) {
                  $criterias_results[$criteria] = $pattern;
                  return true;
               }
            }
            return false;

         case Rule::PATTERN_IS_NOT :
            //Perform comparison with fields in lower case
            $field   = Toolbox::strtolower($field);
            $pattern = Toolbox::strtolower($pattern);
            if ($field != $pattern) {
               $criterias_results[$criteria] = $pattern;
               return true;
            }
            return false;

         case Rule::PATTERN_UNDER :
            $table  = getTableNameForForeignKeyField($criteria);
            $values = getSonsOf($table, $pattern);
            if (isset($values[$field])) {
               return true;
            }
            return false;

         case Rule::PATTERN_NOT_UNDER :
            $table  = getTableNameForForeignKeyField($criteria);
            $values = getSonsOf($table, $pattern);
            if (isset($values[$field])) {
               return false;
            }
            return true;

         case Rule::PATTERN_END :
            $value = "/".$pattern."$/i";
            if (preg_match($value, $field) > 0) {
               $criterias_results[$criteria] = $pattern;
               return true;
            }
            return false;

         case Rule::PATTERN_BEGIN :
            if (empty($pattern)) {
               return false;
            }
            $value = mb_stripos($field, $pattern, 0, 'UTF-8');
            if (($value !== false) && ($value == 0)) {
               $criterias_results[$criteria] = $pattern;
               return true;
            }
            return false;

         case Rule::PATTERN_CONTAIN :
            if (empty($pattern)) {
               return false;
            }
            $value = mb_stripos($field, $pattern, 0, 'UTF-8');
            if (($value !== false) && ($value >= 0)) {
               $criterias_results[$criteria] = $pattern;
               return true;
            }
            return false;

         case Rule::PATTERN_NOT_CONTAIN :
            if (empty($pattern)) {
               return false;
            }
            $value = mb_stripos($field, $pattern, 0, 'UTF-8');
            if ($value === false) {
               $criterias_results[$criteria] = $pattern;
               return true;
            }
            return false;

         case Rule::REGEX_MATCH :
            $results = array();
            // Permit use < and >
            $pattern = Toolbox::unclean_cross_side_scripting_deep($pattern);
            if (preg_match_all($pattern."i",$field,$results)>0) {
               // Drop $result[0] : complete match result
               array_shift($results);
               // And add to $regex_result array
               $res = array();
               foreach($results as $data) {
                  $res[] = $data[0];
               }
               $regex_result[]               = $res;
               $criterias_results[$criteria] = $pattern;
               return true;
            }
            return false;

         case Rule::REGEX_NOT_MATCH :
            // Permit use < and >
            $pattern = Toolbox::unclean_cross_side_scripting_deep($pattern);
            if (preg_match($pattern."i", $field) == 0) {
               $criterias_results[$criteria] = $pattern;
               return true;
            }
            return false;

         case Rule::PATTERN_FIND :
         case Rule::PATTERN_IS_EMPTY :
            // Global criteria will be evaluated later
            return true;
      }
      return false;
   }


   /**
    * Return the condition label by giving his ID
    *
    * @param $ID        condition's ID
    * @param $itemtype  itemtype
    * @param $criterion (default '')
    *
    * @return condition's label
   **/
   static function getConditionByID($ID, $itemtype, $criterion='') {

      $conditions = self::getConditions($itemtype, $criterion);
      if (isset($conditions[$ID])) {
         return $conditions[$ID];
      }
      return "";
   }


   /**
    * @param $itemtype  itemtype
    * @param $criterion (default '')
    *
    * @return array of criteria
   **/
   static function getConditions($itemtype, $criterion='') {

      $criteria =  array(Rule::PATTERN_IS              => __('is'),
                         Rule::PATTERN_IS_NOT          => __('is not'),
                         Rule::PATTERN_CONTAIN         => __('contains'),
                         Rule::PATTERN_NOT_CONTAIN     => __('does not contain'),
                         Rule::PATTERN_BEGIN           => __('starting with'),
                         Rule::PATTERN_END             => __('finished by'),
                         Rule::REGEX_MATCH             => __('regular expression matches'),
                         Rule::REGEX_NOT_MATCH         => __('regular expression does not match'),
                         Rule::PATTERN_EXISTS          => __('exists'),
                         Rule::PATTERN_DOES_NOT_EXISTS => __('does not exist'));

      $extra_criteria = call_user_func(array($itemtype, 'addMoreCriteria'), $criterion);

      foreach ($extra_criteria as $key => $value) {
         $criteria[$key] = $value;
      }

      /// Add Under criteria if tree dropdown table used
      if ($item = getItemForItemtype($itemtype)) {
         $crit = $item->getCriteria($criterion);

         if (isset($crit['type']) && ($crit['type'] == 'dropdown')) {
            $crititemtype = getItemtypeForTable($crit['table']);

            if (($item = getItemForItemtype($crititemtype))
                && $item instanceof CommonTreeDropdown) {
               $criteria[Rule::PATTERN_UNDER]     = __('under');
               $criteria[Rule::PATTERN_NOT_UNDER] = __('not under');
            }
         }
      }

      return $criteria;
   }


   /**
    * Display a dropdown with all the criterias
    *
    * @param $itemtype
    * @param $params    array
   **/
   static function dropdownConditions($itemtype, $params=array()) {

      $p['name']             = 'condition';
      $p['criterion']        = '';
      $p['allow_conditions'] = array();
      $p['value']            = '';
      $p['display']          = true;

      foreach ($params as $key => $value) {
         $p[$key] = $value;
      }
      $elements = array();
      foreach (self::getConditions($itemtype, $p['criterion']) as $pattern => $label) {
         if (empty($p['allow_conditions'])
             || (!empty($p['allow_conditions']) && in_array($pattern,$p['allow_conditions']))) {
            $elements[$pattern] = $label;
         }
      }
      return Dropdown::showFromArray($p['name'], $elements, array('value' => $p['value']));
   }


   /** form for rule criteria
    *
    * @since version 0.85
    *
    * @param $ID      integer  Id of the criteria
    * @param $options array    of possible options:
    *     - rule Object : the rule
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      // Yllen: you always have parent for criteria
      $rule = $options['parent'];

      if ($ID > 0) {
         $this->check($ID, READ);
      } else {
         // Create item
         $options[static::$items_id] = $rule->getField('id');

         //force itemtype of parent
         static::$itemtype = get_class($rule);

         $this->check(-1, CREATE, $options);
      }
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td class='center'>"._n('Criterion', 'Criteria', 1) . "</td><td colspan='3'>";
      echo "<input type='hidden' name='".$rule->getRuleIdField()."' value='".
             $this->fields[$rule->getRuleIdField()]."'>";

      $rand   = $rule->dropdownCriteria(array('value' => $this->fields['criteria']));
      $params = array('criteria' => '__VALUE__',
                      'rand'     => $rand,
                      'sub_type' => $rule->getType());

      Ajax::updateItemOnSelectEvent("dropdown_criteria$rand", "criteria_span",
                                    $CFG_GLPI["root_doc"]."/ajax/rulecriteria.php", $params);

      if (isset($this->fields['criteria']) && !empty($this->fields['criteria'])) {
         $params['criteria']  = $this->fields['criteria'];
         $params['condition'] = $this->fields['condition'];
         $params['pattern']   = $this->fields['pattern'];
         echo "<script type='text/javascript' >\n";
         Ajax::updateItemJsCode("criteria_span",
                                 $CFG_GLPI["root_doc"]."/ajax/rulecriteria.php",
                                 $params);
         echo '</script>';
      }

      if ($rule->specific_parameters) {
         $itemtype = get_class($rule).'Parameter';
         echo "<img alt='' title=\"".__s('Add a criterion')."\" src='".$CFG_GLPI["root_doc"].
                "/pics/add_dropdown.png' style='cursor:pointer; margin-left:2px;'
                onClick=\"".Html::jsGetElementbyID('addcriterion'.$rand).".dialog('open');\">";
         Ajax::createIframeModalWindow('addcriterion'.$rand,
                                       Toolbox::getItemTypeFormURL($itemtype),
                                       array('reloadonclose' => true));
      }

      echo "</td></tr>";
      echo "<tr><td colspan='4'><span id='criteria_span'>\n";
      echo "</span></td></tr>\n";
      $this->showFormButtons($options);
   }

}
?>
