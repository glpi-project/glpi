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
use Notification;
use Notification_NotificationTemplate;
use NotificationEventMailing;
use NotificationTarget;
use NotificationTargetProjectTask;
use NotificationTemplate;
use NotificationTemplateTranslation;
use Project;
use ProjectState;
use ProjectTask;
use ProjectTaskTeam;
use ProjectType;
use QueuedNotification;
use User;
use UserEmail;

/* Test for inc/notificationtargetprojecttask.class.php */

class NotificationTargetProjectTaskTest extends DbTestCase
{
    public function testgetDataForObject()
    {
        $this->login();

        $root_entity = getItemByTypeName('Entity', '_test_root_entity', true);

        // Create a project state and a project type to be referenced by the project
        $state = $this->createItem(ProjectState::class, [
            'name' => 'Notif test project state',
        ]);

        $type = $this->createItem(ProjectType::class, [
            'name' => 'Notif test project type',
        ]);

        // Create the parent project holding the interesting data
        $project = $this->createItem(Project::class, [
            'name'             => 'Test project notif',
            'code'             => 'PRJ-NOTIF-001',
            'content'          => 'Project description content',
            'comment'          => 'Project comment',
            'priority'         => 4,
            'projectstates_id' => $state->getID(),
            'projecttypes_id'  => $type->getID(),
            'plan_start_date'  => '2024-01-01 08:00:00',
            'plan_end_date'    => '2024-02-01 18:00:00',
            'real_start_date'  => '2024-01-02 09:00:00',
            'real_end_date'    => '2024-02-02 17:00:00',
            'entities_id'      => $root_entity,
        ]);

        // Create a task attached to the project
        $ptask = $this->createItem(ProjectTask::class, [
            'name'        => 'Test project task notif',
            'projects_id' => $project->getID(),
            'entities_id' => $root_entity,
        ]);

        $notiftarget = new NotificationTargetProjectTask($root_entity, 'new', $ptask);
        $notiftarget->getTags();

        // basic test for the ##projecttask.projectcode## tag description
        $expected = [
            'tag'            => 'projecttask.projectcode',
            'value'          => true,
            'label'          => 'Project: Code',
            'events'         => 0,
            'foreach'        => false,
            'lang'           => false,
            'allowed_values' => [],
        ];
        $this->assertSame(
            $expected,
            $notiftarget->tag_descriptions['tag']['##projecttask.projectcode##']
        );

        // advanced test: check the values computed for the project tags
        $basic_options = [
            'additionnaloption' => [
                'usertype' => NotificationTarget::GLPI_USER,
            ],
        ];
        $notiftarget->addDataForTemplate('new', $basic_options);
        $data = $notiftarget->data;

        global $CFG_GLPI;

        $this->assertSame('Test project notif', $data['##projecttask.project##']);
        $this->assertSame(
            sprintf(
                '%s/index.php?redirect=Project_%d',
                $CFG_GLPI['url_base'],
                $project->getID()
            ),
            $data['##projecttask.projecturl##']
        );
        $this->assertSame('PRJ-NOTIF-001', $data['##projecttask.projectcode##']);
        $this->assertSame('Project description content', $data['##projecttask.projectdescription##']);
        $this->assertSame('Project comment', $data['##projecttask.projectcomments##']);
        $this->assertSame(
            \Html::convDateTime($project->fields['plan_start_date']),
            $data['##projecttask.projectplanstartdate##']
        );
        $this->assertSame(
            \Html::convDateTime($project->fields['plan_end_date']),
            $data['##projecttask.projectplanenddate##']
        );
        $this->assertSame(
            \Html::convDateTime($project->fields['real_start_date']),
            $data['##projecttask.projectrealstartdate##']
        );
        $this->assertSame(
            \Html::convDateTime($project->fields['real_end_date']),
            $data['##projecttask.projectrealenddate##']
        );
        $this->assertSame(
            \Dropdown::getDropdownName('glpi_projectstates', $state->getID()),
            $data['##projecttask.projectstate##']
        );
        $this->assertSame(
            \Dropdown::getDropdownName('glpi_projecttypes', $type->getID()),
            $data['##projecttask.projecttype##']
        );
        $this->assertSame(
            \CommonITILObject::getPriorityName(4),
            $data['##projecttask.projectpriority##']
        );
    }

    private function createProjectTask(): ProjectTask
    {
        $entities_id = $this->getTestRootEntity(true);

        $project = $this->createItem(Project::class, [
            'name'        => __FUNCTION__,
            'entities_id' => $entities_id,
        ]);

        return $this->createItem(ProjectTask::class, [
            'name'        => __FUNCTION__,
            'projects_id' => $project->getID(),
            'entities_id' => $entities_id,
        ]);
    }

    private function addEmail(string $username, string $email): int
    {
        $users_id = getItemByTypeName(User::class, $username, true);
        $this->createItem(UserEmail::class, [
            'users_id'   => $users_id,
            'email'      => $email,
            'is_default' => 1,
        ]);

        return $users_id;
    }

    private function getMailingTarget(ProjectTask $task): NotificationTargetProjectTask
    {
        $target = new NotificationTargetProjectTask(event: 'assign', object: $task);
        $target->setEvent(NotificationEventMailing::class);

        return $target;
    }

