<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Inventory\Asset\Antivirus;
use Glpi\Inventory\Converter;
use PHPUnit\Framework\Attributes\DataProvider;

include_once __DIR__ . '/../../../../abstracts/AbstractInventoryAsset.php';

/* Test for inc/inventory/asset/antivirus.class.php */

class AntivirusTest extends AbstractInventoryAsset
{
    public static function assetProvider(): array
    {
        return [
            [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <ANTIVIRUS>
      <COMPANY>Microsoft Corporation</COMPANY>
      <ENABLED>1</ENABLED>
      <GUID>{641105E6-77ED-3F35-A304-765193BCB75F}</GUID>
      <NAME>Microsoft Security Essentials</NAME>
      <UPTODATE>1</UPTODATE>
      <VERSION>4.3.216.0</VERSION>
    </ANTIVIRUS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"company": "Microsoft Corporation", "enabled": true, "guid": "{641105E6-77ED-3F35-A304-765193BCB75F}", "name": "Microsoft Security Essentials", "uptodate": true, "version": "4.3.216.0", "manufacturers_id": "Microsoft Corporation", "antivirus_version": "4.3.216.0", "is_active": true, "is_uptodate": true, "is_dynamic": 1}',
            ], [ //no version
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <ANTIVIRUS>
      <COMPANY>Microsoft Corporation</COMPANY>
      <ENABLED>1</ENABLED>
      <GUID>{641105E6-77ED-3F35-A304-765193BCB75F}</GUID>
      <NAME>Microsoft Security Essentials</NAME>
      <UPTODATE>1</UPTODATE>
    </ANTIVIRUS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"company": "Microsoft Corporation", "enabled": true, "guid": "{641105E6-77ED-3F35-A304-765193BCB75F}", "name": "Microsoft Security Essentials", "uptodate": true, "manufacturers_id": "Microsoft Corporation", "antivirus_version": "", "is_active": true, "is_uptodate": true, "is_dynamic": 1}',
            ], [ //w expiration date
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <ANTIVIRUS>
      <COMPANY>Microsoft Corporation</COMPANY>
      <ENABLED>1</ENABLED>
      <GUID>{641105E6-77ED-3F35-A304-765193BCB75F}</GUID>
      <NAME>Microsoft Security Essentials</NAME>
      <UPTODATE>1</UPTODATE>
      <VERSION>4.3.216.0</VERSION>
      <EXPIRATION>01/04/2019</EXPIRATION>
    </ANTIVIRUS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"company": "Microsoft Corporation", "enabled": true, "guid": "{641105E6-77ED-3F35-A304-765193BCB75F}", "name": "Microsoft Security Essentials", "uptodate": true, "version": "4.3.216.0", "manufacturers_id": "Microsoft Corporation", "antivirus_version": "4.3.216.0", "is_active": true, "is_uptodate": true, "expiration": "2019-04-01", "date_expiration": "2019-04-01", "is_dynamic": 1}',
            ],
        ];
    }

    #[DataProvider('assetProvider')]
    public function testPrepare($xml, $expected)
    {
        $converter = new Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new Antivirus($computer, $json->content->antivirus);
        $asset->setExtraData((array) $json->content);
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected), $result[0]);
    }

    public function testWrongMainItem()
    {
        $mainitem = getItemByTypeName('Printer', '_test_printer_all');
        $asset = new Antivirus($mainitem);
        $this->expectExceptionMessage('Antivirus are not handled for Printer');
        $asset->prepare();
    }

    public function testHandle()
    {
        global $DB;
        $computer = getItemByTypeName('Computer', '_test_pc01');

        //first, check there are no AV linked to this computer
        $avc = new \ItemAntivirus();
        $this->assertFalse(
            $avc->getFromDbByCrit(['itemtype' => 'Computer', 'items_id' => $computer->fields['id']]),
            'An antivirus is already linked to computer!'
        );

        //convert data
        $expected = $this::assetProvider()[0];

        $converter = new Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new Antivirus($computer, $json->content->antivirus);
        $asset->setExtraData((array) $json->content);
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected['expected']), $result[0]);

        //handle
        $asset->handleLinks();

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Microsoft Corporation']])->current();
        $this->assertIsArray($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $asset->handle();
        $this->assertTrue(
            $avc->getFromDbByCrit(['itemtype' => 'Computer', 'items_id' => $computer->fields['id']]),
            'Antivirus has not been linked to computer :('
        );

        $this->assertSame($manufacturers_id, $avc->fields['manufacturers_id']);
    }

    public function testUpdate()
    {
        $this->testHandle();

        $computer = getItemByTypeName('Computer', '_test_pc01');

        //first, check there are no AV linked to this computer
        $avc = new \ItemAntivirus();
        $this->assertTrue(
            $avc->getFromDbByCrit(['itemtype' => 'Computer', 'items_id' => $computer->fields['id']]),
            'No antivirus linked to computer!'
        );

        $expected = $this::assetProvider()[0];
        $json_expected = json_decode($expected['expected']);
        $xml = $expected['xml'];
        //change version
        $xml = str_replace('<VERSION>4.3.216.0</VERSION>', '<VERSION>4.5.12.0</VERSION>', $xml);
        $json_expected->version = '4.5.12.0';
        $json_expected->antivirus_version = '4.5.12.0';

        $converter = new Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $asset = new Antivirus($computer, $json->content->antivirus);
        $asset->setExtraData((array) $json->content);
        $result = $asset->prepare();
        $this->assertEquals($json_expected, $result[0]);

        $asset->handleLinks();
        $asset->handle();
        $this->assertTrue($avc->getFromDbByCrit(['itemtype' => 'Computer', 'items_id' => $computer->fields['id']]));

        $this->assertSame('4.5.12.0', $avc->fields['antivirus_version']);
    }

    public function testInventoryUpdate()
    {
        $computer = new \Computer();
        $antivirus = new \ItemAntivirus();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <HARDWARE>
      <NAME>pc002</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>ggheb7ne7</SSN>
    </BIOS>
    <ANTIVIRUS>
      <BASE_VERSION>20200310.007</BASE_VERSION>
      <COMPANY>Kaspersky</COMPANY>
      <ENABLED>1</ENABLED>
      <GUID>{B41C7598-35F6-4D89-7D0E-7ADE69B4047B}</GUID>
      <NAME>Kaspersky Endpoint Security 10 for Windows</NAME>
      <UPTODATE>1</UPTODATE>
      <VERSION>2021 21.3.10.391</VERSION>
    </ANTIVIRUS>
    <ANTIVIRUS>
      <COMPANY>Microsoft Corporation</COMPANY>
      <ENABLED>1</ENABLED>
      <GUID>{641105E6-77ED-3F35-A304-765193BCB75F}</GUID>
      <NAME>Microsoft Security Essentials</NAME>
      <UPTODATE>1</UPTODATE>
      <VERSION>4.3.216.0</VERSION>
    </ANTIVIRUS>
    <VERSIONCLIENT>FusionInventory-Agent_v2.3.19</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>test-pc002</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

        //create manually a computer, with 3 antivirus
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $antivirus_1_id = $antivirus->add([
            'itemtype' => 'Computer',
            'items_id' => $computers_id,
            'name' => 'Kaspersky Endpoint Security 10 for Windows',
            'antivirus_version' => '2021 21.3.10.391',
            'is_active' => 1,
        ]);
        $this->assertGreaterThan(0, $antivirus_1_id);

        $antivirus_2_id = $antivirus->add([
            'itemtype' => 'Computer',
            'items_id' => $computers_id,
            'name' => 'Microsoft Security Essentials',
            'antivirus_version' => '4.3.216.0',
            'is_active' => 1,
        ]);
        $this->assertGreaterThan(0, $antivirus_2_id);

        $antivirus_3_id = $antivirus->add([
            'itemtype' => 'Computer',
            'items_id' => $computers_id,
            'name' => 'Avast Antivirus',
            'antivirus_version' => '19',
            'is_active' => 1,
        ]);
        $this->assertGreaterThan(0, $antivirus_3_id);

        $results = $antivirus->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(3, $results);
        foreach ($results as $result) {
            $this->assertEquals(0, $result['is_dynamic']);
        }

        //computer inventory knows only 2 antivirus: Microsoft and Kaspersky
        $this->doInventory($xml_source, true);

        //we still have 3 antivirus linked to the computer
        $results = $antivirus->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(3, $results);

        //antivirus present in the inventory source are now dynamic
        $results = $antivirus->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(2, $results);

        $this->assertTrue($antivirus->getFromDB($antivirus_1_id));
        $this->assertSame(1, $antivirus->fields['is_dynamic']);

        $this->assertTrue($antivirus->getFromDB($antivirus_2_id));
        $this->assertSame(1, $antivirus->fields['is_dynamic']);

        //antivirus not present in the inventory is still not dynamic
        $results = $antivirus->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->assertCount(1, $results);

        $this->assertTrue($antivirus->getFromDB($antivirus_3_id));
        $this->assertSame(0, $antivirus->fields['is_dynamic']);

        //Redo inventory, but with removed microsoft antivirus
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <ANTIVIRUS>
      <BASE_VERSION>20200310.007</BASE_VERSION>
      <COMPANY>Kaspersky</COMPANY>
      <ENABLED>1</ENABLED>
      <GUID>{B41C7598-35F6-4D89-7D0E-7ADE69B4047B}</GUID>
      <NAME>Kaspersky Endpoint Security 10 for Windows</NAME>
      <UPTODATE>1</UPTODATE>
      <VERSION>2021 21.3.10.391</VERSION>
    </ANTIVIRUS>
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

        //we now have 2 antivirus only
        $results = $antivirus->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(2, $results);

        //antivirus present in the inventory source are still dynamic
        $results = $antivirus->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $results);

        $this->assertTrue($antivirus->getFromDB($antivirus_1_id));
        $this->assertSame(1, $antivirus->fields['is_dynamic']);

        //microsoft has been removed
        $this->assertFalse($antivirus->getFromDB($antivirus_2_id));

        //antivirus not present in the inventory is still not dynamic
        $results = $antivirus->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->assertCount(1, $results);

        $this->assertTrue($antivirus->getFromDB($antivirus_3_id));
        $this->assertSame(0, $antivirus->fields['is_dynamic']);

        //remove all antiviruses from inventory
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
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

        //we now have 1 antivirus only
        $results = $antivirus->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(1, $results);
    }

    public function testPartialUpdate()
    {
        $computer = new \Computer();
        $antivirus = new \ItemAntivirus();

        //create manually a computer, with 3 antivirus
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $antivirus_1_id = $antivirus->add([
            'itemtype' => 'Computer',
            'items_id' => $computers_id,
            'name' => 'Kaspersky Endpoint Security 10 for Windows',
            'antivirus_version' => '2021 21.3.10.391',
            'is_active' => 1,
        ]);
        $this->assertGreaterThan(0, $antivirus_1_id);

        $antivirus_2_id = $antivirus->add([
            'itemtype' => 'Computer',
            'items_id' => $computers_id,
            'name' => 'Microsoft Security Essentials',
            'antivirus_version' => '4.3.216.0',
            'is_active' => 1,
        ]);
        $this->assertGreaterThan(0, $antivirus_2_id);

        $antivirus_3_id = $antivirus->add([
            'itemtype' => 'Computer',
            'items_id' => $computers_id,
            'name' => 'Avast Antivirus',
            'antivirus_version' => '19',
            'is_active' => 1,
        ]);
        $this->assertGreaterThan(0, $antivirus_3_id);

        $results = $antivirus->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(3, $results);
        foreach ($results as $result) {
            $this->assertEquals(0, $result['is_dynamic']);
        }

        $source = '{
            "action": "inventory",
            "content": {
                "hardware": {
                    "name": "pc002"
                },
                "antivirus": [
                    {
                         "base_version": "20200310.007",
                         "company": "Kaspersky",
                         "enabled": true,
                         "guid": "{B41C7598-35F6-4D89-7D0E-7ADE69B4047B}",
                         "name": "Kaspersky Endpoint Security 10 for Windows",
                         "uptodate": true,
                         "version": "2021 21.3.10.391"
                    },
                    {
                         "company": "Microsoft Corporation",
                         "enabled": true,
                         "guid": "{641105E6-77ED-3F35-A304-765193BCB75F}",
                         "name": "Microsoft Security Essentials",
                         "uptodate": true,
                         "version": "4.3.216.0"
                    }
                ],
                "versionclient": "GLPI-Agent_v1.4"
            },
            "deviceid": "pc.site.ru-2023-01-20-11-41-00",
            "itemtype": "Computer"
        }';

        //computer inventory knows only 2 antivirus: Microsoft and Kaspersky
        $this->doInventory(json_decode($source));

        //we still have 3 antivirus linked to the computer
        $results = $antivirus->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(3, $results);

        //antivirus present in the inventory source are now dynamic
        $results = $antivirus->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(2, $results);

        $this->assertTrue($antivirus->getFromDB($antivirus_1_id));
        $this->assertSame(1, $antivirus->fields['is_dynamic']);

        $this->assertTrue($antivirus->getFromDB($antivirus_2_id));
        $this->assertSame(1, $antivirus->fields['is_dynamic']);

        //antivirus not present in the inventory is still not dynamic
        $results = $antivirus->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->assertCount(1, $results);

        $this->assertTrue($antivirus->getFromDB($antivirus_3_id));
        $this->assertSame(0, $antivirus->fields['is_dynamic']);

        //Redo a partial inventory, with removed microsoft antivirus
        $source = '{
            "action": "inventory",
            "content": {
                "hardware": {
                    "name": "pc002"
                },
                "antivirus": [
                    {
                         "base_version": "20200310.007",
                         "company": "Kaspersky",
                         "enabled": true,
                         "guid": "{B41C7598-35F6-4D89-7D0E-7ADE69B4047B}",
                         "name": "Kaspersky Endpoint Security 10 for Windows",
                         "uptodate": true,
                         "version": "2021 21.3.10.391"
                    }
                ],
                "versionclient": "GLPI-Agent_v1.4"
            },
            "deviceid": "pc.site.ru-2023-01-20-11-41-00",
            "itemtype": "Computer",
            "partial": true
        }';

        $this->doInventory(json_decode($source));

        //we now have 2 antivirus only
        $results = $antivirus->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(2, $results);

        //antivirus present in the inventory source are still dynamic
        $results = $antivirus->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $results);

        $this->assertTrue($antivirus->getFromDB($antivirus_1_id));
        $this->assertSame(1, $antivirus->fields['is_dynamic']);

        //microsoft has been removed
        $this->assertFalse($antivirus->getFromDB($antivirus_2_id));

        //antivirus not present in the inventory is still not dynamic
        $results = $antivirus->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->assertCount(1, $results);

        $this->assertTrue($antivirus->getFromDB($antivirus_3_id));
        $this->assertSame(0, $antivirus->fields['is_dynamic']);
    }
}
