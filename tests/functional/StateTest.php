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

use CommonDBTM;
use Computer;
use DbTestCase;
use DropdownVisibility;
use Glpi\Features\StateInterface;
use Phone;
use Printer;
use ReflectionClass;

class StateTest extends DbTestCase
{
    protected function testIsUniqueProvider(): iterable
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

    public function testVisibility(): void
    {
        global $CFG_GLPI;

        $state = new \State();

        $states_id = $state->add([
            'name' => 'Test computer and phone',
            'is_visible_computer' => '1',
            'is_visible_phone' => '1',
        ]);

        $this->assertGreaterThan(0, $states_id);

        $statevisibility = new DropdownVisibility();
        $visibilities = $statevisibility->find(['itemtype' => \State::getType(), 'items_id' => $states_id]);
        $this->assertCount(2, $visibilities);
        $this->assertTrue(
            $statevisibility->getFromDBByCrit([
                'itemtype' => \State::getType(),
                'items_id' => $states_id,
                'visible_itemtype' => Computer::getType(),
                'is_visible' => 1,
            ])
        );
        $this->assertTrue(
            $statevisibility->getFromDBByCrit([
                'itemtype' => \State::getType(),
                'items_id' => $states_id,
                'visible_itemtype' => Phone::getType(),
                'is_visible' => 1,
            ])
        );
        $this->assertFalse(
            $statevisibility->getFromDBByCrit([
                'itemtype' => \State::getType(),
                'items_id' => $states_id,
                'visible_itemtype' => Printer::getType(),
                'is_visible' => 0,
            ])
        );

        $this->assertTrue(
            $state->update([
                'id' => $states_id,
                'is_visible_computer' => '0',
                'is_visible_printer' => '1',
            ])
        );
        $visibilities = $statevisibility->find(['itemtype' => \State::getType(), 'items_id' => $states_id]);
        $this->assertCount(3, $visibilities);
        $visibilities = $statevisibility->find(['itemtype' => \State::getType(), 'items_id' => $states_id, 'is_visible' => 1]);
        $this->assertCount(2, $visibilities);
        $this->assertTrue(
            $statevisibility->getFromDBByCrit([
                'itemtype' => \State::getType(),
                'items_id' => $states_id,
                'visible_itemtype' => Computer::getType(),
                'is_visible' => 0,
            ])
        );
        $this->assertTrue(
            $statevisibility->getFromDBByCrit([
                'itemtype' => \State::getType(),
                'items_id' => $states_id,
                'visible_itemtype' => Phone::getType(),
                'is_visible' => 1,
            ])
        );
        $this->assertTrue(
            $statevisibility->getFromDBByCrit([
                'itemtype' => \State::getType(),
                'items_id' => $states_id,
                'visible_itemtype' => Printer::getType(),
                'is_visible' => 1,
            ])
        );

        $this->assertTrue($state->getFromDB($states_id));
        $this->assertEquals('Test computer and phone', $state->fields['name']);

        $expected_values = [];
        // Default values
        foreach ($CFG_GLPI['state_types'] as $type) {
            $expected_values['is_visible_' . strtolower($type)] = 0;
        }
        $expected_values['is_visible_computer'] = 0;
        $expected_values['is_visible_phone']    = 1;
        $expected_values['is_visible_printer']  = 1;
        foreach ($expected_values as $field => $expected_value) {
            $this->assertSame($expected_value, $state->fields[$field]);
        }
    }

    public function testHasFeature(): void
    {
        global $CFG_GLPI;

        foreach ($CFG_GLPI['state_types'] as $itemtype) {
            $this->assertTrue(
                is_a($itemtype, StateInterface::class, true),
                $itemtype . ' must implement ' . StateInterface::class
            );
        }
    }

    public function testRegisteredTypes(): void
    {
        global $CFG_GLPI, $DB;

        foreach ($CFG_GLPI['state_types'] as $itemtype) {
            $this->assertTrue(
                $DB->fieldExists($itemtype::getTable(), 'states_id'),
                $itemtype . ' should have a `states_id` field.'
            );
        }

        foreach ($DB->listTables() as $table_data) {
            $table_name = $table_data['TABLE_NAME'];
            $classname  = getItemTypeForTable($table_name);

            if (
                $classname === \State::class
                || !is_a($classname, CommonDBTM::class, true)
                || (new ReflectionClass($classname))->isAbstract()
            ) {
                continue;
            }

            $has_field  = $DB->fieldExists($table_name, 'states_id');
            if ($has_field) {
                $this->assertContains(
                    $classname,
                    $CFG_GLPI['state_types'],
                    $classname . ' should be declared in `$CFG_GLPI[\'state_types\']`.'
                );
            } else {
                $this->assertNotContains(
                    $classname,
                    $CFG_GLPI['state_types'],
                    $classname . ' should not be declared in `$CFG_GLPI[\'state_types\']`.'
                );
            }
        }
    }

    public function testIsStateVisible(): void
    {
        global $CFG_GLPI;

        $itemtype = $CFG_GLPI['state_types'][0];

        $state = new \State();
        $states_id = $state->add([
            'name' => 'Test computer and phone',
            'is_visible_' . strtolower($itemtype) => '1',
        ]);
        $this->assertGreaterThan(0, $states_id);

        $item = new $itemtype();
        $this->assertTrue(method_exists($itemtype, 'isStateVisible'), $itemtype . ' misses isStateVisible() method!');
        $this->assertTrue($item->isStateVisible($states_id));

        unset($CFG_GLPI['state_types'][0]);
        $this->assertTrue(method_exists($itemtype, 'isStateVisible'), $itemtype . ' misses isStateVisible() method!');

        $this->expectExceptionMessage(sprintf('Class %s must be present in $CFG_GLPI[\'state_types\']', $itemtype));
        $this->assertTrue($item->isStateVisible($states_id));
    }

    public function testGetStateVisibilityCriteria(): void
    {
        global $CFG_GLPI;

        $itemtype = $CFG_GLPI['state_types'][0];

        $item = new $itemtype();
        $this->assertSame(
            [
                'LEFT JOIN' => [
                    DropdownVisibility::getTable() => [
                        'ON' => [
                            DropdownVisibility::getTable() => 'items_id',
                            \State::getTable() => 'id', [
                                'AND' => [
                                    DropdownVisibility::getTable() . '.itemtype' => \State::getType(),
                                ],
                            ],
                        ],
                    ],
                ],
                'WHERE' => [
                    DropdownVisibility::getTable() . '.itemtype' => \State::getType(),
                    DropdownVisibility::getTable() . '.visible_itemtype' => $itemtype,
                    DropdownVisibility::getTable() . '.is_visible' => 1,
                ],
            ],
            $item->getStateVisibilityCriteria()
        );
    }
}
