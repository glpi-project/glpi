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

/* Test for inc/ticketvalidation.class.php */

class TicketValidation extends DbTestCase {

   /**
    * Create a new ticket and return its id
    *
    * @return integer
    */
   private function getNewTicket($validation=0, $validationUser=0, $validationUnit='%',
         $validationUserUnit='%') {
      //create reference ticket
      $ticket = new \Ticket();
      $this->integer((int)$ticket->add([
         'name'                 => 'ticket title',
         'description'          => 'a description',
         'content'              => '',
         'entities_id'          => getItemByTypeName('Entity', '_test_root_entity', true),
         'validation'           => $validation,
         'validation_unit'      => $validationUnit,
         'validation_user'      => $validationUser,
         'validation_user_unit' => $validationUserUnit
      ]))->isGreaterThan(0);

      $this->boolean($ticket->isNewItem())->isFalse();
      return (int)$ticket->getID();
   }

   private function createNewUser($userName) {
      $user = new \User();
      $this->integer((int)$user->add([
         'name'        => $userName,
         'profiles_id' => 4,
         'entities_id' => 0
      ]))->isGreaterThan(0);

      $this->boolean($user->isNewItem())->isFalse();
      return (int)$user->getID();
   }

   private function createNewGroup($groupName) {
      $group = new \Group();
      $this->integer((int)$group->add([
         'name'        => $groupName,
         'entities_id' => 0
      ]))->isGreaterThan(0);

      $this->boolean($group->isNewItem())->isFalse();
      return (int)$group->getID();
   }

   private function fillUsersInGroup($groups_id, $users=[]) {
      $group_user = new \Group_User();
      foreach ($users as $users_id) {
         $this->integer((int)$group_user->add([
            'groups_id' => $groups_id,
            'users_id'  => $users_id
         ]))->isGreaterThan(0);

         $this->boolean($group_user->isNewItem())->isFalse();
      }
   }

   private function createNewValidation($tickets_id, $users_id, $groups_id=0) {
      $ticketValidation = new \TicketValidation();
      $this->integer((int)$ticketValidation->add([
         'tickets_id'        => $tickets_id,
         'users_id'          => $users_id,
         'users_id_validate' => $users_id,
         'groups_id'         => $groups_id,
         'entities_id'       => 0
      ]))->isGreaterThan(0);

      $this->boolean($ticketValidation->isNewItem())->isFalse();
      return (int)$ticketValidation->getID();
   }

   private function setValidation($id, $status) {
      $ticketValidation = new \TicketValidation();
      $this->boolean($ticketValidation->update([
         'id'     => $id,
         'status' => $status,
      ]))->isTrue();
   }

   private function getTicketValidation($ticket_id) {
      $ticket = new \Ticket();
      $ticket->getFromDB($ticket_id);
      return $ticket->fields['global_validation'];
   }

