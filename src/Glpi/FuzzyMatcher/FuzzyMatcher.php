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

use Normalizer;
use function Safe\preg_split;

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

        // Normalize strings to remove accents
        // The normalizer decomposes the string (FORM_D), which then allows
        // the preg_replace to remove the accents (identified by \p{Mn})
        $subject = Normalizer::normalize($subject, Normalizer::FORM_D);
        $subject = preg_replace('/\p{Mn}/u', '', $subject);
        $filter = Normalizer::normalize($filter, Normalizer::FORM_D);
        $filter = preg_replace('/\p{Mn}/u', '', $filter);

        // Some strategies might disable fuzzy matching for short filters
        // as it may lead too many results that are not really relevant.
        // In this case, we stop the execution here.
        $min_length = $this->strategy->minimumFilterLenghtForFuzzySearch();
        if ($min_length > 0  && strlen($filter) <= $min_length) {
            return false;
        }

        $subject_words = preg_split('/\s+/', $subject, -1, PREG_SPLIT_NO_EMPTY);
        $filter_words = preg_split('/\s+/', $filter, -1, PREG_SPLIT_NO_EMPTY);

        // Check if each filter word match at least one subject word
        foreach ($filter_words as $filter_word) {
            $word_match = false;
            foreach ($subject_words as $subject_word) {
                if ($this->matchWord($subject_word, $filter_word)) {
                    $word_match = true;
                    break;
                }
            }

            if (!$word_match) {
                return false;
            }
        }

        return true;
    }

    private function matchWord(string $word, string $filter): bool
    {
        $cost = levenshtein(
            string1: $word,
            string2: $filter,
            insertion_cost: $this->strategy->insertionCost(),
            replacement_cost: $this->strategy->replacementCost(),
            deletion_cost: $this->strategy->deletionCost(),
        );

        $max_cost = $this->strategy->maxCostForSuccess(strlen($word));

        return $cost <= $max_cost;
    }
}
