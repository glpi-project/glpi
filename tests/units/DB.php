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

/* Test for inc/dbmysql.class.php */

class DB extends \GLPITestCase {

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
            ->boolean($this->testedInstance->fieldExists('glpi_configs', 'ID'))->isFalse()
            ->boolean($this->testedInstance->fieldExists('glpi_configs', 'fakeField'))->isFalse();

      $this->exception(
         function() {
            $this->boolean($this->testedInstance->fieldExists('fakeTable', 'id'))->isFalse();
         }
      )->hasMessage('Table fakeTable does not exists');

      $this->exception(
         function() {
            $this->boolean($this->testedInstance->fieldExists('fakeTable', 'fakeField'))->isFalse();
         }
      )->hasMessage('Table fakeTable does not exists');
   }

   protected function dataName() {
      return [
         ['field', '`field`'],
         ['`field`', '`field`'],
         ['*', '*'],
         ['table.field', '`table`.`field`'],
         ['table.*', '`table`.*'],
         ['field AS f', '`field` AS `f`'],
         ['field as f', '`field` AS `f`'],
         ['table.field as f', '`table`.`field` AS `f`'],
      ];
   }

   /**
    * @dataProvider dataName
    */
   public function testQuoteName($raw, $quoted) {
      $this
         ->if($this->newTestedInstance)
         ->then
            ->string($this->testedInstance->quoteName($raw))->isIdenticalTo($quoted);
   }

   protected function dataValue() {
      return [
         ['foo', "'foo'"],
         ['bar', "'bar'"],
         ['42', "'42'"],
         ['+33', "'+33'"],
         [null, 'NULL'],
         ['null', 'NULL'],
         ['NULL', 'NULL'],
         ['`field`', '`field`'],
         ['`field', "`field"]
      ];
   }

   /**
    * @dataProvider dataValue
    */
   public function testQuoteValue($raw, $expected) {
      $this
         ->if($this->newTestedInstance)
         ->then
         ->string($this->testedInstance->quoteValue($raw))->isIdenticalTo($expected);
   }


   protected function dataInsert() {
      return [
         [
            'table', [
               'field'  => 'value',
               'other'  => 'doe'
            ],
            'INSERT INTO `table` (`field`, `other`) VALUES (:field, :other)'
         ], [
            '`table`', [
               '`field`'  => 'value',
               '`other`'  => 'doe'
            ],
            'INSERT INTO `table` (`field`, `other`) VALUES (:field, :other)'
         ], [
            'table', [
               'field'  => new \QueryParam(),
               'other'  => new \QueryParam()
            ],
            'INSERT INTO `table` (`field`, `other`) VALUES (:field, :other)'
         ], [
            'table', [
               'field'  => new \QueryParam('field'),
               'other'  => new \QueryParam('other')
            ],
            'INSERT INTO `table` (`field`, `other`) VALUES (:field, :other)'
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
            'UPDATE `table` SET `field` = ?, `other` = ? WHERE `id` = ?',
            ['value', 'doe', 1]
         ], [
            'table', [
               'field'  => 'value'
            ], [
               'id'  => [1, 2]
            ],
            'UPDATE `table` SET `field` = ? WHERE `id` IN (?,?)',
            ['value', 1, 2]
         ], [
            'table', [
               'field'  => 'value'
            ], [
               'NOT'  => ['id' => [1, 2]]
            ],
            'UPDATE `table` SET `field` = ? WHERE  NOT (`id` IN (?,?))',
            ['value', 1, 2]
         ], [
            'table', [
               'field'  => new \QueryParam()
            ], [
               'NOT' => ['id' => [new \QueryParam(), new \QueryParam()]]
            ],
            'UPDATE `table` SET `field` = ? WHERE  NOT (`id` IN (?,?))',
            []
         ], [
            'table', [
               'field'  => new \QueryParam('field')
            ], [
               'NOT' => ['id' => [new \QueryParam('idone'), new \QueryParam('idtwo')]]
            ],
            'UPDATE `table` SET `field` = ? WHERE  NOT (`id` IN (?,?))',
            []
         ], [
            'table', [
               'field'  => new \QueryExpression(\DB::quoteName('field') . ' + 1')
            ], [
               'id'  => [1, 2]
            ],
            'UPDATE `table` SET `field` = `field` + 1 WHERE `id` IN (?,?)',
            [1, 2]
         ]
      ];
   }

   /**
    * @dataProvider dataUpdate
    */
   public function testBuildUpdate($table, $values, $where, $expected, $parameters) {
       $this
         ->if($this->newTestedInstance)
         ->then
            ->string($this->testedInstance->buildUpdate($table, $values, $where))->isIdenticalTo($expected)
            ->array($values)->isIdenticalTo($parameters);
   }

   public function testBuildUpdateWException() {
      $this->exception(
         function() {
            $set = ['a' => 'b'];
            $where = [];

            $this
               ->if($this->newTestedInstance)
               ->then
               ->string($this->testedInstance->buildUpdate('table', $set, $where))->isIdenticalTo('');
         }
      )->hasMessage('Cannot run an UPDATE query without WHERE clause!');
   }

   protected function dataDelete() {
      return [
         [
            'table', [
               'id'  => 1
            ],
            'DELETE FROM `table` WHERE `id` = ?',
            [1]
         ], [
            'table', [
               'id'  => [1, 2]
            ],
            'DELETE FROM `table` WHERE `id` IN (?,?)',
            [1, 2]
         ], [
            'table', [
               'NOT'  => ['id' => [1, 2]]
            ],
            'DELETE FROM `table` WHERE  NOT (`id` IN (?,?))',
            [1, 2]
         ], [
            'table', [
               'NOT'  => ['id' => [new \QueryParam(), new \QueryParam()]]
            ],
            'DELETE FROM `table` WHERE  NOT (`id` IN (?,?))',
            []
         ], [
            'table', [
               'NOT'  => ['id' => [new \QueryParam('idone'), new \QueryParam('idtwo')]]
            ],
            'DELETE FROM `table` WHERE  NOT (`id` IN (?,?))',
            []
         ]
      ];
   }

   /**
    * @dataProvider dataDelete
    */
   public function testBuildDelete($table, $where, $expected, $parameters) {
      $params = [];
       $this
         ->if($this->newTestedInstance)
         ->then
            ->string($this->testedInstance->buildDelete($table, $params, $where))->isIdenticalTo($expected)
            ->array($params)->isIdenticalTo($parameters);
   }

   public function testBuildDeleteWException() {
      $this->exception(
         function() {
            $set = [];
            $this
               ->if($this->newTestedInstance)
               ->then
                  ->string($this->testedInstance->buildDelete('table', $set, []))->isIdenticalTo('');
         }
      )->hasMessage('Cannot run an DELETE query without WHERE clause!');
   }

   public function testListTables() {
      $this
         ->if($this->newTestedInstance)
         ->then
            ->given($tables = $this->testedInstance->listTables())
            ->object($tables)
               ->isInstanceOf(\DBMysqlIterator::class)
            ->integer(count($tables))
               ->isGreaterThan(100)
            ->given($tables = $this->testedInstance->listTables('glpi_configs'))
            ->object($tables)
               ->isInstanceOf(\DBMysqlIterator::class)
               ->hasSize(1);

   }

   public function testTablesHasItemtype() {
      $dbu = new \DbUtils();
      $this->newTestedInstance();
      $list = $this->testedInstance->listTables();
      $this->object($list)->isInstanceOf(\DBmysqlIterator::class);
      $this->integer(count($list))->isGreaterThan(200);

      //check if each table has a corresponding itemtype
      while ($line = $list->next()) {
         $this->array($line)
            ->hasSize(1);
         $table = $line['TABLE_NAME'];
         $type = $dbu->getItemTypeForTable($table);

         $this->object($item = $dbu->getItemForItemtype($type))->isInstanceOf('CommonDBTM', $table);
         $this->string(get_class($item))->isIdenticalTo($type);
         $this->string($dbu->getTableForItemType($type))->isIdenticalTo($table);
      }
   }

   public function testQuote() {
      $this
         ->if($this->newTestedInstance)
         ->then
            ->string($this->testedInstance->quote('nothing to do'))->isIdenticalTo("'nothing to do'")
            ->string($this->testedInstance->quote("shoul'be escaped"))->isIdenticalTo("'shoul\\'be escaped'")
            ->string($this->testedInstance->quote("First\nSecond"))->isIdenticalTo("'First\\nSecond'")
            ->string($this->testedInstance->quote("First\rSecond"))->isIdenticalTo("'First\\rSecond'")
            ->string($this->testedInstance->quote('Hi, "you"'))->isIdenticalTo("'Hi, \\\"you\\\"'");
   }
}
