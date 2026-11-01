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
    private const LEGACY = ['api_version' => '2.4.0'];
    private const STRICT = ['api_version' => '4.0.0'];

    public static function strictVersionProvider(): iterable
    {
        yield ['1', false];
        yield ['2.4.0', false];
        yield ['3.0.0', false];
        yield ['3.9.9', false];
        yield ['4', true];
        yield ['4.0.0', true];
        yield ['4.1.0', true];
    }

    #[DataProvider('strictVersionProvider')]
    public function testIsStrictVersion(string $version, bool $expected): void
    {
        $this->assertSame($expected, SchemaValidator::isStrictVersion($version));
    }

    public static function typeFormatProvider(): iterable
    {
        // [property, value, expected error ids on API v4]
        yield 'string ok' => [['type' => 'string'], 'abc', []];
        yield 'string given int' => [['type' => 'string'], 5, ['type']];
        yield 'integer ok' => [['type' => 'integer'], 5, []];
        yield 'integer numeric string' => [['type' => 'integer'], '5', []];
        yield 'integer negative numeric string' => [['type' => 'integer'], '-5', []];
        yield 'integer integral float' => [['type' => 'integer'], 5.0, []];
        yield 'integer non integral float' => [['type' => 'integer'], 5.5, ['type']];
        yield 'integer non integral string' => [['type' => 'integer'], '5.5', ['type']];
        yield 'integer text' => [['type' => 'integer'], 'abc', ['type']];
        yield 'integer int32 overflow' => [['type' => 'integer', 'format' => 'int32'], 2147483648, ['format']];
        yield 'integer int32 overflow string' => [['type' => 'integer', 'format' => 'int32'], '3000000000', ['format']];
        yield 'integer int64' => [['type' => 'integer', 'format' => 'int64'], '3000000000', []];
        yield 'number int' => [['type' => 'number'], 3, []];
        yield 'number float' => [['type' => 'number'], 1.5, []];
        yield 'number numeric string' => [['type' => 'number'], '1.5', []];
        yield 'number text' => [['type' => 'number'], 'abc', ['type']];
        // The two decimal places restriction of the double format is only for response content
        yield 'number double many decimals' => [['type' => 'number', 'format' => 'double'], 1.234, []];
        yield 'boolean true' => [['type' => 'boolean'], true, []];
        yield 'boolean false' => [['type' => 'boolean'], false, []];
        yield 'boolean "true"' => [['type' => 'boolean'], 'true', []];
        yield 'boolean "false"' => [['type' => 'boolean'], 'false', []];
        yield 'boolean "on"' => [['type' => 'boolean'], 'on', []];
        yield 'boolean "off"' => [['type' => 'boolean'], 'off', []];
        yield 'boolean "yes"' => [['type' => 'boolean'], 'yes', []];
        yield 'boolean "no"' => [['type' => 'boolean'], 'no', []];
        yield 'boolean "1"' => [['type' => 'boolean'], '1', []];
        yield 'boolean "0"' => [['type' => 'boolean'], '0', []];
        yield 'boolean 1' => [['type' => 'boolean'], 1, []];
        yield 'boolean 0' => [['type' => 'boolean'], 0, []];
        yield 'boolean maybe' => [['type' => 'boolean'], 'maybe', ['type']];
        yield 'boolean 5' => [['type' => 'boolean'], 5, ['type']];
        yield 'array ok' => [['type' => 'array', 'items' => ['type' => 'string']], ['a'], []];
        yield 'array given string' => [['type' => 'array', 'items' => ['type' => 'string']], 'a', ['type']];
        yield 'date ok' => [['type' => 'string', 'format' => 'date'], '2024-01-31', []];
        yield 'date invalid' => [['type' => 'string', 'format' => 'date'], '31/01/2024', ['format']];
        yield 'date-time ok' => [['type' => 'string', 'format' => 'date-time'], '2024-01-31T10:00:00Z', []];
        yield 'date-time invalid' => [['type' => 'string', 'format' => 'date-time'], '2024-01-31 10:00:00', ['format']];
        yield 'byte ok' => [['type' => 'string', 'format' => 'byte'], 'SGVsbG8=', []];
        yield 'byte invalid' => [['type' => 'string', 'format' => 'byte'], 'not base64!', ['format']];
        yield 'email not checked' => [['type' => 'string', 'format' => 'email'], 'not-an-email', []];
    }

    #[DataProvider('typeFormatProvider')]
    public function testTypeAndFormatOnStrictVersion(array $prop, mixed $value, array $expected_errors): void
    {
        $errors = SchemaValidator::validateValue($prop, $value, self::STRICT);
        $this->assertSame($expected_errors, array_column($errors, 'error'));
    }

    #[DataProvider('typeFormatProvider')]
    public function testTypeAndFormatAreNotValidatedOnInputBeforeStrictVersion(array $prop, mixed $value): void
    {
        $this->assertSame([], SchemaValidator::validateValue($prop, $value, self::LEGACY));
    }

    public function testNullIsAlwaysValid(): void
    {
        $prop = ['type' => 'integer', 'format' => 'int32', 'minimum' => 5, 'enum' => [5, 6]];

        $this->assertSame([], SchemaValidator::validateValue($prop, null, self::STRICT));
        $this->assertSame([], SchemaValidator::validateValue($prop, null, self::LEGACY));
        $this->assertSame([], SchemaValidator::validateValue($prop, null, ['type_format_only' => true]));
    }

    public function testErrorFormatForTypeAndFormat(): void
    {
        $this->assertSame([
            ['error' => 'type', 'message' => 'This field must be of type integer', 'type' => 'integer'],
        ], SchemaValidator::validateValue(['type' => 'integer'], 'abc', self::STRICT));

        $this->assertSame([
            ['error' => 'format', 'message' => 'This field must match the format date', 'format' => 'date'],
        ], SchemaValidator::validateValue(['type' => 'string', 'format' => 'date'], 'abc', self::STRICT));
    }

    public function testEnum(): void
    {
        $prop = ['type' => 'string', 'enum' => ['a', 'b', 'c']];

        $this->assertSame([], SchemaValidator::validateValue($prop, 'b', self::STRICT));
        $this->assertSame([
            ['error' => 'enum', 'message' => 'This field must be one of: a, b, c', 'enum' => ['a', 'b', 'c']],
        ], SchemaValidator::validateValue($prop, 'z', self::STRICT));
        // Not validated before the strict version
        $this->assertSame([], SchemaValidator::validateValue($prop, 'z', self::LEGACY));
    }

    public function testEnumUsesCoercedValue(): void
    {
        $this->assertSame([], SchemaValidator::validateValue(['type' => 'integer', 'enum' => [1, 2]], '2', self::STRICT));
        $this->assertSame(['enum'], array_column(SchemaValidator::validateValue(['type' => 'integer', 'enum' => [1, 2]], '3', self::STRICT), 'error'));
    }

    public function testEnumIsNotCheckedWhenTheTypeIsInvalid(): void
    {
        $errors = SchemaValidator::validateValue(['type' => 'integer', 'enum' => [1, 2]], 'abc', self::STRICT);

        $this->assertSame(['type'], array_column($errors, 'error'));
    }

    public function testConstraintsAreValidatedForAllVersions(): void
    {
        $prop = ['type' => 'string', 'maxLength' => 3, 'pattern' => '^[0-9]+$'];

        foreach ([self::LEGACY, self::STRICT] as $options) {
            $this->assertSame(['maxLength', 'pattern'], array_column(SchemaValidator::validateValue($prop, 'abcdef', $options), 'error'));
            $this->assertSame([], SchemaValidator::validateValue($prop, '123', $options));
        }
    }

    public function testConstraintErrorFormat(): void
    {
        $this->assertSame([
            ['error' => 'maxLength', 'message' => 'This field must be at most 3 characters long', 'maxLength' => 3],
        ], SchemaValidator::validateValue(['type' => 'string', 'maxLength' => 3], 'abcd'));
        $this->assertSame([
            ['error' => 'range', 'message' => 'This field must be between 0 and 120', 'minimum' => 0, 'maximum' => 120],
        ], SchemaValidator::validateValue(['type' => 'integer', 'minimum' => 0, 'maximum' => 120], 121));
        $this->assertSame([
            ['error' => 'minimum', 'message' => 'This field must be at least 10', 'minimum' => 10],
        ], SchemaValidator::validateValue(['type' => 'integer', 'minimum' => 10], 5));
        $this->assertSame([
            ['error' => 'maximum', 'message' => 'This field must be at most 300', 'maximum' => 300],
        ], SchemaValidator::validateValue(['type' => 'integer', 'maximum' => 300], 350));
        $this->assertSame([
            ['error' => 'multipleOf', 'message' => 'This field must be a multiple of 0.5', 'multipleOf' => 0.5],
        ], SchemaValidator::validateValue(['type' => 'number', 'multipleOf' => 0.5], 1.7));
        $this->assertSame([
            ['error' => 'pattern', 'message' => 'This field must match the pattern ^[A-Z]{3}$', 'pattern' => '^[A-Z]{3}$'],
        ], SchemaValidator::validateValue(['type' => 'string', 'pattern' => '^[A-Z]{3}$'], 'abc'));
    }

    public function testAllProblemsOfAValueAreReported(): void
    {
        $errors = SchemaValidator::validateValue(['type' => 'string', 'format' => 'date', 'maxLength' => 3], 'not a date', self::STRICT);

        $this->assertSame(['format', 'maxLength'], array_column($errors, 'error'));
    }

    public function testItemsOfArrayInputAreValidatedOnStrictVersion(): void
    {
        $prop = ['type' => 'integer', 'maximum' => 10];

        $this->assertSame([], SchemaValidator::validateValue($prop, [1, '2'], self::STRICT));
        $this->assertSame(['type', 'maximum'], array_column(SchemaValidator::validateValue($prop, [1, 'x', 11], self::STRICT), 'error'));
        // Before the strict version the items were never validated
        $this->assertSame([], SchemaValidator::validateValue($prop, [1, 'x', 11], self::LEGACY));
    }

    public function testArrayInputIsValidatedAgainstItemsSchema(): void
    {
        // Properties of array input are flattened to the schema of their items, so each item is checked against it
        $this->assertSame([], SchemaValidator::validateValue(['type' => 'string'], ['a', 'b'], self::STRICT));
        $this->assertSame(['type'], array_column(SchemaValidator::validateValue(['type' => 'string'], ['a', 5], self::STRICT), 'error'));
    }

    public function testValidateRequired(): void
    {
        $schema = ['type' => 'object', 'properties' => [
            'name' => ['type' => 'string', 'required' => true],
            'comment' => ['type' => 'string'],
        ]];

        $this->assertSame([
            'name' => [['error' => 'required', 'message' => 'This field is required']],
        ], SchemaValidator::validate($schema, ['comment' => 'a'], ['check_required' => true]));
        $this->assertSame([], SchemaValidator::validate($schema, ['comment' => 'a']));
        $this->assertSame([], SchemaValidator::validate($schema, ['name' => 'a'], ['check_required' => true]));
    }

    public function testValidateIgnoresUnknownKeysAndAggregatesErrors(): void
    {
        $schema = ['type' => 'object', 'properties' => [
            'name' => ['type' => 'string', 'maxLength' => 3, 'required' => true],
            'age' => ['type' => 'integer', 'minimum' => 0],
            'code' => ['type' => 'string', 'required' => true],
        ]];

        $errors = SchemaValidator::validate($schema, ['name' => 'abcdef', 'age' => '-1', 'extra' => 'x'], [...self::STRICT, 'check_required' => true]);

        $this->assertSame(['code', 'name', 'age'], array_keys($errors));
        $this->assertSame(['required'], array_column($errors['code'], 'error'));
        $this->assertSame(['maxLength'], array_column($errors['name'], 'error'));
        $this->assertSame(['minimum'], array_column($errors['age'], 'error'));
    }

    public function testValidateWithCollapsedArrayItems(): void
    {
        $schema = ['type' => 'object', 'properties' => [
            'tags' => ['type' => 'array', 'items' => ['type' => 'string', 'maxLength' => 3]],
        ]];

        $this->assertSame([], SchemaValidator::validate($schema, ['tags' => ['a', 'b']], self::STRICT));
        $this->assertSame(['tags'], array_keys(SchemaValidator::validate($schema, ['tags' => ['a', 'toolong']], self::STRICT)));
    }

    public function testTypeFormatOnlyUsesDefaultFormats(): void
    {
        $schema = ['type' => 'object', 'properties' => [
            'id' => ['type' => 'integer'],
            'price' => ['type' => 'number'],
            'name' => ['type' => 'string'],
        ]];

        $this->assertSame([], SchemaValidator::validate($schema, ['id' => 5, 'price' => 1.25, 'name' => 'a'], ['type_format_only' => true]));
        // int32 and double (two decimal places) are the default formats
        $this->assertSame(['id', 'price'], array_keys(SchemaValidator::validate($schema, ['id' => 2147483648, 'price' => 1.234, 'name' => 'a'], ['type_format_only' => true])));
    }

    public function testTypeFormatOnlyDoesNotCoerceAndWalksNestedContent(): void
    {
        $schema = ['type' => 'object', 'properties' => [
            'id' => ['type' => 'integer'],
            'status' => ['type' => 'object', 'properties' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
            ]],
        ]];
        $options = ['type_format_only' => true];

        $this->assertSame(['id'], array_keys(SchemaValidator::validate($schema, ['id' => '5'], $options)));
        $this->assertSame([], SchemaValidator::validate($schema, ['status' => ['id' => 1, 'name' => 'a']], $options));
        $this->assertSame(['status.id'], array_keys(SchemaValidator::validate($schema, ['status' => ['id' => 'x']], $options)));
    }

    public function testTypeFormatOnlyIgnoresConstraintsEnumAndRequired(): void
    {
        $schema = ['type' => 'object', 'properties' => [
            'name' => ['type' => 'string', 'maxLength' => 1, 'pattern' => '^[0-9]+$', 'enum' => ['x'], 'required' => true],
        ]];

        $this->assertSame([], SchemaValidator::validate($schema, ['name' => 'abc'], ['type_format_only' => true, 'check_required' => true]));
        $this->assertSame([], SchemaValidator::validate($schema, [], ['type_format_only' => true, 'check_required' => true]));
    }
}
