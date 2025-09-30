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
use Glpi\DBAL\QueryExpression;

/**
 * Tests for Profile_User class
 */
class Profile_UserTest extends DbTestCase
{
    /**
     * Tests for Profile_User->canPurgeItem()
     *
     * @return void
     */
    public function testCanPurgeItem(): void
    {
        // Default: only one super admin account
        $super_admin = getItemByTypeName('Profile', 'Super-Admin');
        $this->assertTrue($super_admin->isLastSuperAdminProfile());

        // Default: 4 super admin account authorizations
        $authorizations = (new \Profile_User())->find([
            'profiles_id' => $super_admin->fields['id'],
        ]);
        $this->assertCount(4, $authorizations);
        $glpi_users_id = getItemByTypeName('User', 'glpi', true);
        $tu_users_id = getItemByTypeName('User', TU_USER, true);
        $jsmith_users_id = getItemByTypeName('User', 'jsmith123', true);
        $e2e_tests_users_id = getItemByTypeName('User', 'e2e_tests', true);

        $auth_array = array_column($authorizations, 'users_id');
        $this->assertContains($glpi_users_id, $auth_array);
        $this->assertContains($tu_users_id, $auth_array);
        $this->assertContains($jsmith_users_id, $auth_array);
        $this->assertContains($e2e_tests_users_id, $auth_array);

        $authorizations_by_user_id = [];
        foreach ($authorizations as $authorization) {
            $authorizations_by_user_id[$authorization['users_id']] = $authorization['id'];
        }

        // Delete 2 authorizations
        $this->login('glpi', 'glpi');
        $this->assertTrue(\Profile_User::getById($authorizations_by_user_id[$tu_users_id])->canPurgeItem());
        $this->assertTrue((new \Profile_User())->delete(['id' => $authorizations_by_user_id[$tu_users_id]], 1));
        $this->assertTrue(\Profile_User::getById($authorizations_by_user_id[$jsmith_users_id])->canPurgeItem());
        $this->assertTrue((new \Profile_User())->delete(['id' => $authorizations_by_user_id[$jsmith_users_id]], 1));
        $this->assertTrue(\Profile_User::getById($authorizations_by_user_id[$e2e_tests_users_id])->canPurgeItem());
        $this->assertTrue((new \Profile_User())->delete(['id' => $authorizations_by_user_id[$e2e_tests_users_id]], 1));

        // Last user, can't be purged
        $this->assertFalse(\Profile_User::getById($authorizations_by_user_id[$glpi_users_id])->canPurgeItem());
        // Can still be purged by calling delete, maybe it should not be possible ?
        $this->assertTrue((new \Profile_User())->delete(['id' => $authorizations_by_user_id[$glpi_users_id]], 1));
    }

