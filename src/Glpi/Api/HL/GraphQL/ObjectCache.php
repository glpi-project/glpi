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

namespace Glpi\Api\HL\GraphQL;

use GraphQL\Deferred;

/**
 * Cache for loaded objects during a GraphQL request to avoid multiple database queries for the same object.
 * If a query requests the same object but with different fields, the cached object will be merged to include all requested fields.
 */
final class ObjectCache
{
    /**
     * @var array<string, array<int, CachedObject>> Cached objects indexed by schema name and ID
     */
    private array $objects = [];

    /**
     * @var array<string, array{id: int[], fields: array}> Queue of pending requests for objects
     */
    private array $request_queue = [];

    public function add(string $schema_name, int $id, array $fields): void
    {
        if (!isset($this->request_queue[$schema_name])) {
            $this->request_queue[$schema_name] = ['id' => [], 'fields' => []];
        }
        if (!in_array($id, $this->request_queue[$schema_name]['id'], true)) {
            $this->request_queue[$schema_name]['id'][] = $id;
        }
        $this->request_queue[$schema_name]['fields'] = array_unique(array_merge(
            $this->request_queue[$schema_name]['fields'],
            $fields
        ));
    }

    public function getPending(string $schema_name, bool $clear = true): array
    {
        $result = $this->request_queue[$schema_name] ?? [];
        if ($clear) {
            unset($this->request_queue[$schema_name]);
        }
        return $result;
    }

    /**
     * Get a cached object by schema name and ID.
     *
     * @param string $schema_name The schema name
     * @param int    $id          The object ID
     *
     * @return CachedObject|null The cached object or null if not found
     */
    public function get(string $schema_name, int $id): ?CachedObject
    {
        //TODO What if only some fields are requested for this specific case?
        return $this->objects[$schema_name][$id] ?? null;
    }

    /**
     * Store a cached object.
     * If the object already exists in the cache, merge the data to include all fields.
     *
     * @param string       $schema_name The schema name
     * @param int          $id          The object ID
     * @param array        $data        The object data to cache
     *
     * @return void
     */
    public function set(string $schema_name, int $id, array $data): void
    {
        if (isset($this->objects[$schema_name][$id])) {
            // Merge existing data with new data
            $existing_data = $this->objects[$schema_name][$id]->data;
            $merged_data = array_merge($existing_data, $data);
            $this->objects[$schema_name][$id]->data = $merged_data;
        } else {
            // Store new cached object
            $this->objects[$schema_name][$id] = new CachedObject($data);
        }
    }

    /**
     * Determine which objects need to be loaded and which additional fields are required for already cached objects.
     * @param string $schema_name The schema name
     * @param array<int> $ids     The list of object IDs to check
     * @param array<string> $fields The list of fields requested
     * @return array<int, array> An array where the keys are object IDs that need to be loaded, and the values are arrays of additional fields needed (if not cached, all fields returned).
     */
    public function getNeeded(string $schema_name, array $ids, array $fields)
    {
        $needed = [];
        foreach ($ids as $id) {
            $cached_object = $this->get($schema_name, $id);
            if ($cached_object === null) {
                // Object not cached, need to load all fields
                $needed[$id] = $fields;
            } else {
                // Object cached, check for missing fields
                $missing_fields = array_diff($fields, $cached_object->getFields());
                if (!empty($missing_fields)) {
                    $needed[$id] = $missing_fields;
                }
            }
        }
        return $needed;
    }
}
