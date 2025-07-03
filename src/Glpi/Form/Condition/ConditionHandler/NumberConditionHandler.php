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

class NumberConditionHandler implements ConditionHandlerInterface
{
    #[Override]
    public function getSupportedValueOperators(): array
    {
        return [
            ValueOperator::EQUALS,
            ValueOperator::NOT_EQUALS,
            ValueOperator::GREATER_THAN,
            ValueOperator::GREATER_THAN_OR_EQUALS,
            ValueOperator::LESS_THAN,
            ValueOperator::LESS_THAN_OR_EQUALS,
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
        return [
            'attributes' => [
                'type' => 'number',
                'step' => 'any',
            ],
        ];
    }

    #[Override]
    public function applyValueOperator(
        mixed $a,
        ValueOperator $operator,
        mixed $b,
    ): bool {
        // Normalize values.
        $a = (float) $a;
        $b = (float) $b;

        return match ($operator) {
            ValueOperator::EQUALS                 => $a === $b,
            ValueOperator::NOT_EQUALS             => $a !== $b,
            ValueOperator::GREATER_THAN           => $a > $b,
            ValueOperator::GREATER_THAN_OR_EQUALS => $a >= $b,
            ValueOperator::LESS_THAN              => $a < $b,
            ValueOperator::LESS_THAN_OR_EQUALS    => $a <= $b,

            // Unsupported operators
            default => false,
        };
    }
}
