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

/* Test for inc/databaseinstance.class.php */

class DatabaseInstance extends DbTestCase {

   public function testDelete() {
      $db = new \DatabaseInstance();

      $dbid = $db->add([
         'name' => 'To be removed',
         'port' => 3306,
         'size' => 52000
      ]);

      //check DB is created, and load it
      $this->integer($dbid)->isGreaterThan(0);
      $this->boolean($db->getFromDB($dbid))->isTrue();

      //create link with computer
      $item = new \DatabaseInstance_Item();
      $this->integer(
         $item->add([
            'databaseinstances_id' => $dbid,
            'itemtype' => 'Computer',
            'items_id' => getItemByTypeName('Computer', '_test_pc01', true)
         ])
      )->isGreaterThan(0);

      //test removal
      $this->boolean($db->delete(['id' => $dbid, 1]))->isTrue();
      $this->boolean($db->getFromDB($dbid))->isFalse();

      //ensure instance has been dropped aswell
      $this->integer(countElementsInTable(\DatabaseInstance_Item::getTable()))->isIdenticalTo(0);
   }
}
