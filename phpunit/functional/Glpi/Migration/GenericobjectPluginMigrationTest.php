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

use DbTestCase;
use Glpi\Migration\GenericobjectPluginMigration;
use Glpi\Asset\AssetDefinition;
use Glpi\Migration\PluginMigrationResult;
use Glpi\Asset\Capacity\HasContractsCapacity;
use Glpi\Asset\Capacity\HasDocumentsCapacity;
use Glpi\Asset\Capacity\AllowedInGlobalSearchCapacity;
use Glpi\Asset\Capacity\HasHistoryCapacity;
use Glpi\Asset\Capacity\HasInfocomCapacity;
use Glpi\Asset\Capacity\HasDevicesCapacity;
use Glpi\Asset\Capacity\IsReservableCapacity;
use Glpi\Asset\Capacity\HasNetworkPortCapacity;
use Glpi\Asset\Capacity\HasNotepadCapacity;
use Glpi\Asset\Capacity\IsProjectAssetCapacity;
use Glpi\Dropdown\DropdownDefinition;

class GenericobjectPluginMigrationTest extends DbTestCase
{
    public static function setUpBeforeClass(): void
    {
        /** @var \DBmysql $DB */
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
        /** @var \DBmysql $DB */
        global $DB;

        $tables = $DB->listTables('glpi\_plugin\_genericobject\_%');
        foreach ($tables as $table) {
            $DB->dropTable($table['TABLE_NAME']);
        }

        parent::tearDownAfterClass();
    }

    public function setUp(): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        parent::setUp();

