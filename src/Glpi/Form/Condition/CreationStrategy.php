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

enum CreationStrategy: string implements StrategyInterface
{
    case ALWAYS_CREATED = 'always_created';
    case CREATED_IF = 'created_if';
    case CREATED_UNLESS = 'created_unless';

    #[Override]
    public function getLabel(): string
    {
        return match ($this) {
            self::ALWAYS_CREATED => __("Always created"),
            self::CREATED_IF     => __("Created if..."),
            self::CREATED_UNLESS => __("Created unless..."),
        };
    }

    #[Override]
    public function getIcon(): string
    {
        return match ($this) {
            self::ALWAYS_CREATED => 'ti ti-plus',
            self::CREATED_IF     => 'ti ti-code-plus',
            self::CREATED_UNLESS => 'ti ti-code-plus',
        };
    }

    #[Override]
    public function showEditor(): bool
    {
        return match ($this) {
            self::ALWAYS_CREATED => false,
            self::CREATED_IF     => true,
            self::CREATED_UNLESS => true,
        };
    }

    public function mustBeCreated(bool $conditions_result): bool
    {
        return match ($this) {
            self::ALWAYS_CREATED => true,
            self::CREATED_IF     => $conditions_result,
            self::CREATED_UNLESS => !$conditions_result,
        };
    }
}
