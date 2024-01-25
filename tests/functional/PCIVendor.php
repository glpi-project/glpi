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

class PCIVendor extends DbTestCase
{
    public function testGetList()
    {
        global $DB;

        $vendors = new \PCIVendor();
        $pciids = $vendors->getList();
        $nodb_count = count($pciids);

        $this->array($pciids)->size->isGreaterThanOrEqualTo(15000);

        $this->integer(
            $vendors->add([
                'name'  => 'Something to test',
                'vendorid'  => '01ef',
                'deviceid'  => '02ef'
            ])
        )->isGreaterThan(0);

        $pciids = $vendors->getList();
        ++$nodb_count;
        $this->array($pciids)->size->isIdenticalTo($nodb_count);
    }

    public function testGetManufacturer()
    {
        $vendors = new \PCIVendor();

        $this->boolean($vendors->getManufacturer('one that does not exists'))->isFalse();
        $this->string($vendors->getManufacturer('0010'))->isIdenticalTo("Allied Telesis, Inc (Wrong ID)");

       //override
        $this->integer(
            $vendors->add([
                'name'  => addslashes("UnAllied Telesis, Inc (Good ID)"),
                'vendorid'  => '0010'
            ])
        )->isGreaterThan(0);
        $this->string($vendors->getManufacturer('0010'))->isIdenticalTo("UnAllied Telesis, Inc (Good ID)");
    }

    public function testGetProductName()
    {
        $vendors = new \PCIVendor();

        $this->boolean($vendors->getProductName('vendor does not exists', '9139'))->isFalse();
        $this->boolean($vendors->getProductName('0010', 'device does not exists'))->isFalse();
        $this->string($vendors->getProductName('0010', '8139'))->isIdenticalTo('AT-2500TX V3 Ethernet');

       //override
        $this->integer(
            $vendors->add([
                'name'  => 'not the good one',
                'vendorid'  => '0002',
                'deviceid'  => '8139'
            ])
        )->isGreaterThan(0);
        $this->integer(
            $vendors->add([
                'name'  => 'Yeah, that works',
                'vendorid'  => '0010',
                'deviceid'  => '8139'
            ])
        )->isGreaterThan(0);
        $this->string($vendors->getProductName('0010', '8139'))->isIdenticalTo('Yeah, that works');
    }
}
