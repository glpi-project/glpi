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

use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/*
 * Test for src/CommonITILObject_CommonITILObject.php
 * */
class CommonITILObject_CommonITILObjectTest extends DbTestCase
{
    public function testCountAllLinks()
    {
        // Create a Ticket
        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name' => 'test',
            'content' => 'test',
            'status' => \Ticket::INCOMING,
        ]);
        $this->assertGreaterThan(0, $tickets_id);

        // Create a Change
        $change = new \Change();
        $changes_id = $change->add([
            'name' => 'test',
            'content' => 'test',
            'status' => \Change::INCOMING,
        ]);
        $this->assertGreaterThan(0, $changes_id);

        // Link the Ticket to the Change
        $itil_itil = new \Change_Ticket();
        $itil_itil_id = $itil_itil->add([
            'tickets_id' => $tickets_id,
            'changes_id' => $changes_id,
            'link' => \CommonITILObject_CommonITILObject::LINK_TO,
        ]);
        $this->assertGreaterThan(0, $itil_itil_id);

        // Check the number of links (bypass rights check as we don't test rights here)
        $this->assertEquals(1, \CommonITILObject_CommonITILObject::countAllLinks('Ticket', $tickets_id, true));

        // Create a Problem
        $problem = new \Problem();
        $problems_id = $problem->add([
            'name' => 'test',
            'content' => 'test',
            'status' => \Problem::INCOMING,
        ]);
        $this->assertGreaterThan(0, $problems_id);

        // Link the Ticket to the Problem
        $itil_itil = new \Problem_Ticket();
        $itil_itil_id = $itil_itil->add([
            'tickets_id' => $tickets_id,
            'problems_id' => $problems_id,
            'link' => \CommonITILObject_CommonITILObject::DUPLICATE_WITH,
        ]);
        $this->assertGreaterThan(0, $itil_itil_id);

        $this->assertEquals(2, \CommonITILObject_CommonITILObject::countAllLinks('Ticket', $tickets_id, true));

        // Add another ticket
        $tickets_id2 = $ticket->add([
            'name' => 'test2',
            'content' => 'test2',
            'status' => \Ticket::INCOMING,
        ]);
        $this->assertGreaterThan(0, $tickets_id2);

        // Link the second Ticket to the original ticket
        $ticket_ticket = new \Ticket_Ticket();
        $ticket_ticket_id = $ticket_ticket->add([
            'tickets_id_1' => $tickets_id2,
            'tickets_id_2' => $tickets_id,
            'link' => \CommonITILObject_CommonITILObject::LINK_TO,
        ]);
        $this->assertGreaterThan(0, $ticket_ticket_id);

        // Count links for both tickets (bypass rights check as we don't test rights here)
        $this->assertEquals(3, \CommonITILObject_CommonITILObject::countAllLinks('Ticket', $tickets_id, true));
        $this->assertEquals(1, \CommonITILObject_CommonITILObject::countAllLinks('Ticket', $tickets_id2, true));
    }

    public function testCountLinksByStatus()
    {
        $this->login(); // Required to be able to update a Change

        // Create a Ticket
        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name' => 'test',
            'content' => 'test',
            'status' => \Ticket::INCOMING,
        ]);
        $this->assertGreaterThan(0, $tickets_id);

        // Create a Change
        $change = new \Change();
        $changes_id = $change->add([
            'name' => 'test',
            'content' => 'test',
            'status' => \Change::INCOMING,
        ]);
        $this->assertGreaterThan(0, $changes_id);

        // Link the Ticket to the Change
        $itil_itil = new \Change_Ticket();
        $itil_itil_id = $itil_itil->add([
            'tickets_id' => $tickets_id,
            'changes_id' => $changes_id,
            'link' => \CommonITILObject_CommonITILObject::LINK_TO,
        ]);
        $this->assertGreaterThan(0, $itil_itil_id);

        // Check the number of links by status
        $this->assertEquals(1, \Change_Ticket::countLinksByStatus('Ticket', $tickets_id, [\Change::INCOMING]));
        $this->assertEquals(1, \Change_Ticket::countLinksByStatus('Change', $changes_id, [\Ticket::INCOMING]));

        // Create a Problem
        $problem = new \Problem();
        $problems_id = $problem->add([
            'name' => 'test',
            'content' => 'test',
            'status' => \Problem::INCOMING,
        ]);
        $this->assertGreaterThan(0, $problems_id);

        // Link the Ticket to the Problem
        $itil_itil = new \Problem_Ticket();
        $itil_itil_id = $itil_itil->add([
            'tickets_id' => $tickets_id,
            'problems_id' => $problems_id,
            'link' => \CommonITILObject_CommonITILObject::DUPLICATE_WITH,
        ]);
        $this->assertGreaterThan(0, $itil_itil_id);

        $this->assertEquals(1, \Problem_Ticket::countLinksByStatus('Ticket', $tickets_id, [\Problem::INCOMING]));
        $this->assertEquals(1, \Change_Ticket::countLinksByStatus('Change', $changes_id, [\Ticket::INCOMING]));
        $this->assertEquals(1, \Problem_Ticket::countLinksByStatus('Problem', $problems_id, [\Ticket::INCOMING]));
        $this->assertEquals(0, \Problem_Ticket::countLinksByStatus('Problem', $problems_id, [\Ticket::INCOMING], [\CommonITILObject_CommonITILObject::LINK_TO]));
        $this->assertEquals(1, \Problem_Ticket::countLinksByStatus('Problem', $problems_id, [\Ticket::INCOMING], [\CommonITILObject_CommonITILObject::DUPLICATE_WITH]));

        // Update Change status
        $this->assertTrue($change->update([
            'id' => $changes_id,
            'status' => \Change::PLANNED,
        ]));

        $this->assertEquals(1, \Problem_Ticket::countLinksByStatus('Ticket', $tickets_id, [\Problem::INCOMING]));
        $this->assertEquals(0, \Change_Ticket::countLinksByStatus('Ticket', $tickets_id, [\Change::INCOMING]));
        $this->assertEquals(1, \Change_Ticket::countLinksByStatus('Ticket', $tickets_id, [\Change::PLANNED]));
        $this->assertEquals(1, \Problem_Ticket::countLinksByStatus('Ticket', $tickets_id, [\Problem::INCOMING]));
    }

    public function testGetLinkedTo()
    {
        // Create a Ticket
        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name' => 'test',
            'content' => 'test',
            'status' => \Ticket::INCOMING,
        ]);
        $this->assertGreaterThan(0, $tickets_id);

        // Create a Change
        $change = new \Change();
        $changes_id = $change->add([
            'name' => 'test',
            'content' => 'test',
            'status' => \Change::INCOMING,
        ]);
        $this->assertGreaterThan(0, $changes_id);

        // Link the Ticket to the Change
        $itil_itil = new \Change_Ticket();
        $itil_itil_id = $itil_itil->add([
            'tickets_id' => $tickets_id,
            'changes_id' => $changes_id,
            'link' => \CommonITILObject_CommonITILObject::LINK_TO,
        ]);
        $this->assertGreaterThan(0, $itil_itil_id);

        // Create a Problem
        $problem = new \Problem();
        $problems_id = $problem->add([
            'name' => 'test',
            'content' => 'test',
            'status' => \Problem::INCOMING,
        ]);
        $this->assertGreaterThan(0, $changes_id);

        // Link the Ticket to the Problem
        $itil_itil = new \Problem_Ticket();
        $itil_itil_id = $itil_itil->add([
            'tickets_id' => $tickets_id,
            'problems_id' => $problems_id,
            'link' => \CommonITILObject_CommonITILObject::LINK_TO,
        ]);
        $this->assertGreaterThan(0, $itil_itil_id);

        // Bypass rights check as we don't test rights here
        $this->assertCount(0, \Ticket_Ticket::getLinkedTo('Ticket', $tickets_id, true));
        $this->assertCount(1, \Change_Ticket::getLinkedTo('Ticket', $tickets_id, true));
        $this->assertCount(1, \Problem_Ticket::getLinkedTo('Ticket', $tickets_id, true));
    }

    public function testGetAllLinkedTo()
    {
        // Create a Ticket
        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name' => 'test',
            'content' => 'test',
            'status' => \Ticket::INCOMING,
        ]);
        $this->assertGreaterThan(0, $tickets_id);

        // Create a Change
        $change = new \Change();
        $changes_id = $change->add([
            'name' => 'test',
            'content' => 'test',
            'status' => \Change::INCOMING,
        ]);
        $this->assertGreaterThan(0, $changes_id);

        // Link the Ticket to the Change
        $itil_itil = new \Change_Ticket();
        $itil_itil_id = $itil_itil->add([
            'tickets_id' => $tickets_id,
            'changes_id' => $changes_id,
            'link' => \CommonITILObject_CommonITILObject::LINK_TO,
        ]);
        $this->assertGreaterThan(0, $itil_itil_id);

        // Create a Problem
        $problem = new \Problem();
        $problems_id = $problem->add([
            'name' => 'test',
            'content' => 'test',
            'status' => \Problem::INCOMING,
        ]);
        $this->assertGreaterThan(0, $changes_id);

        // Link the Ticket to the Problem
        $itil_itil = new \Problem_Ticket();
        $itil_itil_id = $itil_itil->add([
            'tickets_id' => $tickets_id,
            'problems_id' => $problems_id,
            'link' => \CommonITILObject_CommonITILObject::LINK_TO,
        ]);
        $this->assertGreaterThan(0, $itil_itil_id);

        // Bypass rights check as we don't test rights here
        $this->assertCount(2, \CommonITILObject_CommonITILObject::getAllLinkedTo('Ticket', $tickets_id, true));
        $this->assertCount(1, \CommonITILObject_CommonITILObject::getAllLinkedTo('Change', $changes_id, true));
        $this->assertCount(1, \CommonITILObject_CommonITILObject::getAllLinkedTo('Problem', $problems_id, true));
    }

    public function testGetLinkName()
    {
        $link_types = [
            \CommonITILObject_CommonITILObject::LINK_TO,
            \CommonITILObject_CommonITILObject::DUPLICATE_WITH,
            \CommonITILObject_CommonITILObject::SON_OF,
            \CommonITILObject_CommonITILObject::PARENT_OF,
        ];
        foreach ($link_types as $link_type) {
            $normal = \CommonITILObject_CommonITILObject::getLinkName($link_type, false, false);
            $inverted = \CommonITILObject_CommonITILObject::getLinkName($link_type, true, false);
            $with_icon = \CommonITILObject_CommonITILObject::getLinkName($link_type, false, true);

            $this->assertTrue(is_string($normal));
            $this->assertTrue(is_string($inverted));
            $this->assertTrue(is_string($with_icon));

            if ($link_type !== \CommonITILObject_CommonITILObject::LINK_TO) {
                $this->assertNotEquals($inverted, $normal);
            }
            $this->assertStringContainsString('<i class', $with_icon);
        }

        // Test invalid link type
        $invalid_link_type = -1;
        $normal = \CommonITILObject_CommonITILObject::getLinkName($invalid_link_type, false, false);
        $inverted = \CommonITILObject_CommonITILObject::getLinkName($invalid_link_type, true, false);
        $with_icon = \CommonITILObject_CommonITILObject::getLinkName($invalid_link_type, false, true);
        $this->assertEquals(NOT_AVAILABLE, $normal);
        $this->assertEquals(NOT_AVAILABLE, $inverted);
        $this->assertEquals(NOT_AVAILABLE, $with_icon);
    }

    public static function getLinkClassProvider()
    {
        return [
            ['Ticket', 'Ticket', \Ticket_Ticket::class],
            ['Ticket', 'Change', \Change_Ticket::class],
            ['Ticket', 'Problem', \Problem_Ticket::class],
            ['Change', 'Ticket', \Change_Ticket::class],
            ['Change', 'Change', \Change_Change::class],
            ['Change', 'Problem', \Change_Problem::class],
            ['Problem', 'Ticket', \Problem_Ticket::class],
            ['Problem', 'Change', \Change_Problem::class],
            ['Problem', 'Problem', \Problem_Problem::class],
        ];
    }

    #[DataProvider('getLinkClassProvider')]
    public function testGetLinkClass(string $itemtype_1, string $itemtype_2, string $expected)
    {
        $this->assertEquals($expected, \CommonITILObject_CommonITILObject::getLinkClass($itemtype_1, $itemtype_2));
    }

    public function testGetAllLinkClasses()
    {
        $link_classes = \CommonITILObject_CommonITILObject::getAllLinkClasses();
        $this->assertGreaterThanOrEqual(6, count($link_classes));

        foreach ($link_classes as $link_class) {
            $this->assertTrue(is_subclass_of($link_class, \CommonITILObject_CommonITILObject::class, true));
        }
    }

    public static function normalizeInputProvider()
    {
        return [
            [
                'class' => \Ticket_Ticket::class,
                'input' => [
                    'itemtype_1'    => 'Ticket',
                    'items_id_1'    => 1,
                    'itemtype_2'    => 'Ticket',
                    'items_id_2'    => 2,
                ],
                'expected' => [
                    'tickets_id_1'  => 1,
                    'tickets_id_2'  => 2,
                    'link'          => \CommonITILObject_CommonITILObject::LINK_TO,
                ],
            ],
            [
                'class' => \Ticket_Ticket::class,
                'input' => [
                    'itemtype_1'    => 'Ticket',
                    'items_id_1'    => 1,
                    'itemtype_2'    => 'Ticket',
                    'items_id_2'    => 2,
                    'link'          => \CommonITILObject_CommonITILObject::PARENT_OF,
                ],
                'expected' => [
                    'tickets_id_1'  => 2,
                    'tickets_id_2'  => 1,
                    'link'          => \CommonITILObject_CommonITILObject::SON_OF,
                ],
            ],
            [
                'class' => \Change_Change::class,
                'input' => [
                    'itemtype_1'    => 'Change',
                    'items_id_1'    => 1,
                    'itemtype_2'    => 'Change',
                    'items_id_2'    => 2,
                ],
                'expected' => [
                    'changes_id_1'  => 1,
                    'changes_id_2'  => 2,
                    'link'          => \CommonITILObject_CommonITILObject::LINK_TO,
                ],
            ],
            [
                'class' => \Change_Ticket::class,
                'input' => [
                    'itemtype_1'    => 'Change',
                    'items_id_1'    => 1,
                    'itemtype_2'    => 'Ticket',
                    'items_id_2'    => 2,
                ],
                'expected' => [
                    'changes_id'    => 1,
                    'tickets_id'    => 2,
                    'link'          => \CommonITILObject_CommonITILObject::LINK_TO,
                ],
            ],
            [
                'class' => \Change_Ticket::class,
                'input' => [
                    'itemtype_1'    => 'Ticket',
                    'items_id_1'    => 2,
                    'itemtype_2'    => 'Change',
                    'items_id_2'    => 1,
                ],
                'expected' => [
                    'changes_id'    => 1,
                    'tickets_id'    => 2,
                    'link'          => \CommonITILObject_CommonITILObject::LINK_TO,
                ],
            ],
            [
                'class' => \Change_Ticket::class,
                'input' => [
                    'itemtype_1'    => 'Change',
                    'items_id_1'    => 1,
                    'itemtype_2'    => 'Ticket',
                    'items_id_2'    => 2,
                    'link'          => \CommonITILObject_CommonITILObject::PARENT_OF,
                ],
                'expected' => [
                    'changes_id'    => 1,
                    'tickets_id'    => 2,
                    'link'          => \CommonITILObject_CommonITILObject::PARENT_OF,
                ],
            ],
            [
                'class' => \Change_Ticket::class,
                'input' => [
                    'itemtype_1'    => 'Ticket',
                    'items_id_1'    => 2,
                    'itemtype_2'    => 'Change',
                    'items_id_2'    => 1,
                    'link'          => \CommonITILObject_CommonITILObject::PARENT_OF,
                ],
                'expected' => [
                    'changes_id'    => 1,
                    'tickets_id'    => 2,
                    'link'          => \CommonITILObject_CommonITILObject::SON_OF,
                ],
            ],
            [
                'class' => \Problem_Ticket::class,
                'input' => [
                    'itemtype_1'    => 'Ticket',
                    'items_id_1'    => 2,
                    'itemtype_2'    => 'Problem',
                    'items_id_2'    => 1,
                ],
                'expected' => [
                    'problems_id'   => 1,
                    'tickets_id'    => 2,
                    'link'          => \CommonITILObject_CommonITILObject::LINK_TO,
                ],
            ],
            [
                'class' => \Problem_Ticket::class,
                'input' => [
                    'itemtype_1'    => 'Problem',
                    'items_id_1'    => 1,
                    'itemtype_2'    => 'Ticket',
                    'items_id_2'    => 2,
                    'link'          => \CommonITILObject_CommonITILObject::PARENT_OF,
                ],
                'expected' => [
                    'problems_id'   => 1,
                    'tickets_id'    => 2,
                    'link'          => \CommonITILObject_CommonITILObject::PARENT_OF,
                ],
            ],
            [
                'class' => \Problem_Ticket::class,
                'input' => [
                    'itemtype_1'    => 'Ticket',
                    'items_id_1'    => 2,
                    'itemtype_2'    => 'Problem',
                    'items_id_2'    => 1,
                    'link'          => \CommonITILObject_CommonITILObject::PARENT_OF,
                ],
                'expected' => [
                    'problems_id'   => 1,
                    'tickets_id'    => 2,
                    'link'          => \CommonITILObject_CommonITILObject::SON_OF,
                ],
            ],
        ];
    }

    #[DataProvider('normalizeInputProvider')]
    public function testNormalizeInput(string $class, array $input, array $expected)
    {
        $instance = new $class();
        $this->assertSame($expected, $instance->normalizeInput($input));
    }

    /**
     * Test getLinkedTo with bypass_right_checks parameter across entities.
     * This test verifies that linked ITIL objects in child entities are visible
     * when accessing from the root entity, and that users without access to certain
     * entities don't see tickets they don't have rights to view.
     */
    public function testGetLinkedToWithCheckViewRightsAcrossEntities()
    {
        $this->login();

        // Get entity IDs
        $root_entity_id = getItemByTypeName('Entity', '_test_root_entity', true);
        $child_entity_1_id = getItemByTypeName('Entity', '_test_child_1', true);
        $child_entity_2_id = getItemByTypeName('Entity', '_test_child_2', true);

        // Create tickets in different entities
        $ticket = new \Ticket();

        // Ticket in root entity
        $ticket_root_id = $this->createItem(
            \Ticket::class,
            [
                'name' => 'Test ticket in root entity',
                'content' => 'Test content',
                'status' => \Ticket::INCOMING,
                'entities_id' => $root_entity_id,
            ]
        )->getID();

        // Ticket in child entity 1
        $ticket_child1_id = $this->createItem(
            \Ticket::class,
            [
                'name' => 'Test ticket in child entity 1',
                'content' => 'Test content',
                'status' => \Ticket::INCOMING,
                'entities_id' => $child_entity_1_id,
            ]
        )->getID();

        // Ticket in child entity 2
        $ticket_child2_id = $this->createItem(
            \Ticket::class,
            [
                'name' => 'Test ticket in child entity 2',
                'content' => 'Test content',
                'status' => \Ticket::INCOMING,
                'entities_id' => $child_entity_2_id,
            ]
        )->getID();

        // Link tickets together
        $this->createItem(
            \Ticket_Ticket::class,
            [
                'tickets_id_1' => $ticket_root_id,
                'tickets_id_2' => $ticket_child1_id,
                'link' => \CommonITILObject_CommonITILObject::LINK_TO,
            ]
        )->getID();

        $this->createItem(
            \Ticket_Ticket::class,
            [
                'tickets_id_1' => $ticket_root_id,
                'tickets_id_2' => $ticket_child2_id,
                'link' => \CommonITILObject_CommonITILObject::LINK_TO,
            ]
        )->getID();

        // Test 1: From root entity with recursive access - should see all linked tickets
        $this->setEntity('_test_root_entity', true);

        // With bypass_right_checks=true - should see all links (bypassing rights check)
        $links_bypass = \Ticket_Ticket::getLinkedTo('Ticket', $ticket_root_id, true);
        $this->assertCount(2, $links_bypass);

        // Default (bypass_right_checks=false) - should still see all links (user has access)
        $links_with_check = \Ticket_Ticket::getLinkedTo('Ticket', $ticket_root_id);
        $this->assertCount(2, $links_with_check);

        // Test 2: From child entity 1 without recursive access
        $this->setEntity('_test_child_1', false);

        // With bypass_right_checks=true - returns all database links
        $links_bypass = \Ticket_Ticket::getLinkedTo('Ticket', $ticket_root_id, true);
        $this->assertCount(2, $links_bypass);

        // Default (bypass_right_checks=false) - should only see the ticket in child entity 1
        $links_with_check = \Ticket_Ticket::getLinkedTo('Ticket', $ticket_root_id);
        $this->assertCount(1, $links_with_check);
        $link_item = reset($links_with_check);
        $this->assertEquals($ticket_child1_id, $link_item['items_id']);
    }

    /**
     * Test getAllLinkedTo with bypass_right_checks parameter.
     */
    public function testGetAllLinkedToWithCheckViewRightsAcrossEntities()
    {
        $this->login();

        // Get entity IDs
        $root_entity_id = getItemByTypeName('Entity', '_test_root_entity', true);
        $child_entity_1_id = getItemByTypeName('Entity', '_test_child_1', true);
        $child_entity_2_id = getItemByTypeName('Entity', '_test_child_2', true);

        // Create a ticket in root entity
        $ticket_root_id = $this->createItem(
            \Ticket::class,
            [
                'name' => 'Test ticket for getAllLinkedTo',
                'content' => 'Test content',
                'status' => \Ticket::INCOMING,
                'entities_id' => $root_entity_id,
            ]
        )->getID();

        // Create a change in child entity 1
        $change_child1_id = $this->createItem(
            \Change::class,
            [
                'name' => 'Test change in child entity 1',
                'content' => 'Test content',
                'status' => \Change::INCOMING,
                'entities_id' => $child_entity_1_id,
            ]
        )->getID();

        // Create a problem in child entity 2
        $problem_child2_id = $this->createItem(
            \Problem::class,
            [
                'name' => 'Test problem in child entity 2',
                'content' => 'Test content',
                'status' => \Problem::INCOMING,
                'entities_id' => $child_entity_2_id,
            ]
        )->getID();

        // Link the ticket to the change
        $this->createItem(
            \Change_Ticket::class,
            [
                'tickets_id' => $ticket_root_id,
                'changes_id' => $change_child1_id,
                'link' => \CommonITILObject_CommonITILObject::LINK_TO,
            ]
        );
        // Link the ticket to the problem
        $this->createItem(
            \Problem_Ticket::class,
            [
                'tickets_id' => $ticket_root_id,
                'problems_id' => $problem_child2_id,
                'link' => \CommonITILObject_CommonITILObject::LINK_TO,
            ]
        );

        // Test from root entity with recursive access
        $this->setEntity('_test_root_entity', true);

        // With bypass_right_checks=true - should see all links
        $all_links_bypass = \CommonITILObject_CommonITILObject::getAllLinkedTo('Ticket', $ticket_root_id, true);
        $this->assertCount(2, $all_links_bypass);

        // Default (bypass_right_checks=false) - should still see all links
        $all_links_with_check = \CommonITILObject_CommonITILObject::getAllLinkedTo('Ticket', $ticket_root_id);
        $this->assertCount(2, $all_links_with_check);

        // Test from child entity 1 without recursive access
        $this->setEntity('_test_child_1', false);

        // Default (bypass_right_checks=false) - should only see the change (in child entity 1)
        $all_links_with_check = \CommonITILObject_CommonITILObject::getAllLinkedTo('Ticket', $ticket_root_id);
        $this->assertCount(1, $all_links_with_check);
        $link_item = reset($all_links_with_check);
        $this->assertEquals('Change', $link_item['itemtype']);
        $this->assertEquals($change_child1_id, $link_item['items_id']);
    }

    /**
     * Test countAllLinks with bypass_right_checks parameter.
     */
    public function testCountAllLinksWithCheckViewRightsAcrossEntities()
    {
        $this->login();

        // Get entity IDs
        $root_entity_id = getItemByTypeName('Entity', '_test_root_entity', true);
        $child_entity_1_id = getItemByTypeName('Entity', '_test_child_1', true);
        $child_entity_2_id = getItemByTypeName('Entity', '_test_child_2', true);

        // Create a ticket in root entity
        $ticket_root_id = $this->createItem(
            \Ticket::class,
            [
                'name' => 'Test ticket for countAllLinks',
                'content' => 'Test content',
                'status' => \Ticket::INCOMING,
                'entities_id' => $root_entity_id,
            ]
        )->getID();

        // Create linked tickets in child entities
        $ticket_child1_id = $this->createItem(
            \Ticket::class,
            [
                'name' => 'Test ticket child 1 for countAllLinks',
                'content' => 'Test content',
                'status' => \Ticket::INCOMING,
                'entities_id' => $child_entity_1_id,
            ]
        )->getID();

        $ticket_child2_id = $this->createItem(
            \Ticket::class,
            [
                'name' => 'Test ticket child 2 for countAllLinks',
                'content' => 'Test content',
                'status' => \Ticket::INCOMING,
                'entities_id' => $child_entity_2_id,
            ]
        )->getID();
        $this->assertGreaterThan(0, $ticket_child2_id);

        // Link tickets
        $this->createItem(
            \Ticket_Ticket::class,
            [
                'tickets_id_1' => $ticket_root_id,
                'tickets_id_2' => $ticket_child1_id,
                'link' => \CommonITILObject_CommonITILObject::LINK_TO,
            ]
        );
        $this->createItem(
            \Ticket_Ticket::class,
            [
                'tickets_id_1' => $ticket_root_id,
                'tickets_id_2' => $ticket_child2_id,
                'link' => \CommonITILObject_CommonITILObject::LINK_TO,
            ]
        );

        // Test from root entity with recursive access
        $this->setEntity('_test_root_entity', true);

        // With bypass_right_checks=true
        $count_bypass = \CommonITILObject_CommonITILObject::countAllLinks('Ticket', $ticket_root_id, true);
        $this->assertEquals(2, $count_bypass);

        // Default (bypass_right_checks=false)
        $count_with_check = \CommonITILObject_CommonITILObject::countAllLinks('Ticket', $ticket_root_id);
        $this->assertEquals(2, $count_with_check);

        // Test from child entity 1 without recursive access
        $this->setEntity('_test_child_1', false);

        // With bypass_right_checks=true - still counts all database links
        $count_bypass = \CommonITILObject_CommonITILObject::countAllLinks('Ticket', $ticket_root_id, true);
        $this->assertEquals(2, $count_bypass);

        // Default (bypass_right_checks=false) - should only count visible links
        $count_with_check = \CommonITILObject_CommonITILObject::countAllLinks('Ticket', $ticket_root_id);
        $this->assertEquals(1, $count_with_check);
    }

    /**
     * Test that a ticket cannot be linked to itself (self-link prevention).
     */
    public function testPreventSelfLink()
    {
        $this->login();

        // Create a ticket
        $ticket_id = $this->createItem(
            \Ticket::class,
            [
                'name' => 'Test ticket for self-link prevention',
                'content' => 'Test content',
                'status' => \Ticket::INCOMING,
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            ]
        )->getID();

        // Try to link the ticket to itself - should fail
        $ticket_ticket = new \Ticket_Ticket();
        $result = $ticket_ticket->add([
            'tickets_id_1' => $ticket_id,
            'tickets_id_2' => $ticket_id,
            'link' => \CommonITILObject_CommonITILObject::LINK_TO,
        ]);
        $this->assertFalse($result);

        // Try the other way around - should also fail
        $result = $ticket_ticket->add([
            'tickets_id_1' => $ticket_id,
            'tickets_id_2' => $ticket_id,
            'link' => \CommonITILObject_CommonITILObject::DUPLICATE_WITH,
        ]);
        $this->assertFalse($result);

        // Same test for Change
        $change_id = $this->createItem(
            \Change::class,
            [
                'name' => 'Test change for self-link prevention',
                'content' => 'Test content',
                'status' => \Change::INCOMING,
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            ]
        )->getID();

        $change_change = new \Change_Change();
        $result = $change_change->add([
            'changes_id_1' => $change_id,
            'changes_id_2' => $change_id,
            'link' => \CommonITILObject_CommonITILObject::LINK_TO,
        ]);
        $this->assertFalse($result);

        // Same test for Problem
        $problem_id = $this->createItem(
            \Problem::class,
            [
                'name' => 'Test problem for self-link prevention',
                'content' => 'Test content',
                'status' => \Problem::INCOMING,
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            ]
        )->getID();

        $problem_problem = new \Problem_Problem();
        $result = $problem_problem->add([
            'problems_id_1' => $problem_id,
            'problems_id_2' => $problem_id,
            'link' => \CommonITILObject_CommonITILObject::LINK_TO,
        ]);
        $this->assertFalse($result);
    }
}
