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

use Change;
use ChangeTask;
use CommonITILObject;
use DbTestCase;
use ITILFollowup;
use ITILFollowupTemplate;
use Notification;
use Notification_NotificationTemplate;
use NotificationTemplate;
use NotificationTemplateTranslation;
use PendingReason;
use PendingReason_Item;
use PHPUnit\Framework\Attributes\DataProvider;
use Problem;
use ProblemTask;
use SolutionTemplate;
use Symfony\Component\DomCrawler\Crawler;
use Ticket;
use TicketTask;

class PendingReasonTest extends DbTestCase
{
    public static function getNextFollowupDateProvider(): array
    {
        return [
            [
                // Case 1: no auto bump
                'fields' => [
                    'pendingreason' => [
                        'calendars_id' => 1,
                    ],
                    'pendingreason_item' => [
                        'followup_frequency' => 0,
                    ],
                ],
                'expected' => false,
            ],
            [
                // Case 2: max bump reached
                'fields' => [
                    'pendingreason' => [
                        'calendars_id' => 1,
                    ],
                    'pendingreason_item' => [
                        'followup_frequency'          => 60,
                        'followups_before_resolution' => 2,
                        'bump_count'                  => 2,
                    ],
                ],
                'expected' => false,
            ],
            [
                // Case 3: first bump
                'fields' => [
                    'pendingreason' => [
                        'calendars_id' => 1,
                    ],
                    'pendingreason_item' => [
                        'followup_frequency'          => 60,
                        'followups_before_resolution' => 2,
                        'bump_count'                  => 0,
                        'last_bump_date'              => '2021-02-25 12:00:00',
                    ],
                ],
                'expected' => date('2021-02-25 12:01:00'),
            ],
            [
                // Case 4: second or more bump
                'fields' => [
                    'pendingreason' => [
                        'calendars_id' => 1,
                    ],
                    'pendingreason_item' => [
                        'followup_frequency'          => 60,
                        'followups_before_resolution' => 7,
                        'bump_count'                  => 5,
                        'last_bump_date'              => '2021-02-25 13:00:00',
                    ],
                ],
                'expected' => '2021-02-25 13:01:00',
            ],
            [
                // Case 5: first with weekend
                'fields' => [
                    'pendingreason' => [
                        'calendars_id' => 1,
                    ],
                    'pendingreason_item' => [
                        'followup_frequency'          => 86400,
                        'followups_before_resolution' => 7,
                        'bump_count'                  => 0,
                        'last_bump_date'              => '2023-04-07 13:00:00',
                    ],
                ],
                'expected' => '2023-04-10 13:00:00',
            ],
            [
                // Case 6: first with xmas holidays
                'fields' => [
                    'calendar_holiday' => [
                        'calendars_id' => 1,
                        'holidays_id'  => 1,
                    ],
                    'pendingreason' => [
                        'calendars_id' => 1,
                    ],
                    'pendingreason_item' => [
                        'followup_frequency'          => 86400,
                        'followups_before_resolution' => 7,
                        'bump_count'                  => 0,
                        'last_bump_date'              => '2018-12-28 13:00:00',
                    ],
                ],
                'expected' => '2019-01-07 13:00:00',
            ],
            [
                // Case 7: no followup
                'fields' => [
                    'pendingreason' => [
                        'calendars_id' => 1,
                    ],
                    'pendingreason_item' => [
                        'followup_frequency'          => 86400,
                        'followups_before_resolution' => -1,
                        'bump_count'                  => 0,
                        'last_bump_date'              => '2018-12-28 13:00:00',
                    ],
                ],
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('getNextFollowupDateProvider')]
    public function testGetNextFollowupDate(array $fields, $expected)
    {
        if (isset($fields['calendar_holiday'])) {
            $this->createItem('Calendar_Holiday', $fields['calendar_holiday']);
        }

        $pending_reason = $this->createItem('PendingReason', $fields['pendingreason']);
        $fields['pendingreason_item']['pendingreasons_id'] = $pending_reason->getID();

        $pending_reason_item = new PendingReason_Item();
        $pending_reason_item->fields = $fields['pendingreason_item'];

        $this->assertEquals($expected, $pending_reason_item->getNextFollowupDate());
    }

    public static function getAutoResolvedateProvider(): array
    {
        return [
            [
                // Case 1: no auto bump
                'fields' => [
                    'pendingreason' => [
                        'calendars_id' => 1,
                    ],
                    'pendingreason_item' => [
                        'followup_frequency'          => 0,
                        'followups_before_resolution' => 2,
                    ],
                ],
                'expected' => false,
            ],
            [
                // Case 2: no auto solve
                'fields' => [
                    'pendingreason' => [
                        'calendars_id' => 1,
                    ],
                    'pendingreason_item' => [
                        'followup_frequency'          => 60,
                        'followups_before_resolution' => 0,
                    ],
                ],
                'expected' => false,
            ],
            [
                // Case 3: 0/5 bump occurred yet
                'fields' => [
                    'pendingreason' => [
                        'calendars_id' => 1,
                    ],
                    'pendingreason_item' => [
                        'followup_frequency'          => 60,
                        'followups_before_resolution' => 5,
                        'bump_count'                  => 0,
                        'last_bump_date'              => '2021-02-25 14:00:00',
                    ],
                ],
                'expected' => '2021-02-25 14:06:00',
            ],
            [
                // Case 4: 1/5 bump occurred
                'fields' => [
                    'pendingreason' => [
                        'calendars_id' => 1,
                    ],
                    'pendingreason_item' => [
                        'followup_frequency'          => 60,
                        'followups_before_resolution' => 5,
                        'bump_count'                  => 1,
                        'last_bump_date'              => '2021-02-25 15:00:00',
                    ],
                ],
                'expected' => '2021-02-25 15:05:00',
            ],
            [
                // Case 5: 2/5 bump occurred
                'fields' => [
                    'pendingreason' => [
                        'calendars_id' => 1,
                    ],
                    'pendingreason_item' => [
                        'followup_frequency'          => 60,
                        'followups_before_resolution' => 5,
                        'bump_count'                  => 2,
                        'last_bump_date'              => '2021-02-25 16:00:00',
                    ],
                ],
                'expected' => '2021-02-25 16:04:00',
            ],
            [
                // Case 5: 3/5 bump occurred
                'fields' => [
                    'pendingreason' => [
                        'calendars_id' => 1,
                    ],
                    'pendingreason_item' => [
                        'followup_frequency'          => 60,
                        'followups_before_resolution' => 5,
                        'bump_count'                  => 3,
                        'last_bump_date'              => '2021-02-25 17:00:00',
                    ],
                ],
                'expected' => '2021-02-25 17:03:00',
            ],
            [
                // Case 5: 4/5 bump occurred
                'fields' => [
                    'pendingreason' => [
                        'calendars_id' => 1,
                    ],
                    'pendingreason_item' => [
                        'followup_frequency'          => 60,
                        'followups_before_resolution' => 5,
                        'bump_count'                  => 4,
                        'last_bump_date'              => '2021-02-25 18:00:00',
                    ],
                ],
                'expected' => '2021-02-25 18:02:00',
            ],
            [
                // Case 5: 5/5 bump occurred
                'fields' => [
                    'pendingreason' => [
                        'calendars_id' => 1,
                    ],
                    'pendingreason_item' => [
                        'followup_frequency'          => 60,
                        'followups_before_resolution' => 5,
                        'bump_count'                  => 5,
                        'last_bump_date'              => '2021-02-25 19:00:00',
                    ],
                ],
                'expected' => '2021-02-25 19:01:00',
            ],
            [
                // Case 6: 0/8 bump occurred with weekend between
                'fields' => [
                    'pendingreason' => [
                        'calendars_id' => 1,
                    ],
                    'pendingreason_item' => [
                        'followup_frequency'          => 86400,
                        'followups_before_resolution' => 8,
                        'bump_count'                  => 0,
                        'last_bump_date'              => '2023-04-07 13:00:00',
                    ],
                ],
                'expected' => '2023-04-20 13:00:00',
            ],
            // Case 7: 0/8 bump occurred with xmas holidays between
            [
                'fields' => [
                    'calendar_holiday' => [
                        'calendars_id' => 1,
                        'holidays_id'  => 1,
                    ],
                    'pendingreason' => [
                        'calendars_id' => 1,
                    ],
                    'pendingreason_item' => [
                        'followup_frequency'          => 86400,
                        'followups_before_resolution' => 8,
                        'bump_count'                  => 0,
                        'last_bump_date'              => '2018-12-21 13:00:00',
                    ],
                ],
                'expected' => '2019-01-10 13:00:00',
            ],
            [
                // Case 8: no bumps before resolution
                'fields' => [
                    'pendingreason' => [
                        'calendars_id' => 1,
                    ],
                    'pendingreason_item' => [
                        'followup_frequency'          => 86400,
                        'followups_before_resolution' => -1,
                        'bump_count'                  => 0,
                        'last_bump_date'              => '2023-05-12 19:00:00',
                    ],
                ],
                'expected' => '2023-05-15 19:00:00',
            ],
        ];
    }

    #[DataProvider('getAutoResolvedateProvider')]
    public function testGetAutoResolvedate(array $fields, $expected)
    {
        if (isset($fields['calendar_holiday'])) {
            $this->createItem('Calendar_Holiday', $fields['calendar_holiday']);
        }

        $pending_reason = $this->createItem('PendingReason', $fields['pendingreason']);
        $fields['pendingreason_item']['pendingreasons_id'] = $pending_reason->getID();

        $pending_reason_item = new PendingReason_Item();
        $pending_reason_item->fields = $fields['pendingreason_item'];

        $this->assertEquals($expected, $pending_reason_item->getAutoResolvedate());
    }


    public static function itemtypeProvider(): array
    {
        return [
            ['itemtype' => Ticket::class],
            ['itemtype' => Change::class],
            ['itemtype' => Problem::class],
        ];
    }

    public static function itemtypeAndActionProvider(): array
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
        } elseif ($action_item instanceof TicketTask) {
            return ['tickets_id' => $item->getID()];
        } elseif ($action_item instanceof ChangeTask) {
            return ['changes_id' => $item->getID()];
        } elseif ($action_item instanceof ProblemTask) {
            return ['problems_id' => $item->getID()];
        }

        return [];
    }

