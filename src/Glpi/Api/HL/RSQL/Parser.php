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

use DBmysql;
use DBmysqlIterator;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Search;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;

use function Safe\preg_match;
use function Safe\preg_replace;

/**
 * Parses tokens from the RSQL lexer into a SQL criteria array to be used by the {@link \DBmysqlIterator} class.
 */
final class Parser
{
    private Search $search;

    private DBmysql $db;

    public function __construct(Search $search)
    {
        global $DB;
        $this->search = $search;
        $this->db = $DB;
    }

    /**
     * @param string $str_array
     * @return array
     * @throws RSQLException If the string is not a valid RSQL group.
     */
    private function rsqlGroupToArray(string $str_array): array
    {
        // Convert string like (item1, item2,item3) to array
        if (!preg_match('/^\((.*)\)$/', $str_array, $matches)) {
            throw new RSQLException('', sprintf(__('Invalid RSQL array: %s'), $str_array));
        }

        // Use CSV parser to handle splitting by comma (not inside quotes) to avoid headache trying to maintain custom regex
        $items = str_getcsv($matches[1], escape: "");
        // Strip slashes added by CSV parser
        $items = array_map('stripslashes', $items);
        // Trim outter quotes (' or ") but only if they are present on both sides
        $items = array_map(function ($item) {
            if (preg_match('/^\s*".*"\s*$/', $item) || preg_match("/^\s*'.*'\s*$/", $item)) {
                return substr($item, 1, -1);
            }
            return $item;
        }, $items);
        return $items;
    }

    public function getOperators(): array
    {
        static $operators = null;

        if ($operators === null) {
            $operators = [
                [
                    'operator' => '==',
                    'description' => 'equivalent to',
                    'value_expected' => true,
                    'sql_where_callable' => fn($a, $b) => [
                        [$this->db::quoteName($a) => $b],
                    ],
                ],
                [
                    'operator' => '!=',
                    'description' => 'not equivalent to',
                    'value_expected' => true,
                    'sql_where_callable' => fn($a, $b) => [
                        [$this->db::quoteName($a) => ['<>', $b]],
                    ],
                ],
                [
                    'operator' => '=in=',
                    'description' => 'in',
                    'value_expected' => true,
                    'sql_where_callable' => fn($a, $b) => [
                        [$this->db::quoteName($a) => $this->rsqlGroupToArray($b)],
                    ],
                ],
                [
                    'operator' => '=out=',
                    'description' => 'not in',
                    'value_expected' => true,
                    'sql_where_callable' => fn($a, $b) => [
                        ['NOT' => [$this->db::quoteName($a) => $this->rsqlGroupToArray($b)]],
                    ],
                ],
                [
                    'operator' => '=lt=',
                    'description' => 'less than',
                    'value_expected' => true,
                    'sql_where_callable' => fn($a, $b) => [
                        [$this->db::quoteName($a) => ['<', $b]],
                    ],
                ],
                [
                    'operator' => '=le=',
                    'description' => 'less than or equal to',
                    'value_expected' => true,
                    'sql_where_callable' => fn($a, $b) => [
                        [$this->db::quoteName($a) => ['<=', $b]],
                    ],
                ],
                [
                    'operator' => '=gt=',
                    'description' => 'greater than',
                    'value_expected' => true,
                    'sql_where_callable' => fn($a, $b) => [
                        [$this->db::quoteName($a) => ['>', $b]],
                    ],
                ],
                [
                    'operator' => '=ge=',
                    'description' => 'greater than or equal to',
                    'value_expected' => true,
                    'sql_where_callable' => fn($a, $b) => [
                        [$this->db::quoteName($a) => ['>=', $b]],
                    ],
                ],
                [
                    'operator' => '=like=',
                    'description' => 'like',
                    'value_expected' => true,
                    'sql_where_callable' => function ($a, $b) {
                        $b = str_replace(['%', '*'], ['_', '%'], $b);
                        return [
                            [
                                $this->db::quoteName($a) => ['LIKE', QueryFunction::cast(new QueryExpression($this->db->quote($b)), 'BINARY')],
                            ],
                        ];
                    },
                ],
                [
                    'operator' => '=ilike=',
                    'description' => 'case insensitive like',
                    'value_expected' => true,
                    'sql_where_callable' => function ($a, $b) {
                        $b = str_replace(['%', '*'], ['_', '%'], $b);
                        return [
                            [$this->db::quoteName($a) => ['LIKE', $b]],
                        ];
                    },
                ],
                [
                    'operator' => '=isnull=',
                    'description' => 'is null',
                    'value_expected' => false,
                    'sql_where_callable' => fn($a, $b) => [
                        [$this->db::quoteName($a) => null],
                    ],
                ],
                [
                    'operator' => '=notnull=',
                    'description' => 'is not null',
                    'value_expected' => false,
                    'sql_where_callable' => fn($a, $b) => [
                        ['NOT' => [$this->db::quoteName($a) => null]],
                    ],
                ],
                [
                    // Empty string or null
                    'operator' => '=empty=',
                    'description' => 'is empty',
                    'value_expected' => false,
                    'sql_where_callable' => fn($a, $b) => [
                        [
                            'OR' => [
                                [$this->db::quoteName($a) => ''],
                                [$this->db::quoteName($a) => null],
                            ],
                        ],
                    ],
                ],
                [
                    'operator' => '=notempty=',
                    'description' => 'is not empty',
                    'value_expected' => false,
                    'sql_where_callable' => fn($a, $b) => [
                        [
                            'AND' => [
                                [$this->db::quoteName($a) => ['<>', '']],
                                [$this->db::quoteName($a) => ['<>', null]],
                            ],
                        ],
                    ],
                ],
            ];
        }
        return $operators;
    }

