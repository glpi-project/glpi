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

use Exception;
use Glpi\Toolbox\ArrayPathAccessor;
use Safe\DateTime;

use function Safe\preg_match;

/**
 * Central validation of HLAPI content against schemas.
 *
 * It is used for the input of requests (query and header parameters, then the body once the schema is known) and for the
 * content of responses in tests.
 *
 * Options (all true by default):
 * - `check_required`: Report missing required properties.
 * - `check_type_format`: Validate the type and format of the values.
 * - `check_constraints`: Validate the constraints (`enum`, `maxLength`, `minimum`, `maximum`, `multipleOf` and `pattern`).
 * - `allow_coercion`: Convert values that can reasonably be interpreted as the expected type (like the string "5" for an
 *   integer, "yes" for a boolean, the integer 5 for a string, a single value for an array or any date understood by `DateTime`
 *   for a date or date-time) to that type, in the validated content. When disabled, the values must already have the right type and format.
 *
 * @phpstan-type ValidationError array{error: string, message: string}&array<string, mixed>
 * @phpstan-type ValidationOptions array{
 *     check_required?: bool,
 *     check_type_format?: bool,
 *     check_constraints?: bool,
 *     allow_coercion?: bool
 * }
 */
final class SchemaValidator
{
    /**
     * Validate content against the properties of a schema. All the problems found are collected, not only the first one.
     * @param array<string, mixed> $schema The raw schema. It must have a `properties` key.
     * @param array<mixed> $content The input or response content. Values are replaced by their coerced value if coercion is allowed.
     * @param ValidationOptions $options
     * @return array<string, array>
     * @phpstan-return array<string, ValidationError[]> The errors by property path
     */
    public static function validate(array $schema, array &$content, array $options = []): array
    {
        $options += [
            'check_required' => true,
            'check_type_format' => true,
            'check_constraints' => true,
            'allow_coercion' => true,
        ];
        $errors = [];

        foreach (Schema::flattenProperties($schema['properties'], '', false) as $path => $prop) {
            $value = ArrayPathAccessor::getElementByArrayPath($content, $path);
            // Null is valid for any type, format and constraint. Whether a value is required is checked separately.
            if ($value === null) {
                if ($options['check_required'] && ($prop['required'] ?? false)) {
                    $errors[$path][] = ['error' => 'required', 'message' => 'This field is required'];
                }
                continue;
            }

            $original_value = $value;
            $prop_errors = self::validateValue($prop, $value, $options);
            if ($value !== $original_value) {
                ArrayPathAccessor::setElementByArrayPath($content, $path, $value);
            }
            if ($prop_errors !== []) {
                $errors[$path] = $prop_errors;
            }
        }

        return $errors;
    }

