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
}
