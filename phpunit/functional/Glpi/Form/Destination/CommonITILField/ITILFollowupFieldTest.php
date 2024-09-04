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
use Glpi\Form\Destination\CommonITILField\ITILFollowupField;
use Glpi\Form\Destination\CommonITILField\ITILFollowupFieldConfig;
use Glpi\Form\Destination\CommonITILField\ITILFollowupFieldStrategy;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeItemDropdown;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use ITILFollowup;
use ITILFollowupTemplate;
use Ticket;

final class ITILFollowupFieldTest extends DbTestCase
{
    use FormTesterTrait;

    public function testFollowupForNoFollowup(): void
    {
        $form = $this->createAndGetFormWithMultipleDropdownItemQuestions();
        $no_followup = new ITILFollowupFieldConfig(
            strategy: ITILFollowupFieldStrategy::NO_FOLLOWUP
        );

        // Test with no answers
        $this->sendFormAndAssertITILFollowup(
            form: $form,
            config: $no_followup,
            answers: [],
            expected_itilfollowups: []
        );

        // Test with answers
        $this->sendFormAndAssertITILFollowup(
            form: $form,
            config: $no_followup,
            answers: [
                "Followup template 1" => [
                    'itemtype' => ITILFollowupTemplate::getType(),
                    'items_id' => $this->createITILFollowupTemplate()->getID(),
                ],
                "Followup template 2" => [
                    'itemtype' => ITILFollowupTemplate::getType(),
                    'items_id' => $this->createITILFollowupTemplate()->getID(),
                ],
                "Followup template 3" => [
                    'itemtype' => ITILFollowupTemplate::getType(),
                    'items_id' => $this->createITILFollowupTemplate()->getID(),
                ],
            ],
            expected_itilfollowups: []
        );
    }

    public function testFollowupForSpecificValues(): void
    {
        $templates = [
            $this->createITILFollowupTemplate('Followup template 1'),
            $this->createITILFollowupTemplate('Followup template 2'),
        ];
        $form = $this->createAndGetFormWithMultipleDropdownItemQuestions();
        $specific_values = new ITILFollowupFieldConfig(
            strategy: ITILFollowupFieldStrategy::SPECIFIC_VALUES,
            specific_itilfollowuptemplates_ids: [$templates[0]->getID(), $templates[1]->getID(),]
        );

        // Test with no answers
        $this->sendFormAndAssertITILFollowup(
            form: $form,
            config: $specific_values,
            answers: [],
            expected_itilfollowups: [
                $templates[0]->getID() => 'Followup template 1',
                $templates[1]->getID() => 'Followup template 2',
            ]
        );

        // Test with answers
        $this->sendFormAndAssertITILFollowup(
            form: $form,
            config: $specific_values,
            answers: [
                "Followup template 1" => [
                    'itemtype' => ITILFollowupTemplate::getType(),
                    'items_id' => $this->createITILFollowupTemplate()->getID(),
                ],
                "Followup template 2" => [
                    'itemtype' => ITILFollowupTemplate::getType(),
                    'items_id' => $this->createITILFollowupTemplate()->getID(),
                ],
                "Followup template 3" => [
                    'itemtype' => ITILFollowupTemplate::getType(),
                    'items_id' => $this->createITILFollowupTemplate()->getID(),
                ],
            ],
            expected_itilfollowups: [
                $templates[0]->getID() => 'Followup template 1',
                $templates[1]->getID() => 'Followup template 2',
            ]
        );
    }

    public function testFollowupForSpecificAnswers(): void
    {
        $templates = [
            $this->createITILFollowupTemplate('Followup template 1'),
            $this->createITILFollowupTemplate('Followup template 2'),
            $this->createITILFollowupTemplate('Followup template 3'),
        ];
        $form = $this->createAndGetFormWithMultipleDropdownItemQuestions();
        $specific_answers = new ITILFollowupFieldConfig(
            strategy: ITILFollowupFieldStrategy::SPECIFIC_ANSWERS,
            specific_question_ids: [
                $this->getQuestionId($form, "Followup template 1"),
                $this->getQuestionId($form, "Followup template 2"),
            ]
        );

        // Test with no answers
        $this->sendFormAndAssertITILFollowup(
            form: $form,
            config: $specific_answers,
            answers: [],
            expected_itilfollowups: []
        );

        // Test with answers
        $this->sendFormAndAssertITILFollowup(
            form: $form,
            config: $specific_answers,
            answers: [
                "Followup template 1" => [
                    'itemtype' => ITILFollowupTemplate::getType(),
                    'items_id' => $templates[0]->getID(),
                ],
                "Followup template 2" => [
                    'itemtype' => ITILFollowupTemplate::getType(),
                    'items_id' => $templates[1]->getID(),
                ],
                "Followup template 3" => [
                    'itemtype' => ITILFollowupTemplate::getType(),
                    'items_id' => $templates[2]->getID(),
                ],
            ],
            expected_itilfollowups: [
                $templates[0]->getID() => 'Followup template 1',
                $templates[1]->getID() => 'Followup template 2',
            ]
        );
    }

