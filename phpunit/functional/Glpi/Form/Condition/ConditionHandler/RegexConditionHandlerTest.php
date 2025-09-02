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
use Glpi\DBAL\JsonFieldInterface;
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
use Glpi\Form\QuestionType\QuestionTypeUrgency;
use Glpi\Form\QuestionType\QuestionTypeUserDevice;
use Glpi\Form\QuestionType\QuestionTypeUserDevicesConfig;
use Location;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use tests\units\Glpi\Form\Condition\AbstractConditionHandler;

final class RegexConditionHandlerTest extends AbstractConditionHandler
{
    public static function getConditionHandler(): ConditionHandlerInterface
    {
        return new RegexConditionHandler(new QuestionTypeShortText(), null);
    }

    #[Override]
    public static function conditionHandlerProvider(): iterable
    {
        $types = [
            QuestionTypeShortText::class => [[
                'regex'          => '/^test$/',
                'valid_answer'   => 'test',
                'invalid_answer' => 'not_test',
            ]],
            QuestionTypeNumber::class => [[
                'regex'          => '/^[0-9]+$/',
                'valid_answer'   => '123',
                'invalid_answer' => 'not_a_number',
            ]],
            QuestionTypeEmail::class => [[
                'regex'         => '/^[\w\.-]+@[\w\.-]+\.[a-zA-Z]{2,}$/',
                'valid_answer'  => 'test@example.com',
                'invalid_answer' => 'not_an_email',
            ]],
            QuestionTypeLongText::class => [[
                'regex'          => '/^test$/',
                'valid_answer'   => '<p>test</p>',
                'invalid_answer' => '<p>not_test</p>',
            ]],
            QuestionTypeDateTime::class => [
                [
                    'regex'               => '/^2024-02/',
                    'valid_answer'        => '2024-02-29',
                    'invalid_answer'      => '2024-03-28',
                    'question_extra_data' => new QuestionTypeDateTimeExtraDataConfig(
                        is_date_enabled: true,
                        is_time_enabled: false,
                    ),
                ],
                [
                    'regex'               => '/^14:/',
                    'valid_answer'        => '14:00',
                    'invalid_answer'      => '13:59',
                    'question_extra_data' => new QuestionTypeDateTimeExtraDataConfig(
                        is_date_enabled: false,
                        is_time_enabled: true,
                    ),
                ],
                [
                    'regex'               => '/^2024-02/',
                    'valid_answer'        => '2024-02-28 15:30:00',
                    'invalid_answer'      => '2024-03-28 15:29:59',
                    'question_extra_data' => new QuestionTypeDateTimeExtraDataConfig(
                        is_date_enabled: true,
                        is_time_enabled: true,
                    ),
                ],
            ],
            QuestionTypeUrgency::class => [[
                'regex'          => '/^3$/',
                'valid_answer'   => 3,
                'invalid_answer' => 1,
            ]],
            QuestionTypeRequestType::class => [[
                'regex'          => '/^1$/',
                'valid_answer'   => 1,
                'invalid_answer' => 2,
            ]],
            QuestionTypeFile::class => [[
                'regex'          => '/^file_[0-9]+\.txt$/',
                'valid_answer'   => ['file_1.txt'],
                'invalid_answer' => ['file_2.doc'],
            ]],
            QuestionTypeRadio::class => [[
                'regex'               => '/^Radio 1$/',
                'valid_answer'        => 'radio_1',
                'invalid_answer'      => 'radio_2',
                'question_extra_data' => new QuestionTypeSelectableExtraDataConfig([
                    'radio_1' => 'Radio 1',
                    'radio_2' => 'Radio 2',
                ]),
            ]],
            QuestionTypeCheckbox::class => [[
                'regex'              => '/^Checkbox 1|Checkbox 2$/',
                'valid_answer'        => ['checkbox_1', 'checkbox_2'],
                'invalid_answer'      => ['checkbox_3', 'checkbox_4'],
                'question_extra_data' => new QuestionTypeSelectableExtraDataConfig([
                    'checkbox_1' => 'Checkbox 1',
                    'checkbox_2' => 'Checkbox 2',
                    'checkbox_3' => 'Checkbox 3',
                    'checkbox_4' => 'Checkbox 4',
                ]),
            ]],
            QuestionTypeDropdown::class => [
                [
                    'regex'               => '/^Dropdown 1$/',
                    'valid_answer'        => ['dropdown_1'],
                    'invalid_answer'      => ['dropdown_2'],
                    'question_extra_data' => new QuestionTypeDropdownExtraDataConfig([
                        'dropdown_1' => 'Dropdown 1',
                        'dropdown_2' => 'Dropdown 2',
                    ], false),
                ],
                [
                    'regex'               => '/^Dropdown 1|Dropdown 2$/',
                    'valid_answer'        => ['dropdown_1', 'dropdown_2'],
                    'invalid_answer'      => ['dropdown_3', 'dropdown_4'],
                    'question_extra_data' => new QuestionTypeDropdownExtraDataConfig([
                        'dropdown_1' => 'Dropdown 1',
                        'dropdown_2' => 'Dropdown 2',
                        'dropdown_3' => 'Dropdown 3',
                        'dropdown_4' => 'Dropdown 4',
                    ], true),
                ],
            ],
            QuestionTypeItem::class => [
                [
                    'regex' => '/^_test_pc01+$/',
                    'valid_answer' => fn() => [
                        'itemtype' => Computer::class,
                        'items_id' => getItemByTypeName(Computer::class, "_test_pc01", true),
                    ],
                    'invalid_answer' => fn() => [
                        'itemtype' => Computer::class,
                        'items_id' => getItemByTypeName(Computer::class, "_test_pc02", true),
                    ],
                    'question_extra_data' => new QuestionTypeItemExtraDataConfig(Computer::class),
                ],
            ],
            QuestionTypeItemDropdown::class => [
                [
                    'regex'        => '/^_location01$/',
                    'valid_answer' => fn() => [
                        'itemtype' => Location::class,
                        'items_id' => getItemByTypeName(Location::class, "_location01", true),
                    ],
                    'invalid_answer' => fn() => [
                        'itemtype' => Location::class,
                        'items_id' => getItemByTypeName(Location::class, "_location02", true),
                    ],
                    'question_extra_data' => new QuestionTypeItemDropdownExtraDataConfig(Location::class),
                ],
            ],
            QuestionTypeUserDevice::class => [
                [
                    'regex'          => '/^_test_pc01$/',
                    'valid_answer'   => fn() => Computer::getType() . '_' . getItemByTypeName(Computer::class, "_test_pc01", true),
                    'invalid_answer' => fn() => Computer::getType() . '_' . getItemByTypeName(Computer::class, "_test_pc02", true),
                    'question_extra_data' => new QuestionTypeUserDevicesConfig(false),
                ],
                [
                    'regex'          => '/^_test_pc01$/',
                    'valid_answer'   => fn() => [Computer::getType() . '_' . getItemByTypeName(Computer::class, "_test_pc01", true)],
                    'invalid_answer' => fn() => [Computer::getType() . '_' . getItemByTypeName(Computer::class, "_test_pc02", true)],
                    'question_extra_data' => new QuestionTypeUserDevicesConfig(true),
                ],
            ],
        ];

        $types = array_merge($types, self::actorsConditionHandlerProvider());

        foreach ($types as $type => $datas) {
            foreach ($datas as $key => $data) {
                foreach ([
                    ValueOperator::MATCH_REGEX,
                    ValueOperator::NOT_MATCH_REGEX,
                ] as $operator) {
                    yield $operator->getLabel() . " for $type - regex matches #$key" => [
                        'question_type'       => $type,
                        'condition_operator'  => $operator,
                        'condition_value'     => $data['regex'],
                        'submitted_answer'    => $data['valid_answer'],
                        'expected_result'     => ValueOperator::MATCH_REGEX === $operator,
                        'question_extra_data' => $data['question_extra_data'] ?? null,
                    ];
                    yield $operator->getLabel() . " for $type - regex does not match #$key" => [
                        'question_type'       => $type,
                        'condition_operator'  => $operator,
                        'condition_value'     => $data['regex'],
                        'submitted_answer'    => $data['invalid_answer'],
                        'expected_result'     => ValueOperator::NOT_MATCH_REGEX === $operator,
                        'question_extra_data' => $data['question_extra_data'] ?? null,
                    ];
                    yield $operator->getLabel() . " for $type - empty answer #$key" => [
                        'question_type'       => $type,
                        'condition_operator'  => $operator,
                        'condition_value'     => $data['regex'],
                        'submitted_answer'    => is_array($data['valid_answer']) ? [] : '',
                        'expected_result'     => ValueOperator::NOT_MATCH_REGEX === $operator,
                        'question_extra_data' => $data['question_extra_data'] ?? null,
                    ];
                    yield $operator->getLabel() . " for $type - invalid regex #$key" => [
                        'question_type'       => $type,
                        'condition_operator'  => $operator,
                        'condition_value'     => '/invalid_regex',
                        'submitted_answer'    => $data['valid_answer'],
                        'expected_result'     => ValueOperator::NOT_MATCH_REGEX === $operator,
                        'question_extra_data' => $data['question_extra_data'] ?? null,
                    ];
                }
            }
        }
    }

