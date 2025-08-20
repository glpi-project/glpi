<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2010-2022 by the FusionInventory Development Team.
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

namespace tests\units\Glpi\Inventory\Asset;

use Glpi\Inventory\Converter;
use Glpi\Inventory\Inventory;
use IPAddress;
use NetworkEquipment;
use NetworkName;
use NetworkPort;
use NetworkPort_NetworkPort;
use NetworkPort_Vlan;
use NetworkPortAggregate;
use NetworkPortType;
use Unmanaged;

class NetworkEquipmentUpdateTest extends \InventoryTestCase
{
    public function testAddNetworkEquipment()
    {
        global $DB, $GLPI_CACHE, $CFG_GLPI;

        $this->assertTrue(
            $DB->update(
                NetworkPortType::getTable(),
                [
                    'is_importable' => 1,
                    'instantiation_type' => 'NetworkPortEthernet',
                ],
                [
                    'value_decimal' => [53, 54],
                ]
            )
        );

        $GLPI_CACHE->set('glpi_inventory_ports_types', null);

        $xml_source = '<?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <TYPE>NETWORKING</TYPE>
        <MANUFACTURER>Cisco</MANUFACTURER>
        <DESCRIPTION>Cisco IOS Software, C2960 Software (C2960-LANBASEK9-M), Version 12.2(50)SE4, RELEASE SOFTWARE (fc1)
Technical Support: http://www.cisco.com/techsupport
Copyright (c) 1986-2010 by Cisco Systems, Inc.
Compiled Fri 26-Mar-10 09:14 by prod_rel_team</DESCRIPTION>
        <NAME>switchr2d2</NAME>
        <SERIAL>FOC147UJEU4</SERIAL>
        <UPTIME>157 days, 02:14:44.00</UPTIME>
        <MAC>6c:50:4d:39:59:80</MAC>
        <ID>0</ID>
        <IPS>
          <IP>192.168.30.67</IP>
          <IP>192.168.40.67</IP>
          <IP>192.168.50.67</IP>
        </IPS>
      </INFO>
      <PORTS>
        <PORT>
          <CONNECTIONS>
            <CONNECTION>
              <MAC>cc:f9:54:a1:03:35</MAC>
            </CONNECTION>
          </CONNECTIONS>
          <IFDESCR>FastEthernet0/1</IFDESCR>
          <IFNAME>Fa0/1</IFNAME>
          <IFNUMBER>10001</IFNUMBER>
          <IFSPEED>100000000</IFSPEED>
          <IFSTATUS>1</IFSTATUS>
          <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
          <IFTYPE>6</IFTYPE>
          <MAC>6c:50:4d:39:59:81</MAC>
          <VLANS>
            <VLAN>
              <NAME>printer</NAME>
              <NUMBER>281</NUMBER>
              <TAGGED>1</TAGGED>
            </VLAN>
          </VLANS>
        </PORT>
        <PORT>
          <CONNECTIONS>
            <CONNECTION>
              <MAC>cc:f9:54:a1:03:36</MAC>
            </CONNECTION>
          </CONNECTIONS>
          <IFDESCR>FastEthernet0/2</IFDESCR>
          <IFNAME>Fa0/2</IFNAME>
          <IFNUMBER>10002</IFNUMBER>
          <IFSPEED>10000000</IFSPEED>
          <IFSTATUS>1</IFSTATUS>
          <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
          <IFTYPE>6</IFTYPE>
          <MAC>6c:50:4d:39:59:82</MAC>
          <TRUNK>1</TRUNK>
          <VLANS>
            <VLAN>
              <NAME>printer</NAME>
              <NUMBER>281</NUMBER>
              <TAGGED>1</TAGGED>
            </VLAN>
            <VLAN>
              <NAME>admin</NAME>
              <NUMBER>280</NUMBER>
              <TAGGED>1</TAGGED>
            </VLAN>
          </VLANS>
        </PORT>
        <PORT>
          <IFDESCR>Port-channel10</IFDESCR>
          <IFNAME>Po10</IFNAME>
          <IFNUMBER>5005</IFNUMBER>
          <IFSPEED>4294967295</IFSPEED>
          <IFSTATUS>1</IFSTATUS>
          <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
          <IFTYPE>53</IFTYPE>
          <MAC>6c:50:4d:39:59:88</MAC>
          <TRUNK>1</TRUNK>
          <AGGREGATE>
            <PORT>10001</PORT>
            <PORT>10002</PORT>
          </AGGREGATE>
        </PORT>
        <PORT>
          <IFDESCR>vlan0</IFDESCR>
          <IFNAME>vlan0</IFNAME>
          <IFNUMBER>5006</IFNUMBER>
          <IFSPEED>4294967295</IFSPEED>
          <IFSTATUS>1</IFSTATUS>
          <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
          <IFTYPE>54</IFTYPE>
          <MAC>6c:50:4d:39:59:89</MAC>
          <TRUNK>1</TRUNK>
        </PORT>
      </PORTS>
    </DEVICE>
    <MODULEVERSION>3.0</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

        $networkEquipment = new NetworkEquipment();

        $converter = new Converter();
        $data = json_decode($converter->convert($xml_source));
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        new Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        $found = $networkEquipment->find();
        $this->assertCount(1, $networkEquipment->find(['name' => 'switchr2d2']));
    }

