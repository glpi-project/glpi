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

namespace tests\units\Glpi\Migration;

use Contract_Item;
use DbTestCase;
use Domain_Item;
use DropdownTranslation;
use FieldUnicity;
use Glpi\Asset\AssetDefinition;
use Glpi\Asset\Capacity\AllowedInGlobalSearchCapacity;
use Glpi\Asset\Capacity\HasContractsCapacity;
use Glpi\Asset\Capacity\HasDevicesCapacity;
use Glpi\Asset\Capacity\HasDocumentsCapacity;
use Glpi\Asset\Capacity\HasHistoryCapacity;
use Glpi\Asset\Capacity\HasInfocomCapacity;
use Glpi\Asset\Capacity\HasNetworkPortCapacity;
use Glpi\Asset\Capacity\HasNotepadCapacity;
use Glpi\Asset\Capacity\IsProjectAssetCapacity;
use Glpi\Asset\Capacity\IsReservableCapacity;
use Glpi\DBAL\QueryExpression;
use Glpi\Dropdown\DropdownDefinition;
use Glpi\Migration\GenericobjectPluginMigration;
use Glpi\Migration\PluginMigrationResult;
use Group_Item;
use Infocom;
use Profile;

class GenericobjectPluginMigrationTest extends DbTestCase
{
    public static function setUpBeforeClass(): void
    {
        global $DB;

        parent::setUpBeforeClass();

        // Load it once for the whole class.
        // This will create new tables, therefore cannot be executed inside the test DB transaction.
        $queries = $DB->getQueriesFromFile(sprintf('%s/tests/fixtures/genericobject-migration/genericobject-db.sql', GLPI_ROOT));
        foreach ($queries as $query) {
            $DB->doQuery($query);
        }
    }

    public static function tearDownAfterClass(): void
    {
        global $DB;

        $tables = $DB->listTables('glpi\_plugin\_genericobject\_%');
        foreach ($tables as $table) {
            $DB->dropTable($table['TABLE_NAME']);
        }

        parent::tearDownAfterClass();
    }

    public function setUp(): void
    {
        global $DB;

        parent::setUp();

        $DB->delete(AssetDefinition::getTable(), [new QueryExpression('true')]);
        $DB->delete(DropdownDefinition::getTable(), [new QueryExpression('true')]);

        // Load it inside the test DB transaction to not have to clean it manually
        $queries = $DB->getQueriesFromFile(sprintf('%s/tests/fixtures/genericobject-migration/glpi-data.sql', GLPI_ROOT));
        foreach ($queries as $query) {
            $DB->doQuery($query);
        }
    }

