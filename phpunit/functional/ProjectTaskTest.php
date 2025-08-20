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
use Glpi\Team\Team;
use Project;

/* Test for inc/projecttask.class.php */

class ProjectTaskTest extends DbTestCase
{
    public function testPlanningConflict()
    {
        $this->login();

        $user = getItemByTypeName('User', 'tech');
        $users_id = (int) $user->fields['id'];

        $ptask = new \ProjectTask();
        $this->assertSame(
            0,
            (int) $ptask->add([
                'name'   => 'test',
            ])
        );

        $this->hasSessionMessages(ERROR, ['A linked project is mandatory']);

        $project = new Project();
        $pid = (int) $project->add([
            'name'   => 'Test project',
        ]);
        $this->assertGreaterThan(0, $pid);

        $this->assertGreaterThan(
            0,
            (int) $ptask->add([
                'name'                     => 'first test, whole period',
                'projects_id'              => $pid,
                'plan_start_date'          => '2019-08-10',
                'plan_end_date'            => '2019-08-20',
                'projecttasktemplates_id'  => 0,
            ])
        );
        $this->hasNoSessionMessages([ERROR, WARNING]);
        $task_id = $ptask->fields['id'];

        $team = new \ProjectTaskTeam();
        $tid = (int) $team->add([
            'projecttasks_id' => $ptask->fields['id'],
            'itemtype'        => \User::getType(),
            'items_id'        => $users_id,
        ]);
        $this->hasNoSessionMessages([ERROR, WARNING]);
        $this->assertGreaterThan(0, $tid);

        $this->assertGreaterThan(
            0,
            (int) $ptask->add([
                'name'                     => 'test, subperiod',
                'projects_id'              => $pid,
                'plan_start_date'          => '2019-08-13',
                'plan_end_date'            => '2019-08-14',
                'projecttasktemplates_id'  => 0,
            ])
        );
        $this->hasNoSessionMessages([ERROR, WARNING]);

        $team = new \ProjectTaskTeam();
        $tid = (int) $team->add([
            'projecttasks_id' => $ptask->fields['id'],
            'itemtype'        => \User::getType(),
            'items_id'        => $users_id,
        ]);

        $usr_str = '<a href="' . $user->getFormURLWithID($users_id) . '">' . $user->getName() . '</a>';
        $this->hasSessionMessages(
            WARNING,
            [
                "The user $usr_str is busy at the selected timeframe.<br/>- Project task: from 2019-08-13 00:00 to 2019-08-14 00:00:<br/><a href='"
            . $ptask->getFormURLWithID($task_id) . "'>first test, whole period</a><br/>",
            ]
        );
        $this->assertGreaterThan(0, $tid);

        //check when updating. first create a new task out of existing bouds
        $this->assertGreaterThan(
            0,
            (int) $ptask->add([
                'name'                     => 'test subperiod, out of bounds',
                'projects_id'              => $pid,
                'plan_start_date'          => '2018-08-13',
                'plan_end_date'            => '2018-08-24',
                'projecttasktemplates_id'  => 0,
            ])
        );
        $this->hasNoSessionMessages([ERROR, WARNING]);

        $team = new \ProjectTaskTeam();
        $tid = (int) $team->add([
            'projecttasks_id' => $ptask->fields['id'],
            'itemtype'        => \User::getType(),
            'items_id'        => $users_id,
        ]);
        $this->hasNoSessionMessages([ERROR, WARNING]);
        $this->assertGreaterThan(0, $tid);

        $this->assertTrue(
            $ptask->update([
                'id'                       => $ptask->fields['id'],
                'name'                     => 'test subperiod, no longer out of bounds',
                'projects_id'              => $pid,
                'plan_start_date'          => '2019-08-13',
                'plan_end_date'            => '2019-08-24',
                'projecttasktemplates_id'  => 0,
            ])
        );

        $this->assertArrayHasKey(WARNING, $_SESSION['MESSAGE_AFTER_REDIRECT']);
        $_SESSION['MESSAGE_AFTER_REDIRECT'] = []; //reset

        //create reference ticket
        $ticket = new \Ticket();
        $this->assertGreaterThan(
            0,
            (int) $ticket->add([
                'name'               => 'ticket title',
                'description'        => 'a description',
                'content'            => '',
                'entities_id'        => getItemByTypeName('Entity', '_test_root_entity', true),
                '_users_id_assign'   => getItemByTypeName('User', 'tech', true),
            ])
        );

        $this->assertFalse($ticket->isNewItem());
        $tid = (int) $ticket->fields['id'];

        $ttask = new \TicketTask();
        $ttask_id = (int) $ttask->add([
            'name'               => 'A ticket task in bounds',
            'content'            => 'A ticket task in bounds',
            'tickets_id'         => $tid,
            'plan'               => [
                'begin'  => '2019-08-11',
                'end'    => '2019-08-12',
            ],
            'users_id_tech'      => $users_id,
            'tasktemplates_id'   => 0,
        ]);
        $usr_str = '<a href="' . $user->getFormURLWithID($users_id) . '">' . $user->getName() . '</a>';

        $this->hasSessionMessages(
            WARNING,
            [
                "The user $usr_str is busy at the selected timeframe.<br/>- Project task: from 2019-08-11 00:00 to 2019-08-12 00:00:<br/><a href='"
            . $ptask->getFormURLWithID($task_id) . "'>first test, whole period</a><br/>",
            ]
        );
        $this->assertGreaterThan(0, $ttask_id);
    }

