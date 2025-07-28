<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Inventory\Asset\Controller;
use Glpi\Inventory\Converter;
use PHPUnit\Framework\Attributes\DataProvider;

include_once __DIR__ . '/../../../../abstracts/AbstractInventoryAsset.php';

/* Test for inc/inventory/asset/controller.class.php */

class ControllerTest extends AbstractInventoryAsset
{
    public static function assetProvider(): array
    {
        return [
            [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
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
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"caption": "Xeon E3-1200 v5/E3-1500 v5/6th Gen Core Processor Host Bridge/DRAM Registers", "driver": "skl_uncore", "manufacturer": "Intel Corporation", "name": "Xeon E3-1200 v5/E3-1500 v5/6th Gen Core Processor Host Bridge/DRAM Registers", "pciclass": "0600", "pcislot": "00:00.0", "productid": "1904", "rev": "08", "type": "Host bridge", "vendorid": "8086", "designation": "Xeon E3-1200 v5/E3-1500 v5/6th Gen Core Processor Host Bridge/DRAM Registers", "manufacturers_id": "Intel Corporation", "interfacetypes_id": "Host bridge", "is_dynamic": 1}',
            ], [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
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
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"caption": "NVMe SSD Controller SM951/PM951", "driver": "nvme", "manufacturer": "Samsung Electronics Co Ltd", "name": "NVMe SSD Controller SM951/PM951", "pciclass": "0108", "pcislot": "3c:00.0", "productid": "a802", "rev": "01", "type": "Non-Volatile memory controller", "vendorid": "144d", "designation": "NVMe SSD Controller SM951/PM951", "manufacturers_id": "Samsung Electronics Co Ltd", "interfacetypes_id": "Non-Volatile memory controller", "is_dynamic": 1}',
            ],
        ];
    }

    #[DataProvider('assetProvider')]
    public function testPrepare($xml, $expected)
    {
        $converter = new Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new Controller($computer, $json->content->controllers);
        $asset->setExtraData((array) $json->content);
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected), $result[0]);
    }

    public function testHandle()
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');

        //first, check there are no controller linked to this computer
        $idc = new \Item_DeviceControl();
        $this->assertFalse(
            $idc->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']),
            'A controller is already linked to computer!'
        );

        //convert data
        $expected = $this->assetProvider()[0];

        $converter = new Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new Controller($computer, $json->content->controllers);
        $asset->setExtraData((array) $json->content);
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected['expected']), $result[0]);

        //handle
        $asset->handleLinks();
        $asset->handle();
        $this->assertTrue(
            $idc->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']),
            'Controller has not been linked to computer :('
        );
    }

    public function testInventoryUpdate()
    {
        $computer = new \Computer();
        $device_control = new \DeviceControl();
        $item_control = new \Item_DeviceControl();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
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

        //create manually a computer, with 3 controllers
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $manufacturer = new \Manufacturer();
        $manufacturers_id = $manufacturer->add([
            'name' => 'Intel Corporation',
        ]);
        $this->assertGreaterThan(0, $manufacturers_id);

        $controller_1_id = $device_control->add([
            'designation' => 'Xeon E3-1200 v5/E3-1500 v5/6th Gen Core Processor Host Bridge/DRAM Registers',
            'manufacturers_id' => $manufacturers_id,
            'entities_id'  => 0,
        ]);
        $this->assertGreaterThan(0, $controller_1_id);

        $item_controller_1_id = $item_control->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicecontrols_id' => $controller_1_id,
        ]);
        $this->assertGreaterThan(0, $item_controller_1_id);

        $manufacturer = new \Manufacturer();
        $manufacturers_id = $manufacturer->add([
            'name' => 'Samsung Electronics Co Ltd',
        ]);
        $this->assertGreaterThan(0, $manufacturers_id);

        $controller_2_id = $device_control->add([
            'designation' => 'NVMe SSD Controller SM951/PM951',
            'manufacturers_id' => $manufacturers_id,
            'entities_id'  => 0,
        ]);
        $this->assertGreaterThan(0, $controller_2_id);

        $item_controller_2_id = $item_control->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicecontrols_id' => $controller_2_id,
        ]);
        $this->assertGreaterThan(0, $item_controller_2_id);

        $controller_3_id = $device_control->add([
            'designation' => 'My Controller',
            'manufacturers_id' => $manufacturers_id,
            'entities_id'  => 0,
        ]);
        $this->assertGreaterThan(0, $controller_3_id);

        $item_controller_3_id = $item_control->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicecontrols_id' => $controller_3_id,
        ]);
        $this->assertGreaterThan(0, $item_controller_3_id);

        $controllers = $item_control->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(3, $controllers);
        foreach ($controllers as $controller) {
            $this->assertEquals(0, $controller['is_dynamic']);
        }

        //computer inventory knows only "Xeon" and "NVMe SSD" controllers
        $this->doInventory($xml_source, true);

        //we still have 3 controllers
        $controllers = $device_control->find();
        $this->assertCount(3, $controllers);

        //we still have 3 controllers items linked to the computer
        $controllers = $item_control->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(3, $controllers);

        //controllers present in the inventory source are now dynamic
        $controllers = $item_control->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(2, $controllers);

        //controller not present in the inventory is still not dynamic
        $controllers = $item_control->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->assertCount(1, $controllers);

        //Redo inventory, but with removed "NVMe SSD" controller
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
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

        //we still have 3 controllers
        $controllers = $device_control->find();
        $this->assertCount(3, $controllers);

        //we now have 2 controllers linked to computer only
        $controllers = $item_control->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(2, $controllers);

        //controller present in the inventory source is still dynamic
        $controllers = $item_control->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $controllers);

        //controller not present in the inventory is still not dynamic
        $controllers = $item_control->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->assertCount(1, $controllers);
    }
}
