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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/// OCS Rules class
class RuleImportComputer extends Rule {

   const RULE_ACTION_LINK_OR_IMPORT    = 0;
   const RULE_ACTION_LINK_OR_NO_IMPORT = 1;


   public $restrict_matching = Rule::AND_MATCHING;
   public $can_sort          = true;

   static $rightname         = 'rule_import';



   function getTitle() {
      return __('Rules for import and link computers');
   }


   /**
    * @see Rule::maxActionsCount()
   **/
   function maxActionsCount() {
      // Unlimited
      return 1;
   }


   function getCriterias() {

      static $criterias = [];

      if (count($criterias)) {
         return $criterias;
      }

      $criterias['entities_id']['table']         = 'glpi_entities';
      $criterias['entities_id']['field']         = 'entities_id';
      $criterias['entities_id']['name']          = __('Target entity for the computer');
      $criterias['entities_id']['linkfield']     = 'entities_id';
      $criterias['entities_id']['type']          = 'dropdown';

      $criterias['states_id']['table']           = 'glpi_states';
      $criterias['states_id']['field']           = 'name';
      $criterias['states_id']['name']            = __('Find computers in GLPI having the status');
      $criterias['states_id']['linkfield']       = 'state';
      $criterias['states_id']['type']            = 'dropdown';
      //Means that this criterion can only be used in a global search query
      $criterias['states_id']['is_global']       = true;
      $criterias['states_id']['allow_condition'] = [Rule::PATTERN_IS, Rule::PATTERN_IS_NOT];

      $criterias['DOMAIN']['name']               = __('Domain');

      $criterias['IPSUBNET']['name']             = __('Subnet');

      $criterias['MACADDRESS']['name']           = __('MAC address');

      $criterias['IPADDRESS']['name']            = __('IP address');

      $criterias['name']['name']                 = __("Computer's name");
      $criterias['name']['allow_condition']      = [Rule::PATTERN_IS, Rule::PATTERN_IS_NOT,
                                                         Rule::PATTERN_IS_EMPTY,
                                                         Rule::PATTERN_FIND];

      $criterias['DESCRIPTION']['name']          = __('Description');

      $criterias['serial']['name']               = __('Serial number');

      // Model as Text to allow text criteria (contains, regex, ...)
      $criterias['model']['name']                = __('Model');

      // Manufacturer as Text to allow text criteria (contains, regex, ...)
      $criterias['manufacturer']['name']         = __('Manufacturer');

      return $criterias;
   }


   function getActions() {

      $actions                           = [];

      $actions['_ignore_import']['name'] = __('To be unaware of import');
      $actions['_ignore_import']['type'] = 'yesonly';

      return $actions;
   }


   static function getRuleActionValues() {

      return [self::RULE_ACTION_LINK_OR_IMPORT
                                          => __('Link if possible'),
                   self::RULE_ACTION_LINK_OR_NO_IMPORT
                                          => __('Link if possible, otherwise imports declined')];
   }


   /**
    * Add more action values specific to this type of rule
    *
    * @see Rule::displayAdditionRuleActionValue()
    *
    * @param value the value for this action
    *
    * @return the label's value or ''
   **/
   function displayAdditionRuleActionValue($value) {

      $values = self::getRuleActionValues();
      if (isset($values[$value])) {
         return $values[$value];
      }
      return '';
   }


   /**
    * @param $criteria
    * @param $name
    * @param $value
   **/
   function manageSpecificCriteriaValues($criteria, $name, $value) {

      switch ($criteria['type']) {
         case "state" :
            $link_array = ["0" => __('No'),
                                "1" => __('Yes if equal'),
                                "2" => __('Yes if empty')];

            Dropdown::showFromArray($name, $link_array, ['value' => $value]);
      }
      return false;
   }


   /**
    * Add more criteria specific to this type of rule
   **/
   static function addMoreCriteria() {

      return [Rule::PATTERN_FIND     => __('is already present in GLPI'),
                   Rule::PATTERN_IS_EMPTY => __('is empty in GLPI')];
   }


   /**
    * @see Rule::getAdditionalCriteriaDisplayPattern()
   **/
   function getAdditionalCriteriaDisplayPattern($ID, $condition, $pattern) {

      if ($condition == Rule::PATTERN_IS_EMPTY) {
          return __('Yes');
      }
      return false;
   }


   /**
    * @see Rule::displayAdditionalRuleCondition()
   **/
   function displayAdditionalRuleCondition($condition, $criteria, $name, $value, $test = false) {

      if ($test) {
         return false;
      }

      switch ($condition) {
         case Rule::PATTERN_FIND :
         case Rule::PATTERN_IS_EMPTY :
            Dropdown::showYesNo($name, 0, 0);
            return true;
      }

      return false;
   }


   /**
    * @see Rule::displayAdditionalRuleAction()
   **/
   function displayAdditionalRuleAction(array $action, $value = '') {

      switch ($action['type']) {
         case 'fusion_type' :
            Dropdown::showFromArray('value', self::getRuleActionValues());
            return true;
      }
      return false;
   }


   /**
    * @param $ID
   **/
   function getCriteriaByID($ID) {

      $criteria = [];
      foreach ($this->criterias as $criterion) {
         if ($ID == $criterion->fields['criteria']) {
            $criteria[] = $criterion;
         }
      }
      return $criteria;
   }


