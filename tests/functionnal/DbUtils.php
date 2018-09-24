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

use \DbTestCase;

/* Test for inc/dbutils.class.php */

class DbUtils extends DbTestCase {
   protected $cached_methods = [
      'testGetAncestorsOfCached',
      'testGetSonsOfCached'
   ];

   public function setUp() {
      global $CFG_GLPI;

      parent::setUp();
      // Clean the cache
      unset($CFG_GLPI['glpiitemtypetables']);
      unset($CFG_GLPI['glpitablesitemtype']);
   }

   protected function dataTableKey() {

      return [
         ['foo', ''],
         ['glpi_computers', 'computers_id'],
         ['glpi_users', 'users_id'],
         ['glpi_plugin_foo_bars', 'plugin_foo_bars_id']
      ];
   }

   protected function dataTableForeignKey() {

      return [
         ['glpi_computers', 'computers_id'],
         ['glpi_users', 'users_id'],
         ['glpi_plugin_foo_bars', 'plugin_foo_bars_id']
      ];
   }

   /**
    * @dataProvider dataTableKey
   **/
   public function testGetForeignKeyFieldForTable($table, $key) {
      $this
         ->if($this->newTestedInstance)
         ->then
            ->string($this->testedInstance->getForeignKeyFieldForTable($table))
            ->isIdenticalTo($key);

      //keep testing old method from db.function
      $this->string(getForeignKeyFieldForTable($table))->isIdenticalTo($key);
   }

   /**
    * @dataProvider dataTableForeignKey
   **/
   public function testIsForeignKeyFieldBase($table, $key) {
      $this
         ->if($this->newTestedInstance)
         ->then
            ->boolean($this->testedInstance->isForeignKeyField($key))->isTrue();

      //keep testing old method from db.function
      $this->boolean(isForeignKeyField($key))->isTrue();
   }

   public function testIsForeignKeyFieldMore() {
      $this
         ->if($this->newTestedInstance)
         ->then
            ->boolean($this->testedInstance->isForeignKeyField('FakeId'))->isFalse()
            ->boolean($this->testedInstance->isForeignKeyField('id_Another_Fake_Id'))->isFalse()
            ->boolean($this->testedInstance->isForeignKeyField('users_id_tech'))->isTrue()
            ->boolean($this->testedInstance->isForeignKeyField('_id'))->isFalse();

      //keep testing old method from db.function
      $this->boolean(isForeignKeyField('FakeId'))->isFalse();
      $this->boolean(isForeignKeyField('id_Another_Fake_Id'))->isFalse();
      $this->boolean(isForeignKeyField('users_id_tech'))->isTrue();
      $this->boolean(isForeignKeyField('_id'))->isFalse();
   }


   /**
    * @dataProvider dataTableForeignKey
   **/
   public function testGetTableNameForForeignKeyField($table, $key) {
      $this
         ->if($this->newTestedInstance)
         ->then
         ->string($this->testedInstance->getTableNameForForeignKeyField($key))->isIdenticalTo($table);

      //keep testing old method from db.function
      $this->string(getTableNameForForeignKeyField($key))->isIdenticalTo($table);
   }

   protected function dataTableType() {
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
      $this
         ->if($this->newTestedInstance)
         ->then
         ->string($this->testedInstance->getTableForItemType($type))->isIdenticalTo($table);

      //keep testing old method from db.function
      $this->string(getTableForItemType($type))->isIdenticalTo($table);
   }

   /**
    * @dataProvider dataTableType
   **/
   public function testGetItemTypeForTable($table, $type, $classexists) {
      if ($classexists) {
         $this
            ->if($this->newTestedInstance)
            ->then
               ->string($this->testedInstance->getItemTypeForTable($table))->isIdenticalTo($type);
      } else {
         $this
            ->if($this->newTestedInstance)
            ->then
               ->string($this->testedInstance->getItemTypeForTable($table))->isIdenticalTo('UNKNOWN');
      }

      //keep testing old method from db.function
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
         $this
            ->if($this->newTestedInstance)
            ->then
               ->object($this->testedInstance->getItemForItemtype($itemtype))->isInstanceOf($itemtype);
      } else {
         $this
            ->if($this->newTestedInstance)
            ->then
               ->boolean($this->testedInstance->getItemForItemtype($itemtype))->isFalse();
      }

