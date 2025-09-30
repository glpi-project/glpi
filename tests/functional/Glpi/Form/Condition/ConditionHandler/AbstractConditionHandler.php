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
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\Condition\ConditionHandler\ConditionHandlerInterface;
use Glpi\Form\Condition\Engine;
use Glpi\Form\Condition\EngineInput;
use Glpi\Form\Condition\LogicOperator;
use Glpi\Form\Condition\Type;
use Glpi\Form\Condition\ValueOperator;
use Glpi\Form\Condition\VisibilityStrategy;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use PHPUnit\Framework\Attributes\DataProvider;

abstract class AbstractConditionHandler extends DbTestCase
{
    use FormTesterTrait;

    abstract public static function conditionHandlerProvider(): iterable;

    /**
     * Get the condition handler instance.
     * This method must return an instance of `ConditionHandlerInterface` or an array of such instances.
     * It is used to provide the condition handler for the tests.
     *
     * @return ConditionHandlerInterface|ConditionHandlerInterface[] An instance of the condition handler or an array of instances.
     */
    abstract public static function getConditionHandler(): ConditionHandlerInterface|array;

    public function testAllAvailableOperatorsAreProvided(): void
    {
        $handlers = $this->getConditionHandler();
        if (!is_array($handlers)) {
            $handlers = [$handlers];
        }

        $available_operators = array_map(
            fn($op) => $op->value,
            array_merge(
                ...array_map(
                    fn(ConditionHandlerInterface $handler) => $handler->getSupportedValueOperators(),
                    $handlers
                )
            )
        );
        $current_operators = array_unique(
            array_map(
                fn($op) => $op['condition_operator']->value,
                iterator_to_array($this->conditionHandlerProvider())
            )
        );

        $this->assertSame(
            array_values($available_operators),
            array_values($current_operators),
            'All available operators must be provided by the condition handler',
        );
    }

    #[DataProvider('conditionHandlerProvider')]
    public function testConditionHandlerProvider(
        string $question_type,
        ValueOperator $condition_operator,
        mixed $condition_value,
        mixed $submitted_answer,
        bool $expected_result,
        ?JsonFieldInterface $question_extra_data = null,
    ): void {
        $this->login();

        // Arrange: create a form with a condition on which we will add a condition.
        $form = new FormBuilder();
        $form->addQuestion(
            name: "My condition",
            type: $question_type,
            extra_data: $question_extra_data ? json_encode($question_extra_data) : '',
        );
        $form->addQuestion("Test subject", QuestionTypeShortText::class);
        $form->setQuestionVisibility("Test subject", VisibilityStrategy::VISIBLE_IF, [
            [
                'logic_operator' => LogicOperator::AND,
                'item_name'      => "My condition",
                'item_type'      => Type::QUESTION,
                'value_operator' => $condition_operator,
                'value'          => $condition_value,
            ],
        ]);

        $form = $this->createForm($form);
        $input = $this->mapInput($form, [
            'answers' => ['My condition' => $submitted_answer],
        ]);

        // Act: execute visibility engine
        $engine = new Engine($form, $input);
        $output = $engine->computeVisibility();

        // Assert: validate output
        $id = $this->getQuestionId($form, "Test subject");
        $this->assertEquals(
            $expected_result,
            $output->isQuestionVisible($id),
        );
    }

    /**
    * Transform a simplified raw input that uses questions names by a real
    * EngineInput object with the correct ids.
    */
    protected function mapInput(Form $form, array $raw_input): EngineInput
    {
        $answers = [];
        foreach ($raw_input['answers'] as $question_name => $answer) {
            $question_id = $this->getQuestionId($form, $question_name);
            $answers[$question_id] = $answer;
        }

        return new EngineInput($answers);
    }
}
