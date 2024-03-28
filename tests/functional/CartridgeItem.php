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

namespace tests\units;

use DbTestCase;

class CartridgeItem extends DbTestCase
{
    /**
     * Test adding an asset with the groups_id and groups_id_tech fields as an array and null.
     * Test updating an asset with the groups_id and groups_id_tech fields as an array and null.
     * @return void
     */
    public function testAddAndUpdateMultipleGroups()
    {
        $cartridgeitem = $this->createItem(\CartridgeItem::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
            'groups_id_tech' => [3, 4],
        ], ['groups_id_tech']);
        $cartridgeitems_id_1 = $cartridgeitem->fields['id'];
        $this->array($cartridgeitem->fields['groups_id_tech'])->containsValues([3, 4]);

        $cartridgeitem = $this->createItem(\CartridgeItem::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
            'groups_id_tech' => null,
        ], ['groups_id_tech']);
        $cartridgeitems_id_2 = $cartridgeitem->fields['id'];
        $this->array($cartridgeitem->fields['groups_id_tech'])->isEmpty();

        // Update both assets. Asset 1 will have the groups set to null and asset 2 will have the groups set to an array.
        $cartridgeitem->getFromDB($cartridgeitems_id_1);
        $this->boolean($cartridgeitem->update([
            'id' => $cartridgeitems_id_1,
            'groups_id_tech' => null,
        ]))->isTrue();
        $this->array($cartridgeitem->fields['groups_id_tech'])->isEmpty();

        $cartridgeitem->getFromDB($cartridgeitems_id_2);
        $this->boolean($cartridgeitem->update([
            'id' => $cartridgeitems_id_2,
            'groups_id_tech' => [7, 8],
        ]))->isTrue();
        $this->array($cartridgeitem->fields['groups_id_tech'])->containsValues([7, 8]);

        // Test updating array to array
        $this->boolean($cartridgeitem->update([
            'id' => $cartridgeitems_id_2,
            'groups_id_tech' => [3, 4],
        ]))->isTrue();
        $this->array($cartridgeitem->fields['groups_id_tech'])->containsValues([3, 4]);
    }

    /**
     * Test the loading asset which still have integer values for groups_id and groups_id_tech (0 for no group).
     * The value should be automatically normalized to an array. If the group was '0', the array should be empty.
     * @return void
     */
    public function testLoadOldItemsSingleGroup()
    {
        /** @var \DBmysql $DB */
        global $DB;
        $cartridgeitem = $this->createItem(\CartridgeItem::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $cartridgeitems_id = $cartridgeitem->fields['id'];

        // Manually set the groups_id_tech field to an integer value.
        // The update migration should move all the groups to the new table directly for performance reasons (no changes to array, etc)
        $DB->delete('glpi_groups_assets', [
            'itemtype' => 'CartridgeItem',
            'items_id' => $cartridgeitems_id,
        ]);
        $DB->insert(
            'glpi_groups_assets',
            [
                'itemtype' => 'CartridgeItem',
                'items_id' => $cartridgeitems_id,
                'groups_id' => 2,
                'type' => 1 // Tech
            ],
        );
        $cartridgeitem->getFromDB($cartridgeitems_id);
        $this->array($cartridgeitem->fields['groups_id_tech'])
            ->hasSize(1)
            ->containsValues([2]);
    }

    /**
     * An empty asset object should have the groups_id and groups_id_tech fields initialized as an empty array.
     * @return void
     */
    public function testGetEmptyMultipleGroups()
    {
        $cartridgeitem = new \CartridgeItem();
        $cartridgeitem->getEmpty();
        $this->array($cartridgeitem->fields['groups_id_tech'])->isEmpty();
    }
}
