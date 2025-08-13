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

namespace tests\units\Glpi\Inventory;

use Glpi\Asset\Asset_PeripheralAsset;
use Glpi\Inventory\Conf;
use InventoryTestCase;

class InventoryOptionsTest extends InventoryTestCase
{
    private string $json_computer = <<<JSON
{
    "action": "inventory",
    "content": {
        "bios": {
            "msn": "190653777602969"
        },
        "hardware": {
            "name": "Unit Tests Computer",
            "uuid": "ea38cc5b-92eb-7777-ec5e-04d9f521c6e3"
        },
        "versionclient": "GLPI-Agent_test"
    },
    "deviceid": "unit-test-computer",
    "itemtype": "Computer"
}
JSON;

    public function testImportComputerNoDevices()
    {
        global $DB;

        $devices = [
            'component_processor' => [
                'node' => [
                    'name' => 'cpus',
                    'contents' => [
                        (object) [
                            'arch' => 'x86_64',
                            'name' => 'Intel Core i7',
                        ],
                    ],
                ],
                'itemtype' => \Item_DeviceProcessor::class,

            ],
            'component_memory' => [
                'node' => [
                    'name' => 'memories',
                    'contents' => [
                        (object) [
                            'capacity' => 8192,
                            'type' => 'DDR4',
                        ],
                    ],
                ],
                'itemtype' => \Item_DeviceMemory::class,
            ],
            'component_networkcard' => [
                'node' => [
                    'name' => 'networks',
                    'contents' => [
                        (object) [
                            'description' => 'Ethernet card',
                            'mac' => '01:AB:23:CD:4E:F5',
                        ],
                    ],
                ],
                'itemtype' => \Item_DeviceNetworkCard::class,
            ],
            'component_graphiccard' => [
                'node' => [
                    'name' => 'videos',
                    'contents' => [
                        (object) [
                            'name' => 'My video card',
                            'memory' => 32768,
                        ],
                    ],
                ],
                'itemtype' => \Item_DeviceGraphicCard::class,
            ],
            'component_drive' => [
                'node' => [
                    'name' => 'storages',
                    'contents' => [
                        (object) [
                            'type' => 'DVD Writer',
                        ],
                    ],
                ],
                'itemtype' => \Item_DeviceDrive::class,
            ],
            'component_control' => [
                'node' => [
                    'name' => 'controllers',
                    'contents' => [
                        (object) [
                            'caption' => 'Wireless 8260',
                            'driver' => 'iwlwifi',
                            'name' => 'Wireless 8260',
                            'productid' => '24f3',
                            'type' => 'Network controller',
                            'vendorid' => '8086',
                        ],
                    ],
                ],
                'itemtype' => \Item_DeviceControl::class,
            ],
            'component_harddrive' => [
                'node' => [
                    'name' => 'storages',
                    'contents' => [
                        (object) [
                            'name' => 'My hard drive',
                        ],
                    ],
                ],
                'itemtype' => \Item_DeviceHardDrive::class,
            ],
            'component_soundcard' => [
                'node' => [
                    'name' => 'sounds',
                    'contents' => [
                        (object) [
                            'name' => 'Audio device',
                        ],
                    ],
                ],
                'itemtype' => \Item_DeviceSoundCard::class,
            ],
            'component_networkcardvirtual' => [
                'node' => [
                    'name' => 'networks',
                    'contents' => [
                        (object) [
                            'description' => 'Virtual Ethernet card',
                            'mac' => '01:AB:23:CD:4E:F5',
                            'virtualdev' => true,
                        ],
                    ],
                ],
                'itemtype' => \Item_DeviceNetworkCard::class,
            ],
            'component_simcard' => [
                'node' => [
                    'name' => 'simcards',
                    'contents' => [
                        (object) [
                            'serial' => 'azerty',
                        ],
                    ],
                ],
                'itemtype' => \Item_DeviceSimcard::class,
            ],
            'component_powersupply' => [
                'node' => [
                    'name' => 'powersupplies',
                    'contents' => [
                        (object) [
                            'name' => 'Power supply',
                        ],
                    ],
                ],
                'itemtype' => \Item_DevicePowerSupply::class,
            ],
            'component_battery' => [
                'node' => [
                    'name' => 'batteries',
                    'contents' => [
                        (object) [
                            'capacity' => 50000,
                        ],
                    ],
                ],
                'itemtype' => \Item_DeviceBattery::class,
            ],
        ];

        foreach ($devices as $config_name => $device) {
            $json = json_decode($this->json_computer);
            $json->content->{$device['node']['name']} = $device['node']['contents'];

            //disable device import
            $this->login();
            $conf = new Conf();
            $this->assertTrue(
                $conf->saveConf([
                    $config_name => 0,
                ])
            );
            $this->logout();

            $inventory = $this->doInventory($json);
            $computer = $inventory->getItem();
            $this->assertSame('Unit Tests Computer', $computer->fields['name']);

            //check no device has been created
            $item_devices = $DB->request([
                'FROM' => $device['itemtype']::getTable(),
                'WHERE' => [
                    'itemtype' => get_class($computer),
                    'items_id' => $computer->getID(),
                ],
            ]);
            $this->assertCount(0, $item_devices);

            //enable device import
            $this->login();
            $conf = new Conf();
            $this->assertTrue(
                $conf->saveConf([
                    $config_name => 1,
                ])
            );
            $this->logout();

            $this->doInventory($json);

            //check device has been created
            $item_devices = $DB->request([
                'FROM' => $device['itemtype']::getTable(),
                'WHERE' => [
                    'itemtype' => get_class($computer),
                    'items_id' => $computer->getID(),
                ],
            ]);
            $this->assertCount(1, $item_devices);
        }
    }

