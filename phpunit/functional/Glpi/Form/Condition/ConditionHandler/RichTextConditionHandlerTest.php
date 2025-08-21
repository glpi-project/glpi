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
use Glpi\Form\QuestionType\QuestionTypeLongText;
use Override;
use tests\units\Glpi\Form\Condition\AbstractConditionHandler;

final class RichTextConditionHandlerTest extends AbstractConditionHandler
{
    public static function getConditionHandler(): ConditionHandlerInterface
    {
        return new RichTextConditionHandler();
    }

    #[Override]
    public static function conditionHandlerProvider(): iterable
    {
        $type = QuestionTypeLongText::class;

        // Test rich text answers with the EQUALS operator
        yield "Equals check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::EQUALS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>unexpected answer</p>",
            'expected_result'    => false,
        ];
        yield "Equals check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::EQUALS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>Exact</p>",
            'expected_result'    => false,
        ];
        yield "Equals check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::EQUALS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>answer</p>",
            'expected_result'    => false,
        ];
        yield "Equals check - case 4 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::EQUALS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>Exact answer</p>",
            'expected_result'    => true,
        ];
        yield "Equals check - case 5 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::EQUALS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>exact ANSWER</p>",
            'expected_result'    => true,
        ];

        // Test rich text answers with the NOT_EQUALS operator
        yield "Not equals check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_EQUALS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>unexpected answer</p>",
            'expected_result'    => true,
        ];
        yield "Not equals check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_EQUALS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>Exact</p>",
            'expected_result'    => true,
        ];
        yield "Not equals check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_EQUALS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>answer</p>",
            'expected_result'    => true,
        ];
        yield "Not equals check - case 4 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_EQUALS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>Exact answer</p>",
            'expected_result'    => false,
        ];
        yield "Not equals check - case 5 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_EQUALS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>exact ANSWER</p>",
            'expected_result'    => false,
        ];

        // Test rich text answers with the CONTAINS operator
        yield "Contains check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::CONTAINS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>unexpected answer</p>",
            'expected_result'    => false,
        ];
        yield "Contains check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::CONTAINS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>Exact</p>",
            'expected_result'    => true,
        ];
        yield "Contains check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::CONTAINS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>answer</p>",
            'expected_result'    => true,
        ];
        yield "Contains check - case 4 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::CONTAINS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>Exact answer</p>",
            'expected_result'    => true,
        ];
        yield "Contains check - case 5 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::CONTAINS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>exact ANSWER</p>",
            'expected_result'    => true,
        ];

        // Test rich text answers with the NOT_CONTAINS operator
        yield "Not contains check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_CONTAINS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>unexpected answer</p>",
            'expected_result'    => true,
        ];
        yield "Not contains check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_CONTAINS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>Exact</p>",
            'expected_result'    => false,
        ];
        yield "Not contains check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_CONTAINS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>answer</p>",
            'expected_result'    => false,
        ];
        yield "Not contains check - case 4 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_CONTAINS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>Exact answer</p>",
            'expected_result'    => false,
        ];
        yield "Not contains check - case 5 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_CONTAINS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>exact ANSWER</p>",
            'expected_result'    => false,
        ];

        // Test string answers with the LENGTH_GREATER_THAN operator
        yield "Length greater than check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LENGTH_GREATER_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => "<p>short</p>",
            'expected_result'    => false,
        ];
        yield "Length greater than check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LENGTH_GREATER_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => "<p>longer than ten characters</p>",
            'expected_result'    => true,
        ];
        yield "Length greater than check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LENGTH_GREATER_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => "<p>exactlyten</p>",
            'expected_result'    => false,
        ];

        // Test string answers with the LENGTH_GREATER_THAN_OR_EQUALS operator
        yield "Length greater than or equals check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LENGTH_GREATER_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => "<p>short</p>",
            'expected_result'    => false,
        ];
        yield "Length greater than or equals check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LENGTH_GREATER_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => "<p>longer than ten characters</p>",
            'expected_result'    => true,
        ];
        yield "Length greater than or equals check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LENGTH_GREATER_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => "<p>exactly ten</p>",
            'expected_result'    => true,
        ];
        yield "Length greater than or equals check - case 4 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LENGTH_GREATER_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => "<p>nine</p>",
            'expected_result'    => false,
        ];

        // Test string answers with the LENGTH_LESS_THAN operator
        yield "Length less than check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LENGTH_LESS_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => "<p>longer than ten characters</p>",
            'expected_result'    => false,
        ];
        yield "Length less than check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LENGTH_LESS_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => "<p>short</p>",
            'expected_result'    => true,
        ];
        yield "Length less than check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LENGTH_LESS_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => "<p>exactly ten</p>",
            'expected_result'    => false,
        ];

        // Test string answers with the LENGTH_LESS_THAN_OR_EQUALS operator
        yield "Length less than or equals check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LENGTH_LESS_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => "<p>longer than ten characters</p>",
            'expected_result'    => false,
        ];
        yield "Length less than or equals check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LENGTH_LESS_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => "<p>short</p>",
            'expected_result'    => true,
        ];
        yield "Length less than or equals check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LENGTH_LESS_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => "<p>exactlyten</p>",
            'expected_result'    => true,
        ];
        yield "Length less than or equals check - case 4 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LENGTH_LESS_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => "<p>nine</p>",
            'expected_result'    => true,
        ];
    }
}
