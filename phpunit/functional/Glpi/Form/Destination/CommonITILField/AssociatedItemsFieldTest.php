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

use Computer;
use DbTestCase;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\CommonITILField\AssociatedItemsField;
use Glpi\Form\Destination\CommonITILField\AssociatedItemsFieldConfig;
use Glpi\Form\Destination\CommonITILField\AssociatedItemsFieldStrategy;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Monitor;

final class AssociatedItemsFieldTest extends DbTestCase
{
    use FormTesterTrait;

    public function testAssociatedItemsFromSpecificItems(): void
    {
        $this->login();

        // Create computers and monitors
        $computers = $this->createComputers(2);
        $monitors = $this->createMonitors(2);

        $specific_values = new AssociatedItemsFieldConfig(
            strategy: AssociatedItemsFieldStrategy::SPECIFIC_VALUES,
            specific_associated_items: [
                Computer::getType() => [
                    $computers[0]->getID(),
                    $computers[1]->getID(),
                ],
                Monitor::getType() => [
                    $monitors[0]->getID(),
                ],
            ]
        );

        // Test with no answers
        $this->sendFormAndAssertAssociatedItems(
            form: $this->createAndGetFormWithMultipleItemQuestions(),
            config: $specific_values,
            answers: [],
            expected_associated_items: [
                Computer::getType() => [
                    $computers[0]->getID() => $computers[0]->getID(),
                    $computers[1]->getID() => $computers[1]->getID(),
                ],
                Monitor::getType() => [
                    $monitors[0]->getID() => $monitors[0]->getID(),
                ],
            ]
        );

        // Test with answers
        $this->sendFormAndAssertAssociatedItems(
            form: $this->createAndGetFormWithMultipleItemQuestions(),
            config: $specific_values,
            answers: [
                "Computer 1" => [
                    'itemtype' => Computer::getType(),
                    'items_id' => $computers[1]->getID(),
                ],
                "Monitor 1" => [
                    'itemtype' => Monitor::getType(),
                    'items_id' => $monitors[1]->getID(),
                ],
                "Computer 2" => [
                    'itemtype' => Computer::getType(),
                    'items_id' => $computers[0]->getID(),
                ]
            ],
            expected_associated_items: [
                Computer::getType() => [
                    $computers[0]->getID() => $computers[0]->getID(),
                    $computers[1]->getID() => $computers[1]->getID(),
                ],
                Monitor::getType() => [
                    $monitors[0]->getID() => $monitors[0]->getID(),
                ],
            ]
        );
    }

    public function testAssociatedItemsFromSpecificAnswers(): void
    {
        $this->login();

        // Create computers and monitors
        $computers = $this->createComputers(2);
        $monitors = $this->createMonitors(2);

        $form = $this->createAndGetFormWithMultipleItemQuestions();
        $specific_answers = new AssociatedItemsFieldConfig(
            strategy: AssociatedItemsFieldStrategy::SPECIFIC_ANSWERS,
            specific_question_ids: [
                $this->getQuestionId($form, "Computer 1"),
                $this->getQuestionId($form, "Monitor 1"),
            ]
        );

        // Test with no answers
        $this->sendFormAndAssertAssociatedItems(
            form: $form,
            config: $specific_answers,
            answers: [],
            expected_associated_items: []
        );

        // Test with answers
        $this->sendFormAndAssertAssociatedItems(
            form: $form,
            config: $specific_answers,
            answers: [
                "Computer 1" => [
                    'itemtype' => Computer::getType(),
                    'items_id' => $computers[1]->getID(),
                ],
                "Monitor 1" => [
                    'itemtype' => Monitor::getType(),
                    'items_id' => $monitors[1]->getID(),
                ],
                "Computer 2" => [
                    'itemtype' => Computer::getType(),
                    'items_id' => $computers[0]->getID(),
                ]
            ],
            expected_associated_items: [
                Computer::getType() => [
                    $computers[1]->getID() => $computers[1]->getID(),
                ],
                Monitor::getType() => [
                    $monitors[1]->getID() => $monitors[1]->getID(),
                ],
            ]
        );
    }

