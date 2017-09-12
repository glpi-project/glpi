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

use \atoum;

/* Test for inc/dbmysql.class.php */

class DB extends atoum {

   public function testTableExist() {
      $this
         ->if($this->newTestedInstance)
         ->then
            ->boolean($this->testedInstance->tableExists('glpi_configs'))->isTrue()
            ->boolean($this->testedInstance->tableExists('fakeTable'))->isFalse();
   }

   public function testFieldExists() {
      $this
         ->if($this->newTestedInstance)
         ->then
            ->boolean($this->testedInstance->fieldExists('glpi_configs', 'id'))->isTrue()
            ->boolean($this->testedInstance->fieldExists('glpi_configs', 'fakeField'))->isFalse()
            ->when(
               function () {
                  $this->boolean($this->testedInstance->fieldExists('fakeTable', 'id'))->isFalse();
               }
            )->error
               ->withType(E_USER_WARNING)
               ->exists()
            ->when(
               function () {
                  $this->boolean($this->testedInstance->fieldExists('fakeTable', 'fakeField'))->isFalse();
               }
            )->error
               ->withType(E_USER_WARNING)
               ->exists();
   }

   public function testListTables() {
      $dbu = new \DbUtils();
      $this->newTestedInstance();
      $list = $this->testedInstance->list_tables();
      $this->object($list)->isInstanceOf('mysqli_result');
      $this->integer($this->testedInstance->numrows($list))->isGreaterThan(200);

      //check if each table has a corresponding itemtype
      while ($line = $this->testedInstance->fetch_array($list)) {
         $this->array($line)
            ->hasSize(2);
         $table = $line[0];
         $type = $dbu->getItemTypeForTable($table);

         $this->object($item = $dbu->getItemForItemtype($type))->isInstanceOf('CommonDBTM');
         $this->string(get_class($item))->isIdenticalTo($type);
         $this->string($dbu->getTableForItemType($type))->isIdenticalTo($table);
      }
   }

   public function testEscape() {
      $this
         ->if($this->newTestedInstance)
         ->then
            ->string($this->testedInstance->escape('nothing to do'))->isIdenticalTo('nothing to do')
            ->string($this->testedInstance->escape("shoul'be escaped"))->isIdenticalTo("shoul\\'be escaped")
            ->string($this->testedInstance->escape("First\nSecond"))->isIdenticalTo("First\\nSecond")
            ->string($this->testedInstance->escape("First\rSecond"))->isIdenticalTo("First\\rSecond")
            ->string($this->testedInstance->escape('Hi, "you"'))->isIdenticalTo('Hi, \\"you\\"');
   }
}
