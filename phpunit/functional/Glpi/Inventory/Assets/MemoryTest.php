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

/* Test for inc/inventory/asset/memory.class.php */

class MemoryTest extends AbstractInventoryAsset
{
    public static function assetProvider(): array
    {
        return [
            [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <MEMORIES>
      <CAPACITY>4096</CAPACITY>
      <CAPTION>System Board Memory</CAPTION>
      <DESCRIPTION>Chip</DESCRIPTION>
      <MANUFACTURER>Elpida</MANUFACTURER>
      <MEMORYCORRECTION>None</MEMORYCORRECTION>
      <NUMSLOTS>1</NUMSLOTS>
      <SERIALNUMBER>12161217</SERIALNUMBER>
      <SPEED>1867</SPEED>
      <TYPE>LPDDR3</TYPE>
    </MEMORIES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"capacity": 4096, "caption": "System Board Memory", "description": "Chip", "manufacturer": "Elpida", "memorycorrection": "None", "numslots": 1, "serialnumber": "12161217", "speed": "1867", "type": "LPDDR3", "size": 4096, "frequence": "1867", "manufacturers_id": "Elpida", "devicememorytypes_id": "LPDDR3", "serial": "12161217", "busID": 1, "designation": "LPDDR3 - Chip", "is_dynamic": 1}'
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
        $asset = new \Glpi\Inventory\Asset\Memory($computer, $json->content->memories);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected), $result[0]);
    }

    public function testHandle()
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');

       //first, check there are no controller linked to this computer
        $idm = new \Item_DeviceMemory();
                 $this->assertFalse(
                     $idm->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']),
                     'A memory is already linked to computer!'
                 );

       //convert data
        $expected = $this->assetProvider()[0];

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\Memory($computer, $json->content->memories);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected['expected']), $result[0]);

       //handle
        $asset->handleLinks();
        $asset->handle();
        $this->assertTrue(
            $idm->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']),
            'Memory has not been linked to computer :('
        );
    }

    public function testInventoryUpdate()
    {
        $computer = new \Computer();
        $device_mem = new \DeviceMemory();
        $item_mem = new \Item_DeviceMemory();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <MEMORIES>
      <CAPACITY>8192</CAPACITY>
      <CAPTION>Bottom-Slot 1(left)</CAPTION>
      <DESCRIPTION>SODIMM</DESCRIPTION>
      <MANUFACTURER>Samsung</MANUFACTURER>
      <MEMORYCORRECTION>None</MEMORYCORRECTION>
      <NUMSLOTS>1</NUMSLOTS>
      <SERIALNUMBER>97842456</SERIALNUMBER>
      <SPEED>2133</SPEED>
      <TYPE>DDR4</TYPE>
    </MEMORIES>
    <MEMORIES>
      <CAPACITY>8192</CAPACITY>
      <CAPTION>Bottom-Slot 2(right)</CAPTION>
      <DESCRIPTION>SODIMM</DESCRIPTION>
      <MANUFACTURER>Samsung</MANUFACTURER>
      <MEMORYCORRECTION>None</MEMORYCORRECTION>
      <NUMSLOTS>1</NUMSLOTS>
      <SERIALNUMBER>97842457</SERIALNUMBER>
      <SPEED>2133</SPEED>
      <TYPE>DDR4</TYPE>
    </MEMORIES>
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

       //create manually a computer, with 3 memories
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $manufacturer = new \Manufacturer();
        $manufacturers_id = $manufacturer->add([
            'name' => 'Samsung'
        ]);
        $this->assertGreaterThan(0, $manufacturers_id);

        $type = new \DeviceMemoryType();
        $types_id = $type->add([
            'name' => 'DDR4'
        ]);
        $this->assertGreaterThan(0, $types_id);

        $mem_1_id = $device_mem->add([
            'designation' => 'DDR4 - SODIMM',
            'manufacturers_id' => $manufacturers_id,
            'devicememorytypes_id' => $types_id,
            'frequence' => '2133',
            'entities_id'  => 0
        ]);
        $this->assertGreaterThan(0, $mem_1_id);

        $item_mem_1_id = $item_mem->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicememories_id' => $mem_1_id,
            'serial' => '97842456',
            'size' => '8192'
        ]);
        $this->assertGreaterThan(0, $item_mem_1_id);

        $item_mem_2_id = $item_mem->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicememories_id' => $mem_1_id,
            'serial' => '97842457',
            'size' => '8192'
        ]);
        $this->assertGreaterThan(0, $item_mem_2_id);

        $mem_3_id = $device_mem->add([
            'designation' => 'DDR3 - SODIMM',
            'manufacturers_id' => $manufacturers_id,
            'devicememorytypes_id' => $types_id,
            'frequence' => '2133',
            'entities_id'  => 0
        ]);
        $this->assertGreaterThan(0, $mem_3_id);

        $item_mem_3_id = $item_mem->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicememories_id' => $mem_3_id
        ]);
        $this->assertGreaterThan(0, $item_mem_3_id);

        $memories = $item_mem->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(3, $memories);
        foreach ($memories as $memory) {
            $this->assertEquals(0, $memory['is_dynamic']);
        }

       //computer inventory knows only "Bottom-Slot 1(left)" and "Bottom-Slot 2(right)" memories
        $this->doInventory($xml_source, true);

       //we still have 2 memory devices
        $memories = $device_mem->find();
        $this->assertCount(2, $memories);

       //we still have 3 memories items linked to the computer
        $memories = $item_mem->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(3, $memories);

       //memories present in the inventory source are now dynamic
        $memories = $item_mem->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(2, $memories);

       //memory not present in the inventory is still not dynamic
        $memories = $item_mem->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->assertCount(1, $memories);

       //Redo inventory, but with removed "Bottom-Slot 2(right)" memory
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <MEMORIES>
      <CAPACITY>8192</CAPACITY>
      <CAPTION>Bottom-Slot 1(left)</CAPTION>
      <DESCRIPTION>SODIMM</DESCRIPTION>
      <MANUFACTURER>Samsung</MANUFACTURER>
      <MEMORYCORRECTION>None</MEMORYCORRECTION>
      <NUMSLOTS>1</NUMSLOTS>
      <SERIALNUMBER>97842456</SERIALNUMBER>
      <SPEED>2133</SPEED>
      <TYPE>DDR4</TYPE>
    </MEMORIES>
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

       //we now have only 2 memories
        $memories = $device_mem->find();
        $this->assertCount(2, $memories);

       //we now have 2 memories linked to computer only
        $memories = $item_mem->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(2, $memories);

       //memory present in the inventory source is still dynamic
        $memories = $item_mem->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $memories);

       //memory not present in the inventory is still not dynamic
        $memories = $item_mem->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->assertCount(1, $memories);
    }
}
