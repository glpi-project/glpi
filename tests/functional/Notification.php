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

use Config;
use Contract;
use DbTestCase;
use Group;
use Group_User;
use NotificationEvent;
use NotificationTarget;
use QueuedNotification;
use User;

/* Test for inc/notification.class.php */

class Notification extends DbTestCase
{
    public function testGetMailingSignature()
    {
        global $CFG_GLPI;

        $this->login();

        $root    = getItemByTypeName('Entity', 'Root entity', true);
        $parent  = getItemByTypeName('Entity', '_test_root_entity', true);
        $child_1 = getItemByTypeName('Entity', '_test_child_1', true);
        $child_2 = getItemByTypeName('Entity', '_test_child_2', true);

        $CFG_GLPI['mailing_signature'] = 'global_signature';

        $this->string(\Notification::getMailingSignature($parent))->isEqualTo("global_signature");
        $this->string(\Notification::getMailingSignature($child_1))->isEqualTo("global_signature");
        $this->string(\Notification::getMailingSignature($child_2))->isEqualTo("global_signature");

        $entity = new \Entity();
        $this->boolean($entity->update([
            'id'                => $root,
            'mailing_signature' => "signature_root",
        ]))->isTrue();

        $this->string(\Notification::getMailingSignature($parent))->isEqualTo("signature_root");
        $this->string(\Notification::getMailingSignature($child_1))->isEqualTo("signature_root");
        $this->string(\Notification::getMailingSignature($child_2))->isEqualTo("signature_root");

        $this->boolean($entity->update([
            'id'                => $parent,
            'mailing_signature' => "signature_parent",
        ]))->isTrue();

        $this->string(\Notification::getMailingSignature($parent))->isEqualTo("signature_parent");
        $this->string(\Notification::getMailingSignature($child_1))->isEqualTo("signature_parent");
        $this->string(\Notification::getMailingSignature($child_2))->isEqualTo("signature_parent");

        $this->boolean($entity->update([
            'id'                => $child_1,
            'mailing_signature' => "signature_child_1",
        ]))->isTrue();

        $this->string(\Notification::getMailingSignature($parent))->isEqualTo("signature_parent");
        $this->string(\Notification::getMailingSignature($child_1))->isEqualTo("signature_child_1");
        $this->string(\Notification::getMailingSignature($child_2))->isEqualTo("signature_parent");

        $this->boolean($entity->update([
            'id'                => $child_2,
            'mailing_signature' => "signature_child_2",
        ]))->isTrue();

        $this->string(\Notification::getMailingSignature($parent))->isEqualTo("signature_parent");
        $this->string(\Notification::getMailingSignature($child_1))->isEqualTo("signature_child_1");
        $this->string(\Notification::getMailingSignature($child_2))->isEqualTo("signature_child_2");
    }

    /**
     * Data provider for the testEntityRestriction case
     *
     * @return iterable
     */
    protected function testEntityRestrictionProvider(): iterable
    {
        global $DB, $CFG_GLPI;

        $this->login();

        // Test users
        list($user_root, $user_sub) = $this->createItems(User::class, [
            [
                'name'         => "User_root_entity",
                '_useremails'  => [-1 => "user_root@teclib.com"],
                '_entities_id' => $this->getTestRootEntity(true),
                '_profiles_id' => 4,                                // Super admin
            ],
            [
                'name'         => "User_sub_entity",
                '_useremails'  => [-1 => "user_sub@teclib.com"],
                '_entities_id' => getItemByTypeName('Entity', '_test_child_1', true),
                '_profiles_id' => 4, // Super admin
            ],
        ]);

        // Put all our tests user into a single group so its easy to add them as
        // recipient of the test notification
        $group = $this->createItem(Group::class, [
            'name'        => "testEntityRestriction_group",
            'entities_id' => $this->getTestRootEntity(true),
            'is_recursive' => true,
        ]);
        $this->createItems(Group_User::class, [
            [
                'users_id'  => $user_root->getID(),
                'groups_id' => $group->getID(),
            ],
            [
                'users_id'  => $user_sub->getID(),
                'groups_id' => $group->getID(),
            ]
        ]);

        // Set up notifications
        $DB->update(\Notification::getTable(), ['is_active' => 0], [1]);
        $active_notification = countElementsInTable(\Notification::getTable(), ['is_active' => 1]);
        $this->integer($active_notification)->isEqualTo(0);

        // Enable notification
        $CFG_GLPI['notifications_mailing'] = true;
        $CFG_GLPI['use_notifications'] = true;

        // Find the "Contract end" notification and enable it
        $notification = getItemByTypeName(\Notification::class, "Contract End");
        $this->updateItem(\Notification::class, $notification->getID(), ['is_active' => 1]);

        // Clear any exisiting target then set our group target
        $DB->delete(NotificationTarget::getTable(), ['notifications_id' => $notification->getID()]);
        $this->createItem(NotificationTarget::class, [
            'notifications_id' => $notification->getID(),
            'items_id'         => $group->getID(),
            'type'             => \Notification::GROUP_TYPE,
        ]);

        // First test case: contract in the root entity with no recursion
        // It should only be visible for the first user
        $contract_root = $this->createItem('Contract', [
            'name'         => 'Contact',
            'entities_id'  => $this->getTestRootEntity(true),
            'is_recursive' => false,
        ]);
        yield [$contract_root, ["user_root@teclib.com"]];

        // Second test case: contract in the root entity with recursion
        // It should be visible for our two users
        $contract_root_and_children = $this->createItem('Contract', [
            'name'         => 'Contact',
            'entities_id'  => $this->getTestRootEntity(true),
            'is_recursive' => true,
        ]);
        yield [$contract_root_and_children, ["user_root@teclib.com", "user_sub@teclib.com"]];
    }

    /**
     * Test that entity restriction are applied correctly for notifications (a
     * user should only receive notification on items he is allowed to see)
     *
     * @dataProvider testEntityRestrictionProvider
     *
     * @param Contract $contract       Test subject on which the notification will be fired
     * @param string[] $expected_queue Array of expected emails
     *
     * @return void
     */
    public function testEntityRestriction(Contract $contract, array $expected_queue): void
    {
        global $DB;

        // Clear notification queue
        $DB->delete(QueuedNotification::getTable(), [1]);
        $queue_size = countElementsInTable(QueuedNotification::getTable());
        $this->integer($queue_size)->isEqualTo(0);

        // Raise fake notification
        NotificationEvent::raiseEvent('end', $contract, [
            'entities_id' => $this->getTestRootEntity(true),
            'items'       => [
                [
                    'id'                => $contract->getID(),
                    'name'              => $contract->fields['name'],
                    'num'               => $contract->fields['num'],
                    'comment'           => $contract->fields['comment'],
                    'accounting_number' => $contract->fields['accounting_number'],
                    'contracttypes_id'  => $contract->fields['contracttypes_id'],
                    'states_id'         => $contract->fields['states_id'],
                    'begin_date'        => $contract->fields['begin_date'],
                    'duration'          => $contract->fields['duration'],
                ]
            ],
        ]);

        // Validate notification queue size
        $queue = (new QueuedNotification())->find();
        $emails = array_column($queue, 'recipient');
        sort($emails);
        sort($expected_queue);
        $this->array($emails)->isEqualTo($expected_queue);
    }
}
