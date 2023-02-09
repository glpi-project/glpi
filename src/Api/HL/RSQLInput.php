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

namespace Glpi\Api\HL;

use Glpi\Api\HL\Doc;

class RSQLInput
{
    private string $filter_string;

    public function __construct($filter_string)
    {
        $this->filter_string = $filter_string;
    }

    public function getRSQLFilterString(): string
    {
        return $this->filter_string;
    }

    private function rsqlGroupToArray(string $str_array): array
    {
        // Convert string like (item1, item2,item3) to array
        if (!preg_match('/^\((.*)\)$/', $str_array, $matches)) {
            throw new \InvalidArgumentException('Invalid RSQL group');
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
        global $DB;

        return [
            [
                'operator' => '==',
                'description' => 'equivilent to',
                'sql_where_callable' => fn ($a, $b) => [
                    [$DB::quoteName($a) => $b]
                ],
            ],
            [
                'operator' => '!=',
                'description' => 'not equivilent to',
                'sql_where_callable' => fn ($a, $b) => [
                    [$DB::quoteName($a) => ['<>', $b]]
                ],
            ],
            [
                'operator' => '=in=',
                'description' => 'in',
                'sql_where_callable' => fn ($a, $b) => [
                    [$DB::quoteName($a) => $this->rsqlGroupToArray($b)]
                ],
            ],
            [
                'operator' => '=out=',
                'description' => 'not in',
                'sql_where_callable' => fn ($a, $b) => [
                    ['NOT' => [$DB::quoteName($a) => $this->rsqlGroupToArray($b)]]
                ],
            ],
            [
                'operator' => '=lt=',
                'description' => 'less than',
                'sql_where_callable' => fn($a, $b) => [
                    [$DB::quoteName($a) => ['<', $b]]
                ],
            ],
            [
                'operator' => '=le=',
                'description' => 'less than or equal to',
                'sql_where_callable' => fn ($a, $b) => [
                    [$DB::quoteName($a) => ['<=', $b]]
                ],
            ],
            [
                'operator' => '=gt=',
                'description' => 'greater than',
                'sql_where_callable' => fn ($a, $b) => [
                    [$DB::quoteName($a) => ['>', $b]]
                ],
            ],
            [
                'operator' => '=ge=',
                'description' => 'greater than or equal to',
                'sql_where_callable' => fn ($a, $b) => [
                    [$DB::quoteName($a) => ['>=', $b]]
                ],
            ],
            [
                'operator' => '=like=',
                'description' => 'like',
                'sql_where_callable' => function ($a, $b) use ($DB) {
                    $b = str_replace(['%', '*'], ['_', '%'], $b);
                    return [
                        new \QueryExpression($DB::quoteName($a) . " LIKE CAST(" . $DB->quote($b) . " AS BINARY)")
                    ];
                },
            ],
            [
                'operator' => '=ilike=',
                'description' => 'case insensitive like',
                'sql_where_callable' => function ($a, $b) use ($DB) {
                    $b = str_replace(['%', '*'], ['_', '%'], $b);
                    return [
                        [$DB::quoteName($a) => ['LIKE', $b]]
                    ];
                },
            ],
            [
                'operator' => '=isnull=',
                'description' => 'is null',
                'sql_where_callable' => fn ($a, $b) => [
                    [$DB::quoteName($a) => null]
                ],
            ],
            [
                'operator' => '=notnull=',
                'description' => 'is not null',
                'sql_where_callable' => fn ($a, $b) => [
                    ['NOT' => [$DB::quoteName($a) => null]]
                ],
            ],
            [
                // Empty string or null
                'operator' => '=empty=',
                'description' => 'is empty',
                'sql_where_callable' => fn ($a, $b) => [
                    [
                        'OR' => [
                            [$DB::quoteName($a) => ''],
                            [$DB::quoteName($a) => null],
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
                            [$DB::quoteName($a) => ['<>', '']],
                            [$DB::quoteName($a) => ['<>', null]],
                        ]
                    ]
                ],
            ],
        ];
    }

    /**
     * @return array
     * @phpstan-return {field: string, value: string, callable: callable}[]
     */
    private function getFilters()
    {
        $filters = [];
        $rsql_filters = explode(';', $this->filter_string);
        $operators = $this->getOperators();
        $operator_pattern = '(' . implode('|', array_column($operators, 'operator')) . ')';
        foreach ($rsql_filters as $rsql_filter) {
            // filter pattern is field_name + operator + value where the operator always starts and ends with a symbol and the value may be a quoted string
            [$field, $operator, $value] = preg_split("/$operator_pattern/", $rsql_filter, 2, PREG_SPLIT_DELIM_CAPTURE);
            $operator = array_filter($operators, static fn ($v) => $v['operator'] === $operator);
            $operator = array_shift($operator);
            if ($operator !== null) {
                $filters[] = [
                    'sql_where_callable' => $operator['sql_where_callable'],
                    'field' => $field,
                    'value' => $value
                ];
                continue;
            }
            // Unknown operator
            $filters[] = [
                'field' => $field,
                'value' => $value
            ];
        }
        return $filters;
    }

    private function isNestedField($field)
    {
        return str_contains($field, '.');
    }

    public function getSQLCriteria(array $schema): array
    {
        $filters = $this->getFilters();
        $flattened_properties = Doc\Schema::flattenProperties($schema['properties']);
        $criteria = [];
        foreach ($filters as $filter) {
            $schema_field = $flattened_properties[$filter['field']] ?? null;
            if ($schema_field === null) {
                continue;
            }
            $sql_field = $schema_field['x-field'] ?? $filter['field'];
            $value = $filter['value'];
            $sql_where_callable = $filter['sql_where_callable'];

            if (!str_contains($sql_field, '.')) {
                $sql_field = '_.' . $sql_field;
            }
            $criteria = array_merge($sql_where_callable($sql_field, $value));
        }
        return $criteria;
    }
}
