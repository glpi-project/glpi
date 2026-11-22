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

use CommonDBTM;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Schemas;
use Glpi\Search\SearchOption;
use Session;

/**
 * Maps between HLAPI schema property paths (e.g. "status.name") and search option numbers.
 *
 * The mapping is resolved in this order for each schema property:
 *  1. An explicit "x-search-option" on the property (an integer, or null to opt the property out).
 *  2. A property with "x-mapped-from" inherits the mapping of the property it is mapped from.
 *  3. Derivation by matching the table, field and join chain of the property against the search options of the schema's itemtype.
 *     If this yields more than one candidate the property is left unmapped rather than guessed.
 *
 * Search option numbers depend on the session (some add-on options are only present for logged-in users), the configuration, plugins and custom assets.
 * Results are therefore cached per schema and per user/profile context and must never be persisted.
 */
final class SearchOptionMapper
{
    /**
     * @var array<string, array{map: array<string, int|string>, unmapped: array<int|string, array<string, mixed>>}>
     */
    private static array $cache = [];

    /**
     * Get the property path for a search option.
     * @param string $schema_name The HLAPI schema name
     * @param int|string $num The search option number
     * @return string|null The dotted property path or null if the option is not mapped to a property of the schema.
     */
    public static function getPropertyForOption(string $schema_name, int|string $num): ?string
    {
        if (is_string($num) && is_numeric($num)) {
            $num = (int) $num;
        }
        $paths = array_map('strval', array_keys(self::getResolved($schema_name)['map'], $num, true));
        if ($paths === []) {
            return null;
        }
        // Several properties may share an option (e.g. "status.id" and "status.name"). Prefer one that isn't a bare identifier.
        $readable_paths = array_filter($paths, static fn(string $path) => !str_ends_with($path, '.id'));
        return array_values($readable_paths !== [] ? $readable_paths : $paths)[0];
    }

    /**
     * Get the search option number for a property path.
     * @param string $schema_name The HLAPI schema name
     * @param string $path The dotted property path
     * @return int|string|null The search option number or null if the property is not mapped.
     */
    public static function getOptionForProperty(string $schema_name, string $path): int|string|null
    {
        return self::getResolved($schema_name)['map'][$path] ?? null;
    }

    /**
     * @param string $schema_name The HLAPI schema name
     * @return array<string, int|string> The full property path => search option number map.
     */
    public static function getMap(string $schema_name): array
    {
        return self::getResolved($schema_name)['map'];
    }

    /**
     * @param string $schema_name The HLAPI schema name
     * @return array<int|string, array<string, mixed>> Search options (by number) of the schema's itemtype that no property maps to.
     *      Options that are not displayable (section headers, virtual or without a table) are not included.
     */
    public static function getUnmappedOptions(string $schema_name): array
    {
        return self::getResolved($schema_name)['unmapped'];
    }

    /**
     * Clear the cached mappings. Mostly useful for tests or after the session context changed.
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }

    /**
     * @return array{map: array<string, int|string>, unmapped: array<int|string, array<string, mixed>>}
     */
    private static function getResolved(string $schema_name): array
    {
        $context_key = implode('|', [
            $schema_name,
            Session::getLoginUserID() ?: 0,
            $_SESSION['glpiactiveprofile']['id'] ?? 0,
        ]);
        if (!isset(self::$cache[$context_key])) {
            self::$cache[$context_key] = self::resolve($schema_name);
        }
        return self::$cache[$context_key];
    }

    /**
     * @return array{map: array<string, int|string>, unmapped: array<int|string, array<string, mixed>>}
     */
    private static function resolve(string $schema_name): array
    {
        $schema = Schemas::getInstance()->getSchema($schema_name);
        if ($schema === null) {
            return ['map' => [], 'unmapped' => []];
        }
        return self::mapSchema($schema);
    }

    /**
     * Compute the property to search option mapping for a schema definition.
     * Results are not cached. Prefer the other methods unless you need to map a schema that is not registered.
     * @param array<string, mixed> $schema The schema definition
     * @return array{map: array<string, int|string>, unmapped: array<int|string, array<string, mixed>>}
     */
    public static function mapSchema(array $schema): array
    {
        $empty = ['map' => [], 'unmapped' => []];
        if (isset($schema['x-subtypes']) || !isset($schema['properties'])) {
            // Union schemas have no single search option set
            return $empty;
        }
        $itemtype = $schema['x-itemtype'] ?? null;
        if (!is_string($itemtype) || !is_subclass_of($itemtype, CommonDBTM::class)) {
            return $empty;
        }

        $options = array_filter(
            SearchOption::getOptionsForItemtype($itemtype),
            static fn($opt) => is_array($opt) && isset($opt['table'], $opt['field'])
                && !str_starts_with($opt['field'], '_virtual')
        );
        $main_table = $itemtype::getTable();
        $flattened = Doc\Schema::flattenProperties($schema['properties']);
        $joins = Doc\Schema::getJoins($schema['properties']);

        $map = [];
        foreach ($flattened as $path => $prop) {
            if (array_key_exists('x-search-option', $prop)) {
                if ($prop['x-search-option'] !== null) {
                    $map[$path] = $prop['x-search-option'];
                }
                continue;
            }
            if (isset($prop['x-mapped-from'])) {
                // Resolved in a second pass once the source properties are known
                continue;
            }
            if (isset($prop['computation'])) {
                continue;
            }
            $num = self::deriveOption($path, $prop, $main_table, $joins, $options);
            if ($num !== null) {
                $map[$path] = $num;
            }
        }
        foreach ($flattened as $path => $prop) {
            if (!array_key_exists('x-search-option', $prop) && isset($prop['x-mapped-from']) && isset($map[$prop['x-mapped-from']])) {
                $map[$path] = $map[$prop['x-mapped-from']];
            }
        }

        $mapped_numbers = array_flip(array_map('strval', $map));
        $unmapped = array_filter(
            $options,
            static fn($opt, $num) => !isset($mapped_numbers[(string) $num])
                && empty($opt['nodisplay']),
            ARRAY_FILTER_USE_BOTH
        );

        return ['map' => $map, 'unmapped' => $unmapped];
    }

