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
use DbTestCase;
use Item_Ticket;
use Ticket;
use TicketCost;

class TicketCostTest extends DbTestCase
{
    private function getNewTicket()
    {
        return $this->createItem(Ticket::class, [
            'name' => 'my ticket name',
            'entities_id' => $this->getTestRootEntity(true),
            '_users_id_assign'   => getItemByTypeName('User', 'tech', true),
            'content'            => '',
        ]);
    }

    private function getNewComputer()
    {
        return $this->createItem(Computer::class, [
            'name' => 'my computer name',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
    }

    private function getNewTicketCost(array $values = [])
    {
        return $this->createItem(TicketCost::class, [
            'name' => $values['name'] ?? 'my ticket cost name',
            'entities_id' => $this->getTestRootEntity(true),
            'tickets_id' => $values['tickets_id'],
            'cost_time' => $values['cost_time'] ?? 0.,
            'cost_fixed' => $values['cost_fixed'] ?? 0.,
            'cost_material' => $values['cost_material'] ?? 0.,
            'actiontime' => $values['actiontime'] ?? 1,
        ]);
    }

    private function getNewItemTicket(array $values = [])
    {
        return $this->createItem(Item_Ticket::class, [
            'tickets_id' => $values['tickets_id'],
            'itemtype' => $values['itemtype'] ?? 'Computer',
            'items_id' => $values['items_id'],
        ]);
    }

    public function testAddCost()
    {
        $computer = $this->getNewComputer();
        $this->assertSame(0., floatval($computer->fields['ticket_tco']));
        $ticket = $this->getNewTicket();
        $this->getNewItemTicket(
            [
                'tickets_id' => $ticket->getID(),
                'itemtype' => 'Computer',
                'items_id' => $computer->getID(),
            ]
        );
        $computer->getFromDB($computer->getID());
        $this->assertSame(0., floatval($computer->fields['ticket_tco']));
        $this->getNewTicketCost(
            [
                'tickets_id' => $ticket->getID(),
                'cost_material' => 10.,
            ]
        );
        $computer->getFromDB($computer->getID());
        $this->assertSame(10., floatval($computer->fields['ticket_tco']));
        $this->getNewTicketCost(
            [
                'tickets_id' => $ticket->getID(),
                'cost_fixed' => 10.,
                'cost_material' => 80.,
            ]
        );
        $computer->getFromDB($computer->getID());
        $this->assertSame(100., floatval($computer->fields['ticket_tco']));
    }

    public function testRemoveCost()
    {
        $computer = $this->getNewComputer();
        $this->assertSame(0., floatval($computer->fields['ticket_tco']));
        $ticket = $this->getNewTicket();
        $this->getNewItemTicket(
            [
                'tickets_id' => $ticket->getID(),
                'itemtype' => 'Computer',
                'items_id' => $computer->getID(),
            ]
        );
        $ticketcost = $this->getNewTicketCost(
            [
                'tickets_id' => $ticket->getID(),
                'cost_fixed' => 10.,
                'cost_material' => 80.,
            ]
        );
        $computer->getFromDB($computer->getID());
        $this->assertSame(90., floatval($computer->fields['ticket_tco']));
        $ticketcost->delete(['id' => $ticketcost->getID()], true);
        $computer->getFromDB($computer->getID());
        $this->assertSame(0., floatval($computer->fields['ticket_tco']));
    }

    public function testAddItem()
    {
        $computer = $this->getNewComputer();
        $this->assertSame(0., floatval($computer->fields['ticket_tco']));
        $ticket = $this->getNewTicket();
        $this->getNewTicketCost(
            [
                'tickets_id' => $ticket->getID(),
                'cost_fixed' => 140.,
                'cost_material' => 860.,
            ]
        );
        $this->getNewTicketCost(
            [
                'tickets_id' => $ticket->getID(),
                'cost_fixed' => 80.,
                'cost_material' => 20.,
            ]
        );
        $this->getNewItemTicket(
            [
                'tickets_id' => $ticket->getID(),
                'itemtype' => 'Computer',
                'items_id' => $computer->getID(),
            ]
        );
        $computer->getFromDB($computer->getID());
        $this->assertSame(1100., floatval($computer->fields['ticket_tco']));
    }

    public function testItemInMultipleTicket()
    {
        $computer = $this->getNewComputer();
        $this->assertSame(0., floatval($computer->fields['ticket_tco']));

        $ticket = $this->getNewTicket();
        $this->getNewItemTicket(
            [
                'tickets_id' => $ticket->getID(),
                'itemtype' => 'Computer',
                'items_id' => $computer->getID(),
            ]
        );
        $computer->getFromDB($computer->getID());
        $this->assertSame(0., floatval($computer->fields['ticket_tco']));

        $this->getNewTicketCost(
            [
                'tickets_id' => $ticket->getID(),
                'cost_material' => 10.,
            ]
        );
        $computer->getFromDB($computer->getID());
        $this->assertSame(10., floatval($computer->fields['ticket_tco']));

        $ticket2 = $this->getNewTicket();
        $this->getNewItemTicket(
            [
                'tickets_id' => $ticket2->getID(),
                'itemtype' => 'Computer',
                'items_id' => $computer->getID(),
            ]
        );
        $this->getNewTicketCost(
            [
                'tickets_id' => $ticket2->getID(),
                'cost_fixed' => 10.,
                'cost_material' => 80.,
            ]
        );
        $computer->getFromDB($computer->getID());
        $this->assertSame(100., floatval($computer->fields['ticket_tco']));
    }
}
