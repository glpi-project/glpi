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
use Glpi\Form\Destination\CommonITILField\ITILCategoryFieldConfig;
use Glpi\Form\Destination\CommonITILField\ITILCategoryFieldStrategy;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeItemDropdown;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use ITILCategory;
use Ticket;
use TicketTemplate;

final class ITILCategoryFieldTest extends DbTestCase
{
    use FormTesterTrait;

    public function testSpecificITILCategory(): void
    {
        $form = $this->createAndGetFormWithMultipleITILCategoryQuestions();
        $itilcategory = $this->createItem(ITILCategory::getType(), [
            'name' => 'Test ITILCategory for specific value',
        ]);

        // Specific value
        $this->sendFormAndAssertTicketCategory(
            form: $form,
            config: new ITILCategoryFieldConfig(
                strategy: ITILCategoryFieldStrategy::SPECIFIC_VALUE,
                specific_itilcategory_id: $itilcategory->getID()
            ),
            answers: [],
            expected_itilcategory: $itilcategory->getID()
        );

        // No specific value
        $this->sendFormAndAssertTicketCategory(
            form: $form,
            config: new ITILCategoryFieldConfig(
                strategy: ITILCategoryFieldStrategy::SPECIFIC_VALUE,
                specific_itilcategory_id: 0
            ),
            answers: [],
            expected_itilcategory: 0
        );
    }

    public function testSpecificITILCategoryWithSpecificTemplate(): void
    {
        $form = $this->createAndGetFormWithMultipleITILCategoryQuestions();
        $ticket_template = $this->createItem(TicketTemplate::getType(), [
            'name' => 'Test TicketTemplate for specific value',
        ]);
        $itilcategory = $this->createItem(ITILCategory::getType(), [
            'name'                        => 'Test ITILCategory for specific value',
            'tickettemplates_id_incident' => $ticket_template->getID(),
        ]);

        $created_ticket = $this->sendFormAndAssertTicketCategory(
            form: $form,
            config: new ITILCategoryFieldConfig(
                strategy: ITILCategoryFieldStrategy::SPECIFIC_VALUE,
                specific_itilcategory_id: $itilcategory->getID()
            ),
            answers: [],
            expected_itilcategory: $itilcategory->getID()
        );

        $this->assertEquals($ticket_template->getID(), $created_ticket->fields['tickettemplates_id']);
    }

    public function testSpecificITILCategoryWithoutSpecificTemplate(): void
    {
        $form = $this->createAndGetFormWithMultipleITILCategoryQuestions();
        $default_template = (new Ticket())->getITILTemplateToUse(
            entities_id: $_SESSION["glpiactive_entity"]
        );
        $itilcategory = $this->createItem(ITILCategory::getType(), [
            'name' => 'Test ITILCategory for specific value',
        ]);

        $created_ticket = $this->sendFormAndAssertTicketCategory(
            form: $form,
            config: new ITILCategoryFieldConfig(
                strategy: ITILCategoryFieldStrategy::SPECIFIC_VALUE,
                specific_itilcategory_id: $itilcategory->getID()
            ),
            answers: [],
            expected_itilcategory: $itilcategory->getID()
        );

        $this->assertEquals($default_template->getID(), $created_ticket->fields['tickettemplates_id']);
    }

