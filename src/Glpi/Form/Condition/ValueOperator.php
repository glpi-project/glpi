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

enum ValueOperator: string
{
    case EQUALS = 'equals';
    case NOT_EQUALS = 'not_equals';
    case CONTAINS = 'contains';
    case NOT_CONTAINS = 'not_contains';
    case GREATER_THAN = 'greater_than';
    case GREATER_THAN_OR_EQUALS = 'greater_than_or_equals';
    case LESS_THAN = 'less_than';
    case LESS_THAN_OR_EQUALS = 'less_than_or_equals';
    case MATCH_REGEX = 'match_regex';
    case NOT_MATCH_REGEX = 'not_match_regex';

    // Not yet implemented:
    // case VISIBLE = 'visible';
    // case NOT_VISIBLE = 'not_visible';

    public function getLabel(): string
    {
        return match ($this) {
            self::EQUALS                 => __("Is equal to"),
            self::NOT_EQUALS             => __("Is not equal to"),
            self::CONTAINS               => __("Contains"),
            self::NOT_CONTAINS           => __("Do not contains"),
            self::GREATER_THAN           => __("Is greater than"),
            self::GREATER_THAN_OR_EQUALS => __("Is greater than or equals to"),
            self::LESS_THAN              => __("Is less than"),
            self::LESS_THAN_OR_EQUALS    => __("Is less than or equals to"),
            self::MATCH_REGEX            => __("Match regular expression"),
            self::NOT_MATCH_REGEX        => __("Do not match regular expression"),
        };
    }
}
