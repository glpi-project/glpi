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

namespace tests\units;

use DbTestCase;
use Generator;

class StateTest extends DbTestCase
{
    protected function testIsUniqueProvider(): Generator
    {
        // Insert test data
        $this->createItems("State", [
            ['name' => "Test"],
            ['name' => "Tést 2"],
            ['name' => "abcdefg"],
        ]);

        yield [
            'input'  => ['name' => 'Test'],
            'expected' => false,
        ];

        yield [
            'input'  => ['name' => "Test'"],
            'expected' => true,
        ];

        yield [
            'input'  => ['name' => "Tést"],
            'expected' => true,
        ];

        yield [
            'input'  => ['name' => "Test 2"],
            'expected' => true,
        ];

        yield [
            'input'  => ['name' => "Tést 2"],
            'expected' => false,
        ];
    }

    public function testIsUnique()
    {
        $provider = $this->testIsUniqueProvider();
        foreach ($provider as $row) {
            $input = $row['input'];
            $expected = $row['expected'];

            $state = new \State();
            $this->assertSame($expected, $state->isUnique($input));
        }
    }
}
