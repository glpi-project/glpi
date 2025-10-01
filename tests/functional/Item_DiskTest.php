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

use DbTestCase;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasVolumesCapacity;
use Glpi\Features\Clonable;
use Item_Disk;
use Toolbox;

class Item_DiskTest extends DbTestCase
{
    public function testRelatedItemHasTab()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasVolumesCapacity::class)]);

        $this->login(); // tab will be available only if corresponding right is available in the current session

        foreach ($CFG_GLPI['disk_types'] as $itemtype) {
            $item = $this->createItem(
                $itemtype,
                $this->getMinimalCreationInput($itemtype)
            );

            $tabs = $item->defineAllTabs();
            $this->assertArrayHasKey('Item_Disk$1', $tabs, $itemtype);
        }
    }

    public function testRelatedItemCloneRelations()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasVolumesCapacity::class)]);

        foreach ($CFG_GLPI['disk_types'] as $itemtype) {
            if (!Toolbox::hasTrait($itemtype, Clonable::class)) {
                continue;
            }

            $item = \getItemForItemtype($itemtype);
            $this->assertContains(Item_Disk::class, $item->getCloneRelations(), $itemtype);
        }
    }

    public function testCreate()
    {
        $this->login();

        $obj = new Item_Disk();

        // Add
        $computer = getItemByTypeName('Computer', '_test_pc01');
        $this->assertInstanceOf('\Computer', $computer);

        $this->assertGreaterThan(
            0,
            $id = (int) $obj->add([
                'itemtype'     => $computer->getType(),
                'items_id'     => $computer->fields['id'],
                'mountpoint'   => '/',
            ])
        );
        $this->assertTrue($obj->getFromDB($id));
        $this->assertSame('/', $obj->fields['mountpoint']);
    }

    public function testUpdate()
    {
        $this->login();

        $obj = new Item_Disk();

        // Add
        $computer = getItemByTypeName('Computer', '_test_pc01');
        $this->assertInstanceOf('\Computer', $computer);

        $this->assertGreaterThan(
            0,
            $id = (int) $obj->add([
                'itemtype'     => $computer->getType(),
                'items_id'     => $computer->fields['id'],
                'mountpoint'   => '/',
            ])
        );
        $this->assertTrue($obj->getFromDB($id));
        $this->assertSame('/', $obj->fields['mountpoint']);

        $this->assertTrue($obj->update([
            'id'           => $id,
            'mountpoint'   => '/mnt',
        ]));
        $this->assertTrue($obj->getFromDB($id));
        $this->assertSame('/mnt', $obj->fields['mountpoint']);
    }

    public function testDelete()
    {
        $this->login();

        $obj = new Item_Disk();

        // Add
        $computer = getItemByTypeName('Computer', '_test_pc01');
        $this->assertInstanceOf('\Computer', $computer);

        $this->assertGreaterThan(
            0,
            $id = (int) $obj->add([
                'itemtype'     => $computer->getType(),
                'items_id'     => $computer->fields['id'],
                'mountpoint'   => '/',
            ])
        );
        $this->assertTrue($obj->getFromDB($id));
        $this->assertSame('/', $obj->fields['mountpoint']);

        $this->assertTrue(
            $obj->delete([
                'id'  => $id,
            ])
        );
        $this->assertTrue($obj->getFromDB($id)); //it's always in DB but with is_deleted = 1
        $this->assertSame(1, $obj->fields['is_deleted']);
    }
}
