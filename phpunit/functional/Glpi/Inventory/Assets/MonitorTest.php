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

/* Test for inc/inventory/asset/monitor.class.php */

class MonitorTest extends AbstractInventoryAsset
{
    public static function assetProvider(): array
    {
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
                'expected'  => '{"base64": "AP///////wBNEEkUAAAAACAZAQSlHRF4Dt5Qo1RMmSYPUFQAAAABAQEBAQEBAQEBAQEBAQEBGjaAoHA4H0AwIDUAJqUQAAAYAAAAEAAAAAAAAAAAAAAAAAAAAAAA/gBESkNQNoBMUTEzM00xAAAAAAACQQMoABIAAAsBCiAgAGY=", "caption": "DJCP6", "description": "32/2015", "manufacturer": "Sharp Corporation", "serial": "AFGHHDR0", "name": "DJCP6", "manufacturers_id": "Sharp Corporation", "monitormodels_id": "DJCP6", "is_dynamic": 1}'
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
                'expected'  => '{"base64": "AP///////wBNEEkUAAAAACAZAQSlHRF4Dt5Qo1RMmSYPUFQAAAABAQEBAQEBAQEBAQEBAQEBGjaAoHA4H0AwIDUAJqUQAAAYAAAAEAAAAAAAAAAAAAAAAAAAAAAA/gBESkNQNoBMUTEzM00xAAAAAAACQQMoABIAAAsBCiAgAGY=", "description": "32/2015", "manufacturer": "Sharp Corporation", "serial": "00000000", "name": "32/2015", "manufacturers_id": "Sharp Corporation", "is_dynamic": 1}'
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
                'expected'  => '{"base64": "AP///////wBNEEkUAAAAACAZAQSlHRF4Dt5Qo1RMmSYPUFQAAAABAQEBAQEBAQEBAQEBAQEBGjaAoHA4H0AwIDUAJqUQAAAYAAAAEAAAAAAAAAAAAAAAAAAAAAAA/gBESkNQNoBMUTEzM00xAAAAAAACQQMoABIAAAsBCiAgAGY=", "manufacturer": "Sharp Corporation", "serial": "00000000", "name": "", "manufacturers_id": "Sharp Corporation", "is_dynamic": 1}'
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
                'expected'  => '{"base64": "AP///////wBNEEkUAAAAACAZAQSlHRF4Dt5Qo1RMmSYPUFQAAAABAQEBAQEBAQEBAQEBAQEBGjaAoHA4H0AwIDUAJqUQAAAYAAAAEAAAAAAAAAAAAAAAAAAAAAAA/gBESkNQNoBMUTEzM00xAAAAAAACQQMoABIAAAsBCiAgAGY=", "caption": "DJCP6", "description": "32/2015", "serial": "", "name": "DJCP6", "manufacturers_id": "", "monitormodels_id": "DJCP6", "is_dynamic": 1}'
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
                'expected'  => '{"base64": "AP///////wAmzQth5AIAAAMaAQOANB14KizFpFZQoSgPUFS/7wDRwIGAlQCzAIFAcU+VDwEBAjqAGHE4LUBYLEUACSUhAAAeAAAA/QA3TB5TEQAKICAgICAgAAAA/wAxMTI2MVY2MTAwNzQwAAAA/ABQTDI0ODBICiAgICAgAdACAx7BSwECAwQFEBESExQfIwkHAYMBAABlAwwAEACMCtCKIOAtEBA+lgAJJSEAABgBHQByUdAeIG4oVQAJJSEAAB6MCtCQIEAxIAxAVQAJJSEAABgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAnw==", "caption": "PL2480H", "description": "3/2016", "manufacturer": "Iiyama North America", "serial": "11261V6100740", "name": "PL2480H", "manufacturers_id": "Iiyama North America", "monitormodels_id": "PL2480H", "is_dynamic": 1}'
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
        $asset = new \Glpi\Inventory\Asset\Monitor($computer, $json->content->monitors);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected), $result[0]);
    }

    public function testHandle()
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');

       //first, check there are no monitor linked to this computer
        $ico = new \Computer_Item();
                 $this->assertFalse(
                     $ico->getFromDbByCrit(['computers_id' => $computer->fields['id'], 'itemtype' => 'Monitor']),
                     'A monitor is already linked to computer!'
                 );

       //convert data
        $expected = $this->assetProvider()[0];

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\Monitor($computer, $json->content->monitors);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected['expected']), $result[0]);

        $agent = new \Agent();
        $agent->getEmpty();
        $asset->setAgent($agent);

       //handle
        $asset->handleLinks();
        $asset->handle();
        $this->assertTrue(
            $ico->getFromDbByCrit(['computers_id' => $computer->fields['id'], 'itemtype' => 'Monitor']),
            'Monitor has not been linked to computer :('
        );
    }

    public function testInventoryMove()
    {
        global $DB;

        $monitor = new \Monitor();
        $item_monitor = new \Computer_Item();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <MONITORS>
      <BASE64>AP///////wBNEEkUAAAAACAZAQSlHRF4Dt5Qo1RMmSYPUFQAAAABAQEBAQEBAQEBAQEBAQEBGjaAoHA4H0AwIDUAJqUQAAAYAAAAEAAAAAAAAAAAAAAAAAAAAAAA/gBESkNQNoBMUTEzM00xAAAAAAACQQMoABIAAAsBCiAgAGY=</BASE64>
      <CAPTION>DJCP6</CAPTION>
      <DESCRIPTION>32/2015</DESCRIPTION>
      <MANUFACTURER>Sharp Corporation</MANUFACTURER>
      <SERIAL>AFGHHDR0</SERIAL>
    </MONITORS>
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

        //computer inventory with one monitor
        $inventory = $this->doInventory($xml_source, true);

        //check for expected logs
        $nblogsnow = countElementsInTable(\Log::getTable());
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'LIMIT' => $nblogsnow,
            'OFFSET' => $this->nblogs,
            'WHERE' => [
                'NOT' => ['itemtype' => \Config::class]
            ]
        ]);
        $this->assertCount(0, $logs);

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Sharp Corporation']])->current();
        $this->assertIsArray($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $computers_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $computers_id);

        //we have 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $monitors);
        $this->assertSame($manufacturers_id, current($monitors)['manufacturers_id']);

        //we have 1 monitor items linked to the computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->assertCount(1, $monitors);

        //monitor present in the inventory source is dynamic
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $monitors);

        //same inventory again
        $inventory = $this->doInventory($xml_source, true);

        //check for expected logs
        $nblogsnow = countElementsInTable(\Log::getTable());
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'LIMIT' => $nblogsnow,
            'OFFSET' => $this->nblogs,
            'WHERE' => [
                'NOT' => ['itemtype' => \Config::class]
            ]
        ]);
        $this->assertCount(0, $logs);

        $computers_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $computers_id);

        //we still have only 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $monitors);
        $this->assertSame($manufacturers_id, current($monitors)['manufacturers_id']);

        //we still have only 1 monitor items linked to the computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->assertCount(1, $monitors);

        //same monitor, but on another computer
        $xml_source_2 = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <MONITORS>
      <BASE64>AP///////wBNEEkUAAAAACAZAQSlHRF4Dt5Qo1RMmSYPUFQAAAABAQEBAQEBAQEBAQEBAQEBGjaAoHA4H0AwIDUAJqUQAAAYAAAAEAAAAAAAAAAAAAAAAAAAAAAA/gBESkNQNoBMUTEzM00xAAAAAAACQQMoABIAAAsBCiAgAGY=</BASE64>
      <CAPTION>DJCP6</CAPTION>
      <DESCRIPTION>32/2015</DESCRIPTION>
      <MANUFACTURER>Sharp Corporation</MANUFACTURER>
      <SERIAL>AFGHHDR0</SERIAL>
    </MONITORS>
    <HARDWARE>
      <NAME>pc003</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>ggheb7ne8</SSN>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Agent_v2.3.19</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>test-pc003</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

        //computer inventory with one monitor
        $inventory = $this->doInventory($xml_source_2, true);

        //check for expected logs
        $nblogsnow = countElementsInTable(\Log::getTable());
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'LIMIT' => $nblogsnow,
            'OFFSET' => $this->nblogs,
            'WHERE' => [
                'NOT' => ['itemtype' => \Config::class]
            ]
        ]);
        $this->assertCount(0, $logs);

        $computers_2_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $computers_2_id);

        //we still have only 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $monitors);
        $this->assertSame($manufacturers_id, current($monitors)['manufacturers_id']);

        //no longer linked on first computer inventoried
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->assertCount(0, $monitors);

        //but now linked on last inventoried computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_2_id]);
        $this->assertCount(1, $monitors);

        //monitor is still dynamic
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_2_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $monitors);

        //replay first computer inventory, monitor is back \o/
        $inventory = $this->doInventory($xml_source, true);

        //check for expected logs
        $nblogsnow = countElementsInTable(\Log::getTable());
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'LIMIT' => $nblogsnow,
            'OFFSET' => $this->nblogs,
            'WHERE' => [
                'NOT' => ['itemtype' => \Config::class]
            ]
        ]);
        $this->assertCount(0, $logs);

        //we still have only 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $monitors);
        $this->assertSame($manufacturers_id, current($monitors)['manufacturers_id']);

        //linked again on first computer inventoried
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->assertCount(1, $monitors);

        //no longer linked on last inventoried computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_2_id]);
        $this->assertCount(0, $monitors);

        //monitor is still dynamic
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $monitors);
    }

    public function testInventoryNoMove()
    {
        global $DB;

        $monitor = new \Monitor();
        $item_monitor = new \Computer_Item();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <MONITORS>
      <BASE64>AP///////wBNEEkUAAAAACAZAQSlHRF4Dt5Qo1RMmSYPUFQAAAABAQEBAQEBAQEBAQEBAQEBGjaAoHA4H0AwIDUAJqUQAAAYAAAAEAAAAAAAAAAAAAAAAAAAAAAA/gBESkNQNoBMUTEzM00xAAAAAAACQQMoABIAAAsBCiAgAGY=</BASE64>
      <CAPTION>DJCP6</CAPTION>
      <DESCRIPTION>32/2015</DESCRIPTION>
      <MANUFACTURER>Sharp Corporation</MANUFACTURER>
      <SERIAL>AFGHHDR0</SERIAL>
    </MONITORS>
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

        //computer inventory with one monitor
        $inventory = $this->doInventory($xml_source, true);

        //check for expected logs
        $nblogsnow = countElementsInTable(\Log::getTable());
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'LIMIT' => $nblogsnow,
            'OFFSET' => $this->nblogs,
            'WHERE' => [
                'NOT' => ['itemtype' => \Config::class]
            ]
        ]);
        $this->assertCount(0, $logs);

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Sharp Corporation']])->current();
        $this->assertIsArray($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $computers_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $computers_id);

        //we have 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $monitors);
        $this->assertSame($manufacturers_id, current($monitors)['manufacturers_id']);

        //we have 1 monitor items linked to the computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->assertCount(1, $monitors);

        //monitor present in the inventory source is dynamic
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $monitors);

        //same inventory again
        $inventory = $this->doInventory($xml_source, true);

        //check for expected logs
        $nblogsnow = countElementsInTable(\Log::getTable());
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'LIMIT' => $nblogsnow,
            'OFFSET' => $this->nblogs,
            'WHERE' => [
                'NOT' => ['itemtype' => \Config::class]
            ]
        ]);
        $this->assertCount(0, $logs);

        $computers_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $computers_id);

        //we still have only 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $monitors);
        $this->assertSame($manufacturers_id, current($monitors)['manufacturers_id']);

        //we still have only 1 monitor items linked to the computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->assertCount(1, $monitors);

        //set to global management
        $this->assertTrue($monitor->getFromDB(current($monitors)['items_id']));
        $this->assertTrue($monitor->update(['id' => $monitor->fields['id'], 'is_global' => \Config::GLOBAL_MANAGEMENT]));

        //same monitor, but on another computer
        $xml_source_2 = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <MONITORS>
      <BASE64>AP///////wBNEEkUAAAAACAZAQSlHRF4Dt5Qo1RMmSYPUFQAAAABAQEBAQEBAQEBAQEBAQEBGjaAoHA4H0AwIDUAJqUQAAAYAAAAEAAAAAAAAAAAAAAAAAAAAAAA/gBESkNQNoBMUTEzM00xAAAAAAACQQMoABIAAAsBCiAgAGY=</BASE64>
      <CAPTION>DJCP6</CAPTION>
      <DESCRIPTION>32/2015</DESCRIPTION>
      <MANUFACTURER>Sharp Corporation</MANUFACTURER>
      <SERIAL>AFGHHDR0</SERIAL>
    </MONITORS>
    <HARDWARE>
      <NAME>pc003</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>ggheb7ne8</SSN>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Agent_v2.3.19</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>test-pc003</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

        //computer inventory with one monitor
        $inventory = $this->doInventory($xml_source_2, true);

        //check for expected logs
        $nblogsnow = countElementsInTable(\Log::getTable());
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'LIMIT' => $nblogsnow,
            'OFFSET' => $this->nblogs,
            'WHERE' => [
                'NOT' => ['itemtype' => \Config::class]
            ]
        ]);
        $this->assertCount(0, $logs);

        $computers_2_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $computers_2_id);

        //we still have only 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $monitors);
        $this->assertSame($manufacturers_id, current($monitors)['manufacturers_id']);

        //still linked on first computer inventoried
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->assertCount(1, $monitors);

        //also linked on last inventoried computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_2_id]);
        $this->assertCount(1, $monitors);

        //monitor is still dynamic
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $monitors);
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_2_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $monitors);
    }

    public function testInventoryGlobalManagement()
    {
        global $DB;

        $monitor = new \Monitor();
        $item_monitor = new \Computer_Item();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <MONITORS>
      <BASE64>AP///////wBNEEkUAAAAACAZAQSlHRF4Dt5Qo1RMmSYPUFQAAAABAQEBAQEBAQEBAQEBAQEBGjaAoHA4H0AwIDUAJqUQAAAYAAAAEAAAAAAAAAAAAAAAAAAAAAAA/gBESkNQNoBMUTEzM00xAAAAAAACQQMoABIAAAsBCiAgAGY=</BASE64>
      <CAPTION>DJCP6</CAPTION>
      <DESCRIPTION>32/2015</DESCRIPTION>
      <MANUFACTURER>Sharp Corporation</MANUFACTURER>
      <SERIAL>AFGHHDR0</SERIAL>
    </MONITORS>
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

        //change default configuration to global management
        $this->login();
        \Config::setConfigurationValues('core', ['monitors_management_restrict' => \Config::GLOBAL_MANAGEMENT]);
        $this->logout();

        //computer inventory with one monitor
        $inventory = $this->doInventory($xml_source, true);

        //restore default configuration
        $this->login();
        \Config::setConfigurationValues('core', ['monitors_management_restrict' => \Config::NO_MANAGEMENT]);
        $this->logOut();

        //check for expected logs
        $nblogsnow = countElementsInTable(\Log::getTable());
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'LIMIT' => $nblogsnow,
            'OFFSET' => $this->nblogs,
            'WHERE' => [
                'NOT' => ['itemtype' => \Config::class]
            ]
        ]);
        $this->assertCount(0, $logs);

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Sharp Corporation']])->current();
        $this->assertIsArray($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $computers_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $computers_id);

        //we have 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $monitors);
        $this->assertSame($manufacturers_id, current($monitors)['manufacturers_id']);

        //we have 1 monitor items linked to the computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->assertCount(1, $monitors);

        //monitor present in the inventory source is dynamic
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $monitors);

        //same monitor, but on another computer
        $xml_source_2 = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <MONITORS>
      <BASE64>AP///////wBNEEkUAAAAACAZAQSlHRF4Dt5Qo1RMmSYPUFQAAAABAQEBAQEBAQEBAQEBAQEBGjaAoHA4H0AwIDUAJqUQAAAYAAAAEAAAAAAAAAAAAAAAAAAAAAAA/gBESkNQNoBMUTEzM00xAAAAAAACQQMoABIAAAsBCiAgAGY=</BASE64>
      <CAPTION>DJCP6</CAPTION>
      <DESCRIPTION>32/2015</DESCRIPTION>
      <MANUFACTURER>Sharp Corporation</MANUFACTURER>
      <SERIAL>AFGHHDR0</SERIAL>
    </MONITORS>
    <HARDWARE>
      <NAME>pc003</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>ggheb7ne8</SSN>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Agent_v2.3.19</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>test-pc003</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

        //change default configuration to global management
        $this->login();
        \Config::setConfigurationValues('core', ['monitors_management_restrict' => \Config::GLOBAL_MANAGEMENT]);
        $this->logout();

        //computer inventory with one monitor
        $inventory = $this->doInventory($xml_source_2, true);

        //restore default configuration
        $this->login();
        \Config::setConfigurationValues('core', ['monitors_management_restrict' => \Config::NO_MANAGEMENT]);
        $this->logOut();

        //check for expected logs
        $nblogsnow = countElementsInTable(\Log::getTable());
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'LIMIT' => $nblogsnow,
            'OFFSET' => $this->nblogs,
            'WHERE' => [
                'NOT' => ['itemtype' => \Config::class]
            ]
        ]);
        $this->assertCount(0, $logs);

        $computers_2_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $computers_2_id);

        //we still have only 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $monitors);
        $this->assertSame($manufacturers_id, current($monitors)['manufacturers_id']);

        //still linked on first computer inventoried
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->assertCount(1, $monitors);

        //also linked on last inventoried computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_2_id]);
        $this->assertCount(1, $monitors);

        //monitor is still dynamic
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $monitors);
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_2_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $monitors);
    }

    public function testInventoryUnitManagement()
    {
        global $DB;

        $monitor = new \Monitor();
        $item_monitor = new \Computer_Item();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <MONITORS>
      <BASE64>AP///////wBNEEkUAAAAACAZAQSlHRF4Dt5Qo1RMmSYPUFQAAAABAQEBAQEBAQEBAQEBAQEBGjaAoHA4H0AwIDUAJqUQAAAYAAAAEAAAAAAAAAAAAAAAAAAAAAAA/gBESkNQNoBMUTEzM00xAAAAAAACQQMoABIAAAsBCiAgAGY=</BASE64>
      <CAPTION>DJCP6</CAPTION>
      <DESCRIPTION>32/2015</DESCRIPTION>
      <MANUFACTURER>Sharp Corporation</MANUFACTURER>
      <SERIAL>AFGHHDR0</SERIAL>
    </MONITORS>
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

        //change default configuration to unit management
        $this->login();
        \Config::setConfigurationValues('core', ['monitors_management_restrict' => \Config::UNIT_MANAGEMENT]);
        $this->logout();

        //computer inventory with one monitor
        $inventory = $this->doInventory($xml_source, true);

        //restore default configuration
        $this->login();
        \Config::setConfigurationValues('core', ['monitors_management_restrict' => \Config::NO_MANAGEMENT]);
        $this->logOut();

        //check for expected logs
        $nblogsnow = countElementsInTable(\Log::getTable());
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'LIMIT' => $nblogsnow,
            'OFFSET' => $this->nblogs,
            'WHERE' => [
                'NOT' => ['itemtype' => \Config::class]
            ]
        ]);
        $this->assertCount(0, $logs);

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Sharp Corporation']])->current();
        $this->assertIsArray($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $computers_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $computers_id);

        //we have 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $monitors);
        $this->assertSame($manufacturers_id, current($monitors)['manufacturers_id']);

        //we have 1 monitor items linked to the computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->assertCount(1, $monitors);

        //monitor present in the inventory source is dynamic
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $monitors);

        //same inventory again
        $inventory = $this->doInventory($xml_source, true);

        //check for expected logs
        $nblogsnow = countElementsInTable(\Log::getTable());
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'LIMIT' => $nblogsnow,
            'OFFSET' => $this->nblogs,
            'WHERE' => [
                'NOT' => ['itemtype' => \Config::class]
            ]
        ]);
        $this->assertCount(0, $logs);

        $computers_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $computers_id);

        //we still have only 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $monitors);
        $this->assertSame($manufacturers_id, current($monitors)['manufacturers_id']);

        //we still have only 1 monitor items linked to the computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->assertCount(1, $monitors);

        //set to global management
        $this->assertTrue($monitor->getFromDB(current($monitors)['items_id']));
        $this->assertTrue($monitor->update(['id' => $monitor->fields['id'], 'is_global' => \Config::GLOBAL_MANAGEMENT]));

        //same monitor, but on another computer
        $xml_source_2 = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <MONITORS>
      <BASE64>AP///////wBNEEkUAAAAACAZAQSlHRF4Dt5Qo1RMmSYPUFQAAAABAQEBAQEBAQEBAQEBAQEBGjaAoHA4H0AwIDUAJqUQAAAYAAAAEAAAAAAAAAAAAAAAAAAAAAAA/gBESkNQNoBMUTEzM00xAAAAAAACQQMoABIAAAsBCiAgAGY=</BASE64>
      <CAPTION>DJCP6</CAPTION>
      <DESCRIPTION>32/2015</DESCRIPTION>
      <MANUFACTURER>Sharp Corporation</MANUFACTURER>
      <SERIAL>AFGHHDR0</SERIAL>
    </MONITORS>
    <HARDWARE>
      <NAME>pc003</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>ggheb7ne8</SSN>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Agent_v2.3.19</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>test-pc003</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

        //change default configuration to unit management
        $this->login();
        \Config::setConfigurationValues('core', ['monitors_management_restrict' => \Config::UNIT_MANAGEMENT]);
        $this->logout();

        //computer inventory with one monitor
        $inventory = $this->doInventory($xml_source_2, true);

        //restore default configuration
        $this->login();
        \Config::setConfigurationValues('core', ['monitors_management_restrict' => \Config::NO_MANAGEMENT]);
        $this->logOut();

        //check for expected logs
        $nblogsnow = countElementsInTable(\Log::getTable());
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'LIMIT' => $nblogsnow,
            'OFFSET' => $this->nblogs,
            'WHERE' => [
                'NOT' => ['itemtype' => \Config::class]
            ]
        ]);
        $this->assertCount(0, $logs);

        $computers_2_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $computers_2_id);

        //we still have only 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $monitors);
        $this->assertSame($manufacturers_id, current($monitors)['manufacturers_id']);

        //no longer linked on first computer inventoried
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->assertCount(0, $monitors);

        //but now linked on last inventoried computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_2_id]);
        $this->assertCount(1, $monitors);

        //monitor is still dynamic
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_2_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $monitors);

        //change default configuration to unit management
        $this->login();
        \Config::setConfigurationValues('core', ['monitors_management_restrict' => \Config::UNIT_MANAGEMENT]);
        $this->logout();

        //replay first computer inventory, monitor is back \o/
        $this->doInventory($xml_source, true);

        //restore default configuration
        $this->login();
        \Config::setConfigurationValues('core', ['monitors_management_restrict' => \Config::NO_MANAGEMENT]);
        $this->logOut();

        //check for expected logs
        $nblogsnow = countElementsInTable(\Log::getTable());
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'LIMIT' => $nblogsnow,
            'OFFSET' => $this->nblogs,
            'WHERE' => [
                'NOT' => ['itemtype' => \Config::class]
            ]
        ]);
        $this->assertCount(0, $logs);

        //we still have only 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $monitors);
        $this->assertSame($manufacturers_id, current($monitors)['manufacturers_id']);

        //linked again on first computer inventoried
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->assertCount(1, $monitors);

        //no longer linked on last inventoried computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_2_id]);
        $this->assertCount(0, $monitors);

        //monitor is still dynamic
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $monitors);
    }

    public function testInventoryImportOrNot()
    {
        global $DB;

        $monitor = new \Monitor();
        $item_monitor = new \Computer_Item();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <MONITORS>
      <BASE64>AP///////wBNEEkUAAAAACAZAQSlHRF4Dt5Qo1RMmSYPUFQAAAABAQEBAQEBAQEBAQEBAQEBGjaAoHA4H0AwIDUAJqUQAAAYAAAAEAAAAAAAAAAAAAAAAAAAAAAA/gBESkNQNoBMUTEzM00xAAAAAAACQQMoABIAAAsBCiAgAGY=</BASE64>
      <CAPTION>DJCP6</CAPTION>
      <DESCRIPTION>32/2015</DESCRIPTION>
      <MANUFACTURER>Sharp Corporation</MANUFACTURER>
      <SERIAL>AFGHHDR0</SERIAL>
    </MONITORS>
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

        //per default, configuration allows monitor import. change that.
        $this->login();
        $conf = new \Glpi\Inventory\Conf();
        $this->assertTrue(
            $conf->saveConf([
                'import_monitor' => 0
            ])
        );
        $this->logout();

        //computer inventory with one printer
        $inventory = $this->doInventory($xml_source, true);

        //restore default configuration
        $this->login();
        $this->assertTrue(
            $conf->saveConf([
                'import_monitor' => 1
            ])
        );
        $this->logOut();

        //check for expected logs
        $nblogsnow = countElementsInTable(\Log::getTable());
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'LIMIT' => $nblogsnow,
            'OFFSET' => $this->nblogs,
            'WHERE' => [
                'NOT' => ['itemtype' => \Config::class]
            ]
        ]);
        $this->assertCount(0, $logs);

        $computers_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $computers_id);

        //no monitor linked to the computer
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(0, $monitors);

        //inventory again
        $this->doInventory($xml_source, true);

        //check for expected logs
        $nblogsnow = countElementsInTable(\Log::getTable());
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'LIMIT' => $nblogsnow,
            'OFFSET' => $this->nblogs,
            'WHERE' => [
                'NOT' => ['itemtype' => \Config::class]
            ]
        ]);
        $this->assertCount(0, $logs);

        //we now have 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $monitors);

        //we have 1 monitor items linked to the computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->assertCount(1, $monitors);

        //monitor present in the inventory source is dynamic
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $monitors);
    }

    public function testInventoryMonitorLog()
    {
        global $DB;

        $monitor = new \Monitor();
        $item_monitor = new \Computer_Item();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <MONITORS>
      <BASE64>AP///////wBNEEkUAAAAACAZAQSlHRF4Dt5Qo1RMmSYPUFQAAAABAQEBAQEBAQEBAQEBAQEBGjaAoHA4H0AwIDUAJqUQAAAYAAAAEAAAAAAAAAAAAAAAAAAAAAAA/gBESkNQNoBMUTEzM00xAAAAAAACQQMoABIAAAsBCiAgAGY=</BASE64>
      <CAPTION>DJCP6</CAPTION>
      <DESCRIPTION>32/2015</DESCRIPTION>
      <MANUFACTURER>Sharp Corporation</MANUFACTURER>
      <SERIAL>AFGHHDR0</SERIAL>
    </MONITORS>
    <MONITORS>
      <BASE64>AP///////wAwrlBAAAAAAAAQAQOAIRV46gMVl1dSjCchUFQAAAABAQEBAQEBAQEBAQEBAQEBxxsAoFAgFzAwIDYAS88QAAAZehcAsVAgGTAwIDYAS88QAAAZAAAADwCBCjKBCigUAQBMo1gzAAAA/gBMVE4xNTRYMy1MMDIKAMI=</BASE64>
      <DESCRIPTION>0/2006</DESCRIPTION>
      <MANUFACTURER>Lenovo Group Limited</MANUFACTURER>
      <NAME>Moniteur Plug-and-Play générique</NAME>
      <SERIAL>AZ789</SERIAL>
    </MONITORS>
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


        //computer inventory with two monitors
        $inventory = $this->doInventory($xml_source, true);

        //check computer
        $computers_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $computers_id);

        //no log from first import (Computer or Monitor)
        $nblogsnow = countElementsInTable(\Log::getTable());
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'LIMIT' => $nblogsnow,
            'OFFSET' => $this->nblogs,
            'WHERE' => [
                'OR' => [
                    'itemtype' => \Computer::class,
                    'itemtype' => \Monitor::class,
                ]
            ]
        ]);
        $this->assertCount(0, $logs);

        //we have 2 monitor
        $this->assertCount(2, $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]));
        //we have 2 monitor items linked to the computer
        $this->assertCount(2, $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]));
        //we have 2 dynamic link
        $this->assertCount(2, $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1]));


        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
          <CONTENT>
          <MONITORS>
            <BASE64>AP///////wAwrlBAAAAAAAAQAQOAIRV46gMVl1dSjCchUFQAAAABAQEBAQEBAQEBAQEBAQEBxxsAoFAgFzAwIDYAS88QAAAZehcAsVAgGTAwIDYAS88QAAAZAAAADwCBCjKBCigUAQBMo1gzAAAA/gBMVE4xNTRYMy1MMDIKAMI=</BASE64>
            <DESCRIPTION>0/2006</DESCRIPTION>
            <MANUFACTURER>Lenovo Group Limited</MANUFACTURER>
            <NAME>Moniteur Plug-and-Play générique</NAME>
            <SERIAL>AZ789</SERIAL>
          </MONITORS>
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

        //inventory again with only one monitor
        $this->doInventory($xml_source, true);
        //check for expected logs (one log for deleted relation)
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'WHERE' => [
                'itemtype' => \Monitor::class,
                'itemtype_link' => \Computer::class,
                'linked_action' => \Log::HISTORY_DEL_RELATION
            ]
        ]);
        $this->assertCount(1, $logs);

        //we have 2 monitor
        $this->assertCount(2, $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]));
        //we have 1 monitor items linked to the computer
        $this->assertCount(1, $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]));
        //we have 1 dynamic link
        $this->assertCount(1, $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1]));


        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
          <CONTENT>
            <MONITORS>
              <BASE64>AP///////wBNEEkUAAAAACAZAQSlHRF4Dt5Qo1RMmSYPUFQAAAABAQEBAQEBAQEBAQEBAQEBGjaAoHA4H0AwIDUAJqUQAAAYAAAAEAAAAAAAAAAAAAAAAAAAAAAA/gBESkNQNoBMUTEzM00xAAAAAAACQQMoABIAAAsBCiAgAGY=</BASE64>
              <CAPTION>DJCP6</CAPTION>
              <DESCRIPTION>32/2015</DESCRIPTION>
              <MANUFACTURER>Sharp Corporation</MANUFACTURER>
              <SERIAL>AFGHHDR0</SERIAL>
            </MONITORS>
            <MONITORS>
              <BASE64>AP///////wAwrlBAAAAAAAAQAQOAIRV46gMVl1dSjCchUFQAAAABAQEBAQEBAQEBAQEBAQEBxxsAoFAgFzAwIDYAS88QAAAZehcAsVAgGTAwIDYAS88QAAAZAAAADwCBCjKBCigUAQBMo1gzAAAA/gBMVE4xNTRYMy1MMDIKAMI=</BASE64>
              <DESCRIPTION>0/2006</DESCRIPTION>
              <MANUFACTURER>Lenovo Group Limited</MANUFACTURER>
              <NAME>Moniteur Plug-and-Play générique</NAME>
              <SERIAL>AZ789</SERIAL>
            </MONITORS>
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

        //computer inventory with one printer
        $inventory = $this->doInventory($xml_source, true);
        //check for expected logs (one log for deleted relation)
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'WHERE' => [
                'itemtype' => \Monitor::class,
                'itemtype_link' => \Computer::class,
                'linked_action' => \Log::HISTORY_ADD_RELATION
            ]
        ]);
        $this->assertCount(1, $logs);

        //we have 2 monitor
        $this->assertCount(2, $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]));
        //we have 2 monitor items linked to the computer
        $this->assertCount(2, $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]));
        //we have 2 dynamic link
        $this->assertCount(2, $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1]));
    }

    public function testInventoryRemoved()
    {
        global $DB;

        $nb_monitors = countElementsInTable(\Monitor::getTable());
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_3.json'));

        $inventory = $this->doInventory($json);

        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();
        $this->assertIsArray($agent);
        $this->assertSame('LF014-2017-02-20-12-19-56', $agent['deviceid']);
        $this->assertSame('LF014-2017-02-20-12-19-56', $agent['name']);
        $this->assertSame('2.3.19', $agent['version']);
        $this->assertSame('Computer', $agent['itemtype']);
        $this->assertSame('000005', $agent['tag']);
        $this->assertSame($agenttype['id'], $agent['agenttypes_id']);

        //check created computer
        $computers_id = $agent['items_id'];
        $this->assertGreaterThan(0, $computers_id);
        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($computers_id));

        //check created monitor
        ++$nb_monitors;
        $this->assertSame($nb_monitors, countElementsInTable(\Monitor::getTable()));
        $this->assertCount(1, $DB->request(['FROM' => \Computer_Item::getTable(), 'WHERE' => ['itemtype' => \Monitor::class, 'computers_id' => $computers_id]]));

        //remove monitor
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_3.json'));
        unset($json->content->monitors);
        $this->doInventory($json);

        //monitor is still present in database
        $this->assertSame($nb_monitors, countElementsInTable(\Monitor::getTable()));
        //link to monitor has been removed
        $this->assertCount(0, $DB->request(['FROM' => \Computer_Item::getTable(), 'WHERE' => ['itemtype' => \Monitor::class, 'computers_id' => $computers_id]]));
    }

    public function testPartialInventoryRemoved()
    {
        global $DB;

        $nb_monitors = countElementsInTable(\Monitor::getTable());
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_3.json'));

        $inventory = $this->doInventory($json);

        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();
        $this->assertIsArray($agent);
        $this->assertSame('LF014-2017-02-20-12-19-56', $agent['deviceid']);
        $this->assertSame('LF014-2017-02-20-12-19-56', $agent['name']);
        $this->assertSame('2.3.19', $agent['version']);
        $this->assertSame('Computer', $agent['itemtype']);
        $this->assertSame('000005', $agent['tag']);
        $this->assertSame($agenttype['id'], $agent['agenttypes_id']);

        //check created computer
        $computers_id = $agent['items_id'];
        $this->assertGreaterThan(0, $computers_id);
        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($computers_id));

        //check created monitor
        ++$nb_monitors;
        $this->assertSame($nb_monitors, countElementsInTable(\Monitor::getTable()));
        $this->assertCount(1, $DB->request(['FROM' => \Computer_Item::getTable(), 'WHERE' => ['itemtype' => \Monitor::class, 'computers_id' => $computers_id]]));

        //no monitor in inventory (missing node)
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_3.json'));
        unset($json->content->monitors);
        $json->partial = true;
        $this->doInventory($json);

        //monitor is still present in database
        $this->assertSame($nb_monitors, countElementsInTable(\Monitor::getTable()));
        //link to monitor is still present
        $this->assertCount(1, $DB->request(['FROM' => \Computer_Item::getTable(), 'WHERE' => ['itemtype' => \Monitor::class, 'computers_id' => $computers_id]]));

        //no monitor left in inventory (empty node)
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_3.json'));
        $json->content->monitors = [];
        $json->partial = true;
        $this->doInventory($json);

        //monitor is still present in database
        $this->assertSame($nb_monitors, countElementsInTable(\Monitor::getTable()));
        //link to monitor has been removed
        $this->assertCount(0, $DB->request(['FROM' => \Computer_Item::getTable(), 'WHERE' => ['itemtype' => \Monitor::class, 'computers_id' => $computers_id]]));
    }
}
