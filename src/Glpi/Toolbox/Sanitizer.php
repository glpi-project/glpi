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

use Stringable;
use Toolbox;

use function Safe\preg_match;

class Sanitizer
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
     * Sanitize a value. Resulting value will have its HTML special chars converted into entities
     * and would be printable in a HTML document without having to be escaped.
     * Also, DB special chars can be escaped to prevent SQL injections.
     *
     * @param mixed $value
     * @param bool  $db_escape
     *
     * @return mixed
     *
     * @deprecated 11.0.0
     */
    public static function sanitize($value, bool $db_escape = false)
    {
        Toolbox::deprecated();

        if (is_array($value)) {
            return array_map(
                fn($val) => self::sanitize($val, $db_escape),
                $value
            );
        }

        if ($value instanceof Stringable || (\is_object($value) && \method_exists($value, '__toString'))) {
            $value = (string) $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        if (self::isNsClassOrCallableIdentifier($value)) {
            // Do not sanitize values that corresponds to an existing namespaced class, to prevent having to unsanitize
            // every usage of `itemtype` to correctly handle namespaces.
            return $value;
        }

        if ($db_escape) {
            $value = self::dbEscape($value);
        }

        return self::encodeHtmlSpecialChars($value);
    }

    /**
     * Unsanitize a value. Reverts self::sanitize() transformation.
     *
     * @param mixed $value
     * @param bool  $db_unescape
     *
     * @return mixed
     *
     * @deprecated 11.0.0
     */
    public static function unsanitize($value, bool $db_unescape = true)
    {
        Toolbox::deprecated();

        if (is_array($value)) {
            return array_map(
                fn($val) => self::unsanitize($val),
                $value
            );
        }
        if (!is_string($value)) {
            return $value;
        }

        $value = self::decodeHtmlSpecialChars($value);
        if ($db_unescape === true) {
            $value = self::dbUnescape($value);
        }

        return $value;
    }

    /**
     * Check if value is sanitized.
     *
     * @param string $value
     *
     * @return bool
     *
     * @deprecated 11.0.0
     */
    public static function isHtmlEncoded(string $value): bool
    {
        Toolbox::deprecated();

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
     * Check if value is escaped for DB usage.
     * A value is considered as escaped if it special char (NULL, \n, \r, \, ', " and EOF) that has been escaped.
     *
     * @param string $value
     *
     * @return bool
     *
     * @deprecated 11.0.0
     */
    public static function isDbEscaped(string $value): bool
    {
        Toolbox::deprecated();

        $value_length = strlen($value);

        // Search for unprotected control chars `NULL`, `\n`, `\r` and `EOF`.
        $control_chars = ["\x00", "\n", "\r", "\x1a"];
        foreach ($control_chars as $char) {
            $char_length = strlen($char);
            $i = 0;
            while (($i = strpos($value, $char, $i)) !== false) {
                if ($i + $char_length <= $value_length && substr($value, $i, $char_length) == $char) {
                    return false; // Unprotected control char found
                }
                $i++;
            }
        }

        // Search for unprotected quotes.
        $quotes = ["'", '"'];
        foreach ($quotes as $char) {
            $i = 0;
            while (($i = strpos($value, $char, $i)) !== false) {
                if ($i === 0 || substr($value, $i - 1, 1) !== '\\') {
                    return false; // Unprotected quote found
                }
                $i++;
            }
        }

        $has_special_chars = false;

        // Search for unprotected backslashes.
        if (str_contains($value, '\\')) {
            $special_chars = ['\x00', '\n', '\r', "\'", '\"', '\x1a'];
            $backslashes_count = 0;

            $i = 0;
            while (($i = strpos($value, '\\', $i)) !== false) {
                $has_special_chars = true;

                // Count successive backslashes.
                $backslashes_count = 1;
                while ($i + 1 <= $value_length && substr($value, $i + 1, 1) == '\\') {
                    $backslashes_count++;
                    $i++;
                }

                // Check if last backslash is related to an escaped special char.
                foreach ($special_chars as $char) {
                    $char_length = strlen($char);
                    if ($i + $char_length <= $value_length && substr($value, $i, $char_length) == $char) {
                        $backslashes_count--;
                        break;
                    }
                }

                // Backslashes are escaped only if there is odd count of them.
                if ($backslashes_count % 2 === 1) {
                    return false; // Unprotected backslash or quote found
                }

                $i++;
            }
        }

        return $has_special_chars;
    }

    /**
     * Check whether the value correspond to a valid namespaced class (or a callable identifier related to a valid class).
     * Note: also support the {namespace}${tab number} format used for tab identifications
     *
     * @param string $value
     *
     * @return bool
     *
     * @deprecated 11.0.0
     */
    public static function isNsClassOrCallableIdentifier(string $value): bool
    {
        Toolbox::deprecated();

        $class_match = [];

        return preg_match(
            '/^(?<class>(([a-zA-Z0-9_]+\\\)+[a-zA-Z0-9_]+))(:?:[a-zA-Z0-9_]+)?(\$[0-9]+)?$/',
            $value,
            $class_match
        ) && class_exists($class_match['class']);
    }

    /**
     * Return verbatim value for an itemtype field.
     * Returned value will be unsanitized if it has been transformed by GLPI sanitizing process.
     *
     * @param string $value
     *
     * @return string
     *
     * @deprecated 11.0.0
     */
    public static function getVerbatimValue(string $value): string
    {
        Toolbox::deprecated();

        return self::unsanitize($value);
    }

    /**
     * Encode HTML special chars, to prevent XSS when value is printed without using any filter.
     *
     * @param string $value
     *
     * @return string
     *
     * @deprecated 11.0.0
     */
    public static function encodeHtmlSpecialChars(string $value): string
    {
        Toolbox::deprecated();

        if (self::isHtmlEncoded($value)) {
            return $value;
        }

        $mapping = self::CHARS_MAPPING;
        return str_replace(array_keys($mapping), array_values($mapping), $value);
    }

    /**
     * Recursively encode HTML special chars on an array.
     *
     * @param array $values
     *
     * @return array
     *
     * @see self::encodeHtmlSpecialChars
     *
     * @deprecated 11.0.0
     */
    public static function encodeHtmlSpecialCharsRecursive(array $values): array
    {
        Toolbox::deprecated();

        return array_map(
            function ($value) {
                if (is_array($value)) {
                    return self::encodeHtmlSpecialCharsRecursive($value);
                }
                if (
                    is_string($value)
                    || $value instanceof Stringable
                    || (\is_object($value) && \method_exists($value, '__toString'))
                ) {
                    return self::encodeHtmlSpecialChars((string) $value);
                }
                return $value;
            },
            $values
        );
    }

    /**
     * Decode HTML special chars.
     *
     * @param string $value
     *
     * @return string
     *
     * @deprecated 11.0.0
     */
    public static function decodeHtmlSpecialChars(string $value): string
    {
        Toolbox::deprecated();

        if (!self::isHtmlEncoded($value)) {
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
     * Recursively decode HTML special chars on an array.
     *
     * @param array $values
     *
     * @return array
     *
     * @see self::decodeHtmlSpecialChars
     *
     * @deprecated 11.0.0
     */
    public static function decodeHtmlSpecialCharsRecursive(array $values): array
    {
        Toolbox::deprecated();

        return array_map(
            function ($value) {
                if (is_array($value)) {
                    return self::decodeHtmlSpecialCharsRecursive($value);
                }
                if (is_string($value)) {
                    return self::decodeHtmlSpecialChars($value);
                }
                return $value;
            },
            $values
        );
    }

    /**
     * Escape DB special chars to protect DB queries.
     *
     * @param string $value
     *
     * @return string
     *
     * @deprecated 11.0.0
     */
    public static function dbEscape(string $value): string
    {
        Toolbox::deprecated();

        if (str_contains($value, '\\') && self::isDbEscaped($value)) {
            // Value is already escaped, do not escape it again.
            // Nota: use `str_contains` to speedup check.
            return $value;
        }

        global $DB;
        return $DB->escape($value);
    }

    /**
     * Recursively escape DB special chars.
     *
     * @param array $values
     *
     * @return array
     *
     * @see self::dbEscape
     *
     * @deprecated 11.0.0
     */
    public static function dbEscapeRecursive(array $values): array
    {
        Toolbox::deprecated();

        return array_map(
            function ($value) {
                if (is_array($value)) {
                    return self::dbEscapeRecursive($value);
                }
                if (
                    is_string($value)
                    || $value instanceof Stringable
                    || (\is_object($value) && \method_exists($value, '__toString'))
                ) {
                    return self::dbEscape((string) $value);
                }
                return $value;
            },
            $values
        );
    }

    /**
     * Revert `mysqli::real_escape_string()` transformation.
     * Inspired by https://stackoverflow.com/a/38769977
     *
     * @param string $value
     *
     * @return string
     *
     * @deprecated 11.0.0
     */
    public static function dbUnescape(string $value): string
    {
        Toolbox::deprecated();

        // stripslashes cannot be used here as it would produce "r" and "n" instead of "\r" and \n".

        if (!(str_contains($value, '\\') && self::isDbEscaped($value))) {
            // Value is not escaped, do not unescape it.
            // Nota: use `str_contains` to speedup check.
            return $value;
        }

        $mapping = [
            'x00' => "\x00",
            'n'   => "\n",
            'r'   => "\r",
            '\\'  => "\\",
            '\''  => "'",
            '"'   => "\"",
            'x1a' => "\x1a",
        ];
        $search  = [];
        $replace = [];
        foreach ($mapping as $s => $r) {
            if (str_contains($value, $s)) {
                $search[]  = $s;
                $replace[] = $r;
            }
        }
        if ($search === []) {
            // Value does not contains any potentially escaped chars.
            return $value;
        }

        $offset = 0;
        $previous_offset = 0;
        $result = '';
        while (($offset = strpos($value, '\\', $offset)) !== false) {
            foreach ($search as $index => $char) {
                $escaped_char = '\\' . $char;
                if ($offset + strlen($char) <= strlen($value) && substr($value, $offset, strlen($escaped_char)) == $escaped_char) {
                    // Append substring located between previous replaced char and current char
                    $result .= substr($value, $previous_offset, $offset - $previous_offset);
                    // Append replacement char
                    $result .= $replace[$index];
                    // Move ofsset after replaced escaped char
                    $offset += strlen($escaped_char);
                    $previous_offset = $offset;
                    break;
                }
            }
        }
        // Append substring located after latest replaced char
        $result .= substr($value, $previous_offset);

        return $result;
    }

    /**
     * Recursively revert `mysqli::real_escape_string()` transformation.
     *
     * @param array $values
     *
     * @return array
     *
     * @see self::dbUnescape
     *
     * @deprecated 11.0.0
     */
    public static function dbUnescapeRecursive(array $values): array
    {
        Toolbox::deprecated();

        return array_map(
            function ($value) {
                if (is_array($value)) {
                    return self::dbUnescapeRecursive($value);
                }
                if (is_string($value)) {
                    return self::dbUnescape($value);
                }
                return $value;
            },
            $values
        );
    }
}