    private static function actorsConditionHandlerProvider(): array
    {
        /** @var class-string<AbstractQuestionTypeActors>[] $types */
        $types = [
            QuestionTypeRequester::class,
            QuestionTypeAssignee::class,
            QuestionTypeObserver::class,
        ];

        $datas = [];
        foreach ($types as $type) {
            $type_name           = (new $type())->getName();
            $allowed_actor_types = (new $type())->getAllowedActorTypes();
            foreach ($allowed_actor_types as $actor_type) {
                $first_actor_name = sprintf('%s-%s-1', $type_name, getForeignKeyFieldForItemType($actor_type));
                $second_actor_name = sprintf('%s-%s-2', $type_name, getForeignKeyFieldForItemType($actor_type));
                $datas[$type][] = [
                    'regex'          => '/^' . $first_actor_name . '$/',
                    'valid_answer'   => fn() => [
                        sprintf('%s-%s', getForeignKeyFieldForItemType($actor_type), getItemByTypeName($actor_type, $first_actor_name, true)),
                    ],
                    'invalid_answer' => fn() => [
                        sprintf('%s-%s', getForeignKeyFieldForItemType($actor_type), getItemByTypeName($actor_type, $second_actor_name, true)),
                    ],
                    'question_extra_data' => new QuestionTypeActorsExtraDataConfig(
                        is_multiple_actors: true
                    ),
                ];
            }
        }

        return $datas;
    }

