<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

use DbTestCase;

/* Test for inc/RuleRight.class.php */

class RuleRight extends DbTestCase {

   public function testGetCriteria() {
      $rule = new \RuleRight();
      $criteria = $rule->getCriterias();
      $this->array($criteria)->size->isGreaterThan(5);
   }

   public function testGetActions() {
      $rule = new \RuleRight();
      $actions  = $rule->getActions();
      $this->array($actions)->size->isGreaterThan(11);
   }

   public function testDefaultRuleExists() {
      $this->integer(
         (int)countElementsInTable(
            'glpi_rules',
            [
               'name'      => 'Root',
               'is_active' => 1,
               'sub_type'  => 'RuleRight'
            ]
         )
      )->isIdenticalTo(1);
   }

   public function testLocalAccount() {
      //prepare rules
      $rules = new \RuleRight();
      $rules_id = $rules->add([
         'sub_type'     => 'RuleRight',
         'name'         => 'test local account ruleright',
         'match'        => 'AND',
         'is_active'    => 1,
         'entities_id'  => 0,
         'is_recursive' => 1,
      ]);

      $criteria = new \RuleCriteria();
      $criteria->add([
         'rules_id'  => $rules_id,
         'criteria'  => 'LOGIN',
         'condition' => \Rule::PATTERN_IS,
         'pattern'   => TU_USER,
      ]);
      $criteria->add([
         'rules_id'  => $rules_id,
         'criteria'  => 'MAIL_EMAIL',
         'condition' => \Rule::PATTERN_IS,
         'pattern'   => TU_USER.'@glpi.com',
      ]);

      $actions = new \RuleAction();
      $actions->add([
         'rules_id'    => $rules_id,
         'action_type' => 'assign',
         'field'       => 'profiles_id',
         'value'       => 5, // 'normal' profile
      ]);
      $actions->add([
         'rules_id'    => $rules_id,
         'action_type' => 'assign',
         'field'       => 'entities_id',
         'value'       => 1, // '_test_child_1' entity
      ]);

      // login the user to force a real synchronisation and get it's glpi id
      $this->login(TU_USER, TU_PASS, false);
      $users_id = \User::getIdByName(TU_USER);
      $this->integer($users_id);

      // check the user got the entity/profiles assigned
      $pu = \Profile_User::getForUser($users_id, true);

      // check the assigned right exists in the collection
      $found = false;
      foreach ($pu as $right) {
         if (isset($right['entities_id']) && $right['entities_id'] == 1
             && isset($right['profiles_id']) && $right['profiles_id'] == 5
             && isset($right['is_dynamic']) && $right['is_dynamic'] == 1) {
            $found = true;
            break;
         }
      }
      $this->boolean($found)->isTrue();

      // cleanup
      $rules->delete([
         'id' => $rules_id,
      ], true);
      $exist = $rules->getFromDB($rules_id);
      $this->boolean($exist)->isFalse();

      // clean right singleton
      \SingletonRuleList::getInstance("RuleRight", 0)->load = 0;
      \SingletonRuleList::getInstance("RuleRight", 0)->list = [];

      // login again
      $this->login(TU_USER, TU_PASS, false);

      // check the user got the entity/profiles assigned
      $pu = \Profile_User::getForUser($users_id, true);

      // check the assigned right exists in the collection
      $found = false;
      foreach ($pu as $right) {
         if (isset($right['entities_id']) && $right['entities_id'] == 1
             && isset($right['profiles_id']) && $right['profiles_id'] == 5
             && isset($right['is_dynamic']) && $right['is_dynamic'] == 1) {
            $found = true;
            break;
         }
      }
      $this->boolean($found)->isFalse();
   }
}
