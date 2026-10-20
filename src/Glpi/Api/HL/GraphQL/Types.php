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
use Glpi\Api\HL\GraphQL\Type\DateTimeType;
use Glpi\Api\HL\Schemas;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use LogicException;

class Types
{
    /** @var array<string, Type> */
    private static array $types = [];

    public static function load(string $type_name, string $api_version): Type
    {
        if ($type_name === 'RFC3339DateTime') {
            return DateTimeType::dateTime();
        }
        if (!isset(self::$types[$type_name])) {
            $schema = Schemas::getInstance($api_version)->getSchema($type_name);
            if ($schema === null) {
                throw new LogicException("Schema for type {$type_name} not found");
            }
            self::$types[$type_name] = self::convertRESTSchemaToGraphQLSchema($type_name, $schema, $api_version);
        }
        return self::$types[$type_name];
    }

    /**
     * @param string $schema_name
     * @param array<string, mixed> $schema
     * @param string $api_version
     * @return ObjectType|ListOfType<ObjectType>
     */
    private static function convertRESTSchemaToGraphQLSchema(string $schema_name, array $schema, string $api_version): ObjectType|ListOfType
    {
        $fields = [];
        $is_array_of_objects = $schema['type'] === Doc\Schema::TYPE_ARRAY && isset($schema['items']['properties']);
        if ($is_array_of_objects) {
            $discriminator = $schema['items']['discriminator']['propertyName'] ?? null;
            foreach ($schema['items']['properties'] as $name => $property) {
                $fields[$name] = static fn() => self::convertRESTPropertyToGraphQLType($property, $name, $schema_name, $api_version, $discriminator);
            }
        } else {
            $discriminator = $schema['discriminator']['propertyName'] ?? null;
            foreach ($schema['properties'] as $name => $property) {
                $fields[$name] = static fn() => self::convertRESTPropertyToGraphQLType($property, $name, $schema_name, $api_version, $discriminator);
            }
        }
        $type_config = [
            'name' => $schema_name,
            'fields' => $fields,
        ];
        if (isset($schema['x-graphql-resolver'])) {
            $type_config['resolveField'] = $schema['x-graphql-resolver'];
        }
        return $is_array_of_objects ? new ListOfType(new ObjectType($type_config)) : new ObjectType($type_config);
    }

    /**
     * @param array<string, mixed> $property
     * @param string|null $name
     * @param string $prefix
     * @param string $api_version
     * @param string|null $parent_discriminator The discriminator property name defined on the parent object schema, if any.
     *     Used for union properties that do not define their own discriminator.
     * @return array{type: Type|ListOfType|ObjectType|callable, resolve?: callable}|null
     */
    private static function convertRESTPropertyToGraphQLType(
        array $property,
        ?string $name,
        string $prefix,
        string $api_version,
        ?string $parent_discriminator = null
    ) {
        $field = self::convertRESTPropertyToGraphQLFieldType($property, $name, $prefix, $api_version, $parent_discriminator);
        if ($field === null) {
            return null;
        }
        // Properties may define their own resolver which takes priority over the default field resolver
        $resolver = $property['x-graphql-resolver'] ?? $property['items']['x-graphql-resolver'] ?? null;
        if ($resolver !== null) {
            $field['resolve'] = $resolver;
        }
        return $field;
    }

    /**
     * @param string $name
     * @param array<int, string|array{"$ref": string}> $variants Schema names or schema references
     * @param string $discriminator The property name used to determine the concrete type of a value
     * @param string $api_version
     * @return UnionType
     */
    private static function createUnionType(string $name, array $variants, string $discriminator, string $api_version): UnionType
    {
        if (isset(self::$types[$name])) {
            /** @var UnionType */
            return self::$types[$name];
        }
        $type_list = array_map(
            static fn($v) => str_replace('#/components/schemas/', '', is_array($v) ? $v['$ref'] : $v),
            $variants
        );
        $union_config = [
            'name' => $name,
            'types' => static fn() => array_map(static fn($t) => self::load($t, $api_version), $type_list),
            'resolveType' => static fn($value): Type => self::load($value[$discriminator], $api_version),
        ];
        // Register the type so it can be found by the schema type loader during execution
        /** @phpstan-ignore-next-line */
        return self::$types[$name] = new UnionType($union_config);
    }

    /**
     * @param array<string, mixed> $property
     * @param string|null $name
     * @param string $prefix
     * @param string $api_version
     * @param string|null $parent_discriminator
     * @return array{type: Type|ListOfType|ObjectType|callable}|null
     */
    private static function convertRESTPropertyToGraphQLFieldType(
        array $property,
        ?string $name,
        string $prefix,
        string $api_version,
        ?string $parent_discriminator
    ) {
        $type = $property['type'] ?? 'string';
        $graphql_type = match ($type) {
            Doc\Schema::TYPE_STRING => Type::string(),
            Doc\Schema::TYPE_INTEGER => Type::int(),
            Doc\Schema::TYPE_NUMBER => Type::float(),
            Doc\Schema::TYPE_BOOLEAN => Type::boolean(),
            default => null,
        };
        if (
            $graphql_type === Type::string()
            && isset($property['format'])
            && $property['format'] === Doc\Schema::FORMAT_STRING_DATE_TIME
        ) {
            $graphql_type = DateTimeType::dateTime();
        }
        if ($graphql_type !== null) {
            return ['type' => $graphql_type];
        }

        // Handle array and object types
        if ($type === Doc\Schema::TYPE_ARRAY) {
            $graphql_type = self::convertRESTPropertyToGraphQLFieldType($property['items'], $name, $prefix, $api_version, $parent_discriminator);
            if ($graphql_type === null) {
                return null;
            }
            return ['type' => new ListOfType($graphql_type['type'])];
        }

        if ($type === Doc\Schema::TYPE_OBJECT) {
            // Unions
            if (isset($property['anyOf']) || isset($property['oneOf'])) {
                // anyOf and oneOf could both use UnionType. Not sure there is a good way to properly say for oneOf that all items are the same type.
                $discriminator = $property['discriminator']['propertyName'] ?? $parent_discriminator ?? '_type';
                return [
                    'type' => self::createUnionType("_{$prefix}_{$name}", $property['anyOf'] ?? $property['oneOf'], $discriminator, $api_version),
                ];
            }

            $properties = $property['properties'];
            $discriminator = $property['discriminator']['propertyName'] ?? null;
            $fields = [];
            foreach ($properties as $prop_name => $prop_value) {
                $fields[$prop_name] = static fn() => self::convertRESTPropertyToGraphQLType($prop_value, $prop_name, $prefix, $api_version, $discriminator);
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
