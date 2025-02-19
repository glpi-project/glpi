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

enum VisibilityStrategy: string implements StrategyInterface
{
    case ALWAYS_VISIBLE = 'always_visible';
    case VISIBLE_IF = 'visible_if';
    case HIDDEN_IF = 'hidden_if';

    #[Override]
    public function getLabel(): string
    {
        return match ($this) {
            self::ALWAYS_VISIBLE => __('Always visible'),
            self::VISIBLE_IF     => __('Visible if...'),
            self::HIDDEN_IF      => __("Hidden if..."),
        };
    }

    #[Override]
    public function getIcon(): string
    {
        return match ($this) {
            self::ALWAYS_VISIBLE => 'ti ti-eye',
            self::VISIBLE_IF     => 'ti ti-eye-cog',
            self::HIDDEN_IF      => 'ti ti-eye-off',
        };
    }

    #[Override]
    public function showEditor(): bool
    {
        return match ($this) {
            self::ALWAYS_VISIBLE => false,
            self::VISIBLE_IF     => true,
            self::HIDDEN_IF      => true,
        };
    }

    public function mustBeVisible(bool $conditions_result): bool
    {
        return match ($this) {
            self::ALWAYS_VISIBLE => true,
            self::VISIBLE_IF     => $conditions_result,
            self::HIDDEN_IF      => !$conditions_result,
        };
    }
}
