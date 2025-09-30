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

use Contract;
use Contract_Item;
use DbTestCase;
use DisplayPreference;
use Glpi\Asset\Asset;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasContractsCapacity;
use Glpi\Asset\Capacity\HasHistoryCapacity;
use Glpi\Tests\Glpi\Asset\CapacityUsageTestTrait;
use Log;

class HasContractsCapacityTest extends DbTestCase
{
    use CapacityUsageTestTrait;

    protected function getTargetCapacity(): string
    {
        return HasContractsCapacity::class;
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
        // registered in $CFG_GLPI["contract_types"]
        $this->assertNotContains($class, $CFG_GLPI["contract_types"]);

        // Enable capacity, the itemtype should now be registered
        $definition = $this->enableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $this->assertContains($class, $CFG_GLPI["contract_types"]);

        // Disable capacity, the itemtype should no longer be registered
        $definition = $this->disableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $this->assertNotContains($class, $CFG_GLPI["contract_types"]);
    }

    /**
     * Test that the "Contract item" tab is registered when the capacity
     * is enabled.
     *
     * @return void
     */
    public function testContractItemTabRegistration(): void
    {
        // Need to be logged in because Contract_Item::getTabNameForItem() will
        // call the Contract::canView() method
        $this->login();

        // Create custom asset definition
        $definition = $this->initAssetDefinition();
        $class = $definition->getAssetClassName();

        // Create our test subject
        $subject = $this->createItem($class, [
            'name' => 'Test asset',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        // Validate that the subject does not have the contract tab, as the
        // capacity is not enabled yet
        $tab_name = "Contract_Item$1";
        $this->assertArrayNotHasKey($tab_name, $subject->defineAllTabs());

        // Enable capacity, the tab should now be registered
        $definition = $this->enableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $this->assertArrayHasKey($tab_name, $subject->defineAllTabs());
    }

    /**
     * Test that any "Contract_Item" items linked to the asset are
     * deleted when the capacity is disabled.
     *
     * @return void
     */
    public function testContractItemDataDeletion(): void
    {
        // Create custom asset definition with the target capacity enabled
        $definition = $this->initAssetDefinition(
            capacities: [new Capacity(name: $this->getTargetCapacity())]
        );
        $class = $definition->getAssetClassName();
        $entity = $this->getTestRootEntity(true);

        // Create our test subject
        $subject = $this->createItem($class, [
            'name' => 'Test asset',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        // Create some contracts that are ready to be assigned to our item
        [
            $contract1,
            $contract2
        ] = $this->createItems(Contract::class, [
            ['name' => 'Contract 1', 'entities_id' => $entity],
            ['name' => 'Contract 2', 'entities_id' => $entity],
        ]);

        // Link contracts to asset
        $this->createItems(Contract_Item::class, [
            [
                'itemtype'     => $subject::getType(),
                'items_id'     => $subject->getID(),
                'contracts_id' => $contract1->getID(),
            ],
            [
                'itemtype'     => $subject::getType(),
                'items_id'     => $subject->getID(),
                'contracts_id' => $contract2->getID(),
            ],
        ]);

        // Ensure contracts are properly linked to our subject
        $items = (new Contract_Item())->find([
            'itemtype' => $subject::getType(),
            'items_id' => $subject->getID(),
        ]);
        $this->assertCount(2, $items);

        // Disable capacity, linked item should be deleted
        $definition = $this->disableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $items = (new Contract_Item())->find([
            'itemtype' => $subject::getType(),
            'items_id' => $subject->getID(),
        ]);
        $this->assertCount(0, $items);
    }

    /**
     * Test that any history entries related to "Contract_Item" and
     * "Contract" are deleted when the capacity is disabled.
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
        $entity = $this->getTestRootEntity(true);

        // Create our test subject
        $subject = $this->createItem($class, [
            'name' => 'Test asset',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        // Create a contract
        $contract = $this->createItem(Contract::class, [
            'name'        => 'Contract 1',
            'entities_id' => $entity,
        ]);

        // Edit the contract name
        $this->updateItem($contract::getType(), $contract->getID(), [
            'name' => 'Contract 1 (edited)',
        ]);

        // Link contract to subject
        $this->createItem(Contract_Item::class, [
            'itemtype'       => $subject::getType(),
            'items_id'       => $subject->getID(),
            'contracts_id' => $contract->getID(),
        ]);

        // Also update some internal fields to make sure their history entries
        // are not deleted when the capacity is disabled
        $this->updateItem($subject::getType(), $subject->getId(), [
            'name' => 'Test asset (edited)',
        ]);

        // Check logs number for the contract:
        // - 1 log for $contract creation
        // - 1 log for $contract update
        // - 1 log for link with $subject
        $count_logs = countElementsInTable(Log::getTable(), [
            'itemtype' => $contract::getType(),
        ]);
        $this->assertEquals(3, $count_logs);

        // Check logs number for the subject:
        // - 1 log for $subject creation
        // - 1 log for $subject update
        // - 1 log for link with $contract
        $count_logs = countElementsInTable(Log::getTable(), [
            'itemtype' => $class,
        ]);
        $this->assertEquals(3, $count_logs);

        // Disable capacity, history entries on both side of the relation should
        // be deleted
        $definition = $this->disableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $count_logs = countElementsInTable(Log::getTable(), [
            'itemtype' => $contract::getType(),
        ]);
        $this->assertEquals(2, $count_logs); // $contract creation + update
        $count_logs = countElementsInTable(Log::getTable(), [
            'itemtype' => $class,
        ]);
        $this->assertEquals(2, $count_logs); // $subject creation + update
    }

    /**
     * Test that any display preferences entries related to
     * "Contract" are deleted when the capacity is disabled.
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
                'num'      => '29', // Linked contract name
                'users_id' => 0,
            ],
            [
                'itemtype' => $subject::getType(),
                'num'      => '129', // Linked contract type
                'users_id' => 0,
            ],
        ]);

        // Count display preferences, should be 9 (2 for contract + 7 for asset)
        $count_display_preferences = countElementsInTable(
            DisplayPreference::getTable(),
            [
                'itemtype' => $subject::getType(),
            ]
        );
        $this->assertEquals(9, $count_display_preferences);

        // Disable capacity, display preferences related to contracts should be
        // deleted while display preferences related to the asset should not be
        // deleted
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
            capacities: [new Capacity(name: HasContractsCapacity::class)]
        );
        $class = $definition->getAssetClassName();
        $entity = $this->getTestRootEntity(true);

        /** @var Asset $asset */
        $asset = $this->createItem($class, [
            'name'        => 'Test asset',
            'entities_id' => $entity,
        ]);

        $contract = $this->createItem(Contract::class, [
            'name'        => 'Contract 1',
            'entities_id' => $entity,
        ]);

        $this->createItem(Contract_Item::class, [
            'itemtype'     => $asset::getType(),
            'items_id'     => $asset->getID(),
            'contracts_id' => $contract->getID(),
        ]);

        $this->assertGreaterThan(0, $clone_id = $asset->clone());
        $this->assertCount(
            1,
            getAllDataFromTable(Contract_Item::getTable(), [
                'contracts_id' => $contract->getID(),
                'itemtype' => $asset::getType(),
                'items_id' => $clone_id,
            ])
        );
    }

    public static function provideIsUsed(): iterable
    {
        yield [
            'target_classname' => Contract::class,
            'relation_classname' => Contract_Item::class,
        ];
    }

    public static function provideGetCapacityUsageDescription(): iterable
    {
        yield [
            'target_classname' => Contract::class,
            'relation_classname' => Contract_Item::class,
            'expected' => '%d contracts attached to %d assets',
        ];
    }
}