   public function testComputeValidationOnlyUsersPercentage() {
      $this->login();
      $ticketId = $this->getNewTicket(50, 10);
      $users_id_1 = $this->createNewUser('valid_1');
      $users_id_2 = $this->createNewUser('valid_2');
      $users_id_3 = $this->createNewUser('valid_3');
      $users_id_4 = $this->createNewUser('valid_4');

      // Create validation
      $val_1 = $this->createNewValidation($ticketId, $users_id_1);
      $val_2 = $this->createNewValidation($ticketId, $users_id_2);
      $val_3 = $this->createNewValidation($ticketId, $users_id_3);
      $val_4 = $this->createNewValidation($ticketId, $users_id_4);

      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::WAITING);
      $this->setValidation($val_1, \TicketValidation::ACCEPTED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::WAITING);
      $this->setValidation($val_2, \TicketValidation::ACCEPTED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::ACCEPTED);
      $this->setValidation($val_3, \TicketValidation::ACCEPTED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::ACCEPTED);
      $this->setValidation($val_4, \TicketValidation::REFUSED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::ACCEPTED);
   }

   public function testComputeValidationOnlyUsersRefusedPercentage() {
      $this->login();
      $ticketId = $this->getNewTicket(22, 10);
      $users_id_1 = $this->createNewUser('valid_1');
      $users_id_2 = $this->createNewUser('valid_2');
      $users_id_3 = $this->createNewUser('valid_3');
      $users_id_4 = $this->createNewUser('valid_4');
      $users_id_5 = $this->createNewUser('valid_5');

      // Create validation
      $val_1 = $this->createNewValidation($ticketId, $users_id_1);
      $val_2 = $this->createNewValidation($ticketId, $users_id_2);
      $val_3 = $this->createNewValidation($ticketId, $users_id_3);
      $val_4 = $this->createNewValidation($ticketId, $users_id_4);
      $val_5 = $this->createNewValidation($ticketId, $users_id_5);

      $this->setValidation($val_1, \TicketValidation::REFUSED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::WAITING);
      $this->setValidation($val_2, \TicketValidation::REFUSED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::WAITING);
      $this->setValidation($val_3, \TicketValidation::REFUSED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::WAITING);
      $this->setValidation($val_4, \TicketValidation::REFUSED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::REFUSED);
      $this->setValidation($val_5, \TicketValidation::ACCEPTED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::REFUSED);
   }


   public function testComputeValidationOnlyGroupsPercentage() {
      $this->login();
      $ticketId = $this->getNewTicket(50, 60);
      $users_id_1 = $this->createNewUser('valid_1');
      $users_id_2 = $this->createNewUser('valid_2');
      $users_id_3 = $this->createNewUser('valid_3');
      $users_id_4 = $this->createNewUser('valid_4');
      $users_id_5 = $this->createNewUser('valid_5');
      $users_id_6 = $this->createNewUser('valid_6');

      $groups_id_1 = $this->createNewGroup('grp_valid_1');
      $groups_id_2 = $this->createNewGroup('grp_valid_2');
      $groups_id_3 = $this->createNewGroup('grp_valid_3');

      $this->fillUsersInGroup($groups_id_1, [$users_id_1, $users_id_2]);
      $this->fillUsersInGroup($groups_id_2, [$users_id_3, $users_id_4, $users_id_5]);
      $this->fillUsersInGroup($groups_id_3, [$users_id_6]);

      // Create validation
      $val_1 = $this->createNewValidation($ticketId, $users_id_1, $groups_id_1);
      $val_2 = $this->createNewValidation($ticketId, $users_id_2, $groups_id_1);
      $val_3 = $this->createNewValidation($ticketId, $users_id_3, $groups_id_2);
      $val_4 = $this->createNewValidation($ticketId, $users_id_4, $groups_id_2);
      $val_5 = $this->createNewValidation($ticketId, $users_id_5, $groups_id_2);
      $val_6 = $this->createNewValidation($ticketId, $users_id_5, $groups_id_3);

      $this->setValidation($val_1, \TicketValidation::ACCEPTED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::WAITING);

      $this->setValidation($val_3, \TicketValidation::ACCEPTED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::WAITING);
      $this->setValidation($val_4, \TicketValidation::ACCEPTED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::WAITING);

      $this->setValidation($val_6, \TicketValidation::ACCEPTED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::ACCEPTED);

      $this->setValidation($val_2, \TicketValidation::REFUSED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::ACCEPTED);
   }

   public function testComputeValidationMixPercentage() {
      $this->login();
      $ticketId = $this->getNewTicket(80, 50);
      $users_id_1 = $this->createNewUser('valid_1');
      $users_id_2 = $this->createNewUser('valid_2');
      $users_id_3 = $this->createNewUser('valid_3');
      $users_id_4 = $this->createNewUser('valid_4');
      $users_id_5 = $this->createNewUser('valid_5');
      $users_id_6 = $this->createNewUser('valid_6');
      $users_id_7 = $this->createNewUser('valid_7');
      $users_id_8 = $this->createNewUser('valid_8');

      $groups_id_1 = $this->createNewGroup('grp_valid_1');
      $groups_id_2 = $this->createNewGroup('grp_valid_2');

      $this->fillUsersInGroup($groups_id_1, [$users_id_1, $users_id_2]);
      $this->fillUsersInGroup($groups_id_2, [$users_id_3, $users_id_4, $users_id_5]);

      // Create validation
      $val_1 = $this->createNewValidation($ticketId, $users_id_1, $groups_id_1);
      $val_2 = $this->createNewValidation($ticketId, $users_id_2, $groups_id_1);
      $val_3 = $this->createNewValidation($ticketId, $users_id_3, $groups_id_2);
      $val_4 = $this->createNewValidation($ticketId, $users_id_4, $groups_id_2);
      $val_5 = $this->createNewValidation($ticketId, $users_id_5, $groups_id_2);
      $val_6 = $this->createNewValidation($ticketId, $users_id_6);
      $val_7 = $this->createNewValidation($ticketId, $users_id_7);
      $val_8 = $this->createNewValidation($ticketId, $users_id_8);

      $this->setValidation($val_1, \TicketValidation::ACCEPTED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::WAITING);

      $this->setValidation($val_3, \TicketValidation::ACCEPTED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::WAITING);
      $this->setValidation($val_4, \TicketValidation::ACCEPTED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::WAITING);

      $this->setValidation($val_6, \TicketValidation::ACCEPTED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::WAITING);

      $this->setValidation($val_7, \TicketValidation::ACCEPTED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::ACCEPTED);

      $this->setValidation($val_8, \TicketValidation::REFUSED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::ACCEPTED);

      $this->setValidation($val_2, \TicketValidation::REFUSED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::ACCEPTED);
   }


   public function testComputeValidationOnlyUsersCounter() {
      $this->login();
      $ticketId = $this->getNewTicket(2, 1, 'c', 'c');
      $users_id_1 = $this->createNewUser('valid_1');
      $users_id_2 = $this->createNewUser('valid_2');
      $users_id_3 = $this->createNewUser('valid_3');
      $users_id_4 = $this->createNewUser('valid_4');

      // Create validation
      $val_1 = $this->createNewValidation($ticketId, $users_id_1);
      $val_2 = $this->createNewValidation($ticketId, $users_id_2);
      $val_3 = $this->createNewValidation($ticketId, $users_id_3);
      $val_4 = $this->createNewValidation($ticketId, $users_id_4);

      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::WAITING);
      $this->setValidation($val_1, \TicketValidation::ACCEPTED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::WAITING);
      $this->setValidation($val_2, \TicketValidation::ACCEPTED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::ACCEPTED);
      $this->setValidation($val_3, \TicketValidation::ACCEPTED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::ACCEPTED);
      $this->setValidation($val_4, \TicketValidation::REFUSED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::ACCEPTED);
   }

   public function testComputeValidationOnlyUsersRefusedCounter() {
      $this->login();
      $ticketId = $this->getNewTicket(2, 1, 'c', 'c');
      $users_id_1 = $this->createNewUser('valid_1');
      $users_id_2 = $this->createNewUser('valid_2');
      $users_id_3 = $this->createNewUser('valid_3');
      $users_id_4 = $this->createNewUser('valid_4');
      $users_id_5 = $this->createNewUser('valid_5');

      // Create validation
      $val_1 = $this->createNewValidation($ticketId, $users_id_1);
      $val_2 = $this->createNewValidation($ticketId, $users_id_2);
      $val_3 = $this->createNewValidation($ticketId, $users_id_3);
      $val_4 = $this->createNewValidation($ticketId, $users_id_4);
      $val_5 = $this->createNewValidation($ticketId, $users_id_5);

      $this->setValidation($val_1, \TicketValidation::REFUSED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::WAITING);
      $this->setValidation($val_2, \TicketValidation::REFUSED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::WAITING);
      $this->setValidation($val_3, \TicketValidation::REFUSED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::WAITING);
      $this->setValidation($val_4, \TicketValidation::REFUSED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::REFUSED);
      $this->setValidation($val_5, \TicketValidation::ACCEPTED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::REFUSED);
   }

   public function testComputeValidationOnlyGroupsCounter() {
      $this->login();
      $ticketId = $this->getNewTicket(3, 1, 'c', 'c');
      $users_id_1 = $this->createNewUser('valid_1');
      $users_id_2 = $this->createNewUser('valid_2');
      $users_id_3 = $this->createNewUser('valid_3');
      $users_id_4 = $this->createNewUser('valid_4');
      $users_id_5 = $this->createNewUser('valid_5');
      $users_id_6 = $this->createNewUser('valid_6');

      $groups_id_1 = $this->createNewGroup('grp_valid_1');
      $groups_id_2 = $this->createNewGroup('grp_valid_2');
      $groups_id_3 = $this->createNewGroup('grp_valid_3');

      $this->fillUsersInGroup($groups_id_1, [$users_id_1, $users_id_2]);
      $this->fillUsersInGroup($groups_id_2, [$users_id_3, $users_id_4, $users_id_5]);
      $this->fillUsersInGroup($groups_id_3, [$users_id_6]);

      // Create validation
      $val_1 = $this->createNewValidation($ticketId, $users_id_1, $groups_id_1);
      $val_2 = $this->createNewValidation($ticketId, $users_id_2, $groups_id_1);
      $val_3 = $this->createNewValidation($ticketId, $users_id_3, $groups_id_2);
      $val_4 = $this->createNewValidation($ticketId, $users_id_4, $groups_id_2);
      $val_5 = $this->createNewValidation($ticketId, $users_id_5, $groups_id_2);
      $val_6 = $this->createNewValidation($ticketId, $users_id_5, $groups_id_3);

      $this->setValidation($val_1, \TicketValidation::ACCEPTED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::WAITING);

      $this->setValidation($val_3, \TicketValidation::ACCEPTED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::WAITING);
      $this->setValidation($val_4, \TicketValidation::ACCEPTED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::WAITING);

      $this->setValidation($val_6, \TicketValidation::ACCEPTED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::ACCEPTED);

      $this->setValidation($val_2, \TicketValidation::REFUSED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::ACCEPTED);
   }


   public function testComputeValidationUsersZero() {
      $this->login();
      $ticketId = $this->getNewTicket(0, 1, 'c', 'c');
      $users_id_1 = $this->createNewUser('valid_1');
      $users_id_2 = $this->createNewUser('valid_2');
      $users_id_3 = $this->createNewUser('valid_3');
      $users_id_4 = $this->createNewUser('valid_4');

      // Create validation
      $val_1 = $this->createNewValidation($ticketId, $users_id_1);
      $val_2 = $this->createNewValidation($ticketId, $users_id_2);
      $val_3 = $this->createNewValidation($ticketId, $users_id_3);
      $val_4 = $this->createNewValidation($ticketId, $users_id_4);

      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::WAITING);
      $this->setValidation($val_1, \TicketValidation::ACCEPTED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::ACCEPTED);
      $this->setValidation($val_2, \TicketValidation::ACCEPTED);
      $this->integer((int)$this->getTicketValidation($ticketId))->isEqualTo(\TicketValidation::ACCEPTED);
   }

   public function testPureComputeZeroAccept() {
      $number = 0;
      $type = '%';
      $total = 2;
      $count = [
         \TicketValidation::WAITING  => 2,
         \TicketValidation::REFUSED  => 0,
         \TicketValidation::ACCEPTED => 0
      ];

      $status = \TicketValidation::pureCompute($number, $type, $total, $count);
      $this->integer((int)$status)->isEqualTo(\TicketValidation::WAITING);
   }
}
