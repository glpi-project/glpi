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

namespace tests\units\Glpi\Form\AccessControl\ControlType;

use AbstractRightsDropdown;
use DbTestCase;
use Glpi\Form\AccessControl\ControlType\AllowListDropdown;
use Group;
use Group_User;
use Profile;
use User;

class AllowListDropdownTest extends DbTestCase
{
    public function testAllUsers(): void
    {
        $this->checkCountUserForCriteria(
            criteria: ['users' => [AbstractRightsDropdown::ALL_USERS]],
            expected_users_count: 7,
        );
    }

    public function testEmptyAllowList(): void
    {
        $this->checkCountUserForCriteria(
            criteria: [],
            expected_users_count: 0,
        );
    }

    public function testAllowListWithSpecificUsers(): void
    {
        $this->checkCountUserForCriteria(
            criteria: [
                'users' => [
                    getItemByTypeName(User::class, '_test_user', true),
                    getItemByTypeName(User::class, 'jsmith123', true),
                ],
            ],
            expected_users_count: 2,
        );
    }

    public function testAllowListWithSpecificGroups(): void
    {
        $this->addToTestGroup1("_test_user");
        $this->addToTestGroup1("glpi");
        $this->addToTestGroup1("post-only");
        $this->checkCountUserForCriteria(
            criteria: [
                'groups' => [getItemByTypeName(Group::class, '_test_group_1', true)],
            ],
            expected_users_count: 3,
        );
    }

    public function testAllowListWithUserInChildGroup(): void
    {
        $this->addToTestGroup2("_test_user");
        $this->checkCountUserForCriteria(
            criteria: [
                'groups' => [getItemByTypeName(Group::class, '_test_group_1', true)],
            ],
            expected_users_count: 1,
        );
    }

    public function testAllowListWithSpecificProfiles(): void
    {
        $this->checkCountUserForCriteria(
            criteria: [
                'profiles' => [
                    getItemByTypeName(Profile::class, 'Technician', true), // Users with this profile: tech + e2e_tests
                    getItemByTypeName(Profile::class, 'Observer', true), // Users with this profile: normal + e2e_tests
                ],
            ],
            expected_users_count: 3,
        );
    }

    public function testAllowListWithMixedCriteria(): void
    {
        $this->addToTestGroup1("_test_user");
        $this->addToTestGroup1("glpi");
        $this->addToTestGroup1("post-only");
        $this->checkCountUserForCriteria(
            criteria: [
                'users' => [
                    getItemByTypeName(User::class, '_test_user', true),
                    getItemByTypeName(User::class, 'jsmith123', true),
                ],
                'groups' => [getItemByTypeName(Group::class, '_test_group_1', true)],
                'profiles' => [
                    getItemByTypeName(Profile::class, 'Technician', true), // Users with this profile: tech + e2e_tests
                    getItemByTypeName(Profile::class, 'Observer', true), // Users with this profile: normal + e2e_tests
                ],
            ],
            // Total = 7 (2 specifics users + 3 from group + 3 from profiles)
            // But _test_user is in both users and groups criteria, so we expect 6.
            expected_users_count: 7,
        );
    }

    private function addToTestGroup1(string $name): void
    {
        // Link post-only to a group.
        $this->createItem(Group_User::class, [
            'users_id'  => getItemByTypeName(User::class, $name, true),
            'groups_id' => getItemByTypeName(Group::class, '_test_group_1', true),
        ]);
    }

    private function addToTestGroup2(string $name): void
    {
        // Link post-only to a group.
        $this->createItem(Group_User::class, [
            'users_id'  => getItemByTypeName(User::class, $name, true),
            'groups_id' => getItemByTypeName(Group::class, '_test_group_2', true),
        ]);
    }

    private function checkCountUserForCriteria(
        int $expected_users_count,
        array $criteria,
    ): void {
        // Search engine rely on session data, we must be logged in.
        $this->login();

        $data = AllowListDropdown::countUsersForCriteria(
            $criteria['users'] ?? [],
            $criteria['groups'] ?? [],
            $criteria['profiles'] ?? [],
        );

        // Link should be properly validated by an E2E test
        $this->assertNotEmpty($data['link']);

        // Validate count
        $this->assertEquals($expected_users_count, $data['count']);
    }
}
