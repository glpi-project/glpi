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

final class FuzzyMatcher
{
    public function __construct(
        private PartialMatchStrategy $strategy,
    ) {}

    public function match(string $subject, string $filter): bool
    {
        if ($filter === "") {
            return true;
        }

        // Cast to lowercase to avoid case issues
        $subject = strtolower($subject);
        $filter  = strtolower($filter);

        // Start with a simple string comparison if the strategy allow it.
        if (
            $this->strategy->tryToMatchUsingStrContains()
            && str_contains($subject, $filter) !== false
        ) {
            return true;
        }

        // Some strategies might disable fuzzy matching for short filters
        // as it may lead too many results that are not really relevant.
        // In this case, we stop the execution here.
        $min_length = $this->strategy->minimumFilterLenghtForFuzzySearch();
        if ($min_length > 0  && strlen($filter) <= $min_length) {
            return false;
        }

        // Actual fuzzy matching, use the costs and threshold defined in the
        // strategy.
        $cost = levenshtein(
            string1: $subject,
            string2: $filter,
            insertion_cost: $this->strategy->insertionCost(),
            replacement_cost: $this->strategy->replacementCost(),
            deletion_cost: $this->strategy->deletionCost(),
        );
        if ($cost <= $this->strategy->maxCostForSuccess()) {
            return true;
        }

        return false;
    }
}
