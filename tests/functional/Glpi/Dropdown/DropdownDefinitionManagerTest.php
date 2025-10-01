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

class DropdownDefinitionManagerTest extends DbTestCase
{
    public function testLoadConcreteClass(): void
    {
        // use a loop to simulate multiple classes
        $mapping = [];
        for ($i = 0; $i < 5; $i++) {
            $system_name = $this->getUniqueString();
            $mapping['Glpi\\CustomDropdown\\' . $system_name . 'Dropdown'] = $this->initDropdownDefinition($system_name);
        }

        foreach ($mapping as $expected_classname => $definition) {
            $this->assertTrue(class_exists($expected_classname));
            $this->assertEquals($definition->fields, $expected_classname::getDefinition()->fields);
        }
    }

    public function testAutoloader(): void
    {
        $this->initDropdownDefinition('Iso3Country_Code'); // use a name with numbers and underscore, to validate it works as expected

        $this->assertTrue(\class_exists('Glpi\CustomDropdown\Iso3Country_CodeDropdown'));
    }

    /**
     * Ensure all asset types are registered in the ticket types configuration.
     *
     * @return void
     */
    public function testStandardDropdownRegistration(): void
    {
        $definition = $this->initDropdownDefinition();
        $class = $definition->getDropdownClassName();

        \Dropdown::resetItemtypesStaticCache();

        $this->login();
        $dropdowns = \Dropdown::getStandardDropdownItemTypes();
        $has_dropdown = false;
        foreach ($dropdowns as $items) {
            if (in_array($class, array_keys($items))) {
                $has_dropdown = true;
                break;
            }
        }
        $this->assertTrue($has_dropdown);
    }
}
