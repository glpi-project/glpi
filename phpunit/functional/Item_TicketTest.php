<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace tests\units;

use Computer;
use Ticket;
use TicketCost;

class Item_TicketTest extends AbstractCommonItilObject_ItemTest
{
    public function testUpdateItemTCO(): void
    {
        $this->login();

        $computer = $this->createItem(Computer::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        // Link new computer with a new ticket
        $ticket = $this->createItem(Ticket::class, [
            'name' => __FUNCTION__,
            'content' => 'test',
            'entities_id' => $this->getTestRootEntity(true),
            'items_id' => ['Computer' => [$computer->getID()]],
        ], ['content', 'items_id']);

        // Add a cost to the ITIL
        $this->createItem(TicketCost::class, [
            'tickets_id' => $ticket->getID(),
            'cost_fixed' => 100,
        ]);

        // Check that the cost is considered in the TCO
        $computer->getFromDB($computer->getID());
        $this->assertEquals(100, $computer->fields['ticket_tco']);
    }
}
