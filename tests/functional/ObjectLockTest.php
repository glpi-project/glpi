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

use Computer;
use Entity;
use Glpi\DBAL\QueryExpression;
use Glpi\Tests\DbTestCase;
use Notification;
use NotificationEvent;
use ObjectLock;
use PHPUnit\Framework\Attributes\DataProvider;
use Profile_User;
use QueuedNotification;
use User;
use UserEmail;

class ObjectLockTest extends DbTestCase
{
    public function testGetFormURLWithID(): void
    {
        $computer = new Computer();
        $computer->add([
            'name' => 'Test Computer',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        $objectLock = new ObjectLock();
        $objectLock->add([
            'itemtype' => Computer::class,
            'items_id' => $computer->getID(),
            'users_id' => 1,
            'date' => date('Y-m-d H:i:s'),
        ]);

        $this->assertEquals(
            "/front/computer.form.php?id={$computer->getID()}",
            ObjectLock::getFormURLWithID($objectLock->getID(), false)
        );
    }

    public static function getEntityIDProvider(): iterable
    {
        yield 'no fields loaded' => [
            'entity_name'     => null,
            'override_fields' => null,
        ];

        yield 'non-existent locked item' => [
            'entity_name'     => null,
            'override_fields' => ['itemtype' => Computer::class, 'items_id' => PHP_INT_MAX],
        ];

        yield 'locked item in test root entity' => [
            'entity_name'     => '_test_root_entity',
            'override_fields' => null,
        ];

        yield 'locked item in child entity' => [
            'entity_name'     => '_test_child_1',
            'override_fields' => null,
        ];
    }

    #[DataProvider('getEntityIDProvider')]
    public function testGetEntityID(?string $entity_name, ?array $override_fields): void
    {
        $objectLock = new ObjectLock();

        if ($entity_name !== null) {
            $this->login();
            $entity_id = getItemByTypeName('Entity', $entity_name, true);
            $computer = $this->createItem(Computer::class, [
                'name'        => __FUNCTION__,
                'entities_id' => $entity_id,
            ]);
            $objectLock->fields = ['itemtype' => Computer::class, 'items_id' => $computer->getID()];
            $expected = $entity_id;
        } else {
            if ($override_fields !== null) {
                $objectLock->fields = $override_fields;
            }
            $expected = 0;
        }

        $this->assertEquals($expected, $objectLock->getEntityID());
    }

    public static function unlockNotificationScenarioProvider(): iterable
    {
        // Case 1: item and lock user in same child entity, requester in parent → sent
        yield 'child item+user, parent requester' => [
            'lock_user_profile_entity' => 'child',
            'lock_user_is_recursive'   => false,
            'locked_item_entity'       => 'child',
            'requester_entity'         => 'parent',
            'expect_notification'      => true,
        ];

        // Case 2: item and lock user in same parent entity, requester in parent → sent
        yield 'parent item+user, parent requester' => [
            'lock_user_profile_entity' => 'parent',
            'lock_user_is_recursive'   => false,
            'locked_item_entity'       => 'parent',
            'requester_entity'         => 'parent',
            'expect_notification'      => true,
        ];

        // Case 3: item in sub-child, lock user in child with recursive access, requester in parent → sent
        yield 'sub-child item, child recursive user, parent requester' => [
            'lock_user_profile_entity' => 'child',
            'lock_user_is_recursive'   => true,
            'locked_item_entity'       => 'sub_child',
            'requester_entity'         => 'parent',
            'expect_notification'      => true,
        ];

        // Case 4: item in sub-child, lock user in child with no recursive access, requester in parent → not sent
        yield 'sub-child item, child non-recursive user, parent requester' => [
            'lock_user_profile_entity' => 'child',
            'lock_user_is_recursive'   => false,
            'locked_item_entity'       => 'sub_child',
            'requester_entity'         => 'parent',
            'expect_notification'      => false,
        ];

        // Case 5: item in sub-child, lock user in child with recursive access, requester in sub-child → sent
        yield 'sub-child item, child recursive user, sub-child requester' => [
            'lock_user_profile_entity' => 'child',
            'lock_user_is_recursive'   => true,
            'locked_item_entity'       => 'sub_child',
            'requester_entity'         => 'sub_child',
            'expect_notification'      => true,
        ];

        // Case 6: item in sub-child, lock user in child with no recursive access, requester in sub-child → not sent
        yield 'sub-child item, child non-recursive user, sub-child requester' => [
            'lock_user_profile_entity' => 'child',
            'lock_user_is_recursive'   => false,
            'locked_item_entity'       => 'sub_child',
            'requester_entity'         => 'sub_child',
            'expect_notification'      => false,
        ];
    }

    #[DataProvider('unlockNotificationScenarioProvider')]
    public function testUnlockNotificationScenario(
        string $lock_user_profile_entity,
        bool $lock_user_is_recursive,
        string $locked_item_entity,
        string $requester_entity,
        bool $expect_notification
    ): void {
        global $CFG_GLPI, $DB;

        $this->login();

        $CFG_GLPI['use_notifications']     = 1;
        $CFG_GLPI['notifications_mailing'] = 1;

        $parent_id = getItemByTypeName('Entity', '_test_root_entity', true);
        $child_id  = getItemByTypeName('Entity', '_test_child_1', true);

        // Create the sub-child entity on demand (cases 3–6).
        $sub_child_id = null;
        $needs_sub_child = in_array('sub_child', [$lock_user_profile_entity, $locked_item_entity, $requester_entity], true);
        if ($needs_sub_child) {
            $sub_child = $this->createItem(Entity::class, [
                'name'        => 'sub_child_test',
                'entities_id' => $child_id,
            ]);
            $sub_child_id = $sub_child->getID();
        }

        $entity_ids = [
            'parent'    => $parent_id,
            'child'     => $child_id,
            'sub_child' => $sub_child_id,
        ];

        // Create the locking user with a profile scoped to the requested entity.
        $locking_user = $this->createItem(User::class, ['name' => 'locking_user', 'is_active' => 1]);
        $this->createItem(UserEmail::class, [
            'users_id'   => $locking_user->getID(),
            'email'      => 'locking_user@test-objectlock.com',
            'is_default' => 1,
        ]);
        $this->createItem(Profile_User::class, [
            'users_id'     => $locking_user->getID(),
            'profiles_id'  => getItemByTypeName('Profile', 'Technician', true),
            'entities_id'  => $entity_ids[$lock_user_profile_entity],
            'is_recursive' => (int) $lock_user_is_recursive,
        ]);

        // Create the locked item and the ObjectLock record.
        $computer = $this->createItem(Computer::class, [
            'name'        => 'locked_computer',
            'entities_id' => $entity_ids[$locked_item_entity],
        ]);
        $ol = $this->createItem(ObjectLock::class, [
            'itemtype' => Computer::class,
            'items_id' => $computer->getID(),
            'users_id' => $locking_user->getID(),
            'date'     => date('Y-m-d H:i:s'),
        ]);

        // Activate the "Request Unlock Items" notification.
        $unlock_notification = new Notification();
        $this->assertTrue(
            $unlock_notification->getFromDBByCrit(['itemtype' => ObjectLock::class, 'event' => 'unlock'])
        );
        $this->assertTrue(
            $unlock_notification->update(['id' => $unlock_notification->getID(), 'is_active' => 1])
        );

        // Switch the session to the requester's entity and clear the queue.
        $this->setEntity($entity_ids[$requester_entity], true);
        $DB->delete(QueuedNotification::getTable(), [new QueryExpression('true')]);

        NotificationEvent::raiseEvent('unlock', $ol);

        $results = (new QueuedNotification())->find([
            'recipient' => 'locking_user@test-objectlock.com',
            'event'     => 'unlock',
        ]);

        if ($expect_notification) {
            $this->assertCount(1, $results, 'Expected unlock notification to be queued for the locking user');
        } else {
            $this->assertCount(0, $results, 'Expected no unlock notification to be queued for the locking user');
        }
    }
}
