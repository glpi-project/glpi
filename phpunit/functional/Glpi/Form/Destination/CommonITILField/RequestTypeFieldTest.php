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
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeRequestType;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Ticket;

final class RequestTypeField extends DbTestCase
{
    use FormTesterTrait;

    public function requestTypeFieldConfigurationProvider(): iterable
    {
        $field = new \Glpi\Form\Destination\CommonITILField\RequestTypeField();

        yield 'Using template/default value' => [
            'form'    => $form = $this->getFormWithMultipleRequestTypeQuestions(),
            'config'  => ['value' => $field::CONFIG_FROM_TEMPLATE],
            'answers' => [],
            'expected_request_type' => Ticket::INCIDENT_TYPE,
        ];

        yield 'Using specific value (DEMAND)' => [
            'form'    => $form = $this->getFormWithMultipleRequestTypeQuestions(),
            'config'  => [
                'value' => $field::CONFIG_SPECIFIC_VALUE,
                $field::EXTRA_CONFIG_REQUEST_TYPE => Ticket::DEMAND_TYPE,
            ],
            'answers' => [],
            'expected_request_type' => Ticket::DEMAND_TYPE,
        ];
        yield 'Using specific value (INCIDENT)' => [
            'form'    => $form = $this->getFormWithMultipleRequestTypeQuestions(),
            'config'  => [
                'value' => $field::CONFIG_SPECIFIC_VALUE,
                $field::EXTRA_CONFIG_REQUEST_TYPE => Ticket::INCIDENT_TYPE,
            ],
            'answers' => [],
            'expected_request_type' => Ticket::INCIDENT_TYPE,
        ];

        yield 'Using specific answer (DEMAND)' => [
            'form'    => $form = $this->getFormWithMultipleRequestTypeQuestions(),
            'config'  => [
                'value' => $field::CONFIG_SPECIFIC_ANSWER,
                $field::EXTRA_CONFIG_QUESTION_ID => $this->getQuestionId($form, "Request type 1"),
            ],
            'answers' => [
                "Request type 1" => Ticket::DEMAND_TYPE,
                "Request type 2" => Ticket::INCIDENT_TYPE,
            ],
            'expected_request_type' => Ticket::DEMAND_TYPE,
        ];
        yield 'Using specific answer (INCIDENT)' => [
            'form'    => $form = $this->getFormWithMultipleRequestTypeQuestions(),
            'config'  => [
                'value' => $field::CONFIG_SPECIFIC_ANSWER,
                $field::EXTRA_CONFIG_QUESTION_ID => $this->getQuestionId($form, "Request type 2"),
            ],
            'answers' => [
                "Request type 1" => Ticket::DEMAND_TYPE,
                "Request type 2" => Ticket::INCIDENT_TYPE,
            ],
            'expected_request_type' => Ticket::INCIDENT_TYPE,
        ];

        yield 'Using last valid answer (multiple answers submitted)' => [
            'form'    => $form = $this->getFormWithMultipleRequestTypeQuestions(),
            'config'  => [
                'value' => $field::CONFIG_LAST_VALID_ANSWER,
            ],
            'answers' => [
                "Request type 1" => Ticket::INCIDENT_TYPE,
                "Request type 2" => Ticket::DEMAND_TYPE,
            ],
            'expected_request_type' => Ticket::DEMAND_TYPE,
        ];
        yield 'Using last valid answer (only first answer was submitted)' => [
            'form'    => $form = $this->getFormWithMultipleRequestTypeQuestions(),
            'config'  => [
                'value' => $field::CONFIG_LAST_VALID_ANSWER,
            ],
            'answers' => [
                "Request type 1" => Ticket::DEMAND_TYPE,
            ],
            'expected_request_type' => Ticket::DEMAND_TYPE,
        ];
        yield 'Using last valid answer (Only second answer was submitted)' => [
            'form'    => $form = $this->getFormWithMultipleRequestTypeQuestions(),
            'config'  => [
                'value' => $field::CONFIG_LAST_VALID_ANSWER,
            ],
            'answers' => [
                "Request type 2" => Ticket::DEMAND_TYPE,
            ],
            'expected_request_type' => Ticket::DEMAND_TYPE,
        ];
        yield 'Using last valid answer (no answers)' => [
            'form'    => $form = $this->getFormWithMultipleRequestTypeQuestions(),
            'config'  => [
                'value' => $field::CONFIG_LAST_VALID_ANSWER,
            ],
            'answers' => [],
            'expected_request_type' => Ticket::INCIDENT_TYPE, // Default value fallback
        ];
    }

    /**
     * @dataProvider requestTypeFieldConfigurationProvider
     */
    public function testRequestTypeFieldConfiguration(
        Form $form,
        array $config,
        array $answers,
        int $expected_request_type
    ): void {
        // Insert config
        $destinations = $form->getDestinations();
        $this->array($destinations)->hasSize(1);
        $destination = current($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            ['config' => ['request_type' => $config]],
            ["config"],
        );

        // The provider use a simplified answer format to be more readable.
        // Rewrite answers into expected format.
        $formatted_answers = [];
        foreach ($answers as $question => $answer) {
            $key = $this->getQuestionId($form, $question);
            $formatted_answers[$key] = $answer;
        }

        // Submit form
        $this->login();
        $answers_handler = AnswersHandler::getInstance();
        $answers = $answers_handler->saveAnswers(
            $form,
            $formatted_answers,
            \Session::getLoginUserID()
        );

        // Get created ticket
        $created_items = $answers->getCreatedItems();
        $this->array($created_items)->hasSize(1);
        $ticket = current($created_items);

        // Check request type
        $this->integer($ticket->fields['type'])
            ->isEqualTo($expected_request_type)
        ;
    }

    private function getFormWithMultipleRequestTypeQuestions(): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion("Request type 1", QuestionTypeRequestType::class);
        $builder->addQuestion("Request type 2", QuestionTypeRequestType::class);
        $builder->addDestination(
            FormDestinationTicket::class,
            "My ticket",
        );
        return $this->createForm($builder);
    }
}
