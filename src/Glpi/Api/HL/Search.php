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

namespace Glpi\Api\HL;

use CommonDBTM;
use Entity;
use ExtraVisibilityCriteria;
use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Api\HL\RSQL\Lexer;
use Glpi\Api\HL\RSQL\Parser;
use Glpi\Api\HL\RSQL\RSQLException;
use Glpi\DBAL\QueryFunction;
use Glpi\Http\JSONResponse;
use Glpi\Http\Response;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryUnion;
use Glpi\Toolbox\ArrayPathAccessor;
use RuntimeException;

/**
 * Internal search engine for the High-Level API
 *
 * In contrast with the regular Search engine, this uses specific schemas which represent items rather than search options.
 * The data returned is not configurable and joined items are returned "whole" as defined by the schema rather than individual fields.
 * Filters are defined using RSQL rather than form data parameters.
 *
 *
 * <hr>
 * SQL special character cheatsheet (hex values):
 * <ul>
 *     <li>0x0: Null. Used as a placeholder in grouped data when there is no result.</li>
 *     <li>0x1D: Group separator. Used to separate grouped data from the DB.</li>
 *     <li>
 *         0x1E: Record separator. Used to separate distinct data within a group.
 *         For example, a parent ID and ID during the fetch for the dehydrated result.
 *         Depending on the nesting level for the property, it there may be multiple parent IDs.
 *         Example: Parent ID 1, Parent ID 2, ID.
 *         In the case of a dehydrated result, the ID is the last item in the group and the rest is used like a path to the relevant object when assembling the result.
 *         This allows for the mapping of multiple children inside an array type (arrays ob objects within arrays of objects).
 *     </li>
 *     <li>0x1F: Unit separator. Used as a replacement for '.' in property names (which would be used as a table/column alias).</li>
 * </ul>
 */
final class Search
{
    private array $schema;
    private array $request_params;
    private array $flattened_properties;
    private array $joins;
    private array $table_schemas;
    private array $tables;
    private bool $union_search_mode;
    private Parser $rsql_parser;
    /**
     * @var array Cache of table names for foreign keys.
     */
    private array $fkey_tables = [];
    private \DBmysql $db_read;

    private function __construct(array $schema, array $request_params)
    {
        $this->schema = $schema;
        $this->request_params = $request_params;
        $this->flattened_properties = Doc\Schema::flattenProperties($schema['properties']);
        $this->joins = Doc\Schema::getJoins($schema['properties']);
        $this->table_schemas = $this->getTables();
        $this->tables = array_keys($this->table_schemas);
        $this->union_search_mode = count($this->tables) > 1;
        $this->rsql_parser = new Parser($this);
        $this->db_read = \DBConnection::getReadConnection();
    }

    /**
     * @throws APIException
     */
    private function validateIterator(\DBmysqlIterator $iterator): void
    {
        if ($iterator->isFailed()) {
            $message = __('An internal error occured while trying to fetch the data.');
            if ($_SESSION['glpi_use_mode'] === \Session::DEBUG_MODE) {
                $message .= ' ' . __('For more information, check the GLPI logs.');
            }
            throw new APIException(
                message: 'A SQL error occured while trying to get data from the database',
                user_message: $message
            );
        }
    }

    public function getFlattenedProperties(): array
    {
        return $this->flattened_properties;
    }

    /**
     * Check if a property is within a join or is itself a join in the case of scalar joined properties.
     * @param string $prop_name The property name
     * @return bool
     */
    private function isJoinedProperty(string $prop_name): bool
    {
        $prop_name = str_replace(chr(0x1F), '.', $prop_name);
        if (isset($this->joins[$prop_name])) {
            return true;
        }
        $prop_parent = substr($prop_name, 0, strrpos($prop_name, '.'));
        return count(array_filter($this->joins, static function ($j_name) use ($prop_parent) {
            return str_starts_with($prop_parent, $j_name);
        }, ARRAY_FILTER_USE_KEY)) > 0;
    }

    public function getSQLFieldForProperty(string $prop_name): string
    {
        $prop = $this->flattened_properties[$prop_name];
        $is_scalar_join = false;
        $is_join = $this->isJoinedProperty($prop_name);
        if (isset($this->joins[$prop_name])) {
            // Scalar property whose value exists in another table
            $is_scalar_join = true;
            $sql_field = $prop['x-field'];
        } else {
            $sql_field = $prop['x-field'] ?? $prop_name;
        }

        if (!$is_join) {
            // Only add the _. prefix if it isn't a join. '_' is the table alias for the main item.
            // Still need to replace all except the last '.' with 0x1F in case it is a nested property.
            $sql_field_parts = explode('.', $sql_field);
            $field_name = array_pop($sql_field_parts);
            $sql_field = trim(implode(chr(0x1F), $sql_field_parts) . '.' . $field_name, '.');
            if (!str_contains($sql_field, chr(0x1F))) {
                $sql_field = '_.' . $sql_field;
            }
        } else {
            if ($is_scalar_join) {
                return str_replace('.', chr(0x1F), $prop_name) . '.' . $sql_field;
            }
            $join_alias = substr($prop_name, 0, strrpos($prop_name, '.'));
            $sql_field = trim(preg_replace('/^' . preg_quote($join_alias, '/') . '/', '', $sql_field), '.');
            $join_alias = str_replace('.', chr(0x1F), trim($join_alias, '.'));
            $sql_field = "{$join_alias}.{$sql_field}";
        }
        return $sql_field;
    }

