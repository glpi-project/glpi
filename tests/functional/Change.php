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

/* Test for inc/change.class.php */

class Change extends DbTestCase
{
    public function testAddFromItem()
    {
       // add change from a computer
        $computer   = getItemByTypeName('Computer', '_test_pc01');
        $change     = new \Change();
        $changes_id = $change->add([
            'name'           => "test add from computer \'_test_pc01\'",
            'content'        => "test add from computer \'_test_pc01\'",
            '_add_from_item' => true,
            '_from_itemtype' => 'Computer',
            '_from_items_id' => $computer->getID(),
        ]);
        $this->integer($changes_id)->isGreaterThan(0);
        $this->boolean($change->getFromDB($changes_id))->isTrue();

       // check relation
        $change_item = new \Change_Item();
        $this->boolean($change_item->getFromDBForItems($change, $computer))->isTrue();
    }

    public function testAssignFromCategory()
    {
        $this->login('glpi', 'glpi');
        $entity = new \Entity();
        $entityId = $entity->import([
            'name' => 'an entity configured to check change auto assignation of user ad group',
            'entities_id' => 0,
            'level' => 1,
            'auto_assign_mode' => \Entity::CONFIG_NEVER,
        ]);

        $this->boolean($entity->isNewID($entityId))->isFalse();
        $entity->getFromDB($entityId);
        $this->integer((int) $entity->fields['auto_assign_mode'])->isEqualTo(\Entity::CONFIG_NEVER);

       // Login again to acess the new entity
        $this->login('glpi', 'glpi');
        $success = \Session::changeActiveEntities($entity->getID(), true);
        $this->boolean($success)->isTrue();

        $group = new \Group();
        $group->add([
            'name' => 'A group to check automatic tech and group assignation',
            'entities_id' => 0,
            'is_recursive' => '1',
            'level' => 0,
        ]);
        $this->boolean($group->isNewItem())->isFalse();

        $itilCategory = new \ITILCategory();
        $itilCategory->add([
            'name' => 'A category to check automatic tech and group assignation',
            'itilcategories_id' => 0,
            'users_id' => 4, // Tech
            'groups_id' => $group->getID(),
        ]);
        $this->boolean($itilCategory->isNewItem())->isFalse();

        $change = new \Change();
        $change->add([
            'name' => 'A change to check if it is not automatically assigned user and group',
            'content' => 'foo',
            'itilcategories_id' => $itilCategory->getID(),
        ]);
        $this->boolean($change->isNewItem())->isFalse();
        $change->getFromDB($change->getID());
        $changeUser = new \Change_User();
        $changeGroup = new \Change_Group();
        $rows = $changeUser->find([
            'changes_id' => $change->getID(),
            'type'       => \CommonITILActor::ASSIGN,
        ]);
        $this->integer(count($rows))->isEqualTo(0);
        $rows = $changeGroup->find([
            'changes_id' => $change->getID(),
            'type'       => \CommonITILActor::ASSIGN,
        ]);

       // check Entity::AUTO_ASSIGN_HARDWARE_CATEGORY assignment
        $entity->update([
            'id' => $entity->getID(),
            'auto_assign_mode' => \Entity::AUTO_ASSIGN_HARDWARE_CATEGORY,
        ]);

        $change = new \Change();
        $change->add([
            'name' => 'A change to check if it is automatically assigned user and group (1)',
            'content' => 'foo',
            'itilcategories_id' => $itilCategory->getID(),
        ]);
        $this->boolean($change->isNewItem())->isFalse();
        $change->getFromDB($change->getID());
        $changeUser = new \Change_User();
        $changeGroup = new \Change_Group();
        $rows = $changeUser->find([
            'changes_id' => $change->getID(),
            'users_id'   => 4, // Tech
            'type'       => \CommonITILActor::ASSIGN,
        ]);
        $this->integer(count($rows))->isEqualTo(0);
        $rows = $changeGroup->find([
            'changes_id' => $change->getID(),
            'groups_id'  => $group->getID(),
            'type'       => \CommonITILActor::ASSIGN,
        ]);

       // check Entity::AUTO_ASSIGN_CATEGORY_HARDWARE assignment
        $entity->update([
            'id' => $entity->getID(),
            'auto_assign_mode' => \Entity::AUTO_ASSIGN_CATEGORY_HARDWARE,
        ]);

        $change = new \Change();
        $change->add([
            'name' => 'A change to check if it is automatically assigned user and group (2)',
            'content' => 'foo',
            'itilcategories_id' => $itilCategory->getID(),
        ]);
        $this->boolean($change->isNewItem())->isFalse();
        $change->getFromDB($change->getID());
        $changeUser = new \Change_User();
        $changeGroup = new \Change_Group();
        $rows = $changeUser->find([
            'changes_id' => $change->getID(),
            'users_id'   => 4, // Tech
            'type'       => \CommonITILActor::ASSIGN,
        ]);
        $this->integer(count($rows))->isEqualTo(0);
        $rows = $changeGroup->find([
            'changes_id' => $change->getID(),
            'groups_id'  => $group->getID(),
            'type'       => \CommonITILActor::ASSIGN,
        ]);
    }

