<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Monolog\Logger;
use Psr\Log\LogLevel;
use QueryExpression;

class DBmysqlIteratorTest extends DbTestCase
{
    /** @var \DBmysqlIterator */
    private \DBmysqlIterator $it;

    public function setUp(): void
    {
        parent::setUp();
        $this->it = new \DBmysqlIterator(null);
    }

    public function testDirectQuery(): void
    {
        $req = 'SELECT Something FROM Somewhere';
        $this->it->execute($req);
        $this->hasPhpLogRecordThatContains('Direct query usage is strongly discouraged!', Logger::NOTICE);
        $this->assertStringContainsString($req, $this->it->getSql());

        $req = 'SELECT @@sql_mode as mode';
        $this->it->execute($req);
        $this->hasPhpLogRecordThatContains('Direct query usage is strongly discouraged!', Logger::NOTICE);
        $this->assertStringContainsString($req, $this->it->getSql());
    }

    public static function legacyQueryProvider(): iterable
    {
        yield [
            'input'  => 'SELECT * FROM glpi_computers',
            'output' => 'SELECT * FROM glpi_computers',
        ];

        yield [
            'input'  => <<<SQL
                SELECT * FROM glpi_computers
SQL,
            'output' => ' SELECT * FROM glpi_computers',
        ];
    }

    /**
     * @dataProvider legacyQueryProvider
     */
    public function testBuildQueryLegacy(string $input, string $output): void
    {
        $this->it->buildQuery($input);
        $this->hasPhpLogRecordThatContains('Direct query usage is strongly discouraged!', Logger::NOTICE);
        $this->assertStringContainsString($output, $this->it->getSql());
    }

