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

namespace tests\units\Glpi\Asset\Capacity;

use DisplayPreference;
use Entity;
use Glpi\Tests\CapacityTestCase;
use Item_Rack;
use Log;
use Rack;

class IsRackableCapacity extends CapacityTestCase
{
    /**
     * Get the tested capacity class.
     *
     * @return string
     */
    protected function getTargetCapacity(): string
    {
        return \Glpi\Asset\Capacity\IsRackableCapacity::class;
    }

    public function testCapacityActivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(\Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\IsRackableCapacity::class,
                \Glpi\Asset\Capacity\HasNotepadCapacity::class,
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
            ]
        );
        $classname_2  = $definition_2->getAssetClassName();
        $definition_3 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\IsRackableCapacity::class,
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
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
                $this->array($CFG_GLPI['rackable_types'])->contains($classname);
            } else {
                $this->array($CFG_GLPI['rackable_types'])->notContains($classname);
            }

            $item = $this->createItem($classname, ['name' => __FUNCTION__, 'entities_id' => $root_entity_id]);

            $so_keys = [
                180, // Name
                181, // Position
            ];
            if ($has_capacity) {
                $this->array($item->getOptions())->hasKeys($so_keys);
            } else {
                $this->array($item->getOptions())->notHasKeys($so_keys);
            }
        }
    }

    public function testCapacityDeactivation(): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\IsRackableCapacity::class,
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\IsRackableCapacity::class,
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
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

        $rack = $this->createItem(\Rack::class, [
            'name' => 'rack 1',
            'entities_id' => $root_entity_id,
            'number_units' => 40
        ]);
        $rack_item_1 = $this->createItem(
            Item_Rack::class,
            [
                'itemtype'     => $item_1::getType(),
                'items_id'     => $item_1->getID(),
                'racks_id'     => $rack->getID(),
                'position'     => 1
            ]
        );
        $this->updateItem(Item_Rack::class, $rack_item_1->getID(), ['position' => 11]);
        $rack_item_2 = $this->createItem(
            Item_Rack::class,
            [
                'itemtype'     => $item_2::getType(),
                'items_id'     => $item_2->getID(),
                'racks_id'     => $rack->getID(),
                'position'     => 2
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
            ]
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
            ]
        ];

        // Ensure relation, display preferences and logs exists, and class is registered to global config
        $this->object(Item_Rack::getById($rack_item_1->getID()))->isInstanceOf(Item_Rack::class);
        $this->object(DisplayPreference::getById($displaypref_1->getID()))->isInstanceOf(DisplayPreference::class);
        $this->integer(countElementsInTable(Log::getTable(), $item_1_logs_criteria))->isEqualTo(3); // create + link + update
        $this->object(Item_Rack::getById($rack_item_2->getID()))->isInstanceOf(Item_Rack::class);
        $this->object(DisplayPreference::getById($displaypref_2->getID()))->isInstanceOf(DisplayPreference::class);
        $this->integer(countElementsInTable(Log::getTable(), $item_2_logs_criteria))->isEqualTo(3); // create + link + update
        $this->array($CFG_GLPI['rackable_types'])->contains($classname_1);
        $this->array($CFG_GLPI['rackable_types'])->contains($classname_2);

        // Disable capacity and check that relations have been cleaned, and class is unregistered from global config
        $this->boolean($definition_1->update(['id' => $definition_1->getID(), 'capacities' => []]))->isTrue();
        $this->boolean(Item_Rack::getById($rack_item_1->getID()))->isFalse();
        $this->boolean(DisplayPreference::getById($displaypref_1->getID()))->isFalse();
        $this->integer(countElementsInTable(Log::getTable(), $item_1_logs_criteria))->isEqualTo(0);
        $this->array($CFG_GLPI['rackable_types'])->notContains($classname_1);

        // Ensure relations, logs and global registration are preserved for other definition
        $this->object(Item_Rack::getById($rack_item_2->getID()))->isInstanceOf(Item_Rack::class);
        $this->object(DisplayPreference::getById($displaypref_2->getID()))->isInstanceOf(DisplayPreference::class);
        $this->integer(countElementsInTable(Log::getTable(), $item_2_logs_criteria))->isEqualTo(3);
        $this->array($CFG_GLPI['rackable_types'])->contains($classname_2);
    }

    public function provideIsUsed(): iterable
    {
        $racks_id = $this->createItem(Rack::class, [
            'name' => 'rack 1',
            'entities_id' => $this->getTestRootEntity(true),
            'number_units' => 40
        ])->getID();

        yield [
            'target_classname' => Item_Rack::class,
            'target_fields' => [
                'racks_id' => $racks_id,
                'position' => 1
            ]
        ];
    }

    public function provideGetCapacityUsageDescription(): iterable
    {
        yield [
            'target_classname' => Item_Rack::class,
            'expected' => 'Used by %d of %d assets'
        ];
    }

    /**
     * @dataProvider provideGetCapacityUsageDescription
     */
    public function testGetCapacityUsageDescription(
        string $target_classname,
        string $expected,
        array $target_fields = [],
        ?string $relation_classname = null,
        array $relation_fields = [],
        array $expected_results = [[1, 1], [2, 1], [2, 2]],
    ): void {
        // Retrieve the test root entity
        $entity_id = $this->getTestRootEntity(true);

        // Create custom asset definition with the target capacity enabled
        $definition = $this->initAssetDefinition(
            system_name: 'TestAsset',
            capacities: [$this->getTargetCapacity()]
        );

        // Create our test subject
        $subject = $this->createItem($definition->getAssetClassName(), [
            'name' => 'Test asset',
        ]);

        // Create a rack
        $rack = $this->createItem(Rack::class, [
            'name' => 'Test rack',
            'entities_id' => $entity_id,
            'number_units' => 40
        ]);

        // Create an item
        $this->createItem($target_classname, [
            'itemtype'       => $subject::getType(),
            'items_id'       => $subject->getID(),
            'racks_id'       => $rack->getID(),
            'position'       => 1
        ]);

        // Check that the capacity usage description is correct
        $capacity = new ($this->getTargetCapacity());
        $this->string($capacity->getCapacityUsageDescription($definition->getAssetClassName()))->isEqualTo(
            sprintf($expected, 1, 1)
        );

        // Create a second subject
        $subject2 = $this->createItem($definition->getAssetClassName(), [
            'name' => 'Test asset 2',
        ]);

        // Check that the capacity usage description is correct
        $this->string($capacity->getCapacityUsageDescription($definition->getAssetClassName()))->isEqualTo(
            sprintf($expected, 1, 2)
        );

        // Create an item linked to the second subject
        $this->createItem($target_classname, [
            'itemtype'       => $subject2::getType(),
            'items_id'       => $subject2->getID(),
            'racks_id'       => $rack->getID(),
            'position'       => 2
        ]);

        // Check that the capacity usage description is correct
        $this->string($capacity->getCapacityUsageDescription($definition->getAssetClassName()))->isEqualTo(
            sprintf($expected, 2, 2)
        );
    }
}
