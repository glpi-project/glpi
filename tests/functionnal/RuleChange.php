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

namespace tests\units;

use \DbTestCase;

/* Test for inc/rulechange.class.php */

class RuleChange extends DbTestCase {

   /**
    * change criteria data provider
    *
    * @return array
    */
   protected function changeRuleCriteriaProvider() {
      return [
         [
            'criteria'   => 'name',
            'condition'  => '2',
            'pattern'    => 'trytest'
         ],
         [
            'criteria'   => 'content',
            'condition'  => '2',
            'pattern'    => 'trytest'
         ],
         [
            'criteria'   => 'itilcategories_id',
            'condition'  => '0',
            'pattern'    => '10'
         ],
         [
            'criteria'   => 'type',
            'condition'  => '0',
            'pattern'    => '14'
         ],
         [
            'criteria'   => 'urgency',
            'condition'  => '0',
            'pattern'    => '1'
         ],
         [
            'criteria'   => 'impact',
            'condition'  => '0',
            'pattern'    => '1'
         ],
         [
            'criteria'   => 'priority',
            'condition'  => '0',
            'pattern'    => '1'
         ],
         [
            'criteria'   => 'service_unavailability',
            'condition'  => '0',
            'pattern'    => '1'
         ]
      ];
   }

   /**
    * change action data provider
    *
    * @return array
    */
   protected function changeRuleActionProvider() {
      return [
         [
            'field' => 'itilcategories_id',
            'value' => '102',
            'type'  => 'assign'
         ],
         [
            'field' => 'type',
            'value' => '104',
            'type'  => 'assign'
         ],
         [
            'field' => '_users_id_requester',
            'value' => '106',
            'type'  => 'assign'
         ],
         [
            'field' => '_groups_id_requester',
            'value' => '108',
            'type'  => 'assign'
         ],
         [
            'field' => '_users_id_assign',
            'value' => '110',
            'type'  => 'assign'
         ],
         [
            'field' => '_groups_id_assign',
            'value' => '112',
            'type'  => 'assign'
         ],
         // [
         //    'field' => '_suppliers_id_assign',
         //    'value' => '114'
         // ],
         [
            'field' => '_users_id_observer',
            'value' => '116',
            'type'  => 'assign'
         ],
         [
            'field' => '_groups_id_observer',
            'value' => '118',
            'type'  => 'assign'
         ],
         [
            'field' => 'urgency',
            'value' => '1',
            'type'  => 'assign'
         ],
         [
            'field' => 'impact',
            'value' => '1',
            'type'  => 'assign'
         ],
         [
            'field' => 'priority',
            'value' => '1',
            'type'  => 'assign'
         ],
         [
            'field' => 'status',
            'value' => '7',
            'type'  => 'assign'
         ],
         [
            'field' => 'affectobject',
            'value' => '192.168.0.2',
            'type'  => 'affectbyip'
         ],
         [
            'field' => 'users_id_validate',
            'value' => '5',
            'type'  => 'add_validation'
         ],
         [
            'field' => 'responsible_id_validate',
            'value' => '1',
            'type'  => 'add_validation'
         ],
         [
            'field' => 'groups_id_validate',
            'value' => '128',
            'type'  => 'add_validation'
         ],
         [
            'field' => 'validation_percent',
            'value' => '60',
            'type'  => 'assign'
         ],
         [
            'field' => 'service_unavailability',
            'value' => '1',
            'type'  => 'assign'
         ],
      ];
   }

   public function testGetCriteria() {
      $rule = new \RuleChange();
      $criteria = $rule->getCriterias();
      $this->array($criteria)->size->isGreaterThan(20);
   }

   public function testGetActions() {
      $rule = new \RuleChange();
      $actions  = $rule->getActions();
      $this->array($actions)->size->isGreaterThan(20);
   }