   /**
    * @see Rule::findWithGlobalCriteria()
   **/
   function findWithGlobalCriteria($input) {
      global $DB, $PLUGIN_HOOKS;

      $complex_criterias = [];
      $sql_where         = '';
      $sql_from          = '';
      $continue          = true;
      $global_criteria   = ['manufacturer', 'model', 'name', 'serial'];

      //Add plugin global criteria
      if (isset($PLUGIN_HOOKS['use_rules'])) {
         foreach ($PLUGIN_HOOKS['use_rules'] as $plugin => $val) {
            if (!Plugin::isPluginLoaded($plugin)) {
               continue;
            }
            if (is_array($val) && in_array($this->getType(), $val)) {
               $global_criteria = Plugin::doOneHook($plugin, "ruleImportComputer_addGlobalCriteria",
                                                    $global_criteria);
            }
         }
      }

      foreach ($global_criteria as $criterion) {
         $criteria = $this->getCriteriaByID($criterion);
         if (!empty($criteria)) {
            foreach ($criteria as $crit) {

               // is a real complex criteria
               if ($crit->fields["condition"] == Rule::PATTERN_FIND) {
                  if (!isset($input[$criterion]) || ($input[$criterion] == '')) {
                     $continue = false;
                  } else {
                     $complex_criterias[] = $crit;
                  }
               }
            }
         }
      }

      foreach ($this->getCriteriaByID('states_id') as $crit) {
         $complex_criterias[] = $crit;
      }

      //If a value is missing, then there's a problem !
      if (!$continue) {
         return false;
      }

      //No complex criteria
      if (empty($complex_criterias)) {
         return true;
      }

      //Build the request to check if the machine exists in GLPI
      if (is_array($input['entities_id'])) {
         $where_entity = implode($input['entities_id'], ',');
      } else {
         $where_entity = $input['entities_id'];
      }

      $sql_where = '1';
      $sql_from  = '';

      $needport = false;
      $needip   = false;
      foreach ($complex_criterias as $criteria) {
         switch ($criteria->fields['criteria']) {
            case 'name' :
               if ($criteria->fields['condition'] == Rule::PATTERN_IS_EMPTY) {
                  $sql_where .= " AND (`glpi_computers`.`name`=''
                                       OR `glpi_computers`.`name` IS NULL) ";
               } else {
                  $sql_where .= " AND (`glpi_computers`.`name`='".$input['name']."') ";
               }
               break;

            case 'serial' :
               $sql_where .= " AND `glpi_computers`.`serial`='".$input["serial"]."'";
               break;

            case 'model' :
               // search for model, don't create it if not found
               $options    = ['manufacturer' => addslashes($input['manufacturer'])];
               $mid        = Dropdown::importExternal('ComputerModel', addslashes($input['model']), -1,
                                                      $options, '', false);
               $sql_where .= " AND `glpi_computers`.`computermodels_id` = '$mid'";
               break;

            case 'manufacturer' :
               // search for manufacturer, don't create it if not found
               $mid        = Dropdown::importExternal('Manufacturer', addslashes($input['manufacturer']), -1,
                                                      [], '', false);
               $sql_where .= " AND `glpi_computers`.`manufacturers_id` = '$mid'";
               break;

            case 'states_id' :
               if ($criteria->fields['condition'] == Rule::PATTERN_IS) {
                  $condition = " IN ";
               } else {
                  $condition = " NOT IN ";
               }
               $sql_where .= " AND `glpi_computers`.`states_id`
                                 $condition ('".$criteria->fields['pattern']."')";
               break;
         }
      }

      if (isset($PLUGIN_HOOKS['use_rules'])) {
         foreach ($PLUGIN_HOOKS['use_rules'] as $plugin => $val) {
            if (!Plugin::isPluginLoaded($plugin)) {
               continue;
            }
            if (is_array($val) && in_array($this->getType(), $val)) {
               $params      = ['where_entity' => $where_entity,
                                    'input'        => $input,
                                    'criteria'     => $complex_criterias,
                                    'sql_where'    => $sql_where,
                                    'sql_from'     => $sql_from];
               $sql_results = Plugin::doOneHook($plugin, "ruleImportComputer_getSqlRestriction",
                                                $params);
               $sql_where   = $sql_results['sql_where'];
               $sql_from    = $sql_results['sql_from'];
            }
         }
      }

      $sql_glpi = "SELECT `glpi_computers`.`id`
                   FROM $sql_from
                   WHERE $sql_where
                   ORDER BY `glpi_computers`.`is_deleted` ASC";
      $result_glpi = $DB->query($sql_glpi);

      if ($DB->numrows($result_glpi) > 0) {
         while ($data = $DB->fetchAssoc($result_glpi)) {
            $this->criterias_results['found_computers'][] = $data['id'];
         }
         return true;
      }

      if (count($this->actions)) {
         foreach ($this->actions as $action) {
            if ($action->fields['field'] == '_fusion') {
               if ($action->fields["value"] == self::RULE_ACTION_LINK_OR_NO_IMPORT) {
                  return true;
               }
            }
         }
      }
      return false;

   }

   function executeActions($output, $params, array $input = []) {

      if (count($this->actions)) {
         foreach ($this->actions as $action) {
            $executeaction = clone $this;
            $ruleoutput    = $executeaction->executePluginsActions($action, $output, $params, $input);
            foreach ($ruleoutput as $key => $value) {
               $output[$key] = $value;
            }
         }
      }
      return $output;
   }

   /**
    * Function used to display type specific criterias during rule's preview
    *
    * @see Rule::showSpecificCriteriasForPreview()
   **/
   function showSpecificCriteriasForPreview($fields) {

      $entity_as_criteria = false;
      foreach ($this->criterias as $criteria) {
         if ($criteria->fields['criteria'] == 'entities_id') {
            $entity_as_criteria = true;
            break;
         }
      }
      if (!$entity_as_criteria) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan ='2'>".__('Entity')."</td>";
         echo "<td>";
         Dropdown::show('Entity');
         echo "</td></tr>";
      }
   }

}
