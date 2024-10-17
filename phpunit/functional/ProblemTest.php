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

/* Test for inc/problem.class.php */

class ProblemTest extends DbTestCase
{
    public function testAddFromItem()
    {
        // add problem from a computer
        $computer   = getItemByTypeName('Computer', '_test_pc01');
        $problem     = new \Problem();
        $problems_id = $problem->add([
            'name'           => "test add from computer \'_test_pc01\'",
            'content'        => "test add from computer \'_test_pc01\'",
            '_add_from_item' => true,
            '_from_itemtype' => 'Computer',
            '_from_items_id' => $computer->getID(),
        ]);
        $this->assertGreaterThan(0, $problems_id);
        $this->assertTrue($problem->getFromDB($problems_id));

        // check relation
        $problem_item = new \Item_Problem();
        $this->assertTrue($problem_item->getFromDBForItems($problem, $computer));
    }

    public function testAssignFromCategory()
    {
        $this->login('glpi', 'glpi');
        $entity = new \Entity();
        $entityId = $entity->import([
            'name' => 'an entity configured to check problem auto assignation of user ad group',
            'entities_id' => 0,
            'level' => 0,
            'auto_assign_mode' => \Entity::CONFIG_NEVER,
        ]);
        $this->assertFalse($entity->isNewID($entityId));

        $entity->getFromDB($entityId);
        $this->assertEquals(\Entity::CONFIG_NEVER, (int)$entity->fields['auto_assign_mode']);

        // Login again to access the new entity
        $this->login('glpi', 'glpi');
        $success = \Session::changeActiveEntities($entity->getID(), true);
        $this->assertTrue($success);

        $group = new \Group();
        $group->add([
            'name' => 'A group to check automatic tech and group assignation',
            'entities_id' => 0,
            'is_recursive' => '1',
            'level' => 0,
        ]);
        $this->assertFalse($group->isNewItem());

        $itilCategory = new \ITILCategory();
        $itilCategory->add([
            'name' => 'A category to check automatic tech and group assignation',
            'itilcategories_id' => 0,
            'users_id' => 4, // Tech
            'groups_id' => $group->getID(),
        ]);
        $this->assertFalse($itilCategory->isNewItem());

        $problem = new \Problem();
        $problem->add([
            'name' => 'A problem to check if it is not automatically assigned user and group',
            'content' => 'foo',
            'itilcategories_id' => $itilCategory->getID(),
        ]);
        $this->assertFalse($problem->isNewItem());
        $problem->getFromDB($problem->getID());
        $problemUser = new \Problem_User();
        $groupProblem = new \group_Problem();
        $rows = $problemUser->find([
            'problems_id' => $problem->getID(),
            'type'       => \CommonITILActor::ASSIGN,
        ]);
        $this->assertCount(0, $rows);
        $rows = $groupProblem->find([
            'problems_id' => $problem->getID(),
            'type'       => \CommonITILActor::ASSIGN,
        ]);
        $this->assertCount(0, $rows);

        // check Entity::AUTO_ASSIGN_HARDWARE_CATEGORY assignment
        $entity->update([
            'id' => $entity->getID(),
            'auto_assign_mode' => \Entity::AUTO_ASSIGN_HARDWARE_CATEGORY,
        ]);

        $problem = new \Problem();
        $problem->add([
            'name' => 'A problem to check if it is automatically assigned user and group (1)',
            'content' => 'foo',
            'itilcategories_id' => $itilCategory->getID(),
        ]);
        $this->assertFalse($problem->isNewItem());
        $problem->getFromDB($problem->getID());
        $problemUser = new \Problem_User();
        $groupProblem = new \group_Problem();
        $rows = $problemUser->find([
            'problems_id' => $problem->getID(),
            'users_id'    => 4, // tech
            'type'       => \CommonITILActor::ASSIGN,
        ]);
        $this->assertCount(0, $rows);
        $rows = $groupProblem->find([
            'problems_id' => $problem->getID(),
            'groups_id'   => $group->getID(),
            'type'       => \CommonITILActor::ASSIGN,
        ]);
        $this->assertCount(0, $rows);

        // check Entity::AUTO_ASSIGN_CATEGORY_HARDWARE assignment
        $entity->update([
            'id' => $entity->getID(),
            'auto_assign_mode' => \Entity::AUTO_ASSIGN_CATEGORY_HARDWARE,
        ]);

        $problem = new \Problem();
        $problem->add([
            'name' => 'A problem to check if it is automatically assigned user and group (2)',
            'content' => 'foo',
            'itilcategories_id' => $itilCategory->getID(),
        ]);
        $this->assertFalse($problem->isNewItem());
        $problem->getFromDB($problem->getID());
        $problemUser = new \Problem_User();
        $groupProblem = new \group_Problem();
        $rows = $problemUser->find([
            'problems_id' => $problem->getID(),
            'users_id'    => 4, // tech
            'type'       => \CommonITILActor::ASSIGN,
        ]);
        $this->assertCount(0, $rows);
        $rows = $groupProblem->find([
            'problems_id' => $problem->getID(),
            'groups_id'   => $group->getID(),
            'type'       => \CommonITILActor::ASSIGN,
        ]);
        $this->assertCount(0, $rows);
    }

    public function testGetTeamRoles(): void
    {
        $roles = \Problem::getTeamRoles();
        $this->assertContains(\CommonITILActor::ASSIGN, $roles);
        $this->assertContains(\CommonITILActor::OBSERVER, $roles);
        $this->assertContains(\CommonITILActor::REQUESTER, $roles);
    }

    public function testGetTeamRoleName(): void
    {
        $roles = \Problem::getTeamRoles();
        foreach ($roles as $role) {
            $this->assertNotEmpty(\Problem::getTeamRoleName($role));
        }
    }
}