    public function testGetTeamRoles(): void
    {
        $roles = \ProjectTask::getTeamRoles();
        $this->assertContains(Team::ROLE_OWNER, $roles);
        $this->assertContains(Team::ROLE_MEMBER, $roles);
    }

    public function testGetTeamRoleName(): void
    {
        $roles = \ProjectTask::getTeamRoles();
        foreach ($roles as $role) {
            $this->assertNotEmpty(\ProjectTask::getTeamRoleName($role));
        }
    }

    /**
     * Tests addTeamMember, deleteTeamMember, and getTeamMembers methods
     */
    public function testTeamManagement(): void
    {
        $this->login();
        $project_task = new \ProjectTask();

        $project = new Project();
        $projects_id = $project->add([
            'name'      => 'Team test',
            'content'   => 'Team test',
        ]);

        $projecttasks_id = $project_task->add([
            'projects_id'  => $projects_id,
            'name'         => 'Team test',
            'content'      => 'Team test',
        ]);
        $this->assertGreaterThan(0, $projecttasks_id);

        // Check team members array has keys for all team itemtypes
        $team = $project_task->getTeam();
        $this->assertEmpty($team);

        // Add team members
        $this->assertTrue($project_task->addTeamMember(\User::class, 4, ['role' => Team::ROLE_MEMBER]));

        // Reload ticket from DB
        $project_task->getFromDB($projecttasks_id);

        // Check team members
        $team = $project_task->getTeam();
        $this->assertCount(1, $team);
        $this->assertEquals(\User::class, $team[0]['itemtype']);
        $this->assertEquals(4, $team[0]['items_id']);
        $this->assertEquals(Team::ROLE_MEMBER, $team[0]['role']);

        // Delete team members
        $this->assertTrue($project_task->deleteTeamMember(\User::class, 4, ['role' => Team::ROLE_MEMBER]));

        //Reload ticket from DB
        $project_task->getFromDB($projecttasks_id);
        $team = $project_task->getTeam();

        $this->assertEmpty($team);

        // Add team members
        $this->assertTrue($project_task->addTeamMember(\Group::class, 5, ['role' => Team::ROLE_MEMBER]));

        // Reload ticket from DB
        $project_task->getFromDB($projecttasks_id);

        // Check team members
        $team = $project_task->getTeam();
        $this->assertCount(1, $team);
        $this->assertEquals(\Group::class, $team[0]['itemtype']);
        $this->assertEquals(5, $team[0]['items_id']);
        $this->assertEquals(Team::ROLE_MEMBER, $team[0]['role']);
    }

    public function testTaskMustHaveLinkedProject()
    {
        // Create a project
        $project = $this->createItem('Project', [
            'name' => 'Project 1',
        ]);

        // Create a task
        $task = $this->createItem('ProjectTask', [
            'name' => 'Task 1',
            'projects_id' => $project->getID(),
        ]);

        // Update the task with a projects_id at 0
        $this->updateItem('ProjectTask', $task->getID(), [
            'projects_id' => 0,
        ], ['projects_id']);

        // Reload task from DB
        $task->getFromDB($task->getID());

        // Check that the task is still linked to the project
        $this->assertEquals($project->getID(), $task->fields['projects_id']);

        // Check if session has an error message
        $this->hasSessionMessages(ERROR, ['A linked project is mandatory']);
    }

