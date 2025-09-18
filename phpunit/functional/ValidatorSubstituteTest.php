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

use Auth;
use Computer;
use DbTestCase;
use DbUtils;
use Preference;
use Session;
use User;

/* Test for inc/RuleRight.class.php */

class ValidatorSubstituteTest extends DbTestCase
{
    protected function providerGetTabNameForItem()
    {
        //login to get session
        $auth = new Auth();
        $this->assertTrue($auth->login(TU_USER, TU_PASS, true));

        yield [
            'item' => new Computer(),
            'expected' => '',
        ];

        yield [
            'item' => new Preference(),
            'expected' => 'Authorized substitutes',
        ];

        $validatorSubstitute = new \ValidatorSubstitute();
        $validatorSubstitute->add([
            'users_id' => Session::getLoginUserId(),
            'users_id_substitute' => User::getIdByName('glpi'),
        ]);

        yield [
            'item' => new Preference(),
            'expected' => "Authorized substitutes 1",
        ];

        $_SESSION['glpishow_count_on_tabs'] = 0;

        yield [
            'item' => new Preference(),
            'expected' => "Authorized substitutes",
        ];

        $_SESSION['glpishow_count_on_tabs'] = 1;
        $validatorSubstitute = new \ValidatorSubstitute();
        $validatorSubstitute->add([
            'users_id' => Session::getLoginUserId(),
            'users_id_substitute' => User::getIdByName('tech'),
        ]);

        yield [
            'item' => new Preference(),
            'expected' => "Authorized substitutes 2",
        ];

        $_SESSION['glpishow_count_on_tabs'] = 0;

        yield [
            'item' => new Preference(),
            'expected' => "Authorized substitutes",
        ];
    }

    public function testGetTabNameForItem()
    {
        foreach ($this->providerGetTabNameForItem() as $row) {
            $item = $row['item'];
            $expected = $row['expected'];

            $instance = new \ValidatorSubstitute();

            $output = $instance->getTabNameForItem($item);
            $this->assertEquals($expected, strip_tags($output));
        }
    }

    protected function providerCanCreateItem()
    {
        yield [
            'input' => [
            ],
            'expected' => false,
        ];

        $this->login('glpi', 'glpi');
        yield [
            'input' => [
                'users_id' => 1,
            ],
            'expected' => false,
        ];

        yield [
            'input' => [
                'users_id' => User::getIdByName('glpi'),
            ],
            'expected' => true,
        ];
    }

    public function testCanCreateItem()
    {
        foreach ($this->providerCanCreateItem() as $row) {
            $input = $row['input'];
            $expected = $row['expected'];

            $instance = new \ValidatorSubstitute();
            $instance->fields = $input;
            $output = $instance->canCreateItem();
            $this->assertEquals($expected, $output);
        }
    }

    protected function providerCanViewItem()
    {
        yield [
            'input' => [
            ],
            'expected' => false,
        ];

        $_SESSION['glpiID'] = 2;
        yield [
            'input' => [
                'users_id' => 1,
            ],
            'expected' => false,
        ];

        yield [
            'input' => [
                'users_id' => User::getIdByName('glpi'),
            ],
            'expected' => true,
        ];
    }

    public function testCanViewItem()
    {
        foreach ($this->providerCanViewItem() as $row) {
            $input = $row['input'];
            $expected = $row['expected'];
            $instance = new \ValidatorSubstitute();
            $instance->fields = $input;
            $output = $instance->canViewItem();
            $this->assertEquals($expected, $output);
        }
    }

    public function providerCanUpdateItem()
    {
        yield [
            'input' => [
            ],
            'expected' => false,
        ];

        $_SESSION['glpiID'] = 2;
        yield [
            'input' => [
                'users_id' => 1,
            ],
            'expected' => false,
        ];

        yield [
            'input' => [
                'users_id' => User::getIdByName('glpi'),
            ],
            'expected' => true,
        ];
    }

    public function testCanUpdateItem()
    {
        foreach ($this->providerCanUpdateItem() as $row) {
            $input = $row['input'];
            $expected = $row['expected'];
            $instance = new \ValidatorSubstitute();
            $instance->fields = $input;
            $output = $instance->canUpdateItem();
            $this->assertEquals($expected, $output);
        }
    }

    protected function providerCanDeleteItem()
    {
        yield [
            'input' => [
            ],
            'expected' => false,
        ];

        $_SESSION['glpiID'] = 2;
        yield [
            'input' => [
                'users_id' => 1,
            ],
            'expected' => false,
        ];

        yield [
            'input' => [
                'users_id' => User::getIdByName('glpi'),
            ],
            'expected' => true,
        ];
    }

