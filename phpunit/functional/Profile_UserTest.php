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

        // Default: 3 super admin account authorizations
        $authorizations = (new \Profile_User())->find([
            'profiles_id' => $super_admin->fields['id']
        ]);
        $this->assertCount(3, $authorizations);
        $this->assertEquals(
            [
                2, // glpi
                6, // TU_USER
                7, // jsmith123
            ],
            array_column($authorizations, 'id')
        );

        // Delete 2 authorizations
        $this->login('glpi', 'glpi');
        $this->assertTrue(\Profile_User::getById(6)->canPurgeItem());
        $this->assertTrue((new \Profile_User())->delete(['id' => 6], 1));
        $this->assertTrue(\Profile_User::getById(7)->canPurgeItem());
        $this->assertTrue((new \Profile_User())->delete(['id' => 7], 1));

        // Last user, can't be purged
        $this->assertFalse(\Profile_User::getById(2)->canPurgeItem());
        // Can still be purged by calling delete, maybe it should not be possible ?
        $this->assertTrue((new \Profile_User())->delete(['id' => 2], 1));
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
        $DB->truncate(\Log::getTable());

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
        $DB->truncate(\Log::getTable());

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
