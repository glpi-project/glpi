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

/* Test for inc/rulecriteria.class.php */

class RuleTest extends DbTestCase {


   /**
    * @covers Rule::getTable
    */
   public function testGetTable() {
      $table = Rule::getTable('RuleDictionnarySoftware');
      $this->assertEquals($table, 'glpi_rules');

      $table = Rule::getTable('RuleTicket');
      $this->assertEquals($table, 'glpi_rules');

   }

   /**
    * @covers Rule::getTypeName
    */
   public function testGetTypeName() {
      $this->assertEquals(Rule::getTypeName(1), 'Rule');
      $this->assertEquals(Rule::getTypeName(Session::getPluralNumber()), 'Rules');
   }

   /**
   * @cover Rule::getRuleObjectByID
   */
   public function testGetRuleObjectByID() {
      $rule = new Rule();
      $rules_id = $rule->add(['name'        => 'Ignore import',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleDictionnarySoftware',
                              'match'       => Rule::AND_MATCHING,
                              'condition'   => 0,
                              'description' => ''
                             ]);

      $obj = Rule::getRuleObjectByID($rules_id);
      $this->assertEquals(get_class($obj), 'RuleDictionnarySoftware');

      $this->assertNull(Rule::getRuleObjectByID(100));

   }

   /**
   * @cover Rule::getConditionsArray
   */
   public function testGetConditionsArray() {
      $this->assertEmpty(Rule::getConditionsArray());

      $conditions = RuleTicket::getConditionsArray();
      $this->assertEquals($conditions, [1 => "Add",
                                        2 => "Update",
                                        3 => "Add / Update"]);

   }

   /**
   * @cover Rule::useConditions
   */
   public function testUseConditions() {
      $rule = new Rule();
      $this->assertFalse($rule->useConditions());

      $ruleticket = new RuleTicket();
      $this->assertTrue($ruleticket->useConditions());
   }

   /**
   * @cover Rule::getConditionName
   */
   public function testGetConditionName() {
      $this->assertEquals(Rule::getConditionName(-1), NOT_AVAILABLE);
      $this->assertEquals(Rule::getConditionName(110), NOT_AVAILABLE);
      $this->assertEquals(RuleTicket::getConditionName(1), 'Add');
      $this->assertEquals(RuleTicket::getConditionName(2), 'Update');
      $this->assertEquals(RuleTicket::getConditionName(3), 'Add / Update');
   }

   /**
   * @cover Rule::getRuleActionClass
   */
   public function testGetRuleActionClass() {
      $rule = new Rule();
      $this->assertEquals($rule->getRuleActionClass(), 'RuleAction');

      $rule = new RuleTicket();
      $this->assertEquals($rule->getRuleActionClass(), 'RuleAction');
   }

   /**
   * @cover Rule::getRuleCriteriaClass
   */
   public function testGetRuleCriteriaClass() {
      $rule = new Rule();
      $this->assertEquals($rule->getRuleCriteriaClass(), 'RuleCriteria');

      $rule = new RuleTicket();
      $this->assertEquals($rule->getRuleCriteriaClass(), 'RuleCriteria');
   }

   /**
   * @cover Rule::getRuleIdField
   */
   public function testGetRuleIdField() {
      $rule = new Rule();
      $this->assertEquals($rule->getRuleIdField(), 'rules_id');

      $rule = new RuleDictionnaryPrinter();
      $this->assertEquals($rule->getRuleIdField(), 'rules_id');

   }

   /**
   * @cover Rule::isEntityAssign
   */
   public function testIsEntityAssign() {
      $rule = new Rule();
      $this->assertFalse($rule->isEntityAssign());

      $rule = new RuleTicket();
      $this->assertTrue($rule->isEntityAssign());
   }

   /**
   * @cover Rule::post_getEmpty
   */
   public function testPost_getEmpty() {
      $rule = new Rule();
      $rule->getEmpty();
      $this->assertEquals(0, $this->fields['is_active']);
   }

