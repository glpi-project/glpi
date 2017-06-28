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

use \DbTestCase;

require_once __DIR__ . '/../DbFunction.php';

/* Test for inc/db.function.php */

class DbFunction extends DbTestCase {

   public function setUp() {
      global $CFG_GLPI;

      parent::setUp();
      // Clean the cache
      unset($CFG_GLPI['glpiitemtypetables']);
      unset($CFG_GLPI['glpitablesitemtype']);
   }

   public function dataTableKey() {

      return [['foo', ''],
                   ['glpi_computers', 'computers_id'],
                   ['glpi_users', 'users_id'],
                   ['glpi_plugin_foo_bars', 'plugin_foo_bars_id']];
   }

   public function dataTableForeignKey() {

      return [['glpi_computers', 'computers_id'],
                   ['glpi_users', 'users_id'],
                   ['glpi_plugin_foo_bars', 'plugin_foo_bars_id']];
   }

   /**
    * @dataProvider dataTableKey
   **/
   public function testGetForeignKeyFieldForTable($table, $key) {
      $this->string(getForeignKeyFieldForTable($table))->isIdenticalTo($key);
   }

   /**
    * @dataProvider dataTableForeignKey
   **/
   public function testIsForeignKeyFieldBase($table, $key) {
      $this->boolean(isForeignKeyField($key))->isTrue();
   }

   public function testIsForeignKeyFieldMore() {
      $this->boolean(isForeignKeyField('FakeId'))->isFalse();
      $this->boolean(isForeignKeyField('id_Another_Fake_Id'))->isFalse();
      $this->boolean(isForeignKeyField('users_id_tech'))->isTrue();
      $this->boolean(isForeignKeyField('_id'))->isFalse();
   }


   /**
    * @dataProvider dataTableForeignKey
   **/
   public function testGetTableNameForForeignKeyField($table, $key) {
      $this->string(getTableNameForForeignKeyField($key))->isIdenticalTo($table);
   }

   public function dataTableType() {
      // Pseudo plugin class for test
      require_once __DIR__ . '/../fixtures/pluginfoobar.php';
      require_once __DIR__ . '/../fixtures/pluginbarfoo.php';

      return [['glpi_computers', 'Computer', true],
                   ['glpi_events', 'Glpi\\Event', true],
                   ['glpi_users', 'User', true],
                   ['glpi_plugin_bar_foos', 'GlpiPlugin\\Bar\\Foo', true],
                   ['glpi_plugin_baz_foos', 'GlpiPlugin\\Baz\\Foo', false],
                   ['glpi_plugin_foo_bars', 'PluginFooBar', true],
                   ['glpi_plugin_foo_bazs', 'PluginFooBaz', false]];
   }

   /**
    * @dataProvider dataTableType
   **/
   public function testGetTableForItemType($table, $type, $classexists) {
      $this->string(getTableForItemType($type))->isIdenticalTo($table);
   }

   /**
    * @dataProvider dataTableType
   **/
   public function testGetItemTypeForTable($table, $type, $classexists) {
      if ($classexists) {
         $this->string(getItemTypeForTable($table))->isIdenticalTo($type);
      } else {
         $this->string(getItemTypeForTable($table))->isIdenticalTo('UNKNOWN');
      }
   }

   /**
    * @dataProvider dataTableType
   **/
   public function testGetItemForItemtype($table, $itemtype, $classexists) {
      if ($classexists) {
         $this->object(getItemForItemtype($itemtype))
            ->isInstanceOf($itemtype);
      } else {
         $this->boolean(getItemForItemtype($itemtype))->isFalse();
      }
   }

   public function dataPlural() {

      return [['model', 'models'],
                   ['address', 'addresses'],
                   ['computer', 'computers'],
                   ['thing', 'things'],
                   ['criteria', 'criterias'],
                   ['version', 'versions'],
                   ['config', 'configs'],
                   ['machine', 'machines'],
                   ['memory', 'memories'],
                   ['licence', 'licences']];
   }

    /**
    * @dataProvider dataPlural
   **/
   public function testGetPlural($singular, $plural) {

      $this->string(getPlural($singular))->isIdenticalTo($plural);
      $this->string(getPlural(getPlural($singular)))->isIdenticalTo($plural);
   }

