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

/* Test for inc/inventory/asset/monitor.class.php */

class Monitor extends AbstractInventoryAsset {

   protected function assetProvider() :array {
      return [
         [
            'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <MONITORS>
      <BASE64>AP///////wBNEEkUAAAAACAZAQSlHRF4Dt5Qo1RMmSYPUFQAAAABAQEBAQEBAQEBAQEBAQEBGjaAoHA4H0AwIDUAJqUQAAAYAAAAEAAAAAAAAAAAAAAAAAAAAAAA/gBESkNQNoBMUTEzM00xAAAAAAACQQMoABIAAAsBCiAgAGY=</BASE64>
      <CAPTION>DJCP6</CAPTION>
      <DESCRIPTION>32/2015</DESCRIPTION>
      <MANUFACTURER>Sharp Corporation</MANUFACTURER>
      <SERIAL>AFGHHDR0</SERIAL>
    </MONITORS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
            'expected'  => '{"base64": "AP///////wBNEEkUAAAAACAZAQSlHRF4Dt5Qo1RMmSYPUFQAAAABAQEBAQEBAQEBAQEBAQEBGjaAoHA4H0AwIDUAJqUQAAAYAAAAEAAAAAAAAAAAAAAAAAAAAAAA/gBESkNQNoBMUTEzM00xAAAAAAACQQMoABIAAAsBCiAgAGY=", "caption": "DJCP6", "description": "32/2015", "manufacturer": "Sharp Corporation", "serial": "AFGHHDR0", "name": "DJCP6", "manufacturers_id": "Sharp Corporation"}'
         ], [ //no name but description
            'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <MONITORS>
      <BASE64>AP///////wBNEEkUAAAAACAZAQSlHRF4Dt5Qo1RMmSYPUFQAAAABAQEBAQEBAQEBAQEBAQEBGjaAoHA4H0AwIDUAJqUQAAAYAAAAEAAAAAAAAAAAAAAAAAAAAAAA/gBESkNQNoBMUTEzM00xAAAAAAACQQMoABIAAAsBCiAgAGY=</BASE64>
      <DESCRIPTION>32/2015</DESCRIPTION>
      <MANUFACTURER>Sharp Corporation</MANUFACTURER>
      <SERIAL>00000000</SERIAL>
    </MONITORS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
            'expected'  => '{"base64": "AP///////wBNEEkUAAAAACAZAQSlHRF4Dt5Qo1RMmSYPUFQAAAABAQEBAQEBAQEBAQEBAQEBGjaAoHA4H0AwIDUAJqUQAAAYAAAAEAAAAAAAAAAAAAAAAAAAAAAA/gBESkNQNoBMUTEzM00xAAAAAAACQQMoABIAAAsBCiAgAGY=", "description": "32/2015", "manufacturer": "Sharp Corporation", "serial": "00000000", "name": "32/2015", "manufacturers_id": "Sharp Corporation"}'
         ], [ //no name, no description
            'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <MONITORS>
      <BASE64>AP///////wBNEEkUAAAAACAZAQSlHRF4Dt5Qo1RMmSYPUFQAAAABAQEBAQEBAQEBAQEBAQEBGjaAoHA4H0AwIDUAJqUQAAAYAAAAEAAAAAAAAAAAAAAAAAAAAAAA/gBESkNQNoBMUTEzM00xAAAAAAACQQMoABIAAAsBCiAgAGY=</BASE64>
      <MANUFACTURER>Sharp Corporation</MANUFACTURER>
      <SERIAL>00000000</SERIAL>
    </MONITORS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
            'expected'  => '{"base64": "AP///////wBNEEkUAAAAACAZAQSlHRF4Dt5Qo1RMmSYPUFQAAAABAQEBAQEBAQEBAQEBAQEBGjaAoHA4H0AwIDUAJqUQAAAYAAAAEAAAAAAAAAAAAAAAAAAAAAAA/gBESkNQNoBMUTEzM00xAAAAAAACQQMoABIAAAsBCiAgAGY=", "manufacturer": "Sharp Corporation", "serial": "00000000", "name": "", "manufacturers_id": "Sharp Corporation"}'
         ], [ //no serial, no manufacturer
            'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <MONITORS>
      <BASE64>AP///////wBNEEkUAAAAACAZAQSlHRF4Dt5Qo1RMmSYPUFQAAAABAQEBAQEBAQEBAQEBAQEBGjaAoHA4H0AwIDUAJqUQAAAYAAAAEAAAAAAAAAAAAAAAAAAAAAAA/gBESkNQNoBMUTEzM00xAAAAAAACQQMoABIAAAsBCiAgAGY=</BASE64>
      <CAPTION>DJCP6</CAPTION>
      <DESCRIPTION>32/2015</DESCRIPTION>
    </MONITORS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
            'expected'  => '{"base64": "AP///////wBNEEkUAAAAACAZAQSlHRF4Dt5Qo1RMmSYPUFQAAAABAQEBAQEBAQEBAQEBAQEBGjaAoHA4H0AwIDUAJqUQAAAYAAAAEAAAAAAAAAAAAAAAAAAAAAAA/gBESkNQNoBMUTEzM00xAAAAAAACQQMoABIAAAsBCiAgAGY=", "caption": "DJCP6", "description": "32/2015", "serial": "", "name": "DJCP6", "manufacturers_id": ""}'
         ], [
            'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <MONITORS>
      <BASE64>AP///////wAmzQth5AIAAAMaAQOANB14KizFpFZQoSgPUFS/7wDRwIGAlQCzAIFAcU+VDwEBAjqAGHE4LUBYLEUACSUhAAAeAAAA/QA3TB5TEQAKICAgICAgAAAA/wAxMTI2MVY2MTAwNzQwAAAA/ABQTDI0ODBICiAgICAgAdACAx7BSwECAwQFEBESExQfIwkHAYMBAABlAwwAEACMCtCKIOAtEBA+lgAJJSEAABgBHQByUdAeIG4oVQAJJSEAAB6MCtCQIEAxIAxAVQAJJSEAABgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAnw==</BASE64>
      <CAPTION>PL2480H</CAPTION>
      <DESCRIPTION>3/2016</DESCRIPTION>
      <MANUFACTURER>Iiyama North America</MANUFACTURER>
      <SERIAL>11261V6100740</SERIAL>
    </MONITORS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
            'expected'  => '{"base64": "AP///////wAmzQth5AIAAAMaAQOANB14KizFpFZQoSgPUFS/7wDRwIGAlQCzAIFAcU+VDwEBAjqAGHE4LUBYLEUACSUhAAAeAAAA/QA3TB5TEQAKICAgICAgAAAA/wAxMTI2MVY2MTAwNzQwAAAA/ABQTDI0ODBICiAgICAgAdACAx7BSwECAwQFEBESExQfIwkHAYMBAABlAwwAEACMCtCKIOAtEBA+lgAJJSEAABgBHQByUdAeIG4oVQAJJSEAAB6MCtCQIEAxIAxAVQAJJSEAABgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAnw==", "caption": "PL2480H", "description": "3/2016", "manufacturer": "Iiyama North America", "serial": "11261V6100740", "name": "PL2480H", "manufacturers_id": "Iiyama North America"}'
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
      $asset = new \Glpi\Inventory\Asset\Monitor($computer, $json->content->monitors);
      $asset->setExtraData((array)$json->content);
      $result = $asset->prepare();
      $this->object($result[0])->isEqualTo(json_decode($expected));
   }

   public function testHandle() {
      $computer = getItemByTypeName('Computer', '_test_pc01');

      //first, check there are no controller linked to this computer
      $ico = new \Computer_Item();
      $this->boolean($ico->getFromDbByCrit(['computers_id' => $computer->fields['id'], 'itemtype' => 'Monitor']))
           ->isFalse('A monitor is already linked to computer!');

      //convert data
      $expected = $this->assetProvider()[0];

      $converter = new \Glpi\Inventory\Converter;
      $data = $converter->convert($expected['xml']);
      $json = json_decode($data);

      $computer = getItemByTypeName('Computer', '_test_pc01');
      $asset = new \Glpi\Inventory\Asset\Monitor($computer, $json->content->monitors);
      $asset->setExtraData((array)$json->content);
      $result = $asset->prepare();
      $this->object($result[0])->isEqualTo(json_decode($expected['expected']));

      $agent = new \Agent();
      $agent->getEmpty();
      $asset->setAgent($agent);

      //handle
      $asset->handleLinks();
      $asset->handle();
      $this->boolean($ico->getFromDbByCrit(['computers_id' => $computer->fields['id'], 'itemtype' => 'Monitor']))
           ->isTrue('Monitor has not been linked to computer :(');
   }
}
