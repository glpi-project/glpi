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

/* Test for inc/ticket.class.php */

class TicketTest extends DbTestCase {

   public function ticketProvider() {
      return array(
            'single requester'   => array(
                  array(
                     '_users_id_requester'   => '3'
                  ),
            ),
            'single unknown requester'   => array(
                  array(
                        '_users_id_requester'         => '0',
                        '_users_id_requester_notif'   => array(
                              'use_notification'            => array('1'),
                              'alternative_email'           => array('unknownuser@localhost.local')
                        ),
                  ),
            ),
           'multiple requesters'   => array(
                  array(
                        '_users_id_requester'   => array('3', '5'),
                  ),
            ),
            'multiple mixed requesters'   => array(
                  array(
                        '_users_id_requester'   => array('3', '5', '0'),
                        '_users_id_requester_notif'   => array(
                              'use_notification'            => array('1', '0', '1'),
                              'alternative_email'           => array('','', 'unknownuser@localhost.local')
                        ),
                  ),
            ),
            'single observer'   => array(
                  array(
                        '_users_id_observer'   => '3'
                  ),
            ),
            'multiple observers'   => array(
                  array(
                        '_users_id_observer'   => array('3', '5'),
                  ),
            ),
            'single assign'   => array(
                  array(
                        '_users_id_observer'   => '3'
                  ),
            ),
            'multiple assigns'   => array(
                  array(
                        '_users_id_observer'   => array('3', '5'),
                  ),
            ),
      );
   }

