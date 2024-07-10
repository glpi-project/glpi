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

namespace tests\units;

use DbTestCase;

/* Test for inc/savedsearch.class.php */

class LockedfieldTest extends DbTestCase
{
    public function testWithComputer()
    {
        $computer = new \Computer();
        $cid = (int)$computer->add([
            'name'         => 'Computer from inventory',
            'serial'       => '123456',
            'otherserial'  => '789012',
            'entities_id'  => 0,
            'is_dynamic'   => 1
        ]);
        $this->assertGreaterThan(0, $cid);

        $lockedfield = new \Lockedfield();
        $this->assertTrue($lockedfield->isHandled($computer));
        $this->assertEmpty($lockedfield->getLockedValues($computer->getType(), $cid));

        //update computer manually, to add a locked field
        $this->assertTrue(
            $computer->update(['id' => $cid, 'otherserial' => 'AZERTY'])
        );

        $this->assertTrue($computer->getFromDB($cid));
        $this->assertSame(['otherserial' => null], $lockedfield->getLockedValues($computer->getType(), $cid));

        //ensure new dynamic update does not override otherserial again
        $this->assertTrue(
            $computer->update([
                'id' => $cid,
                'otherserial'  => '789012',
                'is_dynamic'   => 1
            ])
        );

        $this->assertTrue($computer->getFromDB($cid));
        $this->assertEquals('AZERTY', $computer->fields['otherserial']);
        $this->assertSame(['otherserial' => '789012'], $lockedfield->getLockedValues($computer->getType(), $cid));

        //ensure new dynamic update do not set new lock on regular update
        $this->assertTrue(
            $computer->update([
                'id' => $cid,
                'name'         => 'Computer name changed',
                'is_dynamic'   => 1
            ])
        );

        $this->assertTrue($computer->getFromDB($cid));
        $this->assertEquals('Computer name changed', $computer->fields['name']);
        $this->assertSame(['otherserial' => '789012'], $lockedfield->getLockedValues($computer->getType(), $cid));

        //ensure regular update do work on locked field
        $this->assertTrue(
            $computer->update(['id' => $cid, 'otherserial' => 'QWERTY'])
        );
        $this->assertTrue($computer->getFromDB($cid));
        $this->assertEquals('QWERTY', $computer->fields['otherserial']);
    }

    public function testGlobalLock()
    {
        $computer = new \Computer();
        $cid = (int)$computer->add([
            'name'         => 'Computer from inventory',
            'serial'       => '123456',
            'otherserial'  => '789012',
            'entities_id'  => 0,
            'is_dynamic'   => 1
        ]);
        $this->assertGreaterThan(0, $cid);

        $lockedfield = new \Lockedfield();
        $this->assertTrue($lockedfield->isHandled($computer));
        $this->assertEmpty($lockedfield->getLockedValues($computer->getType(), $cid));

        //add a global lock on otherserial field
        $this->assertGreaterThan(
            0,
            $lockedfield->add([
                'item' => 'Computer - otherserial'
            ])
        );

        $this->assertTrue($computer->getFromDB($cid));
        $this->assertSame(['otherserial' => null], $lockedfield->getLockedValues($computer->getType(), $cid));

        //ensure new dynamic update does not override otherserial again
        $this->assertTrue(
            $computer->update([
                'id' => $cid,
                'otherserial'  => 'changed',
                'is_dynamic' => 1
            ])
        );

        $this->assertTrue($computer->getFromDB($cid));
        $this->assertEquals('789012', $computer->fields['otherserial']);
        $this->assertSame(['otherserial' => null], $lockedfield->getLockedValues($computer->getType(), $cid));

        //ensure new dynamic update do not set new lock on regular update
        $this->assertTrue(
            $computer->update([
                'id' => $cid,
                'name' => 'Computer name changed',
                'is_dynamic' => 1
            ])
        );

        $this->assertTrue($computer->getFromDB($cid));
        $this->assertEquals('Computer name changed', $computer->fields['name']);
        $this->assertSame(['otherserial' => null], $lockedfield->getLockedValues($computer->getType(), $cid));

        //ensure regular update do work on locked field
        $this->assertTrue(
            $computer->update(['id' => $cid, 'otherserial' => 'QWERTY'])
        );
        $this->assertTrue($computer->getFromDB($cid));
        $this->assertEquals('QWERTY', $computer->fields['otherserial']);
    }

