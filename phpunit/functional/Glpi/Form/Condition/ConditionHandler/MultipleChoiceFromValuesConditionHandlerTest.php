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
use Glpi\Form\QuestionType\QuestionTypeCheckbox;
use Glpi\Form\QuestionType\QuestionTypeDropdown;
use Glpi\Form\QuestionType\QuestionTypeDropdownExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeSelectableExtraDataConfig;
use Override;
use tests\units\Glpi\Form\Condition\AbstractConditionHandler;

final class MultipleChoiceFromValuesConditionHandlerTest extends AbstractConditionHandler
{
    public static function getConditionHandler(): ConditionHandlerInterface
    {
        return new MultipleChoiceFromValuesConditionHandler(['opt']);
    }

    #[Override]
    public static function conditionHandlerProvider(): iterable
    {
        $options = [
            "option_a" => "option A",
            "option_b" => "option B",
            "option_c" => "option C",
            "option_d" => "option D",
        ];

        yield from self::getCasesForTypeAndConfig(
            type: QuestionTypeCheckbox::class,
            extra_data: new QuestionTypeSelectableExtraDataConfig(
                options: $options,
            )
        );

        yield from self::getCasesForTypeAndConfig(
            type: QuestionTypeDropdown::class,
            extra_data: new QuestionTypeDropdownExtraDataConfig(
                options: $options,
                is_multiple_dropdown: true,
            )
        );
    }

    private static function getCasesForTypeAndConfig(
        string $type,
        QuestionTypeSelectableExtraDataConfig $extra_data
    ): iterable {

        // Test with the EQUALS operator
        yield "Equals check - case 1 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::EQUALS,
            'condition_value'     => ["option_c"],
            'submitted_answer'    => ["option_b"],
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];
        yield "Equals check - case 2 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::EQUALS,
            'condition_value'     => ["option_c"],
            'submitted_answer'    => ["option_c"],
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];
        yield "Equals check - case 3 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::EQUALS,
            'condition_value'     => ["option_a", "option_c"],
            'submitted_answer'    => ["option_c"],
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];
        yield "Equals check - case 4 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::EQUALS,
            'condition_value'     => ["option_a", "option_c"],
            'submitted_answer'    => ["option_a"],
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];
        yield "Equals check - case 5 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::EQUALS,
            'condition_value'     => ["option_a", "option_c"],
            'submitted_answer'    => ["option_c", "option_a"],
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];
        yield "Equals check - case 6 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::EQUALS,
            'condition_value'     => ["option_a", "option_c"],
            'submitted_answer'    => ["option_a", "option_c"],
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];
        yield "Equals check - case 7 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::EQUALS,
            'condition_value'     => ["option_a", "option_c"],
            'submitted_answer'    => ["option_a", "option_c", "option_b"],
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];

        // Test with the NOT EQUALS operator
        yield "Not equals check - case 1 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_EQUALS,
            'condition_value'     => ["option_c"],
            'submitted_answer'    => ["option_b"],
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];
        yield "Not equals check - case 2 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_EQUALS,
            'condition_value'     => ["option_c"],
            'submitted_answer'    => ["option_c"],
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];
        yield "Not equals check - case 3 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_EQUALS,
            'condition_value'     => ["option_a", "option_c"],
            'submitted_answer'    => ["option_c"],
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];
        yield "Not equals check - case 4 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_EQUALS,
            'condition_value'     => ["option_a", "option_c"],
            'submitted_answer'    => ["option_a"],
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];
        yield "Not equals check - case 5 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_EQUALS,
            'condition_value'     => ["option_a", "option_c"],
            'submitted_answer'    => ["option_c", "option_a"],
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];
        yield "Not equals check - case 6 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_EQUALS,
            'condition_value'     => ["option_a", "option_c"],
            'submitted_answer'    => ["option_a", "option_c"],
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];
        yield "Not equals check - case 7 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_EQUALS,
            'condition_value'     => ["option_a", "option_c"],
            'submitted_answer'    => ["option_a", "option_c", "option_b"],
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];

        // Test with the CONTAINS operator
        yield "Contains check - case 1 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::CONTAINS,
            'condition_value'     => ["option_c"],
            'submitted_answer'    => ["option_b"],
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];
        yield "Contains check - case 2 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::CONTAINS,
            'condition_value'     => ["option_c"],
            'submitted_answer'    => ["option_c"],
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];
        yield "Contains check - case 3 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::CONTAINS,
            'condition_value'     => ["option_a", "option_c"],
            'submitted_answer'    => ["option_c"],
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];
        yield "Contains check - case 4 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::CONTAINS,
            'condition_value'     => ["option_a", "option_c"],
            'submitted_answer'    => ["option_a"],
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];
        yield "Contains check - case 5 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::CONTAINS,
            'condition_value'     => ["option_a", "option_c"],
            'submitted_answer'    => ["option_c", "option_a"],
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];
        yield "Contains check - case 6 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::CONTAINS,
            'condition_value'     => ["option_a", "option_c"],
            'submitted_answer'    => ["option_a", "option_c"],
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];
        yield "Contains check - case 7 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::CONTAINS,
            'condition_value'     => ["option_a"],
            'submitted_answer'    => ["option_c", "option_a"],
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];
        yield "Contains check - case 8 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::CONTAINS,
            'condition_value'     => ["option_a", "option_b"],
            'submitted_answer'    => ["option_c", "option_b", "option_a"],
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];

        // Test with the NOT CONTAINS operator
        yield "Not contains check - case 1 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_CONTAINS,
            'condition_value'     => ["option_c"],
            'submitted_answer'    => ["option_b"],
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];
        yield "Not contains check - case 2 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_CONTAINS,
            'condition_value'     => ["option_c"],
            'submitted_answer'    => ["option_c"],
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];
        yield "Not contains check - case 3 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_CONTAINS,
            'condition_value'     => ["option_a", "option_c"],
            'submitted_answer'    => ["option_c"],
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];
        yield "Not contains check - case 4 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_CONTAINS,
            'condition_value'     => ["option_a", "option_c"],
            'submitted_answer'    => ["option_a"],
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];
        yield "Not contains check - case 5 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_CONTAINS,
            'condition_value'     => ["option_a", "option_c"],
            'submitted_answer'    => ["option_c", "option_a"],
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];
        yield "Not contains check - case 6 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_CONTAINS,
            'condition_value'     => ["option_a", "option_c"],
            'submitted_answer'    => ["option_a", "option_c"],
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];
        yield "Not contains check - case 7 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_CONTAINS,
            'condition_value'     => ["option_a"],
            'submitted_answer'    => ["option_c", "option_a"],
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];
        yield "Not contains check - case 8 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_CONTAINS,
            'condition_value'     => ["option_a", "option_b"],
            'submitted_answer'    => ["option_c", "option_b", "option_a"],
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];
    }
}
