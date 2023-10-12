<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace tests\units\Glpi\DBAL;

use Glpi\DBAL\QueryExpression;

class QueryFunction extends \GLPITestCase
{
    protected function dateAddProvider()
    {
        return [
            ['glpi_computers.date_mod', 1, 'DAY', null, 'DATE_ADD(`glpi_computers`.`date_mod`, INTERVAL 1 DAY)'],
            ['glpi_computers.date_mod', '5-1', 'DAY', null, 'DATE_ADD(`glpi_computers`.`date_mod`, INTERVAL \'5-1\' DAY)'],
            ['glpi_computers.date_mod', 1, 'DAY', 'date_alias', 'DATE_ADD(`glpi_computers`.`date_mod`, INTERVAL 1 DAY) AS `date_alias`'],
            ['glpi_computers.date_mod', 5, 'MONTH', null, 'DATE_ADD(`glpi_computers`.`date_mod`, INTERVAL 5 MONTH)'],
            ['glpi_tickets.date_creation', new QueryExpression('`glpi_tickets`.`time_to_own`'), 'SECOND', null, 'DATE_ADD(`glpi_tickets`.`date_creation`, INTERVAL `glpi_tickets`.`time_to_own` SECOND)'],
        ];
    }

    /**
     * @dataProvider dateAddProvider
     */
    public function testDateAdd($date, $interval, $interval_unit, $alias, $expected)
    {
        $this->string((string) \Glpi\DBAL\QueryFunction::dateAdd($date, $interval, $interval_unit, $alias))->isIdenticalTo($expected);
    }

    protected function concatProvider()
    {
        return [
            [
                [
                    new QueryExpression("'A'"),
                    new QueryExpression("'B'"),
                    new QueryExpression("'C'")
                ], null, "CONCAT('A', 'B', 'C')"
            ],
            [
                [
                    new QueryExpression("'A'"),
                    new QueryExpression("'B'"),
                    new QueryExpression("'C'")
                ], 'concat_alias', "CONCAT('A', 'B', 'C') AS `concat_alias`"
            ],
            [
                [
                    new QueryExpression("'A'"),
                    'glpi_computers.name',
                    new QueryExpression("'C'")
                ], null, "CONCAT('A', `glpi_computers`.`name`, 'C')"
            ],
            [
                [
                    new QueryExpression("'A'"),
                    'glpi_computers.name',
                    new QueryExpression("'C'")
                ], 'concat_alias', "CONCAT('A', `glpi_computers`.`name`, 'C') AS `concat_alias`"
            ],
        ];
    }

    /**
     * @dataProvider concatProvider
     */
    public function testConcat($params, $alias, $expected)
    {
        $this->string((string) \Glpi\DBAL\QueryFunction::concat($params, $alias))->isIdenticalTo($expected);
    }

    protected function ifProvider()
    {
        return [
            ['glpi_computers.is_deleted', new QueryExpression("'deleted'"), new QueryExpression("'not deleted'"), null, "IF(`glpi_computers`.`is_deleted`, 'deleted', 'not deleted')"],
            ['glpi_computers.is_deleted', new QueryExpression("'deleted'"), new QueryExpression("'not deleted'"), 'if_alias', "IF(`glpi_computers`.`is_deleted`, 'deleted', 'not deleted') AS `if_alias`"],
            ['glpi_computers.is_deleted', 'glpi_computers.name', new QueryExpression("'not deleted'"), null, "IF(`glpi_computers`.`is_deleted`, `glpi_computers`.`name`, 'not deleted')"],
            ['glpi_computers.is_deleted', 'glpi_computers.name', new QueryExpression("'not deleted'"), 'if_alias', "IF(`glpi_computers`.`is_deleted`, `glpi_computers`.`name`, 'not deleted') AS `if_alias`"],
        ];
    }

    /**
     * @dataProvider ifProvider
     */
    public function testIf($condition, $true, $false, $alias, $expected)
    {
        $this->string((string) \Glpi\DBAL\QueryFunction::if($condition, $true, $false, $alias))->isIdenticalTo($expected);
    }

