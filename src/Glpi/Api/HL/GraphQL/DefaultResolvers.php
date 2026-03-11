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

use CommonDBTM;
use DBConnection;
use Glpi\Api\HL\OpenAPIGenerator;
use Glpi\Api\HL\Search;
use Glpi\DBAL\QueryFunction;
use Glpi\DBAL\QuerySubQuery;
use Glpi\Debug\Profiler;
use GraphQL\Deferred;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use stdClass;

use function Safe\json_decode;

/**
 * Default GraphQL field resolvers that use the OpenAPI schema to fetch data from the database.
 */
class DefaultResolvers
{
    private \DBmysql $db;
    private ObjectCache $object_cache;
    /** @var array<string, array<string, mixed>> */
    private array $schema_cache = [];

    public function __construct(
        private string $api_version
    ) {
        $this->db = DBConnection::getReadConnection();
        $this->object_cache = new ObjectCache();
    }

    /**
     * @param string $name
     * @return array<string, mixed>|null
     */
    private function getSchemaForObjectName(string $name): ?array
    {
        return OpenAPIGenerator::getComponentSchemas($this->api_version)[$name] ?? null;
    }

    /**
     * @param string $field_name
     * @param ResolveInfo $info
     * @return array{0: string, 1: array<string, mixed>|null} Tuple of schema name and schema array
     */
    private function getSchemaNameAndSchemaForField(string $field_name, ResolveInfo $info): array
    {
        if (!array_key_exists($info->parentType->name, $this->schema_cache)) {
            $schema = $this->getSchemaForObjectName($info->parentType->name);
             if ($schema === null) {
                return [$info->parentType->name, null];
            }
            $this->schema_cache[$info->parentType->name] = $schema;
        }
        $parent_schema = $this->schema_cache[$info->parentType->name];
        $schema_partial = $parent_schema['properties'][$field_name]['items'] ?? $parent_schema['properties'][$field_name];
        if (array_key_exists('x-itemtype', $schema_partial) && is_subclass_of($schema_partial['x-itemtype'], CommonDBTM::class) && !$schema_partial['x-itemtype']::canView()) {
            // Cannot view this itemtype so we shouldn't expand it further
            return [$info->parentType->name . '.' . $field_name, $schema_partial];
        }
        $schema_name = $schema_partial['x-full-schema'] ?? null;
        if ($schema_name === null) {
            return [$info->parentType->name . '.' . $field_name, $schema_partial];
        }
        if (!array_key_exists($schema_name, $this->schema_cache)) {
            $schema = $this->getSchemaForObjectName($schema_name);
            if ($schema === null) {
                return [$schema_name, null];
            }
            $this->schema_cache[$schema_name] = $schema;
        }
        $schema = $this->schema_cache[$schema_name];

        return [$schema_name, $schema];
    }