    public function testAssignEventAndRecipientAreAvailable(): void
    {
        $this->login();

        $target = NotificationTarget::getInstanceByType(ProjectTask::class);
        $this->assertInstanceOf(NotificationTargetProjectTask::class, $target);
        $this->assertArrayHasKey('assign', $target->getAllEvents());

        $recipient_key = Notification::USER_TYPE . '_' . Notification::NEW_TEAM_MEMBER;

        // Recipient is only offered for the assign event
        $assign_target = NotificationTarget::getInstanceByType(ProjectTask::class, 'assign');
        $this->assertInstanceOf(NotificationTargetProjectTask::class, $assign_target);
        $this->assertArrayHasKey($recipient_key, $assign_target->notification_targets);

        $update_target = NotificationTarget::getInstanceByType(ProjectTask::class, 'update');
        $this->assertInstanceOf(NotificationTargetProjectTask::class, $update_target);
        $this->assertArrayNotHasKey($recipient_key, $update_target->notification_targets);
    }

    public function testNewTeamMemberUserRecipient(): void
    {
        $this->login();

        $task      = $this->createProjectTask();
        $tech_id   = $this->addEmail('tech', 'tech@localhost');
        $normal_id = $this->addEmail('normal', 'normal@localhost');

        // Existing member must not be notified of someone else's assignment
        $this->createItem(ProjectTaskTeam::class, [
            'projecttasks_id' => $task->getID(),
            'itemtype'        => User::class,
            'items_id'        => $normal_id,
            '_disablenotif'   => true,
        ]);

        $target = $this->getMailingTarget($task);
        $target->addSpecificTargets(
            ['type' => Notification::USER_TYPE, 'items_id' => Notification::NEW_TEAM_MEMBER],
            ['team_member_itemtype' => User::class, 'team_member_items_id' => $tech_id]
        );

        $this->assertSame(['tech@localhost'], array_keys($target->target));
    }

    public function testNewTeamMemberGroupRecipient(): void
    {
        $this->login();

        $task    = $this->createProjectTask();
        $tech_id = $this->addEmail('tech', 'tech@localhost');

        $group = $this->createItem(Group::class, [
            'name'        => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $this->createItem(Group_User::class, [
            'groups_id' => $group->getID(),
            'users_id'  => $tech_id,
        ]);

        $target = $this->getMailingTarget($task);
        $target->addSpecificTargets(
            ['type' => Notification::USER_TYPE, 'items_id' => Notification::NEW_TEAM_MEMBER],
            ['team_member_itemtype' => Group::class, 'team_member_items_id' => $group->getID()]
        );

        $this->assertSame(['tech@localhost'], array_keys($target->target));
    }

    public function testNewTeamMemberWithInvalidOptions(): void
    {
        $this->login();

        $task = $this->createProjectTask();
        $this->addEmail('tech', 'tech@localhost');

        $invalid_options = [
            [],
            ['team_member_itemtype' => User::class, 'team_member_items_id' => 0],
            ['team_member_itemtype' => User::class, 'team_member_items_id' => 999999],
            ['team_member_itemtype' => 'Computer', 'team_member_items_id' => 1],
        ];

        foreach ($invalid_options as $options) {
            $target = $this->getMailingTarget($task);
            $target->addSpecificTargets(
                ['type' => Notification::USER_TYPE, 'items_id' => Notification::NEW_TEAM_MEMBER],
                $options
            );
            $this->assertEmpty($target->target);
        }
    }

    public function testAssignNotificationIsQueuedOnTeamMemberAdd(): void
    {
        global $CFG_GLPI;

        $this->login();

        $CFG_GLPI['use_notifications']     = 1;
        $CFG_GLPI['notifications_mailing'] = 1;

        $task      = $this->createProjectTask();
        $tech_id   = $this->addEmail('tech', 'tech@localhost');
        $normal_id = $this->addEmail('normal', 'normal@localhost');

        $notification = $this->createItem(Notification::class, [
            'name'         => __FUNCTION__,
            'entities_id'  => 0,
            'is_recursive' => 1,
            'is_active'    => 1,
            'itemtype'     => ProjectTask::class,
            'event'        => 'assign',
        ]);
        $template = $this->createItem(NotificationTemplate::class, [
            'name'     => __FUNCTION__,
            'itemtype' => ProjectTask::class,
        ]);
        $this->createItem(NotificationTemplateTranslation::class, [
            'notificationtemplates_id' => $template->getID(),
            'language'                 => '',
            'subject'                  => 'Assigned to ##projecttask.name##',
            'content_text'             => '##newteammember.name## (##newteammember.itemtype##)',
            'content_html'             => '##newteammember.name## (##newteammember.itemtype##)',
        ]);
        $this->createItem(Notification_NotificationTemplate::class, [
            'notifications_id'         => $notification->getID(),
            'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
            'notificationtemplates_id' => $template->getID(),
        ]);
        $this->createItem(NotificationTarget::class, [
            'notifications_id' => $notification->getID(),
            'type'             => Notification::USER_TYPE,
            'items_id'         => Notification::NEW_TEAM_MEMBER,
        ]);

        // Existing member, added without notification: must not receive the assign notification
        $this->createItem(ProjectTaskTeam::class, [
            'projecttasks_id' => $task->getID(),
            'itemtype'        => User::class,
            'items_id'        => $normal_id,
            '_disablenotif'   => true,
        ]);

        $this->createItem(ProjectTaskTeam::class, [
            'projecttasks_id' => $task->getID(),
            'itemtype'        => User::class,
            'items_id'        => $tech_id,
        ]);

        $queued = (new QueuedNotification())->find([
            'itemtype' => ProjectTask::class,
            'items_id' => $task->getID(),
            'event'    => 'assign',
        ]);
        $this->assertCount(1, $queued);

        $queued = reset($queued);
        $this->assertSame('tech@localhost', $queued['recipient']);
        $this->assertStringContainsString(getItemByTypeName(User::class, 'tech')->getName(), $queued['body_text']);
    }
}
