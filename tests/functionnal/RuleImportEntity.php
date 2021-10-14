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

/* Test for inc/ruleimportlocation.class.php */

class RuleImportEntity extends DbTestCase {

   public function testTwoRegexpEntitiesTest() {
      global $DB;

      $this->login();
      $entity = new \Entity();

      $entities_id_a = $entity->add([
         'name'         => 'Entity A',
         'entities_id'  => 0,
         'completename' => 'Root entitiy > Entity A',
         'level'        => 2,
         'tag'          => 'entA'
      ]);
      $this->integer($entities_id_a)->isGreaterThan(0);

      $entities_id_b = $entity->add([
         'name'         => 'Entity B',
         'entities_id'  => 0,
         'completename' => 'Root entitiy > Entity B',
         'level'        => 2,
         'tag'          => 'entB'
      ]);
      $this->integer($entities_id_b)->isGreaterThan(0);

      $entities_id_c = $entity->add([
         'name'         => 'Entity C',
         'entities_id'  => 0,
         'completename' => 'Root entitiy > Entity C',
         'level'        => 2,
         'tag'          => 'entC'
      ]);
      $this->integer($entities_id_c)->isGreaterThan(0);

      // Add a rule for get entity tag (1)
      $rule = new \Rule();
      $input = [
         'is_active' => 1,
         'name'      => 'entity rule 1',
         'match'     => 'AND',
         'sub_type'  => 'RuleImportEntity',
         'ranking'   => 1
      ];
      $rule1_id = $rule->add($input);
      $this->integer($rule1_id)->isGreaterThan(0);

      // Add criteria
      $rulecriteria = new \RuleCriteria();
      $input = [
         'rules_id'  => $rule1_id,
         'criteria'  => "name",
         'pattern'   => "/^([A-Za-z0-9]*) - ([A-Za-z0-9]*) - (.*)$/",
         'condition' => \RuleImportEntity::REGEX_MATCH
      ];
      $this->integer($rulecriteria->add($input))->isGreaterThan(0);

      // Add action
      $ruleaction = new \RuleAction();
      $input = [
         'rules_id'    => $rule1_id,
         'action_type' => 'regex_result',
         'field'       => '_affect_entity_by_tag',
         'value'       => '#2'
      ];
      $this->integer($ruleaction->add($input))->isGreaterThan(0);

      // Add a rule for get entity tag (2)
      $rule = new \Rule();
      $input = [
      'is_active' => 1,
      'name'      => 'entity rule 2',
      'match'     => 'AND',
      'sub_type'  => 'RuleImportEntity',
      'ranking'   => 2
      ];
      $rule2_id = $rule->add($input);
      $this->integer($rule2_id)->isGreaterThan(0);

      // Add criteria
      $rulecriteria = new \RuleCriteria();
      $input = [
         'rules_id'  => $rule2_id,
         'criteria'  => "name",
         'pattern'   => "/^([A-Za-z0-9]*) - (.*)$/",
         'condition' => \RuleImportEntity::REGEX_MATCH
      ];
      $this->integer($rulecriteria->add($input))->isGreaterThan(0);

      // Add action
      $ruleaction = new \RuleAction();
      $input = [
         'rules_id'    => $rule2_id,
         'action_type' => 'regex_result',
         'field'       => '_affect_entity_by_tag',
         'value'       => '#1'
      ];
      $this->integer($ruleaction->add($input))->isGreaterThan(0);

      $input = [
         'name' => 'computer01 - entC'
      ];

      $ruleEntity = new \RuleImportEntityCollection();
      $ruleEntity->getCollectionPart();
      $ent = $ruleEntity->processAllRules($input, []);

      $expected = [
         'entities_id' => $entities_id_c,
         '_ruleid'     => $rule2_id
      ];
      $this->array($ent)->isEqualTo($expected);

      $input = [
         'name' => 'computer01 - blabla - entB'
      ];

      $ruleEntity->getCollectionPart();
      $ent = $ruleEntity->processAllRules($input, []);

      $expected = [
         'entities_id' => $entities_id_b,
         '_ruleid'     => $rule1_id
      ];
      $this->array($ent)->isEqualTo($expected);
   }

   public function testRefusedByEntity() {
      global $DB;

      $this->login();

      $rule = new \Rule();
      $input = [
         'is_active' => 1,
         'name'      => 'entity refuse rule',
         'match'     => 'AND',
         'sub_type'  => \RuleImportEntity::class,
         'ranking'   => 1
      ];
      $rules_id = $rule->add($input);
      $this->integer($rules_id)->isGreaterThan(0);

      // Add criteria
      $rulecriteria = new \RuleCriteria();
      $this->integer(
         $rulecriteria->add([
            'rules_id'  => $rules_id,
            'criteria'  => "name",
            'pattern'   => "/^([A-Za-z0-9]*) - (.*)$/",
            'condition' => \RuleImportEntity::REGEX_MATCH
         ])
      )->isGreaterThan(0);

      // Add action
      $ruleaction = new \RuleAction();
      $this->integer(
         $ruleaction->add([
            'rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => '_ignore_import',
            'value'       => 1
         ])
      )->isGreaterThan(0);

      $input = [
         'name' => 'computer01 - entD'
      ];

      $ruleEntity = new \RuleImportEntityCollection();
      $ruleEntity->getCollectionPart();
      $ent = $ruleEntity->processAllRules($input, []);

      $expected = [
         '_ignore_import' => 1,
         '_ruleid'     => $rules_id
      ];
      $this->array($ent)->isEqualTo($expected);
   }
}
