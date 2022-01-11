<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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
        $data = $converter->convert($net_xml_source);
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
        $source = $converter->convert($comp_xml_source);

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
}
