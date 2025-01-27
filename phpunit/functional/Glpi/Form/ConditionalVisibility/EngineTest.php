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

namespace tests\units\Glpi\Form\ConditionalVisiblity;

use DbTestCase;
use Glpi\Form\ConditionalVisiblity\Engine;
use Glpi\Form\ConditionalVisiblity\EngineInput;
use Glpi\Form\ConditionalVisiblity\LogicOperator;
use Glpi\Form\ConditionalVisiblity\ValueOperator;
use Glpi\Form\ConditionalVisiblity\VisibilityStrategy;
use Glpi\Form\ConditionalVisiblity\Type;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use PHPUnit\Framework\Attributes\DataProvider;

final class EngineTest extends DbTestCase
{
    use FormTesterTrait;

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
                ]
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
                ]
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
                ]
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
                ]
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

    #[DataProvider('conditionsOnQuestions')]
    #[DataProvider('conditionsOnComments')]
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
