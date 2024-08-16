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

/* Test for inc/consumable.class.php */

class ConsumableTest extends \DbTestCase
{
    /**
     * Test "out" and "back to stock" functions.
     * Test "back" to stock whend linked user or group is deleted.
     */
    public function testOutAndBackToStock()
    {

        $consumable = new \Consumable();

        $consumable_item = new \ConsumableItem();
        $cu_id = (int)$consumable_item->add([
            'name' => 'Test consumable item'
        ]);
        $this->assertGreaterThan(0, $cu_id);

        $group = new \Group();
        $gid1 = (int)$group->add([
            'name' => 'Test group 1'
        ]);
        $this->assertGreaterThan(0, $gid1);
        $gid2 = (int)$group->add([
            'name' => 'Test group 2'
        ]);
        $this->assertGreaterThan(0, $gid2);

        $user = new \User();
        $uid = (int)$user->add([
            'name' => 'User group'
        ]);
        $this->assertGreaterThan(0, $uid);

        $c_ids = [];
        for ($i = 0; $i < 20; $i++) {
            $c_id = (int)$consumable->add([
                'name'               => 'Test consumable',
                'consumableitems_id' => $cu_id,
            ]);
            $this->assertGreaterThan(0, $c_id);

            $c_ids[] = $c_id;

            // Give 1/4 of consumable pool to test group 1
            if ($i % 4 === 0) {
                 $consumable->out($c_id, 'Group', $gid1);
            }
            // Give 1/4 of consumable pool to test group 2
            if ($i % 4 === 1) {
                $consumable->out($c_id, 'Group', $gid2);
            }
            // Give 1/4 of consumable pool to test user
            if ($i % 4 === 2) {
                $consumable->out($c_id, 'User', $uid);
            }
        }

        // Test counters
        $this->assertEquals(20, $consumable->getTotalNumber($cu_id));
        $this->assertEquals(5, $consumable->getUnusedNumber($cu_id));
        $this->assertEquals(15, $consumable->getOldNumber($cu_id));

        // Test back to stock
        $this->assertTrue($consumable->backToStock(['id' => $c_ids[0]]));
        $this->assertEquals(6, $consumable->getUnusedNumber($cu_id));
        $this->assertEquals(14, $consumable->getOldNumber($cu_id));

        // Test forced back to stock by removal of group (not replaced)
        $this->assertTrue($group->delete(['id' => $gid1, true]));
        $this->assertEquals(10, $consumable->getUnusedNumber($cu_id));
        $this->assertEquals(10, $consumable->getOldNumber($cu_id));
        $this->assertEquals(
            0,
            countElementsInTable(
                $consumable->getTable(),
                [
                    'consumableitems_id' => $cu_id,
                    'itemtype'           => 'Group',
                    'items_id'           => $gid1,
                ]
            )
        );

        // Test replacement of a group (no back to stock)
        $this->assertTrue($group->delete(['id' => $gid2, '_replace_by' => $gid1], true));
        $this->assertEquals(10, $consumable->getUnusedNumber($cu_id));
        $this->assertEquals(10, $consumable->getOldNumber($cu_id));
        $this->assertEquals(
            0,
            countElementsInTable(
                $consumable->getTable(),
                [
                    'consumableitems_id' => $cu_id,
                    'itemtype'           => 'Group',
                    'items_id'           => $gid2,
                ]
            )
        );
        $this->assertEquals(
            5,
            countElementsInTable(
                $consumable->getTable(),
                [
                    'consumableitems_id' => $cu_id,
                    'itemtype'           => 'Group',
                    'items_id'           => $gid1,
                ]
            )
        );

        // Test forced back to stock by removal of user (not replaced)
        $this->assertTrue($user->delete(['id' => $uid], true));
        $this->assertEquals(15, $consumable->getUnusedNumber($cu_id));
        $this->assertEquals(5, $consumable->getOldNumber($cu_id));
        $this->assertEquals(
            0,
            countElementsInTable(
                $consumable->getTable(),
                [
                    'consumableitems_id' => $cu_id,
                    'itemtype'           => 'User',
                    'items_id'           => $uid,
                ]
            )
        );
    }

    public function testInfocomInheritance()
    {
        $consumable = new \Consumable();

        $consumable_item = new \ConsumableItem();
        $cu_id = (int)$consumable_item->add([
            'name' => 'Test consumable item'
        ]);
        $this->assertGreaterThan(0, $cu_id);

        $infocom = new \Infocom();
        $infocom_id = (int) $infocom->add([
            'itemtype'  => \ConsumableItem::getType(),
            'items_id'  => $cu_id,
            'buy_date'  => '2020-10-21',
            'value'     => '500'
        ]);
        $this->assertGreaterThan(0, $infocom_id);

        $consumable_id = $consumable->add([
            'consumableitems_id' => $cu_id
        ]);
        $this->assertGreaterThan(0, $consumable_id);

        $infocom2 = new \Infocom();
        $infocom2_id = (int) $infocom2->getFromDBByCrit([
            'itemtype'  => \Consumable::getType(),
            'items_id'  => $consumable_id
        ]);
        $this->assertGreaterThan(0, $infocom2_id);
        $this->assertEquals(
            $infocom->fields['buy_date'],
            $infocom2->fields['buy_date']
        );
        $this->assertEquals(
            $infocom->fields['value'],
            $infocom2->fields['value']
        );
    }
}