    public function testProcessMigration(): void
    {
        // Arrange
        global $DB;

        // Forces the `auto_create_infocoms` config to validate that it is correctly supported by the migration.
        global $CFG_GLPI;
        $CFG_GLPI['auto_create_infocoms'] = true;

        $migration = new GenericobjectPluginMigration($DB);
        $result    = new PluginMigrationResult();
        $this->setPrivateProperty($migration, 'result', $result);

        // Act
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        // Assert

        // Validate created asset definitions
        $expected_definitions = [
            [
                'label'          => 'inactive',
                'comment'        => 'Inactive main object.',
                'icon'           => null,
                'picture'        => null,
                'is_active'      => false,
                'capacities'     => [],
                'profiles'       => [
                    1 => 0,
                    2 => 0,
                    3 => 0,
                    4 => 0,
                    5 => 0,
                    6 => 0,
                    7 => 0,
                    8 => 0,
                ],
                'translations'   => [],
                'fields_display' => [
                    ['key' => 'name', 'order' => 0, 'field_options' => []],
                    ['key' => 'comment', 'order' => 1, 'field_options' => []],
                ],
                'date_creation'  => '2025-03-06 14:30:12',
                // `date_mod` is automatically changed during the migration, we cannot prevent it
                // 'date_mod'       => '2025-03-06 14:32:48',
            ],
            [
                'label'          => 'tablet',
                'comment'        => 'Main object with only than the mandatory fields.',
                'icon'           => null,
                'picture'        => null,
                'is_active'      => true,
                'capacities'     => [],
                'profiles'       => [
                    1 => 0,
                    2 => 0,
                    3 => 0,
                    4 => 127,
                    5 => 0,
                    6 => 0,
                    7 => 0,
                    8 => 0,
                ],
                'translations'   => [],
                'fields_display' => [
                    ['key' => 'name', 'order' => 0, 'field_options' => []],
                    ['key' => 'comment', 'order' => 1, 'field_options' => []],
                ],
                'date_creation'  => '2025-03-05 16:32:43',
                // 'date_mod'       => '2025-03-06 09:58:55',
            ],
            [
                'label'          => 'smartphone',
                'comment'        => 'Main object with all the fields and capacities.',
                'icon'           => null,
                'picture'        => null,
                'is_active'      => true,
                'capacities'     => [
                    ['name' => AllowedInGlobalSearchCapacity::class, 'config' => []],
                    ['name' => HasContractsCapacity::class, 'config' => []],
                    ['name' => HasDevicesCapacity::class, 'config' => []],
                    ['name' => HasDocumentsCapacity::class, 'config' => []],
                    ['name' => HasHistoryCapacity::class, 'config' => []],
                    ['name' => HasInfocomCapacity::class, 'config' => []],
                    ['name' => HasNetworkPortCapacity::class, 'config' => []],
                    ['name' => HasNotepadCapacity::class, 'config' => []],
                    ['name' => IsProjectAssetCapacity::class, 'config' => []],
                    ['name' => IsReservableCapacity::class, 'config' => []],
                ],
                'profiles'       => [
                    1 => 33,
                    2 => 33,
                    3 => 127,
                    4 => 127,
                    5 => 33,
                    6 => 111,
                    7 => 33,
                    8 => 0,
                ],
                'translations'   => [],
                'fields_display' => [
                    ['key' => 'name', 'order' => 0, 'field_options' => []],
                    ['key' => 'serial', 'order' => 1, 'field_options' => []],
                    ['key' => 'otherserial', 'order' => 2, 'field_options' => []],
                    ['key' => 'locations_id', 'order' => 3, 'field_options' => []],
                    ['key' => 'states_id', 'order' => 4, 'field_options' => []],
                    ['key' => 'users_id', 'order' => 5, 'field_options' => []],
                    ['key' => 'groups_id', 'order' => 6, 'field_options' => []],
                    ['key' => 'manufacturers_id', 'order' => 7, 'field_options' => []],
                    ['key' => 'users_id_tech', 'order' => 8, 'field_options' => []],
                    ['key' => 'comment', 'order' => 9, 'field_options' => []],
                    ['key' => 'custom_computers_id_host', 'order' => 10, 'field_options' => []],
                    ['key' => 'custom_config_str', 'order' => 11, 'field_options' => []],
                    ['key' => 'contact', 'order' => 12, 'field_options' => []],
                    ['key' => 'contact_num', 'order' => 13, 'field_options' => []],
                    ['key' => 'custom_creationdate', 'order' => 14, 'field_options' => []],
                    ['key' => 'custom_count', 'order' => 15, 'field_options' => []],
                    ['key' => 'custom_expirationdate', 'order' => 16, 'field_options' => []],
                    ['key' => 'groups_id_tech', 'order' => 17, 'field_options' => []],
                    ['key' => 'custom_other', 'order' => 18, 'field_options' => []],
                    ['key' => 'custom_dropdowns_dropdowns_id_smartphonecategory', 'order' => 19, 'field_options' => []],
                    ['key' => 'assets_assetmodels_id', 'order' => 20, 'field_options' => []],
                    ['key' => 'assets_assettypes_id', 'order' => 21, 'field_options' => []],
                    ['key' => 'custom_url', 'order' => 22, 'field_options' => []],
                    ['key' => 'custom_assets_assets_id_tablet', 'order' => 23, 'field_options' => []],
                    ['key' => 'custom_dropdowns_dropdowns_id_bar', 'order' => 24, 'field_options' => []],
                    ['key' => 'custom_dropdowns_dropdowns_id_foo', 'order' => 25, 'field_options' => []],
                    ['key' => 'custom_dropdowns_dropdowns_id_uaus', 'order' => 26, 'field_options' => []],
                    ['key' => 'custom_dropdowns_dropdowns_id_item_state', 'order' => 27, 'field_options' => []],
                    ['key' => 'custom_dropdowns_dropdowns_id_test_abc', 'order' => 28, 'field_options' => []],
                ],
                'date_creation'  => '2025-03-05 16:28:56',
                // 'date_mod'       => '2025-03-06 14:19:23',
            ],
        ];
        $this->checkItems(AssetDefinition::class, $expected_definitions);
        $this->assertEquals(\count($expected_definitions), \countElementsInTable(AssetDefinition::getTable()));

        $smartphone_definition = \getItemByTypeName(AssetDefinition::class, 'Smartphone');
        $smartphone_class      = $smartphone_definition->getAssetClassName();
        $tablet_definition     = \getItemByTypeName(AssetDefinition::class, 'Tablet');
        $tablet_class          = $tablet_definition->getAssetClassName();

        // Validate that profiles are updated
        $expected_profiles_helpdesk_itemtypes = [
            1 => [
                "Computer",
                "PluginGenericobjectTablet",
                $tablet_class,
            ],
            3 => [
                "Computer",
                "PluginGenericobjectSmartphone",
                "PluginGenericobjectTablet",
                "Software",
                $smartphone_class,
                $tablet_class,
            ],
            4 => [
                "Computer",
                "PluginGenericobjectSmartphone",
                "PluginGenericobjectTablet",
                "Software",
                $smartphone_class,
                $tablet_class,
            ],
            6 => [
                "Computer",
                "PluginGenericobjectTablet",
                $tablet_class,
            ],
        ];
        foreach ($expected_profiles_helpdesk_itemtypes as $profile_id => $expected_helpdesk_itemtypes) {
            $profile = Profile::getById($profile_id);
            $this->assertInstanceOf(Profile::class, $profile);
            $this->assertJson($profile->fields['helpdesk_item_type']);
            $this->assertEqualsCanonicalizing($expected_helpdesk_itemtypes, \json_decode($profile->fields['helpdesk_item_type']));
        }

        // Validate that fields unicities are copied
        $fields_unicity = new FieldUnicity();
        $this->assertTrue($fields_unicity->getFromDBByCrit([
            'name'          => 'Smartphone uniqueness',
            'itemtype'      => $smartphone_class,
            'fields'        => 'name,serial',
            'entities_id'   => 0,
            'is_recursive'  => true,
            'is_active'     => true,
            'action_refuse' => true,
            'action_notify' => false,
        ]));
        $this->assertTrue($fields_unicity->getFromDBByCrit([
            'name'          => 'Tablet uniqueness',
            'itemtype'      => $tablet_class,
            'fields'        => 'name',
            'entities_id'   => 3,
            'is_recursive'  => false,
            'is_active'     => false,
            'action_refuse' => false,
            'action_notify' => true,
        ]));

        // Validate created dropdown definitions
        $expected_definitions = [
            [
                'label'         => 'Bar',
                'icon'          => null,
                'comment'       => null,
                'is_active'     => true,
                'profiles'      => \array_fill_keys([1, 2, 3, 4, 5, 6, 7, 8], 23),
                'translations'  => [],
                'date_creation' => $_SESSION['glpi_currenttime'],
                'date_mod'      => $_SESSION['glpi_currenttime'],
            ],
            [
                'label'         => 'Foo',
                'icon'          => null,
                'comment'       => null,
                'is_active'     => true,
                'profiles'      => \array_fill_keys([1, 2, 3, 4, 5, 6, 7, 8], 23),
                'translations'  => [],
                'date_creation' => $_SESSION['glpi_currenttime'],
                'date_mod'      => $_SESSION['glpi_currenttime'],
            ],
            [
                'label'         => 'Uaus',
                'icon'          => null,
                'comment'       => null,
                'is_active'     => true,
                'profiles'      => \array_fill_keys([1, 2, 3, 4, 5, 6, 7, 8], 23),
                'translations'  => [],
                'date_creation' => $_SESSION['glpi_currenttime'],
                'date_mod'      => $_SESSION['glpi_currenttime'],
            ],
            [
                'label'         => 'Item_State',
                'icon'          => null,
                'comment'       => null,
                'is_active'     => true,
                'profiles'      => \array_fill_keys([1, 2, 3, 4, 5, 6, 7, 8], 23),
                'translations'  => [],
                'date_creation' => $_SESSION['glpi_currenttime'],
                'date_mod'      => $_SESSION['glpi_currenttime'],
            ],
            [
                'label'         => 'Smartphonecategory',
                'icon'          => null,
                'comment'       => null,
                'is_active'     => true,
                'profiles'      => \array_fill_keys([1, 2, 3, 4, 5, 6, 7, 8], 23),
                'translations'  => [],
                'date_creation' => $_SESSION['glpi_currenttime'],
                'date_mod'      => $_SESSION['glpi_currenttime'],
            ],
            [
                'label'         => 'Test_Abc',
                'icon'          => null,
                'comment'       => null,
                'is_active'     => true,
                'profiles'      => \array_fill_keys([1, 2, 3, 4, 5, 6, 7, 8], 23),
                'translations'  => [],
                'date_creation' => $_SESSION['glpi_currenttime'],
                'date_mod'      => $_SESSION['glpi_currenttime'],
            ],
        ];
        $this->checkItems(DropdownDefinition::class, $expected_definitions);
        $this->assertEquals(\count($expected_definitions), \countElementsInTable(DropdownDefinition::getTable()));

        // Validate created assets/model/types
        $this->checkItems(
            $smartphone_definition->getAssetModelClassname(),
            [
                [
                    'name' => 'Model 1',
                ],
                [
                    'name' => 'Model 2',
                ],
                [
                    'name' => 'Model 3',
                ],
                [
                    'name' => 'Model 4',
                ],
                [
                    'name' => 'Model 5',
                ],
            ]
        );

        $this->checkItems(
            $smartphone_definition->getAssetTypeClassname(),
            [
                [
                    'name' => 'Type 1',
                ],
                [
                    'name' => 'Type 2',
                ],
                [
                    'name' => 'Type 3',
                ],
                [
                    'name' => 'Type 4',
                ],
                [
                    'name' => 'Type 5',
                ],
            ]
        );

        $this->checkItems(
            $smartphone_class,
            [
                [
                    'name' => 'Smartphone 1',
                    'comment' => '',
                    'assets_assetdefinitions_id' => $smartphone_definition->getID(),
                    'assets_assetmodels_id' => \getItemByTypeName($smartphone_definition->getAssetModelClassname(), 'Model 2', true),
                    'assets_assettypes_id' => \getItemByTypeName($smartphone_definition->getAssetTypeClassname(), 'Type 5', true),
                    'is_template' => false,
                    'template_name' => '',
                    'is_deleted' => false,
                    'entities_id' => 0,
                    'is_recursive' => false,
                    'serial' => 'SER0123',
                    'otherserial' => '',
                    'locations_id' => 1,
                    'states_id' => 1,
                    'users_id' => 3,
                    'users_id_tech' => 4,
                    'manufacturers_id' => 3,
                    'contact' => '',
                    'contact_num' => '',
                    'custom_computers_id_host' => 1,
                    'custom_config_str' => "# is_foo=0\nis_foo=1\nbar=\"adbizq\"",
                    'custom_creationdate' => '2025-02-01',
                    'custom_count' => 19,
                    'custom_expirationdate' => '2025-12-31',
                    'custom_other' => 'some random value',
                    'custom_url' => 'https://example.org/?id=9783',
                    'custom_dropdowns_dropdowns_id_smartphonecategory' => \getItemByTypeName('Glpi\\CustomDropdown\\SmartphoneCategoryDropdown', 'Cat 5', true),
                    'custom_assets_assets_id_tablet' => \getItemByTypeName('Glpi\\CustomAsset\\TabletAsset', 'Tablet 1', true),
                    'custom_dropdowns_dropdowns_id_bar' => \getItemByTypeName('Glpi\\CustomDropdown\\BarDropdown', 'Bar 3', true),
                    'custom_dropdowns_dropdowns_id_foo' => \getItemByTypeName('Glpi\\CustomDropdown\\FooDropdown', 'Foo 2', true),
                    'custom_dropdowns_dropdowns_id_item_state' => 0,
                    'custom_dropdowns_dropdowns_id_test_abc' => \getItemByTypeName('Glpi\\CustomDropdown\\Test_AbcDropdown', 'Test 1', true),
                    'date_creation' => '2025-03-06 10:08:51',
                    // 'date_mod' => '2025-03-06 10:08:51',
                ],
                [
                    'name' => 'Smartphone 2',
                    'comment' => '',
                    'assets_assetdefinitions_id' => $smartphone_definition->getID(),
                    'assets_assetmodels_id' => \getItemByTypeName($smartphone_definition->getAssetModelClassname(), 'Model 2', true),
                    'assets_assettypes_id' => 0,
                    'is_template' => false,
                    'template_name' => '',
                    'is_deleted' => false,
                    'entities_id' => 0,
                    'is_recursive' => true,
                    'serial' => 'SER0198',
                    'otherserial' => '',
                    'locations_id' => 2,
                    'states_id' => 2,
                    'users_id' => 5,
                    'users_id_tech' => 0,
                    'manufacturers_id' => 2,
                    'contact' => '',
                    'contact_num' => '',
                    'custom_computers_id_host' => 2,
                    'custom_config_str' => '# no config',
                    'custom_creationdate' => null,
                    'custom_count' => 0,
                    'custom_expirationdate' => null,
                    'custom_other' => '',
                    'custom_url' => '',
                    'custom_dropdowns_dropdowns_id_smartphonecategory' => \getItemByTypeName('Glpi\\CustomDropdown\\SmartphoneCategoryDropdown', 'Cat 4', true),
                    'custom_assets_assets_id_tablet' => 0,
                    'custom_dropdowns_dropdowns_id_bar' => \getItemByTypeName('Glpi\\CustomDropdown\\BarDropdown', 'Bar 4', true),
                    'custom_dropdowns_dropdowns_id_foo' => 0,
                    'custom_dropdowns_dropdowns_id_item_state' => \getItemByTypeName('Glpi\\CustomDropdown\\Item_StateDropdown', 'State 3', true),
                    'custom_dropdowns_dropdowns_id_test_abc' => 0,
                    'date_creation' => '2025-03-06 10:11:46',
                    // 'date_mod' => '2025-03-06 10:11:46',
                ],
                [
                    'name' => 'Smartphone 3',
                    'comment' => 'Some comments...',
                    'assets_assetdefinitions_id' => $smartphone_definition->getID(),
                    'assets_assetmodels_id' => 0,
                    'assets_assettypes_id' => \getItemByTypeName($smartphone_definition->getAssetTypeClassname(), 'Type 4', true),
                    'is_template' => false,
                    'template_name' => '',
                    'is_deleted' => false,
                    'entities_id' => 0,
                    'is_recursive' => false,
                    'serial' => '',
                    'otherserial' => 'INV123456',
                    'locations_id' => 0,
                    'states_id' => 0,
                    'users_id' => 0,
                    'users_id_tech' => 4,
                    'manufacturers_id' => 0,
                    'contact' => '',
                    'contact_num' => '',
                    'custom_computers_id_host' => 0,
                    'custom_config_str' => '',
                    'custom_creationdate' => null,
                    'custom_count' => 0,
                    'custom_expirationdate' => null,
                    'custom_other' => '',
                    'custom_url' => '',
                    'custom_dropdowns_dropdowns_id_smartphonecategory' => 0,
                    'custom_assets_assets_id_tablet' => \getItemByTypeName('Glpi\\CustomAsset\\TabletAsset', 'Tablet 3', true),
                    'custom_dropdowns_dropdowns_id_bar' => 0,
                    'custom_dropdowns_dropdowns_id_foo' => 0,
                    'custom_dropdowns_dropdowns_id_item_state' => \getItemByTypeName('Glpi\\CustomDropdown\\Item_StateDropdown', 'State 1', true),
                    'custom_dropdowns_dropdowns_id_test_abc' => \getItemByTypeName('Glpi\\CustomDropdown\\Test_AbcDropdown', 'Test 2', true),
                    'date_creation' => '2025-03-06 10:12:59',
                    // 'date_mod' => '2025-03-06 10:12:59',
                ],
            ]
        );

        $this->checkItems(
            $tablet_class,
            [
                [
                    'name' => 'Tablet 1',
                ],
                [
                    'name' => 'Tablet 2',
                ],
                [
                    'name' => 'Tablet 3',
                ],
                [
                    'name' => 'Tablet 4',
                ],
            ]
        );

        // Validate created dropdowns
        $bar_definition = \getItemByTypeName(DropdownDefinition::class, 'Bar');
        $foo_definition = \getItemByTypeName(DropdownDefinition::class, 'Foo');
        $uau_definition = \getItemByTypeName(DropdownDefinition::class, 'Uaus');
        $item_state_definition = \getItemByTypeName(DropdownDefinition::class, 'Item_State');
        $cat_definition = \getItemByTypeName(DropdownDefinition::class, 'SmartphoneCategory');
        $test_abc_definition = \getItemByTypeName(DropdownDefinition::class, 'Test_Abc');

        $this->checkItems(
            $bar_definition->getDropdownClassName(),
            [
                [
                    'name'          => 'Bar 1',
                    'comment'       => '',
                    'date_creation' => '2025-03-06 10:06:47',
                    'date_mod'      => '2025-03-06 10:06:47',
                    'entities_id'   => 0,
                    'is_recursive'  => false,
                ],
                [
                    'name'          => 'Bar 2',
                    'comment'       => '',
                    'date_creation' => '2025-03-06 10:06:49',
                    'date_mod'      => '2025-03-06 10:06:49',
                    'entities_id'   => 1,
                    'is_recursive'  => true,
                ],
                [
                    'name'          => 'Bar 3',
                    'comment'       => 'A comment about bar 3',
                    'date_creation' => '2025-03-06 10:06:52',
                    'date_mod'      => '2025-03-06 10:07:13',
                    'entities_id'   => 0,
                    'is_recursive'  => true,
                ],
                [
                    'name'          => 'Bar 4',
                ],
                [
                    'name'          => 'Bar 5',
                ],
                [
                    'name'          => 'Bar 6',
                ],
                [
                    'name'          => 'Bar 7',
                ],
                [
                    'name'          => 'Bar 8',
                ],
                [
                    'name'          => 'Bar 9',
                ],
                [
                    'name'          => 'Bar 10',
                ],
            ]
        );

        $this->checkItems(
            $foo_definition->getDropdownClassName(),
            [
                [
                    'name'          => 'Foo 1',
                ],
                [
                    'name'          => 'Foo 2',
                ],
                [
                    'name'          => 'Foo 3',
                ],
                [
                    'name'          => 'Foo 4',
                ],
                [
                    'name'          => 'Foo 5',
                ],
                [
                    'name'          => 'Foo 6',
                ],
                [
                    'name'          => 'Foo 7',
                ],
                [
                    'name'          => 'Foo 8',
                ],
                [
                    'name'          => 'Foo 9',
                ],
                [
                    'name'          => 'Foo 10',
                ],
            ]
        );

        $this->checkItems(
            $uau_definition->getDropdownClassName(),
            [
                [
                    'name'          => 'Uau 1',
                ],
                [
                    'name'          => 'Uau 2',
                ],
            ]
        );

        $this->checkItems(
            $item_state_definition->getDropdownClassName(),
            [
                [
                    'name'          => 'State 1',
                ],
                [
                    'name'          => 'State 2',
                ],
                [
                    'name'          => 'State 3',
                ],
            ]
        );

        $this->checkItems(
            $cat_definition->getDropdownClassName(),
            [
                [
                    'name'          => 'Cat 1',
                ],
                [
                    'name'          => 'Cat 2',
                ],
                [
                    'name'          => 'Cat 3',
                ],
                [
                    'name'          => 'Cat 4',
                ],
                [
                    'name'          => 'Cat 5',
                ],
                [
                    'name'          => 'Cat 6',
                ],
                [
                    'name'          => 'Cat 7',
                ],
                [
                    'name'          => 'Cat 8',
                ],
            ]
        );

        $this->checkItems(
            $test_abc_definition->getDropdownClassName(),
            [
                [
                    'name'          => 'Test 1',
                ],
                [
                    'name'          => 'Test 2',
                ],
                [
                    'name'          => 'Test 3',
                ],
            ]
        );

        // Check dropdown translations
        $bar_classname = $bar_definition->getDropdownClassName();

        $bar_1_id = \getItemByTypeName($bar_classname, 'Bar 1', true);
        $bar_2_id = \getItemByTypeName($bar_classname, 'Bar 2', true);

        $expected_dropdown_translations = [
            [
                'itemtype' => $bar_classname,
                'items_id' => $bar_1_id,
                'language' => 'fr_FR',
                'field'    => 'name',
                'value'    => 'Bar 1 (FR)',
            ],
            [
                'itemtype' => $bar_classname,
                'items_id' => $bar_1_id,
                'language' => 'es_SP',
                'field'    => 'name',
                'value'    => 'Bar 1 (ES)',
            ],
            [
                'itemtype' => $bar_classname,
                'items_id' => $bar_2_id,
                'language' => 'es_SP',
                'field'    => 'name',
                'value'    => 'Bar 2 (ES)',
            ],
        ];
        foreach ($expected_dropdown_translations as $expected_dropdown_translation) {
            $dropdown_translation = new DropdownTranslation();
            $this->assertTrue(
                $dropdown_translation->getFromDBByCrit($expected_dropdown_translation),
                json_encode($expected_dropdown_translation)
            );
        }

        // Check groups relations
        $expected_group_items = [
            [
                'groups_id' => 2,
                'itemtype'  => $smartphone_class,
                'items_id'  => \getItemByTypeName($smartphone_class, 'Smartphone 1', true),
                'type'      => Group_Item::GROUP_TYPE_NORMAL,
            ],
            [
                'groups_id' => 1,
                'itemtype'  => $smartphone_class,
                'items_id'  => \getItemByTypeName($smartphone_class, 'Smartphone 2', true),
                'type'      => Group_Item::GROUP_TYPE_NORMAL,
            ],
            [
                'groups_id' => 4,
                'itemtype'  => $smartphone_class,
                'items_id'  => \getItemByTypeName($smartphone_class, 'Smartphone 2', true),
                'type'      => Group_Item::GROUP_TYPE_TECH,
            ],
        ];
        foreach ($expected_group_items as $expected_group_item) {
            $group_item = new Group_Item();
            $this->assertTrue(
                $group_item->getFromDBByCrit($expected_group_item),
                json_encode($expected_group_item)
            );
        }

        // Check domain relations
        $expected_domain_items = [
            [
                'domains_id'         => 4,
                'itemtype'           => $smartphone_class,
                'items_id'           => \getItemByTypeName($smartphone_class, 'Smartphone 1', true),
                'domainrelations_id' => 0,
                'is_dynamic'         => false,
                'is_deleted'         => false,
            ],
            [
                'domains_id'         => 3,
                'itemtype'           => $smartphone_class,
                'items_id'           => \getItemByTypeName($smartphone_class, 'Smartphone 3', true),
                'domainrelations_id' => 0,
                'is_dynamic'         => false,
                'is_deleted'         => false,
            ],
        ];
        foreach ($expected_domain_items as $expected_domain_item) {
            $domain_item = new Domain_Item();
            $this->assertTrue($domain_item->getFromDBByCrit($expected_domain_item), json_encode($expected_domain_item));
        }

        // Check that polymorphic relations have been copied
        $infocom = new Infocom();
        $this->assertTrue($infocom->getFromDBByCrit([
            'itemtype' => $smartphone_class,
            'items_id' => \getItemByTypeName($smartphone_class, 'Smartphone 2', true),
        ]));
        $expected_infocom_fields = [
            'buy_date'          => '2025-03-03',
            'use_date'          => '2025-03-12',
            'order_date'        => '2025-03-03',
            'delivery_date'     => '2025-03-11',
            'warranty_date'     => '2025-03-12',
            'value'             => 1500,
            'warranty_value'    => 250,
            'warranty_duration' => 12,
            'sink_time'         => 3,
            'sink_type'         => 2,
            'sink_coeff'        => 0,
        ];
        foreach ($expected_infocom_fields as $field_key => $field_value) {
            $this->assertArrayHasKey($field_key, $infocom->fields);
            $this->assertEquals($field_value, $infocom->fields[$field_key]);
        }

        $expected_contract_items = [
            [
                'contracts_id'  => 4,
                'itemtype'      => $smartphone_class,
                'items_id'      => \getItemByTypeName($smartphone_class, 'Smartphone 1', true),
            ],
            [
                'contracts_id'  => 7,
                'itemtype'      => $smartphone_class,
                'items_id'      => \getItemByTypeName($smartphone_class, 'Smartphone 1', true),
            ],
            [
                'contracts_id'  => 12,
                'itemtype'      => $smartphone_class,
                'items_id'      => \getItemByTypeName($smartphone_class, 'Smartphone 3', true),
            ],
        ];
        foreach ($expected_contract_items as $expected_contract_item) {
            $contract_item = new Contract_Item();
            $this->assertTrue($contract_item->getFromDBByCrit($expected_contract_item), json_encode($expected_contract_item));
        }
    }

