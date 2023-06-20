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

use CommonITILValidation;
use Contract;
use ContractType;
use DbTestCase;
use Glpi\Toolbox\Sanitizer;
use Group_User;
use ITILFollowup;
use ITILFollowupTemplate;
use RuleAction;
use RuleCriteria;
use TaskTemplate;
use Ticket_Contract;
use TicketTask;
use Toolbox;

/* Test for inc/ruleticket.class.php */

class RuleTicket extends DbTestCase
{
    public function testGetCriteria()
    {
        $rule = new \RuleTicket();
        $criteria = $rule->getCriterias();
        $this->array($criteria)->size->isGreaterThan(20);
    }

    public function testGetActions()
    {
        $rule = new \RuleTicket();
        $actions  = $rule->getActions();
        $this->array($actions)->size->isGreaterThan(20);
    }

    public function testDefaultRuleExists()
    {
        $this->integer(
            (int)countElementsInTable(
                'glpi_rules',
                [
                    'name' => 'Ticket location from item',
                    'is_active' => 0
                ]
            )
        )->isIdenticalTo(1);
        $this->integer(
            (int)countElementsInTable(
                'glpi_rules',
                [
                    'name' => 'Ticket location from use',
                    'is_active' => 1
                ]
            )
        )->isIdenticalTo(0);
    }

