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
use Glpi\Team\Team;
use Glpi\Toolbox\Sanitizer;
use ProjectState;
use ProjectTask;
use ProjectTeam;
use Search;

/* Test for inc/project.class.php */
class ProjectTest extends DbTestCase
{
    public function testAutocalculatePercentDone()
    {

        $this->login(); // must be logged as ProjectTask uses Session::getLoginUserID()

        $project = new \Project();
        $project_id_1 = $project->add([
            'name' => 'Project 1',
            'auto_percent_done' => 1
        ]);
        $this->assertGreaterThan(0, (int) $project_id_1);
        $project_id_2 = $project->add([
            'name' => 'Project 2',
            'auto_percent_done' => 1,
            'projects_id' => $project_id_1
        ]);
        $this->assertGreaterThan(0, (int) $project_id_2);
        $project_id_3 = $project->add([
            'name' => 'Project 3',
            'projects_id' => $project_id_2
        ]);
        $this->assertGreaterThan(0, (int) $project_id_3);

        $projecttask = new \ProjectTask();
        $projecttask_id_1 = $projecttask->add([
            'name' => 'Project Task 1',
            'auto_percent_done' => 1,
            'projects_id' => $project_id_2,
            'projecttasktemplates_id' => 0
        ]);
        $this->assertGreaterThan(0, (int) $projecttask_id_1);
        $projecttask_id_2 = $projecttask->add([
            'name' => 'Project Task 2',
            'projects_id' => $project_id_2,
            'projecttasks_id' => $projecttask_id_1,
            'projecttasktemplates_id' => 0
        ]);
        $this->assertGreaterThan(0, (int) $projecttask_id_2);

        $project_1 = new \Project();
        $this->assertTrue($project_1->getFromDB($project_id_1));
        $project_2 = new \Project();
        $this->assertTrue($project_2->getFromDB($project_id_2));
        $project_3 = new \Project();
        $this->assertTrue($project_3->getFromDB($project_id_3));
        $this->assertTrue($project_3->update([
            'id'           => $project_id_3,
            'percent_done' => '10'
        ]));

        // Reload projects to get newest values
        $this->assertTrue($project_1->getFromDB($project_id_1));
        $this->assertTrue($project_2->getFromDB($project_id_2));
        // Test parent and parent's parent percent done
        $this->assertEquals(3, $project_2->fields['percent_done']);
        $this->assertEquals(3, $project_1->fields['percent_done']);

        $projecttask_1 = new \ProjectTask();
        $this->assertTrue($projecttask_1->getFromDB($projecttask_id_1));
        $projecttask_2 = new \ProjectTask();
        $this->assertTrue($projecttask_2->getFromDB($projecttask_id_2));

        $this->assertTrue($projecttask_2->update([
            'id'           => $projecttask_id_2,
            'percent_done' => '40'
        ]));

        // Reload projects and tasks to get newest values
        $this->assertTrue($project_1->getFromDB($project_id_1));
        $this->assertTrue($project_2->getFromDB($project_id_2));
        $this->assertTrue($project_3->getFromDB($project_id_3));
        $this->assertTrue($projecttask_1->getFromDB($projecttask_id_1));
        $this->assertEquals(40, $projecttask_1->fields['percent_done']);
        // Check that the child project wasn't changed
        $this->assertEquals(10, $project_3->fields['percent_done']);
        $this->assertEquals(30, $project_2->fields['percent_done']);
        $this->assertEquals(30, $project_1->fields['percent_done']);

        // Test that percent done updates on delete and restore
        $project_3->delete(['id' => $project_id_3]);
        $this->assertTrue($project_2->getFromDB($project_id_2));
        $this->assertEquals(40, $project_2->fields['percent_done']);
        $project_3->restore(['id' => $project_id_3]);
        $this->assertTrue($project_2->getFromDB($project_id_2));
        $this->assertEquals(30, $project_2->fields['percent_done']);
    }

