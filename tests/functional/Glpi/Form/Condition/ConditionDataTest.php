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
use Glpi\Form\Comment;
use Glpi\Form\Condition\ConditionData;
use Glpi\Form\Condition\LogicOperator;
use Glpi\Form\Condition\Type;
use Glpi\Form\Condition\ValueOperator;
use Glpi\Form\Form;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeNumber;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\QuestionType\QuestionTypeUserDevice;
use Glpi\Form\QuestionType\QuestionTypeUserDevicesConfig;
use Glpi\Form\Section;
use PHPUnit\Framework\Attributes\DataProvider;

final class ConditionDataTest extends DbTestCase
{
    public static function provideValidConditions(): iterable
    {
        yield 'Valid condition with EQUALS operator' => [
            'item_uuid' => null,
            'item_type' => Type::QUESTION->value,
            'value_operator' => ValueOperator::EQUALS->value,
            'value' => 'test_value',
            'logic_operator' => LogicOperator::AND->value,
            'expected' => true,
        ];

        yield 'Valid condition with VISIBLE operator' => [
            'item_uuid' => null,
            'item_type' => Type::SECTION->value,
            'value_operator' => ValueOperator::VISIBLE->value,
            'value' => null,
            'logic_operator' => LogicOperator::OR->value,
            'expected' => true,
        ];

        yield 'Valid condition with empty value' => [
            'item_uuid' => null,
            'item_type' => Type::COMMENT->value,
            'value_operator' => ValueOperator::VISIBLE->value,
            'value' => '',
            'logic_operator' => null,
            'expected' => true,
        ];

        yield 'Valid condition with null value' => [
            'item_uuid' => null,
            'item_type' => Type::QUESTION->value,
            'value_operator' => ValueOperator::CONTAINS->value,
            'value' => null,
            'logic_operator' => LogicOperator::AND->value,
            'expected' => true,
        ];
    }

    public static function provideInvalidConditions(): iterable
    {
        yield 'Empty item UUID' => [
            'item_uuid' => '',
            'item_type' => Type::QUESTION->value,
            'value_operator' => ValueOperator::EQUALS->value,
            'value' => 'test_value',
            'logic_operator' => LogicOperator::AND->value,
            'expected' => false,
        ];

        yield 'Invalid item type' => [
            'item_uuid' => null,
            'item_type' => 'invalid_type',
            'value_operator' => ValueOperator::EQUALS->value,
            'value' => 'test_value',
            'logic_operator' => LogicOperator::AND->value,
            'expected' => false,
        ];

        yield 'Null value operator' => [
            'item_uuid' => null,
            'item_type' => Type::QUESTION->value,
            'value_operator' => null,
            'value' => 'test_value',
            'logic_operator' => LogicOperator::AND->value,
            'expected' => false,
        ];

        yield 'Empty value operator' => [
            'item_uuid' => null,
            'item_type' => Type::QUESTION->value,
            'value_operator' => '',
            'value' => 'test_value',
            'logic_operator' => LogicOperator::AND->value,
            'expected' => false,
        ];

        yield 'Invalid value operator' => [
            'item_uuid' => null,
            'item_type' => Type::QUESTION->value,
            'value_operator' => 'invalid_operator',
            'value' => 'test_value',
            'logic_operator' => LogicOperator::AND->value,
            'expected' => false,
        ];
    }

    #[DataProvider('provideValidConditions')]
    public function testIsValidReturnsTrueForValidConditions(
        ?string $item_uuid,
        string $item_type,
        ?string $value_operator,
        mixed $value,
        ?string $logic_operator,
        bool $expected
    ): void {
        $form = $this->createItem(Form::class, []);
        $section = current($form->getSections());
        $question = $this->createItem(Question::class, [
            Section::getForeignKeyField() => $section->getID(),
            'type'                        => QuestionTypeShortText::class,
        ]);
        $comment = $this->createItem(Comment::class, [
            Section::getForeignKeyField() => $section->getID(),
        ]);

        if ($item_uuid === null) {
            $item_uuid = match ($item_type) {
                Type::QUESTION->value => $question->getUuid(),
                Type::SECTION->value  => $section->getUuid(),
                Type::COMMENT->value  => $comment->getUuid(),
                default               => null,
            };
        }

        $condition = new ConditionData(
            item_uuid: $item_uuid,
            item_type: $item_type,
            value_operator: $value_operator,
            value: $value,
            logic_operator: $logic_operator
        );

        $this->assertEquals($expected, $condition->isValid());
    }

