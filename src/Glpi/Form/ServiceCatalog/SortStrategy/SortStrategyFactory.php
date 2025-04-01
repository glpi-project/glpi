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

final class SortStrategyFactory
{
    /** @var string */
    public const DEFAULT_STRATEGY = 'popularity';

    /** @var string[] */
    private const AVAILABLE_STRATEGIES = [
        'alphabetical'         => AlphabeticalSort::class,
        'reverse_alphabetical' => ReverseAlphabeticalSort::class,
        'popularity'           => PopularitySort::class,
    ];

    /**
     * Create a sort strategy based on the given name
     *
     * @param string|null $name The name of the sort strategy to create, or null to use the default
     * @return SortStrategyInterface
     */
    public static function create(?string $name = null): SortStrategyInterface
    {
        $name = $name ?? self::DEFAULT_STRATEGY;

        if (!isset(self::AVAILABLE_STRATEGIES[$name])) {
            $name = self::DEFAULT_STRATEGY;
        }

        $class = self::AVAILABLE_STRATEGIES[$name];
        return new $class();
    }

    /**
     * Get all available sort strategies
     *
     * @return array<string, SortStrategyInterface>
     */
    public static function getAvailableStrategies(): array
    {
        $strategies = [];

        foreach (self::AVAILABLE_STRATEGIES as $name => $class) {
            $strategies[$name] = new $class();
        }

        return $strategies;
    }
}
