<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

use Auth;
use DbTestCase;
use CommonGLPI;
use Computer;
use Preference;
use DbUtils;
use Session;
use User;

/* Test for inc/RuleRight.class.php */

/**
 * @engine isolate
 */
class ValidatorSubstitute extends DbTestCase
{
    public function providerGetTabNameForItem()
    {
        //login to get session
        $auth = new Auth();
        $this->boolean($auth->login(TU_USER, TU_PASS, true))->isTrue();

        yield [
            'item' => new Computer(),
            'expected' => '',
        ];

        yield [
            'item' => new Preference(),
            'expected' => 'Authorized substitutes',
        ];

        $validatorSubstitute = new $this->newTestedInstance();
        $validatorSubstitute->add([
            'users_id' => Session::getLoginUserId(),
            'users_id_substitute' => User::getIdByName('glpi'),
        ]);

        yield [
            'item' => new Preference(),
            'expected' => "Authorized substitute <span class='badge bg-secondary text-secondary-fg'>1</span>",
        ];

        $_SESSION['glpishow_count_on_tabs'] = 0;

        yield [
            'item' => new Preference(),
            'expected' => "Authorized substitutes",
        ];

        $_SESSION['glpishow_count_on_tabs'] = 1;
        $validatorSubstitute = new $this->newTestedInstance();
        $validatorSubstitute->add([
            'users_id' => Session::getLoginUserId(),
            'users_id_substitute' => User::getIdByName('tech'),
        ]);

        yield [
            'item' => new Preference(),
            'expected' => "Authorized substitutes <span class='badge bg-secondary text-secondary-fg'>2</span>",
        ];

        $_SESSION['glpishow_count_on_tabs'] = 0;

        yield [
            'item' => new Preference(),
            'expected' => "Authorized substitutes",
        ];
    }

    /**
     * @dataProvider providerGetTabNameForItem
     *
     * @param CommonGLPI $item
     * @param string $expected
     * @return void
     */
    public function testGetTabNameForItem(CommonGLPI $item, string $expected)
    {
        $instance = $this->newTestedInstance;

        $output = $instance->getTabNameForItem($item);
        $this->string($output)->isEqualTo($expected);
    }

