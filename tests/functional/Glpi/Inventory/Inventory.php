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

class Inventory extends InventoryTestCase
{
    private function checkComputer1($computers_id)
    {
        global $DB;

        //get computer models, manufacturer, ...
        $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->current();
        $this->array($autoupdatesystems);
        $autoupdatesystems_id = $autoupdatesystems['id'];

        $cmodels = $DB->request(['FROM' => \ComputerModel::getTable(), 'WHERE' => ['name' => 'XPS 13 9350']])->current();
        $this->array($cmodels);
        $computermodels_id = $cmodels['id'];

        $ctypes = $DB->request(['FROM' => \ComputerType::getTable(), 'WHERE' => ['name' => 'Laptop']])->current();
        $this->array($ctypes);
        $computertypes_id = $ctypes['id'];

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Dell Inc.']])->current();
        $this->array($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $this->integer($computers_id)->isGreaterThan(0);
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($computers_id))->isTrue();

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
        $this->array($computer->fields)->isIdenticalTo($expected);

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
        $this->array($record)->isIdenticalTo($expected);

       //remote management
        $mgmt = new \Item_RemoteManagement();
        $iterator = $mgmt->getFromItem($computer);
        $this->integer(count($iterator))->isIdenticalTo(1);
        $remote = $iterator->current();
        unset($remote['id']);
        $this->array($remote)->isIdenticalTo([
            'itemtype' => $computer->getType(),
            'items_id' => $computer->fields['id'],
            'remoteid' => '123456789',
            'type' => 'teamviewer',
            'is_dynamic' => 1,
            'is_deleted' => 0
        ]);

        //connections
        $iterator = \Computer_Item::getTypeItems($computers_id, 'Monitor');
        $this->integer(count($iterator))->isIdenticalTo(1);
        $monitor_link = $iterator->current();
        unset($monitor_link['date_mod']);
        unset($monitor_link['date_creation']);

        $mmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Sharp Corporation']])->current();
        $this->array($mmanuf);
        $manufacturers_id = $mmanuf['id'];

        $mmodel = $DB->request(['FROM' => \MonitorModel::getTable(), 'WHERE' => ['name' => 'DJCP6']])->current();
        $this->array($mmodel);
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
        $this->array($monitor_link)->isIdenticalTo($expected);

        $monitor = new \Monitor();
        $this->boolean($monitor->getFromDB($monitor_link['id']))->isTrue();
        $this->boolean((bool)$monitor->fields['is_dynamic'])->isTrue();
        $this->string($monitor->fields['name'])->isIdenticalTo('DJCP6');

        //check network ports
        $iterator = $DB->request([
            'FROM'   => \NetworkPort::getTable(),
            'WHERE'  => [
                'items_id'           => $computers_id,
                'itemtype'           => 'Computer',
            ],
        ]);
        $this->integer(count($iterator))->isIdenticalTo(5);

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
            $this->boolean($netport->getFromDB($ports_id))->isTrue();
            $instantiation = $netport->getInstantiation();
            if ($port['instantiation_type'] === null) {
                $this->boolean($instantiation)->isFalse();
            } else {
                $this->object($instantiation)->isInstanceOf($port['instantiation_type']);
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

            $this->array($port)->isEqualTo($expected);
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

            $this->integer(count($ip_iterator))->isIdenticalTo(count($ips[$port['name']] ?? []));
            if (isset($ips[$port['name']])) {
                //FIXME: missing all ipv6 :(
                $ip = $ip_iterator->current();
                $this->integer((int)$ip['version'])->isIdenticalTo(4);
                $this->string($ip['name'])->isIdenticalTo($ips[$port['name']]['v4']);
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
            $this->integer(count($components[$type]))->isIdenticalTo(
                $count,
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
            $this->array($component)->isIdenticalTo($expected);
        }

        //check printer
        $iterator = \Computer_Item::getTypeItems($computers_id, 'Printer');
        $this->integer(count($iterator))->isIdenticalTo(1);
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
        $this->array($printer_link)->isIdenticalTo($expected);

        $printer = new \Printer();
        $this->boolean($printer->getFromDB($printer_link['id']))->isTrue();
        $this->boolean((bool)$printer->fields['is_dynamic'])->isTrue();
        $this->string($printer->fields['name'])->isIdenticalTo('Officejet_Pro_8600_34AF9E_');

        return $computer;
    }

    private function checkComputer1Volumes(\Computer $computer, array $freesizes = [])
    {
        $idisks = new \Item_Disk();
        $iterator = $idisks->getFromItem($computer);
        $this->integer(count($iterator))->isIdenticalTo(6);

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

            $this->array($volume)->isEqualTo($expected);
            ++$i;
        }
    }

    private function checkComputer1Softwares(\Computer $computer, array $versions = [])
    {
        $isoft = new \Item_SoftwareVersion();
        $iterator = $isoft->getFromItem($computer);
        $this->integer(count($iterator))->isIdenticalTo(7);

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
            $this->array([
                'softname'     => $soft['softname'],
                'version'      => $soft['version'],
                'dateinstall'  => $soft['dateinstall']
            ])->isEqualTo($expected);
            ++$i;
        }
    }

    private function checkComputer1Batteries(\Computer $computer, array $capacities = [])
    {
        global $DB;

        $link        = getItemForItemtype(\Item_DeviceBattery::class);
        $iterator = $DB->request($link->getTableGroupCriteria($computer));
        $this->integer(count($iterator))->isIdenticalTo(1);

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

        $this->array($battery)->isIdenticalTo($expected);
    }

