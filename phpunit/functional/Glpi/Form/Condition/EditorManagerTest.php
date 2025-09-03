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
use Glpi\Form\Condition\ConditionHandler\NumberConditionHandler;
use Glpi\Form\Condition\ConditionHandler\StringConditionHandler;
use Glpi\Form\Condition\ConditionHandler\VisibilityConditionHandler;
use Glpi\Form\Condition\EditorManager;
use Glpi\Form\Condition\FormData;
use Glpi\Form\Condition\LogicOperator;
use Glpi\Form\Condition\ValueOperator;
use Glpi\Form\QuestionType\QuestionTypeFile;
use Glpi\Form\QuestionType\QuestionTypeNumber;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;

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
            ),
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
                [
                    'logic_operator' => LogicOperator::AND->value,
                    'item' => 'section-1',
                    'value_operator' => ValueOperator::VISIBLE->value,
                ],

                [
                    'logic_operator' => LogicOperator::AND->value,
                    'item' => 'comment-1',
                    'value_operator' => ValueOperator::VISIBLE->value,
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
            new ConditionData(
                item_uuid: 1,
                item_type: 'section',
                logic_operator: LogicOperator::AND->value,
                value_operator: ValueOperator::VISIBLE->value,
                value: null,
            ),
            new ConditionData(
                item_uuid: 1,
                item_type: 'comment',
                logic_operator: LogicOperator::AND->value,
                value_operator: ValueOperator::VISIBLE->value,
                value: null,
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
                    'type' => QuestionTypeFile::class, // only support visibility conditions
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
            'Questions' => [
                'question-1' => 'Question 1',
                'question-2' => 'Question 2',
                'question-3' => 'Question 3',
            ],
        ]);
    }

    public static function provideSelectedFormElementIsExcludedFromDropdownValues(): iterable
    {
        yield 'section' => [
            'selected_item_uuid' => 1,
            'selected_item_type' => 'section',
            'expected' => [
                'Questions' => [
                    'question-1' => 'Question 1',
                    'question-2' => 'Question 2',
                    'question-3' => 'Question 3',
                ],
                'Sections' => [
                    'section-2' => 'Section 2',
                    'section-3' => 'Section 3',
                ],
                'Comments' => [
                    'comment-1' => 'Comment 1',
                    'comment-2' => 'Comment 2',
                    'comment-3' => 'Comment 3',
                ],
            ],
        ];

        yield 'question' => [
            'selected_item_uuid' => 1,
            'selected_item_type' => 'question',
            'expected' => [
                'Questions' => [
                    'question-2' => 'Question 2',
                    'question-3' => 'Question 3',
                ],
                'Sections' => [
                    'section-1' => 'Section 1',
                    'section-2' => 'Section 2',
                    'section-3' => 'Section 3',
                ],
                'Comments' => [
                    'comment-1' => 'Comment 1',
                    'comment-2' => 'Comment 2',
                    'comment-3' => 'Comment 3',
                ],
            ],
        ];

        yield 'comment' => [
            'selected_item_uuid' => 1,
            'selected_item_type' => 'comment',
            'expected' => [
                'Questions' => [
                    'question-1' => 'Question 1',
                    'question-2' => 'Question 2',
                    'question-3' => 'Question 3',
                ],
                'Sections' => [
                    'section-1' => 'Section 1',
                    'section-2' => 'Section 2',
                    'section-3' => 'Section 3',
                ],
                'Comments' => [
                    'comment-2' => 'Comment 2',
                    'comment-3' => 'Comment 3',
                ],
            ],
        ];
    }

    #[DataProvider('provideSelectedFormElementIsExcludedFromDropdownValues')]
    public function testSelectedFormElementIsExcludedFromDropdownValues(
        int $selected_item_uuid,
        string $selected_item_type,
        array $expected
    ): void {
        // Arrange: create an editor manager with questions, sections and comments
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
            'sections' => [
                [
                    'uuid' => 1,
                    'name' => 'Section 1',
                ],
                [
                    'uuid' => 2,
                    'name' => 'Section 2',
                ],
                [
                    'uuid' => 3,
                    'name' => 'Section 3',
                ],
            ],
            'comments' => [
                [
                    'uuid' => 1,
                    'name' => 'Comment 1',
                ],
                [
                    'uuid' => 2,
                    'name' => 'Comment 2',
                ],
                [
                    'uuid' => 3,
                    'name' => 'Comment 3',
                ],
            ],
            'selected_item_uuid' => $selected_item_uuid,
            'selected_item_type' => $selected_item_type,
        ]);
        $editor_manager = $this->getManagerWithData($form_data);

        // Act: get the dropdown values
        $dropdown_values = $editor_manager->getItemsDropdownValues();

        // Assert: the dropdown values should not contain the selected item
        $this->assertEquals($expected, $dropdown_values);
    }

    public static function provideValueOperatorsForValidFormElementsAreReturned(): iterable
    {
        yield 'Question' => [
            'uuid' => "1",
            'expected' => [
                'equals'                        => __('Is equal to'),
                'not_equals'                    => __('Is not equal to'),
                'contains'                      => __('Contains'),
                'not_contains'                  => __('Do not contains'),
                'visible'                       => __('Is visible'),
                'not_visible'                   => __('Is not visible'),
                'match_regex'                   => __("Match regular expression"),
                'not_match_regex'               => __("Do not match regular expression"),
                'length_greater_than'           => __('Length is greater than'),
                'length_greater_than_or_equals' => __('Length is greater than or equals to'),
                'length_less_than'              => __('Length is less than'),
                'length_less_than_or_equals'    => __('Length is less than or equals to'),
                'empty'                         => __('Is empty'),
                'not_empty'                     => __('Is not empty'),
            ],
        ];

        yield 'Section' => [
            'uuid' => "2",
            'expected' => [
                'visible'     => __('Is visible'),
                'not_visible' => __('Is not visible'),
            ],
        ];

        yield 'Comment' => [
            'uuid' => "3",
            'expected' => [
                'visible'     => __('Is visible'),
                'not_visible' => __('Is not visible'),
            ],
        ];
    }

    #[DataProvider('provideValueOperatorsForValidFormElementsAreReturned')]
    public function testValueOperatorsForValidFormElementsAreReturned(
        string $uuid,
        array $expected
    ): void {
        // Arrange: create an editor manager with one question that supports conditions.
        $form_data = new FormData([
            'questions' => [
                [
                    'uuid' => 1,
                    'name' => 'Question 1',
                    'type' => QuestionTypeShortText::class,
                ],
            ],
            'sections' => [
                [
                    'uuid' => 2,
                    'name' => 'Section 1',
                ],
            ],
            'comments' => [
                [
                    'uuid' => 3,
                    'name' => 'Comment 1',
                ],
            ],
        ]);
        $editor_manager = $this->getManagerWithData($form_data);

        // Act: get the operators for the question.
        $dropdown_values = $editor_manager->getValueOperatorDropdownValues($uuid);

        // Assert: the expected operators for a "ShortText" questions should be found.
        $this->assertEquals($expected, $dropdown_values);
    }

    public function testValueOperatorsForInvalidQuestionAreNotReturned(): void
    {
        // Arrange: create an editor manager with one invalid question
        $form_data = new FormData([
            'questions' => [
                [
                    'uuid' => 2,
                    'name' => 'Question 2',
                    'type' => QuestionTypeFile::class, // only support visibility and regex conditions
                ],
            ],
        ]);
        $editor_manager = $this->getManagerWithData($form_data);

        // Act: get the operators the question.
        $dropdown_values = $editor_manager->getValueOperatorDropdownValues(2);

        // Assert: no operators should be found.
        $this->assertEquals([
            'visible'         => __("Is visible"),
            'not_visible'     => __("Is not visible"),
            'match_regex'     => __("Match regular expression"),
            'not_match_regex' => __("Do not match regular expression"),
            'empty'           => __("Is empty"),
            'not_empty'       => __("Is not empty"),
        ], $dropdown_values);
    }

    public static function provideHandlerCanBeComputedFromConditionData(): iterable
    {
        yield 'string question' => [
            'condition' => new ConditionData(
                item_uuid: '1',
                item_type: 'question',
                value_operator: ValueOperator::EQUALS->value,
                value: "foo",
            ),
            'expected_handler' => StringConditionHandler::class,
            'form_element' => [
                'uuid' => 1,
                'name' => 'Question 1',
                'type' => QuestionTypeShortText::class,
            ],
            'element_type' => 'questions',
        ];

        yield 'number question' => [
            'condition' => new ConditionData(
                item_uuid: '2',
                item_type: 'question',
                value_operator: ValueOperator::EQUALS->value,
                value: "42",
            ),
            'expected_handler' => NumberConditionHandler::class,
            'form_element' => [
                'uuid' => 2,
                'name' => 'Question 2',
                'type' => QuestionTypeNumber::class,
            ],
            'element_type' => 'questions',
        ];

        yield 'visibility question' => [
            'condition' => new ConditionData(
                item_uuid: '3',
                item_type: 'question',
                value_operator: ValueOperator::VISIBLE->value,
                value: null,
            ),
            'expected_handler' => VisibilityConditionHandler::class,
            'form_element' => [
                'uuid' => 3,
                'name' => 'Question 3',
                'type' => QuestionTypeShortText::class,
            ],
            'element_type' => 'questions',
        ];

        yield 'section visibility' => [
            'condition' => new ConditionData(
                item_uuid: '4',
                item_type: 'section',
                value_operator: ValueOperator::VISIBLE->value,
                value: null,
            ),
            'expected_handler' => VisibilityConditionHandler::class,
            'form_element' => [
                'uuid' => 4,
                'name' => 'Section 1',
            ],
            'element_type' => 'sections',
        ];

        yield 'comment visibility' => [
            'condition' => new ConditionData(
                item_uuid: '5',
                item_type: 'comment',
                value_operator: ValueOperator::VISIBLE->value,
                value: null,
            ),
            'expected_handler' => VisibilityConditionHandler::class,
            'form_element' => [
                'uuid' => 5,
                'name' => 'Comment 1',
            ],
            'element_type' => 'comments',
        ];
    }

    #[DataProvider('provideHandlerCanBeComputedFromConditionData')]
    public function testHandlerCanBeComputedFromConditionData(
        ConditionData $condition,
        string $expected_handler,
        array $form_element,
        string $element_type
    ): void {
        // Arrange: create an editor manager with form data
        $form_data_array = [
            $element_type => [$form_element],
            'conditions' => [
                [
                    'item' => $condition->getItemDropdownKey(),
                    'value_operator' => $condition->getValueOperator()->value,
                ],
            ],
        ];

        // Add value if not null
        if ($condition->getValue() !== null) {
            $form_data_array['conditions'][0]['value'] = $condition->getValue();
        }

        $form_data = new FormData($form_data_array);
        $editor_manager = $this->getManagerWithData($form_data);

        // Act: get the handler using the condition
        $handler = $editor_manager->getHandlerForCondition($condition);

        // Assert: the correct handler class must be found
        $this->assertInstanceOf($expected_handler, $handler);
    }
}