   /**
    * @dataProvider dataPlural
   **/
   public function testGetSingular($singular, $plural) {
      $this->string(getSingular($plural))->isIdenticalTo($singular);
      $this->string(getSingular(getSingular($plural)))->isIdenticalTo($singular);
   }


   public function testCountElementsInTable() {
      global $DB;

      //the case of using an element that is not a table is not handle in the function :
      //testCountElementsInTable($table, $condition="")
      $this->integer((int)countElementsInTable('glpi_configs'))->isGreaterThan(100);
      $this->integer((int)countElementsInTable(['glpi_configs', 'glpi_users']))->isGreaterThan(100);
      $this->integer((int)countElementsInTable('glpi_configs', "context = 'core'"))->isGreaterThan(100);
      $this->integer(
         (int)countElementsInTable(
            'glpi_configs', "context = 'core' AND `name` = 'version'"
         )
      )->isIdenticalTo(1);
      $this->integer((int)countElementsInTable('glpi_configs', "context = 'fakecontext'"))->isIdenticalTo(0);
      // Using iterator
      $this->integer((int)countElementsInTable('glpi_configs', ['context' => 'core', 'name' => 'version']))->isIdenticalTo(1);
      $this->integer((int)countElementsInTable('glpi_configs', ['context' => 'core']))->isGreaterThan(100);
      $this->integer((int)countElementsInTable('glpi_configs', ['context' => 'fakecontext']))->isIdenticalTo(0);
   }


   public function testCountDistinctElementsInTable() {
      global $DB;

      //the case of using an element that is not a table is not handle in the function :
      //testCountElementsInTable($table, $condition="")
      $this->integer((int)countDistinctElementsInTable('glpi_configs', 'id'))->isGreaterThan(0);
      $this->integer((int)countDistinctElementsInTable('glpi_configs', 'context'))->isGreaterThan(0);
      $this->integer(
         (int)countDistinctElementsInTable(
            'glpi_configs',
            'context',
            "name = 'version'"
         )
      )->isIdenticalTo(1);
      $this->integer(
         (int)countDistinctElementsInTable(
            'glpi_configs',
            'id',
            "context ='fakecontext'"
         )
      )->isIdenticalTo(0);
   }

   public function testCountElementsInTableForMyEntities() {

      $this->Login();

      $this->setEntity('_test_root_entity', true);
      $this->integer(
         (int)countElementsInTableForMyEntities('glpi_computers')
      )->isIdenticalTo(6);
      $this->integer(
         (int)countElementsInTableForMyEntities('glpi_computers', 'name="_test_pc11"')
      )->isIdenticalTo(1); // SQL restrict
      $this->integer(
         (int)countElementsInTableForMyEntities('glpi_computers', ['name' => '_test_pc11'])
      )->isIdenticalTo(1); // Criteria
      $this->integer(
         (int)countElementsInTableForMyEntities('glpi_computers', 'name="_test_pc01"')
      )->isIdenticalTo(1);
      $this->integer(
         (int)countElementsInTableForMyEntities('glpi_computers', ['name' => '_test_pc01'])
      )->isIdenticalTo(1);

      $this->setEntity('_test_root_entity', false);
      $this->integer(
         (int)countElementsInTableForMyEntities('glpi_computers')
      )->isIdenticalTo(2);
      $this->integer(
         (int)countElementsInTableForMyEntities('glpi_computers', 'name="_test_pc11"')
      )->isIdenticalTo(0);
      $this->integer(
         (int)countElementsInTableForMyEntities('glpi_computers', ['name' => '_test_pc11'])
      )->isIdenticalTo(0);
      $this->integer(
         (int)countElementsInTableForMyEntities('glpi_computers', 'name="_test_pc01"')
      )->isIdenticalTo(1);
      $this->integer(
         (int)countElementsInTableForMyEntities('glpi_computers', ['name' => '_test_pc01'])
      )->isIdenticalTo(1);

      $this->setEntity('_test_child_1', false);
      $this->integer(
         (int)countElementsInTableForMyEntities('glpi_computers')
      )->isIdenticalTo(2);
      $this->integer(
         (int)countElementsInTableForMyEntities('glpi_computers', 'name="_test_pc11"')
      )->isIdenticalTo(1);
      $this->integer(
         (int)countElementsInTableForMyEntities('glpi_computers', ['name' => '_test_pc11'])
      )->isIdenticalTo(1);
      $this->integer(
         (int)countElementsInTableForMyEntities('glpi_computers', 'name="_test_pc01"')
      )->isIdenticalTo(0);
      $this->integer(
         (int)countElementsInTableForMyEntities('glpi_computers', ['name' => '_test_pc01'])
      )->isIdenticalTo(0);
   }

