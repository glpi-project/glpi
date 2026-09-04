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

    public function testGetDataForGroupTreeParameter()
    {
        $this->login();

        $parent = $this->createItem(\Group::class, ['name' => 'Parent group']);
        $child  = $this->createItem(\Group::class, ['name' => 'Child group', 'groups_id' => $parent->getID()]);
        $user_child_only = getItemByTypeName('User', 'normal', true);

        $this->createItem(\Group_User::class, ['groups_id' => $child->getID(), 'users_id' => $user_child_only]);

        // Default / tree = 0: the parent only sees its own direct members,
        // a member of the child group is not listed here.
        $members = [];
        $ids = [];
        \Group_User::getDataForGroup($parent, $members, $ids, '', 0, false);
        $this->assertNotContains($user_child_only, array_column($members, 'id'));

        // tree = 1 (explicit opt-in): the parent's list also includes
        // members of its sub-groups.
        $members = [];
        $ids = [];
        \Group_User::getDataForGroup($parent, $members, $ids, '', 1, false);
        $this->assertContains($user_child_only, array_column($members, 'id'));
    }

    public function testGetDataForGroupTreeAndManagerFilterCombined()
    {
        $this->login();

        $parent = $this->createItem(\Group::class, ['name' => 'Parent group']);
        $child  = $this->createItem(\Group::class, ['name' => 'Child group', 'groups_id' => $parent->getID()]);
        $manager_in_child     = getItemByTypeName('User', 'normal', true);
        $non_manager_in_child = getItemByTypeName('User', 'post-only', true);

        $this->createItem(\Group_User::class, ['groups_id' => $child->getID(), 'users_id' => $manager_in_child, 'is_manager' => 1]);
        $this->createItem(\Group_User::class, ['groups_id' => $child->getID(), 'users_id' => $non_manager_in_child, 'is_manager' => 0]);

        // tree = 0: the "Manager" filter has nothing to work on, since
        // sub-group members are not part of the parent's list at all.
        $members = [];
        $ids = [];
        \Group_User::getDataForGroup($parent, $members, $ids, ['manager' => '1'], 0, false);
        $this->assertCount(0, $members);

        // tree = 1: the "Manager" filter must correctly keep only the
        // sub-group member who actually is a manager of that sub-group.
        $members = [];
        $ids = [];
        \Group_User::getDataForGroup($parent, $members, $ids, ['manager' => '1'], 1, false);
        $listed_ids = array_column($members, 'id');
        $this->assertContains($manager_in_child, $listed_ids);
        $this->assertNotContains($non_manager_in_child, $listed_ids);
    }

    public function testCountForItemAlwaysCountsDirectMembersOnly()
    {
        $this->login();

        $parent = $this->createItem(\Group::class, ['name' => 'Parent group']);
        $child  = $this->createItem(\Group::class, ['name' => 'Child group', 'groups_id' => $parent->getID()]);
        $uid = getItemByTypeName('User', 'normal', true);
        $group_user = $this->createItem(\Group_User::class, ['groups_id' => $child->getID(), 'users_id' => $uid]);

        unset($_REQUEST['tree']);

        // The tab badge cannot reflect the "tree" toggle live, so it must
        // always reflect direct membership only, regardless of its value.
        $_SESSION['glpi_saved'][\Group_User::class]['tree'] = 1;
        $this->assertSame(0, $group_user->countForItem($parent));

        unset($_SESSION['glpi_saved'][\Group_User::class]['tree']);
    }

    public function testShowForGroupRendersTreeToggle()
    {
        $this->login();

        $entities_id = getItemByTypeName('Entity', '_test_root_entity', true);

        $parent = $this->createItem(\Group::class, ['name' => 'Parent group', 'entities_id' => $entities_id]);
        $child  = $this->createItem(\Group::class, ['name' => 'Child group', 'groups_id' => $parent->getID(), 'entities_id' => $entities_id]);

        $uid = getItemByTypeName('User', 'normal', true);
        $this->createItem(\Group_User::class, ['groups_id' => $child->getID(), 'users_id' => $uid]);

        unset($_REQUEST['tree'], $_SESSION['glpi_saved'][\Group_User::class]['tree']);

        ob_start();
        \Group_User::showForGroup($parent);
        $output = ob_get_clean();

        // The "include sub-groups" toggle must be rendered since the group
        // has a child.
        $this->assertStringContainsString("name='tree'", $output);

        // A group with no children must not display the toggle at all.
        $leaf = $this->createItem(\Group::class, ['name' => 'Leaf group', 'entities_id' => $entities_id]);
        ob_start();
        \Group_User::showForGroup($leaf);
        $output = ob_get_clean();
        $this->assertStringNotContainsString("name='tree'", $output);
    }

    public function testTreeToggleReloadPreservesActiveFilters()
    {
        $this->login();

        $entities_id = getItemByTypeName('Entity', '_test_root_entity', true);
        $parent = $this->createItem(\Group::class, ['name' => 'PF Parent', 'entities_id' => $entities_id]);
        $this->createItem(\Group::class, ['name' => 'PF Child', 'groups_id' => $parent->getID(), 'entities_id' => $entities_id]);

        unset($_REQUEST['tree'], $_SESSION['glpi_saved'][\Group_User::class]['tree']);

        // No active filter: the toggle's reload must not append a stray '&'.
        $_GET['filters'] = [];
        ob_start();
        \Group_User::showForGroup($parent);
        $output = ob_get_clean();
        $this->assertMatchesRegularExpression(
            '/reloadTab\(&quot;start=0&amp;tree=&quot;\+this\.value\+&quot;&quot;\)/',
            $output
        );

        // An active "Manager" filter must be carried over into the toggle's
        // reload string, otherwise switching the toggle would silently reset it.
        $_GET['filters'] = ['manager' => '1'];
        ob_start();
        \Group_User::showForGroup($parent);
        $output = ob_get_clean();
        $this->assertStringContainsString('filters%5Bmanager%5D=1', $output);

        unset($_GET['filters']);
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
}
