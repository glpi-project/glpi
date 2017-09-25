<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

use \atoum;

/* Test for inc/migration.class.php */
/**
 * @engine inline
 */
class Migration extends atoum {

   private $db;
   private $migration;
   private $queries;

   public function beforeTestMethod($method) {
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
      )->isIdenticalTo("<div id='migration_message_9.2'>\n            <p class='center'>Work in progress...</p></div>");
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
         1 => 'SELECT `glpi_configs`.*
                FROM `glpi_configs`
                WHERE `context` = \'core\'
                                              AND `name` = \'one\'',
         2 => 'INSERT
                   INTO `glpi_configs` (`context`,`name`,`value`) VALUES (\'core\',\'one\',\'key\')',

         3 => 'SELECT `glpi_configs`.*
                FROM `glpi_configs`
                WHERE `context` = \'core\'
                                              AND `name` = \'two\'',

         4 => 'INSERT
                   INTO `glpi_configs` (`context`,`name`,`value`) VALUES (\'core\',\'two\',\'value\')',

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
         1 => 'SELECT `glpi_configs`.*
                FROM `glpi_configs`
                WHERE `context` = \'test-context\'
                                              AND `name` = \'one\'',
         2 => 'INSERT
                   INTO `glpi_configs` (`context`,`name`,`value`) VALUES (\'test-context\',\'one\',\'key\')',

         3 => 'SELECT `glpi_configs`.*
                FROM `glpi_configs`
                WHERE `context` = \'test-context\'
                                              AND `name` = \'two\'',

         4 => 'INSERT
                   INTO `glpi_configs` (`context`,`name`,`value`) VALUES (\'test-context\',\'two\',\'value\')',

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
      $this->calling($this->db)->request = $dbresult;

      $this->calling($this->db)->numrows = 0;
      $this->calling($this->db)->fetch_assoc = [];
      $DB = $this->db;

      $this->output(
         function () {
            $this->migration->executeMigration();
         }
      )->isIdenticalTo('Configuration values added for two.Task completed.');

      $this->array($this->queries)->isIdenticalTo([
         0 => 'SELECT `glpi_configs`.*
                FROM `glpi_configs`
                WHERE `context` = \'core\'
                                              AND `name` = \'two\'',
         1 => 'INSERT
                   INTO `glpi_configs` (`context`,`name`,`value`) VALUES (\'core\',\'two\',\'value\')'
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
         0 => 'SELECT TABLE_NAME FROM information_schema.`TABLES`
             WHERE TABLE_SCHEMA = \'' . $DB->dbdefault . '\'
                AND TABLE_TYPE = \'BASE TABLE\'
                AND TABLE_NAME LIKE \'%table1%\'',
         1 => 'SELECT TABLE_NAME FROM information_schema.`TABLES`
             WHERE TABLE_SCHEMA = \'' . $DB->dbdefault  . '\'
                AND TABLE_TYPE = \'BASE TABLE\'
                AND TABLE_NAME LIKE \'%table2%\''
             ]);

      //try to backup existant tables
      $this->queries = [];
      $this->calling($this->db)->tableExists = true;
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
}
