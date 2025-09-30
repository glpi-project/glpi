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

namespace tests\units;

use DbTestCase;
use Glpi\Inventory\Converter;
use Glpi\Inventory\Inventory;
use Printer;
use RuleMatchedLog as GlobalRuleMatchedLog;

/* Test for inc/rule.class.php */

class RuleMatchedLogTest extends DbTestCase
{
    public function testCriteriaAddition()
    {
        $printer = new Printer();
        $rulematchedlog = new GlobalRuleMatchedLog();
        // Addition test
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
        <REQUEST>
          <CONTENT>
            <DEVICE>
              <INFO>
                <COMMENTS>Imprimante HP LaserJet Pro MFP M428fdw</COMMENTS>
                <ID>123456</ID>
                <MANUFACTURER>HP</MANUFACTURER>
                <MEMORY>64</MEMORY>
                <MODEL>LaserJet Pro MFP M428fdw</MODEL>
                <NAME>Imprimante HP LaserJet Pro MFP M428fdw</NAME>
                <RAM>128</RAM>
                <SERIAL>ABC123456</SERIAL>
                <TYPE>PRINTER</TYPE>
                <UPTIME>7 days, 12:34:56.78</UPTIME>
                <IPS><IP>192.168.1.100</IP></IPS>
                <MAC>01:23:45:67:89:ab</MAC>
              </INFO>
              <PORTS>
                <PORT>
                  <IFDESCR>Ethernet/1</IFDESCR>
                  <IFINERRORS>0</IFINERRORS>
                  <IFINOCTETS>1234567890</IFINOCTETS>
                  <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
                  <IFLASTCHANGE>12.34 seconds</IFLASTCHANGE>
                  <IFMTU>1500</IFMTU>
                  <IFNAME>Port 1</IFNAME>
                  <IFNUMBER>1</IFNUMBER>
                  <IFOUTERRORS>0</IFOUTERRORS>
                  <IFOUTOCTETS>987654321</IFOUTOCTETS>
                  <IFSPEED>1000000000</IFSPEED>
                  <IFSTATUS>1</IFSTATUS>
                  <IFTYPE>7</IFTYPE>
                  <IP>192.168.1.100</IP>
                  <IPS>
                    <IP>192.168.1.100</IP>
                  </IPS>
                  <MAC>01:23:45:67:89:ab</MAC>
                </PORT>
              </PORTS>
            </DEVICE>
          </CONTENT>
          <QUERY>SNMP</QUERY>
          <DEVICEID>bar</DEVICEID>
        </REQUEST>';

        $converter = new Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $inventory = new Inventory($json);
        $date_add = $_SESSION['glpi_currenttime'];

        if ($inventory->inError()) {
            dump($inventory->getErrors());
        }
        $this->assertFalse($inventory->inError());
        $this->assertEmpty($inventory->getErrors());

        $printers_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $printers_id);

        $this->assertTrue($printer->getFromDB($printers_id));

        $this->assertTrue($rulematchedlog->getFromDBByCrit(
            [
                'items_id' => $printers_id,
                'itemtype' => Printer::class,
            ]
        ));
        $input = $rulematchedlog->fields['input'];
        $this->assertNotEmpty($input);
        $this->assertEquals('{"_auto":1,"deviceid":"bar","autoupdatesystems_id":"GLPI Native Inventory","last_inventory_update":"' . $date_add . '","manufacturer":"HP","memory":64,"model":"LaserJet Pro MFP M428fdw","name":"Imprimante HP LaserJet Pro MFP M428fdw","serial":"ABC123456","type":"Printer","uptime":"7 days, 12:34:56.78","ip":["192.168.1.100"],"mac":"01:23:45:67:89:ab","description":"Imprimante HP LaserJet Pro MFP M428fdw","sysdescr":"Imprimante HP LaserJet Pro MFP M428fdw","printertypes_id":"Printer","manufacturers_id":"HP","have_ethernet":1,"memory_size":128,"itemtype":"Printer","entities_id":"0"}', $input);

        // Update test
        $xmlupdate = '<?xml version="1.0" encoding="UTF-8" ?>
        <REQUEST>
          <CONTENT>
            <DEVICE>
              <INFO>
                <COMMENTS>Imprimante HP LaserJet Pro MFP M428fdw V2</COMMENTS>
                <ID>123456</ID>
                <MANUFACTURER>HP</MANUFACTURER>
                <MEMORY>64</MEMORY>
                <MODEL>LaserJet Pro MFP M428fdw V2</MODEL>
                <NAME>Imprimante HP LaserJet Pro MFP M428fdw V2</NAME>
                <RAM>128</RAM>
                <SERIAL>ABC123456</SERIAL>
                <TYPE>PRINTER</TYPE>
                <UPTIME>7 days, 12:34:56.78</UPTIME>
                <IPS><IP>192.168.1.100</IP></IPS>
                <MAC>01:23:45:67:89:ab</MAC>
              </INFO>
            </DEVICE>
          </CONTENT>
          <QUERY>SNMP</QUERY>
          <DEVICEID>bar</DEVICEID>
        </REQUEST>';

        $dataupdate = $converter->convert($xmlupdate);
        $jsonupdate = json_decode($dataupdate);
        $inventoryupdate = new Inventory($jsonupdate);
        $date_update = $_SESSION['glpi_currenttime'];
        if ($inventoryupdate->inError()) {
            dump($inventoryupdate->getErrors());
        }
        $this->assertFalse($inventoryupdate->inError());
        $this->assertEmpty($inventoryupdate->getErrors());

        $results = $rulematchedlog->find([
            'items_id' => $printers_id,
            'itemtype' => Printer::class,
        ]);
        $this->assertEquals(2, count($results));
        $result = $rulematchedlog->find(
            [
                'items_id' => $printers_id,
                'itemtype' => Printer::class,
            ],
            'id DESC',
            1
        );
        $update_result = reset($result);
        $input = $update_result['input'];
        $this->assertNotEmpty($input);
        $this->assertEquals('{"_auto":1,"deviceid":"bar","autoupdatesystems_id":"GLPI Native Inventory","last_inventory_update":"' . $date_update . '","manufacturer":"HP","memory":64,"model":"LaserJet Pro MFP M428fdw V2","name":"Imprimante HP LaserJet Pro MFP M428fdw V2","serial":"ABC123456","type":"Printer","uptime":"7 days, 12:34:56.78","ip":["192.168.1.100"],"mac":"01:23:45:67:89:ab","description":"Imprimante HP LaserJet Pro MFP M428fdw V2","sysdescr":"Imprimante HP LaserJet Pro MFP M428fdw V2","printertypes_id":"Printer","manufacturers_id":"HP","have_ethernet":1,"memory_size":128,"itemtype":"Printer","entities_id":"0"}', $input);
    }
}