    public function testITILCategoryFromSpecificQuestion(): void
    {
        $form = $this->createAndGetFormWithMultipleITILCategoryQuestions();
        $itilcategories = $this->createItems(ITILCategory::getType(), [
            ['name' => 'Test ITILCategory 1 for specific question'],
            ['name' => 'Test ITILCategory 2 for specific question'],
        ]);

        // Using answer from first question
        $this->sendFormAndAssertTicketCategory(
            form: $form,
            config: new ITILCategoryFieldConfig(
                strategy: ITILCategoryFieldStrategy::SPECIFIC_ANSWER,
                specific_question_id: $this->getQuestionId($form, "ITILCategory 1")
            ),
            answers: [
                "ITILCategory 1" => [
                    'itemtype' => ITILCategory::getType(),
                    'items_id' => $itilcategories[0]->getID()
                ],
                "ITILCategory 2" => [
                    'itemtype' => ITILCategory::getType(),
                    'items_id' => $itilcategories[1]->getID()
                ],
            ],
            expected_itilcategory: $itilcategories[0]->getID()
        );

        // Using answer from second question
        $this->sendFormAndAssertTicketCategory(
            form: $form,
            config: new ITILCategoryFieldConfig(
                strategy: ITILCategoryFieldStrategy::SPECIFIC_ANSWER,
                specific_question_id: $this->getQuestionId($form, "ITILCategory 2")
            ),
            answers: [
                "ITILCategory 1" => [
                    'itemtype' => ITILCategory::getType(),
                    'items_id' => $itilcategories[0]->getID()
                ],
                "ITILCategory 2" => [
                    'itemtype' => ITILCategory::getType(),
                    'items_id' => $itilcategories[1]->getID()
                ],
            ],
            expected_itilcategory: $itilcategories[1]->getID()
        );
    }

    public function testITILCategoryFromLastValidQuestion(): void
    {
        $form = $this->createAndGetFormWithMultipleITILCategoryQuestions();
        $itilcategories = $this->createItems(ITILCategory::getType(), [
            ['name' => 'Test ITILCategory 1 for last valid answer'],
            ['name' => 'Test ITILCategory 2 for last valid answer'],
            ['name' => 'Test ITILCategory 3 for last valid answer'],
        ]);
        $last_valid_answer_config = new ITILCategoryFieldConfig(
            ITILCategoryFieldStrategy::LAST_VALID_ANSWER
        );

        // With multiple answers submitted
        $this->sendFormAndAssertTicketCategory(
            form: $form,
            config: $last_valid_answer_config,
            answers: [
                "ITILCategory 1" => [
                    'itemtype' => ITILCategory::getType(),
                    'items_id' => $itilcategories[0]->getID()
                ],
                "ITILCategory 2" => [
                    'itemtype' => ITILCategory::getType(),
                    'items_id' => $itilcategories[1]->getID()
                ],
            ],
            expected_itilcategory: $itilcategories[1]->getID()
        );

        // Only first answer was submitted
        $this->sendFormAndAssertTicketCategory(
            form: $form,
            config: $last_valid_answer_config,
            answers: [
                "ITILCategory 1" => [
                    'itemtype' => ITILCategory::getType(),
                    'items_id' => $itilcategories[0]->getID()
                ],
            ],
            expected_itilcategory: $itilcategories[0]->getID()
        );

        // Only second answer was submitted
        $this->sendFormAndAssertTicketCategory(
            form: $form,
            config: $last_valid_answer_config,
            answers: [
                "ITILCategory 2" => [
                    'itemtype' => ITILCategory::getType(),
                    'items_id' => $itilcategories[1]->getID()
                ],
            ],
            expected_itilcategory: $itilcategories[1]->getID()
        );

        // No answers, fallback to default value
        $this->sendFormAndAssertTicketCategory(
            form: $form,
            config: $last_valid_answer_config,
            answers: [],
            expected_itilcategory: 0
        );
    }

    private function sendFormAndAssertTicketCategory(
        Form $form,
        ITILCategoryFieldConfig $config,
        array $answers,
        int $expected_itilcategory,
    ): Ticket {
        // Insert config
        $destinations = $form->getDestinations();
        $this->assertCount(1, $destinations);
        $destination = current($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            ['config' => ['itilcategory' => $config->jsonSerialize()]],
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

        // Check ITILCategory
        $this->assertEquals($expected_itilcategory, $ticket->fields['itilcategories_id']);

        // Return the created ticket to be able to check other fields
        return $ticket;
    }

    private function createAndGetFormWithMultipleITILCategoryQuestions(): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion("ITILCategory 1", QuestionTypeItemDropdown::class, 0, json_encode([
            'itemtype' => ITILCategory::class,
        ]));
        $builder->addQuestion("ITILCategory 2", QuestionTypeItemDropdown::class, 0, json_encode([
            'itemtype' => ITILCategory::class,
        ]));
        $builder->addDestination(
            FormDestinationTicket::class,
            "My ticket",
        );
        return $this->createForm($builder);
    }
}
