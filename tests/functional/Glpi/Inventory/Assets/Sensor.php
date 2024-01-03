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

/* Test for inc/inventory/asset/sensor.class.php */

class Sensor extends AbstractInventoryAsset
{
    protected function assetProvider(): array
    {
        return [
            [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SENSORS>
      <NAME>LSM330DLC 3-axis Accelerometer</NAME>
      <MANUFACTURER>STMicroelectronics</MANUFACTURER>
      <TYPE>ACCELEROMETER</TYPE>
      <POWER>0.23</POWER>
      <VERSION>1</VERSION>
    </SENSORS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"name": "LSM330DLC 3-axis Accelerometer", "manufacturer": "STMicroelectronics", "type": "ACCELEROMETER", "version": "1", "manufacturers_id": "STMicroelectronics", "devicesensortypes_id": "ACCELEROMETER", "designation": "LSM330DLC 3-axis Accelerometer", "is_dynamic": 1}'
            ], [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SENSORS>
      <NAME>AK8975C 3-axis Magnetic field sensor</NAME>
      <MANUFACTURER>Asahi Kasei Microdevices</MANUFACTURER>
      <TYPE>MAGNETIC FIELD</TYPE>
      <POWER>6.8</POWER>
      <VERSION>1</VERSION>
    </SENSORS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"name": "AK8975C 3-axis Magnetic field sensor", "manufacturer": "Asahi Kasei Microdevices", "type": "MAGNETIC FIELD", "version": "1", "manufacturers_id": "Asahi Kasei Microdevices", "devicesensortypes_id": "MAGNETIC FIELD", "designation": "AK8975C 3-axis Magnetic field sensor", "is_dynamic": 1}'
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
        $asset = new \Glpi\Inventory\Asset\Sensor($computer, $json->content->sensors);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->object($result[0])->isEqualTo(json_decode($expected));
    }

    public function testHandle()
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');

       //first, check there are no sensor linked to this computer
        $ids = new \Item_DeviceSensor();
        $this->boolean($ids->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isFalse('A sensor is already linked to computer!');

       //convert data
        $expected = $this->assetProvider()[0];

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\Sensor($computer, $json->content->sensors);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->object($result[0])->isEqualTo(json_decode($expected['expected']));

       //handle
        $asset->handleLinks();
        $asset->handle();
        $this->boolean($ids->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isTrue('Sensor has not been linked to computer :(');
    }

    public function testInventoryUpdate()
    {
        $computer = new \Computer();
        $device_sensor = new \DeviceSensor();
        $item_sensor = new \Item_DeviceSensor();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SENSORS>
      <NAME>LSM330DLC 3-axis Accelerometer</NAME>
      <MANUFACTURER>STMicroelectronics</MANUFACTURER>
      <TYPE>ACCELEROMETER</TYPE>
      <POWER>0.23</POWER>
      <VERSION>1</VERSION>
    </SENSORS>
    <SENSORS>
      <NAME>AK8975C 3-axis Magnetic field sensor</NAME>
      <MANUFACTURER>Asahi Kasei Microdevices</MANUFACTURER>
      <TYPE>MAGNETIC FIELD</TYPE>
      <POWER>6.8</POWER>
      <VERSION>1</VERSION>
    </SENSORS>
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

       //create manually a computer, with 3 sensors
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0
        ]);
        $this->integer($computers_id)->isGreaterThan(0);

        $manufacturer = new \Manufacturer();
        $manufacturers_id = $manufacturer->add([
            'name' => 'STMicroelectronics'
        ]);
        $this->integer($manufacturers_id)->isGreaterThan(0);

        $type = new \DeviceSensorType();
        $types_id = $type->add([
            'name' => 'ACCELEROMETER'
        ]);
        $this->integer($types_id)->isGreaterThan(0);

        $sensor_1_id = $device_sensor->add([
            'designation' => 'LSM330DLC 3-axis Accelerometer',
            'manufacturers_id' => $manufacturers_id,
            'devicesensortypes_id' => $types_id,
            'locations_id' => 0,
            'entities_id'  => 0
        ]);
        $this->integer($sensor_1_id)->isGreaterThan(0);

        $item_sensor_1_id = $item_sensor->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicesensors_id' => $sensor_1_id
        ]);
        $this->integer($item_sensor_1_id)->isGreaterThan(0);

        $manufacturers_id = $manufacturer->add([
            'name' => 'Asahi Kasei Microdevices'
        ]);
        $this->integer($manufacturers_id)->isGreaterThan(0);

        $types_id = $type->add([
            'name' => 'MAGNETIC FIELD'
        ]);
        $this->integer($types_id)->isGreaterThan(0);

        $sensor_2_id = $device_sensor->add([
            'designation' => 'AK8975C 3-axis Magnetic field sensor',
            'manufacturers_id' => $manufacturers_id,
            'devicesensortypes_id' => $types_id,
            'locations_id' => 0,
            'entities_id'  => 0

        ]);
        $this->integer($sensor_2_id)->isGreaterThan(0);

        $item_sensor_2_id = $item_sensor->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicesensors_id' => $sensor_2_id
        ]);
        $this->integer($item_sensor_2_id)->isGreaterThan(0);

        $sensor_3_id = $device_sensor->add([
            'designation' => 'TMG399X RGB Sensor',
            'manufacturers_id' => $manufacturers_id,
            'devicesensortypes_id' => $types_id,
            'locations_id' => 0,
            'entities_id'  => 0
        ]);
        $this->integer($sensor_3_id)->isGreaterThan(0);

        $item_sensor_3_id = $item_sensor->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicesensors_id' => $sensor_3_id
        ]);
        $this->integer($item_sensor_3_id)->isGreaterThan(0);

        $sensors = $item_sensor->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($sensors))->isIdenticalTo(3);
        foreach ($sensors as $sensor) {
            $this->variable($sensor['is_dynamic'])->isEqualTo(0);
        }

       //computer inventory knows only "LSM330DLC" and "AK8975C" sensors
        $this->doInventory($xml_source, true);

       //we still have 3 sensors (+ 1 from bootstrap)
        $sensors = $device_sensor->find();
        $this->integer(count($sensors))->isIdenticalTo(3 + 1);

       //we still have 3 sensors items linked to the computer
        $sensors = $item_sensor->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($sensors))->isIdenticalTo(3);

       //sensors present in the inventory source are now dynamic
        $sensors = $item_sensor->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($sensors))->isIdenticalTo(2);

       //sensor not present in the inventory is still not dynamic
        $sensors = $item_sensor->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->integer(count($sensors))->isIdenticalTo(1);

       //Redo inventory, but with removed "AK8975C" sensor
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SENSORS>
      <NAME>LSM330DLC 3-axis Accelerometer</NAME>
      <MANUFACTURER>STMicroelectronics</MANUFACTURER>
      <TYPE>ACCELEROMETER</TYPE>
      <POWER>0.23</POWER>
      <VERSION>1</VERSION>
    </SENSORS>
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

       //we still have 3 sensors (+1 from bootstrap)
        $sensors = $device_sensor->find();
        $this->integer(count($sensors))->isIdenticalTo(3 + 1);

       //we now have 2 sensors linked to computer only
        $sensors = $item_sensor->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($sensors))->isIdenticalTo(2);

       //sensor present in the inventory source is still dynamic
        $sensors = $item_sensor->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($sensors))->isIdenticalTo(1);

       //sensor not present in the inventory is still not dynamic
        $sensors = $item_sensor->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->integer(count($sensors))->isIdenticalTo(1);
    }
}
