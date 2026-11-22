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

namespace tests\units\Glpi\Api\HL\Doc;

use Glpi\Api\HL\Doc;
use Glpi\Tests\GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SchemaTest extends GLPITestCase
{
    public static function schemaArrayProvider()
    {
        return [
            [
                'schema' => new Doc\Schema(
                    type: Doc\Schema::TYPE_STRING
                ),
                'array' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_STRING,
                ],
            ],
            [
                'schema' => new Doc\Schema(
                    type: Doc\Schema::TYPE_STRING,
                    enum: ['a', 'b', 'c'],
                ),
                'array' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_STRING,
                    'enum' => ['a', 'b', 'c'],
                ],
            ],
        ];
    }

    #[DataProvider('schemaArrayProvider')]
    public function testToArray($schema, $array)
    {
        $this->assertEquals($array, $schema->toArray());
    }

    #[DataProvider('schemaArrayProvider')]
    public function testFromArray($schema, $array)
    {
        $this->assertEquals($schema, Doc\Schema::fromArray($array));
    }

    public static function isValidProvider(): iterable
    {
        $prop = static fn(array $def) => ['type' => 'object', 'properties' => ['p' => $def]];

        // Type checks (default formats apply: string=>string, integer=>int32, number=>double, boolean=>boolean)
        yield 'string ok' => [$prop(['type' => 'string']), ['p' => 'abc'], true];
        yield 'string given int' => [$prop(['type' => 'string']), ['p' => 1], false];
        yield 'integer ok' => [$prop(['type' => 'integer']), ['p' => 5], true];
        yield 'integer given numeric string' => [$prop(['type' => 'integer']), ['p' => '5'], false];
        yield 'integer given float' => [$prop(['type' => 'integer']), ['p' => 5.5], false];
        yield 'integer given true (json round-trip coercion)' => [$prop(['type' => 'integer']), ['p' => true], true];
        yield 'integer given false (json round-trip coercion)' => [$prop(['type' => 'integer']), ['p' => false], true];
        yield 'boolean ok' => [$prop(['type' => 'boolean']), ['p' => false], true];
        yield 'boolean given int' => [$prop(['type' => 'boolean']), ['p' => 1], false];
        yield 'number given int' => [$prop(['type' => 'number']), ['p' => 3], true];
        yield 'number given float' => [$prop(['type' => 'number']), ['p' => 1.5], true];
        yield 'number given numeric string' => [$prop(['type' => 'number']), ['p' => '1.5'], false];
        yield 'array ok' => [$prop(['type' => 'array', 'items' => ['type' => 'string']]), ['p' => ['a']], true];
        yield 'array given string' => [$prop(['type' => 'array', 'items' => ['type' => 'string']]), ['p' => 'a'], false];
        // Current behavior: array items are not validated
        yield 'array items not validated' => [$prop(['type' => 'array', 'items' => ['type' => 'string']]), ['p' => [1, 2]], true];

        // Null and missing values are valid for every type/format
        yield 'null string' => [$prop(['type' => 'string']), ['p' => null], true];
        yield 'null integer' => [$prop(['type' => 'integer']), ['p' => null], true];
        yield 'null date-time' => [$prop(['type' => 'string', 'format' => 'date-time']), ['p' => null], true];
        yield 'missing property' => [$prop(['type' => 'integer']), [], true];

        // Integer formats
        yield 'int32 max' => [$prop(['type' => 'integer', 'format' => 'int32']), ['p' => 2147483647], true];
        yield 'int32 overflow' => [$prop(['type' => 'integer', 'format' => 'int32']), ['p' => 2147483648], false];
        yield 'int32 negative' => [$prop(['type' => 'integer', 'format' => 'int32']), ['p' => -5], true];
        yield 'int64 beyond int32' => [$prop(['type' => 'integer', 'format' => 'int64']), ['p' => 2147483648], true];
        yield 'int64 max' => [$prop(['type' => 'integer', 'format' => 'int64']), ['p' => PHP_INT_MAX], true];

        // Number formats
        yield 'double two decimals' => [$prop(['type' => 'number', 'format' => 'double']), ['p' => 1.25], true];
        yield 'double three decimals' => [$prop(['type' => 'number', 'format' => 'double']), ['p' => 1.234], false];
        yield 'double given int' => [$prop(['type' => 'number', 'format' => 'double']), ['p' => 2], true];
        yield 'float many decimals' => [$prop(['type' => 'number', 'format' => 'float']), ['p' => 1.23456], true];
        yield 'float given int' => [$prop(['type' => 'number', 'format' => 'float']), ['p' => 2], true];
        yield 'number default format is double' => [$prop(['type' => 'number']), ['p' => 1.234], false];

        // String formats
        yield 'byte ok' => [$prop(['type' => 'string', 'format' => 'byte']), ['p' => 'SGVsbG8='], true];
        yield 'byte invalid' => [$prop(['type' => 'string', 'format' => 'byte']), ['p' => 'not base64!'], false];
        yield 'date ok' => [$prop(['type' => 'string', 'format' => 'date']), ['p' => '2024-01-31'], true];
        yield 'date invalid' => [$prop(['type' => 'string', 'format' => 'date']), ['p' => '2024-1-31'], false];
        yield 'date given date-time' => [$prop(['type' => 'string', 'format' => 'date']), ['p' => '2024-01-31T10:00:00Z'], false];
        yield 'date-time Z' => [$prop(['type' => 'string', 'format' => 'date-time']), ['p' => '2024-01-31T10:00:00Z'], true];
        yield 'date-time offset' => [$prop(['type' => 'string', 'format' => 'date-time']), ['p' => '2024-01-31T10:00:00+02:00'], true];
        yield 'date-time fractional seconds' => [$prop(['type' => 'string', 'format' => 'date-time']), ['p' => '2024-01-31T10:00:00.123-05:00'], true];
        yield 'date-time with space' => [$prop(['type' => 'string', 'format' => 'date-time']), ['p' => '2024-01-31 10:00:00'], false];
        yield 'date-time without timezone' => [$prop(['type' => 'string', 'format' => 'date-time']), ['p' => '2024-01-31T10:00:00'], false];
        yield 'password format string' => [$prop(['type' => 'string', 'format' => 'password']), ['p' => 'secret'], true];
        yield 'binary format string' => [$prop(['type' => 'string', 'format' => 'binary']), ['p' => 'raw'], true];

        // Formats without a specific check only rely on the type
        yield 'email format not checked' => [$prop(['type' => 'string', 'format' => 'email']), ['p' => 'not-an-email'], true];
        yield 'uri format not checked' => [$prop(['type' => 'string', 'format' => 'uri']), ['p' => 'not a uri'], true];
        yield 'unknown format still checks type' => [$prop(['type' => 'string', 'format' => 'email']), ['p' => 5], false];

        // Constraints and enums are not checked by isValid
        yield 'enum not checked' => [$prop(['type' => 'string', 'enum' => ['a', 'b']]), ['p' => 'z'], true];
        yield 'pattern not checked' => [$prop(['type' => 'string', 'pattern' => '^[0-9]+$']), ['p' => 'abc'], true];

        // Nested objects
        $nested = ['type' => 'object', 'properties' => [
            'status' => ['type' => 'object', 'properties' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
            ]],
        ]];
        yield 'nested ok' => [$nested, ['status' => ['id' => 1, 'name' => 'a']], true];
        yield 'nested wrong type' => [$nested, ['status' => ['id' => 'x', 'name' => 'a']], false];
        yield 'nested missing' => [$nested, [], true];

        // First failing property makes the whole content invalid
        $multi = ['type' => 'object', 'properties' => [
            'a' => ['type' => 'string'],
            'b' => ['type' => 'integer'],
        ]];
        yield 'multiple props ok' => [$multi, ['a' => 'x', 'b' => 1], true];
        yield 'multiple props one bad' => [$multi, ['a' => 'x', 'b' => 'y'], false];
    }

    #[DataProvider('isValidProvider')]
    public function testIsValid(array $schema, array $content, bool $expected): void
    {
        $this->assertSame($expected, Doc\Schema::fromArray($schema)->isValid($content));
    }

    #[DataProvider('isValidProvider')]
    public function testIsValidResultDoesNotDependOnOperation(array $schema, array $content, bool $expected): void
    {
        // readOnly/writeOnly flags are not preserved by fromArray(), so the operation has no effect on the result
        $this->assertSame($expected, Doc\Schema::fromArray($schema)->isValid($content, 'read'));
        $this->assertSame($expected, Doc\Schema::fromArray($schema)->isValid($content, 'write'));
    }

    public function testIsValidIgnoresExtraContentProperties(): void
    {
        $schema = Doc\Schema::fromArray(['type' => 'object', 'properties' => ['a' => ['type' => 'string']]]);

        $this->assertTrue($schema->isValid(['a' => 'x', 'unknown' => 123]));
    }
}
