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

class NetworkEquipmentTest extends DbTestCase
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
        $this->assertGreaterThan(0, $netequipments_id);

        $this->assertTrue($device->getFromDB($netequipments_id));
        $this->assertSame('Test equipment', $device->fields['name']);

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
        $this->assertGreaterThan(0, $netports_id);

        $this->assertTrue($netport->getFromDB($netports_id));
        $this->assertSame('Test port', $netport->fields['name']);

        $input = [
            'itemtype'           => $device->getType(),
            'items_id'           => $device->getID(),
            'entities_id'        => 0,
            'logical_number'     => 1257,
            'name'               => 'Another test port',
            'instantiation_type' => 'NetworkPortAggregate'
        ];
        $netports_id = $netport->add($input);
        $this->assertGreaterThan(0, $netports_id);

        $this->assertTrue($netport->getFromDB($netports_id));
        $this->assertSame('Another test port', $netport->fields['name']);

        $this->assertSame(2, $netport->countForItem($device));

       //remove network equipment
        $this->assertTrue($device->delete(['id' => $netequipments_id], true));

       //see if links are dropped
        $this->assertSame(0, $netport->countForItem($device));
    }
}
