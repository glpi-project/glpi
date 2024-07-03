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

/* Test for inc/inventory/asset/controller.class.php */

class PeripheralTest extends AbstractInventoryAsset
{
    public static function assetProvider(): array
    {
        return [
            [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
         <REQUEST>
         <CONTENT>
            <USBDEVICES>
               <CAPTION>VFS451 Fingerprint Reader</CAPTION>
               <MANUFACTURER>Validity Sensors, Inc.</MANUFACTURER>
               <NAME>VFS451 Fingerprint Reader</NAME>
               <PRODUCTID>0007</PRODUCTID>
               <SERIAL>00B0FE47AC85</SERIAL>
               <VENDORID>138A</VENDORID>
            </USBDEVICES>
            <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
         </CONTENT>
         <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
         <QUERY>INVENTORY</QUERY>
         </REQUEST>",
                'expected'  => '{"caption": "VFS451 Fingerprint Reader", "manufacturer": "Validity Sensors, Inc.", "name": "VFS451 Fingerprint Reader", "productid": "0007", "serial": "00B0FE47AC85", "vendorid": "138A", "manufacturers_id": "Validity Sensors, Inc.", "is_dynamic": 1}'
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
        $asset = new \Glpi\Inventory\Asset\Peripheral($computer, $json->content->usbdevices);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected), $result[0]);
    }


    public function testHandle()
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');

       //first, check there are no peripheral linked to this computer
        $idp = new \Computer_Item();
                 $this->assertFalse(
                     $idp->getFromDbByCrit(['computers_id' => $computer->fields['id'], 'itemtype' => 'Peripheral']),
                     'A peripheral is already linked to computer!'
                 );

