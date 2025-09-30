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

namespace tests\units\Glpi\Dropdown;

use DbTestCase;
use Glpi\Dropdown\Dropdown;

class DropdownTest extends DbTestCase
{
    protected function getByIdProvider(): iterable
    {
        $foo_definition = $this->initDropdownDefinition();
        $foo_classname = $foo_definition->getDropdownClassName();

        $bar_definition = $this->initDropdownDefinition();
        $bar_classname = $bar_definition->getDropdownClassName();

        // Loop to ensure that switching between definition does not cause any issue
        for ($i = 0; $i < 2; $i++) {
            $fields = [
                'name' => 'Foo dropdown ' . $i,
            ];
            $dropdown = $this->createItem($foo_classname, $fields);
            yield [
                'id'              => $dropdown->getID(),
                'expected_class'  => $foo_classname,
                'expected_fields' => $fields,
            ];

            $fields = [
                'name' => 'Bar dropdown ' . $i,
            ];
            $dropdown = $this->createItem($bar_classname, $fields);
            yield [
                'id'              => $dropdown->getID(),
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

            $dropdown = Dropdown::getById($id);

            $this->assertInstanceOf($expected_class, $dropdown);

            foreach ($expected_fields as $name => $value) {
                $this->assertArrayHasKey($name, $dropdown->fields);
                $this->assertEquals($value, $dropdown->fields[$name]);
            }
        }
    }

    public function testPrepareInputDefinition(): void
    {
        $definition = $this->initDropdownDefinition();
        $classname = $definition->getDropdownClassName();
        $dropdown = new $classname();

        foreach (['prepareInputForAdd','prepareInputForUpdate'] as $method) {
            $dropdown->getEmpty();
            // definition is automatically set if missing
            $this->assertEquals(
                [
                    'name' => 'test',
                    'completename' => 'test',
                    'dropdowns_dropdowndefinitions_id' => $definition->getID(),
                    'dropdowns_dropdowns_id' => 0,
                    'level' => 1,
                ],
                $dropdown->{$method}(['name' => 'test'])
            );
        }
    }

    public function testprepareInputForAddDefinitionWException(): void
    {
        $definition = $this->initDropdownDefinition();
        $classname = $definition->getDropdownClassName();
        $dropdown = new $classname();

        // an exception is thrown if definition is invalid
        $this->expectExceptionMessage('Definition does not match the current concrete class.');
        $dropdown->prepareInputForAdd(['name' => 'test', 'dropdowns_dropdowndefinitions_id' => $definition->getID() + 1]);
    }

    public function testprepareInputForUpdateDefinitionWException(): void
    {
        $definition = $this->initDropdownDefinition();
        $classname = $definition->getDropdownClassName();
        $dropdown = new $classname();

        $dropdown->getEmpty();
        // definition is automatically set if missing
        $this->assertEquals(
            [
                'name' => 'test',
                'completename' => 'test',
                'dropdowns_dropdowndefinitions_id' => $definition->getID(),
                'dropdowns_dropdowns_id' => 0,
                'level' => 1,
            ],
            $dropdown->prepareInputForUpdate(['name' => 'test'])
        );

        // an exception is thrown if definition is invalid
        $this->expectExceptionMessage('Definition does not match the current concrete class.');
        $dropdown->prepareInputForUpdate(['name' => 'test', 'dropdowns_dropdowndefinitions_id' => $definition->getID() + 1]);
    }
    public function testUpdateWithWrongDefinition(): void
    {
        $definition_1 = $this->initDropdownDefinition();
        $classname_1  = $definition_1->getDropdownClassName();
        $definition_2 = $this->initDropdownDefinition();
        $classname_2  = $definition_2->getDropdownClassName();

        $dropdown = $this->createItem($classname_1, ['name' => 'new dropdown']);

        $this->expectExceptionMessage('Definition cannot be changed.');
        $dropdown_2 = new $classname_2();
        $dropdown_2->update(['id' => $dropdown->getID(), 'name' => 'updated']);
    }

    public function testSearchOptionsUnicity(): void
    {
        $definition = $this->initDropdownDefinition();

        $dropdown = $this->createItem($definition->getDropdownClassName(), ['name' => 'test dropdown']);
        $this->assertIsArray($dropdown->searchOptions());
    }

    public function testCanView(): void
    {
        $definition = $this->initDropdownDefinition();
        $this->login();
        $this->assertTrue((new ($definition->getDropdownClassName()))::canView());
        $definition->update([
            'id' => $definition->getID(),
            'is_active' => 0,
        ]);
        $this->assertFalse((new ($definition->getDropdownClassName()))::canView());
    }
}
