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
use Glpi\Asset\Asset;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasHistoryCapacity;
use Glpi\Asset\Capacity\HasOperatingSystemCapacity;
use Glpi\Tests\Glpi\Asset\CapacityUsageTestTrait;
use Item_OperatingSystem;
use Log;

class HasOperatingSystemCapacityTest extends DbTestCase
{
    use CapacityUsageTestTrait;

    protected function getTargetCapacity(): string
    {
        return HasOperatingSystemCapacity::class;
    }

    /**
     * Test that the capacity is properly registered in the configuration
     * when enabled and unregistered when disabled.
     *
     * @return void
     */
    public function testConfigRegistration(): void
    {
        global $CFG_GLPI;

        // Create custom asset definition
        $definition = $this->initAssetDefinition();
        $class = $definition->getAssetClassName();

        // The capacity is not yet enabled, the itemtype should not be
        // registered in $CFG_GLPI["operatingsystem_types"]
        $this->assertNotContains($class, $CFG_GLPI["operatingsystem_types"]);

        // Enable capacity, the itemtype should now be registered
        $definition = $this->enableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $this->assertContains($class, $CFG_GLPI["operatingsystem_types"]);

        // Disable capacity, the itemtype should no longer be registered
        $definition = $this->disableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $this->assertNotContains($class, $CFG_GLPI["operatingsystem_types"]);
    }

