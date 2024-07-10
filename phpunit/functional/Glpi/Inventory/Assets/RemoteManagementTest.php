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

/* Test for inc/inventory/asset/remotemanagement.class.php */

class RemoteManagementTest extends AbstractInventoryAsset
{
    public static function assetProvider(): array
    {
        return [
            [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <REMOTE_MGMT>
      <ID>123456789</ID>
      <TYPE>teamviewer</TYPE>
    </REMOTE_MGMT>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"remoteid": "123456789", "type": "teamviewer", "is_dynamic": 1}'
            ], [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <REMOTE_MGMT>
      <ID>abcdyz</ID>
      <TYPE>anydesk</TYPE>
    </REMOTE_MGMT>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"remoteid": "abcdyz", "type": "anydesk", "is_dynamic": 1}'
            ], [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <REMOTE_MGMT>
      <ID>myspecialid</ID>
      <TYPE>litemanager</TYPE>
    </REMOTE_MGMT>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"remoteid": "myspecialid", "type": "litemanager", "is_dynamic": 1}'
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
        $asset = new \Glpi\Inventory\Asset\RemoteManagement($computer, $json->content->remote_mgmt);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected), $result[0]);
    }

    public function testWrongMainItem()
    {
        $mainasset = getItemByTypeName('Printer', '_test_printer_all');
        $asset = new \Glpi\Inventory\Asset\RemoteManagement($mainasset);

        $this->expectExceptionMessage('Remote Management are handled for following types only:');
        $asset->prepare();
    }

    public function testHandle()
    {
        global $DB;
        $computer = getItemByTypeName('Computer', '_test_pc01');

        //first, check there are no remote management linked to this computer
        $mgmt = new \Item_RemoteManagement();
        $this->assertFalse(
            $mgmt->getFromDbByCrit([
                'itemtype' => $computer->getType(),
                'items_id' => $computer->fields['id']
            ]),
            'A remote management is already linked to computer!'
        );

        //convert data
        $expected = $this::assetProvider()[0];

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $asset = new \Glpi\Inventory\Asset\RemoteManagement($computer, $json->content->remote_mgmt);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected['expected']), $result[0]);

       //handle
        $asset->handleLinks();

        $asset->handle();
        $this->assertTrue(
            $mgmt->getFromDbByCrit([
                'itemtype' => $computer->getType(),
                'items_id' => $computer->fields['id']
            ]),
            'Remote Management has not been linked to computer :('
        );
    }

    public function testUpdate()
    {
        $this->testHandle();

        $computer = getItemByTypeName('Computer', '_test_pc01');

       //first, check there are no remote management linked to this computer
        $mgmt = new \Item_RemoteManagement();
        $this->assertTrue(
            $mgmt->getFromDbByCrit([
                'itemtype' => $computer->getType(),
                'items_id' => $computer->fields['id']
            ]),
            'No remote management linked to computer!'
        );

        $expected = $this::assetProvider()[0];
        $json_expected = json_decode($expected['expected']);
        $xml = $expected['xml'];
       //change version
        $xml = str_replace('<ID>123456789</ID>', '<ID>987654321</ID>', $xml);
        $json_expected->remoteid = '987654321';

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $asset = new \Glpi\Inventory\Asset\RemoteManagement($computer, $json->content->remote_mgmt);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->assertEquals($json_expected, $result[0]);

        $asset->handleLinks();
        $asset->handle();
        $this->assertTrue(
            $mgmt->getFromDbByCrit([
                'itemtype' => $computer->getType(),
                'items_id' => $computer->fields['id']
            ])
        );

        $this->assertSame('987654321', $mgmt->fields['remoteid']);
    }

    public function testInventoryUpdate()
    {
        $computer = new \Computer();
        $mgmt = new \Item_RemoteManagement();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <REMOTE_MGMT>
      <ID>123456789</ID>
      <TYPE>teamviewer</TYPE>
    </REMOTE_MGMT>
    <REMOTE_MGMT>
      <ID>abcdyz</ID>
      <TYPE>anydesk</TYPE>
    </REMOTE_MGMT>
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

       //create manually a computer, with 3 remote managements
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $this->assertGreaterThan(
            0,
            $mgmt->add([
                'itemtype' => 'Computer',
                'items_id' => $computers_id,
                'type' => 'teamviewer',
                'remoteid' => '123456789'
            ])
        );

        $this->assertGreaterThan(
            0,
            $mgmt->add([
                'itemtype' => 'Computer',
                'items_id' => $computers_id,
                'type' => 'anydesk',
                'remoteid' => 'abcdyz'
            ])
        );

        $this->assertGreaterThan(
            0,
            $mgmt->add([
                'itemtype' => 'Computer',
                'items_id' => $computers_id,
                'type' => 'mymgmt',
                'remoteid' => 'qwertyuiop'
            ])
        );

        $mgmts = $mgmt->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(3, $mgmts);
        foreach ($mgmts as $m) {
            $this->assertEquals(0, $m['is_dynamic']);
        }

       //computer inventory knows only "teamviewer" and "anydesk" remote managements
        $this->doInventory($xml_source, true);

        //we still have 3 remote managements
        $mgmts = $mgmt->find();
        $this->assertCount(3, $mgmts);

        //we still have 3 remote managements items linked to the computer
        $mgmts = $mgmt->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(3, $mgmts);

       //remote managements present in the inventory source are now dynamic
        $mgmts = $mgmt->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(2, $mgmts);

       //remote management not present in the inventory is still not dynamic
        $mgmts = $mgmt->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->assertCount(1, $mgmts);

       //Redo inventory, but with removed "anydesk" remote managements
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <REMOTE_MGMT>
      <ID>123456789</ID>
      <TYPE>teamviewer</TYPE>
    </REMOTE_MGMT>
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

       //we now have only 2 remote managements
        $mgmts = $mgmt->find();
        $this->assertCount(2, $mgmts);

       //we now have 2 remote managements linked to computer only
        $mgmts = $mgmt->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(2, $mgmts);

       //remote management present in the inventory source is still dynamic
        $mgmts = $mgmt->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $mgmts);

       //remote management not present in the inventory is still not dynamic
        $mgmts = $mgmt->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->assertCount(1, $mgmts);
    }

    public function testNoMoreRemoteManagement()
    {
        $mgmt = new \Item_RemoteManagement();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <REMOTE_MGMT>
      <ID>abcdyz</ID>
      <TYPE>anydesk</TYPE>
    </REMOTE_MGMT>
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

        //we have 1 remote managements (anydesk / abcdyz) linked to computer
        $mgmts = $mgmt->find();
        $this->assertCount(1, $mgmts);

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
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

        //we have no remote managements linked to computer
        $mgmts = $mgmt->find();
        $this->assertCount(0, $mgmts);
    }
}
