<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use DbTestCase;
use DisplayPreference;
use Entity;
use Glpi\Tests\Asset\CapacityUsageTestTrait;
use Log;
use ReservationItem;

class IsInventoriableCapacityTest extends DbTestCase
{
    protected function getTargetCapacity(): string
    {
        return \Glpi\Asset\Capacity\IsInventoriableCapacity::class;
    }

    public function testCapacityActivation(): void
    {
        global $CFG_GLPI;

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\IsInventoriableCapacity::class,
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
                \Glpi\Asset\Capacity\IsInventoriableCapacity::class,
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
            // Check that the class is globally registered
            if ($has_capacity) {
                $this->assertContains($classname, $CFG_GLPI['inventory_types']);
                $this->assertContains($classname, $CFG_GLPI['agent_types']);
                $this->assertContains($classname, $CFG_GLPI['environment_types']);
                $this->assertContains($classname, $CFG_GLPI['process_types']);
            } else {
                $this->assertNotContains($classname, $CFG_GLPI['inventory_types']);
                $this->assertNotContains($classname, $CFG_GLPI['agent_types']);
                $this->assertNotContains($classname, $CFG_GLPI['environment_types']);
                $this->assertNotContains($classname, $CFG_GLPI['process_types']);
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
                \Glpi\Asset\Capacity\IsInventoriableCapacity::class,
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\IsInventoriableCapacity::class,
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

        // Ensure  class is registered to global config
        $this->assertContains($classname_1, $CFG_GLPI['inventory_types']);
        $this->assertContains($classname_1, $CFG_GLPI['agent_types']);
        $this->assertContains($classname_1, $CFG_GLPI['environment_types']);
        $this->assertContains($classname_1, $CFG_GLPI['process_types']);
        $this->assertContains($classname_2, $CFG_GLPI['inventory_types']);
        $this->assertContains($classname_2, $CFG_GLPI['agent_types']);
        $this->assertContains($classname_2, $CFG_GLPI['environment_types']);
        $this->assertContains($classname_2, $CFG_GLPI['process_types']);

        // Disable capacity and check class is unregistered from global config
        $this->assertTrue($definition_1->update(['id' => $definition_1->getID(), 'capacities' => []]));
        $this->assertNotContains($classname_1, $CFG_GLPI['inventory_types']);
        $this->assertNotContains($classname_1, $CFG_GLPI['agent_types']);
        $this->assertNotContains($classname_1, $CFG_GLPI['environment_types']);
        $this->assertNotContains($classname_1, $CFG_GLPI['process_types']);

        // Ensure global registration is preserved for other definition
        $this->assertContains($classname_2, $CFG_GLPI['inventory_types']);
        $this->assertContains($classname_2, $CFG_GLPI['agent_types']);
        $this->assertContains($classname_2, $CFG_GLPI['environment_types']);
        $this->assertContains($classname_2, $CFG_GLPI['process_types']);
    }

    public function testIsUsed(): void
    {
        global $DB;

        // Retrieve the test root entity
        $entity_id = $this->getTestRootEntity(true);

        // Create custom asset definition with the target capacity enabled
        $definition = $this->initAssetDefinition(
            capacities: [$this->getTargetCapacity()]
        );

        // Create a non-dynamic test subject
        $this->createItem($definition->getAssetClassName(), [
            'name' => 'Test asset',
            'entities_id' => $entity_id,
            'is_dynamic' => 0,
        ]);

        // Check that the capacity can be disabled
        $capacity = new ($this->getTargetCapacity());
        $this->assertFalse($capacity->isUsed($definition->getAssetClassName()));

        // Create a dynamic test subject
        $this->createItem($definition->getAssetClassName(), [
            'name' => 'Test asset 2',
            'entities_id' => $entity_id,
            'is_dynamic' => 1,
        ]);

        // Check that the capacity can't be safely disabled
        $capacity = new ($this->getTargetCapacity());
        $this->assertTrue($capacity->isUsed($definition->getAssetClassName()));
    }

    /**
     * Test if the getCapacityUsageDescription method returns a correct description
     * of the capacity usage.
     *
     * @return void
     */
    public function testGetCapacityUsageDescription(): void
    {
        global $DB;

        $capacity = new ($this->getTargetCapacity());

        // Retrieve the test root entity
        $entity_id = $this->getTestRootEntity(true);

        // Create custom asset definition with the target capacity enabled
        $definition = $this->initAssetDefinition(
            capacities: [$this->getTargetCapacity()]
        );

        // Create a non-dynamic test subject
        $this->createItem($definition->getAssetClassName(), [
            'name' => 'Test asset',
            'entities_id' => $entity_id,
            'is_dynamic' => 0,
        ]);

        $this->assertEquals(
            'Not used',
            $capacity->getCapacityUsageDescription($definition->getAssetClassName())
        );

        // Create a dynamic test subject
        $this->createItem($definition->getAssetClassName(), [
            'name' => 'Test asset 2',
            'entities_id' => $entity_id,
            'is_dynamic' => 1,
        ]);
        $this->assertEquals(
            'Used by 1 asset',
            $capacity->getCapacityUsageDescription($definition->getAssetClassName())
        );

        // Create another dynamic test subject
        $this->createItem($definition->getAssetClassName(), [
            'name' => 'Test asset 3',
            'entities_id' => $entity_id,
            'is_dynamic' => 1,
        ]);
        $this->assertEquals(
            'Used by 2 assets',
            $capacity->getCapacityUsageDescription($definition->getAssetClassName())
        );
    }
}
