<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

class RuleAsset extends Rule {

   // From Rule
   static $rightname = 'rule_asset';
   public $can_sort  = true;

   const ONADD    = 1;
   const ONUPDATE = 2;

   const PARENT  = 1024;

   function getTitle() {
      return __('Business rules for assets');
   }


   function maybeRecursive() {
      return true;
   }


   function isEntityAssign() {
      return true;
   }


   function canUnrecurs() {
      return true;
   }


   function maxActionsCount() {
      return count($this->getActions());
   }


   static function getConditionsArray() {

      return [static::ONADD                   => __('Add'),
                   static::ONUPDATE                => __('Update'),
                   static::ONADD|static::ONUPDATE  => sprintf(__('%1$s / %2$s'), __('Add'),
                                                              __('Update'))];
   }


   function getCriterias() {

      static $criterias = [];

      if (count($criterias)) {
         return $criterias;
      }

      $criterias['_auto']['name']            = __('Automatic inventory');
      $criterias['_auto']['type']            = 'yesno';
      $criterias['_auto']['table']           = '';
      $criterias['_auto']['allow_condition'] = [Rule::PATTERN_IS, Rule::PATTERN_IS_NOT];

      $criterias['_itemtype']['name']             = __('Item type');
      $criterias['_itemtype']['type']             = 'dropdown_assets_itemtype';
      $criterias['_itemtype']['allow_condition']  = [Rule::PATTERN_IS,
                                                      Rule::PATTERN_IS_NOT,
                                                      Rule::REGEX_MATCH,
                                                      Rule::REGEX_NOT_MATCH];

      $criterias['states_id']['table']            = 'glpi_states';
      $criterias['states_id']['field']            = 'states_id';
      $criterias['states_id']['name']             = _n('Status', 'Statuses', 1);
      $criterias['states_id']['type']             = 'dropdown';

      $criterias['comment']['name']               = __('Comments');

      $criterias['contact']['name']               = __('Alternate username');

      $criterias['contact_num']['name']           = __('Alternate username number');

      $criterias['manufacturer']['name']          = __('Manufacturer');
      $criterias['manufacturer']['table']         = 'glpi_manufacturers';
      $criterias['manufacturer']['field']         = 'manufacturers_id';
      $criterias['manufacturer']['type']          = 'dropdown';

      $criterias['locations_id']['table']         = 'glpi_locations';
      $criterias['locations_id']['field']         = 'completename';
      $criterias['locations_id']['name']          = _n('Location', 'Locations', 2);
      $criterias['locations_id']['linkfield']     = 'locations_id';
      $criterias['locations_id']['type']          = 'dropdown';

      $criterias['entities_id']['table']          = 'glpi_entities';
      $criterias['entities_id']['field']          = 'name';
      $criterias['entities_id']['name']           = __('Entity');
      $criterias['entities_id']['linkfield']      = 'entities_id';
      $criterias['entities_id']['type']           = 'dropdown';

      $criterias['users_id']['name']            = __('User');
      $criterias['users_id']['type']            = 'dropdown';
      $criterias['users_id']['table']           = 'glpi_users';

      return $criterias;
   }


   function getActions() {

      $actions                                = [];

      $actions['states_id']['name']           = _n('Status', 'Statuses', 1);
      $actions['states_id']['type']           = 'dropdown';
      $actions['states_id']['table']          = 'glpi_states';

      $actions['locations_id']['name']        = _n('Location', 'Locations', 1);
      $actions['locations_id']['type']        = 'dropdown';
      $actions['locations_id']['table']       = 'glpi_locations';

      $actions['users_id']['name']            = __('User');
      $actions['users_id']['type']            = 'dropdown';
      $actions['users_id']['table']           = 'glpi_users';

      $actions['_affect_user_by_regex']['name']              = __('User based contact information');
      $actions['_affect_user_by_regex']['type']              = 'text';
      $actions['_affect_user_by_regex']['force_actions']     = ['regex_result'];
      $actions['_affect_user_by_regex']['duplicatewith']     = 'users_id';

      $actions['groups_id']['name']           = __('Group');
      $actions['groups_id']['type']           = 'dropdown';
      $actions['groups_id']['table']          = 'glpi_groups';
      $actions['groups_id']['condition']      = 'is_itemgroup';

      $actions['users_id_tech']['table']      = 'glpi_users';
      $actions['users_id_tech']['type']       = 'dropdown';
      $actions['users_id_tech']['name']       = __('Technician in charge of the hardware');

      $actions['groups_id_tech']['name']      = __('Group in charge of the hardware');
      $actions['groups_id_tech']['type']      = 'dropdown';
      $actions['groups_id_tech']['table']     = 'glpi_groups';
      $actions['groups_id_tech']['condition'] = ['is_assign' => 1];

      $actions['comment']['table']            = '';
      $actions['comment']['field']            = 'comment';
      $actions['comment']['name']             = __('Comments');

      return $actions;
   }


   function getRights($interface = 'central') {

      $values = parent::getRights();
      //TRANS: short for : Business rules for ticket (entity parent)
      $values[self::PARENT] = ['short' => __('Parent business'),
                                    'long'  => __('Business rules for ticket (entity parent)')];

      return $values;
   }


   function executeActions($output, $params, array $input = []) {

      if (count($this->actions)) {
         foreach ($this->actions as $action) {
            switch ($action->fields["action_type"]) {
               case "assign" :
                  $output[$action->fields["field"]] = $action->fields["value"];
                  break;

               case "append" :
                  $actions = $this->getActions();
                  $value   = $action->fields["value"];
                  if (isset($actions[$action->fields["field"]]["appendtoarray"])
                      && isset($actions[$action->fields["field"]]["appendtoarrayfield"])) {
                     $value = $actions[$action->fields["field"]]["appendtoarray"];
                     $value[$actions[$action->fields["field"]]["appendtoarrayfield"]]
                            = $action->fields["value"];
                  }
                  $output[$actions[$action->fields["field"]]["appendto"]][] = $value;
                  break;

               case "regex_result" :
                  switch ($action->fields["field"]) {
                     case "_affect_user_by_regex":
                        foreach ($this->regex_results as $regex_result) {
                           $res = RuleAction::getRegexResultById($action->fields["value"],
                                                                 $regex_result);
                           if ($res != null) {
                              $user = User::getIdByName(addslashes($res));
                              if ($user) {
                                 $output['users_id'] = $user;
                              }
                           }
                        }
                        break;
                     default:
                        $res = "";
                        if (isset($this->regex_results[0])) {
                           $res .= RuleAction::getRegexResultById($action->fields["value"],
                                                                  $this->regex_results[0]);
                        } else {
                           $res .= $action->fields["value"];
                        }
                        $output[$action->fields["field"]] = $res;
                        break;
                  }
                  break;

               default:
                  //plugins actions
                  $executeaction = clone $this;
                  $output = $executeaction->executePluginsActions($action, $output, $params);
                  break;
            }
         }
      }
      return $output;
   }


}
