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

use Glpi\Form\Condition\ConditionData;
use Glpi\Form\Condition\EditorManager;
use Glpi\Form\Condition\FormData;
use Glpi\Form\Condition\InputTemplateKey;
use Glpi\Form\Condition\LogicOperator;
use Glpi\Form\Condition\ValueOperator;
use Glpi\Form\QuestionType\QuestionTypeFile;
use Glpi\Form\QuestionType\QuestionTypeNumber;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use GLPITestCase;

final class EditorManagerTest extends GLPITestCase
{
    private function getManagerWithData(FormData $form_data): EditorManager
    {
        $editor_manager = new EditorManager();
        $editor_manager->setFormData($form_data);
        return $editor_manager;
    }

    public function testDefaultConditionIsAdded(): void
    {
        // Arrange: create an editor manager with no conditions
        $form_data = new FormData([]);
        $editor_manager = $this->getManagerWithData($form_data);

        // Act: get the defined conditions
        $conditions = $editor_manager->getDefinedConditions();

        // Assert: the defined conditions should contain a single default condition
        $this->assertEquals($conditions, [
            new ConditionData(
                item_uuid: '',
                item_type: '',
                value_operator: null,
                value: null,
            )
        ]);
    }

    public function testConditionsAreFound(): void
    {
        // Arrange: create an editor manager with two conditions
        $form_data = new FormData([
            'conditions' => [
                [
                    'item' => 'question-1',
                    'value_operator' => ValueOperator::EQUALS->value,
                    'value' => 'foo',
                ],
                [
                    'logic_operator' => LogicOperator::OR->value,
                    'item' => 'question-2',
                    'value_operator' => ValueOperator::EQUALS->value,
                    'value' => 'bar',
                ],
            ],
        ]);
        $editor_manager = $this->getManagerWithData($form_data);

        // Act: get the defined conditions
        $conditions = $editor_manager->getDefinedConditions();

        // Assert: the two defined conditions should be found
        $this->assertEquals($conditions, [
            new ConditionData(
                item_uuid: 1,
                item_type: 'question',
                value_operator: ValueOperator::EQUALS->value,
                value: 'foo',
            ),
            new ConditionData(
                item_uuid: 2,
                item_type: 'question',
                logic_operator: LogicOperator::OR->value,
                value_operator: ValueOperator::EQUALS->value,
                value: 'bar',
            ),
        ]);
    }

    public function testConditionsHaveDefaultLogicOperators(): void
    {
        // Arrange: create an editor manager with one conditon without logic operator
        $form_data = new FormData([
            'conditions' => [
                [
                    'item' => 'question-1',
                    'value_operator' => ValueOperator::EQUALS->value,
                    'value' => 'foo',
                ],
            ],
        ]);
        $editor_manager = $this->getManagerWithData($form_data);

        // Act: get the defined condition
        $conditions = $editor_manager->getDefinedConditions();
        $condition = array_pop($conditions);

        // Assert: the fallback "AND" operator must be found
        $this->assertEquals(LogicOperator::AND, $condition->getLogicOperator());
    }

    public function testOnlyValidQuestionsAreReturnedForDropdowns(): void
    {
        // Arrange: create an editor manager with three questions
        $form_data = new FormData([
            'questions' => [
                [
                    'uuid' => 1,
                    'name' => 'Question 1',
                    'type' => QuestionTypeShortText::class,
                ],
                [
                    'uuid' => 2,
                    'name' => 'Question 2',
                    'type' => QuestionTypeFile::class, // do not support conditions
                ],
                [
                    'uuid' => 3,
                    'name' => 'Question 3',
                    'type' => QuestionTypeShortText::class,
                ],
            ],
        ]);
        $editor_manager = $this->getManagerWithData($form_data);

        // Act: get the questions dropdown values
        $dropdown_values = $editor_manager->getItemsDropdownValues();

        // Assert: the dropdown values should not contains the "Question 2"
        // question that do not support conditions.
        $this->assertEquals($dropdown_values, [
            'question-1' => 'Question 1',
            'question-3' => 'Question 3',
        ]);
    }

