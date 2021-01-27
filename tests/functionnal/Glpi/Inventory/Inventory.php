<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

namespace tests\units\Glpi\Inventory;

use DbTestCase;
use wapmorgan\UnifiedArchive\UnifiedArchive;

class Inventory extends DbTestCase {

   public function testImportComputer() {
      $json = file_get_contents(GLPI_ROOT . '/tests/fixtures/inventory/computer_1.json');

      $nbprinters = countElementsInTable(\Printer::getTable());
      $inventory = new \Glpi\Inventory\Inventory($json);

      if ($inventory->inError()) {
         foreach ($inventory->getErrors() as $error) {
            var_dump($error);
         }
      }
      $this->boolean($inventory->inError())->isFalse();
      $this->array($inventory->getErrors())->isEmpty();

      //check inventory metadata
      $metadata = $inventory->getMetadata();
      $this->array($metadata)->hasSize(5)
         ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
         ->string['version']->isIdenticalTo('FusionInventory-Agent_v2.5.2-1.fc31')
         ->string['itemtype']->isIdenticalTo('Computer')
         ->string['tag']->isIdenticalTo('000005');
      $this->array($metadata['provider'])->hasSize(10);

      global $DB;
      //check created agent
      $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->next();
      $agents = $DB->request(['FROM' => \Agent::getTable()]);
      $this->integer(count($agents))->isIdenticalTo(1);
      $agent = $agents->next();
      $this->array($agent)
         ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
         ->string['name']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
         ->string['version']->isIdenticalTo('2.5.2-1.fc31')
         ->string['itemtype']->isIdenticalTo('Computer')
         ->integer['agenttypes_id']->isIdenticalTo($agenttype['id']);

      //get computer models, manufacturer, ...
      $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->next();
      $this->array($autoupdatesystems);
      $autoupdatesystems_id = $autoupdatesystems['id'];

      $cmodels = $DB->request(['FROM' => \ComputerModel::getTable(), 'WHERE' => ['name' => 'XPS 13 9350']])->next();
      $this->array($cmodels);
      $computermodels_id = $cmodels['id'];

      $ctypes = $DB->request(['FROM' => \ComputerType::getTable(), 'WHERE' => ['name' => 'Laptop']])->next();
      $this->array($ctypes);
      $computertypes_id = $ctypes['id'];

      $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Dell Inc.']])->next();
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
      ];
      $this->array($computer->fields)->isIdenticalTo($expected);

      //operating system
      $ios = new \Item_OperatingSystem();
      $iterator = $ios->getFromItem($computer);
      $record = $iterator->next();

      $expected = [
         'assocID' => $record['assocID'],
         'name' => 'Fedora 31 (Workstation Edition)',
         'version' => '31 (Workstation Edition)',
         'architecture' => 'x86_64',
         'servicepack' => null,
      ];
      $this->array($record)->isIdenticalTo($expected);