   /**
   * @cover Rule::getTitle
   */
   public function testGetTitle() {
      $rule = new Rule();
      $this->assertEquals($rule->getTitle(), __('Rules management'));

      $rule = new RuleTicket();
      $this->assertEquals($rule->getTitle(), __('Business rules for tickets'));
   }

   /**
   * @cover Rule::getCollectionClassName
   */
   public function testGetCollectionClassName() {
      $rule = new Rule();
      $this->assertEquals($rule->getCollectionClassName(), 'RuleCollection');

      $rule = new RuleTicket();
      $this->assertEquals($rule->getCollectionClassName(), 'RuleTicketCollection');

   }

   /**
   * @cover Rule::getSpecificMassiveActions
   */
   public function testGetSpecificMassiveActions() {
      $rule    = new Rule();
      $actions = $rule->getSpecificMassiveActions();
      $this->assertEquals($actions, ['Rule:duplicate' => 'Duplicate',
                                     'Rule:export' => 'Export']);

      $_SESSION['glpiactiveprofile']['rule_dictionnary_software'] = ALLSTANDARDRIGHT;
      $rule    = new RuleDictionnarySoftware();
      $actions = $rule->getSpecificMassiveActions();
      $this->assertEquals($actions, ['Rule:move_rule' => 'Move',
                                     'Rule:duplicate' => 'Duplicate',
                                     'Rule:export'    => 'Export']);

      $_SESSION['glpiactiveprofile']['rule_dictionnary_software'] = READ;
      $rule    = new RuleDictionnarySoftware();
      $actions = $rule->getSpecificMassiveActions();
      $this->assertEquals($actions, ['Rule:duplicate' => 'Duplicate',
                                     'Rule:export'    => 'Export']);

   }

   /**
   * @cover Rule::getSearchOptionsNew
   */
   public function testGetSearchOptionsNew() {
      $rule = new Rule();
      $this->assertEquals(10, count($rule->getSearchOptionsNew()));
   }

   /**
   * @cover Rule::getRuleWithCriteriasAndActions
   */
   public function testGetRuleWithCriteriasAndActions() {
      $rule       = new Rule();
      $criteria   = new RuleCriteria();
      $action     = new RuleAction();

      $rules_id = $rule->add(['name'        => 'Ignore import',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleDictionnarySoftware',
                              'match'       => Rule::OR_MATCHING,
                              'condition'   => 0,
                              'description' => ''
                             ]);

      $criteria->add(['rules_id'  => $rules_id,
                      'criteria'  => 'name',
                      'condition' => Rule::PATTERN_IS,
                      'pattern'   => 'Mozilla Firefox 52'
                     ]);
      $criteria->add(['rules_id'  => $rules_id,
                      'criteria'  => 'name',
                      'condition' => Rule::PATTERN_IS,
                      'pattern'   => 'Mozilla Firefox 53'
                     ]);

      $action->add(['rules_id'    => $rules_id,
                    'action_type' => 'assign',
                    'field'       => '_ignore_import',
                    'value'       => '1'
                   ]);

      $this->assertTrue($rule->getRuleWithCriteriasAndActions($rules_id));
      $this->assertEmpty($rule->criterias);
      $this->assertEmpty($rule->actions);

      $this->assertTrue($rule->getRuleWithCriteriasAndActions($rules_id, 1, 1));
      $this->assertEquals(2, count($rule->criterias));
      $this->assertEquals(1, count($rule->actions));

      $this->assertFalse($rule->getRuleWithCriteriasAndActions(100));
   }

   /**
   * @cover Rule::maxActionsCount
   */
   public function testMaxActionsCount() {
      $rule = new Rule();
      $this->assertEquals(0, $rule->maxActionsCount());

      $rule = new RuleTicket();
      $this->assertEquals(23, $rule->maxActionsCount());

      $rule = new RuleDictionnarySoftware();
      $this->assertEquals(4, $rule->maxActionsCount());

   }

