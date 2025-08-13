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

namespace Glpi\Api\HL\RSQL;

use function Safe\preg_match;

/**
 * Lexer to parse RSQL query (extension of FIQL) string into tokens.
 */
final class Lexer
{
    /** @var int Separator in an RSQL query denoting a logical AND. This is a semicolon in RSQL. */
    public const T_AND = 1;
    /** @var int Separator in an RSQL query denoting a logical OR. This is a colon in RSQL. */
    public const T_OR = 2;
    /** @var int Opening parenthesis denoting the start of a group (no preceding backslash and not part of a T_VALUE). */
    public const T_GROUP_OPEN = 3;
    /** @var int Closing parenthesis denoting the end of a group (no preceding backslash and not part of a T_VALUE). */
    public const T_GROUP_CLOSE = 4;
    /** @var int Part of an individual filter which indicates which property/field it is for.
     * For example, given a filter of model.name=like="test*", model.name is the property.
     */
    public const T_PROPERTY = 5;
    /** @var int Part of an individual filter which indicates the operator to use.
     * For example, given a filter of model.name=like="test*", =like= is the operator.
     * All operators start and end with an equals sign with zero or more a-z characters in between.
     */
    public const T_OPERATOR = 6;
    /** @var int Part of an individual filter which indicates the value to compare against.
     * For example, given a filter of model.name=like="test*", "test*" is the value.
     * The value may or may not be quoted with double or single quotes.
     */
    public const T_VALUE = 7;

    /**
     * @var int Token used when a value would be expected but not found.
     * This could indicate an incomplete filter, or it could simply be a normal occurance if the preceding operator is a unary operator (=isempty= for example).
     */
    public const T_UNSPECIFIED_VALUE = 8;

    public const CHAR_AND = ';';
    public const CHAR_OR = ',';
    public const CHAR_GROUP_OPEN = '(';
    public const CHAR_GROUP_CLOSE = ')';
    public const CHAR_ESCAPE = '\\';
    public const CHARS_PROPERTY = '/[a-zA-Z0-9_.]/';

    private function __construct() {}

