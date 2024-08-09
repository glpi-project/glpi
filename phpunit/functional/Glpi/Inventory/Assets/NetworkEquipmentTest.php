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

namespace tests\units\Glpi\Inventory\Asset;

include_once __DIR__ . '/../../../../abstracts/AbstractInventoryAsset.php';

/* Test for inc/inventory/asset/networkequipment.class.php */

class NetworkEquipmentTest extends AbstractInventoryAsset
{
    const INV_FIXTURES = GLPI_ROOT . '/vendor/glpi-project/inventory_format/examples/';

    public static function assetProvider(): array
    {
        return [
            [
                'xml'   => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <FIRMWARES>
        <DESCRIPTION>device firmware</DESCRIPTION>
        <MANUFACTURER>Cisco</MANUFACTURER>
        <NAME>UCS 6248UP 48-Port</NAME>
        <TYPE>device</TYPE>
        <VERSION>5.0(3)N2(4.02b)</VERSION>
      </FIRMWARES>
      <INFO>
        <COMMENTS>Cisco NX-OS(tm) ucs, Software (ucs-6100-k9-system), Version 5.0(3)N2(4.02b), RELEASE SOFTWARE Copyright (c) 2002-2013 by Cisco Systems, Inc.   Compiled 1/16/2019 18:00:00</COMMENTS>
        <CONTACT>noc@glpi-project.org</CONTACT>
        <CPU>4</CPU>
        <FIRMWARE>5.0(3)N2(4.02b)</FIRMWARE>
        <ID>0</ID>
        <LOCATION>paris.pa3</LOCATION>
        <MAC>8c:60:4f:8d:ae:fc</MAC>
        <MANUFACTURER>Cisco</MANUFACTURER>
        <MODEL>UCS 6248UP 48-Port</MODEL>
        <NAME>ucs6248up-cluster-pa3-B</NAME>
        <SERIAL>SSI1912014B</SERIAL>
        <TYPE>NETWORKING</TYPE>
        <UPTIME>482 days, 05:42:18.50</UPTIME>
        <IPS>
           <IP>127.0.0.1</IP>
           <IP>10.2.5.10</IP>
           <IP>192.168.12.5</IP>
        </IPS>
      </INFO>
    </DEVICE>
    <MODULEVERSION>4.1</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>",
                'asset'  => '{"description":"Cisco NX-OS(tm) ucs, Software (ucs-6100-k9-system), Version 5.0(3)N2(4.02b), RELEASE SOFTWARE Copyright (c) 2002-2013 by Cisco Systems, Inc.   Compiled 1/16/2019 18:00:00", "sysdescr":"Cisco NX-OS(tm) ucs, Software (ucs-6100-k9-system), Version 5.0(3)N2(4.02b), RELEASE SOFTWARE Copyright (c) 2002-2013 by Cisco Systems, Inc.   Compiled 1/16/2019 18:00:00", "autoupdatesystems_id":"GLPI Native Inventory","last_inventory_update": "DATE_NOW","contact":"noc@glpi-project.org","cpu":4,"firmware":"5.0(3)N2(4.02b)","location":"paris.pa3","mac":"8c:60:4f:8d:ae:fc","manufacturer":"Cisco","model":"UCS 6248UP 48-Port","name":"ucs6248up-cluster-pa3-B","serial":"SSI1912014B","type":"Networking","uptime":"482 days, 05:42:18.50","ips":["127.0.0.1","10.2.5.10","192.168.12.5"],"locations_id":"paris.pa3","networkequipmentmodels_id":"UCS 6248UP 48-Port","networkequipmenttypes_id":"Networking","manufacturers_id":"Cisco","is_deleted": 0}'
            ], [
                'xml'   => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <FIRMWARES>
        <DESCRIPTION>device firmware</DESCRIPTION>
        <MANUFACTURER>Cisco</MANUFACTURER>
        <NAME>Catalyst 3750-24/48</NAME>
        <TYPE>device</TYPE>
        <VERSION>12.2(55)SE6</VERSION>
      </FIRMWARES>
      <INFO>
        <COMMENTS>Cisco IOS Software, C3750 Software (C3750-IPSERVICESK9-M), Version 12.2(55)SE6, RELEASE SOFTWARE (fc1)
Technical Support: http://www.cisco.com/techsupport
Copyright (c) 1986-2012 by Cisco Systems, Inc.
Compiled Mon 23-Jul-12 13:22 by prod_rel_team</COMMENTS>
        <CPU>47</CPU>
        <FIRMWARE>12.2(55)SE6</FIRMWARE>
        <ID>0</ID>
        <IPS>
          <IP>10.1.0.100</IP>
          <IP>10.1.0.22</IP>
          <IP>10.1.0.41</IP>
          <IP>10.1.0.45</IP>
          <IP>10.1.0.59</IP>
          <IP>10.11.11.1</IP>
          <IP>10.11.11.5</IP>
          <IP>10.11.13.1</IP>
          <IP>10.11.13.5</IP>
          <IP>172.21.0.1</IP>
          <IP>172.21.0.7</IP>
          <IP>172.22.0.1</IP>
          <IP>172.22.0.5</IP>
          <IP>172.23.0.1</IP>
          <IP>172.23.0.5</IP>
          <IP>172.24.0.1</IP>
          <IP>172.24.0.5</IP>
          <IP>172.25.1.15</IP>
          <IP>172.28.200.1</IP>
          <IP>172.28.200.5</IP>
          <IP>172.28.211.5</IP>
          <IP>172.28.215.1</IP>
          <IP>172.28.221.1</IP>
          <IP>185.10.253.65</IP>
          <IP>185.10.253.97</IP>
          <IP>185.10.254.1</IP>
          <IP>185.10.255.146</IP>
          <IP>185.10.255.224</IP>
          <IP>185.10.255.250</IP>
        </IPS>
        <LOCATION>paris.pa3</LOCATION>
        <MAC>00:23:ac:6a:01:00</MAC>
        <MANUFACTURER>Cisco</MANUFACTURER>
        <MODEL>Catalyst 3750-24/48</MODEL>
        <NAME>3k-1-pa3.teclib.infra</NAME>
        <RAM>128</RAM>
        <SERIAL>FOC1243W0ED</SERIAL>
        <TYPE>NETWORKING</TYPE>
        <UPTIME>103 days, 13:53:28.28</UPTIME>
      </INFO>
    </DEVICE>
    <MODULEVERSION>4.1</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>",
                'asset'  => '{"autoupdatesystems_id":"GLPI Native Inventory","last_inventory_update":"DATE_NOW","cpu":47,"firmware":"12.2(55)SE6","ips":["10.1.0.100","10.1.0.22","10.1.0.41","10.1.0.45","10.1.0.59","10.11.11.1","10.11.11.5","10.11.13.1","10.11.13.5","172.21.0.1","172.21.0.7","172.22.0.1","172.22.0.5","172.23.0.1","172.23.0.5","172.24.0.1","172.24.0.5","172.25.1.15","172.28.200.1","172.28.200.5","172.28.211.5","172.28.215.1","172.28.221.1","185.10.253.65","185.10.253.97","185.10.254.1","185.10.255.146","185.10.255.224","185.10.255.250"],"location":"paris.pa3","mac":"00:23:ac:6a:01:00","manufacturer":"Cisco","model":"Catalyst 3750-24\/48","name":"3k-1-pa3.teclib.infra","ram":128,"serial":"FOC1243W0ED","type":"Networking","uptime":"103 days, 13:53:28.28","description":"Cisco IOS Software, C3750 Software (C3750-IPSERVICESK9-M), Version 12.2(55)SE6, RELEASE SOFTWARE (fc1)\nTechnical Support: http:\/\/www.cisco.com\/techsupport\nCopyright (c) 1986-2012 by Cisco Systems, Inc.\nCompiled Mon 23-Jul-12 13:22 by prod_rel_team","sysdescr":"Cisco IOS Software, C3750 Software (C3750-IPSERVICESK9-M), Version 12.2(55)SE6, RELEASE SOFTWARE (fc1)\nTechnical Support: http:\/\/www.cisco.com\/techsupport\nCopyright (c) 1986-2012 by Cisco Systems, Inc.\nCompiled Mon 23-Jul-12 13:22 by prod_rel_team","locations_id":"paris.pa3","networkequipmentmodels_id":"Catalyst 3750-24\/48","networkequipmenttypes_id":"Networking","manufacturers_id":"Cisco","is_deleted": 0}'
            ]
        ];
    }

    /**
     * @dataProvider assetProvider
     */
    public function testPrepare($xml, $asset)
    {
        $date_now = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date_now;
        $asset = str_replace('DATE_NOW', $date_now, $asset);
        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $netequ = new \NetworkEquipment();
        $main = new \Glpi\Inventory\Asset\NetworkEquipment($netequ, $json);
        $main->setExtraData((array)$json->content);
        $result = $main->prepare();
        $this->assertEquals(json_decode($asset), $result[0]);
    }

    public function testStacked()
    {
       //non stacked
        $json_str = file_get_contents(self::INV_FIXTURES . 'networkequipment_1.json');
        $json = json_decode($json_str);

        $netequ = new \NetworkEquipment();

        $data = (array)$json->content;
        $inventory = new \Glpi\Inventory\Inventory();
        $this->assertTrue($inventory->setData($json));

        $agent = new \Agent();
        $this->assertSame(0, $agent->handleAgent($inventory->extractMetadata()));

        $main = new \Glpi\Inventory\Asset\NetworkEquipment($netequ, $json);
        $main->setAgent($agent)->setExtraData($data);
        $result = $main->prepare();
        $this->assertCount(1, $result);

        $this->assertFalse($main->isStackedSwitch());

        //stacked
        $json_str = file_get_contents(self::INV_FIXTURES . 'networkequipment_2.json');
        $json = json_decode($json_str);

        $netequ = new \NetworkEquipment();

        $data = (array)$json->content;
        $inventory = new \Glpi\Inventory\Inventory();
        $this->assertTrue($inventory->setData($json));

        $agent = new \Agent();
        $this->assertSame(0, $agent->handleAgent($inventory->extractMetadata()));

        $main = new \Glpi\Inventory\Asset\NetworkEquipment($netequ, $json);
        $main->setAgent($agent)->setExtraData($data);
        $result = $main->prepare();
        $this->assertCount(5, $result);

        $expected_stack = [
            1001 => [
                'contained_index' => 1,
                'description' => 'WS-C3750G-48TS',
                'fru' => 1,
                'index' => 1001,
                'model' => 'WS-C3750G-48TS-S',
                'name' => '1',
                'serial' => 'FOC1243W0ED',
                'type' => 'chassis',
                'firmware' => '12.2(55)SE6',
                'stack_number' => 1,
            ],
            2001 => [
                'contained_index' => 1,
                'description' => 'WS-C3750G-48TS',
                'fru' => 1,
                'index' => 2001,
                'model' => 'WS-C3750G-48TS-S',
                'name' => '2',
                'serial' => 'FOC1127Z4LH',
                'type' => 'chassis',
                'firmware' => '12.2(55)SE6',
                'stack_number' => 2,
            ],
            3001 => [
                'contained_index' => 1,
                'description' => 'WS-C3750G-48TS',
                'fru' => 1,
                'index' => 3001,
                'model' => 'WS-C3750G-48TS-S',
                'name' => '3',
                'serial' => 'FOC1232W0JH',
                'type' => 'chassis',
                'firmware' => '12.2(55)SE6',
                'stack_number' => 3,
            ],
            4001 => [
                'contained_index' => 1,
                'description' => 'WS-C3750G-48TS',
                'fru' => 1,
                'index' => 4001,
                'model' => 'WS-C3750G-48TS-S',
                'name' => '4',
                'serial' => 'FOC1033Y0M7',
                'type' => 'chassis',
                'firmware' => '12.2(55)SE6',
                'stack_number' => 4,
            ],
            8001 => [
                'contained_index' => 1,
                'description' => 'WS-C3750G-48TS',
                'fru' => 1,
                'index' => 8001,
                'model' => 'WS-C3750G-48TS-S',
                'name' => '8',
                'serial' => 'FOC0929U1SR',
                'type' => 'chassis',
                'firmware' => '12.2(55)SE6',
                'stack_number' => 8,
            ]
        ];

        $this->assertTrue($main->isStackedSwitch());
        $stack = $main->getStackedSwitches();
        $this->assertSame(array_keys($stack), array_keys($expected_stack));
        foreach ($expected_stack as $key => $entry) {
            $this->assertSame((array)$stack[$key], $entry);
        }
    }

