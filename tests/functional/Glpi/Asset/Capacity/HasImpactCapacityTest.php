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

use Config;
use DbTestCase;
use Entity;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasHistoryCapacity;
use Glpi\Asset\Capacity\HasImpactCapacity;
use Glpi\Asset\Capacity\HasNotepadCapacity;
use Impact;
use ImpactRelation;

class HasImpactCapacityTest extends DbTestCase
{
    public function testCapacityActivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasImpactCapacity::class),
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
                new Capacity(name: HasImpactCapacity::class),
                new Capacity(name: HasHistoryCapacity::class),
            ]
        );
        $classname_3  = $definition_3->getAssetClassName();

        $has_impact_mapping = [
            $classname_1 => true,
            $classname_2 => false,
            $classname_3 => true,
        ];

        $enabled_impact_types = json_decode(Config::getConfigurationValue('core', Impact::CONF_ENABLED)) ?? [];
        foreach ($has_impact_mapping as $classname => $has_impact) {
            // Check that the class is globally registered
            if ($has_impact) {
                $this->assertArrayHasKey($classname, $CFG_GLPI['impact_asset_types']);
                $this->assertContains($classname, $enabled_impact_types);
            } else {
                $this->assertArrayNotHasKey($classname, $CFG_GLPI['impact_asset_types']);
                $this->assertNotContains($classname, $enabled_impact_types);
            }

            $item = $this->createItem($classname, ['name' => __FUNCTION__, 'entities_id' => $root_entity_id]);
            $this->login(); // must be logged in to get tabs list
            if ($has_impact) {
                $this->assertArrayHasKey('Impact$1', $item->defineAllTabs());
            } else {
                $this->assertArrayNotHasKey('Impact$1', $item->defineAllTabs());
            }
        }
    }

    public function testCapacityDeactivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasImpactCapacity::class),
                new Capacity(name: HasHistoryCapacity::class),
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasImpactCapacity::class),
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

        $impact_item_1 = $this->createItem(
            ImpactRelation::class,
            [
                'itemtype_source'           => $item_1::class,
                'items_id_source'           => $item_1->getID(),
                'itemtype_impacted'         => 'Computer',
                'items_id_impacted'         => getItemByTypeName('Computer', '_test_pc01', true),
            ]
        );
        $impact_item_2 = $this->createItem(
            ImpactRelation::class,
            [
                'itemtype_source'             => 'Computer',
                'items_id_source'             => getItemByTypeName('Computer', '_test_pc01', true),
                'itemtype_impacted'           => $item_2::class,
                'items_id_impacted'           => $item_2->getID(),
            ]
        );

        $this->assertInstanceOf(ImpactRelation::class, ImpactRelation::getById($impact_item_1->getID()));
        $this->assertInstanceOf(ImpactRelation::class, ImpactRelation::getById($impact_item_2->getID()));
        $this->assertArrayHasKey($classname_1, $CFG_GLPI['impact_asset_types']);
        $this->assertArrayHasKey($classname_2, $CFG_GLPI['impact_asset_types']);

        $this->assertTrue($definition_1->update(['id' => $definition_1->getID(), 'capacities' => []]));
        $this->assertFalse(ImpactRelation::getById($impact_item_1->getID()));
        $this->assertArrayNotHasKey($classname_1, $CFG_GLPI['impact_asset_types']);

        $this->assertInstanceOf(ImpactRelation::class, ImpactRelation::getById($impact_item_2->getID()));
        $this->assertArrayHasKey($classname_2, $CFG_GLPI['impact_asset_types']);
    }

    public function testIsUsed(): void
    {
        $entity_id = $this->getTestRootEntity(true);

        $definition = $this->initAssetDefinition(
            capacities: [new Capacity(name: HasImpactCapacity::class)]
        );
        $capacity = new HasImpactCapacity();

        $asset = $this->createItem($definition->getAssetClassName(), [
            'name' => 'Test asset',
            'entities_id' => $entity_id,
        ]);
        $computers_id = getItemByTypeName('Computer', '_test_pc01', true);

        // Check usage when the custom asset is the source asset
        $this->assertFalse($capacity->isUsed($definition->getAssetClassName()));
        $relation = $this->createItem(
            ImpactRelation::class,
            [
                'itemtype_source'   => $definition->getAssetClassName(),
                'items_id_source'   => $asset->getID(),
                'itemtype_impacted' => 'Computer',
                'items_id_impacted' => $computers_id,

            ]
        );
        $this->assertTrue($capacity->isUsed($definition->getAssetClassName()));

        // Delete the relation
        $this->assertTrue($relation->delete(['id' => $relation->getID()], true));
        $this->assertFalse($capacity->isUsed($definition->getAssetClassName()));

        // Check when the custom asset on the other side of the relation
        $this->createItem(
            ImpactRelation::class,
            [
                'itemtype_source'   => 'Computer',
                'items_id_source'   => $computers_id,
                'itemtype_impacted' => $definition->getAssetClassName(),
                'items_id_impacted' => $asset->getID(),

            ]
        );
        $this->assertTrue($capacity->isUsed($definition->getAssetClassName()));
    }

    public function testGetCapacityUsageDescription(): void
    {
        $entity_id = $this->getTestRootEntity(true);

        $definition = $this->initAssetDefinition(
            capacities: [new Capacity(name: HasImpactCapacity::class)]
        );
        $capacity = new HasImpactCapacity();

        $asset_1 = $this->createItem($definition->getAssetClassName(), [
            'name' => 'Test asset',
            'entities_id' => $entity_id,
        ]);
        $asset_2 = $this->createItem($definition->getAssetClassName(), [
            'name' => 'Test asset',
            'entities_id' => $entity_id,
        ]);
        $computer_1_id = getItemByTypeName('Computer', '_test_pc01', true);
        $computer_2_id = getItemByTypeName('Computer', '_test_pc02', true);

        $this->createItem(ImpactRelation::class, [
            'itemtype_source'   => $definition->getAssetClassName(),
            'items_id_source'   => $asset_1->getID(),
            'itemtype_impacted' => 'Computer',
            'items_id_impacted' => $computer_1_id,
        ]);
        $this->assertEquals(
            '1 impact relations involving 1 assets',
            $capacity->getCapacityUsageDescription($definition->getAssetClassName())
        );

        $this->createItem(ImpactRelation::class, [
            'itemtype_source'   => $definition->getAssetClassName(),
            'items_id_source'   => $asset_1->getID(),
            'itemtype_impacted' => 'Computer',
            'items_id_impacted' => $computer_2_id,
        ]);
        $this->assertEquals(
            '2 impact relations involving 1 assets',
            $capacity->getCapacityUsageDescription($definition->getAssetClassName())
        );

        $this->createItem(ImpactRelation::class, [
            'itemtype_source'   => $definition->getAssetClassName(),
            'items_id_source'   => $asset_2->getID(),
            'itemtype_impacted' => 'Computer',
            'items_id_impacted' => $computer_1_id,
        ]);
        $this->assertEquals(
            '3 impact relations involving 2 assets',
            $capacity->getCapacityUsageDescription($definition->getAssetClassName())
        );
    }
}
