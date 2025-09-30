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
use Glpi\Form\QuestionType\QuestionTypeActorsExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeAssignee;
use Glpi\Form\QuestionType\QuestionTypeCheckbox;
use Glpi\Form\QuestionType\QuestionTypeDateTime;
use Glpi\Form\QuestionType\QuestionTypeDateTimeExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeDropdown;
use Glpi\Form\QuestionType\QuestionTypeDropdownExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeEmail;
use Glpi\Form\QuestionType\QuestionTypeFile;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\QuestionType\QuestionTypeItemDefaultValueConfig;
use Glpi\Form\QuestionType\QuestionTypeItemDropdown;
use Glpi\Form\QuestionType\QuestionTypeItemDropdownExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeItemExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeLongText;
use Glpi\Form\QuestionType\QuestionTypeNumber;
use Glpi\Form\QuestionType\QuestionTypeObserver;
use Glpi\Form\QuestionType\QuestionTypeRadio;
use Glpi\Form\QuestionType\QuestionTypeRequester;
use Glpi\Form\QuestionType\QuestionTypeRequestType;
use Glpi\Form\QuestionType\QuestionTypeSelectableExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\QuestionType\QuestionTypesManager;
use Glpi\Form\QuestionType\QuestionTypeUrgency;
use Glpi\Form\QuestionType\QuestionTypeUserDevice;
use Glpi\Form\QuestionType\QuestionTypeUserDevicesConfig;
use Location;
use Override;
use Software;
use tests\units\Glpi\Form\Condition\AbstractConditionHandler;

final class EmptyConditionHandlerTest extends AbstractConditionHandler
{
    public static function getConditionHandler(): ConditionHandlerInterface
    {
        return new EmptyConditionHandler(new QuestionTypeShortText(), null);
    }

