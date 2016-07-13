<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// Generic test classe, to be extended for CommonDBTM Object

class DBmysqlIteratorTest extends DbTestCase {


   public function testQuery() {

      $req = 'SELECT Something FROM Somewhere';
      $it = new DBmysqlIterator(NULL, $req);
      $this->assertEquals($req, $it->getSql());
   }


   public function testOnlyTable() {

      $it = new DBmysqlIterator(NULL, 'foo');
      $this->assertEquals('SELECT * FROM `foo`', $it->getSql(), 'Single table, without quotes');

      $it = new DBmysqlIterator(NULL, '`foo`');
      $this->assertEquals('SELECT * FROM `foo`', $it->getSql(), 'Single table, with quotes');

      $it = new DBmysqlIterator(NULL, ['foo', '`bar`']);
      $this->assertEquals('SELECT * FROM `foo`, `bar`', $it->getSql(), 'Multiples tables');
   }


   public function testDebug() {

      $it = new DBmysqlIterator(NULL, 'foo', ['FIELDS' => 'name', 'id = ' . mt_rand()], true);
      $buf = file_get_contents(GLPI_LOG_DIR . '/php-errors.log');
      $this->assertTrue(strpos($buf, 'From DBmysqlIterator') > 0, 'From in php_errors.log');
      $this->assertTrue(strpos($buf, $it->getSql()) > 0, 'Query in php_errors.log');
   }


   public function testFields() {

      $it = new DBmysqlIterator(NULL, 'foo', ['DISTINCT FIELDS' => 'bar']);
      $this->assertEquals('SELECT DISTINCT `bar` FROM `foo`', $it->getSql(), 'Single field');

      $it = new DBmysqlIterator(NULL, 'foo', ['FIELDS' => 'bar']);
      $this->assertEquals('SELECT `bar` FROM `foo`', $it->getSql(), 'Single field');

      $it = new DBmysqlIterator(NULL, 'foo', ['FIELDS' => ['bar', '`baz`']]);
      $this->assertEquals('SELECT `bar`, `baz` FROM `foo`', $it->getSql(), 'Multiple fields');

      $it = new DBmysqlIterator(NULL, 'foo', ['FIELDS' => ['b' => 'bar']]);
      $this->assertEquals('SELECT `b`.`bar` FROM `foo`', $it->getSql(), 'Single field from single table');

      $it = new DBmysqlIterator(NULL, 'foo', ['FIELDS' => ['b' => 'bar', '`c`' => '`baz`']]);
      $this->assertEquals('SELECT `b`.`bar`, `c`.`baz` FROM `foo`', $it->getSql(), 'Fields from multiple tables');

      $it = new DBmysqlIterator(NULL, 'foo', ['FIELDS' => ['a' => ['`bar`', 'baz']]]);
      $this->assertEquals('SELECT `a`.`bar`, `a`.`baz` FROM `foo`', $it->getSql(), 'Multiple fields from single table');
   }


   public function testOrder() {

      $it = new DBmysqlIterator(NULL, 'foo', ['ORDER' => 'bar']);
      $this->assertEquals('SELECT * FROM `foo` ORDER BY `bar`', $it->getSql(), 'Single field without quote');

      $it = new DBmysqlIterator(NULL, 'foo', ['ORDER' => '`baz`']);
      $this->assertEquals('SELECT * FROM `foo` ORDER BY `baz`', $it->getSql(), "Single quoted field");

      $it = new DBmysqlIterator(NULL, 'foo', ['ORDER' => 'bar ASC']);
      $this->assertEquals('SELECT * FROM `foo` ORDER BY `bar` ASC', $it->getSql(), 'Ascending');

      $it = new DBmysqlIterator(NULL, 'foo', ['ORDER' => 'bar DESC']);
      $this->assertEquals('SELECT * FROM `foo` ORDER BY `bar` DESC', $it->getSql(), "Descending");

      $it = new DBmysqlIterator(NULL, 'foo', ['ORDER' => ['`a`', 'b ASC', 'c DESC']]);
      $this->assertEquals('SELECT * FROM `foo` ORDER BY `a`, `b` ASC, `c` DESC', $it->getSql(), "Multiple fields");
   }