   /**
    * @dataProvider changeRuleCriteriaProvider
    */
   public function testCriteriaAdd($criteria, $condition, $pattern) {
      $this->login();

      // prepare rule
      $this->_createTestTriggerRule(\RuleChange::ONADD, $criteria, $condition, $pattern);

      // test create change (trigger on title)
      $change = new \Change;
      $change_input = [
         'name'    => "test change, will trigger on rule (title)",
         'content' => "test"
      ];
      $change_input[$criteria] = $pattern;
      $changes_id = $change->add($change_input);
      if ($criteria == 'urgency') {
         $change_input['urgency'] = 5;
      }
      $this->checkInput($change, $changes_id, $change_input);
      $this->integer((int)$change->getField('urgency'))->isEqualTo(5);
   }

   /**
    * @dataProvider changeRuleCriteriaProvider
    */
   public function testCriteriaUpdate($criteria, $condition, $pattern) {
      $this->login();

      // prepare rule
      $this->_createTestTriggerRule(\RuleChange::ONUPDATE, $criteria, $condition, $pattern);

      // test create change (trigger on title)
      $change = new \Change;
      $change_input = [
         'name'    => "test change, will trigger on rule (title)",
         'content' => "test"
      ];
      $changes_id = $change->add($change_input);
      $this->checkInput($change, $changes_id, $change_input);
      $this->integer((int)$change->getField('urgency'))->isEqualTo(3);

      $update_input = [
         'id'      => $changes_id,
         $criteria => $pattern
      ];
      $change_input[$criteria] = $pattern;
      $change->update($update_input);

      $change->getFromDB($changes_id);
      if ($criteria == 'urgency') {
         $change_input['urgency'] = 5;
      }
      $this->checkInput($change, $changes_id, $change_input);
      $this->integer((int)$change->getField('urgency'))->isEqualTo(5);
   }


   /**
    * @dataProvider changeRuleActionProvider
    */
   public function testActionsAdd($field, $value, $type) {
      global $DB;
      $this->login();

      if ($field == 'groups_id_validate') {
         $group = new \Group();
         $group_user = new \Group_User();
         $input = ['name' => 'group xxx'];
         $groups_id = $group->add($input);
         $group_user->add(['groups_id' => $groups_id, 'users_id' => 2]);
         $group_user->add(['groups_id' => $groups_id, 'users_id' => 5]);
         $value = $groups_id;
      }

      // prepare rule
      $this->_createTestTriggerRuleAction(\RuleChange::ONADD, $field, $value, $type);

      // Special cases
      if ($field == 'affectobject') {
         $computer = new \Computer();
         $networkPort = new \NetworkPort();
         $input = [
            'name'        => 'computer xxx',
            'entities_id' => 0,
         ];
         $computers_id = $computer->add($input);
         $input = [
            'entities_id' => 0,
            'items_id' => $computers_id,
            'itemtype' => 'Computer',
            '_create_children' => 1,
            'instantiation_type' => 'NetworkPortEthernet',
            'logical_number' => 1,
            'name' => 'em0',
            'NetworkName__ipaddresses' => [
               '-1' => $value
            ],
         ];
         $networkPort->add($input);
         $value = (string)$computers_id;
      }

      // test create change (trigger on title)
      $change = new \Change;
      $change_input = [
         'entities_id' => 0,
         'name'        => "test change, will trigger on rule (title)",
         'content'     => "test"
      ];
      $changes_id = $change->add($change_input);
      $changefield = $change->getField($field);
      $changefield = $this->_getUserGroupOfChange($field, $changefield, $changes_id);
      // Special case for responsible validate
      if ($field == 'responsible_id_validate') {
         $value = '4';
      }

      if ($field == 'groups_id_validate') {
         $this->array($changefield)->isEqualTo([2, 5]);
      } else {
         $this->string((string)$changefield)->isEqualTo($value);
      }
   }


