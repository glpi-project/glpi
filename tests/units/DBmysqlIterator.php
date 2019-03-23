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
use Symfony\Component\Yaml\Yaml;

// Generic test classe, to be extended for CommonDBTM Object

class DBmysqlIterator extends DbTestCase {

   private $it;

   public function beforeTestMethod($method) {
      parent::beforeTestMethod($method);

      $db_config = Yaml::parseFile(GLPI_CONFIG_DIR . '/db.yaml');
      $dbclass = '\mock\\' . \Glpi\DatabaseFactory::getDbClass($db_config['driver']);
      $db = new $dbclass($db_config);
      $this->calling($db)->rawQuery->doesNothing;
      $this->it = new \DBmysqlIterator($db);
   }

   public function testQuery() {
      $req = 'SELECT Something FROM Somewhere';
      $it = $this->it->executeRaw($req);
      $this->string($it->getSql())->isIdenticalTo($req);
      $req = 'SELECT @@sql_mode as mode';
      $it = $this->it->executeRaw($req);
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
      $this->exception(
         function() {
            $it = $this->it->execute('', ['foo' => 1]);
            $this->array($it->getParameters())->isIdenticalTo([1]);
            $this->string($it->getSql())->isIdenticalTo('SELECT * WHERE `foo` = ?');
         }
      )
         ->isInstanceOf('RuntimeException')
         ->message->contains('Missing table name');
   }


   /**
    * Temporarily, this is an error, will be allowed later
    */
   public function testNoTableWithoutWhere() {
      $this->exception(
         function () {
            $it = $this->it->execute('');
            $this->string($it->getSql())->isIdenticalTo('SELECT *');
         }
      )
         ->isInstanceOf('RuntimeException')
         ->hasMessage('Missing table name');
   }