    public function testAutocalculatePercentDoneOnTaskAddAndDelete()
    {
        $this->login(); // must be logged as ProjectTask uses Session::getLoginUserID()

        $project = new \Project();
        $project_id_1 = $project->add([
            'name' => 'Project 1',
            'auto_percent_done' => 1
        ]);
        $this->assertGreaterThan(0, (int) $project_id_1);

        $projecttask = new \ProjectTask();
        $projecttask_id_1 = $projecttask->add([
            'name' => 'Project Task 1',
            'projects_id' => $project_id_1,
            'projecttasktemplates_id' => 0,
            'percent_done'  => 0
        ]);
        $this->assertGreaterThan(0, (int) $projecttask_id_1);

        // Project percent done should be 0 now
        $project->getFromDB($project_id_1);
        $this->assertEquals(0, $project->fields['percent_done']);

        // Add a new task with 100 percent done
        $projecttask_id_2 = $projecttask->add([
            'name' => 'Project Task 2',
            'projects_id' => $project_id_1,
            'projecttasktemplates_id' => 0,
            'percent_done'  => 100
        ]);
        $this->assertGreaterThan(0, $projecttask_id_2);

        // Project percent done should be 50 now
        $project->getFromDB($project_id_1);
        $this->assertEquals(50, $project->fields['percent_done']);

        // Delete the first task and check the project percent done is 100
        $projecttask->delete(['id' => $projecttask_id_1], true);
        $project->getFromDB($project_id_1);
        $this->assertEquals(100, $project->fields['percent_done']);
    }

    public function testCreateFromTemplate()
    {
        $this->login();

        $date = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date;

        $project = new \Project();

        // Create a project template
        $template = $this->createItem(
            'Project',
            [
                'name'         => $this->getUniqueString(),
                'entities_id'  => 0,
                'is_recursive' => 1,
                'is_template'  => 1,
            ]
        );

        $expected_names = [];

        $task_1_name = $this->getUniqueString();
        $this->createItem(
            'ProjectTask',
            [
                'name'         => $task_1_name,
                'projects_id'  => $template->getID(),
                'entities_id'  => 0,
                'is_recursive' => 1,
            ]
        );
        $expected_names[] = $task_1_name;

        // Task with a quote in its name
        $task_2_name = "Task 2 '" . $this->getUniqueString();
        $this->createItem(
            'ProjectTask',
            [
                'name'         => $task_2_name,
                'projects_id'  => $template->getID(),
                'entities_id'  => 0,
                'is_recursive' => 1,
            ]
        );
        $expected_names[] = $task_2_name;

        // Add 1 second to GLPI current time
        $date2 = date('Y-m-d H:i:s', strtotime($date) + 1);
        $_SESSION['glpi_currenttime'] = $date2;

        // Create from template
        $entity_id = getItemByTypeName('Entity', '_test_child_2', true);
        $project = $this->createItem(
            'Project',
            [
                'id'           => $template->getID(),
                'name'         => $this->getUniqueString(),
                'entities_id'  => $entity_id,
                'is_recursive' => 0,
            ]
        );
        $this->assertNotEquals($template->getID(), $project->getID());

        // Check created project
        $this->assertEquals($entity_id, $project->fields['entities_id']);
        $this->assertEquals(0, $project->fields['is_recursive']);

        // Verify that the creation date was not copied from the template
        $this->assertNotEquals($date, $project->fields['date']);
        $this->assertNotEquals($date, $project->fields['date_creation']);
        $this->assertNotEquals($date, $project->fields['date_mod']);

        // Check created tasks
        $tasks_data = getAllDataFromTable(ProjectTask::getTable(), ['projects_id' => $project->getID()]);
        $this->assertCount(2, $tasks_data);

        foreach (array_values($tasks_data) as $index => $task_data) {
            $this->assertEquals($expected_names[$index], $task_data['name']);
            $this->assertEquals($entity_id, $task_data['entities_id']);
            $this->assertEquals(0, $task_data['is_recursive']);
        }
    }

    public function testGetTeamRoles(): void
    {
        $roles = \Project::getTeamRoles();
        $this->assertContains(Team::ROLE_OWNER, $roles);
        $this->assertContains(Team::ROLE_MEMBER, $roles);
    }

    public function testGetTeamRoleName(): void
    {
        $roles = \Project::getTeamRoles();
        foreach ($roles as $role) {
            $this->assertNotEmpty(\Project::getTeamRoleName($role));
        }
    }

