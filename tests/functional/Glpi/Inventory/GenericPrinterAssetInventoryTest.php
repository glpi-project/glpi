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
use Glpi\Asset\Capacity\HasNetworkPortCapacity;
use Glpi\Asset\Capacity\IsInventoriableCapacity;
use Glpi\Asset\CapacityConfig;
use Glpi\Inventory\MainAsset\GenericPrinterAsset;
use Glpi\Inventory\Request;
use InventoryTestCase;

class GenericPrinterAssetInventoryTest extends InventoryTestCase
{
    /**
     * Inventory a generic network equipment asset
     *
     * @param Capacity[] $capacities Capacities to activate
     *
     * @return Asset
     */
    private function inventoryPrinter(array $capacities = []): Asset
    {
        global $DB;

        //create HPLaser generic asset
        $definition = $this->initAssetDefinition(
            system_name: 'HPLaser' . $this->getUniqueString(),
            capacities: array_merge(
                $capacities,
                [
                    new Capacity(
                        name: IsInventoriableCapacity::class,
                        config: new CapacityConfig([
                            'inventory_mainasset' => GenericPrinterAsset::class,
                        ])
                    ),
                ]
            )
        );
        $classname  = $definition->getAssetClassName();

        //we take a standard network equipment inventory and just change itemtype to HPLaser
        //tests are inspired from Asset/PrinterTest::testSnmpPrinterManagementPortAdded()
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'printer_2.json'));
        $json->itemtype = $classname;
        $json->deviceid = 'a-printer-deviceid';

        $date_now = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date_now;

        $inventory = $this->doInventory($json);

        //check inventory metadata
        $metadata = $inventory->getMetadata();
        $this->assertCount(5, $metadata);
        $this->assertSame('a-printer-deviceid', $metadata['deviceid']);
        //$this->assertSame('4.1', $metadata['version']);
        $this->assertSame($classname, $metadata['itemtype']);
        $this->assertNull($metadata['port']);
        $this->assertSame('netinventory', $metadata['action']);

        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->assertCount(1, $agents);
        $agent = $agents->current();
        $this->assertIsArray($agent);
        $this->assertSame('a-printer-deviceid', $agent['deviceid']);
        $this->assertSame('a-printer-deviceid', $agent['name']);
        $this->assertSame($classname, $agent['itemtype']);
        $this->assertSame($agenttype['id'], $agent['agenttypes_id']);
        $this->assertGreaterThan(0, $agent['items_id']);

        //get model, manufacturer, ...
        $autoupdatesystems = $DB->request(['FROM' => \AutoupdateSystem::getTable(), 'WHERE' => ['name' => 'GLPI Native Inventory']])->current();
        $this->assertIsArray($autoupdatesystems);
        $autoupdatesystems_id = $autoupdatesystems['id'];

        $cmodels = $DB->request(['FROM' => $definition->getAssetModelClassName()::getTable(), 'WHERE' => ['name' => 'Canon MX 5970']])->current();
        $this->assertIsArray($cmodels);
        $models_id = $cmodels['id'];

        $ctypes = $DB->request(['FROM' => $definition->getAssetTypeClassName()::getTable(), 'WHERE' => ['name' => 'Printer']])->current();
        $this->assertIsArray($ctypes);
        $types_id = $ctypes['id'];

        $cmanuf = $DB->request(['FROM' => \Manufacturer::getTable(), 'WHERE' => ['name' => 'Canon']])->current();
        $this->assertIsArray($cmanuf);
        $manufacturers_id = $cmanuf['id'];

        $locations_id = 0;

        //check created asset
        $equipments = $DB->request(['FROM' => $classname::getTable(), 'WHERE' => ['is_dynamic' => 1]]);
        //no agent with deviceid equals to "foo"
        $this->assertCount(1, $equipments);
        $printers_id = $equipments->current()['id'];

        $equipment = new $classname();
        $this->assertTrue($equipment->getFromDB($printers_id));

        $expected = [
            'id' => $printers_id,
            'assets_assetdefinitions_id' => $equipment->fields['assets_assetdefinitions_id'],
            'assets_assetmodels_id' => $models_id,
            'assets_assettypes_id' => $types_id,
            'name' => 'MX5970',
            'uuid' => null,
            'comment' => null,
            'serial' => 'SDFSDF9874',
            'otherserial' => null,
            'contact' => null,
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
            $this->assertSame($printers_id, $neteq['items_id']);
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
     * Test Generic Printer Asset inventory
     *
     * @return void
     */
    public function testImportPrinter(): void
    {
        //create Printer generic asset
        $asset = $this->inventoryPrinter();

        $printers_id = $asset->getID();
        $classname = $asset::class;

        //no network ports
        $np = new \NetworkPort();
        $this->assertFalse($np->getFromDbByCrit(['itemtype' => $classname, 'items_id' => $printers_id]));
    }

    /**
     * Test Generic Network Asset inventory with network ports
     *
     * @return void
     */
    public function testImportNetworkEquipmentWPorts(): void
    {
        //create Printer generic asset
        $asset = $this->inventoryPrinter([
            new Capacity(
                name: HasNetworkPortCapacity::class
            ),
        ]);

        $printers_id = $asset->getID();
        $classname = $asset::class;

        //check network ports
        $np = new \NetworkPort();
        $this->assertTrue($np->getFromDbByCrit(['itemtype' => $classname, 'items_id' => $printers_id, 'instantiation_type' => 'NetworkPortAggregate']));
        $this->assertTrue($np->getFromDbByCrit(['itemtype' => $classname, 'items_id' => $printers_id, 'instantiation_type' => 'NetworkPortEthernet']));
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
                'pattern' => \Printer::class,
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
                        config: new CapacityConfig(['inventory_mainasset' => GenericPrinterAsset::class])
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
}