    public function testImportVolumes()
    {
        global $DB;

        $orig_json = json_decode($this->json_computer);
        //Work with 2 harddrives, 1 network drive and 1 removable drive
        $orig_json->content->drives = [
            (object) [
                'filesystem' => 'ext4',
                'type' => '/home',
                'volumn' => '/dev/mapper/vg-home',
                'total' => 10240000,
                'free' => 5120000,
            ],
            (object) [
                'filesystem' => 'ext4',
                'type' => '/',
                'volumn' => '/dev/mapper/vg-root',
                'total' => 51200,
                'free' => 25600,
            ],
            (object) [
                'filesystem' => 'nfs',
                'type' => '/backups',
                'volumn' => 'storage.local:/mnt/storage/backups',
            ],
            (object) [
                'filesystem' => 'ntfs',
                'type' => 'Removable Disk',
                'volumn' => '/dev/sdd1',
            ],
        ];
        $json = json_decode(json_encode($orig_json));

        //disable volume import
        $this->login();
        $conf = new Conf();
        $this->assertTrue(
            $conf->saveConf([
                'import_volume' => 0,
            ])
        );
        $this->logout();

        $inventory = $this->doInventory($json);
        $computer = $inventory->getItem();
        $this->assertSame('Unit Tests Computer', $computer->fields['name']);

        //check no item disk has been created
        $item_disks = $DB->request([
            'FROM' => \Item_Disk::getTable(),
            'WHERE' => [
                'itemtype' => get_class($computer),
                'items_id' => $computer->getID(),
            ],
        ]);
        $this->assertCount(0, $item_disks);

        //enable volume import, but not network or removable
        $this->login();
        $conf = new Conf();
        $this->assertTrue(
            $conf->saveConf([
                'import_volume' => 1,
                'component_networkdrive' => 0,
                'component_removablemedia' => 0,
            ])
        );
        $this->logout();

        $this->doInventory($json);

        //check item drive has been created
        $item_disks = $DB->request([
            'FROM' => \Item_Disk::getTable(),
            'WHERE' => [
                'itemtype' => get_class($computer),
                'items_id' => $computer->getID(),
            ],
        ]);
        $this->assertCount(2, $item_disks);


        //enable all volume import
        $this->login();
        $conf = new Conf();
        $this->assertTrue(
            $conf->saveConf([
                'component_networkdrive' => 1,
                'component_removablemedia' => 1,
            ])
        );
        $this->logout();

        $json = json_decode(json_encode($orig_json));
        $this->doInventory($json);

        //check item drive has been created
        $item_disks = $DB->request([
            'FROM' => \Item_Disk::getTable(),
            'WHERE' => [
                'itemtype' => get_class($computer),
                'items_id' => $computer->getID(),
            ],
        ]);
        $this->assertCount(4, $item_disks);
    }

