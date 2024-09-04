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
use Glpi\Form\Destination\CommonITILField\ITILTaskField;
use Glpi\Form\Destination\CommonITILField\ITILTaskFieldConfig;
use Glpi\Form\Destination\CommonITILField\ITILTaskFieldStrategy;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeItemDropdown;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use ITILTask;
use TaskTemplate;
use Ticket;
use TicketTask;

final class ITILTaskFieldTest extends DbTestCase
{
    use FormTesterTrait;

    public function testTaskForNoTask(): void
    {
        $form = $this->createAndGetFormWithMultipleDropdownItemQuestions();
        $no_task = new ITILTaskFieldConfig(
            strategy: ITILTaskFieldStrategy::NO_TASK
        );

        // Test with no answers
        $this->sendFormAndAssertITILTask(
            form: $form,
            config: $no_task,
            answers: [],
            expected_itiltasks: []
        );

        // Test with answers
        $this->sendFormAndAssertITILTask(
            form: $form,
            config: $no_task,
            answers: [
                "Task template 1" => [
                    'itemtype' => TaskTemplate::getType(),
                    'items_id' => $this->createTaskTemplate()->getID(),
                ],
                "Task template 2" => [
                    'itemtype' => TaskTemplate::getType(),
                    'items_id' => $this->createTaskTemplate()->getID(),
                ],
                "Task template 3" => [
                    'itemtype' => TaskTemplate::getType(),
                    'items_id' => $this->createTaskTemplate()->getID(),
                ],
            ],
            expected_itiltasks: []
        );
    }

    public function testTaskForSpecificValues(): void
    {
        $templates = [
            $this->createTaskTemplate('Task template 1'),
            $this->createTaskTemplate('Task template 2'),
        ];
        $form = $this->createAndGetFormWithMultipleDropdownItemQuestions();
        $specific_values = new ITILTaskFieldConfig(
            strategy: ITILTaskFieldStrategy::SPECIFIC_VALUES,
            specific_itiltasktemplates_ids: [$templates[0]->getID(), $templates[1]->getID(),]
        );

        // Test with no answers
        $this->sendFormAndAssertITILTask(
            form: $form,
            config: $specific_values,
            answers: [],
            expected_itiltasks: [
                $templates[0]->getID() => 'Task template 1',
                $templates[1]->getID() => 'Task template 2',
            ]
        );

        // Test with answers
        $this->sendFormAndAssertITILTask(
            form: $form,
            config: $specific_values,
            answers: [
                "Task template 1" => [
                    'itemtype' => TaskTemplate::getType(),
                    'items_id' => $this->createTaskTemplate()->getID(),
                ],
                "Task template 2" => [
                    'itemtype' => TaskTemplate::getType(),
                    'items_id' => $this->createTaskTemplate()->getID(),
                ],
                "Task template 3" => [
                    'itemtype' => TaskTemplate::getType(),
                    'items_id' => $this->createTaskTemplate()->getID(),
                ],
            ],
            expected_itiltasks: [
                $templates[0]->getID() => 'Task template 1',
                $templates[1]->getID() => 'Task template 2',
            ]
        );
    }

    public function testTaskForSpecificAnswers(): void
    {
        $templates = [
            $this->createTaskTemplate('Task template 1'),
            $this->createTaskTemplate('Task template 2'),
            $this->createTaskTemplate('Task template 3'),
        ];
        $form = $this->createAndGetFormWithMultipleDropdownItemQuestions();
        $specific_answers = new ITILTaskFieldConfig(
            strategy: ITILTaskFieldStrategy::SPECIFIC_ANSWERS,
            specific_question_ids: [
                $this->getQuestionId($form, "Task template 1"),
                $this->getQuestionId($form, "Task template 2"),
            ]
        );

        // Test with no answers
        $this->sendFormAndAssertITILTask(
            form: $form,
            config: $specific_answers,
            answers: [],
            expected_itiltasks: []
        );

        // Test with answers
        $this->sendFormAndAssertITILTask(
            form: $form,
            config: $specific_answers,
            answers: [
                "Task template 1" => [
                    'itemtype' => TaskTemplate::getType(),
                    'items_id' => $templates[0]->getID(),
                ],
                "Task template 2" => [
                    'itemtype' => TaskTemplate::getType(),
                    'items_id' => $templates[1]->getID(),
                ],
                "Task template 3" => [
                    'itemtype' => TaskTemplate::getType(),
                    'items_id' => $templates[2]->getID(),
                ],
            ],
            expected_itiltasks: [
                $templates[0]->getID() => 'Task template 1',
                $templates[1]->getID() => 'Task template 2',
            ]
        );
    }

