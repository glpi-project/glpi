<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
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

      $this->assertFalse((boolean)$ticket->isNewItem());
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

      $this->assertFalse((boolean)$ticket->isNewItem());
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
         $this->assertFalse((boolean)$user->isNewItem());

         $ticketUser = new Ticket_User();
         $ticketUser->getFromDBForItems($ticket, $user);
         $this->assertFalse((boolean)$ticketUser->isNewItem());
         $this->assertEquals($role, $ticketUser->getField('type'));
         $this->assertEquals($notify, $ticketUser->getField('use_notification'));
      } else {
         $ticketId = $ticket->getID();
         $ticketUser = new Ticket_User();
         $ticketUser->getFromDBByQuery("WHERE `tickets_id` = '$ticketId' AND `users_id` = '0' AND `alternative_email` = '$alternateEmail'");
         $this->assertFalse((boolean)$ticketUser->isNewItem());
         $this->assertEquals($role, $ticketUser->getField('type'));
         $this->assertEquals($notify, $ticketUser->getField('use_notification'));
      }
   }

   public function testAcls() {
      $ticket = new \Ticket();
      $this->assertFalse((boolean)$ticket->canAdminActors());
      $this->assertFalse((boolean)$ticket->canAssign());
      $this->assertFalse((boolean)$ticket->canAssignToMe());
      $this->assertFalse((boolean)$ticket->canUpdate());
      $this->assertFalse((boolean)$ticket->canView());
      $this->assertFalse((boolean)$ticket->canViewItem());
      $this->assertFalse((boolean)$ticket->canSolve());
      $this->assertFalse((boolean)$ticket->canApprove());
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'));
      $this->assertFalse((boolean)$ticket->canCreateItem());
      $this->assertFalse((boolean)$ticket->canUpdateItem());
      $this->assertFalse((boolean)$ticket->canRequesterUpdateItem());
      $this->assertFalse((boolean)$ticket->canDelete());
      $this->assertFalse((boolean)$ticket->canDeleteItem());
      $this->assertFalse((boolean)$ticket->canAddItem('Document'));
      $this->assertFalse((boolean)$ticket->canAddItem('Ticket_Cost'));
      $this->assertFalse((boolean)$ticket->canAddFollowups());

      $this->login();
      $this->setEntity('Root entity', false);
      $ticket = new \Ticket();
      $this->assertTrue((boolean)$ticket->canAdminActors()); //=> get 2
      $this->assertTrue((boolean)$ticket->canAssign()); //=> get 8192
      $this->assertTrue((boolean)$ticket->canAssignToMe());
      $this->assertTrue((boolean)$ticket->canUpdate());
      $this->assertTrue((boolean)$ticket->canView());
      $this->assertTrue((boolean)$ticket->canViewItem());
      $this->assertTrue((boolean)$ticket->canSolve());
      $this->assertFalse((boolean)$ticket->canApprove());
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canCreateItem());
      $this->assertTrue((boolean)$ticket->canUpdateItem());
      $this->assertFalse((boolean)$ticket->canRequesterUpdateItem());
      $this->assertTrue((boolean)$ticket->canDelete());
      $this->assertTrue((boolean)$ticket->canDeleteItem());
      $this->assertTrue((boolean)$ticket->canAddItem('Document'));
      $this->assertTrue((boolean)$ticket->canAddItem('Ticket_Cost'));
      $this->assertTrue((boolean)$ticket->canAddFollowups());

      $ticket = getItemByTypeName('Ticket', '_ticket01');
      $this->assertTrue((boolean)$ticket->canAdminActors()); //=> get 2
      $this->assertTrue((boolean)$ticket->canAssign()); //=> get 8192
      $this->assertTrue((boolean)$ticket->canAssignToMe());
      $this->assertTrue((boolean)$ticket->canUpdate());
      $this->assertTrue((boolean)$ticket->canView());
      $this->assertTrue((boolean)$ticket->canViewItem());
      $this->assertTrue((boolean)$ticket->canSolve());
      $this->assertTrue((boolean)$ticket->canApprove());
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canCreateItem());
      $this->assertTrue((boolean)$ticket->canUpdateItem());
      $this->assertFalse((boolean)$ticket->canRequesterUpdateItem());
      $this->assertTrue((boolean)$ticket->canDelete());
      $this->assertTrue((boolean)$ticket->canDeleteItem());
      $this->assertTrue((boolean)$ticket->canAddItem('Document'));
      $this->assertTrue((boolean)$ticket->canAddItem('Ticket_Cost'));
      $this->assertTrue((boolean)$ticket->canAddFollowups());
   }

   public function testPostOnlyAcls() {
      $auth = new \Auth();
      $this->assertTrue((boolean)$auth->Login('post-only', 'postonly', true));

      $ticket = new \Ticket();
      $this->assertFalse((boolean)$ticket->canAdminActors());
      $this->assertFalse((boolean)$ticket->canAssign());
      $this->assertFalse((boolean)$ticket->canAssignToMe());
      $this->assertTrue((boolean)$ticket->canUpdate());
      $this->assertTrue((boolean)$ticket->canView());
      $this->assertFalse((boolean)$ticket->canViewItem());
      $this->assertFalse((boolean)$ticket->canSolve());
      $this->assertFalse((boolean)$ticket->canApprove());
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canCreateItem());
      $this->assertFalse((boolean)$ticket->canUpdateItem());
      $this->assertFalse((boolean)$ticket->canRequesterUpdateItem());
      $this->assertTrue((boolean)$ticket->canDelete());
      $this->assertFalse((boolean)$ticket->canDeleteItem());
      $this->assertFalse((boolean)$ticket->canAddItem('Document'));
      $this->assertFalse((boolean)$ticket->canAddItem('Ticket_Cost'));
      $this->assertFalse((boolean)$ticket->canAddFollowups());

      $this->assertGreaterThan(
         0,
         $ticket->add([
            'description'  => 'A ticket to check ACLS',
            'content'      => ''
         ])
      );

      //reload ticket from DB
      $this->assertTrue((boolean)$ticket->getFromDB($ticket->getID()));
      $this->assertFalse((boolean)$ticket->canAdminActors());
      $this->assertFalse((boolean)$ticket->canAssign());
      $this->assertFalse((boolean)$ticket->canAssignToMe());
      $this->assertTrue((boolean)$ticket->canUpdate());
      $this->assertTrue((boolean)$ticket->canView());
      $this->assertTrue((boolean)$ticket->canViewItem());
      $this->assertFalse((boolean)$ticket->canSolve());
      $this->assertTrue((boolean)$ticket->canApprove());
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canCreateItem());
      $this->assertTrue((boolean)$ticket->canUpdateItem());
      $this->assertTrue((boolean)$ticket->canRequesterUpdateItem());
      $this->assertTrue((boolean)$ticket->canDelete());
      $this->assertTrue((boolean)$ticket->canDeleteItem());
      $this->assertTrue((boolean)$ticket->canAddItem('Document'));
      $this->assertTrue((boolean)$ticket->canAddItem('Ticket_Cost'));
      $this->assertTrue((boolean)$ticket->canAddFollowups());

      $uid = getItemByTypeName('User', TU_USER, true);
      //add a followup to the ticket
      $fup = new \TicketFollowup();
      $this->assertGreaterThan(
         0,
         $fup->add([
            'tickets_id'   => $ticket->getID(),
            'users_id'     => $uid,
            'content'      => 'A simple followup'
         ])
      );

      $this->assertTrue((boolean)$ticket->getFromDB($ticket->getID()));
      $this->assertFalse((boolean)$ticket->canAdminActors());
      $this->assertFalse((boolean)$ticket->canAssign());
      $this->assertFalse((boolean)$ticket->canAssignToMe());
      $this->assertTrue((boolean)$ticket->canUpdate());
      $this->assertTrue((boolean)$ticket->canView());
      $this->assertTrue((boolean)$ticket->canViewItem());
      $this->assertFalse((boolean)$ticket->canSolve());
      $this->assertTrue((boolean)$ticket->canApprove());
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canCreateItem());
      $this->assertFalse((boolean)$ticket->canUpdateItem());
      $this->assertFalse((boolean)$ticket->canRequesterUpdateItem());
      $this->assertTrue((boolean)$ticket->canDelete());
      $this->assertFalse((boolean)$ticket->canDeleteItem());
      $this->assertTrue((boolean)$ticket->canAddItem('Document'));
      $this->assertFalse((boolean)$ticket->canAddItem('Ticket_Cost'));
      $this->assertTrue((boolean)$ticket->canAddFollowups());
   }

   public function testTechAcls() {
      $auth = new \Auth();
      $this->assertTrue((boolean)$auth->Login('tech', 'tech', true));

      $ticket = new \Ticket();
      $this->assertTrue((boolean)$ticket->canAdminActors());
      $this->assertFalse((boolean)$ticket->canAssign());
      $this->assertTrue((boolean)$ticket->canAssignToMe());
      $this->assertTrue((boolean)$ticket->canUpdate());
      $this->assertTrue((boolean)$ticket->canView());
      $this->assertTrue((boolean)$ticket->canViewItem());
      $this->assertTrue((boolean)$ticket->canSolve());
      $this->assertFalse((boolean)$ticket->canApprove());
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canCreateItem());
      $this->assertTrue((boolean)$ticket->canUpdateItem());
      $this->assertFalse((boolean)$ticket->canRequesterUpdateItem());
      $this->assertFalse((boolean)$ticket->canDelete());
      $this->assertFalse((boolean)$ticket->canDeleteItem());
      $this->assertTrue((boolean)$ticket->canAddItem('Document'));
      $this->assertTrue((boolean)$ticket->canAddItem('Ticket_Cost'));
      $this->assertTrue((boolean)$ticket->canAddFollowups());

      $this->assertGreaterThan(
         0,
         $ticket->add([
            'description'  => 'A ticket to check ACLS',
            'content'      => ''
         ])
      );

      //reload ticket from DB
      $this->assertTrue((boolean)$ticket->getFromDB($ticket->getID()));
      $this->assertTrue((boolean)$ticket->canAdminActors());
      $this->assertFalse((boolean)$ticket->canAssign());
      $this->assertFalse((boolean)$ticket->canAssignToMe());
      $this->assertTrue((boolean)$ticket->canUpdate());
      $this->assertTrue((boolean)$ticket->canView());
      $this->assertTrue((boolean)$ticket->canViewItem());
      $this->assertTrue((boolean)$ticket->canSolve());
      $this->assertTrue((boolean)$ticket->canApprove());
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canCreateItem());
      $this->assertTrue((boolean)$ticket->canUpdateItem());
      $this->assertTrue((boolean)$ticket->canRequesterUpdateItem());
      $this->assertFalse((boolean)$ticket->canDelete());
      $this->assertFalse((boolean)$ticket->canDeleteItem());
      $this->assertTrue((boolean)$ticket->canAddItem('Document'));
      $this->assertTrue((boolean)$ticket->canAddItem('Ticket_Cost'));
      $this->assertTrue((boolean)$ticket->canAddFollowups());

      $uid = getItemByTypeName('User', TU_USER, true);
      //add a followup to the ticket
      $fup = new \TicketFollowup();
      $this->assertGreaterThan(
         0,
         $fup->add([
            'tickets_id'   => $ticket->getID(),
            'users_id'     => $uid,
            'content'      => 'A simple followup'
         ])
      );

      $this->assertTrue((boolean)$ticket->getFromDB($ticket->getID()));
      $this->assertTrue((boolean)$ticket->canAdminActors());
      $this->assertFalse((boolean)$ticket->canAssign());
      $this->assertFalse((boolean)$ticket->canAssignToMe());
      $this->assertTrue((boolean)$ticket->canUpdate());
      $this->assertTrue((boolean)$ticket->canView());
      $this->assertTrue((boolean)$ticket->canViewItem());
      $this->assertTrue((boolean)$ticket->canSolve());
      $this->assertTrue((boolean)$ticket->canApprove());
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canCreateItem());
      $this->assertTrue((boolean)$ticket->canUpdateItem());
      $this->assertFalse((boolean)$ticket->canRequesterUpdateItem());
      $this->assertFalse((boolean)$ticket->canDelete());
      $this->assertFalse((boolean)$ticket->canDeleteItem());
      $this->assertTrue((boolean)$ticket->canAddItem('Document'));
      $this->assertTrue((boolean)$ticket->canAddItem('Ticket_Cost'));
      $this->assertTrue((boolean)$ticket->canAddFollowups());

      //drop update ticket right from tech profile
      global $DB;
      $query = "UPDATE glpi_profilerights SET rights = 168965 WHERE profiles_id = 6 AND name = 'ticket'";
      $DB->query($query);
      //ACLs have changed: login again.
      $this->assertTrue((boolean)$auth->Login('tech', 'tech', true));

      //reset rights. Done here so ACLs are reset even if tests fails.
      $query = "UPDATE glpi_profilerights SET rights = 168967 WHERE profiles_id = 6 AND name = 'ticket'";
      $DB->query($query);

      $this->assertTrue((boolean)$ticket->getFromDB($ticket->getID()));
      $this->assertFalse((boolean)$ticket->canAdminActors());
      $this->assertFalse((boolean)$ticket->canAssign());
      $this->assertFalse((boolean)$ticket->canAssignToMe());
      $this->assertTrue((boolean)$ticket->canUpdate());
      $this->assertTrue((boolean)$ticket->canView());
      $this->assertTrue((boolean)$ticket->canViewItem());
      $this->assertTrue((boolean)$ticket->canSolve());
      $this->assertTrue((boolean)$ticket->canApprove());
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canCreateItem());
      $this->assertTrue((boolean)$ticket->canUpdateItem());
      $this->assertFalse((boolean)$ticket->canRequesterUpdateItem());
      $this->assertFalse((boolean)$ticket->canDelete());
      $this->assertFalse((boolean)$ticket->canDeleteItem());
      $this->assertTrue((boolean)$ticket->canAddItem('Document'));
      $this->assertFalse((boolean)$ticket->canAddItem('Ticket_Cost'));
      $this->assertTrue((boolean)$ticket->canAddFollowups());

      $this->assertGreaterThan(
         0,
         $ticket->add([
            'description'  => 'Another ticket to check ACLS',
            'content'      => ''
         ])
      );
      $this->assertTrue((boolean)$ticket->getFromDB($ticket->getID()));
      $this->assertFalse((boolean)$ticket->canAdminActors());
      $this->assertFalse((boolean)$ticket->canAssign());
      $this->assertFalse((boolean)$ticket->canAssignToMe());
      $this->assertTrue((boolean)$ticket->canUpdate());
      $this->assertTrue((boolean)$ticket->canView());
      $this->assertTrue((boolean)$ticket->canViewItem());
      $this->assertTrue((boolean)$ticket->canSolve());
      $this->assertTrue((boolean)$ticket->canApprove());
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canCreateItem());
      $this->assertTrue((boolean)$ticket->canUpdateItem());
      $this->assertTrue((boolean)$ticket->canRequesterUpdateItem());
      $this->assertFalse((boolean)$ticket->canDelete());
      $this->assertFalse((boolean)$ticket->canDeleteItem());
      $this->assertTrue((boolean)$ticket->canAddItem('Document'));
      $this->assertTrue((boolean)$ticket->canAddItem('Ticket_Cost'));
      $this->assertTrue((boolean)$ticket->canAddFollowups());
   }

   public function testNotOwnerAcls() {
      $this->login();

      $ticket = new \Ticket();
      $this->assertGreaterThan(
         0,
         $ticket->add([
            'description'  => 'A ticket to check ACLS',
            'content'      => ''
         ])
      );

      $auth = new \Auth();
      $this->assertTrue((boolean)$auth->Login('tech', 'tech', true));

      //reload ticket from DB
      $this->assertTrue((boolean)$ticket->getFromDB($ticket->getID()));
      $this->assertTrue((boolean)$ticket->canAdminActors());
      $this->assertFalse((boolean)$ticket->canAssign());
      $this->assertFalse((boolean)$ticket->canAssignToMe());
      $this->assertTrue((boolean)$ticket->canUpdate());
      $this->assertTrue((boolean)$ticket->canView());
      $this->assertTrue((boolean)$ticket->canViewItem());
      $this->assertTrue((boolean)$ticket->canSolve());
      $this->assertFalse((boolean)$ticket->canApprove());
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canCreateItem());
      $this->assertTrue((boolean)$ticket->canUpdateItem());
      $this->assertFalse((boolean)$ticket->canRequesterUpdateItem());
      $this->assertFalse((boolean)$ticket->canDelete());
      $this->assertFalse((boolean)$ticket->canDeleteItem());
      $this->assertTrue((boolean)$ticket->canAddItem('Document'));
      $this->assertTrue((boolean)$ticket->canAddItem('Ticket_Cost'));
      $this->assertTrue((boolean)$ticket->canAddFollowups());

      //drop update ticket right from tech profile
      global $DB;
      $query = "UPDATE glpi_profilerights SET rights = 168965 WHERE profiles_id = 6 AND name = 'ticket'";
      $DB->query($query);
      //ACLs have changed: login again.
      $this->assertTrue((boolean)$auth->Login('tech', 'tech', true));

      //reset rights. Done here so ACLs are reset even if tests fails.
      $query = "UPDATE glpi_profilerights SET rights = 168967 WHERE profiles_id = 6 AND name = 'ticket'";
      $DB->query($query);

      $this->assertTrue((boolean)$ticket->getFromDB($ticket->getID()));
      $this->assertFalse((boolean)$ticket->canAdminActors());
      $this->assertFalse((boolean)$ticket->canAssign());
      $this->assertFalse((boolean)$ticket->canAssignToMe());
      $this->assertTrue((boolean)$ticket->canUpdate());
      $this->assertTrue((boolean)$ticket->canView());
      $this->assertTrue((boolean)$ticket->canViewItem());
      $this->assertFalse((boolean)$ticket->canSolve());
      $this->assertFalse((boolean)$ticket->canApprove());
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canCreateItem());
      $this->assertTrue((boolean)$ticket->canUpdateItem());
      $this->assertFalse((boolean)$ticket->canRequesterUpdateItem());
      $this->assertFalse((boolean)$ticket->canDelete());
      $this->assertFalse((boolean)$ticket->canDeleteItem());
      $this->assertTrue((boolean)$ticket->canAddItem('Document'));
      $this->assertFalse((boolean)$ticket->canAddItem('Ticket_Cost'));
      $this->assertTrue((boolean)$ticket->canAddFollowups());

      $this->assertTrue((boolean)$auth->Login('post-only', 'postonly', true));
      $this->assertTrue((boolean)$ticket->getFromDB($ticket->getID()));
      $this->assertFalse((boolean)$ticket->canAdminActors());
      $this->assertFalse((boolean)$ticket->canAssign());
      $this->assertFalse((boolean)$ticket->canAssignToMe());
      $this->assertTrue((boolean)$ticket->canUpdate());
      $this->assertTrue((boolean)$ticket->canView());
      $this->assertFalse((boolean)$ticket->canViewItem());
      $this->assertFalse((boolean)$ticket->canSolve());
      $this->assertFalse((boolean)$ticket->canApprove());
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'content', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'name', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'priority', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'type', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canMassiveAction('update', 'location', 'qwerty'));
      $this->assertTrue((boolean)$ticket->canCreateItem());
      $this->assertFalse((boolean)$ticket->canUpdateItem());
      $this->assertFalse((boolean)$ticket->canRequesterUpdateItem());
      $this->assertTrue((boolean)$ticket->canDelete());
      $this->assertFalse((boolean)$ticket->canDeleteItem());
      $this->assertFalse((boolean)$ticket->canAddItem('Document'));
      $this->assertFalse((boolean)$ticket->canAddItem('Ticket_Cost'));
      $this->assertFalse((boolean)$ticket->canAddFollowups());
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
      $this->assertEquals(1, count($matches));

      //Ticket name, editable
      preg_match(
         '/.*<input[^>]*name=\'name\'  value="_ticket01">.*/',
         $output,
         $matches
      );
      $this->assertEquals(($name === true ? 1 : 0), count($matches));

      //Ticket content, editable
      preg_match(
         '/.*<textarea[^>]*name=\'content\'[^>]*>.*/',
         $output,
         $matches
      );
      $this->assertEquals(($textarea === true ? 1 : 0), count($matches));

      //Priority, editable
      preg_match(
         '/.*<select name=\'priority\'[^>]*>.*/',
         $output,
         $matches
      );
      $this->assertEquals(($priority === true ? 1 : 0), count($matches));

      //Save button
      preg_match(
         '/.*<input[^>]type=\'submit\'[^>]*>.*/',
         $output,
         $matches
      );
      $this->assertEquals(($save === true ? 1 : 0), count($matches));

      //Assign to
      preg_match(
         '/.*<select name=\'_itil_assign\[_type\]\'[^>]*>.*/',
         $output,
         $matches
      );
      $this->assertEquals(($assign === true ? 1 : 0), count($matches));
   }

   public function testForm() {
      $this->login();
      $this->setEntity('Root entity', false);
      $ticket = getItemByTypeName('Ticket', '_ticket01');

      $this->checkFormOutput($ticket);
   }

   public function testFormPostOnly() {
      $auth = new Auth();
      $this->assertTrue((boolean)$auth->Login('post-only', 'postonly', true));

      //create a new ticket
      $ticket = new \Ticket();
      $this->assertGreaterThan(
         0,
         $ticket->add([
            'description'  => 'A ticket to check displayed postonly form',
            'content'      => ''
         ])
      );

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
      $this->assertGreaterThan(
         0,
         $fup->add([
            'tickets_id'   => $ticket->getID(),
            'users_id'     => $uid,
            'content'      => 'A simple followup'
         ])
      );

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
      $auth = new Auth();
      $this->assertTrue((boolean)$auth->Login('tech', 'tech', true));

      //create a new ticket
      $ticket = new \Ticket();
      $this->assertGreaterThan(
         0,
         $ticket->add([
            'description'  => 'A ticket to check displayed tech form',
            'content'      => ''
         ])
      );

      //check output with default ACLs
      $this->checkFormOutput(
         $ticket,
         $name = false,
         $textarea = true,
         $priority = false,
         $save = true,
         $assign = true
      );

      //drop update ticket right from tech profile
      global $DB;
      $query = "UPDATE glpi_profilerights SET rights = 168965 WHERE profiles_id = 6 AND name = 'ticket'";
      $DB->query($query);
      //ACLs have changed: login again.
      $this->assertTrue((boolean)$auth->Login('tech', 'tech', true));

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
         $assign = true
      );

      $uid = getItemByTypeName('User', TU_USER, true);
      //add a followup to the ticket
      $fup = new \TicketFollowup();
      $this->assertGreaterThan(
         0,
         $fup->add([
            'tickets_id'   => $ticket->getID(),
            'users_id'     => $uid,
            'content'      => 'A simple followup'
         ])
      );

      //check output with changed ACLs when a followup has been added
      $this->checkFormOutput(
         $ticket,
         $name = false,
         $textarea = false,
         $priority = false,
         $save = false,
         $assign = true
      );
   }

   public function testPriorityAcl() {
      $this->login();

      $ticket = new \Ticket();
      $this->assertGreaterThan(
         0,
         $ticket->add([
            'description'  => 'A ticket to check priority ACLS',
            'content'      => ''
         ])
      );

      $auth = new \Auth();
      $this->assertTrue((boolean)$auth->Login('tech', 'tech', true));
      $this->assertTrue((boolean)$ticket->getFromDB($ticket->getID()));

      $this->assertFalse((boolean)\Session::haveRight(\Ticket::$rightname, \Ticket::CHANGEPRIORITY));
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
      $this->assertTrue((boolean)$auth->Login('tech', 'tech', true));

      //reset rights. Done here so ACLs are reset even if tests fails.
      $query = "UPDATE glpi_profilerights SET rights = 168967 WHERE profiles_id = 6 AND name = 'ticket'";
      $DB->query($query);

      $this->assertTrue((boolean)\Session::haveRight(\Ticket::$rightname, \Ticket::CHANGEPRIORITY));
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
      $this->assertGreaterThan(
         0,
         $ticket->add([
            'description'  => 'A ticket to check assign ACLS',
            'content'      => ''
         ])
      );

      $auth = new \Auth();
      $this->assertTrue((boolean)$auth->Login('tech', 'tech', true));
      $this->assertTrue((boolean)$ticket->getFromDB($ticket->getID()));

      $this->assertFalse((boolean)$ticket->canAssign());
      $this->assertFalse((boolean)$ticket->canAssignToMe());
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
      $this->assertTrue((boolean)$auth->Login('tech', 'tech', true));

      //reset rights. Done here so ACLs are reset even if tests fails.
      $query = "UPDATE glpi_profilerights SET rights = 168967 WHERE profiles_id = 6 AND name = 'ticket'";
      $DB->query($query);

      $this->assertFalse((boolean)$ticket->canAssign());
      $this->assertFalse((boolean)$ticket->canAssignToMe());
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
      $this->assertTrue((boolean)$auth->Login('tech', 'tech', true));

      //reset rights. Done here so ACLs are reset even if tests fails.
      $query = "UPDATE glpi_profilerights SET rights = 168967 WHERE profiles_id = 6 AND name = 'ticket'";
      $DB->query($query);

      $this->assertTrue((boolean)$ticket->canAssign());
      $this->assertFalse((boolean)$ticket->canAssignToMe());
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
}
