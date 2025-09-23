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

use ArrayIterator;
use Computer;
use CronTask;
use DbTestCase;
use Glpi\DBAL\QuerySubQuery;
use Glpi\Progress\AbstractProgressIndicator;
use Glpi\Socket;
use LogicException;
use Migration;
use PHPUnit\Framework\Attributes\DataProvider;

class MigrationTest extends DbTestCase
{
    private $original_db;

    public static function cronTaskProvider(): iterable
    {
        yield [
            'itemtype'  => Computer::class,
            'name'      => 'whatever',
            'frequency' => HOUR_TIMESTAMP,
            'param'     => 25,
            'options'   => [],
            'expected'  => [
                // specific values
                'itemtype'      => Computer::class,
                'name'          => 'whatever',
                'frequency'     => HOUR_TIMESTAMP,
                'param'         => 25,

                // default values
                'mode'          => CronTask::MODE_EXTERNAL,
                'state'         => CronTask::STATE_WAITING,
                'hourmin'       => 0,
                'hourmax'       => 24,
                'logs_lifetime' => 30,
                'allowmode'     => CronTask::MODE_INTERNAL | CronTask::MODE_EXTERNAL,
                'comment'       => '',
            ],
        ];

        yield [
            'itemtype'  => Computer::class,
            'name'      => 'foo',
            'frequency' => DAY_TIMESTAMP,
            'param'     => null,
            'options'   => [
                'mode'          => CronTask::MODE_INTERNAL,
                'state'         => CronTask::STATE_DISABLE,
                'allowmode'     => CronTask::MODE_INTERNAL,
            ],
            'expected'  => [
                // specific values
                'itemtype'      => Computer::class,
                'name'          => 'foo',
                'frequency'     => DAY_TIMESTAMP,
                'param'         => null,
                'mode'          => CronTask::MODE_INTERNAL,
                'state'         => CronTask::STATE_DISABLE,
                'allowmode'     => CronTask::MODE_INTERNAL,

                // default values
                'hourmin'       => 0,
                'hourmax'       => 24,
                'logs_lifetime' => 30,
                'comment'       => '',
            ],
        ];

        yield [
            'itemtype'  => Socket::class,
            'name'      => 'bar',
            'frequency' => HOUR_TIMESTAMP,
            'param'     => null,
            'options'   => [
                'hourmin'       => 9,
                'hourmax'       => 18,
                'logs_lifetime' => 365,
                'comment'       => 'A cron task ...',

            ],
            'expected'  => [
                // specific values
                'itemtype'      => Socket::class,
                'name'          => 'bar',
                'frequency'     => HOUR_TIMESTAMP,
                'param'         => null,
                'hourmin'       => 9,
                'hourmax'       => 18,
                'logs_lifetime' => 365,
                'comment'       => 'A cron task ...',

                // default values
                'mode'          => CronTask::MODE_EXTERNAL,
                'state'         => CronTask::STATE_WAITING,
                'allowmode'     => CronTask::MODE_INTERNAL | CronTask::MODE_EXTERNAL,
            ],
        ];
    }

    #[DataProvider('cronTaskProvider')]
    public function testAddCronTask(
        string $itemtype,
        string $name,
        int $frequency,
        ?int $param,
        array $options,
        array $expected
    ): void {
        $migration = new Migration('1.2.3');
        $migration->addCrontask($itemtype, $name, $frequency, $param, $options);

        $crontask = new CronTask();
        $this->assertTrue($crontask->getFromDBByCrit($expected));
    }

    public function testAddCronTaskThatAlreadyExists(): void
    {
        $existing_crontask = $this->createItem(
            CronTask::class,
            [
                // specific values
                'itemtype'  => Computer::class,
                'name'      => 'duplicate_test',
                'frequency' => HOUR_TIMESTAMP,
                'param'     => null,
            ]
        );

        $migration = new Migration('1.2.3');
        $migration->addCrontask(Computer::class, 'duplicate_test', MONTH_TIMESTAMP, 25);

        // Assert that the contask in DB after the migration matches the previously existing crontask
        $result_crontask = new CronTask();
        $this->assertTrue($result_crontask->getFromDBByCrit(['itemtype' => Computer::class, 'name' => 'duplicate_test']));
        $this->assertEquals($existing_crontask->getID(), $result_crontask->getID());
        $this->assertEquals($existing_crontask->fields, $result_crontask->fields);
    }

    public function testConstructor()
    {
        new Migration(GLPI_VERSION);
        $this->expectOutputString('');
    }

    public function testPrePostQueries()
    {
        $migration = $this->getMigrationMock();
        $migration->addPostQuery('UPDATE post_table SET mfield = "myvalue"');
        $migration->addPreQuery('UPDATE pre_table SET mfield = "myvalue"');
        $migration->addPostQuery('UPDATE post_otable SET ofield = "myvalue"');

        $migration->executeMigration();
        $this->assertEquals([
            'UPDATE pre_table SET mfield = "myvalue"',
            'UPDATE post_table SET mfield = "myvalue"',
            'UPDATE post_otable SET ofield = "myvalue"',
        ], $migration->getMockedQueries());
    }

    public function testAddConfig()
    {
        $migration = $this->getMigrationMock(db_options: [
            '_mock_numrows' => 0,
            '_mock_fetchAssoc' => [],
            '_mock_dataSeek' => true,
            '_mock_listFields' => [
                'id'        => '',
                'context'   => '',
                'name'      => '',
                'value'     => '',
            ],
        ]);

        //test with non-existing value => new keys should be inserted
        $migration->addConfig([
            'one' => 'key',
            'two' => 'value',
        ]);
        $migration->executeMigration();
        $core_queries = [
            'SELECT * FROM `glpi_configs` WHERE `context` = \'core\' AND `name` IN (\'one\', \'two\')',
            'INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES (\'core\', \'one\', \'key\')',
            'INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES (\'core\', \'two\', \'value\')',
        ];
        $this->assertEquals($core_queries, $migration->getMockedQueries(), print_r($migration->getMockedQueries(), true));

        //test with existing value on different context => new keys should be inserted in correct context
        $migration->clearMockedQueries();
        $migration->addConfig([
            'one' => 'key',
            'two' => 'value',
        ], 'test-context');
        $migration->executeMigration();

        $this->assertEquals([
            'SELECT * FROM `glpi_configs` WHERE `context` = \'test-context\' AND `name` IN (\'one\', \'two\')',
            'INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES (\'test-context\', \'one\', \'key\')',
            'INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES (\'test-context\', \'two\', \'value\')',
        ], $migration->getMockedQueries());

        //test with one existing value => only new key should be inserted
        $migration = $this->getMigrationMock(db_options: [
            '_mock_numrows' => 0,
            '_mock_fetchAssoc' => [],
            '_mock_dataSeek' => true,
            '_mock_listFields' => [
                'id'        => '',
                'context'   => '',
                'name'      => '',
                'value'     => '',
            ],
            '_mock_request' => function ($criteria) {
                // Call using 'glpi_configs' value for first parameter
                // corresponds to the call made to retrieve existing values
                // -> returns a value for config 'one'
                if ($criteria === ['FROM' => 'glpi_configs', 'WHERE' => ['context' => 'core', 'name' => ['one', 'two']]]) {
                    return new ArrayIterator([
                        [
                            'id'        => '42',
                            'context'   => 'core',
                            'name'      => 'one',
                            'value'     => 'setted value',
                        ],
                    ]);
                }
                // Other calls corresponds to call made in Config::setConfigurationValues()
                return new ArrayIterator();
            },
        ]);
        $migration->addConfig([
            'one' => 'key',
            'two' => 'value',
        ]);
        $migration->executeMigration();
        $this->assertEquals([
            0 => 'INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES (\'core\', \'two\', \'value\')',
        ], $migration->getMockedQueries());
    }

