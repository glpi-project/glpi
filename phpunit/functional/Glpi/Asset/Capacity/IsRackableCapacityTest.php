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
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasHistoryCapacity;
use Glpi\Asset\Capacity\HasNotepadCapacity;
use Glpi\Asset\Capacity\IsRackableCapacity;
use Item_Rack;
use Log;
use Rack;

class IsRackableCapacityTest extends DbTestCase
{
    public function testCapacityActivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: IsRackableCapacity::class),
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
                new Capacity(name: IsRackableCapacity::class),
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
            if ($has_capacity) {
                $this->assertContains($classname, $CFG_GLPI['rackable_types']);
            } else {
                $this->assertNotContains($classname, $CFG_GLPI['rackable_types']);
            }

            $item = $this->createItem($classname, ['name' => __FUNCTION__, 'entities_id' => $root_entity_id]);

            $so_keys = [
                180, // Name
                181, // Position
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
                new Capacity(name: IsRackableCapacity::class),
                new Capacity(name: HasHistoryCapacity::class),
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: IsRackableCapacity::class),
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

        $rack = $this->createItem(Rack::class, [
            'name' => 'rack 1',
            'entities_id' => $root_entity_id,
            'number_units' => 40,
        ]);
        $rack_item_1 = $this->createItem(
            Item_Rack::class,
            [
                'itemtype'     => $item_1::getType(),
                'items_id'     => $item_1->getID(),
                'racks_id'     => $rack->getID(),
                'position'     => 1,
            ]
        );
        $this->updateItem(Item_Rack::class, $rack_item_1->getID(), ['position' => 11]);
        $rack_item_2 = $this->createItem(
            Item_Rack::class,
            [
                'itemtype'     => $item_2::getType(),
                'items_id'     => $item_2->getID(),
                'racks_id'     => $rack->getID(),
                'position'     => 2,
            ]
        );
        $this->updateItem(Item_Rack::class, $rack_item_2->getID(), ['position' => 12]);
        $displaypref_1   = $this->createItem(
            DisplayPreference::class,
            [
                'itemtype' => $classname_1,
                'num'      => 180, // Rack name
                'users_id' => 0,
            ]
        );
        $displaypref_2   = $this->createItem(
            DisplayPreference::class,
            [
                'itemtype' => $classname_2,
                'num'      => 180, // Rack name
                'users_id' => 0,
            ]
        );

        $item_1_logs_criteria = [
            'OR' => [
                [
                    'itemtype'      => $classname_1,
                    'itemtype_link' => ['LIKE', 'Item_Rack%'],
                ],
                [
                    'itemtype'      => $classname_1,
                    'itemtype_link' => Rack::class,
                ],
                [
                    'itemtype'      => Rack::class,
                    'itemtype_link' => $classname_1,
                ],
            ],
        ];
        $item_2_logs_criteria = [
            'OR' => [
                [
                    'itemtype'      => $classname_2,
                    'itemtype_link' => ['LIKE', 'Item_Rack%'],
                ],
                [
                    'itemtype'      => $classname_2,
                    'itemtype_link' => Rack::class,
                ],
                [
                    'itemtype'      => Rack::class,
                    'itemtype_link' => $classname_2,
                ],
            ],
        ];

        // Ensure relation, display preferences and logs exists, and class is registered to global config
        $this->assertInstanceOf(Item_Rack::class, Item_Rack::getById($rack_item_1->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_1->getID()));
        $this->assertEquals(3, countElementsInTable(Log::getTable(), $item_1_logs_criteria)); // create + link + update
        $this->assertInstanceOf(Item_Rack::class, Item_Rack::getById($rack_item_2->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_2->getID()));
        $this->assertEquals(3, countElementsInTable(Log::getTable(), $item_2_logs_criteria)); // create + link + update
        $this->assertContains($classname_1, $CFG_GLPI['rackable_types']);
        $this->assertContains($classname_2, $CFG_GLPI['rackable_types']);

        // Disable capacity and check that relations have been cleaned, and class is unregistered from global config
        $this->assertTrue($definition_1->update(['id' => $definition_1->getID(), 'capacities' => []]));
        $this->assertFalse(Item_Rack::getById($rack_item_1->getID()));
        $this->assertFalse(DisplayPreference::getById($displaypref_1->getID()));
        $this->assertEquals(0, countElementsInTable(Log::getTable(), $item_1_logs_criteria));
        $this->assertNotContains($classname_1, $CFG_GLPI['rackable_types']);

        // Ensure relations, logs and global registration are preserved for other definition
        $this->assertInstanceOf(Item_Rack::class, Item_Rack::getById($rack_item_2->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_2->getID()));
        $this->assertEquals(3, countElementsInTable(Log::getTable(), $item_2_logs_criteria));
        $this->assertContains($classname_2, $CFG_GLPI['rackable_types']);
    }

    public function testIsUsed(): void
    {
        $entity_id = $this->getTestRootEntity(true);

        $definition = $this->initAssetDefinition(
            capacities: [new Capacity(name: IsRackableCapacity::class)]
        );

        $asset = $this->createItem($definition->getAssetClassName(), [
            'name' => 'Test asset',
            'entities_id' => $entity_id,
        ]);

        // Check that the capacity can be disabled
        $capacity = new IsRackableCapacity();
        $this->assertFalse($capacity->isUsed($definition->getAssetClassName()));

        // Create a relation with a rack
        $racks_id = $this->createItem(Rack::class, [
            'name' => 'rack 1',
            'entities_id' => $entity_id,
            'number_units' => 40,
        ])->getID();
        $this->createItem(
            Item_Rack::class,
            [
                'itemtype' => $definition->getAssetClassName(),
                'items_id' => $asset->getID(),
                'position' => 1,
                'racks_id' => $racks_id,
            ]
        );

        // Check that the capacity can't be safely disabled
        $this->assertTrue($capacity->isUsed($definition->getAssetClassName()));
    }

    public function testGetCapacityUsageDescription(): void
    {
        $entity_id = $this->getTestRootEntity(true);

        $definition = $this->initAssetDefinition(
            system_name: 'TestAsset',
            capacities: [new Capacity(name: IsRackableCapacity::class)]
        );
        $capacity = new IsRackableCapacity();

        // Check that the capacity usage description is correct
        $this->assertEquals(
            'Used by 0 of 0 assets',
            $capacity->getCapacityUsageDescription($definition->getAssetClassName())
        );

        // Create assets
        $asset1 = $this->createItem($definition->getAssetClassName(), [
            'name' => 'Test asset',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $asset2 = $this->createItem($definition->getAssetClassName(), [
            'name' => 'Test asset 2',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        // Check that the capacity usage description is correct
        $this->assertEquals(
            'Used by 0 of 2 assets',
            $capacity->getCapacityUsageDescription($definition->getAssetClassName())
        );

        // Create a relation with a rack
        $rack = $this->createItem(Rack::class, [
            'name' => 'Test rack',
            'entities_id' => $entity_id,
            'number_units' => 40,
        ]);
        $this->createItem(Item_Rack::class, [
            'itemtype' => $definition->getAssetClassName(),
            'items_id' => $asset1->getID(),
            'racks_id' => $rack->getID(),
            'position' => 1,
        ]);

        // Check that the capacity usage description is correct
        $this->assertEquals(
            'Used by 1 of 2 assets',
            $capacity->getCapacityUsageDescription($definition->getAssetClassName())
        );

        // Create an item linked to the second subject
        $this->createItem(Item_Rack::class, [
            'itemtype' => $definition->getAssetClassName(),
            'items_id' => $asset2->getID(),
            'racks_id' => $rack->getID(),
            'position' => 2,
        ]);

        // Check that the capacity usage description is correct
        $this->assertEquals(
            'Used by 2 of 2 assets',
            $capacity->getCapacityUsageDescription($definition->getAssetClassName())
        );
    }
}
