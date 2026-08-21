<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use Glpi\Inventory\Conf;
use Glpi\Inventory\Converter;
use Glpi\Inventory\MainAsset\PDU;
use Glpi\Tests\AbstractInventoryAsset;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('single-thread')]

class PDUTest extends AbstractInventoryAsset
{
    private const XML_TWO_PLUGS = '<?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <FIRMWARES>
        <DESCRIPTION>APC Rack PDU Firmware</DESCRIPTION>
        <MANUFACTURER>APC</MANUFACTURER>
        <NAME>AP8853</NAME>
        <TYPE>PDU</TYPE>
        <VERSION>v6.9.6</VERSION>
      </FIRMWARES>
      <INFO>
        <COMMENTS>APC Rack PDU Switched, 2G, Metered-by-Outlet</COMMENTS>
        <CONTACT>Schneider Electric Support</CONTACT>
        <FIRMWARE>6.9.6</FIRMWARE>
        <ID>1</ID>
        <IPS>
          <IP>192.168.1.50</IP>
        </IPS>
        <LOCATION>DataCenter_Room_01_Rack_A4</LOCATION>
        <MAC>00:C0:B7:65:DE:01</MAC>
        <MANUFACTURER>APC</MANUFACTURER>
        <MODEL>AP8853</MODEL>
        <NAME>PDU-MASTER-RACK-A4</NAME>
        <SERIAL>ZA133456789</SERIAL>
        <TYPE>PDU</TYPE>
        <UPTIME>45:12:30.22</UPTIME>
      </INFO>
      <PDU>
        <TYPE>C13/C19</TYPE>
        <PLUG>
          <NAME>Server_Blade_01</NAME>
          <TYPE>C15</TYPE>
        </PLUG>
        <PLUG>
          <NAME>Storage_SAN_Controller_B</NAME>
          <TYPE>C14</TYPE>
        </PLUG>
      </PDU>
    </DEVICE>
    <MODULEVERSION>4.1</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>APC-PDU-001</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

    private const XML_THREE_PLUGS = '<?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <COMMENTS>APC Rack PDU Switched, 2G, Metered-by-Outlet</COMMENTS>
        <FIRMWARE>6.9.6</FIRMWARE>
        <ID>1</ID>
        <IPS>
          <IP>192.168.1.50</IP>
        </IPS>
        <MAC>00:C0:B7:65:DE:01</MAC>
        <MANUFACTURER>APC</MANUFACTURER>
        <MODEL>AP8853</MODEL>
        <NAME>PDU-MASTER-RACK-A4</NAME>
        <SERIAL>ZA133456789</SERIAL>
        <TYPE>PDU</TYPE>
      </INFO>
      <PDU>
        <TYPE>C13/C19</TYPE>
        <PLUG>
          <NAME>Server_Blade_01</NAME>
          <TYPE>C15</TYPE>
        </PLUG>
        <PLUG>
          <NAME>Storage_SAN_Controller_B</NAME>
          <TYPE>C14</TYPE>
        </PLUG>
        <PLUG>
          <NAME>Network_Switch_Core</NAME>
          <TYPE>C13</TYPE>
        </PLUG>
      </PDU>
    </DEVICE>
    <MODULEVERSION>4.1</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>APC-PDU-001</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

    private const XML_ONE_PLUGS = '<?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <COMMENTS>APC Rack PDU Switched, 2G, Metered-by-Outlet</COMMENTS>
        <FIRMWARE>6.9.6</FIRMWARE>
        <ID>1</ID>
        <IPS><IP>192.168.1.50</IP></IPS>
        <MAC>00:C0:B7:65:DE:01</MAC>
        <MANUFACTURER>APC</MANUFACTURER>
        <MODEL>AP8853</MODEL>
        <NAME>PDU-SINGLE-PLUG</NAME>
        <SERIAL>ZA-SINGLE-001</SERIAL>
        <TYPE>PDU</TYPE>
      </INFO>
      <PDU>
        <TYPE>C13/C19</TYPE>
        <PLUG>
          <NAME>Server_Blade_01</NAME>
          <TYPE>C15</TYPE>
        </PLUG>
      </PDU>
    </DEVICE>
    <MODULEVERSION>4.1</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>APC-PDU-SINGLE</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

    private const XML_ZERO_PLUGS = '<?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <COMMENTS>APC Rack PDU Switched, 2G, Metered-by-Outlet</COMMENTS>
        <FIRMWARE>6.9.6</FIRMWARE>
        <ID>1</ID>
        <IPS>
          <IP>192.168.1.50</IP>
        </IPS>
        <MAC>00:C0:B7:65:DE:01</MAC>
        <MANUFACTURER>APC</MANUFACTURER>
        <MODEL>AP8853</MODEL>
        <NAME>PDU-MASTER-RACK-A4</NAME>
        <SERIAL>ZA133456789</SERIAL>
        <TYPE>PDU</TYPE>
      </INFO>
      <PDU>
        <TYPE>C13/C19</TYPE>
      </PDU>
    </DEVICE>
    <MODULEVERSION>4.1</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>APC-PDU-001</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

