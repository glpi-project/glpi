<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace tests\units;

use CommonITILActor;
use CommonITILValidation;
use DbTestCase;
use Entity;
use Generator;
use Group;
use Group_Ticket;
use Group_User;
use ITILCategory;
use ITILFollowup;
use ITILFollowupTemplate;
use Location;
use Rule;
use RuleAction;
use RuleBuilder;
use RuleCriteria;
use SingletonRuleList;
use TaskTemplate;
use Ticket;
use User;

abstract class RuleCommonITILObject extends DbTestCase
{
    /**
     * Return the name of the Rule class this test class tests
     * @return string
     */
    protected function getTestedClass(): string
    {
        $test_class = static::class;
        // Rule class has the same name as the test class but in the global namespace
        return substr(strrchr($test_class, '\\'), 1);
    }

    /**
     * Get an instance of the tested Rule class
     * @return \RuleCommonITILObject
     */
    protected function getRuleInstance(): \RuleCommonITILObject
    {
        $tested_class = $this->getTestedClass();
        return new $tested_class();
    }

    /**
     * Get the ITIL Object class name that the tested Rule class is related to
     * @return string
     */
    protected function getITILObjectClass(): string
    {
        /** @var \RuleCommonITILObject $tested_class */
        $tested_class = $this->getTestedClass();
        return $tested_class::getItemtype();
    }

    /**
     * Get an instance of the ITIL Object class that the tested Rule class is related to
     * @return \CommonITILObject
     */
    protected function getITILObjectInstance(): \CommonITILObject
    {
        $itil_class = $this->getITILObjectClass();
        return new $itil_class();
    }

    /**
     * Get the name of a link class for the related ITIL Object class and a given other class.
     *
     * This only works for classes in the global namespace.
     * @param string $other_type The simple name of the other class (No namespaces)
     * @return string|null The link class name or null if the link class cannot be determined
     */
    protected function getITILLinkClass(string $other_type): ?string
    {
        $itil_class_name = $this->getITILObjectInstance()->getType();
        // Guess order the class names are in the link class name
        $link_class_name = $itil_class_name . '_' . $other_type;
        if (!class_exists($link_class_name)) {
            $link_class_name = $other_type . '_' . $itil_class_name;
            if (!class_exists($link_class_name)) {
                // Child relation
                $link_class_name = $itil_class_name . $other_type;
                if (!class_exists($link_class_name)) {
                    return null;
                }
            }
        }
        return $link_class_name;
    }

    /**
     * Get an instance of a link class for the related ITIL Object class and a given other class.
     *
     * This only works for classes in the global namespace.
     * @param string $other_type The simple name of the other class (No namespaces)
     * @return \CommonDBTM|null The link class instance or null if the link class cannot be determined
     */
    protected function getITILLinkInstance(string $other_type): ?\CommonDBTM
    {
        $link_class_name = $this->getITILLinkClass($other_type);
        return new $link_class_name();
    }

    abstract public function testGetCriteria();

    abstract public function testGetActions();

    public function testTriggerAdd()
    {
        $this->login();

        // prepare rule
        $this->createTestTriggerRule(\RuleCommonITILObject::ONADD);

        // test create ITIL Object (trigger on title)
        $itil = $this->getITILObjectInstance();
        $itil_id = $itil->add($itil_input = [
            'name'    => "test ITIL Object, will trigger on rule (title)",
            'content' => "test"
        ]);
        $this->checkInput($itil, $itil_id, $itil_input);
        $this->integer((int)$itil->getField('urgency'))->isEqualTo(5);

        // test create ITIL Object (trigger on user assign)
        $itil = $this->getITILObjectInstance();
        $itil_id = $itil->add($itil_input = [
            'name'             => "test ITIL Object, will trigger on rule (user)",
            'content'          => "test",
            '_users_id_assign' => getItemByTypeName('User', "tech", true)
        ]);
        // _users_id_assign is stored in glpi_*_users table, so remove it
        unset($itil_input['_users_id_assign']);
        $this->checkInput($itil, $itil_id, $itil_input);
        $this->integer((int)$itil->getField('urgency'))->isEqualTo(5);
    }

    public function testTriggerUpdate()
    {
        $this->login();
        $this->setEntity('Root entity', true);

        $users_id = (int) getItemByTypeName('User', 'tech', true);

        // prepare rule
        $this->createTestTriggerRule(\RuleCommonITILObject::ONUPDATE);

        // test create ITIL Object (for check triggering on title after update)
        $itil = $this->getITILObjectInstance();
        $itil_id = $itil->add($itil_input = [
            'name'    => "test ITIL Object, will not trigger on rule",
            'content' => "test"
        ]);
        $this->checkInput($itil, $itil_id, $itil_input);
        $this->integer((int)$itil->getField('urgency'))->isEqualTo(3);

        // update ITIL Object title and trigger rule on title updating
        $itil->update([
            'id'   => $itil_id,
            'name' => 'test ITIL Object, will trigger on rule (title)'
        ]);
        $itil->getFromDB($itil_id);
        $this->integer((int)$itil->getField('urgency'))->isEqualTo(5);

        // test create ITIL Object (for check triggering on actor after update)
        $itil = $this->getITILObjectInstance();
        $itil_id = $itil->add($itil_input = [
            'name'    => "test ITIL Object, will not trigger on rule (actor)",
            'content' => "test"
        ]);
        $this->checkInput($itil, $itil_id, $itil_input);
        $this->integer((int)$itil->getField('urgency'))->isEqualTo(3);

        // update ITIL Object title and trigger rule on actor addition
        $itil->update([
            'id'           => $itil_id,
            'content'      => "updated",
            '_lgd'         => true,
            '_itil_assign' => [
                '_type'    => 'user',
                'users_id' => $users_id
            ]
        ]);
        $itil->getFromDB($itil_id);
        $itil_user = $this->getITILLinkInstance('User');
        $actors = $itil_user->getActors($itil_id);
        $this->integer((int)$actors[2][0]['users_id'])->isEqualTo($users_id);
        $this->integer((int)$itil->getField('urgency'))->isEqualTo(5);
    }