    public function providerCanCreateItem()
    {
        yield [
            'input' => [
            ],
            'expected' => false,
        ];

        $this->login('glpi', 'glpi');
        yield [
            'input' => [
                'users_id' => 1
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

    /**
     * @dataProvider providerCanCreateItem
     *
     * @param array $input
     * @param bool  $expected
     * @return void
     */
    public function testCanCreateItem(array $input, bool $expected)
    {
        $instance = $this->newTestedInstance;
        $instance->fields = $input;
        $output = $instance->canCreateItem();
        $this->boolean($output)->isEqualTo($expected);
    }

    public function providerCanViewItem()
    {
        yield [
            'input' => [
            ],
            'expected' => false,
        ];

        $_SESSION['glpiID'] = 2;
        yield [
            'input' => [
                'users_id' => 1
            ],
            'expected' => false,
        ];

        yield [
            'input' => [
                'users_id' => User::getIdByName('glpi')
            ],
            'expected' => true,
        ];
    }

    /**
     * @dataProvider providerCanViewItem
     *
     * @param array $input
     * @param boolean $expected
     * @return void
     */
    public function testCanViewItem(array $input, bool $expected)
    {
        $instance = $this->newTestedInstance;
        $instance->fields = $input;
        $output = $instance->canViewItem();
        $this->boolean($output)->isEqualTo($expected);
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
                'users_id' => 1
            ],
            'expected' => false,
        ];

        yield [
            'input' => [
                'users_id' => User::getIdByName('glpi')
            ],
            'expected' => true,
        ];
    }

    /**
     * @dataProvider providerCanUpdateItem
     *
     * @param array $input
     * @param boolean $expected
     * @return void
     */
    public function testCanUpdateItem(array $input, bool $expected)
    {
        $instance = $this->newTestedInstance;
        $instance->fields = $input;
        $output = $instance->canUpdateItem();
        $this->boolean($output)->isEqualTo($expected);
    }

    public function providerCanDeleteItem()
    {
        yield [
            'input' => [
            ],
            'expected' => false,
        ];

        $_SESSION['glpiID'] = 2;
        yield [
            'input' => [
                'users_id' => 1
            ],
            'expected' => false,
        ];

        yield [
            'input' => [
                'users_id' => User::getIdByName('glpi')
            ],
            'expected' => true,
        ];
    }

    /**
     * @dataProvider providerCanDeleteItem
     *
     * @param array $input
     * @param boolean $expected
     * @return void
     */
    public function testCanDeleteItem(array $input, bool $expected)
    {
        $instance = $this->newTestedInstance;
        $instance->fields = $input;
        $output = $instance->canDeleteItem();
        $this->boolean($output)->isEqualTo($expected);
    }

    public function providerCanPurgeItem()
    {
        yield [
            'input' => [
            ],
            'expected' => false,
        ];

        $_SESSION['glpiID'] = 2;
        yield [
            'input' => [
                'users_id' => 1
            ],
            'expected' => false,
        ];

        yield [
            'input' => [
                'users_id' => User::getIdByName('glpi')
            ],
            'expected' => true,
        ];
    }

    /**
     * @dataProvider providerCanPurgeItem
     *
     * @param array $input
     * @param boolean $expected
     * @return void
     */
    public function testCanPurgeItem(array $input, bool $expected)
    {
        $instance = $this->newTestedInstance;
        $instance->fields = $input;
        $output = $instance->canPurgeItem();
        $this->boolean($output)->isEqualTo($expected);
    }

    public function providerUpdateSubstitutes_dateRange()
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

    /**
     * @dataProvider providerUpdateSubstitutes_dateRange
     *
     * Tests update of substitute date range only (not substitutes users)
     *
     * @param array $input
     * @param array $expected
     * @return void
     */
    public function testUpdateSubstitutes_dateRange(array $input, array $expected)
    {
        $instance = $this->newTestedInstance;

        $output = $instance->updateSubstitutes($input);

        // Check the return value
        $this->boolean($output)->isEqualTo($expected['return']);

        if ($expected['return'] === false) {
            // Check error message
            $this->hasSessionMessages(ERROR, $expected['messages']);
            // Nothing more to check
            return;
        }

        // Check the expected date range
        $user = User::getById($input['users_id']);
        $this->object($user)->isInstanceOf(User::class);

        if ($expected['range']['start'] === null) {
            $this->variable($user->fields['substitution_start_date'])->isNull();
        } else {
            $this->string($user->fields['substitution_start_date'])->isEqualTo($expected['range']['start']);
        }
        if ($expected['range']['end'] === null) {
            $this->variable($user->fields['substitution_end_date'])->isNull();
        } else {
            $this->string($user->fields['substitution_end_date'])->isEqualTo($expected['range']['end']);
        }
    }

    public function providerUpdateSubstitutes_substitutes()
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
                    ]
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

    /**
     * @dataProvider providerUpdateSubstitutes_substitutes
     *
     * Tests update of substitutes only (not date range)
     *
     * @param array $input
     * @param array $expected
     * @return void
     */
    public function testUpdateSubstitutes_substitutes(array $input, array $expected)
    {
        $instance = $this->newTestedInstance;

        $dbUtils = new DbUtils();
        $allRowsCount = $dbUtils->countElementsInTable($instance::getTable());
        $output = $instance->updateSubstitutes($input);

        // Check the return value
        $this->boolean($output)->isEqualTo($expected['return']);

        if ($expected['return'] === false) {
            // Check error message
            $this->hasSessionMessages(ERROR, $expected['messages']);
            // Check that no rows was added / deleted
            $this->integer($dbUtils->countElementsInTable($instance::getTable()))->isEqualTo($allRowsCount);
            // Nothing more to check
            return;
        }

        // Check the expected rows count
        $rows = $instance->find([
            'users_id' => $input['users_id'],
        ]);
        $this->array($rows)->hasSize(count($expected['rows']));
        if (count($expected['rows']) == 0) {
            // Nothing more to test
            return;
        }

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
        $this->array($rows)->hasSize(0);
    }
}
