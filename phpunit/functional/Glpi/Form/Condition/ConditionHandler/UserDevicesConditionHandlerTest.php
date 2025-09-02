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

use Computer;
use Glpi\Form\Condition\ValueOperator;
use Glpi\Form\QuestionType\QuestionTypeUserDevice;
use Glpi\Form\QuestionType\QuestionTypeUserDevicesConfig;
use Override;
use tests\units\Glpi\Form\Condition\AbstractConditionHandler;

final class UserDevicesConditionHandlerTest extends AbstractConditionHandler
{
    public static function getConditionHandler(): array
    {
        return [
            new UserDevicesConditionHandler(is_multiple_devices: false),
            new UserDevicesConditionHandler(is_multiple_devices: true),
        ];
    }

    #[Override]
    public static function conditionHandlerProvider(): iterable
    {
        $type = QuestionTypeUserDevice::class;
        $single_config = new QuestionTypeUserDevicesConfig(false);
        $multiple_config = new QuestionTypeUserDevicesConfig(true);

        // Test user device answers with the IS_ITEMTYPE operator (Computer)
        yield "IS_ITEMTYPE check - case 1 for $type (Computer)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::IS_ITEMTYPE,
            'condition_value'     => Computer::class,
            'submitted_answer'    => "Computer_42",
            'expected_result'     => true,
            'question_extra_data' => $single_config,
        ];
        yield "IS_ITEMTYPE check - case 2 for $type (Computer)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::IS_ITEMTYPE,
            'condition_value'     => Computer::class,
            'submitted_answer'    => "Printer_42",
            'expected_result'     => false,
            'question_extra_data' => $single_config,
        ];
        yield "IS_ITEMTYPE check - case 3 for $type (Computer)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::IS_ITEMTYPE,
            'condition_value'     => Computer::class,
            'submitted_answer'    => "Phone_42",
            'expected_result'     => false,
            'question_extra_data' => $single_config,
        ];

        // Test user device answers with the IS_NOT_ITEMTYPE operator (Computer)
        yield "IS_NOT_ITEMTYPE check - case 1 for $type (Computer)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::IS_NOT_ITEMTYPE,
            'condition_value'     => Computer::class,
            'submitted_answer'    => "Computer_42",
            'expected_result'     => false,
            'question_extra_data' => $single_config,
        ];
        yield "IS_NOT_ITEMTYPE check - case 2 for $type (Computer)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::IS_NOT_ITEMTYPE,
            'condition_value'     => Computer::class,
            'submitted_answer'    => "Printer_42",
            'expected_result'     => true,
            'question_extra_data' => $single_config,
        ];
        yield "IS_NOT_ITEMTYPE check - case 3 for $type (Computer)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::IS_NOT_ITEMTYPE,
            'condition_value'     => Computer::class,
            'submitted_answer'    => "Phone_42",
            'expected_result'     => true,
            'question_extra_data' => $single_config,
        ];

        // Test invalid format cases for single device
        yield "IS_ITEMTYPE check - invalid format case 1 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::IS_ITEMTYPE,
            'condition_value'     => Computer::class,
            'submitted_answer'    => "invalid_value",
            'expected_result'     => false,
            'question_extra_data' => $single_config,
        ];
        yield "IS_ITEMTYPE check - invalid format case 2 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::IS_ITEMTYPE,
            'condition_value'     => Computer::class,
            'submitted_answer'    => "Computer",
            'expected_result'     => false,
            'question_extra_data' => $single_config,
        ];
        yield "IS_ITEMTYPE check - invalid format case 3 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::IS_ITEMTYPE,
            'condition_value'     => Computer::class,
            'submitted_answer'    => "42",
            'expected_result'     => false,
            'question_extra_data' => $single_config,
        ];
        yield "IS_ITEMTYPE check - invalid format case 4 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::IS_ITEMTYPE,
            'condition_value'     => Computer::class,
            'submitted_answer'    => "",
            'expected_result'     => false,
            'question_extra_data' => $single_config,
        ];

        // Test multiple devices with the AT_LEAST_ONE_ITEM_OF_ITEMTYPE operator
        yield "AT_LEAST_ONE_ITEM_OF_ITEMTYPE check - case 1 (contains Computer)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::AT_LEAST_ONE_ITEM_OF_ITEMTYPE,
            'condition_value'     => [Computer::class],
            'submitted_answer'    => ["Computer_42", "Printer_23"],
            'expected_result'     => true,
            'question_extra_data' => $multiple_config,
        ];
        yield "AT_LEAST_ONE_ITEM_OF_ITEMTYPE check - case 2 (no Computer)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::AT_LEAST_ONE_ITEM_OF_ITEMTYPE,
            'condition_value'     => [Computer::class],
            'submitted_answer'    => ["Printer_23", "Phone_42"],
            'expected_result'     => false,
            'question_extra_data' => $multiple_config,
        ];
        yield "AT_LEAST_ONE_ITEM_OF_ITEMTYPE check - case 3 (multiple conditions, some match)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::AT_LEAST_ONE_ITEM_OF_ITEMTYPE,
            'condition_value'     => [Computer::class, "Phone"],
            'submitted_answer'    => ["Printer_23", "Phone_42"],
            'expected_result'     => true,
            'question_extra_data' => $multiple_config,
        ];
        yield "AT_LEAST_ONE_ITEM_OF_ITEMTYPE check - case 4 (empty submission)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::AT_LEAST_ONE_ITEM_OF_ITEMTYPE,
            'condition_value'     => [Computer::class],
            'submitted_answer'    => [],
            'expected_result'     => false,
            'question_extra_data' => $multiple_config,
        ];

        // Test multiple devices with the ALL_ITEMS_OF_ITEMTYPE operator
        yield "ALL_ITEMS_OF_ITEMTYPE check - case 1 (all computers)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::ALL_ITEMS_OF_ITEMTYPE,
            'condition_value'     => [Computer::class],
            'submitted_answer'    => ["Computer_42", "Computer_23"],
            'expected_result'     => true,
            'question_extra_data' => $multiple_config,
        ];
        yield "ALL_ITEMS_OF_ITEMTYPE check - case 2 (mixed types)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::ALL_ITEMS_OF_ITEMTYPE,
            'condition_value'     => [Computer::class],
            'submitted_answer'    => ["Computer_42", "Printer_23"],
            'expected_result'     => false,
            'question_extra_data' => $multiple_config,
        ];
        yield "ALL_ITEMS_OF_ITEMTYPE check - case 3 (multiple allowed types)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::ALL_ITEMS_OF_ITEMTYPE,
            'condition_value'     => [Computer::class, "Printer"],
            'submitted_answer'    => ["Computer_42", "Printer_23"],
            'expected_result'     => true,
            'question_extra_data' => $multiple_config,
        ];
        yield "ALL_ITEMS_OF_ITEMTYPE check - case 4 (empty submission)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::ALL_ITEMS_OF_ITEMTYPE,
            'condition_value'     => [Computer::class],
            'submitted_answer'    => [],
            'expected_result'     => false,
            'question_extra_data' => $multiple_config,
        ];
        yield "ALL_ITEMS_OF_ITEMTYPE check - case 5 (invalid format with valid item)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::ALL_ITEMS_OF_ITEMTYPE,
            'condition_value'     => [Computer::class],
            'submitted_answer'    => ["invalid_format", "Computer_42"],
            'expected_result'     => true,
            'question_extra_data' => $multiple_config,
        ];
        yield "ALL_ITEMS_OF_ITEMTYPE check - case 6 (invalid format)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::ALL_ITEMS_OF_ITEMTYPE,
            'condition_value'     => [Computer::class],
            'submitted_answer'    => ["invalid_format"],
            'expected_result'     => false,
            'question_extra_data' => $multiple_config,
        ];
    }
}
