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

namespace Glpi\Api\HL\Doc;

use ArrayAccess;
use CommonGLPI;
use Glpi\Api\HL\Router;
use Glpi\Toolbox\ArrayPathAccessor;

use function Safe\preg_match;
use function Safe\strtotime;

/**
 * @implements ArrayAccess<string, null|string|array<string, Schema>>
 */
class Schema implements ArrayAccess
{
    public const TYPE_STRING = 'string';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_NUMBER = 'number';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_OBJECT = 'object';
    public const TYPE_ARRAY = 'array';

    public const FORMAT_STRING_BYTE = 'byte';
    public const FORMAT_STRING_BINARY = 'binary';
    public const FORMAT_STRING_DATE = 'date';
    public const FORMAT_STRING_DATE_TIME = 'date-time';
    public const FORMAT_STRING_PASSWORD = 'password';
    public const FORMAT_STRING_STRING = 'string';
    public const FORMAT_INTEGER_INT32 = 'int32';
    public const FORMAT_INTEGER_INT64 = 'int64';
    public const FORMAT_NUMBER_DOUBLE = 'double';
    public const FORMAT_NUMBER_FLOAT = 'float';
    public const FORMAT_BOOLEAN_BOOLEAN = 'boolean';

    public const PATTERN_UUIDV4 = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

    public function __construct(
        private string $type,
        private ?string $format = null,
        /** @var array<string, Schema> $properties */
        private array $properties = [],
        /** @var ?Schema $items */
        private ?Schema $items = null,
        /** @var array|null $enum */
        private ?array $enum = null,
        private ?string $pattern = null,
        private mixed $default = null,
        private array $extra_data = []
    ) {
        if ($this->format === null) {
            $this->format = self::getDefaultFormatForType($this->type);
        }
    }

