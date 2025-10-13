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

use DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class StatTest extends DbTestCase
{
    public static function constructEntryValuesProvider(): iterable
    {
        $params = [
            'technician',
            'technician_followup',
            'user',
            'usertitles_id',
            'usercategories_id',
            'itilcategories_tree',
            'locations_tree',
            'group_tree',
            'groups_tree_assign',
            'group',
            'groups_id_assign',
            'suppliers_id_assign',
            'requesttypes_id',
            'urgency',
            'impact',
            'priority',
            'users_id_recipient',
            'type',
            'itilcategories_id',
            'locations_id',
            'solutiontypes_id',
            'device',
            'comp_champ',
        ];

        $types = [
            'inter_total',
            'inter_solved',
            'inter_solved_with_actiontime',
            'inter_closed',
            'inter_solved_late',
            'inter_opensatisfaction',
            'inter_answersatisfaction',
        ];

        foreach ($params as $param) {
            foreach ($types as $type) {
                yield [
                    'type' => $type,
                    'param' => $param,
                    'expected' => [
                        '2023-01' => 0,
                        '2023-02' => 0,
                        '2023-03' => 0,
                        '2023-04' => 0,
                        '2023-05' => 0,
                        '2023-06' => 1, // Just one ticket in June
                        '2023-07' => 0,
                        '2023-08' => 0,
                        '2023-09' => 0,
                        '2023-10' => 0,
                        '2023-11' => 0,
                        '2023-12' => 0,
                    ],
                ];
            }
        }

        $types_avg = [
            'inter_avgsolvedtime',
            'inter_avgclosedtime',
            'inter_avgactiontime',
            'inter_avgtakeaccount',
        ];

        foreach ($params as $param) {
            foreach ($types_avg as $type_avg) {
                yield [
                    'type' => $type_avg,
                    'param' => $param,
                    'expected' => [
                        '2023-01' => 0,
                        '2023-02' => 0,
                        '2023-03' => 0,
                        '2023-04' => 0,
                        '2023-05' => 0,
                        '2023-06' => '18000.0000', // 5 hours in seconds (15:00 - 20:00)
                        '2023-07' => 0,
                        '2023-08' => 0,
                        '2023-09' => 0,
                        '2023-10' => 0,
                        '2023-11' => 0,
                        '2023-12' => 0,
                    ],
                ];
            }
        }

        foreach ($params as $param) {
            yield [
                'type' => 'inter_avgsatisfaction',
                'param' => $param,
                'expected' => [
                    '2023-01' => 0,
                    '2023-02' => 0,
                    '2023-03' => 0,
                    '2023-04' => 0,
                    '2023-05' => 0,
                    '2023-06' => 1.0,
                    '2023-07' => 0,
                    '2023-08' => 0,
                    '2023-09' => 0,
                    '2023-10' => 0,
                    '2023-11' => 0,
                    '2023-12' => 0,
                ],
            ];
        }
    }

    /**
     * Test constructEntryValues method with all combinations of param and type
     */
    #[DataProvider('constructEntryValuesProvider')]
    public function testConstructEntryValues($type, $param, $expected)
    {
        $this->login('glpi', 'glpi');

        $date = "2023-06-15 15:00:00";
        $_SESSION['glpi_currenttime'] = $date;

        $itemtype = 'Ticket';
        $begin = '2023-01-01';
        $end = '2023-12-31';
        $value = getItemByTypeName(\User::class, 'tech', true);
        $value2 = '';
        $add_criteria = [];

        // Handle parameter-specific value setup
        switch ($param) {
            // Set correct value2 for specific params that require it
            case 'device':
                $value2 = 'DeviceSoundCard';
                break;
            case 'comp_champ':
                $value2 = 'OperatingSystem';
                break;
            case 'usertitles_id':
                // Create a user title
                $title = $this->createItem(\UserTitle::class, [
                    'name' => 'Test Title ' . uniqid(),
                ]);
                $value = $title->getID(); // Use the title ID, not the user ID
                break;

            case 'usercategories_id':
                // Create a user category
                $usercat = $this->createItem(\UserCategory::class, [
                    'name' => 'Test User Category ' . uniqid(),
                ]);
                $value = $usercat->getID(); // Use the category ID, not the user ID
                break;

            case 'locations_tree':
                // Create a location
                $location = $this->createItem(\Location::class, [
                    'name' => 'Test Location ' . uniqid(),
                    'entities_id' => $_SESSION['glpiactive_entity'] ?? 0,
                ]);
                $value = $location->getID(); // Use the location ID
                break;

            case 'group_tree':
            case 'groups_tree_assign':
                // Create a group
                $group = $this->createItem(\Group::class, [
                    'name' => 'Test Group ' . uniqid(),
                    'entities_id' => $_SESSION['glpiactive_entity'] ?? 0,
                ]);
                $value = $group->getID(); // Use the group ID
                break;

            case 'suppliers_id_assign':
                // Create a supplier
                $supplier = $this->createItem(\Supplier::class, [
                    'name' => 'Test Supplier ' . uniqid(),
                    'entities_id' => $_SESSION['glpiactive_entity'] ?? 0,
                ]);
                $value = $supplier->getID(); // Use the supplier ID
                break;

            case 'requesttypes_id':
                $value = 1; // Web form - this should match the requesttypes_id set in createTestDataForStatistics
                break;

            case 'urgency':
                $value = 4; // High urgency - this should match the urgency set in createTestDataForStatistics
                break;

            case 'type':
                $value = \Ticket::INCIDENT_TYPE; // This should match the type set in createTestDataForStatistics
                break;

            case 'solutiontypes_id':
                // Create a solution type
                $soltype = $this->createItem(\SolutionType::class, [
                    'name' => 'Test Solution Type',
                ]);
                $value = $soltype->getID(); // Use the solution type ID
                break;
        }

        // Create test data based on expected values
        $ticket = $this->createTestDataForStatistics($param, $value);

        // Handle different statistic types - tickets need different statuses and dates
        switch ($type) {
            case 'inter_total':
                // Default behavior - ticket created, no special status needed
                break;

            case 'inter_solved':
            case 'inter_solved_with_actiontime':
                // Mark ticket as solved
                $this->updateItem(\Ticket::class, $ticket->getID(), [
                    'status' => \CommonITILObject::SOLVED,
                ]);
                break;

            case 'inter_avgsolvedtime':
            case 'inter_avgtakeaccount':
            case 'inter_solved_late':
            case 'inter_avgactiontime':
                $_SESSION['glpi_currenttime'] = date(
                    "Y-m-d H:i:s",
                    strtotime($date . " +5 hours")
                );
                // For average time calculations, we need solved tickets with specific time differences
                $this->updateItem(\Ticket::class, $ticket->getID(), [
                    'status' => \CommonITILObject::SOLVED,
                    'solvedate' => $_SESSION['glpi_currenttime'],
                ]);
                break;

            case 'inter_avgclosedtime':
                $_SESSION['glpi_currenttime'] = date(
                    "Y-m-d H:i:s",
                    strtotime($date . " +5 hours")
                );
                // For average closed time, we need closed tickets with closedate
                $this->updateItem(\Ticket::class, $ticket->getID(), [
                    'status' => \CommonITILObject::CLOSED,
                    'closedate' => $_SESSION['glpi_currenttime'],
                ]);
                break;

            case 'inter_closed':
                // Mark ticket as closed
                $this->updateItem(\Ticket::class, $ticket->getID(), [
                    'status' => \CommonITILObject::CLOSED,
                    'closedate' => $_SESSION['glpi_currenttime'],
                ]);
                break;

            case 'inter_opensatisfaction':
            case 'inter_answersatisfaction':
            case 'inter_avgsatisfaction':
                // Enable satisfaction surveys in entity
                $this->updateItem(\Entity::class, 0, [
                    'inquest_config' => 1, // Enable satisfaction surveys
                    'inquest_rate' => 100, // 100% rate
                    'inquest_delay' => 0, // Immediate
                    'inquest_duration' => 30, // 30 days duration
                ]);

                // Satisfaction surveys need CLOSED tickets (not just solved)
                $this->updateItem(\Ticket::class, $ticket->getID(), [
                    'status' => \CommonITILObject::CLOSED,
                ]);

                $satisfaction = new \TicketSatisfaction();
                $satisfaction->getFromDBByCrit(
                    ['tickets_id' => $ticket->getID()]
                );
                $this->assertNotFalse($satisfaction);

                // For answered satisfaction surveys, add answer data
                if (in_array($type, ['inter_answersatisfaction', 'inter_avgsatisfaction'])) {
                    $satisfaction->update([
                        'id' => $satisfaction->getID(),
                        'satisfaction' => 1,
                        'tickets_id' => $ticket->getID(),
                    ]);
                }
                break;
        }

        // For device and comp_champ tests, we need a computer
        if (in_array($param, ['device', 'comp_champ'])) {
            $computer = $this->createItem(\Computer::class, [
                'name' => 'Test computer',
                'entities_id' => 0,
                'is_template' => 0,
            ]);
            $computers_id = $computer->getId();

            // Link ticket to computer
            $this->createItem(\Item_Ticket::class, [
                'itemtype' => 'Computer',
                'items_id' => $computers_id,
                'tickets_id' => $ticket->getID(),
            ]);

            // For device test, create a sound card device and link it
            if ($param === 'device') {
                $soundcard = $this->createItem(\DeviceSoundCard::class, [
                    'designation' => 'Test SoundCard',
                    'entities_id' => 0,
                ]);
                $soundcard_id = $soundcard->getId();

                $this->createItem(\Item_DeviceSoundCard::class, [
                    'itemtype' => 'Computer',
                    'items_id' => $computers_id,
                    'devicesoundcards_id' => $soundcard_id,
                ]);

                // Update value to match the created device
                $value = $soundcard_id;
            }

            // For comp_champ test, create an operating system and link it
            if ($param === 'comp_champ') {
                $os = $this->createItem(\OperatingSystem::class, [
                    'name' => 'Test OS',
                ]);
                $os_id = $os->getId();

                $this->createItem(\Item_OperatingSystem::class, [
                    'itemtype' => 'Computer',
                    'items_id' => $computers_id,
                    'operatingsystems_id' => $os_id,
                ]);

                // Update value to match the created OS
                $value = $os_id;
            }
        }

        // For group-related tests, assign groups and use a consistent ID
        if (strpos($param, 'group') !== false && $ticket->getID()) {
            $value = $this->createTestGroup($ticket->getID(), $param);
        }

        // For supplier tests, assign suppliers and get the ID
        if ($param === 'suppliers_id_assign' && $ticket->getID()) {
            $value = $this->createTestSupplier($ticket->getID());
        }

        // For category tests, assign category and get the ID
        if (strpos($param, 'itilcategories') !== false && $ticket->getID()) {
            $value = $this->updateTicketCategory($ticket->getID());
            // Assign category to a ticket
            $this->updateItem(\Ticket::class, $ticket->getID(), [
                'itilcategories_id' => $value,
            ]);
        }

        // For location tests, assign location and get the ID
        if (strpos($param, 'locations') !== false && $ticket->getID()) {
            $value = $this->addTicketLocation($ticket->getID());
            // Assign location to a ticket
            $this->updateItem(\Ticket::class, $ticket->getID(), [
                'locations_id' => $value,
            ]);
        }

        // Call the method under test
        $result = \Stat::constructEntryValues(
            $itemtype,
            $type,
            $begin,
            $end,
            $param,
            $value,
            $value2,
            $add_criteria
        );

        // Instead of exact comparison, just verify the structure matches expected keys
        $this->assertSame($expected, $result);
    }

    /**
     * Create test data for statistics
     * @param string $param Parameter type
     * @param array $expected Expected values
     * @param mixed $value Parameter value
     * @param mixed $value2 Secondary parameter value
     * @return \Ticket $ticket
     */
    private function createTestDataForStatistics($param, $value)
    {
        $ticketData = [
            'name' => "Test ticket",
            'content' => 'Test content for statistics',
            'entities_id' => $_SESSION['glpiactive_entity'],
            'date' => $_SESSION['glpi_currenttime'],
            'actiontime' => 18000, // 5 hours actiontime
            'priority' => 3,
            'urgency' => 3,
            'impact' => 3,
            'takeintoaccount_delay_stat' => 18000, // 5 hours delay
            'time_to_resolve' => date("Y-m-d H:i:s", strtotime($_SESSION['glpi_currenttime'] . " +1 hour")),
        ];

        // Set specific data based on parameter type
        switch ($param) {
            case 'requesttypes_id':
                $ticketData['requesttypes_id'] = 1; // Web form
                break;
            case 'urgency':
                $ticketData['urgency'] = 4; // High urgency
                break;
            case 'impact':
                $ticketData['impact'] = 4; // High impact
                break;
            case 'priority':
                $ticketData['priority'] = 4; // High priority
                break;
            case 'type':
                $ticketData['type'] = \Ticket::INCIDENT_TYPE;
                break;
        }

        $ticket = $this->createItem(\Ticket::class, $ticketData);

        // Create and assign appropriate entities based on parameter type
        $this->assignTestDataToTicket($ticket->getID(), $param, $value);

        return $ticket;
    }

    /**
     * Assign test data to ticket based on parameter type
     */
    private function assignTestDataToTicket($tickets_id, $param, $value)
    {
        switch ($param) {
            case 'technician':
            case 'technician_followup':
                $this->assignExistingUserToTicket($tickets_id, $param, $value);
                break;

            case 'user':
            case 'users_id_recipient':
                $this->assignExistingUserToTicket($tickets_id, $param, $value);
                break;

            case 'usertitles_id':
                // The $value here is actually the title ID, so create user with this title
                $titled_user = $this->createItem(\User::class, [
                    'name' => 'test_titled_user',
                    'usertitles_id' => $value, // $value is the title ID
                    'entities_id' => $_SESSION['glpiactive_entity'],
                ]);
                $this->assignExistingUserToTicket($tickets_id, 'user', $titled_user->getID());
                break;

            case 'usercategories_id':
                // The $value here is actually the category ID, so create user with this category
                $cat_user = $this->createItem(\User::class, [
                    'name' => 'test_cat_user',
                    'usercategories_id' => $value, // $value is the category ID
                    'entities_id' => $_SESSION['glpiactive_entity'],
                ]);
                $this->assignExistingUserToTicket($tickets_id, 'user', $cat_user->getID());
                break;

            case 'group':
            case 'groups_id_assign':
                $this->createTestGroup($tickets_id, $param);
                break;

            case 'group_tree':
            case 'groups_tree_assign':
                // The $value here is already the group ID, so just assign it to the ticket
                if (strpos($param, 'assign') !== false) {
                    $type = \CommonITILActor::ASSIGN;
                } else {
                    $type = \CommonITILActor::REQUESTER;
                }

                $this->createItem(\Group_Ticket::class, [
                    'tickets_id' => $tickets_id,
                    'groups_id' => $value, // $value is the group ID
                    'type' => $type,
                ]);
                break;

            case 'suppliers_id_assign':
                $this->createTestSupplier($tickets_id);
                break;

            case 'itilcategories_id':
            case 'itilcategories_tree':
                $this->updateTicketCategory($tickets_id);
                break;

            case 'locations_id':
            case 'locations_tree':
                // The $value here is already the location ID, so just assign it to the ticket
                $this->updateItem(\Ticket::class, $tickets_id, [
                    'locations_id' => $value,
                ]);
                break;

            case 'solutiontypes_id':
                // The $value here is already the solution type ID, so use it directly
                $this->createItem(\ITILSolution::class, [
                    'itemtype' => \Ticket::class,
                    'items_id' => $tickets_id,
                    'content' => 'Test solution',
                    'solutiontypes_id' => $value, // Use the existing solution type ID
                ]);
                break;

            case 'device':
            case 'comp_champ':
                // For device-related params, just ensure basic ticket structure
                break;

            default:
                // For other params, no special assignment needed
                break;
        }
    }

    /**
     * Helper method to assign existing user to ticket
     */
    private function assignExistingUserToTicket($tickets_id, $param, $users_id)
    {
        // Handle users_id_recipient separately as it's a direct field update
        if ($param === 'users_id_recipient') {
            $this->updateItem(\Ticket::class, $tickets_id, [
                'users_id_recipient' => $users_id,
            ]);
            return;
        }

        // Assign user to ticket based on param type
        if ($param === 'technician' || $param === 'technician_followup') {
            $type = \CommonITILActor::ASSIGN;
        } else {
            $type = \CommonITILActor::REQUESTER;
        }

        $this->createItem(\Ticket_User::class, [
            'tickets_id' => $tickets_id,
            'users_id' => $users_id,
            'type' => $type,
        ]);

        // For technician_followup, create a task
        if ($param === 'technician_followup') {
            $this->createItem(\TicketTask::class, [
                'tickets_id' => $tickets_id,
                'users_id' => $users_id,
                'users_id_tech' => $users_id,
                'content' => 'Test task',
                'actiontime' => 18000, // 5 hours
            ]);
        }
    }

    /**
     * Helper method to create test groups and assign them to tickets
     * @return int The ID of the created group
     */
    private function createTestGroup($tickets_id, $param)
    {
        $group = $this->createItem(\Group::class, [
            'name' => 'testgroup_' . $param,
            'entities_id' => $_SESSION['glpiactive_entity'],
        ]);

        if (!$group || !$group->getID()) {
            return false;
        }

        $groups_id = $group->getID();

        if (strpos($param, 'assign') !== false) {
            $type = \CommonITILActor::ASSIGN;
        } else {
            $type = \CommonITILActor::REQUESTER;
        }

        $this->createItem(\Group_Ticket::class, [
            'tickets_id' => $tickets_id,
            'groups_id' => $groups_id,
            'type' => $type,
        ]);

        return $groups_id;
    }

    /**
     * Helper method to create test suppliers and assign them to tickets
     * @return int The ID of the created supplier
     */
    private function createTestSupplier($tickets_id)
    {
        $supplier = $this->createItem(\Supplier::class, [
            'name' => 'testsupplier',
            'entities_id' => $_SESSION['glpiactive_entity'] ?? 0,
        ]);

        if (!$supplier || !$supplier->getID()) {
            return false;
        }

        $suppliers_id = $supplier->getID();

        $this->createItem(\Supplier_Ticket::class, [
            'tickets_id' => $tickets_id,
            'suppliers_id' => $suppliers_id,
            'type' => \CommonITILActor::ASSIGN,
        ]);

        return $suppliers_id;
    }

    /**
     * Helper method to update ticket category
     * @return int The ID of the created category
     */
    private function updateTicketCategory($tickets_id)
    {
        $category = $this->createItem(\ITILCategory::class, [
            'name' => 'Test category',
            'entities_id' => $_SESSION['glpiactive_entity'],
        ]);

        if (!$category || !$category->getID()) {
            return false;
        }

        $categories_id = $category->getID();

        $this->updateItem(\Ticket::class, $tickets_id, [
            'itilcategories_id' => $categories_id,
        ]);

        return $categories_id;
    }

    /**
     * Helper method to add ticket location
     * @return int The ID of the created location
     */
    private function addTicketLocation($tickets_id)
    {
        $location = $this->createItem(\Location::class, [
            'name' => 'Test location',
            'entities_id' => $_SESSION['glpiactive_entity'] ?? 0,
        ]);

        if (!$location || !$location->getID()) {
            return false;
        }

        $locations_id = $location->getID();

        $this->updateItem(\Ticket::class, $tickets_id, [
            'locations_id' => $locations_id,
        ]);

        return $locations_id;
    }
}