      //keep testing old method from db.function
      if ($classexists) {
         $this->object(getItemForItemtype($itemtype))
            ->isInstanceOf($itemtype);
      } else {
         $this->boolean(getItemForItemtype($itemtype))->isFalse();
      }
   }

   public function dataPlural() {

      return [
         ['model', 'models'],
         ['address', 'addresses'],
         ['computer', 'computers'],
         ['thing', 'things'],
         ['criteria', 'criterias'],
         ['version', 'versions'],
         ['config', 'configs'],
         ['machine', 'machines'],
         ['memory', 'memories'],
         ['licence', 'licences'],
         ['pdu', 'pdus']
      ];
   }

   /**
    * @dataProvider dataPlural
    */
   public function testGetPlural($singular, $plural) {
      $this
         ->if($this->newTestedInstance)
         ->then
            ->string($this->testedInstance->getPlural($singular))->isIdenticalTo($plural)
            ->string(
               $this->testedInstance->getPlural(
                  $this->testedInstance->getPlural(
                     $singular
                  )
               )
            )->isIdenticalTo($plural);

      //keep testing old method from db.function
      $this->string(getPlural($singular))->isIdenticalTo($plural);
      $this->string(getPlural(getPlural($singular)))->isIdenticalTo($plural);
   }

   /**
    * @dataProvider dataPlural
   **/
   public function testGetSingular($singular, $plural) {
      $this
         ->if($this->newTestedInstance)
         ->then
            ->string($this->testedInstance->getSingular($plural))->isIdenticalTo($singular)
            ->string(
               $this->testedInstance->getSingular(
                  $this->testedInstance->getSingular(
                     $plural
                  )
               )
            )->isIdenticalTo($singular);

      //keep testing old method from db.function
      $this->string(getSingular($plural))->isIdenticalTo($singular);
      $this->string(getSingular(getSingular($plural)))->isIdenticalTo($singular);
   }


   public function testCountElementsInTable() {
      $this
         ->if($this->newTestedInstance)
         ->then
            ->integer($this->testedInstance->countElementsInTable('glpi_configs'))->isGreaterThan(100)
            ->integer($this->testedInstance->countElementsInTable(['glpi_configs', 'glpi_users']))->isGreaterThan(100)
            ->integer($this->testedInstance->countElementsInTable('glpi_configs', ['context' => 'core']))->isGreaterThan(100)
            ->integer($this->testedInstance->countElementsInTable('glpi_configs', ['context' => 'core', 'name' => 'version']))->isIdenticalTo(1)
            ->integer($this->testedInstance->countElementsInTable('glpi_configs', ['context' => 'fakecontext']))->isIdenticalTo(0);

      //keep testing old method from db.function
      //the case of using an element that is not a table is not handle in the function :
      $this->integer(countElementsInTable('glpi_configs'))->isGreaterThan(100);
      $this->integer(countElementsInTable(['glpi_configs', 'glpi_users']))->isGreaterThan(100);
      $this->integer(countElementsInTable('glpi_configs', ['context' => 'core', 'name' => 'version']))->isIdenticalTo(1);
      $this->integer(countElementsInTable('glpi_configs', ['context' => 'core']))->isGreaterThan(100);
      $this->integer(countElementsInTable('glpi_configs', ['context' => 'fakecontext']))->isIdenticalTo(0);
   }


   public function testCountDistinctElementsInTable() {
      $this
         ->if($this->newTestedInstance)
         ->then
            ->integer($this->testedInstance->countDistinctElementsInTable('glpi_configs', 'id'))->isGreaterThan(0)
            ->integer($this->testedInstance->countDistinctElementsInTable('glpi_configs', 'context'))->isGreaterThan(0)
            ->integer($this->testedInstance->countDistinctElementsInTable('glpi_tickets', 'entities_id'))->isIdenticalTo(2)
            ->integer($this->testedInstance->countDistinctElementsInTable('glpi_crontasks', 'itemtype', ['frequency' => '86400']))->isIdenticalTo(12)
            ->integer($this->testedInstance->countDistinctElementsInTable('glpi_crontasks', 'id', ['frequency' => '86400']))->isIdenticalTo(15)
            ->integer($this->testedInstance->countDistinctElementsInTable('glpi_configs', 'context', ['name' => 'version']))->isIdenticalTo(1)
            ->integer($this->testedInstance->countDistinctElementsInTable('glpi_configs', 'id', ['context' => 'fakecontext']))->isIdenticalTo(0);

      //keep testing old method from db.function
      //the case of using an element that is not a table is not handle in the function :
      //testCountElementsInTable($table, $condition="")
      $this->integer(countDistinctElementsInTable('glpi_configs', 'id'))->isGreaterThan(0);
      $this->integer(countDistinctElementsInTable('glpi_configs', 'context'))->isGreaterThan(0);
      $this->integer(
         countDistinctElementsInTable(
            'glpi_configs',
            'context',
            ['name' => 'version']
         )
      )->isIdenticalTo(1);
      $this->integer(
         countDistinctElementsInTable(
            'glpi_configs',
            'id',
            ['context' => 'fakecontext']
         )
      )->isIdenticalTo(0);
   }

   protected function dataCountMyEntities() {
      return [
         ['_test_root_entity', true, 'glpi_computers', [], 8],
         ['_test_root_entity', true, 'glpi_computers', ['name' => '_test_pc11'], 1],
         ['_test_root_entity', true, 'glpi_computers', ['name' => '_test_pc01'], 1],

         ['_test_root_entity', false, 'glpi_computers', [], 3],
         ['_test_root_entity', false, 'glpi_computers', ['name' => '_test_pc11'], 0],
         ['_test_root_entity', false, 'glpi_computers', ['name' => '_test_pc01'], 1],

         ['_test_child_1', false, 'glpi_computers', [], 3],
         ['_test_child_1', false, 'glpi_computers', ['name' => '_test_pc11'], 1],
         ['_test_child_1', false, 'glpi_computers', ['name' => '_test_pc01'], 0],
      ];
   }

   /**
    * @dataProvider dataCountMyEntities
    */
   public function testCountElementsInTableForMyEntities(
      $entity,
      $recursive,
      $table,
      $condition,
      $count
   ) {
      $this->login();
      $this->setEntity($entity, $recursive);

      $this
         ->if($this->newTestedInstance)
         ->then
            ->integer($this->testedInstance->countElementsInTableForMyEntities($table, $condition))->isIdenticalTo($count);

      //keep testing old method from db.function
      $this->integer(countElementsInTableForMyEntities($table, $condition))->isIdenticalTo($count);
   }

   protected function dataCountEntities() {
      return [
         ['_test_root_entity', 'glpi_computers', [], 3],
         ['_test_root_entity', 'glpi_computers', ['name' => '_test_pc11'], 0],
         ['_test_root_entity', 'glpi_computers', ['name' => '_test_pc01'], 1],

         ['_test_child_1', 'glpi_computers', [], 3],
         ['_test_child_1', 'glpi_computers', ['name' => '_test_pc11'], 1],
         ['_test_child_1', 'glpi_computers', ['name' => '_test_pc01'], 0],
      ];
   }


   /**
    * @dataProvider dataCountEntities
    */
   public function testCountElementsInTableForEntity(
      $entity,
      $table,
      $condition,
      $count
   ) {
      $eid = getItemByTypeName('Entity', $entity, true);

      $this
         ->if($this->newTestedInstance)
         ->then
            ->integer($this->testedInstance->countElementsInTableForEntity($table, $eid, $condition))->isIdenticalTo($count);

      //keep testing old method from db.function
      $this->integer(countElementsInTableForEntity($table, $eid, $condition))->isIdenticalTo($count);
   }

   public function testGetAllDatasFromTable() {
      $this
         ->if($this->newTestedInstance)
         ->then
            ->array($data = $this->testedInstance->getAllDataFromTable('glpi_configs'))
               ->size->isGreaterThan(100);

      foreach ($data as $key => $array) {
         $this->array($array)
            ->variable['id']->isEqualTo($key);
      }

      $this
         ->if($this->newTestedInstance)
         ->then
            ->array($this->testedInstance->getAllDataFromTable('glpi_configs', ['context' => 'core', 'name' => 'version']))->hasSize(1)
            ->array($data = $this->testedInstance->getAllDataFromTable('glpi_configs', [], false, 'name'))->isNotEmpty();
      $previousArrayName = "";
      foreach ($data as $key => $array) {
         $this->boolean($previousArrayName <= $previousArrayName = $array['name'])->isTrue();
      }

      //TODO: test with cache === true

      //keep testing old method from db.function
      $data = getAllDatasFromTable('glpi_configs');
      $this->array($data)
         ->size->isGreaterThan(100);
      foreach ($data as $key => $array) {
         $this->array($array)
            ->variable['id']->isEqualTo($key);
      }

      $data = getAllDatasFromTable('glpi_configs', ['context' => 'core', 'name' => 'version']);
      $this->array($data)->hasSize(1);

      $data = getAllDatasFromTable('glpi_configs', [], false, 'name');
      $this->array($data)->isNotEmpty();
      $previousArrayName = "";
      foreach ($data as $key => $array) {
         $this->boolean($previousArrayName <= $previousArrayName = $array['name'])->isTrue();
      }
   }


   public function testIsIndex() {
      $this
         ->if($this->newTestedInstance)
         ->then
            ->boolean($this->testedInstance->isIndex('glpi_configs', 'fakeField'))->isFalse()
            ->boolean($this->testedInstance->isIndex('glpi_configs', 'name'))->isFalse()
            ->boolean($this->testedInstance->isIndex('glpi_configs', 'value'))->isFalse()
            ->boolean($this->testedInstance->isIndex('glpi_users', 'locations_id'))->isTrue()
            ->boolean($this->testedInstance->isIndex('glpi_users', 'unicityloginauth'))->isTrue()
         ->when(
            function () {
               $this->boolean($this->testedInstance->isIndex('fakeTable', 'id'))->isFalse();
            }
         )->error
            ->withType(E_USER_WARNING)
            ->exists();

      //keep testing old method from db.function
      $this->boolean(isIndex('glpi_configs', 'fakeField'))->isFalse();
      $this->boolean(isIndex('glpi_configs', 'name'))->isFalse();
      $this->boolean(isIndex('glpi_users', 'locations_id'))->isTrue();
      $this->boolean(isIndex('glpi_users', 'unicityloginauth'))->isTrue();

      $this->when(
         function () {
            $this->boolean(isIndex('fakeTable', 'id'))->isFalse();
         }
      )->error
         ->withType(E_USER_WARNING)
         ->exists();

   }

   public function testGetEntityRestrict() {
      $this->login();
      $this->newTestedInstance();

      // See all, really all
      $_SESSION['glpishowallentities'] = 1; // will be restored by setEntity call

      $this->string($this->testedInstance->getEntitiesRestrictRequest('AND', 'glpi_computers'))->isEmpty();

      $it = new \DBmysqlIterator(null);

      $it->execute('glpi_computers', $this->testedInstance->getEntitiesRestrictCriteria('glpi_computers'));
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `glpi_computers`');

      //keep testing old method from db.function
      $this->string(getEntitiesRestrictRequest('AND', 'glpi_computers'))->isEmpty();
      $it->execute('glpi_computers', getEntitiesRestrictCriteria('glpi_computers'));
      $this->string($it->getSql())->isIdenticalTo('SELECT * FROM `glpi_computers`');

      // See all
      $this->setEntity('_test_root_entity', true);

      $this->string($this->testedInstance->getEntitiesRestrictRequest('WHERE', 'glpi_computers'))
         ->isIdenticalTo("WHERE ( `glpi_computers`.`entities_id` IN ('1', '2', '3')  ) ");
      $it->execute('glpi_computers', $this->testedInstance->getEntitiesRestrictCriteria('glpi_computers'));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE `glpi_computers`.`entities_id` IN (\'1\', \'2\', \'3\')');

      //keep testing old method from db.function
      $this->string(getEntitiesRestrictRequest('WHERE', 'glpi_computers'))
         ->isIdenticalTo("WHERE ( `glpi_computers`.`entities_id` IN ('1', '2', '3')  ) ");
      $it->execute('glpi_computers', getEntitiesRestrictCriteria('glpi_computers'));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE `glpi_computers`.`entities_id` IN (\'1\', \'2\', \'3\')');

      // Root entity
      $this->setEntity('_test_root_entity', false);

      $this->string($this->testedInstance->getEntitiesRestrictRequest('WHERE', 'glpi_computers'))
         ->isIdenticalTo("WHERE ( `glpi_computers`.`entities_id` IN ('1')  ) ");
      $it->execute('glpi_computers', $this->testedInstance->getEntitiesRestrictCriteria('glpi_computers'));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE `glpi_computers`.`entities_id` IN (\'1\')');

      //keep testing old method from db.function
      $this->string(getEntitiesRestrictRequest('WHERE', 'glpi_computers'))
         ->isIdenticalTo("WHERE ( `glpi_computers`.`entities_id` IN ('1')  ) ");
      $it->execute('glpi_computers', getEntitiesRestrictCriteria('glpi_computers'));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE `glpi_computers`.`entities_id` IN (\'1\')');

      // Child
      $this->setEntity('_test_child_1', false);

      $this->string($this->testedInstance->getEntitiesRestrictRequest('WHERE', 'glpi_computers'))
         ->isIdenticalTo("WHERE ( `glpi_computers`.`entities_id` IN ('2')  ) ");
      $it->execute('glpi_computers', $this->testedInstance->getEntitiesRestrictCriteria('glpi_computers'));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE `glpi_computers`.`entities_id` IN (\'2\')');

      //keep testing old method from db.function
      $this->string(getEntitiesRestrictRequest('WHERE', 'glpi_computers'))
         ->isIdenticalTo("WHERE ( `glpi_computers`.`entities_id` IN ('2')  ) ");
      $it->execute('glpi_computers', getEntitiesRestrictCriteria('glpi_computers'));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE `glpi_computers`.`entities_id` IN (\'2\')');

      // Child without table
      $this->string($this->testedInstance->getEntitiesRestrictRequest('WHERE'))
         ->isIdenticalTo("WHERE ( `entities_id` IN ('2')  ) ");
      $it->execute('glpi_computers', $this->testedInstance->getEntitiesRestrictCriteria());
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE `entities_id` IN (\'2\')');

      //keep testing old method from db.function
      $this->string(getEntitiesRestrictRequest('WHERE'))
         ->isIdenticalTo("WHERE ( `entities_id` IN ('2')  ) ");
      $it->execute('glpi_computers', getEntitiesRestrictCriteria());
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE `entities_id` IN (\'2\')');

      // Child + parent
      $this->setEntity('_test_child_2', false);

      $this->string($this->testedInstance->getEntitiesRestrictRequest('WHERE', 'glpi_computers', '', '', true))
         ->isIdenticalTo("WHERE ( `glpi_computers`.`entities_id` IN ('3')  OR (`glpi_computers`.`is_recursive`='1' AND `glpi_computers`.`entities_id` IN ('0','1')) ) ");
      $it->execute('glpi_computers', $this->testedInstance->getEntitiesRestrictCriteria('glpi_computers', '', '', true));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE (`glpi_computers`.`entities_id` IN (\'3\') OR (`glpi_computers`.`is_recursive` = \'1\' AND `glpi_computers`.`entities_id` IN (\'0\', \'1\')))');

      //keep testing old method from db.function
      $this->string(getEntitiesRestrictRequest('WHERE', 'glpi_computers', '', '', true))
         ->isIdenticalTo("WHERE ( `glpi_computers`.`entities_id` IN ('3')  OR (`glpi_computers`.`is_recursive`='1' AND `glpi_computers`.`entities_id` IN ('0','1')) ) ");
      $it->execute('glpi_computers', getEntitiesRestrictCriteria('glpi_computers', '', '', true));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE (`glpi_computers`.`entities_id` IN (\'3\') OR (`glpi_computers`.`is_recursive` = \'1\' AND `glpi_computers`.`entities_id` IN (\'0\', \'1\')))');

      //Child + parent on glpi_entities
      $it->execute('glpi_entities', $this->testedInstance->getEntitiesRestrictCriteria('glpi_entities', '', '', true));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_entities` WHERE (`glpi_entities`.`id` IN (\'3\', \'0\', \'1\'))');

      //keep testing old method from db.function
      $it->execute('glpi_entities', getEntitiesRestrictCriteria('glpi_entities', '', '', true));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_entities` WHERE (`glpi_entities`.`id` IN (\'3\', \'0\', \'1\'))');

      //Child + parent -- automatic recusrivity detection
      $it->execute('glpi_computers', $this->testedInstance->getEntitiesRestrictCriteria('glpi_computers', '', '', 'auto'));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE (`glpi_computers`.`entities_id` IN (\'3\') OR (`glpi_computers`.`is_recursive` = \'1\' AND `glpi_computers`.`entities_id` IN (\'0\', \'1\')))');

      //keep testing old method from db.function
      $it->execute('glpi_computers', getEntitiesRestrictCriteria('glpi_computers', '', '', 'auto'));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE (`glpi_computers`.`entities_id` IN (\'3\') OR (`glpi_computers`.`is_recursive` = \'1\' AND `glpi_computers`.`entities_id` IN (\'0\', \'1\')))');

      // Child + parent without table
      $this->string($this->testedInstance->getEntitiesRestrictRequest('WHERE', '', '', '', true))
         ->isIdenticalTo("WHERE ( `entities_id` IN ('3')  OR (`is_recursive`='1' AND `entities_id` IN ('0','1')) ) ");
      $it->execute('glpi_computers', $this->testedInstance->getEntitiesRestrictCriteria('', '', '', true));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE (`entities_id` IN (\'3\') OR (`is_recursive` = \'1\' AND `entities_id` IN (\'0\', \'1\')))');

      $it->execute('glpi_entities', $this->testedInstance->getEntitiesRestrictCriteria('glpi_entities', '', 3, true));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_entities` WHERE (`glpi_entities`.`id` IN (\'3\', \'0\', \'1\'))');

      $it->execute('glpi_entities', $this->testedInstance->getEntitiesRestrictCriteria('glpi_entities', '', 7, true));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_entities` WHERE `glpi_entities`.`id` = \'7\'');

      //keep testing old method from db.function
      $this->string(getEntitiesRestrictRequest('WHERE', '', '', '', true))
         ->isIdenticalTo("WHERE ( `entities_id` IN ('3')  OR (`is_recursive`='1' AND `entities_id` IN ('0','1')) ) ");
      $it->execute('glpi_computers', getEntitiesRestrictCriteria('', '', '', true));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE (`entities_id` IN (\'3\') OR (`is_recursive` = \'1\' AND `entities_id` IN (\'0\', \'1\')))');

      $it->execute('glpi_entities', getEntitiesRestrictCriteria('glpi_entities', '', 3, true));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_entities` WHERE (`glpi_entities`.`id` IN (\'3\', \'0\', \'1\'))');

      $it->execute('glpi_entities', getEntitiesRestrictCriteria('glpi_entities', '', 7, true));
      $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_entities` WHERE `glpi_entities`.`id` = \'7\'');
   }

   /**
    * Run getAncestorsOf tests
    *
    * @param boolean $cache Is cache enabled?
    * @param boolean $hit   Do we expect a cache hit? (ie. data already exists)
    *
    * @return void
    */
   private function runGetAncestorsOf($cache = false, $hit = false) {
      $ent0 = getItemByTypeName('Entity', '_test_root_entity', true);
      $ent1 = getItemByTypeName('Entity', '_test_child_1', true);
      $ent2 = getItemByTypeName('Entity', '_test_child_2', true);

      //Cache tests:
      //- if $cache === 0; we do not expect anything,
      //- if $cache === 1; we expect cache to be empty before call, and populated after
      //- if $hit   === 1; we expect cache to be populated

      $ckey = $this->nscache . ':glpi_entities_ancestors_cache_';

      //test on ent0
      $expected = [0 => '0'];
      if ($cache === true && $hit === false) {
         $this->boolean(apcu_exists($ckey . $ent0))->isFalse();
      } else if ($cache === true && $hit === true) {
         $this->array(apcu_fetch("$ckey$ent0"))->isIdenticalTo($expected);
      }

      $ancestors = getAncestorsOf('glpi_entities', $ent0);
      $this->array($ancestors)->isIdenticalTo($expected);

      if ($cache === true && $hit === false) {
         $this->array(apcu_fetch("$ckey$ent0"))->isIdenticalTo($expected);
      }

      //test on ent1
      $expected = [0 => '0', 1 => "$ent0"];
      if ($cache === true && $hit === false) {
         $this->boolean(apcu_exists($ckey . $ent1))->isFalse();
      } else if ($cache === true && $hit === true) {
         $this->array(apcu_fetch("$ckey$ent1"))->isIdenticalTo($expected);
      }

      $ancestors = getAncestorsOf('glpi_entities', $ent1);
      $this->array($ancestors)->isIdenticalTo($expected);

      if ($cache === true && $hit === false) {
         $this->array(apcu_fetch("$ckey$ent1"))->isIdenticalTo($expected);
      }

      //test on ent2
      $expected = [0 => '0', 1 => "$ent0"];
      if ($cache === true && $hit === false) {
         $this->boolean(apcu_exists($ckey . $ent2))->isFalse();
      } else if ($cache === true && $hit === true) {
         $this->array(apcu_fetch("$ckey$ent2"))->isIdenticalTo($expected);
      }

      $ancestors = getAncestorsOf('glpi_entities', $ent2);
      $this->array($ancestors)->isIdenticalTo($expected);

      if ($cache === true && $hit === false) {
         $this->array(apcu_fetch("$ckey$ent2"))->isIdenticalTo($expected);
      }

      //test with new sub entity
      //Cache tests:
      //APCu cache is updated on entity creation; so even if we do not expect $hit; we got it.
      $new_id = getItemByTypeName('Entity', 'Sub child entity', true);
      if (!$new_id) {
         $entity = new \Entity();
         $new_id = (int)$entity->add([
            'name'         => 'Sub child entity',
            'entities_id'  => $ent1
         ]);
         $this->integer($new_id)->isGreaterThan(0);
      }

      $expected = [0 => '0', $ent0 => "$ent0", $ent1 => "$ent1"];
      if ($cache === true) {
         $this->array(apcu_fetch("$ckey$new_id"))->isIdenticalTo($expected);
      }

      $ancestors = getAncestorsOf('glpi_entities', $new_id);
      $this->array($ancestors)->isIdenticalTo($expected);

      if ($cache === true && $hit === false) {
         $this->array(apcu_fetch("$ckey$new_id"))->isIdenticalTo($expected);
      }

      //test with another new sub entity
      $new_id2 = getItemByTypeName('Entity', 'Sub child entity 2', true);
      if (!$new_id2) {
         $entity = new \Entity();
         $new_id2 = (int)$entity->add([
            'name'         => 'Sub child entity 2',
            'entities_id'  => $ent2
         ]);
         $this->integer($new_id2)->isGreaterThan(0);
      }

      $expected = [0 => '0', $ent0 => "$ent0", $ent2 => "$ent2"];
      if ($cache === true) {
         $this->array(apcu_fetch("$ckey$new_id2"))->isIdenticalTo($expected);
      }

      $ancestors = getAncestorsOf('glpi_entities', $new_id2);
      $this->array($ancestors)->isIdenticalTo($expected);

      if ($cache === true && $hit === false) {
         $this->array(apcu_fetch("$ckey$new_id2"))->isIdenticalTo($expected);
      }

      //test on multiple entities
      $expected = [0 => '0', $ent0 => "$ent0", $ent1 => "$ent1", $ent2 => "$ent2"];
      if ($cache === true && $hit === false) {
         $this->boolean(apcu_exists($ckey . $new_id . '|' . $new_id2))->isFalse();
      } else if ($cache === true && $hit === true) {
         $this->array(apcu_fetch("$ckey$new_id|$new_id2"))->isIdenticalTo($expected);
      }

      $ancestors = getAncestorsOf('glpi_entities', [$new_id, $new_id2]);
      $this->array($ancestors)->isIdenticalTo($expected);

      if ($cache === true && $hit === false) {
         $this->array(apcu_fetch("$ckey$new_id|$new_id2"))->isIdenticalTo($expected);
      }
   }

   public function testGetAncestorsOf() {
      global $DB;
      //ensure db cache is unset
      $DB->update('glpi_entities', ['ancestors_cache' => null], [true]);
      $this->runGetAncestorsOf();

      $this->integer(
         countElementsInTable(
            'glpi_entities', [
               'NOT' => ['ancestors_cache' => null]]
         )
      )->isGreaterThan(0);
      //run a second time: db cache must be set
      $this->runGetAncestorsOf();
   }

   /**
    * @extensions apcu
    */
   public function testGetAncestorsOfCached() {
      //run with cache
      //first run: no cache hit expected
      $this->runGetAncestorsOf(true);
      //second run: cache hit expected
      $this->runGetAncestorsOf(true, true);
   }


   /**
    * Run getSonsOf tests
    *
    * @param boolean $cache Is cache enabled?
    * @param boolean $hit   Do we expect a cache hit? (ie. data already exists)
    *
    * @return void
    */
   private function runGetSonsOf($cache = false, $hit = false) {
      $ent0 = getItemByTypeName('Entity', '_test_root_entity', true);
      $ent1 = getItemByTypeName('Entity', '_test_child_1', true);
      $ent2 = getItemByTypeName('Entity', '_test_child_2', true);
      $this->newTestedInstance();

      //Cache tests:
      //- if $cache === 0; we do not expect anything,
      //- if $cache === 1; we expect cache to be empty before call, and populated after
      //- if $hit   === 1; we expect cache to be populated

      $ckey = $this->nscache . ':glpi_entities_sons_cache_';

      //test on ent0
      $expected = [$ent0 => "$ent0", $ent1 => "$ent1", $ent2 => "$ent2"];
      if ($cache === true && $hit === false) {
         $this->boolean(apcu_exists($ckey . $ent0))->isFalse();
      } else if ($cache === true && $hit === true) {
         $this->array(apcu_fetch("$ckey$ent0"))->isIdenticalTo($expected);
      }

      $sons = $this->testedInstance->getSonsOf('glpi_entities', $ent0);
      $this->array($sons)->isIdenticalTo($expected);

      if ($cache === true && $hit === false) {
         $this->array(apcu_fetch("$ckey$ent0"))->isIdenticalTo($expected);
      }

      //test on ent1
      $expected = [$ent1 => "$ent1"];
      if ($cache === true && $hit === false) {
         $this->boolean(apcu_exists($ckey . $ent1))->isFalse();
      } else if ($cache === true && $hit === true) {
         $this->array(apcu_fetch("$ckey$ent1"))->isIdenticalTo($expected);
      }

      $sons = $this->testedInstance->getSonsOf('glpi_entities', $ent1);
      $this->array($sons)->isIdenticalTo($expected);

      if ($cache === true && $hit === false) {
         $this->array(apcu_fetch("$ckey$ent1"))->isIdenticalTo($expected);
      }

      //test on ent2
      $expected = [$ent2 => "$ent2"];
      if ($cache === true && $hit === false) {
         $this->boolean(apcu_exists($ckey . $ent2))->isFalse();
      } else if ($cache === true && $hit === true) {
         $this->array(apcu_fetch("$ckey$ent2"))->isIdenticalTo($expected);
      }

      $sons = $this->testedInstance->getSonsOf('glpi_entities', $ent2);
      $this->array($sons)->isIdenticalTo($expected);

      if ($cache === true && $hit === false) {
         $this->array(apcu_fetch("$ckey$ent2"))->isIdenticalTo($expected);
      }

      //test with new sub entity
      //Cache tests:
      //APCu cache is updated on entity creation; so even if we do not expect $hit; we got it.
      $new_id = getItemByTypeName('Entity', 'Sub child entity', true);
      if (!$new_id) {
         $entity = new \Entity();
         $new_id = (int)$entity->add([
            'name'         => 'Sub child entity',
            'entities_id'  => $ent1
         ]);
         $this->integer($new_id)->isGreaterThan(0);
      }

      $expected = [$ent1 => $ent1, $new_id => "$new_id"];
      if ($cache === true) {
         $this->array(apcu_fetch("$ckey$ent1"))->isIdenticalTo($expected);
      }

      $sons = $this->testedInstance->getSonsOf('glpi_entities', $ent1);
      $this->array($sons)->isIdenticalTo($expected);

      if ($cache === true && $hit === false) {
         $this->array(apcu_fetch("$ckey$ent1"))->isIdenticalTo($expected);
      }

      //test with another new sub entity
      $new_id2 = getItemByTypeName('Entity', 'Sub child entity 2', true);
      if (!$new_id2) {
         $entity = new \Entity();
         $new_id2 = (int)$entity->add([
            'name'         => 'Sub child entity 2',
            'entities_id'  => $ent1
         ]);
         $this->integer($new_id2)->isGreaterThan(0);
      }

      $expected = [$ent1 => $ent1, $new_id => "$new_id", $new_id2 => "$new_id2"];
      if ($cache === true) {
         $this->array(apcu_fetch("$ckey$ent1"))->isIdenticalTo($expected);
      }

      $sons = $this->testedInstance->getSonsOf('glpi_entities', $ent1);
      $this->array($sons)->isIdenticalTo($expected);

      if ($cache === true && $hit === false) {
         $this->array(apcu_fetch("$ckey$ent1"))->isIdenticalTo($expected);
      }

      //drop sub entity
      $expected = [$ent1 => $ent1, $new_id2 => "$new_id2"];
      $this->boolean($entity->delete(['id' => $new_id], true))->isTrue();
      if ($cache === true) {
         $this->array(apcu_fetch("$ckey$ent1"))->isIdenticalTo($expected);
      }

      $expected = [$ent1 => $ent1];
      $this->boolean($entity->delete(['id' => $new_id2], true))->isTrue();
      if ($cache === true) {
         $this->array(apcu_fetch("$ckey$ent1"))->isIdenticalTo($expected);
      }
   }

   public function testGetSonsOf() {
      global $DB;
      //ensure db cache is unset
      $DB->update('glpi_entities', ['sons_cache' => null], [true]);
      $this->runGetSonsOf();

      $this->integer(
         $this->testedInstance->countElementsInTable(
            'glpi_entities', [
               'NOT' => ['sons_cache' => null]
            ]
         )
      )->isGreaterThan(0);
      //run a second time: db cache must be set
      $this->runGetSonsOf();
   }

   /**
    * @extensions apcu
    */
   public function testGetSonsOfCached() {
      //run with cache
      //first run: no cache hit expected
      $this->runGetSonsOf(true);
      //second run: cache hit expected
      $this->runGetSonsOf(true, true);
   }

   /**
    * Validates that relation mapping is based on existing tables and fields.
    */
   public function testRelationsValidity() {

      global $DB;

      $this
         ->if($this->newTestedInstance)
         ->then
         ->array($mapping = $this->testedInstance->getDbRelations())
         ->hasKey('_virtual_device');

      $virtual_mapping = $mapping['_virtual_device'];
      unset($mapping['_virtual_device']);

      foreach ($mapping as $tablename => $relations) {
         $this->boolean($DB->tableExists($tablename))
            ->isTrue(sprintf('Invalid table "%s" in relation mapping.', $tablename));

         foreach ($relations as $relation_tablename => $fields) {
            if (strpos($relation_tablename, '_') === 0) {
               $relation_tablename = substr($relation_tablename, 1);
            }

            $this->boolean($DB->tableExists($relation_tablename))
               ->isTrue(sprintf('Invalid table "%s" in relation mapping.', $relation_tablename));

            if (!is_array($fields)) {
               $fields = [$fields];
            }

            foreach ($fields as $field) {
               $this->boolean($DB->fieldExists($relation_tablename, $field))
                  ->isTrue(sprintf('Invalid table field "%s.%s" in relation mapping.', $relation_tablename, $field));
            }
         }
      }

      foreach ($virtual_mapping as $tablename => $fields) {
         $this->boolean($DB->tableExists($tablename))
            ->isTrue(sprintf('Invalid table "%s" in _virtual_device mapping.', $tablename));

         foreach ($fields as $field) {
            $this->boolean($DB->fieldExists($tablename, $field))
               ->isTrue(sprintf('Invalid table field "%s.%s" in _virtual_device mapping.', $tablename, $field));
         }
      }
   }
}
