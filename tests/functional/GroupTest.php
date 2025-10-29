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

use Computer;
use DbTestCase;
use Group;
use Group_Item;
use Group_User;

class GroupTest extends DbTestCase
{
    /**
     * Test getDataItems() behavior with the $user parameter
     * When $user is false: should return only items assigned to the group
     * When $user is true: should return items assigned to the group AND to group members
     */
    public function testGetDataItemsUserParameter()
    {
        $this->login();

        // Setup: Create test data
        $group = new Group();
        $group_id = (int) $group->add([
            'name' => 'Test Tech Group',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'is_assign' => 1,
        ]);
        $this->assertGreaterThan(0, $group_id);
        $this->assertTrue($group->getFromDB($group_id));

        $user_id = (int) getItemByTypeName('User', 'tech', true);

        $group_user = new Group_User();
        $gu_id = (int) $group_user->add([
            'groups_id' => $group_id,
            'users_id' => $user_id,
        ]);
        $this->assertGreaterThan(0, $gu_id);

        // Computer A: assigned to the GROUP
        $computer_a = new Computer();
        $comp_a_id = (int) $computer_a->add([
            'name' => 'Computer A - Assigned to Group',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'groups_id_tech' => $group_id,
        ]);
        $this->assertGreaterThan(0, $comp_a_id);

        // Computer B: assigned to the USER (who is member of the group)
        $computer_b = new Computer();
        $comp_b_id = (int) $computer_b->add([
            'name' => 'Computer B - Assigned to User',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'users_id_tech' => $user_id,
        ]);
        $this->assertGreaterThan(0, $comp_b_id);

        // Test 1: With $user = false
        // Should return ONLY items assigned to the group (Computer A)
        $res_without_user = [];
        $count_without_user = $group->getDataItems(
            tech: true,
            tree: false,
            user: false,
            start: 0,
            res: $res_without_user
        );

        $this->assertEquals(1, $count_without_user, 'With user=false, should return only group items');
        $this->assertCount(1, $res_without_user);
        $this->assertEquals('Computer', $res_without_user[0]['itemtype']);
        $this->assertEquals($comp_a_id, $res_without_user[0]['items_id']);

        // Test 2: With $user = true
        // Should return items assigned to the group AND to group members (Computer A + B)
        $res_with_user = [];
        $count_with_user = $group->getDataItems(
            tech: true,
            tree: false,
            user: true,
            start: 0,
            res: $res_with_user
        );

        $this->assertEquals(2, $count_with_user, 'With user=true, should return group items + user items');
        $this->assertCount(2, $res_with_user);

        // Verify both computers are returned
        $returned_ids = array_column($res_with_user, 'items_id');
        $this->assertContains($comp_a_id, $returned_ids, 'Computer A should be included');
        $this->assertContains($comp_b_id, $returned_ids, 'Computer B should be included');
    }
}
