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

namespace tests\units\Glpi\Asset;

use DbTestCase;
use Glpi\Asset\AssetDefinitionManager;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasHistoryCapacity;
use Glpi\Asset\CustomFieldDefinition;
use Glpi\Asset\CustomFieldType\BooleanType;
use Glpi\Asset\CustomFieldType\DateTimeType;
use Glpi\Asset\CustomFieldType\DateType;
use Glpi\Asset\CustomFieldType\DropdownType;
use Glpi\Asset\CustomFieldType\NumberType;
use Glpi\Asset\CustomFieldType\StringType;
use Glpi\Asset\CustomFieldType\TextType;
use Glpi\Asset\CustomFieldType\URLType;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\Search\SearchEngine;
use Glpi\Search\SearchOption;
use PHPUnit\Framework\Attributes\DataProvider;

class CustomFieldDefinitionTest extends DbTestCase
{
    /**
     * Ensure custom fields are removed from assets when the custom field definition is removed
     * @return void
     */
    public function testCleanDBOnPurge()
    {
        $asset_definition = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasHistoryCapacity::class),
            ]
        );
        $asset_classname = $asset_definition->getAssetClassName();

        $custom_field_definition = $this->createItem(CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'system_name' => 'test_string',
            'label' => 'Test string',
            'type' => StringType::class,
            'default_value' => 'default',
        ]);

        $custom_field_definition_2 = $this->createItem(CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'system_name' => 'test_text',
            'label' => 'Test text',
            'type' => TextType::class,
            'default_value' => 'default text',
        ]);

        $this->assertTrue($asset_definition->getFromDB($asset_definition->getID()));

        $this->assertTrue($asset_definition->update([
            'id' => $asset_definition->getID(),
            'fields_display' => [
                0 => 'name',
                1 => 'custom_test_string',
                2 => 'serial',
            ],
        ]));

        $asset = $this->createItem($asset_classname, [
            'entities_id' => $this->getTestRootEntity(true),
            'name' => 'Test asset',
            'custom_test_string' => 'value',
            'custom_test_text' => 'value2',
        ], ['custom_test_string']);

        $custom_field_definition->delete(['id' => $custom_field_definition->getID()]);

        $asset->getFromDB($asset->getID());
        $this->assertEquals('{"' . $custom_field_definition_2->getID() . '": "value2"}', $asset->fields['custom_fields']);

        $this->assertTrue($asset_definition->getFromDB($asset_definition->getID()));
        $fields_display = $asset_definition->getDecodedFieldsField();
        $this->assertCount(2, $fields_display);
        $this->assertEquals('name', $fields_display[0]['key']);
        $this->assertEquals(0, $fields_display[0]['order']);
        $this->assertEquals('serial', $fields_display[1]['key']);
        $this->assertEquals(1, $fields_display[1]['order']);
    }

    public function testGetAllowedDropdownItemtypes()
    {
        $allowed_itemtypes = AssetDefinitionManager::getInstance()->getAllowedDropdownItemtypes();
        $this->assertNotEmpty($allowed_itemtypes);
        foreach ($allowed_itemtypes as $group => $opts) {
            $this->assertNotEmpty($opts);
            $this->assertIsString($group);
            foreach ($opts as $classname => $label) {
                $this->assertTrue(is_subclass_of($classname, \CommonDBTM::class));
                $this->assertNotEmpty($label);
            }
        }
    }

    public static function validateValueProvider()
    {
        return [
            [
                'field_params' => ['type' => BooleanType::class],
                'given_value' => 1,
                'expected_value' => true,
                'is_valid' => true,
            ],
            [
                'field_params' => ['type' => BooleanType::class],
                'given_value' => 0,
                'expected_value' => false,
                'is_valid' => true,
            ],
            [
                'field_params' => ['type' => BooleanType::class],
                'given_value' => '0',
                'expected_value' => false,
                'is_valid' => true,
            ],
            [
                'field_params' => ['type' => BooleanType::class],
                'given_value' => 2,
                'expected_value' => 2,
                'is_valid' => false,
            ],
            [
                'field_params' => ['type' => StringType::class],
                'given_value' => 'test',
                'expected_value' => 'test',
                'is_valid' => true,
            ],
            [
                'field_params' => ['type' => StringType::class],
                // Longer than 255 characters
                'given_value' => 'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz',
                // Truncated to 255 characters
                'expected_value' => 'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstu',
                'is_valid' => true,
            ],
            [
                'field_params' => ['type' => StringType::class],
                'given_value' => 2,
                'expected_value' => 2,
                'is_valid' => false,
            ],
            [
                'field_params' => ['type' => TextType::class],
                'given_value' => 'test',
                'expected_value' => 'test',
                'is_valid' => true,
            ],
            [
                'field_params' => ['type' => TextType::class],
                'given_value' => 2,
                'expected_value' => 2,
                'is_valid' => false,
            ],
            [
                'field_params' => ['type' => NumberType::class],
                'given_value' => 5,
                'expected_value' => 5,
                'is_valid' => true,
            ],
            [
                'field_params' => ['type' => NumberType::class],
                'given_value' => 'test',
                'expected_value' => 'test',
                'is_valid' => false,
            ],
            [
                'field_params' => ['type' => NumberType::class],
                'given_value' => 5.6,
                'expected_value' => 5,
                'is_valid' => true,
            ],
            [
                'field_params' => ['type' => NumberType::class, 'field_options' => ['step' => 0.1]],
                'given_value' => 5.6,
                'expected_value' => 5.6,
                'is_valid' => true,
            ],
            [
                'field_params' => ['type' => NumberType::class, 'field_options' => ['step' => 0.1, 'min' => 6]],
                'given_value' => 5.6,
                'expected_value' => 6,
                'is_valid' => true,
            ],
            [
                'field_params' => ['type' => NumberType::class, 'field_options' => ['step' => 0.1, 'max' => 5]],
                'given_value' => 5.6,
                'expected_value' => 5,
                'is_valid' => true,
            ],
            [
                'field_params' => ['type' => DateType::class],
                'given_value' => '2021-01-01',
                'expected_value' => '2021-01-01',
                'is_valid' => true,
            ],
            [
                'field_params' => ['type' => DateType::class],
                'given_value' => 'test',
                'expected_value' => 'test',
                'is_valid' => false,
            ],
            [
                'field_params' => ['type' => DateType::class],
                'given_value' => '',
                'expected_value' => null,
                'is_valid' => true,
            ],
            [
                'field_params' => ['type' => DateTimeType::class],
                'given_value' => '2021-01-01 00:00:00',
                'expected_value' => '2021-01-01 00:00:00',
                'is_valid' => true,
            ],
            [
                'field_params' => ['type' => DateTimeType::class],
                'given_value' => '2021-01-01',
                'expected_value' => '2021-01-01',
                'is_valid' => false,
            ],
            [
                'field_params' => ['type' => DateTimeType::class],
                'given_value' => 'test',
                'expected_value' => 'test',
                'is_valid' => false,
            ],
            [
                'field_params' => ['type' => DateTimeType::class],
                'given_value' => '',
                'expected_value' => null,
                'is_valid' => true,
            ],
        ];
    }

    #[DataProvider('validateValueProvider')]
    public function testValidateValue($field_params, $given_value, $expected_value, $is_valid)
    {
        $value = $given_value;
        $custom_field = new CustomFieldDefinition();
        $custom_field->fields = $field_params;
        if (!$is_valid) {
            $this->expectException(\InvalidArgumentException::class);
            $custom_field->getFieldType()->normalizeValue($value);
        } else {
            $this->assertEquals($expected_value, $custom_field->getFieldType()->normalizeValue($value));
        }
    }

    public function testGetEmpty()
    {
        $custom_field = new CustomFieldDefinition();
        $custom_field->getEmpty();
        $this->assertEmpty($custom_field->fields['field_options']);
    }

    public function testGetSearchOption()
    {
        $opt_id_offset = 45000;
        $asset_definition = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasHistoryCapacity::class),
            ]
        );

        $custom_field_definition = $this->createItem(CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'system_name' => 'test_string',
            'label' => 'Test string',
            'type' => StringType::class,
            'default_value' => 'default',
        ]);
        $field_id = $custom_field_definition->getID();
        $opt = $custom_field_definition->getFieldType()->getSearchOption();
        $this->assertEquals($opt_id_offset + $field_id, $opt['id']);
        $this->assertEquals('Test string', $opt['name']);
        $this->assertEquals("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`glpi_assets_assets`.`custom_fields`, '$.\\\"{$field_id}\\\"')), 'default')", (string) $opt['computation']);
        $this->assertEquals('string', $opt['datatype']);

        $custom_field_definition_2 = $this->createItem(CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'system_name' => 'test_text',
            'label' => 'Test text',
            'type' => TextType::class,
            'default_value' => 'default text',
        ]);
        $field_id = $custom_field_definition_2->getID();
        $opt = $custom_field_definition_2->getFieldType()->getSearchOption();
        $this->assertEquals($opt_id_offset + $field_id, $opt['id']);
        $this->assertEquals('Test text', $opt['name']);
        $this->assertEquals("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`glpi_assets_assets`.`custom_fields`, '$.\\\"{$field_id}\\\"')), 'default text')", (string) $opt['computation']);
        $this->assertEquals('text', $opt['datatype']);
        $this->assertArrayNotHasKey('htmltext', $opt);

        $custom_field_definition_4 = $this->createItem(CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'system_name' => 'test_number',
            'label' => 'Test number',
            'type' => NumberType::class,
            'default_value' => 420,
        ]);
        $field_id = $custom_field_definition_4->getID();
        $opt = $custom_field_definition_4->getFieldType()->getSearchOption();
        $this->assertEquals($opt_id_offset + $field_id, $opt['id']);
        $this->assertEquals('Test number', $opt['name']);
        $this->assertEquals("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`glpi_assets_assets`.`custom_fields`, '$.\\\"{$field_id}\\\"')), '420')", (string) $opt['computation']);
        $this->assertEquals('number', $opt['datatype']);

        $custom_field_definition_5 = $this->createItem(CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'system_name' => 'test_date',
            'label' => 'Test date',
            'type' => DateType::class,
            'default_value' => '2021-01-01',
        ]);
        $field_id = $custom_field_definition_5->getID();
        $opt = $custom_field_definition_5->getFieldType()->getSearchOption();
        $this->assertEquals($opt_id_offset + $field_id, $opt['id']);
        $this->assertEquals('Test date', $opt['name']);
        $this->assertEquals("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`glpi_assets_assets`.`custom_fields`, '$.\\\"{$field_id}\\\"')), '2021-01-01')", (string) $opt['computation']);
        $this->assertEquals('date', $opt['datatype']);

        $custom_field_definition_6 = $this->createItem(CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'system_name' => 'test_datetime',
            'label' => 'Test datetime',
            'type' => DateTimeType::class,
            'default_value' => '2021-01-01 03:25:15',
        ]);
        $field_id = $custom_field_definition_6->getID();
        $opt = $custom_field_definition_6->getFieldType()->getSearchOption();
        $this->assertEquals($opt_id_offset + $field_id, $opt['id']);
        $this->assertEquals('Test datetime', $opt['name']);
        $this->assertEquals("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`glpi_assets_assets`.`custom_fields`, '$.\\\"{$field_id}\\\"')), '2021-01-01 03:25:15')", (string) $opt['computation']);
        $this->assertEquals('datetime', $opt['datatype']);

        $custom_field_definition_7 = $this->createItem(CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'system_name' => 'test_dropdown',
            'label' => 'Test dropdown',
            'type' => DropdownType::class,
            'itemtype' => \Computer::class,
            'default_value' => '2',
        ]);
        $field_id = $custom_field_definition_7->getID();
        $opt = $custom_field_definition_7->getFieldType()->getSearchOption();
        $this->assertEquals($opt_id_offset + $field_id, $opt['id']);
        $this->assertEquals('Test dropdown', $opt['name']);

        $custom_field_definition_8 = $this->createItem(CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'system_name' => 'test_url',
            'label' => 'Test url',
            'type' => URLType::class,
            'default_value' => 'https://glpi-project.org',
        ]);
        $field_id = $custom_field_definition_8->getID();
        $opt = $custom_field_definition_8->getFieldType()->getSearchOption();
        $this->assertEquals($opt_id_offset + $field_id, $opt['id']);
        $this->assertEquals('Test url', $opt['name']);
        $this->assertEquals("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`glpi_assets_assets`.`custom_fields`, '$.\\\"{$field_id}\\\"')), 'https://glpi-project.org')", (string) $opt['computation']);
        $this->assertEquals('string', $opt['datatype']);

        $custom_field_definition_9 = $this->createItem(CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'system_name' => 'test_bool',
            'label' => 'Test bool',
            'type' => BooleanType::class,
            'default_value' => '1',
        ]);
        $field_id = $custom_field_definition_9->getID();
        $opt = $custom_field_definition_9->getFieldType()->getSearchOption();
        $this->assertEquals($opt_id_offset + $field_id, $opt['id']);
        $this->assertEquals('Test bool', $opt['name']);
        $this->assertEquals("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`glpi_assets_assets`.`custom_fields`, '$.\\\"{$field_id}\\\"')), '1')", (string) $opt['computation']);
        $this->assertEquals('bool', $opt['datatype']);
    }

    public function testSystemNameUnqiue()
    {
        $asset_definition = $this->initAssetDefinition();

        $field = new CustomFieldDefinition();

        $this->assertGreaterThan(0, $field->add([
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'system_name' => 'test',
            'label' => 'Test',
            'type' => StringType::class,
            'default_value' => 'default',
        ]));

        $this->assertFalse($field->add([
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'system_name' => 'test',
            'label' => 'Test',
            'type' => StringType::class,
            'default_value' => 'default',
        ]));

        $this->hasSessionMessages(ERROR, ['The system name must be unique among fields for this asset definition']);
    }

    /**
     * Date and datetime fields should be stored in the database in UTC/GMT and then converted to the user's timezone when read from the database
     */
    public function testDateTimezones()
    {
        global $DB;

        $original_tz = date_default_timezone_get();
        // Hack to prevent the script tz from being changed by the DB access layer
        $DB->use_timezones = true;
        date_default_timezone_set('Etc/GMT-2'); // This is actually ahead of GMT by 2 hours because it uses the POSIX format

        $asset_definition = $this->initAssetDefinition();

        $field = new CustomFieldDefinition();

        $fields_id = $field->add([
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'system_name' => 'test_datetime',
            'label' => 'Test datetime',
            'type' => DateTimeType::class,
            'default_value' => '2021-01-01 03:25:15',
        ]);

        $asset = new ($asset_definition->getAssetClassName());
        $asset->add([
            'entities_id' => $this->getTestRootEntity(true),
            'name' => 'Test asset',
            'custom_test_datetime' => '2024-04-05 07:25:15',
        ]);

        // Ensure the values are stored in the database in UTC
        $it = $DB->request([
            'SELECT' => ['default_value'],
            'FROM' => $field::getTable(),
            'WHERE' => ['id' => $fields_id],
        ]);
        $this->assertEquals(json_encode('2021-01-01 01:25:15'), $it->current()['default_value']);

        $it = $DB->request([
            'SELECT' => [
                QueryFunction::jsonUnquote(
                    expression: QueryFunction::jsonExtract([
                        'glpi_assets_assets.custom_fields',
                        new QueryExpression($DB::quoteValue('$."' . $fields_id . '"')),
                    ]),
                    alias: 'value'
                ),
            ],
            'FROM' => $asset::getTable(),
            'WHERE' => ['id' => $asset->getID()],
        ]);
        $this->assertEquals('2024-04-05 05:25:15', $it->current()['value']);

        // Ensure the values are converted to the user's timezone when read from the database
        $field->getFromDB($fields_id);
        $this->assertEquals('2021-01-01 03:25:15', $field->fields['default_value']);
        $asset->getFromDB($asset->getID());
        $this->assertEquals('2024-04-05 07:25:15', $asset->fields['custom_test_datetime']);

        date_default_timezone_set($original_tz);
    }

    public function testCustomFieldHistory()
    {
        $asset_definition = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasHistoryCapacity::class),
            ]
        );
        $field_1 = new CustomFieldDefinition();
        $field_2 = new CustomFieldDefinition();

        $field_1->add([
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'system_name' => 'test',
            'label' => 'Test',
            'type' => StringType::class,
            'default_value' => 'default',
        ]);
        $field_2->add([
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'system_name' => 'test_two',
            'label' => 'Test2',
            'type' => DropdownType::class,
            'itemtype' => \Computer::class,
        ]);

        $asset = new ($asset_definition->getAssetClassName());
        $asset->add([
            'entities_id' => $this->getTestRootEntity(true),
            'name' => 'Test asset',
        ]);

        $asset->update([
            'id' => $asset->getID(),
            'custom_test' => 'value',
            'custom_test_two' => getItemByTypeName(\Computer::class, '_test_pc01', true),
        ]);

        $this->assertEquals(1, countElementsInTable(\Log::getTable(), [
            'itemtype' => $asset_definition->getAssetClassName(),
            'items_id' => $asset->getID(),
            'id_search_option' => $field_1->getSearchOptionID(),
            'old_value' => 'default',
            'new_value' => 'value',
        ]));

        $this->assertEquals(1, countElementsInTable(\Log::getTable(), [
            'itemtype' => $asset_definition->getAssetClassName(),
            'items_id' => $asset->getID(),
            'id_search_option' => $field_2->getSearchOptionID(),
            'old_value' => '',
            'new_value' => '_test_pc01',
        ]));
    }

    public function testAddTranslationsOnCreate()
    {
        $asset_definition = $this->initAssetDefinition();
        $field = new CustomFieldDefinition();
        $this->assertGreaterThan(0, $field->add([
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'system_name' => 'test',
            'label' => 'Test',
            'type' => StringType::class,
            'translations' => [
                'fr_FR' => 'test_fr',
            ],
        ]));
        $_SESSION['glpilanguage'] = 'fr_FR';
        $this->assertEquals('test_fr', $field->getFriendlyName());
    }

    public function testAddTranslationsOnUpdate()
    {
        $asset_definition = $this->initAssetDefinition();
        $field = new CustomFieldDefinition();
        $this->assertGreaterThan(0, $field->add([
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'system_name' => 'test',
            'label' => 'Test',
            'type' => StringType::class,
        ]));
        $this->assertTrue($field->update([
            'id' => $field->getID(),
            'translations' => [
                'fr_FR' => 'test_fr',
            ],
        ]));
        $_SESSION['glpilanguage'] = 'fr_FR';
        $this->assertEquals('test_fr', $field->getFriendlyName());
    }

    public function testSystemNameUpdate(): void
    {
        $asset_definition = $this->initAssetDefinition();

        $field = $this->createItem(
            CustomFieldDefinition::class,
            [
                'assets_assetdefinitions_id' => $asset_definition->getID(),
                'system_name' => 'test',
                'type' => StringType::class,
            ]
        );

        $updated = $field->update([
            'id' => $field->getID(),
            'system_name' => 'changed',
        ]);
        $this->assertFalse($updated);
        $this->hasSessionMessages(ERROR, ['The system name cannot be changed.']);
    }

    public function testTypeUpdate(): void
    {
        $asset_definition = $this->initAssetDefinition();

        $field = $this->createItem(
            CustomFieldDefinition::class,
            [
                'assets_assetdefinitions_id' => $asset_definition->getID(),
                'system_name' => 'test',
                'type' => StringType::class,
            ]
        );

        $updated = $field->update([
            'id' => $field->getID(),
            'type' => BooleanType::class,
        ]);
        $this->assertFalse($updated);
        $this->hasSessionMessages(ERROR, ['The field type cannot be changed.']);
    }

    public function testSearchDropdownField(): void
    {
        $this->login();

        $opts = SearchOption::getOptionsForItemtype('Glpi\\CustomAsset\\Test01Asset');
        $single_dropdown_opt = null;
        $multiple_dropdown_opt = null;

        foreach ($opts as $num => $opt) {
            if (!is_array($opt)) {
                continue;
            }
            if ($opt['name'] === 'Single Custom Tag') {
                $single_dropdown_opt = $num;
            } elseif ($opt['name'] === 'Multi Custom Tag') {
                $multiple_dropdown_opt = $num;
            }
        }
        $this->assertNotNull($single_dropdown_opt);
        $this->assertNotNull($multiple_dropdown_opt);

        $this->createItem('Glpi\\CustomAsset\\Test01Asset', [
            'entities_id' => $this->getTestRootEntity(true),
            'name' => __FUNCTION__,
            'custom_customtagsingle' => getItemByTypeName('Glpi\\CustomDropdown\\CustomTagDropdown', 'Tag01', true),
            'custom_customtagmulti' => [
                getItemByTypeName('Glpi\\CustomDropdown\\CustomTagDropdown', 'Tag01', true),
                getItemByTypeName('Glpi\\CustomDropdown\\CustomTagDropdown', 'Tag02', true),
            ],
        ], ['custom_customtagsingle', 'custom_customtagmulti']);

        $data = SearchEngine::getData('Glpi\\CustomAsset\\Test01Asset', [
            'criteria' => [
                [
                    'link' => 'AND',
                    'field' => 'name',
                    'searchtype' => 'equals',
                    'value' => __FUNCTION__,
                ],
                [
                    'link' => 'OR',
                    'field' => $single_dropdown_opt,
                    'searchtype' => 'contains',
                    'value' => 'Tag',
                ],
                [
                    'link' => 'OR',
                    'field' => $multiple_dropdown_opt,
                    'searchtype' => 'contains',
                    'value' => 'Tag',
                ],
            ],
        ], [$single_dropdown_opt, $multiple_dropdown_opt]);

        $this->assertCount(1, $data['data']['rows']);
        $row = reset($data['data']['rows'])['raw'];
        $this->assertEquals(
            'Tag01',
            $row['ITEM_Glpi\\CustomAsset\\Test01Asset_' . $single_dropdown_opt]
        );
        $this->assertEquals(
            'Tag01$#$1$$##$$Tag02$#$2',
            $row['ITEM_Glpi\\CustomAsset\\Test01Asset_' . $multiple_dropdown_opt]
        );
    }
}
