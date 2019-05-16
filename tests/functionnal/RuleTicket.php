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
      $this->_createRule(\RuleTicket::ONADD);

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
      $this->_createRule(\RuleTicket::ONUPDATE);

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

   private function _createRule($condition) {
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
}
