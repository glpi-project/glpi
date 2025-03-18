<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

/* Test for inc/migration.class.php */
class Migration extends \GLPITestCase
{
    /**
     * @var \DB
     */
    private $db;

    /**
     * @var \Migration
     */
    private $migration;

    /**
     * @var string[]
     */
    private $queries;

    public function beforeTestMethod($method)
    {
        parent::beforeTestMethod($method);
        if ($method !== 'testConstructor') {
            $this->db = new \mock\DB();
            $this->db->disableTableCaching();
            $queries = [];
            $this->queries = &$queries;
            $this->calling($this->db)->doQuery = function ($query) use (&$queries) {
                $queries[] = $query;
                return true;
            };
            $this->calling($this->db)->freeResult = true;

            $this->output(
                function () {
                    $this->migration = new \mock\Migration(GLPI_VERSION);
                    $this->calling($this->migration)->displayTitle = function ($msg) {
                        echo $msg;
                    };
                    $this->calling($this->migration)->displayMessage = function ($msg) {
                        echo $msg;
                    };
                    $this->calling($this->migration)->displayWarning = function ($msg) {
                        echo $msg;
                    };
                }
            );
        }
    }

    public function testConstructor()
    {
        $this->output(
            function () {
                new \Migration(GLPI_VERSION);
            }
        )->isEmpty();
    }

    public function testPrePostQueries()
    {
        global $DB;
        $DB = $this->db;

        $this->output(
            function () {
                $this->migration->addPostQuery('UPDATE post_table SET mfield = "myvalue"');
                $this->migration->addPreQuery('UPDATE pre_table SET mfield = "myvalue"');
                $this->migration->addPostQuery('UPDATE post_otable SET ofield = "myvalue"');

                $this->migration->executeMigration();
            }
        )->isIdenticalTo("Task completed.");

        $this->array($this->queries)->isIdenticalTo([
            'UPDATE pre_table SET mfield = "myvalue"',
            'UPDATE post_table SET mfield = "myvalue"',
            'UPDATE post_otable SET ofield = "myvalue"'
        ]);
    }

    public function testAddConfig()
    {
        global $DB;
        $this->calling($this->db)->numrows = 0;
        $this->calling($this->db)->fetchAssoc = [];
        $this->calling($this->db)->dataSeek = true;
        $this->calling($this->db)->listFields = [
            'id'        => '',
            'context'   => '',
            'name'      => '',
            'value'     => ''
        ];
        $DB = $this->db;

       //test with non-existing value => new keys should be inserted
        $this->migration->addConfig([
            'one' => 'key',
            'two' => 'value'
        ]);

        $this->output(
            function () {
                $this->migration->executeMigration();
            }
        )->isIdenticalTo('Configuration values added for one, two (core).Task completed.');

        $core_queries = [
            'SELECT * FROM `glpi_configs` WHERE `context` = \'core\' AND `name` IN (\'one\', \'two\')',
            'SELECT `id` FROM `glpi_configs` WHERE `context` = \'core\' AND `name` = \'one\'',
            'INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES (\'core\', \'one\', \'key\')',
            'SELECT * FROM `glpi_configs` WHERE `glpi_configs`.`id` = \'0\' LIMIT 1',
            'INSERT INTO `glpi_logs` (`items_id`, `itemtype`, `itemtype_link`, `linked_action`, `user_name`, `date_mod`, `id_search_option`, `old_value`, `new_value`) VALUES (\'1\', \'Config\', \'\', \'0\', \'\', \'' . $_SESSION['glpi_currenttime'] . '\', \'1\', \'one \', \'key\')',
            'SELECT `table_name` AS `TABLE_NAME` FROM `information_schema`.`tables` WHERE `table_schema` = \'' . $DB->dbdefault . '\' AND `table_type` = \'BASE TABLE\' AND `table_name` LIKE \'glpi_webhooks\'',
            'SELECT `id` FROM `glpi_configs` WHERE `context` = \'core\' AND `name` = \'two\'',
            'INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES (\'core\', \'two\', \'value\')',
            'SELECT * FROM `glpi_configs` WHERE `glpi_configs`.`id` = \'0\' LIMIT 1',
            'INSERT INTO `glpi_logs` (`items_id`, `itemtype`, `itemtype_link`, `linked_action`, `user_name`, `date_mod`, `id_search_option`, `old_value`, `new_value`) VALUES (\'1\', \'Config\', \'\', \'0\', \'\', \'' . $_SESSION['glpi_currenttime'] . '\', \'1\', \'two \', \'value\')',
            'SELECT `table_name` AS `TABLE_NAME` FROM `information_schema`.`tables` WHERE `table_schema` = \'' . $DB->dbdefault . '\' AND `table_type` = \'BASE TABLE\' AND `table_name` LIKE \'glpi_webhooks\'',
        ];
        $this->array($this->queries)->isIdenticalTo($core_queries, print_r($this->queries, true));

       //test with existing value on different context => new keys should be inserted in correct context
        $this->queries = [];
        $this->migration->addConfig([
            'one' => 'key',
            'two' => 'value'
        ], 'test-context');

        $this->output(
            function () {
                $this->migration->executeMigration();
            }
        )->isIdenticalTo('Configuration values added for one, two (test-context).Task completed.');

        $this->array($this->queries)->isIdenticalTo([
            'SELECT * FROM `glpi_configs` WHERE `context` = \'test-context\' AND `name` IN (\'one\', \'two\')',
            'SELECT `id` FROM `glpi_configs` WHERE `context` = \'test-context\' AND `name` = \'one\'',
            'INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES (\'test-context\', \'one\', \'key\')',
            'SELECT * FROM `glpi_configs` WHERE `glpi_configs`.`id` = \'0\' LIMIT 1',
            'INSERT INTO `glpi_logs` (`items_id`, `itemtype`, `itemtype_link`, `linked_action`, `user_name`, `date_mod`, `id_search_option`, `old_value`, `new_value`) VALUES (\'1\', \'Config\', \'\', \'0\', \'\', \'' . $_SESSION['glpi_currenttime'] . '\', \'1\', \'one (test-context) \', \'key\')',
            'SELECT `table_name` AS `TABLE_NAME` FROM `information_schema`.`tables` WHERE `table_schema` = \'' . $DB->dbdefault . '\' AND `table_type` = \'BASE TABLE\' AND `table_name` LIKE \'glpi_webhooks\'',
            'SELECT `id` FROM `glpi_configs` WHERE `context` = \'test-context\' AND `name` = \'two\'',
            'INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES (\'test-context\', \'two\', \'value\')',
            'SELECT * FROM `glpi_configs` WHERE `glpi_configs`.`id` = \'0\' LIMIT 1',
            'INSERT INTO `glpi_logs` (`items_id`, `itemtype`, `itemtype_link`, `linked_action`, `user_name`, `date_mod`, `id_search_option`, `old_value`, `new_value`) VALUES (\'1\', \'Config\', \'\', \'0\', \'\', \'' . $_SESSION['glpi_currenttime'] . '\', \'1\', \'two (test-context) \', \'value\')',
            'SELECT `table_name` AS `TABLE_NAME` FROM `information_schema`.`tables` WHERE `table_schema` = \'' . $DB->dbdefault . '\' AND `table_type` = \'BASE TABLE\' AND `table_name` LIKE \'glpi_webhooks\'',
        ]);

       //test with one existing value => only new key should be inserted
        $this->migration->addConfig([
            'one' => 'key',
            'two' => 'value'
        ]);
        $this->queries = [];
        $this->calling($this->db)->request = function ($criteria) {
           // Call using 'glpi_configs' value for first parameter
           // corresponds to the call made to retrieve existing values
           // -> returns a value for config 'one'
            if ($criteria === ['FROM' => 'glpi_configs', 'WHERE' => ['context' => 'core', 'name' => ['one', 'two']]]) {
                  $dbresult = [[
                      'id'        => '42',
                      'context'   => 'core',
                      'name'      => 'one',
                      'value'     => 'setted value'
                  ]
                  ];
                  return new \ArrayIterator($dbresult);
            }
          // Other calls corresponds to call made in Config::setConfigurationValues()
            return new \ArrayIterator();
        };

        $DB = $this->db;

        $this->output(
            function () {
                $this->migration->executeMigration();
            }
        )->isIdenticalTo('Configuration values added for two (core).Task completed.');

        $this->array($this->queries)->isIdenticalTo([
            0 => 'INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES (\'core\', \'two\', \'value\')',
            1 => 'INSERT INTO `glpi_logs` (`items_id`, `itemtype`, `itemtype_link`, `linked_action`, `user_name`, `date_mod`, `id_search_option`, `old_value`, `new_value`) VALUES (\'1\', \'Config\', \'\', \'0\', \'\', \'' . $_SESSION['glpi_currenttime'] . '\', \'1\', \'two \', \'value\')',
        ]);
    }

