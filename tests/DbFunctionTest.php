<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

class DbFunctionTest extends DbTestCase {

   protected function setUp() {
      global $CFG_GLPI;

      parent::setUp();
      // Clean the cache
      unset($CFG_GLPI['glpiitemtypetables']);
      unset($CFG_GLPI['glpitablesitemtype']);

      // Pseudo plugin class for test
      include_once 'fixtures/pluginfoobar.php';
   }


   public function dataTableKey() {

      return array(array('foo', ''),
                   array('glpi_computers', 'computers_id'),
                   array('glpi_users', 'users_id'),
                   array('glpi_plugin_foo_bars', 'plugin_foo_bars_id'));
   }


   /**
    * @covers ::getForeignKeyFieldForTable
    * @dataProvider dataTableKey
   **/
   public function testGetForeignKeyFieldForTable($table, $key) {
      $this->assertEquals($key, getForeignKeyFieldForTable($table));
   }


   /**
    * @covers ::isForeignKeyField
    * @dataProvider dataTableKey
   **/
   public function testIsForeignKeyFieldBase($table, $key) {

      if ($key) {
         $this->assertTrue(isForeignKeyField($key));
      }
   }


   /**
    * @covers ::isForeignKeyField
   **/
   public function testIsForeignKeyFieldMore() {

      $this->assertFalse(isForeignKeyField('FakeId'));
      $this->assertFalse(isForeignKeyField('id_Another_Fake_Id'));
      $this->assertTrue(isForeignKeyField('users_id_tech'));
      $this->assertFalse(isForeignKeyField('_id'));
   }


   /**
    * @covers ::getTableNameForForeignKeyField
    * @dataProvider dataTableKey
   **/
   public function testGetTableNameForForeignKeyField($table, $key) {

      if ($key) {
         $this->assertEquals($table, getTableNameForForeignKeyField($key));
      }
   }


   public function dataTableType() {

      return array(array('glpi_computers', 'Computer', true),
                   array('glpi_users', 'User', true),
                   array('glpi_plugin_foo_bars', 'PluginFooBar', true),
                   array('glpi_plugin_foo_bazs', 'PluginFooBaz', false));
   }


   /**
    * @covers ::getTableForItemType
    * @dataProvider dataTableType
   **/
   public function testGetTableForItemType($table, $type, $classexists) {
      $this->assertEquals($table, getTableForItemType($type));
   }


   /**
    * @covers ::getItemTypeForTable
    * @dataProvider dataTableType
   **/
   public function testGetItemTypeForTable($table, $type, $classexists) {

      if ($classexists) {
         $this->assertEquals($type, getItemTypeForTable($table));
      } else {
         $this->assertEquals('UNKNOWN', getItemTypeForTable($table));
      }
   }


   /**
    * @covers ::getItemForItemtype
    * @dataProvider dataTableType
   **/
   public function testGetItemForItemtype($table, $itemtype, $classexists) {

      if ($classexists) {
         $this->assertInstanceOf($itemtype, getItemForItemtype($itemtype));
      } else {
         $this->assertFalse(getItemForItemtype($itemtype));
      }
   }


   public function dataPlural() {

      return array(array('model', 'models'),
                   array('address', 'addresses'),
                   array('computer', 'computers'),
                   array('thing', 'things'),
                   array('criteria', 'criterias'),
                   array('version', 'versions'),
                   array('config', 'configs'),
                   array('machine', 'machines'),
                   array('memory', 'memories'),
                   array('licence', 'licences'));
   }


    /**
    * @covers ::getPlural
    * @dataProvider dataPlural
   **/
   public function testGetPlural($singular, $plural) {

      $this->assertEquals($plural, getPlural($singular));
      $this->assertEquals($plural, getPlural(getPlural($singular)));
   }


   /**
    * @covers ::getSingular
    * @dataProvider dataPlural
   **/
   public function testGetSingular($singular, $plural) {

      $this->assertEquals($singular, getSingular($plural));
      $this->assertEquals($singular, getSingular(getSingular($plural)));
   }


