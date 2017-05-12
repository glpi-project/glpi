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

}
