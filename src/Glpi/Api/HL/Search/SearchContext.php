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

use CommonDBTM;
use Glpi\Api\HL\Doc as Doc;
use RuntimeException;

use function Safe\preg_match;

/**
 * Holds contextual information about a search request including the API schema being used which controls the extent of the search.
 */
final class SearchContext
{
    private array $schema;
    private array $request_params;
    private array $flattened_properties;
    private array $joins;
    private array $union_table_schemas;
    /**
     * @var array Cache of table names for foreign keys.
     */
    private array $fkey_tables = [];

    public function __construct(array $schema, array $request_params)
    {
        $this->schema = $schema;
        $this->request_params = $request_params;
        $this->flattened_properties = Doc\Schema::flattenProperties($schema['properties']);
        $this->joins = Doc\Schema::getJoins($schema['properties']);
        $this->union_table_schemas = $this->getUnionTables();
    }

    public function clearFkeyTablesCache(): void
    {
        $this->fkey_tables = [];
    }

    /**
     * @return array Primary tables to search in for union searches.
     * @phpstan-return array<string, string> Array where the keys are table names and the values are the schema names.
     */
    private function getUnionTables(): array
    {
        $tables_schemas = [];
        if (isset($this->schema['x-subtypes'])) {
            foreach ($this->schema['x-subtypes'] as $subtype_info) {
                if (is_subclass_of($subtype_info['itemtype'], CommonDBTM::class)) {
                    $t = $subtype_info['itemtype']::getTable();
                    $tables_schemas[$t] = $subtype_info['schema_name'];
                } else {
                    throw new RuntimeException('Invalid itemtype');
                }
            }
        }
        return $tables_schemas;
    }

    /**
     * @return bool True if the search context is a union search (searches across multiple schemas).
     */
    public function isUnionSearchMode(): bool
    {
        return isset($this->schema['x-subtypes']);
    }

    /**
     * @return array List of table names to search in for union searches.
     */
    public function getUnionTableNames(): array
    {
        return array_keys($this->union_table_schemas);
    }

    /**
     * @param string $table_name The table that is part of a union search.
     * @return string|null The name of the schema related to the table or null if the table is not part of a union search.
     */
    public function getSchemaNameForUnionTable(string $table_name): ?string
    {
        return $this->union_table_schemas[$table_name];
    }

    /**
     * @return string The table name related to this schema.
     */
    public function getSchemaTable(): string
    {
        if (isset($this->schema['x-table'])) {
            return $this->schema['x-table'];
        } elseif (isset($this->schema['x-itemtype'])) {
            if (is_subclass_of($this->schema['x-itemtype'], CommonDBTM::class)) {
                return $this->schema['x-itemtype']::getTable();
            } else {
                throw new RuntimeException('Invalid itemtype');
            }
        } else {
            throw new RuntimeException('Cannot search using a schema without an x-table or an x-itemtype');
        }
    }

    /**
     * @return array The schema definition for this search context.
     */
    public function getSchema(): array
    {
        return $this->schema;
    }

    /**
     * @return class-string<CommonDBTM>|null The GLPI itemtype related to this schema.
     */
    public function getSchemaItemtype(): ?string
    {
        return $this->schema['x-itemtype'] ?? null;
    }

    /**
     * @return array{schema_name: string, itemtype: class-string<CommonDBTM>}[] Array of schema names/itemtypes this schema combines (for union searches).
     *     An example of this being used is for the Global Assets schema which allows searching across multiple asset schemas.
     */
    public function getSchemaSubtypes(): array
    {
        return $this->schema['x-subtypes'] ?? [];
    }

    /**
     * @return array|callable SQL criteria to restrict search results based on the user's rights or a callable that returns such criteria.
     */
    public function getSchemaReadRestrictCriteria(): array|callable
    {
        return $this->schema['x-rights-conditions']['read'] ?? [];
    }

    /**
     * @param string $name The name of the request parameter to get.
     * @return mixed The value of the request parameter or null if not set.
     */
    public function getRequestParameter(string $name): mixed
    {
        return $this->request_params[$name] ?? null;
    }

