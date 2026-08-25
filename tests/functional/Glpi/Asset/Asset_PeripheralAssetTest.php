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

namespace tests\units\Glpi\Asset;

use Computer;
use Glpi\Asset\Asset_PeripheralAsset;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasPeripheralAssetsCapacity;
use Glpi\Features\Clonable;
use Glpi\Tests\DbTestCase;
use Monitor;
use Peripheral;
use Toolbox;

class Asset_PeripheralAssetTest extends DbTestCase
{
    public function testRelatedItemHasTab()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasPeripheralAssetsCapacity::class)]);

        $this->login(); // tab will be available only if corresponding right is available in the current session

        foreach ($CFG_GLPI['directconnect_types'] as $itemtype) {
            $item = $this->createItem(
                $itemtype,
                $this->getMinimalCreationInput($itemtype)
            );

            $tabs = $item->defineAllTabs();
            $this->assertArrayHasKey('Glpi\\Asset\\Asset_PeripheralAsset$1', $tabs, $itemtype);
        }
    }

    public function testRelatedItemCloneRelations()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasPeripheralAssetsCapacity::class)]);

        foreach ($CFG_GLPI['directconnect_types'] as $itemtype) {
            if (!Toolbox::hasTrait($itemtype, Clonable::class)) {
                continue;
            }

            $item = \getItemForItemtype($itemtype);
            $this->assertContains(Asset_PeripheralAsset::class, $item->getCloneRelations(), $itemtype);
        }
    }

    public function testUnglobalizeReturnsBoolean(): void
    {
        $monitor = $this->createItem(
            Monitor::class,
            [
                'name' => 'Test Monitor',
                'entities_id' => $this->getTestRootEntity(true),
                'is_global' => 1,
            ]
        );

        $result = $monitor->unglobalize();

        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    public function testDeletePeripheralDoesNotCallCleanRelationData(): void
    {
        $computer = $this->createItem(
            Computer::class,
            [
                'name'   => 'Le PC',
                'serial' => 'qqzder45',
                'entities_id' => 0,
            ]
        );

        $periph = $this->createItem(
            Peripheral::class,
            [
                'name' => 'La Souris',
                'serial' => '12345',
                'entities_id'  => 0,
            ]
        );

        $relation = $this->createItem(
            Asset_PeripheralAsset::class,
            [
                'itemtype_asset' => 'Computer',
                'items_id_asset' => $computer->getID(),
                'itemtype_peripheral' => 'Peripheral',
                'items_id_peripheral' => $periph->getID(),
            ]
        );
        $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];

        $this->assertTrue($periph->delete(['id' => $periph->getID()], force: true));

        $this->assertTrue($_SESSION['MESSAGE_AFTER_REDIRECT'] === []);
        $this->assertFalse((new Asset_PeripheralAsset())->getFromDB($relation->getID()));
    }

    public function testUnavailablePeripheralsForAsset(): void
    {
        $this->login();

        $entity_id = $this->getTestRootEntity(true);
        $computer_1 = $this->createItem(Computer::class, [
            'name' => 'Computer 1',
            'entities_id' => $entity_id,
        ]);
        $computer_2 = $this->createItem(Computer::class, [
            'name' => 'Computer 2',
            'entities_id' => $entity_id,
        ]);
        $global_on_other_computer = $this->createItem(Peripheral::class, [
            'name' => 'Global peripheral on other computer',
            'entities_id' => $entity_id,
            'is_global' => 1,
        ]);
        $global_on_current_computer = $this->createItem(Peripheral::class, [
            'name' => 'Global peripheral on current computer',
            'entities_id' => $entity_id,
            'is_global' => 1,
        ]);
        $non_global_on_other_computer = $this->createItem(Peripheral::class, [
            'name' => 'Non-global peripheral on other computer',
            'entities_id' => $entity_id,
            'is_global' => 0,
        ]);

        foreach ([
            [$computer_1, $global_on_other_computer],
            [$computer_1, $non_global_on_other_computer],
            [$computer_2, $global_on_current_computer],
        ] as [$computer, $peripheral]) {
            $this->createItem(Asset_PeripheralAsset::class, [
                'itemtype_asset' => Computer::class,
                'items_id_asset' => $computer->getID(),
                'itemtype_peripheral' => Peripheral::class,
                'items_id_peripheral' => $peripheral->getID(),
            ]);
        }

        $unavailable = iterator_to_array($this->callPrivateMethod(
            new Asset_PeripheralAsset(),
            'getUnavailablePeripherals',
            $computer_2,
            Peripheral::class
        ));
        $unavailable_ids = array_column($unavailable, 'id');

        $this->assertNotContains($global_on_other_computer->getID(), $unavailable_ids);
        $this->assertContains($global_on_current_computer->getID(), $unavailable_ids);
        $this->assertContains($non_global_on_other_computer->getID(), $unavailable_ids);
    }
}
