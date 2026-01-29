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

class Group_UserTest extends DbTestCase
{
    public function testGetGroupUsers()
    {
        $this->login();
        $group = new \Group();
        $gid = (int) $group->add([
            'name' => 'Test group',
        ]);
        $this->assertGreaterThan(0, $gid);

        $uid1 = (int) getItemByTypeName('User', 'normal', true);
        $uid2 = (int) getItemByTypeName('User', 'tech', true);

        $group_user = new \Group_User();
        $this->assertGreaterThan(
            0,
            (int) $group_user->add([
                'groups_id' => $gid,
                'users_id'  => $uid1,
            ])
        );

        $this->assertGreaterThan(
            0,
            (int) $group_user->add([
                'groups_id'    => $gid,
                'users_id'     => $uid2,
                'is_manager'   => 1,
            ])
        );

        $group_users = \Group_User::getGroupUsers($gid);
        $this->assertCount(2, $group_users);

        $group_users = \Group_User::getGroupUsers($gid, ['is_manager' => 1]);
        $this->assertCount(1, $group_users);
        $this->assertSame($uid2, (int) $group_users[0]['id']);

        //cleanup
        $this->assertTrue($group->delete(['id' => $gid], true));

        $group_users = \Group_User::getGroupUsers($gid);
        $this->assertCount(0, $group_users);
    }

    public function testGetUserGroups()
    {
        $this->login();
        $uid = (int) getItemByTypeName('User', 'normal', true);

        $group = new \Group();
        $gid1 = (int) $group->add([
            'name' => 'Test group',
        ]);
        $this->assertGreaterThan(0, $gid1);

        $gid2 = (int) $group->add([
            'name' => 'Test group 2',
        ]);
        $this->assertGreaterThan(0, $gid2);

        $group_user = new \Group_User();
        $this->assertGreaterThan(
            0,
            (int) $group_user->add([
                'groups_id' => $gid1,
                'users_id'  => $uid,
            ])
        );

        $this->assertGreaterThan(
            0,
            (int) $group_user->add([
                'groups_id'    => $gid2,
                'users_id'     => $uid,
                'is_manager'   => 1,
            ])
        );

        $group_users = \Group_User::getUserGroups($uid);
        $this->assertCount(2, $group_users);

        $group_users = \Group_User::getUserGroups($uid, ['glpi_groups_users.is_manager' => 1]);
        $this->assertCount(1, $group_users);
        $this->assertSame($gid2, (int) $group_users[0]['id']);

        //cleanup
        $this->assertTrue($group_user->deleteByCriteria(['users_id' => $uid]));

        $group_users = \Group_User::getUserGroups($uid);
        $this->assertCount(0, $group_users);
    }

    public function testgetListForItemParams()
    {
        $user = getItemByTypeName('User', TU_USER);
        $group_user = new \Group_User();

        $expected = [];
        $this->assertSame($expected, iterator_to_array($group_user->getListForItem($user)));

        //Now, add groups to user
        $group = new \Group();
        $gid1 = (int) $group->add([
            'name' => 'Test group',
        ]);
        $this->assertGreaterThan(0, $gid1);

        $gid2 = (int) $group->add([
            'name' => 'Test group 2',
        ]);
        $this->assertGreaterThan(0, $gid2);

        $this->assertGreaterThan(
            0,
            (int) $group_user->add([
                'groups_id' => $gid1,
                'users_id'  => $user->getID(),
            ])
        );

        $this->assertGreaterThan(
            0,
            (int) $group_user->add([
                'groups_id'    => $gid2,
                'users_id'     => $user->getID(),
                'is_manager'   => 1,
            ])
        );

        $list_items = iterator_to_array($group_user->getListForItem($user));
        $this->assertCount(2, $list_items);
        $this->assertArrayHasKey($gid1, $list_items);
        $this->assertArrayHasKey($gid2, $list_items);

        $this->assertArrayHasKey('linkid', $list_items[$gid1]);
        $this->assertSame('Test group', $list_items[$gid1]['name']);

        $this->assertArrayHasKey('linkid', $list_items[$gid2]);
        $this->assertSame('Test group 2', $list_items[$gid2]['name']);

        $this->assertTrue($group->getFromDB($gid2));
        $list_items = iterator_to_array($group_user->getListForItem($group));
        $this->assertCount(1, $list_items);
        $this->assertArrayHasKey($user->getID(), $list_items);

        $this->assertArrayHasKey('linkid', $list_items[$user->getID()]);
        $this->assertArrayHasKey('is_manager', $list_items[$user->getID()]);
        $this->assertArrayHasKey('is_userdelegate', $list_items[$user->getID()]);
        $this->assertSame(TU_USER, $list_items[$user->getID()]['name']);

        $this->assertSame(2, $group_user->countForItem($user));
        $this->assertSame(1, $group_user->countForItem($group));
    }

