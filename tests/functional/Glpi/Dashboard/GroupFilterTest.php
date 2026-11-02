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
use Glpi\Dashboard\Filters\GroupRequesterFilter;
use Glpi\Dashboard\Filters\GroupTechFilter;
use Glpi\Tests\DbTestCase;
use Group;
use Ticket;

class GroupFilterTest extends DbTestCase
{
    public function testITILGroupFilters()
    {
        /** @var \DBmysql */
        global $DB;

        $groups_id_1 = getItemByTypeName(Group::class, '_test_group_1', true);
        $groups_id_2 = getItemByTypeName(Group::class, '_test_group_2', true);
        $ticket = $this->createItem(Ticket::class, [
            'name' => __FUNCTION__,
            'content' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
            '_groups_id_requester' => $groups_id_1,
            '_groups_id_assign' => $groups_id_2,
        ]);

        $common_criteria = [
            'SELECT' => ['glpi_tickets.id AS tickets_id'],
            'FROM' => Ticket::getTable(),
        ];
        $this->assertContains(
            $ticket->getID(),
            array_column(
                iterator_to_array(
                    $DB->request($common_criteria + GroupRequesterFilter::getCriteria('glpi_tickets', $groups_id_1))
                ),
                'tickets_id'
            )
        );
        $this->assertContains(
            $ticket->getID(),
            array_column(
                iterator_to_array(
                    $DB->request($common_criteria + GroupTechFilter::getCriteria('glpi_tickets', $groups_id_2))
                ),
                'tickets_id'
            )
        );
    }

    public function testAssetGroupFilters()
    {
        /** @var \DBmysql */
        global $DB;

        $groups_id_1 = getItemByTypeName(Group::class, '_test_group_1', true);
        $groups_id_2 = getItemByTypeName(Group::class, '_test_group_2', true);
        $computer = $this->createItem(Computer::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
            'groups_id' => $groups_id_1,
            'groups_id_tech' => $groups_id_2,
        ], ['groups_id', 'groups_id_tech']);

        $common_criteria = [
            'SELECT' => ['glpi_computers.id AS computers_id'],
            'FROM' => Computer::getTable(),
        ];
        $this->assertContains(
            $computer->getID(),
            array_column(
                iterator_to_array(
                    $DB->request($common_criteria + GroupRequesterFilter::getCriteria('glpi_computers', $groups_id_1))
                ),
                'computers_id'
            )
        );
        $this->assertContains(
            $computer->getID(),
            array_column(
                iterator_to_array(
                    $DB->request($common_criteria + GroupTechFilter::getCriteria('glpi_computers', $groups_id_2))
                ),
                'computers_id'
            )
        );
    }

    public function testITILGroupFiltersWithMultipleGroups()
    {
        /** @var \DBmysql */
        global $DB;

        $groups_id_1 = getItemByTypeName(Group::class, '_test_group_1', true);
        $groups_id_2 = getItemByTypeName(Group::class, '_test_group_2', true);

        $ticket_1 = $this->createItem(Ticket::class, [
            'name' => __FUNCTION__ . '_1',
            'content' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
            '_groups_id_requester' => $groups_id_1,
        ]);
        $ticket_2 = $this->createItem(Ticket::class, [
            'name' => __FUNCTION__ . '_2',
            'content' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
            '_groups_id_requester' => $groups_id_2,
        ]);

        $common_criteria = [
            'SELECT' => ['glpi_tickets.id AS tickets_id'],
            'FROM' => Ticket::getTable(),
        ];
        $found = array_column(
            iterator_to_array(
                $DB->request(
                    $common_criteria
                    + GroupRequesterFilter::getCriteria('glpi_tickets', [$groups_id_1, $groups_id_2])
                )
            ),
            'tickets_id'
        );
        $this->assertContains($ticket_1->getID(), $found);
        $this->assertContains($ticket_2->getID(), $found);
    }

    public function testAssetGroupFiltersWithMultipleGroups()
    {
        /** @var \DBmysql */
        global $DB;

        $groups_id_1 = getItemByTypeName(Group::class, '_test_group_1', true);
        $groups_id_2 = getItemByTypeName(Group::class, '_test_group_2', true);

        $computer_1 = $this->createItem(Computer::class, [
            'name' => __FUNCTION__ . '_1',
            'entities_id' => $this->getTestRootEntity(true),
            'groups_id' => $groups_id_1,
        ], ['groups_id']);
        $computer_2 = $this->createItem(Computer::class, [
            'name' => __FUNCTION__ . '_2',
            'entities_id' => $this->getTestRootEntity(true),
            'groups_id' => $groups_id_2,
        ], ['groups_id']);

        $common_criteria = [
            'SELECT' => ['glpi_computers.id AS computers_id'],
            'FROM' => Computer::getTable(),
        ];
        $found = array_column(
            iterator_to_array(
                $DB->request(
                    $common_criteria
                    + GroupRequesterFilter::getCriteria('glpi_computers', [$groups_id_1, $groups_id_2])
                )
            ),
            'computers_id'
        );
        $this->assertContains($computer_1->getID(), $found);
        $this->assertContains($computer_2->getID(), $found);
    }

    public function testGetCriteriaEmptyValues()
    {
        $this->assertSame([], GroupRequesterFilter::getCriteria('glpi_tickets', []));
        $this->assertSame([], GroupRequesterFilter::getCriteria('glpi_tickets', 0));
        $this->assertSame([], GroupRequesterFilter::getCriteria('glpi_tickets', ''));
        $this->assertSame([], GroupRequesterFilter::getCriteria('glpi_computers', [0, '']));
    }

    public function testGetSearchCriteriaSingleAndMultiple()
    {
        $groups_id_1 = getItemByTypeName(Group::class, '_test_group_1', true);
        $groups_id_2 = getItemByTypeName(Group::class, '_test_group_2', true);

        $single = GroupRequesterFilter::getSearchCriteria('glpi_tickets', $groups_id_1);
        $this->assertCount(1, $single);
        $this->assertSame($groups_id_1, $single[0]['value']);
        $this->assertSame('equals', $single[0]['searchtype']);
        $this->assertArrayNotHasKey('criteria', $single[0]);

        $single_from_array = GroupRequesterFilter::getSearchCriteria('glpi_tickets', [$groups_id_1]);
        $this->assertCount(1, $single_from_array);
        $this->assertSame($groups_id_1, $single_from_array[0]['value']);
        $this->assertArrayNotHasKey('criteria', $single_from_array[0]);

        $multi = GroupRequesterFilter::getSearchCriteria('glpi_tickets', [$groups_id_1, $groups_id_2]);
        $this->assertCount(1, $multi);
        $this->assertArrayHasKey('criteria', $multi[0]);
        $this->assertCount(2, $multi[0]['criteria']);
        $this->assertSame('AND', $multi[0]['criteria'][0]['link']);
        $this->assertSame($groups_id_1, $multi[0]['criteria'][0]['value']);
        $this->assertSame('OR', $multi[0]['criteria'][1]['link']);
        $this->assertSame($groups_id_2, $multi[0]['criteria'][1]['value']);
    }

    public function testGetSearchCriteriaEmptyValues()
    {
        $this->assertSame([], GroupRequesterFilter::getSearchCriteria('glpi_tickets', []));
        $this->assertSame([], GroupRequesterFilter::getSearchCriteria('glpi_tickets', 0));
        $this->assertSame([], GroupRequesterFilter::getSearchCriteria('glpi_tickets', ''));
    }
}
