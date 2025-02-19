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

enum LogicOperator: string
{
    case AND = 'and';
    case OR = 'or';

    public function getLabel(): string
    {
        return match ($this) {
            self::AND => __("And"),
            self::OR  => __("Or"),
        };
    }

    public static function getDropdownValues(): array
    {
        return [
            self::AND->value => self::AND->getLabel(),
            self::OR->value  => self::OR->getLabel(),
        ];
    }

    public function apply(bool $a, bool $b): bool
    {
        return match ($this) {
            self::AND => $a && $b,
            self::OR  => $a || $b,
        };
    }
}
