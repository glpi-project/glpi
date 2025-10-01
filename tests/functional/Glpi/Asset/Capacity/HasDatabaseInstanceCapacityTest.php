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

use DatabaseInstance;
use DbTestCase;
use Entity;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasDatabaseInstanceCapacity;
use Glpi\Asset\Capacity\HasHistoryCapacity;
use Glpi\Asset\Capacity\HasNotepadCapacity;
use Glpi\Tests\Glpi\Asset\CapacityUsageTestTrait;

class HasDatabaseInstanceCapacityTest extends DbTestCase
{
    use CapacityUsageTestTrait;

    protected function getTargetCapacity(): string
    {
        return HasDatabaseInstanceCapacity::class;
    }

    public function testCapacityActivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasDatabaseInstanceCapacity::class),
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
        $definition_3 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasDatabaseInstanceCapacity::class),
                new Capacity(name: HasNotepadCapacity::class),
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
                $this->assertContains($classname, $CFG_GLPI['databaseinstance_types']);
            } else {
                $this->assertNotContains($classname, $CFG_GLPI['databaseinstance_types']);
            }

            // Check that the corresponding tab is present on items
            $item = $this->createItem($classname, ['name' => __FUNCTION__, 'entities_id' => $root_entity_id]);
            $this->login(); // must be logged in to get tabs list
            if ($has_capacity) {
                $this->assertArrayHasKey('DatabaseInstance$1', $item->defineAllTabs());
            } else {
                $this->assertArrayNotHasKey('DatabaseInstance$1', $item->defineAllTabs());
            }
        }
    }

    public function testCapacityDeactivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasDatabaseInstanceCapacity::class),
                new Capacity(name: HasHistoryCapacity::class),
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasDatabaseInstanceCapacity::class),
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

        $dbinstance_item_1 = $this->createItem(
            DatabaseInstance::class,
            [
                'name' => __FUNCTION__,
                'entities_id' => $root_entity_id,
                'itemtype'     => $item_1::getType(),
                'items_id'     => $item_1->getID(),
            ]
        );
        $dbinstance_item_2 = $this->createItem(
            DatabaseInstance::class,
            [
                'name' => __FUNCTION__,
                'entities_id' => $root_entity_id,
                'itemtype'     => $item_2::getType(),
                'items_id'     => $item_2->getID(),
            ]
        );

        $dbi_1 = DatabaseInstance::getById($dbinstance_item_1->getID());
        $this->assertInstanceOf(DatabaseInstance::class, $dbi_1);
        $this->assertEquals($classname_1, $dbi_1->fields['itemtype']);
        $this->assertGreaterThan(0, $dbi_1->fields['items_id']);
        $dbi_2 = DatabaseInstance::getById($dbinstance_item_2->getID());
        $this->assertInstanceOf(DatabaseInstance::class, $dbi_2);
        $this->assertEquals($classname_2, $dbi_2->fields['itemtype']);
        $this->assertGreaterThan(0, $dbi_2->fields['items_id']);
        $this->assertContains($classname_1, $CFG_GLPI['databaseinstance_types']);
        $this->assertContains($classname_2, $CFG_GLPI['databaseinstance_types']);

        $this->assertTrue($definition_1->update(['id' => $definition_1->getID(), 'capacities' => []]));
        $dbi_1->getFromDB($dbi_1->getID());
        $this->assertEquals('', $dbi_1->fields['itemtype']);
        $this->assertEquals(0, $dbi_1->fields['items_id']);
        $this->assertNotContains($classname_1, $CFG_GLPI['databaseinstance_types']);

        $dbi_2->getFromDB($dbi_2->getID());
        $this->assertEquals($classname_2, $dbi_2->fields['itemtype']);
        $this->assertGreaterThan(0, $dbi_2->fields['items_id']);
        $this->assertContains($classname_2, $CFG_GLPI['databaseinstance_types']);
    }

    public static function provideIsUsed(): iterable
    {
        yield [
            'target_classname' => DatabaseInstance::class,
        ];
    }

    public static function provideGetCapacityUsageDescription(): iterable
    {
        yield [
            'target_classname' => DatabaseInstance::class,
            'expected' => '%d database instances attached to %d assets',
        ];
    }
}
