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

/* Test for inc/inventory/asset/computer.class.php */

class Software extends AbstractInventoryAsset {

   protected function assetProvider() :array {
      return [
         [
            'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SOFTWARES>
      <ARCH>x86_64</ARCH>
      <COMMENTS>GNU Image Manipulation Program</COMMENTS>
      <FILESIZE>67382735</FILESIZE>
      <FROM>rpm</FROM>
      <INSTALLDATE>03/09/2018</INSTALLDATE>
      <NAME>gimp</NAME>
      <PUBLISHER>Fedora Project</PUBLISHER>
      <SYSTEM_CATEGORY>Unspecified</SYSTEM_CATEGORY>
      <VERSION>2.8.22-7.fc28</VERSION>
    </SOFTWARES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
            'expected'  => '{"arch": "x86_64", "comments": "GNU Image Manipulation Program", "filesize": 67382735, "from": "rpm", "name": "gimp", "publisher": "Fedora Project", "system_category": "Unspecified", "version": "2.8.22-7.fc28", "install_date": "2018-09-03", "manufacturers_id": "Feodra Project", "comment": "GNU Image Manipulation Program", "_system_category": "Unspecified", "operatingsystems_id": 0, "entities_id": 0, "is_template_item": 0, "is_deleted_item": 0, "is_recursive": 0, "date_install": "2018-09-03"}'
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
      $asset = new \Glpi\Inventory\Asset\Software($computer, $json->content->softwares);
      $asset->setExtraData((array)$json->content);
      $result = $asset->prepare();

      //Manufacturer has been imported into db...
      $expected = json_decode($expected);
      $manu = new \Manufacturer();
      $this->boolean($manu->getFromDbByCrit(['name' => $result[0]->publisher]))->isTrue();
      $expected->manufacturers_id = $manu->fields['id'];
      $this->object($result[0])->isEqualTo($expected);
   }

   public function testHandle() {
      $computer = getItemByTypeName('Computer', '_test_pc01');

      //first, check there are no AV linked to this computer
      $sov = new \Item_SoftwareVersion();
      $this->boolean($sov->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isFalse('A software version is already linked to computer!');

      //convert data
      $expected = $this->assetProvider()[0];

      $converter = new \Glpi\Inventory\Converter;
      $data = $converter->convert($expected['xml']);
      $json = json_decode($data);

      $computer = getItemByTypeName('Computer', '_test_pc01');
      $asset = new \Glpi\Inventory\Asset\Software($computer, $json->content->softwares);
      $asset->setExtraData((array)$json->content);
      $result = $asset->prepare();
      $expected = json_decode($expected['expected']);
      $manu = new \Manufacturer();
      $this->boolean($manu->getFromDbByCrit(['name' => $result[0]->publisher]))->isTrue();
      $expected->manufacturers_id = $manu->fields['id'];
      $this->object($result[0])->isEqualTo($expected);

      //handle
      $asset->handleLinks();
      $asset->handle();
      $this->boolean($sov->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isTrue('A software version has not been linked to computer!');
   }
}
