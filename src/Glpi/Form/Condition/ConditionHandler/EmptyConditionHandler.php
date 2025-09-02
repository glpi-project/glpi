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

use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\Condition\ConditionData;
use Glpi\Form\Condition\ConditionValueTransformerInterface;
use Glpi\Form\Condition\ValueOperator;
use Glpi\Form\QuestionType\QuestionTypeInterface;
use Override;

/**
 * Handler for conditions that check if another form element is empty or not
 */
class EmptyConditionHandler implements ConditionHandlerInterface
{
    public function __construct(
        private QuestionTypeInterface $question_type,
        private ?JsonFieldInterface $question_config
    ) {}

    #[Override]
    public function getSupportedValueOperators(): array
    {
        return [
            ValueOperator::EMPTY,
            ValueOperator::NOT_EMPTY,
        ];
    }

    #[Override]
    public function getTemplate(): null
    {
        // No input field needed for empty conditions
        return null;
    }

    #[Override]
    public function getTemplateParameters(ConditionData $condition): array
    {
        return [];
    }

    #[Override]
    public function applyValueOperator(
        mixed $a,
        ValueOperator $operator,
        mixed $b,
    ): bool {
        if ($this->question_type instanceof ConditionValueTransformerInterface) {
            $a = $this->question_type->transformConditionValueForComparisons($a, $this->question_config);
        }

        return match ($operator) {
            ValueOperator::EMPTY     => empty($a),
            ValueOperator::NOT_EMPTY => !empty($a),

            // Unsupported operators
            default => false,
        };
    }
}
