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
    public function testAddTechToTicketFromTask()
    {
        $ticket = new \Ticket();
        $ticket_id = $ticket->add([
            'name'        => "Test tech task",
            'content'     => "Test tech task",
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'date'        => $_SESSION['glpi_currenttime'],
        ]);

        // Add a task
        $task = new \TicketTask();
        $task_id = $task->add([
            'tickets_id' => $ticket_id,
            'users_id'   => 2,
            'content'    => "<p> Test task </p>",
            'state'      => 1,
            'users_id_tech'   => 4,
        ]);
        $this->integer(count($task->find(['id' => $task_id])))->isEqualTo(1);

        $ticket_user = new \Ticket_User();
        $this->integer(count($ticket_user->find(['tickets_id' => $ticket_id, 'users_id' => 4, 'type' => 2])))->isEqualTo(1);

        $task->update([
            'id' => $task_id,
            'tickets_id' => $ticket_id,
            'users_id' => 3,
            'users_id_tech' => 3,
        ]);

        $this->integer(count($ticket_user->find(['tickets_id' => $ticket_id, 'users_id' => 3, 'type' => 2])))->isEqualTo(1);
    }

    public function testAddTechToChangeFromTask()
    {
        $change = new \Change();
        $change_id = $change->add([
            'name'        => "Test tech task",
            'content'     => "Test tech task",
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'date'        => $_SESSION['glpi_currenttime'],
        ]);

        // Add a task
        $task = new \ChangeTask();
        $task_id = $task->add([
            'changes_id' => $change_id,
            'users_id'   => 4,
            'content'    => "<p> Test task </p>",
            'state'      => 1,
            'users_id_tech'   => 4,
        ]);
        $this->integer(count($task->find(['id' => $task_id])))->isEqualTo(1);

        $change_user = new \Change_User();
        $this->integer(count($change_user->find(['changes_id' => $change_id, 'users_id' => 4, 'type' => 2])))->isEqualTo(1);

        $task->update([
            'id' => $task_id,
            'changes_id' => $change_id,
            'users_id' => 3,
            'users_id_tech' => 3,
        ]);

        $this->integer(count($change_user->find(['changes_id' => $change_id, 'users_id' => 3, 'type' => 2])))->isEqualTo(1);
    }

    public function testAddTechToProblemFromTask()
    {
        $problem = new \Problem();
        $problem_id = $problem->add([
            'name'        => "Test tech task",
            'content'     => "Test tech task",
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'date'        => $_SESSION['glpi_currenttime'],
        ]);

        // Add a task
        $task = new \ProblemTask();
        $task_id = $task->add([
            'problems_id' => $problem_id,
            'users_id'   => 4,
            'content'    => "<p> Test task </p>",
            'state'      => 1,
            'users_id_tech'   => 4,
        ]);
        $this->integer(count($task->find(['id' => $task_id])))->isEqualTo(1);

        $problem_user = new \Problem_User();
        $this->integer(count($problem_user->find(['problems_id' => $problem_id, 'users_id' => 4, 'type' => 2])))->isEqualTo(1);
        $task->update([
            'id' => $task_id,
            'problems_id' => $problem_id,
            'users_id' => 3,
            'users_id_tech' => 3,
        ]);
        $this->integer(count($task->find(['id' => $task_id, 'users_id_tech' => 3])))->isEqualTo(1);

        $this->integer(count($problem_user->find(['problems_id' => $problem_id, 'users_id' => 3, 'type' => 2])))->isEqualTo(1);
    }
}
