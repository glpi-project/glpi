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
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasHistoryCapacity;
use Glpi\Asset\Capacity\HasNotepadCapacity;
use Glpi\Asset\Capacity\HasRemoteManagementCapacity;
use Glpi\Tests\Glpi\Asset\CapacityUsageTestTrait;
use Item_RemoteManagement;
use Log;

class HasRemoteManagementCapacityTest extends DbTestCase
{
    use CapacityUsageTestTrait;

    protected function getTargetCapacity(): string
    {
        return HasRemoteManagementCapacity::class;
    }

    public function testCapacityActivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasRemoteManagementCapacity::class),
                new Capacity(name: HasNotepadCapacity::class),
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasHistoryCapacity::class),
            ]
        );
        $classname_2  = $definition_2->getAssetClassName();
        $definition_3 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasRemoteManagementCapacity::class),
                new Capacity(name: HasHistoryCapacity::class),
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
                $this->assertContains($classname, $CFG_GLPI['remote_management_types']);
            } else {
                $this->assertNotContains($classname, $CFG_GLPI['remote_management_types']);
            }

            // Check that the corresponding tab is present on items
            $item = $this->createItem($classname, ['name' => __FUNCTION__, 'entities_id' => $root_entity_id]);
            $this->login(); // must be logged in to get tabs list
            if ($has_capacity) {
                $this->assertArrayHasKey('Item_RemoteManagement$1', $item->defineAllTabs());
            } else {
                $this->assertArrayNotHasKey('Item_RemoteManagement$1', $item->defineAllTabs());
            }

            // Check that the related search options are available
            $so_keys = [1220, 1221];
            $options = $item->getOptions();
            foreach ($so_keys as $so_key) {
                if ($has_capacity) {
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
                new Capacity(name: HasRemoteManagementCapacity::class),
                new Capacity(name: HasHistoryCapacity::class),
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasRemoteManagementCapacity::class),
                new Capacity(name: HasHistoryCapacity::class),
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

        $remotemanagement_item_1 = $this->createItem(
            Item_RemoteManagement::class,
            [
                'itemtype'     => $item_1::class,
                'items_id'     => $item_1->getID(),
                'type' => Item_RemoteManagement::ANYDESK,
            ]
        );
        $remotemanagement_item_2 = $this->createItem(
            Item_RemoteManagement::class,
            [
                'itemtype'     => $item_2::class,
                'items_id'     => $item_2->getID(),
                'type' => Item_RemoteManagement::ANYDESK,
            ]
        );
        $displaypref_1   = $this->createItem(
            DisplayPreference::class,
            [
                'itemtype' => $classname_1,
                'num'      => 1220, // remoteid
                'users_id' => 0,
            ]
        );
        $displaypref_2   = $this->createItem(
            DisplayPreference::class,
            [
                'itemtype' => $classname_2,
                'num'      => 1221, // remoteid
                'users_id' => 0,
            ]
        );

        $item_1_logs_criteria = [
            'itemtype'      => $classname_1,
        ];
        $item_2_logs_criteria = [
            'itemtype'      => $classname_2,
        ];

        // Ensure relation, display preferences and logs exists, and class is registered to global config
        $this->assertInstanceOf(Item_RemoteManagement::class, Item_RemoteManagement::getById($remotemanagement_item_1->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_1->getID()));
        $this->assertEquals(2, countElementsInTable(Log::getTable(), $item_1_logs_criteria)); //create + add volume
        $this->assertInstanceOf(Item_RemoteManagement::class, Item_RemoteManagement::getById($remotemanagement_item_2->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_2->getID()));
        $this->assertEquals(2, countElementsInTable(Log::getTable(), $item_2_logs_criteria)); //create + add volume
        $this->assertContains($classname_1, $CFG_GLPI['remote_management_types']);
        $this->assertContains($classname_2, $CFG_GLPI['remote_management_types']);

        // Disable capacity and check that relations have been cleaned, and class is unregistered from global config
        $this->assertTrue($definition_1->update(['id' => $definition_1->getID(), 'capacities' => []]));
        $this->assertFalse(Item_RemoteManagement::getById($remotemanagement_item_1->getID()));
        $this->assertFalse(DisplayPreference::getById($displaypref_1->getID()));
        $this->assertEquals(0, countElementsInTable(Log::getTable(), $item_1_logs_criteria));
        $this->assertNotContains($classname_1, $CFG_GLPI['remote_management_types']);

        // Ensure relations, logs and global registration are preserved for other definition
        $this->assertInstanceOf(Item_RemoteManagement::class, Item_RemoteManagement::getById($remotemanagement_item_2->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_2->getID()));
        $this->assertEquals(2, countElementsInTable(Log::getTable(), $item_2_logs_criteria));
        $this->assertContains($classname_2, $CFG_GLPI['remote_management_types']);
    }

    public function testCloneAsset()
    {
        $definition = $this->initAssetDefinition(
            capacities: [new Capacity(name: HasRemoteManagementCapacity::class)]
        );
        $class = $definition->getAssetClassName();
        $entity = $this->getTestRootEntity(true);

        $asset = $this->createItem(
            $class,
            [
                'name'        => 'Test asset',
                'entities_id' => $entity,
            ]
        );

        $this->createItem(
            Item_RemoteManagement::class,
            [
                'itemtype' => $class,
                'items_id' => $asset->getID(),
                'type'     => 'anydesk',
                'remoteid' => 'abcdef123456',
            ]
        );

        $this->assertGreaterThan(0, $clone_id = $asset->clone());
        $this->assertCount(
            1,
            getAllDataFromTable(Item_RemoteManagement::getTable(), [
                'items_id' => $clone_id,
                'itemtype' => $class,
                'items_id' => $clone_id,
                'type'     => 'anydesk',
                'remoteid' => 'abcdef123456',
            ])
        );
    }

    public static function provideIsUsed(): iterable
    {
        yield [
            'target_classname' => Item_RemoteManagement::class,
        ];
    }

    public static function provideGetCapacityUsageDescription(): iterable
    {
        yield [
            'target_classname' => Item_RemoteManagement::class,
            'expected' => '%d remote management items attached to %d assets',
        ];
    }
}
