<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace tests\units;

use \DbTestCase;

/* Test for inc/computer_softwarelicense.class.php */

class Computer_SoftwareLicense extends DbTestCase {

   public function testCountForLicense() {
      $this->login();
      $lic = getItemByTypeName('SoftwareLicense', '_test_softlic_1');
      $this->integer((int)\Computer_SoftwareLicense::countForLicense($lic->fields['id']))->isIdenticalTo(3);

      $lic = getItemByTypeName('SoftwareLicense', '_test_softlic_2');
      $this->integer((int)\Computer_SoftwareLicense::countForLicense($lic->fields['id']))->isIdenticalTo(2);

      $lic = getItemByTypeName('SoftwareLicense', '_test_softlic_3');
      $this->integer((int)\Computer_SoftwareLicense::countForLicense($lic->fields['id']))->isIdenticalTo(2);

      $lic = getItemByTypeName('SoftwareLicense', '_test_softlic_4');
      $this->integer((int)\Computer_SoftwareLicense::countForLicense($lic->fields['id']))->isIdenticalTo(0);
   }

   public function testCountForSoftware() {
      $this->login();
      $soft = getItemByTypeName('Software', '_test_soft');
      $this->integer((int)\Computer_SoftwareLicense::countForSoftware($soft->fields['id']))->isIdenticalTo(7);

      $soft = getItemByTypeName('Software', '_test_soft2');
      $this->integer((int)\Computer_SoftwareLicense::countForSoftware($soft->fields['id']))->isIdenticalTo(0);

   }

   public function testGetLicenseForInstallation() {
      $computer1 = getItemByTypeName('Computer', '_test_pc01');
      $version1 = getItemByTypeName('SoftwareVersion', '_test_softver_1');

      $this->Login();

      $this->array(
         \Computer_SoftwareLicense::getLicenseForInstallation(
            $computer1->fields['id'],
            $version1->fields['id']
         )
      )->isEmpty();

      //simulate license install
      $lic = getItemByTypeName('SoftwareLicense', '_test_softlic_1');
      $this->boolean(
         $lic->update([
            'id'                       => $lic->fields['id'],
            'softwareversions_id_use'  => $version1->fields['id']
         ])
      )->isTrue();

      $this->array(
         \Computer_SoftwareLicense::getLicenseForInstallation(
            $computer1->fields['id'],
            $version1->fields['id']
         )
      )->hasSize(1);

      //reset license
      $this->boolean(
         $lic->update([
            'id'                       => $lic->fields['id'],
            'softwareversions_id_use'  => 'NULL'
         ])
      )->isTrue();
   }

   public function testAddUpdateDelete() {
      $computer1 = getItemByTypeName('Computer', '_test_pc01');
      $computer2 = getItemByTypeName('Computer', '_test_pc02');
      $computer3 = getItemByTypeName('Computer', '_test_pc11');
      $lic       = getItemByTypeName('SoftwareLicense', '_test_softlic_4');

      // Do some installations
      $lic_computer = new \Computer_SoftwareLicense();

      $input = [
         'computers_id'        => $computer1->fields['id'],
         'softwarelicenses_id' => $lic->fields['id'],
      ];
      $this->integer((int)$lic_computer->add($input))->isGreaterThan(0);

      $input = [
         'computers_id'        => $computer2->fields['id'],
         'softwarelicenses_id' => $lic->fields['id'],
      ];
      $this->integer((int)$lic_computer->add($input))->isGreaterThan(0);

      $lic = getItemByTypeName('SoftwareLicense', '_test_softlic_4');
      //License is valid: the number of affectations doesn't exceed declared number
      $this->variable($lic->fields['is_valid'])->isEqualTo(1);

      $input = [
         'computers_id'        => $computer3->fields['id'],
         'softwarelicenses_id' => $lic->fields['id']
      ];
      $this->integer((int)$lic_computer->add($input))->isGreaterThan(0);

      $lic = getItemByTypeName('SoftwareLicense', '_test_softlic_4');
      //Number of affectations exceed the number declared in the license
      $this->variable($lic->fields['is_valid'])->isEqualTo(0);

      //test upgrade
      $old_lic      = getItemByTypeName('SoftwareLicense', '_test_softlic_4');
      $new_lic      = getItemByTypeName('SoftwareLicense', '_test_softlic_3');

      $lic_computer = new \Computer_SoftwareLicense();
      $computer     = getItemByTypeName('Computer', '_test_pc01');
      $result = $lic_computer->find([
         'computers_id'          => $computer->fields['id'],
         'softwarelicenses_id'   => $old_lic->fields['id']
      ]);
      $this->boolean($lic_computer->getFromDB(array_keys($result)[0]))->isTrue();

      $lic_computer->upgrade($lic_computer->getID(), $new_lic->fields['id']);

      $this->variable($lic_computer->fields['softwarelicenses_id'])
         ->isNotEqualTo($old_lic->getID())
         ->isEqualTo($new_lic->getID());

      //test delete
      $lic_computer = new \Computer_SoftwareLicense();
      $this->boolean($lic_computer->deleteByCriteria(['softwarelicenses_id' => $lic->fields['id']], true))->isTrue();

      $lic = getItemByTypeName('SoftwareLicense', '_test_softlic_4');
      //Number of installations shouldn't now exceed the number declared in the license
      $this->variable($lic->fields['is_valid'])->isEqualTo(1);
   }


