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
use Entity;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasDocumentsCapacity;
use Glpi\Asset\Capacity\HasHistoryCapacity;
use Glpi\Asset\Capacity\HasNotepadCapacity;
use Log;

class HasHistoryCapacityTest extends DbTestCase
{
    public function testCapacityActivation(): void
    {
        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasHistoryCapacity::class),
                new Capacity(name: HasNotepadCapacity::class),
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasNotepadCapacity::class),
            ]
        );
        $classname_2  = $definition_2->getAssetClassName();
        $definition_3 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasDocumentsCapacity::class),
                new Capacity(name: HasHistoryCapacity::class),
            ]
        );
        $classname_3  = $definition_3->getAssetClassName();

        $has_history_mapping = [
            $classname_1 => true,
            $classname_2 => false,
            $classname_3 => true,
        ];

        foreach ($has_history_mapping as $classname => $has_history) {
            $item = $this->createItem($classname, ['name' => __FUNCTION__, 'entities_id' => $root_entity_id]);

            // Check that the corresponding tab is present on items
            $this->login(); // must be logged in to get tabs list
            if ($has_history) {
                $this->assertArrayHasKey('Log$1', $item->defineAllTabs());
            } else {
                $this->assertArrayNotHasKey('Log$1', $item->defineAllTabs());
            }
        }
    }

    public function testCapacityDeactivation(): void
    {
        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasHistoryCapacity::class),
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
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

        $this->updateItem($classname_1, $item_1->getID(), ['name' => 'updated', 'comment' => 'updated']);
        $this->updateItem($classname_2, $item_2->getID(), ['name' => 'updated too']);

        $item_1_logs_criteria = [
            'itemtype'      => $classname_1,
        ];
        $item_2_logs_criteria = [
            'itemtype'      => $classname_2,
        ];

        // Ensure logs exists
        $this->assertEquals(3, countElementsInTable(Log::getTable(), $item_1_logs_criteria)); // created + 2 fields updated
        $this->assertEquals(2, countElementsInTable(Log::getTable(), $item_2_logs_criteria)); // created + 1 field updated

        // Disable capacity and check that logs have been cleaned
        $this->assertTrue($definition_1->update(['id' => $definition_1->getID(), 'capacities' => []]));
        $this->assertEquals(0, countElementsInTable(Log::getTable(), $item_1_logs_criteria));

        // Ensure logs are preserved for other definition
        $this->assertEquals(2, countElementsInTable(Log::getTable(), $item_2_logs_criteria));
    }

    public function test_Log_getHistoryData(): void
    {
        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasHistoryCapacity::class),
            ]
        );
        $classname  = $definition->getAssetClassName();

        $item = $this->createItem(
            $classname,
            [
                'name' => __FUNCTION__,
                'entities_id' => $root_entity_id,
            ]
        );
        $this->updateItem($classname, $item->getID(), ['name' => 'updated', 'serial' => 'AAAA0000']);
        $this->updateItem($classname, $item->getID(), ['serial' => 'AAAA0001']);

        $history_data = Log::getHistoryData($item);
        $this->assertCount(4, $history_data);
        $this->assertEquals('Add the item', $history_data[3]['change']);
        $this->assertEquals('Change <del>test_Log_getHistoryData</del> to <ins>updated</ins>', $history_data[2]['change']);
        $this->assertEquals('Change <del></del> to <ins>AAAA0000</ins>', $history_data[1]['change']);
        $this->assertEquals('Change <del>AAAA0000</del> to <ins>AAAA0001</ins>', $history_data[0]['change']);
    }

    public function testGetCapacityUsageDescription(): void
    {
        $capacity = new HasHistoryCapacity();

        $entity_id = $this->getTestRootEntity(true);

        $definition = $this->initAssetDefinition(
            capacities: [new Capacity(name: HasHistoryCapacity::class)]
        );

        $asset_1 = $this->createItem($definition->getAssetClassName(), [
            'name' => 'Test asset',
            'entities_id' => $entity_id,
        ]);
        $this->assertEquals(
            '1 logs attached to 1 assets',
            $capacity->getCapacityUsageDescription($definition->getAssetClassName())
        ); // creation log for 1 asset

        $this->updateItem($definition->getAssetClassName(), $asset_1->getID(), ['name' => '1 updated']);
        $this->assertEquals(
            '2 logs attached to 1 assets',
            $capacity->getCapacityUsageDescription($definition->getAssetClassName())
        ); // creation log for 1 asset + update log for 1 asset

        $asset_2 = $this->createItem($definition->getAssetClassName(), [
            'name' => 'Test asset 2',
            'entities_id' => $entity_id,
        ]);
        $this->assertEquals(
            '3 logs attached to 2 assets',
            $capacity->getCapacityUsageDescription($definition->getAssetClassName())
        ); // creation log for 2 assets + update log for 1 asset

        $this->updateItem($definition->getAssetClassName(), $asset_2->getID(), ['name' => '2 updated']);
        $this->assertEquals(
            '4 logs attached to 2 assets',
            $capacity->getCapacityUsageDescription($definition->getAssetClassName())
        ); // creation log for 2 assets + update log for 2 asset
    }

    public function testIsUsed(): void
    {
        // Create custom asset definition with the target capacity enabled
        $definition = $this->initAssetDefinition(
            capacities: [new Capacity(name: HasHistoryCapacity::class)]
        );

        // Check that the capacity can be disabled
        $capacity = new HasHistoryCapacity();
        $this->assertFalse($capacity->isUsed($definition->getAssetClassName()));

        // Create our test subject
        $asset = $this->createItem($definition->getAssetClassName(), [
            'name' => 'Test asset',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        // Check that the capacity can't be safely disabled
        $this->assertTrue($capacity->isUsed($definition->getAssetClassName()));

        $this->createItem(
            Log::class,
            [
                'itemtype' => $definition->getAssetClassName(),
                'items_id' => $asset->getID(),
            ]
        );

        // Check that the capacity can't be safely disabled
        $this->assertTrue($capacity->isUsed($definition->getAssetClassName()));
    }
}
