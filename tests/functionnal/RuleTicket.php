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

use CommonITILValidation;
use Contract;
use ContractType;
use DbTestCase;
use Group_User;
use RuleAction;
use RuleCriteria;
use TaskTemplate;
use Ticket_Contract;
use TicketTask;
use Toolbox;

/* Test for inc/ruleticket.class.php */

class RuleTicket extends DbTestCase {

   public function testGetCriteria() {
      $rule = new \RuleTicket();
      $criteria = $rule->getCriterias();
      $this->array($criteria)->size->isGreaterThan(20);
   }

   public function testGetActions() {
      $rule = new \RuleTicket();
      $actions  = $rule->getActions();
      $this->array($actions)->size->isGreaterThan(20);
   }

   public function testDefaultRuleExists() {
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

   public function testTriggerAdd() {
      $this->login();

      // prepare rule
      $this->_createTestTriggerRule(\RuleTicket::ONADD);

      // test create ticket (trigger on title)
      $ticket = new \Ticket;
      $tickets_id = $ticket->add($ticket_input = [
         'name'    => "test ticket, will trigger on rule (title)",
         'content' => "test"
      ]);
      $this->checkInput($ticket, $tickets_id, $ticket_input);
      $this->integer((int)$ticket->getField('urgency'))->isEqualTo(5);

      // test create ticket (trigger on user assign)
      $ticket = new \Ticket;
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

   public function testTriggerUpdate() {
      $this->login();
      $this->setEntity('Root entity', true);

      $users_id = (int) getItemByTypeName('User', 'tech', true);

      // prepare rule
      $this->_createTestTriggerRule(\RuleTicket::ONUPDATE);

      // test create ticket (for check triggering on title after update)
      $ticket = new \Ticket;
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
      $ticket = new \Ticket;
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
      $ticket_user = new \Ticket_User;
      $actors = $ticket_user->getActors($tickets_id);
      $this->integer((int)$actors[2][1]['users_id'])->isEqualTo($users_id);
      $this->integer((int)$ticket->getField('urgency'))->isEqualTo(5);
   }

   private function _createTestTriggerRule($condition) {
      $ruleticket = new \RuleTicket;
      $rulecrit   = new \RuleCriteria;
      $ruleaction = new \RuleAction;

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
   public function testStatusCriterion() {
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
   public function testStatusAssignNewFromRule() {
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

      // Remove assign self as default tech from session
      $default_tech = $_SESSION['glpiset_default_tech'];
      $_SESSION['glpiset_default_tech'] = false;

      // Check ticket that trigger rule on update
      $ticket = new \Ticket();
      $tickets_id = $ticket->add($ticket_input = [
         'name'    => 'some ticket',
         'content' => 'test'
      ]);
      $this->checkInput($ticket, $tickets_id, $ticket_input);
      $this->integer((int)$ticket->getField('status'))->isEqualTo(\Ticket::INCOMING);
      $this->integer(countElementsInTable(
         \Ticket_User::getTable(),
         ['tickets_id' => $tickets_id, 'type' => \CommonITILActor::ASSIGN]
      ))->isEqualTo(0);

      $this->boolean($ticket->update([
         'id'      => $tickets_id,
         'name'    => 'assign to tech (on update)',
         'content' => 'test'
      ]))->isTrue();
      $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
      $this->integer((int)$ticket->getField('status'))->isEqualTo(\Ticket::INCOMING);
      $this->integer(countElementsInTable(
         \Ticket_User::getTable(),
         ['tickets_id' => $tickets_id, 'type' => \CommonITILActor::ASSIGN]
      ))->isEqualTo(1);

      // Restore assign self as default tech in session
      $_SESSION['glpiset_default_tech'] = $default_tech;
   }

   public function testITILCategoryAssignFromRule() {
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

   public function testITILSolutionAssignFromRule() {
      $this->login();

      // Create solution template
      $solutionTemplate = new \SolutionTemplate();
      $solutionTemplate_id = $solutionTemplate->add($solutionInput = [
         'content' => Toolbox::addslashes_deep("content of solution template  white ' quote")
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
      $this->boolean($itilSolution->getFromDBByCrit(['items_id'            => $tickets_id,
                                                   'itemtype'              => 'Ticket',
                                                   'content'               => Toolbox::addslashes_deep("content of solution template  white ' quote")]))->isTrue();

      $this->integer((int)$itilSolution->getID())->isGreaterThan(0);

      //reload and check ticket status
      $ticket->getFromDB($tickets_id);
      $this->integer((int)$ticket->getField('status'))->isEqualTo(\CommonITILObject::SOLVED);

   }

   public function testGroupRequesterAssignFromDefaultUserOnCreate() {
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

   public function testGroupRequesterAssignFromDefaultUserAndLocationFromUserOnUpdate() {
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

   public function testTaskTemplateAssignFromRule() {
      $this->login();

      // Create solution template
      $task_template = new TaskTemplate();
      $task_template_id = $task_template->add([
         'content' => "test content"
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
         'action_type' => 'assign',
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
      $this->string($task_data['content'])->isEqualTo('test content');

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
      $this->string($task_data['content'])->isEqualTo('test content');
   }

   public function testGroupRequesterAssignFromUserGroupsAndRegexOnUpdateTicketContent() {
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
      unset($ticket_input['id']);
      $this->checkInput($ticket, $tickets_id, $ticket_input);

      //link between groupe1 and ticket will not exist
      $ticketGroup = new \Group_Ticket();
      $this->boolean(
         $ticketGroup->getFromDBByCrit([
            'tickets_id'         => $tickets_id,
            'groups_id'          => $group_id1,
            'type'               => \CommonITILActor::REQUESTER
         ])
      )->isFalse();

      //link between groupe2 and ticket will not exist
      $this->boolean(
         $ticketGroup->getFromDBByCrit([
            'tickets_id'         => $tickets_id,
            'groups_id'          => $group_id2,
            'type'               => \CommonITILActor::REQUESTER
         ])
      )->isFalse();

      //link between groupe3 and ticket will not exist
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

   public function testGroupRequesterAssignFromUserGroupsAndRegexOnAdd() {
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
      unset($ticket_input['id']);
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

   public function testGroupRequesterAssignFromUserGroupsAndRegexOnUpdate() {
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
      unset($ticket_input['id']);
      $this->checkInput($ticket, $tickets_id, $ticket_input);

      //link between groupe1 and ticket will not exist
      $ticketGroup = new \Group_Ticket();
      $this->boolean(
         $ticketGroup->getFromDBByCrit([
            'tickets_id'         => $tickets_id,
            'groups_id'          => $group_id1,
            'type'               => \CommonITILActor::REQUESTER
         ])
      )->isFalse();

      //link between groupe2 and ticket will not exist
      $this->boolean(
         $ticketGroup->getFromDBByCrit([
            'tickets_id'         => $tickets_id,
            'groups_id'          => $group_id2,
            'type'               => \CommonITILActor::REQUESTER
         ])
      )->isFalse();

      //link between groupe2 and ticket will not exist
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
                                 "users_id" => $userNormal->fields['id']]
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

   public function testValidationCriteria() {
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

   public function testValidationAction() {
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

   public function testITILCategoryCode() {
      $this->login();

      // Create rule
      $ruleticket = new \RuleTicket();
      $rulecrit   = new \RuleCriteria();
      $ruleaction = new \RuleAction();

      $ruletid = $ruleticket->add($ruletinput = [
         'name'         => 'test category code',
         'match'        => 'AND',
         'is_active'    => 1,
         'sub_type'     => 'RuleTicket',
         'condition'    => \RuleTicket::ONADD,
         'is_recursive' => 1,
      ]);
      $this->checkInput($ruleticket, $ruletid, $ruletinput);

      // Create criteria to check if category code is R
      $crit_id = $rulecrit->add($crit_input = [
         'rules_id'  => $ruletid,
         'criteria'  => 'itilcategories_id_code',
         'condition' => \Rule::PATTERN_IS,
         'pattern'   => 'R',
      ]);
      $this->checkInput($rulecrit, $crit_id, $crit_input);

      // Create action to put impact to very low
      $action_id = $ruleaction->add($action_input = [
         'rules_id'    => $ruletid,
         'action_type' => 'assign',
         'field'       => 'impact',
         'value'       => 1,
      ]);
      $this->checkInput($ruleaction, $action_id, $action_input);

      // Create new group
      $category = new \ITILCategory();
      $category_id = $category->add($category_input = [
         "name" => "group1",
         "code" => "R"
      ]);
      $this->checkInput($category, $category_id, $category_input);

      // Check ticket that trigger rule on creation
      $ticket = new \Ticket();
      $tickets_id = $ticket->add($ticket_input = [
         'name'              => 'test category code',
         'content'           => 'test category code',
         'itilcategories_id' => $category_id
      ]);
      $this->checkInput($ticket, $tickets_id, $ticket_input);

      // Check that the rule was executed
      $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
      $this->integer($ticket->fields['impact'])->isEqualTo(1);

      // Create another ticket that doesn't match the rule
      $tickets_id = $ticket->add($ticket_input = [
         'name'              => 'test category code',
         'content'           => 'test category code',
         'itilcategories_id' => 0
      ]);
      $this->checkInput($ticket, $tickets_id, $ticket_input);

      // Check that the rule was NOT executed
      $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
      $this->integer($ticket->fields['impact'])->isNotEqualTo(1);
   }

   /**
    * Test contract type criteria
    */
   public function testContractType() {
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
}
