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

class ProjectTaskTest extends DbTestCase
{
    public function testPlanningConflict()
    {
        $this->login();

        $user = getItemByTypeName('User', 'tech');
        $users_id = (int)$user->fields['id'];

        $ptask = new \ProjectTask();
        $this->assertSame(
            0,
            (int)$ptask->add([
                'name'   => 'test'
            ])
        );

        $this->hasSessionMessages(ERROR, ['A linked project is mandatory']);

        $project = new \Project();
        $pid = (int)$project->add([
            'name'   => 'Test project'
        ]);
        $this->assertGreaterThan(0, $pid);

        $this->assertGreaterThan(
            0,
            (int)$ptask->add([
                'name'                     => 'first test, whole period',
                'projects_id'              => $pid,
                'plan_start_date'          => '2019-08-10',
                'plan_end_date'            => '2019-08-20',
                'projecttasktemplates_id'  => 0
            ])
        );
        $this->hasNoSessionMessages([ERROR, WARNING]);
        $task_id = $ptask->fields['id'];

        $team = new \ProjectTaskTeam();
        $tid = (int)$team->add([
            'projecttasks_id' => $ptask->fields['id'],
            'itemtype'        => \User::getType(),
            'items_id'        => $users_id
        ]);
        $this->hasNoSessionMessages([ERROR, WARNING]);
        $this->assertGreaterThan(0, $tid);

        $this->assertGreaterThan(
            0,
            (int)$ptask->add([
                'name'                     => 'test, subperiod',
                'projects_id'              => $pid,
                'plan_start_date'          => '2019-08-13',
                'plan_end_date'            => '2019-08-14',
                'projecttasktemplates_id'  => 0
            ])
        );
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
        $this->assertGreaterThan(0, $tid);

        //check when updating. first create a new task out of existing bouds
        $this->assertGreaterThan(
            0,
            (int)$ptask->add([
                'name'                     => 'test subperiod, out of bounds',
                'projects_id'              => $pid,
                'plan_start_date'          => '2018-08-13',
                'plan_end_date'            => '2018-08-24',
                'projecttasktemplates_id'  => 0
            ])
        );
        $this->hasNoSessionMessages([ERROR, WARNING]);

        $team = new \ProjectTaskTeam();
        $tid = (int)$team->add([
            'projecttasks_id' => $ptask->fields['id'],
            'itemtype'        => \User::getType(),
            'items_id'        => $users_id
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
                'projecttasktemplates_id'  => 0
            ])
        );

        $this->assertArrayHasKey(WARNING, $_SESSION['MESSAGE_AFTER_REDIRECT']);
        $_SESSION['MESSAGE_AFTER_REDIRECT'] = []; //reset

        //create reference ticket
        $ticket = new \Ticket();
        $this->assertGreaterThan(
            0,
            (int)$ticket->add([
                'name'               => 'ticket title',
                'description'        => 'a description',
                'content'            => '',
                'entities_id'        => getItemByTypeName('Entity', '_test_root_entity', true),
                '_users_id_assign'   => getItemByTypeName('User', 'tech', true)
            ])
        );

        $this->assertFalse($ticket->isNewItem());
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
}
