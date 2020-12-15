<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

/* Test for inc/tickettask.class.php */

class TicketTask extends DbTestCase {

   /**
    * Create a new ticket
    *
    * @param boolean $as_object Return Ticket object or its id
    *
    * @return integer|Ticket
    */
   private function getNewTicket($as_object = false) {
      //create reference ticket
      $ticket = new \Ticket();
      $this->integer(
         (int)$ticket->add([
            'name'               => 'ticket title',
            'description'        => 'a description',
            'content'            => '',
            'entities_id'        => getItemByTypeName('Entity', '_test_root_entity', true),
            '_users_id_assign'   => getItemByTypeName('User', 'tech', true)
         ])
      )->isGreaterThan(0);

      $this->boolean($ticket->isNewItem())->isFalse();
      $tid = (int)$ticket->fields['id'];

      $this->hasSessionMessage(
         INFO, [
            "Your ticket has been registered. (Ticket: <a href='".\Ticket::getFormURLWithID($tid)."'>$tid</a>)"
         ]
      );

      return ($as_object ? $ticket : $tid);
   }

   public function testSchedulingAndRecall() {
      $this->login();
      $ticketId = $this->getNewTicket();
      $uid = getItemByTypeName('User', TU_USER, true);

      $date_begin = new \DateTime(); // ==> now
      $date_begin_string = $date_begin->format('Y-m-d H:i:s');

      $date_end = new \DateTime(); // ==> +2days
      $date_end->add(new \DateInterval('P2D'));
      $date_end_string = $date_end->format('Y-m-d H:i:s');

      //create one task with schedule and recall
      $task = new \TicketTask();
      $task_id = $task->add([
         'state'              => \Planning::TODO,
         'tickets_id'         => $ticketId,
         'tasktemplates_id'   => '0',
         'taskcategories_id'  => '0',
         'actiontime'         => "172800", //1hours
         'content'            => "Task with schedule and recall",
         'users_id_tech'      => $uid,
         '_plan'              => ['begin'  => $date_begin_string],
         'plan'               => ['begin'        => $date_begin_string, //start date
                                  'end'          => $date_end_string, //end date auto calculate
                                  '_duraction'   => "172800"],  //period (2 days)
         '_planningrecall'    => ['before_time' => '14400', //recall 4 hours
                                  'itemtype'    => 'TicketTask',
                                  'users_id'    => $uid,
                                  'field'       => 'begin', //default
                                 ]
      ]);
      $this->integer($task_id)->isGreaterThan(0);

      //load plannig schedule with recall
      $recall = new \PlanningRecall();

      //calcul 'when'
      $when = date("Y-m-d H:i:s", strtotime($task->fields['begin']) - 14400);
      $this->boolean($recall->getFromDBByCrit(['before_time'   => '14400', //recall 4 hours
                                                'itemtype'     => 'TicketTask',
                                                'items_id'     => $task_id,
                                                'users_id'     => $uid,
                                                'when'         => $when,
                                             ]))->isTrue();

      //create one task with schedule and without recall
      $task = new \TicketTask();
      $task_id = $task->add([
         'state'              => \Planning::TODO,
         'tickets_id'         => $ticketId,
         'tasktemplates_id'   => '0',
         'taskcategories_id'  => '0',
         'actiontime'         => "172800", //2 days
         'content'            => "Task with schedule and without recall",
         'users_id_tech'      => $uid,
         '_plan'              => ['begin' => $date_begin_string],
         'plan'               => ['begin'       => $date_begin_string, // start date
                                  'end'         => $date_end_string, //end date auto calculate
                                  '_duraction'  => "172800"],  //period (2 days)
         '_planningrecall' => ['before_time' => '-10', //recall to none
                               'itemtype'    => 'TicketTask',
                               'users_id'    => $uid,
                               'field'       => 'begin', //default
                              ]
      ]);
      $this->integer($task_id)->isGreaterThan(0);

      $this->hasSessionMessage(
         WARNING, [
            sprintf(
               'The user <a href="/glpi/front/user.form.php?id=%s">_test_user</a> is busy at the selected timeframe.<br/>- Ticket task: from %s to %s:<br/><a href=\'/glpi/front/ticket.form.php?id=%s&amp;forcetab=TicketTask$1\'>ticket title</a><br/>',
               $uid,
               $date_begin->format('Y-m-d H:i'),
               $date_end->format('Y-m-d H:i'),
               $ticketId
            )
         ]
      );

      //load schedule //which return false (not exist yet without recall)
      $recall = new \PlanningRecall();
      $this->boolean($recall->getFromDBByCrit(['itemtype'   => 'TicketTask',
                                                'items_id'  => $task_id,
                                                'users_id'  => $uid,
                                             ]))->isFalse();

      //update task schedule with recall
      $this->boolean(
      $task->update([
         'id'                 => $task_id,
         'state'              => \Planning::TODO,
         'tickets_id'         => $ticketId,
         'tasktemplates_id'   => '0',
         'taskcategories_id'  => '0',
         'actiontime'         => "172800", //2 days
         'content'            => "Task with schedule and without recall",
         'users_id_tech'      => $uid,
         'actiontime'         => "172800", //1hours  //period (2 days)
         '_plan'              => ['begin' => $date_begin_string],
         'plan'               => ['begin'       => $date_begin_string, // start date
                                  'end'         => $date_end_string, //end date auto calculate
                                  '_duraction'  => "172800"],  //period (2 days)
         '_planningrecall' => ['before_time' => '900',
                               'itemtype'    => 'TicketTask',
                               'users_id'    => $uid,
                               'field'       => 'begin', //default
                              ]
      ])
      )->isTrue();

      $this->hasSessionMessage(
         WARNING, [
            sprintf(
               'The user <a href="/glpi/front/user.form.php?id=%s">_test_user</a> is busy at the selected timeframe.<br/>- Ticket task: from %s to %s:<br/><a href=\'/glpi/front/ticket.form.php?id=%s&amp;forcetab=TicketTask$1\'>ticket title</a><br/>',
               $uid,
               $date_begin->format('Y-m-d H:i'),
               $date_end->format('Y-m-d H:i'),
               $ticketId
            )
         ]
      );

      //load planning recall
      $recall = new \PlanningRecall();

      //calcul when
      $when = date("Y-m-d H:i:s", strtotime($task->fields['begin']) - 900);
      $this->boolean($recall->getFromDBByCrit(['before_time'  => '900',
                                                'itemtype'    => 'TicketTask',
                                                'items_id'    => $task_id,
                                                'users_id'    => $uid,
                                                'when'        => $when,
                                             ]))->isTrue();
   }

