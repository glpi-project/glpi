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

use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\OpenAPIGenerator;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;

use function Safe\strtotime;

class Types
{
    /** @var array<string, Type> */
    private static array $types = [];

    public static function load(string $type_name, string $api_version): Type
    {
        if (!isset(self::$types[$type_name])) {
            $schemas = OpenAPIGenerator::getComponentSchemas($api_version);
            self::$types[$type_name] = self::convertRESTSchemaToGraphQLSchema($type_name, $schemas[$type_name], $api_version);
        }
        return self::$types[$type_name];
    }

    /**
     * @param string $schema_name
     * @param array<string, mixed> $schema
     * @param string $api_version
     * @return ObjectType
     */
    private static function convertRESTSchemaToGraphQLSchema(string $schema_name, array $schema, string $api_version): ObjectType
    {
        $fields = [];
        foreach ($schema['properties'] as $name => $property) {
            $fields[$name] = static fn() => self::convertRESTPropertyToGraphQLType($property, $name, $schema_name, $api_version);
        }
        $type_config = [
            'name' => $schema_name,
            'fields' => $fields,
        ];
        if (isset($schema['x-graphql-resolver'])) {
            $type_config['resolveField'] = $schema['x-graphql-resolver'];
        }
        return new ObjectType($type_config);
    }

    /**
     * @param array<string, mixed> $property
     * @param string|null $name
     * @param string $prefix
     * @param string $api_version
     * @return array{type: Type|ListOfType|ObjectType|callable}|null
     */
    private static function convertRESTPropertyToGraphQLType(array $property, ?string $name, string $prefix, string $api_version)
    {
        $type = $property['type'] ?? 'string';
        $graphql_type = match ($type) {
            Doc\Schema::TYPE_STRING => Type::string(),
            Doc\Schema::TYPE_INTEGER => Type::int(),
            Doc\Schema::TYPE_NUMBER => Type::float(),
            Doc\Schema::TYPE_BOOLEAN => Type::boolean(),
            default => null,
        };
        if ($graphql_type !== null) {
            if (($property['format'] ?? $property['type']) === Doc\Schema::FORMAT_STRING_DATE_TIME) {
                return [
                    'type' => $graphql_type,
                    'resolve' => static fn($value) => isset($value[$name]) ? date(DATE_RFC3339, strtotime($value[$name])) : null,
                ];
            }
            return ['type' => $graphql_type];
        }

        // Handle array and object types
        if ($type === Doc\Schema::TYPE_ARRAY) {
            $items = $property['items'];

            // Unions
            if (isset($items['anyOf']) || isset($items['oneOf'])) {
                $type_list = array_map(
                    static fn($r) => str_replace('#/components/schemas/', '', $r),
                    array_column($items['anyOf'] ?? $items['oneOf'], '$ref')
                );
                // anyOf and oneOf could both use UnionType. Not sure there is a good way to properly say for oneOf that all items are the same type.
                $union_config = [
                    'name' => "_{$prefix}_{$name}",
                    'types' => static fn() => array_map(static fn($t) => self::load($t, $api_version), $type_list),
                    'resolveType' => static function ($value) use ($api_version): Type {
                        $t = $value;
                        return self::load($t['_tile_type'], $api_version);
                    },
                ];
                /** @phpstan-ignore-next-line */
                $graphql_type = new UnionType($union_config);
                return ['type' => new ListOfType($graphql_type)];
            }

            // Regular arrays
            $graphql_type = self::convertRESTPropertyToGraphQLType($items, $name, $prefix, $api_version);
            if ($graphql_type === null) {
                return null;
            }
            return ['type' => new ListOfType($graphql_type['type'])];
        }

        if ($type === Doc\Schema::TYPE_OBJECT) {
            $properties = $property['properties'];
            $fields = [];
            foreach ($properties as $prop_name => $prop_value) {
                $fields[$prop_name] = static fn() => self::convertRESTPropertyToGraphQLType($prop_value, $prop_name, $prefix, $api_version);
            }
            if (isset($property['x-full-schema'])) {
                $full_schema_name = $property['x-full-schema'];
                return [
                    'type' => static fn() => self::load($full_schema_name, $api_version),
                ];
            }
            return [
                'type' => new ObjectType([
                    'name' => "_{$prefix}_{$name}",
                    'fields' => $fields,
                ]),
            ];
        }
        return null;
    }
}
