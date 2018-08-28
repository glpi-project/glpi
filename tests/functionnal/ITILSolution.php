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

/* Test for inc/itilsolution.class.php */

class ITILSolution extends DbTestCase {

   public function testTicketSolution() {
      $this->login();

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

      $solution = new \ITILSolution();
      $this->integer(
         (int)$solution->add([
            'itemtype'  => $ticket::getType(),
            'items_id'  => $ticket->getID(),
            'content'   => 'Current friendly ticket\r\nis solved!'
         ])
      );
      //reload from DB
      $this->boolean($ticket->getFromDB($ticket->getID()))->isTrue();

      $this->variable($ticket->getField('status'))->isEqualTo($ticket::SOLVED);
      $this->string($solution->getField('content'))->isIdenticalTo('Current friendly ticket\r\nis solved!');

      $this->boolean($solution->getFromDB($solution->getID()))->isTrue();
      $this->integer((int)$solution->fields['status'])->isIdenticalTo(\CommonITILValidation::WAITING);

      //approve solution
      $follow = new \ITILFollowup();
      $this->integer(
         (int)$follow->add([
            'itemtype'  => $ticket::getType(),
            'items_id'   => $ticket->getID(),
            'add_close'    => '1'
         ])
      )->isGreaterThan(0);
      $this->boolean($follow->getFromDB($follow->getID()))->isTrue();
      $this->boolean($solution->getFromDB($solution->getID()))->isTrue();
      $this->boolean($ticket->getFromDB($ticket->getID()))->isTrue();
      $this->integer((int)$solution->fields['status'])->isIdenticalTo(\CommonITILValidation::ACCEPTED);
      $this->integer((int)$ticket->fields['status'])->isIdenticalTo($ticket::CLOSED);

      //reopen ticket
      $this->integer(
         (int)$follow->add([
            'itemtype'  => $ticket::getType(),
            'items_id'  => $ticket->getID(),
            'add_reopen'   => '1',
            'content'      => 'This is required'
         ])
      )->isGreaterThan(0);
      $this->boolean($ticket->getFromDB($ticket->getID()))->isTrue();
      $this->boolean($solution->getFromDB($solution->getID()))->isTrue();

      $this->integer((int)$ticket->fields['status'])->isIdenticalTo($ticket::ASSIGNED);
      $this->integer((int)$solution->fields['status'])->isIdenticalTo(\CommonITILValidation::REFUSED);

      $this->integer(
         (int)$solution->add([
            'itemtype'  => $ticket::getType(),
            'items_id'  => $ticket->getID(),
            'content'   => 'Another solution proposed!'
         ])
      );
      //reload from DB
      $this->boolean($ticket->getFromDB($ticket->getID()))->isTrue();
      $this->boolean($solution->getFromDB($solution->getID()))->isTrue();

      $this->variable($ticket->getField('status'))->isEqualTo($ticket::SOLVED);
      $this->integer((int)$solution->fields['status'])->isIdenticalTo(\CommonITILValidation::WAITING);

      //refuse
      $follow = new \ITILFollowup();
      $this->integer(
         (int)$follow->add([
            'itemtype'   => 'Ticket',
            'items_id'   => $ticket->getID(),
            'add_reopen'   => '1',
            'content'      => 'This is required'
         ])
      )->isGreaterThan(0);

      //reload from DB
      $this->boolean($ticket->getFromDB($ticket->getID()))->isTrue();
      $this->boolean($solution->getFromDB($solution->getID()))->isTrue();

      $this->integer((int)$ticket->fields['status'])->isIdenticalTo($ticket::ASSIGNED);
      $this->integer((int)$solution->fields['status'])->isIdenticalTo(\CommonITILValidation::REFUSED);

      $this->integer(
         $solution::countFor(
            'Ticket',
            $ticket->getID()
         )
      )->isIdenticalTo(2);
   }

   public function testSolutionOnDuplicate() {
      $this->login();
      $this->setEntity('Root entity', true);

      $uid = getItemByTypeName('User', TU_USER, true);
      $ticket = new \Ticket();
      $duplicated = (int)$ticket->add([
         'name'               => 'Duplicated ticket',
         'description'        => 'A ticket that will be duplicated',
         'content'            => '',
         '_users_id_assign'   => $uid
      ]);
      $this->integer($duplicated)->isGreaterThan(0);

      $duplicate = (int)$ticket->add([
         'name'               => 'Duplicate ticket',
         'description'        => 'A ticket that is a duplicate',
         'content'            => '',
         '_users_id_assign'   => $uid
      ]);
      $this->integer($duplicate)->isGreaterThan(0);

      $link = new \Ticket_Ticket();
      $this->integer(
         (int)$link->add([
            'tickets_id_1' => $duplicated,
            'tickets_id_2' => $duplicate,
            'link'         => \Ticket_Ticket::DUPLICATE_WITH
         ])
      )->isGreaterThan(0);

      //we got one ticketg, and another that duplicates it.
      //let's manage solutions on them
      $solution = new \ITILSolution();
      $this->integer(
         (int)$solution->add([
            'itemtype'  => $ticket::getType(),
            'items_id'  => $duplicate,
            'content'   => 'Solve from main ticket'
         ])
      );
      //reload from DB
      $this->boolean($ticket->getFromDB($duplicate))->isTrue();
      $this->variable($ticket->getField('status'))->isEqualTo($ticket::SOLVED);

      $this->boolean($ticket->getFromDB($duplicated))->isTrue();
      $this->variable($ticket->getField('status'))->isEqualTo($ticket::SOLVED);
   }

   public function testMultipleSolution() {
      $this->login();

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

      $solution = new \ITILSolution();

      // 1st solution, it should be accepted
      $this->integer(
         (int)$solution->add([
            'itemtype'  => $ticket::getType(),
            'items_id'  => $ticket->getID(),
            'content'   => '1st solution, should be accepted!'
         ])
      );

      $this->boolean($solution->getFromDB($solution->getID()))->isTrue();
      $this->integer((int)$solution->fields['status'])->isIdenticalTo(\CommonITILValidation::WAITING);

      // try to add directly another solution, it should be refused
      $this->boolean(
         $solution->add([
            'itemtype'  => $ticket::getType(),
            'items_id'  => $ticket->getID(),
            'content'   => '2nd solution, should be refused!'
         ])
      )->isFalse();
   }
}