   public function testGetTaskList() {

      $this->login();
      $ticketId = $this->getNewTicket();
      $uid = getItemByTypeName('User', TU_USER, true);

      $tasksstates = [
         \Planning::TODO,
         \Planning::TODO,
         \Planning::INFO
      ];
      //create few tasks
      $task = new \TicketTask();
      foreach ($tasksstates as $taskstate) {
         $this->integer(
            $task->add([
               'content'      => sprintf('Task with "%s" state', $taskstate),
               'state'        => $taskstate,
               'tickets_id'   => $ticketId,
               'users_id_tech'=> $uid
            ])
         )->isGreaterThan(0);
      }

      $iterator = $task::getTaskList('todo', false);
      //we create two ones plus the one in bootstrap data
      $this->integer(count($iterator))->isIdenticalTo(3);

      $iterator = $task::getTaskList('todo', true);
      $this->boolean($iterator)->isFalse();

      $_SESSION['glpigroups'] = [42, 1337];
      $iterator = $task::getTaskList('todo', true);
      //no task for those groups
      $this->integer(count($iterator))->isIdenticalTo(0);
   }

   public function testCentralTaskList() {
      $this->login();
      $ticketId = $this->getNewTicket();
      $uid = getItemByTypeName('User', TU_USER, true);

      $tasksstates = [
         \Planning::TODO,
         \Planning::TODO,
         \Planning::TODO,
         \Planning::INFO,
         \Planning::INFO
      ];
      //create few tasks
      $task = new \TicketTask();
      foreach ($tasksstates as $taskstate) {
         $this->integer(
            $task->add([
               'content'      => sprintf('Task with "%s" state', $taskstate),
               'state'        => $taskstate,
               'tickets_id'   => $ticketId,
               'users_id_tech'=> $uid
            ])
         )->isGreaterThan(0);
      }

      //How could we test there are 4 matching links?
      $this->output(
         function () {
            \TicketTask::showCentralList(0, 'todo', false);
         }
      )
         ->contains("Ticket tasks to do <span class='primary-bg primary-fg count'>4</span>")
         ->matches("/a id='[^']+' href='\/glpi\/front\/ticket.form.php\?id=\d+[^']+'>/");

      //How could we test there are 2 matching links?
      $this->output(
         function () {
            $_SESSION['glpidisplay_count_on_home'] = 2;
            \TicketTask::showCentralList(0, 'todo', false);
            unset($_SESSION['glpidisplay_count_on_home']);
         }
      )
         ->contains("Ticket tasks to do <span class='primary-bg primary-fg count'>2 on 4</span>")
         ->matches("/a id='[^']+' href='\/glpi\/front\/ticket.form.php\?id=\d+[^']+'>/");
   }

