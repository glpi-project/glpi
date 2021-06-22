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

/* Test for inc/inventory/asset/printer.class.php */

class Printer extends AbstractInventoryAsset {

   protected function assetProvider() :array {
      return [
         [
            'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <PRINTERS>
      <DRIVER>HP Color LaserJet Pro MFP M476 PCL 6</DRIVER>
      <NAME>HP Color LaserJet Pro MFP M476 PCL 6</NAME>
      <NETWORK>0</NETWORK>
      <PORT>10.253.6.117</PORT>
      <PRINTPROCESSOR>hpcpp155</PRINTPROCESSOR>
      <RESOLUTION>600x600</RESOLUTION>
      <SHARED>0</SHARED>
      <SHARENAME>HP Color LaserJet Pro MFP M476 PCL 6  (1)</SHARENAME>
      <STATUS>Unknown</STATUS>
    </PRINTERS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
            'expected'  => '{"driver": "HP Color LaserJet Pro MFP M476 PCL 6", "name": "HP Color LaserJet Pro MFP M476 PCL 6", "network": false, "printprocessor": "hpcpp155", "resolution": "600x600", "shared": false, "sharename": "HP Color LaserJet Pro MFP M476 PCL 6  (1)", "status": "Unknown", "have_usb": 0, "autoupdatesystems_id": "GLPI Native Inventory"}'
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
      $asset = new \Glpi\Inventory\Asset\Printer($computer, $json->content->printers);
      $asset->setExtraData((array)$json->content);
      $result = $asset->prepare();
      $this->object($result[0])->isEqualTo(json_decode($expected));
   }

   public function testSnmpPrinter() {
      $json_str = file_get_contents(GLPI_ROOT . '/tests/fixtures/inventory/printer_1.json');
      $json = json_decode($json_str);

      $printer = new \Printer();

      $data = (array)$json->content;
      $inventory = new \Glpi\Inventory\Inventory();
      $this->boolean($inventory->setData($json_str))->isTrue();

      $agent = new \Agent();
      $this->integer($agent->handleAgent($inventory->extractMetadata()))->isGreaterThan(0);

      $main = new \Glpi\Inventory\Asset\Printer($printer, $json);
      $main->setAgent($agent)->setExtraData($data);
      $result = $main->prepare();
      $this->array($result)->hasSize(1);
      $this->array((array)$result[0])->isIdenticalTo([
         'autoupdatesystems_id' => 'GLPI Native Inventory',
         'comments' => 'HP ETHERNET MULTI-ENVIRONMENT,ROM none,JETDIRECT,JD153,EEPROM JSI24090012,CIDATE 09/10/2019',
         'firmware' => '2409048_052887',
         'id' => 8997,
         'ips' => ['10.59.29.175'],
         'mac' => '00:68:eb:f2:be:10',
         'manufacturer' => 'Hewlett-Packard',
         'model' => 'HP LaserJet M507',
         'name' => 'NPIF2BE10',
         'ram' => 512,
         'serial' => 'PHCVN191TG',
         'type' => 'Printer',
         'uptime' => '7 days, 01:26:41.98',
         'printermodels_id' => 'HP LaserJet M507',
         'printertypes_id' => 'Printer',
         'manufacturers_id' => 'Hewlett-Packard',
         'have_usb' => 0,
         'last_pages_counter' => 1802
      ]);

      //get one management port only, since iftype 24 is not importabel per default
      $this->array($main->getNetworkPorts())->isIdenticalTo([]);
      $this->array($mports = $main->getManagementPorts())->hasSize(1)->hasKey('management');
      $this->array((array)$mports['management'])->isIdenticalTo([
         'mac' => '00:68:eb:f2:be:10',
         'name' => 'Management',
         'netname' => 'internal',
         'instantiation_type' => 'NetworkPortAggregate',
         'is_internal' => true,
         'ipaddress' => [
            '10.59.29.175'
         ]
      ]);

      $pcounter = new \stdClass();
      $pcounter->duplex = 831;
      $pcounter->rv_pages = 831;
      $pcounter->total = 1802;
      $pcounter->total_pages = 1802;
      $this->object($main->getCounters())->isEqualTo($pcounter);

      $main->handle();

      $this->boolean($main->areLinksHandled())->isTrue();

      $this->boolean($printer->getFromDB($printer->fields['id']))->isTrue();
      $this->integer($printer->fields['last_pages_counter'])->isIdenticalTo($pcounter->total);

      global $DB;
      $iterator = $DB->request([
         'FROM'   => \PrinterLog::getTable(),
         'WHERE'  => ['printers_id' => $printer->fields['id']]
      ]);
      $this->integer(count($iterator))->isIdenticalTo(1);

      $result = $iterator->next();

      unset($result['id']);
      unset($result['date']);

      $this->array($result)->isIdenticalTo([
         'printers_id' => $printer->fields['id'],
         'total_pages' => 1802,
         'bw_pages' => 0,
         'color_pages' => 0,
         'rv_pages' => 831,
         'prints' => 0,
         'bw_prints' => 0,
         'color_prints' => 0,
         'copies' => 0,
         'bw_copies' => 0,
         'color_copies' => 0,
         'scanned' => 0,
         'faxed' => 0
      ]);
   }
}
