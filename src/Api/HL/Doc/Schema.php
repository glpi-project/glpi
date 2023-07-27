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

namespace Glpi\Api\HL\Doc;

/**
 * @implements \ArrayAccess<string, null|string|array<string, Schema>>
 */
class Schema implements \ArrayAccess
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
        private ?string $pattern = null
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
        } else if ($this->getType() === self::TYPE_ARRAY) {
            $items = $this->getItems();
            if ($items !== null) {
                $r['items'] = $items->toArray();
            }
        } else if ($this->enum !== null) {
            $r['enum'] = $this->enum;
        }
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
        return new Schema(type: $type, format: $format, properties: $properties, items: $items, enum: $enum, pattern: $pattern);
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

    public static function getJoins(array $props, string $prefix = ''): array
    {
        //Walk through properties recursively to find ones of type object with a "x-join" property. Return array of "x-join" properties
        $joins = [];
        foreach ($props as $name => $prop) {
            if ($prop['type'] === self::TYPE_OBJECT && isset($prop['x-join'])) {
                $joins[$name] = $prop['x-join'] + ['parent_type' => self::TYPE_OBJECT];
            } else if ($prop['type'] === self::TYPE_ARRAY && isset($prop['items']['x-join'])) {
                $joins[$name] = $prop['items']['x-join'] + ['parent_type' => self::TYPE_ARRAY];
            } else if ($prop['type'] === self::TYPE_OBJECT && isset($prop['properties'])) {
                $joins += self::getJoins($prop['properties'], $prefix . $name . '.');
            }
        }
        return $joins;
    }

    public static function flattenProperties(array $props, string $prefix = '', bool $collapse_array_types = true): array
    {
        $flattened = [];
        foreach ($props as $name => $prop) {
            if ($collapse_array_types && $prop['type'] === self::TYPE_ARRAY) {
                $prop = $prop['items'];
            }
            if (array_key_exists('type', $prop) && $prop['type'] === self::TYPE_OBJECT) {
                $flattened += self::flattenProperties($prop['properties'], $prefix . $name . '.', $collapse_array_types);
            } else {
                $flattened[$prefix . $name] = $prop;
            }
        }
        return $flattened;
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
            self::TYPE_NUMBER => is_float($value),
            self::TYPE_BOOLEAN => is_bool($value),
            self::TYPE_ARRAY, self::TYPE_OBJECT => is_array($value),
            default => false
        };
        if (!$type_match) {
            return false;
        }

        // Check format
        /** @var self::FORMAT_* $format_match */
        $format_match = match ($format) {
            self::FORMAT_BOOLEAN_BOOLEAN => is_bool($value),
            self::FORMAT_INTEGER_INT32 => ((abs($value) & 0x7FFFFFFF) === abs($value)),
            self::FORMAT_INTEGER_INT64 => ((abs($value) & 0x7FFFFFFFFFFFFFFF) === abs($value)),
            // Double: float and has 2 or less decimal places
            self::FORMAT_NUMBER_DOUBLE => is_float($value) && (strlen(substr(strrchr((string)$value, "."), 1)) <= 2),
            self::FORMAT_NUMBER_FLOAT => is_float($value),
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
        return $format_match;
    }

    public function isValid(array $content): bool
    {
        $flattened_schema = self::flattenProperties($this->toArray()['properties'], '', false);

        foreach ($flattened_schema as $sk => $sv) {
            // Get value from original content by the array path $sk
            $path_arr = explode('.', $sk);
            $current = $content;
            foreach ($path_arr as $path) {
                if (!is_array($current)) {
                    continue;
                }
                if (array_key_exists($path, $current)) {
                    $current = $current[$path];
                } else {
                    return false;
                }
            }
            $cv = $current;

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
}
