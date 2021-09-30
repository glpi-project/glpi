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
      $instance = new \DatabaseInstance();

      $instid = $instance->add([
         'name' => 'To be removed',
         'port' => 3306,
         'size' => 52000
      ]);

      //check DB is created, and load it
      $this->integer($instid)->isGreaterThan(0);
      $this->boolean($instance->getFromDB($instid))->isTrue();

      //create databases
      for ($i = 0; $i < 5; ++$i) {
         $database = new \Database();
         $this->integer(
            $database->add([
               'name'                   => 'Database ' . $i,
               'databaseinstances_id'   => $instid
            ])
         )->isGreaterThan(0);
      }
      $this->integer(countElementsInTable(\Database::getTable()))->isIdenticalTo(5);

      //test removal
      $this->boolean($instance->delete(['id' => $instid], 1))->isTrue();
      $this->boolean($instance->getFromDB($instid))->isFalse();

      //ensure databases has been dropped aswell
      $this->integer(countElementsInTable(\Database::getTable()))->isIdenticalTo(0);
   }
}
