<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
use Entity;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Tests\AbstractCommonItilObject_ItemTest;
use Item_Ticket;
use Profile;
use Ticket;
use TicketCost;
use User;

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

    public function testDeleteFormRequiresRightsOnTheTicket(): void
    {
        $this->login();
        $entity_1 = getItemByTypeName(Entity::class, '_test_child_1', true);
        $entity_2 = getItemByTypeName(Entity::class, '_test_child_2', true);

        $links = [];
        foreach ([$entity_1, $entity_2] as $entity_id) {
            $computer = $this->createItem(Computer::class, ['name' => 'Computer ' . $entity_id, 'entities_id' => $entity_id]);
            $ticket   = $this->createItem(Ticket::class, ['name' => 'Ticket ' . $entity_id, 'content' => 'Content', 'entities_id' => $entity_id]);
            $links[$entity_id] = [
                $ticket,
                $this->createItem(Item_Ticket::class, [
                    'tickets_id' => $ticket->getID(),
                    'itemtype'   => Computer::class,
                    'items_id'   => $computer->getID(),
                ]),
            ];
        }

        // A technician that can only access the first entity
        $this->createItem(User::class, [
            'name'          => 'restricted_technician',
            'password'      => 'Restricted-Pass-123!',
            'password2'     => 'Restricted-Pass-123!',
            '_profiles_id'  => getItemByTypeName(Profile::class, 'Technician', true),
            '_entities_id'  => $entity_1,
            '_is_recursive' => 0,
        ], skip_fields: ['password', 'password2']);
        $this->login('restricted_technician', 'Restricted-Pass-123!');

        $submit = function (Ticket $ticket, Item_Ticket $link): ?\Throwable {
            $_POST = [
                'delete'     => 1,
                'tickets_id' => $ticket->getID(),
                'itemtype'   => $link->fields['itemtype'],
                'items_id'   => $link->fields['items_id'],
            ];
            $exception = null;
            try {
                include GLPI_ROOT . '/front/item_ticket.form.php';
            } catch (\Throwable $e) {
                $exception = $e;
            } finally {
                $_POST = [];
            }
            return $exception;
        };

        // The link of a ticket of the other entity cannot be removed
        [$ticket_2, $link_2] = $links[$entity_2];
        $this->assertInstanceOf(AccessDeniedHttpException::class, $submit($ticket_2, $link_2));
        $this->assertTrue((new Item_Ticket())->getFromDB($link_2->getID()));

        // The link of a ticket of the entity of the technician can be removed
        [$ticket_1, $link_1] = $links[$entity_1];
        $this->assertNotInstanceOf(AccessDeniedHttpException::class, $submit($ticket_1, $link_1));
        $this->assertFalse((new Item_Ticket())->getFromDB($link_1->getID()));
    }
}
