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

class Lockedfield extends DbTestCase
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
        $this->integer($cid)->isGreaterThan(0);

        $lockedfield = new \Lockedfield();
        $this->boolean($lockedfield->isHandled($computer))->isTrue();
        $this->array($lockedfield->getLockedValues($computer->getType(), $cid))->isEmpty();

        //update computer manually, to add a locked field
        $this->boolean(
            (bool)$computer->update(['id' => $cid, 'otherserial' => 'AZERTY'])
        )->isTrue();

        $this->boolean($computer->getFromDB($cid))->isTrue();
        $this->array($lockedfield->getLockedValues($computer->getType(), $cid))->isIdenticalTo(['otherserial' => null]);

        //ensure new dynamic update does not override otherserial again
        $this->boolean(
            (bool)$computer->update([
                'id' => $cid,
                'otherserial'  => '789012',
                'is_dynamic'   => 1
            ])
        )->isTrue();

        $this->boolean($computer->getFromDB($cid))->isTrue();
        $this->variable($computer->fields['otherserial'])->isEqualTo('AZERTY');
        $this->array($lockedfield->getLockedValues($computer->getType(), $cid))->isIdenticalTo(['otherserial' => '789012']);

        //ensure new dynamic update do not set new lock on regular update
        $this->boolean(
            (bool)$computer->update([
                'id' => $cid,
                'name'         => 'Computer name changed',
                'is_dynamic'   => 1
            ])
        )->isTrue();

        $this->boolean($computer->getFromDB($cid))->isTrue();
        $this->variable($computer->fields['name'])->isEqualTo('Computer name changed');
        $this->array($lockedfield->getLockedValues($computer->getType(), $cid))->isIdenticalTo(['otherserial' => '789012']);

        //ensure regular update do work on locked field
        $this->boolean(
            (bool)$computer->update(['id' => $cid, 'otherserial' => 'QWERTY'])
        )->isTrue();
        $this->boolean($computer->getFromDB($cid))->isTrue();
        $this->variable($computer->fields['otherserial'])->isEqualTo('QWERTY');
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
        $this->integer($cid)->isGreaterThan(0);

        $lockedfield = new \Lockedfield();
        $this->boolean($lockedfield->isHandled($computer))->isTrue();
        $this->array($lockedfield->getLockedValues($computer->getType(), $cid))->isEmpty();

        //add a global lock on otherserial field
        $this->integer(
            $lockedfield->add([
                'item' => 'Computer - otherserial'
            ])
        )->isGreaterThan(0);

        $this->boolean($computer->getFromDB($cid))->isTrue();
        $this->array($lockedfield->getLockedValues($computer->getType(), $cid))->isIdenticalTo(['otherserial' => null]);

        //ensure new dynamic update does not override otherserial again
        $this->boolean(
            (bool)$computer->update([
                'id' => $cid,
                'otherserial'  => 'changed',
                'is_dynamic' => 1
            ])
        )->isTrue();

        $this->boolean($computer->getFromDB($cid))->isTrue();
        $this->variable($computer->fields['otherserial'])->isEqualTo('789012');
        $this->array($lockedfield->getLockedValues($computer->getType(), $cid))->isIdenticalTo(['otherserial' => null]);

        //ensure new dynamic update do not set new lock on regular update
        $this->boolean(
            (bool)$computer->update([
                'id' => $cid,
                'name' => 'Computer name changed',
                'is_dynamic' => 1
            ])
        )->isTrue();

        $this->boolean($computer->getFromDB($cid))->isTrue();
        $this->variable($computer->fields['name'])->isEqualTo('Computer name changed');
        $this->array($lockedfield->getLockedValues($computer->getType(), $cid))->isIdenticalTo(['otherserial' => null]);

        //ensure regular update do work on locked field
        $this->boolean(
            (bool)$computer->update(['id' => $cid, 'otherserial' => 'QWERTY'])
        )->isTrue();
        $this->boolean($computer->getFromDB($cid))->isTrue();
        $this->variable($computer->fields['otherserial'])->isEqualTo('QWERTY');
    }

    /**
     * Check for global locks adding an itemtype
     */
    public function testGlobalLockAdd()
    {
        $lockedfield = new \Lockedfield();

        //add a global lock on otherserial field
        $this->integer(
            $lockedfield->add([
                'item' => 'Computer - otherserial'
            ])
        )->isGreaterThan(0);

        $computer = new \Computer();
        $cid = (int)$computer->add([
            'name'         => 'Computer from inventory',
            'serial'       => '123456',
            'otherserial'  => '789012',
            'entities_id'  => 0,
            'is_dynamic'   => 1
        ]);
        $this->integer($cid)->isGreaterThan(0);

        $this->boolean($computer->getFromDB($cid))->isTrue();
        $this->variable($computer->fields['otherserial'])->isEqualTo('');

        $this->boolean($lockedfield->isHandled($computer))->isTrue();
        //lockedfield value must be null because it's a global lock
        $this->array($lockedfield->getLockedValues($computer->getType(), $cid))->isIdenticalTo(['otherserial' => null]);

        //ensure new dynamic update does not override otherserial again
        $this->boolean(
            (bool)$computer->update([
                'id' => $cid,
                'otherserial'  => 'changed',
                'is_dynamic' => 1
            ])
        )->isTrue();

        $this->boolean($computer->getFromDB($cid))->isTrue();
        $this->variable($computer->fields['otherserial'])->isEqualTo('');
        //lockedfield must be null because it's a global lock
        $this->array($lockedfield->getLockedValues($computer->getType(), $cid))->isIdenticalTo(['otherserial' => null]);

        //ensure new dynamic update do not set new lock on regular update
        $this->boolean(
            (bool)$computer->update([
                'id' => $cid,
                'name' => 'Computer name changed',
                'is_dynamic' => 1
            ])
        )->isTrue();

        $this->boolean($computer->getFromDB($cid))->isTrue();
        $this->variable($computer->fields['name'])->isEqualTo('Computer name changed');
        //lockedfield must be null because it's a global lock
        $this->array($lockedfield->getLockedValues($computer->getType(), $cid))->isIdenticalTo(['otherserial' => null]);

        //ensure regular update do work on locked field
        $this->boolean(
            (bool)$computer->update(['id' => $cid, 'otherserial' => 'QWERTY'])
        )->isTrue();
        $this->boolean($computer->getFromDB($cid))->isTrue();
        $this->variable($computer->fields['otherserial'])->isEqualTo('QWERTY');
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
        $this->integer(
            $lockedfield->add([
                'item' => 'Computer - manufacturers_id'
            ])
        )->isGreaterThan(0);

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $inventory = new \Glpi\Inventory\Inventory($json);

        if ($inventory->inError()) {
            $this->dump($inventory->getErrors());
        }
        $this->boolean($inventory->inError())->isFalse();
        $this->array($inventory->getErrors())->isEmpty();

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
        $this->string($iterator->current()['name'])->isIdenticalTo('Computer import (by serial + uuid)');

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
            ->string['deviceid']->isIdenticalTo('glpixps.teclib.infra-2018-10-03-08-42-36')
            ->string['itemtype']->isIdenticalTo('Computer');

        //check created computer
        $computers_id = $agent['items_id'];

        $this->integer($computers_id)->isGreaterThan(0);
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($computers_id))->isTrue();
        $this->integer($computer->fields['manufacturers_id'])->isEqualTo(0);

        //ensure no new manufacturer has been added
        $this->integer(countElementsInTable(\Manufacturer::getTable()))->isIdenticalTo($existing_manufacturers);
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
        $this->boolean($inventory->inError())->isFalse();
        $this->array($inventory->getErrors())->isEmpty();

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
        $this->string($iterator->current()['name'])->isIdenticalTo('Printer import (by serial)');

        $printers_id = $inventory->getItem()->fields['id'];
        $this->integer($printers_id)->isGreaterThan(0);

        $printer = new \Printer();
        $this->boolean($printer->getFromDB($printers_id))->isTrue();
        $this->integer($printer->fields['locations_id'])->isEqualTo(0);

        //ensure no new location has been added
        $this->integer(countElementsInTable(\Location::getTable()))->isIdenticalTo($existing_locations);

        //manually update to lock locations_id field
        $locations_id = getItemByTypeName('Location', '_location02', true);
        $this->boolean(
            $printer->update([
                'id' => $printers_id,
                'locations_id' => $locations_id
            ])
        )->isTrue();
        $this->array($lockedfield->getLockedValues($printer->getType(), $printers_id))->isIdenticalTo(['locations_id' => null]);

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
        $this->boolean($inventory->inError())->isFalse();
        $this->array($inventory->getErrors())->isEmpty();

        $this->boolean($printer->getFromDB($printers_id))->isTrue();
        $this->integer($printer->fields['locations_id'])->isEqualTo($locations_id);

        //ensure no new location has been added
        $this->integer(countElementsInTable(\Location::getTable()))->isIdenticalTo($existing_locations);

        $this->array($lockedfield->getLockedValues($printer->getType(), $printers_id))->isIdenticalTo(['locations_id' => 'Greffe Charron']);
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
        $this->integer(
            $lockedfield->add([
                'item' => 'Printer - locations_id'
            ])
        )->isGreaterThan(0);

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $inventory = new \Glpi\Inventory\Inventory($json);

        if ($inventory->inError()) {
            $this->dump($inventory->getErrors());
        }
        $this->boolean($inventory->inError())->isFalse();
        $this->array($inventory->getErrors())->isEmpty();

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
        $this->string($iterator->current()['name'])->isIdenticalTo('Printer import (by serial)');

        $printers_id = $inventory->getItem()->fields['id'];
        $this->integer($printers_id)->isGreaterThan(0);

        $printer = new \Printer();
        $this->boolean($printer->getFromDB($printers_id))->isTrue();
        $this->integer($printer->fields['locations_id'])->isEqualTo(0);

        //ensure no new location has been added
        $this->integer(countElementsInTable(\Location::getTable()))->isIdenticalTo($existing_locations);
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
        $this->boolean($inventory->inError())->isFalse();
        $this->array($inventory->getErrors())->isEmpty();

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
        $this->string($iterator->current()['name'])->isIdenticalTo('Printer import (by serial)');

        $printers_id = $inventory->getItem()->fields['id'];
        $this->integer($printers_id)->isGreaterThan(0);

        $printer = new \Printer();
        $this->boolean($printer->getFromDB($printers_id))->isTrue();
        $this->integer($printer->fields['locations_id'])->isGreaterThan(0);

        //ensure new location has been added
        ++$existing_locations;
        $this->integer(countElementsInTable(\Location::getTable()))->isIdenticalTo($existing_locations);

        //manually update to lock locations_id field
        $locations_id = getItemByTypeName('Location', '_location02', true);
        $this->boolean(
            $printer->update([
                'id' => $printers_id,
                'locations_id' => $locations_id
            ])
        )->isTrue();
        $this->array($lockedfield->getLockedValues($printer->getType(), $printers_id))->isIdenticalTo(['locations_id' => null]);

        //Replay, with a location, to ensure location has not been updated, and locked value is correct.
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $inventory = new \Glpi\Inventory\Inventory($json);

        if ($inventory->inError()) {
            $this->dump($inventory->getErrors());
        }
        $this->boolean($inventory->inError())->isFalse();
        $this->array($inventory->getErrors())->isEmpty();

        $this->boolean($printer->getFromDB($printers_id))->isTrue();
        $this->integer($printer->fields['locations_id'])->isEqualTo($locations_id);

        //ensure no new location has been added
        $this->integer(countElementsInTable(\Location::getTable()))->isIdenticalTo($existing_locations);

        $this->array($lockedfield->getLockedValues($printer->getType(), $printers_id))->isIdenticalTo(['locations_id' => 'Greffe Charron']);
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
        $this->boolean($inventory->inError())->isFalse();
        $this->array($inventory->getErrors())->isEmpty();

        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);

        $this->boolean($computer->getFromDB($computers_id))->isTrue();

        $this->boolean($cos->getFromDBByCrit(['items_id' => $computers_id]))->isTrue();

        //check OS version
        $this->boolean($aos->getFromDBByCrit(['name' => 'x86_64']))->isTrue();
        $archs_id = $aos->fields['id'];
        $this->integer($cos->fields['operatingsystemarchitectures_id'])->isIdenticalTo($archs_id);

        //check antivirus manufacturer
        $this->boolean($iav->getFromDBByCrit(['computers_id' => $computers_id]))->isTrue();
        $this->boolean($manufacturer->getFromDBByCrit(['name' => 'Microsoft Corporation']))->isTrue();
        $manufacturers_id = $manufacturer->fields['id'];
        $this->integer($iav->fields['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        //manually update OS architecture field
        $newarchs_id = $aos->add(['name' => 'i386']);
        $this->integer($newarchs_id)->isGreaterThan(0);

        $this->boolean(
            $cos->update([
                'id' => $cos->fields['id'],
                'operatingsystemarchitectures_id' => $newarchs_id
            ])
        )->isTrue();
        $this->array($lockedfield->getLockedValues($cos->getType(), $cos->fields['id']))->isIdenticalTo(['operatingsystemarchitectures_id' => null]);

        //manually update AV manufacturer field
        $newmanufacturers_id = $manufacturer->add(['name' => 'Crosoft']);
        $this->integer($newmanufacturers_id)->isGreaterThan(0);

        $this->boolean(
            $iav->update([
                'id' => $iav->fields['id'],
                'manufacturers_id' => $newmanufacturers_id
            ])
        )->isTrue();
        $this->array($lockedfield->getLockedValues($iav->getType(), $iav->fields['id']))->isIdenticalTo(['manufacturers_id' => null]);

        //replay
        $data = $converter->convert($xml);
        $json = json_decode($data);
        $inventory = new \Glpi\Inventory\Inventory($json);

        if ($inventory->inError()) {
            $this->dump($inventory->getErrors());
        }
        $this->boolean($inventory->inError())->isFalse();
        $this->array($inventory->getErrors())->isEmpty();

        //make sure architecture is still the correct one
        $this->boolean($cos->getFromDBByCrit(['items_id' => $computers_id]))->isTrue();
        $this->integer($cos->fields['operatingsystemarchitectures_id'])->isIdenticalTo($newarchs_id);

        $this->array($lockedfield->getLockedValues($cos->getType(), $cos->fields['id']))->isIdenticalTo(['operatingsystemarchitectures_id' => 'x86_64']);

        //make sure manufacturer is still the correct one
        $this->boolean($iav->getFromDBByCrit(['computers_id' => $computers_id]))->isTrue();
        $this->integer($iav->fields['manufacturers_id'])->isIdenticalTo($newmanufacturers_id);

        $this->array($lockedfield->getLockedValues($iav->getType(), $iav->fields['id']))->isIdenticalTo(['manufacturers_id' => 'Microsoft Corporation']);
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
        $this->integer($rules_id)->isGreaterThan(0);
        $this->boolean($collection->moveRule($rules_id, 0, $collection::MOVE_BEFORE))->isTrue();

        // Add criteria
        foreach ($criteria as $crit) {
            $input = [
                'rules_id'  => $rules_id,
                'criteria'  => $crit['criteria'],
                'pattern'   => $crit['pattern'],
                'condition' => $crit['condition'],
            ];
            $this->integer((int)$rulecriteria->add($input))->isGreaterThan(0);
        }

        // Add action
        $ruleaction = new \RuleAction();
        $input = [
            'rules_id'    => $rules_id,
            'action_type' => $action['action_type'],
            'field'       => $action['field'],
            'value'       => $action['value'],
        ];
        $this->integer((int)$ruleaction->add($input))->isGreaterThan(0);

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
        $this->integer($rules_id)->isGreaterThan(0);
        $this->boolean($collection->moveRule($rules_id, $prev_rules_id, $collection::MOVE_BEFORE))->isTrue();

        // Add criteria
        foreach ($criteria as $crit) {
            $input = [
                'rules_id'  => $rules_id,
                'criteria'  => $crit['criteria'],
                'pattern'   => $crit['pattern'],
                'condition' => $crit['condition'],
            ];
            $this->integer((int)$rulecriteria->add($input))->isGreaterThan(0);
        }

        // Add action
        $ruleaction = new \RuleAction();
        $input = [
            'rules_id'    => $rules_id,
            'action_type' => $action['action_type'],
            'field'       => $action['field'],
            'value'       => $action['value'],
        ];
        $this->integer((int)$ruleaction->add($input))->isGreaterThan(0);

        //keep only postgresql
        $json = json_decode(file_get_contents(GLPI_ROOT . '/vendor/glpi-project/inventory_format/examples/computer_2_partial_dbs.json'));
        $pgsql = $json->content->databases_services[1];
        $services = [$pgsql];
        $json->content->databases_services = $services;

        $inventory = new \Glpi\Inventory\Inventory($json);
        if ($inventory->inError()) {
            $this->dump($inventory->getErrors());
        }
        $this->boolean($inventory->inError())->isFalse();
        $this->array($inventory->getErrors())->isEmpty();

        //check created databases & instances
        $this->integer(countElementsInTable(\DatabaseInstance::getTable()))->isIdenticalTo(1);

        //ensure database version has been updated
        $database = new \DatabaseInstance();
        $this->boolean($database->getFromDBByCrit(['name' => 'PostgreSQL 13']))->isTrue();
        $this->string($database->fields['version'])->isIdenticalTo('13.2.3');

        $manufacturer = new \Manufacturer();
        $this->boolean($manufacturer->getFromDBByCrit(['name' => 'PostgreSQL']))->isTrue();
        $manufacturers_id = $manufacturer->fields['id'];
        $this->integer($database->fields['manufacturers_id'])->isIdenticalTo($manufacturers_id);

        $newmanufacturers_id = $manufacturer->add(['name' => 'For test']);
        $this->integer($newmanufacturers_id)->isGreaterThan(0);

        //manually update manufacturer to lock it
        $this->boolean(
            $database->update([
                'id' => $database->fields['id'],
                'manufacturers_id' => $newmanufacturers_id
            ])
        )->isTrue();
        $this->array($lockedfield->getLockedValues($database->getType(), $database->fields['id']))->isIdenticalTo(['manufacturers_id' => null]);

        $json = json_decode(file_get_contents(GLPI_ROOT . '/vendor/glpi-project/inventory_format/examples/computer_2_partial_dbs.json'));
        $pgsql = $json->content->databases_services[1];
        $services = [$pgsql];
        $json->content->databases_services = $services;

        $inventory = new \Glpi\Inventory\Inventory($json);
        if ($inventory->inError()) {
            $this->dump($inventory->getErrors());
        }
        $this->boolean($inventory->inError())->isFalse();
        $this->array($inventory->getErrors())->isEmpty();

        //check created databases & instances
        $this->integer(countElementsInTable(\DatabaseInstance::getTable()))->isIdenticalTo(1);

        //make sure manufacturer is still the correct one
        $database = new \DatabaseInstance();
        $this->boolean($database->getFromDBByCrit(['name' => 'PostgreSQL 13']))->isTrue();
        $this->string($database->fields['version'])->isIdenticalTo('13.2.3');
        $this->integer($database->fields['manufacturers_id'])->isIdenticalTo($newmanufacturers_id);
        $this->array($lockedfield->getLockedValues($database->getType(), $database->fields['id']))->isIdenticalTo(['manufacturers_id' => 'PostgreSQL']);
    }
}
