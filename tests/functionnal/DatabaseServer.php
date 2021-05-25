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

/* Test for inc/database.class.php */

class DatabaseServer extends DbTestCase {

   public function testCreate() {
      $db = new \DatabaseServer();

      $dbid = $db->add([
         'name' => 'Maria, maria',
         '_instance_port' => 3306,
         '_instance_size' => 52000
      ]);

      //check DB is created, and load it
      $this->integer($dbid)->isGreaterThan(0);
      $this->boolean($db->getFromDB($dbid))->isTrue();

      //check instance has been created
      $instances = $db->getInstances();
      $this->array($instances)->hasSize(1);
      $instance = $instances[0];
      $this->string($instance['name'])->isIdenticalTo('"Maria, maria" default instance');
      $this->string($instance['port'])->isIdenticalTo('3306');
      $this->integer($instance['size'])->isIdenticalTo(52000);
   }

   public function testInstanceName() {
      $db = new \DatabaseServer();

      $dbid = $db->add([
         'name' => 'Another maria',
         '_instance_name' => 'the instance',
         '_instance_port' => 3306,
         '_instance_size' => 52000
      ]);

      //check DB is created, and load it
      $this->integer($dbid)->isGreaterThan(0);
      $this->boolean($db->getFromDB($dbid))->isTrue();

      //check instance has been created
      $instances = $db->getInstances();
      $this->array($instances)->hasSize(1);
      $instance = $instances[0];
      $this->string($instance['name'])->isIdenticalTo('the instance');
      $this->string($instance['port'])->isIdenticalTo('3306');
      $this->integer($instance['size'])->isIdenticalTo(52000);
   }

   public function testDelete() {
      $db = new \DatabaseServer();

      $dbid = $db->add([
         'name' => 'To be removed',
         '_instance_port' => 3306,
         '_instance_size' => 52000
      ]);

      //check DB is created, and load it
      $this->integer($dbid)->isGreaterThan(0);
      $this->boolean($db->getFromDB($dbid))->isTrue();

      //create link with computer
      $item = new \DatabaseServer_Item();
      $this->integer(
         $item->add([
            'databaseservers_id' => $dbid,
            'itemtype' => 'Computer',
            'items_id' => getItemByTypeName('Computer', '_test_pc01', true)
         ])
      )->isGreaterThan(0);

      //check instance has been created
      $instances = $db->getInstances();
      $this->array($instances)->hasSize(1);
      $instance = $instances[0];
      $this->string($instance['name'])->isIdenticalTo('"To be removed" default instance');
      $this->string($instance['port'])->isIdenticalTo('3306');
      $this->integer($instance['size'])->isIdenticalTo(52000);

      //test removal
      $this->boolean($db->delete(['id' => $dbid, 1]))->isTrue();
      $this->boolean($db->getFromDB($dbid))->isFalse();

      //ensure instance has been dropped aswell
      $this->integer(countElementsInTable(\DatabaseServerInstance::getTable()))->isIdenticalTo(0);
      $this->integer(countElementsInTable(\DatabaseServer_Item::getTable()))->isIdenticalTo(0);
   }
}