    /**
     * @param mixed $source
     * @param array<string, mixed> $args
     * @param object $context
     * @param ResolveInfo $info
     * @return array<string, mixed>|Deferred|null
     */
    public function resolveObjectField(mixed $source, array $args, object $context, ResolveInfo $info): array|Deferred|null
    {
        $fields_requested = array_keys($info->getFieldSelection(1));
        $field_name = $info->fieldName;
        $id = $source[$field_name . chr(0x1F) . 'id'] ?? null;
        if (!is_numeric($id)) {
            //See State Visibilities for example why this can happen
            $parent_schema = $this->getSchemaForObjectName($info->parentType->name);
            if ($parent_schema === null) {
                // no parent schema, return as is and let GraphQL handle it
                return $source[$field_name] ?? null;
            }
            $joined_values = [];
            if (!array_key_exists('x-itemtype', $parent_schema['properties'][$field_name])) {
                foreach ($source as $source_field_name => $source_field_value) {
                    if (str_contains($source_field_name, $field_name . chr(0x1F))) {
                        $leaf = explode(chr(0x1F), $source_field_name)[1];
                        $joined_values[$leaf] = $source_field_value;
                    }
                }
            }
            return $joined_values ?: null;
        }
        $id = (int) $id;

        [$schema_name, $schema] = $this->getSchemaNameAndSchemaForField($field_name, $info);
        if ($schema === null) {
            throw new Error('Unable to resolve field "' . $field_name . '": schema not found');
        }
        $needed = $this->object_cache->getNeeded($schema_name, [$id], $fields_requested);
        if ($needed === []) {
            // Object is already cached with all requested fields
            return $this->object_cache->get($schema_name, $id)?->data;
        }
        $fields_requested = $needed[$id];

        $this->object_cache->add($schema_name, $id, $fields_requested);
        return new Deferred(function () use ($schema_name, $schema, $id, &$args) {
            Profiler::getInstance()->start('GraphQL2::resolveObjectField::deferred::' . $schema_name, Profiler::CATEGORY_HLAPI);
            $to_load = $this->object_cache->getPending($schema_name);
            if (empty($to_load)) {
                $r = $this->object_cache->get($schema_name, $id)?->data;
                Profiler::getInstance()->stop('GraphQL2::resolveObjectField::deferred::' . $schema_name);
                return $r;
            }

            $args['id'] = $to_load['id'];
            $it = $this->db->request($this->getCriteriaForObject($schema, $to_load['fields'], $args));
            foreach ($it as $data) {
                $this->object_cache->set($schema_name, $data['id'], $data);
            }

            $r = $this->object_cache->get($schema_name, $id)?->data;
            Profiler::getInstance()->stop('GraphQL2::resolveObjectField::deferred::' . $schema_name);
            return $r;
        });
    }

    /**
     * @param mixed $source
     * @param array<string, mixed> $args
     * @param stdClass $context
     * @param ResolveInfo $info
     * @return array<int, mixed>|Deferred
     * @throws Error
     */
    public function resolveListField(mixed $source, array $args, stdClass $context, ResolveInfo $info): array|Deferred
    {
        $fields_requested = array_keys($info->getFieldSelection(1));
        $field_name = $info->fieldName;
        $ids = explode(chr(0x1D), $source[$field_name . chr(0x1F) . 'id'] ?? '');

        if ($source === null) {
            $schema_name = $field_name;
            $schema = $this->getSchemaForObjectName($schema_name);
        } else {
            [$schema_name, $schema] = $this->getSchemaNameAndSchemaForField($field_name, $info);
        }
        if ($schema === null) {
            throw new Error('Unable to resolve field "' . $field_name . '": schema not found');
        }
        if (array_key_exists('x-mapper', $schema)) {
            return $source[$field_name];
        }

        foreach ($ids as $id) {
            $this->object_cache->add($schema_name, (int) $id, $fields_requested);
        }

        $executor = function () use (&$context, $source, $schema_name, $schema, $ids, &$args, $info) {
            Profiler::getInstance()->start('GraphQL2::resolveListField::executor::' . $schema_name, Profiler::CATEGORY_HLAPI);
            $to_load = $this->object_cache->getPending($schema_name);
            if (empty($to_load)) {
                $results = [];
                foreach ($ids as $id) {
                    $cached_object = $this->object_cache->get($schema_name, (int) $id);
                    if ($cached_object !== null) {
                        $results[] = $cached_object->data;
                    }
                }
                Profiler::getInstance()->stop('GraphQL2::resolveListField::executor::' . $schema_name);
                return $results;
            }
            if ($source !== null) {
                $args['id'] = $to_load['id'];
            } else {
                $ids = [];
            }
            $criteria = $this->getCriteriaForObject($schema, $to_load['fields'], $args);
            $it = $this->db->request($criteria);
            foreach ($it as $data) {
                // decode all array fields so that parsing can continue as expected
                foreach ($schema['properties'] as $prop_name => $prop_schema) {
                    if (($prop_schema['type'] ?? '') === 'array' && isset($data[$prop_name]) && is_string($data[$prop_name])) {
                        $data[$prop_name] = json_decode($data[$prop_name], true);
                        // do self-mapping immediately
                        if (isset($prop_schema['items']['x-mapper'], $prop_schema['items']['x-mapped-from']) && $prop_schema['items']['x-mapped-from'] === $prop_name) {
                            $data[$prop_name] = $prop_schema['items']['x-mapper']($data[$prop_name]);
                        }
                    }
                }

                $this->object_cache->set($schema_name, (int) $data['id'], $data);
                if ($source === null) {
                    $ids[] = $data['id'];
                }
            }

            if ($source === null) {
                // Add total count to the context for pagination purposes
                $count_it = $this->db->request($this->getCountCriteriaFromCriteria($criteria));
                $total_count = $count_it->current()['count'] ?? 0;
                if (!property_exists($context, 'pagination')) {
                    $context->pagination = [];
                }
                $context->pagination[$info->path[0]] = [
                    'start' => $args['start'] ?? 0,
                    'limit' => $args['limit'] ?? count($ids),
                    'total_count' => $total_count,
                ];
            }

            $results = [];
            foreach ($ids as $id) {
                $cached_object = $this->object_cache->get($schema_name, (int) $id);
                if ($cached_object !== null) {
                    $results[] = $cached_object->data;
                }
            }
            Profiler::getInstance()->stop('GraphQL2::resolveListField::executor::' . $schema_name);
            return $results;
        };

        if ($source !== null) {
            return new Deferred($executor);
        }
        return $executor();
    }