   /**
   * @cover Rule::maybeRecursive
   */
   public function testMaybeRecursive() {
      $rule = new Rule();
      $this->assertFalse($rule->maybeRecursive());

      $rule = new RuleTicket();
      $this->assertTrue($rule->maybeRecursive());
   }

   /**
   * @cover Rule::getCriteria
   */
   public function testGetCriteria() {
      $ruleTicket = new RuleTicket();
      $criteria   = $ruleTicket->getCriteria('locations_id');
      $this->assertEquals($criteria['table'], 'glpi_locations');
      $this->assertEmpty($ruleTicket->getCriteria('location'));
   }

   /**
   * @cover Rule::getAction
   */
   public function testGetAction() {
      $ruleTicket = new RuleTicket();
      $action     = $ruleTicket->getAction('locations_id');
      $this->assertEquals($action['table'], 'glpi_locations');
      $this->assertEmpty($ruleTicket->getAction('location'));
   }

   /**
   * @cover Rule::getCriteriaName
   */
   public function testGetCriteriaName() {
      $ruleTicket = new RuleTicket();
      $this->assertEquals('Ticket location', $ruleTicket->getCriteriaName('locations_id'));
      $this->assertEquals(__('Unavailable')."&nbsp;", $ruleTicket->getCriteriaName('location'));
   }

   /**
   * @cover Rule::getActionName
   */
   public function testGetActionName() {
      $ruleTicket = new RuleTicket();

      $actions = [__('Location')               => 'locations_id',
                  "&nbsp;"                     => 'location',
                  __('Type')                   => 'type',
                  __('Category')               => 'itilcategories_id',
                  __('Requester')              => '_users_id_requester',
                  __('Requester group')        => '_groups_id_requester',
                  __('Technician')             => '_users_id_assign',
                  __('Technician group')       => '_groups_id_assign',
                  __('Assigned to a supplier') => '_suppliers_id_assign',
                  __('Watcher')                => '_users_id_observer',
                  __('Watcher group')          => '_groups_id_observer',
                  __('Urgency')                => 'urgency',
                  __('Impact')                 => 'impact',
                  __('Priority')               => 'priority',
                  __('Status')                 => 'status',
                  _n('Associated element',
                     'Associated elements', 2) => 'affectobject',
                  sprintf(__('%1$s %2$s'),
                           __('SLT'),
                           __('Time to resolve')) => 'slts_ttr_id',
                  sprintf(__('%1$s %2$s'),
                           __('SLT'),
                           __('Time to own'))     => 'slts_tto_id',
                  sprintf(__('%1$s - %2$s'),
                           __('Send an approval request'),
                           __('User'))            => 'users_id_validate',
                  sprintf(__('%1$s - %2$s'),
                           __('Send an approval request'),
                           __('Group'))           => 'groups_id_validate',
                  sprintf(__('%1$s - %2$s'),
                           __('Send an approval request'),
                           __('Minimum validation required')) => 'validation_percent',
                  __('Approval request to requester group manager') => 'users_id_validate_requester_supervisor',
                  __('Approval request to technician group manager') => 'users_id_validate_assign_supervisor',
                  __('Request source') => 'requesttypes_id'
                 ];
      foreach ($actions as $label => $field) {
         $this->assertEquals($ruleTicket->getActionName($field), $label);
      }
   }

   public function testProcess() {

   }

   /**
   * @cover Rule::prepareInputDataForProcess
   */
   public function testPrepareInputDataForProcess() {
      $rule = new Rule();
      $input = ['name' => 'name', 'test' => 'test'];
      $result = $rule->prepareInputDataForProcess($input, ['test2' => 'test2']);
      $this->assertEquals($result, $input);
   }

