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

   protected function dataName() {
      return [
         ['field', '`field`'],
         ['`field`', '`field`'],
         ['*', '*'],
         ['table.field', '`table`.`field`'],
         ['table.*', '`table`.*']
      ];
   }

   /**
    * @dataProvider dataName
    */
   public function testQuoteName($raw, $quoted) {
      $this->string(\DB::quoteName($raw))->isIdenticalTo($quoted);
   }

   protected function dataValue() {
      return [
         ['foo', "'foo'"],
         ['bar', "'bar'"],
         ['42', "'42'"],
         ['+33', "'+33'"],
         [null, 'NULL'],
         ['null', 'NULL'],
         ['NULL', 'NULL']
      ];
   }

   /**
    * @dataProvider dataValue
    */
   public function testQuoteValue($raw, $expected) {
      $this->string(\DB::quoteValue($raw))->isIdenticalTo($expected);
   }


   protected function dataInsert() {
      return [
         [
            'table', [
               'field'  => 'value',
               'other'  => 'doe'
            ],
            'INSERT INTO `table` (`field`, `other`) VALUES (\'value\', \'doe\')'
         ], [
            '`table`', [
               '`field`'  => 'value',
               '`other`'  => 'doe'
            ],
            'INSERT INTO `table` (`field`, `other`) VALUES (\'value\', \'doe\')'
         ]
      ];
   }

   /**
    * @dataProvider dataInsert
    */
   public function testBuildInsert($table, $values, $expected) {
      $this
         ->if($this->newTestedInstance)
         ->then
            ->string($this->testedInstance->buildInsert($table, $values))->isIdenticalTo($expected);
   }

   protected function dataUpdate() {
      return [
         [
            'table', [
               'field'  => 'value',
               'other'  => 'doe'
            ], [
               'id'  => 1
            ],
            'UPDATE `table` SET `field` = \'value\', `other` = \'doe\' WHERE `id` = 1'
         ], [
            'table', [
               'field'  => 'value'
            ], [
               'id'  => [1, 2]
            ],
            'UPDATE `table` SET `field` = \'value\' WHERE `id` IN (1, 2)'
         ], [
            'table', [
               'field'  => 'value'
            ], [
               'NOT'  => ['id' => [1, 2]]
            ],
            'UPDATE `table` SET `field` = \'value\' WHERE  NOT (`id` IN (1, 2))'
         ]
      ];
   }

   /**
    * @dataProvider dataUpdate
    */
   public function testBuildUpdate($table, $values, $where, $expected) {
       $this
         ->if($this->newTestedInstance)
         ->then
            ->string($this->testedInstance->buildUpdate($table, $values, $where))->isIdenticalTo($expected);
   }

   public function testBuildUpdateWException() {
      $this->exception(
         function() {
            $this
               ->if($this->newTestedInstance)
               ->then
                  ->string($this->testedInstance->buildUpdate('table', ['a' => 'b'], []))->isIdenticalTo('');
         }
      )->hasMessage('Cannot run an UPDATE query without WHERE clause!');
   }

   protected function dataDelete() {
      return [
         [
            'table', [
               'id'  => 1
            ],
            'DELETE FROM `table` WHERE `id` = 1'
         ], [
            'table', [
               'id'  => [1, 2]
            ],
            'DELETE FROM `table` WHERE `id` IN (1, 2)'
         ], [
            'table', [
               'NOT'  => ['id' => [1, 2]]
            ],
            'DELETE FROM `table` WHERE  NOT (`id` IN (1, 2))'
         ]
      ];
   }

   /**
    * @dataProvider dataDelete
    */
   public function testBuildDelete($table, $where, $expected) {
       $this
         ->if($this->newTestedInstance)
         ->then
            ->string($this->testedInstance->buildDelete($table, $where))->isIdenticalTo($expected);
   }

   public function testBuildDeleteWException() {
      $this->exception(
         function() {
            $this
               ->if($this->newTestedInstance)
               ->then
                  ->string($this->testedInstance->buildDelete('table', []))->isIdenticalTo('');
         }
      )->hasMessage('Cannot run an DELETE query without WHERE clause!');
   }
}
