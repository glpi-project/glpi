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

use Glpi\Inventory\Asset\SoundCard;
use Glpi\Inventory\Converter;
use PHPUnit\Framework\Attributes\DataProvider;

include_once __DIR__ . '/../../../../abstracts/AbstractInventoryAsset.php';

/* Test for inc/inventory/asset/controller.class.php */

class SoundCardTest extends AbstractInventoryAsset
{
    public static function assetProvider(): array
    {
        return [
            [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SOUNDS>
      <DESCRIPTION>rev 21</DESCRIPTION>
      <MANUFACTURER>Intel Corporation Sunrise Point-LP HD Audio</MANUFACTURER>
      <NAME>Audio device</NAME>
    </SOUNDS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"description": "rev 21", "manufacturer": "Intel Corporation Sunrise Point-LP HD Audio", "name": "Audio device", "designation": "Audio device", "manufacturers_id": "Intel Corporation Sunrise Point-LP HD Audio", "comment": "rev 21", "is_dynamic": 1}',
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
        $asset = new SoundCard($computer, $json->content->sounds);
        $asset->setExtraData((array) $json->content);
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected), $result[0]);
    }

    public function testHandle()
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');

        //first, check there are no soundcard linked to this computer
        $ids = new \Item_DeviceSoundCard();
        $this->assertFalse(
            $ids->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']),
            'A soundcard is already linked to computer!'
        );

        //convert data
        $expected = $this->assetProvider()[0];

        $converter = new Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new SoundCard($computer, $json->content->sounds);
        $asset->setExtraData((array) $json->content);
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected['expected']), $result[0]);

        //handle
        $asset->handleLinks();
        $asset->handle();
        $this->assertTrue(
            $ids->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']),
            'Soundcard has not been linked to computer :('
        );
    }

    public function testInventoryUpdate()
    {
        $computer = new \Computer();
        $device_sound = new \DeviceSoundCard();
        $item_sound = new \Item_DeviceSoundCard();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SOUNDS>
      <DESCRIPTION>Intel Corporation Sunrise Point-LP HD Audio</DESCRIPTION>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>Audio device</NAME>
    </SOUNDS>
    <SOUNDS>
      <CAPTION>Realtek High Definition Audio</CAPTION>
      <DESCRIPTION>Realtek High Definition Audio</DESCRIPTION>
      <MANUFACTURER>Realtek</MANUFACTURER>
      <NAME>Realtek High Definition Audio</NAME>
    </SOUNDS>
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

        //create manually a computer, with 3 sound cards
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

        $sound_1_id = $device_sound->add([
            'designation' => 'Audio device',
            'manufacturers_id' => $manufacturers_id,
            'entities_id'  => 0,
        ]);
        $this->assertGreaterThan(0, $sound_1_id);

        $item_sound_1_id = $item_sound->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicesoundcards_id' => $sound_1_id,
        ]);
        $this->assertGreaterThan(0, $item_sound_1_id);

        $manufacturers_id = $manufacturer->add([
            'name' => 'Realtek',
        ]);
        $this->assertGreaterThan(0, $manufacturers_id);

        $sound_2_id = $device_sound->add([
            'designation' => 'Realtek High Definition Audio',
            'manufacturers_id' => $manufacturers_id,
            'entities_id'  => 0,
        ]);
        $this->assertGreaterThan(0, $sound_2_id);

        $item_sound_2_id = $item_sound->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicesoundcards_id' => $sound_2_id,
        ]);
        $this->assertGreaterThan(0, $item_sound_2_id);

        $sound_3_id = $device_sound->add([
            'designation' => 'My Sound Card',
            'manufacturers_id' => $manufacturers_id,
            'entities_id'  => 0,
        ]);
        $this->assertGreaterThan(0, $sound_3_id);

        $item_sound_3_id = $item_sound->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicesoundcards_id' => $sound_3_id,
        ]);
        $this->assertGreaterThan(0, $item_sound_3_id);

        $sounds = $item_sound->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(3, $sounds);
        foreach ($sounds as $sound) {
            $this->assertEquals(0, $sound['is_dynamic']);
        }

        //computer inventory knows only "Intel" and "Realtek" sound cards
        $this->doInventory($xml_source, true);

        //we still have 3 sound cards
        $sounds = $device_sound->find();
        $this->assertCount(3, $sounds);

        //we still have 3 sound cards items linked to the computer
        $sounds = $item_sound->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(3, $sounds);

        //sound cards present in the inventory source are now dynamic
        $sounds = $item_sound->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(2, $sounds);

        //sound card not present in the inventory is still not dynamic
        $sounds = $item_sound->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->assertCount(1, $sounds);

        //Redo inventory, but with removed "Realtek" sound card
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SOUNDS>
      <DESCRIPTION>Intel Corporation Sunrise Point-LP HD Audio</DESCRIPTION>
      <MANUFACTURER>Intel Corporation</MANUFACTURER>
      <NAME>Audio device</NAME>
    </SOUNDS>
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

        //we still have 3 sound cards
        $sounds = $device_sound->find();
        $this->assertCount(3, $sounds);

        //we now have 2 sound cards linked to computer only
        $sounds = $item_sound->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(2, $sounds);

        //sound card present in the inventory source is still dynamic
        $sounds = $item_sound->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $sounds);

        //sound card not present in the inventory is still not dynamic
        $sounds = $item_sound->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->assertCount(1, $sounds);
    }
}