    public function testSqlError()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $expected_error = "Table '{$DB->dbdefault}.fakeTable' doesn't exist";
        $DB->request('fakeTable');
        $this->hasSqlLogRecordThatContains($expected_error, LogLevel::ERROR);
    }


    public function testOnlyTable()
    {
        $it = $this->it->execute('foo');
        $this->assertSame('SELECT * FROM `foo`', $it->getSql());

        $it = $this->it->execute('`foo`');
        $this->assertSame('SELECT * FROM `foo`', $it->getSql());

        $it = $this->it->execute(['foo', '`bar`']);
        $this->assertSame('SELECT * FROM `foo`, `bar`', $it->getSql());
    }


    /**
     * This is really an error, no table but a WHERE clause
     */
    public function testNoTableWithWhere()
    {
        $this->expectExceptionObject(new \LogicException('Missing table name.'));
        $this->it->execute('', ['foo' => 1]);
    }


    /**
     * Temporarily, this is an error, will be allowed later
     */
    public function testNoTableWithoutWhere()
    {
        $this->expectExceptionObject(new \LogicException('Missing table name.'));
        $this->it->execute('');
    }


    /**
     * Temporarily, this is an error, will be allowed later
     */
    public function testNoTableWithoutWhereBis()
    {
        $this->expectExceptionObject(new \LogicException('Missing table name.'));
        $this->it->execute(['FROM' => []]);
    }

    public function testDebug()
    {
        //Defining the following constant should produce the same, but this is not testable.
        //define('GLPI_SQL_DEBUG', true);

        $id = mt_rand();
        $this->it->execute('foo', ['FIELDS' => 'name', 'id = ' . $id], true);

        $this->hasSqlLogRecordThatContains(
            'Generated query: SELECT `name` FROM `foo` WHERE (id = ' . $id . ')',
            LogLevel::DEBUG
        );
    }

    public function testFields()
    {
        $it = $this->it->execute('foo', ['FIELDS' => 'bar', 'DISTINCT' => true]);
        $this->assertSame('SELECT DISTINCT `bar` FROM `foo`', $it->getSql());

        $it = $this->it->execute('foo', ['FIELDS' => ['bar', 'baz'], 'DISTINCT' => true]);
        $this->assertSame('SELECT DISTINCT `bar`, `baz` FROM `foo`', $it->getSql());

        $it = $this->it->execute('foo', ['FIELDS' => 'bar']);
        $this->assertSame('SELECT `bar` FROM `foo`', $it->getSql());

        $it = $this->it->execute('foo', ['FIELDS' => ['bar', '`baz`']]);
        $this->assertSame('SELECT `bar`, `baz` FROM `foo`', $it->getSql());

        $it = $this->it->execute('foo', ['FIELDS' => ['b' => 'bar']]);
        $this->assertSame('SELECT `b`.`bar` FROM `foo`', $it->getSql());

        $it = $this->it->execute('foo', ['FIELDS' => ['b' => 'bar', '`c`' => '`baz`']]);
        $this->assertSame('SELECT `b`.`bar`, `c`.`baz` FROM `foo`', $it->getSql());

        $it = $this->it->execute('foo', ['FIELDS' => ['a' => ['`bar`', 'baz']]]);
        $this->assertSame('SELECT `a`.`bar`, `a`.`baz` FROM `foo`', $it->getSql());

        $it = $this->it->execute(['foo', 'bar'], ['FIELDS' => ['foo' => ['*']]]);
        $this->assertSame('SELECT `foo`.* FROM `foo`, `bar`', $it->getSql());

        $it = $this->it->execute(['foo', 'bar'], ['FIELDS' => ['foo.*']]);
        $this->assertSame('SELECT `foo`.* FROM `foo`, `bar`', $it->getSql());

        $it = $this->it->execute('foo', ['FIELDS' => ['SUM' => 'bar AS cpt']]);
        $this->assertSame('SELECT SUM(`bar`) AS `cpt` FROM `foo`', $it->getSql());

        $it = $this->it->execute('foo', ['FIELDS' => ['AVG' => 'bar AS cpt']]);
        $this->assertSame('SELECT AVG(`bar`) AS `cpt` FROM `foo`', $it->getSql());

        $it = $this->it->execute('foo', ['FIELDS' => ['MIN' => 'bar AS cpt']]);
        $this->assertSame('SELECT MIN(`bar`) AS `cpt` FROM `foo`', $it->getSql());

        $it = $this->it->execute('foo', ['FIELDS' => ['MAX' => 'bar AS cpt']]);
        $this->assertSame('SELECT MAX(`bar`) AS `cpt` FROM `foo`', $it->getSql());

        $it = $this->it->execute('foo', ['FIELDS' => new \QueryExpression('IF(bar IS NOT NULL, 1, 0) AS baz')]);
        $this->assertSame('SELECT IF(bar IS NOT NULL, 1, 0) AS baz FROM `foo`', $it->getSql());
    }

    public function testFrom()
    {
        $this->it->buildQuery(['FIELDS' => 'bar', 'FROM' => 'foo']);
        $this->assertSame('SELECT `bar` FROM `foo`', $this->it->getSql());

        $this->it->buildQuery(['FIELDS' => 'bar', 'FROM' => 'foo as baz']);
        $this->assertSame('SELECT `bar` FROM `foo` AS `baz`', $this->it->getSql());

        $this->it->buildQuery(['FIELDS' => 'bar', 'FROM' => ['foo', 'baz']]);
        $this->assertSame('SELECT `bar` FROM `foo`, `baz`', $this->it->getSql());

        $this->it->buildQuery(['FIELDS' => 'c', 'FROM' => new \QueryExpression("(SELECT CONCAT('foo', 'baz') as c) as t")]);
        $this->assertSame("SELECT `c` FROM (SELECT CONCAT('foo', 'baz') as c) as t", $this->it->getSql());
    }


    public function testOrder()
    {
        $it = $this->it->execute('foo', ['ORDERBY' => 'bar']);
        $this->assertSame('SELECT * FROM `foo` ORDER BY `bar`', $it->getSql());

        $it = $this->it->execute('foo', ['ORDER' => 'bar']);
        $this->assertSame('SELECT * FROM `foo` ORDER BY `bar`', $it->getSql());

        $it = $this->it->execute('foo', ['ORDERBY' => '`baz`']);
        $this->assertSame('SELECT * FROM `foo` ORDER BY `baz`', $it->getSql());

        $it = $this->it->execute('foo', ['ORDER' => '`baz`']);
        $this->assertSame('SELECT * FROM `foo` ORDER BY `baz`', $it->getSql());

        $it = $this->it->execute('foo', ['ORDERBY' => 'bar ASC']);
        $this->assertSame('SELECT * FROM `foo` ORDER BY `bar` ASC', $it->getSql());

        $it = $this->it->execute('foo', ['ORDER' => 'bar ASC']);
        $this->assertSame('SELECT * FROM `foo` ORDER BY `bar` ASC', $it->getSql());

        $it = $this->it->execute('foo', ['ORDERBY' => 'bar DESC']);
        $this->assertSame('SELECT * FROM `foo` ORDER BY `bar` DESC', $it->getSql());

        $it = $this->it->execute('foo', ['ORDER' => 'bar DESC']);
        $this->assertSame('SELECT * FROM `foo` ORDER BY `bar` DESC', $it->getSql());

        $it = $this->it->execute('foo', ['ORDERBY' => ['`a`', 'b ASC', 'c DESC']]);
        $this->assertSame('SELECT * FROM `foo` ORDER BY `a`, `b` ASC, `c` DESC', $it->getSql());

        $it = $this->it->execute('foo', ['ORDER' => ['`a`', 'b ASC', 'c DESC']]);
        $this->assertSame('SELECT * FROM `foo` ORDER BY `a`, `b` ASC, `c` DESC', $it->getSql());

        $it = $this->it->execute('foo', ['ORDERBY' => 'bar, baz ASC']);
        $this->assertSame('SELECT * FROM `foo` ORDER BY `bar`, `baz` ASC', $it->getSql());

        $it = $this->it->execute('foo', ['ORDER' => 'bar, baz ASC']);
        $this->assertSame('SELECT * FROM `foo` ORDER BY `bar`, `baz` ASC', $it->getSql());

        $it = $this->it->execute('foo', ['ORDERBY' => 'bar DESC, baz ASC']);
        $this->assertSame('SELECT * FROM `foo` ORDER BY `bar` DESC, `baz` ASC', $it->getSql());

        $it = $this->it->execute('foo', ['ORDER' => 'bar DESC, baz ASC']);
        $this->assertSame('SELECT * FROM `foo` ORDER BY `bar` DESC, `baz` ASC', $it->getSql());

        $it = $this->it->execute('foo', ['ORDER' => new \QueryExpression("CASE WHEN `foo` LIKE 'test%' THEN 0 ELSE 1 END")]);
        $this->assertSame("SELECT * FROM `foo` ORDER BY CASE WHEN `foo` LIKE 'test%' THEN 0 ELSE 1 END", $it->getSql());

        $it = $this->it->execute('foo', ['ORDER' => [new \QueryExpression("CASE WHEN `foo` LIKE 'test%' THEN 0 ELSE 1 END"), 'bar ASC']]);
        $this->assertSame("SELECT * FROM `foo` ORDER BY CASE WHEN `foo` LIKE 'test%' THEN 0 ELSE 1 END, `bar` ASC", $it->getSql());

        $it = $this->it->execute('foo', ['ORDER' => [new \QueryExpression("CASE WHEN `foo` LIKE 'test%' THEN 0 ELSE 1 END"), 'bar ASC, baz DESC']]);
        $this->assertSame("SELECT * FROM `foo` ORDER BY CASE WHEN `foo` LIKE 'test%' THEN 0 ELSE 1 END, `bar` ASC, `baz` DESC", $it->getSql());

        $it = $this->it->execute('foo', ['ORDER' => [new \QueryExpression("CASE WHEN `foo` LIKE 'test%' THEN 0 ELSE 1 END"), 'bar ASC', 'baz DESC']]);
        $this->assertSame("SELECT * FROM `foo` ORDER BY CASE WHEN `foo` LIKE 'test%' THEN 0 ELSE 1 END, `bar` ASC, `baz` DESC", $it->getSql());

        $this->expectExceptionObject(new \LogicException('Invalid order clause.'));
        $this->it->execute('foo', ['ORDER' => [new \stdClass()]]);
    }


    public function testCount()
    {
        $it = $this->it->execute('foo', ['COUNT' => 'cpt']);
        $this->assertSame('SELECT COUNT(*) AS cpt FROM `foo`', $it->getSql());

        $it = $this->it->execute('foo', ['COUNT' => 'cpt', 'SELECT' => 'bar', 'DISTINCT' => true]);
        $this->assertSame('SELECT COUNT(DISTINCT `bar`) AS cpt FROM `foo`', $it->getSql());

        $it = $this->it->execute('foo', ['COUNT' => 'cpt', 'FIELDS' => ['name', 'version']]);
        $this->assertSame('SELECT COUNT(*) AS cpt, `name`, `version` FROM `foo`', $it->getSql());

        $it = $this->it->execute('foo', ['FIELDS' => ['COUNT' => 'bar']]);
        $this->assertSame('SELECT COUNT(`bar`) FROM `foo`', $it->getSql());

        $it = $this->it->execute('foo', ['FIELDS' => ['COUNT' => 'bar AS cpt']]);
        $this->assertSame('SELECT COUNT(`bar`) AS `cpt` FROM `foo`', $it->getSql());

        $it = $this->it->execute('foo', ['FIELDS' => ['foo.bar', 'COUNT' => 'foo.baz']]);
        $this->assertSame('SELECT `foo`.`bar`, COUNT(`foo`.`baz`) FROM `foo`', $it->getSql());

        $it = $this->it->execute('foo', ['FIELDS' => ['COUNT' => ['bar', 'baz']]]);
        $this->assertSame('SELECT COUNT(`bar`), COUNT(`baz`) FROM `foo`', $it->getSql());

        $it = $this->it->execute('foo', ['FIELDS' => ['COUNT' => ['bar AS cpt', 'baz AS cpt2']]]);
        $this->assertSame('SELECT COUNT(`bar`) AS `cpt`, COUNT(`baz`) AS `cpt2` FROM `foo`', $it->getSql());

        $it = $this->it->execute('foo', ['FIELDS' => ['foo.bar', 'COUNT' => ['foo.baz', 'foo.qux']]]);
        $this->assertSame('SELECT `foo`.`bar`, COUNT(`foo`.`baz`), COUNT(`foo`.`qux`) FROM `foo`', $it->getSql());
    }

    public function testCountDistinct()
    {
        $it = $this->it->execute('foo', ['FIELDS' => ['COUNT DISTINCT' => 'bar']]);
        $this->assertSame('SELECT COUNT(DISTINCT(`bar`)) FROM `foo`', $it->getSql());

        $it = $this->it->execute('foo', ['FIELDS' => ['COUNT DISTINCT' => ['bar', 'baz']]]);
        $this->assertSame('SELECT COUNT(DISTINCT(`bar`)), COUNT(DISTINCT(`baz`)) FROM `foo`', $it->getSql());

        $it = $this->it->execute('foo', ['FIELDS' => ['COUNT DISTINCT' => ['bar AS cpt', 'baz AS cpt2']]]);
        $this->assertSame('SELECT COUNT(DISTINCT(`bar`)) AS `cpt`, COUNT(DISTINCT(`baz`)) AS `cpt2` FROM `foo`', $it->getSql());

        $it = $this->it->execute('foo', ['FIELDS' => ['foo.bar', 'COUNT DISTINCT' => ['foo.baz', 'foo.qux']]]);
        $this->assertSame('SELECT `foo`.`bar`, COUNT(DISTINCT(`foo`.`baz`)), COUNT(DISTINCT(`foo`.`qux`)) FROM `foo`', $it->getSql());

        $it = $this->it->execute('foo', ['FIELDS' => 'bar', 'COUNT' => 'cpt', 'DISTINCT' => true]);
        $this->assertSame('SELECT COUNT(DISTINCT `bar`) AS cpt FROM `foo`', $it->getSql());

        $this->expectExceptionObject(new \LogicException("With COUNT and DISTINCT, you must specify exactly one field, or use 'COUNT DISTINCT'."));
        $this->it->execute('foo', ['COUNT' => 'cpt', 'DISTINCT' => true]);
    }


    public function testJoins()
    {
        $it = $this->it->execute('foo', ['LEFT JOIN' => []]);
        $this->assertSame('SELECT * FROM `foo`', $it->getSql());

        $it = $this->it->execute('foo', ['LEFT JOIN' => ['bar' => ['FKEY' => ['bar' => 'id', 'foo' => 'fk']]]]);
        $this->assertSame('SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)', $it->getSql());

        //old JOIN alias for LEFT JOIN
        $it = $this->it->execute('foo', ['JOIN' => ['bar' => ['FKEY' => ['bar' => 'id', 'foo' => 'fk']]]]);
        $this->assertSame('SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)', $it->getSql());

        $it = $this->it->execute('foo', ['LEFT JOIN' => [['TABLE' => 'bar', 'FKEY' => ['bar' => 'id', 'foo' => 'fk']]]]);
        $this->assertSame('SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)', $it->getSql());

        $it = $this->it->execute('foo', ['LEFT JOIN' => ['bar' => ['ON' => ['bar' => 'id', 'foo' => 'fk']]]]);
        $this->assertSame('SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)', $it->getSql());

        $it = $this->it->execute(
            'foo',
            [
                'LEFT JOIN' => [
                    'bar' => [
                        'FKEY' => [
                            'bar' => 'id',
                            'foo' => 'fk',
                        ],
                    ],
                    'baz' => [
                        'FKEY' => [
                            'baz' => 'id',
                            'foo' => 'baz_id',
                        ],
                    ],
                ],
            ]
        );
        $this->assertSame(
            'SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk`) ' .
            'LEFT JOIN `baz` ON (`baz`.`id` = `foo`.`baz_id`)',
            $it->getSql()
        );

        $it = $this->it->execute('foo', ['INNER JOIN' => []]);
        $this->assertSame('SELECT * FROM `foo`', $it->getSql());

        $it = $this->it->execute('foo', ['INNER JOIN' => ['bar' => ['FKEY' => ['bar' => 'id', 'foo' => 'fk']]]]);
        $this->assertSame('SELECT * FROM `foo` INNER JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)', $it->getSql());

        $it = $this->it->execute('foo', ['RIGHT JOIN' => []]);
        $this->assertSame('SELECT * FROM `foo`', $it->getSql());

        $it = $this->it->execute('foo', ['RIGHT JOIN' => ['bar' => ['FKEY' => ['bar' => 'id', 'foo' => 'fk']]]]);
        $this->assertSame('SELECT * FROM `foo` RIGHT JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)', $it->getSql());

        //test conditions
        $it = $this->it->execute(
            'foo',
            [
                'LEFT JOIN' => [
                    'bar' => [
                        'FKEY' => [
                            'bar' => 'id',
                            'foo' => 'fk', [
                                'OR'  => ['field' => ['>', 20]],
                            ],
                        ],
                    ],
                ],
            ]
        );
        $this->assertSame(
            'SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk` OR `field` > \'20\')',
            $it->getSql()
        );

        $it = $this->it->execute(
            'foo',
            [
                'LEFT JOIN' => [
                    'bar' => [
                        'FKEY' => [
                            'bar' => 'id',
                            'foo' => 'fk', [
                                'AND'  => ['field' => 42],
                            ],
                        ],
                    ],
                ],
            ]
        );
        $this->assertSame(
            'SELECT * FROM `foo` LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk` AND `field` = \'42\')',
            $it->getSql()
        );

        //test derived table in JOIN statement
        $it = $this->it->execute(
            'foo',
            [
                'LEFT JOIN' => [
                    [
                        'TABLE'  => new \QuerySubQuery(['FROM' => 'bar'], 't2'),
                        'FKEY'   => [
                            't2'  => 'id',
                            'foo' => 'fk',
                        ],
                    ],
                ],
            ]
        );
        $this->assertSame(
            'SELECT * FROM `foo` LEFT JOIN (SELECT * FROM `bar`) AS `t2` ON (`t2`.`id` = `foo`.`fk`)',
            $it->getSql()
        );
    }

    public function testBadJoin()
    {
        $this->expectExceptionObject(new \LogicException('BAD JOIN'));
        $this->it->execute('foo', ['LEFT JOIN' => ['ON' => ['a' => 'id', 'b' => 'a_id']]]);
    }

    public function testBadJoinValue()
    {
        $this->expectExceptionObject(new \LogicException('BAD JOIN, value must be [ table => criteria ].'));
        $this->it->execute('foo', ['LEFT JOIN' => 'bar']);
    }

    public function testBadJoinFkey()
    {
        $this->expectExceptionObject(new \LogicException('BAD FOREIGN KEY, should be [ table1 => key1, table2 => key2 ] or [ table1 => key1, table2 => key2, [criteria]].'));
        $this->it->execute('foo', ['INNER JOIN' => ['bar' => ['FKEY' => 'akey']]]);
    }

    public function testAnalyseJoins()
    {
        $join = $this->it->analyseJoins(['LEFT JOIN' => ['bar' => ['FKEY' => ['bar' => 'id', 'foo' => 'fk']]]]);
        $this->assertSame(' LEFT JOIN `bar` ON (`bar`.`id` = `foo`.`fk`)', $join);

        // QueryExpression
        $expression = "LEFT JOIN xxxx";
        $join = $this->it->analyseJoins(['LEFT JOIN' => [new QueryExpression($expression)]]);
        $this->assertSame($expression, $join);

        $this->expectExceptionObject(new \LogicException('Invalid JOIN type `LEFT OUTER JOIN`.'));
        $this->it->analyseJoins(['LEFT OUTER JOIN' => ['ON' => ['a' => 'id', 'b' => 'a_id']]]);
    }

    public function testHaving()
    {
        $it = $this->it->execute('foo', ['HAVING' => ['bar' => 1]]);
        $this->assertSame('SELECT * FROM `foo` HAVING `bar` = \'1\'', $it->getSql());

        $it = $this->it->execute('foo', ['HAVING' => ['bar' => ['>', 0]]]);
        $this->assertSame('SELECT * FROM `foo` HAVING `bar` > \'0\'', $it->getSql());
    }

    public function testOperators()
    {
        $it = $this->it->execute('foo', ['a' => 1]);
        $this->assertSame('SELECT * FROM `foo` WHERE `a` = \'1\'', $it->getSql());

        $it = $this->it->execute('foo', ['a' => ['=', 1]]);
        $this->assertSame('SELECT * FROM `foo` WHERE `a` = \'1\'', $it->getSql());

        $it = $this->it->execute('foo', ['a' => ['>', 1]]);
        $this->assertSame('SELECT * FROM `foo` WHERE `a` > \'1\'', $it->getSql());

        $it = $this->it->execute('foo', ['a' => ['LIKE', '%bar%']]);
        $this->assertSame('SELECT * FROM `foo` WHERE `a` LIKE \'%bar%\'', $it->getSql());

        $it = $this->it->execute('foo', ['NOT' => ['a' => ['LIKE', '%bar%']]]);
        $this->assertSame('SELECT * FROM `foo` WHERE NOT (`a` LIKE \'%bar%\')', $it->getSql());

        $it = $this->it->execute('foo', ['a' => ['NOT LIKE', '%bar%']]);
        $this->assertSame('SELECT * FROM `foo` WHERE `a` NOT LIKE \'%bar%\'', $it->getSql());

        $it = $this->it->execute('foo', ['a' => ['<>', 1]]);
        $this->assertSame('SELECT * FROM `foo` WHERE `a` <> \'1\'', $it->getSql());

        $it = $this->it->execute('foo', ['a' => ['&', 1]]);
        $this->assertSame('SELECT * FROM `foo` WHERE `a` & \'1\'', $it->getSql());

        $it = $this->it->execute('foo', ['a' => ['|', 1]]);
        $this->assertSame('SELECT * FROM `foo` WHERE `a` | \'1\'', $it->getSql());
    }


    public function testWhere()
    {
        $it = $this->it->execute('foo', 'id=1');
        $this->assertSame('SELECT * FROM `foo` WHERE id=1', $it->getSql());

        $it = $this->it->execute('foo', ['WHERE' => ['bar' => null]]);
        $this->assertSame('SELECT * FROM `foo` WHERE `bar` IS NULL', $it->getSql());

        $it = $this->it->execute('foo', ['bar' => null]);
        $this->assertSame('SELECT * FROM `foo` WHERE `bar` IS NULL', $it->getSql());

        $it = $this->it->execute('foo', ['`bar`' => null]);
        $this->assertSame('SELECT * FROM `foo` WHERE `bar` IS NULL', $it->getSql());

        $it = $this->it->execute('foo', ['bar' => 1]);
        $this->assertSame('SELECT * FROM `foo` WHERE `bar` = \'1\'', $it->getSql());

        $it = $this->it->execute('foo', ['bar' => [1, 2, 4]]);
        $this->assertSame("SELECT * FROM `foo` WHERE `bar` IN ('1', '2', '4')", $it->getSql());

        $it = $this->it->execute('foo', ['bar' => ['a', 'b', 'c']]);
        $this->assertSame("SELECT * FROM `foo` WHERE `bar` IN ('a', 'b', 'c')", $it->getSql());

        $it = $this->it->execute('foo', ['bar' => 'val']);
        $this->assertSame("SELECT * FROM `foo` WHERE `bar` = 'val'", $it->getSql());

        $it = $this->it->execute('foo', ['bar' => new \QueryExpression('`field`')]);
        $this->assertSame('SELECT * FROM `foo` WHERE `bar` = `field`', $it->getSql());

        $it = $this->it->execute('foo', ['bar' => '?']);
        $this->assertSame('SELECT * FROM `foo` WHERE `bar` = \'?\'', $it->getSql());

        $it = $this->it->execute('foo', ['bar' => new \QueryParam()]);
        $this->assertSame('SELECT * FROM `foo` WHERE `bar` = ?', $it->getSql());

        /*$it = $this->it->execute('foo', ['bar' => new \QueryParam('myparam')]);
        $this->assertSame('SELECT * FROM `foo` WHERE `bar` = :myparam', $it->getSql());*/
    }

    public function testEmptyIn(): void
    {
        $this->expectExceptionObject(new \RuntimeException('Empty IN are not allowed'));
        $this->it->execute('foo', ['bar' => []]);
    }

    public function testFkey()
    {
        $it = $this->it->execute(['foo', 'bar'], ['FKEY' => ['id', 'fk']]);
        $this->assertSame('SELECT * FROM `foo`, `bar` WHERE `id` = `fk`', $it->getSql());

        $it = $this->it->execute(['foo', 'bar'], ['FKEY' => ['foo' => 'id', 'bar' => 'fk']]);
        $this->assertSame('SELECT * FROM `foo`, `bar` WHERE `foo`.`id` = `bar`.`fk`', $it->getSql());

        $it = $this->it->execute(['foo', 'bar'], ['FKEY' => ['`foo`' => 'id', 'bar' => '`fk`']]);
        $this->assertSame('SELECT * FROM `foo`, `bar` WHERE `foo`.`id` = `bar`.`fk`', $it->getSql());
    }

    public function testGroupBy()
    {
        $it = $this->it->execute(['foo'], ['GROUPBY' => ['id']]);
        $this->assertSame('SELECT * FROM `foo` GROUP BY `id`', $it->getSql());

        $it = $this->it->execute(['foo'], ['GROUP' => ['id']]);
        $this->assertSame('SELECT * FROM `foo` GROUP BY `id`', $it->getSql());

        $it = $this->it->execute(['foo'], ['GROUPBY' => 'id']);
        $this->assertSame('SELECT * FROM `foo` GROUP BY `id`', $it->getSql());

        $it = $this->it->execute(['foo'], ['GROUP' => 'id']);
        $this->assertSame('SELECT * FROM `foo` GROUP BY `id`', $it->getSql());

        $it = $this->it->execute(['foo'], ['GROUPBY' => ['id', 'name']]);
        $this->assertSame('SELECT * FROM `foo` GROUP BY `id`, `name`', $it->getSql());

        $it = $this->it->execute(['foo'], ['GROUP' => ['id', 'name']]);
        $this->assertSame('SELECT * FROM `foo` GROUP BY `id`, `name`', $it->getSql());
    }

    public function testNoFieldGroup()
    {
        $this->expectExceptionObject(new \LogicException('Missing group by field.'));
        $this->it->execute(['foo'], ['GROUP' => []]);
    }

    public function testNoFieldGroupBy()
    {
        $this->expectExceptionObject(new \LogicException('Missing group by field.'));
        $this->it->execute(['foo'], ['GROUPBY' => []]);
    }


    public function testRange()
    {

        $it = $this->it->execute('foo', ['START' => 5, 'LIMIT' => 10]);
        $this->assertSame('SELECT * FROM `foo` LIMIT 10 OFFSET 5', $it->getSql());

        $it = $this->it->execute('foo', ['OFFSET' => 5, 'LIMIT' => 10]);
        $this->assertSame('SELECT * FROM `foo` LIMIT 10 OFFSET 5', $it->getSql());
    }


    public function testLogical()
    {
        $it = $this->it->execute(['foo'], [['a' => 1, 'b' => 2]]);
        $this->assertSame('SELECT * FROM `foo` WHERE (`a` = \'1\' AND `b` = \'2\')', $it->getSql());

        $it = $this->it->execute(['foo'], ['AND' => ['a' => 1, 'b' => 2]]);
        $this->assertSame('SELECT * FROM `foo` WHERE (`a` = \'1\' AND `b` = \'2\')', $it->getSql());

        $it = $this->it->execute(['foo'], ['OR' => ['a' => 1, 'b' => 2]]);
        $this->assertSame('SELECT * FROM `foo` WHERE (`a` = \'1\' OR `b` = \'2\')', $it->getSql());

        $it = $this->it->execute(['foo'], ['NOT' => ['a' => 1, 'b' => 2]]);
        $this->assertSame('SELECT * FROM `foo` WHERE NOT (`a` = \'1\' AND `b` = \'2\')', $it->getSql());

        $crit = [
            'WHERE' => [
                'OR' => [
                    [
                        'items_id' => 15,
                        'itemtype' => 'Computer',
                    ],
                    [
                        'items_id' => 3,
                        'itemtype' => 'Document',
                    ],
                ],
            ],
        ];
        $sql = "SELECT * FROM `foo` WHERE ((`items_id` = '15' AND `itemtype` = 'Computer') OR (`items_id` = '3' AND `itemtype` = 'Document'))";
        $it = $this->it->execute(['foo'], $crit);
        $this->assertSame($sql, $it->getSql());

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
        $sql = "SELECT * FROM `foo` WHERE `a` = '1' AND (`b` = '2' OR NOT (`c` IN ('2', '3') AND (`d` = '4' AND `e` = '5')))";
        $it = $this->it->execute(['foo'], $crit);
        $this->assertSame($sql, $it->getSql());

        $crit['FROM'] = 'foo';
        $it = $this->it->execute($crit);
        $this->assertSame($sql, $it->getSql());

        $crit = [
            'FROM'   => 'foo',
            'WHERE'  => [
                'bar' => 'baz',
                'RAW' => ['SELECT COUNT(*) FROM xyz' => 5],
            ],
        ];
        $it = $this->it->execute($crit);
        $this->assertSame("SELECT * FROM `foo` WHERE `bar` = 'baz' AND ((SELECT COUNT(*) FROM xyz) = '5')", $it->getSql());

        $crit = [
            'FROM'   => 'foo',
            'WHERE'  => [
                'bar' => 'baz',
                'RAW' => ['SELECT COUNT(*) FROM xyz' => ['>', 2]],
            ],
        ];
        $it = $this->it->execute($crit);
        $this->assertSame("SELECT * FROM `foo` WHERE `bar` = 'baz' AND ((SELECT COUNT(*) FROM xyz) > '2')", $it->getSql());

        $crit = [
            'FROM'   => 'foo',
            'WHERE'  => [
                'bar' => 'baz',
                'RAW' => ['SELECT COUNT(*) FROM xyz' => [3, 4]],
            ],
        ];
        $it = $this->it->execute($crit);
        $this->assertSame("SELECT * FROM `foo` WHERE `bar` = 'baz' AND ((SELECT COUNT(*) FROM xyz) IN ('3', '4'))", $it->getSql());
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
        $this->assertSame($sql, $it->getSql());
    }


    public function testRows()
    {
        global $DB;

        $it = $this->it->execute('foo');
        $this->assertSame(0, $it->numrows());
        $this->assertCount(0, $it);
        $this->assertNull($it->current());

        $it = $DB->request('glpi_configs', ['context' => 'core', 'name' => 'version']);
        $this->assertSame(1, $it->numrows());
        $this->assertCount(1, $it);
        $row = $it->current();
        $key = $it->key();
        $this->assertSame($key, $row['id']);

        $it = $DB->request('glpi_configs', ['context' => 'core']);
        $this->assertGreaterThan(100, $it->numrows());
        $this->assertGreaterThan(100, count($it));
        $this->assertTrue($it->numrows() == count($it));
    }

    public function testAlias()
    {
        $it = $this->it->execute('foo AS f');
        $this->assertSame('SELECT * FROM `foo` AS `f`', $it->getSql());

        $it = $this->it->execute(['FROM' => 'foo AS f']);
        $this->assertSame('SELECT * FROM `foo` AS `f`', $it->getSql());

        $it = $this->it->execute(['SELECT' => ['field AS f'], 'FROM' => 'bar AS b']);
        $this->assertSame('SELECT `field` AS `f` FROM `bar` AS `b`', $it->getSql());

        $it = $this->it->execute(['SELECT' => ['b.field AS f'], 'FROM' => 'bar AS b']);
        $this->assertSame('SELECT `b`.`field` AS `f` FROM `bar` AS `b`', $it->getSql());

        $it = $this->it->execute(['SELECT' => ['id', 'field AS f', 'baz as Z'], 'FROM' => 'bar AS b']);
        $this->assertSame('SELECT `id`, `field` AS `f`, `baz` AS `Z` FROM `bar` AS `b`', $it->getSql());

        $it = $this->it->execute([
            'FROM' => 'bar AS b',
            'INNER JOIN'   => [
                'foo AS f' => [
                    'FKEY' => [
                        'b'   => 'fid',
                        'f'   => 'id',
                    ],
                ],
            ],
        ]);
        $this->assertSame('SELECT * FROM `bar` AS `b` INNER JOIN `foo` AS `f` ON (`b`.`fid` = `f`.`id`)', $it->getSql());

        $it = $this->it->execute([
            'SELECT' => ['id', 'field  AS  f', 'baz as  Z'],
            'FROM' => 'bar  AS b',
            'INNER JOIN'   => [
                'foo AS  f' => [
                    'FKEY' => [
                        'b'   => 'fid',
                        'f'   => 'id',
                    ],
                ],
            ],
        ]);
        $this->assertSame('SELECT `id`, `field` AS `f`, `baz` AS `Z` FROM `bar` AS `b` INNER JOIN `foo` AS `f` ON (`b`.`fid` = `f`.`id`)', $it->getSql());
    }

    public function testExpression()
    {
        $it = $this->it->execute('foo', [new \QueryExpression('a LIKE b')]);
        $this->assertSame('SELECT * FROM `foo` WHERE a LIKE b', $it->getSql());

        $it = $this->it->execute('foo', ['FIELDS' => ['b' => 'bar', '`c`' => '`baz`', new \QueryExpression('1 AS `myfield`')]]);
        $this->assertSame('SELECT `b`.`bar`, `c`.`baz`, 1 AS `myfield` FROM `foo`', $it->getSql());
    }

    public function testSubQuery()
    {
        $crit = ['SELECT' => 'id', 'FROM' => 'baz', 'WHERE' => ['z' => 'f']];
        $raw_subq = "(SELECT `id` FROM `baz` WHERE `z` = 'f')";

        $sub_query = new \QuerySubQuery($crit);
        $this->assertSame($raw_subq, $sub_query->getQuery());

        $it = $this->it->execute('foo', ['bar' => $sub_query]);
        $this->assertSame(
            "SELECT * FROM `foo` WHERE `bar` IN $raw_subq",
            $it->getSql()
        );

        $it = $this->it->execute('foo', ['bar' => ['<>', $sub_query]]);
        $this->assertSame(
            "SELECT * FROM `foo` WHERE `bar` <> $raw_subq",
            $it->getSql()
        );

        $it = $this->it->execute('foo', ['NOT' => ['bar' => $sub_query]]);
        $this->assertSame(
            "SELECT * FROM `foo` WHERE NOT (`bar` IN $raw_subq)",
            $it->getSql()
        );

        $sub_query = new \QuerySubQuery($crit, 'thesubquery');
        $this->assertSame("$raw_subq AS `thesubquery`", $sub_query->getQuery());

        $it = $this->it->execute('foo', ['bar' => $sub_query]);
        $this->assertSame(
            "SELECT * FROM `foo` WHERE `bar` IN $raw_subq AS `thesubquery`",
            $it->getSql()
        );

        $it = $this->it->execute([
            'SELECT' => ['bar', $sub_query],
            'FROM'   => 'foo',
        ]);
        $this->assertSame(
            "SELECT `bar`, $raw_subq AS `thesubquery` FROM `foo`",
            $it->getSql()
        );
    }

    public function testUnionQuery()
    {
        $union_crit = [
            ['FROM' => 'table1'],
            ['FROM' => 'table2'],
        ];
        $union = new \QueryUnion($union_crit);
        $union_raw_query = '((SELECT * FROM `table1`) UNION ALL (SELECT * FROM `table2`))';
        $raw_query = 'SELECT * FROM ' . $union_raw_query . ' AS `union_' . md5($union_raw_query) . '`';
        $it = $this->it->execute(['FROM' => $union]);
        $this->assertSame($raw_query, $it->getSql());

        $union = new \QueryUnion($union_crit, true);
        $union_raw_query = '((SELECT * FROM `table1`) UNION (SELECT * FROM `table2`))';
        $raw_query = 'SELECT * FROM ' . $union_raw_query . ' AS `union_' . md5($union_raw_query) . '`';
        $it = $this->it->execute(['FROM' => $union]);
        $this->assertSame($raw_query, $it->getSql());

        $union = new \QueryUnion($union_crit, false, 'theunion');
        $raw_query = 'SELECT * FROM ((SELECT * FROM `table1`) UNION ALL (SELECT * FROM `table2`)) AS `theunion`';
        $it = $this->it->execute(['FROM' => $union]);
        $this->assertSame($raw_query, $it->getSql());

        $union = new \QueryUnion($union_crit, false, 'theunion');
        $raw_query = 'SELECT DISTINCT `theunion`.`field` FROM ((SELECT * FROM `table1`) UNION ALL (SELECT * FROM `table2`)) AS `theunion`';
        $crit = [
            'SELECT'    => 'theunion.field',
            'DISTINCT'  => true,
            'FROM'      => $union,
        ];
        $it = $this->it->execute($crit);
        $this->assertSame($raw_query, $it->getSql());

        $union = new \QueryUnion($union_crit, true);
        $union_raw_query = '((SELECT * FROM `table1`) UNION (SELECT * FROM `table2`))';
        $raw_query = 'SELECT DISTINCT `theunion`.`field` FROM ' . $union_raw_query . ' AS `union_' . md5($union_raw_query) . '`';
        $crit = [
            'SELECT'    => 'theunion.field',
            'DISTINCT'  => true,
            'FROM'      => $union,
        ];
        $it = $this->it->execute($crit);
        $this->assertSame($raw_query, $it->getSql());
    }

    public function testComplexUnionQuery()
    {

        $fk = \Ticket::getForeignKeyField();
        $users_table = \User::getTable();
        $users_table = 'glpi_ticket_users';
        $groups_table = 'glpi_groups_tickets';

        $subquery1 = new \QuerySubQuery([
            'SELECT'    => [
                'usr.id AS users_id',
                'tu.type AS type',
            ],
            'FROM'      => "$users_table AS tu",
            'LEFT JOIN' => [
                \User::getTable() . ' AS usr' => [
                    'ON' => [
                        'tu'  => 'users_id',
                        'usr' => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                "tu.$fk" => 42,
            ],
        ]);
        $subquery2 = new \QuerySubQuery([
            'SELECT'    => [
                'usr.id AS users_id',
                'gt.type AS type',
            ],
            'FROM'      => "$groups_table AS gt",
            'LEFT JOIN' => [
                \Group_User::getTable() . ' AS gu'   => [
                    'ON' => [
                        'gu'  => 'groups_id',
                        'gt'  => 'groups_id',
                    ],
                ],
                \User::getTable() . ' AS usr'        => [
                    'ON' => [
                        'gu'  => 'users_id',
                        'usr' => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                "gt.$fk" => 42,
            ],
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

        $union = new \QueryUnion([$subquery1, $subquery2], false, 'allactors');
        $it = $this->it->execute([
            'FIELDS'          => [
                'users_id',
                'type',
            ],
            'DISTINCT'        => true,
            'FROM'            => $union,
        ]);
        $this->assertSame($raw_query, $it->getSql());
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
                                'ADDR.is_deleted' => 0,
                            ],
                        ],
                    ],
                ],
            ],
            'LEFT JOIN'    => [
                'glpi_entities'             => [
                    'ON' => [
                        'ADDR'            => 'entities_id',
                        'glpi_entities'   => 'id',
                    ],
                ],
            ],
            'WHERE'        => [
                'LINK.ipnetworks_id' => 42,
            ],
        ];

        foreach ($CFG_GLPI["networkport_types"] as $itemtype) {
            $table = getTableForItemType($itemtype);
            $criteria = $main_criteria;
            $criteria['SELECT'] = array_merge($criteria['SELECT'], [
                'NAME.id AS name_id',
                'PORT.id AS port_id',
                'ITEM.id AS item_id',
                new \QueryExpression("'$itemtype' AS " . $DB->quoteName('item_type')),
            ]);
            $criteria['INNER JOIN'] = $criteria['INNER JOIN'] + [
                'glpi_networknames AS NAME'   => [
                    'ON' => [
                        'NAME'   => 'id',
                        'ADDR'   => 'items_id', [
                            'AND' => [
                                'NAME.itemtype' => 'NetworkPort',
                            ],
                        ],
                    ],
                ],
                'glpi_networkports AS PORT'   => [
                    'ON' => [
                        'NAME'   => 'items_id',
                        'PORT'   => 'id', [
                            'AND' => [
                                'PORT.itemtype' => $itemtype,
                            ],
                        ],
                    ],
                ],
                "$table AS ITEM"              => [
                    'ON' => [
                        'ITEM'   => 'id',
                        'PORT'   => 'items_id',
                    ],
                ],
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
                            'NAME.itemtype' => 'NetworkPort',
                        ],
                    ],
                ],
            ],
            'glpi_networkports AS PORT'   => [
                'ON' => [
                    'NAME'   => 'items_id',
                    'PORT'   => 'id', [
                        'AND' => [
                            'NOT' => [
                                'PORT.itemtype' => $CFG_GLPI['networkport_types'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $queries[] = $criteria;

        $criteria = $main_criteria;
        $criteria['SELECT'] = array_merge($criteria['SELECT'], [
            'NAME.id AS name_id',
            new \QueryExpression("NULL AS " . $DB->quoteName('port_id')),
            new \QueryExpression('NULL AS ' . $DB->quoteName('item_id')),
            new \QueryExpression("NULL AS " . $DB->quoteName('item_type')),
        ]);
        $criteria['INNER JOIN'] = $criteria['INNER JOIN'] + [
            'glpi_networknames AS NAME'   => [
                'ON' => [
                    'NAME'   => 'id',
                    'ADDR'   => 'items_id', [
                        'AND' => [
                            'NAME.itemtype' => ['!=', 'NetworkPort'],
                        ],
                    ],
                ],
            ],
        ];
        $queries[] = $criteria;

        $criteria = $main_criteria;
        $criteria['SELECT'] = array_merge($criteria['SELECT'], [
            new \QueryExpression("NULL AS " . $DB->quoteName('name_id')),
            new \QueryExpression("NULL AS " . $DB->quoteName('port_id')),
            new \QueryExpression('NULL AS ' . $DB->quoteName('item_id')),
            new \QueryExpression("NULL AS " . $DB->quoteName('item_type')),
        ]);
        $criteria['INNER JOIN']['glpi_ipaddresses AS ADDR']['ON'][0]['AND']['ADDR.itemtype'] = ['!=', 'NetworkName'];
        $queries[] = $criteria;

        $union = new \QueryUnion($queries);
        $criteria = [
            'FROM'   => $union,
        ];

        $it = $this->it->execute($criteria);
        $this->assertSame($raw_query, $it->getSql());
    }

    public function testAnalyseCrit()
    {
        $crit = [new \QuerySubQuery([
            'SELECT' => ['COUNT' => ['users_id']],
            'FROM'   => 'glpi_groups_users',
            'WHERE'  => ['groups_id' => new \QueryExpression('glpi_groups.id')],
        ]),
        ];
        $this->assertSame(
            "(SELECT COUNT(`users_id`) FROM `glpi_groups_users` WHERE `groups_id` = glpi_groups.id)",
            $this->it->analyseCrit($crit)
        );
    }

    public function testIteratorKeyWithId()
    {
        global $DB;

        // Select "id" field, keys will correspond to ids
        $iterator = $DB->request(
            [
                'SELECT' => ['id', 'name'],
                'FROM'   => $this->getUsersFakeTable(),
            ]
        );

        $this->assertEquals($iterator->current()['id'], $iterator->key());
        $this->assertEquals($iterator->current()['id'], $iterator->key()); // Calling key() twice should produce the same result (does not move the pointer)
        $iterator->next();
        $this->assertEquals($iterator->current()['id'], $iterator->key());
        $iterator->next();
        $this->assertEquals($iterator->current()['id'], $iterator->key());
        $iterator->next();
        $this->assertEquals(null, $iterator->key()); // Out of bounds, returns null
    }

    public function testIteratorKeyWithoutId()
    {
        global $DB;

        // Do not select "id" field, keys will be numeric, starting at 0
        $iterator = $DB->request(
            [
                'SELECT' => 'name',
                'FROM'   => $this->getUsersFakeTable(),
            ]
        );

        $this->assertEquals(0, $iterator->key());
        $this->assertEquals(0, $iterator->key()); // Calling key() twice should produce the same result (does not move the pointer)
        $iterator->next();
        $this->assertEquals(1, $iterator->key());
        $iterator->next();
        $this->assertEquals(2, $iterator->key());
        $iterator->next();
        $this->assertEquals(null, $iterator->key()); // Out of bounds, returns null
    }

    public function testIteratorCurrent()
    {
        global $DB;

        $iterator = $DB->request(
            [
                'SELECT' => ['id', 'name'],
                'FROM'   => $this->getUsersFakeTable(),
            ]
        );

        $this->assertEquals(['id' => 2, 'name' => 'jdoe'], $iterator->current());
        $this->assertEquals(['id' => 2, 'name' => 'jdoe'], $iterator->current()); // Calling current() twice should produce the same result (does not move the pointer)
        $iterator->next();
        $this->assertEquals(['id' => 5, 'name' => 'psmith'], $iterator->current());
        $iterator->next();
        $this->assertEquals(['id' => 6, 'name' => 'acain'], $iterator->current());
        $iterator->next();
        $this->assertEquals(null, $iterator->current()); // Out of bounds, returns null
    }

    public function testIteratorCount()
    {
        global $DB;

        $iterator = $DB->request(
            [
                'SELECT' => ['name'],
                'FROM'   => $this->getUsersFakeTable(),
            ]
        );

        $this->assertEquals(3, $iterator->count());
    }

    public function testIteratorValid()
    {
        global $DB;

        $iterator = $DB->request(
            [
                'SELECT' => ['name'],
                'FROM'   => $this->getUsersFakeTable(),
            ]
        );

        for ($i = 0; $i < $iterator->count(); $i++) {
            $this->assertTrue($iterator->valid());
            $iterator->next(); // Iterate until out of bounds
        }
        $this->assertFalse($iterator->valid());

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
    }

    public function testIteratorRewind()
    {
        global $DB;

        $iterator = $DB->request(
            [
                'SELECT' => ['name'],
                'FROM'   => $this->getUsersFakeTable(),
            ]
        );

        for ($i = 0; $i < $iterator->count(); $i++) {
            $this->assertEquals($i, $iterator->key());
            $iterator->next(); // Iterate until out of bounds
        }
        $this->assertNull($iterator->key());

        $iterator->rewind();
        $this->assertEquals(0, $iterator->key());
    }

    public function testIteratorSeek()
    {
        global $DB;

        $iterator = $DB->request(
            [
                'SELECT' => ['id', 'name'],
                'FROM'   => $this->getUsersFakeTable(),
            ]
        );

        $iterator->seek(2);
        $this->assertEquals(['id' => 6, 'name' => 'acain'], $iterator->current());

        $iterator->seek(0);
        $this->assertEquals(['id' => 2, 'name' => 'jdoe'], $iterator->current());

        $iterator->seek(1);
        $this->assertEquals(['id' => 5, 'name' => 'psmith'], $iterator->current());

        $this->expectException(\OutOfBoundsException::class);
        $iterator->seek(3);
    }

    /**
     * Returns a fake users table that can be used to test iterator.
     *
     * @return \QueryExpression
     */
    private function getUsersFakeTable(): \QueryExpression
    {
        global $DB;

        $user_pattern = '(SELECT %1$d AS %2$s, %3$s as %4$s)';
        $users_table = [
            sprintf($user_pattern, 2, $DB->quoteName('id'), $DB->quoteValue('jdoe'), $DB->quoteName('name')),
            sprintf($user_pattern, 5, $DB->quoteName('id'), $DB->quoteValue('psmith'), $DB->quoteName('name')),
            sprintf($user_pattern, 6, $DB->quoteName('id'), $DB->quoteValue('acain'), $DB->quoteName('name')),
        ];

        return new \QueryExpression('(' . implode(' UNION ALL ', $users_table) . ') AS users');
    }

    public function testRawFKeyCondition()
    {
        $this->assertEquals(
            "glpi_tickets.id=(CASE WHEN glpi_tickets_tickets.tickets_id_1=103 THEN glpi_tickets_tickets.tickets_id_2 ELSE glpi_tickets_tickets.tickets_id_1 END)",
            $this->it->analyseCrit([
                'ON' => new QueryExpression("glpi_tickets.id=(CASE WHEN glpi_tickets_tickets.tickets_id_1=103 THEN glpi_tickets_tickets.tickets_id_2 ELSE glpi_tickets_tickets.tickets_id_1 END)"),
            ])
        );
    }
}