    /**
     * Check for global locks adding an itemtype
     */
    public function testGlobalLockAdd()
    {
        $lockedfield = new \Lockedfield();

        //add a global lock on otherserial field
        $this->assertGreaterThan(
            0,
            $lockedfield->add([
                'item' => 'Computer - otherserial'
            ])
        );

        $computer = new \Computer();
        $cid = (int)$computer->add([
            'name'         => 'Computer from inventory',
            'serial'       => '123456',
            'otherserial'  => '789012',
            'entities_id'  => 0,
            'is_dynamic'   => 1
        ]);
        $this->assertGreaterThan(0, $cid);

        $this->assertTrue($computer->getFromDB($cid));
        $this->assertEquals('', $computer->fields['otherserial']);

        $this->assertTrue($lockedfield->isHandled($computer));
        //lockedfield value must be null because it's a global lock
        $this->assertSame(['otherserial' => null], $lockedfield->getLockedValues($computer->getType(), $cid));

        //ensure new dynamic update does not override otherserial again
        $this->assertTrue(
            $computer->update([
                'id' => $cid,
                'otherserial'  => 'changed',
                'is_dynamic' => 1
            ])
        );

        $this->assertTrue($computer->getFromDB($cid));
        $this->assertEquals('', $computer->fields['otherserial']);
        //lockedfield must be null because it's a global lock
        $this->assertSame(['otherserial' => null], $lockedfield->getLockedValues($computer->getType(), $cid));

        //ensure new dynamic update do not set new lock on regular update
        $this->assertTrue(
            $computer->update([
                'id' => $cid,
                'name' => 'Computer name changed',
                'is_dynamic' => 1
            ])
        );

        $this->assertTrue($computer->getFromDB($cid));
        $this->assertEquals('Computer name changed', $computer->fields['name']);
        //lockedfield must be null because it's a global lock
        $this->assertSame(['otherserial' => null], $lockedfield->getLockedValues($computer->getType(), $cid));

        //ensure regular update do work on locked field
        $this->assertTrue(
            $computer->update(['id' => $cid, 'otherserial' => 'QWERTY'])
        );
        $this->assertTrue($computer->getFromDB($cid));
        $this->assertEquals('QWERTY', $computer->fields['otherserial']);
    }

    public function testNoRelation()
    {
        global $DB;

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <HARDWARE>
      <NAME>glpixps</NAME>
      <UUID>25C1BB60-5BCB-11D9-B18F-5404A6A534C4</UUID>
    </HARDWARE>
    <BIOS>
      <MSN>640HP72</MSN>
      <SSN>000</SSN>
      <SMANUFACTURER>Da Manuf</SMANUFACTURER>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>";

        $existing_manufacturers = countElementsInTable(\Manufacturer::getTable());
        $lockedfield = new \Lockedfield();

        //add a global lock on manufacturers_id field
        $this->assertGreaterThan(
            0,
            $lockedfield->add([
                'item' => 'Computer - manufacturers_id'
            ])
        );

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $inventory = new \Glpi\Inventory\Inventory($json);

        if ($inventory->inError()) {
            $this->dump($inventory->getErrors());
        }
        $this->assertFalse($inventory->inError());
        $this->assertEmpty($inventory->getErrors());

        //check matchedlogs
        $criteria = [
            'FROM' => \RuleMatchedLog::getTable(),
            'LEFT JOIN' => [
                \Rule::getTable() => [
                    'ON' => [
                        \RuleMatchedLog::getTable() => 'rules_id',
                        \Rule::getTable() => 'id'
                    ]
                ]
            ],
            'WHERE' => []
        ];
        $iterator = $DB->request($criteria);
        $this->assertSame('Computer import (by serial + uuid)', $iterator->current()['name']);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertSame(1, count($agents));
        $agent = $agents->current();
        $this->assertSame('glpixps.teclib.infra-2018-10-03-08-42-36', $agent['deviceid']);
        $this->assertSame('Computer', $agent['itemtype']);

        //check created computer
        $computers_id = $agent['items_id'];

        $this->assertGreaterThan(0, $computers_id);
        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($computers_id));
        $this->assertEquals(0, $computer->fields['manufacturers_id']);