    public function testFollowupForLastValidAnswer(): void
    {
        $templates = [
            $this->createITILFollowupTemplate('Followup template 1'),
            $this->createITILFollowupTemplate('Followup template 2'),
            $this->createITILFollowupTemplate('Followup template 3'),
        ];
        $form = $this->createAndGetFormWithMultipleDropdownItemQuestions();
        $last_valid_answer = new ITILFollowupFieldConfig(
            strategy: ITILFollowupFieldStrategy::LAST_VALID_ANSWER
        );

        // Test with no answers
        $this->sendFormAndAssertITILFollowup(
            form: $form,
            config: $last_valid_answer,
            answers: [],
            expected_itilfollowups: []
        );

        // Test with answers
        $this->sendFormAndAssertITILFollowup(
            form: $form,
            config: $last_valid_answer,
            answers: [
                "Followup template 1" => [
                    'itemtype' => ITILFollowupTemplate::getType(),
                    'items_id' => $templates[0]->getID(),
                ],
                "Followup template 2" => [
                    'itemtype' => ITILFollowupTemplate::getType(),
                    'items_id' => $templates[1]->getID(),
                ],
                "Followup template 3" => [
                    'itemtype' => ITILFollowupTemplate::getType(),
                    'items_id' => $templates[2]->getID(),
                ],
            ],
            expected_itilfollowups: [
                $templates[2]->getID() => 'Followup template 3',
            ]
        );
    }

    public function testFollowupForAllValidAnswers(): void
    {
        $templates = [
            $this->createITILFollowupTemplate('Followup template 1'),
            $this->createITILFollowupTemplate('Followup template 2'),
            $this->createITILFollowupTemplate('Followup template 3'),
        ];
        $form = $this->createAndGetFormWithMultipleDropdownItemQuestions();
        $all_valid_answers = new ITILFollowupFieldConfig(
            strategy: ITILFollowupFieldStrategy::ALL_VALID_ANSWERS
        );

        // Test with no answers
        $this->sendFormAndAssertITILFollowup(
            form: $form,
            config: $all_valid_answers,
            answers: [],
            expected_itilfollowups: []
        );

        // Test with only one answer
        $this->sendFormAndAssertITILFollowup(
            form: $form,
            config: $all_valid_answers,
            answers: [
                "Followup template 1" => [
                    'itemtype' => ITILFollowupTemplate::getType(),
                    'items_id' => $templates[0]->getID(),
                ],
            ],
            expected_itilfollowups: [
                $templates[0]->getID() => 'Followup template 1',
            ]
        );

        // Test with answers
        $this->sendFormAndAssertITILFollowup(
            form: $form,
            config: $all_valid_answers,
            answers: [
                "Followup template 1" => [
                    'itemtype' => ITILFollowupTemplate::getType(),
                    'items_id' => $templates[0]->getID(),
                ],
                "Followup template 2" => [
                    'itemtype' => ITILFollowupTemplate::getType(),
                    'items_id' => $templates[1]->getID(),
                ],
                "Followup template 3" => [
                    'itemtype' => ITILFollowupTemplate::getType(),
                    'items_id' => $templates[2]->getID(),
                ],
            ],
            expected_itilfollowups: [
                $templates[0]->getID() => 'Followup template 1',
                $templates[1]->getID() => 'Followup template 2',
                $templates[2]->getID() => 'Followup template 3',
            ]
        );
    }

    private function sendFormAndAssertITILFollowup(
        Form $form,
        ITILFollowupFieldConfig $config,
        array $answers,
        array $expected_itilfollowups
    ): void {
        $field = new ITILFollowupField();

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

        // Check ITILFollowup
        $this->assertEquals(
            countElementsInTable(
                ITILFollowup::getTable(),
                [
                    'itemtype' => Ticket::getType(),
                    'items_id' => $ticket->getID(),
                ]
            ),
            count($expected_itilfollowups)
        );

        $itilfollowup  = new ITILFollowup();
        $itilfollowups = $itilfollowup->find([
            'itemtype' => Ticket::getType(),
            'items_id' => $ticket->getID(),
        ]);

        $this->assertEquals(
            array_values(
                array_map(
                    fn(array $itilfollowup) => strip_tags($itilfollowup['content']),
                    $itilfollowups
                )
            ),
            array_values($expected_itilfollowups)
        );
    }

    private function createAndGetFormWithMultipleDropdownItemQuestions(): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion("Followup template 1", QuestionTypeItemDropdown::class, [
            'itemtype' => ITILFollowupTemplate::getType()
        ]);
        $builder->addQuestion("Followup template 2", QuestionTypeItemDropdown::class, [
            'itemtype' => ITILFollowupTemplate::getType()
        ]);
        $builder->addQuestion("Followup template 3", QuestionTypeItemDropdown::class, [
            'itemtype' => ITILFollowupTemplate::getType()
        ]);

        $builder->addDestination(
            FormDestinationTicket::class,
            "My ticket"
        );
        return $this->createForm($builder);
    }

    private function createITILFollowupTemplate(?string $content = null): ITILFollowupTemplate
    {
        return $this->createItem(ITILFollowupTemplate::class, [
            'name' => 'ITIL Followup Template',
            'entities_id' => $this->getTestRootEntity()->getID(),
            'content' => $content ?? 'This is a followup template',
        ]);
    }
}
