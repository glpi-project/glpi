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

/* Test for inc/inventory/asset/printer.class.php */

class Printer extends AbstractInventoryAsset
{
    const INV_FIXTURES = GLPI_ROOT . '/vendor/glpi-project/inventory_format/examples/';

    protected function assetProvider(): array
    {
        return [
            [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <PRINTERS>
      <DRIVER>HP Color LaserJet Pro MFP M476 PCL 6</DRIVER>
      <NAME>HP Color LaserJet Pro MFP M476 PCL 6</NAME>
      <NETWORK>0</NETWORK>
      <PORT>10.253.6.117</PORT>
      <PRINTPROCESSOR>hpcpp155</PRINTPROCESSOR>
      <RESOLUTION>600x600</RESOLUTION>
      <SHARED>0</SHARED>
      <SHARENAME>HP Color LaserJet Pro MFP M476 PCL 6  (1)</SHARENAME>
      <STATUS>Unknown</STATUS>
    </PRINTERS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"driver": "HP Color LaserJet Pro MFP M476 PCL 6", "name": "HP Color LaserJet Pro MFP M476 PCL 6", "network": false, "printprocessor": "hpcpp155", "resolution": "600x600", "shared": false, "sharename": "HP Color LaserJet Pro MFP M476 PCL 6  (1)", "status": "Unknown", "have_usb": 0, "autoupdatesystems_id": "GLPI Native Inventory", "last_inventory_update": "DATE_NOW"}'
            ]
        ];
    }

    /**
     * @dataProvider assetProvider
     */
    public function testPrepare($xml, $expected)
    {
        $date_now = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date_now;
        $expected = str_replace('DATE_NOW', $_SESSION['glpi_currenttime'], $expected);

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\Printer($computer, $json->content->printers);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $this->object($result[0])->isEqualTo(json_decode($expected));
    }

    public function testSnmpPrinter()
    {
        $date_now = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date_now;
        $json_str = file_get_contents(self::INV_FIXTURES . 'printer_1.json');

        $json = json_decode($json_str);

        $printer = new \Printer();

        $data = (array)$json->content;
        $inventory = new \Glpi\Inventory\Inventory();
        $this->boolean($inventory->setData($json))->isTrue();

        $agent = new \Agent();
        $this->integer($agent->handleAgent($inventory->extractMetadata()))->isIdenticalTo(0);

        $main = new \Glpi\Inventory\Asset\Printer($printer, $json);
        $main->setAgent($agent)->setExtraData($data);
        $main->checkConf(new \Glpi\Inventory\Conf());
        $result = $main->prepare();
        $this->array($result)->hasSize(1);
        $this->array((array)$result[0])->isIdenticalTo([
            'autoupdatesystems_id' => 'GLPI Native Inventory',
            'last_inventory_update' => $date_now,
            "is_deleted" => 0,
            'firmware' => '2409048_052887',
            'ips' => ['10.59.29.175'],
            'mac' => '00:68:eb:f2:be:10',
            'manufacturer' => 'Hewlett-Packard',
            'model' => 'HP LaserJet M507',
            'name' => 'NPIF2BE10',
            'serial' => 'PHCVN191TG',
            'type' => 'Printer',
            'uptime' => '7 days, 01:26:41.98',
            'printermodels_id' => 'HP LaserJet M507',
            'printertypes_id' => 'Printer',
            'manufacturers_id' => 'Hewlett-Packard',
            'snmpcredentials_id' => 4,
            'have_usb' => 0,
            'have_ethernet' => 1,
            'memory_size' => 512,
            'last_pages_counter' => 1802,
        ]);

       //no management port (network_device->ips same as network_port->ips)
        $this->array($main->getNetworkPorts())->isIdenticalTo([]);
        $this->array($main->getManagementPorts())->hasSize(0);

        $pcounter = new \stdClass();
        $pcounter->rectoverso = 831;
        $pcounter->rv_pages = 831;
        $pcounter->total = 1802;
        $pcounter->total_pages = 1802;
        $this->object($main->getCounters())->isEqualTo($pcounter);

        $main->handle();

        $this->boolean($main->areLinksHandled())->isTrue();

        $this->boolean($printer->getFromDB($printer->fields['id']))->isTrue();
        $this->integer($printer->fields['last_pages_counter'])->isIdenticalTo($pcounter->total);

        global $DB;
        $iterator = $DB->request([
            'FROM'   => \PrinterLog::getTable(),
            'WHERE'  => ['printers_id' => $printer->fields['id']]
        ]);
        $this->integer(count($iterator))->isIdenticalTo(1);

        $result = $iterator->current();

        unset($result['id']);

        $this->array($result)->isEqualTo([
            'printers_id' => $printer->fields['id'],
            'total_pages' => 1802,
            'bw_pages' => 0,
            'color_pages' => 0,
            'rv_pages' => 831,
            'prints' => 0,
            'bw_prints' => 0,
            'color_prints' => 0,
            'copies' => 0,
            'bw_copies' => 0,
            'color_copies' => 0,
            'scanned' => 0,
            'faxed' => 0,
            'date' => date('Y-m-d', strtotime($_SESSION['glpi_currenttime'])),
            'date_creation' => $_SESSION['glpi_currenttime'],
            'date_mod' => $_SESSION['glpi_currenttime'],
        ]);
    }

    public function testSnmpPrinterManagementPortAdded()
    {
        /**
         * This check if management port is well added
         * When network-device->ips and networkport->ips are different
         */
        $date_now = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date_now;
        $json_str = file_get_contents(self::INV_FIXTURES . 'printer_2.json');

        $json = json_decode($json_str);

        $printer = new \Printer();

        $data = (array)$json->content;
        $inventory = new \Glpi\Inventory\Inventory();
        $this->boolean($inventory->setData($json))->isTrue();

        $agent = new \Agent();
        $this->integer($agent->handleAgent($inventory->extractMetadata()))->isIdenticalTo(0);

        $main = new \Glpi\Inventory\Asset\Printer($printer, $json);
        $main->setAgent($agent)->setExtraData($data);
        $main->checkConf(new \Glpi\Inventory\Conf());
        $result = $main->prepare();
        $this->array($result)->hasSize(1);
        $this->array((array)$result[0])->isIdenticalTo([
            'autoupdatesystems_id' => 'GLPI Native Inventory',
            'last_inventory_update' => $date_now,
            "is_deleted" => 0,
            'firmware' => '8745213_951236',
            'ips' => ['10.59.29.176'],
            'mac' => '00:85:eb:f4:be:20',
            'manufacturer' => 'Canon',
            'model' => 'Canon MX 5970',
            'name' => 'MX5970',
            'serial' => 'SDFSDF9874',
            'type' => 'Printer',
            'uptime' => '8 days, 01:26:41.98',
            'printermodels_id' => 'Canon MX 5970',
            'printertypes_id' => 'Printer',
            'manufacturers_id' => 'Canon',
            'snmpcredentials_id' => 4,
            'have_usb' => 0,
            'have_ethernet' => 1,
            'memory_size' => 256,
            'last_pages_counter' => 800
        ]);

        //get one management port only
        $this->array($mports = $main->getManagementPorts())->hasSize(1)->hasKey('management');
        $this->array((array)$mports['management'])->isIdenticalTo([
            'mac' => '00:85:eb:f4:be:20',
            'name' => 'Management',
            'netname' => 'internal',
            'instantiation_type' => 'NetworkPortAggregate',
            'is_internal' => true,
            'ipaddress' => [
                '10.59.29.176'
            ]
        ]);

        //do real inventory to check dataDB
        $json_str = file_get_contents(self::INV_FIXTURES . 'printer_2.json');
        $json = json_decode($json_str);
        $this->doInventory($json);

        $printer = new \Printer();
        $this->boolean($printer->getFromDbByCrit(['name' => 'MX5970', 'serial' => 'SDFSDF9874']))->isTrue();

        $np = new \NetworkPort();
        $this->boolean($np->getFromDbByCrit(['itemtype' => 'Printer', 'items_id' => $printer->fields['id'] , 'instantiation_type' => 'NetworkPortAggregate']))->isTrue();
        $this->boolean($np->getFromDbByCrit(['itemtype' => 'Printer', 'items_id' => $printer->fields['id'] , 'instantiation_type' => 'NetworkPortEthernet']))->isTrue();

        //remove printer for other test
        $printer->delete($printer->fields);
    }

    public function testSnmpPrinterManagementPortExcluded()
    {
        /**
         * This check if management port is well excluded
         * When network-device->ips and networkport->ips are same
         */
        $date_now = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date_now;
        $json_str = file_get_contents(self::INV_FIXTURES . 'printer_3.json');

        $json = json_decode($json_str);

        $printer = new \Printer();

        $data = (array)$json->content;
        $inventory = new \Glpi\Inventory\Inventory();
        $this->boolean($inventory->setData($json))->isTrue();

        $agent = new \Agent();
        $this->integer($agent->handleAgent($inventory->extractMetadata()))->isIdenticalTo(0);

        $main = new \Glpi\Inventory\Asset\Printer($printer, $json);
        $main->setAgent($agent)->setExtraData($data);
        $main->checkConf(new \Glpi\Inventory\Conf());
        $result = $main->prepare();
        $this->array($result)->hasSize(1);
        $this->array((array)$result[0])->isIdenticalTo([
            'autoupdatesystems_id' => 'GLPI Native Inventory',
            'last_inventory_update' => $date_now,
            "is_deleted" => 0,
            'firmware' => '8745213_951236',
            'ips' => ['10.59.29.176'],
            'mac' => '00:85:eb:f4:be:20',
            'manufacturer' => 'Canon',
            'model' => 'Canon MX 5970',
            'name' => 'MX5970',
            'serial' => 'SDFSDF9874',
            'type' => 'Printer',
            'uptime' => '8 days, 01:26:41.98',
            'printermodels_id' => 'Canon MX 5970',
            'printertypes_id' => 'Printer',
            'manufacturers_id' => 'Canon',
            'snmpcredentials_id' => 4,
            'have_usb' => 0,
            'have_ethernet' => 1,
            'memory_size' => 256,
            'last_pages_counter' => 800
        ]);

        //get no management port
        $this->array($main->getManagementPorts())->hasSize(0);

        //do real inventory to check dataDB
        $json_str = file_get_contents(self::INV_FIXTURES . 'printer_3.json');
        $json = json_decode($json_str);
        $this->doInventory($json);

        $printer = new \Printer();
        $this->boolean($printer->getFromDbByCrit(['name' => 'MX5970', 'serial' => 'SDFSDF9874']))->isTrue();

        //2 NetworkPort
        $np = new \NetworkPort();
        $this->integer(
            countElementsInTable(
                $np::getTable(),
                [['itemtype' => 'Printer', 'items_id' => $printer->fields['id'] , 'instantiation_type' => 'NetworkPortEthernet']]
            )
        )->isIdenticalTo(2);

        //0 NetworkPortAggregate
        $this->boolean($np->getFromDbByCrit(['itemtype' => 'Printer', 'items_id' => $printer->fields['id'] , 'instantiation_type' => 'NetworkPortAggregate']))->isFalse();

        //remove printer for other test
        $printer->delete($printer->fields);
    }

    public function testInventoryMove()
    {
        global $DB;

        $printer = new \Printer();
        $item_printer = new \Computer_Item();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <PRINTERS>
      <DRIVER>HP Color LaserJet Pro MFP M476 PCL 6</DRIVER>
      <NAME>HP Color LaserJet Pro MFP M476 PCL 6</NAME>
      <NETWORK>0</NETWORK>
      <PORT>10.253.6.117</PORT>
      <PRINTPROCESSOR>hpcpp155</PRINTPROCESSOR>
      <RESOLUTION>600x600</RESOLUTION>
      <SHARED>0</SHARED>
      <SHARENAME>HP Color LaserJet Pro MFP M476 PCL 6  (1)</SHARENAME>
      <STATUS>Unknown</STATUS>
      <SERIAL>abcdef</SERIAL>
    </PRINTERS>
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

        //we have 1 printer
        $printers = $printer->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($printers))->isIdenticalTo(1);

        //we have 1 printer items linked to the computer
        $printers = $item_printer->find(['itemtype' => 'printer', 'computers_id' => $computers_id]);
        $this->integer(count($printers))->isIdenticalTo(1);

        //printer present in the inventory source is dynamic
        $printers = $item_printer->find(['itemtype' => 'printer', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($printers))->isIdenticalTo(1);

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

        //we still have only 1 printer
        $printers = $printer->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($printers))->isIdenticalTo(1);

        //we still have only 1 printer items linked to the computer
        $printers = $item_printer->find(['itemtype' => 'printer', 'computers_id' => $computers_id]);
        $this->integer(count($printers))->isIdenticalTo(1);

        //same printer, but on another computer
        $xml_source_2 = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <PRINTERS>
      <DRIVER>HP Color LaserJet Pro MFP M476 PCL 6</DRIVER>
      <NAME>HP Color LaserJet Pro MFP M476 PCL 6</NAME>
      <NETWORK>0</NETWORK>
      <PORT>10.253.6.117</PORT>
      <PRINTPROCESSOR>hpcpp155</PRINTPROCESSOR>
      <RESOLUTION>600x600</RESOLUTION>
      <SHARED>0</SHARED>
      <SHARENAME>HP Color LaserJet Pro MFP M476 PCL 6  (1)</SHARENAME>
      <STATUS>Unknown</STATUS>
      <SERIAL>abcdef</SERIAL>
    </PRINTERS>
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

        //computer inventory with one printer
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

        //we still have only 1 printer
        $printers = $printer->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($printers))->isIdenticalTo(1);

        //no longer linked on first computer inventoried
        $printers = $item_printer->find(['itemtype' => 'printer', 'computers_id' => $computers_id]);
        $this->integer(count($printers))->isIdenticalTo(0);

        //but now linked on last inventoried computer
        $printers = $item_printer->find(['itemtype' => 'printer', 'computers_id' => $computers_2_id]);
        $this->integer(count($printers))->isIdenticalTo(1);

        //printer is still dynamic
        $printers = $item_printer->find(['itemtype' => 'printer', 'computers_id' => $computers_2_id, 'is_dynamic' => 1]);
        $this->integer(count($printers))->isIdenticalTo(1);

        //replay first computer inventory, printer is back \o/
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

        //we still have only 1 printer
        $printers = $printer->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($printers))->isIdenticalTo(1);

        //linked again on first computer inventoried
        $printers = $item_printer->find(['itemtype' => 'printer', 'computers_id' => $computers_id]);
        $this->integer(count($printers))->isIdenticalTo(1);

        //no longer linked on last inventoried computer
        $printers = $item_printer->find(['itemtype' => 'printer', 'computers_id' => $computers_2_id]);
        $this->integer(count($printers))->isIdenticalTo(0);

        //printer is still dynamic
        $printers = $item_printer->find(['itemtype' => 'printer', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($printers))->isIdenticalTo(1);
    }

    public function testInventoryNoMove()
    {
        global $DB;

        $printer = new \Printer();
        $item_printer = new \Computer_Item();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <PRINTERS>
      <DRIVER>HP Color LaserJet Pro MFP M476 PCL 6</DRIVER>
      <NAME>HP Color LaserJet Pro MFP M476 PCL 6</NAME>
      <NETWORK>0</NETWORK>
      <PORT>10.253.6.117</PORT>
      <PRINTPROCESSOR>hpcpp155</PRINTPROCESSOR>
      <RESOLUTION>600x600</RESOLUTION>
      <SHARED>0</SHARED>
      <SHARENAME>HP Color LaserJet Pro MFP M476 PCL 6  (1)</SHARENAME>
      <STATUS>Unknown</STATUS>
      <SERIAL>abcdef</SERIAL>
    </PRINTERS>
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

        //we have 1 printer items linked to the computer
        $printers = $item_printer->find(['itemtype' => 'printer', 'computers_id' => $computers_id]);
        $this->integer(count($printers))->isIdenticalTo(1);

        //set to global management
        $this->boolean($printer->getFromDB(current($printers)['items_id']));
        $this->boolean($printer->update(['id' => $printer->fields['id'], 'is_global' => \Config::GLOBAL_MANAGEMENT]));

        //same printer, but on another computer
        $xml_source_2 = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <PRINTERS>
      <DRIVER>HP Color LaserJet Pro MFP M476 PCL 6</DRIVER>
      <NAME>HP Color LaserJet Pro MFP M476 PCL 6</NAME>
      <NETWORK>0</NETWORK>
      <PORT>10.253.6.117</PORT>
      <PRINTPROCESSOR>hpcpp155</PRINTPROCESSOR>
      <RESOLUTION>600x600</RESOLUTION>
      <SHARED>0</SHARED>
      <SHARENAME>HP Color LaserJet Pro MFP M476 PCL 6  (1)</SHARENAME>
      <STATUS>Unknown</STATUS>
      <SERIAL>abcdef</SERIAL>
    </PRINTERS>
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

        //computer inventory with one printer
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

        //we still have only 1 printer
        $printers = $printer->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($printers))->isIdenticalTo(1);

        //still linked on first computer inventoried
        $printers = $item_printer->find(['itemtype' => 'printer', 'computers_id' => $computers_id]);
        $this->integer(count($printers))->isIdenticalTo(1);

        //also linked on last inventoried computer
        $printers = $item_printer->find(['itemtype' => 'printer', 'computers_id' => $computers_2_id]);
        $this->integer(count($printers))->isIdenticalTo(1);
    }

    public function testInventoryGlobalManagement()
    {
        global $DB;

        $printer = new \Printer();
        $item_printer = new \Computer_Item();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <PRINTERS>
      <DRIVER>HP Color LaserJet Pro MFP M476 PCL 6</DRIVER>
      <NAME>HP Color LaserJet Pro MFP M476 PCL 6</NAME>
      <NETWORK>0</NETWORK>
      <PORT>10.253.6.117</PORT>
      <PRINTPROCESSOR>hpcpp155</PRINTPROCESSOR>
      <RESOLUTION>600x600</RESOLUTION>
      <SHARED>0</SHARED>
      <SHARENAME>HP Color LaserJet Pro MFP M476 PCL 6  (1)</SHARENAME>
      <STATUS>Unknown</STATUS>
      <SERIAL>abcdef</SERIAL>
    </PRINTERS>
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
        \Config::setConfigurationValues('core', ['printers_management_restrict' => \Config::GLOBAL_MANAGEMENT]);
        $this->logout();

        //computer inventory with one printer
        $inventory = $this->doInventory($xml_source, true);

        //restore default configuration
        $this->login();
        \Config::setConfigurationValues('core', ['printers_management_restrict' => \Config::NO_MANAGEMENT]);
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

        //we have 1 printer items linked to the computer
        $printers = $item_printer->find(['itemtype' => 'printer', 'computers_id' => $computers_id]);
        $this->integer(count($printers))->isIdenticalTo(1);

        $this->boolean($printer->getFromDB(current($printers)['items_id']));
        $this->boolean($printer->update(['id' => $printer->fields['id'], 'is_global' => 1]));

        //same printer, but on another computer
        $xml_source_2 = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <PRINTERS>
      <DRIVER>HP Color LaserJet Pro MFP M476 PCL 6</DRIVER>
      <NAME>HP Color LaserJet Pro MFP M476 PCL 6</NAME>
      <NETWORK>0</NETWORK>
      <PORT>10.253.6.117</PORT>
      <PRINTPROCESSOR>hpcpp155</PRINTPROCESSOR>
      <RESOLUTION>600x600</RESOLUTION>
      <SHARED>0</SHARED>
      <SHARENAME>HP Color LaserJet Pro MFP M476 PCL 6  (1)</SHARENAME>
      <STATUS>Unknown</STATUS>
      <SERIAL>abcdef</SERIAL>
    </PRINTERS>
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
        \Config::setConfigurationValues('core', ['printers_management_restrict' => \Config::GLOBAL_MANAGEMENT]);
        $this->logout();

        //computer inventory with one printer
        $inventory = $this->doInventory($xml_source_2, true);

        //restore default configuration
        $this->login();
        \Config::setConfigurationValues('core', ['printers_management_restrict' => \Config::NO_MANAGEMENT]);
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

        //we still have only 1 printer
        $printers = $printer->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($printers))->isIdenticalTo(1);

        //still linked on first computer inventoried
        $printers = $item_printer->find(['itemtype' => 'printer', 'computers_id' => $computers_id]);
        $this->integer(count($printers))->isIdenticalTo(1);

        //also linked on last inventoried computer
        $printers = $item_printer->find(['itemtype' => 'printer', 'computers_id' => $computers_2_id]);
        $this->integer(count($printers))->isIdenticalTo(1);
    }

    public function testInventoryUnitManagement()
    {
        global $DB;

        $printer = new \Printer();
        $item_printer = new \Computer_Item();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <PRINTERS>
      <DRIVER>HP Color LaserJet Pro MFP M476 PCL 6</DRIVER>
      <NAME>HP Color LaserJet Pro MFP M476 PCL 6</NAME>
      <NETWORK>0</NETWORK>
      <PORT>10.253.6.117</PORT>
      <PRINTPROCESSOR>hpcpp155</PRINTPROCESSOR>
      <RESOLUTION>600x600</RESOLUTION>
      <SHARED>0</SHARED>
      <SHARENAME>HP Color LaserJet Pro MFP M476 PCL 6  (1)</SHARENAME>
      <STATUS>Unknown</STATUS>
      <SERIAL>abcdef</SERIAL>
    </PRINTERS>
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
        \Config::setConfigurationValues('core', ['printers_management_restrict' => \Config::UNIT_MANAGEMENT]);
        $this->logout();

        //computer inventory with one printer
        $inventory = $this->doInventory($xml_source, true);

        //restore default configuration
        $this->login();
        \Config::setConfigurationValues('core', ['printers_management_restrict' => \Config::NO_MANAGEMENT]);
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

        //we have 1 printer
        $printers = $printer->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($printers))->isIdenticalTo(1);

        //we have 1 printer items linked to the computer
        $printers = $item_printer->find(['itemtype' => 'Printer', 'computers_id' => $computers_id]);
        $this->integer(count($printers))->isIdenticalTo(1);

        //printer present in the inventory source is dynamic
        $printers = $item_printer->find(['itemtype' => 'Printer', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($printers))->isIdenticalTo(1);

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

        //we still have only 1 printer
        $printers = $printer->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($printers))->isIdenticalTo(1);

        //we still have only 1 printer items linked to the computer
        $printers = $item_printer->find(['itemtype' => 'Printer', 'computers_id' => $computers_id]);
        $this->integer(count($printers))->isIdenticalTo(1);

        //set to global management
        $this->boolean($printer->getFromDB(current($printers)['items_id']));
        $this->boolean($printer->update(['id' => $printer->fields['id'], 'is_global' => \Config::GLOBAL_MANAGEMENT]));

        //same printer, but on another computer
        $xml_source_2 = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <PRINTERS>
      <DRIVER>HP Color LaserJet Pro MFP M476 PCL 6</DRIVER>
      <NAME>HP Color LaserJet Pro MFP M476 PCL 6</NAME>
      <NETWORK>0</NETWORK>
      <PORT>10.253.6.117</PORT>
      <PRINTPROCESSOR>hpcpp155</PRINTPROCESSOR>
      <RESOLUTION>600x600</RESOLUTION>
      <SHARED>0</SHARED>
      <SHARENAME>HP Color LaserJet Pro MFP M476 PCL 6  (1)</SHARENAME>
      <STATUS>Unknown</STATUS>
      <SERIAL>abcdef</SERIAL>
    </PRINTERS>
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
        \Config::setConfigurationValues('core', ['printers_management_restrict' => \Config::UNIT_MANAGEMENT]);
        $this->logout();

        //computer inventory with one printer
        $inventory = $this->doInventory($xml_source_2, true);

        //restore default configuration
        $this->login();
        \Config::setConfigurationValues('core', ['printers_management_restrict' => \Config::NO_MANAGEMENT]);
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

        //we still have only 1 printer
        $printers = $printer->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($printers))->isIdenticalTo(1);

        //no longer linked on first computer inventoried
        $printers = $item_printer->find(['itemtype' => 'Printer', 'computers_id' => $computers_id]);
        $this->integer(count($printers))->isIdenticalTo(0);

        //but now linked on last inventoried computer
        $printers = $item_printer->find(['itemtype' => 'Printer', 'computers_id' => $computers_2_id]);
        $this->integer(count($printers))->isIdenticalTo(1);

        //printer is still dynamic
        $printers = $item_printer->find(['itemtype' => 'Printer', 'computers_id' => $computers_2_id, 'is_dynamic' => 1]);
        $this->integer(count($printers))->isIdenticalTo(1);

        //change default configuration to unit management
        $this->login();
        \Config::setConfigurationValues('core', ['printers_management_restrict' => \Config::UNIT_MANAGEMENT]);
        $this->logout();

        //replay first computer inventory, printer is back \o/
        $this->doInventory($xml_source, true);

        //restore default configuration
        $this->login();
        \Config::setConfigurationValues('core', ['printers_management_restrict' => \Config::NO_MANAGEMENT]);
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

        //we still have only 1 printer
        $printers = $printer->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($printers))->isIdenticalTo(1);

        //linked again on first computer inventoried
        $printers = $item_printer->find(['itemtype' => 'Printer', 'computers_id' => $computers_id]);
        $this->integer(count($printers))->isIdenticalTo(1);

        //no longer linked on last inventoried computer
        $printers = $item_printer->find(['itemtype' => 'Printer', 'computers_id' => $computers_2_id]);
        $this->integer(count($printers))->isIdenticalTo(0);

        //printer is still dynamic
        $printers = $item_printer->find(['itemtype' => 'Printer', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($printers))->isIdenticalTo(1);
    }

    public function testPrinterIgnoreImport()
    {
        global $DB;
        $printer = new \Printer();

       // Add dictionary rule for ignore import for printer "HP Deskjet 2540"
        $rulecollection = new \RuleDictionnaryPrinterCollection();
        $rule = $rulecollection->getRuleClass();
        $rule_id = $rule->add([
            'is_active' => 1,
            'name' => 'Ignore import',
            'match' => 'AND',
            'sub_type' => 'RuleDictionnaryPrinter',
            'ranking' => 1
        ]);
        $this->integer($rule_id)->isGreaterThan(0);

       // Add criteria
        $rulecriteria = new \RuleCriteria(get_class($rule));
        $this->integer(
            $rulecriteria->add([
                'rules_id' => $rule_id,
                'criteria' => "name",
                'pattern' => 'HP Deskjet 2540',
                'condition' => 0
            ])
        )->isGreaterThan(0);

       // Add action
        $ruleaction = new \RuleAction(get_class($rule));
        $this->integer(
            $ruleaction->add([
                'rules_id' => $rule_id,
                'action_type' => 'assign',
                'field' => '_ignore_import',
                'value' => '1'
            ])
        )->isGreaterThan(0);

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <PRINTERS>
      <DRIVER>HP Color LaserJet Pro MFP M476 PCL 6</DRIVER>
      <NAME>HP Color LaserJet Pro MFP M476 PCL 6</NAME>
      <NETWORK>0</NETWORK>
      <PORT>10.253.6.117</PORT>
      <PRINTPROCESSOR>hpcpp155</PRINTPROCESSOR>
      <RESOLUTION>600x600</RESOLUTION>
      <SHARED>0</SHARED>
      <SHARENAME>HP Color LaserJet Pro MFP M476 PCL 6  (1)</SHARENAME>
      <STATUS>Unknown</STATUS>
      <SERIAL>abcdef</SERIAL>
    </PRINTERS>
    <PRINTERS>
      <DRIVER>HP Deskjet 2540</DRIVER>
      <NAME>HP Deskjet 2540</NAME>
      <SERIAL>azerty</SERIAL>
    </PRINTERS>
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

       //computer inventory with two printers, "HP Deskjet 2540" ignored by rules
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

        $item_printer = new \Computer_Item();
        $printers = $item_printer->find(['computers_id' => $inventory->getItem()->fields['id'], 'itemtype' => 'Printer']);
        $this->integer(count($printers))->isIdenticalTo(1);

        $this->boolean($printer->getFromDB(current($printers)['items_id']))->isTrue();
        $this->string($printer->fields['name'])->isIdenticalTo('HP Color LaserJet Pro MFP M476 PCL 6');
    }

    public function testPrinterRenamedImport()
    {
        global $DB;

        $computer = new \Computer();
        $printer = new \Printer();

        $manufacturer = new \Manufacturer();
        $this->integer($manufacturer->add(['name' => 'HP inc.']))->isGreaterThan(0);

        $rulecollection = new \RuleDictionnaryPrinterCollection();
        $rule = $rulecollection->getRuleClass();
        $rule_id = $rule->add([
            'is_active' => 1,
            'name' => 'rename',
            'match' => 'AND',
            'sub_type' => 'RuleDictionnaryPrinter',
            'ranking' => 2
        ]);
        $this->integer($rule_id)->isGreaterThan(0);

        // Add criteria
        $rule = $rulecollection->getRuleClass();
        $rulecriteria = new \RuleCriteria(get_class($rule));
        $this->integer(
            $rulecriteria->add([
                'rules_id' => $rule_id,
                'criteria' => "name",
                'pattern' => 'HP Deskjet 2540',
                'condition' => 0
            ])
        )->isGreaterThan(0);

        // Add action
        $ruleaction = new \RuleAction(get_class($rule));
        $this->integer(
            $ruleaction->add([
                'rules_id' => $rule_id,
                'action_type' => 'assign',
                'field' => 'name',
                'value' => 'HP Deskjet 2540 - renamed'
            ])
        )->isGreaterThan(0);

        // Add action
        $ruleaction = new \RuleAction(get_class($rule));
        $this->integer(
            $ruleaction->add([
                'rules_id' => $rule_id,
                'action_type' => 'assign',
                'field' => 'manufacturer',
                'value' => $manufacturer->fields['id']
            ])
        )->isGreaterThan(0);

        // Add action
        $ruleaction = new \RuleAction(get_class($rule));
        $ruleaction->add([
            'rules_id' => $rule_id,
            'action_type' => 'assign',
            'field' => 'is_global',
            'value' => '0'
        ]);

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <PRINTERS>
      <DRIVER>HP Color LaserJet Pro MFP M476 PCL 6</DRIVER>
      <NAME>HP Color LaserJet Pro MFP M476 PCL 6</NAME>
      <NETWORK>0</NETWORK>
      <PORT>10.253.6.117</PORT>
      <PRINTPROCESSOR>hpcpp155</PRINTPROCESSOR>
      <RESOLUTION>600x600</RESOLUTION>
      <SHARED>0</SHARED>
      <SHARENAME>HP Color LaserJet Pro MFP M476 PCL 6  (1)</SHARENAME>
      <STATUS>Unknown</STATUS>
      <SERIAL>abcdef</SERIAL>
    </PRINTERS>
    <PRINTERS>
      <DRIVER>HP Deskjet 2540</DRIVER>
      <NAME>HP Deskjet 2540</NAME>
      <SERIAL>azerty</SERIAL>
    </PRINTERS>
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

        //computer inventory with two printers, "HP Deskjet 2540" renamed by rules
        $inventory = $this->doInventory($xml_source, true);

        //check for expected logs
        $nblogsnow = countElementsInTable(\Log::getTable());
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'LIMIT' => $nblogsnow,
            'OFFSET' => $this->nblogs,
            'WHERE' => [
                'NOT' => [
                    'itemtype' => [
                        \Config::class,
                        'RuleAction',
                        'RuleDictionnaryPrinter'
                    ]
                ]
            ]
        ]);
        $this->integer(count($logs))->isIdenticalTo(0);

        $computer->getFromDBByCrit(['serial' => 'ggheb7ne7']);

        $item_printer = new \Computer_Item();
        $printers = $item_printer->find(['computers_id' => $inventory->getItem()->fields['id'], 'itemtype' => 'Printer']);
        $this->integer(count($printers))->isIdenticalTo(2);

        $printer1 = array_pop($printers);
        $this->boolean($printer->getFromDB($printer1['items_id']))->isTrue();
        $this->string($printer->fields['name'])->isIdenticalTo('HP Deskjet 2540 - renamed');
        $this->integer($printer->fields['manufacturers_id'])->isIdenticalTo($manufacturer->fields['id']);
        $this->integer($printer->fields['is_global'])->isIdenticalTo(0);

        $printer2 = array_pop($printers);
        $this->boolean($printer->getFromDB($printer2['items_id']))->isTrue();
        $this->string($printer->fields['name'])->isIdenticalTo('HP Color LaserJet Pro MFP M476 PCL 6');
    }

    public function testInventoryImportOrNot()
    {
        global $DB;

        $printer = new \Printer();
        $item_printer = new \Computer_Item();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <PRINTERS>
      <DRIVER>HP Color LaserJet Pro MFP M476 PCL 6</DRIVER>
      <NAME>HP Color LaserJet Pro MFP M476 PCL 6</NAME>
      <NETWORK>0</NETWORK>
      <PORT>10.253.6.117</PORT>
      <PRINTPROCESSOR>hpcpp155</PRINTPROCESSOR>
      <RESOLUTION>600x600</RESOLUTION>
      <SHARED>0</SHARED>
      <SHARENAME>HP Color LaserJet Pro MFP M476 PCL 6  (1)</SHARENAME>
      <STATUS>Unknown</STATUS>
      <SERIAL>abcdef</SERIAL>
    </PRINTERS>
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

        //per default, configuration allows printer import. change that.
        $this->login();
        $conf = new \Glpi\Inventory\Conf();
        $this->boolean(
            $conf->saveConf([
                'import_printer' => 0
            ])
        )->isTrue();
        $this->logout();

        //computer inventory with one printer
        $inventory = $this->doInventory($xml_source, true);

        //restore default configuration
        $this->login();
        $this->boolean(
            $conf->saveConf([
                'import_printer' => 1
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

        //no printer linked to the computer
        $printers = $printer->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($printers))->isIdenticalTo(0);

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

        //we now have 1 printer
        $printers = $printer->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($printers))->isIdenticalTo(1);

        //we have 1 printer items linked to the computer
        $printers = $item_printer->find(['itemtype' => 'printer', 'computers_id' => $computers_id]);
        $this->integer(count($printers))->isIdenticalTo(1);

        //printer present in the inventory source is dynamic
        $printers = $item_printer->find(['itemtype' => 'printer', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($printers))->isIdenticalTo(1);
    }

    public function testSnmpPrinterDiscoveryUpdateAllowed()
    {
        //first step do a standard discovery
        $date_now = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date_now;
        $json_str = file_get_contents(self::INV_FIXTURES . 'printer_1.json');

        $json = json_decode($json_str);

        $printer = new \Printer();

        $data = (array)$json->content;
        $inventory = new \Glpi\Inventory\Inventory();
        $this->boolean($inventory->setData($json))->isTrue();

        $agent = new \Agent();
        $this->integer($agent->handleAgent($inventory->extractMetadata()))->isIdenticalTo(0);

        $main = new \Glpi\Inventory\Asset\Printer($printer, $json);
        $main->setAgent($agent)->setExtraData($data);
        $main->checkConf(new \Glpi\Inventory\Conf());
        $main->setDiscovery(true);
        $result = $main->prepare();
        $this->array($result)->hasSize(1);
        $this->array((array)$result[0])->isIdenticalTo([
            'autoupdatesystems_id' => 'GLPI Native Inventory',
            'last_inventory_update' => $date_now,
            "is_deleted" => 0,
            'firmware' => '2409048_052887',
            'ips' => ['10.59.29.175'],
            'mac' => '00:68:eb:f2:be:10',
            'manufacturer' => 'Hewlett-Packard',
            'model' => 'HP LaserJet M507',
            'name' => 'NPIF2BE10',
            'serial' => 'PHCVN191TG',
            'type' => 'Printer',
            'uptime' => '7 days, 01:26:41.98',
            'printermodels_id' => 'HP LaserJet M507',
            'printertypes_id' => 'Printer',
            'manufacturers_id' => 'Hewlett-Packard',
            'snmpcredentials_id' => 4,
            'have_usb' => 0,
            'have_ethernet' => 1,
            'memory_size' => 512,
            'last_pages_counter' => 1802,
        ]);


        $this->array($main->getNetworkPorts())->isIdenticalTo([]);
        //Since this -> https://github.com/glpi-project/glpi/pull/12197
        //management port is not created if is known from port list of printer.
        $this->array($main->getManagementPorts())->hasSize(0);


        $main->handle();
        $inventory->doInventory();

        //check data
        $this->boolean($printer->getFromDB($printer->fields['id']))->isTrue();
        $this->integer($printer->fields['last_pages_counter'])->isIdenticalTo(1802);
        $this->string($printer->fields['name'])->isIdenticalTo('NPIF2BE10');

        //second step do a standard discovery but change IP to update last page counter
        //GLPI allows the update
        $json = json_decode($json_str);

        //update inventory data
        $new_ip = "10.59.29.180";
        $new_name = 'BE10NPIF2';
        $json->content->network_device->name = $new_name;
        $json->content->network_device->ips[0] = $new_ip;
        $json->content->network_ports[1]->ips = [$new_ip];

        $printer = new \Printer();

        $printer = new \Printer();
        $data = (array)$json->content;

        $inventory = new \Glpi\Inventory\Inventory();
        $this->boolean($inventory->setData($json))->isTrue();

        $agent = new \Agent();
        $this->integer($agent->handleAgent($inventory->extractMetadata()))->isIdenticalTo(0);

        $main = new \Glpi\Inventory\Asset\Printer($printer, $json);
        $main->setAgent($agent)->setExtraData($data);
        $main->checkConf(new \Glpi\Inventory\Conf());
        $main->setDiscovery(true);
        $result = $main->prepare();
        $this->array($result)->hasSize(1);
        $this->array((array)$result[0])->isIdenticalTo([
            'autoupdatesystems_id' => 'GLPI Native Inventory',
            'last_inventory_update' => $date_now,
            "is_deleted" => 0,
            'firmware' => '2409048_052887',
            'ips' => [$new_ip],
            'mac' => '00:68:eb:f2:be:10',
            'manufacturer' => 'Hewlett-Packard',
            'model' => 'HP LaserJet M507',
            'name' => $new_name,
            'serial' => 'PHCVN191TG',
            'type' => 'Printer',
            'uptime' => '7 days, 01:26:41.98',
            'printermodels_id' => 'HP LaserJet M507',
            'printertypes_id' => 'Printer',
            'manufacturers_id' => 'Hewlett-Packard',
            'snmpcredentials_id' => 4,
            'have_usb' => 0,
            'have_ethernet' => 1,
            'memory_size' => 512,
            'last_pages_counter' => 1802,
        ]);

        $this->array($main->getNetworkPorts())->isIdenticalTo([]);
        //Since this -> https://github.com/glpi-project/glpi/pull/12197
        //management port is not created if is known from port list of printer.
        $this->array($main->getManagementPorts())->hasSize(0);

        $main->handle();
        $inventory->doInventory();

        $this->boolean($printer->getFromDB($printer->fields['id']))->isTrue();
        $this->string($printer->fields['name'])->isIdenticalTo($new_name);
    }

    public function testSnmpPrinterManagementPortCleaned()
    {
        /**
         * This check if management port is well cleaned
         */
        $date_now = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date_now;
        $json_str = '{
          "content": {
              "cartridges": [
                  {
                      "tonerblack": "71"
                  }
              ],
              "firmwares": [
                  {
                      "date": "2019-09-16",
                      "description": "device firmware",
                      "manufacturer": "Hewlett-Packard",
                      "name": "CANON MP5353",
                      "type": "device",
                      "version": "2409048_052887"
                  }
              ],
              "network_device": {
                  "firmware": "2409048_052887",
                  "ips": [
                       "10.59.29.208",
                       "0.0.0.0",
                       "127.0.0.1"
                  ],
                  "mac": "00:68:eb:f2:be:10",
                  "manufacturer": "Hewlett-Packard",
                  "model": "CANON MP5353",
                  "name": "NPIF2BE10",
                  "ram": 512,
                  "serial": "PHCVN191TG",
                  "type": "Printer",
                  "uptime": "7 days, 01:26:41.98",
                  "credentials": 4
              },
              "pagecounters": {
                  "rectoverso": 831,
                  "total": 1802
              },
              "network_ports": [
                  {
                      "ifdescr": "CANON MP5353",
                      "ifinerrors": 0,
                      "ifinbytes": 0,
                      "ifinternalstatus": 1,
                      "iflastchange": "0.00 seconds",
                      "ifmtu": 1536,
                      "ifname": "CANON MP5353",
                      "ifnumber": 1,
                      "ifouterrors": 0,
                      "ifoutbytes": 0,
                      "ifspeed": 0,
                      "ifstatus": 1,
                      "iftype": 24
                  },
                  {
                      "ifdescr": "CANON MP5353",
                      "ifinerrors": 0,
                      "ifinbytes": 68906858,
                      "ifinternalstatus": 1,
                      "iflastchange": "0.00 seconds",
                      "ifmtu": 1500,
                      "ifname": "CANON MP5353",
                      "ifnumber": 2,
                      "ifouterrors": 0,
                      "ifoutbytes": 514488,
                      "ifspeed": 1000000000,
                      "ifstatus": 1,
                      "iftype": 6,
                      "ips": [
                          "10.59.29.175"
                      ],
                      "mac": "00:68:eb:f2:be:10"
                  }
              ],
              "versionclient": "missing"
          },
          "action": "netinventory",
          "deviceid": "NPIF2BE10-2020-12-31-11-28-51",
          "itemtype": "Printer"
       }';

        $json = json_decode($json_str);

        $printer = new \Printer();

        $data = (array)$json->content;
        $inventory = new \Glpi\Inventory\Inventory();
        $this->boolean($inventory->setData($json))->isTrue();

        $agent = new \Agent();
        $this->integer($agent->handleAgent($inventory->extractMetadata()))->isIdenticalTo(0);

        $main = new \Glpi\Inventory\Asset\Printer($printer, $json);
        $main->setAgent($agent)->setExtraData($data);
        $main->checkConf(new \Glpi\Inventory\Conf());
        $result = $main->prepare();
        $this->array($result)->hasSize(1);
        $this->array((array)$result[0])->isIdenticalTo([
            'autoupdatesystems_id' => 'GLPI Native Inventory',
            'last_inventory_update' => $date_now,
            "is_deleted" => 0,
            'firmware' => '2409048_052887',
            'ips' => ['10.59.29.208', '0.0.0.0', '127.0.0.1'],
            'mac' => '00:68:eb:f2:be:10',
            'manufacturer' => 'Hewlett-Packard',
            'model' => 'CANON MP5353',
            'name' => 'NPIF2BE10',
            'serial' => 'PHCVN191TG',
            'type' => 'Printer',
            'uptime' => '7 days, 01:26:41.98',
            'printermodels_id' => 'CANON MP5353',
            'printertypes_id' => 'Printer',
            'manufacturers_id' => 'Hewlett-Packard',
            'snmpcredentials_id' => 4,
            'have_usb' => 0,
            'have_ethernet' => 1,
            'memory_size' => 512,
            'last_pages_counter' => 1802
        ]);

        //get no management port
        $this->array($main->getManagementPorts())->hasSize(1);

        //do real inventory to check dataDB
        $json = json_decode($json_str);
        $this->doInventory($json);

        $printer = new \Printer();
        $this->boolean($printer->getFromDbByCrit(['name' => 'NPIF2BE10', 'serial' => 'PHCVN191TG']))->isTrue();

        //1 NetworkPort
        $np = new \NetworkPort();
        $this->integer(
            countElementsInTable(
                $np::getTable(),
                [['itemtype' => 'Printer', 'items_id' => $printer->fields['id'] , 'instantiation_type' => 'NetworkPortEthernet']]
            )
        )->isIdenticalTo(1);

        //1 NetworkPortAggregate
        $this->boolean($np->getFromDbByCrit(['itemtype' => 'Printer', 'items_id' => $printer->fields['id'] , 'instantiation_type' => 'NetworkPortAggregate']))->isTrue();

        //1 NetworkName form NetworkPortAggregate
        $nm = new \NetworkName();
        $this->boolean($nm->getFromDbByCrit(["itemtype" => "NetworkPort", "items_id" => $np->fields['id']]))->isTrue();

        //1 IPAdress form NetworkName
        $ip = new \IPAddress();
        $this->boolean($ip->getFromDbByCrit(["name" => "10.59.29.208", "itemtype" => "NetworkName", "items_id" => $nm->fields['id']]))->isTrue();

        //remove printer for other test
        $printer->delete($printer->fields);
    }


    public function testSnmpPrinterManagementPortNotRecreated()
    {
        /**
         * This check if management port is not recreated at each inventory (network discovery / network inventory)
         */
        $date_now = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date_now;
        $xml_source = '<?xml version="1.0" encoding="UTF-8"?>
        <REQUEST>
          <CONTENT>
            <DEVICE>
              <AUTHPORT />  <AUTHPROTOCOL />  <AUTHSNMP>1</AUTHSNMP>
              <DESCRIPTION>RICOH MP C5503 1.38 / RICOH Network Printer C model / RICOH Network Scanner C model / RICOH Network Facsimile C model</DESCRIPTION>
              <DNSHOSTNAME>10.100.51.207</DNSHOSTNAME>
              <IP>10.100.51.207</IP>
              <IPS>
                <IP>0.0.0.0</IP>
                <IP>10.100.51.207</IP>
                <IP>127.0.0.1</IP>
              </IPS>
              <LOCATION>Location</LOCATION>
              <MAC>00:26:73:12:34:56</MAC>
              <MANUFACTURER>Ricoh</MANUFACTURER>
              <MODEL>MP C5503</MODEL>
              <NETBIOSNAME>CLPSF99</NETBIOSNAME>
              <SERIAL>E1234567890</SERIAL>
              <SNMPHOSTNAME>CLPSF99</SNMPHOSTNAME>
              <TYPE>PRINTER</TYPE>
              <UPTIME>33 days, 01:29:11.00</UPTIME>
              <WORKGROUP>WORKGROUP</WORKGROUP>
            </DEVICE>
            <MODULEVERSION>5.1</MODULEVERSION>
            <PROCESSNUMBER>1</PROCESSNUMBER>
          </CONTENT>
          <DEVICEID>foo</DEVICEID>
          <QUERY>NETDISCOVERY</QUERY>
        </REQUEST>';



        $converter = new \Glpi\Inventory\Converter();
        $source = json_decode($converter->convert($xml_source));
        $inventory = new \Glpi\Inventory\Inventory($source);

        if ($inventory->inError()) {
            $this->dump($inventory->getErrors());
        }
        $this->boolean($inventory->inError())->isFalse();
        $this->array($inventory->getErrors())->isEmpty();

        //do a discovery
        $inventory->setDiscovery(true);
        $inventory->doInventory($xml_source, true);


        $printer = new \Printer();
        $this->boolean($printer->getFromDbByCrit(['name' => 'CLPSF99', 'serial' => 'E1234567890']))->isTrue();

        //1 NetworkPortAggregate
        $np_aggregate = new \NetworkPort();
        $this->boolean($np_aggregate->getFromDbByCrit(['itemtype' => 'Printer', 'items_id' => $printer->fields['id'] , 'instantiation_type' => 'NetworkPortAggregate']))->isTrue();


        //redo a discovery
        $inventory->setDiscovery(true);
        $inventory->doInventory($xml_source, true);

        $printer = new \Printer();
        $this->boolean($printer->getFromDbByCrit(['name' => 'CLPSF99', 'serial' => 'E1234567890']))->isTrue();

        //1 NetworkPortAggregate
        $np_aggregate_after_reimport = new \NetworkPort();
        $this->boolean($np_aggregate_after_reimport->getFromDbByCrit(['itemtype' => 'Printer', 'items_id' => $printer->fields['id'] , 'instantiation_type' => 'NetworkPortAggregate']))->isTrue();


        $this->integer($np_aggregate->fields['id'])->isEqualTo($np_aggregate_after_reimport->fields['id']);

        $xml_network_inventory = '<?xml version="1.0" encoding="UTF-8"?>
        <REQUEST>
          <CONTENT>
            <DEVICE>
              <CARTRIDGES>
                <TONERBLACK>20</TONERBLACK>
                <TONERCYAN>20</TONERCYAN>
                <TONERMAGENTA>40</TONERMAGENTA>
                <TONERYELLOW>20</TONERYELLOW>
                <WASTETONER>100</WASTETONER>
              </CARTRIDGES>
              <INFO>
                <COMMENTS>RICOH MP C5503 1.38 / RICOH Network Printer C model / RICOH Network Scanner C model / RICOH Network Facsimile C model</COMMENTS>
                <ID>1</ID>
                <IPS>
                  <IP>0.0.0.0</IP>
                  <IP>10.100.51.207</IP>
                  <IP>127.0.0.1</IP>
                </IPS>
                <LOCATION>Location</LOCATION>
                <MAC>00:26:73:12:34:56</MAC>
                <MANUFACTURER>Ricoh</MANUFACTURER>
                <MEMORY>1</MEMORY>
                <MODEL>MP C5503</MODEL>
                <NAME>CLPSF99</NAME>
                <RAM>1973</RAM>
                <SERIAL>E1234567890</SERIAL>
                <TYPE>PRINTER</TYPE>
                <UPTIME>33 days, 22:19:01.00</UPTIME>
              </INFO>
              <PAGECOUNTERS>
                <TOTAL>1164615</TOTAL>
              </PAGECOUNTERS>
              <PORTS>
                <PORT>
                  <IFDESCR>ncmac0</IFDESCR>
                  <IFINERRORS>0</IFINERRORS>
                  <IFINOCTETS>2656604236</IFINOCTETS>
                  <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
                  <IFLASTCHANGE>33 days, 22:19:01.00</IFLASTCHANGE>
                  <IFMTU>1500</IFMTU>
                  <IFNAME>ncmac0</IFNAME>
                  <IFNUMBER>1</IFNUMBER>
                  <IFOUTERRORS>0</IFOUTERRORS>
                  <IFOUTOCTETS>1271117255</IFOUTOCTETS>
                  <IFSPEED>100000000</IFSPEED>
                  <IFSTATUS>1</IFSTATUS>
                  <IFTYPE>6</IFTYPE>
                  <IP>10.100.51.207</IP>
                  <IPS>
                    <IP>10.100.51.207</IP>
                  </IPS>
                  <MAC>00:26:73:12:34:56</MAC>
                </PORT>
                <PORT>
                  <IFDESCR>lo0</IFDESCR>
                  <IFINERRORS>0</IFINERRORS>
                  <IFINOCTETS>232223048</IFINOCTETS>
                  <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
                  <IFLASTCHANGE>0.00 seconds</IFLASTCHANGE>
                  <IFMTU>33196</IFMTU>
                  <IFNAME>lo0</IFNAME>
                  <IFNUMBER>2</IFNUMBER>
                  <IFOUTERRORS>0</IFOUTERRORS>
                  <IFOUTOCTETS>232223048</IFOUTOCTETS>
                  <IFSPEED>0</IFSPEED>
                  <IFSTATUS>1</IFSTATUS>
                  <IFTYPE>24</IFTYPE>
                  <IP>127.0.0.1</IP>
                  <IPS>
                    <IP>127.0.0.1</IP>
                  </IPS>
                </PORT>
                <PORT>
                  <IFDESCR>ppp0</IFDESCR>
                  <IFINERRORS>0</IFINERRORS>
                  <IFINOCTETS>0</IFINOCTETS>
                  <IFINTERNALSTATUS>2</IFINTERNALSTATUS>
                  <IFLASTCHANGE>0.00 seconds</IFLASTCHANGE>
                  <IFMTU>1500</IFMTU>
                  <IFNAME>ppp0</IFNAME>
                  <IFNUMBER>3</IFNUMBER>
                  <IFOUTERRORS>0</IFOUTERRORS>
                  <IFOUTOCTETS>0</IFOUTOCTETS>
                  <IFSPEED>0</IFSPEED>
                  <IFSTATUS>2</IFSTATUS>
                  <IFTYPE>1</IFTYPE>
                  <IP>0.0.0.0</IP>
                  <IPS>
                    <IP>0.0.0.0</IP>
                  </IPS>
                </PORT>
              </PORTS>
            </DEVICE>
            <MODULEVERSION>5.1</MODULEVERSION>
            <PROCESSNUMBER>7</PROCESSNUMBER>
          </CONTENT>
          <DEVICEID>foo</DEVICEID>
          <QUERY>SNMPQUERY</QUERY>
        </REQUEST>
        ';

        //do a network inventory
        $inventory->setDiscovery(false);
        $inventory->doInventory($xml_network_inventory, true);


        $printer = new \Printer();
        $this->boolean($printer->getFromDbByCrit(['name' => 'CLPSF99', 'serial' => 'E1234567890']))->isTrue();

        //1 NetworkPortAggregate
        $np_aggregate_after_networkinventory = new \NetworkPort();
        $this->boolean($np_aggregate_after_networkinventory->getFromDbByCrit(['itemtype' => 'Printer', 'items_id' => $printer->fields['id'] , 'instantiation_type' => 'NetworkPortAggregate']))->isTrue();


        $this->integer($np_aggregate_after_reimport->fields['id'])->isEqualTo($np_aggregate_after_networkinventory->fields['id']);

        //redo an discovery
        $inventory->setDiscovery(true);
        $inventory->doInventory($xml_source, true);

        $printer = new \Printer();
        $this->boolean($printer->getFromDbByCrit(['name' => 'CLPSF99', 'serial' => 'E1234567890']))->isTrue();

        //1 NetworkPortAggregate
        $np_aggregate_after_reimport = new \NetworkPort();
        $this->boolean($np_aggregate_after_reimport->getFromDbByCrit(['itemtype' => 'Printer', 'items_id' => $printer->fields['id'] , 'instantiation_type' => 'NetworkPortAggregate']))->isTrue();


        $this->integer($np_aggregate_after_reimport->fields['id'])->isEqualTo($np_aggregate_after_reimport->fields['id']);

        //remove printer for other test
        $printer->delete($printer->fields);
    }

    public function testSnmpPrinterNetworkPortNotRecreated()
    {

        $xml_source = '<?xml version="1.0" encoding="UTF-8"?>
        <REQUEST>
          <CONTENT>
            <DEVICE>
              <CARTRIDGES>
                <TONERBLACK>20</TONERBLACK>
                <TONERCYAN>20</TONERCYAN>
                <TONERMAGENTA>40</TONERMAGENTA>
                <TONERYELLOW>20</TONERYELLOW>
                <WASTETONER>100</WASTETONER>
              </CARTRIDGES>
              <INFO>
                <COMMENTS>RICOH MP C5503 1.38 / RICOH Network Printer C model / RICOH Network Scanner C model / RICOH Network Facsimile C model</COMMENTS>
                <ID>1</ID>
                <IPS>
                  <IP>10.100.51.207</IP>
                </IPS>
                <LOCATION>Location</LOCATION>
                <MAC>00:26:73:12:34:56</MAC>
                <MANUFACTURER>Ricoh</MANUFACTURER>
                <MEMORY>1</MEMORY>
                <MODEL>MP C5503</MODEL>
                <NAME>CLPSF99</NAME>
                <RAM>1973</RAM>
                <SERIAL>E1234567890</SERIAL>
                <TYPE>PRINTER</TYPE>
                <UPTIME>33 days, 22:19:01.00</UPTIME>
              </INFO>
              <PAGECOUNTERS>
                <TOTAL>1164615</TOTAL>
              </PAGECOUNTERS>
              <PORTS>
                <PORT>
                  <IFDESCR>ncmac0</IFDESCR>
                  <IFINERRORS>0</IFINERRORS>
                  <IFINOCTETS>2656604236</IFINOCTETS>
                  <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
                  <IFLASTCHANGE>33 days, 22:19:01.00</IFLASTCHANGE>
                  <IFMTU>1500</IFMTU>
                  <IFNAME>ncmac0</IFNAME>
                  <IFNUMBER>1</IFNUMBER>
                  <IFOUTERRORS>0</IFOUTERRORS>
                  <IFOUTOCTETS>1271117255</IFOUTOCTETS>
                  <IFSPEED>100000000</IFSPEED>
                  <IFSTATUS>1</IFSTATUS>
                  <IFTYPE>6</IFTYPE>
                  <IP>10.100.51.207</IP>
                  <IPS>
                    <IP>10.100.51.207</IP>
                  </IPS>
                  <MAC>00:26:73:12:34:56</MAC>
                </PORT>
              </PORTS>
            </DEVICE>
            <MODULEVERSION>5.1</MODULEVERSION>
            <PROCESSNUMBER>7</PROCESSNUMBER>
          </CONTENT>
          <DEVICEID>foo</DEVICEID>
          <QUERY>SNMPQUERY</QUERY>
        </REQUEST>
        ';

        $converter = new \Glpi\Inventory\Converter();
        $source = json_decode($converter->convert($xml_source));
        $inventory = new \Glpi\Inventory\Inventory($source);

        if ($inventory->inError()) {
            $this->dump($inventory->getErrors());
        }
        $this->boolean($inventory->inError())->isFalse();
        $this->array($inventory->getErrors())->isEmpty();

        //do a inventory
        $inventory->setDiscovery(false);
        $inventory->doInventory($xml_source, true);


        $printer = new \Printer();
        $this->boolean($printer->getFromDbByCrit(['name' => 'CLPSF99', 'serial' => 'E1234567890']))->isTrue();

        //1 NetworkPortEthernet
        $np_ethernet = new \NetworkPort();
        $np_ethernets = $np_ethernet->find(['itemtype' => 'Printer', 'items_id' => $printer->fields['id'] , 'instantiation_type' => 'NetworkPortEthernet']);
        $this->array($np_ethernets)->hasSize(1);
        $first_np_versions = array_pop($np_ethernets);


        //redo a inventory
        $inventory->setDiscovery(false);
        $inventory->doInventory($xml_source, true);

        $printer = new \Printer();
        $this->boolean($printer->getFromDbByCrit(['name' => 'CLPSF99', 'serial' => 'E1234567890']))->isTrue();

        //1 NetworkPortEthernet
        $np_ethernet = new \NetworkPort();
        $np_ethernets = $np_ethernet->find(['itemtype' => 'Printer', 'items_id' => $printer->fields['id'] , 'instantiation_type' => 'NetworkPortEthernet']);
        $this->array($np_ethernets)->hasSize(1);
        $second_np_versions = array_pop($np_ethernets);

        //check NetworkPort is same
        $this->integer($second_np_versions['id'])->isIdenticalTo($first_np_versions['id']);

        //remove printer for other test
        $printer->delete($printer->fields);
    }

    public function testSnmpPrinterAssetTag()
    {

        $xml_source = '<?xml version="1.0" encoding="UTF-8"?>
        <REQUEST>
          <CONTENT>
            <DEVICE>
              <CARTRIDGES>
                <TONERBLACK>20</TONERBLACK>
                <TONERCYAN>20</TONERCYAN>
                <TONERMAGENTA>40</TONERMAGENTA>
                <TONERYELLOW>20</TONERYELLOW>
                <WASTETONER>100</WASTETONER>
              </CARTRIDGES>
              <INFO>
                <COMMENTS>RICOH MP C5503 1.38 / RICOH Network Printer C model / RICOH Network Scanner C model / RICOH Network Facsimile C model</COMMENTS>
                <ID>1</ID>
                <IPS>
                  <IP>10.100.51.207</IP>
                </IPS>
                <LOCATION>Location</LOCATION>
                <MAC>00:26:73:12:34:56</MAC>
                <MANUFACTURER>Ricoh</MANUFACTURER>
                <MEMORY>1</MEMORY>
                <MODEL>MP C5503</MODEL>
                <NAME>CLPSF99</NAME>
                <RAM>1973</RAM>
                <SERIAL>E1234567890</SERIAL>
                <ASSETTAG>other_serial</ASSETTAG>
                <TYPE>PRINTER</TYPE>
                <UPTIME>33 days, 22:19:01.00</UPTIME>
              </INFO>
              <PAGECOUNTERS>
                <TOTAL>1164615</TOTAL>
              </PAGECOUNTERS>
              <PORTS>
                <PORT>
                  <IFDESCR>ncmac0</IFDESCR>
                  <IFINERRORS>0</IFINERRORS>
                  <IFINOCTETS>2656604236</IFINOCTETS>
                  <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
                  <IFLASTCHANGE>33 days, 22:19:01.00</IFLASTCHANGE>
                  <IFMTU>1500</IFMTU>
                  <IFNAME>ncmac0</IFNAME>
                  <IFNUMBER>1</IFNUMBER>
                  <IFOUTERRORS>0</IFOUTERRORS>
                  <IFOUTOCTETS>1271117255</IFOUTOCTETS>
                  <IFSPEED>100000000</IFSPEED>
                  <IFSTATUS>1</IFSTATUS>
                  <IFTYPE>6</IFTYPE>
                  <IP>10.100.51.207</IP>
                  <IPS>
                    <IP>10.100.51.207</IP>
                  </IPS>
                  <MAC>00:26:73:12:34:56</MAC>
                </PORT>
              </PORTS>
            </DEVICE>
            <MODULEVERSION>5.1</MODULEVERSION>
            <PROCESSNUMBER>7</PROCESSNUMBER>
          </CONTENT>
          <DEVICEID>foo</DEVICEID>
          <QUERY>SNMPQUERY</QUERY>
        </REQUEST>
        ';

        $converter = new \Glpi\Inventory\Converter();
        $source = json_decode($converter->convert($xml_source));
        $inventory = new \Glpi\Inventory\Inventory($source);

        if ($inventory->inError()) {
            $this->dump($inventory->getErrors());
        }
        $this->boolean($inventory->inError())->isFalse();
        $this->array($inventory->getErrors())->isEmpty();

        //do a inventory
        $inventory->doInventory($xml_source, true);

        $printer = new \Printer();
        $this->boolean($printer->getFromDbByCrit(['name' => 'CLPSF99', 'serial' => 'E1234567890']))->isTrue();

        $this->string($printer->fields['otherserial'])->isIdenticalTo('other_serial');

        //remove printer for other test
        $printer->delete($printer->fields);
    }
}
