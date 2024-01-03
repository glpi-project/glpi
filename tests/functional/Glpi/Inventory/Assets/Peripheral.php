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

class Peripheral extends AbstractInventoryAsset
{
    protected function assetProvider(): array
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
        $this->object($result[0])->isEqualTo(json_decode($expected));
    }


    public function testHandle()
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');

       //first, check there are no peripheral linked to this computer
        $idp = new \Computer_Item();
        $this->boolean($idp->getFromDbByCrit(['computers_id' => $computer->fields['id'], 'itemtype' => 'Peripheral']))
           ->isFalse('A peripheral is already linked to computer!');

       //convert data
        $expected = $this->assetProvider()[0];

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\Peripheral($computer, $json->content->usbdevices);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->object($result[0])->isEqualTo(json_decode($expected['expected']));

       //handle
        $asset->handleLinks();

        $agent = new \Agent();
        $agent->getEmpty();
        $asset->setAgent($agent);

        $asset->handle();
        $this->boolean($idp->getFromDbByCrit(['computers_id' => $computer->fields['id'], 'itemtype' => 'Peripheral']))
           ->isTrue('Peripheral has not been linked to computer :(');
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
        $this->integer($computers_id)->isGreaterThan(0);

        $manufacturer = new \Manufacturer();
        $manufacturers_id = $manufacturer->add([
            'name' => 'Validity Sensors, Inc.'
        ]);
        $this->integer($manufacturers_id)->isGreaterThan(0);

        $periph_1_id = $periph->add([
            'name' => 'VFS451 Fingerprint Reader',
            'manufacturers_id' => $manufacturers_id,
            'serial' => '00B0FE47AC85',
            'entities_id'  => 0
        ]);
        $this->integer($periph_1_id)->isGreaterThan(0);

        $item_periph_1_id = $item_periph->add([
            'computers_id'     => $computers_id,
            'itemtype'     => \Peripheral::class,
            'items_id' => $periph_1_id
        ]);
        $this->integer($item_periph_1_id)->isGreaterThan(0);

        $manufacturers_id = $manufacturer->add([
            'name' => 'O2 Micro, Inc.'
        ]);
        $this->integer($manufacturers_id)->isGreaterThan(0);

        $periph_2_id = $periph->add([
            'name' => 'OZ776 CCID Smartcard Reader',
            'manufacturers_id' => $manufacturers_id,
            'serial' => 'ABCDEF',
            'entities_id'  => 0
        ]);
        $this->integer($periph_2_id)->isGreaterThan(0);

        $item_periph_2_id = $item_periph->add([
            'computers_id' => $computers_id,
            'items_id' => $periph_2_id,
            'itemtype'     => \Peripheral::class
        ]);
        $this->integer($item_periph_2_id)->isGreaterThan(0);

        $manufacturers_id = $manufacturer->add([
            'name' => 'Logitech, Inc.'
        ]);

        $periph_3_id = $periph->add([
            'name' => 'Unifying Receiver',
            'manufacturers_id' => $manufacturers_id,
            'serial' => 'a0b2c3d4e5',
            'entities_id'  => 0
        ]);
        $this->integer($periph_3_id)->isGreaterThan(0);

        $item_periph_3_id = $item_periph->add([
            'computers_id' => $computers_id,
            'items_id' => $periph_3_id,
            'itemtype' => \Peripheral::class
        ]);
        $this->integer($item_periph_3_id)->isGreaterThan(0);

        $periphs = $item_periph->find(['itemtype' => \Peripheral::class, 'computers_id' => $computers_id]);
        $this->integer(count($periphs))->isIdenticalTo(3);
        foreach ($periphs as $p) {
            $this->variable($p['is_dynamic'])->isEqualTo(0);
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
        $this->integer(count($logs))->isIdenticalTo(0);

        //we still have 3 peripherals
        $periphs = $periph->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($periphs))->isIdenticalTo(3);

        //we still have 3 peripherals items linked to the computer
        $periphs = $item_periph->find(['itemtype' => \Peripheral::class, 'computers_id' => $computers_id]);
        $this->integer(count($periphs))->isIdenticalTo(3);

        //peripherals present in the inventory source are now dynamic
        $periphs = $item_periph->find(['itemtype' => \Peripheral::class, 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($periphs))->isIdenticalTo(2);

        //peripheral not present in the inventory is still not dynamic
        $periphs = $item_periph->find(['itemtype' => \Peripheral::class, 'computers_id' => $computers_id, 'is_dynamic' => 0]);
        $this->integer(count($periphs))->isIdenticalTo(1);

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
        $this->integer(count($logs))->isIdenticalTo(0);

        //we still have 3 peripherals
        $periphs = $periph->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($periphs))->isIdenticalTo(3);

        //we now have 2 peripherals linked to computer only
        $periphs = $item_periph->find();
        $periphs = $item_periph->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id]);
        $this->integer(count($periphs))->isIdenticalTo(2);

        //peripheral present in the inventory source is still dynamic
        $periphs = $item_periph->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($periphs))->isIdenticalTo(1);

        //peripheral not present in the inventory is still not dynamic
        $periphs = $item_periph->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id, 'is_dynamic' => 0]);
        $this->integer(count($periphs))->isIdenticalTo(1);
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
        $this->integer(count($logs))->isIdenticalTo(0);

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Validity Sensors, Inc.']])->current();
        $this->array($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);

        //we have 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($peripherals))->isIdenticalTo(1);
        $this->integer(current($peripherals)['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        //we have 1 peripheral items linked to the computer
        $peripherals = $item_peripheral->find(['itemtype' => 'peripheral', 'computers_id' => $computers_id]);
        $this->integer(count($peripherals))->isIdenticalTo(1);

        //peripheral present in the inventory source is dynamic
        $peripherals = $item_peripheral->find(['itemtype' => 'peripheral', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($peripherals))->isIdenticalTo(1);

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

        //we still have only 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($peripherals))->isIdenticalTo(1);
        $this->integer(current($peripherals)['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        //we still have only 1 peripheral items linked to the computer
        $peripherals = $item_peripheral->find(['itemtype' => 'peripheral', 'computers_id' => $computers_id]);
        $this->integer(count($peripherals))->isIdenticalTo(1);

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
        $this->integer(count($logs))->isIdenticalTo(0);

        $computers_2_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_2_id)->isGreaterThan(0);

        //we still have only 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($peripherals))->isIdenticalTo(1);
        $this->integer(current($peripherals)['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        //no longer linked on first computer inventoried
        $peripherals = $item_peripheral->find(['itemtype' => 'peripheral', 'computers_id' => $computers_id]);
        $this->integer(count($peripherals))->isIdenticalTo(0);

        //but now linked on last inventoried computer
        $peripherals = $item_peripheral->find(['itemtype' => 'peripheral', 'computers_id' => $computers_2_id]);
        $this->integer(count($peripherals))->isIdenticalTo(1);

        //peripheral is still dynamic
        $peripherals = $item_peripheral->find(['itemtype' => 'peripheral', 'computers_id' => $computers_2_id, 'is_dynamic' => 1]);
        $this->integer(count($peripherals))->isIdenticalTo(1);

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
        $this->integer(count($logs))->isIdenticalTo(0);

        //we still have only 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($peripherals))->isIdenticalTo(1);
        $this->integer(current($peripherals)['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        //linked again on first computer inventoried
        $peripherals = $item_peripheral->find(['itemtype' => 'peripheral', 'computers_id' => $computers_id]);
        $this->integer(count($peripherals))->isIdenticalTo(1);

        //no longer linked on last inventoried computer
        $peripherals = $item_peripheral->find(['itemtype' => 'peripheral', 'computers_id' => $computers_2_id]);
        $this->integer(count($peripherals))->isIdenticalTo(0);

        //peripheral is still dynamic
        $peripherals = $item_peripheral->find(['itemtype' => 'peripheral', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($peripherals))->isIdenticalTo(1);
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
        $this->integer(count($logs))->isIdenticalTo(0);

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Validity Sensors, Inc.']])->current();
        $this->array($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);

        //we have 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($peripherals))->isIdenticalTo(1);
        $this->integer(current($peripherals)['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        //we have 1 peripheral items linked to the computer
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id]);
        $this->integer(count($peripherals))->isIdenticalTo(1);

        //peripheral present in the inventory source is dynamic
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($peripherals))->isIdenticalTo(1);

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

        //we still have only 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($peripherals))->isIdenticalTo(1);
        $this->integer(current($peripherals)['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        //we still have only 1 peripheral items linked to the computer
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id]);
        $this->integer(count($peripherals))->isIdenticalTo(1);

        //set to global management
        $this->boolean($peripheral->getFromDB(current($peripherals)['items_id']));
        $this->boolean($peripheral->update(['id' => $peripheral->fields['id'], 'is_global' => \Config::GLOBAL_MANAGEMENT]));

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
        $this->integer(count($logs))->isIdenticalTo(0);

        $computers_2_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_2_id)->isGreaterThan(0);

        //we still have only 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($peripherals))->isIdenticalTo(1);
        $this->integer(current($peripherals)['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        //still linked on first computer inventoried
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id]);
        $this->integer(count($peripherals))->isIdenticalTo(1);

        //also linked on last inventoried computer
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_2_id]);
        $this->integer(count($peripherals))->isIdenticalTo(1);

        //peripheral is still dynamic
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($peripherals))->isIdenticalTo(1);
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_2_id, 'is_dynamic' => 1]);
        $this->integer(count($peripherals))->isIdenticalTo(1);
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
        $this->integer(count($logs))->isIdenticalTo(0);

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Validity Sensors, Inc.']])->current();
        $this->array($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);

        //we have 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($peripherals))->isIdenticalTo(1);
        $this->integer(current($peripherals)['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        //we have 1 peripheral items linked to the computer
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id]);
        $this->integer(count($peripherals))->isIdenticalTo(1);

        //peripheral present in the inventory source is dynamic
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($peripherals))->isIdenticalTo(1);

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
        $this->integer(count($logs))->isIdenticalTo(0);

        $computers_2_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_2_id)->isGreaterThan(0);

        //we still have only 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($peripherals))->isIdenticalTo(1);
        $this->integer(current($peripherals)['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        //still linked on first computer inventoried
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id]);
        $this->integer(count($peripherals))->isIdenticalTo(1);

        //also linked on last inventoried computer
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_2_id]);
        $this->integer(count($peripherals))->isIdenticalTo(1);

        //peripheral is still dynamic
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($peripherals))->isIdenticalTo(1);
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_2_id, 'is_dynamic' => 1]);
        $this->integer(count($peripherals))->isIdenticalTo(1);
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
        $this->integer(count($logs))->isIdenticalTo(0);

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Validity Sensors, Inc.']])->current();
        $this->array($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);

        //we have 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($peripherals))->isIdenticalTo(1);

        //we have 1 peripheral items linked to the computer
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id]);
        $this->integer(count($peripherals))->isIdenticalTo(1);

        //peripheral present in the inventory source is dynamic
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($peripherals))->isIdenticalTo(1);

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

        //we still have only 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($peripherals))->isIdenticalTo(1);
        $this->integer(current($peripherals)['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        //we still have only 1 peripheral items linked to the computer
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id]);
        $this->integer(count($peripherals))->isIdenticalTo(1);

        //set to global management
        $this->boolean($peripheral->getFromDB(current($peripherals)['items_id']));
        $this->boolean($peripheral->update(['id' => $peripheral->fields['id'], 'is_global' => \Config::GLOBAL_MANAGEMENT]));

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
        $this->integer(count($logs))->isIdenticalTo(0);

        $computers_2_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_2_id)->isGreaterThan(0);

        //we still have only 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($peripherals))->isIdenticalTo(1);
        $this->integer(current($peripherals)['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        //no longer linked on first computer inventoried
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id]);
        $this->integer(count($peripherals))->isIdenticalTo(0);

        //but now linked on last inventoried computer
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_2_id]);
        $this->integer(count($peripherals))->isIdenticalTo(1);

        //peripheral is still dynamic
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_2_id, 'is_dynamic' => 1]);
        $this->integer(count($peripherals))->isIdenticalTo(1);

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
        $this->integer(count($logs))->isIdenticalTo(0);

        //we still have only 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($peripherals))->isIdenticalTo(1);
        $this->integer(current($peripherals)['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        //linked again on first computer inventoried
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id]);
        $this->integer(count($peripherals))->isIdenticalTo(1);

        //no longer linked on last inventoried computer
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_2_id]);
        $this->integer(count($peripherals))->isIdenticalTo(0);

        //peripheral is still dynamic
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($peripherals))->isIdenticalTo(1);
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
        $this->boolean(
            $conf->saveConf([
                'import_peripheral' => 0
            ])
        )->isTrue();
        $this->logout();

        //computer inventory with one peripheral
        $inventory = $this->doInventory($xml_source, true);

        //restore default configuration
        $this->login();
        $this->boolean(
            $conf->saveConf([
                'import_peripheral' => 1
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

        //no peripheral linked to the computer
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($peripherals))->isIdenticalTo(0);

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

        //we now have 1 peripheral
        $peripherals = $peripheral->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($peripherals))->isIdenticalTo(1);

        //we have 1 peripheral items linked to the computer
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id]);
        $this->integer(count($peripherals))->isIdenticalTo(1);

        //peripheral present in the inventory source is dynamic
        $peripherals = $item_peripheral->find(['itemtype' => 'Peripheral', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($peripherals))->isIdenticalTo(1);
    }
}
