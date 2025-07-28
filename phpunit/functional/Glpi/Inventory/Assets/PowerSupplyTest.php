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

use Glpi\Inventory\Asset\PowerSupply;
use Glpi\Inventory\Converter;
use PHPUnit\Framework\Attributes\DataProvider;

include_once __DIR__ . '/../../../../abstracts/AbstractInventoryAsset.php';

/* Test for inc/inventory/asset/powersupply.class.php */

class PowerSupplyTest extends AbstractInventoryAsset
{
    public static function assetProvider(): array
    {
        return [
            [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <POWERSUPPLIES>
      <HOTREPLACEABLE>No</HOTREPLACEABLE>
      <PARTNUM>High Efficiency</PARTNUM>
      <PLUGGED>Yes</PLUGGED>
      <STATUS>Present, Unknown</STATUS>
    </POWERSUPPLIES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"hotreplaceable": 1, "partnum": "High Efficiency", "plugged": 1, "status": "Present, Unknown", "designation": "High Efficiency", "is_dynamic": 1}',
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
        $asset = new PowerSupply($computer, $json->content->powersupplies);
        $asset->setExtraData((array) $json->content);
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected), $result[0]);
    }

    public function testHandle()
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');

        //first, check there are no power supply linked to this computer
        $idp = new \Item_DevicePowerSupply();
        $this->assertFalse(
            $idp->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']),
            'A power supply is already linked to computer!'
        );

        //convert data
        $expected = $this->assetProvider()[0];

        $converter = new Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new PowerSupply($computer, $json->content->powersupplies);
        $asset->setExtraData((array) $json->content);
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected['expected']), $result[0]);

        //handle
        $asset->handleLinks();
        $asset->handle();
        $this->assertTrue(
            $idp->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']),
            'Power supply has not been linked to computer :('
        );
    }

    public function testInventoryUpdate()
    {
        $computer = new \Computer();
        $device_ps = new \DevicePowerSupply();
        $item_ps = new \Item_DevicePowerSupply();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <POWERSUPPLIES>
      <HOTREPLACEABLE>No</HOTREPLACEABLE>
      <PARTNUM>High Efficiency</PARTNUM>
      <PLUGGED>Yes</PLUGGED>
      <STATUS>Present, Unknown</STATUS>
    </POWERSUPPLIES>
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

        //computer inventory knows only 1 power supply
        $inventory = $this->doInventory($xml_source, true);

        $computer = $inventory->getItem();
        $computers_id = $computer->fields['id'];

        //we have 1 power supply
        $pws = $device_ps->find();
        $this->assertCount(1, $pws);

        //we have 1 power supply items linked to the computer
        $pws = $item_ps->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(1, $pws);

        //power supply present in the inventory source is dynamic
        $pws = $item_ps->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $pws);
    }
}
