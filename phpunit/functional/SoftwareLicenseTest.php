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

/* Test for inc/softwarelicense.class.php */

/**
 * @engine isolate
 */
class SoftwareLicenseTest extends DbTestCase
{
    public function testTypeName()
    {
        $this->assertSame('License', \SoftwareLicense::getTypeName(1));
        $this->assertSame('Licenses', \SoftwareLicense::getTypeName(0));
        $this->assertSame('Licenses', \SoftwareLicense::getTypeName(10));
    }

    public function testPrepareInputForAdd()
    {
        $license = new \SoftwareLicense();

       //Without softwares_id, import refused
        $input = [
            'name'         => 'not_inserted_software_license',
            'entities_id'  => 0
        ];
        $this->assertFalse($license->prepareInputForAdd($input));
        $this->hasSessionMessages(ERROR, ['Please select a software for this license']);

       //With a softwares_id, import ok
        $input = [ 'name' => 'inserted_sofwarelicense', 'softwares_id' => 1];
        $license->input['softwares_id'] = 1;
        $expected = [ 'name' => 'inserted_sofwarelicense', 'softwares_id' => 1,
            'softwarelicenses_id' => 0, 'level' => 1,
            'completename' => 'inserted_sofwarelicense'
        ];
        $this->assertSame($expected, $license->prepareInputForAdd($input));

       //withtemplate, empty 'expire' should be ignored. id will be replaced in _oldID
        $input = [ 'name' => 'other_inserted_sofwarelicense', 'softwares_id' => 1,
            'id' => 1, 'withtemplate' => 0, 'expire' => '',
            'softwarelicenses_id' => 0
        ];
        $expected = [ 'name' => 'other_inserted_sofwarelicense', 'softwares_id' => 1,
            'softwarelicenses_id' => 0, 'level' => 1,
            'completename' => 'other_inserted_sofwarelicense', '_oldID' => 1
        ];
        $this->assertSame($expected, $license->prepareInputForAdd($input));
    }

    /**
     * Creates a new software
     *
     * @return \Software
     */
    private function createSoft()
    {
        $software     = new \Software();
        $softwares_id = $software->add([
            'name'         => 'Software ' . $this->getUniqueString(),
            'is_template'  => 0,
            'entities_id'  => 0
        ]);
        $this->assertGreaterThan(0, (int)$softwares_id);
        $this->assertTrue($software->getFromDB($softwares_id));

        return $software;
    }

    public function testAdd()
    {
        $this->login();

        $license = new \SoftwareLicense();
        $input = [ 'name' => 'not_inserted_software_license_child'];

        $this->assertFalse($license->add($input));
        $this->hasSessionMessages(ERROR, ['Please select a software for this license']);

        $software     = $this->createSoft();

        $parentlicense = new \SoftwareLicense();
        $parentlicense_id = $parentlicense->add([
            'name'         => 'a_software_license',
            'softwares_id' => $software->getID(),
            'entities_id'  => 0
        ]);
        $this->assertGreaterThan(0, (int)$parentlicense_id);
        $this->assertTrue($parentlicense->getFromDB($parentlicense_id));

        $this->assertSame("a_software_license", $parentlicense->fields['completename']);
        $this->assertSame('a_software_license', $parentlicense->fields['name']);
        $this->assertNull($parentlicense->fields['expire']);
        $this->assertEquals(1, $parentlicense->fields['level']);

        $input  = [
            'softwares_id'          => $software->getID(),
            'expire'                => '2017-01-01 00:00:00',
            'name'                  => 'a_child_license',
            'softwarelicenses_id'   => $parentlicense_id,
            'entities_id'           => $parentlicense->fields['entities_id']
        ];
        $lic_id = $license->add($input);
        $this->assertGreaterThan($parentlicense_id, $lic_id);
        $this->assertTrue($license->getFromDB($lic_id));

        $this->assertSame("a_software_license > a_child_license", $license->fields['completename']);
        $this->assertSame('a_child_license', $license->fields['name']);
        $this->assertSame('2017-01-01', $license->fields['expire']);
        $this->assertEquals(2, $license->fields['level']);
    }

