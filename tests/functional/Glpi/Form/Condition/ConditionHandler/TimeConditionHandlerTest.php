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
use Glpi\Form\QuestionType\QuestionTypeDateTime;
use Glpi\Form\QuestionType\QuestionTypeDateTimeExtraDataConfig;
use Override;
use tests\units\Glpi\Form\Condition\AbstractConditionHandler;

final class TimeConditionHandlerTest extends AbstractConditionHandler
{
    public static function getConditionHandler(): ConditionHandlerInterface
    {
        return new TimeConditionHandler();
    }

    #[Override]
    public static function conditionHandlerProvider(): iterable
    {
        $type = QuestionTypeDateTime::class;
        $extra_data = new QuestionTypeDateTimeExtraDataConfig(
            is_date_enabled: false,
            is_time_enabled: true,
        );

        // Test time answers with the EQUALS operator
        yield "Equals check - case 1 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::EQUALS,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:31",
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];
        yield "Equals check - case 2 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::EQUALS,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:29",
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];
        yield "Equals check - case 3 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::EQUALS,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:30",
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];

        // Test time answers with the NOT_EQUALS operator
        yield "Not equals check - case 1 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_EQUALS,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:31",
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];
        yield "Not equals check - case 2 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_EQUALS,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:29",
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];
        yield "Not equals check - case 3 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_EQUALS,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:30",
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];

        // Test time answers with the GREATER_THAN operator
        yield "Greater than check - case 1 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::GREATER_THAN,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:31",
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];
        yield "Greater than check - case 2 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::GREATER_THAN,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:29",
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];
        yield "Greater than check - case 3 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::GREATER_THAN,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:30",
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];

        // Test time answers with the GREATER_THAN_OR_EQUALS operator
        yield "Greater than or equals check - case 1 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:31",
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];
        yield "Greater than or equals check - case 2 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:29",
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];
        yield "Greater than or equals check - case 3 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:30",
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];

        // Test time answers with the LESS_THAN operator
        yield "Less than check - case 1 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::LESS_THAN,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:31",
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];
        yield "Less than check - case 2 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::LESS_THAN,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:29",
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];
        yield "Less than check - case 3 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::LESS_THAN,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:30",
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];

        // Test time answers with the LESS_THAN_OR_EQUALS operator
        yield "Less than or equals check - case 1 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:31",
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];
        yield "Less than or equals check - case 2 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:29",
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];
        yield "Less than or equals check - case 3 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:30",
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];
    }
}