    public function testMoveTaskToAnotherProject()
    {
        // Create a project
        $project1 = $this->createItem('Project', [
            'name' => 'Project 1',
        ]);

        // Create a project task
        $task = $this->createItem('ProjectTask', [
            'projects_id' => $project1->getID(),
            'name'        => 'Task 1',
        ]);

        // Create a subtask
        $subtask = $this->createItem('ProjectTask', [
            'projects_id'     => $project1->getID(),
            'projecttasks_id' => $task->getID(),
            'name'            => 'Subtask 1',
        ]);

        // Create a subtask of the subtask
        $subtask2 = $this->createItem('ProjectTask', [
            'projects_id'     => $project1->getID(),
            'projecttasks_id' => $subtask->getID(),
            'name'            => 'Subtask 2',
        ]);

        // Create another project
        $project2 = $this->createItem('Project', [
            'name' => 'Project 2',
        ]);

        // Move the task to another project
        $this->updateItem('ProjectTask', $task->getID(), [
            'projects_id' => $project2->getID(),
        ]);

        // Reload all items from DB
        $task->getFromDB($task->getID());
        $subtask->getFromDB($subtask->getID());
        $subtask2->getFromDB($subtask2->getID());

        // Check all tasks have been moved
        $this->assertEquals($project2->getID(), $task->fields['projects_id']);
        $this->assertEquals($project2->getID(), $subtask->fields['projects_id']);
        $this->assertEquals($project2->getID(), $subtask2->fields['projects_id']);
    }