    /**
     * Tokenize an RSQL query string into an array of tokens.
     *
     * @param string $query The RSQL query string to tokenize.
     * @return array An array of tokens.
     * @throws RSQLException If the query string cannot be tokenized completely.
     */
    public static function tokenize(string $query): array
    {
        $tokens = [];
        $pos = 0;
        $buffer = '';
        $length = mb_strlen($query, 'UTF-8');
        $in_filter = false;
        $in_value = false;
        $fn_validate_pos = static function () use ($pos, $length) {
            // Note: phpstan doesn't like this, it should probably be a dedicated method.
            if ($pos < 0 || $pos >= $length) { // @phpstan-ignore smaller.alwaysFalse
                // This case will probably never happen. An issue should be caught before now with a more specific error message.
                throw new RSQLException(
                    message: 'RSQL parser reached an invalid position while parsing the query string.',
                    user_message: __('Invalid RSQL query')
                );
            }
        };

        // Note: When parsing an individual filter, the property and value tokens need to be determined by looking for an operator.
        // Some facts to ease understanding and simplify the tokenization process:
        // Properties will never contain an equals sign. The characters will always be alphanumeric, underscore, or period.
        // Operators will always start and end with an equals sign. The characters in between will always be a-z or nothing.
        // Values MAY be quoted with double or single quotes. If they are quoted, the quotes will be the first and last characters.
        // Values may contain a parenthesis which should NOT be treated as a group open/close if the value is quoted or the parenthesis is escaped with a backslash.
        // If the operator is =in=, =out= or some other operator which takes a list of values, the value will be a comma-separated list of values inside parentheses.

        while ($pos < $length) {
            $char = $query[$pos];
            $prev_char = $pos - 1 >= 0 ? $query[$pos - 1] : null;

            if (!$in_value && $char === self::CHAR_GROUP_OPEN && $prev_char !== self::CHAR_ESCAPE) {
                $tokens[] = [self::T_GROUP_OPEN, $char];
            } elseif (!$in_value && $char === self::CHAR_GROUP_CLOSE && $prev_char !== self::CHAR_ESCAPE) {
                $tokens[] = [self::T_GROUP_CLOSE, $char];
            } elseif (!$in_filter && $char === self::CHAR_AND) {
                $tokens[] = [self::T_AND, $char];
            } elseif (!$in_filter && $char === self::CHAR_OR) {
                $tokens[] = [self::T_OR, $char];
            } elseif (!$in_filter && preg_match(self::CHARS_PROPERTY, $char)) {
                $in_filter = true;
                $buffer .= $char;
                // Property should continue until a '=' or '!' (in the case of != operator) is found.
                while ($pos + 1 < $length && $query[$pos + 1] !== '=' && $query[$pos + 1] !== '!') {
                    $buffer .= $query[++$pos];
                }
                $tokens[] = [self::T_PROPERTY, $buffer];
                // Now the operator is started
                $pos++;
                if (!isset($query[$pos]) || ($query[$pos] !== '=' && $query[$pos] !== '!')) {
                    throw new RSQLException('', sprintf(__('RSQL query is missing an operator in filter for property "%1$s"'), $tokens[count($tokens) - 1][1]));
                }
                $fn_validate_pos();
                $buffer = $query[$pos];
                // Operator should continue until the next '=' is found.
                while ($pos + 1 < $length && $query[$pos + 1] !== '=') {
                    $buffer .= $query[++$pos];
                }
                if (!isset($query[$pos + 1]) || $query[$pos + 1] !== '=') {
                    throw new RSQLException('', sprintf(__('RSQL query has an incomplete operator in filter for property "%1$s"'), $tokens[count($tokens) - 1][1]));
                }
                $tokens[] = [self::T_OPERATOR, $buffer . '='];
                // Now the value is started
                $buffer = '';
                $pos += 2;
                $fn_validate_pos();
                $in_value = true;
                if (!isset($query[$pos])) {
                    $tokens[] = [self::T_UNSPECIFIED_VALUE, ''];
                    break;
                }
                // If the current char is ', ", or (, then the value continues until the matching closing quote or parenthesis is found.
                // When matching, we ignore escaped quotes and parenthesis.
                $char = $query[$pos];
                $starting_char = $char;
                $expected_ending_char = null;
                $buffer = $char;
                if (in_array($starting_char, ['(', '"', '\''])) {
                    $expected_ending_char = $starting_char === '(' ? ')' : $starting_char;
                } else {
                    // We have no starting quote or parenthesis, so the value continues until an unescaped comma, unescaped semicolon, unescaped close parenthesis, or end of string is found.
                }
                while ($pos + 1 < $length) {
                    $char = $query[++$pos];
                    if ($char === self::CHAR_ESCAPE) {
                        // If the current char is an escape character, then the next character is part of the value.
                        $buffer .= $query[++$pos];
                    } elseif ($expected_ending_char !== null && $char === $expected_ending_char) {
                        // If the current char is the expected ending char, then the value is complete.
                        $buffer .= $char;
                        $tokens[] = [self::T_VALUE, $buffer];
                        $buffer = '';
                        $in_filter = false;
                        $in_value = false;
                        break;
                    } elseif ($expected_ending_char === null && in_array($char, [self::CHAR_AND, self::CHAR_OR, self::CHAR_GROUP_CLOSE])) {
                        // If the current char is a comma, semicolon, or close parenthesis, then the value is complete.
                        $tokens[] = [self::T_VALUE, $buffer];
                        $buffer = '';
                        $in_filter = false;
                        $in_value = false;
                        $pos--;
                        break;
                    } else {
                        $buffer .= $char;
                    }
                }
                if ($buffer !== '') {
                    $tokens[] = [self::T_VALUE, $buffer];
                    $buffer = '';
                } elseif ($tokens[count($tokens) - 1][0] === self::T_OPERATOR) {
                    $tokens[] = [self::T_UNSPECIFIED_VALUE, ''];
                }
            }
            $pos++;
        }
        // If there are more open group tokens than close group tokens, then the query is invalid.
        $group_open_count = 0;
        $group_close_count = 0;
        foreach ($tokens as $token) {
            if ($token[0] === self::T_GROUP_OPEN) {
                $group_open_count++;
            } elseif ($token[0] === self::T_GROUP_CLOSE) {
                $group_close_count++;
            }
        }
        if ($group_open_count !== $group_close_count) {
            throw new RSQLException('', __('RSQL query has one or more unclosed groups'));
        }
        return $tokens;
    }
}
