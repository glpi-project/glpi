<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use Entity;
use Glpi\Tests\DbTestCase;
use Profile;
use Profile_User;
use User;

/**
 * Test for supervisor entity access validation in ajax/dropdownValidator.php
 * This tests the logic that prevents supervisors from being selected as validators
 * if they don't have access to the ticket's entity.
 */
class DropdownValidatorTest extends DbTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }


    /**
     * Reproduce the supervisor filtering logic from ajax/dropdownValidator.php
     *
     * @param array<int, array<string, int>> $requester_users Array of requester user data with 'users_id' key
     * @param ?int   $entity_id  The entity ID of the ticket
     * @return array<int, array<string, int|string>> Array of added supervisors
     */
    private function getFilteredSupervisors(array $requester_users, ?int $entity_id): array
    {
        $added_supervisors = [];

        foreach ($requester_users as $requester) {
            $requester = User::getById($requester['users_id']);
            if (!is_object($requester) || User::isNewId($requester->fields['users_id_supervisor'])) {
                // the user is not found or has no supervisor
                continue;
            }

            $supervisor_entities = Profile_User::getUserEntities($requester->fields['users_id_supervisor']);
            if (!in_array($entity_id ?? '', $supervisor_entities)) {
                continue;
            }

            $supervisor = User::getById($requester->fields['users_id_supervisor']);
            if (!is_object($supervisor)) {
                // the user does not have any supervisor
                continue;
            }

            $added_supervisors[] = [
                'id'    => $supervisor->getID(),
                'text'  => sprintf(__('%1$s (supervisor of %2$s)'), $supervisor->getFriendlyName(), $requester->getFriendlyName()),
                'title' => sprintf(__('%1$s - %2$s'), $supervisor->getFriendlyName(), $supervisor->getID()),
            ];
        }

        return $added_supervisors;
    }

    /**
     * Test that supervisors are only included if they have access to the ticket entity
     */
    public function testSupervisorWithSeperateEntities(): void
    {
        $this->login();

        // Create entities
        $root_entity_id = 0;
        $entity1 = $this->createItem(Entity::class, [
            'name'        => 'Entity 1 for supervisor test',
            'entities_id' => $root_entity_id,
        ]);
        $entity2 = $this->createItem(Entity::class, [
            'name'        => 'Entity 2 for supervisor test',
            'entities_id' => $root_entity_id,
        ]);

        // Get a profile that allows validation
        $tech_profile_id = getItemByTypeName(Profile::class, 'Technician', true);

        // Create supervisor 1 with access to entity 1
        $supervisor1 = $this->createItem(User::class, [
            'name'     => 'supervisor_entity1',
            'realname' => 'Supervisor',
            'firstname' => 'Entity1',
        ]);
        $this->createItem(Profile_User::class, [
            'users_id'     => $supervisor1->getID(),
            'profiles_id'  => $tech_profile_id,
            'entities_id'  => $entity1->getID(),
            'is_recursive' => 0,
        ]);

        // Create supervisor 2 with access to entity 2 only
        $supervisor2 = $this->createItem(User::class, [
            'name'     => 'supervisor_entity2',
            'realname' => 'Supervisor',
            'firstname' => 'Entity2',
        ]);
        $this->createItem(Profile_User::class, [
            'users_id'     => $supervisor2->getID(),
            'profiles_id'  => $tech_profile_id,
            'entities_id'  => $entity2->getID(),
            'is_recursive' => 0,
        ]);

        // Create requester 1 with supervisor 1
        $requester1 = $this->createItem(User::class, [
            'name'                => 'requester_with_sup1',
            'users_id_supervisor' => $supervisor1->getID(),
        ]);
        $this->createItem(Profile_User::class, [
            'users_id'     => $requester1->getID(),
            'profiles_id'  => getItemByTypeName(Profile::class, 'Self-Service', true),
            'entities_id'  => $entity1->getID(),
            'is_recursive' => 0,
        ]);

        // Create requester 2 with supervisor 2
        $requester2 = $this->createItem(User::class, [
            'name'                => 'requester_with_sup2',
            'users_id_supervisor' => $supervisor2->getID(),
        ]);
        $this->createItem(Profile_User::class, [
            'users_id'     => $requester2->getID(),
            'profiles_id'  => getItemByTypeName(Profile::class, 'Self-Service', true),
            'entities_id'  => $entity2->getID(),
            'is_recursive' => 0,
        ]);

        // Test Case 1: Supervisor WITH access to the entity should be included
        $requester_users = [['users_id' => $requester1->getID()]];
        $added_supervisors = $this->getFilteredSupervisors($requester_users, $entity1->getID());

        $this->assertCount(1, $added_supervisors);
        $this->assertEquals($supervisor1->getID(), $added_supervisors[0]['id']);

        // Test Case 2: Supervisor WITHOUT access to the entity should NOT be included
        $requester_users = [['users_id' => $requester2->getID()]];
        $added_supervisors = $this->getFilteredSupervisors($requester_users, $entity1->getID());

        $this->assertCount(0, $added_supervisors);

        // Test Case 3: Multiple requesters with different supervisors
        $requester_users = [
            ['users_id' => $requester1->getID()],
            ['users_id' => $requester2->getID()],
        ];
        $added_supervisors = $this->getFilteredSupervisors($requester_users, $entity1->getID());

        // Only supervisor 1 should be in the list (has access to entity 1)
        $this->assertCount(1, $added_supervisors);
        $this->assertEquals($supervisor1->getID(), $added_supervisors[0]['id']);
    }

    /**
     * Test supervisor with recursive access
     */
    public function testSupervisorWithRecursiveAccess(): void
    {
        $this->login();

        // Create parent and child entities
        $root_entity_id = 0;
        $parent_entity = $this->createItem(Entity::class, [
            'name'        => 'Parent entity recursive',
            'entities_id' => $root_entity_id,
        ]);
        $child_entity = $this->createItem(Entity::class, [
            'name'        => 'Child entity recursive',
            'entities_id' => $parent_entity->getID(),
        ]);

        $tech_profile_id = getItemByTypeName(Profile::class, 'Technician', true);

        // Create first user with RECURSIVE access to parent entity
        $user1 = $this->createItem(User::class, [
            'name'     => 'user1_recursive',
            'realname' => 'Recursive',
            'firstname' => 'Supervisor',
        ]);

        $user1_profile_access = $this->createItem(Profile_User::class, [
            'users_id'     => $user1->getID(),
            'profiles_id'  => $tech_profile_id,
            'entities_id'  => $parent_entity->getID(),
            'is_recursive' => 1, // Recursive access
        ]);

        // Create user two in child entity
        $user2 = $this->createItem(User::class, [
            'name'                => 'user2_child_entity',
            'users_id_supervisor' => $user1->getID(),
        ]);
        $this->createItem(Profile_User::class, [
            'users_id'     => $user2->getID(),
            'profiles_id'  => getItemByTypeName(Profile::class, 'Self-Service', true),
            'entities_id'  => $child_entity->getID(),
            'is_recursive' => 0,
        ]);
        $this->createItem(Profile_User::class, [
            'users_id'     => $user2->getID(),
            'profiles_id'  => $tech_profile_id,
            'entities_id'  => $child_entity->getID(),
            'is_recursive' => 0, // Recursive access
        ]);

        //Create user three in parent entity with user 2 as supervisor and without recursive access
        $user3 = $this->createItem(User::class, [
            'name'                => 'user3_parent_entity',
            'users_id_supervisor' => $user2->getID(),
        ]);
        $this->createItem(Profile_User::class, [
            'users_id'     => $user3->getID(),
            'profiles_id'  => getItemByTypeName(Profile::class, 'Self-Service', true),
            'entities_id'  => $parent_entity->getID(),
            'is_recursive' => 0,
        ]);

        // Test 1: user1 should be included for ticket in child entity (recursive access)
        $requester_users = [['users_id' => $user2->getID()]];
        $added_supervisors = $this->getFilteredSupervisors($requester_users, $child_entity->getID());

        $this->assertCount(1, $added_supervisors);
        $this->assertEquals($user1->getID(), $added_supervisors[0]['id']);

        // Test 2: user2 should NOT be included for ticket in parent entity (no recursive access)
        $requester_users = [['users_id' => $user3->getID()]];
        $added_supervisors = $this->getFilteredSupervisors($requester_users, $parent_entity->getID());
        $this->assertCount(0, $added_supervisors);

        //changing user1 access to non recursive and testing again
        $update = $user1_profile_access->update([
            'id' => $user1_profile_access->getID(),
            'is_recursive' => 0, // Non-recursive access
        ]);

        $this->assertTrue($update);

        // Test 3: user1 should NOT be included for ticket in child entity (no recursive access)
        $requester_users = [['users_id' => $user2->getID()]];
        $added_supervisors = $this->getFilteredSupervisors($requester_users, $child_entity->getID());
        $this->assertCount(0, $added_supervisors);
    }

    /**
     * Test with missing entity ID (should not add any supervisors since entity access cannot be verified)
     */
    public function testSupervisorWithMissingEntity(): void
    {
        $this->login();

        // Create a supervisor with access to an entity
        $entity = $this->createItem(Entity::class, [
            'name'        => 'Entity for missing entity test',
            'entities_id' => 0,
        ]);

        $tech_profile_id = getItemByTypeName(Profile::class, 'Technician', true);

        $supervisor = $this->createItem(User::class, [
            'name'     => 'supervisor_missing_entity',
            'realname' => 'Supervisor',
            'firstname' => 'MissingEntity',
        ]);
        $this->createItem(Profile_User::class, [
            'users_id'     => $supervisor->getID(),
            'profiles_id'  => $tech_profile_id,
            'entities_id'  => $entity->getID(),
            'is_recursive' => 0,
        ]);

        // Create a requester with the supervisor
        $requester = $this->createItem(User::class, [
            'name'                => 'requester_with_supervisor',
            'users_id_supervisor' => $supervisor->getID(),
        ]);
        $this->createItem(Profile_User::class, [
            'users_id'     => $requester->getID(),
            'profiles_id'  => getItemByTypeName(Profile::class, 'Self-Service', true),
            'entities_id'  => $entity->getID(),
            'is_recursive' => 0,
        ]);

        // Test with missing entity ID
        $requester_users = [['users_id' => $requester->getID()]];
        $this->getFilteredSupervisors($requester_users, null);

        // Since entity access cannot be verified, the supervisor should not be added
        $added_supervisors = $this->getFilteredSupervisors($requester_users, null);
        $this->assertCount(0, $added_supervisors);
    }

}
