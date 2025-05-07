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

namespace Glpi\Form\Condition;

use Override;

enum ValidationStrategy: string implements StrategyInterface
{
    case NO_VALIDATION = 'no_validation';
    case VALID_IF      = 'valid_if';
    case INVALID_IF    = 'invalid_if';

    #[Override]
    public function getLabel(): string
    {
        return match ($this) {
            self::NO_VALIDATION => __('No validation'),
            self::VALID_IF      => __('Valid if...'),
            self::INVALID_IF    => __('Invalid if...'),
        };
    }

    #[Override]
    public function getIcon(): string
    {
        return match ($this) {
            self::NO_VALIDATION => 'ti ti-filter',
            self::VALID_IF      => 'ti ti-filter-cog',
            self::INVALID_IF    => 'ti ti-filter-x',
        };
    }

    #[Override]
    public function showEditor(): bool
    {
        return match ($this) {
            self::NO_VALIDATION => false,
            self::VALID_IF      => true,
            self::INVALID_IF    => true,
        };
    }

    public function mustBeValidated(bool $conditions_result): bool
    {
        return match ($this) {
            self::NO_VALIDATION => true,
            self::VALID_IF      => $conditions_result,
            self::INVALID_IF    => !$conditions_result,
        };
    }
}
