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

use Glpi\Asset\Capacity\IsInventoriableCapacity;
use Glpi\Inventory\Conf;
use Glpi\Inventory\Converter;
use PHPUnit\Framework\Attributes\DataProvider;

include_once __DIR__ . '/../../../../abstracts/AbstractInventoryAsset.php';

/* Test for inc/inventory/asset/process.class.php */

class Process extends AbstractInventoryAsset
{
    public static function assetProvider(): array
    {
        return [
            [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <HARDWARE>
      <NAME>pc002</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>ggheb7ne7</SSN>
    </BIOS>
    <PROCESSES>
        <CMD>php-fpm: pool www</CMD>
        <CPUUSAGE>0.0</CPUUSAGE>
        <MEM>0.0</MEM>
        <PID>3002</PID>
        <STARTED>2022-03-18 14:54</STARTED>
        <TTY>?</TTY>
        <USER>alexand+</USER>
        <VIRTUALMEMORY>231176</VIRTUALMEMORY>
    </PROCESSES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"cmd": "php-fpm: pool www", "cpuusage": "0.0", "mem": "0.0", "pid": 3002, "started": "2022-03-18 14:54:00", "tty": "?", "user": "alexand+", "virtualmemory": 231176, "memusage": "0.0", "is_dynamic": 1}',
            ],
            [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <PROCESSES>
        <CMD>/usr/sbin/mysqld</CMD>
        <CPUUSAGE>4.5</CPUUSAGE>
        <MEM>4.1</MEM>
        <PID>3012</PID>
        <STARTED>2022-03-18 14:54</STARTED>
        <TTY>?</TTY>
        <USER>mysql</USER>
        <VIRTUALMEMORY>2935028</VIRTUALMEMORY>
    </PROCESSES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"cmd": "/usr/sbin/mysqld", "cpuusage": "4.5", "mem": "4.1", "pid": 3012, "started": "2022-03-18 14:54:00", "tty": "?", "user": "mysql", "virtualmemory": 2935028, "memusage": "4.1", "is_dynamic": 1}',
            ],
        ];
    }

    #[DataProvider('assetProvider')]
    public function testPrepare($xml, $expected)
    {
        $this->login();
        $conf = new Conf();
        $this->assertTrue($conf->saveConf([
            'import_process' => 1,
        ]));
        $this->logout();

        $converter = new Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\Process($computer, $json->content->processes);
        $asset->setExtraData((array) $json->content);

        $this->assertTrue($asset->checkConf($conf));

        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected), $result[0]);
    }

    public function testHandle()
    {
        $this->login();
        $conf = new Conf();
        $this->assertTrue($conf->saveConf([
            'import_process' => 1,
        ]));
        $this->logout();

        $computer = getItemByTypeName('Computer', '_test_pc01');

        //first, check there are no process linked to this computer
        $ipr = new \Item_Process();
        $this->assertFalse(
            $ipr->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']),
            'A process is already linked to computer!'
        );

        //convert data
        $expected = $this->assetProvider()[0];

        $converter = new Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\Process($computer, $json->content->processes);
        $asset->setExtraData((array) $json->content);

        $this->assertTrue($asset->checkConf($conf));

        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected['expected']), $result[0]);

        //handle
        $asset->handleLinks();
        $asset->handle();
        $this->assertTrue(
            $ipr->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']),
            'Process has not been linked to computer :('
        );
    }

    public function testGenericAssetProcess(): void
    {
        global $DB;

        //create generic asset
        $definition = $this->initAssetDefinition(
            system_name: 'MyAsset' . $this->getUniqueString(),
            capacities: array_merge(
                [
                    IsInventoriableCapacity::class,
                ]
            )
        );
        $classname  = $definition->getAssetClassName();

        $this->login();
        $conf = new Conf();
        $this->assertTrue($conf->saveConf([
            'import_process' => 1,
        ]));
        $this->logout();

        $converter = new Converter();
        $data = $converter->convert(self::assetProvider()[0]['xml']);
        $json = json_decode($data);
        //we change itemtype to our asset
        $json->itemtype = $classname;
        $inventory = $this->doInventory($json);

        //check created asset
        $assets_id = $inventory->getAgent()->fields['items_id'];
        $this->assertGreaterThan(0, $assets_id);
        $asset = new $classname();
        $this->assertTrue($asset->getFromDB($assets_id));

        $this->assertSame(
            1,
            countElementsInTable(\Item_Process::getTable(), ['itemtype' => $classname, 'items_id' => $assets_id]),
            'Process has not been linked to asset :('
        );

        //check for tab presence
        $this->login();
        $this->assertArrayHasKey('Item_Process$1', $asset->defineAllTabs());
    }
}
