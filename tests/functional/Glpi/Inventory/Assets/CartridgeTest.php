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

use Glpi\Inventory\Asset\Cartridge;
use Glpi\Inventory\Converter;
use PHPUnit\Framework\Attributes\DataProvider;

include_once __DIR__ . '/../../../../abstracts/AbstractInventoryAsset.php';

/* Test for inc/inventory/asset/cartridge.class.php */

class CartridgeTest extends AbstractInventoryAsset
{
    public static function assetProvider(): array
    {
        return [
            [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST><CONTENT><DEVICE>
      <CARTRIDGES>
        <TONERBLACK>71</TONERBLACK>
      </CARTRIDGES>
      <FIRMWARES>
        <DATE>20190916</DATE>
        <DESCRIPTION>device firmware</DESCRIPTION>
        <MANUFACTURER>Hewlett-Packard</MANUFACTURER>
        <NAME>HP LaserJet M507</NAME>
        <TYPE>device</TYPE>
        <VERSION>2409048_052887</VERSION>
      </FIRMWARES>
      <INFO>
        <COMMENTS>HP ETHERNET MULTI-ENVIRONMENT,ROM none,JETDIRECT,JD153,EEPROM JSI24090012,CIDATE 09/10/2019</COMMENTS>
        <FIRMWARE>2409048_052887</FIRMWARE>
        <ID>8997</ID>
        <IPS>
          <IP>10.59.29.175</IP>
        </IPS>
        <MAC>00:68:eb:f2:be:10</MAC>
        <MANUFACTURER>Hewlett-Packard</MANUFACTURER>
        <MODEL>HP LaserJet M507</MODEL>
        <NAME>NPIF2BE10</NAME>
        <RAM>512</RAM>
        <SERIAL>PHCVN191TG</SERIAL>
        <TYPE>PRINTER</TYPE>
        <UPTIME>7 days, 01:26:41.98</UPTIME>
      </INFO>
      <PAGECOUNTERS>
        <DUPLEX>831</DUPLEX>
        <TOTAL>1802</TOTAL>
      </PAGECOUNTERS>
    </DEVICE></CONTENT><QUERY>SNMP</QUERY><DEVICEID>foo</DEVICEID></REQUEST>",
                'expected'  => '{"tonerblack":"71"}',
            ],
        ];
    }

    #[DataProvider('assetProvider')]
    public function testPrepare($xml, $expected)
    {
        $converter = new Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $printer = getItemByTypeName('Printer', '_test_printer_all');
        $asset = new Cartridge($printer, $json->content->cartridges);
        $asset->setExtraData((array) $json->content);
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected), $result[0]);
    }

    public function testKnownTags()
    {
        $cart = new Cartridge(getItemByTypeName('Printer', '_test_printer_all'));

        $tags = $cart->knownTags();
        $this->assertCount(194, $tags);
        foreach ($tags as $tag) {
            $this->assertArrayHasKey('name', $tag);
        }
    }

    public function testHandle()
    {
        //convert data
        $expected = $this->assetProvider()[0];

        $converter = new Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $printer = getItemByTypeName('Printer', '_test_printer_all');
        $asset = new Cartridge($printer, $json->content->cartridges);
        $asset->setExtraData((array) $json->content);
        $result = $asset->prepare();
        $this->assertEquals(json_decode('{"tonerblack":"71"}'), $result[0]);

        $agent = new \Agent();
        $agent->getEmpty();
        $asset->setAgent($agent);

        //handle
        $asset->handleLinks();
        $asset->handle();

        $printers_id = $printer->fields['id'];

        global $DB;
        $iterator = $DB->request([
            'FROM'   => \Printer_CartridgeInfo::getTable(),
            'WHERE'  => ['printers_id' => $printers_id],
        ]);
        $this->assertCount(1, $iterator);

        $result = $iterator->current();
        $this->assertIsArray($result);
        $this->assertSame('tonerblack', $result['property']);
        $this->assertSame('71', $result['value']);

        //test level changed
        $json = json_decode($data);
        $json->content->cartridges[0]->tonerblack = 60;

        $asset = new Cartridge($printer, $json->content->cartridges);
        $asset->setExtraData((array) $json->content);
        $result = $asset->prepare();
        $this->assertEquals(json_decode('{"tonerblack":"60"}'), $result[0]);

        //handle
        $asset->handleLinks();
        $asset->handle();

        global $DB;
        $iterator = $DB->request([
            'FROM'   => \Printer_CartridgeInfo::getTable(),
            'WHERE'  => ['printers_id' => $printers_id],
        ]);
        $this->assertCount(1, $iterator);

        $result = $iterator->current();
        $this->assertIsArray($result);
        $this->assertSame('tonerblack', $result['property']);
        $this->assertSame('60', $result['value']);

        //test Printer_CartridgeInfo removal
        $this->assertTrue($printer->delete(['id' => $printers_id], true));

        $iterator = $DB->request([
            'FROM'   => \Printer_CartridgeInfo::getTable(),
            'WHERE'  => ['printers_id' => $printers_id],
        ]);
        $this->assertCount(0, $iterator);
    }
}
