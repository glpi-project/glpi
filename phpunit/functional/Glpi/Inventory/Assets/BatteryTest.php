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

/* Test for inc/inventory/asset/battery.class.php */

class BatteryTest extends AbstractInventoryAsset
{
    public static function assetProvider(): array
    {
        return [
            [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <BATTERIES>
      <CAPACITY>43.7456 Wh</CAPACITY>
      <CHEMISTRY>lithium-polymer</CHEMISTRY>
      <DATE>10/11/2015</DATE>
      <MANUFACTURER>SMP</MANUFACTURER>
      <NAME>DELL JHXPY53</NAME>
      <SERIAL>3701</SERIAL>
      <VOLTAGE>8.614 V</VOLTAGE>
    </BATTERIES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"capacity": "43746", "chemistry": "lithium-polymer", "date": "2015-11-10", "manufacturer": "SMP", "name": "DELL JHXPY53", "serial": "3701", "voltage": "8614", "designation": "DELL JHXPY53", "manufacturers_id": "SMP", "manufacturing_date": "2015-11-10", "devicebatterytypes_id": "lithium-polymer", "is_dynamic": 1}'
            ], [ //no voltage
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <BATTERIES>
      <CAPACITY>43.7456 Wh</CAPACITY>
      <CHEMISTRY>lithium-polymer</CHEMISTRY>
      <DATE>10/11/2015</DATE>
      <MANUFACTURER>SMP</MANUFACTURER>
      <NAME>DELL JHXPY53</NAME>
      <SERIAL>3701</SERIAL>
    </BATTERIES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"capacity": "43746", "chemistry": "lithium-polymer", "date": "2015-11-10", "manufacturer": "SMP", "name": "DELL JHXPY53", "serial": "3701", "voltage": "0", "designation": "DELL JHXPY53", "manufacturers_id": "SMP", "manufacturing_date": "2015-11-10", "devicebatterytypes_id": "lithium-polymer", "is_dynamic": 1}'
            ], [ //empty info
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <BATTERIES>
      <CAPACITY>0</CAPACITY>
      <CHEMISTRY>Li-ION</CHEMISTRY>
      <MANUFACTURER>OTHER MANU</MANUFACTURER>
      <NAME></NAME>
      <SERIAL>00000000</SERIAL>
    </BATTERIES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"chemistry": "Li-ION", "manufacturer": "OTHER MANU", "serial": "00000000", "voltage": "0", "capacity": "0", "manufacturers_id": "OTHER MANU", "devicebatterytypes_id": "Li-ION", "is_dynamic": 1}'
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
        $asset = new \Glpi\Inventory\Asset\Battery($computer, $json->content->batteries);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected), $result[0]);
    }

    public function testHandle()
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');

        //first, check there are no battery linked to this computer
        $idb = new \Item_DeviceBattery();
        $this->assertFalse(
            $idb->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']),
            'A battery is already linked to computer!'
        );

        //convert data
        $expected = $this->assetProvider()[0];

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\Battery($computer, $json->content->batteries);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected['expected']), $result[0]);

        //handle
        $asset->handleLinks();
        $asset->handle();
        $this->assertTrue(
            $idb->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']),
            'Battery has not been linked to computer :('
        );
    }

    public function testHandleNoFullInfo()
    {
        $computer = getItemByTypeName('Computer', '_test_pc02');

        //first, check there are no battery linked to this computer
        $idb = new \Item_DeviceBattery();
        $this->assertFalse(
            $idb->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']),
            'A battery is already linked to computer!'
        );

        //convert data
        $expected = $this->assetProvider()[2];

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc02');
        $asset = new \Glpi\Inventory\Asset\Battery($computer, $json->content->batteries);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected['expected']), $result[0]);

        //handle
        $asset->handleLinks();
        $asset->handle();
        $this->assertTrue(
            $idb->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']),
            'Battery has not been linked to computer :('
        );
    }

    public function testInventoryUpdate()
    {
        $computer = new \Computer();
        $device_battery = new \DeviceBattery();
        $item_battery = new \Item_DeviceBattery();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <BATTERIES>
      <CAPACITY>43.7456 Wh</CAPACITY>
      <CHEMISTRY>lithium-polymer</CHEMISTRY>
      <DATE>10/11/2015</DATE>
      <MANUFACTURER>SMP</MANUFACTURER>
      <NAME>DELL JHXPY53</NAME>
      <SERIAL>3701</SERIAL>
      <VOLTAGE>8.614 V</VOLTAGE>
    </BATTERIES>
    <BATTERIES>
      <CAPACITY>45280</CAPACITY>
      <CHEMISTRY>lithium-polymer</CHEMISTRY>
      <DATE>17/07/2020</DATE>
      <MANUFACTURER>SMP</MANUFACTURER>
      <NAME>5B10W138</NAME>
      <SERIAL>7565</SERIAL>
      <VOLTAGE>11100</VOLTAGE>
    </BATTERIES>
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

       //create manually a computer, with 3 batteries
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $manufacturer = new \Manufacturer();
        $manufacturers_id = $manufacturer->add([
            'name' => 'SMP'
        ]);
        $this->assertGreaterThan(0, $manufacturers_id);

        $batterytype = new \DeviceBatteryType();
        $types_id = $batterytype->add([
            'name' => 'lithium-polymer'
        ]);
        $this->assertGreaterThan(0, $types_id);

        $battery_1_id = $device_battery->add([
            'designation' => 'DELL JHXPY53',
            'manufacturers_id' => $manufacturers_id,
            'devicebatterytypes_id' => $types_id,
            'voltage' => 8614,
            'capacity' => 43746,
            'entities_id'  => 0,
        ]);
        $this->assertGreaterThan(0, $battery_1_id);

        $item_battery_1_id = $item_battery->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicebatteries_id' => $battery_1_id,
            'serial' => '3701'
        ]);

        $battery_2_id = $device_battery->add([
            'designation' => '5B10W138',
            'manufacturers_id' => $manufacturers_id,
            'devicebatterytypes_id' => $types_id,
            'voltage' => 11100,
            'capacity' => 45280,
            'entities_id'  => 0,
        ]);
        $this->assertGreaterThan(0, $battery_2_id);

        $item_battery_2_id = $item_battery->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicebatteries_id' => $battery_2_id,
            'serial' => '7565'
        ]);

        $battery_3_id = $device_battery->add([
            'designation' => 'test battery',
            'manufacturers_id' => $manufacturers_id,
            'devicebatterytypes_id' => $types_id,
            'entities_id'  => 0
        ]);
        $this->assertGreaterThan(0, $battery_3_id);

        $item_battery_3_id = $item_battery->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicebatteries_id' => $battery_3_id
        ]);
        $this->assertGreaterThan(0, $item_battery_3_id);

        $disks = $item_battery->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(3, $disks);
        foreach ($disks as $disk) {
            $this->assertEquals(0, $disk['is_dynamic']);
        }

        //computer inventory knows only "DELL JHXPY53" and "5B10W138" batteries
        $this->doInventory($xml_source, true);

        //we still have 3 batteries
        $batteries = $device_battery->find();
        $this->assertCount(3, $batteries);

        //we still have 3 batteries items linked to the computer
        $batteries = $item_battery->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(3, $batteries);

        //batteries present in the inventory source are now dynamic
        $batteries = $item_battery->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(2, $batteries);

        //disk not present in the inventory is still not dynamic
        $batteries = $item_battery->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->assertCount(1, $batteries);

        //Redo inventory, but with removed battery "5B10W138"
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <BATTERIES>
      <CAPACITY>43.7456 Wh</CAPACITY>
      <CHEMISTRY>lithium-polymer</CHEMISTRY>
      <DATE>10/11/2015</DATE>
      <MANUFACTURER>SMP</MANUFACTURER>
      <NAME>DELL JHXPY53</NAME>
      <SERIAL>3701</SERIAL>
      <VOLTAGE>8.614 V</VOLTAGE>
    </BATTERIES>
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

        //we still have 3 batteries
        $batteries = $device_battery->find();
        $this->assertCount(3, $batteries);

        //we now have 2 batteries linked to computer only
        $batteries = $item_battery->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(2, $batteries);

        //battery present in the inventory source is still dynamic
        $batteries = $item_battery->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $batteries);

        //battery not present in the inventory is still not dynamic
        $batteries = $item_battery->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->assertCount(1, $batteries);
    }
}