        //ensure no new manufacturer has been added
        $this->assertSame($existing_manufacturers, countElementsInTable(\Manufacturer::getTable()));
    }

    public function testNoLocation()
    {
        global $DB;

        $xml = "<?xml version=\"1.0\"?>
<REQUEST><CONTENT><DEVICE>
      <INFO>
        <COMMENTS>Brother NC-6800h, Firmware Ver.1.01  (08.12.12),MID 84UB03</COMMENTS>
        <ID>1907</ID>
        <IPS>
          <IP>10.75.230.125</IP>
        </IPS>
        <MAC>00:1b:a9:12:11:f2</MAC>
        <MANUFACTURER>Brother</MANUFACTURER>
        <MEMORY>32</MEMORY>
        <MODEL>Brother HL-5350DN series</MODEL>
        <NAME>CE75I09008</NAME>
        <RAM>32</RAM>
        <SERIAL>D9J230159</SERIAL>
        <TYPE>PRINTER</TYPE>
        <UPTIME>14 days, 22:48:33.30</UPTIME>
      </INFO>
    </DEVICE>
  </CONTENT><QUERY>SNMP</QUERY><DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID></REQUEST>
";
        $existing_locations = countElementsInTable(\Location::getTable());
        $lockedfield = new \Lockedfield();

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $inventory = new \Glpi\Inventory\Inventory($json);

        if ($inventory->inError()) {
            $this->dump($inventory->getErrors());
        }
        $this->assertFalse($inventory->inError());
        $this->assertEmpty($inventory->getErrors());

        //check matchedlogs
        $criteria = [
            'FROM' => \RuleMatchedLog::getTable(),
            'LEFT JOIN' => [
                \Rule::getTable() => [
                    'ON' => [
                        \RuleMatchedLog::getTable() => 'rules_id',
                        \Rule::getTable() => 'id'
                    ]
                ]
            ],
            'WHERE' => []
        ];
        $iterator = $DB->request($criteria);
        $this->assertSame('Printer import (by serial)', $iterator->current()['name']);

        $printers_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $printers_id);

        $printer = new \Printer();
        $this->assertTrue($printer->getFromDB($printers_id));
        $this->assertEquals(0, $printer->fields['locations_id']);

        //ensure no new location has been added
        $this->assertSame($existing_locations, countElementsInTable(\Location::getTable()));

        //manually update to lock locations_id field
        $locations_id = getItemByTypeName('Location', '_location02', true);
        $this->assertTrue(
            $printer->update([
                'id' => $printers_id,
                'locations_id' => $locations_id
            ])
        );
        $this->assertSame(['locations_id' => null], $lockedfield->getLockedValues($printer->getType(), $printers_id));

        //Replay, with a location
        $xml = "<?xml version=\"1.0\"?>
<REQUEST><CONTENT><DEVICE>
      <INFO>
        <COMMENTS>Brother NC-6800h, Firmware Ver.1.01  (08.12.12),MID 84UB03</COMMENTS>
        <ID>1907</ID>
        <IPS>
          <IP>10.75.230.125</IP>
        </IPS>
        <LOCATION>Greffe Charron</LOCATION>
        <MAC>00:1b:a9:12:11:f2</MAC>
        <MANUFACTURER>Brother</MANUFACTURER>
        <MEMORY>32</MEMORY>
        <MODEL>Brother HL-5350DN series</MODEL>
        <NAME>CE75I09008</NAME>
        <RAM>32</RAM>
        <SERIAL>D9J230159</SERIAL>
        <TYPE>PRINTER</TYPE>
        <UPTIME>14 days, 22:48:33.30</UPTIME>
      </INFO>
    </DEVICE>
  </CONTENT><QUERY>SNMP</QUERY><DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID></REQUEST>
