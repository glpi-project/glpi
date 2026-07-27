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

use Glpi\Tests\DbTestCase;
use Group;
use Group_User;
use PlanningRecall;
use Project;
use ProjectTask;
use ProjectTaskTeam;
use Session;
use User;

class ProjectTaskPlanningRecallTest extends DbTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createProjectAndTask(?int $recall = null, string $end_date = '+2 days'): array
    {
        $project = $this->createItem(
            Project::class,
            [
                'name' => __FUNCTION__,
            ]
        );
        $task    = $this->createItem(
            ProjectTask::class,
            [
                'name'          => __FUNCTION__,
                'projects_id'   => $project->getID(),
                'plan_end_date' => date('Y-m-d H:i:s', strtotime($end_date)),
                'recall'        => $recall,
            ]
        );
        return [$project, $task];
    }

    private function addUserToTeam(ProjectTask $task, int $users_id): ProjectTaskTeam
    {
        return $this->createItem(
            ProjectTaskTeam::class,
            [
                'projecttasks_id' => $task->getID(),
                'itemtype'        => User::class,
                'items_id'        => $users_id,
            ]
        );
    }

    private function addGroupToTeam(ProjectTask $task, int $groups_id): ProjectTaskTeam
    {
        return $this->createItem(
            ProjectTaskTeam::class,
            [
                'projecttasks_id' => $task->getID(),
                'itemtype'        => Group::class,
                'items_id'        => $groups_id,
            ]
        );
    }

    private function assertHasRecall(int $task_id, int $users_id, int $before_time): void
    {
        $recall = new PlanningRecall();
        $this->assertTrue(
            $recall->getFromDBByCrit([
                'itemtype' => ProjectTask::class,
                'items_id' => $task_id,
                'users_id' => $users_id,
            ]),
            "Expected a planning recall for user #$users_id on task #$task_id"
        );
        $this->assertSame($before_time, (int) $recall->fields['before_time']);
    }

    private function assertNoRecall(int $task_id, int $users_id): void
    {
        $recall = new PlanningRecall();
        $this->assertFalse(
            $recall->getFromDBByCrit([
                'itemtype' => ProjectTask::class,
                'items_id' => $task_id,
                'users_id' => $users_id,
            ]),
            "Expected no planning recall for user #$users_id on task #$task_id"
        );
    }

    // -------------------------------------------------------------------------
    // Task recall field — creation
    // -------------------------------------------------------------------------

    public function testRecallCreatedForTeamUserOnTaskAdd(): void
    {
        $this->login();
        $users_id = Session::getLoginUserID();

        [, $task] = $this->createProjectAndTask(HOUR_TIMESTAMP);
        $this->addUserToTeam($task, $users_id);

        $this->assertHasRecall($task->getID(), $users_id, HOUR_TIMESTAMP);
    }

    public function testNoRecallCreatedWhenRecallIsNone(): void
    {
        $this->login();
        $users_id = Session::getLoginUserID();

        [, $task] = $this->createProjectAndTask(null);
        $this->addUserToTeam($task, $users_id);

        $this->assertNoRecall($task->getID(), $users_id);
    }

    public function testRecallCreatedForAllTeamMembersOnTaskAdd(): void
    {
        $this->login();
        $current_user_id = Session::getLoginUserID();
        $tech            = getItemByTypeName(User::class, 'tech');

        [, $task] = $this->createProjectAndTask(2 * HOUR_TIMESTAMP);
        $this->addUserToTeam($task, $current_user_id);
        $this->addUserToTeam($task, $tech->getID());

        $this->assertHasRecall($task->getID(), $current_user_id, 2 * HOUR_TIMESTAMP);
        $this->assertHasRecall($task->getID(), $tech->getID(), 2 * HOUR_TIMESTAMP);
    }

    // -------------------------------------------------------------------------
    // Task recall field — update
    // -------------------------------------------------------------------------

    public function testRecallUpdatedForTeamMembersWhenRecallChanges(): void
    {
        $this->login();
        $current_user_id = Session::getLoginUserID();
        $tech            = getItemByTypeName(User::class, 'tech');

        [, $task] = $this->createProjectAndTask(HOUR_TIMESTAMP);
        $this->addUserToTeam($task, $current_user_id);
        $this->addUserToTeam($task, $tech->getID());

        $this->updateItem(ProjectTask::class, $task->getID(), ['recall' => 2 * HOUR_TIMESTAMP]);

        $this->assertHasRecall($task->getID(), $current_user_id, 2 * HOUR_TIMESTAMP);
        $this->assertHasRecall($task->getID(), $tech->getID(), 2 * HOUR_TIMESTAMP);
    }

    public function testRecallCreatedWhenRecallChangesFromNoneToValue(): void
    {
        $this->login();
        $users_id = Session::getLoginUserID();

        [, $task] = $this->createProjectAndTask(null);
        $this->addUserToTeam($task, $users_id);

        $this->assertNoRecall($task->getID(), $users_id);

        $this->updateItem(ProjectTask::class, $task->getID(), ['recall' => HOUR_TIMESTAMP]);

        $this->assertHasRecall($task->getID(), $users_id, HOUR_TIMESTAMP);
    }

    public function testRecallDeletedWhenRecallChangesToNone(): void
    {
        $this->login();
        $users_id = Session::getLoginUserID();

        [, $task] = $this->createProjectAndTask(HOUR_TIMESTAMP);
        $this->addUserToTeam($task, $users_id);
        $this->assertHasRecall($task->getID(), $users_id, HOUR_TIMESTAMP);

        $this->updateItem(ProjectTask::class, $task->getID(), ['recall' => null]);

        $this->assertNoRecall($task->getID(), $users_id);
    }

    // -------------------------------------------------------------------------
    // ProjectTaskTeam — user added / removed
    // -------------------------------------------------------------------------

    public function testRecallCreatedWhenUserAddedToTeam(): void
    {
        $this->login();
        $users_id = Session::getLoginUserID();

        [, $task] = $this->createProjectAndTask(HOUR_TIMESTAMP);
        $this->assertNoRecall($task->getID(), $users_id);

        $this->addUserToTeam($task, $users_id);

        $this->assertHasRecall($task->getID(), $users_id, HOUR_TIMESTAMP);
    }

    public function testNoRecallCreatedWhenRecallIsNoneAndUserAddedToTeam(): void
    {
        $this->login();
        $users_id = Session::getLoginUserID();

        [, $task] = $this->createProjectAndTask(null);
        $this->addUserToTeam($task, $users_id);

        $this->assertNoRecall($task->getID(), $users_id);
    }

    public function testRecallDeletedWhenUserRemovedFromTeam(): void
    {
        $this->login();
        $users_id = Session::getLoginUserID();

        [, $task]   = $this->createProjectAndTask(HOUR_TIMESTAMP);
        $team_entry = $this->addUserToTeam($task, $users_id);
        $this->assertHasRecall($task->getID(), $users_id, HOUR_TIMESTAMP);

        $this->deleteItem(ProjectTaskTeam::class, $team_entry->getID());

        $this->assertNoRecall($task->getID(), $users_id);
    }

    public function testNoRecallDuplicatedWhenUserAddedDirectlyAndAlreadyInTeamViaGroup(): void
    {
        $this->login();
        $tech  = getItemByTypeName(User::class, 'tech');
        $group = $this->createItem(
            Group::class,
            [
                'name' => __FUNCTION__,
            ]
        );
        $this->createItem(
            Group_User::class,
            [
                'groups_id' => $group->getID(),
                'users_id'  => $tech->getID(),
            ]
        );

        [, $task] = $this->createProjectAndTask(HOUR_TIMESTAMP);
        $this->addGroupToTeam($task, $group->getID());
        $this->assertHasRecall($task->getID(), $tech->getID(), HOUR_TIMESTAMP);

        // Add the same user directly — they already have a recall via the group
        $this->addUserToTeam($task, $tech->getID());

        $this->assertSame(1, countElementsInTable(PlanningRecall::getTable(), [
            'itemtype' => ProjectTask::class,
            'items_id' => $task->getID(),
            'users_id' => $tech->getID(),
        ]));
    }

    // -------------------------------------------------------------------------
    // ProjectTaskTeam — group added / removed
    // -------------------------------------------------------------------------

    public function testRecallCreatedForGroupMembersWhenGroupAddedToTeam(): void
    {
        $this->login();
        $tech  = getItemByTypeName(User::class, 'tech');
        $group = $this->createItem(
            Group::class,
            [
                'name' => __FUNCTION__,
            ]
        );
        $this->createItem(
            Group_User::class,
            [
                'groups_id' => $group->getID(),
                'users_id'  => $tech->getID(),
            ]
        );

        [, $task] = $this->createProjectAndTask(HOUR_TIMESTAMP);
        $this->assertNoRecall($task->getID(), $tech->getID());

        $this->addGroupToTeam($task, $group->getID());

        $this->assertHasRecall($task->getID(), $tech->getID(), HOUR_TIMESTAMP);
    }

    public function testRecallDeletedForGroupMembersWhenGroupRemovedFromTeam(): void
    {
        $this->login();
        $tech  = getItemByTypeName(User::class, 'tech');
        $group = $this->createItem(
            Group::class,
            [
                'name' => __FUNCTION__,
            ]
        );
        $this->createItem(
            Group_User::class,
            [
                'groups_id' => $group->getID(),
                'users_id'  => $tech->getID(),
            ]
        );

        [, $task]   = $this->createProjectAndTask(HOUR_TIMESTAMP);
        $team_entry = $this->addGroupToTeam($task, $group->getID());
        $this->assertHasRecall($task->getID(), $tech->getID(), HOUR_TIMESTAMP);

        $this->deleteItem(ProjectTaskTeam::class, $team_entry->getID());

        $this->assertNoRecall($task->getID(), $tech->getID());
    }

    public function testRecallKeptForDirectMemberWhenGroupRemovedFromTeam(): void
    {
        $this->login();
        $tech   = getItemByTypeName(User::class, 'tech');
        $normal = getItemByTypeName(User::class, 'normal');
        $group  = $this->createItem(
            Group::class,
            [
                'name' => __FUNCTION__,
            ]
        );
        $this->createItem(
            Group_User::class,
            [
                'groups_id' => $group->getID(),
                'users_id'  => $tech->getID(),
            ]
        );
        $this->createItem(
            Group_User::class,
            [
                'groups_id' => $group->getID(),
                'users_id'  => $normal->getID(),
            ]
        );

        [, $task]   = $this->createProjectAndTask(HOUR_TIMESTAMP);
        $team_group = $this->addGroupToTeam($task, $group->getID());
        $this->addUserToTeam($task, $tech->getID()); // tech is also directly in the team
        $this->assertHasRecall($task->getID(), $tech->getID(), HOUR_TIMESTAMP);
        $this->assertHasRecall($task->getID(), $normal->getID(), HOUR_TIMESTAMP);

        $this->deleteItem(ProjectTaskTeam::class, $team_group->getID());

        // tech is still directly in team → recall must be preserved
        $this->assertHasRecall($task->getID(), $tech->getID(), HOUR_TIMESTAMP);
        // normal was only in the group → recall must be deleted
        $this->assertNoRecall($task->getID(), $normal->getID());
    }

    // -------------------------------------------------------------------------
    // Group_User — user joins / leaves group
    // -------------------------------------------------------------------------

    public function testRecallCreatedWhenUserJoinsGroupAlreadyInTeam(): void
    {
        $this->login();
        $tech  = getItemByTypeName(User::class, 'tech');
        $group = $this->createItem(
            Group::class,
            [
                'name' => __FUNCTION__,
            ]
        );

        [, $task] = $this->createProjectAndTask(HOUR_TIMESTAMP);
        $this->addGroupToTeam($task, $group->getID());

        // Tech not in group yet → no recall
        $this->assertNoRecall($task->getID(), $tech->getID());

        $this->createItem(
            Group_User::class,
            [
                'groups_id' => $group->getID(),
                'users_id'  => $tech->getID(),
            ]
        );

        $this->assertHasRecall($task->getID(), $tech->getID(), HOUR_TIMESTAMP);
    }

    public function testNoRecallCreatedWhenUserJoinsGroupNotInAnyTeam(): void
    {
        $this->login();
        $tech  = getItemByTypeName(User::class, 'tech');
        $group = $this->createItem(
            Group::class,
            [
                'name' => __FUNCTION__,
            ]
        );

        // Group is NOT added to any task team
        $this->createItem(Group_User::class, [
            'groups_id' => $group->getID(),
            'users_id'  => $tech->getID(),
        ]);

        // No recall should exist for any task
        $recall = new PlanningRecall();
        $this->assertFalse($recall->getFromDBByCrit([
            'itemtype' => ProjectTask::class,
            'users_id' => $tech->getID(),
        ]));
    }

    public function testRecallDeletedWhenUserLeavesGroupInTeam(): void
    {
        $this->login();
        $tech       = getItemByTypeName(User::class, 'tech');
        $group      = $this->createItem(
            Group::class,
            [
                'name' => __FUNCTION__,
            ]
        );
        $group_user = $this->createItem(
            Group_User::class,
            [
                'groups_id' => $group->getID(),
                'users_id'  => $tech->getID(),
            ]
        );

        [, $task] = $this->createProjectAndTask(HOUR_TIMESTAMP);
        $this->addGroupToTeam($task, $group->getID());
        $this->assertHasRecall($task->getID(), $tech->getID(), HOUR_TIMESTAMP);

        $this->deleteItem(Group_User::class, $group_user->getID(), true);

        $this->assertNoRecall($task->getID(), $tech->getID());
    }

    public function testRecallKeptWhenUserLeavesGroupButIsDirectlyInTaskTeam(): void
    {
        $this->login();
        $tech       = getItemByTypeName(User::class, 'tech');
        $group      = $this->createItem(
            Group::class,
            [
                'name' => __FUNCTION__,
            ]
        );

        $group_user = $this->createItem(
            Group_User::class,
            [
                'groups_id' => $group->getID(),
                'users_id'  => $tech->getID(),
            ]
        );

        [, $task] = $this->createProjectAndTask(HOUR_TIMESTAMP);
        $this->addGroupToTeam($task, $group->getID());
        $this->addUserToTeam($task, $tech->getID()); // also directly in team
        $this->assertHasRecall($task->getID(), $tech->getID(), HOUR_TIMESTAMP);

        // Remove from group — user is still directly in the task team
        $this->deleteItem(Group_User::class, $group_user->getID(), true);

        $this->assertHasRecall($task->getID(), $tech->getID(), HOUR_TIMESTAMP);
    }

    // -------------------------------------------------------------------------
    // Group purged from GLPI
    // -------------------------------------------------------------------------

    public function testRecallDeletedWhenGroupPurgedFromGlpi(): void
    {
        $this->login();
        $tech  = getItemByTypeName(User::class, 'tech');
        $group = $this->createItem(
            Group::class,
            [
                'name' => __FUNCTION__,
            ]
        );
        $this->createItem(
            Group_User::class,
            [
                'groups_id' => $group->getID(),
                'users_id'  => $tech->getID(),
            ]
        );

        [, $task] = $this->createProjectAndTask(HOUR_TIMESTAMP);
        $this->addGroupToTeam($task, $group->getID());
        $this->assertHasRecall($task->getID(), $tech->getID(), HOUR_TIMESTAMP);

        // Purging the group cascades: Group → ProjectTaskTeam::post_deleteItem → PlanningRecall deleted
        $this->deleteItem(Group::class, $group->getID(), true);

        $this->assertNoRecall($task->getID(), $tech->getID());
    }

    // -------------------------------------------------------------------------
    // Authorization — ownership and rights checks on update / purge
    // -------------------------------------------------------------------------

    public function testOwnerCanUpdateOwnRecallWhenInTeam(): void
    {
        $this->login();
        $tech = getItemByTypeName(User::class, 'tech');

        [, $task] = $this->createProjectAndTask(HOUR_TIMESTAMP);
        $this->addUserToTeam($task, $tech->getID());

        $recall = new PlanningRecall();
        $this->assertTrue($recall->getFromDBByCrit([
            'itemtype' => ProjectTask::class,
            'items_id' => $task->getID(),
            'users_id' => $tech->getID(),
        ]));

        $this->login('tech', 'tech');
        $this->assertTrue($recall->canUpdateItem());
    }

    public function testNonOwnerCannotUpdateOtherUsersRecall(): void
    {
        $this->login();
        $tech   = getItemByTypeName(User::class, 'tech');
        $normal = getItemByTypeName(User::class, 'normal');

        [, $task] = $this->createProjectAndTask(HOUR_TIMESTAMP);
        $this->addUserToTeam($task, $tech->getID());

        $recall = new PlanningRecall();
        $this->assertTrue($recall->getFromDBByCrit([
            'itemtype' => ProjectTask::class,
            'items_id' => $task->getID(),
            'users_id' => $tech->getID(),
        ]));

        // normal is not the recall owner and has no task update rights
        $this->login('normal', 'normal');
        $this->assertFalse($recall->canUpdateItem());
    }

    public function testOwnerCanPurgeOwnRecall(): void
    {
        $this->login();
        $tech = getItemByTypeName(User::class, 'tech');

        [, $task] = $this->createProjectAndTask(HOUR_TIMESTAMP);
        $this->addUserToTeam($task, $tech->getID());

        $recall = new PlanningRecall();
        $this->assertTrue($recall->getFromDBByCrit([
            'itemtype' => ProjectTask::class,
            'items_id' => $task->getID(),
            'users_id' => $tech->getID(),
        ]));

        $this->login('tech', 'tech');
        $this->assertTrue($recall->canPurgeItem());
    }

    public function testNonOwnerCannotPurgeOtherUsersRecall(): void
    {
        $this->login();
        $tech   = getItemByTypeName(User::class, 'tech');
        $normal = getItemByTypeName(User::class, 'normal');

        [, $task] = $this->createProjectAndTask(HOUR_TIMESTAMP);
        $this->addUserToTeam($task, $tech->getID());

        $recall = new PlanningRecall();
        $this->assertTrue($recall->getFromDBByCrit([
            'itemtype' => ProjectTask::class,
            'items_id' => $task->getID(),
            'users_id' => $tech->getID(),
        ]));

        // normal is not the recall owner and has no task update rights
        $this->login('normal', 'normal');
        $this->assertFalse($recall->canPurgeItem());
    }
}