    public function testCanDeleteItem()
    {
        foreach ($this->providerCanDeleteItem() as $row) {
            $input = $row['input'];
            $expected = $row['expected'];
            $instance = new \ValidatorSubstitute();
            $instance->fields = $input;
            $output = $instance->canDeleteItem();
            $this->assertEquals($expected, $output);
        }
    }

    protected function providerCanPurgeItem()
    {
        yield [
            'input' => [
            ],
            'expected' => false,
        ];

        $_SESSION['glpiID'] = 2;
        yield [
            'input' => [
                'users_id' => 1,
            ],
            'expected' => false,
        ];

        yield [
            'input' => [
                'users_id' => User::getIdByName('glpi'),
            ],
            'expected' => true,
        ];
    }

    public function testCanPurgeItem()
    {
        foreach ($this->providerCanPurgeItem() as $row) {
            $input = $row['input'];
            $expected = $row['expected'];
            $instance = new \ValidatorSubstitute();
            $instance->fields = $input;
            $output = $instance->canPurgeItem();
            $this->assertEquals($expected, $output);
        }
    }

    protected function providerUpdateSubstitutes_dateRange()
    {
        $this->login('normal', 'normal');
        yield 'not allowed to edit' => [
            'input' => [
                'users_id' => User::getIdByName('glpi'),
            ],
            'expected' => [
                'return' => false,
                'range' => [],
                'messages' => ['You cannot change substitutes for this user.'],
            ],
        ];

        yield [
            'input' => [
                'users_id' => User::getIdByName('normal'),
                'substitution_start_date' => '',
                'substitution_end_date' => '',
            ],
            'expected' => [
                'return' => true,
                'range' => [
                    'start' => null,
                    'end'   => null,
                ],
            ],
        ];

        yield [
            'input' => [
                'users_id' => User::getIdByName('normal'),
                'substitution_start_date' => '2022-01-01 12:00:00',
                'substitution_end_date' => null,
            ],
            'expected' => [
                'return' => true,
                'range' => [
                    'start' => '2022-01-01 12:00:00',
                    'end'   => null,
                ],
            ],
        ];

        yield [
            'input' => [
                'users_id' => User::getIdByName('normal'),
                'substitution_start_date' => '2022-01-01 12:00:00',
                'substitution_end_date' => '2022-06-30 12:00:00',
            ],
            'expected' => [
                'return' => true,
                'range' => [
                    'start' => '2022-01-01 12:00:00',
                    'end'   => '2022-06-30 12:00:00',
                ],
            ],
        ];

        yield [
            'input' => [
                'users_id' => User::getIdByName('normal'),
                'substitution_end_date' => '2022-06-30 12:00:00',
            ],
            'expected' => [
                'return' => true,
                'range' => [
                    'start' => '2022-01-01 12:00:00', // Value from previous test case
                    'end'   => '2022-06-30 12:00:00',
                ],
            ],
        ];

        yield [
            'input' => [
                'users_id' => User::getIdByName('normal'),
                'substitution_start_date' => '',
                'substitution_end_date' => '2022-06-30 12:00:00',
            ],
            'expected' => [
                'return' => true,
                'range' => [
                    'start' => null,
                    'end'   => '2022-06-30 12:00:00',
                ],
            ],
        ];

        yield 'swapped values in date range' => [
            'input' => [
                'users_id' => User::getIdByName('normal'),
                'substitution_start_date' => '2023-01-01 12:00:00',
                'substitution_end_date' => '2022-06-30 12:00:00',
            ],
            'expected' => [
                'return' => true,
                'range' => [
                    'start' => '2023-01-01 12:00:00',
                    'end'   => '2023-01-01 12:00:00',
                ],
            ],
        ];

        yield 'swapped values in date range' => [
            'input' => [
                'users_id' => User::getIdByName('normal'),
                'substitution_start_date' => '2023-01-01 12:00:00',
                'substitution_end_date' => '',
            ],
            'expected' => [
                'return' => true,
                'range' => [
                    'start' => '2023-01-01 12:00:00',
                    'end'   => null,
                ],
            ],
        ];
    }

