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

namespace Glpi\Tests\Asset;

trait CapacityUsageTestTrait
{
    /**
     * Get the tested capacity class.
     *
     * @return string
     */
    abstract protected function getTargetCapacity(): string;

    abstract public function provideIsUsed(): iterable;

    abstract public function provideGetCapacityUsageDescription(): iterable;

    /**
     * Test if the method isUsed returns true if the capacity can be disabled
     * without data loss.
     *
     * @dataProvider provideIsUsed
     * @return void
     */
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
            capacities: [$this->getTargetCapacity()]
        );

        // Create our test subject
        $subject = $this->createItem($definition->getAssetClassName(), [
            'name' => 'Test asset',
            'entities_id' => $entity_id,
        ]);

        // Check that the capacity can be disabled
        $capacity = new ($this->getTargetCapacity());
        $this->boolean($capacity->isUsed($definition->getAssetClassName()))->isFalse();

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
            $this->boolean($capacity->isUsed($definition->getAssetClassName()))->isFalse();

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
        $this->boolean($capacity->isUsed($definition->getAssetClassName()))->isTrue();
    }

    /**
     * Test if the getCapacityUsageDescription method returns a correct description
     * of the capacity usage.
     *
     * @dataProvider provideGetCapacityUsageDescription
     * @return void
     */
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
            capacities: [$this->getTargetCapacity()]
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
        $this->string($capacity->getCapacityUsageDescription($definition->getAssetClassName()))->isEqualTo($expectedValue);

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
        $this->string($capacity->getCapacityUsageDescription($definition->getAssetClassName()))->isEqualTo($expectedValue);
    }
}
