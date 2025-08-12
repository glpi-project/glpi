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

/* Test for inc/inventory/asset/environment.class.php */

class Environment extends AbstractInventoryAsset
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
    <ENVS>
      <KEY>LC_ALL</KEY>
      <VAL>C</VAL>
    </ENVS>
    <ENVS>
      <KEY>LANG</KEY>
      <VAL>C</VAL>
    </ENVS>
    <ENVS>
      <KEY>SHELL</KEY>
      <VAL>/bin/zsh</VAL>
    </ENVS>
    <ENVS>
      <KEY>PATH</KEY>
      <VAL>/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/root/bin</VAL>
    </ENVS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '[{"key":"LC_ALL","val":"C","value":"C","is_dynamic":1},{"key":"LANG","val":"C","value":"C","is_dynamic":1},{"key":"SHELL","val":"\\/bin\\/zsh","value":"\\/bin\\/zsh","is_dynamic":1},{"key":"PATH","val":"\\/usr\\/local\\/sbin:\\/usr\\/local\\/bin:\\/usr\\/sbin:\\/usr\\/bin:\\/root\\/bin","value":"\\/usr\\/local\\/sbin:\\/usr\\/local\\/bin:\\/usr\\/sbin:\\/usr\\/bin:\\/root\\/bin","is_dynamic":1}]',
            ],
        ];
    }

    #[DataProvider('assetProvider')]
    public function testPrepare($xml, $expected)
    {
        $this->login();
        $conf = new Conf();
        $this->assertTrue($conf->saveConf([
            'import_env' => 1,
        ]));
        $this->logout();

        $converter = new Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\Environment($computer, $json->content->envs);
        $asset->setExtraData((array) $json->content);

        $this->assertTrue($asset->checkConf($conf));

        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected), $result);
    }

    public function testHandle()
    {
        $this->login();
        $conf = new Conf();
        $this->assertTrue($conf->saveConf([
            'import_env' => 1,
        ]));
        $this->logout();

        $computer = getItemByTypeName('Computer', '_test_pc01');

        //first, check there are no environments linked to this computer
        $this->assertSame(
            0,
            countElementsInTable(\Item_Environment::getTable()),
            'An environment is already linked to computer!'
        );

        //convert data
        $expected = $this->assetProvider()[0];

        $converter = new Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\Environment($computer, $json->content->envs);
        $asset->setExtraData((array) $json->content);

        $this->assertTrue($asset->checkConf($conf));

        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected['expected']), $result);

        //handle
        $asset->handleLinks();
        $asset->handle();

        $this->assertSame(
            count($result),
            countElementsInTable(\Item_Environment::getTable()),
            'Environments has not been linked to computer :('
        );
    }

    public function testGenericAssetEnvironment(): void
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
            'import_env' => 1,
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
            4,
            countElementsInTable(\Item_Environment::getTable(), ['itemtype' => $classname, 'items_id' => $assets_id]),
            'Environments has not been linked to asset :('
        );

        //check for tab presence
        $this->login();
        $this->assertArrayHasKey('Item_Environment$1', $asset->defineAllTabs());
    }
}
