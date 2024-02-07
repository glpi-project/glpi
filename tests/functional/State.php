<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace tests\units;

use DbTestCase;
use Generator;

class State extends DbTestCase
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

    /**
     * @dataprovider testIsUniqueProvider
     */
    public function testIsUnique(array $input, bool $expected)
    {
        $state = new \State();
        $this->boolean($state->isUnique($input))->isEqualTo($expected);
    }

    public function testGetStateList()
    {
        $state = new \State();
        $this->integer($state->add(['name' => 'Test']))->isGreaterThan(0);
        $this->integer($state->add(['name' => 'Test 2']))->isGreaterThan(0);
        $this->integer($state->add(['name' => 'Test 3']))->isGreaterThan(0);

        $this->array(\State::getStateList())->hasSize(3);
    }

    public function testGetStateNotInvList()
    {
        $state = new \State();
        $this->integer($state_id = $state->add(
            [
                'name'                          => 'Not for inv',
                'is_visible_computer'           => 0,
                'is_visible_networkequipment'   => 0,
                'is_visible_phone'              => 0,
                'is_visible_printer'            => 0,
            ]
        ))->isGreaterThan(0);
        $this->integer($state->add(['name' => 'For inv']))->isGreaterThan(0);

        $list = \State::getStateNotForInventory();
        $this->array($list)->hasSize(1);
        $this->boolean(in_array($state_id, $list))->isTrue();
    }
}
