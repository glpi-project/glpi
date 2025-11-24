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

namespace tests\units\Glpi\Form;

use Computer;
use Glpi\Tests\DbTestCase;
use Glpi\Form\Condition\LogicOperator;
use Glpi\Form\Condition\Type;
use Glpi\Form\Condition\ValidationStrategy;
use Glpi\Form\Condition\ValueOperator;
use Glpi\Form\Condition\VisibilityStrategy;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\AbstractQuestionTypeShortAnswer;
use Glpi\Form\QuestionType\QuestionTypeEmail;
use Glpi\Form\QuestionType\QuestionTypeLongText;
use Glpi\Form\QuestionType\QuestionTypeNumber;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use PHPUnit\Framework\Attributes\DataProvider;

class QuestionTest extends DbTestCase
{
    use FormTesterTrait;

    public static function getQuestionTypeProvider(): iterable
    {
        // First set of tests: valid values
        $question = new Question();
        $question->fields = [
            'type' => QuestionTypeShortText::class,
        ];
        yield [$question, new QuestionTypeShortText()];

        $question = new Question();
        $question->fields = [
            'type' => QuestionTypeNumber::class,
        ];
        yield [$question, new QuestionTypeNumber()];

        $question = new Question();
        $question->fields = [
            'type' => QuestionTypeEmail::class,
        ];
        yield [$question, new QuestionTypeEmail()];

        $question = new Question();
        $question->fields = [
            'type' => QuestionTypeLongText::class,
        ];
        yield [$question, new QuestionTypeLongText()];

        // Second set: Invalid values
        $question = new Question();
        $question->fields = [
            'type' => "not a type",
        ];
        yield [$question, null];

        $question = new Question();
        $question->fields = [
            'type' => Computer::class,
        ];
        yield [$question, null];

        $question = new Question();
        $question->fields = [
            'type' => AbstractQuestionTypeShortAnswer::class,
        ];
        yield [$question, null];
    }

    #[DataProvider('getQuestionTypeProvider')]
    public function testGetQuestionType(Question $question, $expected): void
    {
        $type = $question->getQuestionType();
        $this->assertEquals($expected, $type);
    }

    public function testVisibilityConditionsDataAreCleanedWhenStrategyIsReset(): void
    {
        // Arrange: create a form with visibility conditions on a question
        $builder = new FormBuilder();
        $builder->addQuestion("My question", QuestionTypeShortText::class);
        $builder->addQuestion("My other question", QuestionTypeShortText::class);
        $builder->setQuestionVisibility(
            "My other question",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "My question",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "Yes",
                ],
            ]
        );
        $form = $this->createForm($builder);

        // Act: reset the question's visibility strategy
        $question_id = $this->getQuestionId($form, "My other question");
        $question = $this->updateItem(Question::class, $question_id, [
            'visibility_strategy' => VisibilityStrategy::ALWAYS_VISIBLE->value,
        ]);

        // Assert: the conditions should be deleted
        $this->assertEmpty($question->getConfiguredConditionsData());
    }

    public function testValidationConditionsDataAreCleanedWhenStrategyIsReset(): void
    {
        // Arrange: create a form with visibility conditions on a question
        $builder = new FormBuilder();
        $builder->addQuestion("My question", QuestionTypeShortText::class);
        $builder->addQuestion("My other question", QuestionTypeShortText::class);
        $builder->setQuestionValidation(
            "My other question",
            ValidationStrategy::VALID_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "My question",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "Yes",
                ],
            ]
        );
        $form = $this->createForm($builder);

        // Act: reset the question's validation strategy
        $question_id = $this->getQuestionId($form, "My other question");
        $question = $this->updateItem(Question::class, $question_id, [
            'validation_strategy' => ValidationStrategy::NO_VALIDATION->value,
        ]);

        // Assert: the conditions should be deleted
        $this->assertEmpty($question->getConfiguredValidationConditionsData());
    }
}
