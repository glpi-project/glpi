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

namespace Glpi\Api\HL\Doc;

use Glpi\Api\HL\Router;
use Glpi\Toolbox\ArrayPathAccessor;

use function Safe\preg_match;

/**
 * Central validation of HLAPI content against raw (array) schemas.
 *
 * It is used for the input of requests (query and header parameters, then the body once the schema is known) and for the
 * content of responses in tests. Raw schemas are used rather than {@link Schema} objects because the latter do not keep
 * constraints such as `required`, `maxLength` or `minimum`.
 *
 * All the problems found are collected (the validation does not stop at the first one) in the following format:
 * `['property.path' => [['error' => 'maxLength', 'message' => '...', 'maxLength' => 32], ...]]`
 *
 * Input validation is backwards compatible: before API v4 only the constraints that were always validated
 * (required, maxLength, minimum, maximum, multipleOf and pattern) are checked. The type, format and enum checks are only
 * applied to input from API v4. See {@link self::isStrictVersion()}.
 *
 * Options (all optional):
 * - `api_version` (string): The requested API version. Defaults to the current API version.
 * - `check_required` (bool): Whether missing required properties are reported. Only applicable to input. Defaults to false.
 * - `type_format_only` (bool): Validate only the type and format of the content (like for responses) instead of validating input. Defaults to false.
 *
 * @phpstan-type ValidationError array{error: string, message: string}&array<string, mixed>
 * @phpstan-type ValidationOptions array{api_version?: string, check_required?: bool, type_format_only?: bool}
 */
final class SchemaValidator
{

    /**
     * Whether the type, format and enum of input are validated for the given API version.
     * Validating them for earlier versions would reject input that has always been accepted.
     * @param string $api_version
     * @return bool
     */
    public static function isStrictVersion(string $api_version): bool
    {
        return version_compare($api_version, '4.0.0', '>=');
    }

    /**
     * Validate content against the properties of a schema.
     * @param array<string, mixed> $schema The raw schema. It must have a `properties` key.
     * @param array<string, mixed> $content The input or response content
     * @param array $options
     * @phpstan-param ValidationOptions $options
     * @return array<string, array>
     * @phpstan-return array<string, ValidationError[]>
     */
    public static function validate(array $schema, array $content, array $options = []): array
    {
        $errors = [];

        if ($options['type_format_only'] ?? false) {
            // Walk the schema and look up each value in the content. Arrays are not collapsed so they are validated as arrays.
            foreach (Schema::flattenProperties($schema['properties'], '', false) as $path => $prop) {
                $prop_errors = self::validateValue($prop, ArrayPathAccessor::getElementByArrayPath($content, $path), $options);
                if ($prop_errors !== []) {
                    $errors[$path] = $prop_errors;
                }
            }
            return $errors;
        }

        $flattened_properties = Schema::flattenProperties($schema['properties']);

        if ($options['check_required'] ?? false) {
            foreach ($flattened_properties as $path => $prop) {
                if (($prop['required'] ?? false) && !ArrayPathAccessor::hasElementByArrayPath($content, $path)) {
                    $errors[$path][] = [
                        'error' => 'required',
                        'message' => 'This field is required',
                    ];
                }
            }
        }

        // Properties not in the schema are ignored
        foreach ($content as $key => $value) {
            if (!isset($flattened_properties[$key])) {
                continue;
            }
            foreach (self::validateValue($flattened_properties[$key], $value, $options) as $error) {
                $errors[$key][] = $error;
            }
        }

        return $errors;
    }

