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

use DbTestCase;
use Glpi\Dashboard\Dashboard;
use Glpi\DBAL\QueryExpression;
use GlpiPlugin\Tester\Asset\Foo;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\LogLevel;

/* Test for inc/dbutils.class.php */

class DbUtilsTest extends DbTestCase
{
    public function setUp(): void
    {
        global $CFG_GLPI;

        // Clean the cache
        unset($CFG_GLPI['glpiitemtypetables']);
        unset($CFG_GLPI['glpitablesitemtype']);
        parent::setUp();
    }

    public static function dataTableKey()
    {
        return [
            ['foo', ''],
            ['glpi_computers', 'computers_id'],
            ['glpi_users', 'users_id'],
            ['glpi_plugin_foo_bars', 'plugin_foo_bars_id'],
            ['glpi_plugin_fooglpis', 'plugin_fooglpis_id'],
        ];
    }

    public static function dataTableForeignKey()
    {

        return [
            ['glpi_computers', 'computers_id'],
            ['glpi_users', 'users_id'],
            ['glpi_plugin_foo_bars', 'plugin_foo_bars_id'],
        ];
    }

    #[DataProvider('dataTableKey')]
    public function testGetForeignKeyFieldForTable($table, $key)
    {
        $instance = new \DbUtils();
        $this->assertSame($key, $instance->getForeignKeyFieldForTable($table));

        //keep testing old method from db.function
        $this->assertSame($key, getForeignKeyFieldForTable($table));
    }

    #[DataProvider('dataTableForeignKey')]
    public function testIsForeignKeyFieldBase($table, $key)
    {
        $instance = new \DbUtils();
        $this->assertTrue($instance->isForeignKeyField($key));

        //keep testing old method from db.function
        $this->assertTrue(isForeignKeyField($key));
    }

    public function testIsForeignKeyFieldMore()
    {
        $instance = new \DbUtils();
        $this->assertFalse($instance->isForeignKeyField('FakeId'));
        $this->assertFalse($instance->isForeignKeyField('id_Another_Fake_Id'));
        $this->assertTrue($instance->isForeignKeyField('users_id_tech'));
        $this->assertFalse($instance->isForeignKeyField('_id'));

        //keep testing old method from db.function
        $this->assertFalse(isForeignKeyField('FakeId'));
        $this->assertFalse(isForeignKeyField('id_Another_Fake_Id'));
        $this->assertTrue(isForeignKeyField('users_id_tech'));
        $this->assertFalse(isForeignKeyField('_id'));
        $this->assertFalse(isForeignKeyField(''));
        $this->assertFalse(isForeignKeyField(null));
        $this->assertFalse(isForeignKeyField(false));
        $this->assertFalse(isForeignKeyField(42));
    }

    #[DataProvider('dataTableForeignKey')]
    public function testGetTableNameForForeignKeyField($table, $key)
    {
        $instance = new \DbUtils();
        $this->assertSame($table, $instance->getTableNameForForeignKeyField($key));

        //keep testing old method from db.function
        $this->assertSame($table, getTableNameForForeignKeyField($key));
    }

