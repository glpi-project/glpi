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

namespace tests\units\Glpi\Asset;

use DbTestCase;
use Glpi\Asset\AssetDefinitionManager;

class Asset extends DbTestCase
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
            ];
            $asset = $this->createItem($foo_classname, $fields);
            yield [
                'id'              => $asset->getID(),
                'expected_class'  => $foo_classname,
                'expected_fields' => $fields,
            ];

            $fields = [
                'name' => 'Bar asset ' . $i,
            ];
            $asset = $this->createItem($bar_classname, $fields);
            yield [
                'id'              => $asset->getID(),
                'expected_class'  => $bar_classname,
                'expected_fields' => $fields,
            ];
        }
    }

    /**
     * @dataProvider getByIdProvider
     */
    public function testGetById(int $id, string $expected_class, array $expected_fields): void
    {
        $asset = \Glpi\Asset\Asset::getById($id);

        $this->object($asset)->isInstanceOf($expected_class);

        foreach ($expected_fields as $name => $value) {
            $this->array($asset->fields)->hasKey($name);
            $this->variable($asset->fields[$name])->isEqualTo($value);
        }
    }

    public function testPrepareInputDefinition(): void
    {
        $definition = $this->initAssetDefinition();
        $classname = $definition->getAssetClassName();
        $asset = new $classname();

        foreach (['prepareInputForAdd','prepareInputForUpdate'] as $method) {
            // definition is automatically set if missing
            $this->array($asset->{$method}([]))->isEqualTo([
                'assets_assetdefinitions_id' => $definition->getID(),
                'custom_fields' => '[]'
            ]);
            $this->array($asset->{$method}(['name' => 'test']))->isEqualTo([
                'name' => 'test',
                'assets_assetdefinitions_id' => $definition->getID(),
                'custom_fields' => '[]'
            ]);

            // an exception is thrown if definition is invalid
            $this->exception(
                function () use ($asset, $method, $definition) {
                    $asset->{$method}(['assets_assetdefinitions_id' => $definition->getID() + 1]);
                }
            )->message->contains('Asset definition does not match the current concrete class.');
        }
    }

    public function testUpdateWithWrongDefinition(): void
    {
        $definition_1 = $this->initAssetDefinition();
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition();
        $classname_2  = $definition_2->getAssetClassName();

        $asset = $this->createItem($classname_1, ['name' => 'new asset']);

        $this->exception(
            function () use ($asset, $classname_2) {
                $asset_2 = new $classname_2();
                $asset_2->update(['id' => $asset->getID(), 'name' => 'updated']);
            }
        )->message->contains('Asset definition cannot be changed.');
    }

    public function testSearchOptionsUnicity(): void
    {
        $capacities = AssetDefinitionManager::getInstance()->getAvailableCapacities();
        $definition = $this->initAssetDefinition(
            capacities: array_keys($capacities)
        );

        $asset = $this->createItem($definition->getAssetClassName(), ['name' => 'test asset']);

        $this->when(
            function () use ($asset) {
                $this->array($asset->searchOptions());
            }
        )->error()->notExists();
    }

    public function testAddWhereDropdownType()
    {
        $asset_definition = $this->initAssetDefinition();
        $custom_field_definition = $this->createItem(\Glpi\Asset\CustomField::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'name' => 'test_dropdown',
            'label' => 'Test dropdown',
            'type' => 'dropdown',
            'itemtype' => \Computer::class,
            'default_value' => '2',
        ]);
        $field_id = $custom_field_definition->getID();

        $this->string(\Glpi\Asset\Asset::addWhere('AND', false, '', 0, '', '5', $custom_field_definition->getSearchOption()))
            ->isEqualTo("AND ('5' = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`glpi_assets_assets`.`custom_fields`, '$.\\\"{$field_id}\\\"')), '2'))");
        $this->string(\Glpi\Asset\Asset::addWhere('OR', false, '', 0, '', '5', $custom_field_definition->getSearchOption()))
            ->isEqualTo("OR ('5' = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`glpi_assets_assets`.`custom_fields`, '$.\\\"{$field_id}\\\"')), '2'))");
        $this->string(\Glpi\Asset\Asset::addWhere('AND', true, '', 0, '', '5', $custom_field_definition->getSearchOption()))
            ->isEqualTo("AND ('5' != COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`glpi_assets_assets`.`custom_fields`, '$.\\\"{$field_id}\\\"')), '2'))");
    }

    public function testPrepareInputForAdd()
    {
        $asset_definition = $this->initAssetDefinition();
        $string_field = $this->createItem(\Glpi\Asset\CustomField::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'name' => 'test_string',
            'label' => 'Test string',
            'type' => 'string',
            'default_value' => 'default',
        ]);
        $placeholder_field = $this->createItem(\Glpi\Asset\CustomField::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'name' => 'test_placeholder',
            'label' => 'Test placeholder',
            'type' => 'placeholder',
        ], ['name']);

        $asset_definition->getFromDB($asset_definition->getID());

        $asset = new ($asset_definition->getAssetClassName());
        $input = $asset->prepareInputForAdd([
            'custom_test_string' => 'test',
            $placeholder_field->fields['name'] => 'placeholder',
        ]);
        $this->string($input['custom_fields'])
            ->isEqualTo(json_encode([$string_field->getID() => 'test']));
    }

    public function testPrepareInputForUpdate()
    {
        $asset_definition = $this->initAssetDefinition();
        $string_field = $this->createItem(\Glpi\Asset\CustomField::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'name' => 'test_string',
            'label' => 'Test string',
            'type' => 'string',
            'default_value' => 'default',
        ]);
        $placeholder_field = $this->createItem(\Glpi\Asset\CustomField::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'name' => 'test_placeholder',
            'label' => 'Test placeholder',
            'type' => 'placeholder',
        ], ['name']);

        $asset_definition->getFromDB($asset_definition->getID());

        $asset = new ($asset_definition->getAssetClassName());
        $input = $asset->prepareInputForUpdate([
            'custom_test_string' => 'test',
            $placeholder_field->fields['name'] => 'placeholder',
        ]);
        $this->string($input['custom_fields'])
            ->isEqualTo(json_encode([$string_field->getID() => 'test']));
    }

    public function testGetEmpty()
    {
        $asset_definition = $this->initAssetDefinition();
        $this->createItem(\Glpi\Asset\CustomField::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'name' => 'test_string',
            'label' => 'Test string',
            'type' => 'string',
            'default_value' => 'default',
        ]);
        $placeholder_field = $this->createItem(\Glpi\Asset\CustomField::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'name' => 'test_placeholder',
            'label' => 'Test placeholder',
            'type' => 'placeholder',
        ], ['name']);
        $asset = new ($asset_definition->getAssetClassName());
        $asset->getEmpty();

        $this->string($asset->fields['custom_test_string'])->isEqualTo('default');
        $this->array($asset->fields)->notHasKey('custom_' . $placeholder_field->fields['name']);
    }

    public function testPostGetFromDB()
    {
        $asset_definition = $this->initAssetDefinition();
        $this->createItem(\Glpi\Asset\CustomField::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'name' => 'test_string',
            'label' => 'Test string',
            'type' => 'string',
            'default_value' => 'default',
        ]);
        $this->createItem(\Glpi\Asset\CustomField::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'name' => 'test_string_two',
            'label' => 'Test string 2',
            'type' => 'string',
            'default_value' => 'default2',
        ]);
        $this->createItem(\Glpi\Asset\CustomField::class, [
            'assets_assetdefinitions_id' => $asset_definition->getID(),
            'name' => 'test_dropdown',
            'label' => 'Test dropdown',
            'type' => 'dropdown',
            'field_options' => ['multiple' => true]
        ], ['field_options']);

        $asset = $this->createItem($asset_definition->getAssetClassName(), [
            'custom_test_string' => 'test',
            'custom_test_dropdown' => ['1', '2'],
        ], ['custom_test_dropdown']);
        $this->boolean($asset->getFromDB($asset->getID()))->isTrue();
        $this->string($asset->fields['custom_test_string'])->isEqualTo('test');
        $this->string($asset->fields['custom_test_string_two'])->isEqualTo('default2');
        $this->array($asset->fields['custom_test_dropdown'])->isEqualTo(['1', '2']);
    }
}
