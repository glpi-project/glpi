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

/* Test for inc/projecttask.class.php */

class ProjectTask extends DbTestCase
{
    public function testPlanningConflict()
    {
        $this->login();

        $user = getItemByTypeName('User', 'tech');
        $users_id = (int)$user->fields['id'];

        $ptask = new \ProjectTask();
        $this->integer(
            (int)$ptask->add([
                'name'   => 'test'
            ])
        )->isIdenticalTo(0);

        $this->hasSessionMessages(ERROR, ['A linked project is mandatory']);

        $project = new \Project();
        $pid = (int)$project->add([
            'name'   => 'Test project'
        ]);
        $this->integer($pid)->isGreaterThan(0);

        $this->integer(
            (int)$ptask->add([
                'name'                     => 'first test, whole period',
                'projects_id'              => $pid,
                'plan_start_date'          => '2019-08-10',
                'plan_end_date'            => '2019-08-20',
                'projecttasktemplates_id'  => 0
            ])
        )->isGreaterThan(0);
        $this->hasNoSessionMessages([ERROR, WARNING]);
        $task_id = $ptask->fields['id'];

        $team = new \ProjectTaskTeam();
        $tid = (int)$team->add([
            'projecttasks_id' => $ptask->fields['id'],
            'itemtype'        => \User::getType(),
            'items_id'        => $users_id
        ]);
        $this->hasNoSessionMessages([ERROR, WARNING]);
        $this->integer($tid)->isGreaterThan(0);

        $this->integer(
            (int)$ptask->add([
                'name'                     => 'test, subperiod',
                'projects_id'              => $pid,
                'plan_start_date'          => '2019-08-13',
                'plan_end_date'            => '2019-08-14',
                'projecttasktemplates_id'  => 0
            ])
        )->isGreaterThan(0);
        $this->hasNoSessionMessages([ERROR, WARNING]);

        $team = new \ProjectTaskTeam();
        $tid = (int)$team->add([
            'projecttasks_id' => $ptask->fields['id'],
            'itemtype'        => \User::getType(),
            'items_id'        => $users_id
        ]);

        $usr_str = '<a href="' . $user->getFormURLWithID($users_id) . '">' . $user->getName() . '</a>';
        $this->hasSessionMessages(
            WARNING,
            [
                "The user $usr_str is busy at the selected timeframe.<br/>- Project task: from 2019-08-13 00:00 to 2019-08-14 00:00:<br/><a href='" .
            $ptask->getFormURLWithID($task_id) . "'>first test, whole period</a><br/>"
            ]
        );
        $this->integer($tid)->isGreaterThan(0);

       //check when updating. first create a new task out of existing bouds
        $this->integer(
            (int)$ptask->add([
                'name'                     => 'test subperiod, out of bounds',
                'projects_id'              => $pid,
                'plan_start_date'          => '2018-08-13',
                'plan_end_date'            => '2018-08-24',
                'projecttasktemplates_id'  => 0
            ])
        )->isGreaterThan(0);
        $this->hasNoSessionMessages([ERROR, WARNING]);

        $team = new \ProjectTaskTeam();
        $tid = (int)$team->add([
            'projecttasks_id' => $ptask->fields['id'],
            'itemtype'        => \User::getType(),
            'items_id'        => $users_id
        ]);
        $this->hasNoSessionMessages([ERROR, WARNING]);
        $this->integer($tid)->isGreaterThan(0);

        $this->boolean(
            $ptask->update([
                'id'                       => $ptask->fields['id'],
                'name'                     => 'test subperiod, no longer out of bounds',
                'projects_id'              => $pid,
                'plan_start_date'          => '2019-08-13',
                'plan_end_date'            => '2019-08-24',
                'projecttasktemplates_id'  => 0
            ])
        )->isTrue();
        $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])
         ->isNotEmpty()
         ->hasKey(WARNING);
        $_SESSION['MESSAGE_AFTER_REDIRECT'] = []; //reset

       //create reference ticket
        $ticket = new \Ticket();
        $this->integer(
            (int)$ticket->add([
                'name'               => 'ticket title',
                'description'        => 'a description',
                'content'            => '',
                'entities_id'        => getItemByTypeName('Entity', '_test_root_entity', true),
                '_users_id_assign'   => getItemByTypeName('User', 'tech', true)
            ])
        )->isGreaterThan(0);

        $this->boolean($ticket->isNewItem())->isFalse();
        $tid = (int)$ticket->fields['id'];

        $ttask = new \TicketTask();
        $ttask_id = (int)$ttask->add([
            'name'               => 'A ticket task in bounds',
            'content'            => 'A ticket task in bounds',
            'tickets_id'         => $tid,
            'plan'               => [
                'begin'  => '2019-08-11',
                'end'    => '2019-08-12'
            ],
            'users_id_tech'      => $users_id,
            'tasktemplates_id'   => 0
        ]);
        $usr_str = '<a href="' . $user->getFormURLWithID($users_id) . '">' . $user->getName() . '</a>';

        $this->hasSessionMessages(
            WARNING,
            [
                "The user $usr_str is busy at the selected timeframe.<br/>- Project task: from 2019-08-11 00:00 to 2019-08-12 00:00:<br/><a href='" .
            $ptask->getFormURLWithID($task_id) . "'>first test, whole period</a><br/>"
            ]
        );
        $this->integer($ttask_id)->isGreaterThan(0);
    }

    public function testGetTeamRoles(): void
    {
        $roles = \ProjectTask::getTeamRoles();
        $this->array($roles)->containsValues([
            Team::ROLE_OWNER,
            Team::ROLE_MEMBER,
        ]);
    }

    public function testGetTeamRoleName(): void
    {
        $roles = \ProjectTask::getTeamRoles();
        foreach ($roles as $role) {
            $this->string(\ProjectTask::getTeamRoleName($role))->isNotEmpty();
        }
    }

    /**
     * Tests addTeamMember, deleteTeamMember, and getTeamMembers methods
     */
    public function testTeamManagement(): void
    {
        $this->login();
        $project_task = new \ProjectTask();

        $project = new \Project();
        $projects_id = $project->add([
            'name'      => 'Team test',
            'content'   => 'Team test'
        ]);

        $projecttasks_id = $project_task->add([
            'projects_id'  => $projects_id,
            'name'         => 'Team test',
            'content'      => 'Team test'
        ]);
        $this->integer($projecttasks_id)->isGreaterThan(0);

       // Check team members array has keys for all team itemtypes
        $team = $project_task->getTeam();
        $this->array($team)->isEmpty();

       // Add team members
        $this->boolean($project_task->addTeamMember(\User::class, 4, ['role' => Team::ROLE_MEMBER]))->isTrue();

       // Reload ticket from DB
        $project_task->getFromDB($projecttasks_id);

       // Check team members
        $team = $project_task->getTeam();
        $this->array($team)->hasSize(1);
        $this->array($team[0])->hasKeys(['itemtype', 'items_id', 'role']);
        $this->string($team[0]['itemtype'])->isEqualTo(\User::class);
        $this->integer($team[0]['items_id'])->isEqualTo(4);
        $this->integer($team[0]['role'])->isEqualTo(Team::ROLE_MEMBER);

       // Delete team members
        $this->boolean($project_task->deleteTeamMember(\User::class, 4, ['role' => Team::ROLE_MEMBER]))->isTrue();

       //Reload ticket from DB
        $project_task->getFromDB($projecttasks_id);
        $team = $project_task->getTeam();

        $this->array($team)->isEmpty();

       // Add team members
        $this->boolean($project_task->addTeamMember(\Group::class, 5, ['role' => Team::ROLE_MEMBER]))->isTrue();

       // Reload ticket from DB
        $project_task->getFromDB($projecttasks_id);

       // Check team members
        $team = $project_task->getTeam();
        $this->array($team)->hasSize(1);
        $this->array($team[0])->hasKeys(['itemtype', 'items_id', 'role']);
        $this->string($team[0]['itemtype'])->isEqualTo(\Group::class);
        $this->integer($team[0]['items_id'])->isEqualTo(5);
        $this->integer($team[0]['role'])->isEqualTo(Team::ROLE_MEMBER);
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
        $this->integer($task->fields['projects_id'])->isEqualTo($project->getID());

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
        $this->integer($task->fields['projects_id'])->isEqualTo($project2->getID());
        $this->integer($subtask->fields['projects_id'])->isEqualTo($project2->getID());
        $this->integer($subtask2->fields['projects_id'])->isEqualTo($project2->getID());
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

        // Clone the task
        $clonedTaskId = $task->clone();
        $clonedTask = \ProjectTask::getById($clonedTaskId);

        // Check if the cloned task is in the same project with the same name
        $this->integer($clonedTask->fields['projects_id'])->isEqualTo($project->getID());
        $this->string($clonedTask->fields['name'])->isEqualTo($task->fields['name'] . ' (copy)');

        // Check if the subtask has been cloned
        $clonedSubtask = new \ProjectTask();
        $clonedSubtask->getFromDBByCrit([
            'projects_id'     => $project->getID(),
            'projecttasks_id' => $clonedTaskId,
        ]);

        $this->integer($clonedSubtask->getID())->isGreaterThan(0);
        $this->integer($clonedSubtask->fields['projects_id'])->isEqualTo($project->getID());
        $this->string($clonedSubtask->fields['name'])->isEqualTo($subtask->fields['name'] . ' (copy)');

        // Check if the subtask of the subtask has been cloned
        $clonedSubtask2 = new \ProjectTask();
        $clonedSubtask2->getFromDBByCrit([
            'projects_id'     => $project->getID(),
            'projecttasks_id' => $clonedSubtask->getID(),
        ]);

        $this->integer($clonedSubtask2->getID())->isGreaterThan(0);
        $this->integer($clonedSubtask2->fields['projects_id'])->isEqualTo($project->getID());
        $this->string($clonedSubtask2->fields['name'])->isEqualTo($subtask2->fields['name'] . ' (copy)');
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
                'percent_done'  => 0
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
            ]
        ];
        yield [
            'input' => [ // Task with percent_done == 50
                'id'            => $task->getID(),
                'percent_done'  => '50',
            ],
            'result' => [
                'percent_done' => '50',
                'projectstates_id' => 2,
            ]
        ];
        yield [
            'input' => [ // Task with percent_done == 100
                'id'            => $task->getID(),
                'percent_done'  => '100',
            ],
            'result' => [
                'percent_done' => '100',
                'projectstates_id' => 3,
            ]
        ];
        yield [
            'input' => [ // Task with percent_done == 25
                'id'            => $task->getID(),
                'percent_done'  => '25',
            ],
            'result' => [
                'percent_done' => '25',
                'projectstates_id' => 2,
            ]
        ];
        yield [
            'input' => [ // Task with percent_done < 0
                'id'            => $task->getID(),
                'percent_done'  => '-1',
            ],
            'result' => [
                'percent_done' => '-1',
                'projectstates_id' => 1,
            ]
        ];
    }

    /**
     * @dataprovider testAutochangeStateProvider
     */
    public function testAutochangeState($input, $result)
    {
        $projecttask = $this->updateItem('ProjectTask', $input['id'], $input);
        $this->integer($projecttask->fields['percent_done'])->isEqualTo($result['percent_done']);
        $this->integer($projecttask->fields['projectstates_id'])->isEqualTo($result['projectstates_id']);
      
    public function testGetActiveProjectTaskIDsForUser()
    {
        $this->login();
        $entity = getItemByTypeName("Entity", "_test_root_entity", true);

        // Create user
        $user = $this->createItem(\User::getType(), ['name' => __FUNCTION__ . 'user']);

        // Check if a user with no project returns an empty array
        $this->array(\ProjectTask::getActiveProjectTaskIDsForUser([$user->getID()]))
            ->isEmpty();

        // Create project
        $project = $this->createItem(\Project::getType(), [
            'name'         => 'project',
            'entities_id'  => $entity
        ]);

        // Check if a user with a project with no tasks returns an empty array
        $this->array(\ProjectTask::getActiveProjectTaskIDsForUser([$user->getID()]))
            ->isEmpty();

        // Create project task
        $project_task = $this->createItem(\ProjectTask::getType(), [
            'projects_id' => $project->getID(),
            'name'        => 'project task',
        ]);

        // Check if a user with a project with tasks, where the user is not a member of the team, returns an empty array
        $this->array(\ProjectTask::getActiveProjectTaskIDsForUser([$user->getID()]))
            ->isEmpty();

        // Create user team
        $user_team = $this->createItem(\ProjectTaskTeam::getType(), [
            'projecttasks_id' => $project_task->getID(),
            'itemtype'        => \User::class,
            'items_id'        => $user->getID(),
        ]);

        // Check if a user with a project with tasks, where the user is a member of the team, returns an array with the task ID
        $this->array(\ProjectTask::getActiveProjectTaskIDsForUser([$user->getID()]))
            ->isEqualTo([['id' => $project_task->getID()]]);

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
        $this->array(\ProjectTask::getActiveProjectTaskIDsForUser([$user->getID()]))
            ->isEqualTo([['id' => $project_task->getID()]]);

        // Check if a user with a project with tasks, where the user is a member of the group and the group is a member of the team, returns an empty array if $search_in_groups is false
        $this->array(\ProjectTask::getActiveProjectTaskIDsForUser([$user->getID()], false))
            ->isEmpty();
    }

    public function testGetActiveProjectTaskIDsForGroup(): void
    {
        $this->login();
        $entity = getItemByTypeName("Entity", "_test_root_entity", true);

        // Create group
        $group = $this->createItem(\Group::getType(), ['name' => __FUNCTION__ . 'group']);

        // Check if a group with no project returns an empty array
        $this->array(\ProjectTask::getActiveProjectTaskIDsForGroup([$group->getID()]))
            ->isEmpty();

        // Create project
        $project = $this->createItem(\Project::getType(), [
            'name'         => 'project',
            'entities_id'  => $entity,
            'groups_id'    => $group->getID()
        ]);

        // Check if a group with a project with no tasks returns an empty array
        $this->array(\ProjectTask::getActiveProjectTaskIDsForGroup([$group->getID()]))
            ->isEmpty();

        // Create project task
        $project_task = $this->createItem(\ProjectTask::getType(), [
            'projects_id' => $project->getID(),
            'name'        => 'project task',
        ]);

        // Check if a group with a project with tasks, where the group is not a member of the team, returns an empty array
        $this->array(\ProjectTask::getActiveProjectTaskIDsForGroup([$group->getID()]))
            ->isEmpty();

        // Create group team
        $this->createItem(\ProjectTaskTeam::getType(), [
            'projecttasks_id' => $project_task->getID(),
            'itemtype'        => \Group::class,
            'items_id'        => $group->getID(),
        ]);

        // Check if a group with a project with tasks, where the group is a member of the team, returns an array with the task ID
        $this->array(\ProjectTask::getActiveProjectTaskIDsForGroup([$group->getID()]))
            ->isEqualTo([['id' => $project_task->getID()]]);
    }
}
