<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace tests\units\Glpi\Inventory\Asset;

include_once __DIR__ . '/../../../../abstracts/AbstractInventoryAsset.php';

/* Test for inc/inventory/asset/remotemanagement.class.php */

class RemoteManagement extends AbstractInventoryAsset {

   protected function assetProvider() :array {
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
            'expected'  => '{"remoteid": "123456789", "type": "teamviewer"}'
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
            'expected'  => '{"remoteid": "abcdyz", "type": "anydesk"}'
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
            'expected'  => '{"remoteid": "myspecialid", "type": "litemanager"}'
         ]
      ];
   }

   /**
    * @dataProvider assetProvider
    */
   public function testPrepare($xml, $expected) {
      $converter = new \Glpi\Inventory\Converter;
      $data = $converter->convert($xml);
      $json = json_decode($data);

      $computer = getItemByTypeName('Computer', '_test_pc01');
      $asset = new \Glpi\Inventory\Asset\RemoteManagement($computer, $json->content->remote_mgmt);
      $asset->setExtraData((array)$json->content);
      $result = $asset->prepare();
      $this->object($result[0])->isEqualTo(json_decode($expected));
   }

   public function testWrongMainItem() {
      $mainasset = getItemByTypeName('Printer', '_test_printer_all');
      $asset = new \Glpi\Inventory\Asset\RemoteManagement($mainasset);
      $this->exception(
         function () use ($asset) {
            $asset->prepare();
         }
      )->message->contains('Remote Management are handled for following types only:');
   }

   public function testHandle() {
       global $DB;
      $computer = getItemByTypeName('Computer', '_test_pc01');

      //first, check there are no remote management linked to this computer
      $mgmt = new \Item_RemoteManagement;
      $this->boolean(
         $mgmt->getFromDbByCrit([
            'itemtype' => $computer->getType(),
            'items_id' => $computer->fields['id']
         ])
      )->isFalse('An remote management is already linked to computer!');

      //convert data
      $expected = $this->assetProvider()[0];

      $converter = new \Glpi\Inventory\Converter;
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

   public function testUpdate() {
       $this->testHandle();

      $computer = getItemByTypeName('Computer', '_test_pc01');

      //first, check there are no AV linked to this computer
      //first, check there are no remote management linked to this computer
      $mgmt = new \Item_RemoteManagement;
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

      $converter = new \Glpi\Inventory\Converter;
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
}