    public function testCloneProjectTask()
    {
        // Create a project
        $project = $this->createItem('Project', [
            'name' => 'Project 1',
        ]);

        // Create a project task
        $task = $this->createItem('ProjectTask', [
            'projects_id' => $project->getID(),
            'name'        => 'Task 1',
        ]);

        // Create a subtask
        $subtask = $this->createItem('ProjectTask', [
            'projects_id'     => $project->getID(),
            'projecttasks_id' => $task->getID(),
            'name'            => 'Subtask 1',
        ]);

        // Create a subtask of the subtask
        $subtask2 = $this->createItem('ProjectTask', [
            'projects_id'     => $project->getID(),
            'projecttasks_id' => $subtask->getID(),
            'name'            => 'Subtask 2',
        ]);

        // Create a user
        $user_name = 'Project testClone - User' . random_int(0, 99999);
        $this->createItems('User', [['name' => $user_name]]);
        $users_id = getItemByTypeName("User", $user_name, true);

        // Create a group
        $group_name = 'Project testClone - Group' . random_int(0, 99999);
        $this->createItems('Group', [['name' => $group_name]]);
        $groups_id = getItemByTypeName("Group", $group_name, true);

        // Add team to project
        $this->createItems('ProjectTaskTeam', [
            [
                'projecttasks_id' => $task->fields['id'],
                'itemtype'    => 'User',
                'items_id'    => $users_id,
            ],
            [
                'projecttasks_id' => $task->fields['id'],
                'itemtype'    => 'Group',
                'items_id'    => $groups_id,
            ],
        ]);

        // Clone the task
        $clonedTaskId = $task->clone();
        $clonedTask = \ProjectTask::getById($clonedTaskId);

        // Check if the cloned task is in the same project with the same name
        $this->assertEquals($project->getID(), $clonedTask->fields['projects_id']);
        $this->assertEquals($task->fields['name'] . ' (copy)', $clonedTask->fields['name']);

        // Load task team
        $project_task_team = new \ProjectTaskTeam();
        $team = [];
        foreach ($project_task_team->find(['projecttasks_id' => $task->fields['id']]) as $row) {
            $team[] = [
                'itemtype' => $row['itemtype'],
                'items_id' => $row['items_id'],
            ];
        }

        // Load clone team
        $team_clone = [];
        foreach ($project_task_team->find(['projecttasks_id' => $clonedTaskId]) as $row) {
            $team_clone[] = [
                'itemtype' => $row['itemtype'],
                'items_id' => $row['items_id'],
            ];
        }

        // Compare teams
        $this->assertEquals($team, $team_clone);

        // Check if the subtask has been cloned
        $clonedSubtask = new \ProjectTask();
        $clonedSubtask->getFromDBByCrit([
            'projects_id'     => $project->getID(),
            'projecttasks_id' => $clonedTaskId,
        ]);

        $this->assertGreaterThan(0, $clonedSubtask->getID());
        $this->assertEquals($project->getID(), $clonedSubtask->fields['projects_id']);
        $this->assertEquals($subtask->fields['name'] . ' (copy)', $clonedSubtask->fields['name']);

        // Check if the subtask of the subtask has been cloned
        $clonedSubtask2 = new \ProjectTask();
        $clonedSubtask2->getFromDBByCrit([
            'projects_id'     => $project->getID(),
            'projecttasks_id' => $clonedSubtask->getID(),
        ]);

        $this->assertGreaterThan(0, $clonedSubtask2->getID());
        $this->assertEquals($project->getID(), $clonedSubtask2->fields['projects_id']);
        $this->assertEquals($subtask2->fields['name'] . ' (copy)', $clonedSubtask2->fields['name']);
    }
    protected function testAutochangeStateProvider()
    {
        $config = new \Config();
        $config->getFromDBByCrit(['name' => 'projecttask_unstarted_states_id']);
        $this->updateItem('Config', $config->getID(), ['value' => '1']  + $config->fields);

        $config->getFromDBByCrit(['name' => 'projecttask_inprogress_states_id']);
        $this->updateItem('Config', $config->getID(), ['value' => '2']  + $config->fields);

        $config->getFromDBByCrit(['name' => 'projecttask_completed_states_id']);
        $this->updateItem('Config', $config->getID(), ['value' => '3']  + $config->fields);

        $this->login(); // must be logged as ProjectTask uses Session::getLoginUserID()

        $project = $this->createItem(
            'Project',
            [
                'name' => 'Project 1',
            ]
        );
        $task = $this->createItem(
            'ProjectTask',
            [
                'name' => 'Project Task 1',
                'auto_projectstates' => 1,
                'projects_id' => $project->getID(),
                'percent_done'  => 0,
            ]
        );

        yield [
            'input' => [ // Task with percent_done == 0
                'id'            => $task->getID(),
                'percent_done'  => '0',
            ],
            'result' => [
                'percent_done' => '0',
                'projectstates_id' => 1,
            ],
        ];
        yield [
            'input' => [ // Task with percent_done == 50
                'id'            => $task->getID(),
                'percent_done'  => '50',
            ],
            'result' => [
                'percent_done' => '50',
                'projectstates_id' => 2,
            ],
        ];
        yield [
            'input' => [ // Task with percent_done == 100
                'id'            => $task->getID(),
                'percent_done'  => '100',
            ],
            'result' => [
                'percent_done' => '100',
                'projectstates_id' => 3,
            ],
        ];
        yield [
            'input' => [ // Task with percent_done == 25
                'id'            => $task->getID(),
                'percent_done'  => '25',
            ],
            'result' => [
                'percent_done' => '25',
                'projectstates_id' => 2,
            ],
        ];
        yield [
            'input' => [ // Task with percent_done < 0
                'id'            => $task->getID(),
                'percent_done'  => '-1',
            ],
            'result' => [
                'percent_done' => '-1',
                'projectstates_id' => 1,
            ],
        ];
    }

    public function testAutochangeState()
    {
        $provider = $this->testAutochangeStateProvider();
        foreach ($provider as $row) {
            $input = $row['input'];
            $result = $row['result'];

            $projecttask = $this->updateItem('ProjectTask', $input['id'], $input);
            $this->assertEquals($result['percent_done'], $projecttask->fields['percent_done']);
            $this->assertEquals($result['projectstates_id'], $projecttask->fields['projectstates_id']);
        }
    }

