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
use Glpi\Form\QuestionType\QuestionTypeEmail;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Override;
use tests\units\Glpi\Form\Condition\AbstractConditionHandler;

final class StringConditionHandlerTest extends AbstractConditionHandler
{
    #[Override]
    public static function conditionHandlerProvider(): iterable
    {
        $types = [
            QuestionTypeShortText::class,
            QuestionTypeEmail::class,
        ];

        foreach ($types as $type) {
            // Test string answers with the EQUALS operator
            yield "Equals check - case 1 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::EQUALS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "unexpected answer",
                'expected_result'    => false,
            ];
            yield "Equals check - case 2 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::EQUALS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "Exact",
                'expected_result'    => false,
            ];
            yield "Equals check - case 3 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::EQUALS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "answer",
                'expected_result'    => false,
            ];
            yield "Equals check - case 4 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::EQUALS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "Exact answer",
                'expected_result'    => true,
            ];
            yield "Equals check - case 5 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::EQUALS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "exact ANSWER",
                'expected_result'    => true,
            ];

            // Test string answers with the NOT_EQUALS operator
            yield "Not equals check - case 1 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::NOT_EQUALS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "unexpected answer",
                'expected_result'    => true,
            ];
            yield "Not equals check - case 2 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::NOT_EQUALS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "Exact",
                'expected_result'    => true,
            ];
            yield "Not equals check - case 3 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::NOT_EQUALS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "answer",
                'expected_result'    => true,
            ];
            yield "Not equals check - case 4 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::NOT_EQUALS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "Exact answer",
                'expected_result'    => false,
            ];
            yield "Not equals check - case 5 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::NOT_EQUALS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "exact ANSWER",
                'expected_result'    => false,
            ];

            // Test string answers with the CONTAINS operator
            yield "Contains check - case 1 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::CONTAINS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "unexpected answer",
                'expected_result'    => false,
            ];
            yield "Contains check - case 2 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::CONTAINS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "Exact",
                'expected_result'    => true,
            ];
            yield "Contains check - case 3 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::CONTAINS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "answer",
                'expected_result'    => true,
            ];
            yield "Contains check - case 4 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::CONTAINS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "Exact answer",
                'expected_result'    => true,
            ];
            yield "Contains check - case 5 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::CONTAINS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "exact ANSWER",
                'expected_result'    => true,
            ];

            // Test string answers with the NOT_CONTAINS operator
            yield "Not contains check - case 1 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::NOT_CONTAINS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "unexpected answer",
                'expected_result'    => true,
            ];
            yield "Not contains check - case 2 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::NOT_CONTAINS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "Exact",
                'expected_result'    => false,
            ];
            yield "Not contains check - case 3 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::NOT_CONTAINS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "answer",
                'expected_result'    => false,
            ];
            yield "Not contains check - case 4 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::NOT_CONTAINS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "Exact answer",
                'expected_result'    => false,
            ];
            yield "Not contains check - case 5 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::NOT_CONTAINS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "exact ANSWER",
                'expected_result'    => false,
            ];

            // Test string answers with the MATCH_REGEX operator
            yield "Match regex check - case 1 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::MATCH_REGEX,
                'condition_value'    => "/[09]+/",
                'submitted_answer'   => "Invalid format",
                'expected_result'    => false,
            ];
            yield "Match regex check - case 2 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::MATCH_REGEX,
                'condition_value'    => "/[09]+/",
                'submitted_answer'   => "0124511",
                'expected_result'    => true,
            ];
            yield "Match regex check - case 3 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::MATCH_REGEX,
                'condition_value'    => "not a regex", // Invalid regex should not trigger errors
                'submitted_answer'   => "answer",
                'expected_result'    => false,
            ];

            // Test string answers with the NOT_MATCH_REGEX operator
            yield "Not match regex check - case 1 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::NOT_MATCH_REGEX,
                'condition_value'    => "/[09]+/",
                'submitted_answer'   => "Invalid format",
                'expected_result'    => true,
            ];
            yield "Not match regex check - case 2 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::NOT_MATCH_REGEX,
                'condition_value'    => "/[09]+/",
                'submitted_answer'   => "0124511",
                'expected_result'    => false,
            ];
            yield "Not match regex check - case 3 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::NOT_MATCH_REGEX,
                'condition_value'    => "not a regex", // Invalid regex should not trigger errors
                'submitted_answer'   => "answer",
                'expected_result'    => true,
            ];
        }
    }
}