   public function testCloneComputer() {
      $this->login();

      $source_computer = getItemByTypeName('Computer', '_test_pc21');
      $target_computer = getItemByTypeName('Computer', '_test_pc22');

      $lic_computer = new \Computer_SoftwareLicense();
      $lic_computer->cloneComputer($source_computer->fields['id'],
                                   $target_computer->fields['id']);

      $input = ['computers_id' => $source_computer->fields['id']];
      $this->integer((int)countElementsInTable('glpi_computers_softwarelicenses', $input))
         ->isIdenticalTo(3);

      $input = ['computers_id' => $target_computer->fields['id']];
      $this->integer((int)countElementsInTable('glpi_computers_softwarelicenses', $input))
         ->isIdenticalTo(3);

      //cleanup
      $lic_computer = new \Computer_SoftwareLicense();
      $lic_computer->deleteByCriteria(['computers_id' => $target_computer->fields['id']], true);
   }

   public function testGetTabNameForItem() {
      $this->login();

      $license      = getItemByTypeName('SoftwareLicense', '_test_softlic_2');
      $cSoftwareLicense = new \Computer_SoftwareLicense();
      $this->string($cSoftwareLicense->getTabNameForItem(new \Computer(), 0))->isEmpty();
      $this->string($cSoftwareLicense->getTabNameForItem($license, 1))->isEmpty();

      $_SESSION['glpishow_count_on_tabs'] = 0;
      $expected = [1 => __('Summary'),
                   2 => \Computer::getTypeName(\Session::getPluralNumber())];
      $this->array($cSoftwareLicense->getTabNameForItem($license, 0))->isIdenticalTo($expected);

      $_SESSION['glpishow_count_on_tabs'] = 1;
      $expected = [1 => __('Summary'),
                   2 => \Computer_SoftwareLicense::createTabEntry(\Computer::getTypeName(\Session::getPluralNumber()),
                                                                 2)];
      $this->array($cSoftwareLicense->getTabNameForItem($license, 0))->isIdenticalTo($expected);
   }

   public function testCountLicenses() {
      $this->login();

      $software = getItemByTypeName('Software', '_test_soft');
      $this->integer((int)\Computer_SoftwareLicense::countLicenses($software->getID()))->isIdenticalTo(5);

      $software = getItemByTypeName('Software', '_test_soft2');
      $this->integer((int)\Computer_SoftwareLicense::countLicenses($software->getID()))->isIdenticalTo(0);
   }

   public function testGetSearchOptionsNew() {
      $this->login();

      $cSoftwareLicense = new \Computer_SoftwareLicense();
      $this->array($cSoftwareLicense->rawSearchOptions())
         ->hasSize(4);
   }
}