    public function testImportNetworkCards()
    {
        global $DB;

        $orig_json = json_decode($this->json_computer);
        //Work with one network card, and one virtual network card
        $partial_mac = ':ab:23:cd:4e:f5';
        $orig_json->content->networks = [
            (object) [
                'description' => 'Ethernet card',
                'mac' => '01' . $partial_mac,
                'virtualdev' => false,
            ],
            (object) [
                'description' => 'Virtual Ethernet card',
                'mac' => '02' . $partial_mac,
                'virtualdev' => true,
            ],
        ];
        $json = json_decode(json_encode($orig_json));

        //disable network cards import
        $this->login();
        $conf = new Conf();
        $this->assertTrue(
            $conf->saveConf([
                'component_networkcard' => 0,
            ])
        );
        $this->logout();

        $inventory = $this->doInventory($json);
        $computer = $inventory->getItem();
        $this->assertSame('Unit Tests Computer', $computer->fields['name']);

        //check no network card has been created
        $item_devices = $DB->request([
            'FROM' => \Item_DeviceNetworkCard::getTable(),
            'WHERE' => [
                'itemtype' => get_class($computer),
                'items_id' => $computer->getID(),
            ],
        ]);
        $this->assertCount(0, $item_devices);

        //enable network card import, but not virtuals
        $this->login();
        $conf = new Conf();
        $this->assertTrue(
            $conf->saveConf([
                'component_networkcard' => 1,
                'component_networkcardvirtual' => 0,
            ])
        );
        $this->logout();

        $this->doInventory($json);

        //check non virtual card has been created
        $item_devices = $DB->request([
            'FROM' => \Item_DeviceNetworkCard::getTable(),
            'WHERE' => [
                'itemtype' => get_class($computer),
                'items_id' => $computer->getID(),
            ],
        ]);
        $this->assertCount(1, $item_devices);
        $this->assertSame($item_devices->current()['mac'], '01' . $partial_mac);

        //enable virtual network cards import
        $this->login();
        $conf = new Conf();
        $this->assertTrue(
            $conf->saveConf([
                'component_networkcardvirtual' => 1,
            ])
        );
        $this->logout();

        $json = json_decode(json_encode($orig_json));
        $this->doInventory($json);

        //check both network cards has been created
        $item_devices = $DB->request([
            'FROM' => \Item_DeviceNetworkCard::getTable(),
            'WHERE' => [
                'itemtype' => get_class($computer),
                'items_id' => $computer->getID(),
            ],
        ]);
        $this->assertCount(2, $item_devices);
    }

    public function testImportPeripherals()
    {
        global $DB;

        $json = json_decode($this->json_computer);
        $json->content->usbdevices = [
            (object) [
                'name' => 'VFS451 Fingerprint Reader',
                'productid' => '0007',
                'serial' => '00B0FE47AC85',
                'vendorid' => '138A',
            ],
        ];

        //disable peripherals import
        $this->login();
        $conf = new Conf();
        $this->assertTrue(
            $conf->saveConf([
                'import_peripheral' => 0,
            ])
        );
        $this->logout();

        $inventory = $this->doInventory($json);
        $computer = $inventory->getItem();
        $this->assertSame('Unit Tests Computer', $computer->fields['name']);

        //check no peripheral has been created
        $peripherals = $DB->request([
            'FROM' => \Peripheral::getTable(),
            'WHERE' => [
                'name' => $json->content->usbdevices[0]->name,
            ],
        ]);
        $this->assertCount(0, $peripherals);
        $item_peripherals = $DB->request([
            'FROM' => Asset_PeripheralAsset::getTable(),
            'WHERE' => [
                'itemtype_asset'      => 'Computer',
                'items_id_asset'      => $computer->getID(),
                'itemtype_peripheral' => \Peripheral::class,
            ],
        ]);
        $this->assertCount(0, $item_peripherals);

        //enable peripherals import
        $this->login();
        $conf = new Conf();
        $this->assertTrue(
            $conf->saveConf([
                'import_peripheral' => 1,
            ])
        );
        $this->logout();

        $this->doInventory($json);

        //check peripherals has been created
        $peripherals = $DB->request([
            'FROM' => \Peripheral::getTable(),
            'WHERE' => [
                'name' => $json->content->usbdevices[0]->name,
            ],
        ]);
        $this->assertCount(1, $peripherals);
        $item_peripherals = $DB->request([
            'FROM' => Asset_PeripheralAsset::getTable(),
            'WHERE' => [
                'itemtype_asset'      => 'Computer',
                'items_id_asset'      => $computer->getID(),
                'itemtype_peripheral' => \Peripheral::class,
            ],
        ]);
        $this->assertCount(1, $item_peripherals);
    }

