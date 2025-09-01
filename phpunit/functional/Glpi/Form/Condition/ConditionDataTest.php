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
use Glpi\Form\Condition\LogicOperator;
use Glpi\Form\Condition\Type;
use Glpi\Form\Condition\ValueOperator;
use GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class ConditionDataTest extends GLPITestCase
{
    public static function provideValidConditions(): iterable
    {
        yield 'Valid condition with EQUALS operator' => [
            'item_uuid' => '123',
            'item_type' => Type::QUESTION->value,
            'value_operator' => ValueOperator::EQUALS->value,
            'value' => 'test_value',
            'logic_operator' => LogicOperator::AND->value,
            'expected' => true,
        ];

        yield 'Valid condition with VISIBLE operator' => [
            'item_uuid' => '456',
            'item_type' => Type::SECTION->value,
            'value_operator' => ValueOperator::VISIBLE->value,
            'value' => null,
            'logic_operator' => LogicOperator::OR->value,
            'expected' => true,
        ];

        yield 'Valid condition with empty value' => [
            'item_uuid' => '789',
            'item_type' => Type::COMMENT->value,
            'value_operator' => ValueOperator::CONTAINS->value,
            'value' => '',
            'logic_operator' => null,
            'expected' => true,
        ];

        yield 'Valid condition with null value' => [
            'item_uuid' => 'abc',
            'item_type' => Type::QUESTION->value,
            'value_operator' => ValueOperator::GREATER_THAN->value,
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

        yield 'Null item UUID' => [
            'item_uuid' => null,
            'item_type' => Type::QUESTION->value,
            'value_operator' => ValueOperator::EQUALS->value,
            'value' => 'test_value',
            'logic_operator' => LogicOperator::AND->value,
            'expected' => false,
        ];

        yield 'Invalid item type' => [
            'item_uuid' => '123',
            'item_type' => 'invalid_type',
            'value_operator' => ValueOperator::EQUALS->value,
            'value' => 'test_value',
            'logic_operator' => LogicOperator::AND->value,
            'expected' => false,
        ];

        yield 'Null value operator' => [
            'item_uuid' => '123',
            'item_type' => Type::QUESTION->value,
            'value_operator' => null,
            'value' => 'test_value',
            'logic_operator' => LogicOperator::AND->value,
            'expected' => false,
        ];

        yield 'Empty value operator' => [
            'item_uuid' => '123',
            'item_type' => Type::QUESTION->value,
            'value_operator' => '',
            'value' => 'test_value',
            'logic_operator' => LogicOperator::AND->value,
            'expected' => false,
        ];

        yield 'Invalid value operator' => [
            'item_uuid' => '123',
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
        $condition = new ConditionData(
            item_uuid: $item_uuid ?? '',
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
        $condition = new ConditionData(
            item_uuid: $item_uuid ?? '',
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
            ValueOperator::EQUALS,
            ValueOperator::NOT_EQUALS,
            ValueOperator::CONTAINS,
            ValueOperator::NOT_CONTAINS,
            ValueOperator::GREATER_THAN,
            ValueOperator::GREATER_THAN_OR_EQUALS,
            ValueOperator::LESS_THAN,
            ValueOperator::LESS_THAN_OR_EQUALS,
            ValueOperator::VISIBLE,
            ValueOperator::NOT_VISIBLE,
            ValueOperator::EMPTY,
            ValueOperator::NOT_EMPTY,
            ValueOperator::MATCH_REGEX,
            ValueOperator::NOT_MATCH_REGEX,
        ];

        foreach ($validOperators as $operator) {
            $condition = new ConditionData(
                item_uuid: 'test-uuid',
                item_type: Type::QUESTION->value,
                value_operator: $operator->value,
                value: 'test_value',
                logic_operator: LogicOperator::AND->value
            );

            $this->assertTrue(
                $condition->isValid(),
                "Condition should be valid with operator: {$operator->value}"
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

        foreach ($validTypes as $type) {
            $condition = new ConditionData(
                item_uuid: 'test-uuid',
                item_type: $type->value,
                value_operator: ValueOperator::EQUALS->value,
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
        $condition = new ConditionData(
            item_uuid: 'test-uuid-123',
            item_type: Type::QUESTION->value,
            value_operator: ValueOperator::CONTAINS->value,
            value: 'test_value',
            logic_operator: LogicOperator::OR->value
        );

        $this->assertTrue($condition->isValid());
        $this->assertEquals('test-uuid-123', $condition->getItemUuid());
        $this->assertEquals(Type::QUESTION, $condition->getItemType());
        $this->assertEquals(ValueOperator::CONTAINS, $condition->getValueOperator());
        $this->assertEquals('test_value', $condition->getValue());
        $this->assertEquals(LogicOperator::OR, $condition->getLogicOperator());
        $this->assertEquals('question-test-uuid-123', $condition->getItemDropdownKey());
    }

    public function testJsonSerializationIncludesValidation(): void
    {
        $condition = new ConditionData(
            item_uuid: 'json-test-uuid',
            item_type: Type::SECTION->value,
            value_operator: ValueOperator::EQUALS->value,
            value: 'json_value',
            logic_operator: LogicOperator::AND->value
        );

        $this->assertTrue($condition->isValid());

        $serialized = json_encode($condition);
        $decoded = json_decode($serialized, true);

        $this->assertEquals('section-json-test-uuid', $decoded['item']);
        $this->assertEquals('json-test-uuid', $decoded['item_uuid']);
        $this->assertEquals('section', $decoded['item_type']);
        $this->assertEquals('equals', $decoded['value_operator']);
        $this->assertEquals('json_value', $decoded['value']);
        $this->assertEquals('and', $decoded['logic_operator']);
    }
}