   public function testCountElementsInTableForEntity() {
      $e = getItemByTypeName('Entity', '_test_child_1', true);
      $this->integer(
         (int)countElementsInTableForEntity('glpi_computers', $e)
      )->isIdenticalTo(2);
      $this->integer(
         (int)countElementsInTableForEntity('glpi_computers', $e, 'name="_test_pc11"')
      )->isIdenticalTo(1);
      $this->integer(
         (int)countElementsInTableForEntity('glpi_computers', $e, 'name="_test_pc01"')
      )->isIdenticalTo(0);

      $e = getItemByTypeName('Entity', '_test_root_entity', true);
      $this->integer(
         (int)countElementsInTableForEntity('glpi_computers', $e, 'name="_test_pc01"')
      )->isIdenticalTo(1);
   }

   public function testGetAllDatasFromTable() {
      $data = getAllDatasFromTable('glpi_configs');
      $this->array($data)
         ->size->isGreaterThan(100);
      foreach ($data as $key => $array) {
         $this->array($array)
            ->variable['id']->isEqualTo($key);
      }

      $data = getAllDatasFromTable('glpi_configs', "context = 'core' AND `name` = 'version'");
      $this->array($data)->hasSize(1);

      $data = getAllDatasFromTable('glpi_configs', "", false, 'name');
      $this->array($data)->isNotEmpty();
      $previousArrayName = "";
      foreach ($data as $key => $array) {
         $this->boolean($previousArrayName <= $previousArrayName = $array['name'])->isTrue();
      }
   }

   public function testTableExist() {
      $this->boolean(tableExists('glpi_configs'))->isTrue();
      $this->boolean(tableExists('fakeTable'))->isFalse();
   }

   public function testFieldExist() {
      $this->boolean(fieldExists('glpi_configs', 'id'))->isTrue();
      $this->boolean(fieldExists('glpi_configs', 'fakeField'))->isFalse();
      $this->when(
         function () {
            $this->boolean(fieldExists('fakeTable', 'id'))->isFalse();
         }
      )->error
         ->withType(E_USER_WARNING)
         ->exists();

      $this->when(
         function () {
            $this->boolean(fieldExists('fakeTable', 'fakeField'))->isFalse();
         }
      )->error
         ->withType(E_USER_WARNING)
         ->exists();
   }


   public function testIsIndex() {
      $this->boolean(isIndex('glpi_configs', 'fakeField'))->isFalse();
      $this->boolean(isIndex('glpi_configs', 'name'))->isFalse();
      $this->boolean(isIndex('glpi_users', 'locations_id'))->isTrue();
      $this->boolean(isIndex('glpi_users', 'unicity'))->isTrue();

      $this->when(
         function () {
            $this->boolean(isIndex('fakeTable', 'id'))->isFalse();
         }
      )->error
         ->withType(E_USER_WARNING)
         ->exists();

   }

   public function testFormatOutputWebLink() {

      $this->string(formatOutputWebLink('www.glpi-project.org/'))
         ->isIdenticalTo('http://www.glpi-project.org/');
      $this->string(formatOutputWebLink('http://www.glpi-project.org/'))
         ->isIdenticalTo('http://www.glpi-project.org/');
   }

