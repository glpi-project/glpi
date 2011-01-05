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

/// Criteria Rule class
class RuleCriteria extends CommonDBChild {

   // From CommonDBChild
   public $items_id  = 'rules_id';
   public $dohistory = true;

   function __construct($rule_type='Rule') {
      $this->itemtype = $rule_type;
   }


   /**
    * Get title used in rule
    *
    * @return Title of the rule
   **/
   static function getTypeName() {
      global $LANG;

      return $LANG['rulesengine'][6];
   }


   function getNameID($with_comment=0) {
      global $CFG_GLPI,$LANG;

      $rule = new $this->itemtype ();
      return html_clean($rule->getMinimalCriteriaText($this->fields));
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'criteria';
      $tab[1]['name']          = $LANG['rulesengine'][6];
      $tab[1]['massiveaction'] = false;
      $tab[1]['datatype']      = 'string';
      
      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'condition';
      $tab[2]['name']          = $LANG['rulesengine'][14];
      $tab[2]['massiveaction'] = false;
      $tab[2]['datatype']      = 'string';
      
      $tab[3]['table']         = $this->getTable();
      $tab[3]['field']         = 'pattern';
      $tab[3]['name']          = $LANG['rulesengine'][15];
      $tab[3]['massiveaction'] = false;
      $tab[3]['datatype']      = 'string';
      
      return $tab;
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
              FROM `glpi_rulecriterias`
              WHERE `rules_id` = '$ID'
              ORDER BY `id`";
      $result = $DB->query($sql);

      $rules_list = array ();
      while ($rule = $DB->fetch_assoc($result)) {
         $tmp = new RuleCriteria;
         $tmp->fields  = $rule;
         $rules_list[] = $tmp;
      }

      return $rules_list;
   }


   /**
    * Return a value associated with a pattern associated to a criteria to compare it
    *
    * @param $condition condition used
    * @param $initValue the pattern
   **/
   function getValueToMatch($condition, &$initValue) {
      global $LANG;

      $type = $this->getType();

      if (!empty($type)
          && ($condition!=Rule::PATTERN_IS && $condition!=Rule::PATTERN_IS_NOT)) {

         switch ($this->getType()) {
            case "dropdown" :
               return Dropdown::getDropdownName($this->getTable(), $initValue);

            case "dropdown_users" :
               return getUserName($initValue);

            case "dropdown_tracking_itemtype" :
               if (class_exists($initValue)) {
                  $item = new $initValue();
                  return $item->getTypeName();
               } else {
                  if (empty($initValue)) {
                     return $LANG['help'][30];
                  }
               }
               break;

            case "dropdown_urgency" :
               return Ticket::getUrgencyName($initValue);

            case "dropdown_impact" :
               return Ticket::getImpactName($initValue);

            case "dropdown_priority" :
               return Ticket::getPriorityName($initValue);
         }
      }
      return $initValue;
   }