    public function testAssociatedItemsFromLastValidAnswer(): void
    {
        $this->login();

        // Create computers and monitors
        $computers = $this->createComputers(2);
        $monitors = $this->createMonitors(2);

        $form = $this->createAndGetFormWithMultipleItemQuestions();
        $last_valid_answer = new AssociatedItemsFieldConfig(
            strategy: AssociatedItemsFieldStrategy::LAST_VALID_ANSWER
        );

        // Test with no answers
        $this->sendFormAndAssertAssociatedItems(
            form: $form,
            config: $last_valid_answer,
            answers: [],
            expected_associated_items: []
        );

        // Test with answers
        $this->sendFormAndAssertAssociatedItems(
            form: $form,
            config: $last_valid_answer,
            answers: [
                "Computer 1" => [
                    'itemtype' => Computer::getType(),
                    'items_id' => $computers[1]->getID(),
                ],
                "Monitor 1" => [
                    'itemtype' => Monitor::getType(),
                    'items_id' => $monitors[1]->getID(),
                ],
                "Computer 2" => [
                    'itemtype' => Computer::getType(),
                    'items_id' => $computers[0]->getID(),
                ]
            ],
            expected_associated_items: [
                Computer::getType() => [
                    $computers[0]->getID() => $computers[0]->getID(),
                ]
            ]
        );
    }

    public function testAssociatedItemsFromAllValidAnswers(): void
    {
        $this->login();

        // Create computers and monitors
        $computers = $this->createComputers(2);
        $monitors = $this->createMonitors(2);

        $form = $this->createAndGetFormWithMultipleItemQuestions();
        $all_valid_answers = new AssociatedItemsFieldConfig(
            strategy: AssociatedItemsFieldStrategy::ALL_VALID_ANSWERS
        );

        // Test with no answers
        $this->sendFormAndAssertAssociatedItems(
            form: $form,
            config: $all_valid_answers,
            answers: [],
            expected_associated_items: []
        );

        // Test with only one answer
        $this->sendFormAndAssertAssociatedItems(
            form: $form,
            config: $all_valid_answers,
            answers: [
                "Computer 1" => [
                    'itemtype' => Computer::getType(),
                    'items_id' => $computers[1]->getID(),
                ],
            ],
            expected_associated_items: [
                Computer::getType() => [
                    $computers[1]->getID() => $computers[1]->getID(),
                ],
            ]
        );

        // Test with answers
        $this->sendFormAndAssertAssociatedItems(
            form: $form,
            config: $all_valid_answers,
            answers: [
                "Computer 1" => [
                    'itemtype' => Computer::getType(),
                    'items_id' => $computers[1]->getID(),
                ],
                "Monitor 1" => [
                    'itemtype' => Monitor::getType(),
                    'items_id' => $monitors[1]->getID(),
                ],
                "Computer 2" => [
                    'itemtype' => Computer::getType(),
                    'items_id' => $computers[0]->getID(),
                ]
            ],
            expected_associated_items: [
                Computer::getType() => [
                    $computers[1]->getID() => $computers[1]->getID(),
                    $computers[0]->getID() => $computers[0]->getID(),
                ],
                Monitor::getType() => [
                    $monitors[1]->getID() => $monitors[1]->getID(),
                ],
            ]
        );
    }

    private function sendFormAndAssertAssociatedItems(
        Form $form,
        AssociatedItemsFieldConfig $config,
        array $answers,
        array $expected_associated_items
    ): void {
        $field = new AssociatedItemsField();

        // Insert config
        $destinations = $form->getDestinations();
        $this->assertCount(1, $destinations);
        $destination = current($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            ['config' => [$field->getKey() => $config->jsonSerialize()]],
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

        // Check associated items
        $this->assertArrayIsEqualToArrayIgnoringListOfKeys(
            $expected_associated_items,
            $ticket->getLinkedItems(),
            [AnswersSet::getType()]
        );
    }

    private function createComputers(int $count): array
    {
        $computers = [];
        for ($i = 1; $i <= $count; $i++) {
            $computers[] = $this->createItem(Computer::class, ['name' => "Computer $i", 'entities_id' => 0]);
        }
        return $computers;
    }

    private function createMonitors(int $count): array
    {
        $monitors = [];
        for ($i = 1; $i <= $count; $i++) {
            $monitors[] = $this->createItem(Monitor::class, ['name' => "Monitor $i", 'entities_id' => 0]);
        }
        return $monitors;
    }

    private function createAndGetFormWithMultipleItemQuestions(): Form
    {
        $computers = $this->createComputers(3);
        $monitors = $this->createMonitors(3);

        $builder = new FormBuilder();
        $builder->addQuestion("Computer 1", QuestionTypeItem::class, $computers[0]->getID(), json_encode([
            'itemtype' => Computer::getType(),
        ]));
        $builder->addQuestion("Monitor 1", QuestionTypeItem::class, $monitors[0]->getID(), json_encode([
            'itemtype' => Monitor::getType(),
        ]));
        $builder->addQuestion("Computer 2", QuestionTypeItem::class, $computers[1]->getID(), json_encode([
            'itemtype' => Computer::getType(),
        ]));

        $builder->addDestination(
            FormDestinationTicket::class,
            "My ticket"
        );
        return $this->createForm($builder);
    }
}
