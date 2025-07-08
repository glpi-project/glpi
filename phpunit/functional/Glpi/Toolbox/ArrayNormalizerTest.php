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

namespace tests\units\Glpi\Cache;

use Glpi\Toolbox\ArrayNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;

class ArrayNormalizerTest extends \GLPITestCase
{
    public static function valuesProvider(): iterable
    {
        yield 'non indexed array' => [
            'array'             => [
                '1',
                '4',
                '8',
            ],
            'values_normalizer' => 'intval',
            'preserve_keys'     => false,
            'expected'          => [
                1,
                4,
                8,
            ],
        ];

        yield 'indexed array without keys preservation' => [
            'array'             => [
                'a' => '1',
                'b' => '4',
                'c' => 'not an integer',
            ],
            'values_normalizer' => 'intval',
            'preserve_keys'     => false,
            'expected'          => [
                1,
                4,
                0,
            ],
        ];

        yield 'indexed array with keys preservation' => [
            'array'             => [
                'a' => null,
                'b' => false,
                'c' => 0,
                'd' => 'no',
            ],
            'values_normalizer' => 'strval',
            'preserve_keys'     => true,
            'expected'          => [
                'a' => '',
                'b' => '',
                'c' => '0',
                'd' => 'no',
            ],
        ];

        yield 'multi dimentional array cleaning' => [
            'array'             => [
                1,
                ['an unexpected array' => 2],
            ],
            'values_normalizer' => 'intval',
            'preserve_keys'     => false,
            'expected'          => [
                1,
                1, // intval on an array returns 1
            ],
        ];
    }

    #[DataProvider('valuesProvider')]
    public function testNormalizeValues(
        array $array,
        callable $values_normalizer,
        bool $preserve_keys,
        array $expected
    ): void {
        $this->assertSame(
            $expected,
            ArrayNormalizer::normalizeValues($array, $values_normalizer, $preserve_keys)
        );
    }
}
