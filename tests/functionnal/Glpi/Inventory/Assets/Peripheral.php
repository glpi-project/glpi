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

/* Test for inc/inventory/asset/controller.class.php */

class Peripheral extends AbstractInventoryAsset {

   protected function assetProvider() :array {
      return [
         [
            'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
         <REQUEST>
         <CONTENT>
            <USBDEVICES>
               <CAPTION>VFS451 Fingerprint Reader</CAPTION>
               <MANUFACTURER>Validity Sensors, Inc.</MANUFACTURER>
               <NAME>VFS451 Fingerprint Reader</NAME>
               <PRODUCTID>0007</PRODUCTID>
               <SERIAL>00B0FE47AC85</SERIAL>
               <VENDORID>138A</VENDORID>
            </USBDEVICES>
            <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
         </CONTENT>
         <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
         <QUERY>INVENTORY</QUERY>
         </REQUEST>",
            'expected'  => '{"caption": "VFS451 Fingerprint Reader", "manufacturer": "Validity Sensors, Inc.", "name": "VFS451 Fingerprint Reader", "productid": "0007", "serial": "00B0FE47AC85", "vendorid": "138A", "manufacturers_id": "Validity Sensors, Inc."}'
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
      $asset = new \Glpi\Inventory\Asset\Peripheral($computer, $json->content->usbdevices);
      $asset->setExtraData((array)$json->content);
      $result = $asset->prepare();
      $this->object($result[0])->isEqualTo(json_decode($expected));
   }


   public function testHandle() {
      $computer = getItemByTypeName('Computer', '_test_pc01');

      //first, check there are no controller linked to this computer
      $idp = new \Computer_Item();
      $this->boolean($idp->getFromDbByCrit(['computers_id' => $computer->fields['id'], 'itemtype' => 'Peripheral']))
           ->isFalse('A peripheral is already linked to computer!');

      //convert data
      $expected = $this->assetProvider()[0];

      $converter = new \Glpi\Inventory\Converter;
      $data = $converter->convert($expected['xml']);
      $json = json_decode($data);

      $computer = getItemByTypeName('Computer', '_test_pc01');
      $asset = new \Glpi\Inventory\Asset\Peripheral($computer, $json->content->usbdevices);
      $asset->setExtraData((array)$json->content);
      $result = $asset->prepare();
      $this->object($result[0])->isEqualTo(json_decode($expected['expected']));

      //handle
      $asset->handleLinks();

      $agent = new \Agent();
      $agent->getEmpty();
      $asset->setAgent($agent);

      $asset->handle();
      $this->boolean($idp->getFromDbByCrit(['computers_id' => $computer->fields['id'], 'itemtype' => 'Peripheral']))
           ->isTrue('Peripheral has not been linked to computer :(');
   }
}
