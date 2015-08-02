<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2015 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/* Test for inc/db.function.php */

class DbFunctionTest extends PHPUnit_Framework_TestCase {

   protected function setUp() {
      // Clean the cache
      unset($CFG_GLPI['glpiitemtypetables']);
      unset($CFG_GLPI['glpitablesitemtype']);

      // Pseudo plugin class for test
      require_once 'fixtures/pluginfoobar.php';
   }

   public function dataTableKey() {
      return array(
         array('foo', ''),
         array('glpi_computers', 'computers_id'),
         array('glpi_users', 'users_id'),
         array('glpi_plugin_foo_bars', 'plugin_foo_bars_id'),
      );
   }

   /**
    * @covers getForeignKeyFieldForTable
    * @dataProvider dataTableKey
    */
   public function testGetForeignKeyFieldForTable($table, $key) {
      $this->assertEquals($key, getForeignKeyFieldForTable($table));
   }

   /**
    * @covers getForeignKeyFieldForTable
    * @dataProvider dataTableKey
    */
   public function testGgetTableNameForForeignKeyField($table, $key) {
      if ($key) {
         $this->assertEquals($table, getTableNameForForeignKeyField($key));
      }
   }

   public function dataTableType() {
      return array(
         array('glpi_computers', 'Computer', true),
         array('glpi_users', 'User', true),
         array('glpi_plugin_foo_bars', 'PluginFooBar', true),
         array('glpi_plugin_foo_bazs', 'PluginFooBaz', false),
      );
   }

   /**
    * @covers getTableForItemType
    * @dataProvider dataTableType
    */
   public function testGetTableForItemType($table, $type, $classexists) {
      $this->assertEquals($table, getTableForItemType($type));
   }

   /**
    * @covers getItemTypeForTable
    * @dataProvider dataTableType
    */
   public function testGetItemTypeForTable($table, $type, $classexists) {
      if ($classexists) {
         $this->assertEquals($type, getItemTypeForTable($table));
      } else {
         $this->assertEquals('UNKNOWN', getItemTypeForTable($table));
      }
   }
}