    private const XML_NO_SERIAL = '<?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <COMMENTS>APC Rack PDU Switched, 2G, Metered-by-Outlet</COMMENTS>
        <FIRMWARE>6.9.6</FIRMWARE>
        <ID>1</ID>
        <IPS><IP>192.168.1.50</IP></IPS>
        <MAC>00:C0:B7:65:DE:02</MAC>
        <MANUFACTURER>APC</MANUFACTURER>
        <MODEL>AP8853</MODEL>
        <NAME>PDU-NO-SERIAL</NAME>
        <TYPE>PDU</TYPE>
      </INFO>
      <PDU>
        <TYPE>C13/C19</TYPE>
        <PLUG>
          <NAME>Server_Blade_02</NAME>
          <TYPE>C15</TYPE>
        </PLUG>
        <PLUG>
          <NAME>Storage_SAN_Controller_B1</NAME>
          <TYPE>C14</TYPE>
        </PLUG>
      </PDU>
    </DEVICE>
    <MODULEVERSION>4.1</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>APC-PDU-NOSERIAL</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>';

    public static function assetProvider(): array
    {
        return [
            [
                'xml' => self::XML_TWO_PLUGS,
                'expected' => '{"autoupdatesystems_id":"GLPI Native Inventory","last_inventory_update":"DATE_NOW","is_deleted":0,"contact":"Schneider Electric Support","firmware":"6.9.6","ips":["192.168.1.50"],"location":"DataCenter_Room_01_Rack_A4","mac":"00:C0:B7:65:DE:01","manufacturer":"APC","model":"AP8853","name":"PDU-MASTER-RACK-A4","serial":"ZA133456789","type":"Pdu","uptime":"45:12:30.22","description":"APC Rack PDU Switched, 2G, Metered-by-Outlet","pdu":{"plug":[{"name":"Server_Blade_01","plugtypes_id":"C15"},{"name":"Storage_SAN_Controller_B","plugtypes_id":"C14"}]},"sysdescr":"APC Rack PDU Switched, 2G, Metered-by-Outlet","locations_id":"DataCenter_Room_01_Rack_A4","pdumodels_id":"AP8853","pdutypes_id":"C13\/C19","manufacturers_id":"APC"}',
            ],
            [
                'xml' => self::XML_ONE_PLUGS,
                'expected' => '{"autoupdatesystems_id":"GLPI Native Inventory","last_inventory_update":"DATE_NOW","is_deleted":0,"firmware":"6.9.6","ips":["192.168.1.50"],"mac":"00:C0:B7:65:DE:01","manufacturer":"APC","model":"AP8853","name":"PDU-SINGLE-PLUG","serial":"ZA-SINGLE-001","type":"Pdu","description":"APC Rack PDU Switched, 2G, Metered-by-Outlet","pdu":{"plug":[{"name":"Server_Blade_01","plugtypes_id":"C15"}]},"sysdescr":"APC Rack PDU Switched, 2G, Metered-by-Outlet","pdumodels_id":"AP8853","pdutypes_id":"C13\/C19","manufacturers_id":"APC"}',
            ],
        ];
    }

    #[DataProvider('assetProvider')]
    public function testPrepare(string $xml, string $expected): void
    {
        $date_now = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date_now;
        $expected = str_replace('DATE_NOW', $date_now, $expected);

        $converter = new Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $pdu = new \PDU();
        $main = new PDU($pdu, $json);
        $main->setExtraData((array) $json->content);
        $result = $main->prepare();
        $this->assertEquals(json_decode($expected), $result[0]);
    }

    public function testHandle(): void
    {
        $inventory = $this->doInventory(self::XML_TWO_PLUGS, true);

        $pdu = $inventory->getItem();
        $this->assertInstanceOf(\PDU::class, $pdu);
        $pdus_id = $pdu->fields['id'];
        $this->assertGreaterThan(0, $pdus_id);

        $this->assertSame('PDU-MASTER-RACK-A4', $pdu->fields['name']);
        $this->assertSame('ZA133456789', $pdu->fields['serial']);

        $plug = new \Plug();
        $plugs = $plug->find([
            'itemtype_main' => \PDU::class,
            'items_id_main' => $pdus_id,
        ]);
        $this->assertCount(2, $plugs);

        $dynamic_plugs = $plug->find([
            'itemtype_main' => \PDU::class,
            'items_id_main' => $pdus_id,
            'is_dynamic'    => 1,
        ]);
        $this->assertCount(2, $dynamic_plugs);
    }