       //convert data
        $expected = $this->assetProvider()[0];

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\Peripheral($computer, $json->content->usbdevices);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected['expected']), $result[0]);

       //handle
        $asset->handleLinks();

        $agent = new \Agent();
        $agent->getEmpty();
        $asset->setAgent($agent);

        $asset->handle();
        $this->assertTrue(
            $idp->getFromDbByCrit(['computers_id' => $computer->fields['id'], 'itemtype' => 'Peripheral']),
            'Peripheral has not been linked to computer :('
        );
    }

    public function testInventoryUpdate()
    {
        global $DB;

        $computer = new \Computer();
        $periph = new \Peripheral();
        $item_periph = new \Computer_Item();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <USBDEVICES>
      <CAPTION>VFS451 Fingerprint Reader</CAPTION>
      <MANUFACTURER>Validity Sensors, Inc.</MANUFACTURER>
      <NAME>VFS451 Fingerprint Reader</NAME>
      <PRODUCTID>0007</PRODUCTID>
      <SERIAL>00B0FE47AC85</SERIAL>
      <VENDORID>138A</VENDORID>
    </USBDEVICES>
    <USBDEVICES>
      <CAPTION>OZ776 CCID Smartcard Reader</CAPTION>
      <NAME>OZ776 CCID Smartcard Reader</NAME>
      <CLASS>11</CLASS>
      <MANUFACTURER>O2 Micro, Inc.</MANUFACTURER>
      <PRODUCTID>7772</PRODUCTID>
      <SERIAL>ABCDEF</SERIAL>
      <SUBCLASS>0</SUBCLASS>
      <VENDORID>0b97</VENDORID>
    </USBDEVICES>
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

        //create manually a computer, with 3 peripherals
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $manufacturer = new \Manufacturer();
        $manufacturers_id = $manufacturer->add([
            'name' => 'Validity Sensors, Inc.'
        ]);
        $this->assertGreaterThan(0, $manufacturers_id);

        $periph_1_id = $periph->add([
            'name' => 'VFS451 Fingerprint Reader',
            'manufacturers_id' => $manufacturers_id,
            'serial' => '00B0FE47AC85',
            'entities_id'  => 0
        ]);
        $this->assertGreaterThan(0, $periph_1_id);

        $item_periph_1_id = $item_periph->add([
            'computers_id'     => $computers_id,
            'itemtype'     => \Peripheral::class,
            'items_id' => $periph_1_id
        ]);
        $this->assertGreaterThan(0, $item_periph_1_id);

        $manufacturers_id = $manufacturer->add([
            'name' => 'O2 Micro, Inc.'
        ]);
        $this->assertGreaterThan(0, $manufacturers_id);

        $periph_2_id = $periph->add([
            'name' => 'OZ776 CCID Smartcard Reader',
            'manufacturers_id' => $manufacturers_id,
            'serial' => 'ABCDEF',
            'entities_id'  => 0
        ]);
        $this->assertGreaterThan(0, $periph_2_id);

        $item_periph_2_id = $item_periph->add([
            'computers_id' => $computers_id,
            'items_id' => $periph_2_id,
            'itemtype'     => \Peripheral::class
        ]);
        $this->assertGreaterThan(0, $item_periph_2_id);

        $manufacturers_id = $manufacturer->add([
            'name' => 'Logitech, Inc.'
        ]);

        $periph_3_id = $periph->add([
            'name' => 'Unifying Receiver',
            'manufacturers_id' => $manufacturers_id,
            'serial' => 'a0b2c3d4e5',
            'entities_id'  => 0
        ]);
        $this->assertGreaterThan(0, $periph_3_id);

        $item_periph_3_id = $item_periph->add([
            'computers_id' => $computers_id,
            'items_id' => $periph_3_id,
            'itemtype' => \Peripheral::class
        ]);
        $this->assertGreaterThan(0, $item_periph_3_id);

        $periphs = $item_periph->find(['itemtype' => \Peripheral::class, 'computers_id' => $computers_id]);
        $this->assertCount(3, $periphs);
        foreach ($periphs as $p) {
            $this->assertEquals(0, $p['is_dynamic']);
        }

        $this->nblogs = countElementsInTable(\Log::getTable());

        //computer inventory knows only "Fingerprint" and "Smartcard reader" peripherals
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

        //we still have 3 peripherals
        $periphs = $periph->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(3, $periphs);

        //we still have 3 peripherals items linked to the computer
        $periphs = $item_periph->find(['itemtype' => \Peripheral::class, 'computers_id' => $computers_id]);
        $this->assertCount(3, $periphs);

        //peripherals present in the inventory source are now dynamic
        $periphs = $item_periph->find(['itemtype' => \Peripheral::class, 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(2, $periphs);

        //peripheral not present in the inventory is still not dynamic
        $periphs = $item_periph->find(['itemtype' => \Peripheral::class, 'computers_id' => $computers_id, 'is_dynamic' => 0]);
        $this->assertCount(1, $periphs);

        //Redo inventory, but with removed "Smartcard reader" peripheral
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <USBDEVICES>
      <CAPTION>VFS451 Fingerprint Reader</CAPTION>
      <MANUFACTURER>Validity Sensors, Inc.</MANUFACTURER>
      <NAME>VFS451 Fingerprint Reader</NAME>
      <PRODUCTID>0007</PRODUCTID>
      <SERIAL>00B0FE47AC85</SERIAL>
      <VENDORID>138A</VENDORID>
    </USBDEVICES>
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

        $this->nblogs = countElementsInTable(\Log::getTable());

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

        //we still have 3 peripherals
        $periphs = $periph->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(3, $periphs);

        //we now have 2 peripherals linked to computer only
        $periphs = $item_periph->find();
        $periphs = $item_periph->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id]);
        $this->assertCount(2, $periphs);

        //peripheral present in the inventory source is still dynamic
        $periphs = $item_periph->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $periphs);

        //peripheral not present in the inventory is still not dynamic
        $periphs = $item_periph->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id, 'is_dynamic' => 0]);
        $this->assertCount(1, $periphs);
    }

    public function testInventoryMove()
    {
        global $DB;

        $peripheral = new \Peripheral();
        $item_peripheral = new \Computer_Item();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <USBDEVICES>
      <CAPTION>VFS451 Fingerprint Reader</CAPTION>
      <MANUFACTURER>Validity Sensors, Inc.</MANUFACTURER>
      <NAME>VFS451 Fingerprint Reader</NAME>
      <PRODUCTID>0007</PRODUCTID>
      <SERIAL>00B0FE47AC85</SERIAL>
      <VENDORID>138A</VENDORID>
    </USBDEVICES>
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

        //computer inventory with one peripheral
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

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Validity Sensors, Inc.']])->current();
        $this->assertIsArray($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $computers_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $computers_id);

        //we have 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $peripherals);
        $this->assertSame($manufacturers_id, current($peripherals)['manufacturers_id']);

        //we have 1 peripheral items linked to the computer
        $peripherals = $item_peripheral->find(['itemtype' => 'peripheral', 'computers_id' => $computers_id]);
        $this->assertCount(1, $peripherals);

        //peripheral present in the inventory source is dynamic
        $peripherals = $item_peripheral->find(['itemtype' => 'peripheral', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $peripherals);

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

        //we still have only 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $peripherals);
        $this->assertSame($manufacturers_id, current($peripherals)['manufacturers_id']);

        //we still have only 1 peripheral items linked to the computer
        $peripherals = $item_peripheral->find(['itemtype' => 'peripheral', 'computers_id' => $computers_id]);
        $this->assertCount(1, $peripherals);

        //same peripheral, but on another computer
        $xml_source_2 = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <USBDEVICES>
      <CAPTION>VFS451 Fingerprint Reader</CAPTION>
      <MANUFACTURER>Validity Sensors, Inc.</MANUFACTURER>
      <NAME>VFS451 Fingerprint Reader</NAME>
      <PRODUCTID>0007</PRODUCTID>
      <SERIAL>00B0FE47AC85</SERIAL>
      <VENDORID>138A</VENDORID>
    </USBDEVICES>
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

        //computer inventory with one peripheral
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

        //we still have only 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $peripherals);
        $this->assertSame($manufacturers_id, current($peripherals)['manufacturers_id']);

        //no longer linked on first computer inventoried
        $peripherals = $item_peripheral->find(['itemtype' => 'peripheral', 'computers_id' => $computers_id]);
        $this->assertCount(0, $peripherals);

        //but now linked on last inventoried computer
        $peripherals = $item_peripheral->find(['itemtype' => 'peripheral', 'computers_id' => $computers_2_id]);
        $this->assertCount(1, $peripherals);

        //peripheral is still dynamic
        $peripherals = $item_peripheral->find(['itemtype' => 'peripheral', 'computers_id' => $computers_2_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $peripherals);

        //replay first computer inventory, peripheral is back \o/
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

        //we still have only 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $peripherals);
        $this->assertSame($manufacturers_id, current($peripherals)['manufacturers_id']);

        //linked again on first computer inventoried
        $peripherals = $item_peripheral->find(['itemtype' => 'peripheral', 'computers_id' => $computers_id]);
        $this->assertCount(1, $peripherals);

        //no longer linked on last inventoried computer
        $peripherals = $item_peripheral->find(['itemtype' => 'peripheral', 'computers_id' => $computers_2_id]);
        $this->assertCount(0, $peripherals);

        //peripheral is still dynamic
        $peripherals = $item_peripheral->find(['itemtype' => 'peripheral', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $peripherals);
    }

    public function testInventoryNoMove()
    {
        global $DB;

        $peripheral = new \Peripheral();
        $item_peripheral = new \Computer_Item();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <USBDEVICES>
      <CAPTION>VFS451 Fingerprint Reader</CAPTION>
      <MANUFACTURER>Validity Sensors, Inc.</MANUFACTURER>
      <NAME>VFS451 Fingerprint Reader</NAME>
      <PRODUCTID>0007</PRODUCTID>
      <SERIAL>00B0FE47AC85</SERIAL>
      <VENDORID>138A</VENDORID>
    </USBDEVICES>
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

        //computer inventory with one peripheral
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

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Validity Sensors, Inc.']])->current();
        $this->assertIsArray($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $computers_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $computers_id);

        //we have 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $peripherals);
        $this->assertSame($manufacturers_id, current($peripherals)['manufacturers_id']);

        //we have 1 peripheral items linked to the computer
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id]);
        $this->assertCount(1, $peripherals);

        //peripheral present in the inventory source is dynamic
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $peripherals);

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

        //we still have only 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $peripherals);
        $this->assertSame($manufacturers_id, current($peripherals)['manufacturers_id']);

        //we still have only 1 peripheral items linked to the computer
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id]);
        $this->assertCount(1, $peripherals);

        //set to global management
        $this->assertTrue($peripheral->getFromDB(current($peripherals)['items_id']));
        $this->assertTrue($peripheral->update(['id' => $peripheral->fields['id'], 'is_global' => \Config::GLOBAL_MANAGEMENT]));

        //same peripheral, but on another computer
        $xml_source_2 = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <USBDEVICES>
      <CAPTION>VFS451 Fingerprint Reader</CAPTION>
      <MANUFACTURER>Validity Sensors, Inc.</MANUFACTURER>
      <NAME>VFS451 Fingerprint Reader</NAME>
      <PRODUCTID>0007</PRODUCTID>
      <SERIAL>00B0FE47AC85</SERIAL>
      <VENDORID>138A</VENDORID>
    </USBDEVICES>
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

        //computer inventory with one peripheral
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

        //we still have only 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $peripherals);
        $this->assertSame($manufacturers_id, current($peripherals)['manufacturers_id']);

        //still linked on first computer inventoried
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id]);
        $this->assertCount(1, $peripherals);

        //also linked on last inventoried computer
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_2_id]);
        $this->assertCount(1, $peripherals);

        //peripheral is still dynamic
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $peripherals);
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_2_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $peripherals);
    }

    public function testInventoryGlobalManagement()
    {
        global $DB;

        $peripheral = new \Peripheral();
        $item_peripheral = new \Computer_Item();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <USBDEVICES>
      <CAPTION>VFS451 Fingerprint Reader</CAPTION>
      <MANUFACTURER>Validity Sensors, Inc.</MANUFACTURER>
      <NAME>VFS451 Fingerprint Reader</NAME>
      <PRODUCTID>0007</PRODUCTID>
      <SERIAL>00B0FE47AC85</SERIAL>
      <VENDORID>138A</VENDORID>
    </USBDEVICES>
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
        \Config::setConfigurationValues('core', ['peripherals_management_restrict' => \Config::GLOBAL_MANAGEMENT]);
        $this->logout();

        //computer inventory with one peripheral
        $inventory = $this->doInventory($xml_source, true);

        //restore default configuration
        $this->login();
        \Config::setConfigurationValues('core', ['peripherals_management_restrict' => \Config::NO_MANAGEMENT]);
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

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Validity Sensors, Inc.']])->current();
        $this->assertIsArray($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $computers_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $computers_id);

        //we have 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $peripherals);
        $this->assertSame($manufacturers_id, current($peripherals)['manufacturers_id']);

        //we have 1 peripheral items linked to the computer
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id]);
        $this->assertCount(1, $peripherals);

        //peripheral present in the inventory source is dynamic
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $peripherals);

        //same peripheral, but on another computer
        $xml_source_2 = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <USBDEVICES>
      <CAPTION>VFS451 Fingerprint Reader</CAPTION>
      <MANUFACTURER>Validity Sensors, Inc.</MANUFACTURER>
      <NAME>VFS451 Fingerprint Reader</NAME>
      <PRODUCTID>0007</PRODUCTID>
      <SERIAL>00B0FE47AC85</SERIAL>
      <VENDORID>138A</VENDORID>
    </USBDEVICES>
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
        \Config::setConfigurationValues('core', ['peripherals_management_restrict' => \Config::GLOBAL_MANAGEMENT]);
        $this->logout();

        //computer inventory with one peripheral
        $inventory = $this->doInventory($xml_source_2, true);

        //restore default configuration
        $this->login();
        \Config::setConfigurationValues('core', ['peripherals_management_restrict' => \Config::NO_MANAGEMENT]);
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

        //we still have only 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $peripherals);
        $this->assertSame($manufacturers_id, current($peripherals)['manufacturers_id']);

        //still linked on first computer inventoried
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id]);
        $this->assertCount(1, $peripherals);

        //also linked on last inventoried computer
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_2_id]);
        $this->assertCount(1, $peripherals);

        //peripheral is still dynamic
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $peripherals);
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_2_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $peripherals);
    }

    public function testInventoryUnitManagement()
    {
        global $DB;

        $peripheral = new \Peripheral();
        $item_peripheral = new \Computer_Item();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <USBDEVICES>
      <CAPTION>VFS451 Fingerprint Reader</CAPTION>
      <MANUFACTURER>Validity Sensors, Inc.</MANUFACTURER>
      <NAME>VFS451 Fingerprint Reader</NAME>
      <PRODUCTID>0007</PRODUCTID>
      <SERIAL>00B0FE47AC85</SERIAL>
      <VENDORID>138A</VENDORID>
    </USBDEVICES>
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
        \Config::setConfigurationValues('core', ['peripherals_management_restrict' => \Config::UNIT_MANAGEMENT]);
        $this->logout();

        //computer inventory with one peripheral
        $inventory = $this->doInventory($xml_source, true);

        //restore default configuration
        $this->login();
        \Config::setConfigurationValues('core', ['peripherals_management_restrict' => \Config::NO_MANAGEMENT]);
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

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Validity Sensors, Inc.']])->current();
        $this->assertIsArray($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $computers_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $computers_id);

        //we have 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $peripherals);

        //we have 1 peripheral items linked to the computer
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id]);
        $this->assertCount(1, $peripherals);

        //peripheral present in the inventory source is dynamic
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $peripherals);

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

        //we still have only 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $peripherals);
        $this->assertSame($manufacturers_id, current($peripherals)['manufacturers_id']);

        //we still have only 1 peripheral items linked to the computer
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id]);
        $this->assertCount(1, $peripherals);

        //set to global management
        $this->assertTrue($peripheral->getFromDB(current($peripherals)['items_id']));
        $this->assertTrue($peripheral->update(['id' => $peripheral->fields['id'], 'is_global' => \Config::GLOBAL_MANAGEMENT]));

        //same peripheral, but on another computer
        $xml_source_2 = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <USBDEVICES>
      <CAPTION>VFS451 Fingerprint Reader</CAPTION>
      <MANUFACTURER>Validity Sensors, Inc.</MANUFACTURER>
      <NAME>VFS451 Fingerprint Reader</NAME>
      <PRODUCTID>0007</PRODUCTID>
      <SERIAL>00B0FE47AC85</SERIAL>
      <VENDORID>138A</VENDORID>
    </USBDEVICES>
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
        \Config::setConfigurationValues('core', ['peripherals_management_restrict' => \Config::UNIT_MANAGEMENT]);
        $this->logout();

        //computer inventory with one peripheral
        $inventory = $this->doInventory($xml_source_2, true);

        //restore default configuration
        $this->login();
        \Config::setConfigurationValues('core', ['peripherals_management_restrict' => \Config::NO_MANAGEMENT]);
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

        //we still have only 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $peripherals);
        $this->assertSame($manufacturers_id, current($peripherals)['manufacturers_id']);

        //no longer linked on first computer inventoried
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id]);
        $this->assertCount(0, $peripherals);

        //but now linked on last inventoried computer
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_2_id]);
        $this->assertCount(1, $peripherals);

        //peripheral is still dynamic
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_2_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $peripherals);

        //change default configuration to unit management
        $this->login();
        \Config::setConfigurationValues('core', ['peripherals_management_restrict' => \Config::UNIT_MANAGEMENT]);
        $this->logout();

        //replay first computer inventory, peripheral is back \o/
        $this->doInventory($xml_source, true);

        //restore default configuration
        $this->login();
        \Config::setConfigurationValues('core', ['peripherals_management_restrict' => \Config::NO_MANAGEMENT]);
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

        //we still have only 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $peripherals);
        $this->assertSame($manufacturers_id, current($peripherals)['manufacturers_id']);

        //linked again on first computer inventoried
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id]);
        $this->assertCount(1, $peripherals);

        //no longer linked on last inventoried computer
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_2_id]);
        $this->assertCount(0, $peripherals);

        //peripheral is still dynamic
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $peripherals);
    }

    public function testInventoryImportOrNot()
    {
        global $DB;

        $peripheral = new \Peripheral();
        $item_peripheral = new \Computer_Item();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <USBDEVICES>
      <CAPTION>VFS451 Fingerprint Reader</CAPTION>
      <MANUFACTURER>Validity Sensors, Inc.</MANUFACTURER>
      <NAME>VFS451 Fingerprint Reader</NAME>
      <PRODUCTID>0007</PRODUCTID>
      <SERIAL>00B0FE47AC85</SERIAL>
      <VENDORID>138A</VENDORID>
    </USBDEVICES>
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

        //per default, configuration allows peripheral import. change that.
        $this->login();
        $conf = new \Glpi\Inventory\Conf();
        $this->assertTrue(
            $conf->saveConf([
                'import_peripheral' => 0
            ])
        );
        $this->logout();

        //computer inventory with one peripheral
        $inventory = $this->doInventory($xml_source, true);

        //restore default configuration
        $this->login();
        $this->assertTrue(
            $conf->saveConf([
                'import_peripheral' => 1
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

        //no peripheral linked to the computer
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(0, $peripherals);

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

        //we now have 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(1, $peripherals);

        //we have 1 peripheral items linked to the computer
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id]);
        $this->assertCount(1, $peripherals);

        //peripheral present in the inventory source is dynamic
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $peripherals);
    }
}
