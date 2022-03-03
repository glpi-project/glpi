<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
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
        $this->boolean($inventory->setData($json_str))->isTrue();

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
            'have_usb' => 0,
            'have_ethernet' => 1,
            'memory_size' => 512,
            'last_pages_counter' => 1802
        ]);

       //get one management port only, since iftype 24 is not importable per default
        $this->array($main->getNetworkPorts())->isIdenticalTo([]);
        $this->array($mports = $main->getManagementPorts())->hasSize(1)->hasKey('management');
        $this->array((array)$mports['management'])->isIdenticalTo([
            'mac' => '00:68:eb:f2:be:10',
            'name' => 'Management',
            'netname' => 'internal',
            'instantiation_type' => 'NetworkPortAggregate',
            'is_internal' => true,
            'ipaddress' => [
                '10.59.29.175'
            ]
        ]);

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
        unset($result['date']);

        $this->array($result)->isIdenticalTo([
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
            'faxed' => 0
        ]);
    }

    public function testInventoryMove()
    {
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

       //computer inventory with one printer
        $inventory = $this->doInventory($xml_source_2, true);

        $computers_2_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_2_id)->isGreaterThan(0);

       //we still have only 1 printer
        $printers = $printer->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($printers))->isIdenticalTo(1);

       //still linked on first computer inventoried
        $printers = $item_printer->find(['itemtype' => 'printer', 'computers_id' => $computers_id]);
        $this->integer(count($printers))->isIdenticalTo(1);

       //not linked on last inventoried computer
        $printers = $item_printer->find(['itemtype' => 'printer', 'computers_id' => $computers_2_id]);
        $this->integer(count($printers))->isIdenticalTo(0);
    }

    public function testPrinterIgnoreImport()
    {
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

        $item_printer = new \Computer_Item();
        $printers = $item_printer->find(['computers_id' => $inventory->getItem()->fields['id'], 'itemtype' => 'Printer']);
        $this->integer(count($printers))->isIdenticalTo(1);

        $this->boolean($printer->getFromDB(current($printers)['items_id']))->isTrue();
        $this->string($printer->fields['name'])->isIdenticalTo('HP Color LaserJet Pro MFP M476 PCL 6');
    }

    public function testPrinterRenamedImport()
    {
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
}
