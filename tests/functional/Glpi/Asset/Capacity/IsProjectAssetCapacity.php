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
use Entity;
use Glpi\Tests\Asset\CapacityUsageTestTrait;
use Item_Project;
use Log;
use Project;

class IsProjectAssetCapacity extends DbTestCase
{
    use CapacityUsageTestTrait;

    protected function getTargetCapacity(): string
    {
        return \Glpi\Asset\Capacity\IsProjectAssetCapacity::class;
    }

    public function testCapacityActivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
                \Glpi\Asset\Capacity\IsProjectAssetCapacity::class,
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
                \Glpi\Asset\Capacity\IsProjectAssetCapacity::class,
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
                $this->array($CFG_GLPI['project_asset_types'])->contains($classname);
            } else {
                $this->array($CFG_GLPI['project_asset_types'])->notContains($classname);
            }

            // Check that the corresponding tab is present on items
            $item = $this->createItem($classname, ['name' => __FUNCTION__, 'entities_id' => $root_entity_id]);

            // Check that the releated search options are available
            $so_keys = [
                450, // Project name
            ];
            if ($is_project_asset) {
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
                \Glpi\Asset\Capacity\IsProjectAssetCapacity::class,
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
                \Glpi\Asset\Capacity\IsProjectAssetCapacity::class,
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
        $this->object(Item_Project::getById($project_item_1->getID()))->isInstanceOf(Item_Project::class);
        $this->object(DisplayPreference::getById($displaypref_1->getID()))->isInstanceOf(DisplayPreference::class);
        $this->integer(countElementsInTable(Log::getTable(), $item_1_logs_criteria))->isEqualTo(2); // creation + project link
        $this->object(Item_Project::getById($project_item_2->getID()))->isInstanceOf(Item_Project::class);
        $this->object(DisplayPreference::getById($displaypref_2->getID()))->isInstanceOf(DisplayPreference::class);
        $this->integer(countElementsInTable(Log::getTable(), $item_2_logs_criteria))->isEqualTo(2); // creation + project link
        $this->array($CFG_GLPI['project_asset_types'])->contains($classname_1);
        $this->array($CFG_GLPI['project_asset_types'])->contains($classname_2);

        // Disable capacity and check that relations have been cleaned, and class is unregistered from global config
        $this->disableCapacity($definition_1, \Glpi\Asset\Capacity\IsProjectAssetCapacity::class);
        $this->boolean(Item_Project::getById($project_item_1->getID()))->isFalse();
        $this->boolean(DisplayPreference::getById($displaypref_1->getID()))->isFalse();
        $this->integer(countElementsInTable(Log::getTable(), $item_1_logs_criteria))->isEqualTo(1); // creation
        $this->array($CFG_GLPI['project_asset_types'])->notContains($classname_1);

        // Ensure relations, logs and global registration are preserved for other definition
        $this->object(Item_Project::getById($project_item_2->getID()))->isInstanceOf(Item_Project::class);
        $this->object(DisplayPreference::getById($displaypref_2->getID()))->isInstanceOf(DisplayPreference::class);
        $this->integer(countElementsInTable(Log::getTable(), $item_2_logs_criteria))->isEqualTo(2); // creation + project link
        $this->array($CFG_GLPI['project_asset_types'])->contains($classname_2);
    }

    public function testCloneAsset()
    {
        $definition = $this->initAssetDefinition(
            capacities: [\Glpi\Asset\Capacity\IsProjectAssetCapacity::class]
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

        $this->integer($clone_id = $asset->clone())->isGreaterThan(0);
        $this->array(getAllDataFromTable(Item_Project::getTable(), [
            'itemtype'     => $class,
            'items_id'     => $clone_id,
            'projects_id'  => $project->getID(),
        ]))->hasSize(1);
    }

    public function provideIsUsed(): iterable
    {
        yield [
            'target_classname'   => Project::class,
            'relation_classname' => Item_Project::class,
        ];
    }

    public function provideGetCapacityUsageDescription(): iterable
    {
        yield [
            'target_classname'   => Project::class,
            'relation_classname' => Item_Project::class,
            'expected' => '%d assets used in %d projects'
        ];
    }
}