    #[DataProvider('provideInvalidConditions')]
    public function testIsValidReturnsFalseForInvalidConditions(
        ?string $item_uuid,
        string $item_type,
        ?string $value_operator,
        mixed $value,
        ?string $logic_operator,
        bool $expected
    ): void {
        $form = $this->createItem(Form::class, []);
        $question = $this->createItem(Question::class, [
            Section::getForeignKeyField() => current($form->getSections())->getID(),
            'type'                        => QuestionTypeShortText::class,
        ]);

        $condition = new ConditionData(
            item_uuid: $item_uuid ?? $question->getUuid(),
            item_type: $item_type,
            value_operator: $value_operator,
            value: $value,
            logic_operator: $logic_operator
        );

        $this->assertEquals($expected, $condition->isValid());
    }

    public function testIsValidWithAllValueOperators(): void
    {
        $validOperators = [
            ValueOperator::EQUALS->value                        => QuestionTypeShortText::class,
            ValueOperator::NOT_EQUALS->value                    => QuestionTypeShortText::class,
            ValueOperator::CONTAINS->value                      => QuestionTypeShortText::class,
            ValueOperator::NOT_CONTAINS->value                  => QuestionTypeShortText::class,
            ValueOperator::GREATER_THAN->value                  => QuestionTypeNumber::class,
            ValueOperator::GREATER_THAN_OR_EQUALS->value        => QuestionTypeNumber::class,
            ValueOperator::LESS_THAN->value                     => QuestionTypeNumber::class,
            ValueOperator::LESS_THAN_OR_EQUALS->value           => QuestionTypeNumber::class,
            ValueOperator::IS_ITEMTYPE->value                   => [
                'type_class' => QuestionTypeUserDevice::class,
                'extra_data' => json_encode((new QuestionTypeUserDevicesConfig(false))->jsonSerialize()),
            ],
            ValueOperator::IS_NOT_ITEMTYPE->value               => [
                'type_class' => QuestionTypeUserDevice::class,
                'extra_data' => json_encode((new QuestionTypeUserDevicesConfig(false))->jsonSerialize()),
            ],
            ValueOperator::AT_LEAST_ONE_ITEM_OF_ITEMTYPE->value => [
                'type_class' => QuestionTypeUserDevice::class,
                'extra_data' => json_encode((new QuestionTypeUserDevicesConfig(true))->jsonSerialize()),
            ],
            ValueOperator::ALL_ITEMS_OF_ITEMTYPE->value         => [
                'type_class' => QuestionTypeUserDevice::class,
                'extra_data' => json_encode((new QuestionTypeUserDevicesConfig(true))->jsonSerialize()),
            ],
            ValueOperator::MATCH_REGEX->value                   => QuestionTypeShortText::class,
            ValueOperator::NOT_MATCH_REGEX->value               => QuestionTypeShortText::class,
            ValueOperator::LENGTH_GREATER_THAN->value           => QuestionTypeShortText::class,
            ValueOperator::LENGTH_GREATER_THAN_OR_EQUALS->value => QuestionTypeShortText::class,
            ValueOperator::LENGTH_LESS_THAN->value              => QuestionTypeShortText::class,
            ValueOperator::LENGTH_LESS_THAN_OR_EQUALS->value    => QuestionTypeShortText::class,
            ValueOperator::VISIBLE->value                       => QuestionTypeShortText::class,
            ValueOperator::NOT_VISIBLE->value                   => QuestionTypeShortText::class,
            ValueOperator::EMPTY->value                         => QuestionTypeShortText::class,
            ValueOperator::NOT_EMPTY->value                     => QuestionTypeShortText::class,
        ];

        $form = $this->createItem(Form::class, []);

        foreach ($validOperators as $operator => $question_data) {
            $question = $this->createItem(Question::class, [
                Section::getForeignKeyField() => current($form->getSections())->getID(),
                'type'                        => $question_data['type_class'] ?? $question_data,
                'extra_data'                  => $question_data['extra_data'] ?? null,
            ]);

            $condition = new ConditionData(
                item_uuid: $question->getUuid(),
                item_type: Type::QUESTION->value,
                value_operator: $operator,
                value: 'test_value',
                logic_operator: LogicOperator::AND->value
            );

            $this->assertTrue(
                $condition->isValid(),
                "Condition should be valid with operator: {$operator}"
            );
        }
    }