    #[Override]
    public static function conditionHandlerProvider(): iterable
    {
        $types = [
            // List each types and their configs
            "QuestionTypeItem" => [
                'type'             => QuestionTypeItem::class,
                'extra_data'       => new QuestionTypeItemExtraDataConfig(
                    itemtype: Software::class,
                ),
                'empty_answer'     => (new QuestionTypeItemDefaultValueConfig())->jsonSerialize(),
                'not_empty_answer' => (new QuestionTypeItemDefaultValueConfig(1))->jsonSerialize(),
            ],
            "QuestionTypeLongText" => [
                'type'             => QuestionTypeLongText::class,
                'empty_answer'     => '',
                'not_empty_answer' => '<p>Not empty long text</p>',
            ],
            "QuestionTypeShortText" => [
                'type'             => QuestionTypeShortText::class,
                'empty_answer'     => '',
                'not_empty_answer' => 'Not empty short text',
            ],
            "QuestionTypeEmail" => [
                'type'             => QuestionTypeEmail::class,
                'empty_answer'     => '',
                'not_empty_answer' => 'test@test.test',
            ],
            "QuestionTypeNumber" => [
                'type'             => QuestionTypeNumber::class,
                'empty_answer'     => "",
                'not_empty_answer' => 1,
            ],
            "QuestionTypeRequester (simple)" => [
                'type'             => QuestionTypeRequester::class,
                'extra_data'       => new QuestionTypeActorsExtraDataConfig(
                    is_multiple_actors: false,
                ),
                'empty_answer'     => [],
                'not_empty_answer' => ['users_id-2'],
            ],
            "QuestionTypeRequester (multiple)" => [
                'type'             => QuestionTypeRequester::class,
                'extra_data'       => new QuestionTypeActorsExtraDataConfig(
                    is_multiple_actors: true,
                ),
                'empty_answer'     => [],
                'not_empty_answer' => ['users_id-3', 'users_id-2'],
            ],
            "QuestionTypeObserver (simple)" => [
                'type'             => QuestionTypeObserver::class,
                'extra_data'       => new QuestionTypeActorsExtraDataConfig(
                    is_multiple_actors: false,
                ),
                'empty_answer'     => [],
                'not_empty_answer' => ['users_id-2'],
            ],
            "QuestionTypeObserver (multiple)" => [
                'type'             => QuestionTypeObserver::class,
                'extra_data'       => new QuestionTypeActorsExtraDataConfig(
                    is_multiple_actors: true,
                ),
                'empty_answer'     => [],
                'not_empty_answer' => ['users_id-3', 'users_id-2'],
            ],
            "QuestionTypeAssignee (simple)" => [
                'type'             => QuestionTypeAssignee::class,
                'extra_data'       => new QuestionTypeActorsExtraDataConfig(
                    is_multiple_actors: false,
                ),
                'empty_answer'     => [],
                'not_empty_answer' => ['users_id-2'],
            ],
            "QuestionTypeAssignee (multiple)" => [
                'type'             => QuestionTypeAssignee::class,
                'extra_data'       => new QuestionTypeActorsExtraDataConfig(
                    is_multiple_actors: true,
                ),
                'empty_answer'     => [],
                'not_empty_answer' => ['users_id-3', 'users_id-2'],
            ],
            "QuestionTypeDropdown (simple)" => [
                'type'             => QuestionTypeDropdown::class,
                'extra_data'       => new QuestionTypeDropdownExtraDataConfig(
                    is_multiple_dropdown: false,
                    options: [1 => 'a', 2 => 'b'],
                ),
                'empty_answer'     => [0],
                'not_empty_answer' => [1],
            ],
            "QuestionTypeDropdown (multiple)" => [
                'type'             => QuestionTypeDropdown::class,
                'extra_data'       => new QuestionTypeDropdownExtraDataConfig(
                    is_multiple_dropdown: true,
                    options: [1 => 'a', 2 => 'b'],
                ),
                'empty_answer'     => [0],
                'not_empty_answer' => [1, 2],
            ],
            "QuestionTypeCheckbox" => [
                'type'             => QuestionTypeCheckbox::class,
                'extra_data'       => new QuestionTypeSelectableExtraDataConfig(
                    options: [1 => 'a', 2 => 'b'],
                ),
                'empty_answer'     => [0],
                'not_empty_answer' => [1, 2],
            ],
            "QuestionTypeRadio" => [
                'type'             => QuestionTypeRadio::class,
                'extra_data'       => new QuestionTypeSelectableExtraDataConfig(
                    options: [1 => 'a', 2 => 'b'],
                ),
                'empty_answer'     => [0],
                'not_empty_answer' => [1],
            ],
            "QuestionTypeUserDevice (simple)" => [
                'type'             => QuestionTypeUserDevice::class,
                'extra_data'       => new QuestionTypeUserDevicesConfig(
                    is_multiple_devices: false,
                ),
                'empty_answer'     => "",
                'not_empty_answer' => ["Computer_1"],
            ],
            "QuestionTypeUserDevice (multiple)" => [
                'type'             => QuestionTypeUserDevice::class,
                'extra_data'       => new QuestionTypeUserDevicesConfig(
                    is_multiple_devices: true,
                ),
                'empty_answer'     => "",
                'not_empty_answer' => ["Computer_1", "Computer_2"],
            ],
            "QuestionTypeDateTime (date and time)" => [
                'type'             => QuestionTypeDateTime::class,
                'extra_data'       => new QuestionTypeDateTimeExtraDataConfig(
                    is_date_enabled: true,
                    is_time_enabled: true,
                ),
                'empty_answer'     => "",
                'not_empty_answer' => "2025-08-12T12:24",
            ],
            "QuestionTypeDateTime (date)" => [
                'type'             => QuestionTypeDateTime::class,
                'extra_data'       => new QuestionTypeDateTimeExtraDataConfig(
                    is_date_enabled: true,
                    is_time_enabled: false,
                ),
                'empty_answer'     => "",
                'not_empty_answer' => "2025-08-12",
            ],
            "QuestionTypeDateTime (time)" => [
                'type'             => QuestionTypeDateTime::class,
                'extra_data'       => new QuestionTypeDateTimeExtraDataConfig(
                    is_date_enabled: false,
                    is_time_enabled: true,
                ),
                'empty_answer'     => "",
                'not_empty_answer' => "12:24",
            ],
            "QuestionTypeFile" => [
                'type'             => QuestionTypeFile::class,
                'empty_answer'     => "",
                'not_empty_answer' => "file.txt",
            ],
            "QuestionTypeUrgency" => [
                'type'             => QuestionTypeUrgency::class,
                'empty_answer'     => "0",
                'not_empty_answer' => "1",
            ],
            "QuestionTypeRequestType" => [
                'type'             => QuestionTypeRequestType::class,
                'empty_answer'     => "0",
                'not_empty_answer' => "1",
            ],
            "QuestionTypeItemDropdown" => [
                'type' => QuestionTypeItemDropdown::class,
                'extra_data' => new QuestionTypeItemDropdownExtraDataConfig(
                    itemtype: Location::class,
                ),
                'empty_answer'     => (new QuestionTypeItemDefaultValueConfig())->jsonSerialize(),
                'not_empty_answer' => (new QuestionTypeItemDefaultValueConfig(1))->jsonSerialize(),
            ],
        ];

        foreach ($types as $label => $data) {
            $type             = $data['type'];
            $empty_answer     = $data['empty_answer'];
            $not_empty_answer = $data['not_empty_answer'];
            $extra_data       = $data['extra_data'] ?? null;

            /**
             * Test default empty behavior with questions
             * Empty conditions are more tested in other test methods
             */
            yield "Is empty check for $label" => [
                'question_type'       => $type,
                'condition_operator'  => ValueOperator::EMPTY,
                'condition_value'     => null,
                'submitted_answer'    => $empty_answer,
                'expected_result'     => true,
                'question_extra_data' => $extra_data,
            ];

            yield "Is not empty check for $label" => [
                'question_type'       => $type,
                'condition_operator'  => ValueOperator::NOT_EMPTY,
                'condition_value'     => null,
                'submitted_answer'    => $not_empty_answer,
                'expected_result'     => true,
                'question_extra_data' => $extra_data,
            ];
        }
    }

    public function testAllQuestionTypesAreTested(): void
    {
        // Get a map of all used types in the provider
        $types = iterator_to_array(self::conditionHandlerProvider());
        $types_found = array_map(fn($case) => $case['question_type'], $types);
        $types_map = array_flip($types_found);

        // Get all types defined by the manager
        $possible_types = QuestionTypesManager::getInstance()->getQuestionTypes();
        $possible_types_classes = array_map(fn($type) => $type::class, $possible_types);

        foreach ($possible_types_classes as $type) {
            // Ignore tester plugin types
            if (str_starts_with($type, "GlpiPlugin\Tester")) {
                continue;
            }

            $this->assertArrayHasKey($type, $types_map);
        }
    }
}
