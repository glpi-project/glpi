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

/* Test for inc/networkequipment.class.php */

class NetworkEquipment extends DbTestCase
{
    public function testNetEquipmentCRUD()
    {
        $this->login();

       //create network equipment
        $device = new \NetworkEquipment();
        $input = [
            'name'         => 'Test equipment',
            'entities_id'  => 0
        ];
        $netequipments_id = $device->add($input);
        $this->integer($netequipments_id)->isGreaterThan(0);

        $this->boolean($device->getFromDB($netequipments_id))->isTrue();
        $this->string($device->fields['name'])->isIdenticalTo('Test equipment');

       //create ports attached
        $netport = new \NetworkPort();
        $input = [
            'itemtype'           => $device->getType(),
            'items_id'           => $device->getID(),
            'entities_id'        => 0,
            'logical_number'     => 1256,
            'name'               => 'Test port',
            'instantiation_type' => 'NetworkPortEthernet'
        ];
        $netports_id = $netport->add($input);
        $this->integer($netports_id)->isGreaterThan(0);

        $this->boolean($netport->getFromDB($netports_id))->isTrue();
        $this->string($netport->fields['name'])->isIdenticalTo('Test port');

        $input = [
            'itemtype'           => $device->getType(),
            'items_id'           => $device->getID(),
            'entities_id'        => 0,
            'logical_number'     => 1257,
            'name'               => 'Another test port',
            'instantiation_type' => 'NetworkPortAggregate'
        ];
        $netports_id = $netport->add($input);
        $this->integer($netports_id)->isGreaterThan(0);

        $this->boolean($netport->getFromDB($netports_id))->isTrue();
        $this->string($netport->fields['name'])->isIdenticalTo('Another test port');

        $this->integer($netport->countForItem($device))->isIdenticalTo(2);

       //remove network equipment
        $this->boolean($device->delete(['id' => $netequipments_id], true))->isTrue();

       //see if links are dropped
        $this->integer($netport->countForItem($device))->isIdenticalTo(0);
    }

    /**
     * Test adding an asset with the groups_id and groups_id_tech fields as an array and null.
     * Test updating an asset with the groups_id and groups_id_tech fields as an array and null.
     * @return void
     */
    public function testAddAndUpdateMultipleGroups()
    {
        $networkequipment = $this->createItem(\NetworkEquipment::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
            'groups_id' => [1, 2],
            'groups_id_tech' => [3, 4],
        ]);
        $networkequipments_id_1 = $networkequipment->fields['id'];
        $this->array($networkequipment->fields['groups_id'])->containsValues([1, 2]);
        $this->array($networkequipment->fields['groups_id_tech'])->containsValues([3, 4]);

        $networkequipment = $this->createItem(\NetworkEquipment::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
            'groups_id' => null,
            'groups_id_tech' => null,
        ]);
        $networkequipments_id_2 = $networkequipment->fields['id'];
        $this->array($networkequipment->fields['groups_id'])->isEmpty();
        $this->array($networkequipment->fields['groups_id_tech'])->isEmpty();

        // Update both assets. Asset 1 will have the groups set to null and asset 2 will have the groups set to an array.
        $networkequipment->getFromDB($networkequipments_id_1);
        $this->boolean($networkequipment->update([
            'id' => $networkequipments_id_1,
            'groups_id' => null,
            'groups_id_tech' => null,
        ]))->isTrue();
        $this->array($networkequipment->fields['groups_id'])->isEmpty();
        $this->array($networkequipment->fields['groups_id_tech'])->isEmpty();

        $networkequipment->getFromDB($networkequipments_id_2);
        $this->boolean($networkequipment->update([
            'id' => $networkequipments_id_2,
            'groups_id' => [5, 6],
            'groups_id_tech' => [7, 8],
        ]))->isTrue();
        $this->array($networkequipment->fields['groups_id'])->containsValues([5, 6]);
        $this->array($networkequipment->fields['groups_id_tech'])->containsValues([7, 8]);

        // Test updating array to array
        $this->boolean($networkequipment->update([
            'id' => $networkequipments_id_2,
            'groups_id' => [1, 2],
            'groups_id_tech' => [3, 4],
        ]))->isTrue();
        $this->array($networkequipment->fields['groups_id'])->containsValues([1, 2]);
        $this->array($networkequipment->fields['groups_id_tech'])->containsValues([3, 4]);
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
        $networkequipment = $this->createItem(\NetworkEquipment::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $networkequipments_id = $networkequipment->fields['id'];

        // Manually set the groups_id and groups_id_tech fields to an integer value
        // The update migration should mvoe all the groups to the new table directly for performance reasons (no changes to array, etc)
        $DB->delete('glpi_groups_assets', [
            'itemtype' => 'NetworkEquipment',
            'items_id' => $networkequipments_id,
        ]);
        $DB->insert(
            'glpi_groups_assets',
            [
                'itemtype' => 'NetworkEquipment',
                'items_id' => $networkequipments_id,
                'groups_id' => 1,
                'type' => 0 // Normal
            ],
        );
        $DB->insert(
            'glpi_groups_assets',
            [
                'itemtype' => 'NetworkEquipment',
                'items_id' => $networkequipments_id,
                'groups_id' => 2,
                'type' => 1 // Tech
            ],
        );
        $networkequipment->getFromDB($networkequipments_id);
        $this->array($networkequipment->fields['groups_id'])->isEmpty();
        $this->array($networkequipment->fields['groups_id_tech'])->isEmpty();
    }

    /**
     * An empty asset object should have the groups_id and groups_id_tech fields initialized as an empty array.
     * @return void
     */
    public function testGetEmptyMultipleGroups()
    {
        $networkequipment = new \NetworkEquipment();
        $networkequipment->getEmpty();
        $this->array($networkequipment->fields['groups_id'])->isEmpty();
        $this->array($networkequipment->fields['groups_id_tech'])->isEmpty();
    }
}