    /**
     * Get the SQL SELECT criteria required to get the data for the specified property.
     * @param string $prop_name The property name
     * @param bool $distinct_groups Whether to use DISTINCT in GROUP_CONCAT
     * @return QueryExpression|null
     */
    private function getSelectCriteriaForProperty(string $prop_name, bool $distinct_groups = false): ?QueryExpression
    {
        $prop = $this->flattened_properties[$prop_name];
        if ($prop['x-writeonly'] ?? false) {
            // Do not expose write-only fields
            return null;
        }

        // if prop is an array, set the params to the items
        if (array_key_exists('type', $prop) && $prop['type'] === Doc\Schema::TYPE_ARRAY) {
            $prop = $prop['items'];
        }
        if (array_key_exists('type', $prop) && $prop['type'] !== Doc\Schema::TYPE_OBJECT) {
            if (!isset($prop['x-mapper'])) {
                // Do not select fields mapped after the results are retrieved
                $sql_field = $this->getSQLFieldForProperty($prop_name);
                $expression = $this->db_read::quoteName($sql_field);
                if (str_contains($sql_field, '.')) {
                    $join_name = $this->getJoinNameForProperty($prop_name);
                    // Check if the join property is in an array. If so, we need to concat each result.
                    if (array_key_exists($join_name, $this->joins)) {
                        $join_def = $this->joins[$join_name];
                        if (isset($join_def['join_parent'])) {
                            $parent_join = str_replace(chr(0x1F), '.', $join_def['join_parent']);
                            if (array_key_exists($parent_join, $this->joins)) {
                                // Need to concat all parent IDs/primary keys + the property desired
                                $parent_keys = [];
                                $current_join_def = $this->joins[$parent_join];
                                $current_join_parent = $parent_join;
                                while ($current_join_def !== null) {
                                    $parent_keys[] = new QueryExpression('0x1E');
                                    $primary_key = $this->getPrimaryKeyPropertyForJoin($current_join_parent);
                                    // Replace all except last '.' with chr(0x1F) to avoid conflicts with table aliases
                                    $primary_key = implode(chr(0x1F), explode('.', $primary_key, substr_count($primary_key, '.')));


                                    $parent_keys[] = QueryFunction::ifnull(
                                        expression: $primary_key,
                                        value: new QueryExpression('0x0')
                                    );
                                    $current_join_parent = $current_join_def['join_parent'] ?? null;
                                    $current_join_def = $current_join_parent !== null ? ($this->joins[$current_join_parent] ?? null) : null;
                                }
                                $parent_keys = array_reverse($parent_keys);
                                $expression = QueryFunction::groupConcat(
                                    expression: QueryFunction::concat([...$parent_keys, QueryFunction::ifnull($sql_field, new QueryExpression('0x0'))]),
                                    separator: new QueryExpression(chr(0x1D)),
                                );
                            } else {
                                // Probably a nested property
                                $expression = QueryFunction::ifnull($sql_field, new QueryExpression('0x0'));
                                $expression = QueryFunction::groupConcat($expression, new QueryExpression(chr(0x1D)), $distinct_groups);
                            }
                        } else {
                            $expression = QueryFunction::ifnull($sql_field, new QueryExpression('0x0'));
                            $expression = QueryFunction::groupConcat($expression, new QueryExpression(chr(0x1D)), $distinct_groups);
                        }
                    }
                }
                $alias = str_replace('.', chr(0x1F), $prop_name);
                return new QueryExpression($expression, $alias);
            }
        }
        return null;
    }
    /**
     * @return array SELECT criteria for all properties
     * @see Doc\Schema::flattenProperties()
     * @see self::getSelectCriteriaForProperty()
     */
    private function getSelectCriteria(): array
    {
        $select = [];

        foreach ($this->flattened_properties as $prop_name => $prop) {
            $s = $this->getSelectCriteriaForProperty($prop_name);
            if ($s !== null) {
                $select[] = $s;
            }
        }

        return $select;
    }

    /**
     * Get all JOIN clauses for the specified join definition
     * @param string $join_alias The alias/name for the join
     * @param array $join_definition The join definition
     * @return array JOIN clauses in array format used bt {@link \DBmysqlIterator}
     */
    private function getJoins(string $join_alias, array $join_definition): array
    {
        $joins = [];

        $fn_append_join = static function ($join_alias, $join, $parent_type = null) use (&$joins, &$fn_append_join) {
            $join_alias = str_replace('.', chr(0x1F), $join_alias);
            $join_type = ($join['type'] ?? 'LEFT') . ' JOIN';
            if (!isset($joins[$join_type])) {
                $joins[$join_type] = [];
            }
            $join_table = $join['table'] . ' AS ' . $join_alias;
            if (isset($join['ref-join'])) {
                $join_parent = $join['ref-join']['join_parent'] ?? "{$join_alias}_ref";
            } else {
                $join_parent = $join['join_parent'] ?? '_';
            }
            if (isset($join['ref-join'])) {
                $fn_append_join("{$join_alias}_ref", $join['ref-join'], $join['parent_type'] ?? $parent_type);
            }
            $joins[$join_type][$join_table] = [
                'ON' => [
                    $join_alias => $join['field'] ?? 'id',
                    $join_parent => $join['fkey'],
                ],
            ];
            if (isset($join['condition'])) {
                $condition = $join['condition'];
                // recursively inject the join alias into the condition keys in the cases where they don't contain a '.'
                $fn_update_keys = static function ($condition) use (&$fn_update_keys, $join_alias) {
                    $new_condition = [];
                    foreach ($condition as $key => $value) {
                        if (is_array($value)) {
                            $value = $fn_update_keys($value);
                        }
                        $new_condition["{$join_alias}.{$key}"] = $value;
                    }
                    return $new_condition;
                };
                $condition = $fn_update_keys($condition);
                $joins[$join_type][$join_table]['ON'][] = ['AND' => $condition];
            }
        };
        $fn_append_join($join_alias, $join_definition);

        return $joins;
    }

    /**
     * Get the FROM table name and alias for the search, or if in union search mode (multiple top-level item types), the QueryUnion object.
     * @param array $criteria The current search criteria. Used to get the SELECT criteria for the union search.
     * @return QueryUnion|string
     */
    private function getFrom(array $criteria)
    {
        if ($this->union_search_mode) {
            $queries = [];
            foreach ($this->tables as $table) {
                $query = $criteria;
                // Remove join props from the select for now (complex to handle)
                $query['SELECT'] = array_filter($query['SELECT'], function ($select) {
                    $select_str = (string) $select;
                    return str_starts_with($select_str, $this->db_read::quoteName('_.id'));
                });
                //Inject a field for the schema name as the first select
                $schema_name = $this->table_schemas[$table];
                $itemtype_field = new QueryExpression($this->db_read::quoteValue($schema_name), '_itemtype');
                array_unshift($query['SELECT'], $itemtype_field);

                $query['FROM'] = $table . ' AS _';
                unset($query['START'], $query['LIMIT']);
                $queries[] = $query;
            }
            return new QueryUnion($queries, false, '_');
        }

        return $this->tables[0] . ' AS _';
    }