   /**
   * @cover Rule::cleanDBonPurge
   */
   public function testCleanDBonPurge() {
      $rule       = new Rule();
      $criteria   = new RuleCriteria();
      $action     = new RuleAction();

      $rules_id = $rule->add(['name'        => 'Ignore import',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleDictionnarySoftware',
                              'match'       => Rule::OR_MATCHING,
                              'condition'   => 0,
                              'description' => ''
                             ]);

      $criterion_1 = $criteria->add(['rules_id'  => $rules_id,
                      'criteria'  => 'name',
                      'condition' => Rule::PATTERN_IS,
                      'pattern'   => 'Mozilla Firefox 52'
                     ]);
      $criterion_2 = $criteria->add(['rules_id'  => $rules_id,
                      'criteria'  => 'name',
                      'condition' => Rule::PATTERN_IS,
                      'pattern'   => 'Mozilla Firefox 53'
                     ]);

      $action_1 = $action->add(['rules_id'    => $rules_id,
                    'action_type' => 'assign',
                    'field'       => '_ignore_import',
                    'value'       => '1'
                   ]);

      $rule->getFromDB($rules_id);
      $rule->cleanDBonPurge();
      $this->assertFalse($criteria->getFromDB($criterion_1));
      $this->assertFalse($criteria->getFromDB($criterion_2));
      $this->assertFalse($action->getFromDB($action_1));
   }

   /**
   * @cover Rule::prepareInputForAdd
   */
   public function testPrepareInputForAdd() {
      $rule     = new RuleRight();
      //Add a new rule
      $rules_id = $rule->add(['name' => 'MyRule', 'is_active' => 1]);
      $this->assertTrue($rules_id>0);
      $rule->getFromDB($rules_id);
      //Check that an uuid has been generated
      $this->assertNotEmpty($rule->fields['uuid']);
      //Check that a ranking has been added
      $this->assertNotEmpty($rule->fields['ranking']);

      //Add a rule and provide an uuid
      $rules_id = $rule->add(['name' => 'MyRule', 'uuid' => '12345']);
      $rule->getFromDB($rules_id);
      //Check that the uuid has been added as it is, and has not been overriden
      $this->assertEquals($rule->fields['uuid'], '12345');

   }