    protected function testAutoSetDateForAddProvider()
    {
        $_SESSION['glpi_currenttime'] = '2023-10-10 10:10:10';
        $project = $this->createItem('Project', [
            'name' => 'Project 1',
        ]);

        yield [
            /**
             * The real_start_date can be an empty string value because the form
             * may have already been saved without a defined start time.
             *
             * Setting an empty value for the real_start_date field allows
             * to reproduce the behavior of the interface.
             */
            'input' => [ // Task with percent_done < 100 and > 0
                'projects_id'  => $project->getID(),
                'name'         => 'Task 1',
                'percent_done' => '5',
                'real_start_date' => '',
            ],
            'result' => [
                'real_start_date' => \Session::getCurrentTime(),
                'real_end_date'   => null,
            ],
        ];
        yield [
            'input' => [ // Task with percent_done < 100 and > 0
                'projects_id'  => $project->getID(),
                'name'         => 'Task 1',
                'percent_done' => '5',
            ],
            'result' => [
                'real_start_date' => \Session::getCurrentTime(),
                'real_end_date'   => null,
            ],
        ];
        yield [
            'input' => [ // Task with percent_done = 100
                'projects_id'  => $project->getID(),
                'name'         => 'Task 2',
                'percent_done' => '100',
            ],
            'result' => [
                'real_start_date' => \Session::getCurrentTime(),
                'real_end_date'   => \Session::getCurrentTime(),
            ],
        ];
        $tasks = [
            [
                'name'         => 'Task 3',
                'percent_done' => '0',
            ],
            [
                'name' => 'Task 4',
            ],
            [
                'name'         => 'Task 5',
                'percent_done' => null,
            ],
            [
                'name'            => 'Task 7',
                'real_start_date' => null,
                'real_end_date'   => null,
            ],
        ];
        foreach ($tasks as $task) {
            $task['projects_id'] = $project->getID();
            yield [
                'input' => $task,
                'result' => [
                    'real_start_date' => null,
                    'real_end_date'   => null,
                ],
            ];
        }
        yield [
            'input' => [ // Task with real_start_date and no real_end_date
                'projects_id'     => $project->getID(),
                'name'            => 'Task 6',
                'percent_done'    => '0',
                'real_start_date' => '2024-10-10 10:10:10',
                'real_end_date'   => null,
            ],
            'result' => [
                'real_start_date' => '2024-10-10 10:10:10',
                'real_end_date'   => null,
            ],
        ];
        yield [
            'input' => [ // Task with empty real_start_date and real_end_date
                'projects_id'     => $project->getID(),
                'name'            => 'Task 6',
                'real_start_date' => '',
                'real_end_date'   => '',
            ],
            'result' => [
                'real_start_date' => null,
                'real_end_date'   => null,
            ],
        ];
        yield [
            'input' => [ // Task with percent_done = 100 and real_start_date and real_end_date
                'projects_id'     => $project->getID(),
                'name'            => 'Task 8',
                'percent_done'    => '100',
                'real_start_date' => '2023-05-12 11:54:23',
                'real_end_date'   => '2023-09-05 16:21:46',
            ],
            'result' => [
                'real_start_date' => '2023-05-12 11:54:23',
                'real_end_date'   => '2023-09-05 16:21:46',
            ],
        ];
    }

    public function testAutoSetDateForAdd()
    {
        $provider = $this->testAutoSetDateForAddProvider();
        foreach ($provider as $row) {
            $input = $row['input'];
            $result = $row['result'];

            // Some logic can change the fields values, so we need to skip them
            // if the values are different between the input and the result
            $skip_fields = array_filter(
                array_intersect(array_keys($input), array_keys($result)),
                fn($key) => $input[$key] !== $result[$key],
            );

            // Create a project task with percent_done < 100 and > 0
            $task = $this->createItem('ProjectTask', $input, $skip_fields);

            // Check if the task has been added with the current date
            $this->assertEquals($result['real_start_date'], $task->fields['real_start_date']);
            $this->assertEquals($result['real_end_date'], $task->fields['real_end_date']);
        }
    }

