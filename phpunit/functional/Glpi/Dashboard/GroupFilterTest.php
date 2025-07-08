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

namespace tests\units\Glpi\Dashboard;

use Computer;
use DbTestCase;
use Glpi\Dashboard\Filters\GroupRequesterFilter;
use Glpi\Dashboard\Filters\GroupTechFilter;
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
}
