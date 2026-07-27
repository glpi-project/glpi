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
use NotificationTarget;
use Project;
use ProjectState;
use ProjectTask;
use ProjectType;

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

        $notiftarget = new \NotificationTargetProjectTask($root_entity, 'new', $ptask);
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
}