    public function testBackupNonExistantTables()
    {
        $migration = $this->getMigrationMock(db_options: [
            '_mock_numrows' => 0,
        ]);

        $migration->backupTables(['table1', 'table2']);
        $migration->executeMigration();

        $this->assertEquals([
            0 => 'SELECT `table_name` AS `TABLE_NAME` FROM `information_schema`.`tables`'
                . ' WHERE `table_schema` = \'mockedglpi\' AND `table_type` = \'BASE TABLE\' AND `table_name` LIKE \'table1\'',
            1 => 'SELECT `table_name` AS `TABLE_NAME` FROM `information_schema`.`tables`'
                . ' WHERE `table_schema` = \'mockedglpi\' AND `table_type` = \'BASE TABLE\' AND `table_name` LIKE \'table2\'',
        ], $migration->getMockedQueries());
    }

    /**
     * Test backupTables() when a backup table already exists
     * @return void
     */
    public function testBackupExistantBackupTables()
    {
        $migration = $this->getMigrationMock(db_options: [
            '_mock_numrows' => 0,
            '_mock_tableExists' => true,
        ]);

        $caught = null;
        // use try/catch instead of $this->expectException because we want to assert on the ran queries after the exception
        try {
            $migration->backupTables(['glpi_existingtest']);
            $migration->executeMigration();
        } catch (\Exception $e) {
            $caught = $e;
        }
        $this->assertNotNull($caught);
        $this->assertEquals('Unable to rename table glpi_existingtest (ok) to backup_glpi_existingtest (nok)!', $caught->getMessage());

        $this->assertEquals([
            0 => 'DROP TABLE `backup_glpi_existingtest`',
        ], $migration->getMockedQueries());
    }

    public function testBackupTables()
    {
        $migration = $this->getMigrationMock(db_options: [
            '_mock_numrows' => 0,
            '_mock_tableExists' => function ($name) {
                return $name === 'glpi_existingtest';
            },
        ]);

        $migration->backupTables(['glpi_existingtest']);
        $migration->executeMigration();

        $this->assertEquals([
            0 => 'RENAME TABLE `glpi_existingtest` TO `backup_glpi_existingtest`',
        ], $migration->getMockedQueries());
    }

    public function testChangeField()
    {
        $migration = $this->getMigrationMock(db_options: [
            '_mock_fieldExists' => true,
        ]);

        // Test change field with move to first column
        $migration->changeField('change_table', 'ID', 'id', 'integer', ['first' => 'first']);
        $migration->executeMigration();
        $this->assertEquals([
            "ALTER TABLE `change_table` DROP `id` ,\n"
            . "CHANGE `ID` `id` INT NOT NULL DEFAULT '0'   FIRST  ",
        ], $migration->getMockedQueries());

        // Test change field with move to after another column
        $migration->clearMockedQueries();
        $migration->changeField('change_table', 'NAME', 'name', 'string', ['after' => 'id']);
        $migration->executeMigration();
        $collate = $migration->getMockedDb()->use_utf8mb4 ? 'utf8mb4_unicode_ci' : 'utf8_unicode_ci';
        $this->assertEquals([
            "ALTER TABLE `change_table` DROP `name` ,\n"
            . "CHANGE `NAME` `name` VARCHAR(255) COLLATE $collate DEFAULT NULL   AFTER `id` ",
        ], $migration->getMockedQueries());
    }

