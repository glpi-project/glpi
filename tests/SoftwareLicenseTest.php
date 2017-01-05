<?php
/*
-------------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2015-2016 Teclib'.

http://glpi-project.org

based on GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2003-2014 by the INDEPNET Development Team.

-------------------------------------------------------------------------

LICENSE

This file is part of GLPI.

GLPI is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

GLPI is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with GLPI. If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------
*/

/* Test for inc/computer_softwareversion.class.php */

class SoftwareLicenseTest extends DbTestCase {

   /**
    * @covers SoftwareLicense::getTypeName
    */
   public function testTypeName() {
      $this->assertEquals('License', SoftwareLicense::getTypeName(1));
      $this->assertEquals('Licenses', SoftwareLicense::getTypeName(0));
      $this->assertEquals('Licenses', SoftwareLicense::getTypeName(10));
   }

   /**
    * @covers SoftwareLicense::prepareInputForAdd
    */
   public function testPrepareInputForAdd() {
      $license = new SoftwareLicense();

      //With softwares_id, import refused
      $input = [ 'name' => '_test_softlic_3'];
      $this->assertFalse($license->prepareInputForAdd($input));

      //With a softwares_id, import ok
      $input = [ 'name' => '_test_softlic_3', 'softwares_id' => 1];
      $license->input['softwares_id'] = 1;
      $expected = [ 'name' => '_test_softlic_3', 'softwares_id' => 1,
                    'level' => 1, 'completename' => '_test_softlic_3',
                    'softwarelicenses_id' => 0
                 ];
      $this->assertEquals($expected, $license->prepareInputForAdd($input));

      //withtemplate, empty 'expire' should be ignored. id will be replaced in _oldID
      $input = [ 'name' => '_test_softlic_3', 'softwares_id' => 1,
                 'id' => 1, 'withtemplate' => 0, 'expire' => '',
                 'softwarelicenses_id' => 0
              ];
      $expected = [ 'name' => '_test_softlic_3', 'softwares_id' => 1,
                    '_oldID' => 1,'level' => 1,
                    'completename' => '_test_softlic_3', 'softwarelicenses_id' => 0
                 ];
      $this->assertEquals($expected, $license->prepareInputForAdd($input));
   }

   /**
    * @covers Computer_SoftwareVersion::prepareInputForAdd
    * @depends testPrepareInputForAdd
    */
   public function testAdd() {
      $this->Login();

      $license = new SoftwareLicense();
      $input = [ 'name' => '_test_softlic_child'];

      $this->assertEquals(false, $license->add($input));

      $soft   = getItemByTypeName('Software', '_test_soft');
      $father = getItemByTypeName('SoftwareLicense', '_test_softlic_1');
      $input  = ['softwares_id' => $soft->getID(),
                 'expire'=> '2017-01-01 00:00:00',
                 'name' => '_test_softlic_child',
                 'softwarelicenses_id' => $father->getID()
                ];
      $lic_id = $license->add($input);
      $this->assertTrue($lic_id > $soft->getID());
      $this->assertEquals("_test_softlic_1 > _test_softlic_child", $license->fields['completename']);
      $this->assertEquals('_test_softlic_child', $license->fields['name']);
      $this->assertEquals('2017-01-01 00:00:00', $license->fields['expire']);
      $this->assertEquals(2, $license->fields['level']);
   }

   /**
    * @covers SoftwareVersion::testComputeValidityIndicator
    */
   public function testComputeValidityIndicator() {
      $this->Login();

      $license = new SoftwareLicense();
      $soft    = getItemByTypeName('Software', '_test_soft');
      $input   = ['softwares_id' => $soft->fields['id'],
                  'expire'=> '2017-01-01 00:00:00',
                  'name' => '_test_softlic_4',
                  'number' => 3,
                 ];
      $lic_id = $license->add($input);

      $license_computer = new Computer_SoftwareLicense();
      $comp1            = getItemByTypeName('Computer', '_test_pc01');
      $comp2            = getItemByTypeName('Computer', '_test_pc02');

      $input_comp = ['softwarelicenses_id' => $lic_id, 'computers_id' => $comp1->fields['id'],
                     'is_deleted' => 0, 'is_dynamic' => 0
                    ];
      $id = $license_computer->add($input_comp);
      $this->assertTrue($id > 0);

      //Test if number is illimited
      $this->assertEquals(1, SoftwareLicense::computeValidityIndicator($lic_id, -1));
      $this->assertEquals(0, SoftwareLicense::computeValidityIndicator($lic_id, 0));

      $input_comp['computers_id'] = $comp2->fields['id'];
      $id = $license_computer->add($input_comp);
      $this->assertTrue($id > 0);
      $this->assertEquals(1, SoftwareLicense::computeValidityIndicator($lic_id, 2));
      $this->assertEquals(0, SoftwareLicense::computeValidityIndicator($lic_id, 1));
   }

   /**
    * @covers SoftwareLicense::prepareInputForUpdate
    */
   public function testPrepareInputForUpdate() {
      $this->Login();

      $license = new SoftwareLicense();

      $soft    = getItemByTypeName('Software', '_test_soft');
      $input   = ['softwares_id' => $soft->fields['id'],
                  'expire'=> '2017-01-01 00:00:00',
                  'name' => '_test_softlic_4',
                  'number' => 3,
                 ];
      $lic_id = $license->add($input);

      $input    = ['id' => $lic_id, 'number' => 3];
      $expected = ['id' => $lic_id, 'number' => 3, 'is_valid' => 1];
      $this->assertEquals($expected, $license->prepareInputForUpdate($input));
   }

   /**
    * @covers SoftwareLicense::updateValidityIndicator
    */
   public function testUpdateValidityIndicator() {
      $this->Login();

      $license = new SoftwareLicense();
      $comp1  = getItemByTypeName('Computer', '_test_pc01');

      $this->createLicenseWithInstall('_test_softlic_4',
                                      ['_test_pc01', '_test_pc02', '_test_pc22']);

      //Delete a license installation
      $lic = getItemByTypeName('SoftwareLicense', '_test_softlic_4');
      $license_computer = new Computer_SoftwareLicense();
      $input = ['softwarelicenses_id' => $lic->fields['id'],
                'computers_id'        => $comp1->fields['id'],
               ];
      $this->assertTrue($license_computer->deleteByCriteria($input, true));

      //Change the number of assets from 3 to 1
      $input = ['id'     => $lic->fields['id'],
                'number' => 1,
               ];
      $license->update($input);
      $license->getFromDB($lic->fields['id']);

      $this->assertTrue($license->fields['id'] > 0);
      $this->assertEquals($license->fields['number'], $input['number']);

      //Update validity indicator
      $license->updateValidityIndicator($license->fields['id']);
      $this->assertEquals(0, $license->fields['is_valid']);
   }

   public function createLicenseWithInstall($name, $computers) {
      $lic    = getItemByTypeName('SoftwareLicense', $name);
      foreach ($computers as $computer) {
         $comp = getItemByTypeName('Computer', $computer);
         $this->createInstall($lic->fields['id'], $comp->fields['id']);
      }
   }

   public function createInstall($licenses_id, $computers_id) {
      $license_computer = new Computer_SoftwareLicense();
      $input = ['softwarelicenses_id' => $licenses_id,
                'computers_id'        => $computers_id,
                'is_dynamic'          => 0,
                'is_deleted'          => 0
               ];
      $this->assertTrue($license_computer->add($input) > 0);
   }
}
