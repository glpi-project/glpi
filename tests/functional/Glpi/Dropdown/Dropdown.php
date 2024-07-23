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

namespace tests\units\Glpi\Dropdown;

use DbTestCase;

class Dropdown extends DbTestCase
{
    protected function getByIdProvider(): iterable
    {
        $foo_definition = $this->initDropdownDefinition();
        $foo_classname = $foo_definition->getCustomObjectClassName();

        $bar_definition = $this->initDropdownDefinition();
        $bar_classname = $bar_definition->getCustomObjectClassName();

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

    /**
     * @dataProvider getByIdProvider
     */
    public function testGetById(int $id, string $expected_class, array $expected_fields): void
    {
        $dropdown = \Glpi\Dropdown\Dropdown::getById($id);

        $this->object($dropdown)->isInstanceOf($expected_class);

        foreach ($expected_fields as $name => $value) {
            $this->array($dropdown->fields)->hasKey($name);
            $this->variable($dropdown->fields[$name])->isEqualTo($value);
        }
    }

    public function testPrepareInputDefinition(): void
    {
        $definition = $this->initDropdownDefinition();
        $classname = $definition->getCustomObjectClassName();
        $dropdown = new $classname();

        foreach (['prepareInputForAdd','prepareInputForUpdate'] as $method) {
            // definition is automatically set if missing
            $this->array($dropdown->{$method}(['name' => 'test']))->isEqualTo(['name' => 'test', 'dropdowns_dropdowndefinitions_id' => $definition->getID()]);

            // an exception is thrown if definition is invalid
            $this->exception(
                function () use ($dropdown, $method, $definition) {
                    $dropdown->{$method}(['name' => 'test', 'dropdowns_dropdowndefinitions_id' => $definition->getID() + 1]);
                }
            )->message->contains('Dropdown definition does not match the current concrete class.');
        }
    }

    public function testUpdateWithWrongDefinition(): void
    {
        $definition_1 = $this->initDropdownDefinition();
        $classname_1  = $definition_1->getCustomObjectClassName();
        $definition_2 = $this->initDropdownDefinition();
        $classname_2  = $definition_2->getCustomObjectClassName();

        $dropdown = $this->createItem($classname_1, ['name' => 'new dropdown']);

        $this->exception(
            function () use ($dropdown, $classname_2) {
                $dropdown_2 = new $classname_2();
                $dropdown_2->update(['id' => $dropdown->getID(), 'name' => 'updated']);
            }
        )->message->contains('Dropdown definition cannot be changed.');
    }

    public function testSearchOptionsUnicity(): void
    {
        $definition = $this->initDropdownDefinition();

        $dropdown = $this->createItem($definition->getCustomObjectClassName(), ['name' => 'test dropdown']);

        $this->when(
            function () use ($dropdown) {
                $this->array($dropdown->searchOptions());
            }
        )->error()->notExists();
    }
}
