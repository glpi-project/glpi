<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

use DbTestCase;

class Item_Disk extends DbTestCase {

   public function testCreate() {
      $this->login();

      $this->newTestedInstance();
      $obj = $this->testedInstance;

      // Add
      $computer = getItemByTypeName('Computer', '_test_pc01');
      $this->object($computer)->isInstanceOf('\Computer');

      $this->integer(
         $id = (int)$obj->add([
            'itemtype'     => $computer->getType(),
            'items_id'     => $computer->fields['id'],
            'mountpoint'   => '/'
         ])
      )->isGreaterThan(0);
      $this->boolean($obj->getFromDB($id))->isTrue();
      $this->string($obj->fields['mountpoint'])->isIdenticalTo('/');
   }

   public function testUpdate() {
      $this->login();

      $this->newTestedInstance();
      $obj = $this->testedInstance;

      // Add
      $computer = getItemByTypeName('Computer', '_test_pc01');
      $this->object($computer)->isInstanceOf('\Computer');

      $this->integer(
         $id = (int)$obj->add([
            'itemtype'     => $computer->getType(),
            'items_id'     => $computer->fields['id'],
            'mountpoint'   => '/'
         ])
      )->isGreaterThan(0);
      $this->boolean($obj->getFromDB($id))->isTrue();
      $this->string($obj->fields['mountpoint'])->isIdenticalTo('/');

      $this->boolean($obj->update([
         'id'           => $id,
         'mountpoint'   => '/mnt'
      ]))->isTrue();
      $this->boolean($obj->getFromDB($id))->isTrue();
      $this->string($obj->fields['mountpoint'])->isIdenticalTo('/mnt');
   }

   public function testDelete() {
      $this->login();

      $this->newTestedInstance();
      $obj = $this->testedInstance;

      // Add
      $computer = getItemByTypeName('Computer', '_test_pc01');
      $this->object($computer)->isInstanceOf('\Computer');

      $this->integer(
         $id = (int)$obj->add([
            'itemtype'     => $computer->getType(),
            'items_id'     => $computer->fields['id'],
            'mountpoint'   => '/'
         ])
      )->isGreaterThan(0);
      $this->boolean($obj->getFromDB($id))->isTrue();
      $this->string($obj->fields['mountpoint'])->isIdenticalTo('/');

      $this->boolean(
         (boolean)$obj->delete([
            'id'  => $id
         ])
      )->isTrue();
      $this->boolean($obj->getFromDB($id))->isFalse();
   }
}
