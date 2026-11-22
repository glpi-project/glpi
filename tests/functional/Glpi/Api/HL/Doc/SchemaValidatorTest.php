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

namespace tests\units\Glpi\Api\HL\Doc;

use Glpi\Api\HL\Doc\SchemaValidator;
use Glpi\Tests\GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SchemaValidatorTest extends GLPITestCase
{
    /** Options used to validate the content of a response: only the type and format are checked and nothing is converted */
    private const RESPONSE_OPTIONS = ['check_required' => false, 'check_constraints' => false, 'allow_coercion' => false];

    /**
     * @param array<string, mixed> $props
     * @param array<string, mixed> $content
     * @param array<string, bool> $options
     * @return array<string, array>
     */
    private function validate(array $props, array &$content, array $options = []): array
    {
        return SchemaValidator::validate(['type' => 'object', 'properties' => $props], $content, $options);
    }

    /**
     * @param array<string, mixed> $props
     * @param array<string, mixed> $content
     * @param array<string, bool> $options
     * @return string[] The error ids by property path (like `name:maxLength`)
     */
    private function errorIds(array $props, array $content, array $options = []): array
    {
        $ids = [];
        foreach ($this->validate($props, $content, $options) as $path => $errors) {
            foreach ($errors as $error) {
                $ids[] = $path . ':' . $error['error'];
            }
        }
        return $ids;
    }

    public function testAllChecksAreEnabledByDefault(): void
    {
        $props = [
            'id' => ['type' => 'integer', 'required' => true],
            'name' => ['type' => 'string', 'maxLength' => 3],
            'age' => ['type' => 'integer'],
        ];
        $content = ['name' => 'abcdef', 'age' => '5'];

        $this->assertSame(['id:required', 'name:maxLength'], $this->errorIds($props, $content));
        $this->validate($props, $content);
        $this->assertSame(5, $content['age']);
    }

    public function testCheckRequiredOption(): void
    {
        $props = ['name' => ['type' => 'string', 'required' => true]];

        $this->assertSame(['name:required'], $this->errorIds($props, []));
        $this->assertSame([], $this->errorIds($props, [], ['check_required' => false]));
        $this->assertSame([], $this->errorIds($props, ['name' => 'a']));
    }

    public function testCheckTypeFormatOption(): void
    {
        $props = ['when' => ['type' => 'string', 'format' => 'date-time'], 'age' => ['type' => 'integer', 'maximum' => 10]];
        $content = ['when' => 'not a date', 'age' => 'abc'];

        $this->assertSame(['when:format', 'age:type'], $this->errorIds($props, $content));
        $this->assertSame([], $this->errorIds($props, $content, ['check_type_format' => false]));
    }

    public function testCheckConstraintsOption(): void
    {
        $props = [
            'name' => ['type' => 'string', 'maxLength' => 3, 'pattern' => '^[0-9]+$'],
            'level' => ['type' => 'string', 'enum' => ['low', 'high']],
        ];
        $content = ['name' => 'abcdef', 'level' => 'other'];

        $this->assertSame(['name:maxLength', 'name:pattern', 'level:enum'], $this->errorIds($props, $content));
        $this->assertSame([], $this->errorIds($props, $content, ['check_constraints' => false]));
    }

    public static function coercionProvider(): iterable
    {
        yield 'integer from string' => ['integer', '5', 5];
        yield 'integer from negative string' => ['integer', '-5', -5];
        yield 'integer from integral float' => ['integer', 5.0, 5];
        yield 'integer from integral numeric string' => ['integer', '5.0', 5];
        yield 'number from string' => ['number', '1.5', 1.5];
        yield 'number from integer string' => ['number', '3', 3];
        yield 'boolean from "true"' => ['boolean', 'true', true];
        yield 'boolean from "false"' => ['boolean', 'false', false];
        yield 'boolean from "yes"' => ['boolean', 'yes', true];
        yield 'boolean from "no"' => ['boolean', 'no', false];
        yield 'boolean from "on"' => ['boolean', 'on', true];
        yield 'boolean from "off"' => ['boolean', 'off', false];
        yield 'boolean from "1"' => ['boolean', '1', true];
        yield 'boolean from "0"' => ['boolean', '0', false];
        yield 'boolean from 1' => ['boolean', 1, true];
        yield 'boolean from 0' => ['boolean', 0, false];
        yield 'string from integer' => ['string', 354235, '354235'];
        yield 'string from float' => ['string', 1.5, '1.5'];
        yield 'array from scalar' => ['array', 'a', ['a']];
        yield 'array from integer' => ['array', 5, [5]];
        yield 'array is kept' => ['array', ['a', 'b'], ['a', 'b']];
    }

    #[DataProvider('coercionProvider')]
    public function testCoercionModifiesTheContent(string $type, mixed $value, mixed $expected): void
    {
        $content = ['p' => $value];

        $this->assertSame([], $this->validate(['p' => ['type' => $type]], $content));
        $this->assertSame($expected, $content['p']);
    }

    public static function uncoercibleProvider(): iterable
    {
        yield 'integer from text' => ['integer', 'abc'];
        yield 'integer from non integral float' => ['integer', 5.5];
        yield 'integer from non integral string' => ['integer', '5.5'];
        yield 'integer from boolean' => ['integer', true];
        yield 'number from text' => ['number', 'abc'];
        yield 'boolean from text' => ['boolean', 'maybe'];
        yield 'boolean from 5' => ['boolean', 5];
        yield 'string from boolean' => ['string', true];
        yield 'string from array' => ['string', ['a']];
    }

    #[DataProvider('uncoercibleProvider')]
    public function testValueThatCannotBeCoercedIsKeptAndRejected(string $type, mixed $value): void
    {
        $props = ['p' => ['type' => $type, 'items' => ['type' => 'string']]];
        $content = ['p' => $value];

        $this->assertSame(['p:type'], $this->errorIds($props, $content));
        $this->validate($props, $content);
        $this->assertSame($value, $content['p']);
    }

    public function testCoercionDisabled(): void
    {
        $props = ['age' => ['type' => 'integer'], 'active' => ['type' => 'boolean']];
        $content = ['age' => '5', 'active' => 'yes'];

        $this->assertSame(['age:type', 'active:type'], $this->errorIds($props, $content, ['allow_coercion' => false]));
        $this->validate($props, $content, ['allow_coercion' => false]);
        $this->assertSame(['age' => '5', 'active' => 'yes'], $content);
    }

    public function testCoercedValueIsWrittenToNestedContent(): void
    {
        $props = ['status' => ['type' => 'object', 'properties' => [
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
        ]]];
        $content = ['status' => ['id' => '3', 'name' => 'a'], 'extra' => '1'];

        $this->assertSame([], $this->validate($props, $content));
        $this->assertSame(['status' => ['id' => 3, 'name' => 'a'], 'extra' => '1'], $content);
    }

    public function testNestedPathsAreReportedByFullPath(): void
    {
        $props = ['status' => ['type' => 'object', 'properties' => [
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string', 'required' => true, 'maxLength' => 2],
        ]]];

        $this->assertSame(['status.id:type', 'status.name:maxLength'], $this->errorIds($props, ['status' => ['id' => 'x', 'name' => 'abc']]));
        $this->assertSame(['status.name:required'], $this->errorIds($props, ['status' => ['id' => 1]]));
    }

    public function testNullAndUnknownPropertiesAreValid(): void
    {
        $props = ['age' => ['type' => 'integer', 'format' => 'int32', 'minimum' => 5, 'enum' => [5, 6]]];

        $this->assertSame([], $this->errorIds($props, ['age' => null]));
        $this->assertSame([], $this->errorIds($props, ['unknown' => 'abc']));
    }

    public static function formatProvider(): iterable
    {
        yield 'int32 ok' => [['type' => 'integer', 'format' => 'int32'], 2147483647, true];
        yield 'int32 overflow' => [['type' => 'integer', 'format' => 'int32'], 2147483648, false];
        yield 'int32 is the default format of integers' => [['type' => 'integer'], 2147483648, false];
        yield 'int64 beyond int32' => [['type' => 'integer', 'format' => 'int64'], 2147483648, true];
        yield 'float' => [['type' => 'number', 'format' => 'float'], 1.23456, true];
        yield 'byte ok' => [['type' => 'string', 'format' => 'byte'], 'SGVsbG8=', true];
        yield 'byte invalid' => [['type' => 'string', 'format' => 'byte'], 'not base64!', false];
        yield 'date ok' => [['type' => 'string', 'format' => 'date'], '2024-01-31', true];
        yield 'date invalid' => [['type' => 'string', 'format' => 'date'], 'not a date', false];
        yield 'date-time Z' => [['type' => 'string', 'format' => 'date-time'], '2024-01-31T10:00:00Z', true];
        yield 'date-time offset and fraction' => [['type' => 'string', 'format' => 'date-time'], '2024-01-31T10:00:00.123+02:00', true];
        yield 'date-time with space' => [['type' => 'string', 'format' => 'date-time'], '2024-01-31 10:00:00', true];
        yield 'date-time invalid' => [['type' => 'string', 'format' => 'date-time'], 'not a date', false];
        yield 'email is not checked' => [['type' => 'string', 'format' => 'email'], 'not-an-email', true];
    }

    #[DataProvider('formatProvider')]
    public function testFormat(array $prop, mixed $value, bool $valid): void
    {
        $this->assertSame($valid ? [] : ['p:format'], $this->errorIds(['p' => $prop], ['p' => $value]));
    }

    public function testDatesInAnyFormatAreCoercedToTheCanonicalFormat(): void
    {
        $props = [
            'when' => ['type' => 'string', 'format' => 'date-time'],
            'day' => ['type' => 'string', 'format' => 'date'],
        ];
        $content = ['when' => '2024-01-31 10:00:00 UTC', 'day' => '31 January 2024'];

        $this->assertSame([], $this->validate($props, $content));
        $this->assertSame(['when' => '2024-01-31T10:00:00+00:00', 'day' => '2024-01-31'], $content);
    }

    public function testInvalidDateIsKeptAndRejected(): void
    {
        $props = ['when' => ['type' => 'string', 'format' => 'date-time']];
        $content = ['when' => 'not a date'];

        $this->assertSame(['when:format'], $this->errorIds($props, $content));
        $this->validate($props, $content);
        $this->assertSame('not a date', $content['when']);
    }

    public function testDatesAreNotCoercedWhenCoercionIsDisabled(): void
    {
        $props = ['when' => ['type' => 'string', 'format' => 'date-time']];
        $content = ['when' => '2024-01-31 10:00:00'];

        $this->assertSame(['when:format'], $this->errorIds($props, $content, ['allow_coercion' => false]));
        $this->validate($props, $content, ['allow_coercion' => false]);
        $this->assertSame('2024-01-31 10:00:00', $content['when']);
    }

    public function testEnum(): void
    {
        $props = ['level' => ['type' => 'string', 'enum' => ['a', 'b', 'c']]];
        $content = ['level' => 'z'];

        $this->assertSame([], $this->errorIds($props, ['level' => 'b']));
        $this->assertSame([
            'level' => [['error' => 'enum', 'message' => 'This field must be one of: a, b, c', 'enum' => ['a', 'b', 'c']]],
        ], $this->validate($props, $content));
    }

    public function testEnumIsComparedWithTheCoercedValue(): void
    {
        $props = ['level' => ['type' => 'integer', 'enum' => [1, 2]]];

        $this->assertSame([], $this->errorIds($props, ['level' => '2']));
        $this->assertSame(['level:enum'], $this->errorIds($props, ['level' => '3']));
        $this->assertSame(['level:type'], $this->errorIds($props, ['level' => 'abc']));
    }

    public function testTypeAndFormatErrorFormat(): void
    {
        $content = ['age' => 'abc', 'day' => 'not a date'];

        $this->assertSame([
            'age' => [['error' => 'type', 'message' => 'This field must be of type integer', 'type' => 'integer']],
            'day' => [['error' => 'format', 'message' => 'This field must match the format date', 'format' => 'date']],
        ], $this->validate(['age' => ['type' => 'integer'], 'day' => ['type' => 'string', 'format' => 'date']], $content));
    }

    public function testConstraintErrorFormat(): void
    {
        $props = [
            'name' => ['type' => 'string', 'maxLength' => 3],
            'age' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 120],
            'height' => ['type' => 'integer', 'minimum' => 10],
            'weight' => ['type' => 'integer', 'maximum' => 300],
            'step' => ['type' => 'number', 'multipleOf' => 0.5],
            'code' => ['type' => 'string', 'pattern' => '^[A-Z]{3}$'],
        ];
        $content = ['name' => 'abcd', 'age' => 121, 'height' => 5, 'weight' => 350, 'step' => 1.7, 'code' => 'abc'];

        $this->assertSame([
            'name' => [['error' => 'maxLength', 'message' => 'This field must be at most 3 characters long', 'maxLength' => 3]],
            'age' => [['error' => 'range', 'message' => 'This field must be between 0 and 120', 'minimum' => 0, 'maximum' => 120]],
            'height' => [['error' => 'minimum', 'message' => 'This field must be at least 10', 'minimum' => 10]],
            'weight' => [['error' => 'maximum', 'message' => 'This field must be at most 300', 'maximum' => 300]],
            'step' => [['error' => 'multipleOf', 'message' => 'This field must be a multiple of 0.5', 'multipleOf' => 0.5]],
            'code' => [['error' => 'pattern', 'message' => 'This field must match the pattern ^[A-Z]{3}$', 'pattern' => '^[A-Z]{3}$']],
        ], $this->validate($props, $content));
    }

    public function testAllProblemsOfAPropertyAreReported(): void
    {
        $props = ['p' => ['type' => 'string', 'format' => 'date', 'maxLength' => 3]];

        $this->assertSame(['p:format', 'p:maxLength'], $this->errorIds($props, ['p' => 'not a date']));
    }

    public static function responseContentProvider(): iterable
    {
        yield 'string ok' => [['type' => 'string'], 'abc', []];
        yield 'string given int' => [['type' => 'string'], 1, ['p:type']];
        yield 'integer given numeric string' => [['type' => 'integer'], '5', ['p:type']];
        yield 'integer given float' => [['type' => 'integer'], 5.0, ['p:type']];
        // JSON decoding may turn 0 and 1 into booleans
        yield 'integer given boolean' => [['type' => 'integer'], true, []];
        yield 'boolean given string' => [['type' => 'boolean'], 'yes', ['p:type']];
        yield 'number given numeric string' => [['type' => 'number'], '1.5', ['p:type']];
        yield 'number with two decimals' => [['type' => 'number'], 1.25, []];
        yield 'number with three decimals (double is the default format)' => [['type' => 'number'], 1.234, ['p:format']];
        yield 'float with many decimals' => [['type' => 'number', 'format' => 'float'], 1.234, []];
        yield 'array ok' => [['type' => 'array', 'items' => ['type' => 'string']], ['a'], []];
        yield 'array items are not validated' => [['type' => 'array', 'items' => ['type' => 'string']], [1, 2], []];
        yield 'array given string' => [['type' => 'array', 'items' => ['type' => 'string']], 'a', ['p:type']];
    }

    #[DataProvider('responseContentProvider')]
    public function testResponseContent(array $prop, mixed $value, array $expected_errors): void
    {
        $content = ['p' => $value];

        $this->assertSame($expected_errors, $this->errorIds(['p' => $prop], $content, self::RESPONSE_OPTIONS));
        $this->validate(['p' => $prop], $content, self::RESPONSE_OPTIONS);
        $this->assertSame($value, $content['p'], 'Response content must not be modified');
    }

    public function testResponseContentIgnoresRequiredAndConstraints(): void
    {
        $props = ['name' => ['type' => 'string', 'required' => true, 'maxLength' => 1, 'pattern' => '^[0-9]+$', 'enum' => ['x']]];

        $this->assertSame([], $this->errorIds($props, ['name' => 'abc'], self::RESPONSE_OPTIONS));
        $this->assertSame([], $this->errorIds($props, [], self::RESPONSE_OPTIONS));
    }

    public function testResponseContentNestedObjects(): void
    {
        $props = ['status' => ['type' => 'object', 'properties' => [
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
        ]]];

        $this->assertSame([], $this->errorIds($props, ['status' => ['id' => 1, 'name' => 'a']], self::RESPONSE_OPTIONS));
        $this->assertSame(['status.id:type'], $this->errorIds($props, ['status' => ['id' => 'x', 'name' => 'a']], self::RESPONSE_OPTIONS));
    }
}
