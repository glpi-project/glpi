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
use Glpi\Form\QuestionType\QuestionTypeUserDevice;
use Glpi\Form\QuestionType\QuestionTypeUserDevicesConfig;
use Override;

/**
 * Allow text comparison on items using contains operator.
 */
final class UserDevicesAsTextConditionHandler implements ConditionHandlerInterface
{
    public function __construct(
        private QuestionTypeUserDevicesConfig $question_config
    ) {}

    #[Override]
    public function getSupportedValueOperators(): array
    {
        return [
            ValueOperator::CONTAINS,
            ValueOperator::NOT_CONTAINS,
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
        return [];
    }

    #[Override]
    public function applyValueOperator(
        mixed $a,
        ValueOperator $operator,
        mixed $b,
    ): bool {
        if (!$this->question_config->isMultipleDevices() && is_string($a)) {
            $a = [$a];
        }

        if (!is_array($a)) {
            return false;
        }

        // Get valid item names from the raw answer
        $a = (new QuestionTypeUserDevice())->transformConditionValueForComparisons($a, $this->question_config);

        // Normalize values
        $a = array_map(fn(string $item) => strtolower(strval($item)), $a);
        $b = strtolower(strval($b));

        return match ($operator) {
            ValueOperator::CONTAINS     => array_reduce(
                $a,
                fn(bool $carry, string $item) => $carry || str_contains($item, $b),
                false
            ),
            ValueOperator::NOT_CONTAINS => !array_reduce(
                $a,
                fn(bool $carry, string $item) => $carry || str_contains($item, $b),
                false
            ),

            // Unsupported operators
            default => false,
        };
    }
}
