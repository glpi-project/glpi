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

class BiosTest extends AbstractInventoryAsset
{
    public static function assetProvider(): array
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
        $this->assertEquals(json_decode($expected), $result[0]);
    }

    public function testHandle()
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');

       //first, check there are no controller linked to this computer
        $idf = new \Item_DeviceFirmware();
                 $this->assertFalse(
                     $idf->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']),
                     'A firmware is already linked to computer!'
                 );

       //convert data
        $expected = $this->assetProvider()[0];

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\Bios($computer, (array)$json->content->bios);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected['expected']), $result[0]);

       //handle
        $asset->handleLinks();
        $asset->handle();
        $this->assertTrue(
            $idf->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']),
            'Firmware has not been linked to computer :('
        );
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
      <SSN>ggheb7'ne7</SSN>
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
        $this->assertGreaterThan(0, $manufacturers_id);

        //create manually a computer, with a bios
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => addslashes("ggheb7'ne7"),
            'entities_id' => 0
        ]);
        $this->assertGreaterThan(0, $computers_id);

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
        $this->assertGreaterThan(0, $item_bios_id);

        $firmwares = $item_bios->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(1, $firmwares);
        foreach ($firmwares as $firmware) {
            $this->assertEquals(0, $firmware['is_dynamic']);
        }

        //computer inventory knows bios
        $this->doInventory($xml_source, true);

        //we still have 1 bios linked to the computer
        $firmwares = $item_bios->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(1, $firmwares);

        //bios present in the inventory source is now dynamic
        $firmwares = $item_bios->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $firmwares);

        //Redo inventory, but with modified firmware => will create a new one
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <HARDWARE>
      <NAME>pc002</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>ggheb7'ne7</SSN>
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
        $this->assertCount(1, $firmwares);

        //bios present in the inventory source is still dynamic
        $firmwares = $item_bios->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $firmwares);

        //"original" firmware has been removed
        $this->assertFalse($item_bios->getFromDB($item_bios_id));
    }

    public function testHistory()
    {
        global $DB;
        $item_bios = new \Item_DeviceFirmware();

        $xml_source = '<?xml version="1.0" encoding="UTF-8"?>
        <REQUEST>
          <CONTENT>
            <DEVICE>
              <FIRMWARES>
                <DESCRIPTION>device firmware</DESCRIPTION>
                <MANUFACTURER>Cisco</MANUFACTURER>
                <NAME>SG300-10MP</NAME>
                <TYPE>device</TYPE>
                <VERSION>1.4.12</VERSION>
              </FIRMWARES>
              <INFO>
                <COMMENTS>SG300-10MPP 10-Port Gigabit PoE+ Managed Switch</COMMENTS>
                <CONTACT>TECLIB</CONTACT>
                <FIRMWARE>1.4.12</FIRMWARE>
                <ID>2304</ID>
                <IPS>
                  <IP>10.10.10.10</IP>
                  <IP>127.0.0.1</IP>
                </IPS>
                <LOCATION>Teclib Caaen</LOCATION>
                <MAC>cc:8e:71:fb:42:1b</MAC>
                <MANUFACTURER>Cisco</MANUFACTURER>
                <MODEL>SG300-10MP</MODEL>
                <NAME>TECLIB678</NAME>
                <SERIAL>SDGFSDF51687SDF</SERIAL>
                <TYPE>NETWORKING</TYPE>
                <UPTIME>5 hours, 35:46.00</UPTIME>
              </INFO>
            </DEVICE>
            <MODULEVERSION>5.1</MODULEVERSION>
            <PROCESSNUMBER>6465205</PROCESSNUMBER>
          </CONTENT>
          <DEVICEID>teclib-home-2022-09-14-14-57-39</DEVICEID>
          <QUERY>SNMPQUERY</QUERY>
        </REQUEST>';

        //computer inventory knows bios
        $this->doInventory($xml_source, true);

        $networkequipement = new \NetworkEquipment();
        $networkequipement->getFromDBByCrit(['name' => 'TECLIB678']);
        $this->assertGreaterThan(0, $networkequipement->getID());

        //bios present in the inventory source is dynamic
        $firmwares = $item_bios->find(['itemtype' => \NetworkEquipment::class, 'items_id' => $networkequipement->getID(), 'is_dynamic' => 1]);
        $this->assertCount(1, $firmwares);

        //no log from first import (Computer or Monitor)
        $nblogsnow = countElementsInTable(\Log::getTable());
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'LIMIT' => $nblogsnow,
            'OFFSET' => $this->nblogs,
            'WHERE' => [
                'itemtype' => \NetworkEquipment::class,
                'items_id' => $networkequipement->getID(),
                'itemtype_link' => \DeviceFirmware::class
            ]
        ]);
        $this->assertCount(0, $logs);

        // change version
        // As the version is one of the reconciliation keys, we should see a deletion and then an addition.
        $xml_source = '<?xml version="1.0" encoding="UTF-8"?>
        <REQUEST>
          <CONTENT>
            <DEVICE>
              <FIRMWARES>
                <DESCRIPTION>device firmware</DESCRIPTION>
                <MANUFACTURER>Cisco</MANUFACTURER>
                <NAME>SG300-10MP</NAME>
                <TYPE>device</TYPE>
                <VERSION>1.4.13</VERSION>
              </FIRMWARES>
              <INFO>
                <COMMENTS>SG300-10MPP 10-Port Gigabit PoE+ Managed Switch</COMMENTS>
                <CONTACT>TECLIB</CONTACT>
                <FIRMWARE>1.4.13</FIRMWARE>
                <ID>2304</ID>
                <IPS>
                  <IP>10.10.10.10</IP>
                  <IP>127.0.0.1</IP>
                </IPS>
                <LOCATION>Teclib Caaen</LOCATION>
                <MAC>cc:8e:71:fb:42:1b</MAC>
                <MANUFACTURER>Cisco</MANUFACTURER>
                <MODEL>SG300-10MP</MODEL>
                <NAME>TECLIB678</NAME>
                <SERIAL>SDGFSDF51687SDF</SERIAL>
                <TYPE>NETWORKING</TYPE>
                <UPTIME>5 hours, 35:46.00</UPTIME>
              </INFO>
            </DEVICE>
            <MODULEVERSION>5.1</MODULEVERSION>
            <PROCESSNUMBER>6465205</PROCESSNUMBER>
          </CONTENT>
          <DEVICEID>teclib-home-2022-09-14-14-57-39</DEVICEID>
          <QUERY>SNMPQUERY</QUERY>
        </REQUEST>';
        $this->doInventory($xml_source, true);


        $networkequipement = new \NetworkEquipment();
        $networkequipement->getFromDBByCrit(['name' => 'TECLIB678']);
        $this->assertGreaterThan(0, $networkequipement->getID());

        //bios present in the inventory source is dynamic
        $firmwares = $item_bios->find(['itemtype' => \NetworkEquipment::class, 'items_id' => $networkequipement->getID(), 'is_dynamic' => 1]);
        $this->assertCount(1, $firmwares);

        //one log for delete old firmware
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'WHERE' => [
                'itemtype' => \NetworkEquipment::class,
                'items_id' => $networkequipement->getID(),
                'itemtype_link' => \DeviceFirmware::class,
                'linked_action' => \Log::HISTORY_DELETE_DEVICE
            ]
        ]);

        $this->assertCount(1, $logs);

        //one log for add new firmware
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'WHERE' => [
                'itemtype' => \NetworkEquipment::class,
                'items_id' => $networkequipement->getID(),
                'itemtype_link' => \DeviceFirmware::class,
                'linked_action' => \Log::HISTORY_ADD_DEVICE
            ]
        ]);

        $this->assertCount(1, $logs);
    }
}
