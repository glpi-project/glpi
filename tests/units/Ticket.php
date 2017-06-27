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

namespace tests\units;

use \DbTestCase;

/* Test for inc/ticket.class.php */

class Ticket extends DbTestCase {

   public function ticketProvider() {
      return [
         'single requester' => array(
            array(
               '_users_id_requester' => '3'
            ),
         ),
         'single unknown requester' => array(
            array(
               '_users_id_requester'         => '0',
               '_users_id_requester_notif'   => array(
                  'use_notification'   => array('1'),
                  'alternative_email'  => array('unknownuser@localhost.local')
               ),
            ),
         ),
         'multiple requesters' => array(
            array(
               '_users_id_requester' => array('3', '5'),
            ),
         ),
         'multiple mixed requesters' => array(
            array(
               '_users_id_requester'         => array('3', '5', '0'),
               '_users_id_requester_notif'   => array(
                  'use_notification'   => array('1', '0', '1'),
                  'alternative_email'  => array('','', 'unknownuser@localhost.local')
               ),
            ),
         ),
         'single observer' => array(
            array(
               '_users_id_observer' => '3'
            ),
         ),
         'multiple observers' => array(
            array(
               '_users_id_observer' => array('3', '5'),
            ),
         ),
         'single assign' => array(
            array(
               '_users_id_assign' => '3'
            ),
         ),
         'multiple assigns' => array(
            array(
               '_users_id_assign' => array('3', '5'),
            ),
         ),
      ];
   }

   /**
    * @dataProvider ticketProvider
    */
   public function testCreateTicketWithActors($ticketActors) {
      $ticket = new \Ticket();
      $this->integer((int)$ticket->add(array(
            'name'         => 'ticket title',
            'description'  => 'a description',
            'content'      => ''
      ) + $ticketActors))->isGreaterThan(0);

      $this->boolean($ticket->isNewItem())->isFalse();
      $ticketId = $ticket->getID();

      foreach ($ticketActors as $actorType => $actorsList) {
         // Convert single actor (scalar value) to array
         if (!is_array($actorsList)) {
            $actorsList = array($actorsList);
         }

         // Check all actors are assigned to the ticket
         foreach ($actorsList as $index => $actor) {
            $notify = isset($actorList['_users_id_requester_notif']['use_notification'][$index])
                      ? $actorList['_users_id_requester_notif']['use_notification'][$index]
                      : 1;
            $alternateEmail = isset($actorList['_users_id_requester_notif']['use_notification'][$index])
                              ? $actorList['_users_id_requester_notif']['alternative_email'][$index]
                              : '';
            switch ($actorType) {
               case '_users_id_requester':
                  //$this->_testTicketUser($ticket, $actor, \CommonITILActor::REQUESTER, $notify, $alternateEmail);
                  break;
               case '_users_id_observer':
                  $this->_testTicketUser($ticket, $actor, \CommonITILActor::OBSERVER, $notify, $alternateEmail);
                  break;
               case '_users_id_assign':
                  $this->_testTicketUser($ticket, $actor, \CommonITILActor::ASSIGN, $notify, $alternateEmail);
                  break;
            }
         }
      }
   }

   public function testTicketSolution() {
      session_unset();
      $_SESSION['glpicronuserrunning'] = "cron_phpunit";
      $_SESSION['glpi_use_mode']       = \Session::NORMAL_MODE;
      $_SESSION['glpi_currenttime']    = date("Y-m-d H:i:s");

      $uid = getItemByTypeName('User', TU_USER, true);
      $ticket = new \Ticket();
      $this->integer((int)$ticket->add([
         'name'               => 'ticket title',
         'description'        => 'a description',
         'content'            => '',
         '_users_id_assign'   => $uid
      ]))->isGreaterThan(0);

      $this->boolean($ticket->isNewItem())->isFalse();
      $this->variable($ticket->getField('status'))->isIdenticalTo($ticket::ASSIGNED);
      $ticketId = $ticket->getID();

      $this->_testTicketUser(
         $ticket,
         $uid,
         \CommonITILActor::ASSIGN,
         1,
         ''
      );

      $ticket->update([
         'id' => $ticket->getID(),
         'solution'  => 'Current friendly ticket\r\nis solved!'
      ]);
      //reload from DB
      $this->boolean($ticket->getFromDB($ticket->getID()))->isTrue();

      $this->variable($ticket->getField('status'))->isEqualTo($ticket::CLOSED);
      $this->string($ticket->getField('solution'))->isIdenticalTo("Current friendly ticket\r\nis solved!");
   }

   protected function _testTicketUser(\Ticket $ticket, $actor, $role, $notify, $alternateEmail) {
      if ($actor > 0) {
         $user = new \User();
         $this->boolean($user->getFromDB($actor))->isTrue();
         $this->boolean($user->isNewItem())->isFalse();

         $ticketUser = new \Ticket_User();
         $this->boolean($ticketUser->getFromDBForItems($ticket, $user))->isTrue();
      } else {
         $ticketId = $ticket->getID();
         $ticketUser = new \Ticket_User();
         $this->boolean(
            $ticketUser->getFromDBByQuery(
               "WHERE `tickets_id` = '$ticketId'
                  AND `users_id` = '0'
                  AND `alternative_email` = '$alternateEmail'"
            )
         )->isTrue();
      }
      $this->boolean($ticketUser->isNewItem())->isFalse();
      $this->variable($ticketUser->getField('type'))->isEqualTo($role);
      $this->variable($ticketUser->getField('use_notification'))->isEqualTo($notify);
   }

