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
use Ticket;
use TicketTemplate;
use TicketTemplatePredefinedField;

final class TemplateFieldTest extends DbTestCase
{
    use FormTesterTrait;

    public function testDefaultTemplateWithPredefinedField(): void
    {
        $urgency = 5;

        $default_template = (new Ticket())->getITILTemplateToUse(
            entities_id: $_SESSION["glpiactive_entity"]
        );
        $this->createItem(
            TicketTemplatePredefinedField::class,
            [
                'tickettemplates_id' => $default_template->getID(),
                'num'                => 10,
                'value'              => $urgency,
            ]
        );

        $created_ticket = $this->checkTemplateFieldConfiguration(
            form: $this->createAndGetFormWithTicketDestination(),
            config: new TemplateFieldConfig(
                strategy: TemplateFieldStrategy::DEFAULT_TEMPLATE,
            ),
            expected_tickettemplates_id: $default_template->getID()
        );

        $this->assertEquals($urgency, $created_ticket->fields['urgency']);
    }

    public function testSpecificTemplateWithPredefinedField(): void
    {
        $default_urgency = 5;
        $specified_urgency = 2;

        $default_template = (new Ticket())->getITILTemplateToUse(
            entities_id: $_SESSION["glpiactive_entity"]
        );
        $this->createItem(
            TicketTemplatePredefinedField::class,
            [
                'tickettemplates_id' => $default_template->getID(),
                'num'                => 10,
                'value'              => $default_urgency,
            ]
        );

        $ticket_template = $this->createItem(
            TicketTemplate::class,
            ['name' => 'Template 1']
        );
        $this->createItem(
            TicketTemplatePredefinedField::class,
            [
                'tickettemplates_id' => $ticket_template->getID(),
                'num'                => 10,
                'value'              => $specified_urgency,
            ]
        );

        $created_ticket = $this->checkTemplateFieldConfiguration(
            form: $this->createAndGetFormWithTicketDestination(),
            config: new TemplateFieldConfig(
                strategy: TemplateFieldStrategy::SPECIFIC_TEMPLATE,
                specific_template_id: $ticket_template->getID()
            ),
            expected_tickettemplates_id: $ticket_template->getID()
        );

        $this->assertEquals($specified_urgency, $created_ticket->fields['urgency']);
    }

    public function testDefaultTemplate(): void
    {
        $default_template = (new Ticket())->getITILTemplateToUse(
            entities_id: $_SESSION["glpiactive_entity"]
        );

        $this->checkTemplateFieldConfiguration(
            form: $this->createAndGetFormWithTicketDestination(),
            config: new TemplateFieldConfig(
                TemplateFieldStrategy::DEFAULT_TEMPLATE
            ),
            expected_tickettemplates_id: $default_template->getID()
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
        int $expected_tickettemplates_id
    ): Ticket {
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

        // Return the created ticket to be able to check other fields
        return $ticket;
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
