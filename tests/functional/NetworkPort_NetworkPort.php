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

/* Test for inc/networkport_networkport.class.php */

class NetworkPort_NetworkPort extends DbTestCase
{
    private function createPort(\CommonDBTM $asset, string $mac): int
    {
        $port = new \NetworkPort();
        $nb_log = (int)countElementsInTable('glpi_logs');
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
        $this->integer((int)$ports_id)->isGreaterThan(0);
        $this->integer((int)countElementsInTable('glpi_logs'))->isGreaterThan($nb_log);

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
        $this->integer(
            $wired->add([
                'networkports_id_1' => $id_1,
                'networkports_id_2' => $id_2
            ])
        )->isGreaterThan(0);

        $this->boolean($wired->getFromDBForNetworkPort($id_1))->isTrue();
        $this->boolean($wired->getFromDBForNetworkPort($id_2))->isTrue();

        $this->integer($wired->getOppositeContact($id_1))->isIdenticalTo($id_2);
        $this->integer($wired->getOppositeContact($id_2))->isIdenticalTo($id_1);
    }

    public function testHub()
    {
        $this->login();
        $computer = getItemByTypeName('Computer', '_test_pc01');

        $ports_id = $this->createPort($computer, '00:24:81:eb:c6:d2');

        $wired = new \NetworkPort_NetworkPort();
        $hubs_id = $wired->createHub($ports_id);
        $this->integer($hubs_id)->isGreaterThan(0);

        $unmanaged = new \Unmanaged();
        $this->boolean($unmanaged->getFromDB($hubs_id))->isTrue();

        $this->integer($unmanaged->fields['hub'])->isIdenticalTo(1);
        $this->string($unmanaged->fields['name'])->isIdenticalTo('Hub');
        $this->integer($unmanaged->fields['entities_id'])->isIdenticalTo(0);
        $this->string($unmanaged->fields['comment'])->isIdenticalTo('Port: ' . $ports_id);

        $opposites_id = $wired->getOppositeContact($ports_id);
        $this->integer($opposites_id)->isGreaterThan(0);
        $netport = new \NetworkPort();
        $this->boolean($netport->getFromDB($opposites_id))->isTrue();

        $this->integer($netport->fields['items_id'])->isIdenticalTo($hubs_id);
        $this->string($netport->fields['itemtype'])->isIdenticalTo($unmanaged->getType());
        $this->string($netport->fields['name'])->isIdenticalTo('Hub link');
        $this->string($netport->fields['instantiation_type'])->isIdenticalTo(\NetworkPortEthernet::getType());
    }
}
