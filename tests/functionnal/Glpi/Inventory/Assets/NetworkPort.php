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

/* Test for inc/inventory/asset/processos.class.php */

class NetworkPort extends AbstractInventoryAsset {

   protected function assetProvider() :array {
      return [
         [
            'xml'    => "<?xml version=\"1.0\"?>
<REQUEST>
<CONTENT>
   <DEVICE>
      <INFO>
        <COMMENTS>ProCurve J9085A</COMMENTS>
        <FIRMWARE>R.10.06 R.11.60</FIRMWARE>
        <ID>123</ID>
        <IPS>
          <IP>192.168.1.56</IP>
          <IP>192.168.10.56</IP>
        </IPS>
        <LOCATION>BAT A - Niv 3</LOCATION>
        <MAC>b4:39:d6:3a:7f:00</MAC>
        <MODEL>J9085A</MODEL>
        <NAME>FR-SW01</NAME>
        <SERIAL>CN536H7J</SERIAL>
        <TYPE>NETWORKING</TYPE>
        <UPTIME>8 days, 01:48:57.95</UPTIME>
      </INFO>
      <PORTS>
        <PORT>
          <CONNECTIONS>
            <CONNECTION>
              <MAC>00:40:9d:3b:7f:c4</MAC>
            </CONNECTION>
          </CONNECTIONS>
          <IFDESCR>3</IFDESCR>
          <IFNAME>3</IFNAME>
          <IFNUMBER>3</IFNUMBER>
          <IFSTATUS>1</IFSTATUS>
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
</CONTENT>
</REQUEST>",
            'ports'        => ['{"ifdescr":"3","ifname":"3","ifnumber":3,"ifstatus":1,"iftype":6,"trunk":false,"mac":"b4:39:d6:3b:22:bd","instantiation_type":"NetworkPortEthernet","ipaddress":[],"name":"3","logical_number":3}'],
            'connections'  => '{"3":["00:40:9d:3b:7f:c4"]}',
            'vlans'        => '{"3":[{"name":"VLAN160","number":"160","tag":"160"}]}',
            'aggregates'   => '[]'
         ], [
            'xml'          => "<?xml version=\"1.0\"?>
<REQUEST>
<CONTENT>
   <DEVICE>
      <INFO>
        <COMMENTS>ProCurve J9085A</COMMENTS>
        <FIRMWARE>R.10.06 R.11.60</FIRMWARE>
        <ID>123</ID>
        <IPS>
          <IP>192.168.1.56</IP>
          <IP>192.168.10.56</IP>
        </IPS>
        <LOCATION>BAT A - Niv 3</LOCATION>
        <MAC>b4:39:d6:3a:7f:00</MAC>
        <MODEL>J9085A</MODEL>
        <NAME>FR-SW01</NAME>
        <SERIAL>CN536H7J</SERIAL>
        <TYPE>NETWORKING</TYPE>
        <UPTIME>8 days, 01:48:57.95</UPTIME>
      </INFO>
      <PORTS>
         <PORT>
           <AGGREGATE>
             <PORT>10001</PORT>
             <PORT>10002</PORT>
           </AGGREGATE>
           <IFDESCR>Port-channel10</IFDESCR>
           <IFINERRORS>0</IFINERRORS>
           <IFINOCTETS>1076823325</IFINOCTETS>
           <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
           <IFLASTCHANGE>15 days, 23:20:53.24</IFLASTCHANGE>
           <IFMTU>1500</IFMTU>
           <IFNAME>Po10</IFNAME>
           <IFNUMBER>5010</IFNUMBER>
           <IFOUTERRORS>0</IFOUTERRORS>
           <IFOUTOCTETS>2179528910</IFOUTOCTETS>
           <IFSPEED>4294967295</IFSPEED>
           <IFSTATUS>1</IFSTATUS>
           <IFTYPE>56</IFTYPE>
           <MAC>c4:64:13:8c:7e:b5</MAC>
         </PORT>
         <PORT>
           <IFDESCR>Agg one</IFDESCR>
           <IFINERRORS>0</IFINERRORS>
           <IFINOCTETS>0</IFINOCTETS>
           <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
           <IFLASTCHANGE>18 days, 23:20:53.24</IFLASTCHANGE>
           <IFMTU>1500</IFMTU>
           <IFNAME>Agg01</IFNAME>
           <IFNUMBER>10001</IFNUMBER>
           <IFOUTERRORS>0</IFOUTERRORS>
           <IFOUTOCTETS>0</IFOUTOCTETS>
           <IFSPEED>4294967295</IFSPEED>
           <IFSTATUS>1</IFSTATUS>
           <IFTYPE>6</IFTYPE>
           <MAC>c4:64:13:8c:7e:b6</MAC>
         </PORT>
         <PORT>
           <IFDESCR>Agg two</IFDESCR>
           <IFINERRORS>0</IFINERRORS>
           <IFINOCTETS>0</IFINOCTETS>
           <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
           <IFLASTCHANGE>17 days, 23:20:53.24</IFLASTCHANGE>
           <IFMTU>1500</IFMTU>
           <IFNAME>Agg02</IFNAME>
           <IFNUMBER>10002</IFNUMBER>
           <IFOUTERRORS>0</IFOUTERRORS>
           <IFOUTOCTETS>0</IFOUTOCTETS>
           <IFSPEED>4294967295</IFSPEED>
           <IFSTATUS>1</IFSTATUS>
           <IFTYPE>6</IFTYPE>
           <MAC>c4:64:13:8c:7e:b7</MAC>
         </PORT>
      </PORTS>
   </DEVICE>
</CONTENT>
</REQUEST>",
            'ports'         => [
               '{"ifdescr":"Port-channel10","ifinerrors":0,"ifinternalstatus":1,"iflastchange":"15 days, 23:20:53.24","ifmtu":1500,"ifname":"Po10","ifnumber":5010,"ifouterrors":0,"ifspeed":4294967295,"ifstatus":1,"iftype":56,"trunk":false,"mac":"c4:64:13:8c:7e:b5","instantiation_type":"NetworkPortEthernet","ipaddress":[],"name":"Po10","logical_number":5010,"ifinbytes":1076823325,"ifoutbytes":2179528910}',
               '{"ifdescr":"Agg one","ifinerrors":0,"ifinternalstatus":1,"iflastchange":"18 days, 23:20:53.24","ifmtu":1500,"ifname":"Agg01","ifnumber":10001,"ifouterrors":0,"ifspeed":4294967295,"ifstatus":1,"iftype":6,"trunk":false,"mac":"c4:64:13:8c:7e:b6","instantiation_type":"NetworkPortEthernet","ipaddress":[],"name":"Agg01","logical_number":10001,"ifinbytes":0,"ifoutbytes":0}',
               '{"ifdescr":"Agg two","ifinerrors":0,"ifinternalstatus":1,"iflastchange":"17 days, 23:20:53.24","ifmtu":1500,"ifname":"Agg02","ifnumber":10002,"ifouterrors":0,"ifspeed":4294967295,"ifstatus":1,"iftype":6,"trunk":false,"mac":"c4:64:13:8c:7e:b7","instantiation_type":"NetworkPortEthernet","ipaddress":[],"name":"Agg02","logical_number":10002,"ifinbytes":0,"ifoutbytes":0}'
            ],
            'connections'  => '',
            'vlans'        => '',
            'aggregates'   => '{"5010":{"aggregates":{"10001":0,"10002":0}}}'
         ], [
            'xml'          => "<?xml version=\"1.0\"?>
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
        </IPS>
        <LOCATION>paris</LOCATION>
        <MAC>00:23:ac:6a:01:00</MAC>
        <MANUFACTURER>Cisco</MANUFACTURER>
        <MODEL>Catalyst 3750-24/48</MODEL>
        <NAME>3k-1-pa.glpi-project.org</NAME>
        <RAM>128</RAM>
        <SERIAL>FOC1243W0ED</SERIAL>
        <TYPE>NETWORKING</TYPE>
        <UPTIME>103 days, 13:53:28.28</UPTIME>
      </INFO>
      <PORTS>
        <PORT>
          <AGGREGATE>
            <PORT>11646</PORT>
            <PORT>13645</PORT>
          </AGGREGATE>
          <CONNECTIONS>
            <CONNECTION>
              <MAC>00:14:4f:f2:b7:fd</MAC>
              <MAC>00:14:4f:f2:b7:fe</MAC>
            </CONNECTION>
          </CONNECTIONS>
          <IFALIAS>lag1.glpi-project.org</IFALIAS>
          <IFDESCR>Port-channel6</IFDESCR>
          <IFINERRORS>0</IFINERRORS>
          <IFINOCTETS>82823274</IFINOCTETS>
          <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
          <IFLASTCHANGE>32.65 seconds</IFLASTCHANGE>
          <IFMTU>9000</IFMTU>
          <IFNAME>Po6</IFNAME>
          <IFNUMBER>5006</IFNUMBER>
          <IFOUTERRORS>0</IFOUTERRORS>
          <IFOUTOCTETS>657139488</IFOUTOCTETS>
          <IFSPEED>2000000000</IFSPEED>
          <IFSTATUS>1</IFSTATUS>
          <IFTYPE>56</IFTYPE>
          <MAC>00:14:a9:37:ee:2d</MAC>
          <TRUNK>0</TRUNK>
          <VLANS>
            <VLAN>
              <NAME>san-replication</NAME>
              <NUMBER>206</NUMBER>
            </VLAN>
          </VLANS>
        </PORT>
        <PORT>
          <IFALIAS>net1.glpi-project.org</IFALIAS>
          <IFDESCR>GigabitEthernet4/0/46</IFDESCR>
          <IFINERRORS>0</IFINERRORS>
          <IFINOCTETS>38145664</IFINOCTETS>
          <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
          <IFLASTCHANGE>5 minutes, 50.26</IFLASTCHANGE>
          <IFMTU>9000</IFMTU>
          <IFNAME>Gi4/0/46</IFNAME>
          <IFNUMBER>11646</IFNUMBER>
          <IFOUTERRORS>0</IFOUTERRORS>
          <IFOUTOCTETS>167335360</IFOUTOCTETS>
          <IFPORTDUPLEX>3</IFPORTDUPLEX>
          <IFSPEED>1000000000</IFSPEED>
          <IFSTATUS>1</IFSTATUS>
          <IFTYPE>6</IFTYPE>
          <MAC>00:19:2f:08:8f:2e</MAC>
          <TRUNK>0</TRUNK>
          <VLANS>
            <VLAN>
              <NAME>san-replication</NAME>
              <NUMBER>206</NUMBER>
            </VLAN>
          </VLANS>
        </PORT>
        <PORT>
          <IFALIAS>net2.glpi-project.org</IFALIAS>
          <IFDESCR>GigabitEthernet8/0/45</IFDESCR>
          <IFINERRORS>0</IFINERRORS>
          <IFINOCTETS>40326934</IFINOCTETS>
          <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
          <IFLASTCHANGE>30.69 seconds</IFLASTCHANGE>
          <IFMTU>9000</IFMTU>
          <IFNAME>Gi8/0/45</IFNAME>
          <IFNUMBER>13645</IFNUMBER>
          <IFOUTERRORS>0</IFOUTERRORS>
          <IFOUTOCTETS>454911808</IFOUTOCTETS>
          <IFPORTDUPLEX>3</IFPORTDUPLEX>
          <IFSPEED>1000000000</IFSPEED>
          <IFSTATUS>1</IFSTATUS>
          <IFTYPE>6</IFTYPE>
          <MAC>00:14:a9:37:ee:2d</MAC>
          <TRUNK>0</TRUNK>
          <VLANS>
            <VLAN>
              <NAME>san-replication</NAME>
              <NUMBER>206</NUMBER>
            </VLAN>
          </VLANS>
        </PORT>
     </PORTS>
   </DEVICE>
</CONTENT>
</REQUEST>",
            'ports'  => [
               '{"ifalias":"lag1.glpi-project.org","ifdescr":"Port-channel6","ifinerrors":0,"ifinternalstatus":1,"iflastchange":"32.65 seconds","ifmtu":9000,"ifname":"Po6","ifnumber":5006,"ifouterrors":0,"ifspeed":2000000000,"ifstatus":1,"iftype":56,"mac":"00:14:a9:37:ee:2d","trunk":false,"instantiation_type":"NetworkPortEthernet","ipaddress":[],"name":"Po6","logical_number":5006,"ifinbytes":82823274,"ifoutbytes":657139488}',
               '{"ifalias":"net1.glpi-project.org","ifdescr":"GigabitEthernet4\/0\/46","ifinerrors":0,"ifinternalstatus":1,"iflastchange":"5 minutes, 50.26","ifmtu":9000,"ifname":"Gi4\/0\/46","ifnumber":11646,"ifouterrors":0,"ifportduplex":3,"ifspeed":1000000000,"ifstatus":1,"iftype":6,"mac":"00:19:2f:08:8f:2e","trunk":false,"instantiation_type":"NetworkPortEthernet","ipaddress":[],"name":"Gi4\/0\/46","logical_number":11646,"portduplex":3,"ifinbytes":38145664,"ifoutbytes":167335360}',
               '{"ifalias":"net2.glpi-project.org","ifdescr":"GigabitEthernet8\/0\/45","ifinerrors":0,"ifinternalstatus":1,"iflastchange":"30.69 seconds","ifmtu":9000,"ifname":"Gi8\/0\/45","ifnumber":13645,"ifouterrors":0,"ifportduplex":3,"ifspeed":1000000000,"ifstatus":1,"iftype":6,"mac":"00:14:a9:37:ee:2d","trunk":false,"instantiation_type":"NetworkPortEthernet","ipaddress":[],"name":"Gi8\/0\/45","logical_number":13645,"portduplex":3,"ifinbytes":40326934,"ifoutbytes":454911808}'
            ],
            'connections'  => '{"5006":["00:14:4f:f2:b7:fd","00:14:4f:f2:b7:fe"]}',
            'vlans'        => '{"5006":[{"name":"san-replication","number":"206","tag":"206"}],"11646":[{"name":"san-replication","number":"206","tag":"206"}],"13645":[{"name":"san-replication","number":"206","tag":"206"}]}',
            'aggregates'   => '{"5006":{"aggregates":{"11646":0,"13645":0}}}'
         ]
      ];
   }

