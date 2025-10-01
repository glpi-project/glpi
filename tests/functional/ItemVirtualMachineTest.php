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
use Glpi\Asset\Capacity\HasVirtualMachineCapacity;
use Glpi\Features\Clonable;
use ItemVirtualMachine;
use Toolbox;

class ItemVirtualMachineTest extends DbTestCase
{
    public function testRelatedItemHasTab()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasVirtualMachineCapacity::class)]);

        $this->login(); // tab will be available only if corresponding right is available in the current session

        foreach ($CFG_GLPI['itemvirtualmachines_types'] as $itemtype) {
            $item = $this->createItem(
                $itemtype,
                $this->getMinimalCreationInput($itemtype)
            );

            $tabs = $item->defineAllTabs();
            $this->assertArrayHasKey('ItemVirtualMachine$1', $tabs, $itemtype);
        }
    }

    public function testRelatedItemCloneRelations()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasVirtualMachineCapacity::class)]);

        foreach ($CFG_GLPI['itemvirtualmachines_types'] as $itemtype) {
            if (!Toolbox::hasTrait($itemtype, Clonable::class)) {
                continue;
            }

            $item = \getItemForItemtype($itemtype);
            $this->assertContains(ItemVirtualMachine::class, $item->getCloneRelations(), $itemtype);
        }
    }

    public function testCreateAndGet()
    {
        $this->login();

        $obj = new ItemVirtualMachine();
        $uuid = 'c37f7ce8-af95-4676-b454-0959f2c5e162';

        // Add
        $computer = getItemByTypeName('Computer', '_test_pc01');
        $this->assertInstanceOf('\Computer', $computer);

        $this->assertGreaterThan(
            0,
            $id = $obj->add([
                'itemtype'     => 'Computer',
                'items_id'     => $computer->fields['id'],
                'name'         => 'Virtu Hall',
                'uuid'         => $uuid,
                'vcpu'         => 1,
                'ram'          => 1024,
            ])
        );
        $this->assertTrue($obj->getFromDB($id));
        $this->assertSame($uuid, $obj->fields['uuid']);

        $this->assertFalse($obj->findVirtualMachine(['itemtype' => \Computer::getType(), 'name' => 'Virtu Hall']));
        //a machine exists yet
        $this->assertFalse($obj->findVirtualMachine(['itemtype' => \Computer::getType(), 'uuid' => $uuid]));

        $this->assertGreaterThan(
            0,
            $cid = $computer->add([
                'name'         => 'Virtu Hall',
                'uuid'         => $uuid,
                'entities_id'  => 0,
            ])
        );

        $this->assertEquals($cid, $obj->findVirtualMachine(['itemtype' => \Computer::getType(),'uuid' => $uuid]));
    }
}
