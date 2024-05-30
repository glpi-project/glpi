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

namespace Glpi\Form;

/**
 * Utility class to provide the end user input name
 */
final class EndUserInputNameProvider
{
    public const END_USER_INPUT_NAME = 'answers_%d';
    public const END_USER_INPUT_NAME_REGEX = '/^_?answers_(\d+)$/';

    /**
     * Get the end user input name for a given question
     *
     * @param Question $question
     * @return string
     */
    public function getEndUserInputName(Question $question): string
    {
        return sprintf(self::END_USER_INPUT_NAME, $question->getID());
    }

    /**
     * Get the answers submitted by the end user
     * The answers are indexed by question ID
     *
     * @param array $inputs The inputs submitted by the end user
     * @return array
     */
    public function getAnswers(array $inputs): array
    {
        $filteredAnswers = self::filterAnswers($inputs);
        $reindexedAnswers = self::reindexAnswers($filteredAnswers);

        return $reindexedAnswers;
    }

    /**
     * Filter the answers submitted by the end user
     * Only the answers that match the end user input name pattern are kept
     *
     * @param array $answers
     * @return array
     */
    private function filterAnswers(array $answers): array
    {
        return array_filter(
            $answers,
            function ($key) {
                return preg_match(self::END_USER_INPUT_NAME_REGEX, $key);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Reindex the answers submitted by the end user
     * The answers are indexed by question ID
     *
     * @param array $answers
     * @return array
     */
    private function reindexAnswers(array $answers): array
    {
        return array_reduce(
            array_keys($answers),
            function ($carry, $key) use ($answers) {
                $question_id = (int) preg_replace(self::END_USER_INPUT_NAME_REGEX, '$1', $key);
                $carry[$question_id] = $answers[$key];
                return $carry;
            },
            []
        );
    }
}
