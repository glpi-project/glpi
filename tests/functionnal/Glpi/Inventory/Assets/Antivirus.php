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

/* Test for inc/inventory/asset/antivirus.class.php */

class Antivirus extends AbstractInventoryAsset {

   protected function assetProvider() :array {
      return [
         [
            'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <ANTIVIRUS>
      <COMPANY>Microsoft Corporation</COMPANY>
      <ENABLED>1</ENABLED>
      <GUID>{641105E6-77ED-3F35-A304-765193BCB75F}</GUID>
      <NAME>Microsoft Security Essentials</NAME>
      <UPTODATE>1</UPTODATE>
      <VERSION>4.3.216.0</VERSION>
    </ANTIVIRUS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
            'expected'  => '{"company": "Microsoft Corporation", "enabled": true, "guid": "{641105E6-77ED-3F35-A304-765193BCB75F}", "name": "Microsoft Security Essentials", "uptodate": true, "version": "4.3.216.0", "manufacturers_id": "Microsoft Corporation", "antivirus_version": "4.3.216.0", "is_active": true, "is_uptodate": true}'
         ], [ //no version
            'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <ANTIVIRUS>
      <COMPANY>Microsoft Corporation</COMPANY>
      <ENABLED>1</ENABLED>
      <GUID>{641105E6-77ED-3F35-A304-765193BCB75F}</GUID>
      <NAME>Microsoft Security Essentials</NAME>
      <UPTODATE>1</UPTODATE>
    </ANTIVIRUS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
            'expected'  => '{"company": "Microsoft Corporation", "enabled": true, "guid": "{641105E6-77ED-3F35-A304-765193BCB75F}", "name": "Microsoft Security Essentials", "uptodate": true, "manufacturers_id": "Microsoft Corporation", "antivirus_version": "", "is_active": true, "is_uptodate": true}'
         ], [ //w expiration date
            'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <ANTIVIRUS>
      <COMPANY>Microsoft Corporation</COMPANY>
      <ENABLED>1</ENABLED>
      <GUID>{641105E6-77ED-3F35-A304-765193BCB75F}</GUID>
      <NAME>Microsoft Security Essentials</NAME>
      <UPTODATE>1</UPTODATE>
      <VERSION>4.3.216.0</VERSION>
      <EXPIRATION>01/04/2019</EXPIRATION>
    </ANTIVIRUS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
            'expected'  => '{"company": "Microsoft Corporation", "enabled": true, "guid": "{641105E6-77ED-3F35-A304-765193BCB75F}", "name": "Microsoft Security Essentials", "uptodate": true, "version": "4.3.216.0", "manufacturers_id": "Microsoft Corporation", "antivirus_version": "4.3.216.0", "is_active": true, "is_uptodate": true, "expiration": "2019-04-01", "date_expiration": "2019-04-01"}'
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
      $asset = new \Glpi\Inventory\Asset\Antivirus($computer, $json->content->antivirus);
      $asset->setExtraData((array)$json->content);
      $result = $asset->prepare();
      $this->object($result[0])->isEqualTo(json_decode($expected));
   }

   public function testWrongMainItem() {
      $mainasset = getItemByTypeName('Printer', '_test_printer_all');
      $asset = new \Glpi\Inventory\Asset\Antivirus($mainasset);
      $this->exception(
         function () use ($asset) {
            $asset->prepare();
         }
      )->message->contains('Antivirus are handled for computers only.');
   }

   public function testHandle() {
       global $DB;
      $computer = getItemByTypeName('Computer', '_test_pc01');

      //first, check there are no AV linked to this computer
      $avc = new \ComputerAntivirus;
      $this->boolean($avc->getFromDbByCrit(['computers_id' => $computer->fields['id']]))
           ->isFalse('An antivirus is already linked to computer!');

      //convert data
      $expected = $this->assetProvider()[0];

      $converter = new \Glpi\Inventory\Converter;
      $data = $converter->convert($expected['xml']);
      $json = json_decode($data);

      $computer = getItemByTypeName('Computer', '_test_pc01');
      $asset = new \Glpi\Inventory\Asset\Antivirus($computer, $json->content->antivirus);
      $asset->setExtraData((array)$json->content);
      $result = $asset->prepare();
      $this->object($result[0])->isEqualTo(json_decode($expected['expected']));

      //handle
      $asset->handleLinks();

      $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Microsoft Corporation']])->next();
      $this->array($cmanuf);
      $manufacturers_id = $cmanuf['id'];

      $asset->handle();
      $this->boolean($avc->getFromDbByCrit(['computers_id' => $computer->fields['id']]))
           ->isTrue('Antivirus has not been linked to computer :(');

      $this->integer($avc->fields['manufacturers_id'])->isIdenticalTo($manufacturers_id);
   }

   public function testUpdate() {
       $this->testHandle();

      $computer = getItemByTypeName('Computer', '_test_pc01');

      //first, check there are no AV linked to this computer
      $avc = new \ComputerAntivirus;
      $this->boolean($avc->getFromDbByCrit(['computers_id' => $computer->fields['id']]))
           ->isTrue('No antivirus linked to computer!');

      $expected = $this->assetProvider()[0];
      $json_expected = json_decode($expected['expected']);
      $xml = $expected['xml'];
      //change version
      $xml = str_replace('<VERSION>4.3.216.0</VERSION>', '<VERSION>4.5.12.0</VERSION>', $xml);
      $json_expected->version = '4.5.12.0';
      $json_expected->antivirus_version = '4.5.12.0';

      $converter = new \Glpi\Inventory\Converter;
      $data = $converter->convert($xml);
      $json = json_decode($data);

      $asset = new \Glpi\Inventory\Asset\Antivirus($computer, $json->content->antivirus);
      $asset->setExtraData((array)$json->content);
      $result = $asset->prepare();
      $this->object($result[0])->isEqualTo($json_expected);

      $asset->handleLinks();
      $asset->handle();
      $this->boolean($avc->getFromDbByCrit(['computers_id' => $computer->fields['id']]))->isTrue();

      $this->string($avc->fields['antivirus_version'])->isIdenticalTo('4.5.12.0');
   }
}
