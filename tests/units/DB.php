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

namespace tests\units;

use Psr\Log\LogLevel;

/* Test for inc/dbmysql.class.php */

class DB extends \GLPITestCase
{
    public function testTableExist()
    {
        $this
         ->if($this->newTestedInstance)
         ->then
            ->boolean($this->testedInstance->tableExists('glpi_configs'))->isTrue()
            ->boolean($this->testedInstance->tableExists('fakeTable'))->isFalse();
    }

    public function testFieldExists()
    {
        $this
         ->if($this->newTestedInstance)
         ->then
            ->boolean($this->testedInstance->fieldExists('glpi_configs', 'id'))->isTrue()
            ->boolean($this->testedInstance->fieldExists('glpi_configs', 'ID'))->isFalse()
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

    protected function dataName()
    {
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
    public function testQuoteName($raw, $quoted)
    {
        $this->string(\DBmysql::quoteName($raw))->isIdenticalTo($quoted);
    }

    protected function dataValue()
    {
        return [
            ['foo', "'foo'"],
            ['bar', "'bar'"],
            ['42', "'42'"],
            ['+33', "'+33'"],
            [null, 'NULL'],
            ['null', 'NULL'],
            ['NULL', 'NULL'],
            [new \QueryExpression('`field`'), '`field`'],
            ['`field', "'`field'"],
            [false, "'0'"],
            [true, "'1'"],
            ['Glpi\Socket', "'Glpi\\\Socket'"],
        ];
    }

    /**
     * @dataProvider dataValue
     */
    public function testQuoteValue($raw, $expected)
    {
        $this->string(\DBmysql::quoteValue($raw))->isIdenticalTo($expected);
    }


    protected function dataInsert()
    {
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
            ], [
                'table', [
                    'field'  => new \QueryParam(),
                    'other'  => new \QueryParam()
                ],
                'INSERT INTO `table` (`field`, `other`) VALUES (?, ?)'
            ], [
                'table', new \QuerySubQuery([
                    'SELECT' => ['id', 'name'],
                    'FROM' => 'other',
                    'WHERE' => ['NOT' => ['name' => null]]
                ]),
                'INSERT INTO `table` (SELECT `id`, `name` FROM `other` WHERE NOT (`name` IS NULL))'
            ]/*, [
                'table', [
                    'field'  => new \QueryParam('field'),
                    'other'  => new \QueryParam('other')
                ],
                'INSERT INTO `table` (`field`, `other`) VALUES (:field, :other)'
            ]*/ //mysqli does not support named parameters
        ];
    }

    /**
     * @dataProvider dataInsert
     */
    public function testBuildInsert($table, $values, $expected)
    {
        $this
         ->if($this->newTestedInstance)
         ->then
            ->string($this->testedInstance->buildInsert($table, $values))->isIdenticalTo($expected);
    }