    public function testImportMonitors()
    {
        global $DB;

        $json = json_decode($this->json_computer);
        $json->content->monitors = [
            (object) [
                'caption' => 'DJCP6',
                'serial' => 'AFGHHDR0',
            ],
        ];

        //disable monitors import
        $this->login();
        $conf = new Conf();
        $this->assertTrue(
            $conf->saveConf([
                'import_monitor' => 0,
            ])
        );
        $this->logout();

        $inventory = $this->doInventory($json);
        $computer = $inventory->getItem();
        $this->assertSame('Unit Tests Computer', $computer->fields['name']);

        //check no monitor has been created
        $monitors = $DB->request([
            'FROM' => \Monitor::getTable(),
            'WHERE' => [
                'name' => $json->content->monitors[0]->caption,
            ],
        ]);
        $this->assertCount(0, $monitors);
        $item_devices = $DB->request([
            'FROM' => Asset_PeripheralAsset::getTable(),
            'WHERE' => [
                'itemtype_asset'      => 'Computer',
                'items_id_asset'      => $computer->getID(),
                'itemtype_peripheral' => \Monitor::class,
            ],
        ]);
        $this->assertCount(0, $item_devices);

        //enable monitor import
        $this->login();
        $conf = new Conf();
        $this->assertTrue(
            $conf->saveConf([
                'import_monitor' => 1,
            ])
        );
        $this->logout();

        $this->doInventory($json);

        //check monitors has been created
        $monitors = $DB->request([
            'FROM' => \Monitor::getTable(),
            'WHERE' => [
                'name' => $json->content->monitors[0]->caption,
            ],
        ]);
        $this->assertCount(1, $monitors);
        $item_devices = $DB->request([
            'FROM' => Asset_PeripheralAsset::getTable(),
            'WHERE' => [
                'itemtype_asset'      => 'Computer',
                'items_id_asset'      => $computer->getID(),
                'itemtype_peripheral' => \Monitor::class,
            ],
        ]);
        $this->assertCount(1, $item_devices);
    }

