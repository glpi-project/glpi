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

class Item_DeviceSensor extends DbTestCase {

   public function testCreate() {
      $this->login();
      $obj = new \Item_DeviceSensor();

      // Add
      $computer = getItemByTypeName('Computer', '_test_pc01');
      $this->object($computer)->isInstanceOf('\Computer');
      $deviceSensor = getItemByTypeName('DeviceSensor', '_test_sensor_1');
      $this->object($deviceSensor)->isInstanceOf('\DeviceSensor');
      $in = [
            'itemtype'           => 'Computer',
            'items_id'           => $computer->getID(),
            'devicesensors_id'  => $deviceSensor->getID(),
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
      $obj = new \Item_DeviceSensor();

      // Add
      $computer = getItemByTypeName('Computer', '_test_pc01');
      $this->object($computer)->isInstanceOf('\Computer');
      $deviceSensor = getItemByTypeName('DeviceSensor', '_test_sensor_1');
      $this->object($deviceSensor)->isInstanceOf('\DeviceSensor');
      $id = $obj->add([
            'itemtype'           => 'Computer',
            'items_id'           => $computer->getID(),
            'devicesensors_id'   => $deviceSensor->getID(),
            'entities_id'        => 0,
      ]);
       $this->integer($id)->isGreaterThan(0);

      // Update
      $id = $obj->getID();
      $in = [
            'id'                       => $id,
            'serial'                   => $this->getUniqueString(),
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
      $obj = new \Item_DeviceSensor();

      // Add
      $computer = getItemByTypeName('Computer', '_test_pc01');
      $this->object($computer)->isInstanceOf('\Computer');
      $deviceSensor = getItemByTypeName('DeviceSensor', '_test_sensor_1');
      $this->object($deviceSensor)->isInstanceOf('\DeviceSensor');
      $id = $obj->add([
            'itemtype'           => 'Computer',
            'items_id'           => $computer->getID(),
            'devicesensors_id'   => $deviceSensor->getID(),
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
