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

namespace tests\units\Glpi\Form\AccessControl\ControlType;

use DbTestCase;
use Group;
use Group_User;
use Profile;
use User;

class AllowListDropdown extends DbTestCase
{
    protected function testCountUsersForCriteriaProvider(): iterable
    {
        // Search engine rely on session data, we must be logged in.
        $this->login();

        // There are 8 users in the database:
        // - 5 default users (glpi, normal, post-only, tech, glpi-system)
        // - 2 from bootstrap data (_test_user, jsmith123)
        // - 1 from empty_data.php (2e2_tests)
        yield [
            'users'    => [],
            'groups'   => [],
            'profiles' => [],
            'expected' => 8,
        ];

        // Allow only 2 specific users.
        yield [
            'users'    => [
                getItemByTypeName(User::class, '_test_user', true),
                getItemByTypeName(User::class, 'jsmith123', true),
            ],
            'groups'   => [],
            'profiles' => [],
            'expected' => 2, // _test_user + jsmith123
        ];

        // Link post-only to a group and insert it into the allowlist.
        $this->createItem(Group_User::class, [
            'users_id'  => getItemByTypeName(User::class, 'post-only', true),
            'groups_id' => getItemByTypeName(Group::class, '_test_group_1', true),
        ]);
        yield [
            'users'    => [
                getItemByTypeName(User::class, '_test_user', true),
                getItemByTypeName(User::class, 'jsmith123', true),
            ],
            'groups'   => [
                getItemByTypeName(Group::class, '_test_group_1', true)
            ],
            'profiles' => [],
            'expected' => 3, // _test_user + jsmith123 + post-only (from group)
        ];

        // Allow super-administrators (glpi + e2e_tests)
        yield [
            'users'    => [
                getItemByTypeName(User::class, '_test_user', true),
                getItemByTypeName(User::class, 'jsmith123', true),
            ],
            'groups'   => [
                getItemByTypeName(Group::class, '_test_group_1', true)
            ],
            'profiles' => [
                getItemByTypeName(Profile::class, 'Super-Admin', true)
            ],
            'expected' => 5, // _test_user and jsmith123 + post-only (from group) + glpi and e2e_tests (from profile)
        ];
    }

    /**
     * Tests for the `countUsersForCriteria` method.
     *
     * @param array $users
     * @param array $groups
     * @param array $profiles
     *
     * @dataProvider testCountUsersForCriteriaProvider
     */
    public function testCountUsersForCriteria(
        array $users,
        array $groups,
        array $profiles,
        int $expected
    ): void {
        $data = \Glpi\Form\AccessControl\ControlType\AllowListDropdown::countUsersForCriteria(
            $users,
            $groups,
            $profiles
        );

        // Link should be properly validated by an E2E test
        $this->string($data['link'])->isNotEmpty();

        // Validate count
        $this->integer($data['count'])->isEqualTo($expected);
    }
}
