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

namespace Glpi;

enum ImpactLevel: int
{
    case VERY_LOW = 1;
    case LOW = 2;
    case MEDIUM = 3;
    case HIGH = 4;
    case VERY_HIGH = 5;

    /**
     * @return array<int, string>
     */
    public static function getEnabledImpactValuesForDropdown(): array
    {
        global $CFG_GLPI;

        return array_filter(
            array_combine(
                array_map(fn($case) => $case->value, self::cases()),
                array_map(fn($case) => \CommonITILObject::getImpactName($case->value), self::cases()),
            ),
            fn($key) => (($CFG_GLPI['impact_mask'] & (1 << $key)) > 0),
            ARRAY_FILTER_USE_KEY
        );
    }
}