   public function testGetEntityRestrict() {
      $this->Login();

      // See all, really all
      $_SESSION['glpishowallentities'] = 1; // will be restored by setEntity call
      $this->string(getEntitiesRestrictRequest('AND', 'glpi_computers'))->isEmpty();
      $it = new \DBmysqlIterator(null, 'glpi_computers', getEntitiesRestrictCriteria('glpi_computers'));
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `glpi_computers`');

      // See all
      $this->setEntity('_test_root_entity', true);
      $this->string(getEntitiesRestrictRequest('WHERE', 'glpi_computers'))
         ->isIdenticalTo("WHERE ( `glpi_computers`.`entities_id` IN ('1', '2', '3')  ) ");
      $it = new \DBmysqlIterator(null, 'glpi_computers', getEntitiesRestrictCriteria('glpi_computers'));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE `glpi_computers`.`entities_id` IN (1, 2, 3)');

      // Root entity
      $this->setEntity('_test_root_entity', false);
      $this->string(getEntitiesRestrictRequest('WHERE', 'glpi_computers'))
         ->isIdenticalTo("WHERE ( `glpi_computers`.`entities_id` IN ('1')  ) ");
      $it = new \DBmysqlIterator(null, 'glpi_computers', getEntitiesRestrictCriteria('glpi_computers'));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE `glpi_computers`.`entities_id` IN (1)');

      // Child
      $this->setEntity('_test_child_1', false);
      $this->string(getEntitiesRestrictRequest('WHERE', 'glpi_computers'))
         ->isIdenticalTo("WHERE ( `glpi_computers`.`entities_id` IN ('2')  ) ");
      $it = new \DBmysqlIterator(null, 'glpi_computers', getEntitiesRestrictCriteria('glpi_computers'));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE `glpi_computers`.`entities_id` IN (2)');

      // Child without table
      $this->string(getEntitiesRestrictRequest('WHERE'))
         ->isIdenticalTo("WHERE ( `entities_id` IN ('2')  ) ");
      $it = new \DBmysqlIterator(null, 'glpi_computers', getEntitiesRestrictCriteria());
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE `entities_id` IN (2)');

      // Child + parent
      $this->setEntity('_test_child_2', false);
      $this->string(getEntitiesRestrictRequest('WHERE', 'glpi_computers', '', '', true))
         ->isIdenticalTo("WHERE ( `glpi_computers`.`entities_id` IN ('3')  OR (`glpi_computers`.`is_recursive`='1' AND `glpi_computers`.`entities_id` IN ('0','1')) ) ");
      $it = new \DBmysqlIterator(null, 'glpi_computers', getEntitiesRestrictCriteria('glpi_computers', '', '', true));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE (`glpi_computers`.`entities_id` IN (3) OR (`glpi_computers`.`is_recursive` = 1 AND `glpi_computers`.`entities_id` IN (0, 1)))');

      //Child + parent on glpi_entities
      $it = new \DBmysqlIterator(null, 'glpi_entities', getEntitiesRestrictCriteria('glpi_entities', '', '', true));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_entities` WHERE (`glpi_entities`.`id` IN (3, 0, 1))');

      //Child + parent -- automatic recusrivity detection
      $it = new \DBmysqlIterator(null, 'glpi_computers', getEntitiesRestrictCriteria('glpi_computers', '', '', 'auto'));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE (`glpi_computers`.`entities_id` IN (3) OR (`glpi_computers`.`is_recursive` = 1 AND `glpi_computers`.`entities_id` IN (0, 1)))');

      // Child + parent without table
      $this->string(getEntitiesRestrictRequest('WHERE', '', '', '', true))
         ->isIdenticalTo("WHERE ( `entities_id` IN ('3')  OR (`is_recursive`='1' AND `entities_id` IN ('0','1')) ) ");
      $it = new \DBmysqlIterator(null, 'glpi_computers', getEntitiesRestrictCriteria('', '', '', true));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE (`entities_id` IN (3) OR (`is_recursive` = 1 AND `entities_id` IN (0, 1)))');

      $it = new \DBmysqlIterator(null, 'glpi_entities', getEntitiesRestrictCriteria('glpi_entities', '', 3, true));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_entities` WHERE (`glpi_entities`.`id` IN (3, 0, 1))');

      $it = new \DBmysqlIterator(null, 'glpi_entities', getEntitiesRestrictCriteria('glpi_entities', '', 7, true));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_entities` WHERE `glpi_entities`.`id` = 7');
   }
}
