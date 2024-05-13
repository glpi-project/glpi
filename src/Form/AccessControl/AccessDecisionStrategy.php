<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace Glpi\Form\AccessControl;

enum AccessDecisionStrategy: string
{
    case Affirmative = 'affirmative';
    case Unanimous = 'unanimous';

    /**
     * Compute a decision based on the given votes.
     *
     * @param bool[] $votes The votes to consider: true for a positive vote and
     *                      false for a negative vote.
     * @return bool True if the decision is positive, false otherwise.
     */
    public function getDecision(array $votes): bool
    {
        return match ($this) {
            self::Unanimous => !in_array(false, $votes, true),
            self::Affirmative => in_array(true, $votes, true),
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Unanimous => __('Unanimous'),
            self::Affirmative => __('Affirmative'),
        };
    }

    public static function getForDropdown(): array
    {
        $strategies = [];
        foreach (self::cases() as $strategy) {
            $strategies[$strategy->value] = $strategy->getLabel();
        }

        return $strategies;
    }
}