    public function testSelectedQuestionIsExcludedFromDropdownValues(): void
    {
        // Arrange: create an editor manager with three questions
        // The third question is selected.
        $form_data = new FormData([
            'questions' => [
                [
                    'uuid' => 1,
                    'name' => 'Question 1',
                    'type' => QuestionTypeShortText::class,
                ],
                [
                    'uuid' => 2,
                    'name' => 'Question 2',
                    'type' => QuestionTypeShortText::class,
                ],
                [
                    'uuid' => 3,
                    'name' => 'Question 3',
                    'type' => QuestionTypeShortText::class,
                ],
            ],
            'selected_item_uuid' => 3,
            'selected_item_type' => 'question',
        ]);
        $editor_manager = $this->getManagerWithData($form_data);

        // Act: get the questions dropdown values
        $dropdown_values = $editor_manager->getItemsDropdownValues();

        // Assert: the dropdown values should not contains "Question 3" as it
        // is selected.
        $this->assertEquals($dropdown_values, [
            'question-1' => 'Question 1',
            'question-2' => 'Question 2',
        ]);
    }

    public function testValueOperatorsForValidQuestionsAreReturned(): void
    {
        // Arrange: create an editor manager with one question that supports conditions.
        $form_data = new FormData([
            'questions' => [
                [
                    'uuid' => 1,
                    'name' => 'Question 1',
                    'type' => QuestionTypeShortText::class,
                ],
            ],
        ]);
        $editor_manager = $this->getManagerWithData($form_data);

        // Act: get the operators for the question.
        $dropdown_values = $editor_manager->getValueOperatorDropdownValues(1);

        // Assert: the expected operators for a "ShortText" questions should be found.
        $this->assertEquals([
            'equals'       => __("Is equal to"),
            'not_equals'   => __("Is not equal to"),
            'contains'     => __("Contains"),
            'not_contains' => __("Do not contains"),
        ], $dropdown_values);
    }

    public function testValueOperatorsForInvalidQuestionAreNotReturned(): void
    {
        // Arrange: create an editor manager with one invalid question
        $form_data = new FormData([
            'questions' => [
                [
                    'uuid' => 2,
                    'name' => 'Question 2',
                    'type' => QuestionTypeFile::class, // do not support conditions
                ],
            ],
        ]);
        $editor_manager = $this->getManagerWithData($form_data);

        // Act: get the operators the question.
        $dropdown_values = $editor_manager->getValueOperatorDropdownValues(2);

        // Assert: no operators should be found.
        $this->assertEquals([], $dropdown_values);
    }

    public function testInputTemplateIsProvidedForCondition(): void
    {
        // Arrange: create an editor manager with some data
        $condition_1 = new ConditionData(
            item_uuid: '1',
            item_type: 'question',
            value_operator: ValueOperator::EQUALS->value,
            value: "foo",
        );
        $condition_2 = new ConditionData(
            item_uuid: '2',
            item_type: 'question',
            value_operator: ValueOperator::EQUALS->value,
            value: 42,
        );
        $form_data = new FormData([
            'questions' => [
                [
                    'uuid' => 1,
                    'name' => 'Question 1',
                    'type' => QuestionTypeShortText::class,
                ],
                [
                    'uuid' => 2,
                    'name' => 'Question 2',
                    'type' => QuestionTypeNumber::class,
                ],
                [
                    'uuid' => 3,
                    'name' => 'Question 3',
                    'type' => QuestionTypeShortText::class,
                ],
            ],
            'conditions' => [
                [
                    'item'           => $condition_1->getItemDropdownKey(),
                    'value_operator' => $condition_1->getValueOperator()->value,
                    'value'          => $condition_1->getValue(),
                ],
                [
                    'logic_operator' => LogicOperator::AND->value,
                    'item'           => $condition_2->getItemDropdownKey(),
                    'value_operator' => $condition_2->getValueOperator()->value,
                    'value'          => $condition_2->getValue(),
                ],
            ]
        ]);
        $editor_manager = $this->getManagerWithData($form_data);

        // Act: get the input templates for the conditions
        $text_input_template = $editor_manager->getInputTemplateForCondition(
            $condition_1,
            "test_input_name"
        );
        $number_input_template = $editor_manager->getInputTemplateForCondition(
            $condition_2,
            "test_input_name"
        );

        // Assert: templates should be valid HTML with appropriate input types
        $this->assertStringContainsString('<input', $text_input_template);
        $this->assertStringContainsString('name="test_input_name"', $text_input_template);
        $this->assertStringContainsString('value="foo"', $text_input_template);

        $this->assertStringContainsString('<input', $number_input_template);
        $this->assertStringContainsString('name="test_input_name"', $number_input_template);
        $this->assertStringContainsString('value="42"', $number_input_template);
        $this->assertStringContainsString('type="number"', $number_input_template);
        $this->assertStringContainsString('step="any"', $number_input_template);
    }
}
