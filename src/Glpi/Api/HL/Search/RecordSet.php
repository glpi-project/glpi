<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

    private function getJoinNameForFKey(string $fkey): string
    {
        if ($fkey === 'id') {
            return '_';
        }
        return $this->search->getContext()->getJoinNameForProperty($fkey);
    }

    private function getPropertiesToHydrate(string $fkey): array
    {
        if ($fkey === 'id') {
            // Main item
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
        } else {
            $join_name = $this->getJoinNameForFKey($fkey);
            $props_to_use = array_filter($this->search->getContext()->getFlattenedProperties(), function ($prop_name) use ($join_name) {
                if (isset($this->search->getContext()->getJoins()[$prop_name])) {
                    /** Scalar joined properties are fetched directly during {@link self::getMatchingRecords()} */
                    return false;
                }
                $prop_parent = substr($prop_name, 0, strrpos($prop_name, '.'));
                return $prop_parent === $join_name;
            }, ARRAY_FILTER_USE_KEY);
        }
        return $props_to_use;
    }

    /**
     * Returns the row with only the parts that were already hydrated by the initial {@link Search::getMatchingRecords()} call.
     * @param array $row
     * @return array
     */
    private function getHydratedPartsOfMainRecord(array $row): array
    {
        $hydrated_row = [];
        $context = $this->search->getContext();
        $joins = $context->getJoins();
        foreach ($row as $fkey => $value) {
            if (str_starts_with($fkey, '_')) {
                $hydrated_row[$fkey] = $value;
                continue;
            }
            $join_name = $context->getJoinNameForProperty($fkey);
            if (isset($joins[$join_name])) {
                // This is a joined property
                continue;
            }
            $hydrated_row[$fkey] = $value;
        }
        return $hydrated_row;
    }

    public function getHydrationCriteria(string $fkey, string $table, string $itemtype, string $schema_name, array $ids_to_fetch): array
    {
        $criteria = [
            'SELECT' => [],
        ];
        $id_field = 'id';
        $join_name = $this->getJoinNameForFKey($fkey);
        $props_to_use = $fkey !== 'id' || $this->search->getContext()->isUnionSearchMode() ? $this->getPropertiesToHydrate($fkey) : [];

        if ($fkey === 'id') {
            $criteria['FROM'] = "$table AS " . $this->search->getDBRead()::quoteName('_');
            if ($this->search->getContext()->isUnionSearchMode()) {
                $criteria['SELECT'][] = new QueryExpression($this->search->getDBRead()::quoteValue($schema_name), '_itemtype');
            }
        } else {
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
                    //TODO translations should be handled in the main search query? Not sure if it should only be handled in the main search or if it is needed both places.
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
        $itemtype_cache = [];

        foreach ($this->records as $schema_name => $dehydrated_records) {
            Profiler::getInstance()->start('RecordSet::assembleHydratedRecordsLoop', Profiler::CATEGORY_HLAPI);
            foreach ($dehydrated_records as $row) {
                $hydrated_records[] = $this->new_assembleHydratedRecords($row, $schema_name, $fetched_records);
            }
            Profiler::getInstance()->stop('RecordSet::assembleHydratedRecordsLoop');
        }
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
        $dehydrated_refs = array_keys($dehydrated_row);
        $hydrated_record = [];
        $context = $this->search->getContext();
        $joins = $context->getJoins();
        $flattened_properties = $context->getFlattenedProperties();
        foreach ($dehydrated_refs as $dehydrated_ref) {
            if (str_starts_with($dehydrated_ref, '_')) {
                continue;
            }
            $table = $context->getTableForFKey($dehydrated_ref, $schema_name);
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
                    ArrayPathAccessor::setElementByArrayPath($hydrated_record, $k, $v, chr(0x1F));
                }
            } else {
                // Add the joined item fields
                $join_name = $context->getJoinNameForProperty($dehydrated_ref);
                if (isset($flattened_properties[$join_name])) {
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

                    if (isset($joins[$join_name]['parent_type']) && $joins[$join_name]['parent_type'] === Doc\Schema::TYPE_OBJECT) {
                        ArrayPathAccessor::setElementByArrayPath($hydrated_record, $join_prop_path, $matched_record);
                    } elseif ($matched_record !== null) {
                        ArrayPathAccessor::setElementByArrayPath($hydrated_record, $join_prop_path . '.' . $id, $matched_record);
                    }
                }
            }
        }
        // Add any scalar joined properties that may have been fetched with the dehydrated row
        // Do this last as some scalar joined properties may be nested and have other data added after the main record was built
        foreach ($dehydrated_row as $k => $v) {
            $normalized_k = str_replace(chr(0x1F), '.', $k);
            if (isset($joins[$normalized_k]) && !ArrayPathAccessor::hasElementByArrayPath($hydrated_record, $normalized_k)) {
                $v = explode(chr(0x1E), $v);
                $v = end($v);
                ArrayPathAccessor::setElementByArrayPath($hydrated_record, $normalized_k, $v);
            }
        }
        $this->fixupAssembledRecord($hydrated_record);
        return $hydrated_record;
    }

    private function new_assembleHydratedRecords(array $dehydrated_row, string $schema_name, array $fetched_records): array
    {
        $hydrated_record = $dehydrated_row;
        foreach ($dehydrated_row as $dehydrated_ref => $dehyrdated_value) {
            if (!str_contains($dehydrated_ref, chr(0x1F))) {
                continue;
            }
            if ($dehyrdated_value !== null && str_contains($dehyrdated_value, chr(0x1D))) {
                $v = explode(chr(0x1D), $dehyrdated_value);
            } else {
                $v = $dehyrdated_value;
            }
            if (str_contains($dehydrated_ref, chr(0x1F))) {
                if (is_array($v)) {
                    $path_parts = explode(chr(0x1F), $dehydrated_ref);
                    //insert index before the last part and then re-join to dot notation path
                    $last_part = array_pop($path_parts);
                    $parent_path = implode(chr(0x1F), $path_parts);
                    foreach ($v as $i => $vv) {
                        $new_path = $parent_path . '.' . $i . '.' . $last_part;
                        ArrayPathAccessor::setElementByArrayPath($hydrated_record, $new_path, $vv);
                    }
                    unset($hydrated_record[$dehydrated_ref]);
                } else {
                    ArrayPathAccessor::setElementByArrayPath($hydrated_record, str_replace(chr(0x1F), '.', $dehydrated_ref), $v);
                    unset($hydrated_record[$dehydrated_ref]);
                }
            } else {
                $hydrated_record[$dehydrated_ref] = $v;
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
        foreach ($this->search->getContext()->getJoins() as $name => $j) {
            if (!array_key_exists('parent_type', $j)) {
                continue;
            }
            if (str_contains($name, '.')) {
                $pattern = str_replace('.', '\.(?:\d+\.)?', $name);
                $paths = ArrayPathAccessor::getArrayPaths($record, "/^{$pattern}$/");
            } else {
                $paths = [$name];
            }

            foreach ($paths as $path) {
                $join_prop = ArrayPathAccessor::getElementByArrayPath($record, $path);
                if ($join_prop === null) {
                    continue;
                }
                if ($j['parent_type'] === Doc\Schema::TYPE_ARRAY) {
                    //deduplicate $join_prop values. In some cases the SQL may return duplicate joined records like {id:1, name:'A'}, {id:1, name:'A'}
                    $unique_join_prop = [];
                    foreach ($join_prop as $jp) {
                        $unique_join_prop[serialize($jp)] = $jp;
                    }
                    $join_prop = $unique_join_prop;
                    // Re-index the array to have sequential numeric keys
                    $join_prop = array_values($join_prop);
                } elseif (array_key_exists($name, $this->search->getContext()->getFlattenedProperties())) {
                    // Nothing more to do
                    continue;
                } elseif (empty($join_prop) || array_filter($join_prop, static fn($v) => $v !== null) === []) {
                    // Object join with no data = null
                    ArrayPathAccessor::setElementByArrayPath($record, $path, null);
                    continue;
                }
                // Remove any empty values
                $join_prop = array_filter($join_prop, static fn($v) => $v !== []);
                ArrayPathAccessor::setElementByArrayPath($record, $path, $join_prop);
            }
        }
    }
}
