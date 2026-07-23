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

use Glpi\Api\HL\Schemas;
use Glpi\Debug\Profiler;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use LogicException;

final readonly class SchemaGenerator
{
    public function __construct(
        private string $api_version
    ) {}

    public function getSchema(): Schema
    {
        global $GLPI_CACHE;

        $query_type_config = [
            'name' => 'Query',
            'fields' => [],
        ];
        $query_schema_info = $GLPI_CACHE->get('graphql_query_schema_info_' . $this->api_version);

        if ($query_schema_info === null) {
            $query_schema_info = [];
            Profiler::getInstance()->start('OpenAPI Component Schemas Retrieval', Profiler::CATEGORY_HLAPI);
            $component_schemas = Schemas::getInstance($this->api_version)->getAllSchemas();
            Profiler::getInstance()->stop('OpenAPI Component Schemas Retrieval');
            foreach ($component_schemas as $schema_name => $schema_info) {
                $has_custom_resolver = array_key_exists('x-graphql-resolver', $schema_info);
                $should_have_query = (
                    !str_starts_with($schema_name, '_')
                    && (
                        (isset($schema_info['x-itemtype']) && !$has_custom_resolver)
                        || ($has_custom_resolver && $schema_info['x-graphql-resolver'] !== null)
                    )
                );
                if (!$should_have_query) {
                    continue;
                }
                $query_args = [];
                foreach ($schema_info['x-graphql-query-args'] ?? [] as $arg_name => $arg_type) {
                    if (count(array_keys($arg_type)) !== 1 || !isset($arg_type['type'])) {
                        throw new LogicException("Types in 'x-graphql-query-args' must be an array with a single key 'type'.");
                    }
                    if (!($arg_type['type'] instanceof NamedType) || !in_array($arg_type['type']->name(), Type::BUILT_IN_SCALAR_NAMES, true)) {
                        throw new LogicException("Types in 'x-graphql-query-args' can only be instances of built-in scalar types to allow proper serialization.");
                    }
                    $query_args[$arg_name] = $arg_type['type']->name();
                }
                $query_schema_info[$schema_name] = [
                    'has_custom_resolver' => $has_custom_resolver,
                    'is_singleton' => $schema_info['x-singleton'] ?? false,
                    'query_args' => $query_args,
                    'resolver' => $has_custom_resolver ? $schema_info['x-graphql-resolver'] : null,
                ];
            }
            $GLPI_CACHE->set('graphql_query_schema_info_' . $this->api_version, $query_schema_info);
        }

        foreach ($query_schema_info as $schema_name => $schema_info) {
            if ($schema_info['is_singleton'] ?? false) {
                // load type from name
                $query_args = array_map(
                    static fn($arg_type) => ['type' => Type::builtInScalars()[$arg_type]],
                    $schema_info['query_args'] ?? []
                );
                $query_type_config['fields'][$schema_name] = [
                    'type' => fn(): ?Type => Types::load($schema_name, $this->api_version),
                    'args' => $query_args,
                ];
                if (isset($schema_info['resolver'])) {
                    $query_type_config['fields'][$schema_name]['resolve'] = ($schema_info['resolver'])(...);
                }
            } else {
                $query_type_config['fields'][$schema_name] = [
                    'type' => Type::listOf(function () use ($schema_name): Type {
                        $t = Types::load($schema_name, $this->api_version);
                        if ($t === null) {
                            throw new LogicException("Cannot load type for schema name {$schema_name}");
                        }
                        return $t;
                    }),
                    'args' => [
                        'id' => ['type' => Type::int()],
                        'filter' => ['type' => Type::string()],
                        'start' => ['type' => Type::int()],
                        'limit' => ['type' => Type::int()],
                        'sort' => ['type' => Type::string()],
                        'order' => ['type' => Type::string()],
                    ],
                ];
            }
        }
        /** @phpstan-ignore-next-line */
        return new Schema([
            'query' => new ObjectType($query_type_config),
            'typeLoader' => fn(string $sn): ?Type => Types::load($sn, $this->api_version),
        ]);
    }
}