    protected function ifNullProvider()
    {
        return [
            ['`glpi_computers`.`name`', new QueryExpression("'unknown'"), null, "IFNULL(`glpi_computers`.`name`, 'unknown')"],
            ['`glpi_computers`.`name`', new QueryExpression("'unknown'"), 'ifnull_alias', "IFNULL(`glpi_computers`.`name`, 'unknown') AS `ifnull_alias`"],
            ['`glpi_computers`.`name`', '`glpi_computers`.`serial`', null, "IFNULL(`glpi_computers`.`name`, `glpi_computers`.`serial`)"],
            ['`glpi_computers`.`name`', '`glpi_computers`.`serial`', 'ifnull_alias', "IFNULL(`glpi_computers`.`name`, `glpi_computers`.`serial`) AS `ifnull_alias`"],
        ];
    }

    /**
     * @dataProvider ifNullProvider
     */
    public function testIfNUll($expression, $value, $alias, $expected)
    {
        $this->string((string) \Glpi\DBAL\QueryFunction::ifnull($expression, $value, $alias))->isIdenticalTo($expected);
    }

    protected function groupConcatProvider()
    {
        return [
            [
                'expression' => 'glpi_computers.name',
                'separator' => null,
                'distinct' => false,
                'order_by' => null,
                'alias' => null,
                'expected' => "GROUP_CONCAT(`glpi_computers`.`name`)"
            ],
            [
                'expression' => 'glpi_computers.name',
                'separator' => '',
                'distinct' => false,
                'order_by' => null,
                'alias' => null,
                'expected' => "GROUP_CONCAT(`glpi_computers`.`name`)"
            ],
            [
                'expression' => 'glpi_computers.name',
                'separator' => '_',
                'distinct' => false,
                'order_by' => null,
                'alias' => null,
                'expected' => "GROUP_CONCAT(`glpi_computers`.`name` SEPARATOR '_')"
            ],
            [
                'expression' => 'glpi_computers.name',
                'separator' => '_',
                'distinct' => true,
                'order_by' => null,
                'alias' => null,
                'expected' => "GROUP_CONCAT(DISTINCT `glpi_computers`.`name` SEPARATOR '_')"
            ],
            [
                'expression' => 'glpi_computers.name',
                'separator' => '_',
                'distinct' => true,
                'order_by' => 'glpi_computers.is_deleted',
                'alias' => null,
                'expected' => "GROUP_CONCAT(DISTINCT `glpi_computers`.`name` ORDER BY `glpi_computers`.`is_deleted` SEPARATOR '_')"
            ],
            [
                'expression' => 'glpi_computers.name',
                'separator' => '_',
                'distinct' => true,
                'order_by' => 'glpi_computers.is_deleted',
                'alias' => 'group_concat_alias',
                'expected' => "GROUP_CONCAT(DISTINCT `glpi_computers`.`name` ORDER BY `glpi_computers`.`is_deleted` SEPARATOR '_') AS `group_concat_alias`"
            ],
        ];
    }

    /**
     * @dataProvider groupConcatProvider
     */
    public function testGroupConcat($expression, $separator, $distinct, $order_by, $alias, $expected)
    {
        $this->string((string) \Glpi\DBAL\QueryFunction::groupConcat($expression, $separator, $distinct, $order_by, $alias))->isIdenticalTo($expected);
    }

    protected function floorProvider()
    {
        return [
            ['glpi_computers.name', null, "FLOOR(`glpi_computers`.`name`)"],
            ['glpi_computers.name', 'floor_alias', "FLOOR(`glpi_computers`.`name`) AS `floor_alias`"],
        ];
    }

    /**
     * @dataProvider floorProvider
     */
    public function testFloor($expression, $alias, $expected)
    {
        $this->string((string) \Glpi\DBAL\QueryFunction::floor($expression, $alias))->isIdenticalTo($expected);
    }

    protected function sumProvider()
    {
        return [
            ['glpi_computers.name', false, null, "SUM(`glpi_computers`.`name`)"],
            ['glpi_computers.name', false, 'sum_alias', "SUM(`glpi_computers`.`name`) AS `sum_alias`"],
            ['glpi_computers.name', true, null, "SUM(DISTINCT `glpi_computers`.`name`)"],
            ['glpi_computers.name', true, 'sum_alias', "SUM(DISTINCT `glpi_computers`.`name`) AS `sum_alias`"],
        ];
    }

    /**
     * @dataProvider sumProvider
     */
    public function testSum($expression, $distinct, $alias, $expected)
    {
        $this->string((string) \Glpi\DBAL\QueryFunction::sum($expression, $distinct, $alias))->isIdenticalTo($expected);
    }