    protected function dataUpdate()
    {
        return [
            [
                'table', [
                    'field'  => 'value',
                    'other'  => 'doe'
                ], [
                    'id'  => 1
                ],
                [],
                'UPDATE `table` SET `field` = \'value\', `other` = \'doe\' WHERE `id` = \'1\''
            ], [
                'table', [
                    'field'  => 'value'
                ], [
                    'id'  => [1, 2]
                ],
                [],
                'UPDATE `table` SET `field` = \'value\' WHERE `id` IN (\'1\', \'2\')'
            ], [
                'table', [
                    'field'  => 'value'
                ], [
                    'NOT'  => ['id' => [1, 2]]
                ],
                [],
                'UPDATE `table` SET `field` = \'value\' WHERE  NOT (`id` IN (\'1\', \'2\'))'
            ], [
                'table', [
                    'field'  => new \QueryParam()
                ], [
                    'NOT' => ['id' => [new \QueryParam(), new \QueryParam()]]
                ],
                [],
                'UPDATE `table` SET `field` = ? WHERE  NOT (`id` IN (?, ?))'
            ], [
                /*'table', [
                    'field'  => new \QueryParam('field')
                ], [
                    'NOT' => ['id' => [new \QueryParam('idone'), new \QueryParam('idtwo')]]
                ],
                [],
                'UPDATE `table` SET `field` = :field WHERE  NOT (`id` IN (:idone, :idtwo))'
            ], [*/
                'table', [
                    'field'  => new \QueryExpression(\DBmysql::quoteName('field') . ' + 1')
                ], [
                    'id'  => [1, 2]
                ],
                [],
                'UPDATE `table` SET `field` = `field` + 1 WHERE `id` IN (\'1\', \'2\')'
            ], [
                'table', [
                    'field'  => new \QueryExpression(\DBmysql::quoteName('field') . ' + 1')
                ], [
                    'id'  => [1, 2]
                ],
                [],
                'UPDATE `table` SET `field` = `field` + 1 WHERE `id` IN (\'1\', \'2\')'
            ], [
                'table', [
                    'field'  => 'value'
                ], [
                    'id'  => [1, 2]
                ],
                [
                    'LEFT JOIN' => [
                        'another_table' => [
                            'ON' => [
                                'table'         => 'foreign_id',
                                'another_table' => 'id'
                            ]
                        ],
                        'table_3' => [
                            'ON' => [
                                'another_table' => 'some_id',
                                'table_3'       => 'id'
                            ]
                        ]
                    ]
                ],
                'UPDATE `table`'
                . ' LEFT JOIN `another_table` ON (`table`.`foreign_id` = `another_table`.`id`)'
                . ' LEFT JOIN `table_3` ON (`another_table`.`some_id` = `table_3`.`id`)'
                . ' SET `field` = \'value\' WHERE `id` IN (\'1\', \'2\')'
            ]
        ];
    }

    /**
     * @dataProvider dataUpdate
     */
    public function testBuildUpdate($table, $values, $where, array $joins, $expected)
    {
        $this
         ->if($this->newTestedInstance)
         ->then
            ->string($this->testedInstance->buildUpdate($table, $values, $where, $joins))->isIdenticalTo($expected);
    }

    public function testBuildUpdateWException()
    {
        $this->exception(
            function () {
                $this
                  ->if($this->newTestedInstance)
                  ->then
                  ->string($this->testedInstance->buildUpdate('table', ['a' => 'b'], []))->isIdenticalTo('');
            }
        )->hasMessage('Cannot run an UPDATE query without WHERE clause!');
    }

    protected function dataDelete()
    {
        return [
            [
                'table', [
                    'id'  => 1
                ],
                [],
                'DELETE `table` FROM `table` WHERE `id` = \'1\''
            ], [
                'table', [
                    'id'  => [1, 2]
                ],
                [],
                'DELETE `table` FROM `table` WHERE `id` IN (\'1\', \'2\')'
            ], [
                'table', [
                    'NOT'  => ['id' => [1, 2]]
                ],
                [],
                'DELETE `table` FROM `table` WHERE  NOT (`id` IN (\'1\', \'2\'))'
            ], [
                'table', [
                    'NOT'  => ['id' => [new \QueryParam(), new \QueryParam()]]
                ],
                [],
                'DELETE `table` FROM `table` WHERE  NOT (`id` IN (?, ?))'
            ], [
                /*'table', [
                    'NOT'  => ['id' => [new \QueryParam('idone'), new \QueryParam('idtwo')]]
                ],
                [],
                'DELETE `table` FROM `table` WHERE  NOT (`id` IN (:idone, :idtwo))'
            ], [*/
                'table', [
                    'id'  => 1
                ],
                [
                    'LEFT JOIN' => [
                        'another_table' => [
                            'ON' => [
                                'table'         => 'foreign_id',
                                'another_table' => 'id'
                            ]
                        ],
                        'table_3' => [
                            'ON' => [
                                'another_table' => 'some_id',
                                'table_3'       => 'id'
                            ]
                        ]
                    ]
                ],
                'DELETE `table` FROM `table`'
                . ' LEFT JOIN `another_table` ON (`table`.`foreign_id` = `another_table`.`id`)'
                . ' LEFT JOIN `table_3` ON (`another_table`.`some_id` = `table_3`.`id`)'
                . ' WHERE `id` = \'1\''
            ],
        ];
    }