   /**
    * @dataProvider ticketProvider
    */
   public function testCreateTicketWithActors($ticketActors) {
      $ticket = new Ticket();
      $ticket->add(array(
            'name'         => 'ticket title',
            'description'  => 'a description'
      ) + $ticketActors);

      $this->assertFalse($ticket->isNewItem());
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
               case '_users_id_assign':
                  $this->_testTicketUser($ticket, $actor, CommonITILActor::REQUESTER, $notify, $alternateEmail);
                  break;
               case '_users_id_observer':
                  $this->_testTicketUser($ticket, $actor, CommonITILActor::OBSERVER, $notify, $alternateEmail);
                  break;
               case '_users_id_assign':
                  $this->_testTicketUser($ticket, $actor, CommonITILActor::ASSIGN, $notify, $alternateEmail);
                  break;
            }
         }
      }
   }

   public function testTicketSolution() {
      session_unset();
      $_SESSION['glpicronuserrunning'] = "cron_phpunit";
      $_SESSION['glpi_use_mode']       = Session::NORMAL_MODE;

      $uid = getItemByTypeName('User', TU_USER, true);
      $ticket = new Ticket();
      $ticket->add([
         'name'               => 'ticket title',
         'description'        => 'a description',
         '_users_id_assign'   => $uid
      ]);

      $this->assertFalse($ticket->isNewItem());
      $this->assertEquals($ticket::ASSIGNED, $ticket->getField('status'));
      $ticketId = $ticket->getID();

      $this->_testTicketUser(
         $ticket,
         $uid,
         CommonITILActor::ASSIGN,
         1,
         ''
      );

      $ticket->update([
         'id' => $ticket->getID(),
         'solution'  => 'Current friendly ticket\r\nis solved!'
      ]);
      //reload from DB
      $ticket->getFromDB($ticket->getID());

      $this->assertEquals($ticket::CLOSED, $ticket->getField('status'));
      $this->assertEquals("Current friendly ticket\r\nis solved!", $ticket->getField('solution'));
   }

   protected function _testTicketUser(Ticket $ticket, $actor, $role, $notify, $alternateEmail) {
      if ($actor > 0) {
         $user = new User();
         $user->getFromDB($actor);
         $this->assertFalse($user->isNewItem());

         $ticketUser = new Ticket_User();
         $ticketUser->getFromDBForItems($ticket, $user);
         $this->assertFalse($ticketUser->isNewItem());
         $this->assertEquals($role, $ticketUser->getField('type'));
         $this->assertEquals($notify, $ticketUser->getField('use_notification'));
      } else {
         $ticketId = $ticket->getID();
         $ticketUser = new Ticket_User();
         $ticketUser->getFromDBByQuery("WHERE `tickets_id` = '$ticketId' AND `users_id` = '0' AND `alternative_email` = '$alternateEmail'");
         $this->assertFalse($ticketUser->isNewItem());
         $this->assertEquals($role, $ticketUser->getField('type'));
         $this->assertEquals($notify, $ticketUser->getField('use_notification'));
      }
   }

   public function testTasksFromTemplate() {
      // 1- create a task category
      $taskcat    = new TaskCategory;
      $taskcat_id = $taskcat->add([
         'name' => 'my task cat',
      ]);
      $this->assertFalse($taskcat->isNewItem());

      // 2- create some task templates
      $tasktemplate = new TaskTemplate;
      $ttA_id          = $tasktemplate->add([
         'name'              => 'my task template A',
         'content'           => 'my task template A',
         'taskcategories_id' => $taskcat_id,
         'actiontime'        => 60,
         'is_private'        => true,
         'users_id_tech'     => 2,
         'groups_id_tech'    => 0,
         'state'             => Planning::INFO,
      ]);
      $this->assertFalse($tasktemplate->isNewItem());
      $ttB_id          = $tasktemplate->add([
         'name'              => 'my task template B',
         'content'           => 'my task template B',
         'taskcategories_id' => $taskcat_id,
         'actiontime'        => 120,
         'is_private'        => false,
         'users_id_tech'     => 2,
         'groups_id_tech'    => 0,
         'state'             => Planning::TODO,
      ]);
      $this->assertFalse($tasktemplate->isNewItem());

      // 3 - create a ticket template with the task templates in predefined fields
      $tickettemplate    = new TicketTemplate;
      $tickettemplate_id = $tickettemplate->add([
         'name' => 'my ticket template',
      ]);
      $this->assertFalse($tickettemplate->isNewItem());
      $ttp = new TicketTemplatePredefinedField();
      $ttp->add([
         'tickettemplates_id' => $tickettemplate_id,
         'num'                => '175',
         'value'              => $ttA_id,
      ]);
      $this->assertFalse($ttp->isNewItem());
      $ttp->add([
         'tickettemplates_id' => $tickettemplate_id,
         'num'                => '175',
         'value'              => $ttB_id,
      ]);
      $this->assertFalse($ttp->isNewItem());

      // 4 - create a ticket category using the ticket template
      $itilcat    = new ITILCategory;
      $itilcat_id = $itilcat->add([
         'name'                        => 'my itil category',
         'tickettemplates_id_incident' => $tickettemplate_id,
         'tickettemplates_id_demand'   => $tickettemplate_id,
         'is_incident'                 => true,
         'is_request'                  => true,
      ]);
      $this->assertFalse($itilcat->isNewItem());

      // 5 - create a ticket using the ticket category
      $ticket     = new Ticket;
      $tickets_id = $ticket->add([
         'name'                => 'test task template',
         'content'             => 'test task template',
         'itilcategories_id'   => $itilcat_id,
         '_tickettemplates_id' => $tickettemplate_id,
         '_tasktemplates_id'   => [$ttA_id, $ttB_id],
      ]);
      $this->assertFalse($ticket->isNewItem());

      // 6 - check creation of the tasks
      $tickettask = new TicketTask;
      $found_tasks = $tickettask->find("`tickets_id` = $tickets_id", "id ASC");

      // 6.1 -> check first task
      $taskA = array_shift($found_tasks);
      $this->assertEquals('my task template A', $taskA['content']);
      $this->assertEquals($taskcat_id, $taskA['taskcategories_id']);
      $this->assertEquals(60, $taskA['actiontime']);
      $this->assertEquals(1, $taskA['is_private']);
      $this->assertEquals(2, $taskA['users_id_tech']);
      $this->assertEquals(0, $taskA['groups_id_tech']);
      $this->assertEquals(Planning::INFO, $taskA['state']);

      // 6.2 -> check second task
      $taskB = array_shift($found_tasks);
      $this->assertEquals('my task template B', $taskB['content']);
      $this->assertEquals($taskcat_id, $taskB['taskcategories_id']);
      $this->assertEquals(120, $taskB['actiontime']);
      $this->assertEquals(0, $taskB['is_private']);
      $this->assertEquals(2, $taskB['users_id_tech']);
      $this->assertEquals(0, $taskB['groups_id_tech']);
      $this->assertEquals(Planning::TODO, $taskB['state']);
   }
}