    protected function countProvider()
    {
        return [
            ['glpi_computers.name', false, null, "COUNT(`glpi_computers`.`name`)"],
            ['glpi_computers.name', false, 'count_alias', "COUNT(`glpi_computers`.`name`) AS `count_alias`"],
            ['glpi_computers.name', true, null, "COUNT(DISTINCT `glpi_computers`.`name`)"],
            ['glpi_computers.name', true, 'count_alias', "COUNT(DISTINCT `glpi_computers`.`name`) AS `count_alias`"],
        ];
    }

    /**
     * @dataProvider countProvider
     */
    public function testCount($expression, $distinct, $alias, $expected)
    {
        $this->string((string) \Glpi\DBAL\QueryFunction::count($expression, $distinct, $alias))->isIdenticalTo($expected);
    }

    protected function minProvider()
    {
        return [
            ['glpi_computers.uuid', null, "MIN(`glpi_computers`.`uuid`)"],
            ['glpi_computers.uuid', 'min_alias', "MIN(`glpi_computers`.`uuid`) AS `min_alias`"],
        ];
    }

    /**
     * @dataProvider minProvider
     */
    public function testMin($expression, $alias, $expected)
    {
        $this->string((string) \Glpi\DBAL\QueryFunction::min($expression, $alias))->isIdenticalTo($expected);
    }

    protected function avgProvider()
    {
        return [
            ['glpi_tickets.waiting_duration', null, "AVG(`glpi_tickets`.`waiting_duration`)"],
            ['glpi_tickets.waiting_duration', 'avg_alias', "AVG(`glpi_tickets`.`waiting_duration`) AS `avg_alias`"],
        ];
    }

    /**
     * @dataProvider avgProvider
     */
    public function testAvg($expression, $alias, $expected)
    {
        $this->string((string) \Glpi\DBAL\QueryFunction::avg($expression, $alias))->isIdenticalTo($expected);
    }

    protected function castProvider()
    {
        return [
            ['glpi_computers.serial', 'INT', null, "CAST(`glpi_computers`.`serial` AS INT)"],
            ['glpi_computers.serial', 'INT', 'cast_alias', "CAST(`glpi_computers`.`serial` AS INT) AS `cast_alias`"],
        ];
    }

    /**
     * @dataProvider castProvider
     */
    public function testCast($expression, $type, $alias, $expected)
    {
        $this->string((string) \Glpi\DBAL\QueryFunction::cast($expression, $type, $alias))->isIdenticalTo($expected);
    }

    protected function convertProvider()
    {
        return [
            ['glpi_computers.name', 'utf8mb4', null, "CONVERT(`glpi_computers`.`name` USING utf8mb4)"],
            ['glpi_computers.name', 'utf8mb4', 'convert_alias', "CONVERT(`glpi_computers`.`name` USING utf8mb4) AS `convert_alias`"],
        ];
    }

    /**
     * @dataProvider convertProvider
     */
    public function testConvert($expression, $transcoding, $alias, $expected)
    {
        $this->string((string) \Glpi\DBAL\QueryFunction::convert($expression, $transcoding, $alias))->isIdenticalTo($expected);
    }

    protected function nowProvider()
    {
        return [
            [null, "NOW()"],
            ['now_alias', "NOW() AS `now_alias`"],
        ];
    }

    /**
     * @dataProvider nowProvider
     */
    public function testNow($alias, $expected)
    {
        $this->string((string) \Glpi\DBAL\QueryFunction::now($alias))->isIdenticalTo($expected);
    }

    protected function lowerProvider()
    {
        return [
            ['glpi_computers.name', null, "LOWER(`glpi_computers`.`name`)"],
            ['glpi_computers.name', 'lower_alias', "LOWER(`glpi_computers`.`name`) AS `lower_alias`"],
        ];
    }

    /**
     * @dataProvider lowerProvider
     */
    public function testLower($expression, $alias, $expected)
    {
        $this->string((string) \Glpi\DBAL\QueryFunction::lower($expression, $alias))->isIdenticalTo($expected);
    }

    protected function replaceProvider()
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

    /**
     * @dataProvider replaceProvider
     */
    public function testReplace($expression, $search, $replace, $alias, $expected)
    {
        $this->string((string) \Glpi\DBAL\QueryFunction::replace($expression, $search, $replace, $alias))->isIdenticalTo($expected);
    }

    protected function unixTimestampProvider()
    {
        return [
            ['glpi_computers.date_mod', null, "UNIX_TIMESTAMP(`glpi_computers`.`date_mod`)"],
            ['glpi_computers.date_mod', 'unix_timestamp_alias', "UNIX_TIMESTAMP(`glpi_computers`.`date_mod`) AS `unix_timestamp_alias`"],
            [null, null, "UNIX_TIMESTAMP()"],
            [null, 'unix_now', "UNIX_TIMESTAMP() AS `unix_now`"],
        ];
    }