    /**
     * Build the search SQL criteria
     * @return array|array[]
     * @throws RSQLException
     */
    private function getSearchCriteria(): array
    {
        // Handle fields to return
        $criteria = [
            'SELECT' => $this->getSelectCriteria(),
        ];

        // Handle joins
        foreach ($this->joins as $join_alias => $join_definition) {
            $join_clauses = $this->getJoins($join_alias, $join_definition);
            foreach ($join_clauses as $join_type => $join_tables) {
                if (!isset($criteria[$join_type])) {
                    $criteria[$join_type] = [];
                }
                $criteria[$join_type] = array_merge($criteria[$join_type], $join_tables);
            }
        }

        // Handle RSQL filter
        if (isset($this->request_params['filter']) && !empty($this->request_params['filter'])) {
            $criteria['WHERE'] = [$this->rsql_parser->parse(Lexer::tokenize($this->request_params['filter']))];
        }

        // Handle entity and other visibility restrictions
        $entity_restrict = [];
        if (!$this->union_search_mode) {
            $itemtype = $this->schema['x-itemtype'];
            /** @var CommonDBTM $item */
            $item = new $itemtype();
            if ($item instanceof ExtraVisibilityCriteria) {
                $main_table = $item::getTable();
                $visibility_restrict = $item::getVisibilityCriteria();
                $fn_update_keys = static function ($restrict) use (&$fn_update_keys, $main_table) {
                    $new_restrict = [];
                    foreach ($restrict as $key => $value) {
                        $new_key = str_replace($main_table, '_', $key);
                        if (is_array($value)) {
                            $value = $fn_update_keys($value);
                        }
                        $new_restrict[$new_key] = $value;
                    }
                    return $new_restrict;
                };
                $visibility_restrict = $fn_update_keys($visibility_restrict);
                $entity_restrict = $visibility_restrict['WHERE'] ?? [];

                $join_types = ['LEFT JOIN', 'INNER JOIN', 'RIGHT JOIN'];
                foreach ($join_types as $join_type) {
                    if (empty($visibility_restrict[$join_type])) {
                        continue;
                    }
                    if (!isset($criteria[$join_type])) {
                        $criteria[$join_type] = [];
                    }
                    $criteria[$join_type] = array_merge($criteria[$join_type], $visibility_restrict[$join_type]);
                }
            } else if ($item->isEntityAssign()) {
                $entity_restrict = getEntitiesRestrictCriteria('_');
            }
            if ($item instanceof Entity) {
                $entity_restrict = [
                    'OR' => [
                        [$entity_restrict],
                        [getEntitiesRestrictCriteria('_', 'id')]
                    ]
                ];
                // if $entity_restrict has nothing except empty values as leafs, replace with a simple empty array.
                // Expected in root entity when recursive. Having empty arrays will fail the query (thinks it is empty IN).
                $fn_is_empty = static function ($where) use (&$fn_is_empty) {
                    foreach ($where as $where_field => $where_value) {
                        if (is_array($where_value)) {
                            if (!$fn_is_empty($where_value)) {
                                return false;
                            }
                        } else if (!empty($where_value)) {
                            return false;
                        }
                    }
                    return true;
                };
                if ($fn_is_empty($entity_restrict)) {
                    $entity_restrict = [];
                }
            }
        } else {
            //TODO What if some subtypes are entity assign and some are not?
            $entity_restrict = [
                getEntitiesRestrictCriteria('_'),
            ];
        }
        if (!empty($entity_restrict) && $entity_restrict !== [0 => []]) {
            $criteria['WHERE'][] = ['AND' => $entity_restrict];
        }

        // Handle pagination
        if (isset($this->request_params['start']) && is_numeric($this->request_params['start'])) {
            $criteria['START'] = (int) $this->request_params['start'];
        }
        if (isset($this->request_params['limit']) && is_numeric($this->request_params['limit'])) {
            $criteria['LIMIT'] = (int) $this->request_params['limit'];
        }

        // Handle sorting
        if (isset($this->request_params['sort'])) {
            $sorts = array_map(static fn ($s) => trim($s), explode(',', $this->request_params['sort']));
            $orderby = [];
            foreach ($sorts as $s) {
                if ($s === '') {
                    // Ignore empty sorts. probably a trailing comma.
                    continue;
                }
                $sort_parts = explode(':', $s);
                $property = $sort_parts[0];
                $direction = strtoupper($sort_parts[1] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
                // Verify the property is valid
                if (!isset($this->flattened_properties[$property])) {
                    throw new APIException('Invalid property for sorting: ' . $property, 'Invalid property for sorting: ' . $property);
                }
                $sql_field = $this->getSQLFieldForProperty($property);
                $orderby[] = "{$sql_field} {$direction}";
            }
            $criteria['ORDERBY'] = $orderby;
        }

        return $criteria;
    }

    /**
     * @return array Primary tables to search in. For searching a specific itemtype, there will be only one table.
     * For searching a collection of itemtypes (like the Global Assets schema), there will be multiple tables.
     * @phpstan-return array<string, array>
     */
    private function getTables(): array
    {
        $tables_schemas = [];
        if (isset($this->schema['x-table'])) {
            $tables_schemas[$this->schema['x-table']] = $this->schema;
        } else if (isset($this->schema['x-itemtype'])) {
            if (is_subclass_of($this->schema['x-itemtype'], CommonDBTM::class)) {
                $t = $this->schema['x-itemtype']::getTable();
                $tables_schemas[$t] = $this->schema;
            } else {
                throw new RuntimeException('Invalid itemtype');
            }
        } else if (isset($this->schema['x-subtypes'])) {
            foreach ($this->schema['x-subtypes'] as $subtype_info) {
                if (is_subclass_of($subtype_info['itemtype'], CommonDBTM::class)) {
                    $t = $subtype_info['itemtype']::getTable();
                    $tables_schemas[$t] = $subtype_info['schema_name'];
                } else {
                    throw new RuntimeException('Invalid itemtype');
                }
            }
        } else {
            throw new RuntimeException('Cannot search using a schema without an x-table or an x-itemtype');
        }
        return $tables_schemas;
    }

    /**
     * If the schema has a read right condition, add it to the criteria.
     * @param array $criteria The current criteria. Will be modified in-place.
     * @return void
     */
    private function addReadRestrictCriteria(array &$criteria): void
    {
        $read_right_criteria = $this->schema['x-rights-conditions']['read'] ?? [];
        if (is_callable($read_right_criteria)) {
            $read_right_criteria = $read_right_criteria();
        }
        if (!empty($read_right_criteria)) {
            $join_types = ['LEFT JOIN', 'INNER JOIN', 'RIGHT JOIN'];
            foreach ($join_types as $join_type) {
                if (isset($read_right_criteria[$join_type])) {
                    foreach ($read_right_criteria[$join_type] as $join_table => $join_clauses) {
                        if (!isset($criteria[$join_type][$join_table])) {
                            $criteria[$join_type][$join_table] = $join_clauses;
                        }
                    }
                }
            }
            if (isset($read_right_criteria['WHERE'])) {
                if (!isset($criteria['WHERE'])) {
                    $criteria['WHERE'] = [];
                }
                $criteria['WHERE'][] = $read_right_criteria['WHERE'];
            }
        }
    }

    /**
     * Check if the criteria has a filter on joined data.
     * @param array $where The WHERE criteria
     * @return bool
     */
    private function criteriaHasJoinFilter(array $where): bool
    {
        if (empty($where)) {
            return false;
        }

        foreach ($where as $where_field => $where_value) {
            if (is_array($where_value) && $this->criteriaHasJoinFilter($where_value)) {
                return true;
            }
            foreach ($this->joins as $join_alias => $join_definition) {
                if (str_starts_with((string)$where_field, $this->db_read::quoteName($join_alias) . '.')) {
                    return true;
                }
            }
        }
        return false;
    }

    private function getPrimaryKeyPropertyForJoin(string $join): string
    {
        // If this is a scalar property join, simply return the property named the same as the join
        if (isset($this->flattened_properties[$join])) {
            return $join;
        }
        $pkey_field = 'field';
        $join_params = $this->joins[$join]['ref-join'] ?? $this->joins[$join];
        if (isset($this->joins[$join]['ref-join'])) {
            $pkey_field = 'fkey';
        }
        if (isset($join_params['primary-property'])) {
            $pkey_field = 'primary-property';
        }
        $primary_key = $join_params[$pkey_field];
        $prop_matches = array_filter(
            $this->flattened_properties,
            static function ($prop_name) use ($primary_key, $join) {
                // Filter matches for the primary key
                return preg_match('/^' . preg_quote($join, '/') . '\.' . preg_quote($primary_key, '/') . '$/', $prop_name);
            },
            ARRAY_FILTER_USE_KEY
        );

        if (count($prop_matches)) {
            return array_key_first($prop_matches);
        }
        throw new RuntimeException("Cannot find primary key property for join $join");
    }

    private function getJoinNameForProperty(string $prop_name): string
    {
        if (array_key_exists(str_replace(chr(0x1F), '.', $prop_name), $this->joins)) {
            $join_name = str_replace(chr(0x1F), '.', $prop_name);
        } else {
            $join_name = substr($prop_name, 0, strrpos($prop_name, chr(0x1F)));
            $join_name = str_replace(chr(0x1F), '.', $join_name);
        }
        return $join_name;
    }

    /**
     * @return array Matching records in the format Itemtype => IDs
     * @phpstan-return array<string, int[]>
     * @throws RSQLException|APIException
     */
    private function getMatchingRecords($ignore_pagination = false): array
    {
        $records = [];

        $criteria = [
            'SELECT' => [],
        ];

        $criteria = array_merge_recursive($criteria, $this->getSearchCriteria());

        if ($ignore_pagination) {
            unset($criteria['START'], $criteria['LIMIT']);
        }

        $criteria['FROM'] = $this->getFrom($criteria);

        if ($this->union_search_mode) {
            unset($criteria['LEFT JOIN'], $criteria['INNER JOIN'], $criteria['RIGHT JOIN'], $criteria['WHERE']);
        } else {
            $this->addReadRestrictCriteria($criteria);
        }

        $criteria['SELECT'] = ['_.id'];
        if ($this->union_search_mode) {
            $criteria['SELECT'][] = '_itemtype';
            $criteria['GROUPBY'] = ['_itemtype', '_.id'];
        } else {
            foreach ($this->joins as $join_alias => $join) {
                $s = $this->getSelectCriteriaForProperty($this->getPrimaryKeyPropertyForJoin($join_alias), true);
                if ($s !== null) {
                    $criteria['SELECT'][] = $s;
                }
            }
            $criteria['GROUPBY'] = ['_.id'];
        }

        // request just to get the ids/union itemtypes
        $iterator = $this->db_read->request($criteria);
        $this->validateIterator($iterator);

        if ($this->union_search_mode) {
            // group by _itemtype
            foreach ($iterator as $row) {
                if (!isset($records[$row['_itemtype']])) {
                    $records[$row['_itemtype']] = [];
                }
                $records[$row['_itemtype']][$row['id']] = $row;
            }
        } else {
            foreach ($iterator as $row) {
                if (!isset($records[$this->schema['x-itemtype']])) {
                    $records[$this->schema['x-itemtype']] = [];
                }
                $records[$this->schema['x-itemtype']][$row['id']] = $row;
            }
        }

        if ($this->criteriaHasJoinFilter($criteria['WHERE'] ?? [])) {
            // There was a filter on joined data, so the IDs we got are only the ones that match the filter.
            // We want to get all related items in the result and not just the ones that match the filter.
            $criteria['WHERE'] = [];
            if ($this->union_search_mode) {
                foreach ($records as $schema_name => $type_records) {
                    if (!isset($criteria['WHERE']['OR'])) {
                        $criteria['WHERE']['OR'] = [];
                    }
                    $criteria['WHERE']['OR'][] = [
                        'id' => array_column($type_records, 'id'),
                        '_itemtype' => $schema_name,
                    ];
                }
            } else {
                $type_records = $records[$this->schema['x-itemtype']];
                $criteria['WHERE'] = [
                    '_.id' => array_column($type_records, 'id')
                ];
            }
            $iterator = $this->db_read->request($criteria);
            $this->validateIterator($iterator);
            foreach ($iterator as $data) {
                $itemtype = $this->union_search_mode ? $data['_itemtype'] : $this->schema['x-itemtype'];
                if (!isset($records[$itemtype])) {
                    $records[$itemtype] = [];
                }
                $records[$itemtype][$data['id']] = $data;
            }
        }

        return $records;
    }

    /**
     * Resolve the DB table for the given foreign key and schema.
     * @param string $fkey The foreign key name (In the fully qualified property name format, not the SQL field name)
     * @param string $schema_name The schema name
     * @return string The DB table name
     */
    private function getTableForFKey(string $fkey, string $schema_name): string
    {
        $normalized_fkey = str_replace(chr(0x1F), '.', $fkey);
        if (isset($this->joins[$normalized_fkey])) {
            // Scalar property whose value exists in another table
            return $this->joins[$normalized_fkey]['table'];
        }
        if (!isset($this->fkey_tables[$fkey])) {
            if ($fkey === 'id') {
                // This is a primary key on a main item
                if ($this->union_search_mode) {
                    $subtype = array_filter($this->schema['x-subtypes'], static function ($subtype) use ($schema_name) {
                        return $subtype['schema_name'] === $schema_name;
                    });
                    if (count($subtype) !== 1) {
                        throw new RuntimeException('Cannot find subtype for schema ' . $schema_name);
                    }
                    $subtype = reset($subtype);
                    $this->fkey_tables[$fkey] = $subtype['itemtype']::getTable();
                } else {
                    $this->fkey_tables[$fkey] = self::getTableFromSchema($this->schema);
                }
            } else {
                // This is a foreign key on a joined item
                foreach ($this->joins as $join_alias => $join) {
                    if ($fkey === str_replace('.', chr(0x1F), $join_alias) . chr(0x1F) . 'id') {
                        // Found the related join definition. Use the table from that.
                        $this->fkey_tables[$fkey] = $join['table'];
                        break;
                    }
                }
            }
            if (empty($this->fkey_tables[$fkey])) {
                // We still don't have a table. Throw an exception.
                throw new RuntimeException('Cannot find table for property ' . $fkey);
            }
        }
        return $this->fkey_tables[$fkey];
    }

    private function getItemRecordPath(string $join_name, mixed $id, array $hydrated_record): array
    {
        //if the id contains record separators, all but the last one are the parent IDs and need interlaced with the join name to get the actual path.
        if (str_contains($id, chr(0x1E))) {
            $ids_path = explode(chr(0x1E), $id);
            $id = array_pop($ids_path);
            if (empty($id) || $id === "\0") {
                return [$join_name, $id];
            }
            $join_path_parts = explode('.', $join_name);

            $new_path = [];
            // Add placeholder for actual ID. Ensures the ids in the path stop before the last path part.
            $ids_path[] = '';
            // Pad start of ids path array with empty values to match the number of join path parts
            $ids_path = array_pad($ids_path, -count($join_path_parts), '');
            while (count($ids_path) > 0) {
                $new_path[] = array_shift($join_path_parts);
                $current_path = implode('.', $new_path);
                $next_id = array_shift($ids_path);
                // if current path points to an object, we don't need to add the ID to the path
                $path_without_ids = implode('.', array_filter(explode('.', $current_path), static fn ($p) => !is_numeric($p)));
                if (!isset($this->joins[$path_without_ids]['parent_type']) && $this->joins[$path_without_ids]['parent_type'] === Doc\Schema::TYPE_OBJECT) {
                    if (!empty($next_id) && preg_match('/\.\d+/', $current_path)) {
                        $items = ArrayPathAccessor::getElementByArrayPath($hydrated_record, $current_path);
                        // Remove numeric id parts from the path to get the join name
                        $current_join = implode('.', array_filter(explode('.', $current_path), static fn($p) => !is_numeric($p)));
                        $primary_prop = $this->getPrimaryKeyPropertyForJoin($current_join);
                        // We just need the last part of the property name (not the full path)
                        $primary_prop = substr($primary_prop, strrpos($primary_prop, '.') + 1);
                        if ($items !== null) {
                            foreach ($items as $item_index => $item) {
                                if (isset($item[$primary_prop])) {
                                    $next_id = $item_index;
                                }
                            }
                        }
                    }
                    $new_path[] = $next_id;
                }
            }
            $new_path = array_filter($new_path, static fn ($p) => !empty($p));
            $join_prop_path = implode('.', $new_path);
        }
        return [$join_prop_path ?? $join_name, $id];
    }

    /**
     * Assemble the hydrated object
     * @param array $dehydrated_row The dehydrated result (just the primary/foreign keys)
     * @param string $schema_name The name of the schema of the object we are building
     * @param array $fetched_records The records fetched from the DB
     * @return array
     */
    private function assembleHydratedRecords(array $dehydrated_row, string $schema_name, array $fetched_records): array
    {
        $dehydrated_refs = array_keys($dehydrated_row);
        $hydrated_record = [];
        foreach ($dehydrated_refs as $dehydrated_ref) {
            if (str_starts_with($dehydrated_ref, '_')) {
                $dehydrated_ref = 'id';
            }
            $table = $this->getTableForFKey($dehydrated_ref, $schema_name);
            $needed_ids = explode(chr(0x1D), $dehydrated_row[$dehydrated_ref] ?? '');
            $needed_ids = array_filter($needed_ids, static function ($id) {
                return $id !== chr(0x0);
            });
            if ($dehydrated_ref === 'id') {
                // Add the main item fields
                $main_record = $fetched_records[$table][$needed_ids[0]];
                $hydrated_record = [];
                foreach ($main_record as $k => $v) {
                    $k_path = str_replace(chr(0x1F), '.', $k);
                    ArrayPathAccessor::setElementByArrayPath($hydrated_record, $k_path, $v);
                }
            } else {
                // Add the joined item fields
                $join_name = $this->getJoinNameForProperty($dehydrated_ref);
                if (isset($this->flattened_properties[$join_name])) {
                    continue;
                }
                if (!ArrayPathAccessor::hasElementByArrayPath($hydrated_record, $join_name)) {
                    ArrayPathAccessor::setElementByArrayPath($hydrated_record, $join_name, []);
                }
                foreach ($needed_ids as $id) {
                    [$join_prop_path, $id] = $this->getItemRecordPath($join_name, $id, $hydrated_record);
                    if ($id === '' || $id === "\0") {
                        continue;
                    }
                    $matched_record = $fetched_records[$table][(int) $id] ?? null;

                    if (isset($this->joins[$join_name]['parent_type']) && $this->joins[$join_name]['parent_type'] === Doc\Schema::TYPE_OBJECT) {
                        ArrayPathAccessor::setElementByArrayPath($hydrated_record, $join_prop_path, $matched_record);
                    } else {
                        if ($matched_record !== null) {
                            $current = ArrayPathAccessor::getElementByArrayPath($hydrated_record, $join_prop_path);
                            $current[$id] = $matched_record;
                            ArrayPathAccessor::setElementByArrayPath($hydrated_record, $join_prop_path, $current);
                        }
                    }
                }
            }
        }
        // Add any scalar joined properties that may have been fetched with the dehydrated row
        // Do this last as some scalar joined properties may be nested and have other data added after the main record was built
        foreach ($dehydrated_row as $k => $v) {
            $normalized_k = str_replace(chr(0x1F), '.', $k);
            if (isset($this->joins[$normalized_k]) && !ArrayPathAccessor::hasElementByArrayPath($hydrated_record, $normalized_k)) {
                $v = explode(chr(0x1E), $v);
                $v = end($v);
                ArrayPathAccessor::setElementByArrayPath($hydrated_record, $normalized_k, $v);
            }
        }
        $this->fixupAssembledRecord($hydrated_record);
        return $hydrated_record;
    }

    /**
     * Fix-up the assembled result record to ensure it matches the expected schema.
     *
     * Steps taken include:
     * - Removing the keys for array typed data. When assembling the record initially, the keys are the IDs of the joined records to allow for easy lookup.
     * - Changing empty array values for object typed data to null. The value was initialized when assembling the record, but we don't know until the end of the process if any data was added to the object.
     *   If we don't do this, these show as arrays when json encoded.
     * @param array $record
     * @return void
     */
    private function fixupAssembledRecord(array &$record): void
    {
        // Fix keys for array properties. Currently, the keys are probably the IDs of the joined records. They should be the index of the record in the array.
        $array_joins = array_filter($this->joins, static function ($v) {
            return isset($v['parent_type']) && $v['parent_type'] === Doc\Schema::TYPE_ARRAY;
        }, ARRAY_FILTER_USE_BOTH);
        foreach ($array_joins as $name => $join_def) {
            // Get all paths in the array that match the join name. Paths may or may not have number parts between the parts of the join name (separated by '.')
            $pattern = str_replace('.', '\.(?:\d+\.)?', $name);
            $paths = ArrayPathAccessor::getArrayPaths($record, "/^{$pattern}$/");
            foreach ($paths as $path) {
                $join_prop = ArrayPathAccessor::getElementByArrayPath($record, $path);
                if ($join_prop === null) {
                    continue;
                }
                $join_prop = array_values($join_prop);
                // Remove any empty values
                $join_prop = array_filter($join_prop, static fn ($v) => !empty($v));
                ArrayPathAccessor::setElementByArrayPath($record, $path, $join_prop);
            }
        }

        // Fix empty array values for objects by replacing them with null
        $obj_joins = array_filter($this->joins, function ($v, $k) {
            return isset($v['parent_type']) && $v['parent_type'] === Doc\Schema::TYPE_OBJECT && !isset($this->flattened_properties[$k]);
        }, ARRAY_FILTER_USE_BOTH);
        foreach ($obj_joins as $name => $join_def) {
            // Get all paths in the array that match the join name. Paths may or may not have number parts between the parts of the join name (separated by '.')
            $pattern = str_replace('.', '\.(?:\d+\.)?', $name);
            $paths = ArrayPathAccessor::getArrayPaths($record, "/^{$pattern}$/");
            foreach ($paths as $path) {
                $join_prop = ArrayPathAccessor::getElementByArrayPath($record, $path);
                if ($join_prop === null) {
                    continue;
                }
                $join_prop = array_filter($join_prop, static fn ($v) => !empty($v));
                if (empty($join_prop)) {
                    ArrayPathAccessor::setElementByArrayPath($record, $path, null);
                }
            }
        }
    }

    private function hydrateRecords(array $records): array
    {
        $hydrated_records = [];
        /** All data retrieved by table */
        $fetched_records = [];

        foreach ($records as $schema_name => $dehydrated_records) {
            // Clear lookup cache between schemas just in case.
            $this->fkey_tables = [];
            foreach ($dehydrated_records as $row) {
                unset($row['_itemtype']);
                // Make sure we have all the needed data
                foreach ($row as $fkey => $record_ids) {
                    $table = $this->getTableForFKey($fkey, $schema_name);
                    $itemtype = getItemTypeForTable($table);

                    if ($record_ids === null || $record_ids === '' || $record_ids === "\0") {
                        continue;
                    }
                    // Find which IDs we need to fetch. We will avoid fetching records multiple times.
                    $ids_to_fetch = array_map(static function (string|int $id) {
                        // If an ID contains a record separator, it includes a path of IDs to identify the parent record.
                        // The item ID itself is the last one.
                        if (str_contains($id, chr(0x1E))) {
                            $id = explode(chr(0x1E), (string) $id);
                            $id = end($id);
                        }
                        return (int) $id;
                    }, explode(chr(0x1D), $record_ids));
                    $ids_to_fetch = array_diff($ids_to_fetch, array_keys($fetched_records[$table] ?? []));

                    if (empty($ids_to_fetch)) {
                        // Every record needed for this row has already been fetched.
                        continue;
                    }

                    $criteria = [
                        'SELECT' => [],
                    ];
                    $id_field = 'id';
                    $join_name = '_';

                    if ($fkey === 'id') {
                        $props_to_use = array_filter($this->flattened_properties, function ($prop_params, $prop_name) {
                            if (isset($this->joins[$prop_name])) {
                                /** Scalar joined properties are fetched directly during {@link self::getMatchingRecords()} */
                                return false;
                            }
                            $prop_field = $prop_params['x-field'] ?? $prop_name;
                            $mapped_from_other = isset($prop_params['x-mapped-from']) && $prop_params['x-mapped-from'] !== $prop_field;
                            // We aren't handling joins or mapped fields here
                            $prop_name = str_replace(chr(0x1F), '.', $prop_name);
                            $prop_parent = substr($prop_name, 0, strrpos($prop_name, '.'));
                            $is_join = count(array_filter($this->joins, static function ($j_name) use ($prop_parent) {
                                return str_starts_with($prop_parent, $j_name);
                            }, ARRAY_FILTER_USE_KEY)) > 0;
                            return !$is_join && !$mapped_from_other;
                        }, ARRAY_FILTER_USE_BOTH);
                        $criteria['FROM'] = "$table AS " . $this->db_read::quoteName('_');
                        if ($this->union_search_mode) {
                            $criteria['SELECT'][] = new QueryExpression($this->db_read::quoteValue($schema_name), '_itemtype');
                        }
                    } else {
                        $join_name = $this->getJoinNameForProperty($fkey);
                        $props_to_use = array_filter($this->flattened_properties, function ($prop_name) use ($join_name) {
                            if (isset($this->joins[$prop_name])) {
                                /** Scalar joined properties are fetched directly during {@link self::getMatchingRecords()} */
                                return false;
                            }
                            $prop_parent = substr($prop_name, 0, strrpos($prop_name, '.'));
                            return $prop_parent === $join_name;
                        }, ARRAY_FILTER_USE_KEY);

                        $criteria['FROM'] = "$table AS " . $this->db_read::quoteName(str_replace('.', chr(0x1F), $join_name));
                        $id_field = str_replace('.', chr(0x1F), $join_name) . '.id';
                    }
                    $criteria['WHERE'] = [$id_field => $ids_to_fetch];
                    foreach ($props_to_use as $prop_name => $prop) {
                        if ($prop['x-writeonly'] ?? false) {
                            // Property can only be written to, not read. We shouldn't be getting it here.
                            continue;
                        }
                        $sql_field = $this->getSQLFieldForProperty($prop_name);
                        $field_parts = explode('.', $sql_field);
                        $field_only = end($field_parts);
                        // Handle translatable fields
                        $translatable = \Session::haveTranslations($itemtype, $field_only);
                        $trans_alias = "{$join_name}__{$field_only}__trans";
                        $trans_alias = hash('xxh3', $trans_alias);
                        if ($translatable) {
                            if (!isset($criteria['LEFT JOIN'])) {
                                $criteria['LEFT JOIN'] = [];
                            }
                            $criteria['LEFT JOIN']["glpi_dropdowntranslations AS {$trans_alias}"] = [
                                'ON' => [
                                    $join_name => 'id',
                                    $trans_alias => 'items_id', [
                                        'AND' => [
                                            "$trans_alias.language" => \Session::getLanguage(),
                                            "$trans_alias.itemtype" => $itemtype,
                                            "$trans_alias.field" => $field_only,
                                        ]
                                    ]
                                ]
                            ];
                        }
                        // alias should be prop name relative to current join
                        $alias = $prop_name;
                        if ($join_name !== '_') {
                            $alias = preg_replace('/^' . preg_quote($join_name, '/') . '\./', '', $alias);
                        }
                        $alias = str_replace('.', chr(0x1F), $alias);
                        if ($translatable) {
                            // Try to use the translated value, but fall back to the default value if there is no translation
                            $criteria['SELECT'][] = QueryFunction::ifnull(
                                expression: "{$trans_alias}.value",
                                value: $sql_field,
                                alias: $alias
                            );
                        } else {
                            $criteria['SELECT'][] = $sql_field . ' AS ' . $alias;
                        }
                    }

                    // Fetch the data for the current dehydrated record
                    $it = $this->db_read->request($criteria);
                    $this->validateIterator($it);
                    foreach ($it as $data) {
                        $cleaned_data = [];
                        foreach ($data as $k => $v) {
                            ArrayPathAccessor::setElementByArrayPath($cleaned_data, $k, $v);
                        }
                        $fkey_local_name = trim(strrchr($fkey, chr(0x1F)) ?: $fkey, chr(0x1F));
                        $fetched_records[$table][$data[$fkey_local_name]] = $cleaned_data;
                    }
                }

                $hydrated_records[] = $this->assembleHydratedRecords($row, $schema_name, $fetched_records);
            }
        }
        return $hydrated_records;
    }

    /**
     * Fetch results for the given schema and request parameters
     * @param array $schema
     * @param array $request_params
     * @return array The search results
     * @phpstan-return array{results: array, start: int, limit: int, total: int}
     * @throws RSQLException|APIException
     */
    private static function getSearchResultsBySchema(array $schema, array $request_params): array
    {
        // Schema must be an object type
        if ($schema['type'] !== Doc\Schema::TYPE_OBJECT) {
            throw new \RuntimeException('Schema must be an object type');
        }
        // Initialize a new search
        $search = new self($schema, $request_params);
        $ids = $search->getMatchingRecords();
        $results = $search->hydrateRecords($ids);

        $mapped_props = array_filter($search->getFlattenedProperties(), static function ($prop) {
            return isset($prop['x-mapper']);
        });
        foreach ($results as &$result) {
            // Handle mapped fields
            foreach ($mapped_props as $mapped_prop_name => $mapped_prop) {
                if (ArrayPathAccessor::hasElementByArrayPath($result, $mapped_prop['x-mapped-from'])) {
                    ArrayPathAccessor::setElementByArrayPath(
                        array: $result,
                        path: $mapped_prop_name,
                        value: $mapped_prop['x-mapper'](ArrayPathAccessor::getElementByArrayPath($result, $mapped_prop['x-mapped-from']))
                    );
                }
            }
            $result = Doc\Schema::fromArray($schema)->castProperties($result);
        }
        unset($result);

        // Count the total number of results with the same criteria, but without the offset and limit
        $criteria = $search->getSearchCriteria();
        // We only need the total count, so we don't need to hydrate the records
        $all_records = $search->getMatchingRecords(true);
        $total_count = 0;
        foreach ($all_records as $schema_name => $records) {
            $total_count += count($records);
        }

        return [
            'results' => array_values($results),
            'start' => $criteria['START'] ?? 0,
            'limit' => $criteria['LIMIT'] ?? count($results),
            'total' => $total_count,
        ];
    }

    /**
     * Search items using the given schema and request parameters.
     * Public entry point for the internal {@link self::getSearchResultsBySchema()} method.
     * @param array $schema
     * @param array $request_params
     * @return Response
     */
    public static function searchBySchema(array $schema, array $request_params): Response
    {
        $itemtype = $schema['x-itemtype'] ?? null;
        // No item-level checks done here. They are handled when generating the SQL using the x-rights-condtions schema property
        if (($itemtype !== null) && !$itemtype::canView()) {
            return AbstractController::getCRUDErrorResponse(AbstractController::CRUD_ACTION_LIST);
        }
        if (isset($schema['x-subtypes'])) {
            // For this case, we need to filter out the schemas that the user doesn't have read rights on
            $schemas = $schema['x-subtypes'];
            $schemas = array_filter($schemas, static function ($v) {
                $itemtype = $v['itemtype'];
                if (class_exists($itemtype) && is_subclass_of($itemtype, CommonDBTM::class)) {
                    return $itemtype::canView();
                }
                return false;
            });
            $schema['x-subtypes'] = $schemas;
            if (empty($schema['x-subtypes'])) {
                // No right on any subtypes. Could be useful to return an access denied error here instead of an empty list
                return AbstractController::getAccessDeniedErrorResponse();
            }
        }
        try {
            $results = self::getSearchResultsBySchema($schema, $request_params);
        } catch (RSQLException $e) {
            return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_INVALID_PARAMETER, $e->getMessage()), 400);
        } catch (APIException $e) {
            return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_GENERIC, $e->getUserMessage()));
        } catch (\Throwable $e) {
            $message = (new APIException())->getUserMessage();
            return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_GENERIC, $message));
        }
        $has_more = $results['start'] + $results['limit'] < $results['total'];
        $end = max(0, ($results['start'] + $results['limit'] - 1));
        if ($end > $results['total']) {
            $end = $results['total'] - 1;
        }
        return new JSONResponse($results['results'], $has_more ? 206 : 200, [
            'Content-Range' => $results['start'] . '-' . $end . '/' . $results['total'],
        ]);
    }

    /**
     * Map the request parameters to the format required for the GLPI add/update methods.
     * Only top-level properties are mapped.
     * Nested properties which would represent relations are not supported.
     * Creating/updating relations should be done using the appropriate endpoints.
     * @param array $schema
     * @param array $request_params
     * @return array
     */
    public static function getInputParamsBySchema(array $schema, array $request_params): array
    {
        $params = [];
        $flattened_properties = Doc\Schema::flattenProperties($schema['properties']);
        //Get top level properties (do not contain "." in the key)
        $top_level_properties = array_filter($flattened_properties, static function ($k) {
            $is_dropdown_identifier = preg_match('/^(\w+)\.id$/', $k);
            return $is_dropdown_identifier || !str_contains($k, '.');
        }, ARRAY_FILTER_USE_KEY);
        foreach ($top_level_properties as $prop_name => $prop) {
            if (str_contains($prop_name, '.')) {
                // This is a dropdown identifier, we need to get the id from the request
                $prop_name = strstr($prop_name, '.', true);
                $prop = $schema['properties'][$prop_name];
            } else {
                if ($prop['x-readonly'] ?? false) {
                    // Ignore properties marked as read-only
                    continue;
                }
            }

            // Field resolution priority: x-field -> x-join.fkey -> property name
            if (isset($prop['x-field'])) {
                $internal_name = $prop['x-field'];
            } else if (isset($prop['x-join']['fkey'])) {
                $internal_name = $prop['x-join']['fkey'] ?? $prop_name;
            } else {
                $internal_name = $prop_name;
            }
            if (isset($request_params[$prop_name])) {
                $params[$internal_name] = $request_params[$prop_name];
            }
        }
        return $params;
    }

    /**
     * Get the related itemtype for the given schema.
     * @param array $schema
     * @return class-string<CommonDBTM>
     */
    private static function getItemtypeFromSchema(array $schema): string
    {
        $itemtype = $schema['x-itemtype'] ?? ($schema['x-table'] ? getItemTypeForTable($schema['x-table']) : null);
        if ($itemtype === null) {
            throw new \RuntimeException('Schema has no x-table or x-itemtype');
        }
        if (!is_subclass_of($itemtype, CommonDBTM::class)) {
            throw new \RuntimeException('Invalid itemtype');
        }
        return $itemtype;
    }

    /**
     * Get the DB table for the given schema.
     * @param array $schema
     * @return string
     */
    private static function getTableFromSchema(array $schema): string
    {
        $table = $schema['x-table'] ?? ($schema['x-itemtype'] ? getTableForItemType($schema['x-itemtype']) : null);
        if ($table === null) {
            throw new \RuntimeException('Schema has no x-table or x-itemtype');
        }
        return $table;
    }

    /**
     * Get the primary ID field given some other unique field.
     * @param array $schema The schema
     * @param string $field The unique field name
     * @param mixed $value The unique field value
     * @return int|null The ID or null if not found
     */
    public static function getIDForOtherUniqueFieldBySchema(array $schema, string $field, mixed $value): ?int
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (!isset($schema['properties'][$field])) {
            throw new \RuntimeException('Invalid primary key');
        }
        $prop = $schema['properties'][$field];
        $pk_sql_name = $prop['x-field'] ?? $field;
        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM' => self::getTableFromSchema($schema),
            'WHERE' => [
                $pk_sql_name => $value,
            ],
        ]);
        if (count($iterator) === 0) {
            return null;
        }
        return $iterator->current()['id'];
    }

    /**
     * Get a single item of the given schema, request data and unique field.
     * @param array $schema The schema
     * @param array $request_attrs The request attributes
     * @param array $request_params The request parameters
     * @param string $field The unique field to match on. Defaults to ID. If different, the ID is resolved from the given other unique field.
     * The field must be present in the route path (request attributes).
     * @return Response
     * @see self::getIDForOtherUniqueFieldBySchema()
     * @see self::searchBySchema()
     */
    public static function getOneBySchema(array $schema, array $request_attrs, array $request_params, string $field = 'id'): Response
    {
        // Shortcut implementation using the search functionality with an injected RSQL filter and returning the first result.
        // This shouldn't have much if any unneeded overhead as the filter would be mapped to a SQL condition.
        $request_params['filter'] = $field . '==' . $request_attrs[$field];
        $request_params['limit'] = 1;
        unset($request_params['start']);
        try {
            $results = self::getSearchResultsBySchema($schema, $request_params);
        } catch (RSQLException $e) {
            return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_INVALID_PARAMETER, $e->getUserMessage()), 400);
        } catch (APIException $e) {
            return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_GENERIC, $e->getUserMessage()));
        } catch (\Throwable $e) {
            $message = (new APIException())->getUserMessage();
            return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_GENERIC, $message));
        }
        if (count($results['results']) === 0) {
            return AbstractController::getNotFoundErrorResponse();
        }
        return new JSONResponse($results['results'][0]);
    }

    /**
     * Create an item of the given schema using the given request parameters.
     * @param array $schema The schema
     * @param array $request_params The request parameters
     * @param array $get_route The GET route to use to get the created item. This should be an array containing the controller class and method.
     * @phpstan-param array<class-string<AbstractController>, string> $get_route
     * @param array $extra_get_route_params Additional parameters needed to generate the GET route. This should only be needed for complex routes.
     *      This is used to re-map the parameters to the GET route.
     *      The array can contain an 'id' property which is the name of the parameter that the resulting ID is set to ('id' by default).
     *      The array may also contain a 'mapped' property which is an array of parameter names and static values.
     *      For example ['mapped' => ['subitem_type' => 'Followup']] would set the 'subitem_type' parameter to 'Followup'.
     * @return Response
     */
    public static function createBySchema(array $schema, array $request_params, array $get_route, array $extra_get_route_params = []): Response
    {
        $itemtype = self::getItemtypeFromSchema($schema);
        if (!isset($request_params['entity']) && isset($_SESSION['glpiactive_entity'])) {
            $request_params['entity'] = $_SESSION['glpiactive_entity'];
        }
        $input = self::getInputParamsBySchema($schema, $request_params);

        /** @var CommonDBTM $item */
        $item = new $itemtype();
        if (!$item->can($item->getID(), CREATE, $input)) {
            return AbstractController::getAccessDeniedErrorResponse();
        }
        $items_id = $item->add($input);
        [$controller, $method] = $get_route;

        $id_field = $extra_get_route_params['id'] ?? 'id';
        if ($items_id !== false) {
            $request_params[$id_field] = $items_id;
        }
        if (array_key_exists('mapped', $extra_get_route_params)) {
            foreach ($extra_get_route_params['mapped'] as $key => $value) {
                $request_params[$key] = $value;
            }
        }

        return AbstractController::getCRUDCreateResponse($items_id, $controller::getAPIPathForRouteFunction($controller, $method, $request_params));
    }

    /**
     * Update an item of the given schema using the given request parameters.
     * @param array $schema The schema
     * @param array $request_attrs The request attributes
     * @param array $request_params The request parameters
     * @param string $field The unique field to match on. Defaults to ID. If different, the ID is resolved from the given other unique field.
     * The field must be present in the route path (request attributes).
     * @return Response
     * @see self::getIDForOtherUniqueFieldBySchema()
     */
    public static function updateBySchema(array $schema, array $request_attrs, array $request_params, string $field = 'id'): Response
    {
        $items_id = $field === 'id' ? $request_attrs['id'] : self::getIDForOtherUniqueFieldBySchema($schema, $field, $request_attrs[$field]);
        $itemtype = self::getItemtypeFromSchema($schema);
        // Ignore entity updates. This needs to be done through the Transfer process
        // TODO This should probably be handled in a more generic way (support other fields that can be used during creation but not updates)
        if (array_key_exists('entity', $request_attrs)) {
            unset($request_attrs['entity']);
        }
        $input = self::getInputParamsBySchema($schema, $request_params);
        $input['id'] = $items_id;
        /** @var CommonDBTM $item */
        $item = new $itemtype();
        if (!$item->can($items_id, UPDATE, $input)) {
            return AbstractController::getAccessDeniedErrorResponse();
        }
        $result = $item->update($input);

        if ($result === false) {
            return AbstractController::getCRUDErrorResponse(AbstractController::CRUD_ACTION_UPDATE);
        }
        // We should return the updated item but we NEVER return the GLPI item fields directly. Need to use special API methods.
        return self::getOneBySchema($schema, $request_attrs + ['id' => $items_id], $request_params);
    }

    /**
     * Delete an item of the given schema using the given request parameters.
     * @param array $schema The schema
     * @param array $request_attrs The request attributes
     * @param array $request_params The request parameters
     * @param string $field The unique field to match on. Defaults to ID. If different, the ID is resolved from the given other unique field.
     * The field must be present in the route path (request attributes).
     * @return Response
     * @see self::getIDForOtherUniqueFieldBySchema()
     */
    public static function deleteBySchema(array $schema, array $request_attrs, array $request_params, string $field = 'id'): Response
    {
        $items_id = $field === 'id' ? $request_attrs['id'] : self::getIDForOtherUniqueFieldBySchema($schema, $field, $request_attrs[$field]);
        $itemtype = self::getItemtypeFromSchema($schema);
        /** @var CommonDBTM $item */
        $item = new $itemtype();
        $force = $request_params['force'] ?? false;
        $input = ['id' => (int) $items_id];
        $purge = !$item->maybeDeleted() || $force;
        if (!$item->can($items_id, $purge ? PURGE : DELETE, $input)) {
            return AbstractController::getAccessDeniedErrorResponse();
        }
        $result = $item->delete($input, $purge ? 1 : 0);

        if ($result === false) {
            return AbstractController::getCRUDErrorResponse(AbstractController::CRUD_ACTION_DELETE);
        }
        return new JSONResponse(null, 204);
    }
}
