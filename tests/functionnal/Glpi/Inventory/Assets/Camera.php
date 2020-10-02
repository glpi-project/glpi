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

/* Test for inc/inventory/asset/camera.class.php */

class Camera extends AbstractInventoryAsset {

   protected function assetProvider() :array {
      return [
         [
            'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <CAMERAS>
      <RESOLUTION>800x600</RESOLUTION>
      <RESOLUTION>8000x6000</RESOLUTION>
      <LENSFACING>BACK</LENSFACING>
      <FLASHUNIT>1</FLASHUNIT>
      <IMAGEFORMATS>RAW_SENSOR</IMAGEFORMATS>
      <IMAGEFORMATS>JPEG</IMAGEFORMATS>
      <IMAGEFORMATS></IMAGEFORMATS>
      <IMAGEFORMATS>YUV_420_888</IMAGEFORMATS>
      <IMAGEFORMATS></IMAGEFORMATS>
      <IMAGEFORMATS>RAW10</IMAGEFORMATS>
      <ORIENTATION>90</ORIENTATION>
      <FOCALLENGTH>4.77</FOCALLENGTH>
      <SENSORSIZE>6.4x4.8</SENSORSIZE>
      <MANUFACTURER></MANUFACTURER>
      <RESOLUTIONVIDEO>176x144</RESOLUTIONVIDEO>
      <RESOLUTIONVIDEO>1760x1440</RESOLUTIONVIDEO>
      <SUPPORTS></SUPPORTS>
      <MODEL></MODEL>
    </CAMERAS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
            'expected'  => '{"resolution":["800x600","8000x6000"],"lensfacing":"BACK","flashunit":"1","imageformats":["RAW_SENSOR","JPEG","YUV_420_888","RAW10"],"orientation":"90","focallength":"4.77","sensorsize":"6.4x4.8","resolutionvideo":["176x144","1760x1440"]}'
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
      $asset = new \Glpi\Inventory\Asset\Camera($computer, $json->content->cameras);
      $asset->setExtraData((array)$json->content);
      $result = $asset->prepare();
      $this->object($result[0])->isEqualTo(json_decode($expected));
   }

   public function testHandle() {
      $computer = getItemByTypeName('Computer', '_test_pc01');

      //first, check there are no camera linked to this computer
      $idc = new \Item_DeviceCamera();
      $this->boolean($idc->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isFalse('A camera is already linked to computer!');

      //convert data
      $expected = $this->assetProvider()[0];

      $converter = new \Glpi\Inventory\Converter;
      $data = $converter->convert($expected['xml']);
      $json = json_decode($data);

      $computer = getItemByTypeName('Computer', '_test_pc01');
      $asset = new \Glpi\Inventory\Asset\Camera($computer, $json->content->cameras);
      $asset->setExtraData((array)$json->content);
      $result = $asset->prepare();
      $this->object($result[0])->isEqualTo(json_decode($expected['expected']));

      //handle
      $asset->handleLinks();
      $asset->handle();
      $this->boolean($idc->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isTrue('Camera has not been linked to computer :(');

      global $DB;

      //four resolutions has been created
      $iterator = $DB->request(['FROM' => \ImageResolution::getTable()]);
      $this->integer(count($iterator))->isIdenticalTo(4);

      //four images formats has been created
      $iterator = $DB->request(['FROM' => \ImageFormat::getTable()]);
      $this->integer(count($iterator))->isIdenticalTo(4);

      //four links between images  formats and camera has been created
      $iterator = $DB->request(['FROM' => \Item_DeviceCamera_ImageFormat::getTable()]);
      $this->integer(count($iterator))->isIdenticalTo(4);

      //four links between images resolutions and camera has been created
      $iterator = $DB->request(['FROM' => \Item_DeviceCamera_ImageResolution::getTable()]);
      $this->integer(count($iterator))->isIdenticalTo(4);
   }
}
