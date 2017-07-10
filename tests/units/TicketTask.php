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

/* Test for inc/tickettask.class.php */

class TicketTask extends DbTestCase {

   public function testGetTaskList() {

      $this->login();

      //create reference ticket
      $ticket = new \Ticket();
      $this->integer((int)$ticket->add([
            'name'         => 'ticket title',
            'description'  => 'a description',
            'content'      => ''
      ]))->isGreaterThan(0);

      $this->boolean($ticket->isNewItem())->isFalse();
      $ticketId = $ticket->getID();

      $tasksstates = [
         \Planning::TODO,
         \Planning::TODO,
         \Planning::INFO
      ];
      //create few tasks
      $task = new \TicketTask();
      foreach ($tasksstates as $taskstate) {
         $task->add([
            'state'        => $taskstate,
            'tickets_id'   => $ticketId
         ]);
      }

      $iterator = $task::getTaskList('todo', true);
      $this->string($iterator->getSql())->isIdenticalTo(
         'SELECT `id` FROM `glpi_tickettasks` WHERE `state` = 1 AND `users_id_tech` = 6 ORDER BY `date_mod` DESC'
      );
      //only 1 task created? why?
      $this->integer(count($iterator))->isIdenticalTo(1);
   }

}