    public function testNetworkEquipmentGeneral()
    {
        $this->testAddNetworkEquipment();
        $networkEquipment = new NetworkEquipment();
        $this->assertTrue($networkEquipment->getFromDBByCrit(['name' => 'switchr2d2']));

        $this->assertGreaterThan(0, $networkEquipment->fields['networkequipmenttypes_id']);
        $this->assertGreaterThan(0, $networkEquipment->fields['manufacturers_id']);
        $this->assertGreaterThan(0, $networkEquipment->fields['autoupdatesystems_id']);

        unset(
            $networkEquipment->fields['id'],
            $networkEquipment->fields['date_mod'],
            $networkEquipment->fields['date_creation'],
            $networkEquipment->fields['networkequipmenttypes_id'],
            $networkEquipment->fields['manufacturers_id'],
            $networkEquipment->fields['autoupdatesystems_id'],
            $networkEquipment->fields['last_inventory_update']
        );

        $a_reference = [
            'name'                 => 'switchr2d2',
            'serial'               => 'FOC147UJEU4',
            'entities_id'          => 0,
            'is_recursive'         => 0,
            'ram'                  => null,
            'otherserial'          => null,
            'contact'              => null,
            'contact_num'          => null,
            'users_id_tech'        => 0,
            'groups_id_tech'       => [],
            'comment'              => null,
            'locations_id'         => 0,
            'networks_id'          => 0,
            'networkequipmentmodels_id' => 0,
            'is_deleted'           => 0,
            'is_template'          => 0,
            'template_name'        => null,
            'users_id'             => 0,
            'groups_id'            => [],
            'states_id'            => 0,
            'ticket_tco'           => '0.0000',
            'is_dynamic'           => 1,
            'uuid'                 => null,
            'sysdescr'             => 'Cisco IOS Software, C2960 Software (C2960-LANBASEK9-M), Version 12.2(50)SE4, RELEASE SOFTWARE (fc1)
Technical Support: http://www.cisco.com/techsupport
Copyright (c) 1986-2010 by Cisco Systems, Inc.
Compiled Fri 26-Mar-10 09:14 by prod_rel_team',
            'cpu'                   => 0,
            'uptime'                => '157 days, 02:14:44.00',
            'snmpcredentials_id'    => 0,
        ];

        $this->assertEquals($a_reference, $networkEquipment->fields);
    }

    public function testNetworkEquipmentInternalPorts()
    {
        $this->testAddNetworkEquipment();
        $networkPort = new NetworkPort();
        $networkName = new NetworkName();
        $iPAddress   = new IPAddress();

        $networkEquipment = new NetworkEquipment();
        $this->assertTrue($networkEquipment->getFromDBByCrit(['name' => 'switchr2d2']));

        $a_networkports = $networkPort->find(
            ['instantiation_type' => 'NetworkPortAggregate',
                'itemtype'           => 'NetworkEquipment',
                'items_id'           => $networkEquipment->fields['id'],
                'logical_number'     => 0,
            ]
        );

        $this->assertEquals(1, count($a_networkports), 'Number internal ports');

        $a_networkport = current($a_networkports);
        $this->assertEquals('6c:50:4d:39:59:80', $a_networkport['mac']);

        // May have 3 IP
        $a_networkname = current($networkName->find(
            ['items_id' => $a_networkport['id'],
                'itemtype' => 'NetworkPort',
            ],
            [],
            1
        ));
        $a_ips_fromDB = $iPAddress->find(
            ['itemtype' => 'NetworkName',
                'items_id' => $a_networkname['id'],
            ],
            ['name']
        );
        $a_ips = [];
        foreach ($a_ips_fromDB as $data) {
            $a_ips[] = $data['name'];
        }
        $this->assertEquals(['192.168.30.67', '192.168.40.67', '192.168.50.67'], $a_ips);
    }

