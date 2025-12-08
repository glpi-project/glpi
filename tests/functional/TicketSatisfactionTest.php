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

use CommonITILObject;
use CommonITILSatisfaction;
use Entity;
use Glpi\Tests\CommonITILSatisfactionTest;
use Search;
use Ticket;
use TicketSatisfaction;

class TicketSatisfactionTest extends CommonITILSatisfactionTest
{
    /**
     * Test that satisfaction survey end date correctly uses parent entity configuration
     * when child entity is set to inherit (inquest_config = -2).
     *
     * This validates that the recursive CTE query properly resolves the inquest_duration
     * from the parent entity when a child entity inherits its configuration.
     */
    public function testSatisfactionSurveyInheritsParentEntityConfig()
    {
        $this->login('glpi', 'glpi');

        $entity_root_id = getItemByTypeName('Entity', '_test_root_entity', true);
        $user_id = $_SESSION['glpiID'];

        // Configure root entity with satisfaction survey enabled and specific duration
        $this->updateItem(Entity::class, $entity_root_id, [
            'inquest_config'   => 1, // Local configuration
            'inquest_duration' => 7, // 7 days duration
        ]);

        // Create child entity that explicitly inherits from parent (inquest_config = -2)
        $child_entity = $this->createItem(Entity::class, [
            'name'           => __FUNCTION__ . '_child_inherit',
            'entities_id'    => $entity_root_id,
            'inquest_config' => -2, // Inherit from parent
        ]);
        $child_entity_id = $child_entity->getID();

        // Create grandchild entity that also inherits (should get config from root)
        $grandchild_entity = $this->createItem(Entity::class, [
            'name'           => __FUNCTION__ . '_grandchild_inherit',
            'entities_id'    => $child_entity_id,
            'inquest_config' => -2, // Inherit from parent (which inherits from root)
        ]);
        $grandchild_entity_id = $grandchild_entity->getID();

        // Create another child with its own local configuration
        $child_local_entity = $this->createItem(Entity::class, [
            'name'             => __FUNCTION__ . '_child_local',
            'entities_id'      => $entity_root_id,
            'inquest_config'   => 1, // Local configuration
            'inquest_duration' => 3, // Different duration
        ]);
        $child_local_entity_id = $child_local_entity->getID();

        // Create closed tickets in each entity
        $tickets = $this->createItems(Ticket::class, [
            [
                'entities_id' => $entity_root_id,
                'name' => __FUNCTION__ . ' - root entity',
                'content' => 'Ticket in root entity',
                'solvedate' => $_SESSION['glpi_currenttime'],
                'status' => CommonITILObject::CLOSED,
                'users_id_recipient' => $user_id,
            ],
            [
                'entities_id' => $child_entity_id,
                'name' => __FUNCTION__ . ' - child inherit',
                'content' => 'Ticket in child entity with inherited config',
                'solvedate' => $_SESSION['glpi_currenttime'],
                'status' => CommonITILObject::CLOSED,
                'users_id_recipient' => $user_id,
            ],
            [
                'entities_id' => $grandchild_entity_id,
                'name' => __FUNCTION__ . ' - grandchild inherit',
                'content' => 'Ticket in grandchild entity with inherited config',
                'solvedate' => $_SESSION['glpi_currenttime'],
                'status' => CommonITILObject::CLOSED,
                'users_id_recipient' => $user_id,
            ],
            [
                'entities_id' => $child_local_entity_id,
                'name' => __FUNCTION__ . ' - child local config',
                'content' => 'Ticket in child entity with local config',
                'solvedate' => $_SESSION['glpi_currenttime'],
                'status' => CommonITILObject::CLOSED,
                'users_id_recipient' => $user_id,
            ],
        ]);

        // Add satisfaction surveys for each ticket
        $this->createItems(TicketSatisfaction::class, [
            [
                'tickets_id' => $tickets[0]->getID(),
                'type' => CommonITILSatisfaction::TYPE_INTERNAL,
                'date_begin' => $_SESSION['glpi_currenttime'],
            ],
            [
                'tickets_id' => $tickets[1]->getID(),
                'type' => CommonITILSatisfaction::TYPE_INTERNAL,
                'date_begin' => $_SESSION['glpi_currenttime'],
            ],
            [
                'tickets_id' => $tickets[2]->getID(),
                'type' => CommonITILSatisfaction::TYPE_INTERNAL,
                'date_begin' => $_SESSION['glpi_currenttime'],
            ],
            [
                'tickets_id' => $tickets[3]->getID(),
                'type' => CommonITILSatisfaction::TYPE_INTERNAL,
                'date_begin' => $_SESSION['glpi_currenttime'],
            ],
        ]);

        // Search for tickets with satisfaction end date
        $search_params = [
            'is_deleted' => 0,
            'start' => 0,
            'criteria' => [
                [
                    'field' => 1, // name
                    'searchtype' => 'contains',
                    'value' => __FUNCTION__,
                ],
                [
                    'field' => 75, // satisfaction end date
                    'searchtype' => 'contains',
                    'value' => '',
                ],
            ],
            'order' => 1,
        ];

        $search_params = Search::manageParams(Ticket::class, $search_params);
        $data = Search::getDatas(Ticket::class, $search_params);

        // Build results array indexed by ticket ID
        $results = [];
        foreach ($data['data']['rows'] as $row) {
            $results[$row['raw']['ITEM_Ticket_2']] = $row['raw']['ITEM_Ticket_75'];
        }

        $expected_root_end_date = date('Y-m-d H:i:s', strtotime('+7 days', strtotime($_SESSION['glpi_currenttime'])));
        $expected_local_end_date = date('Y-m-d H:i:s', strtotime('+3 days', strtotime($_SESSION['glpi_currenttime'])));

        // Root entity ticket: should use root's 7 days duration
        $this->assertEquals(
            $expected_root_end_date,
            $results[$tickets[0]->getID()],
            'Root entity ticket should have 7 days duration'
        );

        // Child entity with inherited config: should inherit root's 7 days duration
        $this->assertEquals(
            $expected_root_end_date,
            $results[$tickets[1]->getID()],
            'Child entity (inherit) ticket should inherit parent\'s 7 days duration'
        );

        // Grandchild entity with inherited config: should also inherit root's 7 days duration
        $this->assertEquals(
            $expected_root_end_date,
            $results[$tickets[2]->getID()],
            'Grandchild entity (inherit) ticket should inherit root\'s 7 days duration through chain'
        );

        // Child entity with local config: should use its own 3 days duration
        $this->assertEquals(
            $expected_local_end_date,
            $results[$tickets[3]->getID()],
            'Child entity (local config) ticket should use its own 3 days duration'
        );
    }
}
