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

/* Test for inc/inventory/asset/firmware.class.php */

class Firmware extends AbstractInventoryAsset
{
    protected function assetProvider(): array
    {
        return [
            [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
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
        <CONTACT>noc@teclib.com</CONTACT>
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
      </INFO>
    </DEVICE>
    <VERSIONCLIENT>4.1</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
  </REQUEST>",
                'expected'  => '{"description":"device firmware","manufacturer":"Cisco","name":"UCS 6248UP 48-Port","type":"device","version":"5.0(3)N2(4.02b)","manufacturers_id":"Cisco","designation":"UCS 6248UP 48-Port","devicefirmwaretypes_id":"device", "is_dynamic": 1}'
            ]
        ];
    }

    /**
     * @dataProvider assetProvider
     */
    public function testPrepare($xml, $expected)
    {
        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\Firmware($computer, (array)$json->content->firmwares);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->object($result[0])->isEqualTo(json_decode($expected));
    }

    public function testHandle()
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');

       //first, check there are no controller linked to this computer
        $idf = new \Item_DeviceFirmware();
        $this->boolean($idf->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isFalse('A firmware is already linked to computer!');

       //convert data
        $expected = $this->assetProvider()[0];

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\Firmware($computer, (array)$json->content->firmwares);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->object($result[0])->isEqualTo(json_decode($expected['expected']));

       //handle
        $asset->handleLinks();
        $asset->handle();
        $this->boolean($idf->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isTrue('Firmware has not been linked to computer :(');
    }

    public function testLockedFieldandFirmware()
    {
        global $DB;
        $device_fw = new \DeviceFirmware();
        $item_fw = new \Item_DeviceFirmware();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
      <REQUEST>
        <CONTENT>
          <FIRMWARES>
            <DESCRIPTION>device firmware</DESCRIPTION>
            <MANUFACTURER>Cisco</MANUFACTURER>
            <NAME>UCS 6248UP 48-Port</NAME>
            <TYPE>device</TYPE>
            <VERSION>5.0(3)N2(4.02b)</VERSION>
          </FIRMWARES>
          <FIRMWARES>
            <DESCRIPTION>HP Web Management Software version</DESCRIPTION>
            <MANUFACTURER>HP</MANUFACTURER>
            <NAME>HP-HttpMg-Version</NAME>
            <TYPE>system</TYPE>
            <VERSION>WC.16.02.0003</VERSION>
          </FIRMWARES>
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

        //add lockedfield to check for DB warning on manage DeviceFirmware lockedField
        $this->integer(
            (int)$DB->insert("glpi_lockedfields", ["field" => mt_rand(), "itemtype" => "Item_DeviceFirmware", "is_global" => 0])
        )->isGreaterThan(0);


        //computer inventory knows only "UCS 6248UP 48-Port" and "HP-HttpMg-Version" firmwares
        $this->doInventory($xml_source, true);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();

        //we still have 2 firmwares + 1 bios
        $fws = $device_fw->find();
        $this->integer(count($fws))->isIdenticalTo(3);

        //we still have 2 firmwares items linked to the computer
        $fws = $item_fw->find(['itemtype' => 'Computer', 'items_id' => $agent['items_id']]);
        $this->integer(count($fws))->isIdenticalTo(2);
    }

    public function testInventoryUpdate()
    {
        $computer = new \Computer();
        $device_fw = new \DeviceFirmware();
        $item_fw = new \Item_DeviceFirmware();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <FIRMWARES>
      <DESCRIPTION>device firmware</DESCRIPTION>
      <MANUFACTURER>Cisco</MANUFACTURER>
      <NAME>UCS 6248UP 48-Port</NAME>
      <TYPE>device</TYPE>
      <VERSION>5.0(3)N2(4.02b)</VERSION>
    </FIRMWARES>
    <FIRMWARES>
      <DESCRIPTION>HP Web Management Software version</DESCRIPTION>
      <MANUFACTURER>HP</MANUFACTURER>
      <NAME>HP-HttpMg-Version</NAME>
      <TYPE>system</TYPE>
      <VERSION>WC.16.02.0003</VERSION>
    </FIRMWARES>
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

       //create manually a computer, with 3 firmwares
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0
        ]);
        $this->integer($computers_id)->isGreaterThan(0);

