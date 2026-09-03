<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units\Glpi\DBAL;

use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\DBAL\QueryIdentifier;
use Glpi\DBAL\QueryValue;
use Glpi\Tests\GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class QueryFunctionTest extends GLPITestCase
{
    public static function dateAddProvider(): iterable
    {
        return [
            ['glpi_computers.date_mod', 1, 'DAY', null, 'DATE_ADD(`glpi_computers`.`date_mod`, INTERVAL 1 DAY)'],
            ['glpi_computers.date_mod', '5-1', 'DAY', null, 'DATE_ADD(`glpi_computers`.`date_mod`, INTERVAL \'5-1\' DAY)'],
            ['glpi_computers.date_mod', 1, 'DAY', 'date_alias', 'DATE_ADD(`glpi_computers`.`date_mod`, INTERVAL 1 DAY) AS `date_alias`'],
            ['glpi_computers.date_mod', 5, 'MONTH', null, 'DATE_ADD(`glpi_computers`.`date_mod`, INTERVAL 5 MONTH)'],
            ['glpi_tickets.date_creation', new QueryExpression('`glpi_tickets`.`time_to_own`'), 'SECOND', null, 'DATE_ADD(`glpi_tickets`.`date_creation`, INTERVAL `glpi_tickets`.`time_to_own` SECOND)'],
        ];
    }

    #[DataProvider('dateAddProvider')]
    public function testDateAdd($date, $interval, $interval_unit, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::dateAdd($date, $interval, $interval_unit, $alias)
        );
    }

    public static function concatProvider(): iterable
    {
        return [
            [
                [
                    new QueryExpression("'A'"),
                    new QueryExpression("'B'"),
                    new QueryExpression("'C'"),
                ], null, "CONCAT('A', 'B', 'C')",
            ],
            [
                [
                    new QueryExpression("'A'"),
                    new QueryExpression("'B'"),
                    new QueryExpression("'C'"),
                ], 'concat_alias', "CONCAT('A', 'B', 'C') AS `concat_alias`",
            ],
            [
                [
                    new QueryExpression("'A'"),
                    'glpi_computers.name',
                    new QueryExpression("'C'"),
                ], null, "CONCAT('A', `glpi_computers`.`name`, 'C')",
            ],
            [
                [
                    new QueryExpression("'A'"),
                    'glpi_computers.name',
                    new QueryExpression("'C'"),
                ], 'concat_alias', "CONCAT('A', `glpi_computers`.`name`, 'C') AS `concat_alias`",
            ],
        ];
    }

    #[DataProvider('concatProvider')]
    public function testConcat($params, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::concat($params, $alias)
        );
    }

    public static function concatWSProvider(): iterable
    {
        return [
            [
                new QueryExpression("','"),
                [
                    new QueryExpression("'A'"),
                    new QueryExpression("'B'"),
                    new QueryExpression("'C'"),
                ], null, "CONCAT_WS(',', 'A', 'B', 'C')",
            ],
            [
                new QueryExpression("'-'"),
                [
                    new QueryExpression("'A'"),
                    new QueryExpression("'B'"),
                    new QueryExpression("'C'"),
                ], 'concat_alias', "CONCAT_WS('-', 'A', 'B', 'C') AS `concat_alias`",
            ],
            [
                new QueryExpression("','"),
                [
                    new QueryExpression("'A'"),
                    'glpi_computers.name',
                    new QueryExpression("'C'"),
                ], null, "CONCAT_WS(',', 'A', `glpi_computers`.`name`, 'C')",
            ],
            [
                new QueryExpression("','"),
                [
                    new QueryExpression("'A'"),
                    'glpi_computers.name',
                    new QueryExpression("'C'"),
                ], 'concat_alias', "CONCAT_WS(',', 'A', `glpi_computers`.`name`, 'C') AS `concat_alias`",
            ],
        ];
    }

    #[DataProvider('concatWSProvider')]
    public function testConcatWS($separator, $params, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::concat_ws($separator, $params, $alias)
        );
    }

    public static function ifProvider(): iterable
    {
        return [
            ['glpi_computers.is_deleted', new QueryExpression("'deleted'"), new QueryExpression("'not deleted'"), null, "IF(`glpi_computers`.`is_deleted`, 'deleted', 'not deleted')"],
            ['glpi_computers.is_deleted', new QueryExpression("'deleted'"), new QueryExpression("'not deleted'"), 'if_alias', "IF(`glpi_computers`.`is_deleted`, 'deleted', 'not deleted') AS `if_alias`"],
            ['glpi_computers.is_deleted', 'glpi_computers.name', new QueryExpression("'not deleted'"), null, "IF(`glpi_computers`.`is_deleted`, `glpi_computers`.`name`, 'not deleted')"],
            ['glpi_computers.is_deleted', 'glpi_computers.name', new QueryExpression("'not deleted'"), 'if_alias', "IF(`glpi_computers`.`is_deleted`, `glpi_computers`.`name`, 'not deleted') AS `if_alias`"],
        ];
    }

    #[DataProvider('ifProvider')]
    public function testIf($condition, $true, $false, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::if($condition, $true, $false, $alias)
        );
    }

    public static function ifNullProvider(): iterable
    {
        return [
            ['`glpi_computers`.`name`', new QueryExpression("'unknown'"), null, "IFNULL(`glpi_computers`.`name`, 'unknown')"],
            ['`glpi_computers`.`name`', new QueryExpression("'unknown'"), 'ifnull_alias', "IFNULL(`glpi_computers`.`name`, 'unknown') AS `ifnull_alias`"],
            ['`glpi_computers`.`name`', '`glpi_computers`.`serial`', null, "IFNULL(`glpi_computers`.`name`, `glpi_computers`.`serial`)"],
            ['`glpi_computers`.`name`', '`glpi_computers`.`serial`', 'ifnull_alias', "IFNULL(`glpi_computers`.`name`, `glpi_computers`.`serial`) AS `ifnull_alias`"],
        ];
    }

    #[DataProvider('ifNullProvider')]
    public function testIfNUll($expression, $value, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::ifnull($expression, $value, $alias)
        );
    }

    public static function groupConcatProvider(): iterable
    {
        return [
            [
                'expression' => 'glpi_computers.name',
                'separator' => null,
                'distinct' => false,
                'order_by' => null,
                'alias' => null,
                'expected' => "GROUP_CONCAT(`glpi_computers`.`name`)",
            ],
            [
                'expression' => 'glpi_computers.name',
                'separator' => '',
                'distinct' => false,
                'order_by' => null,
                'alias' => null,
                'expected' => "GROUP_CONCAT(`glpi_computers`.`name`)",
            ],
            [
                'expression' => 'glpi_computers.name',
                'separator' => '_',
                'distinct' => false,
                'order_by' => null,
                'alias' => null,
                'expected' => "GROUP_CONCAT(`glpi_computers`.`name` SEPARATOR '_')",
            ],
            [
                'expression' => 'glpi_computers.name',
                'separator' => '_',
                'distinct' => true,
                'order_by' => null,
                'alias' => null,
                'expected' => "GROUP_CONCAT(DISTINCT `glpi_computers`.`name` SEPARATOR '_')",
            ],
            [
                'expression' => 'glpi_computers.name',
                'separator' => '_',
                'distinct' => true,
                'order_by' => 'glpi_computers.is_deleted',
                'alias' => null,
                'expected' => "GROUP_CONCAT(DISTINCT `glpi_computers`.`name` ORDER BY `glpi_computers`.`is_deleted` SEPARATOR '_')",
            ],
            [
                'expression' => 'glpi_computers.name',
                'separator' => '_',
                'distinct' => true,
                'order_by' => 'glpi_computers.is_deleted',
                'alias' => 'group_concat_alias',
                'expected' => "GROUP_CONCAT(DISTINCT `glpi_computers`.`name` ORDER BY `glpi_computers`.`is_deleted` SEPARATOR '_') AS `group_concat_alias`",
            ],
        ];
    }

    #[DataProvider('groupConcatProvider')]
    public function testGroupConcat($expression, $separator, $distinct, $order_by, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::groupConcat($expression, $separator, $distinct, $order_by, $alias)
        );
    }

    public static function floorProvider(): iterable
    {
        return [
            ['glpi_computers.name', null, "FLOOR(`glpi_computers`.`name`)"],
            ['glpi_computers.name', 'floor_alias', "FLOOR(`glpi_computers`.`name`) AS `floor_alias`"],
        ];
    }

    #[DataProvider('floorProvider')]
    public function testFloor($expression, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::floor($expression, $alias)
        );
    }

    public static function sumProvider(): iterable
    {
        return [
            ['glpi_computers.name', false, null, "SUM(`glpi_computers`.`name`)"],
            ['glpi_computers.name', false, 'sum_alias', "SUM(`glpi_computers`.`name`) AS `sum_alias`"],
            ['glpi_computers.name', true, null, "SUM(DISTINCT `glpi_computers`.`name`)"],
            ['glpi_computers.name', true, 'sum_alias', "SUM(DISTINCT `glpi_computers`.`name`) AS `sum_alias`"],
        ];
    }

    #[DataProvider('sumProvider')]
    public function testSum($expression, $distinct, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::sum($expression, $distinct, $alias)
        );
    }

    public static function countProvider(): iterable
    {
        return [
            ['glpi_computers.name', false, null, "COUNT(`glpi_computers`.`name`)"],
            ['glpi_computers.name', false, 'count_alias', "COUNT(`glpi_computers`.`name`) AS `count_alias`"],
            ['glpi_computers.name', true, null, "COUNT(DISTINCT `glpi_computers`.`name`)"],
            ['glpi_computers.name', true, 'count_alias', "COUNT(DISTINCT `glpi_computers`.`name`) AS `count_alias`"],
        ];
    }

    #[DataProvider('countProvider')]
    public function testCount($expression, $distinct, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::count($expression, $distinct, $alias)
        );
    }

    public static function minProvider(): iterable
    {
        return [
            ['glpi_computers.uuid', null, "MIN(`glpi_computers`.`uuid`)"],
            ['glpi_computers.uuid', 'min_alias', "MIN(`glpi_computers`.`uuid`) AS `min_alias`"],
        ];
    }

    #[DataProvider('minProvider')]
    public function testMin($expression, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::min($expression, $alias)
        );
    }

    public static function avgProvider(): iterable
    {
        return [
            ['glpi_tickets.waiting_duration', null, "AVG(`glpi_tickets`.`waiting_duration`)"],
            ['glpi_tickets.waiting_duration', 'avg_alias', "AVG(`glpi_tickets`.`waiting_duration`) AS `avg_alias`"],
        ];
    }

    #[DataProvider('avgProvider')]
    public function testAvg($expression, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::avg($expression, $alias)
        );
    }

    public static function castProvider(): iterable
    {
        return [
            ['glpi_computers.serial', 'INT', null, "CAST(`glpi_computers`.`serial` AS INT)"],
            ['glpi_computers.serial', 'INT', 'cast_alias', "CAST(`glpi_computers`.`serial` AS INT) AS `cast_alias`"],
        ];
    }

    #[DataProvider('castProvider')]
    public function testCast($expression, $type, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::cast($expression, $type, $alias)
        );
    }

    public static function convertProvider(): iterable
    {
        return [
            ['glpi_computers.name', 'utf8mb4', null, "CONVERT(`glpi_computers`.`name` USING utf8mb4)"],
            ['glpi_computers.name', 'utf8mb4', 'convert_alias', "CONVERT(`glpi_computers`.`name` USING utf8mb4) AS `convert_alias`"],
        ];
    }

    #[DataProvider('convertProvider')]
    public function testConvert($expression, $transcoding, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::convert($expression, $transcoding, $alias)
        );
    }

    public static function nowProvider(): iterable
    {
        return [
            [null, "NOW()"],
            ['now_alias', "NOW() AS `now_alias`"],
        ];
    }

    #[DataProvider('nowProvider')]
    public function testNow($alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::now($alias)
        );
    }

    public static function lowerProvider(): iterable
    {
        return [
            ['glpi_computers.name', null, "LOWER(`glpi_computers`.`name`)"],
            ['glpi_computers.name', 'lower_alias', "LOWER(`glpi_computers`.`name`) AS `lower_alias`"],
        ];
    }

    #[DataProvider('lowerProvider')]
    public function testLower($expression, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::lower($expression, $alias)
        );
    }

    public static function replaceProvider(): iterable
    {
        return [
            ['glpi_computers.name', 'glpi_computers.serial', 'glpi_computers.otherserial', null, "REPLACE(`glpi_computers`.`name`, `glpi_computers`.`serial`, `glpi_computers`.`otherserial`)"],
            ['glpi_computers.name', 'glpi_computers.serial', 'glpi_computers.otherserial', 'replace_alias', "REPLACE(`glpi_computers`.`name`, `glpi_computers`.`serial`, `glpi_computers`.`otherserial`) AS `replace_alias`"],
            ['glpi_computers.name', new QueryExpression("'test'"), 'glpi_computers.otherserial', null, "REPLACE(`glpi_computers`.`name`, 'test', `glpi_computers`.`otherserial`)"],
            ['glpi_computers.name', new QueryExpression("'test'"), 'glpi_computers.otherserial', 'replace_alias', "REPLACE(`glpi_computers`.`name`, 'test', `glpi_computers`.`otherserial`) AS `replace_alias`"],
            ['glpi_computers.name', new QueryExpression("'test'"), new QueryExpression("'test2'"), null, "REPLACE(`glpi_computers`.`name`, 'test', 'test2')"],
            ['glpi_computers.name', new QueryExpression("'test'"), new QueryExpression("'test2'"), 'replace_alias', "REPLACE(`glpi_computers`.`name`, 'test', 'test2') AS `replace_alias`"],
        ];
    }

    #[DataProvider('replaceProvider')]
    public function testReplace($expression, $search, $replace, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::replace($expression, $search, $replace, $alias)
        );
    }

    public static function unixTimestampProvider(): iterable
    {
        return [
            ['glpi_computers.date_mod', null, "UNIX_TIMESTAMP(`glpi_computers`.`date_mod`)"],
            ['glpi_computers.date_mod', 'unix_timestamp_alias', "UNIX_TIMESTAMP(`glpi_computers`.`date_mod`) AS `unix_timestamp_alias`"],
            [null, null, "UNIX_TIMESTAMP()"],
            [null, 'unix_now', "UNIX_TIMESTAMP() AS `unix_now`"],
        ];
    }

    #[DataProvider('unixTimestampProvider')]
    public function testUnixTimestamp($expression, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::unixTimestamp($expression, $alias)
        );
    }

    public static function fromUnixTimestampProvider(): iterable
    {
        return [
            [
                'expression' => 'glpi_computers.date_mod',
                'format' => null,
                'alias' => null,
                'expected' => "FROM_UNIXTIME(`glpi_computers`.`date_mod`)",
            ],
            [
                'expression' => 'glpi_computers.date_mod',
                'format' => null,
                'alias' => new QueryExpression('`from_unix_timestamp_alias`'),
                'expected' => "FROM_UNIXTIME(`glpi_computers`.`date_mod`) AS `from_unix_timestamp_alias`",
            ],
            [
                'expression' => 'glpi_computers.date_mod',
                'format' => new QueryExpression("'%Y-%m-%d'"),
                'alias' => null,
                'expected' => "FROM_UNIXTIME(`glpi_computers`.`date_mod`, '%Y-%m-%d')",
            ],
            [
                'expression' => 'glpi_computers.date_mod',
                'format' => new QueryExpression("'%Y-%m-%d'"),
                'alias' => 'from_unix_timestamp_alias',
                'expected' => "FROM_UNIXTIME(`glpi_computers`.`date_mod`, '%Y-%m-%d') AS `from_unix_timestamp_alias`",
            ],
        ];
    }

    #[DataProvider('fromUnixTimestampProvider')]
    public function testFromUnixTimestamp($expression, $format, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::fromUnixtime($expression, $format, $alias)
        );
    }

    public static function dateFormatProvider(): iterable
    {
        return [
            ['glpi_computers.date_mod', '%Y-%m-%d', null, "DATE_FORMAT(`glpi_computers`.`date_mod`, '%Y-%m-%d')"],
            ['glpi_computers.date_mod', '%Y-%m-%d', 'date_format_alias', "DATE_FORMAT(`glpi_computers`.`date_mod`, '%Y-%m-%d') AS `date_format_alias`"],
        ];
    }

    #[DataProvider('dateFormatProvider')]
    public function testDateFormat($expression, $format, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::dateFormat($expression, $format, $alias)
        );
    }

    public static function coalesceProvider(): iterable
    {
        return [
            [
                'params' => [
                    'glpi_computers.name',
                    'glpi_computers.serial',
                    'glpi_computers.otherserial',
                ],
                'alias' => null,
                'expected' => "COALESCE(`glpi_computers`.`name`, `glpi_computers`.`serial`, `glpi_computers`.`otherserial`)",
            ],
            [
                'params' => [
                    'glpi_computers.name',
                    'glpi_computers.serial',
                    'glpi_computers.otherserial',
                ],
                'alias' => 'coalesce_alias',
                'expected' => "COALESCE(`glpi_computers`.`name`, `glpi_computers`.`serial`, `glpi_computers`.`otherserial`) AS `coalesce_alias`",
            ],
        ];
    }

    #[DataProvider('coalesceProvider')]
    public function testCoalesce($params, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::coalesce($params, $alias)
        );
    }

    public static function leastProvider(): iterable
    {
        return [
            [
                'params' => [
                    'glpi_computers.date_mod',
                    'glpi_computers.date_creation',
                ],
                'alias' => null,
                'expected' => "LEAST(`glpi_computers`.`date_mod`, `glpi_computers`.`date_creation`)",
            ],
            [
                'params' => [
                    'glpi_computers.date_mod',
                    'glpi_computers.date_creation',
                ],
                'alias' => 'least_alias',
                'expected' => "LEAST(`glpi_computers`.`date_mod`, `glpi_computers`.`date_creation`) AS `least_alias`",
            ],
        ];
    }

    #[DataProvider('leastProvider')]
    public function testLeast($params, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::least($params, $alias)
        );
    }

    public static function timestampDiffProvider(): iterable
    {
        return [
            [
                'unit' => 'SECOND',
                'expression1' => 'glpi_computers.date_mod',
                'expression2' => 'glpi_computers.date_creation',
                'alias' => null,
                'expected' => "TIMESTAMPDIFF(SECOND, `glpi_computers`.`date_mod`, `glpi_computers`.`date_creation`)",
            ],
            [
                'unit' => 'SECOND',
                'expression1' => 'glpi_computers.date_mod',
                'expression2' => 'glpi_computers.date_creation',
                'alias' => 'timestampdiff_alias',
                'expected' => "TIMESTAMPDIFF(SECOND, `glpi_computers`.`date_mod`, `glpi_computers`.`date_creation`) AS `timestampdiff_alias`",
            ],
        ];
    }

    #[DataProvider('timestampDiffProvider')]
    public function testTimestampDiff($unit, $expression1, $expression2, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::timestampdiff($unit, $expression1, $expression2, $alias)
        );
    }

    public static function bitCountProvider(): iterable
    {
        return [
            [
                'expression' => 'glpi_ipnetworks.netmask_0',
                'alias' => null,
                'expected' => "BIT_COUNT(`glpi_ipnetworks`.`netmask_0`)",
            ],
            [
                'expression' => 'glpi_ipnetworks.netmask_0',
                'alias' => 'bit_count_alias',
                'expected' => "BIT_COUNT(`glpi_ipnetworks`.`netmask_0`) AS `bit_count_alias`",
            ],
        ];
    }

    #[DataProvider('bitCountProvider')]
    public function testBitCount($expression, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::bitCount($expression, $alias)
        );
    }

    public static function substringProvider(): iterable
    {
        return [
            ['glpi_computers.name', 0, 10, null, "SUBSTRING(`glpi_computers`.`name`, 0, 10)"],
            ['glpi_computers.name', 2, 8, 'substring_alias', "SUBSTRING(`glpi_computers`.`name`, 2, 8) AS `substring_alias`"],
            [new QueryExpression("'TestName'"), 0, 10, null, "SUBSTRING('TestName', 0, 10)"],
        ];
    }

    #[DataProvider('substringProvider')]
    public function testSubstring($expression, $start, $length, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::substring($expression, $start, $length, $alias)
        );
    }

    public static function greatestProvider(): iterable
    {
        return [
            [
                'params' => [
                    'glpi_computers.date_mod',
                    'glpi_computers.date_creation',
                ],
                'alias' => null,
                'expected' => "GREATEST(`glpi_computers`.`date_mod`, `glpi_computers`.`date_creation`)",
            ],
            [
                'params' => [
                    'glpi_computers.date_mod',
                    'glpi_computers.date_creation',
                ],
                'alias' => 'greatest_alias',
                'expected' => "GREATEST(`glpi_computers`.`date_mod`, `glpi_computers`.`date_creation`) AS `greatest_alias`",
            ],
        ];
    }

    #[DataProvider('greatestProvider')]
    public function testGreatest($params, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::greatest($params, $alias)
        );
    }

    public static function yearProvider(): iterable
    {
        return [
            [
                'expression' => 'glpi_computers.date_mod',
                'alias' => null,
                'expected' => "YEAR(`glpi_computers`.`date_mod`)",
            ],
            [
                'expression' => 'glpi_computers.date_mod',
                'alias' => 'year_alias',
                'expected' => "YEAR(`glpi_computers`.`date_mod`) AS `year_alias`",
            ],
            [
                'expression' => new QueryExpression("'2023-01-01'"),
                'alias' => null,
                'expected' => "YEAR('2023-01-01')",
            ],
        ];
    }

    #[DataProvider('yearProvider')]
    public function testYear($expression, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::year($expression, $alias)
        );
    }

    public static function timeDiffProvider(): iterable
    {
        return [
            [
                'expression1' => 'glpi_computers.date_mod',
                'expression2' => 'glpi_computers.date_creation',
                'alias' => null,
                'expected' => "TIMEDIFF(`glpi_computers`.`date_mod`, `glpi_computers`.`date_creation`)",
            ],
            [
                'expression1' => 'glpi_computers.date_mod',
                'expression2' => 'glpi_computers.date_creation',
                'alias' => 'timediff_alias',
                'expected' => "TIMEDIFF(`glpi_computers`.`date_mod`, `glpi_computers`.`date_creation`) AS `timediff_alias`",
            ],
            [
                'expression1' => new QueryExpression("'2023-01-01 00:00:00'"),
                'expression2' => 'glpi_computers.date_creation',
                'alias' => null,
                'expected' => "TIMEDIFF('2023-01-01 00:00:00', `glpi_computers`.`date_creation`)",
            ],
        ];
    }

    #[DataProvider('timeDiffProvider')]
    public function testTimeDiff($expression1, $expression2, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::timediff($expression1, $expression2, $alias)
        );
    }

    public static function locateProvider(): iterable
    {
        return [
            [
                'expression' => 'glpi_computers.name',
                'substring' => 'test',
                'alias' => null,
                'expected' => "LOCATE('test', `glpi_computers`.`name`)",
            ],
            [
                'expression' => 'glpi_computers.name',
                'substring' => 'test',
                'alias' => 'locate_alias',
                'expected' => "LOCATE('test', `glpi_computers`.`name`) AS `locate_alias`",
            ],
            [
                'expression' => 'glpi_computers.name',
                'substring' => new QueryExpression('`glpi_computers`.`serial`'),
                'alias' => null,
                'expected' => "LOCATE(`glpi_computers`.`serial`, `glpi_computers`.`name`)",
            ],
        ];
    }

    #[DataProvider('locateProvider')]
    public function testLocate($substring, $expression, $alias, $expected): void
    {
        $this->assertSame(
            $expected,
            (string) QueryFunction::locate($substring, $expression, $alias)
        );
    }

    /**
     * Values bound by an argument must survive the concatenation performed to build the call.
     *
     * These used to get dropped: the argument was cast to a string through `__toString()`, which
     * renders the SQL only, so the statement ended up with more `?` placeholders than bound
     * values, shifting the positional binding of everything that followed.
     */
    public static function parameterPropagationProvider(): iterable
    {
        $sub = static fn(int $value): QueryExpression => new QueryExpression('(SELECT x FROM t WHERE y = ?)', values: [$value]);

        yield 'dateAdd interval' => [
            static fn() => QueryFunction::dateAdd(new QueryIdentifier('date_creation'), $sub(42), 'SECOND'),
            'DATE_ADD(`date_creation`, INTERVAL (SELECT x FROM t WHERE y = ?) SECOND)',
            [42],
        ];
        yield 'dateAdd date' => [
            static fn() => QueryFunction::dateAdd($sub(42), 3, 'DAY'),
            'DATE_ADD((SELECT x FROM t WHERE y = ?), INTERVAL 3 DAY)',
            [42],
        ];
        yield 'dateSub interval' => [
            static fn() => QueryFunction::dateSub(new QueryIdentifier('date_creation'), $sub(42), 'SECOND'),
            'DATE_SUB(`date_creation`, INTERVAL (SELECT x FROM t WHERE y = ?) SECOND)',
            [42],
        ];
        yield 'concat_ws separator and elements' => [
            static fn() => QueryFunction::concat_ws($sub(1), [$sub(2), new QueryIdentifier('a.b'), $sub(3)]),
            'CONCAT_WS((SELECT x FROM t WHERE y = ?), (SELECT x FROM t WHERE y = ?), `a`.`b`, (SELECT x FROM t WHERE y = ?))',
            [1, 2, 3],
        ];
        yield 'groupConcat order by' => [
            static fn() => QueryFunction::groupConcat(
                new QueryIdentifier('a.x'),
                ',',
                false,
                new QueryExpression('CASE WHEN z = ? END', values: [9])
            ),
            "GROUP_CONCAT(`a`.`x` ORDER BY CASE WHEN z = ? END SEPARATOR ',')",
            [9],
        ];
        yield 'groupConcat order by inside an array' => [
            static fn() => QueryFunction::groupConcat(
                new QueryIdentifier('a.x'),
                null,
                false,
                ['a.y', new QueryExpression('CASE WHEN z = ? END', values: [9])]
            ),
            'GROUP_CONCAT(`a`.`x` ORDER BY `a`.`y`, CASE WHEN z = ? END)',
            [9],
        ];
        yield 'if' => [
            static fn() => QueryFunction::if($sub(1), $sub(2), $sub(3)),
            'IF((SELECT x FROM t WHERE y = ?), (SELECT x FROM t WHERE y = ?), (SELECT x FROM t WHERE y = ?))',
            [1, 2, 3],
        ];
        yield 'if with array criteria' => [
            static fn() => QueryFunction::if(['a.x' => 5], new QueryExpression("'yes'"), new QueryExpression("'no'")),
            "IF(`a`.`x` = ?, 'yes', 'no')",
            [5],
        ];
        yield 'cast' => [
            static fn() => QueryFunction::cast($sub(1), 'CHAR'),
            'CAST((SELECT x FROM t WHERE y = ?) AS CHAR)',
            [1],
        ];
        yield 'convert' => [
            static fn() => QueryFunction::convert($sub(1), 'utf8mb4'),
            'CONVERT((SELECT x FROM t WHERE y = ?) USING utf8mb4)',
            [1],
        ];
        yield 'sum distinct' => [
            static fn() => QueryFunction::sum($sub(1), true),
            'SUM(DISTINCT (SELECT x FROM t WHERE y = ?))',
            [1],
        ];
        yield 'magic call with an array' => [
            static fn() => QueryFunction::concat([$sub(1), $sub(2)]),
            'CONCAT((SELECT x FROM t WHERE y = ?), (SELECT x FROM t WHERE y = ?))',
            [1, 2],
        ];
        yield 'nested calls' => [
            static fn() => QueryFunction::ifnull(QueryFunction::cast($sub(1), 'CHAR'), $sub(2)),
            'IFNULL(CAST((SELECT x FROM t WHERE y = ?) AS CHAR), (SELECT x FROM t WHERE y = ?))',
            [1, 2],
        ];
    }

    /**
     * @param callable(): QueryExpression $build
     * @param array<int, mixed> $expected_params
     */
    #[DataProvider('parameterPropagationProvider')]
    public function testParametersArePropagated(callable $build, string $expected_sql, array $expected_params): void
    {
        $result = $build();

        $this->assertSame($expected_sql, (string) $result);
        $this->assertSame($expected_params, $result->getParams());
        // a mismatch here is what silently shifts the binding of the whole statement
        $this->assertSame(substr_count($expected_sql, '?'), count($result->getParams()));
    }

    /**
     * Parameters are positional, so they must be collected in the order the SQL is assembled.
     */
    public function testParametersKeepTheEmissionOrder(): void
    {
        $result = QueryFunction::concat([
            new QueryValue('first'),
            new QueryValue('second'),
            new QueryValue('third'),
        ]);

        $this->assertSame('CONCAT(?, ?, ?)', (string) $result);
        $this->assertSame(['first', 'second', 'third'], $result->getParams());
    }

    public static function queryValueProvider(): iterable
    {
        yield 'aggregate' => [static fn() => QueryFunction::sum(new QueryValue(5)), 'SUM(?)', [5]];
        yield 'ifnull' => [
            static fn() => QueryFunction::ifnull(new QueryIdentifier('a.x'), new QueryValue('fallback')),
            'IFNULL(`a`.`x`, ?)',
            ['fallback'],
        ];
        yield 'cast' => [static fn() => QueryFunction::cast(new QueryValue(7), 'CHAR'), 'CAST(? AS CHAR)', [7]];
        yield 'date interval' => [
            static fn() => QueryFunction::dateAdd(new QueryIdentifier('a.d'), new QueryValue(3), 'DAY'),
            'DATE_ADD(`a`.`d`, INTERVAL ? DAY)',
            [3],
        ];
        yield 'concat_ws' => [
            static fn() => QueryFunction::concat_ws(new QueryValue('-'), [new QueryValue('a'), new QueryIdentifier('b.c')]),
            'CONCAT_WS(?, ?, `b`.`c`)',
            ['-', 'a'],
        ];
    }

    /**
     * @param callable(): QueryExpression $build
     * @param array<int, mixed> $expected_params
     */
    #[DataProvider('queryValueProvider')]
    public function testAQueryValueIsBoundInsteadOfInlined(callable $build, string $expected_sql, array $expected_params): void
    {
        $result = $build();

        $this->assertSame($expected_sql, (string) $result);
        $this->assertSame($expected_params, $result->getParams());
    }

    public function testNullArgumentsAreIgnored(): void
    {
        $this->assertSame('UNIX_TIMESTAMP()', (string) QueryFunction::unixTimestamp());
        $this->assertSame(
            'CONCAT_WS(?, `a`.`b`)',
            (string) QueryFunction::concat_ws(new QueryValue('-'), [new QueryIdentifier('a.b'), null])
        );
    }

    /**
     * A bare string identifier keeps working; it is only the less explicit form.
     */
    public function testABareStringIsStillTreatedAsAnIdentifier(): void
    {
        $bare = QueryFunction::sum('glpi_computers.id');

        $this->assertSame('SUM(`glpi_computers`.`id`)', (string) $bare);
        $this->assertSame([], $bare->getParams());
        $this->assertSame(
            (string) QueryFunction::sum(new QueryIdentifier('glpi_computers.id')),
            (string) $bare
        );
    }
}