   public function testTasksFromTemplate() {
      // 1- create a task category
      $taskcat    = new \TaskCategory;
      $taskcat_id = $taskcat->add([
         'name' => 'my task cat',
      ]);
      $this->boolean($taskcat->isNewItem())->isFalse();

      // 2- create some task templates
      $tasktemplate = new \TaskTemplate;
      $ttA_id          = $tasktemplate->add([
         'name'              => 'my task template A',
         'content'           => 'my task template A',
         'taskcategories_id' => $taskcat_id,
         'actiontime'        => 60,
         'is_private'        => true,
         'users_id_tech'     => 2,
         'groups_id_tech'    => 0,
         'state'             => \Planning::INFO,
      ]);
      $this->boolean($tasktemplate->isNewItem())->isFalse();
      $ttB_id          = $tasktemplate->add([
         'name'              => 'my task template B',
         'content'           => 'my task template B',
         'taskcategories_id' => $taskcat_id,
         'actiontime'        => 120,
         'is_private'        => false,
         'users_id_tech'     => 2,
         'groups_id_tech'    => 0,
         'state'             => \Planning::TODO,
      ]);
      $this->boolean($tasktemplate->isNewItem())->isFalse();

      // 3 - create a ticket template with the task templates in predefined fields
      $tickettemplate    = new \TicketTemplate;
      $tickettemplate_id = $tickettemplate->add([
         'name' => 'my ticket template',
      ]);
      $this->boolean($tickettemplate->isNewItem())->isFalse();
      $ttp = new \TicketTemplatePredefinedField();
      $ttp->add([
         'tickettemplates_id' => $tickettemplate_id,
         'num'                => '175',
         'value'              => $ttA_id,
      ]);
      $this->boolean($ttp->isNewItem())->isFalse();
      $ttp->add([
         'tickettemplates_id' => $tickettemplate_id,
         'num'                => '175',
         'value'              => $ttB_id,
      ]);
      $this->boolean($ttp->isNewItem())->isFalse();

      // 4 - create a ticket category using the ticket template
      $itilcat    = new \ITILCategory;
      $itilcat_id = $itilcat->add([
         'name'                        => 'my itil category',
         'tickettemplates_id_incident' => $tickettemplate_id,
         'tickettemplates_id_demand'   => $tickettemplate_id,
         'is_incident'                 => true,
         'is_request'                  => true,
      ]);
      $this->boolean($itilcat->isNewItem())->isFalse();

      // 5 - create a ticket using the ticket category
      $ticket     = new \Ticket;
      $tickets_id = $ticket->add([
         'name'                => 'test task template',
         'content'             => 'test task template',
         'itilcategories_id'   => $itilcat_id,
         '_tickettemplates_id' => $tickettemplate_id,
         '_tasktemplates_id'   => [$ttA_id, $ttB_id],
      ]);
      $this->boolean($ticket->isNewItem())->isFalse();

      // 6 - check creation of the tasks
      $tickettask = new \TicketTask;
      $found_tasks = $tickettask->find("`tickets_id` = $tickets_id", "id ASC");

      // 6.1 -> check first task
      $taskA = array_shift($found_tasks);
      $this->string($taskA['content'])->isIdenticalTo('my task template A');
      $this->variable($taskA['taskcategories_id'])->isEqualTo($taskcat_id);
      $this->variable($taskA['actiontime'])->isEqualTo(60);
      $this->variable($taskA['is_private'])->isEqualTo(1);
      $this->variable($taskA['users_id_tech'])->isEqualTo(2);
      $this->variable($taskA['groups_id_tech'])->isEqualTo(0);
      $this->variable($taskA['state'])->isEqualTo(\Planning::INFO);

      // 6.2 -> check second task
      $taskB = array_shift($found_tasks);
      $this->string($taskB['content'])->isIdenticalTo('my task template B');
      $this->variable($taskB['taskcategories_id'])->isEqualTo($taskcat_id);
      $this->variable($taskB['actiontime'])->isEqualTo(120);
      $this->variable($taskB['is_private'])->isEqualTo(0);
      $this->variable($taskB['users_id_tech'])->isEqualTo(2);
      $this->variable($taskB['groups_id_tech'])->isEqualTo(0);
      $this->variable($taskB['state'])->isEqualTo(\Planning::TODO);
   }

   public function testAcls() {
      $ticket = new \Ticket();
      //to fix an undefined index
      $_SESSION["glpiactiveprofile"]["interface"] = '';
      $this->boolean((boolean)$ticket->canAdminActors())->isFalse();
      $this->boolean((boolean)$ticket->canAssign())->isFalse();
      $this->boolean((boolean)$ticket->canAssignToMe())->isFalse();
      $this->boolean((boolean)$ticket->canUpdate())->isFalse();
      $this->boolean((boolean)$ticket->canView())->isFalse();
      $this->boolean((boolean)$ticket->canViewItem())->isFalse();
      $this->boolean((boolean)$ticket->canSolve())->isFalse();
      $this->boolean((boolean)$ticket->canApprove())->isFalse();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canCreateItem())->isFalse();
      $this->boolean((boolean)$ticket->canUpdateItem())->isFalse();
      $this->boolean((boolean)$ticket->canRequesterUpdateItem())->isFalse();
      $this->boolean((boolean)$ticket->canDelete())->isFalse();
      $this->boolean((boolean)$ticket->canDeleteItem())->isFalse();
      $this->boolean((boolean)$ticket->canAddItem('Document'))->isFalse();
      $this->boolean((boolean)$ticket->canAddItem('Ticket_Cost'))->isFalse();
      $this->boolean((boolean)$ticket->canAddFollowups())->isFalse();

