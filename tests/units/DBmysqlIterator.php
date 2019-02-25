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
use Monolog\Logger;
use Monolog\Handler\TestHandler;

// Generic test classe, to be extended for CommonDBTM Object

class DBmysqlIterator extends DbTestCase {

   private $it;

   public function beforeTestMethod($method) {
      parent::beforeTestMethod($method);
      $this->it = new \DBmysqlIterator(null);
   }

   public function testQuery() {
      $req = 'SELECT Something FROM Somewhere';
      $it = $this->it->execute($req);
      $this->string($it->getSql())->isIdenticalTo($req);

      $req = 'SELECT @@sql_mode as mode';
      $it = $this->it->execute($req);
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

      $it = $this->it->execute('foo');
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo`');

      $it = $this->it->execute('`foo`');
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo`');

      $it = $this->it->execute(['foo', '`bar`']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo`, `bar`');
   }


   /**
    * This is really an error, no table but a WHERE clase
    */
   public function testNoTableWithWhere() {
      $this->when(
         function () {
            $it = $this->it->execute('', ['foo' => 1]);
            $this->string($it->getSql())->isIdenticalTo('SELECT * WHERE `foo` = \'1\'');
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
            $it = $this->it->execute('');
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
            $it = $this->it->execute(['FROM' => []]);
            $this->string('SELECT *', $it->getSql(), 'No table');
         }
      )->error()
         ->withType(E_USER_ERROR)
         ->withMessage('Missing table name')
         ->exists();

   }


   public function testDebug() {
      global $SQLLOGGER;

      foreach ($SQLLOGGER->getHandlers() as $handler) {
         if ($handler instanceof TestHandler) {
            break;
         }
      }

      //clean from previous queries
      $handler->clear();
      define('GLPI_SQL_DEBUG', true);

      $id = mt_rand();
      $this->it->execute('foo', ['FIELDS' => 'name', 'id = ' . $id]);

      $this->array($handler->getRecords())->hasSize(1);
      $this->boolean(
         $handler->hasRecordThatContains(
            'Generated query: SELECT `name` FROM `foo` WHERE (id = ' . $id . ')',
            Logger::DEBUG
         )
      )->isTrue();
   }


   public function testFields() {
      $it = $this->it->execute('foo', ['DISTINCT FIELDS' => 'bar']);
      $this->string($it->getSql())->isIdenticalTo('SELECT DISTINCT `bar` FROM `foo`');

      $it = $this->it->execute('foo', ['FIELDS' => 'bar']);
      $this->string($it->getSql())->isIdenticalTo('SELECT `bar` FROM `foo`');

      $it = $this->it->execute('foo', ['FIELDS' => ['bar', '`baz`']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT `bar`, `baz` FROM `foo`');

      $it = $this->it->execute('foo', ['FIELDS' => ['b' => 'bar']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT `b`.`bar` FROM `foo`');

      $it = $this->it->execute('foo', ['FIELDS' => ['b' => 'bar', '`c`' => '`baz`']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT `b`.`bar`, `c`.`baz` FROM `foo`');

      $it = $this->it->execute('foo', ['FIELDS' => ['a' => ['`bar`', 'baz']]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT `a`.`bar`, `a`.`baz` FROM `foo`');

      $it = $this->it->execute(['foo', 'bar'], ['FIELDS' => ['foo' => ['*']]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT `foo`.* FROM `foo`, `bar`');

      $it = $this->it->execute(['foo', 'bar'], ['FIELDS' => ['foo.*']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT `foo`.* FROM `foo`, `bar`');

      $it = $this->it->execute('foo', ['FIELDS' => ['SUM' => 'bar AS cpt']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT SUM(`bar`) AS cpt FROM `foo`');

      $it = $this->it->execute('foo', ['FIELDS' => ['AVG' => 'bar AS cpt']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT AVG(`bar`) AS cpt FROM `foo`');

      $it = $this->it->execute('foo', ['FIELDS' => ['MIN' => 'bar AS cpt']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT MIN(`bar`) AS cpt FROM `foo`');

      $it = $this->it->execute('foo', ['FIELDS' => ['MAX' => 'bar AS cpt']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT MAX(`bar`) AS cpt FROM `foo`');
   }


   public function testOrder() {
      $it = $this->it->execute('foo', ['ORDERBY' => 'bar']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `bar`');

      $it = $this->it->execute('foo', ['ORDER' => 'bar']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `bar`');

      $it = $this->it->execute('foo', ['ORDERBY' => '`baz`']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `baz`');

      $it = $this->it->execute('foo', ['ORDER' => '`baz`']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `baz`');

      $it = $this->it->execute('foo', ['ORDERBY' => 'bar ASC']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `bar` ASC');

      $it = $this->it->execute('foo', ['ORDER' => 'bar ASC']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `bar` ASC');

      $it = $this->it->execute('foo', ['ORDERBY' => 'bar DESC']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `bar` DESC');

      $it = $this->it->execute('foo', ['ORDER' => 'bar DESC']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `bar` DESC');

      $it = $this->it->execute('foo', ['ORDERBY' => ['`a`', 'b ASC', 'c DESC']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `a`, `b` ASC, `c` DESC');

      $it = $this->it->execute('foo', ['ORDER' => ['`a`', 'b ASC', 'c DESC']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `a`, `b` ASC, `c` DESC');

      $it = $this->it->execute('foo', ['ORDERBY' => 'bar, baz ASC']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `bar`, `baz` ASC');

      $it = $this->it->execute('foo', ['ORDER' => 'bar, baz ASC']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `bar`, `baz` ASC');

      $it = $this->it->execute('foo', ['ORDERBY' => 'bar DESC, baz ASC']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `bar` DESC, `baz` ASC');

      $it = $this->it->execute('foo', ['ORDER' => 'bar DESC, baz ASC']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `bar` DESC, `baz` ASC');
   }


   public function testCount() {
      $it = $this->it->execute('foo', ['COUNT' => 'cpt']);
      $this->string($it->getSql())->isIdenticalTo('SELECT COUNT(*) AS cpt FROM `foo`');

      $it = $this->it->execute('foo', ['COUNT' => 'cpt', 'SELECT DISTINCT' => 'bar']);
      $this->string($it->getSql())->isIdenticalTo('SELECT COUNT(DISTINCT `bar`) AS cpt FROM `foo`');

      $it = $this->it->execute('foo', ['COUNT' => 'cpt', 'FIELDS' => ['name', 'version']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT COUNT(*) AS cpt, `name`, `version` FROM `foo`');

      $it = $this->it->execute('foo', ['FIELDS' => ['COUNT' => 'bar']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT COUNT(`bar`) FROM `foo`');

      $it = $this->it->execute('foo', ['FIELDS' => ['COUNT' => 'bar AS cpt']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT COUNT(`bar`) AS cpt FROM `foo`');

      $it = $this->it->execute('foo', ['FIELDS' => ['foo.bar', 'COUNT' => 'foo.baz']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT `foo`.`bar`, COUNT(`foo`.`baz`) FROM `foo`');

      $it = $this->it->execute('foo', ['FIELDS' => ['COUNT' => ['bar', 'baz']]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT COUNT(`bar`), COUNT(`baz`) FROM `foo`');

      $it = $this->it->execute('foo', ['FIELDS' => ['COUNT' => ['bar AS cpt', 'baz AS cpt2']]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT COUNT(`bar`) AS cpt, COUNT(`baz`) AS cpt2 FROM `foo`');

      $it = $this->it->execute('foo', ['FIELDS' => ['foo.bar', 'COUNT' => ['foo.baz', 'foo.qux']]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT `foo`.`bar`, COUNT(`foo`.`baz`), COUNT(`foo`.`qux`) FROM `foo`');
   }

   public function testCountDistinct() {
      $it = $this->it->execute('foo', ['FIELDS' => ['COUNT DISTINCT' => 'bar']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT COUNT(DISTINCT(`bar`)) FROM `foo`');

      $it = $this->it->execute('foo', ['FIELDS' => ['COUNT DISTINCT' => ['bar', 'baz']]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT COUNT(DISTINCT(`bar`)), COUNT(DISTINCT(`baz`)) FROM `foo`');

      $it = $this->it->execute('foo', ['FIELDS' => ['COUNT DISTINCT' => ['bar AS cpt', 'baz AS cpt2']]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT COUNT(DISTINCT(`bar`)) AS cpt, COUNT(DISTINCT(`baz`)) AS cpt2 FROM `foo`');

      $it = $this->it->execute('foo', ['FIELDS' => ['foo.bar', 'COUNT DISTINCT' => ['foo.baz', 'foo.qux']]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT `foo`.`bar`, COUNT(DISTINCT(`foo`.`baz`)), COUNT(DISTINCT(`foo`.`qux`)) FROM `foo`');
   }


   public function testJoins() {
      $this->exception(
         function () {
            $it = $this->it->execute('foo', ['JOIN' => ['bar' => ['FKEY' => ['bar' => 'id', 'foo' => 'fk']]]]);
            $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)');
         }
      )->message->contains('"JOIN" is deprecated, please use "LEFT JOIN" instead');

      $this->exception(
         function () {
            $it = $this->it->execute('foo', ['JOIN' => ['bar' => ['FKEY' => ['bar.id', 'foo.fk']]]]);
            $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)');
         }
      )->message->contains('"JOIN" is deprecated, please use "LEFT JOIN" instead');

      $this->exception(
         function () {
            $it = $this->it->execute('foo', ['JOIN' => ['bar' => ['FKEY' => ['id', 'fk'], 'val' => 1]]]);
            $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` LEFT JOIN `bar` ON (`id` = `fk` AND `val` = \'1\')');
         }
      )->message->contains('"JOIN" is deprecated, please use "LEFT JOIN" instead');