   /**
    * Temporarily, this is an error, will be allowed later
    */
   public function testNoTableWithoutWhereBis() {
      $this->exception(
         function () {
            $it = $this->it->execute(['FROM' => []]);
            $this->string('SELECT *', $it->getSql(), 'No table');
         }
      )
         ->isInstanceOf('RuntimeException')
         ->hasMessage('Missing table name');

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
      $it = $this->it->execute('foo', ['FIELDS' => 'bar', 'DISTINCT' => true]);
      $this->string($it->getSql())->isIdenticalTo('SELECT DISTINCT `bar` FROM `foo`');

      $it = $this->it->execute('foo', ['FIELDS' => ['bar', 'baz'], 'DISTINCT' => true]);
      $this->string($it->getSql())->isIdenticalTo('SELECT DISTINCT `bar`, `baz` FROM `foo`');

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

      // Backward compability tests
      $this->exception(
         function() {
            $it = $this->it->execute('foo', ['DISTINCT FIELDS' => ['bar', 'baz']]);
            $this->string($it->getSql())->isIdenticalTo('SELECT DISTINCT `bar`, `baz` FROM `foo`');
         }
      )
         ->isInstanceOf('RuntimeException')
         ->message->contains('"SELECT DISTINCT" and "DISTINCT FIELDS" are depreciated.');

      $this->exception(
         function() {
            $it = $this->it->execute('foo', ['DISTINCT FIELDS' => ['bar'], 'FIELDS' => ['baz']]);
            $this->string($it->getSql())->isIdenticalTo('SELECT DISTINCT `bar`, `baz` FROM `foo`');
         }
      )
         ->isInstanceOf('RuntimeException')
         ->message->contains('"SELECT DISTINCT" and "DISTINCT FIELDS" are depreciated.');

      $this->exception(
         function() {
            $it = $this->it->execute('foo', ['DISTINCT FIELDS' => 'bar', 'FIELDS' => ['baz']]);
            $this->string($it->getSql())->isIdenticalTo('SELECT DISTINCT `bar`, `baz` FROM `foo`');
         }
      )
         ->isInstanceOf('RuntimeException')
         ->message->contains('"SELECT DISTINCT" and "DISTINCT FIELDS" are depreciated.');

      $this->exception(
         function() {
            $it = $this->it->execute('foo', ['DISTINCT FIELDS' => ['bar'], 'FIELDS' => 'baz']);
            $this->string($it->getSql())->isIdenticalTo('SELECT DISTINCT `bar`, `baz` FROM `foo`');
         }
      )
         ->isInstanceOf('RuntimeException')
         ->message->contains('"SELECT DISTINCT" and "DISTINCT FIELDS" are depreciated.');

      $this->exception(
         function() {
            $it = $this->it->execute('foo', ['DISTINCT FIELDS' => 'bar', 'FIELDS' => 'baz']);
            $this->string($it->getSql())->isIdenticalTo('SELECT DISTINCT `bar`, `baz` FROM `foo`');
         }
      )
         ->isInstanceOf('RuntimeException')
         ->message->contains('"SELECT DISTINCT" and "DISTINCT FIELDS" are depreciated.');
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

      $it = $this->it->execute('foo', ['ORDER' => new \QueryExpression('BIT_COUNT(`netmask_3`) DESC')]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY BIT_COUNT(`netmask_3`) DESC');

      $it = $this->it->execute('foo', ['ORDER' => [new \QueryExpression('BIT_COUNT(`netmask_3`) DESC'), new \QueryExpression('BIT_COUNT(`netmask_4`) DESC')]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY BIT_COUNT(`netmask_3`) DESC, BIT_COUNT(`netmask_4`) DESC');
   }


   public function testCount() {
      $it = $this->it->execute('foo', ['COUNT' => 'cpt']);
      $this->string($it->getSql())->isIdenticalTo('SELECT COUNT(*) AS cpt FROM `foo`');

      $it = $this->it->execute('foo', ['COUNT' => 'cpt', 'SELECT' => 'bar', 'DISTINCT' => true]);
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

      // Backward compability tests
      $this->exception(
         function() {
            $it = $this->it->execute('foo', ['COUNT' => 'cpt', 'SELECT DISTINCT' => 'bar']);
            $this->string($it->getSql())->isIdenticalTo('SELECT COUNT(DISTINCT `bar`) AS cpt FROM `foo`');
         }
      )
         ->isInstanceOf('RuntimeException')
         ->message->contains('"SELECT DISTINCT" and "DISTINCT FIELDS" are depreciated.');
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
      $it = $this->it->execute('foo', ['LEFT JOIN' => []]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo`');

      $it = $this->it->execute('foo', ['LEFT JOIN' => ['bar' => ['FKEY' => ['bar' => 'id', 'foo' => 'fk']]]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)');

      $it = $this->it->execute('foo', ['LEFT JOIN' => [['TABLE' => 'bar', 'FKEY' => ['bar' => 'id', 'foo' => 'fk']]]]);
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

      $it = $this->it->execute('foo', ['INNER JOIN' => []]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo`');

      $it = $this->it->execute('foo', ['INNER JOIN' => ['bar' => ['FKEY' => ['bar' => 'id', 'foo' => 'fk']]]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` INNER JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)');

      $it = $this->it->execute('foo', ['RIGHT JOIN' => []]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo`');

      $it = $this->it->execute('foo', ['RIGHT JOIN' => ['bar' => ['FKEY' => ['bar' => 'id', 'foo' => 'fk']]]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` RIGHT JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)');

      $this->exception(
         function() {
            $it = $this->it->execute('foo', ['LEFT JOIN' => ['ON' => ['a' => 'id', 'b' => 'a_id']]]);
         }
      )
         ->isInstanceOf('RuntimeException')
         ->hasMessage('BAD JOIN');

      /* FIXME: dunno why this one fails
      $this->exception(
         function () {
            $it = $this->it->execute('foo', ['LEFT JOIN' => 'bar']);
         }
      )
         ->isInstanceOf('RuntimeException')
         ->hasMessage('BAD JOIN, value must be [ table => criteria ]');*/

      $this->exception(
         function () {
            $it = $this->it->execute('foo', ['INNER JOIN' => ['bar' => ['FKEY' => 'akey']]]);
         }
      )
         ->isInstanceOf('RuntimeException')
         ->hasMessage('BAD FOREIGN KEY, should be [ table1 => key1, table2 => key2 ] or [ table1 => key1, table2 => key2, [criteria]]');

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
         'SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk` OR `field` > ?)'
      );
      $this->array($it->getParameters())->isIdenticalTo([20]);

      $it = $this->it->execute(
         'foo', [
            'LEFT JOIN' => [
               'bar' => [
                  'FKEY' => [
                     'bar' => 'id',
                     'foo' => 'fk', [
                        'AND'  => ['field' => 42]
                     ]
                  ]
               ]
            ]
         ]
      );
      $this->string($it->getSql())->isIdenticalTo(
         'SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk` AND `field` = ?)'
      );
      $this->array($it->getParameters())->isIdenticalTo([42]);

      //test derived table in JOIN statement
      $it = $this->it->execute(
         'foo', [
            'LEFT JOIN' => [
               [
                  'TABLE'  => new \QuerySubQuery(['FROM' => 'bar'], 't2'),
                  'FKEY'   => [
                     't2'  => 'id',
                     'foo' => 'fk'
                  ]
               ]
            ]
         ]
      );
      $this->string($it->getSql())->isIdenticalTo(
         'SELECT * FROM `foo` LEFT JOIN (SELECT * FROM `bar`) AS `t2` ON (`t2`.`id` = `foo`.`fk`)'
      );

   }

   public function testHaving() {
      $it = $this->it->execute('foo', ['HAVING' => ['bar' => 1]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` HAVING `bar` = ?');
      $this->array($it->getParameters())->isIdenticalTo([1]);

      $it = $this->it->execute('foo', ['HAVING' => ['bar' => ['>', 0]]]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` HAVING `bar` > ?');
      $this->array($it->getParameters())->isIdenticalTo([0]);
   }



   public function testOperators() {
      $it = $this->it->execute('foo', ['a' => 1]);
      $this->array($it->getParameters())->isIdenticalTo([1]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` = ?');

      $it = $this->it->execute('foo', ['a' => ['=', 1]]);
      $this->array($it->getParameters())->isIdenticalTo([1]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` = ?');

      $it = $this->it->execute('foo', ['a' => ['>', 1]]);
      $this->array($it->getParameters())->isIdenticalTo([1]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` > ?');

      $it = $this->it->execute('foo', ['a' => ['LIKE', '%bar%']]);
      $this->array($it->getParameters())->isIdenticalTo(['%bar%']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` LIKE ?');

      $it = $this->it->execute('foo', ['NOT' => ['a' => ['LIKE', '%bar%']]]);
      $this->array($it->getParameters())->isIdenticalTo(['%bar%']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE NOT (`a` LIKE ?)');

      $it = $this->it->execute('foo', ['a' => ['NOT LIKE', '%bar%']]);
      $this->array($it->getParameters())->isIdenticalTo(['%bar%']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` NOT LIKE ?');

      $it = $this->it->execute('foo', ['a' => ['<>', 1]]);
      $this->array($it->getParameters())->isIdenticalTo([1]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` <> ?');

      $it = $this->it->execute('foo', ['a' => ['&', 1]]);
      $this->array($it->getParameters())->isIdenticalTo([1]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` & ?');

      $it = $this->it->execute('foo', ['a' => ['|', 1]]);
      $this->array($it->getParameters())->isIdenticalTo([1]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` | ?');
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
      $this->array($it->getParameters())->isIdenticalTo([1]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `bar` = ?');

      $this->exception(
         function() {
            $it = $this->it->execute('foo', ['bar' => []]);
         }
      )
         ->isInstanceOf('RuntimeException')
         ->hasMessage('Empty IN are not allowed');

      $it = $this->it->execute('foo', ['bar' => [1, 2, 4]]);
      $this->array($it->getParameters())->isIdenticalTo([1, 2, 4]);
      $this->string($it->getSql())->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` IN (?,?,?)");

      $it = $this->it->execute('foo', ['bar' => ['a', 'b', 'c']]);
      $this->array($it->getParameters())->isIdenticalTo(['a', 'b', 'c']);
      $this->string($it->getSql())->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` IN (?,?,?)");

      $it = $this->it->execute('foo', ['bar' => 'val']);
      $this->array($it->getParameters())->isIdenticalTo(['val']);
      $this->string($it->getSql())->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` = ?");

      $it = $this->it->execute('foo', ['bar' => '`field`']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `bar` = `field`');

      $it = $this->it->execute('foo', ['bar' => '?']);
      $this->array($it->getParameters())->isIdenticalTo(['?']);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `bar` = ?');

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
      $this->exception(
         function () {
            $it = $this->it->execute(['foo'], ['GROUPBY' => []]);
            $this->string('SELECT * FROM `foo`', $it->getSql(), 'No group by field');
         }
      )
         ->isInstanceOf('RuntimeException')
         ->hasMessage('Missing group by field');

      $this->exception(
         function () {
            $it = $this->it->execute(['foo'], ['GROUP' => []]);
            $this->string('SELECT * FROM `foo`', $it->getSql(), 'No group by field');
         }
      )
         ->isInstanceOf('RuntimeException')
         ->hasMessage('Missing group by field');

   }

   public function testRange() {

      $it = $this->it->execute('foo', ['START' => 5, 'LIMIT' => 10]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` LIMIT 10 OFFSET 5');
   }


   public function testLogical() {
      $it = $this->it->execute(['foo'], [['a' => 1, 'b' => 2]]);
      $this->array($it->getParameters())->isIdenticalTo([1, 2]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE (`a` = ? AND `b` = ?)');

      $it = $this->it->execute(['foo'], ['AND' => ['a' => 1, 'b' => 2]]);
      $this->array($it->getParameters())->isIdenticalTo([1, 2]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE (`a` = ? AND `b` = ?)');

      $it = $this->it->execute(['foo'], ['OR' => ['a' => 1, 'b' => 2]]);
      $this->array($it->getParameters())->isIdenticalTo([1, 2]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE (`a` = ? OR `b` = ?)');

      $it = $this->it->execute(['foo'], ['NOT' => ['a' => 1, 'b' => 2]]);
      $this->array($it->getParameters())->isIdenticalTo([1, 2]);
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE NOT (`a` = ? AND `b` = ?)');

      $crit = [
         'WHERE' => [
            'OR' => [
               [
                  'items_id' => 15,
                  'itemtype' => 'Computer'
               ],
               [
                  'items_id' => 3,
                  'itemtype' => 'Document'
               ],
            ],
         ],
      ];
      $sql = "SELECT * FROM `foo` WHERE ((`items_id` = ? AND `itemtype` = ?) OR (`items_id` = ? AND `itemtype` = ?))";
      $it = $this->it->execute(['foo'], $crit);
      $this->array($it->getParameters())->isIdenticalTo([15, 'Computer', 3, 'Document']);
      $this->string($it->getSql())->isIdenticalTo($sql);

      $crit = [
         'WHERE' => [
            'a'  => 1,
            'OR' => [
               'b'   => 2,
               'NOT' => [
                  'c'   => [2, 3],
                  [
                     'd' => 4,
                     'e' => 5,
                  ],
               ],
            ],
         ],
      ];
      $sql = "SELECT * FROM `foo` WHERE `a` = ? AND (`b` = ? OR NOT (`c` IN (?,?) AND (`d` = ? AND `e` = ?)))";
      $it = $this->it->execute(['foo'], $crit);
      $this->array($it->getParameters())->isIdenticalTo([1, 2, 2, 3, 4, 5]);
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
      $this->string($it->getSql())->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` = ? AND ((SELECT COUNT(*) FROM xyz) = ?)");
      $this->array($it->getParameters())->isIdenticalTo(['baz', 5]);

      $crit = [
         'FROM'   => 'foo',
         'WHERE'  => [
            'bar' => 'baz',
            'RAW' => ['SELECT COUNT(*) FROM xyz' => ['>', 2]]
         ]
      ];
      $it = $this->it->execute($crit);
      $this->string($it->getSql())->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` = ? AND ((SELECT COUNT(*) FROM xyz) > ?)");
      $this->array($it->getParameters())->isIdenticalTo(['baz', 2]);

      $crit = [
         'FROM'   => 'foo',
         'WHERE'  => [
            'bar' => 'baz',
            'RAW' => ['SELECT COUNT(*) FROM xyz' => [3, 4]]
         ]
      ];
      $it = $this->it->execute($crit);
      $this->string($it->getSql())->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` = ? AND ((SELECT COUNT(*) FROM xyz) IN (?,?))");
      $this->array($it->getParameters())->isIdenticalTo(['baz', 3, 4]);
   }


   public function testModern() {
      $req = [
         'SELECT' => ['a', 'b'],
         'FROM'   => 'foo',
         'WHERE'  => ['c' => 1],
      ];
      $sql = "SELECT `a`, `b` FROM `foo` WHERE `c` = ?";
      $it = $this->it->execute($req);
      $this->array($it->getParameters())->isIdenticalTo([1]);
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
      $raw_subq = "(SELECT `id` FROM `baz` WHERE `z` = ?)";

      $sub_query =new \QuerySubQuery($crit);
      $this->string($sub_query->getQuery())->isIdenticalTo($raw_subq);
      $this->array($sub_query->getParameters())->isIdenticalTo(['f']);

      $it = $this->it->execute('foo', ['bar' => $sub_query]);
      $this->string($it->getSql())
           ->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` IN $raw_subq");

      $it = $this->it->execute('foo', ['bar' => ['<>', $sub_query]]);
      $this->string($it->getSql())
           ->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` <> $raw_subq");

      $it = $this->it->execute('foo', ['NOT' => ['bar' => $sub_query]]);
      $this->string($it->getSql())
           ->isIdenticalTo("SELECT * FROM `foo` WHERE NOT (`bar` IN $raw_subq)");

      $sub_query =new \QuerySubQuery($crit, 'thesubquery');
      $this->string($sub_query->getQuery())->isIdenticalTo("$raw_subq AS `thesubquery`");

      $it = $this->it->execute('foo', ['bar' => $sub_query]);
      $this->string($it->getSql())
           ->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` IN $raw_subq AS `thesubquery`");

      $it = $this->it->execute([
         'SELECT' => ['bar', $sub_query],
         'FROM'   => 'foo'
      ]);
      $this->string($it->getSql())
           ->isIdenticalTo("SELECT `bar`, $raw_subq AS `thesubquery` FROM `foo`");
   }

   public function testUnionQuery() {
      $union_crit = [
         ['FROM' => 'table1'],
         ['FROM' => 'table2']
      ];
      $union = new \QueryUnion($union_crit);
      $union_raw_query = '((SELECT * FROM `table1`) UNION ALL (SELECT * FROM `table2`))';
      $raw_query = 'SELECT * FROM ' . $union_raw_query . ' AS `union_' . md5($union_raw_query) . '`';
      $it = $this->it->execute(['FROM' => $union]);
      $this->string($it->getSql())->isIdenticalTo($raw_query);

      $union = new \QueryUnion($union_crit, true);
      $union_raw_query = '((SELECT * FROM `table1`) UNION (SELECT * FROM `table2`))';
      $raw_query = 'SELECT * FROM ' . $union_raw_query . ' AS `union_' . md5($union_raw_query) . '`';
      $it = $this->it->execute(['FROM' => $union]);
      $this->string($it->getSql())->isIdenticalTo($raw_query);

      $union = new \QueryUnion($union_crit, false, 'theunion');
      $raw_query = 'SELECT * FROM ((SELECT * FROM `table1`) UNION ALL (SELECT * FROM `table2`)) AS `theunion`';
      $it = $this->it->execute(['FROM' => $union]);
      $this->string($it->getSql())->isIdenticalTo($raw_query);

      $union = new \QueryUnion($union_crit, false, 'theunion');
      $raw_query = 'SELECT DISTINCT `theunion`.`field` FROM ((SELECT * FROM `table1`) UNION ALL (SELECT * FROM `table2`)) AS `theunion`';
      $crit = [
         'SELECT'    => 'theunion.field',
         'DISTINCT'  => true,
         'FROM'      => $union,
      ];
      $it = $this->it->execute($crit);
      $this->string($it->getSql())->isIdenticalTo($raw_query);

      $union = new \QueryUnion($union_crit, true);
      $union_raw_query = '((SELECT * FROM `table1`) UNION (SELECT * FROM `table2`))';
      $raw_query = 'SELECT DISTINCT `theunion`.`field` FROM ' . $union_raw_query . ' AS `union_' . md5($union_raw_query) . '`';
      $crit = [
         'SELECT'    => 'theunion.field',
         'DISTINCT'  => true,
         'FROM'      => $union,
      ];
      $it = $this->it->execute($crit);
      $this->string($it->getSql())->isIdenticalTo($raw_query);
   }

   public function testComplexUnionQuery() {

      $fk = \Ticket::getForeignKeyField();
      $users_table = \User::getTable();
      $users_table = 'glpi_ticket_users';
      $groups_table = 'glpi_groups_tickets';

      $subquery1 = new \QuerySubQuery([
         'SELECT'    => [
            'usr.id AS users_id',
            'tu.type AS type'
         ],
         'FROM'      => "$users_table AS tu",
         'LEFT JOIN' => [
            \User::getTable() . ' AS usr' => [
               'ON' => [
                  'tu'  => 'users_id',
                  'usr' => 'id'
               ]
            ]
         ],
         'WHERE'     => [
            "tu.$fk" => 42
         ]
      ]);
      $subquery2 = new \QuerySubQuery([
         'SELECT'    => [
            'usr.id AS users_id',
            'gt.type AS type'
         ],
         'FROM'      => "$groups_table AS gt",
         'LEFT JOIN' => [
            \Group_User::getTable() . ' AS gu'   => [
               'ON' => [
                  'gu'  => 'groups_id',
                  'gt'  => 'groups_id'
               ]
            ],
            \User::getTable() . ' AS usr'        => [
               'ON' => [
                  'gu'  => 'users_id',
                  'usr' => 'id'
               ]
            ]
         ],
         'WHERE'     => [
            "gt.$fk" => 42
         ]
      ]);

      $raw_query = "SELECT DISTINCT `users_id`, `type`"
                     . " FROM ((SELECT `usr`.`id` AS `users_id`, `tu`.`type` AS `type`"
                     . " FROM `$users_table` AS `tu`"
                     . " LEFT JOIN `glpi_users` AS `usr` ON (`tu`.`users_id` = `usr`.`id`)"
                     . " WHERE `tu`.`$fk` = ?)"
                     . " UNION ALL"
                     . " (SELECT `usr`.`id` AS `users_id`, `gt`.`type` AS `type`"
                     . " FROM `$groups_table` AS `gt`"
                     . " LEFT JOIN `glpi_groups_users` AS `gu` ON (`gu`.`groups_id` = `gt`.`groups_id`)"
                     . " LEFT JOIN `glpi_users` AS `usr` ON (`gu`.`users_id` = `usr`.`id`)"
                     . " WHERE `gt`.`$fk` = ?)"
                     . ") AS `allactors`";

      $union = new \QueryUnion([$subquery1, $subquery2], false, 'allactors');
      $this->array($union->getParameters())->isIdenticalTo([42, 42]);

      $it = $this->it->execute([
         'FIELDS'          => [
            'users_id',
            'type'
         ],
         'DISTINCT'        => true,
         'FROM'            => $union
      ]);
      $this->string($it->getSql())->isIdenticalTo($raw_query);
      $this->array($it->getParameters())->isIdenticalTo([42, 42]);
   }

   public function testComplexUnionQueryAgain() {
      global $CFG_GLPI, $DB;

      //Old build way
      $queries = [];

      foreach ($CFG_GLPI["networkport_types"] as $itemtype) {
         $table = getTableForItemType($itemtype);
         $queries[] = "(SELECT `ADDR`.`binary_0` AS `binary_0`,
                                 `ADDR`.`binary_1` AS `binary_1`,
                                 `ADDR`.`binary_2` AS `binary_2`,
                                 `ADDR`.`binary_3` AS `binary_3`,
                                 `ADDR`.`name` AS `ip`,
                                 `ADDR`.`id` AS `id`,
                                 `ADDR`.`itemtype` AS `addr_item_type`,
                                 `ADDR`.`items_id` AS `addr_item_id`,
                                 `glpi_entities`.`completename` AS `entity`,
                                 `NAME`.`id` AS `name_id`,
                                 `PORT`.`id` AS `port_id`,
                                 `ITEM`.`id` AS `item_id`,
                                 '$itemtype' AS `item_type`
                        FROM `glpi_ipaddresses_ipnetworks` AS `LINK`
                        INNER JOIN `glpi_ipaddresses` AS `ADDR` ON (`ADDR`.`id` = `LINK`.`ipaddresses_id`
                                                            AND `ADDR`.`itemtype` = ?
                                                            AND `ADDR`.`is_deleted` = ?)
                        INNER JOIN `glpi_networknames` AS `NAME` ON (`NAME`.`id` = `ADDR`.`items_id`
                                                               AND `NAME`.`itemtype` = ?)
                        INNER JOIN `glpi_networkports` AS `PORT` ON (`NAME`.`items_id` = `PORT`.`id`
                                                               AND `PORT`.`itemtype` = ?)
                        INNER JOIN `$table` AS `ITEM` ON (`ITEM`.`id` = `PORT`.`items_id`)
                        LEFT JOIN `glpi_entities` ON (`ADDR`.`entities_id` = `glpi_entities`.`id`)
                        WHERE `LINK`.`ipnetworks_id` = ?)";
      }

      $queries[] = "(SELECT `ADDR`.`binary_0` AS `binary_0`,
                              `ADDR`.`binary_1` AS `binary_1`,
                              `ADDR`.`binary_2` AS `binary_2`,
                              `ADDR`.`binary_3` AS `binary_3`,
                              `ADDR`.`name` AS `ip`,
                              `ADDR`.`id` AS `id`,
                              `ADDR`.`itemtype` AS `addr_item_type`,
                              `ADDR`.`items_id` AS `addr_item_id`,
                              `glpi_entities`.`completename` AS `entity`,
                              `NAME`.`id` AS `name_id`,
                              `PORT`.`id` AS `port_id`,
                              NULL AS `item_id`,
                              NULL AS `item_type`
                     FROM `glpi_ipaddresses_ipnetworks` AS `LINK`
                     INNER JOIN `glpi_ipaddresses` AS `ADDR` ON (`ADDR`.`id` = `LINK`.`ipaddresses_id`
                                                         AND `ADDR`.`itemtype` = ?
                                                         AND `ADDR`.`is_deleted` = ?)
                     INNER JOIN `glpi_networknames` AS `NAME` ON (`NAME`.`id` = `ADDR`.`items_id`
                                                            AND `NAME`.`itemtype` = ?)
                     INNER JOIN `glpi_networkports` AS `PORT`
                        ON (`NAME`.`items_id` = `PORT`.`id`
                              NOT `PORT`.`itemtype`
                                 IN ("  . implode(',', array_fill(0, count($CFG_GLPI['networkport_types']), '?')) . "))
                     LEFT JOIN `glpi_entities` ON (`ADDR`.`entities_id` = `glpi_entities`.`id`)
                     WHERE `LINK`.`ipnetworks_id` = ?)";

      $queries[] = "(SELECT `ADDR`.`binary_0` AS `binary_0`,
                              `ADDR`.`binary_1` AS `binary_1`,
                              `ADDR`.`binary_2` AS `binary_2`,
                              `ADDR`.`binary_3` AS `binary_3`,
                              `ADDR`.`name` AS `ip`,
                              `ADDR`.`id` AS `id`,
                              `ADDR`.`itemtype` AS `addr_item_type`,
                              `ADDR`.`items_id` AS `addr_item_id`,
                              `glpi_entities`.`completename` AS `entity`,
                              `NAME`.`id` AS `name_id`,
                              NULL AS `port_id`,
                              NULL AS `item_id`,
                              NULL AS `item_type`
                     FROM `glpi_ipaddresses_ipnetworks` AS `LINK`
                     INNER JOIN `glpi_ipaddresses` AS `ADDR` ON (`ADDR`.`id` = `LINK`.`ipaddresses_id`
                                                         AND `ADDR`.`itemtype` = ?
                                                         AND `ADDR`.`is_deleted` = ?)
                     INNER JOIN `glpi_networknames` AS `NAME` ON (`NAME`.`id` = `ADDR`.`items_id`
                                                            AND `NAME`.`itemtype` != ?)
                     LEFT JOIN `glpi_entities` ON (`ADDR`.`entities_id` = `glpi_entities`.`id`)
                     WHERE `LINK`.`ipnetworks_id` = ?)";

      $queries[] = "(SELECT `ADDR`.`binary_0` AS `binary_0`,
                              `ADDR`.`binary_1` AS `binary_1`,
                              `ADDR`.`binary_2` AS `binary_2`,
                              `ADDR`.`binary_3` AS `binary_3`,
                              `ADDR`.`name` AS `ip`,
                              `ADDR`.`id` AS `id`,
                              `ADDR`.`itemtype` AS `addr_item_type`,
                              `ADDR`.`items_id` AS `addr_item_id`,
                              `glpi_entities`.`completename` AS `entity`,
                              NULL AS `name_id`,
                              NULL AS `port_id`,
                              NULL AS `item_id`,
                              NULL AS `item_type`
                     FROM `glpi_ipaddresses_ipnetworks` AS `LINK`
                     INNER JOIN `glpi_ipaddresses` AS `ADDR` ON (`ADDR`.`id` = `LINK`.`ipaddresses_id`
                                                         AND `ADDR`.`itemtype` != ?
                                                         AND `ADDR`.`is_deleted` = ?)
                     LEFT JOIN `glpi_entities` ON (`ADDR`.`entities_id` = `glpi_entities`.`id`)
                     WHERE `LINK`.`ipnetworks_id` = ?)";

      $union_raw_query = '(' . preg_replace('/\s+/', ' ', implode(' UNION ALL ', $queries)) . ')';
      $raw_query = 'SELECT * FROM ' . $union_raw_query . ' AS `union_' . md5($union_raw_query) . '`';

      //New build way
      $queries = [];
      $main_criteria = [
         'SELECT'       => [
            'ADDR.binary_0 AS binary_0',
            'ADDR.binary_1 AS binary_1',
            'ADDR.binary_2 AS binary_2',
            'ADDR.binary_3 AS binary_3',
            'ADDR.name AS ip',
            'ADDR.id AS id',
            'ADDR.itemtype AS addr_item_type',
            'ADDR.items_id AS addr_item_id',
            'glpi_entities.completename AS entity',
         ],
         'FROM'         => 'glpi_ipaddresses_ipnetworks AS LINK',
         'INNER JOIN'   => [
            'glpi_ipaddresses AS ADDR' => [
               'ON' => [
                  'ADDR'   => 'id',
                  'LINK'   => 'ipaddresses_id', [
                     'AND' => [
                        'ADDR.itemtype' => 'NetworkName',
                        'ADDR.is_deleted' => 0
                     ]
                  ]
               ]
            ]
         ],
         'LEFT JOIN'    => [
            'glpi_entities'             => [
               'ON' => [
                  'ADDR'            => 'entities_id',
                  'glpi_entities'   => 'id'
               ]
            ]
         ],
         'WHERE'        => [
            'LINK.ipnetworks_id' => 42,
         ]
      ];

      foreach ($CFG_GLPI["networkport_types"] as $itemtype) {
         $table = getTableForItemType($itemtype);
         $criteria = $main_criteria;
         $criteria['SELECT'] = array_merge($criteria['SELECT'], [
            'NAME.id AS name_id',
            'PORT.id AS port_id',
            'ITEM.id AS item_id',
            new \QueryExpression("'$itemtype' AS " . $DB->quoteName('item_type'))
         ]);
         $criteria['INNER JOIN'] = $criteria['INNER JOIN'] + [
            'glpi_networknames AS NAME'   => [
               'ON' => [
                  'NAME'   => 'id',
                  'ADDR'   => 'items_id', [
                     'AND' => [
                        'NAME.itemtype' => 'NetworkPort'
                     ]
                  ]
               ]
            ],
            'glpi_networkports AS PORT'   => [
               'ON' => [
                  'NAME'   => 'items_id',
                  'PORT'   => 'id', [
                     'AND' => [
                        'PORT.itemtype' => $itemtype
                     ]
                  ]
               ]
            ],
            "$table AS ITEM"              => [
               'ON' => [
                  'ITEM'   => 'id',
                  'PORT'   => 'items_id'
               ]
            ]
         ];
         $queries[] = $criteria;
      }

      $criteria = $main_criteria;
      $criteria['SELECT'] = array_merge($criteria['SELECT'], [
         'NAME.id AS name_id',
         'PORT.id AS port_id',
         new \QueryExpression('NULL AS ' . $DB->quoteName('item_id')),
         new \QueryExpression("NULL AS " . $DB->quoteName('item_type')),
      ]);
      $criteria['INNER JOIN'] = $criteria['INNER JOIN'] + [
         'glpi_networknames AS NAME'   => [
            'ON' => [
               'NAME'   => 'id',
               'ADDR'   => 'items_id', [
                  'AND' => [
                     'NAME.itemtype' => 'NetworkPort'
                  ]
               ]
            ]
         ],
         'glpi_networkports AS PORT'   => [
            'ON' => [
               'NAME'   => 'items_id',
               'PORT'   => 'id', [
                  'NOT' => [
                     'PORT.itemtype' => $CFG_GLPI['networkport_types']
                  ]
               ]
            ]
         ]
      ];
      $queries[] = $criteria;

      $criteria = $main_criteria;
      $criteria['SELECT'] = array_merge($criteria['SELECT'], [
         'NAME.id AS name_id',
         new \QueryExpression("NULL AS " . $DB->quoteName('port_id')),
         new \QueryExpression('NULL AS ' . $DB->quoteName('item_id')),
         new \QueryExpression("NULL AS " . $DB->quoteName('item_type'))
      ]);
      $criteria['INNER JOIN'] = $criteria['INNER JOIN'] + [
         'glpi_networknames AS NAME'   => [
            'ON' => [
               'NAME'   => 'id',
               'ADDR'   => 'items_id', [
                  'AND' => [
                     'NAME.itemtype' => ['!=', 'NetworkPort']
                  ]
               ]
            ]
         ]
      ];
      $queries[] = $criteria;

      $criteria = $main_criteria;
      $criteria['SELECT'] = array_merge($criteria['SELECT'], [
         new \QueryExpression("NULL AS " . $DB->quoteName('name_id')),
         new \QueryExpression("NULL AS " . $DB->quoteName('port_id')),
         new \QueryExpression('NULL AS ' . $DB->quoteName('item_id')),
         new \QueryExpression("NULL AS " . $DB->quoteName('item_type'))
      ]);
      $criteria['INNER JOIN']['glpi_ipaddresses AS ADDR']['ON'][0]['AND']['ADDR.itemtype'] = ['!=', 'NetworkName'];
      $queries[] = $criteria;

      $union = new \QueryUnion($queries);
      $criteria = [
         'FROM'   => $union,
      ];

      $it = $this->it->execute($criteria);
      $this->string($it->getSql())->isIdenticalTo($raw_query);
      $this->array($it->getParameters())->isIdenticalTo([
        0 => 'NetworkName',
        1 => 0,
        2 => 'NetworkPort',
        3 => 'Computer',
        4 => 42,
        5 => 'NetworkName',
        6 => 0,
        7 => 'NetworkPort',
        8 => 'NetworkEquipment',
        9 => 42,
        10 => 'NetworkName',
        11 => 0,
        12 => 'NetworkPort',
        13 => 'Peripheral',
        14 => 42,
        15 => 'NetworkName',
        16 => 0,
        17 => 'NetworkPort',
        18 => 'Phone',
        19 => 42,
        20 => 'NetworkName',
        21 => 0,
        22 => 'NetworkPort',
        23 => 'Printer',
        24 => 42,
        25 => 'NetworkName',
        26 => 0,
        27 => 'NetworkPort',
        28 => 'Enclosure',
        29 => 42,
        30 => 'NetworkName',
        31 => 0,
        32 => 'NetworkPort',
        33 => 'PDU',
        34 => 42,
        35 => 'NetworkName',
        36 => 0,
        37 => 'NetworkPort',
        38 => 'Computer',
        39 => 'NetworkEquipment',
        40 => 'Peripheral',
        41 => 'Phone',
        42 => 'Printer',
        43 => 'Enclosure',
        44 => 'PDU',
        45 => 42,
        46 => 'NetworkName',
        47 => 0,
        48 => 'NetworkPort',
        49 => 42,
        50 => 'NetworkName',
        51 => 0,
        52 => 42
      ]);
   }

   public function testAnalyseCrit() {
      global $DB;

      $crit = [new \QuerySubQuery([
         'SELECT' => ['COUNT' => ['users_id']],
         'FROM'   => 'glpi_groups_users',
         'WHERE'  => ['groups_id' => new \QueryExpression($DB->quoteName('glpi_groups.id'))]
      ])];
      $this->string($this->it->analyseCrit($crit))->isIdenticalTo("(SELECT COUNT(`users_id`) FROM `glpi_groups_users` WHERE `groups_id` = `glpi_groups`.`id`)");
   }
}