    /**
     * Validate a single value against a (flattened) property definition.
     * @param array<string, mixed> $prop The raw property definition (type, format, enum, maxLength, ...)
     * @param mixed $value
     * @param array $options
     * @phpstan-param ValidationOptions $options
     * @return array
     * @phpstan-return ValidationError[]
     */
    public static function validateValue(array $prop, mixed $value, array $options = []): array
    {
        // Null is acceptable for any type, format and constraint. Whether a value is required is checked separately.
        if ($value === null) {
            return [];
        }

        $type_format_only = $options['type_format_only'] ?? false;
        $api_version = $options['api_version'] ?? Router::API_VERSION;
        $type = $prop['type'] ?? Schema::TYPE_STRING;

        // Array properties are collapsed to their items schema when flattening input properties. In that case, each item needs to be valid.
        // This is only done from the strict API version as earlier versions never validated the items.
        if (!$type_format_only && self::isStrictVersion($api_version) && is_array($value) && !in_array($type, [Schema::TYPE_ARRAY, Schema::TYPE_OBJECT], true)) {
            $errors = [];
            foreach ($value as $item) {
                foreach (self::validateValue($prop, $item, $options) as $error) {
                    if (!in_array($error, $errors, true)) {
                        $errors[] = $error;
                    }
                }
            }
            return $errors;
        }

        $errors = [];
        if ($type_format_only || self::isStrictVersion($api_version)) {
            $errors = self::validateTypeAndFormat($prop, $value, $type_format_only);
            if ($errors === [] && !$type_format_only) {
                $errors = self::validateEnum($prop, $value);
            }
        }
        if (!$type_format_only) {
            $errors = [...$errors, ...self::validateConstraints($prop, $value)];
        }
        return $errors;
    }

    /**
     * Convert a value that can reasonably be interpreted as the given scalar type (such as a value from a query string).
     * Values that cannot be interpreted as the type are returned as is.
     * @param string $type
     * @param mixed $value
     * @return mixed
     */
    private static function coerceScalar(string $type, mixed $value): mixed
    {
        if (!is_scalar($value)) {
            return $value;
        }
        switch ($type) {
            case Schema::TYPE_INTEGER:
                if (is_string($value) && filter_var($value, FILTER_VALIDATE_INT) !== false) {
                    return (int) $value;
                }
                // JSON encoders may write integers as floats (like 5.0)
                if (is_float($value) && floor($value) === $value) {
                    return (int) $value;
                }
                break;
            case Schema::TYPE_NUMBER:
                if (is_string($value) && is_numeric($value)) {
                    return $value + 0;
                }
                break;
            case Schema::TYPE_BOOLEAN:
                // Accepts true, false, 1, 0, on, off, yes, no and their string equivalents
                if (!is_bool($value)) {
                    $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($bool !== null) {
                        return $bool;
                    }
                }
                break;
        }
        return $value;
    }

    /**
     * @param array<string, mixed> $prop
     * @param mixed $value
     * @param bool $strict_content If true, the value is expected to already be of the right type (like a response) instead of being input that may be coerced.
     * @return array
     * @phpstan-return ValidationError[]
     */
    private static function validateTypeAndFormat(array $prop, mixed $value, bool $strict_content): array
    {
        $type = $prop['type'] ?? Schema::TYPE_STRING;
        $format = $prop['format'] ?? null;

        if ($strict_content) {
            // Content that was encoded as JSON and decoded may have a wrong type. For example, a 0 or 1 integer value may be decoded as a boolean.
            if (is_bool($value) && $type === Schema::TYPE_INTEGER) {
                $value = (int) $value;
            }
            // Schemas without an explicit format use the default format of their type
            $format ??= Schema::getDefaultFormatForType($type);
        } else {
            $value = self::coerceScalar($type, $value);
        }

        $type_match = match ($type) {
            Schema::TYPE_STRING => is_string($value),
            Schema::TYPE_INTEGER => is_int($value),
            Schema::TYPE_NUMBER => is_int($value) || is_float($value),
            Schema::TYPE_BOOLEAN => is_bool($value),
            Schema::TYPE_ARRAY, Schema::TYPE_OBJECT => is_array($value),
            default => false,
        };
        if (!$type_match) {
            return [[
                'error' => 'type',
                'message' => "This field must be of type $type",
                'type' => $type,
            ]];
        }

        $format_match = match ($format) {
            Schema::FORMAT_BOOLEAN_BOOLEAN => is_bool($value),
            Schema::FORMAT_INTEGER_INT32 => (abs($value) & 0x7FFFFFFF) === abs($value),
            Schema::FORMAT_INTEGER_INT64 => (abs($value) & 0x7FFFFFFFFFFFFFFF) === abs($value),
            // Double: float and has 2 or less decimal places. Integers are also accepted. Only applied to content as it is a restriction of what the API returns.
            Schema::FORMAT_NUMBER_DOUBLE => !$strict_content || is_int($value) || (is_float($value) && (strlen(substr(strrchr((string) $value, '.') ?: '', 1)) <= 2)),
            Schema::FORMAT_NUMBER_FLOAT => is_int($value) || is_float($value),
            // Binary: binary data like used for Files
            Schema::FORMAT_STRING_BINARY, Schema::FORMAT_STRING_PASSWORD, Schema::FORMAT_STRING_STRING => is_string($value),
            // Byte: base64 encoded string
            Schema::FORMAT_STRING_BYTE => is_string($value) && preg_match('/^[a-zA-Z0-9\/+]*={0,2}$/', $value) === 1,
            // Date: valid RFC3339 date
            Schema::FORMAT_STRING_DATE => is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1,
            // Date-time: valid RFC3339 date-time
            Schema::FORMAT_STRING_DATE_TIME => is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?(Z|(\+|-)\d{2}:\d{2})$/', $value) === 1,
            default => true,
        };
        if (!$format_match) {
            return [[
                'error' => 'format',
                'message' => "This field must match the format $format",
                'format' => $format,
            ]];
        }

        return [];
    }