    /**
     * @dataProvider unixTimestampProvider
     */
    public function testUnixTimestamp($expression, $alias, $expected)
    {
        $this->string((string) \Glpi\DBAL\QueryFunction::unixTimestamp($expression, $alias))->isIdenticalTo($expected);
    }

    protected function fromUnixTimestampProvider()
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

    /**
     * @dataProvider fromUnixTimestampProvider
     */
    public function testFromUnixTimestamp($expression, $format, $alias, $expected)
    {
        $this->string((string) \Glpi\DBAL\QueryFunction::fromUnixtime($expression, $format, $alias))->isIdenticalTo($expected);
    }

    protected function dateFormatProvider()
    {
        return [
            ['glpi_computers.date_mod', '%Y-%m-%d', null, "DATE_FORMAT(`glpi_computers`.`date_mod`, '%Y-%m-%d')"],
            ['glpi_computers.date_mod', '%Y-%m-%d', 'date_format_alias', "DATE_FORMAT(`glpi_computers`.`date_mod`, '%Y-%m-%d') AS `date_format_alias`"],
        ];
    }

    /**
     * @dataProvider dateFormatProvider
     */
    public function testDateFormat($expression, $format, $alias, $expected)
    {
        $this->string((string) \Glpi\DBAL\QueryFunction::dateFormat($expression, $format, $alias))->isIdenticalTo($expected);
    }

    protected function coalesceProvider()
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

    /**
     * @dataProvider coalesceProvider
     */
    public function testCoalesce($params, $alias, $expected)
    {
        $this->string((string) \Glpi\DBAL\QueryFunction::coalesce($params, $alias))->isIdenticalTo($expected);
    }

    protected function leastProvider()
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

    /**
     * @dataProvider leastProvider
     */
    public function testLeast($params, $alias, $expected)
    {
        $this->string((string) \Glpi\DBAL\QueryFunction::least($params, $alias))->isIdenticalTo($expected);
    }

    protected function timestampDiffProvider()
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

    /**
     * @dataProvider timestampDiffProvider
     */
    public function testTimestampDiff($unit, $expression1, $expression2, $alias, $expected)
    {
        $this->string((string) \Glpi\DBAL\QueryFunction::timestampdiff($unit, $expression1, $expression2, $alias))->isIdenticalTo($expected);
    }

    protected function bitCountProvider()
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

    /**
     * @dataProvider bitCountProvider
     */
    public function testBitCount($expression, $alias, $expected)
    {
        $this->string((string) \Glpi\DBAL\QueryFunction::bitCount($expression, $alias))->isIdenticalTo($expected);
    }

    protected function substringProvider()
    {
        return [
            ['glpi_computers.name', 0, 10, null, "SUBSTRING(`glpi_computers`.`name`, 0, 10)"],
            ['glpi_computers.name', 2, 8, 'substring_alias', "SUBSTRING(`glpi_computers`.`name`, 2, 8) AS `substring_alias`"],
            [new QueryExpression("'TestName'"), 0, 10, null, "SUBSTRING('TestName', 0, 10)"],
        ];
    }

    /**
     * @dataProvider substringProvider
     */
    public function testSubstring($expression, $start, $length, $alias, $expected)
    {
        $this->string((string) \Glpi\DBAL\QueryFunction::substring($expression, $start, $length, $alias))->isIdenticalTo($expected);
    }

    protected function greatestProvider()
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

    /**
     * @dataProvider greatestProvider
     */
    public function testGreatest($params, $alias, $expected)
    {
        $this->string((string) \Glpi\DBAL\QueryFunction::greatest($params, $alias))->isIdenticalTo($expected);
    }

    protected function yearProvider()
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
            ]
        ];
    }

    /**
     * @dataProvider yearProvider
     */
    public function testYear($expression, $alias, $expected)
    {
        $this->string((string) \Glpi\DBAL\QueryFunction::year($expression, $alias))->isIdenticalTo($expected);
    }

    protected function timeDiffProvider()
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

    /**
     * @dataProvider timeDiffProvider
     */
    public function testTimeDiff($expression1, $expression2, $alias, $expected)
    {
        $this->string((string) \Glpi\DBAL\QueryFunction::timediff($expression1, $expression2, $alias))->isIdenticalTo($expected);
    }
}