   /**
    * @dataProvider assetProvider
    */
   public function testPrepare($xml, $ports, $connections, $vlans, $aggregates) {
      $this->login();
      $converter = new \Glpi\Inventory\Converter;
      $data = $converter->convert($xml);
      $json = json_decode($data);

      $neteq = getItemByTypeName('NetworkEquipment', 'My network equipment');
      if ($neteq === false) {
         $neteq = new \NetworkEquipment();
         $this->integer(
            $neteq->add([
               'name'   => 'My network equipment',
               'entities_id'  => 0
            ])
         )->isGreaterThan(0);
      }

      $asset = new \Glpi\Inventory\Asset\NetworkPort($neteq, $json->content->network_ports);
      $asset->setExtraData((array)$json->content);
      $results = $asset->prepare();

      foreach ($results as $key => $result) {
         $this->object($result)->isEqualTo(json_decode($ports[$key]), json_encode($result));
      }
      $this->array($asset->getPart('connections'))->isEqualTo((array)json_decode($connections), json_encode($asset->getPart('connections')));
      $this->array($asset->getPart('vlans'))->isEqualTo((array)json_decode($vlans), json_encode($asset->getPart('vlans')));
      $this->array($asset->getPart('aggregates'))->isEqualTo(json_decode($aggregates, true), json_encode($asset->getPart('aggregates')));
   }
}