    public static function dataTableType()
    {
        // Pseudo plugin class for test
        require_once FIXTURE_DIR . '/another_test.php';
        require_once FIXTURE_DIR . '/pluginbarabstractstuff.php';
        require_once FIXTURE_DIR . '/pluginbarfoo.php';
        require_once FIXTURE_DIR . '/pluginfoobar.php';
        require_once FIXTURE_DIR . '/pluginfooservice.php';
        require_once FIXTURE_DIR . '/pluginfoo_search_item_filter.php';
        require_once FIXTURE_DIR . '/pluginfoo_item_filter.php';
        require_once FIXTURE_DIR . '/pluginfoo_search_a_b_c_d_e_f_g_bar.php';
        require_once FIXTURE_DIR . '/test_a_b.php';

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
            ['glpi_plugin_foo_items_filters', 'GlpiPlugin\Foo\Item_Filter', true], // Single level namespace + CommonDBRelation
            ['glpi_anothers_tests', 'Glpi\Another_Test', true], // Single level namespace + CommonDBRelation
            ['glpi_tests_as_bs', 'Glpi\Test\A_B', true], // Multi-level namespace + CommonDBRelation
            ['glpi_plugin_foo_as_bs_cs_ds_es_fs_gs_bars', 'GlpiPlugin\Foo\A\B\C\D\E\F\G\Bar', true], // Long namespace
        ];
    }

    public static function getTableForItemTypeProvider()
    {
        $table_types_mapping = array_map(
            function ($entry) {
                return array_slice($entry, 0, 2); // remove the useless `is_valid` param
            },
            self::dataTableType()
        );

        $table_types_mapping[] = ['glpi_configs', \NotificationSetting::class];

        $known_classes = self::getClasses();

        foreach ($known_classes as $known_class) {
            if (is_a($known_class, \NotificationTarget::class, true)) {
                // Classes that extends NotificationTarget are always using the `glpi_notificationtargets` table
                $table_types_mapping[] = ['glpi_notificationtargets', $known_class];
            }
            if (is_a($known_class, \Rule::class, true) && !in_array($known_class, [\OlaLevel::class, \SlaLevel::class])) {
                // Classes that extends Rule are always using the `glpi_rules` table
                $table_types_mapping[] = ['glpi_rules', $known_class];
            }
            if (is_a($known_class, \RuleCollection::class, true)) {
                // Classes that extends RuleCollection are always using the `glpi_rules` table
                $table_types_mapping[] = ['glpi_rules', $known_class];
            }
        }

        // specific cases of classes that extends Rule
        $table_types_mapping[] = ['glpi_olalevels', \OlaLevel::class];
        $table_types_mapping[] = ['glpi_slalevels', \SlaLevel::class];

        return $table_types_mapping;
    }

    #[DataProvider('getTableForItemTypeProvider')]
    public function testGetTableForItemType($table, $type)
    {
        $instance = new \DbUtils();
        $this->assertSame($table, $instance->getTableForItemType($type));

        //keep testing old method from db.function
        $this->assertSame($table, getTableForItemType($type));
    }

    #[DataProvider('dataTableType')]
    public function testGetExpectedTableNameForClass($table, $type, $is_valid_type)
    {
        $instance = new \DbUtils();
        $this->assertSame(
            $table,
            $instance->getExpectedTableNameForClass($type)
        );
    }

    #[DataProvider('dataTableType')]
    public function testGetItemTypeForTable($table, $type, $is_valid_type)
    {
        require_once __DIR__ . '/../../tests/fixtures/another_test.php';
        require_once __DIR__ . '/../../tests/fixtures/pluginbarabstractstuff.php';
        require_once __DIR__ . '/../../tests/fixtures/pluginbarfoo.php';
        require_once __DIR__ . '/../../tests/fixtures/pluginfoobar.php';
        require_once __DIR__ . '/../../tests/fixtures/pluginfooservice.php';
        require_once __DIR__ . '/../../tests/fixtures/pluginfoo_search_item_filter.php';
        require_once __DIR__ . '/../../tests/fixtures/pluginfoo_item_filter.php';
        require_once __DIR__ . '/../../tests/fixtures/pluginfoo_search_a_b_c_d_e_f_g_bar.php';
        require_once __DIR__ . '/../../tests/fixtures/test_a_b.php';

        $instance = new \DbUtils();
        if ($is_valid_type) {
            $this->assertSame($type, $instance->getItemTypeForTable($table));
        } else {
            $this->assertNull($instance->getItemTypeForTable($table));
        }

        //keep testing old method from db.function
        if ($is_valid_type) {
            $this->assertSame($type, getItemTypeForTable($table));
        } else {
            $this->assertNull(getItemTypeForTable($table));
        }
    }

    #[DataProvider('dataTableType')]
    public function testGetItemForTable($table, $type, $is_valid_type)
    {
        require_once __DIR__ . '/../../tests/fixtures/another_test.php';
        require_once __DIR__ . '/../../tests/fixtures/pluginbarabstractstuff.php';
        require_once __DIR__ . '/../../tests/fixtures/pluginbarfoo.php';
        require_once __DIR__ . '/../../tests/fixtures/pluginfoobar.php';
        require_once __DIR__ . '/../../tests/fixtures/pluginfooservice.php';
        require_once __DIR__ . '/../../tests/fixtures/pluginfoo_search_item_filter.php';
        require_once __DIR__ . '/../../tests/fixtures/pluginfoo_item_filter.php';
        require_once __DIR__ . '/../../tests/fixtures/pluginfoo_search_a_b_c_d_e_f_g_bar.php';
        require_once __DIR__ . '/../../tests/fixtures/test_a_b.php';

        $instance = new \DbUtils();
        if ($is_valid_type) {
            $this->assertInstanceOf($type, $instance->getItemForTable($table));
        } else {
            $this->assertNull($instance->getItemForTable($table));
        }

        if ($is_valid_type) {
            $this->assertInstanceOf($type, getItemForTable($table));
        } else {
            $this->assertNull(getItemForTable($table));
        }
    }

    public static function getItemForItemtypeProvider(): iterable
    {
        foreach (self::dataTableType() as $test_case) {
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

    #[DataProvider('getItemForItemtypeProvider')]
    public function testGetItemForItemtype($itemtype, $is_valid, $expected_class)
    {
        // Pseudo plugin class for test
        require_once __DIR__ . '/../../tests/fixtures/another_test.php';
        require_once __DIR__ . '/../../tests/fixtures/pluginbarabstractstuff.php';
        require_once __DIR__ . '/../../tests/fixtures/pluginbarfoo.php';
        require_once __DIR__ . '/../../tests/fixtures/pluginfoobar.php';
        require_once __DIR__ . '/../../tests/fixtures/pluginfooservice.php';
        require_once __DIR__ . '/../../tests/fixtures/pluginfoo_search_item_filter.php';
        require_once __DIR__ . '/../../tests/fixtures/pluginfoo_item_filter.php';
        require_once __DIR__ . '/../../tests/fixtures/pluginfoo_search_a_b_c_d_e_f_g_bar.php';
        require_once __DIR__ . '/../../tests/fixtures/test_a_b.php';

        $instance = new \DbUtils();
        if ($is_valid) {
            $this->assertInstanceOf($expected_class, $instance->getItemForItemtype($itemtype));
        } else {
            $this->assertFalse($instance->getItemForItemtype($itemtype));
        }

        //keep testing old method from db.function
        if ($is_valid) {
            $this->assertInstanceOf($expected_class, getItemForItemtype($itemtype));
        } else {
            $this->assertFalse(getItemForItemtype($itemtype));
        }
    }


    public function testGetItemForItemtypeAbstract()
    {
        require_once FIXTURE_DIR . '/pluginbarabstractstuff.php';

        $instance = new \DbUtils();
        $instance->getItemForItemtype('CommonDevice');
        $this->hasPhpLogRecordThatContains(
            'Cannot instanciate "CommonDevice" as it is an abstract class.',
            LogLevel::WARNING
        );
    }

    public function testGetItemForItemtypeAbstract2()
    {
        require_once FIXTURE_DIR . '/pluginbarabstractstuff.php';

        $instance = new \DbUtils();
        $instance->getItemForItemtype('GlpiPlugin\Bar\AbstractStuff');
        $this->hasPhpLogRecordThatContains(
            'Cannot instanciate "GlpiPlugin\Bar\AbstractStuff" as it is an abstract class.',
            LogLevel::WARNING
        );
    }

    public static function itemtypeForeignKeyFieldProvider()
    {
        yield [
            'key'       => 'computers_id',
            'itemtype'  => \Computer::class,
        ];
        yield [
            'key'       => 'appliances_items_id',
            'itemtype'  => \Appliance_Item::class,
        ];
        yield [
            'key'       => 'dashboards_dashboards_id',
            'itemtype'  => Dashboard::class,
        ];
        yield [
            'key'       => 'plugin_tester_assets_foos_id',
            'itemtype'  => Foo::class,
        ];
        yield [
            'key'       => 'plugin_tester_mypseudopsr4classes_id',
            'itemtype'  => \PluginTesterMyPseudoPsr4Class::class,
        ];
        yield [
            'key'       => 'invalid_id',
            'itemtype'  => null,
        ];
    }

    #[DataProvider('itemtypeForeignKeyFieldProvider')]
    public function testGetItemtypeForForeignKeyField(string $key, ?string $itemtype)
    {
        $instance = new \DbUtils();
        if ($itemtype !== null) {
            $this->assertSame($itemtype, $instance->getItemtypeForForeignKeyField($key));
        } else {
            $this->assertNull($instance->getItemtypeForForeignKeyField($key));
        }

        if ($itemtype !== null) {
            $this->assertSame($itemtype, $instance->getItemtypeForForeignKeyField($key));
        } else {
            $this->assertNull($instance->getItemtypeForForeignKeyField($key));
        }
    }

    #[DataProvider('itemtypeForeignKeyFieldProvider')]
    public function testGetItemForForeignKeyField(string $key, ?string $itemtype)
    {
        $instance = new \DbUtils();
        if ($itemtype !== null) {
            $this->assertInstanceOf($itemtype, $instance->getItemForForeignKeyField($key));
        } else {
            $this->assertNull($instance->getItemForForeignKeyField($key));
        }

        if ($itemtype !== null) {
            $this->assertInstanceOf($itemtype, $instance->getItemForForeignKeyField($key));
        } else {
            $this->assertNull($instance->getItemForForeignKeyField($key));
        }
    }

    public static function dataPlural()
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
            ['metrics', 'metrics'],
        ];
    }

    #[DataProvider('dataPlural')]
    public function testGetPlural($singular, $plural)
    {
        $instance = new \DbUtils();
        $this->assertSame($plural, $instance->getPlural($singular));
        $this->assertSame(
            $plural,
            $instance->getPlural(
                $instance->getPlural(
                    $singular
                )
            )
        );

        //keep testing old method from db.function
        $this->assertSame($plural, getPlural($singular));
        $this->assertSame(
            $plural,
            getPlural(
                getPlural(
                    $singular
                )
            )
        );
    }

    #[DataProvider('dataPlural')]
    public function testGetSingular($singular, $plural)
    {
        $instance = new \DbUtils();
        $this->assertSame($singular, $instance->getSingular($plural));
        $this->assertSame(
            $singular,
            $instance->getSingular(
                $instance->getSingular(
                    $plural
                )
            )
        );

        //keep testing old method from db.function
        $this->assertSame($singular, getSingular($plural));
        $this->assertSame(
            $singular,
            getSingular(
                getSingular(
                    $plural
                )
            )
        );
    }


    public function testCountElementsInTable()
    {
        $instance = new \DbUtils();
        $this->assertGreaterThan(100, $instance->countElementsInTable('glpi_configs'));
        $this->assertGreaterThan(100, $instance->countElementsInTable(['glpi_configs', 'glpi_users']));
        $this->assertGreaterThan(100, $instance->countElementsInTable('glpi_configs', ['context' => 'core']));
        $this->assertSame(1, $instance->countElementsInTable('glpi_configs', ['context' => 'core', 'name' => 'version']));
        $this->assertSame(0, $instance->countElementsInTable('glpi_configs', ['context' => 'fakecontext']));

        //keep testing old method from db.function
        //the case of using an element that is not a table is not handle in the function :
        $this->assertGreaterThan(100, countElementsInTable('glpi_configs'));
        $this->assertGreaterThan(100, countElementsInTable(['glpi_configs', 'glpi_users']));
        $this->assertGreaterThan(100, countElementsInTable('glpi_configs', ['context' => 'core']));
        $this->assertSame(1, countElementsInTable('glpi_configs', ['context' => 'core', 'name' => 'version']));
        $this->assertSame(0, countElementsInTable('glpi_configs', ['context' => 'fakecontext']));
    }


    public function testCountDistinctElementsInTable()
    {
        $instance = new \DbUtils();
        $this->assertGreaterThan(0, $instance->countDistinctElementsInTable('glpi_configs', 'id'));
        $this->assertGreaterThan(0, $instance->countDistinctElementsInTable('glpi_configs', 'context'));
        $this->assertGreaterThanOrEqual(2, $instance->countDistinctElementsInTable('glpi_tickets', 'entities_id'));
        $this->assertSame(21, $instance->countDistinctElementsInTable('glpi_crontasks', 'itemtype', ['frequency' => '86400']));
        $this->assertSame(27, $instance->countDistinctElementsInTable('glpi_crontasks', 'id', ['frequency' => '86400']));
        $this->assertSame(1, $instance->countDistinctElementsInTable('glpi_configs', 'context', ['name' => 'version']));
        $this->assertSame(0, $instance->countDistinctElementsInTable('glpi_configs', 'id', ['context' => 'fakecontext']));

        //keep testing old method from db.function
        //the case of using an element that is not a table is not handle in the function :
        //testCountElementsInTable($table, $condition="")
        $this->assertGreaterThan(0, countDistinctElementsInTable('glpi_configs', 'id'));
        $this->assertGreaterThan(0, countDistinctElementsInTable('glpi_configs', 'context'));
        $this->assertSame(
            1,
            countDistinctElementsInTable(
                'glpi_configs',
                'context',
                ['name' => 'version']
            )
        );
        $this->assertSame(
            0,
            countDistinctElementsInTable(
                'glpi_configs',
                'id',
                ['context' => 'fakecontext']
            )
        );
    }

    public static function dataCountMyEntities()
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

    #[DataProvider('dataCountMyEntities')]
    public function testCountElementsInTableForMyEntities(
        $entity,
        $recursive,
        $table,
        $condition,
        $count
    ) {
        $this->login();
        $this->setEntity($entity, $recursive);

        $instance = new \DbUtils();
        $this->assertSame($count, $instance->countElementsInTableForMyEntities($table, $condition));

        //keep testing old method from db.function
        $this->assertSame($count, countElementsInTableForMyEntities($table, $condition));
    }

    public static function dataCountEntities()
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


    #[DataProvider('dataCountEntities')]
    public function testCountElementsInTableForEntity(
        $entity,
        $table,
        $condition,
        $count
    ) {
        $eid = getItemByTypeName('Entity', $entity, true);

        $instance = new \DbUtils();
        $this->assertSame($count, $instance->countElementsInTableForEntity($table, $eid, $condition));

        //keep testing old method from db.function
        $this->assertSame($count, countElementsInTableForEntity($table, $eid, $condition));
    }

    public function testGetAllDataFromTable()
    {
        $instance = new \DbUtils();
        $data = $instance->getAllDataFromTable('glpi_configs');
        $this->assertGreaterThan(100, count($data));

        foreach ($data as $key => $array) {
            $this->assertSame($key, $array['id']);
        }

        $instance = new \DbUtils();
        $this->assertCount(1, $instance->getAllDataFromTable('glpi_configs', ['context' => 'core', 'name' => 'version']));
        $data = $instance->getAllDataFromTable('glpi_configs', ['ORDER' => 'name']);
        $this->assertNotEmpty($data);

        $previousArrayName = "";
        foreach ($data as $key => $array) {
            $this->assertTrue($previousArrayName <= $previousArrayName = $array['name']);
        }

        //TODO: test with cache === true

        //keep testing old method from db.function
        $data = getAllDataFromTable('glpi_configs');
        $this->assertGreaterThan(100, count($data));
        foreach ($data as $key => $array) {
            $this->assertSame($key, $array['id']);
        }

        $data = getAllDataFromTable('glpi_configs', ['context' => 'core', 'name' => 'version']);
        $this->assertCount(1, $data);

        $data = getAllDataFromTable('glpi_configs', ['ORDER' => 'name']);
        $this->assertNotEmpty($data);
        $previousArrayName = "";
        foreach ($data as $key => $array) {
            $this->assertTrue($previousArrayName <= $previousArrayName = $array['name']);
        }
    }

    public function testIsIndex()
    {
        $instance = new \DbUtils();
        $this->assertFalse($instance->isIndex('glpi_configs', 'fakeField'));
        $this->assertTrue($instance->isIndex('glpi_configs', 'name'));
        $this->assertFalse($instance->isIndex('glpi_configs', 'value'));
        $this->assertTrue($instance->isIndex('glpi_users', 'locations_id'));
        $this->assertTrue($instance->isIndex('glpi_users', 'unicityloginauth'));

        $this->assertFalse($instance->isIndex('fakeTable', 'id'));
        $this->hasPhpLogRecordThatContains('Table fakeTable does not exists', LogLevel::WARNING);
    }

    public function testProceduralIsIndex()
    {
        //keep testing old method from db.function
        $this->assertFalse(isIndex('glpi_configs', 'fakeField'));
        $this->assertTrue(isIndex('glpi_configs', 'name'));
        $this->assertFalse(isIndex('glpi_configs', 'value'));
        $this->assertTrue(isIndex('glpi_users', 'locations_id'));
        $this->assertTrue(isIndex('glpi_users', 'unicityloginauth'));

        $this->assertFalse(isIndex('fakeTable', 'id'));
        $this->hasPhpLogRecordThatContains('Table fakeTable does not exists', LogLevel::WARNING);
    }

    public function testGetEntityRestrict()
    {
        $this->login();
        $instance = new \DbUtils();

        $root = getItemByTypeName('Entity', '_test_root_entity', true);
        $child1 = getItemByTypeName('Entity', '_test_child_1', true);
        $child2 = getItemByTypeName('Entity', '_test_child_2', true);
        $child3 = getItemByTypeName('Entity', '_test_child_3', true);

        // See all, really all
        $_SESSION['glpishowallentities'] = 1; // will be restored by setEntity call

        $this->assertEmpty($instance->getEntitiesRestrictRequest('AND', 'glpi_computers'));

        $it = new \DBmysqlIterator(null);

        $it->execute(['FROM' => 'glpi_computers', 'WHERE' => $instance->getEntitiesRestrictCriteria('glpi_computers')]);
        $this->assertSame('SELECT * FROM `glpi_computers` WHERE true', $it->getSql());

        //keep testing old method from db.function
        $this->assertEmpty(getEntitiesRestrictRequest('AND', 'glpi_computers'));
        $it->execute(['FROM' => 'glpi_computers', 'WHERE' => getEntitiesRestrictCriteria('glpi_computers')]);
        $this->assertSame('SELECT * FROM `glpi_computers` WHERE (true)', $it->getSql());

        // See all
        $this->setEntity('_test_root_entity', true);

        $this->assertSame(
            "WHERE ( `glpi_computers`.`entities_id` IN ('$root', '$child1', '$child2', '$child3')  ) ",
            $instance->getEntitiesRestrictRequest('WHERE', 'glpi_computers')
        );
        $it->execute(['FROM' => 'glpi_computers', 'WHERE' => $instance->getEntitiesRestrictCriteria('glpi_computers')]);
        $this->assertSame(
            "SELECT * FROM `glpi_computers` WHERE `glpi_computers`.`entities_id` IN ('$root', '$child1', '$child2', '$child3')",
            $it->getSql()
        );

        //keep testing old method from db.function
        $this->assertSame(
            "WHERE ( `glpi_computers`.`entities_id` IN ('$root', '$child1', '$child2', '$child3')  ) ",
            getEntitiesRestrictRequest('WHERE', 'glpi_computers')
        );
        $it->execute(['FROM' => 'glpi_computers', 'WHERE' => getEntitiesRestrictCriteria('glpi_computers')]);
        $this->assertSame(
            "SELECT * FROM `glpi_computers` WHERE (`glpi_computers`.`entities_id` IN ('$root', '$child1', '$child2', '$child3'))",
            $it->getSql()
        );

        // Root entity
        $this->setEntity('_test_root_entity', false);

        $this->assertSame(
            "WHERE ( `glpi_computers`.`entities_id` IN ('$root')  ) ",
            $instance->getEntitiesRestrictRequest('WHERE', 'glpi_computers')
        );
        $it->execute(['FROM' => 'glpi_computers', 'WHERE' => $instance->getEntitiesRestrictCriteria('glpi_computers')]);
        $this->assertSame(
            "SELECT * FROM `glpi_computers` WHERE `glpi_computers`.`entities_id` IN ('$root')",
            $it->getSql()
        );

        //keep testing old method from db.function
        $this->assertSame(
            "WHERE ( `glpi_computers`.`entities_id` IN ('$root')  ) ",
            getEntitiesRestrictRequest('WHERE', 'glpi_computers')
        );
        $it->execute(['FROM' => 'glpi_computers', 'WHERE' => getEntitiesRestrictCriteria('glpi_computers')]);
        $this->assertSame(
            "SELECT * FROM `glpi_computers` WHERE (`glpi_computers`.`entities_id` IN ('$root'))",
            $it->getSql()
        );

        // Child
        $this->setEntity('_test_child_1', false);

        $this->assertSame(
            "WHERE ( `glpi_computers`.`entities_id` IN ('$child1')  ) ",
            $instance->getEntitiesRestrictRequest('WHERE', 'glpi_computers')
        );
        $it->execute(['FROM' => 'glpi_computers', 'WHERE' => $instance->getEntitiesRestrictCriteria('glpi_computers')]);
        $this->assertSame(
            "SELECT * FROM `glpi_computers` WHERE `glpi_computers`.`entities_id` IN ('$child1')",
            $it->getSql()
        );

        //keep testing old method from db.function
        $this->assertSame(
            "WHERE ( `glpi_computers`.`entities_id` IN ('$child1')  ) ",
            getEntitiesRestrictRequest('WHERE', 'glpi_computers')
        );
        $it->execute(['FROM' => 'glpi_computers', 'WHERE' => getEntitiesRestrictCriteria('glpi_computers')]);
        $this->assertSame(
            "SELECT * FROM `glpi_computers` WHERE (`glpi_computers`.`entities_id` IN ('$child1'))",
            $it->getSql()
        );

        // Child without table
        $this->assertSame(
            "WHERE ( `entities_id` IN ('$child1')  ) ",
            $instance->getEntitiesRestrictRequest('WHERE')
        );
        $it->execute(['FROM' => 'glpi_computers', 'WHERE' => $instance->getEntitiesRestrictCriteria()]);
        $this->assertSame(
            "SELECT * FROM `glpi_computers` WHERE `entities_id` IN ('$child1')",
            $it->getSql()
        );

        //keep testing old method from db.function
        $this->assertSame(
            "WHERE ( `entities_id` IN ('$child1')  ) ",
            getEntitiesRestrictRequest('WHERE')
        );
        $it->execute(['FROM' => 'glpi_computers', 'WHERE' => getEntitiesRestrictCriteria()]);
        $this->assertSame(
            "SELECT * FROM `glpi_computers` WHERE (`entities_id` IN ('$child1'))",
            $it->getSql()
        );

        // Child + parent
        $this->setEntity('_test_child_2', false);

        $this->assertSame(
            "WHERE ( `glpi_computers`.`entities_id` IN ('$child2')  OR (`glpi_computers`.`is_recursive`='1' AND `glpi_computers`.`entities_id` IN (0, $root)) ) ",
            $instance->getEntitiesRestrictRequest('WHERE', 'glpi_computers', '', '', true)
        );
        $it->execute(['FROM' => 'glpi_computers', 'WHERE' => $instance->getEntitiesRestrictCriteria('glpi_computers', '', '', true)]);
        $this->assertSame(
            "SELECT * FROM `glpi_computers` WHERE (`glpi_computers`.`entities_id` IN ('$child2') OR (`glpi_computers`.`is_recursive` = '1' AND `glpi_computers`.`entities_id` IN ('0', '$root')))",
            $it->getSql()
        );

        //keep testing old method from db.function
        $this->assertSame(
            "WHERE ( `glpi_computers`.`entities_id` IN ('$child2')  OR (`glpi_computers`.`is_recursive`='1' AND `glpi_computers`.`entities_id` IN (0, $root)) ) ",
            getEntitiesRestrictRequest('WHERE', 'glpi_computers', '', '', true)
        );
        $it->execute(['FROM' => 'glpi_computers', 'WHERE' => getEntitiesRestrictCriteria('glpi_computers', '', '', true)]);
        $this->assertSame(
            "SELECT * FROM `glpi_computers` WHERE ((`glpi_computers`.`entities_id` IN ('$child2') OR (`glpi_computers`.`is_recursive` = '1' AND `glpi_computers`.`entities_id` IN ('0', '$root'))))",
            $it->getSql()
        );

        //Child + parent on glpi_entities
        $it->execute(['FROM' => 'glpi_entities', 'WHERE' => $instance->getEntitiesRestrictCriteria('glpi_entities', '', '', true)]);
        $this->assertSame(
            "SELECT * FROM `glpi_entities` WHERE (`glpi_entities`.`id` IN ('$child2', '0', '$root'))",
            $it->getSql()
        );

        //keep testing old method from db.function
        $it->execute(['FROM' => 'glpi_entities', 'WHERE' => getEntitiesRestrictCriteria('glpi_entities', '', '', true)]);
        $this->assertSame(
            "SELECT * FROM `glpi_entities` WHERE ((`glpi_entities`.`id` IN ('$child2', '0', '$root')))",
            $it->getSql()
        );

        //Child + parent -- automatic recusrivity detection
        $it->execute(['FROM' => 'glpi_computers', 'WHERE' => $instance->getEntitiesRestrictCriteria('glpi_computers', '', '', 'auto')]);
        $this->assertSame(
            "SELECT * FROM `glpi_computers` WHERE (`glpi_computers`.`entities_id` IN ('$child2') OR (`glpi_computers`.`is_recursive` = '1' AND `glpi_computers`.`entities_id` IN ('0', '$root')))",
            $it->getSql()
        );

        //keep testing old method from db.function
        $it->execute(['FROM' => 'glpi_computers', 'WHERE' => getEntitiesRestrictCriteria('glpi_computers', '', '', 'auto')]);
        $this->assertSame(
            "SELECT * FROM `glpi_computers` WHERE ((`glpi_computers`.`entities_id` IN ('$child2') OR (`glpi_computers`.`is_recursive` = '1' AND `glpi_computers`.`entities_id` IN ('0', '$root'))))",
            $it->getSql()
        );

        // Child + parent without table
        $this->assertSame(
            "WHERE ( `entities_id` IN ('$child2')  OR (`is_recursive`='1' AND `entities_id` IN (0, $root)) ) ",
            $instance->getEntitiesRestrictRequest('WHERE', '', '', '', true)
        );
        $it->execute(['FROM' => 'glpi_computers', 'WHERE' => $instance->getEntitiesRestrictCriteria('', '', '', true)]);
        $this->assertSame(
            "SELECT * FROM `glpi_computers` WHERE (`entities_id` IN ('$child2') OR (`is_recursive` = '1' AND `entities_id` IN ('0', '$root')))",
            $it->getSql()
        );

        $it->execute(['FROM' => 'glpi_entities', 'WHERE' => $instance->getEntitiesRestrictCriteria('glpi_entities', '', $child1, true)]);
        $this->assertSame(
            "SELECT * FROM `glpi_entities` WHERE (`glpi_entities`.`id` IN ('$child1', '0', '$root'))",
            $it->getSql()
        );

        $it->execute(['FROM' => 'glpi_entities', 'WHERE' => $instance->getEntitiesRestrictCriteria('glpi_entities', '', 9, true)]);
        $this->assertSame(
            'SELECT * FROM `glpi_entities` WHERE `glpi_entities`.`id` = \'9\'',
            $it->getSql()
        );

        // Entity value is empty array
        $it->execute('glpi_entities', $instance->getEntitiesRestrictCriteria('glpi_entities', '', [], true));
        $this->hasPhpLogRecordThatContains(
            'User Deprecated: The `DBmysqlIterator::execute()` method signature changed. Its previous signature is deprecated.',
            LogLevel::INFO
        );
        $this->assertSame(0, $it->count());
        $this->assertSame(
            'SELECT * FROM `glpi_entities` WHERE false',
            $it->getSql()
        );

        //keep testing old method from db.function
        $this->assertSame(
            "WHERE ( `entities_id` IN ('$child2')  OR (`is_recursive`='1' AND `entities_id` IN (0, $root)) ) ",
            getEntitiesRestrictRequest('WHERE', '', '', '', true)
        );
        $it->execute(['FROM' => 'glpi_computers', 'WHERE' => getEntitiesRestrictCriteria('', '', '', true)]);
        $this->assertSame(
            "SELECT * FROM `glpi_computers` WHERE ((`entities_id` IN ('$child2') OR (`is_recursive` = '1' AND `entities_id` IN ('0', '$root'))))",
            $it->getSql()
        );

        $it->execute(['FROM' => 'glpi_entities', 'WHERE' => getEntitiesRestrictCriteria('glpi_entities', '', $child1, true)]);
        $this->assertSame(
            "SELECT * FROM `glpi_entities` WHERE ((`glpi_entities`.`id` IN ('$child1', '0', '$root')))",
            $it->getSql()
        );

        $it->execute(['FROM' => 'glpi_entities', 'WHERE' => getEntitiesRestrictCriteria('glpi_entities', '', 9, true)]);
        $this->assertSame(
            'SELECT * FROM `glpi_entities` WHERE (`glpi_entities`.`id` = \'9\')',
            $it->getSql()
        );

        // Entity value is empty array
        $it->execute('glpi_entities', getEntitiesRestrictCriteria('glpi_entities', '', [], true));
        $this->hasPhpLogRecordThatContains(
            'User Deprecated: The `DBmysqlIterator::execute()` method signature changed. Its previous signature is deprecated.',
            LogLevel::INFO
        );
        $this->assertSame(0, $it->count());
        $this->assertSame(
            'SELECT * FROM `glpi_entities` WHERE (false)',
            $it->getSql()
        );
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
            $this->assertFalse($GLPI_CACHE->has($ckey_ent0));
        } elseif ($cache === true && $hit === true) {
            $this->assertSame($expected, $GLPI_CACHE->get($ckey_ent0));
        }

        $ancestors = getAncestorsOf('glpi_entities', $ent0);
        $this->assertSame($expected, $ancestors);

        if ($cache === true && $hit === false) {
            $this->assertSame($expected, $GLPI_CACHE->get($ckey_ent0));
        }

        //test on ent1
        $expected = [0 => 0, $ent0 => $ent0];
        if ($cache === true && $hit === false) {
            $this->assertFalse($GLPI_CACHE->has($ckey_ent1));
        } elseif ($cache === true && $hit === true) {
            $this->assertSame($expected, $GLPI_CACHE->get($ckey_ent1));
        }

        $ancestors = getAncestorsOf('glpi_entities', $ent1);
        $this->assertSame($expected, $ancestors);

        if ($cache === true && $hit === false) {
            $this->assertSame($expected, $GLPI_CACHE->get($ckey_ent1));
        }

        //test on ent2
        $expected = [0 => 0, $ent0 => $ent0];
        if ($cache === true && $hit === false) {
            $this->assertFalse($GLPI_CACHE->has($ckey_ent2));
        } elseif ($cache === true && $hit === true) {
            $this->assertSame($expected, $GLPI_CACHE->get($ckey_ent2));
        }

        $ancestors = getAncestorsOf('glpi_entities', $ent2);
        $this->assertSame($expected, $ancestors);

        if ($cache === true && $hit === false) {
            $this->assertSame($expected, $GLPI_CACHE->get($ckey_ent2));
        }

        //test with new sub entity
        //Cache tests:
        //Cache is updated on entity creation; so even if we do not expect $hit; we got it.
        $entity = new \Entity();
        if ($entity->getFromDBByCrit(['name' => 'Sub child entity'])) {
            $new_id = $entity->getID();
        } else {
            $new_id = $entity->add([
                'name'         => 'Sub child entity',
                'entities_id'  => $ent1,
            ]);
            $this->assertGreaterThan(0, $new_id);
        }
        $ckey_new_id = 'ancestors_cache_glpi_entities_' . $new_id;

        $expected = [0 => 0, $ent0 => $ent0, $ent1 => $ent1];
        if ($cache === true) {
            $this->assertSame($expected, $GLPI_CACHE->get($ckey_new_id));
        }

        $ancestors = getAncestorsOf('glpi_entities', $new_id);
        $this->assertSame($expected, $ancestors);

        if ($cache === true && $hit === false) {
            $this->assertSame($expected, $GLPI_CACHE->get($ckey_new_id));
        }

        //test with another new sub entity
        $entity = new \Entity();
        if ($entity->getFromDBByCrit(['name' => 'Sub child entity 2'])) {
            $new_id2 = $entity->getID();
        } else {
            $new_id2 = $entity->add([
                'name'         => 'Sub child entity 2',
                'entities_id'  => $ent2,
            ]);
            $this->assertGreaterThan(0, $new_id2);
        }
        $ckey_new_id2 = 'ancestors_cache_glpi_entities_' . $new_id2;

        $expected = [0 => 0, $ent0 => $ent0, $ent2 => $ent2];
        if ($cache === true) {
            $this->assertSame($expected, $GLPI_CACHE->get($ckey_new_id2));
        }

        $ancestors = getAncestorsOf('glpi_entities', $new_id2);
        $this->assertSame($expected, $ancestors);

        if ($cache === true && $hit === false) {
            $this->assertSame($expected, $GLPI_CACHE->get($ckey_new_id2));
        }

        // test on multiple entities
        // getAncestorsOf was already called on $new_id and $new_id2 separately, so cache is already populated since we don't cache ancestors of multiple entities together anymore.
        // Ex: getAncestorsOf('glpi_entities', [$new_id, $new_id2]) will populate ancestors_cache_glpi_entities_$new_id and ancestors_cache_glpi_entities_$new_id2
        // but not ancestors_cache_glpi_entities_ . md5(implode('|', [$new_id, $new_id2]))
        // We will ignore the $cache and $hit parameters here and just ensure the combined result is correct.
        $expected = [0 => 0, $ent0 => $ent0, $ent1 => $ent1, $ent2 => $ent2];
        $ancestors = getAncestorsOf('glpi_entities', [$new_id, $new_id2]);
        $this->assertSame($expected, $ancestors);
    }

    public function testGetAncestorsOf()
    {
        global $DB;
        $this->login();
        //ensure db cache is unset
        $DB->update('glpi_entities', ['ancestors_cache' => null], [new QueryExpression('true')]);
        $this->runGetAncestorsOf();

        $this->assertGreaterThan(
            0,
            countElementsInTable(
                'glpi_entities',
                [
                    'NOT' => ['ancestors_cache' => null],
                ]
            )
        );
        //run a second time: db cache must be set
        $this->runGetAncestorsOf();
    }

    #[Group('cache')]
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
        $ent3 = getItemByTypeName('Entity', '_test_child_3', true);
        $instance = new \DbUtils();

        //Cache tests:
        //- if $cache === 0; we do not expect anything,
        //- if $cache === 1; we expect cache to be empty before call, and populated after
        //- if $hit   === 1; we expect cache to be populated

        $ckey_ent0 = 'sons_cache_glpi_entities_' . $ent0;
        $ckey_ent1 = 'sons_cache_glpi_entities_' . $ent1;
        $ckey_ent2 = 'sons_cache_glpi_entities_' . $ent2;
        $ckey_ent3 = 'sons_cache_glpi_entities_' . $ent3;

        //test on ent0
        $expected = [$ent0 => $ent0, $ent1 => $ent1, $ent2 => $ent2, $ent3 => $ent3];
        if ($cache === true && $hit === false) {
            $this->assertFalse($GLPI_CACHE->has($ckey_ent0));
        } elseif ($cache === true && $hit === true) {
            $this->assertSame($expected, $GLPI_CACHE->get($ckey_ent0));
        }

        $sons = $instance->getSonsOf('glpi_entities', $ent0);
        $this->assertSame($expected, $sons);

        if ($cache === true && $hit === false) {
            $this->assertSame($expected, $GLPI_CACHE->get($ckey_ent0));
        }

        //test on ent1
        $expected = [$ent1 => $ent1];
        if ($cache === true && $hit === false) {
            $this->assertFalse($GLPI_CACHE->has($ckey_ent1));
        } elseif ($cache === true && $hit === true) {
            $this->assertSame($expected, $GLPI_CACHE->get($ckey_ent1));
        }

        $sons = $instance->getSonsOf('glpi_entities', $ent1);
        $this->assertSame($expected, $sons);

        if ($cache === true && $hit === false) {
            $this->assertSame($expected, $GLPI_CACHE->get($ckey_ent1));
        }

        //test on ent2
        $expected = [$ent2 => $ent2];
        if ($cache === true && $hit === false) {
            $this->assertFalse($GLPI_CACHE->has($ckey_ent2));
        } elseif ($cache === true && $hit === true) {
            $this->assertSame($expected, $GLPI_CACHE->get($ckey_ent2));
        }

        $sons = $instance->getSonsOf('glpi_entities', $ent2);
        $this->assertSame($expected, $sons);

        if ($cache === true && $hit === false) {
            $this->assertSame($expected, $GLPI_CACHE->get($ckey_ent2));
        }

        //test with new sub entity
        //Cache tests:
        //Cache is updated on entity creation; so even if we do not expect $hit; we got it.
        $entity = new \Entity();
        if ($entity->getFromDBByCrit(['name' => 'Sub child entity'])) {
            $new_id = $entity->getID();
        } else {
            $new_id = (int) $entity->add([
                'name'         => 'Sub child entity',
                'entities_id'  => $ent1,
            ]);
            $this->assertGreaterThan(0, $new_id);
        }

        $expected = [$ent1 => $ent1, $new_id => $new_id];
        if ($cache === true) {
            $this->assertSame($expected, $GLPI_CACHE->get($ckey_ent1));
        }

        $sons = $instance->getSonsOf('glpi_entities', $ent1);
        $this->assertSame($expected, $sons);

        if ($cache === true && $hit === false) {
            $this->assertSame($expected, $GLPI_CACHE->get($ckey_ent1));
        }

        //test with another new sub entity
        $entity = new \Entity();
        if ($entity->getFromDBByCrit(['name' => 'Sub child entity 2'])) {
            $new_id2 = $entity->getID();
        } else {
            $new_id2 = (int) $entity->add([
                'name'         => 'Sub child entity 2',
                'entities_id'  => $ent1,
            ]);
            $this->assertGreaterThan(0, $new_id2);
        }

        $expected = [$ent1 => $ent1, $new_id => $new_id, $new_id2 => $new_id2];
        if ($cache === true) {
            $this->assertSame($expected, $GLPI_CACHE->get($ckey_ent1));
        }

        $sons = $instance->getSonsOf('glpi_entities', $ent1);
        $this->assertSame($expected, $sons);

        if ($cache === true && $hit === false) {
            $this->assertSame($expected, $GLPI_CACHE->get($ckey_ent1));
        }

        //drop sub entity
        $expected = [$ent1 => $ent1, $new_id2 => $new_id2];
        $this->assertTrue($entity->delete(['id' => $new_id], true));
        if ($cache === true) {
            $this->assertSame(null, $GLPI_CACHE->get($ckey_ent1)); // cache has been cleared
        }
        $sons = $instance->getSonsOf('glpi_entities', $ent1);
        $this->assertSame($expected, $sons);
        if ($cache === true) {
            $this->assertSame($expected, $GLPI_CACHE->get($ckey_ent1));
        }

        $expected = [$ent1 => $ent1];
        $this->assertTrue($entity->delete(['id' => $new_id2], true));
        if ($cache === true) {
            $this->assertSame(null, $GLPI_CACHE->get($ckey_ent1)); // cache has been cleared
        }
        $sons = $instance->getSonsOf('glpi_entities', $ent1);
        $this->assertSame($expected, $sons);
        if ($cache === true) {
            $this->assertSame($expected, $GLPI_CACHE->get($ckey_ent1));
        }

        $expected = [$ent0 => $ent0, $ent1 => $ent1, $ent2 => $ent2, $ent3 => $ent3];
        $sons = $instance->getSonsOf('glpi_entities', $ent0);
        $this->assertSame($expected, $sons);
        if ($cache === true) {
            $this->assertSame($expected, $GLPI_CACHE->get($ckey_ent0));
        }
    }

    public function testGetSonsOf()
    {
        global $DB;
        $instance = new \DbUtils();
        $this->login();
        //ensure db cache is unset
        $DB->update('glpi_entities', ['sons_cache' => null], [new QueryExpression('true')]);
        $this->runGetSonsOf();

        $this->assertGreaterThan(
            0,
            $instance->countElementsInTable(
                'glpi_entities',
                [
                    'NOT' => ['sons_cache' => null],
                ]
            )
        );
        //run a second time: db cache must be set
        $this->runGetSonsOf();
    }

    #[Group('cache')]
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

        $instance = new \DbUtils();

        $mapping = $instance->getDbRelations();

        // Check that target fields exists in database
        foreach ($mapping as $source_table => $relations) {
            $this->assertTrue(
                $DB->tableExists($source_table),
                sprintf('Invalid table "%s" in relation mapping.', $source_table)
            );

            foreach ($relations as $target_table_key => $target_fields) {
                $target_table = preg_replace('/^_/', '', $target_table_key);

                $this->assertTrue(
                    $DB->tableExists($target_table),
                    sprintf('Invalid table "%s" in "%s" mapping.', $target_table, $source_table)
                );

                $this->assertIsArray($target_fields);

                $fields_to_check = [];
                foreach ($target_fields as $target_field) {
                    if (is_array($target_field)) {
                        // Polymorphic relation
                        $this->assertCount(2, $target_field); // has only `itemtype*` and `items_id*` fields
                        if ($target_table === 'glpi_ipaddresses') {
                            $this->assertContains('mainitemtype', $target_field);
                            $this->assertContains('mainitems_id', $target_field);
                        } else {
                            $this->assertSame(1, count(preg_grep('/^itemtype/', $target_field)));
                            $this->assertSame(1, count(preg_grep('/^items_id/', $target_field)));
                        }
                        $fields_to_check = array_merge($fields_to_check, $target_field);
                    } else {
                        // Ensure polymorphic relations are correctly declared in an array with both fields names.
                        $msg = sprintf('Invalid table field "%s.%s" in "%s" mapping.', $target_table, $target_field, $source_table);
                        $this->assertDoesNotMatchRegularExpression('/^itemtype/', $target_field, $msg);
                        $this->assertDoesNotMatchRegularExpression('/^items_id/', $target_field, $msg);

                        $fields_to_check[] = $target_field;
                    }
                }
                foreach ($fields_to_check as $field_to_check) {
                    $this->assertTrue(
                        $DB->fieldExists($target_table, $field_to_check),
                        sprintf('Invalid table field "%s.%s" in "%s" mapping.', $target_table, $field_to_check, $source_table)
                    );
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
            $msg .= 'Following relations are missing (did you forget to declare them in inc/relation.constant.php ? ):'
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
        $this->assertTrue(
            empty($forbiddenly_prefixed_relations) && empty($missing_relations) && empty($duplicated_relations) && empty($mixed_relations),
            $msg
        );
    }

    /**
     * Test getDateCriteria
     *
     * @return void
     */
    public function testGetDateCriteria()
    {
        $instance = new \DbUtils();

        $this->assertSame(
            [],
            $instance->getDateCriteria('date', null, null)
        );

        $this->assertSame(
            [],
            $instance->getDateCriteria('date', '', '')
        );

        $this->assertSame(
            [['date' => ['>=', '2018-11-09']]],
            $instance->getDateCriteria('date', '2018-11-09', null)
        );

        $result = $instance->getDateCriteria('date', null, '2018-11-09');
        $this->assertCount(1, $result);

        $this->assertCount(2, $result[0]['date']);
        $this->assertSame('<=', $result[0]['date'][0]);
        $this->assertInstanceOf('\Glpi\DBAL\QueryExpression', $result[0]['date'][1]);

        $this->assertSame(
            "DATE_ADD('2018-11-09', INTERVAL 1 DAY)",
            $result[0]['date'][1]->getValue()
        );

        $result = $instance->getDateCriteria('date', '2018-11-08', '2018-11-09');
        $this->assertCount(2, $result);

        $this->assertSame(['date' => ['>=', '2018-11-08']], $result[0]);
        $this->assertCount(2, $result[1]['date']);
        $this->assertSame('<=', $result[1]['date'][0]);
        $this->assertInstanceOf('\Glpi\DBAL\QueryExpression', $result[1]['date'][1]);

        $this->assertSame(
            "DATE_ADD('2018-11-09', INTERVAL 1 DAY)",
            $result[1]['date'][1]->getValue()
        );
    }

    public function testGetDateCriteriaError1()
    {
        $instance = new \DbUtils();
        $instance->getDateCriteria('date', '2023-02-19\', INTERVAL 1 DAY)))))', null);
        $this->hasPhpLogRecordThatContains(
            'Invalid "2023-02-19\', INTERVAL 1 DAY)))))" date value.',
            LogLevel::WARNING
        );
    }

    public function testGetDateCriteriaError2()
    {
        $instance = new \DbUtils();
        $instance->getDateCriteria('date', null, '2023-02-19\', INTERVAL 1 DAY)))))');
        $this->hasPhpLogRecordThatContains(
            'Invalid "2023-02-19\', INTERVAL 1 DAY)))))" date value.',
            LogLevel::WARNING
        );
    }

    public static function autoNameProvider()
    {
        $test_child_1 = getItemByTypeName('Entity', '_test_child_1', true);

        return [
            //will return name without changes
            [
                //not a template
                'name'         => 'Computer 1',
                'field'        => 'name',
                'is_template'  => false,
                'itemtype'     => 'Computer',
                'entities_id'  => -1, //default
                'expected'     => 'Computer 1',
            ], [
                // not existing on entity, with multibyte strings
                'name'         => '<自動名稱測試_##>',
                'field'       => 'name',
                'is_template'  => true,
                'itemtype'     => 'Computer',
                'entities_id'  => 0,
                'expected'     => '自動名稱測試_01',
            ], [
                // not existing on entity, with multibyte strings
                'name'         => '<自動名稱—####—測試>',
                'field'       => 'name',
                'is_template'  => true,
                'itemtype'     => 'Computer',
                'entities_id'  => 0,
                'expected'     => '自動名稱—0001—測試',
            ], [
                // existing on entity
                'name'         => '<_test_pc##>',
                'field'       => 'name',
                'is_template'  => true,
                'itemtype'     => 'Computer',
                'entities_id'  => $test_child_1,
                'expected'     => '_test_pc14',
            ], [
                // not existing on entity, not sanitized, and containing a special char
                'name'         => '<pc_>_##>',
                'field'       => 'name',
                'is_template'  => true,
                'itemtype'     => 'Computer',
                'entities_id'  => $test_child_1,
                'expected'     => 'pc_>_01',
            ],
        ];
    }

    #[DataProvider('autoNameProvider')]
    public function testAutoName($name, $field, $is_template, $itemtype, $entities_id, $expected)
    {
        $instance = new \DbUtils();

        $autoname = $instance->autoName(
            $name,
            $field,
            $is_template,
            $itemtype,
            $entities_id
        );
        $this->assertSame($expected, $autoname);
    }


    /**
     * Data provider for self::testGetItemtypeWithFixedCase().
     */
    public static function fixItemtypeCaseProvider()
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
            [
                'itemtype' => 'glpiplugin\\foo\\relation_item',
                'expected' => 'GlpiPlugin\\Foo\\Relation_Item',
            ],
            [
                'itemtype' => 'PluginBarFooitem',
                'expected' => 'PluginBarFooItem',
            ],
            [
                'itemtype' => 'GlpiPluGin\\Bar\\Namespacedfoo',
                'expected' => 'GlpiPlugin\\Bar\\NamespacedFoo',
            ],
            [
                'itemtype' => 'glpiplugin\\bar\\models\\bar\\foo_item',
                'expected' => 'GlpiPlugin\\Bar\\Models\\Bar\\Foo_Item',
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
            [
                'itemtype' => 'GlpiPlugin\\Bar\\NamespacedFoo',
                'expected' => 'GlpiPlugin\\Bar\\NamespacedFoo',
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

    #[DataProvider('fixItemtypeCaseProvider')]
    public function testGetItemtypeWithFixedCase($itemtype, $expected)
    {
        $name = 'glpi' . mt_rand();
        vfsStream::setup(
            $name,
            null,
            [
                'src' => [
                    'Glpi' => [
                        'Application' => [
                            'Console' => [
                                'MyCommand.php' => '',
                            ],
                        ],
                        'Something' => [
                            'Item_Filter.php' => '',
                        ],
                        'NamespacedClass.php' => '',
                    ],
                    'MyClass.php' => '',
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
                            'Relation_Item.php' => '',
                        ],
                    ],
                ],
                'marketplace' => [
                    'bar' => [
                        'src' => [
                            'Models' => [
                                'Bar' => [
                                    'Foo_Item.php' => '',
                                ],
                            ],
                            'NamespacedFoo.php' => '',
                            'PluginBarFooItem.php' => '',
                        ],
                    ],
                ],
            ]
        );
        $instance = new \DbUtils();
        $result = $instance->fixItemtypeCase($itemtype, vfsStream::url($name), [vfsStream::url("$name/plugins"), vfsStream::url("$name/marketplace")]);
        $this->assertEquals($expected, $result);
    }
}