    /**
     * Tests addTeamMember, deleteTeamMember, and getTeamMembers methods
     */
    public function testTeamManagement(): void
    {

        $project = new \Project();

        $projects_id = $project->add([
            'name'      => 'Team test',
            'content'   => 'Team test'
        ]);
        $this->assertGreaterThan(0, $projects_id);

        // Check team members array has keys for all team itemtypes
        $team = $project->getTeam();
        $this->assertEmpty($team);

        // Add team members
        $this->assertTrue($project->addTeamMember(\User::class, 4, ['role' => Team::ROLE_MEMBER]));

        // Reload from DB
        $project->getFromDB($projects_id);

        // Check team members
        $team = $project->getTeam();
        $this->assertCount(1, $team);
        $this->assertEquals(\User::class, $team[0]['itemtype']);
        $this->assertEquals(4, $team[0]['items_id']);
        $this->assertEquals(Team::ROLE_MEMBER, $team[0]['role']);

        // Delete team members
        $this->assertTrue($project->deleteTeamMember(\User::class, 4, ['role' => Team::ROLE_MEMBER]));

        //Reload ticket from DB
        $project->getFromDB($projects_id);
        $team = $project->getTeam();

        $this->assertEmpty($team);

        // Add team members
        $this->assertTrue($project->addTeamMember(\Group::class, 5, ['role' => Team::ROLE_MEMBER]));

        // Reload ticket from DB
        $project->getFromDB($projects_id);

        // Check team members
        $team = $project->getTeam();
        $this->assertCount(1, $team);
        $this->assertEquals(\Group::class, $team[0]['itemtype']);
        $this->assertEquals(5, $team[0]['items_id']);
        $this->assertEquals(Team::ROLE_MEMBER, $team[0]['role']);
    }

    public function testClone()
    {
       // Create a basic project
        $project_name = 'Project testClone' . mt_rand();
        $project_input = [
            'name'     => $project_name,
            'priority' => 5,
        ];
        $this->createItems('Project', [$project_input]);
        $projects_id = getItemByTypeName("Project", $project_name, true);

        // Create a user
        $user_name = 'Project testClone - User' . mt_rand();
        $this->createItems('User', [['name' => $user_name]]);
        $users_id = getItemByTypeName("User", $user_name, true);

        // Create a group
        $group_name = 'Project testClone - Group' . mt_rand();
        $this->createItems('Group', [['name' => $group_name]]);
        $groups_id = getItemByTypeName("Group", $group_name, true);

        // Add team to project
        $this->createItems('ProjectTeam', [
            [
                'projects_id' => $projects_id,
                'itemtype'    => 'User',
                'items_id'    => $users_id,
            ],
            [
                'projects_id' => $projects_id,
                'itemtype'    => 'Group',
                'items_id'    => $groups_id,
            ],
        ]);

        // Load current project
        $project = new \Project();
        $this->assertTrue($project->getFromDB($projects_id));

        // Clone project
        $projects_id_clone = $project->clone();
        $this->assertGreaterThan(0, $projects_id_clone);

        // Load clone
        $project_clone = new \Project();
        $this->assertTrue($project_clone->getFromDB($projects_id_clone));

        // Check name
        $this->assertEquals("$project_input[name] (copy)", $project_clone->fields['name']);
        unset($project_clone->fields['name'], $project_input['name']);

        // Check basics fields
        foreach (array_keys($project_input) as $field) {
            $this->assertEquals($project->fields[$field], $project_clone->fields[$field]);
        }

        // Load project team
        $project_team = new ProjectTeam();
        $team = [];
        foreach ($project_team->find(['projects_id' => $projects_id]) as $row) {
            $team[] = [
                'itemtype' => $row['itemtype'],
                'items_id' => $row['items_id'],
            ];
        }

        // Load clone team
        $team_clone = [];
        foreach ($project_team->find(['projects_id' => $projects_id_clone]) as $row) {
            $team_clone[] = [
                'itemtype' => $row['itemtype'],
                'items_id' => $row['items_id'],
            ];
        }

        // Compare teams
        $this->assertEquals($team, $team_clone);

        // Add a task to project
        $task1_name = 'Project testClone - Task' . mt_rand();
        $task1 = $this->createItem('ProjectTask', [
            'projects_id' => $projects_id,
            'name'        => $task1_name,
        ]);
        $task1_id = $task1->fields['id'];

        // Add a task, child of the previous task
        $task2_name = 'Project testClone - Task' . mt_rand();
        $task2 = $this->createItem('ProjectTask', [
            'projects_id'     => $projects_id,
            'name'            => $task2_name,
            'projecttasks_id' => $task1_id,
        ]);
        $task2_id = $task2->fields['id'];

        // Clone project
        $projects_id_clone = $project->clone();
        $this->assertGreaterThan(0, $projects_id_clone);

        // Load clone
        $project_clone = new \Project();
        $this->assertTrue($project_clone->getFromDB($projects_id_clone));

        // Load clone tasks
        $project_task = new ProjectTask();
        $tasks_clone = [];
        foreach ($project_task->find(['projects_id' => $projects_id_clone]) as $row) {
            $tasks_clone[] = [
                'projecttasks_id' => $row['projecttasks_id'],
            ];
        }

        $expected = [
            [
                'projecttasks_id' => 0,
            ],
            [
                'projecttasks_id' => $task1_id + 2,
            ],
        ];

        // Compare tasks
        $this->assertEquals($expected, $tasks_clone);
    }

