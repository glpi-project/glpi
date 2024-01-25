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

class RemoteManagement extends AbstractInventoryAsset
{
    protected function assetProvider(): array
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
        $this->object($result[0])->isEqualTo(json_decode($expected));
    }

    public function testWrongMainItem()
    {
        $mainasset = getItemByTypeName('Printer', '_test_printer_all');
        $asset = new \Glpi\Inventory\Asset\RemoteManagement($mainasset);
        $this->exception(
            function () use ($asset) {
                $asset->prepare();
            }
        )->message->contains('Remote Management are handled for following types only:');
    }

    public function testHandle()
    {
        global $DB;
        $computer = getItemByTypeName('Computer', '_test_pc01');

       //first, check there are no remote management linked to this computer
        $mgmt = new \Item_RemoteManagement();
        $this->boolean(
            $mgmt->getFromDbByCrit([
                'itemtype' => $computer->getType(),
                'items_id' => $computer->fields['id']
            ])
        )->isFalse('An remote management is already linked to computer!');

       //convert data
        $expected = $this->assetProvider()[0];

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $asset = new \Glpi\Inventory\Asset\RemoteManagement($computer, $json->content->remote_mgmt);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->object($result[0])->isEqualTo(json_decode($expected['expected']));

       //handle
        $asset->handleLinks();

        $asset->handle();
        $this->boolean(
            $mgmt->getFromDbByCrit([
                'itemtype' => $computer->getType(),
                'items_id' => $computer->fields['id']
            ])
        )->isTrue('Remote Management has not been linked to computer :(');
    }

    public function testUpdate()
    {
        $this->testHandle();

        $computer = getItemByTypeName('Computer', '_test_pc01');

       //first, check there are no remote management linked to this computer
        $mgmt = new \Item_RemoteManagement();
        $this->boolean(
            $mgmt->getFromDbByCrit([
                'itemtype' => $computer->getType(),
                'items_id' => $computer->fields['id']
            ])
        )->isTrue('No remote management linked to computer!');

        $expected = $this->assetProvider()[0];
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
        $this->object($result[0])->isEqualTo($json_expected);

        $asset->handleLinks();
        $asset->handle();
        $this->boolean(
            $mgmt->getFromDbByCrit([
                'itemtype' => $computer->getType(),
                'items_id' => $computer->fields['id']
            ])
        )->isTrue();

        $this->string($mgmt->fields['remoteid'])->isIdenticalTo('987654321');
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
        $this->integer($computers_id)->isGreaterThan(0);

        $this->integer(
            $mgmt->add([
                'itemtype' => 'Computer',
                'items_id' => $computers_id,
                'type' => 'teamviewer',
                'remoteid' => '123456789'
            ])
        )->isGreaterThan(0);

        $this->integer(
            $mgmt->add([
                'itemtype' => 'Computer',
                'items_id' => $computers_id,
                'type' => 'anydesk',
                'remoteid' => 'abcdyz'
            ])
        )->isGreaterThan(0);

        $this->integer(
            $mgmt->add([
                'itemtype' => 'Computer',
                'items_id' => $computers_id,
                'type' => 'mymgmt',
                'remoteid' => 'qwertyuiop'
            ])
        )->isGreaterThan(0);

        $mgmts = $mgmt->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($mgmts))->isIdenticalTo(3);
        foreach ($mgmts as $m) {
            $this->variable($m['is_dynamic'])->isEqualTo(0);
        }

       //computer inventory knows only "teamviewer" and "anydesk" remote managements
        $this->doInventory($xml_source, true);

        //we still have 3 remote managements
        $mgmts = $mgmt->find();
        $this->integer(count($mgmts))->isIdenticalTo(3);

        //we still have 3 remote managements items linked to the computer
        $mgmts = $mgmt->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($mgmts))->isIdenticalTo(3);

       //remote managements present in the inventory source are now dynamic
        $mgmts = $mgmt->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($mgmts))->isIdenticalTo(2);

       //remote management not present in the inventory is still not dynamic
        $mgmts = $mgmt->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->integer(count($mgmts))->isIdenticalTo(1);

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
        $this->integer(count($mgmts))->isIdenticalTo(2);

       //we now have 2 remote managements linked to computer only
        $mgmts = $mgmt->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($mgmts))->isIdenticalTo(2);

       //remote management present in the inventory source is still dynamic
        $mgmts = $mgmt->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($mgmts))->isIdenticalTo(1);

       //remote management not present in the inventory is still not dynamic
        $mgmts = $mgmt->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->integer(count($mgmts))->isIdenticalTo(1);
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
        $this->integer(count($mgmts))->isIdenticalTo(1);

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
        $this->integer(count($mgmts))->isIdenticalTo(0);
    }
}
