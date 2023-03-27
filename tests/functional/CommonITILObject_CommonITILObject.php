<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

/*
 * Test for src/CommonITILObject_CommonITILObject.php
 * */
class CommonITILObject_CommonITILObject extends DbTestCase
{
    public function testCountAllLinks()
    {
        // Create a Ticket
        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name' => 'test',
            'content' => 'test',
            'status' => \Ticket::INCOMING
        ]);
        $this->integer($tickets_id)->isGreaterThan(0);

        // Create a Change
        $change = new \Change();
        $changes_id = $change->add([
            'name' => 'test',
            'content' => 'test',
            'status' => \Change::INCOMING
        ]);
        $this->integer($changes_id)->isGreaterThan(0);

        // Link the Ticket to the Change
        $itil_itil = new \Change_Ticket();
        $itil_itil_id = $itil_itil->add([
            'tickets_id' => $tickets_id,
            'changes_id' => $changes_id,
            'link' => \CommonITILObject_CommonITILObject::LINK_TO
        ]);
        $this->integer($itil_itil_id)->isGreaterThan(0);

        // Check the number of links
        $this->integer(\CommonITILObject_CommonITILObject::countAllLinks('Ticket', $tickets_id))->isEqualTo(1);

        // Create a Problem
        $problem = new \Problem();
        $problems_id = $problem->add([
            'name' => 'test',
            'content' => 'test',
            'status' => \Problem::INCOMING
        ]);
        $this->integer($problems_id)->isGreaterThan(0);

        // Link the Ticket to the Problem
        $itil_itil = new \Problem_Ticket();
        $itil_itil_id = $itil_itil->add([
            'tickets_id' => $tickets_id,
            'problems_id' => $problems_id,
            'link' => \CommonITILObject_CommonITILObject::DUPLICATE_WITH
        ]);
        $this->integer($itil_itil_id)->isGreaterThan(0);

        $this->integer(\CommonITILObject_CommonITILObject::countAllLinks('Ticket', $tickets_id))->isEqualTo(2);

        // Add another ticket
        $tickets_id2 = $ticket->add([
            'name' => 'test2',
            'content' => 'test2',
            'status' => \Ticket::INCOMING
        ]);
        $this->integer($tickets_id2)->isGreaterThan(0);

        // Link the second Ticket to the original ticket
        $ticket_ticket = new \Ticket_Ticket();
        $ticket_ticket_id = $ticket_ticket->add([
            'tickets_id_1' => $tickets_id2,
            'tickets_id_2' => $tickets_id,
            'link' => \CommonITILObject_CommonITILObject::LINK_TO
        ]);
        $this->integer($ticket_ticket_id)->isGreaterThan(0);

        // Count links for both tickets
        $this->integer(\CommonITILObject_CommonITILObject::countAllLinks('Ticket', $tickets_id))->isEqualTo(3);
        $this->integer(\CommonITILObject_CommonITILObject::countAllLinks('Ticket', $tickets_id2))->isEqualTo(1);
    }

    public function testCountLinksByStatus()
    {
        $this->login(); // Required to be able to update a Change

        // Create a Ticket
        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name' => 'test',
            'content' => 'test',
            'status' => \Ticket::INCOMING
        ]);
        $this->integer($tickets_id)->isGreaterThan(0);

        // Create a Change
        $change = new \Change();
        $changes_id = $change->add([
            'name' => 'test',
            'content' => 'test',
            'status' => \Change::INCOMING
        ]);
        $this->integer($changes_id)->isGreaterThan(0);

        // Link the Ticket to the Change
        $itil_itil = new \Change_Ticket();
        $itil_itil_id = $itil_itil->add([
            'tickets_id' => $tickets_id,
            'changes_id' => $changes_id,
            'link' => \CommonITILObject_CommonITILObject::LINK_TO
        ]);
        $this->integer($itil_itil_id)->isGreaterThan(0);

        // Check the number of links by status
        $this->integer(\Change_Ticket::countLinksByStatus('Ticket', $tickets_id, [\Change::INCOMING]))->isEqualTo(1);
        $this->integer(\Change_Ticket::countLinksByStatus('Change', $changes_id, [\Ticket::INCOMING]))->isEqualTo(1);

        // Create a Problem
        $problem = new \Problem();
        $problems_id = $problem->add([
            'name' => 'test',
            'content' => 'test',
            'status' => \Problem::INCOMING
        ]);
        $this->integer($problems_id)->isGreaterThan(0);

        // Link the Ticket to the Problem
        $itil_itil = new \Problem_Ticket();
        $itil_itil_id = $itil_itil->add([
            'tickets_id' => $tickets_id,
            'problems_id' => $problems_id,
            'link' => \CommonITILObject_CommonITILObject::DUPLICATE_WITH
        ]);
        $this->integer($itil_itil_id)->isGreaterThan(0);

        $this->integer(\Problem_Ticket::countLinksByStatus('Ticket', $tickets_id, [\Problem::INCOMING]))->isEqualTo(1);
        $this->integer(\Change_Ticket::countLinksByStatus('Change', $changes_id, [\Ticket::INCOMING]))->isEqualTo(1);
        $this->integer(\Problem_Ticket::countLinksByStatus('Problem', $problems_id, [\Ticket::INCOMING]))->isEqualTo(1);
        $this->integer(\Problem_Ticket::countLinksByStatus('Problem', $problems_id, [\Ticket::INCOMING], [\CommonITILObject_CommonITILObject::LINK_TO]))->isEqualTo(0);
        $this->integer(\Problem_Ticket::countLinksByStatus('Problem', $problems_id, [\Ticket::INCOMING], [\CommonITILObject_CommonITILObject::DUPLICATE_WITH]))->isEqualTo(1);

        // Update Change status
        $this->boolean($change->update([
            'id' => $changes_id,
            'status' => \Change::PLANNED
        ]))->isTrue();

        $this->integer(\Problem_Ticket::countLinksByStatus('Ticket', $tickets_id, [\Problem::INCOMING]))->isEqualTo(1);
        $this->integer(\Change_Ticket::countLinksByStatus('Ticket', $tickets_id, [\Change::INCOMING]))->isEqualTo(0);
        $this->integer(\Change_Ticket::countLinksByStatus('Ticket', $tickets_id, [\Change::PLANNED]))->isEqualTo(1);
        $this->integer(\Problem_Ticket::countLinksByStatus('Ticket', $tickets_id, [\Problem::INCOMING]))->isEqualTo(1);
    }

    public function testGetLinkedTo()
    {
        // Create a Ticket
        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name' => 'test',
            'content' => 'test',
            'status' => \Ticket::INCOMING
        ]);
        $this->integer($tickets_id)->isGreaterThan(0);

        // Create a Change
        $change = new \Change();
        $changes_id = $change->add([
            'name' => 'test',
            'content' => 'test',
            'status' => \Change::INCOMING
        ]);
        $this->integer($changes_id)->isGreaterThan(0);

        // Link the Ticket to the Change
        $itil_itil = new \Change_Ticket();
        $itil_itil_id = $itil_itil->add([
            'tickets_id' => $tickets_id,
            'changes_id' => $changes_id,
            'link' => \CommonITILObject_CommonITILObject::LINK_TO
        ]);
        $this->integer($itil_itil_id)->isGreaterThan(0);

        // Create a Problem
        $problem = new \Problem();
        $problems_id = $problem->add([
            'name' => 'test',
            'content' => 'test',
            'status' => \Problem::INCOMING
        ]);
        $this->integer($changes_id)->isGreaterThan(0);

        // Link the Ticket to the Problem
        $itil_itil = new \Problem_Ticket();
        $itil_itil_id = $itil_itil->add([
            'tickets_id' => $tickets_id,
            'problems_id' => $problems_id,
            'link' => \CommonITILObject_CommonITILObject::LINK_TO
        ]);
        $this->integer($itil_itil_id)->isGreaterThan(0);

        $this->integer(count(\Ticket_Ticket::getLinkedTo('Ticket', $tickets_id)))->isEqualTo(0);
        $this->integer(count(\Change_Ticket::getLinkedTo('Ticket', $tickets_id)))->isEqualTo(1);
        $this->integer(count(\Problem_Ticket::getLinkedTo('Ticket', $tickets_id)))->isEqualTo(1);
    }

    public function testGetAllLinkedTo()
    {
// Create a Ticket
        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name' => 'test',
            'content' => 'test',
            'status' => \Ticket::INCOMING
        ]);
        $this->integer($tickets_id)->isGreaterThan(0);

        // Create a Change
        $change = new \Change();
        $changes_id = $change->add([
            'name' => 'test',
            'content' => 'test',
            'status' => \Change::INCOMING
        ]);
        $this->integer($changes_id)->isGreaterThan(0);

        // Link the Ticket to the Change
        $itil_itil = new \Change_Ticket();
        $itil_itil_id = $itil_itil->add([
            'tickets_id' => $tickets_id,
            'changes_id' => $changes_id,
            'link' => \CommonITILObject_CommonITILObject::LINK_TO
        ]);
        $this->integer($itil_itil_id)->isGreaterThan(0);

        // Create a Problem
        $problem = new \Problem();
        $problems_id = $problem->add([
            'name' => 'test',
            'content' => 'test',
            'status' => \Problem::INCOMING
        ]);
        $this->integer($changes_id)->isGreaterThan(0);

        // Link the Ticket to the Problem
        $itil_itil = new \Problem_Ticket();
        $itil_itil_id = $itil_itil->add([
            'tickets_id' => $tickets_id,
            'problems_id' => $problems_id,
            'link' => \CommonITILObject_CommonITILObject::LINK_TO
        ]);
        $this->integer($itil_itil_id)->isGreaterThan(0);

        $this->integer(count(\CommonITILObject_CommonITILObject::getAllLinkedTo('Ticket', $tickets_id)))->isEqualTo(2);
        $this->integer(count(\CommonITILObject_CommonITILObject::getAllLinkedTo('Change', $changes_id)))->isEqualTo(1);
        $this->integer(count(\CommonITILObject_CommonITILObject::getAllLinkedTo('Problem', $problems_id)))->isEqualTo(1);
    }

    public function testGetLinkName()
    {
        $link_types = [
            \CommonITILObject_CommonITILObject::LINK_TO,
            \CommonITILObject_CommonITILObject::DUPLICATE_WITH,
            \CommonITILObject_CommonITILObject::SON_OF,
            \CommonITILObject_CommonITILObject::PARENT_OF
        ];
        foreach ($link_types as $link_type) {
            $normal = \CommonITILObject_CommonITILObject::getLinkName($link_type, false, false);
            $inverted = \CommonITILObject_CommonITILObject::getLinkName($link_type, true, false);
            $with_icon = \CommonITILObject_CommonITILObject::getLinkName($link_type, false, true);

            $this->boolean(is_string($normal))->isTrue();
            $this->boolean(is_string($inverted))->isTrue();
            $this->boolean(is_string($with_icon))->isTrue();

            if ($link_type !== \CommonITILObject_CommonITILObject::LINK_TO) {
                $this->string($normal)->isNotEqualTo($inverted);
            }
            $this->string($with_icon)->contains('<i class');
        }

        // Test invalid link type
        $invalid_link_type = -1;
        $normal = \CommonITILObject_CommonITILObject::getLinkName($invalid_link_type, false, false);
        $inverted = \CommonITILObject_CommonITILObject::getLinkName($invalid_link_type, true, false);
        $with_icon = \CommonITILObject_CommonITILObject::getLinkName($invalid_link_type, false, true);
        $this->string($normal)->isEqualTo(NOT_AVAILABLE);
        $this->string($inverted)->isEqualTo(NOT_AVAILABLE);
        $this->string($with_icon)->isEqualTo(NOT_AVAILABLE);
    }

    protected function getLinkClassProvider()
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

    /**
     * @dataProvider getLinkClassProvider
     */
    public function testGetLinkClass(string $itemtype_1, string $itemtype_2, string $expected)
    {
        $this->string(\CommonITILObject_CommonITILObject::getLinkClass($itemtype_1, $itemtype_2))->isEqualTo($expected);
    }

    public function testGetAllLinkClasses()
    {
        $link_classes = \CommonITILObject_CommonITILObject::getAllLinkClasses();
        $this->integer(count($link_classes))->isGreaterThanOrEqualTo(6);

        foreach ($link_classes as $link_class) {
            $this->boolean(is_subclass_of($link_class, \CommonITILObject_CommonITILObject::class, true))->isTrue();
        }
    }

    protected function normalizeInputProvider()
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
                ]
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
                ]
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
                ]
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
                ]
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
                ]
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
                ]
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
                ]
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
                ]
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
                ]
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
                ]
            ],
        ];
    }

    /**
     * @dataProvider normalizeInputProvider
     */
    public function testNormalizeInput(string $class, array $input, array $expected)
    {
        $instance = new $class();
        $this->array($instance->normalizeInput($input))->isIdenticalTo($expected);
    }
}
