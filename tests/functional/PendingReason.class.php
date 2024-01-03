<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use Change;
use ChangeTask;
use CommonITILObject;
use DbTestCase;
use ITILFollowup;
use PendingReason_Item;
use Problem;
use ProblemTask;
use Ticket;
use TicketTask;

class PendingReason extends DbTestCase
{
    protected function testGetNextFollowupDateProvider()
    {
        return [
            [
            // Case 1: no auto bump
                'fields' => [
                    'followup_frequency'          => 0,
                ],
                'expected' => false
            ],
            [
            // Case 2: max bump reached
                'fields' => [
                    'followup_frequency'          => 60,
                    'followups_before_resolution' => 2,
                    'bump_count'                  => 2,
                ],
                'expected' => false
            ],
            [
            // Case 3: first bump
                'fields' => [
                    'followup_frequency'          => 60,
                    'followups_before_resolution' => 2,
                    'bump_count'                  => 0,
                    'last_bump_date'              => '2021-02-25 12:00:00',
                ],
                'expected' => date('2021-02-25 12:01:00')
            ],
            [
            // Case 4: second or more bump
                'fields' => [
                    'followup_frequency'          => 60,
                    'followups_before_resolution' => 7,
                    'bump_count'                  => 5,
                    'last_bump_date'              => '2021-02-25 13:00:00',
                ],
                'expected' => '2021-02-25 13:01:00'
            ],
        ];
    }

    /**
     * @dataprovider testGetNextFollowupDateProvider
     */
    public function testGetNextFollowupDate(array $fields, $expected)
    {
        $pending_reason_item = new \PendingReason_Item();
        $pending_reason_item->fields = $fields;

        $this->variable($expected)->isEqualTo($pending_reason_item->getNextFollowupDate());
    }

    protected function testGetAutoResolvedateProvider()
    {
        return [
            [
            // Case 1: no auto bump
                'fields' => [
                    'followup_frequency'          => 0,
                    'followups_before_resolution' => 2,
                ],
                'expected' => false
            ],
            [
            // Case 2: no auto solve
                'fields' => [
                    'followup_frequency'          => 60,
                    'followups_before_resolution' => 0,
                ],
                'expected' => false
            ],
            [
            // Case 3: 0/5 bump occurred yet
                'fields' => [
                    'followup_frequency'          => 60,
                    'followups_before_resolution' => 5,
                    'bump_count'                  => 0,
                    'last_bump_date'              => '2021-02-25 14:00:00',
                ],
                'expected' => '2021-02-25 14:06:00'
            ],
            [
            // Case 4: 1/5 bump occurred
                'fields' => [
                    'followup_frequency'          => 60,
                    'followups_before_resolution' => 5,
                    'bump_count'                  => 1,
                    'last_bump_date'              => '2021-02-25 15:00:00',
                ],
                'expected' => '2021-02-25 15:05:00'
            ],
            [
            // Case 5: 2/5 bump occurred
                'fields' => [
                    'followup_frequency'          => 60,
                    'followups_before_resolution' => 5,
                    'bump_count'                  => 2,
                    'last_bump_date'              => '2021-02-25 16:00:00',
                ],
                'expected' => '2021-02-25 16:04:00'
            ],
            [
            // Case 5: 3/5 bump occurred
                'fields' => [
                    'followup_frequency'          => 60,
                    'followups_before_resolution' => 5,
                    'bump_count'                  => 3,
                    'last_bump_date'              => '2021-02-25 17:00:00',
                ],
                'expected' => '2021-02-25 17:03:00'
            ],
            [
            // Case 5: 4/5 bump occurred
                'fields' => [
                    'followup_frequency'          => 60,
                    'followups_before_resolution' => 5,
                    'bump_count'                  => 4,
                    'last_bump_date'              => '2021-02-25 18:00:00',
                ],
                'expected' => '2021-02-25 18:02:00'
            ],
            [
            // Case 5: 5/5 bump occurred
                'fields' => [
                    'followup_frequency'          => 60,
                    'followups_before_resolution' => 5,
                    'bump_count'                  => 5,
                    'last_bump_date'              => '2021-02-25 19:00:00',
                ],
                'expected' => '2021-02-25 19:01:00'
            ],
        ];
    }

    /**
     * @dataprovider testGetAutoResolvedateProvider
     */
    public function testGetAutoResolvedate(array $fields, $expected)
    {
        $pending_reason_item = new \PendingReason_Item();
        $pending_reason_item->fields = $fields;

        $this->variable($expected)->isEqualTo($pending_reason_item->getAutoResolvedate());
    }