      $this->login();
      $this->setEntity('Root entity', true);
      $ticket = new \Ticket();
      $this->boolean((boolean)$ticket->canAdminActors())->isTrue(); //=> get 2
      $this->boolean((boolean)$ticket->canAssign())->isTrue(); //=> get 8192
      $this->boolean((boolean)$ticket->canAssignToMe())->isTrue();
      $this->boolean((boolean)$ticket->canUpdate())->isTrue();
      $this->boolean((boolean)$ticket->canView())->isTrue();
      $this->boolean((boolean)$ticket->canViewItem())->isTrue();
      $this->boolean((boolean)$ticket->canSolve())->isTrue();
      $this->boolean((boolean)$ticket->canApprove())->isFalse();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canCreateItem())->isTrue();
      $this->boolean((boolean)$ticket->canUpdateItem())->isTrue();
      $this->boolean((boolean)$ticket->canRequesterUpdateItem())->isFalse();
      $this->boolean((boolean)$ticket->canDelete())->isTrue();
      $this->boolean((boolean)$ticket->canDeleteItem())->isTrue();
      $this->boolean((boolean)$ticket->canAddItem('Document'))->isTrue();
      $this->boolean((boolean)$ticket->canAddItem('Ticket_Cost'))->isTrue();
      $this->boolean((boolean)$ticket->canAddFollowups())->isTrue();

      $ticket = getItemByTypeName('Ticket', '_ticket01');
      $this->boolean((boolean)$ticket->canAdminActors())->isTrue(); //=> get 2
      $this->boolean((boolean)$ticket->canAssign())->isTrue(); //=> get 8192
      $this->boolean((boolean)$ticket->canAssignToMe())->isTrue();
      $this->boolean((boolean)$ticket->canUpdate())->isTrue();
      $this->boolean((boolean)$ticket->canView())->isTrue();
      $this->boolean((boolean)$ticket->canViewItem())->isTrue();
      $this->boolean((boolean)$ticket->canSolve())->isTrue();
      $this->boolean((boolean)$ticket->canApprove())->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canCreateItem())->isTrue();
      $this->boolean((boolean)$ticket->canUpdateItem())->isTrue();
      $this->boolean((boolean)$ticket->canRequesterUpdateItem())->isFalse();
      $this->boolean((boolean)$ticket->canDelete())->isTrue();
      $this->boolean((boolean)$ticket->canDeleteItem())->isTrue();
      $this->boolean((boolean)$ticket->canAddItem('Document'))->isTrue();
      $this->boolean((boolean)$ticket->canAddItem('Ticket_Cost'))->isTrue();
      $this->boolean((boolean)$ticket->canAddFollowups())->isTrue();
   }

   public function testPostOnlyAcls() {
      $auth = new \Auth();
      $this->boolean((boolean)$auth->Login('post-only', 'postonly', true))->isTrue();

      $ticket = new \Ticket();
      $this->boolean((boolean)$ticket->canAdminActors())->isFalse();
      $this->boolean((boolean)$ticket->canAssign())->isFalse();
      $this->boolean((boolean)$ticket->canAssignToMe())->isFalse();
      $this->boolean((boolean)$ticket->canUpdate())->isTrue();
      $this->boolean((boolean)$ticket->canView())->isTrue();
      $this->boolean((boolean)$ticket->canViewItem())->isFalse();
      $this->boolean((boolean)$ticket->canSolve())->isFalse();
      $this->boolean((boolean)$ticket->canApprove())->isFalse();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canCreateItem())->isTrue();
      $this->boolean((boolean)$ticket->canUpdateItem())->isFalse();
      $this->boolean((boolean)$ticket->canRequesterUpdateItem())->isFalse();
      $this->boolean((boolean)$ticket->canDelete())->isTrue();
      $this->boolean((boolean)$ticket->canDeleteItem());
      $this->boolean((boolean)$ticket->canAddItem('Document'));
      $this->boolean((boolean)$ticket->canAddItem('Ticket_Cost'))->isFalse();
      $this->boolean((boolean)$ticket->canAddFollowups())->isFalse();

      $this->integer(
         (int)$ticket->add([
            'name'         => '',
            'description'  => 'A ticket to check ACLS',
            'content'      => ''
         ])
      )->isGreaterThan(0);

      //reload ticket from DB
      $this->boolean((boolean)$ticket->getFromDB($ticket->getID()))->isTrue();
      $this->boolean((boolean)$ticket->canAdminActors())->isFalse();
      $this->boolean((boolean)$ticket->canAssign())->isFalse();
      $this->boolean((boolean)$ticket->canAssignToMe())->isFalse();
      $this->boolean((boolean)$ticket->canUpdate())->isTrue();
      $this->boolean((boolean)$ticket->canView())->isTrue();
      $this->boolean((boolean)$ticket->canViewItem())->isTrue();
      $this->boolean((boolean)$ticket->canSolve())->isFalse();
      $this->boolean((boolean)$ticket->canApprove())->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canCreateItem())->isTrue();
      $this->boolean((boolean)$ticket->canUpdateItem())->isTrue();
      $this->boolean((boolean)$ticket->canRequesterUpdateItem())->isTrue();
      $this->boolean((boolean)$ticket->canDelete())->isTrue();
      $this->boolean((boolean)$ticket->canDeleteItem())->isTrue();
      $this->boolean((boolean)$ticket->canAddItem('Document'))->isTrue();
      $this->boolean((boolean)$ticket->canAddItem('Ticket_Cost'))->isTrue();
      $this->boolean((boolean)$ticket->canAddFollowups())->isTrue();

      $uid = getItemByTypeName('User', TU_USER, true);
      //add a followup to the ticket
      $fup = new \TicketFollowup();
      $this->integer(
         (int)$fup->add([
            'tickets_id'   => $ticket->getID(),
            'users_id'     => $uid,
            'content'      => 'A simple followup'
         ])
      )->isGreaterThan(0);

      $this->boolean((boolean)$ticket->getFromDB($ticket->getID()))->isTrue();
      $this->boolean((boolean)$ticket->canAdminActors())->isFalse();
      $this->boolean((boolean)$ticket->canAssign())->isFalse();
      $this->boolean((boolean)$ticket->canAssignToMe())->isFalse();
      $this->boolean((boolean)$ticket->canUpdate())->isTrue();
      $this->boolean((boolean)$ticket->canView())->isTrue();
      $this->boolean((boolean)$ticket->canViewItem())->isTrue();
      $this->boolean((boolean)$ticket->canSolve())->isFalse();
      $this->boolean((boolean)$ticket->canApprove())->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canCreateItem())->isTrue();
      $this->boolean((boolean)$ticket->canUpdateItem())->isFalse();
      $this->boolean((boolean)$ticket->canRequesterUpdateItem())->isFalse();
      $this->boolean((boolean)$ticket->canDelete())->isTrue();
      $this->boolean((boolean)$ticket->canDeleteItem())->isFalse();
      $this->boolean((boolean)$ticket->canAddItem('Document'))->isTrue();
      $this->boolean((boolean)$ticket->canAddItem('Ticket_Cost'))->isFalse();
      $this->boolean((boolean)$ticket->canAddFollowups())->isTrue();
   }

   public function testTechAcls() {
      $auth = new \Auth();
      $this->boolean((boolean)$auth->Login('tech', 'tech', true))->isTrue();

      $ticket = new \Ticket();
      $this->boolean((boolean)$ticket->canAdminActors())->isTrue();
      $this->boolean((boolean)$ticket->canAssign())->isFalse();
      $this->boolean((boolean)$ticket->canAssignToMe())->isTrue();
      $this->boolean((boolean)$ticket->canUpdate())->isTrue();
      $this->boolean((boolean)$ticket->canView())->isTrue();
      $this->boolean((boolean)$ticket->canViewItem())->isTrue();
      $this->boolean((boolean)$ticket->canSolve())->isTrue();
      $this->boolean((boolean)$ticket->canApprove())->isFalse();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canCreateItem())->isTrue();
      $this->boolean((boolean)$ticket->canUpdateItem())->isTrue();
      $this->boolean((boolean)$ticket->canRequesterUpdateItem())->isFalse();
      $this->boolean((boolean)$ticket->canDelete())->isFalse();
      $this->boolean((boolean)$ticket->canDeleteItem())->isFalse();
      $this->boolean((boolean)$ticket->canAddItem('Document'))->isTrue();
      $this->boolean((boolean)$ticket->canAddItem('Ticket_Cost'))->isTrue();
      $this->boolean((boolean)$ticket->canAddFollowups())->isTrue();

      $this->integer(
         (int)$ticket->add([
            'name'         => '',
            'description'  => 'A ticket to check ACLS',
            'content'      => ''
         ])
      )->isGreaterThan(0);

      //reload ticket from DB
      $this->boolean((boolean)$ticket->getFromDB($ticket->getID()))->isTrue();
      $this->boolean((boolean)$ticket->canAdminActors())->isTrue();
      $this->boolean((boolean)$ticket->canAssign())->isFalse();
      $this->boolean((boolean)$ticket->canAssignToMe())->isFalse();
      $this->boolean((boolean)$ticket->canUpdate())->isTrue();
      $this->boolean((boolean)$ticket->canView())->isTrue();
      $this->boolean((boolean)$ticket->canViewItem())->isTrue();
      $this->boolean((boolean)$ticket->canSolve())->isTrue();
      $this->boolean((boolean)$ticket->canApprove())->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canCreateItem())->isTrue();
      $this->boolean((boolean)$ticket->canUpdateItem())->isTrue();
      $this->boolean((boolean)$ticket->canRequesterUpdateItem())->isTrue();
      $this->boolean((boolean)$ticket->canDelete())->isFalse();
      $this->boolean((boolean)$ticket->canDeleteItem())->isFalse();
      $this->boolean((boolean)$ticket->canAddItem('Document'))->isTrue();
      $this->boolean((boolean)$ticket->canAddItem('Ticket_Cost'))->isTrue();
      $this->boolean((boolean)$ticket->canAddFollowups())->isTrue();

      $uid = getItemByTypeName('User', TU_USER, true);
      //add a followup to the ticket
      $fup = new \TicketFollowup();
      $this->integer(
         (int)$fup->add([
            'tickets_id'   => $ticket->getID(),
            'users_id'     => $uid,
            'content'      => 'A simple followup'
         ])
      )->isGreaterThan(0);

      $this->boolean((boolean)$ticket->getFromDB($ticket->getID()))->isTrue();
      $this->boolean((boolean)$ticket->canAdminActors())->isTrue();
      $this->boolean((boolean)$ticket->canAssign())->isFalse();
      $this->boolean((boolean)$ticket->canAssignToMe())->isFalse();
      $this->boolean((boolean)$ticket->canUpdate())->isTrue();
      $this->boolean((boolean)$ticket->canView())->isTrue();
      $this->boolean((boolean)$ticket->canViewItem())->isTrue();
      $this->boolean((boolean)$ticket->canSolve())->isTrue();
      $this->boolean((boolean)$ticket->canApprove())->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canCreateItem())->isTrue();
      $this->boolean((boolean)$ticket->canUpdateItem())->isTrue();
      $this->boolean((boolean)$ticket->canRequesterUpdateItem())->isFalse();
      $this->boolean((boolean)$ticket->canDelete())->isFalse();
      $this->boolean((boolean)$ticket->canDeleteItem())->isFalse();
      $this->boolean((boolean)$ticket->canAddItem('Document'))->isTrue();
      $this->boolean((boolean)$ticket->canAddItem('Ticket_Cost'))->isTrue();
      $this->boolean((boolean)$ticket->canAddFollowups())->isTrue();

      //drop update ticket right from tech profile
      global $DB;
      $query = "UPDATE glpi_profilerights SET rights = 168965 WHERE profiles_id = 6 AND name = 'ticket'";
      $DB->query($query);
      //ACLs have changed: login again.
      $this->boolean((boolean)$auth->Login('tech', 'tech', true))->isTrue();

      //reset rights. Done here so ACLs are reset even if tests fails.
      $query = "UPDATE glpi_profilerights SET rights = 168967 WHERE profiles_id = 6 AND name = 'ticket'";
      $DB->query($query);

      $this->boolean((boolean)$ticket->getFromDB($ticket->getID()))->isTrue();
      $this->boolean((boolean)$ticket->canAdminActors())->isFalse();
      $this->boolean((boolean)$ticket->canAssign())->isFalse();
      $this->boolean((boolean)$ticket->canAssignToMe())->isFalse();
      $this->boolean((boolean)$ticket->canUpdate())->isTrue();
      $this->boolean((boolean)$ticket->canView())->isTrue();
      $this->boolean((boolean)$ticket->canViewItem())->isTrue();
      $this->boolean((boolean)$ticket->canSolve())->isTrue();
      $this->boolean((boolean)$ticket->canApprove())->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canCreateItem())->isTrue();
      $this->boolean((boolean)$ticket->canUpdateItem())->isTrue();
      $this->boolean((boolean)$ticket->canRequesterUpdateItem())->isFalse();
      $this->boolean((boolean)$ticket->canDelete())->isFalse();
      $this->boolean((boolean)$ticket->canDeleteItem())->isFalse();
      $this->boolean((boolean)$ticket->canAddItem('Document'))->isTrue();
      $this->boolean((boolean)$ticket->canAddItem('Ticket_Cost'))->isFalse();
      $this->boolean((boolean)$ticket->canAddFollowups())->isTrue();

      $this->integer(
         (int)$ticket->add([
            'name'         => '',
            'description'  => 'Another ticket to check ACLS',
            'content'      => ''
         ])
      )->isGreaterThan(0);
      $this->boolean((boolean)$ticket->getFromDB($ticket->getID()))->isTrue();
      $this->boolean((boolean)$ticket->canAdminActors())->isFalse();
      $this->boolean((boolean)$ticket->canAssign())->isFalse();
      $this->boolean((boolean)$ticket->canAssignToMe())->isFalse();
      $this->boolean((boolean)$ticket->canUpdate())->isTrue();
      $this->boolean((boolean)$ticket->canView())->isTrue();
      $this->boolean((boolean)$ticket->canViewItem())->isTrue();
      $this->boolean((boolean)$ticket->canSolve())->isTrue();
      $this->boolean((boolean)$ticket->canApprove())->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canCreateItem())->isTrue();
      $this->boolean((boolean)$ticket->canUpdateItem())->isTrue();
      $this->boolean((boolean)$ticket->canRequesterUpdateItem())->isTrue();
      $this->boolean((boolean)$ticket->canDelete())->isFalse();
      $this->boolean((boolean)$ticket->canDeleteItem())->isFalse();
      $this->boolean((boolean)$ticket->canAddItem('Document'))->isTrue();
      $this->boolean((boolean)$ticket->canAddItem('Ticket_Cost'))->isTrue();
      $this->boolean((boolean)$ticket->canAddFollowups())->isTrue();
   }

   public function testNotOwnerAcls() {
      $this->login();

      $ticket = new \Ticket();
      $this->integer(
         (int)$ticket->add([
            'name'         => '',
            'description'  => 'A ticket to check ACLS',
            'content'      => ''
         ])
      )->isGreaterThan(0);

      $auth = new \Auth();
      $this->boolean((boolean)$auth->Login('tech', 'tech', true))->isTrue();

      //reload ticket from DB
      $this->boolean((boolean)$ticket->getFromDB($ticket->getID()))->isTrue();
      $this->boolean((boolean)$ticket->canAdminActors())->isTrue();
      $this->boolean((boolean)$ticket->canAssign())->isFalse();
      $this->boolean((boolean)$ticket->canAssignToMe())->isFalse();
      $this->boolean((boolean)$ticket->canUpdate())->isTrue();
      $this->boolean((boolean)$ticket->canView())->isTrue();
      $this->boolean((boolean)$ticket->canViewItem())->isTrue();
      $this->boolean((boolean)$ticket->canSolve())->isTrue();
      $this->boolean((boolean)$ticket->canApprove())->isFalse();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canCreateItem())->isTrue();
      $this->boolean((boolean)$ticket->canUpdateItem())->isTrue();
      $this->boolean((boolean)$ticket->canRequesterUpdateItem())->isFalse();
      $this->boolean((boolean)$ticket->canDelete())->isFalse();
      $this->boolean((boolean)$ticket->canDeleteItem())->isFalse();
      $this->boolean((boolean)$ticket->canAddItem('Document'))->isTrue();
      $this->boolean((boolean)$ticket->canAddItem('Ticket_Cost'))->isTrue();
      $this->boolean((boolean)$ticket->canAddFollowups())->isTrue();

      //drop update ticket right from tech profile
      global $DB;
      $query = "UPDATE glpi_profilerights SET rights = 168965 WHERE profiles_id = 6 AND name = 'ticket'";
      $DB->query($query);
      //ACLs have changed: login again.
      $this->boolean((boolean)$auth->Login('tech', 'tech', true))->isTrue();

      //reset rights. Done here so ACLs are reset even if tests fails.
      $query = "UPDATE glpi_profilerights SET rights = 168967 WHERE profiles_id = 6 AND name = 'ticket'";
      $DB->query($query);

      $this->boolean((boolean)$ticket->getFromDB($ticket->getID()))->isTrue();
      $this->boolean((boolean)$ticket->canAdminActors())->isFalse();
      $this->boolean((boolean)$ticket->canAssign())->isFalse();
      $this->boolean((boolean)$ticket->canAssignToMe())->isFalse();
      $this->boolean((boolean)$ticket->canUpdate())->isTrue();
      $this->boolean((boolean)$ticket->canView())->isTrue();
      $this->boolean((boolean)$ticket->canViewItem())->isTrue();
      $this->boolean((boolean)$ticket->canSolve())->isFalse();
      $this->boolean((boolean)$ticket->canApprove())->isFalse();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canCreateItem())->isTrue();
      $this->boolean((boolean)$ticket->canUpdateItem())->isTrue();
      $this->boolean((boolean)$ticket->canRequesterUpdateItem())->isFalse();
      $this->boolean((boolean)$ticket->canDelete())->isFalse();
      $this->boolean((boolean)$ticket->canDeleteItem())->isFalse();
      $this->boolean((boolean)$ticket->canAddItem('Document'))->isTrue();
      $this->boolean((boolean)$ticket->canAddItem('Ticket_Cost'))->isFalse();
      $this->boolean((boolean)$ticket->canAddFollowups())->isTrue();

      $this->boolean((boolean)$auth->Login('post-only', 'postonly', true))->isTrue();
      $this->boolean((boolean)$ticket->getFromDB($ticket->getID()))->isTrue();
      $this->boolean((boolean)$ticket->canAdminActors())->isFalse();
      $this->boolean((boolean)$ticket->canAssign())->isFalse();
      $this->boolean((boolean)$ticket->canAssignToMe())->isFalse();
      $this->boolean((boolean)$ticket->canUpdate())->isTrue();
      $this->boolean((boolean)$ticket->canView())->isTrue();
      $this->boolean((boolean)$ticket->canViewItem())->isFalse();
      $this->boolean((boolean)$ticket->canSolve())->isFalse();
      $this->boolean((boolean)$ticket->canApprove())->isFalse();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'))->isTrue();
      $this->boolean((boolean)$ticket->canCreateItem())->isTrue();
      $this->boolean((boolean)$ticket->canUpdateItem())->isFalse();
      $this->boolean((boolean)$ticket->canRequesterUpdateItem())->isFalse();
      $this->boolean((boolean)$ticket->canDelete())->isTrue();
      $this->boolean((boolean)$ticket->canDeleteItem())->isFalse();
      $this->boolean((boolean)$ticket->canAddItem('Document'))->isFalse();
      $this->boolean((boolean)$ticket->canAddItem('Ticket_Cost'))->isFalse();
      $this->boolean((boolean)$ticket->canAddFollowups())->isFalse();
   }

   /**
    * Checks showForm() output
    *
    * @param \Ticket $ticket   Ticket instance
    * @param boolean $name     Name is editable
    * @param boolean $textarea Content is editable
    * @param boolean $priority Priority can be changed
    * @param boolean $save     Save button is present
    * @param boolean $assign   Can assign
    *
    * @return void
    */
   private function checkFormOutput(
      \Ticket $ticket,
      $name = true,
      $textarea = true,
      $priority = true,
      $save = true,
      $assign = true
   ) {
      ob_start();
      $ticket->showForm($ticket->getID());
      $output =ob_get_contents();
      ob_end_clean();

      //Form title
      preg_match(
         '/.*Ticket - ID: ' . $ticket->getID() . '.*/s',
         $output,
         $matches
      );
      $this->array($matches)->hasSize(1);

      //Ticket name, editable
      preg_match(
         '/.*<input[^>]*name=\'name\'  value="_ticket01">.*/',
         $output,
         $matches
      );
      $this->array($matches)->hasSize(($name === true ? 1 : 0));

      //Ticket content, editable
      preg_match(
         '/.*<textarea[^>]*name=\'content\'[^>]*>.*/',
         $output,
         $matches
      );
      $this->array($matches)->hasSize(($textarea === true ? 1 : 0));

      //Priority, editable
      preg_match(
         '/.*<select name=\'priority\'[^>]*>.*/',
         $output,
         $matches
      );
      $this->array($matches)->hasSize(($priority === true ? 1 : 0));

      //Save button
      preg_match(
         '/.*<input[^>]type=\'submit\'[^>]*>.*/',
         $output,
         $matches
      );
      $this->array($matches)->hasSize(($save === true ? 1 : 0));

      //Assign to
      preg_match(
         '/.*<select name=\'_itil_assign\[_type\]\'[^>]*>.*/',
         $output,
         $matches
      );
      $this->array($matches)->hasSize(($assign === true ? 1 : 0));
   }

   public function testForm() {
      $this->login();
      $this->setEntity('Root entity', true);
      $ticket = getItemByTypeName('Ticket', '_ticket01');

      $this->checkFormOutput($ticket);
   }

   public function testFormPostOnly() {
      $auth = new \Auth();
      $this->boolean((boolean)$auth->Login('post-only', 'postonly', true))->isTrue();

      //create a new ticket
      $ticket = new \Ticket();
      $this->integer(
         (int)$ticket->add([
            'name'         => '',
            'description'  => 'A ticket to check displayed postonly form',
            'content'      => ''
         ])
      )->isGreaterThan(0);
      $this->boolean($ticket->getFromDB($ticket->getId()))->isTrue();

      $this->checkFormOutput(
         $ticket,
         $name = false,
         $textarea = true,
         $priority = false,
         $save = true,
         $assign = false
      );

      $uid = getItemByTypeName('User', TU_USER, true);
      //add a followup to the ticket
      $fup = new \TicketFollowup();
      $this->integer(
         (int)$fup->add([
            'tickets_id'   => $ticket->getID(),
            'users_id'     => $uid,
            'content'      => 'A simple followup'
         ])
      )->isGreaterThan(0);

      $this->checkFormOutput(
         $ticket,
         $name = false,
         $textarea = false,
         $priority = false,
         $save = false,
         $assign = false
      );
   }

   public function testFormTech() {
      $auth = new \Auth();
      $this->boolean((boolean)$auth->Login('tech', 'tech', true))->isTrue();

      //create a new ticket
      $ticket = new \Ticket();
      $this->integer(
         (int)$ticket->add([
            'name'         => '',
            'description'  => 'A ticket to check displayed tech form',
            'content'      => ''
         ])
      )->isGreaterThan(0);
      $this->boolean($ticket->getFromDB($ticket->getId()))->isTrue();

      //check output with default ACLs
      $this->checkFormOutput(
         $ticket,
         $name = false,
         $textarea = true,
         $priority = false,
         $save = true,
         $assign = false
      );

      //drop update ticket right from tech profile
      global $DB;
      $query = "UPDATE glpi_profilerights SET rights = 168965 WHERE profiles_id = 6 AND name = 'ticket'";
      $DB->query($query);
      //ACLs have changed: login again.
      $this->boolean((boolean)$auth->Login('tech', 'tech', true))->isTrue();

      //reset rights. Done here so ACLs are reset even if tests fails.
      $query = "UPDATE glpi_profilerights SET rights = 168967 WHERE profiles_id = 6 AND name = 'ticket'";
      $DB->query($query);

      //check output with changed ACLs
      $this->checkFormOutput(
         $ticket,
         $name = false,
         $textarea = true,
         $priority = false,
         $save = true,
         $assign = false
      );

      $uid = getItemByTypeName('User', TU_USER, true);
      //add a followup to the ticket
      $fup = new \TicketFollowup();
      $this->integer(
         (int)$fup->add([
            'tickets_id'   => $ticket->getID(),
            'users_id'     => $uid,
            'content'      => 'A simple followup'
         ])
      )->isGreaterThan(0);

      //check output with changed ACLs when a followup has been added
      $this->checkFormOutput(
         $ticket,
         $name = false,
         $textarea = false,
         $priority = false,
         $save = false,
         $assign = false
      );
   }

   public function testPriorityAcl() {
      $this->login();

      $ticket = new \Ticket();
      $this->integer(
         (int)$ticket->add([
            'name'         => '',
            'description'  => 'A ticket to check priority ACLS',
            'content'      => ''
         ])
      )->isGreaterThan(0);

      $auth = new \Auth();
      $this->boolean((boolean)$auth->Login('tech', 'tech', true))->isTrue();
      $this->boolean((boolean)$ticket->getFromDB($ticket->getID()))->isTrue();

      $this->boolean((boolean)\Session::haveRight(\Ticket::$rightname, \Ticket::CHANGEPRIORITY))->isFalse();
      //check output with default ACLs
      $this->checkFormOutput(
         $ticket,
         $name = false,
         $textarea = true,
         $priority = false,
         $save = true,
         $assign = false
      );

      //Add priority right from tech profile
      global $DB;
      $query = "UPDATE glpi_profilerights SET rights = 234503 WHERE profiles_id = 6 AND name = 'ticket'";
      $DB->query($query);
      //ACLs have changed: login again.
      $this->boolean((boolean)$auth->Login('tech', 'tech', true))->isTrue();

      //reset rights. Done here so ACLs are reset even if tests fails.
      $query = "UPDATE glpi_profilerights SET rights = 168967 WHERE profiles_id = 6 AND name = 'ticket'";
      $DB->query($query);

      $this->boolean((boolean)\Session::haveRight(\Ticket::$rightname, \Ticket::CHANGEPRIORITY))->isTrue();
      //check output with changed ACLs
      $this->checkFormOutput(
         $ticket,
         $name = false,
         $textarea = true,
         $priority = true,
         $save = true,
         $assign = false
      );
   }

   public function testAssignAcl() {
      $this->login();

      $ticket = new \Ticket();
      $this->integer(
         (int)$ticket->add([
            'name'         => '',
            'description'  => 'A ticket to check assign ACLS',
            'content'      => ''
         ])
      )->isGreaterThan(0);

      $auth = new \Auth();
      $this->boolean((boolean)$auth->Login('tech', 'tech', true))->isTrue();
      $this->boolean((boolean)$ticket->getFromDB($ticket->getID()))->isTrue();

      $this->boolean((boolean)$ticket->canAssign())->isFalse();
      $this->boolean((boolean)$ticket->canAssignToMe())->isFalse();
      //check output with default ACLs
      $this->checkFormOutput(
         $ticket,
         $name = false,
         $textarea = true,
         $priority = false,
         $save = true,
         $assign = false
      );

      //Drop being in charge from tech profile
      global $DB;
      $query = "UPDATE glpi_profilerights SET rights = 136199 WHERE profiles_id = 6 AND name = 'ticket'";
      $DB->query($query);
      //ACLs have changed: login again.
      $this->boolean((boolean)$auth->Login('tech', 'tech', true))->isTrue();

      //reset rights. Done here so ACLs are reset even if tests fails.
      $query = "UPDATE glpi_profilerights SET rights = 168967 WHERE profiles_id = 6 AND name = 'ticket'";
      $DB->query($query);

      $this->boolean((boolean)$ticket->canAssign())->isFalse();
      $this->boolean((boolean)$ticket->canAssignToMe())->isFalse();
      //check output with changed ACLs
      $this->checkFormOutput(
         $ticket,
         $name = false,
         $textarea = true,
         $priority = false,
         $save = true,
         $assign = false
      );

      //Add assign in charge from tech profile
      $query = "UPDATE glpi_profilerights SET rights = 144391 WHERE profiles_id = 6 AND name = 'ticket'";
      $DB->query($query);
      //ACLs have changed: login again.
      $this->boolean((boolean)$auth->Login('tech', 'tech', true))->isTrue();

      //reset rights. Done here so ACLs are reset even if tests fails.
      $query = "UPDATE glpi_profilerights SET rights = 168967 WHERE profiles_id = 6 AND name = 'ticket'";
      $DB->query($query);

      $this->boolean((boolean)$ticket->canAssign())->isTrue();
      $this->boolean((boolean)$ticket->canAssignToMe())->isFalse();
      //check output with changed ACLs
      $this->checkFormOutput(
         $ticket,
         $name = false,
         $textarea = true,
         $priority = false,
         $save = true,
         $assign = true
      );
   }
   
   public function testAddFollowupWhenDefineExpirationPendingStatus() {
      $this->login();

      $entity = new \Entity;
      $ticket = new \Ticket;
      $followup = new \TicketFollowup;

      // First: Activate the option in the entity + activate followup
      $e = getItemByTypeName('Entity', '_test_root_entity', true);
      $input = [
          'id' => $e,
          'pendingenddate' => 0,
          'pending_add_follow' => 1
      ];
      $entity->update($input);

      // Create a ticket
      $input = [
          'name'           => 'test',
          'content'        => 'test description',
          'entities_id'    => $e,
          'status'         => \Ticket::WAITING,
          'pendingenddate' => date('Y-m-d H:i:s', (date('U') - 36000))
      ];
      $ticket_id = $ticket->add($input);
      $this->integer($ticket_id);

      $this->integer(count($followup->find("`tickets_id`='".$ticket_id."'")))->isEqualTo(1);

      // go back ticket in assigned status manually
      $input = [
          'id'     => $ticket_id,
          'status' => \Ticket::ASSIGNED
      ];
      $ticket->update($input);
      $this->integer(count($followup->find("`tickets_id`='".$ticket_id."'")))->isEqualTo(1);
      $ticket->getFromDB($ticket_id);
      $this->variable($ticket->fields['pendingenddate'])->isNull();
      $this->variable($ticket->fields['pendingcomment'])->isNull();

      // Return ticket status in pending state
      $input = [
          'id'     => $ticket_id,
          'status' => \Ticket::WAITING,
          'pendingenddate' => date('Y-m-d H:i:s', (date('U') - 36000))
      ];
      $ticket->update($input);
      $this->integer(count($followup->find("`tickets_id`='".$ticket_id."'")))->isEqualTo(2);

   }

   public function testCronPendingStatus() {
      $this->login();

      $entity = new \Entity;
      $ticket = new \Ticket;
      $followup = new \TicketFollowup;
      $cron   = new \CronTask;

      // First: Activate the option in the entity
      $e = getItemByTypeName('Entity', '_test_root_entity', true);
      $input = [
          'id' => $e,
          'pendingenddate' => 0,
          'pending_add_follow' => 0
      ];
      $entity->update($input);

      // Create a ticket
      $input = [
          'name'           => 'test',
          'content'        => 'test description',
          'entities_id'    => $e,
          'status'         => \Ticket::WAITING,
          'pendingenddate' => date('Y-m-d H:i:s', (date('U') - 36000))
      ];
      $ticket_id = $ticket->add($input);
      $this->integer($ticket_id);

      // Run the cron
      // reset last run, in case we run test few minutes ago
      $cron->getFromDBbyName('Ticket', 'expirePending');
      $cron->resetDate();
      \CronTask::launch(\CronTask::MODE_EXTERNAL, 1, 'expirePending');

      // The ticket must be go in assign status and pending date resetted
      $get_ticket = $ticket->getFromDB($ticket_id);
      $this->boolean($get_ticket)->isTrue;
      $this->string($ticket->fields['status'])->isEqualTo(\Ticket::ASSIGNED);
      $this->variable($ticket->fields['pendingenddate'])->isNull();

      $this->integer(count($followup->find("`tickets_id`='".$ticket_id."'")))->isEqualTo(0);
   }
}
