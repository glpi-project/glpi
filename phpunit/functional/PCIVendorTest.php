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

/* Test for inc/pcivendor.class.php */

class PCIVendorTest extends DbTestCase
{
    public function testGetList()
    {
        $vendors = new \PCIVendor();
        $pciids = $vendors->getList();
        $nodb_count = count($pciids);

        $this->assertGreaterThan(15000, $nodb_count);

        $this->assertGreaterThan(
            0,
            $vendors->add([
                'name'  => 'Something to test',
                'vendorid'  => '01ef',
                'deviceid'  => '02ef'
            ])
        );

        $pciids = $vendors->getList();
        ++$nodb_count;
        $this->assertCount($nodb_count, $pciids);
    }

    public function testGetManufacturer()
    {
        $vendors = new \PCIVendor();

        $this->assertFalse($vendors->getManufacturer('one that does not exists'));
        $this->assertSame(
            "Allied Telesis, Inc (Wrong ID)",
            $vendors->getManufacturer('0010')
        );

        //override
        $this->assertGreaterThan(
            0,
            $vendors->add([
                'name'  => addslashes("UnAllied Telesis, Inc (Good ID)"),
                'vendorid'  => '0010'
            ])
        );
        $this->assertSame(
            "UnAllied Telesis, Inc (Good ID)",
            $vendors->getManufacturer('0010')
        );
    }

    public function testGetProductName()
    {
        $vendors = new \PCIVendor();

        $this->assertFalse($vendors->getProductName('vendor does not exists', '9139'));
        $this->assertFalse($vendors->getProductName('0010', 'device does not exists'));
        $this->assertSame(
            'AT-2500TX V3 Ethernet',
            $vendors->getProductName('0010', '8139')
        );

        //override
        $this->assertGreaterThan(
            0,
            $vendors->add([
                'name'  => 'not the good one',
                'vendorid'  => '0002',
                'deviceid'  => '8139'
            ])
        );
        $this->assertGreaterThan(
            0,
            $vendors->add([
                'name'  => 'Yeah, that works',
                'vendorid'  => '0010',
                'deviceid'  => '8139'
            ])
        );
        $this->assertSame(
            'Yeah, that works',
            $vendors->getProductName('0010', '8139')
        );
    }
}
