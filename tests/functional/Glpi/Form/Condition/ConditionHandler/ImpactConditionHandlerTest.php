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

use CommonITILObject;
use Glpi\Form\Condition\ConditionData;
use Glpi\Form\Condition\ValueOperator;
use Glpi\Form\QuestionType\QuestionTypeImpact;
use Glpi\ImpactLevel;
use Glpi\Tests\AbstractConditionHandlerTest;
use Override;

final class ImpactConditionHandlerTest extends AbstractConditionHandlerTest
{
    public static function getConditionHandler(): ConditionHandlerInterface
    {
        return new ImpactConditionHandler();
    }

    #[Override]
    public static function conditionHandlerProvider(): iterable
    {
        $type = QuestionTypeImpact::class;

        // Test impact answers with the EQUALS operator
        yield "Equals check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::EQUALS,
            'condition_value'    => ImpactLevel::MEDIUM->value,
            'submitted_answer'   => ImpactLevel::LOW->value,
            'expected_result'    => false,
        ];
        yield "Equals check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::EQUALS,
            'condition_value'    => ImpactLevel::MEDIUM->value,
            'submitted_answer'   => ImpactLevel::HIGH->value,
            'expected_result'    => false,
        ];
        yield "Equals check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::EQUALS,
            'condition_value'    => ImpactLevel::MEDIUM->value,
            'submitted_answer'   => ImpactLevel::MEDIUM->value,
            'expected_result'    => true,
        ];

        // Test impact answers with the NOT EQUALS operator
        yield "Not equals check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_EQUALS,
            'condition_value'    => ImpactLevel::MEDIUM->value,
            'submitted_answer'   => ImpactLevel::LOW->value,
            'expected_result'    => true,
        ];
        yield "Not equals check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_EQUALS,
            'condition_value'    => ImpactLevel::MEDIUM->value,
            'submitted_answer'   => ImpactLevel::HIGH->value,
            'expected_result'    => true,
        ];
        yield "Not equals check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_EQUALS,
            'condition_value'    => ImpactLevel::MEDIUM->value,
            'submitted_answer'   => ImpactLevel::MEDIUM->value,
            'expected_result'    => false,
        ];

        // Test impact answers with the GREATER_THAN operator
        yield "Greater than check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN,
            'condition_value'    => ImpactLevel::MEDIUM->value,
            'submitted_answer'   => ImpactLevel::LOW->value,
            'expected_result'    => false,
        ];
        yield "Greater than check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN,
            'condition_value'    => ImpactLevel::MEDIUM->value,
            'submitted_answer'   => ImpactLevel::HIGH->value,
            'expected_result'    => true,
        ];
        yield "Greater than check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN,
            'condition_value'    => ImpactLevel::MEDIUM->value,
            'submitted_answer'   => ImpactLevel::MEDIUM->value,
            'expected_result'    => false,
        ];

        // Test impact answers with the GREATER_THAN_OR_EQUALS operator
        yield "Greater than or equals check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'    => ImpactLevel::MEDIUM->value,
            'submitted_answer'   => ImpactLevel::LOW->value,
            'expected_result'    => false,
        ];
        yield "Greater than or equals check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'    => ImpactLevel::MEDIUM->value,
            'submitted_answer'   => ImpactLevel::HIGH->value,
            'expected_result'    => true,
        ];
        yield "Greater than or equals check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'    => ImpactLevel::MEDIUM->value,
            'submitted_answer'   => ImpactLevel::MEDIUM->value,
            'expected_result'    => true,
        ];

        // Test impact answers with the LESS_THAN operator
        yield "Less than check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN,
            'condition_value'    => ImpactLevel::MEDIUM->value,
            'submitted_answer'   => ImpactLevel::LOW->value,
            'expected_result'    => true,
        ];
        yield "Less than check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN,
            'condition_value'    => ImpactLevel::MEDIUM->value,
            'submitted_answer'   => ImpactLevel::HIGH->value,
            'expected_result'    => false,
        ];
        yield "Less than check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN,
            'condition_value'    => ImpactLevel::MEDIUM->value,
            'submitted_answer'   => ImpactLevel::MEDIUM->value,
            'expected_result'    => false,
        ];

        // Test impact answers with the LESS_THAN_OR_EQUALS operator
        yield "Less than or equals check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'    => ImpactLevel::MEDIUM->value,
            'submitted_answer'   => ImpactLevel::LOW->value,
            'expected_result'    => true,
        ];
        yield "Less than or equals check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'    => ImpactLevel::MEDIUM->value,
            'submitted_answer'   => ImpactLevel::HIGH->value,
            'expected_result'    => false,
        ];
        yield "Less than or equals check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'    => ImpactLevel::MEDIUM->value,
            'submitted_answer'   => ImpactLevel::MEDIUM->value,
            'expected_result'    => true,
        ];
    }

    public function testGetTemplateParametersFiltersDisabledImpacts(): void
    {
        global $CFG_GLPI;

        $original_mask = $CFG_GLPI['impact_mask'];

        try {
            $CFG_GLPI['impact_mask'] = (1 << 1) | (1 << 2) | (1 << 3);

            $handler = new ImpactConditionHandler();
            $condition = new ConditionData('', '', null, null);
            $params = $handler->getTemplateParameters($condition);

            $this->assertEquals(
                [
                    1 => CommonITILObject::getImpactName(1),
                    2 => CommonITILObject::getImpactName(2),
                    3 => CommonITILObject::getImpactName(3),
                ],
                $params['values']
            );
        } finally {
            $CFG_GLPI['impact_mask'] = $original_mask;
        }
    }
}