      //volumes
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
      while ($volume = $iterator->next()) {
         unset($volume['id']);
         unset($volume['date_mod']);
         unset($volume['date_creation']);
         $expected = $expecteds[$i];
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
      $monitor_link = $iterator->next();
      unset($monitor_link['date_mod']);
      unset($monitor_link['date_creation']);

      $mmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Sharp Corporation']])->next();
      $this->array($mmanuf);
      $manufacturers_id = $mmanuf['id'];

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
         'monitormodels_id' => 0,
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
      $this->string($monitor->fields['contact'])->isIdenticalTo('trasher/root');

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
            'instantiation_type' => 'NetworkPortEthernet',
            'mac' => '00:00:00:00:00:00',
         ], [
            'logical_number' => 1,
            'name' => 'enp57s0u1u4',
            'instantiation_type' => 'NetworkPortEthernet',
            'mac' => '00:e0:4c:68:01:db',
         ], [
            'logical_number' => 1,
            'name' => 'wlp58s0',
            'instantiation_type' => 'NetworkPortWifi',
            'mac' => '44:85:00:2b:90:bc',
         ], [
            'logical_number' => 0,
            'name' => 'virbr0',
            'instantiation_type' => 'NetworkPortEthernet',
            'mac' => '52:54:00:fa:20:0e',
         ], [
            'logical_number' => 0,
            'name' => 'virbr0-nic',
            'instantiation_type' => null,
            'mac' => '52:54:00:fa:20:0e',
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
      while ($port = $iterator->next()) {
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
            $ip = $ip_iterator->next();
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

         while ($row = $iterator->next()) {
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
         $this->integer(count($components[$type]))->isIdenticalTo($count);
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
            'Item_DeviceBattery' => [
               [
                  'items_id' => $computers_id,
                  'itemtype' => 'Computer',
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
                  'real_capacity' => 0
               ],
            ],
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
                  'serial' => null,
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

      //softwares
      $isoft = new \Item_SoftwareVersion();
      $iterator = $isoft->getFromItem($computer);
      $this->integer(count($iterator))->isIdenticalTo(6);

      $expecteds = [
         [
            'softname' => 'expat',
            'version' => '2.2.8-1.fc31',
            'dateinstall' => '2019-12-19',
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
      while ($soft = $iterator->next()) {
         $expected = $expecteds[$i];
         $this->array([
            'softname'     => $soft['softname'],
            'version'      => $soft['version'],
            'dateinstall'  => $soft['dateinstall']
         ])->isEqualTo($expected);
         ++$i;
      }

      //check printer
      $iterator = \Computer_Item::getTypeItems($computers_id, 'Printer');
      $this->integer(count($iterator))->isIdenticalTo(1);
      $printer_link = $iterator->next();
      unset($printer_link['date_mod']);
      unset($printer_link['date_creation']);

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
         'linkid' => $printer_link['linkid'],
         'glpi_computers_items_is_dynamic' => 1,
         'entity' => 0,
      ];
      $this->array($printer_link)->isIdenticalTo($expected);

      $printer = new \Printer();
      $this->boolean($printer->getFromDB($printer_link['id']))->isTrue();
      $this->boolean((bool)$printer->fields['is_dynamic'])->isTrue();
      $this->string($printer->fields['name'])->isIdenticalTo('Officejet_Pro_8600_34AF9E_');
      $this->string($printer->fields['contact'])->isIdenticalTo('trasher/root');

      $this->integer(countElementsInTable(\Printer::getTable()))->isIdenticalTo($nbprinters + 1);
   }

   public function testImportNetworkEquipment() {
      $json = file_get_contents(GLPI_ROOT . '/tests/fixtures/inventory/networkequipment_1.json');

      $inventory = new \Glpi\Inventory\Inventory($json);

      if ($inventory->inError()) {
         foreach ($inventory->getErrors() as $error) {
            var_dump($error);
         }
      }
      $this->boolean($inventory->inError())->isFalse();
      $this->array($inventory->getErrors())->isEmpty();

      //check inventory metadata
      $metadata = $inventory->getMetadata();

      $this->array($metadata)->hasSize(4)
         ->string['deviceid']->isIdenticalTo('foo')
         ->string['version']->isIdenticalTo('4.1')
         ->string['itemtype']->isIdenticalTo('NetworkEquipment');
      $this->array($metadata['provider'])->hasSize(0);

      global $DB;
      //check created agent
      $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->next();
      $agents = $DB->request(['FROM' => \Agent::getTable()]);
      //no agent with deviceid equals to "foo"
      $this->integer(count($agents))->isIdenticalTo(0);

      //get model, manufacturer, ...
      $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->next();
      $this->array($autoupdatesystems);
      $autoupdatesystems_id = $autoupdatesystems['id'];

      $cmodels = $DB->request(['FROM' => \NetworkEquipmentModel::getTable(), 'WHERE' => ['name' => 'UCS 6248UP 48-Port']])->next();
      $this->array($cmodels);
      $models_id = $cmodels['id'];

      $ctypes = $DB->request(['FROM' => \NetworkEquipmentType::getTable(), 'WHERE' => ['name' => 'Networking']])->next();
      $this->array($ctypes);
      $types_id = $ctypes['id'];

      $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Cisco']])->next();
      $this->array($cmanuf);
      $manufacturers_id = $cmanuf['id'];

      $cloc = $DB->request(['FROM' => \Location::getTable(), 'WHERE' => ['name' => 'paris.pa3']])->next();
      $this->array($cloc);
      $locations_id = $cloc['id'];

      //check created asset
      $equipments = $DB->request(['FROM' => \NetworkEquipment::getTable()]);
      //no agent with deviceid equals to "foo"
      $this->integer(count($equipments))->isIdenticalTo(1);
      $equipments_id = $equipments->next()['id'];

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
      while ($port = $iterator->next()) {
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
            while ($ip = $ip_iterator->next()) {
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

         while ($row = $iterator->next()) {
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

      while ($unmanaged = $unmanageds->next()) {
         $this->boolean(in_array($unmanaged['name'], array_keys($expecteds)))->isTrue($unmanaged['name']);
         $this->string($unmanaged['sysdescr'])->isIdenticalTo($expecteds[$unmanaged['name']]);
      }
   }

   public function testImportStackedNetworkEquipment() {
      $json = file_get_contents(GLPI_ROOT . '/tests/fixtures/inventory/networkequipment_2.json');

      $inventory = new \Glpi\Inventory\Inventory($json);

      if ($inventory->inError()) {
         foreach ($inventory->getErrors() as $error) {
            var_dump($error);
         }
      }
      $this->boolean($inventory->inError())->isFalse();
      $this->array($inventory->getErrors())->isEmpty();

      //check inventory metadata
      $metadata = $inventory->getMetadata();

      $this->array($metadata)->hasSize(4)
         ->string['deviceid']->isIdenticalTo('3k-1-pa3.glpi-project.infra-2020-12-31-11-28-51')
         ->string['version']->isIdenticalTo('4.1')
         ->string['itemtype']->isIdenticalTo('NetworkEquipment');
      $this->array($metadata['provider'])->hasSize(0);

      global $DB;
      //check created agent
      $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->next();
      $agents = $DB->request(['FROM' => \Agent::getTable()]);
      $this->integer(count($agents))->isIdenticalTo(1);
      $agent = $agents->next();
      $this->array($agent)
         ->string['deviceid']->isIdenticalTo('3k-1-pa3.glpi-project.infra-2020-12-31-11-28-51')
         ->string['name']->isIdenticalTo('3k-1-pa3.glpi-project.infra-2020-12-31-11-28-51')
         //->string['version']->isIdenticalTo('')
         ->string['itemtype']->isIdenticalTo('NetworkEquipment')
         ->integer['agenttypes_id']->isIdenticalTo($agenttype['id']);

      //get model, manufacturer, ...
      $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->next();
      $this->array($autoupdatesystems);
      $autoupdatesystems_id = $autoupdatesystems['id'];

      $cmodels = $DB->request(['FROM' => \NetworkEquipmentModel::getTable(), 'WHERE' => ['name' => 'WS-C3750G-48TS-S']])->next();
      $this->array($cmodels);
      $models_id = $cmodels['id'];

      $ctypes = $DB->request(['FROM' => \NetworkEquipmentType::getTable(), 'WHERE' => ['name' => 'Networking']])->next();
      $this->array($ctypes);
      $types_id = $ctypes['id'];

      $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Cisco']])->next();
      $this->array($cmanuf);
      $manufacturers_id = $cmanuf['id'];

      $cloc = $DB->request(['FROM' => \Location::getTable(), 'WHERE' => ['name' => 'paris.pa3']])->next();
      $this->array($cloc);
      $locations_id = $cloc['id'];

      //check created equipments
      $expected_count = 5;
      $iterator = $DB->request([
         'FROM'   => \NetworkEquipment::getTable()
      ]);
      $this->integer(count($iterator))->isIdenticalTo($expected_count);

      $main_expected = [
         'id' => null,
         'entities_id' => 0,
         'is_recursive' => 0,
         'name' => '3k-1-pa3.glpi-project.infra',
         'ram' => '128',
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

      while ($row = $iterator->next()) {
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
         while ($port = $ports_iterator->next()) {
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
               while ($ip = $ip_iterator->next()) {
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

            while ($row = $dev_iterator->next()) {
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
      while ($row = $db_vlans->next()) {
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
      /*while ($unmanaged = $unmanageds->next()) {
         foreach ($expecteds[$i] as $key => $value) {
            $this->variable($unmanaged[$key])->isEqualTo($value);
         }
         ++$i;
      }*/
   }

   public function testImportNetworkEquipmentMultiConnections() {
      $json = file_get_contents(GLPI_ROOT . '/tests/fixtures/inventory/networkequipment_3.json');

      $inventory = new \Glpi\Inventory\Inventory($json);

      if ($inventory->inError()) {
         foreach ($inventory->getErrors() as $error) {
            var_dump($error);
         }
      }
      $this->boolean($inventory->inError())->isFalse();
      $this->array($inventory->getErrors())->isEmpty();

      //check inventory metadata
      $metadata = $inventory->getMetadata();

      $this->array($metadata)->hasSize(4)
         ->string['deviceid']->isIdenticalTo('HP-2530-48G-2020-12-31-11-28-51')
         ->string['version']->isIdenticalTo('2.5')
         ->string['itemtype']->isIdenticalTo('NetworkEquipment');
      $this->array($metadata['provider'])->hasSize(0);

      global $DB;
      //check created agent
      $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->next();
      $agents = $DB->request(['FROM' => \Agent::getTable()]);
      $this->integer(count($agents))->isIdenticalTo(1);
      $agent = $agents->next();
      $this->array($agent)
         ->string['deviceid']->isIdenticalTo('HP-2530-48G-2020-12-31-11-28-51')
         ->string['name']->isIdenticalTo('HP-2530-48G-2020-12-31-11-28-51')
         //->string['version']->isIdenticalTo('')
         ->string['itemtype']->isIdenticalTo('NetworkEquipment')
         ->integer['agenttypes_id']->isIdenticalTo($agenttype['id']);

      //get model, manufacturer, ...
      $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->next();
      $this->array($autoupdatesystems);
      $autoupdatesystems_id = $autoupdatesystems['id'];

      $cmodels = $DB->request(['FROM' => \NetworkEquipmentModel::getTable(), 'WHERE' => ['name' => '2530-48G']])->next();
      $this->array($cmodels);
      $models_id = $cmodels['id'];

      $ctypes = $DB->request(['FROM' => \NetworkEquipmentType::getTable(), 'WHERE' => ['name' => 'Networking']])->next();
      $this->array($ctypes);
      $types_id = $ctypes['id'];

      $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Hewlett-Packard']])->next();
      $this->array($cmanuf);
      $manufacturers_id = $cmanuf['id'];

      /*$cloc = $DB->request(['FROM' => \Location::getTable(), 'WHERE' => ['name' => 'paris.pa3']])->next();
      $this->array($cloc);
      $locations_id = $cloc['id'];*/
      $locations_id = 0;

      //check created equipments
      $expected_count = 1;
      $iterator = $DB->request([
         'FROM'   => \NetworkEquipment::getTable()
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
      ];

      while ($row = $iterator->next()) {
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
         while ($port = $ports_iterator->next()) {
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
               while ($ip = $ip_iterator->next()) {
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

            while ($row = $dev_iterator->next()) {
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
            'name' => 'Ubiquiti Networks Inc.',
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
            'name' => 'Ubiquiti Networks Inc.',
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
            'name' => 'Ubiquiti Networks Inc.',
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
      while ($unmanaged = $unmanageds->next()) {
         foreach ($expecteds[$i] as $key => $value) {
            $this->variable($unmanaged[$key])->isEqualTo($value);
         }
         ++$i;
      }
   }

   public function testImportNetworkEquipmentWireless() {
      $json = file_get_contents(GLPI_ROOT . '/tests/fixtures/inventory/networkequipment_4.json');

      $inventory = new \Glpi\Inventory\Inventory($json);

      if ($inventory->inError()) {
         foreach ($inventory->getErrors() as $error) {
            var_dump($error);
         }
      }
      $this->boolean($inventory->inError())->isFalse();
      $this->array($inventory->getErrors())->isEmpty();

      //check inventory metadata
      $metadata = $inventory->getMetadata();

      $this->array($metadata)->hasSize(4)
         ->string['deviceid']->isIdenticalTo('CH-GV1-DSI-WLC-INSID-1-2020-12-31-11-28-51')
         ->string['version']->isIdenticalTo('4.1')
         ->string['itemtype']->isIdenticalTo('NetworkEquipment');
      $this->array($metadata['provider'])->hasSize(0);

      global $DB;
      //check created agent
      $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->next();
      $agents = $DB->request(['FROM' => \Agent::getTable()]);
      $this->integer(count($agents))->isIdenticalTo(1);
      $agent = $agents->next();
      $this->array($agent)
         ->string['deviceid']->isIdenticalTo('CH-GV1-DSI-WLC-INSID-1-2020-12-31-11-28-51')
         ->string['name']->isIdenticalTo('CH-GV1-DSI-WLC-INSID-1-2020-12-31-11-28-51')
         //->string['version']->isIdenticalTo('')
         ->string['itemtype']->isIdenticalTo('NetworkEquipment')
         ->integer['agenttypes_id']->isIdenticalTo($agenttype['id']);

      //get model, manufacturer, ...
      $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->next();
      $this->array($autoupdatesystems);
      $autoupdatesystems_id = $autoupdatesystems['id'];

      $cmodels = $DB->request(['FROM' => \NetworkEquipmentModel::getTable(), 'WHERE' => ['name' => 'CT5520']])->next();
      $this->array($cmodels);
      $models_id = $cmodels['id'];

      $ctypes = $DB->request(['FROM' => \NetworkEquipmentType::getTable(), 'WHERE' => ['name' => 'Networking']])->next();
      $this->array($ctypes);
      $types_id = $ctypes['id'];

      $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Cisco']])->next();
      $this->array($cmanuf);
      $manufacturers_id = $cmanuf['id'];

      $cloc = $DB->request(['FROM' => \Location::getTable(), 'WHERE' => ['name' => 'MERY']])->next();
      $this->array($cloc);
      $locations_id = $cloc['id'];

      //check created equipments
      $expected_count = 302;
      $iterator = $DB->request([
         'FROM'   => \NetworkEquipment::getTable()
      ]);
      $this->integer(count($iterator))->isIdenticalTo($expected_count);

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
      ];

      $first = true;
      while ($row = $iterator->next()) {
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
         while ($port = $ports_iterator->next()) {
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
               while ($ip = $ip_iterator->next()) {
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

            while ($row = $dev_iterator->next()) {
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
      while ($unmanaged = $unmanageds->next()) {
         foreach ($expecteds[$i] as $key => $value) {
            $this->variable($unmanaged[$key])->isEqualTo($value);
         }
         ++$i;
      }
   }

   public function testImportNetworkEquipmentWAggregatedPorts() {
      $json = file_get_contents(GLPI_ROOT . '/tests/fixtures/inventory/networkequipment_5.json');

      $inventory = new \Glpi\Inventory\Inventory($json);

      if ($inventory->inError()) {
         foreach ($inventory->getErrors() as $error) {
            var_dump($error);
         }
      }
      $this->boolean($inventory->inError())->isFalse();
      $this->array($inventory->getErrors())->isEmpty();

      //check inventory metadata
      $metadata = $inventory->getMetadata();

      $this->array($metadata)->hasSize(4)
         ->string['deviceid']->isIdenticalTo('DGS-3420-52T-2020-12-31-11-28-51')
         ->string['version']->isIdenticalTo('4.1')
         ->string['itemtype']->isIdenticalTo('NetworkEquipment');
      $this->array($metadata['provider'])->hasSize(0);

      global $DB;
      //check created agent
      $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->next();
      $agents = $DB->request(['FROM' => \Agent::getTable()]);
      $this->integer(count($agents))->isIdenticalTo(1);
      $agent = $agents->next();
      $this->array($agent)
         ->string['deviceid']->isIdenticalTo('DGS-3420-52T-2020-12-31-11-28-51')
         ->string['name']->isIdenticalTo('DGS-3420-52T-2020-12-31-11-28-51')
         //->string['version']->isIdenticalTo('')
         ->string['itemtype']->isIdenticalTo('NetworkEquipment')
         ->integer['agenttypes_id']->isIdenticalTo($agenttype['id']);

      //get model, manufacturer, ...
      $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->next();
      $this->array($autoupdatesystems);
      $autoupdatesystems_id = $autoupdatesystems['id'];

      $cmodels = $DB->request(['FROM' => \NetworkEquipmentModel::getTable(), 'WHERE' => ['name' => 'DGS-3420-52T']])->next();
      $this->array($cmodels);
      $models_id = $cmodels['id'];

      $ctypes = $DB->request(['FROM' => \NetworkEquipmentType::getTable(), 'WHERE' => ['name' => 'Networking']])->next();
      $this->array($ctypes);
      $types_id = $ctypes['id'];

      $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'D-Link']])->next();
      $this->array($cmanuf);
      $manufacturers_id = $cmanuf['id'];

      $cloc = $DB->request(['FROM' => \Location::getTable(), 'WHERE' => ['name' => 'WOB Serverraum']])->next();
      $this->array($cloc);
      $locations_id = $cloc['id'];

      //check created computer
      $equipments_id = $agent['items_id'];
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
      while ($port = $iterator->next()) {
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
            while ($ip = $ip_iterator->next()) {
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

         while ($row = $iterator->next()) {
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

   public function testImportRefusedFromAssetRules() {
      $json = file_get_contents(GLPI_ROOT . '/tests/fixtures/inventory/computer_1.json');
      $data = json_decode($json);
      unset($data->content->bios);
      unset($data->content->hardware->name);
      $json = json_encode($data);

      $inventory = new \Glpi\Inventory\Inventory($json);

      if ($inventory->inError()) {
         foreach ($inventory->getErrors() as $error) {
            var_dump($error);
         }
      }
      $this->boolean($inventory->inError())->isFalse();
      $this->array($inventory->getErrors())->isEmpty();

      //check inventory metadata
      $metadata = $inventory->getMetadata();
      $this->array($metadata)->hasSize(5)
         ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
         ->string['version']->isIdenticalTo('FusionInventory-Agent_v2.5.2-1.fc31')
         ->string['itemtype']->isIdenticalTo('Computer')
         ->string['tag']->isIdenticalTo('000005');
      $this->array($metadata['provider'])->hasSize(10);

      global $DB;
      //check created agent
      $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->next();
      $agents = $DB->request(['FROM' => \Agent::getTable()]);
      $this->integer(count($agents))->isIdenticalTo(1);
      $agent = $agents->next();
      $this->array($agent)
         ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
         ->string['name']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
         ->string['version']->isIdenticalTo('2.5.2-1.fc31')
         ->string['itemtype']->isIdenticalTo('Computer')
         ->integer['agenttypes_id']->isIdenticalTo($agenttype['id']);

      $computers_id = $agent['items_id'];
      $this->integer($computers_id)->isIdenticalTo(0);

      $iterator = $DB->request([
         'FROM'   => \RefusedEquipment::getTable(),
      ]);
      $this->integer(count($iterator))->isIdenticalTo(1);

      $result = $iterator->next();
      $expected = [
         'id' => $result['id'],
         'name' => '',
         'itemtype' => 'Computer',
         'entities_id' => 0,
         'ip' => '["192.168.1.142","fe80::b283:4fa3:d3f2:96b1","192.168.1.118","fe80::92a4:26c6:99dd:2d60"]',
         'mac' => '["00:e0:4c:68:01:db","44:85:00:2b:90:bc"]',
         'rules_id' => $result['rules_id'],
         'method' => null,
         'serial' => '',
         'uuid' => '4c4c4544-0034-3010-8048-b6c04f503732',
         'agents_id' => 0,
         'date_creation' => $result['date_creation'],
         'date_mod' => $result['date_mod']

      ];

      $this->array($result)->isEqualTo($expected);
   }

   public function testImportRefusedFromEntitiesRules() {
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
            'criteria'  => "name",
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

      $json = file_get_contents(GLPI_ROOT . '/tests/fixtures/inventory/computer_1.json');
      $data = json_decode($json);
      unset($data->content->bios);
      unset($data->content->hardware->name);
      $json = json_encode($data);

      $inventory = new \Glpi\Inventory\Inventory($json);

      if ($inventory->inError()) {
         foreach ($inventory->getErrors() as $error) {
            var_dump($error);
         }
      }
      $this->boolean($inventory->inError())->isFalse();
      $this->array($inventory->getErrors())->isEmpty();

      //check inventory metadata
      $metadata = $inventory->getMetadata();
      $this->array($metadata)->hasSize(5)
         ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
         ->string['version']->isIdenticalTo('FusionInventory-Agent_v2.5.2-1.fc31')
         ->string['itemtype']->isIdenticalTo('Computer')
         ->string['tag']->isIdenticalTo('000005');
      $this->array($metadata['provider'])->hasSize(10);

      global $DB;
      //check created agent
      $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->next();
      $agents = $DB->request(['FROM' => \Agent::getTable()]);
      $this->integer(count($agents))->isIdenticalTo(1);
      $agent = $agents->next();
      $this->array($agent)
         ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
         ->string['name']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
         ->string['version']->isIdenticalTo('2.5.2-1.fc31')
         ->string['itemtype']->isIdenticalTo('Computer')
         ->integer['agenttypes_id']->isIdenticalTo($agenttype['id']);

      $computers_id = $agent['items_id'];
      $this->integer($computers_id)->isIdenticalTo(0);

      $iterator = $DB->request([
         'FROM'   => \RefusedEquipment::getTable(),
      ]);
      $this->integer(count($iterator))->isIdenticalTo(1);

      $result = $iterator->next();
      $expected = [
         'id' => $result['id'],
         'name' => '',
         'itemtype' => 'Computer',
         'entities_id' => 0,
         'ip' => '["192.168.1.142","fe80::b283:4fa3:d3f2:96b1","192.168.1.118","fe80::92a4:26c6:99dd:2d60"]',
         'mac' => '["00:e0:4c:68:01:db","44:85:00:2b:90:bc"]',
         'rules_id' => $result['rules_id'],
         'method' => null,
         'serial' => '',
         'uuid' => '4c4c4544-0034-3010-8048-b6c04f503732',
         'agents_id' => 0,
         'date_creation' => $result['date_creation'],
         'date_mod' => $result['date_mod']

      ];

      $this->array($result)->isEqualTo($expected);
   }

   public function testImportFiles() {
      $nbcomputers = countElementsInTable(\Computer::getTable());
      $nbprinters = countElementsInTable(\Printer::getTable());

      $json_path = GLPI_ROOT . '/tests/fixtures/inventory/computer_1.json';
      $files = [
         'importfile' => [
            'name' => 'computer_1.json',
            'type' => 'application/json',
            'tmp_name' => $json_path,
            'error' => 0,
            'size' => filesize($json_path)
         ]
      ];

      $conf = new \Glpi\Inventory\Conf();
      $conf->importFile($files);
      $this->hasSessionMessages(
         INFO, [
            'Alternate username updated. The connected items have been updated using this alternate username.',
            'File has been successfully imported!'
         ]
      );

      //1 computer and 1 printer has been inventoried
      $nbcomputers++;
      $nbprinters++;

      $this->integer($nbcomputers)->isIdenticalTo(countElementsInTable(\Computer::getTable()));
      $this->integer($nbprinters)->isIdenticalTo(countElementsInTable(\Printer::getTable()));
   }

   /**
    * @extensions zip
    */
   public function testImportArchive() {
      $nbcomputers = countElementsInTable(\Computer::getTable());
      $nbprinters = countElementsInTable(\Printer::getTable());
      $nbnetequps = countElementsInTable(\NetworkEquipment::getTable());

      $json_paths = [
         GLPI_ROOT . '/tests/fixtures/inventory/computer_1.json',
         GLPI_ROOT . '/tests/fixtures/inventory/networkequipment_1.json',
         GLPI_ROOT . '/tests/fixtures/inventory/printer_1.json',
      ];

      $archive_path = GLPI_TMP_DIR . '/to_inventory.zip';
      if (file_exists($archive_path)) {
         unlink($archive_path);
      }
      UnifiedArchive::archiveFiles($json_paths, $archive_path);

      $files = [
         'importfile' => [
            'name' => 'to_inventory.zip',
            'type' => 'application/zip',
            'tmp_name' => GLPI_TMP_DIR . '/to_inventory.zip',
            'error' => 0,
            'size' => filesize($archive_path)
         ]
      ];

      $conf = new \Glpi\Inventory\Conf();
      $conf->importFile($files);
      $this->hasSessionMessages(
         INFO, [
            'Alternate username updated. The connected items have been updated using this alternate username.',
            'File has been successfully imported!'
         ]
      );

      //1 computer 2 printers and a network equipment has been inventoried
      $nbcomputers++;
      $nbprinters += 2;
      $nbnetequps++;

      $this->integer($nbcomputers)->isIdenticalTo(countElementsInTable(\Computer::getTable()));
      $this->integer($nbprinters)->isIdenticalTo(countElementsInTable(\Printer::getTable()));
      $this->integer($nbnetequps)->isIdenticalTo(countElementsInTable(\NetworkEquipment::getTable()));
   }

   public function testImportVirtualMachines() {
      $json = file_get_contents(GLPI_ROOT . '/tests/fixtures/inventory/computer_2.json');

      $count_vms = count(json_decode($json)->content->virtualmachines);
      $this->integer($count_vms)->isIdenticalTo(6);

      $nb_vms = countElementsInTable(\ComputerVirtualMachine::getTable());
      $nb_computers = countElementsInTable(\Computer::getTable());
      $inventory = new \Glpi\Inventory\Inventory($json);

      if ($inventory->inError()) {
         $this->dump($inventory->getErrors());
      }
      $this->boolean($inventory->inError())->isFalse();
      $this->array($inventory->getErrors())->isEmpty();

      //check inventory metadata
      $metadata = $inventory->getMetadata();
      $this->array($metadata)->hasSize(4)
         ->string['deviceid']->isIdenticalTo('acomputer-2021-01-26-14-32-36')
         ->string['itemtype']->isIdenticalTo('Computer');

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

      $inventory = new \Glpi\Inventory\Inventory($json);

      if ($inventory->inError()) {
         $this->dump($inventory->getErrors());
      }
      $this->boolean($inventory->inError())->isFalse();
      $this->array($inventory->getErrors())->isEmpty();

      //check inventory metadata
      $metadata = $inventory->getMetadata();
      $this->array($metadata)->hasSize(4)
         ->string['deviceid']->isIdenticalTo('acomputer-2021-01-26-14-32-36')
         ->string['itemtype']->isIdenticalTo('Computer');

      //check we add main computer and one computer per vm
      //one does not have an uuid, so no computer is created.
      $this->integer(countElementsInTable(\Computer::getTable()))->isIdenticalTo($nb_computers + $count_vms - 1);
      //check created vms
      $this->integer(countElementsInTable(\ComputerVirtualMachine::getTable()))->isIdenticalTo($nb_vms);
   }
}
