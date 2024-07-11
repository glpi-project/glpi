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

/* Test for inc/inventory/asset/processos.class.php */

class ProcessorTest extends AbstractInventoryAsset
{
    public static function assetProvider(): array
    {
        return [
            [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <CPUS>
      <ARCH>i386</ARCH>
      <CORE>2</CORE>
      <EXTERNAL_CLOCK>100</EXTERNAL_CLOCK>
      <FAMILYNAME>Core i5</FAMILYNAME>
      <FAMILYNUMBER>6</FAMILYNUMBER>
      <ID>E3 06 04 00 FF FB EB BF</ID>
      <MANUFACTURER>Intel</MANUFACTURER>
      <MODEL>78</MODEL>
      <NAME>Intel(R) Core(TM) i5-6200U CPU @ 2.30GHz</NAME>
      <SERIAL>To Be Filled By O.E.M.</SERIAL>
      <SPEED>2300</SPEED>
      <STEPPING>3</STEPPING>
      <THREAD>4</THREAD>
    </CPUS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"arch": "i386", "core": 2, "external_clock": 100, "familyname": "Core i5", "familynumber": "6", "internalid": "E3 06 04 00 FF FB EB BF", "manufacturer": "Intel", "model": "78", "name": "Intel(R) Core(TM) i5-6200U CPU @ 2.30GHz", "serial": "To Be Filled By O.E.M.", "speed": 2300, "stepping": 3, "thread": 4, "frequency": 2300, "manufacturers_id": "Intel", "designation": "Intel(R) Core(TM) i5-6200U CPU @ 2.30GHz", "nbcores": 2, "nbthreads": 4, "frequency_default": 2300, "frequence": 2300, "is_dynamic": 1}'
            ],
            [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
<CONTENT>
  <CPUS>
    <ARCH>i386</ARCH>
    <CORE>2</CORE>
    <EXTERNAL_CLOCK>100</EXTERNAL_CLOCK>
    <FAMILYNAME>Core i5</FAMILYNAME>
    <FAMILYNUMBER>6</FAMILYNUMBER>
    <ID>E3 06 04 00 FF FB EB BF</ID>
    <MANUFACTURER>Intel</MANUFACTURER>
    <MODEL>78</MODEL>
    <NAME>Intel(R) Core(TM) i5-6200U CPU @ 2.30GHz</NAME>
    <SERIAL>To Be Filled By O.E.M.</SERIAL>
    <STEPPING>3</STEPPING>
    <THREAD>4</THREAD>
  </CPUS>
  <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
</CONTENT>
<DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
<QUERY>INVENTORY</QUERY>
</REQUEST>",
                'expected'  => '{"arch": "i386", "core": 2, "external_clock": 100, "familyname": "Core i5", "familynumber": "6", "internalid": "E3 06 04 00 FF FB EB BF", "manufacturer": "Intel", "model": "78", "name": "Intel(R) Core(TM) i5-6200U CPU @ 2.30GHz", "serial": "To Be Filled By O.E.M.", "stepping": 3, "thread": 4, "frequency": 2300, "manufacturers_id": "Intel", "designation": "Intel(R) Core(TM) i5-6200U CPU @ 2.30GHz", "nbcores": 2, "nbthreads": 4, "frequency_default": 0, "frequency": 0, "frequence": 0, "is_dynamic": 1}'
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
        $asset = new \Glpi\Inventory\Asset\Processor($computer, $json->content->cpus);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected), $result[0]);
    }

    public function testHandle()
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');

       //first, check there are no controller linked to this computer
        $idp = new \Item_DeviceProcessor();
                 $this->assertFalse(
                     $idp->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']),
                     'A processor is already linked to computer!'
                 );

       //convert data
        $expected = $this->assetProvider()[0];

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\Processor($computer, $json->content->cpus);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected['expected']), $result[0]);

       //handle
        $asset->handleLinks();
        $asset->handle();
        $this->assertTrue(
            $idp->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']),
            'Processor has not been linked to computer :('
        );
    }

    public function testInventoryUpdate()
    {
        $computer = new \Computer();
        $device_proc = new \DeviceProcessor();
        $item_proc = new \Item_DeviceProcessor();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <CPUS>
      <NAME>Intel(R) Core(TM) i9</NAME>
      <ARCH>x86_64</ARCH>
      <CORE>4</CORE>
      <MANUFACTURER>Intel</MANUFACTURER>
      <SPEED>2300</SPEED>
    </CPUS>
    <CPUS>
      <NAME>Intel(R) Core(TM) i5</NAME>
      <ARCH>i386</ARCH>
      <CORE>2</CORE>
      <EXTERNAL_CLOCK>100</EXTERNAL_CLOCK>
      <FAMILYNAME>Core i5</FAMILYNAME>
      <FAMILYNUMBER>6</FAMILYNUMBER>
      <ID>E3 06 04 00 FF FB EB BF</ID>
      <MANUFACTURER>Intel</MANUFACTURER>
      <MODEL>78</MODEL>
      <SERIAL>To Be Filled By O.E.M.</SERIAL>
      <SPEED>2000</SPEED>
      <STEPPING>3</STEPPING>
      <THREAD>4</THREAD>
    </CPUS>
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

       //create manually a computer, with 3 processors
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $manufacturer = new \Manufacturer();
        $manufacturers_id = $manufacturer->add([
            'name' => 'Intel'
        ]);
        $this->assertGreaterThan(0, $manufacturers_id);

        $cpu_1_id = $device_proc->add([
            'designation' => 'Intel(R) Core(TM) i9',
            'manufacturers_id' => $manufacturers_id,
            'frequence' => '2300',
            'entities_id'  => 0
        ]);
        $this->assertGreaterThan(0, $cpu_1_id);

        $item_cpu_1_id = $item_proc->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'deviceprocessors_id' => $cpu_1_id
        ]);
        $this->assertGreaterThan(0, $item_cpu_1_id);

        $cpu_2_id = $device_proc->add([
            'designation' => 'Intel(R) Core(TM) i5',
            'manufacturers_id' => $manufacturers_id,
            'frequence' => '2000',
            'entities_id'  => 0
        ]);
        $this->assertGreaterThan(0, $cpu_2_id);

        $item_cpu_2_id = $item_proc->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'deviceprocessors_id' => $cpu_2_id
        ]);
        $this->assertGreaterThan(0, $item_cpu_2_id);

        $cpu_3_id = $device_proc->add([
            'designation' => 'Intel(R) Core(TM) i3',
            'manufacturers_id' => $manufacturers_id,
            'frequence' => '1800',
            'entities_id'  => 0
        ]);
        $this->assertGreaterThan(0, $cpu_3_id);

        $item_cpu_3_id = $item_proc->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'deviceprocessors_id' => $cpu_3_id
        ]);
        $this->assertGreaterThan(0, $item_cpu_3_id);

        $cpus = $item_proc->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(3, $cpus);
        foreach ($cpus as $cpu) {
            $this->assertEquals(0, $cpu['is_dynamic']);
        }

       //computer inventory knows only "i9" and "i5" processors
        $this->doInventory($xml_source, true);

       //we still have 3 processors
        $cpus = $device_proc->find();
        $this->assertCount(3, $cpus);

       //we still have 3 processors items linked to the computer
        $cpus = $item_proc->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(3, $cpus);

       //processors present in the inventory source are now dynamic
        $cpus = $item_proc->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(2, $cpus);

       //processor not present in the inventory is still not dynamic
        $cpus = $item_proc->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->assertCount(1, $cpus);

       //Redo inventory, but with removed "i5" processor
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <CPUS>
      <NAME>Intel(R) Core(TM) i9</NAME>
      <ARCH>x86_64</ARCH>
      <CORE>4</CORE>
      <MANUFACTURER>Intel</MANUFACTURER>
      <SPEED>2300</SPEED>
    </CPUS>
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

       //we still have 3 processors
        $cpus = $device_proc->find();
        $this->assertCount(3, $cpus);

       //we now have 2 processors linked to computer only
        $cpus = $item_proc->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(2, $cpus);

       //processor present in the inventory source is still dynamic
        $cpus = $item_proc->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $cpus);

       //processor not present in the inventory is still not dynamic
        $cpus = $item_proc->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->assertCount(1, $cpus);
    }
}