    /**
     * @dataProvider dataDelete
     */
    public function testBuildDelete($table, $where, array $joins, $expected)
    {
        $this
         ->if($this->newTestedInstance)
         ->then
            ->string($this->testedInstance->buildDelete($table, $where, $joins))->isIdenticalTo($expected);
    }

    public function testBuildDeleteWException()
    {
        $this->exception(
            function () {
                $this
                  ->if($this->newTestedInstance)
                  ->then
                  ->string($this->testedInstance->buildDelete('table', []))->isIdenticalTo('');
            }
        )->hasMessage('Cannot run an DELETE query without WHERE clause!');
    }

    public function testListTables()
    {
        $this
         ->if($this->newTestedInstance)
         ->then
            ->given($tables = $this->testedInstance->listTables())
            ->object($tables)
               ->isInstanceOf(\DBmysqlIterator::class)
            ->integer(count($tables))
               ->isGreaterThan(100)
            ->given($tables = $this->testedInstance->listTables('glpi_configs'))
            ->object($tables)
               ->isInstanceOf(\DBmysqlIterator::class)
               ->hasSize(1);
    }

    public function testTablesHasItemtype()
    {
        $dbu = new \DbUtils();
        $this->newTestedInstance();
        $list = $this->testedInstance->listTables();
        $this->object($list)->isInstanceOf(\DBmysqlIterator::class);
        $this->integer(count($list))->isGreaterThan(200);

       //check if each table has a corresponding itemtype
        foreach ($list as $line) {
            $this->array($line)
            ->hasSize(1);
            $table = $line['TABLE_NAME'];
            if ($table == 'glpi_appliancerelations') {
                //FIXME temporary hack for unit tests
                continue;
            }
            $type = $dbu->getItemTypeForTable($table);
            $this->string($type)->isNotEqualTo('UNKNOWN', "$table does not have corresponding item");

            $this->string($type)->isNotEqualTo('UNKNOWN', 'Cannot find type for table ' . $table);
            $this->object($item = $dbu->getItemForItemtype($type))->isInstanceOf('CommonDBTM', $table);
            $this->string(get_class($item))->isIdenticalTo($type);
            $this->string($dbu->getTableForItemType($type))->isIdenticalTo($table);
        }
    }

    public function testEscape()
    {
        $this
         ->if($this->newTestedInstance)
         ->then
            ->string($this->testedInstance->escape('nothing to do'))->isIdenticalTo('nothing to do')
            ->string($this->testedInstance->escape("shoul'be escaped"))->isIdenticalTo("shoul\\'be escaped")
            ->string($this->testedInstance->escape("First\nSecond"))->isIdenticalTo("First\\nSecond")
            ->string($this->testedInstance->escape("First\rSecond"))->isIdenticalTo("First\\rSecond")
            ->string($this->testedInstance->escape('Hi, "you"'))->isIdenticalTo('Hi, \\"you\\"');
    }

    protected function commentsProvider()
    {
        return [
            [
                'sql' => "SQL EXPRESSION;
/* Here begins a
   multiline comment */
OTHER EXPRESSION;
",
                'expected'  => "SQL EXPRESSION;
OTHER EXPRESSION;"
            ]
        ];
    }

    /**
     * @dataProvider commentsProvider
     */
    public function testRemoveSqlComments($sql, $expected)
    {
        $this
         ->if($this->newTestedInstance)
         ->then
            ->string($this->testedInstance->removeSqlComments($sql))->isIdenticalTo($expected);
    }

