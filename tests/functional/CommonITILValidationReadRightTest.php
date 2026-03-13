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

use ChangeValidation;
use Glpi\Tests\DbTestCase;
use Ticket;
use TicketValidation;

class CommonITILValidationReadRightTest extends DbTestCase
{
    public function testTicketValidationCanViewWithReadRight(): void
    {
        $this->login();

        $_SESSION['glpiactiveprofile'][TicketValidation::$rightname] = READ;

        $this->assertTrue(TicketValidation::canView());
    }

    public function testTicketValidationCanViewWithNoRight(): void
    {
        $this->login();

        $_SESSION['glpiactiveprofile'][TicketValidation::$rightname] = 0;

        $this->assertFalse(TicketValidation::canView());
    }

    public function testChangeValidationCanViewWithReadRight(): void
    {
        $this->login();

        $_SESSION['glpiactiveprofile'][ChangeValidation::$rightname] = READ;

        $this->assertTrue(ChangeValidation::canView());
    }

    public function testChangeValidationCanViewWithNoRight(): void
    {
        $this->login();

        $_SESSION['glpiactiveprofile'][ChangeValidation::$rightname] = 0;

        $this->assertFalse(ChangeValidation::canView());
    }

    public function testTicketValidationGetRightsIncludesRead(): void
    {
        $this->login();

        $validation = new TicketValidation();
        $rights = $validation->getRights();

        $this->assertArrayHasKey(READ, $rights);
    }

    public function testCommonITILValidationGetRightsIncludesRead(): void
    {
        $this->login();

        $validation = new ChangeValidation();
        $rights = $validation->getRights();

        $this->assertArrayHasKey(READ, $rights);
    }

    public function testGetTimelineItemsIncludesValidationWithReadRight(): void
    {
        $this->login();
        $this->setEntity('Root entity', true);

        $ticket = $this->createItem(Ticket::class, [
            'name'        => 'Timeline validation test',
            'content'     => 'Test content',
            'entities_id' => 0,
        ]);

        $validation = $this->createItem(TicketValidation::class, [
            'tickets_id'   => $ticket->getID(),
            'entities_id'  => 0,
            'itemtype_target' => 'User',
            'items_id_target' => 2,
        ]);

        $this->assertGreaterThan(0, $validation->getID());

        $ticket->getFromDB($ticket->getID());

        // Simulate read-only access: only READ on ticketvalidation, READALL on ticket
        $_SESSION['glpiactiveprofile'][TicketValidation::$rightname] = READ;
        $_SESSION['glpiactiveprofile'][Ticket::$rightname] = Ticket::READALL;

        $timeline = $ticket->getTimelineItems();

        $validation_items = array_filter(
            $timeline,
            fn($item) => ($item['type'] ?? '') === TicketValidation::class
        );

        $this->assertNotEmpty($validation_items);
    }

    public function testGetTimelineItemsExcludesValidationWithNoRight(): void
    {
        $this->login();
        $this->setEntity('Root entity', true);

        $ticket = $this->createItem(Ticket::class, [
            'name'        => 'Timeline validation no-right test',
            'content'     => 'Test content',
            'entities_id' => 0,
        ]);

        $this->createItem(TicketValidation::class, [
            'tickets_id'   => $ticket->getID(),
            'entities_id'  => 0,
            'itemtype_target' => 'User',
            'items_id_target' => 2,
        ]);

        $ticket->getFromDB($ticket->getID());

        $_SESSION['glpiactiveprofile'][TicketValidation::$rightname] = 0;

        $timeline = $ticket->getTimelineItems();

        $validation_items = array_filter(
            $timeline,
            fn($item) => $item['type'] === TicketValidation::class
        );

        $this->assertEmpty($validation_items);
    }
}