    protected function itemtypeProvider(): array
    {
        return [
            ['itemtype' => Ticket::class],
            ['itemtype' => Change::class],
            ['itemtype' => Problem::class],
        ];
    }

    protected function itemtypeAndActionProvider(): array
    {
        $array = [];
        $itemtypes = [Ticket::class, Change::class, Problem::class];
        foreach ($itemtypes as $itemtype) {
            $array[] = [
                'itemtype' => $itemtype,
                'action_itemtype' => ITILFollowup::class,
            ];
            $array[] = [
                'itemtype' => $itemtype,
                'action_itemtype' => $itemtype::getTaskClass(),
            ];
        }

        return $array;
    }

    protected static function getBaseActionAddInput($action_item, $item)
    {
        if ($action_item instanceof ITILFollowup) {
            return [
                'items_id' => $item->getID(),
                'itemtype' => $item::getType(),
            ];
        } else if ($action_item instanceof TicketTask) {
            return ['tickets_id' => $item->getID()];
        } else if ($action_item instanceof ChangeTask) {
            return ['changes_id' => $item->getID()];
        } else if ($action_item instanceof ProblemTask) {
            return ['problems_id' => $item->getID()];
        }

        return [];
    }

    /**
     * Test that a PendingReason_Item object is created when an item is marked as
     * pending
     *
     * @dataprovider itemtypeAndActionProvider
     */
    public function testPendingItemCreation($itemtype, $action_itemtype)
    {
        $this->login();

        $item = new $itemtype();
        $action_item = new $action_itemtype();

       // Create test item
        $items_id = $item->add([
            'name'    => 'test',
            'content' => 'test',
        ]);
        $this->integer($items_id)->isGreaterThan(0);
        $this->boolean($item->getFromDB($items_id))->isTrue();

       // Check that no pending item exist
        $this->boolean(PendingReason_Item::getForItem($item))->isFalse();

       // Add a new action with the "pending" flag set
        $actions_id = $action_item->add([
            'content' => 'test',
            'pending' => true,
            'pendingreasons_id' => 0,
        ] + self::getBaseActionAddInput($action_item, $item));
        $this->integer($actions_id)->isGreaterThan(0);

       // Check that pending item have been created
        $this->variable(PendingReason_Item::getForItem($item))->isNotFalse();

       // Check that parent item status was set to pending
        $this->boolean($item->getFromDB($items_id))->isTrue();
        $this->integer($item->fields['status'])->isEqualTo(CommonITILObject::WAITING);
    }

    /**
     * A status change from pending to any other should delete any linked
     * PendingReason_Item objects
     *
     * @dataprovider itemtypeProvider
     */
    public function testStatusChangeNoLongerPending($itemtype)
    {
        $this->login();

        $item = new $itemtype();

       // Create test item
        $items_id = $item->add([
            'name'    => 'test',
            'content' => 'test',
            'status'  => CommonITILObject::WAITING,
        ]);
        $this->integer($items_id)->isGreaterThan(0);
        $this->boolean($item->getFromDB($items_id))->isTrue();

       // Check item is pending
        $this->integer($item->fields['status'])->isEqualTo(CommonITILObject::WAITING);

       // Attach pending item
        $this->boolean(PendingReason_Item::createForItem($item, []))->isTrue();

       // Check pending item
        $this->variable(PendingReason_Item::getForItem($item))->isNotFalse();

       // Change ticket status
        $success = $item->update([
            'id' => $items_id,
            'status' => CommonITILObject::ASSIGNED,
        ]);
        $this->boolean($success)->isTrue();

       // Check pending item again
        $this->boolean(PendingReason_Item::getForItem($item))->isFalse();
    }