        $manufacturer = new \Manufacturer();
        $manufacturers_id = $manufacturer->add([
            'name' => 'Cisco'
        ]);
        $this->integer($manufacturers_id)->isGreaterThan(0);

        $type = new \DeviceFirmwareType();
        $types_id = $type->add([
            'name' => 'device'
        ]);
        $this->integer($types_id)->isGreaterThan(0);

        $fw_1_id = $device_fw->add([
            'designation' => 'UCS 6248UP 48-Port',
            'manufacturers_id' => $manufacturers_id,
            'devicefirmwaretypes_id' => $types_id,
            'version' => '5.0(3)N2(4.02b)',
            'entities_id'  => 0
        ]);
        $this->integer($fw_1_id)->isGreaterThan(0);

        $item_fw_1_id = $item_fw->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicefirmwares_id' => $fw_1_id
        ]);
        $this->integer($item_fw_1_id)->isGreaterThan(0);

        $manufacturer = new \Manufacturer();
        $manufacturers_id = $manufacturer->add([
            'name' => 'HP'
        ]);
        $this->integer($manufacturers_id)->isGreaterThan(0);

        $type = new \DeviceFirmwareType();
        $types_id = $type->add([
            'name' => 'system'
        ]);
        $this->integer($types_id)->isGreaterThan(0);

        $fw_2_id = $device_fw->add([
            'designation' => 'HP-HttpMg-Version',
            'manufacturers_id' => $manufacturers_id,
            'devicefirmwaretypes_id' => $types_id,
            'version' => 'WC.16.02.0003',
            'entities_id'  => 0
        ]);
        $this->integer($fw_2_id)->isGreaterThan(0);

        $item_fw_2_id = $item_fw->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicefirmwares_id' => $fw_2_id
        ]);
        $this->integer($item_fw_2_id)->isGreaterThan(0);

        $fw_3_id = $device_fw->add([
            'designation' => 'My Firmware',
            'manufacturers_id' => $manufacturers_id,
            'devicefirmwaretypes_id' => $types_id,
            'entities_id'  => 0
        ]);
        $this->integer($fw_3_id)->isGreaterThan(0);

        $item_fw_3_id = $item_fw->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicefirmwares_id' => $fw_3_id
        ]);
        $this->integer($item_fw_3_id)->isGreaterThan(0);

        $firmwares = $item_fw->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($firmwares))->isIdenticalTo(3);
        foreach ($firmwares as $firmware) {
            $this->variable($firmware['is_dynamic'])->isEqualTo(0);
        }

       //computer inventory knows only "UCS 6248UP 48-Port" and "HP-HttpMg-Version" firmwares
        $this->doInventory($xml_source, true);

        //we still have 3 firmwares + 1 bios
        $fws = $device_fw->find();
        $this->integer(count($fws))->isIdenticalTo(3 + 1);

        //we still have 3 firmwares items linked to the computer
        $fws = $item_fw->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($fws))->isIdenticalTo(3);

        //firmwares present in the inventory source are now dynamic
        $fws = $item_fw->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($fws))->isIdenticalTo(2);

       //firmware not present in the inventory is still not dynamic
        $fws = $item_fw->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->integer(count($fws))->isIdenticalTo(1);

       //Redo inventory, but with removed "HP-HttpMg-Version" firmware
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <FIRMWARES>
      <DESCRIPTION>device firmware</DESCRIPTION>
      <MANUFACTURER>Cisco</MANUFACTURER>
      <NAME>UCS 6248UP 48-Port</NAME>
      <TYPE>device</TYPE>
      <VERSION>5.0(3)N2(4.02b)</VERSION>
    </FIRMWARES>
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

       //we still have 3 firmwares + 1 bios
        $fws = $device_fw->find();
        $this->integer(count($fws))->isIdenticalTo(3 + 1);

       //we now have 2 firmwares linked to computer only
        $fws = $item_fw->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($fws))->isIdenticalTo(2);

       //firmware present in the inventory source is still dynamic
        $fws = $item_fw->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($fws))->isIdenticalTo(1);

       //firmware not present in the inventory is still not dynamic
        $fws = $item_fw->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->integer(count($fws))->isIdenticalTo(1);
    }
}
