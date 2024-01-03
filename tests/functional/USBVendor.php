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

class USBVendor extends DbTestCase
{
    public function testGetList()
    {
        global $DB;

        $vendors = new \USBVendor();
        $usbids = $vendors->getList();
        $nodb_count = count($usbids);

        $this->array($usbids)->size->isGreaterThanOrEqualTo(20000);

        $this->integer(
            $vendors->add([
                'name'  => 'Something to test',
                'vendorid'  => '01ef',
                'deviceid'  => '02ef'
            ])
        )->isGreaterThan(0);

        $usbids = $vendors->getList();
        ++$nodb_count;
        $this->array($usbids)->size->isIdenticalTo($nodb_count);
    }

    public function testGetManufacturer()
    {
        $vendors = new \USBVendor();

        $this->boolean($vendors->getManufacturer('one that does not exists'))->isFalse();
        $this->string($vendors->getManufacturer('0001'))->isIdenticalTo("Fry's Electronics");
        $this->string($vendors->getManufacturer('17e9'))->isIdenticalTo("DisplayLink");
        $this->string($vendors->getManufacturer('17E9'))->isIdenticalTo("DisplayLink");

       //override
        $this->integer(
            $vendors->add([
                'name'  => addslashes("Farnsworth's Electronics"),
                'vendorid'  => '0001'
            ])
        )->isGreaterThan(0);
        $this->string($vendors->getManufacturer('0001'))->isIdenticalTo("Farnsworth's Electronics");
    }

    public function testGetProductName()
    {
        $vendors = new \USBVendor();

        $this->boolean($vendors->getProductName('vendor does not exists', '7778'))->isFalse();
        $this->boolean($vendors->getProductName('0001', 'device does not exists'))->isFalse();
        $this->string($vendors->getProductName('0001', '7778'))->isIdenticalTo('Counterfeit flash drive [Kingston]');
        $this->string($vendors->getProductName('0bdb', '1926'))->isIdenticalTo('H5321 gw Mobile Broadband Module');

       //override
        $this->integer(
            $vendors->add([
                'name'  => 'not the good one',
                'vendorid'  => '0002',
                'deviceid'  => '7778'
            ])
        )->isGreaterThan(0);
        $this->integer(
            $vendors->add([
                'name'  => 'Yeah, that works',
                'vendorid'  => '0001',
                'deviceid'  => '7778'
            ])
        )->isGreaterThan(0);
        $this->string($vendors->getProductName('0001', '7778'))->isIdenticalTo('Yeah, that works');
    }
}
