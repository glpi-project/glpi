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

use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Destination\CommonITILField\RequestSourceField;
use Glpi\Form\Destination\CommonITILField\RequestSourceFieldConfig;
use Glpi\Form\Destination\CommonITILField\RequestSourceFieldStrategy;
use Glpi\Form\Form;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Override;
use RequestType;
use Ticket;
use TicketTemplatePredefinedField;

include_once __DIR__ . '/../../../../../abstracts/AbstractDestinationFieldTest.php';

final class RequestSourceFieldTest extends AbstractDestinationFieldTest
{
    use FormTesterTrait;

    public function testDefaultSourceWithPredefinedField(): void
    {

        $source = RequestType::getDefault('helpdesk');

        $default_template = (new Ticket())->getITILTemplateToUse(
            entities_id: $_SESSION["glpiactive_entity"]
        );
        $this->createItem(
            TicketTemplatePredefinedField::class,
            [
                'tickettemplates_id' => $default_template->getID(),
                'num' => 9,
                'value' => $source,
            ]
        );

        $created_ticket = $this->checkRequestSourceFieldConfiguration(
            form: $this->createAndGetFormWithTicketDestination(),
            config: new RequestSourceFieldConfig(
                strategy: RequestSourceFieldStrategy::FROM_TEMPLATE,
            ),
        );

        $this->assertEquals($source, $created_ticket->fields['requesttypes_id']);
    }

    #[Override]
    public static function provideConvertFieldConfigFromFormCreator(): iterable
    {
        yield 'Source from template or user default or GLPI default' => [
            'field_key'     => RequestSourceField::getKey(),
            'fields_to_set' => [
                'source_rule' => 0, // PluginFormcreatorAbstractItilTarget::REQUESTSOURCE_NONE
            ],
            'field_config' => new RequestSourceFieldConfig(
                strategy: RequestSourceFieldStrategy::FROM_TEMPLATE,
            ),
        ];

        yield 'Formcreator' => [
            'field_key'     => RequestSourceField::getKey(),
            'fields_to_set' => [
                'source_rule' => 1, // PluginFormcreatorAbstractItilTarget::REQUESTSOURCE_FORMCREATOR
            ],
            'field_config' => new RequestSourceFieldConfig(
                strategy: RequestSourceFieldStrategy::FROM_TEMPLATE,
            ),
        ];
    }

    public function testSpecificSourceWithPredefinedField(): void
    {
        $default_source = RequestType::getDefault('helpdesk');
        $specified_source = RequestType::getDefault('followup');

        $default_template = (new Ticket())->getITILTemplateToUse(
            entities_id: $_SESSION["glpiactive_entity"]
        );
        $this->createItem(
            TicketTemplatePredefinedField::class,
            [
                'tickettemplates_id' => $default_template->getID(),
                'num' => 9,
                'value' => $default_source,
            ]
        );

        $created_ticket = $this->checkRequestSourceFieldConfiguration(
            form: $this->createAndGetFormWithTicketDestination(),
            config: new RequestSourceFieldConfig(
                strategy: RequestSourceFieldStrategy::SPECIFIC_VALUE,
                specific_request_source: $specified_source,
            ),
        );

        $this->assertEquals($specified_source, $created_ticket->fields['requesttypes_id']);
    }

    private function checkRequestSourceFieldConfiguration(
        Form $form,
        RequestSourceFieldConfig $config,
    ): Ticket {
        // Insert config
        $destinations = $form->getDestinations();
        $this->assertCount(1, $destinations);
        $destination = current($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            ['config' => [RequestSourceField::getKey() => $config->jsonSerialize()]],
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

        // Return the created ticket to be able to check other fields
        return $ticket;
    }

    private function createAndGetFormWithTicketDestination(): Form
    {
        $builder = new FormBuilder();
        return $this->createForm($builder);
    }
}
