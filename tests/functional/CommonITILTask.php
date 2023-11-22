<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

class CommonITILTask extends DbTestCase
{
    protected function dataTechTicketTask(): array
    {
        $change = new \Change();
        $change_id = $change->add([
            'name'        => "Test tech task",
            'content'     => "Test tech task",
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'date'        => $_SESSION['glpi_currenttime'],
        ]);
        $problem = new \Problem();
        $problem_id = $problem->add([
            'name'        => "Test tech task",
            'content'     => "Test tech task",
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'date'        => $_SESSION['glpi_currenttime'],
        ]);
        return [
            [
                'id' => 101,
                'task' => new \TicketTask(),
                'type_user' => new \Ticket_User(),
                'foreignkey' => 'tickets_id',
                'task_users_id' => 2,
                'task_content' => "<p> Test task </p>",
                'task_state' => 1,
                'task_users_id_tech' => 4,
                'ticket_users_id' => 4,
                'ticket_type' => 2,
                'update_users_id' => 3,
                'update_users_id_tech' => 3,
            ],
            [
                'id' => $change_id,
                'task' => new \ChangeTask(),
                'type_user' => new \Change_User(),
                'foreignkey' => 'changes_id',
                'task_users_id' => 4,
                'task_content' => "<p> Test task for change </p>",
                'task_state' => 1,
                'task_users_id_tech' => 4,
                'ticket_users_id' => 4,
                'ticket_type' => 2,
                'update_users_id' => 3,
                'update_users_id_tech' => 3,
            ],
            [
                'id' => $problem_id,
                'task' => new \ProblemTask(),
                'type_user' => new \Problem_User(),
                'foreignkey' => 'problems_id',
                'task_users_id' => 4,
                'task_content' => "<p> Test task for problem </p>",
                'task_state' => 1,
                'task_users_id_tech' => 4,
                'ticket_users_id' => 4,
                'ticket_type' => 2,
                'update_users_id' => 3,
                'update_users_id_tech' => 3,
            ],
        ];
    }

    /**
     * @dataprovider dataTechTicketTask
     */
    public function testAddTechToItilFromTask($id, $task, $type_user, $foreignkey, $task_users_id, $task_content, $task_state, $task_users_id_tech, $ticket_users_id, $ticket_type, $update_users_id, $update_users_id_tech)
    {
        // Add a task
        $task_id = $task->add([
            $foreignkey => $id,
            'users_id'   => $task_users_id,
            'content'    => $task_content,
            'state'      => $task_state,
            'users_id_tech'   => $task_users_id_tech,
        ]);
        $this->integer(count($task->find(['id' => $task_id])))->isEqualTo(1);

        $this->integer(count($type_user->find([$foreignkey => $id, 'users_id' => $ticket_users_id, 'type' => $ticket_type])))->isEqualTo(1);

        $task->update([
            'id' => $task_id,
            $foreignkey => $id,
            'users_id' => $update_users_id,
            'users_id_tech' => $update_users_id_tech,
        ]);

        $this->integer(count($type_user->find([$foreignkey => $id, 'users_id' => $update_users_id, 'type' => $ticket_type])))->isEqualTo(1);
    }
}