    public function testGetTeamRoles(): void
    {
        $roles = \Change::getTeamRoles();
        $this->array($roles)->containsValues([
            \CommonITILActor::ASSIGN,
            \CommonITILActor::OBSERVER,
            \CommonITILActor::REQUESTER,
        ]);
    }

    public function testGetTeamRoleName(): void
    {
        $roles = \Change::getTeamRoles();
        foreach ($roles as $role) {
            $this->string(\Change::getTeamRoleName($role))->isNotEmpty();
        }
    }

    public function testAutomaticStatusChange()
    {
        $this->login();
        // Create a change
        $change = new \Change();
        $changes_id = $change->add([
            'name' => "test automatic status change",
            'content' => "test automatic status change",
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);

        // Initial status is new (incoming)
        $this->integer($change->fields['status'])->isIdenticalTo(\CommonITILObject::INCOMING);

        $change->update([
            'id' => $changes_id,
            '_itil_assign' => [
                '_type' => "user",
                'users_id' => getItemByTypeName('User', TU_USER, true),
            ],
        ]);
        $test_users_id = getItemByTypeName('User', TU_USER, true);
        $this->integer($test_users_id)->isGreaterThan(0);

        // Verify user was assigned and status doesn't change
        $change->loadActors();
        $this->integer($change->countUsers(\CommonITILActor::ASSIGN))->isIdenticalTo(1);
        $this->integer($change->fields['status'])->isIdenticalTo(\CommonITILObject::INCOMING);

        // Change status to accepted
        $change->update([
            'id' => $changes_id,
            'status' => \CommonITILObject::ACCEPTED,
        ]);
        // Unassign change and expect the status to stay accepted
        $change_user = new \Change_User();
        $change_user->deleteByCriteria([
            'changes_id' => $changes_id,
            'type' => \CommonITILActor::ASSIGN,
            'users_id' => getItemByTypeName('User', TU_USER, true),
        ]);
        $change->getFromDB($changes_id);
        $this->integer($change->countUsers(\CommonITILActor::ASSIGN))->isIdenticalTo(0);
        $this->integer($change->fields['status'])->isIdenticalTo(\CommonITILObject::ACCEPTED);
    }

    public function testAddAdditionalActorsDuplicated()
    {
        $this->login();
        $change = new \Change();
        $changes_id = $change->add([
            'name'           => "test add additional actors duplicated",
            'content'        => "test add additional actors duplicated",
        ]);
        $this->integer($changes_id)->isGreaterThan(0);

        $users_id = getItemByTypeName('User', TU_USER, true);

        $result = $change->update([
            'id'                       => $changes_id,
            '_additional_requesters'   => [
                [
                    'users_id' => $users_id,
                    'use_notification'  => 0,
                ]
            ]
        ]);
        $this->boolean($result)->isTrue();

        $result = $change->update([
            'id'                       => $changes_id,
            '_additional_requesters'   => [
                [
                    'users_id' => $users_id,
                    'use_notification'  => 0,
                ]
            ]
        ]);
        $this->boolean($result)->isTrue();
    }

    public function testInitialStatus()
    {
        $this->login();
        $change = new \Change();
        $changes_id = $change->add([
            'name' => "test initial status",
            'content' => "test initial status",
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            '_users_id_assign' => getItemByTypeName('User', TU_USER, true),
        ]);
        $this->integer($changes_id)->isGreaterThan(0);
        // Even when automatically assigning a user, the initial status should be set to New
        $this->integer($change->fields['status'])->isIdenticalTo(\CommonITILObject::INCOMING);
    }

    public function testStatusWhenSolutionIsRefused()
    {
        $this->login();
        $change = new \Change();
        $changes_id = $change->add([
            'name' => "test initial status",
            'content' => "test initial status",
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            '_users_id_assign' => getItemByTypeName('User', TU_USER, true),
            'status'    => \CommonITILObject::SOLVED,
        ]);
        $this->integer($changes_id)->isGreaterThan(0);

        $followup = new \ITILFollowup();
        $followup_id = $followup->add([
            'itemtype' => 'Change',
            'items_id' => $changes_id,
            'users_id' => getItemByTypeName('User', TU_USER, true),
            'users_id_editor' => getItemByTypeName('User', TU_USER, true),
            'content' => 'Test followup content',
            'requesttypes_id' => 1,
            'timeline_position' => \CommonITILObject::TIMELINE_LEFT,
            'add_reopen' => ''
        ]);
        $this->integer($followup_id)->isGreaterThan(0);

        $item = $change->getById($changes_id);
        $this->integer($item->fields['status'])->isIdenticalTo(\CommonITILObject::INCOMING);
    }
}