    public function testHandleOnePlug(): void
    {
        $inventory = $this->doInventory(self::XML_ONE_PLUGS, true);

        $pdu = $inventory->getItem();
        $this->assertInstanceOf(\PDU::class, $pdu);
        $pdus_id = $pdu->fields['id'];
        $this->assertGreaterThan(0, $pdus_id);

        $this->assertSame('PDU-SINGLE-PLUG', $pdu->fields['name']);
        $this->assertSame('ZA-SINGLE-001', $pdu->fields['serial']);

        $plug = new \Plug();
        $plugs = $plug->find([
            'itemtype_main' => \PDU::class,
            'items_id_main' => $pdus_id,
        ]);
        $this->assertCount(1, $plugs);

        $dynamic_plugs = $plug->find([
            'itemtype_main' => \PDU::class,
            'items_id_main' => $pdus_id,
            'is_dynamic'    => 1,
        ]);
        $this->assertCount(1, $dynamic_plugs);

        $plug_data = reset($plugs);
        $this->assertSame('Server_Blade_01', $plug_data['name']);
    }

    public function testPduGeneralFields(): void
    {
        $inventory = $this->doInventory(self::XML_TWO_PLUGS, true);

        $pdu = $inventory->getItem();
        $this->assertInstanceOf(\PDU::class, $pdu);

        $this->assertSame(1, $pdu->fields['is_dynamic']);
        $this->assertSame('ZA133456789', $pdu->fields['serial']);
        $this->assertSame('APC Rack PDU Switched, 2G, Metered-by-Outlet', $pdu->fields['sysdescr']);
        $this->assertGreaterThan(0, $pdu->fields['manufacturers_id']);
        $this->assertGreaterThan(0, $pdu->fields['pdumodels_id']);
        $this->assertGreaterThan(0, $pdu->fields['pdutypes_id']);
        $this->assertGreaterThan(0, $pdu->fields['autoupdatesystems_id']);
        $this->assertSame(0, $pdu->fields['is_deleted']);
    }

    public function testImportDisabled(): void
    {
        $this->login();
        $conf = new Conf();
        $this->assertTrue($conf->saveConf(['import_pdu' => 0]));
        $this->logout();

        $this->doInventory(self::XML_TWO_PLUGS, true);

        $pdu = new \PDU();
        $this->assertCount(0, $pdu->find(['name' => 'PDU-MASTER-RACK-A4']));

        $this->login();
        $this->assertTrue($conf->saveConf(['import_pdu' => 1]));
        $this->logout();
    }

    public function testSameSerialNotDuplicated(): void
    {
        $this->doInventory(self::XML_TWO_PLUGS, true);
        $this->doInventory(self::XML_TWO_PLUGS, true);

        $pdu = new \PDU();
        $this->assertCount(1, $pdu->find(['serial' => 'ZA133456789']));
    }

    public function testInventoryUpdate(): void
    {
        $plug = new \Plug();

        // initial inventory: 3 plugs
        $inventory = $this->doInventory(self::XML_THREE_PLUGS, true);
        $pdu = $inventory->getItem();
        $pdus_id = $pdu->fields['id'];
        $this->assertGreaterThan(0, $pdus_id);

        $plugs = $plug->find(['itemtype_main' => \PDU::class, 'items_id_main' => $pdus_id]);
        $this->assertCount(3, $plugs);

        // re-inventory with 2 plugs: the third dynamic plug must be deleted
        $this->doInventory(self::XML_TWO_PLUGS, true);

        $plugs = $plug->find(['itemtype_main' => \PDU::class, 'items_id_main' => $pdus_id]);
        $this->assertCount(2, $plugs);

        $plug_names = array_column($plugs, 'name');
        $this->assertContains('Server_Blade_01', $plug_names);
        $this->assertContains('Storage_SAN_Controller_B', $plug_names);

        // re-inventory with 3 plugs: the removed plug must be re-added
        $this->doInventory(self::XML_THREE_PLUGS, true);

        $plugs = $plug->find(['itemtype_main' => \PDU::class, 'items_id_main' => $pdus_id]);
        $this->assertCount(3, $plugs);

        $plug_names = array_column($plugs, 'name');
        $this->assertContains('Server_Blade_01', $plug_names);
        $this->assertContains('Storage_SAN_Controller_B', $plug_names);
        $this->assertContains('Network_Switch_Core', $plug_names);
    }

