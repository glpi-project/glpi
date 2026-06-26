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

use Computer;
use Glpi\Dashboard\Filters\SlaFilter;
use Glpi\Tests\DbTestCase;
use SLA;
use SLM;
use Ticket;

class SlaFilterTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    private function createSlm(): SLM
    {
        return $this->createItem(SLM::class, [
            'name'        => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]);
    }

    private function createSla(SLM $slm, int $type): SLA
    {
        return $this->createItem(SLA::class, [
            'name'            => 'SLA_' . $type,
            'entities_id'     => $this->getTestRootEntity(true),
            'slms_id'         => $slm->getID(),
            'type'            => $type,
            'number_time'     => 4,
            'definition_time' => 'hour',
        ]);
    }

    public function testSlaFilterAppliesToTickets(): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        $slm     = $this->createSlm();
        $sla_ttr = $this->createSla($slm, SLM::TTR);
        $sla_tto = $this->createSla($slm, SLM::TTO);

        $ticket = $this->createItem(Ticket::class, [
            'name'         => __FUNCTION__,
            'content'      => __FUNCTION__,
            'entities_id'  => $this->getTestRootEntity(true),
            'slas_id_ttr'  => $sla_ttr->getID(),
            'slas_id_tto'  => $sla_tto->getID(),
        ]);

        $common_criteria = [
            'SELECT' => ['glpi_tickets.id AS tickets_id'],
            'FROM'   => Ticket::getTable(),
        ];

        // Filtering by the TTR SLA must match the ticket
        $this->assertContains(
            $ticket->getID(),
            array_column(
                iterator_to_array(
                    $DB->request($common_criteria + SlaFilter::getCriteria('glpi_tickets', $sla_ttr->getID()))
                ),
                'tickets_id'
            )
        );

        // Filtering by the TTO SLA must match the same ticket
        $this->assertContains(
            $ticket->getID(),
            array_column(
                iterator_to_array(
                    $DB->request($common_criteria + SlaFilter::getCriteria('glpi_tickets', $sla_tto->getID()))
                ),
                'tickets_id'
            )
        );
    }

    public function testSlaFilterIgnoresUnrelatedSla(): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        $slm     = $this->createSlm();
        $sla     = $this->createSla($slm, SLM::TTR);
        $other   = $this->createSla($slm, SLM::TTR);

        $ticket = $this->createItem(Ticket::class, [
            'name'         => __FUNCTION__,
            'content'      => __FUNCTION__,
            'entities_id'  => $this->getTestRootEntity(true),
            'slas_id_ttr'  => $sla->getID(),
        ]);

        $rows = iterator_to_array(
            $DB->request([
                'SELECT' => ['glpi_tickets.id AS tickets_id'],
                'FROM'   => Ticket::getTable(),
            ] + SlaFilter::getCriteria('glpi_tickets', $other->getID()))
        );

        $this->assertNotContains($ticket->getID(), array_column($rows, 'tickets_id'));
    }

    public function testCanBeApplied(): void
    {
        $this->assertTrue(SlaFilter::canBeApplied(Ticket::getTable()));
        $this->assertFalse(SlaFilter::canBeApplied(Computer::getTable()));
    }

    public function testGetCriteriaWithoutValueIsEmpty(): void
    {
        $this->assertSame([], SlaFilter::getCriteria('glpi_tickets', 0));
        $this->assertSame([], SlaFilter::getCriteria('glpi_tickets', ''));
    }
}
