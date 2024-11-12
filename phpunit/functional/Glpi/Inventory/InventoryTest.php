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

namespace tests\units\Glpi\Inventory;

use InventoryTestCase;
use Item_OperatingSystem;
use Lockedfield;
use OperatingSystem;
use OperatingSystemArchitecture;
use OperatingSystemServicePack;
use OperatingSystemVersion;
use RuleCriteria;
use wapmorgan\UnifiedArchive\UnifiedArchive;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class InventoryTest extends InventoryTestCase
{
    private function checkComputer1($computers_id)
    {
        global $DB;

        //get computer models, manufacturer, ...
        $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->current();
        $this->assertIsArray($autoupdatesystems);
        $autoupdatesystems_id = $autoupdatesystems['id'];

        $cmodels = $DB->request(['FROM' => \ComputerModel::getTable(), 'WHERE' => ['name' => 'XPS 13 9350']])->current();
        $this->assertIsArray($cmodels);
        $computermodels_id = $cmodels['id'];

        $ctypes = $DB->request(['FROM' => \ComputerType::getTable(), 'WHERE' => ['name' => 'Laptop']])->current();
        $this->assertIsArray($ctypes);
        $computertypes_id = $ctypes['id'];

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Dell Inc.']])->current();
        $this->assertIsArray($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $this->assertGreaterThan(0, $computers_id);
        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($computers_id));

        $expected = [
            'id' => $computers_id,
            'entities_id' => 0,
            'name' => 'glpixps',
            'serial' => '640HP72',
            'otherserial' => null,
            'contact' => 'trasher/root',
            'contact_num' => null,
            'users_id_tech' => 0,
            'groups_id_tech' => 0,
            'comment' => null,
            'date_mod' => $computer->fields['date_mod'],
            'autoupdatesystems_id' => $autoupdatesystems_id,
            'locations_id' => 0,
            'networks_id' => 0,
            'computermodels_id' => $computermodels_id,
            'computertypes_id' => $computertypes_id,
            'is_template' => 0,
            'template_name' => null,
            'manufacturers_id' => $manufacturers_id,
            'is_deleted' => 0,
            'is_dynamic' => 1,
            'users_id' => 0,
            'groups_id' => 0,
            'states_id' => 0,
            'ticket_tco' => '0.0000',
            'uuid' => '4c4c4544-0034-3010-8048-b6c04f503732',
            'date_creation' => $computer->fields['date_creation'],
            'is_recursive' => 0,
            'last_inventory_update' => $computer->fields['last_inventory_update'],
            'last_boot' => '2020-06-09 07:58:08',
        ];
        $this->assertIsArray($computer->fields);
        $this->assertSame($expected, $computer->fields);

        //operating system
        $ios = new \Item_OperatingSystem();
        $iterator = $ios->getFromItem($computer);
        $record = $iterator->current();

        $expected = [
            'assocID' => $record['assocID'],
            'name' => 'Fedora 31 (Workstation Edition)',
            'version' => '31 (Workstation Edition)',
            'architecture' => 'x86_64',
            'servicepack' => null,
        ];
        $this->assertIsArray($record);
        $this->assertSame($expected, $record);

       //remote management
        $mgmt = new \Item_RemoteManagement();
        $iterator = $mgmt->getFromItem($computer);
        $this->assertCount(1, $iterator);
        $remote = $iterator->current();
        unset($remote['id']);
        $this->assertSame(
            [
                'itemtype' => $computer->getType(),
                'items_id' => $computer->fields['id'],
                'remoteid' => '123456789',
                'type' => 'teamviewer',
                'is_dynamic' => 1,
                'is_deleted' => 0
            ],
            $remote
        );

        //connections
        $iterator = \Computer_Item::getTypeItems($computers_id, 'Monitor');
        $this->assertCount(1, $iterator);
        $monitor_link = $iterator->current();
        unset($monitor_link['date_mod']);
        unset($monitor_link['date_creation']);

        $mmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Sharp Corporation']])->current();
        $this->assertIsArray($mmanuf);
        $manufacturers_id = $mmanuf['id'];

        $mmodel = $DB->request(['FROM' => \MonitorModel::getTable(), 'WHERE' => ['name' => 'DJCP6']])->current();
        $this->assertIsArray($mmodel);
        $models_id = $mmodel['id'];

        $expected = [
            'id' => $monitor_link['id'],
            'entities_id' => 0,
            'name' => 'DJCP6',
            'contact' => 'trasher/root',
            'contact_num' => null,
            'users_id_tech' => 0,
            'groups_id_tech' => 0,
            'comment' => null,
            'serial' => 'ABH55D',
            'otherserial' => null,
            'size' => '0.00',
            'have_micro' => 0,
            'have_speaker' => 0,
            'have_subd' => 0,
            'have_bnc' => 0,
            'have_dvi' => 0,
            'have_pivot' => 0,
            'have_hdmi' => 0,
            'have_displayport' => 0,
            'locations_id' => 0,
            'monitortypes_id' => 0,
            'monitormodels_id' => $models_id,
            'manufacturers_id' => $manufacturers_id,
            'is_global' => 0,
            'is_deleted' => 0,
            'is_template' => 0,
            'template_name' => null,
            'users_id' => 0,
            'groups_id' => 0,
            'states_id' => 0,
            'ticket_tco' => '0.0000',
            'is_dynamic' => 1,
            'autoupdatesystems_id' => 0,
            'uuid' => null,
            'is_recursive' => 0,
            'linkid' => $monitor_link['linkid'],
            'glpi_computers_items_is_dynamic' => 1,
            'entity' => 0,
        ];
        $this->assertIsArray($monitor_link);
        $this->assertSame($expected, $monitor_link);

        $monitor = new \Monitor();
        $this->assertTrue($monitor->getFromDB($monitor_link['id']));
        $this->assertTrue((bool)$monitor->fields['is_dynamic']);
        $this->assertSame('DJCP6', $monitor->fields['name']);

        //check network ports
        $iterator = $DB->request([
            'FROM'   => \NetworkPort::getTable(),
            'WHERE'  => [
                'items_id'           => $computers_id,
                'itemtype'           => 'Computer',
            ],
        ]);
        $this->assertCount(5, $iterator);

        $expecteds = [
            [
                'logical_number' => 0,
                'name' => 'lo',
                'instantiation_type' => 'NetworkPortLocal',
                'mac' => '00:00:00:00:00:00',
                'ifinternalstatus' => '1',
            ], [
                'logical_number' => 1,
                'name' => 'enp57s0u1u4',
                'instantiation_type' => 'NetworkPortEthernet',
                'mac' => '00:e0:4c:68:01:db',
                'ifstatus' => '1',
                'ifinternalstatus' => '1',
            ], [
                'logical_number' => 1,
                'name' => 'wlp58s0',
                'instantiation_type' => 'NetworkPortWifi',
                'mac' => '44:85:00:2b:90:bc',
                'ifinternalstatus' => '1',
            ], [
                'logical_number' => 0,
                'name' => 'virbr0',
                'instantiation_type' => 'NetworkPortEthernet',
                'mac' => '52:54:00:fa:20:0e',
                'ifstatus' => '2',
                'ifinternalstatus' => '1',
            ], [
                'logical_number' => 0,
                'name' => 'virbr0-nic',
                'instantiation_type' => null,
                'mac' => '52:54:00:fa:20:0e',
                'ifstatus' => '2',
                'ifinternalstatus' => '2',
            ]
        ];

        $ips = [
            'lo'  => [
                'v4'   => '127.0.0.1',
                'v6'   => '::1'
            ],
            'enp57s0u1u4'  => [
                'v4'   => '192.168.1.142',
                'v6'   => 'fe80::b283:4fa3:d3f2:96b1'
            ],
            'wlp58s0'   => [
                'v4'   => '192.168.1.118',
                'v6'   => 'fe80::92a4:26c6:99dd:2d60'
            ],
            'virbr0' => [
                'v4'   => '192.168.122.1'
            ]
        ];

        $i = 0;
        $netport = new \NetworkPort();
        foreach ($iterator as $port) {
            $ports_id = $port['id'];
            $this->assertTrue($netport->getFromDB($ports_id));
            $instantiation = $netport->getInstantiation();
            if ($port['instantiation_type'] === null) {
                $this->assertFalse($instantiation);
            } else {
                $this->assertInstanceOf($port['instantiation_type'], $instantiation);
            }

            unset($port['id']);
            unset($port['date_creation']);
            unset($port['date_mod']);
            unset($port['comment']);

            $expected = $expecteds[$i];
            $expected = $expected + [
                'items_id' => $computers_id,
                'itemtype' => 'Computer',
                'entities_id' => 0,
                'is_recursive' => 0,
                'is_deleted' => 0,
                'is_dynamic' => 1,
                'ifmtu' => 0,
                'ifspeed' => 0,
                'ifinternalstatus' => null,
                'ifconnectionstatus' => 0,
                'iflastchange' => null,
                'ifinbytes' => 0,
                'ifinerrors' => 0,
                'ifoutbytes' => 0,
                'ifouterrors' => 0,
                'ifstatus' => null,
                'ifdescr' => null,
                'ifalias' => null,
                'portduplex' => null,
                'trunk' => 0,
                'lastup' => null
            ];

            $this->assertIsArray($port);
            $this->assertEquals($expected, $port);
            ++$i;

            //check for ips
            $ip_iterator = $DB->request([
                'SELECT'       => [
                    \IPAddress::getTable() . '.name',
                    \IPAddress::getTable() . '.version'
                ],
                'FROM'   => \IPAddress::getTable(),
                'INNER JOIN'   => [
                    \NetworkName::getTable()   => [
                        'ON'  => [
                            \IPAddress::getTable()     => 'items_id',
                            \NetworkName::getTable()   => 'id', [
                                'AND' => [\IPAddress::getTable() . '.itemtype'  => \NetworkName::getType()]
                            ]
                        ]
                    ]
                ],
                'WHERE'  => [
                    \NetworkName::getTable() . '.itemtype'  => \NetworkPort::getType(),
                    \NetworkName::getTable() . '.items_id'  => $ports_id
                ]
            ]);

            $this->assertCount(count($ips[$port['name']] ?? []), $ip_iterator);
            if (isset($ips[$port['name']])) {
                //FIXME: missing all ipv6 :(
                $ip = $ip_iterator->current();
                $this->assertSame(4, (int)$ip['version']);
                $this->assertSame($ips[$port['name']]['v4'], $ip['name']);
            }
        }

        //check for components
        $components = [];
        $allcount = 0;
        foreach (\Item_Devices::getItemAffinities('Computer') as $link_type) {
            $link        = getItemForItemtype($link_type);
            $iterator = $DB->request($link->getTableGroupCriteria($computer));
            $allcount += count($iterator);
            $components[$link_type] = [];

            foreach ($iterator as $row) {
                $lid = $row['id'];
                unset($row['id']);
                $components[$link_type][$lid] = $row;
            }
        }

        $expecteds = [
            'Item_DeviceMotherboard' => 0,
            'Item_DeviceFirmware' => 1,
            'Item_DeviceProcessor' => 1,
            'Item_DeviceMemory' => 2,
            'Item_DeviceHardDrive' => 1,
            'Item_DeviceNetworkCard' => 0,
            'Item_DeviceDrive' => 0,
            'Item_DeviceBattery' => 1,
            'Item_DeviceGraphicCard' => 0,
            'Item_DeviceSoundCard' => 1,
            'Item_DeviceControl' => 25,
            'Item_DevicePci' => 0,
            'Item_DeviceCase' => 0,
            'Item_DevicePowerSupply' => 0,
            'Item_DeviceGeneric' => 0,
            'Item_DeviceSimcard' => 0,
            'Item_DeviceSensor' => 0
        ];

        foreach ($expecteds as $type => $count) {
            $this->assertSame(
                $count,
                count($components[$type]),
                sprintf(
                    'Expected %1$s %2$s, got %3$s',
                    $count,
                    $type,
                    count($components[$type])
                )
            );
        }

        $expecteds = [
            'Item_DeviceMotherboard' => [],
            'Item_DeviceFirmware' => [
                [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicefirmwares_id' => 104,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ]
            ],
            'Item_DeviceProcessor' =>
               [
                   [
                       'items_id' => $computers_id,
                       'itemtype' => 'Computer',
                       'deviceprocessors_id' => 3060400,
                       'frequency' => 2300,
                       'serial' => null,
                       'is_deleted' => 0,
                       'is_dynamic' => 1,
                       'nbcores' => 2,
                       'nbthreads' => 4,
                       'entities_id' => 0,
                       'is_recursive' => 0,
                       'busID' => null,
                       'otherserial' => null,
                       'locations_id' => 0,
                       'states_id' => 0,
                   ],
               ],
            'Item_DeviceMemory' =>
               [
                   [
                       'items_id' => $computers_id,
                       'itemtype' => 'Computer',
                       'devicememories_id' => 104,
                       'size' => 4096,
                       'serial' => '12161217',
                       'is_deleted' => 0,
                       'is_dynamic' => 1,
                       'entities_id' => 0,
                       'is_recursive' => 0,
                       'busID' => '1',
                       'otherserial' => null,
                       'locations_id' => 0,
                       'states_id' => 0,
                   ], [
                       'items_id' => $computers_id,
                       'itemtype' => 'Computer',
                       'devicememories_id' => 104,
                       'size' => 4096,
                       'serial' => '12121212',
                       'is_deleted' => 0,
                       'is_dynamic' => 1,
                       'entities_id' => 0,
                       'is_recursive' => 0,
                       'busID' => '2',
                       'otherserial' => null,
                       'locations_id' => 0,
                       'states_id' => 0,
                   ],
               ],
            'Item_DeviceHardDrive' => [
                [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'deviceharddrives_id' => 104,
                    'capacity' => 256060,
                    'serial' => 'S29NNXAH146409',
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ],
            ],
            'Item_DeviceNetworkCard' => [],
            'Item_DeviceDrive' => [],
            // 'Item_DeviceBattery' is not tested here, see self::checkComputer1Batteries()
            'Item_DeviceGraphicCard' => [],
            'Item_DeviceSoundCard' => [
                [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicesoundcards_id' => 104,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ],
            ],
            'Item_DeviceControl' => [
                [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicecontrols_id' => 2246,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => 'xyz',
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ], [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicecontrols_id' => 2247,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ], [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicecontrols_id' => 2248,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ], [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicecontrols_id' => 2249,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ], [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicecontrols_id' => 2250,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ], [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicecontrols_id' => 2251,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ], [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicecontrols_id' => 2252,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ], [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicecontrols_id' => 2253,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ], [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicecontrols_id' => 2254,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ], [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicecontrols_id' => 2255,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ], [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicecontrols_id' => 2256,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ], [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicecontrols_id' => 2257,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ], [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicecontrols_id' => 2258,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ], [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicecontrols_id' => 2259,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ], [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicecontrols_id' => 2260,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ], [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicecontrols_id' => 2261,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ], [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicecontrols_id' => 2262,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ], [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicecontrols_id' => 2263,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ], [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicecontrols_id' => 2263,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ], [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicecontrols_id' => 2263,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ], [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicecontrols_id' => 2263,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ], [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicecontrols_id' => 2264,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ], [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicecontrols_id' => 2265,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ], [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicecontrols_id' => 2266,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ], [
                    'items_id' => $computers_id,
                    'itemtype' => 'Computer',
                    'devicecontrols_id' => 2267,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ],
            ],
            'Item_DevicePci' => [],
            'Item_DeviceCase' => [],
            'Item_DevicePowerSupply' => [],
            'Item_DeviceGeneric' => [],
            'Item_DeviceSimcard' => [],
            'Item_DeviceSensor' => [],
        ];

        foreach ($expecteds as $type => $expected) {
            $component = array_values($components[$type]);
           //hack to replace expected fkeys
            foreach ($expected as $i => &$row) {
                foreach (array_keys($row) as $key) {
                    if (isForeignKeyField($key)) {
                        $row[$key] = $component[$i][$key];
                    }
                }
            }
            $this->assertIsArray($component);
            $this->assertSame($expected, $component);
        }

        //check printer
        $iterator = \Computer_Item::getTypeItems($computers_id, 'Printer');
        $this->assertCount(1, $iterator);
        $printer_link = $iterator->current();
        unset($printer_link['date_mod'], $printer_link['date_creation']);

        $expected = [
            'id' => $printer_link['id'],
            'entities_id' => 0,
            'is_recursive' => 0,
            'name' => 'Officejet_Pro_8600_34AF9E_',
            'contact' => 'trasher/root',
            'contact_num' => null,
            'users_id_tech' => 0,
            'groups_id_tech' => 0,
            'serial' => 'MY47L1W1JHEB6',
            'otherserial' => null,
            'have_serial' => 0,
            'have_parallel' => 0,
            'have_usb' => 0,
            'have_wifi' => 0,
            'have_ethernet' => 0,
            'comment' => null,
            'memory_size' => null,
            'locations_id' => 0,
            'networks_id' => 0,
            'printertypes_id' => 0,
            'printermodels_id' => 0,
            'manufacturers_id' => 0,
            'is_global' => 0,
            'is_deleted' => 0,
            'is_template' => 0,
            'template_name' => null,
            'init_pages_counter' => 0,
            'last_pages_counter' => 0,
            'users_id' => 0,
            'groups_id' => 0,
            'states_id' => 0,
            'ticket_tco' => '0.0000',
            'is_dynamic' => 1,
            'uuid' => null,
            'sysdescr' => null,
            'last_inventory_update' => $_SESSION['glpi_currenttime'],
            'snmpcredentials_id' => 0,
            'autoupdatesystems_id' => $autoupdatesystems_id,
            'linkid' => $printer_link['linkid'],
            'glpi_computers_items_is_dynamic' => 1,
            'entity' => 0,
        ];
        $this->assertIsArray($printer_link);
        $this->assertSame($expected, $printer_link);

        $printer = new \Printer();
        $this->assertTrue($printer->getFromDB($printer_link['id']));
        $this->assertTrue((bool)$printer->fields['is_dynamic']);
        $this->assertSame('Officejet_Pro_8600_34AF9E_', $printer->fields['name']);

        return $computer;
    }

    private function checkComputer1Volumes(\Computer $computer, array $freesizes = [])
    {
        $idisks = new \Item_Disk();
        $iterator = $idisks->getFromItem($computer);
        $this->assertCount(6, $iterator);

        $expecteds = [
            [
                'fsname' => 'ext4',
                'name' => '/',
                'device' => '/dev/mapper/xps-root',
                'mountpoint' => '/',
                'filesystems_id' => 4,
                'totalsize' => 40189,
                'freesize' => 11683,
                'encryption_status' => 1,
                'encryption_tool' => 'LUKS1',
                'encryption_algorithm' => 'aes-xts-plain64',
                'encryption_type' => null,
            ], [
                'fsname' => 'ext4',
                'name' => '/var/www',
                'device' => '/dev/mapper/xps-www',
                'mountpoint' => '/var/www',
                'filesystems_id' => 4,
                'totalsize' => 20030,
                'freesize' => 11924,
                'encryption_status' => 0,
                'encryption_tool' => null,
                'encryption_algorithm' => null,
                'encryption_type' => null,
            ], [
                'fsname' => 'ext4',
                'name' => '/boot',
                'device' => '/dev/nvme0n1p2',
                'mountpoint' => '/boot',
                'filesystems_id' => 4,
                'totalsize' => 975,
                'freesize' => 703,
                'encryption_status' => 0,
                'encryption_tool' => null,
                'encryption_algorithm' => null,
                'encryption_type' => null,
            ], [
                'fsname' => 'ext4',
                'name' => '/var/lib/mysql',
                'device' => '/dev/mapper/xps-maria',
                'mountpoint' => '/var/lib/mysql',
                'filesystems_id' => 4,
                'totalsize' => 20030,
                'freesize' => 15740,
                'encryption_status' => 1,
                'encryption_tool' => 'LUKS1',
                'encryption_algorithm' => 'aes-xts-plain64',
                'encryption_type' => null,
            ], [
                'fsname' => 'ext4',
                'name' => '/home',
                'device' => '/dev/mapper/xps-home',
                'mountpoint' => '/home',
                'filesystems_id' => 4,
                'totalsize' => 120439,
                'freesize' => 24872,
                'encryption_status' => 1,
                'encryption_tool' => 'LUKS1',
                'encryption_algorithm' => 'aes-xts-plain64',
                'encryption_type' => null,
            ], [
                'fsname' => 'VFAT',
                'name' => '/boot/efi',
                'device' => '/dev/nvme0n1p1',
                'mountpoint' => '/boot/efi',
                'filesystems_id' => 7,
                'totalsize' => 199,
                'freesize' => 191,
                'encryption_status' => 0,
                'encryption_tool' => null,
                'encryption_algorithm' => null,
                'encryption_type' => null,
            ]
        ];

        $i = 0;
        foreach ($iterator as $volume) {
            unset($volume['id'], $volume['date_mod'], $volume['date_creation']);
            $expected = $expecteds[$i];
            if (count($freesizes)) {
                $expected['freesize'] = $freesizes[$i];
            }
            $expected += [
                'items_id' => $computer->fields['id'],
                'itemtype' => 'Computer',
                'entities_id' => 0,
                'is_deleted' => 0,
                'is_dynamic' => 1
            ];

            ksort($expected);
            ksort($volume);

            $this->assertIsArray($volume);
            $this->assertEquals($expected, $volume);
            ++$i;
        }
    }

    private function checkComputer1Softwares(\Computer $computer, array $versions = [])
    {
        $isoft = new \Item_SoftwareVersion();
        $iterator = $isoft->getFromItem($computer);
        $this->assertCount(7, $iterator);

        $expecteds = [
            [
                'softname' => 'expat',
                'version' => '2.2.8-1.fc31',
                'dateinstall' => '2019-12-19',
            ],[
                'softname' => 'Fedora 31 (Workstation Edition)',
                'version' => '31 (Workstation Edition)',
                'dateinstall' => null,
            ], [
                'softname' => 'gettext',
                'version' => '0.20.1-3.fc31',
                'dateinstall' => '2020-01-15',
            ], [
                'softname' => 'gitg',
                'version' => '3.32.1-1.fc31',
                'dateinstall' => '2019-12-19',
            ], [
                'softname' => 'gnome-calculator',
                'version' => '3.34.1-1.fc31',
                'dateinstall' => '2019-12-19',
            ], [
                'softname' => 'libcryptui',
                'version' => '3.12.2-18.fc31',
                'dateinstall' => '2019-12-19',
            ], [
                'softname' => 'tar',
                'version' => '1.32-2.fc31',
                'dateinstall' => '2019-12-19',
            ],
        ];

        $i = 0;
        foreach ($iterator as $soft) {
            $expected = $expecteds[$i];
            if (count($versions)) {
                $expected['version'] = $versions[$i];
            }
            $this->assertEquals($expected, [
                'softname'     => $soft['softname'],
                'version'      => $soft['version'],
                'dateinstall'  => $soft['dateinstall']
            ]);
            ++$i;
        }
    }

    private function checkComputer1Batteries(\Computer $computer, array $capacities = [])
    {
        global $DB;

        $link        = getItemForItemtype(\Item_DeviceBattery::class);
        $iterator = $DB->request($link->getTableGroupCriteria($computer));
        $this->assertCount(1, $iterator);

        $battery = [];
        foreach ($iterator as $row) {
            unset($row['id']);
            $battery = $row;
        }

        $expected = [
            'items_id' => $computer->fields['id'],
            'itemtype' => $computer->getType(),
            'devicebatteries_id' => 104,
            'manufacturing_date' => '2019-07-06',
            'is_deleted' => 0,
            'is_dynamic' => 1,
            'entities_id' => 0,
            'is_recursive' => 0,
            'serial' => '34605',
            'otherserial' => null,
            'locations_id' => 0,
            'states_id' => 0,
            'real_capacity' => $capacities[0] ?? 50570
        ];

       //hack to replace expected fkeys
        foreach (array_keys($expected) as $key) {
            if (isForeignKeyField($key)) {
                $expected[$key] = $battery[$key];
            }
        }

        $this->assertIsArray($battery);
        $this->assertSame($expected, $battery);
    }

    public function testImportComputer()
    {
        global $DB;

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));

        $inventory = $this->doInventory($json);

       //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->assertCount(7, $metadata);
        $this->assertSame('glpixps-2018-07-09-09-07-13', $metadata['deviceid']);
        $this->assertSame('FusionInventory-Agent_v2.5.2-1.fc31', $metadata['version']);
        $this->assertSame('Computer', $metadata['itemtype']);
        $this->assertNull($metadata['port']);
        $this->assertSame('000005', $metadata['tag']);
        $this->assertCount(10, $metadata['provider']);

        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();
        $this->assertIsArray($agent);
        $this->assertSame('glpixps-2018-07-09-09-07-13', $agent['deviceid']);
        $this->assertSame('glpixps-2018-07-09-09-07-13', $agent['name']);
        $this->assertSame('2.5.2-1.fc31', $agent['version']);
        $this->assertSame('Computer', $agent['itemtype']);
        $this->assertSame('000005', $agent['tag']);
        $this->assertSame($agenttype['id'], $agent['agenttypes_id']);
        $this->assertGreaterThan(0, $agent['items_id']);

        //check created computer
        $computer = $this->checkComputer1($agent['items_id']);
        $this->checkComputer1Volumes($computer);
        $this->checkComputer1Softwares($computer);
        $this->checkComputer1Batteries($computer);

        //check matchedlogs
        $mlogs = new \RuleMatchedLog();
        $found = $mlogs->find();
        $this->assertCount(3, $found);

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

        $monitor_criteria = $criteria;
        $monitor_criteria['WHERE'] = ['itemtype' => \Monitor::getType()];
        $iterator = $DB->request($monitor_criteria);
        $this->assertCount(1, $iterator);
        $this->assertSame('Monitor import (by serial)', $iterator->current()['name']);
        $this->assertSame(\Glpi\Inventory\Request::INVENT_QUERY, $iterator->current()['method']);

        $printer_criteria = $criteria;
        $printer_criteria['WHERE'] = ['itemtype' => \Printer::getType()];
        $iterator = $DB->request($printer_criteria);
        $this->assertCount(1, $iterator);
        $this->assertSame('Printer import (by serial)', $iterator->current()['name']);
        $this->assertSame(\Glpi\Inventory\Request::INVENT_QUERY, $iterator->current()['method']);

        $computer_criteria = $criteria;
        $computer_criteria['WHERE'] = ['itemtype' => \Computer::getType()];
        $iterator = $DB->request($computer_criteria);
        $this->assertCount(1, $iterator);
        $this->assertSame('Computer import (by serial + uuid)', $iterator->current()['name']);
        $this->assertSame(\Glpi\Inventory\Request::INVENT_QUERY, $iterator->current()['method']);
    }

    public function testUpdateComputer()
    {
        global $DB;

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_3.json'));

        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->assertCount(7, $metadata);
        $this->assertSame('LF014-2017-02-20-12-19-56', $metadata['deviceid']);
        $this->assertSame('FusionInventory-Agent_v2.3.19', $metadata['version']);
        $this->assertSame('Computer', $metadata['itemtype']);
        $this->assertNull($metadata['port']);
        $this->assertSame('000005', $metadata['tag']);
        $this->assertCount(9, $metadata['provider']);

        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();
        $this->assertIsArray($agent);
        $this->assertSame('LF014-2017-02-20-12-19-56', $agent['deviceid']);
        $this->assertSame('LF014-2017-02-20-12-19-56', $agent['name']);
        $this->assertSame('2.3.19', $agent['version']);
        $this->assertSame('Computer', $agent['itemtype']);
        $this->assertSame('000005', $agent['tag']);
        $this->assertSame($agenttype['id'], $agent['agenttypes_id']);

        //check matchedlogs
        $mlogs = new \RuleMatchedLog();
        $mrules_found = $mlogs->find();
        $this->assertCount(2, $mrules_found);

        $mrules_criteria = [
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

        $monitor_criteria = $mrules_criteria;
        $monitor_criteria['WHERE'] = ['itemtype' => \Monitor::getType()];
        $iterator = $DB->request($monitor_criteria);
        $this->assertCount(1, $iterator);
        $this->assertSame('Monitor import (by serial)', $iterator->current()['name']);
        $this->assertSame(\Glpi\Inventory\Request::INVENT_QUERY, $iterator->current()['method']);

        $computer_criteria = $mrules_criteria;
        $computer_criteria['WHERE'] = ['itemtype' => \Computer::getType()];
        $iterator = $DB->request($computer_criteria);
        $this->assertCount(1, $iterator);
        $this->assertSame('Computer import (by serial + uuid)', $iterator->current()['name']);
        $this->assertSame($agent['items_id'], $iterator->current()['items_id']);
        $this->assertSame(\Glpi\Inventory\Request::INVENT_QUERY, $iterator->current()['method']);

        //get computer models, manufacturer, ...
        $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->current();
        $this->assertIsArray($autoupdatesystems);
        $autoupdatesystems_id = $autoupdatesystems['id'];

        $cmodels = $DB->request(['FROM' => \ComputerModel::getTable(), 'WHERE' => ['name' => 'PORTEGE Z30-A']])->current();
        $this->assertIsArray($cmodels);
        $computermodels_id = $cmodels['id'];

        $ctypes = $DB->request(['FROM' => \ComputerType::getTable(), 'WHERE' => ['name' => 'Notebook']])->current();
        $this->assertIsArray($ctypes);
        $computertypes_id = $ctypes['id'];

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Toshiba']])->current();
        $this->assertIsArray($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        //check created computer
        $computers_id = $agent['items_id'];
        $this->assertGreaterThan(0, $computers_id);
        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($computers_id));

        $expected = [
            'id' => $computers_id,
            'entities_id' => 0,
            'name' => 'LF014',
            'serial' => '8C554721F',
            'otherserial' => '0000000000',
            'contact' => 'johan',
            'contact_num' => null,
            'users_id_tech' => 0,
            'groups_id_tech' => 0,
            'comment' => null,
            'date_mod' => $computer->fields['date_mod'],
            'autoupdatesystems_id' => $autoupdatesystems_id,
            'locations_id' => 0,
            'networks_id' => 0,
            'computermodels_id' => $computermodels_id,
            'computertypes_id' => $computertypes_id,
            'is_template' => 0,
            'template_name' => null,
            'manufacturers_id' => $manufacturers_id,
            'is_deleted' => 0,
            'is_dynamic' => 1,
            'users_id' => 0,
            'groups_id' => 0,
            'states_id' => 0,
            'ticket_tco' => '0.0000',
            'uuid' => '0055ADC9-1D3A-E411-8043-B05D95113232',
            'date_creation' => $computer->fields['date_creation'],
            'is_recursive' => 0,
            'last_inventory_update' => $computer->fields['last_inventory_update'],
            'last_boot' => "2017-02-20 08:11:53",
        ];
        $this->assertIsArray($computer->fields);
        $this->assertSame($expected, $computer->fields);

        //operating system
        $ios = new \Item_OperatingSystem();
        $iterator = $ios->getFromItem($computer);
        $record = $iterator->current();

        $expected = [
            'assocID' => $record['assocID'],
            'name' => 'Fedora release 25 (Twenty Five)',
            'version' => '25',
            'architecture' => 'x86_64',
            'servicepack' => null,
        ];
        $this->assertIsArray($record);
        $this->assertSame($expected, $record);

        //volumes
        $idisks = new \Item_Disk();
        $iterator = $idisks->getFromItem($computer);
        $this->assertCount(3, $iterator);

        $expecteds_fs = [
            [
                'fsname' => 'ext4',
                'name' => '/',
                'device' => '/dev/mapper/fedora-root',
                'mountpoint' => '/',
                'filesystems_id' => 4,
                'totalsize' => 50268,
                'freesize' => 13336,
                'encryption_status' => 0,
                'encryption_tool' => null,
                'encryption_algorithm' => null,
                'encryption_type' => null,
            ], [
                'fsname' => 'ext4',
                'name' => '/boot',
                'device' => '/dev/sda1',
                'mountpoint' => '/boot',
                'filesystems_id' => 4,
                'totalsize' => 476,
                'freesize' => 279,
                'is_deleted' => 0,
                'is_dynamic' => 1,
                'encryption_status' => 0,
                'encryption_tool' => null,
                'encryption_algorithm' => null,
                'encryption_type' => null,
            ], [
                'fsname' => 'ext4',
                'name' => '/home',
                'device' => '/dev/mapper/fedora-home',
                'mountpoint' => '/home',
                'filesystems_id' => 4,
                'totalsize' => 181527,
                'freesize' => 72579,
                'is_deleted' => 0,
                'is_dynamic' => 1,
                'encryption_status' => 0,
                'encryption_tool' => null,
                'encryption_algorithm' => null,
                'encryption_type' => null,
            ]
        ];

        $i = 0;
        foreach ($iterator as $volume) {
            unset($volume['id']);
            unset($volume['date_mod']);
            unset($volume['date_creation']);
            $expected = $expecteds_fs[$i];
            $expected = $expected + [
                'items_id'     => $computers_id,
                'itemtype'     => 'Computer',
                'entities_id'  => 0,
                'is_deleted'   => 0,
                'is_dynamic'   => 1
            ];
            $this->assertIsArray($volume);
            $this->assertEquals($expected, $volume);
            ++$i;
        }

        //connections
        $iterator = \Computer_Item::getTypeItems($computers_id, 'Monitor');
        $this->assertCount(1, $iterator);

        //check network ports
        $iterator = $DB->request([
            'FROM'   => \NetworkPort::getTable(),
            'WHERE'  => [
                'items_id'           => $computers_id,
                'itemtype'           => 'Computer',
            ],
        ]);
        $this->assertCount(4, $iterator);

        //check for components
        $components = [];
        $allcount = 0;
        foreach (\Item_Devices::getItemAffinities('Computer') as $link_type) {
            $link        = getItemForItemtype($link_type);
            $iterator = $DB->request($link->getTableGroupCriteria($computer));
            $allcount += count($iterator);
            $components[$link_type] = [];

            foreach ($iterator as $row) {
                $lid = $row['id'];
                unset($row['id']);
                $components[$link_type][$lid] = $row;
            }
        }

        $expecteds_components = [
            'Item_DeviceMotherboard' => 0,
            'Item_DeviceFirmware' => 1,
            'Item_DeviceProcessor' => 1,
            'Item_DeviceMemory' => 2,
            'Item_DeviceHardDrive' => 1,
            'Item_DeviceNetworkCard' => 0,
            'Item_DeviceDrive' => 0,
            'Item_DeviceBattery' => 1,
            'Item_DeviceGraphicCard' => 0,
            'Item_DeviceSoundCard' => 2,
            'Item_DeviceControl' => 14,
            'Item_DevicePci' => 0,
            'Item_DeviceCase' => 0,
            'Item_DevicePowerSupply' => 0,
            'Item_DeviceGeneric' => 0,
            'Item_DeviceSimcard' => 0,
            'Item_DeviceSensor' => 0
        ];

        foreach ($expecteds_components as $type => $count) {
            $this->assertCount($count, $components[$type], "$type " . count($components[$type]));
        }

        //check memory
        $this->assertCount(2, $components['Item_DeviceMemory']);
        $mem_component1 = array_pop($components['Item_DeviceMemory']);
        $mem_component2 = array_pop($components['Item_DeviceMemory']);
        $this->assertGreaterThan(0, $mem_component1['devicememories_id']);
        $expected_mem_component = [
            'items_id' => $mem_component1['items_id'],
            'itemtype' => "Computer",
            'devicememories_id' => $mem_component1['devicememories_id'],
            'size' => 2048,
            'serial' => "23853943",
            'is_deleted' => 0,
            'is_dynamic' => 1,
            'entities_id' => 0,
            'is_recursive' => 0,
            'busID' => "2",
            'otherserial' => null,
            'locations_id' => 0,
            'states_id' => 0
        ];
        $this->assertIsArray($mem_component1);
        $this->assertSame($expected_mem_component, $mem_component1);
        $expected_mem_component['busID'] = "1";
        $this->assertIsArray($mem_component2);
        $this->assertSame($expected_mem_component, $mem_component2);

        //software
        $isoft = new \Item_SoftwareVersion();
        $iterator = $isoft->getFromItem($computer);
        $this->assertCount(3034, $iterator);

        //computer has been created, check logs.
        //check for expected logs
        $nblogsnow = countElementsInTable(\Log::getTable());
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'LIMIT' => $nblogsnow,
            'OFFSET' => $this->nblogs,
        ]);
        $this->assertCount(0, $logs);

        //fake computer update (nothing has changed)
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_3.json'));
        $this->doInventory($json);

        $this->assertTrue($computer->getFromDB($computers_id));

        $expected = [
            'id' => $computers_id,
            'entities_id' => 0,
            'name' => 'LF014',
            'serial' => '8C554721F',
            'otherserial' => '0000000000',
            'contact' => 'johan',
            'contact_num' => null,
            'users_id_tech' => 0,
            'groups_id_tech' => 0,
            'comment' => null,
            'date_mod' => $computer->fields['date_mod'],
            'autoupdatesystems_id' => $autoupdatesystems_id,
            'locations_id' => 0,
            'networks_id' => 0,
            'computermodels_id' => $computermodels_id,
            'computertypes_id' => $computertypes_id,
            'is_template' => 0,
            'template_name' => null,
            'manufacturers_id' => $manufacturers_id,
            'is_deleted' => 0,
            'is_dynamic' => 1,
            'users_id' => 0,
            'groups_id' => 0,
            'states_id' => 0,
            'ticket_tco' => '0.0000',
            'uuid' => '0055ADC9-1D3A-E411-8043-B05D95113232',
            'date_creation' => $computer->fields['date_creation'],
            'is_recursive' => 0,
            'last_inventory_update' => $computer->fields['last_inventory_update'],
            'last_boot' => "2017-02-20 08:11:53",
        ];
        $this->assertIsArray($computer->fields);
        $this->assertSame($expected, $computer->fields);

        //operating system
        $ios = new \Item_OperatingSystem();
        $iterator = $ios->getFromItem($computer);
        $record = $iterator->current();

        $expected = [
            'assocID' => $record['assocID'],
            'name' => 'Fedora release 25 (Twenty Five)',
            'version' => '25',
            'architecture' => 'x86_64',
            'servicepack' => null,
        ];
        $this->assertIsArray($record);
        $this->assertSame($expected, $record);

        //volumes
        $idisks = new \Item_Disk();
        $iterator = $idisks->getFromItem($computer);
        $this->assertCount(3, $iterator);

        $i = 0;
        foreach ($iterator as $volume) {
            unset($volume['id']);
            unset($volume['date_mod']);
            unset($volume['date_creation']);
            $expected = $expecteds_fs[$i];
            $expected = $expected + [
                'items_id'     => $computers_id,
                'itemtype'     => 'Computer',
                'entities_id'  => 0,
                'is_deleted'   => 0,
                'is_dynamic'   => 1
            ];
            $this->assertIsArray($volume);
            $this->assertEquals($expected, $volume);
            ++$i;
        }

        //connections
        $iterator = \Computer_Item::getTypeItems($computers_id, 'Monitor');
        $this->assertCount(1, $iterator);

        //check network ports
        $iterator = $DB->request([
            'FROM'   => \NetworkPort::getTable(),
            'WHERE'  => [
                'items_id'           => $computers_id,
                'itemtype'           => 'Computer',
            ],
        ]);
        $this->assertCount(4, $iterator);

        //check for components
        $components = [];
        $allcount = 0;
        foreach (\Item_Devices::getItemAffinities('Computer') as $link_type) {
            $link        = getItemForItemtype($link_type);
            $iterator = $DB->request($link->getTableGroupCriteria($computer));
            $allcount += count($iterator);
            $components[$link_type] = [];

            foreach ($iterator as $row) {
                $lid = $row['id'];
                unset($row['id']);
                $components[$link_type][$lid] = $row;
            }
        }

        foreach ($expecteds_components as $type => $count) {
            $this->assertCount($count, $components[$type], "$type " . count($components[$type]));
        }

        //check memory
        $this->assertCount(2, $components['Item_DeviceMemory']);
        $mem_component1 = array_pop($components['Item_DeviceMemory']);
        $mem_component2 = array_pop($components['Item_DeviceMemory']);
        $this->assertGreaterThan(0, $mem_component1['devicememories_id']);
        $expected_mem_component['busID'] = "2";
        $this->assertIsArray($mem_component1);
        $this->assertSame($expected_mem_component, $mem_component1);
        $expected_mem_component['busID'] = "1";
        $this->assertIsArray($mem_component2);
        $this->assertSame($expected_mem_component, $mem_component2);

        //software
        $isoft = new \Item_SoftwareVersion();
        $iterator = $isoft->getFromItem($computer);
        $this->assertCount(3034, $iterator);

        //check for expected logs
        $nblogsnow = countElementsInTable(\Log::getTable());
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'LIMIT' => $nblogsnow,
            'OFFSET' => $this->nblogs,
        ]);
        $this->assertCount(0, $logs);

        //real computer update
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_3_updated.json'));

        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->assertCount(7, $metadata);
        $this->assertSame('LF014-2017-02-20-12-19-56', $metadata['deviceid']);
        $this->assertSame('FusionInventory-Agent_v2.3.20', $metadata['version']);
        $this->assertSame('Computer', $metadata['itemtype']);
        $this->assertNull($metadata['port']);
        $this->assertSame('000005', $metadata['tag']);
        $this->assertSame('inventory', $metadata['action']);
        $this->assertCount(9, $metadata['provider']);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();
        $this->assertIsArray($agent);
        $this->assertSame('LF014-2017-02-20-12-19-56', $agent['deviceid']);
        $this->assertSame('LF014-2017-02-20-12-19-56', $agent['name']);
        $this->assertSame('2.3.20', $agent['version']);
        $this->assertSame('Computer', $agent['itemtype']);
        $this->assertSame($computers_id, $agent['items_id']);
        $this->assertSame('000005', $agent['tag']);
        $this->assertSame($agenttype['id'], $agent['agenttypes_id']);

        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($computers_id));

        $expected = [
            'id' => $computers_id,
            'entities_id' => 0,
            'name' => 'LF014',
            'serial' => '8C554721F',
            'otherserial' => '0000000000',
            'contact' => 'johan',
            'contact_num' => null,
            'users_id_tech' => 0,
            'groups_id_tech' => 0,
            'comment' => null,
            'date_mod' => $computer->fields['date_mod'],
            'autoupdatesystems_id' => $autoupdatesystems_id,
            'locations_id' => 0,
            'networks_id' => 0,
            'computermodels_id' => $computermodels_id,
            'computertypes_id' => $computertypes_id,
            'is_template' => 0,
            'template_name' => null,
            'manufacturers_id' => $manufacturers_id,
            'is_deleted' => 0,
            'is_dynamic' => 1,
            'users_id' => 0,
            'groups_id' => 0,
            'states_id' => 0,
            'ticket_tco' => '0.0000',
            'uuid' => '0055ADC9-1D3A-E411-8043-B05D95113232',
            'date_creation' => $computer->fields['date_creation'],
            'is_recursive' => 0,
            'last_inventory_update' => $computer->fields['last_inventory_update'],
            'last_boot' => "2017-06-08 07:06:47",
        ];
        $this->assertIsArray($computer->fields);
        $this->assertSame($expected, $computer->fields);

        //operating system
        $ios = new \Item_OperatingSystem();
        $iterator = $ios->getFromItem($computer);
        $record = $iterator->current();

        $expected = [
            'assocID' => $record['assocID'],
            'name' => 'Fedora release 25 (Twenty Five)',
            'version' => '25',
            'architecture' => 'x86_64',
            'servicepack' => null,
        ];
        $this->assertIsArray($record);
        $this->assertSame($expected, $record);

        //volumes
        $idisks = new \Item_Disk();
        $iterator = $idisks->getFromItem($computer);
        $this->assertCount(3, $iterator);

        //update fs values
        $expecteds_fs[0]['totalsize'] = 150268;
        $expecteds_fs[0]['freesize'] = 7914;
        $expecteds_fs[1]['freesize'] = 277;
        $expecteds_fs[2]['freesize'] = 68968;

        $i = 0;
        foreach ($iterator as $volume) {
            unset($volume['id']);
            unset($volume['date_mod']);
            unset($volume['date_creation']);
            $expected = $expecteds_fs[$i];
            $expected = $expected + [
                'items_id'     => $computers_id,
                'itemtype'     => 'Computer',
                'entities_id'  => 0,
                'is_deleted'   => 0,
                'is_dynamic'   => 1
            ];
            $this->assertIsArray($volume);
            $this->assertEquals($expected, $volume);
            ++$i;
        }

        //connections
        $iterator = \Computer_Item::getTypeItems($computers_id, 'Monitor');
        $this->assertCount(0, $iterator);

        //check network ports
        $iterator = $DB->request([
            'FROM'   => \NetworkPort::getTable(),
            'WHERE'  => [
                'items_id'           => $computers_id,
                'itemtype'           => 'Computer',
            ],
        ]);
        $this->assertCount(7, $iterator);

        //check for components
        $components = [];
        $allcount = 0;
        foreach (\Item_Devices::getItemAffinities('Computer') as $link_type) {
            $link        = getItemForItemtype($link_type);
            $iterator = $DB->request($link->getTableGroupCriteria($computer));
            $allcount += count($iterator);
            $components[$link_type] = [];

            foreach ($iterator as $row) {
                $lid = $row['id'];
                unset($row['id']);
                $components[$link_type][$lid] = $row;
            }
        }

        foreach ($expecteds_components as $type => $count) {
            $this->assertCount($count, $components[$type], "$type " . count($components[$type]));
        }

        //check memory
        $this->assertCount(2, $components['Item_DeviceMemory']);
        $mem_component1 = array_pop($components['Item_DeviceMemory']);
        $mem_component2 = array_pop($components['Item_DeviceMemory']);
        $expected_mem_component = [
            'items_id' => $mem_component1['items_id'],
            'itemtype' => "Computer",
            'devicememories_id' => $mem_component1['devicememories_id'],
            'size' => 4096,
            'serial' => "53853943",
            'is_deleted' => 0,
            'is_dynamic' => 1,
            'entities_id' => 0,
            'is_recursive' => 0,
            'busID' => "2",
            'otherserial' => null,
            'locations_id' => 0,
            'states_id' => 0
        ];
        $this->assertIsArray($mem_component1);
        $this->assertSame($expected_mem_component, $mem_component1);
        $expected_mem_component['busID'] = "1";
        $this->assertIsArray($mem_component2);
        $this->assertSame($expected_mem_component, $mem_component2);

        //software
        $isoft = new \Item_SoftwareVersion();
        $iterator = $isoft->getFromItem($computer);
        $this->assertCount(3185, $iterator);

        //check for expected logs after update
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'LIMIT' => countElementsInTable(\Log::getTable()),
            'OFFSET' => $nblogsnow,
        ]);

        $this->assertCount(4418, $logs);

        $expected_types_count = [
            0 => 3, //Agent version, disks usage
            \Log::HISTORY_ADD_DEVICE => 2, //new item_device...
            \Log::HISTORY_DELETE_DEVICE => 2, //delete item_device...
            \Log::HISTORY_ADD_RELATION => 1, //new IPNetwork/IPAddress
            \Log::HISTORY_DEL_RELATION => 2,//monitor-computer relation
            \Log::HISTORY_ADD_SUBITEM => 3247,//network port/name, ip address, VMs, Software
            \Log::HISTORY_UPDATE_SUBITEM => 828,//disks usage, software updates
            \Log::HISTORY_DELETE_SUBITEM => 99,//networkport and networkname, Software?
            \Log::HISTORY_CREATE_ITEM => 232, //virtual machines, os, manufacturer, net ports, net names, software category / item_device...
            \Log::HISTORY_UPDATE_RELATION => 2,//kernel version
        ];

        $types_count = [];
        foreach ($logs as $row) {
            $this->assertSame('inventory', $row['user_name'], print_r($row, true));
            if (!isset($types_count[$row['linked_action']])) {
                $types_count[$row['linked_action']] = 0;
            }
            ++$types_count[$row['linked_action']];
        }

        ksort($types_count);
        ksort($expected_types_count);
        $this->assertEquals(
            $expected_types_count,
            $types_count,
            sprintf(
                "\nGot:\n%s\n\nExpected:\n%s",
                print_r($types_count, true),
                print_r($expected_types_count, true)
            )
        );

        //check matchedlogs
        $mlogs = new \RuleMatchedLog();
        $found = $mlogs->find(['NOT' => ['id' => array_keys($mrules_found)]]);
        $mrules_criteria['WHERE'] = ['NOT' => [\RuleMatchedLog::getTable() . '.id' => array_keys($mrules_found)]];
        $this->assertCount(3, $found);

        $monitor_criteria = $mrules_criteria;
        $monitor_criteria['WHERE'][] = ['itemtype' => \Monitor::getType()];
        $iterator = $DB->request($monitor_criteria);
        $this->assertCount(1, $iterator);
        $this->assertSame('Monitor update (by serial)', $iterator->current()['name']);
        $this->assertSame(\Glpi\Inventory\Request::INVENT_QUERY, $iterator->current()['method']);

        $computer_criteria = $mrules_criteria;
        $computer_criteria['WHERE'][] = ['itemtype' => \Computer::getType()];
        $iterator = $DB->request($computer_criteria);

        $this->assertCount(2, $iterator);
        foreach ($iterator as $rmlog) {
            $this->assertSame('Computer update (by serial + uuid)', $rmlog['name']);
            $this->assertSame($agent['items_id'], $rmlog['items_id']);
            $this->assertSame(\Glpi\Inventory\Request::INVENT_QUERY, $rmlog['method']);
        }
    }

    public function testImportNetworkEquipment()
    {
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'networkequipment_1.json'));

        $date_now = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date_now;
        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();

        $this->assertCount(5, $metadata);
        $this->assertSame('foo', $metadata['deviceid']);
        $this->assertSame('4.1', $metadata['version']);
        $this->assertSame('NetworkEquipment', $metadata['itemtype']);
        $this->assertNull($metadata['port']);
        $this->assertSame('netinventory', $metadata['action']);

        global $DB;
       //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
       //no agent with deviceid equals to "foo"
        $this->assertCount(0, $agents);

       //get model, manufacturer, ...
        $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->current();
        $this->assertIsArray($autoupdatesystems);
        $autoupdatesystems_id = $autoupdatesystems['id'];

        $cmodels = $DB->request(['FROM' => \NetworkEquipmentModel::getTable(), 'WHERE' => ['name' => 'UCS 6248UP 48-Port']])->current();
        $this->assertIsArray($cmodels);
        $models_id = $cmodels['id'];

        $ctypes = $DB->request(['FROM' => \NetworkEquipmentType::getTable(), 'WHERE' => ['name' => 'Networking']])->current();
        $this->assertIsArray($ctypes);
        $types_id = $ctypes['id'];

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Cisco']])->current();
        $this->assertIsArray($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $cloc = $DB->request(['FROM' => \Location::getTable(), 'WHERE' => ['name' => 'paris.pa3']])->current();
        $this->assertIsArray($cloc);
        $locations_id = $cloc['id'];

        //check created asset
        $equipments = $DB->request(['FROM' => \NetworkEquipment::getTable(), 'WHERE' => ['is_dynamic' => 1]]);
        //no agent with deviceid equals to "foo"
        $this->assertCount(1, $equipments);
        $equipments_id = $equipments->current()['id'];

        $equipment = new \NetworkEquipment();
        $this->assertTrue($equipment->getFromDB($equipments_id));

        $expected = [
            'id' => $equipments_id,
            'entities_id' => 0,
            'is_recursive' => 0,
            'name' => 'ucs6248up-cluster-pa3-B',
            'ram' => null,
            'serial' => 'SSI1912014B',
            'otherserial' => null,
            'contact' => 'noc@glpi-project.org',
            'contact_num' => null,
            'users_id_tech' => 0,
            'groups_id_tech' => 0,
            'date_mod' => $equipment->fields['date_mod'],
            'comment' => null,
            'locations_id' => $locations_id,
            'networks_id' => 0,
            'networkequipmenttypes_id' => $types_id,
            'networkequipmentmodels_id' => $models_id,
            'manufacturers_id' => $manufacturers_id,
            'is_deleted' => 0,
            'is_template' => 0,
            'template_name' => null,
            'users_id' => 0,
            'groups_id' => 0,
            'states_id' => 0,
            'ticket_tco' => '0.0000',
            'is_dynamic' => 1,
            'uuid' => null,
            'date_creation' => $equipment->fields['date_creation'],
            'autoupdatesystems_id' => $autoupdatesystems_id,
            'sysdescr' => null,
            'cpu' => 4,
            'uptime' => '482 days, 05:42:18.50',
            'last_inventory_update' => $date_now,
            'snmpcredentials_id' => 4,
        ];
        $this->assertIsArray($equipment->fields);
        $this->assertSame($expected, $equipment->fields);

        //check network ports
        $expected_count = 164;
        $iterator = $DB->request([
            'FROM'   => \NetworkPort::getTable(),
            'WHERE'  => [
                'items_id'           => $equipments_id,
                'itemtype'           => 'NetworkEquipment',
            ],
        ]);
        $this->assertCount($expected_count, $iterator);

        $expecteds = [
            ($expected_count - 1) => [
                'logical_number' => 0,
                'name' => 'Management',
                'instantiation_type' => 'NetworkPortAggregate',
                'mac' => '8c:60:4f:8d:ae:fc',
            ],
        ];

        $ips = [
            'Management' => [
                '10.2.5.10',
                '192.168.12.5',
            ]
        ];

        $i = 0;
        $netport = new \NetworkPort();
        foreach ($iterator as $port) {
            $ports_id = $port['id'];
            $this->assertTrue($netport->getFromDB($ports_id));
            $instantiation = $netport->getInstantiation();
            if ($port['instantiation_type'] === null) {
                $this->assertFalse($instantiation);
            } else {
                $this->assertInstanceOf($port['instantiation_type'], $instantiation);
            }

            unset($port['id']);
            unset($port['date_creation']);
            unset($port['date_mod']);
            unset($port['comment']);

            if (isset($expecteds[$i])) {
                $expected = $expecteds[$i];
                $expected = $expected + [
                    'items_id' => $equipments_id,
                    'itemtype' => 'NetworkEquipment',
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'ifmtu' => 0,
                    'ifspeed' => 0,
                    'ifinternalstatus' => null,
                    'ifconnectionstatus' => 0,
                    'iflastchange' => null,
                    'ifinbytes' => 0,
                    'ifinerrors' => 0,
                    'ifoutbytes' => 0,
                    'ifouterrors' => 0,
                    'ifstatus' => null,
                    'ifdescr' => null,
                    'ifalias' => null,
                    'portduplex' => null,
                    'trunk' => 0,
                    'lastup' => null
                ];

                $this->assertIsArray($port);
                $this->assertEquals($expected, $port);
            } else {
                $this->assertSame('NetworkEquipment', $port['itemtype']);
                $this->assertSame($equipments_id, $port['items_id']);
                $this->assertSame('NetworkPortEthernet', $port['instantiation_type'], print_r($port, true),);
                $this->assertMatchesRegularExpression(
                    '/^(?:(?:[0-9a-f]{2}[\:]{1}){5}|(?:[0-9a-f]{2}[-]{1}){5}|(?:[0-9a-f]{2}){5})[0-9a-f]{2}$/i',
                    $port['mac']
                );
                $this->assertSame(1, $port['is_dynamic']);
            }
            ++$i;

            //check for ips
            $ip_iterator = $DB->request([
                'SELECT'       => [
                    \IPAddress::getTable() . '.name',
                    \IPAddress::getTable() . '.version'
                ],
                'FROM'   => \IPAddress::getTable(),
                'INNER JOIN'   => [
                    \NetworkName::getTable()   => [
                        'ON'  => [
                            \IPAddress::getTable()     => 'items_id',
                            \NetworkName::getTable()   => 'id', [
                                'AND' => [\IPAddress::getTable() . '.itemtype'  => \NetworkName::getType()]
                            ]
                        ]
                    ]
                ],
                'WHERE'  => [
                    \NetworkName::getTable() . '.itemtype'  => \NetworkPort::getType(),
                    \NetworkName::getTable() . '.items_id'  => $ports_id
                ]
            ]);

            $this->assertCount(count($ips[$port['name']] ?? []), $ip_iterator);
            if (isset($ips[$port['name']])) {
                foreach ($ip_iterator as $ip) {
                    $this->assertIsArray($ips[$port['name']]);
                    $this->assertTrue(in_array($ip['name'], $ips[$port['name']]));
                }
            }
        }

        //check for components
        $components = [];
        $allcount = 0;
        foreach (\Item_Devices::getItemAffinities('NetworkEquipment') as $link_type) {
            $link        = getItemForItemtype($link_type);
            $iterator = $DB->request($link->getTableGroupCriteria($equipment));
            $allcount += count($iterator);
            $components[$link_type] = [];

            foreach ($iterator as $row) {
                $lid = $row['id'];
                unset($row['id']);
                $components[$link_type][$lid] = $row;
            }
        }

        $expecteds = [
            'Item_DeviceFirmware' => 1,
            'Item_DeviceMemory' => 0,
            'Item_DeviceHardDrive' => 0,
            'Item_DeviceNetworkCard' => 0,
            'Item_DevicePci' => 0,
            'Item_DevicePowerSupply' => 0,
            'Item_DeviceGeneric' => 0,
            'Item_DeviceSimcard' => 0,
        ];

        foreach ($expecteds as $type => $count) {
            $this->assertSame(
                $count,
                count($components[$type]),
                sprintf(
                    'Expected %1$s %2$s, got %3$s of them',
                    $count,
                    $type,
                    count($components[$type])
                )
            );
        }

        $expecteds = [
            'Item_DeviceFirmware' => [
                [
                    'items_id' => $equipments_id,
                    'itemtype' => 'NetworkEquipment',
                    'devicefirmwares_id' => 104,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ]
            ],
            'Item_DeviceMemory' => [],
            'Item_DeviceHardDrive' => [],
            'Item_DeviceNetworkCard' => [],
            'Item_DevicePci' => [],
            'Item_DevicePowerSupply' => [],
            'Item_DeviceGeneric' => [],
            'Item_DeviceSimcard' => [],
        ];

        foreach ($expecteds as $type => $expected) {
            $component = array_values($components[$type]);
            //hack to replace expected fkeys
            foreach ($expected as $i => &$row) {
                foreach (array_keys($row) as $key) {
                    if (isForeignKeyField($key)) {
                        $row[$key] = $component[$i][$key];
                    }
                }
            }
            $this->assertIsArray($component);
            $this->assertSame($expected, $component);
        }

        //ports connections
        $connections = $DB->request(['FROM' => \NetworkPort_NetworkPort::getTable()]);
        $this->assertCount(5, $connections);

        //unmanaged equipments
        $unmanageds = $DB->request(['FROM' => \Unmanaged::getTable()]);
        $this->assertCount(5, $unmanageds);

        $expecteds = [
            'sw2-mgmt-eqnx' => "Cisco IOS Software, C2960 Software (C2960-LANLITEK9-M), Version 12.2(50)SE5, RELEASE SOFTWARE (fc1)
Technical Support: http://www.cisco.com/techsupport
Copyright (c) 1986-2010 by Cisco Systems, Inc.
Compiled Tue 28-Sep-10 13:44 by prod_rel_team",
            'n9k-1-pa3' => 'Cisco Nexus Operating System (NX-OS) Software, Version 7.0(3)I7(6)',
            'n9k-2-pa3' => 'Cisco Nexus Operating System (NX-OS) Software, Version 7.0(3)I7(6)',
        ];

        foreach ($unmanageds as $unmanaged) {
            $this->assertTrue(in_array($unmanaged['name'], array_keys($expecteds)), $unmanaged['name']);
            $this->assertSame($expecteds[$unmanaged['name']], $unmanaged['sysdescr']);
        }

        //check matchedlogs
        $mlogs = new \RuleMatchedLog();
        $found = $mlogs->find();
        $this->assertCount(6, $found);//1 equipment, 5 unmanageds

        $mrules_criteria = [
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

        $neteq_criteria = $mrules_criteria;
        $neteq_criteria['WHERE'][] = ['itemtype' => \NetworkEquipment::getType()];
        $iterator = $DB->request($neteq_criteria);
        $this->assertCount(1, $iterator);
        foreach ($iterator as $neteq) {
            $this->assertSame('NetworkEquipment import (by serial)', $neteq['name']);
            $this->assertSame($equipments_id, $neteq['items_id']);
            $this->assertSame(\Glpi\Inventory\Request::INVENT_QUERY, $neteq['method']);
        }

        $unmanaged_criteria = $mrules_criteria;
        $unmanaged_criteria['WHERE'][] = ['itemtype' => \Unmanaged::getType()];
        $iterator = $DB->request($unmanaged_criteria);
        $this->assertCount(5, $iterator);
        foreach ($iterator as $unmanaged) {
            $this->assertSame('Global import (by ip+ifdescr)', $unmanaged['name']);
            $this->assertSame(\Glpi\Inventory\Request::INVENT_QUERY, $unmanaged['method']);
        }
    }

    public function testImportStackedNetworkEquipment()
    {
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'networkequipment_2.json'));

        $date_now = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date_now;
        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();

        $this->assertCount(5, $metadata);
        $this->assertSame('3k-1-pa3.glpi-project.infra-2020-12-31-11-28-51', $metadata['deviceid']);
        $this->assertSame('4.1', $metadata['version']);
        $this->assertSame('NetworkEquipment', $metadata['itemtype']);
        $this->assertNull($metadata['port']);
        $this->assertSame('netinventory', $metadata['action']);

        global $DB;
        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $this->assertIsArray($inventory->getAgent()->fields);
        $this->assertSame('3k-1-pa3.glpi-project.infra-2020-12-31-11-28-51', $inventory->getAgent()->fields['deviceid']);
        $this->assertSame('3k-1-pa3.glpi-project.infra-2020-12-31-11-28-51', $inventory->getAgent()->fields['name']);
        $this->assertSame('NetworkEquipment', $inventory->getAgent()->fields['itemtype']);
        $this->assertSame($agenttype['id'], $inventory->getAgent()->fields['agenttypes_id']);

        //get model, manufacturer, ...
        $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->current();
        $this->assertIsArray($autoupdatesystems);
        $autoupdatesystems_id = $autoupdatesystems['id'];

        $cmodels = $DB->request(['FROM' => \NetworkEquipmentModel::getTable(), 'WHERE' => ['name' => 'WS-C3750G-48TS-S']])->current();
        $this->assertIsArray($cmodels);
        $models_id = $cmodels['id'];

        $ctypes = $DB->request(['FROM' => \NetworkEquipmentType::getTable(), 'WHERE' => ['name' => 'Networking']])->current();
        $this->assertIsArray($ctypes);
        $types_id = $ctypes['id'];

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Cisco']])->current();
        $this->assertIsArray($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $cloc = $DB->request(['FROM' => \Location::getTable(), 'WHERE' => ['name' => 'paris.pa3']])->current();
        $this->assertIsArray($cloc);
        $locations_id = $cloc['id'];

        //check created equipments
        $expected_eq_count = 5;
        $iterator = $DB->request([
            'FROM'   => \NetworkEquipment::getTable(),
            'WHERE'  => ['is_dynamic' => 1]
        ]);
        $this->assertCount($expected_eq_count, $iterator);

        $main_expected = [
            'id' => null,
            'entities_id' => 0,
            'is_recursive' => 0,
            'name' => '3k-1-pa3.glpi-project.infra',
            'ram' => 128,
            'serial' => 'FOC1243W0ED',
            'otherserial' => null,
            'contact' => null,
            'contact_num' => null,
            'users_id_tech' => 0,
            'groups_id_tech' => 0,
            'date_mod' => null,
            'comment' => null,
            'locations_id' => $locations_id,
            'networks_id' => 0,
            'networkequipmenttypes_id' => $types_id,
            'networkequipmentmodels_id' => $models_id,
            'manufacturers_id' => $manufacturers_id,
            'is_deleted' => 0,
            'is_template' => 0,
            'template_name' => null,
            'users_id' => 0,
            'groups_id' => 0,
            'states_id' => 0,
            'ticket_tco' => '0.0000',
            'is_dynamic' => 1,
            'uuid' => null,
            'date_creation' => null,
            'autoupdatesystems_id' => $autoupdatesystems_id,
            'sysdescr' => null,
            'cpu' => 47,
            'uptime' => '103 days, 13:53:28.28',
            'last_inventory_update' => $date_now,
            'snmpcredentials_id' => 0,
        ];

        $stacks = [
            1 => [
                'serial'       => 'FOC1243W0ED',
                'connections'  => 0
            ],
            2 => [
                'serial'       => 'FOC1127Z4LH',
                'connections'  => 2
            ],
            3 => [
                'serial'       => 'FOC1232W0JH',
                'connections'  => 5
            ],
            4 => [
                'serial'       => 'FOC1033Y0M7',
                'connections'  => 2
            ],
            8 => [
                'serial'       => 'FOC0929U1SR',
                'connections'  => 2
            ]
        ];

        foreach ($iterator as $row) {
            $expected = $main_expected;
            $equipments_id = $row['id'];
            $expected['id'] = $equipments_id;
            $equipment = new \NetworkEquipment();
            $this->assertTrue($equipment->getFromDB($equipments_id));
            $expected['date_mod'] = $row['date_mod'];
            $expected['date_creation'] = $row['date_creation'];
            $stack_id = preg_replace('/.+ - (\d)/', '$1', $row['name']);
            $this->assertIsArray($stacks);
            $this->assertArrayHasKey($stack_id, $stacks);
            $expected['name'] .= ' - ' . $stack_id;
            $expected['serial'] = $stacks[$stack_id]['serial'];
            $this->assertIsArray($row);
            $this->assertSame($expected, $row);

            //check network ports
            $expected_count = 53;
            $ports_iterator = $DB->request([
                'FROM'   => \NetworkPort::getTable(),
                'WHERE'  => [
                    'items_id'           => $equipments_id,
                    'itemtype'           => 'NetworkEquipment',
                ],
            ]);
            $this->assertSame(
                $expected_count,
                count($ports_iterator),
                sprintf(
                    '%s ports found on %s, %s expected',
                    count($ports_iterator),
                    $row['name'],
                    $expected_count
                )
            );

            $expecteds = [
                ($expected_count - 1) => [
                    'logical_number' => 0,
                    'name' => 'Management',
                    'instantiation_type' => 'NetworkPortAggregate',
                    'mac' => '00:23:ac:6a:01:00',
                ],
            ];

            $ips = [
                'Management' => [
                    '10.1.0.100',
                    '10.1.0.22',
                    '10.1.0.41',
                    '10.1.0.45',
                    '10.1.0.59',
                    '10.11.11.1',
                    '10.11.11.5',
                    '10.11.13.1',
                    '10.11.13.5',
                    '172.21.0.1',
                    '172.21.0.7',
                    '172.22.0.1',
                    '172.22.0.5',
                    '172.23.0.1',
                    '172.23.0.5',
                    '172.24.0.1',
                    '172.24.0.5',
                    '172.25.1.15',
                    '172.28.200.1',
                    '172.28.200.5',
                    '172.28.211.5',
                    '172.28.215.1',
                    '172.28.221.1',
                    '185.10.253.65',
                    '185.10.253.97',
                    '185.10.254.1',
                    '185.10.255.146',
                    '185.10.255.224',
                    '185.10.255.250',
                ]
            ];

            $i = 0;
            $netport = new \NetworkPort();
            $all_ports_ids = [];
            foreach ($ports_iterator as $port) {
                $ports_id = $port['id'];
                $all_ports_ids[] = $port['id'];
                $this->assertTrue($netport->getFromDB($ports_id));
                $instantiation = $netport->getInstantiation();
                if ($port['instantiation_type'] === null) {
                    $this->assertFalse($instantiation);
                } else {
                    $this->assertInstanceOf($port['instantiation_type'], $instantiation);
                }

                unset($port['id']);
                unset($port['date_creation']);
                unset($port['date_mod']);
                unset($port['comment']);

                if (isset($expecteds[$i])) {
                    $expected = $expecteds[$i];
                    $expected = $expected + [
                        'items_id' => $equipments_id,
                        'itemtype' => 'NetworkEquipment',
                        'entities_id' => 0,
                        'is_recursive' => 0,
                        'is_deleted' => 0,
                        'is_dynamic' => 1,
                        'ifmtu' => 0,
                        'ifspeed' => 0,
                        'ifinternalstatus' => null,
                        'ifconnectionstatus' => 0,
                        'iflastchange' => null,
                        'ifinbytes' => 0,
                        'ifinerrors' => 0,
                        'ifoutbytes' => 0,
                        'ifouterrors' => 0,
                        'ifstatus' => null,
                        'ifdescr' => null,
                        'ifalias' => null,
                        'portduplex' => null,
                        'trunk' => 0,
                        'lastup' => null
                    ];

                    $this->assertIsArray($port);
                    $this->assertEquals($expected, $port);
                } else {
                    $this->assertSame('NetworkEquipment', $port['itemtype']);
                    $this->assertSame($equipments_id, $port['items_id']);
                    $this->assertSame('NetworkPortEthernet', $port['instantiation_type'], print_r($port, true));
                    $this->assertMatchesRegularExpression(
                        '/^(?:(?:[0-9a-f]{2}[\:]{1}){5}|(?:[0-9a-f]{2}[-]{1}){5}|(?:[0-9a-f]{2}){5})[0-9a-f]{2}$/i',
                        $port['mac']
                    );
                    $this->assertSame(1, $port['is_dynamic']);
                }
                ++$i;

                //check for ips
                $ip_iterator = $DB->request([
                    'SELECT'       => [
                        \IPAddress::getTable() . '.name',
                        \IPAddress::getTable() . '.version'
                    ],
                    'FROM'   => \IPAddress::getTable(),
                    'INNER JOIN'   => [
                        \NetworkName::getTable()   => [
                            'ON'  => [
                                \IPAddress::getTable()     => 'items_id',
                                \NetworkName::getTable()   => 'id', [
                                    'AND' => [\IPAddress::getTable() . '.itemtype'  => \NetworkName::getType()]
                                ]
                            ]
                        ]
                    ],
                    'WHERE'  => [
                        \NetworkName::getTable() . '.itemtype'  => \NetworkPort::getType(),
                        \NetworkName::getTable() . '.items_id'  => $ports_id
                    ]
                ]);

                $this->assertCount(count($ips[$port['name']] ?? []), $ip_iterator);
                if (isset($ips[$port['name']])) {
                    foreach ($ip_iterator as $ip) {
                        $this->assertIsArray($ips[$port['name']]);
                        $this->assertTrue(in_array($ip['name'], $ips[$port['name']]));
                    }
                }
            }

            //check for components
            $components = [];
            $allcount = 0;
            foreach (\Item_Devices::getItemAffinities('NetworkEquipment') as $link_type) {
                $link = getItemForItemtype($link_type);
                $dev_iterator = $DB->request($link->getTableGroupCriteria($equipment));
                $allcount += count($dev_iterator);
                $components[$link_type] = [];

                foreach ($dev_iterator as $row) {
                    $lid = $row['id'];
                    unset($row['id']);
                    $components[$link_type][$lid] = $row;
                }
            }

            $expecteds = [
                'Item_DeviceFirmware' => 1,
                'Item_DeviceMemory' => 0,
                'Item_DeviceHardDrive' => 0,
                'Item_DeviceNetworkCard' => 0,
                'Item_DevicePci' => 0,
                'Item_DevicePowerSupply' => 0,
                'Item_DeviceGeneric' => 0,
                'Item_DeviceSimcard' => 0,
            ];

            foreach ($expecteds as $type => $count) {
                $this->assertSame(
                    $count,
                    count($components[$type]),
                    sprintf(
                        'Expected %1$s %2$s, got %3$s of them',
                        $count,
                        $type,
                        count($components[$type])
                    )
                );
            }

            $expecteds = [
                'Item_DeviceFirmware' => [
                    [
                        'items_id' => $equipments_id,
                        'itemtype' => 'NetworkEquipment',
                        'devicefirmwares_id' => 104,
                        'is_deleted' => 0,
                        'is_dynamic' => 1,
                        'entities_id' => 0,
                        'is_recursive' => 0,
                        'serial' => null,
                        'otherserial' => null,
                        'locations_id' => 0,
                        'states_id' => 0,
                    ]
                ],
                'Item_DeviceMemory' => [],
                'Item_DeviceHardDrive' => [],
                'Item_DeviceNetworkCard' => [],
                'Item_DevicePci' => [],
                'Item_DevicePowerSupply' => [],
                'Item_DeviceGeneric' => [],
                'Item_DeviceSimcard' => [],
            ];

            foreach ($expecteds as $type => $expected) {
                $component = array_values($components[$type]);
               //hack to replace expected fkeys
                foreach ($expected as $i => &$row) {
                    foreach (array_keys($row) as $key) {
                        if (isForeignKeyField($key)) {
                            $row[$key] = $component[$i][$key];
                        }
                    }
                }
                $this->assertIsArray($component);
                $this->assertSame($expected, $component);
            }

            //ports connections
            $connections = $DB->request([
                'FROM'   => \NetworkPort_NetworkPort::getTable(),
                'WHERE'  => [
                    'networkports_id_1' => $all_ports_ids
                ]
            ]);

            $this->assertSame(
                $stacks[$stack_id]['connections'],
                count($connections),
                sprintf(
                    '%s connections found on stack %s, %s expected',
                    count($connections),
                    $stack_id,
                    $stacks[$stack_id]['connections']
                )
            );
        }

        $db_ports = $DB->request(['FROM' => \NetworkPort::getTable()]);
        $this->assertCount(325, $db_ports);

        $db_neteq_ports = $DB->request(['FROM' => \NetworkPort::getTable(), 'WHERE' => ['itemtype' => 'NetworkEquipment']]);
        $this->assertCount(265, $db_neteq_ports);

        $db_connections = $DB->request(['FROM' => \NetworkPort_NetworkPort::getTable()]);
        $this->assertCount(26, $db_connections);

        $db_unmanageds = $DB->request(['FROM' => \Unmanaged::getTable()]);
        $this->assertCount(45, $db_unmanageds);

        $db_ips = $DB->request(['FROM' => \IPAddress::getTable()]);
        $this->assertCount(150, $db_ips);

        $expected_names = [
            'san-replication',
            'leadiance-pub',
            'san-clients',
            'default',
            'prod',
            'backup',
            'management',
            '1060-pub',
            '1060-priv',
            'public_servlib',
            'UGIPS',
            '0001-pub'
        ];
        $db_vlans = $DB->request(['FROM' => \Vlan::getTable()]);
        $this->assertCount(count($expected_names), $db_vlans);

        $i = 0;
        foreach ($db_vlans as $row) {
            $this->assertSame($expected_names[$i], $row['name']);
            ++$i;
        }

        $db_vlans_ports = $DB->request(['FROM' => \NetworkPort_Vlan::getTable()]);
        $this->assertCount(219, $db_vlans_ports);

        $db_netnames = $DB->request(['FROM' => \NetworkName::getTable()]);
        $this->assertCount(10, $db_netnames);

        $expecteds = [
            [
                'name' => 'sw1-mgmt-eqnx',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '172.25.1.1',
            ], [
                'name' => '3k-1-th2',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '172.28.200.3',
            ], [
                'name' => 'sw2-mgmt-eqnx',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '172.25.1.2',
            ], [
                'name' => 'n9k-2-pa3(SAL1929KJ27)',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '10.1.0.58',
            ], [
                'name' => 'n9k-1-pa3(SAL1929KJ2J)',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '10.1.0.57',
            ]
        ];

        $i = 0;
       /*foreach ($unmanageds as $unmanaged) {
         foreach ($expecteds[$i] as $key => $value) {
            $this->>assertEquals($value, $unmanaged[$key]);
         }
         ++$i;
       }*/

        //check matchedlogs
        $mlogs = new \RuleMatchedLog();
        $found = $mlogs->find();
        $this->assertCount(48, $found);

        $mrules_criteria = [
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

        $neteq_criteria = $mrules_criteria;
        $neteq_criteria['WHERE'][] = ['itemtype' => \NetworkEquipment::getType()];
        $iterator = $DB->request($neteq_criteria);
        $this->assertCount($expected_eq_count, $iterator);
        foreach ($iterator as $neteq) {
            $this->assertSame('NetworkEquipment import (by serial)', $neteq['name']);
            $this->assertSame(\Glpi\Inventory\Request::INVENT_QUERY, $neteq['method']);
        }

        $unmanaged_criteria = $mrules_criteria;
        $unmanaged_criteria['WHERE'][] = ['itemtype' => \Unmanaged::getType()];
        $iterator = $DB->request($unmanaged_criteria);
        $this->assertCount(43, $iterator);
    }

    public function testImportStackedNetworkEquipment2()
    {
        $xml = file_get_contents(FIXTURE_DIR . '/inventories/stacked_switch_name.xml');

        $date_now = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date_now;
        $inventory = $this->doInventory($xml, true);

        //check inventory metadata
        $metadata = $inventory->getMetadata();

        $this->assertCount(5, $metadata);
        $this->assertSame('it-2024-05-11-14-34-10', $metadata['deviceid']);
        $this->assertSame('6.1', $metadata['version']);
        $this->assertSame('NetworkEquipment', $metadata['itemtype']);
        $this->assertNull($metadata['port']);
        $this->assertSame('netinventory', $metadata['action']);

        global $DB;
        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $this->assertSame('it-2024-05-11-14-34-10', $inventory->getAgent()->fields['deviceid']);
        $this->assertSame('it-2024-05-11-14-34-10', $inventory->getAgent()->fields['name']);
        $this->assertSame('NetworkEquipment', $inventory->getAgent()->fields['itemtype']);
        $this->assertSame($agenttype['id'], $inventory->getAgent()->fields['agenttypes_id']);

        //get model, manufacturer, ...
        $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->current();
        $this->assertIsArray($autoupdatesystems);
        $autoupdatesystems_id = $autoupdatesystems['id'];

        $cmodels = $DB->request(['FROM' => \NetworkEquipmentModel::getTable(), 'WHERE' => ['name' => 'JG937A']])->current();
        $this->assertIsArray($cmodels);
        $models_id = $cmodels['id'];

        $ctypes = $DB->request(['FROM' => \NetworkEquipmentType::getTable(), 'WHERE' => ['name' => 'Networking']])->current();
        $this->assertIsArray($ctypes);
        $types_id = $ctypes['id'];

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Hewlett-Packard']])->current();
        $this->assertIsArray($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $cloc = $DB->request(['FROM' => \Location::getTable(), 'WHERE' => ['name' => 'office']])->current();
        $this->assertIsArray($cloc);
        $locations_id = $cloc['id'];

        //check created equipments
        $expected_eq_count = 2;
        $iterator = $DB->request([
            'FROM'   => \NetworkEquipment::getTable(),
            'WHERE'  => ['is_dynamic' => 1]
        ]);
        $this->assertCount($expected_eq_count, $iterator);

        $main_expected = [
            'id' => null,
            'entities_id' => 0,
            'is_recursive' => 0,
            'name' => 'SW00Test - 5130EI',
            'ram' => null,
            'serial' => 'CX15GXXX99',
            'otherserial' => null,
            'contact' => 'admin@xy.z',
            'contact_num' => null,
            'users_id_tech' => 0,
            'groups_id_tech' => 0,
            'date_mod' => null,
            'comment' => null,
            'locations_id' => $locations_id,
            'networks_id' => 0,
            'networkequipmenttypes_id' => $types_id,
            'networkequipmentmodels_id' => $models_id,
            'manufacturers_id' => $manufacturers_id,
            'is_deleted' => 0,
            'is_template' => 0,
            'template_name' => null,
            'users_id' => 0,
            'groups_id' => 0,
            'states_id' => 0,
            'ticket_tco' => '0.0000',
            'is_dynamic' => 1,
            'uuid' => null,
            'date_creation' => null,
            'autoupdatesystems_id' => $autoupdatesystems_id,
            'sysdescr' => null,
            'cpu' => 0,
            'uptime' => '22 days, 00:08:54.24',
            'last_inventory_update' => $date_now,
            'snmpcredentials_id' => 0,
        ];

        $stacks = [
            1 => [
                'name' => 'SW00Test - 5130EI - 1',
                'serial' => 'CX15GXXX99',
                'connections'  => 0
            ],
            2 => [
                'name' => 'SW00Test - 5130EI - 2',
                'serial' => 'CN15CN123456',
                'connections' => 1
            ]
        ];

        foreach ($iterator as $row) {
            $expected = $main_expected;
            $equipments_id = $row['id'];
            $expected['id'] = $equipments_id;
            $equipment = new \NetworkEquipment();
            $this->assertTrue($equipment->getFromDB($equipments_id));
            $expected['date_mod'] = $row['date_mod'];
            $expected['date_creation'] = $row['date_creation'];
            $stack_id = preg_replace('/.+ - (\d)/', '$1', $row['name']);
            $this->assertIsArray($stacks);
            $this->assertArrayHasKey($stack_id, $stacks);
            $expected['name'] .= ' - ' . $stack_id;
            $expected['serial'] = $stacks[$stack_id]['serial'];
            $this->assertIsArray($row);
            $this->assertSame($expected, $row);
        }

        //check matchedlogs
        $mlogs = new \RuleMatchedLog();
        $found = $mlogs->find();
        $this->assertCount(2, $found);

        $mrules_criteria = [
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

        $neteq_criteria = $mrules_criteria;
        $neteq_criteria['WHERE'][] = ['itemtype' => \NetworkEquipment::getType()];
        $iterator = $DB->request($neteq_criteria);
        $this->assertCount($expected_eq_count, $iterator);
        foreach ($iterator as $neteq) {
            $this->assertSame('NetworkEquipment import (by serial)', $neteq['name']);
            $this->assertSame(\Glpi\Inventory\Request::INVENT_QUERY, $neteq['method']);
        }
    }
    public function testImportNetworkEquipmentMultiConnections()
    {
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'networkequipment_3.json'));

        $date_now = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date_now;
        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();

        $this->assertCount(5, $metadata);
        $this->assertSame('HP-2530-48G-2020-12-31-11-28-51', $metadata['deviceid']);
        $this->assertSame('2.5', $metadata['version']);
        $this->assertSame('NetworkEquipment', $metadata['itemtype']);
        $this->assertNull($metadata['port']);
        $this->assertSame('netinventory', $metadata['action']);

        global $DB;
        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $this->assertSame('HP-2530-48G-2020-12-31-11-28-51', $inventory->getAgent()->fields['deviceid']);
        $this->assertSame('HP-2530-48G-2020-12-31-11-28-51', $inventory->getAgent()->fields['name']);
        $this->assertSame('NetworkEquipment', $inventory->getAgent()->fields['itemtype']);
        $this->assertSame($agenttype['id'], $inventory->getAgent()->fields['agenttypes_id']);

        //get model, manufacturer, ...
        $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->current();
        $this->assertIsArray($autoupdatesystems);
        $autoupdatesystems_id = $autoupdatesystems['id'];

        $cmodels = $DB->request(['FROM' => \NetworkEquipmentModel::getTable(), 'WHERE' => ['name' => '2530-48G']])->current();
        $this->assertIsArray($cmodels);
        $models_id = $cmodels['id'];

        $ctypes = $DB->request(['FROM' => \NetworkEquipmentType::getTable(), 'WHERE' => ['name' => 'Networking']])->current();
        $this->assertIsArray($ctypes);
        $types_id = $ctypes['id'];

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Hewlett-Packard']])->current();
        $this->assertIsArray($cmanuf);
        $manufacturers_id = $cmanuf['id'];
        $locations_id = 0;

        //check created equipments
        $expected_count = 1;
        $iterator = $DB->request([
            'FROM'   => \NetworkEquipment::getTable(),
            'WHERE'  => ['is_dynamic' => 1]
        ]);
        $this->assertCount($expected_count, $iterator);

        $expected = [
            'id' => null,
            'entities_id' => 0,
            'is_recursive' => 0,
            'name' => 'HP-2530-48G',
            'ram' => null,
            'serial' => 'CN5BFP62CP',
            'otherserial' => null,
            'contact' => null,
            'contact_num' => null,
            'users_id_tech' => 0,
            'groups_id_tech' => 0,
            'date_mod' => null,
            'comment' => null,
            'locations_id' => $locations_id,
            'networks_id' => 0,
            'networkequipmenttypes_id' => $types_id,
            'networkequipmentmodels_id' => $models_id,
            'manufacturers_id' => $manufacturers_id,
            'is_deleted' => 0,
            'is_template' => 0,
            'template_name' => null,
            'users_id' => 0,
            'groups_id' => 0,
            'states_id' => 0,
            'ticket_tco' => '0.0000',
            'is_dynamic' => 1,
            'uuid' => null,
            'date_creation' => null,
            'autoupdatesystems_id' => $autoupdatesystems_id,
            'sysdescr' => null,
            'cpu' => 0,
            'uptime' => '(78894038) 9 days, 3:09:00.38',
            'last_inventory_update' => $date_now,
            'snmpcredentials_id' => 0,
        ];

        foreach ($iterator as $row) {
            $equipments_id = $row['id'];
            $expected['id'] = $equipments_id;
            $equipment = new \NetworkEquipment();
            $this->assertTrue($equipment->getFromDB($equipments_id));
            $expected['date_mod'] = $row['date_mod'];
            $expected['date_creation'] = $row['date_creation'];
            $this->assertIsArray($row);
            $this->assertSame($expected, $row);

            //check network ports
            $expected_count = 53;
            $ports_iterator = $DB->request([
                'FROM'   => \NetworkPort::getTable(),
                'WHERE'  => [
                    'items_id'           => $equipments_id,
                    'itemtype'           => 'NetworkEquipment',
                ],
            ]);
            $this->assertSame(
                $expected_count,
                count($ports_iterator),
                sprintf(
                    '%s ports found on %s, %s expected',
                    count($ports_iterator),
                    $row['name'],
                    $expected_count
                )
            );

            $expecteds = [
                ($expected_count - 1) => [
                    'logical_number' => 0,
                    'name' => 'Management',
                    'instantiation_type' => 'NetworkPortAggregate',
                    'mac' => 'b0:5a:da:10:10:80',
                ],
            ];

            $ips = [
                'Management' => [
                    '192.168.63.30',
                ]
            ];

            $i = 0;
            $netport = new \NetworkPort();
            $all_ports_ids = [];
            foreach ($ports_iterator as $port) {
                $ports_id = $port['id'];
                $all_ports_ids[] = $port['id'];
                $this->assertTrue($netport->getFromDB($ports_id));
                $instantiation = $netport->getInstantiation();
                if ($port['instantiation_type'] === null) {
                    $this->assertFalse($instantiation);
                } else {
                    $this->assertInstanceOf($port['instantiation_type'], $instantiation);
                }

                unset($port['id']);
                unset($port['date_creation']);
                unset($port['date_mod']);
                unset($port['comment']);

                if (isset($expecteds[$i])) {
                    $expected = $expecteds[$i];
                    $expected = $expected + [
                        'items_id' => $equipments_id,
                        'itemtype' => 'NetworkEquipment',
                        'entities_id' => 0,
                        'is_recursive' => 0,
                        'is_deleted' => 0,
                        'is_dynamic' => 1,
                        'ifmtu' => 0,
                        'ifspeed' => 0,
                        'ifinternalstatus' => null,
                        'ifconnectionstatus' => 0,
                        'iflastchange' => null,
                        'ifinbytes' => 0,
                        'ifinerrors' => 0,
                        'ifoutbytes' => 0,
                        'ifouterrors' => 0,
                        'ifstatus' => null,
                        'ifdescr' => null,
                        'ifalias' => null,
                        'portduplex' => null,
                        'trunk' => 0,
                        'lastup' => null
                    ];

                    $this->assertIsArray($port);
                    $this->assertEquals($expected, $port);
                } else {
                    $this->assertSame('NetworkEquipment', $port['itemtype']);
                    $this->assertSame($equipments_id, $port['items_id']);
                    $this->assertSame('NetworkPortEthernet', $port['instantiation_type'], print_r($port, true));
                    $this->assertMatchesRegularExpression(
                        '/^(?:(?:[0-9a-f]{2}[\:]{1}){5}|(?:[0-9a-f]{2}[-]{1}){5}|(?:[0-9a-f]{2}){5})[0-9a-f]{2}$/i',
                        $port['mac']
                    );
                    $this->assertSame(1, $port['is_dynamic']);
                }
                ++$i;

                //check for ips
                $ip_iterator = $DB->request([
                    'SELECT'       => [
                        \IPAddress::getTable() . '.name',
                        \IPAddress::getTable() . '.version'
                    ],
                    'FROM'   => \IPAddress::getTable(),
                    'INNER JOIN'   => [
                        \NetworkName::getTable()   => [
                            'ON'  => [
                                \IPAddress::getTable()     => 'items_id',
                                \NetworkName::getTable()   => 'id', [
                                    'AND' => [\IPAddress::getTable() . '.itemtype'  => \NetworkName::getType()]
                                ]
                            ]
                        ]
                    ],
                    'WHERE'  => [
                        \NetworkName::getTable() . '.itemtype'  => \NetworkPort::getType(),
                        \NetworkName::getTable() . '.items_id'  => $ports_id
                    ]
                ]);

                $this->assertCount(count($ips[$port['name']] ?? []), $ip_iterator);
                if (isset($ips[$port['name']])) {
                    foreach ($ip_iterator as $ip) {
                        $this->assertIsArray($ips[$port['name']]);
                        $this->assertTrue(in_array($ip['name'], $ips[$port['name']]));
                    }
                }
            }

            //check for components
            $components = [];
            $allcount = 0;
            foreach (\Item_Devices::getItemAffinities('NetworkEquipment') as $link_type) {
                $link = getItemForItemtype($link_type);
                $dev_iterator = $DB->request($link->getTableGroupCriteria($equipment));
                $allcount += count($dev_iterator);
                $components[$link_type] = [];

                foreach ($dev_iterator as $row) {
                    $lid = $row['id'];
                    unset($row['id']);
                    $components[$link_type][$lid] = $row;
                }
            }

            $expecteds = [
                'Item_DeviceFirmware' => 1,
                'Item_DeviceMemory' => 0,
                'Item_DeviceHardDrive' => 0,
                'Item_DeviceNetworkCard' => 0,
                'Item_DevicePci' => 0,
                'Item_DevicePowerSupply' => 0,
                'Item_DeviceGeneric' => 0,
                'Item_DeviceSimcard' => 0,
            ];

            foreach ($expecteds as $type => $count) {
                $this->assertSame(
                    $count,
                    count($components[$type]),
                    sprintf(
                        'Expected %1$s %2$s, got %3$s of them',
                        $count,
                        $type,
                        count($components[$type])
                    )
                );
            }

            $expecteds = [
                'Item_DeviceFirmware' => [
                    [
                        'items_id' => $equipments_id,
                        'itemtype' => 'NetworkEquipment',
                        'devicefirmwares_id' => 104,
                        'is_deleted' => 0,
                        'is_dynamic' => 1,
                        'entities_id' => 0,
                        'is_recursive' => 0,
                        'serial' => null,
                        'otherserial' => null,
                        'locations_id' => 0,
                        'states_id' => 0,
                    ]
                ],
                'Item_DeviceMemory' => [],
                'Item_DeviceHardDrive' => [],
                'Item_DeviceNetworkCard' => [],
                'Item_DevicePci' => [],
                'Item_DevicePowerSupply' => [],
                'Item_DeviceGeneric' => [],
                'Item_DeviceSimcard' => [],
            ];

            foreach ($expecteds as $type => $expected) {
                $component = array_values($components[$type]);
                //hack to replace expected fkeys
                foreach ($expected as $i => &$row) {
                    foreach (array_keys($row) as $key) {
                        if (isForeignKeyField($key)) {
                            $row[$key] = $component[$i][$key];
                        }
                    }
                }
                $this->assertIsArray($component);
                $this->assertSame($expected, $component);
            }

            //ports connections
            $connections = $DB->request([
                'FROM'   => \NetworkPort_NetworkPort::getTable(),
            ]);

            $this->assertCount(63, $connections);
        }

        $connections = $DB->request(['FROM' => \NetworkPort_NetworkPort::getTable()]);
        $this->assertCount(63, $connections);

        $unmanageds = $DB->request(['FROM' => \Unmanaged::getTable()]);
        $this->assertCount(63, $unmanageds);

        $expecteds = [
            [
                'name' => 'Cisco IP Phone SPA303',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '192.168.63.52',
            ], [
                'name' => 'Cisco IP Phone SPA303',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '192.168.63.156',
            ], [
                'name' => 'Cisco IP Phone SPA303',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '192.168.63.40',
            ], [
                'name' => 'Hewlett Packard',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Cisco IP Phone SPA303',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '192.168.63.55',
            ], [
                'name' => 'Cisco IP Phone SPA303',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '192.168.63.81',
            ], [
                'name' => 'Xiamen Yeastar Information Technology Co., Ltd.',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Cisco IP Phone SPA303',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '192.168.63.115',
            ], [
                'name' => 'Cisco IP Phone SPA303',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '192.168.63.76',
            ], [
                'name' => 'Cisco IP Phone SPA303',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '192.168.63.97',
            ], [
                'name' => 'Cisco IP Phone SPA303',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '192.168.63.167',
            ], [
                'name' => 'G-PRO COMPUTER',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Cisco IP Phone SPA303',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '192.168.63.82',
            ], [
                'name' => 'Cisco IP Phone SPA303',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '192.168.63.112',
            ], [
                'name' => 'Cisco IP Phone SPA303',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '192.168.63.80',
            ], [
                'name' => 'G-PRO COMPUTER',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Cisco IP Phone SPA303',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '192.168.63.157',
            ], [
                'name' => 'G-PRO COMPUTER',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Cisco IP Phone SPA303',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '192.168.63.87',
            ], [
                'name' => 'Cisco IP Phone SPA303',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '192.168.63.108',
            ], [
                'name' => 'Cisco IP Phone SPA303',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '192.168.63.54',
            ], [
                'name' => 'Cisco IP Phone SPA303',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '192.168.63.88',
            ], [
                'name' => 'Intel Corporate',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Samsung Electronics Co.,Ltd',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Intel Corporate',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Intel Corporate',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Intel Corporate',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Hon Hai Precision Ind. Co.,Ltd.',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Hon Hai Precision Ind. Co.,Ltd.',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Apple, Inc.',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Intel Corporate',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Intel Corporate',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Intel Corporate',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Samsung Electronics Co.,Ltd',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Ubiquiti Inc',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Intel Corporate',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Intel Corporate',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Apple, Inc.',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Hub',
                'accepted' => 0,
                'hub' => 1,
                'ip' => null,
            ], [
                'name' => 'Intel Corporate',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Intel Corporate',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Intel Corporate',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Apple, Inc.',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'SAMSUNG ELECTRO-MECHANICS(THAILAND)',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Ubiquiti Inc',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Hub',
                'accepted' => 0,
                'hub' => 1,
                'ip' => null,
            ], [
                'name' => 'G-PRO COMPUTER',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'ASUSTek COMPUTER INC.',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'G-PRO COMPUTER',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Cisco IP Phone SPA303',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '192.168.63.146',
            ], [
                'name' => 'G-PRO COMPUTER',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Cisco IP Phone SPA303',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '192.168.63.140',
            ], [
                'name' => 'KYOCERA Display Corporation',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Routerboard.com',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Routerboard.com',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Apple, Inc.',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Ubiquiti Inc',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Apple, Inc.',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Apple, Inc.',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Hub',
                'accepted' => 0,
                'hub' => 1,
                'ip' => null,
            ], [
                'name' => 'Microsoft Corporation',
                'accepted' => 0,
                'hub' => 0,
                'ip' => null,
            ], [
                'name' => 'Cisco IP Phone SPA303',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '192.168.63.51',
            ], [
                'name' => 'Cisco IP Phone SPA303',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '192.168.63.128',
            ]
        ];

        $this->assertCount($unmanageds->count(), $expecteds);

        $i = 0;
        foreach ($unmanageds as $unmanaged) {
            foreach ($expecteds[$i] as $key => $value) {
                $this->assertEquals($value, $unmanaged[$key]);
            }
            ++$i;
        }

        //check matchedlogs
        $mlogs = new \RuleMatchedLog();
        $found = $mlogs->find();
        $this->assertCount(61, $found);

        $mrules_criteria = [
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

        $neteq_criteria = $mrules_criteria;
        $neteq_criteria['WHERE'][] = ['itemtype' => \NetworkEquipment::getType()];
        $iterator = $DB->request($neteq_criteria);
        $this->assertCount(1, $iterator);
        foreach ($iterator as $neteq) {
            $this->assertSame('NetworkEquipment import (by serial)', $neteq['name']);
            $this->assertSame($equipments_id, $neteq['items_id']);
            $this->assertSame(\Glpi\Inventory\Request::INVENT_QUERY, $neteq['method']);
        }

        $unmanaged_criteria = $mrules_criteria;
        $unmanaged_criteria['WHERE'][] = ['itemtype' => \Unmanaged::getType()];
        $iterator = $DB->request($unmanaged_criteria);
        $this->assertCount(60, $iterator);
    }

    public function testImportNetworkEquipmentWireless()
    {
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'networkequipment_4.json'));

        $date_now = date('Y-m-d H:i:s');
        $_SESSION["glpi_currenttime"] = $date_now;
        $inventory = $this->doInventory($json);

       //check inventory metadata
        $metadata = $inventory->getMetadata();

        $this->assertCount(5, $metadata);
        $this->assertSame('CH-GV1-DSI-WLC-INSID-1-2020-12-31-11-28-51', $metadata['deviceid']);
        $this->assertSame('4.1', $metadata['version']);
        $this->assertSame('NetworkEquipment', $metadata['itemtype']);
        $this->assertNull($metadata['port']);
        $this->assertSame('netinventory', $metadata['action']);

        global $DB;
       //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $this->assertSame('CH-GV1-DSI-WLC-INSID-1-2020-12-31-11-28-51', $inventory->getAgent()->fields['deviceid']);
        $this->assertSame('CH-GV1-DSI-WLC-INSID-1-2020-12-31-11-28-51', $inventory->getAgent()->fields['name']);
        $this->assertSame('NetworkEquipment', $inventory->getAgent()->fields['itemtype']);
        $this->assertSame($agenttype['id'], $inventory->getAgent()->fields['agenttypes_id']);

       //get model, manufacturer, ...
        $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->current();
        $this->assertIsArray($autoupdatesystems);
        $autoupdatesystems_id = $autoupdatesystems['id'];

        $cmodels = $DB->request(['FROM' => \NetworkEquipmentModel::getTable(), 'WHERE' => ['name' => 'CT5520']])->current();
        $this->assertIsArray($cmodels);
        $models_id = $cmodels['id'];

        $ctypes = $DB->request(['FROM' => \NetworkEquipmentType::getTable(), 'WHERE' => ['name' => 'Networking']])->current();
        $this->assertIsArray($ctypes);
        $types_id = $ctypes['id'];

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Cisco']])->current();
        $this->assertIsArray($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $cloc = $DB->request(['FROM' => \Location::getTable(), 'WHERE' => ['name' => 'MERY']])->current();
        $this->assertIsArray($cloc);
        $locations_id = $cloc['id'];

        //check created equipments
        $expected_eq_count = 302;
        $iterator = $DB->request([
            'FROM'   => \NetworkEquipment::getTable(),
            'WHERE'  => ['is_dynamic' => 1]
        ]);
        $this->assertCount($expected_eq_count, $iterator);

        $expected_eq = [
            'id' => null,
            'entities_id' => 0,
            'is_recursive' => 0,
            'name' => 'CH-GV1-DSI-WLC-INSID-1',
            'ram' => null,
            'serial' => 'FCH1946V219',
            'otherserial' => null,
            'contact' => null,
            'contact_num' => null,
            'users_id_tech' => 0,
            'groups_id_tech' => 0,
            'date_mod' => null,
            'comment' => null,
            'locations_id' => $locations_id,
            'networks_id' => 0,
            'networkequipmenttypes_id' => $types_id,
            'networkequipmentmodels_id' => $models_id,
            'manufacturers_id' => $manufacturers_id,
            'is_deleted' => 0,
            'is_template' => 0,
            'template_name' => null,
            'users_id' => 0,
            'groups_id' => 0,
            'states_id' => 0,
            'ticket_tco' => '0.0000',
            'is_dynamic' => 1,
            'uuid' => null,
            'date_creation' => null,
            'autoupdatesystems_id' => $autoupdatesystems_id,
            'sysdescr' => null,
            'cpu' => 0,
            'uptime' => '53 days, 4:19:42.16',
            'last_inventory_update' => $date_now,
            'snmpcredentials_id' => 0,
        ];

        $first = true;
        foreach ($iterator as $row) {
            $equipments_id = $row['id'];
            $expected_eq['id'] = $equipments_id;
            $equipment = new \NetworkEquipment();
            $this->assertTrue($equipment->getFromDB($equipments_id));
            $expected_eq['date_mod'] = $row['date_mod'];
            $expected_eq['date_creation'] = $row['date_creation'];
            if (!$first) {
                $expected_eq['name'] = $row['name'];
                $expected_eq['serial'] = $row['serial'];
                $expected_eq['locations_id'] = $row['locations_id'];
                $expected_eq['networkequipmenttypes_id'] = $row['networkequipmenttypes_id'];
                $expected_eq['networkequipmentmodels_id'] = $row['networkequipmentmodels_id'];
                $expected_eq['manufacturers_id'] = $row['manufacturers_id'];
            }
            $this->assertIsArray($row);
            $this->assertSame($expected_eq, $row, print_r($row, true) . print_r($expected_eq, true));

           //check network ports
            $expected_count = ($first ? 4 : 1);
            $ports_iterator = $DB->request([
                'FROM'   => \NetworkPort::getTable(),
                'WHERE'  => [
                    'items_id'           => $equipments_id,
                    'itemtype'           => 'NetworkEquipment',
                ],
            ]);
            $this->assertSame(
                $expected_count,
                count($ports_iterator),
                sprintf(
                    '%s ports found on %s, %s expected',
                    count($ports_iterator),
                    $row['name'],
                    $expected_count
                )
            );

            $expecteds = [
                'Management' => [
                    'logical_number' => 0,
                    'name' => 'Management',
                    'instantiation_type' => 'NetworkPortAggregate',
                    'mac' => '58:ac:78:59:45:fb',
                ],
            ];

            $ips = [
                'Management' => [
                    '1.1.1.1',
                    '10.65.0.184',
                    '10.65.0.192',
                    '169.254.0.192',
                    '192.168.200.116'
                ]
            ];

            $i = 0;
            $netport = new \NetworkPort();
            $all_ports_ids = [];
            foreach ($ports_iterator as $port) {
                $ports_id = $port['id'];
                $all_ports_ids[] = $port['id'];
                $this->assertTrue($netport->getFromDB($ports_id));
                $instantiation = $netport->getInstantiation();
                if ($port['instantiation_type'] === null) {
                    $this->assertFalse($instantiation);
                } else {
                    $this->assertInstanceOf($port['instantiation_type'], $instantiation);
                }

                unset($port['id']);
                unset($port['date_creation']);
                unset($port['date_mod']);
                unset($port['comment']);

                if ($port['mac'] == '58:ac:78:59:45:fb') {
                    $expected = $expecteds['Management'];
                    $expected = $expected + [
                        'items_id' => $equipments_id,
                        'itemtype' => 'NetworkEquipment',
                        'entities_id' => 0,
                        'is_recursive' => 0,
                        'is_deleted' => 0,
                        'is_dynamic' => 1,
                        'ifmtu' => 0,
                        'ifspeed' => 0,
                        'ifinternalstatus' => null,
                        'ifconnectionstatus' => 0,
                        'iflastchange' => null,
                        'ifinbytes' => 0,
                        'ifinerrors' => 0,
                        'ifoutbytes' => 0,
                        'ifouterrors' => 0,
                        'ifstatus' => null,
                        'ifdescr' => null,
                        'ifalias' => null,
                        'portduplex' => null,
                        'trunk' => 0,
                        'lastup' => null
                    ];

                    $this->assertIsArray($port);
                    $this->assertEquals($expected, $port);
                } else {
                    $this->assertSame('NetworkEquipment', $port['itemtype']);
                    $this->assertSame($equipments_id, $port['items_id']);
                   //$this->assertSame('NetworkPortAggregate', print_r($port, true), $port['instantiation_type']);
                    $this->assertMatchesRegularExpression(
                        '/^(?:(?:[0-9a-f]{2}[\:]{1}){5}|(?:[0-9a-f]{2}[-]{1}){5}|(?:[0-9a-f]{2}){5})[0-9a-f]{2}$/i',
                        $port['mac']
                    );
                    $this->assertSame(1, $port['is_dynamic']);
                }
                ++$i;

               //check for ips
                $ip_iterator = $DB->request([
                    'SELECT'       => [
                        \IPAddress::getTable() . '.name',
                        \IPAddress::getTable() . '.version'
                    ],
                    'FROM'   => \IPAddress::getTable(),
                    'INNER JOIN'   => [
                        \NetworkName::getTable()   => [
                            'ON'  => [
                                \IPAddress::getTable()     => 'items_id',
                                \NetworkName::getTable()   => 'id', [
                                    'AND' => [\IPAddress::getTable() . '.itemtype'  => \NetworkName::getType()]
                                ]
                            ]
                        ]
                    ],
                    'WHERE'  => [
                        \NetworkName::getTable() . '.itemtype'  => \NetworkPort::getType(),
                        \NetworkName::getTable() . '.items_id'  => $ports_id
                    ]
                ]);

               //$this->assertCount(count($ips[$port['name']] ?? ['one' => 'one']), $ip_iterator);
                if ($port['mac'] == '58:ac:78:59:45:fb') {
                    foreach ($ip_iterator as $ip) {
                        $this->assertIsArray($ips[$port['name']]);
                        $this->assertTrue(in_array($ip['name'], $ips[$port['name']]));
                    }
                }
            }

           //check for components
            $components = [];
            $allcount = 0;
            foreach (\Item_Devices::getItemAffinities('NetworkEquipment') as $link_type) {
                $link = getItemForItemtype($link_type);
                $dev_iterator = $DB->request($link->getTableGroupCriteria($equipment));
                $allcount += count($dev_iterator);
                $components[$link_type] = [];

                foreach ($dev_iterator as $row) {
                    $lid = $row['id'];
                    unset($row['id']);
                    $components[$link_type][$lid] = $row;
                }
            }

            $expecteds = [
                'Item_DeviceFirmware' => 1,
                'Item_DeviceMemory' => 0,
                'Item_DeviceHardDrive' => 0,
                'Item_DeviceNetworkCard' => 0,
                'Item_DevicePci' => 0,
                'Item_DevicePowerSupply' => 0,
                'Item_DeviceGeneric' => 0,
                'Item_DeviceSimcard' => 0,
            ];

            foreach ($expecteds as $type => $count) {
                $this->assertSame(
                    $count,
                    count($components[$type]),
                    sprintf(
                        'Expected %1$s %2$s, got %3$s of them',
                        $count,
                        $type,
                        count($components[$type])
                    )
                );
            }

            $expecteds = [
                'Item_DeviceFirmware' => [
                    [
                        'items_id' => $equipments_id,
                        'itemtype' => 'NetworkEquipment',
                        'devicefirmwares_id' => 104,
                        'is_deleted' => 0,
                        'is_dynamic' => 1,
                        'entities_id' => 0,
                        'is_recursive' => 0,
                        'serial' => null,
                        'otherserial' => null,
                        'locations_id' => 0,
                        'states_id' => 0,
                    ]
                ],
                'Item_DeviceMemory' => [],
                'Item_DeviceHardDrive' => [],
                'Item_DeviceNetworkCard' => [],
                'Item_DevicePci' => [],
                'Item_DevicePowerSupply' => [],
                'Item_DeviceGeneric' => [],
                'Item_DeviceSimcard' => [],
            ];

            foreach ($expecteds as $type => $expected) {
                $component = array_values($components[$type]);
               //hack to replace expected fkeys
                foreach ($expected as $i => &$row) {
                    foreach (array_keys($row) as $key) {
                        if (isForeignKeyField($key)) {
                            $row[$key] = $component[$i][$key];
                        }
                    }
                }
                $this->assertIsArray($component);
                $this->assertSame($expected, $component);
            }

            $first = false;
        }

        $connections = $DB->request(['FROM' => \NetworkPort_NetworkPort::getTable()]);
        $this->assertCount(2, $connections);

        $unmanageds = $DB->request(['FROM' => \Unmanaged::getTable()]);
        $this->assertCount(2, $unmanageds);

        $expecteds = [
            [
                'name' => 'CH-GV2-DSI-SW-BBONE-1(FOX1819GEG6)',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '192.168.200.6',
            ], [
                'name' => 'CH-GV2-DSI-SW-BBONE-2(FOX1819GEG1)',
                'accepted' => 0,
                'hub' => 0,
                'ip' => '192.168.200.7',
            ]
        ];

        $i = 0;
        foreach ($unmanageds as $unmanaged) {
            foreach ($expecteds[$i] as $key => $value) {
                $this->assertEquals($value, $unmanaged[$key]);
            }
            ++$i;
        }

       //check matchedlogs
        $mlogs = new \RuleMatchedLog();
        $found = $mlogs->find();
        $this->assertCount($expected_eq_count + count($unmanageds), $found);

        $mrules_criteria = [
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

        $neteq_criteria = $mrules_criteria;
        $neteq_criteria['WHERE'][] = ['itemtype' => \NetworkEquipment::getType()];
        $iterator = $DB->request($neteq_criteria);
        $this->assertCount($expected_eq_count, $iterator);
        foreach ($iterator as $neteq) {
            $this->assertSame('NetworkEquipment import (by serial)', $neteq['name']);
            $this->assertSame(\Glpi\Inventory\Request::INVENT_QUERY, $neteq['method']);
        }

        $unmanaged_criteria = $mrules_criteria;
        $unmanaged_criteria['WHERE'][] = ['itemtype' => \Unmanaged::getType()];
        $iterator = $DB->request($unmanaged_criteria);
        $this->assertCount(count($unmanageds), $iterator);
        foreach ($iterator as $unmanaged) {
            $this->assertSame('Global import (by ip+ifdescr)', $unmanaged['name']);
            $this->assertSame(\Glpi\Inventory\Request::INVENT_QUERY, $unmanaged['method']);
        }
    }

    public function testImportNetworkEquipmentWAggregatedPorts()
    {
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'networkequipment_5.json'));

        $date_now = date('Y-m-d H:i:s');
        $_SESSION["glpi_currenttime"] = $date_now;
        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();

        $this->assertCount(5, $metadata);
        $this->assertSame('DGS-3420-52T-2020-12-31-11-28-51', $metadata['deviceid']);
        $this->assertSame('4.1', $metadata['version']);
        $this->assertSame('NetworkEquipment', $metadata['itemtype']);
        $this->assertNull($metadata['port']);
        $this->assertSame('netinventory', $metadata['action']);

        global $DB;
        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $this->assertSame('DGS-3420-52T-2020-12-31-11-28-51', $inventory->getAgent()->fields['deviceid']);
        $this->assertSame('DGS-3420-52T-2020-12-31-11-28-51', $inventory->getAgent()->fields['name']);
        $this->assertSame('NetworkEquipment', $inventory->getAgent()->fields['itemtype']);
        $this->assertSame($agenttype['id'], $inventory->getAgent()->fields['agenttypes_id']);

        //get model, manufacturer, ...
        $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->current();
        $this->assertIsArray($autoupdatesystems);
        $autoupdatesystems_id = $autoupdatesystems['id'];

        $cmodels = $DB->request(['FROM' => \NetworkEquipmentModel::getTable(), 'WHERE' => ['name' => 'DGS-3420-52T']])->current();
        $this->assertIsArray($cmodels);
        $models_id = $cmodels['id'];

        $ctypes = $DB->request(['FROM' => \NetworkEquipmentType::getTable(), 'WHERE' => ['name' => 'Networking']])->current();
        $this->assertIsArray($ctypes);
        $types_id = $ctypes['id'];

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'D-Link']])->current();
        $this->assertIsArray($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $cloc = $DB->request(['FROM' => \Location::getTable(), 'WHERE' => ['name' => 'WOB Serverraum']])->current();
        $this->assertIsArray($cloc);
        $locations_id = $cloc['id'];

        //check created computer
        $equipments_id = $inventory->getAgent()->fields['items_id'];
        $this->assertGreaterThan(0, $equipments_id);
        $equipment = new \NetworkEquipment();
        $this->assertTrue($equipment->getFromDB($equipments_id));

        $expected = [
            'id' => $equipments_id,
            'entities_id' => 0,
            'is_recursive' => 0,
            'name' => 'DGS-3420-52T',
            'ram' => null,
            'serial' => 'R3843D1000001',
            'otherserial' => null,
            'contact' => 'noc@glpi-project.org',
            'contact_num' => null,
            'users_id_tech' => 0,
            'groups_id_tech' => 0,
            'date_mod' => $equipment->fields['date_mod'],
            'comment' => null,
            'locations_id' => $locations_id,
            'networks_id' => 0,
            'networkequipmenttypes_id' => $types_id,
            'networkequipmentmodels_id' => $models_id,
            'manufacturers_id' => $manufacturers_id,
            'is_deleted' => 0,
            'is_template' => 0,
            'template_name' => null,
            'users_id' => 0,
            'groups_id' => 0,
            'states_id' => 0,
            'ticket_tco' => '0.0000',
            'is_dynamic' => 1,
            'uuid' => null,
            'date_creation' => $equipment->fields['date_creation'],
            'autoupdatesystems_id' => $autoupdatesystems_id,
            'sysdescr' => null,
            'cpu' => 0,
            'uptime' => '65 days, 20:13:08.93',
            'last_inventory_update' => $date_now,
            'snmpcredentials_id' => 0,
        ];
        $this->assertIsArray($equipment->fields);
        $this->assertSame($expected, $equipment->fields);

        //check network ports
        $expected_count = 53;
        $iterator = $DB->request([
            'FROM'   => \NetworkPort::getTable(),
            'WHERE'  => [
                'items_id'           => $equipments_id,
                'itemtype'           => 'NetworkEquipment',
            ],
        ]);
        $this->assertCount($expected_count, $iterator);

        $expecteds = [
            0 => [
                'itemtype' => 'NetworkEquipment',
                'logical_number' => 1,
                'name' => '1/1',
                'instantiation_type' => 'NetworkPortAggregate',
                'mac' => 'ac:f1:df:8e:8e:00',
                'ifmtu' => 1500,
                'ifspeed' => 1000000000,
                'ifinternalstatus' => '1',
                'iflastchange' => '16 days, 3:10:22.89',
                'ifstatus' => '1',
                'ifdescr' => 'D-Link DGS-3420-52T R1.50.B03 Port 1 on Unit 1',
                'ifinbytes' => 1636476664,
                'ifoutbytes' => 2829646176,
                'portduplex' => 3
            ],
            ($expected_count - 1) => [
                'logical_number' => 0,
                'name' => 'Management',
                'instantiation_type' => 'NetworkPortAggregate',
                'mac' => 'ac:f1:df:8e:8e:00',
            ],
        ];

        $ips = [
            'Management' => [
                '192.168.16.51',
            ]
        ];

        $i = 0;
        $netport = new \NetworkPort();
        foreach ($iterator as $port) {
            $ports_id = $port['id'];
            $this->assertTrue($netport->getFromDB($ports_id));
            $instantiation = $netport->getInstantiation();
            if ($port['instantiation_type'] === null) {
                $this->assertFalse($instantiation);
            } else {
                $this->assertInstanceOf($port['instantiation_type'], $instantiation);
            }

            unset($port['id']);
            unset($port['date_creation']);
            unset($port['date_mod']);
            unset($port['comment']);

            if (isset($expecteds[$i])) {
                $expected = $expecteds[$i];
                $expected = $expected + [
                    'items_id' => $equipments_id,
                    'itemtype' => 'NetworkEquipment',
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'ifmtu' => 0,
                    'ifspeed' => 0,
                    'ifinternalstatus' => null,
                    'ifconnectionstatus' => 0,
                    'iflastchange' => null,
                    'ifinbytes' => null,
                    'ifinerrors' => 0,
                    'ifoutbytes' => 0,
                    'ifouterrors' => 0,
                    'ifstatus' => null,
                    'ifdescr' => null,
                    'ifalias' => null,
                    'portduplex' => null,
                    'trunk' => 0,
                    'lastup' => null
                ];

                $this->assertIsArray($port);
                $this->assertEquals($expected, $port);
            } else {
                $this->assertSame('NetworkEquipment', $port['itemtype']);
                $this->assertSame($equipments_id, $port['items_id']);
                $this->assertSame('NetworkPortEthernet', $port['instantiation_type'], print_r($port, true));
                $this->assertMatchesRegularExpression(
                    '/^(?:(?:[0-9a-f]{2}[\:]{1}){5}|(?:[0-9a-f]{2}[-]{1}){5}|(?:[0-9a-f]{2}){5})[0-9a-f]{2}$/i',
                    $port['mac']
                );
                $this->assertSame(1, $port['is_dynamic']);
            }
            ++$i;

            //check for ips
            $ip_iterator = $DB->request([
                'SELECT'       => [
                    \IPAddress::getTable() . '.name',
                    \IPAddress::getTable() . '.version'
                ],
                'FROM'   => \IPAddress::getTable(),
                'INNER JOIN'   => [
                    \NetworkName::getTable()   => [
                        'ON'  => [
                            \IPAddress::getTable()     => 'items_id',
                            \NetworkName::getTable()   => 'id', [
                                'AND' => [\IPAddress::getTable() . '.itemtype'  => \NetworkName::getType()]
                            ]
                        ]
                    ]
                ],
                'WHERE'  => [
                    \NetworkName::getTable() . '.itemtype'  => \NetworkPort::getType(),
                    \NetworkName::getTable() . '.items_id'  => $ports_id
                ]
            ]);

            $this->assertCount(count($ips[$port['name']] ?? []), $ip_iterator);
            if (isset($ips[$port['name']])) {
                foreach ($ip_iterator as $ip) {
                    $this->assertIsArray($ips[$port['name']]);
                    $this->assertTrue(in_array($ip['name'], $ips[$port['name']]));
                }
            }
        }

        //check for components
        $components = [];
        $allcount = 0;
        foreach (\Item_Devices::getItemAffinities('NetworkEquipment') as $link_type) {
            $link        = getItemForItemtype($link_type);
            $iterator = $DB->request($link->getTableGroupCriteria($equipment));
            $allcount += count($iterator);
            $components[$link_type] = [];

            foreach ($iterator as $row) {
                $lid = $row['id'];
                unset($row['id']);
                $components[$link_type][$lid] = $row;
            }
        }

        $expecteds = [
            'Item_DeviceFirmware' => 1,
            'Item_DeviceMemory' => 0,
            'Item_DeviceHardDrive' => 0,
            'Item_DeviceNetworkCard' => 0,
            'Item_DevicePci' => 0,
            'Item_DevicePowerSupply' => 0,
            'Item_DeviceGeneric' => 0,
            'Item_DeviceSimcard' => 0,
        ];

        foreach ($expecteds as $type => $count) {
            $this->assertSame(
                $count,
                count($components[$type]),
                sprintf(
                    'Expected %1$s %2$s, got %3$s of them',
                    $count,
                    $type,
                    count($components[$type])
                )
            );
        }

        $expecteds = [
            'Item_DeviceFirmware' => [
                [
                    'items_id' => $equipments_id,
                    'itemtype' => 'NetworkEquipment',
                    'devicefirmwares_id' => 104,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ]
            ],
            'Item_DeviceMemory' => [],
            'Item_DeviceHardDrive' => [],
            'Item_DeviceNetworkCard' => [],
            'Item_DevicePci' => [],
            'Item_DevicePowerSupply' => [],
            'Item_DeviceGeneric' => [],
            'Item_DeviceSimcard' => [],
        ];

        foreach ($expecteds as $type => $expected) {
            $component = array_values($components[$type]);
           //hack to replace expected fkeys
            foreach ($expected as $i => &$row) {
                foreach (array_keys($row) as $key) {
                    if (isForeignKeyField($key)) {
                        $row[$key] = $component[$i][$key];
                    }
                }
            }
            $this->assertIsArray($component);
            $this->assertSame($expected, $component);
        }

        //ports connections
        $connections = $DB->request(['FROM' => \NetworkPort_NetworkPort::getTable()]);
        $this->assertCount(36, $connections);

        //unmanaged equipments
        $unmanageds = $DB->request(['FROM' => \Unmanaged::getTable()]);
        $this->assertCount(36, $unmanageds);
    }

    public function testImportRefusedFromAssetRulesWithNoLog()
    {
        $rule = new \Rule();

        //prepares needed rules id
        $this->assertTrue(
            $rule->getFromDBByCrit(['name' => 'Computer constraint (name)'])
        );
        $rules_id_torefuse = $rule->fields['id'];

        $this->assertTrue(
            $rule->getFromDBByCrit(['name' => 'Computer import denied'])
        );
        $rules_id_refuse = $rule->fields['id'];
        // update action to refused import with no log
        $action = new \RuleAction();
        $action->getFromDBByCrit([
            "rules_id" => $rules_id_refuse,
        ]);
        $action->fields['field'] = '_inventory';
        $action->fields['value'] = 2;
        $action->update($action->fields);


        $this->assertTrue(
            $rule->getFromDBByCrit(['name' => 'Computer import (by name)'])
        );
        $rules_id_toaccept = $rule->fields['id'];

        //move rule to refuse computer inventory
        $rulecollection = new \RuleImportAssetCollection();
        $this->assertTrue(
            $rulecollection->moveRule(
                $rules_id_refuse,
                $rules_id_torefuse,
                \RuleCollection::MOVE_BEFORE
            )
        );

        //do inventory
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));
        $inventory = $this->doInventory($json);

        //move rule back to accept computer inventory
        $this->assertTrue(
            $rulecollection->moveRule(
                $rules_id_refuse,
                $rules_id_toaccept,
                \RuleCollection::MOVE_AFTER
            )
        );

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->assertCount(7, $metadata);
        $this->assertSame('glpixps-2018-07-09-09-07-13', $metadata['deviceid']);
        $this->assertSame('FusionInventory-Agent_v2.5.2-1.fc31', $metadata['version']);
        $this->assertSame('Computer', $metadata['itemtype']);
        $this->assertSame('000005', $metadata['tag']);
        $this->assertNull($metadata['port']);
        $this->assertSame('inventory', $metadata['action']);
        $this->assertCount(10, $metadata['provider']);

        global $DB;
        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();
        $this->assertSame('glpixps-2018-07-09-09-07-13', $agent['deviceid']);
        $this->assertSame('glpixps-2018-07-09-09-07-13', $agent['name']);
        $this->assertSame('2.5.2-1.fc31', $agent['version']);
        $this->assertSame('Computer', $agent['itemtype']);
        $this->assertSame('000005', $agent['tag']);
        $this->assertSame($agenttype['id'], $agent['agenttypes_id']);

        $computers_id = $agent['items_id'];
        $this->assertSame(0, $computers_id);

        $iterator = $DB->request([
            'FROM'   => \RefusedEquipment::getTable(),
        ]);
        $this->assertCount(0, $iterator);
    }

    public function testImportRefusedFromAssetRulesWithLog()
    {
        $rule = new \Rule();

        //prepares needed rules id
        $this->assertTrue(
            $rule->getFromDBByCrit(['name' => 'Computer constraint (name)'])
        );
        $rules_id_torefuse = $rule->fields['id'];


        $this->assertTrue(
            $rule->getFromDBByCrit(['name' => 'Computer import denied'])
        );
        $rules_id_refuse = $rule->fields['id'];

        //update ruleAction to refused import with log
        $ruleaction = new \RuleAction();
        $this->assertTrue($ruleaction->getFromDBByCrit(['rules_id' => $rules_id_refuse]));
        $this->assertTrue(
            $ruleaction->update([
                'id'    => $ruleaction->fields['id'],
                'field' => '_ignore_import',
                'action_type' => 'assign',
                'value' => 1
            ])
        );

        $this->assertTrue(
            $rule->getFromDBByCrit(['name' => 'Computer import (by name)'])
        );
        $rules_id_toaccept = $rule->fields['id'];

        //move rule to refuse computer inventory
        $rulecollection = new \RuleImportAssetCollection();
        $this->assertTrue(
            $rulecollection->moveRule(
                $rules_id_refuse,
                $rules_id_torefuse,
                \RuleCollection::MOVE_BEFORE
            )
        );

        //do inventory
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));
        $inventory = $this->doInventory($json);

        //move rule back to accept computer inventory
        $this->assertTrue(
            $rulecollection->moveRule(
                $rules_id_refuse,
                $rules_id_toaccept,
                \RuleCollection::MOVE_AFTER
            )
        );

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->assertCount(7, $metadata);
        $this->assertSame('glpixps-2018-07-09-09-07-13', $metadata['deviceid']);
        $this->assertSame('FusionInventory-Agent_v2.5.2-1.fc31', $metadata['version']);
        $this->assertSame('Computer', $metadata['itemtype']);
        $this->assertSame('000005', $metadata['tag']);
        $this->assertNull($metadata['port']);
        $this->assertSame('inventory', $metadata['action']);
        $this->assertCount(10, $metadata['provider']);

        global $DB;
        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();
        $this->assertSame('glpixps-2018-07-09-09-07-13', $agent['deviceid']);
        $this->assertSame('glpixps-2018-07-09-09-07-13', $agent['name']);
        $this->assertSame('2.5.2-1.fc31', $agent['version']);
        $this->assertSame('Computer', $agent['itemtype']);
        $this->assertSame('000005', $agent['tag']);
        $this->assertSame($agenttype['id'], $agent['agenttypes_id']);

        $computers_id = $agent['items_id'];
        $this->assertSame(0, $computers_id);

        $iterator = $DB->request([
            'FROM'   => \RefusedEquipment::getTable(),
        ]);
        $this->assertCount(1, $iterator);

        $result = $iterator->current();
        $expected = [
            'id' => $result['id'],
            'name' => 'glpixps',
            'itemtype' => 'Computer',
            'entities_id' => 0,
            'ip' => '["192.168.1.142","fe80::b283:4fa3:d3f2:96b1","192.168.1.118","fe80::92a4:26c6:99dd:2d60","192.168.122.1"]',
            'mac' => '["00:e0:4c:68:01:db","44:85:00:2b:90:bc","52:54:00:fa:20:0e","52:54:00:fa:20:0e"]',
            'rules_id' => $result['rules_id'],
            'method' => null,
            'serial' => '640HP72',
            'uuid' => '4c4c4544-0034-3010-8048-b6c04f503732',
            'agents_id' => 0,
            'date_creation' => $result['date_creation'],
            'date_mod' => $result['date_mod'],
            'autoupdatesystems_id' => $result['autoupdatesystems_id']
        ];

        $this->assertIsArray($result);
        $this->assertEquals($expected, $result);

        //check no matchedlogs
        $mlogs = new \RuleMatchedLog();
        $found = $mlogs->find();
        $this->assertCount(0, $found);

        //test inventory from refused equipment, will be accepted since rules has been reset ;)
        $refused = new \RefusedEquipment();
        $this->assertTrue($refused->getFromDB($result['id']));

        $inventory_request = new \Glpi\Inventory\Request();
        $inventory_request->handleContentType('application/json');
        $contents = file_get_contents($refused->getInventoryFileName());
        $inventory_request->handleRequest($contents);

        $redirect_url = $refused->handleInventoryRequest($inventory_request);
        $this->hasSessionMessages(
            INFO,
            [
                'Inventory is successful, refused entry log has been removed.'
            ]
        );

        //refused equipment has been removed
        $iterator = $DB->request([
            'FROM'   => \RefusedEquipment::getTable(),
        ]);
        $this->assertCount(0, $iterator);

        //but a linked computer
        $gagent = new \Agent();
        $this->assertTrue($gagent->getFromDB($agent['id']));

        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($gagent->fields['items_id']));
        $this->assertSame('glpixps', $computer->fields['name']);

        //check no matchedlogs
        $mlogs = new \RuleMatchedLog();
        $found = $mlogs->find();
        $this->assertCount(3, $found);

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

        $monitor_criteria = $criteria;
        $monitor_criteria['WHERE'] = ['itemtype' => \Monitor::getType()];
        $iterator = $DB->request($monitor_criteria);
        $this->assertCount(1, $iterator);
        $this->assertSame('Monitor import (by serial)', $iterator->current()['name']);
        $this->assertSame(\Glpi\Inventory\Request::INVENT_QUERY, $iterator->current()['method']);

        $printer_criteria = $criteria;
        $printer_criteria['WHERE'] = ['itemtype' => \Printer::getType()];
        $iterator = $DB->request($printer_criteria);
        $this->assertCount(1, $iterator);
        $this->assertSame('Printer import (by serial)', $iterator->current()['name']);
        $this->assertSame(\Glpi\Inventory\Request::INVENT_QUERY, $iterator->current()['method']);

        $computer_criteria = $criteria;
        $computer_criteria['WHERE'] = ['itemtype' => \Computer::getType()];
        $iterator = $DB->request($computer_criteria);
        $this->assertCount(1, $iterator);
        $this->assertSame('Computer import (by serial + uuid)', $iterator->current()['name']);
        $this->assertSame($gagent->fields['items_id'], $iterator->current()['items_id']);
        $this->assertSame(\Glpi\Inventory\Request::INVENT_QUERY, $iterator->current()['method']);
    }

    public function testImportRefusedFromEntitiesRules()
    {
        $this->login();

        $rule = new \Rule();
        $input = [
            'is_active' => 1,
            'name'      => 'entity refuse rule',
            'match'     => 'AND',
            'sub_type'  => \RuleImportEntity::class,
            'ranking'   => 1
        ];
        $rules_id = $rule->add($input);
        $this->assertGreaterThan(0, $rules_id);

        // Add criteria
        $rulecriteria = new \RuleCriteria();
        $this->assertGreaterThan(
            0,
            $rulecriteria->add([
                'rules_id'  => $rules_id,
                'criteria'  => "deviceid",
                'pattern'   => "/^glpixps.*/",
                'condition' => \RuleImportEntity::REGEX_MATCH
            ])
        );

        // Add action
        $ruleaction = new \RuleAction();
        $this->assertGreaterThan(
            0,
            $ruleaction->add([
                'rules_id'    => $rules_id,
                'action_type' => 'assign',
                'field'       => '_ignore_import',
                'value'       => 1
            ])
        );

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));
        unset($json->content->bios);
        unset($json->content->hardware->name);
        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->assertCount(7, $metadata);
        $this->assertSame('glpixps-2018-07-09-09-07-13', $metadata['deviceid']);
        $this->assertSame('FusionInventory-Agent_v2.5.2-1.fc31', $metadata['version']);
        $this->assertSame('Computer', $metadata['itemtype']);
        $this->assertSame('000005', $metadata['tag']);
        $this->assertNull($metadata['port']);
        $this->assertSame('inventory', $metadata['action']);
        $this->assertCount(10, $metadata['provider']);

        global $DB;
        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();
        $this->assertSame('glpixps-2018-07-09-09-07-13', $agent['deviceid']);
        $this->assertSame('glpixps-2018-07-09-09-07-13', $agent['name']);
        $this->assertSame('2.5.2-1.fc31', $agent['version']);
        $this->assertSame('Computer', $agent['itemtype']);
        $this->assertSame('000005', $agent['tag']);
        $this->assertSame($agenttype['id'], $agent['agenttypes_id']);

        $computers_id = $agent['items_id'];
        $this->assertSame(0, $computers_id);

        $iterator = $DB->request([
            'FROM'   => \RefusedEquipment::getTable(),
        ]);
        $this->assertCount(1, $iterator);

        $result = $iterator->current();
        $expected = [
            'id' => $result['id'],
            'name' => '',
            'itemtype' => 'Computer',
            'entities_id' => 0,
            'ip' => '["192.168.1.142","fe80::b283:4fa3:d3f2:96b1","192.168.1.118","fe80::92a4:26c6:99dd:2d60","192.168.122.1"]',
            'mac' => '["00:e0:4c:68:01:db","44:85:00:2b:90:bc","52:54:00:fa:20:0e","52:54:00:fa:20:0e"]',
            'rules_id' => $result['rules_id'],
            'method' => null,
            'serial' => '',
            'uuid' => '4c4c4544-0034-3010-8048-b6c04f503732',
            'agents_id' => 0,
            'date_creation' => $result['date_creation'],
            'date_mod' => $result['date_mod'],
            'autoupdatesystems_id' => $result['autoupdatesystems_id']
        ];

        $this->assertIsArray($result);
        $this->assertEquals($expected, $result);
    }

    public function testImportFiles()
    {
        $nbcomputers = countElementsInTable(\Computer::getTable());
        $nbprinters = countElementsInTable(\Printer::getTable());

        $json_name = 'computer_1.json';
        $json_path = self::INV_FIXTURES . $json_name;
        $conf = new \Glpi\Inventory\Conf();
        $result = $conf->importFiles([$json_name => $json_path]);

        $this->assertIsArray($result[$json_name]);
        $this->assertTrue($result[$json_name]['success']);
        $this->assertInstanceOf('Computer', $result[$json_name]['items'][0]);

        //1 computer and 1 printer has been inventoried
        $nbcomputers++;
        $nbprinters++;

        $this->assertSame(countElementsInTable(\Computer::getTable()), $nbcomputers);
        $this->assertSame(countElementsInTable(\Printer::getTable()), $nbprinters);
    }

    /**
     * @extensions zip
     */
    public function testArchive()
    {
        $nbcomputers = countElementsInTable(\Computer::getTable());
        $nbprinters = countElementsInTable(\Printer::getTable());
        $nbnetequps = countElementsInTable(\NetworkEquipment::getTable());

        $json_paths = [
            realpath(self::INV_FIXTURES . 'computer_1.json'),
            realpath(self::INV_FIXTURES . 'networkequipment_1.json'),
            realpath(self::INV_FIXTURES . 'printer_1.json'),
        ];

        UnifiedArchive::create($json_paths, self::INVENTORY_ARCHIVE_PATH);

        $conf = new \Glpi\Inventory\Conf();
        $result = $conf->importFiles(['to_inventory.zip' => self::INVENTORY_ARCHIVE_PATH]);

        $this->assertCount(3, $result);

        // Expected result for computer_1.json
        $this->assertTrue($result['to_inventory.zip/computer_1.json']['success']);
        $this->assertInstanceOf('Computer', $result['to_inventory.zip/computer_1.json']['items'][0]);

        // Expected result for networkequipment_1.json
        $this->assertTrue($result['to_inventory.zip/networkequipment_1.json']['success']);
        $this->assertInstanceOf('NetworkEquipment', $result['to_inventory.zip/networkequipment_1.json']['items'][0]);

        // Expected result for printer_1.json
        $this->assertTrue($result['to_inventory.zip/printer_1.json']['success']);
        $this->assertInstanceOf('Printer', $result['to_inventory.zip/printer_1.json']['items'][0]);

        //1 computer 2 printers and a network equipment has been inventoried
        $nbcomputers++;
        $nbprinters += 2;
        $nbnetequps++;

        $this->assertSame(countElementsInTable(\Computer::getTable()), $nbcomputers);
        $this->assertSame(countElementsInTable(\Printer::getTable()), $nbprinters);
        $this->assertSame(countElementsInTable(\NetworkEquipment::getTable()), $nbnetequps);
    }

    public function testImportVirtualMachines()
    {
        global $DB;

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_2.json'));

        $count_vms = count($json->content->virtualmachines);
        $this->assertSame(6, $count_vms);

        $nb_vms = countElementsInTable(\ComputerVirtualMachine::getTable());
        $nb_computers = countElementsInTable(\Computer::getTable());
        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->assertCount(5, $metadata);
        $this->assertSame('acomputer-2021-01-26-14-32-36', $metadata['deviceid']);
        $this->assertSame('Computer', $metadata['itemtype']);
        $this->assertNull($metadata['port']);
        $this->assertSame('inventory', $metadata['action']);

        //check we add only one computer
        ++$nb_computers;
        $this->assertSame($nb_computers, countElementsInTable(\Computer::getTable()));
        //check created vms
        $nb_vms += $count_vms;
        $this->assertSame($nb_vms, countElementsInTable(\ComputerVirtualMachine::getTable()));

        //change config to import vms as computers
        $this->login();
        $conf = new \Glpi\Inventory\Conf();
        $this->assertTrue($conf->saveConf(['vm_as_computer' => 1]));
        $this->logout();

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_2.json'));
        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->assertCount(5, $metadata);
        $this->assertSame('acomputer-2021-01-26-14-32-36', $metadata['deviceid']);
        $this->assertSame('Computer', $metadata['itemtype']);
        $this->assertNull($metadata['port']);
        $this->assertSame('inventory', $metadata['action']);

        //check we add main computer and one computer per vm
        //one does not have an uuid, so no computer is created.
        $this->assertSame($nb_computers + $count_vms - 1, countElementsInTable(\Computer::getTable()));
        //check created vms
        $this->assertSame($nb_vms, countElementsInTable(\ComputerVirtualMachine::getTable()));

        //partial inventory: postgres vm has been stopped
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_2_partial_vms.json'));
        $this->doInventory($json);

        //check nothing has changed
        $this->assertSame($nb_computers + $count_vms - 1, countElementsInTable(\Computer::getTable()));
        $this->assertSame($nb_vms, countElementsInTable(\ComputerVirtualMachine::getTable()));

        $iterator = $DB->request([
            'SELECT' => [
                \ComputerVirtualMachine::getTable() . '.id',
                \ComputerVirtualMachine::getTable() . '.name AS vm_name',
                \VirtualMachineState::getTable() . '.name AS state_name',
            ],
            'FROM' => \ComputerVirtualMachine::getTable(),
            'INNER JOIN' => [
                \VirtualMachineState::getTable() => [
                    'ON' => [
                        \VirtualMachineState::getTable() => 'id',
                        \ComputerVirtualMachine::getTable() => 'virtualmachinestates_id'
                    ]
                ]
            ],
            'WHERE' => [
                \ComputerVirtualMachine::getTable() . '.name' => 'db',
                \VirtualMachineState::getTable() . '.name' => 'off'
            ]
        ]);
        $this->assertCount(1, $iterator);
    }

    public function testUpdateVirtualMachines()
    {
        global $DB;

        $json = json_decode(file_get_contents(FIXTURE_DIR . '/inventories/lxc-server-1.json'));

        $count_vms = count($json->content->virtualmachines);
        $this->assertSame(1, $count_vms);

        $nb_vms = countElementsInTable(\ComputerVirtualMachine::getTable());
        $nb_computers = countElementsInTable(\Computer::getTable());
        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->assertCount(5, $metadata);
        $this->assertSame('lxc-server-2022-08-09-17-49-51', $metadata['deviceid']);
        $this->assertSame('Computer', $metadata['itemtype']);
        $this->assertNull($metadata['port']);
        $this->assertSame('inventory', $metadata['action']);

        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();
        $this->assertSame('lxc-server-2022-08-09-17-49-51', $agent['deviceid']);
        $this->assertSame('lxc-server-2022-08-09-17-49-51', $agent['name']);
        $this->assertSame('Computer', $agent['itemtype']);
        $this->assertSame($agenttype['id'], $agent['agenttypes_id']);
        $computers_id = $agent['items_id'];

        //check we add only one computer
        ++$nb_computers;
        $this->assertSame($nb_computers, countElementsInTable(\Computer::getTable()));
        //check created vms
        $nb_vms += $count_vms;
        $this->assertSame($nb_vms, countElementsInTable(\ComputerVirtualMachine::getTable()));

        $cvms = new \ComputerVirtualMachine();
        $this->assertTrue($cvms->getFromDBByCrit(['computers_id' => $computers_id]));

        $this->assertSame('glpi-10-rc1', $cvms->fields['name']);
        $this->assertSame(2, $cvms->fields['vcpu']);
        $this->assertSame(2048, $cvms->fields['ram']);
        $this->assertSame('487dfdb542a4bfb23670b8d4e76d8b6886c2ed35', $cvms->fields['uuid']);

        //import again, RAM has changed
        $json = json_decode(file_get_contents(FIXTURE_DIR . '/inventories/lxc-server-1.json'));
        $json_vm = $json->content->virtualmachines[0];
        $json_vm->memory = 4096;
        $json_vms = [$json_vm];
        $json->content->virtualmachines = $json_vms;

        $this->doInventory($json);

        $this->assertTrue($cvms->getFromDBByCrit(['computers_id' => $computers_id]));

        $this->assertSame('glpi-10-rc1', $cvms->fields['name']);
        $this->assertSame(2, $cvms->fields['vcpu']);
        $this->assertSame(4096, $cvms->fields['ram']);
        $this->assertSame('487dfdb542a4bfb23670b8d4e76d8b6886c2ed35', $cvms->fields['uuid']);
    }

    public function testRuleRefuseImportVirtualMachines()
    {
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_2.json'));

        $count_vms = count($json->content->virtualmachines);
        $this->assertSame(6, $count_vms);

        $nb_vms = countElementsInTable(\ComputerVirtualMachine::getTable());
        $nb_computers = countElementsInTable(\Computer::getTable());

        //change config to import vms as computers
        $this->login();
        $conf = new \Glpi\Inventory\Conf();
        $this->assertTrue($conf->saveConf(['vm_as_computer' => 1]));
        $this->logout();

        //IMPORT rule to refuse "db" virtual machine
        $criteria = [
            [
                'condition' => 0,
                'criteria'  => 'itemtype',
                'pattern'   => 'Computer',
            ], [
                'condition' => \RuleImportAsset::PATTERN_IS,
                'criteria'  => 'name',
                'pattern'   => 'db'
            ]
        ];
        $action = [
            'action_type' => 'assign',
            'field'       => '_ignore_import',
            'value'       => \RuleImportAsset::RULE_ACTION_LINK_OR_NO_IMPORT
        ];
        $rule = new \RuleImportAsset();
        $collection = new \RuleImportAssetCollection();
        $rulecriteria = new \RuleCriteria();

        $input = [
            'is_active' => 1,
            'name'      => 'Refuse one VM creation',
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

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_2.json'));
        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->assertCount(5, $metadata);
        $this->assertSame('acomputer-2021-01-26-14-32-36', $metadata['deviceid']);
        $this->assertSame('Computer', $metadata['itemtype']);
        $this->assertNull($metadata['port']);
        $this->assertSame('inventory', $metadata['action']);

        //check created vms
        $this->assertSame($count_vms, countElementsInTable(\ComputerVirtualMachine::getTable()));

        //check we add main computer and one computer per vm
        //one does not have an uuid, so no computer is created.
        ++$nb_computers;
        $this->assertSame($nb_computers + $count_vms - 2, countElementsInTable(\Computer::getTable()));
    }

    public function testImportDatabases()
    {
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_2.json'));

        $nb_computers = countElementsInTable(\Computer::getTable());
        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->assertCount(5, $metadata);
        $this->assertSame('acomputer-2021-01-26-14-32-36', $metadata['deviceid']);
        $this->assertSame('Computer', $metadata['itemtype']);
        $this->assertNull($metadata['port']);
        $this->assertSame('inventory', $metadata['action']);

        //partial inventory: add databases

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

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_2_partial_dbs.json'));
        $this->doInventory($json);

        //check nothing has changed
        $this->assertSame($nb_computers + 1, countElementsInTable(\Computer::getTable()));

        //check created databases & instances
        $this->assertSame(2, countElementsInTable(\DatabaseInstance::getTable()));
        $this->assertSame(2, countElementsInTable(\DatabaseInstance::getTable(), ['is_dynamic' => 1]));
        $this->assertSame(3, countElementsInTable(\Database::getTable()));

        //play an update - nothing should have changed
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_2_partial_dbs.json'));
        $this->doInventory($json);

        //check nothing has changed
        $this->assertSame($nb_computers + 1, countElementsInTable(\Computer::getTable()));

        //check created databases & instances
        $this->assertSame(2, countElementsInTable(\DatabaseInstance::getTable()));
        $this->assertSame(3, countElementsInTable(\Database::getTable()));

        //keep only mysql
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_2_partial_dbs.json'));
        $mysql = $json->content->databases_services[0];
        //update version
        $mysql->version = 'Ver 15.1 Distrib 10.5.10-MariaDB-modified';
        $dbs = $mysql->databases;

        $db_glpi = &$dbs[0];
        $db_glpi->size = 55000;
        $db_glpi->last_backup_date = '2021-06-25 08:52:44';

        $db_new = &$dbs[1];
        $db_new->name = 'new_database';
        $db_new->size = 2048;

        $services = [$mysql];
        $json->content->databases_services = $services;

        $this->doInventory($json);

        //check created databases & instances
        $this->assertSame(1, countElementsInTable(\DatabaseInstance::getTable(), ['is_deleted' => 0]));
        $this->assertSame(1, countElementsInTable(\DatabaseInstance::getTable(), ['is_deleted' => 1]));

        //ensure database version has been updated
        $database = new \DatabaseInstance();
        $this->assertTrue($database->getFromDBByCrit(['name' => 'MariaDB']));
        $this->assertSame('Ver 15.1 Distrib 10.5.10-MariaDB-modified', $database->fields['version']);

        //- ensure existing instances has been updated
        $databases = $database->getDatabases();
        $this->assertCount(2, $databases);

        $database = array_pop($databases);
        $this->assertIsArray($database);
        $this->assertSame('new_database', $database['name']);
        $this->assertSame(2048, $database['size']);
        $database = array_pop($databases);
        $this->assertIsArray($database);
        $this->assertSame('glpi', $database['name']);
        $this->assertSame(55000, $database['size']);

        //test sql syntax error
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_2_partial_dbs.json'));
        $mysql = $json->content->databases_services[0];
        //update version
        $mysql->name = "Maria'DB";
        $dbs = $mysql->databases;

        $db_glpi = &$dbs[0];
        $db_glpi->size = 55000;
        $db_glpi->last_backup_date = '2021-06-25 08:52:44';

        $db_new = &$dbs[1];
        $db_new->name = 'new_database';
        $db_new->size = 2048;

        $services = [$mysql];
        $json->content->databases_services = $services;

        $this->doInventory($json);

        //check created databases & instances
        $this->assertSame(1, countElementsInTable(\DatabaseInstance::getTable(), ['is_deleted' => 0]));
        $this->assertSame(2, countElementsInTable(\DatabaseInstance::getTable(), ['is_deleted' => 1]));

        //ensure database version has been updated
        $database = new \DatabaseInstance();
        $this->assertTrue($database->getFromDBByCrit(['name' => 'MariaDB']));
        $this->assertSame('Ver 15.1 Distrib 10.5.10-MariaDB-modified', $database->fields['version']);

        //- ensure existing instances has been updated
        $databases = $database->getDatabases();
        $this->assertCount(2, $databases);
        $database = array_pop($databases);
        $this->assertIsArray($database);
        $this->assertSame('new_database', $database['name']);
        $this->assertSame(2048, $database['size']);
        $database = array_pop($databases);
        $this->assertIsArray($database);
        $this->assertSame('glpi', $database['name']);
        $this->assertSame(55000, $database['size']);

        $computer = new \Computer();
        global $DB;
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();
        $computers_id = $agent['items_id'];
        $this->assertTrue($computer->getFromDB($computers_id));
        $this->assertTrue($computer->delete(['id' => $computers_id], true));
    }


    public function testImportPhoneSimCardNoReset()
    {
        global $DB;

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SIMCARDS>
        <COUNTRY>fr</COUNTRY>
        <OPERATOR_CODE>2081</OPERATOR_CODE>
        <OPERATOR_NAME>Orange F</OPERATOR_NAME>
        <SERIAL>89330126162002971850</SERIAL>
        <STATE>SIM_STATE_READY</STATE>
        <LINE_NUMBER></LINE_NUMBER>
        <SUBSCRIBER_ID>1</SUBSCRIBER_ID>
    </SIMCARDS>
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
  <ITEMTYPE>Phone</ITEMTYPE>
</REQUEST>";

        $this->doInventory($xml, true);
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();


        //check created computer
        $phone = new \Phone();
        $this->assertTrue($phone->getFromDB($agent['items_id']));

        //check for components
        $item_devicesimcard = new \Item_DeviceSimcard();
        $simcards_first = $item_devicesimcard->find(['itemtype' => 'Phone' , 'items_id' => $agent['items_id']]);
        $this->assertCount(1, $simcards_first);

        //re run inventory to check if item_simcard ID is changed
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'phone_1.json'));

        $this->doInventory($json);
        $item_devicesimcard = new \Item_DeviceSimcard();
        $simcards_second = $item_devicesimcard->find(['itemtype' => 'Phone' , 'items_id' => $agent['items_id']]);
        $this->assertCount(1, $simcards_second);

        $this->assertIsArray($simcards_first);
        $this->assertSame($simcards_second, $simcards_first);
    }

    public function testImportPhoneMultiSimCardNoReset()
    {
        global $DB;

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SIMCARDS>
        <COUNTRY>fr</COUNTRY>
        <OPERATOR_CODE>2081</OPERATOR_CODE>
        <OPERATOR_NAME>Orange F</OPERATOR_NAME>
        <SERIAL>89330126162002971850</SERIAL>
        <STATE>SIM_STATE_READY</STATE>
        <LINE_NUMBER></LINE_NUMBER>
        <SUBSCRIBER_ID>1</SUBSCRIBER_ID>
    </SIMCARDS>
    <SIMCARDS>
        <COUNTRY>fr</COUNTRY>
        <OPERATOR_CODE>2081</OPERATOR_CODE>
        <OPERATOR_NAME>Orange F</OPERATOR_NAME>
        <SERIAL>23168441316812316511</SERIAL>
        <STATE>SIM_STATE_READY</STATE>
        <LINE_NUMBER></LINE_NUMBER>
        <SUBSCRIBER_ID>2</SUBSCRIBER_ID>
    </SIMCARDS>
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
  <ITEMTYPE>Phone</ITEMTYPE>
</REQUEST>";

        $this->doInventory($xml, true);
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();


        //check created computer
        $phone = new \Phone();
        $this->assertTrue($phone->getFromDB($agent['items_id']));

        //check for components
        $item_devicesimcard = new \Item_DeviceSimcard();
        $simcards_first = $item_devicesimcard->find(['itemtype' => 'Phone' , 'items_id' => $agent['items_id']]);
        $this->assertCount(2, $simcards_first);

        //re run inventory to check if item_simcard ID is changed
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'phone_1.json'));

        $this->doInventory($json);
        $item_devicesimcard = new \Item_DeviceSimcard();
        $simcards_second = $item_devicesimcard->find(['itemtype' => 'Phone' , 'items_id' => $agent['items_id']]);
        $this->assertCount(2, $simcards_second);

        $this->assertIsArray($simcards_first);
        $this->assertSame($simcards_second, $simcards_first);
    }


    public function testImportPhone()
    {
        global $DB;

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'phone_1.json'));

        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->assertCount(5, $metadata);
        $this->assertSame('Mi9TPro-TéléphoneM-2019-12-18-14-30-16', $metadata['deviceid']);
        $this->assertSame('example-app-java', $metadata['version']);
        $this->assertSame('Phone', $metadata['itemtype']);
        $this->assertNull($metadata['port']);
        $this->assertSame('inventory', $metadata['action']);

        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();
        $this->assertIsArray($agent);
        $this->assertSame('Mi9TPro-TéléphoneM-2019-12-18-14-30-16', $agent['deviceid']);
        $this->assertSame('Mi9TPro-TéléphoneM-2019-12-18-14-30-16', $agent['name']);
        $this->assertSame('Phone', $agent['itemtype']);
        $this->assertSame($agenttype['id'], $agent['agenttypes_id']);
        $this->assertGreaterThan(0, $agent['items_id']);

        //check matchedlogs
        $mlogs = new \RuleMatchedLog();
        $found = $mlogs->find();
        $this->assertCount(1, $found);

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
            'WHERE' => ['itemtype' => \Phone::getType()]
        ];

        $iterator = $DB->request($criteria);
        $this->assertCount(1, $iterator);
        $this->assertSame('Phone import (by serial + uuid)', $iterator->current()['name']);
        $this->assertSame(\Glpi\Inventory\Request::INVENT_QUERY, $iterator->current()['method']);

        //get phone models, manufacturer, ...
        $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->current();
        $this->assertIsArray($autoupdatesystems);
        $autoupdatesystems_id = $autoupdatesystems['id'];

        $cmodels = $DB->request(['FROM' => \PhoneModel::getTable(), 'WHERE' => ['name' => 'Mi 9T Pro']])->current();
        $this->assertIsArray($cmodels);
        $computermodels_id = $cmodels['id'];

        $ctypes = $DB->request(['FROM' => \PhoneType::getTable(), 'WHERE' => ['name' => 'Mi 9T Pro']])->current();
        $this->assertIsArray($ctypes);
        $computertypes_id = $ctypes['id'];

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Xiaomi']])->current();
        $this->assertIsArray($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        //check created phone
        $phones_id = $inventory->getAgent()->fields['items_id'];
        $this->assertGreaterThan(0, $phones_id);
        $phone = new \Phone();
        $this->assertTrue($phone->getFromDB($phones_id));

        $expected = [
            'id' => $phones_id,
            'entities_id' => 0,
            'name' => 'Mi9TPro-TéléphoneM',
            'date_mod' => $phone->fields['date_mod'],
            'contact' => 'builder',
            'contact_num' => null,
            'users_id_tech' => 0,
            'groups_id_tech' => 0,
            'comment' => null,
            'serial' => 'af8d8fcfa6fa4794',
            'otherserial' => 'release-keys',
            'locations_id' => 0,
            'phonetypes_id' => $computertypes_id,
            'phonemodels_id' => $computermodels_id,
            'brand' => null,
            'phonepowersupplies_id' => 0,
            'number_line' => null,
            'have_headset' => 0,
            'have_hp' => 0,
            'manufacturers_id' => $manufacturers_id,
            'is_global' => 0,
            'is_deleted' => 0,
            'is_template' => 0,
            'template_name' => null,
            'users_id' => 0,
            'groups_id' => 0,
            'states_id' => 0,
            'ticket_tco' => '0.0000',
            'is_dynamic' => 1,
            'autoupdatesystems_id' => $autoupdatesystems_id,
            'uuid' => 'af8d3fcfa6fe4784',
            'date_creation' => $phone->fields['date_creation'],
            'is_recursive' => 0,
            'last_inventory_update' => $phone->fields['last_inventory_update'],
        ];
        $this->assertIsArray($phone->fields);
        $this->assertSame($expected, $phone->fields);

        //operating system
        $ios = new \Item_OperatingSystem();
        $iterator = $ios->getFromItem($phone);
        $record = $iterator->current();

        $expected = [
            'assocID' => $record['assocID'],
            'name' => 'Q Android 10.0 api 29',
            'version' => '29',
            'architecture' => 'arm64-v8a,armeabi-v7a,armeabi',
            'servicepack' => null,
        ];
        $this->assertIsArray($record);
        $this->assertSame($expected, $record);

        //remote management
        $mgmt = new \Item_RemoteManagement();
        $iterator = $mgmt->getFromItem($phone);
        $this->assertCount(0, $iterator);

        //volumes
        $idisks = new \Item_Disk();
        $iterator = $idisks->getFromItem($phone);
        $this->assertCount(4, $iterator);

        $expecteds = [
            [
                'totalsize' => 3471,
                'freesize' => 23,
            ], [
                'totalsize' => 51913,
                'freesize' => 33722,
            ], [
                'totalsize' => 51913,
                'freesize' => 33722,
            ], [
                'totalsize' => 51913,
                'freesize' => 33722,
            ]
        ];

        $i = 0;
        foreach ($iterator as $volume) {
            unset($volume['id']);
            unset($volume['date_mod']);
            unset($volume['date_creation']);
            $expected = $expecteds[$i];
            $expected = $expected + [
                'fsname'       => null,
                'name'         => null,
                'device'       => null,
                'mountpoint'   => null,
                'filesystems_id' => 0,
                'encryption_status' => 0,
                'encryption_tool' => null,
                'encryption_algorithm' => null,
                'encryption_type' => null,
                'items_id'     => $phones_id,
                'itemtype'     => 'Phone',
                'entities_id'  => 0,
                'is_deleted'   => 0,
                'is_dynamic'   => 1
            ];
            ksort($volume);
            ksort($expected);
            $this->assertIsArray($volume);
            $this->assertEquals($expected, $volume);
            ++$i;
        }

        //check network ports
        $iterator = $DB->request([
            'FROM'   => \NetworkPort::getTable(),
            'WHERE'  => [
                'items_id'           => $phones_id,
                'itemtype'           => 'Phone',
            ],
        ]);
        $this->assertCount(1, $iterator);

        $expecteds = [
            [
                'logical_number' => 1,
                'name' => 'No description found',
                'instantiation_type' => 'NetworkPortWifi',
                'mac' => 'e0:dc:ff:ed:09:59',
                'ifstatus' => '1',
                'ifinternalstatus' => '1',
            ]
        ];

        $ips = [
            'No description found'  => [
                'v4'   => '172.28.214.132',
            ]
        ];

        $i = 0;
        $netport = new \NetworkPort();
        foreach ($iterator as $port) {
            $ports_id = $port['id'];
            $this->assertTrue($netport->getFromDB($ports_id));
            $instantiation = $netport->getInstantiation();
            if ($port['instantiation_type'] === null) {
                $this->assertFalse($instantiation);
            } else {
                $this->assertInstanceOf($port['instantiation_type'], $instantiation);
            }

            unset($port['id']);
            unset($port['date_creation']);
            unset($port['date_mod']);
            unset($port['comment']);

            $expected = $expecteds[$i];
            $expected = $expected + [
                'items_id' => $phones_id,
                'itemtype' => 'Phone',
                'entities_id' => 0,
                'is_recursive' => 0,
                'is_deleted' => 0,
                'is_dynamic' => 1,
                'ifmtu' => 0,
                'ifspeed' => 0,
                'ifinternalstatus' => null,
                'ifconnectionstatus' => 0,
                'iflastchange' => null,
                'ifinbytes' => 0,
                'ifinerrors' => 0,
                'ifoutbytes' => 0,
                'ifouterrors' => 0,
                'ifstatus' => null,
                'ifdescr' => null,
                'ifalias' => null,
                'portduplex' => null,
                'trunk' => 0,
                'lastup' => null
            ];

            $this->assertIsArray($port);
            $this->assertEquals($expected, $port);
            ++$i;

            //check for ips
            $ip_iterator = $DB->request([
                'SELECT'       => [
                    \IPAddress::getTable() . '.name',
                    \IPAddress::getTable() . '.version'
                ],
                'FROM'   => \IPAddress::getTable(),
                'INNER JOIN'   => [
                    \NetworkName::getTable()   => [
                        'ON'  => [
                            \IPAddress::getTable()     => 'items_id',
                            \NetworkName::getTable()   => 'id', [
                                'AND' => [\IPAddress::getTable() . '.itemtype'  => \NetworkName::getType()]
                            ]
                        ]
                    ]
                ],
                'WHERE'  => [
                    \NetworkName::getTable() . '.itemtype'  => \NetworkPort::getType(),
                    \NetworkName::getTable() . '.items_id'  => $ports_id
                ]
            ]);

            $this->assertCount(count($ips[$port['name']] ?? []), $ip_iterator);
            if (isset($ips[$port['name']])) {
                //FIXME: missing all ipv6 :(
                $ip = $ip_iterator->current();
                $this->assertSame(4, (int)$ip['version']);
                $this->assertSame($ips[$port['name']]['v4'], $ip['name']);
            }
        }

        //check for components
        $components = [];
        $allcount = 0;
        foreach (\Item_Devices::getItemAffinities('Computer') as $link_type) {
            $link        = getItemForItemtype($link_type);
            $iterator = $DB->request($link->getTableGroupCriteria($phone));
            $allcount += count($iterator);
            $components[$link_type] = [];

            foreach ($iterator as $row) {
                $lid = $row['id'];
                unset($row['id']);
                $components[$link_type][$lid] = $row;
            }
        }

        $expecteds = [
            'Item_DeviceMotherboard' => 0,
            'Item_DeviceFirmware' => 1,
            'Item_DeviceProcessor' => 1,
            'Item_DeviceMemory' => 1,
            'Item_DeviceHardDrive' => 0,
            'Item_DeviceNetworkCard' => 1,
            'Item_DeviceDrive' => 0,
            'Item_DeviceBattery' => 1,
            'Item_DeviceGraphicCard' => 0,
            'Item_DeviceSoundCard' => 0,
            'Item_DeviceControl' => 0,
            'Item_DevicePci' => 0,
            'Item_DeviceCase' => 0,
            'Item_DevicePowerSupply' => 0,
            'Item_DeviceGeneric' => 0,
            'Item_DeviceSimcard' => 1,
            'Item_DeviceSensor' => 48,
            'Item_DeviceCamera' => 2
        ];

        foreach ($expecteds as $type => $count) {
            $this->assertCount($count, $components[$type], count($components[$type]) . ' ' . $type);
        }

        $expecteds = [
            'Item_DeviceMotherboard' => [],
            'Item_DeviceFirmware' => [
                [
                    'items_id' => $phones_id,
                    'itemtype' => 'Phone',
                    'devicefirmwares_id' => 104,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ]
            ],
            'Item_DeviceProcessor' => [
                [
                    'items_id' => $phones_id,
                    'itemtype' => 'Phone',
                    'deviceprocessors_id' => 3060400,
                    'frequency' => 1785,
                    'serial' => null,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'nbcores' => 8,
                    'nbthreads' => 8,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ],
            ],
            'Item_DeviceMemory' => [
                [
                    'items_id' => $phones_id,
                    'itemtype' => 'Phone',
                    'devicememories_id' => 4,
                    'size' => 5523,
                    'serial' => null,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0
                ],
            ],
            'Item_DeviceHardDrive' => [],
            'Item_DeviceNetworkCard' => [
                [
                    'items_id' => $phones_id,
                    'itemtype' => 'Phone',
                    'devicenetworkcards_id' => 66,
                    'mac' => 'e0:dc:ff:ed:09:59',
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'busID' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ]
            ],
            'Item_DeviceDrive' => [],
            'Item_DeviceBattery' => [
                [
                    'items_id' => $phones_id,
                    'itemtype' => 'Phone',
                    'devicebatteries_id' => 70,
                    'manufacturing_date' => null,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                    'real_capacity' => 0
                ],
            ],
            'Item_DeviceGraphicCard' => [],
            'Item_DeviceSoundCard' => [],
            'Item_DeviceControl' => [],
            'Item_DevicePci' => [],
            'Item_DeviceCase' => [],
            'Item_DevicePowerSupply' => [],
            'Item_DeviceGeneric' => [],
            'Item_DeviceSimcard' => [
                [
                    'items_id' => $phones_id,
                    'itemtype' => 'Phone',
                    'devicesimcards_id' => 68,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => '8933150319050352521',
                    'otherserial' => null,
                    'states_id' => 0,
                    'locations_id' => 0,
                    'lines_id' => 0,
                    'users_id' => 0,
                    'groups_id' => 0,
                    'pin' => '',
                    'pin2' => '',
                    'puk' => '',
                    'puk2' => '',
                    'msin' => '',
                    'comment' => null,
                ]
            ],
            'Item_DeviceCamera' => [
                [
                    'items_id' => $phones_id,
                    'itemtype' => 'Phone',
                    'devicecameras_id' => 4,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'locations_id' => 0,
                ], [
                    'items_id' => $phones_id,
                    'itemtype' => 'Phone',
                    'devicecameras_id' => 4,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'locations_id' => 0,
                ]
            ]
        ];

        foreach ($expecteds as $type => $expected) {
            $component = array_values($components[$type]);
            //hack to replace expected fkeys
            foreach ($expected as $i => &$row) {
                foreach (array_keys($row) as $key) {
                    if (isForeignKeyField($key)) {
                        $row[$key] = $component[$i][$key];
                    }
                }
            }
            $this->assertIsArray($component);
            $this->assertSame($expected, $component);
        }

        //software
        $isoft = new \Item_SoftwareVersion();
        $iterator = $isoft->getFromItem($phone);
        $this->assertCount(4, $iterator);

        $expecteds = [
            [
                'softname' => 'Boutique Amazon',
                'version' => '18.21.2.100',
                'dateinstall' => '2019-08-31',
            ], [
                'softname' => 'CameraTools',
                'version' => '1.0',
                'dateinstall' => '2008-12-31',
            ], [
                'softname' => 'Enregistreur d\'écran',
                'version' => '1.5.9',
                'dateinstall' => '2008-12-31',
            ], [
                'softname' => 'Q Android 10.0 api 29',
                'version' => '29',
                'dateinstall' => null,
            ]
        ];

        $i = 0;
        foreach ($iterator as $soft) {
            $expected = $expecteds[$i];
            $this->assertEquals(
                $expected,
                [
                    'softname'     => $soft['softname'],
                    'version'      => $soft['version'],
                    'dateinstall'  => $soft['dateinstall']
                ]
            );
            ++$i;
        }
    }

    public function testPartialComputerImport()
    {
        global $DB;

        //initial import
        $this->testImportComputer();

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1_partial_volumes.json'));
        $json->content->hardware->lastloggeduser = 'trasher/root';
        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->assertCount(6, $metadata);
        $this->assertSame('glpixps-2018-07-09-09-07-13', $metadata['deviceid']);
        $this->assertSame('FusionInventory-Agent_v2.5.2-1.fc31', $metadata['version']);
        $this->assertSame('Computer', $metadata['itemtype']);
        $this->assertSame('inventory', $metadata['action']);

        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();
        $this->assertIsArray($agent);
        $this->assertSame('glpixps-2018-07-09-09-07-13', $agent['deviceid']);
        $this->assertSame('glpixps-2018-07-09-09-07-13', $agent['name']);
        $this->assertSame('2.5.2-1.fc31', $agent['version']);
        $this->assertSame('Computer', $agent['itemtype']);
        $this->assertSame($agenttype['id'], $agent['agenttypes_id']);

        $computer = $this->checkComputer1($agent['items_id']);

        //volumes free sizes
        $sizes = [
            11883,
            15924,
            603,
            10740,
            20872,
            191
        ];
        $this->checkComputer1Volumes($computer, $sizes);
        $this->checkComputer1Softwares($computer);
        $this->checkComputer1Batteries($computer);

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1_partial_softs.json'));
        $this->doInventory($json);

        //software versions
        $versions = [
            '2.2.8-1.fc31',
            '31 (Workstation Edition)',
            '0.20.1-3.fc31',
            '3.33.0-1.fc31',
            '3.34.1-1.fc31',
            '3.12.2-18.fc31',
            '1.32-2.fc31'
        ];
        $this->checkComputer1Softwares($computer, $versions);

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1_partial_batteries.json'));
        $this->doInventory($json);

        //software versions
        $capacities = [
            40570,
        ];
        $this->checkComputer1Batteries($computer, $capacities);
    }

    public function testDictionnaryManufacturer()
    {
        global $DB;

        //create manufacturer dictionary entry
        $rule = new \Rule();
        $criteria = new \RuleCriteria();
        $action = new \RuleAction();
        $collection = new \RuleDictionnaryManufacturerCollection();
        $manufacturer = new \Manufacturer();
        //$manufacturers_id = $manufacturer->importExternal('Mozilla');

        $rules_id = $rule->add(['name' => 'Set manufacturer',
            'is_active' => 1,
            'entities_id' => 0,
            'sub_type' => 'RuleDictionnaryManufacturer',
            'match' => \Rule::AND_MATCHING,
            'condition' => 0,
            'description' => ''
        ]);
        $this->assertGreaterThan(0, $rules_id);

        $this->assertGreaterThan(
            0,
            $criteria->add([
                'rules_id' => $rules_id,
                'criteria' => 'name',
                'condition' => \Rule::PATTERN_IS,
                'pattern' => 'Dell Inc.'
            ])
        );

        $this->assertGreaterThan(
            0,
            $action->add([
                'rules_id' => $rules_id,
                'action_type' => 'assign',
                'field' => 'name',
                'value' => 'Dictionary manufacturer'
            ])
        );

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_2.json'));

        $nb_computers = countElementsInTable(\Computer::getTable());
        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->assertCount(5, $metadata);
        $this->assertSame('acomputer-2021-01-26-14-32-36', $metadata['deviceid']);
        $this->assertSame('Computer', $metadata['itemtype']);
        $this->assertNull($metadata['port']);
        $this->assertSame('inventory', $metadata['action']);

        //check we add only one computer
        ++$nb_computers;
        $this->assertSame($nb_computers, countElementsInTable(\Computer::getTable()));

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();
        $this->assertIsArray($agent);
        $this->assertSame('acomputer-2021-01-26-14-32-36', $agent['deviceid']);
        $this->assertSame('acomputer-2021-01-26-14-32-36', $agent['name']);
        $this->assertSame('Computer', $agent['itemtype']);
        $this->assertGreaterThan(0, $agent['items_id']);

        //check created computer
        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($agent['items_id']));
        $manufacturer = new \Manufacturer();
        $this->assertTrue($manufacturer->getFromDB($computer->fields['manufacturers_id']));
        $this->assertSame('Dictionary manufacturer', $manufacturer->fields['name']);
    }

    public function testDictionnaryOperatingSystem()
    {
        global $DB;

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));

        $this->doInventory($json);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();

        //check created computer
        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($agent['items_id']));

        //check OS
        $item_operating = new Item_OperatingSystem();
        $this->assertTrue(
            $item_operating->getFromDBByCrit([
                "itemtype" => 'Computer',
                "items_id" => $agent['items_id'],
            ])
        );

        $operating_system = new OperatingSystem();
        $this->assertTrue(
            $operating_system->getFromDB($item_operating->fields['operatingsystems_id'])
        );

        $this->assertSame("Fedora 31 (Workstation Edition)", $operating_system->fields['name']);

        //create rule dictionnary operating system
        $rule = new \Rule();
        $criteria = new \RuleCriteria();
        $action = new \RuleAction();

        $rules_id = $rule->add(['name' => 'Set specific operatingSystem',
            'is_active' => 1,
            'entities_id' => 0,
            'sub_type' => 'RuleDictionnaryOperatingSystem',
            'match' => \Rule::AND_MATCHING,
            'condition' => 0,
            'description' => ''
        ]);
        $this->assertGreaterThan(0, $rules_id);

        $this->assertGreaterThan(
            0,
            $criteria->add([
                'rules_id' => $rules_id,
                'criteria' => 'name',
                'condition' => \Rule::PATTERN_CONTAIN,
                'pattern' => 'Fedora 31'
            ])
        );

        $this->assertGreaterThan(
            0,
            $action->add([
                'rules_id' => $rules_id,
                'action_type' => 'assign',
                'field' => 'name',
                'value' => 'Ubuntu'
            ])
        );

        //redo an inventory
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));
        $this->doInventory($json);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();

        //check updated computer
        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($agent['items_id']));

        //check OS
        $item_operating = new Item_OperatingSystem();
        $this->assertTrue(
            $item_operating->getFromDBByCrit([
                "itemtype" => 'Computer',
                "items_id" => $agent['items_id'],
            ])
        );

        $operating_system = new OperatingSystem();
        $this->assertTrue(
            $operating_system->getFromDB($item_operating->fields['operatingsystems_id'])
        );

        $this->assertSame("Ubuntu", $operating_system->fields['name']);
    }

    public function testDictionnaryOperatingSystemVersion()
    {
        global $DB;

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));

        $this->doInventory($json);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();

        //check created computer
        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($agent['items_id']));

        //check OS
        $item_operating = new Item_OperatingSystem();
        $this->assertTrue(
            $item_operating->getFromDBByCrit([
                "itemtype" => 'Computer',
                "items_id" => $agent['items_id'],
            ])
        );

        $operating_system_version = new OperatingSystemVersion();
        $this->assertTrue(
            $operating_system_version->getFromDB($item_operating->fields['operatingsystemversions_id'])
        );

        //check if is original value
        $this->assertSame("31 (Workstation Edition)", $operating_system_version->fields['name']);

        //create rule dictionnary operating system
        $rule = new \Rule();
        $criteria = new \RuleCriteria();
        $action = new \RuleAction();

        $rules_id = $rule->add(['name' => 'Set specific operatingSystem version',
            'is_active' => 1,
            'entities_id' => 0,
            'sub_type' => 'RuleDictionnaryOperatingSystemVersion',
            'match' => \Rule::AND_MATCHING,
            'condition' => 0,
            'description' => ''
        ]);
        $this->assertGreaterThan(0, $rules_id);

        $this->assertGreaterThan(
            0,
            $criteria->add([
                'rules_id' => $rules_id,
                'criteria' => 'name',
                'condition' => \Rule::PATTERN_CONTAIN,
                'pattern' => '31 (Workstation Edition)'
            ])
        );

        $this->assertGreaterThan(
            0,
            $action->add([
                'rules_id' => $rules_id,
                'action_type' => 'assign',
                'field' => 'name',
                'value' => 'New version'
            ])
        );

        //redo an inventory
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));
        $this->doInventory($json);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();

        //check updated computer
        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($agent['items_id']));

        //check OS
        $item_operating = new Item_OperatingSystem();
        $this->assertTrue(
            $item_operating->getFromDBByCrit([
                "itemtype" => 'Computer',
                "items_id" => $agent['items_id'],
            ])
        );

        $operating_system_version = new OperatingSystemVersion();
        $this->assertTrue(
            $operating_system_version->getFromDB($item_operating->fields['operatingsystemversions_id'])
        );

        //check if is specific value
        $this->assertSame("New version", $operating_system_version->fields['name']);
    }

    public function testDictionnaryOperatingSystemArchitecture()
    {
        global $DB;

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));

        $this->doInventory($json);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();

        //check created computer
        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($agent['items_id']));

        //check OS
        $item_operating = new Item_OperatingSystem();
        $this->assertTrue(
            $item_operating->getFromDBByCrit([
                "itemtype" => 'Computer',
                "items_id" => $agent['items_id'],
            ])
        );

        $operating_arch = new OperatingSystemArchitecture();
        $this->assertTrue(
            $operating_arch->getFromDB($item_operating->fields['operatingsystemarchitectures_id'])
        );
        //check if is original value
        $this->assertSame("x86_64", $operating_arch->fields['name']);

        //create rule dictionnary operating system
        $rule = new \Rule();
        $criteria = new \RuleCriteria();
        $action = new \RuleAction();

        $rules_id = $rule->add(['name' => 'Set specific operatingSystem arch',
            'is_active' => 1,
            'entities_id' => 0,
            'sub_type' => 'RuleDictionnaryOperatingSystemArchitecture',
            'match' => \Rule::AND_MATCHING,
            'condition' => 0,
            'description' => ''
        ]);
        $this->assertGreaterThan(0, $rules_id);

        $this->assertGreaterThan(
            0,
            $criteria->add([
                'rules_id' => $rules_id,
                'criteria' => 'name',
                'condition' => \Rule::PATTERN_CONTAIN,
                'pattern' => 'x86_64'
            ])
        );

        $this->assertGreaterThan(
            0,
            $action->add([
                'rules_id' => $rules_id,
                'action_type' => 'assign',
                'field' => 'name',
                'value' => 'New arch'
            ])
        );

        //redo an inventory
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));
        $this->doInventory($json);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();

        //check updated computer
        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($agent['items_id']));

        //check OS
        $item_operating = new Item_OperatingSystem();
        $this->assertTrue(
            $item_operating->getFromDBByCrit([
                "itemtype" => 'Computer',
                "items_id" => $agent['items_id'],
            ])
        );

        $operating_arch = new OperatingSystemArchitecture();
        $this->assertTrue(
            $operating_arch->getFromDB($item_operating->fields['operatingsystemarchitectures_id'])
        );

        //check if is specific value
        $this->assertSame("New arch", $operating_arch->fields['name']);
    }

    public function testDictionnaryOperatingSystemServicePack()
    {
        global $DB;

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));

        $this->doInventory($json);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();

        //check created computer
        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($agent['items_id']));

        //check OS
        $item_operating = new Item_OperatingSystem();
        $this->assertTrue(
            $item_operating->getFromDBByCrit([
                "itemtype" => 'Computer',
                "items_id" => $agent['items_id'],
            ])
        );

        //No service pack from linux (normal)
        $this->assertEquals(0, $item_operating->fields['operatingsystemservicepacks_id']);

        //create rule dictionnary operating system
        $rule = new \Rule();
        $criteria = new \RuleCriteria();
        $action = new \RuleAction();

        $rules_id = $rule->add(['name' => 'Set specific operatingSystem service pack',
            'is_active' => 1,
            'entities_id' => 0,
            'sub_type' => 'RuleDictionnaryOperatingSystemServicePack',
            'match' => \Rule::AND_MATCHING,
            'condition' => 0,
            'description' => ''
        ]);
        $this->assertGreaterThan(0, $rules_id);

        //create criteria on os_name
        $this->assertGreaterThan(
            0,
            $criteria->add([
                'rules_id' => $rules_id,
                'criteria' => 'os_name',
                'condition' => \Rule::PATTERN_CONTAIN,
                'pattern' => 'Fedora 31'
            ])
        );

        $this->assertGreaterThan(
            0,
            $action->add([
                'rules_id' => $rules_id,
                'action_type' => 'assign',
                'field' => 'name',
                'value' => 'New service_pack'
            ])
        );

        //redo an inventory
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));
        $this->doInventory($json);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();

        //check updated computer
        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($agent['items_id']));

        //check OS
        $item_operating = new Item_OperatingSystem();
        $this->assertTrue(
            $item_operating->getFromDBByCrit([
                "itemtype" => 'Computer',
                "items_id" => $agent['items_id'],
            ])
        );

        $operating_service_pack = new OperatingSystemServicePack();
        $this->assertTrue(
            $operating_service_pack->getFromDB($item_operating->fields['operatingsystemservicepacks_id'])
        );
        //check if is specific value
        $this->assertSame("New service_pack", $operating_service_pack->fields['name']);
    }

    public function testImportStatusAfterClean()
    {
        global $DB;

        $this->login();

        //create states to use
        $state = new \State();
        $inv_states_id = $state->add([
            'name' => 'Has been inventoried'
        ]);
        $this->assertGreaterThan(0, $inv_states_id);

        $cleaned_states_id = $state->add([
            'name' => 'Has been cleaned'
        ]);
        $this->assertGreaterThan(0, $cleaned_states_id);

        \Config::setConfigurationValues(
            'inventory',
            [
                'states_id_default' => $inv_states_id,
                'stale_agents_delay' => 1,
                'stale_agents_action' => exportArrayToDB([
                    \Glpi\Inventory\Conf::STALE_AGENT_ACTION_STATUS
                ]),
                'stale_agents_status' => $cleaned_states_id
            ]
        );

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));

        $inventory = $this->doInventory($json);

        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();
        $agents_id = $agent['id'];
        $this->assertIsArray($agent);
        $this->assertSame('glpixps-2018-07-09-09-07-13', $agent['deviceid']);
        $this->assertSame('glpixps-2018-07-09-09-07-13', $agent['name']);
        $this->assertSame('2.5.2-1.fc31', $agent['version']);
        $this->assertSame('Computer', $agent['itemtype']);
        $this->assertSame('000005', $agent['tag']);
        $this->assertSame($agenttype['id'], $agent['agenttypes_id']);
        $this->assertGreaterThan(0, $agent['items_id']);

        //check created computer
        $computers_id = $agent['items_id'];
        $this->assertGreaterThan(0, $computers_id);
        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($computers_id));

        //check states has been set
        $this->assertSame($inv_states_id, $computer->fields['states_id']);

        $lockedfield = new \Lockedfield();
        $this->assertTrue($lockedfield->isHandled($computer));
        $this->assertSame([], $lockedfield->getLockedValues($computer->getType(), $computers_id));

        //set agent inventory date in past
        $invdate = new \DateTime($agent['last_contact']);
        $invdate->sub(new \DateInterval('P1Y'));

        $agent = new \Agent();
        $this->assertTrue(
            $agent->update([
                'id' => $agents_id,
                'last_contact' => $invdate->format('Y-m-d H:i:s')
            ])
        );

        //cleanup old agents
        $name = \CronTask::launch(-\CronTask::MODE_INTERNAL, 1, 'Cleanoldagents');
        $this->assertSame('Cleanoldagents', $name);

        //check computer state has been updated
        $this->assertTrue($computer->getFromDB($computers_id));
        $this->assertSame($cleaned_states_id, $computer->fields['states_id']);

        $this->assertTrue($lockedfield->isHandled($computer));
        $this->assertSame([], $lockedfield->getLockedValues($computer->getType(), $computers_id));
    }


    public function testDefaultStatesOnAddWithGlobalLock()
    {
        global $DB;

        $this->login();

        //create default states to use
        $default_states = new \State();
        $default_states_id = $default_states->add([
            'name' => 'Has been inventoried'
        ]);
        $this->assertGreaterThan(0, $default_states_id);

        $other_state = new \State();
        $other_states_id = $other_state->add([
            'name' => 'Another states'
        ]);
        $this->assertGreaterThan(0, $other_states_id);

        \Config::setConfigurationValues(
            'inventory',
            [
                'states_id_default' => $default_states_id,
            ]
        );

        //create global  lock on Computer states_id
        $lock = new Lockedfield();
        $lock_id = $lock->add([
            'itemtype' => 'Computer',
            'items_id' => 0,
            'field' => 'states_id',
            'is_global' => 1
        ]);
        $this->assertGreaterThan(0, $lock_id);

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));
        $this->doInventory($json);

        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();
        $agents_id = $agent['id'];
        $this->assertIsArray($agent);
        $this->assertSame('glpixps-2018-07-09-09-07-13', $agent['deviceid']);
        $this->assertSame('glpixps-2018-07-09-09-07-13', $agent['name']);
        $this->assertSame('2.5.2-1.fc31', $agent['version']);
        $this->assertSame('Computer', $agent['itemtype']);
        $this->assertSame('000005', $agent['tag']);
        $this->assertSame($agenttype['id'], $agent['agenttypes_id']);
        $this->assertGreaterThan(0, $agent['items_id']);

        //check created computer
        $computers_id = $agent['items_id'];
        $this->assertGreaterThan(0, $computers_id);
        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($computers_id));

        //check default states has been set
        $this->assertSame($default_states_id, $computer->fields['states_id']);

        //update states
        $this->assertTrue($computer->update(['id' => $computers_id, 'states_id' => $other_states_id]));

        //redo inventory
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));
        $this->doInventory($json);

        //reload computer
        $this->assertTrue($computer->getFromDB($computers_id));
        //check is same on update
        $this->assertSame($other_states_id, $computer->fields['states_id']);
    }


    public function testOtherSerialFromTag()
    {
        global $DB;

        //create rule
        $input_rule = [
            'is_active' => 1,
            'name'      => 'use TAG as otherserial',
            'match'     => 'AND',
            'sub_type'  => 'RuleAsset',
            'condition' => \RuleAsset::ONADD + \RuleAsset::ONUPDATE
        ];

        $rule = new \Rule();
        $rules_id = $rule->add($input_rule);
        $this->assertGreaterThan(0, $rules_id);

        //create criteria
        $input_criteria = [
            'rules_id'  => $rules_id,
            'criteria'      => '_tag',
            'condition' => \Rule::REGEX_MATCH,
            'pattern' => '/(.*)/'
        ];
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add($input_criteria);
        $this->assertGreaterThan(0, $rule_criteria_id);

        //create action
        $input_action = [
            'rules_id'  => $rules_id,
            'action_type' => 'regex_result',
            'field' => 'otherserial',
            'value' => '#0'
        ];
        $rule_action = new \RuleAction();
        $rule_action_id = $rule_action->add($input_action);
        $this->assertGreaterThan(0, $rule_action_id);

        $tag = 'a_tag';
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
        <CONTENT>
          <HARDWARE>
            <NAME>glpixps</NAME>
            <UUID>25C1BB60-5BCB-11D9-B18F-5404A6A534C4</UUID>
          </HARDWARE>
          <BIOS>
            <MSN>640HP72</MSN>
          </BIOS>
          <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
        </CONTENT>
        <DEVICEID>test_otherserial_from_tag</DEVICEID>
        <QUERY>INVENTORY</QUERY>
        <TAG>" . $tag . "</TAG>
        </REQUEST>";

        $this->doInventory($xml_source, true);


        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable(), "WHERE" => ['deviceid' => 'test_otherserial_from_tag']]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();
        $this->assertSame($tag, $agent['tag']);

        //check created computer
        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($agent['items_id']));
        $this->assertSame($tag, $computer->fields['otherserial']);


        //redo inventory by updating tag
        $tag = 'other_tag';
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
        <CONTENT>
          <HARDWARE>
            <NAME>glpixps</NAME>
            <UUID>25C1BB60-5BCB-11D9-B18F-5404A6A534C4</UUID>
          </HARDWARE>
          <BIOS>
            <MSN>640HP72</MSN>
          </BIOS>
          <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
        </CONTENT>
        <DEVICEID>test_otherserial_from_tag</DEVICEID>
        <QUERY>INVENTORY</QUERY>
        <TAG>" . $tag . "</TAG>
        </REQUEST>";

        $this->doInventory($xml_source, true);


        //check agent
        $agents = $DB->request(['FROM' => \Agent::getTable(), "WHERE" => ['deviceid' => 'test_otherserial_from_tag']]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();
        $this->assertSame($tag, $agent['tag']);

        //check created computer
        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($agent['items_id']));
        $this->assertSame($tag, $computer->fields['otherserial']);
    }

    public function testBusinessRuleOnAddComputer()
    {
        global $DB;

        //prepare rule contents
        $state = new \State();
        $states_id = $state->add(['name' => 'Test status']);
        $this->assertGreaterThan(0, $states_id);

        $location = new \Location();
        $locations_id = $location->add(['name' => 'Test location']);
        $this->assertGreaterThan(0, $locations_id);

        //create rule
        $input_rule = [
            'is_active' => 1,
            'name'      => 'Business rule test',
            'match'     => 'AND',
            'sub_type'  => 'RuleAsset',
            'condition' => \RuleAsset::ONADD
        ];

        $rule = new \Rule();
        $rules_id = $rule->add($input_rule);
        $this->assertGreaterThan(0, $rules_id);

        //create criteria
        $input_criteria = [
            'rules_id'  => $rules_id,
            'criteria'      => '_itemtype',
            'condition' => \Rule::PATTERN_IS,
            'pattern' => \Computer::getType()
        ];
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add($input_criteria);
        $this->assertGreaterThan(0, $rule_criteria_id);

        //create actions
        $input_action = [
            'rules_id'  => $rules_id,
            'action_type' => 'assign',
            'field' => 'states_id',
            'value' => $states_id
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        $input_action = [
            'rules_id'  => $rules_id,
            'action_type' => 'assign',
            'field' => 'locations_id',
            'value' => $locations_id
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        $input_action = [
            'rules_id'  => $rules_id,
            'action_type' => 'assign',
            'field' => 'comment',
            'value' => 'A comment'
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        //ensure business rule work on regular Computer add
        $computer = new \Computer();
        $computers_id = $computer->add(['name' => 'Test computer', 'entities_id' => 0]);
        $this->assertGreaterThan(0, $computers_id);
        $this->assertTrue($computer->getFromDB($computers_id));

        $this->assertSame('A comment', $computer->fields['comment']);
        $this->assertSame($states_id, $computer->fields['states_id']);
        $this->assertSame($locations_id, $computer->fields['locations_id']);

        //inventory a new computer
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
        <CONTENT>
          <HARDWARE>
            <NAME>glpixps</NAME>
            <UUID>25C1BB60-5BCB-11D9-B18F-5404A6A534C4</UUID>
          </HARDWARE>
          <BIOS>
            <MSN>640HP72</MSN>
          </BIOS>
          <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
        </CONTENT>
        <DEVICEID>test_setstatusifinventory</DEVICEID>
        <QUERY>INVENTORY</QUERY>
        </REQUEST>";

        $this->doInventory($xml_source, true);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable(), "WHERE" => ['deviceid' => 'test_setstatusifinventory']]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();

        //check created computer
        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($agent['items_id']));
        $this->assertSame('A comment', $computer->fields['comment']);
        $this->assertSame($states_id, $computer->fields['states_id']);
        $this->assertSame($locations_id, $computer->fields['locations_id']);

        //redo inventory
        $this->doInventory($xml_source, true);
        $this->assertTrue($computer->getFromDB($agent['items_id']));
        $this->assertSame('A comment', $computer->fields['comment']);
        $this->assertSame($states_id, $computer->fields['states_id']);
        $this->assertSame($locations_id, $computer->fields['locations_id']);
    }

    public function testBusinessRuleOnUpdateComputer()
    {
        global $DB;

        //prepare rule contents
        $state = new \State();
        $states_id = $state->add(['name' => 'Test status']);
        $this->assertGreaterThan(0, $states_id);

        $location = new \Location();
        $locations_id = $location->add(['name' => 'Test location']);
        $this->assertGreaterThan(0, $locations_id);

        //create rule
        $input_rule = [
            'is_active' => 1,
            'name' => 'Business rule test',
            'match' => 'AND',
            'sub_type' => 'RuleAsset',
            'condition' => \RuleAsset::ONUPDATE
        ];

        $rule = new \Rule();
        $rules_id = $rule->add($input_rule);
        $this->assertGreaterThan(0, $rules_id);

        //create criteria
        $input_criteria = [
            'rules_id' => $rules_id,
            'criteria' => '_itemtype',
            'condition' => \Rule::PATTERN_IS,
            'pattern' => \Computer::getType()
        ];
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add($input_criteria);
        $this->assertGreaterThan(0, $rule_criteria_id);

        //create actions
        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'states_id',
            'value' => $states_id
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'locations_id',
            'value' => $locations_id
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'comment',
            'value' => 'A comment'
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        //ensure business rule work on regular Computer add
        $computer = new \Computer();
        $computers_id = $computer->add(['name' => 'Test computer', 'entities_id' => 0]);
        $this->assertGreaterThan(0, $computers_id);
        $this->assertTrue($computer->getFromDB($computers_id));

        $this->assertNull($computer->fields['comment']);
        $this->assertSame(0, $computer->fields['states_id']);
        $this->assertSame(0, $computer->fields['locations_id']);

        //update computer
        $this->assertTrue(
            $computer->update([
                'id' => $computers_id,
                'comment' => 'Another comment'
            ])
        );
        $this->assertTrue($computer->getFromDB($computers_id));

        $this->assertSame('A comment', $computer->fields['comment']);
        $this->assertSame($states_id, $computer->fields['states_id']);
        $this->assertSame($locations_id, $computer->fields['locations_id']);

        //inventory a new computer
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
        <CONTENT>
          <HARDWARE>
            <NAME>glpixps</NAME>
            <UUID>25C1BB60-5BCB-11D9-B18F-5404A6A534C4</UUID>
          </HARDWARE>
          <BIOS>
            <MSN>640HP72</MSN>
          </BIOS>
          <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
        </CONTENT>
        <DEVICEID>test_setstatusifinventory</DEVICEID>
        <QUERY>INVENTORY</QUERY>
        </REQUEST>";

        $this->doInventory($xml_source, true);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable(), "WHERE" => ['deviceid' => 'test_setstatusifinventory']]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();

        //check created computer
        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($agent['items_id']));
        $this->assertNull($computer->fields['comment']);
        $this->assertSame(0, $computer->fields['states_id']);
        $this->assertSame(0, $computer->fields['locations_id']);

        //redo inventory
        $this->doInventory($xml_source, true);
        $this->assertTrue($computer->getFromDB($agent['items_id']));
        $this->assertSame('A comment', $computer->fields['comment']);
        $this->assertSame($states_id, $computer->fields['states_id']);
        $this->assertSame($locations_id, $computer->fields['locations_id']);
    }

    public function testBusinessRuleOnAddAndOnUpdateComputer()
    {
        global $DB;

        //prepare rule contents
        $state = new \State();
        $states_id = $state->add(['name' => 'Test status']);
        $this->assertGreaterThan(0, $states_id);

        $location = new \Location();
        $locations_id = $location->add(['name' => 'Test location']);
        $this->assertGreaterThan(0, $locations_id);

        //create rule
        $input_rule = [
            'is_active' => 1,
            'name' => 'Business rule test',
            'match' => 'AND',
            'sub_type' => 'RuleAsset',
            'condition' => \RuleAsset::ONADD + \RuleAsset::ONUPDATE
        ];

        $rule = new \Rule();
        $rules_id = $rule->add($input_rule);
        $this->assertGreaterThan(0, $rules_id);

        //create criteria
        $input_criteria = [
            'rules_id' => $rules_id,
            'criteria' => '_itemtype',
            'condition' => \Rule::PATTERN_IS,
            'pattern' => \Computer::getType()
        ];
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add($input_criteria);
        $this->assertGreaterThan(0, $rule_criteria_id);

        //create actions
        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'states_id',
            'value' => $states_id
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'locations_id',
            'value' => $locations_id
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'comment',
            'value' => 'A comment'
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        //ensure business rule work on regular Computer add
        $computer = new \Computer();
        $computers_id = $computer->add(['name' => 'Test computer', 'entities_id' => 0]);
        $this->assertGreaterThan(0, $computers_id);
        $this->assertTrue($computer->getFromDB($computers_id));

        $this->assertSame('A comment', $computer->fields['comment']);
        $this->assertSame($states_id, $computer->fields['states_id']);
        $this->assertSame($locations_id, $computer->fields['locations_id']);

        //update computer
        $this->assertTrue(
            $computer->update([
                'id' => $computers_id,
                'comment' => 'Another comment'
            ])
        );
        $this->assertTrue($computer->getFromDB($computers_id));

        $this->assertSame('A comment', $computer->fields['comment']);
        $this->assertSame($states_id, $computer->fields['states_id']);
        $this->assertSame($locations_id, $computer->fields['locations_id']);

        //inventory a new computer
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
            <REQUEST>
            <CONTENT>
              <HARDWARE>
                <NAME>glpixps</NAME>
                <UUID>25C1BB60-5BCB-11D9-B18F-5404A6A534C4</UUID>
              </HARDWARE>
              <BIOS>
                <MSN>640HP72</MSN>
              </BIOS>
              <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
            </CONTENT>
            <DEVICEID>test_setstatusifinventory</DEVICEID>
            <QUERY>INVENTORY</QUERY>
            </REQUEST>";

        $this->doInventory($xml_source, true);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable(), "WHERE" => ['deviceid' => 'test_setstatusifinventory']]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();

        //check created computer
        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($agent['items_id']));
        $this->assertSame('A comment', $computer->fields['comment']);
        $this->assertSame($states_id, $computer->fields['states_id']);
        $this->assertSame($locations_id, $computer->fields['locations_id']);

        //redo inventory
        $this->doInventory($xml_source, true);
        $this->assertTrue($computer->getFromDB($agent['items_id']));
        $this->assertSame('A comment', $computer->fields['comment']);
        $this->assertSame($states_id, $computer->fields['states_id']);
        $this->assertSame($locations_id, $computer->fields['locations_id']);
    }

    public function testBusinessRuleOnAddNetworkEquipment()
    {
        global $DB;

        //prepare rule contents
        $state = new \State();
        $states_id = $state->add(['name' => 'Test status']);
        $this->assertGreaterThan(0, $states_id);

        $location = new \Location();
        $locations_id = $location->add(['name' => 'Test location']);
        $this->assertGreaterThan(0, $locations_id);

        //create rule
        $input_rule = [
            'is_active' => 1,
            'name'      => 'Business rule test',
            'match'     => 'AND',
            'sub_type'  => 'RuleAsset',
            'condition' => \RuleAsset::ONADD
        ];

        $rule = new \Rule();
        $rules_id = $rule->add($input_rule);
        $this->assertGreaterThan(0, $rules_id);

        //create criteria
        $input_criteria = [
            'rules_id'  => $rules_id,
            'criteria'      => '_itemtype',
            'condition' => \Rule::PATTERN_IS,
            'pattern' => \NetworkEquipment::getType()
        ];
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add($input_criteria);
        $this->assertGreaterThan(0, $rule_criteria_id);

        //create actions
        $input_action = [
            'rules_id'  => $rules_id,
            'action_type' => 'assign',
            'field' => 'states_id',
            'value' => $states_id
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        $input_action = [
            'rules_id'  => $rules_id,
            'action_type' => 'assign',
            'field' => 'locations_id',
            'value' => $locations_id
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        $input_action = [
            'rules_id'  => $rules_id,
            'action_type' => 'assign',
            'field' => 'comment',
            'value' => 'A comment'
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        //ensure business rule work on regular Network Equipment add
        $neteq = new \NetworkEquipment();
        $networkeequipments_id = $neteq->add(['name' => 'Test network equipment', 'entities_id' => 0]);
        $this->assertGreaterThan(0, $networkeequipments_id);
        $this->assertTrue($neteq->getFromDB($networkeequipments_id));

        $this->assertSame('A comment', $neteq->fields['comment']);
        $this->assertSame($states_id, $neteq->fields['states_id']);
        $this->assertSame($locations_id, $neteq->fields['locations_id']);

        //inventory a new network equipment
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <FIRMWARES>
        <DESCRIPTION>device firmware</DESCRIPTION>
        <MANUFACTURER>Cisco</MANUFACTURER>
        <NAME>UCS 6248UP 48-Port</NAME>
        <TYPE>device</TYPE>
        <VERSION>5.0(3)N2(4.02b)</VERSION>
      </FIRMWARES>
      <INFO>
        <COMMENTS>Cisco NX-OS(tm) ucs, Software (ucs-6100-k9-system), Version 5.0(3)N2(4.02b), RELEASE SOFTWARE Copyright (c) 2002-2013 by Cisco Systems, Inc.   Compiled 1/16/2019 18:00:00</COMMENTS>
        <CONTACT>noc@glpi-project.org</CONTACT>
        <CPU>4</CPU>
        <FIRMWARE>5.0(3)N2(4.02b)</FIRMWARE>
        <ID>0</ID>
        <LOCATION>paris.pa3</LOCATION>
        <MAC>8c:60:4f:8d:ae:fc</MAC>
        <MANUFACTURER>Cisco</MANUFACTURER>
        <MODEL>UCS 6248UP 48-Port</MODEL>
        <NAME>ucs6248up-cluster-pa3-B</NAME>
        <SERIAL>SSI1912014B</SERIAL>
        <TYPE>NETWORKING</TYPE>
        <UPTIME>482 days, 05:42:18.50</UPTIME>
        <IPS>
           <IP>127.0.0.1</IP>
           <IP>10.2.5.10</IP>
           <IP>192.168.12.5</IP>
        </IPS>
      </INFO>
    </DEVICE>
    <MODULEVERSION>4.1</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>";

        $this->doInventory($xml_source, true);

        //check created networkequipment
        $neteq = new \NetworkEquipment();
        $this->assertTrue($neteq->getFromDBByCrit(['serial' => 'SSI1912014B']));
        $this->assertSame('A comment', $neteq->fields['comment']);
        $this->assertSame($states_id, $neteq->fields['states_id']);
        $this->assertSame($locations_id, $neteq->fields['locations_id']);

        //redo inventory
        $this->doInventory($xml_source, true);
        $this->assertTrue($neteq->getFromDBByCrit(['serial' => 'SSI1912014B']));
        $this->assertSame('A comment', $neteq->fields['comment']);
        $this->assertSame($states_id, $neteq->fields['states_id']);
        //location is not set by rule on update, but is set from inventory data
        $this->assertSame(getItemByTypeName(\Location::class, 'paris.pa3', true), $neteq->fields['locations_id']);
    }

    public function testBusinessRuleOnUpdateNetworkEquipment()
    {
        global $DB;

        //prepare rule contents
        $state = new \State();
        $states_id = $state->add(['name' => 'Test status']);
        $this->assertGreaterThan(0, $states_id);

        $location = new \Location();
        $locations_id = $location->add(['name' => 'Test location']);
        $this->assertGreaterThan(0, $locations_id);

        //create rule
        $input_rule = [
            'is_active' => 1,
            'name' => 'Business rule test',
            'match' => 'AND',
            'sub_type' => 'RuleAsset',
            'condition' => \RuleAsset::ONUPDATE
        ];

        $rule = new \Rule();
        $rules_id = $rule->add($input_rule);
        $this->assertGreaterThan(0, $rules_id);

        //create criteria
        $input_criteria = [
            'rules_id' => $rules_id,
            'criteria' => '_itemtype',
            'condition' => \Rule::PATTERN_IS,
            'pattern' => \NetworkEquipment::getType()
        ];
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add($input_criteria);
        $this->assertGreaterThan(0, $rule_criteria_id);

        //create actions
        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'states_id',
            'value' => $states_id
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'locations_id',
            'value' => $locations_id
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'comment',
            'value' => 'A comment'
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        //ensure business rule work on regular Network equipment add
        $neteq = new \NetworkEquipment();
        $networkequipments_id = $neteq->add(['name' => 'Test network equipment', 'entities_id' => 0]);
        $this->assertGreaterThan(0, $networkequipments_id);
        $this->assertTrue($neteq->getFromDB($networkequipments_id));

        $this->assertNull($neteq->fields['comment']);
        $this->assertSame(0, $neteq->fields['states_id']);
        $this->assertSame(0, $neteq->fields['locations_id']);

        //update network equipment
        $this->assertTrue(
            $neteq->update([
                'id' => $networkequipments_id,
                'comment' => 'Another comment'
            ])
        );
        $this->assertTrue($neteq->getFromDB($networkequipments_id));

        $this->assertSame('A comment', $neteq->fields['comment']);
        $this->assertSame($states_id, $neteq->fields['states_id']);
        $this->assertSame($locations_id, $neteq->fields['locations_id']);

        //inventory a new network equipment
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <FIRMWARES>
        <DESCRIPTION>device firmware</DESCRIPTION>
        <MANUFACTURER>Cisco</MANUFACTURER>
        <NAME>UCS 6248UP 48-Port</NAME>
        <TYPE>device</TYPE>
        <VERSION>5.0(3)N2(4.02b)</VERSION>
      </FIRMWARES>
      <INFO>
        <COMMENTS>Cisco NX-OS(tm) ucs, Software (ucs-6100-k9-system), Version 5.0(3)N2(4.02b), RELEASE SOFTWARE Copyright (c) 2002-2013 by Cisco Systems, Inc.   Compiled 1/16/2019 18:00:00</COMMENTS>
        <CONTACT>noc@glpi-project.org</CONTACT>
        <CPU>4</CPU>
        <FIRMWARE>5.0(3)N2(4.02b)</FIRMWARE>
        <ID>0</ID>
        <LOCATION>paris.pa3</LOCATION>
        <MAC>8c:60:4f:8d:ae:fc</MAC>
        <MANUFACTURER>Cisco</MANUFACTURER>
        <MODEL>UCS 6248UP 48-Port</MODEL>
        <NAME>ucs6248up-cluster-pa3-B</NAME>
        <SERIAL>SSI1912014B</SERIAL>
        <TYPE>NETWORKING</TYPE>
        <UPTIME>482 days, 05:42:18.50</UPTIME>
        <IPS>
           <IP>127.0.0.1</IP>
           <IP>10.2.5.10</IP>
           <IP>192.168.12.5</IP>
        </IPS>
      </INFO>
    </DEVICE>
    <MODULEVERSION>4.1</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>";

        $this->doInventory($xml_source, true);

        //check created network equipment
        $neteq = new \NetworkEquipment();
        $this->assertTrue($neteq->getFromDBByCrit(['serial' => 'SSI1912014B']));
        $this->assertNull($neteq->fields['comment']);
        $this->assertSame(0, $neteq->fields['states_id']);
        $this->assertSame(getItemByTypeName(\Location::class, 'paris.pa3', true), $neteq->fields['locations_id']);

        //redo inventory
        $this->doInventory($xml_source, true);
        $this->assertTrue($neteq->getFromDBByCrit(['serial' => 'SSI1912014B']));
        $this->assertSame('A comment', $neteq->fields['comment']);
        $this->assertSame($states_id, $neteq->fields['states_id']);
        $this->assertSame($locations_id, $neteq->fields['locations_id']);
    }

    public function testBusinessRuleOnAddAndOnUpdateNetworkEquipment()
    {
        global $DB;

        //prepare rule contents
        $state = new \State();
        $states_id = $state->add(['name' => 'Test status']);
        $this->assertGreaterThan(0, $states_id);

        $location = new \Location();
        $locations_id = $location->add(['name' => 'Test location']);
        $this->assertGreaterThan(0, $locations_id);

        //create rule
        $input_rule = [
            'is_active' => 1,
            'name' => 'Business rule test',
            'match' => 'AND',
            'sub_type' => 'RuleAsset',
            'condition' => \RuleAsset::ONADD + \RuleAsset::ONUPDATE
        ];

        $rule = new \Rule();
        $rules_id = $rule->add($input_rule);
        $this->assertGreaterThan(0, $rules_id);

        //create criteria
        $input_criteria = [
            'rules_id' => $rules_id,
            'criteria' => '_itemtype',
            'condition' => \Rule::PATTERN_IS,
            'pattern' => \NetworkEquipment::getType()
        ];
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add($input_criteria);
        $this->assertGreaterThan(0, $rule_criteria_id);

        //create actions
        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'states_id',
            'value' => $states_id
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'locations_id',
            'value' => $locations_id
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'comment',
            'value' => 'A comment'
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        //ensure business rule work on regular Network equipment add
        $neteq = new \NetworkEquipment();
        $networkequipments_id = $neteq->add(['name' => 'Test network equipment', 'entities_id' => 0]);
        $this->assertGreaterThan(0, $networkequipments_id);
        $this->assertTrue($neteq->getFromDB($networkequipments_id));

        $this->assertSame('A comment', $neteq->fields['comment']);
        $this->assertSame($states_id, $neteq->fields['states_id']);
        $this->assertSame($locations_id, $neteq->fields['locations_id']);

        //update network equipment
        $this->assertTrue(
            $neteq->update([
                'id' => $networkequipments_id,
                'comment' => 'Another comment'
            ])
        );
        $this->assertTrue($neteq->getFromDB($networkequipments_id));

        $this->assertSame('A comment', $neteq->fields['comment']);
        $this->assertSame($states_id, $neteq->fields['states_id']);
        $this->assertSame($locations_id, $neteq->fields['locations_id']);

        //inventory a new network equipment
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <FIRMWARES>
        <DESCRIPTION>device firmware</DESCRIPTION>
        <MANUFACTURER>Cisco</MANUFACTURER>
        <NAME>UCS 6248UP 48-Port</NAME>
        <TYPE>device</TYPE>
        <VERSION>5.0(3)N2(4.02b)</VERSION>
      </FIRMWARES>
      <INFO>
        <COMMENTS>Cisco NX-OS(tm) ucs, Software (ucs-6100-k9-system), Version 5.0(3)N2(4.02b), RELEASE SOFTWARE Copyright (c) 2002-2013 by Cisco Systems, Inc.   Compiled 1/16/2019 18:00:00</COMMENTS>
        <CONTACT>noc@glpi-project.org</CONTACT>
        <CPU>4</CPU>
        <FIRMWARE>5.0(3)N2(4.02b)</FIRMWARE>
        <ID>0</ID>
        <LOCATION>paris.pa3</LOCATION>
        <MAC>8c:60:4f:8d:ae:fc</MAC>
        <MANUFACTURER>Cisco</MANUFACTURER>
        <MODEL>UCS 6248UP 48-Port</MODEL>
        <NAME>ucs6248up-cluster-pa3-B</NAME>
        <SERIAL>SSI1912014B</SERIAL>
        <TYPE>NETWORKING</TYPE>
        <UPTIME>482 days, 05:42:18.50</UPTIME>
        <IPS>
           <IP>127.0.0.1</IP>
           <IP>10.2.5.10</IP>
           <IP>192.168.12.5</IP>
        </IPS>
      </INFO>
    </DEVICE>
    <MODULEVERSION>4.1</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>";

        $this->doInventory($xml_source, true);

        //check created network equipment
        $neteq = new \NetworkEquipment();
        $this->assertTrue($neteq->getFromDBByCrit(['serial' => 'SSI1912014B']));
        $this->assertSame('A comment', $neteq->fields['comment']);
        $this->assertSame($states_id, $neteq->fields['states_id']);
        $this->assertSame($locations_id, $neteq->fields['locations_id']);

        //redo inventory
        $this->doInventory($xml_source, true);
        $this->assertTrue($neteq->getFromDBByCrit(['serial' => 'SSI1912014B']));
        $this->assertSame('A comment', $neteq->fields['comment']);
        $this->assertSame($states_id, $neteq->fields['states_id']);
        $this->assertSame($locations_id, $neteq->fields['locations_id']);
    }

    public function testBusinessRuleOnAddPrinter()
    {
        global $DB;

        //prepare rule contents
        $state = new \State();
        $states_id = $state->add(['name' => 'Test status']);
        $this->assertGreaterThan(0, $states_id);

        $location = new \Location();
        $locations_id = $location->add(['name' => 'Test location']);
        $this->assertGreaterThan(0, $locations_id);

        //create rule
        $input_rule = [
            'is_active' => 1,
            'name'      => 'Business rule test',
            'match'     => 'AND',
            'sub_type'  => 'RuleAsset',
            'condition' => \RuleAsset::ONADD
        ];

        $rule = new \Rule();
        $rules_id = $rule->add($input_rule);
        $this->assertGreaterThan(0, $rules_id);

        //create criteria
        $input_criteria = [
            'rules_id'  => $rules_id,
            'criteria'      => '_itemtype',
            'condition' => \Rule::PATTERN_IS,
            'pattern' => \Printer::getType()
        ];
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add($input_criteria);
        $this->assertGreaterThan(0, $rule_criteria_id);

        //create actions
        $input_action = [
            'rules_id'  => $rules_id,
            'action_type' => 'assign',
            'field' => 'states_id',
            'value' => $states_id
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        $input_action = [
            'rules_id'  => $rules_id,
            'action_type' => 'assign',
            'field' => 'locations_id',
            'value' => $locations_id
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        $input_action = [
            'rules_id'  => $rules_id,
            'action_type' => 'assign',
            'field' => 'comment',
            'value' => 'A comment'
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        //ensure business rule work on regular printer add
        $printer = new \Printer();
        $printers_id = $printer->add(['name' => 'Test printer', 'entities_id' => 0]);
        $this->assertGreaterThan(0, $printers_id);
        $this->assertTrue($printer->getFromDB($printers_id));

        $this->assertSame('A comment', $printer->fields['comment']);
        $this->assertSame($states_id, $printer->fields['states_id']);
        $this->assertSame($locations_id, $printer->fields['locations_id']);

        //inventory a new printer
        $xml_source = '<?xml version="1.0" encoding="UTF-8"?>
        <REQUEST>
          <CONTENT>
            <DEVICE>
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
            </DEVICE>
            <MODULEVERSION>5.1</MODULEVERSION>
            <PROCESSNUMBER>7</PROCESSNUMBER>
          </CONTENT>
          <DEVICEID>foo</DEVICEID>
          <QUERY>SNMPQUERY</QUERY>
        </REQUEST>
        ';

        $this->doInventory($xml_source, true);

        //check created printer
        $printer = new \Printer();
        $this->assertTrue($printer->getFromDBByCrit(['serial' => 'E1234567890']));
        $this->assertSame('A comment', $printer->fields['comment']);
        $this->assertSame($states_id, $printer->fields['states_id']);
        $this->assertSame($locations_id, $printer->fields['locations_id']);

        //redo inventory
        $this->doInventory($xml_source, true);
        $this->assertTrue($printer->getFromDBByCrit(['serial' => 'E1234567890']));
        $this->assertSame('A comment', $printer->fields['comment']);
        $this->assertSame($states_id, $printer->fields['states_id']);
        //location is not set by rule on update, but is set from inventory data
        $this->assertSame(getItemByTypeName(\Location::class, 'Location', true), $printer->fields['locations_id']);
    }

    public function testBusinessRuleOnUpdatePrinter()
    {
        global $DB;

        //prepare rule contents
        $state = new \State();
        $states_id = $state->add(['name' => 'Test status']);
        $this->assertGreaterThan(0, $states_id);

        $location = new \Location();
        $locations_id = $location->add(['name' => 'Test location']);
        $this->assertGreaterThan(0, $locations_id);

        //create rule
        $input_rule = [
            'is_active' => 1,
            'name' => 'Business rule test',
            'match' => 'AND',
            'sub_type' => 'RuleAsset',
            'condition' => \RuleAsset::ONUPDATE
        ];

        $rule = new \Rule();
        $rules_id = $rule->add($input_rule);
        $this->assertGreaterThan(0, $rules_id);

        //create criteria
        $input_criteria = [
            'rules_id' => $rules_id,
            'criteria' => '_itemtype',
            'condition' => \Rule::PATTERN_IS,
            'pattern' => \Printer::getType()
        ];
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add($input_criteria);
        $this->assertGreaterThan(0, $rule_criteria_id);

        //create actions
        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'states_id',
            'value' => $states_id
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'locations_id',
            'value' => $locations_id
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'comment',
            'value' => 'A comment'
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        //ensure business rule work on regular printer add
        $printer = new \Printer();
        $printers_id = $printer->add(['name' => 'Test printer', 'entities_id' => 0]);
        $this->assertGreaterThan(0, $printers_id);
        $this->assertTrue($printer->getFromDB($printers_id));

        $this->assertNull($printer->fields['comment']);
        $this->assertSame(0, $printer->fields['states_id']);
        $this->assertSame(0, $printer->fields['locations_id']);

        //update printer
        $this->assertTrue(
            $printer->update([
                'id' => $printers_id,
                'comment' => 'Another comment'
            ])
        );
        $this->assertTrue($printer->getFromDB($printers_id));

        $this->assertSame('A comment', $printer->fields['comment']);
        $this->assertSame($states_id, $printer->fields['states_id']);
        $this->assertSame($locations_id, $printer->fields['locations_id']);

        //inventory a new printer
        $xml_source = '<?xml version="1.0" encoding="UTF-8"?>
        <REQUEST>
          <CONTENT>
            <DEVICE>
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
            </DEVICE>
            <MODULEVERSION>5.1</MODULEVERSION>
            <PROCESSNUMBER>7</PROCESSNUMBER>
          </CONTENT>
          <DEVICEID>foo</DEVICEID>
          <QUERY>SNMPQUERY</QUERY>
        </REQUEST>
        ';

        $this->doInventory($xml_source, true);

        //check created printer
        $printer = new \Printer();
        $this->assertTrue($printer->getFromDBByCrit(['serial' => 'E1234567890']));
        $this->assertNull($printer->fields['comment']);
        $this->assertSame(0, $printer->fields['states_id']);
        $this->assertSame(getItemByTypeName(\Location::class, 'Location', true), $printer->fields['locations_id']);

        //redo inventory
        $this->doInventory($xml_source, true);
        $this->assertTrue($printer->getFromDBByCrit(['serial' => 'E1234567890']));
        $this->assertSame('A comment', $printer->fields['comment']);
        $this->assertSame($states_id, $printer->fields['states_id']);
        $this->assertSame($locations_id, $printer->fields['locations_id']);
    }

    public function testBusinessRuleOnAddAndOnUpdatePrinter()
    {
        global $DB;

        //prepare rule contents
        $state = new \State();
        $states_id = $state->add(['name' => 'Test status']);
        $this->assertGreaterThan(0, $states_id);

        $location = new \Location();
        $locations_id = $location->add(['name' => 'Test location']);
        $this->assertGreaterThan(0, $locations_id);

        //create rule
        $input_rule = [
            'is_active' => 1,
            'name' => 'Business rule test',
            'match' => 'AND',
            'sub_type' => 'RuleAsset',
            'condition' => \RuleAsset::ONADD + \RuleAsset::ONUPDATE
        ];

        $rule = new \Rule();
        $rules_id = $rule->add($input_rule);
        $this->assertGreaterThan(0, $rules_id);

        //create criteria
        $input_criteria = [
            'rules_id' => $rules_id,
            'criteria' => '_itemtype',
            'condition' => \Rule::PATTERN_IS,
            'pattern' => \Printer::getType()
        ];
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add($input_criteria);
        $this->assertGreaterThan(0, $rule_criteria_id);

        //create actions
        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'states_id',
            'value' => $states_id
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'locations_id',
            'value' => $locations_id
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'comment',
            'value' => 'A comment'
        ];
        $rule_action = new \RuleAction();
        $this->assertGreaterThan(0, $rule_action->add($input_action));

        //ensure business rule work on regular printer add
        $printer = new \Printer();
        $printers_id = $printer->add(['name' => 'Test printer', 'entities_id' => 0]);
        $this->assertGreaterThan(0, $printers_id);
        $this->assertTrue($printer->getFromDB($printers_id));

        $this->assertSame('A comment', $printer->fields['comment']);
        $this->assertSame($states_id, $printer->fields['states_id']);
        $this->assertSame($locations_id, $printer->fields['locations_id']);

        //update network equipment
        $this->assertTrue(
            $printer->update([
                'id' => $printers_id,
                'comment' => 'Another comment'
            ])
        );
        $this->assertTrue($printer->getFromDB($printers_id));

        $this->assertSame('A comment', $printer->fields['comment']);
        $this->assertSame($states_id, $printer->fields['states_id']);
        $this->assertSame($locations_id, $printer->fields['locations_id']);

        //inventory a new printer
        $xml_source = '<?xml version="1.0" encoding="UTF-8"?>
        <REQUEST>
          <CONTENT>
            <DEVICE>
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
            </DEVICE>
            <MODULEVERSION>5.1</MODULEVERSION>
            <PROCESSNUMBER>7</PROCESSNUMBER>
          </CONTENT>
          <DEVICEID>foo</DEVICEID>
          <QUERY>SNMPQUERY</QUERY>
        </REQUEST>
        ';

        $this->doInventory($xml_source, true);

        //check created printer
        $printer = new \Printer();
        $this->assertTrue($printer->getFromDBByCrit(['serial' => 'E1234567890']));
        $this->assertSame('A comment', $printer->fields['comment']);
        $this->assertSame($states_id, $printer->fields['states_id']);
        $this->assertSame($locations_id, $printer->fields['locations_id']);

        //redo inventory
        $this->doInventory($xml_source, true);
        $this->assertTrue($printer->getFromDBByCrit(['serial' => 'E1234567890']));
        $this->assertSame('A comment', $printer->fields['comment']);
        $this->assertSame($states_id, $printer->fields['states_id']);
        $this->assertSame($locations_id, $printer->fields['locations_id']);
    }

    public function testStatusIfInventoryOnAdd()
    {
        global $DB;

        //create rule
        $input_rule = [
            'is_active' => 1,
            'name'      => 'set status if inventory',
            'match'     => 'AND',
            'sub_type'  => 'RuleAsset',
            'condition' => \RuleAsset::ONADD
        ];

        $rule = new \Rule();
        $rules_id = $rule->add($input_rule);
        $this->assertGreaterThan(0, $rules_id);

        //create criteria
        $input_criteria = [
            'rules_id'  => $rules_id,
            'criteria'      => '_auto',
            'condition' => \Rule::PATTERN_IS,
            'pattern' => '1'
        ];
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add($input_criteria);
        $this->assertGreaterThan(0, $rule_criteria_id);

        $state = new \State();
        $states_id = $state->add(['name' => 'test_status_if_inventory']);
        $this->assertGreaterThan(0, $states_id);

        //create action
        $input_action = [
            'rules_id'  => $rules_id,
            'action_type' => 'assign',
            'field' => 'states_id',
            'value' => $states_id
        ];
        $rule_action = new \RuleAction();
        $rule_action_id = $rule_action->add($input_action);
        $this->assertGreaterThan(0, $rule_action_id);

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
        <CONTENT>
          <HARDWARE>
            <NAME>glpixps</NAME>
            <UUID>25C1BB60-5BCB-11D9-B18F-5404A6A534C4</UUID>
          </HARDWARE>
          <BIOS>
            <MSN>640HP72</MSN>
          </BIOS>
          <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
        </CONTENT>
        <DEVICEID>test_setstatusifinventory</DEVICEID>
        <QUERY>INVENTORY</QUERY>
        </REQUEST>";

        $this->doInventory($xml_source, true);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable(), "WHERE" => ['deviceid' => 'test_setstatusifinventory']]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();

        //check created computer
        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($agent['items_id']));
        $this->assertSame($states_id, $computer->fields['states_id']);

        //redo inventory
        $this->doInventory($xml_source, true);
        $this->assertTrue($computer->getFromDB($agent['items_id']));
        $this->assertSame($states_id, $computer->fields['states_id']);
    }

    public function testStatusIfInventoryOnUpdate()
    {
        global $DB;

        //create rule
        $input_rule = [
            'is_active' => 1,
            'name'      => 'set status if inventory',
            'match'     => 'AND',
            'sub_type'  => 'RuleAsset',
            'condition' => \RuleAsset::ONUPDATE
        ];

        $rule = new \Rule();
        $rules_id = $rule->add($input_rule);
        $this->assertGreaterThan(0, $rules_id);

        //create criteria
        $input_criteria = [
            'rules_id'  => $rules_id,
            'criteria'      => '_auto',
            'condition' => \Rule::PATTERN_IS,
            'pattern' => '1'
        ];
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add($input_criteria);
        $this->assertGreaterThan(0, $rule_criteria_id);

        $state = new \State();
        $states_id = $state->add(['name' => 'test_status_if_inventory']);
        $this->assertGreaterThan(0, $states_id);

        //create action
        $input_action = [
            'rules_id'  => $rules_id,
            'action_type' => 'assign',
            'field' => 'states_id',
            'value' => $states_id
        ];
        $rule_action = new \RuleAction();
        $rule_action_id = $rule_action->add($input_action);
        $this->assertGreaterThan(0, $rule_action_id);

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
        <CONTENT>
          <HARDWARE>
            <NAME>glpixps</NAME>
            <UUID>25C1BB60-5BCB-11D9-B18F-5404A6A534C4</UUID>
          </HARDWARE>
          <BIOS>
            <MSN>640HP72</MSN>
          </BIOS>
          <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
        </CONTENT>
        <DEVICEID>test_setstatusifinventory</DEVICEID>
        <QUERY>INVENTORY</QUERY>
        </REQUEST>";

        $this->doInventory($xml_source, true);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable(), "WHERE" => ['deviceid' => 'test_setstatusifinventory']]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();

        //check created computer
        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($agent['items_id']));
        $this->assertSame(0, $computer->fields['states_id']);

        //redo inventory
        $this->doInventory($xml_source, true);
        $this->assertTrue($computer->getFromDB($agent['items_id']));
        $this->assertSame($states_id, $computer->fields['states_id']);
    }

    public function testStatusIfInventoryOnAddUpdate()
    {
        global $DB;

        //create rule
        $input_rule = [
            'is_active' => 1,
            'name'      => 'set status if inventory',
            'match'     => 'AND',
            'sub_type'  => 'RuleAsset',
            'condition' => \RuleAsset::ONADD + \RuleAsset::ONUPDATE
        ];

        $rule = new \Rule();
        $rules_id = $rule->add($input_rule);
        $this->assertGreaterThan(0, $rules_id);

        //create criteria
        $input_criteria = [
            'rules_id'  => $rules_id,
            'criteria'      => '_auto',
            'condition' => \Rule::PATTERN_IS,
            'pattern' => '1'
        ];
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add($input_criteria);
        $this->assertGreaterThan(0, $rule_criteria_id);

        $state = new \State();
        $states_id = $state->add(['name' => 'test_status_if_inventory']);
        $this->assertGreaterThan(0, $states_id);

        //create action
        $input_action = [
            'rules_id'  => $rules_id,
            'action_type' => 'assign',
            'field' => 'states_id',
            'value' => $states_id
        ];
        $rule_action = new \RuleAction();
        $rule_action_id = $rule_action->add($input_action);
        $this->assertGreaterThan(0, $rule_action_id);

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
        <CONTENT>
          <HARDWARE>
            <NAME>glpixps</NAME>
            <UUID>25C1BB60-5BCB-11D9-B18F-5404A6A534C4</UUID>
          </HARDWARE>
          <BIOS>
            <MSN>640HP72</MSN>
          </BIOS>
          <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
        </CONTENT>
        <DEVICEID>test_setstatusifinventory</DEVICEID>
        <QUERY>INVENTORY</QUERY>
        </REQUEST>";

        $this->doInventory($xml_source, true);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable(), "WHERE" => ['deviceid' => 'test_setstatusifinventory']]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();

        //check created computer
        $computer = new \Computer();
        $this->assertTrue($computer->getFromDB($agent['items_id']));
        $this->assertSame($states_id, $computer->fields['states_id']);

        //redo inventory
        $this->doInventory($xml_source, true);
        $this->assertTrue($computer->getFromDB($agent['items_id']));
        $this->assertSame($states_id, $computer->fields['states_id']);
    }

    public function testLocationHIerarchy()
    {
        global $DB;

        //count existing locations
        $locations = $DB->request(['FROM' => 'glpi_locations']);
        $count_locations = count($locations);

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <FIRMWARES>
        <DESCRIPTION>device firmware</DESCRIPTION>
        <MANUFACTURER>Cisco</MANUFACTURER>
        <NAME>UCS 6248UP 48-Port</NAME>
        <TYPE>device</TYPE>
        <VERSION>5.0(3)N2(4.02b)</VERSION>
      </FIRMWARES>
      <INFO>
        <COMMENTS>Cisco NX-OS(tm) ucs, Software (ucs-6100-k9-system), Version 5.0(3)N2(4.02b), RELEASE SOFTWARE Copyright (c) 2002-2013 by Cisco Systems, Inc.   Compiled 1/16/2019 18:00:00</COMMENTS>
        <CONTACT>noc@glpi-project.org</CONTACT>
        <CPU>4</CPU>
        <FIRMWARE>5.0(3)N2(4.02b)</FIRMWARE>
        <ID>0</ID>
        <LOCATION>France &gt; Paris</LOCATION>
        <MAC>8c:60:4f:8d:ae:fc</MAC>
        <MANUFACTURER>Cisco</MANUFACTURER>
        <MODEL>UCS 6248UP 48-Port</MODEL>
        <NAME>ucs6248up-cluster-pa3-B</NAME>
        <SERIAL>SSI1912014B</SERIAL>
        <TYPE>NETWORKING</TYPE>
        <UPTIME>482 days, 05:42:18.50</UPTIME>
        <IPS>
           <IP>127.0.0.1</IP>
           <IP>10.2.5.10</IP>
           <IP>192.168.12.5</IP>
        </IPS>
      </INFO>
    </DEVICE>
    <MODULEVERSION>4.1</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>";

        $this->doInventory($xml_source, true);

        //check created networkequipment
        $neteq = new \NetworkEquipment();
        $this->assertTrue($neteq->getFromDBByCrit(['serial' => 'SSI1912014B']));

        $locations = $DB->request(['FROM' => 'glpi_locations', 'ORDER' => 'id DESC']);
        $this->assertCount($count_locations + 2, $locations);

        $new_location = $locations->current();
        $this->assertSame('Paris', $new_location['name']);
        $this->assertGreaterThan(0, $new_location['locations_id']);

        $locations->next();
        $parent_location = $locations->current();
        $this->assertSame('France', $parent_location['name']);
        $this->assertSame($new_location['locations_id'], $parent_location['id']);
    }

    public function testPartialUser()
    {
        $json_str = <<<JSON
{
    "action": "inventory",
    "content": {
        "bios": {
            "bdate": "2019-09-09",
            "bmanufacturer": "American Megatrends Inc.",
            "bversion": "1201",
            "mmanufacturer": "ASUSTeK COMPUTER INC.",
            "mmodel": "PRIME X570-P",
            "msn": "190653777602969",
            "skunumber": "SKU"
        },
        "hardware": {
            "chassis_type": "Desktop",
            "datelastloggeduser": "Mon Jun 10 09:06",
            "defaultgateway": "192.168.0.254",
            "lastloggeduser": "guillaume",
            "memory": 31990,
            "name": "pc_with_user",
            "swap": 16143,
            "uuid": "ea38cc5b-92eb-7777-ec5e-04d9f521c6e3",
            "vmsystem": "Physical"
        },
        "users": [
            {
                "login": "guillaume"
            }
        ],
        "versionclient": "GLPI-Agent_v1.10-dev"
    },
    "deviceid": "test-2021-11-30-09-57-34",
    "itemtype": "Computer"
}
JSON;
        $json = json_decode($json_str);

        //initial import
        $this->doInventory($json);

        $computer = new \Computer();
        $this->assertTrue($computer->getFromDBByCrit(['name' => 'pc_with_user']));
        $this->assertSame('guillaume', $computer->fields['contact']);

        //change user, and redo inventory
        $json = json_decode($json_str);
        $newuser = 'john';
        $json->content->hardware->lastloggeduser = $newuser;
        $json->content->users[0]->login = $newuser;

        $this->doInventory($json);
        $this->assertTrue($computer->getFromDBByCrit(['name' => 'pc_with_user']));
        $this->assertSame($newuser, $computer->fields['contact']);

        //make partial, change user, and redo inventory
        $json = json_decode($json_str);
        $newuser = 'partialized';
        $json->content->hardware->lastloggeduser = $newuser;
        $json->content->users[0]->login = $newuser;
        $json->partial = true;

        $this->doInventory($json);
        $this->assertTrue($computer->getFromDBByCrit(['name' => 'pc_with_user']));
        $this->assertSame($newuser, $computer->fields['contact']);
    }

    public function testVPNDownToUpPartial()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $json_str = <<<JSON
{
   "action": "inventory",
   "content": {
      "hardware": {
         "name": "pc_with_vpn",
         "uuid": "32EED9C2-204C-42A1-A97E-A6EF2CE44B4F"
      },
      "networks": [
         {
            "description": "Fortinet SSL VPN Virtual Ethernet Adapter",
            "speed": "100000",
            "status": "down",
            "type": "ethernet",
            "virtualdev": true
         }
      ],
      "versionclient": "GLPI-Inventory_v1.11"
   },
   "deviceid": "WinDev2404Eval-2024-10-14-15-28-37",
   "itemtype": "Computer"
}
JSON;
        $json = json_decode($json_str);

        //initial import
        $this->doInventory($json);

        $computer = new \Computer();
        $this->assertTrue($computer->getFromDBByCrit(['name' => 'pc_with_vpn']));

        $nports = $DB->request(
            [
                'FROM' => 'glpi_networkports',
                'WHERE' => [
                    'itemtype' => get_class($computer),
                    'items_id' => $computer->getID()
                ]
            ]
        );
        $this->assertCount(1, $nports);
        $nport_ref = $nports->current();
        $this->assertSame('2', $nport_ref['ifinternalstatus']);
        $netname = new \NetworkName();
        $this->assertCount(0, $netname->find());

        //make partial, change vpn status, and redo inventory
        $json = json_decode($json_str);
        $json->partial = true;
        $vpn = $json->content->networks[0];
        $vpn->ipaddress = '172.27.45.21';
        $vpn->ipmask = '255.255.255.255';
        $vpn->ipsubnet = '172.27.45.21';
        $vpn->mac = '00:09:0f:aa:00:01';
        $vpn->status = 'up';
        $json->content->networks[0] = $vpn;

        $this->doInventory($json);
        $this->assertTrue($computer->getFromDBByCrit(['name' => 'pc_with_vpn']));

        $nports = $DB->request(
            [
                'FROM' => 'glpi_networkports',
                'WHERE' => [
                    'itemtype' => get_class($computer),
                    'items_id' => $computer->getID()
                ]
            ]
        );
        $this->assertCount(1, $nports);

        $nport = $nports->current();
        $this->assertNotEquals($nport_ref['id'], $nport['id']);
        $this->assertSame('1', $nport['ifinternalstatus']);
        $this->assertSame('00:09:0f:aa:00:01', $nport['mac']);

        $netname = new \NetworkName();
        $this->assertTrue(
            $netname->getFromDBByCrit([
                'itemtype' => \NetworkPort::getType(),
                'items_id' => $nport['id']
            ])
        );
        $ip = new \IPAddress();
        $this->assertTrue(
            $ip->getFromDBByCrit([
                'itemtype' => $netname::getType(),
                'items_id' => $netname->getID()
            ])
        );
        $this->assertSame(4, $ip->fields['version']);
        $this->assertSame('172.27.45.21', $ip->fields['name']);

        //make partial without any ports, and redo inventory
        $json = json_decode($json_str);
        $json->partial = true;
        unset($json->content->networks);

        $this->doInventory($json);
        $this->assertTrue($computer->getFromDBByCrit(['name' => 'pc_with_vpn']));

        $nports = $DB->request(
            [
                'FROM' => 'glpi_networkports',
                'WHERE' => [
                    'itemtype' => get_class($computer),
                    'items_id' => $computer->getID()
                ]
            ]
        );
        $this->assertCount(1, $nports);

        //change vpn status, and redo inventory
        $json = json_decode($json_str);
        $vpn = $json->content->networks[0];
        $vpn->status = 'up';
        $vpn->ipaddress = '172.27.45.20';
        $vpn->ipmask = '255.255.255.255';
        $vpn->ipsubnet = '172.27.45.20';
        $vpn->mac = '00:09:0f:aa:00:01';
        $json->content->networks[0] = $vpn;
        $this->doInventory($json);
        $this->assertTrue($computer->getFromDBByCrit(['name' => 'pc_with_vpn']));

        $nports = $DB->request(
            [
                'FROM' => 'glpi_networkports',
                'WHERE' => [
                    'itemtype' => get_class($computer),
                    'items_id' => $computer->getID()
                ]
            ]
        );
        $this->assertCount(1, $nports);
        $nport = $nports->current();
        $this->assertNotEquals($nport_ref['id'], $nport['id']);
        $this->assertSame('1', $nport['ifinternalstatus']);
        $this->assertSame('00:09:0f:aa:00:01', $nport['mac']);

        $netname = new \NetworkName();
        $this->assertTrue(
            $netname->getFromDBByCrit([
                'itemtype' => \NetworkPort::getType(),
                'items_id' => $nport['id']
            ])
        );
        $ip = new \IPAddress();
        $this->assertTrue(
            $ip->getFromDBByCrit([
                'itemtype' => $netname::getType(),
                'items_id' => $netname->getID()
            ])
        );
        $this->assertSame(4, $ip->fields['version']);
        $this->assertSame('172.27.45.20', $ip->fields['name']);

        //without any ports, and redo inventory
        $json = json_decode($json_str);
        unset($json->content->networks);

        $this->doInventory($json);
        $this->assertTrue($computer->getFromDBByCrit(['name' => 'pc_with_vpn']));

        $nports = $DB->request(
            [
                'FROM' => 'glpi_networkports',
                'WHERE' => [
                    'itemtype' => get_class($computer),
                    'items_id' => $computer->getID()
                ]
            ]
        );
        $this->assertCount(0, $nports);
    }

    public function testChangeIP()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $json_str = <<<JSON
{
   "action": "inventory",
   "content": {
      "hardware": {
         "name": "pc_with_vpn",
         "uuid": "32EED9C2-204C-42A1-A97E-A6EF2CE44B4F"
      },
      "networks": [
         {
            "description": "Fortinet SSL VPN Virtual Ethernet Adapter",
            "speed": "100000",
            "status": "up",
            "type": "ethernet",
            "virtualdev": true,
            "ipaddress": "172.27.45.21",
            "ipmask": "255.255.255.255",
            "ipsubnet": "172.27.45.21",
            "mac": "00:09:0f:aa:00:01"
         }
      ],
      "versionclient": "GLPI-Inventory_v1.11"
   },
   "deviceid": "WinDev2404Eval-2024-10-14-15-28-37",
   "itemtype": "Computer"
}
JSON;
        $json = json_decode($json_str);
        $computer = new \Computer();

        //initial import
        $this->doInventory($json);
        $this->assertTrue($computer->getFromDBByCrit(['name' => 'pc_with_vpn']));

        $nports = $DB->request(
            [
                'FROM' => 'glpi_networkports',
                'WHERE' => [
                    'itemtype' => get_class($computer),
                    'items_id' => $computer->getID()
                ]
            ]
        );
        $this->assertCount(1, $nports);

        $nport = $nports->current();
        $this->assertSame('1', $nport['ifinternalstatus']);
        $this->assertSame('00:09:0f:aa:00:01', $nport['mac']);

        $netname = new \NetworkName();
        $this->assertTrue(
            $netname->getFromDBByCrit([
                'itemtype' => \NetworkPort::getType(),
                'items_id' => $nport['id']
            ])
        );
        $ip = new \IPAddress();
        $this->assertTrue(
            $ip->getFromDBByCrit([
                'itemtype' => $netname::getType(),
                'items_id' => $netname->getID()
            ])
        );
        $this->assertSame(4, $ip->fields['version']);
        $this->assertSame('172.27.45.21', $ip->fields['name']);

        //change IP, and redo inventory
        $json = json_decode($json_str);
        $vpn = $json->content->networks[0];
        $vpn->ipaddress = '172.27.45.20';
        $json->content->networks[0] = $vpn;
        $this->doInventory($json);
        $this->assertTrue($computer->getFromDBByCrit(['name' => 'pc_with_vpn']));

        $nports = $DB->request(
            [
                'FROM' => 'glpi_networkports',
                'WHERE' => [
                    'itemtype' => get_class($computer),
                    'items_id' => $computer->getID()
                ]
            ]
        );
        $this->assertCount(1, $nports);
        $nport = $nports->current();
        $this->assertSame('1', $nport['ifinternalstatus']);
        $this->assertSame('00:09:0f:aa:00:01', $nport['mac']);

        $netname = new \NetworkName();
        $this->assertTrue(
            $netname->getFromDBByCrit([
                'itemtype' => \NetworkPort::getType(),
                'items_id' => $nport['id']
            ])
        );
        $ip = new \IPAddress();
        $this->assertCount(1, $ip->find(), 'More than one IP found :/');
        $this->assertTrue(
            $ip->getFromDBByCrit([
                'itemtype' => $netname::getType(),
                'items_id' => $netname->getID()
            ])
        );
        $this->assertSame(4, $ip->fields['version']);
        $this->assertSame('172.27.45.20', $ip->fields['name']);

        //change IP, and redo inventory
        $json = json_decode($json_str);
        $vpn = $json->content->networks[0];
        $vpn->ipaddress = '172.27.45.19';
        $json->content->networks[0] = $vpn;
        $this->doInventory($json);
        $this->assertTrue($computer->getFromDBByCrit(['name' => 'pc_with_vpn']));

        $nports = $DB->request(
            [
                'FROM' => 'glpi_networkports',
                'WHERE' => [
                    'itemtype' => get_class($computer),
                    'items_id' => $computer->getID()
                ]
            ]
        );
        $this->assertCount(1, $nports);
        $nport = $nports->current();
        $this->assertSame('1', $nport['ifinternalstatus']);
        $this->assertSame('00:09:0f:aa:00:01', $nport['mac']);

        $netname = new \NetworkName();
        $this->assertTrue(
            $netname->getFromDBByCrit([
                'itemtype' => \NetworkPort::getType(),
                'items_id' => $nport['id']
            ])
        );
        $ip = new \IPAddress();
        $this->assertCount(1, $ip->find(), 'More than one IP found :/');
        $this->assertTrue(
            $ip->getFromDBByCrit([
                'itemtype' => $netname::getType(),
                'items_id' => $netname->getID()
            ])
        );
        $this->assertSame(4, $ip->fields['version']);
        $this->assertSame('172.27.45.19', $ip->fields['name']);
    }

    public function testChangeIPPartial()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $json_str = <<<JSON
{
   "action": "inventory",
   "content": {
      "hardware": {
         "name": "pc_with_vpn",
         "uuid": "32EED9C2-204C-42A1-A97E-A6EF2CE44B4F"
      },
      "networks": [
         {
            "description": "Fortinet SSL VPN Virtual Ethernet Adapter",
            "speed": "100000",
            "status": "up",
            "type": "ethernet",
            "virtualdev": true,
            "ipaddress": "172.27.45.21",
            "ipmask": "255.255.255.255",
            "ipsubnet": "172.27.45.21",
            "mac": "00:09:0f:aa:00:01"
         }
      ],
      "versionclient": "GLPI-Inventory_v1.11"
   },
   "deviceid": "WinDev2404Eval-2024-10-14-15-28-37",
   "itemtype": "Computer"
}
JSON;
        $json = json_decode($json_str);
        $computer = new \Computer();

        //initial import
        $this->doInventory($json);
        $this->assertTrue($computer->getFromDBByCrit(['name' => 'pc_with_vpn']));

        $nports = $DB->request(
            [
                'FROM' => 'glpi_networkports',
                'WHERE' => [
                    'itemtype' => get_class($computer),
                    'items_id' => $computer->getID()
                ]
            ]
        );
        $this->assertCount(1, $nports);

        $nport = $nports->current();
        $this->assertSame('1', $nport['ifinternalstatus']);
        $this->assertSame('00:09:0f:aa:00:01', $nport['mac']);

        $netname = new \NetworkName();
        $this->assertTrue(
            $netname->getFromDBByCrit([
                'itemtype' => \NetworkPort::getType(),
                'items_id' => $nport['id']
            ])
        );
        $ip = new \IPAddress();
        $this->assertTrue(
            $ip->getFromDBByCrit([
                'itemtype' => $netname::getType(),
                'items_id' => $netname->getID()
            ])
        );
        $this->assertSame(4, $ip->fields['version']);
        $this->assertSame('172.27.45.21', $ip->fields['name']);

        //make partial, change IP, and redo inventory
        $json = json_decode($json_str);
        $json->partial = true;
        $vpn = $json->content->networks[0];
        $vpn->ipaddress = '172.27.45.20';
        $json->content->networks[0] = $vpn;
        $this->doInventory($json);
        $this->assertTrue($computer->getFromDBByCrit(['name' => 'pc_with_vpn']));

        $nports = $DB->request(
            [
                'FROM' => 'glpi_networkports',
                'WHERE' => [
                    'itemtype' => get_class($computer),
                    'items_id' => $computer->getID()
                ]
            ]
        );
        $this->assertCount(1, $nports);
        $nport = $nports->current();
        $this->assertSame('1', $nport['ifinternalstatus']);
        $this->assertSame('00:09:0f:aa:00:01', $nport['mac']);

        $netname = new \NetworkName();
        $this->assertTrue(
            $netname->getFromDBByCrit([
                'itemtype' => \NetworkPort::getType(),
                'items_id' => $nport['id']
            ])
        );
        $ip = new \IPAddress();
        $this->assertCount(1, $ip->find(), 'More than one IP found :/');
        $this->assertTrue(
            $ip->getFromDBByCrit([
                'itemtype' => $netname::getType(),
                'items_id' => $netname->getID()
            ])
        );
        $this->assertSame(4, $ip->fields['version']);
        $this->assertSame('172.27.45.20', $ip->fields['name']);

        //make partial, change IP, and redo inventory
        $json = json_decode($json_str);
        $json->partial = true;
        $vpn = $json->content->networks[0];
        $vpn->ipaddress = '172.27.45.19';
        $json->content->networks[0] = $vpn;
        $this->doInventory($json);
        $this->assertTrue($computer->getFromDBByCrit(['name' => 'pc_with_vpn']));

        $nports = $DB->request(
            [
                'FROM' => 'glpi_networkports',
                'WHERE' => [
                    'itemtype' => get_class($computer),
                    'items_id' => $computer->getID()
                ]
            ]
        );
        $this->assertCount(1, $nports);
        $nport = $nports->current();
        $this->assertSame('1', $nport['ifinternalstatus']);
        $this->assertSame('00:09:0f:aa:00:01', $nport['mac']);

        $netname = new \NetworkName();
        $this->assertTrue(
            $netname->getFromDBByCrit([
                'itemtype' => \NetworkPort::getType(),
                'items_id' => $nport['id']
            ])
        );
        $ip = new \IPAddress();
        $this->assertCount(1, $ip->find(), 'More than one IP found :/');
        $this->assertTrue(
            $ip->getFromDBByCrit([
                'itemtype' => $netname::getType(),
                'items_id' => $netname->getID()
            ])
        );
        $this->assertSame(4, $ip->fields['version']);
        $this->assertSame('172.27.45.19', $ip->fields['name']);
    }

    public function testRuleRecursivityYes(): void
    {
        $this->login();

        $entity_id = getItemByTypeName('Entity', '_test_child_2', true);

        $rule = new \Rule();
        $input = [
            'is_active' => 1,
            'name'      => __METHOD__,
            'match'     => 'AND',
            'sub_type'  => 'RuleImportEntity',
            'ranking'   => 1
        ];
        $rule1_id = $rule->add($input);
        $this->assertGreaterThan(0, $rule1_id);

        // Add criteria
        $rulecriteria = new \RuleCriteria();
        $input = [
            'rules_id'  => $rule1_id,
            'criteria'  => "name",
            'pattern'   => "/.*/",
            'condition' => \RuleImportEntity::REGEX_MATCH
        ];
        $this->assertGreaterThan(0, $rulecriteria->add($input));

        // Add action
        $ruleaction = new \RuleAction();
        $input = [
            'rules_id'    => $rule1_id,
            'action_type' => 'assign',
            'field'       => 'entities_id',
            'value'       => $entity_id,
        ];
        $this->assertGreaterThan(0, $ruleaction->add($input));

        $input = [
            'rules_id'    => $rule1_id,
            'action_type' => 'assign',
            'field'       => 'is_recursive',
            'value'       => 1
        ];
        $this->assertGreaterThan(0, $ruleaction->add($input));

        $files = [
            'computer_1.json',
            'networkequipment_1.json',
            'phone_1.json',
            'printer_1.json',
        ];

        foreach ($files as $file) {
            //run inventory
            $json = json_decode(file_get_contents(self::INV_FIXTURES . $file));
            $inventory = $this->doInventory($json);
            $assets = $inventory->getAssets();

            foreach ($assets as $assettype) {
                foreach ($assettype as $asset) {
                    $this->assertSame($entity_id, $asset->getEntity());
                    if (
                        $asset->maybeRecursive()
                        && !($asset instanceof \Glpi\Inventory\Asset\Software)
                    ) {
                        $this->assertTrue($asset->isRecursive());
                    }
                }
            }
        }
    }

    public function testRuleRecursivityNo(): void
    {
        $this->login();

        $rule = new \Rule();
        $input = [
            'is_active' => 1,
            'name'      => __METHOD__,
            'match'     => 'AND',
            'sub_type'  => 'RuleImportEntity',
            'ranking'   => 1
        ];
        $rule1_id = $rule->add($input);
        $this->assertGreaterThan(0, $rule1_id);

        // Add criteria
        $rulecriteria = new \RuleCriteria();
        $input = [
            'rules_id'  => $rule1_id,
            'criteria'  => "name",
            'pattern'   => "/.*/",
            'condition' => \RuleImportEntity::REGEX_MATCH
        ];
        $this->assertGreaterThan(0, $rulecriteria->add($input));

        // Add action
        $ruleaction = new \RuleAction();
        $input = [
            'rules_id'    => $rule1_id,
            'action_type' => 'assign',
            'field'       => 'entities_id',
            'value'       => getItemByTypeName('Entity', '_test_child_2', true),
        ];
        $this->assertGreaterThan(0, $ruleaction->add($input));

        $input = [
            'rules_id'    => $rule1_id,
            'action_type' => 'assign',
            'field'       => 'is_recursive',
            'value'       => 0
        ];
        $this->assertGreaterThan(0, $ruleaction->add($input));

        $files = [
            'computer_1.json',
            'networkequipment_1.json',
            'phone_1.json',
            'printer_1.json',
        ];

        foreach ($files as $file) {
            //run inventory
            $json = json_decode(file_get_contents(self::INV_FIXTURES . $file));
            $inventory = $this->doInventory($json);
            $assets = $inventory->getAssets();

            foreach ($assets as $assettype) {
                foreach ($assettype as $asset) {
                    if ($asset->maybeRecursive()) {
                        $this->assertFalse($asset->isRecursive());
                    }
                }
            }
        }
    }
}
