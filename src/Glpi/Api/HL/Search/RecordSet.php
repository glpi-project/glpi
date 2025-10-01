<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Api\HL\Search;

use Glpi\Api\HL\APIException;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Search;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\Debug\Profiler;
use Glpi\Toolbox\ArrayPathAccessor;
use Session;

use function Safe\preg_replace;

final class RecordSet
{
    public function __construct(
        private Search $search,
        /** @var array<string, array> */
        private array $records
    ) {}

    public function getTotalCount(): int
    {
        return array_sum(array_map(static fn($records) => count($records), $this->records));
    }

    public function getHydrationCriteria(string $fkey, string $table, string $itemtype, string $schema_name, array $ids_to_fetch): array
    {
        $criteria = [
            'SELECT' => [],
        ];
        $id_field = 'id';
        $join_name = '_';

        if ($fkey === 'id') {
            $props_to_use = array_filter($this->search->getContext()->getFlattenedProperties(), function ($prop_params, $prop_name) {
                if (isset($this->search->getContext()->getJoins()[$prop_name])) {
                    /** Scalar joined properties are fetched directly during {@link self::getMatchingRecords()} */
                    return false;
                }
                $prop_field = $prop_params['x-field'] ?? $prop_name;
                $mapped_from_other = isset($prop_params['x-mapped-property']) || (isset($prop_params['x-mapped-from']) && $prop_params['x-mapped-from'] !== $prop_field);
                // We aren't handling joins or mapped fields here
                $prop_name = str_replace(chr(0x1F), '.', $prop_name);
                $prop_parent = substr($prop_name, 0, strrpos($prop_name, '.'));
                $is_join = count(array_filter($this->search->getContext()->getJoins(), static fn($j_name) => str_starts_with($prop_parent, $j_name), ARRAY_FILTER_USE_KEY)) > 0;
                return !$is_join && !$mapped_from_other;
            }, ARRAY_FILTER_USE_BOTH);
            $criteria['FROM'] = "$table AS " . $this->search->getDBRead()::quoteName('_');
            if ($this->search->getContext()->isUnionSearchMode()) {
                $criteria['SELECT'][] = new QueryExpression($this->search->getDBRead()::quoteValue($schema_name), '_itemtype');
            }
        } else {
            $join_name = $this->search->getJoinNameForProperty($fkey);
            $props_to_use = array_filter($this->search->getContext()->getFlattenedProperties(), function ($prop_name) use ($join_name) {
                if (isset($this->search->getContext()->getJoins()[$prop_name])) {
                    /** Scalar joined properties are fetched directly during {@link self::getMatchingRecords()} */
                    return false;
                }
                $prop_parent = substr($prop_name, 0, strrpos($prop_name, '.'));
                return $prop_parent === $join_name;
            }, ARRAY_FILTER_USE_KEY);

            $criteria['FROM'] = "$table AS " . $this->search->getDBRead()::quoteName(str_replace('.', chr(0x1F), $join_name));
            $id_field = str_replace('.', chr(0x1F), $join_name) . '.id';
        }
        $criteria['WHERE'] = [$id_field => $ids_to_fetch];
        foreach ($props_to_use as $prop_name => $prop) {
            if ($prop['writeOnly'] ?? false) {
                // Property can only be written to, not read. We shouldn't be getting it here.
                continue;
            }

            $trans_alias = null;
            $is_computed = isset($prop['computation']);
            $sql_field = $is_computed ? $prop['computation'] : $this->search->getSQLFieldForProperty($prop_name);
            if (!$is_computed) {
                $field_parts = explode('.', $sql_field);
                $field_only = end($field_parts);
                // Handle translatable fields
                if (Session::haveTranslations($itemtype, $field_only)) {
                    $trans_alias = "{$join_name}__{$field_only}__trans";
                    $trans_alias = hash('xxh3', $trans_alias);
                    if (!isset($criteria['LEFT JOIN'])) {
                        $criteria['LEFT JOIN'] = [];
                    }
                    $criteria['LEFT JOIN']["glpi_dropdowntranslations AS {$trans_alias}"] = [
                        'ON' => [
                            $join_name => 'id',
                            $trans_alias => 'items_id', [
                                'AND' => [
                                    "$trans_alias.language" => Session::getLanguage(),
                                    "$trans_alias.itemtype" => $itemtype,
                                    "$trans_alias.field" => $field_only,
                                ],
                            ],
                        ],
                    ];
                }
            }
            // alias should be prop name relative to current join
            $alias = $prop_name;
            if ($join_name !== '_') {
                $alias = preg_replace('/^' . preg_quote($join_name, '/') . '\./', '', $alias);
            }
            $alias = str_replace('.', chr(0x1F), $alias);
            if (!$is_computed && isset($trans_alias)) {
                // Try to use the translated value, but fall back to the default value if there is no translation
                $criteria['SELECT'][] = QueryFunction::ifnull(
                    expression: "{$trans_alias}.value",
                    value: $sql_field,
                    alias: $alias
                );
            } else {
                $criteria['SELECT'][] = $sql_field instanceof QueryExpression ? new QueryExpression($sql_field, $alias) : ($sql_field . ' AS ' . $alias);
            }
        }
        return $criteria;
    }

