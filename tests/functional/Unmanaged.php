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

/* Test for inc/unmanaged.class.php */

class Unmanaged extends DbTestCase
{
    /**
     * When a switch is inventoried with an unknown linked mac address that belongs in reality to a computer,
     * it creates an Unmanaged device.
     * When computer is inventoried, the Unmanaged device must be replaced with the Computer, on a MAC match,
     * connecting the Computer to the switch.
     *
     * @return void
     */
    public function testUnmanagedToManaged()
    {
        global $CFG_GLPI;

        $this->login();

        $net_xml_source = '<?xml version="1.0" encoding="UTF-8" ?>
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
        </PORT>
      </PORTS>
    </DEVICE>
    <MODULEVERSION>3.0</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

        $networkEquipment = new \NetworkEquipment();
        $networkequipments_id = $networkEquipment->add([
            'serial'      => 'FOC147UJEU4',
            'entities_id' => 0
        ]);
        $this->integer($networkequipments_id)->isGreaterThan(0);

        $converter = new \Glpi\Inventory\Converter();
        $data = json_decode($converter->convert($net_xml_source));
        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($data);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        if ($inventory->inError()) {
            foreach ($inventory->getErrors() as $error) {
                var_dump($error);
            }
        }
        $this->boolean($inventory->inError())->isFalse();
        $this->array($inventory->getErrors())->isIdenticalTo([]);

        $this->integer(count($networkEquipment->find(['NOT' => ['name' => ['LIKE', '_test_%']]])))->isIdenticalTo(1);

        //inventory computer
        $comp_xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <HARDWARE>
      <NAME>pc1</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>XB63J7D</SSN>
    </BIOS>
    <NETWORKS>
      <DESCRIPTION>enp0s25</DESCRIPTION>
      <DRIVER>e1000e</DRIVER>
      <IPADDRESS>192.168.1.103</IPADDRESS>
      <IPGATEWAY>192.168.1.1</IPGATEWAY>
      <IPMASK>255.255.255.0</IPMASK>
      <IPSUBNET>192.168.1.0</IPSUBNET>
      <MACADDR>cc:f9:54:a1:03:35</MACADDR>
      <PCIID>8086:1559:1179:0001</PCIID>
      <PCISLOT>0000:00:19.0</PCISLOT>
      <SPEED>100</SPEED>
      <STATUS>Up</STATUS>
      <TYPE>ethernet</TYPE>
      <VIRTUALDEV>0</VIRTUALDEV>
    </NETWORKS>
    <VERSIONCLIENT>FusionInventory-Agent_v2.3.19</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>pc-2013-02-13</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

        $converter = new \Glpi\Inventory\Converter();
        $source = json_decode($converter->convert($comp_xml_source));

        $CFG_GLPI["is_contact_autoupdate"] = 0;
        $inventory = new \Glpi\Inventory\Inventory($source);
        $CFG_GLPI["is_contact_autoupdate"] = 1; //reset to default

        if ($inventory->inError()) {
            foreach ($inventory->getErrors() as $error) {
                var_dump($error);
            }
        }
        $this->boolean($inventory->inError())->isFalse();
        $this->array($inventory->getErrors())->isIdenticalTo([]);

        $networkPort = new \NetworkPort();
        $networkports = $networkPort->find(['mac' => 'cc:f9:54:a1:03:35']);

        $networkports_id = current($networkports)['id'];

        $computer         = new \Computer();
        $computers_id = $computer->add([
            'serial'      => 'XB63J7D',
            'entities_id' => 0
        ]);

        $networkports = $networkPort->find(['mac' => 'cc:f9:54:a1:03:35']);

        $this->integer(count($networkports))->isIdenticalTo(
            1,
            "The MAC address cc:f9:54:a1:03:35 must be tied to only one port"
        );

        $a_networkport = current($networkports);
        $this->integer($a_networkport['id'])->isIdenticalTo(
            $networkports_id,
            'The networkport ID is not the same between the unknown device and the computer'
        );

        $this->string($a_networkport['itemtype'])->isIdenticalTo('Computer');
    }

