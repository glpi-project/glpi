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
            'name' => 'Supplier Type'
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
            'pictures'            => 'pictures'
        ]);

        // Test item cloning
        $added = $supplier->clone();
        $this->assertGreaterThan(0, (int)$added);

        $clonedSupplier = new \Supplier();
        $this->assertTrue($clonedSupplier->getFromDB($added));

        $fields = $supplier->fields;

        // Check the values. Id and dates must be different, everything else must be equal
        foreach ($fields as $k => $v) {
            switch ($k) {
                case 'id':
                    $this->assertNotEquals($supplier->getField($k), $clonedSupplier->getField($k));
                    break;
                case 'date_mod':
                case 'date_creation':
                    $dateClone = new \DateTime($clonedSupplier->getField($k));
                    $expectedDate = new \DateTime($date);
                    $this->assertEquals($expectedDate, $dateClone);
                    break;
                case 'name':
                    $this->assertSame("create_supplier (copy)", $clonedSupplier->getField($k));
                    break;
                default:
                    $this->assertEquals($supplier->getField($k), $clonedSupplier->getField($k));
            }
        }
    }
}