    public function testAddPendingFollowupOnAlreadyPending(): void
    {
        $this->login();
        $entity = getItemByTypeName('Entity', '_test_root_entity', true);

        // Create a pending reason and a ticket for our tests
        $pending_reason = $this->createItem('PendingReason', [
            'entities_id'                => $entity,
            'name'                       => 'Pending reason 1',
            'followup_frequency'         => 604800,
            'followups_before_resolution' => 3,
        ]);
        $ticket = $this->createItem('Ticket', [
            'name'                => 'Ticket',
            'content'             => 'Ticket',
            '_users_id_requester' => getItemByTypeName('User', 'post-only', true),
            '_users_id_assign'    => getItemByTypeName('User', TU_USER, true),
            'entities_id'         => $entity
        ]);

        // Set the ticket as pending with a reason
        $this->createItem('ITILFollowup', [
            'itemtype'                   => $ticket->getType(),
            'items_id'                   => $ticket->getID(),
            'content'                    => 'Followup with pending reason',
            'pending'                    => true,
            'pendingreasons_id'         => $pending_reason->getID(),
            'followup_frequency'         => 604800,
            'followups_before_resolution' => 3,
        ], ['pending', 'pendingreasons_id', 'followup_frequency', 'followups_before_resolution']);

        // Check that pending reason is applied to parent ticket
        $p_item = PendingReason_Item::getForItem($ticket);
        $this->integer($p_item->fields['pendingreasons_id'])->isEqualTo($pending_reason->getID());
        $this->integer($p_item->fields['followup_frequency'])->isEqualTo(604800);
        $this->integer($p_item->fields['followups_before_resolution'])->isEqualTo(3);

        // Add a new followup, keeping the pending state
        $this->createItem('ITILFollowup', [
            'itemtype'                   => $ticket->getType(),
            'items_id'                   => $ticket->getID(),
            'content'                    => 'Followup that should not remove the pending reason',
            'pending'                    => true,
        ], ['pending']);

        // Check that pending reason is still active
        $p_item = PendingReason_Item::getForItem($ticket);
        $this->integer($p_item->fields['pendingreasons_id'])->isEqualTo($pending_reason->getID());
        $this->integer($p_item->fields['followup_frequency'])->isEqualTo(604800);
        $this->integer($p_item->fields['followups_before_resolution'])->isEqualTo(3);

        // Add a new followup, removing the pending state
        $this->createItem('ITILFollowup', [
            'itemtype'                   => $ticket->getType(),
            'items_id'                   => $ticket->getID(),
            'content'                    => 'Followup that should not remove the pending reason',
            'pending'                    => false,
        ], ['pending']);
        $p_item = PendingReason_Item::getForItem($ticket);
        $this->boolean($p_item)->isFalse();
    }

    /**
     * Remove pending from timeline item should delete any linked
     * PendingReason_Item objects and restore previous status
     */
    public function testRemovePendingAndRevertStatus(): void
    {
        $this->login();
        $entity = getItemByTypeName('Entity', '_test_root_entity', true);

        // Create a pending reason and a ticket for our tests
        $pending_reason = $this->createItem('PendingReason', [
            'entities_id'                   => $entity,
            'name'                          => 'Pending reason 1',
            'followup_frequency'            => 604800,
            'followups_before_resolution'   => 3,
        ]);

        foreach (
            [
                Ticket::class => CommonITILObject::ASSIGNED,
                Change::class => CommonITILObject::EVALUATION,
                Problem::class => CommonITILObject::OBSERVED
            ] as $itemtype => $status
        ) {
            $item = $this->createItem($itemtype, [
                'name'                =>  $itemtype,
                'content'             => "test " .  $itemtype,
                'status'              => $status,
                '_users_id_requester' => getItemByTypeName('User', 'post-only', true),
                '_users_id_assign'    => getItemByTypeName('User', TU_USER, true),
                'entities_id'         => $entity
            ]);

            // Set the item as pending with a reason
            $followup = $this->createItem('ITILFollowup', [
                'itemtype'                      => $item->getType(),
                'items_id'                      => $item->getID(),
                'content'                       => 'Followup with pending reason',
                'pending'                       => true,
                'pendingreasons_id'             => $pending_reason->getID(),
                'followup_frequency'            => 604800,
                'followups_before_resolution'   => 3,
            ], ['pending', 'pendingreasons_id', 'followup_frequency', 'followups_before_resolution']);

            // Check that pending reason is applied to parent item
            $p_item = PendingReason_Item::getForItem($item);
            $this->integer($p_item->fields['pendingreasons_id'])->isEqualTo($pending_reason->getID());
            $this->integer($p_item->fields['followup_frequency'])->isEqualTo(604800);
            $this->integer($p_item->fields['followups_before_resolution'])->isEqualTo(3);
            $this->integer($p_item->fields['previous_status'])->isEqualTo($status);

            // Update followup and unset pending
            $this->boolean($followup->update([
                'id' => $followup->getID(),
                'content'                    => $followup->fields['content'],
                'pending'                    => false,
            ]))->isTrue();

            // Check that pending reason no longer exist
            $p_item = PendingReason_Item::getForItem($item);
            $this->boolean($p_item)->isFalse();

            // Reload / Check that original status is set
            $item->getFromDB($item->getID());
            $this->integer($item->fields['status'])->isEqualTo($status);
        }
    }

