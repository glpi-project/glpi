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

namespace Glpi\Tests\Form\QuestionType;

use Glpi\Form\QuestionType\AbstractQuestionTypeSelectable;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormTesterTrait;
use PHPUnit\Framework\Attributes\DataProvider;

abstract class AbstractQuestionTypeSelectableTest extends DbTestCase
{
    use FormTesterTrait;

    /**
     * Get the selectable question type instance under test.
     */
    abstract protected function getQuestionType(): AbstractQuestionTypeSelectable;

    public static function formatPredefinedValueProvider(): iterable
    {
        yield 'valid uuid' => [
            'value'    => '12345678-1234-1234-1234-123456789012',
            'expected' => '12345678-1234-1234-1234-123456789012',
        ];

        yield 'multiple valid uuids' => [
            'value'    => '12345678-1234-1234-1234-123456789012,87654321-4321-4321-4321-210987654321',
            'expected' => '12345678-1234-1234-1234-123456789012,87654321-4321-4321-4321-210987654321',
        ];

        yield 'empty uuid' => [
            'value'    => '',
            'expected' => null,
        ];

        yield 'mixed valid and invalid uuids' => [
            'value'    => '12345678-1234-1234-1234-123456789012,, 87654321-4321-4321-4321-210987654321',
            'expected' => '12345678-1234-1234-1234-123456789012,87654321-4321-4321-4321-210987654321',
        ];
    }

    #[DataProvider('formatPredefinedValueProvider')]
    public function testFormatPredefinedValue(string $value, ?string $expected): void
    {
        $this->assertSame($expected, $this->getQuestionType()->formatPredefinedValue($value));
    }

    public static function convertDefaultValueProvider(): iterable
    {
        $values = json_encode(['Option 1', 'Option 2', 'Option 3']);

        yield 'matching default value' => [
            'raw_data' => ['default_values' => 'Option 2', 'values' => $values],
            'expected' => [2],
        ];

        yield 'matching json array of default values' => [
            'raw_data' => ['default_values' => json_encode(['Option 1', 'Option 3']), 'values' => $values],
            'expected' => [1, 3],
        ];

        yield 'empty default value' => [
            'raw_data' => ['default_values' => '', 'values' => $values],
            'expected' => [],
        ];

        yield 'no default value key' => [
            'raw_data' => ['values' => $values],
            'expected' => [],
        ];

        // Must not fall back to the first option (false + 1 == 1).
        yield 'default value with no matching option' => [
            'raw_data' => ['default_values' => 'Not an option', 'values' => $values],
            'expected' => [],
        ];

        yield 'mix of matching and non matching default values' => [
            'raw_data' => ['default_values' => json_encode(['Option 3', 'Not an option']), 'values' => $values],
            'expected' => [3],
        ];
    }

    #[DataProvider('convertDefaultValueProvider')]
    public function testConvertDefaultValue(array $raw_data, array $expected): void
    {
        $this->assertSame($expected, $this->getQuestionType()->convertDefaultValue($raw_data));
    }
}
