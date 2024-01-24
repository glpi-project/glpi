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

class AssetType extends DbTestCase
{
    protected function getByIdProvider(): iterable
    {
        $foo_definition = $this->initAssetDefinition();
        $foo_classname = $foo_definition->getAssetTypeClassName();

        $bar_definition = $this->initAssetDefinition();
        $bar_classname = $bar_definition->getAssetTypeClassName();

        // Loop to ensure that switching between definition does not cause any issue
        for ($i = 0; $i < 2; $i++) {
            $fields = [
                'name' => 'Foo asset type' . $i,
            ];
            $asset_type = $this->createItem($foo_classname, $fields);
            yield [
                'id'              => $asset_type->getID(),
                'expected_class'  => $foo_classname,
                'expected_fields' => $fields,
            ];

            $fields = [
                'name' => 'Bar asset type' . $i,
            ];
            $asset_type = $this->createItem($bar_classname, $fields);
            yield [
                'id'              => $asset_type->getID(),
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
        $asset_type = \Glpi\Asset\AssetType::getById($id);

        $this->object($asset_type)->isInstanceOf($expected_class);

        foreach ($expected_fields as $name => $value) {
            $this->array($asset_type->fields)->hasKey($name);
            $this->variable($asset_type->fields[$name])->isEqualTo($value);
        }
    }

    public function testPrepareInputDefinition(): void
    {
        $definition = $this->initAssetDefinition();
        $classname = $definition->getAssetTypeClassName();
        $asset_type = new $classname();

        foreach (['prepareInputForAdd','prepareInputForUpdate'] as $method) {
            // definition is automatically set if missing
            $this->array($asset_type->{$method}([]))->isEqualTo(['assets_assetdefinitions_id' => $definition->getID()]);
            $this->array($asset_type->{$method}(['name' => 'test']))->isEqualTo(['name' => 'test', 'assets_assetdefinitions_id' => $definition->getID()]);

            // an exception is thrown if definition is invalid
            $this->exception(
                function () use ($asset_type, $method, $definition) {
                    $asset_type->{$method}(['assets_assetdefinitions_id' => $definition->getID() + 1]);
                }
            )->message->contains('Asset definition does not match the current concrete class.');
        }
    }

    public function testUpdateWithWrongDefinition(): void
    {
        $definition_1 = $this->initAssetDefinition();
        $classname_1  = $definition_1->getAssetTypeClassName();
        $definition_2 = $this->initAssetDefinition();
        $classname_2  = $definition_2->getAssetTypeClassName();

        $asset_type = $this->createItem($classname_1, ['name' => 'new asset type']);

        $this->exception(
            function () use ($asset_type, $classname_2) {
                $asset_2 = new $classname_2();
                $asset_2->update(['id' => $asset_type->getID(), 'name' => 'updated']);
            }
        )->message->contains('Asset definition cannot be changed.');
    }
}
