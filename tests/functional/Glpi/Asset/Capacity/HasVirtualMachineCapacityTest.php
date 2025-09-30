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
use Glpi\Asset\Capacity\HasVirtualMachineCapacity;
use Glpi\Tests\Glpi\Asset\CapacityUsageTestTrait;
use ItemVirtualMachine;
use Log;

class HasVirtualMachineCapacityTest extends DbTestCase
{
    use CapacityUsageTestTrait;

    protected function getTargetCapacity(): string
    {
        return HasVirtualMachineCapacity::class;
    }

    public function testCapacityActivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasVirtualMachineCapacity::class),
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
                new Capacity(name: HasVirtualMachineCapacity::class),
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
                $this->assertContains($classname, $CFG_GLPI['itemvirtualmachines_types']);
            } else {
                $this->assertNotContains($classname, $CFG_GLPI['itemvirtualmachines_types']);
            }

            // Check that the corresponding tab is present on items
            $item = $this->createItem($classname, ['name' => __FUNCTION__, 'entities_id' => $root_entity_id]);
            $this->login(); // must be logged in to get tabs list
            if ($has_capacity) {
                $this->assertArrayHasKey('ItemVirtualMachine$1', $item->defineAllTabs());
            } else {
                $this->assertArrayNotHasKey('ItemVirtualMachine$1', $item->defineAllTabs());
            }

            // Check that the related search options are available
            $so_keys = [
                160, // Name
                161, // State
                162, // System
                163, // Type
                164, // Proc number
                165, // Memory
                166, // UUID
                179, // Comment
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
                new Capacity(name: HasVirtualMachineCapacity::class),
                new Capacity(name: HasHistoryCapacity::class),
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasVirtualMachineCapacity::class),
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

        $av_item_1 = $this->createItem(
            ItemVirtualMachine::class,
            [
                'name'         => 'vm item 1',
                'itemtype'     => $item_1->getType(),
                'items_id'     => $item_1->getID(),
            ]
        );
        $av_item_2 = $this->createItem(
            ItemVirtualMachine::class,
            [
                'name'         => 'vm item 2',
                'itemtype'     => $item_2->getType(),
                'items_id'     => $item_2->getID(),
            ]
        );
        $displaypref_1   = $this->createItem(
            DisplayPreference::class,
            [
                'itemtype' => $classname_1,
                'num'      => 160, // vm item name
                'users_id' => 0,
            ]
        );
        $displaypref_2   = $this->createItem(
            DisplayPreference::class,
            [
                'itemtype' => $classname_2,
                'num'      => 160, // vm item name
                'users_id' => 0,
            ]
        );

        $item_1_logs_criteria = [
            'itemtype'      => $classname_1,
        ];
        $item_2_logs_criteria = [
            'itemtype'      => $classname_2,
        ];

        // Ensure relation, display preferences and logs exists, and class is registered to global config
        $this->assertInstanceOf(ItemVirtualMachine::class, ItemVirtualMachine::getById($av_item_1->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_1->getID()));
        $this->assertEquals(2, countElementsInTable(Log::getTable(), $item_1_logs_criteria)); //create + add av
        $this->assertInstanceOf(ItemVirtualMachine::class, ItemVirtualMachine::getById($av_item_2->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_2->getID()));
        $this->assertEquals(2, countElementsInTable(Log::getTable(), $item_2_logs_criteria)); //create + add av
        $this->assertContains($classname_1, $CFG_GLPI['itemvirtualmachines_types']);
        $this->assertContains($classname_2, $CFG_GLPI['itemvirtualmachines_types']);

        // Disable capacity and check that relations have been cleaned, and class is unregistered from global config
        $this->assertTrue($definition_1->update(['id' => $definition_1->getID(), 'capacities' => []]));
        $this->assertFalse(ItemVirtualMachine::getById($av_item_1->getID()));
        $this->assertFalse(DisplayPreference::getById($displaypref_1->getID()));
        $this->assertEquals(0, countElementsInTable(Log::getTable(), $item_1_logs_criteria));
        $this->assertNotContains($classname_1, $CFG_GLPI['itemvirtualmachines_types']);

        // Ensure relations, logs and global registration are preserved for other definition
        $this->assertInstanceOf(ItemVirtualMachine::class, ItemVirtualMachine::getById($av_item_2->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_2->getID()));
        $this->assertEquals(2, countElementsInTable(Log::getTable(), $item_2_logs_criteria));
        $this->assertContains($classname_2, $CFG_GLPI['itemvirtualmachines_types']);
    }

    public function testCloneAsset()
    {
        $definition = $this->initAssetDefinition(
            capacities: [new Capacity(name: HasVirtualMachineCapacity::class)]
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
            ItemVirtualMachine::class,
            [
                'name'     => 'Virtual machine',
                'itemtype' => $class,
                'items_id' => $asset->getID(),
            ]
        );
        $this->assertGreaterThan(0, $clone_id = $asset->clone());
        $this->assertCount(
            1,
            getAllDataFromTable(ItemVirtualMachine::getTable(), [
                'name'     => 'Virtual machine (copy)',
                'itemtype' => $class,
                'items_id' => $clone_id,
            ])
        );
    }

    public static function provideIsUsed(): iterable
    {
        yield [
            'target_classname' => ItemVirtualMachine::class,
        ];
    }

    public static function provideGetCapacityUsageDescription(): iterable
    {
        yield [
            'target_classname' => ItemVirtualMachine::class,
            'expected' => '%d virtual machines attached to %d assets',
        ];
    }
}
