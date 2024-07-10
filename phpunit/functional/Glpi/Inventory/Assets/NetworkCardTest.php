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

/* Test for inc/inventory/asset/networkcard.class.php */

class NetworkCardTest extends AbstractInventoryAsset
{
    public static function assetProvider(): array
    {
        return [
            [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <NETWORKS>
      <DESCRIPTION>lo</DESCRIPTION>
      <IPADDRESS>127.0.0.1</IPADDRESS>
      <IPMASK>255.0.0.0</IPMASK>
      <IPSUBNET>127.0.0.0</IPSUBNET>
      <MACADDR>00:00:00:00:00:00</MACADDR>
      <STATUS>Up</STATUS>
      <TYPE>loopback</TYPE>
      <VIRTUALDEV>1</VIRTUALDEV>
    </NETWORKS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"description": "lo", "ipaddress": "127.0.0.1", "ipmask": "255.0.0.0", "ipsubnet": "127.0.0.0", "status": "up", "type": "loopback", "virtualdev": true, "mac": "00:00:00:00:00:00"}',
                'virtual'   => true
            ], [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <NETWORKS>
      <DESCRIPTION>lo</DESCRIPTION>
      <IPADDRESS6>::1</IPADDRESS6>
      <IPMASK6>fff0::</IPMASK6>
      <IPSUBNET6>::</IPSUBNET6>
      <MACADDR>00:00:00:00:00:00</MACADDR>
      <STATUS>Up</STATUS>
      <TYPE>loopback</TYPE>
      <VIRTUALDEV>1</VIRTUALDEV>
    </NETWORKS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"description": "lo", "ipaddress6": "::1", "ipmask6": "fff0::", "ipsubnet6": "::", "status": "up", "type": "loopback", "virtualdev": true, "mac": "00:00:00:00:00:00"}',
                'virtual'   => true
            ], [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <NETWORKS>
      <DESCRIPTION>wlp58s0</DESCRIPTION>
      <DRIVER>iwlwifi</DRIVER>
      <IPADDRESS>192.168.1.119</IPADDRESS>
      <IPGATEWAY>192.168.1.1</IPGATEWAY>
      <IPMASK>255.255.255.0</IPMASK>
      <IPSUBNET>192.168.1.0</IPSUBNET>
      <MACADDR>44:85:00:2b:90:bc</MACADDR>
      <PCIID>8086:24F3:8086:0050</PCIID>
      <PCISLOT>0000:3a:00.0</PCISLOT>
      <STATUS>Up</STATUS>
      <TYPE>wifi</TYPE>
      <VIRTUALDEV>0</VIRTUALDEV>
      <WIFI_BSSID>58:6D:8F:C2:19:BF</WIFI_BSSID>
      <WIFI_MODE>Managed</WIFI_MODE>
      <WIFI_SSID>teclib</WIFI_SSID>
      <WIFI_VERSION>802.11</WIFI_VERSION>
    </NETWORKS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"description": "wlp58s0", "driver": "iwlwifi", "ipaddress": "192.168.1.119", "ipgateway": "192.168.1.1", "ipmask": "255.255.255.0", "ipsubnet": "192.168.1.0", "pciid": "8086:24F3:8086:0050", "pcislot": "0000:3a:00.0", "status": "up", "type": "wifi", "virtualdev": false, "wifi_bssid": "58:6D:8F:C2:19:BF", "wifi_mode": "Managed", "wifi_ssid": "teclib", "wifi_version": "802.11", "mac": "44:85:00:2b:90:bc"}',
                'virtual'   => false
            ], [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <NETWORKS>
      <DESCRIPTION>wlp58s0</DESCRIPTION>
      <DRIVER>iwlwifi</DRIVER>
      <IPADDRESS6>fe80::92a4:26c6:99dd:2d60</IPADDRESS6>
      <IPMASK6>ffff:ffff:ffff:ffff::</IPMASK6>
      <IPSUBNET6>fe80::</IPSUBNET6>
      <MACADDR>44:85:00:2b:90:bc</MACADDR>
      <PCIID>8086:24F3:8086:0050</PCIID>
      <PCISLOT>0000:3a:00.0</PCISLOT>
      <STATUS>Up</STATUS>
      <TYPE>wifi</TYPE>
      <VIRTUALDEV>0</VIRTUALDEV>
      <WIFI_BSSID>58:6D:8F:C2:19:BF</WIFI_BSSID>
      <WIFI_MODE>Managed</WIFI_MODE>
      <WIFI_SSID>teclib</WIFI_SSID>
      <WIFI_VERSION>802.11</WIFI_VERSION>
    </NETWORKS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"description": "wlp58s0", "driver": "iwlwifi", "ipaddress6": "fe80::92a4:26c6:99dd:2d60", "ipmask6": "ffff:ffff:ffff:ffff::", "ipsubnet6": "fe80::", "pciid": "8086:24F3:8086:0050", "pcislot": "0000:3a:00.0", "status": "up", "type": "wifi", "virtualdev": false, "wifi_bssid": "58:6D:8F:C2:19:BF", "wifi_mode": "Managed", "wifi_ssid": "teclib", "wifi_version": "802.11", "mac": "44:85:00:2b:90:bc"}',
                'virtual'   => false
            ], [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <NETWORKS>
      <DESCRIPTION>virbr0</DESCRIPTION>
      <IPADDRESS>192.168.122.1</IPADDRESS>
      <IPMASK>255.255.255.0</IPMASK>
      <IPSUBNET>192.168.122.0</IPSUBNET>
      <MACADDR>52:54:00:fa:20:0e</MACADDR>
      <SLAVES></SLAVES>
      <STATUS>Up</STATUS>
      <TYPE>bridge</TYPE>
      <VIRTUALDEV>1</VIRTUALDEV>
    </NETWORKS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"description": "virbr0", "ipaddress": "192.168.122.1", "ipmask": "255.255.255.0", "ipsubnet": "192.168.122.0", "status": "up", "type": "bridge", "virtualdev": true, "mac": "52:54:00:fa:20:0e"}',
                'virtual'   => true
            ], [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <NETWORKS>
      <DESCRIPTION>virbr0-nic</DESCRIPTION>
      <MACADDR>52:54:00:fa:20:0e</MACADDR>
      <SPEED>10</SPEED>
      <STATUS>Down</STATUS>
      <VIRTUALDEV>1</VIRTUALDEV>
    </NETWORKS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"description": "virbr0-nic", "speed": "10", "status": "down", "virtualdev": true, "mac": "52:54:00:fa:20:0e"}',
                'virtual'   => true
            ], [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <NETWORKS>
      <DESCRIPTION>tun0</DESCRIPTION>
      <IPADDRESS>192.168.11.47</IPADDRESS>
      <IPMASK>255.255.255.0</IPMASK>
      <IPSUBNET>192.168.11.0</IPSUBNET>
      <SPEED>10</SPEED>
      <STATUS>Up</STATUS>
      <VIRTUALDEV>1</VIRTUALDEV>
    </NETWORKS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"description": "tun0", "ipaddress": "192.168.11.47", "ipmask": "255.255.255.0", "ipsubnet": "192.168.11.0", "speed": "10", "status": "up", "virtualdev": true}',
                'virtual'   => true
            ], [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <NETWORKS>
      <DESCRIPTION>tun0</DESCRIPTION>
      <IPADDRESS6>fe80::c33a:59c7:61c5:339e</IPADDRESS6>
      <IPMASK6>ffff:ffff:ffff:ffff::</IPMASK6>
      <IPSUBNET6>fe80::</IPSUBNET6>
      <SPEED>10</SPEED>
      <STATUS>Up</STATUS>
      <VIRTUALDEV>1</VIRTUALDEV>
    </NETWORKS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"description": "tun0", "ipaddress6": "fe80::c33a:59c7:61c5:339e", "ipmask6": "ffff:ffff:ffff:ffff::", "ipsubnet6": "fe80::", "speed": "10", "status": "up", "virtualdev": true}',
                'virtual'   => true
            ]
        ];
    }

    /**
     * @dataProvider assetProvider
     */
    public function testPrepare($xml, $expected, $virtual)
    {
        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\NetworkCard($computer, $json->content->networks);
        $asset->setExtraData((array)$json->content);
        $conf = new \Glpi\Inventory\Conf();
        $asset->checkConf($conf);
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected), $result[0]);
    }

    /**
     * @dataProvider assetProvider
     */
    public function testNoVirtuals($xml, $expected, $virtual)
    {
        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\NetworkCard($computer, $json->content->networks);
        $asset->setExtraData((array)$json->content);
        $this->login();
        $conf = new \Glpi\Inventory\Conf();
        $this->assertTrue($conf->saveConf(['component_networkcardvirtual' => 0]));
        $this->logOut();
        $asset->checkConf($conf);
        $result = $asset->prepare();
        $this->login();
        $this->assertTrue($conf->saveConf(['component_networkcardvirtual' => 1]));
        $this->logOut();
        if ($virtual) {
            $this->assertIsArray($result);
            $this->assertEmpty($result);
        } else {
            $this->assertEquals(json_decode($expected), $result[0]);
        }
    }

    public function testHandle()
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');

       //first, check there are no controller linked to this computer
        $idn = new \Item_DeviceNetworkCard();
                 $this->assertFalse(
                     $idn->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']),
                     'A network card is already linked to computer!'
                 );

       //convert data
        $expected = $this->assetProvider()[0];

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\NetworkCard($computer, $json->content->networks);
        $asset->setExtraData((array)$json->content);
        $conf = new \Glpi\Inventory\Conf();
        $asset->checkConf($conf);
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected['expected']), $result[0]);

       //handle
        $asset->handleLinks();
        $asset->handle();
        $this->assertTrue(
            $idn->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']),
            'Network card has not been linked to computer :('
        );
    }

    public function testAllNetwork()
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <CONTROLLERS>
      <CAPTION>Xeon E3-1200 v5/E3-1500 v5/6th Gen Core Processor Host Bridge/DRAM Registers</CAPTION>
      <DRIVER>skl_uncore</DRIVER>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>Xeon E3-1200 v5/E3-1500 v5/6th Gen Core Processor Host Bridge/DRAM Registers</NAME>
      <PCICLASS>0600</PCICLASS>
      <PCISLOT>00:00.0</PCISLOT>
      <PRODUCTID>1904</PRODUCTID>
      <REV>08</REV>
      <TYPE>Host bridge</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <CONTROLLERS>
      <CAPTION>Skylake GT2 [HD Graphics 520]</CAPTION>
      <DRIVER>i915</DRIVER>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>Skylake GT2 [HD Graphics 520]</NAME>
      <PCICLASS>0300</PCICLASS>
      <PCISLOT>00:02.0</PCISLOT>
      <PRODUCTID>1916</PRODUCTID>
      <REV>07</REV>
      <TYPE>VGA compatible controller</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <CONTROLLERS>
      <CAPTION>Xeon E3-1200 v5/E3-1500 v5/6th Gen Core Processor Thermal Subsystem</CAPTION>
      <DRIVER>proc_thermal</DRIVER>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>Xeon E3-1200 v5/E3-1500 v5/6th Gen Core Processor Thermal Subsystem</NAME>
      <PCICLASS>1180</PCICLASS>
      <PCISLOT>00:04.0</PCISLOT>
      <PRODUCTID>1903</PRODUCTID>
      <REV>08</REV>
      <TYPE>Signal processing controller</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <CONTROLLERS>
      <CAPTION>Sunrise Point-LP USB 3.0 xHCI Controller</CAPTION>
      <DRIVER>xhci_hcd</DRIVER>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>Sunrise Point-LP USB 3.0 xHCI Controller</NAME>
      <PCICLASS>0c03</PCICLASS>
      <PCISLOT>00:14.0</PCISLOT>
      <PRODUCTID>9d2f</PRODUCTID>
      <REV>21</REV>
      <TYPE>USB controller</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <CONTROLLERS>
      <CAPTION>Sunrise Point-LP Thermal subsystem</CAPTION>
      <DRIVER>intel_pch_thermal</DRIVER>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>Sunrise Point-LP Thermal subsystem</NAME>
      <PCICLASS>1180</PCICLASS>
      <PCISLOT>00:14.2</PCISLOT>
      <PRODUCTID>9d31</PRODUCTID>
      <REV>21</REV>
      <TYPE>Signal processing controller</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <CONTROLLERS>
      <CAPTION>Sunrise Point-LP Serial IO I2C Controller #0</CAPTION>
      <DRIVER>intel</DRIVER>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>Sunrise Point-LP Serial IO I2C Controller #0</NAME>
      <PCICLASS>1180</PCICLASS>
      <PCISLOT>00:15.0</PCISLOT>
      <PRODUCTID>9d60</PRODUCTID>
      <REV>21</REV>
      <TYPE>Signal processing controller</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <CONTROLLERS>
      <CAPTION>Sunrise Point-LP Serial IO I2C Controller #1</CAPTION>
      <DRIVER>intel</DRIVER>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>Sunrise Point-LP Serial IO I2C Controller #1</NAME>
      <PCICLASS>1180</PCICLASS>
      <PCISLOT>00:15.1</PCISLOT>
      <PRODUCTID>9d61</PRODUCTID>
      <REV>21</REV>
      <TYPE>Signal processing controller</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <CONTROLLERS>
      <CAPTION>Sunrise Point-LP CSME HECI #1</CAPTION>
      <DRIVER>mei_me</DRIVER>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>Sunrise Point-LP CSME HECI #1</NAME>
      <PCICLASS>0780</PCICLASS>
      <PCISLOT>00:16.0</PCISLOT>
      <PRODUCTID>9d3a</PRODUCTID>
      <REV>21</REV>
      <TYPE>Communication controller</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <CONTROLLERS>
      <CAPTION>Sunrise Point-LP SATA Controller [AHCI mode]</CAPTION>
      <DRIVER>ahci</DRIVER>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>Sunrise Point-LP SATA Controller [AHCI mode]</NAME>
      <PCICLASS>0106</PCICLASS>
      <PCISLOT>00:17.0</PCISLOT>
      <PRODUCTID>9d03</PRODUCTID>
      <REV>21</REV>
      <TYPE>SATA controller</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <CONTROLLERS>
      <CAPTION>Sunrise Point-LP PCI Express Root Port #1</CAPTION>
      <DRIVER>pcieport</DRIVER>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>Sunrise Point-LP PCI Express Root Port #1</NAME>
      <PCICLASS>0604</PCICLASS>
      <PCISLOT>00:1c.0</PCISLOT>
      <PRODUCTID>9d10</PRODUCTID>
      <TYPE>PCI bridge</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <CONTROLLERS>
      <CAPTION>Sunrise Point-LP PCI Express Root Port #5</CAPTION>
      <DRIVER>pcieport</DRIVER>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>Sunrise Point-LP PCI Express Root Port #5</NAME>
      <PCICLASS>0604</PCICLASS>
      <PCISLOT>00:1c.4</PCISLOT>
      <PRODUCTID>9d14</PRODUCTID>
      <TYPE>PCI bridge</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <CONTROLLERS>
      <CAPTION>Sunrise Point-LP PCI Express Root Port #6</CAPTION>
      <DRIVER>pcieport</DRIVER>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>Sunrise Point-LP PCI Express Root Port #6</NAME>
      <PCICLASS>0604</PCICLASS>
      <PCISLOT>00:1c.5</PCISLOT>
      <PRODUCTID>9d15</PRODUCTID>
      <TYPE>PCI bridge</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <CONTROLLERS>
      <CAPTION>Sunrise Point-LP PCI Express Root Port #9</CAPTION>
      <DRIVER>pcieport</DRIVER>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>Sunrise Point-LP PCI Express Root Port #9</NAME>
      <PCICLASS>0604</PCICLASS>
      <PCISLOT>00:1d.0</PCISLOT>
      <PRODUCTID>9d18</PRODUCTID>
      <TYPE>PCI bridge</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <CONTROLLERS>
      <CAPTION>Sunrise Point-LP LPC Controller</CAPTION>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>Sunrise Point-LP LPC Controller</NAME>
      <PCICLASS>0601</PCICLASS>
      <PCISLOT>00:1f.0</PCISLOT>
      <PRODUCTID>9d48</PRODUCTID>
      <REV>21</REV>
      <TYPE>ISA bridge</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <CONTROLLERS>
      <CAPTION>Sunrise Point-LP PMC</CAPTION>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>Sunrise Point-LP PMC</NAME>
      <PCICLASS>0580</PCICLASS>
      <PCISLOT>00:1f.2</PCISLOT>
      <PRODUCTID>9d21</PRODUCTID>
      <REV>21</REV>
      <TYPE>Memory controller</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <CONTROLLERS>
      <CAPTION>Sunrise Point-LP HD Audio</CAPTION>
      <DRIVER>snd_hda_intel</DRIVER>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>Sunrise Point-LP HD Audio</NAME>
      <PCICLASS>0403</PCICLASS>
      <PCISLOT>00:1f.3</PCISLOT>
      <PRODUCTID>9d70</PRODUCTID>
      <REV>21</REV>
      <TYPE>Audio device</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <CONTROLLERS>
      <CAPTION>Sunrise Point-LP SMBus</CAPTION>
      <DRIVER>i801_smbus</DRIVER>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>Sunrise Point-LP SMBus</NAME>
      <PCICLASS>0c05</PCICLASS>
      <PCISLOT>00:1f.4</PCISLOT>
      <PRODUCTID>9d23</PRODUCTID>
      <REV>21</REV>
      <TYPE>SMBus</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <CONTROLLERS>
      <CAPTION>DSL6340 Thunderbolt 3 Bridge [Alpine Ridge 2C 2015]</CAPTION>
      <DRIVER>pcieport</DRIVER>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>DSL6340 Thunderbolt 3 Bridge [Alpine Ridge 2C 2015]</NAME>
      <PCICLASS>0604</PCICLASS>
      <PCISLOT>01:00.0</PCISLOT>
      <PRODUCTID>1576</PRODUCTID>
      <TYPE>PCI bridge</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <CONTROLLERS>
      <CAPTION>DSL6340 Thunderbolt 3 Bridge [Alpine Ridge 2C 2015]</CAPTION>
      <DRIVER>pcieport</DRIVER>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>DSL6340 Thunderbolt 3 Bridge [Alpine Ridge 2C 2015]</NAME>
      <PCICLASS>0604</PCICLASS>
      <PCISLOT>02:00.0</PCISLOT>
      <PRODUCTID>1576</PRODUCTID>
      <TYPE>PCI bridge</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <CONTROLLERS>
      <CAPTION>DSL6340 Thunderbolt 3 Bridge [Alpine Ridge 2C 2015]</CAPTION>
      <DRIVER>pcieport</DRIVER>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>DSL6340 Thunderbolt 3 Bridge [Alpine Ridge 2C 2015]</NAME>
      <PCICLASS>0604</PCICLASS>
      <PCISLOT>02:01.0</PCISLOT>
      <PRODUCTID>1576</PRODUCTID>
      <TYPE>PCI bridge</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <CONTROLLERS>
      <CAPTION>DSL6340 Thunderbolt 3 Bridge [Alpine Ridge 2C 2015]</CAPTION>
      <DRIVER>pcieport</DRIVER>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>DSL6340 Thunderbolt 3 Bridge [Alpine Ridge 2C 2015]</NAME>
      <PCICLASS>0604</PCICLASS>
      <PCISLOT>02:02.0</PCISLOT>
      <PRODUCTID>1576</PRODUCTID>
      <TYPE>PCI bridge</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <CONTROLLERS>
      <CAPTION>DSL6340 Thunderbolt 3 NHI [Alpine Ridge 2C 2015]</CAPTION>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>DSL6340 Thunderbolt 3 NHI [Alpine Ridge 2C 2015]</NAME>
      <PCICLASS>0880</PCICLASS>
      <PCISLOT>03:00.0</PCISLOT>
      <PRODUCTID>1575</PRODUCTID>
      <TYPE>System peripheral</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <CONTROLLERS>
      <CAPTION>DSL6340 USB 3.1 Controller [Alpine Ridge]</CAPTION>
      <DRIVER>xhci_hcd</DRIVER>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>DSL6340 USB 3.1 Controller [Alpine Ridge]</NAME>
      <PCICLASS>0c03</PCICLASS>
      <PCISLOT>39:00.0</PCISLOT>
      <PRODUCTID>15b5</PRODUCTID>
      <TYPE>USB controller</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <CONTROLLERS>
      <CAPTION>Wireless 8260</CAPTION>
      <DRIVER>iwlwifi</DRIVER>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>Wireless 8260</NAME>
      <PCICLASS>0280</PCICLASS>
      <PCISLOT>3a:00.0</PCISLOT>
      <PRODUCTID>24f3</PRODUCTID>
      <TYPE>Network controller</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <CONTROLLERS>
      <CAPTION>RTS525A PCI Express Card Reader</CAPTION>
      <DRIVER>rtsx_pci</DRIVER>
      <MANUFACTURER>Realtek Semiconductor Co., Ltd.</MANUFACTURER>
      <NAME>RTS525A PCI Express Card Reader</NAME>
      <PCICLASS>ff00</PCICLASS>
      <PCISLOT>3b:00.0</PCISLOT>
      <PRODUCTID>525a</PRODUCTID>
      <REV>01</REV>
      <TYPE>Unassigned class</TYPE>
      <VENDORID>10ec</VENDORID>
    </CONTROLLERS>
    <CONTROLLERS>
      <CAPTION>NVMe SSD Controller SM951/PM951</CAPTION>
      <DRIVER>nvme</DRIVER>
      <MANUFACTURER>Samsung Electronics Co Ltd</MANUFACTURER>
      <NAME>NVMe SSD Controller SM951/PM951</NAME>
      <PCICLASS>0108</PCICLASS>
      <PCISLOT>3c:00.0</PCISLOT>
      <PRODUCTID>a802</PRODUCTID>
      <REV>01</REV>
      <TYPE>Non-Volatile memory controller</TYPE>
      <VENDORID>144d</VENDORID>
    </CONTROLLERS>
    <NETWORKS>
      <DESCRIPTION>lo</DESCRIPTION>
      <IPADDRESS>127.0.0.1</IPADDRESS>
      <IPMASK>255.0.0.0</IPMASK>
      <IPSUBNET>127.0.0.0</IPSUBNET>
      <MACADDR>00:00:00:00:00:00</MACADDR>
      <STATUS>Up</STATUS>
      <TYPE>loopback</TYPE>
      <VIRTUALDEV>1</VIRTUALDEV>
    </NETWORKS>
    <NETWORKS>
      <DESCRIPTION>lo</DESCRIPTION>
      <IPADDRESS6>::1</IPADDRESS6>
      <IPMASK6>fff0::</IPMASK6>
      <IPSUBNET6>::</IPSUBNET6>
      <MACADDR>00:00:00:00:00:00</MACADDR>
      <STATUS>Up</STATUS>
      <TYPE>loopback</TYPE>
      <VIRTUALDEV>1</VIRTUALDEV>
    </NETWORKS>
    <NETWORKS>
      <DESCRIPTION>wlp58s0</DESCRIPTION>
      <DRIVER>iwlwifi</DRIVER>
      <IPADDRESS>192.168.1.119</IPADDRESS>
      <IPGATEWAY>192.168.1.1</IPGATEWAY>
      <IPMASK>255.255.255.0</IPMASK>
      <IPSUBNET>192.168.1.0</IPSUBNET>
      <MACADDR>44:85:00:2b:90:bc</MACADDR>
      <PCIID>8086:24F3:8086:0050</PCIID>
      <PCISLOT>0000:3a:00.0</PCISLOT>
      <STATUS>Up</STATUS>
      <TYPE>wifi</TYPE>
      <VIRTUALDEV>0</VIRTUALDEV>
      <WIFI_BSSID>58:6D:8F:C2:19:BF</WIFI_BSSID>
      <WIFI_MODE>Managed</WIFI_MODE>
      <WIFI_SSID>teclib</WIFI_SSID>
      <WIFI_VERSION>802.11</WIFI_VERSION>
    </NETWORKS>
    <NETWORKS>
      <DESCRIPTION>wlp58s0</DESCRIPTION>
      <DRIVER>iwlwifi</DRIVER>
      <IPADDRESS6>fe80::92a4:26c6:99dd:2d60</IPADDRESS6>
      <IPMASK6>ffff:ffff:ffff:ffff::</IPMASK6>
      <IPSUBNET6>fe80::</IPSUBNET6>
      <MACADDR>44:85:00:2b:90:bc</MACADDR>
      <PCIID>8086:24F3:8086:0050</PCIID>
      <PCISLOT>0000:3a:00.0</PCISLOT>
      <STATUS>Up</STATUS>
      <TYPE>wifi</TYPE>
      <VIRTUALDEV>0</VIRTUALDEV>
      <WIFI_BSSID>58:6D:8F:C2:19:BF</WIFI_BSSID>
      <WIFI_MODE>Managed</WIFI_MODE>
      <WIFI_SSID>teclib</WIFI_SSID>
      <WIFI_VERSION>802.11</WIFI_VERSION>
    </NETWORKS>
    <NETWORKS>
      <DESCRIPTION>virbr0</DESCRIPTION>
      <IPADDRESS>192.168.122.1</IPADDRESS>
      <IPMASK>255.255.255.0</IPMASK>
      <IPSUBNET>192.168.122.0</IPSUBNET>
      <MACADDR>52:54:00:fa:20:0e</MACADDR>
      <SLAVES></SLAVES>
      <STATUS>Up</STATUS>
      <TYPE>bridge</TYPE>
      <VIRTUALDEV>1</VIRTUALDEV>
    </NETWORKS>
    <NETWORKS>
      <DESCRIPTION>virbr0-nic</DESCRIPTION>
      <MACADDR>52:54:00:fa:20:0e</MACADDR>
      <SPEED>10</SPEED>
      <STATUS>Down</STATUS>
      <VIRTUALDEV>1</VIRTUALDEV>
    </NETWORKS>
    <NETWORKS>
      <DESCRIPTION>tun0</DESCRIPTION>
      <IPADDRESS>192.168.11.47</IPADDRESS>
      <IPMASK>255.255.255.0</IPMASK>
      <IPSUBNET>192.168.11.0</IPSUBNET>
      <SPEED>10</SPEED>
      <STATUS>Up</STATUS>
      <VIRTUALDEV>1</VIRTUALDEV>
    </NETWORKS>
    <NETWORKS>
      <DESCRIPTION>tun0</DESCRIPTION>
      <IPADDRESS6>fe80::c33a:59c7:61c5:339e</IPADDRESS6>
      <IPMASK6>ffff:ffff:ffff:ffff::</IPMASK6>
      <IPSUBNET6>fe80::</IPSUBNET6>
      <SPEED>10</SPEED>
      <STATUS>Up</STATUS>
      <VIRTUALDEV>1</VIRTUALDEV>
    </NETWORKS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>";

        $computer = getItemByTypeName('Computer', '_test_pc01');

       //first, check there are no controller linked to this computer
        $idn = new \Item_DeviceNetworkCard();
                 $this->assertFalse(
                     $idn->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']),
                     'A network card is already linked to computer!'
                 );

       //convert data
        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $asset = new \Glpi\Inventory\Asset\NetworkCard($computer, $json->content->networks);
        $asset->setExtraData((array)$json->content);
        $conf = new \Glpi\Inventory\Conf();
        $asset->checkConf($conf);
        $result = $asset->prepare();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
        $ports = $asset->getNetworkPorts();
        $this->assertIsArray($ports);
        $this->assertCount(5, $ports);
        $this->assertArrayHasKey('lo-00:00:00:00:00:00', $ports);
        $this->assertArrayHasKey('wlp58s0-44:85:00:2b:90:bc', $ports);
        $this->assertArrayHasKey('virbr0-52:54:00:fa:20:0e', $ports);
        $this->assertArrayHasKey('virbr0-nic-52:54:00:fa:20:0e', $ports);
        $this->assertArrayHasKey('tun0-', $ports);

        $this->assertIsArray($ports['lo-00:00:00:00:00:00']->ipaddress);
        $this->assertTrue(in_array('127.0.0.1', $ports['lo-00:00:00:00:00:00']->ipaddress));
        $this->assertTrue(in_array('::1', $ports['lo-00:00:00:00:00:00']->ipaddress));

        $this->assertIsArray($ports['wlp58s0-44:85:00:2b:90:bc']->ipaddress);
        $this->assertTrue(in_array('192.168.1.119', $ports['wlp58s0-44:85:00:2b:90:bc']->ipaddress));
        $this->assertTrue(in_array('fe80::92a4:26c6:99dd:2d60', $ports['wlp58s0-44:85:00:2b:90:bc']->ipaddress));

        //handle
        $asset->handleLinks();
        $asset->handle();

        //TODO: check for created values in database
    }

    public function testInventoryUpdate()
    {
        $computer = new \Computer();
        $device_net = new \DeviceNetworkCard();
        $item_net = new \Item_DeviceNetworkCard();
        $network_port = new \NetworkPort();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <CONTROLLERS>
      <CAPTION>82540EM Gigabit Ethernet Controller</CAPTION>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>PRO/1000 MT Desktop Adapter</NAME>
      <PCISUBSYSTEMID>8086:001e</PCISUBSYSTEMID>
      <PRODUCTID>100e</PRODUCTID>
      <TYPE>Carte Intel(R) PRO/1000 MT pour station de travail</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <CONTROLLERS>
      <CAPTION>Ethernet Connection I219-LM</CAPTION>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>Ethernet Connection I219-LM</NAME>
      <PCISUBSYSTEMID>1028:06dd</PCISUBSYSTEMID>
      <PRODUCTID>156f</PRODUCTID>
      <TYPE>Intel(R) Ethernet Connection I219-LM</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <NETWORKS>
      <DESCRIPTION>Carte Intel(R) PRO/1000 MT pour station de travail</DESCRIPTION>
      <IPADDRESS>172.28.211.63</IPADDRESS>
      <IPDHCP>172.28.200.22</IPDHCP>
      <IPGATEWAY>172.28.211.1</IPGATEWAY>
      <IPMASK>255.255.255.0</IPMASK>
      <IPSUBNET>172.28.211.0</IPSUBNET>
      <MACADDR>08:00:27:16:9C:60</MACADDR>
      <PCIID>8086:100E:001E:8086</PCIID>
      <SPEED>1000</SPEED>
      <STATUS>Up</STATUS>
      <VIRTUALDEV>0</VIRTUALDEV>
    </NETWORKS>
    <NETWORKS>
      <DESCRIPTION>Intel(R) Ethernet Connection I219-LM</DESCRIPTION>
      <IPADDRESS>10.16.9.64</IPADDRESS>
      <IPDHCP>10.1.2.11</IPDHCP>
      <IPGATEWAY>10.16.1.1</IPGATEWAY>
      <IPMASK>255.255.240.0</IPMASK>
      <IPSUBNET>10.16.0.0</IPSUBNET>
      <MACADDR>18:DB:F2:29:99:35</MACADDR>
      <PCIID>8086:156F:06DD:1028</PCIID>
      <SPEED>1000</SPEED>
      <STATUS>Up</STATUS>
      <VIRTUALDEV>0</VIRTUALDEV>
    </NETWORKS>
    <HARDWARE>
      <NAME>pc002</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>ggheb7ne7</SSN>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Agent_v2.3.19</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>test-pc002</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

       //create manually a computer, with 3 network cards
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $manufacturer = new \Manufacturer();
        $manufacturers_id = $manufacturer->add([
            'name' => 'Intel Corporation'
        ]);
        $this->assertGreaterThan(0, $manufacturers_id);

        $card_1_id = $device_net->add([
            'designation' => '82540EM Gigabit Ethernet Controller',
            'manufacturers_id' => $manufacturers_id,
            'mac_default' => '08:00:27:16:9C:60',
            'entities_id'  => 0
        ]);
        $this->assertGreaterThan(0, $card_1_id);

        $item_card_1_id = $item_net->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicenetworkcards_id' => $card_1_id,
            'mac' => '08:00:27:16:9C:60'
        ]);
        $this->assertGreaterThan(0, $item_card_1_id);

        $card_2_id = $device_net->add([
            'designation' => 'Ethernet Connection I219-LM',
            'manufacturers_id' => $manufacturers_id,
            'mac_default' => '18:db:f2:29:99:35',
            'entities_id'  => 0
        ]);
        $this->assertGreaterThan(0, $card_2_id);

        $item_card_2_id = $item_net->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicenetworkcards_id' => $card_2_id,
            'mac' => '18:db:f2:29:99:35'
        ]);
        $this->assertGreaterThan(0, $item_card_2_id);

        $card_3_id = $device_net->add([
            'designation' => 'Me Ethernet Controller',
            'manufacturers_id' => $manufacturers_id,
            'mac_default' => '00:b1:00:00:00',
            'entities_id'  => 0
        ]);
        $this->assertGreaterThan(0, $card_3_id);

        $item_card_3_id = $item_net->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicenetworkcards_id' => $card_3_id
        ]);
        $this->assertGreaterThan(0, $item_card_3_id);

        $cards = $item_net->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(3, $cards);
        foreach ($cards as $card) {
            $this->assertEquals(0, $card['is_dynamic']);
        }

       //computer inventory knows only 2 network cards
        $this->doInventory($xml_source, true);

       //we still have 3 network cards
        $cards = $device_net->find();
        $this->assertCount(3, $cards);

       //we still have 3 network cards items linked to the computer
        $cards = $item_net->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(3, $cards);

       //network cards present in the inventory source are now dynamic
        $cards = $item_net->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(2, $cards);

       //network card not present in the inventory is still not dynamic
        $cards = $item_net->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->assertCount(1, $cards);

        $this->assertTrue(
            $network_port->getFromDBByCrit(['mac' => '08:00:27:16:9c:60'])
        );
        // the port is up
        $this->assertEquals('1', $network_port->fields['ifinternalstatus']);
        $this->assertEquals('1', $network_port->fields['ifstatus']);

        //Redo inventory, but with removed last network card
        //and the port on the first card is down
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <CONTROLLERS>
      <CAPTION>82540EM Gigabit Ethernet Controller</CAPTION>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>PRO/1000 MT Desktop Adapter</NAME>
      <PCISUBSYSTEMID>8086:001e</PCISUBSYSTEMID>
      <PRODUCTID>100e</PRODUCTID>
      <TYPE>Carte Intel(R) PRO/1000 MT pour station de travail</TYPE>
      <VENDORID>8086</VENDORID>
    </CONTROLLERS>
    <NETWORKS>
      <DESCRIPTION>Carte Intel(R) PRO/1000 MT pour station de travail</DESCRIPTION>
      <IPADDRESS>172.28.211.63</IPADDRESS>
      <IPDHCP>172.28.200.22</IPDHCP>
      <IPGATEWAY>172.28.211.1</IPGATEWAY>
      <IPMASK>255.255.255.0</IPMASK>
      <IPSUBNET>172.28.211.0</IPSUBNET>
      <MACADDR>08:00:27:16:9C:60</MACADDR>
      <PCIID>8086:100E:001E:8086</PCIID>
      <SPEED>-1</SPEED>
      <STATUS>Up</STATUS>
      <VIRTUALDEV>0</VIRTUALDEV>
    </NETWORKS>
    <HARDWARE>
      <NAME>pc002</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>ggheb7ne7</SSN>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Agent_v2.3.19</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>test-pc002</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

        $this->doInventory($xml_source, true);

       //we still have 3 network cards
        $cards = $device_net->find();
        $this->assertCount(3, $cards);

       //we now have 2 network cards linked to computer only
        $cards = $item_net->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(2, $cards);

       //network card present in the inventory source is still dynamic
        $cards = $item_net->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $cards);

       //network card not present in the inventory is still not dynamic
        $cards = $item_net->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->assertCount(1, $cards);

        $this->assertTrue(
            $network_port->getFromDBByCrit(['mac' => '08:00:27:16:9c:60'])
        );
        // the port is up
        $this->assertEquals('1', $network_port->fields['ifinternalstatus']);
        // but the connection is down
        $this->assertEquals('2', $network_port->fields['ifstatus']);
    }
}