    /**
     * Test stacked Dell S50 switch
     *
     * Note: all ports have the same MAC
     */
    public function testStackedDellSwitch()
    {
        $xml_source = '<?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <COMPONENTS>
        <COMPONENT>
          <CONTAINEDININDEX>0</CONTAINEDININDEX>
          <INDEX>-1</INDEX>
          <NAME>Force10 S-series Stack</NAME>
          <TYPE>stack</TYPE>
        </COMPONENT>
        <COMPONENT>
          <CONTAINEDININDEX>-1</CONTAINEDININDEX>
          <DESCRIPTION>48-port E/FE/GE (SB)</DESCRIPTION>
          <FIRMWARE>8.4.2.7</FIRMWARE>
          <INDEX>1</INDEX>
          <MODEL>S50-01-GE-48T-AC</MODEL>
          <NAME>0</NAME>
          <SERIAL>DL253300100</SERIAL>
          <TYPE>chassis</TYPE>
        </COMPONENT>
        <COMPONENT>
          <CONTAINEDININDEX>-1</CONTAINEDININDEX>
          <DESCRIPTION>48-port E/FE/GE (SB)</DESCRIPTION>
          <FIRMWARE>8.4.2.7</FIRMWARE>
          <INDEX>2</INDEX>
          <MODEL>S50-01-GE-48T-AC</MODEL>
          <NAME>1</NAME>
          <SERIAL>DL253300200</SERIAL>
          <TYPE>chassis</TYPE>
        </COMPONENT>
        <COMPONENT>
          <CONTAINEDININDEX>1</CONTAINEDININDEX>
          <INDEX>177783810</INDEX>
          <TYPE>port</TYPE>
        </COMPONENT>
        <COMPONENT>
          <CONTAINEDININDEX>2</CONTAINEDININDEX>
          <INDEX>242009090</INDEX>
          <TYPE>port</TYPE>
        </COMPONENT>
      </COMPONENTS>
      <INFO>
        <MAC>00:01:e8:d7:c9:1d</MAC>
        <NAME>sw-s50</NAME>
        <SERIAL>DL253300100</SERIAL>
        <TYPE>NETWORKING</TYPE>
      </INFO>
      <PORTS>
        <PORT>
          <IFDESCR>GigabitEthernet 0/1</IFDESCR>
          <IFNAME>GigabitEthernet 0/1</IFNAME>
          <IFNUMBER>177783810</IFNUMBER>
          <IFTYPE>6</IFTYPE>
          <MAC>00:01:e8:d7:c9:1d</MAC>
        </PORT>
        <PORT>
          <IFDESCR>GigabitEthernet 1/1</IFDESCR>
          <IFNAME>GigabitEthernet 1/1</IFNAME>
          <IFNUMBER>242009090</IFNUMBER>
          <IFTYPE>6</IFTYPE>
          <MAC>00:01:e8:d7:c9:1d</MAC>
        </PORT>
      </PORTS>
    </DEVICE>
    <MODULEVERSION>4.1</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

        // Import the switch(es) into GLPI
        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($xml_source));
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        if ($inventory->inError()) {
            foreach ($inventory->getErrors() as $error) {
                var_dump($error);
            }
        }
        $this->assertFalse($inventory->inError());
        $this->assertSame([], $inventory->getErrors());

        $networkEquipment = new \NetworkEquipment();
        $networkPort      = new \NetworkPort();

        $this->assertSame(2, countElementsInTable($networkPort->getTable()), 'Must have two ports');