   /**
    * @dataProvider changeRuleActionProvider
    */
   public function testActionsUpdate($field, $value, $type) {
      global $DB;
      $this->login();

      if ($field == 'groups_id_validate') {
         $group = new \Group();
         $group_user = new \Group_User();
         $input = ['name' => 'group xxx'];
         $groups_id = $group->add($input);
         $group_user->add(['groups_id' => $groups_id, 'users_id' => 2]);
         $group_user->add(['groups_id' => $groups_id, 'users_id' => 5]);
         $value = $groups_id;
      }

      // prepare rule
      $this->_createTestTriggerRuleAction(\RuleChange::ONUPDATE, $field, $value, $type);

      // Special cases
      if ($field == 'affectobject') {
         $computer = new \Computer();
         $networkPort = new \NetworkPort();
         $input = [
            'name'        => 'computer xxx',
            'entities_id' => 0,
         ];
         $computers_id = $computer->add($input);
         $input = [
            'entities_id' => 0,
            'items_id' => $computers_id,
            'itemtype' => 'Computer',
            '_create_children' => 1,
            'instantiation_type' => 'NetworkPortEthernet',
            'logical_number' => 1,
            'name' => 'em0',
            'NetworkName__ipaddresses' => [
               '-1' => $value
            ],
         ];
         $networkPort->add($input);
         $value = (string)$computers_id;
      }

      // test create change (trigger on title)
      $change = new \Change;
      $change_input = [
         'entities_id' => 0,
         'name'        => "new change",
         'content'     => "test"
      ];
      $changes_id = $change->add($change_input);
      $this->string((string)$change->getField($field))->isNotEqualTo($value);

      $update_input = [
         'id'   => $changes_id,
         'name' => 'test'
      ];
      $change->update($update_input);

      $change->getFromDB($changes_id);
      $changefield = $change->getField($field);
      $changefield = $this->_getUserGroupOfChange($field, $changefield, $changes_id);
      // Special case for responsible validate
      if ($field == 'responsible_id_validate') {
         $value = '4';
      }
      if ($field == 'groups_id_validate') {
         $this->array($changefield)->isEqualTo([2, 5]);
      } else {
         $this->string((string)$changefield)->isEqualTo($value);
      }
   }


   /**
    * Private function used to create a change rule
    */
   private function _createTestTriggerRule($useRuleFor, $criteria, $condition, $pattern) {

      $rulechange = new \RuleChange;
      $rulechangeCollection = new \RuleChangeCollection();
      $rulecrit   = new \RuleCriteria;
      $ruleaction = new \RuleAction;

      $this->_deleteChangeRules();

      $ruletid = $rulechange->add($ruletinput = [
         'name'         => "test rule add ".$criteria,
         'match'        => 'OR',
         'is_active'    => 1,
         'sub_type'     => 'RuleChange',
         'condition'    => $useRuleFor,
         'is_recursive' => 1
      ]);
      $this->checkInput($rulechange, $ruletid, $ruletinput);
      $crit_id = $rulecrit->add($crit_input = [
         'rules_id'  => $ruletid,
         'criteria'  => $criteria,
         'condition' => $condition,
         'pattern'   => $pattern
      ]);
      $this->checkInput($rulecrit, $crit_id, $crit_input);
      $act_id = $ruleaction->add($act_input = [
         'rules_id'    => $ruletid,
         'action_type' => 'assign',
         'field'       => 'urgency',
         'value'       => 5
      ]);
      $this->checkInput($ruleaction, $act_id, $act_input);
   }

   private function _deleteChangeRules() {
      global $DB;

      $rulechange = new \RuleChange;
      $iterator = $DB->request([
         'FIELDS' => ['id'],
         'FROM'   => 'glpi_rules',
         'WHERE'  => [
            'sub_type' => 'RuleChange'
         ]
      ]);
      while ($data = $iterator->next()) {
         $rulechange->delete($data, true);
      }
   }

