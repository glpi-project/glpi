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

/* Test for inc/Computer_SoftwareLicense.class.php */

class Computer_SoftwareLicenseTest extends DbTestCase {

   /**
    * @covers Computer_SoftwareLicense::testCountForLicense
    */
   public function testCountForLicense() {
      $this->Login();
      $lic = getItemByTypeName('SoftwareLicense', '_test_softlic_1');
      $this->assertEquals(3, Computer_SoftwareLicense::countForLicense($lic->fields['id']));

      $lic = getItemByTypeName('SoftwareLicense', '_test_softlic_2');
      $this->assertEquals(2, Computer_SoftwareLicense::countForLicense($lic->fields['id']));

   }

   /**
    * @covers Computer_SoftwareLicense::testCountForSoftware
    * @depends testCountForLicense
    */
   public function testCountForSoftware() {
      $this->Login();
      $soft = getItemByTypeName('Software', '_test_soft');
      $this->assertEquals(7, Computer_SoftwareLicense::countForSoftware($soft->fields['id']));

      $soft = getItemByTypeName('Software', '_test_softlic_1');
      $this->assertEquals(0, Computer_SoftwareLicense::countForSoftware($soft->fields['id']));

   }

   /**
    * @covers Computer_SoftwareLicense::Add
    * @depends testCountForLicense
    */
   public function testAdd() {

      $computer1 = getItemByTypeName('Computer', '_test_pc01');
      $computer2 = getItemByTypeName('Computer', '_test_pc02');
      $computer3 = getItemByTypeName('Computer', '_test_pc03');
      $lic       = getItemByTypeName('SoftwareLicense', '_test_softlic_1');

      // Do some installations
      $lic_computer = new Computer_SoftwareLicense();
      $input = [
         [
            'computers_id'        => $computer1->fields['id'],
            'softwarelicenses_id' => $lic->fields['id'],
         ], [
            'computers_id'        => $computer2->fields['id'],
            'softwarelicenses_id' => $lic->fields['id']
            ]
         ];

      $lic_computer->add($input);

      $lic = getItemByTypeName('SoftwareLicense', '_test_softlic_1');
      //Number of installations exceed the number declared in the license
      $this->assertEquals(0, $lic->fields['is_valid']);

      $lic = getItemByTypeName('SoftwareLicense', '_test_softlic_2');
      $input = [
         'computers_id'        => $computer1->fields['id'],
         'softwarelicenses_id' => $lic->fields['id']
      ];
      $this->assertGreaterThan(0, $lic_computer->add($input));

      $lic = getItemByTypeName('SoftwareLicense', '_test_softlic_2');
      //License if valid : the number of installations doesn't exceed the number
      //declared
      $this->assertEquals(1, $lic->fields['is_valid']);
   }


   /**
    * @covers Computer_SoftwareLicense::post_deleteFromDB
    * @depends testCountForLicense
    */
   public function testPost_deleteFromDB() {
      $this->Login();
      $lic          = getItemByTypeName('SoftwareLicense', '_test_softlic_1');
      $lic_computer = new Computer_SoftwareLicense();
      $lic_computer->deleteByCriteria(['softwarelicenses_id' => $lic->fields['id']], true);

      $lic = getItemByTypeName('SoftwareLicense', '_test_softlic_1');
      //Number of installations shouldn't now exceed the number declared in the license
      $this->assertEquals(1, $lic->fields['is_valid']);
   }

   /**
    * @covers Computer_SoftwareLicense::upgrade
    */
   public function testUpgrade() {
      $this->Login();
      $old_lic      = getItemByTypeName('SoftwareLicense', '_test_softlic_1');
      $new_lic      = getItemByTypeName('SoftwareLicense', '_test_softlic_3');

      $lic_computer = new Computer_SoftwareLicense();
      $computer     = getItemByTypeName('Computer', '_test_pc01');
      $result = $lic_computer->find("`computers_id`='".$computer->fields['id']."'
                                      AND `softwarelicenses_id`='".$old_lic->fields['id']."'");
      if (!empty($result)) {
         $inst = array_slice($result);
         $lic_computer->upgrade($inst->fields['id'], $new_lic->fields['id']);

         $this->assertNotEquals($old_lic->fields['id'],
                                $lic_computer->fields['softwarelicenses_id']);

         $this->assertEquals($new_lic->fields['id'],
                             $lic_computer->fields['softwarelicenses_id']);
      }
   }

   /**
    * @covers Computer_SoftwareLicense::cloneComputer
    */
   public function testCloneComputer() {
      $this->Login();

      $source_computer = getItemByTypeName('Computer', '_test_pc21');
      $target_computer = getItemByTypeName('Computer', '_test_pc22');

      $lic_computer = new Computer_SoftwareLicense();
      $lic_computer->cloneComputer($source_computer->fields['id'],
                                   $target_computer->fields['id']);

      $input = ['computers_id' => $source_computer->fields['id']];
      $this->assertEquals(2, countElementsInTable('glpi_computers_softwarelicenses',
                                                   $input));

      $input = ['computers_id' => $target_computer->fields['id']];
      $this->assertEquals(2, countElementsInTable('glpi_computers_softwarelicenses',
                                                   $input));
   }

   /**
    * @covers Computer_SoftwareLicense::getTabNameForItem
    * @depends testCloneComputer
    */
   public function testGetTabNameForItem() {
      $this->Login();

      $license      = getItemByTypeName('SoftwareLicense', '_test_softlic_2');
      $cSoftwareLicense = new Computer_SoftwareLicense();
      $this->assertEquals('', $cSoftwareLicense->getTabNameForItem(new Computer(), 0));
      $this->assertEquals('', $cSoftwareLicense->getTabNameForItem($license, 1));

      $_SESSION['glpishow_count_on_tabs'] = 0;
      $expected = [1 => __('Summary'),
                   2 => Computer::getTypeName(Session::getPluralNumber())];
      $this->assertEquals($expected, $cSoftwareLicense->getTabNameForItem($license, 0));

      $_SESSION['glpishow_count_on_tabs'] = 1;
      $expected = [1 => __('Summary'),
                   2 => Computer_SoftwareLicense::createTabEntry(Computer::getTypeName(Session::getPluralNumber()),
                                                                 2)];
      $this->assertEquals($expected, $cSoftwareLicense->getTabNameForItem($license, 0));
   }

   /**
    * @covers Computer_SoftwareLicense::getTabNameForItem
    * @depends testCloneComputer
    */
   public function testCountLicenses() {
      $this->Login();

      $software = getItemByTypeName('Software', '_test_soft');
      $this->assertEquals(5, Computer_SoftwareLicense::countLicenses($software->getID()));

      $software = getItemByTypeName('Software', '_test_soft2');
      $this->assertEquals(0, Computer_SoftwareLicense::countLicenses($software->getID()));
   }

   /**
    * @covers Computer_SoftwareLicense::getTabNameForItem
    * @depends testCloneComputer
    */
   public function testGetSearchOptionsNew() {
      $this->Login();

      $cSoftwareLicense = new Computer_SoftwareLicense();
      $this->assertEquals(4, count($cSoftwareLicense->getSearchOptionsNew()));
   }
}
