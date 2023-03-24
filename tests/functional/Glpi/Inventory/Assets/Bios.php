<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

class Bios extends AbstractInventoryAsset
{
    protected function assetProvider(): array
    {
        return [
            [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <BIOS>
      <ASSETTAG />  <BDATE>06/02/2016</BDATE>
      <BMANUFACTURER>Dell Inc.</BMANUFACTURER>
      <BVERSION>1.4.3</BVERSION>
      <MMANUFACTURER>Dell Inc.</MMANUFACTURER>
      <MMODEL>07TYC2</MMODEL>
      <MSN>/640HP72/CN129636460078/</MSN>
      <SKUNUMBER>0704</SKUNUMBER>
      <SMANUFACTURER>Dell Inc.</SMANUFACTURER>
      <SMODEL>XPS 13 9350</SMODEL>
      <SSN>640HP72</SSN>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"bdate": "2016-02-06", "bmanufacturer": "Dell Inc.", "bversion": "1.4.3", "mmanufacturer": "Dell Inc.", "mmodel": "07TYC2", "msn": "/640HP72/CN129636460078/", "skunumber": "0704", "smanufacturer": "Dell Inc.", "smodel": "XPS 13 9350", "ssn": "640HP72", "date": "2016-02-06", "version": "1.4.3", "manufacturers_id": "Dell Inc.", "designation": "Dell Inc. BIOS", "devicefirmwaretypes_id": "BIOS"}'
            ], [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <BIOS>
      <BVERSION>IM51.0090.B09</BVERSION>
      <SMANUFACTURER>Apple Computer, Inc.</SMANUFACTURER>
      <SMODEL>iMac5,1</SMODEL>
      <SSN>W87051UGVUV</SSN>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"bversion": "IM51.0090.B09", "smanufacturer": "Apple Computer, Inc.", "smodel": "iMac5,1", "ssn": "W87051UGVUV", "version": "IM51.0090.B09", "designation": " BIOS", "devicefirmwaretypes_id": "BIOS"}'
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
        $asset = new \Glpi\Inventory\Asset\Bios($computer, (array)$json->content->bios);
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
        $asset = new \Glpi\Inventory\Asset\Bios($computer, (array)$json->content->bios);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->object($result[0])->isEqualTo(json_decode($expected['expected']));

       //handle
        $asset->handleLinks();
        $asset->handle();
        $this->boolean($idf->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isTrue('Firmware has not been linked to computer :(');
    }

    public function testInventoryUpdate()
    {
        $computer = new \Computer();
        $device_bios = new \DeviceFirmware();
        $item_bios = new \Item_DeviceFirmware();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <HARDWARE>
      <NAME>pc002</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>ggheb7ne7</SSN>
      <BMANUFACTURER>Dell Inc.</BMANUFACTURER>
      <BVERSION>1.4.3</BVERSION>
      <MMANUFACTURER>Dell Inc.</MMANUFACTURER>
      <MMODEL>07TYC2</MMODEL>
      <MSN>/640HP72/CN129636460078/</MSN>
      <SKUNUMBER>0704</SKUNUMBER>
      <SMANUFACTURER>Dell Inc.</SMANUFACTURER>
      <SMODEL>XPS 13 9350</SMODEL>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Agent_v2.3.19</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>test-pc002</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

        $type = new \DeviceFirmwareType();
        $type->getFromDBByCrit([
            'name' => 'BIOS'
        ]);
        $types_id = $type->getID();

        $manufacturer = new \Manufacturer();
        $manufacturers_id = $manufacturer->add([
            'name' => 'Dell Inc.'
        ]);
        $this->integer($manufacturers_id)->isGreaterThan(0);

        //create manually a computer, with a bios
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0
        ]);
        $this->integer($computers_id)->isGreaterThan(0);

        $bios_id = $device_bios->add([
            'designation' => 'Dell Inc. BIOS',
            'devicefirmwaretypes_id' => $types_id,
            'manufacturers_id' => $manufacturers_id,
            'version' => '1.4.3'
        ]);

        $item_bios_id = $item_bios->add([
            'items_id' => $computers_id,
            'itemtype' => 'Computer',
            'devicefirmwares_id' => $bios_id
        ]);
        $this->integer($item_bios_id)->isGreaterThan(0);

        $firmwares = $item_bios->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($firmwares))->isIdenticalTo(1);
        foreach ($firmwares as $firmware) {
            $this->variable($firmware['is_dynamic'])->isEqualTo(0);
        }

        //computer inventory knows bios
        $this->doInventory($xml_source, true);

        //we still have 1 bios linked to the computer
        $firmwares = $item_bios->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($firmwares))->isIdenticalTo(1);

        //bios present in the inventory source is now dynamic
        $firmwares = $item_bios->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($firmwares))->isIdenticalTo(1);

        //Redo inventory, but with modified firmware => will create a new one
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <HARDWARE>
      <NAME>pc002</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>ggheb7ne7</SSN>
      <BMANUFACTURER>Dell Inc.</BMANUFACTURER>
      <BVERSION>1.4.4</BVERSION>
      <MMANUFACTURER>Dell Inc.</MMANUFACTURER>
      <MMODEL>07TYC2</MMODEL>
      <MSN>/640HP72/CN129636460078/</MSN>
      <SKUNUMBER>0704</SKUNUMBER>
      <SMANUFACTURER>Dell Inc.</SMANUFACTURER>
      <SMODEL>XPS 13 9350</SMODEL>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Agent_v2.3.19</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>test-pc002</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

        $this->doInventory($xml_source, true);

        //we still have one firmware
        $firmwares = $item_bios->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($firmwares))->isIdenticalTo(1);

        //bios present in the inventory source is still dynamic
        $firmwares = $item_bios->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($firmwares))->isIdenticalTo(1);

        //"original" firmware has been removed
        $this->boolean($item_bios->getFromDB($item_bios_id))->isFalse();
    }
}
