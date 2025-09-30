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

namespace tests\units\Glpi\Asset\Capacity;

use DbTestCase;
use DisplayPreference;
use Entity;
use Glpi\Asset\Asset;
use Glpi\Asset\Asset_PeripheralAsset;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasHistoryCapacity;
use Glpi\Asset\Capacity\HasNotepadCapacity;
use Glpi\Asset\Capacity\HasPeripheralAssetsCapacity;
use Log;
use Monitor;

class HasPeripheralAssetsCapacityTest extends DbTestCase
{
    public function testCapacityActivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasPeripheralAssetsCapacity::class),
                new Capacity(name: HasNotepadCapacity::class),
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasHistoryCapacity::class),
            ]
        );
        $classname_2  = $definition_2->getAssetClassName();
        $definition_3 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasPeripheralAssetsCapacity::class),
                new Capacity(name: HasHistoryCapacity::class),
            ]
        );
        $classname_3  = $definition_3->getAssetClassName();

        $has_capacity_mapping = [
            $classname_1 => true,
            $classname_2 => false,
            $classname_3 => true,
        ];

        foreach ($has_capacity_mapping as $classname => $has_capacity) {
            // Check that the class is globally registered
            if ($has_capacity) {
                $this->assertContains($classname, $CFG_GLPI['peripheralhost_types']);
                $this->assertContains($classname, Asset_PeripheralAsset::getPeripheralHostItemtypes());
            } else {
                $this->assertNotContains($classname, $CFG_GLPI['peripheralhost_types']);
                $this->assertNotContains($classname, Asset_PeripheralAsset::getPeripheralHostItemtypes());
            }

            // Check that the corresponding tab is present on items
            $item = $this->createItem($classname, ['name' => __FUNCTION__, 'entities_id' => $root_entity_id]);
            $this->login(); // must be logged in to get tabs list
            if ($has_capacity) {
                $this->assertArrayHasKey('Glpi\\Asset\\Asset_PeripheralAsset$1', $item->defineAllTabs());
            } else {
                $this->assertArrayNotHasKey('Glpi\\Asset\\Asset_PeripheralAsset$1', $item->defineAllTabs());
            }

            // Check that the related search options are available
            $so_keys = [
                1429, // Number of monitors
                1430, // Number of peripherals
                1431, // Number of printers
                1432, // Number of phones
            ];
            $options = $item->getOptions();
            foreach ($so_keys as $so_key) {
                if ($has_capacity) {
                    $this->assertArrayHasKey($so_key, $options);
                } else {
                    $this->assertArrayNotHasKey($so_key, $options);
                }
            }
        }
    }

    public function testCapacityDeactivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasPeripheralAssetsCapacity::class),
                new Capacity(name: HasHistoryCapacity::class),
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasPeripheralAssetsCapacity::class),
                new Capacity(name: HasHistoryCapacity::class),
            ]
        );
        $classname_2  = $definition_2->getAssetClassName();

        $item_1          = $this->createItem(
            $classname_1,
            [
                'name' => __FUNCTION__,
                'entities_id' => $root_entity_id,
            ]
        );
        $item_2          = $this->createItem(
            $classname_2,
            [
                'name' => __FUNCTION__,
                'entities_id' => $root_entity_id,
            ]
        );

        $relation_1 = $this->createItem(
            Asset_PeripheralAsset::class,
            [
                'itemtype_asset'      => $classname_1,
                'items_id_asset'      => $item_1->getID(),
                'itemtype_peripheral' => Monitor::class,
                'items_id_peripheral' => getItemByTypeName(Monitor::class, '_test_monitor_1', true),
            ]
        );
        $relation_2 = $this->createItem(
            Asset_PeripheralAsset::class,
            [
                'itemtype_asset'      => $classname_2,
                'items_id_asset'      => $item_2->getID(),
                'itemtype_peripheral' => Monitor::class,
                'items_id_peripheral' => getItemByTypeName(Monitor::class, '_test_monitor_1', true),
            ]
        );
        $displaypref_1   = $this->createItem(
            DisplayPreference::class,
            [
                'itemtype' => $classname_1,
                'num'      => 1429, // Number of monitors
                'users_id' => 0,
            ]
        );
        $displaypref_2   = $this->createItem(
            DisplayPreference::class,
            [
                'itemtype' => $classname_2,
                'num'      => 1429, // Number of monitors
                'users_id' => 0,
            ]
        );

        $item_1_logs_criteria = [
            'itemtype' => $classname_1,
        ];
        $item_2_logs_criteria = [
            'itemtype' => $classname_2,
        ];

        // Ensure relation, display preferences and logs exists, and class is registered to global config
        $this->assertInstanceOf(Asset_PeripheralAsset::class, Asset_PeripheralAsset::getById($relation_1->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_1->getID()));
        $this->assertEquals(2, countElementsInTable(Log::getTable(), $item_1_logs_criteria)); //create + add relation
        $this->assertInstanceOf(Asset_PeripheralAsset::class, Asset_PeripheralAsset::getById($relation_2->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_2->getID()));
        $this->assertEquals(2, countElementsInTable(Log::getTable(), $item_2_logs_criteria)); //create + add relation
        $this->assertContains($classname_1, $CFG_GLPI['peripheralhost_types']);
        $this->assertContains($classname_2, $CFG_GLPI['peripheralhost_types']);

        // Disable capacity and check that relations have been cleaned, and class is unregistered from global config
        $this->assertTrue($definition_1->update(['id' => $definition_1->getID(), 'capacities' => []]));
        $this->assertFalse(Asset_PeripheralAsset::getById($relation_1->getID()));
        $this->assertFalse(DisplayPreference::getById($displaypref_1->getID()));
        $this->assertEquals(0, countElementsInTable(Log::getTable(), $item_1_logs_criteria));
        $this->assertNotContains($classname_1, $CFG_GLPI['peripheralhost_types']);

        // Ensure relations, logs and global registration are preserved for other definition
        $this->assertInstanceOf(Asset_PeripheralAsset::class, Asset_PeripheralAsset::getById($relation_2->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_2->getID()));
        $this->assertEquals(2, countElementsInTable(Log::getTable(), $item_2_logs_criteria));
        $this->assertContains($classname_2, $CFG_GLPI['peripheralhost_types']);
    }

    public function testCloneAsset()
    {
        $definition = $this->initAssetDefinition(
            capacities: [new Capacity(name: HasPeripheralAssetsCapacity::class)]
        );
        $class = $definition->getAssetClassName();
        $entity = $this->getTestRootEntity(true);

        /** @var Asset $asset */
        $asset = $this->createItem(
            $class,
            [
                'name'        => 'Test asset',
                'entities_id' => $entity,
            ]
        );

        $this->createItem(
            Asset_PeripheralAsset::class,
            [
                'itemtype_asset'      => $class,
                'items_id_asset'      => $asset->getID(),
                'itemtype_peripheral' => Monitor::class,
                'items_id_peripheral' => getItemByTypeName(Monitor::class, '_test_monitor_1', true),
            ]
        );
        $this->assertGreaterThan(0, $clone_id = $asset->clone());
        $this->assertCount(
            1,
            getAllDataFromTable(Asset_PeripheralAsset::getTable(), [
                'itemtype_asset'      => $class,
                'items_id_asset'      => $clone_id,
                'itemtype_peripheral' => Monitor::class,
                'items_id_peripheral' => getItemByTypeName(Monitor::class, '_test_monitor_1', true),
            ])
        );
    }

    public function testIsUsed(): void
    {
        global $CFG_GLPI;

        $entity_id = $this->getTestRootEntity(true);

        foreach ($CFG_GLPI['directconnect_types'] as $peripheral_itemtype) {
            $definition = $this->initAssetDefinition(
                capacities: [new Capacity(name: HasPeripheralAssetsCapacity::class)]
            );
            $class = $definition->getAssetClassName();

            $asset = $this->createItem(
                $class,
                [
                    'name'        => __FUNCTION__,
                    'entities_id' => $entity_id,
                ]
            );

            $peripheral = $this->createItem(
                $peripheral_itemtype,
                [
                    'name'        => __FUNCTION__,
                    'entities_id' => $entity_id,
                ]
            );

            $capacity = new HasPeripheralAssetsCapacity();
            $this->assertFalse($capacity->isUsed($class));

            $this->createItem(
                Asset_PeripheralAsset::class,
                [
                    'itemtype_asset'      => $class,
                    'items_id_asset'      => $asset->getID(),
                    'itemtype_peripheral' => $peripheral_itemtype,
                    'items_id_peripheral' => $peripheral->getID(),
                ]
            );
            $this->assertTrue($capacity->isUsed($class));
        }
    }

    public function testGetCapacityUsageDescription(): void
    {
        global $CFG_GLPI;

        $entity_id = $this->getTestRootEntity(true);

        $definition = $this->initAssetDefinition(
            capacities: [new Capacity(name: HasPeripheralAssetsCapacity::class)]
        );
        $class = $definition->getAssetClassName();

        $capacity = new HasPeripheralAssetsCapacity();
        $this->assertEquals(
            '0 peripheral assets attached to 0 assets',
            $capacity->getCapacityUsageDescription($class)
        );

        $count_assets      = 0;
        $count_peripherals = 0;
        while ($count_assets < 3) {
            $asset = $this->createItem(
                $class,
                [
                    'name'        => __FUNCTION__,
                    'entities_id' => $entity_id,
                ]
            );
            $count_assets++;

            foreach ($CFG_GLPI['directconnect_types'] as $peripheral_itemtype) {
                $peripheral = $this->createItem(
                    $peripheral_itemtype,
                    [
                        'name'        => __FUNCTION__,
                        'entities_id' => $entity_id,
                    ]
                );

                $this->createItem(
                    Asset_PeripheralAsset::class,
                    [
                        'itemtype_asset'      => $class,
                        'items_id_asset'      => $asset->getID(),
                        'itemtype_peripheral' => $peripheral_itemtype,
                        'items_id_peripheral' => $peripheral->getID(),
                    ]
                );
                $count_peripherals++;

                $this->assertEquals(
                    sprintf('%d peripheral assets attached to %d assets', $count_peripherals, $count_assets),
                    $capacity->getCapacityUsageDescription($class)
                );
            }
        }
    }
}
