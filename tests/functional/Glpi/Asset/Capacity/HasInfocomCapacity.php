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
use Glpi\Asset\Asset;
use Glpi\Tests\CapacityTestCase;
use Infocom;
use Log;

class HasInfocomCapacity extends CapacityTestCase
{
    /**
     * Get the tested capacity class.
     *
     * @return string
     */
    protected function getTargetCapacity(): string
    {
        return \Glpi\Asset\Capacity\HasInfocomCapacity::class;
    }

    public function testCapacityActivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
                \Glpi\Asset\Capacity\HasInfocomCapacity::class,
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasNotepadCapacity::class,
            ]
        );
        $classname_2  = $definition_2->getAssetClassName();
        $definition_3 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasDocumentsCapacity::class,
                \Glpi\Asset\Capacity\HasInfocomCapacity::class,
            ]
        );
        $classname_3  = $definition_3->getAssetClassName();

        $has_infocom_mapping = [
            $classname_1 => true,
            $classname_2 => false,
            $classname_3 => true,
        ];

        foreach ($has_infocom_mapping as $classname => $has_infocom) {
            // Check that the class is globally registered
            if ($has_infocom) {
                $this->array($CFG_GLPI['infocom_types'])->contains($classname);
                $this->boolean(Infocom::canApplyOn($classname))->isTrue();
                $this->array(Infocom::getItemtypesThatCanHave())->contains($classname);
            } else {
                $this->array($CFG_GLPI['infocom_types'])->notContains($classname);
                $this->boolean(Infocom::canApplyOn($classname))->isFalse();
                $this->array(Infocom::getItemtypesThatCanHave())->notContains($classname);
            }

            // Check that the corresponding tab is present on items
            $item = $this->createItem($classname, ['name' => __FUNCTION__, 'entities_id' => $root_entity_id]);
            $this->login(); // must be logged in to get tabs list
            if ($has_infocom) {
                $this->array($item->defineAllTabs())->hasKey('Infocom$1');
            } else {
                $this->array($item->defineAllTabs())->notHasKey('Infocom$1');
            }

            // Check that the releated search options are available
            $so_keys = [
                25, // Immobilization number
                26, // Order number
                27, // Delivery form
                28, // Invoice number
                37, // Date of purchase
                38, // Startup date
                50, // Budget
                51, // Warranty duration
                52, // Warranty info
                53, // Supplier
                54, // Value
                55, // Warranty extension value
                56, // Amortization duration
                57, // Amortization type
                58, // Amortization coefficient
                59, // Email alarms
                120, // Warranty expiration date
                122, // Infocom comments
                123, // Start date of warranty
                124, // Order date
                125, // Date of last physical inventory
                142, // Delivery date
                159, // Decommission date
                173, // Business criticity
            ];
            if ($has_infocom) {
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
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
                \Glpi\Asset\Capacity\HasInfocomCapacity::class,
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
                \Glpi\Asset\Capacity\HasInfocomCapacity::class,
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

        $infocom_1 = $this->createItem(
            Infocom::class,
            [
                'itemtype' => $item_1->getType(),
                'items_id' => $item_1->getID(),
            ]
        );
        $this->updateItem(
            Infocom::class,
            $infocom_1->getID(),
            [
                'order_date'    => date('Y-m-05'),
                'delivery_date' => date('Y-m-11'),
                'value'         => 100,
            ]
        );
        $infocom_2 = $this->createItem(
            Infocom::class,
            [
                'itemtype' => $item_2::getType(),
                'items_id' => $item_2->getID(),
            ]
        );
        $this->updateItem(
            Infocom::class,
            $infocom_2->getID(),
            [
                'delivery_date' => date('Y-m-23'),
                'value'         => 25.3,
            ]
        );
        $displaypref_1   = $this->createItem(
            DisplayPreference::class,
            [
                'itemtype' => $classname_1,
                'num'      => '54', // Infocom: value
                'users_id' => 0,
            ]
        );
        $displaypref_2   = $this->createItem(
            DisplayPreference::class,
            [
                'itemtype' => $classname_2,
                'num'      => '54', // Infocom: value
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
        $this->object(Infocom::getById($infocom_1->getID()))->isInstanceOf(Infocom::class);
        $this->object(DisplayPreference::getById($displaypref_1->getID()))->isInstanceOf(DisplayPreference::class);
        $this->integer(countElementsInTable(Log::getTable(), $item_1_logs_criteria))->isEqualTo(4); // creation + 3 infocom fields
        $this->object(Infocom::getById($infocom_2->getID()))->isInstanceOf(Infocom::class);
        $this->object(DisplayPreference::getById($displaypref_2->getID()))->isInstanceOf(DisplayPreference::class);
        $this->integer(countElementsInTable(Log::getTable(), $item_2_logs_criteria))->isEqualTo(3); // creation + 2 infocom fields
        $this->array($CFG_GLPI['infocom_types'])->contains($classname_1);
        $this->array($CFG_GLPI['infocom_types'])->contains($classname_2);

        // Disable capacity and check that relations have been cleaned, and class is unregistered from global config
        $this->boolean($definition_1->update(['id' => $definition_1->getID(), 'capacities' => []]))->isTrue();
        $this->boolean(Infocom::getById($infocom_1->getID()))->isFalse();
        $this->boolean(DisplayPreference::getById($displaypref_1->getID()))->isFalse();
        $this->integer(countElementsInTable(Log::getTable(), $item_1_logs_criteria))->isEqualTo(0);
        $this->array($CFG_GLPI['infocom_types'])->notContains($classname_1);

        // Ensure relations, logs and global registration are preserved for other definition
        $this->object(Infocom::getById($infocom_2->getID()))->isInstanceOf(Infocom::class);
        $this->object(DisplayPreference::getById($displaypref_2->getID()))->isInstanceOf(DisplayPreference::class);
        $this->integer(countElementsInTable(Log::getTable(), $item_2_logs_criteria))->isEqualTo(3);
        $this->array($CFG_GLPI['infocom_types'])->contains($classname_2);
    }

    public function testCloneAsset()
    {
        $definition = $this->initAssetDefinition(
            capacities: [\Glpi\Asset\Capacity\HasInfocomCapacity::class]
        );
        $class = $definition->getAssetClassName();
        $entity = $this->getTestRootEntity(true);

        /** @var Asset $asset */
        $asset = $this->createItem($class, [
            'name'        => 'Test asset',
            'entities_id' => $entity,
        ]);

        $this->createItem(Infocom::class, [
            'itemtype' => $class,
            'items_id' => $asset->getID(),
            'delivery_date' => '2020-03-04',
            'value'         => 25.3,
        ]);

        $this->integer($clone_id = $asset->clone())->isGreaterThan(0);
        $this->array(getAllDataFromTable(Infocom::getTable(), [
            'items_id' => $clone_id,
            'itemtype' => $class,
            'delivery_date' => '2020-03-04',
            'value' => '25.3',
        ]))->hasSize(1);
    }

    public function provideIsUsed(): iterable
    {
        yield [
            'target_classname' => Infocom::class,
        ];
    }

    public function provideGetCapacityUsageDescription(): iterable
    {
        yield [
            'target_classname' => Infocom::class,
            'expected' => 'Used by %d of %d assets'
        ];
    }
}
