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
        $api_version = $request->getHeaderLine('GLPI-API-Version') ?: Router::API_VERSION;
        $query = (string) $request->getBody();
        $generator = new GraphQLGenerator($api_version);
        $schema_str = $generator->getSchema();
        try {
            $result = \GraphQL\GraphQL::executeQuery(
                schema: BuildSchema::build($schema_str),
                source: $query,
                fieldResolver: static function ($source, $args, $context, ResolveInfo $info) use ($api_version) {
                    $resolve_obj = true;
                    if ($source !== null) {
                        $resolve_obj = false;
                    }
                    if ($resolve_obj) {
                        $field_selection = $info->getFieldSelection(self::MAX_QUERY_FIELD_DEPTH);
                        // Get the raw Schema for the type
                        $requested_type = $info->fieldName;
                        $schema = OpenAPIGenerator::getComponentSchemas($api_version)[$requested_type] ?? null;
                        if ($schema === null) {
                            return null;
                        }
                        $completed_schema = self::expandSchemaFromRequestedFields($schema, $field_selection, null, $api_version);

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
            trigger_error("Error processing GraphQL request: {$e->getMessage()}", E_USER_WARNING);
            return [];
        }
        return $result->toArray();
    }

    private static function expandSchemaFromRequestedFields(array $schema, array $fields_requested, ?string $object_prop_key, string $api_version): array
    {
        $is_schema_array = array_key_exists('items', $schema) && !array_key_exists('properties', $schema);
        $itemtype = self::getSchemaItemtype($schema, $api_version);
        if ($is_schema_array) {
            $properties = $schema['items']['properties'];
        } else {
            $properties = $schema['properties'];
        }
        $field_names = array_keys($fields_requested);
        foreach ($field_names as $field_name) {
            // Check if any requested field is missing and then try to replace it with the full schema
            if (!isset($properties[$field_name])) {
                $properties = self::replacePartialObjectType($schema, $api_version);
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
                $properties = self::hideOrRemoveProperty($itemtype, $schema_field, $properties, array_keys($fields_requested), $object_prop_key);
            } else if (isset($fields_requested[$schema_field]) && is_array($fields_requested[$schema_field])) {
                $properties[$schema_field] = self::expandSchemaFromRequestedFields($schema_field_data, $fields_requested[$schema_field], $schema_field, $api_version);
            }
        }
        if ($is_schema_array) {
            $schema['items']['properties'] = $properties;
        } else {
            $schema['properties'] = $properties;
        }
        return $schema;
    }

    private static function replacePartialObjectType(array $schema, string $api_version): array
    {
        $is_schema_array = array_key_exists('items', $schema) && !array_key_exists('properties', $schema);
        $full_schema_name = ($is_schema_array ? $schema['items']['x-full-schema'] : $schema['x-full-schema']) ?? null;
        if ($full_schema_name === null) {
            return $schema;
        }
        $full_schema = OpenAPIGenerator::getComponentSchemas($api_version)[$full_schema_name] ?? null;
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

    /**
     * Find the related itemtype of the given partial or complete schema.
     * @param array $schema The schema to find the itemtype of.
     * @param string $api_version The API version
     * @return string|null The itemtype of the given schema or null if it could not be found.
     */
    private static function getSchemaItemtype(array $schema, string $api_version): ?string
    {
        $is_schema_array = array_key_exists('items', $schema) && !array_key_exists('properties', $schema);
        $real_schema = $is_schema_array ? $schema['items'] : $schema;
        if (isset($real_schema['x-itemtype'])) {
            return $real_schema['x-itemtype'];
        }
        if (isset($real_schema['x-full-schema'])) {
            $full_schema = OpenAPIGenerator::getComponentSchemas($api_version)[$real_schema['x-full-schema']] ?? null;
            if ($full_schema !== null) {
                return $full_schema['x-itemtype'] ?? null;
            }
        }
        return null;
    }

    /**
     * Attempt to remove a property that was not requested.
     * This function will evaluate if the property is required in a few ways and if it is, it will be kept but hidden from the final result.
     * Otherwise, the property will be simply removed.
     * <br>
     * Evaluations:
     * <ul>
     *    <li>Is this a primary key?</li>
     *    <li>Is this referenced by an `x-mapped-from` property?</li>
     * </ul>
     * @param class-string<\CommonDBTM>|null $itemtype The itemtype of the object that contains the property. Used to determine the index field name.
     * @param string $property The key of the property to remove or hide.
     * @param array $schema_properties The schema properties of the object that contains the property.
     * @param array $other_requested The other properties that were requested.
     * @param string|null $object_prop_key The key of the object in the parent object. If not null, this is used complete the $other_requested properties.
     * @return array The modified object schema
     */
    private static function hideOrRemoveProperty(?string $itemtype, string $property, array $schema_properties, array $other_requested, ?string $object_prop_key = null): array
    {
        $field = $schema_properties[$property]['x-field'] ?? $property;
        $is_primary_key = $field === 'id';
        if ($itemtype !== null) {
            $is_primary_key = $is_primary_key || $field === $itemtype::getIndexName();
        }

        if ($is_primary_key) {
            $schema_properties[$property]['x-hidden'] = true;
            return $schema_properties;
        }

        $is_hidden = false;
        foreach ($other_requested as $requested_property) {
            $requested_property_schema = $schema_properties[$requested_property] ?? null;
            if ($requested_property_schema === null) {
                continue;
            }
            $mapped_from = $requested_property_schema['x-mapped-from'] ?? null;
            if ($mapped_from === $property) {
                $schema_properties[$property]['x-hidden'] = true;
                $is_hidden = true;
            }
            if ($object_prop_key !== null && $mapped_from === "{$object_prop_key}.{$property}") {
                $schema_properties[$property]['x-hidden'] = true;
                $is_hidden = true;
            }
        }
        if (!$is_hidden) {
            unset($schema_properties[$property]);
        }
        return $schema_properties;
    }
}
