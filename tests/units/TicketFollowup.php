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

/* Test for inc/ticketfollowup.class.php */

class TicketFollowup extends DbTestCase {

   /**
    * Create a new ticket and return its id
    *
    * @return integer
    */
   private function getNewTicket() {
      //create reference ticket
      $ticket = new \Ticket();
      $this->integer((int)$ticket->add([
            'name'         => 'ticket title',
            'description'  => 'a description',
            'content'      => '',
            'entities_id'  => getItemByTypeName('Entity', '_test_root_entity', true),
      ]))->isGreaterThan(0);

      $this->boolean($ticket->isNewItem())->isFalse();
      $this->boolean($ticket->can($ticket->getID(), \READ))->isTrue();
      return (int)$ticket->getID();
   }

   public function testACL() {
      $this->login();

      $ticketId = $this->getNewTicket();
      $fup      = new \TicketFollowup();
      $tmp      = ['tickets_id' => $ticketId];
      $this->boolean((boolean) $fup->can(-1, \CREATE, $tmp))->isTrue();

      $fup_id = $fup->add([
         'content'      => "my followup",
         'tickets_id'   => $ticketId
      ]);
      $this->integer($fup_id)->isGreaterThan(0);
      $this->boolean((boolean) $fup->canViewItem())->isTrue();
      $this->boolean((boolean) $fup->canUpdateItem())->isTrue();
      $this->boolean((boolean) $fup->canPurgeItem())->isTrue();
   }
}
