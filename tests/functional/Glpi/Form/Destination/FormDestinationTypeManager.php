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

namespace tests\units\Glpi\Form\Destination;

use DbTestCase;

class FormDestinationTypeManager extends DbTestCase
{
    /**
     * Test for the getDestinationTypes method.
     *
     * @return void
     */
    public function testGetDestinationTypes(): void
    {
        $manager = \Glpi\Form\Destination\FormDestinationTypeManager::getInstance();

        // Validate that type list is not empty and that each types are correct
        // AbstractFormDestinationType objects.
        $types = $manager->getDestinationTypes();
        $this->array($types)->isNotEmpty();

        foreach ($types as $type) {
            $this->object($type)->isInstanceOf(\Glpi\Form\Destination\AbstractFormDestinationType::class);
        }
    }

    /**
     * Test for the getDestinationTypesDropdownValues method.
     *
     * @return void
     */
    public function testGetDestinationTypesDropdownValues(): void
    {
        $manager = \Glpi\Form\Destination\FormDestinationTypeManager::getInstance();

        // Validate that each key => value couple are what we expect.
        $values = $manager->getDestinationTypesDropdownValues();
        $this->array($values)->hasSize(count($manager->getDestinationTypes()));

        foreach ($values as $class => $label) {
            $this->string($class)->isNotEmpty();
            $item = new $class();
            $this->object($item)->isInstanceOf(\Glpi\Form\Destination\AbstractFormDestinationType::class);

            $this->string($label)->isNotEmpty();
        }
    }

    /**
     * Test for the getDestinationTypesDropdownValues method.
     *
     * @return void
     */
    public function getDefaultType(): void
    {
        $manager = \Glpi\Form\Destination\FormDestinationTypeManager::getInstance();

        // Not much to test here beside running the function to make sure there
        // are no errors.
        $manager->getDefaultType();
    }
}
