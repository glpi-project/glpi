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

use DbTestCase;

class Item_DeviceSimcard extends DbTestCase {

   public function testCreate() {
      $this->login();
      $obj = new \Item_DeviceSimcard();

      // Add
      $computer = getItemByTypeName('Computer', '_test_pc01');
      $this->object($computer)->isInstanceOf('\Computer');
      $deviceSimcard = getItemForItemtype('DeviceSimcard', '_test_simcard_1');
      $this->object($deviceSimcard)->isInstanceOf('\DeviceSimcard');
      $in = [
            'itemtype'           => 'Computer',
            'items_id'           => $computer->getID(),
            'devicesimcards_id'  => $deviceSimcard->getID(),
            'entities_id'        => 0,
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
      $obj = new \Item_DeviceSimcard();

      // Add
      $computer = getItemByTypeName('Computer', '_test_pc01');
      $this->object($computer)->isInstanceOf('\Computer');
      $deviceSimcard = getItemForItemtype('Devicesimcard', '_test_simcard_1');
      $this->object($deviceSimcard)->isInstanceOf('\Devicesimcard');
      $id = $obj->add([
            'itemtype'           => 'Computer',
            'items_id'           => $computer->getID(),
            'devicesimcards_id'  => $deviceSimcard->getID(),
            'entities_id'        => 0,
      ]);
       $this->integer($id)->isGreaterThan(0);

      // Update
      $id = $obj->getID();
      $in = [
            'id'                       => $id,
            'pin'                      => '0123',
            'pin2'                     => '1234',
            'puk'                      => '2345',
            'puk2'                     => '3456',
      ];
      $this->boolean($obj->update($in))->isTrue();
      $this->boolean($obj->getFromDB($id))->isTrue();

      // getField methods
      foreach ($in as $k => $v) {
         $this->variable($obj->getField($k))->isEqualTo($v);
      }
   }

   public function testDenyPinPukUpdate() {
      global $DB;
      //drop update access on item_devicesimcard
      $DB->update(
         'glpi_profilerights',
         ['rights' => 1], [
            'profiles_id'  => 4,
            'name'         => 'devicesimcard_pinpuk'
         ]
      );

      // Profile changed then login
      $this->login();
      //reset rights. Done here so ACLs are reset even if tests fails.
      $DB->update(
         'glpi_profilerights',
         ['rights' => 3], [
            'profiles_id'  => 4,
            'name'         => 'devicesimcard_pinpuk'
         ]
      );

      $obj = new \Item_DeviceSimcard();

      // Add
      $computer = getItemByTypeName('Computer', '_test_pc01');
      $this->object($computer)->isInstanceOf('\Computer');
      $deviceSimcard = getItemForItemtype('Devicesimcard', '_test_simcard_1');
      $this->object($deviceSimcard)->isInstanceOf('\Devicesimcard');
      $id = $obj->add([
            'itemtype'           => 'Computer',
            'items_id'           => $computer->getID(),
            'devicesimcards_id'  => $deviceSimcard->getID(),
            'entities_id'        => 0,
            'pin'                => '0123',
            'pin2'               => '1234',
            'puk'                => '2345',
            'puk2'               => '3456',
      ]);
      $this->integer($id)->isGreaterThan(0);

      // Update
      $id = $obj->getID();
      $in = [
            'id'                 => $id,
            'pin'                => '0000',
            'pin2'               => '0000',
            'puk'                => '0000',
            'puk2'               => '0000',
      ];
      $this->boolean($obj->update($in))->isTrue();
      $this->boolean($obj->getFromDB($id))->isTrue();

      // getField methods
      unset($in['id']);
      foreach ($in as $k => $v) {
         $this->variable($obj->getField($k))->isNotEqualTo($v);
      }
   }


   public function testDelete() {
      $this->login();
      $obj = new \Item_DeviceSimcard();

      // Add
      $computer = getItemByTypeName('Computer', '_test_pc01');
      $this->object($computer)->isInstanceOf('\Computer');
      $deviceSimcard = getItemForItemtype('Devicesimcard', '_test_simcard_1');
      $this->object($deviceSimcard)->isInstanceOf('\Devicesimcard');
      $id = $obj->add([
            'itemtype'           => 'Computer',
            'items_id'           => $computer->getID(),
            'devicesimcards_id'  => $deviceSimcard->getID(),
            'entities_id'        => 0,
      ]);
      $this->integer($id)->isGreaterThan(0);

      // Delete
      $in = [
            'id'                       => $obj->getID(),
      ];
      $this->boolean($obj->delete($in))->isTrue();
   }

}