    public function testIsValidWithAllItemTypes(): void
    {
        $validTypes = [
            Type::QUESTION,
            Type::SECTION,
            Type::COMMENT,
        ];

        $form = $this->createItem(Form::class, []);
        $section = current($form->getSections());
        $question = $this->createItem(Question::class, [
            Section::getForeignKeyField() => $section->getID(),
            'type'                        => QuestionTypeShortText::class,
        ]);
        $comment = $this->createItem(Comment::class, [
            Section::getForeignKeyField() => $section->getID(),
        ]);

        foreach ($validTypes as $type) {
            $item_uuid = match ($type) {
                Type::QUESTION => $question->getUuid(),
                Type::SECTION  => $section->getUuid(),
                Type::COMMENT  => $comment->getUuid(),
                default        => null,
            };

            $condition = new ConditionData(
                item_uuid: $item_uuid,
                item_type: $type->value,
                value_operator: ValueOperator::VISIBLE->value,
                value: 'test_value',
                logic_operator: LogicOperator::AND->value
            );

            $this->assertTrue(
                $condition->isValid(),
                "Condition should be valid with item type: {$type->value}"
            );
        }
    }

    public function testGettersWorkCorrectlyWithValidCondition(): void
    {
        $form = $this->createItem(Form::class, []);
        $question = $this->createItem(Question::class, [
            Section::getForeignKeyField() => current($form->getSections())->getID(),
            'type'                        => QuestionTypeShortText::class,
        ]);

        $condition = new ConditionData(
            item_uuid: $question->getUuid(),
            item_type: Type::QUESTION->value,
            value_operator: ValueOperator::CONTAINS->value,
            value: 'test_value',
            logic_operator: LogicOperator::OR->value
        );

        $this->assertTrue($condition->isValid());
        $this->assertEquals($question->getUuid(), $condition->getItemUuid());
        $this->assertEquals(Type::QUESTION, $condition->getItemType());
        $this->assertEquals(ValueOperator::CONTAINS, $condition->getValueOperator());
        $this->assertEquals('test_value', $condition->getValue());
        $this->assertEquals(LogicOperator::OR, $condition->getLogicOperator());
        $this->assertEquals('question-' . $question->getUuid(), $condition->getItemDropdownKey());
    }

    public function testJsonSerializationIncludesValidation(): void
    {
        $form = $this->createItem(Form::class, []);
        $question = $this->createItem(Question::class, [
            Section::getForeignKeyField() => current($form->getSections())->getID(),
            'type'                        => QuestionTypeShortText::class,
        ]);

        $condition = new ConditionData(
            item_uuid: $question->getUuid(),
            item_type: Type::QUESTION->value,
            value_operator: ValueOperator::EQUALS->value,
            value: 'json_value',
            logic_operator: LogicOperator::AND->value
        );

        $this->assertTrue($condition->isValid());

        $serialized = json_encode($condition);
        $decoded = json_decode($serialized, true);

        $this->assertEquals('question-' . $question->getUuid(), $decoded['item']);
        $this->assertEquals($question->getUuid(), $decoded['item_uuid']);
        $this->assertEquals('question', $decoded['item_type']);
        $this->assertEquals('equals', $decoded['value_operator']);
        $this->assertEquals('json_value', $decoded['value']);
        $this->assertEquals('and', $decoded['logic_operator']);
    }
}
