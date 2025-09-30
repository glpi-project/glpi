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

namespace Glpi\Form\Condition\ConditionHandler;

use Glpi\Form\Condition\ValueOperator;
use Glpi\Form\QuestionType\QuestionTypeNumber;
use Override;
use tests\units\Glpi\Form\Condition\AbstractConditionHandler;

final class NumberConditionHandlerTest extends AbstractConditionHandler
{
    public static function getConditionHandler(): ConditionHandlerInterface
    {
        return new NumberConditionHandler();
    }

    #[Override]
    public static function conditionHandlerProvider(): iterable
    {
        $type = QuestionTypeNumber::class;

        // Test number answers with the EQUALS operator
        yield "Equals check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 9,
            'expected_result'    => false,
        ];
        yield "Equals check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 11,
            'expected_result'    => false,
        ];
        yield "Equals check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 10,
            'expected_result'    => true,
        ];
        yield "Equals check - case 4 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 10.00001,
            'expected_result'    => false,
        ];
        yield "Equals check - case 5 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 10.0,
            'expected_result'    => true,
        ];

        // Test number answers with the NOT EQUALS operator
        yield "Not equals check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 9,
            'expected_result'    => true,
        ];
        yield "Not equals check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 11,
            'expected_result'    => true,
        ];
        yield "Not equals check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 10,
            'expected_result'    => false,
        ];
        yield "Not equals check - case 4 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 10.00001,
            'expected_result'    => true,
        ];
        yield "Not equals check - case 5 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 10.0,
            'expected_result'    => false,
        ];

        // Test number answers with the GREATER_THAN operator
        yield "Greater than check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => 9,
            'expected_result'    => false,
        ];
        yield "Greater than check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => 11,
            'expected_result'    => true,
        ];
        yield "Greater than check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => 10,
            'expected_result'    => false,
        ];
        yield "Greater than check - case 4 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => 10.00001,
            'expected_result'    => true,
        ];
        yield "Greater than check - case 5 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => 9.9999,
            'expected_result'    => false,
        ];
        yield "Greater than check - case 6 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => 10.0,
            'expected_result'    => false,
        ];

        // Test number answers with the GREATER_THAN_OR_EQUALS operator
        yield "Greater than or equals check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 9,
            'expected_result'    => false,
        ];
        yield "Greater than or equals check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 11,
            'expected_result'    => true,
        ];
        yield "Greater than or equals check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 10,
            'expected_result'    => true,
        ];
        yield "Greater than or equals check - case 4 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 10.00001,
            'expected_result'    => true,
        ];
        yield "Greater than or equals check - case 5 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 9.9999,
            'expected_result'    => false,
        ];
        yield "Greater than or equals check - case 6 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 10.0,
            'expected_result'    => true,
        ];

        // Test number answers with the LESS_THAN operator
        yield "Less than check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => 9,
            'expected_result'    => true,
        ];
        yield "Less than check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => 11,
            'expected_result'    => false,
        ];
        yield "Less than check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => 10,
            'expected_result'    => false,
        ];
        yield "Less than check - case 4 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => 10.00001,
            'expected_result'    => false,
        ];
        yield "Less than check - case 5 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => 9.9999,
            'expected_result'    => true,
        ];
        yield "Less than check - case 6 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => 10.0,
            'expected_result'    => false,
        ];

        // Test number answers with the LESS_THAN_OR_EQUALS operator
        yield "Less than or equals check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 9,
            'expected_result'    => true,
        ];
        yield "Less than or equals check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 11,
            'expected_result'    => false,
        ];
        yield "Less than or equals check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 10,
            'expected_result'    => true,
        ];
        yield "Less than or equals check - case 4 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 10.00001,
            'expected_result'    => false,
        ];
        yield "Less than or equals check - case 5 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 9.9999,
            'expected_result'    => true,
        ];
        yield "Less than or equals check - case 6 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 10.0,
            'expected_result'    => true,
        ];
    }
}
