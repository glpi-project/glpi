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

    public function testVisibility()
    {
        $state = new \State();

        $states_id = $state->add([
            'name' => 'Test computer and phone',
            'is_visible_computer' => '1',
            'is_visible_phone' => '1',
        ]);

        $this->integer($states_id)->isGreaterThan(0);

        $statevisibility = new \DropdownVisibility();
        $visibilities = $statevisibility->find(['itemtype' => \State::getType(), 'items_id' => $states_id]);
        $this->array($visibilities)->hasSize(2);
        $this->boolean($statevisibility->getFromDBByCrit(['itemtype' => \State::getType(), 'items_id' => $states_id, 'visible_itemtype' => \Computer::getType(), 'is_visible' => 1]))->isTrue();
        $this->boolean($statevisibility->getFromDBByCrit(['itemtype' => \State::getType(), 'items_id' => $states_id, 'visible_itemtype' => \Phone::getType(), 'is_visible' => 1]))->isTrue();
        $this->boolean($statevisibility->getFromDBByCrit(['itemtype' => \State::getType(), 'items_id' => $states_id, 'visible_itemtype' => \Printer::getType(), 'is_visible' => 0]))->isFalse();

        $state->update([
            'id' => $states_id,
            'is_visible_computer' => '0',
            'is_visible_printer' => '1',
        ]);
        $visibilities = $statevisibility->find(['itemtype' => \State::getType(), 'items_id' => $states_id]);
        $this->array($visibilities)->hasSize(3);
        $visibilities = $statevisibility->find(['itemtype' => \State::getType(), 'items_id' => $states_id, 'is_visible' => 1]);
        $this->array($visibilities)->hasSize(2);
        $this->boolean($statevisibility->getFromDBByCrit(['itemtype' => \State::getType(), 'items_id' => $states_id, 'visible_itemtype' => \Computer::getType(), 'is_visible' => 0]))->isTrue();
        $this->boolean($statevisibility->getFromDBByCrit(['itemtype' => \State::getType(), 'items_id' => $states_id, 'visible_itemtype' => \Phone::getType(), 'is_visible' => 1]))->isTrue();
        $this->boolean($statevisibility->getFromDBByCrit(['itemtype' => \State::getType(), 'items_id' => $states_id, 'visible_itemtype' => \Printer::getType(), 'is_visible' => 1]))->isTrue();

        $this->boolean($state->getFromDB($states_id))->isTrue();
        $this->string($state->fields['name'])->isEqualTo('Test computer and phone');
        $this->integer($state->fields['is_visible_computer'])->isIdenticalTo(0);
        $this->integer($state->fields['is_visible_phone'])->isIdenticalTo(1);
        $this->integer($state->fields['is_visible_printer'])->isIdenticalTo(1);
    }

    public function testHasFeature()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        foreach ($CFG_GLPI['state_types'] as $itemtype) {
            $this->boolean(method_exists($itemtype, 'isStateVisible'))->isTrue($itemtype . ' misses isStateVisible() method!');
        }
    }

    public function testIsStateVisible()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $state = new \State();
        $states_id = $state->add([
            'name' => 'Test computer and phone',
            'is_visible_computer' => '1'
        ]);
        $this->integer($states_id)->isGreaterThan(0);

        $itemtype = $CFG_GLPI['state_types'][0];

        $item = new $itemtype();
        $this->boolean(method_exists($itemtype, 'isStateVisible'))->isTrue($itemtype . ' misses isStateVisible() method!');
        $this->boolean($item->isStateVisible($states_id))->isTrue();

        unset($CFG_GLPI['state_types'][0]);
        $this->boolean(method_exists($itemtype, 'isStateVisible'))->isTrue($itemtype . ' misses isStateVisible() method!');

        $this->when(
            function () use ($item, $states_id) {
                $this->boolean($item->isStateVisible($states_id))->isTrue();
            }
        )
            ->error()
            ->withType(E_USER_ERROR)
            ->withMessage('Class Computer must be present in $CFG_GLPI[\'state_types\']')
            ->exists();
    }

    public function testGetStateVisibilityCriteria()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $itemtype = $CFG_GLPI['state_types'][0];

        $item = new $itemtype();
        $this->array($item->getStateVisibilityCriteria())->isIdenticalTo([
            'LEFT JOIN' => [
                \DropdownVisibility::getTable() => [
                    'ON' => [
                        \DropdownVisibility::getTable() => 'items_id',
                        \State::getTable() => 'id', [
                            'AND' => [
                                \DropdownVisibility::getTable() . '.itemtype' => \State::getType()
                            ]
                        ]
                    ]
                ]
            ],
            'WHERE' => [
                \DropdownVisibility::getTable() . '.itemtype' => \State::getType(),
                \DropdownVisibility::getTable() . '.visible_itemtype' => $itemtype,
                \DropdownVisibility::getTable() . '.is_visible' => 1
            ]
        ]);
    }
}