    public function testIsUserInGroup()
    {
        $group = new \Group();
        // Add a group
        $groups_id = $group->add([
            'name' => __METHOD__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);
        $this->assertGreaterThan(0, (int) $groups_id);
        $this->assertTrue($group->getFromDB($groups_id));

        $group_user = new \Group_User();
        $group_users_id = $group_user->add([
            'groups_id'  => $groups_id,
            'users_id'   => getItemByTypeName('User', 'tech', true),
            'is_dynamic' => 0,
        ]);
        $this->assertGreaterThan(0, (int) $group_users_id);
        $this->assertTrue($group_user->getFromDB($group_users_id));
        $this->assertTrue(\Group_User::isUserInGroup(getItemByTypeName('User', 'tech', true), $groups_id));
        $this->assertFalse(\Group_User::isUserInGroup(getItemByTypeName('User', 'glpi', true), $groups_id));
    }

    public function testDeleteUserDefaultGroup()
    {
        $group = new \Group();
        $gid = (int) $group->add([
            'name' => 'Test group',
        ]);
        $this->assertGreaterThan(0, $gid);

        $user = getItemByTypeName('User', 'tech');

        $group_user = new \Group_User();
        $this->assertGreaterThan(
            0,
            (int) $group_user->add(
                [
                    'groups_id' => $gid,
                    'users_id'  => $user->getID(),
                ]
            )
        );

        $this->assertTrue(
            $user->update(
                [
                    'id' => $user->getID(),
                    'groups_id' => $gid,
                ]
            )
        );

        $group_users = \Group_User::getGroupUsers($gid);
        $this->assertCount(1, $group_users);
        $this->assertSame($user->getID(), (int) $group_users[0]['id']);

        //cleanup
        $this->assertTrue($group->delete(['id' => $gid], true));

        $group_users = \Group_User::getGroupUsers($gid);
        $this->assertCount(0, $group_users);

        $user = getItemByTypeName('User', 'tech');
        $this->assertEquals(0, $user->fields['groups_id']);
    }

    /**
     * Test getManagedGroupsIdsForUsers returns only groups with managers where user is member.
     * Tests both int and array parameter types return same result.
     *
     * User in 3 groups: 2 with managers, 1 without. Should return only the 2 managed groups.
     */
    public function testGetManagedGroupsIdsForUsers()
    {
        // --- Arrange - user in 3 groups, 2 with managers, 1 without
        $group_managed1 = $this->createItem(\Group::class, $this->getMinimalCreationInput(\Group::class));
        $group_managed2 = $this->createItem(\Group::class, $this->getMinimalCreationInput(\Group::class));
        $group_unmanaged = $this->createItem(\Group::class, $this->getMinimalCreationInput(\Group::class));

        $manager1 = getItemByTypeName(\User::class, 'tech');
        $manager2 = getItemByTypeName(\User::class, 'glpi');
        $user = getItemByTypeName(\User::class, 'normal');

        $this->createItems(\Group_User::class, [
            // Group 1 with manager
            ['groups_id' => $group_managed1->getID(), 'users_id' => $manager1->getID(), 'is_manager' => 1],
            ['groups_id' => $group_managed1->getID(), 'users_id' => $user->getID(), 'is_manager' => 0],
            // Group 2 with manager
            ['groups_id' => $group_managed2->getID(), 'users_id' => $manager2->getID(), 'is_manager' => 1],
            ['groups_id' => $group_managed2->getID(), 'users_id' => $user->getID(), 'is_manager' => 0],
            // Group 3 without manager
            ['groups_id' => $group_unmanaged->getID(), 'users_id' => $user->getID(), 'is_manager' => 0],
            ['groups_id' => $group_unmanaged->getID(), 'users_id' => $manager2->getID(), 'is_manager' => 0],
        ]);

        // --- Act
        // test with int parameter
        $managed_groups_int = \Group_User::getManagedGroupsIdsForUsers($user->getID());
        $managed_groups_array = \Group_User::getManagedGroupsIdsForUsers([$user->getID()]);

        // --- Assert - both return the 2 managed groups
        $expected = [$group_managed1->getID(), $group_managed2->getID()];
        $this->assertEqualsCanonicalizing($expected, $managed_groups_int);
        $this->assertEqualsCanonicalizing($expected, $managed_groups_array);
    }

    /**
     * Test getManagedGroupsIdsForUsers with array of user IDs parameter.
     *
     * Multiple users in different managed groups. Should return union of all managed groups.
     */
    public function testGetManagedGroupsIdsForUsersWithArrayParameter()
    {
        // --- Arrange - user1 in groupA, user2 in groupB, both groups have managers
        $groupA = $this->createItem(\Group::class, $this->getMinimalCreationInput(\Group::class));
        $groupB = $this->createItem(\Group::class, $this->getMinimalCreationInput(\Group::class));

        $user1 = getItemByTypeName(\User::class, 'normal');
        $user2 = getItemByTypeName(\User::class, 'tech');
        $managerA = getItemByTypeName(\User::class, 'glpi');
        $managerB = getItemByTypeName(\User::class, 'post-only');

        $this->createItems(\Group_User::class, [
            ['groups_id' => $groupA->getID(), 'users_id' => $managerA->getID(), 'is_manager' => 1],
            ['groups_id' => $groupA->getID(), 'users_id' => $user1->getID(), 'is_manager' => 0],
            ['groups_id' => $groupB->getID(), 'users_id' => $managerB->getID(), 'is_manager' => 1],
            ['groups_id' => $groupB->getID(), 'users_id' => $user2->getID(), 'is_manager' => 0],
        ]);

        // --- Act
        $managed_groups = \Group_User::getManagedGroupsIdsForUsers([$user1->getID(), $user2->getID()]);

        // --- Assert
        $this->assertEqualsCanonicalizing(
            [$groupA->getID(), $groupB->getID()],
            $managed_groups
        );
    }

    /**
     * Test getManagedGroupsIdsForUsers with multiple managers in same group.
     */
    public function testGetManagedGroupsIdsForUsersWithMultipleManagersInSameGroup()
    {
        // --- Arrange - group with 3 managers
        $group = $this->createItem(\Group::class, $this->getMinimalCreationInput(\Group::class));

        $manager1 = getItemByTypeName(\User::class, 'tech');
        $manager2 = getItemByTypeName(\User::class, 'glpi');
        $manager3 = getItemByTypeName(\User::class, 'post-only');
        $user = getItemByTypeName(\User::class, 'normal');

        $this->createItems(\Group_User::class, [
            ['groups_id' => $group->getID(), 'users_id' => $manager1->getID(), 'is_manager' => 1],
            ['groups_id' => $group->getID(), 'users_id' => $manager2->getID(), 'is_manager' => 1],
            ['groups_id' => $group->getID(), 'users_id' => $manager3->getID(), 'is_manager' => 1],
            ['groups_id' => $group->getID(), 'users_id' => $user->getID(), 'is_manager' => 0],
        ]);

        // --- Act
        $managed_groups = \Group_User::getManagedGroupsIdsForUsers($user->getID());

        // --- Assert
        $this->assertEqualsCanonicalizing([$group->getID()], $managed_groups);
    }
}
