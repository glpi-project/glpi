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

namespace tests\units;

use DbTestCase;

/* Test for inc/networkport_networkport.class.php */

class NetworkPort_NetworkPortTest extends DbTestCase
{
    private function createPort(\CommonDBTM $asset, string $mac): int
    {
        $port = new \NetworkPort();
        $nb_log = (int) countElementsInTable('glpi_logs');
        $ports_id = $port->add([
            'items_id'           => $asset->getID(),
            'itemtype'           => 'Computer',
            'entities_id'        => $asset->fields['entities_id'],
            'is_recursive'       => 0,
            'logical_number'     => 1,
            'mac'                => $mac,
            'instantiation_type' => 'NetworkPortEthernet',
            'name'               => 'eth1',
        ]);
        $this->assertGreaterThan(0, (int) $ports_id);
        $this->assertGreaterThan($nb_log, (int) countElementsInTable('glpi_logs'));

        return $ports_id;
    }

    public function testWire()
    {
        $this->login();

        $computer1 = getItemByTypeName('Computer', '_test_pc01');
        $computer2 = getItemByTypeName('Computer', '_test_pc02');

        $id_1 = $this->createPort($computer1, '00:24:81:eb:c6:d0');
        $id_2 = $this->createPort($computer2, '00:24:81:eb:c6:d1');

        $wired = new \NetworkPort_NetworkPort();
        $this->assertGreaterThan(
            0,
            $wired->add([
                'networkports_id_1' => $id_1,
                'networkports_id_2' => $id_2,
            ])
        );

        $this->assertTrue($wired->getFromDBForNetworkPort($id_1));
        $this->assertTrue($wired->getFromDBForNetworkPort($id_2));

        $this->assertSame($id_2, $wired->getOppositeContact($id_1));
        $this->assertSame($id_1, $wired->getOppositeContact($id_2));
    }

    public function testHub()
    {
        $this->login();
        $computer = getItemByTypeName('Computer', '_test_pc01');

        $ports_id = $this->createPort($computer, '00:24:81:eb:c6:d2');

        $wired = new \NetworkPort_NetworkPort();
        $hubs_id = $wired->createHub($ports_id);
        $this->assertGreaterThan(0, $hubs_id);

        $unmanaged = new \Unmanaged();
        $this->assertTrue($unmanaged->getFromDB($hubs_id));

        $this->assertSame(1, $unmanaged->fields['hub']);
        $this->assertSame('Hub', $unmanaged->fields['name']);
        $this->assertSame(0, $unmanaged->fields['entities_id']);
        $this->assertSame('Port: ' . $ports_id, $unmanaged->fields['comment']);

        $opposites_id = $wired->getOppositeContact($ports_id);
        $this->assertGreaterThan(0, $opposites_id);
        $netport = new \NetworkPort();
        $this->assertTrue($netport->getFromDB($opposites_id));

        $this->assertSame($hubs_id, $netport->fields['items_id']);
        $this->assertSame($unmanaged->getType(), $netport->fields['itemtype']);
        $this->assertSame('Hub link', $netport->fields['name']);
        $this->assertSame(\NetworkPortEthernet::getType(), $netport->fields['instantiation_type']);
    }
}
