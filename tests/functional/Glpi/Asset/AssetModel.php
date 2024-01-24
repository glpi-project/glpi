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

class AssetModel extends DbTestCase
{
    protected function getByIdProvider(): iterable
    {
        $foo_definition = $this->initAssetDefinition();
        $foo_classname = $foo_definition->getAssetModelClassName();

        $bar_definition = $this->initAssetDefinition();
        $bar_classname = $bar_definition->getAssetModelClassName();

        // Loop to ensure that switching between definition does not cause any issue
        for ($i = 0; $i < 2; $i++) {
            $fields = [
                'name' => 'Foo asset model' . $i,
            ];
            $asset_model = $this->createItem($foo_classname, $fields);
            yield [
                'id'              => $asset_model->getID(),
                'expected_class'  => $foo_classname,
                'expected_fields' => $fields,
            ];

            $fields = [
                'name' => 'Bar asset model' . $i,
            ];
            $asset_model = $this->createItem($bar_classname, $fields);
            yield [
                'id'              => $asset_model->getID(),
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
        $asset_model = \Glpi\Asset\AssetModel::getById($id);

        $this->object($asset_model)->isInstanceOf($expected_class);

        foreach ($expected_fields as $name => $value) {
            $this->array($asset_model->fields)->hasKey($name);
            $this->variable($asset_model->fields[$name])->isEqualTo($value);
        }
    }

    public function testPrepareInputDefinition(): void
    {
        $definition = $this->initAssetDefinition();
        $classname = $definition->getAssetModelClassName();
        $asset_model = new $classname();

        foreach (['prepareInputForAdd','prepareInputForUpdate'] as $method) {
            // definition is automatically set if missing
            $this->array($asset_model->{$method}([]))->isEqualTo(['assets_assetdefinitions_id' => $definition->getID()]);
            $this->array($asset_model->{$method}(['name' => 'test']))->isEqualTo(['name' => 'test', 'assets_assetdefinitions_id' => $definition->getID()]);

            // an exception is thrown if definition is invalid
            $this->exception(
                function () use ($asset_model, $method, $definition) {
                    $asset_model->{$method}(['assets_assetdefinitions_id' => $definition->getID() + 1]);
                }
            )->message->contains('Asset definition does not match the current concrete class.');
        }
    }

    public function testUpdateWithWrongDefinition(): void
    {
        $definition_1 = $this->initAssetDefinition();
        $classname_1  = $definition_1->getAssetModelClassName();
        $definition_2 = $this->initAssetDefinition();
        $classname_2  = $definition_2->getAssetModelClassName();

        $asset_model = $this->createItem($classname_1, ['name' => 'new asset model']);

        $this->exception(
            function () use ($asset_model, $classname_2) {
                $asset_2 = new $classname_2();
                $asset_2->update(['id' => $asset_model->getID(), 'name' => 'updated']);
            }
        )->message->contains('Asset definition cannot be changed.');
    }
}