   /**
    * Private function used to create a change rule
    */
   private function _createTestTriggerRuleAction($useRuleFor, $field, $value, $type) {

      $rulechange = new \RuleChange;
      $rulechangeCollection = new \RuleChangeCollection();
      $rulecrit   = new \RuleCriteria;
      $ruleaction = new \RuleAction;

      $this->_deleteChangeRules();

      $ruletid = $rulechange->add($ruletinput = [
         'name'         => "test rule add ".$field,
         'match'        => 'OR',
         'is_active'    => 1,
         'sub_type'     => 'RuleChange',
         'condition'    => $useRuleFor,
         'is_recursive' => 1
      ]);
      $this->checkInput($rulechange, $ruletid, $ruletinput);
      $crit_id = $rulecrit->add($crit_input = [
         'rules_id'  => $ruletid,
         'criteria'  => 'name',
         'condition' => 2,
         'pattern'   => 'test'
      ]);
      $this->checkInput($rulecrit, $crit_id, $crit_input);
      $act_id = $ruleaction->add($act_input = [
         'rules_id'    => $ruletid,
         'action_type' => $type,
         'field'       => $field,
         'value'       => $value
      ]);
      $this->checkInput($ruleaction, $act_id, $act_input);

      // special case responsible_id_validate
      if ($field == 'responsible_id_validate') {
         $input = [
            'id' => 6,
            'users_id_supervisor' => 4
         ];
         $user = new \User;
         $user->update($input);
      }
   }

   private function _getUserGroupOfChange($fieldname, $value, $changes_id) {
      global $DB;

      $matches = [];
      preg_match('/^_(users|groups|suppliers)_id_(requester|assign|observer)$/', $fieldname, $matches);
      if (count($matches) == 3) {
         $table = '';
         $fieldTypeName = '';
         if ($matches[1] == 'users') {
            $table = \Change_User::getTable();
            $fieldTypeName = 'users_id';
         } else if ($matches[1] == 'groups') {
            $table = \Change_Group::getTable();
            $fieldTypeName = 'groups_id';
         } else if ($matches[1] == 'suppliers') {
            $table = \Change_Supplier::getTable();
            $fieldTypeName = 'suppliers_id';
         }
         $type = 0;
         if ($matches[2] == 'requester') {
            $type = \CommonITILActor::REQUESTER;
         } else if ($matches[2] == 'assign') {
            $type = \CommonITILActor::ASSIGN;
         } else if ($matches[2] == 'observer') {
            $type = \CommonITILActor::OBSERVER;
         }
         $iterator = $DB->request([
            'FROM'      => $table,
            'WHERE' => [
               'changes_id' => $changes_id,
               'type'       => $type
            ]
         ]);
         if (count($iterator) == 1) {
            $data = $iterator->next();
            return $data[$fieldTypeName];
         } else if (count($iterator) == 0) {
            return '';
         } else if (count($iterator) == 2) {
            if ($fieldname == '_users_id_requester') {
               $data = $iterator->next();
               $data = $iterator->next();
               return $data[$fieldTypeName];
            } else {
               return '';
            }
         } else {
            return '';
         }
      }

      $matches = [];
      preg_match('/^(users|responsible)_id_validate$/', $fieldname, $matches);
      if (count($matches) == 2) {
         $iterator = $DB->request([
            'FROM'      => 'glpi_changevalidations',
            'WHERE' => [
               'changes_id' => $changes_id
            ]
         ]);
         if (count($iterator) == 1) {
            $data = $iterator->next();
            return $data['users_id_validate'];
         } else {
            return '';
         }
      }

      if ($fieldname == 'affectobject') {
         $iterator = $DB->request([
            'FROM'      => 'glpi_changes_items',
            'WHERE' => [
               'changes_id' => $changes_id
            ]
         ]);
         if (count($iterator) == 1) {
            $data = $iterator->next();
            return $data['items_id'];
         } else {
            return '';
         }
      } else if ($fieldname == 'groups_id_validate') {
         $iterator = $DB->request([
            'FROM'      => 'glpi_changevalidations',
            'WHERE' => [
               'changes_id' => $changes_id
            ]
         ]);
         $value = [];
         while ($data = $iterator->next()) {
            $value[] = $data['users_id_validate'];
         }
         return $value;
      }

      return $value;
   }
}
