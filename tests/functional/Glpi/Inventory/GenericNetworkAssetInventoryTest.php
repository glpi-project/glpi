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
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasDevicesCapacity;
use Glpi\Asset\Capacity\HasNetworkPortCapacity;
use Glpi\Asset\Capacity\IsInventoriableCapacity;
use Glpi\Asset\CapacityConfig;
use Glpi\Inventory\MainAsset\GenericNetworkAsset;
use Glpi\Inventory\Request;
use InventoryTestCase;
use NetworkEquipment;

class GenericNetworkAssetInventoryTest extends InventoryTestCase
{
    /**
     * Inventory a generic network equipment asset
     *
     * @param Capacity[] $capacities Capacities to activate
     *
     * @return Asset
     */
    private function inventoryNetworkEquipment(array $capacities = []): Asset
    {
        global $DB;

        //create Cisco generic asset
        $definition = $this->initAssetDefinition(
            system_name: 'SpecificNetworkEquipment' . $this->getUniqueString(),
            capacities: array_merge(
                $capacities,
                [
                    new Capacity(
                        name: IsInventoriableCapacity::class,
                        config: new CapacityConfig([
                            'inventory_mainasset' => GenericNetworkAsset::class,
                        ])
                    ),
                ]
            )
        );
        $classname  = $definition->getAssetClassName();

        //we take a standard network equipment inventory and just change itemtype to SpecificNetworkEquipment
        //tests are the same than InventoryTest::testImportNetworkEquipment()
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'networkequipment_1.json'));
        $json->itemtype = $classname;
        $json->deviceid = 'a-network-deviceid';

        $date_now = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date_now;

        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->assertCount(5, $metadata);
        $this->assertSame('a-network-deviceid', $metadata['deviceid']);
        $this->assertSame('4.1', $metadata['version']);
        $this->assertSame($classname, $metadata['itemtype']);
        $this->assertNull($metadata['port']);
        $this->assertSame('netinventory', $metadata['action']);

        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();
        $this->assertIsArray($agent);
        $this->assertSame('a-network-deviceid', $agent['deviceid']);
        $this->assertSame('a-network-deviceid', $agent['name']);
        $this->assertSame($classname, $agent['itemtype']);
        $this->assertSame($agenttype['id'], $agent['agenttypes_id']);
        $this->assertGreaterThan(0, $agent['items_id']);

        //get model, manufacturer, ...
        $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->current();
        $this->assertIsArray($autoupdatesystems);
        $autoupdatesystems_id = $autoupdatesystems['id'];

        $cmodels = $DB->request(['FROM' => $definition->getAssetModelClassName()::getTable(), 'WHERE' => ['name' => 'UCS 6248UP 48-Port']])->current();
        $this->assertIsArray($cmodels);
        $models_id = $cmodels['id'];

        $ctypes = $DB->request(['FROM' => $definition->getAssetTypeClassName()::getTable(), 'WHERE' => ['name' => 'Networking']])->current();
        $this->assertIsArray($ctypes);
        $types_id = $ctypes['id'];

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Cisco']])->current();
        $this->assertIsArray($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $cloc = $DB->request(['FROM' => \Location::getTable(), 'WHERE' => ['name' => 'paris.pa3']])->current();
        $this->assertIsArray($cloc);
        $locations_id = $cloc['id'];

        //check created asset
        $equipments = $DB->request(['FROM' => $classname::getTable(), 'WHERE' => ['is_dynamic' => 1]]);
        //no agent with deviceid equals to "foo"
        $this->assertCount(1, $equipments);
        $equipments_id = $equipments->current()['id'];

        $equipment = new $classname();
        $this->assertTrue($equipment->getFromDB($equipments_id));

        $expected = [
            'id' => $equipments_id,
            'assets_assetdefinitions_id' => $equipment->fields['assets_assetdefinitions_id'],
            'assets_assetmodels_id' => $models_id,
            'assets_assettypes_id' => $types_id,
            'name' => 'ucs6248up-cluster-pa3-B',
            'uuid' => null,
            'comment' => null,
            'serial' => 'SSI1912014B',
            'otherserial' => null,
            'contact' => 'noc@glpi-project.org',
            'contact_num' => null,
            'users_id' => 0,
            'users_id_tech' => 0,
            'locations_id' => $locations_id,
            'manufacturers_id' => $manufacturers_id,
            'states_id' => 0,
            'entities_id' => 0,
            'is_recursive' => 0,
            'is_deleted' => 0,
            'is_template' => 0,
            'is_dynamic' => 1,
            'template_name' => null,
            'autoupdatesystems_id' => $autoupdatesystems_id,
            'date_creation' => $equipment->fields['date_creation'],
            'date_mod' => $equipment->fields['date_mod'],
            'last_inventory_update' => $date_now,
            'custom_fields' => '[]',
            'groups_id' => [],
            'groups_id_tech' => [],
            /**
            Present in standard NetworkEquipment
            'ram' => null,
            'networks_id' => 0,
            'ticket_tco' => '0.0000',
            'sysdescr' => null,
            'cpu' => 4,
            'uptime' => '482 days, 05:42:18.50',
            'snmpcredentials_id' => $snmpcredentials_id,
             */
        ];
        $this->assertIsArray($equipment->fields);
        $this->assertSame($expected, $equipment->fields);

        //check matchedlogs
        $neteq_criteria = [
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

        $iterator = $DB->request($neteq_criteria);
        $this->assertCount(1, $iterator);
        foreach ($iterator as $neteq) {
            $this->assertSame($classname . ' import (by serial)', $neteq['name']);
            $this->assertSame($equipments_id, $neteq['items_id']);
            $this->assertSame(Request::INVENT_QUERY, $neteq['method']);
        }

        //check created asset
        $assets_id = $inventory->getAgent()->fields['items_id'];
        $this->assertGreaterThan(0, $assets_id);
        $asset = new $classname();
        $this->assertTrue($asset->getFromDB($assets_id));

        return $asset;
    }

    /**
     * Test Generic Network Asset inventory
     *
     * @return void
     */
    public function testImportNetworkEquipment(): void
    {
        global $DB;

        //create Network Equipment generic asset
        $asset = $this->inventoryNetworkEquipment();

        $equipments_id = $asset->getID();
        $classname = $asset::class;

        //no network ports
        $iterator = $DB->request([
            'FROM'   => \NetworkPort::getTable(),
            'WHERE'  => [
                'items_id'           => $equipments_id,
                'itemtype'           => $classname,
            ],
        ]);
        $this->assertCount(0, $iterator);

        //no port connection
        $connections = $DB->request(['FROM' => \NetworkPort_NetworkPort::getTable()]);
        $this->assertCount(0, $connections);

        //no unmanageds
        $unmanageds = $DB->request(['FROM' => \Unmanaged::getTable()]);
        $this->assertCount(0, $unmanageds);

        //no devices
        //check for components
        $components = [];
        $allcount = 0;
        foreach (\Item_Devices::getItemAffinities('NetworkEquipment') as $link_type) {
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
            'Item_DeviceFirmware' => 0,
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
    }

    /**
     * Test Generic Network Asset inventory with network ports
     *
     * @return void
     */
    public function testImportNetworkEquipmentWPorts(): void
    {
        global $DB;

        //create Network Equipment generic asset
        $asset = $this->inventoryNetworkEquipment([
            new Capacity(
                name: HasNetworkPortCapacity::class
            ),
        ]);

        $equipments_id = $asset->getID();
        $classname = $asset::class;

        //check network ports
        $expected_count = 164;
        $iterator = $DB->request([
            'FROM'   => \NetworkPort::getTable(),
            'WHERE'  => [
                'items_id'           => $equipments_id,
                'itemtype'           => $classname,
            ],
        ]);
        $this->assertCount($expected_count, $iterator);

        //first port on GenericAsset, last on std inventory
        $expecteds = [
            0 => [
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

            if (isset($expecteds[$i])) {
                $expected = $expecteds[$i];
                $expected = $expected + [
                    'items_id' => $equipments_id,
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
            } else {
                $this->assertSame($classname, $port['itemtype']);
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
                foreach ($ip_iterator as $ip) {
                    $this->assertIsArray($ips[$port['name']]);
                    $this->assertTrue(in_array($ip['name'], $ips[$port['name']]));
                }
            }
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

        $mlogs = new \RuleMatchedLog();
        $found = $mlogs->find();
        $this->assertCount(6, $found);//1 equipment, 5 unmanageds

        $unmanaged_criteria = [
            'FROM' => \RuleMatchedLog::getTable(),
            'LEFT JOIN' => [
                \Rule::getTable() => [
                    'ON' => [
                        \RuleMatchedLog::getTable() => 'rules_id',
                        \Rule::getTable() => 'id',
                    ],
                ],
            ],
            'WHERE' => ['itemtype' => \Unmanaged::class],
        ];
        $iterator = $DB->request($unmanaged_criteria);
        $this->assertCount(5, $iterator);
        foreach ($iterator as $unmanaged) {
            $this->assertSame('Global import (by ip+ifdescr)', $unmanaged['name']);
            $this->assertSame(Request::INVENT_QUERY, $unmanaged['method']);
        }
    }

    /**
     * Test Generic Network Asset inventory with devices
     *
     * @return void
     */
    public function testImportNetworkEquipmentWDevices(): void
    {
        global $DB;

        //create Network Equipment generic asset
        $asset = $this->inventoryNetworkEquipment([
            new Capacity(
                name: HasDevicesCapacity::class
            ),
        ]);

        $equipments_id = $asset->getID();
        $classname = $asset::class;

        //check for components
        $components = [];
        $allcount = 0;
        foreach (\Item_Devices::getItemAffinities('NetworkEquipment') as $link_type) {
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
                'pattern' => NetworkEquipment::class,
            ],
        ];
        $iterator = $DB->request($criteria);
        $tpl_rules_count = $iterator->current()['cnt'];
        $this->assertGreaterThanOrEqual(6, $tpl_rules_count);

        //create Network generic asset
        $definition = $this->initAssetDefinition(
            system_name: 'SpecificNetworkEquipment' . $this->getUniqueString(),
            capacities: array_merge(
                [],
                [
                    new Capacity(
                        name: IsInventoriableCapacity::class,
                        config: new CapacityConfig(['inventory_mainasset' => GenericNetworkAsset::class])
                    ),
                ]
            )
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

    public function testRuleDefineItemtype(): void
    {
        //create Network Equipment generic asset
        $definition = $this->initAssetDefinition(
            system_name: 'SpecificNetworkEquipment' . $this->getUniqueString(),
            capacities: [
                new Capacity(
                    name: IsInventoriableCapacity::class,
                    config: new CapacityConfig([
                        'inventory_mainasset' => GenericNetworkAsset::class,
                    ])
                ),
            ]
        );
        $classname  = $definition->getAssetClassName();

        //create itemtype definition rule
        $this->addRule(
            \RuleDefineItemtype::class,
            'Change itemtype from name',
            [
                [
                    'condition' => \RuleDefineItemtype::PATTERN_BEGIN,
                    'criteria'  => 'name',
                    'pattern'   => 'ucs',
                ],
            ],
            [
                'action_type' => 'assign',
                'field'       => '_assign',
                'value'       => $classname,
            ]
        );

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <COMMENTS>Cisco NX-OS(tm) ucs, Software (ucs-6100-k9-system), Version 5.0(3)N2(4.02b), RELEASE SOFTWARE Copyright (c) 2002-2013 by Cisco Systems, Inc.   Compiled 1/16/2019 18:00:00</COMMENTS>
        <CONTACT>noc@glpi-project.org</CONTACT>
        <NAME>ucs6248up-cluster-pa3-B</NAME>
        <SERIAL>SSI1912014B</SERIAL>
        <TYPE>NETWORKING</TYPE>
      </INFO>
    </DEVICE>
    <MODULEVERSION>4.1</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>";

        $inventory = $this->doInventory($xml_source, true);
        $item = $inventory->getItem();
        $this->assertInstanceOf($classname, $item);

        //another test, with a name that does not match Rule criterion
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <INFO>
        <COMMENTS>Cisco NX-OS(tm) ucs, Software (ucs-6100-k9-system), Version 5.0(3)N2(4.02b), RELEASE SOFTWARE Copyright (c) 2002-2013 by Cisco Systems, Inc.   Compiled 1/16/2019 18:00:00</COMMENTS>
        <CONTACT>noc@glpi-project.org</CONTACT>
        <NAME>abc6248up-cluster-pa3-B</NAME>
        <SERIAL>SSI1912014B</SERIAL>
        <TYPE>NETWORKING</TYPE>
      </INFO>
    </DEVICE>
    <MODULEVERSION>4.1</MODULEVERSION>
    <PROCESSNUMBER>1</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>foo</DEVICEID>
  <QUERY>SNMPQUERY</QUERY>
</REQUEST>";

        $inventory = $this->doInventory($xml_source, true);
        $item = $inventory->getItem();
        $this->assertInstanceOf(NetworkEquipment::class, $item);
    }
}