    public function testBackupTables()
    {
        global $DB;
        $this->calling($this->db)->numrows = 0;
        $DB = $this->db;

       //try to backup non existant tables
        $this->output(
            function () {
                $this->migration->backupTables(['table1', 'table2']);
                $this->migration->executeMigration();
            }
        )->isIdenticalTo("Task completed.");

        $this->array($this->queries)->isIdenticalTo([
            0 => 'SELECT `table_name` AS `TABLE_NAME` FROM `information_schema`.`tables`' .
               ' WHERE `table_schema` = \'' . $DB->dbdefault .
               '\' AND `table_type` = \'BASE TABLE\' AND `table_name` LIKE \'table1\'',
            1 => 'SELECT `table_name` AS `TABLE_NAME` FROM `information_schema`.`tables`' .
               ' WHERE `table_schema` = \'' . $DB->dbdefault  .
               '\' AND `table_type` = \'BASE TABLE\' AND `table_name` LIKE \'table2\''
        ]);

        //try to backup existant tables
        $this->queries = [];
        $this->calling($this->db)->tableExists = true;
        $DB = $this->db;
        $this->exception(
            function () {
                $this->migration->backupTables(['glpi_existingtest']);
                $this->migration->executeMigration();
            }
        )->message->contains('Unable to rename table glpi_existingtest (ok) to backup_glpi_existingtest (nok)!');

        $this->array($this->queries)->isIdenticalTo([
            0 => 'DROP TABLE `backup_glpi_existingtest`',
        ]);

        $this->queries = [];
        $this->calling($this->db)->tableExists = function ($name) {
            return $name == 'glpi_existingtest';
        };
        $DB = $this->db;
        $this->output(
            function () {
                $this->migration->backupTables(['glpi_existingtest']);
                $this->migration->executeMigration();
            }
        )->isIdenticalTo("glpi_existingtest table already exists. " .
         "A backup have been done to backup_glpi_existingtest" .
         "You can delete backup tables if you have no need of them.Task completed.");

        $this->array($this->queries)->isIdenticalTo([
            0 => 'RENAME TABLE `glpi_existingtest` TO `backup_glpi_existingtest`',
        ]);
    }

