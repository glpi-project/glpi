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

/* Test for inc/computer_softwareversion.class.php */

/**
 * @engine isolate
 */
class Computer_SoftwareVersion extends DbTestCase {

   public function testTypeName() {
      $this->string(\Computer_SoftwareVersion::getTypeName(1))->isIdenticalTo('Installation');
      $this->string(\Computer_SoftwareVersion::getTypeName(0))->isIdenticalTo('Installations');
      $this->string(\Computer_SoftwareVersion::getTypeName(10))->isIdenticalTo('Installations');
   }

   public function testPrepareInputForAdd() {
      $this->login();

      $computer1 = getItemByTypeName('Computer', '_test_pc01');
      $ver = getItemByTypeName('SoftwareVersion', '_test_softver_1', true);

      // Do some installations
      $ins = new \Computer_SoftwareVersion();
      $this->integer((int)$ins->add([
         'computers_id'        => $computer1->getID(),
         'softwareversions_id' => $ver,
      ]))->isGreaterThan(0);

      $input = [
         'computers_id' => $computer1->getID(),
         'name'         => 'A name'
      ];

      $expected = [
         'computers_id'         => $computer1->getID(),
         'name'                 => 'A name',
         'is_template_computer' => $computer1->getField('is_template'),
         'is_deleted_computer'  => $computer1->getField('is_deleted'),
         'entities_id'          => '1',
         'is_recursive'         => 0
      ];

      $this->setEntity('_test_root_entity', true);
      $this->array($ins->prepareInputForAdd($input))->isIdenticalTo($expected);
   }

   public function testPrepareInputForUpdate() {
      $this->login();

      $computer1 = getItemByTypeName('Computer', '_test_pc01');
      $ver = getItemByTypeName('SoftwareVersion', '_test_softver_1', true);

      // Do some installations
      $ins = new \Computer_SoftwareVersion();
      $this->integer((int)$ins->add([
         'computers_id'        => $computer1->getID(),
         'softwareversions_id' => $ver,
      ]))->isGreaterThan(0);

      $input = [
         'computers_id' => $computer1->getID(),
         'name'         => 'Another name'
      ];

      $expected = [
         'computers_id'         => $computer1->getID(),
         'name'                 => 'Another name',
         'is_template_computer' => $computer1->getField('is_template'),
         'is_deleted_computer'  => $computer1->getField('is_deleted')
      ];

      $this->array($ins->prepareInputForUpdate($input))->isIdenticalTo($expected);
   }


   public function testCountInstall() {
      $this->login();

      $computer1 = getItemByTypeName('Computer', '_test_pc01', true);
      $computer11 = getItemByTypeName('Computer', '_test_pc11', true);
      $computer12 = getItemByTypeName('Computer', '_test_pc12', true);
      $ver = getItemByTypeName('SoftwareVersion', '_test_softver_1', true);

      // Do some installations
      $ins = new \Computer_SoftwareVersion();
      $this->integer((int)$ins->add([
         'computers_id'        => $computer1,
         'softwareversions_id' => $ver,
      ]))->isGreaterThan(0);
      $this->integer($ins->add([
         'computers_id'        => $computer11,
         'softwareversions_id' => $ver,
      ]))->isGreaterThan(0);
      $this->integer($ins->add([
         'computers_id'        => $computer12,
         'softwareversions_id' => $ver,
      ]))->isGreaterThan(0);

      // Count installations
      $this->setEntity('_test_root_entity', true);
      $this->integer((int)\Computer_SoftwareVersion::countForVersion($ver), 'count in all tree')
         ->isIdenticalTo(3);

      $this->setEntity('_test_root_entity', false);
      $this->integer((int)\Computer_SoftwareVersion::countForVersion($ver), 'count in root')
         ->isIdenticalTo(1);

      $this->setEntity('_test_child_1', false);
      $this->integer((int)\Computer_SoftwareVersion::countForVersion($ver), 'count in child')
         ->isIdenticalTo(2);
   }

   public function testUpdateDatasFromComputer() {
      $c00 = 1566671;
      $computer1 = getItemByTypeName('Computer', '_test_pc01');
      $ver1 = getItemByTypeName('SoftwareVersion', '_test_softver_1', true);
      $ver2 = getItemByTypeName('SoftwareVersion', '_test_softver_2', true);

      // Do some installations
      $softver = new \Computer_SoftwareVersion();
      $softver01 = $softver->add([
         'computers_id'        => $computer1->getID(),
         'softwareversions_id' => $ver1,
      ]);
      $this->integer((int)$softver01)->isGreaterThan(0);
      $softver02 = $softver->add([
         'computers_id'        => $computer1->getID(),
         'softwareversions_id' => $ver2,
      ]);
      $this->integer((int)$softver02)->isGreaterThan(0);

      foreach ([$softver01, $softver02] as $tsoftver) {
         $o = new \Computer_SoftwareVersion();
         $this->boolean($o->getFromDb($tsoftver))->isTrue();
         $this->variable($o->getField('is_deleted_computer'))->isEqualTo(0);
      }

      //computer that does not exists
      $this->boolean($softver->updateDatasForComputer($c00))->isFalse();

      //update existing computer
      $input = $computer1->fields;
      $input['is_deleted'] = '1';
      $this->boolean($computer1->update($input))->isTrue();

      $this->object($softver->updateDatasForComputer($computer1->getID()))->isInstanceOf('\PDOStatement');

      //check if all has been updated
      foreach ([$softver01, $softver02] as $tsoftver) {
         $o = new \Computer_SoftwareVersion();
         $this->boolean($o->getFromDb($tsoftver))->isTrue();
         $this->variable($o->getField('is_deleted_computer'))->isEqualTo(1);
      }

      //restore computer state
      $input['is_deleted'] = '0';
      $this->boolean($computer1->update($input))->isTrue();
   }

   public function testCountForSoftware() {
      $soft1 = getItemByTypeName('Software', '_test_soft');
      $computer1 = getItemByTypeName('Computer', '_test_pc01');

      $this->Login();

      $this->integer(
         (int)\Computer_SoftwareVersion::countForSoftware($soft1->fields['id'])
      )->isIdenticalTo(0);

      $csoftver = new \Computer_SoftwareVersion();
      $this->integer(
         (int)$csoftver->add([
            'computers_id'          => $computer1->fields['id'],
            'softwareversions_id'   => $soft1->fields['id']
         ])
      )->isGreaterThan(0);

      $this->integer(
         (int)\Computer_SoftwareVersion::countForSoftware($soft1->fields['id'])
      )->isIdenticalTo(1);
   }
}