    /**
     * @param array<string, mixed> $prop
     * @param mixed $value
     * @return array
     * @phpstan-return ValidationError[]
     */
    private static function validateEnum(array $prop, mixed $value): array
    {
        if (!isset($prop['enum']) || !is_array($prop['enum'])) {
            return [];
        }
        // Compare the same way the values were coerced for the type check
        $value = self::coerceScalar($prop['type'] ?? Schema::TYPE_STRING, $value);
        foreach ($prop['enum'] as $allowed) {
            if ($allowed === $value || (is_scalar($allowed) && is_scalar($value) && (string) $allowed === (string) $value)) {
                return [];
            }
        }
        return [[
            'error' => 'enum',
            'message' => 'This field must be one of: ' . implode(', ', array_map(strval(...), $prop['enum'])),
            'enum' => $prop['enum'],
        ]];
    }

    /**
     * Validate the constraints that are checked for all API versions.
     * @param array<string, mixed> $prop
     * @param mixed $value
     * @return array
     * @phpstan-return ValidationError[]
     */
    private static function validateConstraints(array $prop, mixed $value): array
    {
        $errors = [];

        if (isset($prop['maxLength']) && is_string($value) && strlen($value) > $prop['maxLength']) {
            $errors[] = [
                'error' => 'maxLength',
                'message' => "This field must be at most {$prop['maxLength']} characters long",
                'maxLength' => $prop['maxLength'],
            ];
        }

        $min = $prop['minimum'] ?? null;
        $max = $prop['maximum'] ?? null;
        $multiple_of = $prop['multipleOf'] ?? null;
        if ($min !== null && $max !== null && is_numeric($value) && ($value < $min || $value > $max)) {
            $errors[] = [
                'error' => 'range',
                'message' => "This field must be between $min and $max",
                'minimum' => $min,
                'maximum' => $max,
            ];
        } elseif ($min !== null && is_numeric($value) && $value < $min) {
            $errors[] = [
                'error' => 'minimum',
                'message' => "This field must be at least $min",
                'minimum' => $min,
            ];
        } elseif ($max !== null && is_numeric($value) && $value > $max) {
            $errors[] = [
                'error' => 'maximum',
                'message' => "This field must be at most $max",
                'maximum' => $max,
            ];
        }
        if ($multiple_of !== null && is_numeric($value) && fmod((float) $value, (float) $multiple_of) !== 0.0) {
            $errors[] = [
                'error' => 'multipleOf',
                'message' => "This field must be a multiple of $multiple_of",
                'multipleOf' => $multiple_of,
            ];
        }
        if (isset($prop['pattern']) && is_string($value) && !preg_match('/' . $prop['pattern'] . '/', $value)) {
            $errors[] = [
                'error' => 'pattern',
                'message' => "This field must match the pattern {$prop['pattern']}",
                'pattern' => $prop['pattern'],
            ];
        }

        return $errors;
    }
}
