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
use Log;
use NetworkPort;

class HasNetworkPortCapacity extends DbTestCase
{
    use CapacityUsageTestTrait;

    protected function getTargetCapacity(): string
    {
        return \Glpi\Asset\Capacity\HasNetworkPortCapacity::class;
    }

    public function testCapacityActivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasNetworkPortCapacity::class,
                \Glpi\Asset\Capacity\HasNetworkPortCapacity::class,
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
                \Glpi\Asset\Capacity\HasNetworkPortCapacity::class,
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
                $this->array($CFG_GLPI['networkport_types'])->contains($classname);
            } else {
                $this->array($CFG_GLPI['networkport_types'])->notContains($classname);
            }

            // Check that the corresponding tab is present on items
            $item = $this->createItem($classname, ['name' => __FUNCTION__, 'entities_id' => $root_entity_id]);
            $this->login(); // must be logged in to get tabs list
            if ($has_capacity) {
                $this->array($item->defineAllTabs())->hasKey('NetworkPort$1');
            } else {
                $this->array($item->defineAllTabs())->notHasKey('NetworkPort$1');
            }

            // Check that the related search options are available
            $so_keys = [
                21, // MAC
                87, // Instanciation type
                88, // VLAN
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
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
                \Glpi\Asset\Capacity\HasDomainsCapacity::class,
                \Glpi\Asset\Capacity\HasNetworkPortCapacity::class,
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
                \Glpi\Asset\Capacity\HasDomainsCapacity::class,
                \Glpi\Asset\Capacity\HasNetworkPortCapacity::class,
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

        $item_networkport_1 = $this->createItem(
            NetworkPort::class,
            [
                'name' => 'NetworkPort',
                'entities_id' => $root_entity_id,
                'itemtype' => $item_1->getType(),
                'items_id' => $item_1->getID(),
            ]
        );

        $item_networkport_2 = $this->createItem(
            NetworkPort::class,
            [
                'name' => 'NetworkPort',
                'entities_id' => $root_entity_id,
                'itemtype' => $item_2->getType(),
                'items_id' => $item_2->getID(),
            ]
        );

        $displaypref_1   = $this->createItem(
            DisplayPreference::class,
            [
                'itemtype' => $classname_1,
                'users_id' => 0,
            ]
        );
        $displaypref_2   = $this->createItem(
            DisplayPreference::class,
            [
                'itemtype' => $classname_2,
                'users_id' => 0,
            ]
        );

        $item_1_logs_criteria = [
            'itemtype' => $classname_1,
            'itemtype_link' => NetworkPort::class,
        ];
        $item_2_logs_criteria = [
            'itemtype' => $classname_2,
            'itemtype_link' => NetworkPort::class,
        ];

        // Ensure relation, display preferences and logs exists, and class is registered to global config
        $this->object(NetworkPort::getById($item_networkport_1->getID()))->isInstanceOf(NetworkPort::class);
        $this->object(DisplayPreference::getById($displaypref_1->getID()))->isInstanceOf(DisplayPreference::class);
        $this->integer(countElementsInTable(Log::getTable(), $item_1_logs_criteria))->isEqualTo(1);
        $this->object(NetworkPort::getById($item_networkport_2->getID()))->isInstanceOf(NetworkPort::class);
        $this->object(DisplayPreference::getById($displaypref_2->getID()))->isInstanceOf(DisplayPreference::class);
        $this->integer(countElementsInTable(Log::getTable(), $item_2_logs_criteria))->isEqualTo(1);
        $this->array($CFG_GLPI['networkport_types'])->contains($classname_1);
        $this->array($CFG_GLPI['networkport_types'])->contains($classname_2);

        // Disable capacity and check that relations have been cleaned, and class is unregistered from global config
        $this->boolean($definition_1->update(['id' => $definition_1->getID(), 'capacities' => []]))->isTrue();
        $this->boolean(NetworkPort::getById($item_networkport_1->getID()))->isFalse();
        $this->integer(countElementsInTable(Log::getTable(), $item_1_logs_criteria))->isEqualTo(0);
        $this->array($CFG_GLPI['networkport_types'])->notContains($classname_1);

        // Ensure relations, logs and global registration are preserved for other definition
        $this->object(NetworkPort::getById($item_networkport_2->getID()))->isInstanceOf(NetworkPort::class);
        $this->object(DisplayPreference::getById($displaypref_2->getID()))->isInstanceOf(DisplayPreference::class);
        $this->integer(countElementsInTable(Log::getTable(), $item_2_logs_criteria))->isEqualTo(1);
        $this->array($CFG_GLPI['networkport_types'])->contains($classname_2);
    }

    public function tesCloneAsset()
    {
        $definition = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasNetworkPortCapacity::class,
            ]
        );
        $class = $definition->getAssetClassName();
        $entity = $this->getTestRootEntity(true);

        /** @var \Glpi\Asset\Asset $asset */
        $asset = $this->createItem($class, [
            'name'        => 'Test asset',
            'entities_id' => $entity,
        ]);

        $networkport = $this->createItem(NetworkPort::class, [
            'name'        => 'Test networkport',
            'entities_id' => $entity,
            'itemtype'    => $class,
            'items_id'    => $asset->getID(),
        ]);

        $this->integer($clone_id = $asset->clone())->isGreaterThan(0);
        $this->array(getAllDataFromTable(NetworkPort::getTable(), [
            'id' => $networkport->getID(),
            'itemtype' => $asset::getType(),
            'items_id' => $clone_id,
        ]))->hasSize(1);
    }

    public function provideIsUsed(): iterable
    {
        yield [
            'target_classname' => NetworkPort::class,
        ];
    }

    public function provideGetCapacityUsageDescription(): iterable
    {
        yield [
            'target_classname' => NetworkPort::class,
            'expected' => '%d networkports attached to %d assets'
        ];
    }
}
