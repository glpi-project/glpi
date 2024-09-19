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

use DbTestCase;
use DisplayPreference;
use Glpi\Asset\Asset;
use Glpi\Asset\Capacity\HasHistoryCapacity;
use Glpi\Tests\Asset\CapacityUsageTestTrait;
use Item_OperatingSystem;
use Log;

class HasOperatingSystemCapacity extends DbTestCase
{
    use CapacityUsageTestTrait;

    protected function getTargetCapacity(): string
    {
        return \Glpi\Asset\Capacity\HasOperatingSystemCapacity::class;
    }

    /**
     * Test that the capacity is properly registered in the configuration
     * when enabled and unregistered when disabled.
     *
     * @return void
     */
    public function testConfigRegistration(): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        // Create custom asset definition
        $definition = $this->initAssetDefinition();
        $class = $definition->getAssetClassName();

        // The capacity is not yet enabled, the itemtype should not be
        // registered in $CFG_GLPI["operatingsystem_types"]
        $this->array($CFG_GLPI["operatingsystem_types"])->notContains($class);

        // Enable capacity, the itemtype should now be registered
        $definition = $this->enableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $this->array($CFG_GLPI["operatingsystem_types"])->contains($class);

        // Disable capacity, the itemtype should no longer be registered
        $definition = $this->disableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $this->array($CFG_GLPI["operatingsystem_types"])->notContains($class);
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
        $this->array($subject->defineAllTabs())->notHasKey($tab_name);

        // Enable capacity, the tab should now be registered
        $definition = $this->enableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $this->array($subject->defineAllTabs())->hasKey($tab_name);

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
        $this->integer($count_to_add)->isGreaterThan(0);

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
        $this->array($subject->rawSearchOptions())->hasSize(
            $base_search_options_count + $count_to_add
        );

        // Disable capacity, search option count should decrease back to base
        $definition = $this->disableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $this->array($subject->rawSearchOptions())->hasSize(
            $base_search_options_count
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
            capacities: [$this->getTargetCapacity()]
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
        $this->array($items)->hasSize(1);

        // Disable capacity, linked item should be deleted
        $definition = $this->disableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $items = (new Item_OperatingSystem())->find([
            'itemtype' => $subject::getType(),
            'items_id' => $subject->getID(),
        ]);
        $this->array($items)->hasSize(0);
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
                $this->getTargetCapacity(),
                HasHistoryCapacity::class
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
        $this->integer($count_logs)->isEqualTo(5);

        // Disable capacity, history entries related to "Item_OperatingSystem"
        // and "OperatingSystem" should be deleted
        $definition = $this->disableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $count_logs = countElementsInTable(Log::getTable(), [
            'itemtype' => $class,
        ]);
        $this->integer($count_logs)->isEqualTo(2); // $subject creation + update
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
            capacities: [$this->getTargetCapacity()]
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
            ]
        ]);

        // Count display preferences, should be 9 (2 for OS + 7 for asset)
        $count_display_preferences = countElementsInTable(
            DisplayPreference::getTable(),
            [
                'itemtype' => $subject::getType(),
            ]
        );
        $this->integer($count_display_preferences)->isEqualTo(9);

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
        $this->integer($count_display_preferences)->isEqualTo(7);
    }

    public function testCloneAsset()
    {
        $definition = $this->initAssetDefinition(
            capacities: [\Glpi\Asset\Capacity\HasOperatingSystemCapacity::class]
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

        $this->integer($clone_id = $asset->clone())->isGreaterThan(0);
        $this->array(getAllDataFromTable(Item_OperatingSystem::getTable(), [
            'itemtype' => $asset::getType(),
            'items_id' => $clone_id,
            'license_number' => '012345',
        ]))->hasSize(1);
    }

    public function provideIsUsed(): iterable
    {
        yield [
            'target_classname' => Item_OperatingSystem::class,
        ];
    }

    public function provideGetCapacityUsageDescription(): iterable
    {
        yield [
            'target_classname' => Item_OperatingSystem::class,
            'expected' => 'Used by %d of %d assets'
        ];
    }
}
