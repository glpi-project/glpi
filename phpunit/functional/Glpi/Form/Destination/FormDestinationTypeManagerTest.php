<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace tests\units\Glpi\Form\Destination;

use DbTestCase;
use Glpi\Form\Destination\AbstractFormDestinationType;
use Glpi\Form\Destination\FormDestinationTypeManager;

final class FormDestinationTypeManagerTest extends DbTestCase
{
    /**
     * Test for the getDestinationTypes method.
     *
     * @return void
     */
    public function testGetDestinationTypes(): void
    {
        $manager = FormDestinationTypeManager::getInstance();

        // Validate that type list is not empty and that each types are correct
        // AbstractFormDestinationType objects.
        $types = $manager->getDestinationTypes();
        $this->assertNotEmpty($types);

        foreach ($types as $type) {
            $this->assertInstanceOf(AbstractFormDestinationType::class, $type);
        }
    }

    /**
     * Test for the getDestinationTypesDropdownValues method.
     *
     * @return void
     */
    public function testGetDestinationTypesDropdownValues(): void
    {
        $manager = FormDestinationTypeManager::getInstance();

        // Validate that each key => value couple are what we expect.
        $values = $manager->getDestinationTypesDropdownValues();
        $this->assertCount(count($manager->getDestinationTypes()), $values);

        foreach ($values as $class => $label) {
            $this->assertNotEmpty($class);
            $item = new $class();
            $this->assertInstanceOf(AbstractFormDestinationType::class, $item);
            $this->assertNotEmpty($label);
        }
    }

    /**
     * Test for the getDestinationTypesDropdownValues method.
     *
     * @return void
     */
    public function getDefaultType(): void
    {
        $manager = FormDestinationTypeManager::getInstance();

        // Not much to test here beside running the function to make sure there
        // are no errors.
        $manager->getDefaultType();
    }
}
