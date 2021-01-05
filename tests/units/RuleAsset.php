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

/* Test for inc/ruleticket.class.php */

class RuleAsset extends DbTestCase {

   public function testGetCriteria() {
      $rule = new \RuleAsset();
      $criteria = $rule->getCriterias();
      $this->array($criteria)->size->isGreaterThanOrEqualTo(8);
   }

   public function testGetActions() {
      $rule = new \RuleAsset();
      $actions  = $rule->getActions();
      $this->array($actions)->size->isGreaterThanOrEqualTo(8);
   }


   public function testTriggerAdd() {
      $this->login();
      $this->setEntity('_test_root_entity', true);

      $root_ent_id = getItemByTypeName('Entity', '_test_root_entity', true);

      // prepare rule
      $this->_createRuleComment(\RuleAsset::ONUPDATE);

      // test create ticket (trigger on title)
      $computer = new \Computer;
      $computers_id = $computer->add($computer_input = [
         'name'        => "computer",
         '_auto'       => 1,
         'entities_id' => $root_ent_id,
         'is_dynamic'  => 1
      ]);
      $this->integer((int)$computers_id)->isGreaterThan(0);
      $this->boolean((bool)$computer->getFromDB($computers_id))->isTrue();
      $this->string((string)$computer->getField('comment'))->isEqualTo('comment1');

      $computers_id = $computer->add($computer_input = [
         'name'        => "computer2",
         'entities_id' => $root_ent_id,
         'is_dynamic'  => 0,
         '_auto'       => 0
      ]);
      $this->integer((int)$computers_id)->isGreaterThan(0);
      $this->boolean((bool)$computer->getFromDB($computers_id))->isTrue();
      $this->string((string)$computer->getField('comment'))->isEqualTo('');

      $monitor = new \Monitor();
      $monitors_id = $monitor->add($monitor_input = [
         'name'        => "monitor",
         'contact'     => 'tech',
         'entities_id' => $root_ent_id,
         'is_dynamic'  => 1,
         '_auto'       => 1
      ]);
      $this->boolean((bool)$monitor->getFromDB($monitors_id))->isTrue();

      //Rule only apply to computers
      $this->string((string)$monitor->getField('comment'))->isEqualTo('');
      $this->integer((int)$monitor->getField('users_id'))->isEqualTo(0);

      $computers_id = $computer->add($computer_input = [
         'name'        => "computer3",
         'contact'     => 'tech@AD',
         'entities_id' => $root_ent_id,
         'is_dynamic'  => 1,
         '_auto'       => 1
      ]);
      $this->integer((int)$computers_id)->isGreaterThan(0);
      $this->boolean((bool)$computer->getFromDB($computers_id))->isTrue();

      //User rule should apply (extract @domain from the name)
      $this->integer((int)$computer->getField('users_id'))->isEqualTo(4);
      $this->string((string)$computer->getField('comment'))->isEqualTo('comment1');

      $computers_id = $computer->add($computer_input = [
         'name'        => "computer3",
         'contact'     => 'tech@AD',
         'entities_id' => $root_ent_id,
         'is_dynamic'  => 1,
         '_auto'       => 1
      ]);
      $this->integer((int)$computers_id)->isGreaterThan(0);
      $this->boolean((bool)$computer->getFromDB($computers_id))->isTrue();

      //User rule should apply (extract the first user from the list)
      $this->integer((int)$computer->getField('users_id'))->isEqualTo(4);

      $computers_id = $computer->add($computer_input = [
         'name'        => "computer4",
         'contact'     => 'tech,glpi',
         'entities_id' => $root_ent_id,
         'is_dynamic'  => 1,
         '_auto'       => 1
      ]);
      $this->integer((int)$computers_id)->isGreaterThan(0);
      $this->boolean((bool)$computer->getFromDB($computers_id))->isTrue();

      //User rule should apply (extract the first user from the list)
      $this->integer((int)$computer->getField('users_id'))->isEqualTo(4);

      $computers_id = $computer->add($computer_input = [
         'name'        => "computer5",
         'contact'     => 'tech2',
         'entities_id' => $root_ent_id,
         'is_dynamic'  => 1,
         '_auto'       => 1
      ]);
      $this->integer((int)$computers_id)->isGreaterThan(0);
      $this->boolean((bool)$computer->getFromDB($computers_id))->isTrue();

      //User rule should apply (extract @domain from the name) but should not
      //find any user, so users_id is set to 0
      $this->integer((int)$computer->getField('users_id'))->isEqualTo(0);

   }

