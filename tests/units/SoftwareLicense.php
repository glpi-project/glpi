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

namespace tests\units;

use \DbTestCase;

/* Test for inc/computer_softwareversion.class.php */

/**
 * @engine isolate
 */
class SoftwareLicense extends DbTestCase {

   public function testTypeName() {
      $this->string(\SoftwareLicense::getTypeName(1))->isIdenticalTo('License');
      $this->string(\SoftwareLicense::getTypeName(0))->isIdenticalTo('Licenses');
      $this->string(\SoftwareLicense::getTypeName(10))->isIdenticalTo('Licenses');
   }

   public function testPrepareInputForAdd() {
      $license = new \SoftwareLicense();

      //Without softwares_id, import refused
      $input = [
         'name'         => '_test_softlic_3',
         'entities_id'  => 0
      ];
      $this->boolean($license->prepareInputForAdd($input))->isFalse();

      //With a softwares_id, import ok
      $input = [ 'name' => '_test_softlic_3', 'softwares_id' => 1];
      $license->input['softwares_id'] = 1;
      $expected = [ 'name' => '_test_softlic_3', 'softwares_id' => 1,
                    'softwarelicenses_id' => 0, 'level' => 1,
                    'completename' => '_test_softlic_3'
                 ];
      $this->array($license->prepareInputForAdd($input))->isIdenticalTo($expected);

      //withtemplate, empty 'expire' should be ignored. id will be replaced in _oldID
      $input = [ 'name' => '_test_softlic_3', 'softwares_id' => 1,
                 'id' => 1, 'withtemplate' => 0, 'expire' => '',
                 'softwarelicenses_id' => 0
              ];
      $expected = [ 'name' => '_test_softlic_3', 'softwares_id' => 1,
                    'softwarelicenses_id' => 0, 'level' => 1,
                    'completename' => '_test_softlic_3', '_oldID' => 1
                 ];
      $this->array($license->prepareInputForAdd($input))->isIdenticalTo($expected);
   }

   public function testAdd() {
      $this->login();

      $license = new \SoftwareLicense();
      $input = [ 'name' => '_test_softlic_child'];

      $this->boolean($license->add($input))->isFalse();

      $soft   = getItemByTypeName('Software', '_test_soft');
      $father = getItemByTypeName('SoftwareLicense', '_test_softlic_1');
      $input  = ['softwares_id' => $soft->getID(),
                 'expire'=> '2017-01-01 00:00:00',
                 'name' => '_test_softlic_child',
                 'softwarelicenses_id' => $father->getID(),
                 'entities_id'         => $father->fields['entities_id']
                ];
      $lic_id = $license->add($input);
      $this->boolean($lic_id > $soft->getID())->isTrue();
      $this->string($license->fields['completename'])->isIdenticalTo("_test_softlic_1 > _test_softlic_child");
      $this->string($license->fields['name'])->isIdenticalTo('_test_softlic_child');
      $this->string($license->fields['expire'])->isIdenticalTo('2017-01-01 00:00:00');
      $this->variable($license->fields['level'])->isEqualTo(2);
   }

   public function testComputeValidityIndicator() {
      $this->login();

      $license = new \SoftwareLicense();
      $soft    = getItemByTypeName('Software', '_test_soft');
      $input   = ['softwares_id' => $soft->fields['id'],
                  'expire'=> '2017-01-01 00:00:00',
                  'name' => '_test_softlic_4',
                  'number' => 3,
                  'entities_id' => 0
                 ];
      $lic_id = $license->add($input);
      $this->integer((int)$lic_id)->isGreaterThan(0);

      $license_computer = new \Computer_SoftwareLicense();
      $comp1            = getItemByTypeName('Computer', '_test_pc01');
      $comp2            = getItemByTypeName('Computer', '_test_pc02');

      $input_comp = ['softwarelicenses_id' => $lic_id, 'computers_id' => $comp1->fields['id'],
                     'is_deleted' => 0, 'is_dynamic' => 0
                    ];
      $this->integer((int)$license_computer->add($input_comp))->isGreaterThan(0);

      //Test if number is illimited
      $this->variable(\SoftwareLicense::computeValidityIndicator($lic_id, -1))->isEqualTo(1);
      $this->variable(\SoftwareLicense::computeValidityIndicator($lic_id, 0))->isEqualTo(0);

      $input_comp['computers_id'] = $comp2->fields['id'];
      $this->integer((int)$license_computer->add($input_comp))->isGreaterThan(0);
      $this->variable(\SoftwareLicense::computeValidityIndicator($lic_id, 2))->isEqualTo(1);
      $this->variable(\SoftwareLicense::computeValidityIndicator($lic_id, 1))->isEqualTo(0);
   }

   public function testPrepareInputForUpdate() {
      $this->login();

      $license = new \SoftwareLicense();

      $soft    = getItemByTypeName('Software', '_test_soft');
      $input   = ['softwares_id' => $soft->fields['id'],
                  'expire'=> '2017-01-01 00:00:00',
                  'name' => '_test_softlic_4',
                  'number' => 3,
                  'entities_id' => 0
                 ];
      $lic_id = $license->add($input);
      $this->integer((int)$lic_id)->isGreaterThan(0);

      $input    = ['id' => $lic_id, 'number' => 3];
      $expected = ['id' => $lic_id, 'number' => 3, 'is_valid' => 1];
      $this->array($license->prepareInputForUpdate($input))->isIdenticalTo($expected);
   }

   public function testUpdateValidityIndicator() {
      $this->login();

      $license = new \SoftwareLicense();
      $comp1  = getItemByTypeName('Computer', '_test_pc01');

      $this->createLicenseWithInstall('_test_softlic_4',
                                      ['_test_pc01', '_test_pc02', '_test_pc22']);

      //Delete a license installation
      $lic = getItemByTypeName('SoftwareLicense', '_test_softlic_4');
      $license_computer = new \Computer_SoftwareLicense();
      $input = ['softwarelicenses_id' => $lic->fields['id'],
                'computers_id'        => $comp1->fields['id'],
               ];
      $this->boolean($license_computer->deleteByCriteria($input, true))->isTrue();

      $orig_number = $lic->getField('number');
      //Change the number of assets to 1
      $input = ['id'     => $lic->fields['id'],
                'number' => 1,
               ];
      $license->update($input);
      $this->boolean($license->getFromDB($lic->fields['id']))->isTrue();

      $this->integer((int)$license->fields['id'])->isGreaterThan(0);
      $this->variable($input['number'])->isEqualTo($license->fields['number']);

      //Update validity indicator
      $license->updateValidityIndicator($license->fields['id']);
      $this->variable($license->fields['is_valid'])->isEqualTo(0);

      //cleanup
      $input = ['id'     => $lic->fields['id'],
                'number' => $orig_number,
               ];
      $license->update($input);

      //Update validity indicator
      $license->updateValidityIndicator($license->fields['id']);
      $this->variable($license->fields['is_valid'])->isEqualTo(1);
   }

   public function createLicenseWithInstall($name, $computers) {
      $lic    = getItemByTypeName('SoftwareLicense', $name);
      foreach ($computers as $computer) {
         $comp = getItemByTypeName('Computer', $computer);
         $this->createInstall($lic->fields['id'], $comp->fields['id']);
      }
   }

   public function createInstall($licenses_id, $computers_id) {
      $license_computer = new \Computer_SoftwareLicense();
      $input = ['softwarelicenses_id' => $licenses_id,
                'computers_id'        => $computers_id,
                'is_dynamic'          => 0,
                'is_deleted'          => 0
               ];
      $this->integer((int)$license_computer->add($input))->isGreaterThan(0);
   }
}
