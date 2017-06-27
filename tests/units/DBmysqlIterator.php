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

// Generic test classe, to be extended for CommonDBTM Object

class DBmysqlIterator extends DbTestCase {


   public function testQuery() {
      $req = 'SELECT Something FROM Somewhere';
      $it = new \DBmysqlIterator(NULL, $req);
      $this->string($it->getSql())->isIdenticalTo($req);
   }


   public function testSqlError() {
      global $DB;

      $this->exception(
         function () use ($DB) {
            $DB->request('fakeTable');
         }
      )
         ->isInstanceOf('GlpitestSQLerror')
         ->message
            ->contains("fakeTable' doesn't exist");
   }


   public function testOnlyTable() {

      $it = new \DBmysqlIterator(NULL, 'foo');
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo`');

      $it = new \DBmysqlIterator(NULL, '`foo`');
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo`');

      $it = new \DBmysqlIterator(NULL, ['foo', '`bar`']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo`, `bar`');
   }


   /**
    * This is really an error, no table but a WHERE clase
    */
   public function testNoTableWithWhere() {
      $this->when(
         function () {
            $it = new \DBmysqlIterator(NULL, '', ['foo' => 1]);
            $this->string($it->getSql())->isIdenticalTo('SELECT * WHERE `foo` = 1');
         }
      )->error()
         ->withType(E_USER_ERROR)
         ->withMessage('Missing table name')
         ->exists();
   }


   /**
    * Temporarily, this is an error, will be allowed later
    */
   public function testNoTableWithoutWhere() {
      $this->when(
         function () {
            $it = new \DBmysqlIterator(NULL, '');
            $this->string($it->getSql())->isIdenticalTo('SELECT *');
         }
      )->error()
         ->withType(E_USER_ERROR)
         ->withMessage('Missing table name')
         ->exists();
   }


   /**
    * Temporarily, this is an error, will be allowed later
    */
   public function testNoTableWithoutWhereBis() {
      $this->when(
         function () {
            $it = new \DBmysqlIterator(NULL, ['FROM' => []]);
            $this->string('SELECT *', $it->getSql(), 'No table');
         }
      )->error()
         ->withType(E_USER_ERROR)
         ->withMessage('Missing table name')
         ->exists();

   }


   public function testDebug() {
      file_put_contents(GLPI_LOG_DIR . '/php-errors.log', '');
      $it = new \DBmysqlIterator(NULL, 'foo', ['FIELDS' => 'name', 'id = ' . mt_rand()], true);
      $buf = file_get_contents(GLPI_LOG_DIR . '/php-errors.log');
      $this->boolean(strpos($buf, 'From DBmysqlIterator') > 0)->isTrue();
      $this->boolean(strpos($buf, $it->getSql()) > 0)->isTrue;
   }


   public function testFields() {
      $it = new \DBmysqlIterator(NULL, 'foo', ['DISTINCT FIELDS' => 'bar']);
      $this->string($it->getSql())->isIdenticalTo('SELECT DISTINCT `bar` FROM `foo`');

      $it = new \DBmysqlIterator(NULL, 'foo', ['FIELDS' => 'bar']);
      $this->string($it->getSql())->isIdenticalTo('SELECT `bar` FROM `foo`');

      $it = new \DBmysqlIterator(NULL, 'foo', ['FIELDS' => ['bar', '`baz`']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT `bar`, `baz` FROM `foo`');

      $it = new \DBmysqlIterator(NULL, 'foo', ['FIELDS' => ['b' => 'bar']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT `b`.`bar` FROM `foo`');

      $it = new \DBmysqlIterator(NULL, 'foo', ['FIELDS' => ['b' => 'bar', '`c`' => '`baz`']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT `b`.`bar`, `c`.`baz` FROM `foo`');

      $it = new \DBmysqlIterator(NULL, 'foo', ['FIELDS' => ['a' => ['`bar`', 'baz']]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT `a`.`bar`, `a`.`baz` FROM `foo`');

      $it = new \DBmysqlIterator(NULL, ['foo', 'bar'], ['FIELDS' => ['foo' => ['*']]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT `foo`.`*` FROM `foo`, `bar`');
   }


   public function testOrder() {
      $it = new \DBmysqlIterator(NULL, 'foo', ['ORDER' => 'bar']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `bar`');

      $it = new \DBmysqlIterator(NULL, 'foo', ['ORDER' => '`baz`']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `baz`');

      $it = new \DBmysqlIterator(NULL, 'foo', ['ORDER' => 'bar ASC']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `bar` ASC');

      $it = new \DBmysqlIterator(NULL, 'foo', ['ORDER' => 'bar DESC']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `bar` DESC');

      $it = new \DBmysqlIterator(NULL, 'foo', ['ORDER' => ['`a`', 'b ASC', 'c DESC']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `a`, `b` ASC, `c` DESC');
   }


   public function testCount() {
      $it = new \DBmysqlIterator(NULL, 'foo', ['COUNT' => 'cpt']);
      $this->string($it->getSql())->isIdenticalTo('SELECT COUNT(*) AS cpt FROM `foo`');
   }


   public function testJoins() {
      $it = new \DBmysqlIterator(NULL, 'foo', ['JOIN' => ['bar' => ['FKEY' => ['bar' => 'id', 'foo' => 'fk']]]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)');

      $it = new \DBmysqlIterator(NULL, 'foo', ['JOIN' => ['bar' => ['FKEY' => ['bar.id', 'foo.fk']]]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)');

      $it = new \DBmysqlIterator(NULL, 'foo', ['JOIN' => ['bar' => ['FKEY' => ['id', 'fk'], 'val' => 1]]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` LEFT JOIN `bar` ON (`id` = `fk` AND `val` = 1)');

      $it = new \DBmysqlIterator(NULL, 'foo', ['LEFT JOIN' => ['bar' => ['FKEY' => ['bar' => 'id', 'foo' => 'fk']]]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)');

      $it = new \DBmysqlIterator(NULL, 'foo', ['INNER JOIN' => ['bar' => ['FKEY' => ['bar' => 'id', 'foo' => 'fk']]]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` INNER JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)');

      $this->when(
         function () {
            $it = new \DBmysqlIterator(null, 'foo', ['LEFT JOIN' => 'bar']);
         }
      )->error()
         ->withType(E_USER_ERROR)
         ->withMessage('BAD JOIN, value must be [ table => criteria ]')
         ->exists();

      $this->when(
         function () {
            $it = new \DBmysqlIterator(NULL, 'foo', ['INNER JOIN' => ['bar' => ['FKEY' => 'akey']]]);
         }
      )->error()
         ->withType(E_USER_ERROR)
         ->withMessage('BAD FOREIGN KEY, should be [ key1, key2 ]')
         ->exists();
   }


   public function testOperators() {
      $it = new \DBmysqlIterator(NULL, 'foo', ['a' => 1]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` = 1');

      $it = new \DBmysqlIterator(NULL, 'foo', ['a' => ['=', 1]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` = 1');

      $it = new \DBmysqlIterator(NULL, 'foo', ['a' => ['>', 1]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` > 1');

      $it = new \DBmysqlIterator(NULL, 'foo', ['a' => ['LIKE', '%bar%']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` LIKE \'%bar%\'');

      $it = new \DBmysqlIterator(NULL, 'foo', ['NOT' => ['a' => ['LIKE', '%bar%']]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE NOT (`a` LIKE \'%bar%\')');

      $it = new \DBmysqlIterator(NULL, 'foo', ['a' => ['NOT LIKE', '%bar%']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` NOT LIKE \'%bar%\'');
   }


   public function testWhere() {
      $it = new \DBmysqlIterator(NULL, 'foo', 'id=1');
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE id=1');

      $it = new \DBmysqlIterator(NULL, 'foo', ['WHERE' => ['bar' => NULL]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `bar` IS NULL');

      $it = new \DBmysqlIterator(NULL, 'foo', ['bar' => NULL]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `bar` IS NULL');

      $it = new \DBmysqlIterator(NULL, 'foo', ['`bar`' => NULL]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `bar` IS NULL');

      $it = new \DBmysqlIterator(NULL, 'foo', ['bar' => 1]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `bar` = 1');

      $it = new \DBmysqlIterator(NULL, 'foo', ['bar' => [1, 2, 4]]);
      $this->string($it->getSql())->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` IN (1, 2, 4)");

      $it = new \DBmysqlIterator(NULL, 'foo', ['bar' => ['a', 'b', 'c']]);
      $this->string($it->getSql())->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` IN ('a', 'b', 'c')");

      $it = new \DBmysqlIterator(NULL, 'foo', ['bar' => 'val']);
      $this->string($it->getSql())->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` = 'val'");

      $it = new \DBmysqlIterator(NULL, 'foo', ['bar' => '`field`']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `bar` = `field`');
   }


   public function testFkey() {

      $it = new \DBmysqlIterator(NULL, ['foo', 'bar'], ['FKEY' => ['id', 'fk']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo`, `bar` WHERE `id` = `fk`');

      $it = new \DBmysqlIterator(NULL, ['foo', 'bar'], ['FKEY' => ['foo' => 'id', 'bar' => 'fk']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo`, `bar` WHERE `foo`.`id` = `bar`.`fk`');

      $it = new \DBmysqlIterator(NULL, ['foo', 'bar'], ['FKEY' => ['`foo`' => 'id', 'bar' => '`fk`']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo`, `bar` WHERE `foo`.`id` = `bar`.`fk`');
   }


   public function testRange() {

      $it = new \DBmysqlIterator(NULL, 'foo', ['START' => 5, 'LIMIT' => 10]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` LIMIT 10 OFFSET 5');
   }


   public function testLogical() {
      $it = new \DBmysqlIterator(NULL, ['foo'], [['a' => 1, 'b' => 2]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE (`a` = 1 AND `b` = 2)');

      $it = new \DBmysqlIterator(NULL, ['foo'], ['AND' => ['a' => 1, 'b' => 2]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE (`a` = 1 AND `b` = 2)');

      $it = new \DBmysqlIterator(NULL, ['foo'], ['OR' => ['a' => 1, 'b' => 2]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE (`a` = 1 OR `b` = 2)');

      $it = new \DBmysqlIterator(NULL, ['foo'], ['NOT' => ['a' => 1, 'b' => 2]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE NOT (`a` = 1 AND `b` = 2)');

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
      $sql = "SELECT * FROM `foo` WHERE `a` = 1 AND (`b` = 2 OR NOT (`c` IN (2, 3) AND (`d` = 4 AND `e` = 5)))";
      $it = new \DBmysqlIterator(NULL, ['foo'], $crit);
      $this->string($it->getSql())->isIdenticalTo($sql);

      $crit['FROM'] = 'foo';
      $it = new \DBmysqlIterator(NULL, $crit);
      $this->string($it->getSql())->isIdenticalTo($sql);
   }


   public function testModern() {
      $req = [
         'SELECT' => ['a', 'b'],
         'FROM'   => 'foo',
         'WHERE'  => ['c' => 1],
      ];
      $sql = "SELECT `a`, `b` FROM `foo` WHERE `c` = 1";
      $it = new \DBmysqlIterator(NULL, $req);
      $this->string($it->getSql())->isIdenticalTo($sql);
   }


   public function testRows() {
      global $DB;

      $it = new \DBmysqlIterator(NULL, 'foo');
      $this->integer($it->numrows())->isIdenticalTo(0);
      $this->boolean($it->next())->isFalse();

      $it = $DB->request('glpi_configs', ['context' => 'core', 'name' => 'version']);
      $this->integer($it->numrows())->isIdenticalTo(1);
      $row = $it->next();
      $key = $it->key();
      $this->string($row['id'])->isIdenticalTo($key);
   }
}
