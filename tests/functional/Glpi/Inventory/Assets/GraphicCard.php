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

/* Test for inc/inventory/asset/graphiccard.class.php */

class GraphicCard extends AbstractInventoryAsset
{
    protected function assetProvider(): array
    {
        return [
            [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <VIDEOS>
      <CHIPSET>ATY,RadeonX1600</CHIPSET>
      <MEMORY>128</MEMORY>
      <NAME>ATI Radeon X1600</NAME>
      <RESOLUTION>1680x1050</RESOLUTION>
    </VIDEOS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"chipset": "ATY,RadeonX1600", "memory": 128, "name": "ATI Radeon X1600", "resolution": "1680x1050", "designation": "ATI Radeon X1600", "is_dynamic": 1}'
            ], [ //with unit on memory
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <VIDEOS>
      <CHIPSET>Intel(R) HD Graphics Family</CHIPSET>
      <NAME>Intel(R) HD Graphics 530</NAME>
      <RESOLUTION>1920x1080</RESOLUTION>
    </VIDEOS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"chipset": "Intel(R) HD Graphics Family", "name": "Intel(R) HD Graphics 530", "resolution": "1920x1080", "designation": "Intel(R) HD Graphics 530", "is_dynamic": 1}'
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
        $asset = new \Glpi\Inventory\Asset\GraphicCard($computer, $json->content->videos);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->object($result[0])->isEqualTo(json_decode($expected));
    }

    public function testHandle()
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');

       //first, check there are no controller linked to this computer
        $idg = new \Item_DeviceGraphicCard();
        $this->boolean($idg->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isFalse('A graphic cardis already linked to computer!');

       //convert data
        $expected = $this->assetProvider()[0];

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\GraphicCard($computer, $json->content->videos);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->object($result[0])->isEqualTo(json_decode($expected['expected']));

       //handle
        $asset->handleLinks();
        $asset->handle();
        $this->boolean($idg->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isTrue('Graphic card has not been linked to computer :(');
    }

    public function testInventoryUpdate()
    {
        $computer = new \Computer();
        $device_gc = new \DeviceGraphicCard();
        $item_gc = new \Item_DeviceGraphicCard();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <VIDEOS>
      <CHIPSET>ATY,RadeonX1600</CHIPSET>
      <MEMORY>128</MEMORY>
      <NAME>ATI Radeon X1600</NAME>
      <RESOLUTION>1680x1050</RESOLUTION>
    </VIDEOS>
    <VIDEOS>
      <CHIPSET>Intel(R) HD Graphics Family</CHIPSET>
      <NAME>Intel(R) HD Graphics 530</NAME>
      <RESOLUTION>1920x1080</RESOLUTION>
    </VIDEOS>
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

       //create manually a computer, with 3 graphic cards
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0
        ]);
        $this->integer($computers_id)->isGreaterThan(0);

        $gc_1_id = $device_gc->add([
            'designation' => 'ATI Radeon X1600',
            'chipset' => 'ATY,RadeonX1600',
            'entities_id'  => 0
        ]);
        $this->integer($gc_1_id)->isGreaterThan(0);

        $item_gc_1_id = $item_gc->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicegraphiccards_id' => $gc_1_id
        ]);
        $this->integer($item_gc_1_id)->isGreaterThan(0);

        $gc_2_id = $device_gc->add([
            'designation' => 'Intel(R) HD Graphics 530',
            'chipset' => 'Intel(R) HD Graphics Family',
            'entities_id'  => 0
        ]);
        $this->integer($gc_2_id)->isGreaterThan(0);

        $item_gc_2_id = $item_gc->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicegraphiccards_id' => $gc_2_id
        ]);
        $this->integer($item_gc_2_id)->isGreaterThan(0);

        $gc_3_id = $device_gc->add([
            'designation' => 'My Graphic Card',
            'chipset' => 'My chipset',
            'entities_id'  => 0
        ]);
        $this->integer($gc_3_id)->isGreaterThan(0);

        $item_gc_3_id = $item_gc->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicegraphiccards_id' => $gc_3_id
        ]);
        $this->integer($item_gc_3_id)->isGreaterThan(0);

        $gcs = $item_gc->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($gcs))->isIdenticalTo(3);
        foreach ($gcs as $gc) {
            $this->variable($gc['is_dynamic'])->isEqualTo(0);
        }

       //computer inventory knows only "ATI" and "Intel" graphic cards
        $this->doInventory($xml_source, true);

       //we still have 3 graphic cards
        $gcs = $device_gc->find();
        $this->integer(count($gcs))->isIdenticalTo(3);

       //we still have 3 graphic cards items linked to the computer
        $gcs = $item_gc->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($gcs))->isIdenticalTo(3);

       //graphic cards present in the inventory source are now dynamic
        $gcs = $item_gc->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($gcs))->isIdenticalTo(2);

       //graphic card not present in the inventory is still not dynamic
        $gcs = $item_gc->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->integer(count($gcs))->isIdenticalTo(1);

       //Redo inventory, but with removed "Intel" graphic card
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <VIDEOS>
      <CHIPSET>ATY,RadeonX1600</CHIPSET>
      <MEMORY>128</MEMORY>
      <NAME>ATI Radeon X1600</NAME>
      <RESOLUTION>1680x1050</RESOLUTION>
    </VIDEOS>
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

       //we still have 3 graphic cards
        $gcs = $device_gc->find();
        $this->integer(count($gcs))->isIdenticalTo(3);

       //we now have 2 graphic cards linked to computer only
        $gcs = $item_gc->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($gcs))->isIdenticalTo(2);

       //graphic card present in the inventory source is still dynamic
        $gcs = $item_gc->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($gcs))->isIdenticalTo(1);

       //graphic card not present in the inventory is still not dynamic
        $gcs = $item_gc->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->integer(count($gcs))->isIdenticalTo(1);
    }
}