    public function testImportPrinters()
    {
        global $DB;

        $json = json_decode($this->json_computer);
        $json->content->printers = [
            (object) [
                'name' => 'HP Color LaserJet Pro MFP M476 PCL 6',
                'serial' => 'printerserial',
            ],
        ];

        //disable printers import
        $this->login();
        $conf = new Conf();
        $this->assertTrue(
            $conf->saveConf([
                'import_printer' => 0,
            ])
        );
        $this->logout();

        $inventory = $this->doInventory($json);
        $computer = $inventory->getItem();
        $this->assertSame('Unit Tests Computer', $computer->fields['name']);

        //check no printer has been created
        $printers = $DB->request([
            'FROM' => \Printer::getTable(),
            'WHERE' => [
                'name' => $json->content->printers[0]->name,
            ],
        ]);
        $this->assertCount(0, $printers);
        $item_devices = $DB->request([
            'FROM' => Asset_PeripheralAsset::getTable(),
            'WHERE' => [
                'itemtype_asset'      => 'Computer',
                'items_id_asset'      => $computer->getID(),
                'itemtype_peripheral' => \Printer::class,
            ],
        ]);
        $this->assertCount(0, $item_devices);

        //enable printer import
        $this->login();
        $conf = new Conf();
        $this->assertTrue(
            $conf->saveConf([
                'import_printer' => 1,
            ])
        );
        $this->logout();

        $this->doInventory($json);

        //check printers has been created
        $printers = $DB->request([
            'FROM' => \Printer::getTable(),
            'WHERE' => [
                'name' => $json->content->printers[0]->name,
            ],
        ]);
        $this->assertCount(1, $printers);
        $item_devices = $DB->request([
            'FROM' => Asset_PeripheralAsset::getTable(),
            'WHERE' => [
                'itemtype_asset'      => 'Computer',
                'items_id_asset'      => $computer->getID(),
                'itemtype_peripheral' => \Printer::class,
            ],
        ]);
        $this->assertCount(1, $item_devices);
    }

    public function testImportSoftware()
    {
        global $DB;

        $json = json_decode($this->json_computer);
        $json->content->softwares = [
            (object) [
                'arch' => 'x86_64',
                'name' => 'My Software',
                'version' => '1.0',
            ],
        ];

        //disable software import
        $this->login();
        $conf = new Conf();
        $this->assertTrue(
            $conf->saveConf([
                'import_software' => 0,
            ])
        );
        $this->logout();

        $inventory = $this->doInventory($json);
        $computer = $inventory->getItem();
        $this->assertSame('Unit Tests Computer', $computer->fields['name']);

        //check no software has been created
        $software = $DB->request([
            'FROM' => \Software::getTable(),
            'WHERE' => [
                'name' => 'My Software',
            ],
        ]);
        $this->assertCount(0, $software);
        $item_software = $DB->request([
            'FROM' => \Item_SoftwareVersion::getTable(),
            'WHERE' => [
                'items_id' => $computer->getID(),
                'itemtype' => \Computer::class,
            ],
        ]);
        $this->assertCount(0, $item_software);

        //enable software import
        $this->login();
        $conf = new Conf();
        $this->assertTrue(
            $conf->saveConf([
                'import_software' => 1,
            ])
        );
        $this->logout();

        $this->doInventory($json);

        //check software has been created
        $software = $DB->request([
            'FROM' => \Software::getTable(),
            'WHERE' => [
                'name' => 'My Software',
            ],
        ]);
        $this->assertCount(1, $software);
        $item_software = $DB->request([
            'FROM' => \Item_SoftwareVersion::getTable(),
            'WHERE' => [
                'items_id' => $computer->getID(),
                'itemtype' => \Computer::class,
            ],
        ]);
        $this->assertCount(1, $item_software);
    }

    public function testImportAntivirus()
    {
        global $DB;

        $json = json_decode($this->json_computer);
        $json->content->antivirus = [
            (object) [
                'name' => 'Cartapus Antivirus',
                'version' => '1.2.3.4.5678',
                'enabled' => true,
                'guid' => '12345678-1234-1234-1234-123456789012',
            ],
        ];

        //disable antivirus import
        $this->login();
        $conf = new Conf();
        $this->assertTrue(
            $conf->saveConf([
                'import_antivirus' => 0,
            ])
        );
        $this->logout();

        $inventory = $this->doInventory($json);
        $computer = $inventory->getItem();
        $this->assertSame('Unit Tests Computer', $computer->fields['name']);

        //check no antivirus has been created
        $item_av = $DB->request([
            'FROM' => \ItemAntivirus::getTable(),
            'WHERE' => [
                'itemtype' => \Computer::class,
                'items_id' => $computer->getID(),
            ],
        ]);
        $this->assertCount(0, $item_av);

        //enable antivirus import
        $this->login();
        $conf = new Conf();
        $this->assertTrue(
            $conf->saveConf([
                'import_antivirus' => 1,
            ])
        );
        $this->logout();

        $this->doInventory($json);

        //check antivirus has been created
        $item_av = $DB->request([
            'FROM' => \ItemAntivirus::getTable(),
            'WHERE' => [
                'itemtype' => \Computer::class,
                'items_id' => $computer->getID(),
            ],
        ]);
        $this->assertCount(1, $item_av);
    }