        foreach ([['DL253300100', 177783810], ['DL253300200', 242009090]] as list($serial, $logical_number)) {
            $this->assertTrue(
                $networkEquipment->getFromDBByCrit(['serial' => $serial]),
                "Switch s/n $serial doesn't exist"
            );

            $this->assertTrue(
                $networkPort->getFromDBByCrit([
                    'itemtype' => $networkEquipment->getType(),
                    'items_id' => $networkEquipment->getID(),
                    'logical_number' => $logical_number
                ]),
                sprintf("Switch \"%s\" doesn't have port with ifindex %d", $networkEquipment->fields['name'], $logical_number)
            );
        }
    }

    public function testHandle()
    {
        $json_str = file_get_contents(self::INV_FIXTURES . 'networkequipment_2.json');
        $json = json_decode($json_str);

        $netequ = new \NetworkEquipment();

        $data = (array)$json->content;
        $inventory = new \Glpi\Inventory\Inventory();
        $this->assertTrue($inventory->setData($json));

        $agent = new \Agent();
        $this->assertSame(0, $agent->handleAgent($inventory->extractMetadata()));

        $main = new \Glpi\Inventory\Asset\NetworkEquipment($netequ, $json);
        $main->setAgent($agent)->setExtraData($data);
        $main->checkConf(new \Glpi\Inventory\Conf());
        $result = $main->prepare();
        $this->assertCount(5, $result);

        $this->assertTrue($main->isStackedSwitch());

        $main->handle();
        $this->assertTrue($main->areLinksHandled());
    }

    /**
     * Test connection with an existing Switch
     */
    public function testNortelSwitch()
    {
        //Nortel switch
        $xml_source = '<?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <MAC>8c:60:4f:8d:ae:fc</MAC>
        <NAME>nortel</NAME>
        <SERIAL>notrel3456</SERIAL>
        <TYPE>NETWORKING</TYPE>
      </INFO>
      <PORTS>
        <PORT>
          <CONNECTIONS>
            <CDP>1</CDP>
            <CONNECTION>
              <IFNUMBER>22</IFNUMBER>
              <SYSMAC>00:24:b5:bd:c8:01</SYSMAC>
            </CONNECTION>
          </CONNECTIONS>
          <IFDESCR>mgmt0</IFDESCR>
          <IFNAME>mgmt0</IFNAME>
          <IFNUMBER>22</IFNUMBER>
          <IFTYPE>6</IFTYPE>
          <MAC>8c:60:4f:8d:ae:a1</MAC>
        </PORT>
      </PORTS>
    </DEVICE>
    <MODULEVERSION>4.1</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

        $networkEquipment       = new \NetworkEquipment();
        $networkport            = new \NetworkPort();

        // Another switch
        $networkequipments_other_id = $networkEquipment->add([
            'name'        => 'otherswitch',
            'entities_id' => 0
        ]);
        $this->assertGreaterThan(0, $networkequipments_other_id);

        $networkports_other_id = $networkport->add([
            'itemtype'       => 'NetworkEquipment',
            'items_id'       => $networkequipments_other_id,
            'entities_id'    => 0,
            'mac'            => '00:24:b5:bd:c8:01',
            'logical_number' => 22
        ]);
        $this->assertGreaterThan(0, $networkports_other_id);

        // Import the switch into GLPI
        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($xml_source));
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        if ($inventory->inError()) {
            foreach ($inventory->getErrors() as $error) {
                var_dump($error);
            }
        }
        $this->assertFalse($inventory->inError());
        $this->assertSame([], $inventory->getErrors());

        $links = getAllDataFromTable('glpi_networkports_networkports');
        $this->assertCount(1, $links, 'May have 1 connection between 2 network ports');
        $link = current($links);
        $this->assertSame($networkports_other_id, $link['networkports_id_2'], 'Must be linked to otherswitch port');

        $a_networkports = getAllDataFromTable('glpi_networkports');
        $this->assertCount(2, $a_networkports, 'May have 2 network ports (' . print_r($a_networkports, true) . ')');
    }

    /**
     * Test connection between an existing Unmanaged device and a Switch
     */
    public function testNortelUnmanaged()
    {
        //Nortel switch
        $xml_source = '<?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <MAC>8c:60:4f:8d:ae:fc</MAC>
        <NAME>nortel</NAME>
        <SERIAL>notrel3456</SERIAL>
        <TYPE>NETWORKING</TYPE>
      </INFO>
      <PORTS>
        <PORT>
          <CONNECTIONS>
            <CDP>1</CDP>
            <CONNECTION>
              <IFNUMBER>22</IFNUMBER>
              <SYSMAC>00:24:b5:bd:c8:01</SYSMAC>
            </CONNECTION>
          </CONNECTIONS>
          <IFDESCR>mgmt0</IFDESCR>
          <IFNAME>mgmt0</IFNAME>
          <IFNUMBER>22</IFNUMBER>
          <IFTYPE>6</IFTYPE>
          <MAC>8c:60:4f:8d:ae:a1</MAC>
        </PORT>
      </PORTS>
    </DEVICE>
    <MODULEVERSION>4.1</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

        $unmanaged = new \Unmanaged();
        $networkport = new \NetworkPort();

        // Unmanaged device
        $unmanageds_id = $unmanaged->add([
            'name'        => 'Unmanaged device',
            'entities_id' => 0
        ]);
        $this->assertGreaterThan(0, $unmanageds_id);

        $unmanaged_networkports_id = $networkport->add([
            'itemtype'       => 'Unmanaged',
            'items_id'       => $unmanageds_id,
            'entities_id'    => 0,
            'mac'            => '00:24:b5:bd:c8:01',
            'logical_number' => 22
        ]);
        $this->assertGreaterThan(0, $unmanaged_networkports_id);

        // Import the switch into GLPI
        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($xml_source));
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        if ($inventory->inError()) {
            foreach ($inventory->getErrors() as $error) {
                var_dump($error);
            }
        }
        $this->assertFalse($inventory->inError());
        $this->assertSame([], $inventory->getErrors());

        $links = getAllDataFromTable('glpi_networkports_networkports');
        $this->assertCount(1, $links, 'May have 1 connection between 2 network ports');
        $link = current($links);
        $this->assertSame($unmanaged_networkports_id, $link['networkports_id_2'], 'Must be linked to Unmanaged device port');

        $a_networkports = getAllDataFromTable('glpi_networkports');
        $this->assertCount(2, $a_networkports, 'May have 2 network ports (' . print_r($a_networkports, true) . ')');
    }

    /**
     * Test connection with no existing device
     */
    public function testNortelNodevice()
    {
        //Nortel switch
        $xml_source = '<?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <MAC>8c:60:4f:8d:ae:fc</MAC>
        <NAME>nortel</NAME>
        <SERIAL>notrel3456</SERIAL>
        <TYPE>NETWORKING</TYPE>
      </INFO>
      <PORTS>
        <PORT>
          <CONNECTIONS>
            <CDP>1</CDP>
            <CONNECTION>
              <IFNUMBER>22</IFNUMBER>
              <SYSMAC>00:24:b5:bd:c8:01</SYSMAC>
            </CONNECTION>
          </CONNECTIONS>
          <IFDESCR>mgmt0</IFDESCR>
          <IFNAME>mgmt0</IFNAME>
          <IFNUMBER>22</IFNUMBER>
          <IFTYPE>6</IFTYPE>
          <MAC>8c:60:4f:8d:ae:a1</MAC>
        </PORT>
      </PORTS>
    </DEVICE>
    <MODULEVERSION>4.1</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

        // Import the switch into GLPI
        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($xml_source));
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        if ($inventory->inError()) {
            foreach ($inventory->getErrors() as $error) {
                var_dump($error);
            }
        }
        $this->assertFalse($inventory->inError());
        $this->assertSame([], $inventory->getErrors());

        $links = getAllDataFromTable('glpi_networkports_networkports');
        $this->assertCount(1, $links, 'May have 1 connection between 2 network ports');
        $link = current($links);
        $netport = new \NetworkPort();
        $this->assertTrue($netport->getFromDBByCrit(['mac' => '00:24:b5:bd:c8:01']));
        $this->assertSame($netport->fields['id'], $link['networkports_id_2'], 'Must be linked to new Unmanaged device port');

        $a_networkports = getAllDataFromTable('glpi_networkports');
        $this->assertCount(2, $a_networkports, 'May have 2 network ports (' . print_r($a_networkports, true) . ')');

        $unmanageds = getAllDataFromTable(\Unmanaged::getTable());
        $this->assertCount(1, $unmanageds, 'May have 1 new unmanaged device');
    }

    /**
     * @test
     */
    public function testCisco1Switch()
    {
        //Cisco switch
        $xml_source = '<?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <MAC>00:24:b5:bd:c8:01</MAC>
        <NAME>cisco1</NAME>
        <SERIAL>cisco1</SERIAL>
        <TYPE>NETWORKING</TYPE>
      </INFO>
      <PORTS>
        <PORT>
          <CONNECTIONS>
            <CDP>1</CDP>
            <CONNECTION>
              <IFDESCR>GigabitEthernet0/10</IFDESCR>
              <IP>192.168.200.124</IP>
            </CONNECTION>
          </CONNECTIONS>
          <IFDESCR>mgmt0</IFDESCR>
          <IFNAME>mgmt0</IFNAME>
          <IFNUMBER>22</IFNUMBER>
          <IFTYPE>6</IFTYPE>
          <MAC>00:24:b5:bd:c8:02</MAC>
        </PORT>
      </PORTS>
    </DEVICE>
    <MODULEVERSION>4.1</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

        $networkEquipment       = new \NetworkEquipment();
        $networkport            = new \NetworkPort();
        $networkName            = new \NetworkName();
        $iPAddress              = new \IPAddress();

        // Another switch
        $networkequipments_other_id = $networkEquipment->add([
            'name'        => 'otherswitch',
            'entities_id' => 0
        ]);
        $this->assertGreaterThan(0, $networkequipments_other_id);

        // Management port
        $managementports_id = $networkport->add([
            'itemtype'          => 'NetworkEquipment',
            'instantiation_type' => 'NetworkPortAggregate',
            'items_id'          => $networkequipments_other_id,
            'entities_id'       => 0
        ]);
        $this->assertGreaterThan(0, $managementports_id);

        $networknames_id = $networkName->add([
            'entities_id' => 0,
            'itemtype'    => 'NetworkPort',
            'items_id'    => $managementports_id
        ]);
        $this->assertGreaterThan(0, $networknames_id);

        $ipaddress_id = $iPAddress->add([
            'entities_id' => 0,
            'itemtype' => 'NetworkName',
            'items_id' => $networknames_id,
            'name' => '192.168.200.124'
        ]);
        $this->assertGreaterThan(0, $ipaddress_id);

        // Port GigabitEthernet0/10
        $networkports_other_id = $networkport->add([
            'itemtype'       => 'NetworkEquipment',
            'items_id'       => $networkequipments_other_id,
            'entities_id'    => 0,
            'mac'            => '00:24:b5:bd:c8:01',
            'logical_number' => 22,
            'ifdescr' => 'GigabitEthernet0/10'
        ]);
        $this->assertGreaterThan(0, $networkports_other_id);

        // Import the switch into GLPI
        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($xml_source));
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        if ($inventory->inError()) {
            foreach ($inventory->getErrors() as $error) {
                var_dump($error);
            }
        }
        $this->assertFalse($inventory->inError());
        $this->assertSame([], $inventory->getErrors());

        $a_portslinks = getAllDataFromTable('glpi_networkports_networkports');
        $this->assertCount(1, $a_portslinks, sprintf('May have 1 connection between 2 network ports, %s found', count($a_portslinks)));

        $a_networkports = getAllDataFromTable('glpi_networkports');
        $this->assertCount(3, $a_networkports, 'May have 3 network ports (' . print_r($a_networkports, true) . ')');

        $portLink = current($a_portslinks);
        $this->assertSame($networkports_other_id, $portLink['networkports_id_2']);
    }

    /**
     * It find unknown device, but may add the port with this ifdescr
     *
     * FIXME: does not work :'(
     */
    public function testCisco1Unmanaged()
    {
        //Cisco switch
        $xml_source = '<?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <MAC>00:24:b5:bd:c8:01</MAC>
        <NAME>cisco1</NAME>
        <SERIAL>cisco1</SERIAL>
        <TYPE>NETWORKING</TYPE>
      </INFO>
      <PORTS>
        <PORT>
          <CONNECTIONS>
            <CDP>1</CDP>
            <CONNECTION>
              <IFDESCR>GigabitEthernet0/10</IFDESCR>
              <IP>192.168.200.124</IP>
            </CONNECTION>
          </CONNECTIONS>
          <IFDESCR>mgmt0</IFDESCR>
          <IFNAME>mgmt0</IFNAME>
          <IFNUMBER>22</IFNUMBER>
          <IFTYPE>6</IFTYPE>
          <MAC>00:24:b5:bd:c8:02</MAC>
        </PORT>
      </PORTS>
    </DEVICE>
    <MODULEVERSION>4.1</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

        $networkport            = new \NetworkPort();
        $networkName            = new \NetworkName();
        $iPAddress              = new \IPAddress();
        $unmanaged              = new \Unmanaged();

        // Unmanaged
        $unmanageds_id = $unmanaged->add([
            'name'        => 'otherswitch',
            'entities_id' => 0
        ]);
        $this->assertGreaterThan(0, $unmanageds_id);

        $networkports_unknown_id = $networkport->add([
            'itemtype'       => 'Unmanaged',
            'items_id'       => $unmanageds_id,
            'entities_id'    => 0
        ]);
        $this->assertGreaterThan(0, $networkports_unknown_id);

        $networknames_id = $networkName->add([
            'entities_id' => 0,
            'itemtype'    => 'NetworkPort',
            'items_id'    => $networkports_unknown_id
        ]);
        $this->assertGreaterThan(0, $networknames_id);

        $ipaddress_id = $iPAddress->add([
            'entities_id' => 0,
            'itemtype' => 'NetworkName',
            'items_id' => $networknames_id,
            'name' => '192.168.200.124'
        ]);
        $this->assertGreaterThan(0, $ipaddress_id);

        // Import the switch into GLPI
        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($xml_source));
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        if ($inventory->inError()) {
            foreach ($inventory->getErrors() as $error) {
                var_dump($error);
            }
        }
        $this->assertFalse($inventory->inError());
        $this->assertSame([], $inventory->getErrors());

        $a_portslinks = getAllDataFromTable('glpi_networkports_networkports');
        $this->assertCount(1, $a_portslinks, 'May have 1 connection between 2 network ports');

        $a_networkports = getAllDataFromTable('glpi_networkports');
        $this->assertCount(3, $a_networkports, 'May have 3 network ports (' . print_r($a_networkports, true) . ')');

        //FIXME: does not work :'(
        //$a_unknowns = getAllDataFromTable('glpi_unmanageds');
        //$this->assertCount(1, $a_unknowns, 'May have only one unknown device (' . print_r($a_unknowns, true) . ')');

        $a_networkport_ref = [
            'items_id'           => $unmanageds_id,
            'itemtype'           => 'Unmanaged',
            'entities_id'        => 0,
            'is_recursive'       => 0,
            'logical_number'     => 0,
            'name'               => 'GigabitEthernet0/10',
            'instantiation_type' => 'NetworkPortEthernet',
            'mac'                => null,
            'comment'            => null,
            'is_deleted'         => 0,
            'is_dynamic'         => 0,
            'ifmtu' => 0,
            'ifspeed' => 0,
            'ifinternalstatus' => null,
            'ifconnectionstatus' => 0,
            'iflastchange' => null,
            'ifinbytes' => 0,
            'ifinerrors' => 0,
            'ifoutbytes' => 0,
            'ifouterrors' => 0,
            'ifstatus' => null,
            'ifdescr' => null,
            'ifalias' => null,
            'portduplex' => null,
            'trunk' => 0,
            'lastup' => null
        ];
        $networkport = new \NetworkPort();
        $this->assertTrue($networkport->getFromDBByCrit(['name' => 'GigabitEthernet0/10']));
        unset(
            $networkport->fields['id'],
            $networkport->fields['date_mod'],
            $networkport->fields['date_creation']
        );

        //FIXME: does not work :'(
        /*$this->assertEquals($a_networkport_ref, $networkport->fields, 'New unknown port created');

        $portLink = current($a_portslinks);
        $this->assertSame($networkports_unknown_id, $a_portslinks['networkports_id_2']);*/
    }

    public function testCisco1Nodevice()
    {
        //Cisco switch
        $xml_source = '<?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <MAC>00:24:b5:bd:c8:01</MAC>
        <NAME>cisco1</NAME>
        <SERIAL>cisco1</SERIAL>
        <TYPE>NETWORKING</TYPE>
      </INFO>
      <PORTS>
        <PORT>
          <CONNECTIONS>
            <CDP>1</CDP>
            <CONNECTION>
              <IFDESCR>GigabitEthernet0/10</IFDESCR>
              <IP>192.168.200.124</IP>
            </CONNECTION>
          </CONNECTIONS>
          <IFDESCR>mgmt0</IFDESCR>
          <IFNAME>mgmt0</IFNAME>
          <IFNUMBER>22</IFNUMBER>
          <IFTYPE>6</IFTYPE>
          <MAC>00:24:b5:bd:c8:02</MAC>
        </PORT>
      </PORTS>
    </DEVICE>
    <MODULEVERSION>4.1</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

        // Import the switch into GLPI
        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($xml_source));
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        if ($inventory->inError()) {
            foreach ($inventory->getErrors() as $error) {
                var_dump($error);
            }
        }
        $this->assertFalse($inventory->inError());
        $this->assertSame([], $inventory->getErrors());

        $a_portslinks = getAllDataFromTable('glpi_networkports_networkports');
        $this->assertCount(1, $a_portslinks, 'May have 1 connection between 2 network ports');

        $a_networkports = getAllDataFromTable('glpi_networkports');
        $this->assertCount(2, $a_networkports, 'May have 2 network ports (' . print_r($a_networkports, true) . ')');

        $networkPort = new \NetworkPort();
        $networkPort->getFromDBByCrit(['name' => 'GigabitEthernet0/10']);

        $portLink = current($a_portslinks);
        $this->assertSame($networkPort->fields['id'], $portLink['networkports_id_2']);
    }

    /**
     * //FIXME: does not work :'(
     */
    public function testCisco2Switch()
    {
        //Cisco switch
        $xml_source = '<?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <MAC>00:24:b5:bd:c8:01</MAC>
        <NAME>cisco2</NAME>
        <SERIAL>cisco2</SERIAL>
        <TYPE>NETWORKING</TYPE>
      </INFO>
      <PORTS>
        <PORT>
          <CONNECTIONS>
            <CDP>1</CDP>
            <CONNECTION>
              <IFDESCR>ge-0/0/1.0</IFDESCR>
              <IFNUMBER>504</IFNUMBER>
              <SYSDESCR>Juniper Networks, Inc. ex2200-24t-4g , version 10.1R1.8 Build date: 2010-02-12 16:59:31 UTC </SYSDESCR>
              <SYSMAC>2c:6b:f5:98:f9:70</SYSMAC>
              <SYSNAME>juniperswitch3</SYSNAME>
            </CONNECTION>
          </CONNECTIONS>
          <IFDESCR>mgmt0</IFDESCR>
          <IFNAME>mgmt0</IFNAME>
          <IFNUMBER>22</IFNUMBER>
          <IFTYPE>6</IFTYPE>
          <MAC>00:24:b5:bd:c8:02</MAC>
        </PORT>
      </PORTS>
    </DEVICE>
    <MODULEVERSION>4.1</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

        $networkEquipment = new \NetworkEquipment();
        $networkport = new \NetworkPort();

        // Another switch
        $networkequipments_other_id = $networkEquipment->add([
            'name'        => 'juniperswitch3',
            'entities_id' => 0
        ]);
        $this->assertGreaterThan(0, $networkequipments_other_id);

        // Port ge-0/0/1.0
        $networkports_other_id = $networkport->add([
            'itemtype'       => 'NetworkEquipment',
            'items_id'       => $networkequipments_other_id,
            'entities_id'    => 0,
            'mac'            => '2c:6b:f5:98:f9:70',
            'logical_number' => 504,
            'ifdescr' => 'ge-0/0/1.0'
        ]);
        $this->assertGreaterThan(0, $networkports_other_id);

        // Import the switch into GLPI
        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($xml_source));
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        if ($inventory->inError()) {
            foreach ($inventory->getErrors() as $error) {
                var_dump($error);
            }
        }
        $this->assertFalse($inventory->inError());
        $this->assertSame([], $inventory->getErrors());

        $a_portslinks = getAllDataFromTable('glpi_networkports_networkports');
        $this->assertCount(1, $a_portslinks, 'May have 1 connection between 2 network ports');

        $a_networkports = getAllDataFromTable('glpi_networkports');
        $this->assertCount(2, $a_networkports, 'May have 2 network ports (' . print_r($a_networkports, true) . ')');

        //FIXME: does not work :'(
        /*$portLink = current($a_portslinks);
        $this->assertSame($networkequipments_other_id, $portLink['networkports_id_2']);*/
    }

    public function testSwitchLldpImport()
    {
        $xml_source = '<?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <TYPE>NETWORKING</TYPE>
        <MANUFACTURER>Hewlett-Packard</MANUFACTURER>
        <MODEL>J9085A</MODEL>
        <DESCRIPTION>ProCurve J9085A</DESCRIPTION>
        <NAME>FR-SW01</NAME>
        <LOCATION>BAT A - Niv 3</LOCATION>
        <CONTACT>Admin</CONTACT>
        <SERIAL>CN536H7J</SERIAL>
        <FIRMWARE>R.10.06 R.11.60</FIRMWARE>
        <UPTIME>8 days, 01:48:57.95</UPTIME>
        <MAC>b4:39:d6:3a:7f:00</MAC>
        <ID>0</ID>
        <IPS>
          <IP>192.168.1.56</IP>
          <IP>192.168.10.56</IP>
        </IPS>
      </INFO>
      <PORTS>
        <PORT>
          <CONNECTIONS>
            <CDP>1</CDP>
            <CONNECTION>
              <IFDESCR>ge-0/0/1.0</IFDESCR>
              <IFNUMBER>504</IFNUMBER>
              <SYSDESCR>Juniper Networks, Inc. ex2200-24t-4g , version 10.1R1.8 Build date: 2010-02-12 16:59:31 UTC </SYSDESCR>
              <SYSMAC>2c:6b:f5:98:f9:70</SYSMAC>
              <SYSNAME>juniperswitch3</SYSNAME>
            </CONNECTION>
          </CONNECTIONS>
          <IFDESCR>3</IFDESCR>
          <IFNAME>3</IFNAME>
          <IFNUMBER>3</IFNUMBER>
          <IFSPEED>1000000000</IFSPEED>
          <IFSTATUS>1</IFSTATUS>
          <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
          <IFPORTDUPLEX>2</IFPORTDUPLEX>
          <IFTYPE>6</IFTYPE>
          <MAC>b4:39:d6:3b:22:bd</MAC>
          <VLANS>
            <VLAN>
              <NAME>VLAN160</NAME>
              <NUMBER>160</NUMBER>
            </VLAN>
          </VLANS>
        </PORT>
      </PORTS>
    </DEVICE>
    <MODULEVERSION>3.0</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

        $networkEquipment        = new \NetworkEquipment();
        $networkPort             = new \NetworkPort();
        $networkPort_NetworkPort = new \NetworkPort_NetworkPort();

        $networkEquipments_id = $networkEquipment->add([
            'entities_id' => 0,
            'name'        => 'juniperswitch3',
        ]);
        $this->assertGreaterThan(0, $networkEquipments_id);

        // Add management port
        // 2c:6b:f5:98:f9:70
        $mngtports_id = $networkPort->add([
            'mac'                => '2c:6b:f5:98:f9:70',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            'instantiation_type' => 'NetworkPortAggregate',
            'name'               => 'general',
        ]);
        $this->assertGreaterThan(0, $mngtports_id);

        $ports_id = $networkPort->add([
            'mac'                => '2c:6b:f5:98:f9:71',
            'name'               => 'ge-0/0/1.0',
            'logical_number'     => '504',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            'ifdescr'         => 'ge-0/0/1.0',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // Import the switch into GLPI
        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($xml_source));
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        if ($inventory->inError()) {
            foreach ($inventory->getErrors() as $error) {
                var_dump($error);
            }
        }
        $this->assertFalse($inventory->inError());
        $this->assertSame([], $inventory->getErrors());

        // check port
        $this->assertTrue($networkPort->getFromDBByCrit(['mac' => 'b4:39:d6:3b:22:bd']));
        $this->assertTrue($networkPort_NetworkPort->getFromDBForNetworkPort($networkPort->fields['id']));
    }

    /**
     * case 1 : IP on management port of the switch
     */
    public function testSwitchLLDPImport_ifdescr_ip_case1()
    {
        $xml_source = '<?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <TYPE>NETWORKING</TYPE>
        <MANUFACTURER>Hewlett-Packard</MANUFACTURER>
        <MODEL>J9085A</MODEL>
        <DESCRIPTION>ProCurve J9085A</DESCRIPTION>
        <NAME>FR-SW01</NAME>
        <LOCATION>BAT A - Niv 3</LOCATION>
        <CONTACT>Admin</CONTACT>
        <SERIAL>CN536H7J</SERIAL>
        <FIRMWARE>R.10.06 R.11.60</FIRMWARE>
        <UPTIME>8 days, 01:48:57.95</UPTIME>
        <MAC>b4:39:d6:3a:7f:00</MAC>
        <ID>0</ID>
        <IPS>
          <IP>192.168.1.56</IP>
          <IP>192.168.10.56</IP>
        </IPS>
      </INFO>
      <PORTS>
        <PORT>
          <CONNECTIONS>
            <CDP>1</CDP>
            <CONNECTION>
              <IFDESCR>28</IFDESCR>
              <IP>10.226.164.55</IP>
            </CONNECTION>
          </CONNECTIONS>
          <IFDESCR>3</IFDESCR>
          <IFNAME>3</IFNAME>
          <IFNUMBER>3</IFNUMBER>
          <IFSPEED>1000000000</IFSPEED>
          <IFSTATUS>1</IFSTATUS>
          <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
          <IFPORTDUPLEX>2</IFPORTDUPLEX>
          <IFTYPE>6</IFTYPE>
          <MAC>b4:39:d6:3b:22:bd</MAC>
          <VLANS>
            <VLAN>
              <NAME>VLAN160</NAME>
              <NUMBER>160</NUMBER>
            </VLAN>
          </VLANS>
        </PORT>
      </PORTS>
    </DEVICE>
    <MODULEVERSION>3.0</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

        $networkEquipment        = new \NetworkEquipment();
        $networkPort             = new \NetworkPort();
        $networkPort_NetworkPort = new \NetworkPort_NetworkPort();

        $networkEquipments_id = $networkEquipment->add([
            'entities_id' => 0,
            'name'        => 'sw10',
        ]);
        $this->assertGreaterThan(0, $networkEquipments_id);

        // Add management port
        $mngtports_id = $networkPort->add([
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            'instantiation_type' => 'NetworkPortAggregate',
            'name'               => 'general',
            '_create_children'   => 1,
            'NetworkName_name'   => '',
            'NetworkName_fqdns_id' => 0,
            'NetworkName__ipaddresses' => [
                '-1' => '10.226.164.55'
            ],

        ]);
        $this->assertGreaterThan(0, $mngtports_id);

        // Add a port that will not be used, but needed for the test
        $ports_id = $networkPort->add([
            'mac'                => '00:6b:03:98:f9:70',
            'name'               => 'port27',
            'logical_number'     => '28',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            'ifdescr'         => '27',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // Add the second port right
        $ports_id = $networkPort->add([
            'mac'                => '00:6b:03:98:f9:71',
            'name'               => 'port28',
            'logical_number'     => '30',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            'ifdescr'         => '28',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // Add another port that will not be used, but needed for the test
        $ports_id = $networkPort->add([
            'mac'                => '00:6b:03:98:f9:72',
            'name'               => 'port29',
            'logical_number'     => '29',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            'ifdescr'         => '29',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // Import the switch into GLPI
        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($xml_source));
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        if ($inventory->inError()) {
            foreach ($inventory->getErrors() as $error) {
                var_dump($error);
            }
        }
        $this->assertFalse($inventory->inError());
        $this->assertSame([], $inventory->getErrors());

        // check port
        $this->assertTrue($networkPort->getFromDBByCrit(['name' => 'port28']));
        $this->assertTrue($networkPort_NetworkPort->getFromDBForNetworkPort($networkPort->fields['id']));
    }

    /**
     * case 2 : IP on the port of the switch
     */
    public function testSwitchLLDPImport_ifdescr_ip_case2()
    {
        $xml_source = '<?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <TYPE>NETWORKING</TYPE>
        <MANUFACTURER>Hewlett-Packard</MANUFACTURER>
        <MODEL>J9085A</MODEL>
        <DESCRIPTION>ProCurve J9085A</DESCRIPTION>
        <NAME>FR-SW01</NAME>
        <LOCATION>BAT A - Niv 3</LOCATION>
        <CONTACT>Admin</CONTACT>
        <SERIAL>CN536H7J</SERIAL>
        <FIRMWARE>R.10.06 R.11.60</FIRMWARE>
        <UPTIME>8 days, 01:48:57.95</UPTIME>
        <MAC>b4:39:d6:3a:7f:00</MAC>
        <ID>0</ID>
        <IPS>
          <IP>192.168.1.56</IP>
          <IP>192.168.10.56</IP>
        </IPS>
      </INFO>
      <PORTS>
        <PORT>
          <CONNECTIONS>
            <CDP>1</CDP>
            <CONNECTION>
              <IFDESCR>28</IFDESCR>
              <IP>10.226.164.55</IP>
            </CONNECTION>
          </CONNECTIONS>
          <IFDESCR>3</IFDESCR>
          <IFNAME>3</IFNAME>
          <IFNUMBER>3</IFNUMBER>
          <IFSPEED>1000000000</IFSPEED>
          <IFSTATUS>1</IFSTATUS>
          <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
          <IFPORTDUPLEX>2</IFPORTDUPLEX>
          <IFTYPE>6</IFTYPE>
          <MAC>b4:39:d6:3b:22:bd</MAC>
          <VLANS>
            <VLAN>
              <NAME>VLAN160</NAME>
              <NUMBER>160</NUMBER>
            </VLAN>
          </VLANS>
        </PORT>
      </PORTS>
    </DEVICE>
    <MODULEVERSION>3.0</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

        $networkEquipment        = new \NetworkEquipment();
        $networkPort             = new \NetworkPort();
        $networkPort_NetworkPort = new \NetworkPort_NetworkPort();

        $networkEquipments_id = $networkEquipment->add([
            'entities_id' => 0,
            'name'        => 'sw10',
        ]);
        $this->assertGreaterThan(0, $networkEquipments_id);

        // Add a port that will not be used, but needed for the test
        $ports_id = $networkPort->add([
            'mac'                => '00:6b:03:98:f9:70',
            'name'               => 'port27',
            'logical_number'     => '28',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            '_create_children'   => 1,
            'NetworkName_name'   => '',
            'NetworkName_fqdns_id' => 0,
            'NetworkName__ipaddresses' => [
                '-1' => '10.226.164.55'
            ],
            'ifdescr'         => '27',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // Add the second port right
        $ports_id = $networkPort->add([
            'mac'                => '00:6b:03:98:f9:71',
            'name'               => 'port28',
            'logical_number'     => '30',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            '_create_children'   => 1,
            'NetworkName_name'   => '',
            'NetworkName_fqdns_id' => 0,
            'NetworkName__ipaddresses' => [
                '-1' => '10.226.164.55'
            ],
            'ifdescr'         => '28',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // Add another port that will not be used, but needed for the test
        $ports_id = $networkPort->add([
            'mac'                => '00:6b:03:98:f9:72',
            'name'               => 'port29',
            'logical_number'     => '31',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            '_create_children'   => 1,
            'NetworkName_name'   => '',
            'NetworkName_fqdns_id' => 0,
            'NetworkName__ipaddresses' => [
                '-1' => '10.226.164.55'
            ],
            'ifdescr'         => '29',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // Import the switch into GLPI
        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($xml_source));
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        if ($inventory->inError()) {
            foreach ($inventory->getErrors() as $error) {
                var_dump($error);
            }
        }
        $this->assertFalse($inventory->inError());
        $this->assertSame([], $inventory->getErrors());

        // check port
        $this->assertTrue($networkPort->getFromDBByCrit(['name' => 'port28']));
        $this->assertTrue($networkPort_NetworkPort->getFromDBForNetworkPort($networkPort->fields['id']));
    }

    /**
     * case 1 : mac on management port
     */
    public function testSwitchLLDPImport_ifnumber_mac_case1()
    {
        $xml_source = '<?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <TYPE>NETWORKING</TYPE>
        <MANUFACTURER>Hewlett-Packard</MANUFACTURER>
        <MODEL>J9085A</MODEL>
        <DESCRIPTION>ProCurve J9085A</DESCRIPTION>
        <NAME>FR-SW01</NAME>
        <LOCATION>BAT A - Niv 3</LOCATION>
        <CONTACT>Admin</CONTACT>
        <SERIAL>CN536H7J</SERIAL>
        <FIRMWARE>R.10.06 R.11.60</FIRMWARE>
        <UPTIME>8 days, 01:48:57.95</UPTIME>
        <MAC>b4:39:d6:3a:7f:00</MAC>
        <ID>0</ID>
        <IPS>
          <IP>192.168.1.56</IP>
          <IP>192.168.10.56</IP>
        </IPS>
      </INFO>
      <PORTS>
        <PORT>
          <CONNECTIONS>
            <CDP>1</CDP>
            <CONNECTION>
              <IFNUMBER>21</IFNUMBER>
              <SYSMAC>00:24:b5:bd:c8:01</SYSMAC>
            </CONNECTION>
          </CONNECTIONS>
          <IFDESCR>3</IFDESCR>
          <IFNAME>3</IFNAME>
          <IFNUMBER>3</IFNUMBER>
          <IFSPEED>1000000000</IFSPEED>
          <IFSTATUS>1</IFSTATUS>
          <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
          <IFPORTDUPLEX>2</IFPORTDUPLEX>
          <IFTYPE>6</IFTYPE>
          <MAC>b4:39:d6:3b:22:bd</MAC>
          <VLANS>
            <VLAN>
              <NAME>VLAN160</NAME>
              <NUMBER>160</NUMBER>
            </VLAN>
          </VLANS>
        </PORT>
      </PORTS>
    </DEVICE>
    <MODULEVERSION>3.0</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

        $networkEquipment        = new \NetworkEquipment();
        $networkPort             = new \NetworkPort();
        $networkPort_NetworkPort = new \NetworkPort_NetworkPort();

        $networkEquipments_id = $networkEquipment->add([
            'entities_id' => 0,
            'name'        => 'sw10',
        ]);
        $this->assertGreaterThan(0, $networkEquipments_id);

        // Add management port
        $mngtports_id = $networkPort->add([
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            'instantiation_type' => 'NetworkPortAggregate',
            'name'               => 'general',
            'mac'                => '00:24:b5:bd:c8:01',
        ]);
        $this->assertGreaterThan(0, $mngtports_id);

        // Add a port that will not be used, but needed for the test
        $ports_id = $networkPort->add([
            'name'               => 'port20',
            'logical_number'     => '20',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            'ifdescr'            => '20',
            'mac'                => '00:24:b5:bd:c8:02',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // Add the second port right
        $ports_id = $networkPort->add([
            'name'               => 'port21',
            'logical_number'     => '21',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            'ifdescr'            => '21',
            'mac'                => '00:24:b5:bd:c8:03',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // Add another port that will not be used, but needed for the test
        $ports_id = $networkPort->add([
            'name'               => 'port22',
            'logical_number'     => '22',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            'ifdescr'            => '22',
            'mac'                => '00:24:b5:bd:c8:04',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // Import the switch into GLPI
        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($xml_source));
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        if ($inventory->inError()) {
            foreach ($inventory->getErrors() as $error) {
                var_dump($error);
            }
        }

        $this->assertFalse($inventory->inError());
        $this->assertSame([], $inventory->getErrors());

        // check port
        $this->assertTrue($networkPort->getFromDBByCrit(['name' => 'port21']));
        $this->assertTrue($networkPort_NetworkPort->getFromDBForNetworkPort($networkPort->fields['id']));
    }

    /**
     * case 2 : mac on the right port
     */
    public function testSwitchLLDPImport_ifnumber_mac_case2()
    {
        $xml_source = '<?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <TYPE>NETWORKING</TYPE>
        <MANUFACTURER>Hewlett-Packard</MANUFACTURER>
        <MODEL>J9085A</MODEL>
        <DESCRIPTION>ProCurve J9085A</DESCRIPTION>
        <NAME>FR-SW01</NAME>
        <LOCATION>BAT A - Niv 3</LOCATION>
        <CONTACT>Admin</CONTACT>
        <SERIAL>CN536H7J</SERIAL>
        <FIRMWARE>R.10.06 R.11.60</FIRMWARE>
        <UPTIME>8 days, 01:48:57.95</UPTIME>
        <MAC>b4:39:d6:3a:7f:00</MAC>
        <ID>0</ID>
        <IPS>
          <IP>192.168.1.56</IP>
          <IP>192.168.10.56</IP>
        </IPS>
      </INFO>
      <PORTS>
        <PORT>
          <CONNECTIONS>
            <CDP>1</CDP>
            <CONNECTION>
              <IFNUMBER>21</IFNUMBER>
              <SYSMAC>00:24:b5:bd:c8:01</SYSMAC>
            </CONNECTION>
          </CONNECTIONS>
          <IFDESCR>3</IFDESCR>
          <IFNAME>3</IFNAME>
          <IFNUMBER>3</IFNUMBER>
          <IFSPEED>1000000000</IFSPEED>
          <IFSTATUS>1</IFSTATUS>
          <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
          <IFPORTDUPLEX>2</IFPORTDUPLEX>
          <IFTYPE>6</IFTYPE>
          <MAC>b4:39:d6:3b:22:bd</MAC>
          <VLANS>
            <VLAN>
              <NAME>VLAN160</NAME>
              <NUMBER>160</NUMBER>
            </VLAN>
          </VLANS>
        </PORT>
      </PORTS>
    </DEVICE>
    <MODULEVERSION>3.0</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

        $networkEquipment        = new \NetworkEquipment();
        $networkPort             = new \NetworkPort();
        $networkPort_NetworkPort = new \NetworkPort_NetworkPort();

        $networkEquipments_id = $networkEquipment->add([
            'entities_id' => 0,
            'name'        => 'sw10',
        ]);
        $this->assertGreaterThan(0, $networkEquipments_id);

        // Add a port that will not be used, but needed for the test
        $ports_id = $networkPort->add([
            'mac'                => '00:24:b5:bd:c8:00',
            'name'               => 'port20',
            'logical_number'     => '20',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            'ifdescr'         => '20',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // Add the second port right
        $ports_id = $networkPort->add([
            'mac'                => '00:24:b5:bd:c8:01',
            'name'               => 'port21',
            'logical_number'     => '21',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            'ifdescr'         => '21',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // Add another port that will not be used, but needed for the test
        $ports_id = $networkPort->add([
            'mac'                => '00:24:b5:bd:c8:02',
            'name'               => 'port22',
            'logical_number'     => '22',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            'ifdescr'         => '22',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // Import the switch into GLPI
        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($xml_source));
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        if ($inventory->inError()) {
            foreach ($inventory->getErrors() as $error) {
                var_dump($error);
            }
        }

        $this->assertFalse($inventory->inError());
        $this->assertSame([], $inventory->getErrors());

        // check port
        $this->assertTrue($networkPort->getFromDBByCrit(['name' => 'port21']));
        $this->assertTrue($networkPort_NetworkPort->getFromDBForNetworkPort($networkPort->fields['id']));
    }

    /**
     * case 3 : same mac on all ports
     */
    public function testSwitchLLDPImport_ifnumber_mac_case3()
    {
        $xml_source = '<?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <TYPE>NETWORKING</TYPE>
        <MANUFACTURER>Hewlett-Packard</MANUFACTURER>
        <MODEL>J9085A</MODEL>
        <DESCRIPTION>ProCurve J9085A</DESCRIPTION>
        <NAME>FR-SW01</NAME>
        <LOCATION>BAT A - Niv 3</LOCATION>
        <CONTACT>Admin</CONTACT>
        <SERIAL>CN536H7J</SERIAL>
        <FIRMWARE>R.10.06 R.11.60</FIRMWARE>
        <UPTIME>8 days, 01:48:57.95</UPTIME>
        <MAC>b4:39:d6:3a:7f:00</MAC>
        <ID>0</ID>
        <IPS>
          <IP>192.168.1.56</IP>
          <IP>192.168.10.56</IP>
        </IPS>
      </INFO>
      <PORTS>
        <PORT>
          <CONNECTIONS>
            <CDP>1</CDP>
            <CONNECTION>
              <IFNUMBER>21</IFNUMBER>
              <SYSMAC>00:24:b5:bd:c8:01</SYSMAC>
            </CONNECTION>
          </CONNECTIONS>
          <IFDESCR>3</IFDESCR>
          <IFNAME>3</IFNAME>
          <IFNUMBER>3</IFNUMBER>
          <IFSPEED>1000000000</IFSPEED>
          <IFSTATUS>1</IFSTATUS>
          <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
          <IFPORTDUPLEX>2</IFPORTDUPLEX>
          <IFTYPE>6</IFTYPE>
          <MAC>b4:39:d6:3b:22:bd</MAC>
          <VLANS>
            <VLAN>
              <NAME>VLAN160</NAME>
              <NUMBER>160</NUMBER>
            </VLAN>
          </VLANS>
        </PORT>
      </PORTS>
    </DEVICE>
    <MODULEVERSION>3.0</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

        $networkEquipment        = new \NetworkEquipment();
        $networkPort             = new \NetworkPort();
        $networkPort_NetworkPort = new \NetworkPort_NetworkPort();

        $networkEquipments_id = $networkEquipment->add([
            'entities_id' => 0,
            'name'        => 'sw10',
        ]);
        $this->assertGreaterThan(0, $networkEquipments_id);

        // Add a port that will not be used, but needed for the test
        $ports_id = $networkPort->add([
            'mac'                => '00:24:b5:bd:c8:01',
            'name'               => 'port20',
            'logical_number'     => '20',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            'ifdescr'         => '20',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // Add the second port right
        $ports_id = $networkPort->add([
            'mac'                => '00:24:b5:bd:c8:01',
            'name'               => 'port21',
            'logical_number'     => '21',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            'ifdescr'         => '21',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // Add another port that will not be used, but needed for the test
        $ports_id = $networkPort->add([
            'mac'                => '00:24:b5:bd:c8:01',
            'name'               => 'port22',
            'logical_number'     => '22',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            'ifdescr'         => '22',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // Import the switch into GLPI
        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($xml_source));
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        $this->assertFalse($inventory->inError());
        $this->assertSame([], $inventory->getErrors());

        // check port
        $this->assertTrue($networkPort->getFromDBByCrit(['name' => 'port21']));
        $this->assertTrue($networkPort_NetworkPort->getFromDBForNetworkPort($networkPort->fields['id']));
    }

    public function testSwitchLLDPImport_othercase1()
    {
        $xml_source = '<?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <TYPE>NETWORKING</TYPE>
        <MANUFACTURER>Hewlett-Packard</MANUFACTURER>
        <MODEL>J9085A</MODEL>
        <DESCRIPTION>ProCurve J9085A</DESCRIPTION>
        <NAME>FR-SW01</NAME>
        <LOCATION>BAT A - Niv 3</LOCATION>
        <CONTACT>Admin</CONTACT>
        <SERIAL>CN536H7J</SERIAL>
        <FIRMWARE>R.10.06 R.11.60</FIRMWARE>
        <UPTIME>8 days, 01:48:57.95</UPTIME>
        <MAC>b4:39:d6:3a:7f:00</MAC>
        <ID>0</ID>
        <IPS>
          <IP>192.168.1.56</IP>
          <IP>192.168.10.56</IP>
        </IPS>
      </INFO>
      <PORTS>
        <PORT>
         <CONNECTIONS>
            <CDP>1</CDP>
            <CONNECTION>
              <IFDESCR>48</IFDESCR>
              <IP>172.16.100.252</IP>
              <MODEL>ProCurve J9148A 2910al-48G-PoE Switch, revision W.14.49, ROM W.14.04 (/sw/code/build/sbm(t4a))</MODEL>
              <SYSDESCR>ProCurve J9148A 2910al-48G-PoE Switch, revision W.14.49, ROM W.14.04 (/sw/code/build/sbm(t4a))</SYSDESCR>
              <SYSNAME>0x78acc0146cc0</SYSNAME>
            </CONNECTION>
          </CONNECTIONS>
          <IFDESCR>3</IFDESCR>
          <IFNAME>3</IFNAME>
          <IFNUMBER>3</IFNUMBER>
          <IFSPEED>1000000000</IFSPEED>
          <IFSTATUS>1</IFSTATUS>
          <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
          <IFPORTDUPLEX>2</IFPORTDUPLEX>
          <IFTYPE>6</IFTYPE>
          <MAC>b4:39:d6:3b:22:bd</MAC>
          <VLANS>
            <VLAN>
              <NAME>VLAN160</NAME>
              <NUMBER>160</NUMBER>
            </VLAN>
          </VLANS>
        </PORT>
      </PORTS>
    </DEVICE>
    <MODULEVERSION>3.0</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

        $networkEquipment        = new \NetworkEquipment();
        $networkPort             = new \NetworkPort();
        $networkPort_NetworkPort = new \NetworkPort_NetworkPort();

        $networkEquipments_id = $networkEquipment->add([
            'entities_id' => 0,
            'name'        => 'sw001',
        ]);
        $this->assertGreaterThan(0, $networkEquipments_id);

        // Add management port
        $mngtports_id = $networkPort->add([
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            'instantiation_type' => 'NetworkPortAggregate',
            'name'               => 'general',
            '_create_children'   => 1,
            'NetworkName_name'   => '',
            'NetworkName_fqdns_id' => 0,
            'NetworkName__ipaddresses' => [
                '-1' => '172.16.100.252'
            ],
        ]);
        $this->assertGreaterThan(0, $mngtports_id);

        // Add a port that will not be used, but needed for the test
        $ports_id = $networkPort->add([
            'name'               => 'port47',
            'logical_number'     => '47',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            'ifdescr'         => '47',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // Add the second port right
        $ports_id = $networkPort->add([
            'name'               => 'port48',
            'logical_number'     => '48',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            'ifdescr'         => '48',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // Add another port that will not be used, but needed for the test
        $ports_id = $networkPort->add([
            'name'               => 'port49',
            'logical_number'     => '49',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            'ifdescr'         => '49',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // Import the switch into GLPI
        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($xml_source));
        //$json = json_decode($data);
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        $this->assertFalse($inventory->inError());
        $this->assertSame([], $inventory->getErrors());

        // check port
        $this->assertTrue($networkPort->getFromDBByCrit(['name' => 'port48']));
        $this->assertTrue($networkPort_NetworkPort->getFromDBForNetworkPort($networkPort->fields['id']));
    }

    public function testSysdescr()
    {
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
          <CONTENT>
            <DEVICE>
              <FIRMWARES>
                <DESCRIPTION>device firmware</DESCRIPTION>
                <MANUFACTURER>Cisco</MANUFACTURER>
                <NAME>UCS 6248UP 48-Port</NAME>
                <TYPE>device</TYPE>
                <VERSION>5.0(3)N2(4.02b)</VERSION>
              </FIRMWARES>
              <INFO>
                <COMMENTS>this a sysdescr</COMMENTS>
                <CONTACT>noc@glpi-project.org</CONTACT>
                <CPU>4</CPU>
                <FIRMWARE>5.0(3)N2(4.02b)</FIRMWARE>
                <ID>0</ID>
                <LOCATION>paris.pa3</LOCATION>
                <MAC>8c:60:4f:8d:ae:fc</MAC>
                <MANUFACTURER>Cisco</MANUFACTURER>
                <MODEL>UCS 6248UP 48-Port</MODEL>
                <NAME>ucs6248up-cluster-pa3-B</NAME>
                <SERIAL>SSI1912014B</SERIAL>
                <TYPE>NETWORKING</TYPE>
                <UPTIME>482 days, 05:42:18.50</UPTIME>
                <IPS>
                  <IP>127.0.0.1</IP>
                  <IP>10.2.5.10</IP>
                  <IP>192.168.12.5</IP>
                </IPS>
              </INFO>
            </DEVICE>
            <MODULEVERSION>4.1</MODULEVERSION>
            <PROCESSNUMBER>1</PROCESSNUMBER>
          </CONTENT>
          <DEVICEID>foo</DEVICEID>
          <QUERY>SNMPQUERY</QUERY>
        </REQUEST>";

          // Import the switch into GLPI
          $converter = new \Glpi\Inventory\Converter();
          $data = json_decode($converter->convert($xml_source));
          $inventory = new \Glpi\Inventory\Inventory($data);

          $this->assertFalse($inventory->inError());
          $this->assertSame([], $inventory->getErrors());

          // check sysdescr
          $networkequipement = new \NetworkEquipment();
          $found_np = $networkequipement->find(['name' => "ucs6248up-cluster-pa3-B"]);
          $this->assertCount(1, $found_np);
          $first_np = array_pop($found_np);
          $this->assertSame("this a sysdescr", $first_np['sysdescr']);

          $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
          <REQUEST>
            <CONTENT>
              <DEVICE>
                <FIRMWARES>
                  <DESCRIPTION>device firmware</DESCRIPTION>
                  <MANUFACTURER>Cisco</MANUFACTURER>
                  <NAME>UCS 6248UP 48-Port</NAME>
                  <TYPE>device</TYPE>
                  <VERSION>5.0(3)N2(4.02b)</VERSION>
                </FIRMWARES>
                <INFO>
                  <COMMENTS>this a updated sysdescr</COMMENTS>
                  <CONTACT>noc@glpi-project.org</CONTACT>
                  <CPU>4</CPU>
                  <FIRMWARE>5.0(3)N2(4.02b)</FIRMWARE>
                  <ID>0</ID>
                  <LOCATION>paris.pa3</LOCATION>
                  <MAC>8c:60:4f:8d:ae:fc</MAC>
                  <MANUFACTURER>Cisco</MANUFACTURER>
                  <MODEL>UCS 6248UP 48-Port</MODEL>
                  <NAME>ucs6248up-cluster-pa3-B</NAME>
                  <SERIAL>SSI1912014B</SERIAL>
                  <TYPE>NETWORKING</TYPE>
                  <UPTIME>482 days, 05:42:18.50</UPTIME>
                  <IPS>
                    <IP>127.0.0.1</IP>
                    <IP>10.2.5.10</IP>
                    <IP>192.168.12.5</IP>
                  </IPS>
                </INFO>
              </DEVICE>
              <MODULEVERSION>4.1</MODULEVERSION>
              <PROCESSNUMBER>1</PROCESSNUMBER>
            </CONTENT>
            <DEVICEID>foo</DEVICEID>
            <QUERY>SNMPQUERY</QUERY>
          </REQUEST>";

          // Import the switch into GLPI
          $converter = new \Glpi\Inventory\Converter();
          $data = json_decode($converter->convert($xml_source));
          $inventory = new \Glpi\Inventory\Inventory($data);

          $this->assertFalse($inventory->inError());
          $this->assertSame([], $inventory->getErrors());

          // check sysdescr
          $networkequipement = new \NetworkEquipment();
          $found_np = $networkequipement->find(['name' => "ucs6248up-cluster-pa3-B"]);
          $this->assertCount(1, $found_np);
          $first_np = array_pop($found_np);
          $this->assertSame("this a updated sysdescr", $first_np['sysdescr']);
    }

    public function testSwitchMacConnection1()
    {
        $xml_source = '<?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <TYPE>NETWORKING</TYPE>
        <MANUFACTURER>Hewlett-Packard</MANUFACTURER>
        <MODEL>J9085A</MODEL>
        <DESCRIPTION>ProCurve J9085A</DESCRIPTION>
        <NAME>FR-SW01</NAME>
        <LOCATION>BAT A - Niv 3</LOCATION>
        <CONTACT>Admin</CONTACT>
        <SERIAL>CN536H7J</SERIAL>
        <FIRMWARE>R.10.06 R.11.60</FIRMWARE>
        <UPTIME>8 days, 01:48:57.95</UPTIME>
        <MAC>b4:39:d6:3a:7f:00</MAC>
        <ID>0</ID>
        <IPS>
          <IP>192.168.1.56</IP>
          <IP>192.168.10.56</IP>
        </IPS>
      </INFO>
      <PORTS>
        <PORT>
          <CONNECTIONS>
            <CONNECTION>
              <MAC>bc:97:e1:5c:0e:90</MAC>
            </CONNECTION>
          </CONNECTIONS>
          <IFDESCR>gi0/3</IFDESCR>
          <IFNAME>gi0/3</IFNAME>
          <IFNUMBER>3</IFNUMBER>
          <IFSPEED>1000000000</IFSPEED>
          <IFSTATUS>1</IFSTATUS>
          <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
          <IFPORTDUPLEX>2</IFPORTDUPLEX>
          <IFTYPE>6</IFTYPE>
          <MAC>b4:39:d6:3b:22:bd</MAC>
          <VLANS>
            <VLAN>
              <NAME>VLAN160</NAME>
              <NUMBER>160</NUMBER>
            </VLAN>
          </VLANS>
        </PORT>
      </PORTS>
    </DEVICE>
    <MODULEVERSION>3.0</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

        $computer                = new \Computer();
        $networkPort             = new \NetworkPort();
        $networkPort_NetworkPort = new \NetworkPort_NetworkPort();

        // Create a computer
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0
        ]);
        $this->assertGreaterThan(0, $computers_id);

        // Add some computer ports
        $ports_id = $networkPort->add([
            'name'               => 'eth0',
            'logical_number'     => '1',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $computers_id,
            'itemtype'           => 'Computer',
            'mac'                => 'bc:97:e1:5c:0e:90',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        $ports_id = $networkPort->add([
            'name'               => 'eth1',
            'logical_number'     => '1',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $computers_id,
            'itemtype'           => 'Computer',
            'mac'                => 'bc:97:e1:5c:0e:91',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // Import the switch into GLPI
        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($xml_source));

        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        $this->assertFalse($inventory->inError());
        $this->assertSame([], $inventory->getErrors());

        // Verify that eth0 is the only port connected
        $this->assertSame(1, countElementsInTable($networkPort_NetworkPort->getTable()));
        $this->assertTrue($networkPort->getFromDBByCrit(['name' => 'eth0']));
        $this->assertTrue($networkPort_NetworkPort->getFromDBForNetworkPort($networkPort->fields['id']));
    }

    public function testSwitchMacConnection2()
    {
        $xml_source = '<?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <TYPE>NETWORKING</TYPE>
        <MANUFACTURER>Hewlett-Packard</MANUFACTURER>
        <MODEL>J9085A</MODEL>
        <DESCRIPTION>ProCurve J9085A</DESCRIPTION>
        <NAME>FR-SW01</NAME>
        <LOCATION>BAT A - Niv 3</LOCATION>
        <CONTACT>Admin</CONTACT>
        <SERIAL>CN536H7J</SERIAL>
        <FIRMWARE>R.10.06 R.11.60</FIRMWARE>
        <UPTIME>8 days, 01:48:57.95</UPTIME>
        <MAC>b4:39:d6:3a:7f:00</MAC>
        <ID>0</ID>
        <IPS>
          <IP>192.168.1.56</IP>
          <IP>192.168.10.56</IP>
        </IPS>
      </INFO>
      <PORTS>
        <PORT>
          <CONNECTIONS>
            <CONNECTION>
              <MAC>bc:97:e1:5c:0e:90</MAC>
              <MAC>00:85:eb:f4:be:20</MAC>
            </CONNECTION>
          </CONNECTIONS>
          <IFDESCR>gi0/3</IFDESCR>
          <IFNAME>gi0/3</IFNAME>
          <IFNUMBER>3</IFNUMBER>
          <IFSPEED>1000000000</IFSPEED>
          <IFSTATUS>1</IFSTATUS>
          <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
          <IFPORTDUPLEX>2</IFPORTDUPLEX>
          <IFTYPE>6</IFTYPE>
          <MAC>b4:39:d6:3b:22:bd</MAC>
          <VLANS>
            <VLAN>
              <NAME>VLAN160</NAME>
              <NUMBER>160</NUMBER>
            </VLAN>
          </VLANS>
        </PORT>
      </PORTS>
    </DEVICE>
    <MODULEVERSION>3.0</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

        $networkPort             = new \NetworkPort();
        $unmanaged               = new \Unmanaged();
        $networkPort_NetworkPort = new \NetworkPort_NetworkPort();

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $computers_id = $computer->fields['id'];
        $this->assertGreaterThan(0, $computers_id);

        $printer = getItemByTypeName('Printer', '_test_printer_ent0');
        $printers_id = $printer->fields['id'];
        $this->assertGreaterThan(0, $printers_id);

        // Add some ports
        $ports_id = $networkPort->add([
            'name'               => 'eth0',
            'logical_number'     => '1',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $computer->fields['id'],
            'itemtype'           => $computer->getTypeName(1),
            'mac'                => 'bc:97:e1:5c:0e:90',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        $ports_id = $networkPort->add([
            'name'               => 'eth1',
            'logical_number'     => '1',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $computer->fields['id'],
            'itemtype'           => $computer->getTypeName(1),
            'mac'                => 'bc:97:e1:5c:0e:91',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        $ports_id = $networkPort->add([
            'name'               => 'internal',
            'logical_number'     => '1',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $printer->fields['id'],
            'itemtype'           => $printer->getTypeName(1),
            'mac'                => '00:85:eb:f4:be:20',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // Make sure there are no hubs yet
        $this->assertSame(0, countElementsInTable($unmanaged->getTable()));

        // Import the switch into GLPI
        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($xml_source));

        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        $this->assertFalse($inventory->inError());
        $this->assertSame([], $inventory->getErrors());

        // Verify that gi0/3, eth0, internal ports are connected to a hub
        $this->assertSame(1, countElementsInTable($unmanaged->getTable()));
        $this->assertSame(3, countElementsInTable($networkPort_NetworkPort->getTable()));
        foreach (['gi0/3', 'eth0', 'internal'] as $port_name) {
            $this->assertTrue($networkPort->getFromDBByCrit(['name' => $port_name]));
            $this->assertTrue($networkPort->isHubConnected($networkPort->fields['id']));
        }
    }

    public function testSwitchMacConnection3()
    {
        $xml_source = '<?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <TYPE>NETWORKING</TYPE>
        <MANUFACTURER>Hewlett-Packard</MANUFACTURER>
        <MODEL>J9085A</MODEL>
        <DESCRIPTION>ProCurve J9085A</DESCRIPTION>
        <NAME>FR-SW01</NAME>
        <LOCATION>BAT A - Niv 3</LOCATION>
        <CONTACT>Admin</CONTACT>
        <SERIAL>CN536H7J</SERIAL>
        <FIRMWARE>R.10.06 R.11.60</FIRMWARE>
        <UPTIME>8 days, 01:48:57.95</UPTIME>
        <MAC>b4:39:d6:3a:7f:00</MAC>
        <ID>0</ID>
        <IPS>
          <IP>192.168.1.56</IP>
          <IP>192.168.10.56</IP>
        </IPS>
      </INFO>
      <PORTS>
        <PORT>
          <CONNECTIONS>
            <CONNECTION>
              <MAC>bc:97:e1:5c:0e:90</MAC>
            </CONNECTION>
          </CONNECTIONS>
          <IFDESCR>gi0/3</IFDESCR>
          <IFNAME>gi0/3</IFNAME>
          <IFNUMBER>3</IFNUMBER>
          <IFSPEED>1000000000</IFSPEED>
          <IFSTATUS>1</IFSTATUS>
          <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
          <IFPORTDUPLEX>2</IFPORTDUPLEX>
          <IFTYPE>6</IFTYPE>
          <MAC>b4:39:d6:3b:22:bd</MAC>
          <VLANS>
            <VLAN>
              <NAME>VLAN160</NAME>
              <NUMBER>160</NUMBER>
            </VLAN>
          </VLANS>
        </PORT>
      </PORTS>
    </DEVICE>
    <MODULEVERSION>3.0</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

        $networkPort             = new \NetworkPort();
        $unmanaged               = new \Unmanaged();
        $networkPort_NetworkPort = new \NetworkPort_NetworkPort();

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $computers_id = $computer->fields['id'];
        $this->assertGreaterThan(0, $computers_id);

        // Add some ports
        $ports_id = $networkPort->add([
            'name'               => 'eth0',
            'logical_number'     => '1',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $computer->fields['id'],
            'itemtype'           => $computer->getTypeName(1),
            'mac'                => 'bc:97:e1:5c:0e:90',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        $ports_id = $networkPort->add([
            'name'               => 'eth1',
            'logical_number'     => '1',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $computer->fields['id'],
            'itemtype'           => $computer->getTypeName(1),
            'mac'                => 'bc:97:e1:5c:0e:91',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // add a virtual port (logical_number=0) with the same mac as eth0
        $ports_id = $networkPort->add([
            'name'               => 'eth0:srv',
            'logical_number'     => '0',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $computer->fields['id'],
            'itemtype'           => $computer->getTypeName(1),
            'mac'                => 'bc:97:e1:5c:0e:90',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // Import the switch into GLPI
        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($xml_source));

        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        $this->assertFalse($inventory->inError());
        $this->assertSame([], $inventory->getErrors());

        // Make sure there are no hubs
        $this->assertSame(0, countElementsInTable($unmanaged->getTable()));

        // Verify that eth0 is the only port connected
        $this->assertSame(1, countElementsInTable($networkPort_NetworkPort->getTable()));
        $this->assertTrue($networkPort->getFromDBByCrit(['name' => 'eth0']));
        $this->assertTrue($networkPort_NetworkPort->getFromDBForNetworkPort($networkPort->fields['id']));
    }

    public function testSwitchMacConnection4()
    {
        $xml_source = '<?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <TYPE>NETWORKING</TYPE>
        <MANUFACTURER>Hewlett-Packard</MANUFACTURER>
        <MODEL>J9085A</MODEL>
        <DESCRIPTION>ProCurve J9085A</DESCRIPTION>
        <NAME>FR-SW01</NAME>
        <LOCATION>BAT A - Niv 3</LOCATION>
        <CONTACT>Admin</CONTACT>
        <SERIAL>CN536H7J</SERIAL>
        <FIRMWARE>R.10.06 R.11.60</FIRMWARE>
        <UPTIME>8 days, 01:48:57.95</UPTIME>
        <MAC>b4:39:d6:3a:7f:00</MAC>
        <ID>0</ID>
        <IPS>
          <IP>192.168.1.56</IP>
          <IP>192.168.10.56</IP>
        </IPS>
      </INFO>
      <PORTS>
        <PORT>
          <CONNECTIONS>
            <CONNECTION>
              <MAC>bc:97:e1:5c:0e:90</MAC>
              <MAC>fe:54:00:dd:1d:4f</MAC>
              <MAC>fe:54:00:c0:97:8a</MAC>
              <MAC>fe:54:00:ff:04:b5</MAC>
            </CONNECTION>
          </CONNECTIONS>
          <IFDESCR>gi0/3</IFDESCR>
          <IFNAME>gi0/3</IFNAME>
          <IFNUMBER>3</IFNUMBER>
          <IFSPEED>1000000000</IFSPEED>
          <IFSTATUS>1</IFSTATUS>
          <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
          <IFPORTDUPLEX>2</IFPORTDUPLEX>
          <IFTYPE>6</IFTYPE>
          <MAC>b4:39:d6:3b:22:bd</MAC>
          <VLANS>
            <VLAN>
              <NAME>VLAN160</NAME>
              <NUMBER>160</NUMBER>
            </VLAN>
          </VLANS>
        </PORT>
      </PORTS>
    </DEVICE>
    <MODULEVERSION>3.0</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

        $networkPort             = new \NetworkPort();
        $unmanaged               = new \Unmanaged();
        $networkPort_NetworkPort = new \NetworkPort_NetworkPort();

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $computers_id = $computer->fields['id'];
        $this->assertGreaterThan(0, $computers_id);

        // Add some ports
        $ports_id = $networkPort->add([
            'name'               => 'eth0',
            'logical_number'     => '1',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $computer->fields['id'],
            'itemtype'           => $computer->getTypeName(1),
            'mac'                => 'bc:97:e1:5c:0e:90',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        $ports_id = $networkPort->add([
            'name'               => 'eth1',
            'logical_number'     => '1',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $computer->fields['id'],
            'itemtype'           => $computer->getTypeName(1),
            'mac'                => 'bc:97:e1:5c:0e:91',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // add a virtual port (logical_number=0) with the same mac as eth0
        $ports_id = $networkPort->add([
            'name'               => 'ovs-bridge',
            'logical_number'     => '0',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $computer->fields['id'],
            'itemtype'           => $computer->getTypeName(1),
            'mac'                => 'bc:97:e1:5c:0e:90',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // add another virtual port
        $ports_id = $networkPort->add([
            'name'               => 'vnet13',
            'logical_number'     => '0',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $computer->fields['id'],
            'itemtype'           => $computer->getTypeName(1),
            'mac'                => 'fe:54:00:dd:1d:4f',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // add another virtual port
        $ports_id = $networkPort->add([
            'name'               => 'vnet15',
            'logical_number'     => '0',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $computer->fields['id'],
            'itemtype'           => $computer->getTypeName(1),
            'mac'                => 'fe:54:00:c0:97:8a',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // add another virtual port
        $ports_id = $networkPort->add([
            'name'               => 'vnet21',
            'logical_number'     => '0',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $computer->fields['id'],
            'itemtype'           => $computer->getTypeName(1),
            'mac'                => 'fe:54:00:ff:04:b5',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        // Import the switch into GLPI
        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($xml_source));

        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        $this->assertFalse($inventory->inError());
        $this->assertSame([], $inventory->getErrors());

        // Make sure there are no hubs
        $this->assertSame(0, countElementsInTable($unmanaged->getTable()));

        // Verify that eth0 is the only port connected
        $this->assertSame(1, countElementsInTable($networkPort_NetworkPort->getTable()));
        $this->assertTrue($networkPort->getFromDBByCrit(['name' => 'eth0']));
        $this->assertTrue($networkPort_NetworkPort->getFromDBForNetworkPort($networkPort->fields['id']));
    }

    public static function prepareConnectionsProvider()
    {
        return [
            ['json_source' =>
                '{
                  "content": {
                      "network_ports": [
                         {
                            "connections": [
                               {
                                  "ifdescr": "port47",
                                  "ifnumber": 1047,
                                  "sysdescr": "sw001",
                                  "sysmac": "2c:fa:a2:d1:b2:28"
                               }
                            ],
                            "ifnumber": 2,
                            "lldp": true
                         }
                      ]
                   }
                }'
            ],
            ['json_source' =>
                '{
                  "content": {
                      "network_ports": [
                         {
                            "connections": [
                               {
                                  "ifdescr": "port47",
                                  "mac": "2c:fa:a2:d1:b2:99",
                                  "sysdescr": "sw001",
                                  "sysmac": "2c:fa:a2:d1:b2:28"
                               }
                            ],
                            "ifnumber": 2,
                            "lldp": true
                         }
                      ]
                   }
                }'
            ],
            ['json_source' =>
                '{
                  "content": {
                      "network_ports": [
                         {
                            "connections": [
                               {
                                  "ifdescr": "port47",
                                  "sysdescr": "sw001",
                                  "sysmac": "2c:fa:a2:d1:b2:28"
                               }
                            ],
                            "ifnumber": 2,
                            "lldp": true
                         }
                      ]
                   }
                }'
            ],
        ];
    }

    /**
     * @dataProvider prepareConnectionsProvider
     */
    public function testPrepareConnections($json_source)
    {
        $networkEquipment = new \NetworkEquipment();
        $networkPort      = new \NetworkPort();

        $networkEquipments_id = $networkEquipment->add([
            'entities_id' => 0,
            'name'        => 'sw001',
        ]);
        $this->assertGreaterThan(0, $networkEquipments_id);

        $mngtports_id = $networkPort->add([
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            'instantiation_type' => 'NetworkPortAggregate',
            'name'               => 'management',
            'mac'                => '2c:fa:a2:d1:b2:28',
        ]);
        $this->assertGreaterThan(0, $mngtports_id);

        $ports_id = $networkPort->add([
            'name'               => 'port47',
            'logical_number'     => '1047',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            'ifdescr'            => '47',
            'mac'                => '2c:fa:a2:d1:b2:99',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        $json = json_decode($json_source);

        $networkEquipment = getItemByTypeName('NetworkEquipment', '_test_networkequipment_1');

        $asset = new \Glpi\Inventory\Asset\NetworkPort($networkEquipment, (array)$json->content->network_ports);

        $result = $asset->prepare();
        $this->assertCount(1, $result);

        $connections = $asset->getPart('connections');
        $this->assertCount(1, $connections);

        $networkPort = current(current($connections));
        $this->assertTrue(property_exists($networkPort, 'logical_number'));
        $this->assertSame(1047, $networkPort->logical_number);
    }

    public function testUnmanagedNotDuplicatedAtEachInventoryWithLogicalNumber()
    {
        $xml_source = '<?xml version="1.0" encoding="UTF-8" ?>
      <REQUEST>
        <CONTENT>
          <DEVICE>
            <COMPONENTS>
              <COMPONENT>
                <CONTAINEDININDEX>0</CONTAINEDININDEX>
                <INDEX>-1</INDEX>
                <NAME>Force10 S-series Stack</NAME>
                <TYPE>stack</TYPE>
              </COMPONENT>
            </COMPONENTS>
            <INFO>
              <MAC>00:01:e8:d7:c9:1d</MAC>
              <NAME>sw-s50</NAME>
              <SERIAL>DL253300100</SERIAL>
              <TYPE>NETWORKING</TYPE>
            </INFO>
            <PORTS>
              <PORT>
                <CONNECTIONS>
                  <CDP>1</CDP>
                  <CONNECTION>
                    <IFNUMBER>52</IFNUMBER>
                    <IP>10.100.200.10</IP>
                    <SYSDESCR>ExtremeXOS (X440G2-48p-10G4) version 31.7.1.4 31.7.1.4-patch1-77 by release-manager on Mon Nov 21 08:43:09 EST 2022</SYSDESCR>
                    <SYSMAC>00:04:96:f5:82:f5</SYSMAC>
                    <SYSNAME>SW_BATA-RdJ-vdi-1</SYSNAME>
                  </CONNECTION>
                </CONNECTIONS>
                <IFALIAS>BAT-A</IFALIAS>
                <IFDESCR>X670G2-48x-4q Port 1</IFDESCR>
                <IFINERRORS>0</IFINERRORS>
                <IFINOCTETS>2421130293</IFINOCTETS>
                <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
                <IFLASTCHANGE>0:01:51.00</IFLASTCHANGE>
                <IFMTU>1500</IFMTU>
                <IFNAME>1:1</IFNAME>
                <IFNUMBER>1001</IFNUMBER>
                <IFOUTERRORS>0</IFOUTERRORS>
                <IFOUTOCTETS>1619061805</IFOUTOCTETS>
                <IFPORTDUPLEX>3</IFPORTDUPLEX>
                <IFSPEED>10000000000</IFSPEED>
                <IFSTATUS>1</IFSTATUS>
                <IFTYPE>6</IFTYPE>
                <MAC>00:04:96:98:db:22</MAC>
              </PORT>
            </PORTS>
          </DEVICE>
          <MODULEVERSION>4.1</MODULEVERSION>
          <PROCESSNUMBER>1</PROCESSNUMBER>
        </CONTENT>
        <DEVICEID>foo</DEVICEID>
        <QUERY>SNMPQUERY</QUERY>
      </REQUEST>';

        //inventory
        $inventory = $this->doInventory($xml_source, true);

        $network_device_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $network_device_id);

        $unmanaged = new \Unmanaged();
        $this->assertTrue($unmanaged->getFromDBByCrit(['name' => 'SW_BATA-RdJ-vdi-1']));

        //redo inventory and check if we still have a single Unmanaged
        $inventory = $this->doInventory($xml_source, true);

        $unmanaged = new \Unmanaged();
        $this->assertTrue($unmanaged->getFromDBByCrit(['name' => 'SW_BATA-RdJ-vdi-1']));
    }


    public function testUnmanagedNotDuplicatedAtEachInventoryWithIfDescr()
    {
        $xml_source = '<?xml version="1.0" encoding="UTF-8" ?>
      <REQUEST>
        <CONTENT>
          <DEVICE>
            <COMPONENTS>
              <COMPONENT>
                <CONTAINEDININDEX>0</CONTAINEDININDEX>
                <INDEX>-1</INDEX>
                <NAME>Force10 S-series Stack</NAME>
                <TYPE>stack</TYPE>
              </COMPONENT>
            </COMPONENTS>
            <INFO>
              <MAC>00:01:e8:d7:c9:1d</MAC>
              <NAME>sw-s50</NAME>
              <SERIAL>DL253300100</SERIAL>
              <TYPE>NETWORKING</TYPE>
            </INFO>
            <PORTS>
            <PORT>
            <CONNECTIONS>
              <CDP>1</CDP>
              <CONNECTION>
                <IFDESCR>GigabitEthernet0</IFDESCR>
                <IP>10.2.32.239</IP>
                <MODEL>cisco AIR-AP2802E-E-K9</MODEL>
                <SYSDESCR>Cisco AP Software, ap3g3-k9w8 Version: 17.6.5.22
  Technical Support: http://www.cisco.com/techsupport
  Copyright (c) 2014-2015 by Cisco Systems, Inc.</SYSDESCR>
                <SYSNAME>FR-LUC-GCL-WAP-INTRA-239</SYSNAME>
              </CONNECTION>
            </CONNECTIONS>
            <IFALIAS>// Trunk to FR-LUC-GCL-WAP-INTRA-239 //</IFALIAS>
            <IFDESCR>GigabitEthernet1/0/20</IFDESCR>
            <IFINERRORS>0</IFINERRORS>
            <IFINOCTETS>2628580054</IFINOCTETS>
            <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
            <IFLASTCHANGE>245 days, 07:42:45.29</IFLASTCHANGE>
            <IFMTU>1500</IFMTU>
            <IFNAME>Gi1/0/20</IFNAME>
            <IFNUMBER>28</IFNUMBER>
            <IFOUTERRORS>0</IFOUTERRORS>
            <IFOUTOCTETS>2281895971</IFOUTOCTETS>
            <IFPORTDUPLEX>3</IFPORTDUPLEX>
            <IFSPEED>1000000000</IFSPEED>
            <IFSTATUS>1</IFSTATUS>
            <IFTYPE>6</IFTYPE>
            <MAC>c4:4d:84:13:5a:94</MAC>
            <TRUNK>1</TRUNK>
          </PORT>
            </PORTS>
          </DEVICE>
          <MODULEVERSION>4.1</MODULEVERSION>
          <PROCESSNUMBER>1</PROCESSNUMBER>
        </CONTENT>
        <DEVICEID>foo</DEVICEID>
        <QUERY>SNMPQUERY</QUERY>
      </REQUEST>';

        //inventory
        $inventory = $this->doInventory($xml_source, true);

        $network_device_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $network_device_id);

        $unmanaged = new \Unmanaged();
        $this->assertTrue($unmanaged->getFromDBByCrit(['name' => 'FR-LUC-GCL-WAP-INTRA-239']));

        //redo inventory and check if we still have a single Unmanaged
        $inventory = $this->doInventory($xml_source, true);

        $unmanaged = new \Unmanaged();
        $this->assertTrue($unmanaged->getFromDBByCrit(['name' => 'FR-LUC-GCL-WAP-INTRA-239']));
    }


    public function testAssetTag()
    {
        $xml_source = '<?xml version="1.0" encoding="UTF-8" ?>
      <REQUEST>
        <CONTENT>
          <DEVICE>
            <COMPONENTS>
              <COMPONENT>
                <CONTAINEDININDEX>0</CONTAINEDININDEX>
                <INDEX>-1</INDEX>
                <NAME>Force10 S-series Stack</NAME>
                <TYPE>stack</TYPE>
              </COMPONENT>
            </COMPONENTS>
            <INFO>
              <MAC>00:01:e8:d7:c9:1d</MAC>
              <NAME>sw-s50</NAME>
              <SERIAL>DL253300100</SERIAL>
              <ASSETTAG>other_serial</ASSETTAG>
              <TYPE>NETWORKING</TYPE>
            </INFO>
          </DEVICE>
          <MODULEVERSION>4.1</MODULEVERSION>
          <PROCESSNUMBER>1</PROCESSNUMBER>
        </CONTENT>
        <DEVICEID>foo</DEVICEID>
        <QUERY>SNMPQUERY</QUERY>
      </REQUEST>';

        //inventory
        $inventory = $this->doInventory($xml_source, true);

        $network_device_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $network_device_id);

        $networkEquipment = new \NetworkEquipment();
        $this->assertTrue($networkEquipment->getFromDB($network_device_id));

        $this->assertSame('other_serial', $networkEquipment->fields['otherserial']);
    }

    /**
     * Test stacked Dell S50 switch
     *
     */
    public function testStackedDellSwitchN3048P()
    {
        $xml_source = file_get_contents(FIXTURE_DIR . '/inventories/dell_n3048p.xml');
        // Import the switch(es) into GLPI
        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($xml_source));
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        if ($inventory->inError()) {
            foreach ($inventory->getErrors() as $error) {
                var_dump($error);
            }
        }
        $this->assertFalse($inventory->inError());
        $this->assertSame([], $inventory->getErrors());

        $networkEquipment = new \NetworkEquipment();
        $networkPort      = new \NetworkPort();

        $server_serial = [
            'GFH351FGH654FGH354FGH',
            'SDF15SD5F1SD56F1SD1FSD6F1',
            'SDFSDFSDF5414687897SDF',
            'SDFSDF564897DFG5CVB1FGH',
            'SDFSDF564894DFS5D1FSD5F1',
            'SDFDF546SD64SDF84SDF1',
        ];

        foreach ($server_serial as $serial) {
            $this->assertTrue(
                $networkEquipment->getFromDBByCrit(['serial' => $serial]),
                "Switch s/n $serial doesn't exist"
            );

            $found_np = $networkPort->find([
                'itemtype' => $networkEquipment->getType(),
                'items_id' => $networkEquipment->getID(),
            ]);
            //53 because XML have 312 port with iftype 6 -> 52 port
            //and one management port, so 53 port per switch
            $this->assertCount(53, $found_np, 'Must have 53 ports');
        }
    }

    public function testRuleMatchedLog()
    {
        $xml_source = '<?xml version="1.0" encoding="UTF-8"?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <FIRMWARES>
        <DESCRIPTION>device firmware</DESCRIPTION>
        <MANUFACTURER>Cisco</MANUFACTURER>
        <NAME>C9300-24P</NAME>
        <TYPE>device</TYPE>
        <VERSION></VERSION>
      </FIRMWARES>
      <INFO>
        <COMMENTS>Cisco IOS Software [Bengaluru], Catalyst L3 Switch Software (CAT9K_IOSXE), Version 17.6.5, RELEASE SOFTWARE (fc2)
Technical Support: http://www.cisco.com/techsupport
Copyright (c) 1986-2023 by Cisco Systems, Inc.
Compiled Wed 25-Jan-23 16:15 by mcpre</COMMENTS>
        <FIRMWARE>Bengaluru 17.06.05</FIRMWARE>
        <ID>1290</ID>
        <IPS>
          <IP>10.205.13.103</IP>
        </IPS>
        <LOCATION></LOCATION>
        <MAC>10:h3:dg:a8:18:10</MAC>
        <MANUFACTURER>Cisco</MANUFACTURER>
        <MODEL>C9300-24P</MODEL>
        <NAME>switch_import_test</NAME>
        <RAM>1286</RAM>
        <SERIAL>DFGKJ6545684SDF</SERIAL>
        <TYPE>NETWORKING</TYPE>
        <UPTIME>6 days, 01:18:18.70</UPTIME>
      </INFO>
      <PORTS>
        <PORT>
          <CONNECTIONS>
            <CONNECTION>
              <MAC>00:0b:84:09:c1:5e</MAC>
            </CONNECTION>
          </CONNECTIONS>
          <IFALIAS>unmanaged</IFALIAS>
          <IFDESCR>GigabitEthernet1/0/1</IFDESCR>
          <IFINERRORS>0</IFINERRORS>
          <IFINOCTETS>19636462</IFINOCTETS>
          <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
          <IFLASTCHANGE>16 minutes, 37.49</IFLASTCHANGE>
          <IFMTU>1500</IFMTU>
          <IFNAME>Gi1/0/1</IFNAME>
          <IFNUMBER>9</IFNUMBER>
          <IFOUTERRORS>0</IFOUTERRORS>
          <IFOUTOCTETS>285784231</IFOUTOCTETS>
          <IFPORTDUPLEX>3</IFPORTDUPLEX>
          <IFSPEED>100000000</IFSPEED>
          <IFSTATUS>1</IFSTATUS>
          <IFTYPE>6</IFTYPE>
          <MAC>08:f3:fb:a1:04:01</MAC>
          <TRUNK>0</TRUNK>
          <VLANS>
            <VLAN>
              <NAME>INFRA</NAME>
              <NUMBER>10</NUMBER>
            </VLAN>
          </VLANS>
        </PORT>
      </PORTS>
    </DEVICE>
    <MODULEVERSION>5.1</MODULEVERSION>
    <PROCESSNUMBER>51554</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>switch_import_test</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

        //inventory
        $inventory = $this->doInventory($xml_source, true);

        $network_device_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $network_device_id);

        $networkEquipment = new \NetworkEquipment();
        $this->assertTrue($networkEquipment->getFromDB($network_device_id));

        $this->assertSame('DFGKJ6545684SDF', $networkEquipment->fields['serial']);

        $unmanaged = new \Unmanaged();
        $found_unmanaged = $unmanaged->find();
        $this->assertCount(1, $found_unmanaged);

        $rulematchedLog = new \RuleMatchedLog();
        $found_rulematchedLog = $rulematchedLog->find(
            [
                'itemtype' => "Unmanaged",
                'items_id' => current($found_unmanaged)['id'],
            ]
        );
        $this->assertCount(1, $found_rulematchedLog);

        //redo inventory
        $inventory = $this->doInventory($xml_source, true);

        $unmanaged = new \Unmanaged();
        $found_unmanaged = $unmanaged->find();
        //get only one RuleMatchedLog
        $this->assertCount(1, $found_unmanaged);

        $rulematchedLog = new \RuleMatchedLog();
        $found_rulematchedLog = $rulematchedLog->find(
            [
                'itemtype' => "Unmanaged",
                'items_id' => current($found_unmanaged)['id'],
            ]
        );
        //get two RuleMatchedLog
        $this->assertCount(2, $found_rulematchedLog);
    }

    /**
     * Test stacked Cisco C9300 switch
     *
     */
    public function testStackedCiscoSwitchC9300()
    {
        $xml_source = file_get_contents(FIXTURE_DIR . '/inventories/cisco-C9300.xml');
        // Import the switch(es) into GLPI
        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($xml_source));
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        if ($inventory->inError()) {
            foreach ($inventory->getErrors() as $error) {
                var_dump($error);
            }
        }
        $this->assertFalse($inventory->inError());
        $this->assertSame([], $inventory->getErrors());

        $networkEquipment = new \NetworkEquipment();
        $networkPort      = new \NetworkPort();

        $server_serial = [
            'DKFJG3541DF' => 43, //42  Gi1/0/x + 1 -> Gi0/0
            'FOC2637YAPH' => 42  //42  Gi2/0/x
        ];

        foreach ($server_serial as $serial => $nb_port) {
            $this->assertTrue(
                $networkEquipment->getFromDBByCrit(['serial' => $serial]),
                "Switch s/n $serial doesn't exist"
            );

            $found_np = $networkPort->find([
                'itemtype' => $networkEquipment->getType(),
                'items_id' => $networkEquipment->getID(),
            ]);
            $this->assertCount($nb_port, $found_np, 'Must have ' . $nb_port . ' ports');
        }
    }
}