   public function testTriggerUpdate() {
      global $CFG_GLPI;

      $this->login();
      $this->setEntity('_test_root_entity', true);

      $root_ent_id = getItemByTypeName('Entity', '_test_root_entity', true);

      // prepare rule
      $this->_createRuleComment(\RuleAsset::ONUPDATE);
      $this->_createRuleLocation(\RuleAsset::ONUPDATE);

      foreach ($CFG_GLPI['asset_types'] as $itemtype) {
         $item     = new $itemtype;
         $item_input = [
            'name'        => "$itemtype 1",
            '_auto'       => 1,
            'entities_id' => $root_ent_id,
            'is_dynamic'  => 1,
            'comment'     => 'mycomment'
         ];
         if ($itemtype == 'SoftwareLicense') {
            $item_input['softwares_id'] = 1;
         }
         $items_id = $item->add($item_input);
         $this->integer((int)$items_id)->isGreaterThan(0);
         $this->boolean((bool)$item->getFromDB($items_id))->isTrue();
         if ($itemtype == 'Computer') {
            $this->string((string)$item->getField('comment'))->isEqualTo('comment1');
         } else {
            $this->string((string)$item->getField('comment'))->isEqualTo('mycomment');
         }
         $this->integer((int)$item->getField('locations_id'))->isGreaterThan(0);
      }
   }

   private function _createRuleComment($condition) {
      $ruleasset  = new \RuleAsset;
      $rulecrit   = new \RuleCriteria;
      $ruleaction = new \RuleAction;

      $ruleid = $ruleasset->add($ruleinput = [
         'name'         => "rule comment",
         'match'        => 'AND',
         'is_active'    => 1,
         'sub_type'     => 'RuleAsset',
         'condition'    => $condition,
         'is_recursive' => 1
      ]);
      $this->checkInput($ruleasset, $ruleid, $ruleinput);
      $crit_id = $rulecrit->add($crit_input = [
         'rules_id'  => $ruleid,
         'criteria'  => '_itemtype',
         'condition' => \Rule::PATTERN_IS,
         'pattern'   => "Computer"
      ]);
      $crit_id = $rulecrit->add($crit_input = [
         'rules_id'  => $ruleid,
         'criteria'  => '_auto',
         'condition' => \Rule::PATTERN_IS,
         'pattern'   => 1
      ]);
      $this->checkInput($rulecrit, $crit_id, $crit_input);
      $act_id = $ruleaction->add($act_input = [
         'rules_id'    => $ruleid,
         'action_type' => 'assign',
         'field'       => 'comment',
         'value'       => 'comment1'
      ]);
      $this->checkInput($ruleaction, $act_id, $act_input);
   }

   private function _createRuleLocation($condition) {
      $ruleasset  = new \RuleAsset;
      $rulecrit   = new \RuleCriteria;
      $ruleaction = new \RuleAction;

      $ruleid = $ruleasset->add($ruleinput = [
         'name'         => "rule location",
         'match'        => 'AND',
         'is_active'    => 1,
         'sub_type'     => 'RuleAsset',
         'condition'    => $condition,
         'is_recursive' => 1
      ]);
      $this->checkInput($ruleasset, $ruleid, $ruleinput);
      $crit_id = $rulecrit->add($crit_input = [
         'rules_id'  => $ruleid,
         'criteria'  => '_itemtype',
         'condition' => \Rule::PATTERN_IS,
         'pattern'   => "*"
      ]);
      $this->checkInput($rulecrit, $crit_id, $crit_input);
      $act_id = $ruleaction->add($act_input = [
         'rules_id'    => $ruleid,
         'action_type' => 'assign',
         'field'       => 'locations_id',
         'value'       => 1
      ]);
      $this->checkInput($ruleaction, $act_id, $act_input);
   }

}