    /**
     * Data provider for testHandlePendingReasonUpdateFromNewTimelineItem
     *
     * @return iterable
     */
    protected function testUpdatesFromNewTimelineItemProvider(): iterable
    {
        $this->login();
        $entity = getItemByTypeName('Entity', '_test_root_entity', true);

        // Create a set of pending reasons that will be reused in our test cases
        list(
            $pending_reason1,
            $pending_reason2
        ) = $this->createItems(\PendingReason::class, [
            ['entities_id' => $entity, 'is_recursive' => true, 'name' => 'Pending 1'],
            ['entities_id' => $entity, 'is_recursive' => true, 'name' => 'Pending 2'],
        ]);

        // Case 1: ticket without any pending data
        yield [
            'timeline' => [
                ['type' => ITILFollowup::class, 'pending' => 0],
                ['type' => TicketTask::class, 'pending' => 0],
            ],
            'expected' => [
                'status' => CommonITILObject::INCOMING,
            ],
        ];

        // Case 2: ticket with a single pending data
        yield [
            'timeline' => [
                [
                    'type'                        => ITILFollowup::class,
                    'pending'                     => 1,
                    'pendingreasons_id'           => $pending_reason1->getID(),
                    'followup_frequency'          => 3 * DAY_TIMESTAMP,
                    'followups_before_resolution' => 2,
                ],
            ],
            'expected' => [
                'status'                      => CommonITILObject::WAITING,
                'pendingreasons_id'           => $pending_reason1->getID(),
                'followup_frequency'          => 3 * DAY_TIMESTAMP,
                'followups_before_resolution' => 2,
                // Pending reason is attached to the first followup
                'pending_timeline_index'      => 0,
            ]
        ];

        // Case 3: ticket with two tasks (of which the first is pending)
        yield [
            'timeline' => [
                [
                    'type'                        => TicketTask::class,
                    'pending'                     => 1,
                    'pendingreasons_id'           => $pending_reason1->getID(),
                    'followup_frequency'          => 3 * DAY_TIMESTAMP,
                    'followups_before_resolution' => 2,
                ],
                ['type' => TicketTask::class],

            ],
            'expected' => [
                'status'                      => CommonITILObject::WAITING,
                'pendingreasons_id'           => $pending_reason1->getID(),
                'followup_frequency'          => 3 * DAY_TIMESTAMP,
                'followups_before_resolution' => 2,
                // Pending reason is attached to the first task
                'pending_timeline_index'      => 0,
            ]
        ];

        // Case 4: ticket with two followups
        // The first set the pending data and the second change it
        yield [
            'timeline' => [
                [
                    'type'                        => ITILFollowup::class,
                    'pending'                     => 1,
                    'pendingreasons_id'           => $pending_reason1->getID(),
                    'followup_frequency'          => 3 * DAY_TIMESTAMP,
                    'followups_before_resolution' => 2,
                ],
                [
                    'type'                        => ITILFollowup::class,
                    'pending'                     => 1,
                    'pendingreasons_id'           => $pending_reason2->getID(),
                    'followup_frequency'          => 2 * DAY_TIMESTAMP,
                    'followups_before_resolution' => 1,
                ],

            ],
            'expected' => [
                'status'                      => CommonITILObject::WAITING,
                'pendingreasons_id'           => $pending_reason2->getID(),
                'followup_frequency'          => 2 * DAY_TIMESTAMP,
                'followups_before_resolution' => 1,
                // Pending reason is still attached to the first followup, the second one only edited its value
                'pending_timeline_index'      => 0,
            ]
        ];

        // Case 5: ticket with two followups
        // The first set the pending data and the second remove it
        yield [
            'timeline' => [
                [
                    'type'                        => ITILFollowup::class,
                    'pending'                     => 1,
                    'pendingreasons_id'           => $pending_reason1->getID(),
                    'followup_frequency'          => 3 * DAY_TIMESTAMP,
                    'followups_before_resolution' => 2,
                ],
                [
                    'type'    => ITILFollowup::class,
                    'pending' => 0,
                ],

            ],
            'expected' => [
                'status' => CommonITILObject::INCOMING,
            ]
        ];

        // Case 6: ticket with 3 timeline items
        // The first set the pending data, the second remove it and the third add a new pending reason
        yield [
            'timeline' => [
                [
                    'type'                        => ITILFollowup::class,
                    'pending'                     => 1,
                    'pendingreasons_id'           => $pending_reason1->getID(),
                    'followup_frequency'          => 3 * DAY_TIMESTAMP,
                    'followups_before_resolution' => 2,
                ],
                [
                    'type'    => TicketTask::class,
                    'pending' => 0,
                ],
                [
                    'type'                        => ITILFollowup::class,
                    'pending'                     => 1,
                    'pendingreasons_id'           => $pending_reason2->getID(),
                    'followup_frequency'          => 0,
                    'followups_before_resolution' => 0,
                ],
            ],
            'expected' => [
                'status'                      => CommonITILObject::WAITING,
                'pendingreasons_id'           => $pending_reason2->getID(),
                'followup_frequency'          => 0 * DAY_TIMESTAMP,
                'followups_before_resolution' => 0,
                // Pending reason is attached to the third timeline item
                'pending_timeline_index'      => 2,
            ]
        ];

        // Case 7: ticket with 2 timeline items
        // The first set the pending data and the second send the same data
        // This simulate what will be sent if the user does not edit the displayed values
        yield [
            'timeline' => [
                [
                    'type'                        => ITILFollowup::class,
                    'pending'                     => 1,
                    'pendingreasons_id'           => $pending_reason1->getID(),
                    'followup_frequency'          => 3 * DAY_TIMESTAMP,
                    'followups_before_resolution' => 2,
                ],
                [
                    'type'                        => ITILFollowup::class,
                    'pending'                     => 1,
                    'pendingreasons_id'           => $pending_reason1->getID(),
                    'followup_frequency'          => 3 * DAY_TIMESTAMP,
                    'followups_before_resolution' => 2,
                ],
            ],
            'expected' => [
                'status'                      => CommonITILObject::WAITING,
                'pendingreasons_id'           => $pending_reason1->getID(),
                'followup_frequency'          => 3 * DAY_TIMESTAMP,
                'followups_before_resolution' => 2,
                // Pending reason is attached to the first timeline item
                'pending_timeline_index'      => 0,
            ]
        ];
    }