    private function createTestTriggerRule($condition)
    {
        $rule_itil = $this->getRuleInstance();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $rule_itil->add($ruletinput = [
            'name'         => "test rule add",
            'match'        => 'OR',
            'is_active'    => 1,
            'sub_type'     => $this->getTestedClass(),
            'condition'    => $condition,
            'is_recursive' => 1
        ]);
        $this->checkInput($rule_itil, $ruletid, $ruletinput);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'name',
            'condition' => \Rule::PATTERN_CONTAIN,
            'pattern'   => "trigger on rule (title)"
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_users_id_assign',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => getItemByTypeName('User', "tech", true)
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

    /**
     * Test status criterion in rules.
     */
    public function testStatusCriterion()
    {
        $this->login();

        // Create rule
        $rule_itil = $this->getRuleInstance();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $rule_itil->add($ruletinput = [
            'name'         => 'test status criterion',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => $this->getTestedClass(),
            'condition'    => \RuleCommonITILObject::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($rule_itil, $ruletid, $ruletinput);

        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'status',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => \CommonITILObject::INCOMING,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_users_id_assign',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => getItemByTypeName('User', 'tech', true)
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'status',
            'value'       => \CommonITILObject::WAITING,
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);

        // Check ITIL Object that trigger rule on creation
        $itil = $this->getITILObjectInstance();
        $itil_id = $itil->add($itil_input = [
            'name'             => 'change status to waiting if new and assigned to tech',
            'content'          => 'test',
            '_users_id_assign' => getItemByTypeName('User', 'tech', true)
        ]);
        unset($itil_input['_users_id_assign']); // _users_id_assign is stored in glpi_*_users table, so remove it
        $this->checkInput($itil, $itil_id, $itil_input);
        $this->integer((int)$itil->getField('status'))->isEqualTo(\CommonITILObject::WAITING);
    }

    /**
     * Test that new status setting by rules is not overrided when an actor is assigned at the same time.
     */
    public function testStatusAssignNewFromRule()
    {
        $this->login();

        // Create rule
        $rule_itil = $this->getRuleInstance();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $rule_itil->add($ruletinput = [
            'name'         => 'test assign new actor and keep new status',
            'match'        => 'OR',
            'is_active'    => 1,
            'sub_type'     => $this->getTestedClass(),
            'condition'    => \RuleCommonITILObject::ONADD | \RuleCommonITILObject::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->checkInput($rule_itil, $ruletid, $ruletinput);

        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'name',
            'condition' => \Rule::PATTERN_CONTAIN,
            'pattern'   => 'assign to tech',
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => '_users_id_assign',
            'value'       => getItemByTypeName('User', 'tech', true),
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);

        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'status',
            'value'       => \CommonITILObject::INCOMING,
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);

        // Check ITIL Object that trigger rule on creation
        $itil = $this->getITILObjectInstance();
        $itil_id = $itil->add($itil_input = [
            'name'    => 'assign to tech (on creation)',
            'content' => 'test'
        ]);
        $itil_fk = $this->getITILObjectClass()::getForeignKeyField();
        $itil_user_table = $this->getITILLinkClass('User')::getTable();

        $this->checkInput($itil, $itil_id, $itil_input);
        $this->integer((int)$itil->getField('status'))->isEqualTo(\CommonITILObject::INCOMING);
        $this->integer(countElementsInTable(
            $itil_user_table,
            [$itil_fk => $itil_id, 'type' => \CommonITILActor::ASSIGN]
        ))->isEqualTo(1);

        // The next part only applies to tickets
        if ($itil::getType() === 'Ticket') {
            // Check ITIL Object that trigger rule on update
            $itil = $this->getITILObjectInstance();
            $itil_id = $itil->add($itil_input = [
                'name'              => 'some ITIL Object',
                'content'           => 'test',
                '_users_id_assign'  => getItemByTypeName('User', TU_USER, true),
            ]);
            unset($itil_input['_users_id_assign']);
            $this->checkInput($itil, $itil_id, $itil_input);
            $this->integer((int)$itil->getField('status'))->isEqualTo(\Ticket::ASSIGNED);
            $this->integer(countElementsInTable(
                $itil_user_table,
                [$itil_fk => $itil_id, 'type' => \CommonITILActor::ASSIGN]
            ))->isEqualTo(1); // Assigned to TU_USER

            $this->boolean($itil->update([
                'id'                => $itil_id,
                'name'              => 'assign to tech (on update)',
                'content'           => 'test',
                '_users_id_assign'  => getItemByTypeName('User', 'glpi', true), // rule should erase this value
            ]))->isTrue();
            $this->boolean($itil->getFromDB($itil_id))->isTrue();
            $this->integer((int)$itil->getField('status'))->isEqualTo(\CommonITILObject::INCOMING);
            $this->integer(countElementsInTable(
                $itil_user_table,
                [$itil_fk => $itil_id, 'type' => \CommonITILActor::ASSIGN]
            ))->isEqualTo(2); // Assigned to TU_USER + tech
        }
    }

    public function testITILCategoryAssignFromRule()
    {
        $this->login();

        // Create ITILCategory with code
        $ITILCategoryForAdd = new \ITILCategory();
        $ITILCategoryForAddId = $ITILCategoryForAdd->add($categoryinput = [
            "name" => "ITIL Category",
            "code" => "itil_category_for_add",
        ]);

        $this->integer((int)$ITILCategoryForAddId)->isGreaterThan(0);

        // Create ITILCategory with code
        $ITILCategoryForUpdate = new \ITILCategory();
        $ITILCategoryForUpdateId = $ITILCategoryForUpdate->add($categoryinput = [
            "name" => "ITIL Category",
            "code" => "itil_category_for_update",
        ]);

        $this->integer((int)$ITILCategoryForUpdateId)->isGreaterThan(0);

        // Create rule
        $rule_itil = $this->getRuleInstance();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $rule_itil->add($ruletinput = [
            'name'         => 'test to assign ITILCategory',
            'match'        => 'OR',
            'is_active'    => 1,
            'sub_type'     => $this->getTestedClass(),
            'condition'    => \RuleCommonITILObject::ONADD | \RuleCommonITILObject::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->checkInput($rule_itil, $ruletid, $ruletinput);

        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'content',
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => '/#(.*?)#/',
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'regex_result',
            'field'       => '_affect_itilcategory_by_code',
            'value'       => '#0',
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);

        // Check ITIL Object that trigger rule on add
        $itil = $this->getITILObjectInstance();
        $itil_id = $itil->add($itil_input = [
            'name'    => 'some ITIL Object (on insert)',
            'content' => 'some text #itil_category_for_add# some text'
        ]);

        $this->checkInput($itil, $itil_id, $itil_input);
        $this->integer((int)$itil->getField('itilcategories_id'))->isEqualTo($ITILCategoryForAddId);

        $this->boolean($itil->update($itil_input = [
            'id'      => $itil_id,
            'name'    => 'some ITIL Object (on update)',
            'content' => 'some text #itil_category_for_update# some text'
        ]))->isTrue();

        $this->checkInput($itil, $itil_id, $itil_input);
        $this->integer((int)$itil->getField('itilcategories_id'))->isEqualTo($ITILCategoryForUpdateId);
    }

    public function testITILSolutionAssignFromRule()
    {
        $this->login();

        // Create solution template
        $solutionTemplate = new \SolutionTemplate();
        $solutionTemplate_id = $solutionTemplate->add($solutionInput = [
            'content' => "<p>content of solution template  white ' quote</p>"
        ]);
        $this->integer((int)$solutionTemplate_id)->isGreaterThan(0);

        // Create rule
        $rule_itil = $this->getRuleInstance();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $rule_itil->add($ruletinput = [
            'name'         => "test to assign ITILSolution",
            'match'        => 'OR',
            'is_active'    => 1,
            'sub_type'     => $this->getTestedClass(),
            'condition'    => \RuleCommonITILObject::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->checkInput($rule_itil, $ruletid, $ruletinput);

        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'content',
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => '/(.*?)/',
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'solution_template',
            'value'       => $solutionTemplate_id,
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);

        $itil = $this->getITILObjectInstance();
        $itil_id = $itil->add($itil_input = [
            'name'    => 'some ITIL Object',
            'content' => 'some text some text'
        ]);

        $this->checkInput($itil, $itil_id, $itil_input);
        $this->integer((int)$itil_id)->isGreaterThan(0);

        // update ITIL Object content and trigger rule on content updating
        $this->boolean(
            $itil->update([
                'id'   => $itil_id,
                'content' => 'test ITIL Object, will trigger on rule (content)'
            ]),
            'Not updated'
        );

        //load ITILSolution
        $itilSolution = new \ITILSolution();
        $this->boolean($itilSolution->getFromDBByCrit([
            'items_id' => $itil_id,
            'itemtype' => $itil::getType(),
        ]))->isTrue();

        $this->integer((int)$itilSolution->getID())->isGreaterThan(0);
        $this->string($itilSolution->fields['content'])->isEqualTo("<p>content of solution template  white &#039; quote</p>");

        //reload and check ITIL Object status
        $itil->getFromDB($itil_id);
        $this->integer((int)$itil->getField('status'))->isEqualTo(\CommonITILObject::SOLVED);
    }

    public function testAssignGroup()
    {
        $this->login();

        //create new group1
        $group1 = new \Group();
        $group_id1 = $group1->add($group_input1 = [
            "name" => "group1",
            "is_requester" => true
        ]);
        $this->checkInput($group1, $group_id1, $group_input1);

        //create new group2
        $group2 = new \Group();
        $group_id2 = $group2->add($group_input2 = [
            "name" => "group2",
            "is_requester" => true
        ]);
        $this->checkInput($group2, $group_id2, $group_input2);

        // Create rule
        $rule_itil = $this->getRuleInstance();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $rule_itil->add($ruletinput = [
            'name'         => 'test add group on add',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => $this->getTestedClass(),
            'condition'    => \RuleCommonITILObject::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($rule_itil, $ruletid, $ruletinput);

        //create criteria to check
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'content',
            'condition' => \Rule::PATTERN_EXISTS,
            'pattern'   => 1,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        //create action to add group as group requester
        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'add',
            'field'       => '_groups_id_requester',
            'value'       => $group_id1,
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

        //create rule for assign
        $ruletid_assign = $rule_itil->add($ruletinput_assign = [
            'name'         => 'test assign group on add',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => $this->getTestedClass(),
            'condition'    => \RuleCommonITILObject::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($rule_itil, $ruletid_assign, $ruletinput_assign);

        //create criteria to check
        $crit_id_assign = $rulecrit->add($crit_input_assing = [
            'rules_id'  => $ruletid_assign,
            'criteria'  => 'content',
            'condition' => \Rule::PATTERN_EXISTS,
            'pattern'   => 1,
        ]);
        $this->checkInput($rulecrit, $crit_id_assign, $crit_input_assing);

        //create action to assign group as group requester
        $action_id_assign = $ruleaction->add($action_input_assign = [
            'rules_id'    => $ruletid_assign,
            'action_type' => 'assign',
            'field'       => '_groups_id_requester',
            'value'       => $group_id2,
        ]);
        $this->checkInput($ruleaction, $action_id_assign, $action_input_assign);

        // Create ITIL Object
        $itil = $this->getITILObjectInstance();
        $itil_fk = $this->getITILObjectClass()::getForeignKeyField();
        $itil_id = $itil->add($itil_input = [
            'name'             => 'when assigning delete groups to add',
            'content'          => 'test',
        ]);
        $this->checkInput($itil, $itil_id, $itil_input);

        //load ITILGroup1 (expected false)
        $itil_group = $this->getITILLinkInstance('Group');
        $this->boolean(
            $itil_group->getFromDBByCrit([
                $itil_fk    => $itil_id,
                'groups_id' => $group_id1,
                'type'      => \CommonITILActor::REQUESTER
            ])
        )->isFalse();

        //load ITILGroup2 (expected true)
        $itil_group = $this->getITILLinkInstance('Group');
        $this->boolean(
            $itil_group->getFromDBByCrit([
                $itil_fk    => $itil_id,
                'groups_id' => $group_id2,
                'type'      => \CommonITILActor::REQUESTER
            ])
        )->isTrue();
    }

    public function testGroupRequesterAssignFromDefaultUserOnCreate()
    {
        $this->login();

        // Create rule
        $rule_itil = $this->getRuleInstance();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $rule_itil->add($ruletinput = [
            'name'         => 'test group requester criterion',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => $this->getTestedClass(),
            'condition'    => \RuleCommonITILObject::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($rule_itil, $ruletid, $ruletinput);

        //create criteria to check if group requester already define
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_groups_id_requester',
            'condition' => \Rule::PATTERN_DOES_NOT_EXISTS,
            'pattern'   => 1,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        //create action to put default user group as group requester
        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'defaultfromuser',
            'field'       => '_groups_id_requester',
            'value'       => 1,
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

        //create new group
        $group = new \Group();
        $group_id = $group->add($group_input = [
            "name" => "group1",
            "is_requester" => true
        ]);
        $this->checkInput($group, $group_id, $group_input);

        //Load user tech
        $user = new \User();
        $user->getFromDB(getItemByTypeName('User', 'tech', true));

        //add user to group
        $group_user = new Group_User();
        $group_user_id = $group_user->add($group_user_input = [
            "groups_id" => $group_id,
            "users_id"  => $user->fields['id']
        ]);
        $this->checkInput($group_user, $group_user_id, $group_user_input);

        //add default group to user
        $user->fields['groups_id'] = $group_id;
        $this->boolean($user->update($user->fields))->isTrue();

        // Check ITIL Object that trigger rule on creation
        $itil = $this->getITILObjectInstance();
        $itil_id = $itil->add($itil_input = [
            'name'             => 'Add group requester if requester have default group',
            'content'          => 'test',
            '_users_id_requester' => $user->fields['id']
        ]);
        unset($itil_input['_users_id_requester']); // _users_id_requester is stored in glpi_*_users table, so remove it
        $this->checkInput($itil, $itil_id, $itil_input);

        //load ITILGroup
        $itil_group = $this->getITILLinkInstance('Group');
        $itil_fk = $this->getITILObjectClass()::getForeignKeyField();
        $this->boolean(
            $itil_group->getFromDBByCrit([
                $itil_fk    => $itil_id,
                'groups_id' => $group_id,
                'type'      => \CommonITILActor::REQUESTER
            ])
        )->isTrue();
    }


    public function testTaskTemplateAssignFromRule()
    {
        $this->login();

        // Create solution template
        $task_template = new TaskTemplate();
        $task_template_id = $task_template->add([
            'content' => "<p>test content</p>"
        ]);
        $this->integer($task_template_id)->isGreaterThan(0);

        $itil_class = $this->getITILObjectClass();

        // Create rule
        $rule_itil_em = $this->getRuleInstance();
        $rule_itil_id = $rule_itil_em->add($ruletinput = [
            'name'         => "test to assign ITILSolution",
            'match'        => 'OR',
            'is_active'    => 1,
            'sub_type'     => $this->getTestedClass(),
            'condition'    => \RuleCommonITILObject::ONADD + \RuleCommonITILObject::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->integer($rule_itil_id)->isGreaterThan(0);

        // Add condition (priority = 5) to rule
        $rule_criteria_em = new RuleCriteria();
        $rule_criteria_id = $rule_criteria_em->add($crit_input = [
            'rules_id'  => $rule_itil_id,
            'criteria'  => 'priority',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => 5,
        ]);
        $this->integer($rule_criteria_id)->isGreaterThan(0);

        // Add action to rule
        $rule_action_em = new RuleAction();
        $rule_action_id = $rule_action_em->add($act_input = [
            'rules_id'    => $rule_itil_id,
            'action_type' => 'append',
            'field'       => 'task_template',
            'value'       => $task_template_id,
        ]);
        $this->integer($rule_action_id)->isGreaterThan(0);

        // Test on creation
        $itil_em = $this->getITILObjectInstance();
        $itil_fk = $itil_em::getForeignKeyField();
        $itil_id = $itil_em->add([
            'name'     => 'test',
            'content'  => 'test',
            'priority' => 5,
        ]);
        $this->integer($itil_id)->isGreaterThan(0);

        $itil_task_em = $this->getITILLinkInstance('Task');
        $itil_tasks = $itil_task_em->find([
            $itil_fk => $itil_id
        ]);

        $this->array($itil_tasks)->hasSize(1);
        $task_data = array_pop($itil_tasks);
        $this->array($task_data)->hasKey('content');
        $this->string($task_data['content'])->isEqualTo('<p>test content</p>');

        // Test on update
        $itil_em = $this->getITILObjectInstance();
        $itil_id = $itil_em->add([
            'name'     => 'test',
            'content'  => 'test',
            'priority' => 4,
        ]);
        $this->integer($itil_id)->isGreaterThan(0);

        $itil_task_em = $this->getITILLinkInstance('Task');
        $itil_tasks = $itil_task_em->find([
            $itil_fk => $itil_id
        ]);

        $this->array($itil_tasks)->hasSize(0);

        $itil_em->update([
            'id' => $itil_id,
            'priority' => 5,
        ]);
        $itil_tasks = $itil_task_em->find([
            $itil_fk => $itil_id
        ]);

        $this->array($itil_tasks)->hasSize(1);
        $task_data = array_pop($itil_tasks);
        $this->array($task_data)->hasKey('content');
        $this->string($task_data['content'])->isEqualTo('<p>test content</p>');

        // Add a second action to the rule (test multiple creation)
        $this->createItem('TaskTemplate', [
            'name'    => "template 2",
            'content' => '<p>test content 2</p>',
        ]);

        $this->createItem('RuleAction', [
            'rules_id'    => $rule_itil_id,
            'action_type' => 'append',
            'field'       => 'task_template',
            'value'       => getItemByTypeName("TaskTemplate", "template 2", true),
        ]);

        $this->createItem($this->getITILObjectClass(), [
            'name'     => 'test ITIL Object with two tasks',
            'content'  => 'test',
            'priority' => 5,
        ]);

        $itil_tasks = $itil_task_em->find([
            $itil_fk => getItemByTypeName($this->getITILObjectClass(), 'test ITIL Object with two tasks', true),
        ]);
        $this->array($itil_tasks)->hasSize(2);

        $task_data = array_pop($itil_tasks);
        $this->array($task_data)->hasKey('content');
        $this->string($task_data['content'])->isEqualTo('<p>test content 2</p>');

        $task_data = array_pop($itil_tasks);
        $this->array($task_data)->hasKey('content');
        $this->string($task_data['content'])->isEqualTo('<p>test content</p>');
    }

    public function testFollowupTemplateAssignFromRule()
    {
        $this->login();

        // Create followup template
        $followup_template = new ITILFollowupTemplate();
        $followup_template_id = $followup_template->add([
            'content' => "<p>test testFollowupTemplateAssignFromRule</p>"
        ]);
        $this->integer($followup_template_id)->isGreaterThan(0);

        $itil_class = $this->getITILObjectClass();
        $itil_fk = $itil_class::getForeignKeyField();

        // Create rule
        $rule_itil = $this->getRuleInstance();
        $rule_itil_id = $rule_itil->add([
            'name'         => "test to assign ITILSolution",
            'match'        => 'OR',
            'is_active'    => 1,
            'sub_type'     => $this->getTestedClass(),
            'condition'    => \RuleCommonITILObject::ONADD + \RuleCommonITILObject::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->integer($rule_itil_id)->isGreaterThan(0);

        // Add condition (priority = 5) to rule
        $rule_criteria = new RuleCriteria();
        $rule_criteria_id = $rule_criteria->add([
            'rules_id'  => $rule_itil_id,
            'criteria'  => 'priority',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => 4,
        ]);
        $this->integer($rule_criteria_id)->isGreaterThan(0);

        // Add action to rule
        $rule_action = new RuleAction();
        $rule_action_id = $rule_action->add([
            'rules_id'    => $rule_itil_id,
            'action_type' => 'append',
            'field'       => 'itilfollowup_template',
            'value'       => $followup_template_id,
        ]);
        $this->integer($rule_action_id)->isGreaterThan(0);

        // Test on creation
        $itil = $this->getITILObjectInstance();
        $itil_id = $itil->add([
            'name'     => 'test',
            'content'  => 'test',
            'priority' => 4,
        ]);
        $this->integer($itil_id)->isGreaterThan(0);

        $itil_followups = new ITILFollowup();
        $itil_followups = $itil_followups->find([
            'items_id' => $itil_id,
            'itemtype' => $itil_class,
        ]);

        $this->array($itil_followups)->hasSize(1);
        $itil_followups_data = array_pop($itil_followups);
        $this->array($itil_followups_data)->hasKey('content');
        $this->string($itil_followups_data['content'])->isEqualTo('<p>test testFollowupTemplateAssignFromRule</p>');

        // Test on update
        $itil = $this->getITILObjectInstance();
        $itil_id = $itil->add([
            'name'     => 'test',
            'content'  => 'test',
            'priority' => 3,
        ]);
        $this->integer($itil_id)->isGreaterThan(0);

        $itil_followups = new ITILFollowup();
        $itil_followups = $itil_followups->find([
            'items_id' => $itil_id,
            'itemtype' => $itil_class,
        ]);

        $this->array($itil_followups)->hasSize(0);

        $itil->update([
            'id' => $itil_id,
            'priority' => 4,
        ]);

        $itil_followups = new ITILFollowup();
        $itil_followups = $itil_followups->find([
            'items_id' => $itil_id,
            'itemtype' => $itil_class,
        ]);

        $this->array($itil_followups)->hasSize(1);
        $itil_followups_data = array_pop($itil_followups);
        $this->array($itil_followups_data)->hasKey('content');
        $this->string($itil_followups_data['content'])->isEqualTo('<p>test testFollowupTemplateAssignFromRule</p>');

        // Add a second action to the rule (test multiple creation)
        $this->createItem('ITILFollowupTemplate', [
            'name'    => "template 2",
            'content' => '<p>test testFollowupTemplateAssignFromRule 2</p>',
        ]);

        $this->createItem('RuleAction', [
            'rules_id'    => $rule_itil_id,
            'action_type' => 'append',
            'field'       => 'itilfollowup_template',
            'value'       => getItemByTypeName("ITILFollowupTemplate", "template 2", true),
        ]);

        $this->createItem($itil_class, [
            'name'     => 'test ITIL Object with two followups',
            'content'  => 'test',
            'priority' => 4,
        ]);

        $itil_followups = new ITILFollowup();
        $itil_followups = $itil_followups->find([
            'items_id' => getItemByTypeName($itil_class, 'test ITIL Object with two followups', true),
            'itemtype' => $itil_class,
        ]);
        $this->array($itil_followups)->hasSize(2);

        $itil_followups_data = array_pop($itil_followups);
        $this->array($itil_followups_data)->hasKey('content');
        $this->string($itil_followups_data['content'])->isEqualTo('<p>test testFollowupTemplateAssignFromRule 2</p>');

        $itil_followups_data = array_pop($itil_followups);
        $this->array($itil_followups_data)->hasKey('content');
        $this->string($itil_followups_data['content'])->isEqualTo('<p>test testFollowupTemplateAssignFromRule</p>');
    }

    public function testGroupRequesterAssignFromUserGroupsAndRegexOnUpdateITILContent()
    {
        $this->login();

        // Create rule to be triggered on ITIL Object update
        $rule_itil = $this->getRuleInstance();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $rule_itil->add($ruletinput = [
            'name'         => 'test regex group requester criterion',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => $this->getTestedClass(),
            'condition'    => \RuleCommonITILObject::ONUPDATE,
            'is_recursive' => 0,
        ]);
        $this->checkInput($rule_itil, $ruletid, $ruletinput);

        //create criteria to check if group requester match regex (group with parenthesia)
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_groups_id_of_requester',
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => '/(.+\([^()]*\))/',   //retrieve group with '(' and ')'
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        //create action to put the groups that match the criteria
        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'regex_result',
            'field'       => '_groups_id_requester',
            'value'       => '#0',
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

        //Load user post_only
        $user = new \User();
        $user->getFromDB(getItemByTypeName('User', 'post-only', true));

        //create group that matches the rule
        $group = new \Group();
        $group_id1 = $group->add($group_input = [
            "name"         => "group1 (5215)",
            "is_requester" => true
        ]);
        $this->checkInput($group, $group_id1, $group_input);

        //create group that matches the rule
        $group_id2 = $group->add($group_input = [
            "name"         => "group2 (13)",
            "is_requester" => true
        ]);
        $this->checkInput($group, $group_id2, $group_input);

        //create group that not matches the rule
        $group_id3 = $group->add($group_input = [
            "name"         => "group3",
            "is_requester" => true
        ]);
        $this->checkInput($group, $group_id3, $group_input);

        // create ITIL Object
        $itil = $this->getITILObjectInstance();
        $itil_fk = $itil::getForeignKeyField();
        $itil->getEmpty();
        $itil_id = $itil->add($itil_input = [
            'name'                  => 'Add group requester',
            'content'               => 'test',
            '_users_id_requester'   => $user->fields['id']
        ]);
        unset($itil_input['_users_id_requester']);
        $this->checkInput($itil, $itil_id, $itil_input);

        //link between group1 and ITIL Object will not exist
        $itil_group = $this->getITILLinkInstance('Group');
        $this->boolean(
            $itil_group->getFromDBByCrit([
                $itil_fk    => $itil_id,
                'groups_id' => $group_id1,
                'type'      => \CommonITILActor::REQUESTER
            ])
        )->isFalse();

        //link between group2 and ITIL Object will not exist
        $this->boolean(
            $itil_group->getFromDBByCrit([
                $itil_fk    => $itil_id,
                'groups_id' => $group_id2,
                'type'      => \CommonITILActor::REQUESTER
            ])
        )->isFalse();

        //link between group3 and ITIL Object will not exist
        $this->boolean(
            $itil_group->getFromDBByCrit([
                $itil_fk    => $itil_id,
                'groups_id' => $group_id3,
                'type'      => \CommonITILActor::REQUESTER
            ])
        )->isFalse();

        //add user to groups
        $group_user = new Group_User();
        $group_user_id1 = $group_user->add($group_user_input = [
            "groups_id" => $group_id1,
            "users_id"  => $user->fields['id']
        ]);
        $this->checkInput($group_user, $group_user_id1, $group_user_input);

        $group_user = new Group_User();
        $group_user_id2 = $group_user->add($group_user_input = [
            "groups_id" => $group_id2,
            "users_id"  => $user->fields['id']
        ]);
        $this->checkInput($group_user, $group_user_id2, $group_user_input);

        $group_user = new Group_User();
        $group_user_id3 = $group_user->add($group_user_input = [
            "groups_id" => $group_id3,
            "users_id"  => $user->fields['id']
        ]);
        $this->checkInput($group_user, $group_user_id3, $group_user_input);

        //update ITIL Object
        $itil->update($itil_input = [
            'id'                    => $itil_id,
            'content'               => 'testupdated',
        ]);
        $this->checkInput($itil, $itil_id, $itil_input);

        //link between group1 and ITIL Object will exist
        $itil_group = $this->getITILLinkInstance('Group');
        $this->boolean(
            $itil_group->getFromDBByCrit([
                $itil_fk    => $itil_id,
                'groups_id' => $group_id1,
                'type'      => \CommonITILActor::REQUESTER
            ])
        )->isTrue();

        //link between group2 and ITIL Object will exist
        $this->boolean(
            $itil_group->getFromDBByCrit([
                $itil_fk    => $itil_id,
                'groups_id' => $group_id2,
                'type'      => \CommonITILActor::REQUESTER
            ])
        )->isTrue();

        //link between group3 and ITIL Object will not exist
        $this->boolean(
            $itil_group->getFromDBByCrit([
                $itil_fk    => $itil_id,
                'groups_id' => $group_id3,
                'type'      => \CommonITILActor::REQUESTER
            ])
        )->isFalse();
    }

    public function testGroupRequesterAssignFromUserGroupsAndRegexOnAdd()
    {
        $this->login();

        // Create rule to be triggered on add
        $rule_itil = $this->getRuleInstance();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $rule_itil->add($ruletinput = [
            'name'         => 'test regex group requester criterion',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => $this->getTestedClass(),
            'condition'    => \RuleCommonITILObject::ONADD,
            'is_recursive' => 0,
        ]);
        $this->checkInput($rule_itil, $ruletid, $ruletinput);

        //create criteria to check if group requester match regex
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_groups_id_of_requester',
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => '/(.+\([^()]*\))/',   //retrieve group with '(' and ')'
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        //create action to put group matching on criteria
        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'regex_result',
            'field'       => '_groups_id_requester',
            'value'       => '#0',
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

        //create group that matches the rule
        $group = new \Group();
        $group_id1 = $group->add($group_input = [
            "name"         => "group1 (5215)",
            "is_requester" => true
        ]);
        $this->checkInput($group, $group_id1, $group_input);

        //create group that matches the rule
        $group_id2 = $group->add($group_input = [
            "name"         => "group2 (13)",
            "is_requester" => true
        ]);
        $this->checkInput($group, $group_id2, $group_input);

        //create group that not matches the rule
        $group_id3 = $group->add($group_input = [
            "name"         => "group3",
            "is_requester" => true
        ]);
        $this->checkInput($group, $group_id3, $group_input);

        //Load user post_only
        $user = new \User();
        $user->getFromDB(getItemByTypeName('User', 'post-only', true));

        //add user to groups
        $group_user = new Group_User();
        $group_user_id1 = $group_user->add($group_user_input = [
            "groups_id" => $group_id1,
            "users_id"  => $user->fields['id']
        ]);
        $this->checkInput($group_user, $group_user_id1, $group_user_input);

        $group_user = new Group_User();
        $group_user_id2 = $group_user->add($group_user_input = [
            "groups_id" => $group_id2,
            "users_id"  => $user->fields['id']
        ]);
        $this->checkInput($group_user, $group_user_id2, $group_user_input);

        $group_user = new Group_User();
        $group_user_id3 = $group_user->add($group_user_input = [
            "groups_id" => $group_id3,
            "users_id"  => $user->fields['id']
        ]);
        $this->checkInput($group_user, $group_user_id3, $group_user_input);

        // create ITIL Object that trigger rule on creation
        $itil = $this->getITILObjectInstance();
        $itil_fk = $itil->getForeignKeyField();
        $itil->getEmpty();
        $itil_id = $itil->add($itil_input = [
            'name'                  => 'Add group requester',
            'content'               => 'test',
            '_users_id_requester'   => $user->fields['id']
        ]);
        unset($itil_input['_users_id_requester']);
        $this->checkInput($itil, $itil_id, $itil_input);

        //link between group1 and ITIL Object will exist
        $itil_group = $this->getITILLinkInstance('Group');
        $this->boolean(
            $itil_group->getFromDBByCrit([
                $itil_fk    => $itil_id,
                'groups_id' => $group_id1,
                'type'      => \CommonITILActor::REQUESTER
            ])
        )->isTrue();

        //link between group2 and ITIL Object will exist
        $this->boolean(
            $itil_group->getFromDBByCrit([
                $itil_fk    => $itil_id,
                'groups_id' => $group_id2,
                'type'      => \CommonITILActor::REQUESTER
            ])
        )->isTrue();

        //link between group3 and ITIL Object will not exist
        $this->boolean(
            $itil_group->getFromDBByCrit([
                $itil_fk         => $itil_id,
                'groups_id'          => $group_id3,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isFalse();
    }

    public function testGroupRequesterAssignFromUserGroupsAndRegexOnUpdate()
    {
        $this->login();

        $rule_itil = $this->getRuleInstance();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        // Create rule to be triggered on add
        $ruletid = $rule_itil->add($ruletinput = [
            'name'         => 'test regex group requester criterion',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => $this->getTestedClass(),
            'condition'    => \RuleCommonITILObject::ONUPDATE,
            'is_recursive' => 0,
        ]);
        $this->checkInput($rule_itil, $ruletid, $ruletinput);

        //create criteria to check if group requester match regex
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_groups_id_of_requester',
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => '/(.+\([^()]*\))/',   //retrieve group with '(' and ')'
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        //create action to put  group matching on criteria
        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'regex_result',
            'field'       => '_groups_id_requester',
            'value'       => '#0',
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

        //create group that matches the rule
        $group = new \Group();
        $group_id1 = $group->add($group_input = [
            "name"         => "group1 (5215)",
            "is_requester" => true
        ]);
        $this->checkInput($group, $group_id1, $group_input);

        //create group that matches the rule
        $group_id2 = $group->add($group_input = [
            "name"         => "group2 (13)",
            "is_requester" => true
        ]);
        $this->checkInput($group, $group_id2, $group_input);

        //create group that not matches the rule
        $group_id3 = $group->add($group_input = [
            "name"         => "group3",
            "is_requester" => true
        ]);
        $this->checkInput($group, $group_id3, $group_input);

        //Load user post_only
        $userPostOnly = new \User();
        $userPostOnly->getFromDB(getItemByTypeName('User', 'post-only', true));

        //Load user normal
        $userNormal = new \User();
        $userNormal->getFromDB(getItemByTypeName('User', 'normal', true));

        //add to normal user to groups
        $group_user = new Group_User();
        $group_user_id1 = $group_user->add($group_user_input = [
            "groups_id" => $group_id1,
            "users_id"  => $userNormal->fields['id']
        ]);
        $this->checkInput($group_user, $group_user_id1, $group_user_input);

        $group_user = new Group_User();
        $group_user_id2 = $group_user->add($group_user_input = [
            "groups_id" => $group_id2,
            "users_id"  => $userNormal->fields['id']
        ]);
        $this->checkInput($group_user, $group_user_id2, $group_user_input);

        $group_user = new Group_User();
        $group_user_id3 = $group_user->add($group_user_input = [
            "groups_id" => $group_id3,
            "users_id"  => $userNormal->fields['id']
        ]);
        $this->checkInput($group_user, $group_user_id3, $group_user_input);

        // create ITIL Object that trigger rule on creation
        $itil = $this->getITILObjectInstance();
        $itil_fk = $itil::getForeignKeyField();
        $itil->getEmpty();
        $itil_id = $itil->add($itil_input = [
            'name'                  => 'Add group requester',
            'content'               => 'test',
            '_users_id_requester'   => $userPostOnly->fields['id']
        ]);
        unset($itil_input['_users_id_requester']);
        $this->checkInput($itil, $itil_id, $itil_input);

        //link between group1 and ITIL Object will not exist
        $itil_group = $this->getITILLinkInstance('Group');
        $this->boolean(
            $itil_group->getFromDBByCrit([
                $itil_fk         => $itil_id,
                'groups_id'          => $group_id1,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isFalse();

        //link between group2 and ITIL Object will not exist
        $this->boolean(
            $itil_group->getFromDBByCrit([
                $itil_fk         => $itil_id,
                'groups_id'          => $group_id2,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isFalse();

        //link between group2 and ITIL Object will not exist
        $this->boolean(
            $itil_group->getFromDBByCrit([
                $itil_fk         => $itil_id,
                'groups_id'          => $group_id3,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isFalse();

        //remove old user manually because from IHM is done before update ITIL Object
        $itil_user = $this->getITILLinkInstance('User');
        $itil_user->deleteByCriteria([
            "users_id" => $userPostOnly->fields['id'],
            $itil_fk => $itil_id
        ]);

        //update ITIL Object and change requester
        $itil->update($itil_input = [
            'name'                  => 'Add group requester',
            'id'                    => $itil_id,
            'content'               => 'test',
            '_itil_requester'   => ["_type" => "user",
                "users_id" => $userNormal->fields['id']
            ]
        ]);
        unset($itil_input['_itil_requester']);
        $this->checkInput($itil, $itil_id, $itil_input);

        //link between group1 and ITIL Object will exist
        $itil_group = $this->getITILLinkInstance('Group');
        $this->boolean(
            $itil_group->getFromDBByCrit([
                $itil_fk    => $itil_id,
                'groups_id' => $group_id1,
                'type'      => \CommonITILActor::REQUESTER
            ])
        )->isTrue();

        //link between group2 and ITIL Object will exist
        $this->boolean(
            $itil_group->getFromDBByCrit([
                $itil_fk         => $itil_id,
                'groups_id'          => $group_id2,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isTrue();

        //link between group3 and ITIL Object will not exist
        $this->boolean(
            $itil_group->getFromDBByCrit([
                $itil_fk         => $itil_id,
                'groups_id'          => $group_id3,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isFalse();
    }

    public function testValidationCriteria()
    {
        $itil_class = $this->getItilObjectClass();
        $validation_class = $itil_class . 'Validation';
        if (!class_exists($validation_class)) {
            // Useless assertion to prevent atoum from marking this as a failure
            $this->boolean(true)->isTrue();
            return;
        }
        $this->login();

        // Create rule
        $rule_itil = $this->getRuleInstance();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $rule_itil->add($ruletinput = [
            'name'         => 'test validation criteria',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => $this->getTestedClass(),
            'condition'    => \RuleCommonITILObject::ONADD | \RuleCommonITILObject::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->checkInput($rule_itil, $ruletid, $ruletinput);

        // Create criteria to check if validation code is accepted
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'global_validation',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => CommonITILValidation::ACCEPTED,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        // Create action to put impact to very low
        $action_value = 2;
        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'impact',
            'value'       => $action_value,
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

        // Case 1: create a ITIL Object without validation, should not trigger the rule
        $itil = $this->getITILObjectInstance();
        $itil_id = $itil->add($itil_input = [
            'name'              => 'test validation criteria',
            'content'           => 'test validation criteria',
            'global_validation' => CommonITILValidation::WAITING,
        ]);
        $this->checkInput($itil, $itil_id, $itil_input);
        $this->boolean($itil->getFromDB($itil_id))->isTrue();

        // Check that the rule was NOT executed
        $this->boolean($itil->getFromDB($itil_id))->isTrue();
        $this->integer($itil->fields['impact'])->isNotEqualTo($action_value);

        // Case 2: add validation to the ITIL Object, should trigger the rule
        $update = $itil->update([
            'id'                => $itil_id,
            'global_validation' => CommonITILValidation::ACCEPTED,
        ]);
        $this->boolean($update)->isTrue();
        $this->boolean($itil->getFromDB($itil_id))->isTrue();

        // Check that the rule was executed
        $this->boolean($itil->getFromDB($itil_id))->isTrue();
        $this->integer($itil->fields['impact'])->isEqualTo($action_value);

        // Case 3: create a ITIL Object with validation, should trigger the rule
        $itil = $this->getITILObjectInstance();
        $itil_id = $itil->add($itil_input = [
            'name'              => 'test validation criteria',
            'content'           => 'test validation criteria',
            'global_validation' => CommonITILValidation::ACCEPTED,
        ]);
        $this->checkInput($itil, $itil_id, $itil_input);
        $this->boolean($itil->getFromDB($itil_id))->isTrue();

        // Check that the rule was executed
        $this->integer($itil->fields['impact'])->isEqualTo($action_value);
    }

    public function testValidationAction()
    {
        $itil_class = $this->getItilObjectClass();
        $validation_class = $itil_class . 'Validation';
        if (!class_exists($validation_class)) {
            // Useless assertion to prevent atoum from marking this as a failure
            $this->boolean(true)->isTrue();
            return;
        }
        $this->login();

        // Create rule
        $rule_itil = $this->getRuleInstance();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $rule_itil->add($ruletinput = [
            'name'         => 'test validation action',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => $this->getTestedClass(),
            'condition'    => \RuleCommonITILObject::ONADD | \RuleCommonITILObject::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->checkInput($rule_itil, $ruletid, $ruletinput);

        // Create criteria to check if validation code is accepted
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'priority',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => 6,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        // Create action to set validation to "refused"
        $action_value = CommonITILValidation::REFUSED;
        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'global_validation',
            'value'       => $action_value,
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

        // Case 1: create a ITIL Object that should not trigger the rule
        $itil = $this->getITILObjectInstance();
        $itil_id = $itil->add($itil_input = [
            'name'     => 'test validation action',
            'content'  => 'test validation action',
            'priority' => 4,
        ]);
        $this->checkInput($itil, $itil_id, $itil_input);
        $this->boolean($itil->getFromDB($itil_id))->isTrue();

        // Check that the rule was NOT executed
        $this->boolean($itil->getFromDB($itil_id))->isTrue();
        $this->integer($itil->fields['global_validation'])->isNotEqualTo($action_value);

        // Case 2: add target priority to the ITIL Object, should trigger the rule
        $update = $itil->update([
            'id'       => $itil_id,
            'priority' => 6,
        ]);
        $this->boolean($update)->isTrue();
        $this->boolean($itil->getFromDB($itil_id))->isTrue();

        // Check that the rule was executed
        $this->boolean($itil->getFromDB($itil_id))->isTrue();
        $this->integer($itil->fields['global_validation'])->isEqualTo($action_value);

        // Case 3: create a ITIL Object with target priority, should trigger the rule
        $itil = $this->getITILObjectInstance();
        $itil_id = $itil->add($itil_input = [
            'name'     => 'test validation action',
            'content'  => 'test validation action',
            'priority' => 6
        ]);
        $this->checkInput($itil, $itil_id, $itil_input);
        $this->boolean($itil->getFromDB($itil_id))->isTrue();

        // Check that the rule was executed
        $this->integer($itil->fields['global_validation'])->isEqualTo($action_value);
    }

    public function testITILCategoryCode()
    {
        $this->login();

        // Common variables that will be reused
        $rule_criteria_category_code = "R";
        $rule_action_impact_value = 1; // very low

        // Create rule, rule criteria and rule action
        $rule = $this->createItem($this->getTestedClass(), [
            'name'         => 'test category code',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => $this->getTestedClass(),
            'condition'    => \RuleCommonITILObject::ONADD | \RuleCommonITILObject::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->createItem('RuleCriteria', [
            'rules_id'  => $rule->getID(),
            'criteria'  => 'itilcategories_id_code',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => $rule_criteria_category_code,
        ]);
        $this->createItem('RuleAction', [
            'rules_id'    => $rule->getID(),
            'action_type' => 'assign',
            'field'       => 'impact',
            'value'       => $rule_action_impact_value,
        ]);

        // Create new category
        $category = $this->createItem('ITILCategory', [
            "name" => "category_test",
            "code" => $rule_criteria_category_code,
        ]);

        // Check ITIL Object that trigger rule on creation
        $itil = $this->createItem($this->getITILObjectClass(), [
            'name'              => 'test category code',
            'content'           => 'test category code',
            'itilcategories_id' => $category->getID(),
        ]);
        $itil_id = $itil->getID();

        // Check that the rule was executed
        $this->boolean($itil->getFromDB($itil_id))->isTrue();
        $this->integer($itil->fields['impact'])->isEqualTo($rule_action_impact_value);

        // Create another ITIL Object that doesn't match the rule
        $itil = $this->createItem($this->getITILObjectClass(), [
            'name'              => 'test category code',
            'content'           => 'test category code',
            'itilcategories_id' => 0
        ]);
        $itil_id = $itil->getID();

        // Check that the rule was NOT executed
        $this->boolean($itil->getFromDB($itil_id))->isTrue();
        $this->integer($itil->fields['impact'])->isNotEqualTo($rule_action_impact_value);

        // Update ticket to match the rule
        $this->updateItem($this->getITILObjectClass(), $itil_id, [
            'itilcategories_id' => $category->getID(),
        ]);

        // Check that the rule was executed
        $this->boolean($itil->getFromDB($itil_id))->isTrue();
        $this->integer($itil->fields['impact'])->isEqualTo($rule_action_impact_value);

        // Change impact, the rule must not be executed again as the category didn't change
        $this->updateItem($this->getITILObjectClass(), $itil_id, [
            'itilcategories_id' => $category->getID(), // Simulate same category being sent from the user form
            'impact' => 2,
        ]);

        // Check that the rule was NOT executed
        $this->boolean($itil->getFromDB($itil_id))->isTrue();
        $this->integer($itil->fields['impact'])->isNotEqualTo($rule_action_impact_value);
    }

    public function testAssignAppliance()
    {
        $this->login();

        //create appliance "appliance"
        $applianceTest1 = new \Appliance();
        $appliancetest1_id = $applianceTest1->add($applianceTest1_input = [
            "name"                  => "appliance",
            "is_helpdesk_visible"   => true
        ]);
        $this->checkInput($applianceTest1, $appliancetest1_id, $applianceTest1_input);

        //add appliance to ITIL Object type
        $CFG_GLPI["ticket_types"][] = \Appliance::getType();

        // Add rule for create / update trigger (and assign action)
        $rule_itil = $this->getRuleInstance();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $rule_itil->add($ruletinput = [
            'name'         => 'test associated element : appliance',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => $this->getTestedClass(),
            'condition'    => \RuleCommonITILObject::ONUPDATE + \RuleCommonITILObject::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($rule_itil, $ruletid, $ruletinput);

        // Create criteria to check if content contain key word
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'content',
            'condition' => \Rule::PATTERN_CONTAIN,
            'pattern'   => 'appliance',
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        // Create action to add appliance
        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'assign_appliance',
            'value'       => $appliancetest1_id,
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

        //create ITIL Object to match rule on create
        $itilCreate = $this->getITILObjectInstance();
        $itil_fk = $itilCreate::getForeignKeyField();
        $itil_item_table = $this->getITILLinkClass('Item')::getTable();
        $itilCreate_id = $itilCreate->add($itilCreate_input = [
            'name'              => 'test appliance',
            'content'           => 'test appliance'
        ]);
        $this->checkInput($itilCreate, $itilCreate_id, $itilCreate_input);

        //check for one associated element
        $this->integer(countElementsInTable(
            $itil_item_table,
            ['itemtype'  =>  \Appliance::getType(),
                'items_id'   => $appliancetest1_id,
                $itil_fk => $itilCreate_id
            ]
        ))->isEqualTo(1);

        //create ITIL Object to match rule on update
        $itilUpdate = $this->getITILObjectInstance();
        $itilUpdate_id = $itilUpdate->add($itilUpdate_input = [
            'name'              => 'test',
            'content'           => 'test'
        ]);
        $this->checkInput($itilUpdate, $itilUpdate_id, $itilUpdate_input);

        //no appliance associated
        $this->integer(countElementsInTable(
            $itil_item_table,
            ['itemtype'  =>  \Appliance::getType(),
                'items_id'   => $appliancetest1_id,
                $itil_fk => $itilUpdate_id
            ]
        ))->isEqualTo(0);

        //update ITIL Object content to match rule
        $itilUpdate->update(
            [
                'id'      => $itilUpdate_id,
                'name'    => 'test erp',
                'content' => 'appliance'
            ]
        );

        //check for one associated element
        $this->integer(countElementsInTable(
            $itil_item_table,
            ['itemtype'  =>  \Appliance::getType(),
                'items_id'   => $appliancetest1_id,
                $itil_fk => $itilUpdate_id
            ]
        ))->isEqualTo(1);
    }

    public function testRegexAppliance()
    {
        $this->login();

        //create appliance "erp"
        $applianceTest1 = new \Appliance();
        $appliancetest1_id = $applianceTest1->add($applianceTest1_input = [
            "name"                  => "erp",
            "is_helpdesk_visible"   => true
        ]);
        $this->checkInput($applianceTest1, $appliancetest1_id, $applianceTest1_input);

        // Create rule for create / update trigger (and regex action)
        $rule_itil = $this->getRuleInstance();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $rule_itil->add($ruletinput = [
            'name'         => 'test associated element with regex : erp',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => $this->getTestedClass(),
            'condition'    => \RuleCommonITILObject::ONUPDATE + \RuleCommonITILObject::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($rule_itil, $ruletid, $ruletinput);

        // Create criteria to match regex
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'name',
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => '/(erp)/',
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        // Create action to add appliance
        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'regex_result',
            'field'       => 'assign_appliance',
            'value'       => '#0',
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

        //create ITIL Object to match rule on create
        $itilCreate = $this->getITILObjectInstance();
        $itil_fk = $itilCreate::getForeignKeyField();
        $itil_item_table = $this->getITILLinkClass('Item')::getTable();
        $itilCreate_id = $itilCreate->add($itilCreate_input = [
            'name'              => 'test erp',
            'content'           => 'test erp'
        ]);

        $this->checkInput($itilCreate, $itilCreate_id, $itilCreate_input);
        $this->integer($itilCreate_id)->isGreaterThan(0);

        //check for one associated element
        $this->integer(countElementsInTable(
            $itil_item_table,
            ['itemtype'  =>  \Appliance::getType(),
                'items_id'   => $appliancetest1_id,
                $itil_fk => $itilCreate_id
            ]
        ))->isEqualTo(1);

        //create ITIL Object to match rule on update
        $itilUpdate = $this->getITILObjectInstance();
        $itilUpdate_id = $itilUpdate->add($itilUpdate_input = [
            'name'              => 'test',
            'content'           => 'test'
        ]);
        $this->checkInput($itilUpdate, $itilUpdate_id, $itilUpdate_input);

        //no appliance associated
        $this->integer(countElementsInTable(
            $itil_item_table,
            ['itemtype'  =>  \Appliance::getType(),
                'items_id'   => $appliancetest1_id,
                $itil_fk => $itilUpdate_id
            ]
        ))->isEqualTo(0);

        //update ITIL Object content to match rule
        $itilUpdate->update(
            [
                'id'      => $itilUpdate_id,
                'name' => 'erp'
            ]
        );

        //check for one associated element
        $this->integer(countElementsInTable(
            $itil_item_table,
            ['itemtype'  =>  \Appliance::getType(),
                'items_id'   => $appliancetest1_id,
                $itil_fk => $itilUpdate_id
            ]
        ))->isEqualTo(1);
    }

    public function testAppendAppliance()
    {
        $this->login();

        //create appliance "erp"
        $applianceTest1 = new \Appliance();
        $appliancetest1_id = $applianceTest1->add($applianceTest1_input = [
            "name"                  => "erp",
            "is_helpdesk_visible"   => true
        ]);
        $this->checkInput($applianceTest1, $appliancetest1_id, $applianceTest1_input);

        //create appliance "glpi"
        $applianceTest2 = new \Appliance();
        $appliancetest2_id = $applianceTest2->add($applianceTest2_input = [
            "name"                  => "glpi",
            "is_helpdesk_visible"   => true
        ]);
        $this->checkInput($applianceTest2, $appliancetest2_id, $applianceTest2_input);

        // Create rule for create / update trigger (and regex action)
        $rule_itil = $this->getRuleInstance();
        $itil_fk = $this->getITILObjectClass()::getForeignKeyField();
        $itil_item_table = $this->getITILLinkClass('Item')::getTable();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $rule_itil->add($ruletinput = [
            'name'         => 'test associated element with  : erp',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => $this->getTestedClass(),
            'condition'    => \RuleCommonITILObject::ONUPDATE + \RuleCommonITILObject::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($rule_itil, $ruletid, $ruletinput);

        // Create criteria to match regex
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'name',
            'condition' => \Rule::PATTERN_CONTAIN,
            'pattern'   => 'erp',
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        // Create action to add appliance1
        $action_id1 = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'append',
            'field'       => 'assign_appliance',
            'value'       => $appliancetest1_id,
        ]);
        $this->checkInput($ruleaction, $action_id1, $action_input);

        //Create action to add appliance2
        $action_id2 = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'append',
            'field'       => 'assign_appliance',
            'value'       => $appliancetest2_id,
        ]);
        $this->checkInput($ruleaction, $action_id2, $action_input);

        //create ITIL Object to match rule on create
        $itilCreate = $this->getITILObjectInstance();
        $itilCreate_id = $itilCreate->add($itilCreate_input = [
            'name'              => 'test erp',
            'content'           => 'test erp'
        ]);

        $this->checkInput($itilCreate, $itilCreate_id, $itilCreate_input);
        $this->integer($itilCreate_id)->isGreaterThan(0);

        //check for one associated element
        $this->integer(countElementsInTable(
            $itil_item_table,
            [
                'itemtype'  =>  \Appliance::getType(),
                $itil_fk => $itilCreate_id
            ]
        ))->isEqualTo(2);

        //create ITIL Object to match rule on update
        $itilUpdate = $this->getITILObjectInstance();
        $itilUpdate_id = $itilUpdate->add($itilUpdate_input = [
            'name'              => 'test',
            'content'           => 'test'
        ]);
        $this->checkInput($itilUpdate, $itilUpdate_id, $itilUpdate_input);

        //no appliance associated
        $this->integer(countElementsInTable(
            $itil_item_table,
            [
                'itemtype'  =>  \Appliance::getType(),
                'items_id'   => $appliancetest1_id,
                $itil_fk => $itilUpdate_id
            ]
        ))->isEqualTo(0);

        //update ITIL Object content to match rule
        $itilUpdate->update(
            [
                'id'      => $itilUpdate_id,
                'name' => 'test erp'
            ]
        );

        //check for one associated element
        $this->integer(countElementsInTable(
            $itil_item_table,
            [
                'itemtype'  =>  \Appliance::getType(),
                $itil_fk => $itilUpdate_id
            ]
        ))->isEqualTo(2);
    }

    public function testStopProcessingAction()
    {
        $this->login();

        // Create rule
        $rule_itil = $this->getRuleInstance();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid_1 = $rule_itil->add($ruletinput = [
            'name'         => 'stopProcessingAction_1',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => $this->getTestedClass(),
            'condition'    => \RuleCommonITILObject::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($rule_itil, $ruletid_1, $ruletinput);

        $ruletid_2 = $rule_itil->add($ruletinput = [
            'name'         => 'stopProcessingAction_2',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => $this->getTestedClass(),
            'condition'    => \RuleCommonITILObject::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($rule_itil, $ruletid_2, $ruletinput);

        $ruletid_3 = $rule_itil->add($ruletinput = [
            'name'         => 'stopProcessingAction_3',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => $this->getTestedClass(),
            'condition'    => \RuleCommonITILObject::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($rule_itil, $ruletid_3, $ruletinput);

        foreach ([$ruletid_1, $ruletid_2, $ruletid_3] as $ruletid) {
            $crit_id = $rulecrit->add($crit_input = [
                'rules_id'  => $ruletid,
                'criteria'  => 'name',
                'condition' => \Rule::PATTERN_IS,
                'pattern'   => 'stopProcessingAction',
            ]);
            $this->checkInput($rulecrit, $crit_id, $crit_input);
        }

        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid_1,
            'action_type' => 'assign',
            'field'       => 'impact',
            'value'       => 1,
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid_2,
            'action_type' => 'assign',
            'field'       => 'impact',
            'value'       => 2,
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);
        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid_2,
            'action_type' => 'assign',
            'field'       => '_stop_rules_processing',
            'value'       => 1,
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid_3,
            'action_type' => 'assign',
            'field'       => 'impact',
            'value'       => 3,
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

        // Check ITIL Object that trigger rule on creation
        $itil = $this->getITILObjectInstance();
        $itil_id = $itil->add($itil_input = [
            'name'              => 'stopProcessingAction',
            'content'           => 'test stopProcessingAction'
        ]);
        $this->checkInput($itil, $itil_id, $itil_input);

        // Check that the rule was executed
        $this->boolean($itil->getFromDB($itil_id))->isTrue();
        $this->integer($itil->fields['impact'])->isEqualTo(2);
    }

    /**
     * Data provider for testAction
     * @see $this->testAction() for details of the expected parameters
     *
     * @return Generator
     */
    protected function testActionProvider(): Generator
    {

        // Test 'regex_result' action on the ticket category completename
        $root_category = $this->createItem(ITILCategory::getType(), [
            'name' => 'Category root'
        ]);

        // Test 'regex_result' action on the ticket category
        $sub_root_category = $this->createItem(ITILCategory::getType(), [
            'name' => 'Category sub',
            'itilcategories_id' => $root_category->fields['id']
        ]);

        yield [
            'criteria' => [
                'condition' => Rule::REGEX_MATCH,
                'field'     => 'name',
                'pattern'   => '/(.*)/',
            ],
            'action' => [
                'action_type' => 'regex_result',
                'field'       => '_itilcategories_id_by_completename',
                'field_specific' => function ($ticket) {
                    // Can't read '_itilcategories_id_by_completename' from group field, need
                    // to fetch it from the Ticket table
                    return $ticket->fields['itilcategories_id'];
                },
                'value'       => '#0'
            ],
            'control_test_value' => 'Test_title_no_match',
            'real_test_value'    => 'Category root > Category sub',
            'expected_value'     => $sub_root_category->fields['id'],
        ];

        // Test 'regex_result' action on the ticket category
        $category = $this->createItem(ITILCategory::getType(), [
            'name' => 'Category from regex'
        ]);
        yield [
            'criteria' => [
                'condition' => Rule::REGEX_MATCH,
                'field'     => 'name',
                'pattern'   => '/(.*)/',
            ],
            'action' => [
                'action_type' => 'regex_result',
                'field'       => 'itilcategories_id',
                'value'       => '#0'
            ],
            'control_test_value' => 'Test_title_no_match',
            'real_test_value'    => 'Category from regex',
            'expected_value'     => $category->fields['id'],
        ];

        // Test 'regex_result' action on the ticket requester group by completename
        $root_requester_group_completename = $this->createItem(Group::getType(), [
            'name' => 'Requester group root'
        ]);

        $sub_requester_group_completename = $this->createItem(Group::getType(), [
            'name' => 'Requester group sub',
            'groups_id' => $root_requester_group_completename->fields['id'],
        ]);

        yield [
            'criteria' => [
                'condition' => Rule::REGEX_MATCH,
                'field'     => 'name',
                'pattern'   => '/(.*)/',
            ],
            'action' => [
                'action_type'    => 'regex_result',
                'field'          => '_groups_id_requester_by_completename',
                'value'          => '#0',
                'field_specific' => function ($ticket) {
                    // Can't read '_groups_id_requester' from group field, need
                    // to fetch it from the Group_Ticket table
                    $groups = (new Group_Ticket())->find([
                        'type' => CommonITILActor::REQUESTER,
                        'tickets_id' => $ticket->fields['id'],
                    ]);
                    if (count($groups) == 1) {
                        return array_pop($groups)['groups_id'];
                    } else {
                        return 0;
                    }
                }
            ],
            'control_test_value' => 'Test_title_no_match',
            'real_test_value'    => 'Requester group root > Requester group sub',
            'expected_value'     => $sub_requester_group_completename->fields['id'],
        ];

        // Test 'regex_result' action on the ticket requester group
        $requester_group = $this->createItem(Group::getType(), [
            'name' => 'Requester group from regex'
        ]);
        yield [
            'criteria' => [
                'condition' => Rule::REGEX_MATCH,
                'field'     => 'name',
                'pattern'   => '/(.*)/',
            ],
            'action' => [
                'action_type'    => 'regex_result',
                'field'          => '_groups_id_requester',
                'value'          => '#0',
                'field_specific' => function ($ticket) {
                    // Can't read '_groups_id_requester' from group field, need
                    // to fetch it from the Group_Ticket table
                    $groups = (new Group_Ticket())->find([
                        'type' => CommonITILActor::REQUESTER,
                        'tickets_id' => $ticket->fields['id'],
                    ]);
                    if (count($groups) == 1) {
                        return array_pop($groups)['groups_id'];
                    } else {
                        return 0;
                    }
                }
            ],
            'control_test_value' => 'Test_title_no_match',
            'real_test_value'    => 'Requester group from regex',
            'expected_value'     => $requester_group->fields['id'],
        ];


        // Test 'regex_result' action on the ticket observer group by completename
        $root_observer_group_completename = $this->createItem(Group::getType(), [
            'name' => 'Observer group root'
        ]);

        $sub_observer_group_completename = $this->createItem(Group::getType(), [
            'name' => 'Observer group sub',
            'groups_id' => $root_observer_group_completename->fields['id'],
        ]);

        yield [
            'criteria' => [
                'condition' => Rule::REGEX_MATCH,
                'field'     => 'name',
                'pattern'   => '/(.*)/',
            ],
            'action' => [
                'action_type'    => 'regex_result',
                'field'          => '_groups_id_observer_by_completename',
                'value'          => '#0',
                'field_specific' => function ($ticket) {
                    // Can't read '_groups_id_observer' from group field, need
                    // to fetch it from the Group_Ticket table
                    $groups = (new Group_Ticket())->find([
                        'type' => CommonITILActor::OBSERVER,
                        'tickets_id' => $ticket->fields['id'],
                    ]);
                    if (count($groups) == 1) {
                        return array_pop($groups)['groups_id'];
                    } else {
                        return 0;
                    }
                }
            ],
            'control_test_value' => 'Test_title_no_match',
            'real_test_value'    => 'Observer group root > Observer group sub',
            'expected_value'     => $sub_observer_group_completename->fields['id'],
        ];


        // Test 'regex_result' action on the ticket observer group
        $observer_group = $this->createItem(Group::getType(), [
            'name' => 'Observer group from regex'
        ]);
        yield [
            'criteria' => [
                'condition' => Rule::REGEX_MATCH,
                'field'     => 'name',
                'pattern'   => '/(.*)/',
            ],
            'action' => [
                'action_type'    => 'regex_result',
                'field'          => '_groups_id_observer',
                'value'          => '#0',
                'field_specific' => function ($ticket) {
                    // Can't read '_groups_id_requester' from group field, need
                    // to fetch it from the Group_Ticket table
                    $groups = (new Group_Ticket())->find([
                        'type' => CommonITILActor::OBSERVER,
                        'tickets_id' => $ticket->fields['id'],
                    ]);
                    if (count($groups) == 1) {
                        return array_pop($groups)['groups_id'];
                    } else {
                        return 0;
                    }
                }
            ],
            'control_test_value' => 'Test_title_no_match',
            'real_test_value'    => 'Observer group from regex',
            'expected_value'     => $observer_group->fields['id'],
        ];

        // Test 'regex_result' action on the ticket observer group by completename
        $root_assign_group_completename = $this->createItem(Group::getType(), [
            'name' => 'Assign group root'
        ]);

        $sub_assign_group_completename = $this->createItem(Group::getType(), [
            'name' => 'Assign group sub',
            'groups_id' => $root_assign_group_completename->fields['id'],
        ]);

        yield [
            'criteria' => [
                'condition' => Rule::REGEX_MATCH,
                'field'     => 'name',
                'pattern'   => '/(.*)/',
            ],
            'action' => [
                'action_type'    => 'regex_result',
                'field'          => '_groups_id_assign_by_completename',
                'value'          => '#0',
                'field_specific' => function ($ticket) {
                    // Can't read '_groups_id_assign' from group field, need
                    // to fetch it from the Group_Ticket table
                    $groups = (new Group_Ticket())->find([
                        'type' => CommonITILActor::ASSIGN,
                        'tickets_id' => $ticket->fields['id'],
                    ]);
                    if (count($groups) == 1) {
                        return array_pop($groups)['groups_id'];
                    } else {
                        return 0;
                    }
                }
            ],
            'control_test_value' => 'Test_title_no_match',
            'real_test_value'    => 'Assign group root > Assign group sub',
            'expected_value'     => $sub_assign_group_completename->fields['id'],
        ];

        // Test 'regex_result' action on the ticket assigned group
        $tech_group = $this->createItem(Group::getType(), [
            'name' => 'Tech group from regex'
        ]);
        yield [
            'criteria' => [
                'condition' => Rule::REGEX_MATCH,
                'field'     => 'name',
                'pattern'   => '/(.*)/',
            ],
            'action' => [
                'action_type'    => 'regex_result',
                'field'          => '_groups_id_assign',
                'value'          => '#0',
                'field_specific' => function ($ticket) {
                    // Can't read '_groups_id_requester' from group field, need
                    // to fetch it from the Group_Ticket table
                    $groups = (new Group_Ticket())->find([
                        'type' => CommonITILActor::ASSIGN,
                        'tickets_id' => $ticket->fields['id'],
                    ]);
                    if (count($groups) == 1) {
                        return array_pop($groups)['groups_id'];
                    } else {
                        return 0;
                    }
                }
            ],
            'control_test_value' => 'Test_title_no_match',
            'real_test_value'    => 'Tech group from regex',
            'expected_value'     => $tech_group->fields['id'],
        ];
    }

    /**
     * Function used by $this->testAction(), get the result of a test:
     * - If the action field is a "simple field" part of the ticket table like
     *  the category or the description then we can read its value from $item
     * - If the action field is more complex like an assigned group or user, we
     *  will run a specific function supplied in $action that will fetch the
     *  value from the database
     *
     * @param array  $action Action details, supplied by the data provider
     * @param Ticket $item   Test subject
     */
    protected function testActionGetTestResultValue(array $action, Ticket $item)
    {
        if (isset($action['field_specific'])) {
            $value = $action['field_specific']($item);
        } else {
            $value = $item->fields[$action['field']];
        }

        return $value;
    }

    /**
     * Test a given ticket rule
     *
     * @dataprovider testActionProvider
     *
     * @param array  $criteria           Details of the rule criteria:
     *                                    - condition
     *                                    - field
     *                                    - pattern
     * @param array  $action             Details of the rule action:
     *                                    - action_type
     *                                    - field
     *                                    - value
     *                                    - field_specific (optionnal, will
     *                                     contain a callback to handle fields
     *                                     that are no part of the ticket table,
     *                                     like assigned groups for exemple)
     * @param string $control_test_value A control value for the criteria.
     *                                   This value is not expected to trigger
     *                                   the rule
     * @param string $real_test_value    The test value value for the criteria.
     *                                   This value is expected to trigger the
     *                                   rule
     * @param string $expected_value     Expected value if the test succeed.
     *
     * @return void
     */
    public function testAction(
        array $criteria,
        array $action,
        string $control_test_value,
        string $real_test_value,
        string $expected_value
    ): void {
        global $DB;

        $this->login();

        // Disable all others rules before running the test
        $DB->update(Rule::getTable(), ['is_active' => false], [
            'sub_type' => "RuleAsset"
        ]);
        $active_rules = countElementsInTable(Rule::getTable(), [
            'is_active' => true,
            'sub_type'  => "RuleAsset",
        ]);
        $this->integer($active_rules)->isEqualTo(0);

        // Create the rule
        $rule_ticket = $this->createItem(\RuleTicket::getType(), [
            'name'      => __FUNCTION__,
            'match'     => 'AND',
            'is_active' => true,
            'sub_type'  => 'RuleTicket',
            'condition' => \RuleTicket::ONADD | \RuleTicket::ONUPDATE,
        ]);

        // Add the condition
        $this->createItem(RuleCriteria::getType(), [
            'rules_id'  => $rule_ticket->getID(),
            'criteria'  => $criteria['field'],
            'condition' => $criteria['condition'],
            'pattern'   => $criteria['pattern'],
        ]);

        // Add the action
        $this->createItem(RuleAction::getType(), [
            'rules_id'    => $rule_ticket->getID(),
            'action_type' => $action['action_type'],
            'field'       => $action['field'],
            'value'       => $action['value'],
        ]);

        // Reset rule cache
        SingletonRuleList::getInstance("RuleTicket", 0)->load = 0;
        SingletonRuleList::getInstance("RuleTicket", 0)->list = [];

        // First, test the rule on item creation
        // We will create two items, the first one should NOT trigger the rule
        // (control test) and the second should trigger the rule.

        // Create the control test subject
        $control_item = $this->createItem(Ticket::getType(), [
            'name' => $control_test_value,
            'content' => 'testAction',
        ]);

        // Verify that the test subject didn't trigger the rule
        $this->variable($this->testActionGetTestResultValue(
            $action,
            $control_item
        ))->isNotEqualTo($expected_value);

        // Create the real test subject
        $real_item = $this->createItem(Ticket::getType(), [
            'name' => $real_test_value,
            'content' => 'testAction',
        ]);

        // Verify that the test subject did trigger the rule
        $this->variable($this->testActionGetTestResultValue(
            $action,
            $real_item
        ))->isEqualTo($expected_value);

        // Second step, test the rule on item update
        // We will create the item with the control test value, expecting it to
        // not match the rule, then update it to the real value

        // Create the test subject
        $item = $this->createItem(Ticket::getType(), [
            'name'    => $control_test_value,
            'content' => 'testAction',
        ]);

        // Verify that the test subject didn't trigger the rule
        $this->variable($this->testActionGetTestResultValue(
            $action,
            $item
        ))->isNotEqualTo($expected_value);

        // Updatea the test subject to the value expected by the rule
        $this->updateItem(Ticket::getType(), $item->fields['id'], [
            'name' => $real_test_value,
        ]);
        $item->getFromDb($item->fields['id']);

        // Verify that the test subject did trigger the rule
        $this->variable($this->testActionGetTestResultValue(
            $action,
            $item
        ))->isEqualTo($expected_value);
    }

    /**
     * Ensure a rule using the "global_validation" criteria work as expected on updates.
     *
     * @return void
     */
    public function testGlobalValidationCriteria(): void
    {
        $itil_object = $this->getITILObjectInstance();

        if (!$itil_object->isField('global_validation')) {
            // Nothing to check if field not exists.
            $this->boolean(true)->isTrue();
            return;
        }

        $this->login(TU_USER, TU_PASS);

        $entity = getItemByTypeName(Entity::class, '_test_root_entity', true);
        $urgency_if_rule_triggered = 5;

        // Test category that will be used as a secondary rule criteria
        $category1 = $this->createItem(ITILCategory::class, [
            'name'         => 'Test category 1',
            'entities_id'  => $entity,
            'is_recursive' => true,
        ]);
        $category2 = $this->createItem(ITILCategory::class, [
            'name'         => 'Test category 2',
            'entities_id'  => $entity,
            'is_recursive' => true,
        ]);

        $builder = new RuleBuilder('Test global_validation criteria rule');
        $builder
            ->addCriteria('global_validation', Rule::PATTERN_IS, CommonITILValidation::WAITING)
            ->addCriteria('itilcategories_id', Rule::PATTERN_IS, $category1->getID())
            ->addAction('assign', 'urgency', $urgency_if_rule_triggered);
        $this->createRule($builder, $this->getTestedClass());

        // Create ITIL object with validation request
        $itil_object = $this->createItem($this->getITILObjectClass(), [
            'name'              => 'Test ITIL object',
            'entities_id'       => $entity,
            'content'           => 'Test ITIL object content',
            'validatortype'     => 'user',
            '_validation_targets' => [
                [
                    'itemtype_target' => User::class,
                    'items_id_target' => getItemByTypeName(User::class, 'glpi', true)
                ],
            ],
            '_add_validation'   => false,
        ], ['validatortype']);
        $this->integer($itil_object->fields['urgency'])->isNotEqualTo($urgency_if_rule_triggered);
        $this->integer($itil_object->fields['global_validation'])->isEqualTo(CommonITILValidation::WAITING);

        // Change category without triggering the rule
        $this->updateItem($this->getITILObjectClass(), $itil_object->getID(), [
            'itilcategories_id' => $category2->getID()
        ]);
        $itil_object->getFromDB($itil_object->getID());
        $this->integer($itil_object->fields['urgency'])->isNotEqualTo($urgency_if_rule_triggered);

        // Change category and trigger the rule
        $this->updateItem($this->getITILObjectClass(), $itil_object->getID(), [
            'itilcategories_id' => $category1->getID()
        ]);
        $itil_object->getFromDB($itil_object->getID());
        $this->integer($itil_object->fields['urgency'])->isEqualTo($urgency_if_rule_triggered);
    }

    /**
     * Test that the "Code representing the ticket category" criterion works correctly
     * even when the category has been modified just before.
     *
     * @return void
     */
    public function testCategoryCodeCriterionAfterCategoryModification(): void
    {
        // Get the root entity
        $entity = getItemByTypeName(Entity::class, '_test_root_entity', true);

        // Create a category
        $category = $this->createItem(ITILCategory::class, [
            'name' => 'Test category',
            'code' => 'test_category',
            'entities_id' => $entity,
        ]);

        // Create a location
        $location = $this->createItem(Location::class, [
            'name' => 'Test location',
            'entities_id' => $entity,
        ]);

        // Create two rules
        $builder = new RuleBuilder('Test category code criterion rule');
        $builder
            ->addCriteria('urgency', Rule::PATTERN_IS, 5)
            ->addAction('assign', 'itilcategories_id', $category->getID());
        $this->createRule($builder, $this->getTestedClass());

        $builder
            ->addCriteria('itilcategories_id_code', Rule::PATTERN_IS, $category->fields['code'])
            ->addAction('assign', 'locations_id', $location->getID());
        $this->createRule($builder, $this->getTestedClass());

        // Create a itil object with "Very high" urgency
        $itil_object = $this->createItem($this->getITILObjectClass(), [
            'name' => 'Test ITIL',
            'content' => 'Test ITIL content',
            'urgency' => 5, // Assuming 5 is "Very high"
            'entities_id' => $entity,
        ]);

        // Check if the category "Test category" is assigned
        $this->integer($itil_object->fields['itilcategories_id'])->isEqualTo($category->getID());

        // Check if the location "Test location" is assigned
        $this->integer($itil_object->fields['locations_id'])->isEqualTo($location->getID());
    }


    /**
     * Test that the "Default profile" criterion works correctly
     * @return void
     */
    public function testDefaultProfileCriterion(): void
    {
        // Get the root entity
        $entity = getItemByTypeName(Entity::class, '_test_root_entity', true);

        // Create a location
        $location = $this->createItem(Location::class, [
            'name' => 'Test location',
            'entities_id' => $entity,
        ]);

        // Create another location
        $location2 = $this->createItem(Location::class, [
            'name' => 'Other Test location',
            'entities_id' => $entity,
        ]);

        // Create two rules
        $builder = new RuleBuilder('Test default profile criterion rule');
        $builder
            ->addCriteria('profiles_id', Rule::PATTERN_IS, 4)
            ->addAction('assign', 'locations_id', $location->getID());
        $this->createRule($builder, $this->getTestedClass());


        // Create two rules
        $builder = new RuleBuilder('Test default profile criterion rule on update');
        $builder
            ->addCriteria('profiles_id', Rule::PATTERN_IS, 0)
            ->addAction('assign', 'locations_id', $location2->getID());
        $this->createRule($builder, $this->getTestedClass());

        //Load user jsmith123
        $user = new \User();
        $user->getFromDB(getItemByTypeName('User', 'jsmith123', true));

        // Create an ITIL object with "Very high" urgency
        $itil_object = $this->createItem($this->getITILObjectClass(), [
            'name' => 'Test',
            'content' => 'Test content',
            'entities_id' => $entity,
            '_users_id_requester' => $user->fields['id']
        ]);

        // Check if the location "Test location" is assigned
        $this->integer($itil_object->fields['locations_id'])->isEqualTo($location->getID());

        $this->login('tech', 'tech');

        //remove requester
        $itil_user = $this->getITILLinkInstance('User');
        $itil_fk = $this->getITILObjectClass()::getForeignKeyField();
        $this->boolean($itil_user->deleteByCriteria([
            "type" => \CommonITILActor::REQUESTER,
            "users_id" => $user->fields['id'],
            $itil_fk => $itil_object->getID(),
        ]))->isTrue();

        //reload ITIL object
        $this->boolean($itil_object->getFromDB($itil_object->getID()))->isTrue();

        //Load user tech
        $user = new \User();
        $user->getFromDB(getItemByTypeName('User', 'tech', true));

        // update ITIL object to update requester
        $this->boolean($itil_object->update([
            'name'                  => 'Test update',
            'id'                    => $itil_object->fields['id'],
            'content'               => 'test',
            '_itil_requester'   => [
                "_type" => "user",
                "users_id" => $user->fields['id']
            ]
        ]))->isTrue();

        // Check if the location "Test location" is assigned
        $this->integer($itil_object->fields['locations_id'])->isEqualTo($location2->getID());
    }
}
