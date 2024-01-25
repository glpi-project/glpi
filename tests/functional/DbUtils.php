<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use DbTestCase;
use org\bovigo\vfs\vfsStream;

/* Test for inc/dbutils.class.php */

class DbUtils extends DbTestCase
{
    public function setUp()
    {
        global $CFG_GLPI;

       // Clean the cache
        unset($CFG_GLPI['glpiitemtypetables']);
        unset($CFG_GLPI['glpitablesitemtype']);
    }

    protected function dataTableKey()
    {

        return [
            ['foo', ''],
            ['glpi_computers', 'computers_id'],
            ['glpi_users', 'users_id'],
            ['glpi_plugin_foo_bars', 'plugin_foo_bars_id'],
            ['glpi_plugin_fooglpis', 'plugin_fooglpis_id']
        ];
    }

    protected function dataTableForeignKey()
    {

        return [
            ['glpi_computers', 'computers_id'],
            ['glpi_users', 'users_id'],
            ['glpi_plugin_foo_bars', 'plugin_foo_bars_id']
        ];
    }

    /**
     * @dataProvider dataTableKey
     **/
    public function testGetForeignKeyFieldForTable($table, $key)
    {
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
    public function testIsForeignKeyFieldBase($table, $key)
    {
        $this
         ->if($this->newTestedInstance)
         ->then
            ->boolean($this->testedInstance->isForeignKeyField($key))->isTrue();

       //keep testing old method from db.function
        $this->boolean(isForeignKeyField($key))->isTrue();
    }

    public function testIsForeignKeyFieldMore()
    {
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
        $this->boolean(isForeignKeyField(''))->isFalse();
        $this->boolean(isForeignKeyField(null))->isFalse();
        $this->boolean(isForeignKeyField(false))->isFalse();
        $this->boolean(isForeignKeyField(42))->isFalse();
    }


    /**
     * @dataProvider dataTableForeignKey
     **/
    public function testGetTableNameForForeignKeyField($table, $key)
    {
        $this
         ->if($this->newTestedInstance)
         ->then
         ->string($this->testedInstance->getTableNameForForeignKeyField($key))->isIdenticalTo($table);

       //keep testing old method from db.function
        $this->string(getTableNameForForeignKeyField($key))->isIdenticalTo($table);
    }

    protected function dataTableType()
    {
       // Pseudo plugin class for test
        require_once __DIR__ . '/../fixtures/another_test.php';
        require_once __DIR__ . '/../fixtures/pluginbarabstractstuff.php';
        require_once __DIR__ . '/../fixtures/pluginbarfoo.php';
        require_once __DIR__ . '/../fixtures/pluginfoobar.php';
        require_once __DIR__ . '/../fixtures/pluginfooservice.php';
        require_once __DIR__ . '/../fixtures/pluginfoo_search_item_filter.php';
        require_once __DIR__ . '/../fixtures/pluginfoo_search_a_b_c_d_e_f_g_bar.php';
        require_once __DIR__ . '/../fixtures/test_a_b.php';

        return [
            ['glpi_dbmysqls', 'DBmysql', false], // not a CommonGLPI, should not be valid
            ['glpi_computers', 'Computer', true],
            ['glpi_appliances_items', 'Appliance_Item', true],
            ['glpi_dashboards_dashboards', 'Glpi\Dashboard\Dashboard', true],
            ['glpi_events', 'Glpi\Event', true],
            ['glpi_users', 'User', true],
            ['glpi_plugin_bar_foos', 'GlpiPlugin\Bar\Foo', true],
            ['glpi_plugin_baz_foos', 'GlpiPlugin\Baz\Foo', false], // class not exists
            ['glpi_plugin_foo_bars', 'PluginFooBar', true],
            ['glpi_plugin_foo_bazs', 'PluginFooBaz', false], // class not exists
            ['glpi_plugin_foo_services', 'PluginFooService', false], // not a CommonGLPI should not be valid
            ['glpi_plugin_foo_searches_items_filters', 'GlpiPlugin\Foo\Search\Item_Filter', true], // Multi-level namespace + CommonDBRelation
            ['glpi_anothers_tests', 'Glpi\Another_Test', true], // Single level namespace + CommonDBRelation
            ['glpi_tests_as_bs', 'Glpi\Test\A_B', true], // Multi-level namespace + CommonDBRelation
            ['glpi_plugin_foo_as_bs_cs_ds_es_fs_gs_bars', 'GlpiPlugin\Foo\A\B\C\D\E\F\G\Bar', true], // Long namespace
        ];
    }

    /**
     * @dataProvider dataTableType
     **/
    public function testGetTableForItemType($table, $type, $is_valid_type)
    {
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
    public function testGetItemTypeForTable($table, $type, $is_valid_type)
    {
        if ($is_valid_type) {
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
        if ($is_valid_type) {
            $this->string(getItemTypeForTable($table))->isIdenticalTo($type);
        } else {
            $this->string(getItemTypeForTable($table))->isIdenticalTo('UNKNOWN');
        }
    }

    protected function getItemForItemtypeProvider(): iterable
    {
        foreach ($this->dataTableType() as $test_case) {
            yield [
                'itemtype'       => $test_case['1'],
                'is_valid'       => $test_case['2'],
                'expected_class' => $test_case['1'],
            ];

            // Should find itemtype even if wrong case is used
            yield [
                'itemtype'       => strtolower($test_case['1']),
                'is_valid'       => $test_case['2'],
                'expected_class' => $test_case['1'],
            ];
        }
    }

    /**
     * @dataProvider getItemForItemtypeProvider
     **/
    public function testGetItemForItemtype($itemtype, $is_valid, $expected_class)
    {
        if ($is_valid) {
            $this
            ->if($this->newTestedInstance)
            ->then
               ->object($this->testedInstance->getItemForItemtype($itemtype))->isInstanceOf($expected_class);
        } else {
            $this
            ->if($this->newTestedInstance)
            ->then
               ->boolean($this->testedInstance->getItemForItemtype($itemtype))->isFalse();
        }

       //keep testing old method from db.function
        if ($is_valid) {
            $this->object(getItemForItemtype($itemtype))->isInstanceOf($expected_class);
        } else {
            $this->boolean(getItemForItemtype($itemtype))->isFalse();
        }
    }

    public function testGetItemForItemtypeSanitized()
    {
        require_once __DIR__ . '/../fixtures/pluginbarfoo.php';

        $this
         ->if($this->newTestedInstance)
         ->when(function () {
               $this->object($this->testedInstance->getItemForItemtype(addslashes('Glpi\Event')))->isInstanceOf('Glpi\Event');
         })->error
            ->withType(E_USER_WARNING)
            ->withMessage('Unexpected sanitized itemtype "Glpi\\\\Event" encountered.')
            ->exists()
         ->when(function () {
               $this->object($this->testedInstance->getItemForItemtype(addslashes('GlpiPlugin\Bar\Foo')))->isInstanceOf('GlpiPlugin\Bar\Foo');
         })->error
            ->withType(E_USER_WARNING)
            ->withMessage('Unexpected sanitized itemtype "GlpiPlugin\\\\Bar\\\\Foo" encountered.')
            ->exists();
    }

    public function testGetItemForItemtypeAbstract()
    {
        require_once __DIR__ . '/../fixtures/pluginbarabstractstuff.php';

        $this
         ->if($this->newTestedInstance)
         ->when(function () {
               $this->boolean($this->testedInstance->getItemForItemtype('CommonDevice'))->isFalse();
         })->error
            ->withType(E_USER_WARNING)
            ->withMessage('Cannot instanciate "CommonDevice" as it is an abstract class.')
            ->exists()
         ->when(function () {
               $this->boolean($this->testedInstance->getItemForItemtype('GlpiPlugin\Bar\AbstractStuff'))->isFalse();
         })->error
            ->withType(E_USER_WARNING)
            ->withMessage('Cannot instanciate "GlpiPlugin\Bar\AbstractStuff" as it is an abstract class.')
            ->exists();
    }

    public function dataPlural()
    {

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
            ['pdu', 'pdus'],
            ['metrics', 'metrics']
        ];
    }

    /**
     * @dataProvider dataPlural
     */
    public function testGetPlural($singular, $plural)
    {
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
    public function testGetSingular($singular, $plural)
    {
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


    public function testCountElementsInTable()
    {
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


    public function testCountDistinctElementsInTable()
    {
        $this
         ->if($this->newTestedInstance)
         ->then
            ->integer($this->testedInstance->countDistinctElementsInTable('glpi_configs', 'id'))->isGreaterThan(0)
            ->integer($this->testedInstance->countDistinctElementsInTable('glpi_configs', 'context'))->isGreaterThan(0)
            ->integer($this->testedInstance->countDistinctElementsInTable('glpi_tickets', 'entities_id'))->isIdenticalTo(2)
            ->integer($this->testedInstance->countDistinctElementsInTable('glpi_crontasks', 'itemtype', ['frequency' => '86400']))->isIdenticalTo(17)
            ->integer($this->testedInstance->countDistinctElementsInTable('glpi_crontasks', 'id', ['frequency' => '86400']))->isIdenticalTo(20)
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

    protected function dataCountMyEntities()
    {
        return [
            ['_test_root_entity', true, 'glpi_computers', [], 9],
            ['_test_root_entity', true, 'glpi_computers', ['name' => '_test_pc11'], 1],
            ['_test_root_entity', true, 'glpi_computers', ['name' => '_test_pc01'], 1],

            ['_test_root_entity', false, 'glpi_computers', [], 4],
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

    protected function dataCountEntities()
    {
        return [
            ['_test_root_entity', 'glpi_computers', [], 4],
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

    public function testGetAllDataFromTable()
    {
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
            ->array($data = $this->testedInstance->getAllDataFromTable('glpi_configs', ['ORDER' => 'name']))->isNotEmpty();
        $previousArrayName = "";
        foreach ($data as $key => $array) {
            $this->boolean($previousArrayName <= $previousArrayName = $array['name'])->isTrue();
        }

       //TODO: test with cache === true

       //keep testing old method from db.function
        $data = getAllDataFromTable('glpi_configs');
        $this->array($data)
         ->size->isGreaterThan(100);
        foreach ($data as $key => $array) {
            $this->array($array)
            ->variable['id']->isEqualTo($key);
        }

        $data = getAllDataFromTable('glpi_configs', ['context' => 'core', 'name' => 'version']);
        $this->array($data)->hasSize(1);

        $data = getAllDataFromTable('glpi_configs', ['ORDER' => 'name']);
        $this->array($data)->isNotEmpty();
        $previousArrayName = "";
        foreach ($data as $key => $array) {
            $this->boolean($previousArrayName <= $previousArrayName = $array['name'])->isTrue();
        }
    }

    public function testIsIndex()
    {
        $this
         ->if($this->newTestedInstance)
         ->then
            ->boolean($this->testedInstance->isIndex('glpi_configs', 'fakeField'))->isFalse()
            ->boolean($this->testedInstance->isIndex('glpi_configs', 'name'))->isTrue()
            ->boolean($this->testedInstance->isIndex('glpi_configs', 'value'))->isFalse()
            ->boolean($this->testedInstance->isIndex('glpi_users', 'locations_id'))->isTrue()
            ->boolean($this->testedInstance->isIndex('glpi_users', 'unicityloginauth'))->isTrue()
         ->when(function () {
               $this->boolean($this->testedInstance->isIndex('fakeTable', 'id'))->isFalse();
         })->error
            ->withType(E_USER_WARNING)
            ->exists();

       //keep testing old method from db.function
        $this->boolean(isIndex('glpi_configs', 'fakeField'))->isFalse();
        $this->boolean(isIndex('glpi_configs', 'name'))->isTrue();
        $this->boolean(isIndex('glpi_users', 'locations_id'))->isTrue();
        $this->boolean(isIndex('glpi_users', 'unicityloginauth'))->isTrue();

        $this->when(function () {
            $this->boolean(isIndex('fakeTable', 'id'))->isFalse();
        })->error
         ->withType(E_USER_WARNING)
         ->exists();
    }

    public function testGetEntityRestrict()
    {
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
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE (`glpi_computers`.`entities_id` IN (\'1\', \'2\', \'3\'))');

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
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE (`glpi_computers`.`entities_id` IN (\'1\'))');

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
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE (`glpi_computers`.`entities_id` IN (\'2\'))');

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
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE (`entities_id` IN (\'2\'))');

       // Child + parent
        $this->setEntity('_test_child_2', false);

        $this->string($this->testedInstance->getEntitiesRestrictRequest('WHERE', 'glpi_computers', '', '', true))
         ->isIdenticalTo("WHERE ( `glpi_computers`.`entities_id` IN ('3')  OR (`glpi_computers`.`is_recursive`='1' AND `glpi_computers`.`entities_id` IN (0, 1)) ) ");
        $it->execute('glpi_computers', $this->testedInstance->getEntitiesRestrictCriteria('glpi_computers', '', '', true));
        $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE (`glpi_computers`.`entities_id` IN (\'3\') OR (`glpi_computers`.`is_recursive` = \'1\' AND `glpi_computers`.`entities_id` IN (\'0\', \'1\')))');

       //keep testing old method from db.function
        $this->string(getEntitiesRestrictRequest('WHERE', 'glpi_computers', '', '', true))
         ->isIdenticalTo("WHERE ( `glpi_computers`.`entities_id` IN ('3')  OR (`glpi_computers`.`is_recursive`='1' AND `glpi_computers`.`entities_id` IN (0, 1)) ) ");
        $it->execute('glpi_computers', getEntitiesRestrictCriteria('glpi_computers', '', '', true));
        $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE ((`glpi_computers`.`entities_id` IN (\'3\') OR (`glpi_computers`.`is_recursive` = \'1\' AND `glpi_computers`.`entities_id` IN (\'0\', \'1\'))))');

       //Child + parent on glpi_entities
        $it->execute('glpi_entities', $this->testedInstance->getEntitiesRestrictCriteria('glpi_entities', '', '', true));
        $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_entities` WHERE (`glpi_entities`.`id` IN (\'3\', \'0\', \'1\'))');

       //keep testing old method from db.function
        $it->execute('glpi_entities', getEntitiesRestrictCriteria('glpi_entities', '', '', true));
        $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_entities` WHERE ((`glpi_entities`.`id` IN (\'3\', \'0\', \'1\')))');

       //Child + parent -- automatic recusrivity detection
        $it->execute('glpi_computers', $this->testedInstance->getEntitiesRestrictCriteria('glpi_computers', '', '', 'auto'));
        $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE (`glpi_computers`.`entities_id` IN (\'3\') OR (`glpi_computers`.`is_recursive` = \'1\' AND `glpi_computers`.`entities_id` IN (\'0\', \'1\')))');

       //keep testing old method from db.function
        $it->execute('glpi_computers', getEntitiesRestrictCriteria('glpi_computers', '', '', 'auto'));
        $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE ((`glpi_computers`.`entities_id` IN (\'3\') OR (`glpi_computers`.`is_recursive` = \'1\' AND `glpi_computers`.`entities_id` IN (\'0\', \'1\'))))');

       // Child + parent without table
        $this->string($this->testedInstance->getEntitiesRestrictRequest('WHERE', '', '', '', true))
         ->isIdenticalTo("WHERE ( `entities_id` IN ('3')  OR (`is_recursive`='1' AND `entities_id` IN (0, 1)) ) ");
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
         ->isIdenticalTo("WHERE ( `entities_id` IN ('3')  OR (`is_recursive`='1' AND `entities_id` IN (0, 1)) ) ");
        $it->execute('glpi_computers', getEntitiesRestrictCriteria('', '', '', true));
        $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_computers` WHERE ((`entities_id` IN (\'3\') OR (`is_recursive` = \'1\' AND `entities_id` IN (\'0\', \'1\'))))');

        $it->execute('glpi_entities', getEntitiesRestrictCriteria('glpi_entities', '', 3, true));
        $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_entities` WHERE ((`glpi_entities`.`id` IN (\'3\', \'0\', \'1\')))');

        $it->execute('glpi_entities', getEntitiesRestrictCriteria('glpi_entities', '', 7, true));
        $this->string($it->getSql())
         ->isIdenticalTo('SELECT * FROM `glpi_entities` WHERE (`glpi_entities`.`id` = \'7\')');
    }

    /**
     * Run getAncestorsOf tests
     *
     * @param boolean $cache Is cache enabled?
     * @param boolean $hit   Do we expect a cache hit? (ie. data already exists)
     *
     * @return void
     */
    private function runGetAncestorsOf($cache = false, $hit = false)
    {
        global $GLPI_CACHE;

        $ent0 = getItemByTypeName('Entity', '_test_root_entity', true);
        $ent1 = getItemByTypeName('Entity', '_test_child_1', true);
        $ent2 = getItemByTypeName('Entity', '_test_child_2', true);

       //Cache tests:
       //- if $cache === 0; we do not expect anything,
       //- if $cache === 1; we expect cache to be empty before call, and populated after
       //- if $hit   === 1; we expect cache to be populated

        $ckey_ent0 = 'ancestors_cache_glpi_entities_' . $ent0;
        $ckey_ent1 = 'ancestors_cache_glpi_entities_' . $ent1;
        $ckey_ent2 = 'ancestors_cache_glpi_entities_' . $ent2;

       //test on ent0
        $expected = [0 => 0];
        if ($cache === true && $hit === false) {
            $this->boolean($GLPI_CACHE->has($ckey_ent0))->isFalse();
        } else if ($cache === true && $hit === true) {
            $this->array($GLPI_CACHE->get($ckey_ent0))->isIdenticalTo($expected);
        }

        $ancestors = getAncestorsOf('glpi_entities', $ent0);
        $this->array($ancestors)->isIdenticalTo($expected);

        if ($cache === true && $hit === false) {
            $this->array($GLPI_CACHE->get($ckey_ent0))->isIdenticalTo($expected);
        }

       //test on ent1
        $expected = [0 => 0, 1 => $ent0];
        if ($cache === true && $hit === false) {
            $this->boolean($GLPI_CACHE->has($ckey_ent1))->isFalse();
        } else if ($cache === true && $hit === true) {
            $this->array($GLPI_CACHE->get($ckey_ent1))->isIdenticalTo($expected);
        }

        $ancestors = getAncestorsOf('glpi_entities', $ent1);
        $this->array($ancestors)->isIdenticalTo($expected);

        if ($cache === true && $hit === false) {
            $this->array($GLPI_CACHE->get($ckey_ent1))->isIdenticalTo($expected);
        }

       //test on ent2
        $expected = [0 => 0, 1 => $ent0];
        if ($cache === true && $hit === false) {
            $this->boolean($GLPI_CACHE->has($ckey_ent2))->isFalse();
        } else if ($cache === true && $hit === true) {
            $this->array($GLPI_CACHE->get($ckey_ent2))->isIdenticalTo($expected);
        }

        $ancestors = getAncestorsOf('glpi_entities', $ent2);
        $this->array($ancestors)->isIdenticalTo($expected);

        if ($cache === true && $hit === false) {
            $this->array($GLPI_CACHE->get($ckey_ent2))->isIdenticalTo($expected);
        }

       //test with new sub entity
       //Cache tests:
       //Cache is updated on entity creation; so even if we do not expect $hit; we got it.
        $new_id = getItemByTypeName('Entity', 'Sub child entity', true);
        if (!$new_id) {
            $entity = new \Entity();
            $new_id = (int)$entity->add([
                'name'         => 'Sub child entity',
                'entities_id'  => $ent1
            ]);
            $this->integer($new_id)->isGreaterThan(0);
        }
        $ckey_new_id = 'ancestors_cache_glpi_entities_' . $new_id;

        $expected = [0 => 0, $ent0 => $ent0, $ent1 => $ent1];
        if ($cache === true) {
            $this->array($GLPI_CACHE->get($ckey_new_id))->isIdenticalTo($expected);
        }

        $ancestors = getAncestorsOf('glpi_entities', $new_id);
        $this->array($ancestors)->isIdenticalTo($expected);

        if ($cache === true && $hit === false) {
            $this->array($GLPI_CACHE->get($ckey_new_id))->isIdenticalTo($expected);
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
        $ckey_new_id2 = 'ancestors_cache_glpi_entities_' . $new_id2;

        $expected = [0 => 0, $ent0 => $ent0, $ent2 => $ent2];
        if ($cache === true) {
            $this->array($GLPI_CACHE->get($ckey_new_id2))->isIdenticalTo($expected);
        }

        $ancestors = getAncestorsOf('glpi_entities', $new_id2);
        $this->array($ancestors)->isIdenticalTo($expected);

        if ($cache === true && $hit === false) {
            $this->array($GLPI_CACHE->get($ckey_new_id2))->isIdenticalTo($expected);
        }

       //test on multiple entities
        $expected = [0 => 0, $ent0 => $ent0, $ent1 => $ent1, $ent2 => $ent2];
        $ckey_new_all = 'ancestors_cache_glpi_entities_' . md5($new_id . '|' . $new_id2);
        if ($cache === true && $hit === false) {
            $this->boolean($GLPI_CACHE->has($ckey_new_all))->isFalse();
        } else if ($cache === true && $hit === true) {
            $this->array($GLPI_CACHE->get($ckey_new_all))->isIdenticalTo($expected);
        }

        $ancestors = getAncestorsOf('glpi_entities', [$new_id, $new_id2]);
        $this->array($ancestors)->isIdenticalTo($expected);

        if ($cache === true && $hit === false) {
            $this->array($GLPI_CACHE->get($ckey_new_all))->isIdenticalTo($expected);
        }
    }

    public function testGetAncestorsOf()
    {
        global $DB;
        $this->login();
       //ensure db cache is unset
        $DB->update('glpi_entities', ['ancestors_cache' => null], [true]);
        $this->runGetAncestorsOf();

        $this->integer(
            countElementsInTable(
                'glpi_entities',
                [
                    'NOT' => ['ancestors_cache' => null]
                ]
            )
        )->isGreaterThan(0);
       //run a second time: db cache must be set
        $this->runGetAncestorsOf();
    }

    /**
     * @tags cache
     */
    public function testGetAncestorsOfCached()
    {
        $this->login();

        global $GLPI_CACHE;
        $GLPI_CACHE->clear(); // login produce cache, must be cleared

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
    private function runGetSonsOf($cache = false, $hit = false)
    {
        global $GLPI_CACHE;

        $ent0 = getItemByTypeName('Entity', '_test_root_entity', true);
        $ent1 = getItemByTypeName('Entity', '_test_child_1', true);
        $ent2 = getItemByTypeName('Entity', '_test_child_2', true);
        $this->newTestedInstance();

       //Cache tests:
       //- if $cache === 0; we do not expect anything,
       //- if $cache === 1; we expect cache to be empty before call, and populated after
       //- if $hit   === 1; we expect cache to be populated

        $ckey_ent0 = 'sons_cache_glpi_entities_' . $ent0;
        $ckey_ent1 = 'sons_cache_glpi_entities_' . $ent1;
        $ckey_ent2 = 'sons_cache_glpi_entities_' . $ent2;

       //test on ent0
        $expected = [$ent0 => $ent0, $ent1 => $ent1, $ent2 => $ent2];
        if ($cache === true && $hit === false) {
            $this->boolean($GLPI_CACHE->has($ckey_ent0))->isFalse();
        } else if ($cache === true && $hit === true) {
            $this->array($GLPI_CACHE->get($ckey_ent0))->isIdenticalTo($expected);
        }

        $sons = $this->testedInstance->getSonsOf('glpi_entities', $ent0);
        $this->array($sons)->isIdenticalTo($expected);

        if ($cache === true && $hit === false) {
            $this->array($GLPI_CACHE->get($ckey_ent0))->isIdenticalTo($expected);
        }

       //test on ent1
        $expected = [$ent1 => $ent1];
        if ($cache === true && $hit === false) {
            $this->boolean($GLPI_CACHE->has($ckey_ent1))->isFalse();
        } else if ($cache === true && $hit === true) {
            $this->array($GLPI_CACHE->get($ckey_ent1))->isIdenticalTo($expected);
        }

        $sons = $this->testedInstance->getSonsOf('glpi_entities', $ent1);
        $this->array($sons)->isIdenticalTo($expected);

        if ($cache === true && $hit === false) {
            $this->array($GLPI_CACHE->get($ckey_ent1))->isIdenticalTo($expected);
        }

       //test on ent2
        $expected = [$ent2 => $ent2];
        if ($cache === true && $hit === false) {
            $this->boolean($GLPI_CACHE->has($ckey_ent2))->isFalse();
        } else if ($cache === true && $hit === true) {
            $this->array($GLPI_CACHE->get($ckey_ent2))->isIdenticalTo($expected);
        }

        $sons = $this->testedInstance->getSonsOf('glpi_entities', $ent2);
        $this->array($sons)->isIdenticalTo($expected);

        if ($cache === true && $hit === false) {
            $this->array($GLPI_CACHE->get($ckey_ent2))->isIdenticalTo($expected);
        }

       //test with new sub entity
       //Cache tests:
       //Cache is updated on entity creation; so even if we do not expect $hit; we got it.
        $new_id = getItemByTypeName('Entity', 'Sub child entity', true);
        if (!$new_id) {
            $entity = new \Entity();
            $new_id = (int)$entity->add([
                'name'         => 'Sub child entity',
                'entities_id'  => $ent1
            ]);
            $this->integer($new_id)->isGreaterThan(0);
        }

        $expected = [$ent1 => $ent1, $new_id => $new_id];
        if ($cache === true) {
            $this->array($GLPI_CACHE->get($ckey_ent1))->isIdenticalTo($expected);
        }

        $sons = $this->testedInstance->getSonsOf('glpi_entities', $ent1);
        $this->array($sons)->isIdenticalTo($expected);

        if ($cache === true && $hit === false) {
            $this->array($GLPI_CACHE->get($ckey_ent1))->isIdenticalTo($expected);
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

        $expected = [$ent1 => $ent1, $new_id => $new_id, $new_id2 => $new_id2];
        if ($cache === true) {
            $this->array($GLPI_CACHE->get($ckey_ent1))->isIdenticalTo($expected);
        }

        $sons = $this->testedInstance->getSonsOf('glpi_entities', $ent1);
        $this->array($sons)->isIdenticalTo($expected);

        if ($cache === true && $hit === false) {
            $this->array($GLPI_CACHE->get($ckey_ent1))->isIdenticalTo($expected);
        }

       //drop sub entity
        $expected = [$ent1 => $ent1, $new_id2 => $new_id2];
        $this->boolean($entity->delete(['id' => $new_id], true))->isTrue();
        if ($cache === true) {
            $this->array($GLPI_CACHE->get($ckey_ent1))->isIdenticalTo($expected);
        }

        $expected = [$ent1 => $ent1];
        $this->boolean($entity->delete(['id' => $new_id2], true))->isTrue();
        if ($cache === true) {
            $this->array($GLPI_CACHE->get($ckey_ent1))->isIdenticalTo($expected);
        }
    }

    public function testGetSonsOf()
    {
        global $DB;
        $this->login();
       //ensure db cache is unset
        $DB->update('glpi_entities', ['sons_cache' => null], [true]);
        $this->runGetSonsOf();

        $this->integer(
            $this->testedInstance->countElementsInTable(
                'glpi_entities',
                [
                    'NOT' => ['sons_cache' => null]
                ]
            )
        )->isGreaterThan(0);
       //run a second time: db cache must be set
        $this->runGetSonsOf();
    }

    /**
     * @tags cache
     */
    public function testGetSonsOfCached()
    {
        $this->login();

        global $GLPI_CACHE;
        $GLPI_CACHE->clear(); // login produce cache, must be cleared

       //run with cache
       //first run: no cache hit expected
        $this->runGetSonsOf(true);
       //second run: cache hit expected
        $this->runGetSonsOf(true, true);
    }

    /**
     * Validates that relation mapping is based on existing tables and fields.
     */
    public function testRelationsValidity()
    {
        global $DB;

        $this->newTestedInstance();

        $mapping = $this->testedInstance->getDbRelations();

        // Check that target fields exists in database
        foreach ($mapping as $source_table => $relations) {
            $this->boolean($DB->tableExists($source_table))
                ->isTrue(sprintf('Invalid table "%s" in relation mapping.', $source_table));

            foreach ($relations as $target_table_key => $target_fields) {
                $target_table = preg_replace('/^_/', '', $target_table_key);

                $this->boolean($DB->tableExists($target_table))
                    ->isTrue(sprintf('Invalid table "%s" in "%s" mapping.', $target_table, $source_table));

                $this->array($target_fields);

                $fields_to_check = [];
                foreach ($target_fields as $target_field) {
                    if (is_array($target_field)) {
                        // Polymorphic relation
                        $this->array($target_field)->size->isEqualTo(2); // has only `itemtype*` and `items_id*` fields
                        if ($target_table === 'glpi_ipaddresses') {
                            $this->array($target_field)->containsValues(['mainitemtype', 'mainitems_id']);
                        } else {
                            $this->integer(count(preg_grep('/^itemtype/', $target_field)))->isEqualTo(1);
                            $this->integer(count(preg_grep('/^items_id/', $target_field)))->isEqualTo(1);
                        }
                        $fields_to_check = array_merge($fields_to_check, $target_field);
                    } else {
                        // Ensure polymorphic relations are correctly declared in an array with both fields names.
                        $msg = sprintf('Invalid table field "%s.%s" in "%s" mapping.', $target_table, $target_field, $source_table);
                        $this->string($target_field)
                            ->notMatches('/^itemtype/', $msg)
                            ->notMatches('/^items_id/', $msg);

                        $fields_to_check[] = $target_field;
                    }
                }
                foreach ($fields_to_check as $field_to_check) {
                    $this->boolean($DB->fieldExists($target_table, $field_to_check))
                        ->isTrue(sprintf('Invalid table field "%s.%s" in "%s" mapping.', $target_table, $field_to_check, $source_table));
                }
            }
        }

        // Check for duplicated relations declaration
        $already_known_relations = [];
        $duplicated_relations    = [];
        $mixed_relations         = [];
        foreach ($mapping as $source_table => $relations) {
            foreach ($relations as $target_table_key => $target_fields) {
                $is_table_key_prefixed = str_starts_with($target_table_key, '_');

                $unprefixed_table_key = $is_table_key_prefixed ? preg_replace('/^_/', '', $target_table_key) : $target_table_key;
                $prefixed_table_key   = $is_table_key_prefixed ? $target_table_key : '_' . $target_table_key;

                foreach ($target_fields as $target_field) {
                    $relation            = sprintf('‣ "%s" > "%s" > %s', $source_table, $target_table_key, json_encode($target_field));
                    $unprefixed_relation = sprintf('‣ "%s" > "%s" > %s', $source_table, $unprefixed_table_key, json_encode($target_field));
                    $prefixed_relation   = sprintf('‣ "%s" > "%s" > %s', $source_table, $prefixed_table_key, json_encode($target_field));

                    if (in_array($relation, $already_known_relations)) {
                        $duplicated_relations[] = $relation;
                    } elseif (
                        ($is_table_key_prefixed && in_array($unprefixed_relation, $already_known_relations))
                        || (!$is_table_key_prefixed && in_array($prefixed_relation, $already_known_relations))
                    ) {
                        $mixed_relations[] = $relation;
                    }

                    $already_known_relations[] = $relation;
                }
            }
        }

        // Check that relations are all declared (and correctly declared)
        $expected_mapping = [];

        // Compute expected relations based on foreign keys in tables
        foreach ($DB->listTables() as $table_specs) {
            $target_table = $table_specs['TABLE_NAME'];
            $target_itemtype = getItemTypeForTable($target_table);
            if (!is_a($target_itemtype, \CommonDBTM::class, true)) {
                continue;
            }

            foreach ($DB->listFields($target_table) as $field_specs) {
                $target_field = $field_specs['Field'];
                if (!isForeignKeyField($target_field) || preg_match('/^int([ (].+)*$/', $field_specs['Type']) !== 1) {
                    continue;
                }

                $source_itemtype = getItemtypeForForeignKeyField($target_field);
                if (!is_a($source_itemtype, \CommonDBTM::class, true)) {
                    continue;
                }
                $source_table = $source_itemtype::getTable();

                $target_table_key_prefix = '';
                if (
                    (
                        is_a($target_itemtype, \CommonDBChild::class, true)
                        && $target_itemtype::$itemtype === $source_itemtype
                        && $target_itemtype::$items_id === $target_field
                        && $target_itemtype::$mustBeAttached === true
                    )
                    || (
                        is_a($target_itemtype, \CommonDBRelation::class, true)
                        && (
                            (
                                $target_itemtype::$itemtype_1 === $source_itemtype
                                && $target_itemtype::$items_id_1 === $target_field
                                && $target_itemtype::$mustBeAttached_1 === true
                            )
                            || (
                                $target_itemtype::$itemtype_2 === $source_itemtype
                                && $target_itemtype::$items_id_2 === $target_field
                                && $target_itemtype::$mustBeAttached_2 === true
                            )
                        )
                    )
                ) {
                    // If item must be attached, target table key has to be prefixed by "_"
                    // to be ignored by `CommonDBTM::cleanRelationData()`. Indeed, without usage of this prefix,
                    // related item will be preserved with its foreign key defined to 0, making it an unwanted orphaned item.
                    $target_table_key_prefix = '_';
                } elseif ($target_itemtype::getIndexName() === $target_field) {
                    // Automatic update will not be possible due to the way automatic update is done in `CommonDBTM::cleanRelationData()`.
                    $target_table_key_prefix = '_';
                }
                $target_table_key = $target_table_key_prefix . $target_table;

                if (!array_key_exists($source_table, $expected_mapping)) {
                    $expected_mapping[$source_table] = [];
                }
                if (!array_key_exists($target_table_key, $expected_mapping[$source_table])) {
                    $expected_mapping[$source_table][$target_table_key] = [];
                }

                $expected_mapping[$source_table][$target_table_key][] = $target_field;
            }
        }

        // FIXME Try to automatize computation of expected polymorphic relations mapping (not sure it is possible).

        // Check for missing relation
        $missing_relations = [];
        $forbiddenly_prefixed_relations = [];
        foreach ($expected_mapping as $expected_source_table => $expected_relations) {
            foreach ($expected_relations as $expected_target_table_key => $expected_target_fields) {
                foreach ($expected_target_fields as $expected_target_field) {
                    $is_expected_key_prefixed = str_starts_with($expected_target_table_key, '_');

                    $unprefixed_table_key = $is_expected_key_prefixed ? preg_replace('/^_/', '', $expected_target_table_key) : $expected_target_table_key;
                    $prefixed_table_key   = $is_expected_key_prefixed ? $expected_target_table_key : '_' . $expected_target_table_key;

                    $is_declared_without_prefix = isset($mapping[$expected_source_table][$unprefixed_table_key])
                        && in_array($expected_target_field, $mapping[$expected_source_table][$unprefixed_table_key]);
                    $is_declared_with_prefix    = isset($mapping[$expected_source_table][$prefixed_table_key])
                        && in_array($expected_target_field, $mapping[$expected_source_table][$prefixed_table_key]);

                    if ($is_declared_without_prefix && $is_expected_key_prefixed) {
                        // If "_" prefix is present in expected mapping, it means that computation
                        // states that relation has to be handled specifically.
                        // In this case, having this declaration without "_" prefix in result mapping is forbidden.
                        $forbiddenly_prefixed_relations[] = sprintf(
                            '‣ "%s" > "%s" > %s',
                            $expected_source_table,
                            $unprefixed_table_key,
                            json_encode($expected_target_field)
                        );
                    } elseif (!$is_declared_without_prefix && !$is_declared_with_prefix) {
                        $missing_relations[] = sprintf(
                            '‣ "%s" > "%s" > %s',
                            $expected_source_table,
                            $expected_target_table_key,
                            json_encode($expected_target_field)
                        );
                    }
                }
            }
        }

        sort($forbiddenly_prefixed_relations);
        sort($missing_relations);
        sort($duplicated_relations);
        sort($mixed_relations);

        $msg = PHP_EOL;
        if (!empty($forbiddenly_prefixed_relations)) {
            $msg .= 'Following relations are declared without "_" prefix but should not:'
                . PHP_EOL
                . implode(PHP_EOL, $forbiddenly_prefixed_relations)
                . PHP_EOL;
        }
        if (!empty($missing_relations)) {
            $msg .= 'Following relations are missing:'
                . PHP_EOL
                . implode(PHP_EOL, $missing_relations)
                . PHP_EOL;
        }
        if (!empty($duplicated_relations)) {
            $msg .= 'Following relations are duplicated:'
                . PHP_EOL
                . implode(PHP_EOL, $duplicated_relations)
                . PHP_EOL;
        }
        if (!empty($mixed_relations)) {
            $msg .= 'Following relations are mixed (declared twice with and without "_" prefix):'
                . PHP_EOL
                . implode(PHP_EOL, $mixed_relations)
                . PHP_EOL;
        }
        $this->boolean(empty($forbiddenly_prefixed_relations) && empty($missing_relations) && empty($duplicated_relations) && empty($mixed_relations))
            ->isTrue($msg);
    }

    /**
     * Test getDateCriteria
     *
     * @return void
     */
    public function testGetDateCriteria()
    {
        $this->newTestedInstance();

        $this->array(
            $this->testedInstance->getDateCriteria('date', null, null)
        )->isIdenticalTo([]);

        $this->array(
            $this->testedInstance->getDateCriteria('date', '', '')
        )->isIdenticalTo([]);

        $this->array(
            $this->testedInstance->getDateCriteria('date', '2018-11-09', null)
        )->isIdenticalTo([
            ['date' => ['>=', '2018-11-09']]
        ]);

        $result = $this->testedInstance->getDateCriteria('date', null, '2018-11-09');
        $this->array($result)->hasSize(1);

        $this->array($result[0]['date'])
            ->hasSize(2)
            ->string[0]->isIdenticalTo('<=')
            ->object[1]->isInstanceOf('\QueryExpression');

        $this->string(
            $result[0]['date'][1]->getValue()
        )->isIdenticalTo("ADDDATE('2018-11-09', INTERVAL 1 DAY)");

        $result = $this->testedInstance->getDateCriteria('date', '2018-11-08', '2018-11-09');
        $this->array($result)->hasSize(2);

        $this->array($result[0])->isIdenticalTo(['date' => ['>=', '2018-11-08']]);
        $this->array($result[1]['date'])
            ->hasSize(2)
            ->string[0]->isIdenticalTo('<=')
            ->object[1]->isInstanceOf('\QueryExpression');

        $this->string(
            $result[1]['date'][1]->getValue()
        )->isIdenticalTo("ADDDATE('2018-11-09', INTERVAL 1 DAY)");

        $result = null;
        $this->when(function () use (&$result) {
            $result = $this->testedInstance->getDateCriteria('date', '2023-02-19\', INTERVAL 1 DAY)))))', null);
        })->error
            ->withType(E_USER_WARNING)
            ->withMessage('Invalid "2023-02-19\', INTERVAL 1 DAY)))))" date value.')
            ->exists();
        $this->array($result)->isIdenticalTo([]);

        $result = null;
        $this->when(function () use (&$result) {
            $result = $this->testedInstance->getDateCriteria('date', null, '2021-02-19\', INTERVAL 1 DAY)))))');
        })->error
            ->withType(E_USER_WARNING)
            ->withMessage('Invalid "2021-02-19\', INTERVAL 1 DAY)))))" date value.')
            ->exists();
        $this->array($result)->isIdenticalTo([]);
    }

    protected function autoNameProvider()
    {
        return [
         //will return name without changes
            [
            //not a template
                'name'         => 'Computer 1',
                'field'        => 'name',
                'is_template'  => false,
                'itemtype'     => 'Computer',
                'entities_id'  => -1, //default
                'expected'     => 'Computer 1'
            ], [
            //not a template
                'name'         => '&lt;abc&gt;',
                'field'        => 'name',
                'is_template'  => false,
                'itemtype'     => 'Computer',
                'entities_id'  => -1, // default
                'expected'     => '&lt;abc&gt;',
                'deprecated'   => false, // is_template=false result in exiting before deprecation warning
            ], [
            //does not match pattern
                'name'         => '&lt;abc&gt;',
                'field'        => 'name',
                'is_template'  => true,
                'itemtype'     => 'Computer',
                'entities_id'  => -1, // default
                'expected'     => '&lt;abc&gt;',
                'deprecated'   => true,
            ], [
            //first added
                'name'         => '&lt;####&gt;',
                'field'       => 'name',
                'is_template'  => true,
                'itemtype'     => 'Computer',
                'entities_id'  => -1, // default
                'expected'     => '0001',
                'deprecated'   => true,
            ], [
            //existing
                'name'         => '&lt;_test_pc##&gt;',
                'field'       => 'name',
                'is_template'  => true,
                'itemtype'     => 'Computer',
                'entities_id'  => -1, // default
                'expected'     => '_test_pc23',
                'deprecated'   => true,
            ], [
            //not existing on entity
                'name'         => '&lt;_test_pc##&gt;',
                'field'       => 'name',
                'is_template'  => true,
                'itemtype'     => 'Computer',
                'entities_id'  => 0,
                'expected'     => '_test_pc01',
                'deprecated'   => true,
            ], [
            // not existing on entity, with multibyte strings
                'name'         => '<自動名稱測試_##>',
                'field'       => 'name',
                'is_template'  => true,
                'itemtype'     => 'Computer',
                'entities_id'  => 0,
                'expected'     => '自動名稱測試_01'
            ], [
            // not existing on entity, with multibyte strings
                'name'         => '<自動名稱—####—測試>',
                'field'       => 'name',
                'is_template'  => true,
                'itemtype'     => 'Computer',
                'entities_id'  => 0,
                'expected'     => '自動名稱—0001—測試'
            ], [
            //existing on entity
                'name'         => '&lt;_test_pc##&gt;',
                'field'       => 'name',
                'is_template'  => true,
                'itemtype'     => 'Computer',
                'entities_id'  => 1,
                'expected'     => '_test_pc04',
                'deprecated'   => true,
            ], [
            //existing on entity
                'name'         => '&lt;_test_pc##&gt;',
                'field'       => 'name',
                'is_template'  => true,
                'itemtype'     => 'Computer',
                'entities_id'  => 2,
                'expected'     => '_test_pc14',
                'deprecated'   => true,
            ], [
            // existing on entity, new XSS clean output
                'name'         => '&#60;_test_pc##&#62;',
                'field'       => 'name',
                'is_template'  => true,
                'itemtype'     => 'Computer',
                'entities_id'  => 2,
                'expected'     => '_test_pc14',
                'deprecated'   => true,
            ], [
            // existing on entity, not sanitized
                'name'         => '<_test_pc##>',
                'field'       => 'name',
                'is_template'  => true,
                'itemtype'     => 'Computer',
                'entities_id'  => 2,
                'expected'     => '_test_pc14'
            ], [
            // not existing on entity, new XSS clean output, and containing a special char
                'name'         => '&#60;pc_&#60;_##&#62;',
                'field'       => 'name',
                'is_template'  => true,
                'itemtype'     => 'Computer',
                'entities_id'  => 2,
                'expected'     => 'pc_&#60;_01',
                'deprecated'   => true,
            ], [
            // not existing on entity, not sanitized, and containing a special char
                'name'         => '<pc_>_##>',
                'field'       => 'name',
                'is_template'  => true,
                'itemtype'     => 'Computer',
                'entities_id'  => 2,
                'expected'     => 'pc_>_01'
            ],
        ];
    }

    /**
     * @dataProvider autoNameProvider
     */
    public function testAutoName($name, $field, $is_template, $itemtype, $entities_id, $expected, bool $deprecated = false)
    {
        $this->newTestedInstance;

        $call = function () use ($name, $field, $is_template, $itemtype, $entities_id) {
            return $this->testedInstance->autoName(
                $name,
                $field,
                $is_template,
                $itemtype,
                $entities_id
            );
        };
        if (!$deprecated) {
            $autoname = $call();
        } else {
            $autoname = null;
            $this->when($autoname = $call())
            ->error()
               ->withType(E_USER_DEPRECATED)
               ->withMessage('Handling of encoded/escaped value in autoName() is deprecated.')
               ->exists();
        }
        $this->string($autoname)->isIdenticalTo($expected);
    }


    /**
     * Data provider for self::testGetItemtypeWithFixedCase().
     */
    protected function fixItemtypeCaseProvider()
    {
        return [
         // Bad case classnames matching and existing class file
            [
                'itemtype' => 'myclass',
                'expected' => 'MyClass',
            ],
            [
                'itemtype' => 'glpi\\appliCation\\CoNsOlE\\MyCommand',
                'expected' => 'Glpi\\Application\\Console\\MyCommand',
            ],
            [
                'itemtype' => 'Glpi\\Something\\Item_filter',
                'expected' => 'Glpi\\Something\\Item_Filter',
            ],
            [
                'itemtype' => 'PluginFooBaritem',
                'expected' => 'PluginFooBarItem',
            ],
            [
                'itemtype' => 'GlpiPluGin\\Foo\\Namespacedbar',
                'expected' => 'GlpiPlugin\\Foo\\NamespacedBar',
            ],
            [
                'itemtype' => 'glpiplugin\\foo\\models\\foo\\bar_item',
                'expected' => 'GlpiPlugin\\Foo\\Models\\Foo\\Bar_Item',
            ],
         // Good case (should not be altered)
            [
                'itemtype' => 'MyClass',
                'expected' => 'MyClass',
            ],
            [
                'itemtype' => 'Glpi\\Application\\Console\\MyCommand',
                'expected' => 'Glpi\\Application\\Console\\MyCommand',
            ],
            [
                'itemtype' => 'GlpiPlugin\\Foo\\NamespacedBar',
                'expected' => 'GlpiPlugin\\Foo\\NamespacedBar',
            ],
         // Not matching any class file (should not be altered)
            [
                'itemtype' => 'notanitemtype',
                'expected' => 'notanitemtype',
            ],
            [
                'itemtype' => 'GlpiPlugin\\Invalid\\itemtype',
                'expected' => 'GlpiPlugin\\Invalid\\itemtype',
            ],
        ];
    }

    /**
     * @dataProvider fixItemtypeCaseProvider
     */
    public function testGetItemtypeWithFixedCase($itemtype, $expected)
    {

        $this->newTestedInstance();

        vfsStream::setup(
            'glpi',
            null,
            [
                'src' => [
                    'Application' => [
                        'Console' => [
                            'MyCommand.php' => '',
                        ],
                    ],
                    'Something' => [
                        'Item_Filter.php' => '',
                    ],
                    'MyClass.php' => '',
                    'NamespacedClass.php' => '',
                ],
                'plugins' => [
                    'foo' => [
                        'src' => [
                            'Models' => [
                                'Foo' => [
                                    'Bar_Item.php' => '',
                                ],
                            ],
                            'NamespacedBar.php' => '',
                            'PluginFooBarItem.php' => '',
                        ],
                    ],
                ],
            ]
        );

        $result = $this->testedInstance->fixItemtypeCase($itemtype, vfsStream::url('glpi'));
        $this->variable($result)->isEqualTo($expected);
    }
}
