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

namespace tests\units;

/* Test for src/Supplier.php */

use Cartridge;
use CartridgeItem;
use Computer;
use Consumable;
use ConsumableItem;
use DeviceSimcard;
use Infocom;
use Item_DeviceSimcard;
use Supplier;
use Symfony\Component\DomCrawler\Crawler;

class SupplierTest extends \DbTestCase
{
    public function testClone()
    {
        $this->login();

        $date = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date;

        $this->setEntity('_test_root_entity', true);

        // Create trype
        $supplier_type = $this->createItem('SupplierType', [
            'name' => 'Supplier Type',
        ]);

        // Create supplier
        $supplier = $this->createItem('Supplier', [
            'name'                => 'create_supplier',
            'entities_id'         => 0,
            'suppliertypes_id'     => $supplier_type->fields['id'],
            'registration_number' => '123',
            'address'              => 'supplier address',
            'postcode'            => 'supplier postcode',
            'town'                => 'supplier town',
            'state'               => 'supplier state',
            'country'             => 'supplier country',
            'website'             => 'supplier website',
            'phonenumber'         => '456',
            'comment'             => 'comment',
            'fax'                 => '789',
            'email'               => 'supplier@supplier.com',
            'pictures'            => 'pictures',
        ]);

        // Test item cloning
        $added = $supplier->clone();
        $this->assertGreaterThan(0, (int) $added);

        $clonedSupplier = new Supplier();
        $this->assertTrue($clonedSupplier->getFromDB($added));

        $fields = $supplier->fields;

        // Check the values. Id and dates must be different, everything else must be equal
        $expected = $supplier->fields;
        $expected['id'] = $clonedSupplier->getID();
        $expected['date_creation'] = $date;
        $expected['date_mod'] = $date;
        $expected['name'] = "create_supplier (copy)";
        $this->assertEquals($expected, $clonedSupplier->fields);
    }

    public function testShowInfocoms(): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $this->login();

        $supplier = $this->createItem(Supplier::class, [
            'name'        => $this->getUniqueString(),
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $func_name = __FUNCTION__;

        $fn_get_input_for_itemtype = function ($itemtype, $i) use ($func_name) {
            $input = [
                $itemtype::getNameField() => $func_name . $i,
                'entities_id' => $this->getTestRootEntity(true),
                'serial' => "{$itemtype}-serial",
                'otherserial' => "{$itemtype}-otherserial",
            ];
            switch ($itemtype) {
                case Cartridge::class:
                    return [
                        'cartridgeitems_id' => getItemByTypeName(CartridgeItem::class, '_test_cartridgeitem01', true),
                    ] + $input;
                case Consumable::class:
                    return [
                        'consumableitems_id' => getItemByTypeName(ConsumableItem::class, '_test_consumableitem01', true),
                    ] + $input;
                case Item_DeviceSimcard::class:
                    return [
                        'devicesimcards_id' => getItemByTypeName(DeviceSimcard::class, '_test_simcard_1', true),
                        'itemtype' => Computer::class,
                        'items_id' => getItemByTypeName(Computer::class, '_test_pc01', true),
                    ] + $input;
                default:
                    return $input;
            }
        };

        $excluded = Infocom::getExcludedTypes();
        $expected_itemtypes = array_filter(
            $CFG_GLPI['infocom_types'],
            static fn($t) => !in_array($t, $excluded, true)
        );
        foreach ($expected_itemtypes as $itemtype) {
            $this->createItem(Infocom::class, [
                'entities_id'    => $this->getTestRootEntity(true),
                'suppliers_id' => $supplier->getID(),
                'itemtype'        => $itemtype,
                'items_id'        => $this->createItem(
                    $itemtype,
                    $fn_get_input_for_itemtype($itemtype, 1),
                    ['entities_id', 'serial', 'otherserial']
                )->getID(),
            ]);
            $this->createItem(Infocom::class, [
                'entities_id'    => $this->getTestRootEntity(true),
                'suppliers_id' => $supplier->getID(),
                'itemtype'        => $itemtype,
                'items_id'        => $this->createItem(
                    $itemtype,
                    $fn_get_input_for_itemtype($itemtype, 2),
                    ['entities_id', 'serial', 'otherserial']
                )->getID(),
            ]);
        }

        ob_start();
        $supplier->showInfocoms();
        $out = ob_get_clean();

        $crawler = new Crawler($out);
        $rows = $crawler->filter('table tbody tr');
        $this->assertCount(count($expected_itemtypes) * 2, $rows);
        $infocom_types = array_combine(
            array_map(static fn($t) => $t::getTypeName(2), $expected_itemtypes),
            $expected_itemtypes,
        );
        $current_itemtype = null;
        $current_itemtype_item = null;
        $seen_itemtypes = [];
        foreach ($rows as $row) {
            $cells = (new Crawler($row))->filter('td');
            $type_col_content = trim($cells->getNode(0)->textContent);
            if ($type_col_content !== '') {
                [$current_itemtype, $nb] = explode(':', $type_col_content);
                $current_itemtype = $infocom_types[trim($current_itemtype)];
                $seen_itemtypes[] = $current_itemtype;
                $this->assertEquals(2, (int) trim($nb));
                $current_itemtype_item = getItemForItemtype($current_itemtype);
            }
            $this->assertStringContainsString($current_itemtype_item->isEntityAssign() ? 'Root entity > _test_root_entity' : '-', trim($cells->getNode(1)->textContent));
            if ($current_itemtype === Cartridge::class) {
                $this->assertStringContainsString('_test_cartridgeitem01', trim($cells->getNode(2)->textContent));
            } elseif ($current_itemtype === Consumable::class) {
                $this->assertStringContainsString('_test_consumableitem01', trim($cells->getNode(2)->textContent));
            } elseif ($current_itemtype === Item_DeviceSimcard::class) {
                $this->assertStringContainsString("{$current_itemtype}-serial", trim($cells->getNode(2)->textContent));
            } else {
                $this->assertStringContainsString(__FUNCTION__, trim($cells->getNode(2)->textContent));
            }
            $this->assertStringContainsString($current_itemtype_item->isField('serial') ? "{$current_itemtype}-serial" : '-', trim($cells->getNode(3)->textContent));
            $this->assertStringContainsString($current_itemtype_item->isField('otherserial') ? "{$current_itemtype}-otherserial" : '-', trim($cells->getNode(4)->textContent));
        }
        $missing_itemtypes = array_diff($expected_itemtypes, $seen_itemtypes);
        $this->assertEmpty($missing_itemtypes, 'Some infocom types are missing in the output: ' . implode(', ', array_map(fn($t) => $t::getTypeName(2), $missing_itemtypes)));

        // Test replacement of item lists with links to search results when too many items
        $_SESSION['glpilist_limit'] = 1;
        ob_start();
        $supplier->showInfocoms();
        $out = ob_get_clean();
        $crawler = new Crawler($out);
        $rows = $crawler->filter('table tbody tr');
        $this->assertCount(count($expected_itemtypes), $rows);
        foreach ($rows as $row) {
            $cells = (new Crawler($row))->filter('td');
            $type_col_content = trim($cells->getNode(0)->textContent);
            [$current_itemtype, $nb] = explode(':', $type_col_content);
            $current_itemtype = $infocom_types[trim($current_itemtype)];
            $this->assertEquals(2, (int) trim($nb));
            $this->assertStringContainsString(
                $current_itemtype::getSearchURL(),
                trim($cells->eq(2)->html()),
                "Missing link to search results for infocom type {$current_itemtype}",
            );
        }
    }
}