    /**
     * Sql expressions provider
     */
    protected function sqlProvider()
    {
        return array_merge([
            [
                'sql'       => "SQL;\n-- comment;\n\nSQL2;",
                'expected'  => "SQL;\n\nSQL2;"
            ]
        ], $this->commentsProvider());
    }

    /**
     * @dataProvider sqlProvider
     */
    public function testRemoveSqlRemarks($sql, $expected)
    {
        $this
         ->if($this->newTestedInstance)
         ->then
            ->string($this->testedInstance->removeSqlRemarks($sql))->isIdenticalTo($expected);
    }

    protected function tableOptionProvider(): iterable
    {
        yield [
            'sql' => <<<SQL
                CREATE TABLE `%s` (
                    `nameid` varchar(100) NOT NULL,
                    UNIQUE KEY (`nameid`)
                )
            SQL,
            'db_properties' => [],
            'warning' => null
        ];

        // Warnings related to MyISAM usage
        $myisam_declarations = [
            'engine=MyISAM', // without ending `;`
            'engine=MyISAM;', // with ending `;`
            ' Engine =  myisam ', // mixed case
            '   ENGINE  =    MYISAM  ', // uppercase with lots of spaces
            " ENGINE = 'MyISAM'", // surrounded by quotes
            "ROW_FORMAT=DYNAMIC ENGINE=MyISAM", // preceded by another option
            "ENGINE=MyISAM ROW_FORMAT=DYNAMIC" // followed by another option
        ];

        foreach ($myisam_declarations as $table_options) {
            yield [
                'sql' => <<<SQL
                    CREATE TABLE `%s` (
                        `nameid` varchar(100) NOT NULL,
                        UNIQUE KEY (`nameid`)
                    ){$table_options}
                SQL,
                'db_properties' => [
                    'allow_myisam' => true
                ],
                'warning' => null
            ];

            yield [
                'sql' => <<<SQL
                    CREATE TABLE `%s` (
                        `nameid` varchar(100) NOT NULL,
                        UNIQUE KEY (`nameid`)
                    ){$table_options}
                SQL,
                'db_properties' => [
                    'allow_myisam' => false
                ],
                'warning' => 'Usage of "MyISAM" engine is discouraged, please use "InnoDB" engine.'
            ];
        }

        // Warnings related to datetime fields
        yield [
            'sql' => <<<SQL
                CREATE TABLE `%s` (
                    `nameid` varchar(100) NOT NULL,
                    `date` datetime NOT NULL,
                    UNIQUE KEY (`nameid`)
                )
            SQL,
            'db_properties' => [
                'allow_datetime' => true
            ],
            'warning' => null
        ];
        yield [
            'sql' => <<<SQL
                CREATE TABLE `%s` (
                    `nameid` varchar(100) NOT NULL,
                    `date` datetime NOT NULL,
                    UNIQUE KEY (`nameid`)
                )
            SQL,
            'db_properties' => [
                'allow_datetime' => false
            ],
            'warning' => 'Usage of "DATETIME" fields is discouraged, please use "TIMESTAMP" fields instead.'
        ];

        // Warnings related to 'utf8mb4' usage when DB not yet migrated to 'utf8mb4'
        yield [
            'sql' => <<<SQL
                CREATE TABLE `%s` (
                    `nameid` varchar(100) NOT NULL,
                    UNIQUE KEY (`nameid`)
                ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci
            SQL,
            'db_properties' => [
                'use_utf8mb4' => false
            ],
            'warning' => null
        ];
        yield [
            'sql' => <<<SQL
                CREATE TABLE `%s` (
                    `nameid` varchar(100) NOT NULL,
                    UNIQUE KEY (`nameid`)
                ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci
            SQL,
            'db_properties' => [
                'use_utf8mb4' => false
            ],
            'warning' => 'Usage of "utf8mb4" charset/collation detected, should be "utf8"'
        ];

        // Warnings related to 'utf8' usage when DB has been migrated to 'utf8mb4'
        yield [
            'sql' => <<<SQL
                CREATE TABLE `%s` (
                    `nameid` varchar(100) NOT NULL,
                    UNIQUE KEY (`nameid`)
                ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci
            SQL,
            'db_properties' => [
                'use_utf8mb4' => true
            ],
            'warning' => 'Usage of "utf8" charset/collation detected, should be "utf8mb4"'
        ];
        yield [
            'sql' => <<<SQL
                CREATE TABLE `%s` (
                    `nameid` varchar(100) NOT NULL,
                    UNIQUE KEY (`nameid`)
                ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci
            SQL,
            'db_properties' => [
                'use_utf8mb4' => true
            ],
            'warning' => null
        ];

        // Warnings related to usage of signed integers in primary/foreign key fields.
        $int_declarations = [
            '`id` int NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`),' => 'id',
            '`id` int unsigned NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`),' => null,
            '`users_id` int DEFAULT NULL,' => 'users_id',
            '`users_id` int unsigned DEFAULT NULL,' => null,
            '`users_id_tech` int NOT NULL,' => 'users_id_tech',
            '`users_id_tech` int unsigned NOT NULL,' => null,
            'id int DEFAULT NULL,' => 'id', // field name without backticks
            '`users_id`int         unsigned DEFAULT NULL,' => null, // uncommon whitespaces
            '`unconventionnalid` int DEFAULT NULL,' => null, // not matching naming conventions
            '`id_computer` int DEFAULT NULL,' => null, // not matching naming conventions
        ];
        foreach ($int_declarations as $int_declaration => $warning_field) {
            yield [
                'sql' => <<<SQL
                    CREATE TABLE `%s` (
                        `nameid` varchar(100) NOT NULL,
                        {$int_declaration}
                        UNIQUE KEY (`nameid`)
                    )
                SQL,
                'db_properties' => [
                    'allow_signed_keys' => true
                ],
                'warning' => null // No warning as we allow signed keys
            ];
            yield [
                'sql' => <<<SQL
                    CREATE TABLE `%s` (
                        `nameid` varchar(100) NOT NULL,
                        {$int_declaration}
                        UNIQUE KEY (`nameid`)
                    )
                SQL,
                'db_properties' => [
                    'allow_signed_keys' => false
                ],
                'warning' => $warning_field !== null
                    ? sprintf('Usage of signed integers in primary or foreign keys is discouraged, please use unsigned integers instead in `{$table}`.`%s`.', $warning_field)
                    : null,
            ];
        }

        // Check table name extracted in warnings
        $table_declarations = [
            'CREATE TEMPORARY TABLE `%s`', // temporary table
            'CREATE TABLE IF NOT EXISTS `%s`', // if not exists
            'CREATE TABLE`%s`', // no space before table name
            'CREATE TABLE %s', // no quotes
            'CREATE   TEMPORARY  TABLE      IF   NOT    EXISTS`%s`', // random spacing
        ];
        foreach ($table_declarations as $table_declaration) {
            yield [
                'sql' => <<<SQL
                    {$table_declaration} (
                        `id` int NOT NULL AUTO_INCREMENT,
                        PRIMARY KEY (`id`)
                    )
                SQL,
                'db_properties' => [
                    'allow_signed_keys' => false
                ],
                'warning' => sprintf('Usage of signed integers in primary or foreign keys is discouraged, please use unsigned integers instead in `{$table}`.`id`.'),
            ];
        }
    }

    /**
     * @dataProvider tableOptionProvider
     */
    public function testAlterOrCreateTableWarnings(
        string $sql,
        array $db_properties,
        ?string $warning = null
    ) {
        $db = new \mock\DB();

        $create_query_template = $sql;
        $drop_query_template = 'DROP TABLE `%s`';

        $db->log_deprecation_warnings = false; // Prevent deprecation warning from MySQL server
        foreach ($db_properties as $db_property => $value) {
            $db->$db_property = $value;
        }

        $asserter = $warning === null ? 'notExists' : 'exists';

        $table = sprintf('glpitests_%s', uniqid());
        $this->when(
            function () use ($db, $create_query_template, $drop_query_template, $table) {
                $db->query(sprintf($create_query_template, $table));
                $db->query(sprintf($drop_query_template, $table));
            }
        )->error()
            ->withType(E_USER_WARNING)
            ->withMessage(str_replace(['{$table}'], [$table], $warning ?? ''))
            ->$asserter();
    }

    public function testSavepoints()
    {
        global $DB;

        $DB->beginTransaction();

        $computer = new \Computer();
        $DB->setSavepoint('save0', false);
        $computers_id_0 = $computer->add([
            'name'        => 'computer0',
            'entities_id' => 0
        ]);
        $this->integer($computers_id_0)->isGreaterThan(0);
        $DB->setSavepoint('save1', false);
        $computers_id_1 = $computer->add([
            'name'        => 'computer1',
            'entities_id' => 0
        ]);
        $this->integer($computers_id_1)->isGreaterThan(0);
        $this->boolean($computer->getFromDB($computers_id_1))->isTrue();

        $DB->rollBack('save1');
        $this->boolean($computer->getFromDB($computers_id_1))->isFalse();
        $this->boolean($computer->getFromDB($computers_id_0))->isTrue();

        $DB->rollBack('save0');
        $this->boolean($computer->getFromDB($computers_id_1))->isFalse();
        $this->boolean($computer->getFromDB($computers_id_0))->isFalse();

        $DB->rollBack();
    }

    public function testGetLastQueryWarnings()
    {
        $db = new \mock\DB();

        $db->query('SELECT 1/0');
        $this->array($db->getLastQueryWarnings())->isEqualTo(
            [
                [
                    'Level'   => 'Warning',
                    'Code'    => 1365,
                    'Message' => 'Division by 0',
                ]
            ]
        );
        $this->hasSqlLogRecordThatContains('1365: Division by 0', LogLevel::WARNING);

        $db->query('SELECT CAST("1a" AS SIGNED), CAST("123b" AS SIGNED)');
        $this->array($db->getLastQueryWarnings())->isEqualTo(
            [
                [
                    'Level'   => 'Warning',
                    'Code'    => 1292,
                    'Message' => 'Truncated incorrect INTEGER value: \'1a\'',
                ],
                [
                    'Level'   => 'Warning',
                    'Code'    => 1292,
                    'Message' => 'Truncated incorrect INTEGER value: \'123b\'',
                ]
            ]
        );
        $this->hasSqlLogRecordThatContains(
            '1292: Truncated incorrect INTEGER value: \'1a\'' . "\n" . '1292: Truncated incorrect INTEGER value: \'123b\'',
            LogLevel::WARNING
        );
    }

    protected function dataDrop()
    {
        return [
            [
                'tablename',
                'TABLE',
                false,
                'DROP TABLE `tablename`'
            ], [
                'viewname',
                'VIEW',
                false,
                'DROP VIEW `viewname`'
            ], [
                'tablename',
                'TABLE',
                true,
                'DROP TABLE IF EXISTS `tablename`'
            ], [
                'viewname',
                'VIEW',
                true,
                'DROP VIEW IF EXISTS `viewname`'
            ]
        ];
    }

    /**
     * @dataProvider dataDrop
     */
    public function testBuildDrop($name, $type, $exists, $expected)
    {
        $this
            ->if($this->newTestedInstance)
            ->then
            ->string($this->testedInstance->buildDrop($name, $type, $exists))->isIdenticalTo($expected);
    }

    public function testBuildDropWException()
    {
        $this->exception(
            function () {
                $this
                    ->if($this->newTestedInstance)
                    ->then
                    ->string($this->testedInstance->buildDrop('aname', 'UNKNOWN'))->isIdenticalTo('');
            }
        )->hasMessage('Unknown type to drop: UNKNOWN');
    }
}