    public function testTaskForLastValidAnswer(): void
    {
        $templates = [
            $this->createTaskTemplate('Task template 1'),
            $this->createTaskTemplate('Task template 2'),
            $this->createTaskTemplate('Task template 3'),
        ];
        $form = $this->createAndGetFormWithMultipleDropdownItemQuestions();
        $last_valid_answer = new ITILTaskFieldConfig(
            strategy: ITILTaskFieldStrategy::LAST_VALID_ANSWER
        );

        // Test with no answers
        $this->sendFormAndAssertITILTask(
            form: $form,
            config: $last_valid_answer,
            answers: [],
            expected_itiltasks: []
        );

        // Test with answers
        $this->sendFormAndAssertITILTask(
            form: $form,
            config: $last_valid_answer,
            answers: [
                "Task template 1" => [
                    'itemtype' => TaskTemplate::getType(),
                    'items_id' => $templates[0]->getID(),
                ],
                "Task template 2" => [
                    'itemtype' => TaskTemplate::getType(),
                    'items_id' => $templates[1]->getID(),
                ],
                "Task template 3" => [
                    'itemtype' => TaskTemplate::getType(),
                    'items_id' => $templates[2]->getID(),
                ],
            ],
            expected_itiltasks: [
                $templates[2]->getID() => 'Task template 3',
            ]
        );
    }

    public function testTaskForAllValidAnswers(): void
    {
        $templates = [
            $this->createTaskTemplate('Task template 1'),
            $this->createTaskTemplate('Task template 2'),
            $this->createTaskTemplate('Task template 3'),
        ];
        $form = $this->createAndGetFormWithMultipleDropdownItemQuestions();
        $all_valid_answers = new ITILTaskFieldConfig(
            strategy: ITILTaskFieldStrategy::ALL_VALID_ANSWERS
        );

        // Test with no answers
        $this->sendFormAndAssertITILTask(
            form: $form,
            config: $all_valid_answers,
            answers: [],
            expected_itiltasks: []
        );

        // Test with only one answer
        $this->sendFormAndAssertITILTask(
            form: $form,
            config: $all_valid_answers,
            answers: [
                "Task template 1" => [
                    'itemtype' => TaskTemplate::getType(),
                    'items_id' => $templates[0]->getID(),
                ],
            ],
            expected_itiltasks: [
                $templates[0]->getID() => 'Task template 1',
            ]
        );

        // Test with answers
        $this->sendFormAndAssertITILTask(
            form: $form,
            config: $all_valid_answers,
            answers: [
                "Task template 1" => [
                    'itemtype' => TaskTemplate::getType(),
                    'items_id' => $templates[0]->getID(),
                ],
                "Task template 2" => [
                    'itemtype' => TaskTemplate::getType(),
                    'items_id' => $templates[1]->getID(),
                ],
                "Task template 3" => [
                    'itemtype' => TaskTemplate::getType(),
                    'items_id' => $templates[2]->getID(),
                ],
            ],
            expected_itiltasks: [
                $templates[0]->getID() => 'Task template 1',
                $templates[1]->getID() => 'Task template 2',
                $templates[2]->getID() => 'Task template 3',
            ]
        );
    }

    private function sendFormAndAssertITILTask(
        Form $form,
        ITILTaskFieldConfig $config,
        array $answers,
        array $expected_itiltasks
    ): void {
        $field = new ITILTaskField();

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

        // Check TicketTask
        $this->assertEquals(
            countElementsInTable(
                TicketTask::getTable(),
                [
                    'tickets_id' => $ticket->getID(),
                ]
            ),
            count($expected_itiltasks)
        );

        $tickettask  = new TicketTask();
        $tickettasks = $tickettask->find([
            'tickets_id' => $ticket->getID(),
        ]);

        $this->assertEquals(
            array_values(
                array_map(
                    fn(array $tickettask) => strip_tags($tickettask['content']),
                    $tickettasks
                )
            ),
            array_values($expected_itiltasks)
        );
    }

    private function createAndGetFormWithMultipleDropdownItemQuestions(): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion("Task template 1", QuestionTypeItemDropdown::class, [
            'itemtype' => TaskTemplate::getType()
        ]);
        $builder->addQuestion("Task template 2", QuestionTypeItemDropdown::class, [
            'itemtype' => TaskTemplate::getType()
        ]);
        $builder->addQuestion("Task template 3", QuestionTypeItemDropdown::class, [
            'itemtype' => TaskTemplate::getType()
        ]);

        $builder->addDestination(
            FormDestinationTicket::class,
            "My ticket"
        );
        return $this->createForm($builder);
    }

    private function createTaskTemplate(?string $content = null): TaskTemplate
    {
        return $this->createItem(TaskTemplate::class, [
            'name' => 'ITIL Task Template',
            'entities_id' => $this->getTestRootEntity()->getID(),
            'content' => $content ?? 'This is a task template',
        ]);
    }
}
