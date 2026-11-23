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

use Glpi\Dashboard\Filters\DatesFilter;
use Glpi\Tests\DbTestCase;
use Ticket;

class DatesFilterTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    private function getTicketIds(\DBmysql $DB, array $filter_value): array
    {
        return array_column(
            iterator_to_array(
                $DB->request([
                    'SELECT' => ['glpi_tickets.id AS tickets_id'],
                    'FROM'   => Ticket::getTable(),
                ] + DatesFilter::getCriteria(Ticket::getTable(), $filter_value))
            ),
            'tickets_id'
        );
    }

    /**
     * A ticket created at 11:59:59 p.m. on the last day of the filter must be included
     */
    public function testEndDayLastSecondIsIncluded(): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        $ticket = $this->createItem(Ticket::class, [
            'name'        => __FUNCTION__,
            'content'     => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $this->updateItem(Ticket::class, $ticket->getID(), ['date' => '2026-06-26 23:59:59']);

        $this->assertContains(
            $ticket->getID(),
            $this->getTicketIds($DB, ['2026-06-26', '2026-06-26']),
            'ticket at 23:59:59 on end day must be included'
        );
    }

    public function testStartOfRangeIsIncluded(): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        $ticket = $this->createItem(Ticket::class, [
            'name'        => __FUNCTION__,
            'content'     => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $this->updateItem(Ticket::class, $ticket->getID(), ['date' => '2026-06-26 00:00:00']);

        $this->assertContains(
            $ticket->getID(),
            $this->getTicketIds($DB, ['2026-06-26', '2026-06-27']),
            'ticket at start of range (00:00:00) must be included'
        );
    }

    public function testEndOfMultiDayRangeIsIncluded(): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        $ticket = $this->createItem(Ticket::class, [
            'name'        => __FUNCTION__,
            'content'     => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $this->updateItem(Ticket::class, $ticket->getID(), ['date' => '2026-06-27 23:59:59']);

        $this->assertContains(
            $ticket->getID(),
            $this->getTicketIds($DB, ['2026-06-26', '2026-06-27']),
            'ticket at 23:59:59 on last day of a multi-day range must be included'
        );
    }

    public function testTicketBeforeStartDayIsExcluded(): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        $ticket = $this->createItem(Ticket::class, [
            'name'        => __FUNCTION__,
            'content'     => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $this->updateItem(Ticket::class, $ticket->getID(), ['date' => '2026-06-25 23:59:59']);

        $this->assertNotContains(
            $ticket->getID(),
            $this->getTicketIds($DB, ['2026-06-26', '2026-06-26']),
            'ticket before start day must be excluded'
        );
    }

    public function testTicketAfterEndDayIsExcluded(): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        $ticket = $this->createItem(Ticket::class, [
            'name'        => __FUNCTION__,
            'content'     => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $this->updateItem(Ticket::class, $ticket->getID(), ['date' => '2026-06-27 00:00:00']);

        $this->assertNotContains(
            $ticket->getID(),
            $this->getTicketIds($DB, ['2026-06-26', '2026-06-26']),
            'ticket on day after end day must be excluded'
        );
    }

    public function testEmptyFilterValueReturnsNoCriteria(): void
    {
        $this->assertSame([], DatesFilter::getCriteria(Ticket::getTable(), []));
        $this->assertSame([], DatesFilter::getCriteria(Ticket::getTable(), ['2026-06-26']));
        $this->assertSame([], DatesFilter::getCriteria(Ticket::getTable(), 'invalid'));
    }
}