    public static function fieldsFormatsProvider()
    {
        return [
            [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'bool',
                'options'   => [],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` TINYINT NOT NULL DEFAULT '0'   ",
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'bool',
                'options'   => ['value' => 1],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` TINYINT NOT NULL DEFAULT '1'   ",
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'char',
                'options'   => [],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` CHAR(1) DEFAULT NULL   ",
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'char',
                'options'   => ['value' => 'a'],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` CHAR(1) NOT NULL DEFAULT 'a'   ",
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'string',
                'options'   => [],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL   ",
                'db_properties' => [
                    'use_utf8mb4' => false,
                ],
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'string',
                'options'   => [],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL   ",
                'db_properties' => [
                    'use_utf8mb4' => true,
                ],
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'string',
                'options'   => ['value' => 'a string'],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'a string'   ",
                'db_properties' => [
                    'use_utf8mb4' => false,
                ],
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'string',
                'options'   => ['value' => 'a string'],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'a string'   ",
                'db_properties' => [
                    'use_utf8mb4' => true,
                ],
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'integer',
                'options'   => [],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` INT NOT NULL DEFAULT '0'   ",
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'integer',
                'options'   => ['value' => 2],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` INT NOT NULL DEFAULT '2'   ",
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'date',
                'options'   => [],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` DATE DEFAULT NULL   ",
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'date',
                'options'   => ['value' => '2018-06-04'],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` DATE DEFAULT '2018-06-04'   ",
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'datetime',
                'options'   => [],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` TIMESTAMP NULL DEFAULT NULL   ",
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'datetime',
                'options'   => ['value' => '2018-06-04 08:16:38'],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` TIMESTAMP DEFAULT '2018-06-04 08:16:38'   ",
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'text',
                'options'   => [],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` TEXT COLLATE utf8_unicode_ci DEFAULT NULL   ",
                'db_properties' => [
                    'use_utf8mb4' => false,
                ],
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'text',
                'options'   => [],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL   ",
                'db_properties' => [
                    'use_utf8mb4' => true,
                ],
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'text',
                'options'   => ['value' => 'A text'],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` TEXT COLLATE utf8_unicode_ci NOT NULL DEFAULT 'A text'   ",
                'db_properties' => [
                    'use_utf8mb4' => false,
                ],
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'text',
                'options'   => ['value' => 'A text'],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` TEXT COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A text'   ",
                'db_properties' => [
                    'use_utf8mb4' => true,
                ],
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'mediumtext',
                'options'   => [],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` MEDIUMTEXT COLLATE utf8_unicode_ci DEFAULT NULL   ",
                'db_properties' => [
                    'use_utf8mb4' => false,
                ],
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'mediumtext',
                'options'   => [],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` MEDIUMTEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL   ",
                'db_properties' => [
                    'use_utf8mb4' => true,
                ],
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'mediumtext',
                'options'   => ['value' => 'A medium text'],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` MEDIUMTEXT COLLATE utf8_unicode_ci NOT NULL DEFAULT 'A medium text'   ",
                'db_properties' => [
                    'use_utf8mb4' => false,
                ],
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'mediumtext',
                'options'   => ['value' => 'A medium text'],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` MEDIUMTEXT COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A medium text'   ",
                'db_properties' => [
                    'use_utf8mb4' => true,
                ],
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'longtext',
                'options'   => [],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` LONGTEXT COLLATE utf8_unicode_ci DEFAULT NULL   ",
                'db_properties' => [
                    'use_utf8mb4' => false,
                ],
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'longtext',
                'options'   => [],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` LONGTEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL   ",
                'db_properties' => [
                    'use_utf8mb4' => true,
                ],
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'longtext',
                'options'   => ['value' => 'A long text'],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` LONGTEXT COLLATE utf8_unicode_ci NOT NULL DEFAULT 'A long text'   ",
                'db_properties' => [
                    'use_utf8mb4' => false,
                ],
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'longtext',
                'options'   => ['value' => 'A long text'],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` LONGTEXT COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A long text'   ",
                'db_properties' => [
                    'use_utf8mb4' => true,
                ],
            ], [
                'table'     => 'my_table',
                'field'     => 'id',
                'format'    => 'autoincrement',
                'options'   => [],
                'sql'       => "ALTER TABLE `my_table` ADD `id` INT  NOT NULL AUTO_INCREMENT   ",
                'db_properties' => [
                    'allow_signed_keys' => true,
                ],
            ], [
                'table'     => 'my_table',
                'field'     => 'id',
                'format'    => 'autoincrement',
                'options'   => [],
                'sql'       => "ALTER TABLE `my_table` ADD `id` INT unsigned NOT NULL AUTO_INCREMENT   ",
                'db_properties' => [
                    'allow_signed_keys' => false,
                ],
            ], [
                'table'     => 'my_table',
                'field'     => 'othertables_id',
                'format'    => 'fkey',
                'options'   => [],
                'sql'       => "ALTER TABLE `my_table` ADD `othertables_id` INT  NOT NULL DEFAULT 0   ",
                'db_properties' => [
                    'allow_signed_keys' => true,
                ],
            ], [
                'table'     => 'my_table',
                'field'     => 'othertables_id',
                'format'    => 'fkey',
                'options'   => [],
                'sql'       => "ALTER TABLE `my_table` ADD `othertables_id` INT unsigned NOT NULL DEFAULT 0   ",
                'db_properties' => [
                    'allow_signed_keys' => false,
                ],
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => "INT NOT NULL DEFAULT '42'",
                'options'   => [],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` INT NOT NULL DEFAULT '42'   ",
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'integer',
                'options'   => ['comment' => 'a comment'],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` INT NOT NULL DEFAULT '0'  COMMENT 'a comment'  ",
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'integer',
                'options'   => ['after' => 'other_field'],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` INT NOT NULL DEFAULT '0'   AFTER `other_field` ",
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'integer',
                'options'   => ['first' => true],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` INT NOT NULL DEFAULT '0'   FIRST  ",
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'integer',
                'options'   => ['value' => '-2', 'update' => '0', 'condition' => 'WHERE `id` = 0'],
                'sql'       => [
                    "ALTER TABLE `my_table` ADD `my_field` INT NOT NULL DEFAULT '-2'   ",
                    "UPDATE `my_table`
                        SET `my_field` = 0 WHERE `id` = 0",
                ],
            ],
        ];
    }

    #[DataProvider('fieldsFormatsProvider')]
    public function testAddField($table, $field, $format, $options, $sql, array $db_properties = [])
    {
        $migration = $this->getMigrationMock(db_options: $db_properties + [
            '_mock_fieldExists' => false,
        ]);
        $migration->addField($table, $field, $format, $options);
        $migration->executeMigration();
        $this->assertEquals(!is_array($sql) ? [$sql] : $sql, $migration->getMockedQueries());
    }

    public function testFormatBooleanBadDefault()
    {
        $migration = $this->getMigrationMock(db_options: [
            '_mock_fieldExists' => false,
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Default value must be 0 or 1.');
        $migration->addField('my_table', 'my_field', 'bool', ['value' => 2]);
        $migration->executeMigration();
    }

    public function testFormatIntegerBadDefault()
    {
        $migration = $this->getMigrationMock(db_options: [
            '_mock_fieldExists' => false,
        ]);
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Default value must be numeric.');
        $migration->addField('my_table', 'my_field', 'integer', ['value' => 'foo']);
        $migration->executeMigration();
    }

    public function testAddRight()
    {
        global $DB;

        $migration = $this->getMigrationMock(db_options: ['_real_db' => true]);

        //Test adding a READ right when profile has READ and UPDATE config right (Default)
        $migration->addRight('test_addright_1', READ);

        //Test adding a READ right when profile has UPDATE group right
        $migration->addRight('test_addright_2', READ, ['group' => UPDATE]);

        //Test adding an UPDATE right when profile has READ and UPDATE group right and CREATE entity right
        $migration->addRight('test_addright_3', UPDATE, [
            'group'  => READ | UPDATE,
            'entity' => CREATE,
        ]);

        //Test adding a READ right when profile with no requirements
        $migration->addRight('test_addright_4', READ, []);

        $this->assertCount(1, $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE'  => [
                'name'   => 'test_addright_1',
                'rights' => READ,
            ],
        ]));

        $this->assertCount(2, $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE'  => [
                'name'   => 'test_addright_2',
                'rights' => READ,
            ],
        ]));

        $this->assertCount(1, $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE'  => [
                'name'   => 'test_addright_3',
                'rights' => UPDATE,
            ],
        ]));

        $this->assertCount(8, $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE'  => [
                'name'   => 'test_addright_4',
                'rights' => READ,
            ],
        ]));

        //Test adding a READ right only on profiles where it has not been set yet
        $DB->delete('glpi_profilerights', [
            'profiles_id' => [1, 2, 3, 4],
            'name' => 'test_addright_4',
        ]);

        $migration->addRight('test_addright_4', READ | UPDATE, []);

        $this->assertCount(4, $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE'  => [
                'name'   => 'test_addright_4',
                'rights' => READ | UPDATE,
            ],
        ]));
    }

    public function testAddRightByInterface()
    {
        global $DB;

        $migration = $this->getMigrationMock(db_options: ['_real_db' => true]);

        //Test adding a READ right on central interface
        $migration->addRightByInterface('testright1', READ, ['interface' => 'central']);
        //Test adding a READ right on helpdesk interface
        $migration->addRightByInterface('testright2', READ, ['interface' => 'helpdesk']);

        //Check if rights have been added
        $this->assertCount(7, $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE'  => [
                'name'   => 'testright1',
                'rights' => READ,
            ],
        ]));
        $this->assertCount(1, $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE'  => [
                'name'   => 'testright2',
                'rights' => READ,
            ],
        ]));
    }

    public function testRenameTable()
    {
        $migration = $this->getMigrationMock(db_options: [
            '_mock_tableExists' => function ($table) {
                return $table === 'glpi_oldtable';
            },
            '_mock_fieldExists' => function ($table, $field) {
                return $table === 'glpi_oldtable' && $field !== 'bool_field';
            },
            '_mock_doQuery' => function ($query) {
                // Make DbUtils::isIndex return false
                return $query !== 'SHOW INDEX FROM `glpi_oldtable`';
            },
        ]);

        // Case 1, rename with no buffered changes
        $migration->renameTable('glpi_oldtable', 'glpi_newtable');
        $this->assertEquals([
            "RENAME TABLE `glpi_oldtable` TO `glpi_newtable`",
        ], $migration->getMockedQueries());

        // Case 2, rename after changes were already applied
        $migration->clearMockedQueries();

        $migration->addField('glpi_oldtable', 'bool_field', 'bool');
        $migration->addKey('glpi_oldtable', 'id', 'id', 'UNIQUE');
        $migration->addKey('glpi_oldtable', 'fulltext_key', 'fulltext_key', 'FULLTEXT');
        $migration->migrationOneTable('glpi_oldtable');
        $migration->renameTable('glpi_oldtable', 'glpi_newtable');

        $this->assertEquals([
            "SHOW INDEX FROM `glpi_oldtable`",
            "SHOW INDEX FROM `glpi_oldtable`",
            "ALTER TABLE `glpi_oldtable` ADD `bool_field` TINYINT NOT NULL DEFAULT '0'   ",
            "ALTER TABLE `glpi_oldtable` ADD FULLTEXT `fulltext_key` (`fulltext_key`)",
            "ALTER TABLE `glpi_oldtable` ADD UNIQUE `id` (`id`)",
            "RENAME TABLE `glpi_oldtable` TO `glpi_newtable`",
        ], $migration->getMockedQueries());

        // Case 3, apply changes after renaming
        $migration->clearMockedQueries();

        $migration->addField('glpi_oldtable', 'bool_field', 'bool');
        $migration->addKey('glpi_oldtable', 'id', 'id', 'UNIQUE');
        $migration->addKey('glpi_oldtable', 'fulltext_key', 'fulltext_key', 'FULLTEXT');
        $migration->renameTable('glpi_oldtable', 'glpi_newtable');
        $migration->migrationOneTable('glpi_newtable');

        $this->assertEquals([
            "SHOW INDEX FROM `glpi_oldtable`",
            "SHOW INDEX FROM `glpi_oldtable`",
            "RENAME TABLE `glpi_oldtable` TO `glpi_newtable`",
            "ALTER TABLE `glpi_newtable` ADD `bool_field` TINYINT NOT NULL DEFAULT '0'   ",
            "ALTER TABLE `glpi_newtable` ADD FULLTEXT `fulltext_key` (`fulltext_key`)",
            "ALTER TABLE `glpi_newtable` ADD UNIQUE `id` (`id`)",
        ], $migration->getMockedQueries());
    }

    /**
     * Test Migration::renameItemtype().
     * Case: failure as source table does not exists.
     */
    public function testRenameItemtypeWhenSourceTableDoesNotExists()
    {
        $migration = $this->getMigrationMock(db_options: [
            '_mock_tableExists' => false,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Table "glpi_someoldtypes" does not exists.');
        $migration->renameItemtype('SomeOldType', 'NewName');
    }

    /**
     * Test Migration::renameItemtype().
     * Case: failure as destination table already exists.
     */
    public function testRenameItemtypeWhenDestinationTableAlreadyExists()
    {
        $migration = $this->getMigrationMock(db_options: [
            '_mock_tableExists' => true,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Table "glpi_someoldtypes" cannot be renamed as table "glpi_newnames" already exists.');
        $migration->renameItemtype('SomeOldType', 'NewName');
    }

    /**
     * Test Migration::renameItemtype().
     * Case: failure as foreign key field already in use somewhere.
     */
    public function testRenameItemtypeWhenDestinationFieldAlreadyExists()
    {
        $migration = $this->getMigrationMock(db_options: [
            '_mock_tableExists' => function ($table) {
                return $table === 'glpi_someoldtypes';
            },
            '_mock_fieldExists' => true,
            '_mock_request' => new ArrayIterator([
                [
                    'TABLE_NAME' => 'glpi_item_with_fkey', 'COLUMN_NAME' => 'someoldtypes_id',
                ],
            ]),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Field "someoldtypes_id" cannot be renamed in table "glpi_item_with_fkey" as "newnames_id" is field already exists.');
        $migration->renameItemtype('SomeOldType', 'NewName');
    }

    /**
     * Test Migration::renameItemtype().
     * Case: success.
     */
    public function testRenameItemtype()
    {
        $migration = $this->getMigrationMock(db_options: [
            'allow_signed_keys' => false,
            '_mock_tableExists' => function ($table) {
                return $table === 'glpi_someoldtypes';
            },
            '_mock_fieldExists' => function ($table, $field) {
                return str_starts_with($field, "someoldtypes_id");
            },
            '_mock_request' => function ($request) {
                if (
                    isset($request['WHERE']['OR'][0])
                    && $request['WHERE']['OR'][0] === ['column_name'  => 'someoldtypes_id']
                ) {
                    // Request used for foreign key fields
                    return new ArrayIterator([
                        ['TABLE_NAME' => 'glpi_oneitem_with_fkey',     'COLUMN_NAME' => 'someoldtypes_id'],
                        ['TABLE_NAME' => 'glpi_anotheritem_with_fkey', 'COLUMN_NAME' => 'someoldtypes_id'],
                        ['TABLE_NAME' => 'glpi_anotheritem_with_fkey', 'COLUMN_NAME' => 'someoldtypes_id_tech'],
                    ]);
                }
                if (
                    isset($request['WHERE']['OR'][0])
                    && $request['WHERE']['OR'][0] === ['column_name'  => 'itemtype']
                ) {
                    // Request used for itemtype fields
                    return new ArrayIterator([
                        ['TABLE_NAME' => 'glpi_computers', 'COLUMN_NAME' => 'itemtype'],
                        ['TABLE_NAME' => 'glpi_users',     'COLUMN_NAME' => 'itemtype'],
                        ['TABLE_NAME' => 'glpi_stuffs',    'COLUMN_NAME' => 'itemtype_source'],
                        ['TABLE_NAME' => 'glpi_stuffs',    'COLUMN_NAME' => 'itemtype_dest'],
                    ]);
                }
                return [];
            },
        ]);

        // Test renaming with DB structure update
        $migration->renameItemtype('SomeOldType', 'NewName');
        $migration->executeMigration();

        $this->assertEquals([
            "RENAME TABLE `glpi_someoldtypes` TO `glpi_newnames`",
            "UPDATE `glpi_computers` SET `itemtype` = 'NewName' WHERE `itemtype` = 'SomeOldType'",
            "UPDATE `glpi_users` SET `itemtype` = 'NewName' WHERE `itemtype` = 'SomeOldType'",
            "UPDATE `glpi_stuffs` SET `itemtype_source` = 'NewName' WHERE `itemtype_source` = 'SomeOldType'",
            "UPDATE `glpi_stuffs` SET `itemtype_dest` = 'NewName' WHERE `itemtype_dest` = 'SomeOldType'",
            "ALTER TABLE `glpi_oneitem_with_fkey` CHANGE `someoldtypes_id` `newnames_id` int unsigned NOT NULL DEFAULT '0'   ",
            "ALTER TABLE `glpi_anotheritem_with_fkey` CHANGE `someoldtypes_id` `newnames_id` int unsigned NOT NULL DEFAULT '0'   ,\n"
            . "CHANGE `someoldtypes_id_tech` `newnames_id_tech` int unsigned NOT NULL DEFAULT '0'   ",
        ], $migration->getMockedQueries());

        // Test renaming without DB structure update
        $migration->clearMockedQueries();
        $migration->renameItemtype('SomeOldType', 'NewName', false);
        $migration->executeMigration();

        $this->assertEquals([
            "UPDATE `glpi_computers` SET `itemtype` = 'NewName' WHERE `itemtype` = 'SomeOldType'",
            "UPDATE `glpi_users` SET `itemtype` = 'NewName' WHERE `itemtype` = 'SomeOldType'",
            "UPDATE `glpi_stuffs` SET `itemtype_source` = 'NewName' WHERE `itemtype_source` = 'SomeOldType'",
            "UPDATE `glpi_stuffs` SET `itemtype_dest` = 'NewName' WHERE `itemtype_dest` = 'SomeOldType'",
        ], $migration->getMockedQueries());

        // Test renaming when old class and new class have the same table name
        $migration->clearMockedQueries();
        $migration->renameItemtype('PluginFooThing', 'GlpiPlugin\\Foo\\Thing');
        $migration->executeMigration();

        $this->assertEquals([
            "UPDATE `glpi_computers` SET `itemtype` = 'GlpiPlugin\\\\Foo\\\\Thing' WHERE `itemtype` = 'PluginFooThing'",
            "UPDATE `glpi_users` SET `itemtype` = 'GlpiPlugin\\\\Foo\\\\Thing' WHERE `itemtype` = 'PluginFooThing'",
            "UPDATE `glpi_stuffs` SET `itemtype_source` = 'GlpiPlugin\\\\Foo\\\\Thing' WHERE `itemtype_source` = 'PluginFooThing'",
            "UPDATE `glpi_stuffs` SET `itemtype_dest` = 'GlpiPlugin\\\\Foo\\\\Thing' WHERE `itemtype_dest` = 'PluginFooThing'",
        ], $migration->getMockedQueries());
    }

    public function testChangeSearchOption()
    {
        $migration = $this->getMigrationMock(db_options: [
            '_mock_tableExists' => true,
            '_mock_request' => function ($request) {
                if (isset($request['INNER JOIN'])) {
                    $result = [];
                    if ($request['INNER JOIN'][\DisplayPreference::getTable() . ' AS old']['ON'][0]['AND']['new.itemtype'] === 'Printer') {
                        // Simulate duplicated search options on 'Printer'
                        $result = [
                            [
                                'id' => 12,
                            ],
                            [
                                'id' => 156,
                            ],
                            [
                                'id' => 421,
                            ],
                        ];
                    }
                    return new ArrayIterator($result);
                }
                if ($request['FROM'] === \SavedSearch::getTable()) {
                    return new ArrayIterator([
                        [
                            'id'        => 1,
                            'itemtype'  => 'Computer',
                            'query'     => 'is_deleted=0&as_map=0&criteria%5B0%5D%5Blink%5D=AND&criteria%5B0%5D%5Bfield%5D=40&criteria%5B0%5D%5Bsearchtype%5D=contains&criteria%5B0%5D%5Bvalue%5D=LT1&criteria%5B1%5D%5Blink%5D=AND&criteria%5B1%5D%5Bitemtype%5D=Budget&criteria%5B1%5D%5Bmeta%5D=1&criteria%5B1%5D%5Bfield%5D=4&criteria%5B1%5D%5Bsearchtype%5D=contains&criteria%5B1%5D%5Bvalue%5D=&search=Search&itemtype=Computer',
                        ],
                        [
                            'id'        => 2,
                            'itemtype'  => 'Budget',
                            'query'     => 'is_deleted=0&as_map=0&criteria%5B0%5D%5Blink%5D=AND&criteria%5B0%5D%5Bfield%5D=40&criteria%5B0%5D%5Bsearchtype%5D=contains&criteria%5B0%5D%5Bvalue%5D=LT1&criteria%5B1%5D%5Blink%5D=AND&criteria%5B1%5D%5Bitemtype%5D=Computer&criteria%5B1%5D%5Bmeta%5D=1&criteria%5B1%5D%5Bfield%5D=40&criteria%5B1%5D%5Bsearchtype%5D=contains&criteria%5B1%5D%5Bvalue%5D=&search=Search&itemtype=Computer',
                        ],
                        [
                            'id'        => 3,
                            'itemtype'  => 'Monitor',
                            'query'     => 'is_deleted=0&as_map=0&criteria%5B0%5D%5Blink%5D=AND&criteria%5B0%5D%5Bfield%5D=40&criteria%5B0%5D%5Bsearchtype%5D=contains&criteria%5B0%5D%5Bvalue%5D=LT1&criteria%5B1%5D%5Blink%5D=AND&criteria%5B1%5D%5Bitemtype%5D=Budget&criteria%5B1%5D%5Bmeta%5D=1&criteria%5B1%5D%5Bfield%5D=40&criteria%5B1%5D%5Bsearchtype%5D=contains&criteria%5B1%5D%5Bvalue%5D=&search=Search&itemtype=Monitor',
                        ],
                    ]);
                }
                return new ArrayIterator([]);
            },
        ]);

        $migration->changeSearchOption('Computer', 40, 100);
        $migration->changeSearchOption('Printer', 20, 10);
        $migration->changeSearchOption('Ticket', 1, 1001);
        $migration->executeMigration();

        $this->assertEquals([
            "UPDATE `glpi_displaypreferences` SET `num` = '100' WHERE `itemtype` = 'Computer' AND `num` = '40'",
            "DELETE `glpi_displaypreferences` FROM `glpi_displaypreferences` WHERE `id` IN ('12', '156', '421')",
            "UPDATE `glpi_displaypreferences` SET `num` = '10' WHERE `itemtype` = 'Printer' AND `num` = '20'",
            "UPDATE `glpi_displaypreferences` SET `num` = '1001' WHERE `itemtype` = 'Ticket' AND `num` = '1'",
            "UPDATE `glpi_tickettemplatehiddenfields` SET `num` = '1001' WHERE `num` = '1'",
            "UPDATE `glpi_tickettemplatemandatoryfields` SET `num` = '1001' WHERE `num` = '1'",
            "UPDATE `glpi_tickettemplatepredefinedfields` SET `num` = '1001' WHERE `num` = '1'",
            "UPDATE `glpi_savedsearches` SET `query` = 'is_deleted=0&as_map=0&criteria%5B0%5D%5Blink%5D=AND&criteria%5B0%5D%5Bfield%5D=100&criteria%5B0%5D%5Bsearchtype%5D=contains&criteria%5B0%5D%5Bvalue%5D=LT1&criteria%5B1%5D%5Blink%5D=AND&criteria%5B1%5D%5Bitemtype%5D=Budget&criteria%5B1%5D%5Bmeta%5D=1&criteria%5B1%5D%5Bfield%5D=4&criteria%5B1%5D%5Bsearchtype%5D=contains&criteria%5B1%5D%5Bvalue%5D=&search=Search&itemtype=Computer' WHERE `id` = '1'",
            "UPDATE `glpi_savedsearches` SET `query` = 'is_deleted=0&as_map=0&criteria%5B0%5D%5Blink%5D=AND&criteria%5B0%5D%5Bfield%5D=40&criteria%5B0%5D%5Bsearchtype%5D=contains&criteria%5B0%5D%5Bvalue%5D=LT1&criteria%5B1%5D%5Blink%5D=AND&criteria%5B1%5D%5Bitemtype%5D=Computer&criteria%5B1%5D%5Bmeta%5D=1&criteria%5B1%5D%5Bfield%5D=100&criteria%5B1%5D%5Bsearchtype%5D=contains&criteria%5B1%5D%5Bvalue%5D=&search=Search&itemtype=Computer' WHERE `id` = '2'",
        ], $migration->getMockedQueries());
    }

    public function testRemoveSearchOption()
    {
        $migration = $this->getMigrationMock(db_options: [
            '_mock_tableExists' => true,
            '_mock_request' => function ($request) {
                if (isset($request['INNER JOIN'])) {
                    $result = [];
                    if ($request['INNER JOIN'][\DisplayPreference::getTable() . ' AS old']['ON'][0]['AND']['new.itemtype'] === 'Printer') {
                        // Simulate duplicated search options on 'Printer'
                        $result = [
                            [
                                'id' => 12,
                            ],
                            [
                                'id' => 156,
                            ],
                            [
                                'id' => 421,
                            ],
                        ];
                    }
                    return new ArrayIterator($result);
                }
                if ($request['FROM'] === \SavedSearch::getTable()) {
                    return new ArrayIterator([
                        [
                            'id'        => 1,
                            'itemtype'  => 'Computer',
                            'query'     => 'is_deleted=0&as_map=0&criteria%5B0%5D%5Blink%5D=AND&criteria%5B0%5D%5Bfield%5D=40&criteria%5B0%5D%5Bsearchtype%5D=contains&criteria%5B0%5D%5Bvalue%5D=LT1&criteria%5B1%5D%5Blink%5D=AND&criteria%5B1%5D%5Bitemtype%5D=Budget&criteria%5B1%5D%5Bmeta%5D=1&criteria%5B1%5D%5Bfield%5D=4&criteria%5B1%5D%5Bsearchtype%5D=contains&criteria%5B1%5D%5Bvalue%5D=&search=Search&itemtype=Computer',
                        ],
                        [
                            'id'        => 2,
                            'itemtype'  => 'Budget',
                            'query'     => 'is_deleted=0&as_map=0&criteria%5B0%5D%5Blink%5D=AND&criteria%5B0%5D%5Bfield%5D=40&criteria%5B0%5D%5Bsearchtype%5D=contains&criteria%5B0%5D%5Bvalue%5D=LT1&criteria%5B1%5D%5Blink%5D=AND&criteria%5B1%5D%5Bitemtype%5D=Computer&criteria%5B1%5D%5Bmeta%5D=1&criteria%5B1%5D%5Bfield%5D=40&criteria%5B1%5D%5Bsearchtype%5D=contains&criteria%5B1%5D%5Bvalue%5D=&search=Search&itemtype=Computer',
                        ],
                        [
                            'id'        => 3,
                            'itemtype'  => 'Monitor',
                            'query'     => 'is_deleted=0&as_map=0&criteria%5B0%5D%5Blink%5D=AND&criteria%5B0%5D%5Bfield%5D=40&criteria%5B0%5D%5Bsearchtype%5D=contains&criteria%5B0%5D%5Bvalue%5D=LT1&criteria%5B1%5D%5Blink%5D=AND&criteria%5B1%5D%5Bitemtype%5D=Budget&criteria%5B1%5D%5Bmeta%5D=1&criteria%5B1%5D%5Bfield%5D=40&criteria%5B1%5D%5Bsearchtype%5D=contains&criteria%5B1%5D%5Bvalue%5D=&search=Search&itemtype=Monitor',
                        ],
                    ]);
                }
                return new ArrayIterator([]);
            },
        ]);

        $migration->removeSearchOption('Computer', 40);
        $migration->removeSearchOption('Printer', 20);
        $migration->removeSearchOption('Ticket', 1);
        $migration->executeMigration();

        $this->assertEquals([
            "DELETE `glpi_displaypreferences` FROM `glpi_displaypreferences` WHERE `itemtype` = 'Computer' AND `num` = '40'",
            "DELETE `glpi_displaypreferences` FROM `glpi_displaypreferences` WHERE `itemtype` = 'Printer' AND `num` = '20'",
            "DELETE `glpi_displaypreferences` FROM `glpi_displaypreferences` WHERE `itemtype` = 'Ticket' AND `num` = '1'",
            "DELETE `glpi_tickettemplatehiddenfields` FROM `glpi_tickettemplatehiddenfields` WHERE `num` = '1'",
            "DELETE `glpi_tickettemplatemandatoryfields` FROM `glpi_tickettemplatemandatoryfields` WHERE `num` = '1'",
            "DELETE `glpi_tickettemplatepredefinedfields` FROM `glpi_tickettemplatepredefinedfields` WHERE `num` = '1'",
            "UPDATE `glpi_savedsearches` SET `query` = 'is_deleted=0&as_map=0&criteria%5B1%5D%5Blink%5D=AND&criteria%5B1%5D%5Bitemtype%5D=Budget&criteria%5B1%5D%5Bmeta%5D=1&criteria%5B1%5D%5Bfield%5D=4&criteria%5B1%5D%5Bsearchtype%5D=contains&criteria%5B1%5D%5Bvalue%5D=&search=Search&itemtype=Computer' WHERE `id` = '1'",
            "UPDATE `glpi_savedsearches` SET `query` = 'is_deleted=0&as_map=0&criteria%5B0%5D%5Blink%5D=AND&criteria%5B0%5D%5Bfield%5D=40&criteria%5B0%5D%5Bsearchtype%5D=contains&criteria%5B0%5D%5Bvalue%5D=LT1&search=Search&itemtype=Computer' WHERE `id` = '2'",
        ], $migration->getMockedQueries());
    }

    public function testReplaceRight()
    {
        global $DB;

        $migration = $this->getMigrationMock(db_options: ['_real_db' => true]);

        //Test updating a UPDATE right when profile has READ and UPDATE config right (Default)
        $migration->replaceRight('test_replaceright_1', READ);

        $this->assertCount(1, $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE'  => [
                'name'   => 'test_replaceright_1',
                'rights' => READ,
            ],
        ]));

        //Test updating a READ right when profile has UPDATE group right
        $migration->replaceRight('test_replaceright_2', READ, ['group' => UPDATE]);

        $this->assertCount(2, $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE'  => [
                'name'   => 'test_replaceright_2',
                'rights' => READ,
            ],
        ]));

        //Test updating an UPDATE right when profile has READ and UPDATE group right and CREATE entity right
        $migration->replaceRight('test_replaceright_2', UPDATE, [
            'group'  => READ | UPDATE,
            'entity' => CREATE,
        ]);

        $this->assertCount(1, $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE'  => [
                'name'   => 'test_replaceright_2',
                'rights' => UPDATE,
            ],
        ]));

        //Test updating a READ right when profile with no requirements
        $migration->replaceRight('test_replaceright_3', READ, []);

        $this->assertCount(8, $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE'  => [
                'name'   => 'test_replaceright_3',
                'rights' => READ,
            ],
        ]));
    }

    public function testGiveRight()
    {
        global $DB;

        $profiles_id = getItemByTypeName('Profile', 'Super-Admin', true);

        // Adding profiles with different rights
        $DB->insert('glpi_profilerights', [
            'name' => 'test_giveright_1',
            'profiles_id' => $profiles_id,
            'rights' => 0,
        ]);
        $DB->insert('glpi_profilerights', [
            'name' => 'test_giveright_2',
            'profiles_id' => $profiles_id,
            'rights' => READ | UPDATE,
        ]);
        $DB->insert('glpi_profilerights', [
            'name' => 'test_giveright_3',
            'profiles_id' => $profiles_id,
            'rights' => 0,
        ]);

        $migration = $this->getMigrationMock(db_options: ['_real_db' => true]);

        // Adding a READ right with default required rights
        $migration->giveRight('test_giveright_1', READ);
        $rights = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE' => [
                'name' => 'test_giveright_1',
            ],
        ]);
        $this->assertCount(1, $rights);
        $rights = $rights->current();
        $this->assertEquals(READ, $rights['rights']);

        // Adding an UPDATE right with default required rights
        $migration->giveRight('test_giveright_1', UPDATE);
        $rights = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE' => [
                'name' => 'test_giveright_1',
            ],
        ]);
        $this->assertCount(1, $rights);
        $rights = $rights->current();
        $this->assertEquals(READ + UPDATE, $rights['rights']);

        // Adding a READ right for second time
        // Should not change the rights because it's already set
        $migration->giveRight('test_giveright_1', READ);
        $rights = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE' => [
                'name' => 'test_giveright_1',
            ],
        ]);
        $this->assertCount(1, $rights);
        $rights = $rights->current();
        $this->assertEquals(READ + UPDATE, $rights['rights']);

        // Adding a right with specific required rights
        $migration->giveRight('test_giveright_2', CREATE, ['test_giveright_2' => READ | UPDATE]);
        $rights = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE' => [
                'name' => 'test_giveright_2',
            ],
        ]);
        $this->assertCount(1, $rights);
        $rights = $rights->current();
        $this->assertEquals(READ | UPDATE | CREATE, $rights['rights']);

        // Trying to add a right with specific required rights that are not met
        $migration->giveRight('test_giveright_3', CREATE, ['test_giveright_3' => READ | UPDATE]);
        $rights = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE' => [
                'name' => 'test_giveright_3',
            ],
        ]);
        $this->assertCount(1, $rights);
        $rights = $rights->current();
        $this->assertEquals(0, $rights['rights']);
    }

    public function testRemoveConfig()
    {
        global $DB;

        $this->assertNotFalse($DB->insert('glpi_configs', [
            'name' => __FUNCTION__,
            'value' => 'test',
            'context' => 'test',
        ]));

        $migration = $this->getMigrationMock(db_options: ['_real_db' => true]);

        $migration->removeConfig([__FUNCTION__]);
        // Shouldn't be deleted. Wrong context
        $this->assertCount(1, $DB->request([
            'FROM' => 'glpi_configs',
            'WHERE' => [
                'name' => __FUNCTION__,
            ],
        ]));

        $migration->removeConfig([__FUNCTION__], 'test');
        // Should be deleted
        $this->assertCount(0, $DB->request([
            'FROM' => 'glpi_configs',
            'WHERE' => [
                'name' => __FUNCTION__,
            ],
        ]));
    }

    public static function reloadCurrentProfileDataProvider()
    {
        return [
            [
                'fn' => function ($migration) {
                    $migration->addRight('testReloadCurrentProfile', READ);
                },
            ],
            [
                'fn' => function ($migration) {
                    $migration->addRightByInterface('testReloadCurrentProfile', READ, ['interface' => 'central']);
                },
            ],
            [
                'fn' => function ($migration) {
                    $migration->replaceRight('testReloadCurrentProfile', READ);
                },
            ],
        ];
    }

    #[DataProvider('reloadCurrentProfileDataProvider')]
    public function testReloadCurrentProfile($fn)
    {
        global $DB;

        $sub_query = new QuerySubQuery([
            'SELECT' => [
                'profiles_id',
            ],
            'FROM' => 'glpi_profilerights',
            'WHERE' => [
                'name' => 'config',
                'rights' => READ | UPDATE,
            ],
        ]);

        $DB->update('glpi_profiles', [
            'last_rights_update' => null,
        ], [
            'id' => $sub_query,
        ]);

        $migration = $this->getMigrationMock(db_options: ['_real_db' => true]);
        //Test adding a READ right when profile has READ and UPDATE config right (Default)
        $fn($migration);

        $last_rights_updates = $DB->request([
            'SELECT' => [
                'last_rights_update',
            ],
            'FROM' => 'glpi_profiles',
            'WHERE' => [
                'id' => $sub_query,
            ],
        ]);

        foreach ($last_rights_updates as $last_rights_update) {
            $this->assertEquals($_SESSION['glpi_currenttime'], $last_rights_update['last_rights_update']);
        }
    }

    private function getDbMock(array $options = [])
    {
        $db = new class ($options) extends \DB {
            private array $_mock_options;
            public array $_queries = [];
            public $dbdefault = 'mockedglpi';

            public function __construct(array $options)
            {
                $this->_mock_options = $options;
                parent::__construct();
            }

            public function doQuery($query)
            {
                $this->_queries[] = $query;
                if (isset($this->_mock_options['_mock_doQuery'])) {
                    return is_callable($this->_mock_options['_mock_doQuery'])
                        ? ($this->_mock_options['_mock_doQuery'])($query)
                        : $this->_mock_options['_mock_doQuery'];
                }
                return true;
            }

            public function freeResult($result)
            {
                return true;
            }

            public function numrows($result)
            {
                return $this->_mock_options['_mock_numrows'] ?? parent::numrows($result);
            }

            public function fetchAssoc($result)
            {
                return $this->_mock_options['_mock_fetchAssoc'] ?? parent::fetchAssoc($result);
            }

            public function dataSeek($result, $num)
            {
                return $this->_mock_options['_mock_dataSeek'] ?? parent::dataSeek($result, $num);
            }

            public function listFields($table, $usecache = true)
            {
                return $this->_mock_options['_mock_listFields'] ?? parent::listFields($table, $usecache);
            }

            public function request($criteria)
            {
                if (isset($this->_mock_options['_mock_request'])) {
                    return is_callable($this->_mock_options['_mock_request'])
                        ? ($this->_mock_options['_mock_request'])($criteria)
                        : $this->_mock_options['_mock_request'];
                }
                return parent::request($criteria);
            }

            public function tableExists($tablename, $usecache = true)
            {
                if (isset($this->_mock_options['_mock_tableExists'])) {
                    return is_callable($this->_mock_options['_mock_tableExists'])
                        ? ($this->_mock_options['_mock_tableExists'])($tablename)
                        : $this->_mock_options['_mock_tableExists'];
                }
                return parent::tableExists($tablename, $usecache);
            }

            public function fieldExists($table, $field, $usecache = true)
            {
                if (isset($this->_mock_options['_mock_fieldExists'])) {
                    return is_callable($this->_mock_options['_mock_fieldExists'])
                        ? ($this->_mock_options['_mock_fieldExists'])($table, $field)
                        : $this->_mock_options['_mock_fieldExists'];
                }
                return parent::fieldExists($table, $field, $usecache);
            }

            public function quote($value, int $type = 2)
            {
                // Proxy quote to the real DB instance since the mock has no mysqli handler
                global $DB;
                return $DB->quote($value, $type);
            }
        };
        $db->disableTableCaching();
        return $db;
    }

    private function getMigrationMock($ver = GLPI_VERSION, ?AbstractProgressIndicator $progress_indicator = null, array $db_options = [])
    {
        global $DB;
        $db = ($db_options['_real_db'] ?? false) ? $DB : $this->getDbMock($db_options);
        foreach ($db_options as $property => $value) {
            if (property_exists(\DBmysql::class, $property)) {
                $db->{$property} = $value;
            }
        }

        return new class ($ver, $progress_indicator, $db) extends Migration {
            public function __construct($ver, ?AbstractProgressIndicator $progress_indicator = null, $db = null)
            {
                parent::__construct($ver, $progress_indicator);
                $this->db = $db;
            }

            public function displayTitle($title): void
            {
                echo $title;
            }

            public function displayMessage($msg)
            {
                echo $msg;
            }

            public function displayWarning($msg, $red = false): void
            {
                echo $msg;
            }

            public function getMockedQueries(): array
            {
                return $this->db->_queries ?? [];
            }

            public function clearMockedQueries(): void
            {
                $this->db->_queries = [];
            }

            public function getMockedDb(): \DBmysql
            {
                return $this->db;
            }

            protected function getDefaultCollation(): string
            {
                return $this->getMockedDb()->use_utf8mb4 ? 'utf8mb4_unicode_ci' : 'utf8_unicode_ci';
            }

            protected function getDefaultCharset(): string
            {
                return $this->getMockedDb()->use_utf8mb4 ? 'utf8mb4' : 'utf8';
            }

            protected function getDefaultPrimaryKeySignOption(): string
            {
                return $this->getMockedDb()->allow_signed_keys ? '' : 'unsigned';
            }

            protected function hasKey($table, $indexname): bool
            {
                $result = $this->getMockedDb()->doQuery("SHOW INDEX FROM `$table`");

                if ($result && $this->getMockedDb()->numrows($result)) {
                    while ($data = $this->getMockedDb()->fetchAssoc($result)) {
                        if ($data["Key_name"] === $indexname) {
                            return true;
                        }
                    }
                }
                return false;
            }
        };
    }
}
