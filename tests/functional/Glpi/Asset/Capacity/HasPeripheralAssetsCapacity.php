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

use DisplayPreference;
use Entity;
use Glpi\Asset\Asset_PeripheralAsset;
use Glpi\Tests\CapacityTestCase;
use Log;
use Monitor;

class HasPeripheralAssetsCapacity extends CapacityTestCase
{
    /**
     * Get the tested capacity class.
     *
     * @return string
     */
    protected function getTargetCapacity(): string
    {
        return \Glpi\Asset\Capacity\HasPeripheralAssetsCapacity::class;
    }

    public function testCapacityActivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasPeripheralAssetsCapacity::class,
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
                \Glpi\Asset\Capacity\HasPeripheralAssetsCapacity::class,
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
                $this->array($CFG_GLPI['peripheralhost_types'])->contains($classname);
                $this->array(Asset_PeripheralAsset::getPeripheralHostItemtypes())->contains($classname);
            } else {
                $this->array($CFG_GLPI['peripheralhost_types'])->notContains($classname);
                $this->array(Asset_PeripheralAsset::getPeripheralHostItemtypes())->notContains($classname);
            }

            // Check that the corresponding tab is present on items
            $item = $this->createItem($classname, ['name' => __FUNCTION__, 'entities_id' => $root_entity_id]);
            $this->login(); // must be logged in to get tabs list
            if ($has_capacity) {
                $this->array($item->defineAllTabs())->hasKey('Glpi\\Asset\\Asset_PeripheralAsset$1');
            } else {
                $this->array($item->defineAllTabs())->notHasKey('Glpi\\Asset\\Asset_PeripheralAsset$1');
            }

            // Check that the related search options are available
            $so_keys = [
                1429, // Number of monitors
                1430, // Number of peripherals
                1431, // Number of printers
                1432, // Number of phones
            ];
            if ($has_capacity) {
                $this->array($item->getOptions())->hasKeys($so_keys);
            } else {
                $this->array($item->getOptions())->notHasKeys($so_keys);
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
                \Glpi\Asset\Capacity\HasPeripheralAssetsCapacity::class,
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasPeripheralAssetsCapacity::class,
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

        $relation_1 = $this->createItem(
            Asset_PeripheralAsset::class,
            [
                'itemtype_asset'      => $classname_1,
                'items_id_asset'      => $item_1->getID(),
                'itemtype_peripheral' => Monitor::class,
                'items_id_peripheral' => getItemByTypeName(Monitor::class, '_test_monitor_1', true),
            ]
        );
        $relation_2 = $this->createItem(
            Asset_PeripheralAsset::class,
            [
                'itemtype_asset'      => $classname_2,
                'items_id_asset'      => $item_2->getID(),
                'itemtype_peripheral' => Monitor::class,
                'items_id_peripheral' => getItemByTypeName(Monitor::class, '_test_monitor_1', true),
            ]
        );
        $displaypref_1   = $this->createItem(
            DisplayPreference::class,
            [
                'itemtype' => $classname_1,
                'num'      => 1429, // Number of monitors
                'users_id' => 0,
            ]
        );
        $displaypref_2   = $this->createItem(
            DisplayPreference::class,
            [
                'itemtype' => $classname_2,
                'num'      => 1429, // Number of monitors
                'users_id' => 0,
            ]
        );

        $item_1_logs_criteria = [
            'itemtype' => $classname_1,
        ];
        $item_2_logs_criteria = [
            'itemtype' => $classname_2,
        ];

        // Ensure relation, display preferences and logs exists, and class is registered to global config
        $this->object(Asset_PeripheralAsset::getById($relation_1->getID()))->isInstanceOf(Asset_PeripheralAsset::class);
        $this->object(DisplayPreference::getById($displaypref_1->getID()))->isInstanceOf(DisplayPreference::class);
        $this->integer(countElementsInTable(Log::getTable(), $item_1_logs_criteria))->isEqualTo(2); //create + add relation
        $this->object(Asset_PeripheralAsset::getById($relation_2->getID()))->isInstanceOf(Asset_PeripheralAsset::class);
        $this->object(DisplayPreference::getById($displaypref_2->getID()))->isInstanceOf(DisplayPreference::class);
        $this->integer(countElementsInTable(Log::getTable(), $item_2_logs_criteria))->isEqualTo(2); //create + add relation
        $this->array($CFG_GLPI['peripheralhost_types'])->contains($classname_1);
        $this->array($CFG_GLPI['peripheralhost_types'])->contains($classname_2);

        // Disable capacity and check that relations have been cleaned, and class is unregistered from global config
        $this->boolean($definition_1->update(['id' => $definition_1->getID(), 'capacities' => []]))->isTrue();
        $this->boolean(Asset_PeripheralAsset::getById($relation_1->getID()))->isFalse();
        $this->boolean(DisplayPreference::getById($displaypref_1->getID()))->isFalse();
        $this->integer(countElementsInTable(Log::getTable(), $item_1_logs_criteria))->isEqualTo(0);
        $this->array($CFG_GLPI['peripheralhost_types'])->notContains($classname_1);

        // Ensure relations, logs and global registration are preserved for other definition
        $this->object(Asset_PeripheralAsset::getById($relation_2->getID()))->isInstanceOf(Asset_PeripheralAsset::class);
        $this->object(DisplayPreference::getById($displaypref_2->getID()))->isInstanceOf(DisplayPreference::class);
        $this->integer(countElementsInTable(Log::getTable(), $item_2_logs_criteria))->isEqualTo(2);
        $this->array($CFG_GLPI['peripheralhost_types'])->contains($classname_2);
    }

    public function testCloneAsset()
    {
        $definition = $this->initAssetDefinition(
            capacities: [\Glpi\Asset\Capacity\HasPeripheralAssetsCapacity::class]
        );
        $class = $definition->getAssetClassName();
        $entity = $this->getTestRootEntity(true);

        /** @var \Glpi\Asset\Asset $asset */
        $asset = $this->createItem(
            $class,
            [
                'name'        => 'Test asset',
                'entities_id' => $entity,
            ]
        );

        $this->createItem(
            Asset_PeripheralAsset::class,
            [
                'itemtype_asset'      => $class,
                'items_id_asset'      => $asset->getID(),
                'itemtype_peripheral' => Monitor::class,
                'items_id_peripheral' => getItemByTypeName(Monitor::class, '_test_monitor_1', true),
            ]
        );
        $this->integer($clone_id = $asset->clone())->isGreaterThan(0);
        $this->array(getAllDataFromTable(Asset_PeripheralAsset::getTable(), [
            'itemtype_asset'      => $class,
            'items_id_asset'      => $clone_id,
            'itemtype_peripheral' => Monitor::class,
            'items_id_peripheral' => getItemByTypeName(Monitor::class, '_test_monitor_1', true),
        ]))->hasSize(1);
    }

    public function provideIsUsed(): iterable
    {
        // Cannot be tested with the generic method
        return [];
    }

    public function provideGetCapacityUsageDescription(): iterable
    {
        // Cannot be tested with the generic method
        return [];
    }

    /**
     * Cannot be tested with the generic method
     *
     * @ignore
     * @dataProvider provideIsUsed
     *
     * @TODO Move generic test in a trait.
     */
    public function testIsUsed(
        string $target_classname,
        array $target_fields = [],
        ?string $relation_classname = null,
        array $relation_fields = [],
    ): void {
    }

    /**
     * Cannot be tested with the generic method
     *
     * @ignore
     * @dataProvider provideGetCapacityUsageDescription
     *
     * @TODO Move generic test in a trait.
     */
    public function testGetCapacityUsageDescription(
        string $target_classname,
        string $expected,
        array $target_fields = [],
        ?string $relation_classname = null,
        array $relation_fields = [],
        array $expected_results = [[1, 1], [2, 1], [2, 2]],
    ): void {
    }

    public function testIsUsedCustom(): void
    {
        global $CFG_GLPI;

        $entity_id = $this->getTestRootEntity(true);

        foreach ($CFG_GLPI['directconnect_types'] as $peripheral_itemtype) {
            $definition = $this->initAssetDefinition(
                capacities: [$this->getTargetCapacity()]
            );
            $class = $definition->getAssetClassName();

            $asset = $this->createItem(
                $class,
                [
                    'name'        => __FUNCTION__,
                    'entities_id' => $entity_id,
                ]
            );

            $peripheral = $this->createItem(
                $peripheral_itemtype,
                [
                    'name'        => __FUNCTION__,
                    'entities_id' => $entity_id,
                ]
            );

            $capacity = new \Glpi\Asset\Capacity\HasPeripheralAssetsCapacity();
            $this->boolean($capacity->isUsed($class))->isFalse();

            $this->createItem(
                Asset_PeripheralAsset::class,
                [
                    'itemtype_asset'      => $class,
                    'items_id_asset'      => $asset->getID(),
                    'itemtype_peripheral' => $peripheral_itemtype,
                    'items_id_peripheral' => $peripheral->getID(),
                ]
            );
            $this->boolean($capacity->isUsed($class))->isTrue();
        }
    }

    public function testGetCapacityUsageDescriptionCustom(): void
    {
        global $CFG_GLPI;

        $entity_id = $this->getTestRootEntity(true);

        $definition = $this->initAssetDefinition(
            capacities: [$this->getTargetCapacity()]
        );
        $class = $definition->getAssetClassName();

        $capacity = new \Glpi\Asset\Capacity\HasPeripheralAssetsCapacity();
        $this->string($capacity->getCapacityUsageDescription($class))->isEqualTo('0 peripheral assets attached to 0 assets');

        $count_assets      = 0;
        $count_peripherals = 0;
        while ($count_assets < 3) {
            $asset = $this->createItem(
                $class,
                [
                    'name'        => __FUNCTION__,
                    'entities_id' => $entity_id,
                ]
            );
            $count_assets++;

            foreach ($CFG_GLPI['directconnect_types'] as $peripheral_itemtype) {
                $peripheral = $this->createItem(
                    $peripheral_itemtype,
                    [
                        'name'        => __FUNCTION__,
                        'entities_id' => $entity_id,
                    ]
                );

                $this->createItem(
                    Asset_PeripheralAsset::class,
                    [
                        'itemtype_asset'      => $class,
                        'items_id_asset'      => $asset->getID(),
                        'itemtype_peripheral' => $peripheral_itemtype,
                        'items_id_peripheral' => $peripheral->getID(),
                    ]
                );
                $count_peripherals++;

                $this->string($capacity->getCapacityUsageDescription($class))
                    ->isEqualTo(sprintf('%d peripheral assets attached to %d assets', $count_peripherals, $count_assets));
            }
        }
    }
}
