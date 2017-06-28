<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

class Devicesimcard extends DbTestCase {
   private $method;

   public function beforeTestMethod($method) {
      parent::beforeTestMethod($method);
      //to handle GLPI barbarian replacements.
      $this->method = str_replace(
            ['\\', 'beforeTestMethod'],
            ['', $method],
            __METHOD__
            );
   }

   public function testAdd() {
      $this->login();
      $obj = new \DeviceSimcard();

      // Add
      $in = [
            'designation'              => $this->method,
            'manufacturers_id'         => $this->getUniqueInteger(),
            'devicesimcardtypes_id'    => $this->getUniqueInteger(),
            'voltage'                  => $this->getUniqueInteger(),
            'allow_voip'               => '1'
      ];
      $id = $obj->add($in);
      $this->integer((int)$id)->isGreaterThan(0);
      $this->boolean($obj->getFromDB($id))->isTrue();

      // getField methods
      $this->variable($obj->getField('id'))->isEqualTo($id);
      foreach ($in as $k => $v) {
         $this->variable($obj->getField($k))->isEqualTo($v);
      }
   }

   public function testUpdate() {
      $this->login();
      $obj = new \DeviceSimcard();

      // Add
      $id = $obj->add([
            'designation' => $this->getUniqueString(),
      ]);
      $this->integer($id)->isGreaterThan(0);

      // Update
      $in = [
            'id'                       => $id,
            'designation'              => $this->method,
            'manufacturers_id'         => $this->getUniqueInteger(),
            'devicesimcardtypes_id'    => $this->getUniqueInteger(),
            'voltage'                  => $this->getUniqueInteger(),
            'allow_voip'               => '1'
      ];
      $this->boolean($obj->update($in))->isTrue();
      $this->boolean($obj->getFromDB($id))->isTrue();

      // getField methods
      foreach ($in as $k => $v) {
         $this->variable($obj->getField($k))->isEqualTo($v);
      }
   }

   public function testDelete() {
      $this->login();
      $obj = new \DeviceSimcard();

      // Add
      $id = $obj->add([
            'designation' => $this->method,
      ]);
      $this->integer($id)->isGreaterThan(0);

      // Delete
      $in = [
            'id'                       => $obj->getID(),
      ];
      $this->boolean($obj->delete($in))->isTrue();
   }
}