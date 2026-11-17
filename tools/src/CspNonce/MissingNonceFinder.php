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

namespace Glpi\Tools\CspNonce;

use function Safe\preg_match;
use function Safe\preg_match_all;
use function Safe\preg_replace;
use function Safe\preg_replace_callback;

/**
 * Find <script> tags that do not specify the required nonce.
 */
final class MissingNonceFinder
{
    public const string EXPECTED_ATTRIBUTE = 'nonce="{{ csp_nonce() }}"';

    /**
     * @return list<array{line: int, column: int, tag: string, message: string}>
     */
    public static function find(string $source): array
    {
        // Blank Twig comments, keeping offsets intact, so that commented out scripts are ignored.
        $searchable = preg_replace_callback(
            '/\{#.*?#\}/s',
            static fn(array $match): string => preg_replace('/[^\n]/', ' ', $match[0]),
            $source
        );

        // Twig expressions and tags are matched as a whole, as they may contain a `>`.
        $pattern = '/<script\b(?<attrs>(?:[^>{]|\{\{.*?\}\}|\{%.*?%\}|\{(?![{%]))*)>/is';
        if (preg_match_all($pattern, $searchable, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER) === 0) {
            return [];
        }

        $missing = [];
        foreach ($matches as $match) {
            $attrs = $match['attrs'][0];

            if (preg_match('/(?<![\w:-])nonce\s*=\s*(["\'])\{\{-?\s*csp_nonce\(\s*\)\s*-?\}\}\1/i', $attrs) === 1) {
                continue;
            }

            $offset = $match[0][1];
            $line_start = strrpos(substr($source, 0, $offset), "\n");
            $tag = trim(preg_replace('/\s+/', ' ', $match[0][0]));

            $missing[] = [
                'line' => substr_count($source, "\n", 0, $offset) + 1,
                'column' => $offset - ($line_start === false ? 0 : $line_start + 1),
                'tag' => $tag,
                'message' => preg_match('/(?<![\w:-])nonce\s*=/i', $attrs) === 1
                    ? sprintf('The nonce of `%s` must be `{{ csp_nonce() }}`.', $tag)
                    : sprintf('`%s` must carry the CSP nonce: add `%s`.', $tag, self::EXPECTED_ATTRIBUTE),
            ];
        }

        return $missing;
    }
}