    /**
     * Test that a PendingReason_Item object is created when an item is marked as
     * pending
     */
    #[DataProvider('itemtypeAndActionProvider')]
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
        $this->assertGreaterThan(0, $items_id);
        $this->assertTrue($item->getFromDB($items_id));

        // Check that no pending item exist
        $this->assertFalse(PendingReason_Item::getForItem($item));

        // Add a new action with the "pending" flag set
        $actions_id = $action_item->add([
            'content' => 'test',
            'pending' => true,
            'pendingreasons_id' => 0,
        ] + self::getBaseActionAddInput($action_item, $item));
        $this->assertGreaterThan(0, $actions_id);

        // Check that pending item have been created
        $this->assertNotFalse(PendingReason_Item::getForItem($item));

        // Check that parent item status was set to pending
        $this->assertTrue($item->getFromDB($items_id));
        $this->assertEquals(CommonITILObject::WAITING, $item->fields['status']);
    }

    /**
     * A status change from pending to any other should delete any linked
     * PendingReason_Item objects
     */
    #[DataProvider('itemtypeProvider')]
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
        $this->assertGreaterThan(0, $items_id);
        $this->assertTrue($item->getFromDB($items_id));

        // Check item is pending
        $this->assertEquals(CommonITILObject::WAITING, $item->fields['status']);

        // Attach pending item
        $this->assertTrue(PendingReason_Item::createForItem($item, []));

        // Check pending item
        $this->assertNotFalse(PendingReason_Item::getForItem($item));

        // Change ticket status
        $this->assertTrue(
            $item->update([
                'id' => $items_id,
                'status' => CommonITILObject::ASSIGNED,
            ])
        );

        // Check pending item again
        $this->assertFalse(PendingReason_Item::getForItem($item));
    }

    public function testAddPendingFollowupOnAlreadyPending(): void
    {
        $this->login();
        $entity = getItemByTypeName('Entity', '_test_root_entity', true);

        $pending_reason = getItemByTypeName(PendingReason::class, 'needupdate_pendingreason');
        $ticket = $this->createItem('Ticket', [
            'name'                => 'Ticket',
            'content'             => 'Ticket',
            '_users_id_requester' => getItemByTypeName('User', 'post-only', true),
            '_users_id_assign'    => getItemByTypeName('User', TU_USER, true),
            'entities_id'         => $entity,
        ]);

        // Set the ticket as pending with a reason
        $this->createItem('ITILFollowup', [
            'itemtype'                   => Ticket::class,
            'items_id'                   => $ticket->getID(),
            'content'                    => 'Followup with pending reason',
            'pending'                    => true,
            'pendingreasons_id'         => $pending_reason->getID(),
            'followup_frequency'         => 604800,
            'followups_before_resolution' => 3,
        ], ['pending', 'pendingreasons_id', 'followup_frequency', 'followups_before_resolution']);

        // Check that pending reason is applied to parent ticket
        $p_item = PendingReason_Item::getForItem($ticket);
        $this->assertEquals($pending_reason->getID(), $p_item->fields['pendingreasons_id']);
        $this->assertEquals(604800, $p_item->fields['followup_frequency']);
        $this->assertEquals(3, $p_item->fields['followups_before_resolution']);

        // Add a new followup, keeping the pending state
        $this->createItem('ITILFollowup', [
            'itemtype'                   => Ticket::class,
            'items_id'                   => $ticket->getID(),
            'content'                    => 'Followup that should not remove the pending reason',
            'pending'                    => true,
        ], ['pending']);

        // Check that pending reason is still active
        $p_item = PendingReason_Item::getForItem($ticket);
        $this->assertEquals($pending_reason->getID(), $p_item->fields['pendingreasons_id']);
        $this->assertEquals(604800, $p_item->fields['followup_frequency']);
        $this->assertEquals(3, $p_item->fields['followups_before_resolution']);

        // Add a new followup, removing the pending state
        $this->createItem('ITILFollowup', [
            'itemtype'                   => Ticket::class,
            'items_id'                   => $ticket->getID(),
            'content'                    => 'Followup that should remove the pending reason',
            'pending'                    => false,
        ], ['pending']);
        $p_item = PendingReason_Item::getForItem($ticket);
        $this->assertFalse($p_item);
    }

    /**
     * Remove pending from timeline item should delete any linked
     * PendingReason_Item objects and restore previous status
     */
    public function testRemovePendingAndRevertStatus(): void
    {
        $this->login();
        $entity = getItemByTypeName('Entity', '_test_root_entity', true);

        $pending_reason = getItemByTypeName(PendingReason::class, 'needupdate_pendingreason', true);

        foreach (
            [
                Ticket::class => CommonITILObject::ASSIGNED,
                Change::class => Change::EVALUATION,
                Problem::class => CommonITILObject::OBSERVED,
            ] as $itemtype => $status
        ) {
            $item = $this->createItem($itemtype, [
                'name'                =>  $itemtype,
                'content'             => "test " . $itemtype,
                'status'              => $status,
                '_users_id_requester' => getItemByTypeName('User', 'post-only', true),
                '_users_id_assign'    => getItemByTypeName('User', TU_USER, true),
                'entities_id'         => $entity,
            ]);

            // Set the item as pending with a reason
            $followup = $this->createItem('ITILFollowup', [
                'itemtype'                      => $item->getType(),
                'items_id'                      => $item->getID(),
                'content'                       => 'Followup with pending reason',
                'pending'                       => true,
                'pendingreasons_id'             => $pending_reason,
                'followup_frequency'            => 604800,
                'followups_before_resolution'   => 3,
            ], ['pending', 'pendingreasons_id', 'followup_frequency', 'followups_before_resolution']);

            // Check that pending reason is applied to parent item
            $p_item = PendingReason_Item::getForItem($item);
            $this->assertEquals($pending_reason, $p_item->fields['pendingreasons_id']);
            $this->assertEquals(604800, $p_item->fields['followup_frequency']);
            $this->assertEquals(3, $p_item->fields['followups_before_resolution']);
            $this->assertEquals($status, $p_item->fields['previous_status']);

            // Update followup and unset pending
            $this->assertTrue(
                $followup->update([
                    'id' => $followup->getID(),
                    'content'                    => $followup->fields['content'],
                    'pending'                    => false,
                ])
            );

            // Check that pending reason no longer exist
            $p_item = PendingReason_Item::getForItem($item);
            $this->assertFalse($p_item);

            // Reload / Check that original status is set
            $this->assertTrue($item->getFromDB($item->getID()));
            $this->assertEquals($status, $item->fields['status']);
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

        $current_date = '2025-01-31 12:00:00';
        $date2 = '2025-01-31 13:00:00';

        $_SESSION['glpi_currenttime'] = $current_date;
        // Create a set of pending reasons that will be reused in our test cases
        [
            $pending_reason1,
            $pending_reason2
        ] = $this->createItems(PendingReason::class, [
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
                    'last_bump_date'              => $current_date,
                ],
            ],
            'expected' => [
                'status'                      => CommonITILObject::WAITING,
                'pendingreasons_id'           => $pending_reason1->getID(),
                'followup_frequency'          => 3 * DAY_TIMESTAMP,
                'followups_before_resolution' => 2,
                // Pending reason is attached to the last followup
                'pending_timeline_index'      => 0,
                'last_bump_date'              => $current_date,
            ],
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
                    'last_bump_date'              => $current_date,
                ],
                [
                    'type'           => TicketTask::class,
                ],

            ],
            'expected' => [
                'status'                      => CommonITILObject::WAITING,
                'pendingreasons_id'           => $pending_reason1->getID(),
                'followup_frequency'          => 3 * DAY_TIMESTAMP,
                'followups_before_resolution' => 2,
                // Pending reason is attached to the last task
                'pending_timeline_index'      => 0,
                'last_bump_date'              => $current_date,
            ],
        ];

        // Case 4: ticket with two followups
        // The first set the pending data and the second change the pending data
        yield [
            'timeline' => [
                [
                    'type'                        => ITILFollowup::class,
                    'pending'                     => 1,
                    'pendingreasons_id'           => $pending_reason1->getID(),
                    'followup_frequency'          => 3 * DAY_TIMESTAMP,
                    'followups_before_resolution' => 2,
                    'last_bump_date'              => $current_date,
                ],
                [
                    'type'                        => ITILFollowup::class,
                    'pending'                     => 1,
                    'pendingreasons_id'           => $pending_reason2->getID(),
                    'followup_frequency'          => 2 * DAY_TIMESTAMP,
                    'followups_before_resolution' => 1,
                    'last_bump_date'              => $date2,
                ],

            ],
            'expected' => [
                'status'                      => CommonITILObject::WAITING,
                'pendingreasons_id'           => $pending_reason2->getID(),
                'followup_frequency'          => 2 * DAY_TIMESTAMP,
                'followups_before_resolution' => 1,
                // The pending reason is always attached to the last follow-up, the second one changes the value of the first one
                'pending_timeline_index'      => 1,
                'last_bump_date'              => $date2,
            ],
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
            ],
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
                    'last_bump_date'              => $current_date,
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
                    'last_bump_date'              => $date2,
                ],
            ],
            'expected' => [
                'status'                      => CommonITILObject::WAITING,
                'pendingreasons_id'           => $pending_reason2->getID(),
                'followup_frequency'          => 0 * DAY_TIMESTAMP,
                'followups_before_resolution' => 0,
                // Pending reason is attached to the third timeline item
                'pending_timeline_index'      => 2,
                'last_bump_date'              => $date2,
            ],
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
                    'last_bump_date'              => $current_date,
                ],
                [
                    'type'                        => ITILFollowup::class,
                    'pending'                     => 1,
                    'pendingreasons_id'           => $pending_reason1->getID(),
                    'followup_frequency'          => 3 * DAY_TIMESTAMP,
                    'followups_before_resolution' => 2,
                    'last_bump_date'              => $date2,
                ],
            ],
            'expected' => [
                'status'                      => CommonITILObject::WAITING,
                'pendingreasons_id'           => $pending_reason1->getID(),
                'followup_frequency'          => 3 * DAY_TIMESTAMP,
                'followups_before_resolution' => 2,
                // Pending reason is attached to the first timeline item
                'pending_timeline_index'      => 0,
                'last_bump_date'              => $current_date,
            ],
        ];

        // Case 8: ticket with 2 timeline items
        // This simulates what will be sent if a pending task is sent after a follow-up with retry
        yield [
            'timeline' => [
                [
                    'type'                        => ITILFollowup::class,
                    'pending'                     => 1,
                    'pendingreasons_id'           => $pending_reason1->getID(),
                    'followup_frequency'          => 3 * DAY_TIMESTAMP,
                    'followups_before_resolution' => 2,
                    'last_bump_date'              => $current_date,
                ],
                [
                    'type'                        => TicketTask::class,
                    'pending'                     => 1,
                    'last_bump_date'              => $date2,
                ],
            ],
            'expected' => [
                'status'                      => CommonITILObject::WAITING,
                'pendingreasons_id'           => $pending_reason1->getID(),
                'followup_frequency'          => 3 * DAY_TIMESTAMP,
                'followups_before_resolution' => 2,
                // Pending reason is attached to the last followup
                'pending_timeline_index'      => 0,
                'last_bump_date'              => $current_date,
            ],
        ];

        // Case 9: ticket with 2 timeline items
        // This simulate what will be sent if a pending followup with relauch added after the pending followup without relauch
        yield [
            'timeline' => [
                [
                    'type'                        => ITILFollowup::class,
                    'pending'                     => 1,
                    'pendingreasons_id'           => 0,
                    'last_bump_date'              => $current_date,
                ],
                [
                    'type'                        => ITILFollowup::class,
                    'pending'                     => 1,
                    'pendingreasons_id'           => $pending_reason1->getID(),
                    'followup_frequency'          => 3 * DAY_TIMESTAMP,
                    'followups_before_resolution' => 2,
                    'last_bump_date'              => $date2,
                ],
            ],
            'expected' => [
                'status'                      => CommonITILObject::WAITING,
                'pendingreasons_id'           => $pending_reason1->getID(),
                'followup_frequency'          => 3 * DAY_TIMESTAMP,
                'followups_before_resolution' => 2,
                // Pending reason is attached to the last timeline item
                'pending_timeline_index'      => 1,
                'last_bump_date'              => $date2,
            ],
        ];

        // Case 10: ticket with 2 timeline items
        // This simulates what will be sent if a pending follow up without retry is added after a task with waiting
        yield [
            'timeline' => [
                [
                    'type'                        => TicketTask::class,
                    'pending'                     => 1,
                    'pendingreasons_id'           => $pending_reason1->getID(),
                    'followup_frequency'          => 3 * DAY_TIMESTAMP,
                    'followups_before_resolution' => 2,
                    'last_bump_date'              => $current_date,
                ],
                [
                    'type'                        => ITILFollowup::class,
                    'pending'                     => 1,
                    'pendingreasons_id'           => 0,
                    'followup_frequency'          => 0,
                    'followups_before_resolution' => 0,
                    'last_bump_date'              => $date2,
                ],
            ],
            'expected' => [
                'status'                      => CommonITILObject::WAITING,
                'pendingreasons_id'           => 0,
                'followup_frequency'          => 0,
                'followups_before_resolution' => 0,
                // Pending reason is attached to the last timeline item
                'pending_timeline_index'      => 1,
                'last_bump_date'              => $date2,
            ],
        ];

        // Case 11: ticket with 2 timeline items
        // This simulates what will be sent if a pending follow with prompt is added after a pending task
        yield [
            'timeline' => [
                [
                    'type'                        => TicketTask::class,
                    'pending'                     => 1,
                    'pendingreasons_id'           => $pending_reason1->getID(),
                    'followup_frequency'          => 3 * DAY_TIMESTAMP,
                    'followups_before_resolution' => 2,
                    'last_bump_date'              => $current_date,
                ],
                [
                    'type'                        => ITILFollowup::class,
                    'pending'                     => 1,
                    'pendingreasons_id'           => $pending_reason2->getID(),
                    'followup_frequency'          => 2 * DAY_TIMESTAMP,
                    'followups_before_resolution' => 1,
                    'last_bump_date'              => $date2,
                ],
            ],
            'expected' => [
                'status'                      => CommonITILObject::WAITING,
                'pendingreasons_id'           => $pending_reason2->getID(),
                'followup_frequency'          => 2 * DAY_TIMESTAMP,
                'followups_before_resolution' => 1,
                // Pending reason is attached to the last timeline item
                'pending_timeline_index'      => 1,
                'last_bump_date'              => $date2,
            ],
        ];
    }

    /**
     * Test that updates on pending reason data done through new timeline items
     * are handled as expected
     *
     * This validate the "testHandlePendingReasonUpdateFromNewTimelineItem" method
     * and its references in ITILFollowup's and CommonITILTask's post_addItem() method
     *
     * @return void
     */
    public function testHandlePendingReasonUpdateFromNewTimelineItem(): void
    {
        $provider = $this->testUpdatesFromNewTimelineItemProvider();
        foreach ($provider as $row) {
            $timeline = $row['timeline'];
            $expected = $row['expected'];

            // Create test ticket
            $ticket = $this->createItem(Ticket::class, [
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
                'name' => 'test',
                'content' => 'test',
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
                    'followups_before_resolution',
                    'last_bump_date',
                ]);
            }

            // Reload ticket
            $this->assertTrue($ticket->getFromDB($ticket->getID()));

            // Compare final ticket state with expected state
            $this->assertEquals($expected['status'], $ticket->fields['status']);
            if ($ticket->fields['status'] == CommonITILObject::WAITING) {
                // Compute ticket pending data
                $last_timeline_item_pending_data = PendingReason_Item::getLastPendingTimelineItemDataForItem($ticket);
                $ticket_pending_data = PendingReason_Item::getForItem($ticket);

                // Validate pending data
                $keys = ['pendingreasons_id', 'followup_frequency', 'followups_before_resolution'];
                foreach ($keys as $key) {
                    $this->assertEquals(
                        $expected[$key],
                        $last_timeline_item_pending_data->fields[$key]
                    );
                    $this->assertEquals(
                        $expected[$key],
                        $ticket_pending_data->fields[$key]
                    );
                }

                // Check that pending data is attached to the correct followup
                $correct_timeline_item = $items[$expected['pending_timeline_index']];
                $this->assertEquals(
                    $correct_timeline_item::getType(),
                    $last_timeline_item_pending_data->fields['itemtype']
                );
                $this->assertEquals(
                    $correct_timeline_item->getID(),
                    $last_timeline_item_pending_data->fields['items_id']
                );
                $this->assertEquals(
                    $last_timeline_item_pending_data->fields['last_bump_date'],
                    $ticket_pending_data->fields['last_bump_date']
                );
            }
        }
    }

    protected function testCronPendingReasonAutobumpAutosolveProvider()
    {
        $this->login();

        $entity = getItemByTypeName('Entity', '_test_root_entity', true);

        $followup_frequency = 3 * DAY_TIMESTAMP;

        $current_date = '2025-01-31 12:00:00';
        $date_before_bump = '2025-01-28 12:00:00';
        $date_to_bump = '2025-01-28 11:59:59';

        $_SESSION['glpi_currenttime'] = $current_date;

        $itilfollowuptemplate = $this->createItem(ITILFollowupTemplate::class, [
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'name' => 'test',
            'content' => 'test',
        ]);

        $itilsolutiontemplate = $this->createItem(SolutionTemplate::class, [
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'name' => 'test',
            'content' => 'test',
        ]);

        // Create a set of pending reasons that will be reused in our test cases
        [
            $pending_reason1,
        ] = $this->createItems(PendingReason::class, [
            ['entities_id' => $entity, 'is_recursive' => true, 'name' => 'Pending 1', 'itilfollowuptemplates_id' => $itilfollowuptemplate->getID(), 'solutiontemplates_id' => $itilsolutiontemplate->getID()],
        ]);

        // Case 1: followup just published
        yield [
            'timeline' => [
                [
                    'type'                        => ITILFollowup::class,
                    'pending'                     => 1,
                    'pendingreasons_id'           => $pending_reason1->getID(),
                    'followup_frequency'          => $followup_frequency,
                    'followups_before_resolution' => 2,
                    'last_bump_date'              => $current_date,
                    'bump_count'                  => 0,
                ],
            ],
            'expected' => [
                'status'                      => CommonITILObject::WAITING,
                'pendingreasons_id'           => $pending_reason1->getID(),
                'followup_frequency'          => $followup_frequency,
                'followups_before_resolution' => 2,
                'last_bump_date'              => $current_date,
                'bump_count'                  => 0,
            ],
        ];

        // Case 2: task just published
        yield [
            'timeline' => [
                [
                    'type'                        => TicketTask::class,
                    'pending'                     => 1,
                    'pendingreasons_id'           => $pending_reason1->getID(),
                    'followup_frequency'          => $followup_frequency,
                    'followups_before_resolution' => 2,
                    'last_bump_date'              => $current_date,
                    'bump_count'                  => 0,
                ],
            ],
            'expected' => [
                'status'                      => CommonITILObject::WAITING,
                'pendingreasons_id'           => $pending_reason1->getID(),
                'followup_frequency'          => $followup_frequency,
                'followups_before_resolution' => 2,
                'last_bump_date'              => $current_date,
                'bump_count'                  => 0,
            ],
        ];

        // Case 3: follow up published just before the bump date
        yield [
            'timeline' => [
                [
                    'type'                        => ITILFollowup::class,
                    'pending'                     => 1,
                    'pendingreasons_id'           => $pending_reason1->getID(),
                    'followup_frequency'          => $followup_frequency,
                    'followups_before_resolution' => 2,
                    'last_bump_date'              => $date_before_bump,
                    'bump_count'                  => 0,
                ],
            ],
            'expected' => [
                'status'                      => CommonITILObject::WAITING,
                'pendingreasons_id'           => $pending_reason1->getID(),
                'followup_frequency'          => $followup_frequency,
                'followups_before_resolution' => 2,
                'last_bump_date'              => $date_before_bump,
                'bump_count'                  => 0,
            ],
        ];

        // Case 4: follow up that will be bumped one time
        yield [
            'timeline' => [
                [
                    'type'                        => ITILFollowup::class,
                    'pending'                     => 1,
                    'pendingreasons_id'           => $pending_reason1->getID(),
                    'followup_frequency'          => $followup_frequency,
                    'followups_before_resolution' => 2,
                    'last_bump_date'              => $date_to_bump,
                    'bump_count'                  => 0,
                ],
            ],
            'expected' => [
                'status'                      => CommonITILObject::WAITING,
                'pendingreasons_id'           => $pending_reason1->getID(),
                'followup_frequency'          => $followup_frequency,
                'followups_before_resolution' => 2,
                'last_bump_date'              => $current_date,
                'bump_count'                  => 1,
            ],
        ];

        // Case 5: follow up that will be bumped two times
        yield [
            'timeline' => [
                [
                    'type'                        => ITILFollowup::class,
                    'pending'                     => 1,
                    'pendingreasons_id'           => $pending_reason1->getID(),
                    'followup_frequency'          => $followup_frequency,
                    'followups_before_resolution' => 2,
                    'last_bump_date'              => $date_to_bump,
                    'bump_count'                  => 1,
                ],
            ],
            'expected' => [
                'status'                      => CommonITILObject::WAITING,
                'pendingreasons_id'           => $pending_reason1->getID(),
                'followup_frequency'          => $followup_frequency,
                'followups_before_resolution' => 2,
                'last_bump_date'              => $current_date,
                'bump_count'                  => 2,
            ],
        ];

        // Case 6: follow up that will be bumped three times. Close the ticket
        yield [
            'timeline' => [
                [
                    'type'                        => ITILFollowup::class,
                    'pending'                     => 1,
                    'pendingreasons_id'           => $pending_reason1->getID(),
                    'followup_frequency'          => $followup_frequency,
                    'followups_before_resolution' => 2,
                    'last_bump_date'              => $date_to_bump,
                    'bump_count'                  => 2,
                ],
            ],
            'expected' => [
                'status'                      => CommonITILObject::SOLVED,
                'pendingreasons_id'           => 0,
                'followup_frequency'          => 0,
                'followups_before_resolution' => 0,
                'last_bump_date'              => $current_date,
                'bump_count'                  => 3,
            ],
        ];
    }

    public function testCronPendingReasonAutobumpAutosolve()
    {
        $provider = $this->testCronPendingReasonAutobumpAutosolveProvider();
        foreach ($provider as $row) {
            $timeline = $row['timeline'];
            $expected = $row['expected'];

            // Create test ticket
            $ticket = $this->createItem(Ticket::class, [
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
                'name' => 'test',
                'content' => 'test',
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
                    'followups_before_resolution',
                    'last_bump_date',
                    'bump_count',
                ]);
            }

            // launch Cron for closing tickets
            $mode = - \CronTask::MODE_EXTERNAL; // force
            \CronTask::launch($mode, 1, 'pendingreason_autobump_autosolve');

            // Reload ticket
            $this->assertTrue($ticket->getFromDB($ticket->getID()));

            /** @var Ticket $ticket */
            $timeline = $ticket->getTimelineItems();

            $ticket_pending_data = PendingReason_Item::getForItem($ticket);
            $this->assertEquals($expected['status'], $ticket->fields['status']);
            if ($ticket->fields['status'] == CommonITILObject::WAITING) {
                $this->assertEquals($expected['pendingreasons_id'], $ticket_pending_data->fields['pendingreasons_id']);
                $this->assertEquals($expected['followup_frequency'], $ticket_pending_data->fields['followup_frequency']);
                $this->assertEquals($expected['followups_before_resolution'], $ticket_pending_data->fields['followups_before_resolution']);
                $this->assertEquals($expected['last_bump_date'], $ticket_pending_data->fields['last_bump_date']);
                $this->assertEquals($expected['bump_count'], $ticket_pending_data->fields['bump_count']);
            }
        }
    }

    public function testNotificationEvents(): void
    {
        global $CFG_GLPI;

        $notification = new Notification();
        $entities_id = $this->getTestRootEntity(true);

        $add_notifications_id = $notification->add([
            'name' => 'Add PendingReason',
            'entities_id' => $entities_id,
            'itemtype' => 'Ticket',
            'event' => 'pendingreason_add',
            'is_active' => 1,
        ]);

        $remove_notifications_id = $notification->add([
            'name' => 'Remove PendingReason',
            'entities_id' => $entities_id,
            'itemtype' => 'Ticket',
            'event' => 'pendingreason_del',
            'is_active' => 1,
        ]);

        $autoclose_notifications_id = $notification->add([
            'name' => 'Auto close PendingReason',
            'entities_id' => $entities_id,
            'itemtype' => 'Ticket',
            'event' => 'pendingreason_close',
            'is_active' => 1,
        ]);

        $notification_template = new NotificationTemplate();
        $notification_notification_template = new Notification_NotificationTemplate();

        $add_template_id = $notification_template->add([
            'name' => 'PendingReason Add',
            'itemtype' => 'Ticket',
        ]);

        $remove_template_id = $notification_template->add([
            'name' => 'PendingReason Remove',
            'itemtype' => 'Ticket',
        ]);

        $autoclose_template_id = $notification_template->add([
            'name' => 'PendingReason Auto close',
            'itemtype' => 'Ticket',
        ]);

        $notification_notification_template->add([
            'notifications_id' => $add_notifications_id,
            'mode' => 'mailing',
            'notificationtemplates_id' => $add_template_id,
        ]);
        $notification_notification_template->add([
            'notifications_id' => $remove_notifications_id,
            'mode' => 'mailing',
            'notificationtemplates_id' => $remove_template_id,
        ]);
        $notification_notification_template->add([
            'notifications_id' => $autoclose_notifications_id,
            'mode' => 'mailing',
            'notificationtemplates_id' => $autoclose_template_id,
        ]);

        $translation = new NotificationTemplateTranslation();
        $translation->add([
            'notificationtemplates_id' => $add_template_id,
            'language' => '',
            'subject' => 'PendingReason Add',
            'content_text' => 'PendingReason Add',
            'content_html' => 'PendingReason Add',
        ]);
        $translation->add([
            'notificationtemplates_id' => $remove_template_id,
            'language' => '',
            'subject' => 'PendingReason Remove',
            'content_text' => 'PendingReason Remove',
            'content_html' => 'PendingReason Remove',
        ]);
        $translation->add([
            'notificationtemplates_id' => $autoclose_template_id,
            'language' => '',
            'subject' => 'PendingReason Auto close',
            'content_text' => 'PendingReason Auto close',
            'content_html' => 'PendingReason Auto close',
        ]);

        $target = new \NotificationTarget();
        $target->add([
            'notifications_id' => $add_notifications_id,
            'type' => 1, // User
            'items_id' => 7, // Writer
        ]);
        $target->add([
            'notifications_id' => $remove_notifications_id,
            'type' => 1, // User
            'items_id' => 7, // Writer
        ]);
        $target->add([
            'notifications_id' => $autoclose_notifications_id,
            'type' => 1, // User
            'items_id' => 7, // Writer
        ]);

        $pending_reason = getItemByTypeName(PendingReason::class, 'needupdate_pendingreason');

        $this->login();
        $ticket = new Ticket();

        $ticket->add([
            'name' => __FUNCTION__,
            'content' => __FUNCTION__,
            'entities_id' => $entities_id,
            'status' => CommonITILObject::WAITING,
        ]);

        $CFG_GLPI['use_notifications'] = 1;
        $CFG_GLPI['notifications_mailing'] = 1;

        $this->assertTrue(PendingReason_Item::createForItem($ticket, [
            'pendingreasons_id' => $pending_reason->getID(),
            'followup_frequency' => DAY_TIMESTAMP,
            'followups_before_resolution' => 3,
        ]));
        $this->assertCount(1, getAllDataFromTable('glpi_queuednotifications', ['notificationtemplates_id' => $add_template_id]));

        $pri = new PendingReason_Item();
        $this->assertTrue($pri->getFromDBByCrit([
            'items_id' => $ticket->getID(),
            'itemtype' => $ticket::getType(),
        ]));
        $this->assertTrue($pri->update([
            'id' => $pri->getID(),
            'bump_count' => 3,
            'last_bump_date' => date('Y-m-d H:i:s', time() - (2 * DAY_TIMESTAMP)),
        ]));

        \PendingReasonCron::cronPendingreason_autobump_autosolve(new \CronTask());
        $this->assertCount(1, getAllDataFromTable('glpi_queuednotifications', ['notificationtemplates_id' => $autoclose_template_id]));

        PendingReason_Item::deleteForItem($ticket);
        $this->assertCount(1, getAllDataFromTable('glpi_queuednotifications', ['notificationtemplates_id' => $remove_template_id]));
    }

    public function testPendingReasonsMessages()
    {
        $this->login();

        $ticket = getItemByTypeName(Ticket::class, '_ticket01');
        $this->createItem(TicketTask::class, [
            'tickets_id' => $ticket->getID(),
            'content' => 'Test task content',
            'pending' => 1,
            'pendingreasons_id' => getItemByTypeName(PendingReason::class, 'needupdate_pendingreason', true),
            'followup_frequency' => DAY_TIMESTAMP,
            'followups_before_resolution' => 3,
        ], ['pending', 'pendingreasons_id', 'followup_frequency', 'followups_before_resolution']);
        $ticket->getFromDB($ticket->getID());
        $this->assertEquals(CommonITILObject::WAITING, $ticket->fields['status']);
        ob_start();
        $ticket->showForm($ticket->getID());
        $content = ob_get_clean();
        $crawler = new Crawler($content);
        $badges_text = $crawler->filter('.timeline-item.ITILTask:not(#new-TicketTask-block) .bg-blue-lt')->innerText();
        $this->assertStringContainsString('Pending:', $badges_text);
        $ticket->update([
            'id' => $ticket->getID(),
            'status' => CommonITILObject::INCOMING,
        ]);
        $this->assertEquals(CommonITILObject::INCOMING, $ticket->fields['status']);
        ob_start();
        $ticket->showForm($ticket->getID());
        $content = ob_get_clean();
        $crawler = new Crawler($content);
        $badges_text = $crawler->filter('.timeline-item.ITILTask:not(#new-TicketTask-block) .bg-transparent')->innerText();
        $this->assertStringContainsString('Done:', $badges_text);
    }

    /**
     * Test that PendingReason_Item is deleted when the status is changed by a rule
     * This test verifies that business rules can properly remove pending reasons
     */
    public function testPendingReasonDeletedWhenStatusChangedByRule(): void
    {
        $this->login();

        // Clean rule singleton to avoid interference from other tests
        \SingletonRuleList::getInstance("RuleTicket", 0)->load = 0;
        \SingletonRuleList::getInstance("RuleTicket", 0)->list = [];

        // Create a business rule that changes status when priority is set to 5
        $ruleticket = new \RuleTicket();
        $rulecrit   = new \RuleCriteria();
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add([
            'name'         => 'Test rule to change status from waiting to assigned',
            'match'        => 'AND',
            'is_active'    => 1,
            'sub_type'     => 'RuleTicket',
            'condition'    => \RuleTicket::ONUPDATE,
            'is_recursive' => 1,
        ]);
        $this->assertGreaterThan(0, $ruletid);

        // Add criterion: priority is 5 (very high)
        $crit_id = $rulecrit->add([
            'rules_id'  => $ruletid,
            'criteria'  => 'priority',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => 5,
        ]);
        $this->assertGreaterThan(0, $crit_id);

        // Add action: change status to ASSIGNED
        $action_id = $ruleaction->add([
            'rules_id'    => $ruletid,
            'action_type' => 'assign',
            'field'       => 'status',
            'value'       => CommonITILObject::ASSIGNED,
        ]);
        $this->assertGreaterThan(0, $action_id);

        // Create a ticket with WAITING status
        $ticket = new Ticket();
        $tickets_id = $ticket->add([
            'name'    => 'Test ticket for rule-based status change',
            'content' => 'This ticket will have its status changed by a rule',
            'status'  => CommonITILObject::WAITING,
        ]);
        $this->assertGreaterThan(0, $tickets_id);
        $this->assertTrue($ticket->getFromDB($tickets_id));

        // Attach a pending reason to the ticket
        $this->assertTrue(PendingReason_Item::createForItem($ticket, [
            'pendingreasons_id'           => getItemByTypeName(PendingReason::class, 'needupdate_pendingreason', true),
            'followup_frequency'          => DAY_TIMESTAMP,
            'followups_before_resolution' => 3,
        ]));

        // Verify that pending reason exists
        $pending_item = PendingReason_Item::getForItem($ticket);
        $this->assertNotFalse($pending_item);
        $this->assertEquals(CommonITILObject::WAITING, $ticket->fields['status']);

        // Update the ticket priority to trigger the rule
        // The rule should change status from WAITING to ASSIGNED
        $this->assertTrue(
            $ticket->update([
                'id'       => $tickets_id,
                'priority' => 5,
            ])
        );

        // Reload ticket to get updated data
        $this->assertTrue($ticket->getFromDB($tickets_id));

        // Verify that the status was changed by the rule
        $this->assertEquals(CommonITILObject::ASSIGNED, $ticket->fields['status']);

        // Verify that the pending reason was deleted
        $pending_item = PendingReason_Item::getForItem($ticket);
        $this->assertFalse($pending_item, 'PendingReason_Item should be deleted when status is changed by a rule');
    }
}
