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

use Contract;
use Contract_Item;
use DbTestCase;
use DisplayPreference;
use Glpi\Asset\Asset;
use Glpi\Asset\Capacity\HasHistoryCapacity;
use Glpi\Tests\Asset\CapacityUsageTestTrait;
use Log;

class HasContractsCapacity extends DbTestCase
{
    use CapacityUsageTestTrait;

    protected function getTargetCapacity(): string
    {
        return \Glpi\Asset\Capacity\HasContractsCapacity::class;
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
        // registered in $CFG_GLPI["contract_types"]
        $this->array($CFG_GLPI["contract_types"])->notContains($class);

        // Enable capacity, the itemtype should now be registered
        $definition = $this->enableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $this->array($CFG_GLPI["contract_types"])->contains($class);

        // Disable capacity, the itemtype should no longer be registered
        $definition = $this->disableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $this->array($CFG_GLPI["contract_types"])->notContains($class);
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
        $this->array($subject->defineAllTabs())->notHasKey($tab_name);

        // Enable capacity, the tab should now be registered
        $definition = $this->enableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $this->array($subject->defineAllTabs())->hasKey($tab_name);
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
            capacities: [$this->getTargetCapacity()]
        );
        $class = $definition->getAssetClassName();
        $entity = $this->getTestRootEntity(true);

        // Create our test subject
        $subject = $this->createItem($class, [
            'name' => 'Test asset',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        // Create some contracts that are ready to be assigned to our item
        list(
            $contract1,
            $contract2
        ) = $this->createItems(Contract::class, [
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
        $this->array($items)->hasSize(2);

        // Disable capacity, linked item should be deleted
        $definition = $this->disableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $items = (new Contract_Item())->find([
            'itemtype' => $subject::getType(),
            'items_id' => $subject->getID(),
        ]);
        $this->array($items)->hasSize(0);
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
                $this->getTargetCapacity(),
                HasHistoryCapacity::class
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
        $this->integer($count_logs)->isEqualTo(3);

        // Check logs number for the subject:
        // - 1 log for $subject creation
        // - 1 log for $subject update
        // - 1 log for link with $contract
        $count_logs = countElementsInTable(Log::getTable(), [
            'itemtype' => $class,
        ]);
        $this->integer($count_logs)->isEqualTo(3);

        // Disable capacity, history entries on both side of the relation should
        // be deleted
        $definition = $this->disableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $count_logs = countElementsInTable(Log::getTable(), [
            'itemtype' => $contract::getType(),
        ]);
        $this->integer($count_logs)->isEqualTo(2); // $contract creation + update
        $count_logs = countElementsInTable(Log::getTable(), [
            'itemtype' => $class,
        ]);
        $this->integer($count_logs)->isEqualTo(2); // $subject creation + update
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
        $this->integer($count_display_preferences)->isEqualTo(9);

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
        $this->integer($count_display_preferences)->isEqualTo(7);
    }

    public function testCloneAsset()
    {
        $definition = $this->initAssetDefinition(
            capacities: [\Glpi\Asset\Capacity\HasContractsCapacity::class]
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

        $this->integer($clone_id = $asset->clone())->isGreaterThan(0);
        $this->array(getAllDataFromTable(Contract_Item::getTable(), [
            'contracts_id' => $contract->getID(),
            'itemtype' => $asset::getType(),
            'items_id' => $clone_id,
        ]))->hasSize(1);
    }

    public function provideIsUsed(): iterable
    {
        yield [
            'target_classname' => Contract::class,
            'relation_classname' => Contract_Item::class
        ];
    }

    public function provideGetCapacityUsageDescription(): iterable
    {
        yield [
            'target_classname' => Contract::class,
            'relation_classname' => Contract_Item::class,
            'expected' => '%d contracts attached to %d assets'
        ];
    }
}