    public function testChangeField()
    {
        global $DB;
        $DB = $this->db;

       // Test change field with move to first column
        $this->calling($this->db)->fieldExists = true;

        $this->output(
            function () {
                $this->migration->changeField('change_table', 'ID', 'id', 'integer', ['first' => 'first']);
                $this->migration->executeMigration();
            }
        )->isIdenticalTo("Change of the database layout - change_tableTask completed.");

        $this->array($this->queries)->isIdenticalTo([
            "ALTER TABLE `change_table` DROP `id` ,\n" .
         "CHANGE `ID` `id` INT NOT NULL DEFAULT '0'   FIRST  ",
        ]);

       // Test change field with move to after another column
        $this->queries = [];
        $this->calling($this->db)->fieldExists = true;

        $this->output(
            function () {
                $this->migration->changeField('change_table', 'NAME', 'name', 'string', ['after' => 'id']);
                $this->migration->executeMigration();
            }
        )->isIdenticalTo("Change of the database layout - change_tableTask completed.");

        $collate = $DB->use_utf8mb4 ? 'utf8mb4_unicode_ci' : 'utf8_unicode_ci';
        $this->array($this->queries)->isIdenticalTo([
            "ALTER TABLE `change_table` DROP `name` ,\n" .
         "CHANGE `NAME` `name` VARCHAR(255) COLLATE $collate DEFAULT NULL   AFTER `id` ",
        ]);
    }