    /**
     * Test that the "Operating system" tab is registered when the capacity
     * is enabled.
     *
     * @return void
     */
    public function testOperatingSystemTabRegistration(): void
    {
        // Create custom asset definition
        $definition = $this->initAssetDefinition();
        $class = $definition->getAssetClassName();

        // Create our test subject
        $subject = $this->createItem($class, [
            'name' => 'Test asset',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        // Validate that the subject does not have the operating system tab,
        // as the capacity is not enabled yet
        $tab_name = "Item_OperatingSystem$1";
        $this->assertArrayNotHasKey($tab_name, $subject->defineAllTabs());

        // Enable capacity, the tab should now be registered
        $definition = $this->enableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $this->assertArrayHasKey($tab_name, $subject->defineAllTabs());

        // Disable capacity, the tab should no longer be registered
        // INFO: Can't test this case because tabs are not unregistered
        // immediately, but only after a page refresh (which we can't simulate
        // here).
        // Could be done if we implement the code for it but not very useful
        // since tabs are not used from the CLI context
    }

    /**
    * Test that the "Item_OperatingSystem" search options are registered when
    * the capacity is enabled and unregistered when disabled.
    *
    * @return void
    */
    public function testSearchOptionRegistration(): void
    {
        // Create custom asset definition
        $definition = $this->initAssetDefinition();
        $class = $definition->getAssetClassName();

        // Check that we have some valid search options to add
        $item_os = new Item_OperatingSystem();
        $count_to_add = count($item_os::rawSearchOptionsToAdd($class));
        $this->assertGreaterThan(0, $count_to_add);

        // Create our test subject
        $subject = $this->createItem($class, [
            'name' => 'Test asset',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $base_search_options_count = count($subject->rawSearchOptions());

        // Enable capactity, search option count should increase
        $definition = $this->enableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $this->assertCount(
            $base_search_options_count + $count_to_add,
            $subject->rawSearchOptions()
        );

        // Disable capacity, search option count should decrease back to base
        $definition = $this->disableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $this->assertCount(
            $base_search_options_count,
            $subject->rawSearchOptions()
        );
    }

    /**
     * Test that any "Item_OperatingSystem" items linked to the asset are
     * deleted when the capacity is disabled.
     *
     * @return void
     */
    public function testItemOperatingSystemDataDeletion(): void
    {
        // Create custom asset definition with the target capacity enabled
        $definition = $this->initAssetDefinition(
            capacities: [new Capacity($this->getTargetCapacity())]
        );
        $class = $definition->getAssetClassName();

        // Create our test subject
        $subject = $this->createItem($class, [
            'name' => 'Test asset',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        // Create an Item_OperatingSystem linked to our asset
        $this->createItem(Item_OperatingSystem::class, [
            'itemtype'       => $subject::getType(),
            'items_id'       => $subject->getID(),
            'license_number' => '012345',
        ]);

        // Ensure item is properly linked to our subject
        $items = (new Item_OperatingSystem())->find([
            'itemtype' => $subject::getType(),
            'items_id' => $subject->getID(),
        ]);
        $this->assertCount(1, $items);

        // Disable capacity, linked item should be deleted
        $definition = $this->disableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $items = (new Item_OperatingSystem())->find([
            'itemtype' => $subject::getType(),
            'items_id' => $subject->getID(),
        ]);
        $this->assertCount(0, $items);
    }

    /**
     * Test that any history entries related to "Item_OperatingSystem" and
     * "OperatingSystem" are deleted when the capacity is disabled.
     *
     * @return void
     */
    public function testHistoryDataDeletion(): void
    {
        // Create custom asset definition with the target capacity enabled
        $definition = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: $this->getTargetCapacity()),
                new Capacity(name: HasHistoryCapacity::class),
            ]
        );
        $class = $definition->getAssetClassName();

        // Create our test subject
        $subject = $this->createItem($class, [
            'name' => 'Test asset',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        // Create and update the linked OS in order to generate history entries
        $item = $this->createItem(Item_OperatingSystem::class, [
            'itemtype'       => $subject::getType(),
            'items_id'       => $subject->getID(),
            'license_number' => '012345',
        ]);
        $this->updateItem($item::getType(), $item->getId(), [
            'license_number' => '0123456',
        ]);
        $this->updateItem($item::getType(), $item->getId(), [
            'license_number' => '01234567',
        ]);

        // Also update some internal fields to make sure they are not deleted
        // when the capacity is disabled
        $this->updateItem($subject::getType(), $subject->getId(), [
            'name' => 'Test asset (edited)',
        ]);

        // Check logs number:
        // - 1 log for $subject creation
        // - 1 log for $subject update
        // - 1 log for $item creation
        // - 2 log for $item update
        $count_logs = countElementsInTable(Log::getTable(), [
            'itemtype' => $class,
        ]);
        $this->assertEquals(5, $count_logs);

        // Disable capacity, history entries related to "Item_OperatingSystem"
        // and "OperatingSystem" should be deleted
        $definition = $this->disableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $count_logs = countElementsInTable(Log::getTable(), [
            'itemtype' => $class,
        ]);
        $this->assertEquals(2, $count_logs); // $subject creation + update
    }

    /**
     * Test that any display preferences entries related to
     * "Item_OperatingSystem" and "OperatingSystem" are deleted when the
     * capacity is disabled.
     *
     * @return void
     */
    public function testDisplayPreferencesDataDeletion(): void
    {
        // Create custom asset definition with the target capacity enabled
        $definition = $this->initAssetDefinition(
            capacities: [new Capacity(name: $this->getTargetCapacity())]
        );
        $class = $definition->getAssetClassName();

        // Create our test subject and enable the capacity
        $subject = $this->createItem($class, [
            'name' => 'Test asset',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        // Set display preferences
        $this->createItems(DisplayPreference::class, [
            [
                'itemtype' => $subject::getType(),
                'num'      => '45', // Linked OS name
                'users_id' => 0,
            ],
            [
                'itemtype' => $subject::getType(),
                'num'      => '46', // Linked OS version
                'users_id' => 0,
            ],
        ]);

        // Count display preferences, should be 9 (2 for OS + 7 for asset)
        $count_display_preferences = countElementsInTable(
            DisplayPreference::getTable(),
            [
                'itemtype' => $subject::getType(),
            ]
        );
        $this->assertEquals(9, $count_display_preferences);

        // Disable capacity, display preferences related to OS should be
        // deleted while display preferences related to the asset should not be
        // deleted
        // The two OS search options should be deleted
        $definition = $this->disableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $count_display_preferences = countElementsInTable(
            DisplayPreference::getTable(),
            [
                'itemtype' => $subject::getType(),
            ]
        );
        $this->assertEquals(7, $count_display_preferences);
    }

    public function testCloneAsset()
    {
        $definition = $this->initAssetDefinition(
            capacities: [new Capacity(name: HasOperatingSystemCapacity::class)]
        );
        $class = $definition->getAssetClassName();
        $entity = $this->getTestRootEntity(true);

        /** @var Asset $asset */
        $asset = $this->createItem($class, [
            'name'        => 'Test asset',
            'entities_id' => $entity,
        ]);

        $this->createItem(Item_OperatingSystem::class, [
            'itemtype'       => $asset::getType(),
            'items_id'       => $asset->getID(),
            'license_number' => '012345',
        ]);

        $this->assertGreaterThan(0, $clone_id = $asset->clone());
        $this->assertCount(
            1,
            getAllDataFromTable(Item_OperatingSystem::getTable(), [
                'itemtype' => $asset::getType(),
                'items_id' => $clone_id,
                'license_number' => '012345',
            ])
        );
    }

    public static function provideIsUsed(): iterable
    {
        yield [
            'target_classname' => Item_OperatingSystem::class,
        ];
    }

    public static function provideGetCapacityUsageDescription(): iterable
    {
        yield [
            'target_classname' => Item_OperatingSystem::class,
            'expected' => 'Used by %d of %d assets',
        ];
    }
}
