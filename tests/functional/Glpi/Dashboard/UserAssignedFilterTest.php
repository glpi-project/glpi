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

namespace tests\units\Glpi\Dashboard;

use CommonITILActor;
use Computer;
use Glpi\Dashboard\Filters\UserAssignedFilter;
use Glpi\Tests\DbTestCase;
use Ticket;

class UserAssignedFilterTest extends DbTestCase
{
    public function testCanBeApplied(): void
    {
        $this->assertTrue(UserAssignedFilter::canBeApplied(Ticket::getTable()));
        $this->assertFalse(UserAssignedFilter::canBeApplied(Computer::getTable()));
    }

    public function testGetCriteria(): void
    {
        $criteria = UserAssignedFilter::getCriteria('glpi_tickets', '42');
        $this->assertSame(CommonITILActor::ASSIGN, $criteria['WHERE']['ul_assigned.type']);
        $this->assertSame(42, $criteria['WHERE']['ul_assigned.users_id']);

        $this->assertSame([], UserAssignedFilter::getCriteria('glpi_tickets', ''));
        $this->assertSame([], UserAssignedFilter::getCriteria('glpi_tickets', '0'));
        $this->assertSame([], UserAssignedFilter::getCriteria('glpi_tickets', 'invalid'));
    }

    public function testGetSearchCriteria(): void
    {
        $this->login();

        $criteria = UserAssignedFilter::getSearchCriteria('glpi_tickets', '42');
        $this->assertCount(1, $criteria);
        $this->assertSame('AND', $criteria[0]['link']);
        $this->assertSame('equals', $criteria[0]['searchtype']);
        $this->assertSame(5, $criteria[0]['field']);
        $this->assertSame(42, $criteria[0]['value']);

        $this->assertSame([], UserAssignedFilter::getSearchCriteria('glpi_tickets', ''));
        $this->assertSame([], UserAssignedFilter::getSearchCriteria('glpi_tickets', '0'));
        $this->assertSame([], UserAssignedFilter::getSearchCriteria('glpi_tickets', 'invalid'));
    }
}