    protected function testAutoSetDateForUpdateProvider()
    {
        // no failure if `percent_done` is null
        yield [
            'fields' => [
                'percent_done'    => 0,
            ],
            'input' => [
                'percent_done'    => null,
            ],
            'result' => [
                'percent_done'    => 0,
                'real_start_date' => null,
                'real_end_date'   => null,
            ],
        ];

        // `real_start_date` is automatically set reset when `percent_done` is set to something else than 0%
        foreach ([50, 75, 100] as $percent_done) {
            yield [
                'fields' => [
                    'percent_done'    => 0,
                ],
                'input' => [
                    'percent_done'    => $percent_done,
                ],
                'result' => [
                    'percent_done'    => $percent_done,
                    'real_start_date' => \Session::getCurrentTime(),
                ],
            ];
        }

        // `real_end_date` is not to current time if already set when `percent_done` is set to 100%
        yield [
            'fields' => [
                'percent_done'    => 0,
                'real_start_date' => '2023-12-12 04:56:35',
                'real_end_date'   => '2023-12-22 12:23:35',
            ],
            'input' => [
                'percent_done'    => 100,
            ],
            'result' => [
                'percent_done'    => 100,
                'real_start_date' => '2023-12-12 04:56:35',
                'real_end_date'   => '2023-12-22 12:23:35',
            ],
        ];

        // `real_start_date` is not reset when `percent_done` is set to 100%
        // `real_end_date` is set to current time if not already set
        $initial_fields_sets = [
            [
                'percent_done'    => 0,
                'real_start_date' => '2023-12-12 04:56:35',
            ],
            [
                'percent_done'    => 30,
                'real_start_date' => '2023-12-12 04:56:35',
            ],
            [
                'percent_done'    => 100,
                'real_start_date' => '2023-12-12 04:56:35',
            ],
        ];
        foreach ($initial_fields_sets as $fields) {
            yield [
                'fields' => $fields,
                'input'  => [
                    'percent_done'    => 100,
                ],
                'result' => [
                    'percent_done'    => 100,
                    'real_start_date' => '2023-12-12 04:56:35',
                    'real_end_date'   => \Session::getCurrentTime(),
                ],
            ];
        }

        // `real_start_date` is not reset when `percent_done` is set below 100%
        // but `real_end_date` is
        $initial_fields_sets = [
            [
                'percent_done'    => 0,
                'real_start_date' => '2023-12-12 04:56:35',
                'real_end_date'   => '2023-12-22 12:23:35',
            ],
            [
                'percent_done'    => 30,
                'real_start_date' => '2023-12-12 04:56:35',
                'real_end_date'   => '2023-12-22 12:23:35',
            ],
            [
                'percent_done'    => 100,
                'real_start_date' => '2023-12-12 04:56:35',
                'real_end_date'   => '2023-12-22 12:23:35',
            ],
        ];
        foreach ($initial_fields_sets as $fields) {
            yield [
                'fields' => $fields,
                'input' => [
                    'percent_done'    => 50,
                ],
                'result' => [
                    'percent_done'    => 50,
                    'real_start_date' => '2023-12-12 04:56:35',
                    'real_end_date'   => null,
                ],
            ];
        }
    }

    public function testAutoSetDateForUpdate()
    {
        global $DB;

        $provider = $this->testAutoSetDateForUpdateProvider();
        foreach ($provider as $row) {
            $fields = $row['fields'];
            $input = $row['input'];
            $result = $row['result'];

            // Create a project
            $project = $this->createItem(Project::class, [
                'name' => 'Project 1',
            ]);

            $task = $this->createItem(\ProjectTask::class, ['projects_id' => $project->getId()]);

            // Force initial fields (bypass prepareInputForAdd)
            $DB->update(\ProjectTask::getTable(), $fields, ['id' => $task->getID()]);

            $this->updateItem(\ProjectTask::class, $task->getID(), $input);

            $ptask = new \ProjectTask();
            $this->assertTrue($ptask->getFromDB($task->getID()));

            foreach ($result as $field => $value) {
                $this->assertEquals($value, $ptask->fields[$field]);
            }
        }
    }

