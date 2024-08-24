<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

use DbTestCase;

/* Test for inc/ticket_ticket.class.php */

class Ticket_TicketTest extends DbTestCase
{
    private $tone;
    private $ttwo;

    private function createTickets()
    {
        $tone = new \Ticket();
        $this->assertGreaterThan(
            0,
            (int)$tone->add([
                'name'         => 'Linked ticket 01',
                'description'  => 'Linked ticket 01',
                'content'            => '',
            ])
        );
        $this->assertTrue($tone->getFromDB($tone->getID()));
        $this->tone = $tone;

        $ttwo = new \Ticket();
        $this->assertGreaterThan(
            0,
            (int)$ttwo->add([
                'name'         => 'Linked ticket 02',
                'description'  => 'Linked ticket 02',
                'content'            => '',
            ])
        );
        $this->assertTrue($ttwo->getFromDB($ttwo->getID()));
        $this->ttwo = $ttwo;
    }

    public function testSimpleLink()
    {
        $this->createTickets();
        $tone = $this->tone;
        $ttwo = $this->ttwo;

        $link = new \Ticket_Ticket();
        $lid = (int)$link->add([
            'tickets_id_1' => $tone->getID(),
            'tickets_id_2' => $ttwo->getID(),
            'link'         => \Ticket_Ticket::LINK_TO
        ]);
        $this->assertGreaterThan(0, $lid);

        //cannot add same link twice!
        $this->assertFalse(
            $link->add([
                'tickets_id_1' => $tone->getID(),
                'tickets_id_2' => $ttwo->getID(),
                'link'         => \Ticket_Ticket::LINK_TO
            ])
        );

        //but can be reclassed as a duplicate
        $this->assertGreaterThan(
            0,
            (int)$link->add([
                'tickets_id_1' => $tone->getID(),
                'tickets_id_2' => $ttwo->getID(),
                'link'         => \Ticket_Ticket::DUPLICATE_WITH
            ])
        );
        //original link has been removed
        $this->assertFalse($link->getFromDB($lid));

        //cannot reclass from duplicate to simple link
        $this->assertFalse(
            $link->add([
                'tickets_id_1' => $tone->getID(),
                'tickets_id_2' => $ttwo->getID(),
                'link'         => \Ticket_Ticket::LINK_TO
            ])
        );
    }

    public function testSonsParents()
    {
        $this->createTickets();
        $tone = $this->tone;
        $ttwo = $this->ttwo;

        $link = new \Ticket_Ticket();
        $this->assertGreaterThan(
            0,
            (int)$link->add([
                'tickets_id_1' => $tone->getID(),
                'tickets_id_2' => $ttwo->getID(),
                'link'         => \Ticket_Ticket::SON_OF
            ])
        );

        //cannot add same link twice!
        $link = new \Ticket_Ticket();
        $this->assertFalse(
            $link->add([
                'tickets_id_1' => $tone->getID(),
                'tickets_id_2' => $ttwo->getID(),
                'link'         => \Ticket_Ticket::SON_OF
            ])
        );

        $this->createTickets();
        $tone = $this->tone;
        $ttwo = $this->ttwo;

        $link = new \Ticket_Ticket();
        $this->assertGreaterThan(
            0,
            (int)$link->add([
                'tickets_id_1' => $tone->getID(),
                'tickets_id_2' => $ttwo->getID(),
                'link'         => \Ticket_Ticket::PARENT_OF
            ])
        );
        $this->assertTrue($link->getFromDB($link->getID()));

        //PARENT_OF is stored as inversed child
        $this->assertIsArray($link->fields);
        $this->assertSame($ttwo->getID(), $link->fields['tickets_id_1']);
        $this->assertSame($tone->getID(), $link->fields['tickets_id_2']);
        $this->assertSame(\Ticket_Ticket::SON_OF, $link->fields['link']);
    }

    public function testNumberOpen()
    {
        $this->login();
        $this->createTickets();
        $tone = $this->tone;
        $ttwo = $this->ttwo;

        $link = new \Ticket_Ticket();
        $this->assertGreaterThan(
            0,
            (int)$link->add([
                'tickets_id_1' => $tone->getID(),
                'tickets_id_2' => $ttwo->getID(),
                'link'         => \Ticket_Ticket::LINK_TO
            ])
        );

        //not a SON_OF => no child
        $this->assertSame(0, \Ticket_Ticket::countOpenChildren($link->getID()));

        $this->assertTrue(
            $link->update([
                'id'     => $link->getID(),
                'link'   => \Ticket_Ticket::SON_OF
            ])
        );
        $this->assertSame(1, \Ticket_Ticket::countOpenChildren($ttwo->getID()));

        $this->assertTrue(
            $tone->update([
                'id'     => $tone->getID(),
                'status' => \Ticket::CLOSED
            ])
        );
        $this->assertSame(0, \Ticket_Ticket::countOpenChildren($ttwo->getID()));
    }

    public function testRestrictedGetLinkedTicketsTo()
    {
        $this->login();
        $this->createTickets();

        $ticket_ticket = new \Ticket_Ticket();
        $this->assertGreaterThan(
            0,
            $ticket_ticket->add([
                'tickets_id_1' => $this->tone->getID(),
                'tickets_id_2' => $this->ttwo->getID(),
                'link'         => \Ticket_Ticket::LINK_TO
            ])
        );

        $ticket = new \Ticket();
        $this->assertGreaterThan(
            0,
            $other_tickets_id = $ticket->add([
                'name'      => 'Linked ticket 03',
                'content'   => 'Linked ticket 03',
                'users_id'  => $_SESSION['glpiID'] + 1, // Not current user
                '_skip_auto_assign' => true,
                'entities_id' => $this->getTestRootEntity(true),
            ])
        );

        $this->assertGreaterThan(
            0,
            $ticket_ticket->add([
                'tickets_id_1' => $this->tone->getID(),
                'tickets_id_2' => $other_tickets_id,
                'link'         => \Ticket_Ticket::LINK_TO
            ])
        );

        $linked = \Ticket_Ticket::getLinkedTicketsTo($this->tone->getID());
        $this->assertCount(2, $linked);
        $this->assertEqualsCanonicalizing(
            [$this->ttwo->getID(), $other_tickets_id],
            array_column($linked, 'tickets_id')
        );

        // Remove READALL ticket permission
        $_SESSION['glpiactiveprofile']['ticket'] = READ;
        $linked = \Ticket_Ticket::getLinkedTicketsTo($this->tone->getID());
        $this->assertCount(2, $linked);
        $this->assertEqualsCanonicalizing(
            [$this->ttwo->getID(), $other_tickets_id],
            array_column($linked, 'tickets_id')
        );
        // Get linked tickets using view restrictions
        $linked = \Ticket_Ticket::getLinkedTicketsTo($this->tone->getID(), true);
        $this->assertCount(1, $linked);
        $this->assertContains($this->ttwo->getID(), array_column($linked, 'tickets_id'));
    }
}
