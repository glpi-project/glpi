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

namespace Glpi\Tests\Glpi\Asset;

use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\CapacityInterface;
use PHPUnit\Framework\Attributes\DataProvider;

trait CapacityUsageTestTrait
{
    /**
     * Get the tested capacity class.
     *
     * @return class-string<CapacityInterface>
     */
    abstract protected function getTargetCapacity(): string;

    abstract public static function provideIsUsed(): iterable;

    abstract public static function provideGetCapacityUsageDescription(): iterable;

    /**
     * Test if the method isUsed returns true if the capacity can be disabled
     * without data loss.
     *
     * @return void
     */
    #[DataProvider('provideIsUsed')]
    public function testIsUsed(
        string $target_classname,
        array $target_fields = [],
        ?string $relation_classname = null,
        array $relation_fields = [],
    ): void {
        global $DB;

        // Retrieve the test root entity
        $entity_id = $this->getTestRootEntity(true);

        // Create custom asset definition with the target capacity enabled
        $definition = $this->initAssetDefinition(
            capacities: [new Capacity(name: $this->getTargetCapacity())]
        );

        // Create our test subject
        $subject = $this->createItem($definition->getAssetClassName(), [
            'name' => 'Test asset',
            'entities_id' => $entity_id,
        ]);

        // Check that the capacity can be disabled
        $capacity = new ($this->getTargetCapacity());
        $this->assertFalse($capacity->isUsed($definition->getAssetClassName()));

        // Create item
        $table = $target_classname::getTable();
        if ($DB->fieldExists($table, 'itemtype')) {
            $target_fields['itemtype'] = $definition->getAssetClassName();
        }
        if ($DB->fieldExists($table, 'items_id')) {
            $target_fields['items_id'] = $subject->getID();
        }
        if ($DB->fieldExists($table, 'entities_id')) {
            $target_fields['entities_id'] = $entity_id;
        }
        if ($DB->fieldExists($table, 'name')) {
            $target_fields['name'] = 'Test item';
        }

        $item = $this->createItem($target_classname, $target_fields);

        // Create relation
        if ($relation_classname !== null) {
            // Check that the capacity can be disabled
            $this->assertFalse($capacity->isUsed($definition->getAssetClassName()));

            $table = $relation_classname::getTable();
            if ($DB->fieldExists($table, 'itemtype')) {
                $relation_fields['itemtype'] = $subject::getType();
            }
            if ($DB->fieldExists($table, 'items_id')) {
                $relation_fields['items_id'] = $subject->getID();
            }
            if ($DB->fieldExists($table, $target_classname::getForeignKeyField())) {
                $relation_fields[$target_classname::getForeignKeyField()] = $item->getID();
            }

            $this->createItem($relation_classname, $relation_fields);
        }

        // Check that the capacity can't be safely disabled
        $this->assertTrue($capacity->isUsed($definition->getAssetClassName()));
    }

    /**
     * Test if the getCapacityUsageDescription method returns a correct description
     * of the capacity usage.
     *
     * @return void
     */
    #[DataProvider('provideGetCapacityUsageDescription')]
    public function testGetCapacityUsageDescription(
        string $target_classname,
        string $expected,
        array $target_fields = [],
        ?string $relation_classname = null,
        array $relation_fields = [],
    ): void {
        global $DB;

        $capacity = new ($this->getTargetCapacity());

        // Retrieve the test root entity
        $entity_id = $this->getTestRootEntity(true);

        // Create custom asset definition with the target capacity enabled
        $definition = $this->initAssetDefinition(
            capacities: [new Capacity(name: $this->getTargetCapacity())]
        );

        // Create our test subject
        $subject = $this->createItem($definition->getAssetClassName(), [
            'name' => 'Test asset',
            'entities_id' => $entity_id,
        ]);

        // Create item
        $table = $target_classname::getTable();
        if ($DB->fieldExists($table, 'itemtype') && $DB->fieldExists($table, 'items_id')) {
            $target_fields['itemtype'] = $definition->getAssetClassName();
            $target_fields['items_id'] = $subject->getID();
        }
        if ($DB->fieldExists($table, 'entities_id')) {
            $target_fields['entities_id'] = $entity_id;
        }
        if ($DB->fieldExists($table, 'name')) {
            $target_fields['name'] = 'Test item';
        }

        $item = $this->createItem($target_classname, $target_fields);

        // Link item to subject
        if ($relation_classname !== null) {
            $table = $relation_classname::getTable();
            if ($DB->fieldExists($table, 'itemtype')) {
                $relation_fields['itemtype'] = $subject::getType();
            }
            if ($DB->fieldExists($table, 'items_id')) {
                $relation_fields['items_id'] = $subject->getID();
            }
            if ($DB->fieldExists($table, $target_classname::getForeignKeyField())) {
                $relation_fields[$target_classname::getForeignKeyField()] = $item->getID();
            }

            $this->createItem($relation_classname, $relation_fields);
        }

        // Check that the capacity usage description is correct
        $expectedValue = sprintf($expected, 1, 1);
        $this->assertEquals($expectedValue, $capacity->getCapacityUsageDescription($definition->getAssetClassName()));

        // Create a second subject
        $subject2 = $this->createItem($definition->getAssetClassName(), [
            'name' => 'Test asset 2',
            'entities_id' => $entity_id,
        ]);

        // Create a second item
        $table = $target_classname::getTable();
        if ($DB->fieldExists($table, 'itemtype')) {
            $target_fields['itemtype'] = $definition->getAssetClassName();
        }
        if ($DB->fieldExists($table, 'items_id')) {
            $target_fields['items_id'] = $subject2->getID();
        }
        if ($DB->fieldExists($table, 'entities_id')) {
            $target_fields['entities_id'] = $entity_id;
        }
        if ($DB->fieldExists($table, 'name')) {
            $target_fields['name'] = 'Test item';
        }

        $item2 = $this->createItem($target_classname, $target_fields);

        // Link second item to second subject
        if ($relation_classname !== null) {
            $table = $relation_classname::getTable();
            if ($DB->fieldExists($table, 'itemtype')) {
                $relation_fields['itemtype'] = $subject2::getType();
            }
            if ($DB->fieldExists($table, 'items_id')) {
                $relation_fields['items_id'] = $subject2->getID();
            }
            if ($DB->fieldExists($table, $target_classname::getForeignKeyField())) {
                $relation_fields[$target_classname::getForeignKeyField()] = $item2->getID();
            }

            $this->createItem($relation_classname, $relation_fields);
        }

        // Check that the capacity usage description is correct
        $expectedValue = sprintf($expected, 2, 2);
        $this->assertEquals($expectedValue, $capacity->getCapacityUsageDescription($definition->getAssetClassName()));
    }
}
