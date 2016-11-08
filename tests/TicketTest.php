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

class TicketTest extends PHPUnit_Framework_TestCase {

   public function ticketProvider() {
      return array(
            'single requester'   => array(
                  array(
                     '_users_id_requester'   => '3'
                  ),
            ),
            'multiple requesters'   => array(
                  array(
                        '_users_id_requester'   => array('3', '5'),
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
         foreach ($actorsList as $actor) {
            switch ($actorType) {
               case '_users_id_assign':
                  $this->_testTicketUser($ticket, $actor, CommonITILActor::REQUESTER);
                  break;
               case '_users_id_observer':
                  $this->_testTicketUser($ticket, $actor, CommonITILActor::OBSERVER);
                  break;
               case '_users_id_assign':
                  $this->_testTicketUser($ticket, $actor, CommonITILActor::ASSIGN);
                  break;
            }
         }
      }
   }

   protected function _testTicketUser(Ticket $ticket, $actor, $role) {
      $user = new User();
      $user->getFromDB($actor);
      $this->assertFalse($user->isNewItem());

      $ticketUser = new Ticket_User();
      $ticketUser->getFromDBForItems($ticket, $user);
      $this->assertFalse($ticketUser->isNewItem());
      $this->assertEquals($role, $ticketUser->getField('type'));
   }

}