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

class DeviceTest extends AbstractInventoryAsset
{
    public static function assetProvider(): array
    {
        return [];
    }

    public function testInventoryUpdateMemory()
    {
        global $DB;
        $item_mem = new \Item_DeviceMemory();
        $info_com = new \Infocom();

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


        $this->doInventory($xml_source, true);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();
        $computers_id = $agent['items_id'];
        $this->assertGreaterThan(0, $computers_id);
        $computer = new \Computer();
        $computer->getFromDB($computers_id);

        //memories present from the inventory source are dynamic
        $memories = $item_mem->find(['itemtype' => \Computer::class, 'items_id' => $computers_id, 'is_dynamic' => 1]);

        $this->assertCount(2, $memories);

        $data_to_check = [];

        $infocom_buy_date = '2020-10-21';
        $infocom_value = '300';
        foreach ($memories as $result) {
            $item_device_memory_id = $result['id'];
            $input = [
                "items_id" => $item_device_memory_id,
                "itemtype" => \Item_DeviceMemory::class,
                "entities_id" => $computer->fields['entities_id'],
                "buy_date" => $infocom_buy_date,
                "value" => $infocom_value
            ];
            //create infocom
            $id = $info_com->add($input);
            $this->assertGreaterThan(0, $id);
            $input['id'] = $id;
            $data_to_check[] = $input;
        }

        //redo an inventory
        $this->doInventory($xml_source, true);

        //memory present in the inventory source is still dynamic
        $memories = $item_mem->find(['itemtype' =>  \Computer::class, 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(2, $memories);

        //check item_device_memory is the same from first step
        foreach ($data_to_check as $data_value) {
            $memories = $item_mem->find(['id' => $data_value['items_id'], "itemtype" => \Computer::class, 'is_dynamic' => 1]);
            $this->assertCount(1, $memories);
        }

        //check infocom still exists
        foreach ($data_to_check as $data_value) {
            $info_coms = $info_com->find(['id' => $data_value['id'], "buy_date" => $infocom_buy_date, "value" => $infocom_value]);
            $this->assertCount(1, $info_coms);
        }
    }

    public function testInventoryUpdateDrive()
    {
        global $DB;
        $item_drv = new \Item_DeviceHardDrive();
        $info_com = new \Infocom();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <STORAGES>
      <DESCRIPTION>PCI</DESCRIPTION>
      <DISKSIZE>256060</DISKSIZE>
      <FIRMWARE>BXV77D0Q</FIRMWARE>
      <INTERFACE>IDE</INTERFACE>
      <MANUFACTURER>Samsung</MANUFACTURER>
      <MODEL>PM951 NVMe SAMSUNG 256GB</MODEL>
      <NAME>nvme0n1</NAME>
      <SERIALNUMBER>S29NNXAH146764</SERIALNUMBER>
      <TYPE>disk</TYPE>
    </STORAGES>
    <STORAGES>
      <DESCRIPTION>Lecteur de disque</DESCRIPTION>
      <DISKSIZE>320072</DISKSIZE>
      <FIRMWARE>GHBOA900</FIRMWARE>
      <INTERFACE>IDE</INTERFACE>
      <MANUFACTURER>Samsung</MANUFACTURER>
      <MODEL>HGST HTS725032A7E630</MODEL>
      <NAME>\\.\PHYSICALDRIVE0</NAME>
      <SCSI_COID>0</SCSI_COID>
      <SCSI_LUN>0</SCSI_LUN>
      <SCSI_UNID>0</SCSI_UNID>
      <SERIAL>131005TF0401Y11K4NNN</SERIAL>
      <SERIALNUMBER>131005TF0401Y11K4NNN</SERIALNUMBER>
      <TYPE>disk</TYPE>
    </STORAGES>
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

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();
        $computers_id = $agent['items_id'];
        $this->assertGreaterThan(0, $computers_id);
        $computer = new \Computer();
        $computer->getFromDB($computers_id);

        //drives present from the inventory source are dynamic
        $drives = $item_drv->find(['itemtype' => \Computer::class, 'items_id' => $computers_id, 'is_dynamic' => 1]);

        $this->assertCount(2, $drives);

        $data_to_check = [];

        $infocom_buy_date = '2020-10-21';
        $infocom_value = '300';
        foreach ($drives as $result) {
            $item_device_drive_id = $result['id'];
            $input = [
                "items_id" => $item_device_drive_id,
                "itemtype" => \Item_DeviceHardDrive::class,
                "entities_id" => $computer->fields['entities_id'],
                "buy_date" => $infocom_buy_date,
                "value" => $infocom_value
            ];
            //create infocom
            $id = $info_com->add($input);
            $this->assertGreaterThan(0, $id);
            $input['id'] = $id;
            $data_to_check[] = $input;
        }

        //redo an inventory
        $this->doInventory($xml_source, true);

        //drives present in the inventory source are still dynamic
        $drives = $item_drv->find(['itemtype' =>  \Computer::class, 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(2, $drives);

        //check item_device_memory is the same from first step
        foreach ($data_to_check as $data_value) {
            $drives = $item_drv->find(['id' => $data_value['items_id'], "itemtype" => \Computer::class, 'is_dynamic' => 1]);
            $this->assertCount(1, $drives);
        }

        //check infocom still exists
        foreach ($data_to_check as $data_value) {
            $info_coms = $info_com->find(['id' => $data_value['id'], "buy_date" => $infocom_buy_date, "value" => $infocom_value]);
            $this->assertCount(1, $info_coms);
        }
    }

    public function testInventoryLogOnUpdateMemory()
    {
        global $DB;
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


        $this->doInventory($xml_source, true);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();
        $computers_id = $agent['items_id'];
        $this->assertGreaterThan(0, $computers_id);
        $computer = new \Computer();
        $computer->getFromDB($computers_id);

        //memories present from the inventory source are dynamic
        $memories = $item_mem->find(['itemtype' => \Computer::class, 'items_id' => $computers_id, 'is_dynamic' => 1]);

        $this->assertCount(2, $memories);

        //check for expected logs
        $nblogsnow = countElementsInTable(\Log::getTable());
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'WHERE' => [
                'itemtype' => \Item_DeviceMemory::class
            ]
        ]);
        $this->assertCount(0, $logs);

        //redo inventory and update memory capacity 8192 => 4096
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <MEMORIES>
      <CAPACITY>4096</CAPACITY>
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
      <CAPACITY>4096</CAPACITY>
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

        $this->doInventory($xml_source, true);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();
        $computers_id = $agent['items_id'];
        $this->assertGreaterThan(0, $computers_id);
        $computer = new \Computer();
        $computer->getFromDB($computers_id);

        //memories present from the inventory source are dynamic
        $memories = $item_mem->find(['itemtype' => \Computer::class, 'items_id' => $computers_id, 'is_dynamic' => 1]);

        $this->assertCount(2, $memories);

        //check for expected logs
        $nblogsnow = countElementsInTable(\Log::getTable());
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'WHERE' => [
                'itemtype' => \Item_DeviceMemory::class,
            ]
        ]);
        $this->assertCount(2, $logs); //for each memory module

        foreach ($logs as $key => $value) {
            $this->assertSame('8192', $value['old_value']);
            $this->assertSame('4096', $value['new_value']);
            $this->assertSame(20, $value['id_search_option']); //capacity SO
            $this->assertArrayHasKey($value['items_id'], $memories); //concerned item_devicememories
        }
    }
}
