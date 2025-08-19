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
use Glpi\Form\QuestionType\QuestionTypeUserDevice;
use Glpi\Form\QuestionType\QuestionTypeUserDevicesConfig;
use Override;
use tests\units\Glpi\Form\Condition\AbstractConditionHandler;

final class UserDevicesAsTextConditionHandlerTest extends AbstractConditionHandler
{
    public static function getConditionHandler(): ConditionHandlerInterface
    {
        return new UserDevicesAsTextConditionHandler(new QuestionTypeUserDevicesConfig(
            is_multiple_devices: false,
        ));
    }

    #[Override]
    public static function conditionHandlerProvider(): iterable
    {
        $type = QuestionTypeUserDevice::class;

        // Test with single device config
        $single_device_config = new QuestionTypeUserDevicesConfig(
            is_multiple_devices: false,
        );

        // Test with multiple devices config
        $multiple_devices_config = new QuestionTypeUserDevicesConfig(
            is_multiple_devices: true,
        );

        yield from self::getCasesForConfig($type, $single_device_config, 'single device');
        yield from self::getCasesForConfig($type, $multiple_devices_config, 'multiple devices');
    }

    private static function getCasesForConfig(
        string $type,
        QuestionTypeUserDevicesConfig $extra_data,
        string $config_type
    ): iterable {
        $is_multiple = $extra_data->isMultipleDevices();

        // Get real test computer IDs
        $computer_id = getItemByTypeName(\Computer::class, "_test_pc01", true);
        $monitor_id = getItemByTypeName(\Monitor::class, "_test_monitor_1", true);

        // Test user devices with the CONTAINS operator
        yield "Contains check - case 1 for $type ($config_type)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::CONTAINS,
            'condition_value'     => 'test',
            'submitted_answer'    => $is_multiple ? ["Computer_$computer_id"] : "Computer_$computer_id",
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];
        yield "Contains check - case 2 for $type ($config_type)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::CONTAINS,
            'condition_value'     => 'monitor',
            'submitted_answer'    => $is_multiple ? ["Computer_$computer_id"] : "Computer_$computer_id",
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];
        yield "Contains check - case 3 for $type ($config_type)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::CONTAINS,
            'condition_value'     => 'nonexistent',
            'submitted_answer'    => $is_multiple ? ["Computer_$computer_id", "Monitor_$monitor_id"] : "Computer_$computer_id",
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];

        if ($is_multiple) {
            yield "Contains check - case 4 for $type ($config_type)" => [
                'question_type'       => $type,
                'condition_operator'  => ValueOperator::CONTAINS,
                'condition_value'     => 'test',
                'submitted_answer'    => ["Computer_$computer_id", "Monitor_$monitor_id"],
                'expected_result'     => true,
                'question_extra_data' => $extra_data,
            ];
            yield "Contains check - case 5 for $type ($config_type)" => [
                'question_type'       => $type,
                'condition_operator'  => ValueOperator::CONTAINS,
                'condition_value'     => 'phone',
                'submitted_answer'    => ["Computer_$computer_id", "Monitor_$monitor_id"],
                'expected_result'     => false,
                'question_extra_data' => $extra_data,
            ];
        }

        // Test user devices with the NOT_CONTAINS operator
        yield "Not contains check - case 1 for $type ($config_type)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_CONTAINS,
            'condition_value'     => 'monitor',
            'submitted_answer'    => $is_multiple ? ["Computer_$computer_id"] : "Computer_$computer_id",
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];
        yield "Not contains check - case 2 for $type ($config_type)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_CONTAINS,
            'condition_value'     => 'test',
            'submitted_answer'    => $is_multiple ? ["Computer_$computer_id"] : "Computer_$computer_id",
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];
        yield "Not contains check - case 3 for $type ($config_type)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_CONTAINS,
            'condition_value'     => 'nonexistent',
            'submitted_answer'    => $is_multiple ? ["Computer_$computer_id", "Monitor_$monitor_id"] : "Computer_$computer_id",
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];

        if ($is_multiple) {
            yield "Not contains check - case 4 for $type ($config_type)" => [
                'question_type'       => $type,
                'condition_operator'  => ValueOperator::NOT_CONTAINS,
                'condition_value'     => 'test',
                'submitted_answer'    => ["Computer_$computer_id", "Monitor_$monitor_id"],
                'expected_result'     => false,
                'question_extra_data' => $extra_data,
            ];
            yield "Not contains check - case 5 for $type ($config_type)" => [
                'question_type'       => $type,
                'condition_operator'  => ValueOperator::NOT_CONTAINS,
                'condition_value'     => 'phone',
                'submitted_answer'    => ["Computer_$computer_id", "Monitor_$monitor_id"],
                'expected_result'     => true,
                'question_extra_data' => $extra_data,
            ];
        }

        // Test empty answers
        yield "Empty answer test - contains for $type ($config_type)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::CONTAINS,
            'condition_value'     => 'Computer',
            'submitted_answer'    => $is_multiple ? [] : '',
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];
        yield "Empty answer test - not contains for $type ($config_type)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_CONTAINS,
            'condition_value'     => 'Computer',
            'submitted_answer'    => $is_multiple ? [] : '',
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];
    }
}
