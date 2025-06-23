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

namespace Glpi\Form\ServiceCatalog\SortStrategy;

enum SortStrategyEnum: string
{
    case ALPHABETICAL         = 'alphabetical';
    case REVERSE_ALPHABETICAL = 'reverse_alphabetical';
    case POPULARITY           = 'popularity';

    /**
     * Create a new instance of the strategy
     */
    public function getStrategy(): SortStrategyInterface
    {
        return match ($this) {
            self::ALPHABETICAL => new AlphabeticalSort(),
            self::REVERSE_ALPHABETICAL => new ReverseAlphabeticalSort(),
            self::POPULARITY => new PopularitySort(),
        };
    }

    /**
     * Get the default strategy
     */
    public static function getDefault(): self
    {
        return self::POPULARITY;
    }

    /**
     * Get all available sort strategies
     *
     * @return array<string, SortStrategyInterface>
     */
    public static function getAvailableStrategies(): array
    {
        $strategies = [];

        foreach (SortStrategyEnum::cases() as $case) {
            $strategies[$case->value] = $case->getStrategy();
        }

        return $strategies;
    }
}