    public function testLogOperationOnAddAndDelete(): void
    {
        global $DB;

        $user     = getItemByTypeName(\User::class, 'glpi');
        $profile1 = getItemByTypeName(\Profile::class, 'Self-Service');
        $profile2 = getItemByTypeName(\Profile::class, 'Observer');
        $entity1  = getItemByTypeName(\Entity::class, '_test_root_entity');
        $entity2  = getItemByTypeName(\Entity::class, '_test_child_1');

        // Create items
        $DB->delete(\Log::getTable(), [new QueryExpression('true')]);

        $input1 = [
            'users_id'     => $user->getId(),
            'profiles_id'  => $profile1->getId(),
            'entities_id'  => $entity1->getId(),
            'is_dynamic'   => 0,
            'is_recursive' => 0,
        ];
        $input2 = [
            'users_id'     => $user->getId(),
            'profiles_id'  => $profile1->getId(),
            'entities_id'  => $entity2->getId(),
            'is_dynamic'   => 0,
            'is_recursive' => 1,
        ];
        $input3 = [
            'users_id'     => $user->getId(),
            'profiles_id'  => $profile2->getId(),
            'entities_id'  => $entity2->getId(),
            'is_dynamic'   => 1,
            'is_recursive' => 1,
        ];
        $this->createItems(\Profile_User::class, [$input1, $input2, $input3]);

        // Check created log entries
        $expected_entries = [
            // Log entries for first profile
            [
                'itemtype'      => \User::class,
                'items_id'      => $user->getId(),
                'itemtype_link' => \Profile::class,
                'linked_action' => \Log::HISTORY_ADD_SUBITEM,
                'old_value'     => '',
                'new_value'     => sprintf(
                    '%s (%s), %s (%s)',
                    $entity1->fields['completename'],
                    $entity1->fields['id'],
                    $profile1->fields['name'],
                    $profile1->fields['id'],
                ),
            ],
            [
                'itemtype'      => \Profile::class,
                'items_id'      => $profile1->getId(),
                'itemtype_link' => \User::class,
                'linked_action' => \Log::HISTORY_ADD_SUBITEM,
                'old_value'     => '',
                'new_value'     => sprintf(
                    '%s (%s), %s (%s)',
                    $user->fields['name'],
                    $user->fields['id'],
                    $entity1->fields['completename'],
                    $entity1->fields['id'],
                ),
            ],
            [
                'itemtype'      => \Entity::class,
                'items_id'      => $entity1->getId(),
                'itemtype_link' => \User::class,
                'linked_action' => \Log::HISTORY_ADD_SUBITEM,
                'old_value'     => '',
                'new_value'     => sprintf(
                    '%s (%s), %s (%s)',
                    $user->fields['name'],
                    $user->fields['id'],
                    $profile1->fields['name'],
                    $profile1->fields['id'],
                ),
            ],
            // Log entries for second profile
            [
                'itemtype'      => \User::class,
                'items_id'      => $user->getId(),
                'itemtype_link' => \Profile::class,
                'linked_action' => \Log::HISTORY_ADD_SUBITEM,
                'old_value'     => '',
                'new_value'     => sprintf(
                    '%s (%s), %s (%s) (R)',
                    $entity2->fields['completename'],
                    $entity2->fields['id'],
                    $profile1->fields['name'],
                    $profile1->fields['id'],
                ),
            ],
            [
                'itemtype'      => \Profile::class,
                'items_id'      => $profile1->getId(),
                'itemtype_link' => \User::class,
                'linked_action' => \Log::HISTORY_ADD_SUBITEM,
                'old_value'     => '',
                'new_value'     => sprintf(
                    '%s (%s), %s (%s) (R)',
                    $user->fields['name'],
                    $user->fields['id'],
                    $entity2->fields['completename'],
                    $entity2->fields['id'],
                ),
            ],
            [
                'itemtype'      => \Entity::class,
                'items_id'      => $entity2->getId(),
                'itemtype_link' => \User::class,
                'linked_action' => \Log::HISTORY_ADD_SUBITEM,
                'old_value'     => '',
                'new_value'     => sprintf(
                    '%s (%s), %s (%s) (R)',
                    $user->fields['name'],
                    $user->fields['id'],
                    $profile1->fields['name'],
                    $profile1->fields['id'],
                ),
            ],
            // Log entries for third profile
            [
                'itemtype'      => \User::class,
                'items_id'      => $user->getId(),
                'itemtype_link' => \Profile::class,
                'linked_action' => \Log::HISTORY_ADD_SUBITEM,
                'old_value'     => '',
                'new_value'     => sprintf(
                    '%s (%s), %s (%s) (D, R)',
                    $entity2->fields['completename'],
                    $entity2->fields['id'],
                    $profile2->fields['name'],
                    $profile2->fields['id'],
                ),
            ],
            [
                'itemtype'      => \Profile::class,
                'items_id'      => $profile2->getId(),
                'itemtype_link' => \User::class,
                'linked_action' => \Log::HISTORY_ADD_SUBITEM,
                'old_value'     => '',
                'new_value'     => sprintf(
                    '%s (%s), %s (%s) (D, R)',
                    $user->fields['name'],
                    $user->fields['id'],
                    $entity2->fields['completename'],
                    $entity2->fields['id'],
                ),
            ],
            [
                'itemtype'      => \Entity::class,
                'items_id'      => $entity2->getId(),
                'itemtype_link' => \User::class,
                'linked_action' => \Log::HISTORY_ADD_SUBITEM,
                'old_value'     => '',
                'new_value'     => sprintf(
                    '%s (%s), %s (%s) (D, R)',
                    $user->fields['name'],
                    $user->fields['id'],
                    $profile2->fields['name'],
                    $profile2->fields['id'],
                ),
            ],
        ];

        $this->assertEquals(count($expected_entries), countElementsInTable(\Log::getTable()));

        foreach ($expected_entries as $expected_entry) {
            $this->assertEquals(1, countElementsInTable(\Log::getTable(), $expected_entry));
        }

        // Delete items
        $DB->delete(\Log::getTable(), [new QueryExpression('true')]);

        $profile_user = new \Profile_User();
        $this->assertTrue($profile_user->deleteByCriteria($input1));
        $this->assertTrue($profile_user->deleteByCriteria($input2));
        $this->assertTrue($profile_user->deleteByCriteria($input3));

        // Check created log entries
        $expected_entries = [
            // Log entries for first profile
            [
                'itemtype'      => \User::class,
                'items_id'      => $user->getId(),
                'itemtype_link' => \Profile::class,
                'linked_action' => \Log::HISTORY_DELETE_SUBITEM,
                'old_value'     => sprintf(
                    '%s (%s), %s (%s)',
                    $entity1->fields['completename'],
                    $entity1->fields['id'],
                    $profile1->fields['name'],
                    $profile1->fields['id'],
                ),
                'new_value'     => '',
            ],
            [
                'itemtype'      => \Profile::class,
                'items_id'      => $profile1->getId(),
                'itemtype_link' => \User::class,
                'linked_action' => \Log::HISTORY_DELETE_SUBITEM,
                'old_value'     => sprintf(
                    '%s (%s), %s (%s)',
                    $user->fields['name'],
                    $user->fields['id'],
                    $entity1->fields['completename'],
                    $entity1->fields['id'],
                ),
                'new_value'     => '',
            ],
            [
                'itemtype'      => \Entity::class,
                'items_id'      => $entity1->getId(),
                'itemtype_link' => \User::class,
                'linked_action' => \Log::HISTORY_DELETE_SUBITEM,
                'old_value'     => sprintf(
                    '%s (%s), %s (%s)',
                    $user->fields['name'],
                    $user->fields['id'],
                    $profile1->fields['name'],
                    $profile1->fields['id'],
                ),
                'new_value'     => '',
            ],
            // Log entries for second profile
            [
                'itemtype'      => \User::class,
                'items_id'      => $user->getId(),
                'itemtype_link' => \Profile::class,
                'linked_action' => \Log::HISTORY_DELETE_SUBITEM,
                'old_value'     => sprintf(
                    '%s (%s), %s (%s) (R)',
                    $entity2->fields['completename'],
                    $entity2->fields['id'],
                    $profile1->fields['name'],
                    $profile1->fields['id'],
                ),
                'new_value'     => '',
            ],
            [
                'itemtype'      => \Profile::class,
                'items_id'      => $profile1->getId(),
                'itemtype_link' => \User::class,
                'linked_action' => \Log::HISTORY_DELETE_SUBITEM,
                'old_value'     => sprintf(
                    '%s (%s), %s (%s) (R)',
                    $user->fields['name'],
                    $user->fields['id'],
                    $entity2->fields['completename'],
                    $entity2->fields['id'],
                ),
                'new_value'     => '',
            ],
            [
                'itemtype'      => \Entity::class,
                'items_id'      => $entity2->getId(),
                'itemtype_link' => \User::class,
                'linked_action' => \Log::HISTORY_DELETE_SUBITEM,
                'old_value'     => sprintf(
                    '%s (%s), %s (%s) (R)',
                    $user->fields['name'],
                    $user->fields['id'],
                    $profile1->fields['name'],
                    $profile1->fields['id'],
                ),
                'new_value'     => '',
            ],
            // Log entries for third profile
            [
                'itemtype'      => \User::class,
                'items_id'      => $user->getId(),
                'itemtype_link' => \Profile::class,
                'linked_action' => \Log::HISTORY_DELETE_SUBITEM,
                'old_value'     => sprintf(
                    '%s (%s), %s (%s) (D, R)',
                    $entity2->fields['completename'],
                    $entity2->fields['id'],
                    $profile2->fields['name'],
                    $profile2->fields['id'],
                ),
                'new_value'     => '',
            ],
            [
                'itemtype'      => \Profile::class,
                'items_id'      => $profile2->getId(),
                'itemtype_link' => \User::class,
                'linked_action' => \Log::HISTORY_DELETE_SUBITEM,
                'old_value'     => sprintf(
                    '%s (%s), %s (%s) (D, R)',
                    $user->fields['name'],
                    $user->fields['id'],
                    $entity2->fields['completename'],
                    $entity2->fields['id'],
                ),
                'new_value'     => '',
            ],
            [
                'itemtype'      => \Entity::class,
                'items_id'      => $entity2->getId(),
                'itemtype_link' => \User::class,
                'linked_action' => \Log::HISTORY_DELETE_SUBITEM,
                'old_value'     => sprintf(
                    '%s (%s), %s (%s) (D, R)',
                    $user->fields['name'],
                    $user->fields['id'],
                    $profile2->fields['name'],
                    $profile2->fields['id'],
                ),
                'new_value'     => '',
            ],
        ];

        $this->assertEquals(count($expected_entries), countElementsInTable(\Log::getTable()));

        foreach ($expected_entries as $expected_entry) {
            $this->assertEquals(1, countElementsInTable(\Log::getTable(), $expected_entry));
        }
    }
}
