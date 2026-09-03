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
use Project;
use ProjectTask;
use ProjectTaskLink;

/* Test for inc/projecttasklink.class.php */

class ProjectTaskLinkTest extends DbTestCase
{
    public function testGetFromDBForItemIDs()
    {
        $this->login();

        $project = $this->createItem(Project::class, ['name' => 'Test project for task links']);

        $task_input = [
            'projects_id'             => $project->getID(),
            'plan_start_date'         => '2026-01-01 00:00:00',
            'plan_end_date'           => '2026-01-10 00:00:00',
            'projecttasktemplates_id' => 0,
        ];
        [$task_1, $task_2, $task_3] = $this->createItems(ProjectTask::class, [
            ['name' => 'task 1'] + $task_input,
            ['name' => 'task 2'] + $task_input,
            ['name' => 'task 3'] + $task_input,
        ]);

        [$link_1_2, $link_2_3] = $this->createItems(ProjectTaskLink::class, [
            [
                'projecttasks_id_source' => $task_1->getID(),
                'source_uuid'            => uniqid(),
                'projecttasks_id_target' => $task_2->getID(),
                'target_uuid'            => uniqid(),
            ],
            [
                'projecttasks_id_source' => $task_2->getID(),
                'source_uuid'            => uniqid(),
                'projecttasks_id_target' => $task_3->getID(),
                'target_uuid'            => uniqid(),
            ],
        ]);

        $projecttasklink = new ProjectTaskLink();

        // Only task_1 and task_2 requested. Only the link between them must be returned
        $this->assertSame(
            [$link_1_2->getID()],
            $this->getLinkIds($projecttasklink, [$task_1->getID(), $task_2->getID()])
        );

        // All 3 tasks requested: both links must be returned
        $expected = [$link_1_2->getID(), $link_2_3->getID()];
        sort($expected);
        $this->assertSame(
            $expected,
            $this->getLinkIds($projecttasklink, [$task_1->getID(), $task_2->getID(), $task_3->getID()])
        );

        // Only task_3 requested: no link has both ends in scope
        $this->assertSame(
            [],
            $this->getLinkIds($projecttasklink, [$task_3->getID()])
        );
    }

    private function getLinkIds(ProjectTaskLink $projecttasklink, array $ids): array
    {
        $found = [];
        foreach ($projecttasklink->getFromDBForItemIDs($ids) as $data) {
            $found[] = (int) $data['id'];
        }
        sort($found);

        return $found;
    }
}