    public function testUpdateSubstitutes_dateRange()
    {
        foreach ($this->providerUpdateSubstitutes_dateRange() as $row) {
            $input = $row['input'];
            $expected = $row['expected'];
            $instance = new \ValidatorSubstitute();

            $output = $instance->updateSubstitutes($input);

            // Check the return value
            $this->assertEquals($expected['return'], $output);

            if ($expected['return'] === false) {
                // Check error message
                $this->hasSessionMessages(ERROR, $expected['messages']);
                // Nothing more to check
                return;
            }

            // Check the expected date range
            $user = User::getById($input['users_id']);
            $this->assertInstanceOf(User::class, $user);

            $this->assertEquals($expected['range']['start'], $user->fields['substitution_start_date']);
            $this->assertEquals($expected['range']['end'], $user->fields['substitution_end_date']);
        }
    }

    protected function providerUpdateSubstitutes_substitutes()
    {
        $this->login('normal', 'normal');
        yield 'not allowed to edit' => [
            'input' => [
                'users_id' => User::getIdByName('glpi'),
            ],
            'expected' => [
                'return' => false,
                'rows' => [],
                'messages' => ['You cannot change substitutes for this user.'],
            ],
        ];

        yield [
            'input' => [
                'users_id' => User::getIdByName('normal'),
                'substitutes' => [],
            ],
            'expected' => [
                'return' => true,
                'rows' => [],
            ],
        ];

        yield [
            'input' => [
                'users_id' => User::getIdByName('normal'),
                'substitutes' => [User::getIdByName('glpi')],
            ],
            'expected' => [
                'return' => true,
                'rows' => [
                    [
                        'users_id_substitute' => User::getIdByName('glpi'),
                    ],
                ],
            ],
        ];

        yield 'add a user as substitute of himself' => [
            'input' => [
                'users_id' => User::getIdByName('normal'),
                'substitutes' => [User::getIdByName('normal')],
            ],
            'expected' => [
                'return' => false,
                'rows' => [
                ],
                'messages' => ['A user cannot be their own substitute.'],
            ],
        ];
    }

    public function testUpdateSubstitutes_substitutes()
    {
        foreach ($this->providerUpdateSubstitutes_substitutes() as $row) {
            $input = $row['input'];
            $expected = $row['expected'];
            $instance = new \ValidatorSubstitute();

            $dbUtils = new DbUtils();
            $allRowsCount = $dbUtils->countElementsInTable($instance::getTable());
            $output = $instance->updateSubstitutes($input);

            // Check the return value
            $this->assertEquals($expected['return'], $output);

            if ($expected['return'] === false) {
                // Check error message
                $this->hasSessionMessages(ERROR, $expected['messages']);
                // Check that no rows was added / deleted
                $this->assertEquals($allRowsCount, $dbUtils->countElementsInTable($instance::getTable()));
                // Nothing more to check
                return;
            }

            // Check the expected rows count
            $rows = $instance->find([
                'users_id' => $input['users_id'],
            ]);
            $this->assertCount(count($expected['rows']), $rows);

            // Check the expected rows
            $rowsSearch = $rows;
            foreach ($rowsSearch as $id => $row) {
                foreach ($expected['rows'] as $expectedRow) {
                    if ($row['users_id_substitute'] == $expectedRow['users_id_substitute']) {
                        // Row found, removing it from the set
                        unset($rows[$id]);
                    }
                }
            }

            // if all rows are found, the array must now be empty
            $this->assertCount(0, $rows);
        }
    }

    public function testSubstitutesRemoval()
    {
        $delegator = getItemByTypeName(User::class, 'tech');

        //create 2 users
        $user_one = $this->createItem(User::class, ['name' => 'Marie']);
        $this->createItem(
            \ValidatorSubstitute::class,
            [
                'users_id' => $delegator->getID(),
                'users_id_substitute' => $user_one->getID(),
            ]
        );

        $user_two = $this->createItem(User::class, ['name' => 'Jean']);
        $this->createItem(
            \ValidatorSubstitute::class,
            [
                'users_id' => $delegator->getID(),
                'users_id_substitute' => $user_two->getID(),
            ]
        );

        //check substitutes setup
        $this->assertCount(2, $delegator->getSubstitutes());

        // Prepare a MassiveAction for deleting users
        $massive_ids = [
            $user_one->getID() => $user_one->getID(),
            $user_two->getID() => $user_two->getID(),
        ];
        $ma = new \MassiveAction(
            [
                'action' => 'purge',
                'action_name' => 'Purge',
                'items' => [User::class => $massive_ids],
            ],
            [],
            'process'
        );

        // Process the massive action
        $this->login();
        \MassiveAction::processMassiveActionsForOneItemtype($ma, new User(), $massive_ids);

        //make sure Users are removed
        $this->assertCount(0, $delegator->find(['name' => ['Marie', 'Jean']]));
    }
}