    /**
     * @throws APIException
     */
    public function hydrate(): array
    {
        $hydrated_records = [];
        /** All data retrieved by table */
        $fetched_records = [];
        Profiler::getInstance()->start('RecordSet::hydrate get data for dehydrated records', Profiler::CATEGORY_HLAPI);
        Profiler::getInstance()->pause('RecordSet::hydrate get data for dehydrated records');

        foreach ($this->records as $schema_name => $dehydrated_records) {
            // Clear lookup cache between schemas just in case.
            $this->search->getContext()->clearFkeyTablesCache();
            foreach ($dehydrated_records as $row) {
                unset($row['_itemtype']);
                // Make sure we have all the needed data
                foreach ($row as $fkey => $record_ids) {
                    $table = $this->search->getContext()->getTableForFKey($fkey, $schema_name);
                    if ($table === null) {
                        continue;
                    }
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

                    if ($ids_to_fetch === []) {
                        // Every record needed for this row has already been fetched.
                        continue;
                    }

                    $criteria = $this->getHydrationCriteria($fkey, $table, $itemtype, $schema_name, $ids_to_fetch);

                    // Fetch the data for the current dehydrated record
                    Profiler::getInstance()->resume('RecordSet::hydrate get data for dehydrated records');
                    $it = $this->search->getDBRead()->request($criteria);
                    Profiler::getInstance()->pause('RecordSet::hydrate get data for dehydrated records');
                    $this->search->validateIterator($it);
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
        Profiler::getInstance()->stop('RecordSet::hydrate get data for dehydrated records');
        return $hydrated_records;
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
        Profiler::getInstance()->start('RecordSet::assembleHydratedRecords', Profiler::CATEGORY_HLAPI);
        $dehydrated_refs = array_keys($dehydrated_row);
        $hydrated_record = [];
        foreach ($dehydrated_refs as $dehydrated_ref) {
            if (str_starts_with($dehydrated_ref, '_')) {
                $dehydrated_ref = 'id';
            }
            $table = $this->search->getContext()->getTableForFKey($dehydrated_ref, $schema_name);
            if ($table === null) {
                continue;
            }
            $needed_ids = explode(chr(0x1D), $dehydrated_row[$dehydrated_ref] ?? '');
            $needed_ids = array_filter($needed_ids, static fn($id) => $id !== chr(0x0));
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
                $join_name = $this->search->getJoinNameForProperty($dehydrated_ref);
                if (isset($this->search->getContext()->getFlattenedProperties()[$join_name])) {
                    continue;
                }
                if (!ArrayPathAccessor::hasElementByArrayPath($hydrated_record, $join_name)) {
                    ArrayPathAccessor::setElementByArrayPath($hydrated_record, $join_name, []);
                }
                foreach ($needed_ids as $id) {
                    [$join_prop_path, $id] = $this->search->getItemRecordPath($join_name, $id, $hydrated_record);
                    if ($id === '' || $id === "\0") {
                        continue;
                    }
                    $matched_record = $fetched_records[$table][(int) $id] ?? null;

                    if (isset($this->search->getContext()->getJoins()[$join_name]['parent_type']) && $this->search->getContext()->getJoins()[$join_name]['parent_type'] === Doc\Schema::TYPE_OBJECT) {
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
            if (isset($this->search->getContext()->getJoins()[$normalized_k]) && !ArrayPathAccessor::hasElementByArrayPath($hydrated_record, $normalized_k)) {
                $v = explode(chr(0x1E), $v);
                $v = end($v);
                ArrayPathAccessor::setElementByArrayPath($hydrated_record, $normalized_k, $v);
            }
        }
        Profiler::getInstance()->start('RecordSet::fixupAssembledRecord', Profiler::CATEGORY_HLAPI);
        $this->fixupAssembledRecord($hydrated_record);
        Profiler::getInstance()->stop('RecordSet::fixupAssembledRecord');
        Profiler::getInstance()->stop('RecordSet::assembleHydratedRecords');
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
        $array_joins = array_filter($this->search->getContext()->getJoins(), static fn($v) => isset($v['parent_type']) && $v['parent_type'] === Doc\Schema::TYPE_ARRAY, ARRAY_FILTER_USE_BOTH);
        foreach (array_keys($array_joins) as $name) {
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
                $join_prop = array_filter($join_prop, static fn($v) => !empty($v));
                ArrayPathAccessor::setElementByArrayPath($record, $path, $join_prop);
            }
        }

        // Fix empty array values for objects by replacing them with null
        $obj_joins = array_filter($this->search->getContext()->getJoins(), fn($v, $k) => isset($v['parent_type']) && $v['parent_type'] === Doc\Schema::TYPE_OBJECT && !isset($this->search->getContext()->getFlattenedProperties()[$k]), ARRAY_FILTER_USE_BOTH);
        foreach (array_keys($obj_joins) as $name) {
            // Get all paths in the array that match the join name. Paths may or may not have number parts between the parts of the join name (separated by '.')
            $pattern = str_replace('.', '\.(?:\d+\.)?', $name);
            $paths = ArrayPathAccessor::getArrayPaths($record, "/^{$pattern}$/");
            foreach ($paths as $path) {
                $join_prop = ArrayPathAccessor::getElementByArrayPath($record, $path);
                if ($join_prop === null) {
                    continue;
                }
                $join_prop = array_filter($join_prop, static fn($v) => !empty($v));
                if ($join_prop === []) {
                    ArrayPathAccessor::setElementByArrayPath($record, $path, null);
                }
            }
        }
    }
}
