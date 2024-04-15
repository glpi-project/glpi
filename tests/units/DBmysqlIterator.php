<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace tests\units;

use DbTestCase;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryUnion;
use Psr\Log\LogLevel;

// Generic test classe, to be extended for CommonDBTM Object

class DBmysqlIterator extends DbTestCase
{
    /** @var \DBmysqlIterator */
    private $it;

    public function beforeTestMethod($method)
    {
        parent::beforeTestMethod($method);
        $this->it = new \DBmysqlIterator(null);
    }

    public function testSqlError()
    {
        global $DB;

        $expected_error = "Table '{$DB->dbdefault}.fakeTable' doesn't exist";
        $DB->request(['FROM' => 'fakeTable']);
        $this->hasSqlLogRecordThatContains($expected_error, LogLevel::ERROR);
    }


    public function testOnlyTable()
    {

        $it = $this->it->execute(['FROM' => 'foo']);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo`');

        $it = $this->it->execute(['FROM' => '`foo`']);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo`');

        $it = $this->it->execute(['FROM' => ['foo', '`bar`']]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo`, `bar`');
    }


    /**
     * This is really an error, no table but a WHERE clause
     */
    public function testNoTableWithWhere()
    {
        $this->exception(
            function () {
                $it = $this->it->execute(['FROM' => [], 'WHERE' => ['foo' => 1]]);
                $this->string($it->getSql())->isIdenticalTo('SELECT * WHERE `foo` = \'1\'');
            }
        )->isInstanceOf(\LogicException::class)
         ->hasMessage('Missing table name.');
    }


    /**
     * Temporarily, this is an error, will be allowed later
     */
    public function testNoTableWithoutWhere()
    {
        $this->exception(
            function () {
                $it = $this->it->execute(['']);
                $this->string($it->getSql())->isIdenticalTo('SELECT *');
            }
        )->isInstanceOf(\LogicException::class)
         ->hasMessage('Missing table name.');
    }


    /**
     * Temporarily, this is an error, will be allowed later
     */
    public function testNoTableWithoutWhereBis()
    {
        $this->exception(
            function () {
                $it = $this->it->execute(['FROM' => []]);
                $this->string('SELECT *', $it->getSql(), 'No table');
            }
        )->isInstanceOf(\LogicException::class)
         ->hasMessage('Missing table name.');
    }


    public function testDebug()
    {
        define('GLPI_SQL_DEBUG', true);

        $id = mt_rand();
        $this->it->execute(['FROM' => 'foo', 'FIELDS' => 'name', 'id = ' . $id]);

        $this->hasSqlLogRecordThatContains(
            'Generated query: SELECT `name` FROM `foo` WHERE (id = ' . $id . ')',
            LogLevel::DEBUG
        );
    }


    public function testFields()
    {
        $it = $this->it->execute(['FROM' => 'foo', 'FIELDS' => 'bar', 'DISTINCT' => true]);
        $this->string($it->getSql())->isIdenticalTo('SELECT DISTINCT `bar` FROM `foo`');

        $it = $this->it->execute(['FROM' => 'foo', 'FIELDS' => ['bar', 'baz'], 'DISTINCT' => true]);
        $this->string($it->getSql())->isIdenticalTo('SELECT DISTINCT `bar`, `baz` FROM `foo`');

        $it = $this->it->execute(['FROM' => 'foo', 'FIELDS' => 'bar']);
        $this->string($it->getSql())->isIdenticalTo('SELECT `bar` FROM `foo`');

        $it = $this->it->execute(['FROM' => 'foo', 'FIELDS' => ['bar', '`baz`']]);
        $this->string($it->getSql())->isIdenticalTo('SELECT `bar`, `baz` FROM `foo`');

        $it = $this->it->execute(['FROM' => 'foo', 'FIELDS' => ['b' => 'bar']]);
        $this->string($it->getSql())->isIdenticalTo('SELECT `b`.`bar` FROM `foo`');

        $it = $this->it->execute(['FROM' => 'foo', 'FIELDS' => ['b' => 'bar', '`c`' => '`baz`']]);
        $this->string($it->getSql())->isIdenticalTo('SELECT `b`.`bar`, `c`.`baz` FROM `foo`');

        $it = $this->it->execute(['FROM' => 'foo', 'FIELDS' => ['a' => ['`bar`', 'baz']]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT `a`.`bar`, `a`.`baz` FROM `foo`');

        $it = $this->it->execute(['FROM' => ['foo', 'bar'], 'FIELDS' => ['foo' => ['*']]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT `foo`.* FROM `foo`, `bar`');

        $it = $this->it->execute(['FROM' => ['foo', 'bar'], 'FIELDS' => ['foo.*']]);
        $this->string($it->getSql())->isIdenticalTo('SELECT `foo`.* FROM `foo`, `bar`');

        $it = $this->it->execute(['FROM' => 'foo', 'FIELDS' => ['SUM' => 'bar AS cpt']]);
        $this->string($it->getSql())->isIdenticalTo('SELECT SUM(`bar`) AS `cpt` FROM `foo`');

        $it = $this->it->execute(['FROM' => 'foo', 'FIELDS' => ['AVG' => 'bar AS cpt']]);
        $this->string($it->getSql())->isIdenticalTo('SELECT AVG(`bar`) AS `cpt` FROM `foo`');

        $it = $this->it->execute(['FROM' => 'foo', 'FIELDS' => ['MIN' => 'bar AS cpt']]);
        $this->string($it->getSql())->isIdenticalTo('SELECT MIN(`bar`) AS `cpt` FROM `foo`');

        $it = $this->it->execute(['FROM' => 'foo', 'FIELDS' => ['MAX' => 'bar AS cpt']]);
        $this->string($it->getSql())->isIdenticalTo('SELECT MAX(`bar`) AS `cpt` FROM `foo`');

        $it = $this->it->execute(['FROM' => 'foo', 'FIELDS' => new \Glpi\DBAL\QueryExpression('IF(bar IS NOT NULL, 1, 0) AS baz')]);
        $this->string($it->getSql())->isIdenticalTo('SELECT IF(bar IS NOT NULL, 1, 0) AS baz FROM `foo`');
    }

    public function testFrom()
    {
        $this->it->buildQuery(['FIELDS' => 'bar', 'FROM' => 'foo']);
        $this->string($this->it->getSql())->isIdenticalTo('SELECT `bar` FROM `foo`');

        $this->it->buildQuery(['FIELDS' => 'bar', 'FROM' => 'foo as baz']);
        $this->string($this->it->getSql())->isIdenticalTo('SELECT `bar` FROM `foo` AS `baz`');

        $this->it->buildQuery(['FIELDS' => 'bar', 'FROM' => ['foo', 'baz']]);
        $this->string($this->it->getSql())->isIdenticalTo('SELECT `bar` FROM `foo`, `baz`');

        $this->it->buildQuery(['FIELDS' => 'c', 'FROM' => new \Glpi\DBAL\QueryExpression("(SELECT CONCAT('foo', 'baz') as c) as t")]);
        $this->string($this->it->getSql())->isIdenticalTo("SELECT `c` FROM (SELECT CONCAT('foo', 'baz') as c) as t");
    }


    public function testOrder()
    {
        $it = $this->it->execute(['FROM' => 'foo', 'ORDERBY' => 'bar']);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `bar`');

        $it = $this->it->execute(['FROM' => 'foo', 'ORDER' => 'bar']);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `bar`');

        $it = $this->it->execute(['FROM' => 'foo', 'ORDERBY' => '`baz`']);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `baz`');

        $it = $this->it->execute(['FROM' => 'foo', 'ORDER' => '`baz`']);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `baz`');

        $it = $this->it->execute(['FROM' => 'foo', 'ORDERBY' => 'bar ASC']);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `bar` ASC');

        $it = $this->it->execute(['FROM' => 'foo', 'ORDER' => 'bar ASC']);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `bar` ASC');

        $it = $this->it->execute(['FROM' => 'foo', 'ORDERBY' => 'bar DESC']);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `bar` DESC');

        $it = $this->it->execute(['FROM' => 'foo', 'ORDER' => 'bar DESC']);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `bar` DESC');

        $it = $this->it->execute(['FROM' => 'foo', 'ORDERBY' => ['`a`', 'b ASC', 'c DESC']]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `a`, `b` ASC, `c` DESC');

        $it = $this->it->execute(['FROM' => 'foo', 'ORDER' => ['`a`', 'b ASC', 'c DESC']]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `a`, `b` ASC, `c` DESC');

        $it = $this->it->execute(['FROM' => 'foo', 'ORDERBY' => 'bar, baz ASC']);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `bar`, `baz` ASC');

        $it = $this->it->execute(['FROM' => 'foo', 'ORDER' => 'bar, baz ASC']);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `bar`, `baz` ASC');

        $it = $this->it->execute(['FROM' => 'foo', 'ORDERBY' => 'bar DESC, baz ASC']);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `bar` DESC, `baz` ASC');

        $it = $this->it->execute(['FROM' => 'foo', 'ORDER' => 'bar DESC, baz ASC']);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` ORDER BY `bar` DESC, `baz` ASC');

        $it = $this->it->execute(['FROM' => 'foo', 'ORDER' => new \Glpi\DBAL\QueryExpression("CASE WHEN `foo` LIKE 'test%' THEN 0 ELSE 1 END")]);
        $this->string($it->getSql())->isIdenticalTo("SELECT * FROM `foo` ORDER BY CASE WHEN `foo` LIKE 'test%' THEN 0 ELSE 1 END");

        $it = $this->it->execute(['FROM' => 'foo', 'ORDER' => [new \Glpi\DBAL\QueryExpression("CASE WHEN `foo` LIKE 'test%' THEN 0 ELSE 1 END"), 'bar ASC']]);
        $this->string($it->getSql())->isIdenticalTo("SELECT * FROM `foo` ORDER BY CASE WHEN `foo` LIKE 'test%' THEN 0 ELSE 1 END, `bar` ASC");

        $it = $this->it->execute(['FROM' => 'foo', 'ORDER' => [new \Glpi\DBAL\QueryExpression("CASE WHEN `foo` LIKE 'test%' THEN 0 ELSE 1 END"), 'bar ASC, baz DESC']]);
        $this->string($it->getSql())->isIdenticalTo("SELECT * FROM `foo` ORDER BY CASE WHEN `foo` LIKE 'test%' THEN 0 ELSE 1 END, `bar` ASC, `baz` DESC");

        $it = $this->it->execute(['FROM' => 'foo', 'ORDER' => [new \Glpi\DBAL\QueryExpression("CASE WHEN `foo` LIKE 'test%' THEN 0 ELSE 1 END"), 'bar ASC', 'baz DESC']]);
        $this->string($it->getSql())->isIdenticalTo("SELECT * FROM `foo` ORDER BY CASE WHEN `foo` LIKE 'test%' THEN 0 ELSE 1 END, `bar` ASC, `baz` DESC");

        $this->exception(
            function () {
                $this->it->execute(['FROM' => 'foo', 'ORDER' => [new \stdClass()]]);
            }
        )->isInstanceOf(\LogicException::class)
         ->hasMessage('Invalid order clause.');
    }


    public function testCount()
    {
        $it = $this->it->execute(['FROM' => 'foo', 'COUNT' => 'cpt']);
        $this->string($it->getSql())->isIdenticalTo('SELECT COUNT(*) AS cpt FROM `foo`');

        $it = $this->it->execute(['FROM' => 'foo', 'COUNT' => 'cpt', 'SELECT' => 'bar', 'DISTINCT' => true]);
        $this->string($it->getSql())->isIdenticalTo('SELECT COUNT(DISTINCT `bar`) AS cpt FROM `foo`');

        $it = $this->it->execute(['FROM' => 'foo', 'COUNT' => 'cpt', 'FIELDS' => ['name', 'version']]);
        $this->string($it->getSql())->isIdenticalTo('SELECT COUNT(*) AS cpt, `name`, `version` FROM `foo`');

        $it = $this->it->execute(['FROM' => 'foo', 'FIELDS' => ['COUNT' => 'bar']]);
        $this->string($it->getSql())->isIdenticalTo('SELECT COUNT(`bar`) FROM `foo`');

        $it = $this->it->execute(['FROM' => 'foo', 'FIELDS' => ['COUNT' => 'bar AS cpt']]);
        $this->string($it->getSql())->isIdenticalTo('SELECT COUNT(`bar`) AS `cpt` FROM `foo`');

        $it = $this->it->execute(['FROM' => 'foo', 'FIELDS' => ['foo.bar', 'COUNT' => 'foo.baz']]);
        $this->string($it->getSql())->isIdenticalTo('SELECT `foo`.`bar`, COUNT(`foo`.`baz`) FROM `foo`');

        $it = $this->it->execute(['FROM' => 'foo', 'FIELDS' => ['COUNT' => ['bar', 'baz']]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT COUNT(`bar`), COUNT(`baz`) FROM `foo`');

        $it = $this->it->execute(['FROM' => 'foo', 'FIELDS' => ['COUNT' => ['bar AS cpt', 'baz AS cpt2']]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT COUNT(`bar`) AS `cpt`, COUNT(`baz`) AS `cpt2` FROM `foo`');

        $it = $this->it->execute(['FROM' => 'foo', 'FIELDS' => ['foo.bar', 'COUNT' => ['foo.baz', 'foo.qux']]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT `foo`.`bar`, COUNT(`foo`.`baz`), COUNT(`foo`.`qux`) FROM `foo`');
    }

    public function testCountDistinct()
    {
        $it = $this->it->execute(['FROM' => 'foo', 'FIELDS' => ['COUNT DISTINCT' => 'bar']]);
        $this->string($it->getSql())->isIdenticalTo('SELECT COUNT(DISTINCT(`bar`)) FROM `foo`');

        $it = $this->it->execute(['FROM' => 'foo', 'FIELDS' => ['COUNT DISTINCT' => ['bar', 'baz']]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT COUNT(DISTINCT(`bar`)), COUNT(DISTINCT(`baz`)) FROM `foo`');

        $it = $this->it->execute(['FROM' => 'foo', 'FIELDS' => ['COUNT DISTINCT' => ['bar AS cpt', 'baz AS cpt2']]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT COUNT(DISTINCT(`bar`)) AS `cpt`, COUNT(DISTINCT(`baz`)) AS `cpt2` FROM `foo`');

        $it = $this->it->execute(['FROM' => 'foo', 'FIELDS' => ['foo.bar', 'COUNT DISTINCT' => ['foo.baz', 'foo.qux']]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT `foo`.`bar`, COUNT(DISTINCT(`foo`.`baz`)), COUNT(DISTINCT(`foo`.`qux`)) FROM `foo`');

        $it = $this->it->execute(['FROM' => 'foo', 'FIELDS' => 'bar', 'COUNT' => 'cpt', 'DISTINCT' => true]);
        $this->string($it->getSql())->isIdenticalTo('SELECT COUNT(DISTINCT `bar`) AS cpt FROM `foo`');

        $this->exception(
            function () {
                $this->it->execute(['FROM' => 'foo', 'COUNT' => 'cpt', 'DISTINCT' => true]);
            }
        )->isInstanceOf(\LogicException::class)
         ->hasMessage("With COUNT and DISTINCT, you must specify exactly one field, or use 'COUNT DISTINCT'.");
    }


    public function testJoins()
    {
        $it = $this->it->execute(['FROM' => 'foo', 'LEFT JOIN' => []]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo`');

        $it = $this->it->execute(['FROM' => 'foo', 'LEFT JOIN' => ['bar' => ['FKEY' => ['bar' => 'id', 'foo' => 'fk']]]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)');

       //old JOIN alias for LEFT JOIN
        $it = $this->it->execute(['FROM' => 'foo', 'JOIN' => ['bar' => ['FKEY' => ['bar' => 'id', 'foo' => 'fk']]]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)');

        $it = $this->it->execute(['FROM' => 'foo', 'LEFT JOIN' => [['TABLE' => 'bar', 'FKEY' => ['bar' => 'id', 'foo' => 'fk']]]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)');

        $it = $this->it->execute(['FROM' => 'foo', 'LEFT JOIN' => ['bar' => ['ON' => ['bar' => 'id', 'foo' => 'fk']]]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)');

        $it = $this->it->execute(
            [
                'FROM' => 'foo',
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
            'SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk`) ' .
            'LEFT JOIN `baz` ON (`baz`.`id` = `foo`.`baz_id`)'
        );

        $it = $this->it->execute(['FROM' => 'foo', 'INNER JOIN' => []]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo`');

        $it = $this->it->execute(['FROM' => 'foo', 'INNER JOIN' => ['bar' => ['FKEY' => ['bar' => 'id', 'foo' => 'fk']]]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` INNER JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)');

        $it = $this->it->execute(['FROM' => 'foo', 'RIGHT JOIN' => []]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo`');

        $it = $this->it->execute(['FROM' => 'foo', 'RIGHT JOIN' => ['bar' => ['FKEY' => ['bar' => 'id', 'foo' => 'fk']]]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` RIGHT JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)');

        $this->exception(
            function () {
                $this->it->execute(['FROM' => 'foo', 'LEFT JOIN' => ['ON' => ['a' => 'id', 'b' => 'a_id']]]);
            }
        )->isInstanceOf(\LogicException::class)
         ->hasMessage('BAD JOIN');


        $this->exception(
            function () {
                $this->it->execute(['FROM' => 'foo', 'LEFT JOIN' => 'bar']);
            }
        )->isInstanceOf(\LogicException::class)
         ->hasMessage('BAD JOIN, value must be [ table => criteria ].');

        $this->exception(
            function () {
                $this->it->execute(['FROM' => 'foo', 'INNER JOIN' => ['bar' => ['FKEY' => 'akey']]]);
            }
        )->isInstanceOf(\LogicException::class)
         ->hasMessage('BAD FOREIGN KEY, should be [ table1 => key1, table2 => key2 ] or [ table1 => key1, table2 => key2, [criteria]].');

       //test conditions
        $it = $this->it->execute(
            [
                'FROM' => 'foo',
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

        $it = $this->it->execute(
            [
                'FROM' => 'foo',
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
            'SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk` AND `field` = \'42\')'
        );

        //order in fkey should not matter
        $it = $this->it->execute(
            [
                'FROM' => 'foo',
                'LEFT JOIN' => [
                    'bar' => [
                        'FKEY' => [
                            [
                                'AND'  => ['field' => 42]
                            ],
                            'bar' => 'id',
                            'foo' => 'fk'
                        ]
                    ]
                ]
            ]
        );
        $this->string($it->getSql())->isIdenticalTo(
            'SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk` AND `field` = \'42\')'
        );

        //condition set as associative array should work also
        $it = $this->it->execute(
            [
                'FROM' => 'foo',
                'LEFT JOIN' => [
                    'bar' => [
                        'FKEY' => [
                            'bar' => 'id',
                            'foo' => 'fk',
                            'acondition' => [
                                'AND'  => ['field' => 42]
                            ]
                        ]
                    ]
                ]
            ]
        );
        $this->string($it->getSql())->isIdenticalTo(
            'SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk` AND `field` = \'42\')'
        );


        //test derived table in JOIN statement
        $it = $this->it->execute(
            [
                'FROM' => 'foo',
                'LEFT JOIN' => [
                    [
                        'TABLE'  => new \Glpi\DBAL\QuerySubQuery(['FROM' => 'bar'], 't2'),
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

    public function testAnalyseJoins()
    {
        $join = $this->it->analyseJoins(['LEFT JOIN' => ['bar' => ['FKEY' => ['bar' => 'id', 'foo' => 'fk']]]]);
        $this->string($join)->isIdenticalTo(' LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)');

        $this->exception(
            function () {
                $this->it->analyseJoins(['LEFT OUTER JOIN' => ['ON' => ['a' => 'id', 'b' => 'a_id']]]);
            }
        )->isInstanceOf(\LogicException::class)
         ->hasMessage('Invalid JOIN type `LEFT OUTER JOIN`.');

        // QueryExpression
        $expression = "LEFT JOIN xxxx";
        $join = $this->it->analyseJoins(['LEFT JOIN' => [new \Glpi\DBAL\QueryExpression($expression)]]);
        $this->string($join)->isIdenticalTo($expression);
    }

    public function testHaving()
    {
        $it = $this->it->execute(['FROM' => 'foo', 'HAVING' => ['bar' => 1]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` HAVING `bar` = \'1\'');

        $it = $this->it->execute(['FROM' => 'foo', 'HAVING' => ['bar' => ['>', 0]]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` HAVING `bar` > \'0\'');
    }



    public function testOperators()
    {
        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['a' => 1]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` = \'1\'');

        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['a' => ['=', 1]]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` = \'1\'');

        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['a' => ['>', 1]]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` > \'1\'');

        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['a' => ['LIKE', '%bar%']]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` LIKE \'%bar%\'');

        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['NOT' => ['a' => ['LIKE', '%bar%']]]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE NOT (`a` LIKE \'%bar%\')');

        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['a' => ['NOT LIKE', '%bar%']]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` NOT LIKE \'%bar%\'');

        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['a' => ['<>', 1]]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` <> \'1\'');

        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['a' => ['&', 1]]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` & \'1\'');

        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['a' => ['|', 1]]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `a` | \'1\'');
    }


    public function testWhere()
    {
        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => 'id=1']);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE id=1');

        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['bar' => null]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `bar` IS NULL');

        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['bar' => null]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `bar` IS NULL');

        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['`bar`' => null]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `bar` IS NULL');

        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['bar' => 1]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `bar` = \'1\'');

        $this->exception(
            function () {
                $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['bar' => []]]);
            }
        )
         ->isInstanceOf('RuntimeException')
         ->hasMessage('Empty IN are not allowed');

        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['bar' => [1, 2, 4]]]);
        $this->string($it->getSql())->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` IN ('1', '2', '4')");

        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['bar' => ['a', 'b', 'c']]]);
        $this->string($it->getSql())->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` IN ('a', 'b', 'c')");

        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['bar' => 'val']]);
        $this->string($it->getSql())->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` = 'val'");

        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['bar' => new \Glpi\DBAL\QueryExpression('`field`')]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `bar` = `field`');

        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['bar' => '?']]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `bar` = \'?\'');

        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['bar' => new \Glpi\DBAL\QueryParam()]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `bar` = ?');

        /*$it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['bar' => new \QueryParam('myparam')]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE `bar` = :myparam');*/
    }


    public function testFkey()
    {

        $it = $this->it->execute(['FROM' => ['foo', 'bar'], 'WHERE' => ['FKEY' => ['id', 'fk']]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo`, `bar` WHERE `id` = `fk`');

        $it = $this->it->execute(['FROM' => ['foo', 'bar'], 'WHERE' => ['FKEY' => ['foo' => 'id', 'bar' => 'fk']]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo`, `bar` WHERE `foo`.`id` = `bar`.`fk`');

        $it = $this->it->execute(['FROM' => ['foo', 'bar'], 'WHERE' => ['FKEY' => ['`foo`' => 'id', 'bar' => '`fk`']]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo`, `bar` WHERE `foo`.`id` = `bar`.`fk`');
    }

    public function testGroupBy()
    {

        $it = $this->it->execute(['FROM' => 'foo', 'GROUPBY' => ['id']]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` GROUP BY `id`');

        $it = $this->it->execute(['FROM' => 'foo', 'GROUP' => ['id']]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` GROUP BY `id`');

        $it = $this->it->execute(['FROM' => 'foo', 'GROUPBY' => 'id']);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` GROUP BY `id`');

        $it = $this->it->execute(['FROM' => 'foo', 'GROUP' => 'id']);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` GROUP BY `id`');

        $it = $this->it->execute(['FROM' => 'foo', 'GROUPBY' => ['id', 'name']]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` GROUP BY `id`, `name`');

        $it = $this->it->execute(['FROM' => 'foo', 'GROUP' => ['id', 'name']]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` GROUP BY `id`, `name`');
    }

    public function testNoFieldGroupBy()
    {
        $this->exception(
            function () {
                $it = $this->it->execute(['FROM' => 'foo', 'GROUPBY' => []]);
                $this->string('SELECT * FROM `foo`', $it->getSql(), 'No group by field');
            }
        )->isInstanceOf(\LogicException::class)
         ->hasMessage('Missing group by field.');

        $this->exception(
            function () {
                $it = $this->it->execute(['FROM' => 'foo', 'GROUP' => []]);
                $this->string('SELECT * FROM `foo`', $it->getSql(), 'No group by field');
            }
        )->isInstanceOf(\LogicException::class)
         ->hasMessage('Missing group by field.');
    }

    public function testRange()
    {

        $it = $this->it->execute(['FROM' => 'foo', 'START' => 5, 'LIMIT' => 10]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` LIMIT 10 OFFSET 5');

        $it = $this->it->execute(['FROM' => 'foo', 'OFFSET' => 5, 'LIMIT' => 10]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` LIMIT 10 OFFSET 5');
    }


    public function testLogical()
    {
        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => [['a' => 1, 'b' => 2]]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE (`a` = \'1\' AND `b` = \'2\')');

        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['AND' => ['a' => 1, 'b' => 2]]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE (`a` = \'1\' AND `b` = \'2\')');

        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['OR' => ['a' => 1, 'b' => 2]]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE (`a` = \'1\' OR `b` = \'2\')');

        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['NOT' => ['a' => 1, 'b' => 2]]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE NOT (`a` = \'1\' AND `b` = \'2\')');

        $crit = [
            'FROM' => 'foo',
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
        $sql = "SELECT * FROM `foo` WHERE ((`items_id` = '15' AND `itemtype` = 'Computer') OR (`items_id` = '3' AND `itemtype` = 'Document'))";
        $it = $this->it->execute($crit);
        $this->string($it->getSql())->isIdenticalTo($sql);

        $crit = [
            'FROM' => 'foo',
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
        $sql = "SELECT * FROM `foo` WHERE `a` = '1' AND (`b` = '2' OR NOT (`c` IN ('2', '3') AND (`d` = '4' AND `e` = '5')))";
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


    public function testModern()
    {
        $req = [
            'SELECT' => ['a', 'b'],
            'FROM'   => 'foo',
            'WHERE'  => ['c' => 1],
        ];
        $sql = "SELECT `a`, `b` FROM `foo` WHERE `c` = '1'";
        $it = $this->it->execute($req);
        $this->string($it->getSql())->isIdenticalTo($sql);
    }


    public function testRows()
    {
        global $DB;

        $it = $this->it->execute(['FROM' => 'foo']);
        $this->integer($it->numrows())->isIdenticalTo(0);
        $this->integer(count($it))->isIdenticalTo(0);
        $this->variable($it->current())->isNull();

        $it = $DB->request(['FROM' => 'glpi_configs', 'WHERE' => ['context' => 'core', 'name' => 'version']]);
        $this->integer($it->numrows())->isIdenticalTo(1);
        $this->integer(count($it))->isIdenticalTo(1);
        $row = $it->current();
        $key = $it->key();
        $this->integer($row['id'])->isIdenticalTo($key);

        $it = $DB->request(['FROM' => 'glpi_configs', 'WHERE' => ['context' => 'core']]);
        $this->integer($it->numrows())->isGreaterThan(100);
        $this->integer(count($it))->isGreaterThan(100);
        $this->boolean($it->numrows() == count($it))->isTrue();
    }

    public function testAlias()
    {
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

        $it = $this->it->execute([
            'SELECT' => ['id', 'field  AS  f', 'baz as  Z'],
            'FROM' => 'bar  AS b',
            'INNER JOIN'   => [
                'foo AS  f' => [
                    'FKEY' => [
                        'b'   => 'fid',
                        'f'   => 'id'
                    ]
                ]
            ]
        ]);
        $this->string($it->getSql())->isIdenticalTo('SELECT `id`, `field` AS `f`, `baz` AS `Z` FROM `bar` AS `b` INNER JOIN `foo` AS `f` ON (`b`.`fid` = `f`.`id`)');
    }

    public function testExpression()
    {
        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => [new \Glpi\DBAL\QueryExpression('a LIKE b')]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `foo` WHERE a LIKE b');

        $it = $this->it->execute(['FROM' => 'foo', 'FIELDS' => ['b' => 'bar', '`c`' => '`baz`', new \Glpi\DBAL\QueryExpression('1 AS `myfield`')]]);
        $this->string($it->getSql())->isIdenticalTo('SELECT `b`.`bar`, `c`.`baz`, 1 AS `myfield` FROM `foo`');
    }

    public function testSubQuery()
    {
        $crit = ['SELECT' => 'id', 'FROM' => 'baz', 'WHERE' => ['z' => 'f']];
        $raw_subq = "(SELECT `id` FROM `baz` WHERE `z` = 'f')";

        $sub_query = new \Glpi\DBAL\QuerySubQuery($crit);
        $this->string($sub_query->getQuery())->isIdenticalTo($raw_subq);

        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['bar' => $sub_query]]);
        $this->string($it->getSql())
           ->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` IN $raw_subq");

        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['bar' => ['<>', $sub_query]]]);
        $this->string($it->getSql())
           ->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` <> $raw_subq");

        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['NOT' => ['bar' => $sub_query]]]);
        $this->string($it->getSql())
           ->isIdenticalTo("SELECT * FROM `foo` WHERE NOT (`bar` IN $raw_subq)");

        $sub_query = new \Glpi\DBAL\QuerySubQuery($crit, 'thesubquery');
        $this->string($sub_query->getQuery())->isIdenticalTo("$raw_subq AS `thesubquery`");

        $it = $this->it->execute(['FROM' => 'foo', 'WHERE' => ['bar' => $sub_query]]);
        $this->string($it->getSql())
           ->isIdenticalTo("SELECT * FROM `foo` WHERE `bar` IN $raw_subq AS `thesubquery`");

        $it = $this->it->execute([
            'SELECT' => ['bar', $sub_query],
            'FROM'   => 'foo'
        ]);
        $this->string($it->getSql())
           ->isIdenticalTo("SELECT `bar`, $raw_subq AS `thesubquery` FROM `foo`");
    }

    public function testUnionQuery()
    {
        $union_crit = [
            ['FROM' => 'table1'],
            ['FROM' => 'table2']
        ];
        $union = new \Glpi\DBAL\QueryUnion($union_crit);
        $union_raw_query = '((SELECT * FROM `table1`) UNION ALL (SELECT * FROM `table2`))';
        $raw_query = 'SELECT * FROM ' . $union_raw_query . ' AS `union_' . md5($union_raw_query) . '`';
        $it = $this->it->execute(['FROM' => $union]);
        $this->string($it->getSql())->isIdenticalTo($raw_query);

        $union = new \Glpi\DBAL\QueryUnion($union_crit, true);
        $union_raw_query = '((SELECT * FROM `table1`) UNION (SELECT * FROM `table2`))';
        $raw_query = 'SELECT * FROM ' . $union_raw_query . ' AS `union_' . md5($union_raw_query) . '`';
        $it = $this->it->execute(['FROM' => $union]);
        $this->string($it->getSql())->isIdenticalTo($raw_query);

        $union = new \Glpi\DBAL\QueryUnion($union_crit, false, 'theunion');
        $raw_query = 'SELECT * FROM ((SELECT * FROM `table1`) UNION ALL (SELECT * FROM `table2`)) AS `theunion`';
        $it = $this->it->execute(['FROM' => $union]);
        $this->string($it->getSql())->isIdenticalTo($raw_query);

        $union = new \Glpi\DBAL\QueryUnion($union_crit, false, 'theunion');
        $raw_query = 'SELECT DISTINCT `theunion`.`field` FROM ((SELECT * FROM `table1`) UNION ALL (SELECT * FROM `table2`)) AS `theunion`';
        $crit = [
            'SELECT'    => 'theunion.field',
            'DISTINCT'  => true,
            'FROM'      => $union,
        ];
        $it = $this->it->execute($crit);
        $this->string($it->getSql())->isIdenticalTo($raw_query);

        $union = new \Glpi\DBAL\QueryUnion($union_crit, true);
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

    public function testComplexUnionQuery()
    {

        $fk = \Ticket::getForeignKeyField();
        $users_table = \User::getTable();
        $users_table = 'glpi_ticket_users';
        $groups_table = 'glpi_groups_tickets';

        $subquery1 = new \Glpi\DBAL\QuerySubQuery([
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
        $subquery2 = new \Glpi\DBAL\QuerySubQuery([
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
                     . " WHERE `tu`.`$fk` = '42')"
                     . " UNION ALL"
                     . " (SELECT `usr`.`id` AS `users_id`, `gt`.`type` AS `type`"
                     . " FROM `$groups_table` AS `gt`"
                     . " LEFT JOIN `glpi_groups_users` AS `gu` ON (`gu`.`groups_id` = `gt`.`groups_id`)"
                     . " LEFT JOIN `glpi_users` AS `usr` ON (`gu`.`users_id` = `usr`.`id`)"
                     . " WHERE `gt`.`$fk` = '42')"
                     . ") AS `allactors`";

        $union = new \Glpi\DBAL\QueryUnion([$subquery1, $subquery2], false, 'allactors');
        $it = $this->it->execute([
            'FIELDS'          => [
                'users_id',
                'type'
            ],
            'DISTINCT'        => true,
            'FROM'            => $union
        ]);
        $this->string($it->getSql())->isIdenticalTo($raw_query);
    }

    public function testComplexUnionQueryAgain()
    {
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
                                                            AND `ADDR`.`itemtype` = 'NetworkName'
                                                            AND `ADDR`.`is_deleted` = '0')
                        INNER JOIN `glpi_networknames` AS `NAME` ON (`NAME`.`id` = `ADDR`.`items_id`
                                                               AND `NAME`.`itemtype` = 'NetworkPort')
                        INNER JOIN `glpi_networkports` AS `PORT` ON (`NAME`.`items_id` = `PORT`.`id`
                                                               AND `PORT`.`itemtype` = '$itemtype')
                        INNER JOIN `$table` AS `ITEM` ON (`ITEM`.`id` = `PORT`.`items_id`)
                        LEFT JOIN `glpi_entities` ON (`ADDR`.`entities_id` = `glpi_entities`.`id`)
                        WHERE `LINK`.`ipnetworks_id` = '42')";
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
                                                         AND `ADDR`.`itemtype` = 'NetworkName'
                                                         AND `ADDR`.`is_deleted` = '0')
                     INNER JOIN `glpi_networknames` AS `NAME` ON (`NAME`.`id` = `ADDR`.`items_id`
                                                            AND `NAME`.`itemtype` = 'NetworkPort')
                     INNER JOIN `glpi_networkports` AS `PORT`
                        ON (`NAME`.`items_id` = `PORT`.`id`
                             AND NOT (`PORT`.`itemtype`
                                      IN ('" . implode("', '", $CFG_GLPI["networkport_types"]) . "')))
                     LEFT JOIN `glpi_entities` ON (`ADDR`.`entities_id` = `glpi_entities`.`id`)
                     WHERE `LINK`.`ipnetworks_id` = '42')";

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
                                                         AND `ADDR`.`itemtype` = 'NetworkName'
                                                         AND `ADDR`.`is_deleted` = '0')
                     INNER JOIN `glpi_networknames` AS `NAME` ON (`NAME`.`id` = `ADDR`.`items_id`
                                                            AND `NAME`.`itemtype` != 'NetworkPort')
                     LEFT JOIN `glpi_entities` ON (`ADDR`.`entities_id` = `glpi_entities`.`id`)
                     WHERE `LINK`.`ipnetworks_id` = '42')";

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
                                                         AND `ADDR`.`itemtype` != 'NetworkName'
                                                         AND `ADDR`.`is_deleted` = '0')
                     LEFT JOIN `glpi_entities` ON (`ADDR`.`entities_id` = `glpi_entities`.`id`)
                     WHERE `LINK`.`ipnetworks_id` = '42')";

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
                new \Glpi\DBAL\QueryExpression("'$itemtype' AS " . $DB->quoteName('item_type'))
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
            new \Glpi\DBAL\QueryExpression('NULL AS ' . $DB->quoteName('item_id')),
            new \Glpi\DBAL\QueryExpression("NULL AS " . $DB->quoteName('item_type')),
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
                            'NOT' => [
                                'PORT.itemtype' => $CFG_GLPI['networkport_types']
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $queries[] = $criteria;

        $criteria = $main_criteria;
        $criteria['SELECT'] = array_merge($criteria['SELECT'], [
            'NAME.id AS name_id',
            new \Glpi\DBAL\QueryExpression("NULL AS " . $DB->quoteName('port_id')),
            new \Glpi\DBAL\QueryExpression('NULL AS ' . $DB->quoteName('item_id')),
            new \Glpi\DBAL\QueryExpression("NULL AS " . $DB->quoteName('item_type'))
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
            new \Glpi\DBAL\QueryExpression("NULL AS " . $DB->quoteName('name_id')),
            new \Glpi\DBAL\QueryExpression("NULL AS " . $DB->quoteName('port_id')),
            new \Glpi\DBAL\QueryExpression('NULL AS ' . $DB->quoteName('item_id')),
            new \Glpi\DBAL\QueryExpression("NULL AS " . $DB->quoteName('item_type'))
        ]);
        $criteria['INNER JOIN']['glpi_ipaddresses AS ADDR']['ON'][0]['AND']['ADDR.itemtype'] = ['!=', 'NetworkName'];
        $queries[] = $criteria;

        $union = new \Glpi\DBAL\QueryUnion($queries);
        $criteria = [
            'FROM'   => $union,
        ];

        $it = $this->it->execute($criteria);
        $this->string($it->getSql())->isIdenticalTo($raw_query);
    }

    public function testAnalyseCrit()
    {
        $crit = [new \Glpi\DBAL\QuerySubQuery([
            'SELECT' => ['COUNT' => ['users_id']],
            'FROM'   => 'glpi_groups_users',
            'WHERE'  => ['groups_id' => new \Glpi\DBAL\QueryExpression('glpi_groups.id')]
        ])
        ];
        $this->string($this->it->analyseCrit($crit))->isIdenticalTo("(SELECT COUNT(`users_id`) FROM `glpi_groups_users` WHERE `groups_id` = glpi_groups.id)");
    }

    public function testIteratorKeyWithId()
    {
        global $DB;

       // Select "id" field, keys will correspond to ids
        $iterator = $DB->request(
            [
                'SELECT' => ['id', 'name'],
                'FROM'   => $this->getUsersFakeTable()
            ]
        );

        $this->integer($iterator->key())->isEqualTo($iterator->current()['id']);
        $this->integer($iterator->key())->isEqualTo($iterator->current()['id']); // Calling key() twice should produce the same result (does not move the pointer)
        $iterator->next();
        $this->integer($iterator->key())->isEqualTo($iterator->current()['id']);
        $iterator->next();
        $this->integer($iterator->key())->isEqualTo($iterator->current()['id']);
        $iterator->next();
        $this->variable($iterator->key())->isEqualTo(null); // Out of bounds, returns null
    }

    public function testIteratorKeyWithoutId()
    {
        global $DB;

       // Do not select "id" field, keys will be numeric, starting at 0
        $iterator = $DB->request(
            [
                'SELECT' => 'name',
                'FROM'   => $this->getUsersFakeTable()
            ]
        );

        $this->integer($iterator->key())->isEqualTo(0);
        $this->integer($iterator->key())->isEqualTo(0); // Calling key() twice should produce the same result (does not move the pointer)
        $iterator->next();
        $this->integer($iterator->key())->isEqualTo(1);
        $iterator->next();
        $this->integer($iterator->key())->isEqualTo(2);
        $iterator->next();
        $this->variable($iterator->key())->isEqualTo(null); // Out of bounds, returns null
    }

    public function testIteratorCurrent()
    {
        global $DB;

        $iterator = $DB->request(
            [
                'SELECT' => ['id', 'name'],
                'FROM'   => $this->getUsersFakeTable()
            ]
        );

        $this->array($iterator->current())->isEqualTo(['id' => 2, 'name' => 'jdoe']);
        $this->array($iterator->current())->isEqualTo(['id' => 2, 'name' => 'jdoe']); // Calling current() twice should produce the same result (does not move the pointer)
        $iterator->next();
        $this->array($iterator->current())->isEqualTo(['id' => 5, 'name' => 'psmith']);
        $iterator->next();
        $this->array($iterator->current())->isEqualTo(['id' => 6, 'name' => 'acain']);
        $iterator->next();
        $this->variable($iterator->current())->isEqualTo(null); // Out of bounds, returns null
    }

    public function testIteratorCount()
    {
        global $DB;

        $iterator = $DB->request(
            [
                'SELECT' => ['name'],
                'FROM'   => $this->getUsersFakeTable()
            ]
        );

        $this->integer($iterator->count())->isEqualTo(3);
    }

    public function testIteratorValid()
    {
        global $DB;

        $iterator = $DB->request(
            [
                'SELECT' => ['name'],
                'FROM'   => $this->getUsersFakeTable()
            ]
        );

        for ($i = 0; $i < $iterator->count(); $i++) {
            $this->boolean($iterator->valid())->isTrue();
            $iterator->next(); // Iterate until out of bounds
        }
        $this->boolean($iterator->valid())->isFalse();

        $iterator->rewind();
        $this->boolean($iterator->valid())->isTrue();
    }

    public function testIteratorRewind()
    {
        global $DB;

        $iterator = $DB->request(
            [
                'SELECT' => ['name'],
                'FROM'   => $this->getUsersFakeTable()
            ]
        );

        for ($i = 0; $i < $iterator->count(); $i++) {
            $this->integer($iterator->key())->isEqualTo($i);
            $iterator->next(); // Iterate until out of bounds
        }
        $this->variable($iterator->key())->isNull();

        $iterator->rewind();
        $this->integer($iterator->key())->isEqualTo(0);
    }

    public function testIteratorSeek()
    {
        global $DB;

        $iterator = $DB->request(
            [
                'SELECT' => ['id', 'name'],
                'FROM'   => $this->getUsersFakeTable()
            ]
        );

        $iterator->seek(2);
        $this->array($iterator->current())->isEqualTo(['id' => 6, 'name' => 'acain']);

        $iterator->seek(0);
        $this->array($iterator->current())->isEqualTo(['id' => 2, 'name' => 'jdoe']);

        $iterator->seek(1);
        $this->array($iterator->current())->isEqualTo(['id' => 5, 'name' => 'psmith']);

        $this->exception(
            function () use ($iterator) {
                $iterator->seek(3);
            }
        )->isInstanceOf('OutOfBoundsException');
    }

    /**
     * Returns a fake users table that can be used to test iterator.
     *
     * @return \Glpi\DBAL\QueryExpression
     */
    private function getUsersFakeTable(): \Glpi\DBAL\QueryExpression
    {
        global $DB;

        $user_pattern = '(SELECT %1$d AS %2$s, %3$s as %4$s)';
        $users_table = [
            sprintf($user_pattern, 2, $DB->quoteName('id'), $DB->quoteValue('jdoe'), $DB->quoteName('name')),
            sprintf($user_pattern, 5, $DB->quoteName('id'), $DB->quoteValue('psmith'), $DB->quoteName('name')),
            sprintf($user_pattern, 6, $DB->quoteName('id'), $DB->quoteValue('acain'), $DB->quoteName('name')),
        ];

        return new \Glpi\DBAL\QueryExpression('(' . implode(' UNION ALL ', $users_table) . ') AS users');
    }

    public function testInCriteria()
    {
        global $DB;
        $iterator = new \DBmysqlIterator($DB);
        $to_sql_array = static function ($values) use ($DB) {
            $str = '(';
            foreach ($values as $value) {
                $str .= $DB->quoteValue($value) . ', ';
            }
            return rtrim($str, ', ') . ')';
        };

        // Reguar IN
        $criteria = [
            'id' => [1, 2, 3]
        ];
        $expected = $DB::quoteName('id') . " IN " . $to_sql_array($criteria['id']);
        $this->string($iterator->analyseCrit($criteria))->isEqualTo($expected);

        // Explicit IN (array form)
        $criteria = [
            'id' => ['IN', [1, 2, 3]]
        ];
        $expected = $DB::quoteName('id') . " IN " . $to_sql_array($criteria['id'][1]);
        $this->string($iterator->analyseCrit($criteria))->isEqualTo($expected);

        // Explicit NOT IN (array form)
        $criteria = [
            'id' => ['NOT IN', [1, 2, 3]]
        ];
        $expected = $DB::quoteName('id') . " NOT IN " . $to_sql_array($criteria['id'][1]);
        $this->string($iterator->analyseCrit($criteria))->isEqualTo($expected);
    }

    protected function resultProvider(): iterable
    {
        // Data from GLPI 9.5- (autosanitized)
        yield [
            'db_data' => [
                'id'      => 1,
                'name'    => 'A&B',
                'content' => '&lt;p&gt;Test&lt;/p&gt;',
            ],
            'result'  => [
                'id'      => 1,
                'name'    => 'A&B',
                'content' => '<p>Test</p>',
            ]
        ];

        // Data from GLPI 10.0.x (autosanitized)
        yield [
            'db_data' => [
                'id'      => 1,
                'name'    => 'A&#38;B',
                'content' => '&#60;p&#62;Test&#60;/p&#62;',
            ],
            'result'  => [
                'id'      => 1,
                'name'    => 'A&B',
                'content' => '<p>Test</p>',
            ]
        ];

        // Data from GLPI 11.0+ (not autosanitized)
        yield [
            'db_data' => [
                'id'      => 1,
                'name'    => 'A&B',
                'content' => '<p>Test</p>',
            ],
            'result'  => [
                'id'      => 1,
                'name'    => 'A&B',
                'content' => '<p>Test</p>',
            ]
        ];
    }

    /**
     * @dataProvider resultProvider
     */
    public function testAutoUnsanitize(array $db_data, array $result): void
    {

        $this->mockGenerator->orphanize('__construct');
        $mysqli_result = new \mock\mysqli_result();
        $this->calling($mysqli_result)->fetch_assoc = $db_data;
        $this->calling($mysqli_result)->data_seek   = true;
        $this->calling($mysqli_result)->free        = true;

        $this->mockGenerator->orphanize('__construct');
        $db = new \mock\DBMysql();
        $this->calling($db)->doQuery = $mysqli_result;
        $this->calling($db)->numrows = 1;

        $iterator = $db->request(['FROM' => 'glpi_mocks']);

        $this->array($iterator->current())->isEqualTo($result);
    }

    public function testRawFKeyCondition()
    {
        $this->string(
            $this->it->analyseCrit([
                'ON' => new \Glpi\DBAL\QueryExpression("glpi_tickets.id=(CASE WHEN glpi_tickets_tickets.tickets_id_1=103 THEN glpi_tickets_tickets.tickets_id_2 ELSE glpi_tickets_tickets.tickets_id_1 END)")
            ])
        )->isEqualTo("glpi_tickets.id=(CASE WHEN glpi_tickets_tickets.tickets_id_1=103 THEN glpi_tickets_tickets.tickets_id_2 ELSE glpi_tickets_tickets.tickets_id_1 END)");
    }

    protected function requestArgsProvider(): iterable
    {
        // Table name as first param, default value for criteria argument
        yield [
            'params'   => ['glpi_computers', ''],
            'expected' => [
                'criteria' => [
                    'FROM' => 'glpi_computers',
                ],
                'debug'    => false,
            ],
            'sql'      => 'SELECT * FROM `glpi_computers`',
        ];

        // Table name as first param, criteria as a string
        yield [
            'params'   => ['glpi_computers', 'is_deleted = 0'],
            'expected' => [
                'criteria' => [
                    'FROM'  => 'glpi_computers',
                    'WHERE' => [new QueryExpression('is_deleted = 0')]
                ],
                'debug'    => false,
            ],
            'sql'      => 'SELECT * FROM `glpi_computers` WHERE is_deleted = 0',
        ];

        // Table name as first param, criteria as an array
        yield [
            'params'   => ['glpi_computers', ['WHERE' => ['is_deleted' => 0], 'ORDER' => 'id DESC']],
            'expected' => [
                'criteria' => [
                    'FROM'  => 'glpi_computers',
                    'WHERE' => ['is_deleted' => 0],
                    'ORDER' => 'id DESC'
                ],
                'debug'    => false,
            ],
            'sql'      => 'SELECT * FROM `glpi_computers` WHERE `is_deleted` = \'0\' ORDER BY `id` DESC',
        ];

        // Table name as first param, criteria as an array but not encapsulated inside a `WHERE` key
        yield [
            'params'   => ['glpi_computers', ['is_deleted' => 1]],
            'expected' => [
                'criteria' => [
                    'FROM'  => 'glpi_computers',
                    'is_deleted' => 1,
                ],
                'debug'    => false,
            ],
            'sql'      => 'SELECT * FROM `glpi_computers` WHERE `is_deleted` = \'1\'',
        ];

        // First argument is a QueryUnion
        $union = new QueryUnion(
            [
                ['SELECT' => 'serial', 'FROM' => 'glpi_computers'],
                ['SELECT' => 'serial', 'FROM' => 'glpi_printers']
            ],
            false,
            'testalias'
        );
        yield [
            'params'   => [$union, ''],
            'expected' => [
                'criteria' => [
                    'FROM'  => $union,
                ],
                'debug'    => false,
            ],
            'sql'      => 'SELECT * FROM ((SELECT `serial` FROM `glpi_computers`) UNION ALL (SELECT `serial` FROM `glpi_printers`)) AS `testalias`',
        ];
    }

    /**
     * @dataProvider requestArgsProvider
     */
    public function testConvertOldRequestArgsToCriteria(array $params, array $expected, string $sql): void
    {
        $this->mockGenerator->orphanize('__construct');
        $db = new \mock\DBMysql();

        $iterator = new \DBmysqlIterator($db);

        $result = null;
        $this->when(
            function () use ($iterator, $params, &$result) {
                $result = $this->callPrivateMethod($iterator, 'convertOldRequestArgsToCriteria', $params, 'test');
            }
        )
         ->error
         ->withMessage('The `test()` method signature changed. Its previous signature is deprecated.')
         ->withType(E_USER_DEPRECATED)
         ->exists();

        $this->array($result)->isEqualTo($expected);
    }

    /**
     * @dataProvider requestArgsProvider
     */
    public function testExecuteWithOldSignature(array $params, array $expected, string $sql): void
    {
        $this->mockGenerator->orphanize('__construct');
        $mysqli_result = new \mock\mysqli_result();
        $this->calling($mysqli_result)->fetch_assoc = [];
        $this->calling($mysqli_result)->data_seek   = true;
        $this->calling($mysqli_result)->free        = true;

        $this->mockGenerator->orphanize('__construct');
        $db = new \mock\DBMysql();
        $this->calling($db)->doQuery = $mysqli_result;
        $this->calling($db)->numrows = 1;

        $iterator = new \DBmysqlIterator($db);
        $this->when(
            function () use ($iterator, $params) {
                $iterator = $iterator->execute(...$params);
            }
        )
         ->error
         ->withMessage('The `DBmysqlIterator::execute()` method signature changed. Its previous signature is deprecated.')
         ->withType(E_USER_DEPRECATED)
         ->exists();

        $this->string($iterator->getSql())->isEqualTo($sql);
    }

    public function testExecuteWithRawDirectQuery(): void
    {
        $this->mockGenerator->orphanize('__construct');
        $db = new \mock\DBMysql();

        $iterator = new \DBmysqlIterator($db);
        $this->exception(
            function () use ($iterator) {
                $iterator->execute('SELECT * FROM `glpi_computers`');
            }
        )
         ->message->isEqualTo('Building and executing raw queries with the `DBmysqlIterator::execute()` method is prohibited.');
    }

    /**
     * @dataProvider requestArgsProvider
     */
    public function testBuildQueryWithOldSignature(array $params, array $expected, string $sql): void
    {
        $this->mockGenerator->orphanize('__construct');
        $db = new \mock\DBMysql();

        $iterator = new \DBmysqlIterator($db);
        $this->when(
            function () use ($iterator, $params) {
                $iterator = $iterator->buildQuery(...$params);
            }
        )
         ->error
         ->withMessage('The `DBmysqlIterator::buildQuery()` method signature changed. Its previous signature is deprecated.')
         ->withType(E_USER_DEPRECATED)
         ->exists();

        $this->string($iterator->getSql())->isEqualTo($sql);
    }

    public function testBuildQueryWithRawDirectQuery(): void
    {
        $this->mockGenerator->orphanize('__construct');
        $db = new \mock\DBMysql();

        $iterator = new \DBmysqlIterator($db);
        $this->exception(
            function () use ($iterator) {
                $iterator->buildQuery('SELECT * FROM `glpi_computers`');
            }
        )
         ->message->isEqualTo('Building and executing raw queries with the `DBmysqlIterator::buildQuery()` method is prohibited.');
    }
}