   /**
   * @cover Rule::getMinimalCriteriaText
   */
   public function testGetMinimalCriteriaText() {
      $rule     = new RuleTicket();
      $location = getItemByTypeName('Location', "_location01");

      //Testing condition CONTAIN
      $input    = ['criteria'  => 'location',
                   'condition'   => Rule::PATTERN_CONTAIN,
                   'pattern' => '_loc'
                  ];
      //The criterion doesn't exists
      $result   = $rule->getMinimalCriteriaText($input);
      $expected = "<td >Unavailable&nbsp;</td><td >contains</td><td >_loc</td>";
      $this->assertEquals($result, $expected);

      $input['criteria'] = 'users_locations';
      $result   = $rule->getMinimalCriteriaText($input);
      $expected = "<td >Requester location</td><td >contains</td><td >_loc</td>";
      $this->assertEquals($result, $expected);

      //Testing condition IS
      $input['condition'] = Rule::PATTERN_IS;
      $input['pattern']   = $location->getID();
      $result   = $rule->getMinimalCriteriaText($input);
      $expected = "<td >Requester location</td><td >is</td><td >_location01 (Root entity)</td>";
      $this->assertEquals($result, $expected);

      //Testing condition IS NOT
      $input['condition'] = Rule::PATTERN_IS_NOT;
      $result   = $rule->getMinimalCriteriaText($input);
      $expected = "<td >Requester location</td><td >is not</td><td >_location01 (Root entity)</td>";
      $this->assertEquals($result, $expected);

      //Testing condition REGEX MATCH
      $input['condition'] = Rule::REGEX_MATCH;
      $input['pattern']   = '/(loc)/';
      $result   = $rule->getMinimalCriteriaText($input);
      $expected = "<td >Requester location</td><td >regular expression matches</td><td >/(loc)/</td>";
      $this->assertEquals($result, $expected);

      //Testing condition REGEX DOESN NOT MATCH
      $input['condition'] = Rule::REGEX_NOT_MATCH;
      $result   = $rule->getMinimalCriteriaText($input);
      $expected = "<td >Requester location</td><td >regular expression does not match</td><td >/(loc)/</td>";
      $this->assertEquals($result, $expected);

      //Testing condition EXISTS
      $input['condition'] = Rule::PATTERN_EXISTS;
      $result   = $rule->getMinimalCriteriaText($input);
      $expected = "<td >Requester location</td><td >exists</td><td >Yes</td>";
      $this->assertEquals($result, $expected);

      //Testing condition DOES NOT EXIST
      $input['condition'] = Rule::PATTERN_DOES_NOT_EXISTS;
      $result   = $rule->getMinimalCriteriaText($input);
      $expected = "<td >Requester location</td><td >does not exist</td><td >Yes</td>";
      $this->assertEquals($result, $expected);

      //Testing condition UNDER
      $input['condition'] = Rule::PATTERN_UNDER;
      $input['pattern']   = $location->getID();
      $result   = $rule->getMinimalCriteriaText($input);
      $expected = "<td >Requester location</td><td >under</td><td >_location01 (Root entity)</td>";
      $this->assertEquals($result, $expected);

      //Testing condition UNDER
      $input['condition'] = Rule::PATTERN_NOT_UNDER;
      $result   = $rule->getMinimalCriteriaText($input);
      $expected = "<td >Requester location</td><td >not under</td><td >_location01 (Root entity)</td>";
      $this->assertEquals($result, $expected);

      //Testing condition UNDER
      $input['condition'] = Rule::PATTERN_BEGIN;
      $input['pattern']   = '_loc';
      $result   = $rule->getMinimalCriteriaText($input);
      $expected = "<td >Requester location</td><td >starting with</td><td >_loc</td>";
      $this->assertEquals($result, $expected);

      //Testing condition UNDER
      $input['condition'] = Rule::PATTERN_END;
      $input['pattern']   = '_loc';
      $result   = $rule->getMinimalCriteriaText($input);
      $expected = "<td >Requester location</td><td >finished by</td><td >_loc</td>";
      $this->assertEquals($result, $expected);

      //Testing condition UNDER
      $input['condition'] = Rule::PATTERN_END;
      $input['pattern']   = '_loc';
      $result   = $rule->getMinimalCriteriaText($input, 'aaaa');
      $expected = "<td aaaa>Requester location</td><td aaaa>finished by</td><td aaaa>_loc</td>";
      $this->assertEquals($result, $expected);

   }

   /**
   * @cover Rule::getMinimalActionText
   */
   public function testGetMinimalActionText() {
      $rule = new RuleSoftwareCategory();
      $input = ['field' => 'softwarecategories_id',
                'action_type' => 'assign',
                'value' => 1
               ];
      $result = $rule->getMinimalActionText($input);
      $expected = "<td >Category</td><td >Assign</td><td >FUSION</td>";
      $this->assertEquals($result, $expected);

      $input = ['field' => '_import_category',
                'action_type' => 'assign',
                'value' => 1
               ];
      $result = $rule->getMinimalActionText($input);
      $expected = "<td >Import category from inventory tool</td><td >Assign</td><td >Yes</td>";
      $this->assertEquals($result, $expected);

      $input['field'] = '_ignore_import';
      $result = $rule->getMinimalActionText($input);
      $expected = "<td >To be unaware of import</td><td >Assign</td><td >Yes</td>";
      $this->assertEquals($result, $expected);

   }

   /**
   * @cover Rule::getCriteriaDisplayPattern
   */
   public function testGetCriteriaDisplayPattern() {
      $rule   = new Rule();
      $this->assertEquals($rule->getCriteriaDisplayPattern(9, Rule::PATTERN_EXISTS, 1), __('Yes'));
      $this->assertEquals($rule->getCriteriaDisplayPattern(9, Rule::PATTERN_DOES_NOT_EXISTS, 1), __('Yes'));
      $this->assertEquals($rule->getCriteriaDisplayPattern(9, Rule::PATTERN_FIND, 1), __('Yes'));


      $result = $rule->getCriteriaDisplayPattern(9, Rule::PATTERN_IS, 1);
      var_dump($result);
   }
}
