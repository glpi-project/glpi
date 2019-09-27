<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace tests\units\Glpi\Inventory\Asset;

include_once __DIR__ . '/../../../../abstracts/AbstractInventoryAsset.php';

/* Test for inc/inventory/asset/networkequipment.class.php */

class NetworkEquipment extends AbstractInventoryAsset {

   protected function assetProvider() :array {
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
            'asset'  => '{"autoupdatesystems_id":"GLPI Native Inventory","comments":"Cisco NX-OS(tm) ucs, Software (ucs-6100-k9-system), Version 5.0(3)N2(4.02b), RELEASE SOFTWARE Copyright (c) 2002-2013 by Cisco Systems, Inc.   Compiled 1\\/16\\/2019 18:00:00","contact":"noc@glpi-project.org","cpu":4,"firmware":"5.0(3)N2(4.02b)","id":0,"location":"paris.pa3","mac":"8c:60:4f:8d:ae:fc","manufacturer":"Cisco","model":"UCS 6248UP 48-Port","name":"ucs6248up-cluster-pa3-B","serial":"SSI1912014B","type":"Networking","uptime":"482 days, 05:42:18.50","ips":["127.0.0.1","10.2.5.10","192.168.12.5"],"locations_id":"paris.pa3","networkequipmentmodels_id":"UCS 6248UP 48-Port","networkequipmenttypes_id":"Networking","manufacturers_id":"Cisco"}'
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
            'asset'  => '{"autoupdatesystems_id":"GLPI Native Inventory","comments":"Cisco IOS Software, C3750 Software (C3750-IPSERVICESK9-M), Version 12.2(55)SE6, RELEASE SOFTWARE (fc1)\\nTechnical Support: http:\\/\\/www.cisco.com\\/techsupport\\nCopyright (c) 1986-2012 by Cisco Systems, Inc.\\nCompiled Mon 23-Jul-12 13:22 by prod_rel_team","cpu":47,"firmware":"12.2(55)SE6","id":0,"ips":["10.1.0.100","10.1.0.22","10.1.0.41","10.1.0.45","10.1.0.59","10.11.11.1","10.11.11.5","10.11.13.1","10.11.13.5","172.21.0.1","172.21.0.7","172.22.0.1","172.22.0.5","172.23.0.1","172.23.0.5","172.24.0.1","172.24.0.5","172.25.1.15","172.28.200.1","172.28.200.5","172.28.211.5","172.28.215.1","172.28.221.1","185.10.253.65","185.10.253.97","185.10.254.1","185.10.255.146","185.10.255.224","185.10.255.250"],"location":"paris.pa3","mac":"00:23:ac:6a:01:00","manufacturer":"Cisco","model":"Catalyst 3750-24\\/48","name":"3k-1-pa3.teclib.infra","ram":128,"serial":"FOC1243W0ED","type":"Networking","uptime":"103 days, 13:53:28.28","locations_id":"paris.pa3","networkequipmentmodels_id":"Catalyst 3750-24\\/48","networkequipmenttypes_id":"Networking","manufacturers_id":"Cisco"}'
         ]
      ];
   }

   /**
    * @dataProvider assetProvider
    */
   public function testPrepare($xml, $asset) {
      $converter = new \Glpi\Inventory\Converter;
      $data = $converter->convert($xml);
      $json = json_decode($data);

      $netequ = new \NetworkEquipment();
      $main = new \Glpi\Inventory\Asset\NetworkEquipment($netequ, $json);
      $main->setExtraData((array)$json->content);
      $result = $main->prepare();
      $this->object($result[0])->isEqualTo(json_decode($asset));
   }

   public function testStacked() {
      //non stacked
      $json_str = file_get_contents(GLPI_ROOT . '/tests/fixtures/inventory/networkequipment_1.json');
      $json = json_decode($json_str);

      $netequ = new \NetworkEquipment();

      $data = (array)$json->content;
      $inventory = new \Glpi\Inventory\Inventory();
      $this->boolean($inventory->setData($json_str))->isTrue();

      $agent = new \Agent();
      $this->integer($agent->handleAgent($inventory->extractMetadata()))->isIdenticalTo(0);

      $main = new \Glpi\Inventory\Asset\NetworkEquipment($netequ, $json);
      $main->setAgent($agent)->setExtraData($data);
      $result = $main->prepare();
      $this->array($result)->hasSize(1);

      $this->boolean($main->isStackedSwitch())->isFalse();

      //stacked
      $json_str = file_get_contents(GLPI_ROOT . '/tests/fixtures/inventory/networkequipment_2.json');
      $json = json_decode($json_str);

      $netequ = new \NetworkEquipment();

      $data = (array)$json->content;
      $inventory = new \Glpi\Inventory\Inventory();
      $this->boolean($inventory->setData($json_str))->isTrue();

      $agent = new \Agent();
      $this->integer($agent->handleAgent($inventory->extractMetadata()))->isGreaterThan(0);

      $main = new \Glpi\Inventory\Asset\NetworkEquipment($netequ, $json);
      $main->setAgent($agent)->setExtraData($data);
      $result = $main->prepare();
      $this->array($result)->hasSize(5);

      $expected_stack = [
         1001 => [
            'containedinindex' => '1',
            'description' => 'WS-C3750G-48TS',
            'firmware' => '12.2(55)SE6',
            'fru' => 1,
            'index' => 1001,
            'model' => 'WS-C3750G-48TS-S',
            'name' => '1',
            'revision' => 'V04',
            'serial' => 'FOC1243W0ED',
            'type' => 'chassis',
            'version' => '12.2(55)SE6',
         ],
         2001 => [
            'containedinindex' => '1',
            'description' => 'WS-C3750G-48TS',
            'firmware' => '12.2(55)SE6',
            'fru' => 1,
            'index' => 2001,
            'model' => 'WS-C3750G-48TS-S',
            'name' => '2',
            'revision' => 'V04',
            'serial' => 'FOC1127Z4LH',
            'type' => 'chassis',
            'version' => '12.2(55)SE6',
         ],
         3001 => [
            'containedinindex' => '1',
            'description' => 'WS-C3750G-48TS',
            'firmware' => '12.2(55)SE6',
            'fru' => 1,
            'index' => 3001,
            'model' => 'WS-C3750G-48TS-S',
            'name' => '3',
            'revision' => 'V04',
            'serial' => 'FOC1232W0JH',
            'type' => 'chassis',
            'version' => '12.2(55)SE6',
         ],
         4001 => [
            'containedinindex' => '1',
            'description' => 'WS-C3750G-48TS',
            'firmware' => '12.2(55)SE6',
            'fru' => 1,
            'index' => 4001,
            'model' => 'WS-C3750G-48TS-S',
            'name' => '4',
            'revision' => 'V02',
            'serial' => 'FOC1033Y0M7',
            'type' => 'chassis',
            'version' => '12.2(55)SE6',
         ],
         8001 => [
            'containedinindex' => '1',
            'description' => 'WS-C3750G-48TS',
            'firmware' => '12.2(55)SE6',
            'fru' => 1,
            'index' => 8001,
            'model' => 'WS-C3750G-48TS-S',
            'name' => '8',
            'revision' => '02',
            'serial' => 'FOC0929U1SR',
            'type' => 'chassis',
            'version' => '12.2(55)SE6',
         ]
      ];

      $this->boolean($main->isStackedSwitch())->isTrue();
      $stack = $main->getStackedSwitches();
      $this->array(array_keys($expected_stack))->isIdenticalTo(array_keys($stack));
      foreach ($expected_stack as $key => $entry) {
         $this->array($entry)->isIdenticalTo((array)$stack[$key]);
      }
   }

   public function testHandle() {
      $json_str = file_get_contents(GLPI_ROOT . '/tests/fixtures/inventory/networkequipment_2.json');
      $json = json_decode($json_str);

      $netequ = new \NetworkEquipment();

      $data = (array)$json->content;
      $inventory = new \Glpi\Inventory\Inventory();
      $this->boolean($inventory->setData($json_str))->isTrue();

      $agent = new \Agent();
      $this->integer($agent->handleAgent($inventory->extractMetadata()))->isGreaterThan(0);

      $main = new \Glpi\Inventory\Asset\NetworkEquipment($netequ, $json);
      $main->setAgent($agent)->setExtraData($data);
      $result = $main->prepare();
      $this->array($result)->hasSize(5);

      $this->boolean($main->isStackedSwitch())->isTrue();

      $main->handle();
      $this->boolean($main->areLinksHandled())->isTrue();
   }
}
