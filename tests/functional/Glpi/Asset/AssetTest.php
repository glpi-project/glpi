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
use Glpi\Asset\Asset;
use Glpi\Asset\AssetDefinition;
use Glpi\Asset\AssetDefinitionManager;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\CapacityInterface;
use Glpi\Asset\CustomFieldDefinition;
use Glpi\Asset\CustomFieldType\DropdownType;
use Glpi\Asset\CustomFieldType\StringType;

class AssetTest extends DbTestCase
{
    protected function getByIdProvider(): iterable
    {
        $foo_definition = $this->initAssetDefinition();
        $foo_classname = $foo_definition->getAssetClassName();

        $bar_definition = $this->initAssetDefinition();
        $bar_classname = $bar_definition->getAssetClassName();

        // Loop to ensure that switching between definition does not cause any issue
        for ($i = 0; $i < 2; $i++) {
            $fields = [
                'name' => 'Foo asset ' . $i,
                'entities_id' => $this->getTestRootEntity(true),
            ];
            $asset = $this->createItem($foo_classname, $fields);
            yield [
                'id'              => $asset->getID(),
                'expected_class'  => $foo_classname,
                'expected_fields' => $fields,
            ];

            $fields = [
                'name' => 'Bar asset ' . $i,
                'entities_id' => $this->getTestRootEntity(true),
            ];
            $asset = $this->createItem($bar_classname, $fields);
            yield [
                'id'              => $asset->getID(),
                'expected_class'  => $bar_classname,
                'expected_fields' => $fields,
            ];
        }
    }

    public function testGetById(): void
    {
        foreach ($this->getByIdProvider() as $row) {
            $id = $row['id'];
            $expected_class = $row['expected_class'];
            $expected_fields = $row['expected_fields'];

            $asset = Asset::getById($id);

            $this->assertInstanceOf($expected_class, $asset);

            foreach ($expected_fields as $name => $value) {
                $this->assertArrayHasKey($name, $asset->fields);
                $this->assertEquals($value, $asset->fields[$name]);
            }
        }
    }

    public function testPrepareInputDefinition(): void
    {
        $definition = $this->initAssetDefinition();
        $classname = $definition->getAssetClassName();
        $asset = new $classname();

        foreach (['prepareInputForAdd','prepareInputForUpdate'] as $method) {
            // definition is automatically set if missing
            $this->assertEquals(
                [
                    'assets_assetdefinitions_id' => $definition->getID(),
                    'custom_fields' => '[]',
                ],
                $asset->{$method}([])
            );
            $this->assertEquals(
                [
                    'name' => 'test',
                    'assets_assetdefinitions_id' => $definition->getID(),
                    'custom_fields' => '[]',
                ],
                $asset->{$method}(['name' => 'test'])
            );

            $this->expectExceptionMessage('Definition does not match the current concrete class.');
            $asset->{$method}(['assets_assetdefinitions_id' => $definition->getID() + 1]);
        }
    }