    public function testImportUnmanageds(): void
    {
        $xml_source = '<?xml version="1.0" encoding="UTF-8" ?>
      <REQUEST>
        <CONTENT>
          <DEVICE>
            <COMPONENTS>
              <COMPONENT>
                <CONTAINEDININDEX>0</CONTAINEDININDEX>
                <INDEX>-1</INDEX>
                <NAME>Force10 S-series Stack</NAME>
                <TYPE>stack</TYPE>
              </COMPONENT>
            </COMPONENTS>
            <INFO>
              <MAC>00:01:e8:d7:c9:1d</MAC>
              <NAME>sw-s50</NAME>
              <SERIAL>DL253300100</SERIAL>
              <TYPE>NETWORKING</TYPE>
            </INFO>
            <PORTS>
              <PORT>
                <CONNECTIONS>
                  <CDP>1</CDP>
                  <CONNECTION>
                    <IFNUMBER>52</IFNUMBER>
                    <IP>10.100.200.10</IP>
                    <SYSDESCR>ExtremeXOS (X440G2-48p-10G4) version 31.7.1.4 31.7.1.4-patch1-77 by release-manager on Mon Nov 21 08:43:09 EST 2022</SYSDESCR>
                    <SYSMAC>00:04:96:f5:82:f5</SYSMAC>
                    <SYSNAME>SW_BATA-RdJ-vdi-1</SYSNAME>
                  </CONNECTION>
                </CONNECTIONS>
                <IFALIAS>BAT-A</IFALIAS>
                <IFDESCR>X670G2-48x-4q Port 1</IFDESCR>
                <IFINERRORS>0</IFINERRORS>
                <IFINOCTETS>2421130293</IFINOCTETS>
                <IFINTERNALSTATUS>1</IFINTERNALSTATUS>
                <IFLASTCHANGE>0:01:51.00</IFLASTCHANGE>
                <IFMTU>1500</IFMTU>
                <IFNAME>1:1</IFNAME>
                <IFNUMBER>1001</IFNUMBER>
                <IFOUTERRORS>0</IFOUTERRORS>
                <IFOUTOCTETS>1619061805</IFOUTOCTETS>
                <IFPORTDUPLEX>3</IFPORTDUPLEX>
                <IFSPEED>10000000000</IFSPEED>
                <IFSTATUS>1</IFSTATUS>
                <IFTYPE>6</IFTYPE>
                <MAC>00:04:96:98:db:22</MAC>
              </PORT>
            </PORTS>
          </DEVICE>
          <MODULEVERSION>4.1</MODULEVERSION>
          <PROCESSNUMBER>1</PROCESSNUMBER>
        </CONTENT>
        <DEVICEID>foo</DEVICEID>
        <QUERY>SNMPQUERY</QUERY>
      </REQUEST>';

        //disable unmanaged import
        $this->login();
        $conf = new Conf();
        $this->assertTrue(
            $conf->saveConf([
                'import_unmanaged' => 0,
            ])
        );
        $this->logout();

        //inventory
        $inventory = $this->doInventory($xml_source, true);

        $network_device_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $network_device_id);

        $unmanaged = new \Unmanaged();
        $this->assertFalse($unmanaged->getFromDBByCrit(['name' => 'SW_BATA-RdJ-vdi-1']));

        //enable unmanaged import
        $this->login();
        $conf = new Conf();
        $this->assertTrue(
            $conf->saveConf([
                'import_unmanaged' => 1,
            ])
        );
        $this->logout();

        //inventory
        $inventory = $this->doInventory($xml_source, true);

        $network_device_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $network_device_id);

        $unmanaged = new \Unmanaged();
        $this->assertTrue($unmanaged->getFromDBByCrit(['name' => 'SW_BATA-RdJ-vdi-1']));
    }
}
