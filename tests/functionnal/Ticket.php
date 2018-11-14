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

/* Test for inc/ticket.class.php */

class Ticket extends DbTestCase {

   public function ticketProvider() {
      return [
         'single requester' => [
            [
               '_users_id_requester' => '3'
            ],
         ],
         'single unknown requester' => [
            [
               '_users_id_requester'         => '0',
               '_users_id_requester_notif'   => [
                  'use_notification'   => ['1'],
                  'alternative_email'  => ['unknownuser@localhost.local']
               ],
            ],
         ],
         'multiple requesters' => [
            [
               '_users_id_requester' => ['3', '5'],
            ],
         ],
         'multiple mixed requesters' => [
            [
               '_users_id_requester'         => ['3', '5', '0'],
               '_users_id_requester_notif'   => [
                  'use_notification'   => ['1', '0', '1'],
                  'alternative_email'  => ['','', 'unknownuser@localhost.local']
               ],
            ],
         ],
         'single observer' => [
            [
               '_users_id_observer' => '3'
            ],
         ],
         'multiple observers' => [
            [
               '_users_id_observer' => ['3', '5'],
            ],
         ],
         'single assign' => [
            [
               '_users_id_assign' => '3'
            ],
         ],
         'multiple assigns' => [
            [
               '_users_id_assign' => ['3', '5'],
            ],
         ],
      ];
   }

