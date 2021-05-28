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

use CommonITILActor;
use CommonITILValidation;
use \DbTestCase;
use Group_User;
use Ticket_User;
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
}
