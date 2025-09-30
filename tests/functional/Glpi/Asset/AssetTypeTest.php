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
use Glpi\Asset\AssetType;

class AssetTypeTest extends DbTestCase
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

    public function testGetById(): void
    {
        foreach ($this->getByIdProvider() as $row) {
            $id = $row['id'];
            $expected_class = $row['expected_class'];
            $expected_fields = $row['expected_fields'];

            $asset_type = AssetType::getById($id);

            $this->assertInstanceOf($expected_class, $asset_type);

            foreach ($expected_fields as $name => $value) {
                $this->assertArrayHasKey($name, $asset_type->fields);
                $this->assertEquals($value, $asset_type->fields[$name]);
            }
        }
    }

    public function testPrepareInputDefinition(): void
    {
        $definition = $this->initAssetDefinition();
        $classname = $definition->getAssetTypeClassName();
        $asset_type = new $classname();

        foreach (['prepareInputForAdd','prepareInputForUpdate'] as $method) {
            // definition is automatically set if missing
            $this->assertEquals(
                ['assets_assetdefinitions_id' => $definition->getID()],
                $asset_type->{$method}([])
            );
            $this->assertEquals(
                ['name' => 'test', 'assets_assetdefinitions_id' => $definition->getID()],
                $asset_type->{$method}(['name' => 'test'])
            );

            // an exception is thrown if definition is invalid
            $this->expectExceptionMessage('Definition does not match the current concrete class.');
            $asset_type->{$method}(['assets_assetdefinitions_id' => $definition->getID() + 1]);
        }
    }

    public function testUpdateWithWrongDefinition(): void
    {
        $definition_1 = $this->initAssetDefinition();
        $classname_1  = $definition_1->getAssetTypeClassName();
        $definition_2 = $this->initAssetDefinition();
        $classname_2  = $definition_2->getAssetTypeClassName();

        $asset_type = $this->createItem($classname_1, ['name' => 'new asset type']);

        $this->expectExceptionMessage('Definition cannot be changed.');
        $asset_2 = new $classname_2();
        $asset_2->update(['id' => $asset_type->getID(), 'name' => 'updated']);
    }

    public function testDelete(): void
    {
        $definition      = $this->initAssetDefinition();
        $asset_classname = $definition->getAssetClassName();
        $type_classname  = $definition->getAssetModelClassName();
        $type_fkey       = $type_classname::getForeignKeyField();

        $root_entity_id = $this->getTestRootEntity(true);

        $type_1 = $this->createItem($type_classname, ['name' => 'Test type 1']);
        $type_2 = $this->createItem($type_classname, ['name' => 'Test type 2']);
        $type_3 = $this->createItem($type_classname, ['name' => 'Test type 3']);

        $asset_1 = $this->createItem(
            $asset_classname,
            [
                'name'        => 'Test asset 1',
                'entities_id' => $root_entity_id,
                $type_fkey    => $type_1->getID(),
            ]
        );
        $asset_2 = $this->createItem(
            $asset_classname,
            [
                'name'        => 'Test asset 2',
                'entities_id' => $root_entity_id,
                $type_fkey    => $type_2->getID(),
            ]
        );
        $asset_3 = $this->createItem(
            $asset_classname,
            [
                'name'        => 'Test asset 3',
                'entities_id' => $root_entity_id,
                $type_fkey    => $type_3->getID(),
            ]
        );

        // Validate init state
        $this->assertEquals($type_1->getID(), $asset_1->fields[$type_fkey]);
        $this->assertEquals($type_2->getID(), $asset_2->fields[$type_fkey]);
        $this->assertEquals($type_3->getID(), $asset_3->fields[$type_fkey]);

        // Delete type 1 and validate that asset 1 is unlinked from the type
        $this->assertTrue($type_1->delete(['id' => $type_1->getID()], force: true));
        $this->assertTrue($asset_1->getFromDB($asset_1->getID())); // Reload linked asset
        $this->assertEquals(0, $asset_1->fields[$type_fkey]);
        $this->assertEquals($type_2->getID(), $asset_2->fields[$type_fkey]);
        $this->assertEquals($type_3->getID(), $asset_3->fields[$type_fkey]);

        // Delete type 2, with a replacement value, and validate that the asset 2 is updated
        $this->assertTrue($type_2->delete(['id' => $type_2->getID(), '_replace_by' => $type_3->getID()], force: true));
        $this->assertTrue($asset_2->getFromDB($asset_2->getID())); // Reload linked asset
        $this->assertEquals(0, $asset_1->fields[$type_fkey]);
        $this->assertEquals($type_3->getID(), $asset_2->fields[$type_fkey]);
        $this->assertEquals($type_3->getID(), $asset_3->fields[$type_fkey]);
    }
}
