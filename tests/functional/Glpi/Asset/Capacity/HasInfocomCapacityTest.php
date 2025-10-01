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
use Entity;
use Glpi\Asset\Asset;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasDocumentsCapacity;
use Glpi\Asset\Capacity\HasHistoryCapacity;
use Glpi\Asset\Capacity\HasInfocomCapacity;
use Glpi\Asset\Capacity\HasNotepadCapacity;
use Glpi\Tests\Glpi\Asset\CapacityUsageTestTrait;
use Infocom;
use Log;

class HasInfocomCapacityTest extends DbTestCase
{
    use CapacityUsageTestTrait;

    protected function getTargetCapacity(): string
    {
        return HasInfocomCapacity::class;
    }

    public function testCapacityActivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasHistoryCapacity::class),
                new Capacity(name: HasInfocomCapacity::class),
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasNotepadCapacity::class),
            ]
        );
        $classname_2  = $definition_2->getAssetClassName();
        $definition_3 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasDocumentsCapacity::class),
                new Capacity(name: HasInfocomCapacity::class),
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
                $this->assertContains($classname, $CFG_GLPI['infocom_types']);
                $this->assertTrue(Infocom::canApplyOn($classname));
                $this->assertContains($classname, Infocom::getItemtypesThatCanHave());
            } else {
                $this->assertNotContains($classname, $CFG_GLPI['infocom_types']);
                $this->assertFalse(Infocom::canApplyOn($classname));
                $this->assertNotContains($classname, Infocom::getItemtypesThatCanHave());
            }

            // Check that the corresponding tab is present on items
            $item = $this->createItem($classname, ['name' => __FUNCTION__, 'entities_id' => $root_entity_id]);
            $this->login(); // must be logged in to get tabs list
            if ($has_infocom) {
                $this->assertArrayHasKey('Infocom$1', $item->defineAllTabs());
            } else {
                $this->assertArrayNotHasKey('Infocom$1', $item->defineAllTabs());
            }

            // Check that the related search options are available
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
            $options = $item->getOptions();
            foreach ($so_keys as $so_key) {
                if ($has_infocom) {
                    $this->assertArrayHasKey($so_key, $options);
                } else {
                    $this->assertArrayNotHasKey($so_key, $options);
                }
            }
        }
    }

    public function testCapacityDeactivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasHistoryCapacity::class),
                new Capacity(name: HasInfocomCapacity::class),
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasHistoryCapacity::class),
                new Capacity(name: HasInfocomCapacity::class),
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
        $this->assertInstanceOf(Infocom::class, Infocom::getById($infocom_1->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_1->getID()));
        $this->assertEquals(4, countElementsInTable(Log::getTable(), $item_1_logs_criteria)); // creation + 3 infocom fields
        $this->assertInstanceOf(Infocom::class, Infocom::getById($infocom_2->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_2->getID()));
        $this->assertEquals(3, countElementsInTable(Log::getTable(), $item_2_logs_criteria)); // creation + 2 infocom fields
        $this->assertContains($classname_1, $CFG_GLPI['infocom_types']);
        $this->assertContains($classname_2, $CFG_GLPI['infocom_types']);

        // Disable capacity and check that relations have been cleaned, and class is unregistered from global config
        $this->assertTrue($definition_1->update(['id' => $definition_1->getID(), 'capacities' => []]));
        $this->assertFalse(Infocom::getById($infocom_1->getID()));
        $this->assertFalse(DisplayPreference::getById($displaypref_1->getID()));
        $this->assertEquals(0, countElementsInTable(Log::getTable(), $item_1_logs_criteria));
        $this->assertNotContains($classname_1, $CFG_GLPI['infocom_types']);

        // Ensure relations, logs and global registration are preserved for other definition
        $this->assertInstanceOf(Infocom::class, Infocom::getById($infocom_2->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_2->getID()));
        $this->assertEquals(3, countElementsInTable(Log::getTable(), $item_2_logs_criteria));
        $this->assertContains($classname_2, $CFG_GLPI['infocom_types']);
    }

    public function testCloneAsset()
    {
        $definition = $this->initAssetDefinition(
            capacities: [new Capacity(name: HasInfocomCapacity::class)]
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

        $this->assertGreaterThan(0, $clone_id = $asset->clone());
        $this->assertCount(
            1,
            getAllDataFromTable(Infocom::getTable(), [
                'items_id' => $clone_id,
                'itemtype' => $class,
                'delivery_date' => '2020-03-04',
                'value' => '25.3',
            ])
        );
    }

    public static function provideIsUsed(): iterable
    {
        yield [
            'target_classname' => Infocom::class,
        ];
    }

    public static function provideGetCapacityUsageDescription(): iterable
    {
        yield [
            'target_classname' => Infocom::class,
            'expected' => 'Used by %d of %d assets',
        ];
    }
}