   /**
    * Try to match a definied rule
    *
    * @param $criterion RuleCriteria object
    * @param $field the field to match
    * @param $criterias_results
    * @param $regex_result
    *
    * @return true if the field match the rule, false if it doesn't match
   **/
   static function match(RuleCriteria &$criterion, $field, &$criterias_results, &$regex_result) {

      $condition = $criterion->fields['condition'];
      $pattern   = $criterion->fields['pattern'];
      $criteria  = $criterion->fields['criteria'];

      //If pattern is wildcard, don't check the rule and return true
      //or if the condition is "already present in GLPI" : will be processed later
      if ($pattern == Rule::RULE_WILDCARD 
            || $pattern == Rule::PATTERN_FIND) {
         return true;
      }

      // Input are slashed protected, not output.
      if (is_array($field)) {
         $field = stripslashes_deep($field);
      } else {
         // Trim for remove keyboard errors
         $field = stripslashes(trim($field));
      }

      $pattern = trim($pattern);

      if ($condition != Rule::REGEX_MATCH && $condition != Rule::REGEX_NOT_MATCH) {
         //Perform comparison with fields in lower case
         $field   = utf8_strtolower($field);
         $pattern = utf8_strtolower($pattern);
      }

      switch ($condition) {
         case Rule::PATTERN_EXISTS:
            return ($field != '');
            
         case Rule::PATTERN_DOES_NOT_EXISTS:
            return ($field == '');
            
         case Rule::PATTERN_IS :
            if (is_array($field)) {
               // Special case (used only by UNIQUE_PROFILE, for now)
               if (in_array($pattern, $field)) {
                  $criterias_results[$criteria] = $pattern;
                  return true;
               }
            } else if ($field == $pattern) {
               $criterias_results[$criteria] = $pattern;
               return true;
            }
            return false;

         case Rule::PATTERN_IS_NOT :
            if ($field != $pattern) {
               $criterias_results[$criteria] = $pattern;
               return true;
            }
            return false;

         case Rule::PATTERN_END :
            $value = "/".$pattern."$/";
            if (preg_match($value, $field) > 0) {
               $criterias_results[$criteria] = $pattern;
               return true;
            }
            return false;

         case Rule::PATTERN_BEGIN :
            if (empty($pattern)) {
               return false;
            }
            $value = strpos($field,$pattern);
            if (($value !== false) && $value == 0) {
               $criterias_results[$criteria] = $pattern;
               return true;
            }
            return false;

         case Rule::PATTERN_CONTAIN :
            if (empty($pattern)) {
               return false;
            }
            $value = strpos($field,$pattern);
            if (($value !== false) && $value >= 0) {
               $criterias_results[$criteria] = $pattern;
               return true;
            }
            return false;

         case Rule::PATTERN_NOT_CONTAIN :
            if (empty($pattern)) {
               return false;
            }
            $value = strpos($field,$pattern);
            if ($value === false) {
               $criterias_results[$criteria] = $pattern;
               return true;
            }
            return false;

         case Rule::REGEX_MATCH :
            $results = array();
            if (preg_match($pattern."i",$field,$results)>0) {
               // Drop $result[0] : complete match result
               array_shift($results);
               // And add to $regex_result array
               $regex_result[] = $results;
               $criterias_results[$criteria] = $pattern;
               return true;
            }
            return false;

         case Rule::REGEX_NOT_MATCH :
            if (preg_match($pattern."i", $field) == 0) {
               $criterias_results[$criteria] = $pattern;
               return true;
            }
            return false;
          case Rule::PATTERN_FIND:
            return true;
            
      }
      return false;
   }


   /**
    * Return the condition label by giving his ID
    *
    * @param $ID condition's ID
    *
    * @return condition's label
   **/
   static function getConditionByID($ID,$itemtype) {
      $conditions = self::getConditions($itemtype);
      if (isset($conditions[$ID])) {
         return $conditions[$ID];
      } else {
         return "";
      }
   }


   /**
    * 
    */
   static function getConditions($itemtype) {
      global $LANG;

      $criteria =  array(Rule::PATTERN_IS              => $LANG['rulesengine'][0],
                         Rule::PATTERN_IS_NOT          => $LANG['rulesengine'][1],
                         Rule::PATTERN_CONTAIN         => $LANG['rulesengine'][2],
                         Rule::PATTERN_NOT_CONTAIN     => $LANG['rulesengine'][3],
                         Rule::PATTERN_BEGIN           => $LANG['rulesengine'][4],
                         Rule::PATTERN_END             => $LANG['rulesengine'][5],
                         Rule::REGEX_MATCH             => $LANG['rulesengine'][26],
                         Rule::REGEX_NOT_MATCH         => $LANG['rulesengine'][27],
                         Rule::PATTERN_EXISTS          => $LANG['rulesengine'][31],
                         Rule::PATTERN_DOES_NOT_EXISTS => $LANG['rulesengine'][32]);
      $extra_criteria = call_user_func(array($itemtype,'addMoreCriteria'));
      foreach ($extra_criteria as $key => $value) {
         $criteria[$key] = $value;
      }
      
      return $criteria;
   }

   /**
    * Display a dropdown with all the criterias
   **/
   static function dropdownConditions($itemtype, $type, $name, $value='', $allow_condition=array()) {
      global $LANG;

      $elements = array();
      foreach (RuleCriteria::getConditions($itemtype) as $pattern => $label) {
         if (empty($allow_condition)
             || (!empty($allow_condition) && in_array($pattern,$allow_condition))) {

            $elements[$pattern] = $label;
         }
      }
      return Dropdown::showFromArray($name, $elements,array('value' => $value));
   }


}

?>
