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

use DatabaseInstance;
use Entity;
use Glpi\Tests\CapacityTestCase;
use Profile;

class HasDatabaseInstanceCapacity extends CapacityTestCase
{
    /**
     * Get the tested capacity class.
     *
     * @return string
     */
    protected function getTargetCapacity(): string
    {
        return \Glpi\Asset\Capacity\HasDatabaseInstanceCapacity::class;
    }

    public function testCapacityActivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $superadmin_p_id = getItemByTypeName(Profile::class, 'Super-Admin', true);
        $profiles_matrix = [
            $superadmin_p_id => [
                READ   => 1,
            ],
        ];

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasDatabaseInstanceCapacity::class,
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
            ],
            profiles: $profiles_matrix
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
                \Glpi\Asset\Capacity\HasDatabaseInstanceCapacity::class,
                \Glpi\Asset\Capacity\HasNotepadCapacity::class,
            ],
            profiles: $profiles_matrix
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
                $this->array($CFG_GLPI['databaseinstance_types'])->contains($classname);
            } else {
                $this->array($CFG_GLPI['databaseinstance_types'])->notContains($classname);
            }

            // Check that the corresponding tab is present on items
            $item = $this->createItem($classname, ['name' => __FUNCTION__, 'entities_id' => $root_entity_id]);
            $this->login(); // must be logged in to get tabs list
            if ($has_capacity) {
                $this->array($item->defineAllTabs())->hasKey('DatabaseInstance$1');
            } else {
                $this->array($item->defineAllTabs())->notHasKey('DatabaseInstance$1');
            }
        }
    }

    public function testCapacityDeactivation(): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        // Capacity needs specific rights
        $superadmin_p_id = getItemByTypeName(Profile::class, 'Super-Admin', true);
        $profiles_matrix = [
            $superadmin_p_id => [
                READ   => 1,
                UPDATE => 1,
                CREATE => 1,
            ],
        ];

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasDatabaseInstanceCapacity::class,
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
            ],
            profiles: $profiles_matrix
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasDatabaseInstanceCapacity::class,
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
            ],
            profiles: $profiles_matrix
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

        $dbinstance_item_1 = $this->createItem(
            DatabaseInstance::class,
            [
                'name' => __FUNCTION__,
                'entities_id' => $root_entity_id,
                'itemtype'     => $item_1::getType(),
                'items_id'     => $item_1->getID(),
            ]
        );
        $dbinstance_item_2 = $this->createItem(
            DatabaseInstance::class,
            [
                'name' => __FUNCTION__,
                'entities_id' => $root_entity_id,
                'itemtype'     => $item_2::getType(),
                'items_id'     => $item_2->getID(),
            ]
        );

        $dbi_1 = DatabaseInstance::getById($dbinstance_item_1->getID());
        $this->object($dbi_1)->isInstanceOf(DatabaseInstance::class);
        $this->string($dbi_1->fields['itemtype'])->isEqualTo($classname_1);
        $this->integer($dbi_1->fields['items_id'])->isGreaterThan(0);
        $dbi_2 = DatabaseInstance::getById($dbinstance_item_2->getID());
        $this->object($dbi_2)->isInstanceOf(DatabaseInstance::class);
        $this->string($dbi_2->fields['itemtype'])->isEqualTo($classname_2);
        $this->integer($dbi_2->fields['items_id'])->isGreaterThan(0);
        $this->array($CFG_GLPI['databaseinstance_types'])->contains($classname_1);
        $this->array($CFG_GLPI['databaseinstance_types'])->contains($classname_2);

        $this->boolean($definition_1->update(['id' => $definition_1->getID(), 'capacities' => []]))->isTrue();
        $dbi_1->getFromDB($dbi_1->getID());
        $this->string($dbi_1->fields['itemtype'])->isEqualTo('');
        $this->integer($dbi_1->fields['items_id'])->isEqualTo(0);
        $this->array($CFG_GLPI['databaseinstance_types'])->notContains($classname_1);

        $dbi_2->getFromDB($dbi_2->getID());
        $this->string($dbi_2->fields['itemtype'])->isEqualTo($classname_2);
        $this->integer($dbi_2->fields['items_id'])->isGreaterThan(0);
        $this->array($CFG_GLPI['databaseinstance_types'])->contains($classname_2);
    }

    public function provideIsUsed(): iterable
    {
        yield [
            'target_classname' => DatabaseInstance::class,
        ];
    }

    public function provideGetCapacityUsageDescription(): iterable
    {
        yield [
            'target_classname' => DatabaseInstance::class,
            'expected' => '%d database instances attached to %d assets',
            'expected_results' => [
                [1, 1],
                [2, 1],
                [2, 1],
            ]
        ];
    }
}
