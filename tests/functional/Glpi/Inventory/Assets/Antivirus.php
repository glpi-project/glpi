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

/* Test for inc/inventory/asset/antivirus.class.php */

class Antivirus extends AbstractInventoryAsset
{
    protected function assetProvider(): array
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
                'expected'  => '{"company": "Microsoft Corporation", "enabled": true, "guid": "{641105E6-77ED-3F35-A304-765193BCB75F}", "name": "Microsoft Security Essentials", "uptodate": true, "version": "4.3.216.0", "manufacturers_id": "Microsoft Corporation", "antivirus_version": "4.3.216.0", "is_active": true, "is_uptodate": true, "is_dynamic": 1}'
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
                'expected'  => '{"company": "Microsoft Corporation", "enabled": true, "guid": "{641105E6-77ED-3F35-A304-765193BCB75F}", "name": "Microsoft Security Essentials", "uptodate": true, "manufacturers_id": "Microsoft Corporation", "antivirus_version": "", "is_active": true, "is_uptodate": true, "is_dynamic": 1}'
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
                'expected'  => '{"company": "Microsoft Corporation", "enabled": true, "guid": "{641105E6-77ED-3F35-A304-765193BCB75F}", "name": "Microsoft Security Essentials", "uptodate": true, "version": "4.3.216.0", "manufacturers_id": "Microsoft Corporation", "antivirus_version": "4.3.216.0", "is_active": true, "is_uptodate": true, "expiration": "2019-04-01", "date_expiration": "2019-04-01", "is_dynamic": 1}'
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
        $asset = new \Glpi\Inventory\Asset\Antivirus($computer, $json->content->antivirus);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->object($result[0])->isEqualTo(json_decode($expected));
    }

    public function testWrongMainItem()
    {
        $mainitem = getItemByTypeName('Printer', '_test_printer_all');
        $asset = new \Glpi\Inventory\Asset\Antivirus($mainitem);
        $this->exception(
            function () use ($asset) {
                $asset->prepare();
            }
        )->message->contains('Antivirus are handled for computers only.');
    }

    public function testHandle()
    {
        global $DB;
        $computer = getItemByTypeName('Computer', '_test_pc01');

       //first, check there are no AV linked to this computer
        $avc = new \ComputerAntivirus();
        $this->boolean($avc->getFromDbByCrit(['computers_id' => $computer->fields['id']]))
           ->isFalse('An antivirus is already linked to computer!');

       //convert data
        $expected = $this->assetProvider()[0];

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\Antivirus($computer, $json->content->antivirus);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->object($result[0])->isEqualTo(json_decode($expected['expected']));

       //handle
        $asset->handleLinks();

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Microsoft Corporation']])->current();
        $this->array($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $asset->handle();
        $this->boolean($avc->getFromDbByCrit(['computers_id' => $computer->fields['id']]))
           ->isTrue('Antivirus has not been linked to computer :(');

        $this->integer($avc->fields['manufacturers_id'])->isIdenticalTo($manufacturers_id);
    }

    public function testUpdate()
    {
        $this->testHandle();

        $computer = getItemByTypeName('Computer', '_test_pc01');

       //first, check there are no AV linked to this computer
        $avc = new \ComputerAntivirus();
        $this->boolean($avc->getFromDbByCrit(['computers_id' => $computer->fields['id']]))
           ->isTrue('No antivirus linked to computer!');

        $expected = $this->assetProvider()[0];
        $json_expected = json_decode($expected['expected']);
        $xml = $expected['xml'];
       //change version
        $xml = str_replace('<VERSION>4.3.216.0</VERSION>', '<VERSION>4.5.12.0</VERSION>', $xml);
        $json_expected->version = '4.5.12.0';
        $json_expected->antivirus_version = '4.5.12.0';

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $asset = new \Glpi\Inventory\Asset\Antivirus($computer, $json->content->antivirus);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->object($result[0])->isEqualTo($json_expected);

        $asset->handleLinks();
        $asset->handle();
        $this->boolean($avc->getFromDbByCrit(['computers_id' => $computer->fields['id']]))->isTrue();

        $this->string($avc->fields['antivirus_version'])->isIdenticalTo('4.5.12.0');
    }

    public function testInventoryUpdate()
    {
        $computer = new \Computer();
        $antivirus = new \ComputerAntivirus();

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
            'entities_id' => 0
        ]);
        $this->integer($computers_id)->isGreaterThan(0);

        $antivirus_1_id = $antivirus->add([
            'computers_id' => $computers_id,
            'name' => 'Kaspersky Endpoint Security 10 for Windows',
            'antivirus_version' => '2021 21.3.10.391',
            'is_active' => 1
        ]);
        $this->integer($antivirus_1_id)->isGreaterThan(0);

        $antivirus_2_id = $antivirus->add([
            'computers_id' => $computers_id,
            'name' => 'Microsoft Security Essentials',
            'antivirus_version' => '4.3.216.0',
            'is_active' => 1
        ]);
        $this->integer($antivirus_2_id)->isGreaterThan(0);

        $antivirus_3_id = $antivirus->add([
            'computers_id' => $computers_id,
            'name' => 'Avast Antivirus',
            'antivirus_version' => '19',
            'is_active' => 1
        ]);
        $this->integer($antivirus_3_id)->isGreaterThan(0);

        $results = $antivirus->find(['computers_id' => $computers_id]);
        $this->integer(count($results))->isIdenticalTo(3);
        foreach ($results as $result) {
            $this->variable($result['is_dynamic'])->isEqualTo(0);
        }

       //computer inventory knows only 2 antivirus: Microsoft and Kaspersky
        $this->doInventory($xml_source, true);

       //we still have 3 antivirus linked to the computer
        $results = $antivirus->find(['computers_id' => $computers_id]);
        $this->integer(count($results))->isIdenticalTo(3);

       //antivirus present in the inventory source are now dynamic
        $results = $antivirus->find(['computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($results))->isIdenticalTo(2);

        $this->boolean($antivirus->getFromDB($antivirus_1_id))->isTrue();
        $this->integer($antivirus->fields['is_dynamic'])->isIdenticalTo(1);

        $this->boolean($antivirus->getFromDB($antivirus_2_id))->isTrue();
        $this->integer($antivirus->fields['is_dynamic'])->isIdenticalTo(1);

       //antivirus not present in the inventory is still not dynamic
        $results = $antivirus->find(['computers_id' => $computers_id, 'is_dynamic' => 0]);
        $this->integer(count($results))->isIdenticalTo(1);

        $this->boolean($antivirus->getFromDB($antivirus_3_id))->isTrue();
        $this->integer($antivirus->fields['is_dynamic'])->isIdenticalTo(0);

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
        $results = $antivirus->find(['computers_id' => $computers_id]);
        $this->integer(count($results))->isIdenticalTo(2);

       //antivirus present in the inventory source are still dynamic
        $results = $antivirus->find(['computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($results))->isIdenticalTo(1);

        $this->boolean($antivirus->getFromDB($antivirus_1_id))->isTrue();
        $this->integer($antivirus->fields['is_dynamic'])->isIdenticalTo(1);

       //microsoft has been removed
        $this->boolean($antivirus->getFromDB($antivirus_2_id))->isFalse();

       //antivirus not present in the inventory is still not dynamic
        $results = $antivirus->find(['computers_id' => $computers_id, 'is_dynamic' => 0]);
        $this->integer(count($results))->isIdenticalTo(1);

        $this->boolean($antivirus->getFromDB($antivirus_3_id))->isTrue();
        $this->integer($antivirus->fields['is_dynamic'])->isIdenticalTo(0);
    }
}
