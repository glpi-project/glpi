<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

/**
 * Class to convert the OpenAPI schemas to a GraphQL schema
 */
final class GraphQLGenerator
{
    private string $api_version;

    public function __construct(string $api_version)
    {
        $this->api_version = $api_version;
    }

    public function getSchema(): string
    {
        /** @var array<string, string> $types */
        $types = [];
        Profiler::getInstance()->start('OpenAPIGenerator::getComponentSchemas', Profiler::CATEGORY_HLAPI);
        $component_schemas = OpenAPIGenerator::getComponentSchemas($this->api_version);
        Profiler::getInstance()->stop('OpenAPIGenerator::getComponentSchemas');
        foreach ($component_schemas as $schema_name => $schema) {
            $this->writeType($schema_name, $schema, $types);
        }

        $schema = '';
        $queries = "type Query {\n";
        foreach ($types as $type_name => $type_def) {
            $schema .= $type_def . "\n";
            if (!str_starts_with($type_name, '_')) {
                $queries .= "  $type_name(id: Int, filter: String, start: Int, limit: Int, sort: String, order: String): [$type_name]\n";
            }
        }
        $queries .= "}\n";
        $schema .= $queries;
        return $schema;
    }

    /**
     * @param string $schema_name Schema name
     * @param array<string, mixed> $schema Schema definition
     * @param array<string, string> $types GraphQL types collected
     * @return void
     */
    private function writeType(string $schema_name, array $schema, array &$types): void
    {
        if (array_key_exists($schema_name, $types)) {
            return;
        }

        $type_def = "type $schema_name {\n";
        if (isset($schema['properties']) && is_array($schema['properties'])) {
            foreach ($schema['properties'] as $prop_name => $prop_schema) {
                $prop_type = $this->getGraphQLType($prop_name, $prop_schema, $types, $schema_name);
                if ($prop_type !== null) {
                    $type_def .= "  $prop_name: $prop_type\n";
                }
            }
        }
        $type_def .= "}\n";

        $types[$schema_name] = $type_def;
    }

    /**
     * @param string $name Property name
     * @param array<string, mixed> $schema Property schema
     * @param array<string, string> $types GraphQL types collected
     * @param string|null $parent_name Parent property name
     * @return string|null
     */
    private function getGraphQLType(string $name, array $schema, array &$types, ?string $parent_name = null): ?string
    {
        if (isset($schema['type'])) {
            switch ($schema['type']) {
                case Doc\Schema::TYPE_STRING:
                    return 'String';
                case Doc\Schema::TYPE_INTEGER:
                    return 'Int';
                case Doc\Schema::TYPE_BOOLEAN:
                    return 'Boolean';
                case Doc\Schema::TYPE_ARRAY:
                    if (isset($schema['items'])) {
                        $item_type = $this->getGraphQLType($name, $schema['items'], $types, $parent_name);
                        if ($item_type === null) {
                            return null;
                        }
                        return "[$item_type]";
                    }
                    break;
                case Doc\Schema::TYPE_OBJECT:
                    // if the object has x-full-schema, use the value as the type. otherwise create an inline type matching
                    if (isset($schema['x-full-schema']) && is_string($schema['x-full-schema'])) {
                        return $schema['x-full-schema'];
                    } else {
                        if (empty($schema['properties']) || !is_array($schema['properties'])) {
                            return null;
                        }
                        return $this->handleInlineObject($name, $schema, $types, $parent_name);
                    }
            }
        }

        return 'String'; // Default fallback type
    }

    /**
     * @param string $name Property name
     * @param array<string, mixed> $schema Property schema
     * @param array<string, string> $types GraphQL types collected
     * @param string|null $parent_name Parent property name
     * @return string
     */
    private function handleInlineObject(string $name, array $schema, array &$types, ?string $parent_name = null): string
    {
        // inline types start with '_' and named with parent name + field name
        if (($parent_name !== null)) {
            $parent_name = ltrim($parent_name, '_');
        }
        $inline_type_name = '_' . ($parent_name ? ($parent_name . '_') : '') . $name;
        $type_def = "type $inline_type_name {\n";
        if (isset($schema['properties']) && is_array($schema['properties'])) {
            foreach ($schema['properties'] as $prop_name => $prop_schema) {
                $prop_type = $this->getGraphQLType($prop_name, $prop_schema, $types, $inline_type_name);
                if ($prop_type !== null) {
                    $type_def .= "  $prop_name: $prop_type\n";
                }
            }
        }
        $type_def .= "}\n";
        $types[$inline_type_name] = $type_def;
        return $inline_type_name;
    }
}
