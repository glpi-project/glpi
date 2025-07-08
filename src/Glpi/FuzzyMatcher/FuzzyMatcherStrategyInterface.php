<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\FuzzyMatcher;

interface FuzzyMatcherStrategyInterface
{
    /**
     * If true, the matcher will run a simple str_contains check before the
     * actual fuzzy matching.
     * Might be useful if you also enable the minimumFilterLenghtForFuzzySearch.
     */
    public function tryToMatchUsingStrContains(): bool;

    /**
     * If above 0, will disable fuzzy matching if the filter is below the
     * specified length.
     * Useful to avoid having too many irrelevant match on stategies with a low
     * deletion cost.
     */
    public function minimumFilterLenghtForFuzzySearch(): int;

    /**
     * @see https://www.php.net/manual/en/function.levenshtein.php
     */
    public function insertionCost(): int;

    /**
     * @see https://www.php.net/manual/en/function.levenshtein.php
     */
    public function replacementCost(): int;

    /**
     * @see https://www.php.net/manual/en/function.levenshtein.php
     */
    public function deletionCost(): int;

    /**
     * @see https://www.php.net/manual/en/function.levenshtein.php
     */
    public function maxCostForSuccess(): int;
}