    /**
     * Validate a single (non-null) value against a flattened property definition.
     * @param array<string, mixed> $prop
     * @param mixed $value The value. It is replaced by its coerced value if coercion is allowed.
     * @param array<string, bool> $options The options with all the defaults applied
     * @return array
     * @phpstan-return ValidationError[]
     */
    private static function validateValue(array $prop, mixed &$value, array $options): array
    {
        $type = $prop['type'] ?? Schema::TYPE_STRING;
        $format = $prop['format'] ?? Schema::getDefaultFormatForType($type);
        $prop_errors = [];

        if ($options['allow_coercion']) {
            $value = match ($type) {
                Schema::TYPE_STRING => is_int($value) || is_float($value) ? (string) $value : $value,
                // A single value is accepted as a list of one value
                Schema::TYPE_ARRAY => is_array($value) ? $value : [$value],
                Schema::TYPE_INTEGER => is_numeric($value) && (float) $value == (int) $value ? (int) $value : $value,
                Schema::TYPE_NUMBER => is_numeric($value) ? $value + 0 : $value,
                Schema::TYPE_BOOLEAN => is_scalar($value) ? (filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $value) : $value,
                default => $value,
            };
            // Dates and times in any format understood by DateTime are converted to the canonical format of their format
            if (is_string($value) && in_array($format, [Schema::FORMAT_STRING_DATE, Schema::FORMAT_STRING_DATE_TIME], true)) {
                try {
                    $value = (new DateTime($value))->format($format === Schema::FORMAT_STRING_DATE ? 'Y-m-d' : DATE_RFC3339);
                } catch (Exception) {
                    // Not a date. It is reported by the format check.
                }
            }
        }

        if ($options['check_type_format']) {
            $type_match = match ($type) {
                Schema::TYPE_STRING => is_string($value),
                // Content that was encoded as JSON and decoded may have a wrong type. For example, a 0 or 1 integer value may be decoded as a boolean.
                Schema::TYPE_INTEGER => is_int($value) || (is_bool($value) && !$options['allow_coercion']),
                Schema::TYPE_NUMBER => is_int($value) || is_float($value),
                Schema::TYPE_BOOLEAN => is_bool($value),
                Schema::TYPE_ARRAY, Schema::TYPE_OBJECT => is_array($value),
                default => false,
            };
            if (!$type_match) {
                $prop_errors[] = ['error' => 'type', 'message' => "This field must be of type $type", 'type' => $type];
            } elseif (!match ($format) {
                Schema::FORMAT_BOOLEAN_BOOLEAN => is_bool($value),
                Schema::FORMAT_INTEGER_INT32 => (abs((int) $value) & 0x7FFFFFFF) === abs((int) $value),
                Schema::FORMAT_INTEGER_INT64 => (abs((int) $value) & 0x7FFFFFFFFFFFFFFF) === abs((int) $value),
                // Double: integers and floats. Content (not coerced) must also have 2 or less decimal places.
                Schema::FORMAT_NUMBER_DOUBLE => $options['allow_coercion'] || is_int($value) || strlen(substr(strrchr((string) $value, '.') ?: '', 1)) <= 2,
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
            }) {
                $prop_errors[] = ['error' => 'format', 'message' => "This field must match the format $format", 'format' => $format];
            }
        }

        if ($options['check_constraints']) {
            $min = $prop['minimum'] ?? null;
            $max = $prop['maximum'] ?? null;
            $multiple_of = $prop['multipleOf'] ?? null;
            if (isset($prop['enum']) && $prop_errors === [] && !in_array($value, $prop['enum'], true)) {
                $prop_errors[] = ['error' => 'enum', 'message' => 'This field must be one of: ' . implode(', ', $prop['enum']), 'enum' => $prop['enum']];
            }
            if (isset($prop['maxLength']) && is_string($value) && strlen($value) > $prop['maxLength']) {
                $prop_errors[] = [
                    'error' => 'maxLength',
                    'message' => "This field must be at most {$prop['maxLength']} characters long",
                    'maxLength' => $prop['maxLength'],
                ];
            }
            if ($min !== null && $max !== null && is_numeric($value) && ($value < $min || $value > $max)) {
                $prop_errors[] = [
                    'error' => 'range',
                    'message' => "This field must be between $min and $max",
                    'minimum' => $min,
                    'maximum' => $max,
                ];
            } elseif ($min !== null && is_numeric($value) && $value < $min) {
                $prop_errors[] = [
                    'error' => 'minimum',
                    'message' => "This field must be at least $min",
                    'minimum' => $min,
                ];
            } elseif ($max !== null && is_numeric($value) && $value > $max) {
                $prop_errors[] = [
                    'error' => 'maximum',
                    'message' => "This field must be at most $max",
                    'maximum' => $max,
                ];
            }
            if ($multiple_of !== null && is_numeric($value) && fmod((float) $value, (float) $multiple_of) !== 0.0) {
                $prop_errors[] = [
                    'error' => 'multipleOf',
                    'message' => "This field must be a multiple of $multiple_of",
                    'multipleOf' => $multiple_of,
                ];
            }
            if (isset($prop['pattern']) && is_string($value) && !preg_match('/' . $prop['pattern'] . '/', $value)) {
                $prop_errors[] = [
                    'error' => 'pattern',
                    'message' => "This field must match the pattern {$prop['pattern']}",
                    'pattern' => $prop['pattern'],
                ];
            }
        }

        return $prop_errors;
    }
}