    /**
     * @param mixed $source
     * @param array<string, mixed> $args
     * @param object $context
     * @param ResolveInfo $info
     * @return mixed
     */
    public function resolveScalarField(mixed $source, array $args, object $context, ResolveInfo $info): mixed
    {
        $field_name = $info->fieldName;
        $parent_schema = $this->getSchemaForObjectName($info->parentType->name);
        if ($parent_schema === null) {
            // no parent schema, return as is and let GraphQL handle it
            return $source[$field_name] ?? null;
        }

        // handle mapped fields
        if ($parent_schema['properties'][$field_name]['x-mapper'] ?? false) {
            $mapped_from_value = $source[$parent_schema['properties'][$field_name]['x-mapped-from']];
            return $parent_schema['properties'][$field_name]['x-mapper']($mapped_from_value);
        }

        if (!array_key_exists($field_name, $source) || $source[$field_name] === null) {
            // no action needed on null values
            return null;
        }

        // other formats already handled by GraphQL type system
        return $source[$field_name];
    }

    /**
     * @param array<string, mixed> $schema
     * @param string[] $field_selection
     * @param array<string, mixed> $request_params
     * @return array<string, mixed>
     * @throws Error
     * @throws \Glpi\Api\HL\APIException
     * @throws \Glpi\Api\HL\RSQL\RSQLException
     * @throws \Glpi\Api\HL\RightConditionNotMetException
     */
    private function getCriteriaForObject(array $schema, array $field_selection, array $request_params): array
    {
        Profiler::getInstance()->start('GraphQL2::getCriteriaForObject', Profiler::CATEGORY_HLAPI);
        if (!array_key_exists('x-table', $schema) && !array_key_exists('x-itemtype', $schema)) {
            throw new Error("Schema does not define a table or itemtype to query.");
        }
        $table = $schema['x-table'] ?? $schema['x-itemtype']::getTable();
        $criteria = [
            'SELECT' => [],
            'FROM' => "$table AS _",
            'WHERE' => [],
        ];

        $search = new Search($schema, $request_params);

        if (!in_array('id', $field_selection, true)) {
            // Always select ID to uniquely identify records
            $field_selection[] = 'id';
        }

        // remove all fields not in the schema (maybe they requested a field from the full schema which they cannot see) and write-only fields from selection
        $field_selection = array_filter($field_selection, static function ($field_name) use ($schema) {
            return array_key_exists($field_name, $schema['properties']) && !($schema['properties'][$field_name]['writeOnly'] ?? false);
        });

        // if any selected fields are mapped, ensure their source fields are also selected
        foreach ($field_selection as $field_name) {
            if (isset($schema['properties'][$field_name]['x-mapped-from'])) {
                $mapped_from = $schema['properties'][$field_name]['x-mapped-from'];
                if (!in_array($mapped_from, $field_selection, true)) {
                    $field_selection[] = $mapped_from;
                }
            }
        }

        foreach ($field_selection as $field_name) {
            // skip mapped fields unless mapped from themselves
            if (isset($schema['properties'][$field_name]['x-mapper']) && $schema['properties'][$field_name]['x-mapped-from'] !== $field_name) {
                continue;
            }

            if ($schema['properties'][$field_name]['type'] === 'object') {
                if (array_key_exists('id', $schema['properties'][$field_name]['properties'])) {
                    $criteria['SELECT'][] = $search->getSelectCriteriaForProperty("{$field_name}.id", true);
                } else {
                    foreach ($schema['properties'][$field_name]['properties'] as $sub_field_name => $sub_field_schema) {
                        $criteria['SELECT'][] = $search->getSelectCriteriaForProperty("{$field_name}.{$sub_field_name}");
                    }
                }
            } elseif ($schema['properties'][$field_name]['type'] === 'array') {
                // For arrays, we only select the IDs of the items in the array
                if (!array_key_exists('properties', $schema['properties'][$field_name]['items'])) {
                    if ($schema['properties'][$field_name]['items']['x-mapper'] ?? false) {
                        $mapped_from = $schema['properties'][$field_name]['items']['x-mapped-from'];
                        $criteria['SELECT'][] = $search->getSelectCriteriaForProperty($mapped_from);
                    }
                    continue;
                }
                if (array_key_exists('id', $schema['properties'][$field_name]['items']['properties'])) {
                    $criteria['SELECT'][] = $search->getSelectCriteriaForProperty("{$field_name}.id", true);
                }
            } else {
                $criteria['SELECT'][] = $search->getSelectCriteriaForProperty($field_name);
            }
        }

        $search->addJoinsCriteria($criteria);
        $search->addRSQLCriteria($criteria);

        if ($request_params['id'] ?? null) {
            if (!is_array($request_params['id'])) {
                $request_params['id'] = [(int) $request_params['id']];
            }
            $criteria['WHERE'][] = ['_.id' => $request_params['id']];
        }
        $criteria['GROUPBY'] = ['_.id'];

        $search->addVisibilityCriteria($criteria);
        $search->addReadRestrictCriteria($criteria);
        $search->addPaginationCriteria($criteria);
        $search->addSortingCriteria($criteria);
        Profiler::getInstance()->stop('GraphQL2::getCriteriaForObject');

        return $criteria;
    }

    /**
     * @param array<string, mixed> $criteria
     * @return array<string, mixed>|QuerySubQuery[]
     */
    private function getCountCriteriaFromCriteria(array $criteria): array
    {
        $count_criteria = $criteria;
        if (!array_key_exists('HAVING', $count_criteria) || empty($count_criteria['HAVING'])) {
            $table_alias = '_';
            unset($count_criteria['GROUPBY'], $count_criteria['ORDERBY']);
        } else {
            // Some queries may use HAVING to filter on computed fields so the main query must be done as a subquery and then count that
            $table_alias = 'subquery_count';
            $subquery_criteria = new QuerySubQuery($count_criteria, 'subquery_count');
            $count_criteria = [
                'FROM' => $subquery_criteria,
            ];
        }
        $count_select = [QueryFunction::count("$table_alias.id", true, 'count')];
        $count_criteria['SELECT'] = $count_select;
        return $count_criteria;
    }
}
