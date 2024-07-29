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

namespace tests\units\Glpi\Form\Destination\CommonITILField;

use DbTestCase;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Destination\CommonITILField\TemplateFieldConfig;
use Glpi\Form\Destination\CommonITILField\TemplateFieldStrategy;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Form;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use TicketTemplate;

final class TemplateFieldTest extends DbTestCase
{
    use FormTesterTrait;

    public function testDefaultTemplate(): void
    {
        $this->checkTemplateFieldConfiguration(
            form: $this->createAndGetFormWithTicketDestination(),
            config: new TemplateFieldConfig(
                TemplateFieldStrategy::DEFAULT_TEMPLATE
            )
        );
    }

    public function testSpecificTemplate(): void
    {
        $form = $this->createAndGetFormWithTicketDestination();

        // Create ticket template
        $ticket_template = $this->createItem(
            TicketTemplate::class,
            ['name' => 'Template 1']
        );

        // Using created template
        $this->checkTemplateFieldConfiguration(
            form: $form,
            config: new TemplateFieldConfig(
                strategy: TemplateFieldStrategy::SPECIFIC_TEMPLATE,
                specific_template_id: $ticket_template->getID()
            ),
            expected_tickettemplates_id: $ticket_template->getID()
        );
    }

    private function checkTemplateFieldConfiguration(
        Form $form,
        TemplateFieldConfig $config,
        int $expected_tickettemplates_id = 0
    ): void {
        // Insert config
        $destinations = $form->getDestinations();
        $this->assertCount(1, $destinations);
        $destination = current($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            ['config' => ['template' => $config->jsonSerialize()]],
            ["config"],
        );

        // Submit form
        $answers_handler = AnswersHandler::getInstance();
        $answers = $answers_handler->saveAnswers(
            $form,
            [],
            getItemByTypeName(\User::class, TU_USER, true)
        );

        // Get created ticket
        $created_items = $answers->getCreatedItems();
        $this->assertCount(1, $created_items);
        $ticket = current($created_items);

        // Check template
        $this->assertEquals($expected_tickettemplates_id, $ticket->fields['tickettemplates_id']);
    }

    private function createAndGetFormWithTicketDestination(): Form
    {
        $builder = new FormBuilder();
        $builder->addDestination(
            FormDestinationTicket::class,
            "My ticket",
        );
        return $this->createForm($builder);
    }
}
