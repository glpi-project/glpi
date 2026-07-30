<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\QuestionType\QuestionTypeItemDropdown;
use Glpi\Form\QuestionType\QuestionTypeItemDropdownExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeItemExtraDataConfig;
use Glpi\Tests\AbstractConditionHandlerTest;
use Override;
use SoftwareCategory;

final class ItemConditionHandlerTest extends AbstractConditionHandlerTest
{
    public static function getConditionHandler(): ConditionHandlerInterface
    {
        return new ItemConditionHandler('Computer', true);
    }

    private static function getDefaultExtraDataConfig(string $question_type, bool $is_multiple_items): QuestionTypeItemExtraDataConfig
    {
        switch ($question_type) {
            case QuestionTypeItem::class:
                return new QuestionTypeItemExtraDataConfig(
                    itemtype: Computer::class,
                    is_multiple_items: $is_multiple_items,
                );
            case QuestionTypeItemDropdown::class:
                return new QuestionTypeItemDropdownExtraDataConfig(
                    itemtype: SoftwareCategory::class,
                    is_multiple_items: $is_multiple_items,
                );
            default:
                throw new \InvalidArgumentException("Unsupported question type: $question_type");
        }
    }

    #[Override]
    public static function conditionHandlerProvider(): iterable
    {
        $types = [
            QuestionTypeItem::class         => Computer::class,
            QuestionTypeItemDropdown::class => SoftwareCategory::class,
        ];

        foreach ($types as $type => $itemtype) {
            foreach ([true, false] as $is_multiple_items) {
                $extra_data = self::getDefaultExtraDataConfig($type, $is_multiple_items);
                $multiple_label = $is_multiple_items ? 'multiple' : 'single';

                // Test item answers with the EQUALS operator
                yield "Equals check - case 1 for $type ($multiple_label, same items)" => [
                    'question_type'       => $type,
                    'condition_operator'  => ValueOperator::EQUALS,
                    'condition_value'     => ['itemtype' => $itemtype, 'items_ids' => [1]],
                    'submitted_answer'    => ['itemtype' => $itemtype, 'items_ids' => [1]],
                    'expected_result'     => true,
                    'question_extra_data' => $extra_data,
                ];
                yield "Equals check - case 2 for $type ($multiple_label, different items_ids)" => [
                    'question_type'       => $type,
                    'condition_operator'  => ValueOperator::EQUALS,
                    'condition_value'     => ['itemtype' => $itemtype, 'items_ids' => [1]],
                    'submitted_answer'    => ['itemtype' => $itemtype, 'items_ids' => [2]],
                    'expected_result'     => false,
                    'question_extra_data' => $extra_data,
                ];

                // Test item answers with the NOT_EQUALS operator
                yield "Not equals check - case 1 for $type ($multiple_label, same items)" => [
                    'question_type'       => $type,
                    'condition_operator'  => ValueOperator::NOT_EQUALS,
                    'condition_value'     => ['itemtype' => $itemtype, 'items_ids' => [1]],
                    'submitted_answer'    => ['itemtype' => $itemtype, 'items_ids' => [1]],
                    'expected_result'     => false,
                    'question_extra_data' => $extra_data,
                ];
                yield "Not equals check - case 2 for $type ($multiple_label, different items_ids)" => [
                    'question_type'       => $type,
                    'condition_operator'  => ValueOperator::NOT_EQUALS,
                    'condition_value'     => ['itemtype' => $itemtype, 'items_ids' => [1]],
                    'submitted_answer'    => ['itemtype' => $itemtype, 'items_ids' => [2]],
                    'expected_result'     => true,
                    'question_extra_data' => $extra_data,
                ];

                // The CONTAINS and NOT_CONTAINS operators are only relevant for questions with multiple items selection, so we only test them in this case.
                if ($is_multiple_items) {
                    // Test item answers with the CONTAINS operator
                    yield "Contains check - case 1 for $type (multiple, condition items_ids is subset of submitted items_ids)" => [
                        'question_type'       => $type,
                        'condition_operator'  => ValueOperator::CONTAINS,
                        'condition_value'     => ['itemtype' => $itemtype, 'items_ids' => [1, 2]],
                        'submitted_answer'    => ['itemtype' => $itemtype, 'items_ids' => [1, 2, 3]],
                        'expected_result'     => true,
                        'question_extra_data' => $extra_data,
                    ];
                    yield "Contains check - case 2 for $type (multiple, condition items_ids is not subset of submitted items_ids)" => [
                        'question_type'       => $type,
                        'condition_operator'  => ValueOperator::CONTAINS,
                        'condition_value'     => ['itemtype' => $itemtype, 'items_ids' => [1, 2]],
                        'submitted_answer'    => ['itemtype' => $itemtype, 'items_ids' => [2, 3]],
                        'expected_result'     => false,
                        'question_extra_data' => $extra_data,
                    ];

                    // Test item answers with the NOT_CONTAINS operator
                    yield "Not contains check - case 1 for $type (multiple, condition items_ids is subset of submitted items_ids)" => [
                        'question_type'       => $type,
                        'condition_operator'  => ValueOperator::NOT_CONTAINS,
                        'condition_value'     => ['itemtype' => $itemtype, 'items_ids' => [1, 2]],
                        'submitted_answer'    => ['itemtype' => $itemtype, 'items_ids' => [1, 2, 3]],
                        'expected_result'     => false,
                        'question_extra_data' => $extra_data,
                    ];
                    yield "Not contains check - case 2 for $type (multiple, condition items_ids is not subset of submitted items_ids)" => [
                        'question_type'       => $type,
                        'condition_operator'  => ValueOperator::NOT_CONTAINS,
                        'condition_value'     => ['itemtype' => $itemtype, 'items_ids' => [1, 2]],
                        'submitted_answer'    => ['itemtype' => $itemtype, 'items_ids' => [2, 3]],
                        'expected_result'     => true,
                        'question_extra_data' => $extra_data,
                    ];
                }
            }
        }
    }
}
