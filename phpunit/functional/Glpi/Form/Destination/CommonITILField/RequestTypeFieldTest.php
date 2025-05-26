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
use Glpi\Form\Destination\CommonITILField\RequestTypeField;
use Glpi\Form\Destination\CommonITILField\RequestTypeFieldConfig;
use Glpi\Form\Destination\CommonITILField\RequestTypeFieldStrategy;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeRequestType;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Override;
use Ticket;
use TicketTemplate;
use TicketTemplatePredefinedField;

include_once __DIR__ . '/../../../../../abstracts/AbstractDestinationFieldTest.php';

final class RequestTypeFieldTest extends AbstractDestinationFieldTest
{
    use FormTesterTrait;

    public function testRequestTypeFromTemplate(): void
    {
        $from_template_config = new RequestTypeFieldConfig(
            RequestTypeFieldStrategy::FROM_TEMPLATE
        );

        // The default GLPI's template use "INCIDENT"
        $this->sendFormAndAssertTicketType(
            form: $this->createAndGetFormWithMultipleRequestTypeQuestions(),
            config: $from_template_config,
            answers: [],
            expected_request_type: Ticket::INCIDENT_TYPE
        );

        // Set the default type as "Request" using predefined fields
        $this->createItem(TicketTemplatePredefinedField::class, [
            'tickettemplates_id' => getItemByTypeName(TicketTemplate::class, "Default", true),
            'num' => 14, // Request type
            'value' => Ticket::DEMAND_TYPE,
        ]);
        $this->sendFormAndAssertTicketType(
            form: $this->createAndGetFormWithMultipleRequestTypeQuestions(),
            config: $from_template_config,
            answers: [],
            expected_request_type: Ticket::DEMAND_TYPE
        );
    }

    public function testSpecificRequestType(): void
    {
        $form = $this->createAndGetFormWithMultipleRequestTypeQuestions();

        // Specific value: DEMAND
        $this->sendFormAndAssertTicketType(
            form: $form,
            config: new RequestTypeFieldConfig(
                strategy: RequestTypeFieldStrategy::SPECIFIC_VALUE,
                specific_request_type: Ticket::DEMAND_TYPE
            ),
            answers: [],
            expected_request_type: Ticket::DEMAND_TYPE
        );

        // Specific value: INCIDENT
        $this->sendFormAndAssertTicketType(
            form: $form,
            config: new RequestTypeFieldConfig(
                strategy: RequestTypeFieldStrategy::SPECIFIC_VALUE,
                specific_request_type: Ticket::INCIDENT_TYPE
            ),
            answers: [],
            expected_request_type: Ticket::INCIDENT_TYPE
        );
    }

    public function testRequestTypeFromSpecificQuestion(): void
    {
        $form = $this->createAndGetFormWithMultipleRequestTypeQuestions();

        // Using answer from first question
        $this->sendFormAndAssertTicketType(
            form: $form,
            config: new RequestTypeFieldConfig(
                strategy: RequestTypeFieldStrategy::SPECIFIC_ANSWER,
                specific_question_id: $this->getQuestionId($form, "Request type 1")
            ),
            answers: [
                "Request type 1" => Ticket::DEMAND_TYPE,
                "Request type 2" => Ticket::INCIDENT_TYPE,
            ],
            expected_request_type: Ticket::DEMAND_TYPE
        );

        // Using answer from second question
        $this->sendFormAndAssertTicketType(
            form: $form,
            config: new RequestTypeFieldConfig(
                strategy: RequestTypeFieldStrategy::SPECIFIC_ANSWER,
                specific_question_id: $this->getQuestionId($form, "Request type 2")
            ),
            answers: [
                "Request type 1" => Ticket::DEMAND_TYPE,
                "Request type 2" => Ticket::INCIDENT_TYPE,
            ],
            expected_request_type: Ticket::INCIDENT_TYPE
        );
    }

