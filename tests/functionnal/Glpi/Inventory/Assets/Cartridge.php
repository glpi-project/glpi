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

/* Test for inc/inventory/asset/cartridge.class.php */

class Cartridge extends AbstractInventoryAsset {

   protected function assetProvider() :array {
      return [
         [
            'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST><CONTENT><DEVICE>
      <CARTRIDGES>
        <TONERBLACK>71</TONERBLACK>
      </CARTRIDGES>
      <FIRMWARES>
        <DATE>20190916</DATE>
        <DESCRIPTION>device firmware</DESCRIPTION>
        <MANUFACTURER>Hewlett-Packard</MANUFACTURER>
        <NAME>HP LaserJet M507</NAME>
        <TYPE>device</TYPE>
        <VERSION>2409048_052887</VERSION>
      </FIRMWARES>
      <INFO>
        <COMMENTS>HP ETHERNET MULTI-ENVIRONMENT,ROM none,JETDIRECT,JD153,EEPROM JSI24090012,CIDATE 09/10/2019</COMMENTS>
        <FIRMWARE>2409048_052887</FIRMWARE>
        <ID>8997</ID>
        <IPS>
          <IP>10.59.29.175</IP>
        </IPS>
        <MAC>00:68:eb:f2:be:10</MAC>
        <MANUFACTURER>Hewlett-Packard</MANUFACTURER>
        <MODEL>HP LaserJet M507</MODEL>
        <NAME>NPIF2BE10</NAME>
        <RAM>512</RAM>
        <SERIAL>PHCVN191TG</SERIAL>
        <TYPE>PRINTER</TYPE>
        <UPTIME>7 days, 01:26:41.98</UPTIME>
      </INFO>
      <PAGECOUNTERS>
        <DUPLEX>831</DUPLEX>
        <TOTAL>1802</TOTAL>
      </PAGECOUNTERS>
    </DEVICE></CONTENT><QUERY>SNMP</QUERY><DEVICEID>foo</DEVICEID></REQUEST>",
            'expected'  => '{"tonerblack":"71","name":"","serial":"","manufacturers_id":""}'
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

      $printer = getItemByTypeName('Printer', '_test_printer_all');
      $asset = new \Glpi\Inventory\Asset\Monitor($printer, $json->content->cartridges);
      $asset->setExtraData((array)$json->content);
      $result = $asset->prepare();
      $this->object($result[0])->isEqualTo(json_decode($expected));
   }

   public function testKnownTags() {
      $cart = new \Glpi\Inventory\Asset\Cartridge(getItemByTypeName('Printer', '_test_printer_all'));

      $tags = $cart->knownTags();
      $this->array($tags)->hasSize(194);
      foreach ($tags as $tag) {
         $this->array($tag)->hasKey('name');
      }
   }

   public function testHandle() {
      //convert data
      $expected = $this->assetProvider()[0];

      $converter = new \Glpi\Inventory\Converter;
      $data = $converter->convert($expected['xml']);
      $json = json_decode($data);

      $printer = getItemByTypeName('Printer', '_test_printer_all');
      $asset = new \Glpi\Inventory\Asset\Cartridge($printer, $json->content->cartridges);
      $asset->setExtraData((array)$json->content);
      $result = $asset->prepare();
      $this->object($result[0])->isEqualTo(json_decode('{"tonerblack":"71"}'));

      $agent = new \Agent();
      $agent->getEmpty();
      $asset->setAgent($agent);

      //handle
      $asset->handleLinks();
      $asset->handle();

      global $DB;
      $iterator = $DB->request([
         'FROM'   => \Printer_CartridgeInfo::getTable(),
         'WHERE'  => ['printers_id' => $printer->fields['id']]
      ]);
      $this->integer(count($iterator))->isIdenticalTo(1);

      $result = $iterator->next();
      $this->array($result)
         ->string['property']->isIdenticalTo('tonerblack')
         ->string['value']->isIdenticalTo('71');
   }
}