      $it = $this->it->execute('foo', ['LEFT JOIN' => ['bar' => ['FKEY' => ['bar' => 'id', 'foo' => 'fk']]]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)');

      $it = $this->it->execute('foo', ['LEFT JOIN' => ['bar' => ['ON' => ['bar' => 'id', 'foo' => 'fk']]]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)');

      $it = $this->it->execute(
         'foo', [
            'LEFT JOIN' => [
               'bar' => [
                  'FKEY' => [
                     'bar' => 'id',
                     'foo' => 'fk'
                  ]
               ],
               'baz' => [
                  'FKEY' => [
                     'baz' => 'id',
                     'foo' => 'baz_id'
                  ]
               ]
            ]
         ]
      );
      $this->string($it->getSql())->isIdenticalTo(
         'SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk`) '.
         'LEFT JOIN `baz` ON (`baz`.`id` = `foo`.`baz_id`)'
      );

      $it = $this->it->execute('foo', ['INNER JOIN' => ['bar' => ['FKEY' => ['bar' => 'id', 'foo' => 'fk']]]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` INNER JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)');

      $it = $this->it->execute('foo', ['RIGHT JOIN' => ['bar' => ['FKEY' => ['bar' => 'id', 'foo' => 'fk']]]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` RIGHT JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)');

      $this->when(
         function () {
            $it = $this->it->execute('foo', ['LEFT JOIN' => 'bar']);
         }
      )->error()
         ->withType(E_USER_ERROR)
         ->withMessage('BAD JOIN, value must be [ table => criteria ]')
         ->exists();

      $this->when(
         function () {
            $it = $this->it->execute('foo', ['INNER JOIN' => ['bar' => ['FKEY' => 'akey']]]);
         }
      )->error()
         ->withType(E_USER_ERROR)
         ->withMessage('BAD FOREIGN KEY, should be [ table1 => key1, table2 => key2 ] or [ table1 => key1, table2 => key2, [criteria]]')
         ->exists();

      //test conditions
      $it = $this->it->execute(
         'foo', [
            'LEFT JOIN' => [
               'bar' => [
                  'FKEY' => [
                     'bar' => 'id',
                     'foo' => 'fk', [
                        'OR'  => ['field' => ['>', 20]]
                     ]
                  ]
               ]
            ]
         ]
      );
      $this->string($it->getSql())->isIdenticalTo(
         'SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk` OR `field` > \'20\')'
      );
   }


   public function testOperators() {
      $it = $this->it->execute('foo', ['a' => 1]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` = \'1\'');

      $it = $this->it->execute('foo', ['a' => ['=', 1]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` = \'1\'');

      $it = $this->it->execute('foo', ['a' => ['>', 1]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` > \'1\'');

      $it = $this->it->execute('foo', ['a' => ['LIKE', '%bar%']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` LIKE \'%bar%\'');

      $it = $this->it->execute('foo', ['NOT' => ['a' => ['LIKE', '%bar%']]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE NOT (`a` LIKE \'%bar%\')');

      $it = $this->it->execute('foo', ['a' => ['NOT LIKE', '%bar%']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` NOT LIKE \'%bar%\'');

      $it = $this->it->execute('foo', ['a' => ['<>', 1]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` <> \'1\'');

      $it = $this->it->execute('foo', ['a' => ['&', 1]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` & \'1\'');

      $it = $this->it->execute('foo', ['a' => ['|', 1]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` | \'1\'');
   }


   public function testWhere() {
      $it = $this->it->execute('foo', 'id=1');
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE id=1');

      $it = $this->it->execute('foo', ['WHERE' => ['bar' => null]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `bar` IS NULL');

      $it = $this->it->execute('foo', ['bar' => null]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `bar` IS NULL');

      $it = $this->it->execute('foo', ['`bar`' => null]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `bar` IS NULL');

      $it = $this->it->execute('foo', ['bar' => 1]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `bar` = \'1\'');

      $it = $this->it->execute('foo', ['bar' => [1, 2, 4]]);
      $this->string($it->getSql())->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` IN ('1', '2', '4')");

      $it = $this->it->execute('foo', ['bar' => ['a', 'b', 'c']]);
      $this->string($it->getSql())->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` IN ('a', 'b', 'c')");

      $it = $this->it->execute('foo', ['bar' => 'val']);
      $this->string($it->getSql())->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` = 'val'");

      $it = $this->it->execute('foo', ['bar' => '`field`']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `bar` = `field`');

      $it = $this->it->execute('foo', ['bar' => '?']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `bar` = \'?\'');

      $it = $this->it->execute('foo', ['bar' => new \QueryParam()]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `bar` = ?');

      $it = $this->it->execute('foo', ['bar' => new \QueryParam('myparam')]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `bar` = :myparam');
   }


   public function testFkey() {

      $it = $this->it->execute(['foo', 'bar'], ['FKEY' => ['id', 'fk']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo`, `bar` WHERE `id` = `fk`');

      $it = $this->it->execute(['foo', 'bar'], ['FKEY' => ['foo' => 'id', 'bar' => 'fk']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo`, `bar` WHERE `foo`.`id` = `bar`.`fk`');

      $it = $this->it->execute(['foo', 'bar'], ['FKEY' => ['`foo`' => 'id', 'bar' => '`fk`']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo`, `bar` WHERE `foo`.`id` = `bar`.`fk`');
   }

   public function testGroupBy() {

      $it = $this->it->execute(['foo'], ['GROUPBY' => ['id']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` GROUP BY `id`');

      $it = $this->it->execute(['foo'], ['GROUP' => ['id']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` GROUP BY `id`');

      $it = $this->it->execute(['foo'], ['GROUPBY' => 'id']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` GROUP BY `id`');

      $it = $this->it->execute(['foo'], ['GROUP' => 'id']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` GROUP BY `id`');

      $it = $this->it->execute(['foo'], ['GROUPBY' => ['id', 'name']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` GROUP BY `id`, `name`');

      $it = $this->it->execute(['foo'], ['GROUP' => ['id', 'name']]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` GROUP BY `id`, `name`');
   }

   public function testNoFieldGroupBy() {
      $this->when(
         function () {
            $it = $this->it->execute(['foo'], ['GROUPBY' => []]);
            $this->string('SELECT * FROM `foo`', $it->getSql(), 'No group by field');
         }
      )->error()
         ->withType(E_USER_ERROR)
         ->withMessage('Missing group by field')
         ->exists();

      $this->when(
         function () {
            $it = $this->it->execute(['foo'], ['GROUP' => []]);
            $this->string('SELECT * FROM `foo`', $it->getSql(), 'No group by field');
         }
      )->error()
         ->withType(E_USER_ERROR)
         ->withMessage('Missing group by field')
         ->exists();

   }

   public function testRange() {

      $it = $this->it->execute('foo', ['START' => 5, 'LIMIT' => 10]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` LIMIT 10 OFFSET 5');
   }


   public function testLogical() {
      $it = $this->it->execute(['foo'], [['a' => 1, 'b' => 2]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE (`a` = \'1\' AND `b` = \'2\')');

      $it = $this->it->execute(['foo'], ['AND' => ['a' => 1, 'b' => 2]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE (`a` = \'1\' AND `b` = \'2\')');

      $it = $this->it->execute(['foo'], ['OR' => ['a' => 1, 'b' => 2]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE (`a` = \'1\' OR `b` = \'2\')');

      $it = $this->it->execute(['foo'], ['NOT' => ['a' => 1, 'b' => 2]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE NOT (`a` = \'1\' AND `b` = \'2\')');

      $crit = [
         'WHERE' => [
            'a'  => 1,
            'OR' => [
               'b'   => 2,
               'NOT' => [
                  'c'   => [2, 3],
                  'AND' => [
                     'd' => 4,
                     'e' => 5,
                  ],
               ],
            ],
         ],
      ];
      $sql = "SELECT * FROM `foo` WHERE `a` = '1' AND (`b` = '2' OR NOT (`c` IN ('2', '3') AND (`d` = '4' AND `e` = '5')))";
      $it = $this->it->execute(['foo'], $crit);
      $this->string($it->getSql())->isIdenticalTo($sql);

      $crit['FROM'] = 'foo';
      $it = $this->it->execute($crit);
      $this->string($it->getSql())->isIdenticalTo($sql);

      $crit = [
         'FROM'   => 'foo',
         'WHERE'  => [
            'bar' => 'baz',
            'RAW' => ['SELECT COUNT(*) FROM xyz' => 5]
         ]
      ];
      $it = $this->it->execute($crit);
      $this->string($it->getSql())->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` = 'baz' AND ((SELECT COUNT(*) FROM xyz) = '5')");

      $crit = [
         'FROM'   => 'foo',
         'WHERE'  => [
            'bar' => 'baz',
            'RAW' => ['SELECT COUNT(*) FROM xyz' => ['>', 2]]
         ]
      ];
      $it = $this->it->execute($crit);
      $this->string($it->getSql())->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` = 'baz' AND ((SELECT COUNT(*) FROM xyz) > '2')");

      $crit = [
         'FROM'   => 'foo',
         'WHERE'  => [
            'bar' => 'baz',
            'RAW' => ['SELECT COUNT(*) FROM xyz' => [3, 4]]
         ]
      ];
      $it = $this->it->execute($crit);
      $this->string($it->getSql())->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` = 'baz' AND ((SELECT COUNT(*) FROM xyz) IN ('3', '4'))");
   }


   public function testModern() {
      $req = [
         'SELECT' => ['a', 'b'],
         'FROM'   => 'foo',
         'WHERE'  => ['c' => 1],
      ];
      $sql = "SELECT `a`, `b` FROM `foo` WHERE `c` = '1'";
      $it = $this->it->execute($req);
      $this->string($it->getSql())->isIdenticalTo($sql);
   }


   public function testRows() {
      global $DB;

      $it = $this->it->execute('foo');
      $this->integer($it->numrows())->isIdenticalTo(0);
      $this->integer(count($it))->isIdenticalTo(0);
      $this->boolean($it->next())->isFalse();

      $it = $DB->request('glpi_configs', ['context' => 'core', 'name' => 'version']);
      $this->integer($it->numrows())->isIdenticalTo(1);
      $this->integer(count($it))->isIdenticalTo(1);
      $row = $it->next();
      $key = $it->key();
      $this->string($row['id'])->isIdenticalTo($key);

      $it = $DB->request('glpi_configs', ['context' => 'core']);
      $this->integer($it->numrows())->isGreaterThan(100);
      $this->integer(count($it))->isGreaterThan(100);
      $this->boolean($it->numrows() == count($it))->isTrue();
   }

   public function testKey() {
      global $DB;

      // test keys with absence of 'id' in select
      // we should use a incremented position in the first case
      // see https://github.com/glpi-project/glpi/pull/3401
      // previously, the first query returned only one result
      $users_list = iterator_to_array($DB->request([
         'SELECT' => 'name',
         'FROM'   => 'glpi_users']));
      $users_list2 = iterator_to_array($DB->request([
         'SELECT' =>  ['id', 'name'],
         'FROM'   => 'glpi_users']));
      $nb  = count($users_list);
      $nb2 = count($users_list2);
      $this->integer($nb)->isEqualTo($nb2);
   }

   public function testAlias() {
      $it = $this->it->execute('foo AS f');
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` AS `f`');

      $it = $this->it->execute(['FROM' => 'foo AS f']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` AS `f`');

      $it = $this->it->execute(['SELECT' => ['field AS f'], 'FROM' => 'bar AS b']);
      $this->string($it->getSql())->isIdenticalTo('SELECT `field` AS `f` FROM `bar` AS `b`');

      $it = $this->it->execute(['SELECT' => ['b.field AS f'], 'FROM' => 'bar AS b']);
      $this->string($it->getSql())->isIdenticalTo('SELECT `b`.`field` AS `f` FROM `bar` AS `b`');

      $it = $this->it->execute(['SELECT' => ['id', 'field AS f', 'baz as Z'], 'FROM' => 'bar AS b']);
      $this->string($it->getSql())->isIdenticalTo('SELECT `id`, `field` AS `f`, `baz` AS `Z` FROM `bar` AS `b`');

      $it = $this->it->execute([
         'FROM' => 'bar AS b',
         'INNER JOIN'   => [
            'foo AS f' => [
               'FKEY' => [
                  'b'   => 'fid',
                  'f'   => 'id'
               ]
            ]
         ]
      ]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `bar` AS `b` INNER JOIN `foo` AS `f` ON (`b`.`fid` = `f`.`id`)');
   }

   public function testExpression() {
      $it = $this->it->execute('foo', [new \QueryExpression('a LIKE b')]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE a LIKE b');

      $it = $this->it->execute('foo', ['FIELDS' => ['b' => 'bar', '`c`' => '`baz`', new \QueryExpression('1 AS `myfield`')]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT `b`.`bar`, `c`.`baz`, 1 AS `myfield` FROM `foo`');
   }

   public function testSubQuery() {
      $crit = ['SELECT' => 'id', 'FROM' => 'baz', 'WHERE' => ['z' => 'f']];
      $raw_subq = "SELECT `id` FROM `baz` WHERE `z` = 'f'";
      $sub_query =new \QuerySubQuery($crit);
      $this->string($sub_query->getSubQuery())->isIdenticalTo($raw_subq);
      $this->string($sub_query->getOperator())->isIdenticalTo('IN');

      $it = $this->it->execute('foo', ['bar' => $sub_query]);
      $this->string($it->getSql())
           ->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` IN ($raw_subq)");

      $sub_query =new \QuerySubQuery($crit, '<>');
      $this->string($sub_query->getSubQuery())->isIdenticalTo($raw_subq);
      $this->string($sub_query->getOperator())->isIdenticalTo('<>');

      $it = $this->it->execute('foo', ['bar' => $sub_query]);
      $this->string($it->getSql())
           ->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` <> ($raw_subq)");

      $sub_query =new \QuerySubQuery($crit, 'NOT IN');
      $this->string($sub_query->getSubQuery())->isIdenticalTo($raw_subq);
      $this->string($sub_query->getOperator())->isIdenticalTo('NOT IN');

      $it = $this->it->execute('foo', ['bar' => $sub_query]);
      $this->string($it->getSql())
           ->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` NOT IN ($raw_subq)");

      $this->exception(
         function() use($crit) {
            $sub_query =new \QuerySubQuery($crit, 'NOONE');
         }
      )
         ->isInstanceOf('RuntimeException')
         ->hasMessage('Unknown query operator NOONE');
   }
}
