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
use Entity;
use Glpi\PHPUnit\Tests\Glpi\Asset\CapacityUsageTestTrait;
use KnowbaseItem;
use KnowbaseItem_Item;
use Log;

class HasKnowbaseCapacityTest extends DbTestCase
{
    use CapacityUsageTestTrait;

    protected function getTargetCapacity(): string
    {
        return \Glpi\Asset\Capacity\HasKnowbaseCapacity::class;
    }

    public function testCapacityActivation(): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasKnowbaseCapacity::class,
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
                \Glpi\Asset\Capacity\HasKnowbaseCapacity::class,
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
                $this->assertContains($classname, $CFG_GLPI['kb_types']);
            } else {
                $this->assertNotContains($classname, $CFG_GLPI['kb_types']);
            }

            // Check that the corresponding tab is present on items
            $item = $this->createItem($classname, ['name' => __FUNCTION__, 'entities_id' => $root_entity_id]);
            $this->login(); // must be logged in to get tabs list
            if ($has_capacity) {
                $this->assertArrayHasKey('KnowbaseItem_Item$1', $item->defineAllTabs());
            } else {
                $this->assertArrayNotHasKey('KnowbaseItem_Item$1', $item->defineAllTabs());
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
                \Glpi\Asset\Capacity\HasKnowbaseCapacity::class,
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasKnowbaseCapacity::class,
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

        $kb_item = $this->createItem(
            \KnowbaseItem::class,
            [
                'name' => 'kb article',
                'answer' => 'kb answer',
            ]
        );
        $kbitem_item_1 = $this->createItem(
            KnowbaseItem_Item::class,
            [
                'itemtype'     => $item_1::class,
                'items_id'     => $item_1->getID(),
                'knowbaseitems_id' => $kb_item->getID(),
            ]
        );
        $kbitem_item_2 = $this->createItem(
            KnowbaseItem_Item::class,
            [
                'itemtype'     => $item_2::class,
                'items_id'     => $item_2->getID(),
                'knowbaseitems_id' => $kb_item->getID(),
            ]
        );

        $item_1_logs_criteria = [
            'itemtype'      => $classname_1,
        ];
        $item_2_logs_criteria = [
            'itemtype'      => $classname_2,
        ];

        // Ensure relation and logs exists, and class is registered to global config
        $this->assertInstanceOf(KnowbaseItem_Item::class, KnowbaseItem_Item::getById($kbitem_item_1->getID()));
        $this->assertEquals(2, countElementsInTable(Log::getTable(), $item_1_logs_criteria)); //create + add kb item
        $this->assertInstanceOf(KnowbaseItem_Item::class, KnowbaseItem_Item::getById($kbitem_item_2->getID()));
        $this->assertEquals(2, countElementsInTable(Log::getTable(), $item_2_logs_criteria)); //create + add kb item
        $this->assertContains($classname_1, $CFG_GLPI['kb_types']);
        $this->assertContains($classname_2, $CFG_GLPI['kb_types']);

        // Disable capacity and check that relations have been cleaned, and class is unregistered from global config
        $this->assertTrue($definition_1->update(['id' => $definition_1->getID(), 'capacities' => []]));
        $this->assertFalse(KnowbaseItem_Item::getById($kbitem_item_1->getID()));
        $this->assertEquals(0, countElementsInTable(Log::getTable(), $item_1_logs_criteria));
        $this->assertNotContains($classname_1, $CFG_GLPI['kb_types']);

        // Ensure relations, logs and global registration are preserved for other definition
        $this->assertInstanceOf(KnowbaseItem_Item::class, KnowbaseItem_Item::getById($kbitem_item_2->getID()));
        $this->assertEquals(2, countElementsInTable(Log::getTable(), $item_2_logs_criteria));
        $this->assertContains($classname_2, $CFG_GLPI['kb_types']);
    }

    public function testCloneAsset()
    {
        $definition = $this->initAssetDefinition(
            capacities: [\Glpi\Asset\Capacity\HasKnowbaseCapacity::class]
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

        $kb = $this->createItem(KnowbaseItem::class, [
            'name'        => 'KB',
            'answer'      => 'KB answer',
            'entities_id' => $entity,
        ]);

        $this->createItem(
            KnowbaseItem_Item::class,
            [
                'itemtype'         => $class,
                'items_id'         => $asset->getID(),
                'knowbaseitems_id' => $kb->getID(),
            ]
        );

        $this->assertGreaterThan(0, $clone_id = $asset->clone());
        $this->assertCount(
            1,
            getAllDataFromTable(KnowbaseItem_Item::getTable(), [
                'knowbaseitems_id' => $kb->getID(),
                'itemtype'         => $class,
                'items_id'         => $clone_id,
            ])
        );
    }

    public static function provideIsUsed(): iterable
    {
        yield [
            'target_classname' => KnowbaseItem::class,
            'relation_classname' => KnowbaseItem_Item::class,
        ];
    }

    public static function provideGetCapacityUsageDescription(): iterable
    {
        yield [
            'target_classname' => KnowbaseItem::class,
            'relation_classname' => KnowbaseItem_Item::class,
            'expected' => '%d knowbase items attached to %d assets'
        ];
    }
}
