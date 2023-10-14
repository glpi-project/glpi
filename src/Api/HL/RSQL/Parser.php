<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

use Glpi\Api\HL\Doc;
use Glpi\Api\HL\Search;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;

/**
 * Parses tokens from the RSQL lexer into a SQL criteria array to be used by the {@link \DBmysqlIterator} class.
 */
final class Parser
{
    private Search $search;

    private \DBmysql $db;

    public function __construct(Search $search)
    {
        /** @var \DBmysql $DB */
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
            throw new RSQLException(sprintf(__('Invalid RSQL array: %s'), $str_array));
        }

        // Use CSV parser to handle splitting by comma (not inside quotes) to avoid headache trying to maintain custom regex
        $items = str_getcsv($matches[1]);
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
                    'sql_where_callable' => fn ($a, $b) => [
                        [$this->db::quoteName($a) => $b]
                    ],
                ],
                [
                    'operator' => '!=',
                    'description' => 'not equivalent to',
                    'sql_where_callable' => fn ($a, $b) => [
                        [$this->db::quoteName($a) => ['<>', $b]]
                    ],
                ],
                [
                    'operator' => '=in=',
                    'description' => 'in',
                    'sql_where_callable' => fn ($a, $b) => [
                        [$this->db::quoteName($a) => $this->rsqlGroupToArray($b)]
                    ],
                ],
                [
                    'operator' => '=out=',
                    'description' => 'not in',
                    'sql_where_callable' => fn ($a, $b) => [
                        ['NOT' => [$this->db::quoteName($a) => $this->rsqlGroupToArray($b)]]
                    ],
                ],
                [
                    'operator' => '=lt=',
                    'description' => 'less than',
                    'sql_where_callable' => fn($a, $b) => [
                        [$this->db::quoteName($a) => ['<', $b]]
                    ],
                ],
                [
                    'operator' => '=le=',
                    'description' => 'less than or equal to',
                    'sql_where_callable' => fn ($a, $b) => [
                        [$this->db::quoteName($a) => ['<=', $b]]
                    ],
                ],
                [
                    'operator' => '=gt=',
                    'description' => 'greater than',
                    'sql_where_callable' => fn ($a, $b) => [
                        [$this->db::quoteName($a) => ['>', $b]]
                    ],
                ],
                [
                    'operator' => '=ge=',
                    'description' => 'greater than or equal to',
                    'sql_where_callable' => fn ($a, $b) => [
                        [$this->db::quoteName($a) => ['>=', $b]]
                    ],
                ],
                [
                    'operator' => '=like=',
                    'description' => 'like',
                    'sql_where_callable' => function ($a, $b) {
                        $b = str_replace(['%', '*'], ['_', '%'], $b);
                        return [
                            [
                                $this->db::quoteName($a) => ['LIKE', QueryFunction::cast(new QueryExpression($this->db->quote($b)), 'BINARY')]
                            ]
                        ];
                    },
                ],
                [
                    'operator' => '=ilike=',
                    'description' => 'case insensitive like',
                    'sql_where_callable' => function ($a, $b) {
                        $b = str_replace(['%', '*'], ['_', '%'], $b);
                        return [
                            [$this->db::quoteName($a) => ['LIKE', $b]]
                        ];
                    },
                ],
                [
                    'operator' => '=isnull=',
                    'description' => 'is null',
                    'sql_where_callable' => fn ($a, $b) => [
                        [$this->db::quoteName($a) => null]
                    ],
                ],
                [
                    'operator' => '=notnull=',
                    'description' => 'is not null',
                    'sql_where_callable' => fn ($a, $b) => [
                        ['NOT' => [$this->db::quoteName($a) => null]]
                    ],
                ],
                [
                    // Empty string or null
                    'operator' => '=empty=',
                    'description' => 'is empty',
                    'sql_where_callable' => fn ($a, $b) => [
                        [
                            'OR' => [
                                [$this->db::quoteName($a) => ''],
                                [$this->db::quoteName($a) => null],
                            ]
                        ]
                    ],
                ],
                [
                    'operator' => '=notempty=',
                    'description' => 'is not empty',
                    'sql_where_callable' => fn ($a, $b) => [
                        [
                            'AND' => [
                                [$this->db::quoteName($a) => ['<>', '']],
                                [$this->db::quoteName($a) => ['<>', null]],
                            ]
                        ]
                    ],
                ],
            ];
        }
        return $operators;
    }

    /**
     * @param array $tokens Tokens from the RSQL lexer.
     * @return QueryExpression SQL criteria
     */
    public function parse(array $tokens): QueryExpression
    {
        $it = new \DBmysqlIterator($this->db);
        // We are building a SQL string instead of criteria array because it isn't worth the complexity or overhead.
        // Everything done here should be standard SQL. If there is a platform difference, it should be handled in the callables for each operator.
        // SQL already will process logical separators (AND, OR) in the correct order, so we don't need to worry about that.
        $sql_string = '';

        $position = 0;
        $token_count = count($tokens);
        $operators = $this->getOperators();
        // Loop through operators and set the keys to the operator property
        $operators = array_combine(array_column($operators, 'operator'), $operators);

        $buffer = [];
        while ($position < $token_count) {
            [$type, $value] = $tokens[$position];
            if ($type === Lexer::T_PROPERTY) {
                $buffer = [
                    'field' => $this->search->getSQLFieldForProperty($value),
                ];
            } else if ($type === Lexer::T_OPERATOR) {
                $buffer['operator'] = $operators[$value]['sql_where_callable'];
            } else if ($type === Lexer::T_VALUE) {
                // Unquote value if it is quoted
                if (preg_match('/^".*"$/', $value) || preg_match("/^'.*'$/", $value)) {
                    $value = substr($value, 1, -1);
                }
                $criteria_array = $buffer['operator']($buffer['field'], $value);
                $sql_string .= $it->analyseCrit($criteria_array);
                $buffer = [];
            } else if ($sql_string !== '' && ($type === Lexer::T_AND || $type === Lexer::T_OR)) {
                $sql_string .= $type === Lexer::T_AND ? ' AND ' : ' OR ';
            } else if ($type === Lexer::T_GROUP_OPEN) {
                $sql_string .= '(';
            } else if ($type === Lexer::T_GROUP_CLOSE) {
                $sql_string .= ')';
            }
            $position++;
        }

        return new QueryExpression($sql_string);
    }
}
