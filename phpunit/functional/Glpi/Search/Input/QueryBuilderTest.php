<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace tests\units\Glpi\Search\Input;

use Glpi\Search\Input\QueryBuilder;
use PHPUnit\Framework\Attributes\DataProvider;

class QueryBuilderTest extends \GLPITestCase
{
    public static function inputValidationPatternProvider(): iterable
    {
        // string
        foreach (['dropdown', 'itemlink', 'itemtypename', 'specific', 'string', 'text', 'email'] as $datatype) {
            yield [
                'datatype' => $datatype,
                'valid_values' => [
                    // null values
                    'NULL',
                    'null',

                    // any text
                    'test',
                    '10',
                    '‣unicode',

                    // ^ and $ operators
                    '^test',
                    'test$',
                    '^test$',
                ],
                'invalid_values' => [
                    // pattern allows everything, nothing should be invalid
                ],
            ];
        }

        // number
        foreach (['count', 'integer', 'number', 'actiontime', 'decimal', 'timestamp'] as $datatype) {
            yield [
                'datatype' => $datatype,
                'valid_values' => [
                    // null values
                    'NULL',
                    'null',

                    // zero
                    '0',

                    // positive integer values, absolute and relative
                    '1',
                    '128',
                    '>128',
                    '> 128',
                    '>=128',
                    '>= 128',
                    '<512',
                    '< 512',
                    '<=512',
                    '<= 512',

                    // negative integer values, absolute and relative
                    '-1',
                    '>-128',
                    '> -128',
                    '>=-128',
                    '>= -128',
                    '<-256',
                    '< -256',
                    '<=-256',
                    '<= -256',

                    // decimal values, absolute and relative, with one or more numbers after decimal separator
                    '0.156',
                    '10.5',
                    '<0.156',
                    '< 10.5',
                    '<=0.156',
                    '<= 10.5',
                    '>0.156',
                    '> 10.5',
                    '>=0.156',
                    '>= 10.5',
                ],
                'invalid_values' => [
                    // wrong relative operators place
                    '=> 1',
                    '15 >',
                    '3<=',

                    // wrong minus char place
                    '- > 5',
                    '12 -',

                    // invalid decimal
                    '15.3.25',

                    // text value
                    'a',

                    // ^ and $ special operators are not allowed
                    '^1',
                    '1$',
                    '^1$',
                ],
            ];
        }

        // date
        foreach (['date', 'date_delay'] as $datatype) {
            yield [
                'datatype' => $datatype,
                'valid_values' => [
                    // null values
                    'NULL',
                    'null',

                    // date values
                    '2023-01',
                    '2023',
                    '-12-', // all dates in december
                    '04-01',

                    // now + X month
                    '<0.5',
                    '< 6',
                    '<=0.5',
                    '<= 6',
                    '>6',
                    '> 0.5',
                    '>=0.5',
                    '>= 6',

                    // now - X month
                    '<-0.5',
                    '< -12',
                    '<=-12',
                    '<= -0.25',
                    '>-12',
                    '> -12',
                    '>=-0.5',
                    '>= -12',
                ],
                'invalid_values' => [
                    // wrong relative operators place
                    '=> 5',
                    '3 >',
                    '0.5<=',

                    // wrong minus char place
                    '- > 5',
                    '12 -',

                    // invalid date separator
                    '2023/06',

                    // invalid decimal
                    '15.3.25',

                    // time value
                    '23:00:00',

                    // text value
                    'a',

                    // ^ and $ special operators are not allowed
                    '^2023',
                    '06-15$',
                    '^2023-06-15$',
                ],
            ];
        }

        // timestamp
        foreach (['datetime'] as $datatype) {
            yield [
                'datatype' => $datatype,
                'valid_values' => [
                    // null values
                    'NULL',
                    'null',

                    // date values
                    '2023-01',
                    '2023',
                    '-12-', // all dates in december
                    '04-01',

                    // time value
                    '23:00:00',

                    // now + X month
                    '<0.5',
                    '< 6',
                    '<=0.5',
                    '<= 6',
                    '>6',
                    '> 0.5',
                    '>=0.5',
                    '>= 6',

                    // now - X month
                    '<-0.5',
                    '< -12',
                    '<=-12',
                    '<= -0.25',
                    '>-12',
                    '> -12',
                    '>=-0.5',
                    '>= -12',
                ],
                'invalid_values' => [
                    // wrong relative operators place
                    '=> 5',
                    '3 >',
                    '0.5<=',

                    // wrong minus char place
                    '- > 5',

                    // invalid date separator
                    '2023/06',

                    // invalid decimal
                    '15.3.25',

                    // text value
                    'a',

                    // ^ and $ special operators are not allowed
                    '^2023',
                    '06-15$',
                    '^2023-06-15$',
                ],
            ];
        }

        // boolean
        yield [
            'datatype' => 'bool',
            'valid_values' => [
                // null values
                'NULL',
                'null',

                // boolean values
                '0',
                '1',
            ],
            'invalid_values' => [
                // negative value
                '-1',

                // any other integer
                '2',

                // relative operators
                '<0',
                '< 1',
                '<=1',
                '<= 0',
                '>1',
                '> 0',
                '>=0',
                '>= 1',

                // text value
                'a',

                // ^ and $ special operators are not allowed
                '^0',
                '1$',
                '^1$',
            ],
        ];

        // color
        yield [
            'datatype' => 'color',
            'valid_values' => [
                // null values
                'NULL',
                'null',

                // color values
                '#000000',
                '000000',
                '#ffffff',
                'ffffff',
                '#0d0d0d',
                '0d0d0d',

                // ^ and $ operators
                '^#00',
                'ef$',
                '^#0d0d0d$',
            ],
            'invalid_values' => [
                // non hexa decimal char
                'g',
                'y',
                '@',
                '-',

                // relative operators
                '<00',
                '< 0ef',
                '<=a5',
                '<= 32',
                '>ae',
                '> 0a',
                '>=4f',
                '>= 8c',
            ],
        ];
    }

    #[DataProvider('inputValidationPatternProvider')]
    public function testGetInputValidationPattern(
        string $datatype,
        array $valid_values,
        array $invalid_values
    ) {
        $instance = new QueryBuilder();

        $result = QueryBuilder::getInputValidationPattern($datatype);

        $this->assertArrayHasKey('pattern', $result);
        $this->assertArrayHasKey('validation_message', $result);

        foreach ($valid_values as $value) {
            $this->assertEquals(
                1,
                preg_match($result['pattern'], $value),
                sprintf('Invalid result for field `%s` with value `%s`.', $datatype, $value)
            );
        }

        foreach ($invalid_values as $value) {
            $this->assertEquals(
                0,
                preg_match($result['pattern'], $value),
                sprintf('Invalid result for field `%s` with value `%s`.', $datatype, $value)
            );
        }
    }
}
