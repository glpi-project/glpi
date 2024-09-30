<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

class CustomFieldDefinition extends DbTestCase
{
    /**
     * Ensure custom fields are removed from assets when the custom field definition is removed
     * @return void
     */
    public function testCleanDBOnPurge()
    {
        $asset_definition = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
            ]
        );
        $asset_classname = $asset_definition->getAssetClassName();

        $custom_field_definition = $this->createItem(\Glpi\Asset\CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'name' => 'test_string',
            'label' => 'Test string',
            'type' => StringType::class,
            'default_value' => 'default',
        ]);

        $custom_field_definition_2 = $this->createItem(\Glpi\Asset\CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'name' => 'test_text',
            'label' => 'Test text',
            'type' => TextType::class,
            'default_value' => 'default text',
        ]);

        $asset = $this->createItem($asset_classname, [
            'entities_id' => $this->getTestRootEntity(true),
            'name' => 'Test asset',
            'custom_test_string' => 'value',
            'custom_test_text' => 'value2',
        ], ['custom_test_string']);

        $custom_field_definition->delete(['id' => $custom_field_definition->getID()]);

        $asset->getFromDB($asset->getID());
        $this->string($asset->fields['custom_fields'])->isEqualTo('{"' . $custom_field_definition_2->getID() . '": "value2"}');
    }

    public function testGetAllowedDropdownItemtypes()
    {
        $allowed_itemtypes = \Glpi\Asset\AssetDefinitionManager::getInstance()->getAllowedDropdownItemtypes();
        $this->array($allowed_itemtypes)->isNotEmpty();
        foreach ($allowed_itemtypes as $group => $opts) {
            $this->array($opts)->isNotEmpty();
            $this->boolean(is_string($group))->isTrue();
            foreach ($opts as $classname => $label) {
                $this->boolean(is_subclass_of($classname, \CommonDBTM::class))->isTrue();
                $this->string($label)->isNotEmpty();
            }
        }
    }

    protected function validateValueProvider()
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
        ];
    }

    /**
     * @dataProvider validateValueProvider
     */
    public function testValidateValue($field_params, $given_value, $expected_value, $is_valid)
    {
        $value = $given_value;
        $custom_field = new \Glpi\Asset\CustomFieldDefinition();
        $custom_field->fields = $field_params;
        if (!$is_valid) {
            $this->exception(static fn () => $custom_field->getFieldType()->normalizeValue($value));
            $this->boolean($this->exception instanceof \InvalidArgumentException)->isTrue();
        } else {
            $this->variable($custom_field->getFieldType()->normalizeValue($value))->isEqualTo($expected_value);
        }
    }

    public function testGetEmpty()
    {
        $custom_field = new \Glpi\Asset\CustomFieldDefinition();
        $custom_field->getEmpty();
        $this->array($custom_field->fields['field_options'])->isEmpty();
    }

    public function testGetSearchOption()
    {
        $opt_id_offset = 45000;
        $asset_definition = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
            ]
        );

        $custom_field_definition = $this->createItem(\Glpi\Asset\CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'name' => 'test_string',
            'label' => 'Test string',
            'type' => StringType::class,
            'default_value' => 'default',
        ]);
        $field_id = $custom_field_definition->getID();
        $opt = $custom_field_definition->getFieldType()->getSearchOption();
        $this->integer($opt['id'])->isEqualTo($opt_id_offset + $field_id);
        $this->string($opt['name'])->isEqualTo('Test string');
        $this->string((string) $opt['computation'])->isEqualTo("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`glpi_assets_assets`.`custom_fields`, '$.\\\"{$field_id}\\\"')), 'default')");
        $this->string($opt['datatype'])->isEqualTo('string');

        $custom_field_definition_2 = $this->createItem(\Glpi\Asset\CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'name' => 'test_text',
            'label' => 'Test text',
            'type' => TextType::class,
            'default_value' => 'default text',
        ]);
        $field_id = $custom_field_definition_2->getID();
        $opt = $custom_field_definition_2->getFieldType()->getSearchOption();
        $this->integer($opt['id'])->isEqualTo($opt_id_offset + $field_id);
        $this->string($opt['name'])->isEqualTo('Test text');
        $this->string((string) $opt['computation'])->isEqualTo("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`glpi_assets_assets`.`custom_fields`, '$.\\\"{$field_id}\\\"')), 'default text')");
        $this->string($opt['datatype'])->isEqualTo('text');
        $this->array($opt)->notHasKey('htmltext');

        $custom_field_definition_4 = $this->createItem(\Glpi\Asset\CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'name' => 'test_number',
            'label' => 'Test number',
            'type' => NumberType::class,
            'default_value' => 420,
        ]);
        $field_id = $custom_field_definition_4->getID();
        $opt = $custom_field_definition_4->getFieldType()->getSearchOption();
        $this->integer($opt['id'])->isEqualTo($opt_id_offset + $field_id);
        $this->string($opt['name'])->isEqualTo('Test number');
        $this->string((string) $opt['computation'])->isEqualTo("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`glpi_assets_assets`.`custom_fields`, '$.\\\"{$field_id}\\\"')), '420')");
        $this->string($opt['datatype'])->isEqualTo('number');

        $custom_field_definition_5 = $this->createItem(\Glpi\Asset\CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'name' => 'test_date',
            'label' => 'Test date',
            'type' => DateType::class,
            'default_value' => '2021-01-01',
        ]);
        $field_id = $custom_field_definition_5->getID();
        $opt = $custom_field_definition_5->getFieldType()->getSearchOption();
        $this->integer($opt['id'])->isEqualTo($opt_id_offset + $field_id);
        $this->string($opt['name'])->isEqualTo('Test date');
        $this->string((string) $opt['computation'])->isEqualTo("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`glpi_assets_assets`.`custom_fields`, '$.\\\"{$field_id}\\\"')), '2021-01-01')");
        $this->string($opt['datatype'])->isEqualTo('date');

        $custom_field_definition_6 = $this->createItem(\Glpi\Asset\CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'name' => 'test_datetime',
            'label' => 'Test datetime',
            'type' => DateTimeType::class,
            'default_value' => '2021-01-01 03:25:15',
        ]);
        $field_id = $custom_field_definition_6->getID();
        $opt = $custom_field_definition_6->getFieldType()->getSearchOption();
        $this->integer($opt['id'])->isEqualTo($opt_id_offset + $field_id);
        $this->string($opt['name'])->isEqualTo('Test datetime');
        $this->string((string) $opt['computation'])->isEqualTo("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`glpi_assets_assets`.`custom_fields`, '$.\\\"{$field_id}\\\"')), '2021-01-01 03:25:15')");
        $this->string($opt['datatype'])->isEqualTo('datetime');

        $custom_field_definition_7 = $this->createItem(\Glpi\Asset\CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'name' => 'test_dropdown',
            'label' => 'Test dropdown',
            'type' => DropdownType::class,
            'itemtype' => \Computer::class,
            'default_value' => '2',
        ]);
        $field_id = $custom_field_definition_7->getID();
        $opt = $custom_field_definition_7->getFieldType()->getSearchOption();
        $this->integer($opt['id'])->isEqualTo($opt_id_offset + $field_id);
        $this->string($opt['name'])->isEqualTo('Test dropdown');

        $custom_field_definition_8 = $this->createItem(\Glpi\Asset\CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'name' => 'test_url',
            'label' => 'Test url',
            'type' => URLType::class,
            'default_value' => 'https://glpi-project.org',
        ]);
        $field_id = $custom_field_definition_8->getID();
        $opt = $custom_field_definition_8->getFieldType()->getSearchOption();
        $this->integer($opt['id'])->isEqualTo($opt_id_offset + $field_id);
        $this->string($opt['name'])->isEqualTo('Test url');
        $this->string((string) $opt['computation'])->isEqualTo("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`glpi_assets_assets`.`custom_fields`, '$.\\\"{$field_id}\\\"')), 'https://glpi-project.org')");
        $this->string($opt['datatype'])->isEqualTo('string');

        $custom_field_definition_9 = $this->createItem(\Glpi\Asset\CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'name' => 'test_bool',
            'label' => 'Test bool',
            'type' => BooleanType::class,
            'default_value' => '1',
        ]);
        $field_id = $custom_field_definition_9->getID();
        $opt = $custom_field_definition_9->getFieldType()->getSearchOption();
        $this->integer($opt['id'])->isEqualTo($opt_id_offset + $field_id);
        $this->string($opt['name'])->isEqualTo('Test bool');
        $this->string((string) $opt['computation'])->isEqualTo("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`glpi_assets_assets`.`custom_fields`, '$.\\\"{$field_id}\\\"')), '1')");
        $this->string($opt['datatype'])->isEqualTo('bool');
    }

    public function testSystemNameUnqiue()
    {
        $asset_definition = $this->initAssetDefinition();

        $field = new \Glpi\Asset\CustomFieldDefinition();

        $this->integer($field->add([
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'name' => 'test',
            'label' => 'Test',
            'type' => StringType::class,
            'default_value' => 'default',
        ]))->isGreaterThan(0);

        $this->boolean($field->add([
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'name' => 'test',
            'label' => 'Test',
            'type' => StringType::class,
            'default_value' => 'default',
        ]))->isFalse();

        $this->hasSessionMessages(ERROR, ['The system name must be unique among fields for this asset definition']);
    }

    /**
     * Date and datetime fields should be stored in the database in UTC/GMT and then converted to the user's timezone when read from the database
     */
    public function testDateTimezones()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $original_tz = date_default_timezone_get();
        // Hack to prevent the script tz from being changed by the DB access layer
        $DB->use_timezones = true;
        date_default_timezone_set('Etc/GMT-2'); // This is actually ahead of GMT by 2 hours because it uses the POSIX format

        $asset_definition = $this->initAssetDefinition();

        $field = new \Glpi\Asset\CustomFieldDefinition();

        $fields_id = $field->add([
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'name' => 'test_datetime',
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
        $this->string($it->current()['default_value'])->isEqualTo(json_encode('2021-01-01 01:25:15'));

        $it = $DB->request([
            'SELECT' => [
                QueryFunction::jsonUnquote(
                    expression: QueryFunction::jsonExtract([
                        'glpi_assets_assets.custom_fields',
                        new QueryExpression($DB::quoteValue('$."' . $fields_id . '"'))
                    ]),
                    alias: 'value'
                ),
            ],
            'FROM' => $asset::getTable(),
            'WHERE' => ['id' => $asset->getID()],
        ]);
        $this->string($it->current()['value'])->isEqualTo('2024-04-05 05:25:15');

        // Ensure the values are converted to the user's timezone when read from the database
        $field->getFromDB($fields_id);
        $this->string($field->fields['default_value'])->isEqualTo('2021-01-01 03:25:15');
        $asset->getFromDB($asset->getID());
        $this->string($asset->fields['custom_test_datetime'])->isEqualTo('2024-04-05 07:25:15');

        date_default_timezone_set($original_tz);
    }

    public function testCustomFieldHistory()
    {
        $asset_definition = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
            ]
        );
        $field_1 = new \Glpi\Asset\CustomFieldDefinition();
        $field_2 = new \Glpi\Asset\CustomFieldDefinition();

        $field_1->add([
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'name' => 'test',
            'label' => 'Test',
            'type' => StringType::class,
            'default_value' => 'default',
        ]);
        $field_2->add([
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'name' => 'test_two',
            'label' => 'Test2',
            'type' => DropdownType::class,
            'itemtype' => \Computer::class,
        ]);

        $asset = new ($asset_definition->getAssetClassName());
        $asset->add([
            'entities_id' => $this->getTestRootEntity(true),
            'name' => 'Test asset'
        ]);

        $asset->update([
            'id' => $asset->getID(),
            'custom_test' => 'value',
            'custom_test_two' => getItemByTypeName(\Computer::class, '_test_pc01', true),
        ]);

        $this->integer(countElementsInTable(\Log::getTable(), [
            'itemtype' => $asset_definition->getAssetClassName(),
            'items_id' => $asset->getID(),
            'id_search_option' => $field_1->getSearchOptionID(),
            'old_value' => 'default',
            'new_value' => 'value',
        ]))->isIdenticalTo(1);

        $this->integer(countElementsInTable(\Log::getTable(), [
            'itemtype' => $asset_definition->getAssetClassName(),
            'items_id' => $asset->getID(),
            'id_search_option' => $field_2->getSearchOptionID(),
            'old_value' => '',
            'new_value' => '_test_pc01',
        ]))->isIdenticalTo(1);
    }
}