    /**
     * Convert an Unmanaged device into a NetworkEquipment
     * @return void
     */
    public function testConvert()
    {
        //Add Unmanaged network equipment, its network ports, name and IP.
        $unmanaged = new \Unmanaged();
        $unmanageds_id = $unmanaged->add([
            'name'        => 'switch',
            'entities_id' => 0,
            'itemtype' => 'NetworkEquipment',
            'sysdescr' => 'Any Cisco equipment',
            'locations_id' => 0,
            'is_dynamic' => 1,
            'serial' => 'XXS6BEF3',
            'comment' => 'with a comment'
        ]);
        $this->integer($unmanageds_id)->isGreaterThan(0);

        // * Add networkport
        $netport = new \NetworkPort();
        $networkports_id = $netport->add([
            'itemtype' => $unmanaged->getType(),
            'items_id' => $unmanageds_id,
            'instantiation_type' => 'NetworkPortEthernet',
            'name' => 'general',
            'mac' => '00:00:00:43:ae:0f',
            'is_dynamic' => 1
        ]);
        $this->integer($networkports_id)->isGreaterThan(0);

        $netname = new \NetworkName();
        $networknames_id = $netname->add([
            'itemtype' => $netport->getType(),
            'items_id' => $networkports_id,
            'name' => '',
            'is_dynamic' => 1
        ]);
        $this->integer($networknames_id)->isGreaterThan(0);

        $ip = new \IPAddress();
        $this->integer(
            $ip->add([
                'entities_id' => 0,
                'itemtype' => $netname->getType(),
                'items_id' => $networknames_id,
                'name' => '192.168.20.1',
                'is_dynamic' => 1
            ])
        )->isGreaterThan(0);

        //convert to NetworkEquipment
        $unmanaged->convert($unmanageds_id);

        $this->integer(
            countElementsInTable(\NetworkEquipment::getTable(), ['NOT' => ['name' => ['LIKE', '_test_%']]]),
            'No NetworkEquipment added'
        )->isIdenticalTo(1);

        $this->integer(
            countElementsInTable(\Unmanaged::getTable(), ['NOT' => ['name' => ['LIKE', '_test_%']]]),
            'Unmanaged has not been deleted'
        )->isIdenticalTo(0);

        $neteq = new \NetworkEquipment();
        $this->boolean($neteq->getFromDBByCrit(['name' => 'switch']))->isTrue();

        $this->string($neteq->fields['serial'])->isIdenticalTo('XXS6BEF3');
        $this->integer($neteq->fields['is_dynamic'])->isIdenticalTo(1);
        $this->string($neteq->fields['comment'])->isIdenticalTo('with a comment');
        $this->string($neteq->fields['sysdescr'])->isIdenticalTo('Any Cisco equipment');

        $netport->getFromDBByCrit([]);
        unset($netport->fields['date_mod']);
        unset($netport->fields['date_creation']);
        $networkports_id = $netport->fields['id'];
        unset($netport->fields['id']);
        $expected = [
            'name'                 => 'general',
            'items_id'             => $neteq->fields['id'],
            'itemtype'             => 'NetworkEquipment',
            'entities_id'          => 0,
            'is_recursive'         => 0,
            'logical_number'       => 0,
            'instantiation_type'   => 'NetworkPortEthernet',
            'mac'                  => '00:00:00:43:ae:0f',
            'comment'              => null,
            'is_deleted'           => 0,
            'is_dynamic'           => 1,
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
        $this->array($netport->fields)->isEqualTo($expected);

        $netname->getFromDBByCrit(['items_id' => $networkports_id]);
        unset($netname->fields['date_mod']);
        unset($netname->fields['date_creation']);
        $networknames_id = $netname->fields['id'];
        unset($netname->fields['id']);
        $expected = [
            'entities_id' => 0,
            'items_id'    => $networkports_id,
            'itemtype'    => 'NetworkPort',
            'name'        => '',
            'comment'     => null,
            'fqdns_id'    => 0,
            'ipnetworks_id' => 0,
            'is_deleted'  => 0,
            'is_dynamic'  => 1,
        ];
        $this->array($netname->fields)->isEqualTo($expected);

        $ip->getFromDBByCrit(['name' => '192.168.20.1']);
        $expected = [
            'name'        => '192.168.20.1',
            'entities_id' => 0,
            'items_id'    => $networknames_id,
            'itemtype'    => 'NetworkName',
            'version'     => 4,
            'binary_0'    => 0,
            'binary_1'    => 0,
            'binary_2'    => 65535,
            'binary_3'    => 3232240641,
            'is_deleted'  => 0,
            'is_dynamic'  => 1,
            'mainitems_id'  => $neteq->fields['id'],
            'mainitemtype'  => 'NetworkEquipment'
        ];
        unset($ip->fields['id']);
        $this->array($ip->fields)->isEqualTo($expected);
    }
}