    /**
     * @param array $tokens Tokens from the RSQL lexer.
     * @return Result RSQL query result
     * @throws RSQLException
     */
    public function parse(array $tokens): Result
    {
        $it = new DBmysqlIterator($this->db);
        // We are building a SQL string instead of criteria array because it isn't worth the complexity or overhead.
        // Everything done here should be standard SQL. If there is a platform difference, it should be handled in the callables for each operator.
        // SQL already will process logical separators (AND, OR) in the correct order, so we don't need to worry about that.
        $sql_where_string = '';
        $sql_having_string = '';

        $position = 0;
        $token_count = count($tokens);
        $operators = $this->getOperators();
        // Loop through operators and set the keys to the operator property
        $operators = array_combine(array_column($operators, 'operator'), $operators);

        $flat_props = $this->search->getContext()->getFlattenedProperties();
        $invalid_filters = [];

        $buffer = [];
        while ($position < $token_count) {
            [$type, $value] = $tokens[$position];
            if ($type === Lexer::T_PROPERTY) {
                // If the property isn't in the flattened properties array, the filter should be ignored (not valid)
                if (!isset($flat_props[$value])) {
                    // Not valid. Just fill the buffer and continue to the next token. This will be handled once the value token is reached.
                    $invalid_filters[$value] = Error::UNKNOWN_PROPERTY;
                    $buffer = [
                        'property' => null,
                        'field' => null,
                    ];
                } elseif (isset($flat_props[$value]['x-mapped-from'])) {
                    // Mapped properties cannot be used in RSQL currently since they are calculated after the query is executed
                    $invalid_filters[$value] = Error::MAPPED_PROPERTY;
                    $buffer = [
                        'property' => null,
                        'field' => null,
                    ];
                } else {
                    $buffer = [
                        'property' => $value,
                        'field' => $this->search->getSQLFieldForProperty($value),
                    ];
                }
            } elseif ($type === Lexer::T_OPERATOR) {
                if (!isset($operators[$value])) {
                    if ($buffer['property'] !== null) {
                        $invalid_filters[$buffer['property']] = Error::UNKNOWN_OPERATOR;
                    }
                    $buffer['operator'] = null;
                } else {
                    $buffer['operator'] = $operators[$value]['sql_where_callable'];
                    $buffer['value_expected'] = $operators[$value]['value_expected'];
                }
            } elseif ($type === Lexer::T_VALUE || $type === Lexer::T_UNSPECIFIED_VALUE) {
                if ($buffer['property'] !== null && $buffer['operator'] !== null && $buffer['field'] !== null) {
                    if ($buffer['value_expected'] && $type === Lexer::T_UNSPECIFIED_VALUE) {
                        throw new RSQLException('', sprintf(__('RSQL query is missing a value in filter for property "%1$s"'), $buffer['property']));
                    }
                    // Unquote value if it is quoted
                    if (preg_match('/^".*"$/', $value) || preg_match("/^'.*'$/", $value)) {
                        $value = substr($value, 1, -1);
                    }
                    if (isset($flat_props[$buffer['property']])) {
                        $value = match ($flat_props[$buffer['property']]['type']) {
                            // Boolean values are stored as 0 or 1 in the database, but the user may try using "true" or "false" in the RSQL query
                            Doc\Schema::TYPE_BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                            default => $value,
                        };
                    }
                    $criteria_array = $buffer['operator']($buffer['field'], $value);
                    if (isset($flat_props[$buffer['property']]['computation'])) {
                        $sql_having_string .= $it->analyseCrit($criteria_array);
                    } else {
                        $sql_where_string .= $it->analyseCrit($criteria_array);
                    }
                }
                $buffer = [];
            } elseif ($sql_where_string !== '' && ($type === Lexer::T_AND || $type === Lexer::T_OR)) {
                $sql_where_string .= $type === Lexer::T_AND ? ' AND ' : ' OR ';
            } elseif ($type === Lexer::T_GROUP_OPEN) {
                $sql_where_string .= '(';
            } elseif ($type === Lexer::T_GROUP_CLOSE) {
                $sql_where_string .= ')';
            }
            $position++;
        }

        // Remove any trailing ANDs and ORs (may be multiple in a row)
        $sql_where_string = preg_replace('/(\sAND\s|\sOR\s)*$/', '', $sql_where_string);

        // If the string is empty, return a criteria array that will return all results
        if ($sql_where_string === '') {
            $sql_where_string = '1';
        }
        if ($sql_having_string === '') {
            $sql_having_string = '1';
        }

        return new Result(new QueryExpression($sql_where_string), new QueryExpression($sql_having_string), $invalid_filters);
    }
}
