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
class RuleImportEntity extends Rule {

   // From Rule
   static $rightname = 'rule_import';
   public $can_sort  = true;


   function getTitle() {
      return __('Rules for assigning an item to an entity');
   }


   /**
    * @see Rule::maxActionsCount()
   **/
   function maxActionsCount() {
      // Unlimited
      return 4;
   }

   function executeActions($output, $params, array $input = []) {

      if (count($this->actions)) {
         foreach ($this->actions as $action) {
            switch ($action->fields["action_type"]) {
               case "assign" :
                  $output[$action->fields["field"]] = $action->fields["value"];
                  break;

               case "regex_result" :
                  //Assign entity using the regex's result
                  if ($action->fields["field"] == "_affect_entity_by_tag") {
                     //Get the TAG from the regex's results
                     if (isset($this->regex_results[0])) {
                        $res = RuleAction::getRegexResultById($action->fields["value"],
                                                              $this->regex_results[0]);
                     } else {
                        $res = $action->fields["value"];
                     }
                     if ($res != null) {
                        //Get the entity associated with the TAG
                        $target_entity = Entity::getEntityIDByTag(addslashes($res));
                        if ($target_entity != '') {
                           $output["entities_id"] = $target_entity;
                        }
                     }
                  }
                  break;
            }
         }
      }
      return $output;
   }


   /**
    * @see Rule::getCriterias()
   **/
   function getCriterias() {

      static $criterias = [];

      if (count($criterias)) {
         return $criterias;
      }

      $criterias['_source']['table']            = '';
      $criterias['_source']['field']            = '_source';
      $criterias['_source']['name']             = __('Source');
      $criterias['_source']['allow_condition']  = [Rule::PATTERN_IS, Rule::PATTERN_IS_NOT];

      return $criterias;
   }


   /**
    * @since 0.84
    *
    * @see Rule::displayAdditionalRuleCondition()
   **/
   function displayAdditionalRuleCondition($condition, $criteria, $name, $value, $test = false) {
      global $PLUGIN_HOOKS;

      if ($criteria['field'] == '_source') {
         $tab = [];
         foreach ($PLUGIN_HOOKS['import_item'] as $plug => $types) {
            $tab[$plug] = Plugin::getInfo($plug, 'name');
         }
         Dropdown::showFromArray($name, $tab);
         return true;
      }
      return false;
   }


   /**
    * @since 0.84
    *
    * @see Rule::getAdditionalCriteriaDisplayPattern()
   **/
   function getAdditionalCriteriaDisplayPattern($ID, $condition, $pattern) {

      $crit = $this->getCriteria($ID);
      if (count($crit) && $crit['field'] == '_source') {
         $name = Plugin::getInfo($pattern, 'name');
         if (empty($name)) {
            return false;
         }
         return $name;
      }
   }


   /**
    * @see Rule::getActions()
   **/
   function getActions() {

      $actions                             = [];

      $actions['entities_id']['name']      = __('Entity');
      $actions['entities_id']['type']      = 'dropdown';
      $actions['entities_id']['table']     = 'glpi_entities';

      $actions['locations_id']['name']     = __('Location');
      $actions['locations_id']['type']     = 'dropdown';
      $actions['locations_id']['table']    = 'glpi_locations';

      $actions['groups_id']['name']     = __('Group');
      $actions['groups_id']['type']     = 'dropdown';
      $actions['groups_id']['table']    = 'glpi_groups';

      $actions['_ignore_import']['name']   = __('To be unaware of import');
      $actions['_ignore_import']['type']   = 'yesonly';

      $actions['is_recursive']['name']     = __('Child entities');
      $actions['is_recursive']['type']     = 'yesno';

      $actions['groups_id_tech']['name']     = __('Group in charge of the hardware');
      $actions['groups_id_tech']['type']     = 'dropdown';
      $actions['groups_id_tech']['table']    = 'glpi_groups';

      return $actions;
   }

}