        // Load it inside the test DB transaction to not have to clean it manually
        $queries = $DB->getQueriesFromFile(sprintf('%s/tests/fixtures/genericobject-migration/glpi-data.sql', GLPI_ROOT));
        foreach ($queries as $query) {
            $DB->doQuery($query);
        }
    }

    public function testProcessMigration(): void
    {
        // Arrange
        /** @var \DBmysql $DB */
        global $DB;

        $migration = new GenericobjectPluginMigration($DB);
        $result    = new PluginMigrationResult();
        $this->setPrivateProperty($migration, 'result', $result);

        // Act
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        // Assert

        // Validate created asset definitions
        $expected_definitions = [
            'inactive'   => [
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
                    ['key' => 'name', 'order' => 0, 'fields_options' => []],
                    ['key' => 'comment', 'order' => 1, 'fields_options' => []],
                ],
                'date_creation'  => '2025-03-06 14:30:12',
                // `date_mod` is automatically changed during the migration, we cannot prevent it
                // 'date_mod'       => '2025-03-06 14:32:48',
            ],
            'tablet'   => [
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
                    ['key' => 'name', 'order' => 0, 'fields_options' => []],
                    ['key' => 'comment', 'order' => 1, 'fields_options' => []],
                ],
                'date_creation'  => '2025-03-05 16:32:43',
                // 'date_mod'       => '2025-03-06 09:58:55',
            ],
            'smartphone' => [
                'label'          => 'smartphone',
                'comment'        => 'Main object with all the fields and capacities.',
                'icon'           => null,
                'picture'        => '62f9ae776176b4.07833080smartphone.png',
                'is_active'      => true,
                'capacities'     => [
                    AllowedInGlobalSearchCapacity::class,
                    HasContractsCapacity::class,
                    HasDevicesCapacity::class,
                    HasDocumentsCapacity::class,
                    HasHistoryCapacity::class,
                    HasInfocomCapacity::class,
                    HasNetworkPortCapacity::class,
                    HasNotepadCapacity::class,
                    IsProjectAssetCapacity::class,
                    IsReservableCapacity::class,
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
                    ['key' => 'name', 'order' => 0, 'fields_options' => []],
                    ['key' => 'serial', 'order' => 1, 'fields_options' => []],
                    ['key' => 'otherserial', 'order' => 2, 'fields_options' => []],
                    ['key' => 'locations_id', 'order' => 3, 'fields_options' => []],
                    ['key' => 'states_id', 'order' => 4, 'fields_options' => []],
                    ['key' => 'users_id', 'order' => 5, 'fields_options' => []],
                    ['key' => 'groups_id', 'order' => 6, 'fields_options' => []],
                    ['key' => 'manufacturers_id', 'order' => 7, 'fields_options' => []],
                    ['key' => 'users_id_tech', 'order' => 8, 'fields_options' => []],
                    ['key' => 'comment', 'order' => 9, 'fields_options' => []],
                    ['key' => 'custom_computers_id_host', 'order' => 10, 'fields_options' => []],
                    ['key' => 'custom_config_str', 'order' => 11, 'fields_options' => []],
                    ['key' => 'contact', 'order' => 12, 'fields_options' => []],
                    ['key' => 'contact_num', 'order' => 13, 'fields_options' => []],
                    ['key' => 'custom_creationdate', 'order' => 14, 'fields_options' => []],
                    ['key' => 'custom_count', 'order' => 15, 'fields_options' => []],
                    ['key' => 'custom_expirationdate', 'order' => 16, 'fields_options' => []],
                    ['key' => 'groups_id_tech', 'order' => 17, 'fields_options' => []],
                    ['key' => 'custom_other', 'order' => 18, 'fields_options' => []],
                    ['key' => 'custom_dropdowns_dropdowns_id_smartphonecategory', 'order' => 19, 'fields_options' => []],
                    ['key' => 'assets_assetmodels_id', 'order' => 20, 'fields_options' => []],
                    ['key' => 'assets_assettypes_id', 'order' => 21, 'fields_options' => []],
                    ['key' => 'custom_url', 'order' => 22, 'fields_options' => []],
                    ['key' => 'custom_assets_assets_id_tablet', 'order' => 23, 'fields_options' => []],
                    ['key' => 'custom_dropdowns_dropdowns_id_bar', 'order' => 24, 'fields_options' => []],
                    ['key' => 'custom_dropdowns_dropdowns_id_foo', 'order' => 25, 'fields_options' => []],
                ],
                'date_creation'  => '2025-03-05 16:28:56',
                // 'date_mod'       => '2025-03-06 14:19:23',
            ],
        ];

        $this->assertEquals(\count($expected_definitions), \countElementsInTable(AssetDefinition::getTable()));
        $this->checkDefinitionItems(AssetDefinition::class, $expected_definitions);

        // Validate created dropdown definitions
        $expected_definitions = [
            'Bar' => [
                'label'         => 'Bar',
                'icon'          => null,
                'comment'       => null,
                'is_active'     => true,
                'profiles'      => \array_fill_keys([1, 2, 3, 4, 5, 6, 7, 8], 23),
                'translations'  => [],
                'date_creation' => $_SESSION['glpi_currenttime'],
                'date_mod'      => $_SESSION['glpi_currenttime'],
            ],
            'Foo' => [
                'label'         => 'Foo',
                'icon'          => null,
                'comment'       => null,
                'is_active'     => true,
                'profiles'      => \array_fill_keys([1, 2, 3, 4, 5, 6, 7, 8], 23),
                'translations'  => [],
                'date_creation' => $_SESSION['glpi_currenttime'],
                'date_mod'      => $_SESSION['glpi_currenttime'],
            ],
            'Smartphonecategory' => [
                'label'         => 'Smartphonecategory',
                'icon'          => null,
                'comment'       => null,
                'is_active'     => true,
                'profiles'      => \array_fill_keys([1, 2, 3, 4, 5, 6, 7, 8], 23),
                'translations'  => [],
                'date_creation' => $_SESSION['glpi_currenttime'],
                'date_mod'      => $_SESSION['glpi_currenttime'],
            ],
        ];

        $this->assertEquals(\count($expected_definitions), \countElementsInTable(DropdownDefinition::getTable()));
        $this->checkDefinitionItems(DropdownDefinition::class, $expected_definitions);


        // TODO Validate created assets

        // TODO Validate created dropdowns
    }

    /**
     * Check that the expected fields of the given definitions.
     * @param class-string<\Glpi\CustomObject\AbstractDefinition> $class
     * @param array<string, array<string, mixed>> $expected_items
     */
    private function checkDefinitionItems(string $class, array $expected_items): void
    {
        foreach ($expected_items as $system_name => $expected_fields) {
            $definition = new $class();
            $this->assertTrue($definition->getFromDBBySystemName($system_name));
            foreach ($expected_fields as $key => $expected_value) {
                if (\is_array($expected_value)) {
                    $this->assertJson(
                        $definition->fields[$key],
                        sprintf('`%s` field of the `%s` definition does not contain a valid JSON string', $key, $system_name)
                    );
                    $this->assertEqualsCanonicalizing(
                        $expected_value,
                        \json_decode($definition->fields[$key], associative: true),
                        sprintf('`%s` field of the `%s` definition does not match the expected value', $key, $system_name)
                    );
                } else {
                    $this->assertEquals(
                        $expected_value,
                        $definition->fields[$key],
                        sprintf('`%s` field of the `%s` definition does not match the expected value', $key, $system_name)
                    );
                }
            }
        }
    }
}
