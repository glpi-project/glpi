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

/* Test for inc/inventory/asset/battery.class.php */

class Battery extends AbstractInventoryAsset {

   protected function assetProvider() :array {
      return [
         [
            'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <BATTERIES>
      <CAPACITY>43.7456 Wh</CAPACITY>
      <CHEMISTRY>lithium-polymer</CHEMISTRY>
      <DATE>10/11/2015</DATE>
      <MANUFACTURER>SMP</MANUFACTURER>
      <NAME>DELL JHXPY53</NAME>
      <SERIAL>3701</SERIAL>
      <VOLTAGE>8.614 V</VOLTAGE>
    </BATTERIES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
            'expected'  => '{"capacity": "43746", "chemistry": "lithium-polymer", "date": "2015-11-10", "manufacturer": "SMP", "name": "DELL JHXPY53", "serial": "3701", "voltage": "8614", "designation": "DELL JHXPY53", "manufacturers_id": "SMP", "manufacturing_date": "2015-11-10", "devicebatterytypes_id": "lithium-polymer"}'
         ], [ //no voltage
            'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <BATTERIES>
      <CAPACITY>43.7456 Wh</CAPACITY>
      <CHEMISTRY>lithium-polymer</CHEMISTRY>
      <DATE>10/11/2015</DATE>
      <MANUFACTURER>SMP</MANUFACTURER>
      <NAME>DELL JHXPY53</NAME>
      <SERIAL>3701</SERIAL>
    </BATTERIES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
            'expected'  => '{"capacity": "43746", "chemistry": "lithium-polymer", "date": "2015-11-10", "manufacturer": "SMP", "name": "DELL JHXPY53", "serial": "3701", "voltage": "0", "designation": "DELL JHXPY53", "manufacturers_id": "SMP", "manufacturing_date": "2015-11-10", "devicebatterytypes_id": "lithium-polymer"}'
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
      $asset = new \Glpi\Inventory\Asset\Battery($computer, $json->content->batteries);
      $asset->setExtraData((array)$json->content);
      $result = $asset->prepare();
      $this->object($result[0])->isEqualTo(json_decode($expected));
   }

   public function testHandle() {
      $computer = getItemByTypeName('Computer', '_test_pc01');

      //first, check there are no battery linked to this computer
      $idb = new \Item_DeviceBattery();;
      $this->boolean($idb->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isFalse('A battery is already linked to computer!');

      //convert data
      $expected = $this->assetProvider()[0];

      $converter = new \Glpi\Inventory\Converter;
      $data = $converter->convert($expected['xml']);
      $json = json_decode($data);

      $computer = getItemByTypeName('Computer', '_test_pc01');
      $asset = new \Glpi\Inventory\Asset\Battery($computer, $json->content->batteries);
      $asset->setExtraData((array)$json->content);
      $result = $asset->prepare();
      $this->object($result[0])->isEqualTo(json_decode($expected['expected']));

      //handle
      $asset->handleLinks();
      $asset->handle();
      $this->boolean($idb->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isTrue('Battery has not been linked to computer :(');
   }
}
