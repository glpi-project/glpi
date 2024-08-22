<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class GraphQLGenerator
{
    private array $types = [];

    private string $api_version;

    public function __construct(string $api_version)
    {
        $this->api_version = $api_version;
    }

    private function normalizeTypeName(string $type_name): string
    {
        return str_replace(array(' ', '-'), array('', '_'), $type_name);
    }

    public function getSchema()
    {
        $this->loadTypes();
        $schema_str = '';
        foreach ($this->types as $type_name => $type) {
            $schema_str .= $this->writeType($type_name, $type);
        }

        // Write Query
        $schema_str .= "type Query {\n";
        foreach ($this->types as $type_name => $type) {
            if (str_starts_with($type_name, '_')) {
                continue;
            }
            $type_name = $this->normalizeTypeName($type_name);
            $args_str = '(id: Int, filter: String, start: Int, limit: Int, sort: String, order: String)';

            $schema_str .= "  {$type_name}{$args_str}: [$type_name]\n";
        }
        $schema_str .= "}\n";

        return $schema_str;
    }

    private function writeType($type_name, ObjectType|callable $type): string
    {
        $type_name = $this->normalizeTypeName($type_name);
        $type_str = "type $type_name {\n";
        if (is_callable($type)) {
            $type = $type();
        }
        foreach ($type->getFields() as $field_name => $field) {
            try {
                $type_str .= "  $field_name: {$field->getType()}\n";
            } catch (\Throwable $e) {
                trigger_error("Error writing field $field_name for type $type_name: {$e->getMessage()}", E_USER_WARNING);
            }
        }
        $type_str .= "}\n";
        return $type_str;
    }

    private function loadTypes()
    {
        $component_schemas = OpenAPIGenerator::getComponentSchemas($this->api_version);
        foreach ($component_schemas as $schema_name => $schema) {
            $new_types = $this->getTypesForSchema($schema_name, $schema);
            foreach ($new_types as $type_name => $type) {
                $this->types[$type_name] = $type;
            }
        }
    }

    private function getTypesForSchema(string $schema_name, array $schema): array
    {
        $types = [];
        if (in_array($schema_name, ['EntityTransferRecord'])) {
            return [];
        }
        //Names cannot have spaces or dashes
        $schema_name = $this->normalizeTypeName($schema_name);
        $types[$schema_name] = $this->convertRESTSchemaToGraphQLSchema($schema_name, $schema);

        // Handle "internal" types that are used for object properties
        foreach ($schema['properties'] as $prop_name => $prop) {
            if (isset($prop['x-full-schema'])) {
                continue;
            }
            if ($prop['type'] === Doc\Schema::TYPE_OBJECT) {
                $namespaced_type = "{$schema_name}_{$prop_name}";
                $types['_' . $namespaced_type] = $this->convertRESTPropertyToGraphQLType($prop, $namespaced_type);
            } else if ($prop['type'] === Doc\Schema::TYPE_ARRAY) {
                $items = $prop['items'];
                if ($items['type'] === Doc\Schema::TYPE_OBJECT) {
                    $namespaced_type = "{$schema_name}_{$prop_name}";
                    $types['_' . $namespaced_type] = $this->convertRESTPropertyToGraphQLType($items, $namespaced_type);
                }
            }
        }
        return $types;
    }

    private function convertRESTSchemaToGraphQLSchema(string $schema_name, array $schema): ObjectType
    {
        $fields = [];
        foreach ($schema['properties'] as $name => $property) {
            $fields[$name] = [
                'type' => $this->convertRESTPropertyToGraphQLType($property, $name, $schema_name),
                'resolve' => function () {
                    return '';
                }
            ];
        }
        return new ObjectType([
            'name' => $schema_name,
            'fields' => $fields,
        ]);
    }

    private function convertRESTPropertyToGraphQLType(array $property, ?string $name = null, string $prefix = '')
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
            return $graphql_type;
        }

        // Handle array and object types
        if ($type === Doc\Schema::TYPE_ARRAY) {
            $items = $property['items'];
            $graphql_type = $this->convertRESTPropertyToGraphQLType($items, $name, $prefix);
            return Type::listOf($graphql_type);
        }

        if ($type === Doc\Schema::TYPE_OBJECT) {
            $properties = $property['properties'];
            $fields = [];
            foreach ($properties as $prop_name => $prop_value) {
                $fields[$prop_name] = [
                    'type' => $this->convertRESTPropertyToGraphQLType($prop_value, $prop_name, $prefix),
                    'resolve' => function () {
                        return '';
                    }
                ];
            }
            if (isset($property['x-full-schema'])) {
                return function () use ($property) {
                    return $this->types[$property['x-full-schema']];
                };
            }
            return new ObjectType([
                'name' => "_{$prefix}_{$name}",
                'fields' => $fields,
            ]);
        }
    }
}
