<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Api\HL\Doc as Doc;
use Glpi\Debug\Profiler;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Throwable;

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
        return str_replace([' ', '-'], ['', '_'], $type_name);
    }

    public function getSchema()
    {
        Profiler::getInstance()->start('GraphQLGenerator::loadTypes', Profiler::CATEGORY_HLAPI);
        $this->loadTypes();
        Profiler::getInstance()->stop('GraphQLGenerator::loadTypes');
        $schema_str = '';
        Profiler::getInstance()->start('GraphQLGenerator::writeTypes', Profiler::CATEGORY_HLAPI);
        foreach ($this->types as $type_name => $type) {
            $schema_str .= $this->writeType($type_name, $type);
        }
        Profiler::getInstance()->stop('GraphQLGenerator::writeTypes');

        Profiler::getInstance()->start('GraphQLGenerator::normalizeTypeNames', Profiler::CATEGORY_HLAPI);
        // Write Query
        $schema_str .= "type Query {\n";
        foreach (array_keys($this->types) as $type_name) {
            if (str_starts_with($type_name, '_')) {
                continue;
            }
            $type_name = $this->normalizeTypeName($type_name);
            $args_str = '(id: Int, filter: String, start: Int, limit: Int, sort: String, order: String)';

            $schema_str .= "  {$type_name}{$args_str}: [$type_name]\n";
        }
        $schema_str .= "}\n";
        Profiler::getInstance()->stop('GraphQLGenerator::normalizeTypeNames');

        return $schema_str;
    }

    private function writeType($type_name, ObjectType|callable $type): string
    {
        $type_name = $this->normalizeTypeName($type_name);
        $type_str = "type $type_name {\n";
        if (is_callable($type)) {
            $type = $type();
        }
        // Ignore types with no fields (For example, maybe a custom asset from a definition without any custom fields generates an invalid type like "_CustomAsset_Car_custom_fields")
        if (empty($type->getFields())) {
            return '';
        }
        foreach ($type->getFields() as $field_name => $field) {
            $field_type = $field->config['type'];
            if ($field_type instanceof ObjectType && empty($field->config['type']->getFields())) {
                // Ignore properties that would like to types with no fields
                continue;
            }
            try {
                $type_str .= "  $field_name: {$field->getType()}\n";
            } catch (Throwable $e) {
                global $PHPLOGGER;
                $PHPLOGGER->error(
                    "Error writing field $field_name for type $type_name: {$e->getMessage()}",
                    ['exception' => $e]
                );
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
            } elseif ($prop['type'] === Doc\Schema::TYPE_ARRAY) {
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
                'resolve' => fn() => '',
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
                    'resolve' => fn() => '',
                ];
            }
            if (isset($property['x-full-schema'])) {
                return fn() => $this->types[$property['x-full-schema']];
            }
            return new ObjectType([
                'name' => "_{$prefix}_{$name}",
                'fields' => $fields,
            ]);
        }
    }
}
