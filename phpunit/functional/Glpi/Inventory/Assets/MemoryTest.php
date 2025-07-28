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

use Glpi\Inventory\Asset\Memory;
use Glpi\Inventory\Converter;
use PHPUnit\Framework\Attributes\DataProvider;

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
      <MODEL>EBJ81UG8BBU5GNF</MODEL>
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
                'expected'  => '{"capacity": 4096, "caption": "System Board Memory", "description": "Chip", "manufacturer": "Elpida", "model": "EBJ81UG8BBU5GNF", "memorycorrection": "None", "numslots": 1, "serialnumber": "12161217", "speed": "1867", "type": "LPDDR3", "size": 4096, "frequence": "1867", "manufacturers_id": "Elpida", "devicememorymodels_id": "EBJ81UG8BBU5GNF", "devicememorytypes_id": "LPDDR3", "serial": "12161217", "busID": 1, "designation": "LPDDR3 - 1867 - Chip", "is_dynamic": 1}',
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
        $asset = new Memory($computer, $json->content->memories);
        $asset->setExtraData((array) $json->content);
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

        $converter = new Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new Memory($computer, $json->content->memories);
        $asset->setExtraData((array) $json->content);
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
        $mem_model = new \DeviceMemoryModel();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <MEMORIES>
      <CAPACITY>8192</CAPACITY>
      <CAPTION>Bottom-Slot 1(left)</CAPTION>
      <DESCRIPTION>SODIMM</DESCRIPTION>
      <MANUFACTURER>Samsung</MANUFACTURER>
      <MODEL>MODEL-A</MODEL>
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
      <MODEL>MODEL-A</MODEL>
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
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $manufacturer = new \Manufacturer();
        $manufacturers_id = $manufacturer->add([
            'name' => 'Samsung',
        ]);
        $this->assertGreaterThan(0, $manufacturers_id);

        $mem_model_id = $mem_model->add([
            'name' => 'MODEL-A',
        ]);
        $this->assertGreaterThan(0, $mem_model_id);

        $type = new \DeviceMemoryType();
        $types_id = $type->add([
            'name' => 'DDR4',
        ]);
        $this->assertGreaterThan(0, $types_id);

        $mem_1_id = $device_mem->add([
            'designation' => 'DDR4 - 2133 - SODIMM',
            'manufacturers_id' => $manufacturers_id,
            'devicememorymodels_id' => $mem_model_id,
            'devicememorytypes_id' => $types_id,
            'frequence' => '2133',
            'entities_id'  => 0,
        ]);
        $this->assertGreaterThan(0, $mem_1_id);

        $item_mem_1_id = $item_mem->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicememories_id' => $mem_1_id,
            'serial' => '97842456',
            'size' => '8192',
        ]);
        $this->assertGreaterThan(0, $item_mem_1_id);

        $item_mem_2_id = $item_mem->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicememories_id' => $mem_1_id,
            'serial' => '97842457',
            'size' => '8192',
        ]);
        $this->assertGreaterThan(0, $item_mem_2_id);

        $mem_3_id = $device_mem->add([
            'designation' => 'DDR3 - 2133 - SODIMM',
            'manufacturers_id' => $manufacturers_id,
            'devicememorytypes_id' => $types_id,
            'frequence' => '2133',
            'entities_id'  => 0,
        ]);
        $this->assertGreaterThan(0, $mem_3_id);

        $item_mem_3_id = $item_mem->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicememories_id' => $mem_3_id,
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

        //and one memory model
        $this->assertSame(
            1,
            countElementsInTable($mem_model->getTable())
        );

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
        //and a different memory model in Slot 1
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

        //we now have 3 memories
        // 'DDR4 - 2133 - SODIMM' and 'DDR3 - 2133 - SODIMM'
        $this->assertCount(2, $device_mem->find(['devicememorymodels_id' => null]));
        // 'DDR4 - 2133 - SODIMM' (MODEL-A)
        $this->assertCount(1, $device_mem->find(['devicememorymodels_id' => $mem_model_id]));

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

    public function testMemoryOneManufactuerOneNot()
    {
        $device_mem = new \DeviceMemory();
        $item_mem = new \Item_DeviceMemory();

        $json_str = <<<JSON
{
   "action": "inventory",
   "content": {
      "hardware": {
         "name": "pc_with_memories",
         "uuid": "32EED9C2-204C-42A1-A97E-A6EF2CE44B3E"
      },
      "memories": [
         {
            "capacity": 8192,
            "caption": "DIMM 0",
            "description": "SODIMM",
            "manufacturer": "Hynix",
            "model": "HMAA1GS6CJR6N-XN",
            "numslots": 1,
            "serialnumber": "953E68BA",
            "speed": "3200",
            "type": "DDR4"
         },
         {
            "capacity": 16384,
            "caption": "DIMM 0",
            "description": "SODIMM",
            "model": "CT16G4SFRA32A.C16FP",
            "numslots": 2,
            "serialnumber": "E72ADEC5",
            "speed": "3200",
            "type": "DDR4"
         }
      ],
      "versionclient": "GLPI-Inventory_v1.xx"
   },
   "deviceid": "Inspiron-5515-2024-02-01-08-19-36",
   "itemtype": "Computer"
}
JSON;
        $json = json_decode($json_str);

        //initial import
        $this->doInventory($json);

        $computer = new \Computer();
        $this->assertTrue(
            $computer->getFromDBByCrit([
                'name' => 'pc_with_memories', // a computer that remembers
            ])
        );
        $computers_id = $computer->fields['id'];

        //we have 2 memories items linked to the computer
        $memories = $item_mem->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(
            2,
            $memories,
            print_r($memories, true)
        );

        $manufacturer = new \Manufacturer();
        $manufacturers = $manufacturer->find();
        //2 manufacturers ine db: "Hynix" from current inv, "My Manufacturer" from bootstrap data
        $this->assertCount(
            2,
            $manufacturers,
            print_r($manufacturers, true)
        );

        //we have 2 memory devices: one for Hynix, one without manufacturer
        $memories = $device_mem->find();
        $this->assertCount(
            2,
            $memories,
            print_r($memories, true)
        );
    }
}
