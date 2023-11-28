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
        $ticket = new \Ticket();
        $ticket_id = $ticket->add([
            'name'        => "Test tech task",
            'content'     => "Test tech task",
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'date'        => $_SESSION['glpi_currenttime'],
        ]);
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
                'id' => $ticket_id,
                'task' => new \TicketTask(),
                'type_user' => new \Ticket_User(),
                'task_content' => "<p> Test task for ticket</p>",
            ],
            [
                'id' => $change_id,
                'task' => new \ChangeTask(),
                'type_user' => new \Change_User(),
                'task_content' => "<p> Test task for change </p>",
            ],
            [
                'id' => $problem_id,
                'task' => new \ProblemTask(),
                'type_user' => new \Problem_User(),
                'task_content' => "<p> Test task for problem </p>",
            ],
        ];
    }

    /**
     * @dataprovider dataTechTicketTask
     */
    public function testAddTechToItilFromTask($id, $task, $type_user, $task_content)
    {
        $foreignkey = getForeignKeyFieldForItemType($task->getItilObjectItemType());
        // Add a task
        $task_id = $task->add([
            $foreignkey => $id,
            'users_id'   => 4,
            'content'    => $task_content,
            'state'      => 1,
            'users_id_tech'   => 4,
        ]);
        $this->integer(count($task->find(['id' => $task_id])))->isEqualTo(1);

        $this->integer(count($type_user->find(
            [
                $foreignkey => $id,
                'users_id' => 4,
                'type' => \CommonITILActor::ASSIGN
            ]
        )))->isEqualTo(1);

        $this->boolean(
            $task->update([
                'id' => $task_id,
                $foreignkey => $id,
                'users_id' => 3,
                'users_id_tech' => 3,
            ])
        )->isTrue();

        $this->integer(count($type_user->find(
            [
                $foreignkey => $id,
                'users_id' => 3,
                'type' => \CommonITILActor::ASSIGN
            ]
        )))->isEqualTo(1);
    }
}