    public function testRequestTypeFromLastValidQuestion(): void
    {
        $form = $this->createAndGetFormWithMultipleRequestTypeQuestions();
        $last_valid_answer_config = new RequestTypeFieldConfig(
            RequestTypeFieldStrategy::LAST_VALID_ANSWER
        );

        // With multiple answers submitted
        $this->sendFormAndAssertTicketType(
            form: $form,
            config: $last_valid_answer_config,
            answers: [
                "Request type 1" => Ticket::INCIDENT_TYPE,
                "Request type 2" => Ticket::DEMAND_TYPE,
            ],
            expected_request_type: Ticket::DEMAND_TYPE
        );

        // Only first answer was submitted
        $this->sendFormAndAssertTicketType(
            form: $form,
            config: $last_valid_answer_config,
            answers: [
                "Request type 1" => Ticket::DEMAND_TYPE,
            ],
            expected_request_type: Ticket::DEMAND_TYPE
        );

        // Only second answer was submitted
        $this->sendFormAndAssertTicketType(
            form: $form,
            config: $last_valid_answer_config,
            answers: [
                "Request type 2" => Ticket::DEMAND_TYPE,
            ],
            expected_request_type: Ticket::DEMAND_TYPE
        );

        // No answers, fallback to default value
        $this->sendFormAndAssertTicketType(
            form: $form,
            config: $last_valid_answer_config,
            answers: [],
            expected_request_type: Ticket::INCIDENT_TYPE
        );

        // Try again with a different template value
        $this->createItem(TicketTemplatePredefinedField::class, [
            'tickettemplates_id' => getItemByTypeName(TicketTemplate::class, "Default", true),
            'num' => 14, // Request type
            'value' => Ticket::DEMAND_TYPE,
        ]);
        $this->sendFormAndAssertTicketType(
            form: $form,
            config: $last_valid_answer_config,
            answers: [],
            expected_request_type: Ticket::DEMAND_TYPE
        );
    }

    #[Override]
    public static function provideConvertFieldConfigFromFormCreator(): iterable
    {
        yield 'Default or from a template' => [
            'field_key'     => RequestTypeField::getKey(),
            'fields_to_set' => [
                'type_rule' => 0, // PluginFormcreatorAbstractItilTarget::REQUESTTYPE_NONE
            ],
            'field_config' => new RequestTypeFieldConfig(
                strategy: RequestTypeFieldStrategy::FROM_TEMPLATE
            ),
        ];

        yield 'Specific type' => [
            'field_key'     => RequestTypeField::getKey(),
            'fields_to_set' => [
                'type_rule' => 1, // PluginFormcreatorAbstractItilTarget::REQUESTTYPE_SPECIFIC
                'type_question' => 4, // High urgency
            ],
            'field_config' => new RequestTypeFieldConfig(
                strategy: RequestTypeFieldStrategy::SPECIFIC_VALUE,
                specific_request_type: 4 // High urgency
            ),
        ];

        yield 'Equals to the answer to the question' => [
            'field_key'     => RequestTypeField::getKey(),
            'fields_to_set' => [
                'type_rule'     => 2, // PluginFormcreatorAbstractItilTarget::REQUESTTYPE_ANSWER
                'type_question' => 80,
            ],
            'field_config' => fn($migration, $form) => new RequestTypeFieldConfig(
                strategy: RequestTypeFieldStrategy::SPECIFIC_ANSWER,
                specific_question_id: $migration->getMappedItemTarget(
                    'PluginFormcreatorQuestion',
                    80
                )['items_id'] ?? throw new \Exception("Question not found")
            ),
        ];
    }

    private function sendFormAndAssertTicketType(
        Form $form,
        RequestTypeFieldConfig $config,
        array $answers,
        int $expected_request_type,
    ): void {
        // Insert config
        $destinations = $form->getDestinations();
        $this->assertCount(1, $destinations);
        $destination = current($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            ['config' => [RequestTypeField::getKey() => $config->jsonSerialize()]],
            ["config"],
        );

        // The provider use a simplified answer format to be more readable.
        // Rewrite answers into expected format.
        $formatted_answers = [];
        foreach ($answers as $question => $answer) {
            $key = $this->getQuestionId($form, $question);
            // Real answer will be decoded as string by default
            $formatted_answers[$key] = (string) $answer;
        }

        // Submit form
        $answers_handler = AnswersHandler::getInstance();
        $answers = $answers_handler->saveAnswers(
            $form,
            $formatted_answers,
            getItemByTypeName(\User::class, TU_USER, true)
        );

        // Get created ticket
        $created_items = $answers->getCreatedItems();
        $this->assertCount(1, $created_items);
        $ticket = current($created_items);

        // Check request type
        $this->assertEquals($expected_request_type, $ticket->fields['type']);
    }

    private function createAndGetFormWithMultipleRequestTypeQuestions(): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion("Request type 1", QuestionTypeRequestType::class);
        $builder->addQuestion("Request type 2", QuestionTypeRequestType::class);
        return $this->createForm($builder);
    }
}
