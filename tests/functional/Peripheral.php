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

class Peripheral extends DbTestCase
{
    /**
     * Test adding an asset with the groups_id and groups_id_tech fields as an array and null.
     * Test updating an asset with the groups_id and groups_id_tech fields as an array and null.
     * @return void
     */
    public function testAddAndUpdateMultipleGroups()
    {
        $peripheral = $this->createItem(\Peripheral::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
            'groups_id' => [1, 2],
            'groups_id_tech' => [3, 4],
        ], ['groups_id', 'groups_id_tech']);
        $peripherals_id_1 = $peripheral->fields['id'];
        $this->array($peripheral->fields['groups_id'])->containsValues([1, 2]);
        $this->array($peripheral->fields['groups_id_tech'])->containsValues([3, 4]);

        $peripheral = $this->createItem(\Peripheral::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
            'groups_id' => null,
            'groups_id_tech' => null,
        ], ['groups_id', 'groups_id_tech']);
        $peripherals_id_2 = $peripheral->fields['id'];
        $this->array($peripheral->fields['groups_id'])->isEmpty();
        $this->array($peripheral->fields['groups_id_tech'])->isEmpty();

        // Update both assets. Asset 1 will have the groups set to null and asset 2 will have the groups set to an array.
        $peripheral->getFromDB($peripherals_id_1);
        $this->boolean($peripheral->update([
            'id' => $peripherals_id_1,
            'groups_id' => null,
            'groups_id_tech' => null,
        ]))->isTrue();
        $this->array($peripheral->fields['groups_id'])->isEmpty();
        $this->array($peripheral->fields['groups_id_tech'])->isEmpty();

        $peripheral->getFromDB($peripherals_id_2);
        $this->boolean($peripheral->update([
            'id' => $peripherals_id_2,
            'groups_id' => [5, 6],
            'groups_id_tech' => [7, 8],
        ]))->isTrue();
        $this->array($peripheral->fields['groups_id'])->containsValues([5, 6]);
        $this->array($peripheral->fields['groups_id_tech'])->containsValues([7, 8]);

        // Test updating array to array
        $this->boolean($peripheral->update([
            'id' => $peripherals_id_2,
            'groups_id' => [1, 2],
            'groups_id_tech' => [3, 4],
        ]))->isTrue();
        $this->array($peripheral->fields['groups_id'])->containsValues([1, 2]);
        $this->array($peripheral->fields['groups_id_tech'])->containsValues([3, 4]);
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
        $peripheral = $this->createItem(\Peripheral::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $peripherals_id = $peripheral->fields['id'];

        // Manually set the groups_id and groups_id_tech fields to an integer value
        // The update migration should mvoe all the groups to the new table directly for performance reasons (no changes to array, etc)
        $DB->delete('glpi_groups_assets', [
            'itemtype' => 'Peripheral',
            'items_id' => $peripherals_id,
        ]);
        $DB->insert(
            'glpi_groups_assets',
            [
                'itemtype' => 'Peripheral',
                'items_id' => $peripherals_id,
                'groups_id' => 1,
                'type' => 0 // Normal
            ],
        );
        $DB->insert(
            'glpi_groups_assets',
            [
                'itemtype' => 'Peripheral',
                'items_id' => $peripherals_id,
                'groups_id' => 2,
                'type' => 1 // Tech
            ],
        );
        $peripheral->getFromDB($peripherals_id);
        $this->array($peripheral->fields['groups_id'])
            ->hasSize(1)
            ->containsValues([1]);
        $this->array($peripheral->fields['groups_id_tech'])
            ->hasSize(1)
            ->containsValues([2]);
    }

    /**
     * An empty asset object should have the groups_id and groups_id_tech fields initialized as an empty array.
     * @return void
     */
    public function testGetEmptyMultipleGroups()
    {
        $peripheral = new \Peripheral();
        $peripheral->getEmpty();
        $this->array($peripheral->fields['groups_id'])->isEmpty();
        $this->array($peripheral->fields['groups_id_tech'])->isEmpty();
    }
}