    /**
     * Derive the search option for a property from its table, field and join chain.
     * @param string $path The flattened property path
     * @param array<string, mixed> $prop The flattened property definition
     * @param string $main_table The schema's main table
     * @param array<string, array<string, mixed>> $joins The schema's joins (see Doc\Schema::getJoins())
     * @param array<int|string, array<string, mixed>> $options The search options to match against
     * @return int|string|null The option number or null if there is no single match
     */
    private static function deriveOption(string $path, array $prop, string $main_table, array $joins, array $options): int|string|null
    {
        $join_name = self::getJoinNameForPath($path, $joins);
        $segments = explode('.', $path);
        $field = $prop['x-field'] ?? end($segments);

        if ($join_name === null) {
            // Property of the main table
            $table = $main_table;
            $candidates = array_filter(
                $options,
                static fn($opt) => $opt['table'] === $table
                    && $opt['field'] === $field
                    && empty($opt['joinparams'])
                    && ($opt['linkfield'] ?? $field) === $field
            );
        } else {
            $join = $joins[$join_name];
            $table = $join['table'];
            if ($join_name === $path) {
                // Scalar property joined from another table: the property itself is the field
                $field = $prop['x-field'] ?? $field;
            }
            $candidates = array_filter(
                $options,
                static fn($opt) => $opt['table'] === $table
                    && $opt['field'] === $field
                    && self::joinMatches($join, $opt)
            );
        }

        if (count($candidates) === 1) {
            return array_key_first($candidates);
        }
        if (count($candidates) > 1) {
            // Try to discriminate using the join conditions
            $join_condition = $join['condition'] ?? $join['ref-join']['condition'] ?? [];
            $narrowed = array_filter(
                $candidates,
                static fn($opt) => self::conditionMatches($join_condition, $opt)
            );
            if (count($narrowed) === 1) {
                return array_key_first($narrowed);
            }
        }
        return null;
    }

    /**
     * @param array<string, array<string, mixed>> $joins
     * @return string|null The name of the closest join containing the property (or being the property itself), if any.
     */
    private static function getJoinNameForPath(string $path, array $joins): ?string
    {
        if (isset($joins[$path])) {
            return $path;
        }
        $best = null;
        foreach (array_keys($joins) as $join_name) {
            if (str_starts_with($path, $join_name . '.') && ($best === null || strlen($join_name) > strlen($best))) {
                $best = (string) $join_name;
            }
        }
        return $best;
    }

    /**
     * Check if the way a search option reaches its table is equivalent to the x-join definition.
     * @param array<string, mixed> $join
     * @param array<string, mixed> $opt
     */
    private static function joinMatches(array $join, array $opt): bool
    {
        $joinparams = $opt['joinparams'] ?? [];
        $before = self::getBeforeJoinTables($joinparams);

        if (isset($join['ref-join'])) {
            // Many-to-many: the option must go through the same intermediate table
            return $before !== [] && $before[0] === $join['ref-join']['table'];
        }
        if (($join['fkey'] ?? 'id') === 'id') {
            // One-to-many: the option is a child of the main item
            return ($joinparams['jointype'] ?? '') === 'child' && $before === [];
        }
        // Many-to-one dropdown reached through a foreign key of the parent
        return $before === [] && ($opt['linkfield'] ?? null) === $join['fkey'];
    }

    /**
     * @param array<string, mixed> $joinparams
     * @return string[] Tables of the "beforejoin" chain, outermost first.
     */
    private static function getBeforeJoinTables(array $joinparams): array
    {
        if (!isset($joinparams['beforejoin'])) {
            return [];
        }
        $before = $joinparams['beforejoin'];
        $entries = isset($before['table']) ? [$before] : $before;
        $tables = [];
        foreach ($entries as $entry) {
            if (isset($entry['table'])) {
                $tables[] = $entry['table'];
            }
        }
        return $tables;
    }

    /**
     * Check that every plain key of a schema join condition is also present with the same value in the search option conditions.
     * @param array<string, mixed> $join_condition
     * @param array<string, mixed> $opt
     */
    private static function conditionMatches(array $join_condition, array $opt): bool
    {
        $opt_conditions = [
            ...($opt['condition'] ?? []),
            ...($opt['joinparams']['condition'] ?? []),
        ];
        foreach ($join_condition as $key => $value) {
            if (!is_scalar($value)) {
                continue;
            }
            $normalized = str_starts_with((string) $key, 'NEWTABLE.') ? substr((string) $key, strlen('NEWTABLE.')) : (string) $key;
            if (!array_key_exists($key, $opt_conditions) && !array_key_exists($normalized, $opt_conditions)) {
                return false;
            }
            $opt_value = $opt_conditions[$key] ?? $opt_conditions[$normalized];
            if (is_scalar($opt_value) && (string) $opt_value !== (string) $value) {
                return false;
            }
        }
        return true;
    }
}
