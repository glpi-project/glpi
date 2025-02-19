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

namespace tests\units\Glpi\Form\Destination\CommonITILField;

use CommonITILObject;
use DbTestCase;
use Glpi\Form\Destination\CommonITILField\SimpleValueConfig;
use Glpi\Form\Destination\CommonITILField\StatusField;
use Glpi\Form\Form;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;

final class StatusFieldTest extends DbTestCase
{
    use FormTesterTrait;

    public function testStatusWithoutConfig(): void
    {
        // Arrange: create a simple form
        $form = $this->createForm(new FormBuilder());

        // Act: submit form
        $ticket = $this->sendFormAndGetCreatedTicket($form, []);

        // Assert: the ticket should have its default status
        $this->assertEquals(CommonITILObject::INCOMING, $ticket->fields['status']);
    }

    public function testStatusWithDefaultConfig(): void
    {
        // Arrange: create a simple form and set the "default" config
        $form = $this->createForm(new FormBuilder());
        $this->setStatusConfig(
            $form,
            new SimpleValueConfig(StatusField::DEFAULT_STATUS)
        );

        // Act: submit form
        $ticket = $this->sendFormAndGetCreatedTicket($form, []);

        // Assert: the ticket should have its default status
        $this->assertEquals(CommonITILObject::INCOMING, $ticket->fields['status']);
    }

    public function testStatusWithClosedConfig(): void
    {
        // Arrange: create a simple form and set the "closed" config
        $form = $this->createForm(new FormBuilder());
        $this->setStatusConfig(
            $form,
            new SimpleValueConfig(CommonITILObject::CLOSED)
        );

        // Act: submit form
        $ticket = $this->sendFormAndGetCreatedTicket($form, []);

        // Assert: the ticket should have its default status
        $this->assertEquals(CommonITILObject::CLOSED, $ticket->fields['status']);
    }


    private function setStatusConfig(Form $form, SimpleValueConfig $config): void
    {
        // Insert config
        $destinations = $form->getDestinations();
        $this->assertCount(1, $destinations);
        $destination = current($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            ['config' => [StatusField::getKey() => $config->jsonSerialize()]],
            ['config'],
        );
    }
}