    /**
     * @param string $type
     * @phpstan-param self::TYPE_* $type
     * @return ?string
     * @phpstan-return self::FORMAT_*|null
     */
    public static function getDefaultFormatForType(string $type): ?string
    {
        return match ($type) {
            self::TYPE_STRING => self::FORMAT_STRING_STRING,
            self::TYPE_INTEGER => self::FORMAT_INTEGER_INT32,
            self::TYPE_NUMBER => self::FORMAT_NUMBER_DOUBLE,
            self::TYPE_BOOLEAN => self::FORMAT_BOOLEAN_BOOLEAN,
            default => null
        };
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function getFormat(): ?string
    {
        return $this->format;
    }

    /**
     * @return string|null
     */
    public function getPattern(): ?string
    {
        return $this->pattern;
    }

    /**
     * @return array<string, array{type: string, format?: string, properties?: array, items?: array}>
     */
    public function getProperties(): array
    {
        $props = [];
        foreach ($this->properties as $name => $schema) {
            $props[$name] = $schema->toArray();
        }
        return $props;
    }

    public function getItems(): ?Schema
    {
        return $this->items;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $r = [
            'type' => $this->getType(),
        ];
        if ($this->getFormat()) {
            $r['format'] = $this->getFormat();
        }
        if ($this->getPattern()) {
            $r['pattern'] = $this->getPattern();
        }
        if ($this->getType() === self::TYPE_OBJECT) {
            $r['properties'] = $this->getProperties();
        } elseif ($this->getType() === self::TYPE_ARRAY) {
            $items = $this->getItems();
            if ($items !== null) {
                $r['items'] = $items->toArray();
            }
        } elseif ($this->enum !== null) {
            $r['enum'] = $this->enum;
        }
        if ($this->default !== null) {
            $r['default'] = $this->default;
        }
        //TODO Check if we can append extra_data here
        return $r;
    }

    public static function fromArray(array $schema): Schema
    {
        $type = $schema['type'] ?? self::TYPE_STRING;
        $format = $schema['format'] ?? null;
        $pattern = $schema['pattern'] ?? null;
        $properties = [];
        if ($type === self::TYPE_OBJECT) {
            foreach ($schema['properties'] as $name => $prop) {
                $properties[$name] = self::fromArray($prop);
            }
        }
        $items = null;
        if ($type === self::TYPE_ARRAY) {
            $items = self::fromArray($schema['items']);
        }
        $enum = null;
        if (isset($schema['enum'])) {
            $enum = $schema['enum'];
        }
        $default = $schema['default'] ?? null;
        return new Schema(
            type: $type,
            format: $format,
            properties: $properties,
            items: $items,
            enum: $enum,
            pattern: $pattern,
            default: $default
        );
    }

    public function offsetExists(mixed $offset): bool
    {
        return property_exists($this, $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (property_exists($this, $offset)) {
            return $this->$offset;
        }
        return null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        //Not supported
    }

    public function offsetUnset(mixed $offset): void
    {
        //Not supported
    }

    public static function getJoins(array $props, string $prefix = '', ?array $parent_join = null): array
    {
        //Walk through properties recursively to find ones of type object with a "x-join" property. Return array of "x-join" properties
        $joins = [];
        $fn_add_parent_hint = static function ($join, $prefix) use ($parent_join) {
            $prefix = str_replace('.', chr(0x1F), rtrim($prefix, '.'));
            if ($prefix === '' || $parent_join === null) {
                return $join;
            }
            if (isset($join['ref-join']['fkey'])) {
                $join['ref-join']['join_parent'] = $prefix;
            } elseif (isset($join['fkey'])) {
                $join['join_parent'] = $prefix;
            }
            return $join;
        };
        foreach ($props as $name => $prop) {
            if ($prop['type'] === self::TYPE_OBJECT && isset($prop['x-join'])) {
                $new_join = $prop['x-join'] + ['parent_type' => self::TYPE_OBJECT];
                $joins[$prefix . $name] = $fn_add_parent_hint($new_join, $prefix);
                $joins += self::getJoins($prop['properties'], $prefix . $name . '.', $new_join);
            } elseif ($prop['type'] === self::TYPE_ARRAY && isset($prop['items']['x-join'])) {
                $new_join = $prop['items']['x-join'] + ['parent_type' => self::TYPE_ARRAY];
                $joins[$prefix . $name] = $fn_add_parent_hint($new_join, $prefix);
                $joins += self::getJoins($prop['items']['properties'], $prefix . $name . '.', $new_join);
            } elseif ($prop['type'] === self::TYPE_OBJECT && isset($prop['properties'])) {
                if (isset($prop['x-join'])) {
                    $parent_join = $prop['x-join'];
                }
                $joins += self::getJoins($prop['properties'], $prefix . $name . '.', $parent_join);
            } else {
                // Scalar property joined from another table
                if (isset($prop['x-join'])) {
                    $new_join = $prop['x-join'] + ['parent_type' => self::TYPE_OBJECT];
                    $joins[$prefix . $name] = $fn_add_parent_hint($new_join, $prefix);
                }
            }
        }
        if ($prefix === '') {
            // Fix parent_join for all joins
            foreach ($joins as $join_name => $join) {
                if (isset($join['join_parent'])) {
                    // This join is supposed to have a parent
                    // The set parent may not be correct currently. The join's parent may in fact be an ancestor of the one set
                    // We need to check if the current parent exists in the list of joins. If not, we need to find the correct parent by walking up the tree
                    $parent = $join['join_parent'];
                    while ($parent !== '') {
                        if (isset($joins[$parent])) {
                            $joins[$join_name]['join_parent'] = $parent;
                            break;
                        }
                        $parent = substr($parent, 0, strrpos($parent, chr(0x1F)));
                    }
                }
            }
        }
        return $joins;
    }

    public static function flattenProperties(array $props, string $prefix = '', bool $collapse_array_types = true, ?array $parent_obj = null): array
    {
        $flattened = [];
        foreach ($props as $name => $prop) {
            if ($collapse_array_types && $prop['type'] === self::TYPE_ARRAY) {
                $prop = $prop['items'];
            }
            if (array_key_exists('type', $prop) && $prop['type'] === self::TYPE_OBJECT) {
                $is_mapped_obj = isset($prop['x-mapped-from']);
                $new_props = self::flattenProperties($prop['properties'], $prefix . $name . '.', $collapse_array_types, $prop);
                if ($is_mapped_obj) {
                    foreach ($new_props as &$new_prop_data) {
                        $new_prop_data['x-mapped-property'] = true;
                    }
                    unset($new_prop_data);
                }
                $flattened += $new_props;
            } else {
                $flattened[$prefix . $name] = [
                    ...$prop,
                    ...[
                        'x-full-schema' => $parent_obj['x-full-schema'] ?? null,
                    ],
                ];
            }
        }
        return $flattened;
    }

    /**
     * Filter a schema by the requested API version. May return null if the entire schema is not applicable to the requested version.
     * @param array $schema
     * @param string $api_version
     * @return array|null The filtered schema or null
     */
    public static function filterSchemaByAPIVersion(array $schema, string $api_version): ?array
    {
        $schema_versions = [
            'introduced' => $schema['x-version-introduced'],
            'deprecated' => $schema['x-version-deprecated'] ?? null,
            'removed' => $schema['x-version-removed'] ?? null,
        ];

        // Check if the schema itself is applicable to the requested version
        // If the requested version is before the introduction of the schema, or after the removal of the schema, it is not applicable
        // Deprecation has no effect here
        if (
            version_compare($api_version, $schema_versions['introduced']) < 0
            || ($schema_versions['removed'] !== null && version_compare($api_version, $schema_versions['removed']) >= 0)
        ) {
            return null;
        }

        $schema['properties'] = self::filterPropertiesByAPIVersion($schema['properties'], $schema_versions, $api_version);

        // If all properties were filtered out, the schema can be considered not applicable
        if (empty($schema['properties'])) {
            return null;
        }

        return $schema;
    }

    /**
     * Recursively filter properties by the requested API version
     * @param array $props The properties to filter
     * @param array{introduced: string, deprecated?: string, removed?: string} $schema_versions The versioning info from the schema
     * @param string $api_version The API version to filter by
     * @return array The filtered properties
     */
    private static function filterPropertiesByAPIVersion(array $props, array $schema_versions, string $api_version): array
    {
        $filtered_props = [];
        foreach ($props as $name => $prop) {
            $prop_versions = [
                'introduced' => $prop['x-version-introduced'] ?? $schema_versions['introduced'],
                'deprecated' => $prop['x-version-deprecated'] ?? $schema_versions['deprecated'],
                'removed' => $prop['x-version-removed'] ?? $schema_versions['removed'],
            ];
            // Check if the property is applicable to the requested version
            // If the requested version is before the introduction of the property, or after the removal of the property, it is not applicable
            // Deprecation has no effect here
            if (
                version_compare($api_version, $prop_versions['introduced']) < 0
                || ($prop_versions['removed'] !== null && version_compare($api_version, $prop_versions['removed']) >= 0)
            ) {
                continue;
            }
            $filtered_prop = $prop;
            if ($prop['type'] === self::TYPE_OBJECT) {
                if (!empty($prop['properties'])) {
                    $filtered_prop['properties'] = self::filterPropertiesByAPIVersion($prop['properties'], $prop_versions, $api_version);
                }
            } elseif ($prop['type'] === self::TYPE_ARRAY && isset($prop['items'])) {
                if (!empty($prop['items']['properties'])) {
                    $filtered_prop['items']['properties'] = self::filterPropertiesByAPIVersion($prop['items']['properties'], $prop_versions, $api_version);
                }
            }
            $filtered_props[$name] = $filtered_prop;
        }
        return $filtered_props;
    }

    private static function validateTypeAndFormat(string $type, string $format, mixed $value): bool
    {
        // For now at least, null is acceptable for any type and format
        if ($value === null) {
            return true;
        }

        // In some cases, we may be validating content that was already encoded as JSON and the decode process ruins the type
        // For example, a 0 or 1 integer value may be decoded as a boolean
        if (($value === false || $value === true) && $type === self::TYPE_INTEGER) {
            $value = (int) $value;
        }

        $type_match = match ($type) {
            self::TYPE_STRING => is_string($value),
            self::TYPE_INTEGER => is_int($value),
            self::TYPE_NUMBER => is_int($value) || is_float($value),
            self::TYPE_BOOLEAN => is_bool($value),
            self::TYPE_ARRAY, self::TYPE_OBJECT => is_array($value),
            default => false
        };
        if (!$type_match) {
            return false;
        }

        // Check format
        /** @var self::FORMAT_* $format */
        return match ($format) {
            self::FORMAT_BOOLEAN_BOOLEAN => is_bool($value),
            self::FORMAT_INTEGER_INT32 => ((abs($value) & 0x7FFFFFFF) === abs($value)),
            self::FORMAT_INTEGER_INT64 => ((abs($value) & 0x7FFFFFFFFFFFFFFF) === abs($value)),
            // Double: float and has 2 or less decimal places
            // We also accept integers as doubles (no decimal places specified)
            self::FORMAT_NUMBER_DOUBLE => is_int($value) || (is_float($value) && (strlen(substr(strrchr((string) $value, "."), 1)) <= 2)),
            self::FORMAT_NUMBER_FLOAT => is_int($value) || is_float($value),
            // Binary: binary data like used for Files
            self::FORMAT_STRING_BINARY, self::FORMAT_STRING_PASSWORD, self::FORMAT_STRING_STRING => is_string($value),
            // Byte: base64 encoded string
            self::FORMAT_STRING_BYTE => is_string($value) && preg_match('/^[a-zA-Z0-9\/+]*={0,2}$/', $value),
            // Date: valid RFC3339 date
            self::FORMAT_STRING_DATE => is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value),
            // Date-time: valid RFC3339 date-time
            self::FORMAT_STRING_DATE_TIME => is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?(Z|(\+|-)\d{2}:\d{2})$/', $value),
            default => true
        };
    }

    public function isValid(array $content, ?string $operation = null): bool
    {
        $flattened_schema = self::flattenProperties($this->toArray()['properties'], '', false);

        foreach ($flattened_schema as $sk => $sv) {
            $ignored = false;
            // Get value from original content by the array path $sk
            $cv = ArrayPathAccessor::getElementByArrayPath($content, $sk);
            if ($cv === null) {
                if ($operation === 'read' && ($sv['writeOnly'] ?? false)) {
                    $ignored = true;
                } elseif ($operation === 'write' && ($sv['readOnly'] ?? false)) {
                    $ignored = true;
                }
            }
            if ($ignored) {
                // Property was not found, but it wasn't applicable to the operation
                continue;
            }

            // Verify that the type is correct
            if (!self::validateTypeAndFormat($sv['type'], $sv['format'] ?? '', $cv)) {
                return false;
            }
        }
        return true;
    }

    public function castProperties(array $content): array
    {
        $flattened_schema = self::flattenProperties($this->toArray()['properties'], '', false);

        foreach ($flattened_schema as $sk => $sv) {
            // Get value from original content by the array path $sk
            $path_arr = explode('.', $sk);
            $current = &$content;
            $no_match = false;
            foreach ($path_arr as $path) {
                if (isset($current[$path])) {
                    $current = &$current[$path];
                } else {
                    $no_match = true;
                }
            }
            if ($no_match) {
                continue;
            }
            $cv = &$current;

            // Cast the value to the correct type
            $cv = match ($sv['type']) {
                self::TYPE_STRING => (string) $cv,
                self::TYPE_INTEGER => (int) $cv,
                self::TYPE_NUMBER => (float) $cv,
                self::TYPE_BOOLEAN => (bool) $cv,
                default => $cv
            };

            // If the value is a datetime, cast to RFC3339
            if (isset($sv['format']) && $sv['format'] === self::FORMAT_STRING_DATE_TIME) {
                $cv = date(DATE_RFC3339, strtotime($cv));
            }
        }
        return $content;
    }

    /**
     * Combine multiple schemas into a single 'union' schema that allows searching across all of them
     * @param non-empty-array<string, array{x-itemtype: string, properties: mixed}> $schemas
     * @return array{x-subtypes: list<array{schema_name: string, itemtype: string}>, type: self::TYPE_OBJECT, properties: array}
     * @see getUnionSchemaForItemtypes
     */
    public static function getUnionSchema(array $schemas): array
    {
        $shared_properties = array_intersect_key(...array_column($schemas, 'properties'));
        $subtype_info = [];
        foreach ($schemas as $n => $s) {
            $subtype_info[] = [
                'schema_name' => $n,
                'itemtype' => $s['x-itemtype'],
            ];
        }
        return [
            'x-subtypes' => $subtype_info,
            'type' => self::TYPE_OBJECT,
            'properties' => $shared_properties,
        ];
    }

    /**
     * Combine schemas related to multiple GLPI itemtypes into a single 'union' schema that allows searching across all of them
     * @param non-empty-array<string, class-string<CommonGLPI>> $itemtypes
     * @return array{x-subtypes: list<array{schema_name: string, itemtype: string}>, type: self::TYPE_OBJECT, properties: array}
     * @see getUnionSchema
     */
    public static function getUnionSchemaForItemtypes(array $itemtypes, string $api_version): array
    {
        $schemas = [];
        $controllers = Router::getInstance()->getControllers();
        foreach ($controllers as $controller) {
            $known_schemas = $controller::getKnownSchemas($api_version);
            foreach ($known_schemas as $schema_name => $schema) {
                if (array_key_exists('x-itemtype', $schema) && in_array($schema['x-itemtype'], $itemtypes, true)) {
                    $schemas[$schema_name] = $schema;
                }
            }
        }
        return self::getUnionSchema($schemas);
    }
}
