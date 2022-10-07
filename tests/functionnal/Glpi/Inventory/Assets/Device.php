<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

class Device extends AbstractInventoryAsset
{
    protected function assetProvider(): array
    {
        return [];
    }


    public function testInventoryUpdate()
    {
        global $DB;
        $device_mem = new \DeviceMemory();
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
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $computers_id = $agent['items_id'];
        $this->integer($computers_id)->isGreaterThan(0);
        $computer = new \Computer();
        $computer->getFromDB($computers_id);

        //memories present from the inventory source are dynamic
        $memories = $item_mem->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);

        $this->integer(count($memories))->isIdenticalTo(2);

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
            $this->integer($id)->isGreaterThan(0);
            $input['id'] = $id;
            $data_to_check[] = $input;
        }

        //redo an inventory
        $this->doInventory($xml_source, true);

        //memory present in the inventory source is still dynamic
        $memories = $item_mem->find(['itemtype' =>  \Computer::class, 'items_id' => $computers_id, 'is_dynamic' => 1]);
        var_export($memories);
        $this->integer(count($memories))->isIdenticalTo(2);

        //check item_device_memory is the same from first step
        foreach ($data_to_check as $data_value) {
            $memories = $item_mem->find(['id' => $data_value['items_id'], "itemtype" => \Computer::class, 'is_dynamic' => 1]);
            $this->integer(count($memories))->isIdenticalTo(1);
        }

        //check infocom still exists
        foreach ($data_to_check as $data_value) {
            $info_coms = $info_com->find(['id' => $data_value['id'], "buy_date" => $infocom_buy_date, "value" => $infocom_value]);
            $this->integer(count($info_coms))->isIdenticalTo(1);
        }
    }
}