    public function testGetActiveProjectTaskIDsForUser()
    {
        $this->login();
        $entity = getItemByTypeName("Entity", "_test_root_entity", true);

        // Create user
        $user = $this->createItem(\User::getType(), ['name' => __FUNCTION__ . 'user']);

        // Check if a user with no project returns an empty array
        $this->assertEmpty(\ProjectTask::getActiveProjectTaskIDsForUser([$user->getID()]));

        // Create project
        $project = $this->createItem(Project::getType(), [
            'name'         => 'project',
            'entities_id'  => $entity,
        ]);

        // Check if a user with a project with no tasks returns an empty array
        $this->assertEmpty(\ProjectTask::getActiveProjectTaskIDsForUser([$user->getID()]));

        // Create project task
        $project_task = $this->createItem(\ProjectTask::getType(), [
            'projects_id' => $project->getID(),
            'name'        => 'project task',
        ]);

        // Check if a user with a project with tasks, where the user is not a member of the team, returns an empty array
        $this->assertEmpty(\ProjectTask::getActiveProjectTaskIDsForUser([$user->getID()]));

        // Create user team
        $user_team = $this->createItem(\ProjectTaskTeam::getType(), [
            'projecttasks_id' => $project_task->getID(),
            'itemtype'        => \User::class,
            'items_id'        => $user->getID(),
        ]);

        // Check if a user with a project with tasks, where the user is a member of the team, returns an array with the task ID
        $this->assertEquals(
            [['id' => $project_task->getID()]],
            \ProjectTask::getActiveProjectTaskIDsForUser([$user->getID()])
        );

        // Create group
        $group = $this->createItem(\Group::getType(), ['name' => __FUNCTION__ . 'group']);

        // Add user to group
        $this->createItem(\Group_User::getType(), ['groups_id' => $group->getID(), 'users_id' => $user->getID()]);

        // Create group team
        $this->createItem(\ProjectTaskTeam::getType(), [
            'projecttasks_id' => $project_task->getID(),
            'itemtype'        => \Group::class,
            'items_id'        => $group->getID(),
        ]);

        // Remove user team
        $this->deleteItem(\ProjectTaskTeam::getType(), $user_team->getID());

        // Check if a user with a project with tasks, where the user is a member of the group and the group is a member of the team, returns an array with the task ID if $search_in_groups is true
        $this->assertEquals(
            [['id' => $project_task->getID()]],
            \ProjectTask::getActiveProjectTaskIDsForUser([$user->getID()])
        );

        // Check if a user with a project with tasks, where the user is a member of the group and the group is a member of the team, returns an empty array if $search_in_groups is false
        $this->assertEmpty(\ProjectTask::getActiveProjectTaskIDsForUser([$user->getID()], false));

        // Templates should be excluded
        $project = $this->updateItem(
            Project::getType(),
            $project->getID(),
            ['is_template' => true]
        );
        $this->assertEmpty(\ProjectTask::getActiveProjectTaskIDsForUser([$user->getID()]));
    }

    public function testGetActiveProjectTaskIDsForGroup(): void
    {
        $this->login();
        $entity = getItemByTypeName("Entity", "_test_root_entity", true);

        // Create group
        $group = $this->createItem(\Group::getType(), ['name' => __FUNCTION__ . 'group']);

        // Check if a group with no project returns an empty array
        $this->assertEmpty(\ProjectTask::getActiveProjectTaskIDsForGroup([$group->getID()]));

        // Create project
        $project = $this->createItem(Project::getType(), [
            'name'         => 'project',
            'entities_id'  => $entity,
            'groups_id'    => $group->getID(),
        ]);

        // Check if a group with a project with no tasks returns an empty array
        $this->assertEmpty(\ProjectTask::getActiveProjectTaskIDsForGroup([$group->getID()]));

        // Create project task
        $project_task = $this->createItem(\ProjectTask::getType(), [
            'projects_id' => $project->getID(),
            'name'        => 'project task',
        ]);

        // Check if a group with a project with tasks, where the group is not a member of the team, returns an empty array
        $this->assertEmpty(\ProjectTask::getActiveProjectTaskIDsForGroup([$group->getID()]));

        // Create group team
        $this->createItem(\ProjectTaskTeam::getType(), [
            'projecttasks_id' => $project_task->getID(),
            'itemtype'        => \Group::class,
            'items_id'        => $group->getID(),
        ]);

        // Check if a group with a project with tasks, where the group is a member of the team, returns an array with the task ID
        $this->assertEquals(
            [['id' => $project_task->getID()]],
            \ProjectTask::getActiveProjectTaskIDsForGroup([$group->getID()])
        );

        // Templates should be excluded
        $project = $this->updateItem(
            Project::getType(),
            $project->getID(),
            ['is_template' => true]
        );
        $this->assertEmpty(\ProjectTask::getActiveProjectTaskIDsForGroup([$group->getID()]));
    }
}
