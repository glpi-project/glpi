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

namespace tests\units\Glpi\Form\Condition;

use DbTestCase;
use Glpi\Form\Condition\CreationStrategy;
use Glpi\Form\Condition\Engine;
use Glpi\Form\Condition\EngineInput;
use Glpi\Form\Condition\LogicOperator;
use Glpi\Form\Condition\Type;
use Glpi\Form\Condition\ValueOperator;
use Glpi\Form\Condition\VisibilityStrategy;
use Glpi\Form\Destination\FormDestination;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeRadio;
use Glpi\Form\QuestionType\QuestionTypeSelectableExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use PHPUnit\Framework\Attributes\DataProvider;

final class EngineTest extends DbTestCase
{
    use FormTesterTrait;

    public static function conditionsOnForm(): iterable
    {
        $form = new FormBuilder();
        $form->addQuestion("Question 1", QuestionTypeShortText::class);
        $form->setSubmitButtonVisibility(
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question 1",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "correct value",
                ],
            ]
        );

        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "",
                ],
            ],
            'expected_output' => [
                'submit_button' => false,
            ],
        ];

        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "correct value",
                ],
            ],
            'expected_output' => [
                'submit_button' => true,
            ],
        ];

        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "incorrect value",
                ],
            ],
            'expected_output' => [
                'submit_button' => false,
            ],
        ];
    }

    public static function conditionsOnQuestions(): iterable
    {
        $form = new FormBuilder();
        $form->addQuestion("Question 1", QuestionTypeShortText::class);
        $form->addQuestion("Question 2", QuestionTypeShortText::class);
        $form->addQuestion("Question 3", QuestionTypeShortText::class);
        $form->addQuestion("Question 4", QuestionTypeShortText::class);
        $form->setQuestionVisibility(
            "Question 2",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question 1",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "correct value",
                ],
            ]
        );
        $form->setQuestionVisibility(
            "Question 3",
            VisibilityStrategy::HIDDEN_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question 4",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "glpi",
                ],
            ]
        );

        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "",
                    'Question 2' => "",
                    'Question 3' => "",
                    'Question 4' => "",
                ],
            ],
            'expected_output' => [
                'questions' => [
                    'Question 1' => true,
                    'Question 2' => false,
                    'Question 3' => true,
                    'Question 4' => true,
                ],
            ],
        ];
        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "correct value",
                    'Question 2' => "",
                    'Question 3' => "",
                    'Question 4' => "",
                ],
            ],
            'expected_output' => [
                'questions' => [
                    'Question 1' => true,
                    'Question 2' => true,
                    'Question 3' => true,
                    'Question 4' => true,
                ],
            ],
        ];
        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "",
                    'Question 2' => "",
                    'Question 3' => "",
                    'Question 4' => "glpi",
                ],
            ],
            'expected_output' => [
                'questions' => [
                    'Question 1' => true,
                    'Question 2' => false,
                    'Question 3' => false,
                    'Question 4' => true,
                ],
            ],
        ];
        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "correct value",
                    'Question 2' => "",
                    'Question 3' => "",
                    'Question 4' => "glpi",
                ],
            ],
            'expected_output' => [
                'questions' => [
                    'Question 1' => true,
                    'Question 2' => true,
                    'Question 3' => false,
                    'Question 4' => true,
                ],
            ],
        ];
    }

    public static function conditionsOnComments(): iterable
    {
        $form = new FormBuilder();
        $form->addQuestion("Question 1", QuestionTypeShortText::class);
        $form->addQuestion("Question 2", QuestionTypeShortText::class);
        $form->addComment("Comment 1");
        $form->addComment("Comment 2");
        $form->setCommentVisibility(
            "Comment 1",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question 1",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "glpi is incredible",
                ],
            ]
        );
        $form->setCommentVisibility(
            "Comment 2",
            VisibilityStrategy::HIDDEN_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question 2",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "of course",
                ],
            ]
        );

        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "",
                    'Question 2' => "",
                ],
            ],
            'expected_output' => [
                'comments' => [
                    'Comment 1' => false,
                    'Comment 2' => true,
                ],
            ],
        ];
        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "glpi is incredible",
                    'Question 2' => "",
                ],
            ],
            'expected_output' => [
                'comments' => [
                    'Comment 1' => true,
                    'Comment 2' => true,
                ],
            ],
        ];
        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "",
                    'Question 2' => "of course",
                ],
            ],
            'expected_output' => [
                'comments' => [
                    'Comment 1' => false,
                    'Comment 2' => false,
                ],
            ],
        ];
        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "glpi is incredible",
                    'Question 2' => "of course",
                ],
            ],
            'expected_output' => [
                'comments' => [
                    'Comment 1' => true,
                    'Comment 2' => false,
                ],
            ],
        ];
    }

    public static function conditionsOnSections(): iterable
    {
        $form = new FormBuilder();
        $form->addQuestion("Question 1", QuestionTypeShortText::class);
        $form->addQuestion("Question 2", QuestionTypeShortText::class);
        $form->addSection("Test section 1");
        $form->addComment("Comment 1");
        $form->addSection("Test section 2");
        $form->addComment("Comment 2");
        $form->setSectionVisibility(
            "Test section 1",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question 1",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "answer for question 1",
                ],
            ]
        );
        $form->setSectionVisibility(
            "Test section 2",
            VisibilityStrategy::HIDDEN_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question 2",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "answer for question 2",
                ],
            ]
        );

        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "",
                    'Question 2' => "",
                ],
            ],
            'expected_output' => [
                'sections' => [
                    'Test section 1' => false,
                    'Test section 2' => true,
                ],
            ],
        ];
        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "answer for question 1",
                    'Question 2' => "",
                ],
            ],
            'expected_output' => [
                'sections' => [
                    'Test section 1' => true,
                    'Test section 2' => true,
                ],
            ],
        ];
        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "",
                    'Question 2' => "answer for question 2",
                ],
            ],
            'expected_output' => [
                'sections' => [
                    'Test section 1' => false,
                    'Test section 2' => false,
                ],
            ],
        ];
        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "answer for question 1",
                    'Question 2' => "answer for question 2",
                ],
            ],
            'expected_output' => [
                'sections' => [
                    'Test section 1' => true,
                    'Test section 2' => false,
                ],
            ],
        ];
    }

    public static function firstSectionShouldAlwaysBeVisible(): iterable
    {
        $form = new FormBuilder();
        $form->addSection("First section");
        $form->addQuestion("Question used as condition", QuestionTypeShortText::class);
        $form->addSection("Second section");
        $form->addQuestion("Another question", QuestionTypeShortText::class);
        $form->setSectionVisibility(
            "First section",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question used as condition",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "expected answer",
                ],
            ]
        );
        $form->setSectionVisibility(
            "Second section",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question used as condition",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "expected answer",
                ],
            ]
        );

        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question used as condition' => "unexpected answer",
                    'Another question' => "doesn't matter",
                ],
            ],
            'expected_output' => [
                // Despite both sections have the same condition, the first one is visible
                'sections' => [
                    'First section' => true,
                    'Second section' => false,
                ],
            ],
        ];
    }

    public static function conditionsOnQuestionWithNullExtraData(): iterable
    {
        $form = new FormBuilder();
        $form->addQuestion(
            name: "Question 1",
            type: QuestionTypeShortText::class,
            extra_data: null
        );
        $form->addQuestion("Question 2", QuestionTypeShortText::class);
        $form->setQuestionVisibility(
            "Question 2",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question 1",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "correct value",
                ],
            ]
        );

        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "",
                    'Question 2' => "",
                ],
            ],
            'expected_output' => [
                'questions' => [
                    'Question 1' => true,
                    'Question 2' => false,
                ],
            ],
        ];

        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "correct value",
                    'Question 2' => "",
                ],
            ],
            'expected_output' => [
                'questions' => [
                    'Question 1' => true,
                    'Question 2' => true,
                ],
            ],
        ];
    }

    public static function conditionsOnQuestionsWithNotVisibleQuestionAsSource(): iterable
    {
        $form = new FormBuilder();
        $form->addQuestion("Question 1", QuestionTypeRadio::class, "", json_encode(new QuestionTypeSelectableExtraDataConfig([
            1 => 'Option 1',
            2 => 'Option to hide the question 2',
            3 => 'Option 3',
        ])));
        $form->addQuestion("Question 2", QuestionTypeRadio::class, "", json_encode(new QuestionTypeSelectableExtraDataConfig([
            1 => 'Option 1',
            2 => 'Option to show the question 3',
            3 => 'Option 3',
        ])));
        $form->addQuestion("Question 3", QuestionTypeShortText::class);
        $form->setQuestionVisibility(
            "Question 2",
            VisibilityStrategy::HIDDEN_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question 1",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "2", // Option to hide the question 2
                ],
            ]
        );
        $form->setQuestionVisibility(
            "Question 3",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question 2",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "2", // Option to show the question 3
                ],
            ]
        );

        yield 'all questions visible' => [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "1", // Option 1
                    'Question 2' => "1", // Option 1
                    'Question 3' => "",
                ],
            ],
            'expected_output' => [
                'questions' => [
                    'Question 1' => true,
                    'Question 2' => true,
                    'Question 3' => false,
                ],
            ],
        ];

        yield 'question 3 visible through question 2' => [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "1", // Option 1
                    'Question 2' => "2", // Option to show the question 3
                    'Question 3' => "",
                ],
            ],
            'expected_output' => [
                'questions' => [
                    'Question 1' => true,
                    'Question 2' => true,
                    'Question 3' => true,
                ],
            ],
        ];

        yield 'question 2 hidden so question 3 cannot be visible' => [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "2", // Option to hide the question 2
                    'Question 2' => "2", // Option to show the question 3 (but question 2 is not visible)
                    'Question 3' => "",
                ],
            ],
            'expected_output' => [
                'questions' => [
                    'Question 1' => true,
                    'Question 2' => false,
                    'Question 3' => false, // Question 3 is not visible because question 2 is not visible
                ],
            ],
        ];
    }

    #[DataProvider('conditionsOnForm')]
    #[DataProvider('conditionsOnQuestions')]
    #[DataProvider('conditionsOnComments')]
    #[DataProvider('conditionsOnSections')]
    #[DataProvider('firstSectionShouldAlwaysBeVisible')]
    #[DataProvider('conditionsOnQuestionWithNullExtraData')]
    #[DataProvider('conditionsOnQuestionsWithNotVisibleQuestionAsSource')]
    public function testComputation(
        FormBuilder $form,
        array $input,
        array $expected_output,
    ): void {
        // Arrange: create the form and build the correct input
        $form = $this->createForm($form);
        $input = $this->mapInput($form, $input);

        // Act: execute visibility engine
        $engine = new Engine($form, $input);
        $output = $engine->computeVisibility();

        // Assert: validate output
        $this->assertEquals(
            $expected_output['submit_button'] ?? true,
            $output->isFormVisible(),
            "Submit button does not have the expected visibility.",
        );
        foreach (($expected_output['questions'] ?? []) as $name => $expected_visibility) {
            $id = $this->getQuestionId($form, $name);
            $this->assertEquals(
                $expected_visibility,
                $output->isQuestionVisible($id),
                "Question '$name' does not have the expected visibility.",
            );
        }
        foreach (($expected_output['comments'] ?? []) as $name => $expected_visibility) {
            $id = $this->getCommentId($form, $name);
            $this->assertEquals(
                $expected_visibility,
                $output->isCommentVisible($id),
                "Comment '$name' does not have the expected visibility.",
            );
        }
        foreach (($expected_output['sections'] ?? []) as $name => $expected_visibility) {
            $id = $this->getSectionId($form, $name);
            $this->assertEquals(
                $expected_visibility,
                $output->isSectionVisible($id),
                "Section '$name' does not have the expected visibility.",
            );
        }
    }

    public static function conditionnalCreationProvider(): iterable
    {
        yield 'answer 1' => [
            'answer' => 'answer 1',
            'expected' => [
                'Ticket always created' => true,
                'Ticket created if answer 1' => true,
                'Ticket created if answer 2' => false,
                'Ticket created unless answer 1' => false,
                'Ticket created unless answer 2' => true,
            ],
        ];

        yield 'answer 2' => [
            'answer' => 'answer 2',
            'expected' => [
                'Ticket always created' => true,
                'Ticket created if answer 1' => false,
                'Ticket created if answer 2' => true,
                'Ticket created unless answer 1' => true,
                'Ticket created unless answer 2' => false,
            ],
        ];

        yield 'another answer' => [
            'answer' => 'another answer',
            'expected' => [
                'Ticket always created' => true,
                'Ticket created if answer 1' => false,
                'Ticket created if answer 2' => false,
                'Ticket created unless answer 1' => true,
                'Ticket created unless answer 2' => true,
            ],
        ];
    }

    #[DataProvider('conditionnalCreationProvider')]
    public function testConditionnalCreation(
        string $answer,
        array $expected
    ): void {
        // Arrange: create a complex form with multiples conditionnal destinations
        $form = new FormBuilder();
        $form->addQuestion("My answer", QuestionTypeShortText::class);
        $form->addDestination(FormDestinationTicket::class, "Ticket always created");
        $form->setDestinationCondition(
            "Ticket always created",
            CreationStrategy::ALWAYS_CREATED,
            [],
        );
        $form->addDestination(
            FormDestinationTicket::class,
            "Ticket created if answer 1"
        );
        $form->setDestinationCondition(
            "Ticket created if answer 1",
            CreationStrategy::CREATED_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "My answer",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "answer 1",
                ],
            ],
        );
        $form->addDestination(
            FormDestinationTicket::class,
            "Ticket created if answer 2"
        );
        $form->setDestinationCondition(
            "Ticket created if answer 2",
            CreationStrategy::CREATED_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "My answer",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "answer 2",
                ],
            ],
        );
        $form->addDestination(
            FormDestinationTicket::class,
            "Ticket created unless answer 1"
        );
        $form->setDestinationCondition(
            "Ticket created unless answer 1",
            CreationStrategy::CREATED_UNLESS,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "My answer",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "answer 1",
                ],
            ],
        );
        $form->addDestination(
            FormDestinationTicket::class,
            "Ticket created unless answer 2"
        );
        $form->setDestinationCondition(
            "Ticket created unless answer 2",
            CreationStrategy::CREATED_UNLESS,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "My answer",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "answer 2",
                ],
            ],
        );
        $form = $this->createForm($form);

        // Act: compute the conditions
        $input = $this->mapInput($form, ['answers' => ['My answer' => $answer]]);
        $engine = new Engine($form, $input);
        $output = $engine->computeItemsThatMustBeCreated();

        // Assert: validate the created tickets
        foreach ($expected as $name => $must_be_created) {
            $destination = FormDestination::getById(
                $this->getDestinationId($form, $name)
            );
            $this->assertEquals(
                $must_be_created,
                $output->itemMustBeCreated($destination)
            );
        }
    }

    public static function circularDependenciesProvider(): iterable
    {
        // Simple circular dependency between two questions
        $form1 = new FormBuilder();
        $form1->addQuestion("Question A", QuestionTypeShortText::class);
        $form1->addQuestion("Question B", QuestionTypeShortText::class);
        $form1->setQuestionVisibility(
            "Question A",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question B",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::VISIBLE,
                    'value'          => "",
                ],
            ]
        );
        $form1->setQuestionVisibility(
            "Question B",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question A",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::VISIBLE,
                    'value'          => "",
                ],
            ]
        );

        yield 'direct circular dependency' => [
            'form' => $form1,
            'expected_output' => [
                'questions' => [
                    'Question A' => false,
                    'Question B' => false,
                ],
            ],
        ];

        // Complex circular dependency between three questions
        $form2 = new FormBuilder();
        $form2->addQuestion("Question X", QuestionTypeShortText::class);
        $form2->addQuestion("Question Y", QuestionTypeShortText::class);
        $form2->addQuestion("Question Z", QuestionTypeShortText::class);
        $form2->setQuestionVisibility(
            "Question X",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question Y",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::VISIBLE,
                    'value'          => "",
                ],
            ]
        );
        $form2->setQuestionVisibility(
            "Question Y",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question Z",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::VISIBLE,
                    'value'          => "",
                ],
            ]
        );
        $form2->setQuestionVisibility(
            "Question Z",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question X",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::VISIBLE,
                    'value'          => "",
                ],
            ]
        );

        yield 'transitive circular dependency' => [
            'form' => $form2,
            'expected_output' => [
                'questions' => [
                    'Question X' => false,
                    'Question Y' => false,
                    'Question Z' => false,
                ],
            ],
        ];

        // Mixed circular dependencies with comments and sections
        $form3 = new FormBuilder();
        $form3->addQuestion("Question 1", QuestionTypeShortText::class);
        $form3->addComment("Comment 1");
        $form3->addSection("Section 1");
        $form3->setQuestionVisibility(
            "Question 1",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Comment 1",
                    'item_type'      => Type::COMMENT,
                    'value_operator' => ValueOperator::VISIBLE,
                    'value'          => "",
                ],
            ]
        );
        $form3->setCommentVisibility(
            "Comment 1",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Section 1",
                    'item_type'      => Type::SECTION,
                    'value_operator' => ValueOperator::VISIBLE,
                    'value'          => "",
                ],
            ]
        );
        $form3->setSectionVisibility(
            "Section 1",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question 1",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::VISIBLE,
                    'value'          => "",
                ],
            ]
        );

        yield 'mixed item types circular dependency' => [
            'form' => $form3,
            'expected_output' => [
                'questions' => [
                    'Question 1' => false,
                ],
                'comments' => [
                    'Comment 1' => false,
                ],
                'sections' => [
                    'Section 1' => false,
                ],
            ],
        ];
    }

    #[DataProvider('circularDependenciesProvider')]
    public function testCircularDependencies(
        FormBuilder $form,
        array $expected_output
    ): void {
        // Arrange: create the form
        $form = $this->createForm($form);
        $input = new EngineInput([]);

        // Act: execute visibility engine
        $engine = new Engine($form, $input);
        $output = $engine->computeVisibility();

        // Assert: validate output - all items in a circular dependency should be invisible
        foreach (($expected_output['questions'] ?? []) as $name => $expected_visibility) {
            $id = $this->getQuestionId($form, $name);
            $this->assertEquals(
                $expected_visibility,
                $output->isQuestionVisible($id),
                "Question '$name' does not have the expected visibility.",
            );
        }
        foreach (($expected_output['comments'] ?? []) as $name => $expected_visibility) {
            $id = $this->getCommentId($form, $name);
            $this->assertEquals(
                $expected_visibility,
                $output->isCommentVisible($id),
                "Comment '$name' does not have the expected visibility.",
            );
        }
        foreach (($expected_output['sections'] ?? []) as $name => $expected_visibility) {
            $id = $this->getSectionId($form, $name);
            $this->assertEquals(
                $expected_visibility,
                $output->isSectionVisible($id),
                "Section '$name' does not have the expected visibility.",
            );
        }
    }

    public function testSectionsWithoutVisibleChildrenAreHidden(): void
    {
        // Arrange: create a form with two sections
        // The second section will contain a single question that can be hidden.
        $builder = new FormBuilder("My form");
        $builder->addSection("Section 1");
        $builder->addQuestion("Question 1", QuestionTypeShortText::class);
        $builder->addSection("Section 2");
        $builder->addQuestion("Question 2", QuestionTypeShortText::class);
        $builder->setQuestionVisibility(
            "Question 2",
            VisibilityStrategy::HIDDEN_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question 1",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "hide question 2",
                ],
            ]
        );
        $form = $this->createForm($builder);

        // Act: compute visiblity
        $input = $this->mapInput($form, [
            'answers' => [
                'Question 1' => 'hide question 2',
            ],
        ]);
        $engine = new Engine($form, $input);
        $output = $engine->computeVisibility();

        // Assert: since question 2 is hidden, section 2 should be hidden too
        $this->assertFalse($output->isQuestionVisible(
            $this->getQuestionId($form, "Question 2"),
        ));
        $this->assertFalse($output->isSectionVisible(
            $this->getSectionId($form, "Section 2"),
        ));
    }

    /**
     * Transform a simplified raw input that uses questions names by a real
     * EngineInput object with the correct ids.
     */
    private function mapInput(Form $form, array $raw_input): EngineInput
    {
        $answers = [];
        foreach ($raw_input['answers'] as $question_name => $answer) {
            $question_id = $this->getQuestionId($form, $question_name);
            $answers[$question_id] = $answer;
        }

        return new EngineInput($answers);
    }
}