    public function testComputeValidityIndicator()
    {
        $this->login();

        $license = new \SoftwareLicense();

        $software = $this->createSoft();

        $input   = [
            'softwares_id' => $software->getID(),
            'expire'       => '2017-01-01 00:00:00',
            'name'         => 'Test licence ' . $this->getUniqueString(),
            'number'       => 3,
            'entities_id'  => 0
        ];
        $lic_id = $license->add($input);
        $this->assertGreaterThan(0, (int)$lic_id);
        $this->assertTrue($license->getFromDB($lic_id));

        $license_computer = new \Item_SoftwareLicense();
        $comp1            = getItemByTypeName('Computer', '_test_pc01');
        $comp2            = getItemByTypeName('Computer', '_test_pc02');

        $input_comp = [
            'softwarelicenses_id'   => $lic_id,
            'items_id'              => $comp1->getID(),
            'itemtype'              => 'Computer',
            'is_deleted'            => 0,
            'is_dynamic'            => 0
        ];
        $this->assertGreaterThan(0, (int)$license_computer->add($input_comp));

       //Test if number is illimited
        $this->assertEquals(1, \SoftwareLicense::computeValidityIndicator($lic_id, -1));
        $this->assertEquals(0, \SoftwareLicense::computeValidityIndicator($lic_id, 0));

        $input_comp['computers_id'] = $comp2->getID();
        $this->assertGreaterThan(0, (int)$license_computer->add($input_comp));

        $this->assertEquals(1, \SoftwareLicense::computeValidityIndicator($lic_id, 2));
        $this->assertEquals(0, \SoftwareLicense::computeValidityIndicator($lic_id, 1));
    }

    public function testPrepareInputForUpdate()
    {
        $this->login();

        $license = new \SoftwareLicense();

        $software = $this->createSoft();

        $input   = [
            'softwares_id' => $software->getID(),
            'expire'       => '2017-01-01 00:00:00',
            'name'         => 'Test licence ' . $this->getUniqueString(),
            'number'       => 3,
            'entities_id'  => 0
        ];
        $lic_id = $license->add($input);
        $this->assertGreaterThan(0, (int)$lic_id);

        $input    = ['id' => $lic_id, 'number' => 3];
        $expected = ['id' => $lic_id, 'number' => 3, 'is_valid' => 1];
        $this->assertSame($expected, $license->prepareInputForUpdate($input));
    }

    public function testUpdateValidityIndicator()
    {
        $this->login();

        $license = new \SoftwareLicense();
        $comp1  = getItemByTypeName('Computer', '_test_pc01');

        $software = $this->createSoft();
        $input   = [
            'softwares_id' => $software->getID(),
            'expire'       => '2017-01-01 00:00:00',
            'name'         => 'Test licence ' . $this->getUniqueString(),
            'number'       => 3,
            'entities_id'  => 0
        ];
        $lic_id = $license->add($input);
        $this->assertGreaterThan(0, (int)$lic_id);
        $this->assertTrue($license->getFromDB($lic_id));

        $this->createLicenseInstall(
            $license,
            ['_test_pc01', '_test_pc02', '_test_pc22']
        );

       //Delete a license installation
        $license_computer = new \Item_SoftwareLicense();
        $input = [
            'softwarelicenses_id'   => $license->getID(),
            'items_id'              => $comp1->getID(),
            'itemtype'              => 'Computer'
        ];
        $this->assertTrue($license_computer->deleteByCriteria($input, true));

        $orig_number = $license->getField('number');
       //Change the number of assets to 1
        $input = [
            'id'     => $license->getID(),
            'number' => 1,
        ];
        $license->update($input);
        $this->assertTrue($license->getFromDB($license->getID()));

        $this->assertGreaterThan(0, (int)$license->getID());
        $this->assertEquals($license->fields['number'], $input['number']);

       //Update validity indicator
        $license->updateValidityIndicator($license->getID());
        $this->assertEquals(0, $license->fields['is_valid']);

       //cleanup
        $input = [
            'id'     => $license->getID(),
            'number' => $orig_number,
        ];
        $license->update($input);

       //Update validity indicator
        $license->updateValidityIndicator($license->fields['id']);
        $this->assertEquals(1, $license->fields['is_valid']);
    }

    private function createLicenseInstall(\SoftwareLicense $license, $computers)
    {
        foreach ($computers as $computer) {
            $comp = getItemByTypeName('Computer', $computer);
            $this->createInstall($license->getID(), $comp->getID());
        }
    }

    private function createInstall($licenses_id, $items_id)
    {
        $license_computer = new \Item_SoftwareLicense();
        $input = [
            'softwarelicenses_id'   => $licenses_id,
            'items_id'              => $items_id,
            'itemtype'              => 'Computer',
            'is_dynamic'            => 0,
            'is_deleted'            => 0
        ];
        $this->assertGreaterThan(0, (int)$license_computer->add($input));
    }
}
