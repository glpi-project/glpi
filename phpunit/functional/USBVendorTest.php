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

/* Test for inc/usbvendor.class.php */

class USBVendorTest extends DbTestCase
{
    public function testGetList()
    {
        $vendors = new \USBVendor();
        $usbids = $vendors->getList();
        $nodb_count = count($usbids);

        $this->assertGreaterThanOrEqual(20000, $nodb_count);

        $this->assertGreaterThan(
            0,
            $vendors->add([
                'name'  => 'Something to test',
                'vendorid'  => '01ef',
                'deviceid'  => '02ef'
            ])
        );

        $usbids = $vendors->getList();
        ++$nodb_count;
        $this->assertCount($nodb_count, $usbids);
    }

    public function testGetManufacturer()
    {
        $vendors = new \USBVendor();

        $this->assertFalse($vendors->getManufacturer('one that does not exists'));
        $this->assertSame(
            "Fry's Electronics",
            $vendors->getManufacturer('0001')
        );
        $this->assertSame(
            "DisplayLink",
            $vendors->getManufacturer('17e9')
        );
        $this->assertSame(
            "DisplayLink",
            $vendors->getManufacturer('17E9')
        );

        //override
        $this->assertGreaterThan(
            0,
            $vendors->add([
                'name'  => addslashes("Farnsworth's Electronics"),
                'vendorid'  => '0001'
            ])
        );
        $this->assertSame(
            "Farnsworth's Electronics",
            $vendors->getManufacturer('0001')
        );
    }

    public function testGetProductName()
    {
        $vendors = new \USBVendor();

        $this->assertFalse($vendors->getProductName('vendor does not exists', '7778'));
        $this->assertFalse($vendors->getProductName('0001', 'device does not exists'));
        $this->assertSame(
            'Counterfeit flash drive [Kingston]',
            $vendors->getProductName('0001', '7778')
        );
        $this->assertSame(
            'H5321 gw Mobile Broadband Module',
            $vendors->getProductName('0bdb', '1926')
        );

        //override
        $this->assertGreaterThan(
            0,
            $vendors->add([
                'name'  => 'not the good one',
                'vendorid'  => '0002',
                'deviceid'  => '7778'
            ])
        );
        $this->assertGreaterThan(
            0,
            $vendors->add([
                'name'  => 'Yeah, that works',
                'vendorid'  => '0001',
                'deviceid'  => '7778'
            ])
        );
        $this->assertSame(
            'Yeah, that works',
            $vendors->getProductName('0001', '7778')
        );
    }
}
