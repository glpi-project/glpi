<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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
use CommonGLPI;
use Computer;
use Preference;
use DbUtils;
use User;
use DateTime;
use DateInterval;

/* Test for inc/RuleRight.class.php */

/**
 * @engine isolate
 */
class ValidatorSubstitute extends DbTestCase
{
    public function providerGetTabNameForItem()
    {
        yield [
            'item' => new Computer(),
            'expected' => '',
        ];

        yield [
            'item' => new Preference(),
            'expected' => 'Authorized substitute',
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

    public function providerCanEdit()
    {
        $this->login('post-only', 'postonly');
        yield [
            'id' => User::getIdByName('tech'),
            'expected' => false,
        ];

        // Normal has more rights than post-only
        // @see Profile::currentUserHaveMoreRightThan
        $this->login('normal', 'normal');
        yield [
            'id' => User::getIdByName('post-only'),
            'expected' => true,
        ];

        yield [
            'id' => User::getIdByName('normal'), // normal user
            'expected' => true,
        ];
    }

    /**
     * @dataProvider providerCanEdit
     *
     * @param integer $id
     * @param boolean $expected
     * @return void
     */
    public function testCanEdit(int $id, bool $expected)
    {
        $instance = $this->newTestedInstance;

        $output = $instance->canEdit($id);
        $this->boolean($output)->isEqualTo($expected);
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
                'messages' => ['Cannot add a user as substitute of himself.'],
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

    public function providerGetSubstitutes()
    {
        // remove all substitutes, if any
        $instance = $this->newTestedInstance;
        $instance->deleteByCriteria([
            'users_id' => User::getIdByName('normal'),
        ]);
        yield [
            'input' => User::getIdByName('normal'),
            'expected' => [],
        ];

        $this->login('normal', 'normal');
        $instance->updateSubstitutes([
            'users_id' => User::getIdByName('normal'),
            'substitutes' => [User::getIdByName('glpi')],
        ]);
        yield [
            'input' => User::getIdByName('normal'),
            'expected' => [User::getIdByName('glpi')],
        ];

        $instance->updateSubstitutes([
            'users_id' => User::getIdByName('normal'),
            'substitutes' => [User::getIdByName('glpi'), 3],
        ]);
        yield [
            'input' => User::getIdByName('normal'),
            'expected' => [User::getIdByName('glpi'), 3],
        ];
    }

    /**
     * @dataProvider providerGetSubstitutes
     *
     * @param integer $input
     * @param array $expected
     * @return void
     */
    public function testGetSubstitutes(int $input, array $expected)
    {
        $instance = $this->newTestedInstance;
        $output = $instance->getSubstitutes($input);
        $this->array($output)->isEqualTo($expected);
    }

    public function providerGetDelegators()
    {
        // remove all delegators, if any
        $instance = $this->newTestedInstance;
        $instance->deleteByCriteria([
            'users_id_substitute' => User::getIdByName('normal'),
        ]);
        yield [
            'input' => User::getIdByName('normal'),
            'expected' => [],
        ];

        $this->login('glpi', 'glpi');
        $instance->updateSubstitutes([
            'users_id' => User::getIdByName('glpi'),
            'substitutes' => [User::getIdByName('normal')],
        ]);
        yield [
            'input' => User::getIdByName('normal'),
            'expected' => [User::getIdByName('glpi')],
        ];

        $this->login('post-only', 'postonly');
        $instance->updateSubstitutes([
            'users_id' => User::getIdByName('post-only'),
            'substitutes' => [User::getIdByName('normal')],
        ]);
        yield [
            'input' => User::getIdByName('normal'),
            'expected' => [User::getIdByName('glpi'), User::getIdByName('post-only')],
        ];
    }

    /**
     * @dataProvider providerGetDelegators
     *
     * @param integer $input
     * @param array $expected
     * @return void
     */
    public function testGetDelegators(int $input, array $expected)
    {
        $instance = $this->newTestedInstance;
        $output = $instance->getDelegators($input);
        $this->array($output)->isEqualTo($expected);
    }

    public function providerIsUserSubstituteOf()
    {
        $instance = $this->newTestedInstance;
        $instance->deleteByCriteria([
            'users_id' => User::getIdByName('normal'),
        ]);
        yield [
            'users_id'           => User::getIdByName('glpi'),
            'users_id_delegator' => User::getIdByName('normal'),
            'use_date_range'     => false,
            'expected'           => false,
        ];

        $instance->add([
            'users_id' => User::getIdByName('normal'),
            'users_id_substitute' => User::getIdByName('glpi'),
        ]);
        yield [
            'users_id'           => User::getIdByName('glpi'),
            'users_id_delegator' => User::getIdByName('normal'),
            'use_date_range'     => false,
            'expected'           => true,
        ];

        $user = new User();
        $success = $user->update([
            'id' => User::getIdByName('normal'),
            'substitution_end_date' => '1999-01-01 12:00:00',
        ]);
        $this->boolean($success)->isTrue();
        yield [
            'users_id'           => User::getIdByName('glpi'),
            'users_id_delegator' => User::getIdByName('normal'),
            'use_date_range'     => true,
            'expected'           => false,
        ];

        $success = $user->update([
            'id' => User::getIdByName('normal'),
            'substitution_end_date' => '',
            'substitution_start_date' => (new DateTime())->add(new DateInterval('P1Y'))->format('Y-m-d H:i:s'),
        ]);
        $this->boolean($success)->isTrue();
        yield [
            'users_id'           => User::getIdByName('glpi'),
            'users_id_delegator' => User::getIdByName('normal'),
            'use_date_range'     => true,
            'expected'           => false,
        ];

        $success = $user->update([
            'id' => User::getIdByName('normal'),
            'substitution_end_date' => (new DateTime())->add(new DateInterval('P2Y'))->format('Y-m-d H:i:s'),
            'substitution_start_date' => (new DateTime())->add(new DateInterval('P1Y'))->format('Y-m-d H:i:s'),
        ]);
        $this->boolean($success)->isTrue();
        yield [
            'users_id'           => User::getIdByName('glpi'),
            'users_id_delegator' => User::getIdByName('normal'),
            'use_date_range'     => true,
            'expected'           => false,
        ];

        $success = $user->update([
            'id' => User::getIdByName('normal'),
            'substitution_end_date' => (new DateTime())->sub(new DateInterval('P1Y'))->format('Y-m-d H:i:s'),
            'substitution_start_date' => (new DateTime())->sub(new DateInterval('P2Y'))->format('Y-m-d H:i:s'),
        ]);
        $this->boolean($success)->isTrue();
        yield [
            'users_id'           => User::getIdByName('glpi'),
            'users_id_delegator' => User::getIdByName('normal'),
            'use_date_range'     => true,
            'expected'           => false,
        ];
    }

    /**
     * @dataProvider providerIsUserSubstituteOf
     *
     * @param integer $users_id
     * @param integer $users_id_delegator
     * @param boolean $use_date_range
     * @param [type] $expected
     * @return void
     */
    public function testIsUserSubstituteOf(int $users_id, int $users_id_delegator, bool $use_date_range, $expected)
    {
        $instance = $this->newTestedInstance;
        $output = $instance::isUserSubstituteOf($users_id, $users_id_delegator, $use_date_range);
        $this->boolean($output)->isEqualTo($expected);
    }
}
