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

namespace Glpi\Toolbox;

use function Safe\preg_match;

class SanitizedStringsDecoder
{
    private const CHARS_MAPPING = [
        '&'  => '&#38;',
        '<'  => '&#60;',
        '>'  => '&#62;',
    ];

    private const LEGACY_CHARS_MAPPING = [
        '<'  => '&lt;',
        '>'  => '&gt;',
    ];

    /**
     * Check if special chars are encoded.
     */
    private function isHtmlEncoded(string $value): bool
    {
        // A value is Html Encoded if it does not contains
        // - `<`;
        // - `>`;
        // - `&` not followed by an HTML entity identifier;
        // and if it contains any entity used to encode HTML special chars during sanitization process.
        $special_chars_pattern   = '/(<|>|(&(?!#?[a-z0-9]+;)))/i';
        $sanitized_chars = array_merge(
            array_values(self::CHARS_MAPPING),
            array_values(self::LEGACY_CHARS_MAPPING)
        );
        $sanitized_chars_pattern = '/(' . implode('|', $sanitized_chars) . ')/';

        return preg_match($special_chars_pattern, $value) === 0
            && preg_match($sanitized_chars_pattern, $value) === 1;
    }

    /**
     * Decode HTML special chars.
     */
    public function decodeHtmlSpecialChars(string $value): string
    {
        if (!$this->isHtmlEncoded($value)) {
            return $value;
        }

        $mapping = null;
        foreach (self::CHARS_MAPPING as $htmlentity) {
            if (str_contains($value, $htmlentity)) {
                // Value was cleaned using new char mapping, so it must be uncleaned with same mapping
                $mapping = self::CHARS_MAPPING;
                break;
            }
        }
        if ($mapping === null) {
            $mapping = self::LEGACY_CHARS_MAPPING; // Fallback to legacy chars mapping

            if (preg_match('/&lt;img\s+(alt|src|width)=&quot;/', $value)) {
                // In some cases (at least on some ITIL followups, quotes have been converted too,
                // probably due to a misusage of encoding process.
                // Result is that quotes were encoded too (i.e. `&lt:img src=&quot;/front/document.send.php`)
                // and should be decoded too.
                $mapping['"'] = '&quot;';
            }
        }

        $mapping = array_reverse($mapping);
        return str_replace(array_values($mapping), array_keys($mapping), $value);
    }

    /**
     * Decode HTML special chars in completename field value.
     */
    public function decodeHtmlSpecialCharsInCompletename(string $value): string
    {
        $separator = '>';

        return implode(
            $separator,
            array_map(
                fn(string $chunk) => $this->decodeHtmlSpecialChars($chunk),
                explode($separator, $value)
            )
        );
    }
}