    /**
     * Check that the expected items of the given class are present in DB and have the expected fields values.
     *
     * @param class-string<\CommonDBTM> $class
     * @param array<string, array<string, mixed>> $expected_items
     */
    private function checkItems(string $class, array $expected_items): void
    {
        foreach ($expected_items as $expected_fields) {
            $item = new $class();

            $name_field = $class::getNameField();
            $name       = $expected_fields[$name_field];
            $this->assertTrue(
                $item->getFromDBByCrit([$name_field => $name]),
                sprintf('`%s` item not found', $name)
            );

            foreach ($expected_fields as $key => $expected_value) {
                if (\is_array($expected_value)) {
                    $this->assertJson(
                        $item->fields[$key],
                        sprintf('`%s` field of the `%s` item does not contain a valid JSON string', $key, $name)
                    );
                    $this->assertArrayIsEqualIgnoringKeysOrder(
                        $expected_value,
                        \json_decode($item->fields[$key], associative: true),
                        sprintf('`%s` field of the `%s` item does not match the expected value', $key, $name)
                    );
                } else {
                    $this->assertEquals(
                        $expected_value,
                        $item->fields[$key],
                        sprintf('`%s` field of the `%s` item does not match the expected value', $key, $name)
                    );
                }
            }
        }

        $this->assertEquals(
            \count($expected_items),
            \countElementsInTable($class::getTable(), $class::getSystemSqlCriteria())
        );
    }
}