    public function testUpdateWithWrongDefinition(): void
    {
        $definition_1 = $this->initAssetDefinition();
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition();
        $classname_2  = $definition_2->getAssetClassName();

        $asset = $this->createItem($classname_1, [
            'name' => 'new asset',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        $this->expectExceptionMessage('Definition cannot be changed.');
        $asset_2 = new $classname_2();
        $asset_2->update(['id' => $asset->getID(), 'name' => 'updated']);
    }

    public function testSearchOptionsUnicity(): void
    {
        $capacities = array_map(
            fn(CapacityInterface $capacity) => new Capacity(name: $capacity::class),
            AssetDefinitionManager::getInstance()->getAvailableCapacities()
        );
        $definition = $this->initAssetDefinition(
            capacities: $capacities
        );

        $asset = $this->createItem($definition->getAssetClassName(), [
            'name' => 'test asset',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        $this->assertIsArray($asset->searchOptions());
    }

    public function testPrepareInputForAdd()
    {
        $asset_definition = $this->initAssetDefinition();
        $string_field = $this->createItem(CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'system_name' => 'test_string',
            'label' => 'Test string',
            'type' => StringType::class,
            'default_value' => 'default',
        ]);

        $asset_definition->getFromDB($asset_definition->getID());

        $asset = new ($asset_definition->getAssetClassName());
        $input = $asset->prepareInputForAdd([
            'custom_test_string' => 'test',
        ]);
        $this->assertEquals(
            json_encode([$string_field->getID() => 'test']),
            $input['custom_fields']
        );
    }

    public function testPrepareInputForAddWithReadOnlyField(): void
    {
        $this->login();
        $user_profile = \Session::getCurrentProfile();

        $asset_definition = $this->initAssetDefinition();
        $asset_settings = [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'system_name' => 'test_string',
            'label' => 'Test string',
            'type' => StringType::class,
            'default_value' => 'default',
            'field_options' => ['readonly' => [3, 5, 6]],
        ];

        // We test it with the user having the readonly not applied to his profile
        $string_field = $this->createItem(CustomFieldDefinition::class, $asset_settings);

        $asset_definition->getFromDB($asset_definition->getID());

        $asset = new ($asset_definition->getAssetClassName());
        $input = $asset->prepareInputForAdd([
            'name' => 'custom name',
            'custom_test_string' => 'test',
        ]);
        $this->assertEquals('custom name', $input['name']);
        $this->assertEquals('test', $input['custom_test_string']);
        $this->assertEquals(json_encode([$string_field->getID() => 'test']), $input['custom_fields']);


        // We test it with the user having the readonly apply to his profile
        $asset_settings['system_name'] = 'test2_string';
        $asset_settings['field_options']['readonly'][] = $user_profile->getID();
        $this->createItem(CustomFieldDefinition::class, $asset_settings);

        $asset_definition->getFromDB($asset_definition->getID());
        $update = [
            'fields_display' => ['name'],
            'field_options' => [
                'name' => [
                    'full_width' => '0',
                    'required' => '0',
                    '_readonly' => '1',
                    'readonly' => [$user_profile->getID()],
                ],
            ],
        ];
        $this->updateItem(AssetDefinition::class, $asset_definition->getID(), $update, ['fields_display', 'field_options']);

        $asset = new ($asset_definition->getAssetClassName());
        // We trigger the entities add, so default values are applied
        $asset->add(['add' => true, 'entities_id' => 0]);

        $input = $asset->prepareInputForAdd([
            'name' => 'custom name',
            'custom_test2_string' => 'test',
        ]);
        $this->assertNotEquals('custom name', $input['name']);
        $this->assertEquals($asset->fields['name'], $input['name']);
        $this->assertEquals('default', $input['custom_test2_string']);
    }

    public function testPrepareInputForUpdate()
    {
        $asset_definition = $this->initAssetDefinition();
        $string_field = $this->createItem(CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'system_name' => 'test_string',
            'label' => 'Test string',
            'type' => StringType::class,
            'default_value' => 'default',
        ]);

        $asset_definition->getFromDB($asset_definition->getID());

        $asset = new ($asset_definition->getAssetClassName());
        $input = $asset->prepareInputForUpdate([
            'custom_test_string' => 'test',
        ]);
        $this->assertEquals(
            json_encode([$string_field->getID() => 'test']),
            $input['custom_fields']
        );
    }

    public function testPrepareInputForUpdateWithReadOnlyField(): void
    {
        $this->login();
        $user_profile = \Session::getCurrentProfile();

        $asset_definition = $this->initAssetDefinition();
        $asset_settings = [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'system_name' => 'test_string',
            'label' => 'Test string',
            'type' => StringType::class,
            'default_value' => 'default',
            'field_options' => ['readonly' => [3, 5, 6]],
        ];

        // We test it with the user having the readonly not applied to his profile
        $string_field = $this->createItem(CustomFieldDefinition::class, $asset_settings);

        $asset_definition->getFromDB($asset_definition->getID());

        $asset = new ($asset_definition->getAssetClassName());
        $input = $asset->prepareInputForUpdate([
            'name' => 'custom name',
            'custom_test_string' => 'test',
        ]);
        $this->assertEquals('custom name', $input['name']);
        $this->assertEquals('test', $input['custom_test_string']);
        $this->assertEquals(json_encode([$string_field->getID() => 'test']), $input['custom_fields']);


        // We test it with the user having the readonly apply to his profile
        $asset_settings['system_name'] = 'test2_string';
        $asset_settings['field_options']['readonly'][] = $user_profile->getID();
        $this->createItem(CustomFieldDefinition::class, $asset_settings);

        $asset_definition->getFromDB($asset_definition->getID());
        $update = [
            'fields_display' => ['name'],
            'field_options' => [
                'name' => [
                    'full_width' => '0',
                    'required' => '0',
                    '_readonly' => '1',
                    'readonly' => [$user_profile->getID()],
                ],
            ],
        ];
        $this->updateItem(AssetDefinition::class, $asset_definition->getID(), $update, ['fields_display', 'field_options']);

        $asset = new ($asset_definition->getAssetClassName());
        $input = $asset->prepareInputForUpdate([
            'name' => 'custom name',
            'custom_test2_string' => 'test',
        ]);
        $this->assertArrayNotHasKey('name', $input);
        $this->assertArrayNotHasKey('custom_test2_string', $input);
        $this->assertEquals('[]', $input['custom_fields']);
    }

    public function testGetEmpty()
    {
        $asset_definition = $this->initAssetDefinition();
        $this->createItem(CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'system_name' => 'test_string',
            'label' => 'Test string',
            'type' => StringType::class,
            'default_value' => 'default',
        ]);
        $asset = new ($asset_definition->getAssetClassName());
        $asset->getEmpty();

        $this->assertEquals('default', $asset->fields['custom_test_string']);
    }

    public function testPostGetFromDB()
    {
        $asset_definition = $this->initAssetDefinition();
        $this->createItem(CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'system_name' => 'test_string',
            'label' => 'Test string',
            'type' => StringType::class,
            'default_value' => 'default',
        ]);
        $this->createItem(CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'system_name' => 'test_string_two',
            'label' => 'Test string 2',
            'type' => StringType::class,
            'default_value' => 'default2',
        ]);
        $this->createItem(CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'system_name' => 'test_dropdown',
            'label' => 'Test dropdown',
            'type' => DropdownType::class,
            'itemtype' => \Computer::class,
            'field_options' => ['multiple' => true],
        ], ['field_options']);

        $asset = $this->createItem($asset_definition->getAssetClassName(), [
            'entities_id' => $this->getTestRootEntity(true),
            'custom_test_string' => 'test',
            'custom_test_dropdown' => ['1', '2'],
        ], ['custom_test_dropdown']);
        $this->assertTrue($asset->getFromDB($asset->getID()));
        $this->assertEquals('test', $asset->fields['custom_test_string']);
        $this->assertEquals('default2', $asset->fields['custom_test_string_two']);
        $this->assertEquals(['1', '2'], $asset->fields['custom_test_dropdown']);
    }

    public function testCanView(): void
    {
        $definition = $this->initAssetDefinition();
        $this->login();
        $this->assertTrue((new ($definition->getAssetClassName()))::canView());
        $definition->update([
            'id' => $definition->getID(),
            'is_active' => 0,
        ]);
        $this->assertFalse((new ($definition->getAssetClassName()))::canView());
    }
}
