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
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\QuestionType\QuestionTypeItemDropdown;
use Glpi\Form\QuestionType\QuestionTypeItemDropdownExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeItemExtraDataConfig;
use Override;
use SoftwareCategory;
use tests\units\Glpi\Form\Condition\AbstractConditionHandler;

final class ItemAsTextConditionHandlerTest extends AbstractConditionHandler
{
    public static function getConditionHandler(): ConditionHandlerInterface
    {
        return new ItemAsTextConditionHandler('Computer');
    }

    private static function getDefaultExtraDataConfig(string $question_type): QuestionTypeItemExtraDataConfig
    {
        switch ($question_type) {
            case QuestionTypeItem::class:
                return new QuestionTypeItemExtraDataConfig(
                    itemtype: Computer::class,
                );
            case QuestionTypeItemDropdown::class:
                return new QuestionTypeItemDropdownExtraDataConfig(
                    itemtype: SoftwareCategory::class,
                );
            default:
                throw new \InvalidArgumentException("Unsupported question type: $question_type");
        }
    }

    #[Override]
    public static function conditionHandlerProvider(): iterable
    {
        $extra_data = self::getDefaultExtraDataConfig(QuestionTypeItem::class);
        $type = QuestionTypeItem::class;

        // Test item answers with the CONTAINS operator
        yield "Contains check - case 1 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::CONTAINS,
            'condition_value'     => '_test_pc0',
            'submitted_answer'    => [
                'itemtype' => Computer::class,
                'items_id' => getItemByTypeName(Computer::class, "_test_pc01", true),
            ],
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];
        yield "Contains check - case 2 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::CONTAINS,
            'condition_value'     => '_test_pc02',
            'submitted_answer'    => [
                'itemtype' => Computer::class,
                'items_id' => getItemByTypeName(Computer::class, "_test_pc01", true),
            ],
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];

        // Test item answers with the NOT_CONTAINS operator
        yield "Not contains check - case 1 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_CONTAINS,
            'condition_value'     => '_test_pc0',
            'submitted_answer'    => [
                'itemtype' => Computer::class,
                'items_id' => getItemByTypeName(Computer::class, "_test_pc01", true),
            ],
            'expected_result'     => false,
            'question_extra_data' => $extra_data,
        ];
        yield "Not contains check - case 2 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_CONTAINS,
            'condition_value'     => '_test_pc02',
            'submitted_answer'    => [
                'itemtype' => Computer::class,
                'items_id' => getItemByTypeName(Computer::class, "_test_pc01", true),
            ],
            'expected_result'     => true,
            'question_extra_data' => $extra_data,
        ];
    }
}
