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

use CommonITILObject;
use DbTestCase;

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
      $itiltemplate    = new \TicketTemplate;
      $itiltemplate_id = $itiltemplate->add([
         'name' => 'my ticket template',
      ]);
      $this->boolean($itiltemplate->isNewItem())->isFalse();
      $ttp = new \TicketTemplatePredefinedField();
      $ttp->add([
         'tickettemplates_id' => $itiltemplate_id,
         'num'                => '175',
         'value'              => $ttA_id,
      ]);
      $this->boolean($ttp->isNewItem())->isFalse();
      $ttp->add([
         'tickettemplates_id' => $itiltemplate_id,
         'num'                => '176',
         'value'              => $ttB_id,
      ]);
      $this->boolean($ttp->isNewItem())->isFalse();

      // 4 - create a ticket category using the ticket template
      $itilcat    = new \ITILCategory;
      $itilcat_id = $itilcat->add([
         'name'                        => 'my itil category',
         'ticketltemplates_id_incident'=> $itiltemplate_id,
         'tickettemplates_id_demand'   => $itiltemplate_id,
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
         '_tickettemplates_id' => $itiltemplate_id,
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

      // Opening date, editable
      preg_match(
         '/.*<input[^>]*name=[\'"]date[\'"][^>]*>.*/',
         $output,
         $matches
      );
      $this->array($matches)->hasSize(($openDate === true ? 1 : 0));

      // Time to own, editable
      preg_match(
         '/.*<input[^>]*name=[\'"]time_to_own[\'"][^>]*>.*/',
         $output,
         $matches
      );
      $this->array($matches)->hasSize(($timeOwnResolve === true ? 1 : 0));

      // Internal time to own, editable
      preg_match(
         '/.*<input[^>]*name=[\'"]internal_time_to_own[\'"][^>]*>.*/',
         $output,
         $matches
      );
      $this->array($matches)->hasSize(($timeOwnResolve === true ? 1 : 0));

      // Time to resolve, editable
      preg_match(
         '/.*<input[^>]*name=[\'"]time_to_resolve[\'"][^>]*>.*/',
         $output,
         $matches
      );
      $this->array($matches)->hasSize(($timeOwnResolve === true ? 1 : 0));

      // Internal time to resolve, editable
      preg_match(
         '/.*<input[^>]*name=[\'"]internal_time_to_resolve[\'"][^>]*>.*/',
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

      //Ticket name, not editable
      preg_match(
         '/.*<div[^>]*class="[^"]*card-title[^"]*"[^>]*>.*/',
         $output,
         $matches
      );
      $this->array($matches)->hasSize(($name === true ? 1 : 0));

      //Priority, editable
      preg_match(
         '/.*<select name=\'priority\'[^>]*>.*/',
         $output,
         $matches
      );
      $this->array($matches)->hasSize(($priority === true ? 1 : 0));

      //Save button
      preg_match(
         '/.*<button[^>]*type="submit"[^>]*name="update"[^>]*>.*/',
         $output,
         $matches
      );
      $this->array($matches)->hasSize(($save === true ? 1 : 0), ($save === true ? 'Save button missing' : 'Save button present'));

      //Assign to
      /*preg_match(
         '|.*<select name=\'_itil_assign\[_type\]\'[^>]*>.*|',
         $output,
         $matches
      );
      $this->array($matches)->hasSize(($assign === true ? 1 : 0));*/
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

   public function testClone() {
      $this->login();
      $this->setEntity('Root entity', true);
      $ticket = getItemByTypeName('Ticket', '_ticket01');

      $date = date('Y-m-d H:i:s');
      $_SESSION['glpi_currenttime'] = $date;

      // Test item cloning
      $added = $ticket->clone();
      $this->integer((int)$added)->isGreaterThan(0);

      $clonedTicket = new \Ticket();
      $this->boolean($clonedTicket->getFromDB($added))->isTrue();

      $fields = $ticket->fields;

      // Check the ticket values. Id and dates must be different, everything else must be equal
      foreach ($fields as $k => $v) {
         switch ($k) {
            case 'id':
               $this->variable($clonedTicket->getField($k))->isNotEqualTo($ticket->getField($k));
               break;
            case 'date_mod':
            case 'date_creation':
               $dateClone = new \DateTime($clonedTicket->getField($k));
               $expectedDate = new \DateTime($date);
               $this->dateTime($dateClone)->isEqualTo($expectedDate);
               break;
            default:
               $this->executeOnFailure(
                  function() use ($k) {
                      dump($k);
                  }
               )->variable($clonedTicket->getField($k))->isEqualTo($ticket->getField($k));
         }
      }
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
                                               + \Ticket::READMY
                                               + \Ticket::READALL
                                               + \Ticket::READGROUP
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
            'input'    => [
               'urgency'   => 2,
               'impact'    => 2
            ],
            'urgency'  => '2',
            'impact'   => '2',
            'priority' => '2'
         ], [
            'input'    => [
               'urgency'   => 5
            ],
            'urgency'  => '5',
            'impact'   => '3',
            'priority' => '4'
         ], [
            'input'    => [
               'impact'   => 5
            ],
            'urgency'  => '3',
            'impact'   => '5',
            'priority' => '4'
         ], [
            'input'    => [
               'urgency'   => 5,
               'impact'    => 5
            ],
            'urgency'  => '5',
            'impact'   => '5',
            'priority' => '5'
         ], [
            'input'    => [
               'urgency'   => 5,
               'impact'    => 1
            ],
            'urgency'  => '5',
            'impact'   => '1',
            'priority' => '2'
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
      $this->integer((int) $input['slas_id_tto'])->isEqualTo(0);
      $this->integer((int) $input['slas_id_ttr'])->isEqualTo(0);

      $this->string($input['internal_time_to_resolve'])->isEqualTo('NULL');
      $this->string($input['internal_time_to_own'])->isEqualTo('NULL');
      $this->integer((int) $input['olas_id_tto'])->isEqualTo(0);
      $this->integer((int) $input['olas_id_ttr'])->isEqualTo(0);

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
   protected function canTakeIntoAccountProvider() {
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
                  'followup' => \READ + \ITILFollowup::ADDALLTICKET,
               ],
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
                  'followup' => \READ + \ITILFollowup::ADDMYTICKET,
               ],
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
                  'followup' => \READ + \ITILFollowup::ADDGROUPTICKET,
               ],
            ],
            'expected' => true, // has enough rights so can take into account
         ],
         [
            'input'    => [
               '_do_not_compute_takeintoaccount' => 1,
               '_users_id_requester'             => ['4'], // "tech"
               '_users_id_assign'                => ['4'], // "tech"
            ],
            'user'     => [
               'login'    => 'tech',
               'password' => 'tech',
            ],
            // is requester but also assigned, so can take into account
            // this is only possible if "_do_not_compute_takeintoaccount" flag is set by business rules
            'expected' => true,
         ],
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
      $ticketId = $ticket->add(
         $input + [
            'name'    => '',
            'content' => 'A ticket to check canTakeIntoAccount() results',
            'status'  => CommonITILObject::ASSIGNED
         ]
      );
      $this->integer((int)$ticketId)->isGreaterThan(0);
      // Reload ticket to get all default fields values
      $this->boolean($ticket->getFromDB($ticketId))->isTrue();
      // Validate that "takeintoaccount_delay_stat" is not automatically defined
      $this->integer((int)$ticket->fields['takeintoaccount_delay_stat'])->isEqualTo(0);
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

      // Check that computation of "takeintoaccount_delay_stat" can be prevented
      sleep(1); // be sure to wait at least one second before updating
      $this->boolean(
         $ticket->update(
            [
               'id'                              => $ticketId,
               'content'                         => 'Updated ticket 1',
               '_do_not_compute_takeintoaccount' => 1
            ]
         )
      )->isTrue();
      $this->integer((int)$ticket->fields['takeintoaccount_delay_stat'])->isEqualTo(0);

      // Check that computation of "takeintoaccount_delay_stat" is done if user can take into account
      $this->boolean(
         $ticket->update(
            [
               'id'      => $ticketId,
               'content' => 'Updated ticket 2',
            ]
         )
      )->isTrue();
      if (!$expected) {
         $this->integer((int)$ticket->fields['takeintoaccount_delay_stat'])->isEqualTo(0);
      } else {
         $this->integer((int)$ticket->fields['takeintoaccount_delay_stat'])->isGreaterThan(0);
      }
   }

   /**
    * Tests taken into account state.
    */
   public function testIsAlreadyTakenIntoAccount() {

      // Create a ticket
      $this->login();
      $_SESSION['glpiset_default_tech'] = false;
      $ticket = new \Ticket();
      $ticket_id = $ticket->add(
         [
            'name'    => '',
            'content' => 'A ticket to check isAlreadyTakenIntoAccount() results',
         ]
      );
      $this->integer((int)$ticket_id)->isGreaterThan(0);

      // Reload ticket to get all default fields values
      $this->boolean($ticket->getFromDB($ticket_id))->isTrue();

      // Empty ticket is not taken into account
      $this->boolean($ticket->isAlreadyTakenIntoAccount())->isFalse();

      // Take into account
      $this->login('tech', 'tech');
      $ticket_user = new \Ticket_User();
      $ticket_user_id = $ticket_user->add(
         [
            'tickets_id'       => $ticket_id,
            'users_id'         => \Session::getLoginUserID(),
            'use_notification' => 1,
            'type'             => \CommonITILActor::ASSIGN
         ]
      );
      $this->integer((int)$ticket_user_id)->isGreaterThan(0);

      // Assign to tech made ticket taken into account
      $this->boolean($ticket->getFromDB($ticket_id))->isTrue();
      $this->boolean($ticket->isAlreadyTakenIntoAccount())->isTrue();
   }

   public function testCronCloseTicket() {
      global $DB;
      $this->login();
      // set default calendar and autoclose delay in root entity
      $entity = new \Entity;
      $this->boolean($entity->update([
         'id'              => 0,
         'calendars_id'    => 1,
         'autoclose_delay' => 5,
      ]))->isTrue();

      // create some solved tickets at various solvedate
      $ticket = new \Ticket;
      $tickets_id_1 = $ticket->add([
         'name'        => "test autoclose 1",
         'content'     => "test autoclose 1",
         'entities_id' => 0,
         'status'      => \CommonITILObject::SOLVED,
      ]);
      $this->integer((int)$tickets_id_1)->isGreaterThan(0);
      $DB->update('glpi_tickets', [
         'solvedate' => date('Y-m-d 10:00:00', time() - 10 * DAY_TIMESTAMP),
      ], [
         'id' => $tickets_id_1,
      ]);
      $tickets_id_2 = $ticket->add([
         'name'        => "test autoclose 1",
         'content'     => "test autoclose 1",
         'entities_id' => 0,
         'status'      => \CommonITILObject::SOLVED,
      ]);
      $DB->update('glpi_tickets', [
         'solvedate' => date('Y-m-d 10:00:00', time()),
      ], [
         'id' => $tickets_id_2,
      ]);
      $this->integer((int)$tickets_id_2)->isGreaterThan(0);

      // launch Cron for closing tickets
      $mode = - \CronTask::MODE_EXTERNAL; // force
      \CronTask::launch($mode, 5, 'closeticket');

      // check ticket status
      $this->boolean($ticket->getFromDB($tickets_id_1))->isTrue();
      $this->integer((int)$ticket->fields['status'])->isEqualTo(\CommonITILObject::CLOSED);
      $this->boolean($ticket->getFromDB($tickets_id_2))->isTrue();
      $this->integer((int)$ticket->fields['status'])->isEqualTo(\CommonITILObject::SOLVED);
   }

   /**
    * @see self::testTakeIntoAccountDelayComputationOnCreate()
    * @see self::testTakeIntoAccountDelayComputationOnUpdate()
    */
   protected function takeIntoAccountDelayComputationProvider() {
      $this->login();
      $group = new \Group();
      $group_id = $group->add(['name' => 'Test group']);
      $this->integer((int)$group_id)->isGreaterThan(0);

      $group_user = new \Group_User();
      $this->integer(
         (int)$group_user->add([
            'groups_id' => $group_id,
            'users_id'  => '4', // "tech"
         ])
      )->isGreaterThan(0);

      $test_cases = [
         [
            'input'    => [
               'content' => 'test',
            ],
            'computed' => false, // not computed as tech is requester
         ],
         [
            'input'    => [
               '_users_id_assign' => '4', // "tech"
            ],
            'computed' => true, // computed on asignment
         ],
         [
            'input'    => [
               '_users_id_observer' => '4', // "tech"
            ],
            'computed' => false, // not computed as new actor is not assigned
         ],
         /* Triggers PHP error "Uncaught Error: [] operator not supported for strings in /var/www/glpi/inc/ticket.class.php:1162"
         [
            'input'    => [
               '_users_id_requester' => '3', // "post-only"
            ],
            'computed' => false, // not computed as new actor is not assigned
         ],
         */
         [
            'input'    => [
               '_additional_assigns' => [
                  ['users_id' => '4'], // "tech"
               ],
            ],
            'computed' => true, // computed on asignment
         ],
         [
            'input'    => [
               '_additional_observers' => [
                  ['users_id' => '4'], // "tech"
               ],
            ],
            'computed' => false, // not computed as new actor is not assigned
         ],
         [
            'input'    => [
               '_additional_requesters' => [
                  ['users_id' => '2'], // "post-only"
               ],
            ],
            'computed' => false, // not computed as new actor is not assigned
         ],
         [
            'input'    => [
               '_groups_id_assign' => $group_id,
            ],
            'computed' => true, // computed on asignment
         ],
         [
            'input'    => [
               '_groups_id_observer' => $group_id,
            ],
            'computed' => false, // not computed as new actor is not assigned
         ],
         [
            'input'    => [
               '_groups_id_requester' => $group_id,
            ],
            'computed' => false, // not computed as new actor is not assigned
         ],
         [
            'input'    => [
               '_additional_groups_assigns' => [$group_id],
            ],
            'computed' => true, // computed on asignment
         ],
         [
            'input'    => [
               '_additional_groups_observers' => [$group_id],
            ],
            'computed' => false, // not computed as new actor is not assigned
         ],
         [
            'input'    => [
               '_additional_groups_requesters' => [$group_id],
            ],
            'computed' => false, // not computed as new actor is not assigned
         ],
         /* Not computing delay, do not know why
         [
            'input'    => [
               '_suppliers_id_assign' => '1', // "_suplier01_name"
            ],
            'computed' => true, // computed on asignment
         ],
         */
         [
            'input'    => [
               '_additional_suppliers_assigns' => [
                  ['suppliers_id' => '1'], // "_suplier01_name"
               ],
            ],
            'computed' => true, // computed on asignment
         ],
      ];

      // for all test cases that expect a computation
      // add a test case with '_do_not_compute_takeintoaccount' flag to check that computation is prevented
      foreach ($test_cases as $test_case) {
         $test_case['input']['_do_not_compute_takeintoaccount'] = 1;
         $test_case['computed'] = false;
         $test_cases[] = $test_case;
      }

      return $test_cases;
   }

   /**
    * Tests that "takeintoaccount_delay_stat" is computed (or not) as expected on ticket creation.
    *
    * @param array   $input    Input used to create the ticket
    * @param boolean $computed Expected computation state
    *
    * @dataProvider takeIntoAccountDelayComputationProvider
    */
   public function testTakeIntoAccountDelayComputationOnCreate(array $input, $computed) {

      // Create a ticket
      $this->login('tech', 'tech'); // Login with tech to be sure to be the requester
      $_SESSION['glpiset_default_tech'] = false;
      $ticket = new \Ticket();
      $ticketId = $ticket->add(
         $input + [
            'name'    => '',
            'content' => 'A ticket to check takeintoaccount_delay_stat computation state',
         ]
      );
      $this->integer((int)$ticketId)->isGreaterThan(0);

      // Reload ticket to get all default fields values
      $this->boolean($ticket->getFromDB($ticketId))->isTrue();

      if (!$computed) {
         $this->integer((int)$ticket->fields['takeintoaccount_delay_stat'])->isEqualTo(0);
      } else {
         $this->integer((int)$ticket->fields['takeintoaccount_delay_stat'])->isGreaterThan(0);
      }
   }

   /**
    * Tests that "takeintoaccount_delay_stat" is computed (or not) as expected on ticket update.
    *
    * @param array   $input     Input used to update the ticket
    * @param boolean $computed  Expected computation state
    *
    * @dataProvider takeIntoAccountDelayComputationProvider
    */
   public function testTakeIntoAccountDelayComputationOnUpdate(array $input, $computed) {

      // Create a ticket
      $this->login('tech', 'tech'); // Login with tech to be sure to be the requester
      $_SESSION['glpiset_default_tech'] = false;
      $ticket = new \Ticket();
      $ticketId = $ticket->add(
         [
            'name'    => '',
            'content' => 'A ticket to check takeintoaccount_delay_stat computation state',
         ]
      );
      $this->integer((int)$ticketId)->isGreaterThan(0);

      // Reload ticket to get all default fields values
      $this->boolean($ticket->getFromDB($ticketId))->isTrue();

      // Validate that "takeintoaccount_delay_stat" is not automatically defined
      $this->integer((int)$ticket->fields['takeintoaccount_delay_stat'])->isEqualTo(0);

      // Login with tech to be sure to be have rights to take into account
      $this->login('tech', 'tech');

      sleep(1); // be sure to wait at least one second before updating
      $this->boolean(
         $ticket->update(
            $input + [
               'id' => $ticketId,
            ]
         )
      )->isTrue();

      // Reload ticket to get fresh values that can be defined by a tier object
      $this->boolean($ticket->getFromDB($ticketId))->isTrue();

      if (!$computed) {
         $this->integer((int)$ticket->fields['takeintoaccount_delay_stat'])->isEqualTo(0);
      } else {
         $this->integer((int)$ticket->fields['takeintoaccount_delay_stat'])->isGreaterThan(0);
      }
   }

   /**
    * @see self::testStatusComputationOnCreate()
    */
   protected function statusComputationOnCreateProvider() {

      $group = new \Group();
      $group_id = $group->add(['name' => 'Test group']);
      $this->integer((int)$group_id)->isGreaterThan(0);

      return [
         [
            'input'    => [
               '_users_id_assign' => ['4'], // "tech"
               'status' => \CommonITILObject::INCOMING,
            ],
            'expected' => \CommonITILObject::ASSIGNED, // incoming changed to assign as actors are set
         ],
         [
            'input'    => [
               '_groups_id_assign' => $group_id,
               'status' => \CommonITILObject::INCOMING,
            ],
            'expected' => \CommonITILObject::ASSIGNED, // incoming changed to assign as actors are set
         ],
         [
            'input'    => [
               '_suppliers_id_assign' => '1', // "_suplier01_name"
               'status' => \CommonITILObject::INCOMING,
            ],
            'expected' => \CommonITILObject::ASSIGNED, // incoming changed to assign as actors are set
         ],
         [
            'input'    => [
               '_users_id_assign' => ['4'], // "tech"
               'status' => \CommonITILObject::INCOMING,
               '_do_not_compute_status' => '1',
            ],
            'expected' => \CommonITILObject::INCOMING, // flag prevent status change
         ],
         [
            'input'    => [
               '_groups_id_assign' => $group_id,
               'status' => \CommonITILObject::INCOMING,
               '_do_not_compute_status' => '1',
            ],
            'expected' => \CommonITILObject::INCOMING, // flag prevent status change
         ],
         [
            'input'    => [
               '_suppliers_id_assign' => '1', // "_suplier01_name"
               'status' => \CommonITILObject::INCOMING,
               '_do_not_compute_status' => '1',
            ],
            'expected' => \CommonITILObject::INCOMING, // flag prevent status change
         ],
         [
            'input'    => [
               '_users_id_assign' => ['4'], // "tech"
               'status' => \CommonITILObject::WAITING,
            ],
            'expected' => \CommonITILObject::WAITING, // status not changed as not "new"
         ],
         [
            'input'    => [
               '_groups_id_assign' => $group_id,
               'status' => \CommonITILObject::WAITING,
            ],
            'expected' => \CommonITILObject::WAITING, // status not changed as not "new"
         ],
         [
            'input'    => [
               '_suppliers_id_assign' => '1', // "_suplier01_name"
               'status' => \CommonITILObject::WAITING,
            ],
            'expected' => \CommonITILObject::WAITING, // status not changed as not "new"
         ],
      ];
   }

   /**
    * Check computed status on ticket creation..
    *
    * @param array   $input     Input used to create the ticket
    * @param boolean $expected  Expected status
    *
    * @dataProvider statusComputationOnCreateProvider
    */
   public function testStatusComputationOnCreate(array $input, $expected) {

      // Create a ticket
      $this->login();
      $_SESSION['glpiset_default_tech'] = false;
      $ticket = new \Ticket();
      $ticketId = $this->integer(
         (int)$ticket->add([
            'name'    => '',
            'content' => 'A ticket to check status computation',
         ] + $input)
      )->isGreaterThan(0);

      // Reload ticket to get computed fields values
      $this->boolean($ticket->getFromDB($ticketId))->isTrue();

      // Check status
      $this->integer((int)$ticket->fields['status'])->isEqualTo($expected);
   }

   public function testLocationAssignment() {
      $rule = new \Rule();
      $rule->getFromDBByCrit([
         'sub_type' => 'RuleTicket',
         'name' => 'Ticket location from user',
      ]);
      $location = new \Location;
      $location->getFromDBByCrit([
         'name' => '_location01'
      ]);
      $user = new \User;
      $user->add([
         'name' => $this->getUniqueString(),
         'locations_id' => $location->getID(),
      ]);

      // test ad ticket with single requester
      $ticket = new \Ticket;
      $rule->update([
         'id' => $rule->getID(),
         'is_active' => '1'
      ]);
      $ticket->add([
         '_users_id_requester' => $user->getID(),
         'name' => 'test location assignment',
         'content' => 'test location assignment',
      ]);
      $rule->update([
         'id' => $rule->getID(),
         'is_active' => '0'
      ]);
      $ticket->getFromDB($ticket->getID());
      $this->integer((int) $ticket->fields['locations_id'])->isEqualTo($location->getID());

      // test add ticket with multiple requesters
      $ticket = new \Ticket;
      $rule->update([
         'id' => $rule->getID(),
         'is_active' => '1'
      ]);
      $ticket->add([
         '_users_id_requester' => [$user->getID(), 2],
         'name' => 'test location assignment',
         'content' => 'test location assignment',
      ]);
      $rule->update([
         'id' => $rule->getID(),
         'is_active' => '0'
      ]);
      $ticket->getFromDB($ticket->getID());
      $this->integer((int) $ticket->fields['locations_id'])->isEqualTo($location->getID());

      // test add ticket with multiple requesters
      $ticket = new \Ticket;
      $rule->update([
         'id' => $rule->getID(),
         'is_active' => '1'
      ]);
      $ticket->add([
         '_users_id_requester' => [2, $user->getID()],
         'name' => 'test location assignment',
         'content' => 'test location assignment',
      ]);
      $rule->update([
         'id' => $rule->getID(),
         'is_active' => '0'
      ]);
      $ticket->getFromDB($ticket->getID());
      $this->integer((int) $ticket->fields['locations_id'])->isEqualTo(0);
   }

   public function testCronPurgeTicket() {

      $this->login(); // must be logged as Document_Item uses Session::getLoginUserID()

      global $DB;
      // set default calendar and autoclose delay in root entity
      $entity = new \Entity;
      $this->boolean($entity->update([
         'id'              => 0,
         'calendars_id'    => 1,
         'autopurge_delay' => 5,
      ]))->isTrue();

      $doc = new \Document();
      $did = (int)$doc->add([
         'name'   => 'test doc'
      ]);
      $this->integer($did)->isGreaterThan(0);

      // create some closed tickets at various solvedate
      $ticket = new \Ticket;
      $tickets_id_1 = $ticket->add([
         'name'            => "test autopurge 1",
         'content'         => "test autopurge 1",
         'entities_id'     => 0,
         'status'          => \CommonITILObject::CLOSED,
         '_documents_id'   => [$did]
      ]);
      $this->integer((int)$tickets_id_1)->isGreaterThan(0);
      $this->boolean(
         $DB->update('glpi_tickets', [
            'closedate' => date('Y-m-d 10:00:00', time() - 10 * DAY_TIMESTAMP),
         ], [
            'id' => $tickets_id_1,
         ])
      )->isTrue();
      $this->boolean($ticket->getFromDB($tickets_id_1))->isTrue();

      $docitem = new \Document_Item();
      $this->boolean($docitem->getFromDBByCrit(['itemtype' => 'Ticket', 'items_id' => $tickets_id_1]))->isTrue();

      $tickets_id_2 = $ticket->add([
         'name'        => "test autopurge 2",
         'content'     => "test autopurge 2",
         'entities_id' => 0,
         'status'      => \CommonITILObject::CLOSED,
      ]);
      $this->integer((int)$tickets_id_2)->isGreaterThan(0);
      $this->boolean(
         $DB->update('glpi_tickets', [
            'closedate' => date('Y-m-d 10:00:00', time()),
         ], [
            'id' => $tickets_id_2,
         ])
      );

      // launch Cron for closing tickets
      $mode = - \CronTask::MODE_EXTERNAL; // force
      \CronTask::launch($mode, 5, 'purgeticket');

      // check ticket presence
      // first ticket should have been removed
      $this->boolean($ticket->getFromDB($tickets_id_1))->isFalse();
      //also ensure linked document has been dropped
      $this->boolean($docitem->getFromDBByCrit(['itemtype' => 'Ticket', 'items_id' => $tickets_id_1]))->isFalse();
      $this->boolean($doc->getFromDB($did))->isTrue(); //document itself remains
      //second ticket is still present
      $this->boolean($ticket->getFromDB($tickets_id_2))->isTrue();
      $this->integer((int)$ticket->fields['status'])->isEqualTo(\CommonITILObject::CLOSED);
   }

   public function testMerge() {
      $this->login();
      $_SESSION['glpiactiveprofile']['interface'] = '';
      $this->setEntity('Root entity', true);

      $ticket = new \Ticket();
      $ticket1 = $ticket->add([
         'name'        => "test merge 1",
         'content'     => "test merge 1",
         'entities_id' => 0,
         'status'      => \CommonITILObject::INCOMING,
      ]);
      $ticket2 = $ticket->add([
         'name'        => "test merge 2",
         'content'     => "test merge 2",
         'entities_id' => 0,
         'status'      => \CommonITILObject::INCOMING,
      ]);
      $ticket3 = $ticket->add([
         'name'        => "test merge 3",
         'content'     => "test merge 3",
         'entities_id' => 0,
         'status'      => \CommonITILObject::INCOMING,
      ]);

      $task = new \TicketTask();
      $fup = new \ITILFollowup();
      $task->add([
         'tickets_id'   => $ticket2,
         'content'      => 'ticket 2 task 1'
      ]);
      $task->add([
         'tickets_id'   => $ticket3,
         'content'      => 'ticket 3 task 1'
      ]);
      $fup->add([
         'itemtype'  => 'Ticket',
         'items_id'  => $ticket2,
         'content'   => 'ticket 2 fup 1'
      ]);
      $fup->add([
         'itemtype'  => 'Ticket',
         'items_id'  => $ticket3,
         'content'   => 'ticket 3 fup 1'
      ]);

      $document = new \Document();
      $documents_id = $document->add([
         'name'     => 'basic document in both',
         'filename' => 'doc.xls',
         'users_id' => '2', // user "glpi"
      ]);
      $documents_id2 = $document->add([
         'name'     => 'basic document in target',
         'filename' => 'doc.xls',
         'users_id' => '2', // user "glpi"
      ]);
      $documents_id3 = $document->add([
         'name'     => 'basic document in sources',
         'filename' => 'doc.xls',
         'users_id' => '2', // user "glpi"
      ]);

      $document_item = new \Document_Item();
      // Add document to two tickets to test merging duplicates
      $document_item->add([
         'itemtype'     => 'Ticket',
         'items_id'     => $ticket2,
         'documents_id' => $documents_id,
         'entities_id'  => '0',
         'is_recursive' => 0
      ]);
      $document_item->add([
         'itemtype'     => 'Ticket',
         'items_id'     => $ticket1,
         'documents_id' => $documents_id,
         'entities_id'  => '0',
         'is_recursive' => 0
      ]);
      $document_item->add([
         'itemtype'     => 'Ticket',
         'items_id'     => $ticket1,
         'documents_id' => $documents_id2,
         'entities_id'  => '0',
         'is_recursive' => 0
      ]);
      $document_item->add([
         'itemtype'     => 'Ticket',
         'items_id'     => $ticket2,
         'documents_id' => $documents_id3,
         'entities_id'  => '0',
         'is_recursive' => 0
      ]);
      $document_item->add([
         'itemtype'     => 'Ticket',
         'items_id'     => $ticket3,
         'documents_id' => $documents_id3,
         'entities_id'  => '0',
         'is_recursive' => 0
      ]);

      $ticket_user = new \Ticket_User();
      $ticket_user->add([
         'tickets_id'         => $ticket1,
         'type'               => \Ticket_User::REQUESTER,
         'users_id'           => 2
      ]);
      $ticket_user->add([ // Duplicate with #1
         'tickets_id'         => $ticket3,
         'type'               => \Ticket_User::REQUESTER,
         'users_id'           => 2
      ]);
      $ticket_user->add([
         'tickets_id'         => $ticket1,
         'users_id'           => 0,
         'type'               => \Ticket_User::REQUESTER,
         'alternative_email'  => 'test@glpi.com'
      ]);
      $ticket_user->add([ // Duplicate with #3
         'tickets_id'         => $ticket2,
         'users_id'           => 0,
         'type'               => \Ticket_User::REQUESTER,
         'alternative_email'  => 'test@glpi.com'
      ]);
      $ticket_user->add([ // Duplicate with #1
         'tickets_id'         => $ticket2,
         'users_id'           => 2,
         'type'               => \Ticket_User::REQUESTER,
         'alternative_email'  => 'test@glpi.com'
      ]);
      $ticket_user->add([
         'tickets_id'         => $ticket3,
         'users_id'           => 2,
         'type'               => \Ticket_User::ASSIGN,
         'alternative_email'  => 'test@glpi.com'
      ]);

      $ticket_group = new \Group_Ticket();
      $ticket_group->add([
         'tickets_id'         => $ticket1,
         'groups_id'          => 1,
         'type'               => \Group_Ticket::REQUESTER
      ]);
      $ticket_group->add([ // Duplicate with #1
         'tickets_id'         => $ticket3,
         'groups_id'          => 1,
         'type'               => \Group_Ticket::REQUESTER
      ]);
      $ticket_group->add([
         'tickets_id'         => $ticket3,
         'groups_id'          => 1,
         'type'               => \Group_Ticket::ASSIGN
      ]);

      $ticket_supplier = new \Supplier_Ticket();
      $ticket_supplier->add([
         'tickets_id'         => $ticket1,
         'type'               => \Supplier_Ticket::REQUESTER,
         'suppliers_id'       => 2
      ]);
      $ticket_supplier->add([ // Duplicate with #1
         'tickets_id'         => $ticket3,
         'type'               => \Supplier_Ticket::REQUESTER,
         'suppliers_id'       => 2
      ]);
      $ticket_supplier->add([
         'tickets_id'         => $ticket1,
         'suppliers_id'       => 0,
         'type'               => \Supplier_Ticket::REQUESTER,
         'alternative_email'  => 'test@glpi.com'
      ]);
      $ticket_supplier->add([ // Duplicate with #3
         'tickets_id'         => $ticket2,
         'suppliers_id'       => 0,
         'type'               => \Supplier_Ticket::REQUESTER,
         'alternative_email'  => 'test@glpi.com'
      ]);
      $ticket_supplier->add([ // Duplicate with #1
         'tickets_id'         => $ticket2,
         'suppliers_id'       => 2,
         'type'               => \Supplier_Ticket::REQUESTER,
         'alternative_email'  => 'test@glpi.com'
      ]);
      $ticket_supplier->add([
         'tickets_id'         => $ticket3,
         'suppliers_id'       => 2,
         'type'               => \Supplier_Ticket::ASSIGN,
         'alternative_email'  => 'test@glpi.com'
      ]);

      $status = [];
      $mergeparams = [
         'linktypes' => [
            'ITILFollowup',
            'TicketTask',
            'Document'
         ],
         'link_type'  => \Ticket_Ticket::SON_OF
      ];

      \Ticket::merge($ticket1, [$ticket2, $ticket3], $status, $mergeparams);

      $status_counts = array_count_values($status);
      $failure_count = 0;
      if (array_key_exists(1, $status_counts)) {
         $failure_count += $status_counts[1];
      }
      if (array_key_exists(2, $status_counts)) {
         $failure_count += $status_counts[2];
      }

      $this->integer((int)$failure_count)->isEqualTo(0);

      $task_count = count($task->find(['tickets_id' => $ticket1]));
      $fup_count = count($fup->find([
         'itemtype' => 'Ticket',
         'items_id' => $ticket1]));
      $doc_count = count($document_item->find([
         'itemtype' => 'Ticket',
         'items_id' => $ticket1]));
      $user_count = count($ticket_user->find([
         'tickets_id' => $ticket1]));
      $group_count = count($ticket_group->find([
         'tickets_id' => $ticket1]));
      $supplier_count = count($ticket_supplier->find([
         'tickets_id' => $ticket1]));

      // Target ticket should have all tasks
      $this->integer((int)$task_count)->isEqualTo(2);
      // Target ticket should have all followups + 1 for each source ticket description
      $this->integer((int)$fup_count)->isEqualTo(4);
      // Target ticket should have the original document, one instance of the duplicate, and the new document from one of the source tickets
      $this->integer((int)$doc_count)->isEqualTo(3);
      // Target ticket should have all users not marked as duplicates above + original requester (ID: 6)
      $this->integer((int)$user_count)->isEqualTo(4);
      // Target ticket should have all groups not marked as duplicates above
      $this->integer((int)$group_count)->isEqualTo(2);
      // Target ticket should have all suppliers not marked as duplicates above
      $this->integer((int)$supplier_count)->isEqualTo(3);
   }

   /**
    * @see self::testGetAssociatedDocumentsCriteria()
    */
   protected function getAssociatedDocumentsCriteriaProvider() {
      $ticket = new \Ticket();
      $ticket_id = $ticket->add([
         'name'            => "test",
         'content'         => "test",
      ]);
      $this->integer((int)$ticket_id)->isGreaterThan(0);

      return [
         [
            'rights'   => [
               \Change::$rightname       => 0,
               \Problem::$rightname      => 0,
               \Ticket::$rightname       => 0,
               \ITILFollowup::$rightname => 0,
               \TicketTask::$rightname   => 0,
            ],
            'ticket_id'      => $ticket_id,
            'bypass_rights'  => false,
            'expected_where' => sprintf(
               "(`glpi_documents_items`.`itemtype` = 'Ticket' AND `glpi_documents_items`.`items_id` = '%1\$s') OR (`glpi_documents_items`.`itemtype` = 'TicketValidation' AND `glpi_documents_items`.`items_id` IN (SELECT `id` FROM `glpi_ticketvalidations` WHERE `glpi_ticketvalidations`.`tickets_id` = '%1\$s'))",
               $ticket_id
            ),
         ],
         [
            'rights'   => [
               \Change::$rightname       => 0,
               \Problem::$rightname      => 0,
               \Ticket::$rightname       => \READ,
               \ITILFollowup::$rightname => 0,
               \TicketTask::$rightname   => 0,
            ],
            'ticket_id'      => $ticket_id,
            'bypass_rights'  => false,
            'expected_where' => sprintf(
               "(`glpi_documents_items`.`itemtype` = 'Ticket' AND `glpi_documents_items`.`items_id` = '%1\$s')"
               . " OR (`glpi_documents_items`.`itemtype` = 'ITILFollowup' AND `glpi_documents_items`.`items_id` IN (SELECT `id` FROM `glpi_itilfollowups` WHERE `glpi_itilfollowups`.`itemtype` = 'Ticket' AND `glpi_itilfollowups`.`items_id` = '%1\$s' AND ((`is_private` = '0' OR `users_id` = '%2\$s'))))"
               . " OR (`glpi_documents_items`.`itemtype` = 'ITILSolution' AND `glpi_documents_items`.`items_id` IN (SELECT `id` FROM `glpi_itilsolutions` WHERE `glpi_itilsolutions`.`itemtype` = 'Ticket' AND `glpi_itilsolutions`.`items_id` = '%1\$s'))"
               . " OR (`glpi_documents_items`.`itemtype` = 'TicketValidation' AND `glpi_documents_items`.`items_id` IN (SELECT `id` FROM `glpi_ticketvalidations` WHERE `glpi_ticketvalidations`.`tickets_id` = '%1\$s'))",
               $ticket_id,
               getItemByTypeName('User', TU_USER, true)
            ),
         ],
         [
            'rights'   => [
               \Change::$rightname       => 0,
               \Problem::$rightname      => 0,
               \Ticket::$rightname       => \READ,
               \ITILFollowup::$rightname => \ITILFollowup::SEEPUBLIC,
               \TicketTask::$rightname   => \TicketTask::SEEPUBLIC,
            ],
            'ticket_id'      => $ticket_id,
            'bypass_rights'  => false,
            'expected_where' => sprintf(
               "(`glpi_documents_items`.`itemtype` = 'Ticket' AND `glpi_documents_items`.`items_id` = '%1\$s')"
               . " OR (`glpi_documents_items`.`itemtype` = 'ITILFollowup' AND `glpi_documents_items`.`items_id` IN (SELECT `id` FROM `glpi_itilfollowups` WHERE `glpi_itilfollowups`.`itemtype` = 'Ticket' AND `glpi_itilfollowups`.`items_id` = '%1\$s' AND ((`is_private` = '0' OR `users_id` = '%2\$s'))))"
               . " OR (`glpi_documents_items`.`itemtype` = 'ITILSolution' AND `glpi_documents_items`.`items_id` IN (SELECT `id` FROM `glpi_itilsolutions` WHERE `glpi_itilsolutions`.`itemtype` = 'Ticket' AND `glpi_itilsolutions`.`items_id` = '%1\$s'))"
               . " OR (`glpi_documents_items`.`itemtype` = 'TicketValidation' AND `glpi_documents_items`.`items_id` IN (SELECT `id` FROM `glpi_ticketvalidations` WHERE `glpi_ticketvalidations`.`tickets_id` = '%1\$s'))"
               . " OR (`glpi_documents_items`.`itemtype` = 'TicketTask' AND `glpi_documents_items`.`items_id` IN (SELECT `id` FROM `glpi_tickettasks` WHERE `tickets_id` = '%1\$s' AND ((`is_private` = '0' OR `users_id` = '%2\$s'))))",
               $ticket_id,
               getItemByTypeName('User', TU_USER, true)
            ),
         ],
         [
            'rights'   => [
               \Change::$rightname       => 0,
               \Problem::$rightname      => 0,
               \Ticket::$rightname       => \READ,
               \ITILFollowup::$rightname => \ITILFollowup::SEEPRIVATE,
               \TicketTask::$rightname   => 0,
            ],
            'ticket_id'      => $ticket_id,
            'bypass_rights'  => false,
            'expected_where' => sprintf(
               "(`glpi_documents_items`.`itemtype` = 'Ticket' AND `glpi_documents_items`.`items_id` = '%1\$s')"
               . " OR (`glpi_documents_items`.`itemtype` = 'ITILFollowup' AND `glpi_documents_items`.`items_id` IN (SELECT `id` FROM `glpi_itilfollowups` WHERE `glpi_itilfollowups`.`itemtype` = 'Ticket' AND `glpi_itilfollowups`.`items_id` = '%1\$s'))"
               . " OR (`glpi_documents_items`.`itemtype` = 'ITILSolution' AND `glpi_documents_items`.`items_id` IN (SELECT `id` FROM `glpi_itilsolutions` WHERE `glpi_itilsolutions`.`itemtype` = 'Ticket' AND `glpi_itilsolutions`.`items_id` = '%1\$s'))"
               . " OR (`glpi_documents_items`.`itemtype` = 'TicketValidation' AND `glpi_documents_items`.`items_id` IN (SELECT `id` FROM `glpi_ticketvalidations` WHERE `glpi_ticketvalidations`.`tickets_id` = '%1\$s'))",
               $ticket_id,
               getItemByTypeName('User', TU_USER, true)
            ),
         ],
         [
            'rights'   => [
               \Change::$rightname       => 0,
               \Problem::$rightname      => 0,
               \Ticket::$rightname       => \READ,
               \ITILFollowup::$rightname => \ITILFollowup::SEEPUBLIC,
               \TicketTask::$rightname   => \TicketTask::SEEPRIVATE,
            ],
            'ticket_id'      => $ticket_id,
            'bypass_rights'  => false,
            'expected_where' => sprintf(
               "(`glpi_documents_items`.`itemtype` = 'Ticket' AND `glpi_documents_items`.`items_id` = '%1\$s')"
               . " OR (`glpi_documents_items`.`itemtype` = 'ITILFollowup' AND `glpi_documents_items`.`items_id` IN (SELECT `id` FROM `glpi_itilfollowups` WHERE `glpi_itilfollowups`.`itemtype` = 'Ticket' AND `glpi_itilfollowups`.`items_id` = '%1\$s' AND ((`is_private` = '0' OR `users_id` = '%2\$s'))))"
               . " OR (`glpi_documents_items`.`itemtype` = 'ITILSolution' AND `glpi_documents_items`.`items_id` IN (SELECT `id` FROM `glpi_itilsolutions` WHERE `glpi_itilsolutions`.`itemtype` = 'Ticket' AND `glpi_itilsolutions`.`items_id` = '%1\$s'))"
               . " OR (`glpi_documents_items`.`itemtype` = 'TicketValidation' AND `glpi_documents_items`.`items_id` IN (SELECT `id` FROM `glpi_ticketvalidations` WHERE `glpi_ticketvalidations`.`tickets_id` = '%1\$s'))"
               . " OR (`glpi_documents_items`.`itemtype` = 'TicketTask' AND `glpi_documents_items`.`items_id` IN (SELECT `id` FROM `glpi_tickettasks` WHERE `tickets_id` = '%1\$s'))",
               $ticket_id,
               getItemByTypeName('User', TU_USER, true)
            ),
         ],
      ];
   }

   /**
    * @dataProvider getAssociatedDocumentsCriteriaProvider
    */
   public function testGetAssociatedDocumentsCriteria($rights, $ticket_id, $bypass_rights, $expected_where) {
      $this->login();

      $ticket = new \Ticket();
      $this->boolean($ticket->getFromDB($ticket_id))->isTrue();

      $session_backup = $_SESSION['glpiactiveprofile'];
      foreach ($rights as $rightname => $rightvalue) {
         $_SESSION['glpiactiveprofile'][$rightname] = $rightvalue;
      }
      $crit = $ticket->getAssociatedDocumentsCriteria($bypass_rights);
      $_SESSION['glpiactiveprofile'] = $session_backup;

      $it = new \DBmysqlIterator(null);
      $it->execute('glpi_tickets', $crit);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `glpi_tickets` WHERE (' . $expected_where . ')');
   }

   public function testKeepScreenshotsOnFormReload() {
      //FIXME: temporary commented for other tests to work; must be fixed on modernui
      return true;

      //login to get session
      $auth = new \Auth();
      $this->boolean($auth->login(TU_USER, TU_PASS, true))->isTrue();

      $base64Image = base64_encode(file_get_contents(__DIR__ . '/../fixtures/uploads/foo.png'));

      // Test display of saved inputs from a previous submit
      $_SESSION['saveInput'][\Ticket::class] = [
         'content' => '&lt;p&gt; &lt;/p&gt;&lt;p&gt;&lt;img id="3e29dffe-0237ea21-5e5e7034b1d1a1.77230247"'
         . ' src="data:image/png;base64,' . $base64Image . '" width="12" height="12" /&gt;&lt;/p&gt;',
      ];

      $this->output(
         function () {
            $instance = new \Ticket();
            $instance->showForm('-1');
         }
      )->contains('src=&quot;data:image/png;base64,' . $base64Image . '&quot;');
   }

   public function testScreenshotConvertedIntoDocument() {

      $this->login(); // must be logged as Document_Item uses Session::getLoginUserID()

      // Test uploads for item creation
      $base64Image = base64_encode(file_get_contents(__DIR__ . '/../fixtures/uploads/foo.png'));
      $filename = '5e5e92ffd9bd91.11111111image_paste22222222.png';
      $instance = new \Ticket();
      $input = [
         'name'    => 'a ticket',
         'content' => '&lt;p&gt; &lt;/p&gt;&lt;p&gt;&lt;img id="3e29dffe-0237ea21-5e5e7034b1d1a1.00000000"'
         . ' src="data:image/png;base64,' . $base64Image . '" width="12" height="12" /&gt;&lt;/p&gt;',
         '_content' => [
            $filename,
         ],
         '_tag_content' => [
            '3e29dffe-0237ea21-5e5e7034b1d1a1.00000000',
         ],
         '_prefix_content' => [
            '5e5e92ffd9bd91.11111111',
         ]
      ];
      copy(__DIR__ . '/../fixtures/uploads/foo.png', GLPI_TMP_DIR . '/' . $filename);
      $instance->add($input);
      $expected = 'a href="/front/document.send.php?docid=';
      $this->string($instance->fields['content'])->contains($expected);

      // Test uploads for item update
      $base64Image = base64_encode(file_get_contents(__DIR__ . '/../fixtures/uploads/bar.png'));
      $filename = '5e5e92ffd9bd91.44444444image_paste55555555.png';
      copy(__DIR__ . '/../fixtures/uploads/bar.png', GLPI_TMP_DIR . '/' . $filename);
      $instance->update([
         'id' => $instance->getID(),
         'content' => '&lt;p&gt; &lt;/p&gt;&lt;p&gt;&lt;img id="3e29dffe-0237ea21-5e5e7034b1d1a1.33333333"'
         . ' src="data:image/png;base64,' . $base64Image . '" width="12" height="12" /&gt;&lt;/p&gt;',
         '_content' => [
            $filename,
         ],
         '_tag_content' => [
            '3e29dffe-0237ea21-5e5e7034b1d1a1.33333333',
         ],
         '_prefix_content' => [
            '5e5e92ffd9bd91.44444444',
         ]
      ]);
      $expected = 'a href="/front/document.send.php?docid=';
      $this->string($instance->fields['content'])->contains($expected);
   }

   public function testUploadDocuments() {

      $this->login(); // must be logged as Document_Item uses Session::getLoginUserID()

      // Test uploads for item creation
      $filename = '5e5e92ffd9bd91.11111111' . 'foo.txt';
      $instance = new \Ticket();
      $input = [
         'name'    => 'a ticket',
         'content' => 'testUploadDocuments',
         '_filename' => [
            $filename,
         ],
         '_tag_filename' => [
            '3e29dffe-0237ea21-5e5e7034b1ffff.00000000',
         ],
         '_prefix_filename' => [
            '5e5e92ffd9bd91.11111111',
         ]
      ];
      copy(__DIR__ . '/../fixtures/uploads/foo.txt', GLPI_TMP_DIR . '/' . $filename);
      $instance->add($input);
      $this->string($instance->fields['content'])->contains('testUploadDocuments');
      $count = (new \DBUtils())->countElementsInTable(\Document_Item::getTable(), [
         'itemtype' => 'Ticket',
         'items_id' => $instance->getID(),
      ]);
      $this->integer($count)->isEqualTo(1);

      // Test uploads for item update (adds a 2nd document)
      $filename = '5e5e92ffd9bd91.44444444bar.txt';
      copy(__DIR__ . '/../fixtures/uploads/bar.txt', GLPI_TMP_DIR . '/' . $filename);
      $instance->update([
         'id' => $instance->getID(),
         'content' => 'update testUploadDocuments',
         '_filename' => [
            $filename,
         ],
         '_tag_filename' => [
            '3e29dffe-0237ea21-5e5e7034b1d1a1.33333333',
         ],
         '_prefix_filename' => [
            '5e5e92ffd9bd91.44444444',
         ]
      ]);
      $this->string($instance->fields['content'])->contains('update testUploadDocuments');
      $count = (new \DBUtils())->countElementsInTable(\Document_Item::getTable(), [
         'itemtype' => 'Ticket',
         'items_id' => $instance->getID(),
      ]);
      $this->integer($count)->isEqualTo(2);
   }

   public function testKeepScreenshotFromTemplate() {
      //FIXME: temporary commented for other tests to work; must be fixed on modernui
      return true;

      //login to get session
      $auth = new \Auth();
      $this->boolean($auth->login(TU_USER, TU_PASS, true))->isTrue();

      // create a template with a predeined description
      $ticketTemplate = new \TicketTemplate();
      $ticketTemplate->add([
         'name' => $this->getUniqueString(),
      ]);
      $base64Image = base64_encode(file_get_contents(__DIR__ . '/../fixtures/uploads/foo.png'));
      $content = '&lt;p&gt;&lt;img id="3e29dffe-0237ea21-5e57d2c8895d55.57735524"'
      . ' src="data:image/png;base64,' . $base64Image . '" width="12" height="12" /&gt;&lt;/p&gt;';
      $predefinedField = new \TicketTemplatePredefinedField();
      $predefinedField->add([
         'tickettemplates_id' => $ticketTemplate->getID(),
         'num' => '21',
         'value' => $content
      ]);
      $session_tpl_id_back = $_SESSION['glpiactiveprofile']['tickettemplates_id'];
      $_SESSION['glpiactiveprofile']['tickettemplates_id'] = $ticketTemplate->getID();

      $this->output(
         function () use ($session_tpl_id_back) {
            $instance = new \Ticket();
            $instance->showForm('0');
            $_SESSION['glpiactiveprofile']['tickettemplates_id'] = $session_tpl_id_back;
         }
      )->contains('src=&quot;data:image/png;base64,' . $base64Image . '&quot;');
   }


   public function testCanDelegateeCreateTicket() {
      $normal_id   = getItemByTypeName('User', 'normal', true);
      $tech_id     = getItemByTypeName('User', 'tech', true);
      $postonly_id = getItemByTypeName('User', 'post-only', true);
      $tuser_id    = getItemByTypeName('User', TU_USER, true);

      // check base behavior (only standard interface can create for other users)
      $this->login();
      $this->boolean(\Ticket::canDelegateeCreateTicket($normal_id))->isTrue();
      $this->login('tech', 'tech');
      $this->boolean(\Ticket::canDelegateeCreateTicket($normal_id))->isTrue();
      $this->login('post-only', 'postonly');
      $this->boolean(\Ticket::canDelegateeCreateTicket($normal_id))->isFalse();

      // create a test group
      $group = new \Group;
      $groups_id = $group->add(['name' => 'test delegatee']);
      $this->integer($groups_id)->isGreaterThan(0);

      // make postonly delegate of the group
      $gu = new \Group_User;
      $this->integer($gu->add([
         'users_id'         => $postonly_id,
         'groups_id'        => $groups_id,
         'is_userdelegate' => 1,
      ]))->isGreaterThan(0);
      $this->integer($gu->add([
         'users_id'  => $normal_id,
         'groups_id' => $groups_id,
      ]))->isGreaterThan(0);

      // check postonly can now create (yes for normal and himself) or not (no for others) for other users
      $this->login('post-only', 'postonly');
      $this->boolean(\Ticket::canDelegateeCreateTicket($postonly_id))->isTrue();
      $this->boolean(\Ticket::canDelegateeCreateTicket($normal_id))->isTrue();
      $this->boolean(\Ticket::canDelegateeCreateTicket($tech_id))->isFalse();
      $this->boolean(\Ticket::canDelegateeCreateTicket($tuser_id))->isFalse();
   }

   public function testCanAddFollowupsDefaults() {
      $tech_id = getItemByTypeName('User', 'tech', true);
      $normal_id = getItemByTypeName('User', 'normal', true);
      $post_only_id = getItemByTypeName('User', 'post-only', true);

      $this->login();

      $ticket = new \Ticket();
      $this->integer(
         (int)$ticket->add([
            'name'    => '',
            'content' => 'A ticket to check ACLS',
         ])
      )->isGreaterThan(0);

      $this->boolean((boolean)$ticket->canUserAddFollowups($tech_id))->isTrue();
      $this->boolean((boolean)$ticket->canUserAddFollowups($normal_id))->isFalse();
      $this->boolean((boolean)$ticket->canUserAddFollowups($post_only_id))->isFalse();

      $this->login('tech', 'tech');
      $this->boolean((boolean)$ticket->canAddFollowups())->isTrue();
      $this->login('normal', 'normal');
      $this->boolean((boolean)$ticket->canAddFollowups())->isFalse();
      $this->login('post-only', 'postonly');
      $this->boolean((boolean)$ticket->canAddFollowups())->isFalse();
   }

   public function testCanAddFollowupsAsRecipient() {
      global $DB;

      $post_only_id = getItemByTypeName('User', 'post-only', true);

      $this->login();

      $ticket = new \Ticket();
      $this->integer(
         (int)$ticket->add([
            'name'               => '',
            'content'            => 'A ticket to check ACLS',
            'users_id_recipient' => $post_only_id,
            '_auto_import'       => false,
         ])
      )->isGreaterThan(0);

      // Drop all followup rights
      $DB->update(
         'glpi_profilerights',
         [
            'rights' => 0
         ],
         [
            'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
            'name'        => \ITILFollowup::$rightname,
         ]
      );

      // Cannot add followup as user do not have ADDMYTICKET right
      $this->login();
      $this->boolean((boolean)$ticket->canUserAddFollowups($post_only_id))->isFalse();
      $this->login('post-only', 'postonly');
      $this->boolean((boolean)$ticket->canAddFollowups())->isFalse();

      // Add user right
      $DB->update(
         'glpi_profilerights',
         [
            'rights' => \ITILFollowup::ADDMYTICKET
         ],
         [
            'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
            'name'        => \ITILFollowup::$rightname,
         ]
      );

      // User is recipient and have ADDMYTICKET, he should be able to add followup
      $this->login();
      $this->boolean((boolean)$ticket->canUserAddFollowups($post_only_id))->isTrue();
      $this->login('post-only', 'postonly');
      $this->boolean((boolean)$ticket->canAddFollowups())->isTrue();
   }

   public function testCanAddFollowupsAsRequester() {
      global $DB;

      $post_only_id = getItemByTypeName('User', 'post-only', true);

      $this->login();

      $ticket = new \Ticket();
      $this->integer(
         (int)$ticket->add([
            'name'    => '',
            'content' => 'A ticket to check ACLS',
         ])
      )->isGreaterThan(0);

      // Drop all followup rights
      $DB->update(
         'glpi_profilerights',
         [
            'rights' => 0
         ],
         [
            'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
            'name'        => \ITILFollowup::$rightname,
         ]
      );

      // Cannot add followups by default
      $this->login();
      $this->boolean((boolean)$ticket->canUserAddFollowups($post_only_id))->isFalse();
      $this->login('post-only', 'postonly');
      $this->boolean((boolean)$ticket->canAddFollowups())->isFalse();

      // Add user as requester
      $this->login();
      $ticket_user = new \Ticket_User();
      $input_ticket_user = [
         'tickets_id' => $ticket->getID(),
         'users_id'   => $post_only_id,
         'type'       => \CommonITILActor::REQUESTER
      ];
      $this->integer((int) $ticket_user->add($input_ticket_user))->isGreaterThan(0);
      $this->boolean($ticket->getFromDB($ticket->getID()))->isTrue(); // Reload ticket actors

      // Cannot add followup as user do not have ADDMYTICKET right
      $this->login();
      $this->boolean((boolean)$ticket->canUserAddFollowups($post_only_id))->isFalse();
      $this->login('post-only', 'postonly');
      $this->boolean((boolean)$ticket->canAddFollowups())->isFalse();

      // Add user right
      $DB->update(
         'glpi_profilerights',
         [
            'rights' => \ITILFollowup::ADDMYTICKET
         ],
         [
            'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
            'name'        => \ITILFollowup::$rightname,
         ]
      );

      // User is requester and have ADDMYTICKET, he should be able to add followup
      $this->login();
      $this->boolean((boolean)$ticket->canUserAddFollowups($post_only_id))->isTrue();
      $this->login('post-only', 'postonly');
      $this->boolean((boolean)$ticket->canAddFollowups())->isTrue();
   }

   public function testCanAddFollowupsAsRequesterGroup() {
      global $DB;

      $post_only_id = getItemByTypeName('User', 'post-only', true);

      $this->login();

      $ticket = new \Ticket();
      $this->integer(
         (int)$ticket->add([
            'name'    => '',
            'content' => 'A ticket to check ACLS',
         ])
      )->isGreaterThan(0);

      // Drop all followup rights
      $DB->update(
         'glpi_profilerights',
         [
            'rights' => 0
         ],
         [
            'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
            'name'        => \ITILFollowup::$rightname,
         ]
      );

      // Cannot add followups by default
      $this->login();
      $this->boolean((boolean)$ticket->canUserAddFollowups($post_only_id))->isFalse();
      $this->login('post-only', 'postonly');
      $this->boolean((boolean)$ticket->canAddFollowups())->isFalse();

      // Add user's group as requester
      $this->login();
      $group = new \Group();
      $group_id = $group->add(['name' => 'Test group']);
      $this->integer((int)$group_id)->isGreaterThan(0);
      $group_user = new \Group_User();
      $this->integer(
         (int)$group_user->add([
            'groups_id' => $group_id,
            'users_id'  => $post_only_id,
         ])
      )->isGreaterThan(0);

      $group_ticket = new \Group_Ticket();
      $input_group_ticket = [
         'tickets_id' => $ticket->getID(),
         'groups_id'  => $group_id,
         'type'       => \CommonITILActor::REQUESTER
      ];
      $this->integer((int) $group_ticket->add($input_group_ticket))->isGreaterThan(0);
      $this->boolean($ticket->getFromDB($ticket->getID()))->isTrue(); // Reload ticket actors

      // Cannot add followup as user do not have ADDGROUPTICKET right
      $this->login();
      $this->boolean((boolean)$ticket->canUserAddFollowups($post_only_id))->isFalse();
      $this->login('post-only', 'postonly');
      $this->boolean((boolean)$ticket->canAddFollowups())->isFalse();

      // Add user right
      $DB->update(
         'glpi_profilerights',
         [
            'rights' => \ITILFollowup::ADDGROUPTICKET
         ],
         [
            'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
            'name'        => \ITILFollowup::$rightname,
         ]
      );

      // User is requester and have ADDGROUPTICKET, he should be able to add followup
      $this->login();
      $this->boolean((boolean)$ticket->canUserAddFollowups($post_only_id))->isTrue();
      $this->login('post-only', 'postonly');
      $this->boolean((boolean)$ticket->canAddFollowups())->isTrue();
   }

   public function testCanAddFollowupsAsAssigned() {
      global $DB;

      $post_only_id = getItemByTypeName('User', 'post-only', true);

      $this->login();

      $ticket = new \Ticket();
      $this->integer(
         (int)$ticket->add([
            'name'    => '',
            'content' => 'A ticket to check ACLS',
         ])
      )->isGreaterThan(0);

      // Drop all followup rights
      $DB->update(
         'glpi_profilerights',
         [
            'rights' => 0
         ],
         [
            'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
            'name'        => \ITILFollowup::$rightname,
         ]
      );

      // Cannot add followups by default
      $this->login();
      $this->boolean((boolean)$ticket->canUserAddFollowups($post_only_id))->isFalse();
      $this->login('post-only', 'postonly');
      $this->boolean((boolean)$ticket->canAddFollowups())->isFalse();

      // Add user as requester
      $this->login();
      $ticket_user = new \Ticket_User();
      $input_ticket_user = [
         'tickets_id' => $ticket->getID(),
         'users_id'   => $post_only_id,
         'type'       => \CommonITILActor::ASSIGN
      ];
      $this->integer((int) $ticket_user->add($input_ticket_user))->isGreaterThan(0);
      $this->boolean($ticket->getFromDB($ticket->getID()))->isTrue(); // Reload ticket actors

      // Can add followup as user is assigned
      $this->login();
      $this->boolean((boolean)$ticket->canUserAddFollowups($post_only_id))->isTrue();
      $this->login('post-only', 'postonly');
      $this->boolean((boolean)$ticket->canAddFollowups())->isTrue();
   }

   public function testCanAddFollowupsAsAssignedGroup() {
      global $DB;

      $post_only_id = getItemByTypeName('User', 'post-only', true);

      $this->login();

      $ticket = new \Ticket();
      $this->integer(
         (int)$ticket->add([
            'name'    => '',
            'content' => 'A ticket to check ACLS',
         ])
      )->isGreaterThan(0);

      // Drop all followup rights
      $DB->update(
         'glpi_profilerights',
         [
            'rights' => 0
         ],
         [
            'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
            'name'        => \ITILFollowup::$rightname,
         ]
      );

      // Cannot add followups by default
      $this->login();
      $this->boolean((boolean)$ticket->canUserAddFollowups($post_only_id))->isFalse();
      $this->login('post-only', 'postonly');
      $this->boolean((boolean)$ticket->canAddFollowups())->isFalse();

      // Add user's group as requester
      $this->login();
      $group = new \Group();
      $group_id = $group->add(['name' => 'Test group']);
      $this->integer((int)$group_id)->isGreaterThan(0);
      $group_user = new \Group_User();
      $this->integer(
         (int)$group_user->add([
            'groups_id' => $group_id,
            'users_id'  => $post_only_id,
         ])
      )->isGreaterThan(0);

      $group_ticket = new \Group_Ticket();
      $input_group_ticket = [
         'tickets_id' => $ticket->getID(),
         'groups_id'  => $group_id,
         'type'       => \CommonITILActor::ASSIGN
      ];
      $this->integer((int) $group_ticket->add($input_group_ticket))->isGreaterThan(0);
      $this->boolean($ticket->getFromDB($ticket->getID()))->isTrue(); // Reload ticket actors

      // Can add followup as user is assigned
      $this->login();
      $this->boolean((boolean)$ticket->canUserAddFollowups($post_only_id))->isTrue();
      $this->login('post-only', 'postonly');
      $this->boolean((boolean)$ticket->canAddFollowups())->isTrue();
   }

   public function testCanAddFollowupsAsObserver() {
      global $DB;

      $post_only_id = getItemByTypeName('User', 'post-only', true);

      $this->login();

      $ticket = new \Ticket();
      $this->integer(
         (int)$ticket->add([
            'name'    => '',
            'content' => 'A ticket to check ACLS',
         ])
      )->isGreaterThan(0);

      // Cannot add followups by default
      $this->login();
      $this->boolean((boolean)$ticket->canUserAddFollowups($post_only_id))->isFalse();
      $this->login('post-only', 'postonly');
      $this->boolean((boolean)$ticket->canAddFollowups())->isFalse();

      // Add user as observer
      $this->login();
      $ticket_user = new \Ticket_User();
      $input_ticket_user = [
         'tickets_id' => $ticket->getID(),
         'users_id'   => $post_only_id,
         'type'       => \CommonITILActor::OBSERVER
      ];
      $this->integer((int) $ticket_user->add($input_ticket_user))->isGreaterThan(0);
      $this->boolean($ticket->getFromDB($ticket->getID()))->isTrue(); // Reload ticket actors

      // Cannot add followup as user do not have ADD_AS_FOLLOWUP right
      $this->login();
      $this->boolean((boolean)$ticket->canUserAddFollowups($post_only_id))->isFalse();
      $this->login('post-only', 'postonly');
      $this->boolean((boolean)$ticket->canAddFollowups())->isFalse();

      // Add user right
      $DB->update(
         'glpi_profilerights',
         [
            'rights' => \ITILFollowup::ADD_AS_OBSERVER
         ],
         [
            'profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
            'name'        => \ITILFollowup::$rightname,
         ]
      );

      // User is observer and have ADD_AS_OBSERVER, he should be able to add followup
      $this->login();
      $this->boolean((boolean)$ticket->canUserAddFollowups($post_only_id))->isTrue();
      $this->login('post-only', 'postonly');
      $this->boolean((boolean)$ticket->canAddFollowups())->isTrue();
   }
}