   public function testWhere() {

      $it = new DBmysqlIterator(NULL, 'foo', 'id=1');
      $this->assertEquals('SELECT * FROM `foo` WHERE id=1', $it->getSql(), 'Explicit WHBERE');

      $it = new DBmysqlIterator(NULL, 'foo', ['WHERE' => ['bar' => NULL]]);
      $this->assertEquals('SELECT * FROM `foo` WHERE `bar` IS NULL', $it->getSql(), 'NULL value (WHERE)');

      $it = new DBmysqlIterator(NULL, 'foo', ['bar' => NULL]);
      $this->assertEquals('SELECT * FROM `foo` WHERE `bar` IS NULL', $it->getSql(), 'NULL value');

      $it = new DBmysqlIterator(NULL, 'foo', ['`bar`' => NULL]);
      $this->assertEquals('SELECT * FROM `foo` WHERE `bar` IS NULL', $it->getSql(), 'NULL value');

      $it = new DBmysqlIterator(NULL, 'foo', ['bar' => 1]);
      $this->assertEquals('SELECT * FROM `foo` WHERE `bar` = 1', $it->getSql(), 'Integer value');

      $it = new DBmysqlIterator(NULL, 'foo', ['bar' => [1, 2, 4]]);
      $this->assertEquals("SELECT * FROM `foo` WHERE `bar` IN ('1','2','4')", $it->getSql(), 'Multiple values');

      $it = new DBmysqlIterator(NULL, 'foo', ['bar' => 'val']);
      $this->assertEquals("SELECT * FROM `foo` WHERE `bar` = 'val'", $it->getSql(), 'String value');

      $it = new DBmysqlIterator(NULL, 'foo', ['bar' => '`field`']);
      $this->assertEquals('SELECT * FROM `foo` WHERE `bar` = `field`', $it->getSql(), 'Field value');
   }


   public function testFkey() {

      $it = new DBmysqlIterator(NULL, ['foo', 'bar'], ['FKEY' => ['id', 'fk']]);
      $this->assertEquals('SELECT * FROM `foo`, `bar` WHERE `id` = `fk`', $it->getSql(), 'FKEY fields only');

      $it = new DBmysqlIterator(NULL, ['foo', 'bar'], ['FKEY' => ['foo' => 'id', 'bar' => 'fk']]);
      $this->assertEquals('SELECT * FROM `foo`, `bar` WHERE `foo`.`id` = `bar`.`fk`', $it->getSql(), 'FKEY tables and fields unquoted');

      $it = new DBmysqlIterator(NULL, ['foo', 'bar'], ['FKEY' => ['`foo`' => 'id', 'bar' => '`fk`']]);
      $this->assertEquals('SELECT * FROM `foo`, `bar` WHERE `foo`.`id` = `bar`.`fk`', $it->getSql(), 'FKEY tables and fields, some quoted');
   }


   public function testRange() {

      $it = new DBmysqlIterator(NULL, 'foo', ['START' => 5, 'LIMIT' => 10]);
      $this->assertEquals('SELECT * FROM `foo` LIMIT 10 OFFSET 5', $it->getSql(), 'Single table, without quotes');
   }


   public function testLogical() {

      $it = new DBmysqlIterator(NULL, ['foo'], [['a' => 1, 'b' => 2]]);
      $this->assertEquals('SELECT * FROM `foo` WHERE (`a` = 1 AND `b` = 2)', $it->getSql(), 'Logical implicit AND');

      $it = new DBmysqlIterator(NULL, ['foo'], ['AND' => ['a' => 1, 'b' => 2]]);
      $this->assertEquals('SELECT * FROM `foo` WHERE (`a` = 1 AND `b` = 2)', $it->getSql(), 'Logical AND');

      $it = new DBmysqlIterator(NULL, ['foo'], ['OR' => ['a' => 1, 'b' => 2]]);
      $this->assertEquals('SELECT * FROM `foo` WHERE (`a` = 1 OR `b` = 2)', $it->getSql(), 'Logical OR');

      $it = new DBmysqlIterator(NULL, ['foo'], ['NOT' => ['a' => 1, 'b' => 2]]);
      $this->assertEquals('SELECT * FROM `foo` WHERE NOT (`a` = 1 AND `b` = 2)', $it->getSql(), 'Logical NOT');

      $crit = ['WHERE' => ['a'  => 1,
                           'OR' => ['b'   => 2,
                                    'NOT' => ['c'   => [2, 3],
                                              'AND' => ['d' => 4,
                                                        'e' => 5,
                                                       ],
                                             ],
                                   ],
                          ],
              ];
      $it = new DBmysqlIterator(NULL, ['foo'], $crit);
      $this->assertEquals("SELECT * FROM `foo` WHERE `a` = 1 AND (`b` = 2 OR NOT (`c` IN ('2','3') AND (`d` = 4 AND `e` = 5)))", $it->getSql(), 'Complex case');
   }


   public function testRows() {
      global $DB;

      $it = new DBmysqlIterator(NULL, 'foo');
      $this->assertEquals(0, $it->numrows());
      $this->assertFalse($it->next());

      $it = $DB->request('glpi_configs', ['context' => 'core', 'name' => 'version']);
      $this->assertEquals(1, $it->numrows());
      $row = $it->next();
      $key = $it->key();
      $this->assertEquals($key, $row['id']);
   }
}
