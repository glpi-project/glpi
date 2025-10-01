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

namespace Glpi\Api\HL;

use CommonDBTM;
use DBConnection;
use DBmysql;
use DBmysqlIterator;
use Entity;
use ExtraVisibilityCriteria;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\RSQL\Lexer;
use Glpi\Api\HL\RSQL\Parser;
use Glpi\Api\HL\RSQL\RSQLException;
use Glpi\Api\HL\Search\RecordSet;
use Glpi\Api\HL\Search\SearchContext;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\DBAL\QueryUnion;
use Glpi\Debug\Profiler;
use Glpi\Toolbox\ArrayPathAccessor;
use RuntimeException;
use Session;

use function Safe\preg_match;
use function Safe\preg_replace;

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
    private Parser $rsql_parser;
    private SearchContext $context;
    private DBmysql $db_read;

    private function __construct(array $schema, array $request_params)
    {
        $this->context = new SearchContext($schema, $request_params);
        $this->rsql_parser = new Parser($this);
        $this->db_read = DBConnection::getReadConnection();
    }

    public function getContext(): SearchContext
    {
        return $this->context;
    }

    public function getDBRead(): DBmysql
    {
        return $this->db_read;
    }

    /**
     * @throws APIException
     */
    public function validateIterator(DBmysqlIterator $iterator): void
    {
        if ($iterator->isFailed()) {
            $message = __('An internal error occurred while trying to fetch the data.');
            if ($_SESSION['glpi_use_mode'] === Session::DEBUG_MODE) {
                $message .= ' ' . __('For more information, check the GLPI logs.');
            }
            throw new APIException(
                message: 'A SQL error occurred while trying to get data from the database',
                user_message: $message,
                code: 500,
            );
        }
    }

    public function getSQLFieldForProperty(string $prop_name): string
    {
        $prop = $this->context->getFlattenedProperties()[$prop_name];
        $is_scalar_join = false;
        $is_join = $this->context->isJoinedProperty($prop_name);
        if (isset($this->context->getJoins()[$prop_name])) {
            // Scalar property whose value exists in another table
            $is_scalar_join = true;
            $sql_field = $prop['x-field'];
        } else {
            $sql_field = $prop['x-field'] ?? $prop_name;
        }
        $is_computed = isset($prop['computation']);

        // Computed fields may be used in HAVING clauses so we have no refer to the fields by the alias
        if ($is_computed) {
            return str_replace('.', chr(0x1F), trim($prop_name, '.'));
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
        $prop = $this->context->getFlattenedProperties()[$prop_name];
        if ($prop['writeOnly'] ?? false) {
            // Do not expose write-only fields
            return null;
        }

        // if prop is an array, set the params to the items
        if (array_key_exists('type', $prop) && $prop['type'] === Doc\Schema::TYPE_ARRAY) {
            $prop = $prop['items'];
        }
        if (array_key_exists('type', $prop) && $prop['type'] !== Doc\Schema::TYPE_OBJECT) {
            if (isset($prop['x-mapper']) || isset($prop['x-mapped-property'])) {
                // Do not select fields mapped after the results are retrieved
                return null;
            }
            if (isset($prop['computation'])) {
                $expression = $prop['computation'];
            } else {
                $sql_field = $this->getSQLFieldForProperty($prop_name);
                $expression = $this->db_read::quoteName($sql_field);
                if (str_contains($sql_field, '.')) {
                    $join_name = $this->getJoinNameForProperty($prop_name);
                    // Check if the join property is in an array. If so, we need to concat each result.
                    if (array_key_exists($join_name, $this->context->getJoins())) {
                        $join_def = $this->context->getJoins()[$join_name];
                        if (isset($join_def['join_parent'])) {
                            $parent_join = str_replace(chr(0x1F), '.', $join_def['join_parent']);
                            if (array_key_exists($parent_join, $this->context->getJoins())) {
                                // Need to concat all parent IDs/primary keys + the property desired
                                $parent_keys = [];
                                $current_join_def = $this->context->getJoins()[$parent_join];
                                $current_join_parent = $parent_join;
                                while ($current_join_def !== null) {
                                    $parent_keys[] = new QueryExpression('0x1E');
                                    $primary_key = $this->context->getPrimaryKeyPropertyForJoin($current_join_parent);
                                    // Replace all except last '.' with chr(0x1F) to avoid conflicts with table aliases
                                    $primary_key = implode(chr(0x1F), explode('.', $primary_key, substr_count($primary_key, '.')));


                                    $parent_keys[] = QueryFunction::ifnull(
                                        expression: $primary_key,
                                        value: new QueryExpression('0x0')
                                    );
                                    $current_join_parent = $current_join_def['join_parent'] ?? null;
                                    $current_join_def = $current_join_parent !== null ? ($this->context->getJoins()[$current_join_parent] ?? null) : null;
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
            }

            $alias = str_replace('.', chr(0x1F), $prop_name);
            return new QueryExpression($expression, $alias);
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

        foreach (array_keys($this->context->getFlattenedProperties()) as $prop_name) {
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
        if ($this->context->isUnionSearchMode()) {
            $queries = [];
            foreach ($this->context->getUnionTableNames() as $table) {
                $query = $criteria;
                // Remove join props from the select for now (complex to handle)
                $query['SELECT'] = array_filter($query['SELECT'], function ($select) {
                    $select_str = (string) $select;
                    return str_starts_with($select_str, $this->db_read::quoteName('_.id'));
                });
                //Inject a field for the schema name as the first select
                $schema_name = $this->context->getSchemaNameForUnionTable($table);
                $itemtype_field = new QueryExpression($this->db_read::quoteValue($schema_name), '_itemtype');
                array_unshift($query['SELECT'], $itemtype_field);

                $query['FROM'] = $table . ' AS _';
                unset($query['START'], $query['LIMIT']);
                $queries[] = $query;
            }
            return new QueryUnion($queries, false, '_');
        }

        return $this->context->getSchemaTable() . ' AS _';
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
        foreach ($this->context->getJoins() as $join_alias => $join_definition) {
            $join_clauses = $this->getJoins($join_alias, $join_definition);
            foreach ($join_clauses as $join_type => $join_tables) {
                if (!isset($criteria[$join_type])) {
                    $criteria[$join_type] = [];
                }
                $criteria[$join_type] = array_merge($criteria[$join_type], $join_tables);
            }
        }

        // Handle RSQL filter
        if (!empty($this->context->getRequestParameter('filter'))) {
            $filter_result = $this->rsql_parser->parse(Lexer::tokenize($this->context->getRequestParameter('filter')));
            // Fail the request if any of the filters are invalid
            if (!empty($filter_result->getInvalidFilters())) {
                throw new RSQLException(
                    message: 'RSQL query has invalid filters',
                    details: array_map(static fn($rsql_error) => $rsql_error->getMessage(), $filter_result->getInvalidFilters())
                );
            }
            $criteria['WHERE'] = [$filter_result->getSQLWhereCriteria()];
            $criteria['HAVING'] = [$filter_result->getSQLHavingCriteria()];
        }

        // Handle entity and other visibility restrictions
        $entity_restrict = [];
        if (!$this->context->isUnionSearchMode()) {
            $itemtype = $this->context->getSchemaItemtype(); //should not that use self::getItemFromSchema()?
            /** @var CommonDBTM $item */
            $item = getItemForItemtype($itemtype);
            $entity_restrict = [];
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
            }
            if ($item->isEntityAssign()) {
                $entity_restrict[] = getEntitiesRestrictCriteria('_');
            }
            if ($item instanceof Entity) {
                $entity_restrict = [
                    'OR' => [
                        [$entity_restrict],
                        [getEntitiesRestrictCriteria('_', 'id')],
                    ],
                ];
                // if $entity_restrict has nothing except empty values as leafs, replace with a simple empty array.
                // Expected in root entity when recursive. Having empty arrays will fail the query (thinks it is empty IN).
                $fn_is_empty = static function ($where) use (&$fn_is_empty) {
                    foreach ($where as $where_field => $where_value) {
                        if (is_array($where_value)) {
                            if (!$fn_is_empty($where_value)) {
                                return false;
                            }
                        } elseif (!empty($where_value)) {
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
            $entity_restrict = [getEntitiesRestrictCriteria('_'),];
        }
        if (!empty($entity_restrict) && $entity_restrict !== [0 => []]) {
            $criteria['WHERE'][] = ['AND' => $entity_restrict];
        }

        // Handle pagination
        $start = $this->context->getRequestParameter('start');
        $limit = $this->context->getRequestParameter('limit');
        if (is_numeric($start)) {
            $criteria['START'] = (int) $start;
        }
        if (is_numeric($limit)) {
            $criteria['LIMIT'] = (int) $limit;
        }

        // Handle sorting
        $sort = $this->context->getRequestParameter('sort');
        if ($sort !== null) {
            $sorts = array_map(static fn($s) => trim($s), explode(',', (string) $sort));
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
                if (!isset($this->context->getFlattenedProperties()[$property])) {
                    throw new APIException(
                        message: 'Invalid property for sorting: ' . $property,
                        user_message: 'Invalid property for sorting: ' . $property,
                        code: 400,
                    );
                }
                $sql_field = $this->getSQLFieldForProperty($property);
                $orderby[] = "{$sql_field} {$direction}";
            }
            $criteria['ORDERBY'] = $orderby;
        }

        return $criteria;
    }

    /**
     * If the schema has a read right condition, add it to the criteria.
     * @param array $criteria The current criteria. Will be modified in-place.
     * @return void
     * @throws RightConditionNotMetException If the read condition check returns false indicating we know the user cannot view any of the resources without needing to check the database.
     */
    private function addReadRestrictCriteria(array &$criteria): void
    {
        $read_right_criteria = $this->context->getSchemaReadRestrictCriteria();
        if (is_callable($read_right_criteria)) {
            $read_right_criteria = $read_right_criteria();
        }
        if ($read_right_criteria === false) {
            throw new RightConditionNotMetException();
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
        if ($where === []) {
            return false;
        }

        foreach ($where as $where_field => $where_value) {
            if (is_array($where_value) && $this->criteriaHasJoinFilter($where_value)) {
                return true;
            }
            foreach (array_keys($this->context->getJoins()) as $join_alias) {
                if (str_starts_with((string) $where_field, $this->db_read::quoteName($join_alias) . '.')) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getJoinNameForProperty(string $prop_name): string
    {
        $joins = $this->context->getJoins();
        $prop_name = str_replace(chr(0x1F), '.', $prop_name);
        if (array_key_exists($prop_name, $joins)) {
            $join_name = $prop_name;
        } else {
            $join_name = substr($prop_name, 0, strrpos($prop_name, '.'));
        }
        return $join_name;
    }

    /**
     * @param bool $ignore_pagination
     * @return RecordSet
     * @throws APIException
     * @throws RSQLException
     */
    private function getMatchingRecords($ignore_pagination = false): RecordSet
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

        try {
            if ($this->context->isUnionSearchMode()) {
                unset($criteria['LEFT JOIN'], $criteria['INNER JOIN'], $criteria['RIGHT JOIN'], $criteria['WHERE']);
            } else {
                $this->addReadRestrictCriteria($criteria);
            }

            if ($this->context->isUnionSearchMode()) {
                $criteria['SELECT'] = ['_.id'];
                $criteria['SELECT'][] = '_itemtype';
                $criteria['GROUPBY'] = ['_itemtype', '_.id'];
            } else {
                $criteria['SELECT'][] = '_.id';
                foreach (array_keys($this->context->getJoins()) as $join_alias) {
                    $s = $this->getSelectCriteriaForProperty($this->context->getPrimaryKeyPropertyForJoin($join_alias), true);
                    if ($s !== null) {
                        $criteria['SELECT'][] = $s;
                    }
                }
                $criteria['GROUPBY'] = ['_.id'];
            }

            // request just to get the ids/union itemtypes
            $iterator = $this->db_read->request($criteria);
            $this->validateIterator($iterator);
        } catch (RightConditionNotMetException) {
            // The read restrict check seems to have returned false indicating that we already know the user cannot view any of these resources
            global $DB;
            $iterator = new DBmysqlIterator($DB);
            // No validation done because we know the inner result isn't a mysqli result
        }

        if ($this->context->isUnionSearchMode()) {
            // group by _itemtype
            foreach ($iterator as $row) {
                if (!isset($records[$row['_itemtype']])) {
                    $records[$row['_itemtype']] = [];
                }
                $records[$row['_itemtype']][$row['id']] = $row;
            }
        } else {
            foreach ($iterator as $row) {
                if (!isset($records[$this->context->getSchemaItemtype()])) {
                    $records[$this->context->getSchemaItemtype()] = [];
                }
                $records[$this->context->getSchemaItemtype()][$row['id']] = $row;
            }
        }

        if ($this->criteriaHasJoinFilter($criteria['WHERE'] ?? [])) {
            // There was a filter on joined data, so the IDs we got are only the ones that match the filter.
            // We want to get all related items in the result and not just the ones that match the filter.
            $criteria['WHERE'] = [];
            if ($this->context->isUnionSearchMode()) {
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
                $type_records = $records[$this->context->getSchemaItemtype()];
                $criteria['WHERE'] = [
                    '_.id' => array_column($type_records, 'id'),
                ];
            }
            $iterator = $this->db_read->request($criteria);
            $this->validateIterator($iterator);
            foreach ($iterator as $data) {
                $itemtype = $this->context->isUnionSearchMode() ? $data['_itemtype'] : $this->context->getSchemaItemtype();
                if (!isset($records[$itemtype])) {
                    $records[$itemtype] = [];
                }
                $records[$itemtype][$data['id']] = $data;
            }
        }

        return new RecordSet($this, $records);
    }

    public function getItemRecordPath(string $join_name, mixed $id, array $hydrated_record): array
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
                $path_without_ids = implode('.', array_filter(explode('.', $current_path), static fn($p) => !is_numeric($p)));
                if (!isset($this->context->getJoins()[$path_without_ids]['parent_type']) && $this->context->getJoins()[$path_without_ids]['parent_type'] === Doc\Schema::TYPE_OBJECT) {
                    if (!empty($next_id) && preg_match('/\.\d+/', $current_path)) {
                        $items = ArrayPathAccessor::getElementByArrayPath($hydrated_record, $current_path);
                        // Remove numeric id parts from the path to get the join name
                        $current_join = implode('.', array_filter(explode('.', $current_path), static fn($p) => !is_numeric($p)));
                        $primary_prop = $this->context->getPrimaryKeyPropertyForJoin($current_join);
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
            $new_path = array_filter($new_path, static fn($p) => !empty($p));
            $join_prop_path = implode('.', $new_path);
        }
        return [$join_prop_path ?? $join_name, $id];
    }

    /**
     * Fetch results for the given schema and request parameters.
     * Use {@link ResourceAccessor::getOneBySchema()} or {@link ResourceAccessor::searchBySchema()} instead of suing this directly.
     * @param array $schema
     * @param array $request_params
     * @return array The search results
     * @phpstan-return array{results: array, start: int, limit: int, total: int}
     * @throws RSQLException|APIException
     */
    public static function getSearchResultsBySchema(array $schema, array $request_params): array
    {
        // Schema must be an object type
        if ($schema['type'] !== Doc\Schema::TYPE_OBJECT) {
            throw new RuntimeException('Schema must be an object type');
        }
        Profiler::getInstance()->start('Search::getSearchResultsBySchema', Profiler::CATEGORY_HLAPI);
        // Initialize a new search
        $search = new self($schema, $request_params);
        Profiler::getInstance()->start('Get matching records', Profiler::CATEGORY_HLAPI);
        $record_set = $search->getMatchingRecords();
        Profiler::getInstance()->stop('Get matching records');
        Profiler::getInstance()->start('Hydrate matching records', Profiler::CATEGORY_HLAPI);
        $results = $record_set->hydrate();
        Profiler::getInstance()->stop('Hydrate matching records');

        $mapped_props = array_filter($search->context->getFlattenedProperties(), static fn($prop) => isset($prop['x-mapper']));

        Profiler::getInstance()->start('Map and cast properties', Profiler::CATEGORY_HLAPI);
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
            // Handle mapped objects
            $mapped_objs = [];
            foreach ($search->context->getFlattenedProperties() as $prop_name => $prop) {
                if (isset($prop['x-mapped-property'])) {
                    $parent_obj_path = substr($prop_name, 0, strrpos($prop_name, '.'));
                    if (isset($mapped_objs[$parent_obj_path])) {
                        // Parent object already mapped
                        continue;
                    }
                    $parent_obj = ArrayPathAccessor::getElementByArrayPath($schema['properties'], $parent_obj_path);
                    if ($parent_obj === null) {
                        continue;
                    }
                    $mapper = $parent_obj['items']['x-mapper'] ?? $parent_obj['x-mapper'];
                    $mapped_from = $parent_obj['items']['x-mapped-from'] ?? $parent_obj['x-mapped-from'];
                    $mapped_objs[$parent_obj_path] = $mapper(ArrayPathAccessor::getElementByArrayPath($result, $mapped_from));
                }
            }
            foreach ($mapped_objs as $path => $data) {
                $existing_data = ArrayPathAccessor::getElementByArrayPath($result, $path) ?? [];
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $data = array_merge($existing_data, $data);
                ArrayPathAccessor::setElementByArrayPath($result, $path, $data);
            }
            $result = Doc\Schema::fromArray($schema)->castProperties($result);
        }
        Profiler::getInstance()->stop('Map and cast properties');
        unset($result);

        Profiler::getInstance()->start('Query for the total count', Profiler::CATEGORY_HLAPI);
        // Count the total number of results with the same criteria, but without the offset and limit
        $criteria = $search->getSearchCriteria();
        // We only need the total count, so we don't need to hydrate the records
        $all_records = $search->getMatchingRecords(true);
        Profiler::getInstance()->stop('Query for the total count');
        Profiler::getInstance()->stop('Search::getSearchResultsBySchema');

        return [
            'results' => array_values($results),
            'start' => $criteria['START'] ?? 0,
            'limit' => $criteria['LIMIT'] ?? count($results),
            'total' => $all_records->getTotalCount(),
        ];
    }
}