";

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $inventory = new \Glpi\Inventory\Inventory($json);

        if ($inventory->inError()) {
            $this->dump($inventory->getErrors());
        }
        $this->assertFalse($inventory->inError());
        $this->assertEmpty($inventory->getErrors());

        $this->assertTrue($printer->getFromDB($printers_id));
        $this->assertEquals($locations_id, $printer->fields['locations_id']);

        //ensure no new location has been added
        $this->assertSame($existing_locations, countElementsInTable(\Location::getTable()));

        $this->assertSame(['locations_id' => 'Greffe Charron'], $lockedfield->getLockedValues($printer->getType(), $printers_id));
    }

    public function testNoLocationGlobal()
    {
        global $DB;

        $xml = "<?xml version=\"1.0\"?>
<REQUEST><CONTENT><DEVICE>
      <INFO>
        <COMMENTS>Brother NC-6800h, Firmware Ver.1.01  (08.12.12),MID 84UB03</COMMENTS>
        <ID>1907</ID>
        <IPS>
          <IP>10.75.230.125</IP>
        </IPS>
        <LOCATION>Greffe Charron</LOCATION>
        <MAC>00:1b:a9:12:11:f2</MAC>
        <MANUFACTURER>Brother</MANUFACTURER>
        <MEMORY>32</MEMORY>
        <MODEL>Brother HL-5350DN series</MODEL>
        <NAME>CE75I09008</NAME>
        <RAM>32</RAM>
        <SERIAL>D9J230159</SERIAL>
        <TYPE>PRINTER</TYPE>
        <UPTIME>14 days, 22:48:33.30</UPTIME>
      </INFO>
    </DEVICE>
  </CONTENT><QUERY>SNMP</QUERY><DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID></REQUEST>
";

        $existing_locations = countElementsInTable(\Location::getTable());
        $lockedfield = new \Lockedfield();

        //add a global lock on locations_id field
        $this->assertGreaterThan(
            0,
            $lockedfield->add([
                'item' => 'Printer - locations_id'
            ])
        );

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $inventory = new \Glpi\Inventory\Inventory($json);

        if ($inventory->inError()) {
            $this->dump($inventory->getErrors());
        }
        $this->assertFalse($inventory->inError());
        $this->assertEmpty($inventory->getErrors());

        //check matchedlogs
        $criteria = [
            'FROM' => \RuleMatchedLog::getTable(),
            'LEFT JOIN' => [
                \Rule::getTable() => [
                    'ON' => [
                        \RuleMatchedLog::getTable() => 'rules_id',
                        \Rule::getTable() => 'id'
                    ]
                ]
            ],
            'WHERE' => []
        ];
        $iterator = $DB->request($criteria);
        $this->assertSame('Printer import (by serial)', $iterator->current()['name']);

        $printers_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $printers_id);

        $printer = new \Printer();
        $this->assertTrue($printer->getFromDB($printers_id));
        $this->assertEquals(0, $printer->fields['locations_id']);

        //ensure no new location has been added
        $this->assertSame($existing_locations, countElementsInTable(\Location::getTable()));
    }

    public function testLockedDdValue()
    {
        global $DB;

        //Play, with a location
        $xml = "<?xml version=\"1.0\"?>
<REQUEST><CONTENT><DEVICE>
      <INFO>
        <COMMENTS>Brother NC-6800h, Firmware Ver.1.01  (08.12.12),MID 84UB03</COMMENTS>
        <ID>1907</ID>
        <IPS>
          <IP>10.75.230.125</IP>
        </IPS>
        <LOCATION>Greffe Charron</LOCATION>
        <MAC>00:1b:a9:12:11:f2</MAC>
        <MANUFACTURER>Brother</MANUFACTURER>
        <MEMORY>32</MEMORY>
        <MODEL>Brother HL-5350DN series</MODEL>
        <NAME>CE75I09008</NAME>
        <RAM>32</RAM>
        <SERIAL>D9J230159</SERIAL>
        <TYPE>PRINTER</TYPE>
        <UPTIME>14 days, 22:48:33.30</UPTIME>
      </INFO>
    </DEVICE>
  </CONTENT><QUERY>SNMP</QUERY><DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID></REQUEST>
";

        $existing_locations = countElementsInTable(\Location::getTable());
        $lockedfield = new \Lockedfield();

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $inventory = new \Glpi\Inventory\Inventory($json);

        if ($inventory->inError()) {
            $this->dump($inventory->getErrors());
        }
        $this->assertFalse($inventory->inError());
        $this->assertEmpty($inventory->getErrors());

        //check matchedlogs
        $criteria = [
            'FROM' => \RuleMatchedLog::getTable(),
            'LEFT JOIN' => [
                \Rule::getTable() => [
                    'ON' => [
                        \RuleMatchedLog::getTable() => 'rules_id',
                        \Rule::getTable() => 'id'
                    ]
                ]
            ],
            'WHERE' => []
        ];
        $iterator = $DB->request($criteria);
        $this->assertSame('Printer import (by serial)', $iterator->current()['name']);

        $printers_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $printers_id);

        $printer = new \Printer();
        $this->assertTrue($printer->getFromDB($printers_id));
        $this->assertGreaterThan(0, $printer->fields['locations_id']);

        //ensure new location has been added
        ++$existing_locations;
        $this->assertSame($existing_locations, countElementsInTable(\Location::getTable()));

        //manually update to lock locations_id field
        $locations_id = getItemByTypeName('Location', '_location02', true);
        $this->assertTrue(
            $printer->update([
                'id' => $printers_id,
                'locations_id' => $locations_id
            ])
        );
        $this->assertSame(['locations_id' => null], $lockedfield->getLockedValues($printer->getType(), $printers_id));

        //Replay, with a location, to ensure location has not been updated, and locked value is correct.
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $inventory = new \Glpi\Inventory\Inventory($json);

        if ($inventory->inError()) {
            $this->dump($inventory->getErrors());
        }
        $this->assertFalse($inventory->inError());
        $this->assertEmpty($inventory->getErrors());

        $this->assertTrue($printer->getFromDB($printers_id));
        $this->assertEquals($locations_id, $printer->fields['locations_id']);

        //ensure no new location has been added
        $this->assertSame($existing_locations, countElementsInTable(\Location::getTable()));

        $this->assertSame(['locations_id' => 'Greffe Charron'], $lockedfield->getLockedValues($printer->getType(), $printers_id));
    }

    public function testLockedRelations()
    {
        $computer = new \Computer();
        $cos = new \Item_OperatingSystem();
        $aos = new \OperatingSystemArchitecture();
        $manufacturer = new \Manufacturer();
        $iav = new \ComputerAntivirus();

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <OPERATINGSYSTEM>
      <ARCH>x86_64</ARCH>
      <BOOT_TIME>2018-10-02 08:56:09</BOOT_TIME>
      <FQDN>test-pc002</FQDN>
      <FULL_NAME>Fedora 28 (Workstation Edition)</FULL_NAME>
      <HOSTID>a8c07701</HOSTID>
      <KERNEL_NAME>linux</KERNEL_NAME>
      <KERNEL_VERSION>4.18.9-200.fc28.x86_64</KERNEL_VERSION>
      <NAME>Fedora</NAME>
      <TIMEZONE>
        <NAME>CEST</NAME>
        <OFFSET>+0200</OFFSET>
      </TIMEZONE>
      <VERSION>28 (Workstation Edition)</VERSION>
    </OPERATINGSYSTEM>
    <ANTIVIRUS>
      <COMPANY>Microsoft Corporation</COMPANY>
      <ENABLED>1</ENABLED>
      <GUID>{641105E6-77ED-3F35-A304-765193BCB75F}</GUID>
      <NAME>Microsoft Security Essentials</NAME>
      <UPTODATE>1</UPTODATE>
      <VERSION>4.3.216.0</VERSION>
      <EXPIRATION>01/04/2019</EXPIRATION>
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

        $lockedfield = new \Lockedfield();

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $inventory = new \Glpi\Inventory\Inventory($json);

        if ($inventory->inError()) {
            $this->dump($inventory->getErrors());
        }
        $this->assertFalse($inventory->inError());
        $this->assertEmpty($inventory->getErrors());

        $computers_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $computers_id);

        $this->assertTrue($computer->getFromDB($computers_id));

        $this->assertTrue($cos->getFromDBByCrit(['items_id' => $computers_id]));

        //check OS version
        $this->assertTrue($aos->getFromDBByCrit(['name' => 'x86_64']));
        $archs_id = $aos->fields['id'];
        $this->assertSame($archs_id, $cos->fields['operatingsystemarchitectures_id']);

        //check antivirus manufacturer
        $this->assertTrue($iav->getFromDBByCrit(['computers_id' => $computers_id]));
        $this->assertTrue($manufacturer->getFromDBByCrit(['name' => 'Microsoft Corporation']));
        $manufacturers_id = $manufacturer->fields['id'];
        $this->assertSame($manufacturers_id, $iav->fields['manufacturers_id']);

        //manually update OS architecture field
        $newarchs_id = $aos->add(['name' => 'i386']);
        $this->assertGreaterThan(0, $newarchs_id);

        $this->assertTrue(
            $cos->update([
                'id' => $cos->fields['id'],
                'operatingsystemarchitectures_id' => $newarchs_id
            ])
        );
        $this->assertSame(['operatingsystemarchitectures_id' => null], $lockedfield->getLockedValues($cos->getType(), $cos->fields['id']));

        //manually update AV manufacturer field
        $newmanufacturers_id = $manufacturer->add(['name' => 'Crosoft']);
        $this->assertGreaterThan(0, $newmanufacturers_id);

        $this->assertTrue(
            $iav->update([
                'id' => $iav->fields['id'],
                'manufacturers_id' => $newmanufacturers_id
            ])
        );
        $this->assertSame(['manufacturers_id' => null], $lockedfield->getLockedValues($iav->getType(), $iav->fields['id']));

        //replay
        $data = $converter->convert($xml);
        $json = json_decode($data);
        $inventory = new \Glpi\Inventory\Inventory($json);

        if ($inventory->inError()) {
            $this->dump($inventory->getErrors());
        }
        $this->assertFalse($inventory->inError());
        $this->assertEmpty($inventory->getErrors());

        //make sure architecture is still the correct one
        $this->assertTrue($cos->getFromDBByCrit(['items_id' => $computers_id]));
        $this->assertSame($newarchs_id, $cos->fields['operatingsystemarchitectures_id']);

        $this->assertSame(['operatingsystemarchitectures_id' => 'x86_64'], $lockedfield->getLockedValues($cos->getType(), $cos->fields['id']));

        //make sure manufacturer is still the correct one
        $this->assertTrue($iav->getFromDBByCrit(['computers_id' => $computers_id]));
        $this->assertSame($newmanufacturers_id, $iav->fields['manufacturers_id']);

        $this->assertSame(['manufacturers_id' => 'Microsoft Corporation'], $lockedfield->getLockedValues($iav->getType(), $iav->fields['id']));
    }

    public function testLockDatabasesManufacturer()
    {
        $lockedfield = new \Lockedfield();

        //IMPORT rule
        $criteria = [
            [
                'condition' => 0,
                'criteria'  => 'itemtype',
                'pattern'   => 'DatabaseInstance',
            ], [
                'condition' => \RuleImportAsset::PATTERN_EXISTS,
                'criteria'  => 'name',
                'pattern'   => '1'
            ]
        ];
        $action = [
            'action_type' => 'assign',
            'field'       => '_inventory',
            'value'       => \RuleImportAsset::RULE_ACTION_LINK_OR_IMPORT
        ];
        $rule = new \RuleImportAsset();
        $collection = new \RuleImportAssetCollection();
        $rulecriteria = new \RuleCriteria();

        $input = [
            'is_active' => 1,
            'name'      => 'Database server import (by name)',
            'match'     => 'AND',
            'sub_type'  => 'RuleImportAsset',
        ];

        $rules_id = $rule->add($input);
        $this->assertGreaterThan(0, $rules_id);
        $this->assertTrue($collection->moveRule($rules_id, 0, $collection::MOVE_BEFORE));

        // Add criteria
        foreach ($criteria as $crit) {
            $input = [
                'rules_id'  => $rules_id,
                'criteria'  => $crit['criteria'],
                'pattern'   => $crit['pattern'],
                'condition' => $crit['condition'],
            ];
            $this->assertGreaterThan(0, (int)$rulecriteria->add($input));
        }

        // Add action
        $ruleaction = new \RuleAction();
        $input = [
            'rules_id'    => $rules_id,
            'action_type' => $action['action_type'],
            'field'       => $action['field'],
            'value'       => $action['value'],
        ];
        $this->assertGreaterThan(0, (int)$ruleaction->add($input));

        //UPDATE rule
        $criteria = [
            [
                'condition' => 0,
                'criteria'  => 'itemtype',
                'pattern'   => 'DatabaseInstance',
            ], [
                'condition' => \RuleImportAsset::PATTERN_FIND,
                'criteria'  => 'name',
                'pattern'   => '1'
            ], [
                'condition' => \RuleImportAsset::PATTERN_EXISTS,
                'criteria' => 'name',
                'pattern' => '1'
            ]
        ];
        $action = [
            'action_type' => 'assign',
            'field'       => '_inventory',
            'value'       => \RuleImportAsset::RULE_ACTION_LINK_OR_IMPORT
        ];
        $rule = new \RuleImportAsset();
        $collection = new \RuleImportAssetCollection();
        $rulecriteria = new \RuleCriteria();

        $input = [
            'is_active' => 1,
            'name'      => 'Database server update (by name)',
            'match'     => 'AND',
            'sub_type'  => 'RuleImportAsset',
        ];

        $prev_rules_id = $rules_id;
        $rules_id = $rule->add($input);
        $this->assertGreaterThan(0, $rules_id);
        $this->assertTrue($collection->moveRule($rules_id, $prev_rules_id, $collection::MOVE_BEFORE));

        // Add criteria
        foreach ($criteria as $crit) {
            $input = [
                'rules_id'  => $rules_id,
                'criteria'  => $crit['criteria'],
                'pattern'   => $crit['pattern'],
                'condition' => $crit['condition'],
            ];
            $this->assertGreaterThan(0, (int)$rulecriteria->add($input));
        }

        // Add action
        $ruleaction = new \RuleAction();
        $input = [
            'rules_id'    => $rules_id,
            'action_type' => $action['action_type'],
            'field'       => $action['field'],
            'value'       => $action['value'],
        ];
        $this->assertGreaterThan(0, (int)$ruleaction->add($input));

        //keep only postgresql
        $json = json_decode(file_get_contents(GLPI_ROOT . '/vendor/glpi-project/inventory_format/examples/computer_2_partial_dbs.json'));
        $pgsql = $json->content->databases_services[1];
        $services = [$pgsql];
        $json->content->databases_services = $services;

        $inventory = new \Glpi\Inventory\Inventory($json);
        if ($inventory->inError()) {
            $this->dump($inventory->getErrors());
        }
        $this->assertFalse($inventory->inError());
        $this->assertEmpty($inventory->getErrors());

        //check created databases & instances
        $this->assertSame(1, countElementsInTable(\DatabaseInstance::getTable()));

        //ensure database version has been updated
        $database = new \DatabaseInstance();
        $this->assertTrue($database->getFromDBByCrit(['name' => 'PostgreSQL 13']));
        $this->assertSame('13.2.3', $database->fields['version']);

        $manufacturer = new \Manufacturer();
        $this->assertTrue($manufacturer->getFromDBByCrit(['name' => 'PostgreSQL']));
        $manufacturers_id = $manufacturer->fields['id'];
        $this->assertSame($manufacturers_id, $database->fields['manufacturers_id']);

        $newmanufacturers_id = $manufacturer->add(['name' => 'For test']);
        $this->assertGreaterThan(0, $newmanufacturers_id);

        //manually update manufacturer to lock it
        $this->assertTrue(
            $database->update([
                'id' => $database->fields['id'],
                'manufacturers_id' => $newmanufacturers_id
            ])
        );
        $this->assertSame(['manufacturers_id' => null], $lockedfield->getLockedValues($database->getType(), $database->fields['id']));

        $json = json_decode(file_get_contents(GLPI_ROOT . '/vendor/glpi-project/inventory_format/examples/computer_2_partial_dbs.json'));
        $pgsql = $json->content->databases_services[1];
        $services = [$pgsql];
        $json->content->databases_services = $services;

        $inventory = new \Glpi\Inventory\Inventory($json);
        if ($inventory->inError()) {
            $this->dump($inventory->getErrors());
        }
        $this->assertFalse($inventory->inError());
        $this->assertEmpty($inventory->getErrors());

        //check created databases & instances
        $this->assertSame(1, countElementsInTable(\DatabaseInstance::getTable()));

        //make sure manufacturer is still the correct one
        $database = new \DatabaseInstance();
        $this->assertTrue($database->getFromDBByCrit(['name' => 'PostgreSQL 13']));
        $this->assertSame('13.2.3', $database->fields['version']);
        $this->assertSame($newmanufacturers_id, $database->fields['manufacturers_id']);
        $this->assertSame(['manufacturers_id' => 'PostgreSQL'], $lockedfield->getLockedValues($database->getType(), $database->fields['id']));
    }
}