    /**
     * Test that updates on pending reason data done through new timeline items
     * are handled as expected
     *
     * This validate the "testHandlePendingReasonUpdateFromNewTimelineItem" method
     * and its references in ITILFollowup's and CommonITILTask's post_addItem() method
     *
     * @dataProvider testUpdatesFromNewTimelineItemProvider
     *
     * @param array $timeline A simple description of the timeline items to create
     *                        and their impact on pending reason data
     * @param array $expected The expected state after all timeline items have been
     *                        created
     * @return void
     */
    public function testHandlePendingReasonUpdateFromNewTimelineItem(
        array $timeline,
        array $expected
    ): void {
        // Create test ticket
        $ticket = $this->createItem(Ticket::class, [
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'name'        => 'test',
            'content'     => 'test',
        ]);

        // Insert timeline
        $items = [];
        foreach ($timeline as $timeline_item) {
            // Insert fake content
            $timeline_item['content'] = 'test';

            // Read and prepare itemtype (task or followup)
            $itemtype = $timeline_item['type'];
            unset($timeline_item['type']);

            if ($itemtype == ITILFollowup::class) {
                $timeline_item['itemtype'] = Ticket::class;
                $timeline_item['items_id'] = $ticket->getID();
            } else {
                $timeline_item['tickets_id'] = $ticket->getID();
            }
            $items[] = $this->createItem($itemtype, $timeline_item, [
                'pending',
                'pendingreasons_id',
                'followup_frequency',
                'followups_before_resolution'
            ]);
        }

        // Reload ticket
        $ticket->getFromDB($ticket->getID());

        // Compare final ticket state with expected state
        $this->integer($ticket->fields['status'])->isEqualTo($expected['status']);
        if ($ticket->fields['status'] == CommonITILObject::WAITING) {
            // Compute ticket pending data
            $last_timeline_item_pending_data = PendingReason_Item::getLastPendingTimelineItemDataForItem($ticket);
            $ticket_pending_data = PendingReason_Item::getForItem($ticket);

            // Validate pending data
            $keys = ['pendingreasons_id', 'followup_frequency', 'followups_before_resolution'];
            foreach ($keys as $key) {
                $this
                    ->integer($last_timeline_item_pending_data->fields[$key])
                    ->isEqualTo($expected[$key])
                ;
                $this
                    ->integer($ticket_pending_data->fields[$key])
                    ->isEqualTo($expected[$key])
                ;
            }

            // Check that pending data is attached to the correct followup
            $correct_timeline_item = $items[$expected['pending_timeline_index']];
            $this
                ->string($last_timeline_item_pending_data->fields['itemtype'])
                ->isEqualTo($correct_timeline_item::getType())
            ;
            $this
                ->integer($last_timeline_item_pending_data->fields['items_id'])
                ->isEqualTo($correct_timeline_item->getID())
            ;
        }
    }
}