   /**
    * @covers ::countElementsInTable
   **/
   public function testCountElementsInTable() {
   global $DB;

      //the case of using an element that is not a table is not handle in the function :
      //testCountElementsInTable($table, $condition="")
      $this->assertGreaterThan(100, countElementsInTable('glpi_configs'));
      $this->assertGreaterThan(100, countElementsInTable(array('glpi_configs', 'glpi_users')));
      $this->assertGreaterThan(100, countElementsInTable('glpi_configs', "context = 'core'"));
      $this->assertEquals(1, countElementsInTable('glpi_configs', "context = 'core'
                                                   AND `name` = 'version'"));
      $this->assertEquals(0, countElementsInTable('glpi_configs', "context = 'fakecontext'"));
   }


   /**
    * @covers ::countDistinctElementsInTable
   **/
   public function testCountDistinctElementsInTable() {
   global $DB;

      //the case of using an element that is not a table is not handle in the function :
      //testCountElementsInTable($table, $condition="")
      $this->assertGreaterThan(0, countDistinctElementsInTable('glpi_configs','id'));
      $this->assertGreaterThan(0, countDistinctElementsInTable('glpi_configs','context'));
      $this->assertEquals(1, countDistinctElementsInTable('glpi_configs','context',
                                                          "name = 'version'"));
      $this->assertEquals(0, countDistinctElementsInTable('glpi_configs', 'id',
                                                          "context ='fakecontext'"));
   }

   /**
    * @covers ::countElementsInTableForMyEntities
    */
   public function testCountElementsInTableForMyEntities() {

      $this->Login();

      $this->setEntity('_test_root_entity', true);
      $this->assertEquals(6, countElementsInTableForMyEntities('glpi_computers'));
      $this->assertEquals(1, countElementsInTableForMyEntities('glpi_computers', 'name="_test_pc11"'));
      $this->assertEquals(1, countElementsInTableForMyEntities('glpi_computers', 'name="_test_pc01"'));

      $this->setEntity('_test_root_entity', false);
      $this->assertEquals(2, countElementsInTableForMyEntities('glpi_computers'));
      $this->assertEquals(0, countElementsInTableForMyEntities('glpi_computers', 'name="_test_pc11"'));
      $this->assertEquals(1, countElementsInTableForMyEntities('glpi_computers', 'name="_test_pc01"'));

      $this->setEntity('_test_child_1', false);
      $this->assertEquals(2, countElementsInTableForMyEntities('glpi_computers'));
      $this->assertEquals(1, countElementsInTableForMyEntities('glpi_computers', 'name="_test_pc11"'));
      $this->assertEquals(0, countElementsInTableForMyEntities('glpi_computers', 'name="_test_pc01"'));
   }

   /**
    * @covers ::countElementsInTableForEntity
    */
   public function testCountElementsInTableForEntity() {

      $e = getItemByTypeName('Entity', '_test_child_1', true);
      $this->assertEquals(2, countElementsInTableForEntity('glpi_computers', $e));
      $this->assertEquals(1, countElementsInTableForEntity('glpi_computers', $e, 'name="_test_pc11"'));
      $this->assertEquals(0, countElementsInTableForEntity('glpi_computers', $e, 'name="_test_pc01"'));

      $e = getItemByTypeName('Entity', '_test_root_entity', true);
      $this->assertEquals(1, countElementsInTableForEntity('glpi_computers', $e, 'name="_test_pc01"'));
   }

   /**
    *@covers ::getAllDatasFromTable
   **/
   public function testGetAllDatasFromTable() {

      $data = getAllDatasFromTable('glpi_configs');
      $this->assertTrue(is_array($data));
      $this->assertGreaterThan(100,count($data));
      foreach($data as $key => $array) {
         $this->assertTrue(is_array($array));
         $this->assertTrue($key == $array['id']);
      }

      $data = getAllDatasFromTable('glpi_configs', "context = 'core' AND `name` = 'version'");
      $this->assertEquals(1, count($data));

      $data = getAllDatasFromTable('glpi_configs',"", false,'name');
      $previousArrayName = "";
      foreach($data as $key => $array) {
         $this->assertTrue($previousArrayName <= $previousArrayName = $array['name']);
      }
   }

/*
TODO :
getTreeLeafValueName
getTreeValueName
getAncestorsOf
getTreeForItem
contructTreeFromList
contructListFromTree
getRealQueryForTreeItem
regenerateTreeCompleteName
getNextItem
getPreviousItem
formatUserName
getUserName
*/


   /**
    *@covers ::TableExists
   **/
   public function testTableExist() {

      $this->assertTrue(TableExists('glpi_configs'));
      $this->assertFalse(TableExists('fakeTable'));
   }


   /**
    *@covers ::FieldExists
   **/
   public function testFieldExist() {

      $this->assertTrue(FieldExists('glpi_configs','id'));
      $this->assertFalse(FieldExists('glpi_configs','fakeField'));
      $this->assertFalse(FieldExists('fakeTable','id'));
      $this->assertFalse(FieldExists('fakeTable','fakeField'));
   }


   /**
    * @covers ::isIndex
   **/
   public function testIsIndex() {

      $this->assertFalse(isIndex('glpi_configs','fakeField'));
      $this->assertFalse(isIndex('fakeTable','id'));
      $this->assertFalse(isIndex('glpi_configs','name'));
      $this->assertTrue(isIndex('glpi_users','locations_id'));
      $this->assertTrue(isIndex('glpi_users','unicity'));
   }


 /*
 TODO :
    autoName
    closeDBConnections
*/


   /**
    * @covers ::formatOutputWebLink
   **/
   public function testFormatOutputWebLink(){

      $this->assertEquals('http://www.glpi-project.org/',
                          formatOutputWebLink('www.glpi-project.org/'));
      $this->assertEquals('http://www.glpi-project.org/',
                          formatOutputWebLink('http://www.glpi-project.org/'));
   }

/*
TODO :
   getDateRequest
   exportArrayToDB
   importArrayFromDB
   get_hour_from_sql
   getDbRelations
   getEntitiesRestrictRequest
*/

}