   public function testPlanningConflict() {
      $this->login();

      $user = getItemByTypeName('User', 'tech');
      $users_id = (int)$user->fields['id'];

      $ticket = $this->getNewTicket(true);
      $tid = $ticket->fields['id'];

      $ttask = new \TicketTask();
      $this->integer(
         (int)$ttask->add([
            'name'               => 'first test, whole period',
            'content'            => 'first test, whole period',
            'tickets_id'         => $tid,
            'plan'               => [
               'begin'  => '2019-08-10',
               'end'    => '2019-08-20'
            ],
            'users_id_tech'      => $users_id,
            'tasktemplates_id'   => 0
         ])
      )->isGreaterThan(0);
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isEmpty();

      $this->integer(
         (int)$ttask->add([
            'name'               => 'test, subperiod',
            'content'            => 'test, subperiod',
            'tickets_id'         => $tid,
            'plan'               => [
               'begin'   => '2019-08-13',
               'end'     => '2019-08-14'
            ],
            'users_id_tech'      => $users_id,
            'tasktemplates_id'   => 0
         ])
      )->isGreaterThan(0);

      $usr_str = '<a href="' . $user->getFormURLWithID($users_id) . '">' . $user->getName() . '</a>';
      $this->hasSessionMessage(
         WARNING, [
            "The user $usr_str is busy at the selected timeframe.<br/>- Ticket task: from 2019-08-13  to 2019-08-14 :<br/><a href='".
            $ticket->getFormURLWithID($tid)."&amp;forcetab=TicketTask$1'>ticket title</a><br/>"
         ]
      );
      $this->integer($tid)->isGreaterThan(0);

      //add another task to be updated
      $this->integer(
         (int)$ttask->add([
            'name'               => 'first test, whole period',
            'content'            => 'first test, whole period',
            'tickets_id'         => $tid,
            'plan'               => [
               'begin'  => '2018-08-10',
               'end'    => '2018-08-20'
            ],
            'users_id_tech'      => $users_id,
            'tasktemplates_id'   => 0
         ])
      )->isGreaterThan(0);
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isEmpty();

      $this->boolean($ttask->getFromDB($ttask->fields['id']))->isTrue();

      $this->boolean(
         $ttask->update([
            'id'           => $ttask->fields['id'],
            'tickets_id'   => $tid,
            'plan'               => [
               'begin'  => str_replace('2018', '2019', $ttask->fields['begin']),
               'end'    => str_replace('2018', '2019', $ttask->fields['end'])
            ],
            'users_id_tech'      => $users_id,
         ])
      )->isTrue();

      $usr_str = '<a href="' . $user->getFormURLWithID($users_id) . '">' . $user->getName() . '</a>';
      $this->hasSessionMessage(
         WARNING, [
            "The user $usr_str is busy at the selected timeframe.<br/>- Ticket task: from 2019-08-10 00:00 to 2019-08-20 00:00:<br/><a href='".
            $ticket->getFormURLWithID($tid)."&amp;forcetab=TicketTask$1'>ticket title</a><br/>- Ticket task: from 2019-08-13 00:00 to 2019-08-14 00:00:<br/><a href='".$ticket
            ->getFormURLWithID($tid)."&amp;forcetab=TicketTask$1'>ticket title</a><br/>"
         ]
      );
   }
}