    public function testCloneWithOverridenInput()
    {
        $project = $this->createItem(
            'Project',
            [
                'name' => __FUNCTION__,
            ]
        );

        $raw_description = <<<PLAINTEXT
            > a
            > multiline
            > description
PLAINTEXT;

        $sanitized_description = <<<PLAINTEXT
            &#62; a
            &#62; multiline
            &#62; description
PLAINTEXT;

        // Clone with raw input
        $projects_id_clone = $project->clone(['content' => $raw_description]);
        $project_clone = new \Project();
        $this->assertTrue($project_clone->getFromDB($projects_id_clone));
        $this->assertEquals($sanitized_description, $project_clone->fields['content']);

        // Clone with already sanitized input
        $projects_id_clone = $project->clone(Sanitizer::sanitize(['content' => $raw_description]));
        $project_clone = new \Project();
        $this->assertTrue($project_clone->getFromDB($projects_id_clone));
        $this->assertEquals($sanitized_description, $project_clone->fields['content']);
    }

    /**
     * Functional test to ensure that project's states colors are shown in
     * the search results
     */
    public function testProjectStateColorInSearchResults(): void
    {
        $this->login();
        $entity = getItemByTypeName("Entity", "_test_root_entity", true);

        // Create some unique state colors
        list(
            $state1,
            $state2,
            $state3
        ) = $this->createItems(ProjectState::getType(), [
            ['name' => 'state1', 'color' => '#000001'],
            ['name' => 'state2', 'color' => '#000002'],
            ['name' => 'state3', 'color' => '#000003'],
        ]);

        // Create projects using these states
        $this->createItems(\Project::getType(), [
            ['name' => 'project1a', 'projectstates_id' => $state1->getID(), 'entities_id' => $entity],
            ['name' => 'project2a', 'projectstates_id' => $state2->getID(), 'entities_id' => $entity],
            ['name' => 'project2b', 'projectstates_id' => $state2->getID(), 'entities_id' => $entity],
            ['name' => 'project3a', 'projectstates_id' => $state3->getID(), 'entities_id' => $entity],
            ['name' => 'project3b', 'projectstates_id' => $state3->getID(), 'entities_id' => $entity],
            ['name' => 'project3c', 'projectstates_id' => $state3->getID(), 'entities_id' => $entity],
        ]);

        // Execute search
        $params = [
            'display_type' => Search::HTML_OUTPUT,
            'criteria'     => [],
            'item_type'    => \Project::getType(),
            'is_deleted'   => 0,
            'as_map'       => 0,
            'forcedisplay' => [/* State */ 12],
        ];
        ob_start();
        Search::showList($params['item_type'], $params);
        $html = ob_get_contents();
        ob_end_clean();

        // Check results
        $this->assertEquals(1, substr_count($html, "background-color: #000001;"));
        $this->assertEquals(2, substr_count($html, "background-color: #000002;"));
        $this->assertEquals(3, substr_count($html, "background-color: #000003;"));
    }
}
