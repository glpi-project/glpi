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

use Glpi\Dashboard\Filters\EntityFilter;
use Glpi\Tests\DbTestCase;
use Ticket;

class EntityFilterTest extends DbTestCase
{
    public function testEntityFilterMatchesSubtree(): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        $this->login();

        $root_id = $this->getTestRootEntity(true);
        $child_1 = getItemByTypeName('Entity', '_test_child_1', true);
        $child_2 = getItemByTypeName('Entity', '_test_child_2', true);

        $this->setEntity($root_id, true);

        $ticket_1 = $this->createItem(Ticket::class, [
            'name'        => __FUNCTION__ . '_1',
            'content'     => __FUNCTION__,
            'entities_id' => $child_1,
        ]);
        $ticket_2 = $this->createItem(Ticket::class, [
            'name'        => __FUNCTION__ . '_2',
            'content'     => __FUNCTION__,
            'entities_id' => $child_2,
        ]);

        $base = [
            'SELECT' => ['glpi_tickets.id AS tickets_id'],
            'FROM'   => Ticket::getTable(),
        ];
        $fetch = fn($value) => array_column(
            iterator_to_array($DB->request($base + EntityFilter::getCriteria('glpi_tickets', $value))),
            'tickets_id'
        );

        // Filtering on child_1 only matches its own ticket.
        $this->assertContains($ticket_1->getID(), $fetch($child_1));
        $this->assertNotContains($ticket_2->getID(), $fetch($child_1));

        // Filtering on the root entity matches both descendants.
        $this->assertContains($ticket_1->getID(), $fetch($root_id));
        $this->assertContains($ticket_2->getID(), $fetch($root_id));
    }

    public function testEntityFilterIgnoredOutsideActiveScope(): void
    {
        $this->login();

        $child_1 = getItemByTypeName('Entity', '_test_child_1', true);
        $child_2 = getItemByTypeName('Entity', '_test_child_2', true);

        // Only child_1 is active — picking child_2 must not leak any WHERE.
        $this->setEntity($child_1, false);

        $this->assertSame([], EntityFilter::getCriteria('glpi_tickets', $child_2));
    }
}
