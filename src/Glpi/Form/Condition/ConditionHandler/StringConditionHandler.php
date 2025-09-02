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

use Glpi\Form\Condition\ConditionData;
use Glpi\Form\Condition\ValueOperator;
use Override;

class StringConditionHandler implements ConditionHandlerInterface
{
    #[Override]
    public function getSupportedValueOperators(): array
    {
        return [
            ValueOperator::EQUALS,
            ValueOperator::NOT_EQUALS,
            ValueOperator::CONTAINS,
            ValueOperator::NOT_CONTAINS,
            ValueOperator::LENGTH_GREATER_THAN,
            ValueOperator::LENGTH_GREATER_THAN_OR_EQUALS,
            ValueOperator::LENGTH_LESS_THAN,
            ValueOperator::LENGTH_LESS_THAN_OR_EQUALS,
        ];
    }

    #[Override]
    public function getTemplate(): string
    {
        return '/pages/admin/form/condition_handler_templates/input.html.twig';
    }

    #[Override]
    public function getTemplateParameters(ConditionData $condition): array
    {
        switch ($condition->getValueOperator()) {
            case ValueOperator::LENGTH_GREATER_THAN:
            case ValueOperator::LENGTH_GREATER_THAN_OR_EQUALS:
            case ValueOperator::LENGTH_LESS_THAN:
            case ValueOperator::LENGTH_LESS_THAN_OR_EQUALS:
                // For length operators, we want to display a number input.
                return [
                    'attributes' => [
                        'type' => 'number',
                        'step' => 'any',
                    ],
                ];
            default:
                return [];
        }
    }

    #[Override]
    public function applyValueOperator(
        mixed $a,
        ValueOperator $operator,
        mixed $b,
    ): bool {
        // Normalize strings.
        $a = strtolower(strval($a));
        $b = strtolower(strval($b));

        return match ($operator) {
            ValueOperator::EQUALS          => $a === $b,
            ValueOperator::NOT_EQUALS      => $a !== $b,
            ValueOperator::CONTAINS        => str_contains($b, $a),
            ValueOperator::NOT_CONTAINS    => !str_contains($b, $a),

            // Length comparison operators
            ValueOperator::LENGTH_GREATER_THAN           => strlen($a) > intval($b),
            ValueOperator::LENGTH_GREATER_THAN_OR_EQUALS => strlen($a) >= intval($b),
            ValueOperator::LENGTH_LESS_THAN              => strlen($a) < intval($b),
            ValueOperator::LENGTH_LESS_THAN_OR_EQUALS    => strlen($a) <= intval($b),

            // Unsupported operators
            default => false,
        };
    }
}