   /**
    * @dataProvider ticketProvider
    */
   public function testCreateTicketWithActors($ticketActors) {
      $ticket = new \Ticket();
      $this->integer((int)$ticket->add([
            'name'    => 'ticket title',
            'content' => 'a description',
      ] + $ticketActors))->isGreaterThan(0);

      $this->boolean($ticket->isNewItem())->isFalse();
      $ticketId = $ticket->getID();

      foreach ($ticketActors as $actorType => $actorsList) {
         // Convert single actor (scalar value) to array
         if (!is_array($actorsList)) {
            $actorsList = [$actorsList];
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
            $ticketUser->getFromDBByCrit([
               'tickets_id'         => $ticketId,
               'users_id'           => 0,
               'alternative_email'  => $alternateEmail
            ])
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
         'num'                => '176',
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
      $found_tasks = $tickettask->find(['tickets_id' => $tickets_id], "id ASC");

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
      $this->boolean((boolean)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isFalse();

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
      $this->boolean((boolean)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isTrue();

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
      $this->boolean((boolean)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isTrue();
   }

   public function testPostOnlyAcls() {
      $auth = new \Auth();
      $this->boolean((boolean)$auth->login('post-only', 'postonly', true))->isTrue();

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
      $this->boolean((boolean)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isFalse();

      $this->integer(
         (int)$ticket->add([
            'name'    => '',
            'content' => 'A ticket to check ACLS',
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
      $this->boolean((boolean)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isTrue();

      $uid = getItemByTypeName('User', TU_USER, true);
      //add a followup to the ticket
      $fup = new \ITILFollowup();
      $this->integer(
         (int)$fup->add([
            'itemtype'  => 'Ticket',
            'items_id'   => $ticket->getID(),
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
      $this->boolean((boolean)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isTrue();
   }

   public function testTechAcls() {
      $auth = new \Auth();
      $this->boolean((boolean)$auth->login('tech', 'tech', true))->isTrue();

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
      $this->boolean((boolean)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isTrue();

      $this->integer(
         (int)$ticket->add([
            'name'    => '',
            'content' => 'A ticket to check ACLS',
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
      $this->boolean((boolean)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isTrue();

      $uid = getItemByTypeName('User', TU_USER, true);
      //add a followup to the ticket
      $fup = new \ITILFollowup();
      $this->integer(
         (int)$fup->add([
            'itemtype'  => 'Ticket',
            'items_id'   => $ticket->getID(),
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
      $this->boolean((boolean)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isTrue();

      //drop update ticket right from tech profile
      global $DB;
      $DB->update(
         'glpi_profilerights',
         ['rights' => 168965], [
            'profiles_id'  => 6,
            'name'         => 'ticket'
         ]
      );
      //ACLs have changed: login again.
      $this->boolean((boolean)$auth->login('tech', 'tech', true))->isTrue();

      //reset rights. Done here so ACLs are reset even if tests fails.
      $DB->update(
         'glpi_profilerights',
         ['rights' => 168967], [
            'profiles_id'  => 6,
            'name'         => 'ticket'
         ]
      );

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
      $this->boolean((boolean)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isTrue();

      $this->integer(
         (int)$ticket->add([
            'name'    => '',
            'content' => 'Another ticket to check ACLS',
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
      $this->boolean((boolean)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isTrue();
   }

   public function testNotOwnerAcls() {
      $this->login();

      $ticket = new \Ticket();
      $this->integer(
         (int)$ticket->add([
            'name'    => '',
            'content' => 'A ticket to check ACLS',
         ])
      )->isGreaterThan(0);

      $auth = new \Auth();
      $this->boolean((boolean)$auth->login('tech', 'tech', true))->isTrue();

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
      $this->boolean((boolean)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isTrue();

      //drop update ticket right from tech profile
      global $DB;
      $DB->update(
         'glpi_profilerights',
         ['rights' => 168965], [
            'profiles_id'  => 6,
            'name'         => 'ticket'
         ]
      );
      //ACLs have changed: login again.
      $this->boolean((boolean)$auth->login('tech', 'tech', true))->isTrue();

      //reset rights. Done here so ACLs are reset even if tests fails.
      $DB->update(
         'glpi_profilerights',
         ['rights' => 168967], [
            'profiles_id'  => 6,
            'name'         => 'ticket'
         ]
      );

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
      $this->boolean((boolean)$ticket->canUpdateItem())->isFalse();
      $this->boolean((boolean)$ticket->canRequesterUpdateItem())->isFalse();
      $this->boolean((boolean)$ticket->canDelete())->isFalse();
      $this->boolean((boolean)$ticket->canDeleteItem())->isFalse();
      $this->boolean((boolean)$ticket->canAddItem('Document'))->isTrue();
      $this->boolean((boolean)$ticket->canAddItem('Ticket_Cost'))->isFalse();
      $this->boolean((boolean)$ticket->canAddFollowups())->isTrue();
      $this->boolean((boolean)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isTrue();

      // post only tests
      $this->boolean((boolean)$auth->login('post-only', 'postonly', true))->isTrue();
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
      $this->boolean((boolean)$ticket->canUserAddFollowups(\Session::getLoginUserID()))->isFalse();
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
      $assign = true,
      $openDate = true,
      $timeOwnResolve = true,
      $type = true,
      $status = true,
      $urgency = true,
      $impact = true,
      $category = true,
      $requestSource = true,
      $location = true
   ) {
      ob_start();
      $ticket->showForm($ticket->getID());
      $output =ob_get_contents();
      ob_end_clean();

      //Form title
      preg_match(
         '/.*Ticket - ID ' . $ticket->getID() . '.*/s',
         $output,
         $matches
      );
      $this->array($matches)->hasSize(1);

      // Opening date, editable
      preg_match(
         '/.*<input[^>]*name=\'_date\'[^>]*>.*/',
         $output,
         $matches
      );
      $this->array($matches)->hasSize(($openDate === true ? 1 : 0));

      // Time to own, editable
      preg_match(
         '/.*<input[^>]*name=\'_time_to_own\'[^>]*>.*/',
         $output,
         $matches
      );
      $this->array($matches)->hasSize(($timeOwnResolve === true ? 1 : 0));

      // Internal time to own, editable
      preg_match(
         '/.*<input[^>]*name=\'_internal_time_to_own\'[^>]*>.*/',
         $output,
         $matches
      );
      $this->array($matches)->hasSize(($timeOwnResolve === true ? 1 : 0));

      // Time to resolve, editable
      preg_match(
         '/.*<input[^>]*name=\'_time_to_resolve\'[^>]*>.*/',
         $output,
         $matches
      );
      $this->array($matches)->hasSize(($timeOwnResolve === true ? 1 : 0));

      // Internal time to resolve, editable
      preg_match(
         '/.*<input[^>]*name=\'_internal_time_to_resolve\'[^>]*>.*/',
         $output,
         $matches
      );
      $this->array($matches)->hasSize(($timeOwnResolve === true ? 1 : 0));

      //Type
      preg_match(
         '/.*<select[^>]*name=\'type\'[^>]*>.*/',
         $output,
         $matches
      );
      $this->array($matches)->hasSize(($type === true ? 1 : 0));

      //Status
      preg_match(
         '/.*<select[^>]*name=\'status\'[^>]*>.*/',
         $output,
         $matches
      );
      $this->array($matches)->hasSize(($status === true ? 1 : 0));

      //Urgency
      preg_match(
         '/.*<select[^>]*name=\'urgency\'[^>]*>.*/',
         $output,
         $matches
      );
      $this->array($matches)->hasSize(($urgency === true ? 1 : 0));

      //Impact
      preg_match(
         '/.*<select[^>]*name=\'impact\'[^>]*>.*/',
         $output,
         $matches
      );
      $this->array($matches)->hasSize(($impact === true ? 1 : 0));

      //Category
      preg_match(
         '/.*<select[^>]*name="itilcategories_id"[^>]*>.*/',
         $output,
         $matches
      );
      $this->array($matches)->hasSize(($category === true ? 1 : 0));

      //Request source file_put_contents('/tmp/out.html', $output)
      if ($requestSource === true) {
         preg_match(
            '/.*<select[^>]*name="requesttypes_id"[^>]*>.*/',
            $output,
            $matches
            );
         $this->array($matches)->hasSize(1);
      } else {
         preg_match(
            '/.*<input[^>]*name="requesttypes_id"[^>]*>.*/',
            $output,
            $matches
            );
         $this->array($matches)->hasSize(1);
      }

      //Location
      preg_match(
         '/.*<select[^>]*name="locations_id"[^>]*>.*/',
         $output,
         $matches
      );
      $this->array($matches)->hasSize(($location === true ? 1 : 0));

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
         '/.*<input[^>]type=\'submit\'[^>]*name=\'update\'[^>]*>.*/',
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
      $this->boolean((boolean)$auth->login('post-only', 'postonly', true))->isTrue();

      //create a new ticket
      $ticket = new \Ticket();
      $this->integer(
         (int)$ticket->add([
            'name'    => '',
            'content' => 'A ticket to check displayed postonly form',
         ])
      )->isGreaterThan(0);
      $this->boolean($ticket->getFromDB($ticket->getId()))->isTrue();

      $this->checkFormOutput(
         $ticket,
         $name = false,
         $textarea = true,
         $priority = false,
         $save = true,
         $assign = false,
         $openDate = false,
         $timeOwnResolve = false,
         $type = false,
         $status = false,
         $urgency = true,
         $impact = false,
         $category = true,
         $requestSource = false,
         $location = false
      );

      $uid = getItemByTypeName('User', TU_USER, true);
      //add a followup to the ticket
      $fup = new \ITILFollowup();
      $this->integer(
         (int)$fup->add([
            'itemtype'  => 'Ticket',
            'items_id'   => $ticket->getID(),
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
         $assign = false,
         $openDate = false,
         $timeOwnResolve = false,
         $type = false,
         $status = false,
         $urgency = false,
         $impact = false,
         $category = false,
         $requestSource = false,
         $location = false
      );
   }

   public function testFormTech() {
      global $DB;

      //create a new ticket with tu user
      $auth = new \Auth();
      $this->login();
      $ticket = new \Ticket();
      $this->integer(
         (int)$ticket->add([
            'name'                => '',
            'content'             => 'A ticket to check displayed tech form',
            '_users_id_requester' => '3', // post-only
            '_users_id_assign'    => '4', // tech
         ])
      )->isGreaterThan(0);
      $this->boolean($ticket->getFromDB($ticket->getId()))->isTrue();

      //check output with default ACLs
      $this->changeTechRight();
      $this->checkFormOutput(
         $ticket,
         $name = false,
         $textarea = true,
         $priority = false,
         $save = true,
         $assign = false,
         $openDate = true,
         $timeOwnResolve = true,
         $type = true,
         $status = true,
         $urgency = true,
         $impact = true,
         $category = true,
         $requestSource = true,
         $location = true
      );

      //drop UPDATE ticket right from tech profile (still with OWN)
      $this->changeTechRight(168965);
      $this->checkFormOutput(
         $ticket,
         $name = false,
         $textarea = true,
         $priority = false,
         $save = true,
         $assign = false,
         $openDate = true,
         $timeOwnResolve = true,
         $type = true,
         $status = true,
         $urgency = true,
         $impact = true,
         $category = true,
         $requestSource = true,
         $location = true
      );

      //drop UPDATE ticket right from tech profile (without OWN)
      $this->changeTechRight(136197);
      $this->checkFormOutput(
         $ticket,
         $name = false,
         $textarea = false,
         $priority = false,
         $save = false,
         $assign = false,
         $openDate = false,
         $timeOwnResolve = false,
         $type = false,
         $status = false,
         $urgency = false,
         $impact = false,
         $category = false,
         $requestSource = false,
         $location = false
      );

      // only assign and priority right for tech (without UPDATE and OWN rights)
      $this->changeTechRight(94209);
      $this->checkFormOutput(
         $ticket,
         $name = false,
         $textarea = false,
         $priority = true,
         $save = true,
         $assign = true,
         $openDate = false,
         $timeOwnResolve = false,
         $type = false,
         $status = false,
         $urgency = false,
         $impact = false,
         $category = false,
         $requestSource = false,
         $location = false
      );

      // no update rights, only display for tech
      $this->changeTechRight(3077);
      $this->checkFormOutput(
         $ticket,
         $name = false,
         $textarea = false,
         $priority = false,
         $save = false,
         $assign = false,
         $openDate = false,
         $timeOwnResolve = false,
         $type = false,
         $status = false,
         $urgency = false,
         $impact = false,
         $category = false,
         $requestSource = false,
         $location = false
      );

      $uid = getItemByTypeName('User', TU_USER, true);
      //add a followup to the ticket
      $fup = new \ITILFollowup();
      $this->integer(
         (int)$fup->add([
            'itemtype'  => 'Ticket',
            'items_id'   => $ticket->getID(),
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
         $assign = false,
         $openDate = false,
         $timeOwnResolve = false,
         $type = false,
         $status = false,
         $urgency = false,
         $impact = false,
         $category = false,
         $requestSource = false,
         $location = false
      );
   }

   public function changeTechRight($rights = 168967) {
      global $DB;

      // set new rights
      $DB->update(
         'glpi_profilerights',
         ['rights' => $rights], [
            'profiles_id'  => 6,
            'name'         => 'ticket'
         ]
      );

      //ACLs have changed: login again.
      $auth = new \Auth();
      $this->boolean((boolean) $auth->Login('tech', 'tech', true))->isTrue();

      if ($rights != 168967) {
         //reset rights. Done here so ACLs are reset even if tests fails.
         $DB->update(
            'glpi_profilerights',
            ['rights' => 168967], [
               'profiles_id'  => 6,
               'name'         => 'ticket'
            ]
         );
      }
   }

   public function testPriorityAcl() {
      $this->login();

      $ticket = new \Ticket();
      $this->integer(
         (int)$ticket->add([
            'name'    => '',
            'content' => 'A ticket to check priority ACLS',
         ])
      )->isGreaterThan(0);

      $auth = new \Auth();
      $this->boolean((boolean)$auth->login('tech', 'tech', true))->isTrue();
      $this->boolean((boolean)$ticket->getFromDB($ticket->getID()))->isTrue();

      $this->boolean((boolean)\Session::haveRight(\Ticket::$rightname, \Ticket::CHANGEPRIORITY))->isFalse();
      //check output with default ACLs
      $this->checkFormOutput(
         $ticket,
         $name = false,
         $textarea = true,
         $priority = false,
         $save = true,
         $assign = false,
         $openDate = true,
         $timeOwnResolve = true,
         $type = true,
         $status = true,
         $urgency = true,
         $impact = true,
         $category = true,
         $requestSource = true,
         $location = true
      );

      //Add priority right from tech profile
      global $DB;
      $DB->update(
         'glpi_profilerights',
         ['rights' => 234503], [
            'profiles_id'  => 6,
            'name'         => 'ticket'
         ]
      );

      //ACLs have changed: login again.
      $this->boolean((boolean)$auth->login('tech', 'tech', true))->isTrue();

      //reset rights. Done here so ACLs are reset even if tests fails.
      $DB->update(
         'glpi_profilerights',
         ['rights' => 168967], [
            'profiles_id'  => 6,
            'name'         => 'ticket'
         ]
      );

      $this->boolean((boolean)\Session::haveRight(\Ticket::$rightname, \Ticket::CHANGEPRIORITY))->isTrue();
      //check output with changed ACLs
      $this->checkFormOutput(
         $ticket,
         $name = false,
         $textarea = true,
         $priority = true,
         $save = true,
         $assign = false,
         $openDate = true,
         $timeOwnResolve = true,
         $type = true,
         $status = true,
         $urgency = true,
         $impact = true,
         $category = true,
         $requestSource = true,
         $location = true
      );
   }

   public function testAssignAcl() {
      $this->login();

      $ticket = new \Ticket();
      $this->integer(
         (int)$ticket->add([
            'name'    => '',
            'content' => 'A ticket to check assign ACLS',
         ])
      )->isGreaterThan(0);

      $auth = new \Auth();
      $this->boolean((boolean)$auth->login('tech', 'tech', true))->isTrue();
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
         $assign = false,
         $openDate = true,
         $timeOwnResolve = true,
         $type = true,
         $status = true,
         $urgency = true,
         $impact = true,
         $category = true,
         $requestSource = true,
         $location = true
      );

      //Drop being in charge from tech profile
      global $DB;
      $DB->update(
         'glpi_profilerights',
         ['rights' => 136199], [
            'profiles_id'  => 6,
            'name'         => 'ticket'
         ]
      );

      //ACLs have changed: login again.
      $this->boolean((boolean)$auth->login('tech', 'tech', true))->isTrue();

      //reset rights. Done here so ACLs are reset even if tests fails.
      $DB->update(
         'glpi_profilerights',
         ['rights' => 168967], [
            'profiles_id'  => 6,
            'name'         => 'ticket'
         ]
      );

      $this->boolean((boolean)$ticket->canAssign())->isFalse();
      $this->boolean((boolean)$ticket->canAssignToMe())->isFalse();
      //check output with changed ACLs
      $this->checkFormOutput(
         $ticket,
         $name = false,
         $textarea = true,
         $priority = false,
         $save = true,
         $assign = false,
         $openDate = true,
         $timeOwnResolve = true,
         $type = true,
         $status = true,
         $urgency = true,
         $impact = true,
         $category = true,
         $requestSource = true,
         $location = true
      );

      //Add assign in charge from tech profile
      $DB->update(
         'glpi_profilerights',
         ['rights' => 144391], [
            'profiles_id'  => 6,
            'name'         => 'ticket'
         ]
      );

      //ACLs have changed: login again.
      $this->boolean((boolean)$auth->login('tech', 'tech', true))->isTrue();

      //reset rights. Done here so ACLs are reset even if tests fails.
      $DB->update(
         'glpi_profilerights',
         ['rights' => 168967], [
            'profiles_id'  => 6,
            'name'         => 'ticket'
         ]
      );

      $this->boolean((boolean)$ticket->canAssign())->isTrue();
      $this->boolean((boolean)$ticket->canAssignToMe())->isFalse();
      //check output with changed ACLs
      $this->checkFormOutput(
         $ticket,
         $name = false,
         $textarea = true,
         $priority = false,
         $save = true,
         $assign = true,
         $openDate = true,
         $timeOwnResolve = true,
         $type = true,
         $status = true,
         $urgency = true,
         $impact = true,
         $category = true,
         $requestSource = true,
         $location = true
      );
   }

   public function testUpdateFollowup() {
      $uid = getItemByTypeName('User', 'tech', true);
      $auth = new \Auth();
      $this->boolean((boolean)$auth->login('tech', 'tech', true))->isTrue();

      $ticket = new \Ticket();
      $this->integer(
         (int)$ticket->add([
            'name'    => '',
            'content' => 'A ticket to check followup updates',
         ])
      )->isGreaterThan(0);

      //add a followup to the ticket
      $fup = new \ITILFollowup();
      $this->integer(
         (int)$fup->add([
            'itemtype'  => $ticket::getType(),
            'items_id'   => $ticket->getID(),
            'users_id'     => $uid,
            'content'      => 'A simple followup'
         ])
      )->isGreaterThan(0);

      $this->login();
      $uid2 = getItemByTypeName('User', TU_USER, true);
      $this->boolean($fup->getFromDB($fup->getID()))->isTrue();
      $this->boolean($fup->update([
         'id'        => $fup->getID(),
         'content'   => 'A simple edited followup'
      ]))->isTrue();

      $this->boolean($fup->getFromDB($fup->getID()))->isTrue();
      $this->array($fup->fields)
         ->variable['users_id']->isEqualTo($uid)
         ->variable['users_id_editor']->isEqualTo($uid2);
   }

   protected function _testGetTimelinePosition($tlp, $tickets_id) {
      foreach ($tlp as $users_name => $user) {
         $this->login($users_name, $user['pass']);
         $uid = getItemByTypeName('User', $users_name, true);

         // ITILFollowup
         $fup = new \ITILFollowup();
         $this->integer(
            (int)$fup->add([
               'itemtype'  => 'Ticket',
               'items_id'   => $tickets_id,
               'users_id'     => $uid,
               'content'      => 'A simple followup'
            ])
         )->isGreaterThan(0);

         $this->integer(
            (int)$fup->fields['timeline_position']
         )->isEqualTo($user['pos']);

         // TicketTask
         $task = new \TicketTask();
         $this->integer(
            (int)$task->add([
               'tickets_id'   => $tickets_id,
               'users_id'     => $uid,
               'content'      => 'A simple Task'
            ])
         )->isGreaterThan(0);

         $this->integer(
            (int)$task->fields['timeline_position']
         )->isEqualTo($user['pos']);

         // Document and Document_Item
         $doc = new \Document();
         $this->integer(
            (int)$doc->add([
               'users_id'     => $uid,
               'tickets_id'   => $tickets_id,
               'name'         => 'A simple document object'
            ])
         )->isGreaterThan(0);

         $doc_item = new \Document_Item();
         $this->integer(
            (int)$doc_item->add([
               'users_id'      => $uid,
               'items_id'      => $tickets_id,
               'itemtype'      => 'Ticket',
               'documents_id'  => $doc->getID()
            ])
         )->isGreaterThan(0);

         $this->integer(
            (int)$doc_item->fields['timeline_position']
         )->isEqualTo($user['pos']);

         // TicketValidation
         $val = new \TicketValidation();
         $this->integer(
            (int)$val->add([
               'tickets_id'   => $tickets_id,
               'comment_submission'      => 'A simple validation',
               'users_id_validate' => 5, // normal
               'status' => 2
            ])
         )->isGreaterThan(0);

         $this->integer(
            (int)$val->fields['timeline_position']
         )->isEqualTo($user['pos']);
      }
   }

   protected function _testGetTimelinePositionSolution($tlp, $tickets_id) {
      foreach ($tlp as $users_name => $user) {
         $this->login($users_name, $user['pass']);
         $uid = getItemByTypeName('User', $users_name, true);

         // Ticket Solution
         $tkt = new \Ticket();
         $this->boolean(
            (boolean)$tkt->update([
               'id'   => $tickets_id,
               'solution'      => 'A simple solution from '.$users_name
            ])
         )->isEqualto(true);

         $this->integer(
            (int)$tkt->getTimelinePosition($tickets_id, 'Solution', $uid)
         )->isEqualTo($user['pos']);
      }
   }

   function testGetTimelinePosition() {

      // login TU_USER
      $this->login();

      // create ticket
      // with post-only as requester
      // tech as assigned to
      // normal as observer
      $ticket = new \Ticket();
      $this->integer((int)$ticket->add([
            'name'                => 'ticket title',
            'content'             => 'a description',
            '_users_id_requester' => '3', // post-only
            '_users_id_observer'  => '5', // normal
            '_users_id_assign'    => ['4', '5'] // tech and normal
      ] ))->isGreaterThan(0);

      $tlp = [
         'glpi'      => ['pass' => 'glpi',     'pos' => \CommonITILObject::TIMELINE_LEFT],
         'post-only' => ['pass' => 'postonly', 'pos' => \CommonITILObject::TIMELINE_LEFT],
         'tech'      => ['pass' => 'tech',     'pos' => \CommonITILObject::TIMELINE_RIGHT],
         'normal'    => ['pass' => 'normal',   'pos' => \CommonITILObject::TIMELINE_RIGHT]
      ];

      $this->_testGetTimelinePosition($tlp, $ticket->getID());

      // Solution timeline tests
      $tlp = [
         'tech'      => ['pass' => 'tech',     'pos' => \CommonITILObject::TIMELINE_RIGHT]
      ];

      $this->_testGetTimelinePositionSolution($tlp, $ticket->getID());

      return $ticket->getID();
   }

   function testGetTimelineItems() {

      $tkt_id = $this->testGetTimelinePosition();

      // login TU_USER
      $this->login();

      $ticket = new \Ticket();
      $this->boolean(
         (boolean)$ticket->getFromDB($tkt_id)
      )->isTrue();

      // test timeline_position from getTimelineItems()
      $timeline_items = $ticket->getTimelineItems();

      foreach ($timeline_items as $item) {
         switch ($item['type']) {
            case 'ITILFollowup':
            case 'TicketTask':
            case 'TicketValidation':
            case 'Document_Item':
               if (in_array($item['item']['users_id'], [2, 3])) {
                  $this->integer((int)$item['item']['timeline_position'])->isEqualTo(\CommonITILObject::TIMELINE_LEFT);
               } else {
                  $this->integer((int)$item['item']['timeline_position'])->isEqualTo(\CommonITILObject::TIMELINE_RIGHT);
               }
               break;
            case 'Solution':
               $this->integer((int)$item['item']['timeline_position'])->isEqualTo(\CommonITILObject::TIMELINE_RIGHT);
               break;
         }
      }
   }

   public function inputProvider() {
      return [
         [
            'input'     => [
               'name'     => 'This is a title',
               'content'   => 'This is a content'
            ],
            'expected'  => [
               'name' => 'This is a title',
               'content' => 'This is a content'
            ]
         ], [
            'input'     => [
               'name'      => '',
               'content'   => 'This is a content'
            ],
            'expected'  => [
               'name' => 'This is a content',
               'content' => 'This is a content'
            ]
         ], [
            'input'     => [
               'name'      => '',
               'content'   => "This is a content\nwith a carriage return"
            ],
            'expected'  => [
               'name' => 'This is a content with a carriage return',
               'content' => 'This is a content\nwith a carriage return'
            ]
         ], [
            'input'     => [
               'name'      => '',
               'content'   => "This is a content\r\nwith a carriage return"
            ],
            'expected'  => [
               'name' => 'This is a content with a carriage return',
               'content' => 'This is a content\nwith a carriage return'
            ]
         ], [
            'input'     => [
               'name'      => '',
               'content'   => "<p>This is a content\r\nwith a carriage return</p>"
            ],
            'expected'  => [
               'name' => 'This is a content with a carriage return',
               'content' => '<p>This is a content\nwith a carriage return</p>',
            ]
         ], [
            'input'     => [
               'name'      => '',
               'content'   => "&lt;p&gt;This is a content\r\nwith a carriage return&lt;/p&gt;"
            ],
            'expected'  => [
               'name' => 'This is a content with a carriage return',
               'content' => '&lt;p&gt;This is a content\nwith a carriage return&lt;/p&gt;'
            ]
         ], [
            'input'     => [
               'name'      => '',
               'content'   => 'Test for buggy &#039; character'
            ],
            'expected'  => [
               'name'      => 'Test for buggy \\\' character',
               'content'   => 'Test for buggy \\\' character',
            ]
         ], [
            'input'     => [
               'name'      => '',
               'content'   => 'Test for buggy &#39; character'
            ],
            'expected'  => [
               'name'      => 'Test for buggy \\\' character',
               'content'   => 'Test for buggy \\\' character',
            ]
         ]
      ];
   }

   /**
    * @dataProvider inputProvider
    */
   public function testPrepareInputForAdd($input, $expected) {
      global $CFG_GLPI;

      $this
         ->if($this->newTestedInstance)
         ->then
            ->array($this->testedInstance->prepareInputForAdd(\Toolbox::addslashes_deep($input)))
               ->string['name']->isIdenticalTo($expected['name'])
               ->string['content']->isIdenticalTo($expected['content']);
   }

   public function testAssignChangeStatus() {
      // login postonly
      $this->login('post-only', 'postonly');

      //create a new ticket
      $ticket = new \Ticket();
      $this->integer(
         (int)$ticket->add([
            'name'    => '',
            'content' => 'A ticket to check change of status when using "associate myself" feature',
         ])
      )->isGreaterThan(0);
      $tickets_id = $ticket->getID();
      $this->boolean($ticket->getFromDB($tickets_id))->isTrue();

      // login TU_USER
      $this->login();

      // simulate "associate myself" feature
      $ticket_user = new \Ticket_User();
      $input_ticket_user = [
         'tickets_id'       => $tickets_id,
         'users_id'         => \Session::getLoginUserID(),
         'use_notification' => 1,
         'type'             => \CommonITILActor::ASSIGN
      ];
      $this->integer((int) $ticket_user->add($input_ticket_user))->isGreaterThan(0);
      $this->boolean($ticket_user->getFromDB($ticket_user->getId()))->isTrue();

      // check status (should be ASSIGNED)
      $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
      $this->integer((int) $ticket->fields['status'])
           ->isEqualto(\CommonITILObject::ASSIGNED);

      // remove associated user
      $ticket_user->delete([
         'id' => $ticket_user->getId()
      ]);

      // check status (should be INCOMING)
      $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
      $this->integer((int) $ticket->fields['status'])
           ->isEqualto(\CommonITILObject::INCOMING);

      // drop UPDATE right to TU_USER and redo "associate myself"
      $saverights = $_SESSION['glpiactiveprofile'];
      $_SESSION['glpiactiveprofile']['ticket'] -= \UPDATE;
      $this->integer((int) $ticket_user->add($input_ticket_user))->isGreaterThan(0);
      // restore rights
      $_SESSION['glpiactiveprofile'] = $saverights;
      //check ticket creation
      $this->boolean($ticket_user->getFromDB($ticket_user->getId()))->isTrue();

      // check status (should be ASSIGNED)
      $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
      $this->integer((int) $ticket->fields['status'])
           ->isEqualto(\CommonITILObject::ASSIGNED);

      // remove associated user
      $ticket_user->delete([
         'id' => $ticket_user->getId()
      ]);

      // check status (should be INCOMING)
      $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
      $this->integer((int) $ticket->fields['status'])
           ->isEqualto(\CommonITILObject::INCOMING);

      // remove associated user
      $ticket_user->delete([
         'id' => $ticket_user->getId()
      ]);

      // check with very limited rights and redo "associate myself"
      $_SESSION['glpiactiveprofile']['ticket'] = \CREATE
                                               + \Ticket::READMY;
                                               + \Ticket::READALL;
                                               + \Ticket::READGROUP;
                                               + \Ticket::OWN; // OWN right must allow self-assign
      $this->integer((int) $ticket_user->add($input_ticket_user))->isGreaterThan(0);
      // restore rights
      $_SESSION['glpiactiveprofile'] = $saverights;
      //check ticket creation
      $this->boolean($ticket_user->getFromDB($ticket_user->getId()))->isTrue();

      // check status (should still be ASSIGNED)
      $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
      $this->integer((int) $ticket->fields['status'])
           ->isEqualto(\CommonITILObject::ASSIGNED);
   }

   public function testClosedTicketTransfer() {

      // 1- create a category
      $itilcat      = new \ITILCategory;
      $first_cat_id = $itilcat->add([
                                       'name' => 'my first cat',
                                    ]);
      $this->boolean($itilcat->isNewItem())->isFalse();

      // 2- create a category
      $second_cat    = new \ITILCategory;
      $second_cat_id = $second_cat->add([
                                           'name' => 'my second cat',
                                        ]);
      $this->boolean($second_cat->isNewItem())->isFalse();

      // 3- create ticket
      $ticket    = new \Ticket;
      $ticket_id = $ticket->add([
                                   'name'              => 'A ticket to check the category change when using the "transfer" function.',
                                   'content'           => 'A ticket to check the category change when using the "transfer" function.',
                                   'itilcategories_id' => $first_cat_id,
                                   'status'            => \CommonITILObject::CLOSED
                                ]);

      $this->boolean($ticket->isNewItem())->isFalse();

      // 4 - delete category with replacement
      $itilcat->delete(['id'          => $first_cat_id,
                        '_replace_by' => $second_cat_id], 1);

      // 5 - check that the category has been replaced in the ticket
      $ticket->getFromDB($ticket_id);
      $this->integer((int)$ticket->fields['itilcategories_id'])
           ->isEqualto($second_cat_id);
   }

   protected function computePriorityProvider() {
      return [
         [
            'input'  => [
               'urgency'   => 2,
               'impact'    => 2
            ],
            '2',
            '2',
            '2'
         ], [
            'input'  => [
               'urgency'   => 5
            ],
            '5',
            '3',
            '4'
         ], [
            'input'  => [
               'impact'   => 5
            ],
            '3',
            '5',
            '4'
         ], [
            'input'  => [
               'urgency'   => 5,
               'impact'    => 5
            ],
            '5',
            '5',
            '5'
         ], [
            'input'  => [
               'urgency'   => 5,
               'impact'    => 1
            ],
            '5',
            '1',
            '2'
         ]
      ];
   }

   /**
    * @dataProvider computePriorityProvider
    */
   public function testComputePriority($input, $urgency, $impact, $priority) {
      $this->login();
      $ticket = getItemByTypeName('Ticket', '_ticket01');
      $input['id'] = $ticket->fields['id'];
      $result = $ticket->prepareInputForUpdate($input);
      $this->array($result)
         ->string['urgency']->isIdenticalTo($urgency)
         ->string['impact']->isIdenticalTo($impact)
         ->string['priority']->isIdenticalTo($priority);
   }

   public function testGetDefaultValues() {
      $input = \Ticket::getDefaultValues();

      $this->integer($input['_users_id_requester'])->isEqualTo(0);
      $this->array($input['_users_id_requester_notif']['use_notification'])->contains('1');
      $this->array($input['_users_id_requester_notif']['alternative_email'])->contains('');

      $this->integer($input['_groups_id_requester'])->isEqualTo(0);

      $this->integer($input['_users_id_assign'])->isEqualTo(0);
      $this->array($input['_users_id_assign_notif']['use_notification'])->contains('1');
      $this->array($input['_users_id_assign_notif']['alternative_email'])->contains('');

      $this->integer($input['_groups_id_assign'])->isEqualTo(0);

      $this->integer($input['_users_id_observer'])->isEqualTo(0);
      $this->array($input['_users_id_observer_notif']['use_notification'])->contains('1');
      $this->array($input['_users_id_observer_notif']['alternative_email'])->contains('');

      $this->integer($input['_suppliers_id_assign'])->isEqualTo(0);
      $this->array($input['_suppliers_id_assign_notif']['use_notification'])->contains('1');
      $this->array($input['_suppliers_id_assign_notif']['alternative_email'])->contains('');

      $this->string($input['name'])->isEqualTo('');
      $this->string($input['content'])->isEqualTo('');
      $this->integer((int) $input['itilcategories_id'])->isEqualTo(0);
      $this->integer((int) $input['urgency'])->isEqualTo(3);
      $this->integer((int) $input['impact'])->isEqualTo(3);
      $this->integer((int) $input['priority'])->isEqualTo(3);
      $this->integer((int) $input['requesttypes_id'])->isEqualTo(1);
      $this->integer((int) $input['actiontime'])->isEqualTo(0);
      $this->integer((int) $input['entities_id'])->isEqualTo(0);
      $this->integer((int) $input['status'])->isEqualTo(\Ticket::INCOMING);
      $this->array($input['followup'])->size->isEqualTo(0);
      $this->string($input['itemtype'])->isEqualTo('');
      $this->integer((int) $input['items_id'])->isEqualTo(0);
      $this->array($input['plan'])->size->isEqualTo(0);
      $this->integer((int) $input['global_validation'])->isEqualTo(\CommonITILValidation::NONE);

      $this->string($input['time_to_resolve'])->isEqualTo('NULL');
      $this->string($input['time_to_own'])->isEqualTo('NULL');
      $this->integer((int) $input['slas_tto_id'])->isEqualTo(0);
      $this->integer((int) $input['slas_ttr_id'])->isEqualTo(0);

      $this->string($input['internal_time_to_resolve'])->isEqualTo('NULL');
      $this->string($input['internal_time_to_own'])->isEqualTo('NULL');
      $this->integer((int) $input['olas_tto_id'])->isEqualTo(0);
      $this->integer((int) $input['olas_ttr_id'])->isEqualTo(0);

      $this->integer((int) $input['_add_validation'])->isEqualTo(0);

      $this->array($input['users_id_validate'])->size->isEqualTo(0);
      $this->integer((int) $input['type'])->isEqualTo(\Ticket::INCIDENT_TYPE);
      $this->array($input['_documents_id'])->size->isEqualTo(0);
      $this->array($input['_tasktemplates_id'])->size->isEqualTo(0);
      $this->array($input['_filename'])->size->isEqualTo(0);
      $this->array($input['_tag_filename'])->size->isEqualTo(0);
   }

   /**
    * @see self::testCanTakeIntoAccount()
    */
   public function canTakeIntoAccountProvider() {
      return [
         [
            'input'    => [
               '_users_id_requester' => ['3'], // "post-only"
            ],
            'user'     => [
               'login'    => 'post-only',
               'password' => 'postonly',
            ],
            'expected' => false, // is requester, so cannot take into account
         ],
         [
            'input'    => [
               '_users_id_requester' => ['3', '4'], // "post-only" and "tech"
            ],
            'user'     => [
               'login'    => 'tech',
               'password' => 'tech',
            ],
            'expected' => false, // is requester, so cannot take into account
         ],
         [
            'input'    => [
               '_users_id_requester' => ['3'], // "post-only"
            ],
            'user'     => [
               'login'    => 'tech',
               'password' => 'tech',
            ],
            'expected' => true, // has enough rights so can take into account
         ],
         [
            'input'    => [
               '_users_id_requester' => ['3'], // "post-only"
            ],
            'user'     => [
               'login'    => 'tech',
               'password' => 'tech',
               'rights'   => [
                  'task' => \READ,
                  'followup' => \READ,
               ],
            ],
            'expected' => false, // has not enough rights so cannot take into account
         ],
         [
            'input'    => [
               '_users_id_requester' => ['3'], // "post-only"
            ],
            'user'     => [
               'login'    => 'tech',
               'password' => 'tech',
               'rights'   => [
                  'task' => \READ + \CommonITILTask::ADDALLITEM,
                  'followup' => \READ,
               ],
            ],
            'expected' => true, // has not enough rights so cannot take into account
         ],
         [
            'input'    => [
               '_users_id_requester' => ['3'], // "post-only"
            ],
            'user'     => [
               'login'    => 'tech',
               'password' => 'tech',
               'rights'   => [
                  'task' => \READ,
                  'followup' => \READ + \ITILFollowup::ADDALLTICKET,
               ],
            ],
            'expected' => true, // has not enough rights so cannot take into account
         ],
         [
            'input'    => [
               '_users_id_requester' => ['3'], // "post-only"
            ],
            'user'     => [
               'login'    => 'tech',
               'password' => 'tech',
               'rights'   => [
                  'task' => \READ,
                  'followup' => \READ + \ITILFollowup::ADDMYTICKET,
               ],
            ],
            'expected' => true, // has not enough rights so cannot take into account
         ],
         [
            'input'    => [
               '_users_id_requester' => ['3'], // "post-only"
            ],
            'user'     => [
               'login'    => 'tech',
               'password' => 'tech',
               'rights'   => [
                  'task' => \READ,
                  'followup' => \READ + \ITILFollowup::ADDGROUPTICKET,
               ],
            ],
            'expected' => true, // has not enough rights so cannot take into account
         ],
         [
            'input'    => [
               '_users_id_requester'        => ['3'], // "post-only"
               'takeintoaccount_delay_stat' => '10',
            ],
            'user'     => [
               'login'    => 'tech',
               'password' => 'tech',
            ],
            'expected' => false, // ticket is already taken into account
         ],
         /* Cannot test that requester user can take ticket into account if also assigned
          * because assigning a user makes the ticket automatically taken into account.
          * We decided with @orthagh to keep this rule even if it cannot be tested yet.
         [
            'input'    => [
               '_users_id_requester' => ['4'], // "tech"
               '_users_id_assign'    => ['4'], // "tech"
            ],
            'user'     => [
               'login'    => 'tech',
               'password' => 'tech',
            ],
            'expected' => true, // is requester but also assigned, so can take into account
         ],
         */
      ];
   }
   /**
    * Tests ability to take a ticket into account.
    *
    * @param array   $input    Input used to create the ticket
    * @param array   $user     Array containing 'login' and 'password' fields of tested user,
    *                          and a 'rights' array if rights have to be forced
    * @param boolean $expected Expected result of "Ticket::canTakeIntoAccount()" method
    *
    * @dataProvider canTakeIntoAccountProvider
    */
   public function testCanTakeIntoAccount(array $input, array $user, $expected) {
      // Create a ticket
      $this->login();
      $_SESSION['glpiset_default_tech'] = false;
      $ticket = new \Ticket();
      $ticketId = $this->integer(
         (int)$ticket->add([
            'name'    => '',
            'content' => 'A ticket to check canTakeIntoAccount() results',
         ] + $input)
      )->isGreaterThan(0);
      // Reload ticket to get all default fields values
      $this->boolean($ticket->getFromDB($ticketId))->isTrue();
      // Check if "takeintoaccount_delay_stat" is not automatically defined
      $expectedStat = array_key_exists('takeintoaccount_delay_stat', $input)
         ? $input['takeintoaccount_delay_stat']
         : 0;
      $this->integer((int)$ticket->fields['takeintoaccount_delay_stat'])->isEqualTo($expectedStat);
      // Login with tested user
      $this->login($user['login'], $user['password']);
      // Apply specific rights if defined
      if (array_key_exists('rights', $user)) {
         foreach ($user['rights'] as $rightname => $rightvalue) {
            $_SESSION['glpiactiveprofile'][$rightname] = $rightvalue;
         }
      }
      // Verify result
      $this->boolean($ticket->canTakeIntoAccount())->isEqualTo($expected);
   }
}
