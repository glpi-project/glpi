<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

use Glpi\Http\Request;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Utils\BuildSchema;

/**
 * GraphQL processor
 */
final class GraphQL
{
    /**
     * Maximum depth of fields in the query that will be recognized.
     */
    public const MAX_QUERY_FIELD_DEPTH = 25;

    public static function processRequest(Request $request): array
    {
        $query = (string) $request->getBody();
        $generator = new GraphQLGenerator();
        $schema_str = $generator->getSchema();
        try {
            $result = \GraphQL\GraphQL::executeQuery(
                schema: BuildSchema::build($schema_str),
                source: $query,
                fieldResolver: function ($source, $args, $context, ResolveInfo $info) {
                    $resolve_obj = true;
                    if ($source !== null) {
                        $resolve_obj = false;
                    }
                    if ($resolve_obj) {
                        $field_selection = $info->getFieldSelection(self::MAX_QUERY_FIELD_DEPTH);
                        $fields_requested = array_keys($info->getFieldSelection());
                        // Get the raw Schema for the type
                        $requested_type = $info->fieldName;
                        $schema = OpenAPIGenerator::getComponentSchemas()[$requested_type] ?? null;
                        if ($schema === null) {
                            return null;
                        }
                        $completed_schema = self::expandSchemaFromRequestedFields($schema, $field_selection);

                        foreach ($completed_schema['properties'] as $schema_field => $schema_field_data) {
                            if (!in_array($schema_field, $fields_requested)) {
                                unset($completed_schema['properties'][$schema_field]);
                            }
                        }

                        if (isset($args['id'])) {
                            $result = json_decode(Search::getOneBySchema($completed_schema, ['id' => $args['id']], [])->getBody(), true);
                            return [$result];
                        }
                        return json_decode(Search::searchBySchema($completed_schema, $args)->getBody(), true);
                    }

                    return $source[$info->fieldName] ?? null;
                }
            );
        } catch (\Throwable $e) {
            return [];
        }
        return $result->toArray();
    }

    private static function expandSchemaFromRequestedFields(array $schema, array $fields_requested): array
    {
        $is_schema_array = array_key_exists('items', $schema) && !array_key_exists('properties', $schema);
        if ($is_schema_array) {
            $properties = $schema['items']['properties'];
        } else {
            $properties = $schema['properties'];
        }
        $field_names = array_keys($fields_requested);
        foreach ($field_names as $field_name) {
            // Check if any requested field is missing and then try to replace it with the full schema
            if (!isset($properties[$field_name])) {
                $properties = self::replacePartialObjectType($schema);
                if ($is_schema_array) {
                    $properties = $properties['items']['properties'];
                } else {
                    $properties = $properties['properties'];
                }
                break;
            }
        }
        foreach ($properties as $schema_field => $schema_field_data) {
            if (!in_array($schema_field, $field_names, true)) {
                unset($properties[$schema_field]);
            } else if (isset($fields_requested[$schema_field]) && is_array($fields_requested[$schema_field])) {
                $properties[$schema_field] = self::expandSchemaFromRequestedFields($schema_field_data, $fields_requested[$schema_field]);
            }
        }
        if ($is_schema_array) {
            $schema['items']['properties'] = $properties;
        } else {
            $schema['properties'] = $properties;
        }
        return $schema;
    }

    private static function replacePartialObjectType(array $schema): array
    {
        $is_schema_array = array_key_exists('items', $schema) && !array_key_exists('properties', $schema);
        $full_schema_name = ($is_schema_array ? $schema['items']['x-full-schema'] : $schema['x-full-schema']) ?? null;
        if ($full_schema_name === null) {
            return $schema;
        }
        $full_schema = OpenAPIGenerator::getComponentSchemas()[$full_schema_name] ?? null;
        if ($full_schema === null) {
            return $schema;
        }

        if ($is_schema_array) {
            $schema['items']['properties'] = $full_schema['properties'];
        } else {
            $schema['properties'] = $full_schema['properties'];
        }
        return $schema;
    }
}
