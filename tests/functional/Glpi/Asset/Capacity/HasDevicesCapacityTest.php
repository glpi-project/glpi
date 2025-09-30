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
use DeviceHardDrive;
use DisplayPreference;
use Entity;
use Glpi\Asset\Asset;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasDevicesCapacity;
use Glpi\Asset\Capacity\HasHistoryCapacity;
use Glpi\Asset\Capacity\HasNotepadCapacity;
use Item_DeviceHardDrive;
use Item_Devices;
use Log;

class HasDevicesCapacityTest extends DbTestCase
{
    public function testCapacityActivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasDevicesCapacity::class),
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
                new Capacity(name: HasDevicesCapacity::class),
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
                $this->assertContains($classname, $CFG_GLPI['itemdevices_types']);
                $this->assertContains($classname, $CFG_GLPI['itemdevices_itemaffinity']);
            } else {
                $this->assertNotContains($classname, $CFG_GLPI['itemdevices_types']);
                $this->assertNotContains($classname, $CFG_GLPI['itemdevices_itemaffinity']);
            }
            foreach (array_keys($CFG_GLPI) as $config_key) {
                if (preg_match('/^itemdevice[a-z+]_types$/', $config_key)) {
                    if ($has_capacity) {
                        $this->assertContains($classname, $CFG_GLPI[$config_key]);
                    } else {
                        $this->assertNotContains($classname, $CFG_GLPI[$config_key]);
                    }
                }
            }
            if ($has_capacity) {
                $this->assertNotEmpty(Item_Devices::getItemAffinities($classname));
            } else {
                $this->assertEmpty(Item_Devices::getItemAffinities($classname));
            }

            // Check that the corresponding tab is present on items
            $item = $this->createItem($classname, ['name' => __FUNCTION__, 'entities_id' => $root_entity_id]);
            $this->login(); // must be logged in to get tabs list
            if ($has_capacity) {
                $this->assertArrayHasKey('Item_Devices$1', $item->defineAllTabs());
            } else {
                $this->assertArrayNotHasKey('Item_Devices$1', $item->defineAllTabs());
            }

            // Check that the related search options are available
            $so_keys = [
                13,   // Graphic card / Designation
                14,   // Motherboard / Designation
                17,   // Processor / Designation
                18,   // Processor / Number of cores
                34,   // Processor / Number of threads
                35,   // Processor / Number of processors
                35,   // Processor / Frequency
                39,   // Power supply / Designation
                95,   // PCI / Designation
                110,  // Memory / Designation
                111,  // Memory / Size
                112,  // Network card / Designation
                113,  // Network card / MAC
                114,  // Hard drive / Designation
                115,  // Hard drive / Capacity
                116,  // Hard drive / Type
                1313, // Firmware / Designation
                1314, // Firmware / Version
                1315, // Firmware / Type
                1316, // Firmware / Model
                1317, // Firmware / Manufacturer
                1318, // Firmware / Serial
                1319, // Firmware / Other serial
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
                new Capacity(name: HasDevicesCapacity::class),
                new Capacity(name: HasHistoryCapacity::class),
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasDevicesCapacity::class),
                new Capacity(name: HasHistoryCapacity::class),
            ]
        );
        $classname_2  = $definition_2->getAssetClassName();

        $item_1 = $this->createItem(
            $classname_1,
            [
                'name' => __FUNCTION__,
                'entities_id' => $root_entity_id,
            ]
        );
        $item_2 = $this->createItem(
            $classname_2,
            [
                'name' => __FUNCTION__,
                'entities_id' => $root_entity_id,
            ]
        );

        $device = $this->createItem(
            DeviceHardDrive::class,
            [
                'designation' => 'HDD',
            ]
        );

        $device_item_1 = $this->createItem(
            Item_DeviceHardDrive::class,
            [
                'deviceharddrives_id' => $device->getID(),
                'itemtype'            => $item_1->getType(),
                'items_id'            => $item_1->getID(),
            ]
        );
        $device_item_2 = $this->createItem(
            Item_DeviceHardDrive::class,
            [
                'deviceharddrives_id' => $device->getID(),
                'itemtype'            => $item_2->getType(),
                'items_id'            => $item_2->getID(),
            ]
        );
        $displaypref_1   = $this->createItem(
            DisplayPreference::class,
            [
                'itemtype' => $classname_1,
                'num'      => 115, // capacity
                'users_id' => 0,
            ]
        );
        $displaypref_2   = $this->createItem(
            DisplayPreference::class,
            [
                'itemtype' => $classname_2,
                'num'      => 115, // capacity
                'users_id' => 0,
            ]
        );

        $item_1_logs_criteria = [
            'itemtype'      => $classname_1,
            'itemtype_link' => DeviceHardDrive::class,
        ];
        $item_2_logs_criteria = [
            'itemtype'      => $classname_2,
            'itemtype_link' => DeviceHardDrive::class,
        ];

        // Ensure relation, display preferences and logs exists, and class is registered to global config
        $this->assertInstanceOf(Item_DeviceHardDrive::class, Item_DeviceHardDrive::getById($device_item_1->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_1->getID()));
        $this->assertEquals(1, countElementsInTable(Log::getTable(), $item_1_logs_criteria)); // link
        $this->assertInstanceOf(Item_DeviceHardDrive::class, Item_DeviceHardDrive::getById($device_item_2->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_2->getID()));
        $this->assertEquals(1, countElementsInTable(Log::getTable(), $item_2_logs_criteria)); // link
        $this->assertContains($classname_1, $CFG_GLPI['itemdevices_types']);
        $this->assertContains($classname_2, $CFG_GLPI['itemdevices_types']);

        // Disable capacity and check that relations have been cleaned, and class is unregistered from global config
        $this->assertTrue($definition_1->update(['id' => $definition_1->getID(), 'capacities' => []]));
        $this->assertFalse(Item_DeviceHardDrive::getById($device_item_1->getID()));
        $this->assertFalse(DisplayPreference::getById($displaypref_1->getID()));
        $this->assertEquals(0, countElementsInTable(Log::getTable(), $item_1_logs_criteria));
        $this->assertNotContains($classname_1, $CFG_GLPI['itemdevices_types']);

        // Ensure relations, logs and global registration are preserved for other definition
        $this->assertInstanceOf(Item_DeviceHardDrive::class, Item_DeviceHardDrive::getById($device_item_2->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_2->getID()));
        $this->assertEquals(1, countElementsInTable(Log::getTable(), $item_2_logs_criteria));
        $this->assertContains($classname_2, $CFG_GLPI['itemdevices_types']);
    }

    public function testCloneAsset()
    {
        $definition = $this->initAssetDefinition(
            capacities: [new Capacity(name: HasDevicesCapacity::class)]
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

        $device = $this->createItem(
            DeviceHardDrive::class,
            [
                'designation' => 'HDD',
            ]
        );

        $this->createItem(
            Item_DeviceHardDrive::class,
            [
                'deviceharddrives_id' => $device->getID(),
                'itemtype'            => $class,
                'items_id'            => $asset->getID(),
            ]
        );

        $this->assertGreaterThan(0, $clone_id = $asset->clone());
        $this->assertCount(
            1,
            getAllDataFromTable(Item_DeviceHardDrive::getTable(), [
                'deviceharddrives_id' => $device->getID(),
                'itemtype'            => $class,
                'items_id'            => $clone_id,
            ])
        );
    }

    public function testIsUsed(): void
    {
        $entity_id = $this->getTestRootEntity(true);

        $definition = $this->initAssetDefinition(
            capacities: [new Capacity(name: HasDevicesCapacity::class)]
        );

        $asset = $this->createItem($definition->getAssetClassName(), [
            'name' => 'Test asset',
            'entities_id' => $entity_id,
        ]);

        // Check that the capacity is not yet considered as used
        $capacity = new HasDevicesCapacity();
        $this->assertFalse($capacity->isUsed($definition->getAssetClassName()));

        // Create a relation with a device
        $device = $this->createItem(
            DeviceHardDrive::class,
            [
                'designation' => 'HDD',
            ]
        );

        $this->createItem(
            Item_DeviceHardDrive::class,
            [
                'deviceharddrives_id' => $device->getID(),
                'itemtype'            => $definition->getAssetClassName(),
                'items_id'            => $asset->getID(),
            ]
        );

        // Check that the capacity is considered as used
        $this->assertTrue($capacity->isUsed($definition->getAssetClassName()));
    }

    public function testGetCapacityUsageDescription(): void
    {
        $entity_id = $this->getTestRootEntity(true);

        $definition = $this->initAssetDefinition(
            system_name: 'TestAsset',
            capacities: [new Capacity(name: HasDevicesCapacity::class)]
        );
        $capacity = new HasDevicesCapacity();

        // Check that the capacity usage description is correct
        $this->assertEquals(
            '0 components attached to 0 assets',
            $capacity->getCapacityUsageDescription($definition->getAssetClassName())
        );

        // Create assets
        $asset_1 = $this->createItem($definition->getAssetClassName(), [
            'name' => 'Test asset',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $asset_2 = $this->createItem($definition->getAssetClassName(), [
            'name' => 'Test asset 2',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        // Check that the capacity usage description is correct
        $this->assertEquals(
            '0 components attached to 0 assets',
            $capacity->getCapacityUsageDescription($definition->getAssetClassName())
        );

        $device_1 = $this->createItem(
            DeviceHardDrive::class,
            [
                'designation' => 'HDD abcd',
            ]
        );
        $device_2 = $this->createItem(
            DeviceHardDrive::class,
            [
                'designation' => 'HDD efgh',
            ]
        );

        // Attach the first component to the first asset
        for ($i = 0; $i < 3; $i++) {
            // Each relation should be counted once, as it corresponds to a unique component
            $this->createItem(
                Item_DeviceHardDrive::class,
                [
                    'deviceharddrives_id' => $device_1->getID(),
                    'itemtype'            => $definition->getAssetClassName(),
                    'items_id'            => $asset_1->getID(),
                ]
            );
        }

        // Check that the capacity usage description is correct
        $this->assertEquals(
            '1 components attached to 1 assets',
            $capacity->getCapacityUsageDescription($definition->getAssetClassName())
        );

        // Attach the first component to the second asset
        for ($i = 0; $i < 3; $i++) {
            // Each relation should be counted once, as it corresponds to a unique component
            $this->createItem(
                Item_DeviceHardDrive::class,
                [
                    'deviceharddrives_id' => $device_1->getID(),
                    'itemtype'            => $definition->getAssetClassName(),
                    'items_id'            => $asset_2->getID(),
                ]
            );
        }

        // Check that the capacity usage description is correct
        $this->assertEquals(
            '1 components attached to 2 assets',
            $capacity->getCapacityUsageDescription($definition->getAssetClassName())
        );

        // Attach the second component to the first asset
        for ($i = 0; $i < 3; $i++) {
            // Each relation should be counted once, as it corresponds to a unique component
            $this->createItem(
                Item_DeviceHardDrive::class,
                [
                    'deviceharddrives_id' => $device_2->getID(),
                    'itemtype'            => $definition->getAssetClassName(),
                    'items_id'            => $asset_1->getID(),
                ]
            );
        }

        // Check that the capacity usage description is correct
        $this->assertEquals(
            '2 components attached to 2 assets',
            $capacity->getCapacityUsageDescription($definition->getAssetClassName())
        );
    }
}