    #[DataProvider('conditionHandlerProvider')]
    public function testConditionHandlerProvider(
        string $question_type,
        ValueOperator $condition_operator,
        mixed $condition_value,
        mixed $submitted_answer,
        bool $expected_result,
        ?JsonFieldInterface $question_extra_data = null,
    ): void {
        /** @var class-string<AbstractQuestionTypeActors>[] $types */
        $types = [
            QuestionTypeRequester::class,
            QuestionTypeObserver::class,
            QuestionTypeAssignee::class,
        ];

        foreach ($types as $type) {
            $allowed_actor_types = (new $type())->getAllowedActorTypes();
            foreach ($allowed_actor_types as $actor_type) {
                $this->createItem($actor_type, [
                    'entities_id' => $this->getTestRootEntity(true),
                    'name'        => sprintf(
                        '%s-%s-1',
                        (new $type())->getName(),
                        getForeignKeyFieldForItemType($actor_type)
                    ),
                ]);
                $this->createItem($actor_type, [
                    'entities_id' => $this->getTestRootEntity(true),
                    'name'        => sprintf(
                        '%s-%s-2',
                        (new $type())->getName(),
                        getForeignKeyFieldForItemType($actor_type)
                    ),
                ]);
            }
        }

        // Submited answers must be a callable
        if (is_callable($submitted_answer)) {
            $submitted_answer = $submitted_answer();
        }

        parent::testConditionHandlerProvider(
            question_type: $question_type,
            condition_operator: $condition_operator,
            condition_value: $condition_value,
            submitted_answer: $submitted_answer,
            expected_result: $expected_result,
            question_extra_data: $question_extra_data,
        );
    }
}
