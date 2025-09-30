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

namespace tests\units\Glpi\Form\Destination;

use DbTestCase;
use Glpi\Form\Condition\CreationStrategy;
use Glpi\Form\Destination\FormDestinationInterface;
use Glpi\Form\Destination\FormDestinationManager;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use GlpiPlugin\Tester\Form\ComputerDestination;

final class FormDestinationManagerTest extends DbTestCase
{
    use FormTesterTrait;

    /**
     * Test for the getDestinationTypes method.
     *
     * @return void
     */
    public function testGetDestinationTypes(): void
    {
        $manager = FormDestinationManager::getInstance();

        // Validate that type list is not empty and that each types are correct
        // AbstractFormDestinationType objects.
        $types = $manager->getDestinationTypes();
        $this->assertNotEmpty($types);

        foreach ($types as $type) {
            $this->assertInstanceOf(FormDestinationInterface::class, $type);
        }
    }

    /**
     * Test for the getDestinationTypesDropdownValues method.
     *
     * @return void
     */
    public function testGetDestinationTypesDropdownValues(): void
    {
        $manager = FormDestinationManager::getInstance();

        // Validate that each key => value couple are what we expect.
        $values = $manager->getDestinationTypesDropdownValues();
        $this->assertCount(count($manager->getDestinationTypes()), $values);

        foreach ($values as $class => $label) {
            $this->assertNotEmpty($class);
            $item = new $class();
            $this->assertInstanceOf(FormDestinationInterface::class, $item);
            $this->assertNotEmpty($label);
        }

        // Make sure plugin types are found
        $this->assertArrayHasKey(ComputerDestination::class, $values);
    }

    /**
     * Test for the getDefaultType method.
     *
     * @return void
     */
    public function testGetDefaultType(): void
    {
        $manager = FormDestinationManager::getInstance();

        // Not much to test here beside running the function to make sure there
        // are no errors.
        $manager->getDefaultType();
    }

    public function testDefaultFormHasNoWarnings(): void
    {
        // Arrange: create a form with default values
        $form = $this->createForm(new FormBuilder());

        // Act: get warnings for this form
        $warnings = FormDestinationManager::getInstance()->getWarnings($form);

        // Assert: there should be no warnings
        $this->assertEmpty($warnings);
    }

    public function testFormWithoutDestinationHasWarning(): void
    {
        // Arrange: create a form and remove its default destination
        $form = $this->createForm(new FormBuilder());
        $destinations = $form->getDestinations();
        foreach ($destinations as $destination) {
            $this->deleteItem($destination::class, $destination->getId(), true);
        }

        // Act: get warnings for this form
        $warnings = FormDestinationManager::getInstance()->getWarnings($form);

        // Assert: there should be a single warning
        $message = "This form is invalid, it must create at least one item.";
        $this->assertEquals([$message], $warnings);
    }

    public function testFormWithOnlyConditionalDestinationHasWarning(): void
    {
        // Arrange: create a form and make its default destinations as conditional
        $form = $this->createForm(new FormBuilder());
        $destinations = $form->getDestinations();
        foreach ($destinations as $destination) {
            $this->updateItem($destination::class, $destination->getId(), [
                'creation_strategy' => CreationStrategy::CREATED_IF->value,
            ]);
        }

        // Act: get warnings for this form
        $warnings = FormDestinationManager::getInstance()->getWarnings($form);

        // Assert: there should be a single warning
        $message = "You have defined conditions for all the items below. This may be dangerous, please make sure that in every situation at least one item will be created.";
        $this->assertEquals([$message], $warnings);
    }
}
