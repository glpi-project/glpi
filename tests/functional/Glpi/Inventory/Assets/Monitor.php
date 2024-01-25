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

class Monitor extends AbstractInventoryAsset
{
    protected function assetProvider(): array
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
        $this->object($result[0])->isEqualTo(json_decode($expected));
    }

    public function testHandle()
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');

       //first, check there are no monitor linked to this computer
        $ico = new \Computer_Item();
        $this->boolean($ico->getFromDbByCrit(['computers_id' => $computer->fields['id'], 'itemtype' => 'Monitor']))
           ->isFalse('A monitor is already linked to computer!');

       //convert data
        $expected = $this->assetProvider()[0];

        $converter = new \Glpi\Inventory\Converter();
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
        $this->integer(count($logs))->isIdenticalTo(0);

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Sharp Corporation']])->current();
        $this->array($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);

        //we have 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($monitors))->isIdenticalTo(1);
        $this->integer(current($monitors)['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        //we have 1 monitor items linked to the computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->integer(count($monitors))->isIdenticalTo(1);

        //monitor present in the inventory source is dynamic
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($monitors))->isIdenticalTo(1);

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
        $this->integer(count($logs))->isIdenticalTo(0);

        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);

        //we still have only 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($monitors))->isIdenticalTo(1);
        $this->integer(current($monitors)['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        //we still have only 1 monitor items linked to the computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->integer(count($monitors))->isIdenticalTo(1);

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
        $this->integer(count($logs))->isIdenticalTo(0);

        $computers_2_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_2_id)->isGreaterThan(0);

        //we still have only 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($monitors))->isIdenticalTo(1);
        $this->integer(current($monitors)['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        //no longer linked on first computer inventoried
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->integer(count($monitors))->isIdenticalTo(0);

        //but now linked on last inventoried computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_2_id]);
        $this->integer(count($monitors))->isIdenticalTo(1);

        //monitor is still dynamic
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_2_id, 'is_dynamic' => 1]);
        $this->integer(count($monitors))->isIdenticalTo(1);

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
        $this->integer(count($logs))->isIdenticalTo(0);

        //we still have only 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($monitors))->isIdenticalTo(1);
        $this->integer(current($monitors)['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        //linked again on first computer inventoried
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->integer(count($monitors))->isIdenticalTo(1);

        //no longer linked on last inventoried computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_2_id]);
        $this->integer(count($monitors))->isIdenticalTo(0);

        //monitor is still dynamic
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($monitors))->isIdenticalTo(1);
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
        $this->integer(count($logs))->isIdenticalTo(0);

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Sharp Corporation']])->current();
        $this->array($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);

        //we have 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($monitors))->isIdenticalTo(1);
        $this->integer(current($monitors)['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        //we have 1 monitor items linked to the computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->integer(count($monitors))->isIdenticalTo(1);

        //monitor present in the inventory source is dynamic
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($monitors))->isIdenticalTo(1);

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
        $this->integer(count($logs))->isIdenticalTo(0);

        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);

        //we still have only 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($monitors))->isIdenticalTo(1);
        $this->integer(current($monitors)['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        //we still have only 1 monitor items linked to the computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->integer(count($monitors))->isIdenticalTo(1);

        //set to global management
        $this->boolean($monitor->getFromDB(current($monitors)['items_id']));
        $this->boolean($monitor->update(['id' => $monitor->fields['id'], 'is_global' => \Config::GLOBAL_MANAGEMENT]));

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
        $this->integer(count($logs))->isIdenticalTo(0);

        $computers_2_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_2_id)->isGreaterThan(0);

        //we still have only 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($monitors))->isIdenticalTo(1);
        $this->integer(current($monitors)['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        //still linked on first computer inventoried
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->integer(count($monitors))->isIdenticalTo(1);

        //also linked on last inventoried computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_2_id]);
        $this->integer(count($monitors))->isIdenticalTo(1);

        //monitor is still dynamic
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($monitors))->isIdenticalTo(1);
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_2_id, 'is_dynamic' => 1]);
        $this->integer(count($monitors))->isIdenticalTo(1);
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
        $this->integer(count($logs))->isIdenticalTo(0);

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Sharp Corporation']])->current();
        $this->array($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);

        //we have 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($monitors))->isIdenticalTo(1);
        $this->integer(current($monitors)['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        //we have 1 monitor items linked to the computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->integer(count($monitors))->isIdenticalTo(1);

        //monitor present in the inventory source is dynamic
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($monitors))->isIdenticalTo(1);

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
        $this->integer(count($logs))->isIdenticalTo(0);

        $computers_2_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_2_id)->isGreaterThan(0);

        //we still have only 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($monitors))->isIdenticalTo(1);
        $this->integer(current($monitors)['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        //still linked on first computer inventoried
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->integer(count($monitors))->isIdenticalTo(1);

        //also linked on last inventoried computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_2_id]);
        $this->integer(count($monitors))->isIdenticalTo(1);

        //monitor is still dynamic
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($monitors))->isIdenticalTo(1);
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_2_id, 'is_dynamic' => 1]);
        $this->integer(count($monitors))->isIdenticalTo(1);
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
        $this->integer(count($logs))->isIdenticalTo(0);

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Sharp Corporation']])->current();
        $this->array($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);

        //we have 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($monitors))->isIdenticalTo(1);
        $this->integer(current($monitors)['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        //we have 1 monitor items linked to the computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->integer(count($monitors))->isIdenticalTo(1);

        //monitor present in the inventory source is dynamic
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($monitors))->isIdenticalTo(1);

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
        $this->integer(count($logs))->isIdenticalTo(0);

        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);

        //we still have only 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($monitors))->isIdenticalTo(1);
        $this->integer(current($monitors)['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        //we still have only 1 monitor items linked to the computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->integer(count($monitors))->isIdenticalTo(1);

        //set to global management
        $this->boolean($monitor->getFromDB(current($monitors)['items_id']));
        $this->boolean($monitor->update(['id' => $monitor->fields['id'], 'is_global' => \Config::GLOBAL_MANAGEMENT]));

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
        $this->integer(count($logs))->isIdenticalTo(0);

        $computers_2_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_2_id)->isGreaterThan(0);

        //we still have only 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($monitors))->isIdenticalTo(1);
        $this->integer(current($monitors)['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        //no longer linked on first computer inventoried
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->integer(count($monitors))->isIdenticalTo(0);

        //but now linked on last inventoried computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_2_id]);
        $this->integer(count($monitors))->isIdenticalTo(1);

        //monitor is still dynamic
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_2_id, 'is_dynamic' => 1]);
        $this->integer(count($monitors))->isIdenticalTo(1);

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
        $this->integer(count($logs))->isIdenticalTo(0);

        //we still have only 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($monitors))->isIdenticalTo(1);
        $this->integer(current($monitors)['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        //linked again on first computer inventoried
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->integer(count($monitors))->isIdenticalTo(1);

        //no longer linked on last inventoried computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_2_id]);
        $this->integer(count($monitors))->isIdenticalTo(0);

        //monitor is still dynamic
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($monitors))->isIdenticalTo(1);
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
        $this->boolean(
            $conf->saveConf([
                'import_monitor' => 0
            ])
        )->isTrue();
        $this->logout();

        //computer inventory with one printer
        $inventory = $this->doInventory($xml_source, true);

        //restore default configuration
        $this->login();
        $this->boolean(
            $conf->saveConf([
                'import_monitor' => 1
            ])
        )->isTrue();
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
        $this->integer(count($logs))->isIdenticalTo(0);

        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);

        //no monitor linked to the computer
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($monitors))->isIdenticalTo(0);

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
        $this->integer(count($logs))->isIdenticalTo(0);

        //we now have 1 monitor
        $monitors = $monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($monitors))->isIdenticalTo(1);

        //we have 1 monitor items linked to the computer
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id]);
        $this->integer(count($monitors))->isIdenticalTo(1);

        //monitor present in the inventory source is dynamic
        $monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($monitors))->isIdenticalTo(1);
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
        $this->integer($computers_id)->isGreaterThan(0);

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
        $this->integer(count($logs))->isIdenticalTo(0);

        //we have 2 monitor
        $this->integer(count($monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]])))->isIdenticalTo(2);
        //we have 2 monitor items linked to the computer
        $this->integer(count($item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id])))->isIdenticalTo(2);
        //we have 2 dynamic link
        $this->integer(count($item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1])))->isIdenticalTo(2);


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
        $this->integer(count($logs))->isIdenticalTo(1);

        //we have 2 monitor
        $this->integer(count($monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]])))->isIdenticalTo(2);
        //we have 1 monitor items linked to the computer
        $this->integer(count($item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id])))->isIdenticalTo(1);
        //we have 1 dynamic link
        $this->integer(count($item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1])))->isIdenticalTo(1);


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
        $this->integer(count($logs))->isIdenticalTo(1);

        //we have 2 monitor
        $this->integer(count($monitor->find(['NOT' => ['name' => ['LIKE', '_test_%']]])))->isIdenticalTo(2);
        //we have 2 monitor items linked to the computer
        $this->integer(count($item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id])))->isIdenticalTo(2);
        //we have 2 dynamic link
        $this->integer(count($item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1])))->isIdenticalTo(2);
    }
}