    public function testTriggerAdd()
    {
        $this->login();

       // prepare rule
        $this->createTestTriggerRule(\RuleTicket::ONADD);

       // test create ticket (trigger on title)
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'    => "test ticket, will trigger on rule (title)",
            'content' => "test"
        ]);
        $this->checkInput($ticket, $tickets_id, $ticket_input);
        $this->integer((int)$ticket->getField('urgency'))->isEqualTo(5);

       // test create ticket (trigger on user assign)
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'             => "test ticket, will trigger on rule (user)",
            'content'          => "test",
            '_users_id_assign' => getItemByTypeName('User', "tech", true)
        ]);
       // _users_id_assign is stored in glpi_tickets_users table, so remove it
        unset($ticket_input['_users_id_assign']);
        $this->checkInput($ticket, $tickets_id, $ticket_input);
        $this->integer((int)$ticket->getField('urgency'))->isEqualTo(5);
    }

    public function testTriggerUpdate()
    {
        $this->login();
        $this->setEntity('Root entity', true);

        $users_id = (int) getItemByTypeName('User', 'tech', true);

       // prepare rule
        $this->createTestTriggerRule(\RuleTicket::ONUPDATE);

       // test create ticket (for check triggering on title after update)
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'    => "test ticket, will not trigger on rule",
            'content' => "test"
        ]);
        $this->checkInput($ticket, $tickets_id, $ticket_input);
        $this->integer((int)$ticket->getField('urgency'))->isEqualTo(3);

       // update ticket title and trigger rule on title updating
        $ticket->update([
            'id'   => $tickets_id,
            'name' => 'test ticket, will trigger on rule (title)'
        ]);
        $ticket->getFromDB($tickets_id);
        $this->integer((int)$ticket->getField('urgency'))->isEqualTo(5);

       // test create ticket (for check triggering on actor after update)
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'    => "test ticket, will not trigger on rule (actor)",
            'content' => "test"
        ]);
        $this->checkInput($ticket, $tickets_id, $ticket_input);
        $this->integer((int)$ticket->getField('urgency'))->isEqualTo(3);

       // update ticket title and trigger rule on actor addition
        $ticket->update([
            'id'           => $tickets_id,
            'content'      => "updated",
            '_lgd'         => true,
            '_itil_assign' => [
                '_type'    => 'user',
                'users_id' => $users_id
            ]
        ]);
        $ticket->getFromDB($tickets_id);
        $ticket_user = new \Ticket_User();
        $actors = $ticket_user->getActors($tickets_id);
        $this->integer((int)$actors[2][0]['users_id'])->isEqualTo($users_id);
        $this->integer((int)$ticket->getField('urgency'))->isEqualTo(5);
    }

    private function createTestTriggerRule($condition)
    {
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => "test rule add",
            'match'        => 'OR',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => $condition,
            'is_recursive' => 1
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);
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
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'test status criterion',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'status',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => \Ticket::INCOMING,
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
            'value'       => \Ticket::WAITING,
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);

       // Check ticket that trigger rule on creation
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'             => 'change status to waiting if new and assigned to tech',
            'content'          => 'test',
            '_users_id_assign' => getItemByTypeName('User', 'tech', true)
        ]);
        unset($ticket_input['_users_id_assign']); // _users_id_assign is stored in glpi_tickets_users table, so remove it
        $this->checkInput($ticket, $tickets_id, $ticket_input);
        $this->integer((int)$ticket->getField('status'))->isEqualTo(\Ticket::WAITING);
    }

    /**
     * Test that new status setting by rules is not overrided when an actor is assigned at the same time.
     */
    public function testStatusAssignNewFromRule()
    {
        $this->login();

       // Create rule
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'test assign new actor and keep new status',
            'match'        => 'OR',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD | \RuleTicket::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

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
            'value'       => \Ticket::INCOMING,
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);

       // Check ticket that trigger rule on creation
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'    => 'assign to tech (on creation)',
            'content' => 'test'
        ]);
        $this->checkInput($ticket, $tickets_id, $ticket_input);
        $this->integer((int)$ticket->getField('status'))->isEqualTo(\Ticket::INCOMING);
        $this->integer(countElementsInTable(
            \Ticket_User::getTable(),
            ['tickets_id' => $tickets_id, 'type' => \CommonITILActor::ASSIGN]
        ))->isEqualTo(1);

       // Check ticket that trigger rule on update
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'             => 'some ticket',
            'content'          => 'test',
            '_users_id_assign' => getItemByTypeName('User', TU_USER, true),
        ]);
        unset($ticket_input['_users_id_assign']);
        $this->checkInput($ticket, $tickets_id, $ticket_input);
        $this->integer((int)$ticket->getField('status'))->isEqualTo(\Ticket::ASSIGNED);
        $this->integer(countElementsInTable(
            \Ticket_User::getTable(),
            ['tickets_id' => $tickets_id, 'type' => \CommonITILActor::ASSIGN]
        ))->isEqualTo(1); // Assigned to TU_USER

        $this->boolean($ticket->update([
            'id'               => $tickets_id,
            'name'             => 'assign to tech (on update)',
            'content'          => 'test',
            '_users_id_assign' => getItemByTypeName('User', 'glpi', true), // rule should erase this value
        ]))->isTrue();
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer((int)$ticket->getField('status'))->isEqualTo(\Ticket::INCOMING);
        $this->integer(countElementsInTable(
            \Ticket_User::getTable(),
            ['tickets_id' => $tickets_id, 'type' => \CommonITILActor::ASSIGN]
        ))->isEqualTo(2); // Assigned to TU_USER + tech
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
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'test to assign ITILCategory',
            'match'        => 'OR',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD | \RuleTicket::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

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

       // Check ticket that trigger rule on add
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'    => 'some ticket (on insert)',
            'content' => 'some text #itil_category_for_add# some text'
        ]);

        $this->checkInput($ticket, $tickets_id, $ticket_input);
        $this->integer((int)$ticket->getField('itilcategories_id'))->isEqualTo($ITILCategoryForAddId);

        $this->boolean($ticket->update($ticket_input = [
            'id'      => $tickets_id,
            'name'    => 'some ticket (on update)',
            'content' => 'some text #itil_category_for_update# some text'
        ]))->isTrue();

        $this->checkInput($ticket, $tickets_id, $ticket_input);
        $this->integer((int)$ticket->getField('itilcategories_id'))->isEqualTo($ITILCategoryForUpdateId);
    }

    public function testITILSolutionAssignFromRule()
    {
        $this->login();

       // Create solution template
        $solutionTemplate = new \SolutionTemplate();
        $solutionTemplate_id = $solutionTemplate->add($solutionInput = [
            'content' => Toolbox::addslashes_deep("<p>content of solution template  white ' quote</p>")
        ]);
        $this->integer((int)$solutionTemplate_id)->isGreaterThan(0);

       // Create rule
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => "test to assign ITILSolution",
            'match'        => 'OR',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

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

        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'    => 'some ticket',
            'content' => 'some text some text'
        ]);

        $this->checkInput($ticket, $tickets_id, $ticket_input);
        $this->integer((int)$tickets_id)->isGreaterThan(0);

       // update ticket content and trigger rule on content updating
        $ticket->update([
            'id'   => $tickets_id,
            'content' => 'test ticket, will trigger on rule (content)'
        ]);

       //load ITILSolution
        $itilSolution = new \ITILSolution();
        $this->boolean($itilSolution->getFromDBByCrit([
            'items_id' => $tickets_id,
            'itemtype' => 'Ticket',
            'content'  => Sanitizer::sanitize("<p>content of solution template  white ' quote</p>")
        ]))->isTrue();

        $this->integer((int)$itilSolution->getID())->isGreaterThan(0);

       //reload and check ticket status
        $ticket->getFromDB($tickets_id);
        $this->integer((int)$ticket->getField('status'))->isEqualTo(\CommonITILObject::SOLVED);
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
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'test add group on add',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

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
        $ruletid_assign = $ruleticket->add($ruletinput_assign = [
            'name'         => 'test assign group on add',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid_assign, $ruletinput_assign);

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

       // Create ticket
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'             => 'when assigning delete groups to add',
            'content'          => 'test',
        ]);
        $this->checkInput($ticket, $tickets_id, $ticket_input);

       //load TicketGroup1 (expected false)
        $ticketGroup = new \Group_Ticket();
        $this->boolean(
            $ticketGroup->getFromDBByCrit([
                'tickets_id'         => $tickets_id,
                'groups_id'          => $group_id1,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isFalse();

       //load TicketGroup2 (expected true)
        $ticketGroup = new \Group_Ticket();
        $this->boolean(
            $ticketGroup->getFromDBByCrit([
                'tickets_id'         => $tickets_id,
                'groups_id'          => $group_id2,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isTrue();
    }

    public function testGroupRequesterAssignFromDefaultUserOnCreate()
    {
        $this->login();

       // Create rule
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'test group requester criterion',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

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

       // Check ticket that trigger rule on creation
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'             => 'Add group requester if requester have default group',
            'content'          => 'test',
            '_users_id_requester' => $user->fields['id']
        ]);
        unset($ticket_input['_users_id_requester']); // _users_id_requester is stored in glpi_tickets_users table, so remove it
        $this->checkInput($ticket, $tickets_id, $ticket_input);

       //load TicketGroup
        $ticketGroup = new \Group_Ticket();
        $this->boolean(
            $ticketGroup->getFromDBByCrit([
                'tickets_id'         => $tickets_id,
                'groups_id'          => $group_id,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isTrue();
    }

    public function testGroupRequesterAssignFromDefaultUserAndLocationFromUserOnUpdate()
    {
        $this->login();

       // Create rule
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'test group requester from user on update',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

       //create criteria to check an update
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'content',
            'condition' => \Rule::PATTERN_EXISTS,
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

       //create action to put user location as ticket location
        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'fromuser',
            'field'       => 'locations_id',
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

       //create new location
        $location = new \Location();
        $location_id = $location->add($location_input = [
            "name" => "location1",
        ]);
        $this->checkInput($location, $location_id, $location_input);

       //add location to user
        $user->fields['locations_id'] = $location_id;
        $this->boolean($user->update($user->fields))->isTrue();

       // Create ticket
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'             => 'Add group requester if requester have default group',
            'content'          => 'test',
            '_users_id_requester' => $user->fields['id']
        ]);
        unset($ticket_input['_users_id_requester']); // _users_id_requester is stored in glpi_tickets_users table, so remove it
        $this->checkInput($ticket, $tickets_id, $ticket_input);

       //locations_id must be set to 0
        $this->integer($ticket->fields['locations_id'])->isIdenticalTo(0);

       //load TicketGroup (expected false)
        $ticketGroup = new \Group_Ticket();
        $this->boolean(
            $ticketGroup->getFromDBByCrit([
                'tickets_id'         => $tickets_id,
                'groups_id'          => $group_id,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isFalse();

       //Update ticket to trigger rule
        $ticket->update($ticket_input = [
            'id' => $tickets_id,
            'content' => 'test on update'
        ]);
        $this->checkInput($ticket, $tickets_id, $ticket_input);

       //load TicketGroup
        $ticketGroup = new \Group_Ticket();
        $this->boolean(
            $ticketGroup->getFromDBByCrit([
                'tickets_id'         => $tickets_id,
                'groups_id'          => $group_id,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isTrue();

       //locations_id must be set to
        $ticket->getFromDB($tickets_id);
        $this->integer($ticket->fields['locations_id'])->isIdenticalTo($location_id);
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

       // Create rule
        $rule_ticket_em = new \RuleTicket();
        $rule_ticket_id = $rule_ticket_em->add($ruletinput = [
            'name'         => "test to assign ITILSolution",
            'match'        => 'OR',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD + \RuleTicket::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->integer($rule_ticket_id)->isGreaterThan(0);

       // Add condition (priority = 5) to rule
        $rule_criteria_em = new RuleCriteria();
        $rule_criteria_id = $rule_criteria_em->add($crit_input = [
            'rules_id'  => $rule_ticket_id,
            'criteria'  => 'priority',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => 5,
        ]);
        $this->integer($rule_criteria_id)->isGreaterThan(0);

       // Add action to rule
        $rule_action_em = new RuleAction();
        $rule_action_id = $rule_action_em->add($act_input = [
            'rules_id'    => $rule_ticket_id,
            'action_type' => 'append',
            'field'       => 'task_template',
            'value'       => $task_template_id,
        ]);
        $this->integer($rule_action_id)->isGreaterThan(0);

       // Test on creation
        $ticket_em = new \Ticket();
        $ticket_id = $ticket_em->add([
            'name'     => 'test',
            'content'  => 'test',
            'priority' => 5,
        ]);
        $this->integer($ticket_id)->isGreaterThan(0);

        $ticket_task_em = new TicketTask();
        $ticket_tasks = $ticket_task_em->find([
            'tickets_id' => $ticket_id
        ]);

        $this->array($ticket_tasks)->hasSize(1);
        $task_data = array_pop($ticket_tasks);
        $this->array($task_data)->hasKey('content');
        $this->string($task_data['content'])->isEqualTo(Sanitizer::encodeHtmlSpecialChars('<p>test content</p>'));

       // Test on update
        $ticket_em = new \Ticket();
        $ticket_id = $ticket_em->add([
            'name'     => 'test',
            'content'  => 'test',
            'priority' => 4,
        ]);
        $this->integer($ticket_id)->isGreaterThan(0);

        $ticket_task_em = new TicketTask();
        $ticket_tasks = $ticket_task_em->find([
            'tickets_id' => $ticket_id
        ]);

        $this->array($ticket_tasks)->hasSize(0);

        $ticket_em->update([
            'id' => $ticket_id,
            'priority' => 5,
        ]);
        $ticket_tasks = $ticket_task_em->find([
            'tickets_id' => $ticket_id
        ]);

        $this->array($ticket_tasks)->hasSize(1);
        $task_data = array_pop($ticket_tasks);
        $this->array($task_data)->hasKey('content');
        $this->string($task_data['content'])->isEqualTo(
            Sanitizer::encodeHtmlSpecialChars('<p>test content</p>')
        );

       // Add a second action to the rule (test multiple creation)
        $this->createItem('TaskTemplate', [
            'name'    => "template 2",
            'content' => '<p>test content 2</p>',
        ]);

        $this->createItem('RuleAction', [
            'rules_id'    => $rule_ticket_id,
            'action_type' => 'append',
            'field'       => 'task_template',
            'value'       => getItemByTypeName("TaskTemplate", "template 2", true),
        ]);

        $this->createItem('Ticket', [
            'name'     => 'test ticket with two tasks',
            'content'  => 'test',
            'priority' => 5,
        ]);

        $ticket_tasks = $ticket_task_em->find([
            'tickets_id' => getItemByTypeName("Ticket", 'test ticket with two tasks', true),
        ]);
        $this->array($ticket_tasks)->hasSize(2);

        $task_data = array_pop($ticket_tasks);
        $this->array($task_data)->hasKey('content');
        $this->string($task_data['content'])->isEqualTo(
            Sanitizer::encodeHtmlSpecialChars('<p>test content 2</p>')
        );

        $task_data = array_pop($ticket_tasks);
        $this->array($task_data)->hasKey('content');
        $this->string($task_data['content'])->isEqualTo(
            Sanitizer::encodeHtmlSpecialChars('<p>test content</p>')
        );
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

       // Create rule
        $rule_ticket = new \RuleTicket();
        $rule_ticket_id = $rule_ticket->add([
            'name'         => "test to assign ITILSolution",
            'match'        => 'OR',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD + \RuleTicket::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->integer($rule_ticket_id)->isGreaterThan(0);

       // Add condition (priority = 5) to rule
        $rule_criteria = new RuleCriteria();
        $rule_criteria_id = $rule_criteria->add([
            'rules_id'  => $rule_ticket_id,
            'criteria'  => 'priority',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => 4,
        ]);
        $this->integer($rule_criteria_id)->isGreaterThan(0);

       // Add action to rule
        $rule_action = new RuleAction();
        $rule_action_id = $rule_action->add([
            'rules_id'    => $rule_ticket_id,
            'action_type' => 'append',
            'field'       => 'itilfollowup_template',
            'value'       => $followup_template_id,
        ]);
        $this->integer($rule_action_id)->isGreaterThan(0);

       // Test on creation
        $ticket = new \Ticket();
        $ticket_id = $ticket->add([
            'name'     => 'test',
            'content'  => 'test',
            'priority' => 4,
        ]);
        $this->integer($ticket_id)->isGreaterThan(0);

        $ticket_followups = new ITILFollowup();
        $ticket_followups = $ticket_followups->find([
            'items_id' => $ticket_id,
            'itemtype' => "Ticket",
        ]);

        $this->array($ticket_followups)->hasSize(1);
        $ticket_followups_data = array_pop($ticket_followups);
        $this->array($ticket_followups_data)->hasKey('content');
        $this->string($ticket_followups_data['content'])->isEqualTo(
            Sanitizer::encodeHtmlSpecialChars('<p>test testFollowupTemplateAssignFromRule</p>')
        );

       // Test on update
        $ticket = new \Ticket();
        $ticket_id = $ticket->add([
            'name'     => 'test',
            'content'  => 'test',
            'priority' => 3,
        ]);
        $this->integer($ticket_id)->isGreaterThan(0);

        $ticket_followups = new ITILFollowup();
        $ticket_followups = $ticket_followups->find([
            'items_id' => $ticket_id,
            'itemtype' => "Ticket",
        ]);

        $this->array($ticket_followups)->hasSize(0);

        $ticket->update([
            'id' => $ticket_id,
            'priority' => 4,
        ]);

        $ticket_followups = new ITILFollowup();
        $ticket_followups = $ticket_followups->find([
            'items_id' => $ticket_id,
            'itemtype' => "Ticket",
        ]);

        $this->array($ticket_followups)->hasSize(1);
        $ticket_followups_data = array_pop($ticket_followups);
        $this->array($ticket_followups_data)->hasKey('content');
        $this->string($ticket_followups_data['content'])->isEqualTo(
            Sanitizer::encodeHtmlSpecialChars('<p>test testFollowupTemplateAssignFromRule</p>')
        );

       // Add a second action to the rule (test multiple creation)
        $this->createItem('ITILFollowupTemplate', [
            'name'    => "template 2",
            'content' => '<p>test testFollowupTemplateAssignFromRule 2</p>',
        ]);

        $this->createItem('RuleAction', [
            'rules_id'    => $rule_ticket_id,
            'action_type' => 'append',
            'field'       => 'itilfollowup_template',
            'value'       => getItemByTypeName("ITILFollowupTemplate", "template 2", true),
        ]);

        $this->createItem('Ticket', [
            'name'     => 'test ticket with two followups',
            'content'  => 'test',
            'priority' => 4,
        ]);

        $ticket_followups = new ITILFollowup();
        $ticket_followups = $ticket_followups->find([
            'items_id' => getItemByTypeName("Ticket", 'test ticket with two followups', true),
            'itemtype' => "Ticket",
        ]);
        $this->array($ticket_followups)->hasSize(2);

        $ticket_followups_data = array_pop($ticket_followups);
        $this->array($ticket_followups_data)->hasKey('content');
        $this->string($ticket_followups_data['content'])->isEqualTo(
            Sanitizer::encodeHtmlSpecialChars('<p>test testFollowupTemplateAssignFromRule 2</p>')
        );

        $ticket_followups_data = array_pop($ticket_followups);
        $this->array($ticket_followups_data)->hasKey('content');
        $this->string($ticket_followups_data['content'])->isEqualTo(
            Sanitizer::encodeHtmlSpecialChars('<p>test testFollowupTemplateAssignFromRule</p>')
        );
    }

    public function testGroupRequesterAssignFromUserGroupsAndRegexOnUpdateTicketContent()
    {
        $this->login();

       // Create rule to be triggered on ticket update
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'test regex group requester criterion',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONUPDATE,
            'is_recursive' => 0,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

       //create criteria to check if group requester match regex (group with parenthesia)
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_groups_id_of_requester',
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => Toolbox::addslashes_deep('/(.+\([^()]*\))/'),   //retrieve group with '(' and ')'
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

       // create ticket
        $ticket = new \Ticket();
        $ticket->getEmpty();
        $tickets_id = $ticket->add($ticket_input = [
            'name'                  => 'Add group requester',
            'content'               => 'test',
            '_users_id_requester'   => $user->fields['id']
        ]);
        unset($ticket_input['_users_id_requester']);
        $this->checkInput($ticket, $tickets_id, $ticket_input);

       //link between group1 and ticket will not exist
        $ticketGroup = new \Group_Ticket();
        $this->boolean(
            $ticketGroup->getFromDBByCrit([
                'tickets_id'         => $tickets_id,
                'groups_id'          => $group_id1,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isFalse();

       //link between group2 and ticket will not exist
        $this->boolean(
            $ticketGroup->getFromDBByCrit([
                'tickets_id'         => $tickets_id,
                'groups_id'          => $group_id2,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isFalse();

       //link between group3 and ticket will not exist
        $this->boolean(
            $ticketGroup->getFromDBByCrit([
                'tickets_id'         => $tickets_id,
                'groups_id'          => $group_id3,
                'type'               => \CommonITILActor::REQUESTER
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

       //update ticket
        $ticket->update($ticket_input = [
            'id'                    => $tickets_id,
            'content'               => 'testupdated',
        ]);
        $this->checkInput($ticket, $tickets_id, $ticket_input);

       //link between group1 and ticket will exist
        $ticketGroup = new \Group_Ticket();
        $this->boolean(
            $ticketGroup->getFromDBByCrit([
                'tickets_id'         => $tickets_id,
                'groups_id'          => $group_id1,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isTrue();

       //link between group2 and ticket will exist
        $this->boolean(
            $ticketGroup->getFromDBByCrit([
                'tickets_id'         => $tickets_id,
                'groups_id'          => $group_id2,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isTrue();

       //link between group3 and ticket will not exist
        $this->boolean(
            $ticketGroup->getFromDBByCrit([
                'tickets_id'         => $tickets_id,
                'groups_id'          => $group_id3,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isFalse();
    }

    public function testGroupRequesterAssignFromUserGroupsAndRegexOnAdd()
    {
        $this->login();

       // Create rule to be triggered on add
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'test regex group requester criterion',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD,
            'is_recursive' => 0,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

       //create criteria to check if group requester match regex
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_groups_id_of_requester',
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => Toolbox::addslashes_deep('/(.+\([^()]*\))/'),   //retrieve group with '(' and ')'
        ]);
       //change value because of addslashes
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

       // create ticket that trigger rule on creation
        $ticket = new \Ticket();
        $ticket->getEmpty();
        $tickets_id = $ticket->add($ticket_input = [
            'name'                  => 'Add group requester',
            'content'               => 'test',
            '_users_id_requester'   => $user->fields['id']
        ]);
        unset($ticket_input['_users_id_requester']);
        $this->checkInput($ticket, $tickets_id, $ticket_input);

       //link between group1 and ticket will exist
        $ticketGroup = new \Group_Ticket();
        $this->boolean(
            $ticketGroup->getFromDBByCrit([
                'tickets_id'         => $tickets_id,
                'groups_id'          => $group_id1,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isTrue();

       //link between group2 and ticket will exist
        $this->boolean(
            $ticketGroup->getFromDBByCrit([
                'tickets_id'         => $tickets_id,
                'groups_id'          => $group_id2,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isTrue();

       //link between group3 and ticket will not exist
        $this->boolean(
            $ticketGroup->getFromDBByCrit([
                'tickets_id'         => $tickets_id,
                'groups_id'          => $group_id3,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isFalse();
    }

    public function testGroupRequesterAssignFromUserGroupsAndRegexOnUpdate()
    {
        $this->login();

        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

       // Create rule to be triggered on add
        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'test regex group requester criterion',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONUPDATE,
            'is_recursive' => 0,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

       //create criteria to check if group requester match regex
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_groups_id_of_requester',
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => Toolbox::addslashes_deep('/(.+\([^()]*\))/'),   //retrieve group with '(' and ')'
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

       // create ticket that trigger rule on creation
        $ticket = new \Ticket();
        $ticket->getEmpty();
        $tickets_id = $ticket->add($ticket_input = [
            'name'                  => 'Add group requester',
            'content'               => 'test',
            '_users_id_requester'   => $userPostOnly->fields['id']
        ]);
        unset($ticket_input['_users_id_requester']);
        $this->checkInput($ticket, $tickets_id, $ticket_input);

       //link between group1 and ticket will not exist
        $ticketGroup = new \Group_Ticket();
        $this->boolean(
            $ticketGroup->getFromDBByCrit([
                'tickets_id'         => $tickets_id,
                'groups_id'          => $group_id1,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isFalse();

       //link between group2 and ticket will not exist
        $this->boolean(
            $ticketGroup->getFromDBByCrit([
                'tickets_id'         => $tickets_id,
                'groups_id'          => $group_id2,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isFalse();

       //link between group2 and ticket will not exist
        $this->boolean(
            $ticketGroup->getFromDBByCrit([
                'tickets_id'         => $tickets_id,
                'groups_id'          => $group_id3,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isFalse();

       //remove old user manually because from IHM is done before update ticket
        $ticket_user = new \Ticket_User();
        $ticket_user->deleteByCriteria([
            "users_id" => $userPostOnly->fields['id'],
            "tickets_id" => $tickets_id
        ]);

       //update ticket and change requester
        $ticket->update($ticket_input = [
            'name'                  => 'Add group requester',
            'id'                    => $tickets_id,
            'content'               => 'test',
            '_itil_requester'   => ["_type" => "user",
                "users_id" => $userNormal->fields['id']
            ]
        ]);
        unset($ticket_input['_itil_requester']);
        $this->checkInput($ticket, $tickets_id, $ticket_input);

       //link between group1 and ticket will exist
        $ticketGroup = new \Group_Ticket();
        $this->boolean(
            $ticketGroup->getFromDBByCrit([
                'tickets_id'         => $tickets_id,
                'groups_id'          => $group_id1,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isTrue();

       //link between group2 and ticket will exist
        $this->boolean(
            $ticketGroup->getFromDBByCrit([
                'tickets_id'         => $tickets_id,
                'groups_id'          => $group_id2,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isTrue();

       //link between group3 and ticket will not exist
        $this->boolean(
            $ticketGroup->getFromDBByCrit([
                'tickets_id'         => $tickets_id,
                'groups_id'          => $group_id3,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isFalse();
    }

    public function testValidationCriteria()
    {
        $this->login();

       // Create rule
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'test validation criteria',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD | \RuleTicket::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

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

       // Case 1: create a ticket without validation, should not trigger the rule
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'              => 'test validation criteria',
            'content'           => 'test validation criteria',
            'global_validation' => CommonITILValidation::WAITING,
        ]);
        $this->checkInput($ticket, $tickets_id, $ticket_input);
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();

       // Check that the rule was NOT executed
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer($ticket->fields['impact'])->isNotEqualTo($action_value);

       // Case 2: add validation to the ticket, should trigger the rule
        $update = $ticket->update([
            'id'                => $tickets_id,
            'global_validation' => CommonITILValidation::ACCEPTED,
        ]);
        $this->boolean($update)->isTrue();
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();

       // Check that the rule was executed
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer($ticket->fields['impact'])->isEqualTo($action_value);

       // Case 3: create a ticket with validation, should trigger the rule
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'              => 'test validation criteria',
            'content'           => 'test validation criteria',
            'global_validation' => CommonITILValidation::ACCEPTED,
        ]);
        $this->checkInput($ticket, $tickets_id, $ticket_input);
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();

       // Check that the rule was executed
        $this->integer($ticket->fields['impact'])->isEqualTo($action_value);
    }

    public function testValidationAction()
    {
        $this->login();

       // Create rule
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'test validation action',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD | \RuleTicket::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

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

       // Case 1: create a ticket that should not trigger the rule
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'     => 'test validation action',
            'content'  => 'test validation action',
            'priority' => 4,
        ]);
        $this->checkInput($ticket, $tickets_id, $ticket_input);
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();

       // Check that the rule was NOT executed
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer($ticket->fields['global_validation'])->isNotEqualTo($action_value);

       // Case 2: add target priority to the ticket, should trigger the rule
        $update = $ticket->update([
            'id'       => $tickets_id,
            'priority' => 6,
        ]);
        $this->boolean($update)->isTrue();
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();

       // Check that the rule was executed
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer($ticket->fields['global_validation'])->isEqualTo($action_value);

       // Case 3: create a ticket with target priority, should trigger the rule
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'     => 'test validation action',
            'content'  => 'test validation action',
            'priority' => 6
        ]);
        $this->checkInput($ticket, $tickets_id, $ticket_input);
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();

       // Check that the rule was executed
        $this->integer($ticket->fields['global_validation'])->isEqualTo($action_value);
    }

    public function testITILCategoryCode()
    {
        $this->login();

        // Common variables that will be reused
        $rule_criteria_category_code = "R";
        $rule_action_impact_value = 1; // very low

        // Create rule, rule criteria and rule action
        $rule = $this->createItem('RuleTicket', [
            'name'         => 'test category code',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD | \RuleTicket::ONUPDATE,
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

        // Check ticket that trigger rule on creation
        $ticket = $this->createItem('Ticket', [
            'name'              => 'test category code',
            'content'           => 'test category code',
            'itilcategories_id' => $category->getID(),
        ]);
        $tickets_id = $ticket->getID();

        // Check that the rule was executed
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer($ticket->fields['impact'])->isEqualTo($rule_action_impact_value);

        // Create another ticket that doesn't match the rule
        $ticket = $this->createItem('Ticket', [
            'name'              => 'test category code',
            'content'           => 'test category code',
            'itilcategories_id' => 0
        ]);
        $tickets_id = $ticket->getID();

        // Check that the rule was NOT executed
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer($ticket->fields['impact'])->isNotEqualTo($rule_action_impact_value);

        // Update ticket to match the rule
        $this->updateItem('Ticket', $ticket->getID(), [
            'itilcategories_id' => $category->getID(),
        ]);

        // Check that the rule was executed
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer($ticket->fields['impact'])->isEqualTo($rule_action_impact_value);

        // Change impact, the rule must not be executed again as the category didn't change
        $this->updateItem('Ticket', $ticket->getID(), [
            'itilcategories_id' => $category->getID(), // Simulate same category being sent from the user form
            'impact' => 2,
        ]);

        // Check that the rule was not executed
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer($ticket->fields['impact'])->isNotEqualTo($rule_action_impact_value);
    }

    /**
     * Test contract type criteria
     */
    public function testContractType()
    {
        $this->login();

       // Create contract type (we need its id to setup the rule)
        $contract_type = new ContractType();
        $contract_type_input = [
            'name'        => 'test_contract',
        ];
        $contract_type_id = $contract_type->add($contract_type_input);
        $this->checkInput($contract_type, $contract_type_id, $contract_type_input);

       // Create rule
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'test contract type',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD | \RuleTicket::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

       // Create criteria to check if category code is R
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_contract_types',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => $contract_type_id,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

       // Create action to put impact to very low
        $rule_value = 2;
        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'impact',
            'value'       => $rule_value,
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

       // Create new group
        $category = new \ITILCategory();
        $category_id = $category->add($category_input = [
            "name" => "group1",
            "code" => "R"
        ]);
        $this->checkInput($category, $category_id, $category_input);

       // Create a ticket
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'              => 'test category code',
            'content'           => 'test category code',
            'itilcategories_id' => $category_id
        ]);
        $this->checkInput($ticket, $tickets_id, $ticket_input);

       // Check that the rule was not executed yet
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer($ticket->fields['impact'])->isNotEqualTo($rule_value);

       // Update ticket
        $update_1_res = $ticket->update([
            'id' => $ticket->fields['id'],
            'content' => 'content update 1',
        ]);
        $this->boolean($update_1_res)->isTrue();

       // Check that rule was not executed yet
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer($ticket->fields['impact'])->isNotEqualTo($rule_value);

       // Create contract
        $contract = new Contract();
        $contract_input = [
            'name'             => 'test_contract',
            'contracttypes_id' => $contract_type_id,
            'entities_id'      => getItemByTypeName('Entity', '_test_root_entity', true),
        ];
        $contract_id = $contract->add($contract_input);
        $this->checkInput($contract, $contract_id, $contract_input);

       // Link contract to ticket
        $ticketcontract = new Ticket_Contract();
        $ticketcontract_input = [
            'contracts_id' => $contract_id,
            'tickets_id'   => $ticket->fields['id'],
        ];
        $ticketcontract_id = $ticketcontract->add($ticketcontract_input);
        $this->checkInput($ticketcontract, $ticketcontract_id, $ticketcontract_input);

       // Update ticket a second time
        $update_2_res = $ticket->update([
            'id' => $ticket->fields['id'],
            'content' => 'content update 2',
        ]);
        $this->boolean($update_2_res)->isTrue();

       // Check that rule was executed correctly
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer($ticket->fields['impact'])->isEqualTo($rule_value);

       // Create a second ticket with the contract linked
        $ticket_2 = new \Ticket();
        $tickets_id_2 = $ticket->add($ticket_input_2 = [
            'name'              => 'test category code',
            'content'           => 'test category code',
            'itilcategories_id' => $category_id,
            '_contracts_id'     => $contract_id,
        ]);
        unset($ticket_input_2['_contracts_id']); // Remove temporary field as the "checkInput" method will not be able to find it
        $this->checkInput($ticket_2, $tickets_id_2, $ticket_input_2);

       // Check that the rule was executed correctly
        $this->boolean($ticket_2->getFromDB($tickets_id_2))->isTrue();
        $this->integer($ticket_2->fields['impact'])->isEqualTo($rule_value);
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

       //add appliance to ticket type
        $CFG_GLPI["ticket_types"][] = \Appliance::getType();

       // Add rule for create / update trigger (and assign action)
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'test associated element : appliance',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONUPDATE + \RuleTicket::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

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

       //create ticket to match rule on create
        $ticketCreate = new \Ticket();
        $ticketsCreate_id = $ticketCreate->add($ticketCreate_input = [
            'name'              => 'test appliance',
            'content'           => 'test appliance'
        ]);
        $this->checkInput($ticketCreate, $ticketsCreate_id, $ticketCreate_input);

       //check for one associated element
        $this->integer(countElementsInTable(
            \Item_Ticket::getTable(),
            ['itemtype'  =>  \Appliance::getType(),
                'items_id'   => $appliancetest1_id,
                'tickets_id' => $ticketsCreate_id
            ]
        ))->isEqualTo(1);

       //create ticket to match rule on update
        $ticketUpdate = new \Ticket();
        $ticketsUpdate_id = $ticketUpdate->add($ticketUpdate_input = [
            'name'              => 'test',
            'content'           => 'test'
        ]);
        $this->checkInput($ticketUpdate, $ticketsUpdate_id, $ticketUpdate_input);

       //no appliance associated
        $this->integer(countElementsInTable(
            \Item_Ticket::getTable(),
            ['itemtype'  =>  \Appliance::getType(),
                'items_id'   => $appliancetest1_id,
                'tickets_id' => $ticketsUpdate_id
            ]
        ))->isEqualTo(0);

       //update ticket content to match rule
        $ticketUpdate->update(
            [
                'id'      => $ticketsUpdate_id,
                'name'    => 'test erp',
                'content' => 'appliance'
            ]
        );

       //check for one associated element
        $this->integer(countElementsInTable(
            \Item_Ticket::getTable(),
            ['itemtype'  =>  \Appliance::getType(),
                'items_id'   => $appliancetest1_id,
                'tickets_id' => $ticketsUpdate_id
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
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'test associated element with regex : erp',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONUPDATE + \RuleTicket::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

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

       //create ticket to match rule on create
       //create ticket to match rule on create
        $ticketCreate = new \Ticket();
        $ticketsCreate_id = $ticketCreate->add($ticketCreate_input = [
            'name'              => 'test erp',
            'content'           => 'test erp'
        ]);

        $this->checkInput($ticketCreate, $ticketsCreate_id, $ticketCreate_input);
        $this->integer($ticketsCreate_id)->isGreaterThan(0);

       //check for one associated element
        $this->integer(countElementsInTable(
            \Item_Ticket::getTable(),
            ['itemtype'  =>  \Appliance::getType(),
                'items_id'   => $appliancetest1_id,
                'tickets_id' => $ticketsCreate_id
            ]
        ))->isEqualTo(1);

       //create ticket to match rule on update
        $ticketUpdate = new \Ticket();
        $ticketsUpdate_id = $ticketUpdate->add($ticketUpdate_input = [
            'name'              => 'test',
            'content'           => 'test'
        ]);
        $this->checkInput($ticketUpdate, $ticketsUpdate_id, $ticketUpdate_input);

       //no appliance associated
        $this->integer(countElementsInTable(
            \Item_Ticket::getTable(),
            ['itemtype'  =>  \Appliance::getType(),
                'items_id'   => $appliancetest1_id,
                'tickets_id' => $ticketsUpdate_id
            ]
        ))->isEqualTo(0);

       //update ticket content to match rule
        $ticketUpdate->update(
            [
                'id'      => $ticketsUpdate_id,
                'name' => 'erp'
            ]
        );

       //check for one associated element
        $this->integer(countElementsInTable(
            \Item_Ticket::getTable(),
            ['itemtype'  =>  \Appliance::getType(),
                'items_id'   => $appliancetest1_id,
                'tickets_id' => $ticketsUpdate_id
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
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'test associated element with  : erp',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONUPDATE + \RuleTicket::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

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

       //create ticket to match rule on create
        $ticketCreate = new \Ticket();
        $ticketsCreate_id = $ticketCreate->add($ticketCreate_input = [
            'name'              => 'test erp',
            'content'           => 'test erp'
        ]);

        $this->checkInput($ticketCreate, $ticketsCreate_id, $ticketCreate_input);
        $this->integer($ticketsCreate_id)->isGreaterThan(0);

       //check for one associated element
        $this->integer(countElementsInTable(
            \Item_Ticket::getTable(),
            ['itemtype'  =>  \Appliance::getType(),
                'tickets_id' => $ticketsCreate_id
            ]
        ))->isEqualTo(2);

       //create ticket to match rule on update
        $ticketUpdate = new \Ticket();
        $ticketsUpdate_id = $ticketUpdate->add($ticketUpdate_input = [
            'name'              => 'test',
            'content'           => 'test'
        ]);
        $this->checkInput($ticketUpdate, $ticketsUpdate_id, $ticketUpdate_input);

       //no appliance associated
        $this->integer(countElementsInTable(
            \Item_Ticket::getTable(),
            ['itemtype'  =>  \Appliance::getType(),
                'items_id'   => $appliancetest1_id,
                'tickets_id' => $ticketsUpdate_id
            ]
        ))->isEqualTo(0);

       //update ticket content to match rule
        $ticketUpdate->update(
            [
                'id'      => $ticketsUpdate_id,
                'name' => 'test erp'
            ]
        );

       //check for one associated element
        $this->integer(countElementsInTable(
            \Item_Ticket::getTable(),
            ['itemtype'  =>  \Appliance::getType(),
                'tickets_id' => $ticketsUpdate_id
            ]
        ))->isEqualTo(2);
    }

    public function testAssignContract()
    {
        $this->login();

       // Create contract1 "zabbix"
        $contractTest1 = new \Contract();
        $contracttest1_id = $contractTest1->add($contractTest1_input = [
            "name"                  => "zabbix",
            "entities_id"           => 0
        ]);
        $this->checkInput($contractTest1, $contracttest1_id, $contractTest1_input);

       // Create rule for create regex action
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'test associate contract with  : glpi',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

       // Create criteria to match regex
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'itilcategories_id',
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => '/(zabbix)/',
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

       // Create action to assign contract1
        $action_id1 = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'regex_result',
            'field'       => 'assign_contract',
            'value'       => '#0',
        ]);
        $this->checkInput($ruleaction, $action_id1, $action_input);

       // Create category for ticket
        $category = new \ITILCategory();
        $category_id = $category->add($category_input = [
            "name" => "zabbix"
        ]);
        $this->checkInput($category, $category_id, $category_input);

       // Create ticket to match rule on create
        $ticketCreate = new \Ticket();
        $ticketsCreate_id = $ticketCreate->add($ticketCreate_input = [
            'name'              => 'test zabbix',
            'content'           => 'test zabbix',
            'itilcategories_id' => $category_id
        ]);

        $this->checkInput($ticketCreate, $ticketsCreate_id, $ticketCreate_input);
        $this->integer($ticketsCreate_id)->isGreaterThan(0);

       // Check for one associated element
        $this->integer(countElementsInTable(
            \Ticket_Contract::getTable(),
            ['contracts_id'  => $contracttest1_id,
                'tickets_id' => $ticketsCreate_id
            ]
        ))->isEqualTo(1);
    }

    protected function testMailHeaderCriteriaProvider()
    {
        return [
            [
                "pattern"  => 'pattern_priority',
                "header"   => 'x-priority',
            ],
            [
                "pattern"  => 'pattern_from',
                "header"   => 'from',
            ],
            [
                "pattern"  => 'pattern_to',
                "header"   => 'to',
            ],
            [
                "pattern"  => 'pattern_reply-to',
                "header"   => 'reply-to',
            ],
            [
                "pattern"  => 'pattern_in-reply-to',
                "header"   => 'in-reply-to',
            ],
            [
                "pattern"  => 'pattern_subject',
                "header"   => 'subject',
            ],
        ];
    }

    /**
     * @dataprovider testMailHeaderCriteriaProvider
     */
    public function testMailHeaderCriteria(
        string $pattern,
        string $header
    ) {
        // clean right singleton
        \SingletonRuleList::getInstance("RuleTicket", 0)->load = 0;
        \SingletonRuleList::getInstance("RuleTicket", 0)->list = [];

        $this->login();

        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'test ' . $header,
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => "_" . $header,
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => $pattern,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        // Create action to put priority to very high
        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'priority',
            'value'       => 5,
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name'              => 'test ' . $header . ' header',
            'content'           => 'test ' . $header . ' header',
            '_head'             => [
                $header => $pattern
            ]
        ]);

        // Verify ticket has priority 5
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer($ticket->fields['priority'])->isEqualTo(5);

        // Retest ticket with different header
        $tickets_id = $ticket->add([
            'name'              => 'test ' . $header . ' header',
            'content'           => 'test ' . $header . ' header',
            '_head'             => [
                $header => 'header_foo_bar'
            ]
        ]);

        // Verify ticket does not have priority 5
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer($ticket->fields['priority'])->isNotEqualTo(5);
    }

    public function testStopProcessingAction()
    {
        $this->login();

        // Create rule
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid_1 = $ruleticket->add($ruletinput = [
            'name'         => 'stopProcessingAction_1',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid_1, $ruletinput);

        $ruletid_2 = $ruleticket->add($ruletinput = [
            'name'         => 'stopProcessingAction_2',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid_2, $ruletinput);

        $ruletid_3 = $ruleticket->add($ruletinput = [
            'name'         => 'stopProcessingAction_3',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid_3, $ruletinput);

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

        // Check ticket that trigger rule on creation
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'              => 'stopProcessingAction',
            'content'           => 'test stopProcessingAction'
        ]);
        $this->checkInput($ticket, $tickets_id, $ticket_input);

        // Check that the rule was executed
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->integer($ticket->fields['impact'])->isEqualTo(2);
    }

    public function testAppendUserOnUpdate()
    {
        $this->login();

        $user = new \User();
        $user_id = $user->add($user_input = [
            "name" => "user1"
        ]);
        $this->checkInput($user, $user_id, $user_input);

        // Create rule
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'testAppendUserOnUpdate',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

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
            'action_type' => 'append',
            'field'       => '_users_id_requester',
            'value'       => $user_id,
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

        // Create ticket
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'             => 'testAppendUserOnUpdate',
            'content'          => 'test',
        ]);
        $this->checkInput($ticket, $tickets_id, $ticket_input);

        $ticketUser = new \Ticket_User();
        $this->boolean(
            $ticketUser->getFromDBByCrit([
                'tickets_id'    => $tickets_id,
                'users_id'      => $user_id,
                'type'          => \CommonITILActor::REQUESTER
            ])
        )->isFalse();

        // Test updating ticket
        $this->boolean($ticket->update(['id' => $tickets_id, 'content' => 'test2']))->isTrue();
        $result = $ticketUser->getFromDBByCrit([
            'tickets_id'    => $tickets_id,
            'users_id'      => $user_id,
            'type'          => \CommonITILActor::REQUESTER
        ]);
        $this->boolean($result)->isTrue();

        $this->boolean($ticketUser->delete(['id' => $ticketUser->getID()]))->isTrue();

        // Test ticket update when _actors input is present but empty (emulate update from form when no actors present already)
        $this->boolean($ticket->update(['id' => $tickets_id, 'content' => 'test3', '_actors' => []]))->isTrue();
        $result = $ticketUser->getFromDBByCrit([
            'tickets_id'    => $tickets_id,
            'users_id'      => $user_id,
            'type'          => \CommonITILActor::REQUESTER
        ]);
        $this->boolean($result)->isTrue();
    }

    public function testAppendGroupOnUpdate()
    {
        $this->login();

        //create new group1
        $group1 = new \Group();
        $group_id1 = $group1->add($group_input1 = [
            "name" => "group1",
            "is_requester" => true
        ]);
        $this->checkInput($group1, $group_id1, $group_input1);

        // Create rule
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'testAppendGroupOnUpdate',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

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
            'action_type' => 'append',
            'field'       => '_groups_id_requester',
            'value'       => $group_id1,
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

        // Create ticket
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'             => 'testAppendGroupOnUpdate',
            'content'          => 'test',
        ]);
        $this->checkInput($ticket, $tickets_id, $ticket_input);

        //load TicketGroup1 (expected false)
        $ticketGroup = new \Group_Ticket();
        $this->boolean(
            $ticketGroup->getFromDBByCrit([
                'tickets_id'         => $tickets_id,
                'groups_id'          => $group_id1,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isFalse();

        // Test updating ticket
        $this->boolean($ticket->update(['id' => $tickets_id, 'content' => 'test2']))->isTrue();
        $result = $ticketGroup->getFromDBByCrit([
            'tickets_id'         => $tickets_id,
            'groups_id'          => $group_id1,
            'type'               => \CommonITILActor::REQUESTER
        ]);
        $this->boolean($result)->isTrue();

        $this->boolean($ticketGroup->delete(['id' => $ticketGroup->getID()]))->isTrue();

        // Test ticket update when _actors input is present but empty (emulate update from form when no actors present already)
        $this->boolean($ticket->update(['id' => $tickets_id, 'content' => 'test3', '_actors' => []]))->isTrue();
        $result = $ticketGroup->getFromDBByCrit([
            'tickets_id'         => $tickets_id,
            'groups_id'          => $group_id1,
            'type'               => \CommonITILActor::REQUESTER
        ]);
        $this->boolean($result)->isTrue();
    }

    public function testAppendSupplierOnUpdate()
    {
        $this->login();

        $supplier = new \Supplier();
        $supplier_id = $supplier->add($group_input1 = [
            "name" => "supplier1",
            'entities_id'   => getItemByTypeName('Entity', '_test_root_entity', true)
        ]);
        $this->checkInput($supplier, $supplier_id, $group_input1);

        // Create rule
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'testAppendSupplierOnUpdate',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

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
            'action_type' => 'append',
            'field'       => '_suppliers_id_assign',
            'value'       => $supplier_id,
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

        // Create ticket
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name'             => 'testAppendSupplierOnUpdate',
            'content'          => 'test',
        ]);
        $this->checkInput($ticket, $tickets_id, $ticket_input);

        //load TicketGroup1 (expected false)
        $ticketSupplier = new \Supplier_Ticket();
        $this->boolean(
            $ticketSupplier->getFromDBByCrit([
                'tickets_id'    => $tickets_id,
                'suppliers_id'  => $supplier_id,
                'type'          => \CommonITILActor::ASSIGN
            ])
        )->isFalse();

        // Test updating ticket
        $this->boolean($ticket->update(['id' => $tickets_id, 'content' => 'test2']))->isTrue();
        $result = $ticketSupplier->getFromDBByCrit([
            'tickets_id'    => $tickets_id,
            'suppliers_id'  => $supplier_id,
            'type'          => \CommonITILActor::ASSIGN
        ]);
        $this->boolean($result)->isTrue();

        $this->boolean($ticketSupplier->delete(['id' => $ticketSupplier->getID()]))->isTrue();

        // Test ticket update when _actors input is present but empty (emulate update from form when no actors present already)
        $this->boolean($ticket->update(['id' => $tickets_id, 'content' => 'test3', '_actors' => []]))->isTrue();
        $result = $ticketSupplier->getFromDBByCrit([
            'tickets_id'    => $tickets_id,
            'suppliers_id'  => $supplier_id,
            'type'          => \CommonITILActor::ASSIGN
        ]);
        $this->boolean($result)->isTrue();
    }

    public function testNewActors()
    {
        $this->login();

        $tech_id   = getItemByTypeName('User', "tech", true);
        $groups_id = getItemByTypeName('Group', '_test_group_1', true);

        $supplier = new \Supplier();
        $suppliers_id = $supplier->add([
            'name'        => 'Supplier 1',
            'entities_id' => 0,
        ]);
        $this->integer($suppliers_id)->isGreaterThan(0);

        $location = new \Location();
        $locations_id = $location->add([
            'name' => 'Location 1',
        ]);
        $this->integer($locations_id)->isGreaterThan(0);

        // Create rule
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'testNewActors',
            'match'        => 'OR',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

        //create criteria to check
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_users_id_requester',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => $tech_id,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_users_id_observer',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => $tech_id,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_users_id_assign',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => $tech_id,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_groups_id_requester',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => $groups_id,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_groups_id_observer',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => $groups_id,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_groups_id_assign',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => $groups_id,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => '_suppliers_id_assign',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => $suppliers_id,
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        //create action to add group as group requester
        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'locations_id',
            'value'       => $locations_id,
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

        // test all common actors
        foreach (['User', 'Group'] as $actoritemtype) {
            $items_id = ($actoritemtype == "User") ? $tech_id : $groups_id;
            foreach (['requester', 'observer', 'assign'] as $actortype) {
                $ticket = new \Ticket();
                $tickets_id = $ticket->add([
                    'name'    => 'test actors',
                    'content' => 'test actors',
                    '_actors' => [
                        $actortype => [
                            [
                                'itemtype' => $actoritemtype,
                                'items_id' => $items_id,
                            ]
                        ]
                    ],
                ]);
                $ticket->getFromDB($tickets_id);
                $this->integer($ticket->fields['locations_id'])->isEqualTo($locations_id);
            }
        }

        // test also suppliers for assign
        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name'    => 'test actors supplier',
            'content' => 'test actors supplier',
            '_actors' => [
                'assign' => [
                    [
                        'itemtype' => 'Supplier',
                        'items_id' => $suppliers_id,
                    ]
                ]
            ],
        ]);
        $ticket->getFromDB($tickets_id);
        $this->integer($ticket->fields['locations_id'])->isEqualTo($locations_id);
    }

    public function testAssignProject()
    {
        $this->login();

       //create project "project"
        $projectTest1 = new \Project();
        $projecttest1_id = $projectTest1->add($projectTest1_input = [
            "name"                  => "project"
        ]);
        $this->checkInput($projectTest1, $projecttest1_id, $projectTest1_input);

       // Add rule for create / update trigger (and assign action)
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => 'test associated element : project',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONUPDATE + \RuleTicket::ONADD,
            'is_recursive' => 1,
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

       // Create criteria to check if content contain key word
        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'content',
            'condition' => \Rule::PATTERN_CONTAIN,
            'pattern'   => 'project',
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

       // Create action to add project
        $action_id = $ruleaction->add($action_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'assign_project',
            'value'       => $projecttest1_id,
        ]);
        $this->checkInput($ruleaction, $action_id, $action_input);

       //create ticket to match rule on create
        $ticketCreate = new \Ticket();
        $ticketsCreate_id = $ticketCreate->add($ticketCreate_input = [
            'name'              => 'test project',
            'content'           => 'test project'
        ]);
        $this->checkInput($ticketCreate, $ticketsCreate_id, $ticketCreate_input);

       //check for one associated element
        $this->integer(countElementsInTable(
            \Item_Project::getTable(),
            ['itemtype'  =>  \Ticket::getType(),
                'projects_id'   => $projecttest1_id,
                'items_id' => $ticketsCreate_id
            ]
        ))->isEqualTo(1);

       //create ticket to match rule on update
        $ticketUpdate = new \Ticket();
        $ticketsUpdate_id = $ticketUpdate->add($ticketUpdate_input = [
            'name'              => 'test',
            'content'           => 'test'
        ]);
        $this->checkInput($ticketUpdate, $ticketsUpdate_id, $ticketUpdate_input);

        //no project associated
        $this->integer(countElementsInTable(
            \Item_Project::getTable(),
            ['itemtype'  =>  \Ticket::getType(),
                'projects_id'   => $projecttest1_id,
                'items_id' => $ticketsUpdate_id
            ]
        ))->isEqualTo(0);

       //update ticket content to match rule
        $ticketUpdate->update(
            [
                'id'      => $ticketsUpdate_id,
                'name'    => 'test erp',
                'content' => 'project'
            ]
        );

       //check for one associated element
        $this->integer(countElementsInTable(
            \Item_Project::getTable(),
            ['itemtype'  =>  \Ticket::getType(),
                'projects_id'   => $projecttest1_id,
                'items_id' => $ticketsUpdate_id
            ]
        ))->isEqualTo(1);
    }

    public function testFollowupTemplateAssignFromGroup()
    {
        $this->login();

        // Create rule
        $rule_ticket = new \RuleTicket();
        $rule_ticket_id = $rule_ticket->add([
            'name'         => 'test group requester criterion',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD + \RuleTicket::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->integer($rule_ticket_id)->isGreaterThan(0);

        //create group that matches the rule
        $group = new \Group();
        $group_id1 = $group->add($group_input = [
            "name"         => "group1",
            "is_requester" => true
        ]);
        $this->checkInput($group, $group_id1, $group_input);

        //create group that doesn't match the rule
        $group_id2 = $group->add($group_input = [
            "name"         => "group2",
            "is_requester" => true
        ]);
        $this->checkInput($group, $group_id2, $group_input);

        // Create criteria to check if requester group is group1
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add([
            'rules_id'  => $rule_ticket_id,
            'criteria'  => '_groups_id_requester',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => $group_id1,
        ]);
        $this->integer($rule_criteria_id)->isGreaterThan(0);

        // Create followup template
        $followup_template = new ITILFollowupTemplate();
        $followup_template_id = $followup_template->add([
            'content' => "<p>test testFollowupTemplateAssignFromGroup</p>",
        ]);
        $this->integer($followup_template_id)->isGreaterThan(0);

        // Add action to rule
        $rule_action = new RuleAction();
        $rule_action_id = $rule_action->add([
            'rules_id'    => $rule_ticket_id,
            'action_type' => 'append',
            'field'       => 'itilfollowup_template',
            'value'       => $followup_template_id,
        ]);
        $this->integer($rule_action_id)->isGreaterThan(0);

        // Create ticket
        $ticket = new \Ticket();
        $ticket_id = $ticket->add([
            'name'              => 'test',
            'content'           => 'test',
            '_groups_id_requester' => [$group_id1],
        ]);
        $this->integer($ticket_id)->isGreaterThan(0);

        //link between group1 and ticket will exist
        $ticketGroup = new \Group_Ticket();
        $this->boolean(
            $ticketGroup->getFromDBByCrit([
                'tickets_id'         => $ticket_id,
                'groups_id'          => $group_id1,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isTrue();

        // Check that followup was added
        $this->integer(countElementsInTable(
            ITILFollowup::getTable(),
            ['itemtype' => \Ticket::getType(), 'items_id' => $ticket_id]
        ))->isEqualTo(1);

        // Add group2 to ticket
        $ticket->update([
            'id'                  => $ticket_id,
            '_groups_id_requester' => [$group_id1, $group_id2],
        ]);

        //link between group2 and ticket will exist
        $this->boolean(
            $ticketGroup->getFromDBByCrit([
                'tickets_id'         => $ticket_id,
                'groups_id'          => $group_id2,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isTrue();

        // Check that followup was added
        $this->integer(countElementsInTable(
            ITILFollowup::getTable(),
            ['itemtype' => \Ticket::getType(), 'items_id' => $ticket_id]
        ))->isEqualTo(2);

        // Add user to ticket
        $user = new \User();
        $user_id = $user->add([
            'name' => 'test',
        ]);
        $this->integer($user_id)->isGreaterThan(0);

        $ticket->update([
            'id'                  => $ticket_id,
            '_users_id_requester' => [$user_id],
        ]);

        //link between user and ticket will exist
        $ticketUser = new \Ticket_User();
        $this->boolean(
            $ticketUser->getFromDBByCrit([
                'tickets_id'         => $ticket_id,
                'users_id'           => $user_id,
                'type'               => \CommonITILActor::REQUESTER
            ])
        )->isTrue();

        // Check that followup was NOT added
        $this->integer(countElementsInTable(
            ITILFollowup::getTable(),
            ['itemtype' => \Ticket::getType(), 'items_id' => $ticket_id]
        ))->isEqualTo(2);
    }

    public function testSLACriterion()
    {
        $this->login('glpi', 'glpi');

        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name'         => "test rule SLA",
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONADD + \RuleTicket::ONUPDATE,
            'is_recursive' => 1
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

        $slm = new \SLM();
        $slm_id = $slm->add(
            [
                'name'         => 'Test SLM',
                'calendars_id' => 0, //24/24 7/7
            ]
        );
        $this->integer($slm_id)->isGreaterThan(0);

        // prepare sla/ola inputs
        $sla_in = [
            'slms_id'         => $slm_id,
            'name'            => "SLA TTR",
            'comment'         => $this->getUniqueString(),
            'type'            => \SLM::TTR,
            'number_time'     => 4,
            'definition_time' => 'day',
        ];

        // add SLA (TTR)
        $sla    = new \SLA();
        $slas_id_ttr = $sla->add($sla_in);
        $this->checkInput($sla, $slas_id_ttr, $sla_in);

        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'slas_id_ttr',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => $slas_id_ttr
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        $crit_id = $rulecrit->add($crit_input = [
            'rules_id'  => $ruletid,
            'criteria'  => 'urgency',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => 5
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        //create new location
        $location = new \Location();
        $location_id = $location->add($location_input = [
            "name" => "location1",
        ]);
        $this->checkInput($location, $location_id, $location_input);

        $act_id = $ruleaction->add($act_input = [
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'locations_id',
            'value'       => $location_id
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);

        //create ticket to match rule
        $ticket = new \Ticket();
        $ticket_id = $ticket->add($ticket_input = [
            'name'              => 'test SLA',
            'content'           => 'test SLA',
            'slas_id_ttr'       => $slas_id_ttr,
            'urgency'           => 5
        ]);
        $this->checkInput($ticket, $ticket_id, $ticket_input);

        $this->integer($ticket->fields['locations_id'])->isIdenticalTo($location_id);

        //create ticket to not match rule
        $ticket = new \Ticket();
        $ticket_id = $ticket->add($ticket_input = [
            'name'              => 'test SLA',
            'content'           => 'test SLA',
            'slas_id_ttr'       => $slas_id_ttr,
        ]);
        $this->checkInput($ticket, $ticket_id, $ticket_input);

        $this->integer($ticket->fields['locations_id'])->isIdenticalTo(0);

        //update URGENCY to match rule
        $this->boolean($ticket->update($ticket_input = [
            'id'                => $ticket_id,
            'urgency'           => 5,
        ]))->isTrue();

        $ticket->getFromDB($ticket_id);
        $this->checkInput($ticket, $ticket_id, $ticket_input);

        $this->integer($ticket->fields['locations_id'])->isIdenticalTo($location_id);
    }

    /**
     * Data provider for testAssignLocationFromUser
     *
     * @return iterable
     */
    protected function testAssignLocationFromUserProvider(): iterable
    {
        $this->login();
        $entity = getItemByTypeName('Entity', '_test_root_entity');
        $user = getItemByTypeName('User', TU_USER);

        // Create rule
        $rule = $this->createItem("RuleTicket", [
            'name'        => "test rule SLA",
            'match'       => 'AND',
            'is_active'   => 1,
            'sub_type'    => 'RuleTicket',
            'condition'   => \RuleTicket::ONADD,
            'entities_id' => $entity->getID(),
        ]);
        $this->createItem("RuleCriteria", [
            'rules_id'  => $rule->getID(),
            'criteria'  => 'locations_id',
            'condition' => \Rule::PATTERN_DOES_NOT_EXISTS,
            'pattern'   => 1
        ]);
        $this->createItem("RuleCriteria", [
            'rules_id'  => $rule->getID(),
            'criteria'  => '_locations_id_of_requester',
            'condition' => \Rule::PATTERN_EXISTS,
            'pattern'   => 1
        ]);
        $this->createItem("RuleAction", [
            'rules_id'    => $rule->getID(),
            'action_type' => 'fromuser',
            'field'       => 'locations_id',
            'value'       => 1
        ]);

        // Create location and set it to our user
        $user_location = $this->createItem('Location', [
            'name'        => 'User location',
            'entities_id' => $entity->getID(),
        ]);
        $this->updateItem('User', $user->getID(), [
            'locations_id' => $user_location->getID()
        ]);

        // Create another location
        $other_location = $this->createItem('Location', [
            'name'        => 'Other location',
            'entities_id' => $entity->getID(),
        ]);

        // Create a ticket without location, should trigger the rule and set the user location
        yield [null, $user_location->getID()];

        // Create a ticket with a specific location, should not trigger the rule
        yield [$other_location->getID(), $other_location->getID()];
    }

    /**
     * Test the following rule:
     * IF ticket location is not set AND Requester has a location
     * THEN set location from requester
     *
     * @param int|null $input_locations_id               Input location
     * @param int      $expected_location_after_creation Ticket final location after the rule are processed
     *
     * @return void
     *
     * @dataprovider testAssignLocationFromUserProvider
     */
    public function testAssignLocationFromUser(
        ?int $input_locations_id,
        int $expected_location_after_creation
    ): void {
        $input = [
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'name'        => 'test ticket',
            'content'     => 'test ticket',
            '_actors'     => [
                // Requester is needed for the criteria on the requester's location
                'requester' => [
                    [
                        'itemtype' => 'User',
                        'items_id' => getItemByTypeName('User', TU_USER, true),
                    ]
                ],
                // Must have an assigned tech for the test to be meaningfull as this
                // will trigger some post_update code that will run the rules again
                'assign' => [
                    [
                        'itemtype' => 'User',
                        'items_id' => getItemByTypeName('User', TU_USER, true),
                    ]
                ]
            ]
        ];

        if (!is_null($input_locations_id)) {
            $input['locations_id'] = $input_locations_id;
        }

        $ticket = $this->createItem('Ticket', $input);
        $ticket->getFromDB($ticket->getID());
        $this->integer($ticket->fields['locations_id'])->isEqualTo($expected_location_after_creation);
    }
}