    /**
     * @return array The flattened properties of the schema.
     */
    public function getFlattenedProperties(): array
    {
        return $this->flattened_properties;
    }

    /**
     * @return array The joins defined in the schema.
     */
    public function getJoins(): array
    {
        return $this->joins;
    }

    /**
     * Check if a property is within a join or is itself a join in the case of scalar joined properties.
     * @param string $prop_name The property name
     * @return bool
     */
    public function isJoinedProperty(string $prop_name): bool
    {
        if (isset($this->joins[$prop_name])) {
            return true;
        }
        $prop_parent = substr($prop_name, 0, strrpos($prop_name, '.'));
        return count(array_filter($this->joins, static fn($j_name) => str_starts_with($prop_parent, $j_name), ARRAY_FILTER_USE_KEY)) > 0;
    }

    /**
     * @param string $join The name of the join.
     * @return string The property name that represents the primary key of the join.
     */
    public function getPrimaryKeyPropertyForJoin(string $join): string
    {
        // If this is a scalar property join, simply return the property named the same as the join
        if (isset($this->getFlattenedProperties()[$join])) {
            return $join;
        }
        $pkey_field = 'field';
        $join_params = $this->getJoins()[$join]['ref-join'] ?? $this->getJoins()[$join];
        if (isset($this->getJoins()[$join]['ref-join'])) {
            $pkey_field = 'fkey';
        }
        if (isset($join_params['primary-property'])) {
            $pkey_field = 'primary-property';
        }
        $primary_key = $join_params[$pkey_field];
        $prop_matches = array_filter(
            $this->getFlattenedProperties(),
            static fn($prop_name)
                // Filter matches for the primary key
                => preg_match('/^' . preg_quote($join, '/') . '\.' . preg_quote($primary_key, '/') . '$/', $prop_name) === 1,
            ARRAY_FILTER_USE_KEY
        );

        if (count($prop_matches)) {
            return array_key_first($prop_matches);
        }
        throw new RuntimeException("Cannot find primary key property for join $join");
    }

    /**
     * Resolve the DB table for the given foreign key and schema.
     * @param string $fkey The foreign key name (In the fully qualified property name format, not the SQL field name)
     * @param string $schema_name The schema name
     * @return string|null The DB table name or null if the fkey parameter doesn't seem to be a foreign key.
     */
    public function getTableForFKey(string $fkey, string $schema_name): ?string
    {
        $normalized_fkey = str_replace(chr(0x1F), '.', $fkey);
        if (isset($this->getJoins()[$normalized_fkey])) {
            // Scalar property whose value exists in another table
            return $this->getJoins()[$normalized_fkey]['table'];
        }
        if (!isset($this->fkey_tables[$fkey])) {
            if ($fkey === 'id') {
                // This is a primary key on a main item
                if ($this->isUnionSearchMode()) {
                    $subtype = array_filter($this->getSchemaSubtypes(), static fn($subtype) => $subtype['schema_name'] === $schema_name);
                    if (count($subtype) !== 1) {
                        throw new RuntimeException('Cannot find subtype for schema ' . $schema_name);
                    }
                    $subtype = reset($subtype);
                    $this->fkey_tables[$fkey] = $subtype['itemtype']::getTable();
                } else {
                    $this->fkey_tables[$fkey] = $this->getSchemaTable();
                }
            } else {
                // This is a foreign key on a joined item
                foreach ($this->getJoins() as $join_alias => $join) {
                    if ($fkey === str_replace('.', chr(0x1F), $join_alias) . chr(0x1F) . 'id') {
                        // Found the related join definition. Use the table from that.
                        $this->fkey_tables[$fkey] = $join['table'];
                        break;
                    }
                }
            }
            if (empty($this->fkey_tables[$fkey])) {
                // Probably not a fkey
                return null;
            }
        }
        return $this->fkey_tables[$fkey];
    }
}
