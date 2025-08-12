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

namespace tests\units\Glpi\Features;

use Group_Item;
use PHPUnit\Framework\Attributes\DataProvider;

class AssignableItem extends \DbTestCase
{
    protected function itemtypeProvider(): iterable
    {
        global $CFG_GLPI;

        foreach ($CFG_GLPI['assignable_types'] as $itemtype) {
            yield $itemtype => [
                'class' => $itemtype,
            ];
        }
    }

    #[DataProvider('itemtypeProvider')]
    public function testClassUsesTrait(string $class): void
    {
        $this->boolean(in_array(\Glpi\Features\AssignableItem::class, class_uses($class), true))->isTrue();
    }

    /**
     * Test adding an item with the groups_id/groups_id_tech field as an array and null.
     * Test updating an item with the groups_id/groups_id_tech field as an array and null.
     */
    #[DataProvider('itemtypeProvider')]
    public function testAddAndUpdateMultipleGroups(string $class): void
    {
        $this->login(); // login to bypass some rights checks (e.g. on domain records)

        $input = $this->getMinimalCreationInput($class);

        $item_1 = $this->createItem(
            $class,
            $input + [
                $class::getNameField() => __FUNCTION__ . ' 1',
                'groups_id'            => [1, 2],
                'groups_id_tech'       => [3],
            ]
        );
        $this->array($item_1->fields['groups_id'])->isEqualTo([1, 2]);
        $this->array($item_1->fields['groups_id_tech'])->isEqualTo([3]);

        $item_2 = $this->createItem(
            $class,
            $input + [
                $class::getNameField() => __FUNCTION__ . ' 2',
                'groups_id'            => null,
                'groups_id_tech'       => null,
            ]
        );
        $this->array($item_2->fields['groups_id'])->isEmpty();
        $this->array($item_2->fields['groups_id_tech'])->isEmpty();

        // Update both items. Asset 1 will have the groups set to null and item 2 will have the groups set to an array.
        $updated = $item_1->update(['id' => $item_1->getID(), 'groups_id' => null, 'groups_id_tech' => null]);
        $this->boolean($updated)->isTrue();
        $this->array($item_1->fields['groups_id'])->isEmpty();
        $this->array($item_1->fields['groups_id_tech'])->isEmpty();

        $updated = $item_2->update(['id' => $item_2->getID(), 'groups_id' => [5, 6], 'groups_id_tech' => [7]]);
        $this->boolean($updated)->isTrue();
        $this->array($item_2->fields['groups_id'])->isEqualTo([5, 6]);
        $this->array($item_2->fields['groups_id_tech'])->isEqualTo([7]);

        // Test updating array to array
        $updated = $item_2->update(['id' => $item_2->getID(), 'groups_id' => [1, 2], 'groups_id_tech' => [4, 5]]);
        $this->boolean($updated)->isTrue();
        $this->array($item_2->fields['groups_id'])->isEqualTo([1, 2]);
        $this->array($item_2->fields['groups_id_tech'])->isEqualTo([4, 5]);
    }

    /**
     * Test the loading item which still have integer values for groups_id/groups_id_tech (0 for no group).
     * The value should be automatically normalized to an array. If the group was '0', the array should be empty.
     */
    #[DataProvider('itemtypeProvider')]
    public function testLoadGroupsFromDb(string $class): void
    {
        global $DB;

        $input = $this->getMinimalCreationInput($class);

        $item = $this->createItem(
            $class,
            $input + [
                $class::getNameField() => __FUNCTION__,
            ]
        );
        $this->array($item->fields['groups_id'])->isEmpty();
        $this->array($item->fields['groups_id_tech'])->isEmpty();

        $DB->insert(
            'glpi_groups_items',
            [
                'itemtype'  => $class,
                'items_id'  => $item->getID(),
                'groups_id' => 1,
                'type'      => Group_Item::GROUP_TYPE_NORMAL,
            ],
        );
        $DB->insert(
            'glpi_groups_items',
            [
                'itemtype'  => $class,
                'items_id'  => $item->getID(),
                'groups_id' => 2,
                'type'      => Group_Item::GROUP_TYPE_TECH,
            ],
        );

        $this->boolean($item->getFromDB($item->getID()))->isTrue();
        $this->array($item->fields['groups_id'])->isEqualTo([1]);
        $this->array($item->fields['groups_id_tech'])->isEqualTo([2]);

        $DB->insert(
            'glpi_groups_items',
            [
                'itemtype'  => $class,
                'items_id'  => $item->getID(),
                'groups_id' => 3,
                'type'      => Group_Item::GROUP_TYPE_NORMAL,
            ],
        );
        $DB->insert(
            'glpi_groups_items',
            [
                'itemtype'  => $class,
                'items_id'  => $item->getID(),
                'groups_id' => 4,
                'type'      => Group_Item::GROUP_TYPE_TECH,
            ],
        );
        $this->boolean($item->getFromDB($item->getID()))->isTrue();
        $this->array($item->fields['groups_id'])->isEqualTo([1, 3]);
        $this->array($item->fields['groups_id_tech'])->isEqualTo([2, 4]);
    }

    /**
     * An empty item should have the groups_id/groups_id_tech fields initialized as an empty array.
     */
    #[DataProvider('itemtypeProvider')]
    public function testGetEmpty(string $class): void
    {
        $item = new $class();
        $this->boolean($item->getEmpty())->isTrue();
        $this->array($item->fields['groups_id'])->isEmpty();
        $this->array($item->fields['groups_id_tech'])->isEmpty();
    }

    /**
     * Check that adding and updating an item with groups_id/groups_id_tech as an integer still works (minor BC, mainly for API scripts).
     */
    #[DataProvider('itemtypeProvider')]
    public function testAddUpdateWithIntGroups(string $class): void
    {
        $this->login(); // login to bypass some rights checks (e.g. on domain records)

        $input = $this->getMinimalCreationInput($class);

        $item = $this->createItem(
            $class,
            $input + [
                $class::getNameField() => __FUNCTION__,
                'groups_id'            => 1,
                'groups_id_tech'       => 2,
            ],
            ['groups_id', 'groups_id_tech'] // ignore the fields as it will be transformed to an array
        );
        $this->array($item->fields['groups_id'])->isEqualTo([1]);
        $this->array($item->fields['groups_id_tech'])->isEqualTo([2]);

        $updated = $item->update(['id' => $item->getID(), 'groups_id' => 3, 'groups_id_tech' => 4]);
        $this->boolean($updated)->isTrue();
        $this->array($item->fields['groups_id'])->isEqualTo([3]);
        $this->array($item->fields['groups_id_tech'])->isEqualTo([4]);
    }

    public function testGenericAsset(): void
    {
        $class = $this->initAssetDefinition()->getAssetClassName();

        $this->testAddAndUpdateMultipleGroups($class);
        $this->testLoadGroupsFromDb($class);
        $this->testGetEmpty($class);
        $this->testAddUpdateWithIntGroups($class);
    }
}
