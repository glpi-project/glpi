<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

class Asset extends DbTestCase
{
    protected function getByIdProvider(): iterable
    {
        $foo_definition = $this->initAssetDefinition();
        $foo_classname = $foo_definition->getConcreteClassName();

        $bar_definition = $this->initAssetDefinition();
        $bar_classname = $bar_definition->getConcreteClassName();

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
        $classname = $definition->getConcreteClassName();
        $asset = new $classname();

        foreach (['prepareInputForAdd','prepareInputForUpdate'] as $method) {
            // definition is automatically set if missing
            $this->array($asset->{$method}([]))->isEqualTo(['assets_assetdefinitions_id' => $definition->getID()]);
            $this->array($asset->{$method}(['name' => 'test']))->isEqualTo(['name' => 'test', 'assets_assetdefinitions_id' => $definition->getID()]);

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
        $classname_1  = $definition_1->getConcreteClassName();
        $definition_2 = $this->initAssetDefinition();
        $classname_2  = $definition_2->getConcreteClassName();

        $asset = $this->createItem($classname_1, ['name' => 'new asset']);

        $this->exception(
            function () use ($asset, $classname_2) {
                $asset_2 = new $classname_2();
                $asset_2->update(['id' => $asset->getID(), 'name' => 'updated']);
            }
        )->message->contains('Asset definition cannot be changed.');
    }
}