    protected function fieldsFormatsProvider()
    {
        return [
            [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'bool',
                'options'   => [],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` TINYINT NOT NULL DEFAULT '0'   "
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'bool',
                'options'   => ['value' => 1],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` TINYINT NOT NULL DEFAULT '1'   "
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'char',
                'options'   => [],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` CHAR(1) DEFAULT NULL   "
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'char',
                'options'   => ['value' => 'a'],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` CHAR(1) NOT NULL DEFAULT 'a'   "
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
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` INT NOT NULL DEFAULT '0'   "
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'integer',
                'options'   => ['value' => 2],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` INT NOT NULL DEFAULT '2'   "
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'date',
                'options'   => [],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` DATE DEFAULT NULL   "
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'date',
                'options'   => ['value' => '2018-06-04'],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` DATE DEFAULT '2018-06-04'   "
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'datetime',
                'options'   => [],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` TIMESTAMP NULL DEFAULT NULL   "
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'datetime',
                'options'   => ['value' => '2018-06-04 08:16:38'],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` TIMESTAMP DEFAULT '2018-06-04 08:16:38'   "
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
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` INT NOT NULL DEFAULT '42'   "
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'integer',
                'options'   => ['comment' => 'a comment'],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` INT NOT NULL DEFAULT '0'  COMMENT 'a comment'  "
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'integer',
                'options'   => ['after' => 'other_field'],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` INT NOT NULL DEFAULT '0'   AFTER `other_field` "
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'integer',
                'options'   => ['first' => true],
                'sql'       => "ALTER TABLE `my_table` ADD `my_field` INT NOT NULL DEFAULT '0'   FIRST  "
            ], [
                'table'     => 'my_table',
                'field'     => 'my_field',
                'format'    => 'integer',
                'options'   => ['value' => '-2', 'update' => '0', 'condition' => 'WHERE `id` = 0'],
                'sql'       => [
                    "ALTER TABLE `my_table` ADD `my_field` INT NOT NULL DEFAULT '-2'   ",
                    "UPDATE `my_table`
                        SET `my_field` = 0 WHERE `id` = 0",
                ]
            ]
        ];
    }

    /**
     * @dataProvider fieldsFormatsProvider
     */
    public function testAddField($table, $field, $format, $options, $sql, array $db_properties = [])
    {
        global $DB;
        $DB = $this->db;
        foreach ($db_properties as $db_property => $value) {
            $DB->$db_property = $value;
        }
        $this->calling($this->db)->fieldExists = false;
        $this->queries = [];

        $this->output(
            function () use ($table, $field, $format, $options) {
                $this->migration->addField($table, $field, $format, $options);
                $this->migration->executeMigration();
            }
        )->isIdenticalTo("Change of the database layout - my_tableTask completed.");

        if (!is_array($sql)) {
            $sql = [$sql];
        }

        $this->array($this->queries)->isIdenticalTo($sql);
    }

    public function testFormatBooleanBadDefault()
    {
        global $DB;
        $DB = $this->db;
        $this->calling($this->db)->fieldExists = false;
        $this->queries = [];

        $this->exception(
            function () {
                $this->migration->addField('my_table', 'my_field', 'bool', ['value' => 2]);

                $this->output(
                    function () {
                        $this->migration->executeMigration();
                    }
                )->isEqualTo('Change of the database layout - my_tableTask completed.');
            }
        )->isInstanceOf(\LogicException::class)
         ->hasMessage('Default value must be 0 or 1.');
    }

    public function testFormatIntegerBadDefault()
    {
        global $DB;
        $DB = $this->db;
        $this->calling($this->db)->fieldExists = false;
        $this->queries = [];

        $this->exception(
            function () {
                $this->migration->addField('my_table', 'my_field', 'integer', ['value' => 'foo']);

                $this->output(
                    function () {
                        $this->migration->executeMigration();
                    }
                )->isEqualTo('Change of the database layout - my_tableTask completed.');
            }
        )->isInstanceOf(\LogicException::class)
         ->hasMessage('Default value must be numeric.');
    }

    public function testAddRight()
    {
        global $DB;

        // Clean DB to handle potential failure of previous test
        $DB->delete('glpi_profilerights', [
            'name' => [
                'test_addright_1', 'test_addright_2', 'test_addright_3', 'test_addright_4'
            ]
        ]);

        //Test adding a READ right when profile has READ and UPDATE config right (Default)
        $this->output(
            function () {
                $this->migration->addRight('test_addright_1', READ);
            }
        )->isEqualTo('New rights has been added for test_addright_1, you should review ACLs after update');

        //Test adding a READ right when profile has UPDATE group right
        $this->output(
            function () {
                $this->migration->addRight('test_addright_2', READ, ['group' => UPDATE]);
            }
        )->isEqualTo('New rights has been added for test_addright_2, you should review ACLs after update');

        //Test adding an UPDATE right when profile has READ and UPDATE group right and CREATE entity right
        $this->output(
            function () {
                $this->migration->addRight('test_addright_3', UPDATE, [
                    'group'  => READ | UPDATE,
                    'entity' => CREATE
                ]);
            }
        )->isEqualTo('New rights has been added for test_addright_3, you should review ACLs after update');

        //Test adding a READ right when profile with no requirements
        $this->output(
            function () {
                $this->migration->addRight('test_addright_4', READ, []);
            }
        )->isEqualTo('New rights has been added for test_addright_4, you should review ACLs after update');

        $right1 = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE'  => [
                'name'   => 'test_addright_1',
                'rights' => READ
            ]
        ]);
        $this->integer(count($right1))->isEqualTo(1);

        $right1 = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE'  => [
                'name'   => 'test_addright_2',
                'rights' => READ
            ]
        ]);
        $this->integer(count($right1))->isEqualTo(2);

        $right1 = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE'  => [
                'name'   => 'test_addright_3',
                'rights' => UPDATE
            ]
        ]);
        $this->integer(count($right1))->isEqualTo(1);

        $right1 = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE'  => [
                'name'   => 'test_addright_4',
                'rights' => READ
            ]
        ]);
        $this->integer(count($right1))->isEqualTo(8);

        //Test adding a READ right only on profiles where it has not been set yet
        $DB->delete('glpi_profilerights', [
            'profiles_id' => [1, 2, 3, 4],
            'name' => 'test_addright_4'
        ]);

        $this->output(
            function () {
                $this->migration->addRight('test_addright_4', READ | UPDATE, []);
            }
        )->isEqualTo('New rights has been added for test_addright_4, you should review ACLs after update');

        $right4 = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE'  => [
                'name'   => 'test_addright_4',
                'rights' => READ | UPDATE
            ]
        ]);
        $this->integer(count($right4))->isEqualTo(4);

        // Clean DB after test
        $DB->delete('glpi_profilerights', [
            'name' => [
                'test_addright_1', 'test_addright_2', 'test_addright_3', 'test_addright_4'
            ]
        ]);
    }

    public function testAddRightByInterface()
    {
        global $DB;

        $DB->delete('glpi_profilerights', [
            'name' => [
                'testright1', 'testright2'
            ]
        ]);
        //Test adding a READ right on central interface
        $this->output(
            function () {
                $this->migration->addRightByInterface('testright1', READ, ['interface' => 'central']);
            }
        )->isEqualTo('Rights has been updated for testright1, you should review ACLs after update');

        //Test adding a READ right on helpdesk interface
        $this->output(
            function () {
                $this->migration->addRightByInterface('testright2', READ, ['interface' => 'helpdesk']);
            }
        )->isEqualTo('Rights has been updated for testright2, you should review ACLs after update');

        //Check if rights have been added
        $right1 = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE'  => [
                'name'   => 'testright1',
                'rights' => READ
            ]
        ]);
        $this->integer(count($right1))->isEqualTo(7);

        $right2 = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE'  => [
                'name'   => 'testright2',
                'rights' => READ
            ]
        ]);
        $this->integer(count($right2))->isEqualTo(1);

        $DB->delete('glpi_profilerights', [
            'name' => [
                'testright1', 'testright2'
            ]
        ]);
    }

    public function testRenameTable()
    {

        global $DB;
        $DB = $this->db;

        $this->calling($this->db)->tableExists = function ($table) {
            return $table === 'glpi_oldtable';
        };
        $this->calling($this->db)->fieldExists = function ($table, $field) {
            return $table === 'glpi_oldtable' && $field !== 'bool_field';
        };

        $queries = [];
        $this->queries = &$queries;
        $this->calling($this->db)->doQuery = function ($query) use (&$queries) {
            if ($query === 'SHOW INDEX FROM `glpi_oldtable`') {
                  // Make DbUtils::isIndex return false
                  return false;
            }
            $queries[] = $query;
            return true;
        };

       // Case 1, rename with no buffered changes
        $this->queries = [];

        $this->migration->renameTable('glpi_oldtable', 'glpi_newtable');

        $this->array($this->queries)->isIdenticalTo(
            [
                "RENAME TABLE `glpi_oldtable` TO `glpi_newtable`",
            ]
        );

       // Case 2, rename after changes were already applied
        $this->queries = [];

        $this->migration->addField('glpi_oldtable', 'bool_field', 'bool');
        $this->migration->addKey('glpi_oldtable', 'id', 'id', 'UNIQUE');
        $this->migration->addKey('glpi_oldtable', 'fulltext_key', 'fulltext_key', 'FULLTEXT');
        $this->output(
            function () {
                $this->migration->migrationOneTable('glpi_oldtable');
            }
        )->isEqualTo(
            'Change of the database layout - glpi_oldtable'
            . 'Adding fulltext indices - glpi_oldtable'
            . 'Adding unicity indices - glpi_oldtable'
        );
        $this->migration->renameTable('glpi_oldtable', 'glpi_newtable');

        $this->array($this->queries)->isIdenticalTo(
            [
                "ALTER TABLE `glpi_oldtable` ADD `bool_field` TINYINT NOT NULL DEFAULT '0'   ",
                "ALTER TABLE `glpi_oldtable` ADD FULLTEXT `fulltext_key` (`fulltext_key`)",
                "ALTER TABLE `glpi_oldtable` ADD UNIQUE `id` (`id`)",
                "RENAME TABLE `glpi_oldtable` TO `glpi_newtable`",
            ]
        );

       // Case 3, apply changes after renaming
        $this->queries = [];

        $this->migration->addField('glpi_oldtable', 'bool_field', 'bool');
        $this->migration->addKey('glpi_oldtable', 'id', 'id', 'UNIQUE');
        $this->migration->addKey('glpi_oldtable', 'fulltext_key', 'fulltext_key', 'FULLTEXT');
        $this->migration->renameTable('glpi_oldtable', 'glpi_newtable');
        $this->output(
            function () {
                $this->migration->migrationOneTable('glpi_newtable');
            }
        )->isEqualTo(
            'Change of the database layout - glpi_newtable'
            . 'Adding fulltext indices - glpi_newtable'
            . 'Adding unicity indices - glpi_newtable'
        );

        $this->array($this->queries)->isIdenticalTo(
            [
                "RENAME TABLE `glpi_oldtable` TO `glpi_newtable`",
                "ALTER TABLE `glpi_newtable` ADD `bool_field` TINYINT NOT NULL DEFAULT '0'   ",
                "ALTER TABLE `glpi_newtable` ADD FULLTEXT `fulltext_key` (`fulltext_key`)",
                "ALTER TABLE `glpi_newtable` ADD UNIQUE `id` (`id`)",
            ]
        );
    }

    /**
     * Test Migration::renameItemtype().
     * Case: failure as source table does not exists.
     */
    public function testRenameItemtypeWhenSourceTableDoesNotExists()
    {
        global $DB;
        $DB = $this->db;

        $this->calling($this->db)->tableExists = false;

        $this->output(
            function () {
                $this->exception(
                    function () {
                        $this->migration->renameItemtype('SomeOldType', 'NewName');
                    }
                )->isInstanceOf(\RuntimeException::class)
                ->message
                ->contains('Table "glpi_someoldtypes" does not exists.');
            }
        )->isEqualTo('Renaming "SomeOldType" itemtype to "NewName"...');
    }

    /**
     * Test Migration::renameItemtype().
     * Case: failure as destination table already exists.
     */
    public function testRenameItemtypeWhenDestinationTableAlreadyExists()
    {
        global $DB;
        $DB = $this->db;

        $this->calling($this->db)->tableExists = true;

        $this->output(
            function () {
                $this->exception(
                    function () {
                        $this->migration->renameItemtype('SomeOldType', 'NewName');
                    }
                )->isInstanceOf(\RuntimeException::class)
                ->message
                ->contains('Table "glpi_someoldtypes" cannot be renamed as table "glpi_newnames" already exists.');
            }
        )->isEqualTo('Renaming "SomeOldType" itemtype to "NewName"...');
    }

    /**
     * Test Migration::renameItemtype().
     * Case: failure as foreign key field already in use somewhere.
     */
    public function testRenameItemtypeWhenDestinationFieldAlreadyExists()
    {
        global $DB;
        $DB = $this->db;

        $this->calling($this->db)->tableExists = function ($table) {
            return $table === 'glpi_someoldtypes';
        };
        $this->calling($this->db)->fieldExists = true;
        $this->calling($this->db)->request = new \ArrayIterator([
            [
                'TABLE_NAME' => 'glpi_item_with_fkey', 'COLUMN_NAME' => 'someoldtypes_id'
            ]
        ]);


        $this->output(
            function () {
                $this->exception(
                    function () {
                        $this->migration->renameItemtype('SomeOldType', 'NewName');
                    }
                )->isInstanceOf(\RuntimeException::class)
                ->message
                ->contains('Field "someoldtypes_id" cannot be renamed in table "glpi_item_with_fkey" as "newnames_id" is field already exists.');
            }
        )->isEqualTo('Renaming "SomeOldType" itemtype to "NewName"...');
    }

    /**
     * Test Migration::renameItemtype().
     * Case: success.
     */
    public function testRenameItemtype()
    {
        global $DB;
        $DB = $this->db;
        $DB->allow_signed_keys = false;

        $this->calling($this->db)->tableExists = function ($table) {
            return $table === 'glpi_someoldtypes';
        };
        $this->calling($this->db)->fieldExists = function ($table, $field) {
            return preg_match('/^someoldtypes_id/', $field);
        };
        $this->calling($this->db)->request = function ($request) {
            if (
                isset($request['WHERE']['OR'][0])
                && $request['WHERE']['OR'][0] === ['column_name'  => 'someoldtypes_id']
            ) {
                  // Request used for foreign key fields
                  return new \ArrayIterator([
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
                 return new \ArrayIterator([
                     ['TABLE_NAME' => 'glpi_computers', 'COLUMN_NAME' => 'itemtype'],
                     ['TABLE_NAME' => 'glpi_users',     'COLUMN_NAME' => 'itemtype'],
                     ['TABLE_NAME' => 'glpi_stuffs',    'COLUMN_NAME' => 'itemtype_source'],
                     ['TABLE_NAME' => 'glpi_stuffs',    'COLUMN_NAME' => 'itemtype_dest'],
                 ]);
            }
            return [];
        };

       // Test renaming with DB structure update
        $this->output(
            function () {
                $this->migration->renameItemtype('SomeOldType', 'NewName');
                $this->migration->executeMigration();
            }
        )->isIdenticalTo(
            implode(
                '',
                [
                    'Renaming "SomeOldType" itemtype to "NewName"...',
                    'Renaming "glpi_someoldtypes" table to "glpi_newnames"...',
                    'Renaming "someoldtypes_id" foreign keys to "newnames_id" in all tables...',
                    'Renaming "SomeOldType" itemtype to "NewName" in all tables...',
                    'Change of the database layout - glpi_oneitem_with_fkey',
                    'Change of the database layout - glpi_anotheritem_with_fkey',
                    'Task completed.',
                ]
            )
        );

        $this->array($this->queries)->isIdenticalTo([
            "RENAME TABLE `glpi_someoldtypes` TO `glpi_newnames`",
            "UPDATE `glpi_computers` SET `itemtype` = 'NewName' WHERE `itemtype` = 'SomeOldType'",
            "UPDATE `glpi_users` SET `itemtype` = 'NewName' WHERE `itemtype` = 'SomeOldType'",
            "UPDATE `glpi_stuffs` SET `itemtype_source` = 'NewName' WHERE `itemtype_source` = 'SomeOldType'",
            "UPDATE `glpi_stuffs` SET `itemtype_dest` = 'NewName' WHERE `itemtype_dest` = 'SomeOldType'",
            "ALTER TABLE `glpi_oneitem_with_fkey` CHANGE `someoldtypes_id` `newnames_id` int unsigned NOT NULL DEFAULT '0'   ",
            "ALTER TABLE `glpi_anotheritem_with_fkey` CHANGE `someoldtypes_id` `newnames_id` int unsigned NOT NULL DEFAULT '0'   ,\n"
                . "CHANGE `someoldtypes_id_tech` `newnames_id_tech` int unsigned NOT NULL DEFAULT '0'   ",
        ]);

       // Test renaming without DB structure update
        $this->queries = [];

        $this->output(
            function () {
                $this->migration->renameItemtype('SomeOldType', 'NewName', false);
                $this->migration->executeMigration();
            }
        )->isIdenticalTo(
            implode(
                '',
                [
                    'Renaming "SomeOldType" itemtype to "NewName"...',
                    'Renaming "SomeOldType" itemtype to "NewName" in all tables...',
                    'Task completed.',
                ]
            )
        );

        $this->array($this->queries)->isIdenticalTo([
            "UPDATE `glpi_computers` SET `itemtype` = 'NewName' WHERE `itemtype` = 'SomeOldType'",
            "UPDATE `glpi_users` SET `itemtype` = 'NewName' WHERE `itemtype` = 'SomeOldType'",
            "UPDATE `glpi_stuffs` SET `itemtype_source` = 'NewName' WHERE `itemtype_source` = 'SomeOldType'",
            "UPDATE `glpi_stuffs` SET `itemtype_dest` = 'NewName' WHERE `itemtype_dest` = 'SomeOldType'",
        ]);

        // Test renaming when old class and new class have the same table name
        $this->queries = [];

        $this->output(
            function () {
                $this->migration->renameItemtype('PluginFooThing', 'GlpiPlugin\\Foo\\Thing');
                $this->migration->executeMigration();
            }
        )->isIdenticalTo(
            implode(
                '',
                [
                    'Renaming "PluginFooThing" itemtype to "GlpiPlugin\\Foo\\Thing"...',
                    'Renaming "PluginFooThing" itemtype to "GlpiPlugin\\Foo\\Thing" in all tables...',
                    'Task completed.',
                ]
            )
        );

        $this->array($this->queries)->isIdenticalTo([
            "UPDATE `glpi_computers` SET `itemtype` = 'GlpiPlugin\\\\Foo\\\\Thing' WHERE `itemtype` = 'PluginFooThing'",
            "UPDATE `glpi_users` SET `itemtype` = 'GlpiPlugin\\\\Foo\\\\Thing' WHERE `itemtype` = 'PluginFooThing'",
            "UPDATE `glpi_stuffs` SET `itemtype_source` = 'GlpiPlugin\\\\Foo\\\\Thing' WHERE `itemtype_source` = 'PluginFooThing'",
            "UPDATE `glpi_stuffs` SET `itemtype_dest` = 'GlpiPlugin\\\\Foo\\\\Thing' WHERE `itemtype_dest` = 'PluginFooThing'",
        ]);
    }

    public function testChangeSearchOption()
    {
        global $DB;
        $DB = $this->db;

        $this->calling($this->db)->request = function ($request) {
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
                        ]
                    ];
                }
                return new \ArrayIterator($result);
            }
            if ($request['FROM'] === \SavedSearch::getTable()) {
                return new \ArrayIterator([
                    [
                        'id'        => 1,
                        'itemtype'  => 'Computer',
                        'query'     => 'is_deleted=0&as_map=0&criteria%5B0%5D%5Blink%5D=AND&criteria%5B0%5D%5Bfield%5D=40&criteria%5B0%5D%5Bsearchtype%5D=contains&criteria%5B0%5D%5Bvalue%5D=LT1&criteria%5B1%5D%5Blink%5D=AND&criteria%5B1%5D%5Bitemtype%5D=Budget&criteria%5B1%5D%5Bmeta%5D=1&criteria%5B1%5D%5Bfield%5D=4&criteria%5B1%5D%5Bsearchtype%5D=contains&criteria%5B1%5D%5Bvalue%5D=&search=Search&itemtype=Computer'
                    ],
                    [
                        'id'        => 2,
                        'itemtype'  => 'Budget',
                        'query'     => 'is_deleted=0&as_map=0&criteria%5B0%5D%5Blink%5D=AND&criteria%5B0%5D%5Bfield%5D=40&criteria%5B0%5D%5Bsearchtype%5D=contains&criteria%5B0%5D%5Bvalue%5D=LT1&criteria%5B1%5D%5Blink%5D=AND&criteria%5B1%5D%5Bitemtype%5D=Computer&criteria%5B1%5D%5Bmeta%5D=1&criteria%5B1%5D%5Bfield%5D=40&criteria%5B1%5D%5Bsearchtype%5D=contains&criteria%5B1%5D%5Bvalue%5D=&search=Search&itemtype=Computer'
                    ],
                    [
                        'id'        => 3,
                        'itemtype'  => 'Monitor',
                        'query'     => 'is_deleted=0&as_map=0&criteria%5B0%5D%5Blink%5D=AND&criteria%5B0%5D%5Bfield%5D=40&criteria%5B0%5D%5Bsearchtype%5D=contains&criteria%5B0%5D%5Bvalue%5D=LT1&criteria%5B1%5D%5Blink%5D=AND&criteria%5B1%5D%5Bitemtype%5D=Budget&criteria%5B1%5D%5Bmeta%5D=1&criteria%5B1%5D%5Bfield%5D=40&criteria%5B1%5D%5Bsearchtype%5D=contains&criteria%5B1%5D%5Bvalue%5D=&search=Search&itemtype=Monitor'
                    ]
                ]);
            }
            return new \ArrayIterator([]);
        };

        $this->migration->changeSearchOption('Computer', 40, 100);
        $this->migration->changeSearchOption('Printer', 20, 10);
        $this->migration->changeSearchOption('Ticket', 1, 1001);

        $this->calling($this->db)->tableExists = true;

        $this->output(
            function () {
                $this->migration->executeMigration();
            }
        )->isIdenticalTo('Task completed.');

        $this->array($this->queries)->isIdenticalTo([
            "UPDATE `glpi_displaypreferences` SET `num` = '100' WHERE `itemtype` = 'Computer' AND `num` = '40'",
            "DELETE `glpi_displaypreferences` FROM `glpi_displaypreferences` WHERE `id` IN ('12', '156', '421')",
            "UPDATE `glpi_displaypreferences` SET `num` = '10' WHERE `itemtype` = 'Printer' AND `num` = '20'",
            "UPDATE `glpi_displaypreferences` SET `num` = '1001' WHERE `itemtype` = 'Ticket' AND `num` = '1'",
            "UPDATE `glpi_tickettemplatehiddenfields` SET `num` = '1001' WHERE `num` = '1'",
            "UPDATE `glpi_tickettemplatemandatoryfields` SET `num` = '1001' WHERE `num` = '1'",
            "UPDATE `glpi_tickettemplatepredefinedfields` SET `num` = '1001' WHERE `num` = '1'",
            "UPDATE `glpi_savedsearches` SET `query` = 'is_deleted=0&as_map=0&criteria%5B0%5D%5Blink%5D=AND&criteria%5B0%5D%5Bfield%5D=100&criteria%5B0%5D%5Bsearchtype%5D=contains&criteria%5B0%5D%5Bvalue%5D=LT1&criteria%5B1%5D%5Blink%5D=AND&criteria%5B1%5D%5Bitemtype%5D=Budget&criteria%5B1%5D%5Bmeta%5D=1&criteria%5B1%5D%5Bfield%5D=4&criteria%5B1%5D%5Bsearchtype%5D=contains&criteria%5B1%5D%5Bvalue%5D=&search=Search&itemtype=Computer' WHERE `id` = '1'",
            "UPDATE `glpi_savedsearches` SET `query` = 'is_deleted=0&as_map=0&criteria%5B0%5D%5Blink%5D=AND&criteria%5B0%5D%5Bfield%5D=40&criteria%5B0%5D%5Bsearchtype%5D=contains&criteria%5B0%5D%5Bvalue%5D=LT1&criteria%5B1%5D%5Blink%5D=AND&criteria%5B1%5D%5Bitemtype%5D=Computer&criteria%5B1%5D%5Bmeta%5D=1&criteria%5B1%5D%5Bfield%5D=100&criteria%5B1%5D%5Bsearchtype%5D=contains&criteria%5B1%5D%5Bvalue%5D=&search=Search&itemtype=Computer' WHERE `id` = '2'",
        ]);
    }

    public function testReplaceRight()
    {
        global $DB;

        // Clean DB to handle potential failure of previous test
        $DB->delete('glpi_profilerights', [
            'name' => [
                'test_replaceright_1', 'test_replaceright_2', 'test_replaceright_3'
            ]
        ]);

        //Test updating a UPDATE right when profile has READ and UPDATE config right (Default)
        $this->output(
            function () {
                $this->migration->replaceRight('test_replaceright_1', READ);
            }
        )->isEqualTo('Rights has been updated for test_replaceright_1, you should review ACLs after update');

        $right1 = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE'  => [
                'name'   => 'test_replaceright_1',
                'rights' => READ
            ]
        ]);
        $this->integer(count($right1))->isEqualTo(1);

        //Test updating a READ right when profile has UPDATE group right
        $this->output(
            function () {
                $this->migration->replaceRight('test_replaceright_2', READ, ['group' => UPDATE]);
            }
        )->isEqualTo('Rights has been updated for test_replaceright_2, you should review ACLs after update');

        $right1 = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE'  => [
                'name'   => 'test_replaceright_2',
                'rights' => READ
            ]
        ]);
        $this->integer(count($right1))->isEqualTo(2);

        //Test updating an UPDATE right when profile has READ and UPDATE group right and CREATE entity right
        $this->output(
            function () {
                $this->migration->replaceRight('test_replaceright_2', UPDATE, [
                    'group'  => READ | UPDATE,
                    'entity' => CREATE
                ]);
            }
        )->isEqualTo('Rights has been updated for test_replaceright_2, you should review ACLs after update');

        $right1 = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE'  => [
                'name'   => 'test_replaceright_2',
                'rights' => UPDATE
            ]
        ]);
        $this->integer(count($right1))->isEqualTo(1);

        //Test updating a READ right when profile with no requirements
        $this->output(
            function () {
                $this->migration->replaceRight('test_replaceright_3', READ, []);
            }
        )->isEqualTo('Rights has been updated for test_replaceright_3, you should review ACLs after update');

        $right1 = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE'  => [
                'name'   => 'test_replaceright_3',
                'rights' => READ
            ]
        ]);
        $this->integer(count($right1))->isEqualTo(8);

        // Clean DB after test
        $DB->delete('glpi_profilerights', [
            'name' => [
                'test_replaceright_1', 'test_replaceright_2', 'test_replaceright_3'
            ]
        ]);
    }

    public function testGiveRight()
    {
        global $DB;

        // Clean profilrights table to handle potential failure of previous test
        $DB->delete('glpi_profilerights', [
            'name' => [
                'test_giveright_1',
                'test_giveright_2',
                'test_giveright_3'
            ]
        ]);

        $profiles_id = getItemByTypeName('Profile', 'Super-Admin', true);

        // Adding profiles with different rights
        $DB->insert('glpi_profilerights', [
            'name' => 'test_giveright_1',
            'profiles_id' => $profiles_id,
            'rights' => 0
        ]);
        $DB->insert('glpi_profilerights', [
            'name' => 'test_giveright_2',
            'profiles_id' => $profiles_id,
            'rights' => READ | UPDATE
        ]);
        $DB->insert('glpi_profilerights', [
            'name' => 'test_giveright_3',
            'profiles_id' => $profiles_id,
            'rights' => 0
        ]);

        // Adding a READ right with default required rights
        $this->output(
            function () {
                $this->migration->giveRight('test_giveright_1', READ);
            }
        )->isEqualTo('Rights have been given for test_giveright_1, you should review ACLs after update');
        $rights = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE' => [
                'name' => 'test_giveright_1',
            ]
        ]);
        $this->integer(count($rights))->isEqualTo(1);
        $rights = $rights->current();
        $this->integer($rights['rights'])->isEqualTo(READ);

        // Adding an UPDATE right with default required rights
        $this->output(
            function () {
                $this->migration->giveRight('test_giveright_1', UPDATE);
            }
        )->isEqualTo('Rights have been given for test_giveright_1, you should review ACLs after update');
        $rights = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE' => [
                'name' => 'test_giveright_1',
            ]
        ]);
        $this->integer(count($rights))->isEqualTo(1);
        $rights = $rights->current();
        $this->integer($rights['rights'])->isEqualTo(READ + UPDATE);

        // Adding a READ right for second time
        // Should not change the rights because it's already set
        $this->migration->giveRight('test_giveright_1', READ);
        $rights = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE' => [
                'name' => 'test_giveright_1',
            ]
        ]);
        $this->integer(count($rights))->isEqualTo(1);
        $rights = $rights->current();
        $this->integer($rights['rights'])->isEqualTo(READ + UPDATE);

        // Adding a right with specific required rights
        $this->output(
            function () {
                $this->migration->giveRight('test_giveright_2', CREATE, ['test_giveright_2' => READ | UPDATE]);
            }
        )->isEqualTo('Rights have been given for test_giveright_2, you should review ACLs after update');
        $rights = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE' => [
                'name' => 'test_giveright_2',
            ]
        ]);
        $this->integer(count($rights))->isEqualTo(1);
        $rights = $rights->current();
        $this->integer($rights['rights'])->isEqualTo(READ | UPDATE | CREATE);

        // Trying to add a right with specific required rights that are not met
        $this->migration->giveRight('test_giveright_3', CREATE, ['test_giveright_3' => READ | UPDATE]);
        $rights = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE' => [
                'name' => 'test_giveright_3',
            ]
        ]);
        $this->integer(count($rights))->isEqualTo(1);
        $rights = $rights->current();
        $this->integer($rights['rights'])->isEqualTo(0);

        // Clean profilrights
        $DB->delete('glpi_profilerights', [
            'name' => [
                'test_giveright_1',
                'test_giveright_2',
                'test_giveright_3'
            ]
        ]);
    }

    public function testRemoveConfig()
    {
        global $DB;

        $this->variable($DB->insert('glpi_configs', [
            'name' => __FUNCTION__,
            'value' => 'test',
            'context' => 'test'
        ]))->isNotEqualTo(false);

        $this->migration->removeConfig([__FUNCTION__]);
        // Shouldn't be deleted. Wrong context
        $this->integer(count($DB->request([
            'FROM' => 'glpi_configs',
            'WHERE' => [
                'name' => __FUNCTION__
            ]
        ])))->isEqualTo(1);

        $this->migration->removeConfig([__FUNCTION__], 'test');
        // Should be deleted
        $this->integer(count($DB->request([
            'FROM' => 'glpi_configs',
            'WHERE' => [
                'name' => __FUNCTION__
            ]
        ])))->isEqualTo(0);
    }

    public function reloadCurrentProfileDataProvider()
    {
        $data = [
            [
                'fn' => function () {
                    $this->migration->addRight('testReloadCurrentProfile', READ);
                },
                'expected' => 'New rights has been added for testReloadCurrentProfile, you should review ACLs after update'
            ],
            [
                'fn' => function () {
                    $this->migration->addRightByInterface('testReloadCurrentProfile', READ, ['interface' => 'central']);
                },
                'expected' => 'Rights has been updated for testReloadCurrentProfile, you should review ACLs after update'
            ],
            [
                'fn' => function () {
                    $this->migration->replaceRight('testReloadCurrentProfile', READ);
                },
                'expected' => 'Rights has been updated for testReloadCurrentProfile, you should review ACLs after update'
            ],
        ];

        return $data;
    }

    /**
     * @dataProvider reloadCurrentProfileDataProvider
     */
    public function testReloadCurrentProfile($fn, $expected)
    {
        global $DB;

        // Clean DB to handle potential failure of previous test
        $DB->delete('glpi_profilerights', [
            'name' => [
                'testReloadCurrentProfile'
            ]
        ]);

        $sub_query = new \Glpi\DBAL\QuerySubQuery([
            'SELECT' => [
                'profiles_id'
            ],
            'FROM' => 'glpi_profilerights',
            'WHERE' => [
                'name' => 'config',
                'rights' => READ | UPDATE
            ]
        ]);

        $DB->update('glpi_profiles', [
            'last_rights_update' => null
        ], [
            'id' => $sub_query
        ]);

        //Test adding a READ right when profile has READ and UPDATE config right (Default)
        $this->output($fn)->isEqualTo($expected);

        $last_rights_updates = $DB->request([
            'SELECT' => [
                'last_rights_update'
            ],
            'FROM' => 'glpi_profiles',
            'WHERE' => [
                'id' => $sub_query
            ]
        ]);

        foreach ($last_rights_updates as $last_rights_update) {
            $this->variable($last_rights_update['last_rights_update'])->isEqualTo($_SESSION['glpi_currenttime']);
        }

        // Clean DB after test
        $DB->delete('glpi_profilerights', [
            'name' => [
                'testReloadCurrentProfile'
            ]
        ]);
    }
}
