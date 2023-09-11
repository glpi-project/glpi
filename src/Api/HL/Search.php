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

use CommonDBTM;
use Entity;
use ExtraVisibilityCriteria;
use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Api\HL\Doc;
use Glpi\DBAL\QueryFunction;
use Glpi\Http\JSONResponse;
use Glpi\Http\Response;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryUnion;
use RuntimeException;

/**
 * Internal search engine for the High-Level API
 *
 * In contrast with the regular Search engine, this uses specific schemas which represent items rather than search options.
 * The data returned is not configurable and joined items are returned "whole" as defined by the schema rather than individual fields.
 * Filters are defined using RSQL rather than form data parameters.
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

    private function __construct(array $schema, array $request_params)
    {
        $this->schema = $schema;
        $this->request_params = $request_params;
        $this->flattened_properties = Doc\Schema::flattenProperties($schema['properties']);
        $this->joins = Doc\Schema::getJoins($schema['properties']);
        $this->table_schemas = $this->getTables();
        $this->tables = array_keys($this->table_schemas);
        $this->union_search_mode = count($this->tables) > 1;
    }

    private function getSQLFieldForProperty(string $prop_name): string
    {
        $prop = $this->flattened_properties[$prop_name];
        $is_join = str_contains($prop_name, '.');
        $sql_field = $prop['x-field'] ?? $prop_name;
        if (!$is_join) {
            // Only add the _. prefix if it isn't a join
            $sql_field = "_.$sql_field";
        } else if ($prop_name !== $sql_field) {
            // If the property name is different from the SQL field name, we will need to add/change the table alias
            // $prop_name is a join where the part before the dot is the join alias (also the property on the main item), and the part after the dot is the property on the joined item
            $join_alias = explode('.', $prop_name)[0];
            $sql_field = "{$join_alias}.{$sql_field}";
        }
        return $sql_field;
    }

    /**
     * @param string $prop_name
     * @return QueryExpression|null
     */
    private function getSelectCriteriaForProperty(string $prop_name, bool $distinct_groups = false): ?QueryExpression
    {
        global $DB;

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
                $expression = $DB::quoteName($sql_field);
                if (str_contains($sql_field, '.')) {
                    $join_name = explode('.', $sql_field, 2)[0];
                    if (array_key_exists($join_name, $this->joins) && $this->joins[$join_name]['parent_type'] === 'array') {
                        $expression = QueryFunction::ifnull($sql_field, new QueryExpression('0x0'));
                        if ($distinct_groups) {
                            $expression = QueryFunction::groupConcat($expression, new QueryExpression(chr(0x1D)), true);
                        } else {
                            $expression = QueryFunction::groupConcat($expression, new QueryExpression(chr(0x1D)), false);
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
     * @return array SELECT criteria
     * @see Doc\Schema::flattenProperties()
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

    private static function getJoins(string $join_alias, array $join_definition): array
    {
        $joins = [];

        $fn_append_join = static function ($join_alias, $join, $parent_type = null) use (&$joins, &$fn_append_join) {
            $join_type = ($join['type'] ?? 'LEFT') . ' JOIN';
            if (!isset($joins[$join_type])) {
                $joins[$join_type] = [];
            }
            $join_table = $join['table'] . ' AS ' . $join_alias;
            $join_parent = (isset($join['ref_join']) && $join['ref_join']) ? "{$join_alias}_ref" : '_';
            if (isset($join['ref_join'])) {
                $fn_append_join("{$join_alias}_ref", $join['ref_join'], $join['parent_type'] ?? $parent_type);
            }
            $joins[$join_type][$join_table] = [
                'ON' => [
                    $join_alias => $join['field'] ?? 'id',
                    $join_parent => $join['fkey'],
                ],
            ];
            if (isset($join['condition'])) {
                $joins[$join_type][$join_table]['ON'][] = ['AND' => $join['condition']];
            }
        };
        $fn_append_join($join_alias, $join_definition);

        return $joins;
    }

    private function getFrom(array $criteria)
    {
        global $DB;

        if ($this->union_search_mode) {
            $queries = [];
            foreach ($this->tables as $table) {
                $query = $criteria;
                // Remove join props from the select for now (complex to handle)
                $query['SELECT'] = array_filter($query['SELECT'], static function ($select) use ($DB) {
                    return str_contains($select, $DB::quoteName('_') . '.');
                });
                //Inject a field for the schema name as the first select
                $schema_name = $this->table_schemas[$table];
                $itemtype_field = new QueryExpression($DB::quoteValue($schema_name), '_itemtype');
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
     */
    private function getSearchCriteria(): array
    {
        $criteria = [
            'SELECT' => $this->getSelectCriteria(),
        ];

        foreach ($this->joins as $join_alias => $join_definition) {
            $join_clauses = self::getJoins($join_alias, $join_definition);
            foreach ($join_clauses as $join_type => $join_tables) {
                if (!isset($criteria[$join_type])) {
                    $criteria[$join_type] = [];
                }
                $criteria[$join_type] = array_merge($criteria[$join_type], $join_tables);
            }
        }

        if (isset($this->request_params['filter']) && !empty($this->request_params['filter'])) {
            $rsql = new RSQLInput($this->request_params['filter']);
            $criteria['WHERE'] = $rsql->getSQLCriteria($this->schema);
        }
        $entity_restrict = [];
        if (!$this->union_search_mode) {
            $itemtype = $this->schema['x-itemtype'];
            /** @var CommonDBTM $item */
            $item = new $itemtype();
            if ($item instanceof ExtraVisibilityCriteria) {
                $visibility_restrict = $item::getVisibilityCriteria();
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
        if (!empty($entity_restrict)) {
            $criteria['WHERE'][] = ['AND' => $entity_restrict];
        }

        if (isset($this->request_params['start'])) {
            $criteria['START'] = $this->request_params['start'];
        }

        if (isset($this->request_params['limit'])) {
            $criteria['LIMIT'] = $this->request_params['limit'];
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
     * @return array Matching records in the format Itemtype => IDs
     * @phpstan-return array<string, int[]>
     */
    private function getMatchingRecords($ignore_pagination = false): array
    {
        global $DB;

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

        $criteria['SELECT'] = ['_.id'];
        if ($this->union_search_mode) {
            $criteria['SELECT'][] = '_itemtype';
            $criteria['GROUPBY'] = ['_itemtype', '_.id'];
        } else {
            foreach ($this->joins as $join_alias => $join) {
                $s = $this->getSelectCriteriaForProperty("$join_alias.id", true);
                if ($s !== null) {
                    $criteria['SELECT'][] = $s;
                }
            }
            $criteria['GROUPBY'] = ['_.id'];
        }

        // request just to get the ids/union itemtypes
        $iterator = $DB->request($criteria);

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

        if (!empty($criteria['WHERE'])) {
            $fn_has_join_filter = function ($where) use (&$fn_has_join_filter, $DB) {
                foreach ($where as $where_field => $where_value) {
                    if (is_array($where_value)) {
                        if ($fn_has_join_filter($where_value)) {
                            return true;
                        }
                    }
                    foreach ($this->joins as $join_alias => $join_definition) {
                        if (str_starts_with((string)$where_field, $DB::quoteName($join_alias) . '.')) {
                            return true;
                        }
                    }
                }
                return false;
            };
            if ($fn_has_join_filter($criteria['WHERE'])) {
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
                $iterator = $DB->request($criteria);
                foreach ($iterator as $data) {
                    $itemtype = $this->union_search_mode ? $data['_itemtype'] : $this->schema['x-itemtype'];
                    if (!isset($records[$itemtype])) {
                        $records[$itemtype] = [];
                    }
                    $records[$itemtype][$data['id']] = $data;
                }
            }
        }

        return $records;
    }

    private function hydrateRecords(array $records): array
    {
        global $DB;

        $hydrated_records = [];
        /** All data retrieved by table */
        $fetched_records = [];

        foreach ($records as $schema_name => $dehydrated_records) {
            $fkey_tables = [];
            $fn_get_table = function ($fkey) use ($schema_name, &$fkey_tables) {
                if (!isset($fkey_tables[$fkey])) {
                    if ($fkey === 'id') {
                        if ($this->union_search_mode) {
                            $subtype = array_filter($this->schema['x-subtypes'], static function ($subtype) use ($schema_name) {
                                return $subtype['schema_name'] === $schema_name;
                            });
                            if (count($subtype) !== 1) {
                                throw new RuntimeException('Cannot find subtype for schema ' . $schema_name);
                            }
                            $subtype = reset($subtype);
                            $fkey_tables[$fkey] = $subtype['itemtype']::getTable();
                        } else {
                            $fkey_tables[$fkey] = self::getTableFromSchema($this->schema);
                        }
                    } else {
                        foreach ($this->joins as $join_alias => $join) {
                            if ($fkey === $join_alias . chr(0x1F) . 'id') {
                                $fkey_tables[$fkey] = $join['table'];
                                break;
                            }
                        }
                    }
                    if ($fkey_tables[$fkey] === null) {
                        throw new RuntimeException('Cannot find table for property ' . $fkey);
                    }
                }
                return $fkey_tables[$fkey];
            };
            foreach ($dehydrated_records as $row) {
                unset($row['_itemtype']);
                // Make sure we have all the needed data
                foreach ($row as $fkey => $record_ids) {
                    $table = $fn_get_table($fkey);
                    $itemtype = getItemTypeForTable($table);

                    if ($record_ids === null) {
                        continue;
                    }
                    $ids_to_fetch = array_map(static fn($id) => (int) $id, explode(chr(0x1D), $record_ids));
                    $ids_to_fetch = array_diff($ids_to_fetch, array_keys($fetched_records[$table] ?? []));

                    if (empty($ids_to_fetch)) {
                        continue;
                    }

                    $criteria = [
                        'SELECT' => [],
                    ];
                    $id_field = 'id';
                    $join_name = '_';

                    if ($fkey === 'id') {
                        $props_to_use = array_filter($this->flattened_properties, static function ($prop_params, $prop_name) {
                            $prop_field = $prop_params['x-field'] ?? $prop_name;
                            $mapped_from_other = isset($prop_params['x-mapped-from']) && $prop_params['x-mapped-from'] !== $prop_field;
                            // We aren't handling joins or mapped fields here
                            return !str_contains($prop_name, '.') && !$mapped_from_other;
                        }, ARRAY_FILTER_USE_BOTH);
                        $criteria['FROM'] = "$table AS " . $DB::quoteName('_');
                        if ($this->union_search_mode) {
                            $criteria['SELECT'][] = new QueryExpression($DB::quoteValue($schema_name), '_itemtype');
                        }
                    } else {
                        $join_name = explode(chr(0x1F), $fkey)[0];
                        $props_to_use = array_filter($this->flattened_properties, static function ($prop_name) use ($join_name) {
                            return str_starts_with($prop_name, $join_name . '.');
                        }, ARRAY_FILTER_USE_KEY);

                        $criteria['FROM'] = "$table AS " . $DB::quoteName($join_name);
                        $id_field = $join_name . '.id';
                    }
                    $criteria['WHERE'] = [$id_field => $ids_to_fetch];
                    foreach ($props_to_use as $prop_name => $prop) {
                        if ($prop['x-writeonly'] ?? false) {
                            continue;
                        }
                        $sql_field = $this->getSQLFieldForProperty($prop_name);
                        $field_parts = explode('.', $sql_field);
                        $field_only = end($field_parts);
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
                        if ($translatable) {
                            $criteria['SELECT'][] = QueryFunction::ifnull(
                                expression: "{$trans_alias}.value",
                                value: $sql_field,
                                alias: str_replace('.', chr(0x1F), $prop_name)
                            );
                        } else {
                            $criteria['SELECT'][] = $sql_field . ' AS ' . str_replace('.', chr(0x1F), $prop_name);
                        }
                    }

                    $it = $DB->request($criteria);
                    foreach ($it as $data) {
                        $cleaned_data = [];
                        foreach ($data as $k => $v) {
                            if (!str_contains($k, chr(0x1F))) {
                                $cleaned_data[$k] = $v;
                                continue;
                            }
                            $cleaned_data[explode(chr(0x1F), $k)[1]] = $v;
                        }
                        $fetched_records[$table][$data[$fkey]] = $cleaned_data;
                    }
                }

                $dehydrated_refs = array_keys($row);
                $hydrated_record = [];
                foreach ($dehydrated_refs as $dehydrated_ref) {
                    if (str_starts_with($dehydrated_ref, '_')) {
                        $dehydrated_ref = 'id';
                    }
                    $table = $fn_get_table($dehydrated_ref);
                    $needed_ids = explode(chr(0x1D), $row[$dehydrated_ref] ?? '');
                    $needed_ids = array_filter($needed_ids, static function ($id) {
                        return $id !== chr(0x0);
                    });
                    if ($dehydrated_ref === 'id') {
                        $hydrated_record = $fetched_records[$table][$needed_ids[0]];
                    } else {
                        $join_name = explode(chr(0x1F), $dehydrated_ref)[0];
                        $hydrated_record[$join_name] = [];
                        foreach ($needed_ids as $id) {
                            $matched_record = $fetched_records[$table][(int) $id] ?? null;
                            if (isset($this->joins[$join_name]['parent_type']) && $this->joins[$join_name]['parent_type'] === Doc\Schema::TYPE_OBJECT) {
                                $hydrated_record[$join_name] = $matched_record;
                            } else {
                                if ($matched_record !== null) {
                                    $hydrated_record[$join_name][] = $matched_record;
                                }
                            }
                        }
                    }
                }
                $hydrated_records[] = $hydrated_record;
            }
        }
        return $hydrated_records;
    }

    private static function getSearchResultsBySchema(array $schema, array $request_params): array
    {
        // Schema must be an object type
        if ($schema['type'] !== Doc\Schema::TYPE_OBJECT) {
            throw new \RuntimeException('Schema must be an object type');
        }
        $search = new self($schema, $request_params);
        $ids = $search->getMatchingRecords();
        $results = $search->hydrateRecords($ids);

        $flattened_properties = Doc\Schema::flattenProperties($schema['properties']);
        $mapped_props = array_filter($flattened_properties, static function ($prop) {
            return isset($prop['x-mapper']);
        });
        foreach ($results as &$result) {
            // Handle mapped fields
            foreach ($mapped_props as $mapped_prop_name => $mapped_prop) {
                $mapped_from_path = explode('.', $mapped_prop['x-mapped-from']);
                $mapped_from = $result;
                foreach ($mapped_from_path as $path_part) {
                    if (isset($mapped_from[$path_part])) {
                        $mapped_from = $mapped_from[$path_part];
                    } else {
                        $mapped_from = null;
                        break;
                    }
                }
                $mapped_to_path = explode('.', $mapped_prop_name);
                // set the mapped value to the result of the x-mapper callable
                $current = &$result;
                foreach ($mapped_to_path as $path_part) {
                    if (!isset($current[$path_part])) {
                        $current[$path_part] = [];
                    }
                    $current = &$current[$path_part];
                }
                $current = $mapped_prop['x-mapper']($mapped_from);
            }
            $result = Doc\Schema::fromArray($schema)->castProperties($result);
        }
        unset($result);

        // Count the total number of results with the same criteria, but without the offset and limit
        $criteria = $search->getSearchCriteria();
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
        $results = self::getSearchResultsBySchema($schema, $request_params);
        $has_more = $results['start'] + $results['limit'] < $results['total'];
        $end = max(0, ($results['start'] + $results['limit'] - 1));
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
                $prop_name = explode('.', $prop_name)[0];
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

    private static function getTableFromSchema(array $schema): string
    {
        $table = $schema['x-table'] ?? ($schema['x-itemtype'] ? getTableForItemType($schema['x-itemtype']) : null);
        if ($table === null) {
            throw new \RuntimeException('Schema has no x-table or x-itemtype');
        }
        return $table;
    }

    private static function getIDForOtherUniqueFieldBySchema(array $schema, string $field, mixed $value): ?int
    {
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

    public static function getOneBySchema(array $schema, array $request_attrs, array $request_params, string $field = 'id'): Response
    {
        // Shortcut implementation using the search functionality with an injected RSQL filter and returning the first result.
        // This shouldn't have much if any unneeded overhead as the filter would be mapped to a SQL condition.
        $request_params['filter'] = $field . '==' . $request_attrs[$field];
        $request_params['limit'] = 1;
        unset($request_params['start']);
        $results = self::getSearchResultsBySchema($schema, $request_params);
        if (count($results['results']) === 0) {
            return AbstractController::getNotFoundErrorResponse();
        }
        return new JSONResponse($results['results'][0]);
    }

    /**
     * @param array $schema
     * @param array $request_params
     * @param array $get_route
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
