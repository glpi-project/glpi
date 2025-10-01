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

use Glpi\Asset\Asset;
use Glpi\Asset\Asset_PeripheralAsset;
use Glpi\Asset\AssetDefinitionManager;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasOperatingSystemCapacity;
use Glpi\Asset\Capacity\HasVolumesCapacity;
use Glpi\Asset\Capacity\IsInventoriableCapacity;
use Glpi\Inventory\Request;
use InventoryTestCase;

class GenericAssetInventoryTest extends InventoryTestCase
{
    /**
     * Inventory a generic smartphone asset
     *
     * @param Capacity[] $capacities Capacities to activate
     *
     * @return Asset
     */
    private function inventorySmartphone(array $capacities = []): Asset
    {
        global $DB;

        //create Smartphone generic asset
        $definition = $this->initAssetDefinition(
            system_name: 'Smartphone' . $this->getUniqueString(),
            capacities: array_merge(
                $capacities,
                [
                    new Capacity(name: IsInventoriableCapacity::class),
                ]
            )
        );
        $classname  = $definition->getAssetClassName();

        //we take a standard phone inventory and just change itemtype to Smartphone
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'phone_1.json'));
        $json->itemtype = $classname;
        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->assertCount(5, $metadata);
        $this->assertSame('Mi9TPro-TéléphoneM-2019-12-18-14-30-16', $metadata['deviceid']);
        $this->assertSame('example-app-java', $metadata['version']);
        $this->assertSame($classname, $metadata['itemtype']);
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
        $this->assertSame($classname, $agent['itemtype']);
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
                        \Rule::getTable() => 'id',
                    ],
                ],
            ],
            'WHERE' => ['itemtype' => $classname],
        ];

        $iterator = $DB->request($criteria);
        $this->assertCount(1, $iterator);
        //$this->assertSame('Assert import (by serial + uuid)', $iterator->current()['name']);
        $this->assertSame($classname . ' import (by serial + uuid)', $iterator->current()['name']);
        $this->assertSame(Request::INVENT_QUERY, $iterator->current()['method']);

        //get models, manufacturer, ...
        $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->current();
        $this->assertIsArray($autoupdatesystems);
        $autoupdatesystems_id = $autoupdatesystems['id'];

        $cmodels = $DB->request(['FROM' => $definition->getAssetModelClassName()::getTable(), 'WHERE' => ['name' => 'Mi 9T Pro']])->current();
        $this->assertIsArray($cmodels);
        $models_id = $cmodels['id'];

        $ctypes = $DB->request(['FROM' => $definition->getAssetTypeClassName()::getTable(), 'WHERE' => ['name' => 'Mi 9T Pro']])->current();
        $this->assertIsArray($ctypes);
        $types_id = $ctypes['id'];

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Xiaomi']])->current();
        $this->assertIsArray($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        //check created asset
        $assets_id = $inventory->getAgent()->fields['items_id'];
        $this->assertGreaterThan(0, $assets_id);
        $asset = new $classname();
        $this->assertTrue($asset->getFromDB($assets_id));

        $expected = [
            'id' => $assets_id,
            'entities_id' => 0,
            'name' => 'Mi9TPro-TéléphoneM',
            'date_mod' => $asset->fields['date_mod'],
            'contact' => 'builder',
            'contact_num' => null,
            'custom_fields' => '[]',
            'users_id_tech' => 0,
            'comment' => null,
            'serial' => 'af8d8fcfa6fa4794',
            'otherserial' => 'release-keys',
            'locations_id' => 0,
            'assets_assetdefinitions_id' => $definition->getID(),
            'assets_assettypes_id' => $types_id,
            'assets_assetmodels_id' => $models_id,
            'manufacturers_id' => $manufacturers_id,
            'is_deleted' => 0,
            'is_template' => 0,
            'template_name' => null,
            'users_id' => 0,
            'states_id' => 0,
            'is_dynamic' => 1,
            'autoupdatesystems_id' => $autoupdatesystems_id,
            'uuid' => 'af8d3fcfa6fe4784',
            'date_creation' => $asset->fields['date_creation'],
            'is_recursive' => 0,
            'last_inventory_update' => $asset->fields['last_inventory_update'],
            'groups_id' => [],
            'groups_id_tech' => [],
        ];
        $this->assertIsArray($asset->fields);
        ksort($expected);
        ksort($asset->fields);
        $this->assertSame($expected, $asset->fields);

        return $asset;
    }

    /**
     * Test basic Generic Asset inventory
     *
     * @return void
     */
    public function testImportSmartphone(): void
    {
        global $DB;

        //create Smartphone generic asset
        $asset = $this->inventorySmartphone();
        $classname = $asset::class;
        $assets_id = $asset->getID();

        //operating system
        $ios = new \Item_OperatingSystem();
        $iterator = $ios->getFromItem($asset);
        //No OS capacity, no OS :)
        $this->assertCount(0, $iterator);

        //remote management
        $mgmt = new \Item_RemoteManagement();
        $iterator = $mgmt->getFromItem($asset);
        $this->assertCount(0, $iterator);

        //volumes
        $idisks = new \Item_Disk();
        $iterator = $idisks->getFromItem($asset);
        //No disk capacity, no disk :)
        $this->assertCount(0, $iterator);

        //check network ports
        $iterator = $DB->request([
            'FROM'   => \NetworkPort::getTable(),
            'WHERE'  => [
                'items_id'           => $assets_id,
                'itemtype'           => $classname,
            ],
        ]);
        $this->assertCount(0, $iterator);

        //check for components
        $components = [];
        $this->assertCount(0, \Item_Devices::getItemAffinities($classname));

        //software
        $isoft = new \Item_SoftwareVersion();
        $iterator = $isoft->getFromItem($asset);
        //No software capacity, no software :)
        $this->assertCount(0, $iterator);
    }

    /**
     * Test Generic Asset inventory with OS
     *
     * @return void
     */
    public function testImportSmartphoneWOS()
    {
        //create Smartphone generic asset
        $asset = $this->inventorySmartphone([new Capacity(name: HasOperatingSystemCapacity::class)]);

        //operating system
        $ios = new \Item_OperatingSystem();
        $iterator = $ios->getFromItem($asset);
        //OS capacity enabled
        $this->assertCount(1, $iterator);
        $record = $iterator->current();

        $expected = [
            'assocID' => $record['assocID'],
            'name' => 'Q Android 10.0 api 29',
            'version' => '29',
            'architecture' => 'arm64-v8a,armeabi-v7a,armeabi',
            'servicepack' => null,
        ];
        $this->assertSame($expected, $record);
    }

    /**
     * Test Generic Asset inventory with Volumes
     *
     * @return void
     */
    public function testImportSmartphoneWVolumes()
    {
        //create Smartphone generic asset
        $asset = $this->inventorySmartphone([new Capacity(name: HasVolumesCapacity::class)]);
        $classname = $asset::class;
        $assets_id = $asset->getID();

        //volumes
        $idisks = new \Item_Disk();
        $iterator = $idisks->getFromItem($asset);
        //Disk capacity activated
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
            ],
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
                'items_id'     => $assets_id,
                'itemtype'     => $classname,
                'entities_id'  => 0,
                'is_deleted'   => 0,
                'is_dynamic'   => 1,
            ];
            ksort($volume);
            ksort($expected);
            $this->assertIsArray($volume);
            $this->assertEquals($expected, $volume);
            ++$i;
        }
    }

    /**
     * Test Generic Asset inventory with all capacities enabled
     *
     * @return void
     */
    public function testImportSmartphoneAllCapacities(): void
    {
        global $DB;

        //create Smartphone generic asset
        $capacities = [];
        foreach (array_keys(AssetDefinitionManager::getInstance()->getAvailableCapacities()) as $available_capacity) {
            if ($available_capacity !== IsInventoriableCapacity::class) {
                $capacities[] = new Capacity(name: $available_capacity);
            }
        }
        $asset = $this->inventorySmartphone($capacities);
        $classname = $asset::class;
        $assets_id = $asset->getID();

        //operating system
        $ios = new \Item_OperatingSystem();
        $iterator = $ios->getFromItem($asset);
        $this->assertCount(1, $iterator);
        $record = $iterator->current();

        $expected = [
            'assocID' => $record['assocID'],
            'name' => 'Q Android 10.0 api 29',
            'version' => '29',
            'architecture' => 'arm64-v8a,armeabi-v7a,armeabi',
            'servicepack' => null,
        ];
        $this->assertSame($expected, $record);

        //remote management
        $mgmt = new \Item_RemoteManagement();
        $iterator = $mgmt->getFromItem($asset);
        $this->assertCount(0, $iterator);

        //volumes
        $idisks = new \Item_Disk();
        $iterator = $idisks->getFromItem($asset);
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
            ],
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
                'items_id'     => $assets_id,
                'itemtype'     => $classname,
                'entities_id'  => 0,
                'is_deleted'   => 0,
                'is_dynamic'   => 1,
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
                'items_id'           => $assets_id,
                'itemtype'           => $classname,
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
            ],
        ];

        $ips = [
            'No description found'  => [
                'v4'   => '172.28.214.132',
            ],
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
                'items_id' => $assets_id,
                'itemtype' => $classname,
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
                'lastup' => null,
            ];

            $this->assertIsArray($port);
            $this->assertEquals($expected, $port);
            ++$i;

            //check for ips
            $ip_iterator = $DB->request([
                'SELECT'       => [
                    \IPAddress::getTable() . '.name',
                    \IPAddress::getTable() . '.version',
                ],
                'FROM'   => \IPAddress::getTable(),
                'INNER JOIN'   => [
                    \NetworkName::getTable()   => [
                        'ON'  => [
                            \IPAddress::getTable()     => 'items_id',
                            \NetworkName::getTable()   => 'id', [
                                'AND' => [\IPAddress::getTable() . '.itemtype'  => \NetworkName::getType()],
                            ],
                        ],
                    ],
                ],
                'WHERE'  => [
                    \NetworkName::getTable() . '.itemtype'  => \NetworkPort::getType(),
                    \NetworkName::getTable() . '.items_id'  => $ports_id,
                ],
            ]);

            $this->assertCount(count($ips[$port['name']] ?? []), $ip_iterator);
            if (isset($ips[$port['name']])) {
                //FIXME: missing all ipv6 :(
                $ip = $ip_iterator->current();
                $this->assertSame(4, (int) $ip['version']);
                $this->assertSame($ips[$port['name']]['v4'], $ip['name']);
            }
        }

        //check for components
        $components = [];
        foreach (\Item_Devices::getItemAffinities('Computer') as $link_type) {
            $link        = getItemForItemtype($link_type);
            $iterator = $DB->request($link->getTableGroupCriteria($asset));
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
            'Item_DeviceCamera' => 2,
        ];

        foreach ($expecteds as $type => $count) {
            $this->assertCount($count, $components[$type], count($components[$type]) . ' ' . $type);
        }

        $expecteds = [
            'Item_DeviceMotherboard' => [],
            'Item_DeviceFirmware' => [
                [
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
                    'devicefirmwares_id' => 104,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ],
            ],
            'Item_DeviceProcessor' => [
                [
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'states_id' => 0,
                ],
            ],
            'Item_DeviceHardDrive' => [],
            'Item_DeviceNetworkCard' => [
                [
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                ],
            ],
            'Item_DeviceDrive' => [],
            'Item_DeviceBattery' => [
                [
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'real_capacity' => 0,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'users_id_tech' => 0,
                    'pin' => '',
                    'pin2' => '',
                    'puk' => '',
                    'puk2' => '',
                    'msin' => '',
                    'comment' => null,
                ],
            ],
            'Item_DeviceCamera' => [
                [
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
                    'devicecameras_id' => 4,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'locations_id' => 0,
                    'states_id' => 0,
                ], [
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
                    'devicecameras_id' => 4,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'locations_id' => 0,
                    'states_id' => 0,
                ],
            ],
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
            $this->assertEquals($expected, $component);
        }

        //software
        $isoft = new \Item_SoftwareVersion();
        $iterator = $isoft->getFromItem($asset);
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
            ],
        ];

        $i = 0;
        foreach ($iterator as $soft) {
            $expected = $expecteds[$i];
            $this->assertEquals(
                $expected,
                [
                    'softname'     => $soft['softname'],
                    'version'      => $soft['version'],
                    'dateinstall'  => $soft['dateinstall'],
                ]
            );
            ++$i;
        }
    }

    /**
     * Inventory a generic server asset
     *
     * @param array<class-string> $capacities Capacities to activate
     *
     * @return Asset
     */
    private function inventoryServer(array $capacities = []): Asset
    {
        global $DB;

        //create Server generic asset
        $definition = $this->initAssetDefinition(
            system_name: 'Server' . $this->getUniqueString(),
            capacities: array_merge(
                $capacities,
                [
                    new Capacity(name: IsInventoriableCapacity::class),
                ]
            )
        );
        $classname  = $definition->getAssetClassName();

        //we take a standard phone inventory and just change itemtype to Server
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));
        $json->itemtype = $classname;
        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->assertCount(7, $metadata);
        $this->assertSame('glpixps-2018-07-09-09-07-13', $metadata['deviceid']);
        $this->assertSame('FusionInventory-Agent_v2.5.2-1.fc31', $metadata['version']);
        $this->assertSame($classname, $metadata['itemtype']);
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
        $this->assertSame($classname, $agent['itemtype']);
        $this->assertSame('000005', $agent['tag']);
        $this->assertSame($agenttype['id'], $agent['agenttypes_id']);
        $this->assertGreaterThan(0, $agent['items_id']);

        //check created asset
        $assets_id = $inventory->getAgent()->fields['items_id'];
        $this->assertGreaterThan(0, $assets_id);
        $asset = new $classname();
        $this->assertTrue($asset->getFromDB($assets_id));

        //get models, manufacturer, ...
        $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->current();
        $this->assertIsArray($autoupdatesystems);
        $autoupdatesystems_id = $autoupdatesystems['id'];

        $cmodels = $DB->request(['FROM' => $definition->getAssetModelClassName()::getTable(), 'WHERE' => ['name' => 'XPS 13 9350']])->current();
        $this->assertIsArray($cmodels);
        $models_id = $cmodels['id'];

        $ctypes = $DB->request(['FROM' => $definition->getAssetTypeClassName()::getTable(), 'WHERE' => ['name' => 'Laptop']])->current();
        $this->assertIsArray($ctypes);
        $types_id = $ctypes['id'];

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Dell Inc.']])->current();
        $this->assertIsArray($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $expected = [
            'id' => $assets_id,
            'entities_id' => 0,
            'name' => 'glpixps',
            'serial' => '640HP72',
            'otherserial' => null,
            'contact' => 'trasher/root',
            'contact_num' => null,
            'custom_fields' => '[]',
            'users_id_tech' => 0,
            'comment' => null,
            'date_mod' => $asset->fields['date_mod'],
            'autoupdatesystems_id' => $autoupdatesystems_id,
            'locations_id' => 0,
            'assets_assetdefinitions_id' => $definition->getID(),
            'assets_assettypes_id' => $types_id,
            'assets_assetmodels_id' => $models_id,
            'is_template' => 0,
            'template_name' => null,
            'manufacturers_id' => $manufacturers_id,
            'is_deleted' => 0,
            'is_dynamic' => 1,
            'users_id' => 0,
            'states_id' => 0,
            'uuid' => '4c4c4544-0034-3010-8048-b6c04f503732',
            'date_creation' => $asset->fields['date_creation'],
            'is_recursive' => 0,
            'last_inventory_update' => $asset->fields['last_inventory_update'],
            'groups_id' => [],
            'groups_id_tech' => [],
        ];
        $this->assertIsArray($asset->fields);
        ksort($expected);
        ksort($asset->fields);
        $this->assertSame($expected, $asset->fields);

        return $asset;
    }

    /**
     * Test basic Generic Asset inventory
     *
     * @return void
     */
    public function testImportServer(): void
    {
        global $DB;

        //create Server generic asset
        $asset = $this->inventoryServer();
        $classname = $asset::class;
        $assets_id = $asset->getID();

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
                        \Rule::getTable() => 'id',
                    ],
                ],
            ],
            'WHERE' => [],
        ];

        $computer_criteria = $criteria;
        $computer_criteria['WHERE'] = ['itemtype' => $classname];
        $iterator = $DB->request($computer_criteria);
        $this->assertCount(1, $iterator);
        $this->assertSame($classname . ' import (by serial + uuid)', $iterator->current()['name']);
        $this->assertSame(Request::INVENT_QUERY, $iterator->current()['method']);

        //operating system
        $ios = new \Item_OperatingSystem();
        $iterator = $ios->getFromItem($asset);
        $this->assertCount(0, $iterator);

        //remote management
        $mgmt = new \Item_RemoteManagement();
        $iterator = $mgmt->getFromItem($asset);
        $this->assertCount(0, $iterator);

        //connections
        $connections = getAllDataFromTable(
            Asset_PeripheralAsset::getTable(),
            [
                'itemtype_asset'      => $classname,
                'items_id_asset'      => $assets_id,
                'itemtype_peripheral' => 'Monitor',
            ]
        );
        $this->assertCount(0, $connections);

        //check network ports
        $iterator = $DB->request([
            'FROM'   => \NetworkPort::getTable(),
            'WHERE'  => [
                'items_id'           => $assets_id,
                'itemtype'           => $classname,
            ],
        ]);
        $this->assertCount(0, $iterator);

        //check for components
        $components = [];
        $this->assertCount(0, \Item_Devices::getItemAffinities($classname));

        //check printer
        $connections = getAllDataFromTable(
            Asset_PeripheralAsset::getTable(),
            [
                'itemtype_asset'      => $classname,
                'items_id_asset'      => $assets_id,
                'itemtype_peripheral' => 'Printer',
            ]
        );
        $this->assertCount(0, $connections);

        $idisks = new \Item_Disk();
        $iterator = $idisks->getFromItem($asset);
        $this->assertCount(0, $iterator);

        $isoft = new \Item_SoftwareVersion();
        $iterator = $isoft->getFromItem($asset);
        $this->assertCount(0, $iterator);

        $link        = getItemForItemtype(\Item_DeviceBattery::class);
        $iterator = $DB->request($link->getTableGroupCriteria($asset));
        $this->assertCount(0, $iterator);
    }

    public function testImportServerWOS()
    {
        //create Server generic asset
        $asset = $this->inventoryServer([new Capacity(name: HasOperatingSystemCapacity::class)]);

        //operating system
        $ios = new \Item_OperatingSystem();
        $iterator = $ios->getFromItem($asset);
        //OS capacity enabled
        $this->assertCount(1, $iterator);
        $record = $iterator->current();

        $expected = [
            'assocID' => $record['assocID'],
            'name' => 'Fedora 31 (Workstation Edition)',
            'version' => '31 (Workstation Edition)',
            'architecture' => 'x86_64',
            'servicepack' => null,
        ];
        $this->assertSame($expected, $record);
    }

    public function testImportServerAllCapacities(): void
    {
        global $DB;

        //create Server generic asset
        $capacities = [];
        foreach (array_keys(AssetDefinitionManager::getInstance()->getAvailableCapacities()) as $available_capacity) {
            if ($available_capacity !== IsInventoriableCapacity::class) {
                $capacities[] = new Capacity(name: $available_capacity);
            }
        }
        $asset = $this->inventoryServer($capacities);
        $classname = $asset::class;
        $assets_id = $asset->getID();

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
                        \Rule::getTable() => 'id',
                    ],
                ],
            ],
            'WHERE' => [],
        ];

        $monitor_criteria = $criteria;
        $monitor_criteria['WHERE'] = ['itemtype' => \Monitor::getType()];
        $iterator = $DB->request($monitor_criteria);
        $this->assertCount(1, $iterator);
        $this->assertSame('Monitor import (by serial)', $iterator->current()['name']);
        $this->assertSame(Request::INVENT_QUERY, $iterator->current()['method']);

        $printer_criteria = $criteria;
        $printer_criteria['WHERE'] = ['itemtype' => \Printer::getType()];
        $iterator = $DB->request($printer_criteria);
        $this->assertCount(1, $iterator);
        $this->assertSame('Printer import (by serial)', $iterator->current()['name']);
        $this->assertSame(Request::INVENT_QUERY, $iterator->current()['method']);

        $computer_criteria = $criteria;
        $computer_criteria['WHERE'] = ['itemtype' => $classname];
        $iterator = $DB->request($computer_criteria);
        $this->assertCount(1, $iterator);
        $this->assertSame($classname . ' import (by serial + uuid)', $iterator->current()['name']);
        $this->assertSame(Request::INVENT_QUERY, $iterator->current()['method']);

        //operating system
        $ios = new \Item_OperatingSystem();
        $iterator = $ios->getFromItem($asset);
        $this->assertCount(1, $iterator);
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
        $iterator = $mgmt->getFromItem($asset);
        $this->assertCount(1, $iterator);
        $remote = $iterator->current();
        unset($remote['id']);
        $this->assertSame(
            [
                'itemtype' => $classname,
                'items_id' => $assets_id,
                'remoteid' => '123456789',
                'type' => 'teamviewer',
                'is_dynamic' => 1,
                'is_deleted' => 0,
            ],
            $remote
        );

        //connections
        $connections = getAllDataFromTable(
            Asset_PeripheralAsset::getTable(),
            [
                'itemtype_asset'      => $classname,
                'items_id_asset'      => $assets_id,
                'itemtype_peripheral' => 'Monitor',
            ]
        );
        $this->assertCount(1, $connections);
        $connection = $connections[array_key_first($connections)];
        $monitor = new \Monitor();
        $this->assertTrue($monitor->getFromDB($connection['items_id_peripheral']));
        $monitor_fields = $monitor->fields;
        unset($monitor_fields['date_mod'], $monitor_fields['date_creation']);

        $mmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Sharp Corporation']])->current();
        $this->assertIsArray($mmanuf);
        $manufacturers_id = $mmanuf['id'];

        $mmodel = $DB->request(['FROM' => \MonitorModel::getTable(), 'WHERE' => ['name' => 'DJCP6']])->current();
        $this->assertIsArray($mmodel);
        $models_id = $mmodel['id'];

        $expected = [
            'id' => $monitor_fields['id'],
            'entities_id' => 0,
            'name' => 'DJCP6',
            'contact' => 'trasher/root',
            'contact_num' => null,
            'users_id_tech' => 0,
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
            'states_id' => 0,
            'ticket_tco' => '0.0000',
            'is_dynamic' => 1,
            'autoupdatesystems_id' => 0,
            'uuid' => null,
            'is_recursive' => 0,
            'groups_id' => [],
            'groups_id_tech' => [],
        ];
        $this->assertIsArray($monitor_fields);
        $this->assertSame($expected, $monitor_fields);

        //check network ports
        $iterator = $DB->request([
            'FROM'   => \NetworkPort::getTable(),
            'WHERE'  => [
                'items_id'           => $assets_id,
                'itemtype'           => $classname,
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
                'instantiation_type' => 'NetworkPortEthernet',
                'mac' => '52:54:00:fa:20:0e',
                'ifstatus' => '2',
                'ifinternalstatus' => '2',
            ],
        ];

        $ips = [
            'enp57s0u1u4'  => [
                'v4'   => '192.168.1.142',
                'v6'   => 'fe80::b283:4fa3:d3f2:96b1',
            ],
            'wlp58s0'   => [
                'v4'   => '192.168.1.118',
                'v6'   => 'fe80::92a4:26c6:99dd:2d60',
            ],
            'virbr0' => [
                'v4'   => '192.168.122.1',
            ],
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
                'items_id' => $assets_id,
                'itemtype' => $classname,
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
                'lastup' => null,
            ];

            $this->assertIsArray($port);
            $this->assertEquals($expected, $port);
            ++$i;

            //check for ips
            $ip_iterator = $DB->request([
                'SELECT'       => [
                    \IPAddress::getTable() . '.name',
                    \IPAddress::getTable() . '.version',
                ],
                'FROM'   => \IPAddress::getTable(),
                'INNER JOIN'   => [
                    \NetworkName::getTable()   => [
                        'ON'  => [
                            \IPAddress::getTable()     => 'items_id',
                            \NetworkName::getTable()   => 'id', [
                                'AND' => [\IPAddress::getTable() . '.itemtype'  => \NetworkName::getType()],
                            ],
                        ],
                    ],
                ],
                'WHERE'  => [
                    \NetworkName::getTable() . '.itemtype'  => \NetworkPort::getType(),
                    \NetworkName::getTable() . '.items_id'  => $ports_id,
                ],
            ]);

            $this->assertCount(count($ips[$port['name']] ?? []), $ip_iterator);
            if (isset($ips[$port['name']])) {
                //FIXME: missing all ipv6 :(
                $ip = $ip_iterator->current();
                $this->assertSame(4, (int) $ip['version']);
                $this->assertSame($ips[$port['name']]['v4'], $ip['name']);
            }
        }

        //check for components
        $components = [];
        $allcount = 0;
        foreach (\Item_Devices::getItemAffinities($classname) as $link_type) {
            $link        = getItemForItemtype($link_type);
            $iterator = $DB->request($link->getTableGroupCriteria($asset));
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
            'Item_DeviceSensor' => 0,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
                    'devicefirmwares_id' => 104,
                    'is_deleted' => 0,
                    'is_dynamic' => 1,
                    'entities_id' => 0,
                    'is_recursive' => 0,
                    'serial' => null,
                    'otherserial' => null,
                    'locations_id' => 0,
                    'states_id' => 0,
                ],
            ],
            'Item_DeviceProcessor'
                => [
                    [
                        'items_id' => $assets_id,
                        'itemtype' => $classname,
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
            'Item_DeviceMemory'
                => [
                    [
                        'items_id' => $assets_id,
                        'itemtype' => $classname,
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
                        'items_id' => $assets_id,
                        'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
            'Item_DeviceGraphicCard' => [],
            'Item_DeviceSoundCard' => [
                [
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
                    'items_id' => $assets_id,
                    'itemtype' => $classname,
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
        $connections = getAllDataFromTable(
            Asset_PeripheralAsset::getTable(),
            [
                'itemtype_asset'      => $classname,
                'items_id_asset'      => $assets_id,
                'itemtype_peripheral' => 'Printer',
            ]
        );
        $this->assertCount(1, $connections);
        $connection = $connections[array_key_first($connections)];
        $printer = new \Printer();
        $this->assertTrue($printer->getFromDB($connection['items_id_peripheral']));
        $printer_fields = $printer->fields;
        unset($printer_fields['date_mod'], $printer_fields['date_creation']);

        $expected = [
            'id' => $printer_fields['id'],
            'entities_id' => 0,
            'is_recursive' => 0,
            'name' => 'Officejet_Pro_8600_34AF9E_',
            'contact' => 'trasher/root',
            'contact_num' => null,
            'users_id_tech' => 0,
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
            'states_id' => 0,
            'ticket_tco' => '0.0000',
            'is_dynamic' => 1,
            'uuid' => null,
            'sysdescr' => null,
            'last_inventory_update' => $_SESSION['glpi_currenttime'],
            'snmpcredentials_id' => 0,
            'autoupdatesystems_id' => $asset->fields['autoupdatesystems_id'],
            'groups_id' => [],
            'groups_id_tech' => [],
        ];
        $this->assertIsArray($printer_fields);
        $this->assertSame($expected, $printer_fields);

        //check volumes
        $idisks = new \Item_Disk();
        $iterator = $idisks->getFromItem($asset);
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
            ],
        ];

        $i = 0;
        foreach ($iterator as $volume) {
            unset($volume['id'], $volume['date_mod'], $volume['date_creation']);
            $expected = $expecteds[$i];
            $expected += [
                'items_id' => $assets_id,
                'itemtype' => $classname,
                'entities_id' => 0,
                'is_deleted' => 0,
                'is_dynamic' => 1,
            ];

            ksort($expected);
            ksort($volume);

            $this->assertIsArray($volume);
            $this->assertEquals($expected, $volume);
            ++$i;
        }

        $isoft = new \Item_SoftwareVersion();
        $iterator = $isoft->getFromItem($asset);
        $this->assertCount(7, $iterator);

        $link        = getItemForItemtype(\Item_DeviceBattery::class);
        $iterator = $DB->request($link->getTableGroupCriteria($asset));
        $this->assertCount(1, $iterator);

        $battery = [];
        foreach ($iterator as $row) {
            unset($row['id']);
            $battery = $row;
        }

        $expected = [
            'items_id' => $assets_id,
            'itemtype' => $classname,
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
            'real_capacity' => 50570,
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

    public function testRulesCreation(): void
    {
        global $DB;

        $rules = new \RuleImportAsset();
        $this->assertTrue($rules->initRules());

        $criteria = [
            'COUNT' => 'cnt',
            'FROM' => \RuleImportAsset::getTable(),
            'LEFT JOIN' => [
                'glpi_rulecriterias' => [
                    'FKEY' => [
                        'glpi_rules' => 'id',
                        'glpi_rulecriterias' => 'rules_id',
                    ],
                ],
            ],
            'WHERE' => [
                'sub_type' => \RuleImportAsset::class,
                'criteria' => 'itemtype',
                'pattern' => \Computer::class,
            ],
        ];
        $iterator = $DB->request($criteria);
        $tpl_rules_count = $iterator->current()['cnt'];
        $this->assertGreaterThanOrEqual(13, $tpl_rules_count);

        //create Server generic asset
        $definition = $this->initAssetDefinition(
            system_name: 'Server' . $this->getUniqueString(),
            capacities: [new Capacity(name: IsInventoriableCapacity::class)]
        );
        $classname  = $definition->getAssetClassName();

        $criteria['WHERE']['pattern'] = $classname;
        $iterator = $DB->request($criteria);
        $this->assertSame($tpl_rules_count, $iterator->current()['cnt']);

        // Disable capacity and check that rules have been cleaned
        $this->assertTrue($definition->update(['id' => $definition->getID(), 'capacities' => []]));

        $iterator = $DB->request($criteria);
        $this->assertSame(0, $iterator->current()['cnt']);
    }
}
