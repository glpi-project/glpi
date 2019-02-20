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

/* Test for inc/migration.class.php */
/**
 * @engine inline
 */
class Migration extends \GLPITestCase {

   private $db;
   private $migration;
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
         $this->calling($this->db)->free_result = true;

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
      $this->calling($this->db)->fetch_assoc = [];
      $this->calling($this->db)->data_seek = true;
      $this->calling($this->db)->list_fields = [
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
      )->isIdenticalTo('Configuration values added for one, two.Task completed.');

      $this->array($this->queries)->isIdenticalTo([
         0 => 'SELECT * FROM `glpi_configs` WHERE `context` = \'core\' AND `name` IN (\'one\', \'two\')',
         1 => 'SELECT `id` FROM `glpi_configs` WHERE `context` = \'core\' AND `name` = \'one\'',
         2 => 'INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES (\'core\', \'one\', \'key\')',
         3 => 'SELECT `id` FROM `glpi_configs` WHERE `context` = \'core\' AND `name` = \'two\'',
         4 => 'INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES (\'core\', \'two\', \'value\')'
      ]);

      //test with context set => new keys should be inserted in correct context
      $this->queries = [];
      $this->migration->setContext('test-context');

      $this->output(
         function () {
            $this->migration->executeMigration();
         }
      )->isIdenticalTo('Configuration values added for one, two.Task completed.');

      $this->array($this->queries)->isIdenticalTo([
         0 => 'SELECT * FROM `glpi_configs` WHERE `context` = \'test-context\' AND `name` IN (\'one\', \'two\')',
         1 => 'SELECT `id` FROM `glpi_configs` WHERE `context` = \'test-context\' AND `name` = \'one\'',
         2 => 'INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES (\'test-context\', \'one\', \'key\')',
         3 => 'SELECT `id` FROM `glpi_configs` WHERE `context` = \'test-context\' AND `name` = \'two\'',
         4 => 'INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES (\'test-context\', \'two\', \'value\')'
      ]);

      $this->migration->setContext('core'); //reset

      //test with one existing value => only new key should be inserted
      $this->queries = [];
      $dbresult = [[
         'id'        => '42',
         'context'   => 'core',
         'name'      => 'one',
         'value'     => 'setted value'
      ]];
      $it = new \ArrayIterator($dbresult);
      $this->calling($this->db)->request = $it;

      $DB = $this->db;

      $this->output(
         function () {
            $this->migration->executeMigration();
         }
      )->isIdenticalTo('Configuration values added for two.Task completed.');

      $this->array($this->queries)->isIdenticalTo([
         0 => 'INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES (\'core\', \'two\', \'value\')'
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
         0 => 'SELECT `TABLE_NAME` FROM `information_schema`.`TABLES`' .
               ' WHERE `TABLE_SCHEMA` = \'' . $DB->dbdefault .
               '\' AND `TABLE_TYPE` = \'BASE TABLE\' AND `TABLE_NAME` LIKE \'%table1%\'',
         1 => 'SELECT `TABLE_NAME` FROM `information_schema`.`TABLES`' .
               ' WHERE `TABLE_SCHEMA` = \'' . $DB->dbdefault  .
               '\' AND `TABLE_TYPE` = \'BASE TABLE\' AND `TABLE_NAME` LIKE \'%table2%\''
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
      $this->calling($this->db)->fieldExists = true;

      $this->output(
         function () {
            $this->migration->changeField('change_table', 'ID', 'id', 'integer');
            $this->migration->executeMigration();
         }
      )->isIdenticalTo("Change of the database layout - change_tableTask completed.");

      $this->array($this->queries)->isIdenticalTo([
         "ALTER TABLE `change_table` DROP `id`  ,\n" .
         "CHANGE `ID` `id` INT(11) NOT NULL DEFAULT '0'  ",
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
            'sql'       => "ALTER TABLE `my_table` ADD `my_field` DATETIME DEFAULT NULL   "
         ], [
            'table'     => 'my_table',
            'field'     => 'my_field',
            'format'    => 'datetime',
            'options'   => ['value' => '2018-06-04 08:16:38'],
            'sql'       => "ALTER TABLE `my_table` ADD `my_field` DATETIME DEFAULT '2018-06-04 08:16:38'   "
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

      $this->array($this->queries)->isIdenticalTo([$sql]);
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
}
