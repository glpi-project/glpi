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

/* Test for inc/inventory/asset/camera.class.php */

class Camera extends AbstractInventoryAsset
{
    protected function assetProvider(): array
    {
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
                'expected'  => '{"resolution":["800x600","8000x6000"],"lensfacing":"BACK","flashunit":"1","imageformats":["RAW_SENSOR","JPEG","YUV_420_888","RAW10"],"orientation":"90","focallength":"4.77","sensorsize":"6.4x4.8","resolutionvideo":["176x144","1760x1440"], "is_dynamic": 1}'
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
        $asset = new \Glpi\Inventory\Asset\Camera($computer, $json->content->cameras);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->object($result[0])->isEqualTo(json_decode($expected));
    }

    public function testHandle()
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');

       //first, check there are no camera linked to this computer
        $idc = new \Item_DeviceCamera();
        $this->boolean($idc->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isFalse('A camera is already linked to computer!');

       //convert data
        $expected = $this->assetProvider()[0];

        $converter = new \Glpi\Inventory\Converter();
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

    public function testInventoryUpdate()
    {
        $computer = new \Computer();
        $device_cam = new \DeviceCamera();
        $item_cam = new \Item_DeviceCamera();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <CAMERAS>
      <DESIGNATION>Front cam</DESIGNATION>
      <MANUFACTURER>Xiaomi</MANUFACTURER>
      <MODEL>Test</MODEL>
    </CAMERAS>
    <CAMERAS>
      <DESIGNATION>Rear cam</DESIGNATION>
      <MANUFACTURER>Xiaomi</MANUFACTURER>
      <MODEL>Test</MODEL>
    </CAMERAS>
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

       //create manually a computer, with 3 cameras
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0
        ]);
        $this->integer($computers_id)->isGreaterThan(0);

        $manufacturer = new \Manufacturer();
        $manufacturers_id = $manufacturer->add([
            'name' => 'Xiaomi'
        ]);
        $this->integer($manufacturers_id)->isGreaterThan(0);

        $model = new \DeviceCameraModel();
        $models_id = $model->add([
            'name' => 'test'
        ]);
        $this->integer($models_id)->isGreaterThan(0);

        $cam_1_id = $device_cam->add([
            'designation' => 'Front cam',
            'manufacturers_id' => $manufacturers_id,
            'devicecameramodels_id' => $models_id,
            'entities_id'  => 0
        ]);
        $this->integer($cam_1_id)->isGreaterThan(0);

        $item_cam_1_id = $item_cam->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicecameras_id' => $cam_1_id
        ]);

        $cam_2_id = $device_cam->add([
            'designation' => 'Rear cam',
            'manufacturers_id' => $manufacturers_id,
            'devicecameramodels_id' => $models_id,
            'entities_id'  => 0
        ]);
        $this->integer($cam_2_id)->isGreaterThan(0);

        $item_cam_2_id = $item_cam->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicecameras_id' => $cam_2_id
        ]);

        $cam_3_id = $device_cam->add([
            'designation' => 'Other cam',
            'manufacturers_id' => $manufacturers_id,
            'devicecameramodels_id' => $models_id,
            'entities_id'  => 0
        ]);
        $this->integer($cam_3_id)->isGreaterThan(0);

        $item_cam_3_id = $item_cam->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicecameras_id' => $cam_3_id
        ]);

        $cams = $item_cam->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($cams))->isIdenticalTo(3);
        foreach ($cams as $cam) {
            $this->variable($cam['is_dynamic'])->isEqualTo(0);
        }

       //computer inventory knows only "Front" and "Rear" cameras
        $this->doInventory($xml_source, true);

       //we still have 3 cameras
        $cams = $device_cam->find();
        $this->integer(count($cams))->isIdenticalTo(3);

       //we still have 3 cameras items linked to the computer
        $cams = $item_cam->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($cams))->isIdenticalTo(3);

       //cameras present in the inventory source are now dynamic
        $cams = $item_cam->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($cams))->isIdenticalTo(2);

       //camera not present in the inventory is still not dynamic
        $cams = $item_cam->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->integer(count($cams))->isIdenticalTo(1);

       //Redo inventory, but with removed "Rear" camera
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <CAMERAS>
      <DESIGNATION>Front cam</DESIGNATION>
      <MANUFACTURER>Xiaomi</MANUFACTURER>
      <MODEL>Test</MODEL>
    </CAMERAS>
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

       //we still have 3 cameras
        $cams = $device_cam->find();
        $this->integer(count($cams))->isIdenticalTo(3);

       //we now have 2 cameras linked to computer only
        $cams = $item_cam->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($cams))->isIdenticalTo(2);

       //camera present in the inventory source is still dynamic
        $cams = $item_cam->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($cams))->isIdenticalTo(1);

       //camera not present in the inventory is still not dynamic
        $cams = $item_cam->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->integer(count($cams))->isIdenticalTo(1);
    }
}
