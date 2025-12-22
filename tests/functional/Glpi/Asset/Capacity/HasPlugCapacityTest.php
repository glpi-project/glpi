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

namespace tests\units\Glpi\Asset\Capacity;

use Computer;
use Entity;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasHistoryCapacity;
use Glpi\Asset\Capacity\HasNotepadCapacity;
use Glpi\Asset\Capacity\HasPlugCapacity;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\Glpi\Asset\CapacityUsageTestTrait;
use Log;
use Plug;

class HasPlugCapacityTest extends DbTestCase
{
    use CapacityUsageTestTrait;

    protected function getTargetCapacity(): string
    {
        return HasPlugCapacity::class;
    }

    public function testCapacityActivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasPlugCapacity::class),
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
                new Capacity(name: HasPlugCapacity::class),
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
                $this->assertContains($classname, $CFG_GLPI['plug_types']);
            } else {
                $this->assertNotContains($classname, $CFG_GLPI['plug_types']);
            }

            // Check that the corresponding tab is present on items
            $item = $this->createItem($classname, ['name' => __FUNCTION__, 'entities_id' => $root_entity_id]);
            $this->login(); // must be logged in to get tabs list
            if ($has_capacity) {
                $this->assertArrayHasKey('Plug$1', $item->defineAllTabs());
            } else {
                $this->assertArrayNotHasKey('Plug$1', $item->defineAllTabs());
            }
        }
    }

    public function testCapacityDeactivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasPlugCapacity::class),
                new Capacity(name: HasHistoryCapacity::class),
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasPlugCapacity::class),
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
        $plug_1 = $this->createItem(
            Plug::class,
            [
                'name' => __FUNCTION__ . '1',
            ]
        );
        $plug_2 = $this->createItem(
            Plug::class,
            [
                'name' => __FUNCTION__ . '2',
            ]
        );

        $plug_item_1 = $this->createItem(
            Plug::class,
            [
                'itemtype' => $item_1::class,
                'items_id' => $item_1->getID(),
                'mainitemtype' => $plug_1::class,
                'mainitems_id' => $plug_1->getID(),
            ]
        );
        $plug_item_2 = $this->createItem(
            Plug::class,
            [
                'itemtype' => $item_2::class,
                'items_id' => $item_2->getID(),
                'mainitemtype' => $plug_2::class,
                'mainitems_id' => $plug_2->getID(),
            ]
        );

        $item_1_logs_criteria = [
            'itemtype'      => $classname_1,
        ];
        $item_2_logs_criteria = [
            'itemtype'      => $classname_2,
        ];

        // Ensure relation, display preferences and logs exists, and class is registered to global config
        $this->assertInstanceOf(Plug::class, Plug::getById($plug_item_1->getID()));
        $this->assertEquals(2, countElementsInTable(Log::getTable(), $item_1_logs_criteria)); //create + add plug
        $this->assertInstanceOf(Plug::class, Plug::getById($plug_item_2->getID()));
        $this->assertEquals(2, countElementsInTable(Log::getTable(), $item_2_logs_criteria)); //create + add plug
        $this->assertContains($classname_1, $CFG_GLPI['plug_types']);
        $this->assertContains($classname_2, $CFG_GLPI['plug_types']);

        // Disable capacity and check that relations have been cleaned, and class is unregistered from global config
        $this->assertTrue($definition_1->update(['id' => $definition_1->getID(), 'capacities' => []]));
        $this->assertFalse(Plug::getById($plug_item_1->getID()));
        $this->assertEquals(0, countElementsInTable(Log::getTable(), $item_1_logs_criteria));
        $this->assertNotContains($classname_1, $CFG_GLPI['plug_types']);

        // Ensure relations, logs and global registration are preserved for other definition
        $this->assertInstanceOf(Plug::class, Plug::getById($plug_item_2->getID()));
        $this->assertEquals(2, countElementsInTable(Log::getTable(), $item_2_logs_criteria));
        $this->assertContains($classname_2, $CFG_GLPI['plug_types']);
    }

    public function testCloneAsset()
    {
        $definition = $this->initAssetDefinition(
            capacities: [new Capacity(name: HasPlugCapacity::class)]
        );
        $class = $definition->getAssetClassName();
        $entity = $this->getTestRootEntity(true);

        $asset = $this->createItem(
            $class,
            [
                'name'        => 'Test asset',
                'entities_id' => $entity,
            ]
        );

        $computer = $this->createItem(
            Computer::class,
            [
                'name'        => 'Test Computer',
                'entities_id' => $entity,
            ]
        );

        $plug = $this->createItem(
            Plug::class,
            [
                'itemtype'     => Computer::class,
                'items_id'     => $computer->getID(),
                'mainitemtype'     => $class,
                'mainitems_id'     => $asset->getID(),
            ]
        );

        $this->assertGreaterThan(0, $plug->clone());
        $this->assertCount(
            2,
            getAllDataFromTable(Plug::getTable(), [
                'itemtype'     => Computer::class,
                'items_id'     => $computer->getID(),
                'mainitemtype'     => $class,
                'mainitems_id'     => $asset->getID(),
            ])
        );
    }

    public static function provideIsUsed(): iterable
    {
        yield [
            'target_classname'   => Plug::class,
        ];
    }

    public static function provideGetCapacityUsageDescription(): iterable
    {
        yield [
            'target_classname'   => Plug::class,
            'expected'           => '%d plugs attached to %d assets',
        ];
    }
}