    public function testDynamicPlugsDeletedOnReInventoryWithNoPlugs(): void
    {
        $plug = new \Plug();

        // initial inventory: 2 dynamic plugs
        $inventory = $this->doInventory(self::XML_TWO_PLUGS, true);
        $pdu = $inventory->getItem();
        $pdus_id = $pdu->fields['id'];
        $this->assertGreaterThan(0, $pdus_id);
        $this->assertCount(2, $plug->find(['itemtype_main' => \PDU::class, 'items_id_main' => $pdus_id]));

        // add a manual (non-dynamic) plug to verify it is preserved
        $manual_id = $plug->add([
            'name'          => 'Manual_Plug',
            'plugtypes_id'  => 0,
            'itemtype_main' => \PDU::class,
            'items_id_main' => $pdus_id,
            'is_dynamic'    => 0,
        ]);
        $this->assertGreaterThan(0, $manual_id);

        // re-inventory with 0 plugs: dynamic plugs deleted, manual plug preserved
        $this->doInventory(self::XML_ZERO_PLUGS, true);

        $remaining = $plug->find(['itemtype_main' => \PDU::class, 'items_id_main' => $pdus_id]);
        $this->assertCount(1, $remaining);
        $remaining_plug = reset($remaining);
        $this->assertSame('Manual_Plug', $remaining_plug['name']);
        $this->assertSame(0, (int) $remaining_plug['is_dynamic']);
    }

    public function testLockedFieldPDU(): void
    {
        // create PDU via inventory
        $inventory = $this->doInventory(self::XML_TWO_PLUGS, true);
        $pdu = $inventory->getItem();
        $pdus_id = $pdu->fields['id'];
        $this->assertGreaterThan(0, $pdus_id);
        $this->assertSame('PDU-MASTER-RACK-A4', $pdu->fields['name']);

        // manually update name — triggers Lockedfield creation on a dynamic item
        $this->assertTrue($pdu->update(['id' => $pdus_id, 'name' => 'Manual PDU Name']));

        $lockedfield = new \Lockedfield();
        $locks = $lockedfield->find(['itemtype' => \PDU::class, 'items_id' => $pdus_id, 'field' => 'name']);
        $this->assertCount(1, $locks);

        // re-inventory: the locked name must not be overwritten
        $this->doInventory(self::XML_TWO_PLUGS, true);

        $this->assertTrue($pdu->getFromDB($pdus_id));
        $this->assertSame('Manual PDU Name', $pdu->fields['name']);

        // lock must still be present
        $locks = $lockedfield->find(['itemtype' => \PDU::class, 'items_id' => $pdus_id, 'field' => 'name']);
        $this->assertCount(1, $locks);
    }

    public function testHandleRefusedByRule(): void
    {
        $this->login();

        $refuse_id = $this->addRule(
            \RuleImportAsset::class,
            'Refuse PDU without serial',
            [
                [
                    'criteria'  => 'serial',
                    'condition' => \Rule::PATTERN_IS_EMPTY,
                    'pattern'   => '',
                ],
            ],
            [
                'action_type' => 'assign',
                'field'       => '_inventory',
                'value'       => '0',
            ]
        );

        // move the new rule before all existing rules so it takes priority
        $existing = (new \RuleImportAsset())->find(
            ['sub_type' => \RuleImportAsset::class],
            ['ranking ASC'],
            1
        );
        if (!empty($existing)) {
            $first_id = (int) array_key_first($existing);
            if ($first_id !== $refuse_id) {
                (new \RuleImportAssetCollection())->moveRule(
                    $refuse_id,
                    $first_id,
                    \RuleCollection::MOVE_BEFORE
                );
            }
        }

        $this->logout();

        $this->doInventory(self::XML_NO_SERIAL, true);

        $pdu = new \PDU();
        $this->assertCount(0, $pdu->find(['name' => 'PDU-NO-SERIAL']));

        $plug = new \Plug();
        $this->assertCount(
            0,
            $plug->find(['name' => ['Server_Blade_02', 'Storage_SAN_Controller_B1']])
        );
    }

    /*public function testLockedFieldAndPlug(): void
    {
        global $DB;

        // insert a locked field for Plug to verify inventory is resilient
        $this->assertGreaterThan(
            0,
            (int) $DB->insert('glpi_lockedfields', [
                'field'    => 'name',
                'itemtype' => 'Plug',
                'is_global' => 0,
            ])
        );

        $inventory = $this->doInventory(self::XML_TWO_PLUGS, true);
        $pdu = $inventory->getItem();
        $pdus_id = $pdu->fields['id'];
        $this->assertGreaterThan(0, $pdus_id);

        // plugs must still be created despite the locked field
        $plug = new \Plug();
        $plugs = $plug->find(['itemtype_main' => \PDU::class, 'items_id_main' => $pdus_id]);
        $this->assertCount(2, $plugs);
    }*/
}
