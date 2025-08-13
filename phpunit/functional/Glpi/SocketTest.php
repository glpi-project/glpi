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

namespace tests\units\Glpi;

use DbTestCase;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasSocketCapacity;
use Glpi\Features\Clonable;
use Glpi\Socket;
use NetworkPort;
use NetworkPortEthernet;
use Toolbox;

class SocketTest extends DbTestCase
{
    public function testRelatedItemHasTab()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasSocketCapacity::class)]);

        $this->login(); // tab will be available only if corresponding right is available in the current session

        foreach ($CFG_GLPI['socket_types'] as $itemtype) {
            $item = $this->createItem(
                $itemtype,
                $this->getMinimalCreationInput($itemtype)
            );

            $tabs = $item->defineAllTabs();
            $this->assertArrayHasKey('Glpi\\Socket$1', $tabs, $itemtype);
        }
    }

    public function testRelatedItemCloneRelations()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasSocketCapacity::class)]);

        foreach ($CFG_GLPI['socket_types'] as $itemtype) {
            if (!Toolbox::hasTrait($itemtype, Clonable::class)) {
                continue;
            }

            $item = \getItemForItemtype($itemtype);
            $this->assertContains(Socket::class, $item->getCloneRelations(), $itemtype);
        }
    }

    public function testCreateNoItem()
    {
        $socket = new Socket();
        $sockets_id = $socket->add([
            'name' => __FUNCTION__,
            'items_id' => '',
            'itemtype' => 'Computer',
        ]);
        $this->assertTrue($socket->getFromDB($sockets_id));
        $this->assertSame(null, $socket->fields['itemtype']);
        $this->assertSame(0, $socket->fields['items_id']);
    }


    public function testSocketDisconnect()
    {
        // Create socket
        $socket = new Socket();
        $socketId = $socket->add([
            'name'      => __FUNCTION__,
            'items_id'  => '',
            'itemtype'  => '',
        ]);

        $this->assertTrue($socket->getFromDB($socketId));
        $this->assertNull($socket->fields['itemtype']);
        $this->assertSame(0, $socket->fields['items_id']);

        // Retrieve test computer
        $computer = getItemByTypeName('Computer', '_test_pc01');
        $this->assertNotFalse($computer);

        // Create NetworkPortEthernet
        $networkPort = new NetworkPort();
        $networkPortId = $networkPort->add([
            'items_id'                    => $computer->getID(),
            'itemtype'                    => 'Computer',
            'entities_id'                 => $computer->fields['entities_id'],
            'is_recursive'                => 0,
            'logical_number'              => 3,
            'mac'                         => '00:24:81:eb:c6:d2',
            'instantiation_type'          => 'NetworkPortEthernet',
            'name'                        => 'eth1',
            'comment'                     => 'Comment me!',
            'items_devicenetworkcards_id' => 0,
            'type'                        => 'T',
            'speed'                       => 1000,
            'speed_other_value'           => '',
            'NetworkName_name'            => 'test1',
            'NetworkName_comment'         => 'test1 comment',
            'NetworkName_fqdns_id'        => 0,
            'NetworkName__ipaddresses'    => ['-1' => '192.168.20.1'],
            '_create_children'            => true,
        ]);

        // Verify NetworkPort creation
        $this->assertTrue($networkPort->getFromDBByCrit([
            'itemtype'           => 'Computer',
            'items_id'           => $computer->getID(),
            'name'               => 'eth1',
            'instantiation_type' => 'NetworkPortEthernet',
        ]));

        // Get NetworkPortEthernet instantiation
        $ethernetPort = new NetworkPortEthernet();
        $this->assertTrue($ethernetPort->getFromDBByCrit([
            'networkports_id' => $networkPort->getID(),
        ]));

        // Connect socket to ethernet port
        $this->assertTrue($ethernetPort->update([
            'id'               => $ethernetPort->getID(),
            'sockets_id'       => $socketId,
            'networkports_id'  => $networkPortId,
        ]));

        // Check that socket is correctly linked
        $this->assertTrue($socket->getFromDB($socketId));
        $this->assertSame('Computer', $socket->fields['itemtype']);
        $this->assertSame($computer->getID(), $socket->fields['items_id']);
        $this->assertSame($networkPortId, $socket->fields['networkports_id']);

        // Disconnect socket
        $this->assertTrue($ethernetPort->update([
            'id'               => $ethernetPort->getID(),
            'sockets_id'       => 0,
            'networkports_id'  => $networkPortId,
        ]));

        // Verify socket is disconnected from network port but still linked to main item
        $this->assertTrue($socket->getFromDB($socketId));
        $this->assertSame('Computer', $socket->fields['itemtype']);
        $this->assertSame($computer->getID(), $socket->fields['items_id']);
        $this->assertSame(0, $socket->fields['networkports_id']);
    }


}