    public function testUnmanagedNetworkPort()
    {
        $this->testAddNetworkEquipment();
        $networkPort = new NetworkPort();

        $a_networkports = $networkPort->find(
            ['mac'      => 'cc:f9:54:a1:03:35',
                'itemtype' => 'Unmanaged',
            ]
        );

        $this->assertEquals(1, count($a_networkports), 'Number of networkport may be 1');

        $a_networkport = current($a_networkports);
        $this->assertEquals('NetworkPortEthernet', $a_networkport['instantiation_type'], 'instantiation type may be "NetworkPortEthernet"');

        $this->assertGreaterThan(0, $a_networkport['items_id'], 'items_id may be more than 0');
    }

    public function testNetworkPortConnection()
    {
        $this->testAddNetworkEquipment();
        $networkPort = new NetworkPort();
        $networkPort_NetworkPort = new NetworkPort_NetworkPort();
        $unmanaged = new Unmanaged();

        $a_networkports = $networkPort->find(['logical_number' => 10001, 'itemtype' => NetworkEquipment::class]);

        $this->assertEquals(1, count($a_networkports), 'Number of networkport 10001 may be 1');

        $a_networkport = current($a_networkports);
        $opposites_id = $networkPort_NetworkPort->getOppositeContact($a_networkport['id']);

        $this->assertTrue($networkPort->getFromDB($opposites_id), 'Cannot load opposite');
        $unmanaged->getFromDB($networkPort->fields['items_id']);

        $this->assertEquals(0, $unmanaged->fields['hub'], 'May not be a hub');

        $a_networkports = $networkPort->find(
            ['items_id' => $unmanaged->fields['id'],
                'itemtype' => 'Unmanaged',
            ]
        );

        $this->assertEquals(1, count($a_networkports), 'Number of networkport of unknown ports may be 1');
    }

    public function testNetworkPortAggregation()
    {
        $this->testAddNetworkEquipment();
        $networkPort = new NetworkPort();
        $networkPortAggregate = new NetworkPortAggregate();

        $a_networkports = $networkPort->find(['logical_number' => 5005]);
        $this->assertCount(1, $a_networkports, 'Number of networkport 5005 may be 1');
        $a_networkport = current($a_networkports);

        $aggregates = $networkPortAggregate->find(['networkports_id' => $a_networkport['id']], [], 1);
        $this->assertCount(1, $aggregates, 'Number of aggregates may be 1');
        $a_aggregate = current($aggregates);

        $a_ports = importArrayFromDB($a_aggregate['networkports_id_list']);

        $reference = [];
        $networkPort->getFromDBByCrit(['name' => 'Fa0/1']);
        $reference[] = $networkPort->fields['id'];
        $networkPort->getFromDBByCrit(['name' => 'Fa0/2']);
        $reference[] = $networkPort->fields['id'];

        $this->assertEquals($reference, $a_ports, 'aggregate ports');
    }


    public function testVlansPort10002()
    {
        $this->testAddNetworkEquipment();
        $networkPort = new NetworkPort();
        $networkEquipment = new NetworkEquipment();
        $networkEquipment->getFromDBByCrit(['name' => 'switchr2d2']);

        $a_networkports = $networkPort->find(
            ['instantiation_type' => 'NetworkPortEthernet',
                'itemtype'           => 'NetworkEquipment',
                'items_id'           => $networkEquipment->fields['id'],
                'name'               => 'Fa0/2',
            ]
        );

        $this->assertEquals(
            1,
            count($a_networkports),
            'Networkport 10002 of switch must have only 1 port'
        );

        $a_networkport = current($a_networkports);

        $a_vlans = NetworkPort_Vlan::getVlansForNetworkPort($a_networkport['id']);
        $this->assertEquals(2, count($a_vlans), 'Networkport 10002 of switch may have 2 Vlans');
    }


    public function testNetworkPortCreated()
    {
        $this->testAddNetworkEquipment();
        $networkPort = new NetworkPort();
        $a_networkports = $networkPort->find(['itemtype' => 'NetworkEquipment']);

        $expected = 4 + 1; //4 standard ports (10001, 10002, 5005, 5006) + 1 management port
        $this->assertEquals($expected, count($a_networkports), 'Number of network ports must be ' . $expected);
    }
}
