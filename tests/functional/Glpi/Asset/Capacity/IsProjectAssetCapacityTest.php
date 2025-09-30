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
use Glpi\Asset\Capacity\HasDocumentsCapacity;
use Glpi\Asset\Capacity\HasHistoryCapacity;
use Glpi\Asset\Capacity\HasNotepadCapacity;
use Glpi\Asset\Capacity\IsProjectAssetCapacity;
use Glpi\Tests\Glpi\Asset\CapacityUsageTestTrait;
use Item_Project;
use Log;
use Project;

class IsProjectAssetCapacityTest extends DbTestCase
{
    use CapacityUsageTestTrait;

    protected function getTargetCapacity(): string
    {
        return IsProjectAssetCapacity::class;
    }

    public function testCapacityActivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasHistoryCapacity::class),
                new Capacity(name: IsProjectAssetCapacity::class),
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
                new Capacity(name: IsProjectAssetCapacity::class),
            ]
        );
        $classname_3  = $definition_3->getAssetClassName();

        $is_project_asset_mapping = [
            $classname_1 => true,
            $classname_2 => false,
            $classname_3 => true,
        ];

        foreach ($is_project_asset_mapping as $classname => $is_project_asset) {
            // Check that the class is globally registered
            if ($is_project_asset) {
                $this->assertContains($classname, $CFG_GLPI['project_asset_types']);
            } else {
                $this->assertNotContains($classname, $CFG_GLPI['project_asset_types']);
            }

            // Check that the corresponding tab is present on items
            $item = $this->createItem($classname, ['name' => __FUNCTION__, 'entities_id' => $root_entity_id]);

            // Check that the related search options are available
            $so_keys = [
                450, // Project name
            ];
            $options = $item->getOptions();
            foreach ($so_keys as $so_key) {
                if ($is_project_asset) {
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
                new Capacity(name: IsProjectAssetCapacity::class),
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasHistoryCapacity::class),
                new Capacity(name: IsProjectAssetCapacity::class),
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

        $project_1 = $this->createItem(
            Project::class,
            [
                'name' => __FUNCTION__ . '1',
            ]
        );
        $project_2 = $this->createItem(
            Project::class,
            [
                'name' => __FUNCTION__ . '2',
            ]
        );

        $project_item_1 = $this->createItem(
            Item_Project::class,
            [
                'itemtype'    => $item_1::class,
                'items_id'    => $item_1->getID(),
                'projects_id' => $project_1->getID(),
            ]
        );
        $project_item_2 = $this->createItem(
            Item_Project::class,
            [
                'itemtype'    => $item_2::class,
                'items_id'    => $item_2->getID(),
                'projects_id' => $project_2->getID(),
            ]
        );

        $displaypref_1   = $this->createItem(
            DisplayPreference::class,
            [
                'itemtype' => $classname_1,
                'num'      => '450', // Project name
                'users_id' => 0,
            ]
        );
        $displaypref_2   = $this->createItem(
            DisplayPreference::class,
            [
                'itemtype' => $classname_2,
                'num'      => '450', // Project name
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
        $this->assertInstanceOf(Item_Project::class, Item_Project::getById($project_item_1->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_1->getID()));
        $this->assertEquals(2, countElementsInTable(Log::getTable(), $item_1_logs_criteria)); // creation + project link
        $this->assertInstanceOf(Item_Project::class, Item_Project::getById($project_item_2->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_2->getID()));
        $this->assertEquals(2, countElementsInTable(Log::getTable(), $item_2_logs_criteria)); // creation + project link
        $this->assertContains($classname_1, $CFG_GLPI['project_asset_types']);
        $this->assertContains($classname_2, $CFG_GLPI['project_asset_types']);

        // Disable capacity and check that relations have been cleaned, and class is unregistered from global config
        $this->disableCapacity($definition_1, IsProjectAssetCapacity::class);
        $this->assertFalse(Item_Project::getById($project_item_1->getID()));
        $this->assertFalse(DisplayPreference::getById($displaypref_1->getID()));
        $this->assertEquals(1, countElementsInTable(Log::getTable(), $item_1_logs_criteria)); // creation
        $this->assertNotContains($classname_1, $CFG_GLPI['project_asset_types']);

        // Ensure relations, logs and global registration are preserved for other definition
        $this->assertInstanceOf(Item_Project::class, Item_Project::getById($project_item_2->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_2->getID()));
        $this->assertEquals(2, countElementsInTable(Log::getTable(), $item_2_logs_criteria)); // creation + project link
        $this->assertContains($classname_2, $CFG_GLPI['project_asset_types']);
    }

    public function testCloneAsset()
    {
        $definition = $this->initAssetDefinition(
            capacities: [new Capacity(name: IsProjectAssetCapacity::class)]
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

        $project = $this->createItem(
            Project::class,
            [
                'name' => __FUNCTION__,
            ]
        );

        $this->createItem(
            Item_Project::class,
            [
                'itemtype'     => $class,
                'items_id'     => $asset->getID(),
                'projects_id'  => $project->getID(),
            ]
        );

        $this->assertGreaterThan(0, $clone_id = $asset->clone());
        $this->assertCount(
            1,
            getAllDataFromTable(Item_Project::getTable(), [
                'itemtype'     => $class,
                'items_id'     => $clone_id,
                'projects_id'  => $project->getID(),
            ])
        );
    }

    public static function provideIsUsed(): iterable
    {
        yield [
            'target_classname'   => Project::class,
            'relation_classname' => Item_Project::class,
        ];
    }

    public static function provideGetCapacityUsageDescription(): iterable
    {
        yield [
            'target_classname'   => Project::class,
            'relation_classname' => Item_Project::class,
            'expected' => '%d assets used in %d projects',
        ];
    }
}
