<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

/* Test for inc/migration.class.php */
/**
 * @engine inline
 */
class Migration extends \GLPITestCase {

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

   public function beforeTestMethod($method) {
      parent::beforeTestMethod($method);
      if ($method !== 'testConstructor') {
         $this->db = new \mock\DB();
         $queries = [];
         $this->queries = &$queries;
         $this->calling($this->db)->query = function ($query) use (&$queries) {
            $queries[] = $query;
            return true;
         };
         $this->calling($this->db)->freeResult = true;

         $this->output(
            function () {
               $this->migration = new \mock\Migration(GLPI_VERSION);
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

   public function testConstructor() {
      $this->output(
         function () {
            new \Migration(GLPI_VERSION);
         }
      )->isEmpty();
   }

   public function testPrePostQueries() {
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

   public function testAddConfig() {
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

      //test with non existing value => new keys should be inserted
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
         0 => 'SELECT * FROM `glpi_configs` WHERE `context` = \'core\' AND `name` IN (\'one\', \'two\')',
         1 => 'SELECT `id` FROM `glpi_configs` WHERE `context` = \'core\' AND `name` = \'one\'',
         2 => 'INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES (\'core\', \'one\', \'key\')',
         3 => 'INSERT INTO `glpi_logs` (`items_id`, `itemtype`, `itemtype_link`, `linked_action`, `user_name`, `date_mod`, `id_search_option`, `old_value`, `new_value`) VALUES (\'1\', \'Config\', \'\', \'0\', \'\', \''.$_SESSION['glpi_currenttime'].'\', \'1\', \'one \', \'key\')',
         4 => 'SELECT `id` FROM `glpi_configs` WHERE `context` = \'core\' AND `name` = \'two\'',
         5 => 'INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES (\'core\', \'two\', \'value\')',
         6 => 'INSERT INTO `glpi_logs` (`items_id`, `itemtype`, `itemtype_link`, `linked_action`, `user_name`, `date_mod`, `id_search_option`, `old_value`, `new_value`) VALUES (\'1\', \'Config\', \'\', \'0\', \'\', \''.$_SESSION['glpi_currenttime'].'\', \'1\', \'two \', \'value\')',
      ];
      $this->array($this->queries)->isIdenticalTo($core_queries);

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
         0 => 'SELECT * FROM `glpi_configs` WHERE `context` = \'test-context\' AND `name` IN (\'one\', \'two\')',
         1 => 'SELECT `id` FROM `glpi_configs` WHERE `context` = \'test-context\' AND `name` = \'one\'',
         2 => 'INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES (\'test-context\', \'one\', \'key\')',
         3 => 'INSERT INTO `glpi_logs` (`items_id`, `itemtype`, `itemtype_link`, `linked_action`, `user_name`, `date_mod`, `id_search_option`, `old_value`, `new_value`) VALUES (\'1\', \'Config\', \'\', \'0\', \'\', \''.$_SESSION['glpi_currenttime'].'\', \'1\', \'one (test-context) \', \'key\')',
         4 => 'SELECT `id` FROM `glpi_configs` WHERE `context` = \'test-context\' AND `name` = \'two\'',
         5 => 'INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES (\'test-context\', \'two\', \'value\')',
         6 => 'INSERT INTO `glpi_logs` (`items_id`, `itemtype`, `itemtype_link`, `linked_action`, `user_name`, `date_mod`, `id_search_option`, `old_value`, `new_value`) VALUES (\'1\', \'Config\', \'\', \'0\', \'\', \''.$_SESSION['glpi_currenttime'].'\', \'1\', \'two (test-context) \', \'value\')',
      ]);

      //test with one existing value => only new key should be inserted
      $this->migration->addConfig([
         'one' => 'key',
         'two' => 'value'
      ]);
      $this->queries = [];
      $this->calling($this->db)->request = function ($table) {
         // Call using 'glpi_configs' value for first parameter
         // corresponds to the call made to retrieve exisintg values
         // -> returns a value for config 'one'
         if ('glpi_configs' === $table) {
            $dbresult = [[
               'id'        => '42',
               'context'   => 'core',
               'name'      => 'one',
               'value'     => 'setted value'
            ]];
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
         1 => 'INSERT INTO `glpi_logs` (`items_id`, `itemtype`, `itemtype_link`, `linked_action`, `user_name`, `date_mod`, `id_search_option`, `old_value`, `new_value`) VALUES (\'1\', \'Config\', \'\', \'0\', \'\', \''.$_SESSION['glpi_currenttime'].'\', \'1\', \'two \', \'value\')',
      ]);
   }

   public function testBackupTables() {
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
      /*)->isIdenticalTo("glpi_existingtest table already exists. " .
         "A backup have been done to backup_glpi_existingtest" .
         "You can delete backup tables if you have no need of them.Task completed.");*/

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

   public function testChangeField() {
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
         "ALTER TABLE `change_table` DROP `id`  ,\n" .
         "CHANGE `ID` `id` INT(11) NOT NULL DEFAULT '0'   FIRST  ",
      ]);

      // Test change field with move to after an other column
      $this->queries = [];
      $this->calling($this->db)->fieldExists = true;

      $this->output(
         function () {
            $this->migration->changeField('change_table', 'NAME', 'name', 'string', ['after' => 'id']);
            $this->migration->executeMigration();
         }
      )->isIdenticalTo("Change of the database layout - change_tableTask completed.");

      $this->array($this->queries)->isIdenticalTo([
         "ALTER TABLE `change_table` DROP `name`  ,\n" .
         "CHANGE `NAME` `name` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL   AFTER `id` ",
      ]);
   }

   protected function fieldsFormatsProvider() {
      return [
         [
            'table'     => 'my_table',
            'field'     => 'my_field',
            'format'    => 'bool',
            'options'   => [],
            'sql'       => "ALTER TABLE `my_table` ADD `my_field` TINYINT(1) NOT NULL DEFAULT '0'   "
         ], [
            'table'     => 'my_table',
            'field'     => 'my_field',
            'format'    => 'bool',
            'options'   => ['value' => 1],
            'sql'       => "ALTER TABLE `my_table` ADD `my_field` TINYINT(1) NOT NULL DEFAULT '1'   "
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
            'sql'       => "ALTER TABLE `my_table` ADD `my_field` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL   "
         ], [
            'table'     => 'my_table',
            'field'     => 'my_field',
            'format'    => 'string',
            'options'   => ['value' => 'a string'],
            'sql'       => "ALTER TABLE `my_table` ADD `my_field` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'a string'   "
         ], [
            'table'     => 'my_table',
            'field'     => 'my_field',
            'format'    => 'integer',
            'options'   => [],
            'sql'       => "ALTER TABLE `my_table` ADD `my_field` INT(11) NOT NULL DEFAULT '0'   "
         ], [
            'table'     => 'my_table',
            'field'     => 'my_field',
            'format'    => 'integer',
            'options'   => ['value' => 2],
            'sql'       => "ALTER TABLE `my_table` ADD `my_field` INT(11) NOT NULL DEFAULT '2'   "
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
            'sql'       => "ALTER TABLE `my_table` ADD `my_field` TEXT COLLATE utf8_unicode_ci DEFAULT NULL   "
         ], [
            'table'     => 'my_table',
            'field'     => 'my_field',
            'format'    => 'text',
            'options'   => ['value' => 'A text'],
            'sql'       => "ALTER TABLE `my_table` ADD `my_field` TEXT COLLATE utf8_unicode_ci NOT NULL DEFAULT 'A text'   "
         ], [
            'table'     => 'my_table',
            'field'     => 'my_field',
            'format'    => 'longtext',
            'options'   => [],
            'sql'       => "ALTER TABLE `my_table` ADD `my_field` LONGTEXT COLLATE utf8_unicode_ci DEFAULT NULL   "
         ], [
            'table'     => 'my_table',
            'field'     => 'my_field',
            'format'    => 'longtext',
            'options'   => ['value' => 'A long text'],
            'sql'       => "ALTER TABLE `my_table` ADD `my_field` LONGTEXT COLLATE utf8_unicode_ci NOT NULL DEFAULT 'A long text'   "
         ], [
            'table'     => 'my_table',
            'field'     => 'my_field',
            'format'    => 'autoincrement',
            'options'   => [],
            'sql'       => "ALTER TABLE `my_table` ADD `my_field` INT(11) NOT NULL AUTO_INCREMENT   "
         ], [
            'table'     => 'my_table',
            'field'     => 'my_field',
            'format'    => "INT(3) NOT NULL DEFAULT '42'",
            'options'   => [],
            'sql'       => "ALTER TABLE `my_table` ADD `my_field` INT(3) NOT NULL DEFAULT '42'   "
         ], [
            'table'     => 'my_table',
            'field'     => 'my_field',
            'format'    => 'integer',
            'options'   => ['comment' => 'a comment'],
            'sql'       => "ALTER TABLE `my_table` ADD `my_field` INT(11) NOT NULL DEFAULT '0'  COMMENT 'a comment'  "
         ], [
            'table'     => 'my_table',
            'field'     => 'my_field',
            'format'    => 'integer',
            'options'   => ['after' => 'other_field'],
            'sql'       => "ALTER TABLE `my_table` ADD `my_field` INT(11) NOT NULL DEFAULT '0'   AFTER `other_field` "
         ], [
            'table'     => 'my_table',
            'field'     => 'my_field',
            'format'    => 'integer',
            'options'   => ['first' => true],
            'sql'       => "ALTER TABLE `my_table` ADD `my_field` INT(11) NOT NULL DEFAULT '0'   FIRST  "
         ], [
            'table'     => 'my_table',
            'field'     => 'my_field',
            'format'    => 'integer',
            'options'   => ['value' => '-2', 'update' => '0', 'condition' => 'WHERE `id` = 0'],
            'sql'       => [
               "ALTER TABLE `my_table` ADD `my_field` INT(11) NOT NULL DEFAULT '-2'   ",
               "UPDATE `my_table`
                        SET `my_field` = 0 WHERE `id` = 0",
            ]
         ]
      ];
   }

   /**
    * @dataProvider fieldsFormatsProvider
    */
   public function testAddField($table, $field, $format, $options, $sql) {
      global $DB;
      $DB = $this->db;
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

   public function testFormatBooleanBadDefault() {
      global $DB;
      $DB = $this->db;
      $this->calling($this->db)->fieldExists = false;
      $this->queries = [];

      $this->when(
         function () {
            $this->migration->addField('my_table', 'my_field', 'bool', ['value' => 2]);
            $this->migration->executeMigration();
         }
      )->error()
         ->withType(E_USER_ERROR)
         ->withMessage('default_value must be 0 or 1')
         ->exists();
   }

   public function testFormatIntegerBadDefault() {
      global $DB;
      $DB = $this->db;
      $this->calling($this->db)->fieldExists = false;
      $this->queries = [];

      $this->when(
         function () {
            $this->migration->addField('my_table', 'my_field', 'integer', ['value' => 'foo']);
            $this->migration->executeMigration();
         }
      )->error()
         ->withType(E_USER_ERROR)
         ->withMessage('default_value must be numeric')
         ->exists();
   }

   public function testAddRight() {
      global $DB;

      $DB->delete('glpi_profilerights', [
         'name' => [
            'testright1', 'testright2', 'testright3', 'testright4'
         ]
      ]);
      //Test adding a READ right when profile has READ and UPDATE config right (Default)
      $this->migration->addRight('testright1', READ);
      //Test adding a READ right when profile has UPDATE group right
      $this->migration->addRight('testright2', READ, ['group' => UPDATE]);
      //Test adding an UPDATE right when profile has READ and UPDATE group right and CREATE entity right
      $this->migration->addRight('testright3', UPDATE, [
         'group'  => READ | UPDATE,
         'entity' => CREATE
      ]);
      //Test adding a READ right when profile with no requirements
      $this->migration->addRight('testright4', READ, []);

      $right1 = $DB->request([
         'FROM' => 'glpi_profilerights',
         'WHERE'  => [
            'name'   => 'testright1',
            'rights' => READ
         ]
      ]);
      $this->integer(count($right1))->isEqualTo(1);

      $right1 = $DB->request([
         'FROM' => 'glpi_profilerights',
         'WHERE'  => [
            'name'   => 'testright2',
            'rights' => READ
         ]
      ]);
      $this->integer(count($right1))->isEqualTo(2);

      $right1 = $DB->request([
         'FROM' => 'glpi_profilerights',
         'WHERE'  => [
            'name'   => 'testright3',
            'rights' => UPDATE
         ]
      ]);
      $this->integer(count($right1))->isEqualTo(1);

      $right1 = $DB->request([
         'FROM' => 'glpi_profilerights',
         'WHERE'  => [
            'name'   => 'testright4',
            'rights' => READ
         ]
      ]);
      $this->integer(count($right1))->isEqualTo(8);

      //Test adding a READ right only on profiles where it has not been set yet
      $DB->delete('glpi_profilerights', [
         'profiles_id' => [1, 2, 3, 4],
         'name' => 'testright4'
      ]);

      $this->migration->addRight('testright4', READ | UPDATE, []);

      $right4 = $DB->request([
         'FROM' => 'glpi_profilerights',
         'WHERE'  => [
            'name'   => 'testright4',
            'rights' => READ | UPDATE
         ]
      ]);
      $this->integer(count($right4))->isEqualTo(4);
   }

   public function testRenameTable() {

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
      $this->calling($this->db)->query = function ($query) use (&$queries) {
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
      $this->migration->migrationOneTable('glpi_oldtable');
      $this->migration->renameTable('glpi_oldtable', 'glpi_newtable');

      $this->array($this->queries)->isIdenticalTo(
         [
            "ALTER TABLE `glpi_oldtable` ADD `bool_field` TINYINT(1) NOT NULL DEFAULT '0'   ",
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
      $this->migration->migrationOneTable('glpi_newtable');

      $this->array($this->queries)->isIdenticalTo(
         [
            "RENAME TABLE `glpi_oldtable` TO `glpi_newtable`",
            "ALTER TABLE `glpi_newtable` ADD `bool_field` TINYINT(1) NOT NULL DEFAULT '0'   ",
            "ALTER TABLE `glpi_newtable` ADD FULLTEXT `fulltext_key` (`fulltext_key`)",
            "ALTER TABLE `glpi_newtable` ADD UNIQUE `id` (`id`)",
         ]
      );
   }

   /**
    * Test Migration::renameItemtype().
    * Case: failure as source table does not exists.
    */
   public function testRenameItemtypeWhenSourceTableDoesNotExists() {
      global $DB;
      $DB = $this->db;

      $this->calling($this->db)->tableExists = false;

      $migration = $this->migration;
      $this->exception(
         function() use ($migration) {
            $migration->renameItemtype('SomeOldType', 'NewName');
         }
      )->isInstanceOf(\RuntimeException::class)
      ->message
      ->contains('Table "glpi_someoldtypes" does not exists.');
   }

   /**
    * Test Migration::renameItemtype().
    * Case: failure as destination table already exists.
    */
   public function testRenameItemtypeWhenDestinationTableAlreadyExists() {
      global $DB;
      $DB = $this->db;

      $this->calling($this->db)->tableExists = true;

      $this->exception(
         function() {
            $this->migration->renameItemtype('SomeOldType', 'NewName');
         }
      )->isInstanceOf(\RuntimeException::class)
      ->message
      ->contains('Table "glpi_someoldtypes" cannot be renamed as table "glpi_newnames" already exists.');
   }

   /**
    * Test Migration::renameItemtype().
    * Case: failure as foreign key field already in use somewhere.
    */
   public function testRenameItemtypeWhenDestinationFieldAlreadyExists() {
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

      $this->exception(
         function() {
            $this->migration->renameItemtype('SomeOldType', 'NewName');
         }
      )->isInstanceOf(\RuntimeException::class)
      ->message
      ->contains('Field "someoldtypes_id" cannot be renamed in table "glpi_item_with_fkey" as "newnames_id" is field already exists.');
   }

   /**
    * Test Migration::renameItemtype().
    * Case: success.
    */
   public function testRenameItemtype() {
      global $DB;
      $DB = $this->db;

      $this->calling($this->db)->tableExists = function ($table) {
         return $table === 'glpi_someoldtypes';
      };
      $this->calling($this->db)->fieldExists = function ($table, $field) {
         return preg_match('/^someoldtypes_id/', $field);
      };
      $this->calling($this->db)->request = function ($request) {
         if (isset($request['WHERE']['OR'][0])
             && $request['WHERE']['OR'][0] === ['column_name'  => 'someoldtypes_id']) {
            // Request used for foreign key fields
            return new \ArrayIterator([
               ['TABLE_NAME' => 'glpi_oneitem_with_fkey',     'COLUMN_NAME' => 'someoldtypes_id'],
               ['TABLE_NAME' => 'glpi_anotheritem_with_fkey', 'COLUMN_NAME' => 'someoldtypes_id'],
               ['TABLE_NAME' => 'glpi_anotheritem_with_fkey', 'COLUMN_NAME' => 'someoldtypes_id_tech'],
            ]);
         }
         if (isset($request['WHERE']['OR'][0])
             && $request['WHERE']['OR'][0] === ['column_name'  => 'itemtype']) {
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
               '============================ Rename "SomeOldType" itemtype to "NewName" ============================' . "\n",
               'Rename "glpi_someoldtypes" table to "glpi_newnames"',
               'Rename "someoldtypes_id" foreign keys to "newnames_id" in all tables',
               'Rename "SomeOldType" itemtype to "NewName" in all tables',
               'Change of the database layout - glpi_oneitem_with_fkey',
               'Change of the database layout - glpi_anotheritem_with_fkey',
               'Task completed.',
            ]
         )
      );

      $this->array($this->queries)->isIdenticalTo([
         "RENAME TABLE `glpi_someoldtypes` TO `glpi_newnames`",
         "ALTER TABLE `glpi_oneitem_with_fkey` CHANGE `someoldtypes_id` `newnames_id` INT(11) NOT NULL DEFAULT '0'   ",
         "ALTER TABLE `glpi_anotheritem_with_fkey` CHANGE `someoldtypes_id` `newnames_id` INT(11) NOT NULL DEFAULT '0'   ,\n"
         . "CHANGE `someoldtypes_id_tech` `newnames_id_tech` INT(11) NOT NULL DEFAULT '0'   ",
         "UPDATE `glpi_computers` SET `itemtype` = 'NewName' WHERE `itemtype` = 'SomeOldType'",
         "UPDATE `glpi_users` SET `itemtype` = 'NewName' WHERE `itemtype` = 'SomeOldType'",
         "UPDATE `glpi_stuffs` SET `itemtype_source` = 'NewName' WHERE `itemtype_source` = 'SomeOldType'",
         "UPDATE `glpi_stuffs` SET `itemtype_dest` = 'NewName' WHERE `itemtype_dest` = 'SomeOldType'",
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
               '============================ Rename "SomeOldType" itemtype to "NewName" ============================' . "\n",
               'Rename "SomeOldType" itemtype to "NewName" in all tables',
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
   }

   public function testChangeSearchOption() {
      global $DB;
      $DB = $this->db;

      $this->calling($this->db)->request = function ($request) {
         if (!isset($request['FROM'])) {
            return new \ArrayIterator([]);
         }
         if ($request['FROM'] === \DisplayPreference::getTable()) {
            return new \ArrayIterator([
               [
                  'id'        => 0,
                  'itemtype'  => 'Computer',
                  'num'       => 40,
               ],
               [
                  'id'        => 1,
                  'itemtype'  => 'Computer',
                  'num'       => 41,
               ],
               [
                  'id'        => 2,
                  'itemtype'  => 'Monitor',
                  'num'       => 40,
               ]
            ]);
         } else if ($request['FROM'] === \SavedSearch::getTable()) {
            return new \ArrayIterator([
               [
                  'id'        => 0,
                  'itemtype'  => 'Computer',
                  'query'     => 'is_deleted=0&as_map=0&criteria%5B0%5D%5Blink%5D=AND&criteria%5B0%5D%5Bfield%5D=40&criteria%5B0%5D%5Bsearchtype%5D=contains&criteria%5B0%5D%5Bvalue%5D=LT1&criteria%5B1%5D%5Blink%5D=AND&criteria%5B1%5D%5Bitemtype%5D=Budget&criteria%5B1%5D%5Bmeta%5D=1&criteria%5B1%5D%5Bfield%5D=4&criteria%5B1%5D%5Bsearchtype%5D=contains&criteria%5B1%5D%5Bvalue%5D=&search=Search&itemtype=Computer'
               ],
               [
                  'id'        => 1,
                  'itemtype'  => 'Budget',
                  'query'     => 'is_deleted=0&as_map=0&criteria%5B0%5D%5Blink%5D=AND&criteria%5B0%5D%5Bfield%5D=40&criteria%5B0%5D%5Bsearchtype%5D=contains&criteria%5B0%5D%5Bvalue%5D=LT1&criteria%5B1%5D%5Blink%5D=AND&criteria%5B1%5D%5Bitemtype%5D=Computer&criteria%5B1%5D%5Bmeta%5D=1&criteria%5B1%5D%5Bfield%5D=40&criteria%5B1%5D%5Bsearchtype%5D=contains&criteria%5B1%5D%5Bvalue%5D=&search=Search&itemtype=Computer'
               ],
               [
                  'id'        => 2,
                  'itemtype'  => 'Monitor',
                  'query'     => 'is_deleted=0&as_map=0&criteria%5B0%5D%5Blink%5D=AND&criteria%5B0%5D%5Bfield%5D=40&criteria%5B0%5D%5Bsearchtype%5D=contains&criteria%5B0%5D%5Bvalue%5D=LT1&criteria%5B1%5D%5Blink%5D=AND&criteria%5B1%5D%5Bitemtype%5D=Budget&criteria%5B1%5D%5Bmeta%5D=1&criteria%5B1%5D%5Bfield%5D=40&criteria%5B1%5D%5Bsearchtype%5D=contains&criteria%5B1%5D%5Bvalue%5D=&search=Search&itemtype=Monitor'
               ]
            ]);
         }
         return new \ArrayIterator([]);
      };

      $this->migration->changeSearchOption('Computer', 40, 100);
      $this->migration->executeMigration();

      $this->array($this->queries)->isIdenticalTo([
         "UPDATE `glpi_displaypreferences` SET `num` = '100' WHERE `itemtype` = 'Computer' AND `num` = '40'",
         "UPDATE `glpi_savedsearches` SET `query` = 'is_deleted=0&as_map=0&criteria%5B0%5D%5Blink%5D=AND&criteria%5B0%5D%5Bfield%5D=100&criteria%5B0%5D%5Bsearchtype%5D=contains&criteria%5B0%5D%5Bvalue%5D=LT1&criteria%5B1%5D%5Blink%5D=AND&criteria%5B1%5D%5Bitemtype%5D=Budget&criteria%5B1%5D%5Bmeta%5D=1&criteria%5B1%5D%5Bfield%5D=4&criteria%5B1%5D%5Bsearchtype%5D=contains&criteria%5B1%5D%5Bvalue%5D=&search=Search&itemtype=Computer' WHERE `id` = '0'",
         "UPDATE `glpi_savedsearches` SET `query` = 'is_deleted=0&as_map=0&criteria%5B0%5D%5Blink%5D=AND&criteria%5B0%5D%5Bfield%5D=40&criteria%5B0%5D%5Bsearchtype%5D=contains&criteria%5B0%5D%5Bvalue%5D=LT1&criteria%5B1%5D%5Blink%5D=AND&criteria%5B1%5D%5Bitemtype%5D=Computer&criteria%5B1%5D%5Bmeta%5D=1&criteria%5B1%5D%5Bfield%5D=100&criteria%5B1%5D%5Bsearchtype%5D=contains&criteria%5B1%5D%5Bvalue%5D=&search=Search&itemtype=Computer' WHERE `id` = '1'",
      ]);
   }
}