    public function testImportComputer()
    {
        global $DB;

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));

        $inventory = $this->doInventory($json);

       //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->array($metadata)->hasSize(7)
            ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
            ->string['version']->isIdenticalTo('FusionInventory-Agent_v2.5.2-1.fc31')
            ->string['itemtype']->isIdenticalTo('Computer')
            ->variable['port']->isIdenticalTo(null)
            ->string['tag']->isIdenticalTo('000005');
        $this->array($metadata['provider'])->hasSize(10);

        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
            ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
            ->string['name']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
            ->string['version']->isIdenticalTo('2.5.2-1.fc31')
            ->string['itemtype']->isIdenticalTo('Computer')
            ->string['tag']->isIdenticalTo('000005')
            ->integer['agenttypes_id']->isIdenticalTo($agenttype['id'])
            ->integer['items_id']->isGreaterThan(0);

        //check created computer
        $computer = $this->checkComputer1($agent['items_id']);
        $this->checkComputer1Volumes($computer);
        $this->checkComputer1Softwares($computer);
        $this->checkComputer1Batteries($computer);

        //check matchedlogs
        $mlogs = new \RuleMatchedLog();
        $found = $mlogs->find();
        $this->array($found)->hasSize(3);

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
        $this->integer(count($iterator))->isIdenticalTo(1);
        $this->string($iterator->current()['name'])->isIdenticalTo('Monitor import (by serial)');
        $this->string($iterator->current()['method'])->isIdenticalTo(\Glpi\Inventory\Request::INVENT_QUERY);

        $printer_criteria = $criteria;
        $printer_criteria['WHERE'] = ['itemtype' => \Printer::getType()];
        $iterator = $DB->request($printer_criteria);
        $this->integer(count($iterator))->isIdenticalTo(1);
        $this->string($iterator->current()['name'])->isIdenticalTo('Printer import (by serial)');
        $this->string($iterator->current()['method'])->isIdenticalTo(\Glpi\Inventory\Request::INVENT_QUERY);

        $computer_criteria = $criteria;
        $computer_criteria['WHERE'] = ['itemtype' => \Computer::getType()];
        $iterator = $DB->request($computer_criteria);
        $this->integer(count($iterator))->isIdenticalTo(1);
        $this->string($iterator->current()['name'])->isIdenticalTo('Computer import (by serial + uuid)');
        $this->string($iterator->current()['method'])->isIdenticalTo(\Glpi\Inventory\Request::INVENT_QUERY);
    }

    public function testUpdateComputer()
    {
        global $DB;

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_3.json'));

        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->array($metadata)->hasSize(7)
            ->string['deviceid']->isIdenticalTo('LF014-2017-02-20-12-19-56')
            ->string['version']->isIdenticalTo('FusionInventory-Agent_v2.3.19')
            ->string['itemtype']->isIdenticalTo('Computer')
            ->variable['port']->isIdenticalTo(null)
            ->string['tag']->isIdenticalTo('000005');
        $this->array($metadata['provider'])->hasSize(9);

        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
            ->string['deviceid']->isIdenticalTo('LF014-2017-02-20-12-19-56')
            ->string['name']->isIdenticalTo('LF014-2017-02-20-12-19-56')
            ->string['version']->isIdenticalTo('2.3.19')
            ->string['itemtype']->isIdenticalTo('Computer')
            ->string['tag']->isIdenticalTo('000005')
            ->integer['agenttypes_id']->isIdenticalTo($agenttype['id']);

        //check matchedlogs
        $mlogs = new \RuleMatchedLog();
        $mrules_found = $mlogs->find();
        $this->array($mrules_found)->hasSize(2);

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
        $this->integer(count($iterator))->isIdenticalTo(1);
        $this->string($iterator->current()['name'])->isIdenticalTo('Monitor import (by serial)');
        $this->string($iterator->current()['method'])->isIdenticalTo(\Glpi\Inventory\Request::INVENT_QUERY);

        $computer_criteria = $mrules_criteria;
        $computer_criteria['WHERE'] = ['itemtype' => \Computer::getType()];
        $iterator = $DB->request($computer_criteria);
        $this->integer(count($iterator))->isIdenticalTo(1);
        $this->string($iterator->current()['name'])->isIdenticalTo('Computer import (by serial + uuid)');
        $this->integer($iterator->current()['items_id'])->isIdenticalTo($agent['items_id']);
        $this->string($iterator->current()['method'])->isIdenticalTo(\Glpi\Inventory\Request::INVENT_QUERY);

        //get computer models, manufacturer, ...
        $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->current();
        $this->array($autoupdatesystems);
        $autoupdatesystems_id = $autoupdatesystems['id'];

        $cmodels = $DB->request(['FROM' => \ComputerModel::getTable(), 'WHERE' => ['name' => 'PORTEGE Z30-A']])->current();
        $this->array($cmodels);
        $computermodels_id = $cmodels['id'];

        $ctypes = $DB->request(['FROM' => \ComputerType::getTable(), 'WHERE' => ['name' => 'Notebook']])->current();
        $this->array($ctypes);
        $computertypes_id = $ctypes['id'];

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Toshiba']])->current();
        $this->array($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        //check created computer
        $computers_id = $agent['items_id'];
        $this->integer($computers_id)->isGreaterThan(0);
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($computers_id))->isTrue();

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
        $this->array($computer->fields)->isIdenticalTo($expected);

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
        $this->array($record)->isIdenticalTo($expected);

        //volumes
        $idisks = new \Item_Disk();
        $iterator = $idisks->getFromItem($computer);
        $this->integer(count($iterator))->isIdenticalTo(3);

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
            $this->array($volume)->isEqualTo($expected);
            ++$i;
        }

        //connections
        $iterator = \Computer_Item::getTypeItems($computers_id, 'Monitor');
        $this->integer(count($iterator))->isIdenticalTo(1);

        //check network ports
        $iterator = $DB->request([
            'FROM'   => \NetworkPort::getTable(),
            'WHERE'  => [
                'items_id'           => $computers_id,
                'itemtype'           => 'Computer',
            ],
        ]);
        $this->integer(count($iterator))->isIdenticalTo(4);

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
            $this->integer(count($components[$type]))->isIdenticalTo($count, "$type " . count($components[$type]));
        }

        //check memory
        $this->array($components['Item_DeviceMemory'])->hasSize(2);
        $mem_component1 = array_pop($components['Item_DeviceMemory']);
        $mem_component2 = array_pop($components['Item_DeviceMemory']);
        $this->integer($mem_component1['devicememories_id'])->isGreaterThan(0);
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
        $this->array($mem_component1)->isIdenticalTo($expected_mem_component);
        $expected_mem_component['busID'] = "1";
        $this->array($mem_component2)->isIdenticalTo($expected_mem_component);

        //software
        $isoft = new \Item_SoftwareVersion();
        $iterator = $isoft->getFromItem($computer);
        $this->integer(count($iterator))->isIdenticalTo(3034);

        //computer has been created, check logs.
        //check for expected logs
        $nblogsnow = countElementsInTable(\Log::getTable());
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'LIMIT' => $nblogsnow,
            'OFFSET' => $this->nblogs,
        ]);
        $this->integer(count($logs))->isIdenticalTo(0);

        //fake computer update (nothing has changed)
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_3.json'));
        $this->doInventory($json);

        $this->boolean($computer->getFromDB($computers_id))->isTrue();

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
        $this->array($computer->fields)->isIdenticalTo($expected);

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
        $this->array($record)->isIdenticalTo($expected);

        //volumes
        $idisks = new \Item_Disk();
        $iterator = $idisks->getFromItem($computer);
        $this->integer(count($iterator))->isIdenticalTo(3);

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
            $this->array($volume)->isEqualTo($expected);
            ++$i;
        }

        //connections
        $iterator = \Computer_Item::getTypeItems($computers_id, 'Monitor');
        $this->integer(count($iterator))->isIdenticalTo(1);

        //check network ports
        $iterator = $DB->request([
            'FROM'   => \NetworkPort::getTable(),
            'WHERE'  => [
                'items_id'           => $computers_id,
                'itemtype'           => 'Computer',
            ],
        ]);
        $this->integer(count($iterator))->isIdenticalTo(4);

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
            $this->integer(count($components[$type]))->isIdenticalTo($count, "$type " . count($components[$type]));
        }

        //check memory
        $this->array($components['Item_DeviceMemory'])->hasSize(2);
        $mem_component1 = array_pop($components['Item_DeviceMemory']);
        $mem_component2 = array_pop($components['Item_DeviceMemory']);
        $this->integer($mem_component1['devicememories_id'])->isGreaterThan(0);
        $expected_mem_component['busID'] = "2";
        $this->array($mem_component1)->isIdenticalTo($expected_mem_component);
        $expected_mem_component['busID'] = "1";
        $this->array($mem_component2)->isIdenticalTo($expected_mem_component);

        //software
        $isoft = new \Item_SoftwareVersion();
        $iterator = $isoft->getFromItem($computer);
        $this->integer(count($iterator))->isIdenticalTo(3034);

        //check for expected logs
        $nblogsnow = countElementsInTable(\Log::getTable());
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'LIMIT' => $nblogsnow,
            'OFFSET' => $this->nblogs,
        ]);
        $this->integer(count($logs))->isIdenticalTo(0);

        //real computer update
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_3_updated.json'));

        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->array($metadata)->hasSize(7)
            ->string['deviceid']->isIdenticalTo('LF014-2017-02-20-12-19-56')
            ->string['version']->isIdenticalTo('FusionInventory-Agent_v2.3.20')
            ->string['itemtype']->isIdenticalTo('Computer')
            ->string['tag']->isIdenticalTo('000005')
            ->variable['port']->isIdenticalTo(null)
            ->string['action']->isIdenticalTo('inventory');
        ;
        $this->array($metadata['provider'])->hasSize(9);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
            ->string['deviceid']->isIdenticalTo('LF014-2017-02-20-12-19-56')
            ->string['name']->isIdenticalTo('LF014-2017-02-20-12-19-56')
            ->string['version']->isIdenticalTo('2.3.20')
            ->string['itemtype']->isIdenticalTo('Computer')
            ->integer['items_id']->isIdenticalTo($computers_id)
            ->string['tag']->isIdenticalTo('000005')
            ->integer['agenttypes_id']->isIdenticalTo($agenttype['id']);

        $computer = new \Computer();
        $this->boolean($computer->getFromDB($computers_id))->isTrue();

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
        $this->array($computer->fields)->isIdenticalTo($expected);

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
        $this->array($record)->isIdenticalTo($expected);

        //volumes
        $idisks = new \Item_Disk();
        $iterator = $idisks->getFromItem($computer);
        $this->integer(count($iterator))->isIdenticalTo(3);

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
            $this->array($volume)->isEqualTo($expected);
            ++$i;
        }

        //connections
        $iterator = \Computer_Item::getTypeItems($computers_id, 'Monitor');
        $this->integer(count($iterator))->isIdenticalTo(0);

        //check network ports
        $iterator = $DB->request([
            'FROM'   => \NetworkPort::getTable(),
            'WHERE'  => [
                'items_id'           => $computers_id,
                'itemtype'           => 'Computer',
            ],
        ]);
        $this->integer(count($iterator))->isIdenticalTo(7);

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
            $this->integer(count($components[$type]))->isIdenticalTo($count, "$type " . count($components[$type]));
        }

        //check memory
        $this->array($components['Item_DeviceMemory'])->hasSize(2);
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
        $this->array($mem_component1)->isIdenticalTo($expected_mem_component);
        $expected_mem_component['busID'] = "1";
        $this->array($mem_component2)->isIdenticalTo($expected_mem_component);

        //software
        $isoft = new \Item_SoftwareVersion();
        $iterator = $isoft->getFromItem($computer);
        $this->integer(count($iterator))->isIdenticalTo(3185);

        //check for expected logs after update
        $logs = $DB->request([
            'FROM' => \Log::getTable(),
            'LIMIT' => countElementsInTable(\Log::getTable()),
            'OFFSET' => $nblogsnow,
        ]);

        $this->integer(count($logs))->isIdenticalTo(4418);

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
            $this->string($row['user_name'])->isIdenticalTo('inventory', print_r($row, true));
            if (!isset($types_count[$row['linked_action']])) {
                $types_count[$row['linked_action']] = 0;
            }
            ++$types_count[$row['linked_action']];
        }

        ksort($types_count);
        ksort($expected_types_count);
        $this->array($types_count)->isEqualTo(
            $expected_types_count,
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
        $this->array($found)->hasSize(3);

        $monitor_criteria = $mrules_criteria;
        $monitor_criteria['WHERE'][] = ['itemtype' => \Monitor::getType()];
        $iterator = $DB->request($monitor_criteria);
        $this->integer(count($iterator))->isIdenticalTo(1);
        $this->string($iterator->current()['name'])->isIdenticalTo('Monitor update (by serial)');
        $this->string($iterator->current()['method'])->isIdenticalTo(\Glpi\Inventory\Request::INVENT_QUERY);

        $computer_criteria = $mrules_criteria;
        $computer_criteria['WHERE'][] = ['itemtype' => \Computer::getType()];
        $iterator = $DB->request($computer_criteria);

        $this->integer(count($iterator))->isIdenticalTo(2);
        foreach ($iterator as $rmlog) {
            $this->string($rmlog['name'])->isIdenticalTo('Computer update (by serial + uuid)');
            $this->integer($rmlog['items_id'])->isIdenticalTo($agent['items_id']);
            $this->string($rmlog['method'])->isIdenticalTo(\Glpi\Inventory\Request::INVENT_QUERY);
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

        $this->array($metadata)->hasSize(5)
         ->string['deviceid']->isIdenticalTo('foo')
         ->string['version']->isIdenticalTo('4.1')
         ->string['itemtype']->isIdenticalTo('NetworkEquipment')
         ->variable['port']->isIdenticalTo(null)
         ->string['action']->isIdenticalTo('netinventory');

        global $DB;
       //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
       //no agent with deviceid equals to "foo"
        $this->integer(count($agents))->isIdenticalTo(0);

       //get model, manufacturer, ...
        $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->current();
        $this->array($autoupdatesystems);
        $autoupdatesystems_id = $autoupdatesystems['id'];

        $cmodels = $DB->request(['FROM' => \NetworkEquipmentModel::getTable(), 'WHERE' => ['name' => 'UCS 6248UP 48-Port']])->current();
        $this->array($cmodels);
        $models_id = $cmodels['id'];

        $ctypes = $DB->request(['FROM' => \NetworkEquipmentType::getTable(), 'WHERE' => ['name' => 'Networking']])->current();
        $this->array($ctypes);
        $types_id = $ctypes['id'];

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Cisco']])->current();
        $this->array($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $cloc = $DB->request(['FROM' => \Location::getTable(), 'WHERE' => ['name' => 'paris.pa3']])->current();
        $this->array($cloc);
        $locations_id = $cloc['id'];

        //check created asset
        $equipments = $DB->request(['FROM' => \NetworkEquipment::getTable(), 'WHERE' => ['is_dynamic' => 1]]);
        //no agent with deviceid equals to "foo"
        $this->integer(count($equipments))->isIdenticalTo(1);
        $equipments_id = $equipments->current()['id'];

        $equipment = new \NetworkEquipment();
        $this->boolean($equipment->getFromDB($equipments_id))->isTrue();

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
        $this->array($equipment->fields)->isIdenticalTo($expected);

        //check network ports
        $expected_count = 164;
        $iterator = $DB->request([
            'FROM'   => \NetworkPort::getTable(),
            'WHERE'  => [
                'items_id'           => $equipments_id,
                'itemtype'           => 'NetworkEquipment',
            ],
        ]);
        $this->integer(count($iterator))->isIdenticalTo($expected_count);

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
            $this->boolean($netport->getFromDB($ports_id))->isTrue();
            $instantiation = $netport->getInstantiation();
            if ($port['instantiation_type'] === null) {
                $this->boolean($instantiation)->isFalse();
            } else {
                $this->object($instantiation)->isInstanceOf($port['instantiation_type']);
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

                $this->array($port)->isEqualTo($expected);
            } else {
                $this->string($port['itemtype'])->isIdenticalTo('NetworkEquipment');
                $this->integer($port['items_id'])->isIdenticalTo($equipments_id);
                $this->string($port['instantiation_type'])->isIdenticalTo('NetworkPortEthernet', print_r($port, true));
                $this->string($port['mac'])->matches('/^(?:(?:[0-9a-f]{2}[\:]{1}){5}|(?:[0-9a-f]{2}[-]{1}){5}|(?:[0-9a-f]{2}){5})[0-9a-f]{2}$/i');
                $this->integer($port['is_dynamic'])->isIdenticalTo(1);
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

            $this->integer(count($ip_iterator))->isIdenticalTo(count($ips[$port['name']] ?? []));
            if (isset($ips[$port['name']])) {
                foreach ($ip_iterator as $ip) {
                    $this->array($ips[$port['name']])->contains($ip['name']);
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
            $this->integer(count($components[$type]))->isIdenticalTo(
                $count,
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
            $this->array($component)->isIdenticalTo($expected);
        }

        //ports connections
        $connections = $DB->request(['FROM' => \NetworkPort_NetworkPort::getTable()]);
        $this->integer(count($connections))->isIdenticalTo(5);

        //unmanaged equipments
        $unmanageds = $DB->request(['FROM' => \Unmanaged::getTable()]);
        $this->integer(count($unmanageds))->isIdenticalTo(5);

        $expecteds = [
            'sw2-mgmt-eqnx' => "Cisco IOS Software, C2960 Software (C2960-LANLITEK9-M), Version 12.2(50)SE5, RELEASE SOFTWARE (fc1)
Technical Support: http://www.cisco.com/techsupport
Copyright (c) 1986-2010 by Cisco Systems, Inc.
Compiled Tue 28-Sep-10 13:44 by prod_rel_team",
            'n9k-1-pa3' => 'Cisco Nexus Operating System (NX-OS) Software, Version 7.0(3)I7(6)',
            'n9k-2-pa3' => 'Cisco Nexus Operating System (NX-OS) Software, Version 7.0(3)I7(6)',
        ];

        foreach ($unmanageds as $unmanaged) {
            $this->boolean(in_array($unmanaged['name'], array_keys($expecteds)))->isTrue($unmanaged['name']);
            $this->string($unmanaged['sysdescr'])->isIdenticalTo($expecteds[$unmanaged['name']]);
        }

        //check matchedlogs
        $mlogs = new \RuleMatchedLog();
        $found = $mlogs->find();
        $this->array($found)->hasSize(6);//1 equipment, 5 unmanageds

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
        $this->integer(count($iterator))->isIdenticalTo(1);
        foreach ($iterator as $neteq) {
            $this->string($neteq['name'])->isIdenticalTo('NetworkEquipment import (by serial)');
            $this->integer($neteq['items_id'])->isIdenticalTo($equipments_id);
            $this->string($neteq['method'])->isIdenticalTo(\Glpi\Inventory\Request::INVENT_QUERY);
        }

        $unmanaged_criteria = $mrules_criteria;
        $unmanaged_criteria['WHERE'][] = ['itemtype' => \Unmanaged::getType()];
        $iterator = $DB->request($unmanaged_criteria);
        $this->integer(count($iterator))->isIdenticalTo(5);
        foreach ($iterator as $unmanaged) {
            $this->string($unmanaged['name'])->isIdenticalTo('Global import (by ip+ifdescr)');
            $this->string($unmanaged['method'])->isIdenticalTo(\Glpi\Inventory\Request::INVENT_QUERY);
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

        $this->array($metadata)->hasSize(5)
         ->string['deviceid']->isIdenticalTo('3k-1-pa3.glpi-project.infra-2020-12-31-11-28-51')
         ->string['version']->isIdenticalTo('4.1')
         ->string['itemtype']->isIdenticalTo('NetworkEquipment')
         ->variable['port']->isIdenticalTo(null)
         ->string['action']->isIdenticalTo('netinventory');

        global $DB;
        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $this->array($inventory->getAgent()->fields)
         ->string['deviceid']->isIdenticalTo('3k-1-pa3.glpi-project.infra-2020-12-31-11-28-51')
         ->string['name']->isIdenticalTo('3k-1-pa3.glpi-project.infra-2020-12-31-11-28-51')
         //->string['version']->isIdenticalTo('')
         ->string['itemtype']->isIdenticalTo('NetworkEquipment')
         ->integer['agenttypes_id']->isIdenticalTo($agenttype['id']);

        //get model, manufacturer, ...
        $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->current();
        $this->array($autoupdatesystems);
        $autoupdatesystems_id = $autoupdatesystems['id'];

        $cmodels = $DB->request(['FROM' => \NetworkEquipmentModel::getTable(), 'WHERE' => ['name' => 'WS-C3750G-48TS-S']])->current();
        $this->array($cmodels);
        $models_id = $cmodels['id'];

        $ctypes = $DB->request(['FROM' => \NetworkEquipmentType::getTable(), 'WHERE' => ['name' => 'Networking']])->current();
        $this->array($ctypes);
        $types_id = $ctypes['id'];

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Cisco']])->current();
        $this->array($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $cloc = $DB->request(['FROM' => \Location::getTable(), 'WHERE' => ['name' => 'paris.pa3']])->current();
        $this->array($cloc);
        $locations_id = $cloc['id'];

        //check created equipments
        $expected_eq_count = 5;
        $iterator = $DB->request([
            'FROM'   => \NetworkEquipment::getTable(),
            'WHERE'  => ['is_dynamic' => 1]
        ]);
        $this->integer(count($iterator))->isIdenticalTo($expected_eq_count);

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
            $this->boolean($equipment->getFromDB($equipments_id))->isTrue();
            $expected['date_mod'] = $row['date_mod'];
            $expected['date_creation'] = $row['date_creation'];
            $stack_id = preg_replace('/.+ - (\d)/', '$1', $row['name']);
            $this->array($stacks)->hasKey($stack_id);
            $expected['name'] .= ' - ' . $stack_id;
            $expected['serial'] = $stacks[$stack_id]['serial'];
            $this->array($row)->isIdenticalTo($expected);

            //check network ports
            $expected_count = 53;
            $ports_iterator = $DB->request([
                'FROM'   => \NetworkPort::getTable(),
                'WHERE'  => [
                    'items_id'           => $equipments_id,
                    'itemtype'           => 'NetworkEquipment',
                ],
            ]);
            $this->integer(count($ports_iterator))->isIdenticalTo(
                $expected_count,
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
                $this->boolean($netport->getFromDB($ports_id))->isTrue();
                $instantiation = $netport->getInstantiation();
                if ($port['instantiation_type'] === null) {
                    $this->boolean($instantiation)->isFalse();
                } else {
                    $this->object($instantiation)->isInstanceOf($port['instantiation_type']);
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

                    $this->array($port)->isEqualTo($expected);
                } else {
                    $this->string($port['itemtype'])->isIdenticalTo('NetworkEquipment');
                    $this->integer($port['items_id'])->isIdenticalTo($equipments_id);
                    $this->string($port['instantiation_type'])->isIdenticalTo('NetworkPortEthernet', print_r($port, true));
                    $this->string($port['mac'])->matches('/^(?:(?:[0-9a-f]{2}[\:]{1}){5}|(?:[0-9a-f]{2}[-]{1}){5}|(?:[0-9a-f]{2}){5})[0-9a-f]{2}$/i');
                    $this->integer($port['is_dynamic'])->isIdenticalTo(1);
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

                $this->integer(count($ip_iterator))->isIdenticalTo(count($ips[$port['name']] ?? []));
                if (isset($ips[$port['name']])) {
                    foreach ($ip_iterator as $ip) {
                        $this->array($ips[$port['name']])->contains($ip['name']);
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
                $this->integer(count($components[$type]))->isIdenticalTo(
                    $count,
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
                $this->array($component)->isIdenticalTo($expected);
            }

            //ports connections
            $connections = $DB->request([
                'FROM'   => \NetworkPort_NetworkPort::getTable(),
                'WHERE'  => [
                    'networkports_id_1' => $all_ports_ids
                ]
            ]);

            $this->integer(count($connections))->isIdenticalTo(
                $stacks[$stack_id]['connections'],
                sprintf(
                    '%s connections found on stack %s, %s expected',
                    count($connections),
                    $stack_id,
                    $stacks[$stack_id]['connections']
                )
            );
        }

        $db_ports = $DB->request(['FROM' => \NetworkPort::getTable()]);
        $this->integer(count($db_ports))->isIdenticalTo(325);

        $db_neteq_ports = $DB->request(['FROM' => \NetworkPort::getTable(), 'WHERE' => ['itemtype' => 'NetworkEquipment']]);
        $this->integer(count($db_neteq_ports))->isIdenticalTo(265);

        $db_connections = $DB->request(['FROM' => \NetworkPort_NetworkPort::getTable()]);
        $this->integer(count($db_connections))->isIdenticalTo(26);

        $db_unmanageds = $DB->request(['FROM' => \Unmanaged::getTable()]);
        $this->integer(count($db_unmanageds))->isIdenticalTo(45);

        $db_ips = $DB->request(['FROM' => \IPAddress::getTable()]);
        $this->integer(count($db_ips))->isIdenticalTo(150);

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
        $this->integer(count($db_vlans))->isIdenticalTo(count($expected_names));

        $i = 0;
        foreach ($db_vlans as $row) {
            $this->string($row['name'])->isEqualTo($expected_names[$i]);
            ++$i;
        }

        $db_vlans_ports = $DB->request(['FROM' => \NetworkPort_Vlan::getTable()]);
        $this->integer(count($db_vlans_ports))->isIdenticalTo(219);

        $db_netnames = $DB->request(['FROM' => \NetworkName::getTable()]);
        $this->integer(count($db_netnames))->isIdenticalTo(10);

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
            $this->variable($unmanaged[$key])->isEqualTo($value);
         }
         ++$i;
       }*/

        //check matchedlogs
        $mlogs = new \RuleMatchedLog();
        $found = $mlogs->find();
        $this->array($found)->hasSize(48);

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
        $this->integer(count($iterator))->isIdenticalTo($expected_eq_count);
        foreach ($iterator as $neteq) {
            $this->string($neteq['name'])->isIdenticalTo('NetworkEquipment import (by serial)');
            $this->string($neteq['method'])->isIdenticalTo(\Glpi\Inventory\Request::INVENT_QUERY);
        }

        $unmanaged_criteria = $mrules_criteria;
        $unmanaged_criteria['WHERE'][] = ['itemtype' => \Unmanaged::getType()];
        $iterator = $DB->request($unmanaged_criteria);
        $this->integer(count($iterator))->isIdenticalTo(43);
    }

    public function testImportNetworkEquipmentMultiConnections()
    {
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'networkequipment_3.json'));

        $date_now = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date_now;
        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();

        $this->array($metadata)->hasSize(5)
         ->string['deviceid']->isIdenticalTo('HP-2530-48G-2020-12-31-11-28-51')
         ->string['version']->isIdenticalTo('2.5')
         ->string['itemtype']->isIdenticalTo('NetworkEquipment')
         ->variable['port']->isIdenticalTo(null)
         ->string['action']->isIdenticalTo('netinventory');

        global $DB;
        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $this->array($inventory->getAgent()->fields)
         ->string['deviceid']->isIdenticalTo('HP-2530-48G-2020-12-31-11-28-51')
         ->string['name']->isIdenticalTo('HP-2530-48G-2020-12-31-11-28-51')
         ->string['itemtype']->isIdenticalTo('NetworkEquipment')
         ->integer['agenttypes_id']->isIdenticalTo($agenttype['id']);

        //get model, manufacturer, ...
        $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->current();
        $this->array($autoupdatesystems);
        $autoupdatesystems_id = $autoupdatesystems['id'];

        $cmodels = $DB->request(['FROM' => \NetworkEquipmentModel::getTable(), 'WHERE' => ['name' => '2530-48G']])->current();
        $this->array($cmodels);
        $models_id = $cmodels['id'];

        $ctypes = $DB->request(['FROM' => \NetworkEquipmentType::getTable(), 'WHERE' => ['name' => 'Networking']])->current();
        $this->array($ctypes);
        $types_id = $ctypes['id'];

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Hewlett-Packard']])->current();
        $this->array($cmanuf);
        $manufacturers_id = $cmanuf['id'];
        $locations_id = 0;

        //check created equipments
        $expected_count = 1;
        $iterator = $DB->request([
            'FROM'   => \NetworkEquipment::getTable(),
            'WHERE'  => ['is_dynamic' => 1]
        ]);
        $this->integer(count($iterator))->isIdenticalTo($expected_count);

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
            $this->boolean($equipment->getFromDB($equipments_id))->isTrue();
            $expected['date_mod'] = $row['date_mod'];
            $expected['date_creation'] = $row['date_creation'];
            $this->array($row)->isIdenticalTo($expected);

            //check network ports
            $expected_count = 53;
            $ports_iterator = $DB->request([
                'FROM'   => \NetworkPort::getTable(),
                'WHERE'  => [
                    'items_id'           => $equipments_id,
                    'itemtype'           => 'NetworkEquipment',
                ],
            ]);
            $this->integer(count($ports_iterator))->isIdenticalTo(
                $expected_count,
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
                $this->boolean($netport->getFromDB($ports_id))->isTrue();
                $instantiation = $netport->getInstantiation();
                if ($port['instantiation_type'] === null) {
                    $this->boolean($instantiation)->isFalse();
                } else {
                    $this->object($instantiation)->isInstanceOf($port['instantiation_type']);
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

                    $this->array($port)->isEqualTo($expected);
                } else {
                    $this->string($port['itemtype'])->isIdenticalTo('NetworkEquipment');
                    $this->integer($port['items_id'])->isIdenticalTo($equipments_id);
                    $this->string($port['instantiation_type'])->isIdenticalTo('NetworkPortEthernet', print_r($port, true));
                    $this->string($port['mac'])->matches('/^(?:(?:[0-9a-f]{2}[\:]{1}){5}|(?:[0-9a-f]{2}[-]{1}){5}|(?:[0-9a-f]{2}){5})[0-9a-f]{2}$/i');
                    $this->integer($port['is_dynamic'])->isIdenticalTo(1);
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

                $this->integer(count($ip_iterator))->isIdenticalTo(count($ips[$port['name']] ?? []));
                if (isset($ips[$port['name']])) {
                    foreach ($ip_iterator as $ip) {
                        $this->array($ips[$port['name']])->contains($ip['name']);
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
                $this->integer(count($components[$type]))->isIdenticalTo(
                    $count,
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
                $this->array($component)->isIdenticalTo($expected);
            }

            //ports connections
            $connections = $DB->request([
                'FROM'   => \NetworkPort_NetworkPort::getTable(),
            ]);

            $this->integer(count($connections))->isIdenticalTo(63);
        }

        $connections = $DB->request(['FROM' => \NetworkPort_NetworkPort::getTable()]);
        $this->integer(count($connections))->isIdenticalTo(63);

        $unmanageds = $DB->request(['FROM' => \Unmanaged::getTable()]);
        $this->integer(count($unmanageds))->isIdenticalTo(63);

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

        $this->integer(count($expecteds))->isIdenticalTo($unmanageds->count());

        $i = 0;
        foreach ($unmanageds as $unmanaged) {
            foreach ($expecteds[$i] as $key => $value) {
                $this->variable($unmanaged[$key])->isEqualTo($value);
            }
            ++$i;
        }

        //check matchedlogs
        $mlogs = new \RuleMatchedLog();
        $found = $mlogs->find();
        $this->array($found)->hasSize(61);

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
        $this->integer(count($iterator))->isIdenticalTo(1);
        foreach ($iterator as $neteq) {
            $this->string($neteq['name'])->isIdenticalTo('NetworkEquipment import (by serial)');
            $this->integer($neteq['items_id'])->isIdenticalTo($equipments_id);
            $this->string($neteq['method'])->isIdenticalTo(\Glpi\Inventory\Request::INVENT_QUERY);
        }

        $unmanaged_criteria = $mrules_criteria;
        $unmanaged_criteria['WHERE'][] = ['itemtype' => \Unmanaged::getType()];
        $iterator = $DB->request($unmanaged_criteria);
        $this->integer(count($iterator))->isIdenticalTo(60);
    }

    public function testImportNetworkEquipmentWireless()
    {
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'networkequipment_4.json'));

        $date_now = date('Y-m-d H:i:s');
        $_SESSION["glpi_currenttime"] = $date_now;
        $inventory = $this->doInventory($json);

       //check inventory metadata
        $metadata = $inventory->getMetadata();

        $this->array($metadata)->hasSize(5)
         ->string['deviceid']->isIdenticalTo('CH-GV1-DSI-WLC-INSID-1-2020-12-31-11-28-51')
         ->string['version']->isIdenticalTo('4.1')
         ->string['itemtype']->isIdenticalTo('NetworkEquipment')
         ->variable['port']->isIdenticalTo(null)
         ->string['action']->isIdenticalTo('netinventory');

        global $DB;
       //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $this->array($inventory->getAgent()->fields)
         ->string['deviceid']->isIdenticalTo('CH-GV1-DSI-WLC-INSID-1-2020-12-31-11-28-51')
         ->string['name']->isIdenticalTo('CH-GV1-DSI-WLC-INSID-1-2020-12-31-11-28-51')
         //->string['version']->isIdenticalTo('')
         ->string['itemtype']->isIdenticalTo('NetworkEquipment')
         ->integer['agenttypes_id']->isIdenticalTo($agenttype['id']);

       //get model, manufacturer, ...
        $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->current();
        $this->array($autoupdatesystems);
        $autoupdatesystems_id = $autoupdatesystems['id'];

        $cmodels = $DB->request(['FROM' => \NetworkEquipmentModel::getTable(), 'WHERE' => ['name' => 'CT5520']])->current();
        $this->array($cmodels);
        $models_id = $cmodels['id'];

        $ctypes = $DB->request(['FROM' => \NetworkEquipmentType::getTable(), 'WHERE' => ['name' => 'Networking']])->current();
        $this->array($ctypes);
        $types_id = $ctypes['id'];

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Cisco']])->current();
        $this->array($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $cloc = $DB->request(['FROM' => \Location::getTable(), 'WHERE' => ['name' => 'MERY']])->current();
        $this->array($cloc);
        $locations_id = $cloc['id'];

        //check created equipments
        $expected_eq_count = 302;
        $iterator = $DB->request([
            'FROM'   => \NetworkEquipment::getTable(),
            'WHERE'  => ['is_dynamic' => 1]
        ]);
        $this->integer(count($iterator))->isIdenticalTo($expected_eq_count);

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
            $this->boolean($equipment->getFromDB($equipments_id))->isTrue();
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
            $this->array($row)->isIdenticalTo($expected_eq, print_r($row, true) . print_r($expected_eq, true));

           //check network ports
            $expected_count = ($first ? 4 : 1);
            $ports_iterator = $DB->request([
                'FROM'   => \NetworkPort::getTable(),
                'WHERE'  => [
                    'items_id'           => $equipments_id,
                    'itemtype'           => 'NetworkEquipment',
                ],
            ]);
            $this->integer(count($ports_iterator))->isIdenticalTo(
                $expected_count,
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
                $this->boolean($netport->getFromDB($ports_id))->isTrue();
                $instantiation = $netport->getInstantiation();
                if ($port['instantiation_type'] === null) {
                    $this->boolean($instantiation)->isFalse();
                } else {
                    $this->object($instantiation)->isInstanceOf($port['instantiation_type']);
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

                    $this->array($port)->isEqualTo($expected);
                } else {
                    $this->string($port['itemtype'])->isIdenticalTo('NetworkEquipment');
                    $this->integer($port['items_id'])->isIdenticalTo($equipments_id);
                   //$this->string($port['instantiation_type'])->isIdenticalTo('NetworkPortAggregate', print_r($port, true));
                    $this->string($port['mac'])->matches('/^(?:(?:[0-9a-f]{2}[\:]{1}){5}|(?:[0-9a-f]{2}[-]{1}){5}|(?:[0-9a-f]{2}){5})[0-9a-f]{2}$/i');
                    $this->integer($port['is_dynamic'])->isIdenticalTo(1);
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

               //$this->integer(count($ip_iterator))->isIdenticalTo(count($ips[$port['name']] ?? ['one' => 'one']));
                if ($port['mac'] == '58:ac:78:59:45:fb') {
                    foreach ($ip_iterator as $ip) {
                        $this->array($ips[$port['name']])->contains($ip['name']);
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
                $this->integer(count($components[$type]))->isIdenticalTo(
                    $count,
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
                $this->array($component)->isIdenticalTo($expected);
            }

            $first = false;
        }

        $connections = $DB->request(['FROM' => \NetworkPort_NetworkPort::getTable()]);
        $this->integer(count($connections))->isIdenticalTo(2);

        $unmanageds = $DB->request(['FROM' => \Unmanaged::getTable()]);
        $this->integer(count($unmanageds))->isIdenticalTo(2);

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
                $this->variable($unmanaged[$key])->isEqualTo($value);
            }
            ++$i;
        }

       //check matchedlogs
        $mlogs = new \RuleMatchedLog();
        $found = $mlogs->find();
        $this->array($found)->hasSize($expected_eq_count + count($unmanageds));

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
        $this->integer(count($iterator))->isIdenticalTo($expected_eq_count);
        foreach ($iterator as $neteq) {
            $this->string($neteq['name'])->isIdenticalTo('NetworkEquipment import (by serial)');
            $this->string($neteq['method'])->isIdenticalTo(\Glpi\Inventory\Request::INVENT_QUERY);
        }

        $unmanaged_criteria = $mrules_criteria;
        $unmanaged_criteria['WHERE'][] = ['itemtype' => \Unmanaged::getType()];
        $iterator = $DB->request($unmanaged_criteria);
        $this->integer(count($iterator))->isIdenticalTo(count($unmanageds));
        foreach ($iterator as $unmanaged) {
            $this->string($unmanaged['name'])->isIdenticalTo('Global import (by ip+ifdescr)');
            $this->string($unmanaged['method'])->isIdenticalTo(\Glpi\Inventory\Request::INVENT_QUERY);
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

        $this->array($metadata)->hasSize(5)
         ->string['deviceid']->isIdenticalTo('DGS-3420-52T-2020-12-31-11-28-51')
         ->string['version']->isIdenticalTo('4.1')
         ->string['itemtype']->isIdenticalTo('NetworkEquipment')
         ->variable['port']->isIdenticalTo(null)
         ->string['action']->isIdenticalTo('netinventory');

        global $DB;
        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $this->array($inventory->getAgent()->fields)
         ->string['deviceid']->isIdenticalTo('DGS-3420-52T-2020-12-31-11-28-51')
         ->string['name']->isIdenticalTo('DGS-3420-52T-2020-12-31-11-28-51')
         //->string['version']->isIdenticalTo('')
         ->string['itemtype']->isIdenticalTo('NetworkEquipment')
         ->integer['agenttypes_id']->isIdenticalTo($agenttype['id']);

        //get model, manufacturer, ...
        $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->current();
        $this->array($autoupdatesystems);
        $autoupdatesystems_id = $autoupdatesystems['id'];

        $cmodels = $DB->request(['FROM' => \NetworkEquipmentModel::getTable(), 'WHERE' => ['name' => 'DGS-3420-52T']])->current();
        $this->array($cmodels);
        $models_id = $cmodels['id'];

        $ctypes = $DB->request(['FROM' => \NetworkEquipmentType::getTable(), 'WHERE' => ['name' => 'Networking']])->current();
        $this->array($ctypes);
        $types_id = $ctypes['id'];

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'D-Link']])->current();
        $this->array($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $cloc = $DB->request(['FROM' => \Location::getTable(), 'WHERE' => ['name' => 'WOB Serverraum']])->current();
        $this->array($cloc);
        $locations_id = $cloc['id'];

        //check created computer
        $equipments_id = $inventory->getAgent()->fields['items_id'];
        $this->integer($equipments_id)->isGreaterThan(0);
        $equipment = new \NetworkEquipment();
        $this->boolean($equipment->getFromDB($equipments_id))->isTrue();

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
        $this->array($equipment->fields)->isIdenticalTo($expected);

        //check network ports
        $expected_count = 53;
        $iterator = $DB->request([
            'FROM'   => \NetworkPort::getTable(),
            'WHERE'  => [
                'items_id'           => $equipments_id,
                'itemtype'           => 'NetworkEquipment',
            ],
        ]);
        $this->integer(count($iterator))->isIdenticalTo($expected_count);

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
            $this->boolean($netport->getFromDB($ports_id))->isTrue();
            $instantiation = $netport->getInstantiation();
            if ($port['instantiation_type'] === null) {
                $this->boolean($instantiation)->isFalse();
            } else {
                $this->object($instantiation)->isInstanceOf($port['instantiation_type']);
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

                $this->array($port)->isEqualTo($expected);
            } else {
                $this->string($port['itemtype'])->isIdenticalTo('NetworkEquipment');
                $this->integer($port['items_id'])->isIdenticalTo($equipments_id);
                $this->string($port['instantiation_type'])->isIdenticalTo('NetworkPortEthernet', print_r($port, true));
                $this->string($port['mac'])->matches('/^(?:(?:[0-9a-f]{2}[\:]{1}){5}|(?:[0-9a-f]{2}[-]{1}){5}|(?:[0-9a-f]{2}){5})[0-9a-f]{2}$/i');
                $this->integer($port['is_dynamic'])->isIdenticalTo(1);
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

            $this->integer(count($ip_iterator))->isIdenticalTo(count($ips[$port['name']] ?? []));
            if (isset($ips[$port['name']])) {
                foreach ($ip_iterator as $ip) {
                    $this->array($ips[$port['name']])->contains($ip['name']);
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
            $this->integer(count($components[$type]))->isIdenticalTo(
                $count,
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
            $this->array($component)->isIdenticalTo($expected);
        }

        //ports connections
        $connections = $DB->request(['FROM' => \NetworkPort_NetworkPort::getTable()]);
        $this->integer(count($connections))->isIdenticalTo(36);

        //unmanaged equipments
        $unmanageds = $DB->request(['FROM' => \Unmanaged::getTable()]);
        $this->integer(count($unmanageds))->isIdenticalTo(36);
    }

    public function testImportRefusedFromAssetRulesWithNoLog()
    {
        $rule = new \Rule();

        //prepares needed rules id
        $this->boolean(
            $rule->getFromDBByCrit(['name' => 'Computer constraint (name)'])
        )->isTrue();
        $rules_id_torefuse = $rule->fields['id'];

        $this->boolean(
            $rule->getFromDBByCrit(['name' => 'Computer import denied'])
        )->isTrue();
        $rules_id_refuse = $rule->fields['id'];
        // update action to refused import with no log
        $action = new \RuleAction();
        $action->getFromDBByCrit([
            "rules_id" => $rules_id_refuse,
        ]);
        $action->fields['field'] = '_inventory';
        $action->fields['value'] = 2;
        $action->update($action->fields);


        $this->boolean(
            $rule->getFromDBByCrit(['name' => 'Computer import (by name)'])
        )->isTrue();
        $rules_id_toaccept = $rule->fields['id'];

        //move rule to refuse computer inventory
        $rulecollection = new \RuleImportAssetCollection();
        $this->boolean(
            $rulecollection->moveRule(
                $rules_id_refuse,
                $rules_id_torefuse,
                \RuleCollection::MOVE_BEFORE
            )
        )->isTrue();

        //do inventory
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));
        $inventory = $this->doInventory($json);

        //move rule back to accept computer inventory
        $this->boolean(
            $rulecollection->moveRule(
                $rules_id_refuse,
                $rules_id_toaccept,
                \RuleCollection::MOVE_AFTER
            )
        )->isTrue();

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->array($metadata)->hasSize(7)
         ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
         ->string['version']->isIdenticalTo('FusionInventory-Agent_v2.5.2-1.fc31')
         ->string['itemtype']->isIdenticalTo('Computer')
         ->string['tag']->isIdenticalTo('000005')
         ->variable['port']->isIdenticalTo(null)
         ->string['action']->isIdenticalTo('inventory');
        $this->array($metadata['provider'])->hasSize(10);

        global $DB;
        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
         ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
         ->string['name']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
         ->string['version']->isIdenticalTo('2.5.2-1.fc31')
         ->string['itemtype']->isIdenticalTo('Computer')
         ->string['tag']->isIdenticalTo('000005')
         ->integer['agenttypes_id']->isIdenticalTo($agenttype['id']);

        $computers_id = $agent['items_id'];
        $this->integer($computers_id)->isIdenticalTo(0);

        $iterator = $DB->request([
            'FROM'   => \RefusedEquipment::getTable(),
        ]);
        $this->integer(count($iterator))->isIdenticalTo(0);
    }

    public function testImportRefusedFromAssetRulesWithLog()
    {
        $rule = new \Rule();

        //prepares needed rules id
        $this->boolean(
            $rule->getFromDBByCrit(['name' => 'Computer constraint (name)'])
        )->isTrue();
        $rules_id_torefuse = $rule->fields['id'];


        $this->boolean(
            $rule->getFromDBByCrit(['name' => 'Computer import denied'])
        )->isTrue();
        $rules_id_refuse = $rule->fields['id'];

        //update ruleAction to refused import with log
        $ruleaction = new \RuleAction();
        $this->boolean($ruleaction->getFromDBByCrit(['rules_id' => $rules_id_refuse]))->isTrue();
        $this->boolean(
            $ruleaction->update([
                'id'    => $ruleaction->fields['id'],
                'field' => '_ignore_import',
                'action_type' => 'assign',
                'value' => 1
            ])
        )->isTrue();

        $this->boolean(
            $rule->getFromDBByCrit(['name' => 'Computer import (by name)'])
        )->isTrue();
        $rules_id_toaccept = $rule->fields['id'];

        //move rule to refuse computer inventory
        $rulecollection = new \RuleImportAssetCollection();
        $this->boolean(
            $rulecollection->moveRule(
                $rules_id_refuse,
                $rules_id_torefuse,
                \RuleCollection::MOVE_BEFORE
            )
        )->isTrue();

        //do inventory
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));
        $inventory = $this->doInventory($json);

        //move rule back to accept computer inventory
        $this->boolean(
            $rulecollection->moveRule(
                $rules_id_refuse,
                $rules_id_toaccept,
                \RuleCollection::MOVE_AFTER
            )
        )->isTrue();

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->array($metadata)->hasSize(7)
         ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
         ->string['version']->isIdenticalTo('FusionInventory-Agent_v2.5.2-1.fc31')
         ->string['itemtype']->isIdenticalTo('Computer')
         ->string['tag']->isIdenticalTo('000005')
         ->variable['port']->isIdenticalTo(null)
         ->string['action']->isIdenticalTo('inventory');
        $this->array($metadata['provider'])->hasSize(10);

        global $DB;
        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
         ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
         ->string['name']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
         ->string['version']->isIdenticalTo('2.5.2-1.fc31')
         ->string['itemtype']->isIdenticalTo('Computer')
         ->string['tag']->isIdenticalTo('000005')
         ->integer['agenttypes_id']->isIdenticalTo($agenttype['id']);

        $computers_id = $agent['items_id'];
        $this->integer($computers_id)->isIdenticalTo(0);

        $iterator = $DB->request([
            'FROM'   => \RefusedEquipment::getTable(),
        ]);
        $this->integer(count($iterator))->isIdenticalTo(1);

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

        $this->array($result)->isEqualTo($expected);

        //check no matchedlogs
        $mlogs = new \RuleMatchedLog();
        $found = $mlogs->find();
        $this->array($found)->hasSize(0);

        //test inventory from refused equipment, will be accepted since rules has been reset ;)
        $refused = new \RefusedEquipment();
        $this->boolean($refused->getFromDB($result['id']))->isTrue();

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
        $this->integer(count($iterator))->isIdenticalTo(0);

        //but a linked computer
        $gagent = new \Agent();
        $this->boolean($gagent->getFromDB($agent['id']))->isTrue();

        $computer = new \Computer();
        $this->boolean($computer->getFromDB($gagent->fields['items_id']))->isTrue();
        $this->string($computer->fields['name'])->isIdenticalTo('glpixps');

        //check no matchedlogs
        $mlogs = new \RuleMatchedLog();
        $found = $mlogs->find();
        $this->array($found)->hasSize(3);

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
        $this->integer(count($iterator))->isIdenticalTo(1);
        $this->string($iterator->current()['name'])->isIdenticalTo('Monitor import (by serial)');
        $this->string($iterator->current()['method'])->isIdenticalTo(\Glpi\Inventory\Request::INVENT_QUERY);

        $printer_criteria = $criteria;
        $printer_criteria['WHERE'] = ['itemtype' => \Printer::getType()];
        $iterator = $DB->request($printer_criteria);
        $this->integer(count($iterator))->isIdenticalTo(1);
        $this->string($iterator->current()['name'])->isIdenticalTo('Printer import (by serial)');
        $this->string($iterator->current()['method'])->isIdenticalTo(\Glpi\Inventory\Request::INVENT_QUERY);

        $computer_criteria = $criteria;
        $computer_criteria['WHERE'] = ['itemtype' => \Computer::getType()];
        $iterator = $DB->request($computer_criteria);
        $this->integer(count($iterator))->isIdenticalTo(1);
        $this->string($iterator->current()['name'])->isIdenticalTo('Computer import (by serial + uuid)');
        $this->integer($iterator->current()['items_id'])->isIdenticalTo($gagent->fields['items_id']);
        $this->string($iterator->current()['method'])->isIdenticalTo(\Glpi\Inventory\Request::INVENT_QUERY);
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
        $this->integer($rules_id)->isGreaterThan(0);

        // Add criteria
        $rulecriteria = new \RuleCriteria();
        $this->integer(
            $rulecriteria->add([
                'rules_id'  => $rules_id,
                'criteria'  => "deviceid",
                'pattern'   => "/^glpixps.*/",
                'condition' => \RuleImportEntity::REGEX_MATCH
            ])
        )->isGreaterThan(0);

        // Add action
        $ruleaction = new \RuleAction();
        $this->integer(
            $ruleaction->add([
                'rules_id'    => $rules_id,
                'action_type' => 'assign',
                'field'       => '_ignore_import',
                'value'       => 1
            ])
        )->isGreaterThan(0);

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));
        unset($json->content->bios);
        unset($json->content->hardware->name);
        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->array($metadata)->hasSize(7)
         ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
         ->string['version']->isIdenticalTo('FusionInventory-Agent_v2.5.2-1.fc31')
         ->string['itemtype']->isIdenticalTo('Computer')
         ->string['tag']->isIdenticalTo('000005')
         ->variable['port']->isIdenticalTo(null)
         ->string['action']->isIdenticalTo('inventory');
        $this->array($metadata['provider'])->hasSize(10);

        global $DB;
        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
         ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
         ->string['name']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
         ->string['version']->isIdenticalTo('2.5.2-1.fc31')
         ->string['itemtype']->isIdenticalTo('Computer')
         ->string['tag']->isIdenticalTo('000005')
         ->integer['agenttypes_id']->isIdenticalTo($agenttype['id']);

        $computers_id = $agent['items_id'];
        $this->integer($computers_id)->isIdenticalTo(0);

        $iterator = $DB->request([
            'FROM'   => \RefusedEquipment::getTable(),
        ]);
        $this->integer(count($iterator))->isIdenticalTo(1);

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

        $this->array($result)->isEqualTo($expected);
    }

    public function testImportFiles()
    {
        $nbcomputers = countElementsInTable(\Computer::getTable());
        $nbprinters = countElementsInTable(\Printer::getTable());

        $json_name = 'computer_1.json';
        $json_path = self::INV_FIXTURES . $json_name;
        $conf = new \Glpi\Inventory\Conf();
        $result = $conf->importFiles([$json_name => $json_path]);
        $this
            ->array($result[$json_name])
            ->then
            ->boolean($result[$json_name]['success'])
            ->isTrue()
            ->then
            ->object($result[$json_name]['items'][0])
            ->isInstanceOf('Computer');

        //1 computer and 1 printer has been inventoried
        $nbcomputers++;
        $nbprinters++;

        $this->integer($nbcomputers)->isIdenticalTo(countElementsInTable(\Computer::getTable()));
        $this->integer($nbprinters)->isIdenticalTo(countElementsInTable(\Printer::getTable()));
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
            self::INV_FIXTURES . 'computer_1.json',
            self::INV_FIXTURES . 'networkequipment_1.json',
            self::INV_FIXTURES . 'printer_1.json',
        ];

        UnifiedArchive::archiveFiles($json_paths, self::INVENTORY_ARCHIVE_PATH);

        $conf = new \Glpi\Inventory\Conf();
        $result = $conf->importFiles(['to_inventory.zip' => self::INVENTORY_ARCHIVE_PATH]);

        $this->array($result)->hasSize(3);

        // Expected result for computer_1.json
        $this
            ->boolean($result['to_inventory.zip/computer_1.json']['success'])
            ->isTrue()
            ->then
            ->object($result['to_inventory.zip/computer_1.json']['items'][0])
            ->isInstanceOf('Computer');

        // Expected result for networkequipment_1.json
        $this
            ->boolean($result['to_inventory.zip/networkequipment_1.json']['success'])
            ->isTrue()
            ->then
            ->object($result['to_inventory.zip/networkequipment_1.json']['items'][0])
            ->isInstanceOf('NetworkEquipment');

        // Expected result for printer_1.json
        $this
            ->boolean($result['to_inventory.zip/printer_1.json']['success'])
            ->isTrue()
            ->then
            ->object($result['to_inventory.zip/printer_1.json']['items'][0])
            ->isInstanceOf('Printer');

        //1 computer 2 printers and a network equipment has been inventoried
        $nbcomputers++;
        $nbprinters += 2;
        $nbnetequps++;

        $this->integer($nbcomputers)->isIdenticalTo(countElementsInTable(\Computer::getTable()));
        $this->integer($nbprinters)->isIdenticalTo(countElementsInTable(\Printer::getTable()));
        $this->integer($nbnetequps)->isIdenticalTo(countElementsInTable(\NetworkEquipment::getTable()));
    }

    public function testImportVirtualMachines()
    {
        global $DB;

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_2.json'));

        $count_vms = count($json->content->virtualmachines);
        $this->integer($count_vms)->isIdenticalTo(6);

        $nb_vms = countElementsInTable(\ComputerVirtualMachine::getTable());
        $nb_computers = countElementsInTable(\Computer::getTable());
        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->array($metadata)->hasSize(5)
         ->string['deviceid']->isIdenticalTo('acomputer-2021-01-26-14-32-36')
         ->string['itemtype']->isIdenticalTo('Computer')
         ->variable['port']->isIdenticalTo(null)
         ->string['action']->isIdenticalTo('inventory');

        //check we add only one computer
        ++$nb_computers;
        $this->integer(countElementsInTable(\Computer::getTable()))->isIdenticalTo($nb_computers);
        //check created vms
        $nb_vms += $count_vms;
        $this->integer(countElementsInTable(\ComputerVirtualMachine::getTable()))->isIdenticalTo($nb_vms);

        //change config to import vms as computers
        $this->login();
        $conf = new \Glpi\Inventory\Conf();
        $this->boolean($conf->saveConf(['vm_as_computer' => 1]))->isTrue();
        $this->logout();

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_2.json'));
        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->array($metadata)->hasSize(5)
         ->string['deviceid']->isIdenticalTo('acomputer-2021-01-26-14-32-36')
         ->string['itemtype']->isIdenticalTo('Computer')
         ->variable['port']->isIdenticalTo(null)
         ->string['action']->isIdenticalTo('inventory');

        //check we add main computer and one computer per vm
        //one does not have an uuid, so no computer is created.
        $this->integer(countElementsInTable(\Computer::getTable()))->isIdenticalTo($nb_computers + $count_vms - 1);
        //check created vms
        $this->integer(countElementsInTable(\ComputerVirtualMachine::getTable()))->isIdenticalTo($nb_vms);

        //partial inventory: postgres vm has been stopped
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_2_partial_vms.json'));
        $this->doInventory($json);

        //check nothing has changed
        $this->integer(countElementsInTable(\Computer::getTable()))->isIdenticalTo($nb_computers + $count_vms - 1);
        $this->integer(countElementsInTable(\ComputerVirtualMachine::getTable()))->isIdenticalTo($nb_vms);

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
        $this->integer(count($iterator))->isIdenticalTo(1);
    }

    public function testUpdateVirtualMachines()
    {
        global $DB;

        $json = json_decode(file_get_contents(GLPI_ROOT . '/tests/fixtures/inventories/lxc-server-1.json'));

        $count_vms = count($json->content->virtualmachines);
        $this->integer($count_vms)->isIdenticalTo(1);

        $nb_vms = countElementsInTable(\ComputerVirtualMachine::getTable());
        $nb_computers = countElementsInTable(\Computer::getTable());
        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->array($metadata)->hasSize(5)
            ->string['deviceid']->isIdenticalTo('lxc-server-2022-08-09-17-49-51')
            ->string['itemtype']->isIdenticalTo('Computer')
            ->variable['port']->isIdenticalTo(null)
            ->string['action']->isIdenticalTo('inventory');

        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
            ->string['deviceid']->isIdenticalTo('lxc-server-2022-08-09-17-49-51')
            ->string['name']->isIdenticalTo('lxc-server-2022-08-09-17-49-51')
            ->string['itemtype']->isIdenticalTo('Computer')
            ->integer['agenttypes_id']->isIdenticalTo($agenttype['id']);
        $computers_id = $agent['items_id'];

        //check we add only one computer
        ++$nb_computers;
        $this->integer(countElementsInTable(\Computer::getTable()))->isIdenticalTo($nb_computers);
        //check created vms
        $nb_vms += $count_vms;
        $this->integer(countElementsInTable(\ComputerVirtualMachine::getTable()))->isIdenticalTo($nb_vms);

        $cvms = new \ComputerVirtualMachine();
        $this->boolean($cvms->getFromDBByCrit(['computers_id' => $computers_id]))->isTrue();

        $this->array($cvms->fields)
            ->string['name']->isIdenticalTo('glpi-10-rc1')
            ->integer['vcpu']->isIdenticalTo(2)
            ->integer['ram']->isIdenticalTo(2048)
            ->string['uuid']->isIdenticalTo('487dfdb542a4bfb23670b8d4e76d8b6886c2ed35')
        ;

        //import again, RAM has changed
        $json = json_decode(file_get_contents(GLPI_ROOT . '/tests/fixtures/inventories/lxc-server-1.json'));
        $json_vm = $json->content->virtualmachines[0];
        $json_vm->memory = 4096;
        $json_vms = [$json_vm];
        $json->content->virtualmachines = $json_vms;

        $this->doInventory($json);

        $this->boolean($cvms->getFromDBByCrit(['computers_id' => $computers_id]))->isTrue();

        $this->array($cvms->fields)
            ->string['name']->isIdenticalTo('glpi-10-rc1')
            ->integer['vcpu']->isIdenticalTo(2)
            ->integer['ram']->isIdenticalTo(4096)
            ->string['uuid']->isIdenticalTo('487dfdb542a4bfb23670b8d4e76d8b6886c2ed35')
        ;
    }

    public function testRuleRefuseImportVirtualMachines()
    {
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_2.json'));

        $count_vms = count($json->content->virtualmachines);
        $this->integer($count_vms)->isIdenticalTo(6);

        $nb_vms = countElementsInTable(\ComputerVirtualMachine::getTable());
        $nb_computers = countElementsInTable(\Computer::getTable());

        //change config to import vms as computers
        $this->login();
        $conf = new \Glpi\Inventory\Conf();
        $this->boolean($conf->saveConf(['vm_as_computer' => 1]))->isTrue();
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

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_2.json'));
        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->array($metadata)->hasSize(5)
            ->string['deviceid']->isIdenticalTo('acomputer-2021-01-26-14-32-36')
            ->string['itemtype']->isIdenticalTo('Computer')
            ->variable['port']->isIdenticalTo(null)
            ->string['action']->isIdenticalTo('inventory');

        global $DB;

        //check created vms
        $this->integer(countElementsInTable(\ComputerVirtualMachine::getTable()))->isIdenticalTo($count_vms);

        //check we add main computer and one computer per vm
        //one does not have an uuid, so no computer is created.
        ++$nb_computers;
        $this->integer(countElementsInTable(\Computer::getTable()))->isIdenticalTo($nb_computers + $count_vms - 2);
    }

    public function testImportDatabases()
    {
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_2.json'));

        $nb_computers = countElementsInTable(\Computer::getTable());
        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->array($metadata)->hasSize(5)
            ->string['deviceid']->isIdenticalTo('acomputer-2021-01-26-14-32-36')
            ->string['itemtype']->isIdenticalTo('Computer')
            ->variable['port']->isIdenticalTo(null)
            ->string['action']->isIdenticalTo('inventory');

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

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_2_partial_dbs.json'));
        $this->doInventory($json);

        //check nothing has changed
        $this->integer(countElementsInTable(\Computer::getTable()))->isIdenticalTo($nb_computers + 1);

        //check created databases & instances
        $this->integer(countElementsInTable(\DatabaseInstance::getTable()))->isIdenticalTo(2);
        $this->integer(countElementsInTable(\DatabaseInstance::getTable(), ['is_dynamic' => 1]))->isIdenticalTo(2);
        $this->integer(countElementsInTable(\Database::getTable()))->isIdenticalTo(3);

        //play an update - nothing should have changed
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_2_partial_dbs.json'));
        $this->doInventory($json);

        //check nothing has changed
        $this->integer(countElementsInTable(\Computer::getTable()))->isIdenticalTo($nb_computers + 1);

        //check created databases & instances
        $this->integer(countElementsInTable(\DatabaseInstance::getTable()))->isIdenticalTo(2);
        $this->integer(countElementsInTable(\Database::getTable()))->isIdenticalTo(3);

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
        $this->integer(countElementsInTable(\DatabaseInstance::getTable(), ['is_deleted' => 0]))->isIdenticalTo(1);
        $this->integer(countElementsInTable(\DatabaseInstance::getTable(), ['is_deleted' => 1]))->isIdenticalTo(1);

        //ensure database version has been updated
        $database = new \DatabaseInstance();
        $this->boolean($database->getFromDBByCrit(['name' => 'MariaDB']))->isTrue();
        $this->string($database->fields['version'])->isIdenticalTo('Ver 15.1 Distrib 10.5.10-MariaDB-modified');

        //- ensure existing instances has been updated
        $databases = $database->getDatabases();
        $this->array($databases)->hasSize(2);
        $this->array(array_pop($databases))
            ->string['name']->isIdenticalTo('new_database')
            ->integer['size']->isIdenticalTo(2048);
        $this->array(array_pop($databases))
            ->string['name']->isIdenticalTo('glpi')
            ->integer['size']->isIdenticalTo(55000);

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
        $this->integer(countElementsInTable(\DatabaseInstance::getTable(), ['is_deleted' => 0]))->isIdenticalTo(1);
        $this->integer(countElementsInTable(\DatabaseInstance::getTable(), ['is_deleted' => 1]))->isIdenticalTo(2);

        //ensure database version has been updated
        $database = new \DatabaseInstance();
        $this->boolean($database->getFromDBByCrit(['name' => 'MariaDB']))->isTrue();
        $this->string($database->fields['version'])->isIdenticalTo('Ver 15.1 Distrib 10.5.10-MariaDB-modified');

        //- ensure existing instances has been updated
        $databases = $database->getDatabases();
        $this->array($databases)->hasSize(2);
        $this->array(array_pop($databases))
            ->string['name']->isIdenticalTo('new_database')
            ->integer['size']->isIdenticalTo(2048);
        $this->array(array_pop($databases))
            ->string['name']->isIdenticalTo('glpi')
            ->integer['size']->isIdenticalTo(55000);
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
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();


        //check created computer
        $phone = new \Phone();
        $this->boolean($phone->getFromDB($agent['items_id']))->isTrue();

        //check for components
        $item_devicesimcard = new \Item_DeviceSimcard();
        $simcards_first = $item_devicesimcard->find(['itemtype' => 'Phone' , 'items_id' => $agent['items_id']]);
        $this->integer(count($simcards_first))->isIdenticalTo(1);

        //re run inventory to check if item_simcard ID is changed
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'phone_1.json'));

        $this->doInventory($json);
        $item_devicesimcard = new \Item_DeviceSimcard();
        $simcards_second = $item_devicesimcard->find(['itemtype' => 'Phone' , 'items_id' => $agent['items_id']]);
        $this->integer(count($simcards_second))->isIdenticalTo(1);

        $this->array($simcards_first)->isIdenticalTo($simcards_second);
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
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();


        //check created computer
        $phone = new \Phone();
        $this->boolean($phone->getFromDB($agent['items_id']))->isTrue();

        //check for components
        $item_devicesimcard = new \Item_DeviceSimcard();
        $simcards_first = $item_devicesimcard->find(['itemtype' => 'Phone' , 'items_id' => $agent['items_id']]);
        $this->integer(count($simcards_first))->isIdenticalTo(2);

        //re run inventory to check if item_simcard ID is changed
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'phone_1.json'));

        $this->doInventory($json);
        $item_devicesimcard = new \Item_DeviceSimcard();
        $simcards_second = $item_devicesimcard->find(['itemtype' => 'Phone' , 'items_id' => $agent['items_id']]);
        $this->integer(count($simcards_second))->isIdenticalTo(2);

        $this->array($simcards_first)->isIdenticalTo($simcards_second);
    }


    public function testImportPhone()
    {
        global $DB, $CFG_GLPI;

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'phone_1.json'));

        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->array($metadata)->hasSize(5)
         ->string['deviceid']->isIdenticalTo('Mi9TPro-TéléphoneM-2019-12-18-14-30-16')
         ->string['version']->isIdenticalTo('example-app-java')
         ->string['itemtype']->isIdenticalTo('Phone')
         ->variable['port']->isIdenticalTo(null)
         ->string['action']->isIdenticalTo('inventory');

        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
         ->string['deviceid']->isIdenticalTo('Mi9TPro-TéléphoneM-2019-12-18-14-30-16')
         ->string['name']->isIdenticalTo('Mi9TPro-TéléphoneM-2019-12-18-14-30-16')
         ->string['itemtype']->isIdenticalTo('Phone')
         ->integer['agenttypes_id']->isIdenticalTo($agenttype['id'])
         ->integer['items_id']->isGreaterThan(0);

        //check matchedlogs
        $mlogs = new \RuleMatchedLog();
        $found = $mlogs->find();
        $this->array($found)->hasSize(1);

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
        $this->integer(count($iterator))->isIdenticalTo(1);
        $this->string($iterator->current()['name'])->isIdenticalTo('Phone import (by serial + uuid)');
        $this->string($iterator->current()['method'])->isIdenticalTo(\Glpi\Inventory\Request::INVENT_QUERY);

        //get phone models, manufacturer, ...
        $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->current();
        $this->array($autoupdatesystems);
        $autoupdatesystems_id = $autoupdatesystems['id'];

        $cmodels = $DB->request(['FROM' => \PhoneModel::getTable(), 'WHERE' => ['name' => 'Mi 9T Pro']])->current();
        $this->array($cmodels);
        $computermodels_id = $cmodels['id'];

        $ctypes = $DB->request(['FROM' => \PhoneType::getTable(), 'WHERE' => ['name' => 'Mi 9T Pro']])->current();
        $this->array($ctypes);
        $computertypes_id = $ctypes['id'];

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Xiaomi']])->current();
        $this->array($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        //check created phone
        $phones_id = $inventory->getAgent()->fields['items_id'];
        $this->integer($phones_id)->isGreaterThan(0);
        $phone = new \Phone();
        $this->boolean($phone->getFromDB($phones_id))->isTrue();

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
        $this->array($phone->fields)->isIdenticalTo($expected);

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
        $this->array($record)->isIdenticalTo($expected);

        //remote management
        $mgmt = new \Item_RemoteManagement();
        $iterator = $mgmt->getFromItem($phone);
        $this->integer(count($iterator))->isIdenticalTo(0);

        //volumes
        $idisks = new \Item_Disk();
        $iterator = $idisks->getFromItem($phone);
        $this->integer(count($iterator))->isIdenticalTo(4);

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
            $this->array($volume)->isEqualTo($expected);
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
        $this->integer(count($iterator))->isIdenticalTo(1);

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
            $this->boolean($netport->getFromDB($ports_id))->isTrue();
            $instantiation = $netport->getInstantiation();
            if ($port['instantiation_type'] === null) {
                $this->boolean($instantiation)->isFalse();
            } else {
                $this->object($instantiation)->isInstanceOf($port['instantiation_type']);
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

            $this->array($port)->isEqualTo($expected);
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

            $this->integer(count($ip_iterator))->isIdenticalTo(count($ips[$port['name']] ?? []));
            if (isset($ips[$port['name']])) {
                //FIXME: missing all ipv6 :(
                $ip = $ip_iterator->current();
                $this->integer((int)$ip['version'])->isIdenticalTo(4);
                $this->string($ip['name'])->isIdenticalTo($ips[$port['name']]['v4']);
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
            $this->integer(count($components[$type]))->isIdenticalTo($count, count($components[$type]) . ' ' . $type);
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
            $this->array($component)->isIdenticalTo($expected);
        }

        //software
        $isoft = new \Item_SoftwareVersion();
        $iterator = $isoft->getFromItem($phone);
        $this->integer(count($iterator))->isIdenticalTo(4);

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
            $this->array([
                'softname'     => $soft['softname'],
                'version'      => $soft['version'],
                'dateinstall'  => $soft['dateinstall']
            ])->isEqualTo($expected);
            ++$i;
        }
    }

    public function testPartialComputerImport()
    {
        global $DB;

        //initial import
        $this->testImportComputer();

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1_partial_volumes.json'));
        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->array($metadata)->hasSize(6)
         ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
         ->string['version']->isIdenticalTo('FusionInventory-Agent_v2.5.2-1.fc31')
         ->string['itemtype']->isIdenticalTo('Computer')
         ->string['action']->isIdenticalTo('inventory');

        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
         ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
         ->string['name']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
         ->string['version']->isIdenticalTo('2.5.2-1.fc31')
         ->string['itemtype']->isIdenticalTo('Computer')
         ->integer['agenttypes_id']->isIdenticalTo($agenttype['id']);

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
        $this->integer($rules_id)->isGreaterThan(0);

        $this->integer(
            $criteria->add([
                'rules_id' => $rules_id,
                'criteria' => 'name',
                'condition' => \Rule::PATTERN_IS,
                'pattern' => 'Dell Inc.'
            ])
        )->isGreaterThan(0);

        $this->integer(
            $action->add([
                'rules_id' => $rules_id,
                'action_type' => 'assign',
                'field' => 'name',
                'value' => 'Dictionary manufacturer'
            ])
        )->isGreaterThan(0);

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_2.json'));

        $nb_computers = countElementsInTable(\Computer::getTable());
        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->array($metadata)->hasSize(5)
            ->string['deviceid']->isIdenticalTo('acomputer-2021-01-26-14-32-36')
            ->string['itemtype']->isIdenticalTo('Computer')
            ->variable['port']->isIdenticalTo(null)
            ->string['action']->isIdenticalTo('inventory');

        //check we add only one computer
        ++$nb_computers;
        $this->integer(countElementsInTable(\Computer::getTable()))->isIdenticalTo($nb_computers);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
            ->string['deviceid']->isIdenticalTo('acomputer-2021-01-26-14-32-36')
            ->string['name']->isIdenticalTo('acomputer-2021-01-26-14-32-36')
            ->string['itemtype']->isIdenticalTo('Computer')
            ->integer['items_id']->isGreaterThan(0);

        //check created computer
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($agent['items_id']))->isTrue();
        $manufacturer = new \Manufacturer();
        $this->boolean($manufacturer->getFromDB($computer->fields['manufacturers_id']))->isTrue();
        $this->string($manufacturer->fields['name'])->isIdenticalTo('Dictionary manufacturer');
    }

    public function testDictionnaryOperatingSystem()
    {
        global $DB;

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));

        $this->doInventory($json);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();

        //check created computer
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($agent['items_id']))->isTrue();

        //check OS
        $item_operating = new Item_OperatingSystem();
        $this->boolean(
            $item_operating->getFromDBByCrit([
                "itemtype" => 'Computer',
                "items_id" => $agent['items_id'],
            ])
        )->isTrue();

        $operating_system = new OperatingSystem();
        $this->boolean(
            $operating_system->getFromDB($item_operating->fields['operatingsystems_id'])
        )->isTrue();

        $this->string($operating_system->fields['name'])->isEqualTo("Fedora 31 (Workstation Edition)");

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
        $this->integer($rules_id)->isGreaterThan(0);

        $this->integer(
            $criteria->add([
                'rules_id' => $rules_id,
                'criteria' => 'name',
                'condition' => \Rule::PATTERN_CONTAIN,
                'pattern' => 'Fedora 31'
            ])
        )->isGreaterThan(0);

        $this->integer(
            $action->add([
                'rules_id' => $rules_id,
                'action_type' => 'assign',
                'field' => 'name',
                'value' => 'Ubuntu'
            ])
        )->isGreaterThan(0);

        //redo an inventory
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));
        $this->doInventory($json);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();

        //check updated computer
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($agent['items_id']))->isTrue();

        //check OS
        $item_operating = new Item_OperatingSystem();
        $this->boolean(
            $item_operating->getFromDBByCrit([
                "itemtype" => 'Computer',
                "items_id" => $agent['items_id'],
            ])
        )->isTrue();

        $operating_system = new OperatingSystem();
        $this->boolean(
            $operating_system->getFromDB($item_operating->fields['operatingsystems_id'])
        )->isTrue();

        $this->string($operating_system->fields['name'])->isEqualTo("Ubuntu");
    }

    public function testDictionnaryOperatingSystemVersion()
    {
        global $DB;

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));

        $this->doInventory($json);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();

        //check created computer
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($agent['items_id']))->isTrue();

        //check OS
        $item_operating = new Item_OperatingSystem();
        $this->boolean(
            $item_operating->getFromDBByCrit([
                "itemtype" => 'Computer',
                "items_id" => $agent['items_id'],
            ])
        )->isTrue();

        $operating_system_version = new OperatingSystemVersion();
        $this->boolean(
            $operating_system_version->getFromDB($item_operating->fields['operatingsystemversions_id'])
        )->isTrue();

        //check if is original value
        $this->string($operating_system_version->fields['name'])->isEqualTo("31 (Workstation Edition)");

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
        $this->integer($rules_id)->isGreaterThan(0);

        $this->integer(
            $criteria->add([
                'rules_id' => $rules_id,
                'criteria' => 'name',
                'condition' => \Rule::PATTERN_CONTAIN,
                'pattern' => '31 (Workstation Edition)'
            ])
        )->isGreaterThan(0);

        $this->integer(
            $action->add([
                'rules_id' => $rules_id,
                'action_type' => 'assign',
                'field' => 'name',
                'value' => 'New version'
            ])
        )->isGreaterThan(0);

        //redo an inventory
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));
        $this->doInventory($json);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();

        //check updated computer
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($agent['items_id']))->isTrue();

        //check OS
        $item_operating = new Item_OperatingSystem();
        $this->boolean(
            $item_operating->getFromDBByCrit([
                "itemtype" => 'Computer',
                "items_id" => $agent['items_id'],
            ])
        )->isTrue();

        $operating_system_version = new OperatingSystemVersion();
        $this->boolean(
            $operating_system_version->getFromDB($item_operating->fields['operatingsystemversions_id'])
        )->isTrue();

        //check if is specific value
        $this->string($operating_system_version->fields['name'])->isEqualTo("New version");
    }

    public function testDictionnaryOperatingSystemArchitecture()
    {
        global $DB;

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));

        $this->doInventory($json);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();

        //check created computer
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($agent['items_id']))->isTrue();

        //check OS
        $item_operating = new Item_OperatingSystem();
        $this->boolean(
            $item_operating->getFromDBByCrit([
                "itemtype" => 'Computer',
                "items_id" => $agent['items_id'],
            ])
        )->isTrue();

        $operating_arch = new OperatingSystemArchitecture();
        $this->boolean(
            $operating_arch->getFromDB($item_operating->fields['operatingsystemarchitectures_id'])
        )->isTrue();
        //check if is original value
        $this->string($operating_arch->fields['name'])->isEqualTo("x86_64");

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
        $this->integer($rules_id)->isGreaterThan(0);

        $this->integer(
            $criteria->add([
                'rules_id' => $rules_id,
                'criteria' => 'name',
                'condition' => \Rule::PATTERN_CONTAIN,
                'pattern' => 'x86_64'
            ])
        )->isGreaterThan(0);

        $this->integer(
            $action->add([
                'rules_id' => $rules_id,
                'action_type' => 'assign',
                'field' => 'name',
                'value' => 'New arch'
            ])
        )->isGreaterThan(0);

        //redo an inventory
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));
        $this->doInventory($json);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();

        //check updated computer
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($agent['items_id']))->isTrue();

        //check OS
        $item_operating = new Item_OperatingSystem();
        $this->boolean(
            $item_operating->getFromDBByCrit([
                "itemtype" => 'Computer',
                "items_id" => $agent['items_id'],
            ])
        )->isTrue();

        $operating_arch = new OperatingSystemArchitecture();
        $this->boolean(
            $operating_arch->getFromDB($item_operating->fields['operatingsystemarchitectures_id'])
        )->isTrue();

        //check if is specific value
        $this->string($operating_arch->fields['name'])->isEqualTo("New arch");
    }

    public function testDictionnaryOperatingSystemServicePack()
    {
        global $DB;

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));

        $this->doInventory($json);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();

        //check created computer
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($agent['items_id']))->isTrue();

        //check OS
        $item_operating = new Item_OperatingSystem();
        $this->boolean(
            $item_operating->getFromDBByCrit([
                "itemtype" => 'Computer',
                "items_id" => $agent['items_id'],
            ])
        )->isTrue();

        //No service pack from linux (normal)
        $this->integer($item_operating->fields['operatingsystemservicepacks_id'])->isEqualto(0);

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
        $this->integer($rules_id)->isGreaterThan(0);

        //create criteria on os_name
        $this->integer(
            $criteria->add([
                'rules_id' => $rules_id,
                'criteria' => 'os_name',
                'condition' => \Rule::PATTERN_CONTAIN,
                'pattern' => 'Fedora 31'
            ])
        )->isGreaterThan(0);

        $this->integer(
            $action->add([
                'rules_id' => $rules_id,
                'action_type' => 'assign',
                'field' => 'name',
                'value' => 'New service_pack'
            ])
        )->isGreaterThan(0);

        //redo an inventory
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));
        $this->doInventory($json);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();

        //check updated computer
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($agent['items_id']))->isTrue();

        //check OS
        $item_operating = new Item_OperatingSystem();
        $this->boolean(
            $item_operating->getFromDBByCrit([
                "itemtype" => 'Computer',
                "items_id" => $agent['items_id'],
            ])
        )->isTrue();

        $operating_service_pack = new OperatingSystemServicePack();
        $this->boolean(
            $operating_service_pack->getFromDB($item_operating->fields['operatingsystemservicepacks_id'])
        )->isTrue();
        //check if is specific value
        $this->string($operating_service_pack->fields['name'])->isEqualTo("New service_pack");
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
        $this->integer($inv_states_id)->isGreaterThan(0);

        $cleaned_states_id = $state->add([
            'name' => 'Has been cleaned'
        ]);
        $this->integer($cleaned_states_id)->isGreaterThan(0);

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
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $agents_id = $agent['id'];
        $this->array($agent)
            ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
            ->string['name']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
            ->string['version']->isIdenticalTo('2.5.2-1.fc31')
            ->string['itemtype']->isIdenticalTo('Computer')
            ->string['tag']->isIdenticalTo('000005')
            ->integer['agenttypes_id']->isIdenticalTo($agenttype['id'])
            ->integer['items_id']->isGreaterThan(0);

        //check created computer
        $computers_id = $agent['items_id'];
        $this->integer($computers_id)->isGreaterThan(0);
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($computers_id))->isTrue();

        //check states has been set
        $this->integer($computer->fields['states_id'])->isIdenticalTo($inv_states_id);

        $lockedfield = new \Lockedfield();
        $this->boolean($lockedfield->isHandled($computer))->isTrue();
        $this->array($lockedfield->getLockedValues($computer->getType(), $computers_id))->isEmpty();

        //set agent inventory date in past
        $invdate = new \DateTime($agent['last_contact']);
        $invdate->sub(new \DateInterval('P1Y'));

        $agent = new \Agent();
        $this->boolean(
            $agent->update([
                'id' => $agents_id,
                'last_contact' => $invdate->format('Y-m-d H:i:s')
            ])
        )->isTrue();

        //cleanup old agents
        $name = \CronTask::launch(-\CronTask::MODE_INTERNAL, 1, 'Cleanoldagents');
        $this->string($name)->isIdenticalTo('Cleanoldagents');

        //check computer state has been updated
        $this->boolean($computer->getFromDB($computers_id))->isTrue();
        $this->integer($computer->fields['states_id'])->isIdenticalTo($cleaned_states_id);

        $this->boolean($lockedfield->isHandled($computer))->isTrue();
        $this->array($lockedfield->getLockedValues($computer->getType(), $computers_id))->isEmpty();
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
        $this->integer($default_states_id)->isGreaterThan(0);

        $other_state = new \State();
        $other_states_id = $other_state->add([
            'name' => 'Another states'
        ]);
        $this->integer($other_states_id)->isGreaterThan(0);

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
        $this->integer($lock_id)->isGreaterThan(0);

        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));
        $this->doInventory($json);

        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $agents_id = $agent['id'];
        $this->array($agent)
            ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
            ->string['name']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
            ->string['version']->isIdenticalTo('2.5.2-1.fc31')
            ->string['itemtype']->isIdenticalTo('Computer')
            ->string['tag']->isIdenticalTo('000005')
            ->integer['agenttypes_id']->isIdenticalTo($agenttype['id'])
            ->integer['items_id']->isGreaterThan(0);

        //check created computer
        $computers_id = $agent['items_id'];
        $this->integer($computers_id)->isGreaterThan(0);
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($computers_id))->isTrue();

        //check default states has been set
        $this->integer($computer->fields['states_id'])->isIdenticalTo($default_states_id);

        //update states
        $this->boolean($computer->update(['id' => $computers_id, 'states_id' => $other_states_id]))->isTrue();

        //redo inventory
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));
        $this->doInventory($json);

        //reload computer
        $this->boolean($computer->getFromDB($computers_id))->isTrue();
        //check is same on update
        $this->integer($computer->fields['states_id'])->isIdenticalTo($other_states_id);
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
        $this->integer($rules_id)->isGreaterThan(0);

        //create criteria
        $input_criteria = [
            'rules_id'  => $rules_id,
            'criteria'      => '_tag',
            'condition' => \Rule::REGEX_MATCH,
            'pattern' => '/(.*)/'
        ];
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add($input_criteria);
        $this->integer($rule_criteria_id)->isGreaterThan(0);

        //create action
        $input_action = [
            'rules_id'  => $rules_id,
            'action_type' => 'regex_result',
            'field' => 'otherserial',
            'value' => '#0'
        ];
        $rule_action = new \RuleAction();
        $rule_action_id = $rule_action->add($input_action);
        $this->integer($rule_action_id)->isGreaterThan(0);

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
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->string($agent['tag'])->isIdenticalTo($tag);

        //check created computer
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($agent['items_id']))->isTrue();
        $this->string($computer->fields['otherserial'])->isIdenticalTo($tag);


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
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->string($agent['tag'])->isIdenticalTo($tag);

        //check created computer
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($agent['items_id']))->isTrue();
        $this->string($computer->fields['otherserial'])->isIdenticalTo($tag);
    }

    public function testBusinessRuleOnAddComputer()
    {
        global $DB;

        //prepare rule contents
        $state = new \State();
        $states_id = $state->add(['name' => 'Test status']);
        $this->integer($states_id)->isGreaterThan(0);

        $location = new \Location();
        $locations_id = $location->add(['name' => 'Test location']);
        $this->integer($locations_id)->isGreaterThan(0);

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
        $this->integer($rules_id)->isGreaterThan(0);

        //create criteria
        $input_criteria = [
            'rules_id'  => $rules_id,
            'criteria'      => '_itemtype',
            'condition' => \Rule::PATTERN_IS,
            'pattern' => \Computer::getType()
        ];
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add($input_criteria);
        $this->integer($rule_criteria_id)->isGreaterThan(0);

        //create actions
        $input_action = [
            'rules_id'  => $rules_id,
            'action_type' => 'assign',
            'field' => 'states_id',
            'value' => $states_id
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        $input_action = [
            'rules_id'  => $rules_id,
            'action_type' => 'assign',
            'field' => 'locations_id',
            'value' => $locations_id
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        $input_action = [
            'rules_id'  => $rules_id,
            'action_type' => 'assign',
            'field' => 'comment',
            'value' => 'A comment'
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        //ensure business rule work on regular Computer add
        $computer = new \Computer();
        $computers_id = $computer->add(['name' => 'Test computer', 'entities_id' => 0]);
        $this->integer($computers_id)->isGreaterThan(0);
        $this->boolean($computer->getFromDB($computers_id))->isTrue();

        $this->string($computer->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($computer->fields['states_id'])->isIdenticalTo($states_id);
        $this->integer($computer->fields['locations_id'])->isIdenticalTo($locations_id);

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
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();

        //check created computer
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($agent['items_id']))->isTrue();
        $this->string($computer->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($computer->fields['states_id'])->isIdenticalTo($states_id);
        $this->integer($computer->fields['locations_id'])->isIdenticalTo($locations_id);

        //redo inventory
        $this->doInventory($xml_source, true);
        $this->boolean($computer->getFromDB($agent['items_id']))->isTrue();
        $this->string($computer->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($computer->fields['states_id'])->isIdenticalTo($states_id);
        $this->integer($computer->fields['locations_id'])->isIdenticalTo($locations_id);
    }

    public function testBusinessRuleOnUpdateComputer()
    {
        global $DB;

        //prepare rule contents
        $state = new \State();
        $states_id = $state->add(['name' => 'Test status']);
        $this->integer($states_id)->isGreaterThan(0);

        $location = new \Location();
        $locations_id = $location->add(['name' => 'Test location']);
        $this->integer($locations_id)->isGreaterThan(0);

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
        $this->integer($rules_id)->isGreaterThan(0);

        //create criteria
        $input_criteria = [
            'rules_id' => $rules_id,
            'criteria' => '_itemtype',
            'condition' => \Rule::PATTERN_IS,
            'pattern' => \Computer::getType()
        ];
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add($input_criteria);
        $this->integer($rule_criteria_id)->isGreaterThan(0);

        //create actions
        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'states_id',
            'value' => $states_id
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'locations_id',
            'value' => $locations_id
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'comment',
            'value' => 'A comment'
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        //ensure business rule work on regular Computer add
        $computer = new \Computer();
        $computers_id = $computer->add(['name' => 'Test computer', 'entities_id' => 0]);
        $this->integer($computers_id)->isGreaterThan(0);
        $this->boolean($computer->getFromDB($computers_id))->isTrue();

        $this->variable($computer->fields['comment'])->isNull();
        $this->integer($computer->fields['states_id'])->isIdenticalTo(0);
        $this->integer($computer->fields['locations_id'])->isIdenticalTo(0);

        //update computer
        $this->boolean(
            $computer->update([
                'id' => $computers_id,
                'comment' => 'Another comment'
            ])
        )->isTrue();
        $this->boolean($computer->getFromDB($computers_id))->isTrue();

        $this->string($computer->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($computer->fields['states_id'])->isIdenticalTo($states_id);
        $this->integer($computer->fields['locations_id'])->isIdenticalTo($locations_id);

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
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();

        //check created computer
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($agent['items_id']))->isTrue();
        $this->variable($computer->fields['comment'])->isNull();
        $this->integer($computer->fields['states_id'])->isIdenticalTo(0);
        $this->integer($computer->fields['locations_id'])->isIdenticalTo(0);

        //redo inventory
        $this->doInventory($xml_source, true);
        $this->boolean($computer->getFromDB($agent['items_id']))->isTrue();
        $this->string($computer->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($computer->fields['states_id'])->isIdenticalTo($states_id);
        $this->integer($computer->fields['locations_id'])->isIdenticalTo($locations_id);
    }

    public function testBusinessRuleOnAddAndOnUpdateComputer()
    {
        global $DB;

        //prepare rule contents
        $state = new \State();
        $states_id = $state->add(['name' => 'Test status']);
        $this->integer($states_id)->isGreaterThan(0);

        $location = new \Location();
        $locations_id = $location->add(['name' => 'Test location']);
        $this->integer($locations_id)->isGreaterThan(0);

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
        $this->integer($rules_id)->isGreaterThan(0);

        //create criteria
        $input_criteria = [
            'rules_id' => $rules_id,
            'criteria' => '_itemtype',
            'condition' => \Rule::PATTERN_IS,
            'pattern' => \Computer::getType()
        ];
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add($input_criteria);
        $this->integer($rule_criteria_id)->isGreaterThan(0);

        //create actions
        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'states_id',
            'value' => $states_id
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'locations_id',
            'value' => $locations_id
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'comment',
            'value' => 'A comment'
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        //ensure business rule work on regular Computer add
        $computer = new \Computer();
        $computers_id = $computer->add(['name' => 'Test computer', 'entities_id' => 0]);
        $this->integer($computers_id)->isGreaterThan(0);
        $this->boolean($computer->getFromDB($computers_id))->isTrue();

        $this->string($computer->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($computer->fields['states_id'])->isIdenticalTo($states_id);
        $this->integer($computer->fields['locations_id'])->isIdenticalTo($locations_id);

        //update computer
        $this->boolean(
            $computer->update([
                'id' => $computers_id,
                'comment' => 'Another comment'
            ])
        )->isTrue();
        $this->boolean($computer->getFromDB($computers_id))->isTrue();

        $this->string($computer->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($computer->fields['states_id'])->isIdenticalTo($states_id);
        $this->integer($computer->fields['locations_id'])->isIdenticalTo($locations_id);

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
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();

        //check created computer
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($agent['items_id']))->isTrue();
        $this->string($computer->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($computer->fields['states_id'])->isIdenticalTo($states_id);
        $this->integer($computer->fields['locations_id'])->isIdenticalTo($locations_id);

        //redo inventory
        $this->doInventory($xml_source, true);
        $this->boolean($computer->getFromDB($agent['items_id']))->isTrue();
        $this->string($computer->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($computer->fields['states_id'])->isIdenticalTo($states_id);
        $this->integer($computer->fields['locations_id'])->isIdenticalTo($locations_id);
    }

    public function testBusinessRuleOnAddNetworkEquipment()
    {
        global $DB;

        //prepare rule contents
        $state = new \State();
        $states_id = $state->add(['name' => 'Test status']);
        $this->integer($states_id)->isGreaterThan(0);

        $location = new \Location();
        $locations_id = $location->add(['name' => 'Test location']);
        $this->integer($locations_id)->isGreaterThan(0);

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
        $this->integer($rules_id)->isGreaterThan(0);

        //create criteria
        $input_criteria = [
            'rules_id'  => $rules_id,
            'criteria'      => '_itemtype',
            'condition' => \Rule::PATTERN_IS,
            'pattern' => \NetworkEquipment::getType()
        ];
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add($input_criteria);
        $this->integer($rule_criteria_id)->isGreaterThan(0);

        //create actions
        $input_action = [
            'rules_id'  => $rules_id,
            'action_type' => 'assign',
            'field' => 'states_id',
            'value' => $states_id
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        $input_action = [
            'rules_id'  => $rules_id,
            'action_type' => 'assign',
            'field' => 'locations_id',
            'value' => $locations_id
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        $input_action = [
            'rules_id'  => $rules_id,
            'action_type' => 'assign',
            'field' => 'comment',
            'value' => 'A comment'
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        //ensure business rule work on regular Network Equipment add
        $neteq = new \NetworkEquipment();
        $networkeequipments_id = $neteq->add(['name' => 'Test network equipment', 'entities_id' => 0]);
        $this->integer($networkeequipments_id)->isGreaterThan(0);
        $this->boolean($neteq->getFromDB($networkeequipments_id))->isTrue();

        $this->string($neteq->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($neteq->fields['states_id'])->isIdenticalTo($states_id);
        $this->integer($neteq->fields['locations_id'])->isIdenticalTo($locations_id);

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
        $this->boolean($neteq->getFromDBByCrit(['serial' => 'SSI1912014B']))->isTrue();
        $this->string($neteq->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($neteq->fields['states_id'])->isIdenticalTo($states_id);
        $this->integer($neteq->fields['locations_id'])->isIdenticalTo($locations_id);

        //redo inventory
        $this->doInventory($xml_source, true);
        $this->boolean($neteq->getFromDBByCrit(['serial' => 'SSI1912014B']))->isTrue();
        $this->string($neteq->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($neteq->fields['states_id'])->isIdenticalTo($states_id);
        //location is not set by rule on update, but is set from inventory data
        $this->integer($neteq->fields['locations_id'])->isIdenticalTo(getItemByTypeName(\Location::class, 'paris.pa3', true));
    }

    public function testBusinessRuleOnUpdateNetworkEquipment()
    {
        global $DB;

        //prepare rule contents
        $state = new \State();
        $states_id = $state->add(['name' => 'Test status']);
        $this->integer($states_id)->isGreaterThan(0);

        $location = new \Location();
        $locations_id = $location->add(['name' => 'Test location']);
        $this->integer($locations_id)->isGreaterThan(0);

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
        $this->integer($rules_id)->isGreaterThan(0);

        //create criteria
        $input_criteria = [
            'rules_id' => $rules_id,
            'criteria' => '_itemtype',
            'condition' => \Rule::PATTERN_IS,
            'pattern' => \NetworkEquipment::getType()
        ];
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add($input_criteria);
        $this->integer($rule_criteria_id)->isGreaterThan(0);

        //create actions
        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'states_id',
            'value' => $states_id
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'locations_id',
            'value' => $locations_id
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'comment',
            'value' => 'A comment'
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        //ensure business rule work on regular Network equipment add
        $neteq = new \NetworkEquipment();
        $networkequipments_id = $neteq->add(['name' => 'Test network equipment', 'entities_id' => 0]);
        $this->integer($networkequipments_id)->isGreaterThan(0);
        $this->boolean($neteq->getFromDB($networkequipments_id))->isTrue();

        $this->variable($neteq->fields['comment'])->isNull();
        $this->integer($neteq->fields['states_id'])->isIdenticalTo(0);
        $this->integer($neteq->fields['locations_id'])->isIdenticalTo(0);

        //update network equipment
        $this->boolean(
            $neteq->update([
                'id' => $networkequipments_id,
                'comment' => 'Another comment'
            ])
        )->isTrue();
        $this->boolean($neteq->getFromDB($networkequipments_id))->isTrue();

        $this->string($neteq->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($neteq->fields['states_id'])->isIdenticalTo($states_id);
        $this->integer($neteq->fields['locations_id'])->isIdenticalTo($locations_id);

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
        $this->boolean($neteq->getFromDBByCrit(['serial' => 'SSI1912014B']))->isTrue();
        $this->variable($neteq->fields['comment'])->isNull();
        $this->integer($neteq->fields['states_id'])->isIdenticalTo(0);
        $this->integer($neteq->fields['locations_id'])->isIdenticalTo(getItemByTypeName(\Location::class, 'paris.pa3', true));

        //redo inventory
        $this->doInventory($xml_source, true);
        $this->boolean($neteq->getFromDBByCrit(['serial' => 'SSI1912014B']))->isTrue();
        $this->string($neteq->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($neteq->fields['states_id'])->isIdenticalTo($states_id);
        $this->integer($neteq->fields['locations_id'])->isIdenticalTo($locations_id);
    }

    public function testBusinessRuleOnAddAndOnUpdateNetworkEquipment()
    {
        global $DB;

        //prepare rule contents
        $state = new \State();
        $states_id = $state->add(['name' => 'Test status']);
        $this->integer($states_id)->isGreaterThan(0);

        $location = new \Location();
        $locations_id = $location->add(['name' => 'Test location']);
        $this->integer($locations_id)->isGreaterThan(0);

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
        $this->integer($rules_id)->isGreaterThan(0);

        //create criteria
        $input_criteria = [
            'rules_id' => $rules_id,
            'criteria' => '_itemtype',
            'condition' => \Rule::PATTERN_IS,
            'pattern' => \NetworkEquipment::getType()
        ];
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add($input_criteria);
        $this->integer($rule_criteria_id)->isGreaterThan(0);

        //create actions
        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'states_id',
            'value' => $states_id
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'locations_id',
            'value' => $locations_id
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'comment',
            'value' => 'A comment'
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        //ensure business rule work on regular Network equipment add
        $neteq = new \NetworkEquipment();
        $networkequipments_id = $neteq->add(['name' => 'Test network equipment', 'entities_id' => 0]);
        $this->integer($networkequipments_id)->isGreaterThan(0);
        $this->boolean($neteq->getFromDB($networkequipments_id))->isTrue();

        $this->string($neteq->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($neteq->fields['states_id'])->isIdenticalTo($states_id);
        $this->integer($neteq->fields['locations_id'])->isIdenticalTo($locations_id);

        //update network equipment
        $this->boolean(
            $neteq->update([
                'id' => $networkequipments_id,
                'comment' => 'Another comment'
            ])
        )->isTrue();
        $this->boolean($neteq->getFromDB($networkequipments_id))->isTrue();

        $this->string($neteq->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($neteq->fields['states_id'])->isIdenticalTo($states_id);
        $this->integer($neteq->fields['locations_id'])->isIdenticalTo($locations_id);

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
        $this->boolean($neteq->getFromDBByCrit(['serial' => 'SSI1912014B']))->isTrue();
        $this->string($neteq->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($neteq->fields['states_id'])->isIdenticalTo($states_id);
        $this->integer($neteq->fields['locations_id'])->isIdenticalTo($locations_id);

        //redo inventory
        $this->doInventory($xml_source, true);
        $this->boolean($neteq->getFromDBByCrit(['serial' => 'SSI1912014B']))->isTrue();
        $this->string($neteq->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($neteq->fields['states_id'])->isIdenticalTo($states_id);
        $this->integer($neteq->fields['locations_id'])->isIdenticalTo($locations_id);
    }

    public function testBusinessRuleOnAddPrinter()
    {
        global $DB;

        //prepare rule contents
        $state = new \State();
        $states_id = $state->add(['name' => 'Test status']);
        $this->integer($states_id)->isGreaterThan(0);

        $location = new \Location();
        $locations_id = $location->add(['name' => 'Test location']);
        $this->integer($locations_id)->isGreaterThan(0);

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
        $this->integer($rules_id)->isGreaterThan(0);

        //create criteria
        $input_criteria = [
            'rules_id'  => $rules_id,
            'criteria'      => '_itemtype',
            'condition' => \Rule::PATTERN_IS,
            'pattern' => \Printer::getType()
        ];
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add($input_criteria);
        $this->integer($rule_criteria_id)->isGreaterThan(0);

        //create actions
        $input_action = [
            'rules_id'  => $rules_id,
            'action_type' => 'assign',
            'field' => 'states_id',
            'value' => $states_id
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        $input_action = [
            'rules_id'  => $rules_id,
            'action_type' => 'assign',
            'field' => 'locations_id',
            'value' => $locations_id
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        $input_action = [
            'rules_id'  => $rules_id,
            'action_type' => 'assign',
            'field' => 'comment',
            'value' => 'A comment'
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        //ensure business rule work on regular printer add
        $printer = new \Printer();
        $printers_id = $printer->add(['name' => 'Test printer', 'entities_id' => 0]);
        $this->integer($printers_id)->isGreaterThan(0);
        $this->boolean($printer->getFromDB($printers_id))->isTrue();

        $this->string($printer->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($printer->fields['states_id'])->isIdenticalTo($states_id);
        $this->integer($printer->fields['locations_id'])->isIdenticalTo($locations_id);

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
        $this->boolean($printer->getFromDBByCrit(['serial' => 'E1234567890']))->isTrue();
        $this->string($printer->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($printer->fields['states_id'])->isIdenticalTo($states_id);
        $this->integer($printer->fields['locations_id'])->isIdenticalTo($locations_id);

        //redo inventory
        $this->doInventory($xml_source, true);
        $this->boolean($printer->getFromDBByCrit(['serial' => 'E1234567890']))->isTrue();
        $this->string($printer->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($printer->fields['states_id'])->isIdenticalTo($states_id);
        //location is not set by rule on update, but is set from inventory data
        $this->integer($printer->fields['locations_id'])->isIdenticalTo(getItemByTypeName(\Location::class, 'Location', true));
    }

    public function testBusinessRuleOnUpdatePrinter()
    {
        global $DB;

        //prepare rule contents
        $state = new \State();
        $states_id = $state->add(['name' => 'Test status']);
        $this->integer($states_id)->isGreaterThan(0);

        $location = new \Location();
        $locations_id = $location->add(['name' => 'Test location']);
        $this->integer($locations_id)->isGreaterThan(0);

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
        $this->integer($rules_id)->isGreaterThan(0);

        //create criteria
        $input_criteria = [
            'rules_id' => $rules_id,
            'criteria' => '_itemtype',
            'condition' => \Rule::PATTERN_IS,
            'pattern' => \Printer::getType()
        ];
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add($input_criteria);
        $this->integer($rule_criteria_id)->isGreaterThan(0);

        //create actions
        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'states_id',
            'value' => $states_id
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'locations_id',
            'value' => $locations_id
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'comment',
            'value' => 'A comment'
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        //ensure business rule work on regular printer add
        $printer = new \Printer();
        $printers_id = $printer->add(['name' => 'Test printer', 'entities_id' => 0]);
        $this->integer($printers_id)->isGreaterThan(0);
        $this->boolean($printer->getFromDB($printers_id))->isTrue();

        $this->variable($printer->fields['comment'])->isNull();
        $this->integer($printer->fields['states_id'])->isIdenticalTo(0);
        $this->integer($printer->fields['locations_id'])->isIdenticalTo(0);

        //update printer
        $this->boolean(
            $printer->update([
                'id' => $printers_id,
                'comment' => 'Another comment'
            ])
        )->isTrue();
        $this->boolean($printer->getFromDB($printers_id))->isTrue();

        $this->string($printer->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($printer->fields['states_id'])->isIdenticalTo($states_id);
        $this->integer($printer->fields['locations_id'])->isIdenticalTo($locations_id);

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
        $this->boolean($printer->getFromDBByCrit(['serial' => 'E1234567890']))->isTrue();
        $this->variable($printer->fields['comment'])->isNull();
        $this->integer($printer->fields['states_id'])->isIdenticalTo(0);
        $this->integer($printer->fields['locations_id'])->isIdenticalTo(getItemByTypeName(\Location::class, 'Location', true));

        //redo inventory
        $this->doInventory($xml_source, true);
        $this->boolean($printer->getFromDBByCrit(['serial' => 'E1234567890']))->isTrue();
        $this->string($printer->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($printer->fields['states_id'])->isIdenticalTo($states_id);
        $this->integer($printer->fields['locations_id'])->isIdenticalTo($locations_id);
    }

    public function testBusinessRuleOnAddAndOnUpdatePrinter()
    {
        global $DB;

        //prepare rule contents
        $state = new \State();
        $states_id = $state->add(['name' => 'Test status']);
        $this->integer($states_id)->isGreaterThan(0);

        $location = new \Location();
        $locations_id = $location->add(['name' => 'Test location']);
        $this->integer($locations_id)->isGreaterThan(0);

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
        $this->integer($rules_id)->isGreaterThan(0);

        //create criteria
        $input_criteria = [
            'rules_id' => $rules_id,
            'criteria' => '_itemtype',
            'condition' => \Rule::PATTERN_IS,
            'pattern' => \Printer::getType()
        ];
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add($input_criteria);
        $this->integer($rule_criteria_id)->isGreaterThan(0);

        //create actions
        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'states_id',
            'value' => $states_id
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'locations_id',
            'value' => $locations_id
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        $input_action = [
            'rules_id' => $rules_id,
            'action_type' => 'assign',
            'field' => 'comment',
            'value' => 'A comment'
        ];
        $rule_action = new \RuleAction();
        $this->integer($rule_action->add($input_action))->isGreaterThan(0);

        //ensure business rule work on regular printer add
        $printer = new \Printer();
        $printers_id = $printer->add(['name' => 'Test printer', 'entities_id' => 0]);
        $this->integer($printers_id)->isGreaterThan(0);
        $this->boolean($printer->getFromDB($printers_id))->isTrue();

        $this->string($printer->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($printer->fields['states_id'])->isIdenticalTo($states_id);
        $this->integer($printer->fields['locations_id'])->isIdenticalTo($locations_id);

        //update network equipment
        $this->boolean(
            $printer->update([
                'id' => $printers_id,
                'comment' => 'Another comment'
            ])
        )->isTrue();
        $this->boolean($printer->getFromDB($printers_id))->isTrue();

        $this->string($printer->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($printer->fields['states_id'])->isIdenticalTo($states_id);
        $this->integer($printer->fields['locations_id'])->isIdenticalTo($locations_id);

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
        $this->boolean($printer->getFromDBByCrit(['serial' => 'E1234567890']))->isTrue();
        $this->string($printer->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($printer->fields['states_id'])->isIdenticalTo($states_id);
        $this->integer($printer->fields['locations_id'])->isIdenticalTo($locations_id);

        //redo inventory
        $this->doInventory($xml_source, true);
        $this->boolean($printer->getFromDBByCrit(['serial' => 'E1234567890']))->isTrue();
        $this->string($printer->fields['comment'])->isIdenticalTo('A comment');
        $this->integer($printer->fields['states_id'])->isIdenticalTo($states_id);
        $this->integer($printer->fields['locations_id'])->isIdenticalTo($locations_id);
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
        $this->integer($rules_id)->isGreaterThan(0);

        //create criteria
        $input_criteria = [
            'rules_id'  => $rules_id,
            'criteria'      => '_auto',
            'condition' => \Rule::PATTERN_IS,
            'pattern' => '1'
        ];
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add($input_criteria);
        $this->integer($rule_criteria_id)->isGreaterThan(0);

        $state = new \State();
        $states_id = $state->add(['name' => 'test_status_if_inventory']);
        $this->integer($states_id)->isGreaterThan(0);

        //create action
        $input_action = [
            'rules_id'  => $rules_id,
            'action_type' => 'assign',
            'field' => 'states_id',
            'value' => $states_id
        ];
        $rule_action = new \RuleAction();
        $rule_action_id = $rule_action->add($input_action);
        $this->integer($rule_action_id)->isGreaterThan(0);

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
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();

        //check created computer
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($agent['items_id']))->isTrue();
        $this->integer($computer->fields['states_id'])->isIdenticalTo($states_id);

        //redo inventory
        $this->doInventory($xml_source, true);
        $this->boolean($computer->getFromDB($agent['items_id']))->isTrue();
        $this->integer($computer->fields['states_id'])->isIdenticalTo($states_id);
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
        $this->integer($rules_id)->isGreaterThan(0);

        //create criteria
        $input_criteria = [
            'rules_id'  => $rules_id,
            'criteria'      => '_auto',
            'condition' => \Rule::PATTERN_IS,
            'pattern' => '1'
        ];
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add($input_criteria);
        $this->integer($rule_criteria_id)->isGreaterThan(0);

        $state = new \State();
        $states_id = $state->add(['name' => 'test_status_if_inventory']);
        $this->integer($states_id)->isGreaterThan(0);

        //create action
        $input_action = [
            'rules_id'  => $rules_id,
            'action_type' => 'assign',
            'field' => 'states_id',
            'value' => $states_id
        ];
        $rule_action = new \RuleAction();
        $rule_action_id = $rule_action->add($input_action);
        $this->integer($rule_action_id)->isGreaterThan(0);

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
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();

        //check created computer
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($agent['items_id']))->isTrue();
        $this->integer($computer->fields['states_id'])->isIdenticalTo(0);

        //redo inventory
        $this->doInventory($xml_source, true);
        $this->boolean($computer->getFromDB($agent['items_id']))->isTrue();
        $this->integer($computer->fields['states_id'])->isIdenticalTo($states_id);
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
        $this->integer($rules_id)->isGreaterThan(0);

        //create criteria
        $input_criteria = [
            'rules_id'  => $rules_id,
            'criteria'      => '_auto',
            'condition' => \Rule::PATTERN_IS,
            'pattern' => '1'
        ];
        $rule_criteria = new \RuleCriteria();
        $rule_criteria_id = $rule_criteria->add($input_criteria);
        $this->integer($rule_criteria_id)->isGreaterThan(0);

        $state = new \State();
        $states_id = $state->add(['name' => 'test_status_if_inventory']);
        $this->integer($states_id)->isGreaterThan(0);

        //create action
        $input_action = [
            'rules_id'  => $rules_id,
            'action_type' => 'assign',
            'field' => 'states_id',
            'value' => $states_id
        ];
        $rule_action = new \RuleAction();
        $rule_action_id = $rule_action->add($input_action);
        $this->integer($rule_action_id)->isGreaterThan(0);

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
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();

        //check created computer
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($agent['items_id']))->isTrue();
        $this->integer($computer->fields['states_id'])->isIdenticalTo($states_id);

        //redo inventory
        $this->doInventory($xml_source, true);
        $this->boolean($computer->getFromDB($agent['items_id']))->isTrue();
        $this->integer($computer->fields['states_id'])->isIdenticalTo($states_id);
    }
}
